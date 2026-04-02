<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    // ─── POST /api/auth/login ────────────────────────────────────────────────
    public function login(LoginRequest $request, AuthService $authService): JsonResponse
    {
        $result = $authService->login($request->validated());
        return response()->json(
            collect($result)->except('code')->toArray(),
            $result['code']
        );
    }

    // ─── POST /api/auth/logout ───────────────────────────────────────────────
    public function logout(Request $request, AuthService $authService): JsonResponse
    {
        $result = $authService->logout($request->user());

        return response()->json(
            collect($result)->except('code')->toArray(),
            $result['code']
        );
    }

    // ─── GET /api/auth/me ────────────────────────────────────────────────────
    public function me(Request $request, AuthService $authService): JsonResponse
    {
        $result = $authService->me($request->user());

        return response()->json(
            collect($result)->except('code')->toArray(),
            $result['code']
        );
    }
}
