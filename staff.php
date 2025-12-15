<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/StaffController.php';

$staffController = new StaffController();
$staff = $staffController->getAllStaff(true); // Include inactive staff
$stats = $staffController->getStaffStats();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    switch ($_GET['action']) {
        case 'deactivate':
            if ($staffController->deactivateStaff($id)) {
                $_SESSION['success_message'] = "Staff member deactivated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to deactivate staff member.";
            }
            break;
            
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
    
    header("Location: staff.php");
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
    <title>All Staff | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .role-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .role-admin { background-color: #dc3545; color: white; }
        .role-manager { background-color: #fd7e14; color: white; }
        .role-staff { background-color: #20c997; color: white; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .stats-card { border-left: 4px solid; }
        .stats-card.total { border-color: #007bff; }
        .stats-card.active { border-color: #28a745; }
        .stats-card.inactive { border-color: #dc3545; }
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
                            <h3><strong>All</strong> Staff</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="add-staff.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Staff
                            </a>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card total">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Staff</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_staff']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card active">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Active Staff</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['active_staff']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card inactive">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Inactive Staff</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['inactive_staff']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                                                Roles</div>
                                            <div class="h6 mb-0 font-weight-bold">
                                                Admin: <?php echo $stats['admin_count']; ?> | 
                                                Manager: <?php echo $stats['manager_count']; ?> | 
                                                Staff: <?php echo $stats['staff_count']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                                    <h5 class="card-title">Staff Members</h5>
                                    <h6 class="card-subtitle text-muted">Manage your staff members and their permissions.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($staff)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h5>No Staff Members</h5>
                                            <p class="text-muted">Get started by adding your first staff member.</p>
                                            <a href="add-staff.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Staff
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
                                                        <th>Status</th>
                                                        <th>Last Login</th>
                                                        <th>Created</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($staff as $member): ?>
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
                                                                <span class="status-badge status-<?php echo $member['status']; ?>">
                                                                    <?php echo ucfirst($member['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo $member['last_login'] ? date('M j, Y g:i A', strtotime($member['last_login'])) : 'Never'; ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($member['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="edit-staff.php?id=<?php echo $member['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <?php if ($member['status'] == 'active'): ?>
                                                                    <a href="staff.php?action=deactivate&id=<?php echo $member['id']; ?>" 
                                                                       class="btn btn-sm btn-secondary" 
                                                                       onclick="return confirm('Are you sure you want to deactivate this staff member?')"
                                                                       title="Deactivate">
                                                                       <i class="fas fa-user-slash"></i>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <a href="staff.php?action=activate&id=<?php echo $member['id']; ?>" 
                                                                       class="btn btn-sm btn-success" 
                                                                       onclick="return confirm('Are you sure you want to activate this staff member?')"
                                                                       title="Activate">
                                                                       <i class="fas fa-user-check"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                                <a href="staff.php?action=delete&id=<?php echo $member['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this staff member? This action cannot be undone.')"
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