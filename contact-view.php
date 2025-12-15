<?php
// contact-view.php
// This page handles fetching and updating the single row in the contact_info table.

// --- 1. INCLUDE REQUIRED FILES ---
// Ensure these paths are correct relative to this file.
require_once 'config/constants.php';
require_once 'controllers/AuthController.php'; // Assuming for admin panel authentication
require_once 'models/ContactModel.php'; 
require_once 'controllers/ContactController.php';

// Initialize Controller and variables
$contactController = new ContactController();
$message = null;
$result = ['success' => null];

// --- 2. HANDLE FORM SUBMISSION (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Attempt to update the contact details via the Controller
    $result = $contactController->updateContactDetails($_POST);
    $message = $result['message']; 
}

// --- 3. FETCH CURRENT DETAILS ---
// Fetch data *after* a potential update to display the newest values
$contact = $contactController->getContactDetails(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Contact Management - Admin Panel</title>
    <link rel="stylesheet" href="css/light.css"> 
</head>
<body>
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>

        <main class="content">
            <div class="container-fluid p-0">
                <div class="d-flex justify-content-between mb-3">
                    <h3><strong>Contact</strong> Details Management</h3>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $result['success'] ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm mt-4">
                    <div class="card-header"><h5 class="mb-0">Current Contact Information</h5></div>
                    <div class="card-body">
                        <form action="contact-view.php" method="POST">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($contact['id'] ?? '1') ?>">

                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label fw-semibold">Helpline Number</label>
                                    <input type="text" name="help_number" class="form-control" required
                                           value="<?= htmlspecialchars($contact['help_number'] ?? '') ?>">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label fw-semibold">Service Time</label>
                                    <input type="text" name="service_time" class="form-control"
                                           value="<?= htmlspecialchars($contact['service_time'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label fw-semibold">Sales Email</label>
                                    <input type="email" name="sales_email" class="form-control" required
                                           value="<?= htmlspecialchars($contact['sales_email'] ?? '') ?>">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label fw-semibold">Corporate Email</label>
                                    <input type="email" name="corporate_email" class="form-control"
                                           value="<?= htmlspecialchars($contact['corporate_email'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Address One (Required)</label>
                                <textarea name="address_one" rows="3" class="form-control" required><?= htmlspecialchars($contact['address_one'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Address Two (Optional)</label>
                                <textarea name="address_two" rows="3" class="form-control"><?= htmlspecialchars($contact['address_two'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<script src="js/app.js"></script> 
</body>
</html>