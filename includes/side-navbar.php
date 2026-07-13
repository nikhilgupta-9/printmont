<?php
// Authentication check for dashboard

// Start session first
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}

// Use relative paths
include_once(__DIR__ . '/../config/database.php');
include_once(__DIR__ . '/../controllers/AuthController.php');

try {
	$database = new Database();
	$db = $database->getConnection();
	$auth = new AuthController($db);

	// Check if user is logged in
	if (!$auth->isLoggedIn()) {
		header('Location: ../index.php');
		exit();
	}

	// Get current user info
	$current_user = $auth->getCurrentUser();
	$user_role = $current_user['role'] ?? 'admin';

} catch (Exception $e) {
	error_log("Authentication error: " . $e->getMessage());
	header('Location: ../index.php');
	exit();
}

// Function to check if menu item should be displayed based on role
function shouldDisplay($required_roles, $user_role)
{
	return in_array($user_role, $required_roles) || in_array('all', $required_roles);
}

// Define role-based access
$menu_access = [
	'dashboard' => ['all'],
	'orders' => ['admin', 'manager', 'staff'],
	'products' => ['admin', 'manager', 'staff'],
	'categories' => ['admin', 'manager'],
	'home_page' => ['admin', 'manager'],
	'customers' => ['admin', 'manager', 'staff'],
	'marketing' => ['admin', 'manager'],
	'settings' => ['admin'],
	'staff_management' => ['admin'],
	'website_management' => ['admin', 'manager'],
	'analytics' => ['admin', 'manager'],
	'blog' => ['admin', 'manager', 'staff'],
	'bulk_orders' => ['admin', 'manager', 'staff'],
	'payment' => ['admin'],
	'seo' => ['admin', 'manager']
];

// Get current page URL
$current_page = basename($_SERVER['PHP_SELF']);

// Define page groups for active state
$page_groups = [
	'dashboard' => ['dashboard.php'],

	// Home Page Management
	'home_settings' => [
		'home-layout-manager.php',
		'view-banner.php',
		'add-banner.php',
		'services.php'
	],

	// Categories Management
	'categories' => [
		'view-categories.php',
		'add-main-category.php',
		'add-sub-category.php',
		'add-sub-sub-category.php',
		'home-page-categories.php'
	],

	// Products Management
	'products' => [
		'product.php',
		'view-products.php',
		'top-selection-products.php',
		'besteseller-prodcuts.php',
		'deactive-products.php',
		'single-product-version.php',
		'product-filters.php'
	],

	// Products Review
	'reviews' => [
		'add-review.php',
		'reviews.php'
	],

	// Website Pages
	'pages' => [
		'add-page.php',
		'contact-view.php',
		'about-page.php',
		'careers.php',
		'affiliate-page.php',
		'policy-edit.php',
		'faq-view.php',
		'faq-view-category.php',
		'help-center.php'
	],

	// Watermark
	'watermark' => [
		'add-watermark.php',
		'watermarks.php',
		'deactivated-watermarks.php'
	],

	// Orders Management
	'orders' => [
		'orders.php',
		'pending-orders.php',
		'processing-orders.php',
		'delivered-orders.php',
		'completed-orders.php',
		'declined-orders.php',
		'refund-orders.php',
		'total-sold-orders.php'
	],

	// Coupons
	'coupons' => [
		'add-coupon.php',
		'coupons.php',
		'expired-coupons.php'
	],

	// Customers
	'customers' => [
		'customers.php',
		'customer-images.php'
	],

	// Bulk Order Inquiry
	'inquiry' => [
		'new-inquiry.php',
		'all-inquiry.php',
		'pending-inquiry.php',
		'completed-inquiry.php'
	],

	// Blog Management
	'blog' => [
		'blog-categories.php',
		'add-blog-category.php',
		'add-blog-post.php',
		'blog-posts.php'
	],

	// Email Settings
	'email' => [
		'email-templates.php',
		'email-configurations.php'
	],

	// Social Media
	'social' => [
		'add-social-link.php',
		'social-links.php'
	],

	// SEO Tools
	'seo' => [
		'google-analytics.php',
		'facebook-pixels.php',
		'meta-keywords.php'
	],

	// General Settings
	'general' => [
		'logo-management.php',
		'loader-settings.php',
		'footer-management.php',
		'login-background.php'
	],

	// Staff Management
	'staff_mgmt' => [
		'add-staff.php',
		'staff.php',
		'deactivated-staff.php'
	],

	// Analytics
	'analytics' => [
		'sales-report.php'
	]
];

