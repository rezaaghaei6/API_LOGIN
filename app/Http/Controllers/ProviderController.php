<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Provider;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProviderController extends Controller
{
    private function authenticateJWT(Request $request)
    {
        try {
            $jwt = $request->bearerToken();
            if (!$jwt) {
                return ['error' => 'Token not provided', 'status' => 401];
            }

            $jwtSecret = env('JWT_SECRET');
            if (!$jwtSecret) {
                return ['error' => 'JWT secret not configured', 'status' => 500];
            }

            $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
            $user = User::find($decoded->sub);

            if (!$user) {
                return ['error' => 'User not found', 'status' => 401];
            }

            return ['user' => $user];
        } catch (Exception $e) {
            return ['error' => 'Invalid token: ' . $e->getMessage(), 'status' => 401];
        }
    }

    public function updateLocation(Request $request)
    {
        // اعتبارسنجی توکن JWT
        $authResult = $this->authenticateJWT($request);
        if (isset($authResult['error'])) {
            return response()->json([
                'status' => 'error', 
                'message' => $authResult['error']
            ], $authResult['status']);
        }

        $user = $authResult['user'];

        if ($user->role !== 'provider') {
            return response()->json([
                'status' => 'error', 
                'message' => 'Unauthorized role'
            ], 403);
        }

        // اعتبارسنجی ورودی‌ها
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'is_online' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error', 
                'message' => $validator->errors()->first()
            ], 400);
        }

        $provider = Provider::where('user_id', $user->id)->first();

        if (!$provider) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Provider not found'
            ], 404);
        }

        $provider->update([
            'lat' => $request->lat,
            'lng' => $request->lng,
            'is_online' => $request->is_online,
        ]);

        return response()->json([
            'status' => 'success', 
            'message' => 'Location updated successfully',
            'provider' => $provider
        ]);
    }

    public function getNearby(Request $request)
    {
        // اعتبارسنجی توکن JWT
        $authResult = $this->authenticateJWT($request);
        if (isset($authResult['error'])) {
            return response()->json([
                'status' => 'error', 
                'message' => $authResult['error']
            ], $authResult['status']);
        }

        // اعتبارسنجی ورودی‌ها
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'sometimes|numeric|min:0.1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error', 
                'message' => $validator->errors()->first()
            ], 400);
        }

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $radius = $request->input('radius', 5); // Default 5km radius

        $providers = Provider::selectRaw(
            'id, name, lat, lng, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
            [$lat, $lng, $lat]
        )
        ->where('is_online', true)
        ->whereNotNull('lat')
        ->whereNotNull('lng')
        ->having('distance', '<', $radius)
        ->orderBy('distance')
        ->get();

        return response()->json([
            'status' => 'success', 
            'providers' => $providers,
            'count' => $providers->count()
        ]);
    }
}