<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/StaffController.php';

$staffController = new StaffController();
$id = $_GET['id'] ?? 0;
$staff = $staffController->getStaffById($id);

if (!$staff) {
    $_SESSION['error_message'] = "Staff member not found!";
    header("Location: staff.php");
    exit;
}

// Handle form submission
if ($_POST) {
    if (isset($_POST['update_staff'])) {
        try {
            $data = [
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'role' => $_POST['role'],
                'status' => $_POST['status']
            ];

            // Only update password if provided
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }
            
            if ($staffController->updateStaff($id, $data)) {
                $_SESSION['success_message'] = "Staff member updated successfully!";
                header("Location: staff.php");
                exit;
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
    <title>Edit Staff | Printmont</title>
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
                            <h3><strong>Edit</strong> Staff</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="staff.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Staff
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
                                    <h5 class="card-title">Edit Staff Member</h5>
                                    <h6 class="card-subtitle text-muted">Update staff information and permissions.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Username *</label>
                                                <input type="text" class="form-control" name="username" 
                                                       value="<?php echo htmlspecialchars($staff['username']); ?>" 
                                                       placeholder="Enter username" required>
                                                <small class="form-text text-muted">Must be unique and 3-20 characters long.</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email Address *</label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?php echo htmlspecialchars($staff['email']); ?>" 
                                                       placeholder="staff@example.com" required>
                                                <small class="form-text text-muted">Must be a valid and unique email address.</small>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">New Password</label>
                                                <input type="password" class="form-control" name="password" 
                                                       placeholder="Leave blank to keep current password">
                                                <small class="form-text text-muted">Minimum 6 characters. Leave blank to keep current password.</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Confirm New Password</label>
                                                <input type="password" class="form-control" name="confirm_password" 
                                                       placeholder="Confirm new password">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Role *</label>
                                                <select class="form-select" name="role" required>
                                                    <option value="staff" <?php echo $staff['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                                    <option value="manager" <?php echo $staff['role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                                    <option value="admin" <?php echo $staff['role'] == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Status *</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="active" <?php echo $staff['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $staff['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Staff Information -->
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <h6>Staff Information</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <tr>
                                                            <th width="30%">Last Login</th>
                                                            <td><?php echo $staff['last_login'] ? date('M j, Y g:i A', strtotime($staff['last_login'])) : 'Never'; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Account Created</th>
                                                            <td><?php echo date('M j, Y g:i A', strtotime($staff['created_at'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Last Updated</th>
                                                            <td><?php echo date('M j, Y g:i A', strtotime($staff['updated_at'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Login Attempts</th>
                                                            <td><?php echo $staff['login_attempts'] ?? 0; ?></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3 mt-4">
                                            <button type="submit" name="update_staff" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Staff Member
                                            </button>
                                            <a href="staff.php" class="btn btn-secondary">Cancel</a>
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
        // Password confirmation validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            
            if (password.value !== '' && password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
            }
            
            if (password.value !== '' && password.value.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                password.focus();
            }
        });
    </script>
</body>
</html>