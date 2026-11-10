<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/ProductModel.php');
require_once(__DIR__ . '/../models/CategoryModel.php');
require_once(__DIR__ . '/../config/customization-options-schema.php');
require_once(__DIR__ . '/../config/product-sales-schema.php');
require_once(__DIR__ . '/../config/product-info-banners-schema.php');
require_once(__DIR__ . '/../includes/image-upload-helper.php');

class ProductController
{
    private $productModel;
    private $categoryModel;
    private $db;
    private $customizationLibrary = null; // id => option row, loaded once per request

    public function __construct($db = null)
    {
        if (!$db) {
            $database = new Database();
            $db = $database->getConnection();
        }
        $this->db = $db;
        $this->productModel = new ProductModel();
        $this->categoryModel = new Category($db);
    }

    // Master customization options (customization-options.php), keyed by id
    private function getCustomizationLibrary()
    {
        if ($this->customizationLibrary !== null) return $this->customizationLibrary;
        $this->customizationLibrary = [];
        if (!$this->db) return $this->customizationLibrary;
        ensureCustomizationOptionsTable($this->db);
        $res = $this->db->query("SELECT id, name, input_type, option_values, help, is_required, status FROM customization_options");
        while ($res && ($row = $res->fetch_assoc())) {
            $row['values'] = json_decode($row['option_values'] ?? '[]', true) ?: [];
            $this->customizationLibrary[(int) $row['id']] = $row;
        }
        return $this->customizationLibrary;
    }

    /**
     * Turns a product's stored customization_fields into what the product page renders.
     * Library entries ({"option_id":N,"required":1,"values":[{"label":"White","price":0}]}) take
     * name / type / colours / help from the master option, so editing the master updates every
     * product; only the chosen values and their per-product prices come from the product.
     * Entries without option_id (older per-product fields) pass through unchanged.
     */
    public function resolveCustomizationFields($stored)
    {
        $entries = is_array($stored) ? $stored : (json_decode($stored ?? '[]', true) ?: []);
        if (empty($entries)) return [];

        $library = $this->getCustomizationLibrary();
        $fields = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) continue;
            if (empty($entry['option_id'])) {
                $fields[] = $entry;
                continue;
            }
            $master = $library[(int) $entry['option_id']] ?? null;
            if (!$master || $master['status'] !== 'active') continue;

            $field = [
                'option_id' => (int) $master['id'],
                'label'     => $master['name'],
                'type'      => $master['input_type'],
                'required'  => !empty($entry['required']) ? 1 : 0,
                'help'      => $master['help'] ?? '',
            ];

