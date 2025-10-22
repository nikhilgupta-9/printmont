<?php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/LogoController.php';

// Initialize Logo Controller
$database = new Database();
$db = $database->getConnection();
$logoController = new LogoController($db);

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $result = $logoController->createLogo($_POST, $_FILES['logo_file']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'danger';
    } elseif ($action == 'update') {
        $result = $logoController->updateLogo($_POST['id'], $_POST, $_FILES['logo_file'] ?? null);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'danger';
    }
}

// Get all logos for display
$logos_result = $logoController->getAllLogos();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="AdminKit">
    <meta name="keywords"
        content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />

    <link rel="canonical" href="index.html" />

    <title>Logo Management - Admin Panel</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .logo-preview {
            max-width: 200px;
            max-height: 100px;
            border: 1px solid #ddd;
            padding: 5px;
            margin: 5px 0;
        }
        .asset-type-badge {
            font-size: 0.75em;
        }
        .active-logo {
            border-left: 4px solid #28a745;
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
                            <h3><strong>Logo</strong> Management</h3>
                        </div>

                        <div class="col-auto ms-auto text-end mt-n1">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLogoModal">
                                <i class="fas fa-plus"></i> Add New Logo
                            </button>
                        </div>
                    </div>

                    <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Logos</h5>
                                    <h6 class="card-subtitle text-muted">Manage website logos and assets.</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="logosTable">
                                            <thead>
                                                <tr>
                                                    <th>Preview</th>
                                                    <th>Asset Name</th>
                                                    <th>Type</th>
                                                    <th>Dimensions</th>
                                                    <th>Size</th>
                                                    <th>Version</th>
                                                    <th>Status</th>
                                                    <th>Uploaded</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                if ($logos_result && $logos_result->num_rows > 0) {
                                                    while ($logo = $logos_result->fetch_assoc()): 
                                                ?>
                                                <tr class="<?php echo $logo['is_active'] ? 'active-logo' : ''; ?>">
                                                    <td>
                                                        <?php if (in_array($logo['file_extension'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                                        <img src="<?php echo $logo['file_path'] . $logo['file_name']; ?>" 
                                                             class="logo-preview" alt="<?php echo $logo['alt_text']; ?>">
                                                        <?php else: ?>
                                                        <div class="logo-preview text-center">
                                                            <small><?php echo strtoupper($logo['file_extension']); ?></small>
                                                        </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $logo['asset_name']; ?></td>
                                                    <td>
                                                        <span class="badge bg-primary asset-type-badge">
                                                            <?php echo str_replace('_', ' ', $logo['asset_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $logo['dimensions'] ?: 'N/A'; ?></td>
                                                    <td><?php echo round($logo['file_size'] / 1024, 2); ?> KB</td>
                                                    <td>v<?php echo $logo['version']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $logo['is_active'] ? 'success' : 'secondary'; ?>">
                                                            <?php echo $logo['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($logo['upload_timestamp'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" 
                                                                onclick="editLogo(<?php echo $logo['id']; ?>)"
                                                                data-bs-toggle="tooltip" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" 
                                                                onclick="deleteLogo(<?php echo $logo['id']; ?>)"
                                                                data-bs-toggle="tooltip" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php if (!$logo['is_active']): ?>
                                                        <button class="btn btn-sm btn-success" 
                                                                onclick="activateLogo(<?php echo $logo['id']; ?>)"
                                                                data-bs-toggle="tooltip" title="Activate">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endwhile;
                                                } else {
                                                    echo '<tr><td colspan="9" class="text-center">No logos found</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Add Logo Modal -->
    <div class="modal fade" id="addLogoModal" tabindex="-1" aria-labelledby="addLogoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLogoModalLabel">Add New Logo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="logoForm" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="asset_type">Asset Type</label>
                                <select class="form-control" id="asset_type" name="asset_type" required>
                                    <option value="">Select Asset Type</option>
                                    <option value="mobile_logo">Mobile Logo</option>
                                    <option value="desktop_logo">Desktop Logo</option>
                                    <option value="favicon">Favicon</option>
                                    <option value="email_logo">Email Logo</option>
                                    <option value="footer_logo">Footer Logo</option>
                                    <option value="social_media_icon">Social Media Icon</option>
                                    <option value="payment_icon">Payment Icon</option>
                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="asset_name">Asset Name</label>
                                <input type="text" class="form-control" id="asset_name" name="asset_name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="logo_file">Logo File</label>
                            <input type="file" class="form-control" id="logo_file" name="logo_file" accept=".jpg,.jpeg,.png,.gif,.svg,.ico,.webp" required>
                            <div class="form-text">Allowed formats: JPG, PNG, GIF, SVG, ICO, WebP. Max size: 5MB</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>

                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="alt_text">Alt Text</label>
                                <input type="text" class="form-control" id="alt_text" name="alt_text">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label" for="target_url">Target URL</label>
                                <input type="url" class="form-control" id="target_url" name="target_url">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    Set as active version
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload Logo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function editLogo(id) {
            // Implement edit functionality
            alert('Edit logo with ID: ' + id);
            // You can implement AJAX call to fetch logo details and populate edit modal
        }

        function deleteLogo(id) {
            if (confirm('Are you sure you want to delete this logo?')) {
                // Implement delete functionality
                window.location.href = 'delete-logo.php?id=' + id;
            }
        }

        function activateLogo(id) {
            if (confirm('Are you sure you want to activate this logo?')) {
                // Implement activate functionality
                window.location.href = 'activate-logo.php?id=' + id;
            }
        }

        // File preview
        document.getElementById('logo_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You can show preview here if needed
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>