<?php
require_once __DIR__ . '/../vendor/autoload.php'; // If using composer
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Safe fallbacks so this class works even if config/constants.php (which has
// side-effecting CORS headers) hasn't been loaded in the current request.
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

    // $type is embedded in the token so an access token can't be replayed
    // as a refresh token and vice versa.
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

        return JWT::encode($tokenPayload, $this->secret, $this->algorithm);
    }

    // Issues a matched access + refresh token pair for a login/register/refresh response.
    public function generateTokenPair($payload): array {
        return [
            'access_token'  => $this->generateToken($payload, 'access'),
            'refresh_token' => $this->generateToken($payload, 'refresh'),
            'token_type'    => 'Bearer',
            'expires_in'    => self::ACCESS_TOKEN_TTL,
        ];
    }

    public function validateToken($token, string $expectedType = 'access') {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            if (($decoded->type ?? 'access') !== $expectedType) {
                return false;
            }
            return $decoded->data;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getTokenFromHeader() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
?>