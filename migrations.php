<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/constants.php');
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/AuthController.php');
require_once(__DIR__ . '/services/MigrationRunner.php');

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$runner = new MigrationRunner($db);
$currentUser = $auth->getCurrentUser();
$adminName = $currentUser['username'] ?? ($currentUser['email'] ?? 'admin');

// AJAX endpoint to retrieve file content for modal preview
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_content') {
    header('Content-Type: application/json; charset=utf-8');
    $name = trim($_GET['file'] ?? '');
    if (empty($name)) {
        echo json_encode(['ok' => false, 'error' => 'File name missing']);
        exit;
    }
    $content = $runner->getFileContent($name);
    if ($content === null) {
        echo json_encode(['ok' => false, 'error' => 'Migration file not found or unreadable.']);
    } else {
        echo json_encode(['ok' => true, 'filename' => htmlspecialchars($name), 'content' => $content]);
    }
    exit;
}

// POST/redirect/GET handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['migrations_csrf'] ?? '', $_POST['csrf'])) {
        $_SESSION['error_message'] = 'Security token expired. Please try again.';
        header('Location: migrations.php');
        exit();
    }

    try {
        if ($action === 'run_pending') {
            $pending = $runner->getPending();
            if (empty($pending)) {
                $_SESSION['error_message'] = 'Nothing to run — the database is already up to date.';
            } else {
                $result = $runner->runPending($adminName);
                $_SESSION['migration_log'] = $result['log'];

                if ($result['ok']) {
                    $_SESSION['success_message'] = sprintf(
                        'Applied %d migration(s) successfully.',
                        count($result['log'])
                    );
                } else {
                    $done = count($result['log']) - 1;
                    $_SESSION['error_message'] = sprintf(
                        'Migration failed after %d successful migration(s). %s',
                        max(0, $done),
                        $result['error']
                    );
                }
            }
        } elseif ($action === 'baseline_all') {
            $count = $runner->baseline($runner->getPending(), $adminName);
            $_SESSION['success_message'] = sprintf(
                'Marked %d migration(s) as already applied. Nothing was run against the database.',
                $count
            );
        } elseif ($action === 'baseline_one') {
            $name = $_POST['migration'] ?? '';
            $count = $runner->baseline([$name], $adminName);
            $_SESSION['success_message'] = $count
                ? htmlspecialchars($name) . ' marked as already applied.'
                : 'Could not mark that migration.';
        } elseif ($action === 'run_one') {
            $name = $_POST['migration'] ?? '';
            $result = $runner->runOne($name, $adminName);
            $_SESSION['migration_log'] = [$result];
            if ($result['ok']) {
                $_SESSION['success_message'] = htmlspecialchars($name) . ' applied successfully in ' . $result['ms'] . 'ms.';
            } else {
                $_SESSION['error_message'] = 'Migration failed. ' . $result['error'];
            }
        } elseif ($action === 'create_migration') {
            $title = trim($_POST['migration_title'] ?? '');
            $sqlContent = trim($_POST['migration_sql'] ?? '');
            if (empty($title)) {
                $_SESSION['error_message'] = 'Please enter a migration title or description.';
            } elseif (empty($sqlContent)) {
                $_SESSION['error_message'] = 'Migration SQL content cannot be empty.';
            } else {
                $createRes = $runner->createMigrationFile($title, $sqlContent);
                if ($createRes['ok']) {
                    $_SESSION['success_message'] = "Created migration file: <strong>{$createRes['filename']}</strong> successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to create migration file: " . $createRes['error'];
                }
            }
        }
    } catch (Throwable $e) {
        $_SESSION['error_message'] = 'Migration error: ' . $e->getMessage();
    }

    header('Location: migrations.php');
    exit();
}

$csrf = bin2hex(random_bytes(16));
$_SESSION['migrations_csrf'] = $csrf;

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message'] ?? '';
$migrationLog    = $_SESSION['migration_log'] ?? [];
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['migration_log']);

