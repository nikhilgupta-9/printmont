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
        return $branch;
    }

    private function buildHomeTree(array $categories, int $parentId, string $device): array {
        $showCol = $device === 'mobile' ? 'mobile_home_show' : 'desktop_home_show';
        $menuStatusCol = $device === 'mobile' ? 'mobile_topbar_status' : 'desktop_menu_status';
        $branch = [];
        foreach ($categories as $c) {
            if ((int) $c['parent_id'] !== $parentId) continue;
            if (isset($c['status']) && ($c['status'] === 'inactive' || $c['status'] === 'disabled')) continue;
            if (isset($c[$menuStatusCol]) && $c[$menuStatusCol] === 'hide') continue;

            $node = $this->formatHomeItem($c, $device);
            $node['shown_on_home'] = isset($c[$showCol]) && $c[$showCol] === 'yes';
            $children = $this->buildHomeTree($categories, (int) $c['id'], $device);
            if ($children) $node['children'] = $children;
            $branch[] = $node;
        }
        return $branch;
    }

    private function formatHomeItem(array $c, string $device): array {
        $img = fn($path) => $path ? $this->baseUrl() . ltrim($path, '/') : null;

        $base = [
            'id'    => (int) $c['id'],
            'name'  => $c['name'],
            'slug'  => $c['slug'],
            'level' => (int) $c['level'],
            'url'   => '/category/' . $c['slug'],
        ];

        if ($device === 'mobile') {
            return $base + [
                'design'   => $c['mobile_home_design'],
                'format'   => $c['mobile_home_format'],
                'order'    => (int) $c['mobile_home_order'],
                'bg_color' => $c['mobile_bg_color'],
                'image'    => $img($c['mobile_image'] ?: $c['image']),
                'bg_image' => $img($c['mobile_bg_image']),
            ];
        }

        return $base + [
            'design'   => $c['desktop_home_design'],
            'order'    => (int) $c['desktop_home_order'],
            'bg_color' => $c['desktop_bg_color'],
            'image'    => $img($c['desktop_image'] ?: $c['image']),
            'bg_image' => $img($c['desktop_bg_image']),
        ];
    }

    private function formatMenuNode(array $c): array {
        $img = fn($path) => $path ? $this->baseUrl() . ltrim($path, '/') : null;

        return [
            'id'    => (int) $c['id'],
            'name'  => $c['name'],
            'slug'  => $c['slug'],
            'level' => (int) $c['level'],
            'url'   => '/category/' . $c['slug'],
            'desktop' => [
                'status'              => $c['desktop_menu_status'],
                'order'               => (int) $c['desktop_menu_order'],
                'view_design_enabled' => $c['desktop_menu_view'] === 'yes',
                'design'              => $c['desktop_menu_design'],
                'tag'                 => $c['desktop_menu_tag'],
                'image'               => $img($c['desktop_menu_image']),
            ],
            'mobile' => [
                'status'              => $c['mobile_topbar_status'],
                'order'               => (int) $c['mobile_topbar_order'],
                'view_design_enabled' => $c['mobile_menu_view'] === 'yes',
                'design'              => $c['mobile_menu_design'],
                'sidebar_order'       => (int) $c['mobile_sidebar_order'],
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
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    private function baseUrl(): string {
        $proto = BASE_URL;
        return $proto;
    }
}
?>
