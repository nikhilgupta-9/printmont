<?php
require_once 'config/database.php';

class BlogModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection(); // Mysqli Connection
    }

    // Sanitize values
    private function escape($value) {
        return $this->conn->real_escape_string($value);
    }

    // Generate SEO-friendly slug
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

    private function slugExists($slug) {
        $slug = $this->escape($slug);
        $query = "SELECT id FROM blog_posts WHERE slug='$slug' AND is_active='1'";
        $result = $this->conn->query($query);
        return $result->num_rows > 0;
    }

    // Get all posts
    public function getAllPosts($status = null) {
        $query = "SELECT * FROM blog_posts WHERE is_active='1'";

        if (!empty($status)) {
            $status = $this->escape($status);
            $query .= " AND status='$status'";
        }

        $query .= " ORDER BY created_at DESC";

        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get post by ID
    public function getPostById($id) {
        $id = (int)$id;
        $query = "SELECT * FROM blog_posts WHERE id='$id' AND is_active='1'";
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    // Get post by slug
    public function getPostBySlug($slug) {
        $slug = $this->escape($slug);
        $query = "SELECT * FROM blog_posts 
                  WHERE slug='$slug' AND is_active='1' AND status='published'";

        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    // Create post
    public function createPost($data) {

        $title = $this->escape($data['title']);
        $content = $this->escape($data['content']);
        $excerpt = $this->escape($data['excerpt']);
        $meta_title = $this->escape($data['meta_title']);
        $meta_description = $this->escape($data['meta_description']);
        $meta_keywords = $this->escape($data['meta_keywords']);
        $featured_image = $this->escape($data['featured_image']);
        $status = $this->escape($data['status']);
        $author_id = (int)$data['author_id'];

        // Generate slug
        $slug = empty($data['slug']) ? $this->generateSlug($title) : $this->escape($data['slug']);

        // Publishing date
        $published_at = !empty($data['published_at']) 
                        ? $this->escape($data['published_at']) 
                        : date('Y-m-d H:i:s');

        $query = "INSERT INTO blog_posts 
                (title, slug, content, excerpt, meta_title, meta_description, meta_keywords, 
                 featured_image, status, author_id, published_at)
                VALUES 
                ('$title', '$slug', '$content', '$excerpt', '$meta_title', '$meta_description', 
                '$meta_keywords', '$featured_image', '$status', '$author_id', '$published_at')";

        return $this->conn->query($query);
    }

    // Update post
    public function updatePost($id, $data) {
        $id = (int)$id;

        $title = $this->escape($data['title']);
        $content = $this->escape($data['content']);
        $excerpt = $this->escape($data['excerpt']);
        $meta_title = $this->escape($data['meta_title']);
        $meta_description = $this->escape($data['meta_description']);
        $meta_keywords = $this->escape($data['meta_keywords']);
        $featured_image = $this->escape($data['featured_image']);
        $status = $this->escape($data['status']);

        $slug = empty($data['slug']) ? $this->generateSlug($title) : $this->escape($data['slug']);

        // Publishing date
        $published_at = !empty($data['published_at']) ? $this->escape($data['published_at']) : null;

        $query = "UPDATE blog_posts SET 
                    title='$title',
                    slug='$slug',
                    content='$content',
                    excerpt='$excerpt',
                    meta_title='$meta_title',
                    meta_description='$meta_description',
                    meta_keywords='$meta_keywords',
                    featured_image='$featured_image',
                    status='$status',
                    published_at='$published_at',
                    updated_at=NOW()
                  WHERE id='$id'";

        return $this->conn->query($query);
    }

    // Soft delete
    public function deletePost($id) {
        $id = (int)$id;
        $query = "UPDATE blog_posts SET is_active='0' WHERE id='$id'";
        return $this->conn->query($query);
    }

    // Increment views
    public function incrementViews($id) {
        $id = (int)$id;
        $query = "UPDATE blog_posts SET views = views + 1 WHERE id='$id'";
        return $this->conn->query($query);
    }

    // Popular posts
    public function getPopularPosts($limit = 5) {
        $limit = (int)$limit;
        $query = "SELECT * FROM blog_posts 
                  WHERE status='published' AND is_active='1'
                  ORDER BY views DESC, created_at DESC
                  LIMIT $limit";

        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Search posts
    public function searchPosts($keyword) {
        $keyword = '%'.$this->escape($keyword).'%';

        $query = "SELECT * FROM blog_posts 
                  WHERE (title LIKE '$keyword' 
                     OR content LIKE '$keyword' 
                     OR excerpt LIKE '$keyword')
                  AND status='published' 
                  AND is_active='1'
                  ORDER BY created_at DESC";

        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
