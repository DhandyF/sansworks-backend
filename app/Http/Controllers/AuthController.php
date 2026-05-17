<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected ActivityLogService $activityLog;

    public function __construct()
    {
        $this->activityLog = new ActivityLogService();
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            $this->activityLog->log('auth.failed_login', 'user', $request->username, [
                'username' => $request->username,
                'reason' => 'invalid_credentials',
            ], null);
            
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($user->status !== 'active') {
            $this->activityLog->log('auth.failed_login', 'user', $user->id, [
                'email' => $user->email,
                'username' => $user->username,
                'reason' => 'inactive_account',
            ], null);
            
            return response()->json([
                'message' => 'Account is inactive',
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->activityLog->log('auth.login', 'user', $user->id, [
            'email' => $user->email,
            'username' => $user->username,
            'name' => $user->name,
        ], $user->id);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(): JsonResponse
    {
        $user = auth()->user();
        
        $this->activityLog->log('auth.logout', 'user', $user->id, [
            'email' => $user->email,
            'username' => $user->username,
            'name' => $user->name,
        ]);

        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json(new UserResource(auth()->user()));
    }
}
