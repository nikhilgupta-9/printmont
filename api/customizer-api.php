<?php
// API for Visual Design Customizer Studio (Zazzle / Printo style online canvas editor)
header('Content-Type: application/json; charset=UTF-8');
require_once(__DIR__ . '/cors.php');

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/customizer-schema.php');

try {
    $database = new Database();
    $db = $database->getConnection();
    ensureCustomizerTables($db);

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isLocalHost = strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
    $protocol = (!$isLocalHost && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $scriptDir = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
    $apiPos = strpos($scriptDir, '/api');
    $basePath = $apiPos !== false ? substr($scriptDir, 0, $apiPos) : (isset($_SERVER['SCRIPT_NAME']) ? dirname(dirname($_SERVER['SCRIPT_NAME'])) : '/printmont/printmont-backend');
    $baseUrl = $protocol . "://" . $host . rtrim($basePath, '/') . "/";

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? ($method === 'POST' ? 'save_design' : 'get_template');

    // ─────────────────────────────────────────────────────────────
    // 1. GET TEMPLATE
    // ─────────────────────────────────────────────────────────────
    if ($action === 'get_template') {
        $productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
        $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
        $explicitKeyword = trim($_GET['keyword'] ?? '');

        $matchedTemplate = null;
        $productRow = null;

        if ($productId > 0) {
            $stmt = $db->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug 
                                   FROM products p 
                                   LEFT JOIN categories c ON p.category_id = c.id 
                                   WHERE p.id = ? LIMIT 1");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $productRow = $stmt->get_result()->fetch_assoc();

            if ($productRow && !empty($productRow['customizer_template_id'])) {
                $tId = (int) $productRow['customizer_template_id'];
                $tRes = $db->query("SELECT * FROM customizer_templates WHERE id = $tId AND status = 'active' LIMIT 1");
                if ($tRes && ($t = $tRes->fetch_assoc())) {
                    $matchedTemplate = $t;
                }
            }
        }

        // Automatic keyword matching based on product title or category name
        if (!$matchedTemplate) {
            $searchString = mb_strtolower($explicitKeyword);
            if ($productRow) {
                $searchString .= ' ' . mb_strtolower(($productRow['name'] ?? '') . ' ' . ($productRow['category_name'] ?? '') . ' ' . ($productRow['product_slug'] ?? ''));
            }

            $allTemplatesRes = $db->query("SELECT * FROM customizer_templates WHERE status = 'active' ORDER BY is_default ASC, display_order ASC");
            $allTemplates = [];
            while ($allTemplatesRes && ($t = $allTemplatesRes->fetch_assoc())) {
                $allTemplates[] = $t;
            }

            // Keyword mapping priorities
            $keywordsOrder = [
                'wall-art' => ['framed wall art', 'wall art', 'photo frame', 'photo frames', 'framed print', 'family wall'],
                't-shirt' => ['tshirt', 't-shirt', 't shirt', 'shirt', 'polo', 'hoodie', 'apparel', 'jersey', 'sweatshirt'],
                'mug'     => ['mug', 'cup', 'coffee mug', 'ceramic mug', 'tea cup'],
                'bottle'  => ['bottle', 'flask', 'sipper', 'water bottle', 'tumbler', 'shaker'],
                'pen'     => ['pen', 'ballpoint', 'rollerball', 'fountain pen'],
                'bag'     => ['bag', 'tote', 'backpack', 'duffle', 'jute bag', 'canvas bag'],
                'laptop'  => ['laptop', 'laptop skin', 'laptop sleeve', 'macbook'],
                'mobile'  => ['mobile', 'phone', 'case', 'cover', 'iphone', 'samsung'],
                'diary'   => ['diary', 'notebook', 'journal', 'planner', 'organizer', 'notepad']
            ];

            foreach ($keywordsOrder as $key => $synonyms) {
                foreach ($synonyms as $syn) {
                    if (strpos($searchString, $syn) !== false) {
                        foreach ($allTemplates as $tpl) {
                            if ($tpl['category_keyword'] === $key) {
                                $matchedTemplate = $tpl;
                                break 2;
                            }
                        }
                    }
                }
            }

            // Fallback to default
            if (!$matchedTemplate) {
                foreach ($allTemplates as $tpl) {
                    if (!empty($tpl['is_default'])) {
                        $matchedTemplate = $tpl;
                        break;
                    }
                }
                if (!$matchedTemplate && !empty($allTemplates)) {
                    $matchedTemplate = $allTemplates[0];
                }
            }
        }

        // Parse views config
        $views = json_decode($matchedTemplate['views_config'] ?? '[]', true) ?: [];

        // Cliparts
        $clipartsRes = $db->query("SELECT id, category, name, svg_content, image_url FROM customizer_cliparts WHERE status = 'active' ORDER BY display_order ASC");
        $cliparts = [];
        while ($clipartsRes && ($c = $clipartsRes->fetch_assoc())) {
            $cliparts[] = [
                'id'       => (int) $c['id'],
                'category' => $c['category'],
                'name'     => $c['name'],
                'svg'      => $c['svg_content'],
                'image_url'=> $c['image_url'] ? ((strpos($c['image_url'], 'http') === 0) ? $c['image_url'] : $baseUrl . ltrim($c['image_url'], '/')) : null
            ];
        }

        // Curated font collection with web font families
        $fonts = [
            ['family' => 'Inter', 'category' => 'sans-serif', 'label' => 'Inter (Modern Standard)'],
            ['family' => 'Roboto', 'category' => 'sans-serif', 'label' => 'Roboto (Clean Sans)'],
            ['family' => 'Poppins', 'category' => 'sans-serif', 'label' => 'Poppins (Geometric Bold)'],
            ['family' => 'Montserrat', 'category' => 'sans-serif', 'label' => 'Montserrat (Brand Pro)'],
            ['family' => 'Playfair Display', 'category' => 'serif', 'label' => 'Playfair Display (Luxury Serif)'],
            ['family' => 'Bebas Neue', 'category' => 'display', 'label' => 'Bebas Neue (Impact All-Caps)'],
            ['family' => 'Pacifico', 'category' => 'handwriting', 'label' => 'Pacifico (Brush Script)'],
            ['family' => 'Oswald', 'category' => 'sans-serif', 'label' => 'Oswald (Compact Bold)'],
            ['family' => 'Dancing Script', 'category' => 'handwriting', 'label' => 'Dancing Script (Elegant Cursive)'],
            ['family' => 'Courier Prime', 'category' => 'monospace', 'label' => 'Courier Prime (Monospace)']
        ];

        // All available templates for switching in customizer
        $allTemplatesList = [];
        $tListRes = $db->query("SELECT id, title, category_keyword, is_default FROM customizer_templates WHERE status = 'active' ORDER BY display_order ASC");
        while ($tListRes && ($row = $tListRes->fetch_assoc())) {
            $allTemplatesList[] = [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'keyword' => $row['category_keyword']
            ];
        }

        echo json_encode([
            'success'   => true,
            'template'  => [
                'id'               => (int) ($matchedTemplate['id'] ?? 0),
                'title'            => $matchedTemplate['title'] ?? 'Customizer Template',
                'category_keyword' => $matchedTemplate['category_keyword'] ?? 'general',
                'description'      => $matchedTemplate['description'] ?? '',
                'dpi_guide'        => $matchedTemplate['dpi_guide'] ?? '',
                'allowed_tools'    => explode(',', $matchedTemplate['allowed_tools'] ?? 'text,image,clipart,shapes,rotate,arc'),
                'views'            => $views
            ],
            'available_templates' => $allTemplatesList,
            'cliparts'  => $cliparts,
            'fonts'     => $fonts,
            'product'   => $productRow ? [
                'id'       => (int) $productRow['id'],
                'name'     => $productRow['name'],
                'price'    => (float) ($productRow['offer_price'] ?? $productRow['price'] ?? 0),
                'color'    => $productRow['color'] ?? '',
                'thumbnail'=> $productRow['thumbnail_image'] ?? $productRow['image'] ?? ''
            ] : null
        ]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // 2. UPLOAD LOGO / ARTWORK FILE
    // ─────────────────────────────────────────────────────────────
    if ($action === 'upload_logo') {
        if (empty($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            exit;
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Upload error code: ' . $file['error']]);
            exit;
        }

        $allowedExts = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Allowed formats: PNG, JPG, JPEG, SVG, WEBP']);
            exit;
        }

        if ($file['size'] > 15 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'File size cannot exceed 15MB']);
            exit;
        }

        $targetDir = __DIR__ . '/../uploads/customizer/logos/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $targetDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
            exit;
        }

        $fullUrl = $baseUrl . 'uploads/customizer/logos/' . $fileName;
        echo json_encode([
            'success'   => true,
            'file_name' => $file['name'],
            'url'       => $fullUrl
        ]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // 3. SAVE DESIGN (SNAPSHOT + LAYERS DATA)
    // ─────────────────────────────────────────────────────────────
    if ($action === 'save_design') {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;

        $previewBase64 = $payload['preview_image'] ?? '';
        $designLayers  = $payload['design_data'] ?? [];
        $productId     = (int) ($payload['product_id'] ?? 0);

        $previewUrl = '';

        if (!empty($previewBase64) && strpos($previewBase64, 'data:image') === 0) {
            $dir = __DIR__ . '/../uploads/customizer/previews/';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            // Extract base64 part
            $parts = explode(',', $previewBase64, 2);
            if (count($parts) === 2) {
                $decoded = base64_decode($parts[1]);
                if ($decoded !== false) {
                    $snapFile = 'design_' . ($productId ?: 'custom') . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.png';
                    file_put_contents($dir . $snapFile, $decoded);
                    $previewUrl = $baseUrl . 'uploads/customizer/previews/' . $snapFile;
                }
            }
        } elseif (!empty($payload['preview_url'])) {
            $previewUrl = $payload['preview_url'];
        }

        // Summary strings of customer design for print vendor
        $textSummary = [];
        $imageSummary = [];
        if (is_array($designLayers)) {
            foreach ($designLayers as $sideKey => $sideData) {
                if (!empty($sideData['layers']) && is_array($sideData['layers'])) {
                    foreach ($sideData['layers'] as $layer) {
                        if (($layer['type'] ?? '') === 'text' && !empty($layer['text'])) {
                            $textSummary[] = $layer['text'] . ' (' . ($layer['font'] ?? 'Inter') . ')';
                        } elseif (($layer['type'] ?? '') === 'image' && !empty($layer['url'])) {
                            $imageSummary[] = $layer['url'];
                        }
                    }
                }
            }
        }

        echo json_encode([
            'success'       => true,
            'preview_url'   => $previewUrl,
            'design_data'   => [
                'preview_url'   => $previewUrl,
                'sides'         => $designLayers,
                'texts'         => $textSummary,
                'images'        => $imageSummary,
                'saved_at'      => date('Y-m-d H:i:s')
            ]
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
