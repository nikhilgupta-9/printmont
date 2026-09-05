<?php
/**
 * JSON endpoint behind the page editor.
 *
 * Every write goes through here: the browser never names a database table
 * or column. It sends the page, the band, the part and the editor's own
 * field names, and services/PageEditor.php looks the mapping up in the
 * same schema the screen was drawn from. A tampered request can only
 * reach fields the schema already exposes.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/services/PageEditor.php';

header('Content-Type: application/json; charset=utf-8');

const PE_UPLOAD_DIR = 'uploads/page-sections/';

function pe_fail(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function pe_ok(array $payload = []): void
{
    echo json_encode(['success' => true] + $payload);
    exit();
}

// ---------- Who is asking ----------
try {
    $db = (new Database())->getConnection();
    $auth = new AuthController($db);
    if (!$auth->isLoggedIn()) {
        pe_fail('Your session has expired. Sign in again.', 401);
    }
} catch (Exception $e) {
    error_log('Page editor auth error: ' . $e->getMessage());
    pe_fail('Could not verify your session.', 401);
}

$editor = new PageEditor($db, require __DIR__ . '/config/page-editor-schema.php');

$action = $_REQUEST['action'] ?? '';
$pageKey = $_REQUEST['page'] ?? '';
$schema = $editor->page($pageKey);

if (!$schema) {
    pe_fail('Unknown page.');
}

// Writes carry the token the editor page put in the session.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = $_POST['csrf'] ?? '';
    if (empty($_SESSION['pe_csrf']) || !hash_equals($_SESSION['pe_csrf'], $sent)) {
        pe_fail('This page was open too long. Reload and try again.', 419);
    }
}

/** The band and part a request names, or an error if the address is not real. */
function pe_address(PageEditor $editor, array $schema): array
{
    $bandKey = $_REQUEST['band'] ?? '';
    $index = (int) ($_REQUEST['part'] ?? 0);

    $band = null;
    foreach ($schema['bands'] as $candidate) {
        if ($candidate['key'] === $bandKey) {
            $band = $candidate;
        }
    }
    $part = $editor->partAt($schema, $bandKey, $index);

    if (!$band || !$part) {
        pe_fail('Unknown section of this page.');
    }
    return [$band, $part, $index];
}

/** Store an uploaded image and return its relative path. */
function pe_store_image(array $file, string $pageKey): string
{
    $dir = PE_UPLOAD_DIR . $pageKey . '/';
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
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

    $target = $dir . time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('The image could not be saved.');
    }

    return $target;
}

