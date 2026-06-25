<?php
require_once __DIR__ . '/vendor/autoload.php';

class PrintmontAPIPDF extends TCPDF {
    public function Header() {
        $this->SetFillColor(22, 33, 62);
        $this->Rect(0, 0, 210, 14, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetXY(10, 4);
        $this->Cell(0, 6, 'Printmont API Documentation', 0, 0, 'L');
        $this->SetXY(10, 4);
        $this->Cell(0, 6, 'http://localhost/printmont-admin/api/', 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }
    public function Footer() {
        $this->SetY(-12);
        $this->SetFillColor(22, 33, 62);
        $this->Rect(0, 285, 210, 12, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', '', 8);
        $this->SetXY(10, 287);
        $this->Cell(0, 6, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'L');
        $this->SetXY(10, 287);
        $this->Cell(0, 6, 'Printmont E-Commerce  |  Generated ' . date('d M Y'), 0, 0, 'R');
    }
}

$pdf = new PrintmontAPIPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Printmont Admin');
$pdf->SetAuthor('Printmont');
$pdf->SetTitle('Printmont API Documentation');
$pdf->SetMargins(15, 20, 15);
$pdf->SetAutoPageBreak(true, 18);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->AddPage();

$BASE = 'http://localhost/printmont-admin/api';

// ─── Helper functions ─────────────────────────────────────────────────────────

function coverPage($pdf, $BASE) {
    $pdf->SetY(40);
    // Logo block
    $pdf->SetFillColor(22, 33, 62);
    $pdf->RoundedRect(15, 35, 180, 50, 5, '1111', 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 28);
    $pdf->SetXY(15, 46);
    $pdf->Cell(180, 12, 'PRINTMONT', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 14);
    $pdf->SetXY(15, 60);
    $pdf->Cell(180, 8, 'API Documentation', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(15, 70);
    $pdf->Cell(180, 6, 'Complete REST API Reference', 0, 1, 'C');

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY(100);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetFillColor(240, 244, 248);
    $pdf->RoundedRect(15, 98, 180, 26, 3, '1111', 'F');
    $pdf->SetXY(15, 100);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(180, 6, 'Base URL', 0, 1, 'C');
    $pdf->SetFont('courier', '', 10);
    $pdf->SetTextColor(192, 57, 43);
    $pdf->SetXY(15, 107);
    $pdf->Cell(180, 6, $BASE . '/', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY(130);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell(180, 6,
        'This document lists all available API endpoints in the Printmont e-commerce backend, ' .
        'organized by module. Each entry includes the HTTP method, full endpoint URL, description, ' .
        'query/body parameters, and authentication requirements.',
        0, 'C');

    // Summary table
    $pdf->SetY(165);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(22, 33, 62);
    $pdf->Cell(180, 8, 'API Modules Summary', 0, 1, 'C');
    $pdf->SetY($pdf->GetY() + 2);

    $modules = [
        ['#', 'Module', 'Endpoints'],
        ['1', 'Authentication', '5'],
        ['2', 'User Addresses', '5'],
        ['3', 'Orders', '7'],
        ['4', 'Products', '5'],
        ['5', 'Home Product Sections', '7'],
        ['6', 'Categories', '1'],
        ['7', 'Cart', '4'],
        ['8', 'Wishlist', '3'],
        ['9', 'Banners', '2'],
        ['10', 'Blog', '7'],
        ['11', 'Careers', '3'],
        ['12', 'Content & CMS', '6'],
        ['', 'TOTAL', '55+'],
    ];

    $colWidths = [12, 120, 48];
    $pdf->SetFont('helvetica', 'B', 9);
    foreach ($modules as $i => $row) {
        if ($i === 0) {
            $pdf->SetFillColor(22, 33, 62);
            $pdf->SetTextColor(255, 255, 255);
            $fill = true;
        } elseif ($i === count($modules) - 1) {
            $pdf->SetFillColor(15, 52, 96);
            $pdf->SetTextColor(255, 255, 255);
            $fill = true;
            $pdf->SetFont('helvetica', 'B', 9);
        } else {
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor($i % 2 === 0 ? 240 : 255, $i % 2 === 0 ? 244 : 255, $i % 2 === 0 ? 248 : 255);
            $pdf->SetTextColor(30, 30, 30);
            $fill = true;
        }
        $pdf->Cell($colWidths[0], 7, $row[0], 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[1], 7, $row[1], 1, 0, 'L', $fill);
        $pdf->Cell($colWidths[2], 7, $row[2], 1, 1, 'C', $fill);
    }
    $pdf->SetTextColor(0,0,0);
}

function sectionHeader($pdf, $text) {
    $pdf->SetY($pdf->GetY() + 4);
    $pdf->SetFillColor(22, 33, 62);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 9, '  ' . $text, 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY($pdf->GetY() + 2);
}

function methodColor($method) {
    $map = [
        'GET'    => [39, 174, 96],
        'POST'   => [41, 128, 185],
        'PUT'    => [230, 126, 34],
        'DELETE' => [192, 57, 43],
        'MULTI'  => [142, 68, 173],
    ];
    return $map[$method] ?? [100, 100, 100];
}

function apiBlock($pdf, $BASE, $name, $method, $endpoint, $description, $params = [], $auth = false, $notes = []) {
    // API name
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(15, 52, 96);
    $pdf->Cell(0, 7, $name, 0, 1, 'L');

    // Method badge + endpoint
    $mc = methodColor($method);
    $pdf->SetFillColor($mc[0], $mc[1], $mc[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(14, 6, $method, 0, 0, 'C', true);

    $pdf->SetFillColor(238, 238, 255);
    $pdf->SetTextColor(26, 26, 46);
    $pdf->SetFont('courier', '', 8);
    $pdf->Cell(0, 6, '  ' . $BASE . $endpoint, 0, 1, 'L', true);
    $pdf->SetY($pdf->GetY() + 1);

    // Description
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->MultiCell(0, 5, $description, 0, 'L');

    // Auth
    if ($auth) {
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(142, 68, 173);
        $pdf->Cell(0, 5, '  Auth: Bearer Token required in Authorization header', 0, 1, 'L');
    }

    // Params table
    if (!empty($params)) {
        $pdf->SetY($pdf->GetY() + 1);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 5, 'Parameters:', 0, 1, 'L');

        $colW = [40, 24, 20, 96];
        $pdf->SetFillColor(22, 33, 62);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($colW[0], 6, ' Name', 1, 0, 'L', true);
        $pdf->Cell($colW[1], 6, 'Type', 1, 0, 'C', true);
        $pdf->Cell($colW[2], 6, 'Required', 1, 0, 'C', true);
        $pdf->Cell($colW[3], 6, 'Description', 1, 1, 'L', true);

        foreach ($params as $i => $p) {
            $fill = ($i % 2 === 0);
            $pdf->SetFillColor(240, 244, 248);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->SetFont('courier', '', 8);
            $pdf->Cell($colW[0], 6, ' ' . $p[0], 1, 0, 'L', $fill);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(41, 128, 185);
            $pdf->Cell($colW[1], 6, $p[1], 1, 0, 'C', $fill);
            $pdf->SetTextColor($p[2] === 'Yes' ? 192 : 100, $p[2] === 'Yes' ? 57 : 100, $p[2] === 'Yes' ? 43 : 100);
            $pdf->Cell($colW[2], 6, $p[2], 1, 0, 'C', $fill);
            $pdf->SetTextColor(50, 50, 50);
            $pdf->Cell($colW[3], 6, $p[3], 1, 1, 'L', $fill);
        }
    }

    // Notes
    foreach ($notes as $note) {
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 5, '* ' . $note, 0, 1, 'L');
    }

    // Divider
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->SetY($pdf->GetY() + 2);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->SetY($pdf->GetY() + 3);
    $pdf->SetTextColor(0, 0, 0);
}

// ─── COVER PAGE ───────────────────────────────────────────────────────────────
coverPage($pdf, $BASE);

// ─── AUTH ────────────────────────────────────────────────────────────────────
$pdf->AddPage();
sectionHeader($pdf, '1. Authentication');

apiBlock($pdf, $BASE, 'User Register', 'POST', '/user-api.php?action=register',
    'Register a new customer account.',
    [
        ['name', 'string', 'Yes', 'Full name of the user'],
        ['email', 'string', 'Yes', 'Email address (must be unique)'],
        ['password', 'string', 'Yes', 'Account password'],
        ['phone', 'string', 'No', 'Phone number'],
    ]
);

apiBlock($pdf, $BASE, 'User Login', 'POST', '/user-api.php?action=login',
    'Authenticate a user and return a JWT Bearer token for protected endpoints.',
    [
        ['email', 'string', 'Yes', 'Registered email address'],
        ['password', 'string', 'Yes', 'Account password'],
    ]
);

apiBlock($pdf, $BASE, 'Get User Profile', 'GET', '/user-api.php?action=profile',
    'Retrieve the authenticated user\'s profile details.',
    [], true
);

apiBlock($pdf, $BASE, 'Update User Profile', 'POST', '/user-api.php?action=update_profile',
    'Update the authenticated user\'s profile information.',
    [
        ['name', 'string', 'No', 'Updated full name'],
        ['phone', 'string', 'No', 'Updated phone number'],
        ['email', 'string', 'No', 'Updated email address'],
    ], true
);

apiBlock($pdf, $BASE, 'Change Password', 'PUT', '/router.php?action=change_password',
    'Change the authenticated user\'s account password.',
    [
        ['old_password', 'string', 'Yes', 'Current account password'],
        ['new_password', 'string', 'Yes', 'New password to set'],
    ], true
);

// ─── ADDRESSES ───────────────────────────────────────────────────────────────
sectionHeader($pdf, '2. User Addresses');

apiBlock($pdf, $BASE, 'Get Addresses', 'GET', '/user-api.php?action=get_addresses',
    'Get all saved delivery addresses for the authenticated user.', [], true);

apiBlock($pdf, $BASE, 'Add Address', 'POST', '/user-api.php?action=add_address',
    'Add a new delivery address to the user\'s account.',
    [
        ['full_name', 'string', 'Yes', 'Recipient full name'],
        ['phone', 'string', 'Yes', 'Contact phone number'],
        ['address_line1', 'string', 'Yes', 'Street address line 1'],
        ['address_line2', 'string', 'No', 'Street address line 2 (optional)'],
        ['city', 'string', 'Yes', 'City'],
        ['state', 'string', 'Yes', 'State / Province'],
        ['pincode', 'string', 'Yes', 'Postal / ZIP code'],
        ['country', 'string', 'No', 'Country (default: India)'],
    ], true
);

apiBlock($pdf, $BASE, 'Update Address', 'POST', '/user-api.php?action=update_address',
    'Update an existing delivery address.',
    [['address_id', 'integer', 'Yes', 'ID of the address to update']], true);

apiBlock($pdf, $BASE, 'Delete Address', 'POST', '/user-api.php?action=delete_address',
    'Remove a delivery address from the user\'s account.',
    [['address_id', 'integer', 'Yes', 'ID of the address to delete']], true);

apiBlock($pdf, $BASE, 'Set Default Address', 'POST', '/user-api.php?action=set_default_address',
    'Mark an address as the default shipping address for checkout.',
    [['address_id', 'integer', 'Yes', 'ID of address to set as default']], true);

// ─── ORDERS ──────────────────────────────────────────────────────────────────
$pdf->AddPage();
sectionHeader($pdf, '3. Orders');

apiBlock($pdf, $BASE, 'Get All Orders', 'GET', '/user-api.php?action=get_orders',
    'Retrieve a list of all orders. Supports filtering by user.',
    [['user_id', 'integer', 'No', 'Filter orders by a specific customer\'s user ID']]
);

apiBlock($pdf, $BASE, 'Get Order by ID', 'GET', '/user-api.php?action=get_order&id={id}',
    'Retrieve the full details of a single order including items and status history.',
    [['id', 'integer', 'Yes', 'Order ID']]
);

apiBlock($pdf, $BASE, 'Create Order', 'POST', '/user-api.php?action=create_order',
    'Place a new order. Accepts cart items, customer address, and payment method.',
    [
        ['user_id', 'integer', 'Yes', 'Customer user ID'],
        ['items', 'array', 'Yes', 'Array of {product_id, quantity, price}'],
        ['address_id', 'integer', 'Yes', 'Delivery address ID'],
        ['payment_method', 'string', 'Yes', 'cod | online | upi'],
        ['coupon_code', 'string', 'No', 'Discount coupon code (if any)'],
    ]
);

apiBlock($pdf, $BASE, 'Get Customer Orders', 'GET', '/router.php?action=get_customer_orders&user_id={id}',
    'Get all orders placed by a specific customer.',
    [['user_id', 'integer', 'Yes', 'Customer user ID']]
);

apiBlock($pdf, $BASE, 'Update Order Status', 'PUT', '/router.php?action=update_order_status&id={id}',
    'Change the fulfillment status of an order.',
    [
        ['status', 'string', 'Yes', 'pending | processing | shipped | delivered | cancelled'],
        ['notes', 'string', 'No', 'Optional admin note for this status change'],
    ]
);

apiBlock($pdf, $BASE, 'Update Payment Status', 'PUT', '/router.php?action=update_payment_status&id={id}',
    'Update the payment status for a specific order.',
    [['payment_status', 'string', 'Yes', 'paid | unpaid | refunded']]
);

apiBlock($pdf, $BASE, 'Dashboard Stats', 'GET', '/router.php?action=get_dashboard_stats',
    'Get summary statistics: total orders, revenue, pending orders, total customers.');

// ─── PRODUCTS ────────────────────────────────────────────────────────────────
$pdf->AddPage();
sectionHeader($pdf, '4. Products');

apiBlock($pdf, $BASE, 'Get All Products', 'GET', '/product-api.php',
    'Returns all active products. Each product includes a full image gallery array with primary image flag.');

apiBlock($pdf, $BASE, 'Get Product by ID', 'GET', '/product-api.php?id={id}',
    'Retrieve a single product\'s full details including images, variants, and category info.',
    [['id', 'integer', 'Yes', 'Product ID']]
);

apiBlock($pdf, $BASE, 'Get Deactivated Products', 'GET', '/product-api.php?status=deactive',
    'List all deactivated (hidden from store) products.');

apiBlock($pdf, $BASE, 'Related Products', 'GET', '/related_products.php?id={id}',
    'Get products in the same category as the given product (excludes the product itself).',
    [['id', 'integer', 'Yes', 'Product ID to find related items for']]
);

apiBlock($pdf, $BASE, 'Search Products', 'GET', '/search-api.php',
    'Full-text search across products with optional price and category filters.',
    [
        ['q', 'string', 'Yes', 'Search keyword (name, description, tags)'],
        ['category', 'string', 'No', 'Filter by category slug'],
        ['min_price', 'number', 'No', 'Minimum price filter'],
        ['max_price', 'number', 'No', 'Maximum price filter'],
        ['limit', 'integer', 'No', 'Max results to return (default: 10)'],
    ]
);

// ─── HOME PRODUCT SECTIONS ───────────────────────────────────────────────────
$pdf->AddPage();
sectionHeader($pdf, '5. Home Page Product Sections');

apiBlock($pdf, $BASE, 'Home Products (Multi-Action)', 'GET', '/home-product-api.php?action={action}',
    'Fetch curated product lists for various homepage sections. Pass one of the actions below as the ?action= parameter.',
    [
        ['top_selection', 'string', '—', 'Top selection / featured products'],
        ['top_rated', 'string', '—', 'Highest rated products'],
        ['top_deal', 'string', '—', 'Top deals grouped by category'],
        ['discount_for_you', 'string', '—', 'Products with active discounts'],
        ['recently_viewed', 'string', '—', 'Recently viewed products'],
        ['categories', 'string', '—', 'All product categories list'],
        ['men_clothing', 'string', '—', 'Products in men-clothing category'],
        ['women_clothing', 'string', '—', 'Products in women-clothing category'],
        ['kids', 'string', '—', 'Products in kids category'],
        ['mobile', 'string', '—', 'Mobile phones & accessories'],
        ['laptop', 'string', '—', 'Laptop & computer products'],
        ['buds', 'string', '—', 'Earbuds & headphones'],
        ['home_decor', 'string', '—', 'Home decor products'],
        ['table_dinnerware', 'string', '—', 'Table and dinnerware items'],
        ['women_outfit', 'string', '—', 'Women outfit products'],
        ['men', 'string', '—', 'General men products'],
        ['women', 'string', '—', 'General women products'],
    ]
);

apiBlock($pdf, $BASE, 'Top Rated Products', 'GET', '/top-rated-products.php',
    'Get the list of products marked as top-rated.');

apiBlock($pdf, $BASE, 'Set Top Rated Status', 'POST', '/top-rated-products.php',
    'Mark or unmark a product as top-rated.',
    [
        ['product_id', 'integer', 'Yes', 'Product ID'],
        ['status', 'integer', 'Yes', '1 = mark as top-rated, 0 = remove'],
    ]
);

apiBlock($pdf, $BASE, 'Top Selection Products', 'GET', '/top-selection-products.php',
    'Get the list of products marked as top-selection.');

apiBlock($pdf, $BASE, 'Set Top Selection Status', 'POST', '/top-selection-products.php',
    'Mark or unmark a product as top-selection.',
    [
        ['product_id', 'integer', 'Yes', 'Product ID'],
        ['status', 'integer', 'Yes', '1 = mark as top-selection, 0 = remove'],
    ]
);

apiBlock($pdf, $BASE, 'Bestseller Products', 'GET', '/bestseller-products.php',
    'Get the list of products marked as bestsellers.');

apiBlock($pdf, $BASE, 'Set Bestseller Status', 'POST', '/bestseller-products.php',
    'Mark or unmark a product as a bestseller.',
    [
        ['product_id', 'integer', 'Yes', 'Product ID'],
        ['status', 'integer', 'Yes', '1 = mark as bestseller, 0 = remove'],
    ]
);

// ─── CATEGORIES ──────────────────────────────────────────────────────────────
$pdf->AddPage();
sectionHeader($pdf, '6. Categories');

apiBlock($pdf, $BASE, 'Get All Categories', 'GET', '/category-api.php',
    'Retrieve all product categories including the full hierarchy: Main Category > Sub Category > Sub-Sub Category.');

// ─── CART ────────────────────────────────────────────────────────────────────
sectionHeader($pdf, '7. Cart');

apiBlock($pdf, $BASE, 'Get Cart', 'GET', '/cart-api.php',
    'Get all items currently in the authenticated user\'s shopping cart.', [], true);

apiBlock($pdf, $BASE, 'Add to Cart', 'POST', '/cart-api.php',
    'Add a product to the shopping cart.',
    [
        ['product_id', 'integer', 'Yes', 'Product ID to add'],
        ['quantity', 'integer', 'No', 'Quantity to add (default: 1)'],
    ], true
);

apiBlock($pdf, $BASE, 'Update Cart Item', 'PUT', '/cart-api.php',
    'Update the quantity of an existing cart item.',
    [
        ['item_id', 'integer', 'Yes', 'Cart item ID to update'],
        ['quantity', 'integer', 'Yes', 'New quantity'],
    ], true
);

apiBlock($pdf, $BASE, 'Remove from Cart', 'DELETE', '/cart-api.php?item_id={id}',
    'Remove a specific item from the shopping cart.',
    [['item_id', 'integer', 'Yes', 'Cart item ID to remove']], true
);

// ─── WISHLIST ────────────────────────────────────────────────────────────────
sectionHeader($pdf, '8. Wishlist');

apiBlock($pdf, $BASE, 'Get Wishlist', 'GET', '/wishlist-api.php',
    'Get all products saved in the authenticated user\'s wishlist.', [], true);

apiBlock($pdf, $BASE, 'Add to Wishlist', 'POST', '/wishlist-api.php',
    'Save a product to the wishlist.',
    [['product_id', 'integer', 'Yes', 'Product ID to add to wishlist']], true);

apiBlock($pdf, $BASE, 'Remove from Wishlist', 'DELETE', '/wishlist-api.php?product_id={id}',
    'Remove a product from the wishlist.',
    [['product_id', 'integer', 'Yes', 'Product ID to remove']], true);

// ─── BANNERS ─────────────────────────────────────────────────────────────────
$pdf->AddPage();
sectionHeader($pdf, '9. Banners');

apiBlock($pdf, $BASE, 'Homepage Banners', 'GET', '/banner_api.php',
    'Get all active banners for the homepage hero/carousel section. Returns image URL, link, position, and display order.');

apiBlock($pdf, $BASE, 'Blog Page Banner', 'GET', '/blog-page-banner-api.php',
    'Get the banner image displayed at the top of the blog listing page.');

// ─── BLOG ────────────────────────────────────────────────────────────────────
sectionHeader($pdf, '10. Blog');

apiBlock($pdf, $BASE, 'Get All Blog Posts', 'GET', '/blog-api.php/posts',
    'Get all published blog posts with metadata (title, slug, excerpt, category, author, published date).');

apiBlock($pdf, $BASE, 'Get Blog Post by ID or Slug', 'GET', '/blog-api.php/posts/{id|slug}',
    'Get a single published blog post. Automatically increments the view count on each access.',
    [['id or slug', 'string', 'Yes', 'Numeric post ID or URL-friendly slug']]
);

apiBlock($pdf, $BASE, 'Get Blog Post by Slug (Alt)', 'GET', '/blog-single.php?slug={slug}',
    'Alternative endpoint to fetch a single published blog post by its URL slug.',
    [['slug', 'string', 'Yes', 'URL slug of the blog post']]
);

apiBlock($pdf, $BASE, 'Get Blog Categories', 'GET', '/blog-api.php/categories',
    'Get all active blog categories with post count.');

apiBlock($pdf, $BASE, 'Get Category with Posts', 'GET', '/blog-api.php/categories/{id}',
    'Get a single blog category and all its associated published posts.',
    [['id', 'integer', 'Yes', 'Blog category ID']]
);

apiBlock($pdf, $BASE, 'Recent Blog Posts', 'GET', '/blog-api.php/recent',
    'Get the most recently published blog posts.',
    [['limit', 'integer', 'No', 'Number of posts to return (default: 5)']]
);

apiBlock($pdf, $BASE, 'Popular Blog Posts', 'GET', '/blog-api.php/popular',
    'Get the most-viewed / popular blog posts.',
    [['limit', 'integer', 'No', 'Number of posts to return (default: 5)']]
);

// ─── CAREERS ─────────────────────────────────────────────────────────────────
$pdf->AddPage();
sectionHeader($pdf, '11. Careers');

apiBlock($pdf, $BASE, 'Get All Job Listings', 'GET', '/career-get-api.php',
    'Get all open job positions with optional department/type filters and pagination support.',
    [
        ['active_only', 'integer', 'No', '1 = active only (default), 0 = all listings'],
        ['department', 'string', 'No', 'Filter by department name (e.g. Engineering)'],
        ['job_type', 'string', 'No', 'Filter by type: full-time | part-time | contract | internship'],
        ['featured', 'integer', 'No', '1 = featured listings only'],
        ['limit', 'integer', 'No', 'Maximum number of results'],
        ['offset', 'integer', 'No', 'Pagination offset (default: 0)'],
    ]
);

apiBlock($pdf, $BASE, 'Get Job by ID', 'GET', '/career-get-api.php?id={id}',
    'Get a single job listing\'s full details. Increments the view count on access.',
    [['id', 'integer', 'Yes', 'Career/job listing ID']]
);

apiBlock($pdf, $BASE, 'Submit Job Application', 'POST', '/career-post-api.php',
    'Submit a job application with resume upload. Use multipart/form-data content type for file upload.',
    [
        ['career_id', 'integer', 'Yes', 'ID of the job being applied for'],
        ['full_name', 'string', 'Yes', 'Applicant\'s full name'],
        ['email', 'string', 'Yes', 'Applicant\'s email address'],
        ['phone', 'string', 'Yes', 'Applicant\'s phone number'],
        ['resume', 'file', 'Yes', 'Resume file (PDF or DOC, max 5MB)'],
        ['cover_letter', 'string', 'No', 'Cover letter text'],
        ['experience', 'string', 'No', 'Work experience summary'],
        ['education', 'string', 'No', 'Education background'],
        ['skills', 'string', 'No', 'Key skills (comma-separated)'],
        ['linkedin_url', 'string', 'No', 'LinkedIn profile URL'],
        ['portfolio_url', 'string', 'No', 'Portfolio or personal website URL'],
    ]
);

// ─── CONTENT / CMS ───────────────────────────────────────────────────────────
sectionHeader($pdf, '12. Content & CMS');

apiBlock($pdf, $BASE, 'About Us', 'GET', '/about-api.php',
    'Get all About Us page sections (company story, mission, vision, team, values, etc.).');

apiBlock($pdf, $BASE, 'Contact Information', 'GET', '/contact-api.php',
    'Get the store\'s contact details including email, phone, address, map link, and social media links.');

apiBlock($pdf, $BASE, 'FAQ List', 'GET', '/faq-api.php',
    'Get all frequently asked questions. Optionally filter by active/inactive status.',
    [['is_active', 'integer', 'No', '1 = active only, 0 = inactive only, omit = all']]
);

apiBlock($pdf, $BASE, 'Help Center', 'GET', '/help-center-api.php',
    'Get all FAQ categories with their associated FAQs nested inside. Supports filtering and empty category handling.',
    [
        ['active_only', 'boolean', 'No', 'true = active only (default: true)'],
        ['include_empty', 'boolean', 'No', 'Include categories with no FAQs (default: true)'],
        ['type', 'string', 'No', 'Filter categories by type'],
    ]
);

apiBlock($pdf, $BASE, 'Policies', 'GET', '/policies-api.php',
    'Get all site policy pages: Privacy Policy, Return & Refund Policy, Terms & Conditions, Shipping Policy, etc.');

apiBlock($pdf, $BASE, 'Logo', 'GET', '/logo-api.php',
    'Get logo assets for the website. Supports multiple query filters to fetch specific logo types.',
    [
        ['(no param)', 'flag', 'No', 'Returns all logos'],
        ['favicon', 'flag', 'No', '?favicon — returns the favicon only'],
        ['desktop_logo', 'flag', 'No', '?desktop_logo — returns the main website logo'],
        ['id', 'integer', 'No', '?id={id} — get a specific logo by ID'],
        ['type', 'string', 'No', '?type={type} — get logos by type (header, footer, etc.)'],
        ['active_type', 'string', 'No', '?active_type={type} — get only the active logo for a type'],
    ]
);

// ─── OUTPUT ───────────────────────────────────────────────────────────────────
$outputPath = __DIR__ . '/Printmont_API_Documentation.pdf';
$pdf->Output($outputPath, 'F');
echo "PDF generated: " . $outputPath . "\n";
?>
