<?php
header("Content-Type: application/json");
require_once(__DIR__ . '/cors.php');
require_once(__DIR__ . '/../config/database.php');

$database = new Database();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($method === 'GET') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

    $params = [];
    $sql = "SELECT id, question, answer, product_id, category_id, helpful_count as likes, not_helpful_count as dislikes,
                   'Printmont Seller' as author, 'Verified Response' as seller, created_at
            FROM faqs
            WHERE is_active = 1 AND (status = 'active' OR status IS NULL)";

    if ($productId > 0) {
        $sql .= " AND (product_id = ? OR product_id IS NULL OR product_id = 0)";
        $params[] = $productId;
    } elseif ($categoryId > 0) {
        $sql .= " AND (category_id = ? OR category_id IS NULL OR category_id = 0)";
        $params[] = $categoryId;
    }

    $sql .= " ORDER BY (product_id = " . (int)$productId . ") DESC, display_order ASC, id ASC LIMIT 20";

    try {
        $faqs = $database->fetchAll($sql, $params);
        echo json_encode([
            'success' => true,
            'data' => $faqs,
            'count' => count($faqs)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'data' => []
        ]);
    }
    exit();
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? 'ask';

    if ($action === 'reaction') {
        $faqId = (int)($input['faq_id'] ?? $input['id'] ?? 0);
        $type = $input['type'] ?? 'like'; // 'like' or 'dislike'

        if ($faqId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid question ID']);
            exit();
        }

        if ($type === 'like') {
            $database->execute("UPDATE faqs SET helpful_count = helpful_count + 1 WHERE id = ?", [$faqId]);
        } else {
            $database->execute("UPDATE faqs SET not_helpful_count = not_helpful_count + 1 WHERE id = ?", [$faqId]);
        }

        $row = $database->fetch("SELECT id, helpful_count as likes, not_helpful_count as dislikes FROM faqs WHERE id = ?", [$faqId]);
        echo json_encode([
            'success' => true,
            'message' => 'Reaction saved',
            'data' => $row
        ]);
        exit();
    }

    if ($action === 'ask') {
        $question = trim($input['question'] ?? '');
        $productId = (int)($input['product_id'] ?? 0);
        $name = trim($input['name'] ?? 'Customer');
        $email = trim($input['email'] ?? '');

        if (empty($question)) {
            echo json_encode(['success' => false, 'error' => 'Please enter a valid question.']);
            exit();
        }

        // Insert as a pending question (admin can answer in faq-view.php)
        $database->execute(
            "INSERT INTO faqs (question, answer, product_id, is_active, status, display_order, helpful_count, not_helpful_count)
             VALUES (?, ?, ?, 1, 'active', 99, 0, 0)",
            [$question, 'Thanks for your question! Our product specialist is reviewing it and will provide a detailed answer shortly.', $productId > 0 ? $productId : null]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Your question has been submitted successfully!'
        ]);
        exit();
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
