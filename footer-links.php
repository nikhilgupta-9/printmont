<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/FooterLinkController.php';
require_once 'controllers/FooterSectionController.php';

$linkController = new FooterLinkController();
$sectionController = new FooterSectionController();

$links = $linkController->getAllLinks();
$sections = $sectionController->getAllSections();

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
    <title>Footer Links | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .section-badge { font-size: 0.8rem; }
        .url-text { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Footer</strong> Links</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                             <a href="footer-management.php" class="btn btn-secondary">
                                <i class="fas fa-arrow"></i> Back Link
                            </a>
                            <a href="add-footer-link.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Link
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

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Footer Links</h5>
                                    <h6 class="card-subtitle text-muted">Manage links in your footer sections.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($links)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-link fa-3x text-muted mb-3"></i>
                                            <h5>No Footer Links</h5>
                                            <p class="text-muted">Get started by adding your first footer link.</p>
                                            <a href="add-footer-link.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Link1
                                            </a>
                                            
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>URL</th>
                                                        <th>Section</th>
                                                        <th>Order</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($links as $link): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($link['title']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <div class="url-text" title="<?php echo htmlspecialchars($link['url']); ?>">
                                                                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="text-decoration-none">
                                                                        <?php echo htmlspecialchars($link['url']); ?>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary section-badge">
                                                                    <?php echo htmlspecialchars($link['section_title']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark">
                                                                    <?php echo $link['link_order']; ?>
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
                                                                <a href="edit-footer-link.php?id=<?php echo $link['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="delete-footer-link.php?id=<?php echo $link['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this link?')"
                                                                   title="Delete">
                                                                   <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Links by Section -->
                                        <div class="row mt-4">
                                            <?php foreach ($sections as $section): 
                                                $sectionLinks = array_filter($links, function($link) use ($section) {
                                                    return $link['section_id'] == $section['id'];
                                                });
                                            ?>
                                                <div class="col-md-3 mb-3">
                                                    <div class="card">
                                                        <div class="card-header">
                                                            <h6 class="card-title mb-0"><?php echo htmlspecialchars($section['title']); ?></h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p class="mb-2">
                                                                <strong><?php echo count($sectionLinks); ?></strong> links
                                                            </p>
                                                            <a href="add-footer-link.php?section_id=<?php echo $section['id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                Add Link
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
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