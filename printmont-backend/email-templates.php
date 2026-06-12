<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/EmailController.php';

$emailController = new EmailController();
$templates = $emailController->getAllTemplates();

// Handle form submission
if ($_POST) {
    if (isset($_POST['add_template'])) {
        $data = [
            'template_name' => $_POST['template_name'],
            'template_subject' => $_POST['template_subject'],
            'template_body' => $_POST['template_body'],
            'template_type' => $_POST['template_type'],
            'status' => $_POST['status']
        ];
        
        if ($emailController->createTemplate($data)) {
            $_SESSION['success_message'] = "Email template created successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to create email template.";
        }
        header("Location: email-templates.php");
        exit;
    }
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
    <title>Email Templates | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .type-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .type-system { background-color: #e3f2fd; color: #1565c0; }
        .type-custom { background-color: #f3e5f5; color: #7b1fa2; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .template-preview { max-height: 100px; overflow: hidden; position: relative; }
        .template-preview:after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 20px; background: linear-gradient(transparent, #f8f9fa); }
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
                            <h3><strong>Email</strong> Templates</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                                <i class="fas fa-plus"></i> Add Template
                            </button>
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
                                    <h5 class="card-title">Email Templates</h5>
                                    <h6 class="card-subtitle text-muted">Manage your email templates for various purposes.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($templates)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                                            <h5>No Email Templates</h5>
                                            <p class="text-muted">Get started by creating your first email template.</p>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                                                <i class="fas fa-plus"></i> Add Template
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Template Name</th>
                                                        <th>Subject</th>
                                                        <th>Type</th>
                                                        <th>Status</th>
                                                        <th>Created</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($templates as $template): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($template['template_name']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($template['template_subject']); ?>">
                                                                    <?php echo htmlspecialchars($template['template_subject']); ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="type-badge type-<?php echo $template['template_type']; ?>">
                                                                    <?php echo ucfirst($template['template_type']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $template['status']; ?>">
                                                                    <?php echo ucfirst($template['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($template['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="edit-email-template.php?id=<?php echo $template['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-sm btn-info" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#previewTemplateModal"
                                                                        data-name="<?php echo htmlspecialchars($template['template_name']); ?>"
                                                                        data-subject="<?php echo htmlspecialchars($template['template_subject']); ?>"
                                                                        data-body="<?php echo htmlspecialchars($template['template_body']); ?>"
                                                                        title="Preview">
                                                                   <i class="fas fa-eye"></i>
                                                                </button>
                                                                <a href="delete-email-template.php?id=<?php echo $template['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this template?')"
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

    <!-- Add Template Modal -->
    <div class="modal fade" id="addTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Email Template</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Template Name *</label>
                                <input type="text" class="form-control" name="template_name" 
                                       placeholder="e.g., Welcome Email, Order Confirmation" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Template Type</label>
                                <select class="form-select" name="template_type">
                                    <option value="system">System</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Subject *</label>
                            <input type="text" class="form-control" name="template_subject" 
                                   placeholder="Subject line of the email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Body *</label>
                            <textarea class="form-control" name="template_body" rows="12" 
                                      placeholder="Enter the email template body. You can use HTML formatting." required></textarea>
                            <small class="form-text text-muted">
                                Available variables: {customer_name}, {order_id}, {site_name}, {site_url}
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_template" class="btn btn-primary">Create Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview Template Modal -->
    <div class="modal fade" id="previewTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Template Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 id="previewTemplateName" class="mb-2"></h6>
                    <p><strong>Subject:</strong> <span id="previewTemplateSubject"></span></p>
                    <div class="border p-3 bg-light">
                        <div id="previewTemplateBody"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview template functionality
        var previewTemplateModal = document.getElementById('previewTemplateModal');
        previewTemplateModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var templateName = button.getAttribute('data-name');
            var templateSubject = button.getAttribute('data-subject');
            var templateBody = button.getAttribute('data-body');
            
            document.getElementById('previewTemplateName').textContent = templateName;
            document.getElementById('previewTemplateSubject').textContent = templateSubject;
            document.getElementById('previewTemplateBody').innerHTML = templateBody;
        });
    </script>
</body>
</html>