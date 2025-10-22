<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';
require_once '../controllers/LogoController.php';

$database = new Database();
$db = $database->getConnection();
$logoController = new LogoController($db);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $logoController->getLogoById($_GET['id']);
            if ($stmt->rowCount() > 0) {
                $logo = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(array("success" => true, "data" => $logo));
            } else {
                echo json_encode(array("success" => false, "message" => "Logo not found"));
            }
        } elseif (isset($_GET['type'])) {
            $stmt = $logoController->getLogosByType($_GET['type']);
            $logos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(array("success" => true, "data" => $logos));
        } elseif (isset($_GET['active_type'])) {
            $stmt = $logoController->getActiveLogoByType($_GET['active_type']);
            if ($stmt->rowCount() > 0) {
                $logo = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(array("success" => true, "data" => $logo));
            } else {
                echo json_encode(array("success" => false, "message" => "No active logo found"));
            }
        } else {
            $stmt = $logoController->getAllLogos();
            $logos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(array("success" => true, "data" => $logos));
        }
        break;

    case 'POST':
        $result = $logoController->createLogo($_POST, $_FILES['logo_file'] ?? null);
        echo json_encode($result);
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $put_vars);
        $result = $logoController->updateLogo($input['id'], $input, $_FILES['logo_file'] ?? null);
        echo json_encode($result);
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $delete_vars);
        $result = $logoController->deleteLogo($input['id']);
        echo json_encode($result);
        break;

    default:
        echo json_encode(array("success" => false, "message" => "Invalid request method"));
        break;
}
?>