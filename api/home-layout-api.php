<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once(__DIR__ . '/../controllers/HomeLayoutController.php');
$controller = new HomeLayoutController();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'search_products') {
        $q = trim($_GET['q'] ?? '');
        $db = new Database();
        if (empty($q)) {
            $products = $db->fetchAll(
                "SELECT id, name, sku, regular_price, offer_price, price, thumbnail_image 
                 FROM products WHERE status = 'active' ORDER BY id DESC LIMIT 15"
            );
        } else {
            $like = "%{$q}%";
            $isNum = is_numeric($q) ? (int)$q : 0;
            $products = $db->fetchAll(
                "SELECT id, name, sku, regular_price, offer_price, price, thumbnail_image 
                 FROM products 
                 WHERE status = 'active' AND (name LIKE ? OR sku LIKE ? OR id = ?) 
                 ORDER BY (name LIKE ?) DESC, id DESC LIMIT 20",
                [$like, $like, $isNum, "{$q}%"]
            );
        }
        echo json_encode(['success' => true, 'data' => $products]);
        exit;
    }

    if ($action === 'get_products_by_ids') {
        $idsStr = trim($_GET['ids'] ?? '');
        $ids = array_filter(array_map('intval', explode(',', $idsStr)));
        if (empty($ids)) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        $db = new Database();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $products = $db->fetchAll(
            "SELECT id, name, sku, regular_price, offer_price, price, thumbnail_image 
             FROM products WHERE id IN ($placeholders) 
             ORDER BY FIELD(id, " . implode(',', $ids) . ")",
            $ids
        );
        echo json_encode(['success' => true, 'data' => $products]);
        exit;
    }

    if ($action === 'get_home_services') {
        $db = new Database();
        $services = $db->fetchAll(
            "SELECT id, title, description, icon, image, button_text, button_url, background_color, text_color, order_number 
             FROM home_services 
             WHERE status = 'active' 
             ORDER BY order_number ASC, id ASC"
        );
        echo json_encode(['success' => true, 'data' => $services]);
        exit;
    }

    if ($action === 'get_carousels') {
        $db = new Database();
        $carousels = $db->fetchAll(
            "SELECT c.*, cat.name as category_name, cat.slug as category_slug 
             FROM carousels c 
             LEFT JOIN categories cat ON c.desktop_home_category_id = cat.id 
             WHERE c.status = 'active' 
             ORDER BY c.desktop_sort_order ASC, c.id DESC"
        );
        echo json_encode(['success' => true, 'data' => $carousels]);
        exit;
    }

    if ($action === 'get_home_products') {
        $carouselId = (int)($_GET['carousel_id'] ?? 0);
        $db = new Database();
        $where = "hp.status = 'active' AND p.status = 'active'";
        $params = [];
        if ($carouselId > 0) {
            $where .= " AND (hp.home_carousel_id = ? OR hp.desktop_home_carousel_id = ?)";
            $params = [$carouselId, $carouselId];
        }
        $products = $db->fetchAll(
            "SELECT hp.*, p.name, p.product_slug as slug, p.price, p.offer_price, p.regular_price, p.thumbnail_image, p.sku 
             FROM home_products hp 
             JOIN products p ON hp.product_id = p.id 
             WHERE $where 
             ORDER BY hp.sort_order ASC, hp.id DESC",
            $params
        );
        echo json_encode(['success' => true, 'data' => $products]);
        exit;
    }

    if ($action === 'get_live_orders') {
        $db = new Database();
        $orders = $db->fetchAll(
            "SELECT o.id, o.customer_name, o.created_at, p.id as product_id, p.name as product_name, p.product_slug as slug, p.thumbnail_image, p.price, p.offer_price
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             JOIN products p ON oi.product_id = p.id
             WHERE p.status = 'active'
             ORDER BY o.id DESC LIMIT 15"
        );
        $cities = ['New Delhi', 'Mumbai', 'Bengaluru', 'Pune', 'Hyderabad', 'Chennai', 'Kolkata', 'Ahmedabad', 'Jaipur', 'Chandigarh', 'Gurugram', 'Noida'];
        $firstNames = ['Rahul', 'Pooja', 'Aman', 'Sneha', 'Vikram', 'Ananya', 'Rohan', 'Kavita', 'Aditya', 'Ritu', 'Manish', 'Neha'];
        $timeDeltas = [2, 4, 7, 11, 15, 22, 31, 45, 58, 75];

        $results = [];
        if (empty($orders)) {
            $prods = $db->fetchAll("SELECT id as product_id, name as product_name, product_slug as slug, thumbnail_image, price, offer_price FROM products WHERE status = 'active' ORDER BY id DESC LIMIT 12");
            foreach ($prods as $idx => $p) {
                $city = $cities[$idx % count($cities)];
                $name = $firstNames[$idx % count($firstNames)];
                $mins = $timeDeltas[$idx % count($timeDeltas)];
                $results[] = [
                    'id' => $idx + 1,
                    'customer_name' => $name,
                    'city' => $city,
                    'product_id' => $p['product_id'],
                    'product_name' => $p['product_name'],
                    'product_slug' => $p['slug'],
                    'thumbnail_image' => $p['thumbnail_image'],
                    'price' => $p['offer_price'] ?: $p['price'],
                    'time_ago' => $mins . ' minutes ago'
                ];
            }
        } else {
            foreach ($orders as $idx => $o) {
                $city = $cities[$idx % count($cities)];
                $rawName = trim($o['customer_name'] ?? '');
                $nameParts = explode(' ', $rawName);
                $name = !empty($nameParts[0]) ? $nameParts[0] : $firstNames[$idx % count($firstNames)];
                $mins = $timeDeltas[$idx % count($timeDeltas)];
                $results[] = [
                    'id' => $o['id'],
                    'customer_name' => $name,
                    'city' => $city,
                    'product_id' => $o['product_id'],
                    'product_name' => $o['product_name'],
                    'product_slug' => $o['slug'],
                    'thumbnail_image' => $o['thumbnail_image'],
                    'price' => $o['offer_price'] ?: $o['price'],
                    'time_ago' => $mins . ' minutes ago'
                ];
            }
        }
        echo json_encode(['success' => true, 'data' => $results]);
        exit;
    }

    if ($action === 'search_reviews') {
        $q = trim($_GET['q'] ?? '');
        $db = new Database();
        if (empty($q)) {
            $reviews = $db->fetchAll(
                "SELECT r.id, r.customer_name, r.rating, r.title, r.comment, r.occasion, r.city, r.created_at,
                        p.name as product_name, p.thumbnail_image as product_image
                 FROM product_reviews r
                 LEFT JOIN products p ON r.product_id = p.id
                 WHERE r.status = 'approved'
                 ORDER BY r.rating DESC, r.created_at DESC LIMIT 20"
            );
        } else {
            $like = "%{$q}%";
            $isNum = is_numeric($q) ? (int)$q : 0;
            $reviews = $db->fetchAll(
                "SELECT r.id, r.customer_name, r.rating, r.title, r.comment, r.occasion, r.city, r.created_at,
                        p.name as product_name, p.thumbnail_image as product_image
                 FROM product_reviews r
                 LEFT JOIN products p ON r.product_id = p.id
                 WHERE r.status = 'approved' AND (r.customer_name LIKE ? OR r.comment LIKE ? OR r.title LIKE ? OR r.occasion LIKE ? OR p.name LIKE ? OR r.id = ?)
                 ORDER BY (r.customer_name LIKE ?) DESC, r.rating DESC, r.id DESC LIMIT 25",
                [$like, $like, $like, $like, $like, $isNum, "{$q}%"]
            );
        }
        echo json_encode(['success' => true, 'data' => $reviews]);
        exit;
    }

    if ($action === 'get_reviews_by_ids') {
        $idsStr = trim($_GET['ids'] ?? '');
        $ids = array_filter(array_map('intval', explode(',', $idsStr)));
        if (empty($ids)) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        $db = new Database();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $reviews = $db->fetchAll(
            "SELECT r.id, r.customer_name, r.rating, r.title, r.comment, r.occasion, r.city, r.created_at,
                    p.name as product_name, p.thumbnail_image as product_image
             FROM product_reviews r
             LEFT JOIN products p ON r.product_id = p.id
             WHERE r.id IN ($placeholders)
             ORDER BY FIELD(r.id, " . implode(',', $ids) . ")",
            $ids
        );
        echo json_encode(['success' => true, 'data' => $reviews]);
        exit;
    }

    if ($action === 'get_subcategories') {
        $parentId = (int)($_GET['parent_id'] ?? 0);
        $idsStr = trim($_GET['ids'] ?? '');
        $db = new Database();

        if (!empty($idsStr)) {
            $ids = array_filter(array_map('intval', explode(',', $idsStr)));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $subs = $db->fetchAll(
                    "SELECT id, name, slug, COALESCE(NULLIF(desktop_image, ''), NULLIF(mobile_image, ''), '') as image_url, parent_id, level 
                     FROM categories 
                     WHERE id IN ($placeholders) AND status = 'active' 
                     ORDER BY FIELD(id, " . implode(',', $ids) . ")",
                    $ids
                );
                echo json_encode(['success' => true, 'data' => $subs]);
                exit;
            }
        }

        if ($parentId > 0) {
            $subs = $db->fetchAll(
                "SELECT id, name, slug, COALESCE(NULLIF(desktop_image, ''), NULLIF(mobile_image, ''), '') as image_url, parent_id, level 
                 FROM categories 
                 WHERE parent_id = ? AND status = 'active' 
                 ORDER BY name ASC",
                [$parentId]
            );
            if (!empty($subs)) {
                $childIds = array_column($subs, 'id');
                $placeholders = implode(',', array_fill(0, count($childIds), '?'));
                $subSubs = $db->fetchAll(
                    "SELECT id, name, slug, COALESCE(NULLIF(desktop_image, ''), NULLIF(mobile_image, ''), '') as image_url, parent_id, level 
                     FROM categories 
                     WHERE parent_id IN ($placeholders) AND status = 'active' 
                     ORDER BY name ASC",
                    $childIds
                );
                if (!empty($subSubs)) {
                    $subs = array_merge($subs, $subSubs);
                }
            }
        } else {
            $subs = $db->fetchAll(
                "SELECT id, name, slug, COALESCE(NULLIF(desktop_image, ''), NULLIF(mobile_image, ''), '') as image_url, parent_id, level 
                 FROM categories 
                 WHERE level >= 2 AND status = 'active' 
                 ORDER BY name ASC"
            );
        }
        echo json_encode(['success' => true, 'data' => $subs]);
        exit;
    }

    $target = $_GET['target'] ?? 'desktop';
    if (!in_array($target, ['desktop', 'mobile'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid target.']);
        exit;
    }

    // For public frontend API, only fetch active sections
    $db = new Database();
    $layoutModel = new HomeLayoutModel();
    $sections = $layoutModel->getActiveSections($target);

    // Helper functions for reviews formatting
    $timeAgoHelper = function($datetime) {
        $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
        if (!$timestamp) return 'Recently';
        $diff = time() - $timestamp;
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return round($diff / 60) . ' mins ago';
        if ($diff < 86400) return round($diff / 3600) . ' hours ago';
        if ($diff < 604800) return round($diff / 86400) . ' days ago';
        if ($diff < 2592000) return round($diff / 604800) . ' weeks ago';
        return round($diff / 2592000) . ' months ago';
    };

    $initialsHelper = function($name) {
        $clean = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $name ?? ''));
        if (empty($clean)) return 'PM';
        $parts = preg_split('/\s+/', $clean);
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
        }
        return strtoupper(substr($parts[0], 0, 2));
    };

    // Fetch banners and dynamic content for sections
    $bannerModel = new BannerModel();
    foreach ($sections as &$section) {
        if ($section['section_key'] === 'home_hero' || $section['section_key'] === 'mobile_hero_slider') {
            $heroSliders = $db->fetchAll(
                "SELECT id, title, '' as description,
                        desktop_image as large, mobile_image as small,
                        desktop_image as image_url_desktop, mobile_image as image_url_mobile,
                        desktop_url as target, mobile_url,
                        desktop_sort_order as display_order, auto_play_speed, desktop_design, mobile_design
                 FROM sliders
                 WHERE slider_type = 'main' AND status = 'active'
                   AND (start_date IS NULL OR start_date <= CURDATE())
                   AND (end_date IS NULL OR end_date >= CURDATE())
                 ORDER BY desktop_sort_order ASC, id ASC"
            );
            $section['banners'] = $heroSliders;
        } elseif ($section['section_type'] === 'small_banner_carousel' || $section['section_type'] === 'small_banner_slider' || $section['section_type'] === 'small_height_carousel' || strpos($section['section_key'], 'small_banner') !== false) {
            $secKey = $section['section_key'];
            $allSmallSliders = $db->fetchAll(
                "SELECT id, title, '' as description,
                        desktop_image as large, mobile_image as small,
                        desktop_image as image_url_desktop, mobile_image as image_url_mobile,
                        desktop_url as target, mobile_url,
                        desktop_sort_order as display_order, auto_play_speed, desktop_design, mobile_design,
                        desktop_status, mobile_status, target_section, placement_rules
                 FROM sliders
                 WHERE slider_type = 'small' AND status = 'active'
                   AND (start_date IS NULL OR start_date <= CURDATE())
                   AND (end_date IS NULL OR end_date >= CURDATE())
                 ORDER BY desktop_sort_order ASC, id ASC"
            );

            // Filter sliders specifically assigned to this section key
            $matchedSliders = array_filter($allSmallSliders, function($sl) use ($secKey) {
                $target = $sl['target_section'] ?? '';
                $rules = $sl['placement_rules'] ?? '';
                if ($target === $secKey || stripos($target, $secKey) !== false || stripos($rules, $secKey) !== false) {
                    return true;
                }
                return false;
            });

            // If no custom slider is bound to this copy key, fallback to default small sliders
            if (empty($matchedSliders)) {
                $matchedSliders = array_filter($allSmallSliders, function($sl) {
                    $target = $sl['target_section'] ?? '';
                    return empty($target) || $target === 'small_banner_slider' || $target === 'home_hero';
                });
            }

            $section['banners'] = array_values($matchedSliders);
        } elseif (in_array($section['section_type'], ['slider', 'banner', 'banner_category_slider', 'one_and_half_carousel', 'peeking_slider', 'small_banner_carousel', 'small_banner_slider', 'small_height_carousel'])) {
            $sectionDbRow = $db->fetch(
                "SELECT id FROM banner_sections WHERE section_key = ? LIMIT 1",
                [$section['section_key']]
            );
            if ($sectionDbRow) {
                // Return formatted banner objects
                $banners = $bannerModel->getBannersBySection((int)$sectionDbRow['id']);
                $formattedBanners = [];
                foreach ($banners as $b) {
                    $formattedBanners[] = [
                        'id' => (int)$b['id'],
                        'title' => $b['title'],
                        'description' => $b['description'],
                        'large' => $b['image_url_desktop'],
                        'small' => $b['image_url_mobile'],
                        'target' => $b['target_url'],
                        'display_order' => (int)$b['display_order']
                    ];
                }
                $section['banners'] = $formattedBanners;
            } else {
                $section['banners'] = [];
            }
        } elseif (in_array($section['section_type'], ['customer_reviews', 'reviews', 'customer_review_carousel'])) {
            // Load reviews for customer reviews section
            $reviewLimit = !empty($section['product_limit']) ? (int)$section['product_limit'] : 5;
            $customIds = array_filter(array_map('intval', explode(',', $section['product_ids'] ?? '')));
            
            $loadedReviews = [];
            if (!empty($customIds)) {
                $placeholders = implode(',', array_fill(0, count($customIds), '?'));
                $loadedReviews = $db->fetchAll(
                    "SELECT r.id, r.customer_name, r.rating, r.title, r.comment, r.occasion, r.city, r.created_at,
                            p.name as product_name, p.thumbnail_image as product_image
                     FROM product_reviews r
                     LEFT JOIN products p ON r.product_id = p.id
                     WHERE r.status = 'approved' AND r.id IN ($placeholders)
                     ORDER BY FIELD(r.id, " . implode(',', $customIds) . ")",
                    $customIds
                );
            }
            
            // Fallback to top 4-5 rated reviews if no handpicked reviews or empty result
            if (empty($loadedReviews)) {
                $loadedReviews = $db->fetchAll(
                    "SELECT r.id, r.customer_name, r.rating, r.title, r.comment, r.occasion, r.city, r.created_at,
                            p.name as product_name, p.thumbnail_image as product_image
                     FROM product_reviews r
                     LEFT JOIN products p ON r.product_id = p.id
                     WHERE r.status = 'approved' AND r.rating >= 4
                     ORDER BY r.rating DESC, r.created_at DESC
                     LIMIT " . (int)$reviewLimit
                );
            }

            foreach ($loadedReviews as &$rev) {
                $rev['initials'] = $initialsHelper($rev['customer_name']);
                $rev['time_ago'] = $timeAgoHelper($rev['created_at']);
                $rev['rating'] = (float)($rev['rating'] ?? 5);
                $rev['occasion'] = $rev['occasion'] ?: 'Personalized';
                $rev['city'] = $rev['city'] ?: 'Delhi';
            }

            $section['reviews'] = $loadedReviews;
            $section['banners'] = [];
        } else {
            $section['banners'] = [];
        }
    }

    echo json_encode(['success' => true, 'data' => $sections]);
    exit;
}

// For POST actions, verify admin session
$isAdmin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
if (!$isAdmin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin login required.']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'reorder') {
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    if (empty($ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No IDs provided for reordering.']);
        exit;
    }
    $res = $controller->reorderSections($ids);
    echo json_encode($res);
    exit;
}

if ($action === 'toggle') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    $status = $input['status'] ?? '';
    if (!$id || empty($status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID and status are required.']);
        exit;
    }
    $res = $controller->toggleSection($id, $status);
    echo json_encode($res);
    exit;
}

if ($action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Section ID is required for deletion.']);
        exit;
    }
    $res = $controller->deleteSection($id);
    echo json_encode($res);
    exit;
}

if ($action === 'duplicate') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Section ID is required for duplication.']);
        exit;
    }
    $res = $controller->duplicateSection($id);
    echo json_encode($res);
    exit;
}

if ($action === 'create') {
    // Multi-part form post
    $res = $controller->createSection($_POST, $_FILES);
    echo json_encode($res);
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Section ID is required for update.']);
        exit;
    }
    $res = $controller->updateSection($id, $_POST, $_FILES);
    echo json_encode($res);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid action.']);
exit;
?>
