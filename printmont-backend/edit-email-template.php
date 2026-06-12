<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/EmailController.php';

$emailController = new EmailController();
$id = $_GET['id'] ?? 0;
$template = $emailController->getTemplateById($id);

if (!$template) {
    $_SESSION['error_message'] = "Email template not found!";
    header("Location: email-templates.php");
    exit;
}

if ($_POST) {
    if (isset($_POST['update_template'])) {
        $data = [
            'template_name' => $_POST['template_name'],
            'template_subject' => $_POST['template_subject'],
            'template_body' => $_POST['template_body'],
            'template_type' => $_POST['template_type'],
            'status' => $_POST['status']
        ];
        
        if ($emailController->updateTemplate($id, $data)) {
            $_SESSION['success_message'] = "Email template updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update email template.";
        }
        header("Location: email-templates.php");
        exit;
    }
}
?>

<!-- Similar structure to the add template page but with pre-filled values -->