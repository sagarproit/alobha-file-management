<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        // Parse JWT manually (Header.Payload.Signature)
        list($header, $payload, $signature) = explode('.', $token);
        $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$payload", env('JWT_SECRET'), true)), '+/', '-_'), '=');

        if ($signature !== $expected) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $data = json_decode(base64_decode($payload));

        if ($data->exp < time()) {
            return response()->json(['error' => 'Token expired'], 401);
        }

        $user = User::find($data->sub);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // ✅ Properly bind the user to Laravel request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
