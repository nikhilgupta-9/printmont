<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SmallBannerSliderController.php';

$controller = new SmallBannerSliderController();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    switch ($_GET['action']) {
        case 'delete':
            if ($controller->deleteSlider($id)) {
                $_SESSION['success_message'] = "Small banner slider deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete small banner slider.";
            }
            break;
            
        case 'toggle_status':
            $type = $_GET['type'] ?? 'all';
            if ($controller->toggleStatus($id, $type)) {
                $_SESSION['success_message'] = "Slider status updated!";
            } else {
                $_SESSION['error_message'] = "Failed to update slider status.";
            }
            break;
    }
    
    $redirectUrl = "small-banner-slider.php";
    $query = [];
    if (!empty($_GET['page'])) $query['page'] = $_GET['page'];
    if (!empty($_GET['tab'])) $query['tab'] = $_GET['tab'];
    if (!empty($_GET['section'])) $query['section'] = $_GET['section'];
    if (!empty($_GET['device'])) $query['device'] = $_GET['device'];
    if (!empty($query)) $redirectUrl .= '?' . http_build_query($query);

    header("Location: " . $redirectUrl);
    exit;
}

// Active filters
$activePage = $_GET['page'] ?? ($_GET['tab'] ?? 'all');
$activeSection = trim($_GET['section'] ?? '');
$activeDevice = trim($_GET['device'] ?? 'all');
$highlightId = intval($_GET['highlight'] ?? 0);

