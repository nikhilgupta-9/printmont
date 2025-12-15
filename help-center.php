<?php
// help-center.php
session_start();
require_once 'config/database.php';
require_once 'controllers/HelpCenterController.php';

$helpController = new HelpCenterController();
$categories = $helpController->getAllCategories();
$articles = $helpController->getAllArticles();
$faqs = $helpController->getAllFaqs();
$stats = $helpController->getHelpCenterStats();

// Handle deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete_category'])) {
            if ($helpController->deleteCategory($_POST['id'])) {
                $_SESSION['success_message'] = "Category deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete category!";
            }
        } elseif (isset($_POST['delete_article'])) {
            if ($helpController->deleteArticle($_POST['id'])) {
                $_SESSION['success_message'] = "Article deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete article!";
            }
        } elseif (isset($_POST['delete_faq'])) {
            if ($helpController->deleteFaq($_POST['id'])) {
                $_SESSION['success_message'] = "FAQ deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete FAQ!";
            }
        }
        
        header("Location: help-center.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: help-center.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Help Center Management | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .help-header { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; }
        .stats-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .category-card { border-left: 4px solid #007bff; }
        .article-card { border-left: 4px solid #28a745; }
        .faq-card { border-left: 4px solid #ffc107; }
        .icon-preview { font-size: 1.5em; color: #6c757d; }
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
                            <div class="card help-header">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h2 class="card-title text-white mb-1">Help Center Management</h2>
                                            <p class="card-text text-white-50 mb-0">Manage help categories, articles, and FAQs</p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <a href="add-category.php" class="btn btn-light btn-sm">
                                                <i class="fas fa-plus"></i> Add Category
                                            </a>
                                            <a href="add-article.php" class="btn btn-outline-light btn-sm">
                                                <i class="fas fa-file-alt"></i> Add Article
                                            </a>
                                            <a href="add-faq.php" class="btn btn-outline-light btn-sm">
                                                <i class="fas fa-question-circle"></i> Add FAQ
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="stats-card">
                                <div class="text-center">
                                    <div class="h3 text-primary"><?php echo $stats['total_categories']; ?></div>
                                    <div class="text-muted">Categories</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card">
                                <div class="text-center">
                                    <div class="h3 text-success"><?php echo $stats['total_articles']; ?></div>
                                    <div class="text-muted">Articles</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card">
                                <div class="text-center">
                                    <div class="h3 text-info"><?php echo $stats['featured_articles']; ?></div>
                                    <div class="text-muted">Featured</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card">
                                <div class="text-center">
                                    <div class="h3 text-warning"><?php echo $stats['total_faqs']; ?></div>
                                    <div class="text-muted">FAQs</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card">
                                <div class="text-center">
                                    <div class="h3 text-danger"><?php echo $stats['total_views']; ?></div>
                                    <div class="text-muted">Total Views</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card">
                                <div class="text-center">
                                    <div class="h3 text-dark"><?php echo $stats['active_categories']; ?></div>
                                    <div class="text-muted">Active Categories</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Categories -->
                        <div class="col-lg-4">
                            <div class="card category-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        Categories 
                                        <span class="badge bg-primary"><?php echo count($categories); ?></span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($categories)): ?>
                                        <p class="text-muted text-center">No categories found. <a href="add-category.php">Add your first category</a></p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Category</th>
                                                        <th>Order</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($categories as $category): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if ($category['icon']): ?>
                                                                <i class="<?php echo $category['icon']; ?> me-2 icon-preview"></i>
                                                            <?php endif; ?>
                                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                            <?php if ($category['description']): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo $category['display_order']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $category['is_active'] ? 'success' : 'secondary'; ?>">
                                                                <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="edit-category.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                                <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger">
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

                        <!-- Articles -->
                        <div class="col-lg-4">
                            <div class="card article-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        Articles 
                                        <span class="badge bg-success"><?php echo count($articles); ?></span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($articles)): ?>
                                        <p class="text-muted text-center">No articles found. <a href="add-article.php">Add your first article</a></p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Category</th>
                                                        <th>Views</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($articles as $article): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($article['title']); ?></strong>
                                                            <?php if ($article['is_featured']): ?>
                                                                <span class="badge bg-warning ms-1">Featured</span>
                                                            <?php endif; ?>
                                                            <br>
                                                            <small class="text-muted">/<?php echo $article['slug']; ?></small>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($article['category_name']); ?></td>
                                                        <td><?php echo $article['views_count']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $article['is_active'] ? 'success' : 'secondary'; ?>">
                                                                <?php echo $article['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="edit-article.php?id=<?php echo $article['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this article?')">
                                                                <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
                                                                <button type="submit" name="delete_article" class="btn btn-sm btn-outline-danger">
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

                        <!-- FAQs -->
                        <div class="col-lg-4">
                            <div class="card faq-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        FAQs 
                                        <span class="badge bg-warning"><?php echo count($faqs); ?></span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($faqs)): ?>
                                        <p class="text-muted text-center">No FAQs found. <a href="add-faq.php">Add your first FAQ</a></p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Question</th>
                                                        <th>Category</th>
                                                        <th>Order</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($faqs as $faq): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($faq['question']); ?></strong>
                                                            <?php if (strlen($faq['question']) > 50): ?>
                                                                <br><small class="text-muted"><?php echo substr(htmlspecialchars($faq['question']), 0, 50); ?>...</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($faq['category_name'] ?? 'General'); ?></td>
                                                        <td><?php echo $faq['display_order']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $faq['is_active'] ? 'success' : 'secondary'; ?>">
                                                                <?php echo $faq['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="edit-faq.php?id=<?php echo $faq['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this FAQ?')">
                                                                <input type="hidden" name="id" value="<?php echo $faq['id']; ?>">
                                                                <button type="submit" name="delete_faq" class="btn btn-sm btn-outline-danger">
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