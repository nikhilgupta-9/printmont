<?php
require_once(__DIR__ . '/../config/database.php');

class BannerModel {
    private $db;
    private $table = "banners";

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllBanners() {
        $query = "SELECT * FROM {$this->table} ORDER BY position, display_order ASC, created_at DESC";
        return $this->db->fetchAll($query);
    }

    public function getBannerById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetch($query, [$id]);
    }

    public function getActiveBanners() {
        $query = "SELECT * FROM {$this->table} 
                 WHERE status = 'active' 
                 AND (start_date IS NULL OR start_date <= NOW()) 
                 AND (end_date IS NULL OR end_date >= NOW())
                 ORDER BY position, display_order ASC";
        return $this->db->fetchAll($query);
    }

    public function getBannersByPosition($position) {
        $query = "SELECT * FROM {$this->table} 
                 WHERE position = ? AND status = 'active' 
                 AND (start_date IS NULL OR start_date <= NOW()) 
                 AND (end_date IS NULL OR end_date >= NOW())
                 ORDER BY display_order ASC";
        return $this->db->fetchAll($query, [$position]);
    }

    public function createBanner($data) {
        $query = "INSERT INTO {$this->table} 
                 (title, description, image_url_desktop, image_url_mobile, target_url, position, display_order, status, start_date, end_date) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['title'],
            $data['description'],
            $data['image_url_desktop'],
            $data['image_url_mobile'],
            $data['target_url'],
            $data['position'],
            $data['display_order'],
            $data['status'],
            $data['start_date'],
            $data['end_date']
        ];
        
        return $this->db->insert($query, $params);
    }

    public function updateBanner($id, $data) {
        $query = "UPDATE {$this->table} SET 
                 title = ?, description = ?, image_url_desktop = ?, image_url_mobile = ?, 
                 target_url = ?, position = ?, display_order = ?, status = ?, 
                 start_date = ?, end_date = ?, updated_at = NOW() 
                 WHERE id = ?";
        
        $params = [
            $data['title'],
            $data['description'],
            $data['image_url_desktop'],
            $data['image_url_mobile'],
            $data['target_url'],
            $data['position'],
            $data['display_order'],
            $data['status'],
            $data['start_date'],
            $data['end_date'],
            $id
        ];
        
        return $this->db->execute($query, $params);
    }

    public function deleteBanner($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->execute($query, [$id]);
    }

    public function getAvailablePositions() {
        return [
            'home_hero' => 'Homepage Hero Section (Full Width)',
            'home_above_fold' => 'Homepage Above Fold (Banner Row)',
            'home_mid_section_1' => 'Homepage Middle Section 1 (3 Column)',
            'home_mid_section_2' => 'Homepage Middle Section 2 (2 Column)', 
            'home_mid_section_3' => 'Homepage Middle Section 3 (Carousel)',
            'home_below_selection' => 'Two Banner After selection',
            'home_after_discount' => 'Homepage After Discount',
            'home_after_rated' => 'Homepage After Top Rated',
            'home_after_top_deal_categories' => 'Homepage After Top Deals and Categories',
            'home_after_table_dinner_ware1' => 'Homepage After Table and Dinnerware 1',
            'home_after_table_dinner_ware2' => 'Homepage After Table and Dinnerware 2',
            'home_after_table_dinner_ware3' => 'Homepage After Table and Dinnerware 3',
            'home_after_home_desocre' => 'Homepage After Home Decore',
            'home_after_electronics_item' => 'Homepage After Electronic Items',
            'home_after_cloths' => 'Homepage After Cloths',
            'home_after_top_selection' => 'Homepage After Top Selection',
            'home_after_men_cloths' => 'Homepage Men Cloths',
            'home_after_men_cloths2' => 'Homepage After Men Cloths',
            'home_after_women_cloths' => 'Homepage Women Cloths',
            'product_page_top' => 'Product Page Top',
            'product_page_middle' => 'Product Page Middle',
            'cart_page' => 'Cart Page',
            'checkout_top' => 'Checkout Page Top',
            'blog_page_banner' => 'Blog Page Banner',
            'newsletter_popup' => 'Newsletter Popup'
        ];
    }

    public function getLayoutTypes() {
        return [
            'single' => 'Single Banner (Full Width)',
            'row' => 'Banner Row (Multiple Banners)',
            'grid_2' => '2 Column Grid',
            'grid_3' => '3 Column Grid', 
            'carousel' => 'Image Carousel',
            'sidebar' => 'Sidebar Banner'
        ];
    }

    public function getPositionLayout($position) {
        $layouts = [
            'home_hero' => 'single',
            'home_above_fold' => 'row',
            'home_mid_section_1' => 'grid_3',
            'home_mid_section_2' => 'grid_2',
            'home_mid_section_3' => 'carousel',
            'home_below_fold' => 'single',
            'home_before_footer' => 'row',
            'category_top' => 'single',
            'category_sidebar' => 'sidebar',
            'product_page_top' => 'single',
            'product_page_middle' => 'single',
            'cart_page' => 'single',
            'checkout_top' => 'single',
            'blog_page_banner' => 'sinble',
            'newsletter_popup' => 'single'
        ];
        
        return $layouts[$position] ?? 'single';
    }

    public function getBannerStats() {
        $query = "SELECT 
                 COUNT(*) as total,
                 SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                 SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                 SUM(CASE WHEN start_date > NOW() THEN 1 ELSE 0 END) as scheduled,
                 SUM(CASE WHEN end_date < NOW() THEN 1 ELSE 0 END) as expired
                 FROM {$this->table}";
        
        return $this->db->fetch($query);
    }

    public function getBannersCountByPosition() {
        $query = "SELECT position, COUNT(*) as count 
                 FROM {$this->table} 
                 GROUP BY position 
                 ORDER BY count DESC";
        return $this->db->fetchAll($query);
    }

      public function getBannersWithPagination($page = 1, $perPage = 10, $search = '')
    {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM banners WHERE 1=1";

        if (!empty($search)) {
            $search = "%" . $search . "%";
            $sql .= " AND (title LIKE '{$search}' 
                    OR description LIKE '{$search}' 
                    OR position LIKE '{$search}')";
        }

        $sql .= " ORDER BY display_order ASC, created_at DESC 
              LIMIT {$perPage} OFFSET {$offset}";

        return $this->db->fetchAll($sql);
    }


    public function getBannersCount($search = '')
    {
        $sql = "SELECT COUNT(*) AS total FROM banners WHERE 1=1";

        if (!empty($search)) {
            $search = "%" . $search . "%";
            $sql .= " AND (title LIKE '{$search}' 
                    OR description LIKE '{$search}' 
                    OR position LIKE '{$search}')";
        }

        $result = $this->db->fetch($sql);
        return $result['total'] ?? 0;
    }
}
?>