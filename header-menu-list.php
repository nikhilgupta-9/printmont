<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/AuthController.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$controller = new CategoryController();
$database = new Database();
$conn = $database->getConnection();

// Handle AJAX Status Toggle
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

// Read current layout design
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

// Calculate Stats
$totalCategoriesCount = count($flatCategories);
$visibleInHeaderCount = 0;
$hiddenInHeaderCount = 0;
$level1Count = 0;
$level2Count = 0;
$level3Count = 0;

foreach ($flatCategories as $cat) {
    if (($cat['desktop_menu_status'] ?? 'show') === 'show') {
        $visibleInHeaderCount++;
    } else {
        $hiddenInHeaderCount++;
    }
    $lvl = (int)($cat['level'] ?? 1);
    if ($lvl === 1) $level1Count++;
    elseif ($lvl === 2) $level2Count++;
    elseif ($lvl === 3) $level3Count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Header Menu Hierarchy &amp; Management | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f6f9; opacity: 0; }
        .card-executive { border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.03); }
        
        .stat-mini-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-mini-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .thumb-preview {
            width: 38px;
            height: 38px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            flex-shrink: 0;
        }

        /* Tree Item Hierarchy */
        .menu-tree-node {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
            position: relative;
        }
        .menu-tree-node:hover {
            box-shadow: 0 4px 14px rgba(0,0,0,0.05);
            border-color: #cbd5e1;
        }

        .level-1-node { border-left: 5px solid #1f509a; background-color: #ffffff; }
        .level-2-node { border-left: 5px solid #10b981; background-color: #f8fafc; margin-left: 28px; }
        .level-3-node { border-left: 5px solid #f59e0b; background-color: #ffffff; margin-left: 56px; }

        @media (max-width: 767.98px) {
            .level-2-node { margin-left: 14px; }
            .level-3-node { margin-left: 28px; }
            .thumb-preview { width: 32px; height: 32px; }
        }

        .toggle-chevron {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: #f1f5f9;
            color: #475569;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .toggle-chevron:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        .children-collapsed { display: none; }

        .search-filter-input {
            border-radius: 10px;
            padding: 0.55rem 1rem;
            border: 1px solid #cbd5e1;
        }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        
        <main class="content p-3 p-md-4">
            <div class="container-fluid p-0">

                <!-- Header Actions -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <div>
                        <h3 class="fw-bold mb-1">Header Category Menu — All Menus</h3>
                        <p class="text-muted mb-0">Full hierarchy of Top Icons (Home Page) and Inside Pages Top Menu across desktop &amp; mobile.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="add-top-icon.php" class="btn btn-primary">
                            <i data-feather="plus-circle" class="me-1"></i> Top Icon
                        </a>
                        <a href="add-menu-page.php" class="btn btn-outline-success">
                            <i data-feather="plus" class="me-1"></i> Top Menu Inside Pages
                        </a>
                        <a href="header-menu-design.php" class="btn btn-outline-primary">
                            <i data-feather="layout" class="me-1"></i> Select Design
                        </a>
                    </div>
                </div>

                <!-- Active Design Badges Strip -->
                <?php
                $hDesk = $data['home_desktop_design'] ?? $currentDesign;
                $hMob  = $data['home_mobile_design'] ?? 'design1';
                $iDesk = $data['inner_desktop_design'] ?? 'design1';
                $iMob  = $data['inner_mobile_design'] ?? 'design1';
                ?>
                <div class="card card-executive mb-4 p-3 bg-white">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="fw-bold text-dark small"><i class="fas fa-palette text-primary me-1"></i> Active Header Layout Designs:</div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-primary px-3 py-2">Home Desktop: <?php echo ucfirst($hDesk); ?></span>
                            <span class="badge bg-info text-dark px-3 py-2">Home Mobile: <?php echo ucfirst($hMob); ?></span>
                            <span class="badge bg-success px-3 py-2">Inner Desktop: <?php echo ucfirst($iDesk); ?></span>
                            <span class="badge bg-warning text-dark px-3 py-2">Inner Mobile: <?php echo ucfirst($iMob); ?></span>
                            <a href="header-menu-design.php" class="btn btn-xs btn-outline-primary ms-1 py-1 px-2" style="font-size:12px">Change</a>
                        </div>
                    </div>
                </div>

                <!-- Summary Metrics Row -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="stat-mini-card">
                            <div class="stat-mini-icon" style="background: #eef4fc; color: #1f509a;">
                                <i data-feather="folder"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Total Categories</div>
                                <div class="fw-bold fs-5 text-dark"><?php echo $totalCategoriesCount; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-mini-card">
                            <div class="stat-mini-icon" style="background: #ecfdf5; color: #059669;">
                                <i data-feather="eye"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Visible in Header</div>
                                <div class="fw-bold fs-5 text-success"><?php echo $visibleInHeaderCount; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-mini-card">
                            <div class="stat-mini-icon" style="background: #fef2f2; color: #dc2626;">
                                <i data-feather="eye-off"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Hidden from Header</div>
                                <div class="fw-bold fs-5 text-danger"><?php echo $hiddenInHeaderCount; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-mini-card">
                            <div class="stat-mini-icon" style="background: #f5f3ff; color: #7c3aed;">
                                <i data-feather="layers"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Tier Breakdown</div>
                                <div class="fw-semibold text-dark" style="font-size: 0.85rem;">
                                    <span class="text-primary fw-bold"><?php echo $level1Count; ?></span> L1 / 
                                    <span class="text-success fw-bold"><?php echo $level2Count; ?></span> L2 / 
                                    <span class="text-warning fw-bold"><?php echo $level3Count; ?></span> L3
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Tree Card -->
                <div class="card card-executive mb-4">
                    <div class="card-header bg-white border-bottom p-3 p-md-4">
                        <div class="row g-3 align-items-center justify-content-between">
                            
                            <!-- Search / Filter Input -->
                            <div class="col-12 col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i data-feather="search" style="width: 16px; height: 16px;"></i>
                                    </span>
                                    <input type="text" id="categorySearchInput" class="form-control border-start-0" placeholder="Filter category by name..." onkeyup="filterCategoryTree(this.value)">
                                </div>
                            </div>

                            <!-- Expand / Collapse & Filter Controls -->
                            <div class="col-12 col-md-7 text-md-end text-start">
                                <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="expandAllTree()">
                                        <i data-feather="maximize-2" style="width: 14px; height: 14px;" class="me-1"></i> Expand All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="collapseAllTree()">
                                        <i data-feather="minimize-2" style="width: 14px; height: 14px;" class="me-1"></i> Collapse All
                                    </button>
                                    <a href="view-categories.php" class="btn btn-sm btn-outline-primary">
                                        <i data-feather="grid" style="width: 14px; height: 14px;" class="me-1"></i> Full Categories Table
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-body p-3 p-md-4">
                        <?php if (empty($categoryTree)): ?>
                            <div class="text-center py-5">
                                <i data-feather="folder-minus" style="width: 48px; height: 48px;" class="text-muted mb-3"></i>
                                <h5>No categories found in database</h5>
                                <p class="text-muted">Create your first category to start configuring the header menu.</p>
                                <a href="add-main-category.php" class="btn btn-primary">Add Category</a>
                            </div>
                        <?php else: ?>
                            <div id="category_tree_root">
                                <?php
                                function renderExecutiveMenuTree(array $nodes, int $depth = 1) {
                                    foreach ($nodes as $node) {
                                        $hasChildren = !empty($node['children']);
                                        $level = (int)($node['level'] ?? 1);
                                        $isLevel1 = $level === 1;
                                        $isLevel2 = $level === 2;
                                        $isLevel3 = $level === 3;
                                        
                                        $editUrl = $isLevel1 ? ('add-top-icon.php?category_id=' . $node['id']) : ('add-menu-page.php?category_id=' . $node['id']);
                                        $fullEditUrl = 'edit-category.php?id=' . $node['id'];
                                        $thumb = !empty($node['desktop_menu_image']) ? $node['desktop_menu_image'] : (!empty($node['image']) ? $node['image'] : '');
                                        $status = strtolower($node['desktop_menu_status'] ?? 'show');
                                        if (empty($status)) $status = 'show';
                                        $isVisible = ($status === 'show');
                                        $nodeId = $node['id'];
                                        $name = htmlspecialchars($node['name'] ?? '');
                                        ?>
                                        <div class="menu-tree-node level-<?php echo $level; ?>-node p-2 p-md-3" data-name="<?php echo strtolower($name); ?>">
                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                
                                                <!-- Left info -->
                                                <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 220px;">
                                                    <?php if ($hasChildren): ?>
                                                        <span class="toggle-chevron" onclick="toggleNode(this)" title="Toggle subcategories">
                                                            <i data-feather="chevron-down" style="width: 16px; height: 16px;"></i>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="width: 28px; height: 28px; display: inline-block;"></span>
                                                    <?php endif; ?>

                                                    <?php if (!empty($thumb)): ?>
                                                        <img src="<?php echo htmlspecialchars($thumb); ?>" class="thumb-preview" alt="<?php echo $name; ?>" onerror="this.src='img/photos/unsplash-1.jpg'">
                                                    <?php else: ?>
                                                        <div class="thumb-preview d-flex align-items-center justify-content-center text-muted">
                                                            <i data-feather="image" style="width: 16px; height: 16px;"></i>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div>
                                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                                            <span class="fw-bold text-dark" style="font-size: 0.95rem;"><?php echo $name; ?></span>
                                                            
                                                            <!-- Level Badge -->
                                                            <?php if ($isLevel1): ?>
                                                                <span class="badge bg-primary px-2 py-1" style="font-size: 0.7rem;">Top Icon (L1)</span>
                                                            <?php elseif ($isLevel2): ?>
                                                                <span class="badge bg-success px-2 py-1" style="font-size: 0.7rem;">Sub (L2)</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.7rem;">Sub-Sub (L3)</span>
                                                            <?php endif; ?>

                                                            <?php if (!empty($node['desktop_menu_tag'])): ?>
                                                                <span class="badge bg-info text-dark px-2 py-1" style="font-size: 0.7rem;"><?php echo htmlspecialchars($node['desktop_menu_tag']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-muted small">
                                                            Slug: <code>/category/<?php echo htmlspecialchars($node['slug'] ?? ''); ?></code>
                                                            <?php if ($hasChildren): ?>
                                                                • <span class="fw-semibold text-primary"><?php echo count($node['children']); ?> sub-items</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Right controls -->
                                                <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                                    
                                                    <!-- Sort order badge -->
                                                    <span class="badge bg-light text-muted border px-2 py-1" title="Order: <?php echo (int)($node['desktop_menu_order'] ?? 0); ?>">
                                                        #<?php echo (int)($node['desktop_menu_order'] ?? 0); ?>
                                                    </span>

                                                    <!-- Visibility Toggle Switch -->
                                                    <div class="form-check form-switch mb-0 d-inline-flex align-items-center" title="Toggle visibility in header navigation">
                                                        <input class="form-check-input" 
                                                               type="checkbox" 
                                                               role="switch" 
                                                               id="switch_<?php echo $nodeId; ?>"
                                                               <?php echo $isVisible ? 'checked' : ''; ?>
                                                               onchange="toggleCategoryVisibility(<?php echo $nodeId; ?>, this)"
                                                               style="cursor: pointer; width: 2.2em; height: 1.1em;">
                                                        <label class="form-check-label badge <?php echo $isVisible ? 'bg-success' : 'bg-secondary'; ?> ms-1" 
                                                               id="badge_<?php echo $nodeId; ?>" 
                                                               for="switch_<?php echo $nodeId; ?>"
                                                               style="cursor: pointer; font-size: 0.75rem;">
                                                            <?php echo $isVisible ? 'Header: Shown' : 'Header: Hidden'; ?>
                                                        </label>
                                                    </div>

                                                    <!-- Edit & Action buttons -->
                                                    <a href="<?php echo $editUrl; ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="Edit Category">
                                                        <i data-feather="edit-2" style="width: 14px; height: 14px;"></i> Edit
                                                    </a>

                                                    <?php if ($isLevel1): ?>
                                                        <a href="add-sub-category.php?parent_id=<?php echo $nodeId; ?>" class="btn btn-sm btn-outline-success py-1 px-2" title="Add child under this category">
                                                            <i data-feather="plus" style="width: 14px; height: 14px;"></i> +Sub
                                                        </a>
                                                    <?php elseif ($isLevel2): ?>
                                                        <a href="add-sub-sub-category.php?parent_id=<?php echo $nodeId; ?>" class="btn btn-sm btn-outline-warning text-dark py-1 px-2" title="Add sub-sub category">
                                                            <i data-feather="plus" style="width: 14px; height: 14px;"></i> +Sub
                                                        </a>
                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                            <!-- Recursive Children Container -->
                                            <?php if ($hasChildren): ?>
                                                <div class="children-container mt-2 pt-2 border-top">
                                                    <?php renderExecutiveMenuTree($node['children'], $depth + 1); ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>
                                        <?php
                                    }
                                }
                                renderExecutiveMenuTree($categoryTree);
                                ?>
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
<script>
    function toggleNode(el) {
        var node = el.closest('.menu-tree-node');
        var container = node.querySelector('.children-container');
        if (!container) return;

        if (container.classList.contains('children-collapsed')) {
            container.classList.remove('children-collapsed');
            el.innerHTML = '<i data-feather="chevron-down" style="width: 16px; height: 16px;"></i>';
        } else {
            container.classList.add('children-collapsed');
            el.innerHTML = '<i data-feather="chevron-right" style="width: 16px; height: 16px;"></i>';
        }
        if (typeof feather !== 'undefined') feather.replace();
    }

    function expandAllTree() {
        document.querySelectorAll('.children-container').forEach(function(c) {
            c.classList.remove('children-collapsed');
        });
        document.querySelectorAll('.toggle-chevron').forEach(function(btn) {
            btn.innerHTML = '<i data-feather="chevron-down" style="width: 16px; height: 16px;"></i>';
        });
        if (typeof feather !== 'undefined') feather.replace();
    }

    function collapseAllTree() {
        document.querySelectorAll('.children-container').forEach(function(c) {
            c.classList.add('children-collapsed');
        });
        document.querySelectorAll('.toggle-chevron').forEach(function(btn) {
            btn.innerHTML = '<i data-feather="chevron-right" style="width: 16px; height: 16px;"></i>';
        });
        if (typeof feather !== 'undefined') feather.replace();
    }

    function filterCategoryTree(searchVal) {
        var query = searchVal.trim().toLowerCase();
        var nodes = document.querySelectorAll('.menu-tree-node');

        if (!query) {
            nodes.forEach(function(n) {
                n.style.display = 'block';
            });
            return;
        }

        nodes.forEach(function(n) {
            var name = n.getAttribute('data-name') || '';
            if (name.includes(query)) {
                n.style.display = 'block';
                // Show all parent containers
                var parent = n.parentElement.closest('.menu-tree-node');
                while (parent) {
                    parent.style.display = 'block';
                    var childContainer = parent.querySelector('.children-container');
                    if (childContainer) childContainer.classList.remove('children-collapsed');
                    parent = parent.parentElement.closest('.menu-tree-node');
                }
            } else {
                n.style.display = 'none';
            }
        });
        if (typeof feather !== 'undefined') feather.replace();
    }

    function toggleCategoryVisibility(catId, switchElem) {
        var isChecked = switchElem.checked;
        var newStatus = isChecked ? 'show' : 'hide';

        switchElem.disabled = true;

        var formData = new FormData();
        formData.append('action', 'toggle_header_status');
        formData.append('id', catId);
        formData.append('status', newStatus);

        fetch('header-menu-list.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            switchElem.disabled = false;
            if (data.success) {
                var badge = document.getElementById('badge_' + catId);
                if (badge) {
                    badge.textContent = newStatus === 'show' ? 'Header: Shown' : 'Header: Hidden';
                    if (newStatus === 'show') {
                        badge.classList.remove('bg-secondary');
                        badge.classList.add('bg-success');
                    } else {
                        badge.classList.remove('bg-success');
                        badge.classList.add('bg-secondary');
                    }
                }
            } else {
                alert('Error updating status: ' + (data.error || 'Unknown error'));
                switchElem.checked = !isChecked;
            }
        })
        .catch(function(err) {
            switchElem.disabled = false;
            alert('Network communication error.');
            switchElem.checked = !isChecked;
        });
    }
</script>
</body>
</html>
