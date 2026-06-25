<?php
// career-applications.php
session_start();
require_once 'config/database.php';
require_once 'controllers/CareerController.php';

$careerController = new CareerController();
$careerId = $_GET['career_id'] ?? null;
$applications = $careerController->getJobApplications($careerId);
$careers = $careerController->getAllCareers();
$statusOptions = $careerController->getStatusOptions();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if ($careerController->updateApplicationStatus($_POST['id'], $_POST['status'])) {
        $_SESSION['success_message'] = "Application status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update application status!";
    }
    header("Location: career-applications.php" . ($careerId ? "?career_id=" . $careerId : ""));
    exit;
}

// Handle application deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_application'])) {
    $application = $careerController->getApplicationById($_POST['id']);
    if ($application && file_exists($application['resume_path'])) {
        unlink($application['resume_path']);
    }
    
    if ($careerController->deleteApplication($_POST['id'])) {
        $_SESSION['success_message'] = "Application deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete application!";
    }
    header("Location: career-applications.php" . ($careerId ? "?career_id=" . $careerId : ""));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Job Applications | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .applications-header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
        .stats-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .application-card { border-left: 4px solid #007bff; }
        .status-badge { font-size: 0.75em; }
        .filter-section { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
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
                            <?php echo $_SESSION['success_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card applications-header">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h2 class="card-title text-white mb-1">Job Applications</h2>
                                            <p class="card-text text-white-50 mb-0">
                                                <?php if ($careerId && $applications): ?>
                                                    Applications for: <?php echo htmlspecialchars($applications[0]['job_title']); ?>
                                                <?php else: ?>
                                                    All job applications
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <a href="careers.php" class="btn btn-light btn-sm">
                                                <i class="fas fa-briefcase"></i> View Jobs
                                            </a>
                                            <?php if ($careerId): ?>
                                                <a href="career-applications.php" class="btn btn-outline-light btn-sm">
                                                    <i class="fas fa-list"></i> All Applications
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-section">
                        <div class="row">
                            <div class="col-md-8">
                                <h5>Filter Applications</h5>
                                <form method="GET" class="row g-3">
                                    <div class="col-md-6">
                                        <label for="career_filter" class="form-label">Filter by Job</label>
                                        <select class="form-select" id="career_filter" name="career_id" onchange="this.form.submit()">
                                            <option value="">All Jobs</option>
                                            <?php foreach ($careers as $career): ?>
                                                <option value="<?php echo $career['id']; ?>" 
                                                        <?php echo $careerId == $career['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($career['job_title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status_filter" class="form-label">Filter by Status</label>
                                        <select class="form-select" id="status_filter" onchange="filterByStatus(this.value)">
                                            <option value="">All Statuses</option>
                                            <?php foreach ($statusOptions as $value => $label): ?>
                                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <h5>Quick Stats</h5>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-center">
                                            <div class="h4 text-primary mb-1"><?php echo count($applications); ?></div>
                                            <small class="text-muted">Total</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center">
                                            <div class="h4 text-warning mb-1">
                                                <?php echo count(array_filter($applications, fn($app) => $app['status'] === 'pending')); ?>
                                            </div>
                                            <small class="text-muted">Pending</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Applications List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card application-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        Applications 
                                        <span class="badge bg-primary"><?php echo count($applications); ?></span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($applications)): ?>
                                        <p class="text-muted text-center">No applications found.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="applicationsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Applicant</th>
                                                        <th>Job Position</th>
                                                        <th>Contact</th>
                                                        <th>Applied Date</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($applications as $application): ?>
                                                    <tr class="application-row" data-status="<?php echo $application['status']; ?>">
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($application['applicant_name']); ?></strong>
                                                            <?php if ($application['cover_letter']): ?>
                                                                <br>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-file-alt"></i> Has cover letter
                                                                </small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($application['job_title']); ?>
                                                        </td>
                                                        <td>
                                                            <div>
                                                                <i class="fas fa-envelope text-muted me-1"></i>
                                                                <?php echo htmlspecialchars($application['applicant_email']); ?>
                                                            </div>
                                                            <?php if ($application['applicant_phone']): ?>
                                                                <div>
                                                                    <i class="fas fa-phone text-muted me-1"></i>
                                                                    <?php echo htmlspecialchars($application['applicant_phone']); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo date('M j, Y', strtotime($application['applied_at'])); ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?php echo date('g:i A', strtotime($application['applied_at'])); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $statusColors = [
                                                                'pending' => 'warning',
                                                                'reviewed' => 'info',
                                                                'shortlisted' => 'primary',
                                                                'rejected' => 'danger',
                                                                'hired' => 'success'
                                                            ];
                                                            ?>
                                                            <span class="badge bg-<?php echo $statusColors[$application['status']] ?? 'secondary'; ?> status-badge">
                                                                <?php echo $statusOptions[$application['status']] ?? ucfirst($application['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button type="button" class="btn btn-outline-info" 
                                                                        onclick="viewApplication(<?php echo $application['id']; ?>)"
                                                                        title="View Details">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                
                                                                <button type="button" class="btn btn-outline-primary dropdown-toggle" 
                                                                        data-bs-toggle="dropdown" title="Change Status">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <?php foreach ($statusOptions as $value => $label): ?>
                                                                        <?php if ($value !== $application['status']): ?>
                                                                            <li>
                                                                                <form method="POST" class="d-inline">
                                                                                    <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
                                                                                    <input type="hidden" name="status" value="<?php echo $value; ?>">
                                                                                    <button type="submit" name="update_status" class="dropdown-item">
                                                                                        Mark as <?php echo $label; ?>
                                                                                    </button>
                                                                                </form>
                                                                            </li>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                                
                                                                <a href="<?php echo $application['resume_path']; ?>" 
                                                                   class="btn btn-outline-success" 
                                                                   target="_blank"
                                                                   title="Download Resume">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                                
                                                                <form method="POST" class="d-inline" 
                                                                      onsubmit="return confirm('Are you sure you want to delete this application?')">
                                                                    <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
                                                                    <button type="submit" name="delete_application" class="btn btn-outline-danger" title="Delete">
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

    <!-- View Application Modal -->
    <div class="modal fade" id="viewApplicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="applicationDetails">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        function filterByStatus(status) {
            const rows = document.querySelectorAll('.application-row');
            rows.forEach(row => {
                if (!status || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function viewApplication(applicationId) {
            fetch('get-application-details.php?id=' + applicationId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('applicationDetails').innerHTML = data;
                    const modal = new bootstrap.Modal(document.getElementById('viewApplicationModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('applicationDetails').innerHTML = '<p class="text-danger">Error loading application details.</p>';
                    const modal = new bootstrap.Modal(document.getElementById('viewApplicationModal'));
                    modal.show();
                });
        }

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>