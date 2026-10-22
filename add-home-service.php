<?php
/**
 * Add / Edit Home Service Banner — Printmont Admin
 * 
 * Manage feature/service highlight cards on Home Page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id > 0;
$error = '';
$success = '';

$item = [
    'title'            => '',
    'description'      => '',
    'icon'             => 'fas fa-truck-fast',
    'image'            => '',
    'desktop_image'    => '',
    'mobile_image'     => '',
    'button_text'      => 'Learn More',
    'button_url'       => '/about-us',
    'background_color' => '#f8f9fa',
    'text_color'       => '#212529',
    'order_number'     => 0,
    'status'           => 'active',
    'desktop_status'   => 'active',
    'mobile_status'    => 'active'
];

if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM home_services WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $item = array_merge($item, $row);
    } else {
        $_SESSION['error_message'] = "Service #$id not found.";
        header('Location: home-services.php');
        exit();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title            = trim($_POST['title'] ?? '');
    $description      = trim($_POST['description'] ?? '');
    $icon             = trim($_POST['icon'] ?? 'fas fa-truck-fast');
    $button_text      = trim($_POST['button_text'] ?? '');
    $button_url       = trim($_POST['button_url'] ?? '');
    $background_color = trim($_POST['background_color'] ?? '#f8f9fa');
    $text_color       = trim($_POST['text_color'] ?? '#212529');
    $order_number     = (int)($_POST['order_number'] ?? 0);
    $status           = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $desktop_status   = ($_POST['desktop_status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $mobile_status    = ($_POST['mobile_status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    $uploadDir = __DIR__ . '/uploads/home-services/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $image = $item['image'];
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
            $fname = 'service_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fname)) {
                $image = 'uploads/home-services/' . $fname;
            }
        }
    }

    if (empty($title)) {
        $error = "Service Title is required.";
    } else {
        if ($is_edit) {
            $stmt = $db->prepare("UPDATE home_services SET 
                title = ?, description = ?, icon = ?, image = ?, desktop_image = ?, mobile_image = ?,
                button_text = ?, button_url = ?, desktop_url = ?, mobile_url = ?,
                background_color = ?, text_color = ?, order_number = ?, desktop_sort_order = ?, mobile_sort_order = ?,
                status = ?, desktop_status = ?, mobile_status = ?, updated_at = NOW()
                WHERE id = ?");

            $stmt->bind_param('ssssssssssssiiisssi',
                $title, $description, $icon, $image, $image, $image,
                $button_text, $button_url, $button_url, $button_url,
                $background_color, $text_color, $order_number, $order_number, $order_number,
                $status, $desktop_status, $mobile_status,
                $id
            );

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Home service updated successfully!";
                header('Location: home-services.php');
                exit();
            } else {
                $error = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $stmt = $db->prepare("INSERT INTO home_services (
                title, description, icon, image, desktop_image, mobile_image,
                button_text, button_url, desktop_url, mobile_url,
                background_color, text_color, order_number, desktop_sort_order, mobile_sort_order,
                status, desktop_status, mobile_status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, NOW()
            )");

            $stmt->bind_param('ssssssssssssiiisss',
                $title, $description, $icon, $image, $image, $image,
                $button_text, $button_url, $button_url, $button_url,
                $background_color, $text_color, $order_number, $order_number, $order_number,
                $status, $desktop_status, $mobile_status
            );

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Home service created successfully!";
                header('Location: home-services.php');
                exit();
            } else {
                $error = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $is_edit ? 'Edit Service' : 'Add Home Service'; ?> | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-3 align-items-center">
                        <div class="col-auto">
                            <h1 class="h3 mb-0"><strong><?php echo $is_edit ? 'Edit' : 'Add'; ?></strong> Home Service</h1>
                            <p class="text-muted small">Configure feature highlight cards shown on the home page.</p>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="home-services.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Services
                            </a>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title fw-bold text-primary mb-3">Service Details</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Service Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" placeholder="e.g. Same Day Express Delivery" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">FontAwesome Icon Class</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i id="iconPreview" class="<?php echo htmlspecialchars($item['icon']); ?>"></i></span>
                                            <input type="text" id="iconInput" name="icon" class="form-control" placeholder="fas fa-shipping-fast" value="<?php echo htmlspecialchars($item['icon']); ?>" oninput="document.getElementById('iconPreview').className = this.value">
                                        </div>
                                        <small class="text-muted">e.g. <code>fas fa-truck-fast</code>, <code>fas fa-award</code>, <code>fas fa-headset</code></small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Sort Order</label>
                                        <input type="number" name="order_number" class="form-control" value="<?php echo (int)$item['order_number']; ?>">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold">Description / Subtitle</label>
                                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the service promise"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Button CTA Text</label>
                                        <input type="text" name="button_text" class="form-control" placeholder="Learn More / Order Now" value="<?php echo htmlspecialchars($item['button_text']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Button URL</label>
                                        <input type="text" name="button_url" class="form-control" placeholder="/services or https://..." value="<?php echo htmlspecialchars($item['button_url']); ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Card Background Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" value="<?php echo htmlspecialchars($item['background_color'] ?: '#f8f9fa'); ?>" onchange="document.getElementById('bg_hex').value = this.value">
                                            <input type="text" id="bg_hex" name="background_color" class="form-control" value="<?php echo htmlspecialchars($item['background_color'] ?: '#f8f9fa'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Text Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" value="<?php echo htmlspecialchars($item['text_color'] ?: '#212529'); ?>" onchange="document.getElementById('text_hex').value = this.value">
                                            <input type="text" id="text_hex" name="text_color" class="form-control" value="<?php echo htmlspecialchars($item['text_color'] ?: '#212529'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Optional Service Image/Banner</label>
                                        <input type="file" name="image" class="form-control" accept="image/*">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" style="max-height: 60px; margin-top: 8px; border-radius: 4px;" alt="Service">
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Master Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" <?php echo $item['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $item['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Desktop Status</label>
                                        <select name="desktop_status" class="form-select">
                                            <option value="active" <?php echo $item['desktop_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $item['desktop_status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Mobile Status</label>
                                        <select name="mobile_status" class="form-select">
                                            <option value="active" <?php echo $item['mobile_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $item['mobile_status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <a href="home-services.php" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-5 fw-bold">
                                <i class="fas fa-save me-1"></i> <?php echo $is_edit ? 'Update Service' : 'Save Service'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>
    <script src="js/app.js"></script>
</body>
</html>
