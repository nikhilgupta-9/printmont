<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    if ($action === 'get_detail' && isset($_REQUEST['id'])) {
        $detail = $notificationController->getNotificationDetail($_REQUEST['id'], $user_id);
        if ($detail) {
            // Auto mark as read on detail view if not already read
            if (!$detail['is_read']) {
                $notificationController->markAsRead($_REQUEST['id'], $user_id);
                $detail['is_read'] = 1;
            }
            echo json_encode(['success' => true, 'data' => $detail]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Notification not found']);
        }
        exit;
    }

    if ($action === 'mark_read' && isset($_POST['id'])) {
        $res = $notificationController->markAsRead($_POST['id'], $user_id);
        $unread_count = $notificationController->getUnreadCount($user_id);
        echo json_encode(['success' => (bool)$res, 'unread_count' => $unread_count]);
        exit;
    }
    
    if ($action === 'mark_all_read') {
        $res = $notificationController->markAllAsRead($user_id);
        echo json_encode(['success' => (bool)$res, 'unread_count' => 0]);
        exit;
    }

    if ($action === 'delete' && isset($_POST['id'])) {
        $res = $notificationController->deleteNotification($_POST['id']);
        $unread_count = $notificationController->getUnreadCount($user_id);
        echo json_encode(['success' => (bool)$res, 'unread_count' => $unread_count]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Handle standard form POST actions fallback
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

// Filter & Pagination options
$filter = $_GET['filter'] ?? 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(5, min(100, (int)$_GET['limit'])) : 10;

// Fetch all notifications for KPI summary metrics
$all_notifications = $notificationController->getUserNotifications($user_id, 1000, false);
$unread_count = $notificationController->getUnreadCount($user_id);
$total_notifications = count($all_notifications);

// Calculate Category Counts
$order_count = 0;
$user_count = 0;
$system_count = 0;
foreach ($all_notifications as $item) {
    if (in_array($item['type'], ['order', 'success'])) {
        $order_count++;
    } elseif (in_array($item['type'], ['user', 'info'])) {
        $user_count++;
    } else {
        $system_count++;
    }
}

// Fetch Paginated Notifications for active filter & page
$paginatedResult = $notificationController->getPaginatedNotifications($user_id, $page, $limit, $filter);
$notifications = $paginatedResult['data'];
$filtered_total = $paginatedResult['total'];
$total_pages = $paginatedResult['total_pages'];
$offset = $paginatedResult['offset'];

$start_record = ($filtered_total > 0) ? ($offset + 1) : 0;
$end_record = min($offset + count($notifications), $filtered_total);

$open_id = isset($_GET['open_id']) ? (int)$_GET['open_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>All Notifications | Printmont Admin</title>
    <!-- Dynamic Brand Favicon (Rule 1) -->
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card {
            border: none;
            border-radius: 12px;
            transition: all 0.25s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        }
        .stat-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .notification-card {
            transition: all 0.2s ease-in-out;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.06);
            border-left: 5px solid #e2e8f0;
            cursor: pointer;
        }
        .notification-card.unread {
            background-color: #f6fafe;
            border-left-color: #3b7ddd;
        }
        .notification-card:hover {
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            transform: translateY(-1px);
        }
        .notif-icon-circle {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }
        .filter-btn.active {
            font-weight: 600;
        }
        .modal-notif-header {
            border-bottom: 1px solid #edf2f7;
            background: #f8fafc;
            border-top-left-radius: calc(0.5rem - 1px);
            border-top-right-radius: calc(0.5rem - 1px);
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

                    <!-- Page Title & Header Bar -->
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-7">
                            <h3 class="mb-1 fw-bold text-dark">
                                <i class="fas fa-bell me-2 text-primary"></i>Notification Center
                            </h3>
                            <p class="text-muted mb-0">
                                Real-time activity updates, order alerts, customer actions, and system logs.
                            </p>
                        </div>
                        <div class="col-md-5 text-md-end mt-3 mt-md-0">
                            <?php if ($unread_count > 0): ?>
                                <button type="button" id="btnMarkAllRead" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-check-double me-1"></i> Mark All as Read
                                </button>
                            <?php endif; ?>
                            <a href="notifications.php" class="btn btn-primary">
                                <i class="fas fa-sync-alt me-1"></i> Refresh
                            </a>
                        </div>
                    </div>

                    <!-- Flash messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $_SESSION['success_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <!-- Stats / KPI Cards -->
                    <div class="row mb-4 g-3">
                        <div class="col-sm-6 col-xl-3">
                            <div class="card stat-card h-100">
                                <div class="card-body d-flex align-items-center justify-content-between p-3">
                                    <div>
                                        <div class="text-muted small fw-semibold text-uppercase">Total Alerts</div>
                                        <h3 class="mb-0 fw-bold mt-1 text-dark" id="kpiTotal"><?php echo $total_notifications; ?></h3>
                                    </div>
                                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                                        <i class="fas fa-layer-group"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="card stat-card h-100">
                                <div class="card-body d-flex align-items-center justify-content-between p-3">
                                    <div>
                                        <div class="text-muted small fw-semibold text-uppercase">Unread Alerts</div>
                                        <h3 class="mb-0 fw-bold mt-1 text-danger" id="kpiUnread"><?php echo $unread_count; ?></h3>
                                    </div>
                                    <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="card stat-card h-100">
                                <div class="card-body d-flex align-items-center justify-content-between p-3">
                                    <div>
                                        <div class="text-muted small fw-semibold text-uppercase">Order & Sales</div>
                                        <h3 class="mb-0 fw-bold mt-1 text-info"><?php echo $order_count; ?></h3>
                                    </div>
                                    <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="card stat-card h-100">
                                <div class="card-body d-flex align-items-center justify-content-between p-3">
                                    <div>
                                        <div class="text-muted small fw-semibold text-uppercase">Users & System</div>
                                        <h3 class="mb-0 fw-bold mt-1 text-success"><?php echo $user_count + $system_count; ?></h3>
                                    </div>
                                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar: Search & Category Filters -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-md-5 col-lg-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-search text-muted"></i>
                                        </span>
                                        <input type="text" id="searchInput" class="form-control bg-light border-start-0 ps-0" placeholder="Search notifications by keywords...">
                                    </div>
                                </div>
                                <div class="col-md-7 col-lg-8">
                                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                                        <a href="notifications.php?filter=all" class="btn btn-sm filter-btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                            All <span class="badge bg-white text-dark ms-1"><?php echo $total_notifications; ?></span>
                                        </a>
                                        <a href="notifications.php?filter=unread" class="btn btn-sm filter-btn <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                            Unread <span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span>
                                        </a>
                                        <a href="notifications.php?filter=orders" class="btn btn-sm filter-btn <?php echo $filter === 'orders' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                            <i class="fas fa-shopping-cart me-1"></i> Orders <span class="badge bg-info ms-1"><?php echo $order_count; ?></span>
                                        </a>
                                        <a href="notifications.php?filter=users" class="btn btn-sm filter-btn <?php echo $filter === 'users' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                            <i class="fas fa-user me-1"></i> Users <span class="badge bg-success ms-1"><?php echo $user_count; ?></span>
                                        </a>
                                        <a href="notifications.php?filter=system" class="btn btn-sm filter-btn <?php echo $filter === 'system' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                            <i class="fas fa-sliders-h me-1"></i> System <span class="badge bg-secondary ms-1"><?php echo $system_count; ?></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Feed -->
                    <div class="row">
                        <div class="col-12" id="notificationFeedContainer">
                            <?php if (empty($notifications)): ?>
                                <div class="card border-0 shadow-sm text-center py-5">
                                    <div class="card-body">
                                        <div class="text-muted mb-3">
                                            <i class="fas fa-bell-slash fa-4x opacity-50"></i>
                                        </div>
                                        <h5 class="fw-semibold">No notifications found</h5>
                                        <p class="text-muted mb-3">
                                            <?php echo $filter === 'unread' ? 'You have no unread notifications.' : 'No notification items in this view.'; ?>
                                        </p>
                                        <?php if ($filter !== 'all'): ?>
                                            <a href="notifications.php?filter=all" class="btn btn-sm btn-primary">View All Notifications</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): 
                                    $color = $notificationController->getNotificationColor($notification['type']);
                                    $icon = $notificationController->getNotificationFontAwesomeIcon($notification['type'], $notification['icon']);
                                    $is_read = (bool)$notification['is_read'];
                                    $time_ago = $notificationController->formatNotificationTime($notification['created_at']);
                                    $exact_time = date('M j, Y g:i A', strtotime($notification['created_at']));
                                ?>
                                    <div class="card notification-card mb-3 shadow-sm <?php echo $is_read ? '' : 'unread'; ?>" 
                                         id="card-notif-<?php echo $notification['id']; ?>"
                                         data-id="<?php echo $notification['id']; ?>"
                                         data-title="<?php echo htmlspecialchars($notification['title'], ENT_QUOTES); ?>"
                                         data-message="<?php echo htmlspecialchars($notification['message'], ENT_QUOTES); ?>"
                                         data-type="<?php echo htmlspecialchars($notification['type'], ENT_QUOTES); ?>"
                                         data-color="<?php echo $color; ?>"
                                         data-icon="<?php echo $icon; ?>"
                                         data-link="<?php echo htmlspecialchars($notification['link'] ?? '', ENT_QUOTES); ?>"
                                         data-time="<?php echo $time_ago; ?>"
                                         data-date="<?php echo $exact_time; ?>"
                                         data-is-read="<?php echo $is_read ? '1' : '0'; ?>">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="notif-icon-circle bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?> flex-shrink-0">
                                                    <i class="fas fa-<?php echo htmlspecialchars($icon); ?>"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <h6 class="mb-0 fw-bold text-dark notif-title">
                                                                <?php echo htmlspecialchars($notification['title']); ?>
                                                            </h6>
                                                            <span class="badge bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?> text-uppercase small" style="font-size: 0.7rem;">
                                                                <?php echo htmlspecialchars($notification['type']); ?>
                                                            </span>
                                                            <?php if (!$is_read): ?>
                                                                <span class="badge bg-primary notif-new-badge">New</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="far fa-clock me-1"></i>
                                                            <span class="fw-medium"><?php echo $time_ago; ?></span>
                                                            <span class="d-none d-md-inline ms-1">(<?php echo $exact_time; ?>)</span>
                                                        </small>
                                                    </div>
                                                    
                                                    <p class="mb-2 text-secondary notif-msg-text">
                                                        <?php echo htmlspecialchars($notification['message']); ?>
                                                    </p>
                                                    
                                                    <div class="d-flex align-items-center gap-2 mt-2">
                                                        <!-- View Details button that triggers modal -->
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-open-detail" data-id="<?php echo $notification['id']; ?>">
                                                            <i class="fas fa-eye me-1"></i> View Details
                                                        </button>

                                                        <?php if (!empty($notification['link']) && $notification['link'] !== '#'): 
                                                            $openUrl = "api/notifications/open.php?id=" . $notification['id'] . "&redirect=" . urlencode($notification['link']);
                                                        ?>
                                                            <a href="<?php echo htmlspecialchars($openUrl); ?>" class="btn btn-sm btn-outline-secondary">
                                                                <i class="fas fa-external-link-alt me-1"></i> Action Link
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if (!$is_read): ?>
                                                            <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none btn-quick-read" data-id="<?php echo $notification['id']; ?>">
                                                                <i class="fas fa-check me-1"></i> Mark as Read
                                                            </button>
                                                        <?php endif; ?>

                                                        <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none ms-auto btn-quick-delete" data-id="<?php echo $notification['id']; ?>">
                                                            <i class="fas fa-trash-alt me-1"></i> Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Pagination Controls -->
                            <?php if ($total_pages > 1): ?>
                                <div class="card border-0 shadow-sm mt-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                                            <div class="text-muted small">
                                                Showing <span class="fw-bold text-dark"><?php echo $start_record; ?></span> to <span class="fw-bold text-dark"><?php echo $end_record; ?></span> of <span class="fw-bold text-dark"><?php echo $filtered_total; ?></span> notifications
                                            </div>
                                            <div class="d-flex align-items-center flex-wrap gap-3">
                                                <div class="d-flex align-items-center gap-1 text-muted small">
                                                    <span>Per page:</span>
                                                    <select class="form-select form-select-sm" style="width: auto;" onchange="location.href='notifications.php?filter=<?php echo urlencode($filter); ?>&page=1&limit=' + this.value;">
                                                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                                    </select>
                                                </div>
                                                <nav aria-label="Page navigation">
                                                    <ul class="pagination pagination-sm mb-0">
                                                        <!-- First Page -->
                                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                                            <a class="page-link" href="notifications.php?filter=<?php echo urlencode($filter); ?>&page=1&limit=<?php echo $limit; ?>" title="First Page">
                                                                <i class="fas fa-angle-double-left"></i>
                                                            </a>
                                                        </li>
                                                        <!-- Prev Page -->
                                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                                            <a class="page-link" href="notifications.php?filter=<?php echo urlencode($filter); ?>&page=<?php echo max(1, $page - 1); ?>&limit=<?php echo $limit; ?>" title="Previous Page">
                                                                <i class="fas fa-angle-left"></i>
                                                            </a>
                                                        </li>
                                                        
                                                        <?php
                                                        $range = 2;
                                                        $start_p = max(1, $page - $range);
                                                        $end_p = min($total_pages, $page + $range);
                                                        
                                                        if ($start_p > 1) {
                                                            echo '<li class="page-item"><a class="page-link" href="notifications.php?filter=' . urlencode($filter) . '&page=1&limit=' . $limit . '">1</a></li>';
                                                            if ($start_p > 2) {
                                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                            }
                                                        }
                                                        
                                                        for ($p = $start_p; $p <= $end_p; $p++) {
                                                            $active = ($p == $page) ? 'active' : '';
                                                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="notifications.php?filter=' . urlencode($filter) . '&page=' . $p . '&limit=' . $limit . '">' . $p . '</a></li>';
                                                        }
                                                        
                                                        if ($end_p < $total_pages) {
                                                            if ($end_p < $total_pages - 1) {
                                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                            }
                                                            echo '<li class="page-item"><a class="page-link" href="notifications.php?filter=' . urlencode($filter) . '&page=' . $total_pages . '&limit=' . $limit . '">' . $total_pages . '</a></li>';
                                                        }
                                                        ?>
                                                        
                                                        <!-- Next Page -->
                                                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                                            <a class="page-link" href="notifications.php?filter=<?php echo urlencode($filter); ?>&page=<?php echo min($total_pages, $page + 1); ?>&limit=<?php echo $limit; ?>" title="Next Page">
                                                                <i class="fas fa-angle-right"></i>
                                                            </a>
                                                        </li>
                                                        <!-- Last Page -->
                                                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                                            <a class="page-link" href="notifications.php?filter=<?php echo urlencode($filter); ?>&page=<?php echo $total_pages; ?>&limit=<?php echo $limit; ?>" title="Last Page">
                                                                <i class="fas fa-angle-double-right"></i>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </nav>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>
            
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Notification Detail View Modal -->
    <div class="modal fade" id="notificationDetailModal" tabindex="-1" aria-labelledby="notificationDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header modal-notif-header align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <div id="modalIconContainer" class="notif-icon-circle bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px; font-size: 1rem;">
                            <i id="modalIcon" class="fas fa-bell"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="notificationDetailModalLabel">Notification Details</h5>
                            <span id="modalTypeBadge" class="badge bg-primary text-uppercase" style="font-size: 0.65rem;">INFO</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <h5 class="fw-bold text-dark mb-2" id="modalTitle"></h5>
                    
                    <div class="d-flex align-items-center gap-2 text-muted small mb-3">
                        <i class="far fa-clock"></i>
                        <span id="modalTimeAgo" class="fw-semibold"></span>
                        <span>•</span>
                        <span id="modalExactDate"></span>
                    </div>
                    
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <p class="mb-0 text-dark" id="modalMessage" style="white-space: pre-wrap; line-height: 1.6; font-size: 0.95rem;"></p>
                    </div>

                    <div id="modalLinkContainer" class="d-none mb-3">
                        <a href="#" id="modalActionLink" class="btn btn-primary w-100">
                            <i class="fas fa-external-link-alt me-1"></i> Open Associated Resource
                        </a>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between">
                    <div>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="modalDeleteBtn">
                            <i class="fas fa-trash-alt me-1"></i> Delete
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const detailModalEl = document.getElementById('notificationDetailModal');
        const detailModal = new bootstrap.Modal(detailModalEl);
        
        let currentModalNotifId = null;

        function openNotificationModal(id) {
            currentModalNotifId = id;
            const card = document.getElementById('card-notif-' + id);
            
            if (card) {
                // Populate from data attributes immediately
                const title = card.getAttribute('data-title');
                const message = card.getAttribute('data-message');
                const type = card.getAttribute('data-type') || 'info';
                const color = card.getAttribute('data-color') || 'primary';
                const icon = card.getAttribute('data-icon') || 'bell';
                const link = card.getAttribute('data-link');
                const timeAgo = card.getAttribute('data-time');
                const exactDate = card.getAttribute('data-date');

                document.getElementById('modalTitle').textContent = title;
                document.getElementById('modalMessage').textContent = message;
                document.getElementById('modalTimeAgo').textContent = timeAgo;
                document.getElementById('modalExactDate').textContent = exactDate;
                
                const typeBadge = document.getElementById('modalTypeBadge');
                typeBadge.textContent = type;
                typeBadge.className = 'badge bg-' + color + ' text-uppercase';

                const iconContainer = document.getElementById('modalIconContainer');
                iconContainer.className = 'notif-icon-circle bg-' + color + ' bg-opacity-10 text-' + color;
                document.getElementById('modalIcon').className = 'fas fa-' + icon;

                const linkContainer = document.getElementById('modalLinkContainer');
                const actionLink = document.getElementById('modalActionLink');
                if (link && link !== '#' && link !== '') {
                    linkContainer.classList.remove('d-none');
                    let targetUrl = 'api/notifications/open.php?id=' + id + '&redirect=' + encodeURIComponent(link);
                    actionLink.href = targetUrl;
                } else {
                    linkContainer.classList.add('d-none');
                }

                // Mark card as read in UI
                card.classList.remove('unread');
                card.setAttribute('data-is-read', '1');
                const newBadge = card.querySelector('.notif-new-badge');
                if (newBadge) newBadge.remove();
                const quickReadBtn = card.querySelector('.btn-quick-read');
                if (quickReadBtn) quickReadBtn.remove();

                detailModal.show();
            }

            // AJAX request to mark read and get freshest detail
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=get_detail&id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    const d = data.data;
                    document.getElementById('modalTitle').textContent = d.title;
                    document.getElementById('modalMessage').textContent = d.message;
                    document.getElementById('modalTimeAgo').textContent = d.time_ago;
                    document.getElementById('modalExactDate').textContent = d.formatted_date;
                }
            })
            .catch(err => console.error('Error fetching notification detail:', err));
        }

        // Auto-open modal if open_id is present in URL
        const autoOpenId = <?php echo $open_id; ?>;
        if (autoOpenId > 0) {
            openNotificationModal(autoOpenId);
        }

        // Handle Card & Button Clicks to Open Detail
        document.querySelectorAll('.btn-open-detail').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.getAttribute('data-id');
                openNotificationModal(id);
            });
        });

        document.querySelectorAll('.notification-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicked on link or action buttons
                if (e.target.closest('a') || e.target.closest('button')) {
                    return;
                }
                const id = this.getAttribute('data-id');
                openNotificationModal(id);
            });
        });

        // Quick Mark as Read Handler
        document.querySelectorAll('.btn-quick-read').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.getAttribute('data-id');
                const card = document.getElementById('card-notif-' + id);
                
                fetch('notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=mark_read&id=' + encodeURIComponent(id)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (card) {
                            card.classList.remove('unread');
                            card.setAttribute('data-is-read', '1');
                            const newBadge = card.querySelector('.notif-new-badge');
                            if (newBadge) newBadge.remove();
                            btn.remove();
                        }
                        if (document.getElementById('kpiUnread')) {
                            document.getElementById('kpiUnread').textContent = data.unread_count;
                        }
                    }
                })
                .catch(err => console.error(err));
            });
        });

        // Quick Delete Handler
        document.querySelectorAll('.btn-quick-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!confirm('Are you sure you want to delete this notification?')) return;
                
                const id = this.getAttribute('data-id');
                const card = document.getElementById('card-notif-' + id);
                
                fetch('notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=delete&id=' + encodeURIComponent(id)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (card) {
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.95)';
                            setTimeout(() => card.remove(), 250);
                        }
                        if (document.getElementById('kpiUnread')) {
                            document.getElementById('kpiUnread').textContent = data.unread_count;
                        }
                    }
                })
                .catch(err => console.error(err));
            });
        });

        // Modal Delete Button
        document.getElementById('modalDeleteBtn').addEventListener('click', function() {
            if (!currentModalNotifId) return;
            if (!confirm('Are you sure you want to delete this notification?')) return;

            const id = currentModalNotifId;
            const card = document.getElementById('card-notif-' + id);

            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=delete&id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    detailModal.hide();
                    if (card) card.remove();
                    if (document.getElementById('kpiUnread')) {
                        document.getElementById('kpiUnread').textContent = data.unread_count;
                    }
                }
            })
            .catch(err => console.error(err));
        });

        // Mark All Read button handler
        const btnMarkAllRead = document.getElementById('btnMarkAllRead');
        if (btnMarkAllRead) {
            btnMarkAllRead.addEventListener('click', function() {
                fetch('notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=mark_all_read'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.notification-card').forEach(card => {
                            card.classList.remove('unread');
                            card.setAttribute('data-is-read', '1');
                            const newBadge = card.querySelector('.notif-new-badge');
                            if (newBadge) newBadge.remove();
                            const quickRead = card.querySelector('.btn-quick-read');
                            if (quickRead) quickRead.remove();
                        });
                        if (document.getElementById('kpiUnread')) {
                            document.getElementById('kpiUnread').textContent = '0';
                        }
                        btnMarkAllRead.remove();
                    }
                })
                .catch(err => console.error(err));
            });
        }

        // Live Search Input Filter
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const cards = document.querySelectorAll('.notification-card');
                
                cards.forEach(card => {
                    const title = (card.getAttribute('data-title') || '').toLowerCase();
                    const message = (card.getAttribute('data-message') || '').toLowerCase();
                    const type = (card.getAttribute('data-type') || '').toLowerCase();

                    if (title.includes(query) || message.includes(query) || type.includes(query)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    });
    </script>
</body>
</html>
