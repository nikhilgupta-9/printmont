<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/BannerLayoutController.php';

$bannerController = new BannerLayoutController();
$banners = $bannerController->getAllBanners();

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
    <title>Banner Management | Printmont</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

	<link class="js-stylesheet" href="css/light.css" rel="stylesheet">
	<script src="js/settings.js"></script>
	<style>
		body {
			opacity: 0;
		}
	</style>
	<!-- END SETTINGS -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-120946860-10"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag() { dataLayer.push(arguments); }
		gtag('js', new Date());

		gtag('config', 'UA-120946860-10', { 'anonymize_ip': true });
	</script>
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
                                    <h5 class="card-title">All Banners</h5>
                                    <h6 class="card-subtitle text-muted">Manage banners for different pages.</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Preview</th>
                                                    <th>Title</th>
                                                    <th>Type</th>
                                                    <th>Display Order</th>
                                                    <th>Status</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($banners)): ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center py-4">
                                                            <div class="text-muted">No banners found. <a href="add-banner.php">Add your first banner</a></div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($banners as $banner): ?>
                                                        <tr>
                                                            <td>
                                                                <img src="<?php echo htmlspecialchars($banner['image_url_desktop']); ?>" 
                                                                     alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                                                                     style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($banner['title']); ?></strong>
                                                                <?php if (!empty($banner['description'])): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($banner['description']); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                $bannerTypes = $bannerController->getBannerTypes();
                                                                echo htmlspecialchars($bannerTypes[$banner['banner_type']] ?? $banner['banner_type']); 
                                                                ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($banner['display_order']); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $banner['status'] == 'active' ? 'success' : ($banner['status'] == 'scheduled' ? 'warning' : 'secondary'); ?>">
                                                                    <?php echo ucfirst($banner['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo $banner['start_date'] ? date('M j, Y', strtotime($banner['start_date'])) : 'Immediate'; ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo $banner['end_date'] ? date('M j, Y', strtotime($banner['end_date'])) : 'No end'; ?>
                                                                </small>
                                                            </td>
                                                            <td>
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