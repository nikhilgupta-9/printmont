<?php
class Database {
    private $host = "localhost";
    private $db_name = "flipkart_admin";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
    $this->conn = null;
    try {
      $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
      $this->conn->query("SET NAMES utf8");
    } catch(Exception $e) {
      echo "Connection error: " . $e->getMessage();
    }
    return $this->conn;
  }
}
?>