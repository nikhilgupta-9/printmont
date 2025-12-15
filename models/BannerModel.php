<?php
require_once 'models/BannerModel.php';

class BannerController {
    private $bannerModel;

    public function __construct() {
        $this->bannerModel = new BannerModel();
    }

    public function getAllBanners() {
        return $this->bannerModel->getAllBanners();
    }

    public function getBannerById($id) {
        return $this->bannerModel->getBannerById($id);
    }

    public function getBannerTypes() {
        return $this->bannerModel->getBannerTypes();
    }

    public function addBanner($data, $files) {
        try {
            // Validate required fields
            $required = ['title', 'banner_type', 'display_order'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }

            // Handle image uploads
            $desktopImage = $this->handleImageUpload($files['image_url_desktop'], 'desktop');
            $mobileImage = $this->handleImageUpload($files['image_url_mobile'], 'mobile');

            $bannerData = [
                'title' => trim($data['title']),
                'description' => trim($data['description'] ?? ''),
                'image_url_desktop' => $desktopImage,
                'image_url_mobile' => $mobileImage,
                'target_url' => trim($data['target_url'] ?? ''),
                'banner_type' => $data['banner_type'],
                'display_order' => intval($data['display_order']),
                'status' => $data['status'] ?? 'active',
                'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
                'end_date' => !empty($data['end_date']) ? $data['end_date'] : null
            ];

            $bannerId = $this->bannerModel->createBanner($bannerData);
            
            return ['success' => true, 'banner_id' => $bannerId];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateBanner($id, $data, $files) {
        try {
            $required = ['title', 'banner_type', 'display_order'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }

            // Get current banner data
            $currentBanner = $this->bannerModel->getBannerById($id);
            
            // Handle image uploads - keep existing if no new file uploaded
            $desktopImage = $currentBanner['image_url_desktop'];
            if (!empty($files['image_url_desktop']['name'])) {
                $desktopImage = $this->handleImageUpload($files['image_url_desktop'], 'desktop');
            }

            $mobileImage = $currentBanner['image_url_mobile'];
            if (!empty($files['image_url_mobile']['name'])) {
                $mobileImage = $this->handleImageUpload($files['image_url_mobile'], 'mobile');
            }

            $bannerData = [
                'title' => trim($data['title']),
                'description' => trim($data['description'] ?? ''),
                'image_url_desktop' => $desktopImage,
                'image_url_mobile' => $mobileImage,
                'target_url' => trim($data['target_url'] ?? ''),
                'banner_type' => $data['banner_type'],
                'display_order' => intval($data['display_order']),
                'status' => $data['status'] ?? 'active',
                'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
                'end_date' => !empty($data['end_date']) ? $data['end_date'] : null
            ];

            $success = $this->bannerModel->updateBanner($id, $bannerData);
            
            if ($success) {
                return ['success' => true];
            } else {
                throw new Exception("Failed to update banner");
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteBanner($id) {
        return $this->bannerModel->deleteBanner($id);
    }

    private function handleImageUpload($file, $type) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Invalid file type for $type image. Only JPEG, PNG, GIF, and WebP are allowed.");
            }

            $uploadDir = 'uploads/banners/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = $type . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9-_\.]/', '', $file['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                return $filePath;
            } else {
                throw new Exception("Failed to upload $type image");
            }
        } else {
            throw new Exception("Please upload a valid $type image");
        }
    }
}
?>