<?php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/LogoController.php';

// Initialize Logo Controller
$database = new Database();
$db = $database->getConnection();
$logoController = new LogoController($db);

$logo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($logo_id > 0) {
    // Get logo details before deletion for confirmation
    $logo_result = $logoController->getLogoById($logo_id);
    
    if ($logo_result->num_rows > 0) {
        $logo = $logo_result->fetch_assoc();
        
        // Delete the logo
        $result = $logoController->deleteLogo($logo_id);
        
        if ($result['success']) {
            $_SESSION['flash_message'] = array(
                'message' => 'Logo deleted successfully!',
                'type' => 'success'
            );
        } else {
            $_SESSION['flash_message'] = array(
                'message' => 'Error deleting logo: ' . $result['message'],
                'type' => 'danger'
            );
        }
    } else {
        $_SESSION['flash_message'] = array(
            'message' => 'Logo not found!',
            'type' => 'danger'
        );
    }
} else {
    $_SESSION['flash_message'] = array(
        'message' => 'Invalid logo ID!',
        'type' => 'danger'
    );
}

// Redirect back to logo management page
header("Location: logo-management.php");
exit();
?>