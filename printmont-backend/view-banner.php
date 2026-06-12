<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

// Get search and pagination parameters
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10; // Items per page

// Create banner controller
$bannerController = new BannerController();

// Get banners with pagination and search
$banners = $bannerController->getBannersWithPagination($page, $perPage, $search);
$totalBanners = $bannerController->getBannersCount($search);
$totalPages = ceil($totalBanners / $perPage);

$stats = $bannerController->getBannerStats();
$positions = $bannerController->getAvailablePositions();

// Check for messages
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
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="AdminKit">
    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Banner Management | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .banner-image { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d1fae5; color: #065f46; }
        .status-inactive { background-color: #fee2e2; color: #991b1b; }
        .status-draft { background-color: #fef3c7; color: #92400e; }
        .stats-card { transition: all 0.3s ease; }
        .stats-card:hover { transform: translateY(-2px); }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .pagination .page-link { color: #495057; }
        .pagination .page-item.active .page-link { background-color: #0d6efd; border-color: #0d6efd; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Banner</strong> Management</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="add-banner.php" class="btn btn-primary">Add New Banner</a>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Quick Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?php echo $stats['total'] ?? 0; ?></h4>
                                            <span>Total Banners</span>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-image fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?php echo $stats['active'] ?? 0; ?></h4>
                                            <span>Active</span>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?php echo $stats['scheduled'] ?? 0; ?></h4>
                                            <span>Scheduled</span>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white stats-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?php echo $stats['expired'] ?? 0; ?></h4>
                                            <span>Expired</span>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-calendar-times fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Banners</h5>
                                    <h6 class="card-subtitle text-muted">Manage your website banners and their positions.</h6>
                                    
                                    <!-- Search Bar -->
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <form method="GET" action="" class="d-flex">
                                                <div class="input-group">
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="search" 
                                                           placeholder="Search by title, description, or position..." 
                                                           value="<?php echo htmlspecialchars($search); ?>">
                                                    <button class="btn btn-outline-primary" type="submit">
                                                        <i class="fas fa-search"></i> Search
                                                    </button>
                                                    <?php if (!empty($search)): ?>
                                                        <a href="banner.php" class="btn btn-outline-secondary">
                                                            <i class="fas fa-times"></i> Clear
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <div class="text-muted">
                                                Showing <?php echo count($banners); ?> of <?php echo $totalBanners; ?> banners
                                                <?php if (!empty($search)): ?>
                                                    for "<?php echo htmlspecialchars($search); ?>"
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Images</th>
                                                    <th>Title</th>
                                                    <th>Position</th>
                                                    <th>Target URL</th>
                                                    <th>Order</th>
                                                    <th>Status</th>
                                                    <th>Dates</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($banners)): ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center py-4">
                                                            <div class="text-muted">
                                                                <?php if (!empty($search)): ?>
                                                                    No banners found matching your search criteria.
                                                                <?php else: ?>
                                                                    No banners found. <a href="add-banner.php">Add your first banner</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($banners as $banner): ?>
                                                        <tr>
                                                            <td><?php echo $banner['id']; ?></td>
                                                            <td>
                                                                <div class="d-flex gap-2">
                                                                    <?php if (!empty($banner['image_url_desktop'])): ?>
                                                                        <img src="<?php echo htmlspecialchars($banner['image_url_desktop']); ?>" 
                                                                             alt="Desktop" class="banner-image" title="Desktop">
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($banner['image_url_mobile'])): ?>
                                                                        <img src="<?php echo htmlspecialchars($banner['image_url_mobile']); ?>" 
                                                                             alt="Mobile" class="banner-image" title="Mobile">
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($banner['title']); ?></strong>
                                                                <?php if (!empty($banner['description'])): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($banner['description']); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark">
                                                                    <?php echo htmlspecialchars($positions[$banner['position']] ?? $banner['position']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($banner['target_url'])): ?>
                                                                    <a href="<?php echo htmlspecialchars($banner['target_url']); ?>" 
                                                                       target="_blank" class="text-primary">
                                                                        <?php echo htmlspecialchars($banner['target_url']); ?>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo $banner['display_order']; ?></td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $banner['status']; ?>">
                                                                    <?php echo ucfirst($banner['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php if ($banner['start_date']): ?>
                                                                        From: <?php echo date('M j, Y', strtotime($banner['start_date'])); ?><br>
                                                                    <?php endif; ?>
                                                                    <?php if ($banner['end_date']): ?>
                                                                        To: <?php echo date('M j, Y', strtotime($banner['end_date'])); ?>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="edit-banner.php?id=<?php echo $banner['id']; ?>" 
                                                                   class="btn btn-sm btn-primary" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="delete-banner.php?id=<?php echo $banner['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this banner?')"
                                                                   title="Delete">
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
                                        <nav aria-label="Banner pagination">
                                            <ul class="pagination justify-content-center">
                                                <!-- Previous Page -->
                                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                    <a class="page-link" 
                                                       href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                                       aria-label="Previous">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>

                                                <!-- Page Numbers -->
                                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                    <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                            <a class="page-link" 
                                                               href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                                <?php echo $i; ?>
                                                            </a>
                                                        </li>
                                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                                        <li class="page-item disabled">
                                                            <span class="page-link">...</span>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endfor; ?>

                                                <!-- Next Page -->
                                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                                    <a class="page-link" 
                                                       href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                                       aria-label="Next">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
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