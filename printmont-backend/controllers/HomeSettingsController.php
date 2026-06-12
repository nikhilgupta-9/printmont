<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/HomeSlider.php');
require_once(__DIR__ . '/../models/HomeOffer.php');
require_once(__DIR__ . '/../models/HomeService.php');

class HomeSettingsController {
    private $db;
    private $slider;
    private $offer;
    private $service;

    public function __construct() {
        $this->db = new Database();
        $this->slider = new HomeSlider($this->db->getConnection());
        $this->offer = new HomeOffer($this->db->getConnection());
        $this->service = new HomeService($this->db->getConnection());
    }

    // Slider Methods
    public function getAllSliders() {
        return $this->slider->getAll();
    }

    public function getActiveSliders() {
        return $this->slider->getActiveSliders();
    }

    public function getSliderById($id) {
        return $this->slider->getById($id);
    }

    public function createSlider($data) {
        return $this->slider->create($data);
    }

    public function updateSlider($id, $data) {
        return $this->slider->update($id, $data);
    }

    public function deleteSlider($id) {
        return $this->slider->delete($id);
    }

    public function updateSliderOrder($id, $order) {
        return $this->slider->updateOrder($id, $order);
    }

    public function getSliderMaxOrder() {
        return $this->slider->getMaxOrder();
    }

    // Offer Methods
    public function getAllOffers() {
        return $this->offer->getAll();
    }

    public function getActiveOffers() {
        return $this->offer->getActiveOffers();
    }

    public function getOfferById($id) {
        return $this->offer->getById($id);
    }

    public function createOffer($data) {
        return $this->offer->create($data);
    }

    public function updateOffer($id, $data) {
        return $this->offer->update($id, $data);
    }

    public function deleteOffer($id) {
        return $this->offer->delete($id);
    }

    public function updateOfferOrder($id, $order) {
        return $this->offer->updateOrder($id, $order);
    }

    public function getOfferMaxOrder() {
        return $this->offer->getMaxOrder();
    }

    // Service Methods
    public function getAllServices() {
        return $this->service->getAll();
    }

    public function getActiveServices() {
        return $this->service->getActiveServices();
    }

    public function getServiceById($id) {
        return $this->service->getById($id);
    }

    public function createService($data) {
        return $this->service->create($data);
    }

    public function updateService($id, $data) {
        return $this->service->update($id, $data);
    }

    public function deleteService($id) {
        return $this->service->delete($id);
    }

    public function updateServiceOrder($id, $order) {
        return $this->service->updateOrder($id, $order);
    }

    public function getServiceMaxOrder() {
        return $this->service->getMaxOrder();
    }

    // Validation Methods
    public function validateImage($imageUrl) {
        if (empty($imageUrl)) {
            throw new Exception("Image URL is required.");
        }

        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw new Exception("Please provide a valid image URL.");
        }

        return true;
    }

    public function validateDates($startDate, $endDate) {
        if (!empty($startDate) && !empty($endDate)) {
            if (strtotime($startDate) > strtotime($endDate)) {
                throw new Exception("End date cannot be before start date.");
            }
        }

        return true;
    }

    public function validateColor($color) {
        if (!empty($color) && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            throw new Exception("Please provide a valid hex color code (e.g., #ffffff).");
        }

        return true;
    }
}
?>