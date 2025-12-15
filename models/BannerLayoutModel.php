<?php
require_once 'config/Database.php';

class BannerLayoutModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllLayouts() {
        $query = "SELECT * FROM banner_layouts ORDER BY page_name, display_order ASC";
        return $this->db->fetchAll($query);
    }

    public function getLayoutById($id) {
        $query = "SELECT * FROM banner_layouts WHERE id = ?";
        return $this->db->fetch($query, [$id]);
    }

    public function getLayoutsByPage($page_name) {
        $query = "SELECT * FROM banner_layouts WHERE page_name = ? AND status = 'active' ORDER BY display_order ASC";
        return $this->db->fetchAll($query, [$page_name]);
    }

    public function createLayout($data) {
        $query = "INSERT INTO banner_layouts (page_name, section_name, layout_type, display_order, max_banners, status) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['page_name'],
            $data['section_name'],
            $data['layout_type'],
            $data['display_order'],
            $data['max_banners'],
            $data['status']
        ];
        
        return $this->db->insert($query, $params);
    }

    public function updateLayout($id, $data) {
        $query = "UPDATE banner_layouts SET page_name = ?, section_name = ?, layout_type = ?, 
                  display_order = ?, max_banners = ?, status = ? WHERE id = ?";
        
        $params = [
            $data['page_name'],
            $data['section_name'],
            $data['layout_type'],
            $data['display_order'],
            $data['max_banners'],
            $data['status'],
            $id
        ];
        
        return $this->db->execute($query, $params);
    }

    public function deleteLayout($id) {
        $query = "DELETE FROM banner_layouts WHERE id = ?";
        return $this->db->execute($query, [$id]);
    }

    public function assignBannerToLayout($banner_id, $layout_id, $position = 0) {
        $query = "INSERT INTO banner_assignments (banner_id, layout_id, position) VALUES (?, ?, ?)";
        return $this->db->execute($query, [$banner_id, $layout_id, $position]);
    }

    public function getBannersByLayout($layout_id) {
        $query = "SELECT b.*, ba.position, ba.status as assignment_status 
                  FROM banners b 
                  INNER JOIN banner_assignments ba ON b.id = ba.banner_id 
                  WHERE ba.layout_id = ? AND ba.status = 'active' 
                  ORDER BY ba.position ASC";
        return $this->db->fetchAll($query, [$layout_id]);
    }

    public function removeBannerFromLayout($banner_id, $layout_id) {
        $query = "DELETE FROM banner_assignments WHERE banner_id = ? AND layout_id = ?";
        return $this->db->execute($query, [$banner_id, $layout_id]);
    }

    public function updateBannerPosition($banner_id, $layout_id, $position) {
        $query = "UPDATE banner_assignments SET position = ? WHERE banner_id = ? AND layout_id = ?";
        return $this->db->execute($query, [$position, $banner_id, $layout_id]);
    }

    public function getAvailablePages() {
        return [
            'home' => 'Home Page',
            'category' => 'Category Page',
            'product' => 'Product Page',
            'cart' => 'Cart Page',
            'checkout' => 'Checkout Page',
            'about' => 'About Page',
            'contact' => 'Contact Page'
        ];
    }

    public function getLayoutTypes() {
        return [
            'single' => 'Single Banner',
            'grid_2' => '2 Column Grid',
            'grid_3' => '3 Column Grid',
            'grid_4' => '4 Column Grid',
            'carousel' => 'Carousel/Slider',
            'mixed' => 'Mixed Layout'
        ];
    }
}
?>