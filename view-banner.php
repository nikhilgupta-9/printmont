<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

$bannerController = new BannerController();

$search   = $_GET['search']   ?? '';
$pageFilter = $_GET['filter_page'] ?? '';
$page     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage  = 15;

$banners      = $bannerController->getBannersWithPagination($page, $perPage, $search);
$totalBanners = $bannerController->getBannersCount($search);
$totalPages   = ceil($totalBanners / $perPage);
$stats        = $bannerController->getBannerStats();
$allSections  = $bannerController->getAllSections();

// Get unique pages for filter tabs
$pages = array_unique(array_column($allSections, 'page'));

// Filter banners by page if filter is set
if ($pageFilter !== '') {
    $banners = array_filter($banners, fn($b) => ($b['section_page'] ?? '') === $pageFilter);
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message']   ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Banner Management | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .banner-thumb { width: 80px; height: 55px; object-fit: cover; border-radius: 4px; border: 1px solid #dee2e6; }
        .status-active   { background: #d1fae5; color: #065f46; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
        .status-inactive { background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
        .stats-card { transition: transform .2s; }
        .stats-card:hover { transform: translateY(-2px); }
        .section-badge { background: #e0e7ff; color: #3730a3; padding: 2px 7px; border-radius: 8px; font-size: 11px; font-weight: 600; }
        .page-badge { background: #f3f4f6; color: #374151; padding: 2px 6px; border-radius: 6px; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; }
        .col-dots { display: inline-flex; gap: 3px; vertical-align: middle; }
        .col-dot { width: 8px; height: 8px; border-radius: 2px; background: #6366f1; display: inline-block; }
        .slider-badge { background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 6px; font-size: 10px; }
        .action-btn { padding: 3px 8px; font-size: 12px; }
        .filter-tabs .nav-link { color: #6c757d; font-size: 13px; }
        .filter-tabs .nav-link.active { color: #0d6efd; font-weight: 600; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <!-- Header -->
                <div class="row mb-2 mb-xl-3">
                    <div class="col-auto d-none d-sm-block">
                        <h3><strong>Banner</strong> Management</h3>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1">
                        <a href="add-banner.php" class="btn btn-primary">+ Add New Banner</a>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card bg-primary text-white stats-card">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['total'] ?? 0; ?></h4>
                                        <small>Total Banners</small>
                                    </div>
                                    <i class="fas fa-images fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-success text-white stats-card">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['active'] ?? 0; ?></h4>
                                        <small>Active</small>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-warning text-white stats-card">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['scheduled'] ?? 0; ?></h4>
                                        <small>Scheduled</small>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-danger text-white stats-card">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['expired'] ?? 0; ?></h4>
                                        <small>Expired</small>
                                    </div>
                                    <i class="fas fa-calendar-times fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Banners Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h5 class="card-title mb-0">All Banners</h5>
                                <small class="text-muted">Grouped by section · search by title or section</small>
                            </div>
                            <!-- Search -->
                            <form method="GET" class="d-flex gap-2" style="min-width:260px">
                                <input type="hidden" name="filter_page" value="<?php echo htmlspecialchars($pageFilter); ?>">
                                <input type="text" class="form-control form-control-sm" name="search"
                                       placeholder="Search title / section…"
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit">Search</button>
                                <?php if ($search || $pageFilter): ?>
                                    <a href="view-banner.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <!-- Page filter tabs -->
                        <ul class="nav nav-tabs filter-tabs mt-3 border-0">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $pageFilter === '' ? 'active' : ''; ?>"
                                   href="?search=<?php echo urlencode($search); ?>">All Pages</a>
                            </li>
                            <?php foreach ($pages as $pg): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $pageFilter === $pg ? 'active' : ''; ?>"
                                       href="?filter_page=<?php echo urlencode($pg); ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo ucfirst($pg); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:50px">#</th>
                                        <th style="width:100px">Image</th>
                                        <th>Title</th>
                                        <th>Section</th>
                                        <th style="width:80px">Columns</th>
                                        <th style="width:70px">Order</th>
                                        <th style="width:80px">Status</th>
                                        <th>Schedule</th>
                                        <th style="width:90px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($banners)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <?php if ($search || $pageFilter): ?>
                                                No banners match your filter. <a href="view-banner.php">Clear filters</a>
                                            <?php else: ?>
                                                No banners yet. <a href="add-banner.php">Add your first banner</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $lastPage = null;
                                    foreach ($banners as $b):
                                        $currentPage = $b['section_page'] ?? '';
                                        if ($currentPage !== $lastPage):
                                            $lastPage = $currentPage;
                                    ?>
                                        <tr class="table-secondary">
                                            <td colspan="9" class="py-2 px-3">
                                                <strong style="font-size:12px;letter-spacing:.5px;text-transform:uppercase">
                                                    <?php echo htmlspecialchars($currentPage ?: 'No Page'); ?> Page
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                        <tr>
                                            <td class="text-muted" style="font-size:12px"><?php echo $b['id']; ?></td>
                                            <td>
                                                <?php if (!empty($b['image_url_desktop'])): ?>
                                                    <img src="<?php echo htmlspecialchars($b['image_url_desktop']); ?>"
                                                         class="banner-thumb" title="Desktop">
                                                <?php else: ?>
                                                    <div class="banner-thumb d-flex align-items-center justify-content-center bg-light text-muted" style="font-size:10px">No img</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($b['title']); ?></strong>
                                                <?php if (!empty($b['description'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($b['description'], 0, 60, '…')); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($b['target_url'])): ?>
                                                    <br><small><a href="<?php echo htmlspecialchars($b['target_url']); ?>" target="_blank" class="text-primary">↗ link</a></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="section-badge"><?php echo htmlspecialchars($b['section_label'] ?? $b['section_key'] ?? '—'); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($b['is_slider'])): ?>
                                                    <span class="slider-badge">Slider</span>
                                                <?php else: ?>
                                                    <span class="col-dots">
                                                        <?php for ($c = 0; $c < (int)($b['columns_per_row'] ?? 1); $c++): ?>
                                                            <span class="col-dot"></span>
                                                        <?php endfor; ?>
                                                    </span>
                                                    <small class="text-muted d-block" style="font-size:10px"><?php echo $b['columns_per_row'] ?? 1; ?> col</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?php echo $b['display_order']; ?></td>
                                            <td>
                                                <span class="status-<?php echo $b['status']; ?>">
                                                    <?php echo ucfirst($b['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php if ($b['start_date']): ?>From: <?php echo date('d M Y', strtotime($b['start_date'])); ?><br><?php endif; ?>
                                                    <?php if ($b['end_date']): ?>To: <?php echo date('d M Y', strtotime($b['end_date'])); ?><?php endif; ?>
                                                    <?php if (!$b['start_date'] && !$b['end_date']): ?>—<?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <a href="edit-banner.php?id=<?php echo $b['id']; ?>"
                                                   class="btn btn-sm btn-outline-primary action-btn" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete-banner.php?id=<?php echo $b['id']; ?>"
                                                   class="btn btn-sm btn-outline-danger action-btn"
                                                   onclick="return confirm('Delete this banner?')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                                <small class="text-muted">
                                    Showing <?php echo count($banners); ?> of <?php echo $totalBanners; ?> banners
                                </small>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&filter_page=<?php echo urlencode($pageFilter); ?>">&laquo;</a>
                                        </li>
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_page=<?php echo urlencode($pageFilter); ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php elseif (abs($i - $page) == 3): ?>
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&filter_page=<?php echo urlencode($pageFilter); ?>">&raquo;</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>
<script src="js/app.js"></script>
</body>
</html>
