<?php
// Visual Design Customizer Studio Schema & Starter Templates
// Controls the interactive Zazzle/Canva-style designer for corporate gifting products.

if (!function_exists('ensureCustomizerTables')) {
    function ensureCustomizerTables($db) {
        static $done = false;
        if ($done) return;

        // Customizer templates table
        $db->query("CREATE TABLE IF NOT EXISTS customizer_templates (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            category_keyword VARCHAR(100) NOT NULL,
            description VARCHAR(255) NULL,
            views_config LONGTEXT NOT NULL,
            allowed_tools VARCHAR(255) NOT NULL DEFAULT 'text,image,clipart,shapes,rotate,arc',
            dpi_guide VARCHAR(255) NULL DEFAULT 'Recommended 300 DPI transparent PNG or high-res JPG',
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            display_order INT(11) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_ct_keyword (category_keyword, status),
            KEY idx_ct_status (status, display_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Customizer cliparts & corporate icons table
        $db->query("CREATE TABLE IF NOT EXISTS customizer_cliparts (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(100) NOT NULL DEFAULT 'Corporate',
            name VARCHAR(100) NOT NULL,
            svg_content LONGTEXT NULL,
            image_url VARCHAR(500) NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            display_order INT(11) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_cc_cat (category, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Check if products table has customizer columns
        $chkCol = $db->query("SHOW COLUMNS FROM products LIKE 'customizer_template_id'");
        if ($chkCol && $chkCol->num_rows === 0) {
            $db->query("ALTER TABLE products ADD COLUMN customizer_template_id INT(11) NULL DEFAULT NULL AFTER customization_label");
            $db->query("ALTER TABLE products ADD COLUMN enable_customizer TINYINT(1) NOT NULL DEFAULT 1 AFTER customizer_template_id");
        }

        // Seed starter templates if table is empty
        $cnt = $db->query("SELECT COUNT(*) AS c FROM customizer_templates");
        $row = $cnt ? $cnt->fetch_assoc() : null;
        if (($row['c'] ?? 0) == 0) {
            seedStarterCustomizerTemplates($db);
        }

        // Keep the framed wall art canvas available on existing installations too.
        $wallArt = $db->query("SELECT id FROM customizer_templates WHERE category_keyword = 'wall-art' LIMIT 1");
        if ($wallArt && $wallArt->num_rows === 0) {
            $views = json_encode([[
                'id' => 'front', 'name' => 'Front View', 'mockup_url' => '/mockups/framed_wall_art.svg',
                'print_area' => ['x' => 18, 'y' => 16, 'width' => 64, 'height' => 68, 'shape' => 'rect'], 'tintable' => false
            ]]);
            $title = 'Framed Wall Art';
            $keyword = 'wall-art';
            $description = 'Portrait framed print with a large editable photo and artwork area.';
            $tools = 'text,image,clipart,shapes,rotate,arc';
            $dpi = 'Use a high resolution image (300 DPI recommended) for sharp printing.';
            $order = 9;
            $stmt = $db->prepare("INSERT INTO customizer_templates (title, category_keyword, description, views_config, allowed_tools, dpi_guide, is_default, display_order) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
            $stmt->bind_param('ssssssi', $title, $keyword, $description, $views, $tools, $dpi, $order);
            $stmt->execute();
        }

        // Seed starter cliparts if table is empty
        $cntClip = $db->query("SELECT COUNT(*) AS c FROM customizer_cliparts");
        $rowClip = $cntClip ? $cntClip->fetch_assoc() : null;
        if (($rowClip['c'] ?? 0) == 0) {
            seedStarterCustomizerCliparts($db);
        }

        $done = true;
    }
}

if (!function_exists('seedStarterCustomizerTemplates')) {
    function seedStarterCustomizerTemplates($db) {
        $templates = [
            [
                'title' => 'Custom Apparel & T-Shirts',
                'category_keyword' => 't-shirt',
                'description' => 'Round neck, polo, hoodies & corporate uniforms with front chest & back print zones.',
                'views_config' => json_encode([
                    [
                        'id' => 'front',
                        'name' => 'Front View',
                        'mockup_url' => '/mockups/tshirt_front.svg',
                        'print_area' => ['x' => 30, 'y' => 24, 'width' => 40, 'height' => 48, 'shape' => 'rect'],
                        'tintable' => true
                    ],
                    [
                        'id' => 'back',
                        'name' => 'Back View',
                        'mockup_url' => '/mockups/tshirt_back.svg',
                        'print_area' => ['x' => 30, 'y' => 22, 'width' => 40, 'height' => 50, 'shape' => 'rect'],
                        'tintable' => true
                    ]
                ]),
                'allowed_tools' => 'text,image,clipart,shapes,rotate,arc',
                'dpi_guide' => 'Ideal print size: 3000 x 3600 px (300 DPI). Transparent PNG recommended.',
                'is_default' => 0,
                'display_order' => 1
            ],
            [
                'title' => 'Ceramic Mugs & Cups',
                'category_keyword' => 'mug',
                'description' => '11oz / 15oz ceramic coffee mugs with front facing and full wrap-around print zones.',
                'views_config' => json_encode([
                    [
                        'id' => 'front',
                        'name' => 'Front Side',
                        'mockup_url' => '/mockups/mug_front.svg',
                        'print_area' => ['x' => 24, 'y' => 24, 'width' => 42, 'height' => 50, 'shape' => 'rect'],
                        'tintable' => false
                    ],
                    [
                        'id' => 'wrap',
                        'name' => 'Full Wrap View',
                        'mockup_url' => '/mockups/mug_wrap.svg',
                        'print_area' => ['x' => 10, 'y' => 22, 'width' => 80, 'height' => 54, 'shape' => 'rect'],
                        'tintable' => false
                    ]
                ]),
                'allowed_tools' => 'text,image,clipart,shapes,rotate,arc',
                'dpi_guide' => 'Wrap print area: 2400 x 950 px (300 DPI). Full edge wrap supported.',
                'is_default' => 0,
                'display_order' => 2
            ],
            [
                'title' => 'Stainless Steel Water Bottles & Sippers',
                'category_keyword' => 'bottle',
                'description' => 'Insulated gym bottles, flasks & steel sippers with vertical center branding.',
                'views_config' => json_encode([
                    [
                        'id' => 'front',
                        'name' => 'Front Center',
                        'mockup_url' => '/mockups/bottle_front.svg',
                        'print_area' => ['x' => 36, 'y' => 32, 'width' => 28, 'height' => 46, 'shape' => 'cylinder'],
                        'tintable' => true
                    ]
                ]),
                'allowed_tools' => 'text,image,clipart,shapes,rotate,arc',
                'dpi_guide' => 'UV & Laser Engraving. Single color vector or high-contrast PNG.',
                'is_default' => 0,
                'display_order' => 3
            ],
            [
                'title' => 'Corporate Executive Pens',
                'category_keyword' => 'pen',
                'description' => 'Metal ballpoint and rollerball pens with laser engraving and screen print strip.',
                'views_config' => json_encode([
                    [
                        'id' => 'barrel',
                        'name' => 'Barrel Print Strip',
                        'mockup_url' => '/mockups/pen_mockup.svg',
                        'print_area' => ['x' => 22, 'y' => 40, 'width' => 56, 'height' => 18, 'shape' => 'rect'],
                        'tintable' => true
                    ]
                ]),
                'allowed_tools' => 'text,image,clipart,rotate',
                'dpi_guide' => 'Max print height 8mm. Clear vector logo or high-contrast text works best.',
                'is_default' => 0,
                'display_order' => 4
            ],
            [
                'title' => 'Canvas Tote Bags & Backpacks',
                'category_keyword' => 'bag',
                'description' => 'Eco-friendly cotton tote bags, laptop bags & backpacks with large center branding.',
                'views_config' => json_encode([
                    [
                        'id' => 'front',
                        'name' => 'Front Center',
                        'mockup_url' => '/mockups/bag_mockup.svg',
                        'print_area' => ['x' => 27, 'y' => 38, 'width' => 46, 'height' => 44, 'shape' => 'rect'],
                        'tintable' => true
                    ]
                ]),
                'allowed_tools' => 'text,image,clipart,shapes,rotate,arc',
                'dpi_guide' => 'Screen & Digital Print. Up to 10 x 10 inches print box.',
                'is_default' => 0,
                'display_order' => 5
            ],
            [
                'title' => 'Laptop Skins & Sleeves',
                'category_keyword' => 'laptop',
                'description' => 'Vinyl laptop skins (13\" / 15.6\") and neoprene padded sleeves.',
                'views_config' => json_encode([
                    [
                        'id' => 'top',
                        'name' => 'Top Surface',
                        'mockup_url' => '/mockups/laptop_mockup.svg',
                        'print_area' => ['x' => 18, 'y' => 14, 'width' => 64, 'height' => 72, 'shape' => 'rect'],
                        'tintable' => false
                    ]
                ]),
                'allowed_tools' => 'text,image,clipart,shapes,rotate,arc',
                'dpi_guide' => 'High resolution full surface art. 3800 x 2400 px.',
                'is_default' => 0,
                'display_order' => 6
            ],
            [
                'title' => 'Mobile Covers & Phone Cases',
                'category_keyword' => 'mobile',
                'description' => 'Hard matte and glass phone covers with camera cut-out safe zone.',
                'views_config' => json_encode([
                    [
                        'id' => 'back',
                        'name' => 'Back Case',
                        'mockup_url' => '/mockups/mobile_cover_mockup.svg',
                        'print_area' => ['x' => 25, 'y' => 12, 'width' => 50, 'height' => 76, 'shape' => 'phone_case'],
                        'tintable' => false
                    ]
                ]),
                'allowed_tools' => 'text,image,clipart,shapes,rotate,arc',
                'dpi_guide' => 'Full wrap 3D sublimation. Keep logos away from top-left camera cutout.',
                'is_default' => 0,
                'display_order' => 7
            ],
            [
                'title' => 'Executive Diaries & Notebooks',
                'category_keyword' => 'diary',
                'description' => 'Hardcover corporate diaries, journals and organizers with foil/deboss print area.',
                'views_config' => json_encode([
                    [
                        'id' => 'cover',
                        'name' => 'Front Hard Cover',
                        'mockup_url' => '/mockups/diary_mockup.svg',
                        'print_area' => ['x' => 24, 'y' => 16, 'width' => 52, 'height' => 68, 'shape' => 'rect'],
                        'tintable' => true
                    ]
                ]),
                'allowed_tools' => 'text,image,clipart,shapes,rotate,arc',
                'dpi_guide' => 'Embossing, Foil Stamping, or UV Color Print.',
                'is_default' => 0,
                'display_order' => 8
            ],
            [
                'title' => 'Universal Corporate Gift Template',
                'category_keyword' => 'general',
                'description' => 'Universal customizable canvas adaptable for any gift item or accessory.',
                'views_config' => json_encode([
                    [
                        'id' => 'front',
                        'name' => 'Product Front',
                        'mockup_url' => '/mockups/general_gift.svg',
                        'print_area' => ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60, 'shape' => 'rect'],
                        'tintable' => false
                    ]
                ]),
                'allowed_tools' => 'text,image,clipart,shapes,rotate,arc',
                'dpi_guide' => 'High resolution transparent PNG (300 DPI) works best.',
                'is_default' => 1,
                'display_order' => 99
            ]
        ];

        $stmt = $db->prepare("INSERT INTO customizer_templates (title, category_keyword, description, views_config, allowed_tools, dpi_guide, is_default, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($templates as $t) {
            $stmt->bind_param("ssssssii", $t['title'], $t['category_keyword'], $t['description'], $t['views_config'], $t['allowed_tools'], $t['dpi_guide'], $t['is_default'], $t['display_order']);
            $stmt->execute();
        }
    }
}

if (!function_exists('seedStarterCustomizerCliparts')) {
    function seedStarterCustomizerCliparts($db) {
        $cliparts = [
            // Corporate & Badges
            ['Corporate', 'Verified Star Badge', '<svg viewBox="0 0 100 100" fill="currentColor"><polygon points="50,5 64,35 97,38 72,61 80,94 50,77 20,94 28,61 3,38 36,35"/></svg>'],
            ['Corporate', 'Trophy of Excellence', '<svg viewBox="0 0 100 100" fill="currentColor"><path d="M25,20 L75,20 L75,40 C75,55 60,65 50,65 C40,65 25,55 25,40 Z M20,25 C10,25 5,35 15,45 C20,50 25,50 25,45 Z M80,25 C90,25 95,35 85,45 C80,50 75,50 75,45 Z M45,65 L45,80 L35,80 L35,88 L65,88 L65,80 L55,80 L55,65 Z"/></svg>'],
            ['Corporate', 'Shield of Trust', '<svg viewBox="0 0 100 100" fill="currentColor"><path d="M50,10 L85,25 L85,55 C85,75 50,92 50,92 C50,92 15,75 15,55 L15,25 Z M42,65 L70,37 L63,30 L42,51 L32,41 L25,48 Z"/></svg>'],
            ['Corporate', 'Award Ribbon', '<svg viewBox="0 0 100 100" fill="currentColor"><circle cx="50" cy="40" r="28"/><polygon points="35,62 30,92 48,82 52,82 70,92 65,62"/></svg>'],
            ['Corporate', 'Crown Leader', '<svg viewBox="0 0 100 100" fill="currentColor"><path d="M15,75 L85,75 L80,35 L62,55 L50,22 L38,55 L20,35 Z M15,80 L85,80 L85,86 L15,86 Z"/></svg>'],
            ['Corporate', 'Thumbs Up Approval', '<svg viewBox="0 0 100 100" fill="currentColor"><path d="M20,45 L32,45 L32,85 L20,85 Z M35,85 L70,85 C75,85 78,82 78,77 L82,58 C83,53 79,48 74,48 L56,48 L59,32 C60,27 56,22 51,22 C49,22 47,23 46,25 L35,45 Z"/></svg>'],

            // Icons & Lifestyle
            ['Lifestyle', 'Coffee Break Mug', '<svg viewBox="0 0 100 100" fill="currentColor"><path d="M20,35 L20,70 C20,78 27,85 35,85 L65,85 C73,85 80,78 80,70 L80,35 Z M80,42 L88,42 C93,42 96,46 96,52 C96,58 93,62 88,62 L80,62 Z M35,20 C35,28 32,28 32,32 M50,18 C50,28 47,28 47,32 M65,20 C65,28 62,28 62,32"/></svg>'],
            ['Lifestyle', 'Festive Gift Box', '<svg viewBox="0 0 100 100" fill="currentColor"><path d="M15,35 L85,35 L85,47 L15,47 Z M20,47 L80,47 L80,85 L20,85 Z M45,35 L45,85 M55,35 L55,85 M35,24 C35,16 48,16 48,25 M65,24 C65,16 52,16 52,25"/></svg>'],
            ['Lifestyle', 'Heart of Gratitude', '<svg viewBox="0 0 100 100" fill="currentColor"><path d="M50,85 C50,85 15,60 15,36 C15,22 25,15 37,15 C44,15 50,20 50,20 C50,20 56,15 63,15 C75,15 85,22 85,36 C85,60 50,85 50,85 Z"/></svg>'],
            ['Lifestyle', 'Rocket Growth', '<svg viewBox="0 0 100 100" fill="currentColor"><path d="M50,10 C50,10 75,30 75,60 L62,68 L50,60 L38,68 L25,60 C25,30 50,10 50,10 Z M42,75 L50,88 L58,75 Z"/></svg>'],
            ['Lifestyle', 'Handshake Partnership', '<svg viewBox="0 0 100 100" fill="currentColor"><path d="M15,45 L35,25 L48,38 L38,48 Z M85,45 L65,25 L52,38 L62,48 Z M32,54 L44,66 C47,69 53,69 56,66 L72,50 L64,42 L52,54 L40,42 Z"/></svg>'],
            ['Lifestyle', 'Festive Diya Lamp', '<svg viewBox="0 0 100 100" fill="currentColor"><path d="M15,60 C15,78 30,85 50,85 C70,85 85,78 85,60 L15,60 Z M50,20 C50,20 38,38 38,48 C38,55 43,58 50,58 C57,58 62,55 62,48 C62,38 50,20 50,20 Z"/></svg>']
        ];

        $stmt = $db->prepare("INSERT INTO customizer_cliparts (category, name, svg_content, display_order) VALUES (?, ?, ?, ?)");
        $order = 1;
        foreach ($cliparts as [$cat, $name, $svg]) {
            $stmt->bind_param("sssi", $cat, $name, $svg, $order);
            $stmt->execute();
            $order++;
        }
    }
}
