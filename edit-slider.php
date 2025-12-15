<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/HomeSettingsController.php';

$homeController = new HomeSettingsController();
$sliderId = $_GET['id'] ?? 0;
$slider = $homeController->getSliderById($sliderId);

if (!$slider) {
    $_SESSION['error_message'] = "Slider not found!";
    header("Location: slider.php");
    exit;
}

// Handle form submission
if ($_POST) {
    if (isset($_POST['update_slider'])) {
        $data = [
            'title' => $_POST['title'],
            'subtitle' => $_POST['subtitle'],
            'description' => $_POST['description'],
            'image' => $_POST['image'],
            'button_text' => $_POST['button_text'],
            'button_url' => $_POST['button_url'],
            'text_position' => $_POST['text_position'],
            'text_color' => $_POST['text_color'],
            'overlay_opacity' => $_POST['overlay_opacity'],
            'order_number' => $_POST['order_number'],
            'status' => $_POST['status'],
            'start_date' => $_POST['start_date'] ?: null,
            'end_date' => $_POST['end_date'] ?: null
        ];
        
        try {
            // Validate image URL
            $homeController->validateImage($data['image']);
            
            // Validate dates
            $homeController->validateDates($data['start_date'], $data['end_date']);
            
            // Validate color
            $homeController->validateColor($data['text_color']);
            
            if ($homeController->updateSlider($sliderId, $data)) {
                $_SESSION['success_message'] = "Slider updated successfully!";
                header("Location: slider.php");
                exit;
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? $error_message ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Slider | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .image-preview { max-width: 300px; max-height: 200px; margin-top: 10px; border-radius: 8px; }
        .color-preview { width: 30px; height: 30px; border-radius: 4px; display: inline-block; margin-left: 10px; border: 1px solid #ddd; }
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
                            <h3><strong>Edit</strong> Slider</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="slider.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Sliders
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
                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Edit Slider</h5>
                                    <h6 class="card-subtitle text-muted">Update slider information.</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Current Image Preview -->
                                    <div class="text-center mb-4">
                                        <img src="<?php echo htmlspecialchars($slider['image']); ?>" 
                                             class="image-preview" 
                                             alt="Current slider image">
                                        <p class="text-muted mt-2">Current Image</p>
                                    </div>

                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Title *</label>
                                                <input type="text" class="form-control" name="title" 
                                                       value="<?php echo htmlspecialchars($slider['title']); ?>" 
                                                       placeholder="Main heading" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Subtitle</label>
                                                <input type="text" class="form-control" name="subtitle" 
                                                       value="<?php echo htmlspecialchars($slider['subtitle'] ?? ''); ?>" 
                                                       placeholder="Subheading (optional)">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="3" 
                                                      placeholder="Detailed description (optional)"><?php echo htmlspecialchars($slider['description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Image URL *</label>
                                            <input type="url" class="form-control" name="image" id="image" 
                                                   value="<?php echo htmlspecialchars($slider['image']); ?>" 
                                                   placeholder="https://example.com/slider-image.jpg" required>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="openMediaManager()">
                                                <i class="fas fa-images"></i> Choose Image
                                            </button>
                                            <div class="image-preview-container mt-2">
                                                <img id="imagePreview" class="image-preview" src="<?php echo htmlspecialchars($slider['image']); ?>" alt="Preview">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Button Text</label>
                                                <input type="text" class="form-control" name="button_text" 
                                                       value="<?php echo htmlspecialchars($slider['button_text'] ?? ''); ?>" 
                                                       placeholder="Button text">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Button URL</label>
                                                <input type="url" class="form-control" name="button_url" 
                                                       value="<?php echo htmlspecialchars($slider['button_url'] ?? ''); ?>" 
                                                       placeholder="https://example.com">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Text Position</label>
                                                <select class="form-select" name="text_position">
                                                    <option value="left" <?php echo $slider['text_position'] == 'left' ? 'selected' : ''; ?>>Left</option>
                                                    <option value="center" <?php echo $slider['text_position'] == 'center' ? 'selected' : ''; ?>>Center</option>
                                                    <option value="right" <?php echo $slider['text_position'] == 'right' ? 'selected' : ''; ?>>Right</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Text Color</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="text_color" id="text_color"
                                                           value="<?php echo htmlspecialchars($slider['text_color']); ?>" 
                                                           placeholder="#ffffff">
                                                    <span class="input-group-text color-preview" id="colorPreview" 
                                                          style="background-color: <?php echo htmlspecialchars($slider['text_color']); ?>"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Overlay Opacity</label>
                                                <input type="number" class="form-control" name="overlay_opacity" 
                                                       value="<?php echo htmlspecialchars($slider['overlay_opacity']); ?>" 
                                                       min="0" max="1" step="0.1" placeholder="0.3">
                                                <small class="form-text text-muted">0 (transparent) to 1 (opaque)</small>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Order Number</label>
                                                <input type="number" class="form-control" name="order_number" 
                                                       value="<?php echo htmlspecialchars($slider['order_number']); ?>" 
                                                       min="1" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status">
                                                    <option value="active" <?php echo $slider['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $slider['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Start Date</label>
                                                <input type="date" class="form-control" name="start_date" 
                                                       value="<?php echo $slider['start_date'] ? date('Y-m-d', strtotime($slider['start_date'])) : ''; ?>">
                                                <small class="form-text text-muted">Leave empty for immediate start</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">End Date</label>
                                                <input type="date" class="form-control" name="end_date" 
                                                       value="<?php echo $slider['end_date'] ? date('Y-m-d', strtotime($slider['end_date'])) : ''; ?>">
                                                <small class="form-text text-muted">Leave empty for no end date</small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <button type="submit" name="update_slider" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Slider
                                            </button>
                                            <a href="slider.php" class="btn btn-secondary">Cancel</a>
                                            <a href="slider.php?action=delete&id=<?php echo $slider['id']; ?>" 
                                               class="btn btn-danger float-end" 
                                               onclick="return confirm('Are you sure you want to delete this slider?')">
                                                <i class="fas fa-trash"></i> Delete Slider
                                            </a>
                                        </div>
                                    </form>
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
    <script>
        // Image preview
        document.getElementById('image').addEventListener('input', function() {
            const preview = document.getElementById('imagePreview');
            if (this.value) {
                preview.src = this.value;
            }
        });

        // Color preview
        document.getElementById('text_color').addEventListener('input', function() {
            document.getElementById('colorPreview').style.backgroundColor = this.value;
        });

        // Media manager function (placeholder)
        function openMediaManager() {
            alert('Media manager would open here. In a real implementation, this would allow you to select or upload images.');
        }
    </script>
</body>
</html>