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
						<a class="dropdown-item logout" href="<?= BASE_URL ?>/api/auth/logout.php">
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
				<li class="sidebar-item">
					<a class='sidebar-link' href='dashboard.php'>
						<i class="align-middle" data-feather="home"></i>
						<span class="align-middle">Dashboard</span>
					</a>
				</li>
			<?php endif; ?>

			<!-- Home Page Management -->
			<?php if (shouldDisplay($menu_access['home_page'], $user_role)): ?>
				<li class="sidebar-header">
					Home Page Management
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#home-settings" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="layout"></i>
						<span class="align-middle">Home Page Settings</span>
					</a>
					<ul id="home-settings" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='view-banner.php'>Slider</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='add-banner.php'>Add Sliders</a>
						</li>
						<!-- <li class="sidebar-item">
							<a class='sidebar-link' href='services.php'>Services</a>
						</li> -->
					</ul>
				</li>


				<!-- <li class="sidebar-item">
					<a data-bs-target="#banners" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="image"></i>
						<span class="align-middle">Banners</span>
					</a>
					<ul id="banners" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='add-banner.php'>Add New Banner</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='view-banner.php'>All Banners</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='deactivated-banners.php'>Deactivated Banners</a>
						</li>
					</ul>
				</li> -->

				<!-- <li class="sidebar-item">
					<a data-bs-target="#carousels" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="sliders"></i>
						<span class="align-middle">Carousels</span>
					</a>
					<ul id="carousels" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='add-carousel.php'>Add New Carousel</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='carousels.php'>All Carousels</a>
						</li>
					</ul>
				</li> -->

				<!-- <li class="sidebar-item">
					<a data-bs-target="#master-modules" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="grid"></i>
						<span class="align-middle">Master Modules</span>
					</a>
					<ul id="master-modules" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='home-category-modules.php'>Home Page Categories</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='sponsored-modules.php'>Sponsored Modules</a>
						</li>
					</ul>
				</li> -->
			<?php endif; ?>

			<!-- Products Management -->
			<?php if (shouldDisplay($menu_access['products'], $user_role)): ?>
				<li class="sidebar-header">
					Products Management
				</li>

				<!-- Categories Management -->
				<?php if (shouldDisplay($menu_access['categories'], $user_role)): ?>
					<li class="sidebar-item">
						<a data-bs-target="#categories" data-bs-toggle="collapse" class="sidebar-link">
							<i class="align-middle" data-feather="layers"></i>
							<span class="align-middle">Categories</span>
						</a>
						<ul id="categories" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
							<li class="sidebar-item">
								<a class='sidebar-link' href='view-categories.php'>All Categories</a>
							</li>
							<li class="sidebar-item">
								<a class='sidebar-link' href='add-category.php'>Add Category</a>
							</li>
							<!-- <li class="sidebar-item">
								<a class='sidebar-link' href='sub-sub-categories.php'>Sub Sub Category</a>
							</li>
							<li class="sidebar-item">
								<a class='sidebar-link' href='header-category-menu.php'>Header Category Menu</a>
							</li>
							<li class="sidebar-item">
								<a class='sidebar-link' href='home-page-categories.php'>Home Page Categories</a>
							</li> -->
						</ul>
					</li>
				<?php endif; ?>

				<li class="sidebar-item">
					<a data-bs-target="#products" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="package"></i>
						<span class="align-middle">Products</span>
					</a>
					<ul id="products" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='product.php'>Add New Product</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='view-products.php'>All Products</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='top-selection-products.php'>Make Top Selection</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='besteseller-prodcuts.php'>Make Bestseller</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='deactive-products.php'>Deactivated Products</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='single-product-version.php'>Single Product Version</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='product-filters.php'>Product Filters</a>
						</li>
						
						
					</ul>
				</li>


				<?php if (shouldDisplay($menu_access['products'], $user_role)): ?>
					<li class="sidebar-item">
						<a data-bs-target="#reviews" data-bs-toggle="collapse" class="sidebar-link">
							<i class="align-middle" data-feather="message-square"></i>
							<span class="align-middle">Products Review</span>
						</a>
						<ul id="reviews" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
							<li class="sidebar-item">
								<a class='sidebar-link' href='add-review.php'>Add Review</a>
							</li>
							<li class="sidebar-item">
								<a class='sidebar-link' href='reviews.php'>All Reviews</a>
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
					<a data-bs-target="#pages" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="file-text"></i>
						<span class="align-middle">Website Pages</span>
					</a>
					<ul id="pages" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='add-page.php'>Add New Page</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='contact-view.php'>Contact Us</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='about-page.php'>About Us</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='careers.php'>Careers</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='affiliate-page.php'>Affiliate Program</a>
						</li>
						<!-- <li class="sidebar-item">
							<a class='sidebar-link' href='terms-page.php'>Terms & Conditions</a>
						</li> -->
						<li class="sidebar-item">
							<a class='sidebar-link' href='policy-edit.php'>Policy Management</a>
						</li>
						<!-- <li class="sidebar-item">
							<a class='sidebar-link' href='shipping-page.php'>Shipping Policy</a>
						</li> -->
						<!-- <li class="sidebar-item">
							<a class='sidebar-link' href='return-page.php'>Return & Refund</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='terms-of-use-page.php'>Terms of Use</a>
						</li> -->
						<li class="sidebar-item">
							<a class='sidebar-link' href='faq-view.php'>FAQ</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='faq-view-category.php'>FAQ Category</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='help-center.php'>Help Center</a>
						</li>
					</ul>
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#watermark" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="droplet"></i>
						<span class="align-middle">Watermark</span>
					</a>
					<ul id="watermark" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='add-watermark.php'>Add Watermark</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='watermarks.php'>All Watermarks</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='deactivated-watermarks.php'>Deactivated</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<!-- Orders Management -->
			<?php if (shouldDisplay($menu_access['orders'], $user_role)): ?>
				<li class="sidebar-header">
					Orders Management
				</li>


				<li class="sidebar-item">
					<a data-bs-target="#orders" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="shopping-cart"></i>
						<span class="align-middle">Orders</span>
					</a>
					<ul id="orders" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='orders.php'>All Orders</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='pending-orders.php'>Pending Orders</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='processing-orders.php'>Processing Orders</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='delivered-orders.php'>Delivered Orders</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='completed-orders.php'>Completed Orders</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='declined-orders.php'>Declined Orders</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='refund-orders.php'>Refund Orders</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='total-sold-orders.php'>Total Sold Orders</a>
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
					<a data-bs-target="#coupons" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="tag"></i>
						<span class="align-middle">Coupons</span>
					</a>
					<ul id="coupons" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='add-coupon.php'>Add New Coupon</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='coupons.php'>All Coupons</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='expired-coupons.php'>Expired Coupons</a>
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
					<a data-bs-target="#customers" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="users"></i>
						<span class="align-middle">Customers</span>
					</a>
					<ul id="customers" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='customers.php'>Customers List</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='customer-images.php'>Customer Default Images</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<!-- Bulk Order Inquiry -->
			<?php if (shouldDisplay($menu_access['bulk_orders'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#inquiry" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="inbox"></i>
						<span class="align-middle">Bulk Order Inquiry</span>
					</a>
					<ul id="inquiry" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='new-inquiry.php'>New Inquiry</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='all-inquiry.php'>All Inquiry</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='pending-inquiry.php'>Pending Inquiry</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='completed-inquiry.php'>Completed Inquiry</a>
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
					<a data-bs-target="#blog" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="edit"></i>
						<span class="align-middle">Blog</span>
					</a>
					<ul id="blog" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='blog-categories.php'>
								<i class="align-middle" data-feather="edit-3"></i>
								<span class="align-middle">Blog Category</span>
							</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='add-blog-category.php'>
								<i class="align-middle" data-feather="eye"></i>
								<span class="align-middle">Add Blog Category</span>
							</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='add-blog-post.php'>
								<i class="align-middle" data-feather="plus"></i>
								<span class="align-middle">Add New Post</span>
							</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='blog-posts.php'>
								<i class="align-middle" data-feather="list"></i>
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

				<!-- <li class="sidebar-item">
				<a data-bs-target="#payment" data-bs-toggle="collapse" class="sidebar-link">
					<i class="align-middle" data-feather="credit-card"></i>
					<span class="align-middle">Payment Settings</span>
				</a>
				<ul id="payment" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
					<li class="sidebar-item">
						<a class='sidebar-link' href='payment-information.php'>Payment Information</a>
					</li>
					<li class="sidebar-item">
						<a class='sidebar-link' href='payment-gateways.php'>Payment Gateways</a>
					</li>
					<li class="sidebar-item">
						<a class='sidebar-link' href='cod-settings.php'>Cash on Delivery</a>
					</li>
				</ul>
			</li> -->

				<li class="sidebar-item">
					<a data-bs-target="#email" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="mail"></i>
						<span class="align-middle">Email Settings</span>
					</a>
					<ul id="email" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='email-templates.php'>Email Template</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='email-configurations.php'>Email Configurations</a>
						</li>
					</ul>
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#social" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="share-2"></i>
						<span class="align-middle">Social Media</span>
					</a>
					<ul id="social" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='add-social-link.php'>Add Social Links</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='social-links.php'>All Social Links</a>
						</li>
					</ul>
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#seo" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="search"></i>
						<span class="align-middle">SEO Tools</span>
					</a>
					<ul id="seo" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='google-analytics.php'>Google Analytics</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='facebook-pixels.php'>Facebook Pixels</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='meta-keywords.php'>Meta Keywords</a>
						</li>
					</ul>
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#general" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="settings"></i>
						<span class="align-middle">General Settings</span>
					</a>
					<ul id="general" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
						<li class="sidebar-item">
							<a class='sidebar-link' href='logo-management.php'>Logo</a>
						</li>
						<!-- <li class="sidebar-item">
							<a class='sidebar-link' href='favicon-settings.php'>Favicon</a>
						</li> -->
						<li class="sidebar-item">
							<a class='sidebar-link' href='loader-settings.php'>Loader</a>
						</li>
						<!-- <li class="sidebar-item">
						<a class='sidebar-link' href='website-contents.php'>Website Contents</a>
					</li> -->
						<li class="sidebar-item">
							<a class='sidebar-link' href='footer-management.php'>Footer</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='login-background.php'>Login Background</a>
						</li>
						<!-- <li class="sidebar-item">
						<a class='sidebar-link' href='error-background.php'>Error Background</a>
					</li>
					<li class="sidebar-item">
						<a class='sidebar-link' href='maintenance-mode.php'>Website Maintenance</a>
					</li> -->
					</ul>
				</li>

				<!-- Staff Management -->
				<li class="sidebar-item">
					<!-- <a data-bs-target="#staff" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="user-check"></i>
						<span class="align-middle">Staff Management</span>
					</a> -->
					<!-- <ul id="staff" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar"> -->
					<!-- <li class="sidebar-item">
							<a data-bs-target="#roles" data-bs-toggle="collapse" class="sidebar-link">
								Manage Roles
							</a>
							<ul id="roles" class="sidebar-dropdown list-unstyled collapse">
								<li class="sidebar-item">
									<a class='sidebar-link' href='add-role.php'>Add New Role</a>
								</li>
								<li class="sidebar-item">
									<a class='sidebar-link' href='roles.php'>All Roles</a>
								</li>
							</ul>
						</li> -->
				<li class="sidebar-item">
					<a data-bs-target="#staff-mgmt" data-bs-toggle="collapse" class="sidebar-link">
						<i class="align-middle" data-feather="user-check"></i>
						<span class="align-middle">Staff Management</span>
					</a>
					<ul id="staff-mgmt" class="sidebar-dropdown list-unstyled collapse">
						<li class="sidebar-item">
							<a class='sidebar-link' href='add-staff.php'>Add New Staff</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='staff.php'>All Staff</a>
						</li>
						<li class="sidebar-item">
							<a class='sidebar-link' href='deactivated-staff.php'>Deactivated Staff</a>
						</li>
					</ul>
				</li>
				<!-- </ul> -->
				</li>
			<?php endif; ?>

			<!-- Analytics -->
			<?php if (shouldDisplay($menu_access['analytics'], $user_role)): ?>
				<li class="sidebar-header">
					Analytics & Reports
				</li>

				<li class="sidebar-item">
					<a class='sidebar-link' href='sales-report.php'>
						<i class="align-middle" data-feather="bar-chart-2"></i>
						<span class="align-middle">Sales Report</span>
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