$pageCounts = $controller->getPageCounts();
$sliders = $controller->getSlidersFiltered($activePage, $activeSection, ($activeDevice !== 'all' ? $activeDevice : ''));

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
    <title>Small Banner Carousel | Page-wise Setup | Printmont</title>
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="icon" type="image/png" href="img/icons/icon-48x48.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .status-active { background-color: #d1fae5; color: #065f46; }
        .status-inactive { background-color: #fee2e2; color: #991b1b; }
        .slider-thumb { width: 110px; height: 38px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.08); background: #f8fafc; }
        .slider-thumb-mob { width: 65px; height: 32px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; background: #f8fafc; }
        .format-badge { font-size: 11px; padding: 2px 6px; border-radius: 4px; background: #eff6ff; color: #1d4ed8; font-weight: 600; }
        .table > tbody > tr > td { vertical-align: middle; }
        .highlight-row { background-color: #fef3c7 !important; border-left: 4px solid #f59e0b !important; animation: pulseHighlight 2s ease-in-out; }
        @keyframes pulseHighlight {
            0% { background-color: #fde68a; }
            50% { background-color: #fef3c7; }
            100% { background-color: #fffbeb; }
        }
        .page-nav-tab {
            padding: 8px 16px;
            font-weight: 600;
            font-size: 13px;
            border-radius: 6px;
            color: #475569;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            transition: all 0.2s;
        }
        .page-nav-tab:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        .page-nav-tab.active {
            background: #2563eb;
            color: #ffffff;
        }
        .page-nav-tab.active .badge {
            background: #ffffff !important;
            color: #2563eb !important;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <!-- Top Page Title & Actions -->
                    <div class="row mb-3 align-items-center">
                        <div class="col">
                            <h1 class="h3 mb-1 text-gray-800 fw-bold d-flex align-items-center gap-2">
                                <i class="fas fa-layer-group text-primary"></i> Small Banner Carousel
                            </h1>
                            <p class="text-muted mb-0 small">
                                Manage compact/slim promotional ribbon sliders for <strong>Home, Category, Product, Cart, and Checkout</strong> pages.
                            </p>
                        </div>
                        <div class="col-auto d-flex gap-2">
                            <a href="home-layout-manager.php?tab=mobile" class="btn btn-outline-secondary shadow-sm btn-sm">
                                <i class="fas fa-th-list me-1"></i> Home Layout Manager
                            </a>
                            <a href="add-small-banner-slider.php<?php echo (!empty($activePage) && $activePage !== 'all') ? '?page=' . urlencode($activePage) : ''; ?><?php echo (!empty($activeSection)) ? '&section=' . urlencode($activeSection) : ''; ?>" class="btn btn-primary shadow-sm btn-sm">
                                <i class="fas fa-plus me-1"></i> Add Small Banner
                            </a>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-exclamation-triangle me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Active Filter Notice (if filtered by section or layout manager) -->
                    <?php if (!empty($activeSection) || (!empty($activePage) && $activePage !== 'all') || (!empty($activeDevice) && $activeDevice !== 'all')): ?>
                        <div class="alert alert-info border-info d-flex align-items-center justify-content-between p-3 mb-3 shadow-sm rounded-3">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fas fa-filter text-primary fa-lg"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">Active Filter Applied</h6>
                                    <div class="small text-muted">
                                        <?php if (!empty($activePage) && $activePage !== 'all'): ?>
                                            <span class="me-2"><strong>Page:</strong> <?php echo htmlspecialchars(ucfirst($activePage)); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($activeSection)): ?>
                                            <span class="me-2"><strong>Section Key:</strong> <code class="bg-white px-2 py-0.5 rounded border"><?php echo htmlspecialchars($activeSection); ?></code></span>
                                        <?php endif; ?>
                                        <?php if (!empty($activeDevice) && $activeDevice !== 'all'): ?>
                                            <span class="me-2"><strong>Device:</strong> <?php echo htmlspecialchars(ucfirst($activeDevice)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="add-small-banner-slider.php?page=<?php echo urlencode($activePage); ?>&section=<?php echo urlencode($activeSection); ?>" class="btn btn-sm btn-primary fw-semibold">
                                    <i class="fas fa-plus me-1"></i> Add To This Section
                                </a>
                                <a href="small-banner-slider.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Clear Filters
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Page-wise Tabs Navigation -->
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex flex-wrap gap-1">
                                <a href="small-banner-slider.php?page=all<?php echo !empty($activeDevice) ? '&device='.$activeDevice : ''; ?>" 
                                   class="page-nav-tab <?php echo $activePage === 'all' ? 'active' : ''; ?>">
                                    <i class="fas fa-globe"></i> All Pages
                                    <span class="badge bg-secondary text-white rounded-pill px-2 py-0.5"><?php echo $pageCounts['all']; ?></span>
                                </a>
                                <a href="small-banner-slider.php?page=home<?php echo !empty($activeDevice) ? '&device='.$activeDevice : ''; ?>" 
                                   class="page-nav-tab <?php echo $activePage === 'home' ? 'active' : ''; ?>">
                                    <i class="fas fa-home"></i> Home Page
                                    <span class="badge bg-secondary text-white rounded-pill px-2 py-0.5"><?php echo $pageCounts['home']; ?></span>
                                </a>
                                <a href="small-banner-slider.php?page=category<?php echo !empty($activeDevice) ? '&device='.$activeDevice : ''; ?>" 
                                   class="page-nav-tab <?php echo $activePage === 'category' ? 'active' : ''; ?>">
                                    <i class="fas fa-th"></i> Category Page
                                    <span class="badge bg-secondary text-white rounded-pill px-2 py-0.5"><?php echo $pageCounts['category']; ?></span>
                                </a>
                                <a href="small-banner-slider.php?page=product<?php echo !empty($activeDevice) ? '&device='.$activeDevice : ''; ?>" 
                                   class="page-nav-tab <?php echo $activePage === 'product' ? 'active' : ''; ?>">
                                    <i class="fas fa-box"></i> Product Detail Page
                                    <span class="badge bg-secondary text-white rounded-pill px-2 py-0.5"><?php echo $pageCounts['product']; ?></span>
                                </a>
                                <a href="small-banner-slider.php?page=cart<?php echo !empty($activeDevice) ? '&device='.$activeDevice : ''; ?>" 
                                   class="page-nav-tab <?php echo $activePage === 'cart' ? 'active' : ''; ?>">
                                    <i class="fas fa-shopping-cart"></i> Cart Page
                                    <span class="badge bg-secondary text-white rounded-pill px-2 py-0.5"><?php echo $pageCounts['cart']; ?></span>
                                </a>
                                <a href="small-banner-slider.php?page=checkout<?php echo !empty($activeDevice) ? '&device='.$activeDevice : ''; ?>" 
                                   class="page-nav-tab <?php echo $activePage === 'checkout' ? 'active' : ''; ?>">
                                    <i class="fas fa-credit-card"></i> Checkout Page
                                    <span class="badge bg-secondary text-white rounded-pill px-2 py-0.5"><?php echo $pageCounts['checkout']; ?></span>
                                </a>
                                <a href="small-banner-slider.php?page=other<?php echo !empty($activeDevice) ? '&device='.$activeDevice : ''; ?>" 
                                   class="page-nav-tab <?php echo $activePage === 'other' ? 'active' : ''; ?>">
                                    <i class="fas fa-ellipsis-h"></i> Other Pages
                                    <span class="badge bg-secondary text-white rounded-pill px-2 py-0.5"><?php echo $pageCounts['other']; ?></span>
                                </a>
                            </div>

                            <!-- Device View Filter -->
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="small-banner-slider.php?page=<?php echo urlencode($activePage); ?>&device=all<?php echo !empty($activeSection) ? '&section='.urlencode($activeSection) : ''; ?>" 
                                   class="btn <?php echo $activeDevice === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                    <i class="fas fa-desktop-alt me-1"></i> All Devices
                                </a>
                                <a href="small-banner-slider.php?page=<?php echo urlencode($activePage); ?>&device=desktop<?php echo !empty($activeSection) ? '&section='.urlencode($activeSection) : ''; ?>" 
                                   class="btn <?php echo $activeDevice === 'desktop' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                    <i class="fas fa-desktop me-1"></i> Desktop
                                </a>
                                <a href="small-banner-slider.php?page=<?php echo urlencode($activePage); ?>&device=mobile<?php echo !empty($activeSection) ? '&section='.urlencode($activeSection) : ''; ?>" 
                                   class="btn <?php echo $activeDevice === 'mobile' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                    <i class="fas fa-mobile-alt me-1"></i> Mobile
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Sliders Table Card -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold text-dark">
                                <i class="fas fa-images text-primary me-2"></i> Small Banner Sliders (<?php echo count($sliders); ?>)
                            </h5>
                            <a href="add-small-banner-slider.php<?php echo (!empty($activePage) && $activePage !== 'all') ? '?page=' . urlencode($activePage) : ''; ?><?php echo (!empty($activeSection)) ? '&section=' . urlencode($activeSection) : ''; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Add New
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" id="slidersTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Desktop Banner</th>
                                            <th>Mobile Banner</th>
                                            <th>Title & Info</th>
                                            <th>Target Pages & Section</th>
                                            <th>Links & Format</th>
                                            <th>Sort Order</th>
                                            <th>Desktop Status</th>
                                            <th>Mobile Status</th>
                                            <th style="width: 130px;" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($sliders)): ?>
                                            <tr>
                                                <td colspan="10" class="text-center py-5 text-muted">
                                                    <i class="fas fa-layer-group fa-3x mb-3 text-secondary d-block"></i>
                                                    <p class="mb-1 fw-semibold">No small banner sliders found for this page/filter</p>
                                                    <small class="text-muted">Create small banner ribbons to promote top deals, free shipping, or category launches.</small><br>
                                                    <a href="add-small-banner-slider.php<?php echo (!empty($activePage) && $activePage !== 'all') ? '?page=' . urlencode($activePage) : ''; ?><?php echo (!empty($activeSection)) ? '&section=' . urlencode($activeSection) : ''; ?>" class="btn btn-sm btn-primary mt-3">
                                                        <i class="fas fa-plus me-1"></i> Add Small Banner
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($sliders as $index => $row): 
                                                $isMatchedSection = (!empty($activeSection) && (
                                                    stripos($row['target_section'] ?? '', $activeSection) !== false ||
                                                    stripos($row['placement_rules'] ?? '', $activeSection) !== false
                                                ));
                                                $isHighlighted = ($highlightId === (int)$row['id']) || $isMatchedSection;
                                            ?>
                                                <tr id="slider-row-<?php echo $row['id']; ?>" class="<?php echo $isHighlighted ? 'highlight-row' : ''; ?>">
                                                    <td class="fw-bold text-muted"><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <?php if (!empty($row['desktop_image'])): ?>
                                                            <a href="<?php echo htmlspecialchars($row['desktop_image']); ?>" target="_blank" title="View Desktop Banner">
                                                                <img src="<?php echo htmlspecialchars($row['desktop_image']); ?>" class="slider-thumb" alt="Desktop">
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-secondary border">No Desktop Image</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($row['mobile_image'])): ?>
                                                            <a href="<?php echo htmlspecialchars($row['mobile_image']); ?>" target="_blank" title="View Mobile Banner">
                                                                <img src="<?php echo htmlspecialchars($row['mobile_image']); ?>" class="slider-thumb-mob" alt="Mobile">
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-secondary border">No Mobile Image</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($row['title']); ?></span>
                                                        <small class="text-muted">ID: #<?php echo $row['id']; ?></small>
                                                        <?php if ($isMatchedSection): ?>
                                                            <span class="badge bg-warning text-dark border border-warning ms-1" style="font-size: 10px;">
                                                                <i class="fas fa-check-circle me-1"></i> Active Section
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $pgs = !empty($row['target_pages']) ? explode(',', $row['target_pages']) : ['home'];
                                                            if (in_array('all', $pgs)): 
                                                        ?>
                                                            <span class="badge bg-primary">All Pages</span>
                                                        <?php else: ?>
                                                            <div class="d-flex flex-wrap gap-1" style="max-width: 170px;">
                                                                <?php foreach ($pgs as $p): ?>
                                                                    <span class="badge bg-secondary bg-opacity-10 text-dark border" style="font-size: 10px; font-weight: 500;">
                                                                        <i class="fas fa-file-alt me-0.5 text-muted"></i> <?php echo htmlspecialchars(ucfirst($p)); ?>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($row['target_section'])): ?>
                                                            <div class="mt-1">
                                                                <span class="badge bg-info bg-opacity-10 text-info border" style="font-size: 10px;">
                                                                    <i class="fas fa-arrows-alt-v me-0.5"></i> <?php echo ucfirst($row['position_relation'] ?? 'after'); ?>: <strong><?php echo htmlspecialchars($row['target_section']); ?></strong>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <div><strong class="text-muted">Desk:</strong> <a href="<?php echo htmlspecialchars($row['desktop_url'] ?: '#'); ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 120px;"><?php echo htmlspecialchars($row['desktop_url'] ?: 'No URL'); ?></a></div>
                                                            <div><strong class="text-muted">Mob:</strong> <a href="<?php echo htmlspecialchars($row['mobile_url'] ?: '#'); ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 120px;"><?php echo htmlspecialchars($row['mobile_url'] ?: 'No URL'); ?></a></div>
                                                            <span class="format-badge mt-1 d-inline-block"><?php echo htmlspecialchars($row['mobile_design'] ?: 'small_banner_standard'); ?></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">Desk: <?php echo (int)$row['desktop_sort_order']; ?></span><br>
                                                        <span class="badge bg-light text-dark border mt-1">Mob: <?php echo (int)$row['mobile_sort_order']; ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="small-banner-slider.php?action=toggle_status&type=desktop&id=<?php echo $row['id']; ?><?php echo !empty($activePage) ? '&page='.urlencode($activePage) : ''; ?><?php echo !empty($activeSection) ? '&section='.urlencode($activeSection) : ''; ?>" 
                                                           class="status-badge <?php echo ($row['desktop_status'] ?? 'active') === 'active' ? 'status-active' : 'status-inactive'; ?>"
                                                           title="Click to toggle desktop visibility">
                                                            <i class="fas fa-circle" style="font-size: 8px;"></i>
                                                            <?php echo ($row['desktop_status'] ?? 'active') === 'active' ? 'Show' : 'Hide'; ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="small-banner-slider.php?action=toggle_status&type=mobile&id=<?php echo $row['id']; ?><?php echo !empty($activePage) ? '&page='.urlencode($activePage) : ''; ?><?php echo !empty($activeSection) ? '&section='.urlencode($activeSection) : ''; ?>" 
                                                           class="status-badge <?php echo ($row['mobile_status'] ?? 'active') === 'active' ? 'status-active' : 'status-inactive'; ?>"
                                                           title="Click to toggle mobile visibility">
                                                            <i class="fas fa-circle" style="font-size: 8px;"></i>
                                                            <?php echo ($row['mobile_status'] ?? 'active') === 'active' ? 'Show' : 'Hide'; ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="edit-small-banner-slider.php?id=<?php echo $row['id']; ?><?php echo !empty($activePage) ? '&page='.urlencode($activePage) : ''; ?><?php echo !empty($activeSection) ? '&section='.urlencode($activeSection) : ''; ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit Slider">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="small-banner-slider.php?action=delete&id=<?php echo $row['id']; ?><?php echo !empty($activePage) ? '&page='.urlencode($activePage) : ''; ?><?php echo !empty($activeSection) ? '&section='.urlencode($activeSection) : ''; ?>" 
                                                           class="btn btn-sm btn-outline-danger" 
                                                           onclick="return confirm('Are you sure you want to delete this small banner slider?');"
                                                           title="Delete Slider">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>
    <script src="js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const highlightedRow = document.querySelector('.highlight-row');
            if (highlightedRow) {
                highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>
</html>
