<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;

class RegisterController extends Controller
{
    public function __construct(
        protected ViewFactory $view
    ) {}

    /**
     * Display the registration page.
     */
    public function index(): View
    {
        return $this->view->make('templates/auth.core');
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|min:3|max:191|unique:users,username|regex:/^[a-z0-9_]+$/',
            'name_first' => 'required|string|max:191',
            'name_last' => 'required|string|max:191',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = User::create([
                'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'email' => $request->email,
                'username' => strtolower($request->username),
                'name_first' => $request->name_first,
                'name_last' => $request->name_last,
                'password' => Hash::make($request->password),
                'language' => 'en',
                'root_admin' => false,
                'use_totp' => false,
            ]);

            // Log the user in
            auth()->login($user);

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully',
                'redirect' => '/dashboard',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create account: ' . $e->getMessage(),
            ], 500);
        }
    }
}

