<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Review.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$reviewModel = new Review($db);

// Helper function to calculate relative time ago
function timeAgo($datetime) {
    $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
    if (!$timestamp) return 'Recently';
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $mins = round($difference / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = round($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = round($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 2592000) {
        $weeks = round($difference / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 31536000) {
        $months = round($difference / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = round($difference / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

// Helper to get initials
function getInitials($name) {
    $name = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $name));
    if (empty($name)) return 'PM';
    $parts = preg_split('/\s+/', $name);
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    }
    return strtoupper(substr($parts[0], 0, 2));
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: Fetch Approved Reviews
if ($method === 'GET') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $categorySlug = isset($_GET['category_slug']) ? trim($_GET['category_slug']) : null;
    $ratingFilter = isset($_GET['rating']) && $_GET['rating'] !== '' ? (int)$_GET['rating'] : null;
    $occasionFilter = isset($_GET['occasion']) ? trim($_GET['occasion']) : null;
    $cityFilter = isset($_GET['city']) ? trim($_GET['city']) : null;
    $searchFilter = isset($_GET['search']) ? trim($_GET['search']) : null;
    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
    
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 12;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $categoryName = '';
    // If category_slug is provided, look up category_id
    if (!empty($categorySlug) && empty($categoryId)) {
        $cat_stmt = $db->prepare("SELECT id, name FROM categories WHERE slug = ? LIMIT 1");
        $cat_stmt->bind_param("s", $categorySlug);
        $cat_stmt->execute();
        $cat_res = $cat_stmt->get_result();
        if ($cat_row = $cat_res->fetch_assoc()) {
            $categoryId = (int)$cat_row['id'];
            $categoryName = $cat_row['name'];
        }
    } elseif (!empty($categoryId)) {
        $cat_stmt = $db->prepare("SELECT name FROM categories WHERE id = ? LIMIT 1");
        $cat_stmt->bind_param("i", $categoryId);
        $cat_stmt->execute();
        $cat_res = $cat_stmt->get_result();
        if ($cat_row = $cat_res->fetch_assoc()) {
            $categoryName = $cat_row['name'];
        }
    }

    $where_conditions = ["r.status = 'approved'"];
    $params = [];
    $types = "";

    if (!empty($productId)) {
        $where_conditions[] = "r.product_id = ?";
        $params[] = $productId;
        $types .= "i";
    }

    if (!empty($categoryId)) {
        // Match product's category_id, sub_category_id, or sub_sub_category_id
        $where_conditions[] = "(p.category_id = ? OR p.sub_category_id = ? OR p.sub_sub_category_id = ?)";
        $params[] = $categoryId;
        $params[] = $categoryId;
        $params[] = $categoryId;
        $types .= "iii";
    }

    if (!empty($ratingFilter)) {
        $where_conditions[] = "r.rating = ?";
        $params[] = $ratingFilter;
        $types .= "i";
    }

    if (!empty($occasionFilter) && $occasionFilter !== 'All') {
        $where_conditions[] = "r.occasion = ?";
        $params[] = $occasionFilter;
        $types .= "s";
    }

    if (!empty($cityFilter) && $cityFilter !== 'All') {
        $where_conditions[] = "r.city = ?";
        $params[] = $cityFilter;
        $types .= "s";
    }

    if (!empty($searchFilter)) {
        $where_conditions[] = "(r.customer_name LIKE ? OR r.comment LIKE ? OR r.title LIKE ? OR p.name LIKE ?)";
        $like = "%{$searchFilter}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= "ssss";
    }

    $idsParam = isset($_GET['ids']) ? trim($_GET['ids']) : null;
    $hasSpecificIds = false;
    if (!empty($idsParam)) {
        $idsList = array_filter(array_map('intval', explode(',', $idsParam)));
        if (!empty($idsList)) {
            $hasSpecificIds = true;
            $placeholders = implode(',', array_fill(0, count($idsList), '?'));
            $where_conditions[] = "r.id IN ($placeholders)";
            foreach ($idsList as $idVal) {
                $params[] = $idVal;
                $types .= "i";
            }
        }
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Sort order
    if ($hasSpecificIds && !empty($idsList)) {
        $orderBy = "FIELD(r.id, " . implode(',', $idsList) . ")";
    } else {
        $orderBy = "r.created_at DESC";
        if ($sort === 'oldest') {
            $orderBy = "r.created_at ASC";
        } elseif ($sort === 'rating_high') {
            $orderBy = "r.rating DESC, r.created_at DESC";
        } elseif ($sort === 'rating_low') {
            $orderBy = "r.rating ASC, r.created_at DESC";
        }
    }

    // 1. Get Total Count
    $count_sql = "SELECT COUNT(*) as total_count FROM product_reviews r LEFT JOIN products p ON r.product_id = p.id WHERE {$where_clause}";
    $count_stmt = $db->prepare($count_sql);
    if ($count_stmt && !empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $total_count = 0;
    if ($count_stmt) {
        $count_stmt->execute();
        $c_res = $count_stmt->get_result();
        if ($c_row = $c_res->fetch_assoc()) {
            $total_count = (int)$c_row['total_count'];
        }
    }

    // 2. Rating breakdown stats across the category/product
    $stat_where = ["r.status = 'approved'"];
    $stat_params = [];
    $stat_types = "";
    if (!empty($productId)) {
        $stat_where[] = "r.product_id = ?";
        $stat_params[] = $productId;
        $stat_types .= "i";
    }
    if (!empty($categoryId)) {
        $stat_where[] = "(p.category_id = ? OR p.sub_category_id = ? OR p.sub_sub_category_id = ?)";
        $stat_params[] = $categoryId;
        $stat_params[] = $categoryId;
        $stat_params[] = $categoryId;
        $stat_types .= "iii";
    }
    $stat_where_clause = implode(" AND ", $stat_where);
    $stat_sql = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(r.rating) as avg_rating,
                    COUNT(CASE WHEN r.rating = 5 THEN 1 END) as star_5,
                    COUNT(CASE WHEN r.rating = 4 THEN 1 END) as star_4,
                    COUNT(CASE WHEN r.rating = 3 THEN 1 END) as star_3,
                    COUNT(CASE WHEN r.rating = 2 THEN 1 END) as star_2,
                    COUNT(CASE WHEN r.rating = 1 THEN 1 END) as star_1
                 FROM product_reviews r
                 LEFT JOIN products p ON r.product_id = p.id
                 WHERE {$stat_where_clause}";
    $stat_stmt = $db->prepare($stat_sql);
    if ($stat_stmt && !empty($stat_params)) {
        $stat_stmt->bind_param($stat_types, ...$stat_params);
    }
    $overall_stats = [
        'total' => $total_count,
        'average' => 5.0,
        'stars' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]
    ];
    if ($stat_stmt) {
        $stat_stmt->execute();
        $s_res = $stat_stmt->get_result();
        if ($s_row = $s_res->fetch_assoc()) {
            $overall_stats = [
                'total' => (int)($s_row['total_reviews'] ?? 0),
                'average' => round((float)($s_row['avg_rating'] ?? 5.0), 1),
                'stars' => [
                    5 => (int)($s_row['star_5'] ?? 0),
                    4 => (int)($s_row['star_4'] ?? 0),
                    3 => (int)($s_row['star_3'] ?? 0),
                    2 => (int)($s_row['star_2'] ?? 0),
                    1 => (int)($s_row['star_1'] ?? 0)
                ]
            ];
        }
    }

    // 3. Fetch Paginated Records
    $sql = "SELECT r.*, 
                   p.name as product_name,
                   p.thumbnail_image as product_image
            FROM product_reviews r
            LEFT JOIN products p ON r.product_id = p.id
            WHERE {$where_clause}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?";

    $fetch_params = $params;
    $fetch_types = $types . "ii";
    $fetch_params[] = $limit;
    $fetch_params[] = $offset;

    $stmt = $db->prepare($sql);
    if ($stmt && !empty($fetch_params)) {
        $stmt->bind_param($fetch_types, ...$fetch_params);
    }

    $reviews = [];
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $row['initials'] = getInitials($row['customer_name']);
            $row['time_ago'] = timeAgo($row['created_at']);
            $row['rating'] = (float)($row['rating'] ?? 5);
            $row['occasion'] = $row['occasion'] ?: 'Birthday';
            $row['city'] = $row['city'] ?: 'Delhi';

            // Format images array
            $rawImages = $row['images'];
            $imagesList = [];
            if (!empty($rawImages)) {
                $decoded = json_decode($rawImages, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $img) {
                        if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
                            $imagesList[] = $img;
                        } else {
                            $imagesList[] = 'http://localhost/printmont/printmont-backend/' . ltrim($img, '/');
                        }
                    }
                } elseif (is_string($rawImages) && (strpos($rawImages, 'http') === 0 || strpos($rawImages, 'uploads') === 0)) {
                    $imagesList[] = (strpos($rawImages, 'http') === 0) ? $rawImages : 'http://localhost/printmont/printmont-backend/' . ltrim($rawImages, '/');
                }
            }
            $row['images'] = $imagesList;

            $row['likes'] = (int)($row['helpful_votes'] ?? 0);
            $row['dislikes'] = (int)($row['unhelpful_votes'] ?? 0);

            $reviews[] = $row;
        }
    }

    // Fallback: If 0 reviews for this specific product, get general approved reviews
    if ($total_count === 0 && !empty($productId)) {
        $fallbackSql = "SELECT r.*, p.name as product_name, p.thumbnail_image as product_image 
                        FROM product_reviews r 
                        LEFT JOIN products p ON r.product_id = p.id 
                        WHERE r.status = 'approved' 
                        ORDER BY r.rating DESC, r.created_at DESC LIMIT ?";
        $fstmt = $db->prepare($fallbackSql);
        if ($fstmt) {
            $fstmt->bind_param("i", $limit);
            $fstmt->execute();
            $fres = $fstmt->get_result();
            while ($row = $fres->fetch_assoc()) {
                $row['initials'] = getInitials($row['customer_name']);
                $row['time_ago'] = timeAgo($row['created_at']);
                $row['rating'] = (float)($row['rating'] ?? 5);
                $row['occasion'] = $row['occasion'] ?: 'Birthday';
                $row['city'] = $row['city'] ?: 'Delhi';
                $row['likes'] = (int)($row['helpful_votes'] ?? 0);
                $row['dislikes'] = (int)($row['unhelpful_votes'] ?? 0);

                $rawImages = $row['images'];
                $imagesList = [];
                if (!empty($rawImages)) {
                    $decoded = json_decode($rawImages, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $img) {
                            if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
                                $imagesList[] = $img;
                            } else {
                                $imagesList[] = 'http://localhost/printmont/printmont-backend/' . ltrim($img, '/');
                            }
                        }
                    } elseif (is_string($rawImages) && (strpos($rawImages, 'http') === 0 || strpos($rawImages, 'uploads') === 0)) {
                        $imagesList[] = (strpos($rawImages, 'http') === 0) ? $rawImages : 'http://localhost/printmont/printmont-backend/' . ltrim($rawImages, '/');
                    }
                }
                $row['images'] = $imagesList;
                $reviews[] = $row;
            }
            $total_count = count($reviews);
            $overall_stats = [
                'total' => $total_count,
                'average' => 4.8,
                'stars' => [5 => max(1, $total_count - 1), 4 => 1, 3 => 0, 2 => 0, 1 => 0]
            ];
        }
    }

    $totalPages = max(1, ceil($total_count / $limit));

    echo json_encode([
        'status' => 'success',
        'success' => true,
        'category_name' => $categoryName,
        'total_count' => $total_count,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'limit' => $limit,
        'data' => $reviews,
        'reviews' => $reviews,
        'stats' => $overall_stats
    ]);
    exit;
}

