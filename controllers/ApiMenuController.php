<?php
require_once(__DIR__ . '/../models/ApiMenuModel.php');
require_once(__DIR__ . '/../config/database.php');

class ApiMenuController {
    private ApiMenuModel $model;

    public function __construct() {
        $this->model = new ApiMenuModel();
    }

    // ----------------------------------------------------------------
    // GET /api/menu_api.php?type=home                  → tree, both desktop + mobile
    // GET /api/menu_api.php?type=home&device=desktop    → tree, desktop only
    // GET /api/menu_api.php?type=home&device=mobile     → tree, mobile only
    // The FULL 3-level category tree (Main → Sub → Sub-Sub), same as
    // ?type=inner and the admin "All Menus" tree. Every node carries
    // shown_on_home so the caller can decide what to actually render —
    // nothing is pruned, so the tree shape is always visible even when
    // few categories are flagged.
    // ----------------------------------------------------------------
    public function getHomeMenu(): void {
        $device = strtolower(trim($_GET['device'] ?? ''));
        if (!in_array($device, ['desktop', 'mobile'], true)) {
            $device = '';
        }

        $flat = $this->model->getAllActiveFlat();

        if ($device === '') {
            $this->ok([
                'desktop' => $this->buildHomeTree($flat, 0, 'desktop'),
                'mobile'  => $this->buildHomeTree($flat, 0, 'mobile'),
            ], ['total_categories' => count($flat)]);
        } else {
            $tree = $this->buildHomeTree($flat, 0, $device);
            $this->ok($tree, ['device' => $device, 'total_categories' => count($flat)]);
        }
    }

    // ----------------------------------------------------------------
    // GET /api/menu_api.php?type=inner
    // The persistent top-menu / header navigation, as a 3-level tree.
    // Nodes hidden on BOTH desktop and mobile are pruned out entirely.
    // ----------------------------------------------------------------
    public function getInnerMenu(): void {
        $flat = $this->model->getAllActiveFlat();
        $tree = $this->buildMenuTree($flat, 0);
        $this->ok($tree, ['total_categories' => count($flat)]);
    }

