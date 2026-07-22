<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../models/ProductModel.php');
require_once(__DIR__ . '/../models/CategoryModel.php');

class ProductController
{
    private $productModel;
    private $categoryModel;

    public function __construct($db)
    {
        $this->productModel = new ProductModel();
        $this->categoryModel = new Category($db);
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

    private function formatProductForApi($product)
    {
        // Get base URL for absolute image paths
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
        $baseUrl = $protocol . "://" . $host . $basePath . "/";

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

        return [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'long_description' => $product['long_description'] ?? null,
            'instructions' => $product['instructions'] ?? null,
            'delivery_info' => $product['delivery_info'] ?? null,
            'category_id' => (int) $product['category_id'],
            'category_name' => $product['category_name'] ?? ($product['main_category_name'] ?? null),
            'main_category_name' => $product['main_category_name'] ?? null,
            'brand' => $product['brand'],
            'price' => (float) $product['price'],
            'discount_price' => $product['discount_price'] ? (float) $product['discount_price'] : null,
            'regular_price' => (float) ($product['regular_price'] ?? 0),
            'offer_price' => $product['offer_price'] ? (float) $product['offer_price'] : null,
            'stock_quantity' => (int) $product['stock_quantity'],
            'sku' => $product['sku'],
            'slug' => $product['slug'] ?? null,
            'status' => $product['status'],
            'featured' => (bool) $product['featured'],
            'our_bestseller' => (bool) ($product['our_bestseller'] ?? false),
            'top_rated' => (bool) ($product['top_rated'] ?? false),
            'top_deal' => (bool) ($product['top_deal'] ?? false),
            'min_quantity' => (int) ($product['min_quantity'] ?? 1),
            'out_of_stock_status' => $product['out_of_stock_status'] ?? 'in_stock',
            'show_left_quantity' => (bool) ($product['show_left_quantity'] ?? false),
            'show_help_button' => (bool) ($product['show_help_button'] ?? false),
            'show_bulk_form' => (bool) ($product['show_bulk_form'] ?? false),
            'customization_label_status' => (bool) ($product['customization_label_status'] ?? false),
            'customization_data' => !empty($product['customization_data']) ? json_decode($product['customization_data'], true) : [],
            'size_status' => (bool) ($product['size_status'] ?? false),
            'sizes' => !empty($product['sizes']) ? json_decode($product['sizes'], true) : [],
            'colors' => !empty($product['colors']) ? json_decode($product['colors'], true) : [],
            'color_images' => !empty($product['color_images']) ? json_decode($product['color_images'], true) : [],
            'materials' => !empty($product['materials']) ? json_decode($product['materials'], true) : [],
            'lamination' => !empty($product['lamination']) ? json_decode($product['lamination'], true) : [],
            'orientation' => !empty($product['orientation']) ? json_decode($product['orientation'], true) : [],
            'printing_location' => !empty($product['printing_location']) ? json_decode($product['printing_location'], true) : [],
            'bulk_order_pricing' => !empty($product['bulk_order_pricing']) ? json_decode($product['bulk_order_pricing'], true) : [],
            'quantity_pricing' => !empty($product['quantity_pricing']) ? json_decode($product['quantity_pricing'], true) : [],
            'shipping_method_status' => (bool) ($product['shipping_method_status'] ?? false),
            'local_shipping_price' => (float) ($product['local_shipping_price'] ?? 0),
            'regional_shipping_price' => (float) ($product['regional_shipping_price'] ?? 0),
            'national_shipping_price' => (float) ($product['national_shipping_price'] ?? 0),
            'regional_shipping_msg' => $product['regional_shipping_msg'] ?? '',
            'national_shipping_msg' => $product['national_shipping_msg'] ?? '',
            'cancel_status' => $product['cancel_status'] ?? 'yes',
            'cancel_type' => $product['cancel_type'] ?? 'hour',
            'cancel_value' => (int) ($product['cancel_value'] ?? 24),
            'cod_status' => $product['cod_status'] ?? 'yes',
            'addon_product_ids' => !empty($product['addon_product_ids']) ? json_decode($product['addon_product_ids'], true) : [],
            'meta_title' => $product['meta_title'] ?? '',
            'meta_keywords' => $product['meta_keywords'] ?? '',
            'meta_description' => $product['meta_description'] ?? '',
            'alt_tag' => $product['alt_tag'] ?? '',
            'images'          => $formattedImages,
            'primary_image'   => $primaryImageUrl,
            'thumbnail'       => $primaryImageUrl,
            'created_at' => $product['created_at'],
            'updated_at' => $product['updated_at']
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
            $mainImages     = [];
            $galleryImages  = [];

            if (!empty($files['thumbnail_image']['name'])) {
                $thumbnailImage = $this->uploadImage($files['thumbnail_image'], 'thumbnail');
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
            $customizationFields = [];
            if (!empty($data['customization_labels']) && is_array($data['customization_labels'])) {
                foreach ($data['customization_labels'] as $i => $label) {
                    if (!empty($label)) {
                        $customizationFields[] = [
                            'label'    => $label,
                            'type'     => $data['customization_types'][$i] ?? 'text',
                            'required' => isset($data['customization_required'][$i]) ? 1 : 0,
                        ];
                    }
                }
            }

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

            // ── Color attributes (JSON) ───────────────────────────────────
            $colorAttributes = [];
            if (!empty($data['color_names']) && is_array($data['color_names'])) {
                foreach ($data['color_names'] as $i => $name) {
                    if (!empty($name)) {
                        $colorAttributes[] = [
                            'name'  => $name,
                            'hex'   => $data['color_hexes'][$i] ?? '#000000',
                            'price' => floatval($data['color_prices'][$i] ?? 0),
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

                // Tab 2 – Filters
                'color'                  => trim($data['color'] ?? ''),
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
                'homepage_category_id'   => !empty($data['homepage_category_id']) ? intval($data['homepage_category_id']) : null,
                'homepage_carousel_id'   => !empty($data['homepage_carousel_id']) ? intval($data['homepage_carousel_id']) : null,
                'status'                 => $data['status'] ?? 'active',
                'featured'               => isset($data['featured']) ? 1 : 0,
                'top_selection'          => isset($data['top_selection']) ? 1 : 0,
                'our_bestseller'         => isset($data['our_bestseller']) ? 1 : 0,
                'top_rated'              => isset($data['top_rated']) ? 1 : 0,
                'top_deal_by_categories' => isset($data['top_deal_by_categories']) ? 1 : 0,

                // Tab 4 – Images
                'alt_tag'                => trim($data['alt_tag'] ?? ''),
                'thumbnail_image'        => $thumbnailImage,
                'gallery_images'         => !empty($galleryImages) ? json_encode(array_values($galleryImages)) : null,

                // Tab 5 – SEO
                'meta_title'             => trim($data['meta_title'] ?? ''),
                'meta_keywords'          => trim($data['meta_keywords'] ?? ''),
                'meta_description'       => trim($data['meta_description'] ?? ''),

                // Tab 6 – Visibility
                'show_quantity'          => isset($data['show_quantity']) ? 1 : 0,
                'show_help_button'       => isset($data['show_help_button']) ? 1 : 0,
                'show_bulk_form'         => isset($data['show_bulk_form']) ? 1 : 0,

                // Tab 7 – Customization
                'show_customization_label' => isset($data['show_customization_label']) ? 1 : 0,
                'customization_label'    => trim($data['customization_label'] ?? ''),
                'customization_fields'   => !empty($customizationFields) ? json_encode($customizationFields) : null,

                // Tab 8 – Addons
                'addon_product_ids'      => $addonIds,

                // Tab 9 – Attributes
                'show_sizes'             => isset($data['show_sizes']) ? 1 : 0,
                'show_colors'            => isset($data['show_colors']) ? 1 : 0,
                'size_attributes'        => !empty($sizeAttributes) ? json_encode($sizeAttributes) : null,
                'color_attributes'       => !empty($colorAttributes) ? json_encode($colorAttributes) : null,
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

            return ['success' => true, 'product_id' => $productId, 'message' => 'Product added successfully!'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function uploadImage($file, $type = 'product')
    {
        $uploadDir = 'uploads/products/' . $type . '/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if ($file['error'] === UPLOAD_ERR_OK) {
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);

            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.");
            }

            // Validate file size (5MB max)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("File size too large. Maximum size is 5MB.");
            }

            // Generate unique filename
            $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('product_' . $type . '_') . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                return $filePath;
            } else {
                throw new Exception("Failed to upload image: " . $file['name']);
            }
        } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            throw new Exception("Upload error: " . $this->getUploadError($file['error']));
        }

        return '';
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

    public function updateProduct($id, $data, $files)
    {
        try {
            // Get existing product
            $existingProduct = $this->getProductById($id);
            if (!$existingProduct) {
                throw new Exception("Product not found");
            }

            // Handle image uploads
            $thumbnailImage = $existingProduct['thumbnail_image'] ?? '';
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

            // Handle additional categories
            $additionalCategoryIds = isset($data['additional_category_ids']) ? json_encode($data['additional_category_ids']) : ($existingProduct['additional_category_ids'] ?? null);
            $additionalSubCategoryIds = isset($data['additional_sub_category_ids']) ? json_encode($data['additional_sub_category_ids']) : ($existingProduct['additional_sub_category_ids'] ?? null);
            $additionalSubSubCategoryIds = isset($data['additional_sub_sub_category_ids']) ? json_encode($data['additional_sub_sub_category_ids']) : ($existingProduct['additional_sub_sub_category_ids'] ?? null);

            // Handle customization fields
            $customizationFields = [];
            if (isset($data['customization_labels']) && is_array($data['customization_labels'])) {
                foreach ($data['customization_labels'] as $index => $label) {
                    if (!empty($label)) {
                        $customizationFields[] = [
                            'label' => $label,
                            'type' => $data['customization_types'][$index] ?? 'text',
                            'required' => isset($data['customization_required'][$index]) ? (int) $data['customization_required'][$index] : 0,
                            'price' => floatval($data['customization_prices'][$index] ?? 0),
                            'max_images' => intval($data['customization_max_images'][$index] ?? 1)
                        ];
                    }
                }
            }

            // Handle size attributes
            $sizeAttributes = [];
            if (isset($data['sizes']) && is_array($data['sizes'])) {
                foreach ($data['sizes'] as $index => $size) {
                    if (!empty($size)) {
                        $sizeAttributes[] = [
                            'size' => $size,
                            'price' => floatval($data['size_prices'][$index] ?? 0),
                            'stock' => intval($data['size_stocks'][$index] ?? 0),
                            'sku' => $data['size_skus'][$index] ?? '',
                            // Handle size image if uploaded
                        ];
                    }
                }
            }

            // Handle color attributes
            $colorAttributes = [];
            if (isset($data['colors']) && is_array($data['colors'])) {
                foreach ($data['colors'] as $index => $color) {
                    if (!empty($color)) {
                        $colorAttributes[] = [
                            'name' => $color,
                            'code' => $data['color_codes'][$index] ?? '#000000',
                            'price' => floatval($data['color_prices'][$index] ?? 0),
                            'stock' => intval($data['color_stocks'][$index] ?? 0),
                            // Handle color image if uploaded
                        ];
                    }
                }
            }

            // Handle material attributes
            $materialAttributes = [];
            if (isset($data['materials']) && is_array($data['materials'])) {
                foreach ($data['materials'] as $index => $material) {
                    if (!empty($material)) {
                        $materialAttributes[] = [
                            'material' => $material,
                            'price' => floatval($data['material_prices'][$index] ?? 0),
                            'sku' => $data['material_skus'][$index] ?? ''
                        ];
                    }
                }
            }

            // Handle lamination attributes
            $laminationAttributes = [];
            if (isset($data['laminations']) && is_array($data['laminations'])) {
                foreach ($data['laminations'] as $index => $lamination) {
                    if (!empty($lamination)) {
                        $laminationAttributes[] = [
                            'lamination' => $lamination,
                            'price' => floatval($data['lamination_prices'][$index] ?? 0),
                            'sku' => $data['lamination_skus'][$index] ?? ''
                        ];
                    }
                }
            }

            // Handle orientation attributes
            $orientationAttributes = [];
            if (isset($data['orientations']) && is_array($data['orientations'])) {
                foreach ($data['orientations'] as $index => $orientation) {
                    if (!empty($orientation)) {
                        $orientationAttributes[] = [
                            'orientation' => $orientation,
                            'price' => floatval($data['orientation_prices'][$index] ?? 0),
                            'sku' => $data['orientation_skus'][$index] ?? ''
                        ];
                    }
                }
            }

            // Handle quantity price breaks
            $quantityPriceBreaks = [];
            if (isset($data['quantity_breaks']) && is_array($data['quantity_breaks'])) {
                foreach ($data['quantity_breaks'] as $index => $minQty) {
                    if (!empty($minQty)) {
                        $quantityPriceBreaks[] = [
                            'min_qty' => intval($minQty),
                            'price' => floatval($data['quantity_break_prices'][$index] ?? 0),
                            'discount' => floatval($data['quantity_break_discounts'][$index] ?? 0),
                            'sku' => $data['quantity_break_skus'][$index] ?? ''
                        ];
                    }
                }
            }

            // Handle addon products
            $addonProductIds = isset($data['addon_product_ids']) ? json_encode($data['addon_product_ids']) : ($existingProduct['addon_product_ids'] ?? null);

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
                'color' => trim($data['color'] ?? $existingProduct['color'] ?? ''),
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
                'offer_price' => !empty($data['offer_price']) ? floatval($data['offer_price']) : ($existingProduct['offer_price'] ?? null),
                'discount_price' => !empty($data['offer_price']) ? floatval($data['offer_price']) : ($existingProduct['discount_price'] ?? null),
                'sort_order' => intval($data['sort_order'] ?? $existingProduct['sort_order'] ?? 0),
                'stock_quantity' => intval($data['product_quantity'] ?? $existingProduct['stock_quantity']),
                'product_quantity' => intval($data['product_quantity'] ?? $existingProduct['product_quantity'] ?? $existingProduct['stock_quantity']),
                'out_of_stock_status' => $data['out_of_stock_status'] ?? $existingProduct['out_of_stock_status'] ?? 'in_stock',
                'minimum_quantity' => intval($data['minimum_quantity'] ?? $existingProduct['minimum_quantity'] ?? 1),
                'bulk_price_variance' => trim($data['bulk_price_variance'] ?? $existingProduct['bulk_price_variance'] ?? ''),
                'sku' => trim($data['sku_code'] ?? $existingProduct['sku']),

                // Status & Display
                'status' => $data['status'] ?? $existingProduct['status'] ?? 'active',
                'show_quantity' => isset($data['show_quantity']) ? 1 : ($existingProduct['show_quantity'] ?? 1),
                'show_bulk_form' => isset($data['show_bulk_form']) ? 1 : ($existingProduct['show_bulk_form'] ?? 0),
                'show_help_button' => isset($data['show_help_button']) ? 1 : ($existingProduct['show_help_button'] ?? 0),
                'help_button_text' => trim($data['help_button_text'] ?? $existingProduct['help_button_text'] ?? 'Need Help?'),
                'help_button_link' => trim($data['help_button_link'] ?? $existingProduct['help_button_link'] ?? '/contact-us'),

                // Customization
                'show_customization_label' => isset($data['show_customization_label']) ? 1 : ($existingProduct['show_customization_label'] ?? 0),
                'customization_label' => trim($data['customization_label'] ?? $existingProduct['customization_label'] ?? ''),
                'customization_fields' => !empty($customizationFields) ? json_encode($customizationFields) : ($existingProduct['customization_fields'] ?? null),

                // Addons
                'addon_product_ids' => $addonProductIds,

                // Attribute flags
                'show_sizes' => isset($data['show_sizes']) ? 1 : ($existingProduct['show_sizes'] ?? 1),
                'show_colors' => isset($data['show_colors']) ? 1 : ($existingProduct['show_colors'] ?? 1),

                // Attribute values (JSON)
                'size_attributes' => !empty($sizeAttributes) ? json_encode($sizeAttributes) : ($existingProduct['size_attributes'] ?? null),
                'color_attributes' => !empty($colorAttributes) ? json_encode($colorAttributes) : ($existingProduct['color_attributes'] ?? null),
                'material_attributes' => !empty($materialAttributes) ? json_encode($materialAttributes) : ($existingProduct['material_attributes'] ?? null),
                'lamination_attributes' => !empty($laminationAttributes) ? json_encode($laminationAttributes) : ($existingProduct['lamination_attributes'] ?? null),
                'orientation_attributes' => !empty($orientationAttributes) ? json_encode($orientationAttributes) : ($existingProduct['orientation_attributes'] ?? null),
                'quantity_price_breaks' => !empty($quantityPriceBreaks) ? json_encode($quantityPriceBreaks) : ($existingProduct['quantity_price_breaks'] ?? null),

                // Shipping
                'shipping_method_status' => isset($data['shipping_method_status']) ? 1 : ($existingProduct['shipping_method_status'] ?? 1),
                'local_shipping_charge' => floatval($data['local_shipping_charge'] ?? $existingProduct['local_shipping_charge'] ?? 0),
                'local_shipping_message' => trim($data['local_shipping_message'] ?? $existingProduct['local_shipping_message'] ?? ''),
                'regional_shipping_charge' => floatval($data['regional_shipping_charge'] ?? $existingProduct['regional_shipping_charge'] ?? 0),
                'regional_shipping_message' => trim($data['regional_shipping_message'] ?? $existingProduct['regional_shipping_message'] ?? ''),
                'national_shipping_charge' => floatval($data['national_shipping_charge'] ?? $existingProduct['national_shipping_charge'] ?? 0),
                'national_shipping_message' => trim($data['national_shipping_message'] ?? $existingProduct['national_shipping_message'] ?? ''),

                // Cancellation
                'cancel_available' => isset($data['cancel_available']) ? 1 : ($existingProduct['cancel_available'] ?? 1),
                'cancel_time' => intval($data['cancel_time'] ?? $existingProduct['cancel_time'] ?? 24),
                'cancel_type' => $data['cancel_type'] ?? $existingProduct['cancel_type'] ?? 'hours',

                // COD
                'cod_available' => isset($data['cod_available']) ? 1 : ($existingProduct['cod_available'] ?? 1),

                // Badges/Flags
                'featured' => isset($data['featured']) ? 1 : ($existingProduct['featured'] ?? 0),
                'top_selection' => isset($data['top_selection']) ? 1 : ($existingProduct['top_selection'] ?? 0),
                'our_bestseller' => isset($data['our_bestseller']) ? 1 : ($existingProduct['our_bestseller'] ?? 0),
                'top_rated' => isset($data['top_rated']) ? 1 : ($existingProduct['top_rated'] ?? 0),
                'top_deal_by_categories' => isset($data['top_deal_by_categories']) ? 1 : ($existingProduct['top_deal_by_categories'] ?? 0),

                // Timestamps
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Remove null values that shouldn't be updated
            $updateData = array_filter($updateData, function ($value) {
                return $value !== null;
            });

            // Update product
            $success = $this->productModel->updateProduct($id, $updateData, $mainImages);

            if ($success) {
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
            $formattedProducts[] = $this->formatProductForApi($product);
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
            $formattedProducts[] = $this->formatProductForApi($product);
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
            $formattedProducts[] = $this->formatProductForApi($product);
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
            return ['success' => true, 'data' => $products];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getRecentlyViewedApi()
    {
        try {
            $products = $this->productModel->getActiveProducts();
            return ['success' => true, 'data' => $products];
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
            return ['success' => true, 'data' => $products];
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
