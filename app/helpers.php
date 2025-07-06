<?php

use App\Models\User;
use App\Models\AuditLog;

function logRepoAction($repositoryId, $userId, $action, $details = null)
{
    AuditLog::create([
        'repository_id' => $repositoryId,
        'user_id' => $userId,
        'action' => $action,
        'details' => $details
    ]);
}


function generate_jwt(User $user)
{
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $payload = json_encode([
        'sub' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'iat' => time(),
        'exp' => time() + 3600 // 1 hour
    ]);

    $base64UrlHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $base64UrlPayload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');

    $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", env('JWT_SECRET'), true);
    $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
}
