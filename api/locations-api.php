<?php
// api/locations-api.php
header('Content-Type: application/json');
require_once(__DIR__ . '/cors.php');

ini_set('display_errors', 0);
error_reporting(0);

// India Post lookup for pincodes outside the admin delivery list, cached in the DB.
// Returns ['city','state'] for a real pincode, false when it does not exist, null when unreachable.
function lookupIndiaPostPincode($conn, $pincode)
{
    $conn->query("CREATE TABLE IF NOT EXISTS pincode_lookup_cache (
        pincode VARCHAR(6) NOT NULL PRIMARY KEY,
        is_valid TINYINT(1) NOT NULL,
        city VARCHAR(100) NULL,
        state VARCHAR(100) NULL,
        checked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $conn->prepare("SELECT is_valid, city, state FROM pincode_lookup_cache WHERE pincode = ? AND checked_at > (NOW() - INTERVAL 30 DAY)");
    $stmt->bind_param('s', $pincode);
    $stmt->execute();
    $cached = $stmt->get_result()->fetch_assoc();
    if ($cached) {
        return $cached['is_valid'] ? ['city' => $cached['city'], 'state' => $cached['state']] : false;
    }

    $ch = curl_init('https://api.postalpincode.in/pincode/' . $pincode);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = ($body && $status === 200) ? json_decode($body, true) : null;
    if (!is_array($json) || !isset($json[0]['Status'])) {
        return null; // unreachable — caller assumes standard delivery
    }

    $valid = $json[0]['Status'] === 'Success' && !empty($json[0]['PostOffice']);
    $city = $valid ? ($json[0]['PostOffice'][0]['District'] ?? null) : null;
    $state = $valid ? ($json[0]['PostOffice'][0]['State'] ?? null) : null;

    $stmt = $conn->prepare("REPLACE INTO pincode_lookup_cache (pincode, is_valid, city, state, checked_at) VALUES (?, ?, ?, ?, NOW())");
    $v = $valid ? 1 : 0;
    $stmt->bind_param('siss', $pincode, $v, $city, $state);
    $stmt->execute();

    return $valid ? ['city' => $city, 'state' => $state] : false;
}

try {
    require_once(__DIR__ . '/../config/database.php');
    $db = new Database();
    $conn = $db->getConnection();

    $action = $_GET['action'] ?? $_POST['action'] ?? 'popular';

    // Clean any prior output buffer
    if (ob_get_length()) {
        ob_clean();
    }

    switch ($action) {
        case 'popular':
        case 'popular_cities': {
            $stmt = $conn->prepare("SELECT id, city_name, state_name, pincode, icon_url, is_popular, is_same_day, is_next_day, standard_delivery_days, delivery_charge, free_delivery_above, display_order FROM delivery_locations WHERE status = 'active' AND is_popular = 1 ORDER BY display_order ASC, city_name ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            exit();
        }

        case 'search': {
            $query = trim($_GET['q'] ?? '');
            if (empty($query)) {
                echo json_encode(['success' => true, 'data' => []]);
                exit();
            }

            $searchParam = "%{$query}%";
            $stmt = $conn->prepare("SELECT id, city_name, state_name, pincode, is_same_day, is_next_day, standard_delivery_days FROM delivery_locations WHERE status = 'active' AND (city_name LIKE ? OR pincode LIKE ? OR state_name LIKE ?) ORDER BY is_popular DESC, city_name ASC LIMIT 10");
            $stmt->bind_param('sss', $searchParam, $searchParam, $searchParam);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            // If it looks like a 6-digit pincode and not directly in DB, return dynamic feasible delivery record
            if (empty($data) && preg_match('/^[1-9][0-9]{5}$/', $query)) {
                $data[] = [
                    'id' => 0,
                    'city_name' => 'Pincode ' . $query,
                    'state_name' => 'India',
                    'pincode' => $query,
                    'is_same_day' => 0,
                    'is_next_day' => 1,
                    'standard_delivery_days' => '2-4 Days'
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            exit();
        }

        case 'check_pincode': {
            // Delivery check for a pincode (product page + cart).
            // 1. Admin list (delivery-locations.php) → its delivery days.
            // 2. Otherwise India Post confirms the pincode exists → standard delivery.
            //    Unknown pincodes are refused; if India Post can't be reached, standard delivery is assumed.
            $pincode = trim($_GET['pincode'] ?? $_POST['pincode'] ?? '');
            if (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
                echo json_encode([
                    'success' => false,
                    'deliverable' => false,
                    'message' => 'Please enter a valid 6-digit Indian pincode.'
                ]);
                exit();
            }

            $stmt = $conn->prepare("SELECT * FROM delivery_locations WHERE pincode = ? AND status = 'active' LIMIT 1");
            $stmt->bind_param('s', $pincode);
            $stmt->execute();
            $location = $stmt->get_result()->fetch_assoc();

            if ($location) {
                $info = [
                    'city_name' => $location['city_name'],
                    'state_name' => $location['state_name'],
                    'is_same_day' => (bool) $location['is_same_day'],
                    'is_next_day' => (bool) $location['is_next_day'],
                    'standard_delivery_days' => $location['standard_delivery_days'],
                ];
            } else {
                $lookup = lookupIndiaPostPincode($conn, $pincode);
                if ($lookup === false) {
                    echo json_encode([
                        'success' => true,
                        'deliverable' => false,
                        'pincode' => $pincode,
                        'message' => "Pincode {$pincode} does not exist. Please check and try again."
                    ]);
                    exit();
                }
                $info = [
                    'city_name' => $lookup['city'] ?? ('Pincode ' . $pincode),
                    'state_name' => $lookup['state'] ?? 'India',
                    'is_same_day' => false,
                    'is_next_day' => false,
                    'standard_delivery_days' => '3-5 Days',
                ];
            }

            // Latest day in "3-5 Days" / "Same Day / 1-2 Days" → expected delivery date
            preg_match_all('/\d+/', (string) $info['standard_delivery_days'], $m);
            $maxDays = $m[0] ? max(array_map('intval', $m[0])) : 5;
            $deliveryBy = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->modify("+{$maxDays} day");

            // COD from admin payment settings
            $codAvailable = false;
            $res = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_group = 'payment_settings' AND setting_key IN ('cod_enabled','cod_restricted_pincodes')");
            $pay = [];
            while ($res && ($row = $res->fetch_assoc())) $pay[$row['setting_key']] = $row['setting_value'];
            if (($pay['cod_enabled'] ?? '1') === '1') {
                $restricted = array_filter(array_map('trim', preg_split('/[\s,]+/', (string) ($pay['cod_restricted_pincodes'] ?? ''))));
                $codAvailable = !in_array($pincode, $restricted, true);
            }

            echo json_encode(array_merge($info, [
                'success' => true,
                'deliverable' => true,
                'pincode' => $pincode,
                'delivery_by' => $deliveryBy->format('Y-m-d'),
                'delivery_by_label' => $deliveryBy->format('D, j M'),
                'cod_available' => $codAvailable,
                'message' => 'Delivery available to ' . $info['city_name'] . ', ' . $info['state_name'],
            ]));
            exit();
        }

        case 'admin_list': {
            $stmt = $conn->prepare("SELECT * FROM delivery_locations ORDER BY display_order ASC, id DESC");
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            exit();
        }

        case 'admin_save': {
            $id = intval($_POST['id'] ?? 0);
            $cityName = trim($_POST['city_name'] ?? '');
            $stateName = trim($_POST['state_name'] ?? '');
            $pincode = trim($_POST['pincode'] ?? '');
            $isPopular = isset($_POST['is_popular']) ? intval($_POST['is_popular']) : 1;
            $isSameDay = isset($_POST['is_same_day']) ? intval($_POST['is_same_day']) : 0;
            $isNextDay = isset($_POST['is_next_day']) ? intval($_POST['is_next_day']) : 1;
            $standardDays = trim($_POST['standard_delivery_days'] ?? '2-4 Days');
            $deliveryCharge = floatval($_POST['delivery_charge'] ?? 0);
            $freeAbove = floatval($_POST['free_delivery_above'] ?? 499);
            $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
            $displayOrder = intval($_POST['display_order'] ?? 0);

            if (empty($cityName)) {
                echo json_encode(['success' => false, 'error' => 'City name is required.']);
                exit();
            }

            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE delivery_locations SET city_name = ?, state_name = ?, pincode = ?, is_popular = ?, is_same_day = ?, is_next_day = ?, standard_delivery_days = ?, delivery_charge = ?, free_delivery_above = ?, status = ?, display_order = ? WHERE id = ?");
                $stmt->bind_param('sssiisddssii', $cityName, $stateName, $pincode, $isPopular, $isSameDay, $isNextDay, $standardDays, $deliveryCharge, $freeAbove, $status, $displayOrder, $id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("INSERT INTO delivery_locations (city_name, state_name, pincode, is_popular, is_same_day, is_next_day, standard_delivery_days, delivery_charge, free_delivery_above, status, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssiisddssi', $cityName, $stateName, $pincode, $isPopular, $isSameDay, $isNextDay, $standardDays, $deliveryCharge, $freeAbove, $status, $displayOrder);
                $stmt->execute();
                $id = $stmt->insert_id;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Location saved successfully.',
                'id' => $id
            ]);
            exit();
        }

        case 'admin_delete': {
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM delivery_locations WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
            echo json_encode(['success' => true, 'message' => 'Location deleted.']);
            exit();
        }

        case 'admin_toggle': {
            $id = intval($_POST['id'] ?? 0);
            $field = $_POST['field'] ?? 'status';
            if ($id > 0 && in_array($field, ['status', 'is_popular', 'is_same_day', 'is_next_day'])) {
                if ($field === 'status') {
                    $conn->query("UPDATE delivery_locations SET status = IF(status='active', 'inactive', 'active') WHERE id = {$id}");
                } else {
                    $conn->query("UPDATE delivery_locations SET {$field} = IF({$field}=1, 0, 1) WHERE id = {$id}");
                }
            }
            echo json_encode(['success' => true, 'message' => 'Status updated.']);
            exit();
        }

        default: {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action.'
            ]);
            exit();
        }
    }

} catch (Exception $e) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit();
}
