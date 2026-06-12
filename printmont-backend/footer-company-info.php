<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/FooterCompanyInfoController.php';

$companyController = new FooterCompanyInfoController();
$companyInfo = $companyController->getCompanyInfo();

// Handle form submission
if ($_POST) {
    try {
        $data = [
            'company_name' => trim($_POST['company_name']),
            'address' => trim($_POST['address']),
            'customer_care' => trim($_POST['customer_care']),
            'newsletter_text' => trim($_POST['newsletter_text']),
            'copyright_text' => trim($_POST['copyright_text']),
            'status' => $_POST['status']
        ];

        // Validation
        if (empty($data['company_name'])) {
            throw new Exception("Company name is required.");
        }
        if (empty($data['address'])) {
            throw new Exception("Company address is required.");
        }
        if (empty($data['customer_care'])) {
            throw new Exception("Customer care number is required.");
        }
        if (empty($data['newsletter_text'])) {
            throw new Exception("Newsletter text is required.");
        }
        if (empty($data['copyright_text'])) {
            throw new Exception("Copyright text is required.");
        }

        if ($companyController->updateCompanyInfo($data)) {
            $_SESSION['success_message'] = "Company information updated successfully!";
            header("Location: footer-company-info.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to update company information.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }

    // Refresh company info
    $companyInfo = $companyController->getCompanyInfo();
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
    <title>Company Information | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .preview-section {
            background-color: #0e1a35;
            color: white;
            padding: 20px;
            border-radius: 8px;
        }

        .preview-link {
            color: #dee2e6;
            text-decoration: none;
            display: block;
            margin-bottom: 5px;
        }

        .preview-link:hover {
            color: white;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
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
                            <h3><strong>Company</strong> Information</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="footer-management.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Footer
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
                        <!-- Edit Form -->
                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <?php echo $companyInfo ? 'Edit' : 'Add'; ?> Company Information
                                    </h5>
                                    <h6 class="card-subtitle text-muted">
                                        Update your company details for the website footer.
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="companyForm">
                                        <!-- Company Name -->
                                        <div class="mb-3">
                                            <label for="company_name" class="form-label required">Company Name</label>
                                            <input type="text" class="form-control" id="company_name"
                                                name="company_name"
                                                value="<?php echo $companyInfo ? htmlspecialchars($companyInfo['company_name']) : 'Xordox International Pvt. Ltd'; ?>"
                                                required maxlength="255" placeholder="Enter company name">
                                        </div>

                                        <!-- Address -->
                                        <div class="mb-3">
                                            <label for="address" class="form-label required">Registered Address</label>
                                            <textarea class="form-control" id="address" name="address" rows="4" required
                                                placeholder="Enter complete company address"><?php echo $companyInfo ? htmlspecialchars($companyInfo['address']) : "3398, Bagichi Acchi ji Bara Hindu Rao,\nNear Filmistan Cinema, New Delhi 110006,\nNew Delhi, India."; ?></textarea>
                                            <small class="form-text text-muted">
                                                Use line breaks (Enter) to format the address properly in the footer.
                                            </small>
                                        </div>

                                        <!-- Customer Care -->
                                        <div class="mb-3">
                                            <label for="customer_care" class="form-label required">Customer Care
                                                Number</label>
                                            <input type="text" class="form-control" id="customer_care"
                                                name="customer_care"
                                                value="<?php echo $companyInfo ? htmlspecialchars($companyInfo['customer_care']) : '+91-9818532463'; ?>"
                                                required maxlength="50" placeholder="e.g., +91-9818532463">
                                        </div>

                                        <!-- Newsletter Text -->
                                        <div class="mb-3">
                                            <label for="newsletter_text" class="form-label required">Newsletter
                                                Text</label>
                                            <textarea class="form-control" id="newsletter_text" name="newsletter_text"
                                                rows="2" required
                                                placeholder="Text displayed above newsletter subscription"><?php echo $companyInfo ? htmlspecialchars($companyInfo['newsletter_text']) : 'Get updates on new products and offers. Coupons.'; ?></textarea>
                                            <small class="form-text text-muted">
                                                This text appears above the newsletter subscription input field.
                                            </small>
                                        </div>

                                        <!-- Copyright Text -->
                                        <div class="mb-3">
                                            <label for="copyright_text" class="form-label required">Copyright
                                                Text</label>
                                            <input type="text" class="form-control" id="copyright_text"
                                                name="copyright_text"
                                                value="<?php echo $companyInfo ? htmlspecialchars($companyInfo['copyright_text']) : '© 2019-2024 printmont.com All Rights Reserved.'; ?>"
                                                required maxlength="500"
                                                placeholder="e.g., © 2019-2024 company.com All Rights Reserved">
                                        </div>

                                        <!-- Status -->
                                        <div class="mb-4">
                                            <label for="status" class="form-label required">Status</label>
                                            <select name="status" id="status" class="form-control" required>
                                                <option value="active" <?php echo ($companyInfo && $companyInfo['status'] == 'active') ? 'selected' : 'selected'; ?>>
                                                    Active - Show in footer</option>
                                                <option value="inactive" <?php echo ($companyInfo && $companyInfo['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive -
                                                    Hide from footer</option>
                                            </select>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i>
                                                <?php echo $companyInfo ? 'Update' : 'Create'; ?> Company Info
                                            </button>
                                            <button type="reset" class="btn btn-outline-secondary"
                                                onclick="resetForm()">
                                                <i class="fas fa-undo"></i> Reset
                                            </button>
                                            <a href="footer-management.php" class="btn btn-secondary ms-auto">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Current Information -->
                            <?php if ($companyInfo): ?>
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title">Current Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Last Updated:</strong><br>
                                                <span class="text-muted">
                                                    <?php echo date('F j, Y \a\t g:i A', strtotime($companyInfo['updated_at'])); ?>
                                                </span>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Status:</strong><br>
                                                <span class="status-badge status-<?php echo $companyInfo['status']; ?>">
                                                    <?php echo ucfirst($companyInfo['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <strong>Record ID:</strong><br>
                                                <span class="text-muted">#<?php echo $companyInfo['id']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Preview & Guidelines -->
                        <div class="col-12 col-lg-4">
                            <!-- Live Preview -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-eye"></i> Live Preview
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="preview-section">
                                        <!-- Support Column -->
                                        <div class="preview-section">
                                            <h6 class="text-white mb-3">SUPPORT</h6>
                                            <a href="/account" class="preview-link">Account Settings</a>
                                            <a href="/orders" class="preview-link">My Orders</a>
                                            <a href="/wallet" class="preview-link">My Wallet</a>
                                            <a href="/track" class="preview-link">Track Orders</a>

                                            <!-- Company Address -->
                                            <div class="mt-3 pt-3 border-top border-secondary">
                                                <small class="text-muted">
                                                    <strong id="previewCompanyName">
                                                        <?php echo $companyInfo ? htmlspecialchars($companyInfo['company_name']) : 'Xordox International Pvt. Ltd'; ?>
                                                    </strong><br>
                                                    <span id="previewAddress">
                                                        <?php
                                                        $address = $companyInfo ? $companyInfo['address'] : "3398, Bagichi Acchi ji Bara Hindu Rao,\nNear Filmistan Cinema, New Delhi 110006,\nNew Delhi, India.";
                                                        echo nl2br(htmlspecialchars($address));
                                                        ?>
                                                    </span><br>
                                                    Customer Care: <span id="previewCustomerCare">
                                                        <?php echo $companyInfo ? htmlspecialchars($companyInfo['customer_care']) : '+91-9818532463'; ?>
                                                    </span>
                                                </small>
                                            </div>

                                            <!-- Social Links Placeholder -->
                                            <div class="mt-3">
                                                <h6 class="text-white mb-2">Follow Us</h6>
                                                <div class="d-flex gap-2">
                                                    <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                                                    <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                                                    <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                                                    <a href="#" class="text-white"><i
                                                            class="fab fa-linkedin-in"></i></a>
                                                    <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Newsletter Column -->
                                        <div class="preview-section mt-4">
                                            <h6 class="text-white mb-3">Newsletter</h6>
                                            <p class="text-muted small mb-3" id="previewNewsletterText">
                                                <?php echo $companyInfo ? htmlspecialchars($companyInfo['newsletter_text']) : 'Get updates on new products and offers. Coupons.'; ?>
                                            </p>
                                            <div class="input-group input-group-sm">
                                                <input type="email" class="form-control" placeholder="Your email"
                                                    style="background: #1a2a4a; border: 1px solid #2d3e5d; color: white;">
                                                <button class="btn btn-primary"
                                                    style="background: #007bff; border: 1px solid #007bff;">Submit</button>
                                            </div>
                                        </div>

                                        <!-- Bottom Section -->
                                        <div class="border-top border-secondary pt-3 mt-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <div class="d-flex flex-wrap gap-3">
                                                        <a href="/sell" class="text-muted small"><i
                                                                class="fas fa-store me-1"></i>Become a Seller</a>
                                                        <a href="/advertising" class="text-muted small"><i
                                                                class="fas fa-bullhorn me-1"></i>Advertising</a>
                                                        <a href="/coins" class="text-muted small"><i
                                                                class="fas fa-coins me-1"></i>Printmont Coins</a>
                                                        <a href="/help" class="text-muted small"><i
                                                                class="fas fa-question-circle me-1"></i>Help Center</a>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 text-md-end">
                                                    <small class="text-muted" id="previewCopyright">
                                                        <?php echo $companyInfo ? htmlspecialchars($companyInfo['copyright_text']) : '© 2019-2024 printmont.com All Rights Reserved.'; ?>
                                                    </small>
                                                    <div class="mt-2">
                                                        <i class="fab fa-cc-visa me-2 text-muted"></i>
                                                        <i class="fab fa-cc-mastercard me-2 text-muted"></i>
                                                        <i class="fas fa-credit-card me-2 text-muted"></i>
                                                        <i class="fab fa-cc-amex me-2 text-muted"></i>
                                                        <i class="fas fa-university me-2 text-muted"></i>
                                                        <i class="fas fa-mobile-alt me-2 text-muted"></i>
                                                        <i class="fas fa-wallet me-2 text-muted"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Guidelines -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-lightbulb"></i> Guidelines
                                    </h5>
                                </div>

                                <div class="card-body">

                                    <!-- Address Formatting -->
                                    <div class="alert alert-info p-2">
                                        <h6 class="mb-2 fw-bold">📝 Address Formatting</h6>
                                        <ul class="small ps-3 mb-0">
                                            <li>Use line breaks for better readability</li>
                                            <li>Include complete legal address</li>
                                            <li>Keep formatting consistent</li>
                                        </ul>
                                    </div>

                                    <!-- Contact Information -->
                                    <div class="alert alert-warning p-2">
                                        <h6 class="mb-2 fw-bold">📞 Contact Information</h6>
                                        <ul class="small ps-3 mb-0">
                                            <li>Use international phone format</li>
                                            <li>Include country code</li>
                                            <li>Ensure number is active</li>
                                        </ul>
                                    </div>

                                    <!-- Newsletter Text -->
                                    <div class="alert alert-success p-2">
                                        <h6 class="mb-2 fw-bold">📢 Newsletter Text</h6>
                                        <ul class="small ps-3 mb-0">
                                            <li>Keep it short and compelling</li>
                                            <li>Highlight benefits for users</li>
                                            <li>Include call-to-action</li>
                                        </ul>
                                    </div>

                                    <!-- Copyright Text -->
                                    <div class="alert alert-primary p-2">
                                        <h6 class="mb-2 fw-bold">© Copyright Text</h6>
                                        <ul class="small ps-3 mb-0">
                                            <li>Include current year range</li>
                                            <li>Add your domain name</li>
                                            <li>Use "All Rights Reserved"</li>
                                        </ul>
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
    <script>
        // Live preview updates
        function updatePreview() {
            // Update company name
            document.getElementById('previewCompanyName').textContent =
                document.getElementById('company_name').value || 'Xordox International Pvt. Ltd';

            // Update address with line breaks
            const address = document.getElementById('address').value ||
                "3398, Bagichi Acchi ji Bara Hindu Rao,\nNear Filmistan Cinema, New Delhi 110006,\nNew Delhi, India.";
            document.getElementById('previewAddress').innerHTML = address.replace(/\n/g, '<br>');

            // Update customer care
            document.getElementById('previewCustomerCare').textContent =
                document.getElementById('customer_care').value || '+91-9818532463';

            // Update newsletter text
            document.getElementById('previewNewsletterText').textContent =
                document.getElementById('newsletter_text').value || 'Get updates on new products and offers. Coupons.';

            // Update copyright
            document.getElementById('previewCopyright').textContent =
                document.getElementById('copyright_text').value || '© 2019-2024 printmont.com All Rights Reserved.';
        }

        // Add event listeners for live preview
        document.getElementById('company_name').addEventListener('input', updatePreview);
        document.getElementById('address').addEventListener('input', updatePreview);
        document.getElementById('customer_care').addEventListener('input', updatePreview);
        document.getElementById('newsletter_text').addEventListener('input', updatePreview);
        document.getElementById('copyright_text').addEventListener('input', updatePreview);

        // Reset form function
        function resetForm() {
            if (confirm('Are you sure you want to reset all changes?')) {
                document.getElementById('companyForm').reset();
                updatePreview();
            }
        }

        // Form validation
        document.getElementById('companyForm').addEventListener('submit', function (e) {
            const companyName = document.getElementById('company_name').value.trim();
            const address = document.getElementById('address').value.trim();
            const customerCare = document.getElementById('customer_care').value.trim();
            const newsletterText = document.getElementById('newsletter_text').value.trim();
            const copyrightText = document.getElementById('copyright_text').value.trim();

            if (!companyName) {
                e.preventDefault();
                alert('Please enter company name');
                document.getElementById('company_name').focus();
                return;
            }

            if (!address) {
                e.preventDefault();
                alert('Please enter company address');
                document.getElementById('address').focus();
                return;
            }

            if (!customerCare) {
                e.preventDefault();
                alert('Please enter customer care number');
                document.getElementById('customer_care').focus();
                return;
            }

            if (!newsletterText) {
                e.preventDefault();
                alert('Please enter newsletter text');
                document.getElementById('newsletter_text').focus();
                return;
            }

            if (!copyrightText) {
                e.preventDefault();
                alert('Please enter copyright text');
                document.getElementById('copyright_text').focus();
                return;
            }

            // Check if there are actual changes
            const originalValues = {
                company_name: '<?php echo $companyInfo ? htmlspecialchars($companyInfo['company_name']) : 'Xordox International Pvt. Ltd'; ?>',
                address: `<?php echo $companyInfo ? str_replace(["\r\n", "\r", "\n"], '\n', htmlspecialchars($companyInfo['address'])) : "3398, Bagichi Acchi ji Bara Hindu Rao,\nNear Filmistan Cinema, New Delhi 110006,\nNew Delhi, India."; ?>`,
                customer_care: '<?php echo $companyInfo ? htmlspecialchars($companyInfo['customer_care']) : '+91-9818532463'; ?>',
                newsletter_text: '<?php echo $companyInfo ? htmlspecialchars($companyInfo['newsletter_text']) : 'Get updates on new products and offers. Coupons.'; ?>',
                copyright_text: '<?php echo $companyInfo ? htmlspecialchars($companyInfo['copyright_text']) : '© 2019-2024 printmont.com All Rights Reserved.'; ?>',
                status: '<?php echo $companyInfo ? $companyInfo['status'] : 'active'; ?>'
            };

            const currentValues = {
                company_name: companyName,
                address: address,
                customer_care: customerCare,
                newsletter_text: newsletterText,
                copyright_text: copyrightText,
                status: document.getElementById('status').value
            };

            let hasChanges = false;
            for (const key in originalValues) {
                if (originalValues[key] !== currentValues[key]) {
                    hasChanges = true;
                    break;
                }
            }

            if (!hasChanges) {
                e.preventDefault();
                alert('No changes were made to the company information.');
            }
        });

        // Initialize preview on page load
        document.addEventListener('DOMContentLoaded', updatePreview);
    </script>
</body>

</html>