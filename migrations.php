<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/services/MigrationRunner.php');

$isAdmin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
if (!$isAdmin) {
    header("Location: index.php");
    exit();
}

$runner = new MigrationRunner();
$adminName = $_SESSION['username'] ?? $_SESSION['email'] ?? 'admin';

// POST/redirect/GET so a refresh cannot re-run migrations.
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
                $_SESSION['success_message'] = htmlspecialchars($name) . ' applied successfully.';
            } else {
                $_SESSION['error_message'] = 'Migration failed. ' . $result['error'];
            }
        }
    } catch (Exception $e) {
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
// Nothing recorded yet but tables already exist -> this database predates the runner.
$needsBaseline = $appliedCount === 0 && count($pending) > 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Database Migrations | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-applied { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-baselined { background-color: #e2e3e5; color: #41464b; }
        .status-warn { background-color: #f8d7da; color: #721c24; }
        .migration-name { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; }
        .log-box { background:#1e2229; color:#e6e6e6; border-radius:6px; padding:14px 16px;
                   font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size:12.5px;
                   white-space:pre-wrap; word-break:break-word; max-height:320px; overflow:auto; }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>

            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Database</strong> Migrations</h3>
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

                    <?php if ($needsBaseline): ?>
                        <div class="alert alert-warning" role="alert">
                            <div class="alert-message">
                                <h4 class="alert-heading">This database has no migration history yet</h4>
                                <p class="mb-2">
                                    No migrations are recorded, but the tables they create may already exist because
                                    they were applied by hand. Some of these files contain destructive steps
                                    (<span class="migration-name">DROP TABLE</span>), so running them now could
                                    delete data.
                                </p>
                                <p class="mb-0">
                                    If this schema is already up to date, use
                                    <strong>Mark all as already applied</strong> first. From then on, only genuinely
                                    new migrations will run.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div>
                                            <h5 class="card-title mb-1">Schema migrations</h5>
                                            <h6 class="card-subtitle text-muted mb-0">
                                                <?php echo $appliedCount; ?> applied &middot;
                                                <?php echo count($pending); ?> pending &middot;
                                                from <span class="migration-name">database/migrations/</span>
                                            </h6>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <?php if ($needsBaseline): ?>
                                                <form method="post" class="d-inline"
                                                      onsubmit="return confirm('Mark all <?php echo count($pending); ?> migration(s) as applied WITHOUT running them?\n\nUse this only if the schema is already up to date.');">
                                                    <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                                                    <input type="hidden" name="action" value="baseline_all">
                                                    <button type="submit" class="btn btn-outline-secondary">
                                                        <i class="align-middle" data-feather="check-square"></i>
                                                        Mark all as already applied
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="post" class="d-inline"
                                                  onsubmit="return confirm('Run <?php echo count($pending); ?> pending migration(s) against the live database?\n\nMake sure you have a backup. This cannot be undone.');">
                                                <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                                                <input type="hidden" name="action" value="run_pending">
                                                <button type="submit" class="btn btn-primary"
                                                        <?php echo empty($pending) ? 'disabled' : ''; ?>>
                                                    <i class="align-middle" data-feather="database"></i>
                                                    <?php echo empty($pending)
                                                        ? 'Database up to date'
                                                        : 'Run ' . count($pending) . ' pending migration' . (count($pending) === 1 ? '' : 's'); ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <?php if (empty($status)): ?>
                                        <div class="text-center py-4">
                                            <i data-feather="database" style="width:48px;height:48px;" class="text-muted mb-3"></i>
                                            <h5>No migration files found</h5>
                                            <p class="text-muted mb-0">
                                                Add <span class="migration-name">.sql</span> files to
                                                <span class="migration-name">database/migrations/</span> — they run in
                                                filename order, so keep the <span class="migration-name">NNN_</span> prefix.
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Migration</th>
                                                        <th>Status</th>
                                                        <th class="text-end">Statements</th>
                                                        <th>Applied at</th>
                                                        <th>By</th>
                                                        <th class="text-end">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($status as $row): ?>
                                                    <tr>
                                                        <td class="migration-name"><?php echo htmlspecialchars($row['name']); ?></td>
                                                        <td>
                                                            <?php if (!empty($row['missing_file'])): ?>
                                                                <span class="status-badge status-warn">File missing</span>
                                                            <?php elseif (!$row['applied']): ?>
                                                                <span class="status-badge status-pending">Pending</span>
                                                            <?php elseif ($row['baselined']): ?>
                                                                <span class="status-badge status-baselined">Baselined</span>
                                                            <?php else: ?>
                                                                <span class="status-badge status-applied">Applied</span>
                                                            <?php endif; ?>

                                                            <?php if ($row['checksum_changed']): ?>
                                                                <span class="status-badge status-warn ms-1"
                                                                      title="The .sql file changed after it was applied. Add a new migration instead of editing an applied one.">
                                                                    File changed
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end"><?php echo (int) $row['statements']; ?></td>
                                                        <td><?php echo $row['applied_at'] ? htmlspecialchars($row['applied_at']) : '<span class="text-muted">&mdash;</span>'; ?></td>
                                                        <td><?php echo $row['applied_by'] ? htmlspecialchars($row['applied_by']) : '<span class="text-muted">&mdash;</span>'; ?></td>
                                                        <td class="text-end">
                                                            <?php if (!$row['applied'] && empty($row['missing_file'])): ?>
                                                                <form method="post" class="d-inline"
                                                                      onsubmit="return confirm('Run <?php echo htmlspecialchars($row['name']); ?> now?');">
                                                                    <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                                                                    <input type="hidden" name="action" value="run_one">
                                                                    <input type="hidden" name="migration" value="<?php echo htmlspecialchars($row['name']); ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Run</button>
                                                                </form>
                                                                <form method="post" class="d-inline"
                                                                      onsubmit="return confirm('Mark <?php echo htmlspecialchars($row['name']); ?> as applied WITHOUT running it?');">
                                                                    <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                                                                    <input type="hidden" name="action" value="baseline_one">
                                                                    <input type="hidden" name="migration" value="<?php echo htmlspecialchars($row['name']); ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Mark applied</button>
                                                                </form>
                                                            <?php else: ?>
                                                                <span class="text-muted">&mdash;</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($migrationLog)): ?>
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Last run</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="log-box"><?php
                                            foreach ($migrationLog as $entry) {
                                                echo htmlspecialchars(sprintf(
                                                    "%s  %s  (%d statement(s), %dms)\n",
                                                    $entry['ok'] ? '[  OK  ]' : '[FAILED]',
                                                    $entry['migration'],
                                                    $entry['statements'],
                                                    $entry['ms']
                                                ));
                                                if (!$entry['ok']) {
                                                    echo htmlspecialchars("          " . $entry['error'] . "\n");
                                                }
                                            }
                                        ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">How this works</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li>Drop a new <span class="migration-name">.sql</span> file into
                                            <span class="migration-name">database/migrations/</span> and deploy it. It shows up here as
                                            <em>Pending</em>.</li>
                                        <li>Files run in filename order — keep the
                                            <span class="migration-name">004_</span>, <span class="migration-name">005_</span> prefix.</li>
                                        <li>Each applied file is recorded in <span class="migration-name">schema_migrations</span>, so
                                            clicking the button again is safe: nothing re-runs.</li>
                                        <li>Write migrations defensively —
                                            <span class="migration-name">CREATE TABLE IF NOT EXISTS</span>,
                                            <span class="migration-name">ADD COLUMN IF NOT EXISTS</span>,
                                            <span class="migration-name">INSERT IGNORE</span>.</li>
                                        <li>Never edit a migration that already ran; add a new one. Edited files are
                                            flagged <em>File changed</em> here.</li>
                                        <li><strong>Back up first.</strong> MySQL commits DDL immediately, so a migration
                                            that fails halfway cannot be rolled back. The runner stops at the first error and
                                            tells you which statement failed.</li>
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

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>

</html>
