<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/ContactController.php';

$contactController = new ContactController();

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id'              => $_POST['id'] ?? '',
        'help_number'     => trim($_POST['help_number'] ?? ''),
        'service_time'    => trim($_POST['service_time'] ?? ''),
        'sales_email'     => trim($_POST['sales_email'] ?? ''),
        'corporate_email' => trim($_POST['corporate_email'] ?? ''),
        'address_one'     => trim($_POST['address_one'] ?? ''),
        'address_two'     => trim($_POST['address_two'] ?? ''),
    ];

    $result = $contactController->updateContactDetails($data);

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }

    header('Location: general-settings.php');
    exit();
}

$contact = $contactController->getContactDetails();

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
    <title>General Settings | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
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
                            <h3><strong>General</strong> Settings</h3>
                        </div>
                    </div>

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
                        <div class="col-12 col-xl-9">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Contact Details</h5>
                                    <h6 class="card-subtitle text-muted">
                                        Shown on the storefront Contact page and served by
                                        <code>api/contact-api.php</code>.
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="general-settings.php">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($contact['id'] ?? ''); ?>">

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Help Number <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="help_number" required
                                                       value="<?php echo htmlspecialchars($contact['help_number'] ?? ''); ?>"
                                                       placeholder="e.g. 9818532463">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Service Time</label>
                                                <input type="text" class="form-control" name="service_time"
                                                       value="<?php echo htmlspecialchars($contact['service_time'] ?? ''); ?>"
                                                       placeholder="e.g. Mon - Sat: 10:30 AM - 7:00 PM">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Sales / Support Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" name="sales_email" required
                                                       value="<?php echo htmlspecialchars($contact['sales_email'] ?? ''); ?>"
                                                       placeholder="support@printmont.com">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label">Corporate Email</label>
                                                <input type="email" class="form-control" name="corporate_email"
                                                       value="<?php echo htmlspecialchars($contact['corporate_email'] ?? ''); ?>"
                                                       placeholder="info@printmont.com">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Address One <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="address_one" rows="2" required
                                                      placeholder="Registered / primary address"><?php echo htmlspecialchars($contact['address_one'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Address Two</label>
                                            <textarea class="form-control" name="address_two" rows="2"
                                                      placeholder="Branch or secondary address (optional)"><?php echo htmlspecialchars($contact['address_two'] ?? ''); ?></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Save Contact Details</button>
                                        <a href="contact-inquiries.php" class="btn btn-light">View Inquiries</a>
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
</body>
</html>