    public function handleOptions(): void {
        $this->setHeaders();
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function buildMenuTree(array $categories, int $parentId): array {
        $branch = [];
        foreach ($categories as $c) {
            if ((int) $c['parent_id'] !== $parentId) continue;
            if (isset($c['status']) && ($c['status'] === 'inactive' || $c['status'] === 'disabled')) continue;
            if (isset($c['desktop_menu_status']) && isset($c['mobile_topbar_status'])) {
                if ($c['desktop_menu_status'] === 'hide' && $c['mobile_topbar_status'] === 'hide') continue;
            }

            $node = $this->formatMenuNode($c);
            $children = $this->buildMenuTree($categories, (int) $c['id']);
            if ($children) $node['children'] = $children;
            $branch[] = $node;
        }

        usort($branch, function($a, $b) {
            $orderA = ($a['desktop']['order'] > 0) ? $a['desktop']['order'] : (($a['display_order'] > 0) ? $a['display_order'] : 999999);
            $orderB = ($b['desktop']['order'] > 0) ? $b['desktop']['order'] : (($b['display_order'] > 0) ? $b['display_order'] : 999999);
            if ($orderA !== $orderB) return $orderA <=> $orderB;
            return strcmp($a['name'], $b['name']);
        });

        return $branch;
    }

    private function buildHomeTree(array $categories, int $parentId, string $device): array {
        $menuStatusCol = $device === 'mobile' ? 'mobile_topbar_status' : 'desktop_menu_status';
        $branch = [];
        foreach ($categories as $c) {
            if ((int) $c['parent_id'] !== $parentId) continue;
            if (isset($c['status']) && ($c['status'] === 'inactive' || $c['status'] === 'disabled')) continue;
            if (isset($c[$menuStatusCol]) && $c[$menuStatusCol] === 'hide') continue;

            $node = $this->formatHomeItem($c, $device);
            $node['shown_on_home'] = (isset($c[$menuStatusCol]) && $c[$menuStatusCol] === 'show');
            $children = $this->buildHomeTree($categories, (int) $c['id'], $device);
            if ($children) $node['children'] = $children;
            $branch[] = $node;
        }

        usort($branch, function($a, $b) {
            $orderA = ($a['order'] > 0) ? $a['order'] : (($a['display_order'] > 0) ? $a['display_order'] : 999999);
            $orderB = ($b['order'] > 0) ? $b['order'] : (($b['display_order'] > 0) ? $b['display_order'] : 999999);
            if ($orderA !== $orderB) return $orderA <=> $orderB;
            return strcmp($a['name'], $b['name']);
        });

        return $branch;
    }

    private function formatHomeItem(array $c, string $device): array {
        $img = fn($path) => $path ? (preg_match('/^https?:\/\//i', $path) ? $path : $this->baseUrl() . ltrim($path, '/')) : null;

        $desktopName = !empty($c['desktop_menu_title']) ? $c['desktop_menu_title'] : $c['name'];
        $mobileName  = !empty($c['mobile_topbar_title']) ? $c['mobile_topbar_title'] : (!empty($c['desktop_menu_title']) ? $c['desktop_menu_title'] : $c['name']);
        $displayName = ($device === 'mobile') ? $mobileName : $desktopName;

        $base = [
            'id'                  => (int) $c['id'],
            'name'                => $displayName,
            'category_name'       => $c['name'],
            'desktop_menu_title'  => $desktopName,
            'mobile_topbar_title' => $mobileName,
            'slug'                => $c['slug'],
            'level'               => (int) $c['level'],
            'display_order'       => (int) ($c['display_order'] ?? 0),
            'url'                 => !empty($c['custom_url']) ? $c['custom_url'] : ('/category/' . $c['slug']),
            'custom_url'          => $c['custom_url'] ?? null,
            'desktop' => [
                'title'               => $desktopName,
                'status'              => $c['desktop_menu_status'] ?? 'show',
                'order'               => (int) (($c['desktop_menu_order'] > 0) ? $c['desktop_menu_order'] : ($c['display_order'] ?? 0)),
                'image'               => $img($c['image'] ?? ''),
                'menu_image'          => $img($c['desktop_menu_image'] ?: ($c['image'] ?? '')),
            ],
            'mobile' => [
                'title'               => $mobileName,
                'status'              => $c['mobile_topbar_status'] ?? 'show',
                'order'               => (int) (($c['mobile_topbar_order'] > 0) ? $c['mobile_topbar_order'] : ($c['display_order'] ?? 0)),
                'image'               => $img($c['image'] ?? ''),
            ],
        ];

        if ($device === 'mobile') {
            return array_merge($base, [
                'name'                 => $mobileName,
                'order'                => (int) (($c['mobile_topbar_order'] > 0) ? $c['mobile_topbar_order'] : ($c['display_order'] ?? 0)),
                'image'                => $img($c['image'] ?? ''),
                'mobile_topbar_order'  => (int) ($c['mobile_topbar_order'] ?? 0),
                'mobile_sidebar_order' => (int) ($c['mobile_sidebar_order'] ?? 0),
                'status'               => $c['mobile_topbar_status'] ?? 'show',
            ]);
        }

        return array_merge($base, [
            'name'               => $desktopName,
            'order'              => (int) (($c['desktop_menu_order'] > 0) ? $c['desktop_menu_order'] : ($c['display_order'] ?? 0)),
            'image'              => $img($c['image'] ?? ''),
            'menu_image'         => $img($c['desktop_menu_image'] ?: ($c['image'] ?? '')),
            'desktop_menu_order' => (int) ($c['desktop_menu_order'] ?? 0),
            'status'             => $c['desktop_menu_status'] ?? 'show',
        ]);
    }

    private function formatMenuNode(array $c): array {
        $img = fn($path) => $path ? (preg_match('/^https?:\/\//i', $path) ? $path : $this->baseUrl() . ltrim($path, '/')) : null;

        $desktopName = !empty($c['desktop_menu_title']) ? $c['desktop_menu_title'] : $c['name'];
        $mobileName  = !empty($c['mobile_topbar_title']) ? $c['mobile_topbar_title'] : $desktopName;

        $banners = [];
        for ($i = 1; $i <= 3; $i++) {
            $colImg = ($i === 1) ? ($c['desktop_menu_image'] ?? null) : ($c["desktop_menu_image_{$i}"] ?? null);
            $colTitle = $c["desktop_menu_title_{$i}"] ?? null;
            $colUrl = $c["desktop_menu_url_{$i}"] ?? null;
            if (!empty($colImg)) {
                $banners[] = [
                    'image' => $img($colImg),
                    'title' => $colTitle ?: $desktopName,
                    'url'   => !empty($colUrl) ? $colUrl : (!empty($c['custom_url']) ? $c['custom_url'] : ('/category/' . $c['slug'])),
                ];
            }
        }

        return [
            'id'            => (int) $c['id'],
            'name'          => $desktopName,
            'category_name' => $c['name'],
            'slug'          => $c['slug'],
            'level'         => (int) $c['level'],
            'display_order' => (int) ($c['display_order'] ?? 0),
            'url'           => !empty($c['custom_url']) ? $c['custom_url'] : ('/category/' . $c['slug']),
            'custom_url'    => $c['custom_url'] ?? null,
            'image'         => $img($c['image'] ?? ''),
            'menu_image'    => $img(!empty($c['desktop_menu_image']) ? $c['desktop_menu_image'] : ($c['image'] ?? '')),
            'desktop_menu_image' => $img($c['desktop_menu_image'] ?? ''),
            'desktop_menu_image_2' => $img($c['desktop_menu_image_2'] ?? null),
            'desktop_menu_image_3' => $img($c['desktop_menu_image_3'] ?? null),
            'showcase_banners'   => $banners,
            'desktop' => [
                'title'               => $desktopName,
                'status'              => $c['desktop_menu_status'] ?? 'show',
                'order'               => (int) (($c['desktop_menu_order'] ?? 0) > 0 ? $c['desktop_menu_order'] : ($c['display_order'] ?? 0)),
                'view_design_enabled' => ($c['desktop_menu_view'] ?? '') === 'yes',
                'design'              => $c['desktop_menu_design'] ?? '',
                'tag'                 => $c['desktop_menu_tag'] ?? '',
                'image'               => $img($c['image'] ?? ''),
                'menu_image'          => $img(!empty($c['desktop_menu_image']) ? $c['desktop_menu_image'] : ($c['image'] ?? '')),
                'showcase_banners'    => $banners,
            ],
            'mobile' => [
                'title'               => $mobileName,
                'status'              => $c['mobile_topbar_status'] ?? 'show',
                'order'               => (int) (($c['mobile_topbar_order'] ?? 0) > 0 ? $c['mobile_topbar_order'] : ($c['display_order'] ?? 0)),
                'view_design_enabled' => ($c['mobile_menu_view'] ?? '') === 'yes',
                'design'              => $c['mobile_menu_design'] ?? '',
                'sidebar_order'       => (int) (($c['mobile_sidebar_order'] ?? 0) > 0 ? $c['mobile_sidebar_order'] : ($c['display_order'] ?? 0)),
                'image'               => $img($c['image'] ?? ''),
            ],
        ];
    }

    private function ok($data, array $meta = []): void {
        $this->setHeaders();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data'    => $data,
            'meta'    => array_merge(['timestamp' => date('c')], $meta),
        ]);
        exit;
    }

    private function error(string $message, int $code = 500): void {
        $this->setHeaders();
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }

    private function setHeaders(): void {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Cache-Control, Pragma');
        header('Access-Control-Max-Age: 86400');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private function baseUrl(): string {
        $proto = BASE_URL;
        return $proto;
    }
}
?>
