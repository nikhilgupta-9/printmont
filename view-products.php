<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/AuthController.php');
require_once(__DIR__ . '/controllers/ProductController.php');

// Create database connection
$database = new Database();
$db = $database->getConnection();

$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Pass the database connection to the controller
$productController = new ProductController($db);

// Handle AJAX status/featured/bestseller/assured toggle requests
if (isset($_POST['action']) && in_array($_POST['action'], ['toggle_status', 'toggle_featured', 'toggle_bestseller', 'toggle_assured'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    $productId = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'];

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        exit;
    }

    if ($action === 'toggle_status') {
        $newStatus = (isset($_POST['status']) && $_POST['status'] === 'active') ? 'active' : 'inactive';
        $stmt = $db->prepare("UPDATE products SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $productId);
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok, 'status' => $newStatus, 'error' => $ok ? null : $db->error]);
    } elseif ($action === 'toggle_featured') {
        $val = !empty($_POST['val']) ? 1 : 0;
        $stmt = $db->prepare("UPDATE products SET featured = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $val, $productId);
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok, 'featured' => $val, 'error' => $ok ? null : $db->error]);
    } elseif ($action === 'toggle_bestseller') {
        $val = !empty($_POST['val']) ? 1 : 0;
        $stmt = $db->prepare("UPDATE products SET our_bestseller = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $val, $productId);
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok, 'bestseller' => $val, 'error' => $ok ? null : $db->error]);
    } elseif ($action === 'toggle_assured') {
        $val = !empty($_POST['val']) ? 1 : 0;
        $stmt = $db->prepare("UPDATE products SET is_assured = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $val, $productId);
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok, 'assured' => $val, 'error' => $ok ? null : $db->error]);
    }
    exit;
}

