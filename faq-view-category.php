<?php
require_once( __DIR__ . '/config/database.php');
require_once( __DIR__ . '/controllers/AuthController.php');
require_once( __DIR__ . '/controllers/FaqCategoryController.php');

// Initialize Category Controller
$database = new Database();
$db = $database->getConnection();
$categoryController = new CategoryController($db);

// Ensure table exists
$categoryController->ensureTableExists();

$message = '';
$message_type = 'success';
$current_category = null;

// Get Category ID from URL for edit mode
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get current user ID
$current_user_id = $_SESSION['user_id'] ?? 1;

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $_POST['created_by'] = $current_user_id;
                $_POST['updated_by'] = $current_user_id;
                $result = $categoryController->createCategory($_POST);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'update':
                if ($category_id) {
                    $_POST['updated_by'] = $current_user_id;
                    $result = $categoryController->updateCategory($category_id, $_POST);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'danger';
                    
                    // Refresh category data after update
                    if ($result['success']) {
                        $category_result = $categoryController->getCategoryById($category_id);
                        $current_category = $category_result->fetch_assoc();
                    }
                }
                break;
                
            case 'delete':
                if (isset($_POST['delete_id'])) {
                    $result = $categoryController->deleteCategory($_POST['delete_id']);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'danger';
                    $category_id = 0; // Reset to list view after delete
                }
                break;
        }
    }
}

// Get category data for editing
if ($category_id) {
    $category_result = $categoryController->getCategoryById($category_id);
    if ($category_result && $category_result->num_rows > 0) {
        $current_category = $category_result->fetch_assoc();
    } else {
        $message = 'Category not found!';
        $message_type = 'danger';
        $category_id = 0;
    }
}

// Get all categories for listing
$categories_result = $categoryController->getAllCategories();
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get category types
$category_types = $categoryController->getCategoryTypes();

// Get statistics
$stats = $categoryController->getCategoryStats();

// Update FAQ counts
$categoryController->updateFAQCounts();

