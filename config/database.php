<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define BASE_URL outside the class
define("BASE_URL", "https://printmont.com/");
// define("BASE_URL", "http://localhost/printmont-backend/");

class Database {
    private $host = "localhost";
    private $db_name = "u427250797_print_mont_adm";
    private $username = "u427250797_print_mont_adm";
    private $password = "!!e|b9gW";

    // private $db_name = "flipkart_admin";
    // private $username = "root";
    // private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            $this->conn->query("SET NAMES utf8");
        } catch(Exception $e) {
            echo "Connection error: " . $e->getMessage();
        }
        return $this->conn;
    }

    public function execute($query, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function fetch($query, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }

    public function fetchAll($query, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    }

    public function insert($query, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $success = $stmt->execute();
        $insert_id = $stmt->insert_id;
        $stmt->close();
        return $insert_id;
    }

    public function beginTransaction() {
        $conn = $this->getConnection();
        return $conn->begin_transaction();
    }

    public function commit() {
        $conn = $this->getConnection();
        return $conn->commit();
    }

    public function rollback() {
        $conn = $this->getConnection();
        return $conn->rollback();
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>