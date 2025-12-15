<?php
// Uncomment and configure this section for authentication
/*
$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

// Redirect to index.php if user is not logged in or session timed out
if (!$auth->isLoggedIn() || !$auth->checkSessionTimeout()) {
    // Clear session (delegate to controller if available)
    if (method_exists($auth, 'logout')) {
        $auth->logout(false); // pass false to avoid double redirect inside controller
    } else {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    header('Location: index.php');
    exit();
}
*/
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
					<img src="img/avatars/avatar.jpg" class="avatar img-fluid rounded me-1" alt="Admin User" />
				</div>
				<div class="flex-grow-1 ps-2">
					<a class="sidebar-user-title dropdown-toggle" href="#" data-bs-toggle="dropdown">
						Admin User
					</a>
					<div class="dropdown-menu dropdown-menu-start">
						<a class='dropdown-item' href='admin-profile.php'><i class="align-middle me-1"
								data-feather="user"></i> Profile</a>
						<a class="dropdown-item" href="#"><i class="align-middle me-1" data-feather="settings"></i>
							Settings</a>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item logout" href="logout.php">
							<i class="align-middle me-1" data-feather="log-out"></i> Log out
						</a>
					</div>
					<div class="sidebar-user-subtitle">Administrator</div>
				</div>
			</div>
		</div>

		<ul class="sidebar-nav">
			<!-- Dashboard -->
			<li class="sidebar-item">
				<a class='sidebar-link' href='dashboard.php'>
					<i class="align-middle" data-feather="home"></i>
					<span class="align-middle">Dashboard</span>
				</a>
			</li>


			<!-- Home Page Management -->
			<li class="sidebar-header">
				Home Page
			</li>

			<!-- In side-navbar.php -->
			<!-- In side-navbar.php -->
			<li class="sidebar-item">
				<a data-bs-target="#banners" data-bs-toggle="collapse" class="sidebar-link">
					<i class="align-middle" data-feather="image"></i>
					<span class="align-middle">Banners</span>
				</a>
				<ul id="banners" class="sidebar-dropdown list-unstyled collapse show" data-bs-parent="#sidebar">
					<li class="sidebar-item">
						<a class='sidebar-link' href='banner-list.php'>All Banners</a>
					</li>
					<li class="sidebar-item">
						<a class='sidebar-link' href='add-banner.php'>Add Banner</a>
					</li>
					<li class="sidebar-item">
						<a class='sidebar-link' href='banner-layouts.php'>Banner Layouts</a>
					</li>
					<li class="sidebar-item">
						<a class='sidebar-link' href='add-banner-layout.php'>Add Layout</a>
					</li>
					<li class="sidebar-item">
						<a class='sidebar-link' href='assign-banners.php'>Assign Banners</a>
					</li>
				</ul>
			</li>

			<li class="sidebar-item">
				<a class='sidebar-link' href='staff.php'>
					<i class="align-middle" data-feather="users"></i>
					<span class="align-middle">Staff</span>
				</a>
			</li>

			<li class="sidebar-item">
				<a class='sidebar-link' href='logo-management.php'>
					<i class="align-middle" data-feather="flag"></i>
					<span class="align-middle">Logo Management</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class='sidebar-link' href='blog-view.php'>
					<i class="align-middle" data-feather="book"></i>
					<span class="align-middle">Blog</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class='sidebar-link' href='policy-edit.php'>
					<i class="align-middle" data-feather="book"></i>
					<span class="align-middle">Policies</span>
				</a>
			</li>
			<li class="sidebar-item">
				<a class='sidebar-link' href='contact-view.php'>
					<i class="align-middle" data-feather="book"></i>
					<span class="align-middle">Contact</span>
				</a>
			</li>

			<!-- Catalog Management -->
			<li class="sidebar-header">
				Catalog
			</li>

			<li class="sidebar-item">
				<a class='sidebar-link' href='view-categories.php'>
					<i class="align-middle" data-feather="grid"></i>
					<span class="align-middle">Categories</span>
				</a>
			</li>

			<li class="sidebar-item">
				<a data-bs-target="#products" data-bs-toggle="collapse" class="sidebar-link">
					<i class="align-middle" data-feather="package"></i>
					<span class="align-middle">Products</span>
				</a>
				<ul id="products" class="sidebar-dropdown list-unstyled collapse show" data-bs-parent="#sidebar">
					<li class="sidebar-item">
						<a class='sidebar-link' href='view-products.php'>All Products</a>
					</li>
					<li class="sidebar-item">
						<a class='sidebar-link' href='product.php'>Add Product</a>
					</li>
				</ul>
			</li>

			<!-- Orders Management -->
			<li class="sidebar-header">
				Orders
			</li>

			<li class="sidebar-item">
				<a class='sidebar-link' href='all-orders.php'>
					<i class="align-middle" data-feather="shopping-cart"></i>
					<span class="align-middle">All Orders</span>
					<span class="sidebar-badge badge bg-primary">120</span>
				</a>
			</li>

			<li class="sidebar-item">
				<a class='sidebar-link' href='pending-orders.php'>
					<i class="align-middle" data-feather="clock"></i>
					<span class="align-middle">Pending Orders</span>
					<span class="sidebar-badge badge bg-warning">8</span>
				</a>
			</li>

			<!-- Customers -->
			<li class="sidebar-header">
				Customers
			</li>

			<li class="sidebar-item">
				<a class='sidebar-link' href='customer-list.php'>
					<i class="align-middle" data-feather="users"></i>
					<span class="align-middle">Customers</span>
					<span class="sidebar-badge badge bg-primary">1.2K</span>
				</a>
			</li>

			<!-- Analytics -->
			<li class="sidebar-header">
				Analytics
			</li>

			<li class="sidebar-item">
				<a class='sidebar-link' href='sales-report.php'>
					<i class="align-middle" data-feather="bar-chart-2"></i>
					<span class="align-middle">Sales Report</span>
				</a>
			</li>

			<!-- Marketing -->
			<li class="sidebar-header">
				Marketing
			</li>

			<li class="sidebar-item">
				<a class='sidebar-link' href='coupons.php'>
					<i class="align-middle" data-feather="percent"></i>
					<span class="align-middle">Coupons</span>
				</a>
			</li>

			<!-- Settings -->
			<li class="sidebar-header">
				Settings
			</li>

			<li class="sidebar-item">
				<a class='sidebar-link' href='admin-profile.php'>
					<i class="align-middle" data-feather="user"></i>
					<span class="align-middle">Admin Profile</span>
				</a>
			</li>

			<li class="sidebar-item">
				<a class='sidebar-link' href='settings.php'>
					<i class="align-middle" data-feather="settings"></i>
					<span class="align-middle">System Settings</span>
				</a>
			</li>
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