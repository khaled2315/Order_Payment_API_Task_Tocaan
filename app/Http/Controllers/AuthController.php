<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Login an existing user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = auth('api')->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout the current user.
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }
}
