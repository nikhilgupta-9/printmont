<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

$bannerController = new BannerController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id > 0 && $action === 'toggle') {
        $bannerController->toggleBannerStatus($id, ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive');
        $_SESSION['success_message'] = 'Banner status updated.';
    } elseif ($id > 0 && $action === 'delete') {
        $res = $bannerController->deleteBanner($id);
        if (!empty($res['success'])) {
            $_SESSION['success_message'] = 'Banner deleted.';
        } else {
            $_SESSION['error_message'] = $res['error'] ?? 'Could not delete the banner.';
        }
    }
    $qs = $_POST['return'] ?? '';
    header('Location: page-banners.php' . ($qs !== '' ? '?' . ltrim($qs, '?') : ''));
    exit;
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$pageFilter    = trim($_GET['page'] ?? '');
$sectionFilter = (int)($_GET['section_id'] ?? 0);

$allBanners = $bannerController->getPageBanners();
$allSections = $bannerController->getPageSections();

$pageCounts = [];
foreach ($allSections as $s) {
    $pageCounts[$s['page']] = ($pageCounts[$s['page']] ?? 0) + (int)$s['banner_count'];
}
ksort($pageCounts);

$grouped = [];
foreach ($allSections as $s) {
    if ($pageFilter !== '' && $s['page'] !== $pageFilter) continue;
    if ($sectionFilter > 0 && (int)$s['id'] !== $sectionFilter) continue;
    $grouped[$s['id']] = ['section' => $s, 'banners' => []];
}
foreach ($allBanners as $b) {
    if (isset($grouped[$b['section_id']])) $grouped[$b['section_id']]['banners'][] = $b;
}

function pb_label($page) { return ucwords(str_replace(['-', '_'], ' ', $page)); }
function pb_img($path) {
    if (!$path) return '';
    return preg_match('#^https?://#', $path) ? $path : htmlspecialchars($path);
}
$returnQs = $_SERVER['QUERY_STRING'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Page Banners &amp; Carousels | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; background-color: #f1f5f9; }
        .pg-pills { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
        .pg-pill { padding:7px 14px; border-radius:999px; background:#fff; border:1px solid #e2e8f0; color:#475569; font-size:13px; font-weight:600; text-decoration:none; }
        .pg-pill:hover { border-color:#93c5fd; color:#0b53a1; }
        .pg-pill.active { background:#0b53a1; border-color:#0b53a1; color:#fff; }
        .pg-pill .c { opacity:.75; margin-left:4px; font-weight:500; }
        .pg-section { background:#fff; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:20px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.04); }
        .pg-section-head { padding:14px 20px; background:#fafbfc; border-bottom:1px solid #eef2f6; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .pg-section-head h5 { margin:0; font-size:15px; font-weight:700; color:#1e293b; }
        .pg-badge { font-size:11px; font-weight:700; padding:3px 9px; border-radius:999px; text-transform:uppercase; letter-spacing:.03em; }
        .pg-badge.page { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
        .pg-badge.slider { background:#faf5ff; color:#7e22ce; border:1px solid #e9d5ff; }
        .pg-badge.single { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
        .pg-key { font-size:12px; color:#64748b; }
        .pg-key code { background:#f1f5f9; padding:1px 6px; border-radius:4px; color:#0f172a; }
        .pg-row { display:grid; grid-template-columns:70px 190px 110px 1fr auto; gap:16px; align-items:center; padding:12px 20px; border-bottom:1px solid #f1f5f9; }
        .pg-row:last-child { border-bottom:none; }
        .pg-thumb { width:190px; height:62px; border-radius:6px; background:#f1f5f9; object-fit:cover; border:1px solid #e2e8f0; }
        .pg-thumb.m { width:56px; height:56px; }
        .pg-title { font-weight:600; color:#0f172a; font-size:14px; }
        .pg-url { font-size:12px; color:#64748b; word-break:break-all; }
        .pg-actions { display:flex; gap:6px; align-items:center; justify-content:flex-end; }
        .pg-empty { padding:26px 20px; text-align:center; color:#94a3b8; font-size:13.5px; }
        @media (max-width:991.98px) { .pg-row { grid-template-columns:1fr 1fr; } .pg-thumb { width:100%; } .pg-actions { grid-column:1 / -1; justify-content:flex-start; } }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-3 align-items-center">
                    <div class="col-md-7">
                        <h3 class="mb-0 fw-bold text-dark"><i class="fas fa-images text-primary me-2"></i>Page Banners &amp; Carousels</h3>
                        <p class="text-muted small mb-0">Banners and carousels for every page except Home, grouped by page and section.</p>
                    </div>
                    <div class="col-md-5 text-md-end mt-2 mt-md-0">
                        <a href="add-page-banner.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Page Banner / Carousel</a>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="pg-pills">
                    <a href="page-banners.php" class="pg-pill <?php echo $pageFilter === '' && !$sectionFilter ? 'active' : ''; ?>">All Pages<span class="c"><?php echo array_sum($pageCounts); ?></span></a>
                    <?php foreach ($pageCounts as $pg => $cnt): ?>
                        <a href="page-banners.php?page=<?php echo urlencode($pg); ?>" class="pg-pill <?php echo $pageFilter === $pg ? 'active' : ''; ?>"><?php echo htmlspecialchars(pb_label($pg)); ?><span class="c"><?php echo $cnt; ?></span></a>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($grouped)): ?>
                    <div class="pg-section"><div class="pg-empty">No sections found. <a href="add-page-banner.php">Add your first page banner</a>.</div></div>
                <?php endif; ?>

                <?php foreach ($grouped as $g): $sec = $g['section']; $list = $g['banners']; ?>
                    <div class="pg-section">
                        <div class="pg-section-head">
                            <span class="pg-badge page"><?php echo htmlspecialchars(pb_label($sec['page'])); ?></span>
                            <h5><?php echo htmlspecialchars($sec['label']); ?></h5>
                            <span class="pg-badge <?php echo $sec['is_slider'] ? 'slider' : 'single'; ?>"><?php echo $sec['is_slider'] ? 'Carousel' : 'Banner'; ?></span>
                            <span class="pg-key ms-auto">Key: <code><?php echo htmlspecialchars($sec['section_key']); ?></code></span>
                            <a href="add-page-banner.php?page=<?php echo urlencode($sec['page']); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i>Add</a>
                        </div>
                        <?php if (empty($list)): ?>
                            <div class="pg-empty">Nothing here yet &mdash; this section is empty.</div>
                        <?php endif; ?>
                        <?php foreach ($list as $b): $active = $b['status'] === 'active'; ?>
                            <div class="pg-row">
                                <div>
                                    <span class="badge bg-light text-dark border">#<?php echo (int)$b['display_order'] + 1; ?></span>
                                </div>
                                <div>
                                    <?php if ($b['image_url_desktop']): ?>
                                        <img class="pg-thumb" src="<?php echo pb_img($b['image_url_desktop']); ?>" alt="Desktop">
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($b['image_url_mobile']): ?>
                                        <img class="pg-thumb m" src="<?php echo pb_img($b['image_url_mobile']); ?>" alt="Mobile">
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="pg-title"><?php echo htmlspecialchars($b['title']); ?></div>
                                    <div class="pg-url"><?php echo $b['target_url'] ? htmlspecialchars($b['target_url']) : '<span class="text-muted">No link</span>'; ?></div>
                                </div>
                                <div class="pg-actions">
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $active ? 'inactive' : 'active'; ?>">
                                        <input type="hidden" name="return" value="<?php echo htmlspecialchars($returnQs); ?>">
                                        <button class="btn btn-sm <?php echo $active ? 'btn-success' : 'btn-outline-secondary'; ?>" title="Click to <?php echo $active ? 'hide' : 'show'; ?>">
                                            <i class="fas fa-<?php echo $active ? 'eye' : 'eye-slash'; ?> me-1"></i><?php echo $active ? 'Active' : 'Hidden'; ?>
                                        </button>
                                    </form>
                                    <a href="edit-banner.php?id=<?php echo (int)$b['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pen"></i></a>
                                    <form method="POST" class="m-0" onsubmit="return confirm('Delete this banner permanently?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                        <input type="hidden" name="return" value="<?php echo htmlspecialchars($returnQs); ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>
<script src="js/app.js"></script>
</body>
</html>
