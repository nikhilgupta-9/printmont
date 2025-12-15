<?php
require_once(__DIR__ . '/../models/BlogModel.php');

class BlogController
{
    private $blogModel;

    public function __construct()
    {
        $this->blogModel = new BlogModel();
    }

    /* ================================
       Get All Blogs
       ================================ */
    public function getAllBlogs()
    {
        return $this->blogModel->getAllBlogs();
    }

    /* ================================
       Get Single Blog By ID
       ================================ */
    public function getBlogById($id)
    {
        return $this->blogModel->getBlogById($id);
    }

    /* ================================
       Add New Blog
       ================================ */
    public function addBlog($data)
    {
        try {
            // Required fields
            $required = ['title', 'slug', 'category', 'author', 'content', 'publish_status', 'status'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }

            // Prepare data
            $blogData = [
                'title' => trim($data['title']),
                'slug' => trim($data['slug']),
                'category' => trim($data['category']),
                'author' => trim($data['author']),
                'short_description' => trim($data['short_description'] ?? ''),
                'content' => trim($data['content']),
                'publish_status' => $data['publish_status'],
                'status' => $data['status'],
                'main_image' => $data['main_image'] ?? null,
                'second_image' => $data['second_image'] ?? null,
                'date' => $data['date'] ?? date('Y-m-d')
            ];

            $blogId = $this->blogModel->addBlog($blogData);

            return ['success' => true, 'blog_id' => $blogId];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /* ================================
       Update Blog
       ================================ */
    public function updateBlog($id, $data)
    {
        try {
            if (empty($data['title']) || empty($data['content'])) {
                throw new Exception("Title and Content are required");
            }

            $blogData = [
                'title' => trim($data['title']),
                'slug' => trim($data['slug']),
                'category' => trim($data['category']),
                'author' => trim($data['author']),
                'short_description' => trim($data['short_description'] ?? ''),
                'content' => trim($data['content']),
                'publish_status' => $data['publish_status'],
                'status' => $data['status'],
                'main_image' => $data['main_image'] ?? null,
                'second_image' => $data['second_image'] ?? null,
                'date' => $data['date'] ?? date('Y-m-d')
            ];

            $res = $this->blogModel->updateBlog($id, $blogData);

            return ['success' => $res];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /* ================================
       Delete Blog
       ================================ */
    public function deleteBlog($id)
    {
        return $this->blogModel->deleteBlog($id);
    }
}
?>
