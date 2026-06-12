<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/FooterSectionController.php';
require_once 'controllers/FooterLinkController.php';
require_once 'controllers/FooterCompanyInfoController.php';
require_once 'controllers/FooterSocialLinkController.php';
require_once 'controllers/FooterBottomLinkController.php';
require_once 'controllers/FooterPaymentMethodController.php';

// Initialize controllers
$sectionController = new FooterSectionController();
$linkController = new FooterLinkController();
$companyController = new FooterCompanyInfoController();
$socialController = new FooterSocialLinkController();
$bottomLinkController = new FooterBottomLinkController();
$paymentController = new FooterPaymentMethodController();

// Get all data
$sections = $sectionController->getAllSections();
$links = $linkController->getAllLinks();
$companyInfo = $companyController->getCompanyInfo();
$socialLinks = $socialController->getAllSocialLinks();
$bottomLinks = $bottomLinkController->getAllBottomLinks();
$paymentMethods = $paymentController->getAllPaymentMethods();

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
    <title>Footer Management | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        .management-card { border-left: 4px solid #007bff; }
        .section-badge { font-size: 0.8rem; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .footer-preview { background-color: #0e1a35; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .preview-section { margin-bottom: 20px; }
        .preview-link { color: #dee2e6; text-decoration: none; display: block; margin-bottom: 5px; }
        .preview-link:hover { color: white; }
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
                            <h3><strong>Footer</strong> Management</h3>
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

                    <!-- Quick Stats -->
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card management-card">
                                <div class="card-body text-center">
                                    <h4><?php echo count($sections); ?></h4>
                                    <p class="mb-0">Sections</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card management-card">
                                <div class="card-body text-center">
                                    <h4><?php echo count($links); ?></h4>
                                    <p class="mb-0">Links</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card management-card">
                                <div class="card-body text-center">
                                    <h4><?php echo count($socialLinks); ?></h4>
                                    <p class="mb-0">Social Links</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card management-card">
                                <div class="card-body text-center">
                                    <h4><?php echo count($bottomLinks); ?></h4>
                                    <p class="mb-0">Bottom Links</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card management-card">
                                <div class="card-body text-center">
                                    <h4><?php echo count($paymentMethods); ?></h4>
                                    <p class="mb-0">Payment Methods</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card management-card">
                                <div class="card-body text-center">
                                    <h4><?php echo $companyInfo ? '✓' : '✗'; ?></h4>
                                    <p class="mb-0">Company Info</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Quick Actions</h5>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="footer-sections.php" class="btn btn-primary">Manage Sections</a>
                                        <a href="footer-links.php" class="btn btn-success">Manage Links</a>
                                        <a href="footer-company-info.php" class="btn btn-info">Company Info</a>
                                        <a href="social-links.php" class="btn btn-warning">Social Links</a>
                                        <a href="footer-bottom-links.php" class="btn btn-secondary">Bottom Links</a>
                                        <a href="footer-payment-methods.php" class="btn btn-dark">Payment Methods</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Preview -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Footer Preview</h5>
                                    <h6 class="card-subtitle text-muted">How your footer will appear on the website.</h6>
                                </div>
                                <div class="card-body">
                                    <div class="footer-preview">
                                        <div class="row">
                                            <!-- Sections -->
                                            <?php foreach ($sections as $section): 
                                                $sectionLinks = array_filter($links, function($link) use ($section) {
                                                    return $link['section_id'] == $section['id'] && $link['status'] == 'active';
                                                });
                                            ?>
                                                <?php if ($section['status'] == 'active' && count($sectionLinks) > 0): ?>
                                                    <div class="col-lg-3 col-md-6 preview-section">
                                                        <h6 class="text-white mb-3"><?php echo htmlspecialchars($section['title']); ?></h6>
                                                        <?php foreach ($sectionLinks as $link): ?>
                                                            <a href="<?php echo htmlspecialchars($link['url']); ?>" class="preview-link">
                                                                <?php echo htmlspecialchars($link['title']); ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>

                                            <!-- Support & Address Column -->
                                            <div class="col-lg-3 col-md-6 preview-section">
                                                <h6 class="text-white mb-3">SUPPORT</h6>
                                                <?php
                                                $supportLinks = array_filter($links, function($link) {
                                                    return strpos(strtolower($link['section_title']), 'support') !== false && $link['status'] == 'active';
                                                });
                                                foreach ($supportLinks as $link): ?>
                                                    <a href="<?php echo htmlspecialchars($link['url']); ?>" class="preview-link">
                                                        <?php echo htmlspecialchars($link['title']); ?>
                                                    </a>
                                                <?php endforeach; ?>

                                                <?php if ($companyInfo): ?>
                                                    <div class="mt-3 pt-3 border-top">
                                                        <small class="text-muted">
                                                            <?php echo nl2br(htmlspecialchars($companyInfo['address'])); ?><br>
                                                            Customer Care: <?php echo htmlspecialchars($companyInfo['customer_care']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Social Links -->
                                                <?php if (count($socialLinks) > 0): ?>
                                                    <div class="mt-3">
                                                        <h6 class="text-white mb-2">Follow Us</h6>
                                                        <div class="d-flex gap-2">
                                                            <?php foreach ($socialLinks as $social): 
                                                                if ($social['status'] == 'active'): ?>
                                                                    <a href="<?php echo htmlspecialchars($social['url']); ?>" class="text-white">
                                                                        <i class="<?php echo htmlspecialchars($social['icon']); ?>"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Newsletter -->
                                            <div class="col-lg-3 col-md-6 preview-section">
                                                <h6 class="text-white mb-3">Newsletter</h6>
                                                <?php if ($companyInfo): ?>
                                                    <p class="text-muted small"><?php echo htmlspecialchars($companyInfo['newsletter_text']); ?></p>
                                                <?php endif; ?>
                                                <div class="input-group mb-3">
                                                    <input type="email" class="form-control" placeholder="Your email">
                                                    <button class="btn btn-primary">Submit</button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bottom Section Preview -->
                                        <div class="border-top pt-3 mt-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <div class="d-flex flex-wrap gap-3">
                                                        <?php foreach ($bottomLinks as $bottom): 
                                                            if ($bottom['status'] == 'active'): ?>
                                                                <a href="<?php echo htmlspecialchars($bottom['url']); ?>" class="text-muted small">
                                                                    <?php if ($bottom['icon']): ?>
                                                                        <i class="<?php echo htmlspecialchars($bottom['icon']); ?> me-1"></i>
                                                                    <?php endif; ?>
                                                                    <?php echo htmlspecialchars($bottom['title']); ?>
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 text-md-end">
                                                    <?php if ($companyInfo): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($companyInfo['copyright_text']); ?></small>
                                                    <?php endif; ?>
                                                    <div class="mt-2">
                                                        <?php foreach ($paymentMethods as $payment): 
                                                            if ($payment['status'] == 'active'): ?>
                                                                <i class="<?php echo htmlspecialchars($payment['icon']); ?> me-2 text-muted"></i>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
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