<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Fetch all active product filters
    $filtersSql = "SELECT id, filter_name, filter_key, filter_type, sort_order, status FROM product_filters WHERE status = 'active' ORDER BY sort_order ASC, id ASC";
    $filtersResult = $conn->query($filtersSql);
    $filters = [];
    $filterIds = [];

    if ($filtersResult && $filtersResult->num_rows > 0) {
        while ($row = $filtersResult->fetch_assoc()) {
            $row['values'] = [];
            $filters[$row['id']] = $row;
            $filterIds[] = (int)$row['id'];
        }
    }

    if (!empty($filterIds)) {
        $idsStr = implode(',', $filterIds);
        $valuesSql = "SELECT id, filter_id, value_name, value_slug, value_data, sort_order, status FROM product_filter_values WHERE filter_id IN ($idsStr) AND status = 'active' ORDER BY sort_order ASC, id ASC";
        $valuesResult = $conn->query($valuesSql);
        if ($valuesResult && $valuesResult->num_rows > 0) {
            while ($v = $valuesResult->fetch_assoc()) {
                if (isset($filters[$v['filter_id']])) {
                    $filters[$v['filter_id']]['values'][] = $v;
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => array_values($filters)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
