<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/BlogPostController.php';

$postController = new BlogPostController();
$categories = $postController->getActiveCategories();

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
            'author_id' => $_SESSION['user_id'] // Assuming user is logged in
        ];

        // Handle featured image upload
        if (!empty($_FILES['featured_image']['name'])) {
            $data['featured_image'] = $postController->uploadImage($_FILES['featured_image']);
        }

        // Auto-generate meta description if empty
        if (empty($data['meta_description']) && !empty($data['content'])) {
            $data['meta_description'] = $postController->generateMetaDescription($data['content']);
        }

        // Auto-generate meta title if empty
        if (empty($data['meta_title'])) {
            $data['meta_title'] = $data['title'];
        }

        $result = $postController->createPost($data);
        
        if ($result) {
            $_SESSION['message'] = 'Blog post created successfully!';
            $_SESSION['message_type'] = 'success';
            header('Location: blog-posts.php');
            exit();
        } else {
            throw new Exception('Failed to create blog post.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Blog Post | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <!-- Include CKEditor or similar WYSIWYG editor -->
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
                            <h3><strong>Add</strong> Blog Post</h3>
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
                                    <h5 class="card-title mb-0">Create New Post</h5>
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
                                                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                                           placeholder="Enter post title">
                                                </div>

                                                <!-- Slug -->
                                                <div class="mb-3">
                                                    <label for="slug" class="form-label">Slug *</label>
                                                    <input type="text" class="form-control" id="slug" name="slug" required 
                                                           value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>"
                                                           placeholder="URL-friendly version">
                                                    <div class="form-text">SEO-friendly URL slug. Auto-generated from title if empty.</div>
                                                </div>

                                                <!-- Content -->
                                                <div class="mb-3">
                                                    <label for="content" class="form-label">Content *</label>
                                                    <textarea class="form-control" id="content" name="content" rows="15" required
                                                              placeholder="Write your post content here"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                                                </div>

                                                <!-- Excerpt -->
                                                <div class="mb-3">
                                                    <label for="excerpt" class="form-label">Excerpt</label>
                                                    <textarea class="form-control" id="excerpt" name="excerpt" rows="3"
                                                              placeholder="Brief description of your post"><?php echo isset($_POST['excerpt']) ? htmlspecialchars($_POST['excerpt']) : ''; ?></textarea>
                                                    <div class="form-text">Brief description of your post. If empty, it will be generated from content.</div>
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
                                                                <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                                                <option value="published" <?php echo (isset($_POST['status']) && $_POST['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                                                            </select>
                                                        </div>
                                                        <div class="d-grid gap-2">
                                                            <button type="submit" name="publish" class="btn btn-primary">Publish</button>
                                                            <button type="submit" name="save_draft" value="draft" class="btn btn-outline-secondary">Save Draft</button>
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
                                                                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
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
                                                        <div class="mb-3">
                                                            <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                                                            <div class="form-text">Recommended size: 1200x630px. Max 5MB.</div>
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
                                                                   value="<?php echo isset($_POST['meta_title']) ? htmlspecialchars($_POST['meta_title']) : ''; ?>"
                                                                   placeholder="Title for search engines">
                                                            <div class="form-text">Title for search engines. If empty, post title will be used.</div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="meta_description" class="form-label">Meta Description</label>
                                                            <textarea class="form-control" id="meta_description" name="meta_description" rows="3"
                                                                      placeholder="Description for search engines"><?php echo isset($_POST['meta_description']) ? htmlspecialchars($_POST['meta_description']) : ''; ?></textarea>
                                                            <div class="form-text">
                                                                <span id="meta_desc_count">0</span>/160 characters
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                                            <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                                                                   value="<?php echo isset($_POST['meta_keywords']) ? htmlspecialchars($_POST['meta_keywords']) : ''; ?>"
                                                                   placeholder="Comma-separated keywords">
                                                            <div class="form-text">Comma-separated keywords for SEO</div>
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

        // Auto-generate slug from title
        document.getElementById('title').addEventListener('input', function() {
            const slugField = document.getElementById('slug');
            if (!slugField.value) {
                const slug = this.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9 -]/g, '-')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                slugField.value = slug;
            }
        });

        // Auto-generate meta description from content
        document.getElementById('content').addEventListener('input', function() {
            const metaDescField = document.getElementById('meta_description');
            if (!metaDescField.value) {
                // For CKEditor, we need to get the data differently
                const content = CKEDITOR.instances.content.getData().replace(/<[^>]*>/g, '').substring(0, 160);
                metaDescField.value = content + (content.length >= 160 ? '...' : '');
                updateMetaDescCount();
            }
        });

        // Update meta description character count
        function updateMetaDescCount() {
            const metaDesc = document.getElementById('meta_description').value;
            document.getElementById('meta_desc_count').textContent = metaDesc.length;
        }

        document.getElementById('meta_description').addEventListener('input', updateMetaDescCount);

        // Image preview
        document.getElementById('featured_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <div class="border rounded p-2">
                            <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">
                            <div class="mt-2 small text-muted">Image Preview</div>
                        </div>
                    `;
                }
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });

        // Auto-fill meta title from post title
        document.getElementById('title').addEventListener('blur', function() {
            const metaTitleField = document.getElementById('meta_title');
            if (!metaTitleField.value) {
                metaTitleField.value = this.value;
            }
        });

        // Handle save draft button
        document.querySelector('button[name="save_draft"]').addEventListener('click', function(e) {
            document.getElementById('status').value = 'draft';
        });

        // Initialize character count
        updateMetaDescCount();

        // Initialize Feather icons
        feather.replace();
    </script>
</body>
</html>