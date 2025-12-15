<?php
require_once(__DIR__ . '/../config/database.php');

class LogoController {
    private $conn;
    private $table_name = "website_assets";
    private $upload_dir = "uploads/logos/";

    public function __construct($db) {
        $this->conn = $db;
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }

    // Get all logos
    public function getAllLogos() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY asset_type, upload_timestamp DESC";
        $result = $this->conn->query($query);
        return $result;
    }

    // Get logo by ID
    public function getLogoById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    // Get logos by type
    public function getLogosByType($asset_type) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE asset_type = ? ORDER BY version DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $asset_type);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    // Get active logo by type
    public function getActiveLogoByType($asset_type) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE asset_type = ? AND is_active = 1 ORDER BY version DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $asset_type);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    // Create new logo
    public function createLogo($data, $file) {
        // Validate file upload
        if ($file && $file['error'] == 0) {
            $uploadResult = $this->handleFileUpload($file);
            if (!$uploadResult['success']) {
                return array("success" => false, "message" => $uploadResult['message']);
            }
        } else {
            return array("success" => false, "message" => "No file uploaded or upload error");
        }

        // Get next version number
        $version = $this->getNextVersion($data['asset_type']);

        // Deactivate previous versions if this is set to active
        if (isset($data['is_active']) && $data['is_active'] == 1) {
            $this->deactivateOtherVersions($data['asset_type']);
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  SET asset_type=?, asset_name=?, file_name=?, 
                  file_path=?, file_extension=?, file_size=?, 
                  dimensions=?, uploaded_by=?, is_active=?, 
                  version=?, description=?, alt_text=?, 
                  target_url=?, metadata=?";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $asset_type = htmlspecialchars(strip_tags($data['asset_type']));
        $asset_name = htmlspecialchars(strip_tags($data['asset_name']));
        $description = htmlspecialchars(strip_tags($data['description'] ?? ''));
        $alt_text = htmlspecialchars(strip_tags($data['alt_text'] ?? ''));
        $target_url = htmlspecialchars(strip_tags($data['target_url'] ?? ''));
        $is_active = isset($data['is_active']) ? 1 : 0;
        $uploaded_by = $data['uploaded_by'] ?? 1; // Default to admin user

        // Bind parameters
        $stmt->bind_param("sssssisisissis", 
            $asset_type, 
            $asset_name, 
            $uploadResult['file_name'],
            $uploadResult['file_path'],
            $uploadResult['file_extension'],
            $uploadResult['file_size'],
            $uploadResult['dimensions'],
            $uploaded_by,
            $is_active,
            $version,
            $description,
            $alt_text,
            $target_url,
            $uploadResult['metadata']
        );

        if ($stmt->execute()) {
            return array("success" => true, "message" => "Logo created successfully", "id" => $stmt->insert_id);
        }
        return array("success" => false, "message" => "Unable to create logo: " . $stmt->error);
    }

    // Update logo
    public function updateLogo($id, $data, $file = null) {
        // Get current logo data
        $current_logo = $this->getLogoById($id);
        if ($current_logo->num_rows == 0) {
            return array("success" => false, "message" => "Logo not found");
        }
        $current_data = $current_logo->fetch_assoc();

        $uploadResult = null;
        if ($file && $file['error'] == 0) {
            $uploadResult = $this->handleFileUpload($file);
            if (!$uploadResult['success']) {
                return array("success" => false, "message" => $uploadResult['message']);
            }
            // Delete old file
            $this->deleteFile($current_data['file_path'] . $current_data['file_name']);
        }

        // Deactivate previous versions if this is set to active
        if (isset($data['is_active']) && $data['is_active'] == 1) {
            $this->deactivateOtherVersions($current_data['asset_type'], $id);
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET asset_name=?, description=?, 
                  alt_text=?, target_url=?, is_active=?";

        // Add file fields if new file was uploaded
        if ($uploadResult) {
            $query .= ", file_name=?, file_path=?, file_extension=?, 
                      file_size=?, dimensions=?, metadata=?";
        }

        $query .= " WHERE id=?";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $asset_name = htmlspecialchars(strip_tags($data['asset_name']));
        $description = htmlspecialchars(strip_tags($data['description'] ?? ''));
        $alt_text = htmlspecialchars(strip_tags($data['alt_text'] ?? ''));
        $target_url = htmlspecialchars(strip_tags($data['target_url'] ?? ''));
        $is_active = isset($data['is_active']) ? 1 : 0;

        // Bind parameters based on whether file is uploaded
        if ($uploadResult) {
            $stmt->bind_param("ssssisssisi", 
                $asset_name, 
                $description, 
                $alt_text, 
                $target_url, 
                $is_active,
                $uploadResult['file_name'],
                $uploadResult['file_path'],
                $uploadResult['file_extension'],
                $uploadResult['file_size'],
                $uploadResult['dimensions'],
                $uploadResult['metadata'],
                $id
            );
        } else {
            $stmt->bind_param("ssssii", 
                $asset_name, 
                $description, 
                $alt_text, 
                $target_url, 
                $is_active,
                $id
            );
        }

        if ($stmt->execute()) {
            return array("success" => true, "message" => "Logo updated successfully");
        }
        return array("success" => false, "message" => "Unable to update logo: " . $stmt->error);
    }

    // Delete logo
    public function deleteLogo($id) {
        // Get logo data to delete file
        $logo = $this->getLogoById($id);
        if ($logo->num_rows == 0) {
            return array("success" => false, "message" => "Logo not found");
        }
        $logo_data = $logo->fetch_assoc();

        // Delete file
        $this->deleteFile($logo_data['file_path'] . $logo_data['file_name']);

        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return array("success" => true, "message" => "Logo deleted successfully");
        }
        return array("success" => false, "message" => "Unable to delete logo: " . $stmt->error);
    }

    // Helper methods
    private function handleFileUpload($file) {
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'webp');
        $max_file_size = 5 * 1024 * 1024; // 5MB

        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = $this->upload_dir;
        $target_file = $file_path . $file_name;

        // Validate file type
        if (!in_array($file_extension, $allowed_types)) {
            return array("success" => false, "message" => "Invalid file type. Allowed: " . implode(', ', $allowed_types));
        }

        // Validate file size
        if ($file['size'] > $max_file_size) {
            return array("success" => false, "message" => "File too large. Maximum size: 5MB");
        }

        // Get image dimensions
        $dimensions = '';
        if (in_array($file_extension, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
            $image_info = getimagesize($file['tmp_name']);
            if ($image_info) {
                $dimensions = $image_info[0] . 'x' . $image_info[1];
            }
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $metadata = json_encode(array(
                'original_name' => $file['name'],
                'mime_type' => $file['type'],
                'upload_time' => date('Y-m-d H:i:s')
            ));

            return array(
                "success" => true,
                "file_name" => $file_name,
                "file_path" => $file_path,
                "file_extension" => $file_extension,
                "file_size" => $file['size'],
                "dimensions" => $dimensions,
                "metadata" => $metadata
            );
        }

        return array("success" => false, "message" => "Failed to upload file");
    }

    private function deleteFile($file_path) {
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    private function getNextVersion($asset_type) {
        $query = "SELECT MAX(version) as max_version FROM " . $this->table_name . " WHERE asset_type = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $asset_type);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return ($row['max_version'] ?? 0) + 1;
    }

    private function deactivateOtherVersions($asset_type, $exclude_id = null) {
        $query = "UPDATE " . $this->table_name . " SET is_active = 0 WHERE asset_type = ?";
        if ($exclude_id) {
            $query .= " AND id != ?";
        }
        
        $stmt = $this->conn->prepare($query);
        if ($exclude_id) {
            $stmt->bind_param("si", $asset_type, $exclude_id);
        } else {
            $stmt->bind_param("s", $asset_type);
        }
        $stmt->execute();
    }
}
?>