<?php
class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $name;
    public $description;
    public $category_id;
    public $brand;
    public $price;
    public $discount_price;
    public $stock_quantity;
    public $sku;
    public $status;
    public $featured;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllProducts($page = 1, $limit = 10, $search = '', $category = '') {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE 1=1";
        
        $params = array();
        
        if (!empty($search)) {
            $query .= " AND (p.name LIKE :search OR p.description LIKE :search OR p.sku LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($category)) {
            $query .= " AND p.category_id = :category";
            $params[':category'] = $category;
        }
        
        $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " p WHERE 1=1";
        if (!empty($search)) {
            $countQuery .= " AND (p.name LIKE :search OR p.description LIKE :search OR p.sku LIKE :search)";
        }
        if (!empty($category)) {
            $countQuery .= " AND p.category_id = :category";
        }
        
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $countStmt->bindValue($key, $value);
            }
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return array(
            "products" => $stmt->fetchAll(PDO::FETCH_ASSOC),
            "pagination" => array(
                "current_page" => $page,
                "per_page" => $limit,
                "total" => $total,
                "total_pages" => ceil($total / $limit)
            )
        );
    }

    public function getProductById() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->category_id = $row['category_id'];
            $this->brand = $row['brand'];
            $this->price = $row['price'];
            $this->discount_price = $row['discount_price'];
            $this->stock_quantity = $row['stock_quantity'];
            $this->sku = $row['sku'];
            $this->status = $row['status'];
            $this->featured = $row['featured'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, description=:description, category_id=:category_id, 
                      brand=:brand, price=:price, discount_price=:discount_price, 
                      stock_quantity=:stock_quantity, sku=:sku, status=:status, featured=:featured";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":brand", $this->brand);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":discount_price", $this->discount_price);
        $stmt->bindParam(":stock_quantity", $this->stock_quantity);
        $stmt->bindParam(":sku", $this->sku);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":featured", $this->featured);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, description=:description, category_id=:category_id, 
                      brand=:brand, price=:price, discount_price=:discount_price, 
                      stock_quantity=:stock_quantity, status=:status, featured=:featured,
                      updated_at=NOW()
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":brand", $this->brand);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":discount_price", $this->discount_price);
        $stmt->bindParam(":stock_quantity", $this->stock_quantity);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":featured", $this->featured);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
}
?>