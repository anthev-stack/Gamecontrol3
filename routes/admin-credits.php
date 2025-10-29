<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin\CreditManagementController;

/*
|--------------------------------------------------------------------------
| Admin Credit Management Routes
|--------------------------------------------------------------------------
|
| Routes for managing user credits in the admin panel.
|
*/

Route::prefix('/credits')->name('admin.credits.')->group(function () {
    Route::get('/', [CreditManagementController::class, 'index'])->name('index');
    Route::get('/users', [CreditManagementController::class, 'users'])->name('users');
    Route::get('/users/{user}/transactions', [CreditManagementController::class, 'userTransactions'])->name('user.transactions');
    Route::post('/grant', [CreditManagementController::class, 'grantCredits'])->name('grant');
    Route::post('/deduct', [CreditManagementController::class, 'deductCredits'])->name('deduct');
    Route::get('/statistics', [CreditManagementController::class, 'statistics'])->name('statistics');
});

