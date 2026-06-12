<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CustomerController.php';

$customerController = new CustomerController();
$customerId = $_GET['id'] ?? 0;
$customer = $customerController->getCustomerById($customerId);

if (!$customer) {
    $_SESSION['error_message'] = "Customer not found!";
    header("Location: customers.php");
    exit;
}

// Handle form submission
if ($_POST) {
    if (isset($_POST['update_customer'])) {
        $data = [
            'company_name' => $_POST['company_name'],
            'phone' => $_POST['phone'],
            'address' => $_POST['address'],
            'city' => $_POST['city'],
            'state' => $_POST['state'],
            'country' => $_POST['country'],
            'postal_code' => $_POST['postal_code'],
            'customer_type' => $_POST['customer_type'],
            'status' => $_POST['status'],
            'notes' => $_POST['notes']
        ];
        
        try {
            $errors = $customerController->validateCustomerData($data);
            
            if (empty($errors)) {
                if ($customerController->updateCustomer($customerId, $data)) {
                    $_SESSION['success_message'] = "Customer updated successfully!";
                    header("Location: customers.php");
                    exit;
                }
            } else {
                $error_message = implode("<br>", $errors);
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? $error_message ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Customer | Printmont</title>
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
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Edit</strong> Customer</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="customers.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Customers
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
                            <div class="alert-message"><?php echo $error_message; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Edit Customer Information</h5>
                                    <h6 class="card-subtitle text-muted">Update customer details.</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Customer Basic Info -->
                                    <div class="mb-4 p-3 bg-light rounded">
                                        <h6>Basic Information</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Username:</strong> <?php echo htmlspecialchars($customer['username']); ?>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <strong>Total Orders:</strong> <?php echo $customer['total_orders']; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Total Spent:</strong> ₹<?php echo number_format($customer['total_spent'], 2); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Customer Type</label>
                                                <select class="form-select" name="customer_type">
                                                    <option value="individual" <?php echo $customer['customer_type'] == 'individual' ? 'selected' : ''; ?>>Individual</option>
                                                    <option value="business" <?php echo $customer['customer_type'] == 'business' ? 'selected' : ''; ?>>Business</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status">
                                                    <option value="active" <?php echo $customer['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $customer['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="suspended" <?php echo $customer['status'] == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Company Name</label>
                                            <input type="text" class="form-control" name="company_name" 
                                                   value="<?php echo htmlspecialchars($customer['company_name'] ?? ''); ?>" 
                                                   placeholder="Company name (for business customers)">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="tel" class="form-control" name="phone" 
                                                   value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" 
                                                   placeholder="+91 1234567890">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <textarea class="form-control" name="address" rows="3" 
                                                      placeholder="Full address"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">City</label>
                                                <input type="text" class="form-control" name="city" 
                                                       value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>" 
                                                       placeholder="City">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">State</label>
                                                <input type="text" class="form-control" name="state" 
                                                       value="<?php echo htmlspecialchars($customer['state'] ?? ''); ?>" 
                                                       placeholder="State">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Postal Code</label>
                                                <input type="text" class="form-control" name="postal_code" 
                                                       value="<?php echo htmlspecialchars($customer['postal_code'] ?? ''); ?>" 
                                                       placeholder="PIN code">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Country</label>
                                            <input type="text" class="form-control" name="country" 
                                                   value="<?php echo htmlspecialchars($customer['country'] ?? 'India'); ?>" 
                                                   placeholder="Country">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Notes</label>
                                            <textarea class="form-control" name="notes" rows="3" 
                                                      placeholder="Additional notes about this customer"><?php echo htmlspecialchars($customer['notes'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <button type="submit" name="update_customer" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Customer
                                            </button>
                                            <a href="customers.php" class="btn btn-secondary">Cancel</a>
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
</body>
</html>