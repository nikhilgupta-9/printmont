<?php
require_once(__DIR__ . '/../config/database.php');

class SmallBannerSliderController {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllSliders(): array {
        return $this->db->fetchAll(
            "SELECT * FROM sliders 
             WHERE slider_type = 'small' 
             ORDER BY desktop_sort_order ASC, mobile_sort_order ASC, id DESC"
        );
    }

    public function getSlidersFiltered(string $page = '', string $section = '', string $device = ''): array {
        $all = $this->getAllSliders();
        if (empty($page) && empty($section) && empty($device)) {
            return $all;
        }

        return array_values(array_filter($all, function($s) use ($page, $section, $device) {
            // Filter by device if requested
            if ($device === 'desktop' && empty($s['desktop_image'])) return false;
            if ($device === 'mobile' && empty($s['mobile_image'])) return false;

            // Filter by page if requested
            if (!empty($page) && $page !== 'all') {
                $rawPgs = !empty($s['target_pages']) ? explode(',', strtolower($s['target_pages'])) : ['home'];
                $matchPage = in_array('all', $rawPgs) || in_array(strtolower($page), $rawPgs);
                if (!$matchPage) return false;
            }

            // Filter by section if requested
            if (!empty($section)) {
                $targetSec = strtolower($s['target_section'] ?? '');
                $rules = strtolower($s['placement_rules'] ?? '');
                $matchSec = ($targetSec === strtolower($section)) 
                    || (strpos($targetSec, strtolower($section)) !== false)
                    || (strpos($rules, strtolower($section)) !== false);
                if (!$matchSec) return false;
            }

            return true;
        }));
    }

    public function getPageCounts(): array {
        $all = $this->getAllSliders();
        $counts = [
            'all' => count($all),
            'home' => 0,
            'category' => 0,
            'product' => 0,
            'cart' => 0,
            'checkout' => 0,
            'other' => 0
        ];

        foreach ($all as $s) {
            $rawPgs = !empty($s['target_pages']) ? explode(',', strtolower($s['target_pages'])) : ['home'];
            if (in_array('all', $rawPgs)) {
                $counts['home']++;
                $counts['category']++;
                $counts['product']++;
                $counts['cart']++;
                $counts['checkout']++;
                $counts['other']++;
                continue;
            }

            foreach ($rawPgs as $p) {
                $p = trim($p);
                if (isset($counts[$p])) {
                    $counts[$p]++;
                } else {
                    $counts['other']++;
                }
            }
        }

        return $counts;
    }

    public function getActiveSliders(): array {
        return $this->db->fetchAll(
            "SELECT * FROM sliders 
             WHERE slider_type = 'small' AND status = 'active'
               AND (start_date IS NULL OR start_date <= CURDATE())
               AND (end_date IS NULL OR end_date >= CURDATE())
             ORDER BY desktop_sort_order ASC, id ASC"
        );
    }

    public function getSliderById(int $id): ?array {
        $row = $this->db->fetch("SELECT * FROM sliders WHERE id = ? AND slider_type = 'small'", [$id]);
        return $row ?: null;
    }

