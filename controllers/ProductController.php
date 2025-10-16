<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/JWTHandler.php';
require_once __DIR__ . '/../models/Product.php';

class ProductController {
    private $db;
    private $product;
    private $jwt;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->product = new Product($this->db);
        $this->jwt = new JWTHandler();
    }

    private function authenticate() {
        $token = $this->jwt->getTokenFromHeader();
        if (!$token) {
            http_response_code(401);
            echo json_encode(array("error" => "No token provided"));
            return false;
        }

        $decoded = $this->jwt->validateToken($token);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode(array("error" => "Invalid token"));
            return false;
        }

        return $decoded;
    }

    public function getAllProducts() {
        header('Content-Type: application/json');
        
        $user = $this->authenticate();
        if (!$user) return;

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $category = isset($_GET['category']) ? $_GET['category'] : '';

        $result = $this->product->getAllProducts($page, $limit, $search, $category);
        
        echo json_encode($result);
    }

    public function getProduct($id) {
        header('Content-Type: application/json');
        
        $user = $this->authenticate();
        if (!$user) return;

        $this->product->id = $id;
        if ($this->product->getProductById()) {
            echo json_encode(array(
                "product" => array(
                    "id" => $this->product->id,
                    "name" => $this->product->name,
                    "description" => $this->product->description,
                    "category_id" => $this->product->category_id,
                    "brand" => $this->product->brand,
                    "price" => $this->product->price,
                    "discount_price" => $this->product->discount_price,
                    "stock_quantity" => $this->product->stock_quantity,
                    "sku" => $this->product->sku,
                    "status" => $this->product->status,
                    "featured" => $this->product->featured,
                    "created_at" => $this->product->created_at
                )
            ));
        } else {
            http_response_code(404);
            echo json_encode(array("error" => "Product not found"));
        }
    }

    public function createProduct() {
        header('Content-Type: application/json');
        
        $user = $this->authenticate();
        if (!$user) return;

        $data = json_decode(file_get_contents("php://input"));

        // Validate required fields
        $required = ['name', 'category_id', 'price', 'sku'];
        foreach ($required as $field) {
            if (!isset($data->$field) || empty($data->$field)) {
                http_response_code(400);
                echo json_encode(array("error" => "Field '$field' is required"));
                return;
            }
        }

        $this->product->name = $data->name;
        $this->product->description = $data->description ?? '';
        $this->product->category_id = $data->category_id;
        $this->product->brand = $data->brand ?? '';
        $this->product->price = $data->price;
        $this->product->discount_price = $data->discount_price ?? null;
        $this->product->stock_quantity = $data->stock_quantity ?? 0;
        $this->product->sku = $data->sku;
        $this->product->status = $data->status ?? 'active';
        $this->product->featured = $data->featured ?? false;

        if ($this->product->create()) {
            http_response_code(201);
            echo json_encode(array("message" => "Product created successfully", "id" => $this->product->id));
        } else {
            http_response_code(500);
            echo json_encode(array("error" => "Unable to create product"));
        }
    }

    public function updateProduct($id) {
        header('Content-Type: application/json');
        
        $user = $this->authenticate();
        if (!$user) return;

        $data = json_decode(file_get_contents("php://input"));

        $this->product->id = $id;
        if (!$this->product->getProductById()) {
            http_response_code(404);
            echo json_encode(array("error" => "Product not found"));
            return;
        }

        // Update fields
        if (isset($data->name)) $this->product->name = $data->name;
        if (isset($data->description)) $this->product->description = $data->description;
        if (isset($data->category_id)) $this->product->category_id = $data->category_id;
        if (isset($data->brand)) $this->product->brand = $data->brand;
        if (isset($data->price)) $this->product->price = $data->price;
        if (isset($data->discount_price)) $this->product->discount_price = $data->discount_price;
        if (isset($data->stock_quantity)) $this->product->stock_quantity = $data->stock_quantity;
        if (isset($data->status)) $this->product->status = $data->status;
        if (isset($data->featured)) $this->product->featured = $data->featured;

        if ($this->product->update()) {
            echo json_encode(array("message" => "Product updated successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("error" => "Unable to update product"));
        }
    }
}
?>