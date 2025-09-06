<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error', 
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Invalid credentials'
            ], 401);
        }

        $payload = [
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour expiration
        ];

        $jwtSecret = env('JWT_SECRET');
        if (!$jwtSecret) {
            return response()->json([
                'status' => 'error', 
                'message' => 'JWT secret not configured'
            ], 500);
        }

        $token = JWT::encode($payload, $jwtSecret, 'HS256');

        return response()->json([
            'status' => 'success', 
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'role' => $user->role
            ]
        ]);
    }
}