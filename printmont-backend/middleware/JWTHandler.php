<?php
require_once __DIR__ . '/../vendor/autoload.php'; // If using composer
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler {
    private $secret;
    private $algorithm;

    public function __construct() {
        $this->secret = JWT_SECRET;
        $this->algorithm = JWT_ALGORITHM;
    }

    public function generateToken($payload) {
        $issuedAt = time();
        $expire = $issuedAt + (7 * 24 * 60 * 60); // 7 days
        
        $tokenPayload = array(
            "iss" => "flipkart-admin",
            "aud" => "flipkart-admin",
            "iat" => $issuedAt,
            "exp" => $expire,
            "data" => $payload
        );

        return JWT::encode($tokenPayload, $this->secret, $this->algorithm);
    }

    public function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
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