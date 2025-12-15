<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/BannerLayoutController.php';

$layoutController = new BannerLayoutController();
$layouts = $layoutController->getAllLayouts();

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
    <title>Banner Layouts | Printmont</title>
    <!-- Include your CSS and JS files -->
    <style>
        .layout-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .layout-header { display: flex; justify-content: between; align-items: center; margin-bottom: 10px; }
        .layout-badges { display: flex; gap: 5px; }
        .banner-grid { display: grid; gap: 10px; margin-top: 10px; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }
        .banner-item { position: relative; border: 1px solid #ddd; border-radius: 4px; padding: 5px; }
        .banner-item img { width: 100%; height: 80px; object-fit: cover; border-radius: 4px; }
        .empty-slot { background: #f8f9fa; border: 2px dashed #dee2e6; height: 80px; display: flex; align-items: center; justify-content: center; color: #6c757d; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Banner</strong> Layouts</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="add-banner-layout.php" class="btn btn-primary me-2">Add Layout</a>
                            <a href="assign-banners.php" class="btn btn-success">Assign Banners</a>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Banner Layouts</h5>
                                    <h6 class="card-subtitle text-muted">Manage banner layouts for different pages.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($layouts)): ?>
                                        <div class="text-center py-4">
                                            <div class="text-muted">No layouts found. <a href="add-banner-layout.php">Create your first layout</a></div>
                                        </div>
                                    <?php else: ?>
                                        <?php 
                                        $pages = $layoutController->getAvailablePages();
                                        $layoutTypes = $layoutController->getLayoutTypes();
                                        
                                        // Group layouts by page
                                        $layoutsByPage = [];
                                        foreach ($layouts as $layout) {
                                            $layoutsByPage[$layout['page_name']][] = $layout;
                                        }
                                        ?>
                                        
                                        <?php foreach ($layoutsByPage as $pageName => $pageLayouts): ?>
                                            <div class="mb-4">
                                                <h5><?php echo htmlspecialchars($pages[$pageName] ?? $pageName); ?></h5>
                                                <?php foreach ($pageLayouts as $layout): ?>
                                                    <?php 
                                                    $banners = $layoutController->getBannersByLayout($layout['id']);
                                                    $maxBanners = $layout['max_banners'];
                                                    ?>
                                                    <div class="layout-card">
                                                        <div class="layout-header">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($layout['section_name']); ?></strong>
                                                                <div class="layout-badges mt-1">
                                                                    <span class="badge bg-info"><?php echo htmlspecialchars($layoutTypes[$layout['layout_type']]); ?></span>
                                                                    <span class="badge bg-<?php echo $layout['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                                        <?php echo ucfirst($layout['status']); ?>
                                                                    </span>
                                                                    <span class="badge bg-light text-dark">Max: <?php echo $maxBanners; ?> banners</span>
                                                                    <span class="badge bg-light text-dark">Order: <?php echo $layout['display_order']; ?></span>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <a href="edit-banner-layout.php?id=<?php echo $layout['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                                <a href="assign-banners.php?layout_id=<?php echo $layout['id']; ?>" class="btn btn-sm btn-success">Manage Banners</a>
                                                                <a href="delete-banner-layout.php?id=<?php echo $layout['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure? This will remove all banner assignments.')">Delete</a>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="banner-grid <?php echo str_replace('_', '-', $layout['layout_type']); ?>">
                                                            <?php for ($i = 0; $i < $maxBanners; $i++): ?>
                                                                <?php if (isset($banners[$i])): ?>
                                                                    <div class="banner-item">
                                                                        <img src="<?php echo htmlspecialchars($banners[$i]['image_url_desktop']); ?>" 
                                                                             alt="<?php echo htmlspecialchars($banners[$i]['title']); ?>">
                                                                        <small class="d-block text-center"><?php echo htmlspecialchars($banners[$i]['title']); ?></small>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="empty-slot">
                                                                        <small>Empty Slot</small>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
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
</body>
</html>