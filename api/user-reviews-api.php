<?php
// printmont-backend/api/user-reviews-api.php - Dedicated API for User Dashboard Product Reviews

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthControllerAPI.php';
require_once __DIR__ . '/../services/CoinService.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Database connection failed']);
    exit;
}

// 1. Resolve Authorization Token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['Authorization'] ?? '');
if (empty($authHeader) && function_exists('apache_request_headers')) {
    $requestHeaders = apache_request_headers();
    $authHeader = $requestHeaders['Authorization'] ?? ($requestHeaders['authorization'] ?? '');
}

$token = null;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
} elseif (!empty($_GET['token'])) {
    $token = trim($_GET['token']);
} elseif (!empty($_POST['token'])) {
    $token = trim($_POST['token']);
}

$authUser = null;
if (!empty($token)) {
    $authResult = (new AuthController($db))->verifyToken($token);
    if (!empty($authResult['success']) && !empty($authResult['user'])) {
        $authUser = $authResult['user'];
    }
}

if (!$authUser) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'require_login' => true,
        'message' => 'Please sign in to view or manage your product reviews.'
    ]);
    exit;
}

$userId = (int)$authUser['id'];
$userEmail = trim($authUser['email'] ?? '');
$userPhone = trim($authUser['phone'] ?? '');
$userName = trim(($authUser['first_name'] ?? '') . ' ' . ($authUser['last_name'] ?? '')) ?: ($authUser['username'] ?? 'Customer');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_reviews');

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput, true);
    if (is_array($jsonInput)) {
        if (!empty($jsonInput['action'])) {
            $action = $jsonInput['action'];
        }
    } else {
        $jsonInput = $_POST;
    }
}

$coinService = new CoinService($db);
$coinSettings = $coinService->getSettings();
$reviewBonusCoins = (int)($coinSettings['review_bonus_coins'] ?? 25);

// Helper to format image paths
function formatReviewImageUrl($path) {
    if (empty($path)) return null;
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    return 'http://localhost/printmont/printmont-backend/' . ltrim($path, '/');
}

