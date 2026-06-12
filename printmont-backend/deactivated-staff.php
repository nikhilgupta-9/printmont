<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/StaffController.php';

$staffController = new StaffController();
$deactivatedStaff = $staffController->getDeactivatedStaff();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    switch ($_GET['action']) {
        case 'activate':
            if ($staffController->activateStaff($id)) {
                $_SESSION['success_message'] = "Staff member activated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to activate staff member.";
            }
            break;
            
        case 'delete':
            if ($staffController->deleteStaff($id)) {
                $_SESSION['success_message'] = "Staff member deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete staff member.";
            }
            break;
    }
    
    header("Location: deactivated-staff.php");
    exit;
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
    <title>Deactivated Staff | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .role-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .role-admin { background-color: #dc3545; color: white; }
        .role-manager { background-color: #fd7e14; color: white; }
        .role-staff { background-color: #20c997; color: white; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
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
                            <h3><strong>Deactivated</strong> Staff</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="staff.php" class="btn btn-primary">
                                <i class="fas fa-users"></i> View All Staff
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
                                    <h5 class="card-title">Deactivated Staff Members</h5>
                                    <h6 class="card-subtitle text-muted">Manage deactivated staff accounts.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($deactivatedStaff)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-user-times fa-3x text-muted mb-3"></i>
                                            <h5>No Deactivated Staff</h5>
                                            <p class="text-muted">All staff members are currently active.</p>
                                            <a href="staff.php" class="btn btn-primary">
                                                <i class="fas fa-users"></i> View All Staff
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Username</th>
                                                        <th>Email</th>
                                                        <th>Role</th>
                                                        <th>Last Login</th>
                                                        <th>Deactivated Since</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($deactivatedStaff as $member): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($member['username']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($member['email']); ?>
                                                            </td>
                                                            <td>
                                                                <span class="role-badge role-<?php echo $member['role']; ?>">
                                                                    <?php echo ucfirst($member['role']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo $member['last_login'] ? date('M j, Y g:i A', strtotime($member['last_login'])) : 'Never'; ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($member['updated_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="deactivated-staff.php?action=activate&id=<?php echo $member['id']; ?>" 
                                                                   class="btn btn-sm btn-success" 
                                                                   onclick="return confirm('Are you sure you want to activate this staff member?')"
                                                                   title="Activate">
                                                                   <i class="fas fa-user-check"></i>
                                                                </a>
                                                                <a href="deactivated-staff.php?action=delete&id=<?php echo $member['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to permanently delete this staff member? This action cannot be undone.')"
                                                                   title="Delete">
                                                                   <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>