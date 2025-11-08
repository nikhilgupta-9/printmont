<?php
// filepath: c:\xampp\htdocs\printmont-admin\api\banner_api.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include_once "../config/Database.php"; // Include the Database class

// Instantiate database object
$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // ------------------- FETCH ALL OR SINGLE -------------------
    case "GET":
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM banners WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            echo json_encode($result ?: ["message" => "Banner not found"]);
        } else {
            $sql = "SELECT * FROM banners ORDER BY display_order ASC";
            $result = $conn->query($sql);
            $banners = [];
            while ($row = $result->fetch_assoc()) {
                $banners[] = $row;
            }
            echo json_encode($banners);
        }
        break;

    // ------------------- CREATE -------------------
    case "POST":
        // Check if files are uploaded
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $target_url = $_POST['target_url'] ?? '';
        $display_order = $_POST['display_order'] ?? 0;
        $status = $_POST['status'] ?? 'inactive';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;

        // Handle image uploads
        $upload_dir = "../uploads/banners/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $desktop_image = "";
        if (isset($_FILES['image_url_desktop']) && $_FILES['image_url_desktop']['error'] === UPLOAD_ERR_OK) {
            $desktop_image = uniqid() . "_" . basename($_FILES['image_url_desktop']['name']);
            move_uploaded_file($_FILES['image_url_desktop']['tmp_name'], $upload_dir . $desktop_image);
        }

        $mobile_image = "";
        if (isset($_FILES['image_url_mobile']) && $_FILES['image_url_mobile']['error'] === UPLOAD_ERR_OK) {
            $mobile_image = uniqid() . "_" . basename($_FILES['image_url_mobile']['name']);
            move_uploaded_file($_FILES['image_url_mobile']['tmp_name'], $upload_dir . $mobile_image);
        }

        // Save banner info in database
        $sql = "INSERT INTO banners (title, description, image_url_desktop, image_url_mobile, target_url, display_order, status, start_date, end_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssissss",
            $title,
            $description,
            $desktop_image,
            $mobile_image,
            $target_url,
            $display_order,
            $status,
            $start_date,
            $end_date
        );

        if ($stmt->execute()) {
            echo json_encode(["message" => "Banner uploaded successfully"]);
        } else {
            echo json_encode(["message" => "Database error while saving banner"]);
        }
        break;


    // ------------------- UPDATE -------------------
    case "PUT":
        // Get data from $_POST instead of file_get_contents
        $id = $_POST['id'] ?? null;
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $target_url = $_POST['target_url'] ?? '';
        $display_order = $_POST['display_order'] ?? 0;
        $status = $_POST['status'] ?? 'inactive';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;

        // Get existing image filenames from the database
        $sql_select = "SELECT image_url_desktop, image_url_mobile FROM banners WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $result_select = $stmt_select->get_result()->fetch_assoc();

        $existing_desktop_image = $result_select['image_url_desktop'] ?? '';
        $existing_mobile_image = $result_select['image_url_mobile'] ?? '';

        // Handle image uploads
        $upload_dir = "../uploads/banners/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Handle desktop image upload
        $desktop_image = $existing_desktop_image; // Default to existing image
        if (isset($_FILES['image_url_desktop']) && $_FILES['image_url_desktop']['error'] === UPLOAD_ERR_OK) {
            // Delete the old image if it exists
            if (!empty($existing_desktop_image)) {
                @unlink($upload_dir . $existing_desktop_image); // Use @ to suppress warnings
            }
            $desktop_image = uniqid() . "_" . basename($_FILES['image_url_desktop']['name']);
            move_uploaded_file($_FILES['image_url_desktop']['tmp_name'], $upload_dir . $desktop_image);
        }

        // Handle mobile image upload
        $mobile_image = $existing_mobile_image; // Default to existing image
        if (isset($_FILES['image_url_mobile']) && $_FILES['image_url_mobile']['error'] === UPLOAD_ERR_OK) {
            // Delete the old image if it exists
            if (!empty($existing_mobile_image)) {
                @unlink($upload_dir . $existing_mobile_image); // Use @ to suppress warnings
            }
            $mobile_image = uniqid() . "_" . basename($_FILES['image_url_mobile']['name']);
            move_uploaded_file($_FILES['image_url_mobile']['tmp_name'], $upload_dir . $mobile_image);
        }

        // Update banner info in database
        $sql = "UPDATE banners SET title=?, description=?, image_url_desktop=?, image_url_mobile=?, target_url=?, display_order=?, status=?, start_date=?, end_date=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssissssi",
            $title,
            $description,
            $desktop_image,
            $mobile_image,
            $target_url,
            $display_order,
            $status,
            $start_date,
            $end_date,
            $id
        );

        if ($stmt->execute()) {
            echo json_encode(["message" => "Banner updated successfully"]);
        } else {
            echo json_encode(["message" => "Error updating banner"]);
        }
        break;

    // ------------------- DELETE -------------------
    case "DELETE":
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID required for delete"]);
            exit;
        }

        $id = intval($_GET['id']);
        $sql = "DELETE FROM banners WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Banner deleted successfully"]);
        } else {
            echo json_encode(["message" => "Error deleting banner"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
?>