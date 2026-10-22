<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$controller = new CategoryController();
$mainCategories = $controller->getMainCategories();
$error = '';

// AJAX: get sub cats for selected main cat
if (isset($_GET['get_sub']) && isset($_GET['parent_id'])) {
    header('Content-Type: application/json');
    echo json_encode($controller->getSubCategories((int) $_GET['parent_id']));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    function uploadCatImg3($fileKey, $subdir = 'general')
    {
        if (empty($_FILES[$fileKey]['name']))
            return '';
        $file = $_FILES[$fileKey];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) || $file['size'] > 5 * 1024 * 1024)
            return '';
        $dir = __DIR__ . "/uploads/category/{$subdir}/";
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        $fn = uniqid() . '_' . time() . '.' . $ext;
        return move_uploaded_file($file['tmp_name'], $dir . $fn) ? "uploads/category/{$subdir}/{$fn}" : '';
    }

    $parentId = (int) ($_POST['sub_parent_id'] ?? 0);
    $data = [
        'name' => $name,
        'slug' => $slug,
        'description' => trim($_POST['description'] ?? ''),
        'parent_id' => $parentId,
        'level' => 3,
        'status' => $_POST['status'] ?? 'active',
        'is_featured' => (int) ($_POST['is_featured'] ?? 0),
        'icon' => trim($_POST['icon'] ?? ''),
        'display_order' => (int) ($_POST['display_order'] ?? 0),
        // Desktop menu
        'desktop_menu_status' => $_POST['desktop_menu_status'] ?? 'show',
        'desktop_menu_order' => (int) ($_POST['desktop_menu_order'] ?? 0),
        'desktop_menu_tag' => trim($_POST['desktop_menu_tag'] ?? ''),
        // Mobile menu
        'mobile_topbar_status' => $_POST['mobile_topbar_status'] ?? 'show',
        'mobile_topbar_order' => (int) ($_POST['mobile_topbar_order'] ?? 0),
        'mobile_sidebar_order' => (int) ($_POST['mobile_sidebar_order'] ?? 0),
        // SEO
        'meta_title' => trim($_POST['meta_title'] ?? ''),
        'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
    ];

    if (empty($data['name'])) {
        $error = 'Sub sub category name is required.';
    } elseif (!$parentId) {
        $error = 'Please select a sub category.';
    } else {
        $result = $controller->createCategory($data);
        if ($result) {
            $_SESSION['success_message'] = 'Sub sub category created successfully!';
            header('Location: view-categories.php');
            exit;
        } else {
            $error = 'Failed to create sub sub category.';
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
    <title>Add Sub Sub Category | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body {
            opacity: 1 !important;
        }

        .required-field::after {
            content: " *";
            color: #dc3545;
        }

        .section-card {
            border-left: 4px solid #0d6efd;
        }

        .section-card.mobile {
            border-left-color: #198754;
        }

        .section-card.seo {
            border-left-color: #ffc107;
        }

        .design-option { 
            border: 2px solid #dee2e6; 
            border-radius: 10px; 
            padding: 12px; 
            text-align: left; 
            transition: all .25s ease-in-out; 
            cursor: pointer; 
            background: #ffffff;
            height: 100%;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .design-option:hover { 
            border-color: #0d6efd; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.12);
        }
        input[type=radio]:checked + .design-option { 
            border-color: #0d6efd; 
            background: #f0f7ff; 
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
        }
        .design-option .check-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            display: none;
            font-size: 18px;
            color: #0d6efd;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        input[type=radio]:checked + .design-option .check-badge {
            display: block;
        }
        /* Mobile Section Green Accent */
        .section-card.mobile .design-option:hover {
            border-color: #198754;
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.15);
        }
        .section-card.mobile input[type=radio]:checked + .design-option {
            border-color: #198754;
            background: #f4faf6;
            box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.25);
        }
        .section-card.mobile input[type=radio]:checked + .design-option .check-badge {
            color: #198754;
        }
        .design-tag {
            font-size: 10.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        .design-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .design-desc {
            font-size: 11.5px;
            color: #64748b;
            line-height: 1.4;
            margin-top: auto;
            margin-bottom: 0;
        }

        .image-preview {
            max-height: 120px;
            border-radius: 6px;
            margin-top: 8px;
            display: none;
        }

        .conditional-block {
            display: none;
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

                    <div class="row mb-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Add</strong> Sub Sub Category</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="view-categories.php" class="btn btn-success">View All Categories</a>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible"><button class="btn-close"
                                data-bs-dismiss="alert"></button><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="mainForm">

                        <!-- BASIC INFO -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <!-- Step 1: Select Main Category -->
                                        <div class="mb-3">
                                            <label class="form-label required-field">Main Category</label>
                                            <select id="mainCatSelect" class="form-control">
                                                <option value="">— Select Main Category —</option>
                                                <?php foreach ($mainCategories as $mc): ?>
                                                    <option value="<?php echo $mc['id']; ?>">
                                                        <?php echo htmlspecialchars($mc['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Step 2: Select Sub Category (loaded via AJAX) -->
                                        <div class="mb-3">
                                            <label class="form-label required-field">Sub Category</label>
                                            <select name="sub_parent_id" id="subCatSelect" class="form-control"
                                                required>
                                                <option value="">— Select Main Category first —</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label required-field">Sub Sub Category Name</label>
                                        <input type="text" class="form-control" name="name" id="catName" required
                                            placeholder="e.g. Gaming Laptops">
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label required-field">Slug</label>
                                        <input type="text" class="form-control" name="slug" id="catSlug"
                                            placeholder="auto-generated">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-md-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-3">
                                        <label class="form-label">Featured</label>
                                        <select name="is_featured" class="form-control">
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-3">
                                        <label class="form-label">Display Order</label>
                                        <input type="number" class="form-control" name="display_order" value="0"
                                            min="0">
                                    </div>
                                    <div class="mb-3 col-md-3">
                                        <label class="form-label">Icon</label>
                                        <input type="text" class="form-control" name="icon" placeholder="fas fa-tag">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- DESKTOP TOP MENU -->
                        <div class="card mb-4 section-card">
                            <div class="card-header bg-primary bg-opacity-10">
                                <h5 class="mb-0 text-primary"><i class="fas fa-desktop me-2"></i>Desktop – Top Menu
                                    Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Sub Sub Category Status</label>
                                        <select name="desktop_menu_status" class="form-control">
                                            <option value="show">Show</option>
                                            <option value="hide">Hide</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Sort Order</label>
                                        <input type="number" class="form-control" name="desktop_menu_order" value="0"
                                            min="0">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Menu Tag <small class="text-muted">(e.g. New,
                                                Sale)</small></label>
                                        <input type="text" class="form-control" name="desktop_menu_tag"
                                            placeholder="New" maxlength="100">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- MOBILE TOP BAR -->
                        <div class="card mb-4 section-card mobile">
                            <div class="card-header bg-success bg-opacity-10">
                                <h5 class="mb-0 text-success"><i class="fas fa-mobile-alt me-2"></i>Mobile – Top Bar
                                    Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Mobile Top Bar Status</label>
                                        <select name="mobile_topbar_status" class="form-control">
                                            <option value="show">Show</option>
                                            <option value="hide">Hide</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Top Bar Sort Order</label>
                                        <input type="number" class="form-control" name="mobile_topbar_order" value="0"
                                            min="0">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Sidebar Sort Order</label>
                                        <input type="number" class="form-control" name="mobile_sidebar_order" value="0"
                                            min="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEO -->
                        <div class="card mb-4 section-card seo">
                            <div class="card-header bg-warning bg-opacity-10">
                                <h5 class="mb-0"><i class="fas fa-search me-2"></i>SEO Meta</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Meta Title</label>
                                    <input type="text" class="form-control" name="meta_title" maxlength="255">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Meta Keywords</label>
                                    <input type="text" class="form-control" name="meta_keywords">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Meta Description</label>
                                    <textarea class="form-control" name="meta_description" rows="3"
                                        maxlength="160"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">Create Sub Sub Category</button>
                            <a href="view-categories.php" class="btn btn-secondary ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>
    <script src="js/app.js"></script>
    <script>
        // Slug auto-generate
        const catName = document.getElementById('catName');
        const catSlug = document.getElementById('catSlug');
        catName.addEventListener('input', function () {
            if (!catSlug._manual) catSlug.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        });
        catSlug.addEventListener('input', function () { this._manual = !!this.value; });

        // Load sub categories when main category selected
        document.getElementById('mainCatSelect').addEventListener('change', function () {
            const subSel = document.getElementById('subCatSelect');
            subSel.innerHTML = '<option value="">Loading...</option>';
            if (!this.value) { subSel.innerHTML = '<option value="">— Select Main Category first —</option>'; return; }
            fetch(`add-sub-sub-category.php?get_sub=1&parent_id=${this.value}`)
                .then(r => r.json())
                .then(data => {
                    subSel.innerHTML = '<option value="">— Select Sub Category —</option>';
                    data.forEach(s => subSel.innerHTML += `<option value="${s.id}">${s.name}</option>`);
                });
        });

        function bindToggle(selectId, blockId) {
            const sel = document.getElementById(selectId);
            const blk = document.getElementById(blockId);
            if (!sel || !blk) return;
            const update = () => blk.style.display = sel.value === 'yes' ? 'block' : 'none';
            sel.addEventListener('change', update);
            update();
        }
        
        

        function syncColor(pickerId, textId) {
            const p = document.getElementById(pickerId), t = document.getElementById(textId);
            if (!p || !t) return;
            p.addEventListener('input', () => t.value = p.value);
            t.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(t.value)) p.value = t.value; });
        }
        
        

        function previewImg(input, id) {
            const el = document.getElementById(id);
            if (!input.files || !input.files[0]) return;
            const r = new FileReader();
            r.onload = e => { el.src = e.target.result; el.style.display = 'block'; };
            r.readAsDataURL(input.files[0]);
        }
    </script>
</body>

</html>