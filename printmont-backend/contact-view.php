<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/ContactController.php';

// Authentication check
$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

if (!$authController->isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Initialize Controller and variables
$contactController = new ContactController();
$message = null;
$message_type = 'success'; // 'success' or 'danger'

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $contactController->updateContactDetails($_POST);
    $message = $result['message'];
    $message_type = $result['success'] ? 'success' : 'danger';
}

// Fetch current details
$contact = $contactController->getContactDetails();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Contact Management - PrintMont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .contact-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .contact-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            padding: 20px 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .last-updated {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
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
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="h3 mb-0">
                                <i class="fas fa-address-book me-2 text-primary"></i>
                                <strong>Contact</strong> Information
                            </h1>
                            <div class="text-muted">
                                <small>Last updated: <?php echo !empty($contact['updated_at']) ? date('M j, Y g:i A', strtotime($contact['updated_at'])) : 'Never'; ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                            <div><?php echo htmlspecialchars($message); ?></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card contact-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0 text-white">
                                    <i class="fas fa-edit me-2"></i>
                                    Manage Contact Details
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <form action="contact-view.php" method="POST">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($contact['id'] ?? ''); ?>">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-phone me-2 text-primary"></i>
                                                Helpline Number *
                                            </label>
                                            <input type="text" name="help_number" class="form-control" required
                                                   placeholder="e.g., +1 234 567 8900"
                                                   value="<?php echo htmlspecialchars($contact['help_number'] ?? ''); ?>">
                                            <small class="text-muted">Primary contact number for customers</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-clock me-2 text-warning"></i>
                                                Service Time
                                            </label>
                                            <input type="text" name="service_time" class="form-control"
                                                   placeholder="e.g., Mon-Fri 9AM-6PM"
                                                   value="<?php echo htmlspecialchars($contact['service_time'] ?? ''); ?>">
                                            <small class="text-muted">Business hours for customer support</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-envelope me-2 text-success"></i>
                                                Sales Email *
                                            </label>
                                            <input type="email" name="sales_email" class="form-control" required
                                                   placeholder="e.g., sales@printmont.com"
                                                   value="<?php echo htmlspecialchars($contact['sales_email'] ?? ''); ?>">
                                            <small class="text-muted">Email for sales inquiries</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-building me-2 text-info"></i>
                                                Corporate Email
                                            </label>
                                            <input type="email" name="corporate_email" class="form-control"
                                                   placeholder="e.g., info@printmont.com"
                                                   value="<?php echo htmlspecialchars($contact['corporate_email'] ?? ''); ?>">
                                            <small class="text-muted">Email for corporate communications</small>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                            Primary Address *
                                        </label>
                                        <textarea name="address_one" rows="3" class="form-control" required
                                                  placeholder="Enter your main business address"><?php echo htmlspecialchars($contact['address_one'] ?? ''); ?></textarea>
                                        <small class="text-muted">Main office or business location</small>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="fas fa-map-marker-alt me-2 text-secondary"></i>
                                            Secondary Address (Optional)
                                        </label>
                                        <textarea name="address_two" rows="3" class="form-control"
                                                  placeholder="Enter additional address if any"><?php echo htmlspecialchars($contact['address_two'] ?? ''); ?></textarea>
                                        <small class="text-muted">Additional location or branch office</small>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1 text-info"></i>
                                            Fields marked with * are required
                                        </div>
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="fas fa-save me-2"></i>
                                            Save Changes
                                        </button>
                                    </div>
                                </form>

                                <?php if (!empty($contact['updated_at'])): ?>
                                    <div class="last-updated mt-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-history me-2 text-muted"></i>
                                            <div>
                                                <small class="text-muted">Last updated: 
                                                    <strong><?php echo date('F j, Y \a\t g:i A', strtotime($contact['updated_at'])); ?></strong>
                                                </small>
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
<script>
    // Form validation enhancement
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '#e9ecef';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields marked with *');
            }
        });
        
        // Real-time email validation
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            field.addEventListener('blur', function() {
                if (this.value && !this.validity.valid) {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '#e9ecef';
                }
            });
        });
    });
</script>
</body>
</html>