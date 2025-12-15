<?php
// about-us.php
session_start();
require_once 'config/constants.php';
require_once 'controllers/AboutUsController.php';

$aboutUsController = new AboutUsController();
$sections = $aboutUsController->getAllSections();
$teamMembers = $aboutUsController->getAllTeamMembers();

// Handle deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_section'])) {
    if ($aboutUsController->deleteSection($_POST['id'])) {
        $_SESSION['success_message'] = "Section deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete section!";
    }
    header("Location: about-page.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_team_member'])) {
    if ($aboutUsController->deleteTeamMember($_POST['id'])) {
        $_SESSION['success_message'] = "Team member deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete team member!";
    }
    header("Location: about-page.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>About Us Management | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .section-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .section-card { border-left: 4px solid #007bff; }
        .team-card { border-left: 4px solid #28a745; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card section-header">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h2 class="card-title text-white mb-1">About Us Management</h2>
                                            <p class="card-text text-white-50 mb-0">Manage your about us page sections and team members</p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <a href="add-section.php" class="btn btn-light btn-sm">
                                                <i class="fas fa-plus"></i> Add Section
                                            </a>
                                            <a href="add-team-member.php" class="btn btn-outline-light btn-sm">
                                                <i class="fas fa-user-plus"></i> Add Team Member
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="text-center">
                                    <div class="h3 text-primary"><?php echo count($sections); ?></div>
                                    <div class="text-muted">Total Sections</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="text-center">
                                    <div class="h3 text-success"><?php echo count($teamMembers); ?></div>
                                    <div class="text-muted">Team Members</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="text-center">
                                    <div class="h3 text-info"><?php echo count(array_filter($sections, fn($s) => $s['is_active'])); ?></div>
                                    <div class="text-muted">Active Sections</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="text-center">
                                    <div class="h3 text-warning"><?php echo count(array_filter($teamMembers, fn($t) => $t['is_active'])); ?></div>
                                    <div class="text-muted">Active Team Members</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- About Us Sections -->
                        <div class="col-lg-6">
                            <div class="card section-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">About Us Sections</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($sections)): ?>
                                        <p class="text-muted text-center">No sections found. <a href="add-section.php">Add your first section</a></p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Type</th>
                                                        <th>Order</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($sections as $section): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($section['section_title']); ?></td>
                                                        <td>
                                                            <span class="badge bg-secondary"><?php echo ucfirst($section['section_type']); ?></span>
                                                        </td>
                                                        <td><?php echo $section['display_order']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $section['is_active'] ? 'success' : 'secondary'; ?>">
                                                                <?php echo $section['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="edit-about-page.php?id=<?php echo $section['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this section?')">
                                                                <input type="hidden" name="id" value="<?php echo $section['id']; ?>">
                                                                <button type="submit" name="delete_section" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
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

                        <!-- Team Members -->
                        <div class="col-lg-6">
                            <div class="card team-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Team Members</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($teamMembers)): ?>
                                        <p class="text-muted text-center">No team members found. <a href="add-team-member.php">Add your first team member</a></p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Position</th>
                                                        <th>Order</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($teamMembers as $member): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($member['position']); ?></td>
                                                        <td><?php echo $member['display_order']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $member['is_active'] ? 'success' : 'secondary'; ?>">
                                                                <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="edit-team-member.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this team member?')">
                                                                <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                                                <button type="submit" name="delete_team_member" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
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