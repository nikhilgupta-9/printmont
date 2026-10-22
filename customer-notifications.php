<?php
session_start();
require_once(__DIR__ . '/config/database.php');

$db = (new Database())->getConnection();

$categories = [
    'offers'  => 'New sale / offer',
    'orders'  => 'Order update',
    'account' => 'Account',
    'rewards' => 'Rewards & coins',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send') {
        $category = array_key_exists($_POST['category'] ?? '', $categories) ? $_POST['category'] : 'offers';
        $title    = trim($_POST['title'] ?? '');
        $message  = trim($_POST['message'] ?? '');
        $link     = trim($_POST['link'] ?? '');
        $userId   = (int) ($_POST['user_id'] ?? 0);

        if ($title === '' || $message === '') {
            $_SESSION['error_message'] = 'Title and message are required.';
        } elseif ($userId > 0 && !$db->query("SELECT id FROM users WHERE id = {$userId}")->num_rows) {
            $_SESSION['error_message'] = "No customer found with ID {$userId}.";
        } else {
            $uid = $userId > 0 ? $userId : null;
            $stmt = $db->prepare('INSERT INTO customer_notifications (user_id, category, title, message, link) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('issss', $uid, $category, $title, $message, $link);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success_message'] = $uid ? "Notification sent to customer #{$uid}." : 'Notification sent to all customers.';
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $db->query("DELETE FROM customer_notification_reads WHERE notification_id = {$id}");
        $db->query("DELETE FROM customer_notifications WHERE id = {$id}");
        $_SESSION['success_message'] = 'Notification deleted.';
    }

    header('Location: customer-notifications.php');
    exit;
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$recent = $db->query(
    "SELECT n.*, (SELECT COUNT(*) FROM customer_notification_reads r WHERE r.notification_id = n.id) AS reads_count
     FROM customer_notifications n ORDER BY n.created_at DESC, n.id DESC LIMIT 60"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Customer Notifications | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; background-color: #f1f5f9; }
        .cn-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.04); margin-bottom:20px; overflow:hidden; }
        .cn-card-head { padding:14px 20px; background:#fafbfc; border-bottom:1px solid #eef2f6; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px; }
        .cn-card-body { padding:20px; }
        .cn-label { font-size:13px; font-weight:600; color:#334155; margin-bottom:6px; display:block; }
        .cn-row { display:grid; grid-template-columns:110px 1fr auto; gap:14px; align-items:center; padding:12px 20px; border-bottom:1px solid #f1f5f9; }
        .cn-row:last-child { border-bottom:none; }
        .cn-badge { font-size:11px; font-weight:700; padding:3px 9px; border-radius:999px; text-transform:uppercase; letter-spacing:.03em; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; text-align:center; }
        .cn-badge.orders { background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
        .cn-badge.account { background:#f1f5f9; color:#475569; border-color:#e2e8f0; }
        .cn-badge.rewards { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
        .cn-title { font-weight:600; color:#0f172a; font-size:14px; }
        .cn-msg { font-size:12.5px; color:#64748b; }
        .cn-meta { font-size:11.5px; color:#94a3b8; margin-top:2px; }
        @media (max-width:767.98px) { .cn-row { grid-template-columns:1fr; } }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <div class="mb-3">
                    <h3 class="mb-0 fw-bold text-dark"><i class="fas fa-bell text-primary me-2"></i>Customer Notifications</h3>
                    <p class="text-muted small mb-0">Send a new sale, offer or announcement to customers. Order status updates and new coupons are sent automatically.</p>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="cn-card">
                    <div class="cn-card-head"><i class="fas fa-paper-plane text-primary"></i> Send a notification</div>
                    <div class="cn-card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="send">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="cn-label">Type</label>
                                    <select name="category" class="form-select">
                                        <?php foreach ($categories as $key => $label): ?>
                                            <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="cn-label">Title</label>
                                    <input type="text" name="title" class="form-control" maxlength="120" placeholder="e.g. Diwali Mega Sale is live" required>
                                </div>
                                <div class="col-12">
                                    <label class="cn-label">Message</label>
                                    <textarea name="message" class="form-control" rows="3" placeholder="e.g. Up to 40% off on custom apparel. Use code DIWALI40 at checkout." required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="cn-label">Link when tapped <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="text" name="link" class="form-control" placeholder="/allproducts or /user/giftcard">
                                </div>
                                <div class="col-md-6">
                                    <label class="cn-label">Send to</label>
                                    <input type="number" name="user_id" class="form-control" min="0" placeholder="Leave empty to send to all customers (or enter one customer's user ID)">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-paper-plane me-1"></i> Send notification</button>
                        </form>
                    </div>
                </div>

                <div class="cn-card">
                    <div class="cn-card-head"><i class="fas fa-clock-rotate-left text-primary"></i> Recent notifications</div>
                    <?php if (!$recent): ?>
                        <div class="cn-card-body text-center text-muted">Nothing sent yet.</div>
                    <?php endif; ?>
                    <?php foreach ($recent as $n): ?>
                        <div class="cn-row">
                            <span class="cn-badge <?php echo htmlspecialchars($n['category']); ?>"><?php echo htmlspecialchars($categories[$n['category']] ?? $n['category']); ?></span>
                            <div>
                                <div class="cn-title"><?php echo htmlspecialchars($n['title']); ?></div>
                                <div class="cn-msg"><?php echo htmlspecialchars($n['message']); ?></div>
                                <div class="cn-meta">
                                    <?php echo $n['user_id'] ? 'Customer #' . (int)$n['user_id'] : 'All customers'; ?>
                                    &middot; <?php echo htmlspecialchars($n['created_at']); ?>
                                    &middot; read by <?php echo (int)$n['reads_count']; ?>
                                </div>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this notification for everyone it was sent to?');" class="m-0">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$n['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>
<script src="js/app.js"></script>
</body>
</html>
