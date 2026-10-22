<?php
/**
 * Customer Notifications API — requires Authorization: Bearer <access_token>.
 *
 * GET  ?action=list&type=all|orders|offers|account|rewards&limit=30&offset=0
 *        → { notifications[], unread_count, has_more }
 * GET  ?action=unread_count                     → { unread_count }
 * POST {action:"mark_read", id}                 → marks one notification read
 * POST {action:"mark_all_read"}                 → marks everything read
 * GET  ?action=prefs                            → { prefs: {category: {channel: bool}} }
 * POST {action:"save_prefs", prefs:{...}}       → saves the preference matrix
 *
 * A notification with user_id NULL is a broadcast (new sale / offer) shown to every customer.
 */
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
require_once(__DIR__ . '/cors.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../controllers/AuthControllerAPI.php');

const CN_CATEGORIES = ['orders', 'offers', 'account', 'rewards'];
const CN_CHANNELS   = ['inapp', 'email', 'sms', 'whatsapp'];
const CN_DEFAULTS   = [
    'orders'  => ['inapp' => true, 'email' => true, 'sms' => true,  'whatsapp' => true],
    'offers'  => ['inapp' => true, 'email' => true, 'sms' => false, 'whatsapp' => false],
    'account' => ['inapp' => true, 'email' => true, 'sms' => true,  'whatsapp' => false],
    'rewards' => ['inapp' => true, 'email' => true, 'sms' => false, 'whatsapp' => false],
];

function cn_bearer(): ?string {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (!$h && function_exists('apache_request_headers')) {
        $all = apache_request_headers();
        $h = $all['Authorization'] ?? ($all['authorization'] ?? null);
    }
    return ($h && preg_match('/Bearer\s(\S+)/', $h, $m)) ? $m[1] : null;
}

function cn_fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $auth = new AuthController($db);

    $token = cn_bearer();
    if (!$token) cn_fail('No token provided', 401);
    $verified = $auth->verifyToken($token);
    if (empty($verified['success'])) cn_fail($verified['error'] ?? 'Unauthorized', 401);
    $userId = (int) $verified['user']['id'];

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $_GET['action'] ?? ($input['action'] ?? '');
    $method = $_SERVER['REQUEST_METHOD'];

    // ---- preferences ----------------------------------------------------
    $loadPrefs = function () use ($db, $userId): array {
        $prefs = CN_DEFAULTS;
        $stmt = $db->prepare('SELECT category, channel, enabled FROM customer_notification_preferences WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if (isset($prefs[$row['category']][$row['channel']])) {
                $prefs[$row['category']][$row['channel']] = (bool) $row['enabled'];
            }
        }
        $stmt->close();
        return $prefs;
    };

    // Categories the customer has switched off for in-app notifications.
    $hiddenCategories = function () use ($loadPrefs): array {
        $hidden = [];
        foreach ($loadPrefs() as $cat => $channels) {
            if (empty($channels['inapp'])) $hidden[] = $cat;
        }
        return $hidden;
    };

    // Shared WHERE for "notifications visible to this user".
    $visibleSql = function (array $hidden): string {
        $sql = "(n.user_id = ? OR n.user_id IS NULL)
                AND n.created_at >= (SELECT created_at FROM users WHERE id = ?)";
        if ($hidden) {
            // $hidden only ever holds values from CN_CATEGORIES.
            $sql .= " AND n.category NOT IN ('" . implode("','", $hidden) . "')";
        }
        return $sql;
    };

    $unreadCount = function () use ($db, $userId, $hiddenCategories, $visibleSql): int {
        $sql = "SELECT COUNT(*) AS c FROM customer_notifications n
                LEFT JOIN customer_notification_reads r ON r.notification_id = n.id AND r.user_id = ?
                WHERE r.user_id IS NULL AND " . $visibleSql($hiddenCategories());
        $stmt = $db->prepare($sql);
        $stmt->bind_param('iii', $userId, $userId, $userId);
        $stmt->execute();
        $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();
        return $count;
    };

    if ($method === 'GET' && ($action === 'list' || $action === '')) {
        $type   = $_GET['type'] ?? 'all';
        $limit  = max(1, min(100, (int) ($_GET['limit'] ?? 30)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $hidden = $hiddenCategories();

        $sql = "SELECT n.id, n.category, n.title, n.message, n.link, n.created_at,
                       (r.user_id IS NOT NULL) AS is_read
                FROM customer_notifications n
                LEFT JOIN customer_notification_reads r ON r.notification_id = n.id AND r.user_id = ?
                WHERE " . $visibleSql($hidden);
        $types = 'iii';
        $params = [$userId, $userId, $userId];
        if ($type !== 'all' && in_array($type, CN_CATEGORIES, true)) {
            $sql .= ' AND n.category = ?';
            $types .= 's';
            $params[] = $type;
        }
        $sql .= ' ORDER BY n.created_at DESC, n.id DESC LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = $limit + 1;
        $params[] = $offset;

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $hasMore = count($rows) > $limit;
        $rows = array_slice($rows, 0, $limit);
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['is_read'] = (bool) $row['is_read'];
        }
        unset($row);

        echo json_encode([
            'success'       => true,
            'notifications' => $rows,
            'unread_count'  => $unreadCount(),
            'has_more'      => $hasMore,
        ]);
        exit;
    }

    if ($method === 'GET' && $action === 'unread_count') {
        echo json_encode(['success' => true, 'unread_count' => $unreadCount()]);
        exit;
    }

    if ($method === 'GET' && $action === 'prefs') {
        echo json_encode(['success' => true, 'prefs' => $loadPrefs()]);
        exit;
    }

    if ($method === 'POST' && $action === 'mark_read') {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) cn_fail('Notification id is required');
        $stmt = $db->prepare(
            'INSERT IGNORE INTO customer_notification_reads (user_id, notification_id)
             SELECT ?, id FROM customer_notifications WHERE id = ? AND (user_id = ? OR user_id IS NULL)'
        );
        $stmt->bind_param('iii', $userId, $id, $userId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'unread_count' => $unreadCount()]);
        exit;
    }

    if ($method === 'POST' && $action === 'mark_all_read') {
        $stmt = $db->prepare(
            'INSERT IGNORE INTO customer_notification_reads (user_id, notification_id)
             SELECT ?, id FROM customer_notifications WHERE user_id = ? OR user_id IS NULL'
        );
        $stmt->bind_param('ii', $userId, $userId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'unread_count' => 0]);
        exit;
    }

    if ($method === 'POST' && $action === 'save_prefs') {
        $incoming = $input['prefs'] ?? null;
        if (!is_array($incoming)) cn_fail('Preferences are required');

        $stmt = $db->prepare(
            'INSERT INTO customer_notification_preferences (user_id, category, channel, enabled)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)'
        );
        foreach (CN_CATEGORIES as $cat) {
            foreach (CN_CHANNELS as $ch) {
                if (!isset($incoming[$cat][$ch])) continue;
                $enabled = $incoming[$cat][$ch] ? 1 : 0;
                $stmt->bind_param('issi', $userId, $cat, $ch, $enabled);
                $stmt->execute();
            }
        }
        $stmt->close();
        echo json_encode(['success' => true, 'prefs' => $loadPrefs(), 'message' => 'Preferences saved']);
        exit;
    }

    cn_fail('Unknown action', 400);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
