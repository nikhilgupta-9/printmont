<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/BlogPostController.php';

$postController = new BlogPostController();
$status = $_GET['status'] ?? null;
$search = $_GET['search'] ?? '';

// Handle actions
if (isset($_GET['delete_id'])) {
    $result = $postController->deletePost($_GET['delete_id']);
    if ($result) {
        $_SESSION['message'] = 'Post deleted successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error deleting post!';
        $_SESSION['message_type'] = 'error';
    }
    header('Location: blog-posts.php');
    exit();
}

if (isset($_GET['status_id']) && isset($_GET['status'])) {
    $result = $postController->updateStatus($_GET['status_id'], $_GET['status']);
    if ($result) {
        $_SESSION['message'] = 'Post status updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error updating post status!';
        $_SESSION['message_type'] = 'error';
    }
    header('Location: blog-posts.php');
    exit();
}

// Get posts
if (!empty($search)) {
    $posts = $postController->searchPosts($search);
} else {
    $posts = $postController->getAllPosts($status);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Blog Posts | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
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
                            <h3><strong>Blog</strong> Posts</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="add-blog-post.php" class="btn btn-primary">
                                <i class="align-middle me-1" data-feather="plus"></i>
                                Add New Post
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <?php
                                        if ($status == 'draft') echo 'Draft Posts';
                                        elseif ($status == 'published') echo 'Published Posts';
                                        else echo 'All Posts';
                                        ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Messages -->
                                    <?php if (isset($_SESSION['message'])): ?>
                                        <div class="alert alert-<?php echo $_SESSION['message_type'] == 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                                            <?php echo $_SESSION['message']; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                                    <?php endif; ?>

                                    <!-- Filters -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="btn-group">
                                                <a href="blog-posts.php" class="btn btn-outline-secondary <?php echo !$status ? 'active' : ''; ?>">All</a>
                                                <a href="blog-posts.php?status=published" class="btn btn-outline-secondary <?php echo $status == 'published' ? 'active' : ''; ?>">Published</a>
                                                <a href="blog-posts.php?status=draft" class="btn btn-outline-secondary <?php echo $status == 'draft' ? 'active' : ''; ?>">Drafts</a>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <form method="GET" class="d-flex">
                                                <input type="text" name="search" class="form-control me-2" placeholder="Search posts..." value="<?php echo htmlspecialchars($search); ?>">
                                                <button type="submit" class="btn btn-outline-primary">Search</button>
                                                <?php if (!empty($search)): ?>
                                                    <a href="blog-posts.php" class="btn btn-outline-secondary ms-2">Clear</a>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Posts Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Category</th>
                                                    <th>Status</th>
                                                    <th>Views</th>
                                                    <th>Published</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($posts)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-4">
                                                            No posts found.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($posts as $post): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($post['featured_image'])): ?>
                                                                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" class="rounded me-3" style="width: 60px; height: 40px; object-fit: cover;">
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                                                        <?php if ($post['excerpt']): ?>
                                                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 100)); ?>...</small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <?php if ($post['category_name']): ?>
                                                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Uncategorized</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $post['status'] == 'published' ? 'success' : 'warning'; ?>">
                                                                    <?php echo ucfirst($post['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="text-muted"><?php echo $post['views']; ?> views</span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo $post['published_at'] ? date('M j, Y', strtotime($post['published_at'])) : 'Not published'; ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <a href="edit-blog-post.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary">
                                                                        <i class="align-middle" data-feather="edit-2"></i>
                                                                    </a>
                                                                    <?php if ($post['status'] == 'published'): ?>
                                                                        <a href="blog-posts.php?status_id=<?php echo $post['id']; ?>&status=draft" class="btn btn-outline-warning" title="Unpublish">
                                                                            <i class="align-middle" data-feather="eye-off"></i>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <a href="blog-posts.php?status_id=<?php echo $post['id']; ?>&status=published" class="btn btn-outline-success" title="Publish">
                                                                            <i class="align-middle" data-feather="eye"></i>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <a href="blog-posts.php?delete_id=<?php echo $post['id']; ?>" 
                                                                       class="btn btn-outline-danger" 
                                                                       onclick="return confirm('Are you sure you want to delete this post?')">
                                                                        <i class="align-middle" data-feather="trash-2"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        feather.replace();
    </script>
</body>
</html>