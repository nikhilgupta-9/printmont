<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$db = new Database();           // ✅ create object
$conn = $db->getConnection();  // ✅ call method

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if (isset($_GET['parent_id'])) {
    $parent_id = (int) $_GET['parent_id'];

    $sql = "SELECT id, name 
            FROM categories 
            WHERE parent_id = $parent_id 
            AND level = 2 
            AND status = 'active' 
            ORDER BY display_order, name";

    $result = $conn->query($sql);

    $categories = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    echo json_encode($categories);
} else {
    echo json_encode([]);
}

$conn->close();
