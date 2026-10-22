<?php
require_once(__DIR__ . '/../config/database.php');

class HomePageTopSliderController {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllSliders(): array {
        return $this->db->fetchAll(
            "SELECT * FROM sliders 
             WHERE slider_type = 'main' 
             ORDER BY desktop_sort_order ASC, mobile_sort_order ASC, id DESC"
        );
    }

    public function getActiveSliders(): array {
        return $this->db->fetchAll(
            "SELECT * FROM sliders 
             WHERE slider_type = 'main' AND status = 'active'
               AND (start_date IS NULL OR start_date <= CURDATE())
               AND (end_date IS NULL OR end_date >= CURDATE())
             ORDER BY desktop_sort_order ASC, id ASC"
        );
    }

    public function getSliderById(int $id): ?array {
        $row = $this->db->fetch("SELECT * FROM sliders WHERE id = ?", [$id]);
        return $row ?: null;
    }

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
        $globalMobileDesign = trim($post['mobile_design'] ?? 'design_1');
        $globalDesktopDesign = trim($post['desktop_design'] ?? 'format_1');

        $positionRelation = in_array($post['position_relation'] ?? '', ['after', 'before', 'top', 'bottom']) ? $post['position_relation'] : 'top';
        $targetSection    = trim($post['target_section'] ?? 'home_hero');
        if (empty($targetSection)) $targetSection = 'home_hero';

        $pagePlacement = [];
        if (!empty($post['page_placement']) && is_array($post['page_placement'])) {
            foreach ($post['page_placement'] as $pKey => $pVal) {
                if (is_array($pVal)) {
                    $pos = in_array($pVal['position'] ?? '', ['after', 'before', 'top', 'bottom']) ? $pVal['position'] : 'top';
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
                if (empty($title)) $title = 'Desktop Top Slider #' . ($index + 1);
                $deskUrl    = trim($slideData['desktop_url'] ?? '');
                $deskSort   = isset($slideData['desktop_sort_order']) ? (int)$slideData['desktop_sort_order'] : (int)($index + 1);
                $deskStatus = in_array($slideData['desktop_status'] ?? '', ['active', 'inactive']) ? $slideData['desktop_status'] : 'active';
                $deskDesign = trim($slideData['desktop_design'] ?? $globalDesktopDesign);

                $insertId = $this->db->insert(
                    "INSERT INTO sliders 
                     (title, slider_type, target_pages, position_relation, target_section, placement_rules,
                      desktop_design, desktop_image, desktop_url, desktop_sort_order, desktop_status,
                      mobile_design, mobile_image, mobile_url, mobile_sort_order, mobile_status,
                      auto_play_speed, auto_run_time, start_date, end_date, status)
                     VALUES (?, 'main', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '', 0, 'inactive', ?, ?, ?, ?, ?)",
                    [
                        $title, $globalTargetPages, $deskPrimaryPos, $deskPrimarySec, $placementRules,
                        $deskDesign, $deskImg, $deskUrl, $deskSort, $deskStatus,
                        $globalMobileDesign, $autoPlaySpeedDesktop, $autoRunTimeDesktop, $startDate, $endDate, $status
                    ]
                );

                if ($insertId) {
                    $this->syncBanner($insertId, $title, $deskImg, $deskImg, $deskUrl, $deskSort, $deskStatus, $startDate, $endDate);
                    $createdCount++;
                }
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
                $mobImg = $mobFile ? $this->uploadFile($mobFile, 'mobile') : null;

                if (empty($mobImg)) continue; // Skip if no image

                $title     = trim($slideData['title'] ?? '');
                if (empty($title)) $title = 'Mobile Top Slider #' . ($index + 1);
                $mobUrl    = trim($slideData['mobile_url'] ?? '');
                $mobSort   = isset($slideData['mobile_sort_order']) ? (int)$slideData['mobile_sort_order'] : (int)($index + 1);
                $mobStatus = in_array($slideData['mobile_status'] ?? '', ['active', 'inactive']) ? $slideData['mobile_status'] : 'active';
                $mobDesign = trim($slideData['mobile_design'] ?? $globalMobileDesign);

                $insertId = $this->db->insert(
                    "INSERT INTO sliders 
                     (title, slider_type, target_pages, position_relation, target_section, placement_rules,
                      desktop_design, desktop_image, desktop_url, desktop_sort_order, desktop_status,
                      mobile_design, mobile_image, mobile_url, mobile_sort_order, mobile_status,
                      auto_play_speed, auto_run_time, start_date, end_date, status)
                     VALUES (?, 'main', ?, ?, ?, ?, 'format_1', '', '', 0, 'inactive', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $title, $globalTargetPages, $mobPrimaryPos, $mobPrimarySec, $placementRules,
                        $mobDesign, $mobImg, $mobUrl, $mobSort, $mobStatus,
                        $autoPlaySpeedMobile, $autoRunTimeMobile, $startDate, $endDate, $status
                    ]
                );

                if ($insertId) {
                    $this->syncBanner($insertId, $title, $mobImg, $mobImg, $mobUrl, $mobSort, $mobStatus, $startDate, $endDate);
                    $createdCount++;
                }
            }
        }

        return $createdCount;
    }