    /**
     * Creates separate Desktop and Mobile sliders.
     */
    public function createSeparateSliders(array $post, array $files): int {
        $globalTargetPages = 'home';
        if (!empty($post['target_pages'])) {
            if (is_array($post['target_pages'])) {
                $globalTargetPages = in_array('all', $post['target_pages']) ? 'all' : implode(',', array_filter($post['target_pages']));
            } else {
                $globalTargetPages = trim($post['target_pages']);
            }
        }
        if (empty($globalTargetPages)) $globalTargetPages = 'home';

        $autoPlaySpeedDesktop = (int)($post['desktop_auto_play_speed'] ?? ($post['auto_play_speed'] ?? 3000));
        if ($autoPlaySpeedDesktop < 500) $autoPlaySpeedDesktop = 3000;
        $autoRunTimeDesktop   = (int)ceil($autoPlaySpeedDesktop / 1000);

        $autoPlaySpeedMobile  = (int)($post['mobile_auto_play_speed'] ?? ($post['auto_play_speed'] ?? 3000));
        if ($autoPlaySpeedMobile < 500) $autoPlaySpeedMobile = 3000;
        $autoRunTimeMobile    = (int)ceil($autoPlaySpeedMobile / 1000);

        $startDate = !empty($post['start_date']) ? $post['start_date'] : null;
        $endDate   = !empty($post['end_date']) ? $post['end_date'] : null;
        $status    = in_array($post['status'] ?? '', ['active', 'inactive']) ? $post['status'] : 'active';
        $globalMobileDesign = trim($post['mobile_design'] ?? 'small_banner_standard');

        $positionRelation = in_array($post['position_relation'] ?? '', ['after', 'before', 'top', 'bottom']) ? $post['position_relation'] : 'after';
        $targetSection    = trim($post['target_section'] ?? 'home_hero');
        if (empty($targetSection)) $targetSection = 'home_hero';

        $pagePlacement = [];
        if (!empty($post['page_placement']) && is_array($post['page_placement'])) {
            foreach ($post['page_placement'] as $pKey => $pVal) {
                if (is_array($pVal)) {
                    $pos = in_array($pVal['position'] ?? '', ['after', 'before', 'top', 'bottom']) ? $pVal['position'] : 'after';
                    $secs = [];
                    if (!empty($pVal['sections'])) {
                        $secs = is_array($pVal['sections']) ? array_filter(array_map('trim', $pVal['sections'])) : [trim($pVal['sections'])];
                    } elseif (!empty($pVal['section'])) {
                        $secs = is_array($pVal['section']) ? array_filter(array_map('trim', $pVal['section'])) : [trim($pVal['section'])];
                    }
                    if (empty($secs)) {
                        $secs = ['home_hero'];
                    }
                    $pagePlacement[$pKey] = [
                        'position' => $pos,
                        'sections' => array_values($secs),
                        'section'  => implode(',', $secs)
                    ];
                }
            }
        }
        $placementRules = !empty($pagePlacement) ? json_encode($pagePlacement) : null;

        $createdCount = 0;

        // 1. Process Desktop Slides
        $deskPrimaryPos = $positionRelation;
        $deskPrimarySec = $targetSection;
        if (!empty($pagePlacement)) {
            if (isset($pagePlacement['home_desktop'])) {
                $deskPrimaryPos = $pagePlacement['home_desktop']['position'];
                $deskPrimarySec = $pagePlacement['home_desktop']['section'];
            } else {
                foreach ($pagePlacement as $pk => $pv) {
                    if (str_ends_with($pk, '_desktop') || $pk === 'home') {
                        $deskPrimaryPos = $pv['position'];
                        $deskPrimarySec = $pv['section'];
                        break;
                    }
                }
            }
        }

        if (!empty($post['desktop_slides']) && is_array($post['desktop_slides'])) {
            foreach ($post['desktop_slides'] as $index => $slideData) {
                $deskFile = $files['desktop_slide_image_' . $index] ?? null;
                $deskImg = $deskFile ? $this->uploadFile($deskFile, 'desktop') : null;

                if (empty($deskImg)) continue; // Skip if no image

                $title      = trim($slideData['title'] ?? '');
                if (empty($title)) $title = 'Desktop Small Banner #' . ($index + 1);
                $deskUrl    = trim($slideData['desktop_url'] ?? '');
                $deskSort   = isset($slideData['desktop_sort_order']) ? (int)$slideData['desktop_sort_order'] : (int)$index;
                $deskStatus = in_array($slideData['desktop_status'] ?? '', ['active', 'inactive']) ? $slideData['desktop_status'] : 'active';

                $insertId = $this->db->insert(
                    "INSERT INTO sliders 
                     (title, slider_type, target_pages, position_relation, target_section, placement_rules,
                      desktop_design, desktop_image, desktop_url, desktop_sort_order, desktop_status,
                      mobile_design, mobile_image, mobile_url, mobile_sort_order, mobile_status,
                      auto_play_speed, auto_run_time, start_date, end_date, status)
                     VALUES (?, 'small', ?, ?, ?, ?, 'small_banner', ?, ?, ?, ?, ?, '', '', 0, 'inactive', ?, ?, ?, ?, ?)",
                    [
                        $title, $globalTargetPages, $deskPrimaryPos, $deskPrimarySec, $placementRules,
                        $deskImg, $deskUrl, $deskSort, $deskStatus,
                        $globalMobileDesign, $autoPlaySpeedDesktop, $autoRunTimeDesktop, $startDate, $endDate, $status
                    ]
                );

                if ($insertId) $createdCount++;
            }
        }

        // 2. Process Mobile Slides
        $mobPrimaryPos = $positionRelation;
        $mobPrimarySec = $targetSection;
        if (!empty($pagePlacement)) {
            if (isset($pagePlacement['home_mobile'])) {
                $mobPrimaryPos = $pagePlacement['home_mobile']['position'];
                $mobPrimarySec = $pagePlacement['home_mobile']['section'];
            } else {
                foreach ($pagePlacement as $pk => $pv) {
                    if (str_ends_with($pk, '_mobile') || $pk === 'home') {
                        $mobPrimaryPos = $pv['position'];
                        $mobPrimarySec = $pv['section'];
                        break;
                    }
                }
            }
        }

        if (!empty($post['mobile_slides']) && is_array($post['mobile_slides'])) {
            foreach ($post['mobile_slides'] as $index => $slideData) {
                $mobFile = $files['mobile_slide_image_' . $index] ?? null;
                $mobImg  = $mobFile ? $this->uploadFile($mobFile, 'mobile') : null;

                if (empty($mobImg)) continue; // Skip if no image

                $title      = trim($slideData['title'] ?? '');
                if (empty($title)) $title = 'Mobile Small Banner #' . ($index + 1);
                $mobUrl     = trim($slideData['mobile_url'] ?? '');
                $mobSort    = isset($slideData['mobile_sort_order']) ? (int)$slideData['mobile_sort_order'] : (int)$index;
                $mobStatus  = in_array($slideData['mobile_status'] ?? '', ['active', 'inactive']) ? $slideData['mobile_status'] : 'active';
                $mobDesign  = trim($slideData['mobile_design'] ?? $globalMobileDesign);

                $insertId = $this->db->insert(
                    "INSERT INTO sliders 
                     (title, slider_type, target_pages, position_relation, target_section, placement_rules,
                      desktop_design, desktop_image, desktop_url, desktop_sort_order, desktop_status,
                      mobile_design, mobile_image, mobile_url, mobile_sort_order, mobile_status,
                      auto_play_speed, auto_run_time, start_date, end_date, status)
                     VALUES (?, 'small', ?, ?, ?, ?, 'small_banner', '', '', 0, 'inactive', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $title, $globalTargetPages, $mobPrimaryPos, $mobPrimarySec, $placementRules,
                        $mobDesign, $mobImg, $mobUrl, $mobSort, $mobStatus,
                        $autoPlaySpeedMobile, $autoRunTimeMobile, $startDate, $endDate, $status
                    ]
                );

                if ($insertId) $createdCount++;
            }
        }

        // 3. Fallback: Unified Slides Repeater if submitted via 'slides'
        if ($createdCount === 0 && !empty($post['slides']) && is_array($post['slides'])) {
            return $this->createDynamicSliders($post, $files);
        }

        // 4. Fallback: Direct Multi-file upload
        if ($createdCount === 0) {
            return $this->createMultipleSliders($post, $files);
        }

        return $createdCount;
    }