// Function to check if current page belongs to a group
function isActiveGroup($group_name, $current_page, $page_groups)
{
	if (isset($page_groups[$group_name])) {
		return in_array($current_page, $page_groups[$group_name]);
	}
	return false;
}

// Function to check if current page is active
function isActivePage($page_name, $current_page)
{
	return $current_page === $page_name;
}

// Function to check if parent should be expanded
function shouldExpand($group_name, $current_page, $page_groups)
{
	return isActiveGroup($group_name, $current_page, $page_groups) ||
		(isset($page_groups[$group_name]) && in_array($current_page, $page_groups[$group_name]));
}
?>

<nav id="sidebar" class="sidebar js-sidebar">
	<div class="sidebar-content js-simplebar">
		<a class='sidebar-brand' href='dashboard.php'>
			<span class="sidebar-brand-text align-middle">
				Printmont Admin
			</span>
			<svg class="sidebar-brand-icon align-middle" width="32px" height="32px" viewBox="0 0 24 24" fill="none"
				stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="square" stroke-linejoin="miter" color="#FFFFFF"
				style="margin-left: -3px">
				<path d="M12 4L20 8.00004L12 12L4 8.00004L12 4Z"></path>
				<path d="M20 12L12 16L4 12"></path>
				<path d="M20 16L12 20L4 16"></path>
			</svg>
		</a>

		<div class="sidebar-user">
			<div class="d-flex justify-content-center">
				<div class="flex-shrink-0">
					<img src="img/avatars/avatar.jpg" class="avatar img-fluid rounded me-1"
						alt="<?php echo htmlspecialchars($current_user['username']); ?>" />
				</div>
				<div class="flex-grow-1 ps-2">
					<a class="sidebar-user-title dropdown-toggle" href="#" data-bs-toggle="dropdown">
						<?php echo htmlspecialchars($current_user['username'] ?? 'Admin User'); ?>
					</a>
					<div class="dropdown-menu dropdown-menu-start">
						<a class='dropdown-item' href='pages-profile.php'><i class="align-middle me-1"
								data-feather="user"></i> Profile</a>
						<a class="dropdown-item" href="footer-management.php"><i class="align-middle me-1"
								data-feather="settings"></i>
							Settings</a>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item logout" href="<?= BASE_URL ?>api/auth/logout.php">
							<i class="align-middle me-1" data-feather="log-out"></i> Log out
						</a>
					</div>
					<div class="sidebar-user-subtitle">
						<?php echo htmlspecialchars(ucfirst($current_user['role'] ?? 'Administrator')); ?>
					</div>
				</div>
			</div>
		</div>

		<ul class="sidebar-nav">
			<!-- Dashboard -->
			<?php if (shouldDisplay($menu_access['dashboard'], $user_role)): ?>
				<li class="sidebar-item <?php echo isActivePage('dashboard.php', $current_page) ? 'active' : ''; ?>">
					<a class='sidebar-link' href='dashboard.php'>
						<i class="align-middle" data-feather="home"></i>
						<span class="align-middle">Dashboard</span>
						<?php if (isActivePage('dashboard.php', $current_page)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
				</li>
			<?php endif; ?>

			<!-- Orders Management -->
			<?php if (shouldDisplay($menu_access['orders'], $user_role)): ?>
				<li class="sidebar-header">
					Orders Management
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#orders" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('orders', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="shopping-cart"></i>
						<span class="align-middle">Orders</span>
						<?php if (isActiveGroup('orders', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="orders"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('orders', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='orders.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('orders.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">All Orders</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('pending-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='pending-orders.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('pending-orders.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Pending Orders</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('processing-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='processing-orders.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('processing-orders.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Processing Orders</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('delivered-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='delivered-orders.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('delivered-orders.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Delivered Orders</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('completed-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='completed-orders.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('completed-orders.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Completed Orders</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('declined-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='declined-orders.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('declined-orders.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Declined Orders</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('refund-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='refund-orders.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('refund-orders.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Refund Orders</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('total-sold-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='total-sold-orders.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('total-sold-orders.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Total Sold Orders</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<!-- Home Page Management -->
			<?php if (shouldDisplay($menu_access['home_page'], $user_role)): ?>
				<li class="sidebar-header">
					Home Page Management
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#home-settings" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('home_settings', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="layout"></i>
						<span class="align-middle">Home Page Settings</span>
						<?php if (isActiveGroup('home_settings', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="home-settings"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('home_settings', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li
							class="sidebar-item <?php echo isActivePage('home-layout-manager.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='home-layout-manager.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('home-layout-manager.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Layout Manager</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('view-banner.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='view-banner.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('view-banner.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Slider</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('add-banner.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-banner.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('add-banner.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Add Sliders</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<!-- Products Management -->
			<?php if (shouldDisplay($menu_access['products'], $user_role)): ?>
				<li class="sidebar-header">
					Products Management
				</li>

				<!-- Categories Management -->
				<?php if (shouldDisplay($menu_access['categories'], $user_role)): ?>
					<li class="sidebar-item">
						<a data-bs-target="#categories" data-bs-toggle="collapse"
							class="sidebar-link <?php echo isActiveGroup('categories', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
							<i class="align-middle" data-feather="layers"></i>
							<span class="align-middle">Categories</span>
							<?php if (isActiveGroup('categories', $current_page, $page_groups)): ?>
								<span class="sidebar-badge">●</span>
							<?php endif; ?>
						</a>
						<ul id="categories"
							class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('categories', $current_page, $page_groups) ? 'show' : ''; ?>"
							data-bs-parent="#sidebar">
							<li
								class="sidebar-item <?php echo isActivePage('view-categories.php', $current_page) ? 'active' : ''; ?>">
								<a class='sidebar-link' href='view-categories.php'>
									<i class="align-middle"
										data-feather="<?php echo isActivePage('view-categories.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
									<span class="align-middle">All Categories</span>
								</a>
							</li>
							<li
								class="sidebar-item <?php echo isActivePage('add-main-category.php', $current_page) ? 'active' : ''; ?>">
								<a class='sidebar-link' href='add-main-category.php'>
									<i class="align-middle"
										data-feather="<?php echo isActivePage('add-main-category.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
									<span class="align-middle">Add Category</span>
								</a>
							</li>
							<li
								class="sidebar-item <?php echo isActivePage('add-sub-category.php', $current_page) ? 'active' : ''; ?>">
								<a class='sidebar-link' href='add-sub-category.php'>
									<i class="align-middle"
										data-feather="<?php echo isActivePage('add-sub-category.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
									<span class="align-middle">Add Sub Category</span>
								</a>
							</li>
							<li
								class="sidebar-item <?php echo isActivePage('add-sub-sub-category.php', $current_page) ? 'active' : ''; ?>">
								<a class='sidebar-link' href='add-sub-sub-category.php'>
									<i class="align-middle"
										data-feather="<?php echo isActivePage('add-sub-sub-category.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
									<span class="align-middle">Add Sub Sub Category</span>
								</a>
							</li>
						</ul>
					</li>
				<?php endif; ?>

				<li class="sidebar-item">
					<a data-bs-target="#products" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('products', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="package"></i>
						<span class="align-middle">Products</span>
						<?php if (isActiveGroup('products', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="products"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('products', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('product.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='product.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('product.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Add New Product</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('view-products.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='view-products.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('view-products.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">All Products</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('top-selection-products.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='top-selection-products.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('top-selection-products.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Make Top Selection</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('besteseller-prodcuts.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='besteseller-prodcuts.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('besteseller-prodcuts.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Make Bestseller</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('deactive-products.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='deactive-products.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('deactive-products.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Deactivated Products</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('single-product-version.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='single-product-version.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('single-product-version.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Single Product Version</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('product-filters.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='product-filters.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('product-filters.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Product Filters</span>
							</a>
						</li>
					</ul>
				</li>

				<?php if (shouldDisplay($menu_access['products'], $user_role)): ?>
					<li class="sidebar-item">
						<a data-bs-target="#reviews" data-bs-toggle="collapse"
							class="sidebar-link <?php echo isActiveGroup('reviews', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
							<i class="align-middle" data-feather="message-square"></i>
							<span class="align-middle">Products Review</span>
							<?php if (isActiveGroup('reviews', $current_page, $page_groups)): ?>
								<span class="sidebar-badge">●</span>
							<?php endif; ?>
						</a>
						<ul id="reviews"
							class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('reviews', $current_page, $page_groups) ? 'show' : ''; ?>"
							data-bs-parent="#sidebar">
							<li
								class="sidebar-item <?php echo isActivePage('add-review.php', $current_page) ? 'active' : ''; ?>">
								<a class='sidebar-link' href='add-review.php'>
									<i class="align-middle"
										data-feather="<?php echo isActivePage('add-review.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
									<span class="align-middle">Add Review</span>
								</a>
							</li>
							<li class="sidebar-item <?php echo isActivePage('reviews.php', $current_page) ? 'active' : ''; ?>">
								<a class='sidebar-link' href='reviews.php'>
									<i class="align-middle"
										data-feather="<?php echo isActivePage('reviews.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
									<span class="align-middle">All Reviews</span>
								</a>
							</li>
						</ul>
					</li>
				<?php endif; ?>
			<?php endif; ?>

			<!-- Website Management -->
			<?php if (shouldDisplay($menu_access['website_management'], $user_role)): ?>
				<li class="sidebar-header">
					Website Management
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#pages" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('pages', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="file-text"></i>
						<span class="align-middle">Website Pages</span>
						<?php if (isActiveGroup('pages', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="pages"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('pages', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('add-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-page.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('add-page.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Add New Page</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('contact-view.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='contact-view.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('contact-view.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Contact Us</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('about-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='about-page.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('about-page.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">About Us</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('careers.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='careers.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('careers.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Careers</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('affiliate-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='affiliate-page.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('affiliate-page.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Affiliate Program</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('policy-edit.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='policy-edit.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('policy-edit.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Policy Management</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('faq-view.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='faq-view.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('faq-view.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">FAQ</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('faq-view-category.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='faq-view-category.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('faq-view-category.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">FAQ Category</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('help-center.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='help-center.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('help-center.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Help Center</span>
							</a>
						</li>
					</ul>
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#watermark" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('watermark', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="droplet"></i>
						<span class="align-middle">Watermark</span>
						<?php if (isActiveGroup('watermark', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="watermark"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('watermark', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li
							class="sidebar-item <?php echo isActivePage('add-watermark.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-watermark.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('add-watermark.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Add Watermark</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('watermarks.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='watermarks.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('watermarks.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">All Watermarks</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('deactivated-watermarks.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='deactivated-watermarks.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('deactivated-watermarks.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Deactivated</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<!-- Marketing -->
			<?php if (shouldDisplay($menu_access['marketing'], $user_role)): ?>
				<li class="sidebar-header">
					Marketing
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#coupons" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('coupons', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="tag"></i>
						<span class="align-middle">Coupons</span>
						<?php if (isActiveGroup('coupons', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="coupons"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('coupons', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li
							class="sidebar-item <?php echo isActivePage('add-coupon.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-coupon.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('add-coupon.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Add New Coupon</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('coupons.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='coupons.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('coupons.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">All Coupons</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('expired-coupons.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='expired-coupons.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('expired-coupons.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Expired Coupons</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<!-- Customers -->
			<?php if (shouldDisplay($menu_access['customers'], $user_role)): ?>
				<li class="sidebar-header">
					Customers
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#customers" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('customers', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="users"></i>
						<span class="align-middle">Customers</span>
						<?php if (isActiveGroup('customers', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="customers"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('customers', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li
							class="sidebar-item <?php echo isActivePage('customers.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='customers.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('customers.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Customers List</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('customer-images.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='customer-images.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('customer-images.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Customer Default Images</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<!-- Bulk Order Inquiry -->
			<?php if (shouldDisplay($menu_access['bulk_orders'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#inquiry" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('inquiry', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="inbox"></i>
						<span class="align-middle">Bulk Order Inquiry</span>
						<?php if (isActiveGroup('inquiry', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="inquiry"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('inquiry', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li
							class="sidebar-item <?php echo isActivePage('new-inquiry.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='new-inquiry.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('new-inquiry.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">New Inquiry</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('all-inquiry.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='all-inquiry.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('all-inquiry.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">All Inquiry</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('pending-inquiry.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='pending-inquiry.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('pending-inquiry.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Pending Inquiry</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('completed-inquiry.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='completed-inquiry.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('completed-inquiry.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Completed Inquiry</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<!-- Blog Management -->
			<?php if (shouldDisplay($menu_access['blog'], $user_role)): ?>
				<li class="sidebar-header">
					Blog Management
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#blog" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('blog', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="edit"></i>
						<span class="align-middle">Blog</span>
						<?php if (isActiveGroup('blog', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="blog"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('blog', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li
							class="sidebar-item <?php echo isActivePage('blog-categories.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='blog-categories.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('blog-categories.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Blog Category</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('add-blog-category.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-blog-category.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('add-blog-category.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Add Blog Category</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('add-blog-post.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-blog-post.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('add-blog-post.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Add New Post</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('blog-posts.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='blog-posts.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('blog-posts.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">All Posts</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<!-- Settings -->
			<?php if (shouldDisplay($menu_access['settings'], $user_role)): ?>
				<li class="sidebar-header">
					System Settings
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#email" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('email', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="mail"></i>
						<span class="align-middle">Email Settings</span>
						<?php if (isActiveGroup('email', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="email"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('email', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li
							class="sidebar-item <?php echo isActivePage('email-templates.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='email-templates.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('email-templates.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Email Template</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('email-configurations.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='email-configurations.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('email-configurations.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Email Configurations</span>
							</a>
						</li>
					</ul>
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#social" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('social', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="share-2"></i>
						<span class="align-middle">Social Media</span>
						<?php if (isActiveGroup('social', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="social"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('social', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li
							class="sidebar-item <?php echo isActivePage('add-social-link.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-social-link.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('add-social-link.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Add Social Links</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('social-links.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='social-links.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('social-links.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">All Social Links</span>
							</a>
						</li>
					</ul>
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#seo" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('seo', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="search"></i>
						<span class="align-middle">SEO Tools</span>
						<?php if (isActiveGroup('seo', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="seo"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('seo', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li
							class="sidebar-item <?php echo isActivePage('google-analytics.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='google-analytics.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('google-analytics.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Google Analytics</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('facebook-pixels.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='facebook-pixels.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('facebook-pixels.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Facebook Pixels</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('meta-keywords.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='meta-keywords.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('meta-keywords.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Meta Keywords</span>
							</a>
						</li>
					</ul>
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#general" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('general', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="settings"></i>
						<span class="align-middle">General Settings</span>
						<?php if (isActiveGroup('general', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="general"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('general', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li
							class="sidebar-item <?php echo isActivePage('logo-management.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='logo-management.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('logo-management.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Logo</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('loader-settings.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='loader-settings.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('loader-settings.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Loader</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('footer-management.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='footer-management.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('footer-management.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Footer</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('login-background.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='login-background.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('login-background.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Login Background</span>
							</a>
						</li>
					</ul>
				</li>

				<!-- Staff Management -->
				<li class="sidebar-item">
					<a data-bs-target="#staff-mgmt" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('staff_mgmt', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="user-check"></i>
						<span class="align-middle">Staff Management</span>
						<?php if (isActiveGroup('staff_mgmt', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="staff-mgmt"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('staff_mgmt', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li
							class="sidebar-item <?php echo isActivePage('add-staff.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-staff.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('add-staff.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Add New Staff</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('staff.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='staff.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('staff.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">All Staff</span>
							</a>
						</li>
						<li
							class="sidebar-item <?php echo isActivePage('deactivated-staff.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='deactivated-staff.php'>
								<i class="align-middle"
									data-feather="<?php echo isActivePage('deactivated-staff.php', $current_page) ? 'circle' : 'circle'; ?>"></i>
								<span class="align-middle">Deactivated Staff</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<!-- Analytics -->
			<?php if (shouldDisplay($menu_access['analytics'], $user_role)): ?>
				<li class="sidebar-header">
					Analytics & Reports
				</li>

				<li class="sidebar-item <?php echo isActivePage('sales-report.php', $current_page) ? 'active' : ''; ?>">
					<a class='sidebar-link' href='sales-report.php'>
						<i class="align-middle" data-feather="bar-chart-2"></i>
						<span class="align-middle">Sales Report</span>
						<?php if (isActivePage('sales-report.php', $current_page)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
				</li>
			<?php endif; ?>
		</ul>

		<!-- Quick Stats -->
		<div class="sidebar-cta">
			<div class="sidebar-cta-content">
				<strong class="d-inline-block mb-2">Today's Stats</strong>
				<div class="mb-2 text-sm">
					<small>Orders: <strong class="text-success">15</strong></small>
				</div>
				<div class="mb-2 text-sm">
					<small>Revenue: <strong class="text-success">₹25,430</strong></small>
				</div>
				<div class="mb-3 text-sm">
					<small>Visitors: <strong class="text-success">1,234</strong></small>
				</div>
				<div class="d-grid">
					<a href="dashboard.php" class="btn btn-outline-primary btn-sm">View Dashboard</a>
				</div>
			</div>
		</div>
	</div>
</nav>

<style>
	/* Active state styles */
	.sidebar-item.active>.sidebar-link {
		background-color: rgba(var(--bs-primary-rgb), 0.1);
		color: var(--bs-primary);
		border-right: 3px solid var(--bs-primary);
	}

	.sidebar-item.active>.sidebar-link i {
		color: var(--bs-primary);
	}

	.sidebar-dropdown .sidebar-item.active>.sidebar-link {
		background-color: rgba(var(--bs-primary-rgb), 0.1);
		color: var(--bs-primary);
		border-right: 3px solid var(--bs-primary);
	}

	.sidebar-dropdown .sidebar-item.active>.sidebar-link i {
		color: var(--bs-primary);
	}

	/* Active badge indicator */
	.sidebar-badge {
		position: absolute;
		right: 15px;
		top: 50%;
		transform: translateY(-50%);
		color: var(--bs-primary);
		font-size: 12px;
	}

	/* Collapsible menu arrow */
	.sidebar-link[data-bs-toggle="collapse"]::after {
		content: "";
		position: absolute;
		right: 15px;
		top: 50%;
		transform: translateY(-50%);
		border: solid;
		border-width: 0 2px 2px 0;
		display: inline-block;
		padding: 3px;
		transform: translateY(-50%) rotate(45deg);
		transition: transform 0.3s ease;
	}

	.sidebar-link[data-bs-toggle="collapse"]:not(.collapsed)::after {
		transform: translateY(-50%) rotate(-135deg);
	}

	/* Hover effects */
	.sidebar-item>.sidebar-link:hover {
		background-color: rgba(var(--bs-primary-rgb), 0.05);
	}
</style>

<script>
	// Add active class management
	document.addEventListener('DOMContentLoaded', function () {
		// Store active state in session storage
		const activeMenu = sessionStorage.getItem('activeMenu');
		if (activeMenu) {
			const menuItem = document.querySelector(`[href="${activeMenu}"]`);
			if (menuItem) {
				menuItem.closest('.sidebar-item').classList.add('active');
			}
		}

		// Update active menu on click
		document.querySelectorAll('.sidebar-link').forEach(link => {
			link.addEventListener('click', function (e) {
				if (this.getAttribute('href') && !this.hasAttribute('data-bs-toggle')) {
					sessionStorage.setItem('activeMenu', this.getAttribute('href'));
				}
			});
		});

		// Auto expand parent menu if child is active
		document.querySelectorAll('.sidebar-item.active').forEach(item => {
			const parentMenu = item.closest('.sidebar-dropdown');
			if (parentMenu) {	
				const toggleButton = document.querySelector(`[data-bs-target="#${parentMenu.id}"]`);
				if (toggleButton) {
					toggleButton.classList.remove('collapsed');
					toggleButton.setAttribute('aria-expanded', 'true');
					parentMenu.classList.add('show');
				}
			}
		});
	});
</script>