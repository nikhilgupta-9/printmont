<?php
require_once(__DIR__ . '/../models/ApiMenuModel.php');
require_once(__DIR__ . '/../config/database.php');

class ApiMenuController {
    private ApiMenuModel $model;

    public function __construct() {
        $this->model = new ApiMenuModel();
    }

    // ----------------------------------------------------------------
    // GET /api/menu_api.php?type=home                  → both desktop + mobile
    // GET /api/menu_api.php?type=home&device=desktop    → desktop only
    // GET /api/menu_api.php?type=home&device=mobile     → mobile only
    // Categories configured to appear ON the home page.
    // ----------------------------------------------------------------
    public function getHomeMenu(): void {
        $device = strtolower(trim($_GET['device'] ?? ''));
        if (!in_array($device, ['desktop', 'mobile'], true)) {
            $device = '';
        }

        if ($device === '') {
            $desktop = $this->model->getHomeMenu('desktop');
            $mobile  = $this->model->getHomeMenu('mobile');
            $this->ok([
                'desktop' => array_map(fn($c) => $this->formatHomeItem($c, 'desktop'), $desktop),
                'mobile'  => array_map(fn($c) => $this->formatHomeItem($c, 'mobile'), $mobile),
            ], ['desktop_total' => count($desktop), 'mobile_total' => count($mobile)]);
        } else {
            $rows = $this->model->getHomeMenu($device);
            $this->ok(array_map(fn($c) => $this->formatHomeItem($c, $device), $rows), ['device' => $device, 'total' => count($rows)]);
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
            if ($c['desktop_menu_status'] === 'hide' && $c['mobile_topbar_status'] === 'hide') continue;

            $node = $this->formatMenuNode($c);
            $children = $this->buildMenuTree($categories, (int) $c['id']);
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
