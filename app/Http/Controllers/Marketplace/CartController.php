<?php

namespace Pterodactyl\Http\Controllers\Marketplace;

use Pterodactyl\Models\Cart;
use Pterodactyl\Models\CartItem;
use Pterodactyl\Models\HostingPlan;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;

class CartController extends Controller
{
    public function __construct(
        protected ViewFactory $view
    ) {}

    /**
     * Display the cart page.
     */
    public function index(): View
    {
        return $this->view->make('templates/base.core');
    }

    /**
     * Get current cart.
     */
    public function show(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);

        return response()->json([
            'data' => [
                'id' => $cart->id,
                'uuid' => $cart->uuid,
                'items' => $cart->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'plan' => [
                            'id' => $item->plan->id,
                            'name' => $item->plan->name,
                            'slug' => $item->plan->slug,
                            'price' => (float) $item->plan->price,
                            'billing_period' => $item->plan->billing_period,
                        ],
                        'quantity' => $item->quantity,
                        'price_at_time' => (float) $item->price_at_time,
                        'subtotal' => (float) $item->subtotal,
                    ];
                }),
                'total' => (float) $cart->total,
                'item_count' => $cart->items->sum('quantity'),
            ],
        ]);
    }

    /**
     * Add item to cart.
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:hosting_plans,id',
            'quantity' => 'sometimes|integer|min:1|max:10',
        ]);

        $plan = HostingPlan::findOrFail($request->plan_id);

        if (!$plan->isAvailable()) {
            return response()->json([
                'error' => 'This plan is currently unavailable.',
            ], 400);
        }

        $cart = $this->getOrCreateCart($request);

        // Check if item already exists in cart
        $existingItem = $cart->items()->where('plan_id', $plan->id)->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $request->quantity ?? 1);
        } else {
            $cart->items()->create([
                'plan_id' => $plan->id,
                'quantity' => $request->quantity ?? 1,
                'price_at_time' => $plan->price,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function remove(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);

        $cart->items()->where('id', $itemId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
        ]);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, int $itemId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        $cart = $this->getOrCreateCart($request);
        $item = $cart->items()->findOrFail($itemId);

        $item->update([
            'quantity' => $request->quantity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
        ]);
    }

    /**
     * Clear all items from cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared',
        ]);
    }

    /**
     * Get or create a cart for the current user/guest.
     */
    protected function getOrCreateCart(Request $request): Cart
    {
        $userId = auth()->id();
        $sessionToken = null;

        if (!$userId) {
            $sessionToken = $request->session()->get('cart_session_token') ?? Str::random(32);
            $request->session()->put('cart_session_token', $sessionToken);
        }

        if ($userId) {
            return Cart::firstOrCreate(
                ['user_id' => $userId],
                ['uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()]
            );
        }

        return Cart::firstOrCreate(
            ['session_token' => $sessionToken],
            ['uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()]
        );
    }
}


