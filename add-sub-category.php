<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$controller     = new CategoryController();
$mainCategories = $controller->getMainCategories();
$error   = '';
$success = '';

// Pre-select parent when arriving from "Add Sub Category" on a specific main category row
$selectedParentId = (int)($_POST['parent_id'] ?? $_GET['parent_id'] ?? 0);
$nextOrder = $selectedParentId ? $controller->getNextDisplayOrder(2, $selectedParentId) : 1;

function old($key, $default = '') {
    return htmlspecialchars($_POST[$key] ?? $default);
}
function oldSel($key, $value, $default = '') {
    return (($_POST[$key] ?? $default) === $value) ? 'selected' : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    function uploadCatImg($fileKey, $subdir = 'general') {
        if (empty($_FILES[$fileKey]['name'])) return '';
        $file = $_FILES[$fileKey];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp']) || $file['size'] > 5*1024*1024) return '';
        $dir = __DIR__ . "/uploads/category/{$subdir}/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fn = uniqid().'_'.time().'.'.$ext;
        return move_uploaded_file($file['tmp_name'], $dir.$fn) ? "uploads/category/{$subdir}/{$fn}" : '';
    }

    $parentId = (int)($_POST['parent_id'] ?? 0);
    $data = [
        'name'                 => $name,
        'slug'                 => $slug,
        'description'          => trim($_POST['description'] ?? ''),
        'parent_id'            => $parentId,
        'level'                => 2,
        'status'               => $_POST['status'] ?? 'active',
        'is_featured'          => (int)($_POST['is_featured'] ?? 0),
        'icon'                 => trim($_POST['icon'] ?? ''),
        'display_order'        => (int)($_POST['display_order'] ?? $controller->getNextDisplayOrder(2, $parentId)),
        'image'                => uploadCatImg('image', 'sub'),
        // Desktop Inner Menu (FNP Column Header)
        'desktop_menu_status'  => $_POST['desktop_menu_status'] ?? 'show',
        'desktop_menu_order'   => (int)($_POST['desktop_menu_order'] ?? (int)($_POST['display_order'] ?? 0)),
        'desktop_menu_tag'     => trim($_POST['desktop_menu_tag'] ?? ''),
        // Mobile
        'mobile_topbar_status' => $_POST['mobile_topbar_status'] ?? 'show',
        'mobile_topbar_order'  => (int)($_POST['mobile_topbar_order'] ?? (int)($_POST['display_order'] ?? 0)),
        // SEO
        'meta_title'           => trim($_POST['meta_title'] ?? ''),
        'meta_keywords'        => trim($_POST['meta_keywords'] ?? ''),
        'meta_description'     => trim($_POST['meta_description'] ?? ''),
    ];

    if (empty($data['name'])) {
        $error = 'Subcategory name is required.';
    } elseif (!$parentId) {
        $error = 'Please select a main parent category.';
    } elseif ($controller->checkCategoryExists($data['name'], $data['slug'])) {
        $error = 'A category with this name or slug already exists. Please choose a different name or slug.';
    } else {
        $result = $controller->createCategory($data);
        if ($result) {
            $_SESSION['success_message'] = 'Subcategory created successfully!';
            header('Location: view-categories.php');
            exit;
        } else {
            $error = 'Failed to create subcategory.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Add Subcategory | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 1 !important; font-family: 'Inter', sans-serif; }
        .required-field::after { content: " *"; color: #dc3545; }
        .nav-tabs .nav-link {
            font-size: 14px;
            font-weight: 600;
            padding: 12px 18px;
            color: #4b5563;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .nav-tabs .nav-link.active {
            color: #198754 !important;
            border-color: #dee2e6 #dee2e6 #ffffff !important;
            background-color: #ffffff !important;
            font-weight: 700;
        }
        .tab-content {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 8px 8px;
            padding: 24px;
        }
        .section-header-box {
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
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

                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="h3 fw-bold mb-1">Add Subcategory</h2>
                        <span class="badge bg-success px-2 py-1">Level 2 – Mega Menu Column Header</span>
                    </div>
                    <div>
                        <a href="view-categories.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> All Categories
                        </a>
                        <button type="submit" form="subCatForm" class="btn btn-success px-4">
                            <i class="fas fa-plus me-1"></i> Create Subcategory
                        </button>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="subCatForm">

                    <!-- SEPARATE TAB NAVIGATION -->
                    <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="tab-basic-btn" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button" role="tab">
                                <i class="fas fa-info-circle me-1 text-success"></i> 1. Subcategory Info
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-inner-btn" data-bs-toggle="tab" data-bs-target="#tab-inner" type="button" role="tab">
                                <i class="fas fa-bars me-1 text-primary"></i> 2. Inner Dropdown Column Position & Tag
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-seo-btn" data-bs-toggle="tab" data-bs-target="#tab-seo" type="button" role="tab">
                                <i class="fas fa-search me-1 text-warning"></i> 3. SEO Settings
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content shadow-sm mb-4">

                        <!-- TAB 1: BASIC INFO -->
                        <div class="tab-pane fade show active" id="tab-basic" role="tabpanel">
                            <div class="section-header-box bg-light border">
                                <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-folder-open me-2 text-success"></i>Subcategory Details</h5>
                                <p class="text-muted small mb-0">Select the Main Category (Parent) that this subcategory belongs to.</p>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field fw-bold">Parent Main Category</label>
                                    <select name="parent_id" class="form-select form-select-lg" required>
                                        <option value="">-- Select Main Category --</option>
                                        <?php foreach ($mainCategories as $main): ?>
                                            <option value="<?php echo $main['id']; ?>" <?php echo ($selectedParentId == $main['id'] || old('parent_id') == $main['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($main['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">This subcategory will appear as a column inside this main category's dropdown.</small>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field fw-semibold">Subcategory Name</label>
                                    <input type="text" class="form-control form-control-lg" name="name" required
                                           value="<?php echo old('name'); ?>" placeholder="e.g. Diarise & Executive Notebooks">
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label fw-semibold">Slug (URL)</label>
                                    <input type="text" class="form-control" name="slug" value="<?php echo old('slug'); ?>" placeholder="e.g. diaries-notebooks">
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?php echo oldSel('status', 'active', 'active'); ?>>Active (Visible)</option>
                                        <option value="inactive" <?php echo oldSel('status', 'inactive', 'active'); ?>>Inactive (Hidden)</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label fw-semibold">Display Order</label>
                                    <input type="number" class="form-control" name="display_order" value="<?php echo old('display_order', $nextOrder); ?>" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: INNER DROPDOWN COLUMN & TAG -->
                        <div class="tab-pane fade" id="tab-inner" role="tabpanel">
                            <div class="section-header-box bg-primary bg-opacity-10 border border-primary border-opacity-25">
                                <h5 class="fw-bold mb-1 text-primary"><i class="fas fa-bars me-2"></i>Inner Dropdown Column & Tag Settings</h5>
                                <p class="text-muted small mb-0">Manage column ordering and badges inside the 5-column mega menu.</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Show in Dropdown Menu?</label>
                                    <select name="desktop_menu_status" class="form-select">
                                        <option value="show" <?php echo oldSel('desktop_menu_status', 'show', 'show'); ?>>Show in Dropdown</option>
                                        <option value="hide" <?php echo oldSel('desktop_menu_status', 'hide', 'show'); ?>>Hide from Dropdown</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Dropdown Column Order</label>
                                    <input type="number" class="form-control" name="desktop_menu_order" value="<?php echo old('desktop_menu_order', $nextOrder); ?>" min="0">
                                    <small class="text-muted">Columns are ordered 1 to 5 based on this number.</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Menu Badge / Tag (Pill)</label>
                                    <input type="text" class="form-control" name="desktop_menu_tag" value="<?php echo old('desktop_menu_tag'); ?>" placeholder="e.g. New, Hot">
                                    <small class="text-muted">Shows a red badge (e.g. <span class="badge bg-danger">New</span>) next to this link.</small>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: SEO -->
                        <div class="tab-pane fade" id="tab-seo" role="tabpanel">
                            <div class="section-header-box bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-search me-2 text-warning"></i>SEO Meta Information</h5>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Meta Title</label>
                                <input type="text" class="form-control" name="meta_title" value="<?php echo old('meta_title'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Meta Description</label>
                                <textarea class="form-control" name="meta_description" rows="3"><?php echo old('meta_description'); ?></textarea>
                            </div>
                        </div>

                    </div>

                    <!-- Actions -->
                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border">
                        <a href="view-categories.php" class="btn btn-secondary px-3"><i class="fas fa-times me-1"></i> Cancel</a>
                        <button type="submit" class="btn btn-success px-4 fw-semibold"><i class="fas fa-plus me-1"></i> Create Subcategory</button>
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