    public function createDynamicSliders(array $post, array $files): int {
        return $this->createSeparateSliders($post, $files);
    }

    public function createSlider(array $post, array $files): int {
        return $this->createSeparateSliders($post, $files);
    }

    public function updateSlider(int $id, array $post, array $files): bool {
        $existing = $this->getSliderById($id);
        if (!$existing) return false;

        $desktopImage = $this->uploadFile($files['desktop_image'] ?? [], 'desktop');
        $mobileImage  = $this->uploadFile($files['mobile_image'] ?? [], 'mobile');

        if (empty($desktopImage)) {
            $desktopImage = $existing['desktop_image'];
        }
        if (empty($mobileImage)) {
            $mobileImage = $existing['mobile_image'] ?: $desktopImage;
        }

        $title            = trim($post['title'] ?? $existing['title']);
        $desktopUrl       = trim($post['desktop_url'] ?? $existing['desktop_url']);
        $desktopSortOrder = (int)($post['desktop_sort_order'] ?? $existing['desktop_sort_order']);
        $desktopStatus    = in_array($post['desktop_status'] ?? '', ['active', 'inactive']) ? $post['desktop_status'] : ($existing['desktop_status'] ?? 'active');
        
        $mobileDesign     = trim($post['mobile_design'] ?? ($existing['mobile_design'] ?: 'small_banner_standard'));
        $mobileUrl        = trim($post['mobile_url'] ?? $existing['mobile_url']);
        $mobileSortOrder  = (int)($post['mobile_sort_order'] ?? $existing['mobile_sort_order']);
        $mobileStatus     = in_array($post['mobile_status'] ?? '', ['active', 'inactive']) ? $post['mobile_status'] : ($existing['mobile_status'] ?? 'active');
        
        $status           = in_array($post['status'] ?? '', ['active', 'inactive']) ? $post['status'] : $existing['status'];
        $autoPlaySpeed    = (int)($post['auto_play_speed'] ?? $existing['auto_play_speed'] ?? 3000);
        if ($autoPlaySpeed < 500) $autoPlaySpeed = 3000;
        $autoRunTime      = (int)ceil($autoPlaySpeed / 1000);
        $startDate        = !empty($post['start_date']) ? $post['start_date'] : null;
        $endDate          = !empty($post['end_date']) ? $post['end_date'] : null;

        $positionRelation = in_array($post['position_relation'] ?? '', ['after', 'before', 'top', 'bottom']) ? $post['position_relation'] : ($existing['position_relation'] ?? 'after');
        $targetSection    = trim($post['target_section'] ?? ($existing['target_section'] ?? 'home_hero'));

        $pagePlacement = [];
        if (!empty($post['page_placement']) && is_array($post['page_placement'])) {
            foreach ($post['page_placement'] as $pKey => $pVal) {
                if (is_array($pVal)) {
                    $pos = in_array($pVal['position'] ?? '', ['after', 'before', 'top', 'bottom']) ? $pVal['position'] : 'after';
                    $secs = [];
                    if (!empty($pVal['sections'])) {
                        $secs = is_array($pVal['sections']) ? array_filter(array_map('trim', $pVal['sections'])) : [trim($pVal['sections'])];
                    } elseif (!empty($pVal['section'])) {
                        $secs = is_array($pVal['section']) ? array_filter(array_map('trim', $pVal['section'])) : [trim($pVal['section'])];
                    }
                    if (empty($secs)) {
                        $secs = ['home_hero'];
                    }
                    $pagePlacement[$pKey] = [
                        'position' => $pos,
                        'sections' => array_values($secs),
                        'section'  => implode(',', $secs)
                    ];
                }
            }
        }
        $placementRules = !empty($pagePlacement) ? json_encode($pagePlacement) : ($existing['placement_rules'] ?? null);
        if (!empty($pagePlacement['home_desktop']['section'])) {
            $targetSection = $pagePlacement['home_desktop']['section'];
        } elseif (!empty($pagePlacement['home_mobile']['section'])) {
            $targetSection = $pagePlacement['home_mobile']['section'];
        }

        $targetPages = $existing['target_pages'] ?? 'home';
        if (isset($post['target_pages'])) {
            if (is_array($post['target_pages'])) {
                $targetPages = in_array('all', $post['target_pages']) ? 'all' : implode(',', array_filter($post['target_pages']));
            } else {
                $targetPages = trim($post['target_pages']);
            }
        }
        if (empty($targetPages)) $targetPages = 'home';

        $this->db->execute(
            "UPDATE sliders SET 
             title = ?, target_pages = ?, position_relation = ?, target_section = ?, placement_rules = ?,
             desktop_design = 'small_banner', desktop_image = ?, desktop_url = ?, desktop_sort_order = ?, desktop_status = ?,
             mobile_design = ?, mobile_image = ?, mobile_url = ?, mobile_sort_order = ?, mobile_status = ?,
             auto_play_speed = ?, auto_run_time = ?, start_date = ?, end_date = ?, status = ?
             WHERE id = ? AND slider_type = 'small'",
            [
                $title, $targetPages, $positionRelation, $targetSection, $placementRules,
                $desktopImage, $desktopUrl, $desktopSortOrder, $desktopStatus,
                $mobileDesign, $mobileImage, $mobileUrl, $mobileSortOrder, $mobileStatus,
                $autoPlaySpeed, $autoRunTime, $startDate, $endDate, $status,
                $id
            ]
        );

        return true;
    }

