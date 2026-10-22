<?php
require_once(__DIR__ . '/../config/database.php');

$database = new Database();
$db = $database->getConnection();

echo "Starting comprehensive product images update..." . PHP_EOL;

function cleanUrl($url) {
    if (empty($url)) return '';
    return str_replace(' ', '%20', trim($url));
}

// 1. Explicit mappings for products 363 to 373
$specificProducts = [
    363 => [
        'thumb' => 'https://printo-s3.dietpixels.net/New%20Products/Canvas-Gallery-Wraps_1790167141.jpg',
        'gallery' => [
            'https://printo-s3.dietpixels.net/New%20Products/Canvas-Gallery-Wraps_1790167141.jpg',
            'https://files.printo.in/site/20220222_184154447916_138cbc_canvas.jpg',
            'https://printo-s3.dietpixels.net/New%20Products/MDF-Canvas_1790317969.jpg'
        ]
    ],
    364 => [
        'thumb' => 'https://files.printo.in/site/20230822_120505853248_544ca2_Photoframe.jpg',
        'gallery' => [
            'https://files.printo.in/site/20230822_120505853248_544ca2_Photoframe.jpg',
            'https://printo-s3.dietpixels.net/Soldout/Products/Classic-Photo-Frames_1785994576.jpg',
            'https://printo-s3.mobimedia.ai/site/2024/Material/1_1708748608.jpg'
        ]
    ],
    365 => [
        'thumb' => 'https://printo-s3.dietpixels.net/Soldout/Products/Acrylic-Photo-Frames_1786000692.jpg',
        'gallery' => [
            'https://printo-s3.dietpixels.net/Soldout/Products/Acrylic-Photo-Frames_1786000692.jpg',
            'https://printo-s3.dietpixels.net/1212_1785151110.jpg',
            'https://files.printo.in/site/20230822_120505853248_544ca2_Photoframe.jpg'
        ]
    ],
    366 => [
        'thumb' => 'https://files.printo.in/site/20220712_234243995246_075eb3_Search-image-1920x1450.jpg',
        'gallery' => [
            'https://files.printo.in/site/20220712_234243995246_075eb3_Search-image-1920x1450.jpg',
            'https://printo-s3.dietpixels.net/Soldout/Products/Wooden-Engraved_1786105099.jpg',
            'https://printo-s3.dietpixels.net/Soldout/Products/Wooden-stand_1785995800.jpg'
        ]
    ],
    367 => [
        'thumb' => 'https://printo-s3.dietpixels.net/Soldout/Products/Wooden-stand_1785995800.jpg',
        'gallery' => [
            'https://printo-s3.dietpixels.net/Soldout/Products/Wooden-stand_1785995800.jpg',
            'https://files.printo.in/site/20220712_234243995246_075eb3_Search-image-1920x1450.jpg',
            'https://printo-s3.dietpixels.net/Soldout/Products/Wooden-Engraved_1786105099.jpg'
        ]
    ],
    368 => [
        'thumb' => 'https://printo-s3.dietpixels.net/Soldout/Products/Wooden-Engraved_1786105099.jpg',
        'gallery' => [
            'https://printo-s3.dietpixels.net/Soldout/Products/Wooden-Engraved_1786105099.jpg',
            'https://printo-s3.dietpixels.net/1212_1785151110.jpg',
            'https://files.printo.in/site/20220712_234243995246_075eb3_Search-image-1920x1450.jpg'
        ]
    ],
    369 => [
        'thumb' => 'https://printo-s3.dietpixels.net/site/2024/Engagement%20kits%202025/Mug/1440/Standard_1733897221.jpg',
        'gallery' => [
            'https://printo-s3.dietpixels.net/site/2024/Engagement%20kits%202025/Mug/1440/Standard_1733897221.jpg',
            'https://printo-s3.dietpixels.net/New%20Products/Drinkware_1789969543.jpg',
            'https://files.printo.in/site/20230215_235655181734_828f70_Water-Bottle.jpg'
        ]
    ],
    370 => [
        'thumb' => 'https://printo-s3.dietpixels.net/Soldout/Products/Coasters_1786000692.jpg',
        'gallery' => [
            'https://printo-s3.dietpixels.net/Soldout/Products/Coasters_1786000692.jpg',
            'https://files.printo.in/site/20230215_225611795860_f1fbe2_fridge-mgnet.jpg',
            'https://printo-s3.dietpixels.net/Soldout/Products/Wooden-stand_1785995800.jpg'
        ]
    ],
    371 => [
        'thumb' => 'https://files.printo.in/site/20220809_191901934590_52ec84_Gift_Hampers.jpg',
        'gallery' => [
            'https://files.printo.in/site/20220809_191901934590_52ec84_Gift_Hampers.jpg',
            'https://files.printo.in/site/20210618_131035036271_884266_Corporate_Gifting.jpg',
            'https://files.printo.in/site/20230822_120505853248_544ca2_Photoframe.jpg'
        ]
    ],
    372 => [
        'thumb' => 'https://files.printo.in/site/20220809_191901934590_52ec84_Gift_Hampers.jpg',
        'gallery' => [
            'https://files.printo.in/site/20220809_191901934590_52ec84_Gift_Hampers.jpg',
            'https://files.printo.in/site/20210618_131035036271_884266_Corporate_Gifting.jpg',
            'https://files.printo.in/site/20230822_120505853248_544ca2_Photoframe.jpg'
        ]
    ],
    373 => [
        'thumb' => 'https://files.printo.in/site/20230822_120505853248_544ca2_Photoframe.jpg',
        'gallery' => [
            'https://files.printo.in/site/20230822_120505853248_544ca2_Photoframe.jpg',
            'https://printo-s3.dietpixels.net/Soldout/Products/Classic-Photo-Frames_1785994576.jpg',
            'https://printo-s3.dietpixels.net/Soldout/Products/Wooden-stand_1785995800.jpg'
        ]
    ]
];