// -------------------------------------------------------------
// ACTION: GET_REVIEWS (Fetch purchased to-review items & already reviewed)
// -------------------------------------------------------------
if ($method === 'GET' || ($method === 'POST' && $action === 'get_reviews')) {
    // A. Fetch All Purchased Products for User
    $purchaseQuery = "
        SELECT 
            oi.id as order_item_id,
            oi.order_id,
            oi.product_id,
            oi.product_name,
            oi.product_sku,
            oi.unit_price,
            oi.quantity,
            oi.product_image as item_image,
            o.order_number,
            o.status as order_status,
            o.created_at as order_date,
            p.name as live_product_name,
            p.product_slug,
            p.thumbnail_image as live_thumbnail,
            p.price as live_price
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE (o.user_id = ? 
           OR (o.customer_email IS NOT NULL AND o.customer_email != '' AND o.customer_email = ?)
           OR (? != '' AND o.customer_phone IS NOT NULL AND o.customer_phone = ?))
          AND o.status NOT IN ('cancelled', 'refunded')
        ORDER BY (CASE WHEN o.status IN ('delivered', 'completed') THEN 1 ELSE 2 END) ASC, o.created_at DESC, oi.id DESC
    ";

    $pStmt = $db->prepare($purchaseQuery);
    $pStmt->bind_param("isss", $userId, $userEmail, $userPhone, $userPhone);
    $pStmt->execute();
    $pResult = $pStmt->get_result();

    $purchasedByProduct = [];
    while ($row = $pResult->fetch_assoc()) {
        $pid = (int)$row['product_id'];
        if ($pid <= 0) continue;

        // Keep most recent purchase per distinct product
        if (!isset($purchasedByProduct[$pid])) {
            $img = !empty($row['live_thumbnail']) ? $row['live_thumbnail'] : $row['item_image'];
            $purchasedByProduct[$pid] = [
                'product_id' => $pid,
                'product_name' => $row['live_product_name'] ?: ($row['product_name'] ?: 'Product #' . $pid),
                'product_slug' => $row['product_slug'] ?: '',
                'product_image' => formatReviewImageUrl($img),
                'order_id' => (int)$row['order_id'],
                'order_number' => $row['order_number'],
                'order_status' => $row['order_status'],
                'order_date' => $row['order_date'],
                'unit_price' => (float)($row['live_price'] ?? $row['unit_price']),
                'quantity' => (int)$row['quantity']
            ];
        }
    }

    // B. Fetch User's Existing Reviews
    $revQuery = "
        SELECT 
            r.id,
            r.product_id,
            r.user_id,
            r.customer_name,
            r.rating,
            r.title,
            r.comment,
            r.status,
            r.is_verified,
            r.helpful_votes,
            r.unhelpful_votes,
            r.occasion,
            r.city,
            r.images,
            r.admin_reply,
            r.created_at,
            r.updated_at,
            p.name as live_product_name,
            p.product_slug,
            p.thumbnail_image
        FROM product_reviews r
        LEFT JOIN products p ON r.product_id = p.id
        WHERE (r.user_id = ? OR (r.customer_email IS NOT NULL AND r.customer_email != '' AND r.customer_email = ?))
        ORDER BY r.created_at DESC
    ";

    $rStmt = $db->prepare($revQuery);
    $rStmt->bind_param("is", $userId, $userEmail);
    $rStmt->execute();
    $rResult = $rStmt->get_result();

    $reviewedItems = [];
    $reviewedProductIds = [];

    while ($rRow = $rResult->fetch_assoc()) {
        $pid = (int)$rRow['product_id'];
        $reviewedProductIds[$pid] = true;

        // Format images array
        $imagesList = [];
        if (!empty($rRow['images'])) {
            $decoded = json_decode($rRow['images'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $img) {
                    $imagesList[] = formatReviewImageUrl($img);
                }
            } elseif (is_string($rRow['images'])) {
                $imagesList[] = formatReviewImageUrl($rRow['images']);
            }
        }

        $prodImg = !empty($rRow['thumbnail_image']) 
            ? formatReviewImageUrl($rRow['thumbnail_image']) 
            : (isset($purchasedByProduct[$pid]) ? $purchasedByProduct[$pid]['product_image'] : '/default-img.jpg');

        $prodName = !empty($rRow['live_product_name'])
            ? $rRow['live_product_name']
            : (isset($purchasedByProduct[$pid]) ? $purchasedByProduct[$pid]['product_name'] : 'Product #' . $pid);

        $orderInfo = $purchasedByProduct[$pid] ?? null;

        $reviewedItems[] = [
            'id' => (int)$rRow['id'],
            'product_id' => $pid,
            'product_name' => $prodName,
            'product_slug' => $rRow['product_slug'] ?? ($orderInfo['product_slug'] ?? ''),
            'product_image' => $prodImg,
            'rating' => (int)$rRow['rating'],
            'title' => $rRow['title'] ?: '',
            'comment' => $rRow['comment'] ?: '',
            'status' => $rRow['status'] ?: 'pending',
            'is_verified' => (int)($rRow['is_verified'] ?? 1),
            'helpful_votes' => (int)($rRow['helpful_votes'] ?? 0),
            'unhelpful_votes' => (int)($rRow['unhelpful_votes'] ?? 0),
            'occasion' => $rRow['occasion'] ?: 'Personal',
            'city' => $rRow['city'] ?: '',
            'images' => $imagesList,
            'admin_reply' => $rRow['admin_reply'] ?: null,
            'created_at' => $rRow['created_at'],
            'updated_at' => $rRow['updated_at'],
            'order_number' => $orderInfo['order_number'] ?? null,
            'order_date' => $orderInfo['order_date'] ?? null
        ];
    }

    // C. Determine To-Be-Reviewed (Purchased items not yet reviewed)
    $toReviewItems = [];
    foreach ($purchasedByProduct as $pid => $item) {
        if (!isset($reviewedProductIds[$pid])) {
            $toReviewItems[] = $item;
        }
    }

    // User's current coin balance
    $userCoins = $coinService->getUserCoins($userId);

    echo json_encode([
        'status' => 'success',
        'success' => true,
        'data' => [
            'to_review' => array_values($toReviewItems),
            'reviewed' => array_values($reviewedItems),
            'stats' => [
                'to_review_count' => count($toReviewItems),
                'reviewed_count' => count($reviewedItems),
                'total_purchased_products' => count($purchasedByProduct),
                'review_bonus_coins' => $reviewBonusCoins,
                'user_coin_balance' => (int)($userCoins['balance'] ?? 0)
            ]
        ]
    ]);
    exit;
}

