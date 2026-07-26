<?php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

if (!defined('JWT_SECRET')) define('JWT_SECRET', '609a34ea43dcbe481fd8b3b3df7f59c4d3ef4148cc84cfb47150ed958a050d24');
if (!defined('JWT_ALGORITHM')) define('JWT_ALGORITHM', 'HS256');

class JWTHandler {
    private $secret;
    private $algorithm;

    const ACCESS_TOKEN_TTL  = 3600;       // 1 hour
    const REFRESH_TOKEN_TTL = 2592000;    // 30 days

    public function __construct() {
        $this->secret = JWT_SECRET;
        $this->algorithm = JWT_ALGORITHM;
    }

    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode($data) {
        $b64 = str_replace(['-', '_'], ['+', '/'], $data);
        $remainder = strlen($b64) % 4;
        if ($remainder) {
            $b64 .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode($b64);
    }

    private function nativeEncode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    private function nativeDecode($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
        $signature = $this->base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        if (!hash_equals($expectedSignature, $signature)) return false;
        $payload = json_decode($this->base64UrlDecode($base64UrlPayload), true);
        if (isset($payload['exp']) && $payload['exp'] < time()) return false;
        return $payload;
    }

    public function generateToken($payload, string $type = 'access', ?int $ttl = null) {
        $issuedAt = time();
        $ttl = $ttl ?? ($type === 'refresh' ? self::REFRESH_TOKEN_TTL : self::ACCESS_TOKEN_TTL);

        $tokenPayload = [
            "iss"  => "printmont-api",
            "aud"  => "printmont-api",
            "iat"  => $issuedAt,
            "exp"  => $issuedAt + $ttl,
            "type" => $type,
            "data" => $payload,
        ];

        if (class_exists('Firebase\JWT\JWT')) {
            try {
                return \Firebase\JWT\JWT::encode($tokenPayload, $this->secret, $this->algorithm);
            } catch (\Throwable $e) {
                return $this->nativeEncode($tokenPayload);
            }
        }

        return $this->nativeEncode($tokenPayload);
    }

    public function generateTokenPair($payload): array {
        return [
            'access_token'  => $this->generateToken($payload, 'access'),
            'refresh_token' => $this->generateToken($payload, 'refresh'),
            'token_type'    => 'Bearer',
            'expires_in'    => self::ACCESS_TOKEN_TTL,
        ];
    }

    public function validateToken($token, string $expectedType = 'access') {
        if (empty($token)) return false;

        if (class_exists('Firebase\JWT\JWT') && class_exists('Firebase\JWT\Key')) {
            try {
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($this->secret, $this->algorithm));
                if (($decoded->type ?? 'access') !== $expectedType) {
                    return false;
                }
                return json_decode(json_encode($decoded->data), true);
            } catch (\Throwable $e) {
                // Fallback to native decode
            }
        }

        $decoded = $this->nativeDecode($token);
        if (!$decoded || ($decoded['type'] ?? 'access') !== $expectedType) {
            return false;
        }
        return $decoded['data'] ?? false;
    }

    public function getTokenFromHeader() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
?>