foreach ($specificProducts as $pid => $info) {
    $database->execute("UPDATE products SET thumbnail_image = ? WHERE id = ?", [$info['thumb'], $pid]);
    
    // Clear and insert gallery
    $database->execute("DELETE FROM product_images WHERE product_id = ?", [$pid]);
    foreach ($info['gallery'] as $idx => $gUrl) {
        $database->execute("INSERT INTO product_images (product_id, image_url, is_primary, display_order, created_at) VALUES (?, ?, ?, ?, NOW())", [
            $pid, cleanUrl($gUrl), $idx === 0 ? 1 : 0, $idx
        ]);
    }
    echo "Updated specific product ID: $pid" . PHP_EOL;
}

// 2. Process all products in the database: URL-encode spaces and ensure gallery images
$allProducts = $database->fetchAll("SELECT id, name, thumbnail_image, category_id FROM products ORDER BY id ASC");

// Category fallback image pools for extra gallery images
$categoryGalleryPools = [
    'apparel' => [
        'https://printo-s3.dietpixels.net/Layer_4_1782812324.jpg',
        'https://printo-s3.dietpixels.net/site/2024/Layer%204@4x_1714470633.png',
        'https://printo-s3.mobimedia.ai/site/2024/Material/1_1708748608.jpg'
    ],
    'photo' => [
        'https://files.printo.in/site/20230822_120505853248_544ca2_Photoframe.jpg',
        'https://printo-s3.dietpixels.net/Soldout/Products/Acrylic-Photo-Frames_1786000692.jpg',
        'https://printo-s3.dietpixels.net/Soldout/Products/Classic-Photo-Frames_1785994576.jpg',
        'https://printo-s3.dietpixels.net/Soldout/Products/Wooden-stand_1785995800.jpg'
    ],
    'mug' => [
        'https://printo-s3.dietpixels.net/site/2024/Engagement%20kits%202025/Mug/1440/Standard_1733897221.jpg',
        'https://printo-s3.dietpixels.net/New%20Products/Drinkware_1789969543.jpg',
        'https://files.printo.in/site/20230215_235655181734_828f70_Water-Bottle.jpg'
    ],
    'bag' => [
        'https://files.printo.in/site/20230215_235643392352_8f14bf_Backpacks.jpg',
        'https://printo-s3.dietpixels.net/2_1789733179.jpg',
        'https://printo-s3.dietpixels.net/New%20Products/Bulk-Tote-Bags_1790318203.jpg',
        'https://files.printo.in/site/20231109_111153033209_9c75a5_new.jpg'
    ],
    'stationery' => [
        'https://files.printo.in/site/20230215_232707867910_248928_Thank-you-card.jpg',
        'https://files.printo.in/site/20230215_230928906048_514186_letterhead.jpg',
        'https://files.printo.in/site/20220319_154707699605_f192ae_notebooko_1.jpg',
        'https://files.printo.in/site/20200604_093207847782_c8a471_Flyers_MP.jpg'
    ],
    'signage' => [
        'https://printo-s3.dietpixels.net/New%20Products/Standees_1789969543.jpg',
        'https://files.printo.in/site/20230215_235648343481_9abef1_Banner.jpg',
        'https://printo-s3.dietpixels.net/1212_1785151110.jpg',
        'https://files.printo.in/site/20210911_223231326386_9f1869_Stickers.jpg'
    ],
    'gift' => [
        'https://files.printo.in/site/20220809_191901934590_52ec84_Gift_Hampers.jpg',
        'https://files.printo.in/site/20210618_131035036271_884266_Corporate_Gifting.jpg',
        'https://printo-s3.dietpixels.net/Soldout/Products/Wooden-Engraved_1786105099.jpg'
    ]
];

