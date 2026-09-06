<?php
session_start();
require_once 'config/database.php';
require_once 'controllers/SitemapController.php';

$sitemapController = new SitemapController();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate_xml') {
        $baseUrl = !empty($_POST['base_url']) ? trim($_POST['base_url']) : 'http://localhost:5173';
        $res = $sitemapController->generateXmlSitemap($baseUrl);
        $_SESSION['success_message'] = $res['message'];
        header("Location: sitemap-management.php");
        exit;
    }

    if ($action === 'add') {
        $data = [
            'title' => $_POST['title'] ?? '',
            'url' => $_POST['url'] ?? '',
            'category' => $_POST['category'] ?? 'main_pages',
            'changefreq' => $_POST['changefreq'] ?? 'weekly',
            'priority' => $_POST['priority'] ?? 0.8,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => $_POST['sort_order'] ?? 0
        ];
        $res = $sitemapController->createEntry($data);
        if ($res['success']) {
            $_SESSION['success_message'] = $res['message'];
        } else {
            $_SESSION['error_message'] = $res['message'];
        }
        header("Location: sitemap-management.php");
        exit;
    }

    if ($action === 'edit') {
        $id = $_POST['id'] ?? 0;
        $data = [
            'title' => $_POST['title'] ?? '',
            'url' => $_POST['url'] ?? '',
            'category' => $_POST['category'] ?? 'main_pages',
            'changefreq' => $_POST['changefreq'] ?? 'weekly',
            'priority' => $_POST['priority'] ?? 0.8,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => $_POST['sort_order'] ?? 0
        ];
        $res = $sitemapController->updateEntry($id, $data);
        if ($res['success']) {
            $_SESSION['success_message'] = $res['message'];
        } else {
            $_SESSION['error_message'] = $res['message'];
        }
        header("Location: sitemap-management.php");
        exit;
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $res = $sitemapController->deleteEntry($id);
        if ($res['success']) {
            $_SESSION['success_message'] = $res['message'];
        } else {
            $_SESSION['error_message'] = $res['message'];
        }
        header("Location: sitemap-management.php");
        exit;
    }

    if ($action === 'toggle_status') {
        $id = $_POST['id'] ?? 0;
        $sitemapController->toggleStatus($id);
        echo json_encode(['success' => true]);
        exit;
    }
}

// Filter
$category_filter = $_GET['category'] ?? 'all';
$entries = $sitemapController->getAllEntries($category_filter, false);
$stats = $sitemapController->getSitemapStats();