    public function deleteSlider(int $id): bool {
        $existing = $this->getSliderById($id);
        if (!$existing) return false;

        $this->db->execute("DELETE FROM sliders WHERE id = ? AND slider_type = 'small'", [$id]);
        return true;
    }

    public function toggleStatus(int $id, string $type = 'all'): bool {
        $existing = $this->getSliderById($id);
        if (!$existing) return false;

        if ($type === 'desktop') {
            $newStatus = ($existing['desktop_status'] === 'active') ? 'inactive' : 'active';
            $this->db->execute("UPDATE sliders SET desktop_status = ? WHERE id = ?", [$newStatus, $id]);
        } elseif ($type === 'mobile') {
            $newStatus = ($existing['mobile_status'] === 'active') ? 'inactive' : 'active';
            $this->db->execute("UPDATE sliders SET mobile_status = ? WHERE id = ?", [$newStatus, $id]);
        } else {
            $newStatus = ($existing['status'] === 'active') ? 'inactive' : 'active';
            $this->db->execute("UPDATE sliders SET status = ?, desktop_status = ?, mobile_status = ? WHERE id = ?", [$newStatus, $newStatus, $newStatus, $id]);
        }
        return true;
    }

    public function uploadFile(array $file, string $subfolder): ?string {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            throw new Exception("Invalid file extension: $ext. Allowed: " . implode(', ', $allowedExts));
        }