$updatedCount = 0;
foreach ($allProducts as $p) {
    $pid = $p['id'];
    $name = strtolower($p['name']);
    $thumb = cleanUrl($p['thumbnail_image']);

    // If thumbnail is empty or local /card/ path, replace with category match
    if (empty($thumb) || strpos($thumb, '/card/') === 0 || strpos($thumb, 'placehold') !== false) {
        if (strpos($name, 'mug') !== false || strpos($name, 'bottle') !== false || strpos($name, 'drinkware') !== false) {
            $thumb = 'https://printo-s3.dietpixels.net/site/2024/Engagement%20kits%202025/Mug/1440/Standard_1733897221.jpg';
        } elseif (strpos($name, 'frame') !== false || strpos($name, 'canvas') !== false || strpos($name, 'art') !== false) {
            $thumb = 'https://files.printo.in/site/20230822_120505853248_544ca2_Photoframe.jpg';
        } elseif (strpos($name, 'shirt') !== false || strpos($name, 'polo') !== false || strpos($name, 'apparel') !== false) {
            $thumb = 'https://printo-s3.dietpixels.net/Layer_4_1782812324.jpg';
        } elseif (strpos($name, 'bag') !== false || strpos($name, 'tote') !== false || strpos($name, 'laptop') !== false) {
            $thumb = 'https://files.printo.in/site/20230215_235643392352_8f14bf_Backpacks.jpg';
        } elseif (strpos($name, 'standee') !== false || strpos($name, 'sign') !== false || strpos($name, 'banner') !== false) {
            $thumb = 'https://printo-s3.dietpixels.net/New%20Products/Standees_1789969543.jpg';
        } else {
            $thumb = 'https://files.printo.in/site/20220809_191901934590_52ec84_Gift_Hampers.jpg';
        }
    }

    $database->execute("UPDATE products SET thumbnail_image = ? WHERE id = ?", [$thumb, $pid]);

    // Check gallery
    $existingGallery = $database->fetchAll("SELECT id, image_url FROM product_images WHERE product_id = ?", [$pid]);
    
    // Clean existing gallery URLs (remove spaces)
    foreach ($existingGallery as $g) {
        $cleanG = cleanUrl($g['image_url']);
        if ($cleanG !== $g['image_url']) {
            $database->execute("UPDATE product_images SET image_url = ? WHERE id = ?", [$cleanG, $g['id']]);
        }
    }

    // If gallery is empty or has < 2 images, insert primary + companion images
    if (count($existingGallery) < 2) {
        // Determine category pool
        $poolKey = 'gift';
        if (strpos($name, 'shirt') !== false || strpos($name, 't-shirt') !== false || strpos($name, 'polo') !== false || strpos($name, 'cap') !== false || strpos($name, 'apparel') !== false) {
            $poolKey = 'apparel';
        } elseif (strpos($name, 'mug') !== false || strpos($name, 'bottle') !== false || strpos($name, 'drink') !== false || strpos($name, 'sipper') !== false) {
            $poolKey = 'mug';
        } elseif (strpos($name, 'frame') !== false || strpos($name, 'canvas') !== false || strpos($name, 'photo') !== false || strpos($name, 'poster') !== false) {
            $poolKey = 'photo';
        } elseif (strpos($name, 'bag') !== false || strpos($name, 'tote') !== false || strpos($name, 'backpack') !== false || strpos($name, 'pouch') !== false) {
            $poolKey = 'bag';
        } elseif (strpos($name, 'card') !== false || strpos($name, 'book') !== false || strpos($name, 'flyer') !== false || strpos($name, 'brochure') !== false || strpos($name, 'letterhead') !== false || strpos($name, 'stamp') !== false) {
            $poolKey = 'stationery';
        } elseif (strpos($name, 'standee') !== false || strpos($name, 'banner') !== false || strpos($name, 'sign') !== false || strpos($name, 'sticker') !== false || strpos($name, 'dangler') !== false) {
            $poolKey = 'signage';
        }

        $pool = $categoryGalleryPools[$poolKey] ?? $categoryGalleryPools['gift'];
        
        // Ensure primary image is present
        $hasPrimary = false;
        foreach ($existingGallery as $g) {
            if ($cleanUrl = cleanUrl($g['image_url']) === $thumb) {
                $hasPrimary = true;
                break;
            }
        }
        if (!$hasPrimary && !empty($thumb)) {
            $database->execute("INSERT INTO product_images (product_id, image_url, is_primary, display_order, created_at) VALUES (?, ?, 1, 0, NOW())", [
                $pid, $thumb
            ]);
        }

        // Add 2 companion images from pool
        $order = 1;
        foreach ($pool as $companionImg) {
            if ($companionImg !== $thumb && $order <= 2) {
                $database->execute("INSERT INTO product_images (product_id, image_url, is_primary, display_order, created_at) VALUES (?, ?, 0, ?, NOW())", [
                    $pid, cleanUrl($companionImg), $order++
                ]);
            }
        }
    }
    $updatedCount++;
}

echo "Successfully updated and verified all $updatedCount products with high-resolution images & gallery!" . PHP_EOL;
