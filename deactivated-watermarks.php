<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'models/Watermark.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$watermarkModel = new Watermark($db);

// Handle Reactivate action
if (isset($_GET['action']) && $_GET['action'] === 'activate' && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($watermarkModel->setActive($id)) {
        $_SESSION['success_message'] = "Watermark #{$id} reactivated as primary storewide watermark!";
    } else {
        $_SESSION['error_message'] = "Failed to activate watermark.";
    }
    header("Location: deactivated-watermarks.php");
    exit;
}

// Handle Delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($watermarkModel->delete($id)) {
        $_SESSION['success_message'] = "Inactive watermark preset #{$id} deleted permanently.";
    } else {
        $_SESSION['error_message'] = "Failed to delete watermark.";
    }
    header("Location: deactivated-watermarks.php");
    exit;
}

$all = $watermarkModel->getAll();
$deactivated = array_filter($all, fn($w) => $w['status'] === 'inactive');

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Deactivated Watermarks | Printmont Admin</title>

    <!-- Rule 1: Dynamic Brand Favicon -->
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --pm-primary: #0B53A1;
            --pm-primary-dark: #083c75;
            --pm-primary-light: #e8f0fe;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
        }

        .custom-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .custom-card-header {
            padding: 18px 24px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }

        .custom-card-body {
            padding: 24px;
        }

        .type-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .type-text { background-color: #e0f2fe; color: #0369a1; }
        .type-image { background-color: #ede9fe; color: #6d28d9; }

        .table > :not(caption) > * > * {
            padding: 14px 18px;
            border-bottom-color: #f1f5f9;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">

                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge" style="background-color: #f1f5f9; color: #475569; font-size: 12px; font-weight: 700;">
                                    ARCHIVED / DRAFT PRESETS
                                </span>
                                <span class="text-muted fs-sm">• Watermark Management</span>
                            </div>
                            <h2 class="h3 font-weight-bold text-dark mt-1 mb-0">Deactivated Watermarks</h2>
                            <p class="text-muted mb-0">Inactive watermark templates that can be restored with a single click.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="watermarks.php" class="btn btn-primary" style="background-color: var(--pm-primary); border-color: var(--pm-primary);">
                                <i class="fa-solid fa-arrow-left me-1"></i> Active Watermark Studio
                            </a>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center border-0 shadow-sm mb-4" role="alert" style="background-color: #ecfdf5; color: #065f46;">
                            <i class="fa-solid fa-circle-check fs-5 me-3 text-success"></i>
                            <div class="flex-grow-1"><?php echo $success_message; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center border-0 shadow-sm mb-4" role="alert" style="background-color: #fef2f2; color: #991b1b;">
                            <i class="fa-solid fa-circle-exclamation fs-5 me-3 text-danger"></i>
                            <div class="flex-grow-1"><?php echo $error_message; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="custom-card">
                        <div class="custom-card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold">Archived Presets List</h5>
                            <span class="badge bg-secondary font-monospace"><?php echo count($deactivated); ?> Inactive</span>
                        </div>

                        <div class="custom-card-body p-0">
                            <?php if (empty($deactivated)): ?>
                                <div class="text-center py-5">
                                    <div class="stat-icon mx-auto mb-3" style="background: #f1f5f9; color: #94a3b8; width: 64px; height: 64px; font-size: 28px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark">No Deactivated Watermarks</h5>
                                    <p class="text-muted mb-3" style="max-width: 450px; margin: 0 auto;">
                                        All your watermark presets are currently configured or active in the Watermark Studio.
                                    </p>
                                    <a href="watermarks.php" class="btn btn-outline-primary btn-sm">
                                        Go to Watermark Studio
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4">Preset Name</th>
                                                <th>Type</th>
                                                <th>Watermark Text / Logo</th>
                                                <th>Position</th>
                                                <th>Opacity</th>
                                                <th class="text-end pe-4">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($deactivated as $wm): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($wm['title']); ?></div>
                                                    <div class="text-muted fs-xs font-monospace">ID: #<?php echo $wm['id']; ?></div>
                                                </td>

                                                <td>
                                                    <span class="type-badge type-<?php echo $wm['type']; ?>">
                                                        <?php echo ucfirst($wm['type']); ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <?php if ($wm['type'] === 'image' && !empty($wm['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($wm['image']); ?>" alt="Logo" style="max-height: 28px; max-width: 100px; background: #0f172a; padding: 2px 6px; border-radius: 4px;">
                                                    <?php else: ?>
                                                        <span class="font-monospace text-muted fs-sm"><?php echo htmlspecialchars($wm['watermark_text']); ?></span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <span class="font-monospace fs-sm text-dark"><?php echo ucwords(str_replace('_', ' ', $wm['position'])); ?></span>
                                                </td>

                                                <td>
                                                    <span class="badge bg-light text-dark border"><?php echo $wm['opacity']; ?>%</span>
                                                </td>

                                                <td class="text-end pe-4">
                                                    <div class="d-inline-flex gap-2">
                                                        <a href="deactivated-watermarks.php?action=activate&id=<?php echo $wm['id']; ?>" 
                                                           class="btn btn-sm btn-success" 
                                                           onclick="return confirm('Activate watermark \'<?php echo htmlspecialchars($wm['title']); ?>\' as primary?');">
                                                            <i class="fa-solid fa-power-off me-1"></i> Reactivate
                                                        </a>

                                                        <a href="deactivated-watermarks.php?action=delete&id=<?php echo $wm['id']; ?>" 
                                                           class="btn btn-sm btn-outline-danger" 
                                                           onclick="return confirm('Permanently delete this watermark?');">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </a>
                                                    </div>
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
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
