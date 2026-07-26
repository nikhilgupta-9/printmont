<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CarouselController.php';

$carouselController = new CarouselController();

// Handle delete
if (isset($_GET['delete'])) {
    if ($carouselController->deleteCarousel((int)$_GET['delete'])) {
        $_SESSION['success_message'] = "Carousel deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete carousel.";
    }
    header("Location: carousels.php");
    exit();
}

$carousels = $carouselController->getAllCarousels();

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
    <title>All Carousels | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .color-dot { display: inline-block; width: 16px; height: 16px; border-radius: 50%; border: 1px solid #ccc; vertical-align: middle; }
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
                            <h3><strong>All</strong> Carousels</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="add-carousel.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Carousel
                            </a>
                        </div>
                    </div>

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

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Carousels</h5>
                                    <h6 class="card-subtitle text-muted">Manage product carousels.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($carousels)): ?>
                                        <div class="text-center py-4">
                                            <i data-feather="columns" style="width:48px;height:48px;" class="text-muted mb-3"></i>
                                            <h5>No Records Found</h5>
                                            <p class="text-muted">No carousels yet. Click "Add Carousel" to create one.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover my-0">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Title</th>
                                                        <th>Slug</th>
                                                        <th>Desktop Design</th>
                                                        <th>Mobile Design</th>
                                                        <th>BG Color</th>
                                                        <th>Sort</th>
                                                        <th>Status</th>
                                                        <th class="text-end">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($carousels as $i => $c): ?>
                                                        <tr>
                                                            <td><?php echo $i + 1; ?></td>
                                                            <td><?php echo htmlspecialchars($c['title']); ?></td>
                                                            <td><?php echo htmlspecialchars($c['slug']); ?></td>
                                                            <td><?php echo htmlspecialchars($c['desktop_home_design'] ?: '-'); ?></td>
                                                            <td><?php echo htmlspecialchars($c['mobile_home_design'] ?: '-'); ?></td>
                                                            <td>
                                                                <?php if (!empty($c['desktop_bg_color'])): ?>
                                                                    <span class="color-dot" style="background:<?php echo htmlspecialchars($c['desktop_bg_color']); ?>"></span>
                                                                    <?php echo htmlspecialchars($c['desktop_bg_color']); ?>
                                                                <?php else: ?>-<?php endif; ?>
                                                            </td>
                                                            <td><?php echo (int)$c['desktop_sort_order']; ?></td>
                                                            <td>
                                                                <?php $st = $c['status'] ?? 'active'; ?>
                                                                <span class="status-badge status-<?php echo $st === 'active' ? 'active' : 'inactive'; ?>">
                                                                    <?php echo ucfirst($st); ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-end action-buttons">
                                                                <a href="edit-carousel.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="carousels.php?delete=<?php echo (int)$c['id']; ?>" class="btn btn-sm btn-danger"
                                                                   title="Delete" onclick="return confirm('Delete this carousel?');">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
