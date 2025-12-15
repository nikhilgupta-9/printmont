<?php
require_once(__DIR__ . '/../models/ApiBannerModel.php');

class ApiBannerController {
    private $bannerModel;

    public function __construct() {
        $this->bannerModel = new ApiBannerModel();
    }

    /**
     * Set JSON response headers
     */
    private function setJsonHeaders() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    /**
     * Send JSON response
     */
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Handle OPTIONS request for CORS
     */
    public function handleOptions() {
        $this->setJsonHeaders();
        exit;
    }

    /**
     * Get banners by specific position
     */
    public function getBannersByPosition($position) {
        $this->setJsonHeaders();

        try {
            if (empty($position)) {
                throw new Exception("Position parameter is required");
            }

            $banners = $this->bannerModel->getBannersByPosition($position);
            
            $response = [
                'success' => true,
                'data' => [
                    'position' => $position,
                    'layout' => $this->bannerModel->getPositionLayout($position),
                    'banners' => $this->formatBanners($banners)
                ],
                'meta' => [
                    'total' => count($banners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get all homepage banners
     */
    public function getHomepageBanners() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getHomepageBanners();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getHomeAboveFold() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getHomeAboveFold();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMiddleSectionOne() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionOne();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMiddleSectionTwo() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionTwo();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMiddleSectionThree() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionThree();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMiddleSectionFour() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionFour();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMiddleSectionFive() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionFive();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
     public function getMiddleSectionSix() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionSix();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
     public function getMiddleSectionNine() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionNine();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getMiddleSectionTen() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionTen();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getMiddleSectionEleven() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionEleven();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getMiddleSectionTwelve() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionTwelve();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getMiddleSectionThirteen() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionThirteen();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getMiddleSectionFourteen() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionFourteen();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getMiddleSectionFifteen() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionFifteen();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getMiddleSectionSixteen() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionSixteen();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getMiddleSectionSeventeen() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionSeventeen();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getMiddleSectionEighteen() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionEighteen();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getMiddleSectionNineteen() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getMiddleSectionNineteen();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBlogPageBanner() {
        $this->setJsonHeaders();

        try {
            $homeBanners = $this->bannerModel->getBlogPageBanner();
            
            $response = [
                'success' => true,
                'data' => $this->formatHomepageBanners($homeBanners),
                'meta' => [
                    'total_sections' => count($homeBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category page banners
     */
    public function getCategoryBanners($categoryId = null) {
        $this->setJsonHeaders();

        try {
            $categoryBanners = $this->bannerModel->getCategoryBanners($categoryId);
            
            $response = [
                'success' => true,
                'data' => $categoryBanners,
                'meta' => [
                    'category_id' => $categoryId,
                    'total_sections' => count($categoryBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product page banners
     */
    public function getProductBanners() {
        $this->setJsonHeaders();

        try {
            $productBanners = $this->bannerModel->getProductBanners();
            
            $response = [
                'success' => true,
                'data' => $productBanners,
                'meta' => [
                    'total_sections' => count($productBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get checkout page banners
     */
    public function getCheckoutBanners() {
        $this->setJsonHeaders();

        try {
            $checkoutBanners = $this->bannerModel->getCheckoutBanners();
            
            $response = [
                'success' => true,
                'data' => $checkoutBanners,
                'meta' => [
                    'total_sections' => count($checkoutBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sidebar banners
     */
    public function getSidebarBanners() {
        $this->setJsonHeaders();

        try {
            $sidebarBanners = $this->bannerModel->getSidebarBanners();
            
            $response = [
                'success' => true,
                'data' => $sidebarBanners,
                'meta' => [
                    'total_sections' => count($sidebarBanners),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available positions
     */
    public function getBannerPositions() {
        $this->setJsonHeaders();

        try {
            $positions = $this->bannerModel->getAllBannerPositions();
            
            $response = [
                'success' => true,
                'data' => $positions,
                'meta' => [
                    'total_positions' => count($positions),
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track banner click
     */
    public function trackBannerClick() {
        $this->setJsonHeaders();

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['banner_id']) || empty($input['banner_id'])) {
                throw new Exception("Banner ID is required");
            }

            $bannerId = intval($input['banner_id']);
            $userId = isset($input['user_id']) ? intval($input['user_id']) : null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

            // Verify banner exists
            $banner = $this->bannerModel->getBannerById($bannerId);
            if (!$banner) {
                throw new Exception("Banner not found");
            }

            $success = $this->bannerModel->trackBannerClick($bannerId, $userId, $ipAddress);
            
            if ($success) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Banner click tracked successfully',
                    'data' => [
                        'banner_id' => $bannerId,
                        'click_id' => $success
                    ]
                ]);
            } else {
                throw new Exception("Failed to track banner click");
            }

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get banner statistics
     */
    public function getBannerStats($bannerId = null) {
        $this->setJsonHeaders();

        try {
            $stats = $this->bannerModel->getBannerStats($bannerId);
            
            $response = [
                'success' => true,
                'data' => $stats,
                'meta' => [
                    'banner_id' => $bannerId,
                    'timestamp' => date('c')
                ]
            ];

            $this->sendResponse($response);

        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format banners for API response
     */
    private function formatBanners($banners) {
        $baseUrl = $this->getBaseUrl();
        
        return array_map(function($banner) use ($baseUrl) {
            return [
                'id' => (int)$banner['id'],
                'title' => $banner['title'],
                'description' => $banner['description'],
                'images' => [
                    'desktop' => $baseUrl . $banner['image_url_desktop'],
                    'mobile' => $baseUrl . $banner['image_url_mobile']
                ],
                'target_url' => $banner['target_url'],
                'display_order' => (int)$banner['display_order'],
                'position' => $banner['position'],
                'dates' => [
                    'start' => $banner['start_date'],
                    'end' => $banner['end_date']
                ]
            ];
        }, $banners);
    }

    /**
     * Format homepage banners for API response
     */
    private function formatHomepageBanners($homeBanners) {
        $formatted = [];
        
        foreach ($homeBanners as $position => $section) {
            $formatted[$position] = [
                'layout' => $section['layout'],
                'banners' => $this->formatBanners($section['banners'])
            ];
        }
        
        return $formatted;
    }

    /**
     * Get base URL for image paths
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . "://" . $host . '/';
    }
}
?>