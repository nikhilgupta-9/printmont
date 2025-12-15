<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/BlogPostController.php';

$postController = new BlogPostController();
$categories = $postController->getActiveCategories();

// Get post data
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: blog-posts.php');
    exit();
}

$post = $postController->getPostById($id);
if (!$post) {
    header('Location: blog-posts.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'title' => $_POST['title'],
            'slug' => $_POST['slug'],
            'content' => $_POST['content'],
            'excerpt' => $_POST['excerpt'],
            'category_id' => $_POST['category_id'] ?: null,
            'status' => $_POST['status'],
            'meta_title' => $_POST['meta_title'],
            'meta_description' => $_POST['meta_description'],
            'meta_keywords' => $_POST['meta_keywords'],
            'published_at' => $_POST['published_at']
        ];

        // Handle featured image upload
        if (!empty($_FILES['featured_image']['name'])) {
            $data['featured_image'] = $postController->uploadImage($_FILES['featured_image']);
        } elseif (isset($_POST['remove_image'])) {
            $data['featured_image'] = null;
        }

        $result = $postController->updatePost($id, $data);
        
        if ($result) {
            $_SESSION['message'] = 'Blog post updated successfully!';
            $_SESSION['message_type'] = 'success';
            header('Location: blog-posts.php');
            exit();
        } else {
            throw new Exception('Failed to update blog post.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    
    // Refresh post data after update
    $post = $postController->getPostById($id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Blog Post | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
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
                            <h3><strong>Edit</strong> Blog Post</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="blog-posts.php" class="btn btn-outline-primary">
                                <i class="align-middle me-1" data-feather="arrow-left"></i>
                                Back to Posts
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Edit Post: <?php echo htmlspecialchars($post['title']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($error)): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?php echo $error; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" enctype="multipart/form-data" id="postForm">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <!-- Title -->
                                                <div class="mb-3">
                                                    <label for="title" class="form-label">Title *</label>
                                                    <input type="text" class="form-control" id="title" name="title" required 
                                                           value="<?php echo htmlspecialchars($post['title']); ?>">
                                                </div>

                                                <!-- Slug -->
                                                <div class="mb-3">
                                                    <label for="slug" class="form-label">Slug *</label>
                                                    <input type="text" class="form-control" id="slug" name="slug" required 
                                                           value="<?php echo htmlspecialchars($post['slug']); ?>">
                                                    <div class="form-text">SEO-friendly URL slug.</div>
                                                </div>

                                                <!-- Content -->
                                                <div class="mb-3">
                                                    <label for="content" class="form-label">Content *</label>
                                                    <textarea class="form-control" id="content" name="content" rows="15" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                                                </div>

                                                <!-- Excerpt -->
                                                <div class="mb-3">
                                                    <label for="excerpt" class="form-label">Excerpt</label>
                                                    <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                                                    <div class="form-text">Brief description of your post.</div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <!-- Publish Settings -->
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h6 class="card-title mb-0">Publish</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="mb-3">
                                                            <label for="status" class="form-label">Status</label>
                                                            <select class="form-select" id="status" name="status">
                                                                <option value="draft" <?php echo $post['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                                <option value="published" <?php echo $post['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                                            </select>
                                                        </div>
                                                        <?php if ($post['published_at']): ?>
                                                        <div class="mb-3">
                                                            <label for="published_at" class="form-label">Published On</label>
                                                            <input type="datetime-local" class="form-control" name="published_at" 
                                                                   value="<?php echo date('Y-m-d\TH:i', strtotime($post['published_at'])); ?>">
                                                        </div>
                                                        <?php endif; ?>
                                                        <div class="d-grid gap-2">
                                                            <button type="submit" class="btn btn-primary">Update Post</button>
                                                            <a href="blog-posts.php" class="btn btn-outline-secondary">Cancel</a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Categories -->
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h6 class="card-title mb-0">Categories</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="mb-3">
                                                            <label for="category_id" class="form-label">Category</label>
                                                            <select class="form-select" id="category_id" name="category_id">
                                                                <option value="">Uncategorized</option>
                                                                <?php foreach ($categories as $category): ?>
                                                                    <option value="<?php echo $category['id']; ?>" 
                                                                        <?php echo $post['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Featured Image -->
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h6 class="card-title mb-0">Featured Image</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php if (!empty($post['featured_image'])): ?>
                                                            <div class="mb-3 text-center">
                                                                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" class="img-fluid rounded mb-2" style="max-height: 200px;">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image">
                                                                    <label class="form-check-label" for="remove_image">
                                                                        Remove featured image
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="mb-3">
                                                            <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                                                            <div class="form-text">Upload new image to replace current one.</div>
                                                        </div>
                                                        <div id="imagePreview" class="mt-2 text-center"></div>
                                                    </div>
                                                </div>

                                                <!-- SEO Settings -->
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="card-title mb-0">SEO Settings</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="mb-3">
                                                            <label for="meta_title" class="form-label">Meta Title</label>
                                                            <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                                                   value="<?php echo htmlspecialchars($post['meta_title']); ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="meta_description" class="form-label">Meta Description</label>
                                                            <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($post['meta_description']); ?></textarea>
                                                            <div class="form-text">
                                                                <span id="meta_desc_count"><?php echo strlen($post['meta_description']); ?></span>/160 characters
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                                            <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                                                                   value="<?php echo htmlspecialchars($post['meta_keywords']); ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Post Stats -->
                                                <div class="card mt-4">
                                                    <div class="card-header">
                                                        <h6 class="card-title mb-0">Post Statistics</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row text-center">
                                                            <div class="col-6">
                                                                <div class="text-muted small">Views</div>
                                                                <div class="h5"><?php echo $post['views']; ?></div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted small">Created</div>
                                                                <div class="small"><?php echo date('M j, Y', strtotime($post['created_at'])); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="row text-center mt-2">
                                                            <div class="col-12">
                                                                <div class="text-muted small">Last Updated</div>
                                                                <div class="small"><?php echo date('M j, Y g:i A', strtotime($post['updated_at'])); ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
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
        // Initialize CKEditor
        CKEDITOR.replace('content', {
            toolbar: [
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'RemoveFormat'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', 'Blockquote'] },
                { name: 'links', items: ['Link', 'Unlink'] },
                { name: 'insert', items: ['Image', 'Table', 'HorizontalRule'] },
                { name: 'styles', items: ['Styles', 'Format'] },
                { name: 'tools', items: ['Maximize', 'Source'] }
            ],
            height: 400
        });

        // Character count for meta description
        document.getElementById('meta_description').addEventListener('input', function() {
            document.getElementById('meta_desc_count').textContent = this.value.length;
        });

        // Image preview for new uploads
        document.getElementById('featured_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <div class="border rounded p-2">
                            <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">
                            <div class="mt-2 small text-muted">New Image Preview</div>
                        </div>
                    `;
                }
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });

        // Initialize Feather icons
        feather.replace();
    </script>
</body>
</html>