// -------------------------------------------------------------
// FILTER & PAGINATION PARAMETERS
// -------------------------------------------------------------
$search = trim($_GET['search'] ?? '');
$category_id = trim($_GET['category_id'] ?? '');
$status = trim($_GET['status'] ?? '');
$featured = isset($_GET['featured']) && $_GET['featured'] !== '' ? trim($_GET['featured']) : '';
$bestseller = isset($_GET['bestseller']) && $_GET['bestseller'] !== '' ? trim($_GET['bestseller']) : '';
$stock_filter = trim($_GET['stock'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(5, min(100, (int)$_GET['limit'])) : 15;
$offset = ($page - 1) * $limit;

// -------------------------------------------------------------
// KPI STATS OVERVIEW
// -------------------------------------------------------------
$stats_sql = "SELECT 
    COUNT(*) as total_count,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_count,
    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
    COUNT(CASE WHEN stock_quantity <= 5 THEN 1 END) as low_stock_count,
    COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock_count,
    COUNT(CASE WHEN our_bestseller = 1 THEN 1 END) as bestseller_count,
    COUNT(CASE WHEN featured = 1 THEN 1 END) as featured_count
    FROM products";
$stats = $database->fetch($stats_sql) ?: [
    'total_count' => 0, 'active_count' => 0, 'inactive_count' => 0, 'draft_count' => 0,
    'low_stock_count' => 0, 'out_of_stock_count' => 0, 'bestseller_count' => 0, 'featured_count' => 0
];

// Helper function to build query strings
function buildProductQuery($newParams = []) {
    $current = $_GET;
    unset($current['ajax']);
    foreach ($newParams as $k => $v) {
        if ($v === null || $v === '') {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    return 'view-products.php?' . http_build_query($current);
}

// -------------------------------------------------------------
// HTML TABLE ROW & PAGINATION BUILDERS
// -------------------------------------------------------------
function renderProductsTbodyHtml($products) {
    if (empty($products)) {
        return '<tr>
            <td colspan="9" class="text-center py-5">
                <div class="text-muted">
                    <i class="fas fa-box-open fs-1 text-secondary opacity-50 mb-2"></i>
                    <h6>No products found matching your search</h6>
                    <p class="small text-muted mb-3">Try adjusting your keywords or reset filters</p>
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="resetFiltersLive()">Clear Filters</button>
                    <a href="product.php" class="btn btn-sm btn-primary">+ Add New Product</a>
                </div>
            </td>
        </tr>';
    }

    $html = '';
    foreach ($products as $p) {
        $regPrice = (float)($p['regular_price'] ?? $p['price'] ?? 0);
        $offPrice = !empty($p['offer_price']) ? (float)$p['offer_price'] : ((float)($p['discount_price'] ?? 0));
        $activePrice = ($offPrice > 0 && $offPrice < $regPrice) ? $offPrice : $regPrice;
        $discPct = ($regPrice > $activePrice && $regPrice > 0) ? round((($regPrice - $activePrice) / $regPrice) * 100) : 0;
        $stock = (int)($p['stock_quantity'] ?? $p['product_quantity'] ?? 0);

        // Primary image resolution
        $imgSrc = $p['primary_image'] ?? $p['thumbnail_image'] ?? '';
        if (empty($imgSrc) && !empty($p['images']) && is_array($p['images'])) {
            $imgSrc = $p['images'][0]['image_url'] ?? '';
        }
        if (!empty($imgSrc)) {
            if (strpos($imgSrc, 'http://') !== 0 && strpos($imgSrc, 'https://') !== 0) {
                $imgSrc = ltrim($imgSrc, '/');
            }
        } else {
            $imgSrc = 'img/icons/icon-48x48.png';
        }

        // If the main image link is dead, show the thumbnail instead (then the default icon)
        $thumbFallback = (string) ($p['thumbnail_image'] ?? '');
        if ($thumbFallback === '' || $thumbFallback === $imgSrc) {
            $thumbFallback = 'img/icons/icon-48x48.png';
        } elseif (strpos($thumbFallback, 'http://') !== 0 && strpos($thumbFallback, 'https://') !== 0) {
            $thumbFallback = ltrim($thumbFallback, '/');
        }
        $thumbFallbackJs = htmlspecialchars(json_encode($thumbFallback), ENT_QUOTES);

        $pName = htmlspecialchars((string)($p['name'] ?? ''));
        $pId = (int)$p['id'];
        $pBrand = htmlspecialchars((string)($p['brand'] ?? ''));
        $pSlug = htmlspecialchars((string)($p['product_slug'] ?? ''));
        $pSku = htmlspecialchars((string)($p['sku'] ?: ('ID-' . $pId)));
        $pCat = htmlspecialchars((string)($p['category_name'] ?? 'Uncategorized'));
        $pStatus = htmlspecialchars((string)($p['status'] ?? 'active'));
        $pDate = date('M d, Y', strtotime($p['created_at']));
        $imgSafe = htmlspecialchars($imgSrc);
        $pNameJs = htmlspecialchars(addslashes($p['name'] ?? ''));

        // Stock pill
        if ($stock > 10) {
            $stockHtml = '<span class="stock-pill in-stock"><i class="fas fa-check-circle" style="font-size: 9px;"></i> ' . $stock . ' in stock</span>';
        } elseif ($stock > 0) {
            $stockHtml = '<span class="stock-pill low-stock"><i class="fas fa-exclamation-triangle" style="font-size: 9px;"></i> ' . $stock . ' left</span>';
        } else {
            $stockHtml = '<span class="stock-pill out-stock"><i class="fas fa-times-circle" style="font-size: 9px;"></i> Out of stock</span>';
        }

        // Highlight tags
        $featActive = !empty($p['featured']) ? 'is-active-feat' : '';
        $bestActive = !empty($p['our_bestseller']) ? 'is-active-best' : '';
        $assuredActive = (!isset($p['is_assured']) || !empty($p['is_assured'])) ? 'is-active-assured' : '';

        // Price HTML
        $priceHtml = '<div class="badge-price-curr">₹' . number_format($activePrice, 2) . '</div>';
        if ($discPct > 0) {
            $priceHtml .= '<div class="d-flex align-items-center gap-1 mt-0.5"><span class="badge-price-old">₹' . number_format($regPrice, 2) . '</span><span class="badge-discount">-' . $discPct . '%</span></div>';
        }

        $brandHtml = !empty($pBrand) ? '<span class="badge bg-light text-secondary border text-2xs">' . $pBrand . '</span>' : '';
        $slugHtml = !empty($pSlug) ? '<small class="text-muted" style="font-size: 11px;">/' . $pSlug . '</small>' : '';

        $html .= '<tr id="row-prod-' . $pId . '">
            <td class="text-center">
                <div class="product-thumb-box mx-auto" onclick="previewImageModal(\'' . $imgSafe . '\', \'' . $pNameJs . '\')">
                    <img src="' . $imgSafe . '" alt="' . $pName . '" loading="lazy" onerror="if(this.dataset.fb){this.onerror=null;this.src=\'img/icons/icon-48x48.png\';}else{this.dataset.fb=1;this.src=' . $thumbFallbackJs . ';}">
                </div>
            </td>
            <td>
                <a href="edit-product.php?id=' . $pId . '" class="product-name-link mb-1">' . $pName . '</a>
                <div class="d-flex align-items-center gap-2 mt-1">' . $brandHtml . $slugHtml . '</div>
            </td>
            <td>
                <div class="badge-sku mb-1">' . $pSku . '</div>
                <div class="badge-category" title="' . $pCat . '">' . $pCat . '</div>
            </td>
            <td>' . $priceHtml . '</td>
            <td>' . $stockHtml . '</td>
            <td>
                <div class="d-flex flex-wrap gap-1">
                    <button type="button" class="tag-toggle-btn ' . $featActive . '" onclick="toggleProductFlag(' . $pId . ', \'toggle_featured\', this)" title="Click to toggle Featured"><i class="fas fa-bolt me-0.5"></i> Featured</button>
                    <button type="button" class="tag-toggle-btn ' . $bestActive . '" onclick="toggleProductFlag(' . $pId . ', \'toggle_bestseller\', this)" title="Click to toggle Bestseller"><i class="fas fa-star me-0.5"></i> Bestseller</button>
                    <button type="button" class="tag-toggle-btn ' . $assuredActive . '" onclick="toggleProductFlag(' . $pId . ', \'toggle_assured\', this)" title="Click to toggle Printmont Assured Quality Badge"><i class="fas fa-shield-alt me-0.5"></i> Assured</button>
                </div>
            </td>
            <td>
                <span class="status-switch-pill ' . $pStatus . '" onclick="toggleProductStatus(' . $pId . ', this)" title="Click to toggle Status">● ' . ucfirst($pStatus) . '</span>
            </td>
            <td class="text-muted" style="font-size: 11.5px;">' . $pDate . '</td>
            <td class="text-end">
                <div class="d-flex justify-content-end gap-1">
                    <a href="http://localhost:5173/product/' . urlencode($pSlug ?: 'item') . '-p' . $pId . '" target="_blank" class="action-icon-btn btn-view" title="Preview Live Single Product Page"><i class="fas fa-external-link-alt" style="font-size: 11px;"></i></a>
                    <a href="edit-product.php?id=' . $pId . '" class="action-icon-btn btn-edit" title="Edit Product"><i class="fas fa-pen" style="font-size: 11px;"></i></a>
                    <a href="delete-product.php?id=' . $pId . '" class="action-icon-btn btn-del" onclick="return confirm(\'Are you sure you want to permanently delete \\\'' . $pNameJs . '\\\'?\')" title="Delete Product"><i class="fas fa-trash-alt" style="font-size: 11px;"></i></a>
                </div>
            </td>
        </tr>';
    }
    return $html;
}

function renderProductsPaginationHtml($page, $totalPages, $totalProducts) {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="card-footer bg-white border-top py-3 d-flex flex-wrap align-items-center justify-content-between">
        <div class="text-muted small">
            Page <strong>' . $page . '</strong> of <strong>' . $totalPages . '</strong> (' . number_format($totalProducts) . ' total items)
        </div>
        <nav aria-label="Product Pagination">
            <ul class="pagination pagination-sm mb-0">';
            
    // Prev
    $prevDis = ($page <= 1) ? 'disabled' : '';
    $prevPage = max(1, $page - 1);
    $html .= '<li class="page-item ' . $prevDis . '"><a class="page-link" href="' . buildProductQuery(['page' => $prevPage]) . '" onclick="gotoProductPage(' . $prevPage . '); return false;"><i class="fas fa-chevron-left" style="font-size: 10px;"></i></a></li>';

    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)) {
            $active = ($i == $page) ? 'active' : '';
            $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . buildProductQuery(['page' => $i]) . '" onclick="gotoProductPage(' . $i . '); return false;">' . $i . '</a></li>';
        } elseif ($i == $page - 3 || $i == $page + 3) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // Next
    $nextDis = ($page >= $totalPages) ? 'disabled' : '';
    $nextPage = min($totalPages, $page + 1);
    $html .= '<li class="page-item ' . $nextDis . '"><a class="page-link" href="' . buildProductQuery(['page' => $nextPage]) . '" onclick="gotoProductPage(' . $nextPage . '); return false;"><i class="fas fa-chevron-right" style="font-size: 10px;"></i></a></li>';

    $html .= '</ul></nav></div>';
    return $html;
}

// -------------------------------------------------------------
// GET FILTERED PRODUCTS & HANDLE AJAX LIVE SEARCH
// -------------------------------------------------------------
$filterParams = [
    'search' => $search,
    'category_id' => $category_id,
    'status' => $status,
    'featured' => $featured,
    'bestseller' => $bestseller,
    'page' => $page,
    'limit' => $limit
];

$products = $productController->getProductsWithFilters($filterParams);
$totalProducts = $productController->getTotalProductsCount($search, $category_id, $status, $featured, $bestseller);
$totalPages = ceil($totalProducts / $limit);

// AJAX LIVE SEARCH ENDPOINT
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    $showingText = "Showing " . ($totalProducts > 0 ? ($offset + 1) : 0) . "–" . min($offset + $limit, $totalProducts) . " of " . $totalProducts . " products";
    if ($search || $category_id || $status || $bestseller !== '' || $featured !== '') {
        $showingText .= ' <span class="badge bg-primary-subtle text-primary ms-1">Filtered</span>';
    }

    echo json_encode([
        'success' => true,
        'total' => $totalProducts,
        'title_text' => 'All Products (' . number_format($totalProducts) . ')',
        'showing_text' => $showingText,
        'tbody_html' => renderProductsTbodyHtml($products),
        'pagination_html' => renderProductsPaginationHtml($page, $totalPages, $totalProducts)
    ]);
    exit;
}

// Get categories for filter dropdown
$categories = $productController->getAllCategoriesForFilter();

// Check for session messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Products Management | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .product-thumb-box {
            width: 54px;
            height: 54px;
            border-radius: 8px;
            overflow: hidden;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            flex-shrink: 0;
        }
        .product-thumb-box:hover {
            transform: scale(1.08);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .product-thumb-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .product-name-link {
            color: #1e293b;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.15s;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.35;
        }
        .product-name-link:hover {
            color: #0d6efd;
        }
        .badge-sku {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            padding: 3px 6px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        .badge-category {
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 500;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-block;
            max-width: 160px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .badge-price-curr {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
        }
        .badge-price-old {
            font-size: 11px;
            color: #94a3b8;
            text-decoration: line-through;
        }
        .badge-discount {
            font-size: 10px;
            font-weight: 700;
            background: #dcfce7;
            color: #15803d;
            padding: 1px 5px;
            border-radius: 4px;
        }
        .stock-pill {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .stock-pill.in-stock { background: #dcfce7; color: #166534; }
        .stock-pill.low-stock { background: #fef3c7; color: #92400e; }
        .stock-pill.out-stock { background: #fee2e2; color: #991b1b; }

        .status-switch-pill {
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            user-select: none;
        }
        .status-switch-pill.active {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }
        .status-switch-pill.inactive {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fca5a5;
        }
        .status-switch-pill.draft {
            background: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
        }
        
        .tag-toggle-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 11px;
            padding: 2px 7px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .tag-toggle-btn.is-active-feat {
            background: #e0e7ff;
            color: #4338ca;
            border-color: #a5b4fc;
            font-weight: 600;
        }
        .tag-toggle-btn.is-active-best {
            background: #ffedd5;
            color: #c2410c;
            border-color: #fdba74;
            font-weight: 600;
        }
        .tag-toggle-btn.is-active-assured {
            background: #dbeafe;
            color: #1d4ed8;
            border-color: #93c5fd;
            font-weight: 600;
        }

        .filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .kpi-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .action-icon-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .action-icon-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .action-icon-btn.btn-edit:hover {
            background: #e0f2fe;
            color: #0284c7;
            border-color: #bae6fd;
        }
        .action-icon-btn.btn-del:hover {
            background: #fee2e2;
            color: #dc2626;
            border-color: #fecaca;
        }
        .action-icon-btn.btn-view:hover {
            background: #fef3c7;
            color: #d97706;
            border-color: #fde68a;
        }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    
                    <!-- Page Header -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h3 class="mb-0"><strong>Product</strong> Management</h3>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0 text-muted" style="font-size: 12px;">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item">Catalog</li>
                                    <li class="breadcrumb-item active" aria-current="page">All Products</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="view-products.php" class="btn btn-outline-secondary d-flex align-items-center gap-1.5" title="Refresh">
                                <i class="align-middle" data-feather="rotate-ccw"></i> Refresh
                            </a>
                            <a href="product.php" class="btn btn-primary d-flex align-items-center gap-1.5 shadow-sm">
                                <i class="align-middle" data-feather="plus"></i> Add New Product
                            </a>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-check-circle fs-5"></i>
                                <div><?php echo htmlspecialchars($success_message); ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-exclamation-circle fs-5"></i>
                                <div><?php echo htmlspecialchars($error_message); ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- KPI Stat Overview Strip -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="kpi-card d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Total Products</span>
                                    <h3 class="fw-bold mb-0 text-dark mt-1"><?php echo number_format($stats['total_count']); ?></h3>
                                    <span class="text-muted text-xs">In Catalog</span>
                                </div>
                                <div class="kpi-icon-wrap bg-primary-subtle text-primary">
                                    <i class="align-middle" data-feather="package"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Active Products</span>
                                    <h3 class="fw-bold mb-0 text-success mt-1"><?php echo number_format($stats['active_count']); ?></h3>
                                    <span class="text-muted text-xs">Live in store</span>
                                </div>
                                <div class="kpi-icon-wrap bg-success-subtle text-success">
                                    <i class="align-middle" data-feather="check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Low / Out of Stock</span>
                                    <h3 class="fw-bold mb-0 text-warning mt-1"><?php echo number_format($stats['low_stock_count']); ?></h3>
                                    <span class="text-muted text-xs"><?php echo $stats['out_of_stock_count']; ?> Out of stock</span>
                                </div>
                                <div class="kpi-icon-wrap bg-warning-subtle text-warning">
                                    <i class="align-middle" data-feather="alert-triangle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Featured / Best</span>
                                    <h3 class="fw-bold mb-0 text-info mt-1"><?php echo number_format($stats['bestseller_count'] + $stats['featured_count']); ?></h3>
                                    <span class="text-muted text-xs"><?php echo $stats['bestseller_count']; ?> Bestsellers</span>
                                </div>
                                <div class="kpi-icon-wrap bg-info-subtle text-info">
                                    <i class="align-middle" data-feather="star"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search & Filter Card -->
                    <div class="filter-card p-3 mb-4">
                        <form method="GET" action="view-products.php" id="filterForm" onsubmit="event.preventDefault(); fetchProductsAjax(1);">
                            <div class="row g-2 align-items-end">
                                
                                <!-- Search Input -->
                                <div class="col-12 col-md-4 col-lg-3">
                                    <label class="form-label text-muted fw-bold text-xs mb-1">Search Products</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i id="searchIcon" class="fas fa-search text-muted"></i>
                                            <i id="searchSpinner" class="fas fa-spinner fa-spin text-primary" style="display: none;"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0 border-end-0" id="searchInput" name="search" 
                                               value="<?php echo htmlspecialchars($search); ?>" 
                                               placeholder="Name, SKU, brand..." autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary border-start-0" id="searchClearBtn" style="display: <?php echo !empty($search) ? 'block' : 'none'; ?>;" onclick="clearSearchInput()" title="Clear Search">
                                            <i class="fas fa-times text-muted" style="font-size: 11px;"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Category Filter -->
                                <div class="col-6 col-md-3 col-lg-2">
                                    <label class="form-label text-muted fw-bold text-xs mb-1">Category</label>
                                    <select class="form-select form-select-sm" id="categoryFilter" name="category_id">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Status Filter -->
                                <div class="col-6 col-md-2 col-lg-2">
                                    <label class="form-label text-muted fw-bold text-xs mb-1">Status</label>
                                    <select class="form-select form-select-sm" id="statusFilter" name="status">
                                        <option value="">All Status</option>
                                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    </select>
                                </div>

                                <!-- Badges Filter -->
                                <div class="col-6 col-md-2 col-lg-2">
                                    <label class="form-label text-muted fw-bold text-xs mb-1">Badge</label>
                                    <select class="form-select form-select-sm" id="bestsellerFilter" name="bestseller">
                                        <option value="">All Products</option>
                                        <option value="1" <?php echo $bestseller === '1' ? 'selected' : ''; ?>>Bestsellers</option>
                                        <option value="0" <?php echo $bestseller === '0' ? 'selected' : ''; ?>>Standard</option>
                                    </select>
                                </div>

                                <!-- Per Page Limit -->
                                <div class="col-6 col-md-1 col-lg-1">
                                    <label class="form-label text-muted fw-bold text-xs mb-1">Per Page</label>
                                    <select class="form-select form-select-sm" id="limitFilter" name="limit">
                                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="15" <?php echo $limit == 15 ? 'selected' : ''; ?>>15</option>
                                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                    </select>
                                </div>

                                <!-- Action Buttons -->
                                <div class="col-12 col-md-2 col-lg-2 d-flex gap-1.5">
                                    <button type="submit" class="btn btn-sm btn-primary w-50 fw-semibold">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-50" onclick="resetFiltersLive()">
                                        Reset
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>

                    <!-- Products Table Card -->
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-0 fw-bold" id="productsTitleText">All Products (<?php echo number_format($totalProducts); ?>)</h5>
                                <div class="text-muted" style="font-size: 12px;" id="productsShowingText">
                                    Showing <?php echo $totalProducts > 0 ? ($offset + 1) : 0; ?>–<?php echo min($offset + $limit, $totalProducts); ?> of <?php echo $totalProducts; ?> products
                                    <?php if ($search || $category_id || $status || $bestseller !== '' || $featured !== ''): ?>
                                        <span class="badge bg-primary-subtle text-primary ms-1">Filtered</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-muted" style="font-size: 12px; font-weight: 600;">
                                        <tr>
                                            <th style="width: 60px;" class="text-center">Image</th>
                                            <th style="min-width: 240px;">Product Details</th>
                                            <th style="width: 140px;">SKU &amp; Category</th>
                                            <th style="width: 130px;">Price (₹)</th>
                                            <th style="width: 110px;">Stock</th>
                                            <th style="width: 130px;">Highlights</th>
                                            <th style="width: 110px;">Status</th>
                                            <th style="width: 110px;">Created</th>
                                            <th style="width: 120px;" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsTableBody" style="font-size: 13px; transition: opacity 0.15s ease;">
                                        <?php echo renderProductsTbodyHtml($products); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Card Footer with Pagination -->
                        <div id="productsPaginationWrap">
                            <?php echo renderProductsPaginationHtml($page, $totalPages, $totalProducts); ?>
                        </div>

                    </div>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title text-truncate fw-bold" id="previewModalTitle">Image Preview</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-3">
                    <img id="previewModalImg" src="" alt="Preview" class="img-fluid rounded border" style="max-height: 280px; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        // Preview Image in Modal
        function previewImageModal(imgUrl, title) {
            document.getElementById('previewModalImg').src = imgUrl;
            document.getElementById('previewModalTitle').innerText = title || 'Product Image';
            var myModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
            myModal.show();
        }

        // AJAX Live Real-Time Search & Filtering Logic
        let searchDebounceTimer = null;
        let ajaxAbortController = null;

        const searchInput = document.getElementById('searchInput');
        const searchClearBtn = document.getElementById('searchClearBtn');
        const searchSpinner = document.getElementById('searchSpinner');
        const searchIcon = document.getElementById('searchIcon');
        const categoryFilter = document.getElementById('categoryFilter');
        const statusFilter = document.getElementById('statusFilter');
        const bestsellerFilter = document.getElementById('bestsellerFilter');
        const limitFilter = document.getElementById('limitFilter');
        const productsTableBody = document.getElementById('productsTableBody');
        const productsPaginationWrap = document.getElementById('productsPaginationWrap');
        const productsTitleText = document.getElementById('productsTitleText');
        const productsShowingText = document.getElementById('productsShowingText');

        function showSearchLoading(loading) {
            if (loading) {
                if (searchSpinner) searchSpinner.style.display = 'inline-block';
                if (searchIcon) searchIcon.style.display = 'none';
                if (productsTableBody) productsTableBody.style.opacity = '0.5';
            } else {
                if (searchSpinner) searchSpinner.style.display = 'none';
                if (searchIcon) searchIcon.style.display = 'inline-block';
                if (productsTableBody) productsTableBody.style.opacity = '1';
            }
        }

        function fetchProductsAjax(pageOverride = 1, updateHistory = true) {
            if (ajaxAbortController) {
                ajaxAbortController.abort();
            }
            ajaxAbortController = new AbortController();

            const searchVal = searchInput ? searchInput.value.trim() : '';
            const categoryVal = categoryFilter ? categoryFilter.value : '';
            const statusVal = statusFilter ? statusFilter.value : '';
            const bestsellerVal = bestsellerFilter ? bestsellerFilter.value : '';
            const limitVal = limitFilter ? limitFilter.value : '15';

            if (searchClearBtn) {
                searchClearBtn.style.display = searchVal.length > 0 ? 'block' : 'none';
            }

            const params = new URLSearchParams();
            params.set('ajax', '1');
            params.set('page', pageOverride || 1);
            if (searchVal) params.set('search', searchVal);
            if (categoryVal) params.set('category_id', categoryVal);
            if (statusVal) params.set('status', statusVal);
            if (bestsellerVal !== '') params.set('bestseller', bestsellerVal);
            if (limitVal) params.set('limit', limitVal);

            showSearchLoading(true);

            fetch('view-products.php?' + params.toString(), {
                signal: ajaxAbortController.signal
            })
            .then(res => res.json())
            .then(data => {
                showSearchLoading(false);
                if (data.success) {
                    if (productsTableBody) productsTableBody.innerHTML = data.tbody_html;
                    if (productsPaginationWrap) productsPaginationWrap.innerHTML = data.pagination_html;
                    if (productsTitleText) productsTitleText.innerHTML = data.title_text;
                    if (productsShowingText) productsShowingText.innerHTML = data.showing_text;

                    if (updateHistory) {
                        const historyParams = new URLSearchParams(params);
                        historyParams.delete('ajax');
                        const newUrl = 'view-products.php' + (historyParams.toString() ? ('?' + historyParams.toString()) : '');
                        window.history.replaceState({ page: pageOverride }, '', newUrl);
                    }
                }
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    showSearchLoading(false);
                    console.error('Search AJAX error:', err);
                }
            });
        }

        // Live typing in search bar with 250ms debounce
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => {
                    fetchProductsAjax(1);
                }, 250);
            });
        }

        // Clear Search Input
        function clearSearchInput() {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            if (searchClearBtn) searchClearBtn.style.display = 'none';
            fetchProductsAjax(1);
        }

        // Reset All Filters
        function resetFiltersLive() {
            if (searchInput) searchInput.value = '';
            if (categoryFilter) categoryFilter.value = '';
            if (statusFilter) statusFilter.value = '';
            if (bestsellerFilter) bestsellerFilter.value = '';
            if (limitFilter) limitFilter.value = '15';
            if (searchClearBtn) searchClearBtn.style.display = 'none';
            fetchProductsAjax(1);
        }

        // Dropdown changes trigger instant AJAX
        [categoryFilter, statusFilter, bestsellerFilter, limitFilter].forEach(el => {
            if (el) {
                el.addEventListener('change', () => fetchProductsAjax(1));
            }
        });

        // Pagination page click handler
        window.gotoProductPage = function(page) {
            fetchProductsAjax(page);
            const tableCard = document.querySelector('.card.border-0.shadow-sm');
            if (tableCard) {
                tableCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        window.resetFiltersLive = resetFiltersLive;
        window.clearSearchInput = clearSearchInput;

        // AJAX Toggle Product Status
        function toggleProductStatus(productId, btnEl) {
            var currentStatus = btnEl.classList.contains('active') ? 'active' : 'inactive';
            var nextStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            btnEl.style.opacity = '0.5';
            
            var formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', productId);
            formData.append('status', nextStatus);

            fetch('view-products.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnEl.style.opacity = '1';
                if (data.success) {
                    btnEl.classList.remove('active', 'inactive', 'draft');
                    btnEl.classList.add(data.status);
                    btnEl.innerText = '● ' + data.status.charAt(0).toUpperCase() + data.status.slice(1);
                } else {
                    alert(data.error || 'Failed to update status');
                }
            })
            .catch(err => {
                btnEl.style.opacity = '1';
                console.error(err);
            });
        }

        // AJAX Toggle Product Featured / Bestseller Flag
        function toggleProductFlag(productId, actionType, btnEl) {
            var isCurrentActive = btnEl.classList.contains('is-active-feat') || btnEl.classList.contains('is-active-best') || btnEl.classList.contains('is-active-assured');
            var nextVal = isCurrentActive ? 0 : 1;
            
            btnEl.style.opacity = '0.5';
            
            var formData = new FormData();
            formData.append('action', actionType);
            formData.append('id', productId);
            formData.append('val', nextVal);

            fetch('view-products.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnEl.style.opacity = '1';
                if (data.success) {
                    if (actionType === 'toggle_featured') {
                        if (data.featured) {
                            btnEl.classList.add('is-active-feat');
                        } else {
                            btnEl.classList.remove('is-active-feat');
                        }
                    } else if (actionType === 'toggle_bestseller') {
                        if (data.bestseller) {
                            btnEl.classList.add('is-active-best');
                        } else {
                            btnEl.classList.remove('is-active-best');
                        }
                    } else if (actionType === 'toggle_assured') {
                        if (data.assured) {
                            btnEl.classList.add('is-active-assured');
                        } else {
                            btnEl.classList.remove('is-active-assured');
                        }
                    }
                } else {
                    alert(data.error || 'Failed to update flag');
                }
            })
            .catch(err => {
                btnEl.style.opacity = '1';
                console.error(err);
            });
        }
    </script>
</body>
</html>