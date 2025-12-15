<?php
class Database {
    private $host = "localhost";
    private $db_name = "prinmont_db";
    private $username = "root";
    private $password = "";
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
            $types = str_repeat('s', count($params)); // All parameters as strings
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
            $types = str_repeat('s', count($params));
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
            $types = str_repeat('s', count($params));
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
            $types = str_repeat('s', count($params));
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
}
?>