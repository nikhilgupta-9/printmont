<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$controller = new CategoryController();
$topIcons   = $controller->getTopIconCategories();
$insidePages = $controller->getMenuInsidePages();

$configFile = __DIR__ . '/config/header_menu_design.json';
$currentDesign = 'design1';
if (file_exists($configFile)) {
    $data = json_decode(file_get_contents($configFile), true);
    $currentDesign = $data['design'] ?? 'design1';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Header Menu — All Menus | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        .thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 4px; }
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
                        <h3><strong>Header Menu</strong> — All Menus</h3>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1">
                        <div class="btn-group" role="group">
                            <a href="add-top-icon.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Top Icon</a>
                            <a href="add-menu-page.php" class="btn btn-success"><i class="fas fa-plus me-1"></i> Menu Page</a>
                            <a href="header-menu-design.php" class="btn btn-outline-secondary">Layout: <?php echo htmlspecialchars($currentDesign); ?></a>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Top Icons <span class="text-muted">(Main Categories)</span></h5></div>
                    <div class="card-body">
                        <?php if (empty($topIcons)): ?>
                            <p class="text-muted mb-0">No main categories found.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="60">Image</th>
                                        <th>Name</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th width="100">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topIcons as $ti): ?>
                                    <tr>
                                        <td>
                                            <?php $thumb = $ti['desktop_menu_image'] ?: $ti['image']; ?>
                                            <?php if ($thumb): ?>
                                                <img src="<?php echo htmlspecialchars($thumb); ?>" class="thumb" onerror="this.style.display='none'">
                                            <?php else: ?>
                                                <div class="thumb bg-light d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted"></i></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($ti['name']); ?></td>
                                        <td><span class="badge bg-info"><?php echo (int)$ti['desktop_menu_order']; ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo $ti['desktop_menu_status'] == 'show' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($ti['desktop_menu_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="add-top-icon.php?category_id=<?php echo $ti['id']; ?>" class="btn btn-outline-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
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

                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Top Menu Inside Pages <span class="text-muted">(Sub / Sub-Sub Categories)</span></h5></div>
                    <div class="card-body">
                        <?php if (empty($insidePages)): ?>
                            <p class="text-muted mb-0">No sub or sub-sub categories found.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="60">Image</th>
                                        <th>Name</th>
                                        <th>Parent</th>
                                        <th>Design</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th width="100">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($insidePages as $ip): ?>
                                    <tr>
                                        <td>
                                            <?php if ($ip['desktop_menu_image']): ?>
                                                <img src="<?php echo htmlspecialchars($ip['desktop_menu_image']); ?>" class="thumb" onerror="this.style.display='none'">
                                            <?php else: ?>
                                                <div class="thumb bg-light d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted"></i></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($ip['name']); ?>
                                            <span class="badge bg-<?php echo $ip['level'] == 3 ? 'warning' : 'success'; ?> ms-1">
                                                <?php echo $ip['level'] == 3 ? 'Sub-Sub' : 'Sub'; ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?php echo htmlspecialchars($ip['parent_name'] ?? '—'); ?></td>
                                        <td><?php echo $ip['desktop_menu_design'] ? htmlspecialchars($ip['desktop_menu_design']) : '<span class="text-muted">—</span>'; ?></td>
                                        <td><span class="badge bg-info"><?php echo (int)$ip['desktop_menu_order']; ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo $ip['desktop_menu_status'] == 'show' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($ip['desktop_menu_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="add-menu-page.php?category_id=<?php echo $ip['id']; ?>" class="btn btn-outline-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
