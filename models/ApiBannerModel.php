<?php
require_once(__DIR__ . '/../config/database.php');

class ApiBannerModel {
    private $db;
    private $table = "banners";

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Get all active banners for a specific position
     */
    public function getBannersByPosition($position) {
        $query = "SELECT id, title, description, image_url_desktop, image_url_mobile, 
                         target_url, display_order, position, start_date, end_date
                  FROM {$this->table} 
                  WHERE position = ? AND status = 'active' 
                  AND (start_date IS NULL OR start_date <= NOW()) 
                  AND (end_date IS NULL OR end_date >= NOW())
                  ORDER BY display_order ASC";
        
        return $this->db->fetchAll($query, [$position]);
    }

    /**
     * Get all homepage banners with their positions
     */
    public function getHomepageBanners() {
        $homePositions = [
            'home_hero',
            'home_above_fold', 
            'home_mid_section_1',
            'home_mid_section_2',
            'home_mid_section_3',
            'home_below_fold',
            'home_before_footer'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }

    public function getHomeAboveFold() {
        $homePositions = [
            'home_above_fold'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }

    public function getMiddleSectionOne() {
        $homePositions = [
            'home_mid_section_1'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }

    public function getMiddleSectionTwo() {
        $homePositions = [
            'home_mid_section_2'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }

    public function getMiddleSectionThree() {
        $homePositions = [
            'home_mid_section_3'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }

    public function getMiddleSectionFour() {
        $homePositions = [
            'home_below_selection'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }

    public function getMiddleSectionFive() {
        $homePositions = [
            'home_after_discount'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
    public function getMiddleSectionSix() {
        $homePositions = [
            'home_after_rated'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
     public function getMiddleSectionNine() {
        $homePositions = [
            'home_after_top_deal_categories'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
    // after nine new 
    
    public function getMiddleSectionTen() {
        $homePositions = [
            'home_after_table_dinner_ware1'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
    public function getMiddleSectionEleven() {
        $homePositions = [
            'home_after_table_dinner_ware2'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
    public function getMiddleSectionTwelve() {
        $homePositions = [
            'home_after_table_dinner_ware3'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
    public function getMiddleSectionThirteen() {
        $homePositions = [
            'home_after_home_desocre'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
    public function getMiddleSectionFourteen() {
        $homePositions = [
            'home_after_electronics_item'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
    public function getMiddleSectionFifteen() {
        $homePositions = [
            'home_after_cloths'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
    public function getMiddleSectionSixteen() {
        $homePositions = [
            'home_after_top_selection'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
    public function getMiddleSectionSeventeen() {
        $homePositions = [
            'home_after_men_cloths'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
    public function getMiddleSectionEighteen() {
        $homePositions = [
            'home_after_men_cloths2'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }
    
    public function getMiddleSectionNineteen() {
        $homePositions = [
            'home_after_women_cloths'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }

    public function getBlogPageBanner() {
        $homePositions = [
            'blog_page_banner'
        ];
        
        $homeBanners = [];
        foreach ($homePositions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $homeBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $homeBanners;
    }

    /**
     * Get banners for category pages getMiddleSectionOne  getBlogPageBanner
     */
    public function getCategoryBanners($categoryId = null) {
        $positions = ['category_top', 'category_sidebar'];
        
        $categoryBanners = [];
        foreach ($positions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $categoryBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $categoryBanners;
    }

    /**
     * Get banners for product pages
     */
    public function getProductBanners() {
        $positions = ['product_page_top', 'product_page_middle'];
        
        $productBanners = [];
        foreach ($positions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $productBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $productBanners;
    }

    /**
     * Get banners for cart and checkout pages
     */
    public function getCheckoutBanners() {
        $positions = ['cart_page', 'checkout_top'];
        
        $checkoutBanners = [];
        foreach ($positions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $checkoutBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $checkoutBanners;
    }

    /**
     * Get sidebar banners for blog and other pages
     */
    public function getSidebarBanners() {
        $positions = ['blog_sidebar'];
        
        $sidebarBanners = [];
        foreach ($positions as $position) {
            $banners = $this->getBannersByPosition($position);
            if (!empty($banners)) {
                $sidebarBanners[$position] = [
                    'layout' => $this->getPositionLayout($position),
                    'banners' => $banners
                ];
            }
        }
        
        return $sidebarBanners;
    }

    /**
     * Get all available banner positions
     */
    public function getAllBannerPositions() {
        return [
            'home_hero' => 'Homepage Hero Section',
            'home_above_fold' => 'Homepage Above Fold',
            'home_mid_section_1' => 'Homepage Middle Section 1',
            'home_mid_section_2' => 'Homepage Middle Section 2',
            'home_mid_section_3' => 'Homepage Middle Section 3',
            'home_below_fold' => 'Homepage Below Fold',
            'home_before_footer' => 'Homepage Before Footer',
            'category_top' => 'Category Page Top',
            'category_sidebar' => 'Category Page Sidebar',
            'product_page_top' => 'Product Page Top',
            'product_page_middle' => 'Product Page Middle',
            'cart_page' => 'Cart Page',
            'checkout_top' => 'Checkout Page Top',
            'blog_sidebar' => 'Blog Sidebar',
            'newsletter_popup' => 'Newsletter Popup'
        ];
    }

    /**
     * Get layout type for position
     */
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
            'blog_sidebar' => 'sidebar',
            'newsletter_popup' => 'single'
        ];
        
        return $layouts[$position] ?? 'single';
    }

    /**
     * Get banner by ID
     */
    public function getBannerById($id) {
        $query = "SELECT id, title, description, image_url_desktop, image_url_mobile, 
                         target_url, display_order, position, status, 
                         start_date, end_date, created_at
                  FROM {$this->table} 
                  WHERE id = ?";
        
        return $this->db->fetch($query, [$id]);
    }

    /**
     * Track banner click for analytics
     */
    public function trackBannerClick($bannerId, $userId = null, $ipAddress = null) {
        $query = "INSERT INTO banner_clicks (banner_id, user_id, ip_address, clicked_at) 
                  VALUES (?, ?, ?, NOW())";
        
        return $this->db->insert($query, [$bannerId, $userId, $ipAddress]);
    }

    /**
     * Get banner statistics
     */
    public function getBannerStats($bannerId = null) {
        if ($bannerId) {
            $query = "SELECT 
                     COUNT(DISTINCT bc.id) as total_clicks,
                     COUNT(DISTINCT bc.user_id) as unique_users,
                     COUNT(DISTINCT bc.ip_address) as unique_ips
                     FROM banner_clicks bc
                     WHERE bc.banner_id = ?";
            return $this->db->fetch($query, [$bannerId]);
        } else {
            $query = "SELECT 
                     COUNT(*) as total_banners,
                     SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_banners,
                     COUNT(DISTINCT bc.id) as total_clicks
                     FROM banners b
                     LEFT JOIN banner_clicks bc ON b.id = bc.banner_id";
            return $this->db->fetch($query);
        }
    }
}
?>