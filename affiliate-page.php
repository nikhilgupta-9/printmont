<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';

$db = (new Database())->getConnection();

/** Pages this screen manages, and the section types each one supports. */
$PAGES = [
    'affiliate' => [
        'label' => 'Affiliate Program',
        'types' => [
            'hero'      => 'Hero — title, intro, button label in "extra"',
            'highlight' => 'Highlight card — title and description',
            'step'      => 'How it works step — title and description',
            'rate'      => 'Commission row — category in title, rate in "extra"',
            'faq'       => 'FAQ — question in title, answer in content',
            'contact'   => 'Closing line — sentence in title, email in "extra"',
        ],
    ],
    'business-solutions' => [
        'label' => 'Business Solutions',
        'types' => [
            'hero'    => 'Hero — title, intro, button label in "extra"',
            'feature' => 'Service card — title and description',
            'step'    => 'How it works step — title and description',
            'contact' => 'Closing line — sentence in title, email in "extra"',
        ],
    ],
];

$pageKey = $_GET['page'] ?? 'affiliate';
if (!isset($PAGES[$pageKey])) {
    $pageKey = 'affiliate';
}

// ---------- Write actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $id    = (int) ($_POST['id'] ?? 0);
            $type  = trim($_POST['section_type'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $body  = trim($_POST['content'] ?? '');
            $extra = trim($_POST['extra'] ?? '');
            $order = (int) ($_POST['display_order'] ?? 0);
            $live  = isset($_POST['is_active']) ? 1 : 0;

            if ($title === '') {
                throw new Exception('A title is required.');
            }
            if (!isset($PAGES[$pageKey]['types'][$type])) {
                throw new Exception('Unknown section type for this page.');
            }

            if ($id > 0) {
                $stmt = $db->prepare(
                    "UPDATE page_sections
                     SET section_type = ?, title = ?, content = ?, extra = ?, display_order = ?, is_active = ?
                     WHERE id = ? AND page_key = ?"
                );
                $stmt->bind_param('ssssiiis', $type, $title, $body, $extra, $order, $live, $id, $pageKey);
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO page_sections (page_key, section_type, title, content, extra, display_order, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('sssssii', $pageKey, $type, $title, $body, $extra, $order, $live);
            }

            if (!$stmt || !$stmt->execute()) {
                throw new Exception('Could not save the section.');
            }
            $_SESSION['success_message'] = $id > 0 ? 'Section updated.' : 'Section added.';
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM page_sections WHERE id = ? AND page_key = ?");
            $stmt->bind_param('is', $id, $pageKey);
            $stmt->execute();
            $_SESSION['success_message'] = 'Section deleted.';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }

    header('Location: affiliate-page.php?page=' . urlencode($pageKey));
    exit();
}

// ---------- Read ----------
$sections = [];
$stmt = $db->prepare(
    "SELECT * FROM page_sections WHERE page_key = ? ORDER BY display_order ASC, id ASC"
);
$stmt->bind_param('s', $pageKey);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $sections[] = $row;
}
$stmt->close();

$editing = null;
if (!empty($_GET['edit'])) {
    foreach ($sections as $s) {
        if ((int) $s['id'] === (int) $_GET['edit']) {
            $editing = $s;
            break;
        }
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $PAGES[$pageKey]['label']; ?> | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>

        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-3">
                    <div class="col-auto">
                        <h3><strong><?php echo $PAGES[$pageKey]['label']; ?></strong> Page</h3>
                    </div>
                    <div class="col-auto ms-auto">
                        <?php foreach ($PAGES as $key => $meta): ?>
                            <a href="affiliate-page.php?page=<?php echo urlencode($key); ?>"
                               class="btn btn-sm <?php echo $key === $pageKey ? 'btn-primary' : 'btn-light'; ?>">
                                <?php echo $meta['label']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Editor -->
                    <div class="col-12 col-xl-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><?php echo $editing ? 'Edit Section' : 'Add Section'; ?></h5>
                                <h6 class="card-subtitle text-muted">
                                    Shown on the storefront <?php echo strtolower($PAGES[$pageKey]['label']); ?> page.
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="affiliate-page.php?page=<?php echo urlencode($pageKey); ?>">
                                    <input type="hidden" name="action" value="save">
                                    <input type="hidden" name="id" value="<?php echo (int) ($editing['id'] ?? 0); ?>">

                                    <div class="mb-3">
                                        <label class="form-label">Section Type</label>
                                        <select name="section_type" class="form-select" required>
                                            <?php foreach ($PAGES[$pageKey]['types'] as $value => $label): ?>
                                                <option value="<?php echo $value; ?>"
                                                    <?php echo ($editing['section_type'] ?? '') === $value ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title" required
                                               value="<?php echo htmlspecialchars($editing['title'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Content</label>
                                        <textarea class="form-control" name="content" rows="4"><?php echo htmlspecialchars($editing['content'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Extra</label>
                                        <input type="text" class="form-control" name="extra"
                                               placeholder="Commission rate, or hero button label"
                                               value="<?php echo htmlspecialchars($editing['extra'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Display Order</label>
                                        <input type="number" class="form-control" name="display_order"
                                               value="<?php echo (int) ($editing['display_order'] ?? 0); ?>">
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                            <?php echo (!$editing || (int) $editing['is_active'] === 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $editing ? 'Update Section' : 'Add Section'; ?>
                                    </button>
                                    <?php if ($editing): ?>
                                        <a href="affiliate-page.php?page=<?php echo urlencode($pageKey); ?>" class="btn btn-light">Cancel</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- List -->
                    <div class="col-12 col-xl-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Sections (<?php echo count($sections); ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!$sections): ?>
                                    <div class="text-center py-4">
                                        <h5>No sections yet</h5>
                                        <p class="text-muted mb-0">Add one using the form to build this page.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Order</th>
                                                    <th>Type</th>
                                                    <th>Title</th>
                                                    <th>Extra</th>
                                                    <th>Status</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($sections as $s): ?>
                                                <tr>
                                                    <td><?php echo (int) $s['display_order']; ?></td>
                                                    <td><code><?php echo htmlspecialchars($s['section_type']); ?></code></td>
                                                    <td><?php echo htmlspecialchars($s['title']); ?></td>
                                                    <td class="text-muted"><?php echo htmlspecialchars($s['extra'] ?: '—'); ?></td>
                                                    <td>
                                                        <?php echo (int) $s['is_active'] === 1
                                                            ? '<span class="badge bg-success">Active</span>'
                                                            : '<span class="badge bg-secondary">Hidden</span>'; ?>
                                                    </td>
                                                    <td class="text-end" style="white-space:nowrap;">
                                                        <a class="btn btn-sm btn-light"
                                                           href="affiliate-page.php?page=<?php echo urlencode($pageKey); ?>&edit=<?php echo (int) $s['id']; ?>">Edit</a>
                                                        <form method="POST" class="d-inline"
                                                              action="affiliate-page.php?page=<?php echo urlencode($pageKey); ?>"
                                                              onsubmit="return confirm('Delete this section?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
