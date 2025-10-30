<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Base;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;

// Homepage shows marketplace for guests, dashboard for authenticated users
Route::get('/', [Base\IndexController::class, 'index'])->name('index')
    ->withoutMiddleware(['auth', 'auth.session', RequireTwoFactorAuthentication::class]);

// User dashboard (authenticated)
Route::get('/dashboard', [Base\IndexController::class, 'dashboard'])->name('dashboard');

// Account settings (authenticated)
Route::get('/account', [Base\IndexController::class, 'index'])
    ->withoutMiddleware(RequireTwoFactorAuthentication::class)
    ->name('account');

Route::get('/locales/locale.json', Base\LocaleController::class)
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class])
    ->where('namespace', '.*');

Route::get('/{react}', [Base\IndexController::class, 'index'])
    ->where('react', '^(?!(\/)?(api|auth|admin|daemon)).+');
