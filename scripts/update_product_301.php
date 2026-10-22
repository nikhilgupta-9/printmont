<?php
require_once __DIR__ . '/../config/database.php';

$db = new Database();

$data = [
    'brand' => 'Xordox',
    'minimum_quantity' => 10,
    'show_customization_label' => 1,
    'customization_label' => 'Customizable',
    'show_sizes' => 1,
    'show_colors' => 1,
    'size_attributes' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
    'color_attributes' => json_encode([
        ['name' => 'Rust Brown', 'hex' => '#8B3A2B', 'price' => 0],
        ['name' => 'White', 'hex' => '#FFFFFF', 'price' => 0],
        ['name' => 'Black', 'hex' => '#1A1A1A', 'price' => 0],
        ['name' => 'Navy Blue', 'hex' => '#002060', 'price' => 0],
        ['name' => 'Maroon', 'hex' => '#800000', 'price' => 0],
        ['name' => 'Heather Grey', 'hex' => '#9E9E9E', 'price' => 0],
    ]),
    'material_attributes' => json_encode(['100% Cotton 180gsm', '100% Bio-Wash 220gsm', 'Poly-Cotton Blend']),
    'printing_location' => json_encode(['Front Side', 'Back Side', 'Left Chest', 'Front & Back']),
    'customization_fields' => json_encode([
        ['label' => 'Upload Custom Artwork / Logo', 'type' => 'file', 'required' => false],
        ['label' => 'Custom Name or Message', 'type' => 'text', 'required' => false],
    ]),
    'type' => 'Round Neck',
    'material' => '100% Cotton 180gsm',
    'color' => 'Rust Brown',
    'occasion' => 'Casual & Workwear',
    'gender' => 'Men',
    'ideal_for' => 'Men / Unisex',
    'customization_tech' => 'Full Colour Print',
    'customization_location' => 'Front Side',
    'features' => '100% Super Combed Cotton, Intellieaze Breathability, Bio-Washed Finish, Reinforced Double Stitching',
    'long_description' => '<p>Celebrate love and cherished moments with this personalized photo frame / custom garment, tailored with special images, the couple\'s names, and a significant date. Perfect for weddings, anniversaries, or any milestone, it is a heartfelt way to honour their journey together. This item beautifully preserves their memories, adding sentimental value to their home.</p>',
    'instructions' => '<p>Machine wash inside out in cold water with similar colors. Do not bleach. Tumble dry low. Do not iron directly over the print area.</p>',
    'delivery_info' => '<p>Standard shipping takes 3-5 business days across major cities. Express courier delivery available at checkout. 10-Day replacement guarantee on manufacturing defects.</p>',
    'cancel_available' => 1,
    'cancel_time' => 10,
    'cancel_type' => 'days',
    'cod_available' => 1,
    'shipping_method_status' => 1,
    'local_shipping_charge' => 0.00,
    'regional_shipping_charge' => 0.00,
    'national_shipping_charge' => 0.00,
    'bulk_price_variance' => json_encode([
        ['min_qty' => 10, 'max_qty' => 24, 'price' => 499],
        ['min_qty' => 25, 'max_qty' => 49, 'price' => 449],
        ['min_qty' => 50, 'max_qty' => 99, 'price' => 399],
        ['min_qty' => 100, 'max_qty' => 499, 'price' => 349],
        ['min_qty' => 500, 'max_qty' => 1000, 'price' => 299],
    ]),
    'show_bulk_form' => 1,
    'show_help_button' => 1,
    'help_button_text' => 'Need Help?',
    'help_button_link' => '/contact'
];

$setParts = [];
$params = [];
foreach ($data as $col => $val) {
    $setParts[] = "`$col` = ?";
    $params[] = $val;
}
$params[] = 301;

$sql = "UPDATE products SET " . implode(', ', $setParts) . " WHERE id = ?";
$ok = $db->execute($sql, $params);
echo "Update product 301 status: " . ($ok ? "SUCCESS" : "FAILED") . PHP_EOL;