            if (in_array($master['input_type'], customizationChoiceTypes(), true)) {
                $chosen = [];
                foreach ((array) ($entry['values'] ?? []) as $v) {
                    $chosen[mb_strtolower((string) ($v['label'] ?? ''))] = $v;
                }
                $options = [];
                foreach ($master['values'] as $mv) {
                    $key = mb_strtolower($mv['label']);
                    if (!isset($chosen[$key])) continue;
                    $price = $chosen[$key]['price'] ?? null;
                    $options[] = [
                        'label' => $mv['label'],
                        'hex'   => $mv['hex'] ?? '',
                        'price' => ($price === null || $price === '') ? (float) $mv['price'] : (float) $price,
                    ];
                }
                if (empty($options)) continue;
                $field['options'] = $options;
            }
            $fields[] = $field;
        }
        return $fields;
    }

    // API Methods - Fetch Only
    public function getAllProductsApi()
    {
        $products = $this->productModel->getAllProducts();

        // Format products for API response
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductForApi($product);
        }

        return $formattedProducts;
    }

    public function getProductByIdApi($id)
    {
        $product = $this->productModel->getProductById($id);
        if ($product) {
            return $this->formatProductForApi($product);
        }
        return null;
    }

    public function getProductBySlugApi($slug)
    {
        $product = $this->productModel->getProductBySlug($slug);
        if ($product) {
            return $this->formatProductForApi($product);
        }
        return null;
    }

    private function formatProductForApi($product)
    {
        // Get base URL for absolute image paths.
        // Local XAMPP has no trusted cert for "localhost", so force http there
        // regardless of the protocol this particular request arrived on
        // (a browser can end up hitting https://localhost due to stale HSTS
        // from an unrelated local project) — otherwise image URLs come out
        // https and silently fail to load.
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isLocalHost = strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
        $protocol = (!$isLocalHost && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $scriptDir = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
        $apiPos = strpos($scriptDir, '/api');
        $basePath = $apiPos !== false ? substr($scriptDir, 0, $apiPos) : (isset($_SERVER['SCRIPT_NAME']) ? dirname(dirname($_SERVER['SCRIPT_NAME'])) : '/printmont/printmont-backend');
        $baseUrl = $protocol . "://" . $host . rtrim($basePath, '/') . "/";

        // Format images with full URLs
        // getProductById() uses 'main_images' key; other methods use 'images' key
        $imageSource = [];
        if (!empty($product['main_images'])) {
            $imageSource = $product['main_images'];
        } elseif (!empty($product['images']) && is_array($product['images'])) {
            $imageSource = $product['images'];
        }

        $formattedImages = [];
        $primaryImageUrl = null;

        foreach ($imageSource as $image) {
            $imagePath = $image['image_url'] ?? '';
            if (empty($imagePath)) continue;
            $absoluteUrl = (strpos($imagePath, 'http') === 0)
                ? $imagePath
                : $baseUrl . ltrim($imagePath, '/');
            $isPrimary = !empty($image['is_primary']);
            if ($isPrimary && $primaryImageUrl === null) {
                $primaryImageUrl = $absoluteUrl;
            }
            $formattedImages[] = [
                'id'            => (int) ($image['id'] ?? 0),
                'image_url'     => $absoluteUrl,
                'is_primary'    => (bool) $isPrimary,
                'display_order' => (int) ($image['display_order'] ?? 0),
            ];
        }

        // Fallback 1: thumbnail_image column on products table
        if (empty($formattedImages) && !empty($product['thumbnail_image'])) {
            $tp = $product['thumbnail_image'];
            $au = (strpos($tp, 'http') === 0) ? $tp : $baseUrl . ltrim($tp, '/');
            $primaryImageUrl = $au;
            $formattedImages[] = ['id' => 0, 'image_url' => $au, 'is_primary' => true, 'display_order' => 0];
        }

        // Fallback 2: generic image column
        if (empty($formattedImages) && !empty($product['image'])) {
            $tp = $product['image'];
            $au = (strpos($tp, 'http') === 0) ? $tp : $baseUrl . ltrim($tp, '/');
            $primaryImageUrl = $au;
            $formattedImages[] = ['id' => 0, 'image_url' => $au, 'is_primary' => true, 'display_order' => 0];
        }

        // Use first image as primary if none marked
        if ($primaryImageUrl === null && !empty($formattedImages)) {
            $primaryImageUrl = $formattedImages[0]['image_url'];
        }

        // Gallery images (admin section 4) — shown after the main images on the product page
        $galleryImages = [];
        foreach ((json_decode($product['gallery_images'] ?? '[]', true) ?: []) as $gp) {
            if (!is_string($gp) || $gp === '') continue;
            $galleryImages[] = (strpos($gp, 'http') === 0) ? $gp : $baseUrl . ltrim($gp, '/');
        }

        $basePrice = (float) ($product['price'] ?? 0);
        $regPrice = (float) ($product['regular_price'] ?? 0);
        $offPrice = !empty($product['offer_price']) ? (float)$product['offer_price'] : (!empty($product['discount_price']) ? (float)$product['discount_price'] : $basePrice);
        
        $mrp = $regPrice > 0 ? $regPrice : ($offPrice > 0 ? round($offPrice * 1.5) : 2999);
        $sellingPrice = $offPrice > 0 ? $offPrice : ($basePrice > 0 ? $basePrice : 1499);

        // Live timed sale (admin: product-sales.php) → sale price becomes the selling price
        $sale = null;
        $liveSale = $this->db ? getLiveSaleForProduct($this->db, $product['id']) : null;
        if ($liveSale) {
            $salePrice = salePriceFor($liveSale, $sellingPrice);
            if ($salePrice > 0 && $salePrice < $sellingPrice) {
                $sale = [
                    'id'             => (int) $liveSale['id'],
                    'label'          => $liveSale['label'],
                    'price_before'   => $sellingPrice,
                    'sale_price'     => $salePrice,
                    'starts_at'      => saleTimestamp($liveSale['starts_at']),
                    'ends_at'        => saleTimestamp($liveSale['ends_at']), // epoch seconds
                ];
                $sellingPrice = $salePrice;
            }
        }
        $discountPct = ($mrp > $sellingPrice) ? (int)round((($mrp - $sellingPrice) / $mrp) * 100) : 0;

        // Format Color attributes and variant images with full URLs
        $rawColors = !empty($product['color_attributes']) ? json_decode($product['color_attributes'], true) : (!empty($product['color']) ? [['name' => $product['color'], 'hex' => '#000000', 'price' => 0, 'image' => '', 'text' => '']] : []);
        $formattedColors = [];
        $formattedColorImages = [];

        if (is_array($rawColors)) {
            foreach ($rawColors as $c) {
                if (is_string($c)) {
                    $c = ['name' => $c, 'hex' => '#000000', 'price' => 0, 'image' => '', 'text' => ''];
                }
                $imgUrl = '';
                if (!empty($c['image'])) {
                    $imgUrl = (strpos($c['image'], 'http') === 0) ? $c['image'] : $baseUrl . ltrim($c['image'], '/');
                }
                $c['image'] = $imgUrl;
                $formattedColors[] = $c;
                if ($imgUrl && !empty($c['name'])) {
                    $formattedColorImages[$c['name']] = $imgUrl;
                }
            }
        }

        $rawColorImages = !empty($product['color_images']) ? json_decode($product['color_images'], true) : [];
        if (is_array($rawColorImages)) {
            foreach ($rawColorImages as $k => $v) {
                if (!empty($v)) {
                    $formattedColorImages[$k] = (strpos($v, 'http') === 0) ? $v : $baseUrl . ltrim($v, '/');
                }
            }
        }

        return [
            'sale' => $sale,
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'long_description' => $product['long_description'] ?? null,
            'instructions' => $product['instructions'] ?? null,
            'delivery_info' => $product['delivery_info'] ?? null,
            'category_id' => (int) $product['category_id'],
            'category_name' => $product['category_name'] ?? ($product['main_category_name'] ?? null),
            'main_category_name' => $product['main_category_name'] ?? null,
            'sub_category_id' => !empty($product['sub_category_id']) ? (int) $product['sub_category_id'] : null,
            'sub_category_name' => $product['sub_category_name'] ?? null,
            'sub_sub_category_id' => !empty($product['sub_sub_category_id']) ? (int) $product['sub_sub_category_id'] : null,
            'sub_sub_category_name' => $product['sub_sub_category_name'] ?? null,
            'brand' => $product['brand'],
            'price' => $sellingPrice,
            'selling_price' => $sellingPrice,
            'offer_price' => $sellingPrice,
            'discount_price' => $sellingPrice,
            'regular_price' => $mrp,
            'mrp' => $mrp,
            'original_price' => $mrp,
            'discount_percent' => $discountPct,
            'discount_pct' => $discountPct,
            'stock_quantity' => (int) $product['stock_quantity'],
            'sku' => $product['sku'],
            'slug' => $product['product_slug'] ?? null,
            'status' => $product['status'],
            'featured' => (bool) $product['featured'],
            'our_bestseller' => (bool) ($product['our_bestseller'] ?? false),
            'top_rated' => (bool) ($product['top_rated'] ?? false),
            'top_deal' => (bool) ($product['top_deal'] ?? false),
            'is_assured' => (bool) ($product['is_assured'] ?? true),
            'assured' => (bool) ($product['is_assured'] ?? true),
            'min_quantity' => (int) ($product['minimum_quantity'] ?? 1),
            'out_of_stock_status' => $product['out_of_stock_status'] ?? 'in_stock',
            'show_cart_buttons' => !isset($product['show_cart_buttons']) || (bool) $product['show_cart_buttons'],
            'show_left_quantity' => (bool) ($product['show_quantity'] ?? false),
            'show_help_button' => (bool) ($product['show_help_button'] ?? false),
            'help_button_text' => $product['help_button_text'] ?? 'Need Help?',
            'help_button_link' => $product['help_button_link'] ?? '',
            'show_bulk_form' => (bool) ($product['show_bulk_form'] ?? false),
            'show_inquiry_form' => (bool) ($product['show_inquiry_form'] ?? false),
            'customization_label_status' => (bool) ($product['show_customization_label'] ?? false),
            'enable_upload_design' => isset($product['enable_upload_design']) ? (bool) $product['enable_upload_design'] : true,
            'upload_design_max_images' => isset($product['upload_design_max_images']) ? max(1, (int) $product['upload_design_max_images']) : 4,
            'upload_design_max_texts' => isset($product['upload_design_max_texts']) ? max(0, (int) $product['upload_design_max_texts']) : 2,
            'enable_customizer' => isset($product['enable_customizer']) ? (bool) $product['enable_customizer'] : true,
            'customizer_template_id' => !empty($product['customizer_template_id']) ? (int) $product['customizer_template_id'] : null,
            'customization_label' => $product['customization_label'] ?? 'Customizable',
            'customization_data' => $this->resolveCustomizationFields($product['customization_fields'] ?? null),
            'custom_attributes' => !empty($product['product_attributes_fields']) ? (is_array($product['product_attributes_fields']) ? $product['product_attributes_fields'] : (json_decode($product['product_attributes_fields'], true) ?: [])) : [],
            'product_attributes_fields' => !empty($product['product_attributes_fields']) ? (is_array($product['product_attributes_fields']) ? $product['product_attributes_fields'] : (json_decode($product['product_attributes_fields'], true) ?: [])) : [],
            'size_status' => (bool) ($product['show_sizes'] ?? false),
            'sizes' => !empty($product['size_attributes']) ? json_decode($product['size_attributes'], true) : [],
            'size_chart_image' => !empty($product['size_chart_image']) ? ((strpos($product['size_chart_image'], 'http') === 0) ? $product['size_chart_image'] : $baseUrl . ltrim($product['size_chart_image'], '/')) : null,
            'colors' => $formattedColors,
            'color_images' => $formattedColorImages,
            'materials' => !empty($product['material_attributes']) ? json_decode($product['material_attributes'], true) : (!empty($product['material']) ? [$product['material']] : []),
            'lamination' => !empty($product['lamination_attributes']) ? json_decode($product['lamination_attributes'], true) : [],
            'orientation' => !empty($product['orientation_attributes']) ? json_decode($product['orientation_attributes'], true) : [],
            'printing_location' => !empty($product['printing_location']) ? json_decode($product['printing_location'], true) : (!empty($product['customization_location']) ? array_filter(array_map('trim', explode(',', $product['customization_location']))) : []),
            'type' => $product['type'] ?? '',
            'material' => $product['material'] ?? '',
            'color' => $product['color'] ?? '',
            'occasion' => $product['occasion'] ?? '',
            'discount_type' => $product['discount_type'] ?? '',
            'gender' => $product['gender'] ?? '',
            'ideal_for' => $product['ideal_for'] ?? '',
            'customization_tech' => $product['customization_tech'] ?? '',
            'customization_location' => $product['customization_location'] ?? '',
            'features' => $product['features'] ?? '',
            'shape' => $product['shape'] ?? '',
            'gift_type' => $product['gift_type'] ?? '',
            'capacity' => $product['capacity'] ?? '',
            'ink_color' => $product['ink_color'] ?? '',
            'bulk_order_pricing' => !empty($product['bulk_price_variance']) ? json_decode($product['bulk_price_variance'], true) : [],
            'quantity_pricing' => !empty($product['quantity_price_breaks']) ? json_decode($product['quantity_price_breaks'], true) : [],
            'shipping_method_status' => (bool) ($product['shipping_method_status'] ?? false),
            'local_shipping_price' => (float) ($product['local_shipping_charge'] ?? 0),
            'regional_shipping_price' => (float) ($product['regional_shipping_charge'] ?? 0),
            'national_shipping_price' => (float) ($product['national_shipping_charge'] ?? 0),
            'local_shipping_msg' => $product['local_shipping_message'] ?? '',
            'regional_shipping_msg' => $product['regional_shipping_message'] ?? '',
            'national_shipping_msg' => $product['national_shipping_message'] ?? '',
            'cancel_status' => !empty($product['cancel_available'] ?? 1) ? 'yes' : 'no',
            'cancel_type' => $product['cancel_type'] ?? 'hours',
            'cancel_value' => (int) ($product['cancel_time'] ?? 24),
            'cod_status' => !empty($product['cod_available'] ?? 1) ? 'yes' : 'no',
            'addon_product_ids' => !empty($product['addon_product_ids']) ? json_decode($product['addon_product_ids'], true) : [],
            'meta_title' => $product['meta_title'] ?? '',
            'meta_keywords' => $product['meta_keywords'] ?? '',
            'meta_description' => $product['meta_description'] ?? '',
            'alt_tag' => $product['alt_tag'] ?? '',
            'images'          => $formattedImages,
            'gallery_images'  => $galleryImages,
            'primary_image'   => $primaryImageUrl,
            'thumbnail_image' => $primaryImageUrl,
            'thumbnail'       => $primaryImageUrl,
            'image'           => $primaryImageUrl,
            'img'             => $primaryImageUrl,
            'created_at' => $product['created_at'],
            'updated_at' => $product['updated_at']
        ];
    }

    /**
     * Secure and lightweight product formatting for public homepage cards and carousels
     * Excludes internal database schema, sensitive configurations, and bulky backend metadata.
     */
    public function formatProductCardForApi($product)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . $host . '/printmont-backend/';

        $primaryImageUrl = null;
        if (!empty($product['images']) && is_array($product['images'])) {
            foreach ($product['images'] as $image) {
                if (!empty($image['is_primary'])) {
                    $tp = $image['image_url'];
                    $primaryImageUrl = (strpos($tp, 'http') === 0) ? $tp : $baseUrl . ltrim($tp, '/');
                    break;
                }
            }
            if ($primaryImageUrl === null && !empty($product['images'][0]['image_url'])) {
                $tp = $product['images'][0]['image_url'];
                $primaryImageUrl = (strpos($tp, 'http') === 0) ? $tp : $baseUrl . ltrim($tp, '/');
            }
        }

        if (empty($primaryImageUrl) && !empty($product['thumbnail_image'])) {
            $tp = $product['thumbnail_image'];
            $primaryImageUrl = (strpos($tp, 'http') === 0) ? $tp : $baseUrl . ltrim($tp, '/');
        }

        if (empty($primaryImageUrl) && !empty($product['image'])) {
            $tp = $product['image'];
            $primaryImageUrl = (strpos($tp, 'http') === 0) ? $tp : $baseUrl . ltrim($tp, '/');
        }

        $basePrice = (float) ($product['price'] ?? 0);
        $regPrice = (float) ($product['regular_price'] ?? 0);
        $offPrice = !empty($product['offer_price']) ? (float)$product['offer_price'] : (!empty($product['discount_price']) ? (float)$product['discount_price'] : $basePrice);
        
        $mrp = $regPrice > 0 ? $regPrice : ($offPrice > 0 ? round($offPrice * 1.5) : 2999);
        $sellingPrice = $offPrice > 0 ? $offPrice : ($basePrice > 0 ? $basePrice : 1499);
        $discountPct = ($mrp > $sellingPrice) ? (int)round((($mrp - $sellingPrice) / $mrp) * 100) : 0;

        $id = (int)$product['id'];
        $rating = !empty($product['rating']) ? (float)$product['rating'] : round(4.5 + ($id % 5) * 0.1, 1);
        $reviews = !empty($product['reviews']) ? (int)$product['reviews'] : (18 + ($id % 35) * 8);

        return [
            'id' => $id,
            'name' => $product['name'] ?? '',
            'title' => $product['name'] ?? '',
            'slug' => $product['product_slug'] ?? null,
            'price' => $sellingPrice,
            'selling_price' => $sellingPrice,
            'offer_price' => $sellingPrice,
            'regular_price' => $mrp,
            'mrp' => $mrp,
            'original_price' => $mrp,
            'discount_percent' => $discountPct,
            'discount_pct' => $discountPct,
            'image' => $primaryImageUrl,
            'img' => $primaryImageUrl,
            'thumbnail_image' => $primaryImageUrl,
            'rating' => $rating,
            'reviews' => $reviews,
            'customizable' => true,
            'customization_label' => $product['customization_label'] ?? 'CUSTOMIZABLE',
            'badge' => 'Priemium',
            'category_name' => $product['category_name'] ?? null
        ];
    }

    public function getCategories()
    {
        return $this->categoryModel->getAllCategoriesFlat();
    }

    public function addProduct($data, $files)
    {
        try {
            // Required field validation
            $required = ['product_name', 'product_slug', 'sku_code', 'regular_price', 'product_quantity', 'category_id'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }

            // ── Image uploads ──────────────────────────────────────────────
            $thumbnailImage = '';
            $sizeChartImage = null;
            $mainImages     = [];
            $galleryImages  = [];

            if (!empty($files['thumbnail_image']['name'])) {
                $thumbnailImage = $this->uploadImage($files['thumbnail_image'], 'thumbnail');
            }
            if (!empty($files['size_chart_image']['name'])) {
                try {
                    $sizeChartImage = $this->uploadImage($files['size_chart_image'], 'size_charts');
                } catch (Exception $e) {
                    error_log("Failed to upload size chart image: " . $e->getMessage());
                }
            }
            if (!empty($files['main_images']['name'][0])) {
                $mainImages = $this->uploadMultipleImages($files['main_images'], 'main');
            }
            if (!empty($files['gallery_images']['name'][0])) {
                $galleryImages = $this->uploadMultipleImages($files['gallery_images'], 'gallery');
            }

            // Format main images for product_images table
            $formattedMainImages = [];
            foreach ($mainImages as $index => $path) {
                $formattedMainImages[] = [
                    'image_url'     => $path,
                    'is_primary'    => ($index === 0),
                    'display_order' => $index,
                ];
            }

            // ── Bulk pricing (JSON) ────────────────────────────────────────
            $bulkPricing = [];
            if (!empty($data['bulk_min_qty']) && is_array($data['bulk_min_qty'])) {
                foreach ($data['bulk_min_qty'] as $i => $minQty) {
                    if ($minQty !== '') {
                        $bulkPricing[] = [
                            'min_qty' => intval($minQty),
                            'max_qty' => intval($data['bulk_max_qty'][$i] ?? 0),
                            'price'   => floatval($data['bulk_price'][$i] ?? 0),
                        ];
                    }
                }
            }

            // ── Customization fields (JSON) ───────────────────────────────
            $customizationFields = $this->buildCustomizationFields($data);

            // ── Additional Product Attributes (JSON, Tab 9) ───────────────
            $productAttributeFields = $this->buildProductAttributeFields($data);

            // ── Size attributes (JSON) ────────────────────────────────────
            $sizeAttributes = [];
            if (!empty($data['size_names']) && is_array($data['size_names'])) {
                foreach ($data['size_names'] as $i => $name) {
                    if (!empty($name)) {
                        $sizeAttributes[] = [
                            'name'  => $name,
                            'price' => floatval($data['size_prices'][$i] ?? 0),
                        ];
                    }
                }
            }

            // ── Color attributes & per-product variant images (JSON) ───────
            $colorAttributes = [];
            $colorImagesMap  = [];
            if (!empty($data['variant_color_name']) && is_array($data['variant_color_name'])) {
                foreach ($data['variant_color_name'] as $i => $name) {
                    $name = trim($name);
                    if ($name === '') continue;
                    $hex = $data['variant_color_hex'][$i] ?? '#000000';
                    $price = floatval($data['variant_color_price'][$i] ?? 0);
                    $text = trim($data['variant_color_text'][$i] ?? '');
                    $imagePath = trim($data['existing_variant_color_images'][$i] ?? '');

                    // Upload new variant image if supplied
                    if (isset($files['variant_color_images']['error'][$i]) && $files['variant_color_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $uploadedPath = $this->uploadVariantImage($files['variant_color_images'], $i, $name);
                        if ($uploadedPath) {
                            $imagePath = $uploadedPath;
                        }
                    }

                    $colorAttributes[] = [
                        'name'  => $name,
                        'hex'   => $hex,
                        'price' => $price,
                        'image' => $imagePath,
                        'text'  => $text,
                    ];
                    if (!empty($imagePath)) {
                        $colorImagesMap[$name] = $imagePath;
                    }
                }
            } elseif (!empty($data['color_names']) && is_array($data['color_names'])) {
                foreach ($data['color_names'] as $i => $name) {
                    $name = trim($name);
                    if ($name !== '') {
                        $colorAttributes[] = [
                            'name'  => $name,
                            'hex'   => $data['color_hexes'][$i] ?? '#000000',
                            'price' => floatval($data['color_prices'][$i] ?? 0),
                            'image' => '',
                            'text'  => '',
                        ];
                    }
                }
            }

            // ── Quantity pricing tiers (JSON) ─────────────────────────────
            $quantityPricing = [];
            if (!empty($data['qty_tier_qty']) && is_array($data['qty_tier_qty'])) {
                foreach ($data['qty_tier_qty'] as $i => $qty) {
                    if ($qty !== '') {
                        $quantityPricing[] = [
                            'qty'   => intval($qty),
                            'price' => floatval($data['qty_tier_price'][$i] ?? 0),
                        ];
                    }
                }
            }

            // ── Addon product IDs ─────────────────────────────────────────
            $addonIds = !empty($data['addon_product_ids']) ? $data['addon_product_ids'] : '[]';

            // ── Assemble all fields ───────────────────────────────────────
            $productData = [
                // Tab 1 – General
                'name'                   => trim($data['product_name']),
                'product_slug'           => trim($data['product_slug']),
                'description'            => $data['description'] ?? '',
                'long_description'       => $data['long_description'] ?? '',
                'instructions'           => $data['instructions'] ?? '',
                'delivery_info'          => $data['delivery_info'] ?? '',
                'price'                  => floatval($data['regular_price']),
                'regular_price'          => floatval($data['regular_price']),
                'offer_price'            => !empty($data['offer_price']) ? floatval($data['offer_price']) : null,
                'discount_price'         => !empty($data['offer_price']) ? floatval($data['offer_price']) : null,
                'sort_order'             => intval($data['sort_order'] ?? 0),
                'stock_quantity'         => intval($data['product_quantity']),
                'product_quantity'       => intval($data['product_quantity']),
                'out_of_stock_status'    => $data['out_of_stock_status'] ?? 'in_stock',
                'minimum_quantity'       => intval($data['minimum_quantity'] ?? 1),
                'bulk_price_variance'    => !empty($bulkPricing) ? json_encode($bulkPricing) : '',
                'sku'                    => trim($data['sku_code']),

                // Tab 2 – Filters & Master Attributes
                'color'                  => !empty($colorAttributes) ? implode(', ', array_column($colorAttributes, 'name')) : trim($data['color'] ?? ''),
                'type'                   => trim($data['type'] ?? ''),
                'material'               => trim($data['material'] ?? ''),
                'occasion'               => trim($data['occasion'] ?? ''),
                'discount_type'          => trim($data['discount_type'] ?? ''),
                'brand'                  => trim($data['brand'] ?? ''),
                'shape'                  => trim($data['shape'] ?? ''),
                'gender'                 => trim($data['gender'] ?? ''),
                'gift_type'              => trim($data['gift_type'] ?? ''),
                'ideal_for'              => trim($data['ideal_for'] ?? ''),
                'customization_tech'     => trim($data['customization_tech'] ?? ''),
                'customization_location' => trim($data['customization_location'] ?? ''),
                'capacity'               => trim($data['capacity'] ?? ''),
                'ink_color'              => trim($data['ink_color'] ?? ''),
                'features'               => trim($data['features'] ?? ''),

                // Tab 3 – Category links
                'category_id'            => !empty($data['category_id']) ? intval($data['category_id']) : null,
                'sub_category_id'        => !empty($data['sub_category_id']) ? intval($data['sub_category_id']) : null,
                'sub_sub_category_id'    => !empty($data['sub_sub_category_id']) ? intval($data['sub_sub_category_id']) : null,
                'additional_sub_category_ids' => isset($data['additional_sub_category_ids']) ? json_encode(array_map('intval', $data['additional_sub_category_ids'])) : null,
                'additional_sub_sub_category_ids' => isset($data['additional_sub_sub_category_ids']) ? json_encode(array_map('intval', $data['additional_sub_sub_category_ids'])) : null,
                'homepage_category_id'   => !empty($data['homepage_category_id']) ? intval($data['homepage_category_id']) : null,
                'homepage_carousel_id'   => !empty($data['homepage_carousel_id']) ? intval($data['homepage_carousel_id']) : null,
                'status'                 => $data['status'] ?? 'active',
                'featured'               => isset($data['featured']) ? 1 : 0,
                'top_selection'          => isset($data['top_selection']) ? 1 : 0,
                'our_bestseller'         => isset($data['our_bestseller']) ? 1 : 0,
                'top_rated'              => isset($data['top_rated']) ? 1 : 0,
                'top_deal_by_categories' => isset($data['top_deal_by_categories']) ? 1 : 0,
                'is_assured'             => isset($data['is_assured']) ? intval($data['is_assured']) : 1,

                // Tab 4 – Images
                'alt_tag'                => trim($data['alt_tag'] ?? ''),
                'thumbnail_image'        => $thumbnailImage,
                'gallery_images'         => !empty($galleryImages) ? json_encode(array_values($galleryImages)) : null,

                // Tab 6 – SEO
                'meta_title'             => trim($data['meta_title'] ?? ''),
                'meta_keywords'          => trim($data['meta_keywords'] ?? ''),
                'meta_description'       => trim($data['meta_description'] ?? ''),

                // Tab 7 – Status Show and Hide Button
                'show_cart_buttons'      => isset($data['show_cart_buttons']) ? 1 : 0,
                'show_quantity'          => isset($data['show_quantity']) ? 1 : 0,
                'show_help_button'       => isset($data['show_help_button']) ? 1 : 0,
                'show_bulk_form'         => isset($data['show_bulk_form']) ? 1 : 0,
                'show_inquiry_form'      => isset($data['show_inquiry_form']) ? 1 : 0,
                'size_chart_image'       => $sizeChartImage,

                // Tab 8 – Customization
                'show_customization_label' => isset($data['show_customization_label']) ? 1 : 0,
                'enable_upload_design'   => isset($data['enable_upload_design']) ? 1 : 0,
                'upload_design_max_images' => isset($data['upload_design_max_images']) ? max(1, (int) $data['upload_design_max_images']) : 4,
                'upload_design_max_texts' => isset($data['upload_design_max_texts']) ? max(0, (int) $data['upload_design_max_texts']) : 2,
                'enable_customizer'      => isset($data['enable_customizer']) ? 1 : 0,
                'customizer_template_id' => !empty($data['customizer_template_id']) ? (int) $data['customizer_template_id'] : null,
                'customization_label'    => trim($data['customization_label'] ?? ''),
                'customization_fields'   => !empty($customizationFields) ? json_encode($customizationFields) : null,

                // Tab 9 – Additional Product Attributes
                'product_attributes_fields' => !empty($productAttributeFields) ? json_encode($productAttributeFields) : null,

                // Tab 8 – Addons
                'addon_product_ids'      => $addonIds,

                // Tab 9 – Attributes
                'show_sizes'             => isset($data['show_sizes']) ? 1 : 0,
                'show_colors'            => isset($data['show_colors']) ? 1 : 0,
                'size_attributes'        => !empty($sizeAttributes) ? json_encode($sizeAttributes) : null,
                'color_attributes'       => !empty($colorAttributes) ? json_encode($colorAttributes) : null,
                'color_images'           => !empty($colorImagesMap) ? json_encode($colorImagesMap) : null,
                'material_attributes'    => !empty($data['attr_material']) ? json_encode([$data['attr_material']]) : null,
                'lamination_attributes'  => !empty($data['attr_lamination']) ? json_encode([$data['attr_lamination']]) : null,
                'orientation_attributes' => !empty($data['attr_orientation']) ? json_encode([$data['attr_orientation']]) : null,
                'quantity_price_breaks'  => !empty($quantityPricing) ? json_encode($quantityPricing) : null,

                // Tab 10 – Shipping
                'shipping_method_status'    => isset($data['shipping_method_status']) ? 1 : 0,
                'local_shipping_charge'     => floatval($data['local_shipping_charge'] ?? 0),
                'local_shipping_message'    => trim($data['local_shipping_message'] ?? ''),
                'regional_shipping_charge'  => floatval($data['regional_shipping_charge'] ?? 0),
                'regional_shipping_message' => trim($data['regional_shipping_message'] ?? ''),
                'national_shipping_charge'  => floatval($data['national_shipping_charge'] ?? 0),
                'national_shipping_message' => trim($data['national_shipping_message'] ?? ''),

                // Tab 11 – Cancel
                'cancel_available'       => isset($data['cancel_available']) ? 1 : 0,
                'cancel_type'            => $data['cancel_type'] ?? 'hours',
                'cancel_time'            => intval($data['cancel_time'] ?? 24),

                // Tab 12 – COD
                'cod_available'          => isset($data['cod_available']) ? 1 : 0,

                // Meta
                'view_count'             => 0,
                'created_at'             => date('Y-m-d H:i:s'),
                'updated_at'             => date('Y-m-d H:i:s'),
            ];

            $productId = $this->productModel->createProduct($productData, $formattedMainImages);

            // Sync multi-category assignments in product_categories
            $this->syncProductCategories($productId, $data['category_id'] ?? 0, $data['additional_category_ids'] ?? []);

            // Tab 1, point 13 – Product Information Image & Text Upload (shown above Ratings & Reviews)
            $this->saveProductInfoBanners($productId, $data, $files);

            return ['success' => true, 'product_id' => $productId, 'message' => 'Product added successfully!'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function syncProductCategories($productId, $primaryCategoryId, $additionalCategoryIds)
    {
        if (!$this->db || $productId <= 0) return;
        
        $allCats = [];
        if (!empty($primaryCategoryId)) {
            $allCats[] = (int)$primaryCategoryId;
        }
        if (!empty($additionalCategoryIds) && is_array($additionalCategoryIds)) {
            foreach ($additionalCategoryIds as $cid) {
                $cid = (int)$cid;
                if ($cid > 0 && !in_array($cid, $allCats)) {
                    $allCats[] = $cid;
                }
            }
        }
        
        $del = $this->db->prepare("DELETE FROM product_categories WHERE product_id = ?");
        if ($del) {
            $del->bind_param('i', $productId);
            $del->execute();
            $del->close();
        }
        
        if (!empty($allCats)) {
            $ins = $this->db->prepare("INSERT INTO product_categories (product_id, category_id, is_primary) VALUES (?, ?, ?)");
            if ($ins) {
                foreach ($allCats as $cid) {
                    $isPrimary = ($cid == (int)$primaryCategoryId) ? 1 : 0;
                    $ins->bind_param('iii', $productId, $cid, $isPrimary);
                    $ins->execute();
                }
                $ins->close();
            }
        }
    }

    private function uploadImage($file, $type = 'product')
    {
        if (empty($file) || empty($file['name'])) return '';
        $relativeDir = 'uploads/products/' . trim((string)$type, '/');
        $result = saveUploadedImageAsWebp(
            $file,
            __DIR__ . '/../' . $relativeDir,
            $relativeDir,
            ['max_bytes' => 5 * 1024 * 1024, 'quality' => 84]
        );
        if (!$result['success']) {
            throw new Exception($result['error']);
        }
        return $result['path'];
    }

    private function uploadMultipleImages($files, $type = 'product')
    {
        $uploadedImages = [];

        foreach ($files['tmp_name'] as $key => $tmp_name) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$key],
                    'type' => $files['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];

                try {
                    $imagePath = $this->uploadImage($file, $type);
                    if ($imagePath) {
                        $uploadedImages[] = $imagePath;
                    }
                } catch (Exception $e) {
                    // Log error but continue with other images
                    error_log("Failed to upload image {$files['name'][$key]}: " . $e->getMessage());
                }
            }
        }

        return $uploadedImages;
    }

    private function uploadInfoBannerImage($file)
    {
        if (empty($file) || empty($file['name'])) return '';
        $relativeDir = 'uploads/products/info-banners';
        $result = saveUploadedImageAsWebp(
            $file,
            __DIR__ . '/../' . $relativeDir,
            $relativeDir,
            ['max_bytes' => 5 * 1024 * 1024, 'quality' => 84]
        );
        if (!$result['success']) throw new Exception($result['error']);
        return $result['path'];
    }

    /**
     * Upload an individual product color variant image (uploads/products/variants/)
     */
    private function uploadVariantImage($filesArray, $index, $colorName)
    {
        $tmpName = $filesArray['tmp_name'][$index] ?? '';
        $origName = $filesArray['name'][$index] ?? '';
        if (empty($tmpName) || !file_exists($tmpName)) return null;

        $relativeDir = 'uploads/products/variants';
        $file = [
            'name' => $origName,
            'type' => $filesArray['type'][$index] ?? '',
            'tmp_name' => $tmpName,
            'error' => $filesArray['error'][$index] ?? UPLOAD_ERR_OK,
            'size' => $filesArray['size'][$index] ?? (filesize($tmpName) ?: 0),
        ];
        $result = saveUploadedImageAsWebp(
            $file,
            __DIR__ . '/../' . $relativeDir,
            $relativeDir,
            ['max_bytes' => 5 * 1024 * 1024, 'quality' => 84]
        );
        return $result['success'] ? $result['path'] : null;
    }

    /**
     * Tab 1, point 13 – Product Information Image & Text Upload.
     * Shown on the product page above Ratings & Reviews (frontend: ProductInfoBanners.jsx).
     * Rows post as parallel arrays (info_banner_title[], info_banner_image[], ...), one entry
     * per banner; a blank/removed row is skipped. Replaces the whole set for this product on
     * every save, deleting only the image files that were actually dropped or replaced.
     */
    private function saveProductInfoBanners($productId, $data, $files)
    {
        if (!$this->db || $productId <= 0) return;
        ensureProductInfoBannersTable($this->db);

        $titles       = (array) ($data['info_banner_title'] ?? []);
        $descriptions = (array) ($data['info_banner_description'] ?? []);
        $buttonTexts  = (array) ($data['info_banner_button_text'] ?? []);
        $buttonLinks  = (array) ($data['info_banner_button_link'] ?? []);
        $existingImgs = (array) ($data['info_banner_existing_image'] ?? []);
        $removeFlags  = (array) ($data['info_banner_remove'] ?? []);
        $fileNames    = $files['info_banner_image']['name'] ?? [];

        $rowCount = max(count($titles), count($existingImgs), count($fileNames), count($descriptions));

        $oldImages = [];
        $existingRows = $this->db->query("SELECT image FROM product_info_banners WHERE product_id = " . (int) $productId);
        while ($existingRows && ($row = $existingRows->fetch_assoc())) {
            if (!empty($row['image'])) $oldImages[] = $row['image'];
        }

        $keptImages = [];
        $rowsToInsert = [];

        for ($i = 0; $i < $rowCount; $i++) {
            if (!empty($removeFlags[$i])) continue;

            $title = trim($titles[$i] ?? '');
            $description = trim($descriptions[$i] ?? '');
            $buttonText = trim($buttonTexts[$i] ?? '');
            $buttonLink = trim($buttonLinks[$i] ?? '');
            $existingImage = trim($existingImgs[$i] ?? '');

            $uploadedImage = '';
            if (!empty($fileNames[$i])) {
                $uploadedImage = $this->uploadInfoBannerImage([
                    'name'     => $files['info_banner_image']['name'][$i],
                    'type'     => $files['info_banner_image']['type'][$i],
                    'tmp_name' => $files['info_banner_image']['tmp_name'][$i],
                    'error'    => $files['info_banner_image']['error'][$i],
                    'size'     => $files['info_banner_image']['size'][$i],
                ]);
            }

            $image = $uploadedImage ?: $existingImage;
            if ($title === '' && $description === '' && $buttonText === '' && $image === '') {
                continue; // blank trailing row
            }

            if ($image !== '') $keptImages[] = $image;
            $rowsToInsert[] = [$title, $description, $buttonText, $buttonLink, $image];
        }

        $this->db->query("DELETE FROM product_info_banners WHERE product_id = " . (int) $productId);

        foreach ($oldImages as $oldImage) {
            if (!in_array($oldImage, $keptImages, true) && file_exists($oldImage)) {
                @unlink($oldImage);
            }
        }

        if (!empty($rowsToInsert)) {
            $stmt = $this->db->prepare(
                "INSERT INTO product_info_banners (product_id, title, description, button_text, button_link, image, display_order, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'active')"
            );
            foreach ($rowsToInsert as $order => $row) {
                [$title, $description, $buttonText, $buttonLink, $image] = $row;
                $stmt->bind_param('isssssi', $productId, $title, $description, $buttonText, $buttonLink, $image, $order);
                $stmt->execute();
            }
            $stmt->close();
        }
    }

    private function getUploadError($errorCode)
    {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
        ];

        return $upload_errors[$errorCode] ?? 'Unknown upload error.';
    }

    public function getProductById($id)
    {
        // $productModel = new ProductModel($this->$db);
        return $this->productModel->getProductById($id);
    }

    public function getAllProducts()
    {
        return $this->productModel->getAllProducts();
    }

    /**
     * Customization options from the admin form (product.php → section 7), one entry per option row:
     *   customization_labels[]  "Fabric Colour"
     *   customization_types[]   select | radio | color | multi | text | textarea | number | image | both
     *   customization_required[] 0/1 (one hidden value per row, so rows never shift)
     *   customization_help[]    short hint shown under the field
     *   customization_options[] JSON list of choices: [{"label":"White","hex":"#ffffff","price":0}, ...]
     */
    private function buildCustomizationFields($data)
    {
        // product.php section 7 posts two things, both saved:
        //  1. ticked library options as one JSON list (customization_assignments):
        //     [{"option_id":5,"required":1,"values":[{"label":"White","price":0}, ...]}, ...]
        //  2. key-value rows written for this product only (customization_labels[] etc., below)
        $assigned = [];
        if (isset($data['customization_assignments'])) {
            $library = $this->getCustomizationLibrary();
            $assigned = [];
            foreach ((json_decode($data['customization_assignments'], true) ?: []) as $a) {
                $id = (int) ($a['option_id'] ?? 0);
                $master = $library[$id] ?? null;
                if (!$master || isset($assigned[$id])) continue;

                $entry = ['option_id' => $id, 'required' => !empty($a['required']) ? 1 : 0];
                if (in_array($master['input_type'], customizationChoiceTypes(), true)) {
                    $known = [];
                    foreach ($master['values'] as $mv) $known[mb_strtolower($mv['label'])] = $mv['label'];
                    $values = [];
                    foreach ((array) ($a['values'] ?? []) as $v) {
                        $key = mb_strtolower(trim((string) ($v['label'] ?? '')));
                        if (!isset($known[$key])) continue;
                        $price = $v['price'] ?? '';
                        $values[] = ['label' => $known[$key], 'price' => ($price === '' || $price === null) ? null : round(max(0, (float) $price), 2)];
                    }
                    if (empty($values)) continue;
                    $entry['values'] = $values;
                }
                $assigned[$id] = $entry;
            }
        }
        $assigned = array_values($assigned);

        $allowedTypes = ['select', 'radio', 'color', 'multi', 'text', 'textarea', 'number', 'image', 'both'];
        // (key-value rows written on the product itself)
        $choiceTypes  = ['select', 'radio', 'color', 'multi'];
        $fields = [];

        foreach ((array) ($data['customization_labels'] ?? []) as $i => $label) {
            $label = trim((string) $label);
            if ($label === '') continue;

            $type = $data['customization_types'][$i] ?? 'text';
            if ($type === 'file') $type = 'image';
            if (!in_array($type, $allowedTypes, true)) $type = 'text';

            $field = [
                'label'      => $label,
                'type'       => $type,
                'required'   => !empty($data['customization_required'][$i]) ? 1 : 0,
                'help'       => trim((string) ($data['customization_help'][$i] ?? '')),
                'price'      => floatval($data['customization_prices'][$i] ?? 0),
                'max_images' => intval($data['customization_max_images'][$i] ?? 1),
            ];

            if (in_array($type, $choiceTypes, true)) {
                $options = [];
                foreach ((json_decode($data['customization_options'][$i] ?? '[]', true) ?: []) as $opt) {
                    $optLabel = trim((string) ($opt['label'] ?? ''));
                    if ($optLabel === '') continue;
                    $hex = (string) ($opt['hex'] ?? '');
                    $options[] = [
                        'label' => $optLabel,
                        'hex'   => preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? $hex : '',
                        'price' => floatval($opt['price'] ?? 0),
                    ];
                }
                if (empty($options)) continue; // a dropdown with nothing to choose is useless
                $field['options'] = $options;
            }

            $fields[] = $field;
        }

        // Library options first, then the product's own key-value rows
        return array_merge($assigned, $fields);
    }

    /**
     * Additional Product Attributes from the admin form (product.php → Tab 9), one entry per row:
     *   attribute_labels[]    "Weight"
     *   attribute_types[]     select | radio | color | multi | text | textarea | number | image | both
     *   attribute_required[]  0/1 (one hidden value per row, so rows never shift)
     *   attribute_help[]      short hint shown under the field
     *   attribute_options[]   JSON list of choices: [{"label":"Matte","hex":"","price":0}, ...]
     * Separate from customization_fields (Tab 7) — these describe the product itself rather than
     * something the customer personalizes, and are stored in their own column.
     */
    private function buildProductAttributeFields($data)
    {
        $allowedTypes = ['select', 'radio', 'color', 'multi', 'text', 'textarea', 'number', 'image', 'both'];
        $choiceTypes  = ['select', 'radio', 'color', 'multi'];
        $fields = [];

        foreach ((array) ($data['attribute_labels'] ?? []) as $i => $label) {
            $label = trim((string) $label);
            if ($label === '') continue;

            $type = $data['attribute_types'][$i] ?? 'text';
            if (!in_array($type, $allowedTypes, true)) $type = 'text';

            $field = [
                'label'    => $label,
                'type'     => $type,
                'required' => !empty($data['attribute_required'][$i]) ? 1 : 0,
                'help'     => trim((string) ($data['attribute_help'][$i] ?? '')),
            ];

            if (in_array($type, $choiceTypes, true)) {
                $options = [];
                foreach ((json_decode($data['attribute_options'][$i] ?? '[]', true) ?: []) as $opt) {
                    $optLabel = trim((string) ($opt['label'] ?? ''));
                    if ($optLabel === '') continue;
                    $hex = (string) ($opt['hex'] ?? '');
                    $options[] = [
                        'label' => $optLabel,
                        'hex'   => preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? $hex : '',
                        'price' => floatval($opt['price'] ?? 0),
                    ];
                }
                if (empty($options)) continue;
                $field['options'] = $options;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    public function updateProduct($id, $data, $files)
    {
        try {
            // Get existing product
            $existingProduct = $this->getProductById($id);
            if (!$existingProduct) {
                throw new Exception("Product not found");
            }

            // The admin edit form (product.php?id=) posts every field and sets full_form=1.
            // Then an unchecked checkbox means 0 and an emptied list means "clear it";
            // other callers keep the old "missing field = keep existing value" behaviour.
            $fullForm = !empty($data['full_form']);
            $flag = function ($key, $default = 0) use ($data, $existingProduct, $fullForm) {
                if (isset($data[$key])) return 1;
                return $fullForm ? 0 : ($existingProduct[$key] ?? $default);
            };
            $listValue = function ($list, $key) use ($existingProduct, $fullForm) {
                if (!empty($list)) return json_encode($list);
                return $fullForm ? '[]' : ($existingProduct[$key] ?? null);
            };

            // Handle image uploads
            $thumbnailImage = $existingProduct['thumbnail_image'] ?? '';
            if (!empty($data['remove_thumbnail']) && empty($files['thumbnail_image']['name'])) {
                $thumbnailImage = '';
            }
            $mainImages = [];
            $galleryImages = json_decode($existingProduct['gallery_images'] ?? '[]', true) ?: [];

            // Upload new thumbnail if provided
            if (!empty($files['thumbnail_image']['name'])) {
                // Delete old thumbnail if exists
                if ($thumbnailImage && file_exists($thumbnailImage)) {
                    unlink($thumbnailImage);
                }
                $thumbnailImage = $this->uploadImage($files['thumbnail_image'], 'thumbnail');
            }

            // Size chart image handling
            $sizeChartImage = $existingProduct['size_chart_image'] ?? null;
            if (!empty($data['remove_size_chart_image']) && empty($files['size_chart_image']['name'])) {
                if ($sizeChartImage && file_exists($sizeChartImage)) {
                    @unlink($sizeChartImage);
                }
                $sizeChartImage = null;
            }
            if (!empty($files['size_chart_image']['name'])) {
                if ($sizeChartImage && file_exists($sizeChartImage)) {
                    @unlink($sizeChartImage);
                }
                try {
                    $sizeChartImage = $this->uploadImage($files['size_chart_image'], 'size_charts');
                } catch (Exception $e) {
                    error_log("Failed to upload size chart image: " . $e->getMessage());
                }
            }

            // Handle existing main images from form
            $existingMainImages = isset($data['existing_main_images']) ? (array) $data['existing_main_images'] : [];

            // Upload new main images if provided
            $newMainImages = [];
            if (!empty($files['main_images']['name'][0])) {
                $newMainImages = $this->uploadMultipleImages($files['main_images'], 'main');
            }

            // Prepare main images array for database
            // Add existing main images first
            foreach ($existingMainImages as $index => $imageUrl) {
                if (!empty($imageUrl)) {
                    $mainImages[] = [
                        'image_url' => $imageUrl,
                        'is_primary' => ($index === 0), // First existing image is primary
                        'display_order' => $index
                    ];
                }
            }

            // Add new main images
            foreach ($newMainImages as $index => $imagePath) {
                $mainImages[] = [
                    'image_url' => $imagePath,
                    'is_primary' => (empty($existingMainImages) && $index === 0), // First new image is primary if no existing images
                    'display_order' => count($existingMainImages) + $index
                ];
            }

            // Handle gallery images
            $existingGalleryImages = isset($data['existing_gallery_images']) ? (array) $data['existing_gallery_images'] : [];

            // Upload new gallery images if provided
            if (!empty($files['gallery_images']['name'][0])) {
                $newGalleryImages = $this->uploadMultipleImages($files['gallery_images'], 'gallery');
                $galleryImages = array_merge($existingGalleryImages, $newGalleryImages);
            } else {
                $galleryImages = $existingGalleryImages;
            }

            // Remove empty values from gallery images
            $galleryImages = array_filter($galleryImages);

            // Handle additional categories (ids stored as JSON integers so JSON_CONTAINS type-matches
            // the integer category id passed in from storefront category queries)
            $additionalCategoryIds = isset($data['additional_category_ids']) ? json_encode(array_map('intval', $data['additional_category_ids'])) : ($existingProduct['additional_category_ids'] ?? null);
            $additionalSubCategoryIds = isset($data['additional_sub_category_ids']) ? json_encode(array_map('intval', $data['additional_sub_category_ids'])) : ($existingProduct['additional_sub_category_ids'] ?? null);
            $additionalSubSubCategoryIds = isset($data['additional_sub_sub_category_ids']) ? json_encode(array_map('intval', $data['additional_sub_sub_category_ids'])) : ($existingProduct['additional_sub_sub_category_ids'] ?? null);

            // Handle customization fields
            $customizationFields = $this->buildCustomizationFields($data);

            // Handle additional product attributes (Tab 9)
            $productAttributeFields = $this->buildProductAttributeFields($data);

            // Handle bulk pricing
            $bulkPricing = [];
            if (!empty($data['bulk_min_qty']) && is_array($data['bulk_min_qty'])) {
                foreach ($data['bulk_min_qty'] as $i => $minQty) {
                    if ($minQty !== '') {
                        $bulkPricing[] = [
                            'min_qty' => intval($minQty),
                            'max_qty' => intval($data['bulk_max_qty'][$i] ?? 0),
                            'price'   => floatval($data['bulk_price'][$i] ?? 0),
                        ];
                    }
                }
            }

            // Handle size attributes
            $sizeAttributes = [];
            if (!empty($data['size_names']) && is_array($data['size_names'])) {
                foreach ($data['size_names'] as $index => $size) {
                    if (!empty($size)) {
                        $sizeAttributes[] = [
                            'name'  => $size,
                            'price' => floatval($data['size_prices'][$index] ?? 0),
                        ];
                    }
                }
            } elseif (!empty($data['sizes']) && is_array($data['sizes'])) {
                foreach ($data['sizes'] as $index => $size) {
                    if (!empty($size)) {
                        $sizeAttributes[] = [
                            'name'  => $size,
                            'price' => floatval($data['size_prices'][$index] ?? 0),
                        ];
                    }
                }
            }

            // Handle color attributes & variant images
            $colorAttributes = [];
            $colorImagesMap = [];
            if (!empty($data['variant_color_name']) && is_array($data['variant_color_name'])) {
                foreach ($data['variant_color_name'] as $index => $color) {
                    $color = trim($color);
                    if ($color === '') continue;
                    $hex = $data['variant_color_hex'][$index] ?? '#000000';
                    $price = floatval($data['variant_color_price'][$index] ?? 0);
                    $text = trim($data['variant_color_text'][$index] ?? '');
                    $imagePath = trim($data['existing_variant_color_images'][$index] ?? '');

                    if (isset($files['variant_color_images']['error'][$index]) && $files['variant_color_images']['error'][$index] === UPLOAD_ERR_OK) {
                        $uploadedPath = $this->uploadVariantImage($files['variant_color_images'], $index, $color);
                        if ($uploadedPath) {
                            $imagePath = $uploadedPath;
                        }
                    }

                    $colorAttributes[] = [
                        'name'  => $color,
                        'hex'   => $hex,
                        'price' => $price,
                        'image' => $imagePath,
                        'text'  => $text,
                    ];
                    if (!empty($imagePath)) {
                        $colorImagesMap[$color] = $imagePath;
                    }
                }
            } elseif (!empty($data['color_names']) && is_array($data['color_names'])) {
                foreach ($data['color_names'] as $index => $color) {
                    $color = trim($color);
                    if ($color !== '') {
                        $colorAttributes[] = [
                            'name'  => $color,
                            'hex'   => $data['color_hexes'][$index] ?? ($data['color_codes'][$index] ?? '#000000'),
                            'price' => floatval($data['color_prices'][$index] ?? 0),
                            'image' => '',
                            'text'  => '',
                        ];
                    }
                }
            } elseif (!empty($data['colors']) && is_array($data['colors'])) {
                foreach ($data['colors'] as $index => $color) {
                    if (!empty($color)) {
                        $colorAttributes[] = [
                            'name'  => is_array($color) ? ($color['name'] ?? '') : $color,
                            'hex'   => is_array($color) ? ($color['hex'] ?? '#000000') : '#000000',
                            'price' => is_array($color) ? floatval($color['price'] ?? 0) : floatval($data['color_prices'][$index] ?? 0),
                            'image' => is_array($color) ? ($color['image'] ?? '') : '',
                            'text'  => is_array($color) ? ($color['text'] ?? '') : '',
                        ];
                    }
                }
            }

            // Handle material attributes
            $materialAttributes = [];
            if (!empty($data['attr_material'])) {
                $materialAttributes = array_filter(array_map('trim', explode(',', $data['attr_material'])));
            } elseif (isset($data['materials']) && is_array($data['materials'])) {
                foreach ($data['materials'] as $index => $material) {
                    if (!empty($material)) {
                        $materialAttributes[] = is_array($material) ? ($material['material'] ?? '') : $material;
                    }
                }
            }

            // Handle lamination attributes
            $laminationAttributes = [];
            if (!empty($data['attr_lamination'])) {
                $laminationAttributes = array_filter(array_map('trim', explode(',', $data['attr_lamination'])));
            } elseif (isset($data['laminations']) && is_array($data['laminations'])) {
                foreach ($data['laminations'] as $index => $lamination) {
                    if (!empty($lamination)) {
                        $laminationAttributes[] = is_array($lamination) ? ($lamination['lamination'] ?? '') : $lamination;
                    }
                }
            }

            // Handle orientation attributes
            $orientationAttributes = [];
            if (!empty($data['attr_orientation'])) {
                $orientationAttributes = [$data['attr_orientation']];
            } elseif (isset($data['orientations']) && is_array($data['orientations'])) {
                foreach ($data['orientations'] as $index => $orientation) {
                    if (!empty($orientation)) {
                        $orientationAttributes[] = is_array($orientation) ? ($orientation['orientation'] ?? '') : $orientation;
                    }
                }
            }

            // Handle printing location
            $printingLocations = [];
            if (!empty($data['attr_printing_location'])) {
                $printingLocations = array_filter(array_map('trim', explode(',', $data['attr_printing_location'])));
            } elseif (isset($data['printing_locations']) && is_array($data['printing_locations'])) {
                foreach ($data['printing_locations'] as $index => $pl) {
                    if (!empty($pl)) {
                        $printingLocations[] = is_array($pl) ? ($pl['name'] ?? '') : $pl;
                    }
                }
            }

            // Handle quantity price breaks
            $quantityPriceBreaks = [];
            if (!empty($data['qty_tier_qty']) && is_array($data['qty_tier_qty'])) {
                foreach ($data['qty_tier_qty'] as $index => $qty) {
                    if ($qty !== '') {
                        $quantityPriceBreaks[] = [
                            'qty'   => intval($qty),
                            'price' => floatval($data['qty_tier_price'][$index] ?? 0),
                        ];
                    }
                }
            } elseif (isset($data['quantity_breaks']) && is_array($data['quantity_breaks'])) {
                foreach ($data['quantity_breaks'] as $index => $minQty) {
                    if (!empty($minQty)) {
                        $quantityPriceBreaks[] = [
                            'qty'   => intval($minQty),
                            'price' => floatval($data['quantity_break_prices'][$index] ?? 0),
                        ];
                    }
                }
            }

            // Handle addon products
            $addonProductIds = isset($data['addon_product_ids']) ? (is_array($data['addon_product_ids']) ? json_encode($data['addon_product_ids']) : $data['addon_product_ids']) : ($existingProduct['addon_product_ids'] ?? null);

            // Prepare update data with ALL database fields
            $updateData = [
                // Basic Information
                'name' => trim($data['product_name'] ?? $existingProduct['name']),
                'product_slug' => trim($data['product_slug'] ?? $existingProduct['product_slug']),
                'description' => trim($data['description'] ?? $existingProduct['description'] ?? ''),
                'long_description' => trim($data['long_description'] ?? $existingProduct['long_description'] ?? ''),
                'instructions' => trim($data['instructions'] ?? $existingProduct['instructions'] ?? ''),
                'delivery_info' => trim($data['delivery_info'] ?? $existingProduct['delivery_info'] ?? ''),
                'alt_tag' => trim($data['alt_tag'] ?? $existingProduct['alt_tag'] ?? ''),

                // SEO
                'meta_title' => trim($data['meta_title'] ?? $existingProduct['meta_title'] ?? ''),
                'meta_keywords' => trim($data['meta_keywords'] ?? $existingProduct['meta_keywords'] ?? ''),
                'meta_description' => trim($data['meta_description'] ?? $existingProduct['meta_description'] ?? ''),

                // Images
                'thumbnail_image' => $thumbnailImage,
                'gallery_images' => !empty($galleryImages) ? json_encode(array_values($galleryImages)) : null,

                // Categories
                'category_id' => !empty($data['category_id']) ? $data['category_id'] : ($existingProduct['category_id'] ?? null),
                'sub_category_id' => !empty($data['sub_category_id']) ? $data['sub_category_id'] : ($existingProduct['sub_category_id'] ?? null),
                'sub_sub_category_id' => !empty($data['sub_sub_category_id']) ? $data['sub_sub_category_id'] : ($existingProduct['sub_sub_category_id'] ?? null),
                'homepage_category_id' => !empty($data['homepage_category_id']) ? $data['homepage_category_id'] : ($existingProduct['homepage_category_id'] ?? null),
                'homepage_carousel_id' => $existingProduct['homepage_carousel_id'] ?? null, // Add if you have this field in form
                'additional_category_ids' => $additionalCategoryIds,
                'additional_sub_category_ids' => $additionalSubCategoryIds,
                'additional_sub_sub_category_ids' => $additionalSubSubCategoryIds,

                // Filters & Attributes
                'brand' => trim($data['brand'] ?? $existingProduct['brand'] ?? ''),
                'color' => !empty($colorAttributes) ? implode(', ', array_column($colorAttributes, 'name')) : trim($data['color'] ?? $existingProduct['color'] ?? ''),
                'type' => trim($data['type'] ?? $existingProduct['type'] ?? ''),
                'material' => trim($data['material'] ?? $existingProduct['material'] ?? ''),
                'occasion' => trim($data['occasion'] ?? $existingProduct['occasion'] ?? ''),
                'discount_type' => trim($data['discount_type'] ?? $existingProduct['discount_type'] ?? ''),
                'shape' => trim($data['shape'] ?? $existingProduct['shape'] ?? ''),
                'gender' => trim($data['gender'] ?? $existingProduct['gender'] ?? ''),
                'gift_type' => trim($data['gift_type'] ?? $existingProduct['gift_type'] ?? ''),
                'ideal_for' => trim($data['ideal_for'] ?? $existingProduct['ideal_for'] ?? ''),
                'customization_tech' => trim($data['customization_tech'] ?? $existingProduct['customization_tech'] ?? ''),
                'customization_location' => trim($data['customization_location'] ?? $existingProduct['customization_location'] ?? ''),
                'capacity' => trim($data['capacity'] ?? $existingProduct['capacity'] ?? ''),
                'ink_color' => trim($data['ink_color'] ?? $existingProduct['ink_color'] ?? ''),
                'features' => trim($data['features'] ?? $existingProduct['features'] ?? ''),

                // Pricing & Inventory
                'price' => floatval($data['regular_price'] ?? $existingProduct['price']),
                'regular_price' => floatval($data['regular_price'] ?? $existingProduct['regular_price'] ?? $existingProduct['price']),
                'offer_price' => !empty($data['offer_price']) ? floatval($data['offer_price']) : ($fullForm ? 0 : ($existingProduct['offer_price'] ?? null)),
                'discount_price' => !empty($data['offer_price']) ? floatval($data['offer_price']) : ($fullForm ? 0 : ($existingProduct['discount_price'] ?? null)),
                'sort_order' => intval($data['sort_order'] ?? $existingProduct['sort_order'] ?? 0),
                'stock_quantity' => intval($data['product_quantity'] ?? $existingProduct['stock_quantity']),
                'product_quantity' => intval($data['product_quantity'] ?? $existingProduct['product_quantity'] ?? $existingProduct['stock_quantity']),
                'out_of_stock_status' => $data['out_of_stock_status'] ?? $existingProduct['out_of_stock_status'] ?? 'in_stock',
                'minimum_quantity' => intval($data['minimum_quantity'] ?? $existingProduct['minimum_quantity'] ?? 1),
                'bulk_price_variance' => !empty($bulkPricing) ? json_encode($bulkPricing) : ($fullForm ? '[]' : (isset($data['bulk_price_variance']) ? trim($data['bulk_price_variance']) : ($existingProduct['bulk_price_variance'] ?? ''))),
                'sku' => trim($data['sku_code'] ?? $existingProduct['sku']),

                // Status & Display (Tab 7)
                'status' => $data['status'] ?? $existingProduct['status'] ?? 'active',
                'show_cart_buttons' => $flag('show_cart_buttons', 1),
                'show_quantity' => $flag('show_quantity', 1),
                'show_help_button' => $flag('show_help_button', 0),
                'show_bulk_form' => $flag('show_bulk_form', 0),
                'show_inquiry_form' => $flag('show_inquiry_form', 0),
                'size_chart_image' => $sizeChartImage,
                'help_button_text' => trim($data['help_button_text'] ?? $existingProduct['help_button_text'] ?? 'Need Help?'),
                'help_button_link' => trim($data['help_button_link'] ?? $existingProduct['help_button_link'] ?? '/contact-us'),

                // Customization
                'show_customization_label' => $flag('show_customization_label', 0),
                'enable_upload_design' => $flag('enable_upload_design', 1),
                'upload_design_max_images' => isset($data['upload_design_max_images']) ? max(1, (int) $data['upload_design_max_images']) : (isset($existingProduct['upload_design_max_images']) ? (int) $existingProduct['upload_design_max_images'] : 4),
                'upload_design_max_texts' => isset($data['upload_design_max_texts']) ? max(0, (int) $data['upload_design_max_texts']) : (isset($existingProduct['upload_design_max_texts']) ? (int) $existingProduct['upload_design_max_texts'] : 2),
                'enable_customizer' => $flag('enable_customizer', 1),
                'customizer_template_id' => !empty($data['customizer_template_id']) ? (int) $data['customizer_template_id'] : null,
                'customization_label' => trim($data['customization_label'] ?? $existingProduct['customization_label'] ?? ''),
                'customization_fields' => $listValue($customizationFields, 'customization_fields'),
                'product_attributes_fields' => $listValue($productAttributeFields, 'product_attributes_fields'),

                // Addons
                'addon_product_ids' => $addonProductIds,

                // Attribute flags
                'show_sizes' => $flag('show_sizes', 1),
                'show_colors' => $flag('show_colors', 1),

                // Attribute values (JSON)
                'size_attributes' => $listValue($sizeAttributes, 'size_attributes'),
                'color_attributes' => $listValue($colorAttributes, 'color_attributes'),
                'color_images' => !empty($colorImagesMap) ? json_encode($colorImagesMap) : ($fullForm ? '{}' : ($existingProduct['color_images'] ?? null)),
                'material_attributes' => $listValue($materialAttributes, 'material_attributes'),
                'lamination_attributes' => $listValue($laminationAttributes, 'lamination_attributes'),
                'orientation_attributes' => $listValue($orientationAttributes, 'orientation_attributes'),
                'printing_location' => $listValue($printingLocations, 'printing_location'),
                'quantity_price_breaks' => $listValue($quantityPriceBreaks, 'quantity_price_breaks'),

                // Shipping
                'shipping_method_status' => $flag('shipping_method_status', 1),
                'local_shipping_charge' => floatval($data['local_shipping_charge'] ?? $existingProduct['local_shipping_charge'] ?? 0),
                'local_shipping_message' => trim($data['local_shipping_message'] ?? $existingProduct['local_shipping_message'] ?? ''),
                'regional_shipping_charge' => floatval($data['regional_shipping_charge'] ?? $existingProduct['regional_shipping_charge'] ?? 0),
                'regional_shipping_message' => trim($data['regional_shipping_message'] ?? $existingProduct['regional_shipping_message'] ?? ''),
                'national_shipping_charge' => floatval($data['national_shipping_charge'] ?? $existingProduct['national_shipping_charge'] ?? 0),
                'national_shipping_message' => trim($data['national_shipping_message'] ?? $existingProduct['national_shipping_message'] ?? ''),

                // Cancellation
                'cancel_available' => $flag('cancel_available', 1),
                'cancel_time' => intval($data['cancel_time'] ?? $existingProduct['cancel_time'] ?? 24),
                'cancel_type' => $data['cancel_type'] ?? $existingProduct['cancel_type'] ?? 'hours',

                // COD
                'cod_available' => $flag('cod_available', 1),

                // Badges/Flags
                'featured' => $flag('featured', 0),
                'top_selection' => $flag('top_selection', 0),
                'our_bestseller' => $flag('our_bestseller', 0),
                'top_rated' => $flag('top_rated', 0),
                'top_deal_by_categories' => $flag('top_deal_by_categories', 0),
                'is_assured' => isset($data['is_assured']) ? 1 : 0,

                // Timestamps
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Remove null values that shouldn't be updated
            $updateData = array_filter($updateData, function ($value) {
                return $value !== null;
            });

            // Update product
            $success = $this->productModel->updateProduct($id, $updateData, $mainImages, $fullForm);

            if ($success) {
                // Sync multi-category assignments in product_categories
                $this->syncProductCategories($id, $data['category_id'] ?? 0, $data['additional_category_ids'] ?? []);
                // Tab 1, point 13 – Product Information Image & Text Upload (shown above Ratings & Reviews)
                $this->saveProductInfoBanners($id, $data, $files);
                return ['success' => true, 'message' => 'Product updated successfully!'];
            } else {
                throw new Exception("Failed to update product");
            }

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


    public function deleteProduct($id)
    {
        return $this->productModel->deleteProduct($id);
    }

    public function getDeactiveProducts()
    {
        $products = $this->productModel->getDeactiveProducts();

        // Format products for API response
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductForApi($product);
        }

        return $formattedProducts;
    }

    // Add to ProductController class
    public function toggleBestseller($productId)
    {
        try {
            $result = $this->productModel->toggleBestseller($productId);

            if ($result) {
                // Get updated product to return current status
                $product = $this->productModel->getProductById($productId);
                return [
                    'success' => true,
                    'message' => 'Bestseller status updated successfully',
                    'is_bestseller' => $product['our_bestseller']
                ];
            } else {
                throw new Exception("Failed to update bestseller status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setBestsellerStatus($productId, $status)
    {
        try {
            $result = $this->productModel->setBestsellerStatus($productId, $status);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Bestseller status updated successfully',
                    'is_bestseller' => $status
                ];
            } else {
                throw new Exception("Failed to update bestseller status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getBestsellerProducts()
    {
        $products = $this->productModel->getBestsellerProducts();

        // Format products for API response
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductForApi($product);
        }

        return $formattedProducts;
    }

    // Add to ProductController class
    public function getProductsWithPagination($search = '', $category_id = '', $status = '', $offset = 0, $limit = 10)
    {
        return $this->productModel->getProductsWithPagination($search, $category_id, $status, $offset, $limit);
    }

    // public function getCategories() {
    //     return $this->categoryModel->getAllCategoriesFlat();
    // }

    // Add these methods to your ProductController class

    // Bestseller API methods
    public function getBestsellerProductsApi()
    {
        $products = $this->productModel->getBestsellerProducts();
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductCardForApi($product);
        }
        return $formattedProducts;
    }

    // Top Selection methods
    public function getTopSelectionProducts()
    {
        return $this->productModel->getTopSelectionProducts();
    }

    public function getTopSelectionProductsApi()
    {
        $products = $this->productModel->getTopSelectionProducts();
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductCardForApi($product);
        }
        return $formattedProducts;
    }

    public function toggleTopSelection($productId)
    {
        try {
            $result = $this->productModel->toggleTopSelection($productId);

            if ($result) {
                $product = $this->productModel->getProductById($productId);
                return [
                    'success' => true,
                    'message' => 'Top Selection status updated successfully',
                    'is_top_selection' => $product['top_selection'] ?? 0
                ];
            } else {
                throw new Exception("Failed to update top selection status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setTopSelectionStatus($productId, $status)
    {
        try {
            $result = $this->productModel->setTopSelectionStatus($productId, $status);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Top Selection status updated successfully',
                    'is_top_selection' => $status
                ];
            } else {
                throw new Exception("Failed to update top selection status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Top Rated methods
    public function getTopRatedProducts()
    {
        return $this->productModel->getTopRatedProducts();
    }

    public function getTopRatedProductsApi()
    {
        $products = $this->productModel->getTopRatedProducts();
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = $this->formatProductCardForApi($product);
        }
        return $formattedProducts;
    }

    public function toggleTopRated($productId)
    {
        try {
            $result = $this->productModel->toggleTopRated($productId);

            if ($result) {
                $product = $this->productModel->getProductById($productId);
                return [
                    'success' => true,
                    'message' => 'Top Rated status updated successfully',
                    'is_top_rated' => $product['top_rated'] ?? 0
                ];
            } else {
                throw new Exception("Failed to update top rated status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setTopRatedStatus($productId, $status)
    {
        try {
            $result = $this->productModel->setTopRatedStatus($productId, $status);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Top Rated status updated successfully',
                    'is_top_rated' => $status
                ];
            } else {
                throw new Exception("Failed to update top rated status");
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Add to ProductController class
    public function getProductsWithFilters($params = [])
    {
        $search = $params['search'] ?? '';
        $category_id = $params['category_id'] ?? '';
        $status = $params['status'] ?? '';
        $featured = $params['featured'] ?? '';
        $bestseller = $params['bestseller'] ?? '';
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 10;
        $offset = ($page - 1) * $limit;

        return $this->productModel->getProductsWithFilters(
            $search,
            $category_id,
            $status,
            $featured,
            $bestseller,
            $offset,
            $limit
        );
    }

    public function getTotalProductsCount($search = '', $category_id = '', $status = '', $featured = '', $bestseller = '')
    {
        return $this->productModel->getTotalProductsCount($search, $category_id, $status, $featured, $bestseller);
    }

    public function getAllCategoriesForFilter()
    {
        return $this->categoryModel->getAllCategoriesFlat();
    }


    // Add to ProductController class

    // Existing methods you already have
    // public function getTopSelectionProductsApi() {
    //     $products = $this->productModel->getTopSelectionProducts();
    //     return ['success' => true, 'data' => $products];
    // }

    // public function getTopRatedProductsApi() {
    //     $products = $this->productModel->getTopRatedProducts();
    //     return ['success' => true, 'data' => $products];
    // }

    // public function getTopDealByCategoriesProductsApi()
    // {
    //     $products = $this->productModel->getTopDealByCategoriesProducts();
    //     return ['success' => true, 'data' => $products];
    // }

    // NEW METHODS TO ADD:
    public function getDiscountProductsApi()
    {
        try {
            $products = $this->productModel->getDiscountProducts();
            if (empty($products)) {
                $products = $this->productModel->getActiveProducts();
            }
            $formattedProducts = [];
            foreach ($products as $product) {
                $formattedProducts[] = $this->formatProductCardForApi($product);
            }
            return ['success' => true, 'data' => $formattedProducts];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getRecentlyViewedApi()
    {
        try {
            $products = $this->productModel->getActiveProducts();
            $formattedProducts = [];
            foreach ($products as $product) {
                $formattedProducts[] = $this->formatProductCardForApi($product);
            }
            return ['success' => true, 'data' => $formattedProducts];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Categories API
    public function getAllCategoriesApi()
    {
        try {
            $categories = $this->categoryModel->getAllWithHierarchy();
            return ['success' => true, 'data' => $categories];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Products by Category Slug API
    public function getProductsByCategoryApi($categorySlug)
    {
        try {
            $products = $this->productModel->getProductsByCategorySlug($categorySlug);
            return ['success' => true, 'data' => $products];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Fix method name - you had typo in method name
    public function getTopDealByCategoriesProductsApi()
    {
        try {
            $products = $this->productModel->getTopDealByCategoriesProducts();
            $formattedProducts = [];
            foreach ($products as $product) {
                $formattedProducts[] = $this->formatProductCardForApi($product);
            }
            return ['success' => true, 'data' => $formattedProducts];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


   

    public function searchProducts($params)
    {
        try {
            $term  = $params['query'] ?? '';
            $limit = isset($params['limit']) ? (int) $params['limit'] : 20;
            $products = $this->productModel->searchProducts($term, $limit);
            $formatted = [];
            foreach ($products as $p) {
                $formatted[] = $this->formatProductForApi($p);
            }
            return $formatted;
        } catch (Exception $e) {
            throw new Exception("Search failed: " . $e->getMessage());
        }
    }

    public function searchProductsForAddon($term, $excludeId = null)
    {
        return $this->productModel->searchProductsByName($term, $excludeId, 10);
    }
}
