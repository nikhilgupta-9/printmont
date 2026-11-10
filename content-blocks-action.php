<?php
/**
 * JSON endpoint behind the Website Content block builder.
 *
 * Unlike the page editor, a content block is not pinned to one table
 * shape — it is one row in content_blocks: a page_key the admin typed,
 * a pattern name, and a `data` JSON blob whose fields come from
 * config/content-block-patterns.php. The browser never invents a field;
 * every value is checked against that pattern's own field list before
 * it is stored.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

header('Content-Type: application/json; charset=utf-8');

const CB_UPLOAD_DIR = 'uploads/content-blocks/';

function cb_fail(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function cb_ok(array $payload = []): void
{
    echo json_encode(['success' => true] + $payload);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $auth = new AuthController($db);
    if (!$auth->isLoggedIn()) {
        cb_fail('Your session has expired. Sign in again.', 401);
    }
} catch (Exception $e) {
    error_log('Content blocks auth error: ' . $e->getMessage());
    cb_fail('Could not verify your session.', 401);
}

$patterns = require __DIR__ . '/config/content-block-patterns.php';

if (empty($_SESSION['cb_csrf'])) {
    $_SESSION['cb_csrf'] = bin2hex(random_bytes(16));
}

$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = $_POST['csrf'] ?? '';
    if (empty($_SESSION['cb_csrf']) || !hash_equals($_SESSION['cb_csrf'], $sent)) {
        cb_fail('This page was open too long. Reload and try again.', 419);
    }
}

/** One block by id, decoded, or null. */
function cb_find($db, int $id): ?array
{
    $stmt = $db->prepare('SELECT * FROM content_blocks WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }
    $row['data'] = json_decode($row['data'], true) ?: [];
    $row['id'] = (int) $row['id'];
    $row['display_order'] = (int) $row['display_order'];
    $row['is_active'] = (int) $row['is_active'];
    return $row;
}

/** Store an uploaded image and return its relative path. */
function cb_store_image(array $file): string
{
    if (!is_dir(CB_UPLOAD_DIR) && !mkdir(CB_UPLOAD_DIR, 0777, true) && !is_dir(CB_UPLOAD_DIR)) {
        throw new Exception('The upload folder could not be created.');
    }
    if (getimagesize($file['tmp_name']) === false) {
        throw new Exception('That file is not an image.');
    }
    if ($file['size'] > 5000000) {
        throw new Exception('That image is larger than 5MB.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        throw new Exception('Only JPG, JPEG, PNG, GIF and WEBP files are allowed.');
    }
    $target = CB_UPLOAD_DIR . time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('The image could not be saved.');
    }
    return $target;
}

function cb_drop_image(?string $path): void
{
    if (!$path || strpos($path, 'uploads/') !== 0 || strpos($path, '..') !== false) {
        return;
    }
    if (is_file($path)) {
        @unlink($path);
    }
}

/** One field's posted value, following its own input type — never guessed. */
function cb_field_value(array $field, string $prefix, array $existing)
{
    $name = $prefix . $field['name'];

    if ($field['input'] === 'image') {
        $current = $existing[$field['name']] ?? '';
        if (!empty($_FILES[$name]) && $_FILES[$name]['error'] === UPLOAD_ERR_OK) {
            $path = cb_store_image($_FILES[$name]);
            cb_drop_image($current);
            return $path;
        }
        if (!empty($_POST[$name . '__remove'])) {
            cb_drop_image($current);
            return '';
        }
        return $current;
    }

    return trim((string) ($_POST[$name] ?? ''));
}

/**
 * Posted values for a pattern's own fields, keyed exactly as its schema
 * names them. A repeater sends its own row count (data_<name>_count) so
 * the server never has to guess how many rows the browser built.
 */
function cb_collect(array $fields, string $prefix, array $existing): array
{
    $out = [];
    foreach ($fields as $field) {
        if ($field['input'] === 'repeater') {
            $count = (int) ($_POST[$prefix . $field['name'] . '_count'] ?? 0);
            $items = [];
            for ($i = 0; $i < $count; $i++) {
                $itemPrefix = $prefix . $field['name'] . '[' . $i . '][';
                $items[] = cb_collect_flat($field['fields'], $itemPrefix);
            }
            $out[$field['name']] = $items;
        } else {
            $out[$field['name']] = cb_field_value($field, $prefix, $existing);
        }
    }
    return $out;
}

/** Like cb_collect, but for a repeater item's own flat (non-nested) fields. */
function cb_collect_flat(array $fields, string $prefix): array
{
    $out = [];
    foreach ($fields as $field) {
        $out[$field['name']] = trim((string) ($_POST[$prefix . $field['name'] . ']'] ?? ''));
    }
    return $out;
}

try {
    switch ($action) {

        case 'list':
            $stmt = $db->prepare('SELECT * FROM content_blocks ORDER BY page_key ASC, display_order ASC, id ASC');
            $stmt->execute();
            $rows = [];
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $row['data'] = json_decode($row['data'], true) ?: [];
                $row['id'] = (int) $row['id'];
                $row['display_order'] = (int) $row['display_order'];
                $row['is_active'] = (int) $row['is_active'];
                $rows[] = $row;
            }
            $stmt->close();
            cb_ok(['blocks' => $rows, 'patterns' => $patterns, 'csrf' => $_SESSION['cb_csrf']]);

        case 'save': {
            $id = (int) ($_POST['id'] ?? 0);
            $pageKey = trim((string) ($_POST['page_key'] ?? ''));
            $pattern = trim((string) ($_POST['pattern'] ?? ''));
            $title = trim((string) ($_POST['title'] ?? ''));
            $active = isset($_POST['is_active']) ? (int) (bool) $_POST['is_active'] : 1;

            if ($pageKey === '') {
                cb_fail('Tell this block which page it belongs to.');
            }
            if (!isset($patterns[$pattern])) {
                cb_fail('Unknown design pattern.');
            }

            $existing = [];
            if ($id > 0) {
                $current = cb_find($db, $id);
                if (!$current) {
                    cb_fail('That section no longer exists. Reload the page.');
                }
                $existing = $current['data'];
            }

            $data = cb_collect($patterns[$pattern]['fields'], 'data_', $existing);
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE content_blocks SET page_key=?, pattern=?, title=?, data=?, is_active=? WHERE id=?');
                $stmt->bind_param('ssssii', $pageKey, $pattern, $title, $json, $active, $id);
                if (!$stmt->execute()) {
                    throw new Exception('The section could not be saved.');
                }
                $stmt->close();
            } else {
                $next = $db->query("SELECT COALESCE(MAX(display_order), 0) + 1 AS n FROM content_blocks WHERE page_key = '" . $db->real_escape_string($pageKey) . "'")->fetch_assoc()['n'];
                $stmt = $db->prepare('INSERT INTO content_blocks (page_key, pattern, title, data, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('ssssii', $pageKey, $pattern, $title, $json, $next, $active);
                if (!$stmt->execute()) {
                    throw new Exception('The section could not be added.');
                }
                $id = $db->insert_id;
                $stmt->close();
            }

            cb_ok(['message' => 'Saved.', 'block' => cb_find($db, $id)]);
        }

        case 'delete': {
            $id = (int) ($_POST['id'] ?? 0);
            $row = cb_find($db, $id);
            if (!$row) {
                cb_fail('That section no longer exists.');
            }
            foreach ($row['data'] as $value) {
                if (is_string($value) && strpos($value, CB_UPLOAD_DIR) === 0) {
                    cb_drop_image($value);
                }
            }
            $stmt = $db->prepare('DELETE FROM content_blocks WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            cb_ok(['message' => 'Deleted.']);
        }

        case 'toggle': {
            $id = (int) ($_POST['id'] ?? 0);
            $active = !empty($_POST['is_active']) ? 1 : 0;
            if (!cb_find($db, $id)) {
                cb_fail('That section no longer exists.');
            }
            $stmt = $db->prepare('UPDATE content_blocks SET is_active = ? WHERE id = ?');
            $stmt->bind_param('ii', $active, $id);
            $stmt->execute();
            cb_ok(['message' => $active ? 'Shown on the site.' : 'Hidden from the site.']);
        }

        case 'reorder': {
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            if (!is_array($ids) || !$ids) {
                cb_fail('Nothing to reorder.');
            }
            $stmt = $db->prepare('UPDATE content_blocks SET display_order = ? WHERE id = ?');
            $position = 1;
            foreach ($ids as $rawId) {
                $rowId = (int) $rawId;
                if ($rowId <= 0) {
                    continue;
                }
                $stmt->bind_param('ii', $position, $rowId);
                $stmt->execute();
                $position++;
            }
            cb_ok(['message' => 'Order saved.']);
        }
    }
} catch (Exception $e) {
    cb_fail($e->getMessage());
}

cb_fail('Unknown action.');