// Flash messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sitemap Management | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .badge-cat-main_pages { background-color: #3b7ddd; color: #fff; }
        .badge-cat-products_categories { background-color: #1cbb8c; color: #fff; }
        .badge-cat-company_info { background-color: #6f42c1; color: #fff; }
        .badge-cat-help_support { background-color: #17a2b8; color: #fff; }
        .badge-cat-legal_policies { background-color: #6c757d; color: #fff; }
        .badge-cat-custom { background-color: #fd7e14; color: #fff; }
        .stat-card-icon { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
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
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-6">
                            <h1 class="h3 mb-0 text-dark fw-bold">
                                <i class="fas fa-sitemap me-2 text-primary"></i>Sitemap Management
                            </h1>
                            <p class="text-muted small mb-0">Manage XML & HTML sitemap URLs, priorities, and SEO synchronization</p>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateXmlModal">
                                <i class="fas fa-sync-alt me-1"></i> Generate XML Sitemap
                            </button>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                                <i class="fas fa-plus me-1"></i> Add Sitemap URL
                            </button>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Stats Row -->
                    <div class="row mb-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body d-flex align-items-center justify-content-between p-3">
                                    <div>
                                        <h6 class="text-muted small mb-1">Total Configured URLs</h6>
                                        <h3 class="fw-bold mb-0 text-dark"><?php echo count($entries); ?></h3>
                                    </div>
                                    <div class="stat-card-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="fas fa-link"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body d-flex align-items-center justify-content-between p-3">
                                    <div>
                                        <h6 class="text-muted small mb-1">Total XML URLs</h6>
                                        <h3 class="fw-bold mb-0 text-success"><?php echo $stats['total_urls'] ?? count($entries); ?></h3>
                                    </div>
                                    <div class="stat-card-icon bg-success bg-opacity-10 text-success">
                                        <i class="fas fa-file-code"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body d-flex align-items-center justify-content-between p-3">
                                    <div>
                                        <h6 class="text-muted small mb-1">XML Sitemap Status</h6>
                                        <h4 class="fw-bold mb-0 text-<?php echo !empty($stats['xml_exists']) ? 'success' : 'warning'; ?>">
                                            <?php echo !empty($stats['xml_exists']) ? 'Generated & Live' : 'Not Generated'; ?>
                                        </h4>
                                    </div>
                                    <div class="stat-card-icon bg-info bg-opacity-10 text-info">
                                        <i class="fas fa-check-double"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body d-flex align-items-center justify-content-between p-3">
                                    <div>
                                        <h6 class="text-muted small mb-1">Last Generated</h6>
                                        <span class="small fw-semibold text-dark">
                                            <?php echo !empty($stats['last_generated']) ? date('M j, Y g:i A', strtotime($stats['last_generated'])) : 'Never'; ?>
                                        </span>
                                    </div>
                                    <div class="stat-card-icon bg-secondary bg-opacity-10 text-secondary">
                                        <i class="far fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Tabs & Table -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom p-3">
                            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                                <ul class="nav nav-pills card-header-pills">
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $category_filter === 'all' ? 'active' : ''; ?>" href="sitemap-management.php?category=all">
                                            All Links (<?php echo count($sitemapController->getAllEntries(null, false)); ?>)
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $category_filter === 'main_pages' ? 'active' : ''; ?>" href="sitemap-management.php?category=main_pages">
                                            Main Pages
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $category_filter === 'company_info' ? 'active' : ''; ?>" href="sitemap-management.php?category=company_info">
                                            Company
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $category_filter === 'help_support' ? 'active' : ''; ?>" href="sitemap-management.php?category=help_support">
                                            Help &amp; Support
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $category_filter === 'legal_policies' ? 'active' : ''; ?>" href="sitemap-management.php?category=legal_policies">
                                            Policies
                                        </a>
                                    </li>
                                </ul>

                                <div class="d-flex gap-2">
                                    <a href="sitemap.xml" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-external-link-alt me-1"></i> View sitemap.xml
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Page Title</th>
                                            <th>URL / Route Path</th>
                                            <th>Category</th>
                                            <th>Frequency</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th class="text-end" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($entries)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted">
                                                    <i class="fas fa-link fa-2x mb-2 d-block opacity-50"></i>
                                                    No sitemap entries found for this category.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($entries as $idx => $entry): ?>
                                                <tr>
                                                    <td><?php echo $idx + 1; ?></td>
                                                    <td class="fw-semibold text-dark">
                                                        <?php echo htmlspecialchars($entry['title']); ?>
                                                    </td>
                                                    <td>
                                                        <a href="http://localhost:5173<?php echo htmlspecialchars($entry['url']); ?>" target="_blank" class="text-decoration-none text-primary">
                                                            <?php echo htmlspecialchars($entry['url']); ?>
                                                            <i class="fas fa-external-link-alt ms-1 small" style="font-size: 10px;"></i>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-cat-<?php echo htmlspecialchars($entry['category']); ?> px-2 py-1">
                                                            <?php echo ucwords(str_replace('_', ' ', $entry['category'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($entry['changefreq']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                            <?php echo number_format($entry['priority'], 1); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                                data-id="<?php echo $entry['id']; ?>"
                                                                <?php echo $entry['is_active'] ? 'checked' : ''; ?>>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-outline-primary me-1 edit-btn"
                                                            data-id="<?php echo $entry['id']; ?>"
                                                            data-title="<?php echo htmlspecialchars($entry['title']); ?>"
                                                            data-url="<?php echo htmlspecialchars($entry['url']); ?>"
                                                            data-category="<?php echo htmlspecialchars($entry['category']); ?>"
                                                            data-changefreq="<?php echo htmlspecialchars($entry['changefreq']); ?>"
                                                            data-priority="<?php echo htmlspecialchars($entry['priority']); ?>"
                                                            data-active="<?php echo $entry['is_active']; ?>"
                                                            data-sort="<?php echo $entry['sort_order']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>

                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this URL?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Add Entry Modal -->
    <div class="modal fade" id="addEntryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Add Sitemap URL</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Page Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" placeholder="e.g. Bulk Merchandise" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Route Path / URL <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="url" placeholder="e.g. /bulk-order or https://..." required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Category</label>
                                <select class="form-select" name="category">
                                    <option value="main_pages">Main Pages</option>
                                    <option value="company_info">Company &amp; About</option>
                                    <option value="help_support">Help &amp; Support</option>
                                    <option value="legal_policies">Legal &amp; Policies</option>
                                    <option value="custom">Custom Links</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Change Frequency</label>
                                <select class="form-select" name="changefreq">
                                    <option value="daily">Daily</option>
                                    <option value="weekly" selected>Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Priority (0.1 - 1.0)</label>
                                <select class="form-select" name="priority">
                                    <option value="1.0">1.0 (Highest)</option>
                                    <option value="0.9">0.9</option>
                                    <option value="0.8" selected>0.8 (Standard)</option>
                                    <option value="0.7">0.7</option>
                                    <option value="0.6">0.6</option>
                                    <option value="0.5">0.5</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" value="0">
                            </div>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="addActive">
                            <label class="form-check-label" for="addActive">Active in Sitemap</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save URL</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Entry Modal -->
    <div class="modal fade" id="editEntryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Sitemap URL</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Page Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="editTitle" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Route Path / URL <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="url" id="editUrl" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Category</label>
                                <select class="form-select" name="category" id="editCategory">
                                    <option value="main_pages">Main Pages</option>
                                    <option value="company_info">Company &amp; About</option>
                                    <option value="help_support">Help &amp; Support</option>
                                    <option value="legal_policies">Legal &amp; Policies</option>
                                    <option value="custom">Custom Links</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Change Frequency</label>
                                <select class="form-select" name="changefreq" id="editChangefreq">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Priority (0.1 - 1.0)</label>
                                <select class="form-select" name="priority" id="editPriority">
                                    <option value="1.0">1.0 (Highest)</option>
                                    <option value="0.9">0.9</option>
                                    <option value="0.8">0.8 (Standard)</option>
                                    <option value="0.7">0.7</option>
                                    <option value="0.6">0.6</option>
                                    <option value="0.5">0.5</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" id="editSort">
                            </div>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive">
                            <label class="form-check-label" for="editActive">Active in Sitemap</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update URL</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Generate XML Modal -->
    <div class="modal fade" id="generateXmlModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="generate_xml">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Generate XML Sitemap</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            This will automatically build a compliant <code>sitemap.xml</code> indexing all active pages, product categories, career vacancies, and catalog items.
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Frontend Base URL</label>
                            <input type="url" class="form-control" name="base_url" value="http://localhost:5173" required>
                            <small class="text-muted">Base domain used for generating absolute XML URLs.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-play me-1"></i> Generate Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit modal population
            const editBtns = document.querySelectorAll('.edit-btn');
            const editModal = new bootstrap.Modal(document.getElementById('editEntryModal'));
            
            editBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('editId').value = this.dataset.id;
                    document.getElementById('editTitle').value = this.dataset.title;
                    document.getElementById('editUrl').value = this.dataset.url;
                    document.getElementById('editCategory').value = this.dataset.category;
                    document.getElementById('editChangefreq').value = this.dataset.changefreq;
                    document.getElementById('editPriority').value = this.dataset.priority;
                    document.getElementById('editSort').value = this.dataset.sort;
                    document.getElementById('editActive').checked = (this.dataset.active == '1');
                    editModal.show();
                });
            });

            // Live status toggle
            const toggles = document.querySelectorAll('.status-toggle');
            toggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const id = this.dataset.id;
                    const formData = new FormData();
                    formData.append('action', 'toggle_status');
                    formData.append('id', id);
                    fetch('sitemap-management.php', {
                        method: 'POST',
                        body: formData
                    });
                });
            });
        });
    </script>
</body>
</html>
