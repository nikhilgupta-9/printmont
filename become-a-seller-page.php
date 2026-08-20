<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';

$db = (new Database())->getConnection();

const SELLER_PAGE_KEY = 'become-a-seller';
const SELLER_UPLOAD_DIR = 'uploads/seller-page/';

/**
 * Section types on this page. `hint` explains what the shared columns mean
 * for that type, since title/content/extra do different jobs per section.
 */
$TYPES = [
    'hero'     => ['label' => 'Hero banner',        'hint' => 'Headline in Title, sub-line in Content, button label in Extra. Image is the banner background.'],
    'stat'     => ['label' => 'Stat',               'hint' => 'Figure in Title (e.g. 10,000+), label in Content.'],
    'heading'  => ['label' => 'Section heading',    'hint' => 'Dark half in Title. Content is "blue half|intro paragraph". Extra is the band: why, stories, journey, tools, platform, help.'],
    'benefit'  => ['label' => 'Why-sell card',      'hint' => 'Title and description. Image replaces the default icon.'],
    'story'    => ['label' => 'Seller story',       'hint' => 'Name in Title, quote in Content, company in Extra. Image is the portrait.'],
    'journey'  => ['label' => 'Journey step',       'hint' => 'Title and description. Image is the step artwork.'],
    'tool'     => ['label' => 'Growth tool',        'hint' => 'Title, description, link path in Extra (e.g. /contact).'],
    'platform' => ['label' => 'Platform slide',     'hint' => 'Title, description, button label in Extra. Image is the screenshot.'],
    'label'    => ['label' => 'Button / label',     'hint' => 'Caption in Title. Extra is the key: stories_cta, journey_cta, form_submit, tools_watermark.'],
    'topic'    => ['label' => 'Enquiry topic',      'hint' => 'One dropdown option. Title only.'],
];

/**
 * Store an uploaded image and return its relative path.
 * Mirrors AboutUsController::uploadImage — same limits and allowed types.
 */
function seller_upload_image(array $file): string
{
    if (!is_dir(SELLER_UPLOAD_DIR)) {
        mkdir(SELLER_UPLOAD_DIR, 0777, true);
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

    $target = SELLER_UPLOAD_DIR . time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('The image could not be saved.');
    }

    return $target;
}

// ---------- Write ----------
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
            $image = trim($_POST['existing_image'] ?? '');

            if ($title === '') {
                throw new Exception('A title is required.');
            }
            if (!isset($TYPES[$type])) {
                throw new Exception('Unknown section type.');
            }

            if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $new = seller_upload_image($_FILES['image']);
                // Replace rather than orphan the previous file.
                if ($image !== '' && is_file($image)) {
                    @unlink($image);
                }
                $image = $new;
            }

            if (!empty($_POST['remove_image']) && $image !== '') {
                if (is_file($image)) {
                    @unlink($image);
                }
                $image = '';
            }

            if ($id > 0) {
                $stmt = $db->prepare(
                    "UPDATE page_sections
                     SET section_type = ?, title = ?, content = ?, extra = ?, image_path = ?, display_order = ?, is_active = ?
                     WHERE id = ? AND page_key = ?"
                );
                $key = SELLER_PAGE_KEY;
                $stmt->bind_param('sssssiiis', $type, $title, $body, $extra, $image, $order, $live, $id, $key);
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $key = SELLER_PAGE_KEY;
                $stmt->bind_param('ssssssii', $key, $type, $title, $body, $extra, $image, $order, $live);
            }

            if (!$stmt || !$stmt->execute()) {
                throw new Exception('Could not save the section.');
            }
            $_SESSION['success_message'] = $id > 0 ? 'Section updated.' : 'Section added.';
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $key = SELLER_PAGE_KEY;

            // Remove the image file along with the row.
            $find = $db->prepare("SELECT image_path FROM page_sections WHERE id = ? AND page_key = ?");
            $find->bind_param('is', $id, $key);
            $find->execute();
            $row = $find->get_result()->fetch_assoc();
            if (!empty($row['image_path']) && is_file($row['image_path'])) {
                @unlink($row['image_path']);
            }
            $find->close();

            $stmt = $db->prepare("DELETE FROM page_sections WHERE id = ? AND page_key = ?");
            $stmt->bind_param('is', $id, $key);
            $stmt->execute();
            $_SESSION['success_message'] = 'Section deleted.';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }

    header('Location: become-a-seller-page.php');
    exit();
}

