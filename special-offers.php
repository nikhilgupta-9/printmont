<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SpecialOfferBannerController.php';

$isAdmin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
if (!$isAdmin) {
    header("Location: index.php");
    exit();
}

$controller = new SpecialOfferBannerController();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    switch ($_GET['action']) {
        case 'delete':
            if ($controller->deleteBanner($id)) {
                $_SESSION['success_message'] = "Special Offer banner deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete special offer banner.";
            }
            break;

        case 'toggle_status':
            if ($controller->toggleStatus($id)) {
                $_SESSION['success_message'] = "Status updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update status.";
            }
            break;
    }

    header("Location: special-offers.php");
    exit;
}

$filterType   = $_GET['type'] ?? 'all';
$filterTarget = $_GET['target'] ?? 'all';
$filterFormat = $_GET['format'] ?? 'all';

$allBanners = $controller->getAllBanners('all', 'all', 'all');

// Stats
$totalCount    = count($allBanners);
$activeCount   = count(array_filter($allBanners, fn($b) => $b['status'] === 'active'));
$bannerCount   = count(array_filter($allBanners, fn($b) => $b['display_type'] === 'banner'));
$carouselCount = count(array_filter($allBanners, fn($b) => $b['display_type'] === 'carousel'));
$squareGridCount = count(array_filter($allBanners, fn($b) => $b['layout_format'] === '2x2'));

