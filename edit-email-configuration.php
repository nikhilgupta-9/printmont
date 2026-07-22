<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/EmailController.php';

$emailController = new EmailController();
$id = $_GET['id'] ?? 0;

if (!$id) {
    header("Location: email-configurations.php");
    exit;
}

$config = $emailController->getConfigurationById($id);

if (!$config) {
    $_SESSION['error_message'] = "Configuration not found.";
    header("Location: email-configurations.php");
    exit;
}

// Handle form submission
if ($_POST) {
    if (isset($_POST['update_configuration'])) {
        $data = [
            'config_name' => $_POST['config_name'],
            'purpose' => $_POST['purpose'] ?? 'Customer Support',
            'mail_driver' => $_POST['mail_driver'],
            'mail_host' => $_POST['mail_host'],
            'mail_port' => $_POST['mail_port'],
            'mail_username' => $_POST['mail_username'],
            'mail_password' => $_POST['mail_password'] ?: $config['mail_password'],
            'mail_encryption' => $_POST['mail_encryption'],
            'mail_from_address' => $_POST['mail_from_address'],
            'mail_from_name' => $_POST['mail_from_name'],
            'status' => $_POST['status']
        ];
        
        if ($emailController->updateConfiguration($id, $data)) {
            $_SESSION['success_message'] = "Email configuration updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update email configuration.";
        }
        header("Location: email-configurations.php");
        exit;
    }
}

$purposes = [
    'Customer Support', 'Orders', 'No Reply', 'Sales', 'Marketing',
    'Offers & Promotions', 'Contact Page', 'Returns & Refund', 'Billing',
    'Accounts', 'Careers', 'HR', 'Vendor/Seller Support', 'Partnership',
    'Legal', 'Privacy / DPDP', 'Security', 'Verification / OTP', 'Notifications', 'Newsletter'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Email Configuration | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-3">
                        <div class="col-auto">
                            <h3>Edit <strong>Email Configuration</strong></h3>
                        </div>
                        <div class="col-auto ms-auto text-end">
                            <a href="email-configurations.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Configurations
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-xl-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Update Settings for <?php echo htmlspecialchars($config['config_name']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Configuration Name *</label>
                                                <input type="text" class="form-control" name="config_name" 
                                                       value="<?php echo htmlspecialchars($config['config_name']); ?>" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Purpose (Mail Use Case) *</label>
                                                <select class="form-select" name="purpose" required>
                                                    <?php foreach ($purposes as $p): ?>
                                                        <option value="<?php echo htmlspecialchars($p); ?>" <?php echo ($config['purpose'] ?? '') === $p ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($p); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Mail Driver *</label>
                                                <select class="form-select" name="mail_driver" id="mail_driver" required>
                                                    <option value="smtp" <?php echo $config['mail_driver'] === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                                                    <option value="sendmail" <?php echo $config['mail_driver'] === 'sendmail' ? 'selected' : ''; ?>>Sendmail</option>
                                                    <option value="mail" <?php echo $config['mail_driver'] === 'mail' ? 'selected' : ''; ?>>PHP Mail</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div id="smtp_settings">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Mail Host *</label>
                                                    <input type="text" class="form-control" name="mail_host" 
                                                           value="<?php echo htmlspecialchars($config['mail_host']); ?>">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Mail Port *</label>
                                                    <input type="text" class="form-control" name="mail_port" 
                                                           value="<?php echo htmlspecialchars($config['mail_port']); ?>">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Mail Username *</label>
                                                    <input type="text" class="form-control" name="mail_username" 
                                                           value="<?php echo htmlspecialchars($config['mail_username']); ?>">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Mail Password</label>
                                                    <input type="password" class="form-control" name="mail_password" 
                                                           placeholder="Leave blank to keep current password">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Encryption *</label>
                                                    <select class="form-select" name="mail_encryption">
                                                        <option value="tls" <?php echo $config['mail_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                        <option value="ssl" <?php echo $config['mail_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                        <option value="none" <?php echo $config['mail_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">From Address *</label>
                                                <input type="email" class="form-control" name="mail_from_address" 
                                                       value="<?php echo htmlspecialchars($config['mail_from_address']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">From Name *</label>
                                                <input type="text" class="form-control" name="mail_from_name" 
                                                       value="<?php echo htmlspecialchars($config['mail_from_name']); ?>" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status">
                                                <option value="active" <?php echo $config['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo $config['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </div>

                                        <div class="mt-4">
                                            <button type="submit" name="update_configuration" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Update Configuration
                                            </button>
                                            <a href="email-configurations.php" class="btn btn-secondary ms-2">Cancel</a>
                                        </div>
                                    </form>
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
    <script>
        document.getElementById('mail_driver').addEventListener('change', function() {
            var smtpSettings = document.getElementById('smtp_settings');
            smtpSettings.style.display = (this.value === 'smtp') ? 'block' : 'none';
        });
    </script>
</body>
</html>
