<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/HomePageTopSliderController.php';

$controller = new HomePageTopSliderController();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    switch ($_GET['action']) {
        case 'delete':
            if ($controller->deleteSlider($id)) {
                $_SESSION['success_message'] = "Top Slider deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete top slider.";
            }
            break;
            
        case 'toggle_status':
            if ($controller->toggleStatus($id)) {
                $_SESSION['success_message'] = "Slider status updated!";
            } else {
                $_SESSION['error_message'] = "Failed to update slider status.";
            }
            break;
    }
    
    header("Location: home-page-top-slider.php");
    exit;
}

$sliders = $controller->getAllSliders();

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
    <title>Home Page Top Slider | Printmont</title>
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="icon" type="image/png" href="img/icons/icon-48x48.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .status-active { background-color: #d1fae5; color: #065f46; }
        .status-inactive { background-color: #fee2e2; color: #991b1b; }
        .slider-thumb { width: 90px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .format-badge { font-size: 11px; padding: 2px 6px; border-radius: 4px; background: #eff6ff; color: #1d4ed8; font-weight: 600; }
        .table > tbody > tr > td { vertical-align: middle; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-3 align-items-center">
                        <div class="col">
                            <h1 class="h3 mb-0 text-gray-800 fw-bold">Home Page Top Slider</h1>
                            <p class="text-muted mb-0">Manage top hero banner slider for Desktop & Mobile homepage.</p>
                        </div>
                        <div class="col-auto">
                            <a href="add-home-page-top-slider.php" class="btn btn-primary shadow-sm">
                                <i class="fas fa-plus me-1"></i> Add Top Slider
                            </a>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold"><i class="fas fa-images text-primary me-2"></i> Active Top Sliders</h5>
                            <span class="badge bg-primary rounded-pill"><?php echo count($sliders); ?> Slides</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($sliders)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                    <h5>No Top Sliders Found</h5>
                                    <p class="text-muted">Click "Add Top Slider" to upload your first desktop & mobile hero slider.</p>
                                    <a href="add-home-page-top-slider.php" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus me-1"></i> Add Slider
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;">#</th>
                                                <th>Title Slider Name</th>
                                                <th>Desktop Slide</th>
                                                <th>Mobile Slide</th>
                                                <th>URL Target</th>
                                                <th>Auto Time</th>
                                                <th>Schedule</th>
                                                <th>Status</th>
                                                <th class="text-end" style="width: 130px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sliders as $index => $slider): ?>
                                                <?php 
                                                    $dImg = $slider['desktop_image'] ? (strpos($slider['desktop_image'], 'http') === 0 ? $slider['desktop_image'] : $slider['desktop_image']) : 'img/placeholder.png';
                                                    $mImg = $slider['mobile_image'] ? (strpos($slider['mobile_image'], 'http') === 0 ? $slider['mobile_image'] : $slider['mobile_image']) : $dImg;
                                                ?>
                                                <tr>
                                                    <td class="fw-bold text-muted"><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($slider['title'] ?: 'Untitled Slider'); ?></div>
                                                        <small class="text-muted">ID: #<?php echo $slider['id']; ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="<?php echo htmlspecialchars($dImg); ?>" alt="Desktop Slide" class="slider-thumb" onerror="this.src='img/placeholder.png';">
                                                            <div>
                                                                <span class="format-badge"><?php echo htmlspecialchars($slider['desktop_design'] ?: 'Format 1'); ?></span>
                                                                <div class="small text-muted mt-0.5">Order: <strong><?php echo (int)$slider['desktop_sort_order']; ?></strong></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="<?php echo htmlspecialchars($mImg); ?>" alt="Mobile Slide" class="slider-thumb" onerror="this.src='img/placeholder.png';">
                                                            <div>
                                                                <span class="format-badge" style="background:#fef3c7; color:#b45309;"><?php echo htmlspecialchars($slider['mobile_design'] ?: 'Design 1'); ?></span>
                                                                <div class="small text-muted mt-0.5">Order: <strong><?php echo (int)$slider['mobile_sort_order']; ?></strong></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 160px;" title="<?php echo htmlspecialchars($slider['desktop_url'] ?: $slider['mobile_url'] ?: '#'); ?>">
                                                            <a href="<?php echo htmlspecialchars($slider['desktop_url'] ?: $slider['mobile_url'] ?: '#'); ?>" target="_blank" class="text-primary text-decoration-none">
                                                                <i class="fas fa-external-link-alt fa-xs me-1"></i><?php echo htmlspecialchars($slider['desktop_url'] ?: $slider['mobile_url'] ?: 'No link'); ?>
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo round(($slider['auto_play_speed'] ?: 3000) / 1000, 1); ?>s</span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($slider['start_date']) || !empty($slider['end_date'])): ?>
                                                            <div class="small text-muted">
                                                                <div><i class="fas fa-calendar-alt fa-xs me-1"></i><?php echo $slider['start_date'] ?: 'Always'; ?></div>
                                                                <div><i class="fas fa-calendar-check fa-xs me-1"></i><?php echo $slider['end_date'] ?: 'Forever'; ?></div>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted small">Always Active</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="home-page-top-slider.php?action=toggle_status&id=<?php echo $slider['id']; ?>" 
                                                           class="status-badge <?php echo $slider['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>"
                                                           title="Click to toggle status">
                                                            <i class="fas fa-<?php echo $slider['status'] === 'active' ? 'eye' : 'eye-slash'; ?>"></i>
                                                            <?php echo ucfirst($slider['status']); ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="edit-home-page-top-slider.php?id=<?php echo $slider['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="home-page-top-slider.php?action=delete&id=<?php echo $slider['id']; ?>" 
                                                           class="btn btn-sm btn-outline-danger" 
                                                           onclick="return confirm('Are you sure you want to delete this slider?');" 
                                                           title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
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
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>
    <script src="js/app.js"></script>
</body>
</html>
