<?php
/**
 * JWT Helper
 * Gestione token autenticazione
 */

class JWT {
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode($payload, $secret = null) {
        if ($secret === null) {
            $secret = getenv('JWT_SECRET') ?: 'default_secret_change_in_production';
        }

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function decode($jwt, $secret = null) {
        if ($secret === null) {
            $secret = getenv('JWT_SECRET') ?: 'default_secret_change_in_production';
        }

        $tokenParts = explode('.', $jwt);
        
        if (count($tokenParts) !== 3) {
            return false;
        }

        $header = self::base64UrlDecode($tokenParts[0]);
        $payload = self::base64UrlDecode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        if ($base64UrlSignature !== $signatureProvided) {
            return false;
        }

        $payload = json_decode($payload, true);

        // Verifica scadenza
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    public static function createToken($userId, $email, $plan = 'free') {
        $expiration = time() + (int)(getenv('JWT_EXPIRATION') ?: 604800); // 7 giorni default

        $payload = [
            'user_id' => $userId,
            'email' => $email,
            'plan' => $plan,
            'iat' => time(),
            'exp' => $expiration
        ];

        return self::encode($payload);
    }
}
