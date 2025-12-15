<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SocialLinkController.php';

$socialLinkController = new SocialLinkController();

// Filters
$filters = [
    'status' => $_GET['status'] ?? ''
];

$social_links = $socialLinkController->getAllSocialLinks($filters);
$platform_options = $socialLinkController->getPlatformOptions();

// Check for messages
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
    <title>Social Links | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .social-table th { font-weight: 600; background-color: #f8f9fa; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .social-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .filter-card { background-color: #f8f9fa; }
        .platform-badge { font-size: 11px; padding: 3px 6px; }
        .url-text { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
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
                            <h3><strong>Social</strong> Links</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="add-social-link.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Social Link
                            </a>
                        </div>
                    </div>

                    <!-- Messages -->
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

                    <!-- Filters -->
                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control" onchange="this.form.submit()">
                                        <option value="">All Statuses</option>
                                        <option value="active" <?php echo $filters['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $filters['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-9 d-flex align-items-end">
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Manage your social media links displayed on the website.
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Social Links</h5>
                                    <h6 class="card-subtitle text-muted">Manage social media links for your website footer and contact pages.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($social_links)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-share-alt fa-3x text-muted mb-3"></i>
                                            <h5>No Social Links Found</h5>
                                            <p class="text-muted">Get started by adding your first social media link.</p>
                                            <a href="add-social-link.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Social Link
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover social-table">
                                                <thead>
                                                    <tr>
                                                        <th>Platform</th>
                                                        <th>URL</th>
                                                        <th>Icon</th>
                                                        <th>Order</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($social_links as $link): 
                                                        $platform_info = $platform_options[$link['platform']] ?? ['name' => ucfirst($link['platform']), 'color' => '#6c757d'];
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="social-icon me-3" style="background-color: <?php echo $platform_info['color']; ?>">
                                                                        <i class="<?php echo $link['icon']; ?>"></i>
                                                                    </div>
                                                                    <div>
                                                                        <strong><?php echo htmlspecialchars($platform_info['name']); ?></strong>
                                                                        <br>
                                                                        <span class="badge bg-secondary platform-badge"><?php echo htmlspecialchars($link['platform']); ?></span>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="url-text" title="<?php echo htmlspecialchars($link['url']); ?>">
                                                                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="text-decoration-none">
                                                                        <?php echo htmlspecialchars($link['url']); ?>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($link['icon']); ?></code>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark">
                                                                    <?php echo $link['display_order']; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $link['status']; ?>">
                                                                    <?php echo ucfirst($link['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                                                                   target="_blank" 
                                                                   class="btn btn-sm btn-outline-primary" 
                                                                   title="Visit Link">
                                                                   <i class="fas fa-external-link-alt"></i>
                                                                </a>
                                                                <a href="edit-social-link.php?id=<?php echo $link['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-warning" 
                                                                   title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="delete-social-link.php?id=<?php echo $link['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this social link?')"
                                                                   title="Delete">
                                                                   <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Quick Stats -->
                                        <div class="row mt-4">
                                            <div class="col-md-4">
                                                <div class="card bg-primary text-white">
                                                    <div class="card-body text-center">
                                                        <h4><?php echo count(array_filter($social_links, function($link) { return $link['status'] === 'active'; })); ?></h4>
                                                        <p class="mb-0">Active Links</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-info text-white">
                                                    <div class="card-body text-center">
                                                        <h4><?php echo count($social_links); ?></h4>
                                                        <p class="mb-0">Total Links</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-success text-white">
                                                    <div class="card-body text-center">
                                                        <h4><?php echo count($platform_options); ?></h4>
                                                        <p class="mb-0">Available Platforms</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
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