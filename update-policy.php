<?php
/**
 * POST handler for the policy editor.
 *
 * Both policy-edit.php and policy-management.php have always posted here, but
 * the file did not exist — and because .htaccess rewrites unknown paths to
 * index.php, the submit returned HTTP 200 (the admin login page) instead of a
 * 404. Saving a policy appeared to work and silently did nothing.
 */
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/PolicyController.php';

// The admin auth check normally rides along inside includes/side-navbar.php,
// which a handler with no UI never includes. Guard explicitly, the same way
// contact-view.php and edit-product.php do — otherwise this endpoint would let
// anyone rewrite the site's policies.
$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

if (!$authController->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: policy-edit.php');
    exit();
}

$policyKey = trim($_POST['policy_key'] ?? '');

if ($policyKey === '') {
    $_SESSION['error_message'] = 'No policy was specified.';
    header('Location: policy-edit.php');
    exit();
}

// Blank point rows are just empty inputs the admin never filled in.
$points = array_values(array_filter(
    array_map('trim', (array) ($_POST['points'] ?? [])),
    fn($p) => $p !== ''
));

$policyController = new PolicyController();

$result = $policyController->updatePolicy($policyKey, [
    'heading'     => $_POST['heading'] ?? '',
    // Rich text from CKEditor: kept as HTML on purpose, so no strip_tags here.
    'description' => $_POST['description'] ?? '',
    'status'      => $_POST['status'] ?? 'active',
    'points'      => $points,
]);

$_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];

// Return to the tab the admin was editing.
header('Location: policy-edit.php#' . urlencode($policyKey));
exit();
