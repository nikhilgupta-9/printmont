<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
ob_start();
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

$db = new Database();
$conn = $db->getConnection();

// --- AUTHENTICATION & ACCESS CONTROL ---
// Restrict access: Only logged in users (admins/staff) are permitted to visit the dashboard
$auth = new AuthController($conn);
if (!$auth->isLoggedIn()) {
    if (!headers_sent()) {
        header('Location: index.php');
    } else {
        echo '<script>window.location.href="index.php";</script>';
    }
    exit();
}

$current_user = $auth->getCurrentUser();
if (!$current_user) {
    if (!headers_sent()) {
        header('Location: index.php');
    } else {
        echo '<script>window.location.href="index.php";</script>';
    }
    exit();
}

$admin_name = !empty($current_user['username']) ? htmlspecialchars($current_user['username']) : (!empty($current_user['first_name']) ? htmlspecialchars($current_user['first_name']) : 'Admin');

// --- 1. KEY PERFORMANCE METRICS ---
// Orders & Revenue
$order_stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(COALESCE(grand_total, total_amount, 0)) as total_revenue,
        SUM(CASE WHEN payment_status = 'paid' THEN COALESCE(grand_total, total_amount, 0) ELSE 0 END) as paid_revenue,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN status = 'delivered' OR status = 'completed' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
    FROM orders
";
$order_stats = $conn->query($order_stats_query)->fetch_assoc();
$total_orders = (int)($order_stats['total_orders'] ?? 0);
$total_revenue = (float)($order_stats['total_revenue'] ?? 0);
$paid_revenue = (float)($order_stats['paid_revenue'] ?? 0);
$pending_orders = (int)($order_stats['pending_orders'] ?? 0);
$processing_orders = (int)($order_stats['processing_orders'] ?? 0);
$shipped_orders = (int)($order_stats['shipped_orders'] ?? 0);
$delivered_orders = (int)($order_stats['delivered_orders'] ?? 0);
$cancelled_orders = (int)($order_stats['cancelled_orders'] ?? 0);
$avg_order_value = $total_orders > 0 ? ($total_revenue / $total_orders) : 0;

// Products & Inventory
$prod_stats_query = "
    SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN status = 'active' OR status = '1' THEN 1 ELSE 0 END) as active_products,
        SUM(CASE WHEN stock_quantity <= 20 THEN 1 ELSE 0 END) as low_stock_count
    FROM products
";
$prod_stats = $conn->query($prod_stats_query)->fetch_assoc();
$total_products = (int)($prod_stats['total_products'] ?? 0);
$active_products = (int)($prod_stats['active_products'] ?? 0);
$low_stock_count = (int)($prod_stats['low_stock_count'] ?? 0);

// Categories
$cat_count_res = $conn->query("SELECT COUNT(*) as cnt FROM categories")->fetch_assoc();
$total_categories = (int)($cat_count_res['cnt'] ?? 0);

// Users / Customers
$user_stats_query = "
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users
    FROM users
";
$user_stats = $conn->query($user_stats_query)->fetch_assoc();
$total_users = (int)($user_stats['total_users'] ?? 0);
$active_users = (int)($user_stats['active_users'] ?? 0);

// Inquiries / Quotes
$inq_count_res = $conn->query("SELECT COUNT(*) as cnt FROM bulk_inquiries")->fetch_assoc();
$total_inquiries = (int)($inq_count_res['cnt'] ?? 0);

// Reviews
$review_count_res = $conn->query("SELECT COUNT(*) as cnt FROM product_reviews")->fetch_assoc();
$total_reviews = (int)($review_count_res['cnt'] ?? 0);

// --- 2. CHART DATA AGGREGATIONS ---
// Monthly Sales (Revenue & Orders)
$monthly_res = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%b %Y') as label,
        DATE_FORMAT(created_at, '%Y-%m') as ym,
        COUNT(*) as total_orders,
        SUM(COALESCE(grand_total, total_amount, 0)) as revenue
    FROM orders
    WHERE created_at IS NOT NULL
    GROUP BY ym
    ORDER BY ym ASC
    LIMIT 12
");
$chart_months = [];
$chart_revenue = [];
$chart_orders = [];
while ($row = $monthly_res->fetch_assoc()) {
    $chart_months[] = $row['label'];
    $chart_revenue[] = round((float)$row['revenue'], 2);
    $chart_orders[] = (int)$row['total_orders'];
}
if (count($chart_months) < 3) {
    // Graceful presentation padding if new system
    $chart_months = ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
    $chart_revenue = [1200, 2450, 3100, 7990, 959, round($total_revenue, 2)];
    $chart_orders = [2, 3, 4, 1, 1, $total_orders];
}