$page_title = $category_id ? 'Edit Category' : 'Category Management';
$form_title = $category_id ? 'Edit Category' : 'Add New Category';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="FAQ Category Management">
    <meta name="author" content="AdminKit">
    <meta name="keywords" content="faq, category, management, admin">

    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />

    <title><?php echo $page_title; ?> - Admin Panel</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .category-item { transition: all 0.3s ease; }
        .category-item:hover { background-color: #f8f9fa; }
        .action-buttons .btn { margin-right: 5px; margin-bottom: 5px; }
        .stats-card { border-left: 4px solid #0d6efd; }
        .form-required:after { content: " *"; color: #dc3545; }
        .current-category { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .category-badge { 
            display: inline-block; 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 0.75rem; 
            font-weight: 600;
        }
        .color-preview { 
            width: 20px; 
            height: 20px; 
            border-radius: 50%; 
            display: inline-block; 
            margin-right: 8px;
            border: 2px solid #dee2e6;
        }
        .icon-preview { 
            width: 30px; 
            text-align: center; 
            margin-right: 8px;
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

                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3>
                                <strong><?php echo $page_title; ?></strong>
                                <?php if ($category_id): ?>
                                    <span class="badge bg-primary">Editing</span>
                                <?php endif; ?>
                            </h3>
                        </div>

                        <div class="col-auto ms-auto text-end mt-n1">
                            <?php if ($category_id): ?>
                                <a href="category-management.php" class="btn btn-light bg-white me-2">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary" onclick="showAddForm()">
                                    <i class="fas fa-plus"></i> Add New Category
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

                    <?php if (!$category_id): ?>
                    <!-- Statistics and List View -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $stats['total_categories']; ?></h5>
                                    <p class="card-text text-muted">Total Categories</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card" style="border-left-color: #198754;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $stats['active_categories']; ?></h5>
                                    <p class="card-text text-muted">Active Categories</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card" style="border-left-color: #ffc107;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $stats['total_faqs_in_categories']; ?></h5>
                                    <p class="card-text text-muted">Total FAQs</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card" style="border-left-color: #6f42c1;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo count($categories); ?></h5>
                                    <p class="card-text text-muted">Displayed</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">All Categories</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($categories) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($categories as $category): ?>
                                        <div class="list-group-item category-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-1 text-center">
                                                    <span class="badge bg-light text-dark"><?php echo $category['display_order']; ?></span>
                                                </div>
                                                <div class="col-md-7">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <?php if (!empty($category['icon'])): ?>
                                                            <div class="icon-preview">
                                                                <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($category['name']); ?></h6>
                                                        <?php if (!empty($category['color'])): ?>
                                                            <span class="color-preview ms-2" style="background-color: <?php echo $category['color']; ?>"></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($category['description'])): ?>
                                                        <p class="mb-1 text-muted small">
                                                            <?php echo htmlspecialchars($category['description']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <div class="mt-2">
                                                        <span class="badge <?php echo $category['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                        <span class="category-badge" style="background-color: <?php echo $category['color'] . '20'; ?>; color: <?php echo $category['color']; ?>;">
                                                            <?php echo ucfirst($category['type']); ?>
                                                        </span>
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-file-alt"></i> <?php echo $category['faq_count']; ?> FAQs
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 action-buttons">
                                                    <div class="btn-group" role="group">
                                                        <a href="category-management.php?id=<?php echo $category['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                    <div class="mt-2 small text-muted">
                                                        Updated: <?php echo date('M j, Y g:i A', strtotime($category['updated_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-folder fa-3x text-muted mb-3"></i>
                                    <h5>No Categories Found</h5>
                                    <p class="text-muted">Get started by creating your first category.</p>
                                    <button type="button" class="btn btn-primary" onclick="showAddForm()">
                                        <i class="fas fa-plus me-1"></i>
                                        Create Your First Category
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Add Category Form (Initially Hidden) -->
                    <div class="card mt-4" id="addForm" style="display: none;">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Add New Category</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="createCategoryForm">
                                <input type="hidden" name="action" value="create">
                                <input type="hidden" name="created_by" value="<?php echo $current_user_id; ?>">
                                <input type="hidden" name="updated_by" value="<?php echo $current_user_id; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="create_name" class="form-label form-required">Category Name</label>
                                            <input type="text" class="form-control" id="create_name" name="name" 
                                                   required placeholder="Enter category name">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="create_type" class="form-label">Type</label>
                                            <select class="form-select" id="create_type" name="type">
                                                <?php foreach ($category_types as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="create_display_order" class="form-label">Display Order</label>
                                            <input type="number" class="form-control" id="create_display_order" 
                                                   name="display_order" value="<?php echo count($categories) + 1; ?>" min="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="create_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="create_description" name="description" 
                                              rows="3" placeholder="Enter category description"></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="create_icon" class="form-label">Icon Class</label>
                                            <input type="text" class="form-control" id="create_icon" 
                                                   name="icon" placeholder="fas fa-folder">
                                            <div class="form-text">Font Awesome icon class</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="create_color" class="form-label">Color</label>
                                            <input type="color" class="form-control form-control-color" id="create_color" 
                                                   name="color" value="#6c757d" title="Choose category color">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-check form-switch mt-4 pt-2">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="create_is_active" name="is_active" value="1" checked>
                                                <label class="form-check-label" for="create_is_active">
                                                    <strong>Active Category</strong>
                                                </label>
                                            </div>
                                            <div class="form-text">Inactive categories won't be shown</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        Create Category
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
                    <!-- Edit Category Form -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title"><?php echo $form_title; ?></h5>
                                    <h6 class="card-subtitle text-muted">Update category information.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="editCategoryForm">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="updated_by" value="<?php echo $current_user_id; ?>">
                                        
                                        <div class="current-category">
                                            <h6>Current Category</h6>
                                            <div class="d-flex align-items-center mb-2">
                                                <?php if (!empty($current_category['icon'])): ?>
                                                    <div class="icon-preview">
                                                        <i class="<?php echo htmlspecialchars($current_category['icon']); ?>"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <strong><?php echo htmlspecialchars($current_category['name']); ?></strong>
                                                <?php if (!empty($current_category['color'])): ?>
                                                    <span class="color-preview ms-2" style="background-color: <?php echo $current_category['color']; ?>"></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Created: <?php echo date('M j, Y g:i A', strtotime($current_category['created_at'])); ?> | 
                                                    Updated: <?php echo date('M j, Y g:i A', strtotime($current_category['updated_at'])); ?> |
                                                    FAQs: <?php echo $current_category['faq_count']; ?>
                                                </small>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="edit_name" class="form-label form-required">Category Name</label>
                                                    <input type="text" class="form-control" id="edit_name" name="name" 
                                                           required value="<?php echo htmlspecialchars($current_category['name']); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="edit_type" class="form-label">Type</label>
                                                    <select class="form-select" id="edit_type" name="type">
                                                        <?php foreach ($category_types as $value => $label): ?>
                                                            <option value="<?php echo $value; ?>" 
                                                                <?php echo ($current_category['type'] == $value) ? 'selected' : ''; ?>>
                                                                <?php echo $label; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="edit_display_order" class="form-label">Display Order</label>
                                                    <input type="number" class="form-control" id="edit_display_order" 
                                                           name="display_order" value="<?php echo $current_category['display_order']; ?>" min="0">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="edit_description" class="form-label">Description</label>
                                            <textarea class="form-control" id="edit_description" name="description" 
                                                      rows="3" placeholder="Enter category description"><?php echo htmlspecialchars($current_category['description']); ?></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="edit_icon" class="form-label">Icon Class</label>
                                                    <input type="text" class="form-control" id="edit_icon" 
                                                           name="icon" value="<?php echo htmlspecialchars($current_category['icon']); ?>"
                                                           placeholder="fas fa-folder">
                                                    <div class="form-text">Font Awesome icon class</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="edit_color" class="form-label">Color</label>
                                                    <input type="color" class="form-control form-control-color" id="edit_color" 
                                                           name="color" value="<?php echo $current_category['color']; ?>" title="Choose category color">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-check form-switch mt-4 pt-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="edit_is_active" name="is_active" value="1"
                                                               <?php echo $current_category['is_active'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="edit_is_active">
                                                            <strong>Active Category</strong>
                                                        </label>
                                                    </div>
                                                    <div class="form-text">Inactive categories won't be shown</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex gap-2 mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Update Category
                                            </button>
                                            <a href="category-management.php" class="btn btn-secondary">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </a>
                                            <button type="button" class="btn btn-danger ms-auto" 
                                                    onclick="confirmDelete(<?php echo $current_category['id']; ?>, '<?php echo addslashes($current_category['name']); ?>')">
                                                <i class="fas fa-trash me-1"></i> Delete Category
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Category Information -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Category Information</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo $current_category['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $current_category['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Type:</strong></td>
                                            <td><?php echo ucfirst($current_category['type']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Display Order:</strong></td>
                                            <td><?php echo $current_category['display_order']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>FAQs Count:</strong></td>
                                            <td><?php echo $current_category['faq_count']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Color:</strong></td>
                                            <td>
                                                <span class="color-preview" style="background-color: <?php echo $current_category['color']; ?>"></span>
                                                <?php echo $current_category['color']; ?>
                                            </td>
                                        </tr>
                                        <?php if (!empty($current_category['icon'])): ?>
                                        <tr>
                                            <td><strong>Icon:</strong></td>
                                            <td>
                                                <i class="<?php echo htmlspecialchars($current_category['icon']); ?> me-2"></i>
                                                <?php echo htmlspecialchars($current_category['icon']); ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($current_category['created_at'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Updated:</strong></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($current_category['updated_at'])); ?></td>
                                        </tr>
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
                                        <a href="category-management.php" class="btn btn-outline-primary">
                                            <i class="fas fa-list me-1"></i> View All Categories
                                        </a>
                                        <a href="faq-management.php" class="btn btn-outline-success">
                                            <i class="fas fa-question-circle me-1"></i> Manage FAQs
                                        </a>
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
                    <p>Are you sure you want to delete this category? This action cannot be undone.</p>
                    <div class="alert alert-warning">
                        <strong>Category to delete:</strong><br>
                        <span id="deleteCategoryName"></span>
                    </div>
                    <p class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        This category has <?php echo isset($current_category['faq_count']) ? $current_category['faq_count'] : 0; ?> FAQs associated with it.
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
                            <i class="fas fa-trash me-1"></i> Delete Category
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
            document.getElementById('create_name').focus();
            // Auto-set display order
            document.getElementById('create_display_order').value = <?php echo count($categories) + 1; ?>;
        }

        function hideAddForm() {
            document.getElementById('addForm').style.display = 'none';
            document.getElementById('createCategoryForm').reset();
            // Reset display order
            document.getElementById('create_display_order').value = <?php echo count($categories) + 1; ?>;
        }

        // Delete confirmation
        function confirmDelete(id, name) {
            document.getElementById('deleteCategoryName').textContent = name;
            document.getElementById('deleteId').value = id;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Form validation
        function validateForm(formId, nameId) {
            const form = document.getElementById(formId);
            const name = document.getElementById(nameId);
            
            if (form && name) {
                form.addEventListener('submit', function(e) {
                    const nameVal = name.value.trim();
                    
                    if (!nameVal) {
                        e.preventDefault();
                        alert('Please enter a category name.');
                        name.focus();
                        return false;
                    }
                    
                    if (nameVal.length > 255) {
                        e.preventDefault();
                        alert('Category name is too long. Maximum 255 characters allowed.');
                        name.focus();
                        return false;
                    }
                });
            }
        }

        // Reset edit form
        function resetEditForm() {
            if (confirm('Are you sure you want to reset all changes? This cannot be undone.')) {
                document.getElementById('editCategoryForm').reset();
            }
        }

        // Icon preview
        function setupIconPreview(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            
            if (input && preview) {
                input.addEventListener('input', function() {
                    preview.className = this.value + ' fa-fw';
                });
            }
        }

        // Color preview
        function setupColorPreview(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            
            if (input && preview) {
                input.addEventListener('input', function() {
                    preview.style.backgroundColor = this.value;
                });
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize form validation
            validateForm('createCategoryForm', 'create_name');
            validateForm('editCategoryForm', 'edit_name');
            
            // Auto-save reminder for edit form
            <?php if ($category_id): ?>
            let formChanged = false;
            const editForm = document.getElementById('editCategoryForm');
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