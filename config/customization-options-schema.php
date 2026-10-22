<?php
// Master library of customization options ("keys") — admin page: customization-options.php.
// A product only stores which keys it uses (+ which values / price overrides), see
// ProductController::buildCustomizationFields() and resolveCustomizationFields().

if (!function_exists('ensureCustomizationOptionsTable')) {
    function ensureCustomizationOptionsTable($db) {
        $db->query("CREATE TABLE IF NOT EXISTS customization_options (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            input_type VARCHAR(20) NOT NULL DEFAULT 'select',
            option_values TEXT NULL,
            help VARCHAR(255) NULL,
            is_required TINYINT(1) NOT NULL DEFAULT 0,
            display_order INT(11) NOT NULL DEFAULT 0,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_co_status (status, display_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

if (!function_exists('customizationInputTypes')) {
    // input_type => admin label
    function customizationInputTypes() {
        return [
            'select'   => 'Dropdown',
            'color'    => 'Colour palette',
            'multi'    => 'Multi-select chips',
            'radio'    => 'Radio buttons',
            'text'     => 'Text (single line)',
            'textarea' => 'Text (multi-line)',
            'number'   => 'Number',
            'image'    => 'Image / file upload',
            'both'     => 'Text + image upload',
        ];
    }

    // Types where the customer picks from a list of values
    function customizationChoiceTypes() {
        return ['select', 'color', 'multi', 'radio'];
    }

    // Cleans a list of values coming from the admin form: [{label, hex, price}, ...]
    function sanitizeCustomizationValues($values) {
        $out = [];
        $seen = [];
        foreach ((array) $values as $v) {
            $label = trim((string) ($v['label'] ?? ''));
            if ($label === '' || isset($seen[mb_strtolower($label)])) continue;
            $seen[mb_strtolower($label)] = true;
            $hex = (string) ($v['hex'] ?? '');
            $out[] = [
                'label' => mb_substr($label, 0, 100),
                'hex'   => preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? $hex : '',
                'price' => round(max(0, (float) ($v['price'] ?? 0)), 2),
            ];
        }
        return $out;
    }
}

if (!function_exists('customizationStarterSet')) {
    // Common keys for corporate gifting products; imported once from the admin page
    function customizationStarterSet() {
        $v = function (...$items) {
            return array_map(function ($i) {
                return is_array($i) ? ['label' => $i[0], 'hex' => $i[1] ?? '', 'price' => $i[2] ?? 0] : ['label' => $i, 'hex' => '', 'price' => 0];
            }, $items);
        };
        return [
            ['Tshirt Type', 'select', 1, $v('Round Neck', 'Polo', 'V-Neck', 'Hoodie'), ''],
            ['Style', 'select', 1, $v('Unisex', 'Men', 'Women', 'Kids'), ''],
            ['Material / Fabric', 'select', 1, $v('100% Cotton 180gsm', '100% Cotton 220gsm', 'Polyester Dri-Fit'), ''],
            ['Print Type', 'select', 1, $v('Full Colour Print', 'Screen Print', 'Embroidery', 'Laser Engraving', 'UV Print'), ''],
            ['Fabric Colour', 'color', 1, $v(['White', '#ffffff'], ['Black', '#000000'], ['Navy Blue', '#1f2a44'], ['Red', '#c62828'], ['Grey Melange', '#9e9e9e']), ''],
            ['Print Locations', 'multi', 1, $v('Front', ['Back', '', 50], ['Left Sleeve', '', 30], ['Right Sleeve', '', 30]), 'Extra print locations are charged per piece.'],
            ['Print Size', 'select', 0, $v('Pocket', 'A4', ['A3', '', 40]), ''],
            ['Mug Type', 'select', 1, $v('Ceramic White 11oz', ['Magic Mug', '', 60], ['Inner Colour Mug', '', 40]), ''],
            ['Print Area', 'radio', 1, $v('One Side', ['Wrap Around', '', 30]), ''],
            ['Product Colour', 'color', 1, $v(['Black', '#000000'], ['White', '#ffffff'], ['Blue', '#1565c0'], ['Silver', '#bdbdbd'], ['Red', '#c62828']), ''],
            ['Ink Colour', 'select', 1, $v('Blue', 'Black'), ''],
            ['Size', 'select', 1, $v('8x10 inch', 'A4', ['A3', '', 200], ['12x18 inch', '', 300]), ''],
            ['Orientation', 'radio', 1, $v('Portrait', 'Landscape'), ''],
            ['Name / Text to Print', 'text', 0, [], 'Up to 25 characters.'],
            ['Engraving Text', 'textarea', 0, [], 'Name, title, event and date.'],
            ['Upload Logo / Design', 'image', 0, [], 'PNG, JPG or PDF. High resolution works best.'],
            ['Upload Photo', 'image', 0, [], 'Clear, high resolution photo.'],
        ];
    }
}
