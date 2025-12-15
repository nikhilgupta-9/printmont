<?php
require_once( __DIR__ . '/config/database.php');
require_once( __DIR__ . '/controllers/AuthController.php');
require_once( __DIR__ . '/controllers/FaqController.php');
require_once( __DIR__ . '/controllers/CategoryController.php');

// Initialize Controllers
$database = new Database();
$db = $database->getConnection();
$faqController = new FaqController($db);
// $categoryController = new CategoryController($db);

// Ensure tables exist
$faqController->ensureTableExists();

$message = '';
$message_type = 'success';
$current_faq = null;

// Get FAQ ID from URL
$faq_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$faq_id) {
    header('Location: faq-view.php');
    exit();
}

// Handle form submission
if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        // Prepare FAQ data
        $faq_data = [
            'question' => $_POST['question'] ?? '',
            'answer' => $_POST['answer'] ?? '',
            'category_id' => isset($_POST['category_id']) ? intval($_POST['category_id']) : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'display_order' => isset($_POST['display_order']) ? intval($_POST['display_order']) : 0,
            'keywords' => $_POST['keywords'] ?? '',
            'updated_by' => $_SESSION['user_id'] ?? 1 // Assuming you have user session
        ];

        $result = $faqController->updateFAQ($faq_id, $faq_data);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'danger';
        
        // Refresh FAQ data after update
        if ($result['success']) {
            $faq_result = $faqController->getFAQById($faq_id);
            $current_faq = $faq_result->fetch_assoc();
        }
    }
    
    // Handle delete action
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $result = $faqController->deleteFAQ($faq_id);
        if ($result['success']) {
            header('Location: faq-view.php?message=' . urlencode($result['message']) . '&type=success');
            exit();
        } else {
            $message = $result['message'];
            $message_type = 'danger';
        }
    }
}

// Get FAQ data for editing
$faq_result = $faqController->getFAQById($faq_id);
if ($faq_result && $faq_result->num_rows > 0) {
    $current_faq = $faq_result->fetch_assoc();
} else {
    header('Location: faq-view.php?message=FAQ+not+found&type=danger');
    exit();
}

// Get all categories for dropdown
// $categories_result = $categoryController->getAllCategories();
// $categories = [];
// if ($categories_result) {
//     while ($row = $categories_result->fetch_assoc()) {
//         $categories[] = $row;
//     }
// }

// Get FAQ statistics for sidebar
$stats = $faqController->getFAQStats();

