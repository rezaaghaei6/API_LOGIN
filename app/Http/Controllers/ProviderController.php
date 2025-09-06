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
    public function updateLocation(Request $request)
    {
        // اعتبارسنجی توکن JWT
        try {
            $jwt = $request->bearerToken();
            if (!$jwt) {
                return response()->json(['status' => 'error', 'message' => 'Token not provided'], 401);
            }

            $decoded = JWT::decode($jwt, new Key(env('JWT_SECRET'), 'HS256'));
            $user = User::find($decoded->sub);

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 401);
            }

            if ($user->role !== 'provider') {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized role'], 403);
            }
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Invalid token'], 401);
        }

        // اعتبارسنجی ورودی‌ها
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'is_online' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $provider = Provider::where('user_id', $user->id)->first();

        if (!$provider) {
            return response()->json(['status' => 'error', 'message' => 'Provider not found'], 404);
        }

        $provider->update([
            'lat' => $request->lat,
            'lng' => $request->lng,
            'is_online' => $request->is_online,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Location updated successfully']);
    }

    public function getNearby(Request $request)
    {
        // اعتبارسنجی توکن JWT
        try {
            $jwt = $request->bearerToken();
            if (!$jwt) {
                return response()->json(['status' => 'error', 'message' => 'Token not provided'], 401);
            }

            $decoded = JWT::decode($jwt, new Key(env('JWT_SECRET'), 'HS256'));
            $user = User::find($decoded->sub);

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 401);
            }
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Invalid token'], 401);
        }

        // اعتبارسنجی ورودی‌ها
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $radius = 5; // 5km radius

        $providers = Provider::selectRaw(
            'id, name, lat, lng, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
            [$lat, $lng, $lat]
        )
        ->where('is_online', true)
        ->having('distance', '<', $radius)
        ->orderBy('distance')
        ->get();

        return response()->json(['status' => 'success', 'providers' => $providers]);
    }
}