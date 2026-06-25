<?php
require_once(__DIR__ . '/../models/BlogPostModel.php');
require_once(__DIR__ . '/../controllers/BlogCategoryController.php');

class BlogPostController {
    private $model;
    private $categoryController;

    public function __construct() {
        $this->model = new BlogPostModel();
        $this->categoryController = new BlogCategoryController();
    }

    public function getAllPosts($status = null, $category_id = null) {
        return $this->model->getAllPosts($status, $category_id);
    }

    public function getPostById($id) {
        return $this->model->getPostById($id);
    }

    public function createPost($data) {
        return $this->model->createPost($data);
    }

    public function updatePost($id, $data) {
        return $this->model->updatePost($id, $data);
    }

    public function deletePost($id) {
        return $this->model->deletePost($id);
    }

    public function updateStatus($id, $status) {
        return $this->model->updateStatus($id, $status);
    }

    public function searchPosts($keyword) {
        return $this->model->searchPosts($keyword);
    }

    public function getActiveCategories() {
        return $this->categoryController->getActiveCategories();
    }

    // File upload method
    public function uploadImage($file) {
        $targetDir = "uploads/blog/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', basename($file["name"]));
        $targetFilePath = $targetDir . $fileName;

        // Check if file is an actual image
        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            throw new Exception("File is not an image.");
        }

        // Check file size (5MB limit)
        if ($file["size"] > 5000000) {
            throw new Exception("Sorry, your file is too large.");
        }

        // Allow certain file formats
        $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            throw new Exception("Sorry, only JPG, JPEG, PNG, GIF & WEBP files are allowed.");
        }

        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
            return $targetFilePath;
        }
        
        throw new Exception("Sorry, there was an error uploading your file.");
    }

    // Generate meta description from content
    public function generateMetaDescription($content, $length = 160) {
        $content = strip_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        if (strlen($content) > $length) {
            $content = substr($content, 0, $length);
            $content = substr($content, 0, strrpos($content, ' ')) . '...';
        }
        return $content;
    }

    // Get post by slug
public function getPostBySlug($slug) {
    return $this->model->getPostBySlug($slug);
}

// Get recent posts
public function getRecentPosts($limit = 5) {
    return $this->model->getRecentPosts($limit);
}

// Get popular posts
public function getPopularPosts($limit = 5) {
    return $this->model->getPopularPosts($limit);
}

// Increment views
public function incrementViews($id) {
    return $this->model->incrementViews($id);
}

// Get posts by category
public function getPostsByCategory($category_id) {
    return $this->model->getPostsByCategory($category_id);
}
}
?>