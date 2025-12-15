<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SocialLinkController.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid social link ID!";
    header("Location: social-links.php");
    exit();
}

$linkId = (int)$_GET['id'];
$socialLinkController = new SocialLinkController();
$social_link = $socialLinkController->getSocialLinkById($linkId);
$platform_options = $socialLinkController->getPlatformOptions();

if (!$social_link) {
    $_SESSION['error_message'] = "Social link not found!";
    header("Location: social-links.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Social Link | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .platform-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .platform-icon-large { font-size: 3rem; margin-bottom: 15px; }
        .info-card { border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <!-- Platform Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card platform-header">
                                <div class="card-body text-center">
                                    <div class="platform-icon-large">
                                        <i class="<?php echo htmlspecialchars($social_link['icon']); ?>"></i>
                                    </div>
                                    <h2 class="card-title text-white mb-1">
                                        <?php echo htmlspecialchars($platform_options[$social_link['platform']]['name'] ?? ucfirst($social_link['platform'])); ?>
                                    </h2>
                                    <p class="text-white-50 mb-0">Social Media Link Details</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Link Information -->
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Link Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Platform:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($platform_options[$social_link['platform']]['name'] ?? ucfirst($social_link['platform'])); ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Platform ID:</strong><br>
                                            <code><?php echo htmlspecialchars($social_link['platform']); ?></code>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <strong>URL:</strong><br>
                                            <a href="<?php echo htmlspecialchars($social_link['url']); ?>" target="_blank" class="text-break">
                                                <?php echo htmlspecialchars($social_link['url']); ?>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Icon Class:</strong><br>
                                            <code><?php echo htmlspecialchars($social_link['icon']); ?></code>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Display Order:</strong><br>
                                            <span class="badge bg-primary"><?php echo $social_link['display_order']; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Status:</strong><br>
                                            <span class="badge bg-<?php echo $social_link['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($social_link['status']); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Link ID:</strong><br>
                                            <span class="text-muted">#<?php echo $social_link['id']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Quick Actions -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="<?php echo htmlspecialchars($social_link['url']); ?>" 
                                           target="_blank" 
                                           class="btn btn-primary">
                                            <i class="fas fa-external-link-alt me-2"></i>Visit Link
                                        </a>
                                        <a href="edit-social-link.php?id=<?php echo $linkId; ?>" 
                                           class="btn btn-warning">
                                            <i class="fas fa-edit me-2"></i>Edit Link
                                        </a>
                                        <a href="social-links.php" 
                                           class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to List
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Link Metadata -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Link Metadata</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Created:</strong><br>
                                        <small class="text-muted">
                                            <?php echo date('F j, Y \a\t g:i A', strtotime($social_link['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Last Updated:</strong><br>
                                        <small class="text-muted">
                                            <?php echo date('F j, Y \a\t g:i A', strtotime($social_link['updated_at'])); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <strong>Platform Color:</strong><br>
                                        <div class="d-flex align-items-center mt-1">
                                            <div style="width: 20px; height: 20px; background-color: <?php echo $platform_options[$social_link['platform']]['color'] ?? '#6c757d'; ?>; border-radius: 4px; margin-right: 10px;"></div>
                                            <code><?php echo $platform_options[$social_link['platform']]['color'] ?? '#6c757d'; ?></code>
                                        </div>
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

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>