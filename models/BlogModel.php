<?php
require_once(__DIR__ . '/../config/database.php');

class BlogModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /* ================================
       Get All Blogs
       ================================ */
    public function getAllBlogs() {
        $query = "SELECT * FROM blogs ORDER BY date DESC";
        $blogs = $this->db->fetchAll($query);

        return $blogs;
    }

    /* ================================
       Get Blog By ID
       ================================ */
    public function getBlogById($id) {
        $query = "SELECT * FROM blogs WHERE id = ?";
        return $this->db->fetch($query, [$id]);
    }

    /* ================================
       Add Blog
       ================================ */
    public function addBlog($data) {
        $query = "INSERT INTO blogs
                  (title, slug, category, author, main_image, second_image, short_description, content, publish_status, status, date)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['title'],
            $data['slug'],
            $data['category'],
            $data['author'],
            $data['main_image'],
            $data['second_image'],
            $data['short_description'],
            $data['content'],
            $data['publish_status'] ?? 'Unpublished',
            $data['status'] ?? 'Inactive'
        ];

        return $this->db->insert($query, $params);
    }

    /* ================================
       Update Blog
       ================================ */
    public function updateBlog($id, $data) {
        $query = "UPDATE blogs SET
                  title = ?,
                  slug = ?,
                  category = ?,
                  author = ?,
                  main_image = ?,
                  second_image = ?,
                  short_description = ?,
                  content = ?,
                  publish_status = ?,
                  status = ?,
                  date = NOW()
                  WHERE id = ?";

        $params = [
            $data['title'],
            $data['slug'],
            $data['category'],
            $data['author'],
            $data['main_image'],
            $data['second_image'],
            $data['short_description'],
            $data['content'],
            $data['publish_status'] ?? 'Unpublished',
            $data['status'] ?? 'Inactive',
            $id
        ];

        return $this->db->execute($query, $params);
    }

    /* ================================
       Delete Blog
       ================================ */
    public function deleteBlog($id) {
        $query = "DELETE FROM blogs WHERE id = ?";
        return $this->db->execute($query, [$id]);
    }

   
}
?>
