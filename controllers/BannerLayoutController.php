<?php
require_once(__DIR__ . '/../models/BannerLayoutModel.php');
require_once(__DIR__ . '/../models/BannerModel.php');

class BannerLayoutController {
    private $layoutModel;
    private $bannerModel;

    public function __construct() {
        $this->layoutModel = new BannerLayoutModel();
        $this->bannerModel = new BannerModel();
    }

    public function getAllLayouts() {
        return $this->layoutModel->getAllLayouts();
    }

    public function getLayoutById($id) {
        return $this->layoutModel->getLayoutById($id);
    }

    public function getAvailablePages() {
        return $this->layoutModel->getAvailablePages();
    }

    public function getLayoutTypes() {
        return $this->layoutModel->getLayoutTypes();
    }

    public function createLayout($data) {
        try {
            $required = ['page_name', 'section_name', 'layout_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }

            $layoutData = [
                'page_name' => $data['page_name'],
                'section_name' => $data['section_name'],
                'layout_type' => $data['layout_type'],
                'display_order' => intval($data['display_order'] ?? 0),
                'max_banners' => intval($data['max_banners'] ?? 1),
                'status' => $data['status'] ?? 'active'
            ];

            $layoutId = $this->layoutModel->createLayout($layoutData);
            
            return ['success' => true, 'layout_id' => $layoutId];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateLayout($id, $data) {
        try {
            $required = ['page_name', 'section_name', 'layout_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }

            $layoutData = [
                'page_name' => $data['page_name'],
                'section_name' => $data['section_name'],
                'layout_type' => $data['layout_type'],
                'display_order' => intval($data['display_order'] ?? 0),
                'max_banners' => intval($data['max_banners'] ?? 1),
                'status' => $data['status'] ?? 'active'
            ];

            $success = $this->layoutModel->updateLayout($id, $layoutData);
            
            if ($success) {
                return ['success' => true];
            } else {
                throw new Exception("Failed to update layout");
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteLayout($id) {
        return $this->layoutModel->deleteLayout($id);
    }

    public function assignBanner($banner_id, $layout_id, $position = 0) {
        try {
            $success = $this->layoutModel->assignBannerToLayout($banner_id, $layout_id, $position);
            
            if ($success) {
                return ['success' => true, 'message' => 'Banner assigned successfully'];
            } else {
                throw new Exception("Failed to assign banner");
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getBannersByLayout($layout_id) {
        return $this->layoutModel->getBannersByLayout($layout_id);
    }

    public function removeBannerFromLayout($banner_id, $layout_id) {
        return $this->layoutModel->removeBannerFromLayout($banner_id, $layout_id);
    }

    public function getAllBanners() {
        return $this->bannerModel->getAllBanners();
    }

    public function getAvailableBannersForLayout($layout_id) {
        $assignedBanners = $this->getBannersByLayout($layout_id);
        $allBanners = $this->getAllBanners();
        
        $assignedIds = array_column($assignedBanners, 'id');
        $availableBanners = array_filter($allBanners, function($banner) use ($assignedIds) {
            return !in_array($banner['id'], $assignedIds);
        });
        
        return array_values($availableBanners);
    }
}
?>