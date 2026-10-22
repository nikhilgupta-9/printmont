<?php
/**
 * seed_homepage_and_category_products.php
 * Populates and maps all homepage and category products into print_prod database.
 */

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();
$pdo = new PDO("mysql:host=127.0.0.1;dbname=print_prod;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Starting Product & Category Mapping...\n";

// 1. Ensure Categories & Subcategories
$categoriesToEnsure = [
    // Level 1
    ['id' => 1, 'name' => 'Personalized Gifts', 'slug' => 'personalized-gifts', 'level' => 1, 'parent_id' => 0],
    ['id' => 17, 'name' => 'Corporate Gifts & Hampers', 'slug' => 'corporate-gifts-hampers', 'level' => 1, 'parent_id' => 0],
    ['id' => 31, 'name' => 'Custom Drinkware & Sippers', 'slug' => 'custom-drinkware-sippers', 'level' => 1, 'parent_id' => 0],
    ['id' => 47, 'name' => 'Custom Apparel & Wearables', 'slug' => 'custom-apparel-wearables', 'level' => 1, 'parent_id' => 0],
    ['id' => 63, 'name' => 'Stationery & Office Gifts', 'slug' => 'stationery-office-gifts', 'level' => 1, 'parent_id' => 0],
    ['id' => 84, 'name' => 'Tech & Smart Electronics', 'slug' => 'tech-smart-electronics', 'level' => 1, 'parent_id' => 0],
    ['id' => 102, 'name' => 'Bags, Wallets & Travel', 'slug' => 'bags-wallets-travel', 'level' => 1, 'parent_id' => 0],
    ['id' => 115, 'name' => 'Plants & Eco-Friendly Gifts', 'slug' => 'plants-eco-friendly-gifts', 'level' => 1, 'parent_id' => 0],
    ['id' => 129, 'name' => 'Gourmet & Chocolate Hampers', 'slug' => 'gourmet-chocolate-hampers', 'level' => 1, 'parent_id' => 0],
    ['id' => 138, 'name' => 'Awards, Trophies & Mementos', 'slug' => 'awards-trophies-mementos', 'level' => 1, 'parent_id' => 0],

    // Apparel Subcategories
    ['id' => 48, 'name' => "Men's Fashion & Shirts", 'slug' => 'mens-fashion-shirts', 'level' => 2, 'parent_id' => 47],
    ['id' => 49, 'name' => "Women's Fashion & Dresses", 'slug' => 'womens-fashion-dresses', 'level' => 2, 'parent_id' => 47],
    ['id' => 50, 'name' => "Kid's Wear & Outfits", 'slug' => 'kids-wear-outfits', 'level' => 2, 'parent_id' => 47],

    // Tech Subcategories
    ['id' => 90, 'name' => 'TWS Earbuds & Headphones', 'slug' => 'earbuds-headphones', 'level' => 2, 'parent_id' => 84],
    ['id' => 91, 'name' => 'Smartphones & Mobiles', 'slug' => 'smartphones-mobiles', 'level' => 2, 'parent_id' => 84],
    ['id' => 94, 'name' => 'Smart Watches & Fitness', 'slug' => 'smart-watches-fitness', 'level' => 2, 'parent_id' => 84],
    ['id' => 97, 'name' => 'Laptops & Tech Accessories', 'slug' => 'laptops-tech-accessories', 'level' => 2, 'parent_id' => 84],

    // Drinkware & Decor Subcategories
    ['id' => 32, 'name' => 'Thermal Flasks & Bottles', 'slug' => 'thermal-flasks-bottles', 'level' => 2, 'parent_id' => 31],
    ['id' => 37, 'name' => 'Coffee Mugs & Cups', 'slug' => 'coffee-mugs-cups', 'level' => 2, 'parent_id' => 31],
    ['id' => 116, 'name' => 'Indoor Plants & Planters', 'slug' => 'indoor-plants-planters', 'level' => 2, 'parent_id' => 115],
    ['id' => 117, 'name' => 'Tableware & Dinnerware', 'slug' => 'tableware-dinnerware', 'level' => 2, 'parent_id' => 115],
    ['id' => 118, 'name' => 'Home Decor & Lamps', 'slug' => 'home-decor-lamps', 'level' => 2, 'parent_id' => 115],
];

foreach ($categoriesToEnsure as $cat) {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$cat['id']]);
    $exists = $stmt->fetch();

    if ($exists) {
        $up = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, level = ?, parent_id = ?, status = 'active', desktop_home_show = 'yes' WHERE id = ?");
        $up->execute([$cat['name'], $cat['slug'], $cat['level'], $cat['parent_id'], $cat['id']]);
    } else {
        $ins = $pdo->prepare("INSERT INTO categories (id, name, slug, level, parent_id, status, desktop_home_show) VALUES (?, ?, ?, ?, ?, 'active', 'yes')");
        $ins->execute([$cat['id'], $cat['name'], $cat['slug'], $cat['level'], $cat['parent_id']]);
    }
}
echo "Categories ensured.\n";

