<?php
require_once( __DIR__ . '/config/database.php');
require_once( __DIR__ . '/controllers/AuthController.php');
require_once( __DIR__ . '/controllers/FaqController.php');
require_once( __DIR__ . '/controllers/FaqCategoryController.php');

// Initialize Controllers
$database = new Database();
$db = $database->getConnection();
$faqController = new FaqController($db);
$categoryController = new CategoryController($db);

// Ensure table exists
$faqController->ensureTableExists();

$message = '';
$message_type = 'success';
$current_faq = null;

// Get FAQ ID from URL for edit mode
$faq_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get current user ID (you can replace this with your session logic)
$current_user_id = $_SESSION['user_id'] ?? 1;

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                // Add user info to POST data
                $_POST['created_by'] = $current_user_id;
                $_POST['updated_by'] = $current_user_id;
                $result = $faqController->createFAQ($_POST);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'update':
                if ($faq_id) {
                    $_POST['updated_by'] = $current_user_id;
                    $result = $faqController->updateFAQ($faq_id, $_POST);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'danger';
                    
                    // Refresh FAQ data after update
                    if ($result['success']) {
                        $faq_result = $faqController->getFAQById($faq_id);
                        $current_faq = $faq_result->fetch_assoc();
                    }
                }
                break;
                
            case 'delete':
                if (isset($_POST['delete_id'])) {
                    $result = $faqController->deleteFAQ($_POST['delete_id']);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'danger';
                    $faq_id = 0; // Reset to list view after delete
                }
                break;
        }
    }
}

// Get FAQ data for editing
if ($faq_id) {
    $faq_result = $faqController->getFAQById($faq_id);
    if ($faq_result && $faq_result->num_rows > 0) {
        $current_faq = $faq_result->fetch_assoc();
    } else {
        $message = 'FAQ not found!';
        $message_type = 'danger';
        $faq_id = 0;
    }
}

// Get all FAQs for listing
$faqs_result = $faqController->getAllFAQs();
$faqs = [];
if ($faqs_result) {
    while ($row = $faqs_result->fetch_assoc()) {
        $faqs[] = $row;
    }
}

// Get all categories for dropdown
$categories_result = $categoryController->getAllCategories();
$categories = [];
if ($categories_result) {
   $categories = $categories_result; // already an array


}

// Get statistics
$stats = $faqController->getFAQStats();

