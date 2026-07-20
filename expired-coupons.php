<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CouponController.php');

$couponController = new CouponController();

$search  = $_GET['search'] ?? '';
$page    = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;

$coupons      = $couponController->getCouponsWithPagination($page, $perPage, $search, 'expired');
$totalCoupons = $couponController->getCouponsCount($search, 'expired');
$totalPages   = max(1, ceil($totalCoupons / $perPage));

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message']   ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Expired Coupons | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-expired { background-color: #fff3cd; color: #856404; }
        .action-btn { padding: 4px 8px; margin: 0 2px; }
        .coupon-code {
            font-family: "SFMono-Regular", Consolas, monospace;
            font-weight: 600;
            letter-spacing: .5px;
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

                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Expired</strong> Coupons</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="coupons.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list"></i> All Coupons
                            </a>
                            <a href="add-coupon.php" class="btn btn-primary ms-1">
                                <i class="fas fa-plus"></i> Add New Coupon
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

                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0">Expired Coupons</h5>
                                    <h6 class="card-subtitle text-muted mb-0">
                                        Coupons past their end date, or manually marked expired.
                                    </h6>
                                </div>
                                <div class="col-auto">
                                    <form method="GET" class="d-flex">
                                        <input type="text" name="search" class="form-control form-control-sm"
                                               placeholder="Search coupon code..."
                                               value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-sm btn-primary ms-1">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if ($search !== ''): ?>
                                            <a href="expired-coupons.php" class="btn btn-sm btn-secondary ms-1">Clear</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Coupon Code</th>
                                            <th>Discount</th>
                                            <th>Applies To</th>
                                            <th>Min Order</th>
                                            <th>Used</th>
                                            <th>Expired On</th>
                                            <th>Status</th>
                                            <th width="110">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($coupons)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-5 text-muted">
                                                    <?php if ($search !== ''): ?>
                                                        No expired coupons match &ldquo;<?php echo htmlspecialchars($search); ?>&rdquo;.
                                                    <?php else: ?>
                                                        No expired coupons. <a href="coupons.php">View all coupons</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $rowNum = ($page - 1) * $perPage; ?>
                                            <?php foreach ($coupons as $c): $rowNum++; ?>
                                                <tr>
                                                    <td><?php echo $rowNum; ?></td>
                                                    <td><span class="coupon-code"><?php echo htmlspecialchars($c['coupon_code']); ?></span></td>
                                                    <td>
                                                        <?php if ($c['discount_format'] === 'percentage'): ?>
                                                            <?php echo rtrim(rtrim(number_format((float)$c['discount_value'], 2), '0'), '.'); ?>%
                                                        <?php else: ?>
                                                            &#8377;<?php echo number_format((float)$c['discount_value'], 2); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $applies = array_filter([
                                                            $c['category_name']         ?? null,
                                                            $c['sub_category_name']     ?? null,
                                                            $c['sub_sub_category_name'] ?? null,
                                                        ]);
                                                        ?>
                                                        <?php if ($applies): ?>
                                                            <small><?php echo htmlspecialchars(implode(' &rsaquo; ', $applies)); ?></small>
                                                        <?php else: ?>
                                                            <small class="text-muted">All products</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>&#8377;<?php echo number_format((float)$c['min_order_value'], 2); ?></td>
                                                    <td><?php echo (int)$c['used_count']; ?></td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo $c['end_date'] ? date('M j, Y', strtotime($c['end_date'])) : '—'; ?>
                                                        </small>
                                                    </td>
                                                    <td><span class="status-badge status-expired">Expired</span></td>
                                                    <td>
                                                        <a href="edit-coupon.php?id=<?php echo (int)$c['id']; ?>"
                                                           class="btn btn-sm btn-outline-primary action-btn" title="Edit / Reactivate">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="delete-coupon.php?id=<?php echo (int)$c['id']; ?>"
                                                           class="btn btn-sm btn-outline-danger action-btn"
                                                           onclick="return confirm('Delete coupon <?php echo htmlspecialchars($c['coupon_code'], ENT_QUOTES); ?>? This cannot be undone.')"
                                                           title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Showing <?php echo count($coupons); ?> of <?php echo $totalCoupons; ?> expired coupons
                                </small>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">&laquo;</a>
                                        </li>
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <?php if ($i == 1 || $i == $totalPages || abs($i - $page) < 3): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php elseif (abs($i - $page) == 3): ?>
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">&raquo;</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
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
