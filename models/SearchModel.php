<?php
/**
 * SearchModel.php
 * Handles MySQL database operations for fast, ranked, and filtered searches.
 */
require_once __DIR__ . '/../config/database.php';

class SearchModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Get debounced live search suggestions grouped by entity types
     * Weighted Ranking:
     * 100: Exact Product Name
     * 80:  Starts With Product Name
     * 60:  Contains Product Name
     * 40:  SKU / Brand Match
     * 20:  Description FULLTEXT Match
     */
    public function getSuggestions($keyword, $limit = 12) {
        $cleanKey = trim($keyword);
        if (empty($cleanKey)) {
            return [
                'products' => [],
                'categories' => [],
                'subCategories' => [],
                'subSubCategories' => [],
                'productTypes' => []
            ];
        }

        $searchPattern = '%' . $cleanKey . '%';
        $startsWithPattern = $cleanKey . '%';
        $exactPattern = $cleanKey;

        // 1. Fetch Top Matching Products with Weighted Ranking
        $cleanNoSpace = str_replace([' ', '-'], '', $cleanKey);
        $normalizedPattern = '%' . $cleanNoSpace . '%';

        $productSql = "
            SELECT 
                p.id, 
                p.name, 
                p.price, 
                p.discount_price, 
                p.brand, 
                p.sku,
                (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as image,
                c.name as category_name,
                c.id as category_id,
                (
                    CASE 
                        WHEN LOWER(p.name) = LOWER(?) THEN 100
                        WHEN LOWER(p.name) LIKE LOWER(?) THEN 80
                        WHEN LOWER(p.name) LIKE LOWER(?) THEN 60
                        WHEN LOWER(p.brand) LIKE LOWER(?) OR LOWER(p.sku) LIKE LOWER(?) THEN 40
                        ELSE 20
                    END
                ) as relevance_score
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'active'
              AND (
                LOWER(p.name) LIKE LOWER(?)
                OR REPLACE(REPLACE(LOWER(p.name), ' ', ''), '-', '') LIKE LOWER(?)
                OR LOWER(p.sku) LIKE LOWER(?)
                OR LOWER(p.brand) LIKE LOWER(?)
                OR LOWER(p.description) LIKE LOWER(?)
                OR LOWER(c.name) LIKE LOWER(?)
              )
            ORDER BY relevance_score DESC, p.id DESC
            LIMIT ?
        ";

        $productParams = [
            $exactPattern,
            $startsWithPattern,
            $searchPattern,
            $searchPattern,
            $searchPattern,
            $searchPattern,
            $normalizedPattern,
            $searchPattern,
            $searchPattern,
            $searchPattern,
            $searchPattern,
            (int)$limit
        ];

        $products = $this->db->fetchAll($productSql, $productParams);

        // Process images with fallback
        foreach ($products as &$prod) {
            $prod['price'] = (float)$prod['price'];
            $prod['discount_price'] = $prod['discount_price'] ? (float)$prod['discount_price'] : null;
            if (!$prod['image']) {
                $prod['image'] = 'uploads/products/default.jpg';
            }
        }

        // 2. Fetch Matching Categories & Sub-categories
        $categorySql = "
            SELECT 
                c.id, 
                c.name, 
                c.slug, 
                c.level, 
                c.image,
                c.parent_id,
                p.name as parent_name
            FROM categories c
            LEFT JOIN categories p ON c.parent_id = p.id
            WHERE c.status = 'active'
              AND LOWER(c.name) LIKE LOWER(?)
            ORDER BY c.level ASC, c.display_order ASC
            LIMIT 10
        ";

        $catResults = $this->db->fetchAll($categorySql, [$searchPattern]);

        $categories = [];
        $subCategories = [];
        $subSubCategories = [];
        $productTypes = [];

        foreach ($catResults as $cat) {
            $item = [
                'id' => (int)$cat['id'],
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'image' => $cat['image'],
                'path' => $cat['parent_name'] ? ($cat['parent_name'] . ' > ' . $cat['name']) : $cat['name']
            ];

            if ($cat['level'] == 0) {
                $item['type'] = 'Category';
                $categories[] = $item;
            } elseif ($cat['level'] == 1) {
                $item['type'] = 'Sub Category';
                $subCategories[] = $item;
            } elseif ($cat['level'] == 2) {
                $item['type'] = 'Sub Sub Category';
                $subSubCategories[] = $item;
            } else {
                $item['type'] = 'Product Type';
                $productTypes[] = $item;
            }
        }

        return [
            'products' => array_slice($products, 0, 8),
            'categories' => $categories,
            'subCategories' => $subCategories,
            'subSubCategories' => $subSubCategories,
            'productTypes' => $productTypes
        ];
    }

    /**
     * Full Search Results Page Query with Filtering & Pagination
     */
    public function getSearchResults($params = []) {
        $query = trim($params['q'] ?? '');
        $categoryId = isset($params['category_id']) && $params['category_id'] !== '' ? (int)$params['category_id'] : null;
        $brand = trim($params['brand'] ?? '');
        $minPrice = isset($params['min_price']) && $params['min_price'] !== '' ? (float)$params['min_price'] : null;
        $maxPrice = isset($params['max_price']) && $params['max_price'] !== '' ? (float)$params['max_price'] : null;
        $sort = $params['sort'] ?? 'relevance';
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = max(1, min(40, (int)($params['limit'] ?? 12)));
        $offset = ($page - 1) * $limit;

        $whereClause = ["p.status = 'active'"];
        $queryParams = [];

        if (!empty($query)) {
            $searchPattern = '%' . $query . '%';
            $cleanNoSpace = str_replace([' ', '-'], '', $query);
            $normalizedPattern = '%' . $cleanNoSpace . '%';

            $whereClause[] = "(
                LOWER(p.name) LIKE LOWER(?) 
                OR REPLACE(REPLACE(LOWER(p.name), ' ', ''), '-', '') LIKE LOWER(?)
                OR LOWER(p.sku) LIKE LOWER(?) 
                OR LOWER(p.brand) LIKE LOWER(?) 
                OR LOWER(p.description) LIKE LOWER(?) 
                OR LOWER(c.name) LIKE LOWER(?)
            )";
            $queryParams[] = $searchPattern;
            $queryParams[] = $normalizedPattern;
            $queryParams[] = $searchPattern;
            $queryParams[] = $searchPattern;
            $queryParams[] = $searchPattern;
            $queryParams[] = $searchPattern;
        }

        if ($categoryId) {
            $whereClause[] = "(p.category_id = ? OR c.parent_id = ?)";
            $queryParams[] = $categoryId;
            $queryParams[] = $categoryId;
        }

        if (!empty($brand)) {
            $whereClause[] = "LOWER(p.brand) = LOWER(?)";
            $queryParams[] = $brand;
        }

        if ($minPrice !== null) {
            $whereClause[] = "p.price >= ?";
            $queryParams[] = $minPrice;
        }

        if ($maxPrice !== null) {
            $whereClause[] = "p.price <= ?";
            $queryParams[] = $maxPrice;
        }

        $whereStr = implode(' AND ', $whereClause);

        // Sorting Order
        $orderBy = "p.id DESC";
        switch ($sort) {
            case 'price_low_high':
                $orderBy = "COALESCE(NULLIF(p.discount_price, 0), p.price) ASC";
                break;
            case 'price_high_low':
                $orderBy = "COALESCE(NULLIF(p.discount_price, 0), p.price) DESC";
                break;
            case 'newest':
                $orderBy = "p.created_at DESC";
                break;
            case 'relevance':
            default:
                if (!empty($query)) {
                    $orderBy = "
                        CASE 
                            WHEN LOWER(p.name) = LOWER('" . addslashes($query) . "') THEN 1
                            WHEN LOWER(p.name) LIKE LOWER('" . addslashes($query) . "%') THEN 2
                            ELSE 3
                        END ASC, p.id DESC
                    ";
                }
                break;
        }

        // Count total results
        $countSql = "
            SELECT COUNT(DISTINCT p.id) as total 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE $whereStr
        ";
        $countRes = $this->db->fetch($countSql, $queryParams);
        $totalItems = (int)($countRes['total'] ?? 0);

        // Fetch paginated products
        $itemsSql = "
            SELECT 
                p.id, 
                p.name, 
                p.price, 
                p.discount_price, 
                p.brand, 
                p.sku,
                p.created_at,
                (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as image,
                c.name as category_name,
                c.id as category_id
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE $whereStr
            ORDER BY $orderBy
            LIMIT ? OFFSET ?
        ";

        $execParams = array_merge($queryParams, [$limit, $offset]);
        $items = $this->db->fetchAll($itemsSql, $execParams);

        foreach ($items as &$item) {
            $item['price'] = (float)$item['price'];
            $item['discount_price'] = $item['discount_price'] ? (float)$item['discount_price'] : null;
            if (!$item['image']) {
                $item['image'] = 'uploads/products/default.jpg';
            }
        }

        // Fetch Facets (Available Brands & Categories for filters)
        $brandFacets = $this->db->fetchAll("
            SELECT DISTINCT p.brand 
            FROM products p 
            WHERE p.status = 'active' AND p.brand IS NOT NULL AND TRIM(p.brand) != '' 
            ORDER BY p.brand ASC
        ");

        $cleanBrands = [];
        foreach ($brandFacets as $bRow) {
            $val = trim($bRow['brand'] ?? '');
            if ($val !== '' && !str_contains($val, 'Deprecated') && !str_contains($val, 'htmlspecialchars')) {
                $cleanBrands[] = $val;
            }
        }

        $catFacets = $this->db->fetchAll("
            SELECT id, name, parent_id 
            FROM categories 
            WHERE status = 'active' AND level = 0 
            ORDER BY display_order ASC, name ASC
        ");

        $priceBoundRes = $this->db->fetch("
            SELECT MIN(price) as min_val, MAX(price) as max_val 
            FROM products 
            WHERE status = 'active'
        ");

        return [
            'items' => $items,
            'total' => $totalItems,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($totalItems / $limit),
            'facets' => [
                'categories' => $catFacets,
                'brands' => $cleanBrands,
                'priceRange' => [
                    'min' => (float)($priceBoundRes['min_val'] ?? 0),
                    'max' => (float)($priceBoundRes['max_val'] ?? 10000)
                ]
            ]
        ];
    }
}