// -------------------------------------------------------------
// ACTION: SUBMIT_REVIEW (Submit rating & review from user dashboard)
// -------------------------------------------------------------
if ($method === 'POST' && ($action === 'submit_review' || $action === 'submit')) {
    $input = !empty($jsonInput) ? $jsonInput : $_POST;

    $productId = (int)($input['product_id'] ?? 0);
    $rating = max(1, min(5, (int)($input['rating'] ?? 5)));
    $title = trim($input['title'] ?? '');
    $comment = trim($input['comment'] ?? ($input['review'] ?? ''));
    $occasion = trim($input['occasion'] ?? 'General / Personal');
    $city = trim($input['city'] ?? 'Delhi');
    $customerName = trim($input['customer_name'] ?? '') ?: $userName;
    $customerEmail = $userEmail;

    if ($productId <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Please select a valid product to review.']);
        exit;
    }

    if (empty($comment)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Please provide detailed feedback for the product.']);
        exit;
    }

    if (empty($title)) {
        $labels = [1 => 'Poor Quality', 2 => 'Below Average', 3 => 'Decent Product', 4 => 'Very Good Product', 5 => 'Outstanding Quality'];
        $title = $labels[$rating] ?? 'Good Product';
    }

    // Lookup product details
    $pCheck = $db->prepare("SELECT id, name FROM products WHERE id = ? LIMIT 1");
    $pCheck->bind_param("i", $productId);
    $pCheck->execute();
    $pRow = $pCheck->get_result()->fetch_assoc();
    $productName = $pRow['name'] ?? ("Product #" . $productId);

    // Handle Uploaded Images
    $uploaded_images = [];
    $upload_dir = __DIR__ . '/../uploads/reviews/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // 1. Check $_FILES
    if (!empty($_FILES['images']['name'][0])) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $countFiles = count($_FILES['images']['name']);
        for ($i = 0; $i < $countFiles; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_exts, true)) {
                    $fn = 'review_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_dir . $fn)) {
                        $uploaded_images[] = 'uploads/reviews/' . $fn;
                    }
                }
            }
        }
    }

    // 2. Check base64 images in input
    if (!empty($input['images']) && is_array($input['images'])) {
        foreach ($input['images'] as $imgData) {
            if (preg_match('/^data:image\/(\w+);base64,/', $imgData, $type)) {
                $data = substr($imgData, strpos($imgData, ',') + 1);
                $ext = strtolower($type[1]);
                if ($ext === 'jpeg') $ext = 'jpg';
                if (!in_array($ext, ['jpg', 'png', 'webp', 'gif'], true)) {
                    continue;
                }
                $decodedData = base64_decode($data);
                if ($decodedData !== false && @getimagesizefromstring($decodedData) !== false) {
                    $fn = 'review_' . time() . '_' . uniqid() . '.' . $ext;
                    if (file_put_contents($upload_dir . $fn, $decodedData)) {
                        $uploaded_images[] = 'uploads/reviews/' . $fn;
                    }
                }
            } elseif (is_string($imgData) && !empty($imgData)) {
                // If it's already a relative path or URL, preserve it
                $clean = str_replace('http://localhost/printmont/printmont-backend/', '', $imgData);
                $uploaded_images[] = $clean;
            }
        }
    }

    $imagesJson = !empty($uploaded_images) ? json_encode(array_values($uploaded_images)) : null;

    // Check if user already reviewed this product
    $checkReview = $db->prepare("SELECT id, images FROM product_reviews WHERE product_id = ? AND (user_id = ? OR customer_email = ?) LIMIT 1");
    $checkReview->bind_param("iis", $productId, $userId, $userEmail);
    $checkReview->execute();
    $existing = $checkReview->get_result()->fetch_assoc();

    $isUpdate = false;
    $reviewId = 0;

    if ($existing) {
        $isUpdate = true;
        $reviewId = (int)$existing['id'];
        
        // If no new images uploaded, retain existing
        if (empty($imagesJson) && !empty($existing['images'])) {
            $imagesJson = $existing['images'];
        }

        $upd = $db->prepare("
            UPDATE product_reviews 
            SET rating = ?, title = ?, comment = ?, occasion = ?, city = ?, images = ?, status = 'pending', updated_at = NOW()
            WHERE id = ?
        ");
        $upd->bind_param("isssssi", $rating, $title, $comment, $occasion, $city, $imagesJson, $reviewId);
        $upd->execute();
    } else {
        $ins = $db->prepare("
            INSERT INTO product_reviews 
            (product_id, user_id, customer_name, customer_email, rating, title, comment, status, is_verified, occasion, city, images)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 1, ?, ?, ?)
        ");
        $ins->bind_param("iississsss", $productId, $userId, $customerName, $customerEmail, $rating, $title, $comment, $occasion, $city, $imagesJson);
        $ins->execute();
        $reviewId = (int)$ins->insert_id;
    }

    // Award SuperCoins for writing a verified review!
    $coinResult = $coinService->awardReviewCoins($userId, $productId, $productName);
    $coinsAwarded = $coinResult['awarded'] ?? 0;

    $successMsg = $isUpdate 
        ? "Your review for {$productName} has been updated and submitted for moderation."
        : "Thank you! Your verified review for {$productName} has been submitted for moderation.";

    if ($coinsAwarded > 0) {
        $successMsg .= " 🪙 You earned +{$coinsAwarded} SuperCoins!";
    }

    echo json_encode([
        'status' => 'success',
        'success' => true,
        'message' => $successMsg,
        'is_update' => $isUpdate,
        'review_id' => $reviewId,
        'coins_awarded' => $coinsAwarded,
        'data' => [
            'id' => $reviewId,
            'product_id' => $productId,
            'product_name' => $productName,
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'status' => 'pending'
        ]
    ]);
    exit;
}

// -------------------------------------------------------------
// ACTION: DELETE_REVIEW (Remove a user's own review)
// -------------------------------------------------------------
if ($method === 'POST' && $action === 'delete_review') {
    $input = !empty($jsonInput) ? $jsonInput : $_POST;
    $reviewId = (int)($input['review_id'] ?? ($input['id'] ?? 0));

    if ($reviewId <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Invalid review ID.']);
        exit;
    }

    $del = $db->prepare("DELETE FROM product_reviews WHERE id = ? AND (user_id = ? OR customer_email = ?)");
    $del->bind_param("iis", $reviewId, $userId, $userEmail);
    $del->execute();

    if ($del->affected_rows > 0) {
        echo json_encode([
            'status' => 'success',
            'success' => true,
            'message' => 'Your review has been successfully removed.'
        ]);
    } else {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'success' => false,
            'message' => 'Could not delete review or you are not authorized to delete it.'
        ]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Unknown action or unsupported method.']);
