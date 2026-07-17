<?php
require_once(__DIR__ . '/../models/ApiCategoryModel.php');

class ApiCategoryController {
    private ApiCategoryModel $model;

    public function __construct() {
        $this->model = new ApiCategoryModel();
    }

    // ----------------------------------------------------------------
    // GET /api/category_api.php?action=categories
    // Main categories (level 1).
    // ----------------------------------------------------------------
    public function getCategories(): void {
        $rows = $this->model->getMainCategories();
        $this->ok(array_map([$this, 'formatCategory'], $rows), ['total' => count($rows)]);
    }

    // ----------------------------------------------------------------
    // GET /api/category_api.php?action=subcategories&parent_id=14
    // Sub categories (level 2), optionally filtered by parent_id.
    // ----------------------------------------------------------------
    public function getSubCategories(): void {
        $parentId = isset($_GET['parent_id']) ? (int) $_GET['parent_id'] : null;
        $rows = $this->model->getSubCategories($parentId);
        $this->ok(array_map([$this, 'formatCategory'], $rows), ['total' => count($rows), 'parent_id' => $parentId]);
    }

    // ----------------------------------------------------------------
    // GET /api/category_api.php?action=subsubcategories&parent_id=75
    // Sub-sub categories (level 3), optionally filtered by parent_id.
    // ----------------------------------------------------------------
    public function getSubSubCategories(): void {
        $parentId = isset($_GET['parent_id']) ? (int) $_GET['parent_id'] : null;
        $rows = $this->model->getSubSubCategories($parentId);
        $this->ok(array_map([$this, 'formatCategory'], $rows), ['total' => count($rows), 'parent_id' => $parentId]);
    }

    // ----------------------------------------------------------------
    // GET /api/category_api.php?action=tree
    // Full 3-level hierarchy in one call, one DB query.
    // ----------------------------------------------------------------
    public function getTree(): void {
        $flat = $this->model->getAllActiveFlat();
        $tree = $this->buildTree($flat, 0);
        $this->ok($tree, ['total' => count($flat)]);
    }

    // ----------------------------------------------------------------
    // GET /api/category_api.php?id=14
    // GET /api/category_api.php?slug=electronics
    // Single category by id or slug.
    // ----------------------------------------------------------------
    public function getOne(): void {
        $id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $slug = trim($_GET['slug'] ?? '');

        $row = $id ? $this->model->getById($id) : ($slug !== '' ? $this->model->getBySlug($slug) : null);

        if (!$row) {
            $this->error('Category not found', 404);
        }

        $this->ok($this->formatCategory($row));
    }

    public function handleOptions(): void {
        $this->setHeaders();
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function buildTree(array $categories, int $parentId): array {
        $branch = [];
        foreach ($categories as $category) {
            if ((int) $category['parent_id'] === $parentId) {
                $node = $this->formatCategory($category);
                $children = $this->buildTree($categories, (int) $category['id']);
                if ($children) {
                    $node['children'] = $children;
                }
                $branch[] = $node;
            }
        }
        return $branch;
    }

    private function formatCategory(array $c, string $baseUrl = ''): array {
        if ($baseUrl === '') $baseUrl = $this->baseUrl();
        $img = fn($path) => $path ? $baseUrl . ltrim($path, '/') : null;

        return [
            'id'            => (int) $c['id'],
            'name'          => $c['name'],
            'slug'          => $c['slug'],
            'description'   => $c['description'],
            'level'         => (int) $c['level'],
            'parent_id'     => (int) $c['parent_id'],
            'status'        => $c['status'],
            'display_order' => (int) $c['display_order'],
            'is_featured'   => (bool) $c['is_featured'],
            'icon'          => $c['icon'],
            'images' => [
                'image'      => $img($c['image']),
                'desktop'    => $img($c['desktop_image']),
                'desktop_bg' => $img($c['desktop_bg_image']),
                'mobile'     => $img($c['mobile_image']),
                'mobile_bg'  => $img($c['mobile_bg_image']),
            ],
            'desktop_menu' => [
                'status'              => $c['desktop_menu_status'],
                'order'               => (int) $c['desktop_menu_order'],
                'view_design_enabled' => $c['desktop_menu_view'] === 'yes',
                'design'              => $c['desktop_menu_design'],
                'tag'                 => $c['desktop_menu_tag'],
                'image'               => $img($c['desktop_menu_image']),
            ],
            'mobile_menu' => [
                'topbar_status'       => $c['mobile_topbar_status'],
                'topbar_order'        => (int) $c['mobile_topbar_order'],
                'view_design_enabled' => $c['mobile_menu_view'] === 'yes',
                'design'              => $c['mobile_menu_design'],
                'sidebar_order'       => (int) $c['mobile_sidebar_order'],
            ],
            'desktop_home' => [
                'show'     => $c['desktop_home_show'] === 'yes',
                'design'   => $c['desktop_home_design'],
                'order'    => (int) $c['desktop_home_order'],
                'bg_color' => $c['desktop_bg_color'],
            ],
            'mobile_home' => [
                'show'     => $c['mobile_home_show'] === 'yes',
                'design'   => $c['mobile_home_design'],
                'format'   => $c['mobile_home_format'],
                'order'    => (int) $c['mobile_home_order'],
                'bg_color' => $c['mobile_bg_color'],
            ],
            'seo' => [
                'meta_title'       => $c['meta_title'],
                'meta_keywords'    => $c['meta_keywords'],
                'meta_description' => $c['meta_description'],
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
        return BASE_URL;
    }
}
?>