// Handle POST: Submit Review or Vote Reaction
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $action = $input['action'] ?? 'submit';

    // Handle Helpful / Unhelpful reaction
    if ($action === 'reaction' || $action === 'vote') {
        $reviewId = (int)($input['review_id'] ?? $input['id'] ?? 0);
        $type = $input['type'] ?? 'like';

        if ($reviewId <= 0) {
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Invalid review ID']);
            exit;
        }

        if ($type === 'like') {
            $db->query("UPDATE product_reviews SET helpful_votes = helpful_votes + 1, total_votes = total_votes + 1 WHERE id = $reviewId");
        } else {
            $db->query("UPDATE product_reviews SET unhelpful_votes = unhelpful_votes + 1, total_votes = total_votes + 1 WHERE id = $reviewId");
        }

        $rowRes = $db->query("SELECT id, helpful_votes as likes, unhelpful_votes as dislikes FROM product_reviews WHERE id = $reviewId");
        $row = $rowRes ? $rowRes->fetch_assoc() : null;
        echo json_encode(['status' => 'success', 'success' => true, 'message' => 'Feedback recorded', 'data' => $row]);
        exit;
    }

    // Require Login: the Bearer token must be a valid access token of an active user.
    // The reviewer is always taken from the token, never from user_id in the request body.
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['Authorization'] ?? '');
    if (empty($authHeader) && function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $authHeader = $requestHeaders['Authorization'] ?? ($requestHeaders['authorization'] ?? '');
    }
    $token = null;
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }

    $authUser = null;
    if (!empty($token)) {
        require_once __DIR__ . '/../controllers/AuthControllerAPI.php';
        $authResult = (new AuthController($db))->verifyToken($token);
        if (!empty($authResult['success'])) {
            $authUser = $authResult['user'];
        }
    }

    if (!$authUser) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'success' => false,
            'require_login' => true,
            'message' => 'Please login to your account to submit a review.'
        ]);
        exit;
    }

    $userId = (int)$authUser['id'];
    $accountName = trim(($authUser['first_name'] ?? '') . ' ' . ($authUser['last_name'] ?? ''));
    $customerName = trim($input['customer_name'] ?? ($input['name'] ?? '')) ?: ($accountName ?: ($authUser['username'] ?? ''));
    $customerEmail = $authUser['email'] ?? '';

    $productId = (int)($input['product_id'] ?? 0);
    $rating = (int)($input['rating'] ?? 5);
    $title = trim($input['title'] ?? '');
    $comment = trim($input['comment'] ?? ($input['review'] ?? ''));
    $occasion = trim($input['occasion'] ?? 'Birthday');
    $city = trim($input['city'] ?? 'Delhi');

    if ($productId <= 0) {
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Please choose a product to review']);
        exit;
    }

    if (empty($customerName)) {
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Please provide your name']);
        exit;
    }

    if (empty($comment)) {
        echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Please provide a review comment']);
        exit;
    }

    // Handle Uploaded Images (Multipart files or Base64 array)
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
                if (in_array($ext, $allowed_exts)) {
                    $fn = 'review_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_dir . $fn)) {
                        $uploaded_images[] = 'uploads/reviews/' . $fn;
                    }
                }
            }
        }
    }

    // 2. Check base64 images in JSON input
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
                $uploaded_images[] = $imgData;
            }
        }
    }

    // New review from frontend is always 'pending'
    $reviewData = [
        'product_id' => $productId,
        'user_id' => $userId,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'rating' => max(1, min(5, $rating)),
        'title' => $title ?: substr($comment, 0, 50),
        'comment' => $comment,
        'occasion' => $occasion,
        'city' => $city,
        'images' => $uploaded_images,
        'status' => 'pending', // Pending by default until admin approves!
        'is_verified' => 1
    ];

    $success = $reviewModel->create($reviewData);

    if ($success) {
        echo json_encode([
            'status' => 'success',
            'success' => true,
            'message' => 'Thank you for your feedback! Your review has been submitted and will be published once approved by our team.',
            'pending' => true,
            'image_count' => count($uploaded_images)
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'success' => false,
            'message' => 'Failed to save review. Please try again later.'
        ]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unsupported method']);
