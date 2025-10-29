<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Pterodactyl\Models\User;
use Pterodactyl\Models\CreditTransaction;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;

class CreditManagementController extends Controller
{
    public function __construct(
        protected ViewFactory $view
    ) {}

    /**
     * Display the credit management page.
     */
    public function index(): View
    {
        return $this->view->make('admin.credits.index');
    }

    /**
     * Get all users with credit information.
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::query()
            ->select(['id', 'uuid', 'username', 'email', 'name_first', 'name_last', 'credits'])
            ->orderBy('credits', 'desc');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(50);

        return response()->json($users);
    }

    /**
     * Get user's credit transaction history.
     */
    public function userTransactions(User $user): JsonResponse
    {
        $transactions = CreditTransaction::where('user_id', $user->id)
            ->with(['admin'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($transactions);
    }

    /**
     * Grant credits to a user.
     */
    public function grantCredits(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01|max:10000',
            'reason' => 'required|in:giveaway,refund,gift',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = User::findOrFail($request->user_id);
            $amount = (float) $request->amount;

            // Update user credits
            $previousBalance = $user->credits;
            $user->credits = $previousBalance + $amount;
            $user->save();

            // Create transaction record
            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'admin_id' => auth()->id(),
                'amount' => $amount,
                'balance_after' => $user->credits,
                'type' => 'admin_grant',
                'reason' => $request->reason,
                'description' => $request->description,
                'metadata' => json_encode([
                    'admin_email' => auth()->user()->email,
                    'admin_username' => auth()->user()->username,
                ]),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully granted ${amount} credits to {$user->email}",
                'data' => [
                    'transaction' => $transaction,
                    'new_balance' => $user->credits,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to grant credits: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deduct credits from a user (admin only).
     */
    public function deductCredits(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = User::findOrFail($request->user_id);
            $amount = (float) $request->amount;

            if ($user->credits < $amount) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient credits',
                ], 400);
            }

            // Update user credits
            $previousBalance = $user->credits;
            $user->credits = $previousBalance - $amount;
            $user->save();

            // Create transaction record
            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'admin_id' => auth()->id(),
                'amount' => -$amount, // Negative for deduction
                'balance_after' => $user->credits,
                'type' => 'admin_grant',
                'reason' => 'other',
                'description' => $request->description ?? $request->reason,
                'metadata' => json_encode([
                    'admin_email' => auth()->user()->email,
                    'admin_username' => auth()->user()->username,
                ]),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deducted ${amount} credits from {$user->email}",
                'data' => [
                    'transaction' => $transaction,
                    'new_balance' => $user->credits,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to deduct credits: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get credit statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_credits_in_circulation' => User::sum('credits'),
            'users_with_credits' => User::where('credits', '>', 0)->count(),
            'total_users' => User::count(),
            'credits_granted_30_days' => CreditTransaction::where('type', 'admin_grant')
                ->where('amount', '>', 0)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('amount'),
            'credits_used_30_days' => abs(CreditTransaction::where('type', 'payment')
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('amount')),
            'recent_transactions' => CreditTransaction::with(['user', 'admin'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json($stats);
    }
}