    public function createSlider(array $post, array $files): int {
        $desktopImage = $this->uploadFile($files['desktop_image'] ?? [], 'desktop');
        $mobileImage  = $this->uploadFile($files['mobile_image'] ?? [], 'mobile');

        // Fallback if one image is provided
        if (empty($mobileImage) && !empty($desktopImage)) {
            $mobileImage = $desktopImage;
        } elseif (empty($desktopImage) && !empty($mobileImage)) {
            $desktopImage = $mobileImage;
        }

        $title            = trim($post['title'] ?? '');
        $desktopDesign    = trim($post['desktop_design'] ?? 'format_1');
        $desktopUrl       = trim($post['desktop_url'] ?? '');
        $mobileDesign     = trim($post['mobile_design'] ?? 'design_1');
        $mobileUrl        = trim($post['mobile_url'] ?? ($post['desktop_url'] ?? ''));
        $desktopSortOrder = (int)($post['desktop_sort_order'] ?? 0);
        $mobileSortOrder  = (int)($post['mobile_sort_order'] ?? 0);
        $status           = in_array($post['status'] ?? '', ['active', 'inactive']) ? $post['status'] : 'active';
        $autoPlaySpeed    = (int)($post['auto_play_speed'] ?? 3000);
        if ($autoPlaySpeed < 500) $autoPlaySpeed = 3000;
        $autoRunTime      = (int)ceil($autoPlaySpeed / 1000);
        $startDate        = !empty($post['start_date']) ? $post['start_date'] : null;
        $endDate          = !empty($post['end_date']) ? $post['end_date'] : null;

        $targetPages      = !empty($post['target_pages']) ? (is_array($post['target_pages']) ? implode(',', $post['target_pages']) : trim($post['target_pages'])) : 'home';
        $positionRelation = trim($post['position_relation'] ?? 'top');
        $targetSection    = trim($post['target_section'] ?? 'home_hero');
        $placementRules   = !empty($post['placement_rules']) ? (is_array($post['placement_rules']) ? json_encode($post['placement_rules']) : $post['placement_rules']) : null;

        $insertId = $this->db->insert(
            "INSERT INTO sliders 
             (title, slider_type, target_pages, position_relation, target_section, placement_rules,
              desktop_design, desktop_image, desktop_url, desktop_sort_order, desktop_status,
              mobile_design, mobile_image, mobile_url, mobile_sort_order, mobile_status,
              auto_play_speed, auto_run_time, start_date, end_date, status)
             VALUES (?, 'main', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $title, $targetPages, $positionRelation, $targetSection, $placementRules,
                $desktopDesign, $desktopImage, $desktopUrl, $desktopSortOrder, $status,
                $mobileDesign, $mobileImage, $mobileUrl, $mobileSortOrder, $status,
                $autoPlaySpeed, $autoRunTime, $startDate, $endDate, $status
            ]
        );

        // Sync to banners table with section_id = 1 (home_hero)
        $this->syncBanner($insertId, $title, $desktopImage, $mobileImage, $desktopUrl, $desktopSortOrder, $status, $startDate, $endDate);

        return $insertId;
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
        $desktopDesign    = trim($post['desktop_design'] ?? $existing['desktop_design']);
        $desktopUrl       = trim($post['desktop_url'] ?? $existing['desktop_url']);
        $mobileDesign     = trim($post['mobile_design'] ?? $existing['mobile_design']);
        $mobileUrl        = trim($post['mobile_url'] ?? $existing['mobile_url']);
        $desktopSortOrder = (int)($post['desktop_sort_order'] ?? $existing['desktop_sort_order']);
        $mobileSortOrder  = (int)($post['mobile_sort_order'] ?? $existing['mobile_sort_order']);
        $desktopStatus    = in_array($post['desktop_status'] ?? '', ['active', 'inactive']) ? $post['desktop_status'] : ($existing['desktop_status'] ?? 'active');
        $mobileStatus     = in_array($post['mobile_status'] ?? '', ['active', 'inactive']) ? $post['mobile_status'] : ($existing['mobile_status'] ?? 'active');
        $status           = in_array($post['status'] ?? '', ['active', 'inactive']) ? $post['status'] : $existing['status'];
        $autoPlaySpeed    = (int)($post['auto_play_speed'] ?? $existing['auto_play_speed'] ?? 3000);
        if ($autoPlaySpeed < 500) $autoPlaySpeed = 3000;
        $autoRunTime      = (int)ceil($autoPlaySpeed / 1000);
        $startDate        = !empty($post['start_date']) ? $post['start_date'] : null;
        $endDate          = !empty($post['end_date']) ? $post['end_date'] : null;

        $targetPages      = !empty($post['target_pages']) ? (is_array($post['target_pages']) ? implode(',', $post['target_pages']) : trim($post['target_pages'])) : ($existing['target_pages'] ?? 'home');
        $positionRelation = trim($post['position_relation'] ?? ($existing['position_relation'] ?? 'top'));
        $targetSection    = trim($post['target_section'] ?? ($existing['target_section'] ?? 'home_hero'));

        $pagePlacement = [];
        if (!empty($post['page_placement']) && is_array($post['page_placement'])) {
            foreach ($post['page_placement'] as $pKey => $pVal) {
                if (is_array($pVal)) {
                    $pos = in_array($pVal['position'] ?? '', ['after', 'before', 'top', 'bottom']) ? $pVal['position'] : 'top';
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

        $this->db->execute(
            "UPDATE sliders SET 
             title = ?, target_pages = ?, position_relation = ?, target_section = ?, placement_rules = ?,
             desktop_design = ?, desktop_image = ?, desktop_url = ?, desktop_sort_order = ?, desktop_status = ?,
             mobile_design = ?, mobile_image = ?, mobile_url = ?, mobile_sort_order = ?, mobile_status = ?,
             auto_play_speed = ?, auto_run_time = ?, start_date = ?, end_date = ?, status = ?
             WHERE id = ?",
            [
                $title, $targetPages, $positionRelation, $targetSection, $placementRules,
                $desktopDesign, $desktopImage, $desktopUrl, $desktopSortOrder, $desktopStatus,
                $mobileDesign, $mobileImage, $mobileUrl, $mobileSortOrder, $mobileStatus,
                $autoPlaySpeed, $autoRunTime, $startDate, $endDate, $status,
                $id
            ]
        );

        // Sync to banners table with section_id = 1 (home_hero)
        $this->syncBanner($id, $title, $desktopImage, $mobileImage, $desktopUrl, $desktopSortOrder, $status, $startDate, $endDate);

        return true;
    }

    public function deleteSlider(int $id): bool {
        $existing = $this->getSliderById($id);
        if (!$existing) return false;

        $this->db->execute("DELETE FROM sliders WHERE id = ?", [$id]);
        $this->db->execute("DELETE FROM banners WHERE section_id = 1 AND (title = ? OR target_url = ?)", [$existing['title'], $existing['desktop_url']]);
        return true;
    }

    public function toggleStatus(int $id): bool {
        $existing = $this->getSliderById($id);
        if (!$existing) return false;

        $newStatus = ($existing['status'] === 'active') ? 'inactive' : 'active';
        $this->db->execute("UPDATE sliders SET status = ?, desktop_status = ?, mobile_status = ? WHERE id = ?", [$newStatus, $newStatus, $newStatus, $id]);
        return true;
    }

    private function syncBanner(int $sliderId, string $title, string $desktopImage, string $mobileImage, string $url, int $order, string $status, ?string $startDate, ?string $endDate): void {
        // Find existing banner in section_id = 1
        $banner = $this->db->fetch("SELECT id FROM banners WHERE section_id = 1 AND display_order = ? LIMIT 1", [$order]);
        if ($banner) {
            $this->db->execute(
                "UPDATE banners SET 
                 title = ?, image_url_desktop = ?, image_url_mobile = ?, target_url = ?, 
                 display_order = ?, status = ?, start_date = ?, end_date = ? 
                 WHERE id = ?",
                [$title, $desktopImage, $mobileImage, $url, $order, $status, $startDate, $endDate, $banner['id']]
            );
        } else {
            $this->db->insert(
                "INSERT INTO banners 
                 (title, section_id, image_url_desktop, image_url_mobile, target_url, display_order, status, start_date, end_date) 
                 VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?)",
                [$title, $desktopImage, $mobileImage, $url, $order, $status, $startDate, $endDate]
            );
        }
    }

    private function uploadFile(array $file, string $type): string {
        if (empty($file) || empty($file['name']) || empty($file['tmp_name'])) {
            return '';
        }

        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif'];
        if (!in_array($ext, $allowed)) {
            return '';
        }

        $dir = __DIR__ . "/../uploads/banners/{$type}/";
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @chmod($dir, 0777);

        $filename = uniqid() . '_' . time() . '.' . $ext;
        $dest = $dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return "uploads/banners/{$type}/{$filename}";
        }

        return '';
    }
}
?>
