<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$message = '';
$message_type = '';

// Handle Direct POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $section_name = trim($_POST['section_name'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $subtext = trim($_POST['subtext'] ?? '');
        $icon_name = trim($_POST['icon_name'] ?? 'default');
        $url = trim($_POST['url'] ?? '#');
        $display_order = intval($_POST['display_order'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (empty($title)) {
            $message = 'Title is required!';
            $message_type = 'danger';
        } else {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE quick_links SET section_name = ?, title = ?, subtext = ?, icon_name = ?, url = ?, display_order = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssssisi", $section_name, $title, $subtext, $icon_name, $url, $display_order, $status, $id);
                if ($stmt->execute()) {
                    $message = 'Quick Link updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Database error: ' . $db->error;
                    $message_type = 'danger';
                }
            } else {
                $stmt = $db->prepare("INSERT INTO quick_links (section_name, title, subtext, icon_name, url, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssis", $section_name, $title, $subtext, $icon_name, $url, $display_order, $status);
                if ($stmt->execute()) {
                    $message = 'Quick Link created successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Database error: ' . $db->error;
                    $message_type = 'danger';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM quick_links WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Quick Link deleted successfully!';
                $message_type = 'success';
            }
        }
    } elseif ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE quick_links SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = 'Status toggled successfully!';
            $message_type = 'success';
        }
    }
}

// Fetch all quick links
$links = [];
$res = $db->query("SELECT * FROM quick_links ORDER BY display_order ASC, id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $links[] = $row;
    }
}

