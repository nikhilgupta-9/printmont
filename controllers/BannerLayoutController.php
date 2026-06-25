<?php
require_once(__DIR__ . '/../models/BannerModel.php');

class BannerController {
    private $bannerModel;

    public function __construct() {
        $this->bannerModel = new BannerModel();
    }

    // Add these new methods for pagination and search
    public function getBannersWithPagination($page = 1, $perPage = 10, $search = '') {
        return $this->bannerModel->getBannersWithPagination($page, $perPage, $search);
    }

    public function getBannersCount($search = '') {
        return $this->bannerModel->getBannersCount($search);
    }

    public function getAllBanners() {
        return $this->bannerModel->getAllBanners();
    }

    public function getBannerById($id) {
        return $this->bannerModel->getBannerById($id);
    }

    public function getActiveBanners() {
        return $this->bannerModel->getActiveBanners();
    }

    public function getBannersByPosition($position) {
        return $this->bannerModel->getBannersByPosition($position);
    }

    public function getAvailablePositions() {
        return $this->bannerModel->getAvailablePositions();
    }

    public function getLayoutTypes() {
        return $this->bannerModel->getLayoutTypes();
    }

    public function getPositionLayout($position) {
        return $this->bannerModel->getPositionLayout($position);
    }

    public function getBannerStats() {
        return $this->bannerModel->getBannerStats();
    }

    public function getBannersCountByPosition() {
        return $this->bannerModel->getBannersCountByPosition();
    }

    public function createBanner($data, $files) {
        try {
            // Validate required fields
            $required = ['title', 'position', 'display_order'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }

            // Handle image uploads
            $desktopImage = '';
            $mobileImage = '';

            if (!empty($files['image_url_desktop']['name'])) {
                $desktopImage = $this->handleImageUpload($files['image_url_desktop'], 'desktop');
            } else {
                throw new Exception("Desktop image is required");
            }

            if (!empty($files['image_url_mobile']['name'])) {
                $mobileImage = $this->handleImageUpload($files['image_url_mobile'], 'mobile');
            } else {
                // Use desktop image as fallback for mobile
                $mobileImage = $desktopImage;
            }

            // Prepare banner data
            $bannerData = [
                'title' => trim($data['title']),
                'description' => trim($data['description'] ?? ''),
                'image_url_desktop' => $desktopImage,
                'image_url_mobile' => $mobileImage,
                'target_url' => trim($data['target_url'] ?? ''),
                'position' => $data['position'],
                'display_order' => intval($data['display_order']),
                'status' => $data['status'] ?? 'active',
                'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
                'end_date' => !empty($data['end_date']) ? $data['end_date'] : null
            ];

            $bannerId = $this->bannerModel->createBanner($bannerData);
            
            return ['success' => true, 'banner_id' => $bannerId, 'message' => 'Banner created successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateBanner($id, $data, $files) {
        try {
            // Get current banner
            $currentBanner = $this->bannerModel->getBannerById($id);
            if (!$currentBanner) {
                throw new Exception("Banner not found");
            }

            // Handle image uploads
            $desktopImage = $currentBanner['image_url_desktop'];
            $mobileImage = $currentBanner['image_url_mobile'];

            if (!empty($files['image_url_desktop']['name'])) {
                // Delete old desktop image if exists
                if (!empty($currentBanner['image_url_desktop']) && file_exists($currentBanner['image_url_desktop'])) {
                    unlink($currentBanner['image_url_desktop']);
                }
                $desktopImage = $this->handleImageUpload($files['image_url_desktop'], 'desktop');
            }

            if (!empty($files['image_url_mobile']['name'])) {
                // Delete old mobile image if exists
                if (!empty($currentBanner['image_url_mobile']) && file_exists($currentBanner['image_url_mobile'])) {
                    unlink($currentBanner['image_url_mobile']);
                }
                $mobileImage = $this->handleImageUpload($files['image_url_mobile'], 'mobile');
            }

            // Prepare banner data
            $bannerData = [
                'title' => trim($data['title']),
                'description' => trim($data['description'] ?? ''),
                'image_url_desktop' => $desktopImage,
                'image_url_mobile' => $mobileImage,
                'target_url' => trim($data['target_url'] ?? ''),
                'position' => $data['position'],
                'display_order' => intval($data['display_order']),
                'status' => $data['status'] ?? 'active',
                'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
                'end_date' => !empty($data['end_date']) ? $data['end_date'] : null
            ];

            $success = $this->bannerModel->updateBanner($id, $bannerData);
            
            if ($success) {
                return ['success' => true, 'message' => 'Banner updated successfully'];
            } else {
                throw new Exception("Failed to update banner");
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteBanner($id) {
        try {
            // Get banner to delete images
            $banner = $this->bannerModel->getBannerById($id);
            if (!$banner) {
                throw new Exception("Banner not found");
            }

            // Delete images if they exist and are different files
            if (!empty($banner['image_url_desktop']) && file_exists($banner['image_url_desktop'])) {
                unlink($banner['image_url_desktop']);
            }
            if (!empty($banner['image_url_mobile']) && file_exists($banner['image_url_mobile']) && $banner['image_url_mobile'] !== $banner['image_url_desktop']) {
                unlink($banner['image_url_mobile']);
            }

            $success = $this->bannerModel->deleteBanner($id);
            
            if ($success) {
                return ['success' => true, 'message' => 'Banner deleted successfully'];
            } else {
                throw new Exception("Failed to delete banner");
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function handleImageUpload($file, $type) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $file['error']);
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.");
        }

        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("File size too large. Maximum size is 5MB.");
        }

        // Create directory if it doesn't exist
        $uploadDir = 'uploads/banners/' . $type . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9-_\.]/', '', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return $filePath;
        } else {
            throw new Exception("Failed to upload file: " . $file['name']);
        }
    }

    
}
?>