$status  = $runner->getStatus();
$pending = $runner->getPending();
$appliedCount = count(array_filter($status, fn($r) => $r['applied']));
$totalCount = count($status);
$nextPrefix = $runner->getNextPrefix();
$dbInfo = $runner->getDatabaseInfo();

// Nothing recorded yet but tables already exist -> this database predates the runner.
$needsBaseline = $appliedCount === 0 && count($pending) > 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Database Migrations | Printmont Admin</title>

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
            --pm-accent: #f8991d;
            --pm-success: #10b981;
            --pm-danger: #ef4444;
            --pm-warning: #f59e0b;
            --pm-dark: #0f172a;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
        }

        .stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
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

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-applied {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-pending {
            background-color: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .status-baselined {
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .status-warn {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .migration-name {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
        }

        .log-box {
            background: #0f172a;
            color: #38bdf8;
            border-radius: 10px;
            padding: 16px 20px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 350px;
            overflow-y: auto;
            border: 1px solid #1e293b;
            line-height: 1.6;
        }

        .code-modal-pre {
            background: #090d16;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Fira Code', ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 13px;
            line-height: 1.5;
            max-height: 520px;
            overflow: auto;
            border: 1px solid #1e293b;
        }

        .filter-btn.active {
            background-color: #0B53A1 !important;
            color: #ffffff !important;
            border-color: #0B53A1 !important;
        }

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
                                <span class="badge" style="background-color: var(--pm-primary-light); color: var(--pm-primary); font-size: 12px; font-weight: 700;">
                                    DATABASE ENGINE
                                </span>
                                <span class="text-muted fs-sm">• Schema Management</span>
                            </div>
                            <h2 class="h3 font-weight-bold text-dark mt-1 mb-0">Database Migrations</h2>
                            <p class="text-muted mb-0">Track, inspect, and apply version-controlled schema definitions reliably.</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createMigrationModal">
                                <i class="fa-solid fa-plus me-1"></i> New Migration
                            </button>
                            <?php if ($needsBaseline): ?>
                                <form method="post" class="d-inline"
                                      onsubmit="return confirm('Mark all <?php echo count($pending); ?> migration(s) as applied WITHOUT executing SQL?\n\nUse this only if your database tables are already up to date.');">
                                    <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="action" value="baseline_all">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="fa-solid fa-check-double me-1"></i> Baseline All
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="post" class="d-inline"
                                  onsubmit="return confirm('Run <?php echo count($pending); ?> pending migration(s) against live database (<?php echo htmlspecialchars($dbInfo['db_name']); ?>)?\n\nPlease ensure you have a recent backup.');">
                                <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                                <input type="hidden" name="action" value="run_pending">
                                <button type="submit" class="btn btn-primary" <?php echo empty($pending) ? 'disabled' : ''; ?> style="background: var(--pm-primary); border-color: var(--pm-primary);">
                                    <i class="fa-solid fa-bolt me-1"></i>
                                    <?php echo empty($pending) ? 'Database Up To Date' : 'Run ' . count($pending) . ' Pending Migration' . (count($pending) === 1 ? '' : 's'); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Flash Alerts -->
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

                    <?php if ($needsBaseline): ?>
                        <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert" style="background-color: #fffbeb; color: #92400e;">
                            <div class="d-flex align-items-start">
                                <i class="fa-solid fa-triangle-exclamation fs-4 me-3 mt-1 text-warning"></i>
                                <div>
                                    <h5 class="alert-heading fw-bold mb-1">Unrecorded Schema History Detected</h5>
                                    <p class="mb-2 fs-sm">
                                        No migration history was recorded in <span class="badge bg-light text-dark font-monospace">schema_migrations</span>, but database tables exist.
                                        If these migrations were already applied manually, click <strong>"Baseline All"</strong> to mark them as applied without running potentially destructive duplicate queries.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- KPI Statistics Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 p-xl-4 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fw-semibold fs-sm">Total Migrations</span>
                                        <h3 class="fw-bold mb-0 mt-1 text-dark"><?php echo $totalCount; ?></h3>
                                        <small class="text-muted">In <span class="font-monospace">database/migrations</span></small>
                                    </div>
                                    <div class="stat-icon" style="background-color: #e0f2fe; color: #0284c7;">
                                        <i class="fa-solid fa-folder-tree"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 p-xl-4 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fw-semibold fs-sm">Applied Successfully</span>
                                        <h3 class="fw-bold mb-0 mt-1 text-success"><?php echo $appliedCount; ?></h3>
                                        <small class="text-muted"><?php echo $totalCount > 0 ? round(($appliedCount / $totalCount) * 100) : 100; ?>% schema sync</small>
                                    </div>
                                    <div class="stat-icon" style="background-color: #ecfdf5; color: #10b981;">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 p-xl-4 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fw-semibold fs-sm">Pending Execution</span>
                                        <h3 class="fw-bold mb-0 mt-1 <?php echo count($pending) > 0 ? 'text-warning' : 'text-muted'; ?>">
                                            <?php echo count($pending); ?>
                                        </h3>
                                        <small class="text-muted"><?php echo count($pending) > 0 ? 'Action required' : 'Fully synced'; ?></small>
                                    </div>
                                    <div class="stat-icon" style="background-color: #fef3c7; color: #d97706;">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 p-xl-4 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fw-semibold fs-sm">Database Engine</span>
                                        <h4 class="fw-bold mb-0 mt-1 text-dark font-monospace fs-6 text-truncate" style="max-width: 130px;">
                                            <?php echo htmlspecialchars($dbInfo['db_name'] ?: 'print_prod'); ?>
                                        </h4>
                                        <small class="text-muted"><?php echo $dbInfo['tables_count']; ?> tables &bull; <?php echo $dbInfo['size_mb']; ?> MB</small>
                                    </div>
                                    <div class="stat-icon" style="background-color: #f1f5f9; color: #0B53A1;">
                                        <i class="fa-solid fa-server"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Migration Log Box (if available from last execution) -->
                    <?php if (!empty($migrationLog)): ?>
                        <div class="custom-card border-primary">
                            <div class="custom-card-header d-flex justify-content-between align-items-center bg-dark text-white py-2 px-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-terminal text-info"></i>
                                    <span class="fw-bold font-monospace fs-sm">Execution Console Output</span>
                                </div>
                                <span class="badge bg-secondary font-monospace"><?php echo count($migrationLog); ?> entries</span>
                            </div>
                            <div class="p-0">
                                <div class="log-box m-0 rounded-0"><?php
                                    foreach ($migrationLog as $entry) {
                                        $skipped = (int) ($entry['skipped'] ?? 0);
                                        $statusTag = $entry['ok'] ? '[  SUCCESS  ]' : '[  FAILED   ]';
                                        $statusColor = $entry['ok'] ? 'text-success' : 'text-danger';
                                        
                                        echo "<span class='{$statusColor} fw-bold'>" . htmlspecialchars($statusTag) . "</span> "
                                            . htmlspecialchars($entry['migration'])
                                            . " (" . (int)$entry['statements'] . " statement(s)"
                                            . ($skipped > 0 ? ", {$skipped} skipped" : "")
                                            . ", " . (int)$entry['ms'] . "ms)\n";
                                        
                                        if (!$entry['ok']) {
                                            echo "<span class='text-danger'>               Error: " . htmlspecialchars($entry['error']) . "</span>\n";
                                        }
                                    }
                                ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Migrations Table & Management Card -->
                    <div class="custom-card">
                        <div class="custom-card-header">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-12 col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>
                                        <input type="text" id="migrationSearch" class="form-control border-start-0 ps-0" placeholder="Search migration files...">
                                    </div>
                                </div>

                                <div class="col-12 col-md-8 d-flex justify-content-md-end gap-2 flex-wrap">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all">All (<?php echo $totalCount; ?>)</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="pending">Pending (<?php echo count($pending); ?>)</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="applied">Applied (<?php echo $appliedCount; ?>)</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="custom-card-body p-0">
                            <?php if (empty($status)): ?>
                                <div class="text-center py-5">
                                    <div class="stat-icon mx-auto mb-3" style="background: #f1f5f9; color: #94a3b8; width: 64px; height: 64px; font-size: 28px;">
                                        <i class="fa-solid fa-database"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark">No Migration Files Found</h5>
                                    <p class="text-muted mb-3" style="max-width: 480px; margin: 0 auto;">
                                        Add <span class="font-monospace">.sql</span> files inside <span class="font-monospace">database/migrations/</span> with sequential numbers like <span class="font-monospace">001_title.sql</span>.
                                    </p>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createMigrationModal">
                                        <i class="fa-solid fa-plus me-1"></i> Create First Migration
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="migrationsTable">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4" style="width: 35%;">Migration Name</th>
                                                <th>Status</th>
                                                <th class="text-center">Statements</th>
                                                <th>Applied At</th>
                                                <th>Applied By</th>
                                                <th class="text-end pe-4">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($status as $row): 
                                            $isPending = !$row['applied'];
                                            $filterCategory = $isPending ? 'pending' : 'applied';
                                        ?>
                                            <tr class="migration-row" data-category="<?php echo $filterCategory; ?>" data-name="<?php echo strtolower($row['name']); ?>">
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="fa-solid fa-file-code text-primary opacity-75"></i>
                                                        <span class="migration-name"><?php echo htmlspecialchars($row['name']); ?></span>
                                                        <?php if (empty($row['missing_file'])): ?>
                                                            <button type="button" class="btn btn-link btn-sm p-0 text-muted view-sql-btn ms-1" 
                                                                    data-file="<?php echo htmlspecialchars($row['name']); ?>" 
                                                                    title="View SQL Script">
                                                                <i class="fa-regular fa-eye"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($row['missing_file'])): ?>
                                                        <span class="status-badge status-warn" title="Migration is recorded in DB table, but .sql file is missing on disk.">
                                                            <i class="fa-solid fa-file-circle-xmark"></i> File Missing
                                                        </span>
                                                    <?php elseif ($isPending): ?>
                                                        <span class="status-badge status-pending">
                                                            <i class="fa-solid fa-hourglass-half"></i> Pending
                                                        </span>
                                                    <?php elseif ($row['baselined']): ?>
                                                        <span class="status-badge status-baselined" title="Schema already matched this file when runner was configured.">
                                                            <i class="fa-solid fa-check-double"></i> Baselined
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-applied">
                                                            <i class="fa-solid fa-circle-check"></i> Applied
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if ($row['checksum_changed']): ?>
                                                        <span class="status-badge status-warn ms-1" title="The .sql file was modified after it was applied. Always create a new migration instead of modifying applied ones.">
                                                            <i class="fa-solid fa-triangle-exclamation"></i> File Changed
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center font-monospace fs-sm text-muted">
                                                    <?php echo (int) $row['statements']; ?>
                                                </td>
                                                <td class="fs-sm">
                                                    <?php echo $row['applied_at'] ? htmlspecialchars(date('M d, Y H:i', strtotime($row['applied_at']))) : '<span class="text-muted">&mdash;</span>'; ?>
                                                </td>
                                                <td class="fs-sm">
                                                    <?php echo $row['applied_by'] ? '<span class="badge bg-light text-dark border">' . htmlspecialchars($row['applied_by']) . '</span>' : '<span class="text-muted">&mdash;</span>'; ?>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="d-inline-flex gap-1">
                                                        <?php if (empty($row['missing_file'])): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary view-sql-btn" 
                                                                    data-file="<?php echo htmlspecialchars($row['name']); ?>" 
                                                                    title="View Source">
                                                                <i class="fa-solid fa-code"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($isPending && empty($row['missing_file'])): ?>
                                                            <form method="post" class="d-inline"
                                                                  onsubmit="return confirm('Run <?php echo htmlspecialchars($row['name']); ?> now?');">
                                                                <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                                                                <input type="hidden" name="action" value="run_one">
                                                                <input type="hidden" name="migration" value="<?php echo htmlspecialchars($row['name']); ?>">
                                                                <button type="submit" class="btn btn-sm btn-primary" title="Execute Migration">
                                                                    <i class="fa-solid fa-play"></i> Run
                                                                </button>
                                                            </form>
                                                            <form method="post" class="d-inline"
                                                                  onsubmit="return confirm('Mark <?php echo htmlspecialchars($row['name']); ?> as applied without running SQL queries?');">
                                                                <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                                                                <input type="hidden" name="action" value="baseline_one">
                                                                <input type="hidden" name="migration" value="<?php echo htmlspecialchars($row['name']); ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Mark as applied">
                                                                    <i class="fa-solid fa-check"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
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

                    <!-- Developer Guidelines Card -->
                    <div class="custom-card">
                        <div class="custom-card-header d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-info text-primary"></i>
                            <h5 class="card-title mb-0 fw-bold">Database Migration Guidelines & Best Practices</h5>
                        </div>
                        <div class="custom-card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-dark"><i class="fa-solid fa-shield-halved text-success me-2"></i> Defensive SQL Writing</h6>
                                    <p class="text-muted fs-sm mb-3">Always write migrations idempotently so re-running or running across different environments never breaks:</p>
                                    <ul class="fs-sm text-muted mb-0">
                                        <li>Use <code class="text-dark">CREATE TABLE IF NOT EXISTS</code></li>
                                        <li>Use <code class="text-dark">ALTER TABLE ... ADD COLUMN IF NOT EXISTS</code> (or write separate safe alter statements)</li>
                                        <li>Use <code class="text-dark">INSERT IGNORE INTO</code> or <code class="text-dark">ON DUPLICATE KEY UPDATE</code> for seed data.</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-dark"><i class="fa-solid fa-code-branch text-primary me-2"></i> Sequential Versioning</h6>
                                    <p class="text-muted fs-sm mb-3">Migrations are executed alphabetically by filename order:</p>
                                    <ul class="fs-sm text-muted mb-0">
                                        <li>Next available file sequence: <span class="badge bg-primary font-monospace"><?php echo $nextPrefix; ?>your_feature_name.sql</span></li>
                                        <li><strong>Immutable History:</strong> Never edit an applied migration file. Create a new migration file to alter schema.</li>
                                        <li>Backup before running large migrations with <code class="text-dark">DROP</code> or <code class="text-dark">MODIFY</code> columns.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- SQL Viewer Modal -->
    <div class="modal fade" id="viewSqlModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-code text-info"></i>
                        <h5 class="modal-title font-monospace fs-6 mb-0 text-white" id="viewSqlModalTitle">migration.sql</h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-light" id="copySqlBtn">
                            <i class="fa-regular fa-copy me-1"></i> Copy
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body p-0 bg-dark">
                    <div id="sqlModalLoading" class="text-center py-5 text-muted">
                        <div class="spinner-border spinner-border-sm text-info mb-2" role="status"></div>
                        <div>Loading SQL script content...</div>
                    </div>
                    <pre class="code-modal-pre m-0" id="sqlModalContent" style="display: none;"></pre>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Migration Modal -->
    <div class="modal fade" id="createMigrationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <form method="post" action="migrations.php">
                    <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="action" value="create_migration">
                    
                    <div class="modal-header bg-primary text-white py-3">
                        <h5 class="modal-title text-white fw-bold mb-0">
                            <i class="fa-solid fa-plus-circle me-2"></i> Create New Migration
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Migration Title / Slug <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="migration_title" id="migration_title_input" 
                                   placeholder="e.g. add_customer_wallet_system" required>
                            <div class="form-text mt-1">
                                Will be saved as: <span class="badge bg-light text-dark font-monospace border" id="preview_filename"><?php echo $nextPrefix; ?>title.sql</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold text-dark mb-0">SQL Query / Schema Changes <span class="text-danger">*</span></label>
                                <span class="fs-xs text-muted">Supports multi-statement SQL</span>
                            </div>
                            <textarea class="form-control font-monospace" name="migration_sql" id="migration_sql_input" rows="10" 
                                      placeholder="-- Example SQL statements
CREATE TABLE IF NOT EXISTS `sample_table` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `loyalty_points` INT NOT NULL DEFAULT 0;" required></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Migration File
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Live Search & Filter
            const searchInput = document.getElementById('migrationSearch');
            const rows = document.querySelectorAll('.migration-row');
            const filterBtns = document.querySelectorAll('.filter-btn');

            let currentFilter = 'all';

            function filterRows() {
                const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
                rows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const category = row.getAttribute('data-category') || '';

                    const matchesQuery = !query || name.includes(query);
                    const matchesFilter = (currentFilter === 'all') || (category === currentFilter);

                    if (matchesQuery && matchesFilter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', filterRows);
            }

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.getAttribute('data-filter');
                    filterRows();
                });
            });

            // Dynamic Migration Filename Preview
            const titleInput = document.getElementById('migration_title_input');
            const previewFilename = document.getElementById('preview_filename');
            const nextPrefix = <?php echo json_encode($nextPrefix); ?>;

            if (titleInput && previewFilename) {
                titleInput.addEventListener('input', function() {
                    let clean = this.value.toLowerCase().trim().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '');
                    previewFilename.textContent = nextPrefix + (clean || 'title') + '.sql';
                });
            }

            // View SQL Modal AJAX
            const viewSqlModal = new bootstrap.Modal(document.getElementById('viewSqlModal'));
            const modalTitle = document.getElementById('viewSqlModalTitle');
            const modalLoading = document.getElementById('sqlModalLoading');
            const modalContent = document.getElementById('sqlModalContent');
            const copySqlBtn = document.getElementById('copySqlBtn');

            document.querySelectorAll('.view-sql-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const filename = this.getAttribute('data-file');
                    modalTitle.textContent = filename;
                    modalLoading.style.display = 'block';
                    modalContent.style.display = 'none';
                    modalContent.textContent = '';
                    viewSqlModal.show();

                    fetch('migrations.php?ajax=get_content&file=' + encodeURIComponent(filename))
                        .then(res => res.json())
                        .then(data => {
                            modalLoading.style.display = 'none';
                            if (data.ok) {
                                modalContent.textContent = data.content;
                                modalContent.style.display = 'block';
                            } else {
                                modalContent.textContent = 'Error: ' + (data.error || 'Could not load SQL file');
                                modalContent.style.display = 'block';
                            }
                        })
                        .catch(err => {
                            modalLoading.style.display = 'none';
                            modalContent.textContent = 'Network error loading file content.';
                            modalContent.style.display = 'block';
                        });
                });
            });

            // Copy SQL to clipboard
            if (copySqlBtn) {
                copySqlBtn.addEventListener('click', function() {
                    if (modalContent.textContent) {
                        navigator.clipboard.writeText(modalContent.textContent).then(() => {
                            const originalHTML = copySqlBtn.innerHTML;
                            copySqlBtn.innerHTML = '<i class="fa-solid fa-check me-1 text-success"></i> Copied!';
                            setTimeout(() => {
                                copySqlBtn.innerHTML = originalHTML;
                            }, 2000);
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>
