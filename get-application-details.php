<?php
// get-application-details.php
session_start();
require_once 'config/database.php';
require_once 'controllers/CareerController.php';

if (!isset($_GET['id'])) {
    die('Application ID not provided.');
}

$careerController = new CareerController();
$application = $careerController->getApplicationById($_GET['id']);
$statusOptions = $careerController->getStatusOptions();

if (!$application) {
    die('Application not found.');
}

$statusColors = [
    'pending' => 'warning',
    'reviewed' => 'info',
    'shortlisted' => 'primary',
    'rejected' => 'danger',
    'hired' => 'success'
];
?>

<div class="row">
    <div class="col-md-6">
        <h6>Applicant Information</h6>
        <div class="mb-3">
            <strong>Name:</strong> <?php echo htmlspecialchars($application['applicant_name'] ?? $application['name'] ?? 'N/A'); ?><br>
            <strong>Email:</strong> <?php echo htmlspecialchars($application['applicant_email'] ?? $application['email'] ?? 'N/A'); ?><br>
            <?php if (!empty($application['applicant_phone']) || !empty($application['phone'])): ?>
                <strong>Phone:</strong> <?php echo htmlspecialchars($application['applicant_phone'] ?? $application['phone'] ?? ''); ?><br>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <h6>Application Details</h6>
        <div class="mb-3">
            <strong>Position:</strong> <?php echo htmlspecialchars($application['job_title'] ?? 'N/A'); ?><br>
            <strong>Applied:</strong> <?php echo !empty($application['applied_at']) ? date('F j, Y g:i A', strtotime($application['applied_at'])) : 'N/A'; ?><br>
            <strong>Status:</strong> 
            <?php $detailStatus = $application['status'] ?? 'pending'; ?>
            <span class="badge bg-<?php echo $statusColors[$detailStatus] ?? 'secondary'; ?>">
                <?php echo $statusOptions[$detailStatus] ?? ucfirst($detailStatus); ?>
            </span>
        </div>
    </div>
</div>

<?php if (!empty($application['cover_letter'])): ?>
<div class="row mt-3">
    <div class="col-12">
        <h6>Cover Letter</h6>
        <div class="border rounded p-3 bg-light">
            <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-3">
    <div class="col-12">
        <h6>Resume</h6>
        <div class="d-flex gap-2">
            <?php if (!empty($application['resume_path'])): ?>
                <a href="<?php echo htmlspecialchars($application['resume_path']); ?>" 
                   class="btn btn-success btn-sm" 
                   target="_blank">
                    <i class="fas fa-download"></i> Download Resume
                </a>
            <?php else: ?>
                <span class="text-muted fst-italic">No resume uploaded.</span>
            <?php endif; ?>
            <form method="POST" action="career-applications.php" class="d-inline">
                <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        Change Status
                    </button>
                    <ul class="dropdown-menu">
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <?php if ($value !== ($application['status'] ?? '')): ?>
                                <li>
                                    <input type="hidden" name="status" value="<?php echo $value; ?>">
                                    <button type="submit" name="update_status" class="dropdown-item">
                                        Mark as <?php echo $label; ?>
                                    </button>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </form>
        </div>
    </div>
</div>