$page_title = 'Edit FAQ - ' . htmlspecialchars($current_faq['question']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Edit FAQ - Admin Panel">
    <meta name="author" content="AdminKit">
    <meta name="keywords" content="admin, dashboard, faq, management">

    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />

    <title><?php echo $page_title; ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .faq-preview { 
            background-color: #f8f9fa; 
            border-left: 4px solid #0d6efd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .stats-card { 
            border-left: 4px solid #0d6efd; 
            margin-bottom: 15px;
        }
        .form-required:after { 
            content: " *"; 
            color: #dc3545; 
        }
        .character-counter {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .character-counter.warning {
            color: #ffc107;
        }
        .character-counter.danger {
            color: #dc3545;
        }
        .action-buttons {
            margin-top: 1rem;
        }
    </style>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>

        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>

            <main class="content">
                <div class="container-fluid p-0">

                    <!-- Header -->
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3>
                                <strong>Edit FAQ</strong>
                                <span class="badge bg-primary">Editing</span>
                            </h3>
                        </div>

                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="faq-view.php" class="btn btn-light bg-white me-2">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="confirmDelete(<?php echo $current_faq['id']; ?>, '<?php echo addslashes($current_faq['question']); ?>')">
                                <i class="fas fa-trash"></i> Delete FAQ
                            </button>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if (isset($message) && !empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type == 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show p-3" role="alert">
                        <i class="fas fa-<?php echo $message_type == 'error' ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Main Form -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">FAQ Information</h5>
                                    <h6 class="card-subtitle text-muted">Update FAQ content and settings.</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Current FAQ Preview -->
                                    <div class="faq-preview">
                                        <h6>Current FAQ Preview</h6>
                                        <p><strong>Question:</strong> <?php echo htmlspecialchars($current_faq['question']); ?></p>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Created: <?php echo date('M j, Y g:i A', strtotime($current_faq['created_at'])); ?> | 
                                                Updated: <?php echo date('M j, Y g:i A', strtotime($current_faq['updated_at'])); ?> |
                                                <i class="fas fa-eye me-1"></i> Views: <?php echo $current_faq['view_count']; ?>
                                            </small>
                                        </div>
                                    </div>

                                    <form method="POST" id="editFaqForm">
                                        <input type="hidden" name="action" value="update">
                                        
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="mb-3">
                                                    <label for="question" class="form-label form-required">Question</label>
                                                    <textarea class="form-control" id="question" name="question" 
                                                              rows="3" required placeholder="Enter the frequently asked question"
                                                              maxlength="500"><?php echo htmlspecialchars($current_faq['question']); ?></textarea>
                                                    <div class="character-counter" id="question_counter">
                                                        0/500 characters
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="display_order" class="form-label">Display Order</label>
                                                    <input type="number" class="form-control" id="display_order" 
                                                           name="display_order" value="<?php echo $current_faq['display_order']; ?>"
                                                           min="0" placeholder="Order in list">
                                                    <div class="form-text">Lower numbers display first</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="answer" class="form-label form-required">Answer</label>
                                            <textarea class="form-control" id="answer" name="answer" 
                                                      rows="8" required placeholder="Enter the detailed answer"
                                                      maxlength="2000"><?php echo htmlspecialchars($current_faq['answer']); ?></textarea>
                                            <div class="character-counter" id="answer_counter">
                                                0/2000 characters
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="category_id" class="form-label">Category</label>
                                                    <select class="form-select" id="category_id" name="category_id">
                                                        <option value="">-- No Category --</option>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?php echo $category['id']; ?>" 
                                                                <?php echo ($current_faq['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($category['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="form-text">Optional: Assign to a category</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="keywords" class="form-label">Keywords</label>
                                                    <input type="text" class="form-control" id="keywords" 
                                                           name="keywords" value="<?php echo htmlspecialchars($current_faq['keywords'] ?? ''); ?>"
                                                           placeholder="Comma-separated keywords for search">
                                                    <div class="form-text">e.g., pricing, features, support</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="is_active" name="is_active" value="1"
                                                       <?php echo $current_faq['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_active">
                                                    Active FAQ
                                                </label>
                                            </div>
                                            <div class="form-text">Inactive FAQs won't be shown to users</div>
                                        </div>
                                        
                                        <div class="action-buttons">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Update FAQ
                                            </button>
                                            <a href="faq-view.php" class="btn btn-secondary">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </a>
                                            <button type="button" class="btn btn-outline-danger float-end" 
                                                    onclick="confirmDelete(<?php echo $current_faq['id']; ?>, '<?php echo addslashes($current_faq['question']); ?>')">
                                                <i class="fas fa-trash me-1"></i> Delete FAQ
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-md-4">
                            <!-- FAQ Statistics -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">FAQ Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <div class="stats-card p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">Total Views</h6>
                                                <p class="text-muted mb-0">All time</p>
                                            </div>
                                            <div class="text-end">
                                                <h4 class="mb-0 text-primary"><?php echo $current_faq['view_count']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="stats-card p-3" style="border-left-color: #198754;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">Helpful Votes</h6>
                                                <p class="text-muted mb-0">User feedback</p>
                                            </div>
                                            <div class="text-end">
                                                <h4 class="mb-0 text-success"><?php echo $current_faq['helpful_count']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="stats-card p-3" style="border-left-color: #dc3545;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">Not Helpful</h6>
                                                <p class="text-muted mb-0">User feedback</p>
                                            </div>
                                            <div class="text-end">
                                                <h4 class="mb-0 text-danger"><?php echo $current_faq['not_helpful_count']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- FAQ Information -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title">FAQ Details</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo $current_faq['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $current_faq['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Display Order:</strong></td>
                                            <td><?php echo $current_faq['display_order']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Category:</strong></td>
                                            <td>
                                                <?php 
                                                if ($current_faq['category_id']) {
                                                    $category_name = 'Uncategorized';
                                                    foreach ($categories as $cat) {
                                                        if ($cat['id'] == $current_faq['category_id']) {
                                                            $category_name = htmlspecialchars($cat['name']);
                                                            break;
                                                        }
                                                    }
                                                    echo $category_name;
                                                } else {
                                                    echo '<span class="text-muted">No Category</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($current_faq['created_at'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Updated:</strong></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($current_faq['updated_at'])); ?></td>
                                        </tr>
                                        <?php if (!empty($current_faq['keywords'])): ?>
                                        <tr>
                                            <td><strong>Keywords:</strong></td>
                                            <td><?php echo htmlspecialchars($current_faq['keywords']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="faq-view.php" class="btn btn-outline-primary">
                                            <i class="fas fa-list me-1"></i> View All FAQs
                                        </a>
                                        <button type="button" class="btn btn-outline-success" onclick="previewFAQ()">
                                            <i class="fas fa-eye me-1"></i> Preview FAQ
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="resetForm()">
                                            <i class="fas fa-undo me-1"></i> Reset Changes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this FAQ? This action cannot be undone.</p>
                    <div class="alert alert-warning">
                        <strong>Question to delete:</strong><br>
                        <span id="deleteQuestion"></span>
                    </div>
                    <p class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        This FAQ has been viewed <?php echo $current_faq['view_count']; ?> times and received 
                        <?php echo $current_faq['helpful_count']; ?> helpful votes.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="delete_id" id="deleteId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> Delete FAQ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        // Character counters with color coding
        function setupCounter(textareaId, counterId, maxLength) {
            const textarea = document.getElementById(textareaId);
            const counter = document.getElementById(counterId);
            
            if (textarea && counter) {
                function updateCounter() {
                    const length = textarea.value.length;
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
                
                textarea.addEventListener('input', updateCounter);
                updateCounter(); // Initialize
            }
        }

        // Delete confirmation
        function confirmDelete(id, question) {
            document.getElementById('deleteQuestion').textContent = question;
            document.getElementById('deleteId').value = id;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Form validation
        function validateForm() {
            const form = document.getElementById('editFaqForm');
            const question = document.getElementById('question');
            const answer = document.getElementById('answer');
            
            form.addEventListener('submit', function(e) {
                const questionVal = question.value.trim();
                const answerVal = answer.value.trim();
                
                if (!questionVal || !answerVal) {
                    e.preventDefault();
                    alert('Please fill in all required fields (question and answer).');
                    question.focus();
                    return false;
                }
                
                if (questionVal.length > 500) {
                    e.preventDefault();
                    alert('Question is too long. Maximum 500 characters allowed.');
                    question.focus();
                    return false;
                }
                
                if (answerVal.length > 2000) {
                    e.preventDefault();
                    alert('Answer is too long. Maximum 2000 characters allowed.');
                    answer.focus();
                    return false;
                }
            });
        }

        // Preview FAQ (opens in new tab with formatted content)
        function previewFAQ() {
            const question = document.getElementById('question').value;
            const answer = document.getElementById('answer').value;
            
            if (!question || !answer) {
                alert('Please fill in both question and answer to preview.');
                return;
            }
            
            const previewWindow = window.open('', '_blank');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>FAQ Preview</title>
                    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; background-color: #f8f9fa; }
                        .faq-preview { max-width: 800px; margin: 0 auto; }
                        .faq-question { color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
                        .faq-answer { background: white; padding: 20px; border-radius: 5px; margin-top: 15px; }
                    </style>
                </head>
                <body>
                    <div class="faq-preview">
                        <h1 class="faq-question">${question}</h1>
                        <div class="faq-answer">
                            ${answer.replace(/\n/g, '<br>')}
                        </div>
                        <div class="mt-3 text-muted text-center">
                            <small>FAQ Preview - This is how your FAQ will appear to users</small>
                        </div>
                    </div>
                </body>
                </html>
            `);
            previewWindow.document.close();
        }

        // Reset form to original values
        function resetForm() {
            if (confirm('Are you sure you want to reset all changes? This cannot be undone.')) {
                document.getElementById('editFaqForm').reset();
                // Re-initialize counters
                setupCounter('question', 'question_counter', 500);
                setupCounter('answer', 'answer_counter', 2000);
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupCounter('question', 'question_counter', 500);
            setupCounter('answer', 'answer_counter', 2000);
            validateForm();
            
            // Auto-save reminder
            let formChanged = false;
            const form = document.getElementById('editFaqForm');
            const inputs = form.querySelectorAll('input, textarea, select');
            
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    formChanged = true;
                });
            });
            
            window.addEventListener('beforeunload', (e) => {
                if (formChanged) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                }
            });
            
            form.addEventListener('submit', () => {
                formChanged = false;
            });
        });
    </script>
</body>
</html>