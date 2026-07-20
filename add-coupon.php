<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CouponController.php');

$couponController = new CouponController();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $couponController->createCoupon($_POST);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: coupons.php');
        exit;
    }
    $error_message = $result['error'];
}

$categories = $couponController->getAllCategories();
$carousels  = $couponController->getAllCarousels();

// Sticky values so a validation failure doesn't wipe the form.
function old($key, $default = '') { return htmlspecialchars($_POST[$key] ?? $default); }
function oldSel($key, $value, $default = '') { return (($_POST[$key] ?? $default) == $value) ? 'selected' : ''; }

$mainCategories = array_values(array_filter($categories, fn($c) => (int)$c['level'] === 0));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Add Coupon | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .required-field::after { content: " *"; color: #dc3545; }
        #coupon_code { text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
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
                            <h3><strong>Add</strong> Coupon</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="coupons.php" class="btn btn-success">
                                <i class="fas fa-list"></i> View All Coupons
                            </a>
                        </div>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <!-- Coupon details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Coupon Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label required-field">Coupon Code</label>
                                        <input type="text" class="form-control" name="coupon_code" id="coupon_code"
                                               required placeholder="e.g. WELCOME20" value="<?php echo old('coupon_code'); ?>">
                                        <small class="text-muted">Letters, numbers, hyphens and underscores only. Saved in uppercase.</small>
                                    </div>
                                    <div class="mb-3 col-md-3">
                                        <label class="form-label required-field">Discount Format</label>
                                        <select name="discount_format" id="discount_format" class="form-control">
                                            <option value="percentage" <?php echo oldSel('discount_format', 'percentage', 'percentage'); ?>>Percentage (%)</option>
                                            <option value="fixed" <?php echo oldSel('discount_format', 'fixed', 'percentage'); ?>>Fixed Amount (&#8377;)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-3">
                                        <label class="form-label required-field" id="discount_value_label">Percentage</label>
                                        <input type="number" step="0.01" min="0" class="form-control"
                                               name="discount_value" id="discount_value" required
                                               placeholder="e.g. 20" value="<?php echo old('discount_value'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Applicability (DOCX: Allow ... Select) -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Where The Coupon Applies</h5>
                                <small class="text-muted">Leave all blank to allow the coupon on every product.</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">Allow Product Type Category</label>
                                        <select name="category_id" id="category_id" class="form-control">
                                            <option value="">— All Categories —</option>
                                            <?php foreach ($mainCategories as $cat): ?>
                                                <option value="<?php echo (int)$cat['id']; ?>" <?php echo oldSel('category_id', $cat['id']); ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">Allow Sub Category</label>
                                        <select name="sub_category_id" id="sub_category_id" class="form-control">
                                            <option value="">— All Sub Categories —</option>
                                        </select>
                                        <small class="text-muted">Populated from the selected category.</small>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">Allow Sub Sub Category</label>
                                        <select name="sub_sub_category_id" id="sub_sub_category_id" class="form-control">
                                            <option value="">— All Sub Sub Categories —</option>
                                        </select>
                                        <small class="text-muted">Populated from the selected sub category.</small>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">Allow Product Carousel Type</label>
                                        <select name="carousel_type" class="form-control">
                                            <option value="">— Any Carousel —</option>
                                            <?php foreach ($carousels as $car): ?>
                                                <?php $val = $car['slug'] ?: $car['title']; ?>
                                                <option value="<?php echo htmlspecialchars($val); ?>" <?php echo oldSel('carousel_type', $val); ?>>
                                                    <?php echo htmlspecialchars($car['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (empty($carousels)): ?>
                                            <small class="text-muted">No carousels exist yet — add them under Manage Carousels.</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Limits and validity -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Limits &amp; Validity</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Minimum Order Value (&#8377;)</label>
                                        <input type="number" step="0.01" min="0" class="form-control"
                                               name="min_order_value" placeholder="0.00"
                                               value="<?php echo old('min_order_value', '0'); ?>">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" min="0" class="form-control" name="quantity"
                                               placeholder="0" value="<?php echo old('quantity', '0'); ?>">
                                        <small class="text-muted">Total times this coupon may be used. 0 = unlimited.</small>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="active" <?php echo oldSel('status', 'active', 'active'); ?>>Active</option>
                                            <option value="inactive" <?php echo oldSel('status', 'inactive', 'active'); ?>>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" name="start_date" id="start_date"
                                               value="<?php echo old('start_date'); ?>">
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">End Date</label>
                                        <input type="date" class="form-control" name="end_date" id="end_date"
                                               value="<?php echo old('end_date'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Coupon
                            </button>
                            <a href="coupons.php" class="btn btn-secondary ms-2">Cancel</a>
                        </div>
                    </form>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Every active category, used to drive the three cascading selects without a round trip.
        const ALL_CATEGORIES = <?php echo json_encode($categories); ?>;

        const selMain = document.getElementById('category_id');
        const selSub = document.getElementById('sub_category_id');
        const selSubSub = document.getElementById('sub_sub_category_id');

        function fillOptions(select, items, placeholder, selectedId) {
            select.innerHTML = '<option value="">' + placeholder + '</option>';
            items.forEach(function (item) {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                if (String(item.id) === String(selectedId)) opt.selected = true;
                select.appendChild(opt);
            });
        }

        function childrenOf(parentId, level) {
            if (!parentId) return [];
            return ALL_CATEGORIES.filter(function (c) {
                return String(c.parent_id) === String(parentId) && Number(c.level) === level;
            });
        }

        function refreshSub(selectedId) {
            fillOptions(selSub, childrenOf(selMain.value, 1), '— All Sub Categories —', selectedId);
        }

        function refreshSubSub(selectedId) {
            fillOptions(selSubSub, childrenOf(selSub.value, 2), '— All Sub Sub Categories —', selectedId);
        }

        selMain.addEventListener('change', function () { refreshSub(null); refreshSubSub(null); });
        selSub.addEventListener('change', function () { refreshSubSub(null); });

        // Restore the sticky selection after a failed submit.
        refreshSub(<?php echo json_encode($_POST['sub_category_id'] ?? ''); ?>);
        refreshSubSub(<?php echo json_encode($_POST['sub_sub_category_id'] ?? ''); ?>);

        // Label follows the chosen discount format.
        const fmt = document.getElementById('discount_format');
        const lbl = document.getElementById('discount_value_label');
        function syncLabel() {
            lbl.textContent = fmt.value === 'fixed' ? 'Discount Amount (₹)' : 'Percentage';
        }
        fmt.addEventListener('change', syncLabel);
        syncLabel();

        document.getElementById('coupon_code').addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });

        // Stop the end date being set before the start date.
        const start = document.getElementById('start_date');
        const end = document.getElementById('end_date');
        start.addEventListener('change', function () { end.min = this.value; });
        if (start.value) end.min = start.value;
    </script>
</body>

</html>
