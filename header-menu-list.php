<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$controller = new CategoryController();

$database = new Database();
$conn = $database->getConnection();

if (isset($_POST['action']) && $_POST['action'] === 'toggle_header_status') {
    header('Content-Type: application/json');
    $catId = (int)($_POST['id'] ?? 0);
    $newStatus = (isset($_POST['status']) && $_POST['status'] === 'show') ? 'show' : 'hide';

    if ($catId > 0) {
        $stmt = $conn->prepare("UPDATE categories SET desktop_menu_status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $catId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'status' => $newStatus]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
    }
    exit;
}
$flatCategories = $controller->getAllActiveCategoriesFlat();

$configFile = __DIR__ . '/config/header_menu_design.json';
$currentDesign = 'design1';
if (file_exists($configFile)) {
    $data = json_decode(file_get_contents($configFile), true);
    $currentDesign = $data['design'] ?? 'design1';
}

function buildMenuCategoryTree(array $categories, int $parentId = 0): array {
    $branch = [];
    foreach ($categories as $category) {
        if ((int)$category['parent_id'] === $parentId) {
            $children = buildMenuCategoryTree($categories, (int)$category['id']);
            if ($children) {
                $category['children'] = $children;
            }
            $branch[] = $category;
        }
    }
    return $branch;
}

$categoryTree = buildMenuCategoryTree($flatCategories);
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
        .thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; }
        .menu-item { border-left: 4px solid #007bff; margin: 5px 0; }
        .menu-level-1 { border-left-color: #007bff; background-color: #f8f9fa; }
        .menu-level-2 { border-left-color: #28a745; background-color: #e9ecef; margin-left: 24px; }
        .menu-level-3 { border-left-color: #fd7e14; background-color: #dee2e6; margin-left: 48px; }
        .toggle-children { cursor: pointer; width: 22px; display: inline-flex; align-items: center; justify-content: center; }
        .children.collapsed { display: none; }
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

                <p class="text-muted">Every header menu item is a category — Main Categories are Top Icons, Sub / Sub-Sub Categories are Top Menu Inside Pages. This mirrors your category tree exactly.</p>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Category → Menu Tree</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categoryTree)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <h5>No categories found</h5>
                            </div>
                        <?php else: ?>
                            <?php
                            function renderMenuTree(array $nodes, int $depth = 1) {
                                foreach ($nodes as $node) {
                                    $hasChildren = !empty($node['children']);
                                    $level = (int) $node['level'];
                                    $isTopIcon = $level === 1;
                                    $editUrl = $isTopIcon
                                        ? 'add-top-icon.php?category_id=' . $node['id']
                                        : 'add-menu-page.php?category_id=' . $node['id'];
                                    $thumb = $node['desktop_menu_image'] ?: $node['image'];
                                    ?>
                                    <div class="menu-item menu-level-<?php echo $level; ?> p-2 rounded mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <?php if ($hasChildren): ?>
                                                    <span class="toggle-children" onclick="toggleMenuChildren(this)">
                                                        <i class="fas fa-chevron-down"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="toggle-children"></span>
                                                <?php endif; ?>

                                                <?php if ($thumb): ?>
                                                    <img src="<?php echo htmlspecialchars($thumb); ?>" class="thumb me-2" onerror="this.style.display='none'">
                                                <?php else: ?>
                                                    <div class="thumb me-2 bg-white d-flex align-items-center justify-content-center rounded">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>

                                                <div>
                                                    <strong><?php echo htmlspecialchars($node['name']); ?></strong>
                                                    <span class="badge bg-<?php echo $isTopIcon ? 'primary' : ($level == 3 ? 'warning' : 'success'); ?> ms-2">
                                                        <?php echo $isTopIcon ? 'Top Icon' : ($level == 3 ? 'Sub-Sub' : 'Sub'); ?>
                                                    </span>
                                                    <?php if (!empty($node['desktop_menu_tag'])): ?>
                                                        <span class="badge bg-info ms-1"><?php echo htmlspecialchars($node['desktop_menu_tag']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="d-flex gap-2 align-items-center">
                                                <?php if (!empty($node['desktop_menu_design'])): ?>
                                                    <span class="badge bg-light text-dark border">Design: <?php echo htmlspecialchars($node['desktop_menu_design']); ?></span>
                                                <?php endif; ?>
                                                <span class="badge bg-secondary" title="Sort order">
                                                    <i class="fas fa-sort-numeric-up me-1"></i><?php echo (int) $node['desktop_menu_order']; ?>
                                                </span>
                                                 <div class="form-check form-switch mb-0 d-inline-flex align-items-center" style="cursor: pointer;" title="Toggle Header Menu Visibility (Show/Hide)">
                                                     <input class="form-check-input me-1" 
                                                            type="checkbox" 
                                                            role="switch" 
                                                            id="headerSwitch_<?php echo $node['id']; ?>"
                                                            <?php echo ($node['desktop_menu_status'] ?? 'show') === 'show' ? 'checked' : ''; ?>
                                                            onchange="toggleHeaderMenuStatus(<?php echo $node['id']; ?>, this)"
                                                            style="cursor: pointer; width: 2.2em; height: 1.1em;">
                                                     <label class="form-check-label badge bg-<?php echo ($node['desktop_menu_status'] ?? 'show') === 'show' ? 'success' : 'secondary'; ?>" 
                                                            id="headerBadge_<?php echo $node['id']; ?>" 
                                                            for="headerSwitch_<?php echo $node['id']; ?>"
                                                            style="cursor: pointer;">
                                                         Header: <?php echo ucfirst($node['desktop_menu_status'] ?? 'show'); ?>
                                                     </label>
                                                 </div>
                                                <a href="<?php echo $editUrl; ?>" class="btn btn-outline-primary btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </div>

                                        <?php if ($hasChildren): ?>
                                            <div class="children mt-2">
                                                <?php renderMenuTree($node['children'], $depth + 1); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                }
                            }
                            renderMenuTree($categoryTree);
                            ?>
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
<script>
function toggleMenuChildren(el) {
    const container = el.closest('.menu-item').querySelector('.children');
    const icon = el.querySelector('i');
    if (container.classList.contains('collapsed')) {
        container.classList.remove('collapsed');
        icon.className = 'fas fa-chevron-down';
    } else {
        container.classList.add('collapsed');
        icon.className = 'fas fa-chevron-right';
    }
}

function toggleHeaderMenuStatus(categoryId, switchElem) {
    const isChecked = switchElem.checked;
    const newStatus = isChecked ? 'show' : 'hide';

    switchElem.disabled = true;

    const formData = new FormData();
    formData.append('action', 'toggle_header_status');
    formData.append('id', categoryId);
    formData.append('status', newStatus);

    fetch('header-menu-list.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        switchElem.disabled = false;
        if (data.success) {
            const badge = document.getElementById(`headerBadge_${categoryId}`);
            if (badge) {
                badge.textContent = 'Header: ' + newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                if (newStatus === 'show') {
                    badge.classList.remove('bg-secondary');
                    badge.classList.add('bg-success');
                } else {
                    badge.classList.remove('bg-success');
                    badge.classList.add('bg-secondary');
                }
            }
        } else {
            alert('Error updating header status: ' + (data.error || 'Unknown error'));
            switchElem.checked = !isChecked;
        }
    })
    .catch(err => {
        switchElem.disabled = false;
        alert('Network error while updating status.');
        switchElem.checked = !isChecked;
    });
}
</script>
</body>
</html>