// Filtered list
$banners = array_filter($allBanners, function($b) use ($filterType, $filterTarget, $filterFormat) {
    if ($filterType !== 'all' && $b['display_type'] !== $filterType) return false;
    if ($filterTarget !== 'all' && $b['device_target'] !== 'all' && $b['device_target'] !== $filterTarget) return false;
    if ($filterFormat !== 'all' && $b['layout_format'] !== $filterFormat) return false;
    return true;
});

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Special Offer Banners &amp; Carousels | Printmont</title>
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="icon" type="image/png" href="img/icons/icon-48x48.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }
        .status-active { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-active:hover { background-color: #bbf7d0; }
        .status-inactive { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .status-inactive:hover { background-color: #fecaca; }

        .banner-thumb {
            width: 100px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .banner-thumb:hover {
            transform: scale(1.4);
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0,0,0,0.18);
        }
        .banner-thumb-square {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .type-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .type-banner { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
        .type-carousel { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }

        .layout-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .offer-tag-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            color: #fff;
            display: inline-block;
        }

        .stat-card {
            border-radius: 10px;
            transition: transform 0.15s ease-in-out;
        }
        .stat-card:hover {
            transform: translateY(-2px);
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
                    <!-- Page Header -->
                    <div class="row mb-4 align-items-center">
                        <div class="col">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark p-2 rounded-3 fs-6">
                                    <i class="fas fa-tag"></i>
                                </span>
                                <div>
                                    <h1 class="h3 mb-0 text-gray-800 fw-bold">Special Offer Banners</h1>
                                    <p class="text-muted mb-0 small">Manage promotional special offer banners, carousels, 2x2 square grids &amp; peeking sliders.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <a href="add-special-offer.php" class="btn btn-primary btn-lg shadow-sm">
                                <i class="fas fa-plus me-1"></i> Add Special Offer
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

                    <!-- Stats Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="card stat-card shadow-sm border-0 bg-white">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-muted small fw-semibold">Total Offers</div>
                                            <div class="h3 fw-bold mb-0 text-dark"><?php echo $totalCount; ?></div>
                                        </div>
                                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                                            <i class="fas fa-layer-group fa-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="card stat-card shadow-sm border-0 bg-white">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-muted small fw-semibold">Active Offers</div>
                                            <div class="h3 fw-bold mb-0 text-success"><?php echo $activeCount; ?></div>
                                        </div>
                                        <div class="p-2 bg-success bg-opacity-10 text-success rounded-3">
                                            <i class="fas fa-check-circle fa-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="card stat-card shadow-sm border-0 bg-white">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-muted small fw-semibold">Static Banners</div>
                                            <div class="h3 fw-bold mb-0 text-primary"><?php echo $bannerCount; ?></div>
                                        </div>
                                        <div class="p-2 bg-info bg-opacity-10 text-info rounded-3">
                                            <i class="fas fa-image fa-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="card stat-card shadow-sm border-0 bg-white">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-muted small fw-semibold">Carousels / Sliders</div>
                                            <div class="h3 fw-bold mb-0 text-warning"><?php echo $carouselCount; ?></div>
                                        </div>
                                        <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-3">
                                            <i class="fas fa-sliders fa-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters & Table Card -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="row g-2 align-items-center justify-content-between">
                                <div class="col-md-4 col-12">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Search special offers..." oninput="filterTableRows()">
                                    </div>
                                </div>
                                <div class="col-md-8 col-12 d-flex flex-wrap gap-2 justify-content-md-end">
                                    <select class="form-select form-select-sm w-auto" onchange="applyFilter('type', this.value)">
                                        <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Types (Banner &amp; Carousel)</option>
                                        <option value="banner" <?php echo $filterType === 'banner' ? 'selected' : ''; ?>>Static Banners Only</option>
                                        <option value="carousel" <?php echo $filterType === 'carousel' ? 'selected' : ''; ?>>Carousels Only</option>
                                    </select>

                                    <select class="form-select form-select-sm w-auto" onchange="applyFilter('format', this.value)">
                                        <option value="all" <?php echo $filterFormat === 'all' ? 'selected' : ''; ?>>All Layout Formats</option>
                                        <option value="1" <?php echo $filterFormat === '1' ? 'selected' : ''; ?>>1 Col (Full Width)</option>
                                        <option value="1.5" <?php echo $filterFormat === '1.5' ? 'selected' : ''; ?>>1.5 Peeking Carousel</option>
                                        <option value="2" <?php echo $filterFormat === '2' ? 'selected' : ''; ?>>2 Columns</option>
                                        <option value="2x2" <?php echo $filterFormat === '2x2' ? 'selected' : ''; ?>>2x2 Square (4 Images in 2 Rows)</option>
                                        <option value="3" <?php echo $filterFormat === '3' ? 'selected' : ''; ?>>3 Columns</option>
                                    </select>

                                    <select class="form-select form-select-sm w-auto" onchange="applyFilter('target', this.value)">
                                        <option value="all" <?php echo $filterTarget === 'all' ? 'selected' : ''; ?>>All Devices</option>
                                        <option value="desktop" <?php echo $filterTarget === 'desktop' ? 'selected' : ''; ?>>Desktop Only</option>
                                        <option value="mobile" <?php echo $filterTarget === 'mobile' ? 'selected' : ''; ?>>Mobile Only</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="specialOffersTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th style="width: 130px;">Desktop Image</th>
                                            <th style="width: 90px;">Mobile Image</th>
                                            <th>Title &amp; Badge</th>
                                            <th>Display Type</th>
                                            <th>Layout Format</th>
                                            <th>Target Device</th>
                                            <th>Redirect Link</th>
                                            <th>Status</th>
                                            <th style="width: 120px;" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($banners)): ?>
                                            <tr>
                                                <td colspan="10" class="text-center py-5 text-muted">
                                                    <i class="fas fa-tag fa-3x mb-3 text-secondary opacity-50"></i>
                                                    <h5>No Special Offer Banners Found</h5>
                                                    <p class="small mb-3">Naye special offer banner ya carousel add karne ke liye upar button pr click karein.</p>
                                                    <a href="add-special-offer.php" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-plus me-1"></i> Add Special Offer Now
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($banners as $idx => $b): ?>
                                                <tr>
                                                    <td class="text-muted fw-bold"><?php echo $idx + 1; ?></td>
                                                    <td>
                                                        <?php if (!empty($b['image_desktop'])): ?>
                                                            <a href="<?php echo htmlspecialchars($b['image_desktop']); ?>" target="_blank" title="Click to view full image">
                                                                <img src="<?php echo htmlspecialchars($b['image_desktop']); ?>" 
                                                                    alt="Desktop preview" 
                                                                    class="<?php echo $b['layout_format'] === '2x2' ? 'banner-thumb-square' : 'banner-thumb'; ?>"
                                                                    onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22100%22%20height%3D%2252%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20100%2052%22%3E%3Crect%20width%3D%22100%22%20height%3D%2252%22%20fill%3D%22%23f1f5f9%22%2F%3E%3Ctext%20x%3D%2250%22%20y%3D%2230%22%20fill%3D%22%2394a3b8%22%20font-family%3D%22sans-serif%22%20font-size%3D%2211%22%20font-weight%3D%22bold%22%20text-anchor%3D%22middle%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E';">
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border">No Image</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($b['image_mobile'])): ?>
                                                            <a href="<?php echo htmlspecialchars($b['image_mobile']); ?>" target="_blank" title="Click to view full image">
                                                                <img src="<?php echo htmlspecialchars($b['image_mobile']); ?>" 
                                                                    alt="Mobile preview" 
                                                                    class="<?php echo $b['layout_format'] === '2x2' ? 'banner-thumb-square' : 'banner-thumb'; ?>" 
                                                                    style="width: 60px; height: 38px;"
                                                                    onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2260%22%20height%3D%2238%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2060%2038%22%3E%3Crect%20width%3D%2260%22%20height%3D%2238%22%20fill%3D%22%23f1f5f9%22%2F%3E%3Ctext%20x%3D%2230%22%20y%3D%2222%22%20fill%3D%22%2394a3b8%22%20font-family%3D%22sans-serif%22%20font-size%3D%229%22%20font-weight%3D%22bold%22%20text-anchor%3D%22middle%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E';">
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border">Same</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-dark">
                                                            <?php echo htmlspecialchars($b['title'] ?: 'Untitled Special Offer'); ?>
                                                        </div>
                                                        <?php if (!empty($b['subtitle'])): ?>
                                                            <div class="small text-muted"><?php echo htmlspecialchars($b['subtitle']); ?></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($b['offer_badge'])): ?>
                                                            <span class="offer-tag-badge mt-1" style="background-color: <?php echo htmlspecialchars($b['badge_color'] ?: '#ef4444'); ?>;">
                                                                <i class="fas fa-fire me-1"></i><?php echo htmlspecialchars($b['offer_badge']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($b['display_type'] === 'carousel'): ?>
                                                            <span class="type-badge type-carousel">
                                                                <i class="fas fa-sliders"></i> Carousel
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="type-badge type-banner">
                                                                <i class="fas fa-image"></i> Static Banner
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="layout-badge">
                                                            <?php 
                                                            switch ($b['layout_format']) {
                                                                case '1':
                                                                    echo '<i class="fas fa-square text-primary me-1"></i> 1 Col (Full)';
                                                                    break;
                                                                case '1.5':
                                                                    echo '<i class="fas fa-film text-warning me-1"></i> 1.5 Peeking';
                                                                    break;
                                                                case '2':
                                                                    echo '<i class="fas fa-columns text-success me-1"></i> 2 Columns';
                                                                    break;
                                                                case '2x2':
                                                                    echo '<i class="fas fa-th-large text-danger me-1"></i> 2x2 Square (4 Imgs in 2 Rows)';
                                                                    break;
                                                                case '3':
                                                                    echo '<i class="fas fa-table-columns text-info me-1"></i> 3 Columns';
                                                                    break;
                                                                default:
                                                                    echo htmlspecialchars($b['layout_format']);
                                                            }
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">
                                                            <i class="fas <?php echo $b['device_target'] === 'mobile' ? 'fa-mobile-screen' : ($b['device_target'] === 'desktop' ? 'fa-desktop' : 'fa-laptop'); ?> me-1"></i>
                                                            <?php echo ucfirst($b['device_target']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="<?php echo htmlspecialchars($b['link_url'] ?: '#'); ?>" target="_blank" class="small text-truncate d-inline-block text-decoration-none text-primary" style="max-width: 160px;">
                                                            <?php echo htmlspecialchars($b['link_url'] ?: '/allproducts'); ?>
                                                            <i class="fas fa-external-link-alt ms-1" style="font-size: 10px;"></i>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="special-offers.php?action=toggle_status&id=<?php echo $b['id']; ?>" 
                                                           class="status-badge <?php echo $b['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>"
                                                           title="Click to toggle status">
                                                            <i class="fas <?php echo $b['status'] === 'active' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                                            <?php echo ucfirst($b['status']); ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="edit-special-offer.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-primary" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="special-offers.php?action=delete&id=<?php echo $b['id']; ?>" 
                                                               class="btn btn-outline-danger" 
                                                               title="Delete" 
                                                               onclick="return confirm('Are you sure you want to delete this special offer?');">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
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
        function applyFilter(param, value) {
            const url = new URL(window.location.href);
            if (value === 'all') {
                url.searchParams.delete(param);
            } else {
                url.searchParams.set(param, value);
            }
            window.location.href = url.toString();
        }

        function filterTableRows() {
            const query = (document.getElementById('tableSearchInput').value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('#specialOffersTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = (query === '' || text.includes(query)) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
