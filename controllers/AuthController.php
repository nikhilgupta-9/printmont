<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/JWTHandler.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;
    private $user;
    private $jwt;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
        $this->jwt = new JWTHandler();
    }

    public function login() {
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->email) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode(array("error" => "Email and password are required"));
            return;
        }

        $this->user->email = $data->email;
        $userExists = $this->user->getUserByEmail();

        if ($userExists && password_verify($data->password, $this->user->password)) {
            if ($this->user->status !== 'active') {
                http_response_code(403);
                echo json_encode(array("error" => "Account is deactivated"));
                return;
            }

            // Update last login
            $this->user->updateLastLogin();

            // Generate token
            $tokenData = array(
                "id" => $this->user->id,
                "email" => $this->user->email,
                "role" => $this->user->role
            );

            $token = $this->jwt->generateToken($tokenData);

            echo json_encode(array(
                "message" => "Login successful",
                "token" => $token,
                "user" => array(
                    "id" => $this->user->id,
                    "username" => $this->user->username,
                    "email" => $this->user->email,
                    "role" => $this->user->role
                )
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("error" => "Invalid credentials"));
        }
    }

    public function getCurrentUser() {
        header('Content-Type: application/json');
        
        $token = $this->jwt->getTokenFromHeader();
        if (!$token) {
            http_response_code(401);
            echo json_encode(array("error" => "No token provided"));
            return;
        }

        $decoded = $this->jwt->validateToken($token);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode(array("error" => "Invalid token"));
            return;
        }

        $this->user->id = $decoded->id;
        if ($this->user->getUserById()) {
            echo json_encode(array(
                "user" => array(
                    "id" => $this->user->id,
                    "username" => $this->user->username,
                    "email" => $this->user->email,
                    "role" => $this->user->role,
                    "last_login" => $this->user->last_login
                )
            ));
        } else {
            http_response_code(404);
            echo json_encode(array("error" => "User not found"));
        }
    }
}
?>