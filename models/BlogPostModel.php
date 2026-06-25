<?php
require_once(__DIR__ . '/../config/database.php');

class BlogPostModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection(); // mysqli connection
    }

    // Generate slug
    public function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists($slug, $excludeId = null) {
        $slug = $this->conn->real_escape_string($slug);

        $query = "SELECT id FROM blog_posts WHERE slug = '$slug'";
        if ($excludeId) {
            $excludeId = intval($excludeId);
            $query .= " AND id != $excludeId";
        }

        $result = $this->conn->query($query);
        return ($result->num_rows > 0);
    }

    // Get all posts
    public function getAllPosts($status = null, $category_id = null) {
        $query = "SELECT p.*, c.name AS category_name 
                  FROM blog_posts p
                  LEFT JOIN blog_categories c ON p.category_id = c.id 
                  WHERE p.is_active = 1";

        if ($status) {
            $status = $this->conn->real_escape_string($status);
            $query .= " AND p.status = '$status'";
        }

        if ($category_id) {
            $category_id = intval($category_id);
            $query .= " AND p.category_id = $category_id";
        }

        $query .= " ORDER BY p.created_at DESC";

        $result = $this->conn->query($query);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    // Get post by ID
    public function getPostById($id) {
        $id = intval($id);

        $query = "SELECT p.*, c.name AS category_name
                  FROM blog_posts p
                  LEFT JOIN blog_categories c ON p.category_id = c.id
                  WHERE p.id = $id AND p.is_active = 1";

        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    // Create post
    public function createPost($data) {
        $title = $this->conn->real_escape_string($data['title']);
        $content = $this->conn->real_escape_string($data['content']);
        $excerpt = $this->conn->real_escape_string($data['excerpt']);
        $featured_image = $this->conn->real_escape_string($data['featured_image']);
        $category_id = intval($data['category_id']);
        $author_id = intval($data['author_id']);
        $status = $this->conn->real_escape_string($data['status']);
        $meta_title = $this->conn->real_escape_string($data['meta_title']);
        $meta_description = $this->conn->real_escape_string($data['meta_description']);
        $meta_keywords = $this->conn->real_escape_string($data['meta_keywords']);

        if (empty($data['slug'])) {
            $slug = $this->generateSlug($data['title']);
        } else {
            $slug = $this->conn->real_escape_string($data['slug']);
        }

        // Published date
        if ($status == 'published' && empty($data['published_at'])) {
            $published_at = date('Y-m-d H:i:s');
        } else {
            $published_at = $this->conn->real_escape_string($data['published_at']);
        }

        $query = "
            INSERT INTO blog_posts 
            (title, slug, content, excerpt, featured_image, category_id, author_id, status, 
             meta_title, meta_description, meta_keywords, published_at)
            VALUES 
            ('$title', '$slug', '$content', '$excerpt', '$featured_image', $category_id, $author_id, 
             '$status', '$meta_title', '$meta_description', '$meta_keywords', '$published_at')
        ";

        return $this->conn->query($query);
    }

    // Update post
    public function updatePost($id, $data) {
        $id = intval($id);

        $title = $this->conn->real_escape_string($data['title']);
        $content = $this->conn->real_escape_string($data['content']);
        $excerpt = $this->conn->real_escape_string($data['excerpt']);
        $featured_image = $this->conn->real_escape_string($data['featured_image']);
        $category_id = intval($data['category_id']);
        $status = $this->conn->real_escape_string($data['status']);
        $meta_title = $this->conn->real_escape_string($data['meta_title']);
        $meta_description = $this->conn->real_escape_string($data['meta_description']);
        $meta_keywords = $this->conn->real_escape_string($data['meta_keywords']);

        if (empty($data['slug'])) {
            $slug = $this->generateSlug($data['title']);
        } else {
            $slug = $this->conn->real_escape_string($data['slug']);
        }

        if ($status == 'published' && empty($data['published_at'])) {
            $published_at = date('Y-m-d H:i:s');
        } else {
            $published_at = $this->conn->real_escape_string($data['published_at']);
        }

        $query = "
            UPDATE blog_posts SET
                title = '$title',
                slug = '$slug',
                content = '$content',
                excerpt = '$excerpt',
                featured_image = '$featured_image',
                category_id = $category_id,
                status = '$status',
                meta_title = '$meta_title',
                meta_description = '$meta_description',
                meta_keywords = '$meta_keywords',
                published_at = '$published_at',
                updated_at = NOW()
            WHERE id = $id
        ";

        return $this->conn->query($query);
    }

    // Soft delete
    public function deletePost($id) {
        $id = intval($id);
        $query = "UPDATE blog_posts SET is_active = 0 WHERE id = $id";
        return $this->conn->query($query);
    }

    // Update status
    public function updateStatus($id, $status) {
        $id = intval($id);
        $status = $this->conn->real_escape_string($status);

        $query = "UPDATE blog_posts SET status = '$status', updated_at = NOW()";

        if ($status == 'published') {
            $query .= ", published_at = NOW()";
        }

        $query .= " WHERE id = $id";

        return $this->conn->query($query);
    }

    // Increment views
    public function incrementViews($id) {
        $id = intval($id);
        $query = "UPDATE blog_posts SET views = views + 1 WHERE id = $id";
        return $this->conn->query($query);
    }

    // Search posts
    public function searchPosts($keyword) {
        $keyword = $this->conn->real_escape_string($keyword);

        $query = "SELECT p.*, c.name AS category_name 
                  FROM blog_posts p
                  LEFT JOIN blog_categories c ON p.category_id = c.id
                  WHERE 
                      (p.title LIKE '%$keyword%' 
                      OR p.content LIKE '%$keyword%' 
                      OR p.excerpt LIKE '%$keyword%')
                  AND p.is_active = 1
                  ORDER BY p.created_at DESC";

        $result = $this->conn->query($query);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    // Get posts by category
    public function getPostsByCategory($category_id) {
        $category_id = intval($category_id);

        $query = "SELECT p.*, c.name AS category_name
                  FROM blog_posts p
                  LEFT JOIN blog_categories c ON p.category_id = c.id
                  WHERE p.category_id = $category_id 
                  AND p.is_active = 1 
                  AND p.status = 'published'
                  ORDER BY p.created_at DESC";

        $result = $this->conn->query($query);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    
}
?>
