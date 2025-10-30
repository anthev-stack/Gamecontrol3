<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Marketplace;

/*
|--------------------------------------------------------------------------
| Marketplace Routes
|--------------------------------------------------------------------------
|
| These routes handle the hosting marketplace features including the
| homepage, shopping cart, checkout, and billing.
|
*/

// Public routes (no authentication required - NO MIDDLEWARE!)
Route::withoutMiddleware(['auth', 'auth.session'])->group(function () {
    Route::get('/', [Marketplace\PlanController::class, 'index'])->name('index');
    Route::get('/plans', [Marketplace\PlanController::class, 'list'])->name('marketplace.plans.list');
});
Route::get('/cart', [Marketplace\CartController::class, 'index'])->name('marketplace.cart.index');
Route::post('/cart/add', [Marketplace\CartController::class, 'add'])->name('marketplace.cart.add');
Route::get('/cart/show', [Marketplace\CartController::class, 'show'])->name('marketplace.cart.show');
Route::delete('/cart/remove/{id}', [Marketplace\CartController::class, 'remove'])->name('marketplace.cart.remove');
Route::patch('/cart/update/{id}', [Marketplace\CartController::class, 'update'])->name('marketplace.cart.update');
Route::post('/cart/clear', [Marketplace\CartController::class, 'clear'])->name('marketplace.cart.clear');

// Checkout routes (registration happens during checkout if needed)
Route::get('/checkout', [Marketplace\CheckoutController::class, 'index'])->name('marketplace.checkout.index');
Route::post('/checkout/complete', [Marketplace\CheckoutController::class, 'complete'])->name('marketplace.checkout.complete');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Billing dashboard
    Route::get('/billing', [Marketplace\BillingController::class, 'index'])->name('marketplace.billing.index');
    Route::get('/billing/orders', [Marketplace\BillingController::class, 'orders'])->name('marketplace.billing.orders');
    Route::get('/billing/invoices', [Marketplace\BillingController::class, 'invoices'])->name('marketplace.billing.invoices');
    Route::get('/billing/invoices/{invoice}', [Marketplace\BillingController::class, 'invoice'])->name('marketplace.billing.invoice');
    
    // Split billing
    Route::prefix('/servers/{server}/billing')->name('marketplace.billing.')->group(function () {
        Route::get('/shares', [Marketplace\SplitBillingController::class, 'getServerShares'])->name('shares');
        Route::post('/invite', [Marketplace\SplitBillingController::class, 'sendInvitation'])->name('invite');
        Route::delete('/shares/{user}', [Marketplace\SplitBillingController::class, 'removeShare'])->name('remove-share');
    });
    
    Route::get('/billing/invitations', [Marketplace\SplitBillingController::class, 'getInvitations'])->name('marketplace.billing.invitations');
    Route::post('/billing/invitations/{token}/accept', [Marketplace\SplitBillingController::class, 'acceptInvitation'])->name('marketplace.billing.accept-invitation');
    Route::post('/billing/invitations/{token}/decline', [Marketplace\SplitBillingController::class, 'declineInvitation'])->name('marketplace.billing.decline-invitation');
    Route::delete('/billing/invitations/{invitation}', [Marketplace\SplitBillingController::class, 'cancelInvitation'])->name('marketplace.billing.cancel-invitation');
});