// Stats
$total_links = count($links);
$active_links = count(array_filter($links, fn($l) => $l['status'] === 'active'));
$sections = array_unique(array_filter(array_map(fn($l) => $l['section_name'], $links)));
$total_sections = count($sections);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Quick Links Manager | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-active { background: #dcfce7; color: #166534; font-weight: 600; }
        .badge-inactive { background: #fee2e2; color: #991b1b; font-weight: 600; }
        .icon-circle { width: 36px; height: 36px; border-radius: 50%; background: #f1f5f9; display: inline-flex; align-items: center; justify-content: center; color: #0b53a1; }
        .section-tag { background: #f3f4f6; color: #374151; font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 600; text-transform: uppercase; }
        .table td { vertical-align: middle; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>

            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3 align-items-center">
                        <div class="col-auto d-none d-sm-block">
                            <h3 class="mb-0"><strong>Quick Links</strong> Manager (Mobile & Desktop)</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="http://localhost:5173/quick-links" target="_blank" class="btn btn-outline-primary me-2">
                                <i class="fas fa-external-link-alt me-1"></i> Preview Live Page
                            </a>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#linkModal" onclick="resetForm()">
                                <i class="fas fa-plus me-1"></i> Add Quick Link
                            </button>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Stats Cards -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-body d-flex align-items-center">
                                    <div class="icon-circle bg-primary-subtle text-primary me-3" style="width:48px;height:48px;">
                                        <i class="fas fa-link fa-lg"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0 fw-bold"><?php echo $total_links; ?></h4>
                                        <small class="text-muted">Total Quick Links</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-body d-flex align-items-center">
                                    <div class="icon-circle bg-success-subtle text-success me-3" style="width:48px;height:48px;">
                                        <i class="fas fa-check-circle fa-lg"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0 fw-bold"><?php echo $active_links; ?></h4>
                                        <small class="text-muted">Active Links</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-body d-flex align-items-center">
                                    <div class="icon-circle bg-info-subtle text-info me-3" style="width:48px;height:48px;">
                                        <i class="fas fa-layer-group fa-lg"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0 fw-bold"><?php echo $total_sections ?: 1; ?></h4>
                                        <small class="text-muted">Configured Sections</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Links Table -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0">Configured Quick Links</h5>
                                    <small class="text-muted">Control all mobile quick navigation links and section groupings in real-time</small>
                                </div>
                                <div class="col-auto">
                                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search link title or section..." onkeyup="filterTable()">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="linksTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th style="width: 60px;">Icon</th>
                                        <th>Title & Subtitle</th>
                                        <th>Section</th>
                                        <th>Destination URL</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th class="text-end" style="width: 150px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($links)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">No quick links configured yet. Click "Add Quick Link" to get started.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($links as $index => $item): ?>
                                            <tr>
                                                <td class="text-muted"><?php echo $index + 1; ?></td>
                                                <td>
                                                    <div class="icon-circle">
                                                        <i class="fas fa-<?php 
                                                            $iconMap = [
                                                                'truck' => 'truck-fast',
                                                                'account' => 'user-circle',
                                                                'help' => 'circle-question',
                                                                'business' => 'briefcase',
                                                                'bulk' => 'boxes-stacked',
                                                                'support' => 'headset',
                                                                'franchise' => 'store',
                                                                'seller' => 'handshake',
                                                                'about' => 'circle-info',
                                                                'blog' => 'newspaper',
                                                                'career' => 'user-tie',
                                                                'affiliate' => 'users-rectangle',
                                                                'security' => 'shield-halved',
                                                                'sitemap' => 'sitemap',
                                                                'contact' => 'phone',
                                                                'terms' => 'file-contract',
                                                                'privacy' => 'lock',
                                                                'shipping' => 'dolly',
                                                                'refund' => 'rotate-left',
                                                                'terms_of_use' => 'scale-balanced'
                                                            ];
                                                            echo $iconMap[$item['icon_name']] ?? 'link';
                                                        ?>"></i>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['title']); ?></div>
                                                    <?php if ($item['subtext']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($item['subtext']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($item['section_name']): ?>
                                                        <span class="section-tag"><?php echo htmlspecialchars($item['section_name']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Top Level</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($item['url']); ?>" target="_blank" class="text-decoration-none text-primary">
                                                        <code><?php echo htmlspecialchars($item['url']); ?></code>
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo $item['display_order']; ?></span>
                                                </td>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="toggle">
                                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" class="badge border-0 <?php echo $item['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>" style="cursor:pointer;" title="Click to toggle status">
                                                            <?php echo ucfirst($item['status']); ?>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-secondary me-1" onclick='editLink(<?php echo json_encode($item); ?>)' title="Edit Link">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this quick link?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Link">
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
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Modal for Add / Edit Quick Link -->
    <div class="modal fade" id="linkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" id="quickLinkForm">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="link_id" value="0">
                    
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalTitle">Add Quick Link</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Section Group</label>
                            <input list="sectionsList" name="section_name" id="section_name" class="form-control" placeholder="e.g. SUPPORT : or leave blank for top-level">
                            <datalist id="sectionsList">
                                <option value="">Top Level (No Heading)</option>
                                <option value="SUPPORT :">SUPPORT :</option>
                                <option value="Need More Help?">Need More Help?</option>
                                <option value="OUR COMPANY :">OUR COMPANY :</option>
                                <option value="POLICY INFO :">POLICY INFO :</option>
                            </datalist>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Link Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. Business Solutions">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Subtitle / Description</label>
                            <input type="text" name="subtext" id="subtext" class="form-control" placeholder="e.g. Answer to all your business related solutions.">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Icon</label>
                                <select name="icon_name" id="icon_name" class="form-select">
                                    <option value="truck">🚚 Delivery / Track Truck</option>
                                    <option value="account">👤 Account / User</option>
                                    <option value="help">❓ Help / FAQ</option>
                                    <option value="business">💼 Business Solutions</option>
                                    <option value="bulk">📦 Bulk Orders</option>
                                    <option value="support">🎧 Help Center Support</option>
                                    <option value="franchise">🏪 Franchise Store</option>
                                    <option value="seller">🤝 Become a Seller</option>
                                    <option value="about">ℹ️ About Us</option>
                                    <option value="blog">📰 Blog & Articles</option>
                                    <option value="career">👔 Careers & Jobs</option>
                                    <option value="affiliate">👥 Affiliate Program</option>
                                    <option value="security">🛡️ Safe & Security</option>
                                    <option value="sitemap">🌐 Sitemap Pages</option>
                                    <option value="contact">📞 Contact Us</option>
                                    <option value="terms">📄 Terms & Conditions</option>
                                    <option value="privacy">🔒 Privacy Policy</option>
                                    <option value="shipping">🚚 Shipping Policy</option>
                                    <option value="refund">🔄 Return & Refund</option>
                                    <option value="terms_of_use">⚖️ Terms of Use</option>
                                    <option value="default">🔗 Standard Link</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Display Order</label>
                                <input type="number" name="display_order" id="display_order" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Destination URL <span class="text-danger">*</span></label>
                            <input type="text" name="url" id="url" class="form-control" required placeholder="/business-solutions">
                            <small class="text-muted">Relative paths like <code>/help-center</code> or full URLs</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveBtn">Save Quick Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function resetForm() {
            document.getElementById('modalTitle').innerText = 'Add Quick Link';
            document.getElementById('link_id').value = '0';
            document.getElementById('section_name').value = '';
            document.getElementById('title').value = '';
            document.getElementById('subtext').value = '';
            document.getElementById('icon_name').value = 'default';
            document.getElementById('url').value = '/';
            document.getElementById('display_order').value = '0';
            document.getElementById('status').value = 'active';
            document.getElementById('saveBtn').innerText = 'Create Quick Link';
        }

        function editLink(item) {
            document.getElementById('modalTitle').innerText = 'Edit Quick Link';
            document.getElementById('link_id').value = item.id;
            document.getElementById('section_name').value = item.section_name || '';
            document.getElementById('title').value = item.title || '';
            document.getElementById('subtext').value = item.subtext || '';
            document.getElementById('icon_name').value = item.icon_name || 'default';
            document.getElementById('url').value = item.url || '';
            document.getElementById('display_order').value = item.display_order || 0;
            document.getElementById('status').value = item.status || 'active';
            document.getElementById('saveBtn').innerText = 'Update Quick Link';
            
            var myModal = new bootstrap.Modal(document.getElementById('linkModal'));
            myModal.show();
        }

        function filterTable() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#linksTable tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
