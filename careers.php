<?php
// careers.php
session_start();
require_once 'config/database.php';
require_once 'controllers/CareerController.php';

$careerController = new CareerController();
$careers = $careerController->getAllCareers();
$stats = $careerController->getCareerStats();

// Handle deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_career'])) {
    $result = $careerController->deleteCareer($_POST['id']);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    header("Location: careers.php");
    exit;
}

// Get pending applications count
$pending_applications = 0;
$applications = $careerController->getJobApplications();
foreach ($applications as $app) {
    if ($app['status'] === 'pending') {
        $pending_applications++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Careers Management | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .career-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; border-left: 4px solid #007bff; }
        .job-card { border-left: 4px solid #007bff; }
        .application-card { border-left: 4px solid #28a745; }
        .department-badge { font-size: 0.75em; }
        .job-type-badge { font-size: 0.7em; }
        .action-buttons .btn { margin-right: 5px; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <!-- Display Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $_SESSION['success_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $_SESSION['error_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card career-header">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h2 class="card-title text-white mb-1">Careers Management</h2>
                                            <p class="card-text text-white-50 mb-0">Manage job postings and applications</p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <a href="add-career.php" class="btn btn-light btn-sm">
                                                <i class="fas fa-plus me-1"></i> Add Job Posting
                                            </a>
                                            <a href="career-applications.php" class="btn btn-outline-light btn-sm">
                                                <i class="fas fa-users me-1"></i> View Applications
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
                            <div class="stats-card" style="border-left-color: #007bff;">
                                <div class="text-center">
                                    <div class="h3 text-primary"><?php echo $stats['total_jobs']; ?></div>
                                    <div class="text-muted">Total Jobs</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card" style="border-left-color: #28a745;">
                                <div class="text-center">
                                    <div class="h3 text-success"><?php echo $stats['active_jobs']; ?></div>
                                    <div class="text-muted">Active Jobs</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card" style="border-left-color: #17a2b8;">
                                <div class="text-center">
                                    <div class="h3 text-info"><?php echo $stats['total_applications']; ?></div>
                                    <div class="text-muted">Total Applications</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card" style="border-left-color: #ffc107;">
                                <div class="text-center">
                                    <div class="h3 text-warning"><?php echo $pending_applications; ?></div>
                                    <div class="text-muted">Pending Applications</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Job Postings -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-briefcase me-2"></i>Job Postings
                                        <span class="badge bg-primary ms-2"><?php echo count($careers); ?></span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($careers)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                            <h5>No Job Postings Found</h5>
                                            <p class="text-muted">Get started by creating your first job posting.</p>
                                            <a href="add-career.php" class="btn btn-primary">
                                                <i class="fas fa-plus me-1"></i> Create First Job Posting
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Job Title</th>
                                                        <th>Department</th>
                                                        <th>Type</th>
                                                        <th>Location</th>
                                                        <th class="text-center">Applications</th>
                                                        <th>Status</th>
                                                        <th>Posted Date</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($careers as $career): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-start">
                                                                <div class="flex-grow-1">
                                                                    <strong class="d-block"><?php echo htmlspecialchars($career['job_title']); ?></strong>
                                                                    <?php if ($career['salary_range']): ?>
                                                                        <small class="text-muted"><?php echo htmlspecialchars($career['salary_range']); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            // FIXED: Using getDepartmentOptions() instead of getDepartments()
                                                            $departments = $careerController->getDepartmentOptions();
                                                            $deptClass = [
                                                                'technology' => 'bg-primary',
                                                                'design' => 'bg-info',
                                                                'marketing' => 'bg-success',
                                                                'sales' => 'bg-warning',
                                                                'operations' => 'bg-secondary',
                                                                'hr' => 'bg-danger',
                                                                'finance' => 'bg-dark'
                                                            ];
                                                            ?>
                                                            <span class="badge <?php echo $deptClass[$career['department']] ?? 'bg-secondary'; ?> department-badge">
                                                                <?php echo $departments[$career['department']] ?? ucfirst($career['department']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            // FIXED: Using getJobTypeOptions() instead of getJobTypes()
                                                            $jobTypes = $careerController->getJobTypeOptions();
                                                            $typeClass = [
                                                                'full_time' => 'bg-success',
                                                                'part_time' => 'bg-info',
                                                                'contract' => 'bg-warning',
                                                                'internship' => 'bg-primary',
                                                                'remote' => 'bg-secondary'
                                                            ];
                                                            ?>
                                                            <span class="badge <?php echo $typeClass[$career['job_type']] ?? 'bg-secondary'; ?> job-type-badge">
                                                                <?php echo $jobTypes[$career['job_type']] ?? ucfirst($career['job_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                                            <?php echo htmlspecialchars($career['location']); ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-light text-dark border">
                                                                <i class="fas fa-users me-1"></i><?php echo $career['applications_count']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $career['is_active'] ? 'success' : 'secondary'; ?>">
                                                                <i class="fas fa-circle me-1" style="font-size: 0.5em;"></i>
                                                                <?php echo $career['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo date('M j, Y', strtotime($career['created_at'])); ?>
                                                            </small>
                                                        </td>
                                                        <td class="text-center action-buttons">
                                                            <div class="btn-group" role="group">
                                                                <a href="careers.php?id=<?php echo $career['id']; ?>" class="btn btn-sm btn-outline-info" title="View Details" data-bs-toggle="tooltip">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="edit-career.php?id=<?php echo $career['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit" data-bs-toggle="tooltip">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="career-applications.php?career_id=<?php echo $career['id']; ?>" class="btn btn-sm btn-outline-success" title="View Applications" data-bs-toggle="tooltip">
                                                                    <i class="fas fa-users"></i>
                                                                </a>
                                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete \'<?php echo addslashes($career['job_title']); ?>\'? This action cannot be undone.')">
                                                                    <input type="hidden" name="id" value="<?php echo $career['id']; ?>">
                                                                    <button type="submit" name="delete_career" class="btn btn-sm btn-outline-danger" title="Delete" data-bs-toggle="tooltip">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
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
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>