        $uploadBaseDir = __DIR__ . '/../uploads/banners/' . $subfolder . '/';
        if (!is_dir($uploadBaseDir)) {
            @mkdir($uploadBaseDir, 0777, true);
        }

        $filename = uniqid('small_') . '_' . time() . '.' . $ext;
        $destination = $uploadBaseDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            if (!copy($file['tmp_name'], $destination)) {
                throw new Exception("Failed to save uploaded file to $destination");
            }
        }

        return 'uploads/banners/' . $subfolder . '/' . $filename;
    }

    public function uploadMultipleFiles(array $filesArray, string $subfolder): array {
        $uploaded = [];
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $uploadBaseDir = __DIR__ . '/../uploads/banners/' . $subfolder . '/';
        
        if (!is_dir($uploadBaseDir)) {
            @mkdir($uploadBaseDir, 0777, true);
        }

        $count = is_array($filesArray['name']) ? count($filesArray['name']) : 0;
        for ($i = 0; $i < $count; $i++) {
            if ($filesArray['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($filesArray['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts)) {
                    continue;
                }

                $filename = uniqid('small_') . '_' . time() . '_' . $i . '.' . $ext;
                $destination = $uploadBaseDir . $filename;

                if (move_uploaded_file($filesArray['tmp_name'][$i], $destination) || copy($filesArray['tmp_name'][$i], $destination)) {
                    $uploaded[] = 'uploads/banners/' . $subfolder . '/' . $filename;
                }
            }
        }

        return $uploaded;
    }
}
