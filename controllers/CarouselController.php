<?php
/**
 * Carousel Controller
 * Wires the carousel pages (carousels.php / add-carousel.php / edit-carousel.php)
 * to the `carousels` table. Same shape as controllers/CategoryController.php.
 */
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/CarouselModel.php');

class CarouselController {
    private $db;
    private $carousel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->carousel = new Carousel($this->db);
    }

    public function getAllCarousels() {
        return $this->carousel->getAll('desktop_sort_order', 'ASC');
    }

    public function getById($id) {
        return $this->carousel->getById((int)$id);
    }

    public function createCarousel($data) {
        return $this->carousel->create($data);
    }

    public function updateCarousel($id, $data) {
        return $this->carousel->update($id, $data);
    }

    public function deleteCarousel($id) {
        return $this->carousel->delete((int)$id);
    }

    // --- Dropdown sources -------------------------------------------------

    /** Active categories (id, name) for the category-select boxes. */
    public function getCategoriesForSelect() {
        $rows = [];
        $result = $this->db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY display_order ASC, name ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) $rows[] = $row;
        }
        return $rows;
    }

    /** Banners (id, title) for the banner-select boxes. Empty if table absent. */
    public function getBannersForSelect() {
        $rows = [];
        $result = @$this->db->query("SELECT id, title FROM banners ORDER BY id DESC");
        if ($result) {
            while ($row = $result->fetch_assoc()) $rows[] = $row;
        }
        return $rows;
    }
}