$page_title = $faq_id ? 'Edit FAQ' : 'FAQ Management';
$form_title = $faq_id ? 'Edit FAQ' : 'Add New FAQ';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="AdminKit">
    <meta name="keywords"
        content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />

    <title><?php echo $page_title; ?> - Admin Panel</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .faq-item { transition: all 0.3s ease; }
        .faq-item:hover { background-color: #f8f9fa; }
        .action-buttons .btn { margin-right: 5px; margin-bottom: 5px; }
        .stats-card { border-left: 4px solid #0d6efd; }
        .form-required:after { content: " *"; color: #dc3545; }
        .current-faq { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .character-counter { font-size: 0.875rem; margin-top: 0.25rem; }
        .character-counter.warning { color: #ffc107; }
        .character-counter.danger { color: #dc3545; }
        .metadata-card { background-color: #f8f9fa; border-left: 4px solid #6c757d; }
    </style>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>

        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>

            <main class="content">
                <div class="container-fluid p-0">

                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3>
                                <strong><?php echo $page_title; ?></strong>
                                <?php if ($faq_id): ?>
                                    <span class="badge bg-primary">Editing</span>
                                <?php endif; ?>
                            </h3>
                        </div>

                        <div class="col-auto ms-auto text-end mt-n1">
                            <?php if ($faq_id): ?>
                                <a href="faq-view.php" class="btn btn-light bg-white me-2">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            <?php else: ?>
                                <a href="faq-view-category.php" class="btn btn-info" >
                                    <i class="fas fa-plus"></i> Add New FAQ Category
                            </a>
                                <button type="button" class="btn btn-primary" onclick="showAddForm()">
                                    <i class="fas fa-plus"></i> Add New FAQ
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (isset($message) && !empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type == 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show p-3" role="alert">
                        <i class="fas fa-<?php echo $message_type == 'error' ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (!$faq_id): ?>
                    <!-- Statistics and List View -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $stats['total_faqs']; ?></h5>
                                    <p class="card-text text-muted">Total FAQs</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card" style="border-left-color: #198754;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $stats['active_faqs']; ?></h5>
                                    <p class="card-text text-muted">Active FAQs</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card" style="border-left-color: #ffc107;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $stats['total_views']; ?></h5>
                                    <p class="card-text text-muted">Total Views</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card" style="border-left-color: #6f42c1;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo isset($stats['helpful_count']) ? $stats['helpful_count'] : 0; ?>
</h5>
                                    <p class="card-text text-muted">Helpful Votes</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">All FAQs</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($faqs) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($faqs as $faq): ?>
                                        <div class="list-group-item faq-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-1 text-center">
                                                    <span class="badge bg-light text-dark"><?php echo $faq['display_order']; ?></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($faq['question']); ?></h6>
                                                    <p class="mb-1 text-muted small">
                                                        <?php 
                                                        $answer_preview = strip_tags($faq['answer']);
                                                        echo strlen($answer_preview) > 150 ? 
                                                            substr($answer_preview, 0, 150) . '...' : 
                                                            $answer_preview;
                                                        ?>
                                                    </p>
                                                    <div class="mt-2">
                                                        <span class="badge <?php echo $faq['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                            <?php echo $faq['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                        <?php if (!empty($faq['keywords'])): ?>
                                                            <span class="badge bg-light text-dark">
                                                                <i class="fas fa-tags"></i> <?php echo htmlspecialchars($faq['keywords']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($faq['category_id']): ?>
                                                            <span class="badge bg-primary">
                                                                <i class="fas fa-folder"></i> 
                                                                <?php 
                                                                $category_name = 'Uncategorized';
                                                                foreach ($categories as $cat) {
                                                                    if ($cat['id'] == $faq['category_id']) {
                                                                        $category_name = $cat['name'];
                                                                        break;
                                                                    }
                                                                }
                                                                echo htmlspecialchars($category_name);
                                                                ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-5 action-buttons">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="me-3">
                                                            <div class="text-muted small">
                                                                <i class="fas fa-eye me-1"></i><?php echo $faq['view_count']; ?> |
                                                                <i class="fas fa-thumbs-up me-1"></i><?php echo $faq['helpful_count']; ?> |
                                                                <i class="fas fa-thumbs-down me-1"></i><?php echo $faq['not_helpful_count']; ?>
                                                            </div>
                                                            <div class="mt-1 small text-muted">
                                                                Updated: <?php echo date('M j, Y g:i A', strtotime($faq['updated_at'])); ?>
                                                            </div>
                                                        </div>
                                                        <div class="btn-group" role="group">
                                                            <a href="faq-view.php?id=<?php echo $faq['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                    onclick="confirmDelete(<?php echo $faq['id']; ?>, '<?php echo addslashes($faq['question']); ?>')">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                    <h5>No FAQs Found</h5>
                                    <p class="text-muted">Get started by creating your first FAQ.</p>
                                    <button type="button" class="btn btn-primary" onclick="showAddForm()">
                                        <i class="fas fa-plus me-1"></i>
                                        Create Your First FAQ
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Add FAQ Form (Initially Hidden) -->
                    <div class="card mt-4" id="addForm" style="display: none;">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Add New FAQ</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="createFaqForm">
                                <input type="hidden" name="action" value="create">
                                <input type="hidden" name="created_by" value="<?php echo $current_user_id; ?>">
                                <input type="hidden" name="updated_by" value="<?php echo $current_user_id; ?>">
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="create_question" class="form-label form-required">Question</label>
                                            <textarea class="form-control" id="create_question" name="question" 
                                                      rows="3" required placeholder="Enter the frequently asked question"></textarea>
                                            <div class="character-counter" id="create_question_counter">0/500 characters</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="create_category_id" class="form-label">Category</label>
                                            <select class="form-select" id="create_category_id" name="category_id">
                                                <option value="">-- No Category --</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="create_answer" class="form-label form-required">Answer</label>
                                    <textarea class="form-control" id="create_answer" name="answer" 
                                              rows="6" required placeholder="Enter the detailed answer"></textarea>
                                    <div class="character-counter" id="create_answer_counter">0/2000 characters</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="create_keywords" class="form-label">Keywords</label>
                                            <input type="text" class="form-control" id="create_keywords" 
                                                   name="keywords" placeholder="Comma-separated keywords for search">
                                            <div class="form-text">e.g., pricing, features, support</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="create_display_order" class="form-label">Display Order</label>
                                            <input type="number" class="form-control" id="create_display_order" 
                                                   name="display_order" value="<?php echo count($faqs) + 1; ?>" min="0">
                                            <div class="form-text">Lower numbers display first</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="create_view_count" class="form-label">Initial Views</label>
                                            <input type="number" class="form-control" id="create_view_count" 
                                                   name="view_count" value="0" min="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="create_helpful_count" class="form-label">Helpful Votes</label>
                                            <input type="number" class="form-control" id="create_helpful_count" 
                                                   name="helpful_count" value="0" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="create_not_helpful_count" class="form-label">Not Helpful Votes</label>
                                            <input type="number" class="form-control" id="create_not_helpful_count" 
                                                   name="not_helpful_count" value="0" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-check form-switch mt-4 pt-2">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="create_is_active" name="is_active" value="1" checked>
                                                <label class="form-check-label" for="create_is_active">
                                                    <strong>Active FAQ</strong>
                                                </label>
                                            </div>
                                            <div class="form-text">Inactive FAQs won't be shown to users</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Metadata -->
                                <div class="card metadata-card mt-3">
                                    <div class="card-body py-2">
                                        <div class="row text-center">
                                            <div class="col-md-4">
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i>
                                                    Created By: User <?php echo $current_user_id; ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Created: <?php echo date('M j, Y g:i A'); ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted">
                                                    <i class="fas fa-sync me-1"></i>
                                                    Updated: <?php echo date('M j, Y g:i A'); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        Create FAQ
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="hideAddForm()">
                                        <i class="fas fa-times me-1"></i>
                                        Cancel
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo me-1"></i>
                                        Reset Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- Edit FAQ Form -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title"><?php echo $form_title; ?></h5>
                                    <h6 class="card-subtitle text-muted">Update FAQ information and content.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="editFaqForm">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="updated_by" value="<?php echo $current_user_id; ?>">
                                        
                                        <div class="current-faq">
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

                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="mb-3">
                                                    <label for="edit_question" class="form-label form-required">Question</label>
                                                    <textarea class="form-control" id="edit_question" name="question" 
                                                              rows="3" required placeholder="Enter the question"><?php echo htmlspecialchars($current_faq['question']); ?></textarea>
                                                    <div class="character-counter" id="edit_question_counter">0/500 characters</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="edit_category_id" class="form-label">Category</label>
                                                    <select class="form-select" id="edit_category_id" name="category_id">
                                                        <option value="">-- No Category --</option>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?php echo $category['id']; ?>" 
                                                                <?php echo ($current_faq['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($category['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="edit_answer" class="form-label form-required">Answer</label>
                                            <textarea class="form-control" id="edit_answer" name="answer" 
                                                      rows="6" required placeholder="Enter the detailed answer"><?php echo htmlspecialchars($current_faq['answer']); ?></textarea>
                                            <div class="character-counter" id="edit_answer_counter">0/2000 characters</div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="edit_keywords" class="form-label">Keywords</label>
                                                    <input type="text" class="form-control" id="edit_keywords" 
                                                           name="keywords" value="<?php echo htmlspecialchars($current_faq['keywords'] ?? ''); ?>"
                                                           placeholder="Comma-separated keywords">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label for="edit_display_order" class="form-label">Display Order</label>
                                                    <input type="number" class="form-control" id="edit_display_order" 
                                                           name="display_order" value="<?php echo $current_faq['display_order']; ?>"
                                                           min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label for="edit_view_count" class="form-label">Views</label>
                                                    <input type="number" class="form-control" id="edit_view_count" 
                                                           name="view_count" value="<?php echo $current_faq['view_count']; ?>"
                                                           min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label for="edit_helpful_count" class="form-label">Helpful</label>
                                                    <input type="number" class="form-control" id="edit_helpful_count" 
                                                           name="helpful_count" value="<?php echo $current_faq['helpful_count']; ?>"
                                                           min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label for="edit_not_helpful_count" class="form-label">Not Helpful</label>
                                                    <input type="number" class="form-control" id="edit_not_helpful_count" 
                                                           name="not_helpful_count" value="<?php echo $current_faq['not_helpful_count']; ?>"
                                                           min="0">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="edit_is_active" name="is_active" value="1"
                                                       <?php echo $current_faq['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="edit_is_active">
                                                    Active FAQ
                                                </label>
                                            </div>
                                            <div class="form-text">Inactive FAQs won't be shown to users</div>
                                        </div>
                                        
                                        <div class="d-flex gap-2 mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Update FAQ
                                            </button>
                                            <a href="faq-view.php" class="btn btn-secondary">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </a>
                                            <button type="button" class="btn btn-danger ms-auto" 
                                                    onclick="confirmDelete(<?php echo $current_faq['id']; ?>, '<?php echo addslashes($current_faq['question']); ?>')">
                                                <i class="fas fa-trash me-1"></i> Delete FAQ
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- FAQ Statistics -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">FAQ Statistics</h5>
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
                                            <td><strong>Views:</strong></td>
                                            <td><?php echo $current_faq['view_count']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Helpful Votes:</strong></td>
                                            <td><?php echo $current_faq['helpful_count']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Not Helpful Votes:</strong></td>
                                            <td><?php echo $current_faq['not_helpful_count']; ?></td>
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
                                        <button type="button" class="btn btn-outline-info" onclick="resetEditForm()">
                                            <i class="fas fa-undo me-1"></i> Reset Changes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

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
                        This FAQ has been viewed <?php echo isset($current_faq['view_count']) ? $current_faq['view_count'] : 0; ?> times.
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
        // Show/hide add form
        function showAddForm() {
            document.getElementById('addForm').style.display = 'block';
            document.getElementById('create_question').focus();
            // Auto-set display order
            document.getElementById('create_display_order').value = <?php echo count($faqs) + 1; ?>;
        }

        function hideAddForm() {
            document.getElementById('addForm').style.display = 'none';
            document.getElementById('createFaqForm').reset();
            // Reset display order
            document.getElementById('create_display_order').value = <?php echo count($faqs) + 1; ?>;
        }

        // Delete confirmation
        function confirmDelete(id, question) {
            document.getElementById('deleteQuestion').textContent = question;
            document.getElementById('deleteId').value = id;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

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

        // Form validation
        function validateForm(formId, questionId, answerId) {
            const form = document.getElementById(formId);
            const question = document.getElementById(questionId);
            const answer = document.getElementById(answerId);
            
            if (form && question && answer) {
                form.addEventListener('submit', function(e) {
                    const questionVal = question.value.trim();
                    const answerVal = answer.value.trim();
                    
                    if (!questionVal || !answerVal) {
                        e.preventDefault();
                        alert('Please fill in all required fields (question and answer).');
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
        }

        // Preview FAQ
        function previewFAQ() {
            const question = document.getElementById('edit_question').value;
            const answer = document.getElementById('edit_answer').value;
            
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

        // Reset edit form
        function resetEditForm() {
            if (confirm('Are you sure you want to reset all changes? This cannot be undone.')) {
                document.getElementById('editFaqForm').reset();
                // Re-initialize counters
                setupCounter('edit_question', 'edit_question_counter', 500);
                setupCounter('edit_answer', 'edit_answer_counter', 2000);
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize counters
            setupCounter('create_question', 'create_question_counter', 500);
            setupCounter('create_answer', 'create_answer_counter', 2000);
            setupCounter('edit_question', 'edit_question_counter', 500);
            setupCounter('edit_answer', 'edit_answer_counter', 2000);
            
            // Initialize form validation
            validateForm('createFaqForm', 'create_question', 'create_answer');
            validateForm('editFaqForm', 'edit_question', 'edit_answer');
            
            // Auto-save reminder for edit form
            <?php if ($faq_id): ?>
            let formChanged = false;
            const editForm = document.getElementById('editFaqForm');
            const inputs = editForm.querySelectorAll('input, textarea, select');
            
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
            
            editForm.addEventListener('submit', () => {
                formChanged = false;
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>