// 2. Define Homepage & Catalog Products List
$products = [
    // ── Women's Outfits & Fashion (Carousel & Mosaics) ─────────────────────
    [
        'id' => 301,
        'name' => "Rust Brown Half-Sleeves Knitted Mens Shirt",
        'slug' => 'rust-brown-half-sleeves-knitted-mens-shirt',
        'sku' => 'PM-MSH-001',
        'category_id' => 47,
        'sub_category_id' => 48,
        'price' => 499.00,
        'regular_price' => 1200.00,
        'offer_price' => 499.00,
        'description' => "Crafted from 100% super combed cotton, this premium knitted shirt offers superior softness, breathability, and lasting all-day comfort.",
        'images' => ['/men_shirt/men-shirt-8.jpeg', '/men_shirt/men-shirt-7.jpeg', '/men_shirt/men-shirt-6.jpeg', '/men_shirt/men-shirt-5.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 302,
        'name' => "Floral Print Summer Designer Kurti",
        'slug' => 'floral-print-summer-designer-kurti',
        'sku' => 'PM-WDR-001',
        'category_id' => 47,
        'sub_category_id' => 49,
        'price' => 499.00,
        'regular_price' => 999.00,
        'offer_price' => 499.00,
        'description' => "Elegant floral print cotton kurti with delicate border embroidery. Perfect for casual outings and daily office wear.",
        'images' => ['/women-dress/women-dress-1.jpeg', '/women-dress/women-dress-2.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 303,
        'name' => "Chikankari Embroidered Anarkali Kurti",
        'slug' => 'chikankari-embroidered-anarkali-kurti',
        'sku' => 'PM-WDR-002',
        'category_id' => 47,
        'sub_category_id' => 49,
        'price' => 699.00,
        'regular_price' => 1499.00,
        'offer_price' => 699.00,
        'description' => "Authentic handcrafted Lucknowi Chikankari embroidered kurti in soft modal cotton fabric.",
        'images' => ['/women-dress/women-dress-2.jpeg', '/women-dress/women-dress-3.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 304,
        'name' => "Festive Georgette Flared Gown",
        'slug' => 'festive-georgette-flared-gown',
        'sku' => 'PM-WDR-003',
        'category_id' => 47,
        'sub_category_id' => 49,
        'price' => 899.00,
        'regular_price' => 1999.00,
        'offer_price' => 899.00,
        'description' => "Floor-length flared designer gown with shimmer accents and flattering silhouette.",
        'images' => ['/women-dress/women-dress-3.jpeg', '/women-dress/women-dress-4.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 305,
        'name' => "Ethnic Block Print Straight Kurta",
        'slug' => 'ethnic-block-print-straight-kurta',
        'sku' => 'PM-WDR-004',
        'category_id' => 47,
        'sub_category_id' => 49,
        'price' => 499.00,
        'regular_price' => 999.00,
        'offer_price' => 499.00,
        'description' => "Traditional Rajasthani block print straight cut kurta with wooden button detailing.",
        'images' => ['/women-dress/women-dress-4.jpeg', '/women-dress/women-dress-5.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 306,
        'name' => "Contemporary Western Tiered Maxi Dress",
        'slug' => 'contemporary-western-tiered-maxi-dress',
        'sku' => 'PM-WDR-005',
        'category_id' => 47,
        'sub_category_id' => 49,
        'price' => 799.00,
        'regular_price' => 1599.00,
        'offer_price' => 799.00,
        'description' => "Breezy tiered maxi dress in lightweight rayon blend with adjustable waist tie.",
        'images' => ['/women-dress/women-dress-5.jpeg', '/women-dress/women-dress-6.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 307,
        'name' => "Party Wear Embroidered Silk Tunic",
        'slug' => 'party-wear-embroidered-silk-tunic',
        'sku' => 'PM-WDR-006',
        'category_id' => 47,
        'sub_category_id' => 49,
        'price' => 649.00,
        'regular_price' => 1299.00,
        'offer_price' => 649.00,
        'description' => "Art silk festive tunic top with delicate zari neck embroidery.",
        'images' => ['/women-dress/women-dress-6.jpeg', '/women-dress/women-dress-7.jpeg'],
        'bestseller' => 0, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 308,
        'name' => "Casual Striped Button-Down Kurti",
        'slug' => 'casual-striped-button-down-kurti',
        'sku' => 'PM-WDR-007',
        'category_id' => 47,
        'sub_category_id' => 49,
        'price' => 549.00,
        'regular_price' => 1099.00,
        'offer_price' => 549.00,
        'description' => "Smart vertical striped everyday wear kurti with 3/4th rollup sleeves.",
        'images' => ['/women-dress/women-dress-7.jpeg', '/women-dress/women-dress-8.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 309,
        'name' => "Festive Foil Printed Kurta Set",
        'slug' => 'festive-foil-printed-kurta-set',
        'sku' => 'PM-WDR-008',
        'category_id' => 47,
        'sub_category_id' => 49,
        'price' => 799.00,
        'regular_price' => 1799.00,
        'offer_price' => 799.00,
        'description' => "Gold foil printed festive kurta paired with matching palazzo pants.",
        'images' => ['/women-dress/women-dress-8.jpeg', '/women-dress/women-dress-9.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],

    // ── Men's Fashion & Shirts ──────────────────────────────────────────────
    [
        'id' => 310,
        'name' => "Navy Blue Sulphur Washed Cotton Shirt",
        'slug' => 'navy-blue-sulphur-washed-cotton-shirt',
        'sku' => 'PM-MSH-002',
        'category_id' => 47,
        'sub_category_id' => 48,
        'price' => 499.00,
        'regular_price' => 1299.00,
        'offer_price' => 499.00,
        'description' => "Super soft sulphur-washed cotton shirt in deep navy blue with breathable weave.",
        'images' => ['/men_shirt/men-shirt-8.jpeg', '/men_shirt/men-shirt-1.png'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 311,
        'name' => "Slim Fit Checkered Casual Shirt",
        'slug' => 'slim-fit-checkered-casual-shirt',
        'sku' => 'PM-MSH-003',
        'category_id' => 47,
        'sub_category_id' => 48,
        'price' => 599.00,
        'regular_price' => 1399.00,
        'offer_price' => 599.00,
        'description' => "Modern check pattern shirt crafted from premium yarn-dyed 100% cotton.",
        'images' => ['/men_shirt/men-shirt-7.jpeg', '/men_shirt/men-shirt-6.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 312,
        'name' => "Solid Linen Blend Mandarin Collar Shirt",
        'slug' => 'solid-linen-blend-mandarin-collar-shirt',
        'sku' => 'PM-MSH-004',
        'category_id' => 47,
        'sub_category_id' => 48,
        'price' => 699.00,
        'regular_price' => 1599.00,
        'offer_price' => 699.00,
        'description' => "Lightweight breathable linen blend shirt with clean band collar.",
        'images' => ['/men_shirt/men-shirt-6.jpeg', '/men_shirt/men-shirt-5.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 313,
        'name' => "Urban Camo Print Casual Shirt",
        'slug' => 'urban-camo-print-casual-shirt',
        'sku' => 'PM-MSH-005',
        'category_id' => 47,
        'sub_category_id' => 48,
        'price' => 499.00,
        'regular_price' => 999.00,
        'offer_price' => 499.00,
        'description' => "Trendy camouflage printed shirt made for casual meetups and weekend outings.",
        'images' => ['/men_shirt/men-shirt-2.jpeg', '/men_shirt/men-shirt-3.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],

    // ── Tableware & Dinnerware (Mosaic Flat 1 & 2) ──────────────────────────
    [
        'id' => 314,
        'name' => "Royal Opalware Ceramic Dinner Set 16 Pcs",
        'slug' => 'royal-opalware-ceramic-dinner-set-16-pcs',
        'sku' => 'PM-TBL-001',
        'category_id' => 115,
        'sub_category_id' => 117,
        'price' => 1499.00,
        'regular_price' => 2999.00,
        'offer_price' => 1499.00,
        'description' => "16-piece microwave safe bone-free ceramic dinner set with gold rim borders.",
        'images' => ['/girl-product-img/big-plate.jpg', '/girl-product-img/dust-cleaner.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 315,
        'name' => "Matte Black Insulated Coffee Travel Mug",
        'slug' => 'matte-black-insulated-coffee-travel-mug',
        'sku' => 'PM-DRK-002',
        'category_id' => 31,
        'sub_category_id' => 37,
        'price' => 499.00,
        'regular_price' => 999.00,
        'offer_price' => 499.00,
        'description' => "Double-walled vacuum insulated mug with leak-proof flip lid and temperature lock.",
        'images' => ['/section-img/pro12.jpeg', '/section-img/pro13.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 316,
        'name' => "Premium Stainless Steel Gold Cutlery Set",
        'slug' => 'premium-stainless-steel-gold-cutlery-set',
        'sku' => 'PM-TBL-002',
        'category_id' => 115,
        'sub_category_id' => 117,
        'price' => 699.00,
        'regular_price' => 1499.00,
        'offer_price' => 699.00,
        'description' => "Mirror polished food-grade stainless steel cutlery set with ergonomic handles.",
        'images' => ['/girl-product-img/dust-cleaner.jpeg', '/girl-product-img/big-plate.jpg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 317,
        'name' => "Crystal Cut Glass Tumbler Set 6 Pcs",
        'slug' => 'crystal-cut-glass-tumbler-set-6-pcs',
        'sku' => 'PM-TBL-003',
        'category_id' => 115,
        'sub_category_id' => 117,
        'price' => 599.00,
        'regular_price' => 1199.00,
        'offer_price' => 599.00,
        'description' => "Heavy-base lead-free crystal glass tumblers for water, juices, and beverages.",
        'images' => ['/girl-product-img/colorfull-plant.jpeg', '/girl-product-img/big-plate.jpg'],
        'bestseller' => 0, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],

    // ── Home Decor & Lamps (Mosaic Flat 1 & 2) ──────────────────────────────
    [
        'id' => 318,
        'name' => "Live Money Plant in Geometric Ceramic Pot",
        'slug' => 'live-money-plant-in-geometric-ceramic-pot',
        'sku' => 'PM-DEC-001',
        'category_id' => 115,
        'sub_category_id' => 116,
        'price' => 399.00,
        'regular_price' => 799.00,
        'offer_price' => 399.00,
        'description' => "Air-purifying natural live money plant in handcrafted ceramic tabletop planter.",
        'images' => ['/girl-product-img/plant-1.jpeg', '/girl-product-img/plant-2.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 319,
        'name' => "Modern Wooden Base Warm LED Table Lamp",
        'slug' => 'modern-wooden-base-warm-led-table-lamp',
        'sku' => 'PM-DEC-002',
        'category_id' => 115,
        'sub_category_id' => 118,
        'price' => 799.00,
        'regular_price' => 1699.00,
        'offer_price' => 799.00,
        'description' => "Minimalist bedside lamp with textured fabric shade and solid pine wood base.",
        'images' => ['/girl-product-img/plant-2.jpeg', '/girl-product-img/plant-3.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 320,
        'name' => "Nordic Silent Sweep Designer Wall Clock",
        'slug' => 'nordic-silent-sweep-designer-wall-clock',
        'sku' => 'PM-DEC-003',
        'category_id' => 115,
        'sub_category_id' => 118,
        'price' => 699.00,
        'regular_price' => 1399.00,
        'offer_price' => 699.00,
        'description' => "12-inch non-ticking quartz wall clock with clean modern minimalist typography.",
        'images' => ['/girl-product-img/plant-3.jpeg', '/girl-product-img/plant-4.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 321,
        'name' => "Artisanal Ceramic Glazed Flower Vase",
        'slug' => 'artisanal-ceramic-glazed-flower-vase',
        'sku' => 'PM-DEC-004',
        'category_id' => 115,
        'sub_category_id' => 118,
        'price' => 499.00,
        'regular_price' => 999.00,
        'offer_price' => 499.00,
        'description' => "Handcrafted terracotta flower vase with dual-tone ceramic glaze finish.",
        'images' => ['/girl-product-img/plant-4.jpeg', '/girl-product-img/plant-1.jpeg'],
        'bestseller' => 0, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],

    // ── Tech & Audio (Grouped Mosaic 3) ─────────────────────────────────────
    [
        'id' => 322,
        'name' => "5G Ultra Smartphone 8GB 128GB AMOLED",
        'slug' => '5g-ultra-smartphone-8gb-128gb-amoled',
        'sku' => 'PM-ELC-001',
        'category_id' => 84,
        'sub_category_id' => 91,
        'price' => 18999.00,
        'regular_price' => 24999.00,
        'offer_price' => 18999.00,
        'description' => "Next-gen 5G smartphone with 120Hz AMOLED display and 64MP AI triple camera.",
        'images' => ['/electro/mobile-1.jpeg', '/section-img/pro14.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 323,
        'name' => "TWS Active Noise Cancelling Earbuds 40H Playtime",
        'slug' => 'tws-active-noise-cancelling-earbuds-40h-playtime',
        'sku' => 'PM-ELC-002',
        'category_id' => 84,
        'sub_category_id' => 90,
        'price' => 1999.00,
        'regular_price' => 3999.00,
        'offer_price' => 1999.00,
        'description' => "True Wireless Earbuds with 30dB ANC, quad mic ENC calling, and fast USB-C charging.",
        'images' => ['/electro/buds-1.jpeg', '/electro/buds-2.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 324,
        'name' => "Slim Ergonomic Aluminum Laptop Stand",
        'slug' => 'slim-ergonomic-aluminum-laptop-stand',
        'sku' => 'PM-ELC-003',
        'category_id' => 84,
        'sub_category_id' => 97,
        'price' => 999.00,
        'regular_price' => 1999.00,
        'offer_price' => 999.00,
        'description' => "Foldable 6-level height adjustable aluminum alloy stand for laptops up to 17 inches.",
        'images' => ['/electro/lap-1.jpeg', '/electro/lap-2.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 325,
        'name' => "Bluetooth Calling Smartwatch with HD Display",
        'slug' => 'bluetooth-calling-smartwatch-with-hd-display',
        'sku' => 'PM-ELC-004',
        'category_id' => 84,
        'sub_category_id' => 94,
        'price' => 1799.00,
        'regular_price' => 3499.00,
        'offer_price' => 1799.00,
        'description' => "1.96-inch curved HD display smartwatch with 100+ sports modes and SpO2 tracking.",
        'images' => ['/electro/watch-1.jpeg', '/electro/watch-2.jpeg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],

    // ── Kids Wear & Outfits (Grouped Mosaic 1) ──────────────────────────────
    [
        'id' => 326,
        'name' => "Kids 100% Bio-Wash Cotton Printed T-Shirt",
        'slug' => 'kids-100-bio-wash-cotton-printed-t-shirt',
        'sku' => 'PM-KID-001',
        'category_id' => 47,
        'sub_category_id' => 50,
        'price' => 349.00,
        'regular_price' => 699.00,
        'offer_price' => 349.00,
        'description' => "Ultra-soft hypoallergenic bio-washed cotton t-shirt for boys and girls.",
        'images' => ['/sq/kid1.jpg', '/sq/kid2.jpg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 327,
        'name' => "Kids Breathable Lightweight Sports Shoes",
        'slug' => 'kids-breathable-lightweight-sports-shoes',
        'sku' => 'PM-KID-002',
        'category_id' => 47,
        'sub_category_id' => 50,
        'price' => 699.00,
        'regular_price' => 1499.00,
        'offer_price' => 699.00,
        'description' => "Slip-on cushioned running shoes with anti-skid TPR soles.",
        'images' => ['/sq/kid2.jpg', '/sq/kid3.jpg'],
        'bestseller' => 1, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
    [
        'id' => 328,
        'name' => "Educational Wooden Building Blocks Gift Set",
        'slug' => 'educational-wooden-building-blocks-gift-set',
        'sku' => 'PM-KID-003',
        'category_id' => 1,
        'sub_category_id' => 2,
        'price' => 599.00,
        'regular_price' => 1199.00,
        'offer_price' => 599.00,
        'description' => "Non-toxic organic wooden shapes and colors construction set for toddlers.",
        'images' => ['/sq/kid3.jpg', '/sq/kid1.jpg'],
        'bestseller' => 0, 'top_rated' => 1, 'top_selection' => 1, 'top_deal' => 1
    ],
];

foreach ($products as $p) {
    // Check if product exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$p['id']]);
    $existing = $stmt->fetch();

    $thumb = $p['images'][0] ?? '';

    if ($existing) {
        $up = $pdo->prepare("
            UPDATE products SET 
                name = ?, product_slug = ?, sku = ?, category_id = ?, sub_category_id = ?,
                price = ?, regular_price = ?, offer_price = ?, discount_price = ?, description = ?,
                thumbnail_image = ?, stock_quantity = 100, status = 'active',
                our_bestseller = ?, top_rated = ?, top_selection = ?, top_deal = ?, top_deal_by_categories = ?, minimum_quantity = 1
            WHERE id = ?
        ");
        $up->execute([
            $p['name'], $p['slug'], $p['sku'], $p['category_id'], $p['sub_category_id'],
            $p['price'], $p['regular_price'], $p['offer_price'], $p['price'], $p['description'],
            $thumb, $p['bestseller'], $p['top_rated'], $p['top_selection'], $p['top_deal'], $p['top_deal'],
            $p['id']
        ]);
        $prodId = $p['id'];
    } else {
        $ins = $pdo->prepare("
            INSERT INTO products (
                id, name, product_slug, sku, category_id, sub_category_id,
                price, regular_price, offer_price, discount_price, description,
                thumbnail_image, stock_quantity, status,
                our_bestseller, top_rated, top_selection, top_deal, top_deal_by_categories, minimum_quantity
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 100, 'active', ?, ?, ?, ?, ?, 1)
        ");
        $ins->execute([
            $p['id'], $p['name'], $p['slug'], $p['sku'], $p['category_id'], $p['sub_category_id'],
            $p['price'], $p['regular_price'], $p['offer_price'], $p['price'], $p['description'],
            $thumb, $p['bestseller'], $p['top_rated'], $p['top_selection'], $p['top_deal'], $p['top_deal']
        ]);
        $prodId = $p['id'];
    }

    // Manage product_images table
    $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$prodId]);

    $order = 0;
    foreach ($p['images'] as $img) {
        $isPrimary = ($order === 0) ? 1 : 0;
        $imgStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)");
        $imgStmt->execute([$prodId, $img, $isPrimary, $order]);
        $order++;
    }
}

echo "Successfully seeded and mapped " . count($products) . " products to categories!\n";