// ---------- Read ----------
$sections = [];
$key = SELLER_PAGE_KEY;
$stmt = $db->prepare("SELECT * FROM page_sections WHERE page_key = ? ORDER BY display_order ASC, id ASC");
$stmt->bind_param('s', $key);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $sections[$row['section_type']][] = $row;
}
$stmt->close();

$editing = null;
if (!empty($_GET['edit'])) {
    foreach ($sections as $group) {
        foreach ($group as $s) {
            if ((int) $s['id'] === (int) $_GET['edit']) {
                $editing = $s;
            }
        }
    }
}

$total = array_sum(array_map('count', $sections));
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Become a Seller | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .sec-thumb { width: 54px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #e0e0e0; }
        .sec-none  { color: #adb5bd; font-size: 12px; }
        .type-hint { font-size: 12px; color: #6c757d; }
    </style>
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
                        <h3><strong>Become a Seller</strong> Page</h3>
                        <p class="text-muted mb-0">Every heading, card and label on the storefront seller page.</p>
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
                            </div>
                            <div class="card-body">
                                <form method="POST" action="become-a-seller-page.php" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="save">
                                    <input type="hidden" name="id" value="<?php echo (int) ($editing['id'] ?? 0); ?>">
                                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editing['image_path'] ?? ''); ?>">

                                    <div class="mb-3">
                                        <label class="form-label">Section Type</label>
                                        <select name="section_type" id="section_type" class="form-select" required>
                                            <?php foreach ($TYPES as $value => $meta): ?>
                                                <option value="<?php echo $value; ?>"
                                                        data-hint="<?php echo htmlspecialchars($meta['hint']); ?>"
                                                    <?php echo ($editing['section_type'] ?? '') === $value ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($meta['label']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="type-hint mt-1" id="type_hint"></div>
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
                                               value="<?php echo htmlspecialchars($editing['extra'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Image</label>
                                        <?php if (!empty($editing['image_path'])): ?>
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <img src="<?php echo htmlspecialchars($editing['image_path']); ?>" class="sec-thumb" alt="">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image">
                                                    <label class="form-check-label" for="remove_image">Remove</label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">
                                        <div class="form-text">JPG, PNG, GIF or WEBP, up to 5MB. Optional — icons are used when empty.</div>
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
                                        <a href="become-a-seller-page.php" class="btn btn-light">Cancel</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- List, grouped by band -->
                    <div class="col-12 col-xl-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Sections (<?php echo $total; ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!$total): ?>
                                    <div class="text-center py-4">
                                        <h5>No sections yet</h5>
                                        <p class="text-muted mb-0">Add one to build the page.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($TYPES as $type => $meta): ?>
                                        <?php if (empty($sections[$type])) continue; ?>
                                        <h6 class="fw-bold mt-3 mb-2">
                                            <?php echo htmlspecialchars($meta['label']); ?>
                                            <span class="text-muted">(<?php echo count($sections[$type]); ?>)</span>
                                        </h6>
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-hover mb-0">
                                                <tbody>
                                                <?php foreach ($sections[$type] as $s): ?>
                                                    <tr>
                                                        <td style="width:70px;">
                                                            <?php if (!empty($s['image_path'])): ?>
                                                                <img src="<?php echo htmlspecialchars($s['image_path']); ?>" class="sec-thumb" alt="">
                                                            <?php else: ?>
                                                                <span class="sec-none">no image</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($s['title']); ?></strong>
                                                            <?php if (!empty($s['extra'])): ?>
                                                                <br><span class="text-muted small"><?php echo htmlspecialchars($s['extra']); ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td style="width:70px;"><?php echo (int) $s['display_order']; ?></td>
                                                        <td style="width:90px;">
                                                            <?php echo (int) $s['is_active'] === 1
                                                                ? '<span class="badge bg-success">Active</span>'
                                                                : '<span class="badge bg-secondary">Hidden</span>'; ?>
                                                        </td>
                                                        <td class="text-end" style="white-space:nowrap; width:150px;">
                                                            <a class="btn btn-sm btn-light" href="become-a-seller-page.php?edit=<?php echo (int) $s['id']; ?>">Edit</a>
                                                            <form method="POST" class="d-inline" action="become-a-seller-page.php"
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
                                    <?php endforeach; ?>
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
<script>
    // Show what title/content/extra mean for the selected type.
    (function () {
        var select = document.getElementById('section_type');
        var hint = document.getElementById('type_hint');
        if (!select || !hint) return;

        function render() {
            var opt = select.options[select.selectedIndex];
            hint.textContent = opt ? opt.getAttribute('data-hint') || '' : '';
        }

        select.addEventListener('change', render);
        render();
    })();
</script>
</body>
</html>
