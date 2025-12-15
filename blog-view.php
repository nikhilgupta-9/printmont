<?php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/BlogController.php';

$blogController = new BlogController();
$blogs = $blogController->getAllBlogs(); // Fetch all blogs from DB
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Blog Management - Admin Panel</title>
    <link rel="stylesheet" href="css/light.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .blog-img { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; }
        .status-badge { cursor:pointer; padding:6px 12px; border-radius:20px; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; }
        .dot { width:10px; height:10px; border-radius:50%; }
        .dot-active { background:#28a745; }
        .dot-inactive { background:#dc3545; }
    </style>
</head>

<body>
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>

        <main class="content">
            <div class="container-fluid p-0">
                <div class="d-flex justify-content-between mb-3">
                    <h3><strong>Blog</strong> Management</h3>
                    <button class="btn btn-primary" id="showFormBtn">+ Add New Blog</button>
                </div>

                <!-- BLOG LIST TABLE -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h5 class="mb-0">All Blogs</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>Author</th>
                                        <th>Publish</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($blogs as $blog): ?>
                                        <tr>
                                            <td><img src="<?= $blog['main_image'] ?>" class="blog-img" /></td>
                                            <td><?= htmlspecialchars($blog['title']) ?></td>
                                            <td><?= htmlspecialchars($blog['date']) ?></td>
                                            <td><?= htmlspecialchars($blog['author']) ?></td>
                                            
                                            <!-- Publish Status -->
                                            <td>
                                                <select class="form-select form-select-sm"
                                                        onchange="changePublishStatus(<?= $blog['id'] ?>, this.value)">
                                                    <option value="Published" <?= $blog['publish_status'] === 'Published' ? 'selected' : '' ?>>Published</option>
                                                    <option value="Unpublished" <?= $blog['publish_status'] === 'Unpublished' ? 'selected' : '' ?>>Unpublished</option>
                                                </select>
                                            </td>

                                            <!-- Status Badge -->
                                            <td id="statusCell<?= $blog['id'] ?>">
                                                <?php if ($blog['publish_status'] === 'Published'): ?>
                                                    <span class="status-badge text-success"><span class="dot dot-active"></span> Active</span>
                                                <?php else: ?>
                                                    <span class="status-badge text-danger"><span class="dot dot-inactive"></span> Inactive</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Actions -->
                                            <td>
                                                <button class="btn btn-sm btn-info"
                                                        onclick='editBlog(<?= json_encode($blog) ?>)'>Edit</button>
                                                <button class="btn btn-sm btn-danger"
                                                        onclick="deleteBlog(<?= $blog['id'] ?>)">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ADD / EDIT BLOG FORM -->
                <div class="card shadow-sm" id="blogFormCard" style="display:none;">
                    <div class="card-header">
                        <h5 id="formTitle" class="mb-0">Add New Blog</h5>
                        <small class="text-muted">Manage your blogs</small>
                    </div>
                    <div class="card-body">
                        <form id="blogForm" action="save-blog.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" id="blogId">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Blog Title</label>
                                <input type="text" name="title" id="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Slug</label>
                                <input type="text" name="slug" id="slug" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Category</label>
                                <input type="text" name="category" id="category" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Author</label>
                                <input type="text" name="author" id="author" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Main Blog Image</label>
                                <input type="file" name="main_image" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Second Image</label>
                                <input type="file" name="second_image" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Short Description</label>
                                <textarea name="short_description" id="short_description" rows="3" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Full Content</label>
                                <textarea name="content" id="content" rows="8" class="form-control"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Blog</button>
                        </form>
                    </div>
                </div>

            </div>
        </main>

        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<script src="js/app.js"></script>
<script>
    // Show Add Blog Form
    document.getElementById("showFormBtn").onclick = () => {
        document.getElementById("blogFormCard").style.display = "block";
        document.getElementById("formTitle").innerText = "Add New Blog";
        document.getElementById("blogForm").reset();
    };

    // Edit Blog
    function editBlog(blog) {
        document.getElementById("blogFormCard").style.display = "block";
        document.getElementById("formTitle").innerText = "Edit Blog";

        document.getElementById("blogId").value = blog.id;
        document.getElementById("title").value = blog.title;
        document.getElementById("slug").value = blog.slug;
        document.getElementById("category").value = blog.category;
        document.getElementById("author").value = blog.author;
        document.getElementById("short_description").value = blog.short_description;
        document.getElementById("content").value = blog.content;
    }

    // Delete Blog
    function deleteBlog(id) {
        if(confirm("Are you sure you want to delete this blog?")) {
            window.location.href = "delete-blog.php?id=" + id;
        }
    }

    // Change Publish Status
    function changePublishStatus(id, newStatus) {
        let statusCell = document.getElementById("statusCell" + id);
        if(newStatus === "Published") {
            statusCell.innerHTML = `<span class="status-badge text-success"><span class="dot dot-active"></span> Active</span>`;
        } else {
            statusCell.innerHTML = `<span class="status-badge text-danger"><span class="dot dot-inactive"></span> Inactive</span>`;
        }
        // Optional: Call API to update DB
        console.log("Update API → update-publish.php?id=" + id + "&status=" + newStatus);
    }
</script>
</body>
</html>
