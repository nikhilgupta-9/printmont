<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/HomeSettingsController.php';

$homeController = new HomeSettingsController();
$sliders = $homeController->getAllSliders();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    switch ($_GET['action']) {
        case 'delete':
            if ($homeController->deleteSlider($id)) {
                $_SESSION['success_message'] = "Slider deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete slider.";
            }
            break;
            
        case 'toggle_status':
            $slider = $homeController->getSliderById($id);
            if ($slider) {
                $newStatus = $slider['status'] == 'active' ? 'inactive' : 'active';
                $data = ['status' => $newStatus];
                if ($homeController->updateSlider($id, $data)) {
                    $_SESSION['success_message'] = "Slider status updated!";
                }
            }
            break;
    }
    
    header("Location: slider.php");
    exit;
}

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
    <title>Home Slider | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .slider-image { width: 100px; height: 60px; object-fit: cover; border-radius: 4px; }
        .position-badge { background-color: #e9ecef; color: #495057; padding: 4px 8px; border-radius: 12px; font-size: 12px; }
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
                            <h3><strong>Home</strong> Slider</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="add-slider.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Slider
                            </a>
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

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Slider Management</h5>
                                    <h6 class="card-subtitle text-muted">Manage your homepage slider images and content.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($sliders)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-images fa-3x text-muted mb-3"></i>
                                            <h5>No Sliders</h5>
                                            <p class="text-muted">Get started by adding your first slider image.</p>
                                            <a href="add-slider.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Slider
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Image</th>
                                                        <th>Title</th>
                                                        <th>Text Position</th>
                                                        <th>Button</th>
                                                        <th>Order</th>
                                                        <th>Status</th>
                                                        <th>Date Range</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($sliders as $slider): ?>
                                                        <tr>
                                                            <td>
                                                                <img src="<?php echo htmlspecialchars($slider['image']); ?>" 
                                                                     class="slider-image" 
                                                                     alt="<?php echo htmlspecialchars($slider['title']); ?>">
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($slider['title']); ?></strong>
                                                                <?php if ($slider['subtitle']): ?>
                                                                    <br>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($slider['subtitle']); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="position-badge">
                                                                    <?php echo ucfirst($slider['text_position']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($slider['button_text']): ?>
                                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($slider['button_text']); ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No button</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary"><?php echo $slider['order_number']; ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $slider['status']; ?>">
                                                                    <?php echo ucfirst($slider['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php 
                                                                    if ($slider['start_date'] && $slider['end_date']) {
                                                                        echo date('M j, Y', strtotime($slider['start_date'])) . ' - ' . date('M j, Y', strtotime($slider['end_date']));
                                                                    } else {
                                                                        echo 'Always active';
                                                                    }
                                                                    ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="edit-slider.php?id=<?php echo $slider['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="slider.php?action=toggle_status&id=<?php echo $slider['id']; ?>" 
                                                                   class="btn btn-sm btn-<?php echo $slider['status'] == 'active' ? 'secondary' : 'success'; ?>"
                                                                   title="<?php echo $slider['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                                   <i class="fas fa-<?php echo $slider['status'] == 'active' ? 'eye-slash' : 'eye'; ?>"></i>
                                                                </a>
                                                                <a href="slider.php?action=delete&id=<?php echo $slider['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this slider?')"
                                                                   title="Delete">
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