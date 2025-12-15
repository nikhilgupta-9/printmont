<?php
require_once(__DIR__ . '/../config/database.php');

class CategoryModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllCategories() {
        $query = "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name";
        return $this->db->fetchAll($query);
    }

    public function getCategoryById($id) {
        $query = "SELECT * FROM categories WHERE id = ?";
        return $this->db->fetch($query, [$id]);
    }
}
?>