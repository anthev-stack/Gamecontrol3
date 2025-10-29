<?php

namespace Pterodactyl\Http\Controllers\Marketplace;

use Pterodactyl\Models\Cart;
use Pterodactyl\Models\Order;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;

class CheckoutController extends Controller
{
    public function __construct(
        protected ViewFactory $view,
        protected ServerCreationService $serverCreationService
    ) {}

    /**
     * Display the checkout page.
     */
    public function index(): View
    {
        return $this->view->make('templates/base.core');
    }

    /**
     * Complete the checkout process.
     */
    public function complete(Request $request): JsonResponse
    {
        $request->validate([
            'billing_name' => 'required|string|max:191',
            'billing_email' => 'required|email|max:191',
            'billing_address' => 'nullable|string',
            'billing_city' => 'nullable|string|max:191',
            'billing_state' => 'nullable|string|max:191',
            'billing_country' => 'nullable|string|max:191',
            'billing_postal_code' => 'nullable|string|max:191',
            'register' => 'sometimes|boolean',
            'password' => 'required_if:register,true|min:8',
            'create_account' => 'sometimes|boolean',
            'use_credits' => 'sometimes|boolean',
            'credits_amount' => 'sometimes|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Get or create cart
                $cart = $this->getCart($request);

                if ($cart->isEmpty()) {
                    return response()->json([
                        'error' => 'Your cart is empty',
                    ], 400);
                }

                // Get or create user
                $user = auth()->user();
                
                if (!$user) {
                    if ($request->register || $request->create_account) {
                        $user = $this->createUser($request);
                    } else {
                        return response()->json([
                            'error' => 'Please create an account to continue',
                        ], 401);
                    }
                }

                // Calculate totals
                $subtotal = $cart->total;
                $tax = 0; // Implement tax calculation if needed
                $total = $subtotal + $tax;

                // Handle credit payment
                $creditsUsed = 0;
                $remainingAmount = $total;

                if ($request->use_credits && $user->credits > 0) {
                    $creditsUsed = min($user->credits, $total);
                    $remainingAmount = $total - $creditsUsed;

                    // Deduct credits from user
                    $previousBalance = $user->credits;
                    $user->credits = $previousBalance - $creditsUsed;
                    $user->save();
                }

                // Create order
                $order = Order::create([
                    'user_id' => $user->id,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'currency' => 'USD',
                    'status' => 'pending',
                    'billing_name' => $request->billing_name,
                    'billing_email' => $request->billing_email,
                    'billing_address' => $request->billing_address,
                    'billing_city' => $request->billing_city,
                    'billing_state' => $request->billing_state,
                    'billing_country' => $request->billing_country,
                    'billing_postal_code' => $request->billing_postal_code,
                ]);

                // Create order items
                foreach ($cart->items as $item) {
                    $order->items()->create([
                        'plan_id' => $item->plan_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price_at_time,
                        'plan_snapshot' => json_encode([
                            'name' => $item->plan->name,
                            'description' => $item->plan->description,
                        ]),
                    ]);
                }

                // Record credit transaction if credits were used
                if ($creditsUsed > 0) {
                    \Pterodactyl\Models\CreditTransaction::create([
                        'user_id' => $user->id,
                        'amount' => -$creditsUsed,
                        'balance_after' => $user->credits,
                        'type' => 'payment',
                        'reason' => 'payment',
                        'description' => "Payment for order {$order->order_number}",
                        'order_id' => $order->id,
                    ]);
                }

                // For now, we'll create the server immediately
                // In production, you'd process remaining payment first if remainingAmount > 0
                if ($remainingAmount > 0) {
                    // TODO: Process payment gateway for $remainingAmount
                    // For development, we'll proceed anyway
                }

                $server = $this->createServerFromCart($cart, $user, $order);

                // Update order with server reference
                $order->update([
                    'server_id' => $server->id,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Clear cart
                $cart->items()->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Order completed successfully',
                    'data' => [
                        'order' => [
                            'id' => $order->id,
                            'uuid' => $order->uuid,
                            'order_number' => $order->order_number,
                            'total' => (float) $order->total,
                            'credits_used' => (float) $creditsUsed,
                            'remaining_amount' => (float) $remainingAmount,
                        ],
                        'server' => [
                            'id' => $server->id,
                            'uuid' => $server->uuid,
                            'name' => $server->name,
                        ],
                        'user' => [
                            'remaining_credits' => (float) $user->credits,
                        ],
                    ],
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to complete checkout: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the current cart.
     */
    protected function getCart(Request $request): Cart
    {
        if (auth()->check()) {
            return Cart::where('user_id', auth()->id())->with('items.plan')->firstOrNew();
        }

        $sessionToken = $request->session()->get('cart_session_token');
        if ($sessionToken) {
            return Cart::where('session_token', $sessionToken)->with('items.plan')->firstOrNew();
        }

        return new Cart();
    }

    /**
     * Create a new user from checkout details.
     */
    protected function createUser(Request $request): \Pterodactyl\Models\User
    {
        $userData = [
            'username' => $request->billing_email, // or generate from email
            'email' => $request->billing_email,
            'name_first' => explode(' ', $request->billing_name, 2)[0],
            'name_last' => explode(' ', $request->billing_name, 2)[1] ?? '',
            'password' => bcrypt($request->password),
        ];

        return \Pterodactyl\Models\User::create($userData);
    }

    /**
     * Create server from cart items.
     */
    protected function createServerFromCart(Cart $cart, \Pterodactyl\Models\User $user, Order $order)
    {
        // For now, create server from first cart item
        // In production, you might want to handle multiple items differently
        $item = $cart->items->first();
        $plan = $item->plan;

        $serverData = [
            'name' => $user->username . "'s Server",
            'owner_id' => $user->id,
            'node_id' => 1, // Get from plan or node pool
            'allocation_id' => 1, // Get available allocation
            'nest_id' => $plan->nest_id,
            'egg_id' => $plan->egg_id,
            'memory' => $plan->memory,
            'swap' => $plan->swap,
            'disk' => $plan->disk,
            'io' => $plan->io,
            'cpu' => $plan->cpu,
            'threads' => $plan->threads,
            'database_limit' => $plan->database_limit,
            'allocation_limit' => $plan->allocation_limit,
            'backup_limit' => $plan->backup_limit,
            'startup' => '', // Get from egg
            'image' => '', // Get from egg
        ];

        return $this->serverCreationService->handle($serverData);
    }
}


