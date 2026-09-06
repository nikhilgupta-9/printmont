<?php
session_start();
require_once 'config/database.php';
require_once 'controllers/NotificationController.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$notificationController = new NotificationController();

// Handle AJAX actions
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_read' && isset($_POST['id'])) {
        $res = $notificationController->markAsRead($_POST['id'], $user_id);
        echo json_encode(['success' => (bool)$res]);
        exit;
    }
    
    if ($action === 'mark_all_read') {
        $res = $notificationController->markAllAsRead($user_id);
        echo json_encode(['success' => (bool)$res]);
        exit;
    }

    if ($action === 'delete' && isset($_POST['id'])) {
        $res = $notificationController->deleteNotification($_POST['id']);
        echo json_encode(['success' => (bool)$res]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Handle standard form POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'mark_all_read') {
            $notificationController->markAllAsRead($user_id);
            $_SESSION['success_message'] = "All notifications marked as read.";
        } elseif ($_POST['action'] === 'mark_read' && isset($_POST['id'])) {
            $notificationController->markAsRead($_POST['id'], $user_id);
            $_SESSION['success_message'] = "Notification marked as read.";
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $notificationController->deleteNotification($_POST['id']);
            $_SESSION['success_message'] = "Notification deleted successfully.";
        }
        header("Location: notifications.php");
        exit;
    }
}

// Filter option
$filter = $_GET['filter'] ?? 'all';
$unread_only = ($filter === 'unread');

$notifications = $notificationController->getUserNotifications($user_id, 100, $unread_only);
$unread_count = $notificationController->getUnreadCount($user_id);
$total_notifications = count($notificationController->getUserNotifications($user_id, 500, false));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>All Notifications | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .notification-card {
            transition: all 0.2s ease-in-out;
            border-left: 4px solid transparent;
        }
        .notification-card.unread {
            background-color: #f0f7ff;
            border-left-color: #3b7ddd;
        }
        .notification-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-1px);
        }
        .notif-icon-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
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

                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                    <div>
                                        <h4 class="mb-1 fw-bold text-dark">
                                            <i class="fas fa-bell me-2 text-primary"></i>Notifications
                                        </h4>
                                        <p class="text-muted mb-0">
                                            Manage your system notifications, alerts, and job applications
                                        </p>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php if ($unread_count > 0): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="mark_all_read">
                                                <button type="submit" class="btn btn-outline-primary">
                                                    <i class="fas fa-check-double me-1"></i> Mark All as Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <div class="btn-group" role="group">
                                            <a href="notifications.php?filter=all" class="btn btn-<?php echo $filter === 'all' ? 'primary' : 'outline-secondary'; ?>">
                                                All (<?php echo $total_notifications; ?>)
                                            </a>
                                            <a href="notifications.php?filter=unread" class="btn btn-<?php echo $filter === 'unread' ? 'primary' : 'outline-secondary'; ?>">
                                                Unread (<?php echo $unread_count; ?>)
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flash messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $_SESSION['success_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <!-- Notifications List -->
                    <div class="row">
                        <div class="col-12">
                            <?php if (empty($notifications)): ?>
                                <div class="card border-0 shadow-sm text-center py-5">
                                    <div class="card-body">
                                        <div class="text-muted mb-3">
                                            <i class="fas fa-bell-slash fa-4x opacity-50"></i>
                                        </div>
                                        <h5 class="fw-semibold">No notifications found</h5>
                                        <p class="text-muted">
                                            <?php echo $filter === 'unread' ? 'You have no unread notifications.' : 'You are all caught up!'; ?>
                                        </p>
                                        <?php if ($filter === 'unread'): ?>
                                            <a href="notifications.php?filter=all" class="btn btn-sm btn-primary">View All Notifications</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): 
                                    $color = $notificationController->getNotificationColor($notification['type']);
                                    $icon = $notificationController->getNotificationFontAwesomeIcon($notification['type'], $notification['icon']);
                                    $is_read = (bool)$notification['is_read'];
                                ?>
                                    <div class="card notification-card mb-3 shadow-sm border-0 <?php echo $is_read ? '' : 'unread'; ?>">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="notif-icon-circle bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?> flex-shrink-0">
                                                    <i class="fas fa-<?php echo htmlspecialchars($icon); ?>"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                                        <h6 class="mb-0 fw-bold text-dark">
                                                            <?php echo htmlspecialchars($notification['title']); ?>
                                                            <?php if (!$is_read): ?>
                                                                <span class="badge bg-primary ms-2">New</span>
                                                            <?php endif; ?>
                                                        </h6>
                                                        <small class="text-muted">
                                                            <i class="far fa-clock me-1"></i>
                                                            <?php echo $notificationController->formatNotificationTime($notification['created_at']); ?>
                                                            (<?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>)
                                                        </small>
                                                    </div>
                                                    <p class="mb-2 text-secondary">
                                                        <?php echo htmlspecialchars($notification['message']); ?>
                                                    </p>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if (!empty($notification['link'])): 
                                                            $openUrl = "api/notifications/open.php?id=" . $notification['id'] . "&redirect=" . urlencode($notification['link']);
                                                        ?>
                                                            <a href="<?php echo htmlspecialchars($openUrl); ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-external-link-alt me-1"></i> View Details
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if (!$is_read): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="mark_read">
                                                                <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-link text-muted p-0 text-decoration-none">
                                                                    <i class="fas fa-check me-1"></i> Mark as Read
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <form method="POST" class="d-inline ms-auto" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0 text-decoration-none">
                                                                <i class="fas fa-trash-alt me-1"></i> Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