/** Delete an uploaded file, but only ever one of ours. */
function pe_drop_image(?string $path): void
{
    if (!$path || strpos($path, 'uploads/') !== 0 || strpos($path, '..') !== false) {
        return;
    }
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Turn posted field values into the canonical fields the engine stores,
 * following the schema rather than anything the browser claims.
 */
function pe_collect(array $part, array $existing, string $pageKey): array
{
    $values = [
        'title'      => $existing['title'] ?? '',
        'content'    => $existing['content'] ?? '',
        'extra'      => $existing['extra'] ?? '',
        'image_path' => $existing['image_path'] ?? '',
    ];
    $splits = [];

    foreach ($part['fields'] as $field) {
        $column = $field['column'];
        $name = $field['name'];

        if (($field['input'] ?? 'text') === 'image') {
            $current = $values[$column];

            if (!empty($_FILES[$name]) && $_FILES[$name]['error'] === UPLOAD_ERR_OK) {
                $values[$column] = pe_store_image($_FILES[$name], $pageKey);
                pe_drop_image($current);   // replace rather than orphan
            } elseif (!empty($_POST[$name . '__remove'])) {
                pe_drop_image($current);
                $values[$column] = '';
            }
            continue;
        }

        $value = trim((string) ($_POST[$name] ?? ''));

        if (!empty($field['required']) && $value === '') {
            throw new Exception($field['label'] . ' cannot be empty.');
        }

        if (isset($field['part'])) {
            $splits[$column][(int) $field['part']] = $value;
        } else {
            $values[$column] = $value;
        }
    }

    // Re-join the columns that hold two values behind a separator.
    foreach ($splits as $column => $pieces) {
        ksort($pieces);
        $separator = '|';
        foreach ($part['fields'] as $field) {
            if ($field['column'] === $column && isset($field['separator'])) {
                $separator = $field['separator'];
            }
        }
        $values[$column] = implode($separator, $pieces);
    }

    // A pinned part always owns its key, whatever the browser sent.
    if (!empty($part['match']['extra'])) {
        $values['extra'] = $part['match']['extra'];
    }

    return $values;
}

// ------------------------------------------------------------------
try {
    switch ($action) {

        case 'list':
            pe_ok(['rows' => $editor->rows($schema, $pageKey)]);

        case 'save': {
            [$band, $part, $index] = pe_address($editor, $schema);
            $id = (int) ($_POST['id'] ?? 0);

            $existing = [];
            if ($id > 0) {
                $raw = $editor->row($schema, $part, $pageKey, $id);
                if (!$raw) {
                    pe_fail('That section no longer exists. Reload the page.');
                }
                $existing = $editor->normalise($editor->source($schema, $part, $pageKey), $raw);
            }

            $values = pe_collect($part, $existing, $pageKey);
            $active = isset($_POST['is_active']) ? (bool) $_POST['is_active'] : true;

            $savedId = $editor->save($schema, $part, $pageKey, $id, $values, $active);
            $saved = $editor->row($schema, $part, $pageKey, $savedId);

            pe_ok([
                'message' => $id > 0 ? 'Saved.' : 'Added.',
                'bucket'  => PageEditor::bucket($band['key'], $index),
                'section' => $editor->normalise($editor->source($schema, $part, $pageKey), $saved),
            ]);
        }

        case 'duplicate': {
            [$band, $part, $index] = pe_address($editor, $schema);
            $id = (int) ($_POST['id'] ?? 0);

            $raw = $editor->row($schema, $part, $pageKey, $id);
            if (!$raw) {
                pe_fail('That section no longer exists.');
            }

            $source = $editor->source($schema, $part, $pageKey);
            $copy = $editor->normalise($source, $raw);

            // The copy starts hidden so a half-finished duplicate never
            // appears on the live site, and shares the original's image.
            $newId = $editor->save($schema, $part, $pageKey, 0, [
                'title'      => $copy['title'] . ' (copy)',
                'content'    => $copy['content'],
                'extra'      => $copy['extra'],
                'image_path' => '',
            ], false);

            pe_ok([
                'message' => 'Copied. The copy is hidden until you show it.',
                'bucket'  => PageEditor::bucket($band['key'], $index),
                'section' => $editor->normalise($source, $editor->row($schema, $part, $pageKey, $newId)),
            ]);
        }

        case 'delete': {
            [, $part] = pe_address($editor, $schema);
            $id = (int) ($_POST['id'] ?? 0);

            $raw = $editor->row($schema, $part, $pageKey, $id);
            if (!$raw) {
                pe_fail('That section no longer exists.');
            }

            $source = $editor->source($schema, $part, $pageKey);
            pe_drop_image($editor->normalise($source, $raw)['image_path']);
            $editor->delete($schema, $part, $pageKey, $id);

            pe_ok(['message' => 'Deleted.']);
        }

        case 'toggle': {
            [, $part] = pe_address($editor, $schema);
            $id = (int) ($_POST['id'] ?? 0);
            $active = !empty($_POST['is_active']);

            if (!$editor->row($schema, $part, $pageKey, $id)) {
                pe_fail('That section no longer exists.');
            }
            $editor->toggle($schema, $part, $pageKey, $id, $active);

            pe_ok(['message' => $active ? 'Shown on the site.' : 'Hidden from the site.']);
        }

        case 'reorder': {
            [, $part] = pe_address($editor, $schema);
            $ids = json_decode($_POST['ids'] ?? '[]', true);

            if (!is_array($ids) || !$ids) {
                pe_fail('Nothing to reorder.');
            }
            $editor->reorder($schema, $part, $pageKey, $ids);

            pe_ok(['message' => 'Order saved.']);
        }
    }
} catch (Exception $e) {
    pe_fail($e->getMessage());
}

pe_fail('Unknown action.');
