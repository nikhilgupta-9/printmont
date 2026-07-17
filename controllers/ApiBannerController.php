<?php
require_once(__DIR__ . '/../models/ApiBannerModel.php');

class ApiBannerController {
    private ApiBannerModel $model;

    public function __construct() {
        $this->model = new ApiBannerModel();
    }

    // ----------------------------------------------------------------
    // GET /api/banner_api.php?page=home
    // Returns ALL sections + their banners for a given page in one call.
    // ----------------------------------------------------------------
    public function getByPage(): void {
        $page = trim($_GET['page'] ?? '');
        if ($page === '') {
            $this->error('page parameter is required', 400);
        }

        $data = $this->model->getByPage($page);
        $this->ok([
            'page'     => $page,
            'sections' => $this->formatSections($data),
        ], ['total_sections' => count($data)]);
    }

    // ----------------------------------------------------------------
    // GET /api/banner_api.php?section=home_hero
    // Returns one section with its banners.
    // ----------------------------------------------------------------
    public function getBySection(): void {
        $key = trim($_GET['section'] ?? '');
        if ($key === '') {
            $this->error('section parameter is required', 400);
        }

        $data = $this->model->getBySectionKey($key);
        if (empty($data)) {
            $this->error('Section not found or has no active banners', 404);
        }

        $this->ok($this->formatSection($data));
    }

    // ----------------------------------------------------------------
    // GET /api/banner_api.php?action=sections
    // Returns full list of sections (for admin dropdowns).
    // ----------------------------------------------------------------
    public function getSectionsList(): void {
        $sections = $this->model->getAllSections();
        $this->ok($sections, ['total' => count($sections)]);
    }

    public function handleOptions(): void {
        $this->setHeaders();
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function formatSections(array $sections): array {
        $baseUrl = $this->baseUrl();
        $out = [];
        foreach ($sections as $key => $section) {
            $out[$key] = $this->formatSection(array_merge(['section_key' => $key], $section), $baseUrl);
        }
        return $out;
    }

    private function formatSection(array $section, string $baseUrl = ''): array {
        if ($baseUrl === '') $baseUrl = $this->baseUrl();
        return [
            'section_key'     => $section['section_key'],
            'label'           => $section['label'],
            'columns_per_row' => $section['columns_per_row'],
            'is_slider'       => $section['is_slider'],
            'banners'         => array_map(fn($b) => $this->formatBanner($b, $baseUrl), $section['banners']),
        ];
    }

    private function formatBanner(array $b, string $baseUrl): array {
        return [
            'id'            => (int) $b['id'],
            'title'         => $b['title'],
            'description'   => $b['description'],
            'images'        => [
                'desktop' => $baseUrl . $b['image_url_desktop'],
                'mobile'  => $baseUrl . $b['image_url_mobile'],
            ],
            'target_url'    => $b['target_url'],
            'display_order' => (int) $b['display_order'],
        ];
    }

    private function ok(array $data, array $meta = []): void {
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
