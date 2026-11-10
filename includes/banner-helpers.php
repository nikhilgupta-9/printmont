<?php
if (!function_exists('resolveAdminThumb')) {
    function resolveAdminThumb($path) {
        if (empty($path)) return '';
        $trimmed = trim($path);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, '//') || str_starts_with($trimmed, 'data:')) {
            return $trimmed;
        }
        if (str_starts_with($trimmed, '/printmont/')) {
            return $trimmed;
        }
        return ltrim($trimmed, '/');
    }
}

/**
 * "Place this new carousel/banner before or after an existing section" — the positioning
 * mechanism from the Manage Carousels spec ("Category select Add > Add Check Box (After and
 * before)" etc). Each anchor type keeps its own order column; we only ever READ it here and
 * write a fractional value into the carousel's OWN sort_order, so existing rows never need
 * renumbering — a 2nd "after" of the same anchor just lands at +0.75, +0.875, and so on.
 */
if (!function_exists('ANCHOR_SECTION_TYPES')) {
    function ANCHOR_SECTION_TYPES() {
        return [
            'category'          => 'Category',
            'product_carousel'  => 'Products Carousel',
            'category_carousel' => 'Category Carousel',
            'banner'            => 'Banner',
            'banner_carousel'   => 'Banner Carousel',
            'extra_product'     => 'Extra Featured Product Block',
        ];
    }
}

// device is 'desktop' or 'mobile'
if (!function_exists('getAnchorSectionOptions')) {
    function getAnchorSectionOptions($db, string $anchorType, string $device): array {
        $options = [];
        switch ($anchorType) {
            case 'category':
                $col = $device === 'mobile' ? 'mobile_home_order' : 'desktop_home_order';
                $res = $db->query("SELECT id, name AS label, $col AS sort_order FROM categories WHERE status = 'active' ORDER BY $col ASC");
                break;
            case 'product_carousel':
                $col = $device === 'mobile' ? 'mobile_sort_order' : 'desktop_sort_order';
                $res = $db->query("SELECT id, title AS label, $col AS sort_order FROM carousels WHERE carousel_type = 'product' ORDER BY $col ASC");
                break;
            case 'category_carousel':
                $col = $device === 'mobile' ? 'mobile_sort_order' : 'desktop_sort_order';
                $res = $db->query("SELECT id, title AS label, $col AS sort_order FROM carousels WHERE carousel_type = 'category' ORDER BY $col ASC");
                break;
            case 'banner':
                $col = $device === 'mobile' ? 'mobile_sort_order' : 'desktop_sort_order';
                $res = $db->query("SELECT id, title AS label, $col AS sort_order FROM banners WHERE banner_type = 'single' ORDER BY $col ASC");
                break;
            case 'banner_carousel':
                $col = $device === 'mobile' ? 'mobile_sort_order' : 'desktop_sort_order';
                $res = $db->query("SELECT id, title AS label, $col AS sort_order FROM banner_carousels ORDER BY $col ASC");
                break;
            case 'extra_product':
                $col = $device === 'mobile' ? 'mobile_home_order' : 'desktop_home_order';
                $res = $db->query("SELECT id, CONCAT(name, ' (Extra Products)') AS label, $col AS sort_order FROM categories WHERE status = 'active' AND home_featured_product_ids IS NOT NULL AND home_featured_product_ids != '' ORDER BY $col ASC");
                break;
            default:
                return [];
        }
        if ($res) {
            while ($row = $res->fetch_assoc()) $options[] = $row;
        }
        return $options;
    }
}

/**
 * Resolve {anchor_type, anchor_id, position} into a sort_order for the row being saved.
 * Falls back to "end of the list" (max + 1) when no anchor was chosen, so a plain save
 * without positioning still behaves like before.
 */
if (!function_exists('resolveAnchoredSortOrder')) {
    function resolveAnchoredSortOrder($db, ?string $anchorType, ?int $anchorId, string $position, string $device, string $ownTable = 'carousels'): float {
        if (empty($anchorType) || empty($anchorId)) {
            $col = $device === 'mobile' ? 'mobile_sort_order' : 'desktop_sort_order';
            $res = $db->query("SELECT MAX($col) AS m FROM $ownTable");
            $max = $res ? (float) ($res->fetch_assoc()['m'] ?? 0) : 0;
            return $max + 1;
        }

        $options = getAnchorSectionOptions($db, $anchorType, $device);
        $anchorOrder = null;
        $prevOrder = null;
        $nextOrder = null;
        foreach ($options as $i => $opt) {
            if ((int) $opt['id'] === $anchorId) {
                $anchorOrder = (float) $opt['sort_order'];
                $prevOrder = $i > 0 ? (float) $options[$i - 1]['sort_order'] : null;
                $nextOrder = $i < count($options) - 1 ? (float) $options[$i + 1]['sort_order'] : null;
                break;
            }
        }
        if ($anchorOrder === null) {
            $col = $device === 'mobile' ? 'mobile_sort_order' : 'desktop_sort_order';
            $res = $db->query("SELECT MAX($col) AS m FROM $ownTable");
            $max = $res ? (float) ($res->fetch_assoc()['m'] ?? 0) : 0;
            return $max + 1;
        }

        if ($position === 'before') {
            return $prevOrder !== null ? ($prevOrder + $anchorOrder) / 2 : $anchorOrder - 1;
        }
        return $nextOrder !== null ? ($anchorOrder + $nextOrder) / 2 : $anchorOrder + 1;
    }
}
