<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SeoController.php';

$seoController = new SeoController();
$pixels = $seoController->getAllPixels();

// Handle form submission
if ($_POST) {
    if (isset($_POST['add_pixel'])) {
        $data = [
            'pixel_id' => $_POST['pixel_id'],
            'pixel_name' => $_POST['pixel_name'],
            'status' => $_POST['status']
        ];
        
        if ($seoController->createPixel($data)) {
            $_SESSION['success_message'] = "Facebook Pixel added successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to add Facebook Pixel.";
        }
        header("Location: facebook-pixels.php");
        exit;
    }
}

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
    <title>Facebook Pixels | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .code-preview { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; }
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
                            <h3><strong>Facebook</strong> Pixels</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPixelModal">
                                <i class="fas fa-plus"></i> Add Pixel
                            </button>
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
                                    <h5 class="card-title">Facebook Pixel Configurations</h5>
                                    <h6 class="card-subtitle text-muted">Manage your Facebook Pixel tracking codes.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($pixels)): ?>
                                        <div class="text-center py-4">
                                            <i class="fab fa-facebook fa-3x text-muted mb-3"></i>
                                            <h5>No Facebook Pixels</h5>
                                            <p class="text-muted">Get started by adding your first Facebook Pixel.</p>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPixelModal">
                                                <i class="fas fa-plus"></i> Add Pixel
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Pixel Name</th>
                                                        <th>Pixel ID</th>
                                                        <th>Status</th>
                                                        <th>Created</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($pixels as $pixel): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($pixel['pixel_name']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($pixel['pixel_id']); ?></code>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $pixel['status']; ?>">
                                                                    <?php echo ucfirst($pixel['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($pixel['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="edit-facebook-pixel.php?id=<?php echo $pixel['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="delete-facebook-pixel.php?id=<?php echo $pixel['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this pixel?')"
                                                                   title="Delete">
                                                                   <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Code Preview -->
                                        <div class="mt-4">
                                            <h6>Implementation Code</h6>
                                            <div class="code-preview">
                                                &lt;!-- Facebook Pixel Code --&gt;<br>
                                                &lt;script&gt;<br>
                                                &nbsp;&nbsp;!function(f,b,e,v,n,t,s)<br>
                                                &nbsp;&nbsp;{if(f.fbq)return;n=f.fbq=function(){n.callMethod?<br>
                                                &nbsp;&nbsp;n.callMethod.apply(n,arguments):n.queue.push(arguments)};<br>
                                                &nbsp;&nbsp;if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';<br>
                                                &nbsp;&nbsp;n.queue=[];t=b.createElement(e);t.async=!0;<br>
                                                &nbsp;&nbsp;t.src=v;s=b.getElementsByTagName(e)[0];<br>
                                                &nbsp;&nbsp;s.parentNode.insertBefore(t,s)}(window, document,'script',<br>
                                                &nbsp;&nbsp;'https://connect.facebook.net/en_US/fbevents.js');<br>
                                                &nbsp;&nbsp;fbq('init', 'PIXEL_ID');<br>
                                                &nbsp;&nbsp;fbq('track', 'PageView');<br>
                                                &lt;/script&gt;<br>
                                                &lt;noscript&gt;&lt;img height="1" width="1" style="display:none"<br>
                                                &nbsp;&nbsp;src="https://www.facebook.com/tr?id=PIXEL_ID&ev=PageView&noscript=1"<br>
                                                &nbsp;&nbsp;/&gt;&lt;/noscript&gt;<br>
                                                &lt;!-- End Facebook Pixel Code --&gt;
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

    <!-- Add Pixel Modal -->
    <div class="modal fade" id="addPixelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Facebook Pixel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pixel Name *</label>
                            <input type="text" class="form-control" name="pixel_name" 
                                   placeholder="e.g., Main Tracking Pixel" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pixel ID *</label>
                            <input type="text" class="form-control" name="pixel_id" 
                                   placeholder="123456789012345" required>
                            <small class="form-text text-muted">Your Facebook Pixel ID (numeric)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <small class="form-text text-muted">Only one pixel can be active at a time.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_pixel" class="btn btn-primary">Add Pixel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>