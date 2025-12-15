<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/LogoController.php');

$database = new Database();
$db = $database->getConnection();
$logoController = new LogoController($db);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $logoController->getLogoById($_GET['id']);
            if ($stmt && $stmt->num_rows > 0) {
                $logo = $stmt->fetch_assoc();
                echo json_encode(["success" => true, "data" => $logo]);
            } else {
                echo json_encode(["success" => false, "message" => "Logo not found"]);
            }

        } elseif (isset($_GET['type'])) {
            $stmt = $logoController->getLogosByType($_GET['type']);
            $logos = [];
            if ($stmt && $stmt->num_rows > 0) {
                while ($row = $stmt->fetch_assoc()) {
                    $logos[] = $row;
                }
            }
            echo json_encode(["success" => true, "data" => $logos]);

        } elseif (isset($_GET['active_type'])) {
            $stmt = $logoController->getActiveLogoByType($_GET['active_type']);
            if ($stmt && $stmt->num_rows > 0) {
                $logo = $stmt->fetch_assoc();
                echo json_encode(["success" => true, "data" => $logo]);
            } else {
                echo json_encode(["success" => false, "message" => "No active logo found"]);
            }

        } else {
            $stmt = $logoController->getAllLogos();
            $logos = [];
            if ($stmt && $stmt->num_rows > 0) {
                while ($row = $stmt->fetch_assoc()) {
                    $logos[] = $row;
                }
            }
            echo json_encode(["success" => true, "data" => $logos]);
        }
        break;

    case 'POST':
        $result = $logoController->createLogo($_POST, $_FILES['logo_file'] ?? null);
        echo json_encode($result);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        $result = $logoController->updateLogo($input['id'], $input, $_FILES['logo_file'] ?? null);
        echo json_encode($result);
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $result = $logoController->deleteLogo($input['id']);
        echo json_encode($result);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid request method"]);
        break;
}
?>