<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

$bannerController = new BannerController();

// Handle status toggling or deletion
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $type = $_GET['type'] ?? 'banner';

    if ($_GET['action'] === 'activate') {
        if ($type === 'carousel') {
            $bannerController->updateBannerCarousel($id, ['status' => 'active', 'desktop_status' => 'active', 'mobile_status' => 'active'], []);
        } else {
            $bannerController->toggleBannerStatus($id, 'active');
        }
        $_SESSION['success_message'] = 'Banner activated successfully!';
        header('Location: deactivated-banners.php');
        exit;
    } elseif ($_GET['action'] === 'delete') {
        if ($type === 'carousel') {
            $bannerController->deleteBannerCarousel($id);
        } else {
            $bannerController->deleteBanner($id);
        }
        $_SESSION['success_message'] = 'Banner permanently deleted!';
        header('Location: deactivated-banners.php');
        exit;
    }
}

$deactivatedBanners = $bannerController->getDeactivatedBanners();

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
    <title>Deactivated Banners | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; background-color: #f1f5f9; }
        .admin-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 24px;
            overflow: hidden;
        }
        .banner-thumb {
            width: 70px; height: 48px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0;
        }
        .status-badge-inactive {
            background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;
            padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 4px;
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

                <div class="row mb-3 align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-ban text-danger me-2"></i>Deactivated Banners
                        </h3>
                        <p class="text-muted small mb-0">Hidden or paused banners. You can easily re-activate them anytime.</p>
                    </div>
                    <div class="col-md-6 text-md-end mt-2 mt-md-0">
                        <a href="view-banner.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> All Active Banners
                        </a>
                        <a href="add-banner.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add New Banner
                        </a>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="admin-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 70px;">ID</th>
                                    <th>Preview</th>
                                    <th>Banner Title</th>
                                    <th>Format Design</th>
                                    <th>Placement</th>
                                    <th>Status</th>
                                    <th>Sort Order</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($deactivatedBanners)): ?>
                                    <?php foreach ($deactivatedBanners as $b): 
                                        $dImg = $b['desktop_image'] ?: $b['image_url_desktop'];
                                    ?>
                                        <tr>
                                            <td class="fw-bold text-muted">#<?php echo $b['id']; ?></td>
                                            <td>
                                                <?php if ($dImg): ?>
                                                    <img src="<?php echo htmlspecialchars($dImg); ?>" class="banner-thumb" alt="Thumb" onerror="this.src='img/icons/icon-48x48.png'">
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border p-2">No Image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($b['title']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($b['desktop_url'] ?: $b['target_url'] ?: 'No URL'); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $b['desktop_design'] ?: $b['layout_format'] ?: 'Format 1'))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo htmlspecialchars($b['section_label'] ?: $b['position'] ?: 'Home'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge-inactive">
                                                    <i class="fas fa-eye-slash"></i> Hidden / Inactive
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-muted"><?php echo (int)($b['desktop_sort_order'] ?: $b['display_order']); ?></span>
                                            </td>
                                            <td class="text-end">
                                                <a href="deactivated-banners.php?action=activate&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-success me-1" title="Activate &amp; Show Banner">
                                                    <i class="fas fa-check-circle me-1"></i> Activate
                                                </a>
                                                <a href="edit-banner.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="deactivated-banners.php?action=delete&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete Permanently" onclick="return confirm('Are you sure you want to permanently delete this banner?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                                            <h5 class="fw-bold text-dark">No Deactivated Banners</h5>
                                            <p class="text-muted mb-0">All your banners and carousels are currently active and visible.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
