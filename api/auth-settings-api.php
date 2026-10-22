<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = (new Database())->getConnection();
    
    $query = "SELECT setting_key, setting_value FROM site_settings WHERE setting_group = 'login'";
    $result = $db->query($query);
    
    $settings = [
        'desktop_auth_image'   => 'uploads/auth/login_products_art.png',
        'mobile_promo_banner'  => 'uploads/auth/mobile_offer_banner.jpg',
        'mobile_banner_enabled'=> '1',
        'login_heading'        => 'Login',
        'login_subheading'     => 'Get access to your Orders, Wishlist and Recommendations',
        'signup_heading'       => "Looks like you're new here!",
        'signup_subheading'    => 'Sign up with your mobile number to get started'
    ];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['setting_value'])) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'data'    => $settings
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
