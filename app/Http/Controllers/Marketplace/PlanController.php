<?php

namespace Pterodactyl\Http\Controllers\Marketplace;

use Pterodactyl\Models\HostingPlan;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;

class PlanController extends Controller
{
    public function __construct(
        protected ViewFactory $view
    ) {}

    /**
     * Display the homepage with available plans.
     */
    public function index(): View
    {
        return $this->view->make('templates/base.core');
    }

    /**
     * Get all active plans (JSON API for frontend).
     */
    public function list(): JsonResponse
    {
        $plans = HostingPlan::query()
            ->where('is_active', true)
            ->with(['nest', 'egg'])
            ->orderBy('is_featured', 'desc')
            ->orderBy('price', 'asc')
            ->get();

        return response()->json([
            'data' => $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'uuid' => $plan->uuid,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'slug' => $plan->slug,
                    'memory' => $plan->memory,
                    'swap' => $plan->swap,
                    'disk' => $plan->disk,
                    'io' => $plan->io,
                    'cpu' => $plan->cpu,
                    'threads' => $plan->threads,
                    'nest' => [
                        'id' => $plan->nest->id,
                        'name' => $plan->nest->name,
                    ],
                    'egg' => [
                        'id' => $plan->egg->id,
                        'name' => $plan->egg->name,
                    ],
                    'price' => (float) $plan->price,
                    'billing_period' => $plan->billing_period,
                    'is_featured' => $plan->is_featured,
                    'is_available' => $plan->isAvailable(),
                    'formatted_price' => $plan->formatted_price,
                ];
            }),
        ]);
    }
}