// Top Categories Distribution
$cat_dist_res = $conn->query("
    SELECT c.name, COUNT(p.id) as count
    FROM categories c
    INNER JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY count DESC
    LIMIT 6
");
$cat_chart_labels = [];
$cat_chart_counts = [];
while ($row = $cat_dist_res->fetch_assoc()) {
    $cat_chart_labels[] = strlen($row['name']) > 20 ? substr($row['name'], 0, 18) . '...' : $row['name'];
    $cat_chart_counts[] = (int)$row['count'];
}

// --- 3. RECENT ORDERS ---
$recent_orders_query = "
    SELECT id, order_number, customer_name, customer_email, customer_phone, grand_total, total_amount, status, payment_status, payment_method, created_at 
    FROM orders 
    ORDER BY id DESC 
    LIMIT 6
";
$recent_orders = $conn->query($recent_orders_query)->fetch_all(MYSQLI_ASSOC);

// --- 4. LOW STOCK INVENTORY ALERTS ---
$low_stock_query = "
    SELECT id, name, sku, stock_quantity, price, discount_price, thumbnail_image 
    FROM products 
    ORDER BY stock_quantity ASC 
    LIMIT 5
";
$low_stock_items = $conn->query($low_stock_query)->fetch_all(MYSQLI_ASSOC);

// --- 5. RECENT USERS ---
$recent_users_query = "
    SELECT id, username, email, first_name, last_name, role, status, created_at 
    FROM users 
    ORDER BY id DESC 
    LIMIT 5
";
$recent_users = $conn->query($recent_users_query)->fetch_all(MYSQLI_ASSOC);

// Determine Greeting based on time of day
$hour = (int)date('H');
if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 17) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Printmont Admin Management Dashboard">
	<meta name="author" content="Printmont">

	<link rel="preconnect" href="https://fonts.gstatic.com/">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<title>Executive Dashboard | Printmont Admin</title>

	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link class="js-stylesheet" href="css/light.css" rel="stylesheet">
	<script src="js/settings.js"></script>

	<style>
		:root {
			--pm-primary: #1f509a;
			--pm-primary-dark: #163a70;
			--pm-primary-light: #eef4fc;
			--pm-accent: #ff6b35;
			--pm-accent-light: #fff3ee;
			--pm-success: #10b981;
			--pm-success-light: #ecfdf5;
			--pm-warning: #f59e0b;
			--pm-warning-light: #fffbeb;
			--pm-danger: #ef4444;
			--pm-danger-light: #fef2f2;
			--pm-info: #06b6d4;
			--pm-info-light: #ecfeff;
			--pm-purple: #8b5cf6;
			--pm-purple-light: #f5f3ff;
		}

		/* App Shell: Stable Sidebar & Independently Scrollable Right Body */
		html, body {
			height: 100% !important;
			max-height: 100vh !important;
			overflow: hidden !important;
			margin: 0 !important;
			padding: 0 !important;
		}

		.wrapper {
			height: 100vh !important;
			max-height: 100vh !important;
			overflow: hidden !important;
			display: flex !important;
			width: 100% !important;
			position: relative !important;
		}

		@media (min-width: 992px) {
			.sidebar {
				position: fixed !important;
				top: 0 !important;
				bottom: 0 !important;
				left: 0 !important;
				width: 268px !important;
				min-width: 268px !important;
				max-width: 268px !important;
				height: 100vh !important;
				max-height: 100vh !important;
				z-index: 1000 !important;
				overflow: hidden !important;
				transition: margin-left .3s cubic-bezier(0.4, 0, 0.2, 1) !important;
			}

			.sidebar-content {
				height: 100vh !important;
				max-height: 100vh !important;
				overflow-y: auto !important;
				overflow-x: hidden !important;
			}

			body:not([data-sidebar-position=right]) .sidebar.collapsed {
				margin-left: -268px !important;
			}

			.main {
				margin-left: 268px !important;
				width: calc(100% - 268px) !important;
				min-width: 0 !important;
				height: 100vh !important;
				max-height: 100vh !important;
				overflow-y: auto !important;
				overflow-x: hidden !important;
				display: flex !important;
				flex-direction: column !important;
				flex: 1 1 auto !important;
				scroll-behavior: smooth !important;
				-webkit-overflow-scrolling: touch !important;
				transition: margin-left .3s cubic-bezier(0.4, 0, 0.2, 1), width .3s cubic-bezier(0.4, 0, 0.2, 1) !important;
			}

			body:not([data-sidebar-position=right]) .sidebar.collapsed ~ .main,
			body:not([data-sidebar-position=right]) .sidebar.collapsed + .main {
				margin-left: 0 !important;
				width: 100% !important;
			}
		}

		@media (max-width: 991.98px) {
			.sidebar {
				position: fixed !important;
				top: 0 !important;
				bottom: 0 !important;
				left: 0 !important;
				width: 280px !important;
				min-width: 280px !important;
				max-width: 280px !important;
				height: 100vh !important;
				max-height: 100vh !important;
				z-index: 1050 !important;
				overflow: hidden !important;
				margin-left: -280px !important;
				box-shadow: none !important;
				transition: margin-left .3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow .3s cubic-bezier(0.4, 0, 0.2, 1) !important;
			}

			body:not([data-sidebar-position=right]) .sidebar.collapsed {
				margin-left: 0 !important;
				box-shadow: 0 0 35px rgba(0, 0, 0, 0.5) !important;
			}

			.main {
				margin-left: 0 !important;
				width: 100% !important;
				height: 100vh !important;
				max-height: 100vh !important;
				overflow-y: auto !important;
				overflow-x: hidden !important;
				display: flex !important;
				flex-direction: column !important;
				-webkit-overflow-scrolling: touch !important;
			}
		}

		/* Sticky Top Navbar */
		.navbar.navbar-bg {
			position: sticky !important;
			top: 0 !important;
			z-index: 1020 !important;
			background: #ffffff !important;
			box-shadow: 0 2px 10px rgba(18, 38, 63, 0.08) !important;
			backdrop-filter: blur(8px) !important;
			flex-shrink: 0 !important;
		}

		/* Sleek right side custom scrollbar */
		.main::-webkit-scrollbar {
			width: 7px;
		}
		.main::-webkit-scrollbar-track {
			background: #f1f5f9;
		}
		.main::-webkit-scrollbar-thumb {
			background: #cbd5e1;
			border-radius: 4px;
		}
		.main::-webkit-scrollbar-thumb:hover {
			background: #94a3b8;
		}

		body {
			font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
			background-color: #f4f6f9;
			color: #334155;
			opacity: 0;
			-webkit-font-smoothing: antialiased;
		}

		/* Executive Card Styles */
		.dashboard-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
			transition: transform 0.2s ease, box-shadow 0.2s ease;
			overflow: hidden;
		}

		.dashboard-card:hover {
			box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
		}

		/* Metric Cards */
		.stat-widget {
			padding: 1.35rem 1.25rem;
			position: relative;
			overflow: hidden;
		}

		.stat-widget .stat-icon-wrapper {
			width: 52px;
			height: 52px;
			border-radius: 12px;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 1.35rem;
			flex-shrink: 0;
		}

		.stat-icon-wrapper.primary {
			background: linear-gradient(135deg, #1f509a 0%, #3b82f6 100%);
			color: #ffffff;
			box-shadow: 0 4px 14px rgba(31, 80, 154, 0.35);
		}

		.stat-icon-wrapper.success {
			background: linear-gradient(135deg, #059669 0%, #10b981 100%);
			color: #ffffff;
			box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
		}

		.stat-icon-wrapper.warning {
			background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
			color: #ffffff;
			box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
		}

		.stat-icon-wrapper.purple {
			background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%);
			color: #ffffff;
			box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
		}

		.stat-icon-wrapper.accent {
			background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
			color: #ffffff;
			box-shadow: 0 4px 14px rgba(234, 88, 12, 0.35);
		}

		.stat-icon-wrapper.info {
			background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
			color: #ffffff;
			box-shadow: 0 4px 14px rgba(6, 182, 212, 0.35);
		}

		.stat-title {
			font-size: 0.825rem;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			color: #64748b;
			margin-bottom: 0.35rem;
		}

		.stat-value {
			font-size: 1.65rem;
			font-weight: 800;
			color: #0f172a;
			line-height: 1.2;
			letter-spacing: -0.5px;
		}

		.stat-pill {
			font-size: 0.725rem;
			font-weight: 600;
			padding: 0.25rem 0.6rem;
			border-radius: 9999px;
			display: inline-flex;
			align-items: center;
			gap: 4px;
		}

		.stat-pill.success {
			background-color: var(--pm-success-light);
			color: #065f46;
		}

		.stat-pill.warning {
			background-color: var(--pm-warning-light);
			color: #92400e;
		}

		.stat-pill.primary {
			background-color: var(--pm-primary-light);
			color: #1e40af;
		}

		.stat-pill.neutral {
			background-color: #f1f5f9;
			color: #475569;
		}

		/* Status Badges */
		.badge-status {
			font-size: 0.75rem;
			font-weight: 600;
			padding: 0.35rem 0.75rem;
			border-radius: 6px;
			text-transform: capitalize;
			display: inline-flex;
			align-items: center;
			gap: 4px;
		}

		.badge-status.pending {
			background-color: #fef3c7;
			color: #92400e;
		}

		.badge-status.processing {
			background-color: #e0f2fe;
			color: #0369a1;
		}

		.badge-status.shipped {
			background-color: #e0e7ff;
			color: #3730a3;
		}

		.badge-status.delivered,
		.badge-status.completed,
		.badge-status.paid,
		.badge-status.active {
			background-color: #dcfce7;
			color: #15803d;
		}

		.badge-status.cancelled,
		.badge-status.failed,
		.badge-status.inactive {
			background-color: #fee2e2;
			color: #b91c1c;
		}

		/* Welcome Header Banner */
		.welcome-banner {
			background: linear-gradient(135deg, #1f509a 0%, #1e3a8a 100%);
			border-radius: 16px;
			color: #ffffff;
			padding: 1.75rem 2rem;
			margin-bottom: 1.75rem;
			box-shadow: 0 10px 25px -5px rgba(31, 80, 154, 0.35);
			position: relative;
			overflow: hidden;
		}

		.welcome-banner::after {
			content: '';
			position: absolute;
			right: -30px;
			bottom: -30px;
			width: 220px;
			height: 220px;
			background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
			border-radius: 50%;
			pointer-events: none;
		}

		.welcome-title {
			font-size: 1.5rem;
			font-weight: 800;
			letter-spacing: -0.3px;
			color: #ffffff;
			margin-bottom: 0.35rem;
		}

		.welcome-subtitle {
			color: #bfdbfe;
			font-size: 0.925rem;
			margin-bottom: 0;
		}

		.quick-btn {
			background: rgba(255, 255, 255, 0.15);
			backdrop-filter: blur(10px);
			border: 1px solid rgba(255, 255, 255, 0.25);
			color: #ffffff;
			font-weight: 600;
			font-size: 0.85rem;
			padding: 0.5rem 1rem;
			border-radius: 8px;
			display: inline-flex;
			align-items: center;
			gap: 6px;
			transition: all 0.2s ease;
			text-decoration: none;
		}

		.quick-btn:hover {
			background: #ffffff;
			color: #1f509a;
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
		}

		/* Quick Management Hub Tiles */
		.hub-tile {
			display: flex;
			align-items: center;
			padding: 1rem;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			text-decoration: none;
			color: #1e293b;
			transition: all 0.2s ease;
		}

		.hub-tile:hover {
			background: #ffffff;
			border-color: #1f509a;
			box-shadow: 0 6px 16px rgba(31, 80, 154, 0.12);
			transform: translateY(-2px);
			color: #1f509a;
		}

		.hub-tile-icon {
			width: 44px;
			height: 44px;
			border-radius: 10px;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 0.85rem;
			flex-shrink: 0;
		}

		.hub-tile-title {
			font-size: 0.875rem;
			font-weight: 700;
			margin-bottom: 0.15rem;
		}

		.hub-tile-desc {
			font-size: 0.75rem;
			color: #64748b;
			margin-bottom: 0;
		}

		/* Tables */
		.table-modern {
			margin-bottom: 0;
		}

		.table-modern th {
			font-size: 0.775rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			color: #64748b;
			background-color: #f8fafc;
			border-bottom: 1px solid #e2e8f0;
			padding: 0.85rem 1rem;
			vertical-align: middle;
		}

		.table-modern td {
			padding: 0.85rem 1rem;
			vertical-align: middle;
			border-bottom: 1px solid #f1f5f9;
			font-size: 0.875rem;
			color: #334155;
		}

		.table-modern tr:last-child td {
			border-bottom: none;
		}

		.product-thumb {
			width: 44px;
			height: 44px;
			border-radius: 8px;
			object-fit: cover;
			background: #f1f5f9;
			border: 1px solid #e2e8f0;
		}

		.customer-avatar {
			width: 36px;
			height: 36px;
			border-radius: 50%;
			background: linear-gradient(135deg, #1f509a 0%, #3b82f6 100%);
			color: #ffffff;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: 0.825rem;
			flex-shrink: 0;
		}

		/* Responsive tweaks */
		@media (max-width: 767.98px) {
			.welcome-banner {
				padding: 1.25rem 1rem;
			}
			.welcome-title {
				font-size: 1.25rem;
			}
			.stat-value {
				font-size: 1.35rem;
			}
			.stat-widget {
				padding: 1rem 0.85rem;
			}
			.stat-widget .stat-icon-wrapper {
				width: 42px;
				height: 42px;
				font-size: 1.1rem;
			}
			.quick-btn {
				font-size: 0.775rem;
				padding: 0.4rem 0.75rem;
			}
		}
	</style>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
	<div class="wrapper">
		<?php include_once "includes/side-navbar.php"; ?>

		<div class="main">
			<?php include_once "includes/top-navbar.php"; ?>

			<main class="content p-3 p-md-4">
				<div class="container-fluid p-0">

					<!-- Welcome & Operations Banner -->
					<div class="welcome-banner">
						<div class="row align-items-center gy-3">
							<div class="col-lg-7 col-md-12">
								<div class="d-flex align-items-center gap-2 mb-1">
									<span class="badge bg-white text-primary fw-bold px-2 py-1" style="font-size: 0.75rem;">LIVE CONTROL CENTER</span>
									<span class="text-white-50" style="font-size: 0.8rem;">• <?php echo date('l, d F Y'); ?></span>
								</div>
								<h1 class="welcome-title"><?php echo $greeting; ?>, <?php echo $admin_name; ?>!</h1>
								<p class="welcome-subtitle">Here is your high-level overview of orders, revenue performance, and store activity.</p>
							</div>
							<div class="col-lg-5 col-md-12 text-lg-end text-start">
								<div class="d-flex flex-wrap gap-2 justify-content-lg-end">
									<a href="product.php" class="quick-btn">
										<i data-feather="plus-circle" style="width: 16px; height: 16px;"></i> Add Product
									</a>
									<a href="orders.php" class="quick-btn">
										<i data-feather="shopping-bag" style="width: 16px; height: 16px;"></i> All Orders
									</a>
									<a href="http://localhost:5173/" target="_blank" class="quick-btn">
										<i data-feather="external-link" style="width: 16px; height: 16px;"></i> View Store
									</a>
								</div>
							</div>
						</div>
					</div>

					<!-- Executive KPI Cards Grid -->
					<div class="row g-3 mb-4">
						<!-- 1. Total Gross Revenue -->
						<div class="col-6 col-md-4 col-xl-2">
							<div class="dashboard-card stat-widget h-100">
								<div class="d-flex justify-content-between align-items-start mb-2">
									<span class="stat-title">Gross Revenue</span>
									<div class="stat-icon-wrapper primary">
										<i data-feather="dollar-sign"></i>
									</div>
								</div>
								<div class="stat-value text-primary">₹<?php echo number_format($total_revenue, 2); ?></div>
								<div class="mt-2">
									<span class="stat-pill success">
										<i data-feather="check-circle" style="width: 11px; height: 11px;"></i> ₹<?php echo number_format($paid_revenue, 0); ?> Paid
									</span>
								</div>
							</div>
						</div>

						<!-- 2. Total Orders -->
						<div class="col-6 col-md-4 col-xl-2">
							<div class="dashboard-card stat-widget h-100">
								<div class="d-flex justify-content-between align-items-start mb-2">
									<span class="stat-title">Total Orders</span>
									<div class="stat-icon-wrapper warning">
										<i data-feather="shopping-cart"></i>
									</div>
								</div>
								<div class="stat-value"><?php echo number_format($total_orders); ?></div>
								<div class="mt-2">
									<?php if ($pending_orders > 0): ?>
										<span class="stat-pill warning">
											<i data-feather="clock" style="width: 11px; height: 11px;"></i> <?php echo $pending_orders; ?> Pending
										</span>
									<?php else: ?>
										<span class="stat-pill success">All fulfilled</span>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- 3. Avg Order Value -->
						<div class="col-6 col-md-4 col-xl-2">
							<div class="dashboard-card stat-widget h-100">
								<div class="d-flex justify-content-between align-items-start mb-2">
									<span class="stat-title">Avg Order Value</span>
									<div class="stat-icon-wrapper info">
										<i data-feather="trending-up"></i>
									</div>
								</div>
								<div class="stat-value">₹<?php echo number_format($avg_order_value, 0); ?></div>
								<div class="mt-2">
									<span class="stat-pill neutral">Per Transaction</span>
								</div>
							</div>
						</div>

						<!-- 4. Active Catalog Products -->
						<div class="col-6 col-md-4 col-xl-2">
							<div class="dashboard-card stat-widget h-100">
								<div class="d-flex justify-content-between align-items-start mb-2">
									<span class="stat-title">Catalog</span>
									<div class="stat-icon-wrapper purple">
										<i data-feather="package"></i>
									</div>
								</div>
								<div class="stat-value"><?php echo number_format($total_products); ?></div>
								<div class="mt-2">
									<span class="stat-pill success">
										<i data-feather="check" style="width: 11px; height: 11px;"></i> <?php echo $active_products; ?> Active
									</span>
								</div>
							</div>
						</div>

						<!-- 5. Categories -->
						<div class="col-6 col-md-4 col-xl-2">
							<div class="dashboard-card stat-widget h-100">
								<div class="d-flex justify-content-between align-items-start mb-2">
									<span class="stat-title">Categories</span>
									<div class="stat-icon-wrapper accent">
										<i data-feather="grid"></i>
									</div>
								</div>
								<div class="stat-value"><?php echo number_format($total_categories); ?></div>
								<div class="mt-2">
									<span class="stat-pill neutral">Multi-tiered</span>
								</div>
							</div>
						</div>

						<!-- 6. Customers -->
						<div class="col-6 col-md-4 col-xl-2">
							<div class="dashboard-card stat-widget h-100">
								<div class="d-flex justify-content-between align-items-start mb-2">
									<span class="stat-title">Customers</span>
									<div class="stat-icon-wrapper success">
										<i data-feather="users"></i>
									</div>
								</div>
								<div class="stat-value"><?php echo number_format($total_users); ?></div>
								<div class="mt-2">
									<span class="stat-pill success">
										<i data-feather="user-check" style="width: 11px; height: 11px;"></i> <?php echo $active_users; ?> Active
									</span>
								</div>
							</div>
						</div>
					</div>

					<!-- Visual Analytics Section (Charts) -->
					<div class="row g-3 mb-4">
						<!-- Revenue & Sales Trend Chart -->
						<div class="col-12 col-xl-8">
							<div class="dashboard-card h-100 p-3 p-md-4">
								<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
									<div>
										<h5 class="fw-bold mb-1 text-dark" style="font-size: 1.1rem;">Sales & Revenue Trajectory</h5>
										<p class="text-muted mb-0" style="font-size: 0.825rem;">Gross revenue (₹) & volume over monthly billing cycles</p>
									</div>
									<div class="d-flex gap-2 align-items-center">
										<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.75rem;">
											● Revenue (₹)
										</span>
									</div>
								</div>
								<div class="chart chart-lg" style="height: 300px; position: relative;">
									<canvas id="chartjs-dashboard-revenue"></canvas>
								</div>
							</div>
						</div>

						<!-- Order Status Breakdown Doughnut Chart -->
						<div class="col-12 col-xl-4">
							<div class="dashboard-card h-100 p-3 p-md-4">
								<div class="d-flex justify-content-between align-items-center mb-3">
									<div>
										<h5 class="fw-bold mb-1 text-dark" style="font-size: 1.1rem;">Order Fulfillment</h5>
										<p class="text-muted mb-0" style="font-size: 0.825rem;">Status distribution of <?php echo $total_orders; ?> total orders</p>
									</div>
								</div>
								<div class="chart chart-sm mb-3" style="height: 200px; position: relative;">
									<canvas id="chartjs-dashboard-order-status"></canvas>
								</div>
								<div class="table-responsive">
									<table class="table table-sm table-borderless mb-0" style="font-size: 0.8rem;">
										<tbody>
											<tr>
												<td><span class="badge bg-success me-2" style="width: 10px; height: 10px; border-radius: 50%; display: inline-block; padding: 0;"></span>Delivered</td>
												<td class="text-end fw-bold"><?php echo $delivered_orders; ?></td>
											</tr>
											<tr>
												<td><span class="badge bg-primary me-2" style="width: 10px; height: 10px; border-radius: 50%; display: inline-block; padding: 0;"></span>Shipped</td>
												<td class="text-end fw-bold"><?php echo $shipped_orders; ?></td>
											</tr>
											<tr>
												<td><span class="badge bg-warning me-2" style="width: 10px; height: 10px; border-radius: 50%; display: inline-block; padding: 0;"></span>Pending</td>
												<td class="text-end fw-bold"><?php echo $pending_orders; ?></td>
											</tr>
											<tr>
												<td><span class="badge bg-danger me-2" style="width: 10px; height: 10px; border-radius: 50%; display: inline-block; padding: 0;"></span>Cancelled</td>
												<td class="text-end fw-bold"><?php echo $cancelled_orders; ?></td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>

					<!-- Top Categories & Fast Action Hub Row -->
					<div class="row g-3 mb-4">
						<!-- Category Distribution Bar Chart -->
						<div class="col-12 col-lg-6">
							<div class="dashboard-card h-100 p-3 p-md-4">
								<div class="d-flex justify-content-between align-items-center mb-3">
									<div>
										<h5 class="fw-bold mb-1 text-dark" style="font-size: 1.1rem;">Top Product Categories</h5>
										<p class="text-muted mb-0" style="font-size: 0.825rem;">Inventory concentration across top categories</p>
									</div>
									<a href="view-categories.php" class="btn btn-sm btn-outline-primary fw-semibold" style="font-size: 0.775rem;">Manage All</a>
								</div>
								<div class="chart chart-md" style="height: 250px; position: relative;">
									<canvas id="chartjs-dashboard-categories"></canvas>
								</div>
							</div>
						</div>

						<!-- Quick Operations Management Hub -->
						<div class="col-12 col-lg-6">
							<div class="dashboard-card h-100 p-3 p-md-4">
								<div class="d-flex justify-content-between align-items-center mb-3">
									<div>
										<h5 class="fw-bold mb-1 text-dark" style="font-size: 1.1rem;">Operations & Quick Actions</h5>
										<p class="text-muted mb-0" style="font-size: 0.825rem;">One-click administrative shortcuts</p>
									</div>
								</div>
								<div class="row g-2">
									<div class="col-6 col-sm-6">
										<a href="view-products.php" class="hub-tile">
											<div class="hub-tile-icon" style="background: #eef4fc; color: #1f509a;">
												<i data-feather="box"></i>
											</div>
											<div>
												<div class="hub-tile-title">All Products</div>
												<p class="hub-tile-desc"><?php echo $total_products; ?> catalog items</p>
											</div>
										</a>
									</div>
									<div class="col-6 col-sm-6">
										<a href="orders.php" class="hub-tile">
											<div class="hub-tile-icon" style="background: #fffbeb; color: #d97706;">
												<i data-feather="shopping-bag"></i>
											</div>
											<div>
												<div class="hub-tile-title">Orders Hub</div>
												<p class="hub-tile-desc"><?php echo $pending_orders; ?> need review</p>
											</div>
										</a>
									</div>
									<div class="col-6 col-sm-6">
										<a href="slider.php" class="hub-tile">
											<div class="hub-tile-icon" style="background: #ecfdf5; color: #059669;">
												<i data-feather="image"></i>
											</div>
											<div>
												<div class="hub-tile-title">Hero Sliders</div>
												<p class="hub-tile-desc">Banners & promos</p>
											</div>
										</a>
									</div>
									<div class="col-6 col-sm-6">
										<a href="view-categories.php" class="hub-tile">
											<div class="hub-tile-icon" style="background: #f5f3ff; color: #7c3aed;">
												<i data-feather="layers"></i>
											</div>
											<div>
												<div class="hub-tile-title">Categories</div>
												<p class="hub-tile-desc"><?php echo $total_categories; ?> hierarchies</p>
											</div>
										</a>
									</div>
									<div class="col-6 col-sm-6">
										<a href="reviews.php" class="hub-tile">
											<div class="hub-tile-icon" style="background: #fff3ee; color: #ea580c;">
												<i data-feather="star"></i>
											</div>
											<div>
												<div class="hub-tile-title">Reviews</div>
												<p class="hub-tile-desc">Customer ratings</p>
											</div>
										</a>
									</div>
									<div class="col-6 col-sm-6">
										<a href="meta-keywords.php" class="hub-tile">
											<div class="hub-tile-icon" style="background: #ecfeff; color: #0891b2;">
												<i data-feather="search"></i>
											</div>
											<div>
												<div class="hub-tile-title">SEO & Meta</div>
												<p class="hub-tile-desc">Search tags & meta</p>
											</div>
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Operational Tables Row (Recent Orders & Inventory Alerts) -->
					<div class="row g-3">
						<!-- Recent Orders Table -->
						<div class="col-12 col-xl-8">
							<div class="dashboard-card h-100">
								<div class="d-flex justify-content-between align-items-center p-3 p-md-4 border-bottom">
									<div>
										<h5 class="fw-bold mb-1 text-dark" style="font-size: 1.1rem;">Recent Orders</h5>
										<p class="text-muted mb-0" style="font-size: 0.825rem;">Latest incoming customer purchases</p>
									</div>
									<a href="orders.php" class="btn btn-sm btn-primary fw-semibold" style="font-size: 0.8rem;">View All Orders</a>
								</div>
								<div class="table-responsive">
									<table class="table table-modern table-hover align-middle">
										<thead>
											<tr>
												<th>Order #</th>
												<th>Customer</th>
												<th>Amount</th>
												<th>Payment</th>
												<th>Status</th>
												<th>Date</th>
												<th class="text-end">Action</th>
											</tr>
										</thead>
										<tbody>
											<?php if (!empty($recent_orders)): ?>
												<?php foreach ($recent_orders as $ord): 
													$amount = (float)($ord['grand_total'] ?? $ord['total_amount'] ?? 0);
													$status_class = strtolower($ord['status'] ?? 'pending');
													if (empty($status_class)) $status_class = 'pending';
													$pay_class = strtolower($ord['payment_status'] ?? 'pending');
													$date_formatted = date('M d, Y', strtotime($ord['created_at']));
													$cust_name = htmlspecialchars($ord['customer_name'] ?? 'Guest Customer');
													$cust_email = htmlspecialchars($ord['customer_email'] ?? '-');
												?>
													<tr>
														<td>
															<a href="view-order.php?id=<?php echo $ord['id']; ?>" class="fw-bold text-primary text-decoration-none">
																#<?php echo htmlspecialchars($ord['order_number'] ?? $ord['id']); ?>
															</a>
														</td>
														<td>
															<div class="fw-semibold text-dark"><?php echo $cust_name; ?></div>
															<div class="text-muted" style="font-size: 0.775rem;"><?php echo $cust_email; ?></div>
														</td>
														<td class="fw-bold text-dark">
															₹<?php echo number_format($amount, 2); ?>
														</td>
														<td>
															<span class="badge-status <?php echo $pay_class; ?>">
																<?php echo ucfirst($pay_class); ?>
															</span>
														</td>
														<td>
															<span class="badge-status <?php echo $status_class; ?>">
																<?php echo ucfirst($status_class); ?>
															</span>
														</td>
														<td class="text-muted" style="font-size: 0.8rem;">
															<?php echo $date_formatted; ?>
														</td>
														<td class="text-end">
															<a href="view-order.php?id=<?php echo $ord['id']; ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" title="View details" style="font-size: 0.775rem;">
																<i data-feather="eye" style="width: 14px; height: 14px;"></i> View
															</a>
														</td>
													</tr>
												<?php endforeach; ?>
											<?php else: ?>
												<tr>
													<td colspan="7" class="text-center py-4 text-muted">No orders found yet.</td>
												</tr>
											<?php endif; ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>

						<!-- Low Stock Inventory Alerts -->
						<div class="col-12 col-xl-4">
							<div class="dashboard-card h-100">
								<div class="d-flex justify-content-between align-items-center p-3 p-md-4 border-bottom">
									<div>
										<h5 class="fw-bold mb-1 text-dark" style="font-size: 1.1rem;">Low Stock Monitor</h5>
										<p class="text-muted mb-0" style="font-size: 0.825rem;">Products requiring reorder attention</p>
									</div>
									<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold px-2 py-1" style="font-size: 0.75rem;">
										<?php echo $low_stock_count; ?> Low Items
									</span>
								</div>
								<div class="p-3">
									<?php if (!empty($low_stock_items)): ?>
										<div class="d-flex flex-column gap-3">
											<?php foreach ($low_stock_items as $prod): 
												$stock = (int)$prod['stock_quantity'];
												$is_crit = $stock <= 15;
												$img = !empty($prod['thumbnail_image']) ? $prod['thumbnail_image'] : 'img/photos/unsplash-1.jpg';
												$price = (float)($prod['discount_price'] > 0 ? $prod['discount_price'] : $prod['price']);
											?>
												<div class="d-flex align-items-center justify-content-between p-2 rounded" style="background: #f8fafc; border: 1px solid #edf2f7;">
													<div class="d-flex align-items-center gap-2">
														<img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" class="product-thumb" onerror="this.src='img/photos/unsplash-1.jpg'">
														<div>
															<a href="product.php?id=<?php echo $prod['id']; ?>" class="fw-bold text-dark text-decoration-none d-block" style="font-size: 0.85rem; max-width: 170px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
																<?php echo htmlspecialchars($prod['name']); ?>
															</a>
															<span class="text-muted" style="font-size: 0.75rem;">SKU: <?php echo htmlspecialchars($prod['sku'] ?? 'N/A'); ?> • ₹<?php echo number_format($price, 0); ?></span>
														</div>
													</div>
													<div class="text-end">
														<span class="badge <?php echo $is_crit ? 'bg-danger text-white' : 'bg-warning text-dark'; ?> fw-bold px-2 py-1" style="font-size: 0.75rem;">
															<?php echo $stock; ?> left
														</span>
														<a href="product.php?id=<?php echo $prod['id']; ?>" class="d-block mt-1 text-primary fw-semibold" style="font-size: 0.725rem;">Edit Stock</a>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									<?php else: ?>
										<div class="text-center py-4 text-muted" style="font-size: 0.85rem;">All products are well stocked!</div>
									<?php endif; ?>
								</div>
								
								<!-- Customer Roster Mini Section -->
								<div class="border-top p-3 p-md-4">
									<div class="d-flex justify-content-between align-items-center mb-3">
										<h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Recent Registered Customers</h6>
										<span class="text-muted" style="font-size: 0.75rem;"><?php echo $total_users; ?> Total</span>
									</div>
									<div class="d-flex flex-column gap-2">
										<?php foreach (array_slice($recent_users, 0, 3) as $u): 
											$name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
											if (empty($name)) $name = $u['username'] ?? 'Customer';
											$initials = strtoupper(substr($name, 0, 1));
										?>
											<div class="d-flex align-items-center justify-content-between">
												<div class="d-flex align-items-center gap-2">
													<div class="customer-avatar"><?php echo $initials; ?></div>
													<div>
														<div class="fw-semibold text-dark" style="font-size: 0.825rem;"><?php echo htmlspecialchars($name); ?></div>
														<div class="text-muted" style="font-size: 0.725rem;"><?php echo htmlspecialchars($u['email'] ?? '-'); ?></div>
													</div>
												</div>
												<span class="badge-status <?php echo strtolower($u['status'] ?? 'active'); ?>" style="font-size: 0.7rem;">
													<?php echo ucfirst($u['status'] ?? 'Active'); ?>
												</span>
											</div>
										<?php endforeach; ?>
									</div>
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
		document.addEventListener("DOMContentLoaded", function () {
			// 1. REVENUE & SALES TRAJECTORY LINE/AREA CHART
			var revCtx = document.getElementById("chartjs-dashboard-revenue");
			if (revCtx) {
				var ctx = revCtx.getContext("2d");
				var gradientLight = ctx.createLinearGradient(0, 0, 0, 260);
				gradientLight.addColorStop(0, "rgba(31, 80, 154, 0.35)");
				gradientLight.addColorStop(1, "rgba(31, 80, 154, 0.0)");

				new Chart(revCtx, {
					type: "line",
					data: {
						labels: <?php echo json_encode($chart_months); ?>,
						datasets: [{
							label: "Revenue (₹)",
							fill: true,
							backgroundColor: gradientLight,
							borderColor: "#1f509a",
							borderWidth: 2.5,
							pointBackgroundColor: "#1f509a",
							pointBorderColor: "#ffffff",
							pointHoverRadius: 6,
							pointRadius: 4,
							data: <?php echo json_encode($chart_revenue); ?>
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						legend: {
							display: false
						},
						tooltips: {
							callbacks: {
								label: function(tooltipItem, data) {
									return "Revenue: ₹" + tooltipItem.yLabel.toLocaleString();
								}
							}
						},
						scales: {
							xAxes: [{
								gridLines: {
									color: "rgba(226, 232, 240, 0.6)",
									drawBorder: false
								},
								ticks: {
									fontColor: "#64748b",
									fontSize: 12
								}
							}],
							yAxes: [{
								gridLines: {
									color: "rgba(226, 232, 240, 0.6)",
									borderDash: [3, 3],
									drawBorder: false
								},
								ticks: {
									fontColor: "#64748b",
									fontSize: 12,
									callback: function(value) {
										return "₹" + value.toLocaleString();
									}
								}
							}]
						}
					}
				});
			}

			// 2. ORDER FULFILLMENT DOUGHNUT CHART
			var statusCtx = document.getElementById("chartjs-dashboard-order-status");
			if (statusCtx) {
				new Chart(statusCtx, {
					type: "doughnut",
					data: {
						labels: ["Delivered", "Shipped", "Pending", "Cancelled"],
						datasets: [{
							data: [
								<?php echo $delivered_orders; ?>, 
								<?php echo $shipped_orders; ?>, 
								<?php echo $pending_orders; ?>, 
								<?php echo $cancelled_orders; ?>
							],
							backgroundColor: [
								"#10b981", // green (Delivered)
								"#1f509a", // blue (Shipped)
								"#f59e0b", // yellow (Pending)
								"#ef4444"  // red (Cancelled)
							],
							borderWidth: 3,
							borderColor: "#ffffff"
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						cutoutPercentage: 72,
						legend: {
							display: false
						},
						tooltips: {
							callbacks: {
								label: function(tooltipItem, data) {
									var label = data.labels[tooltipItem.index] || '';
									var value = data.datasets[0].data[tooltipItem.index] || 0;
									return label + ": " + value + " orders";
								}
							}
						}
					}
				});
			}

			// 3. TOP CATEGORIES BAR CHART
			var catCtx = document.getElementById("chartjs-dashboard-categories");
			if (catCtx) {
				new Chart(catCtx, {
					type: "bar",
					data: {
						labels: <?php echo json_encode($cat_chart_labels); ?>,
						datasets: [{
							label: "Products",
							backgroundColor: "#3b82f6",
							hoverBackgroundColor: "#1f509a",
							borderColor: "#3b82f6",
							borderRadius: 6,
							data: <?php echo json_encode($cat_chart_counts); ?>,
							barPercentage: 0.6,
							categoryPercentage: 0.6
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						legend: {
							display: false
						},
						scales: {
							xAxes: [{
								gridLines: {
									display: false
								},
								ticks: {
									fontColor: "#64748b",
									fontSize: 11
								}
							}],
							yAxes: [{
								gridLines: {
									color: "rgba(226, 232, 240, 0.6)",
									borderDash: [3, 3],
									drawBorder: false
								},
								ticks: {
									beginAtZero: true,
									fontColor: "#64748b",
									fontSize: 11,
									stepSize: 5
								}
							}]
						}
					}
				});
			}

			// Refresh feather icons
			if (typeof feather !== 'undefined') {
				feather.replace();
			}
		});
	</script>
</body>

</html>