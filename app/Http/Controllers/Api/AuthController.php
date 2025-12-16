<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'numeric', 'unique:users,phone'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $data = $validator->validated();

        $user = User::create([
            'uid' => Str::uuid(),
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);

        $token = auth('api')->login($user);

        $data = [
            'user' => [
                'id' => $user->id,
                'uid' => $user->uid,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'tokens_balance' => $user->tokens_balance,
                'status' => $user->status,
            ],
            'token' => $token,
            'token_type' => 'bearer',
        ];

        return $this->successResponse($data, 'User registered successfully', 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return $this->errorResponse('Invalid email or password', 401);
        }

        $user = auth('api')->user();

        $data = [
            'user' => [
                'id' => $user->id,
                'uid' => $user->uid,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'tokens_balance' => $user->tokens_balance,
                'status' => $user->status,
            ],
            'token' => $token,
            'token_type' => 'bearer',
        ];

        return $this->successResponse($data, 'Login successful', 200);
    }

    public function logout(Request $request)
    {
        try {
            auth('api')->logout();

            return $this->successResponse(null, 'Logged out successfully', 200);

        } catch (\Exception $e) {

            return $this->errorResponse('Failed to logout', 500);

        }
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return $this->successResponse($user, 'Profile retrieved', 200);
    }
}
