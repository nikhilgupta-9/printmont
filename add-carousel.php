<?php
/**
 * Add / Edit Product Carousel — Printmont Admin
 * 
 * Manages desktop and mobile product carousel sections on Home and Other pages.
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

// Load categories for dropdowns
$categories = [];
$catRes = $db->query("SELECT id, name, slug FROM categories WHERE status = 'active' ORDER BY name ASC");
if ($catRes) {
    while ($row = $catRes->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Default values
$item = [
    'title'                     => '',
    'slug'                      => '',
    'desktop_home_category_id'  => '',
    'desktop_home_design'       => 'design-1',
    'desktop_bg_color'          => '#ffffff',
    'desktop_bg_image'          => '',
    'desktop_show_other_pages'  => 'no',
    'desktop_other_category_id' => '',
    'desktop_sort_order'        => 0,
    'desktop_status'            => 'active',
    'mobile_home_show'          => 'yes',
    'mobile_home_category_id'   => '',
    'mobile_home_design'        => 'design-1',
    'mobile_bg_color'           => '#ffffff',
    'mobile_bg_image'           => '',
    'mobile_show_other_pages'   => 'no',
    'mobile_other_category_id'  => '',
    'mobile_sort_order'         => 0,
    'mobile_status'             => 'active',
    'meta_title'                => '',
    'meta_keywords'             => '',
    'meta_description'          => '',
    'status'                    => 'active'
];

// If editing, load existing carousel
if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM carousels WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $item = array_merge($item, $row);
    } else {
        $_SESSION['error_message'] = "Carousel #$id not found.";
        header('Location: carousels.php');
        exit();
    }
    $stmt->close();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    }

    $desktop_home_category_id  = !empty($_POST['desktop_home_category_id']) ? (int)$_POST['desktop_home_category_id'] : null;
    $desktop_home_design       = $_POST['desktop_home_design'] ?? 'design-1';
    $desktop_bg_color          = trim($_POST['desktop_bg_color'] ?? '#ffffff');
    $desktop_show_other_pages  = ($_POST['desktop_show_other_pages'] ?? 'no') === 'yes' ? 'yes' : 'no';
    $desktop_other_category_id = !empty($_POST['desktop_other_category_id']) ? (int)$_POST['desktop_other_category_id'] : null;
    $desktop_sort_order        = (int)($_POST['desktop_sort_order'] ?? 0);
    $desktop_status            = ($_POST['desktop_status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    $mobile_home_show          = ($_POST['mobile_home_show'] ?? 'yes') === 'no' ? 'no' : 'yes';
    $mobile_home_category_id   = !empty($_POST['mobile_home_category_id']) ? (int)$_POST['mobile_home_category_id'] : null;
    $mobile_home_design        = $_POST['mobile_home_design'] ?? 'design-1';
    $mobile_bg_color           = trim($_POST['mobile_bg_color'] ?? '#ffffff');
    $mobile_show_other_pages   = ($_POST['mobile_show_other_pages'] ?? 'no') === 'yes' ? 'yes' : 'no';
    $mobile_other_category_id  = !empty($_POST['mobile_other_category_id']) ? (int)$_POST['mobile_other_category_id'] : null;
    $mobile_sort_order         = (int)($_POST['mobile_sort_order'] ?? 0);
    $mobile_status             = ($_POST['mobile_status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    $meta_title                = trim($_POST['meta_title'] ?? '');
    $meta_keywords             = trim($_POST['meta_keywords'] ?? '');
    $meta_description          = trim($_POST['meta_description'] ?? '');
    $status                    = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    // File upload helper
    $uploadDir = __DIR__ . '/uploads/carousels/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $desktop_bg_image = $item['desktop_bg_image'];
    if (!empty($_FILES['desktop_bg_image']['name'])) {
        $file = $_FILES['desktop_bg_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
            $fname = 'carousel_desk_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fname)) {
                $desktop_bg_image = 'uploads/carousels/' . $fname;
            }
        }
    }

    $mobile_bg_image = $item['mobile_bg_image'];
    if (!empty($_FILES['mobile_bg_image']['name'])) {
        $file = $_FILES['mobile_bg_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
            $fname = 'carousel_mob_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fname)) {
                $mobile_bg_image = 'uploads/carousels/' . $fname;
            }
        }
    }

    if (empty($title)) {
        $error = "Carousel Title is required.";
    } else {
        if ($is_edit) {
            $stmt = $db->prepare("UPDATE carousels SET 
                title = ?, slug = ?,
                desktop_home_category_id = ?, desktop_home_design = ?, desktop_bg_color = ?, desktop_bg_image = ?,
                desktop_show_other_pages = ?, desktop_other_pages = ?, desktop_other_category_id = ?,
                desktop_sort_order = ?, desktop_status = ?,
                mobile_home_show = ?, mobile_home_category_id = ?, mobile_home_design = ?, mobile_bg_color = ?, mobile_bg_image = ?,
                mobile_show_other_pages = ?, mobile_other_pages = ?, mobile_other_category_id = ?,
                mobile_sort_order = ?, mobile_status = ?,
                meta_title = ?, meta_keywords = ?, meta_description = ?, status = ?, updated_at = NOW()
                WHERE id = ?");

            $stmt->bind_param('ssisssssiississsssiississi',
                $title, $slug,
                $desktop_home_category_id, $desktop_home_design, $desktop_bg_color, $desktop_bg_image,
                $desktop_show_other_pages, $desktop_show_other_pages, $desktop_other_category_id,
                $desktop_sort_order, $desktop_status,
                $mobile_home_show, $mobile_home_category_id, $mobile_home_design, $mobile_bg_color, $mobile_bg_image,
                $mobile_show_other_pages, $mobile_show_other_pages, $mobile_other_category_id,
                $mobile_sort_order, $mobile_status,
                $meta_title, $meta_keywords, $meta_description, $status,
                $id
            );

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Carousel updated successfully!";
                header('Location: carousels.php');
                exit();
            } else {
                $error = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $stmt = $db->prepare("INSERT INTO carousels (
                title, slug,
                desktop_home_category_id, desktop_home_design, desktop_bg_color, desktop_bg_image,
                desktop_show_other_pages, desktop_other_pages, desktop_other_category_id,
                desktop_sort_order, desktop_status,
                mobile_home_show, mobile_home_category_id, mobile_home_design, mobile_bg_color, mobile_bg_image,
                mobile_show_other_pages, mobile_other_pages, mobile_other_category_id,
                mobile_sort_order, mobile_status,
                meta_title, meta_keywords, meta_description, status, created_at
            ) VALUES (
                ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?, NOW()
            )");

            $stmt->bind_param('ssisssssiississsssiississ',
                $title, $slug,
                $desktop_home_category_id, $desktop_home_design, $desktop_bg_color, $desktop_bg_image,
                $desktop_show_other_pages, $desktop_show_other_pages, $desktop_other_category_id,
                $desktop_sort_order, $desktop_status,
                $mobile_home_show, $mobile_home_category_id, $mobile_home_design, $mobile_bg_color, $mobile_bg_image,
                $mobile_show_other_pages, $mobile_show_other_pages, $mobile_other_category_id,
                $mobile_sort_order, $mobile_status,
                $meta_title, $meta_keywords, $meta_description, $status
            );

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Carousel added successfully!";
                header('Location: carousels.php');
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
    <title><?php echo $is_edit ? 'Edit Carousel' : 'Add Carousel'; ?> | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-section-title {
            font-size: 15px;
            font-weight: 700;
            color: #03172c;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-section-title i { color: #00529b; }
        .design-choice-card {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            height: 100%;
        }
        .design-choice-card:hover {
            border-color: #00529b;
            background: #f0f7fc;
        }
        .design-choice-card input[type="radio"] {
            display: none;
        }
        .design-choice-card.selected {
            border-color: #00529b;
            background: #e8f4fc;
            box-shadow: 0 0 0 2px rgba(0, 82, 155, 0.2);
        }
        .design-choice-card .badge-icon {
            font-size: 24px;
            color: #00529b;
            margin-bottom: 6px;
        }
        .preview-thumb {
            max-width: 140px;
            max-height: 80px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            object-fit: cover;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-3">
                        <div class="col-auto">
                            <h1 class="h3 mb-0"><strong><?php echo $is_edit ? 'Edit' : 'Add New'; ?></strong> Carousel</h1>
                            <p class="text-muted small">Configure responsive product carousels across Desktop & Mobile views.</p>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="carousels.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Carousels
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
                        <!-- Card 1: Basic Information -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-body">
                                <div class="form-section-title">
                                    <i class="fas fa-info-circle"></i> Carousel General Details
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Carousel Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" placeholder="e.g. Most Popular Products, Corporate Gifts" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">URL Slug (Auto if empty)</label>
                                        <input type="text" name="slug" class="form-control" placeholder="most-popular-products" value="<?php echo htmlspecialchars($item['slug']); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Master Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" <?php echo $item['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $item['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Desktop Configuration -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-body">
                                <div class="form-section-title">
                                    <i class="fas fa-desktop"></i> Desktop View Settings (Home & Other Pages)
                                </div>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Desktop Home Category</label>
                                        <select name="desktop_home_category_id" class="form-select">
                                            <option value="">-- Choose Category (Optional) --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo (int)$item['desktop_home_category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Products from this category will populate the carousel if automatic.</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Desktop Sort Order</label>
                                        <input type="number" name="desktop_sort_order" class="form-control" value="<?php echo (int)$item['desktop_sort_order']; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Desktop Status</label>
                                        <select name="desktop_status" class="form-select">
                                            <option value="active" <?php echo $item['desktop_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $item['desktop_status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold d-block">Desktop Home Design Pattern (4 Types)</label>
                                    <div class="row g-2">
                                        <?php 
                                        $deskDesigns = [
                                            'design-1' => ['name' => 'Design 1: Grid 4 Box', 'icon' => 'fa-th-large', 'desc' => 'Classic 4-column balanced product grid'],
                                            'design-2' => ['name' => 'Design 2: Scroll Carousel', 'icon' => 'fa-sliders-h', 'desc' => 'Fluid horizontal scroll cards with navigation arrows'],
                                            'design-3' => ['name' => 'Design 3: Cards & Badges', 'icon' => 'fa-id-badge', 'desc' => 'Elevated cards with discount % and rating pills'],
                                            'design-4' => ['name' => 'Design 4: Hero Highlight', 'icon' => 'fa-star', 'desc' => '1 Large featured spotlight + 3 compact cards']
                                        ];
                                        foreach ($deskDesigns as $dKey => $dInfo): 
                                            $isSelected = ($item['desktop_home_design'] === $dKey);
                                        ?>
                                            <div class="col-md-3 col-6">
                                                <label class="design-choice-card <?php echo $isSelected ? 'selected' : ''; ?>" onclick="selectDesign(this, 'desktop_home_design')">
                                                    <input type="radio" name="desktop_home_design" value="<?php echo $dKey; ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                                                    <div class="badge-icon"><i class="fas <?php echo $dInfo['icon']; ?>"></i></div>
                                                    <div class="fw-bold small"><?php echo $dInfo['name']; ?></div>
                                                    <div class="text-muted" style="font-size:11px;"><?php echo $dInfo['desc']; ?></div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Desktop Background Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" value="<?php echo htmlspecialchars($item['desktop_bg_color'] ?: '#ffffff'); ?>" onchange="document.getElementById('desk_bg_hex').value = this.value">
                                            <input type="text" id="desk_bg_hex" name="desktop_bg_color" class="form-control" value="<?php echo htmlspecialchars($item['desktop_bg_color'] ?: '#ffffff'); ?>" placeholder="#ffffff">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Desktop Background Image</label>
                                        <input type="file" name="desktop_bg_image" class="form-control" accept="image/*">
                                        <?php if (!empty($item['desktop_bg_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['desktop_bg_image']); ?>" class="preview-thumb" alt="Desktop BG">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Show on Other Pages?</label>
                                        <select name="desktop_show_other_pages" class="form-select">
                                            <option value="no" <?php echo $item['desktop_show_other_pages'] === 'no' ? 'selected' : ''; ?>>No</option>
                                            <option value="yes" <?php echo $item['desktop_show_other_pages'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Mobile Configuration -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-body">
                                <div class="form-section-title">
                                    <i class="fas fa-mobile-alt"></i> Mobile View Settings
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Show on Mobile Home?</label>
                                        <select name="mobile_home_show" class="form-select">
                                            <option value="yes" <?php echo $item['mobile_home_show'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                            <option value="no" <?php echo $item['mobile_home_show'] === 'no' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">Mobile Home Category</label>
                                        <select name="mobile_home_category_id" class="form-select">
                                            <option value="">-- Choose Category (Optional) --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo (int)$item['mobile_home_category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Mobile Sort Order</label>
                                        <input type="number" name="mobile_sort_order" class="form-control" value="<?php echo (int)$item['mobile_sort_order']; ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Mobile Status</label>
                                        <select name="mobile_status" class="form-select">
                                            <option value="active" <?php echo $item['mobile_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $item['mobile_status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold d-block">Mobile Home Design Pattern (3 Types)</label>
                                    <div class="row g-2">
                                        <?php 
                                        $mobDesigns = [
                                            'design-1' => ['name' => 'Design 1: 2-Column Grid', 'icon' => 'fa-columns', 'desc' => 'Dual column compact cards optimized for touch'],
                                            'design-2' => ['name' => 'Design 2: Single Card Swiper', 'icon' => 'fa-arrows-alt-h', 'desc' => 'Horizontal snap-scroll carousel'],
                                            'design-3' => ['name' => 'Design 3: List Stack', 'icon' => 'fa-list-ul', 'desc' => 'Vertical stacked rows with side thumbnail & price']
                                        ];
                                        foreach ($mobDesigns as $dKey => $dInfo): 
                                            $isSelected = ($item['mobile_home_design'] === $dKey);
                                        ?>
                                            <div class="col-md-4 col-12">
                                                <label class="design-choice-card <?php echo $isSelected ? 'selected' : ''; ?>" onclick="selectDesign(this, 'mobile_home_design')">
                                                    <input type="radio" name="mobile_home_design" value="<?php echo $dKey; ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                                                    <div class="badge-icon"><i class="fas <?php echo $dInfo['icon']; ?>"></i></div>
                                                    <div class="fw-bold small"><?php echo $dInfo['name']; ?></div>
                                                    <div class="text-muted" style="font-size:11px;"><?php echo $dInfo['desc']; ?></div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Mobile Background Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" value="<?php echo htmlspecialchars($item['mobile_bg_color'] ?: '#ffffff'); ?>" onchange="document.getElementById('mob_bg_hex').value = this.value">
                                            <input type="text" id="mob_bg_hex" name="mobile_bg_color" class="form-control" value="<?php echo htmlspecialchars($item['mobile_bg_color'] ?: '#ffffff'); ?>" placeholder="#ffffff">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Mobile Background Image</label>
                                        <input type="file" name="mobile_bg_image" class="form-control" accept="image/*">
                                        <?php if (!empty($item['mobile_bg_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['mobile_bg_image']); ?>" class="preview-thumb" alt="Mobile BG">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Show on Mobile Other Pages?</label>
                                        <select name="mobile_show_other_pages" class="form-select">
                                            <option value="no" <?php echo $item['mobile_show_other_pages'] === 'no' ? 'selected' : ''; ?>>No</option>
                                            <option value="yes" <?php echo $item['mobile_show_other_pages'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: SEO Metadata -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-body">
                                <div class="form-section-title">
                                    <i class="fas fa-search"></i> SEO Meta Information
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Meta Title</label>
                                        <input type="text" name="meta_title" class="form-control" placeholder="Meta Title for search engines" value="<?php echo htmlspecialchars($item['meta_title']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Meta Keywords</label>
                                        <input type="text" name="meta_keywords" class="form-control" placeholder="keyword1, keyword2, keyword3" value="<?php echo htmlspecialchars($item['meta_keywords']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Meta Description</label>
                                        <textarea name="meta_description" class="form-control" rows="2" placeholder="Brief SEO description"><?php echo htmlspecialchars($item['meta_description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Bar -->
                        <div class="d-flex align-items-center justify-content-between mb-5">
                            <a href="carousels.php" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-5 fw-bold">
                                <i class="fas fa-save me-1"></i> <?php echo $is_edit ? 'Update Carousel' : 'Create Carousel'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function selectDesign(labelEl, radioName) {
            document.querySelectorAll(`input[name="${radioName}"]`).forEach(input => {
                const parent = input.closest('.design-choice-card');
                if (parent) parent.classList.remove('selected');
            });
            const radio = labelEl.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                labelEl.classList.add('selected');
            }
        }
    </script>
</body>
</html>
