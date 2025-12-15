<?php
// add-career.php
session_start();
require_once 'config/database.php';
require_once 'controllers/CareerController.php';

$careerController = new CareerController();
$pageTitle = "Add Job Posting";

// FIXED: Use the correct method names
$departments = $careerController->getDepartmentOptions();
$jobTypes = $careerController->getJobTypeOptions();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'job_title' => $_POST['job_title'] ?? '',
            'department' => $_POST['department'] ?? '',
            'job_type' => $_POST['job_type'] ?? '',
            'location' => $_POST['location'] ?? '',
            'description' => $_POST['description'] ?? '',
            'requirements' => $_POST['requirements'] ?? '',
            'responsibilities' => $_POST['responsibilities'] ?? '',
            'salary_range' => $_POST['salary_range'] ?? '',
            'application_deadline' => $_POST['application_deadline'] ?? null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Create career using the controller method that returns array
        $result = $careerController->createCareer($data);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header("Location: careers.php");
            exit;
        } else {
            throw new Exception($result['message']);
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $pageTitle; ?> | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        .form-section h6 {
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .character-counter {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .character-counter.warning { color: #ffc107; }
        .character-counter.danger { color: #dc3545; }
        .form-required:after { content: " *"; color: #dc3545; }
        .preview-modal { background-color: rgba(0,0,0,0.5); }
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
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-plus me-2"></i><?php echo $pageTitle; ?>
                                    </h5>
                                    <p class="text-muted mb-0">Create a new job posting for your careers page</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Display Messages -->
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $_SESSION['error_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="POST" id="careerForm">
                                        <!-- Basic Information -->
                                        <div class="form-section">
                                            <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="job_title" class="form-label form-required">Job Title</label>
                                                        <input type="text" class="form-control" id="job_title" name="job_title" 
                                                               value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>" required
                                                               placeholder="e.g., Senior Web Developer"
                                                               maxlength="255">
                                                        <div class="character-counter" id="job_title_counter">0/255 characters</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="location" class="form-label form-required">Location</label>
                                                        <input type="text" class="form-control" id="location" name="location" 
                                                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" required
                                                               placeholder="e.g., New York, NY or Remote"
                                                               maxlength="255">
                                                        <div class="character-counter" id="location_counter">0/255 characters</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="department" class="form-label form-required">Department</label>
                                                        <select class="form-select" id="department" name="department" required>
                                                            <option value="">Select Department</option>
                                                            <?php foreach ($departments as $value => $label): ?>
                                                                <option value="<?php echo $value; ?>" 
                                                                        <?php echo ($_POST['department'] ?? '') == $value ? 'selected' : ''; ?>>
                                                                    <?php echo $label; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="job_type" class="form-label form-required">Job Type</label>
                                                        <select class="form-select" id="job_type" name="job_type" required>
                                                            <option value="">Select Job Type</option>
                                                            <?php foreach ($jobTypes as $value => $label): ?>
                                                                <option value="<?php echo $value; ?>" 
                                                                        <?php echo ($_POST['job_type'] ?? '') == $value ? 'selected' : ''; ?>>
                                                                    <?php echo $label; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="salary_range" class="form-label">Salary Range</label>
                                                        <input type="text" class="form-control" id="salary_range" name="salary_range" 
                                                               value="<?php echo htmlspecialchars($_POST['salary_range'] ?? ''); ?>"
                                                               placeholder="e.g., $60,000 - $80,000"
                                                               maxlength="100">
                                                        <div class="form-text">Optional - will be displayed to candidates</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="application_deadline" class="form-label">Application Deadline</label>
                                                        <input type="date" class="form-control" id="application_deadline" name="application_deadline" 
                                                               value="<?php echo htmlspecialchars($_POST['application_deadline'] ?? ''); ?>"
                                                               min="<?php echo date('Y-m-d'); ?>">
                                                        <div class="form-text">Leave empty for no deadline</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <div class="form-check form-switch mt-2">
                                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                                            <label class="form-check-label" for="is_active">
                                                                Active Job Posting
                                                            </label>
                                                        </div>
                                                        <div class="form-text">Inactive postings won't be visible to candidates</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Job Description -->
                                        <div class="form-section">
                                            <h6><i class="fas fa-file-alt me-2"></i>Job Description</h6>
                                            <div class="mb-3">
                                                <label for="description" class="form-label form-required">Job Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="6" 
                                                          required placeholder="Describe the role, company culture, and what makes this position exciting..."
                                                          maxlength="5000"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                                <div class="character-counter" id="description_counter">0/5000 characters</div>
                                                <div class="form-text">Describe the role and what candidates can expect.</div>
                                            </div>
                                        </div>

                                        <!-- Responsibilities -->
                                        <div class="form-section">
                                            <h6><i class="fas fa-tasks me-2"></i>Key Responsibilities</h6>
                                            <div class="mb-3">
                                                <label for="responsibilities" class="form-label form-required">Responsibilities</label>
                                                <textarea class="form-control" id="responsibilities" name="responsibilities" rows="6" 
                                                          required placeholder="List the main responsibilities and duties..."
                                                          maxlength="3000"><?php echo htmlspecialchars($_POST['responsibilities'] ?? ''); ?></textarea>
                                                <div class="character-counter" id="responsibilities_counter">0/3000 characters</div>
                                                <div class="form-text">List key responsibilities (one per line or use bullet points).</div>
                                            </div>
                                        </div>

                                        <!-- Requirements -->
                                        <div class="form-section">
                                            <h6><i class="fas fa-graduation-cap me-2"></i>Requirements & Qualifications</h6>
                                            <div class="mb-3">
                                                <label for="requirements" class="form-label form-required">Requirements</label>
                                                <textarea class="form-control" id="requirements" name="requirements" rows="6" 
                                                          required placeholder="List the required skills, experience, and qualifications..."
                                                          maxlength="3000"><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
                                                <div class="character-counter" id="requirements_counter">0/3000 characters</div>
                                                <div class="form-text">List required and preferred qualifications (one per line).</div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="mt-4 pt-3 border-top">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-plus me-2"></i> Create Job Posting
                                            </button>
                                            <a href="careers.php" class="btn btn-outline-secondary btn-lg">
                                                <i class="fas fa-arrow-left me-2"></i> Back to List
                                            </a>
                                            
                                            <button type="button" class="btn btn-outline-info btn-lg" onclick="previewJobPosting()">
                                                <i class="fas fa-eye me-2"></i> Preview
                                            </button>

                                            <button type="reset" class="btn btn-outline-warning btn-lg">
                                                <i class="fas fa-undo me-2"></i> Reset Form
                                            </button>
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

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Job Posting Preview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="previewContent">
                    <!-- Preview content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close Preview
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        // Character counters
        function setupCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);
            
            if (input && counter) {
                function updateCounter() {
                    const length = input.value.length;
                    const percentage = (length / maxLength) * 100;
                    
                    counter.textContent = `${length}/${maxLength} characters`;
                    
                    // Update color based on usage
                    counter.className = 'character-counter';
                    if (percentage > 90) {
                        counter.classList.add('danger');
                    } else if (percentage > 75) {
                        counter.classList.add('warning');
                    }
                }
                
                input.addEventListener('input', updateCounter);
                updateCounter(); // Initialize
            }
        }

        // Auto-resize textareas
        function setupAutoResize(textareaId) {
            const textarea = document.getElementById(textareaId);
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
                // Trigger initial resize
                textarea.dispatchEvent(new Event('input'));
            }
        }

        // Preview job posting
        function previewJobPosting() {
            const title = document.getElementById('job_title').value || '[No Title]';
            const departmentSelect = document.getElementById('department');
            const department = departmentSelect.options[departmentSelect.selectedIndex]?.text || 'Not specified';
            const jobTypeSelect = document.getElementById('job_type');
            const jobType = jobTypeSelect.options[jobTypeSelect.selectedIndex]?.text || 'Not specified';
            const location = document.getElementById('location').value || 'Not specified';
            const salary = document.getElementById('salary_range').value || 'Not specified';
            const deadline = document.getElementById('application_deadline').value || 'No deadline';
            const description = document.getElementById('description').value || '[No Description]';
            const responsibilities = document.getElementById('responsibilities').value || '[No Responsibilities]';
            const requirements = document.getElementById('requirements').value || '[No Requirements]';
            const isActive = document.getElementById('is_active').checked;
            
            const formattedDeadline = deadline === 'No deadline' ? 'No deadline' : new Date(deadline).toLocaleDateString();
            
            const previewHTML = `
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">${title}</h4>
                            <span class="badge bg-${isActive ? 'success' : 'secondary'}">${isActive ? 'Active' : 'Inactive'}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Job Meta Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="d-flex flex-column">
                                    <div class="mb-2">
                                        <strong><i class="fas fa-building me-2 text-muted"></i>Department:</strong>
                                        <span class="badge bg-primary ms-2">${department}</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="fas fa-briefcase me-2 text-muted"></i>Job Type:</strong>
                                        <span class="badge bg-info ms-2">${jobType}</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="fas fa-map-marker-alt me-2 text-muted"></i>Location:</strong>
                                        ${location}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex flex-column">
                                    <div class="mb-2">
                                        <strong><i class="fas fa-money-bill-wave me-2 text-muted"></i>Salary Range:</strong>
                                        ${salary}
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="fas fa-calendar-alt me-2 text-muted"></i>Application Deadline:</strong>
                                        ${formattedDeadline}
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="fas fa-clock me-2 text-muted"></i>Posted:</strong>
                                        ${new Date().toLocaleDateString()}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Job Description -->
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">
                                <i class="fas fa-file-alt me-2 text-primary"></i>Job Description
                            </h5>
                            <div style="white-space: pre-line; line-height: 1.6; font-size: 1.05rem;" class="mt-3">
                                ${description}
                            </div>
                        </div>
                        
                        <!-- Responsibilities -->
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">
                                <i class="fas fa-tasks me-2 text-success"></i>Key Responsibilities
                            </h5>
                            <div style="white-space: pre-line; line-height: 1.6; font-size: 1.05rem;" class="mt-3">
                                ${responsibilities}
                            </div>
                        </div>
                        
                        <!-- Requirements -->
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">
                                <i class="fas fa-graduation-cap me-2 text-info"></i>Requirements & Qualifications
                            </h5>
                            <div style="white-space: pre-line; line-height: 1.6; font-size: 1.05rem;" class="mt-3">
                                ${requirements}
                            </div>
                        </div>
                        
                        <!-- Call to Action -->
                        <div class="alert alert-info mt-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle fa-2x me-3"></i>
                                <div>
                                    <h6 class="alert-heading mb-1">Ready to Apply?</h6>
                                    <p class="mb-0">This is how your job posting will appear to candidates. Make sure all information is accurate and compelling!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('previewContent').innerHTML = previewHTML;
            const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            previewModal.show();
        }

        // Form validation
        function validateForm() {
            const form = document.getElementById('careerForm');
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields.');
                return false;
            }
            
            return true;
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Setup character counters
            setupCounter('job_title', 'job_title_counter', 255);
            setupCounter('location', 'location_counter', 255);
            setupCounter('description', 'description_counter', 5000);
            setupCounter('responsibilities', 'responsibilities_counter', 3000);
            setupCounter('requirements', 'requirements_counter', 3000);
            
            // Setup auto-resize for textareas
            setupAutoResize('description');
            setupAutoResize('responsibilities');
            setupAutoResize('requirements');
            
            // Form validation
            const form = document.getElementById('careerForm');
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                }
            });
            
            // Reset form handler
            form.addEventListener('reset', function() {
                // Reset character counters
                document.querySelectorAll('.character-counter').forEach(counter => {
                    counter.textContent = '0/' + counter.textContent.split('/')[1];
                    counter.className = 'character-counter';
                });
                
                // Reset textarea heights
                ['description', 'responsibilities', 'requirements'].forEach(id => {
                    const textarea = document.getElementById(id);
                    if (textarea) {
                        textarea.style.height = 'auto';
                    }
                });
            });
        });
    </script>
</body>
</html>