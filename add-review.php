<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/AuthController.php');
require_once(__DIR__ . '/controllers/ReviewController.php');

$database = new Database();
$db = $database->getConnection();

$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$reviewController = new ReviewController();

// Fetch products for dropdown
$products_stmt = $db->query("SELECT id, name, sku FROM products ORDER BY name ASC");
$products = [];
if ($products_stmt) {
    while ($p = $products_stmt->fetch_assoc()) {
        $products[] = $p;
    }
}

// Handle Form Submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $occasion = trim($_POST['occasion'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $status = $_POST['status'] ?? 'approved';
    $isVerified = isset($_POST['is_verified']) ? 1 : 0;

    // Handle Multiple Image Uploads from dynamic fields
    $uploaded_images = [];
    $upload_dir = __DIR__ . '/uploads/reviews/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!empty($_FILES['images']['name'])) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        // Handle array of files
        if (is_array($_FILES['images']['name'])) {
            $total_files = count($_FILES['images']['name']);
            for ($i = 0; $i < $total_files; $i++) {
                $file_name = $_FILES['images']['name'][$i];
                $file_tmp = $_FILES['images']['tmp_name'][$i];
                $file_error = $_FILES['images']['error'][$i];
                $file_size = $_FILES['images']['size'][$i];

                if ($file_error === UPLOAD_ERR_OK && $file_size > 0) {
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed_exts)) {
                        $new_filename = 'review_' . time() . '_' . uniqid() . '.' . $ext;
                        $target_path = $upload_dir . $new_filename;
                        if (move_uploaded_file($file_tmp, $target_path)) {
                            $uploaded_images[] = 'uploads/reviews/' . $new_filename;
                        }
                    }
                }
            }
        }
    }

    if ($productId <= 0) {
        $error_message = 'Please select a valid product.';
    } elseif (empty($customerName)) {
        $error_message = 'Please enter reviewer name.';
    } elseif ($rating < 1 || $rating > 5) {
        $error_message = 'Rating must be between 1 and 5.';
    } elseif (empty($comment)) {
        $error_message = 'Please enter review comment/text.';
    } else {
        $reviewData = [
            'product_id' => $productId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'rating' => $rating,
            'title' => $title ?: substr($comment, 0, 40),
            'comment' => $comment,
            'occasion' => $occasion,
            'city' => $city,
            'status' => $status,
            'is_verified' => $isVerified,
            'images' => $uploaded_images
        ];

        $inserted = $reviewController->createReview($reviewData);
        if ($inserted) {
            $_SESSION['success_message'] = 'Review added successfully with ' . count($uploaded_images) . ' image(s)!';
            header('Location: reviews.php');
            exit();
        } else {
            $error_message = 'Failed to create review. Please try again.';
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
    <title>Add Product Review | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .form-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            padding: 1.75rem;
        }
        .image-repeater-wrapper {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
        }
        .image-row-item {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.2s ease;
        }
        .image-row-item:hover {
            border-color: #94a3b8;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .image-row-preview {
            width: 64px;
            height: 64px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .image-row-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-row-preview i {
            color: #94a3b8;
            font-size: 1.5rem;
        }
        .image-row-controls {
            flex-grow: 1;
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
                    
                    <!-- Header -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800"><strong>Add</strong> Review</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0" style="font-size: 0.85rem;">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="reviews.php">Reviews</a></li>
                                    <li class="breadcrumb-item active">Add Review</li>
                                </ol>
                            </nav>
                        </div>
                        <div>
                            <a href="reviews.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Reviews
                            </a>
                        </div>
                    </div>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12 col-xl-8">
                            <div class="form-card">
                                <form method="POST" action="add-review.php" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Select Product <span class="text-danger">*</span></label>
                                        <select name="product_id" class="form-select form-select-lg" required>
                                            <option value="">-- Choose Product --</option>
                                            <?php foreach ($products as $p): ?>
                                                <option value="<?php echo $p['id']; ?>" <?php echo (isset($_POST['product_id']) && $_POST['product_id'] == $p['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($p['name']); ?> <?php echo !empty($p['sku']) ? '(' . htmlspecialchars($p['sku']) . ')' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Reviewer Name <span class="text-danger">*</span></label>
                                            <input type="text" name="customer_name" class="form-control" placeholder="e.g. Ankur Priyadarshi" value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Reviewer Email (Optional)</label>
                                            <input type="email" name="customer_email" class="form-control" placeholder="e.g. ankur@example.com" value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Rating <span class="text-danger">*</span></label>
                                            <select name="rating" class="form-select" required>
                                                <option value="5" <?php echo (($_POST['rating'] ?? '5') == '5') ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ (5 Stars)</option>
                                                <option value="4" <?php echo (($_POST['rating'] ?? '') == '4') ? 'selected' : ''; ?>>⭐⭐⭐⭐ (4 Stars)</option>
                                                <option value="3" <?php echo (($_POST['rating'] ?? '') == '3') ? 'selected' : ''; ?>>⭐⭐⭐ (3 Stars)</option>
                                                <option value="2" <?php echo (($_POST['rating'] ?? '') == '2') ? 'selected' : ''; ?>>⭐⭐ (2 Stars)</option>
                                                <option value="1" <?php echo (($_POST['rating'] ?? '') == '1') ? 'selected' : ''; ?>>⭐ (1 Star)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">Occasion (e.g. Birthday)</label>
                                            <input type="text" name="occasion" class="form-control" placeholder="e.g. Birthday, Anniversary, Corporate" value="<?php echo htmlspecialchars($_POST['occasion'] ?? 'Birthday'); ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-bold">City (e.g. Delhi)</label>
                                            <input type="text" name="city" class="form-control" placeholder="e.g. Delhi, Mumbai, Bengaluru" value="<?php echo htmlspecialchars($_POST['city'] ?? 'Delhi'); ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Review Title / Headline</label>
                                        <input type="text" name="title" class="form-control" placeholder="e.g. Prompt delivery & amazing print clarity" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Review Comment <span class="text-danger">*</span></label>
                                        <textarea name="comment" class="form-control" rows="4" placeholder="Write full review text here..." required><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                                    </div>

                                    <!-- Dynamic Multiple Images Section with Add More & Remove -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <label class="form-label fw-bold mb-0">
                                                <i class="fas fa-camera text-primary me-1"></i> Customer Product Photos (Multiple Images)
                                            </label>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_add_image_top" onclick="addNewImageRow()">
                                                <i class="fas fa-plus me-1"></i> Add One More Image
                                            </button>
                                        </div>
                                        
                                        <div class="image-repeater-wrapper">
                                            <div id="image_rows_container">
                                                <!-- Image Row 1 -->
                                                <div class="image-row-item" id="image_row_0">
                                                    <div class="image-row-preview" id="preview_box_0">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                    <div class="image-row-controls">
                                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                                            <span class="small fw-bold text-secondary image-row-label">Image #1</span>
                                                        </div>
                                                        <input type="file" name="images[]" class="form-control form-control-sm" accept="image/*" onchange="previewImageRow(this, 0)">
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeImageRow(0)" title="Remove this image">
                                                            <i class="fas fa-trash-alt me-1"></i> Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                                                <span class="text-muted small">
                                                    <i class="fas fa-info-circle me-1"></i> Supports JPG, PNG, WEBP, GIF. You can add as many images as needed.
                                                </span>
                                                <button type="button" class="btn btn-primary btn-sm" onclick="addNewImageRow()">
                                                    <i class="fas fa-plus me-1"></i> Add One More Image
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row align-items-center mb-4">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label fw-bold">Moderation Status</label>
                                            <select name="status" class="form-select">
                                                <option value="approved" <?php echo (($_POST['status'] ?? 'approved') === 'approved') ? 'selected' : ''; ?>>Approved (Live on website)</option>
                                                <option value="pending" <?php echo (($_POST['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending (Needs approval)</option>
                                                <option value="rejected" <?php echo (($_POST['status'] ?? '') === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check mt-3 pt-1">
                                                <input class="form-check-input" type="checkbox" name="is_verified" id="is_verified" value="1" <?php echo (!isset($_POST['is_verified']) || $_POST['is_verified']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold" for="is_verified">
                                                    Mark as Verified Buyer
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" name="submit_review" class="btn btn-primary px-4 py-2">
                                            <i class="fas fa-check-circle me-1"></i> Save Review
                                        </button>
                                        <a href="reviews.php" class="btn btn-light px-4 py-2">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="col-12 col-xl-4">
                            <div class="card border-0 shadow-sm rounded-3">
                                <div class="card-header bg-light py-3">
                                    <h5 class="card-title mb-0 fw-bold"><i class="fas fa-info-circle text-primary me-2"></i>Review Workflow</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted" style="font-size: 0.9rem;">
                                        <strong>Multiple Images:</strong> Click <strong>"Add One More Image"</strong> to upload additional product pictures. You can remove any image using the <strong>"Remove"</strong> button.
                                    </p>
                                    <p class="text-muted" style="font-size: 0.9rem;">
                                        <strong>User Login Requirement:</strong> On the customer frontend, only logged-in users are permitted to submit reviews.
                                    </p>
                                    <p class="text-muted mb-0" style="font-size: 0.9rem;">
                                        <strong>Moderation Queue:</strong> Frontend submissions enter <code>pending</code> status until approved by an admin.
                                    </p>
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
    <script>
        let imageRowCount = 1;

        function updateImageRowLabels() {
            const container = document.getElementById('image_rows_container');
            const items = container.getElementsByClassName('image-row-item');
            for (let i = 0; i < items.length; i++) {
                const label = items[i].querySelector('.image-row-label');
                if (label) {
                    label.textContent = `Image #${i + 1}`;
                }
            }
        }

        function addNewImageRow() {
            const container = document.getElementById('image_rows_container');
            const rowId = imageRowCount++;

            const newRow = document.createElement('div');
            newRow.className = 'image-row-item';
            newRow.id = `image_row_${rowId}`;
            newRow.innerHTML = `
                <div class="image-row-preview" id="preview_box_${rowId}">
                    <i class="fas fa-image"></i>
                </div>
                <div class="image-row-controls">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="small fw-bold text-secondary image-row-label">Image #${container.children.length + 1}</span>
                    </div>
                    <input type="file" name="images[]" class="form-control form-control-sm" accept="image/*" onchange="previewImageRow(this, ${rowId})">
                </div>
                <div>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeImageRow(${rowId})" title="Remove this image">
                        <i class="fas fa-trash-alt me-1"></i> Remove
                    </button>
                </div>
            `;

            container.appendChild(newRow);
            updateImageRowLabels();
        }

        function removeImageRow(rowId) {
            const container = document.getElementById('image_rows_container');
            const row = document.getElementById(`image_row_${rowId}`);
            
            if (row) {
                // If it's the only row, just clear it instead of removing
                if (container.children.length === 1) {
                    const input = row.querySelector('input[type="file"]');
                    if (input) input.value = '';
                    const previewBox = document.getElementById(`preview_box_${rowId}`);
                    if (previewBox) {
                        previewBox.innerHTML = '<i class="fas fa-image"></i>';
                    }
                } else {
                    row.remove();
                    updateImageRowLabels();
                }
            }
        }

        function previewImageRow(input, rowId) {
            const previewBox = document.getElementById(`preview_box_${rowId}`);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (previewBox) {
                        previewBox.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    }
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                if (previewBox) {
                    previewBox.innerHTML = '<i class="fas fa-image"></i>';
                }
            }
        }
    </script>
</body>
</html>
