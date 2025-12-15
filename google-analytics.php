<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SeoController.php';

$seoController = new SeoController();
$analytics = $seoController->getAllAnalytics();

// Handle form submission
if ($_POST) {
    if (isset($_POST['add_analytics'])) {
        $data = [
            'tracking_id' => $_POST['tracking_id'],
            'measurement_id' => $_POST['measurement_id'],
            'status' => $_POST['status']
        ];
        
        if ($seoController->createAnalytics($data)) {
            $_SESSION['success_message'] = "Google Analytics configuration added successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to add Google Analytics configuration.";
        }
        header("Location: google-analytics.php");
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
    <title>Google Analytics | Printmont</title>
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
                            <h3><strong>Google</strong> Analytics</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnalyticsModal">
                                <i class="fas fa-plus"></i> Add Configuration
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
                                    <h5 class="card-title">Google Analytics Configurations</h5>
                                    <h6 class="card-subtitle text-muted">Manage your Google Analytics tracking codes.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($analytics)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                            <h5>No Analytics Configuration</h5>
                                            <p class="text-muted">Get started by adding your first Google Analytics configuration.</p>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnalyticsModal">
                                                <i class="fas fa-plus"></i> Add Configuration
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Tracking ID</th>
                                                        <th>Measurement ID</th>
                                                        <th>Status</th>
                                                        <th>Created</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($analytics as $analytic): ?>
                                                        <tr>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($analytic['tracking_id']); ?></code>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($analytic['measurement_id'] ?? 'N/A'); ?></code>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $analytic['status']; ?>">
                                                                    <?php echo ucfirst($analytic['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($analytic['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="edit-google-analytics.php?id=<?php echo $analytic['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="delete-google-analytics.php?id=<?php echo $analytic['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this configuration?')"
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
                                                &lt;!-- Global site tag (gtag.js) - Google Analytics --&gt;<br>
                                                &lt;script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"&gt;&lt;/script&gt;<br>
                                                &lt;script&gt;<br>
                                                &nbsp;&nbsp;window.dataLayer = window.dataLayer || [];<br>
                                                &nbsp;&nbsp;function gtag(){dataLayer.push(arguments);}<br>
                                                &nbsp;&nbsp;gtag('js', new Date());<br>
                                                &nbsp;&nbsp;gtag('config', 'GA_MEASUREMENT_ID');<br>
                                                &lt;/script&gt;
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

    <!-- Add Analytics Modal -->
    <div class="modal fade" id="addAnalyticsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Google Analytics</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tracking ID *</label>
                            <input type="text" class="form-control" name="tracking_id" 
                                   placeholder="G-XXXXXXXXXX" required>
                            <small class="form-text text-muted">Format: G-XXXXXXXXXX</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Measurement ID</label>
                            <input type="text" class="form-control" name="measurement_id" 
                                   placeholder="Optional for GA4">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <small class="form-text text-muted">Only one configuration can be active at a time.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_analytics" class="btn btn-primary">Add Configuration</button>
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