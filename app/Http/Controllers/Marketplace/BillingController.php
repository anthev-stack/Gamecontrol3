<?php

namespace Pterodactyl\Http\Controllers\Marketplace;

use Pterodactyl\Models\Invoice;
use Pterodactyl\Models\Order;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;

class BillingController extends Controller
{
    public function __construct(
        protected ViewFactory $view
    ) {}

    /**
     * Display the billing dashboard.
     */
    public function index(): View
    {
        return $this->view->make('templates/base.core');
    }

    /**
     * Get user's orders.
     */
    public function orders(): JsonResponse
    {
        $orders = Order::where('user_id', auth()->id())
            ->with(['items.plan'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'uuid' => $order->uuid,
                    'order_number' => $order->order_number,
                    'total' => (float) $order->total,
                    'currency' => $order->currency,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toISOString(),
                    'completed_at' => $order->completed_at?->toISOString(),
                    'items' => $order->items->map(function ($item) {
                        return [
                            'plan' => $item->plan->name,
                            'quantity' => $item->quantity,
                            'price' => (float) $item->price,
                        ];
                    }),
                    'server' => $order->server ? [
                        'id' => $order->server->id,
                        'uuid' => $order->server->uuid,
                        'name' => $order->server->name,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Get user's invoices.
     */
    public function invoices(): JsonResponse
    {
        $invoices = Invoice::where('user_id', auth()->id())
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'uuid' => $invoice->uuid,
                    'invoice_number' => $invoice->invoice_number,
                    'total' => (float) $invoice->total,
                    'currency' => $invoice->currency,
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date?->toISOString(),
                    'created_at' => $invoice->created_at->toISOString(),
                    'paid_at' => $invoice->paid_at?->toISOString(),
                    'is_overdue' => $invoice->isOverdue(),
                ];
            }),
        ]);
    }

    /**
     * Get a specific invoice.
     */
    public function invoice(Invoice $invoice): JsonResponse
    {
        if ($invoice->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $invoice->load(['items', 'user', 'server']);

        return response()->json([
            'data' => [
                'id' => $invoice->id,
                'uuid' => $invoice->uuid,
                'invoice_number' => $invoice->invoice_number,
                'amount' => (float) $invoice->amount,
                'tax' => (float) $invoice->tax,
                'total' => (float) $invoice->total,
                'currency' => $invoice->currency,
                'status' => $invoice->status,
                'period_start' => $invoice->period_start?->toISOString(),
                'period_end' => $invoice->period_end?->toISOString(),
                'due_date' => $invoice->due_date?->toISOString(),
                'billing_name' => $invoice->billing_name,
                'billing_email' => $invoice->billing_email,
                'billing_address' => $invoice->billing_address,
                'notes' => $invoice->notes,
                'created_at' => $invoice->created_at->toISOString(),
                'paid_at' => $invoice->paid_at?->toISOString(),
                'items' => $invoice->items->map(function ($item) {
                    return [
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                    ];
                }),
            ],
        ]);
    }
}


