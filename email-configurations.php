<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/EmailController.php';

$emailController = new EmailController();
$configurations = $emailController->getAllConfigurations();

// Handle form submission
if ($_POST) {
    if (isset($_POST['add_configuration'])) {
        $data = [
            'config_name' => $_POST['config_name'],
            'mail_driver' => $_POST['mail_driver'],
            'mail_host' => $_POST['mail_host'],
            'mail_port' => $_POST['mail_port'],
            'mail_username' => $_POST['mail_username'],
            'mail_password' => $_POST['mail_password'],
            'mail_encryption' => $_POST['mail_encryption'],
            'mail_from_address' => $_POST['mail_from_address'],
            'mail_from_name' => $_POST['mail_from_name'],
            'status' => $_POST['status']
        ];
        
        if ($emailController->createConfiguration($data)) {
            $_SESSION['success_message'] = "Email configuration created successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to create email configuration.";
        }
        header("Location: email-configurations.php");
        exit;
    }

    // Test connection
    if (isset($_POST['test_connection'])) {
        $data = [
            'mail_host' => $_POST['mail_host'],
            'mail_port' => $_POST['mail_port'],
            'mail_username' => $_POST['mail_username'],
            'mail_password' => $_POST['mail_password'],
            'mail_encryption' => $_POST['mail_encryption']
        ];
        
        if ($emailController->testConfiguration($data)) {
            $_SESSION['success_message'] = "Connection test successful!";
        } else {
            $_SESSION['error_message'] = "Connection test failed. Please check your settings.";
        }
        header("Location: email-configurations.php");
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
    <title>Email Configurations | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .encryption-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .encryption-ssl { background-color: #e8f5e8; color: #2e7d32; }
        .encryption-tls { background-color: #e3f2fd; color: #1565c0; }
        .encryption-none { background-color: #f5f5f5; color: #616161; }
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
                            <h3><strong>Email</strong> Configurations</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConfigModal">
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
                                    <h5 class="card-title">Email Configurations</h5>
                                    <h6 class="card-subtitle text-muted">Manage your email server configurations.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($configurations)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-cogs fa-3x text-muted mb-3"></i>
                                            <h5>No Email Configurations</h5>
                                            <p class="text-muted">Get started by adding your first email configuration.</p>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConfigModal">
                                                <i class="fas fa-plus"></i> Add Configuration
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Config Name</th>
                                                        <th>Driver</th>
                                                        <th>Host</th>
                                                        <th>Port</th>
                                                        <th>From Address</th>
                                                        <th>Encryption</th>
                                                        <th>Status</th>
                                                        <th>Created</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($configurations as $config): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($config['config_name']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary"><?php echo strtoupper($config['mail_driver']); ?></span>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($config['mail_host']); ?></code>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($config['mail_port']); ?>
                                                            </td>
                                                            <td>
                                                                <small><?php echo htmlspecialchars($config['mail_from_address']); ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="encryption-badge encryption-<?php echo $config['mail_encryption']; ?>">
                                                                    <?php echo strtoupper($config['mail_encryption']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $config['status']; ?>">
                                                                    <?php echo ucfirst($config['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($config['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="edit-email-configuration.php?id=<?php echo $config['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="delete-email-configuration.php?id=<?php echo $config['id']; ?>" 
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

                                        <!-- Active Configuration Info -->
                                        <?php 
                                        $activeConfig = $emailController->getActiveConfiguration();
                                        if ($activeConfig): ?>
                                        <div class="alert alert-success mt-3">
                                            <h6 class="alert-heading">Active Configuration</h6>
                                            <p class="mb-0">
                                                <strong><?php echo htmlspecialchars($activeConfig['config_name']); ?></strong> 
                                                is currently active. All system emails will be sent using this configuration.
                                            </p>
                                        </div>
                                        <?php else: ?>
                                        <div class="alert alert-warning mt-3">
                                            <h6 class="alert-heading">No Active Configuration</h6>
                                            <p class="mb-0">No email configuration is currently active. Please activate one to enable email sending.</p>
                                        </div>
                                        <?php endif; ?>
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

    <!-- Add Configuration Modal -->
    <div class="modal fade" id="addConfigModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Email Configuration</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Configuration Name *</label>
                                <input type="text" class="form-control" name="config_name" 
                                       placeholder="e.g., Gmail SMTP, SendGrid" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mail Driver *</label>
                                <select class="form-select" name="mail_driver" id="mail_driver" required>
                                    <option value="smtp">SMTP</option>
                                    <option value="sendmail">Sendmail</option>
                                    <option value="mail">PHP Mail</option>
                                </select>
                            </div>
                        </div>

                        <div id="smtp_settings">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mail Host *</label>
                                    <input type="text" class="form-control" name="mail_host" 
                                           placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mail Port *</label>
                                    <input type="text" class="form-control" name="mail_port" 
                                           placeholder="587">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mail Username *</label>
                                    <input type="text" class="form-control" name="mail_username" 
                                           placeholder="your-email@gmail.com">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mail Password *</label>
                                    <input type="password" class="form-control" name="mail_password" 
                                           placeholder="Your email password or app password">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Encryption *</label>
                                    <select class="form-select" name="mail_encryption">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="none">None</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <button type="submit" name="test_connection" class="btn btn-outline-primary mt-4">
                                        <i class="fas fa-plug"></i> Test Connection
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Address *</label>
                                <input type="email" class="form-control" name="mail_from_address" 
                                       placeholder="noreply@yourdomain.com" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Name *</label>
                                <input type="text" class="form-control" name="mail_from_name" 
                                       placeholder="Your Company Name" required>
                            </div>
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
                        <button type="submit" name="add_configuration" class="btn btn-primary">Save Configuration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle SMTP settings based on driver selection
        document.getElementById('mail_driver').addEventListener('change', function() {
            var smtpSettings = document.getElementById('smtp_settings');
            if (this.value === 'smtp') {
                smtpSettings.style.display = 'block';
            } else {
                smtpSettings.style.display = 'none';
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            var driverSelect = document.getElementById('mail_driver');
            var smtpSettings = document.getElementById('smtp_settings');
            if (driverSelect.value !== 'smtp') {
                smtpSettings.style.display = 'none';
            }
        });
    </script>
</body>
</html>