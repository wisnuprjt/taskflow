<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $token = auth('api')->attempt($request->validated());

        if (! $token) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => UserResource::make(auth('api')->user()),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Logged out.']);
    }
}
