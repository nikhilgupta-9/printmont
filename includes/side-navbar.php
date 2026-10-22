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
		// The login page is index.php in the backend root, not one level up.
		if (!headers_sent()) {
			header('Location: index.php');
		} else {
			echo '<script>window.location.href="index.php";</script>';
		}
		exit();
	}

	// Get current user info
	$current_user = $auth->getCurrentUser();
	$user_role = (!empty($current_user['role'])) ? $current_user['role'] : 'admin';

} catch (Exception $e) {
	error_log("Authentication error: " . $e->getMessage());
	if (!headers_sent()) {
		header('Location: index.php');
	} else {
		echo '<script>window.location.href="index.php";</script>';
	}
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
	'header_menu' => ['admin', 'manager'],
	'banners' => ['admin', 'manager'],
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
	'seo' => ['admin', 'manager'],
	'carousels' => ['admin', 'manager'],
	'home_products' => ['admin', 'manager'],
	'roles' => ['admin'],
	'gift_finder' => ['admin', 'manager'],
	'master_modules' => ['admin'],
	'contact_inquiries' => ['admin', 'manager', 'staff'],
	'cod' => ['admin'],
	'product_filters' => ['admin', 'manager'],
	'careers' => ['admin', 'manager'],
	'notifications' => ['all']
];

// Get current page URL
$current_page = basename($_SERVER['PHP_SELF']);

// Define page groups for active state
$page_groups = [
	'dashboard' => ['dashboard.php'],
	'notifications' => ['notifications.php', 'customer-notifications.php'],

	// Banner Management (Home Pages Banners)
	'banners' => [
		'view-banner.php',
		'add-banner.php',
		'edit-banner.php',
		'add-banner-carousel.php',
		'edit-banner-carousel.php',
		'deactivated-banners.php',
		'banner-layouts.php',
		'banner-carousels.php'
	],

	// Banner Management (Other Pages: Category, About, Bulk Orders, ...)
	'page_banners' => [
		'add-page-banner.php',
		'page-banners.php'
	],

	// Home Page Management
	'home_settings' => [
		'home-layout-manager.php',
		'product-layout-manager.php',
		'services.php'
	],

	// Home Sliders
	'home_sliders' => [
		'slider.php',
		'add-slider.php',
		'edit-slider.php',
		'home-sliders.php',
		'add-home-slider.php',
		'edit-home-slider.php'
	],

	// Home Services
	'home_services' => [
		'home-services.php',
		'add-home-service.php'
	],

	// Banner Carousels
	'banner_carousels' => [
		'banner-carousels.php',
		'add-banner-carousel.php',
		'edit-banner-carousel.php'
	],

	// Categories Management
	'categories' => [
		'view-categories.php',
		'add-main-category.php',
		'add-sub-category.php',
		'add-sub-sub-category.php',
		'edit-category.php',
		'home-page-categories.php'
	],

	// Header Category Menu
	'header_menu' => [
		'header-settings.php',
		'header-menu-list.php',
		'add-top-icon.php',
		'add-menu-page.php',
		'header-menu-design.php'
	],

	// Products Management
	'products' => [
		'product.php',
		'edit-product.php',
		'view-products.php',
		'top-selection-products.php',
		'besteseller-prodcuts.php',
		'deactive-products.php',
		'single-product-version.php',
		'category-banner-texts.php',
		'customization-options.php',
		'product-sales.php',
		'gift-vouchers.php'
	],

	// Product Filters
	'product_filters' => [
		'product-filters.php'
	],

	// Products Review
	'reviews' => [
		'add-review.php',
		'reviews.php',
		'edit-review.php',
		'view-review.php'
	],

	// Manage Carousels
	'carousels' => [
		'carousels.php',
		'add-carousel.php',
		'edit-carousel.php'
	],

	// Home Page Products
	'home_products' => [
		'home-products.php',
		'add-home-product.php',
		'edit-home-product.php',
		'deactive-home-products.php'
	],

	// Website Pages
	'pages' => [
		'website-pages.php',
		'about-us-page.php',
		'faq-page.php',
		'help-center-page.php',
		'security-page.php',
		'affiliate-page.php',
		'become-a-seller-page.php',
		'business-solutions-page.php',
		'lead-inquiries.php',
		'bulk-orders-page.php',
		'franchise-page.php',
		'advertising-page.php',
		'add-page.php',
		'contact-view.php',
		'policy-management.php',
		'policy-edit.php',
		'faq-view-category.php',
		'edit-faq.php',
		// Superseded screens, kept so a bookmark still expands the group.
		'about-page.php',
		'edit-about-page.php',
		'faq-view.php',
		'help-center.php',
		'security-management.php'
	],

	// Careers Management
	'careers' => [
		'careers.php',
		'add-career.php',
		'edit-career.php',
		'career-applications.php'
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
		'total-sold-orders.php',
		'edit-order.php',
		'view-order.php'
	],

	// Coupons
	'coupons' => [
		'add-coupon.php',
		'coupons.php',
		'edit-coupon.php',
		'expired-coupons.php'
	],

	// Customers
	'customers' => [
		'customers.php',
		'add-customer.php',
		'edit-customer.php',
		'view-customer.php',
		'customer-images.php'
	],

	// Coins & Wallets
	'coins_wallets' => [
		'coins-manager.php',
		'wallets-manager.php'
	],

	// Bulk Order Inquiry
	'inquiry' => [
		'new-inquiry.php',
		'all-inquiry.php',
		'pending-inquiry.php',
		'completed-inquiry.php',
		'total-sold-inquiry.php',
		'bulk-inquiries.php'
	],

	// Contact Inquiries
	'contact_inquiries' => [
		'contact-inquiries.php'
	],

	// Blog Management
	'blog' => [
		'blog-page.php',
		'blog-posts.php',
		'add-blog-post.php',
		'edit-blog-post.php',
		'blog-categories.php',
		'add-blog-category.php',
		'edit-blog-category.php',
		'blog-tags.php',
		'blog-comments.php'
	],

	// Email Settings
	'email' => [
		'email-templates.php',
		'email-configurations.php',
		'edit-email-template.php'
	],

	// Social Media
	'social' => [
		'add-social-link.php',
		'edit-social-link.php',
		'social-links.php'
	],

	// SEO Tools
	'seo' => [
		'google-analytics.php',
		'facebook-pixels.php',
		'meta-keywords.php',
		'sitemap-management.php'
	],

	// Payment Settings
	'payment_settings' => [
		'payment-settings.php'
	],

	// COD Settings
	'cod_settings' => [
		'cod-settings.php'
	],

	// Roles Management
	'roles' => [
		'roles.php',
		'add-role.php',
		'edit-role.php'
	],

	// General Settings
	'general' => [
		'logo-management.php',
		'edit-logo.php',
		'loader-settings.php',
		'footer-management.php',
		'quick-links-manager.php',
		'footer-company-info.php',
		'footer-links.php',
		'footer-sections.php',
		'login-background.php',
		'favicon-settings.php',
		'website-content.php',
		'error-background.php',
		'maintenance-settings.php',
		'general-settings.php'
	],

	// Staff Management
	'staff_mgmt' => [
		'add-staff.php',
		'edit-staff.php',
		'staff.php',
		'deactivated-staff.php'
	],

	// Gift Finder
	'gift_finder' => [
		'gift-finder.php'
	],

	// Master Modules
	'master_modules' => [
		'master-modules.php'
	],

	// Database Migrations
	'migrations' => [
		'migrations.php'
	],

	// Product Filters (standalone)
	'product_filters_setting' => [
		'product-filters.php'
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
<style>
/* ==========================================================================
   PRINTMONT EXECUTIVE ADMIN SIDEBAR - THEME UPGRADE
   Colors: Deep Executive Navy (#03172c, #072545), Brand Blue (#00529b, #0284c7), Accent Cyan (#38bdf8)
   ========================================================================== */

/* Fixed Sidebar & Layout - Stable Sidebar & Independently Scrollable Right Body */
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
		transition: margin-left .3s cubic-bezier(0.4, 0, 0.2, 1), left .3s cubic-bezier(0.4, 0, 0.2, 1) !important;
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
		box-shadow: 0 0 35px rgba(0, 0, 0, 0.7) !important;
	}

	.main {
		margin-left: 0 !important;
		width: 100% !important;
		min-width: 0 !important;
	}

	.sidebar-mobile-backdrop {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(3, 23, 44, 0.65);
		backdrop-filter: blur(4px);
		z-index: 1040;
		opacity: 0;
		visibility: hidden;
		transition: opacity .3s ease, visibility .3s ease;
	}

	.sidebar-mobile-backdrop.show {
		opacity: 1 !important;
		visibility: visible !important;
	}
}

/* Sidebar Theme Aesthetics */
.sidebar,
body[data-theme=default] .sidebar,
body[data-theme=dark] .sidebar {
	background: linear-gradient(180deg, #03172c 0%, #062343 45%, #03162a 100%) !important;
	background-image: radial-gradient(circle at 10% 0%, rgba(14, 165, 233, 0.12) 0%, transparent 40%), radial-gradient(circle at 90% 100%, rgba(11, 83, 161, 0.15) 0%, transparent 45%), linear-gradient(180deg, #03172c 0%, #062343 45%, #03162a 100%) !important;
	border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
	box-shadow: 4px 0 24px rgba(3, 23, 44, 0.35) !important;
}

.sidebar-content,
body[data-theme=default] .sidebar-content,
body[data-theme=dark] .sidebar-content {
	background: transparent !important;
	display: flex !important;
	flex-direction: column !important;
	height: 100vh !important;
	max-height: 100vh !important;
	overflow-y: auto !important;
	overflow-x: hidden !important;
	-webkit-overflow-scrolling: touch !important;
	scrollbar-width: thin;
	scrollbar-color: rgba(56, 189, 248, 0.25) transparent;
}

.sidebar-content::-webkit-scrollbar {
	width: 5px;
}
.sidebar-content::-webkit-scrollbar-track {
	background: transparent;
}
.sidebar-content::-webkit-scrollbar-thumb {
	background: rgba(56, 189, 248, 0.2);
	border-radius: 4px;
}
.sidebar-content::-webkit-scrollbar-thumb:hover {
	background: rgba(56, 189, 248, 0.45);
}

.sidebar .simplebar-wrapper,
.sidebar .simplebar-mask,
.sidebar .simplebar-content-wrapper {
	height: 100vh !important;
	max-height: 100vh !important;
	overflow-y: auto !important;
	overflow-x: hidden !important;
	-webkit-overflow-scrolling: touch !important;
}

.sidebar .simplebar-content {
	display: flex !important;
	flex-direction: column !important;
	min-height: 100% !important;
	height: auto !important;
	padding-bottom: 4rem !important;
}

/* Brand Header */
.sidebar-brand {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	padding: 1.15rem 1.25rem !important;
	background: rgba(255, 255, 255, 0.03) !important;
	border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
	text-decoration: none !important;
	min-height: 64px;
}

.sidebar-brand-logo {
	height: 32px;
	width: auto;
	object-fit: contain;
	filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.3));
}

.sidebar-brand-fallback-text {
	font-size: 1.15rem;
	font-weight: 800;
	color: #ffffff;
	letter-spacing: -0.5px;
}

.sidebar-brand-badge {
	background: rgba(56, 189, 248, 0.12);
	border: 1px solid rgba(56, 189, 248, 0.3);
	color: #38bdf8;
	font-size: 0.62rem;
	font-weight: 800;
	border-radius: 6px;
	padding: 2px 7px;
	letter-spacing: 0.5px;
	text-transform: uppercase;
}

/* User Profile Widget */
.sidebar-user {
	margin: 12px 12px 10px !important;
	padding: 10px 12px !important;
	background: rgba(255, 255, 255, 0.04) !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	border-radius: 12px !important;
	backdrop-filter: blur(10px) !important;
	transition: all 0.2s ease !important;
}

.sidebar-user:hover {
	background: rgba(255, 255, 255, 0.07) !important;
	border-color: rgba(56, 189, 248, 0.3) !important;
	box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2) !important;
}

.sidebar-user .avatar {
	width: 36px !important;
	height: 36px !important;
	object-fit: cover !important;
	border: 2px solid rgba(56, 189, 248, 0.4) !important;
	box-shadow: 0 0 10px rgba(56, 189, 248, 0.25) !important;
}

.sidebar-online-indicator {
	position: absolute;
	bottom: 0;
	right: 0;
	width: 9px;
	height: 9px;
	background: #10b981;
	border: 2px solid #03172c;
	border-radius: 50%;
	box-shadow: 0 0 6px #10b981;
}

.sidebar-user-name {
	color: #ffffff !important;
	font-weight: 700 !important;
	font-size: 0.85rem !important;
	letter-spacing: -0.2px !important;
	line-height: 1.25;
}

.sidebar-user-role {
	margin-top: 2px;
}

.sidebar-role-pill {
	display: inline-block;
	background: rgba(11, 83, 161, 0.35);
	border: 1px solid rgba(56, 189, 248, 0.25);
	color: #38bdf8;
	font-size: 0.65rem;
	font-weight: 700;
	border-radius: 20px;
	padding: 1px 7px;
	text-transform: uppercase;
	letter-spacing: 0.3px;
}

.sidebar-user-options {
	color: #94a3b8 !important;
	background: rgba(255, 255, 255, 0.06);
	width: 26px;
	height: 26px;
	border-radius: 7px;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.15s ease;
	text-decoration: none !important;
}

.sidebar-user-options:hover {
	color: #ffffff !important;
	background: rgba(255, 255, 255, 0.12);
}

.no-caret::after {
	display: none !important;
}

/* Quick Search Box */
.sidebar-search-box {
	margin: 0 12px 10px !important;
}

.sidebar-search-inner {
	position: relative;
	display: flex;
	align-items: center;
}

.sidebar-search-icon {
	position: absolute;
	left: 11px;
	width: 14px !important;
	height: 14px !important;
	color: #64748b !important;
	pointer-events: none;
	transition: color 0.15s ease;
}

.sidebar-search-input {
	width: 100%;
	height: 35px;
	background: rgba(255, 255, 255, 0.04) !important;
	border: 1px solid rgba(255, 255, 255, 0.09) !important;
	border-radius: 9px !important;
	padding: 5px 28px 5px 33px !important;
	font-size: 0.8rem !important;
	color: #ffffff !important;
	outline: none !important;
	transition: all 0.2s ease !important;
}

.sidebar-search-input::placeholder {
	color: #64748b !important;
	font-size: 0.76rem !important;
}

.sidebar-search-input:focus {
	background: rgba(255, 255, 255, 0.08) !important;
	border-color: #38bdf8 !important;
	box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15) !important;
}

.sidebar-search-inner:focus-within .sidebar-search-icon {
	color: #38bdf8 !important;
}

.sidebar-search-clear {
	position: absolute;
	right: 9px;
	background: transparent;
	border: none;
	color: #94a3b8;
	font-size: 1rem;
	line-height: 1;
	cursor: pointer;
	padding: 0;
}

.sidebar-search-clear:hover {
	color: #ffffff;
}

/* Section Headers */
.sidebar-header {
	background: transparent !important;
	color: #38bdf8 !important;
	font-size: 0.67rem !important;
	font-weight: 800 !important;
	text-transform: uppercase !important;
	letter-spacing: 0.09em !important;
	padding: 1.15rem 1.15rem 0.4rem !important;
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	opacity: 0.95 !important;
}

.sidebar-header::after {
	content: "" !important;
	flex: 1 !important;
	height: 1px !important;
	background: linear-gradient(90deg, rgba(56, 189, 248, 0.25) 0%, rgba(255, 255, 255, 0.02) 100%) !important;
}

/* Menu Items & Links */
.sidebar-item {
	margin: 2px 8px !important;
}

.sidebar-link,
a.sidebar-link {
	background: transparent !important;
	color: #cbd5e1 !important;
	border-left: none !important;
	border-radius: 9px !important;
	padding: 0.58rem 0.85rem !important;
	font-size: 0.84rem !important;
	font-weight: 500 !important;
	display: flex !important;
	align-items: center !important;
	gap: 10px !important;
	transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
	position: relative !important;
	text-decoration: none !important;
}

.sidebar-link i,
.sidebar-link svg {
	color: #94a3b8 !important;
	width: 16px !important;
	height: 16px !important;
	transition: all 0.18s ease !important;
	flex-shrink: 0 !important;
}

.sidebar-link:hover {
	background: rgba(255, 255, 255, 0.08) !important;
	color: #ffffff !important;
	transform: translateX(3px) !important;
}

.sidebar-link:hover i,
.sidebar-link:hover svg {
	color: #38bdf8 !important;
	transform: scale(1.1) !important;
}

/* Active Main Menu Item */
.sidebar-item.active > .sidebar-link,
.sidebar-item.active .sidebar-link:hover {
	background: linear-gradient(135deg, #0b53a1 0%, #0284c7 100%) !important;
	color: #ffffff !important;
	font-weight: 700 !important;
	box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35) !important;
	border-left: none !important;
	transform: translateX(2px) !important;
}

.sidebar-item.active > .sidebar-link i,
.sidebar-item.active > .sidebar-link svg {
	color: #ffffff !important;
}

/* Nested Dropdowns */
.sidebar-dropdown {
	background: rgba(0, 0, 0, 0.22) !important;
	border-radius: 9px !important;
	margin: 3px 2px 5px 12px !important;
	padding: 5px 3px 5px 10px !important;
	border-left: 2px solid rgba(56, 189, 248, 0.35) !important;
}

.sidebar-dropdown .sidebar-item {
	margin: 1px 0 !important;
}

.sidebar-dropdown .sidebar-link {
	padding: 0.42rem 0.75rem !important;
	font-size: 0.8rem !important;
	color: #94a3b8 !important;
	background: transparent !important;
	border-radius: 7px !important;
	font-weight: 500 !important;
}

.sidebar-dropdown .sidebar-link:hover {
	color: #ffffff !important;
	background: rgba(255, 255, 255, 0.07) !important;
	transform: translateX(3px) !important;
}

.sidebar-dropdown .sidebar-item.active > .sidebar-link {
	background: rgba(56, 189, 248, 0.16) !important;
	color: #38bdf8 !important;
	font-weight: 700 !important;
	box-shadow: none !important;
}

.sidebar-dropdown .sidebar-link:before {
	display: none !important;
}

/* Dropdown Chevron Indicator */
.sidebar [data-bs-toggle=collapse]:after {
	content: "" !important;
	display: inline-block !important;
	width: 6px !important;
	height: 6px !important;
	border-right: 2px solid #94a3b8 !important;
	border-bottom: 2px solid #94a3b8 !important;
	transform: rotate(45deg) !important;
	transition: transform 0.2s ease, border-color 0.2s ease !important;
	margin-left: auto !important;
	position: static !important;
	top: auto !important;
	right: auto !important;
}

.sidebar [data-bs-toggle=collapse]:hover:after {
	border-color: #ffffff !important;
}

.sidebar [data-bs-toggle=collapse]:not(.collapsed):after {
	transform: rotate(-135deg) !important;
	border-color: #38bdf8 !important;
}

/* Sidebar Badges */
.sidebar-badge {
	font-size: 0.68rem !important;
	font-weight: 700 !important;
	padding: 2px 7px !important;
	border-radius: 12px !important;
	background: rgba(56, 189, 248, 0.18) !important;
	color: #38bdf8 !important;
	border: 1px solid rgba(56, 189, 248, 0.32) !important;
	margin-left: auto !important;
	letter-spacing: 0.2px !important;
}

/* Sidebar Live Status Card */
.sidebar-status-card {
	background: rgba(255, 255, 255, 0.03);
	border: 1px solid rgba(255, 255, 255, 0.07);
	border-radius: 10px;
	padding: 10px 12px;
	margin: 14px 10px 20px;
}

.sidebar-live-dot {
	width: 8px;
	height: 8px;
	background: #10b981;
	border-radius: 50%;
	display: inline-block;
	box-shadow: 0 0 8px #10b981;
	animation: pulseLive 2s infinite;
}

@keyframes pulseLive {
	0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
	70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
	100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
</style>

<nav id="sidebar" class="sidebar js-sidebar">
	<div class="sidebar-content js-simplebar">
		<a class='sidebar-brand text-decoration-none' href='dashboard.php'>
			<div class="d-flex align-items-center gap-2">
				<img src="img/PrintwhiteLogo.png" alt="Printmont" class="sidebar-brand-logo" onerror="this.style.display='none'; document.getElementById('sidebar-brand-fallback-text').style.display='inline-flex';" />
				<span id="sidebar-brand-fallback-text" class="sidebar-brand-fallback-text" style="display:none;">PRINTMONT</span>
			</div>
			<span class="sidebar-brand-badge">ADMIN</span>
		</a>

		<div class="sidebar-user">
			<div class="d-flex align-items-center justify-content-between">
				<div class="d-flex align-items-center gap-2 min-width-0">
					<div class="position-relative flex-shrink-0">
						<img src="img/avatars/avatar.jpg" class="avatar rounded-circle"
							alt="<?php echo htmlspecialchars($current_user['username']); ?>"
							onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($current_user['username'] ?? 'Admin'); ?>&background=00529b&color=fff';" />
						<span class="sidebar-online-indicator" title="Online"></span>
					</div>
					<div class="min-width-0 text-truncate">
						<div class="sidebar-user-name text-truncate">
							<?php echo htmlspecialchars($current_user['username'] ?? 'Admin User'); ?>
						</div>
						<div class="sidebar-user-role">
							<span class="sidebar-role-pill"><?php echo htmlspecialchars(ucfirst($current_user['role'] ?? 'Administrator')); ?></span>
						</div>
					</div>
				</div>
				<div class="dropdown">
					<a class="sidebar-user-options dropdown-toggle no-caret" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="Account settings">
						<i data-feather="more-vertical" class="align-middle"></i>
					</a>
					<div class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2">
						<a class='dropdown-item d-flex align-items-center gap-2 py-2 text-dark' href='pages-profile.php'>
							<i data-feather="user" class="text-primary" style="width:15px;height:15px;"></i> Profile
						</a>
						<a class="dropdown-item d-flex align-items-center gap-2 py-2 text-dark" href="footer-management.php">
							<i data-feather="settings" class="text-primary" style="width:15px;height:15px;"></i> Settings
						</a>
						<div class="dropdown-divider my-1"></div>
						<a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger logout" href="<?= BASE_URL ?>api/auth/logout.php">
							<i data-feather="log-out" class="text-danger" style="width:15px;height:15px;"></i> Log out
						</a>
					</div>
				</div>
			</div>
		</div>

		<!-- Quick Menu Search -->
		<div class="sidebar-search-box">
			<div class="sidebar-search-inner">
				<i data-feather="search" class="sidebar-search-icon"></i>
				<input type="text" id="sidebarMenuSearch" class="sidebar-search-input" placeholder="Quick find menu..." autocomplete="off" />
				<button type="button" id="sidebarSearchClear" class="sidebar-search-clear" style="display:none;" aria-label="Clear">&times;</button>
			</div>
		</div>

		<ul class="sidebar-nav">
			<!-- SECTION: OVERVIEW -->
			<li class="sidebar-header">
				Overview
			</li>

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

			<?php if (shouldDisplay($menu_access['notifications'], $user_role)): ?>
				<li class="sidebar-item <?php echo isActivePage('notifications.php', $current_page) ? 'active' : ''; ?>">
					<a class='sidebar-link' href='notifications.php'>
						<i class="align-middle" data-feather="bell"></i>
						<span class="align-middle">Notifications</span>
						<?php if (isActivePage('notifications.php', $current_page)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
				</li>
				<li class="sidebar-item <?php echo isActivePage('customer-notifications.php', $current_page) ? 'active' : ''; ?>">
					<a class='sidebar-link' href='customer-notifications.php'>
						<i class="align-middle" data-feather="send"></i>
						<span class="align-middle">Customer Notifications</span>
						<?php if (isActivePage('customer-notifications.php', $current_page)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['analytics'], $user_role)): ?>
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


			<!-- SECTION: SALES & ORDERS -->
			<li class="sidebar-header">
				Sales &amp; Orders
			</li>

			<?php if (shouldDisplay($menu_access['orders'], $user_role)): ?>
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
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All Orders</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('pending-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='pending-orders.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Pending Orders</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('processing-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='processing-orders.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Processing Orders</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('delivered-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='delivered-orders.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Delivered Orders</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('completed-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='completed-orders.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Completed Orders</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('declined-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='declined-orders.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Declined Orders</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('refund-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='refund-orders.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Refund Orders</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('total-sold-orders.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='total-sold-orders.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Total Sold Orders</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['customers'], $user_role)): ?>
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
						<li class="sidebar-item <?php echo isActivePage('customers.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='customers.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Customers List</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('customer-images.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='customer-images.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Customer Default Images</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<!-- Coins & Wallets Management -->
			<li class="sidebar-item">
				<a data-bs-target="#coins-wallets-menu" data-bs-toggle="collapse"
					class="sidebar-link <?php echo (isActivePage('coins-manager.php', $current_page) || isActivePage('wallets-manager.php', $current_page)) ? '' : 'collapsed'; ?>">
					<i class="align-middle" data-feather="dollar-sign"></i>
					<span class="align-middle">Coins &amp; Wallets</span>
					<?php if (isActivePage('coins-manager.php', $current_page) || isActivePage('wallets-manager.php', $current_page)): ?>
						<span class="sidebar-badge">●</span>
					<?php endif; ?>
				</a>
				<ul id="coins-wallets-menu"
					class="sidebar-dropdown list-unstyled collapse <?php echo (isActivePage('coins-manager.php', $current_page) || isActivePage('wallets-manager.php', $current_page)) ? 'show' : ''; ?>"
					data-bs-parent="#sidebar">
					<li class="sidebar-item <?php echo isActivePage('coins-manager.php', $current_page) ? 'active' : ''; ?>">
						<a class='sidebar-link' href='coins-manager.php'>
							<i class="align-middle" data-feather="circle"></i>
							<span class="align-middle">SuperCoins Manager</span>
						</a>
					</li>
					<li class="sidebar-item <?php echo isActivePage('wallets-manager.php', $current_page) ? 'active' : ''; ?>">
						<a class='sidebar-link' href='wallets-manager.php'>
							<i class="align-middle" data-feather="circle"></i>
							<span class="align-middle">Customer Wallets (₹)</span>
						</a>
					</li>
				</ul>
			</li>

			<?php if (shouldDisplay($menu_access['bulk_orders'], $user_role) || shouldDisplay($menu_access['contact_inquiries'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#inquiries-all" data-bs-toggle="collapse"
						class="sidebar-link <?php echo (isActiveGroup('inquiry', $current_page, $page_groups) || isActiveGroup('contact_inquiries', $current_page, $page_groups)) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="inbox"></i>
						<span class="align-middle">Inquiries &amp; Leads</span>
						<?php if (isActiveGroup('inquiry', $current_page, $page_groups) || isActiveGroup('contact_inquiries', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="inquiries-all"
						class="sidebar-dropdown list-unstyled collapse <?php echo (isActiveGroup('inquiry', $current_page, $page_groups) || isActiveGroup('contact_inquiries', $current_page, $page_groups)) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('all-inquiry.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='all-inquiry.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Bulk Order Inquiries</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('new-inquiry.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='new-inquiry.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">New Bulk Inquiry</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('pending-inquiry.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='pending-inquiry.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Pending Inquiries</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('completed-inquiry.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='completed-inquiry.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Completed Inquiries</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('total-sold-inquiry.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='total-sold-inquiry.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Total Sold Inquiries</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('contact-inquiries.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='contact-inquiries.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Contact Form Messages</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>


			<!-- SECTION: CATALOG & PRODUCTS -->
			<li class="sidebar-header">
				Catalog &amp; Products
			</li>

			<?php if (shouldDisplay($menu_access['products'], $user_role)): ?>
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
						<li class="sidebar-item <?php echo isActivePage('view-products.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='view-products.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All Products</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('product.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='product.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add New Product</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('top-selection-products.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='top-selection-products.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Top Selection Products</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('besteseller-prodcuts.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='besteseller-prodcuts.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Bestseller Products</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('deactive-products.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='deactive-products.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Deactivated Products</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('single-product-version.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='single-product-version.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Single Product Version</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('category-banner-texts.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='category-banner-texts.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Category Banner Texts</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('customization-options.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='customization-options.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Customization Options</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('product-sales.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='product-sales.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Product Sales</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('gift-vouchers.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='gift-vouchers.php'>
								<i class="align-middle" data-feather="gift"></i>
								<span class="align-middle">Gift Vouchers</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['categories'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#categories" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('categories', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="layers"></i>
						<span class="align-middle">Manage Categories</span>
						<?php if (isActiveGroup('categories', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="categories"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('categories', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('add-main-category.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-main-category.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Main Category</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo (isActivePage('view-categories.php', $current_page) && isset($_GET['level']) && $_GET['level'] == '1') ? 'active' : ''; ?>">
							<a class='sidebar-link' href='view-categories.php?level=1'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Main All Category</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-sub-category.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-sub-category.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Sub Category</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo (isActivePage('view-categories.php', $current_page) && isset($_GET['level']) && $_GET['level'] == '2') ? 'active' : ''; ?>">
							<a class='sidebar-link' href='view-categories.php?level=2'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Sub All Category</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-sub-sub-category.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-sub-sub-category.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Sub Sub Category</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo (isActivePage('view-categories.php', $current_page) && isset($_GET['level']) && $_GET['level'] == '3') ? 'active' : ''; ?>">
							<a class='sidebar-link' href='view-categories.php?level=3'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Sub Sub All Category</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo (isActivePage('view-categories.php', $current_page) && empty($_GET['level'])) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='view-categories.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All Categories Overview</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['product_filters'], $user_role)): ?>
				<li class="sidebar-item <?php echo isActivePage('product-filters.php', $current_page) ? 'active' : ''; ?>">
					<a class='sidebar-link' href='product-filters.php'>
						<i class="align-middle" data-feather="filter"></i>
						<span class="align-middle">Product Filters</span>
						<?php if (isActivePage('product-filters.php', $current_page)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['products'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#reviews" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('reviews', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="message-square"></i>
						<span class="align-middle">Product Reviews</span>
						<?php if (isActiveGroup('reviews', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="reviews"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('reviews', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('reviews.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='reviews.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All Reviews</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-review.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-review.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add Review</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['gift_finder'], $user_role)): ?>
				<li class="sidebar-item <?php echo isActivePage('gift-finder.php', $current_page) ? 'active' : ''; ?>">
					<a class='sidebar-link' href='gift-finder.php'>
						<i class="align-middle" data-feather="gift"></i>
						<span class="align-middle">Gift Finder</span>
						<?php if (isActivePage('gift-finder.php', $current_page)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
				</li>
			<?php endif; ?>


			<!-- SECTION: STOREFRONT & CMS -->
			<li class="sidebar-header">
				Storefront &amp; Design
			</li>

			<?php if (shouldDisplay($menu_access['header_menu'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#headerMenu" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('header_menu', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="menu"></i>
						<span class="align-middle">Header Category Menu</span>
						<?php if (isActiveGroup('header_menu', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="headerMenu"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('header_menu', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('add-top-icon.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-top-icon.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Top Icon</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-menu-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-menu-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Top Menu Inside Pages</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('header-menu-design.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='header-menu-design.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Select Design</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('header-menu-list.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='header-menu-list.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All menus</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('header-settings.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='header-settings.php'>
								<i class="align-middle" data-feather="sliders"></i>
								<span class="align-middle">Page Rules &amp; Visibility</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['banners'] ?? ['admin', 'manager'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#banner-management" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('banners', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="image"></i>
						<span class="align-middle">Home Page Banners</span>
						<?php if (isActiveGroup('banners', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="banner-management"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('banners', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('add-banner.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-banner.php'>
								<i class="align-middle" data-feather="plus-circle"></i>
								<span class="align-middle">Add Home Banner</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-banner-carousel.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-banner-carousel.php'>
								<i class="align-middle" data-feather="sliders"></i>
								<span class="align-middle">Add Home Carousel</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('view-banner.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='view-banner.php?filter_page=home'>
								<i class="align-middle" data-feather="layers"></i>
								<span class="align-middle">All Home Banners</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('deactivated-banners.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='deactivated-banners.php'>
								<i class="align-middle" data-feather="slash"></i>
								<span class="align-middle text-danger">Deactivated Banner</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['banners'] ?? ['admin', 'manager'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#page-banner-management" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('page_banners', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="monitor"></i>
						<span class="align-middle">Page Banners</span>
						<?php if (isActiveGroup('page_banners', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="page-banner-management"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('page_banners', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('add-page-banner.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-page-banner.php'>
								<i class="align-middle" data-feather="plus-circle"></i>
								<span class="align-middle">Add Banner / Carousel</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('page-banners.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='page-banners.php'>
								<i class="align-middle" data-feather="layers"></i>
								<span class="align-middle">All Page Banners</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['home_page'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#home-settings" data-bs-toggle="collapse"
						class="sidebar-link <?php echo (isActiveGroup('home_settings', $current_page, $page_groups) || isActiveGroup('carousels', $current_page, $page_groups) || isActiveGroup('home_products', $current_page, $page_groups)) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="layout"></i>
						<span class="align-middle">Home Page Layout</span>
						<?php if (isActiveGroup('home_settings', $current_page, $page_groups) || isActiveGroup('carousels', $current_page, $page_groups) || isActiveGroup('home_products', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="home-settings"
						class="sidebar-dropdown list-unstyled collapse <?php echo (isActiveGroup('home_settings', $current_page, $page_groups) || isActiveGroup('carousels', $current_page, $page_groups) || isActiveGroup('home_products', $current_page, $page_groups)) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('home-layout-manager.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='home-layout-manager.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Home Layout Manager</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('product-layout-manager.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='product-layout-manager.php'>
								<i class="align-middle" data-feather="smartphone"></i>
								<span class="align-middle">Product Detail Layout (Mobile)</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('carousels.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='carousels.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All Carousels</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-carousel.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-carousel.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add Carousel</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('home-products.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='home-products.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Home Featured Products</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-home-product.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-home-product.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add Home Product</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['website_management'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#pages" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('pages', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="file-text"></i>
						<span class="align-middle">Website Pages &amp; CMS</span>
						<?php if (isActiveGroup('pages', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="pages"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('pages', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('website-pages.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='website-pages.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All CMS Pages</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add New Page</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('about-us-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='about-us-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">About Us Page</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('contact-view.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='contact-view.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Contact Us Page</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('faq-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='faq-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">FAQ Page</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('faq-view-category.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='faq-view-category.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">FAQ Categories</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('help-center-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='help-center-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Help Center</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('security-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='security-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Security Page</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('policy-edit.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='policy-edit.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Policy Management</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('affiliate-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='affiliate-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Affiliate Program</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('become-a-seller-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='become-a-seller-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Become a Seller</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('business-solutions-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='business-solutions-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Business Solutions</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('lead-inquiries.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='lead-inquiries.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Lead Inquiries</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('bulk-orders-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='bulk-orders-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Bulk Orders</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('franchise-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='franchise-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Franchise Page</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('advertising-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='advertising-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Advertising Page</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['blog'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#blog" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('blog', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="edit-3"></i>
						<span class="align-middle">Blog &amp; Articles</span>
						<?php if (isActiveGroup('blog', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="blog"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('blog', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('blog-page.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='blog-page.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Blog Page (Banners &amp; Layout)</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('blog-posts.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='blog-posts.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All Posts</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-blog-post.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-blog-post.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add New Post</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('blog-categories.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='blog-categories.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Blog Categories</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-blog-category.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-blog-category.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add Blog Category</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['careers'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#careers" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('careers', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="briefcase"></i>
						<span class="align-middle">Careers &amp; Jobs</span>
						<?php if (isActiveGroup('careers', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="careers"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('careers', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('careers.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='careers.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Job Openings</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-career.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-career.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add Job Opening</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('career-applications.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='career-applications.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Applications Received</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['website_management'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#watermark" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('watermark', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="droplet"></i>
						<span class="align-middle">Media Watermarks</span>
						<?php if (isActiveGroup('watermark', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="watermark"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('watermark', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('watermarks.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='watermarks.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All Watermarks</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-watermark.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-watermark.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add Watermark</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('deactivated-watermarks.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='deactivated-watermarks.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Deactivated Watermarks</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>


			<!-- SECTION: PROMOTIONS & MARKETING -->
			<li class="sidebar-header">
				Promotions &amp; Marketing
			</li>

			<?php if (shouldDisplay($menu_access['marketing'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#coupons" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('coupons', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="tag"></i>
						<span class="align-middle">Coupons &amp; Discounts</span>
						<?php if (isActiveGroup('coupons', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="coupons"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('coupons', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('coupons.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='coupons.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All Coupons</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-coupon.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-coupon.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add New Coupon</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('expired-coupons.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='expired-coupons.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Expired Coupons</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>

			<?php if (shouldDisplay($menu_access['seo'], $user_role)): ?>
				<li class="sidebar-item">
					<a data-bs-target="#seo" data-bs-toggle="collapse"
						class="sidebar-link <?php echo isActiveGroup('seo', $current_page, $page_groups) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="search"></i>
						<span class="align-middle">SEO &amp; Tracking</span>
						<?php if (isActiveGroup('seo', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="seo"
						class="sidebar-dropdown list-unstyled collapse <?php echo isActiveGroup('seo', $current_page, $page_groups) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('google-analytics.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='google-analytics.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Google Analytics</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('facebook-pixels.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='facebook-pixels.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Facebook Pixels</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('meta-keywords.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='meta-keywords.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Meta Keywords</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('sitemap-management.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='sitemap-management.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Sitemap XML</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>


			<!-- SECTION: SETTINGS & SYSTEM -->
			<li class="sidebar-header">
				Settings &amp; Administration
			</li>

			<?php if (shouldDisplay($menu_access['settings'], $user_role)): ?>
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
						<li class="sidebar-item <?php echo isActivePage('logo-management.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='logo-management.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Site Logo</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('favicon-settings.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='favicon-settings.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Favicon</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('loader-settings.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='loader-settings.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Page Loader</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('footer-management.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='footer-management.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Footer Management</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('quick-links-manager.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='quick-links-manager.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Quick Links Manager</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('general-settings.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='general-settings.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Contact Info</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('website-content.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='website-content.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Website Content</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('login-background.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='login-background.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Login Background</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('error-background.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='error-background.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Error Background</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('delivery-locations.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='delivery-locations.php'>
								<i class="align-middle" data-feather="map-pin"></i>
								<span class="align-middle">Delivery Locations</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('maintenance-settings.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='maintenance-settings.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Maintenance Mode</span>
							</a>
						</li>
					</ul>
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#payment-group" data-bs-toggle="collapse"
						class="sidebar-link <?php echo (isActiveGroup('payment_settings', $current_page, $page_groups) || isActiveGroup('cod_settings', $current_page, $page_groups)) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="credit-card"></i>
						<span class="align-middle">Payment &amp; COD</span>
						<?php if (isActiveGroup('payment_settings', $current_page, $page_groups) || isActiveGroup('cod_settings', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="payment-group"
						class="sidebar-dropdown list-unstyled collapse <?php echo (isActiveGroup('payment_settings', $current_page, $page_groups) || isActiveGroup('cod_settings', $current_page, $page_groups)) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('payment-settings.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='payment-settings.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Payment Gateways</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('cod-settings.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='cod-settings.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">COD Settings</span>
							</a>
						</li>
					</ul>
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
						<li class="sidebar-item <?php echo isActivePage('email-configurations.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='email-configurations.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">SMTP Configurations</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('email-templates.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='email-templates.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Email Templates</span>
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
						<li class="sidebar-item <?php echo isActivePage('social-links.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='social-links.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All Social Links</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-social-link.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-social-link.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add Social Link</span>
							</a>
						</li>
					</ul>
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#staff-mgmt" data-bs-toggle="collapse"
						class="sidebar-link <?php echo (isActiveGroup('staff_mgmt', $current_page, $page_groups) || isActiveGroup('roles', $current_page, $page_groups)) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="user-check"></i>
						<span class="align-middle">Staff &amp; Permissions</span>
						<?php if (isActiveGroup('staff_mgmt', $current_page, $page_groups) || isActiveGroup('roles', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="staff-mgmt"
						class="sidebar-dropdown list-unstyled collapse <?php echo (isActiveGroup('staff_mgmt', $current_page, $page_groups) || isActiveGroup('roles', $current_page, $page_groups)) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('staff.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='staff.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">All Staff</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-staff.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-staff.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add New Staff</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('deactivated-staff.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='deactivated-staff.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Deactivated Staff</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('roles.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='roles.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Manage Roles</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('add-role.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='add-role.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Add New Role</span>
							</a>
						</li>
					</ul>
				</li>

				<li class="sidebar-item">
					<a data-bs-target="#system-tools" data-bs-toggle="collapse"
						class="sidebar-link <?php echo (isActiveGroup('master_modules', $current_page, $page_groups) || isActiveGroup('migrations', $current_page, $page_groups)) ? '' : 'collapsed'; ?>">
						<i class="align-middle" data-feather="database"></i>
						<span class="align-middle">System &amp; Database</span>
						<?php if (isActiveGroup('master_modules', $current_page, $page_groups) || isActiveGroup('migrations', $current_page, $page_groups)): ?>
							<span class="sidebar-badge">●</span>
						<?php endif; ?>
					</a>
					<ul id="system-tools"
						class="sidebar-dropdown list-unstyled collapse <?php echo (isActiveGroup('master_modules', $current_page, $page_groups) || isActiveGroup('migrations', $current_page, $page_groups)) ? 'show' : ''; ?>"
						data-bs-parent="#sidebar">
						<li class="sidebar-item <?php echo isActivePage('master-modules.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='master-modules.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">Master Modules</span>
							</a>
						</li>
						<li class="sidebar-item <?php echo isActivePage('migrations.php', $current_page) ? 'active' : ''; ?>">
							<a class='sidebar-link' href='migrations.php'>
								<i class="align-middle" data-feather="circle"></i>
								<span class="align-middle">DB Migrations</span>
							</a>
						</li>
					</ul>
				</li>
			<?php endif; ?>
		</ul>

		<!-- Sidebar Status Footer Card -->
		<div class="sidebar-status-card">
			<div class="d-flex align-items-center justify-content-between mb-1">
				<span class="d-flex align-items-center gap-2 text-white small fw-bold">
					<span class="sidebar-live-dot"></span> System Live
				</span>
				<span class="sidebar-badge" style="margin-left:0; font-size:0.62rem; padding: 1px 7px;">v2.6 PRO</span>
			</div>
			<div style="font-size: 0.72rem; color: #94a3b8; display:flex; justify-content:space-between; align-items:center;">
				<span>Printmont Admin Core</span>
				<span class="text-white-50"><?php echo date('H:i'); ?> IST</span>
			</div>
		</div>

	</div>
</nav>

<style>
	/* Layout width and overflow normalization to keep sidebar stable & right body scrollable */
	html, body {
		height: 100% !important;
		max-height: 100vh !important;
		overflow: hidden !important;
		max-width: 100vw !important;
		margin: 0 !important;
		padding: 0 !important;
	}
	.wrapper {
		height: 100vh !important;
		max-height: 100vh !important;
		overflow: hidden !important;
		max-width: 100% !important;
		width: 100% !important;
		display: flex !important;
		position: relative !important;
	}
	.main {
		min-width: 0 !important;
		max-width: 100% !important;
		height: 100vh !important;
		max-height: 100vh !important;
		overflow-y: auto !important;
		overflow-x: hidden !important;
		display: flex !important;
		flex-direction: column !important;
		scroll-behavior: smooth !important;
		-webkit-overflow-scrolling: touch !important;
	}
	.content {
		max-width: 100% !important;
		box-sizing: border-box !important;
		flex: 1 0 auto !important;
	}
	.container-fluid.p-0 > .row,
	.container.p-0 > .row {
		margin-left: 0 !important;
		margin-right: 0 !important;
	}
</style>

<script>
	// Persistent Sidebar Scroll Position & Active Class Management
	(function () {
		const STORAGE_KEY = 'admin_sidebar_scroll_pos';

		function getScrollContainer() {
			return document.querySelector('#sidebar .simplebar-content-wrapper') ||
				   document.querySelector('.sidebar-content') ||
				   document.querySelector('#sidebar');
		}

		function saveScrollPos() {
			const el = getScrollContainer();
			if (el) {
				sessionStorage.setItem(STORAGE_KEY, el.scrollTop);
			}
		}

		function restoreScrollPos() {
			const savedPos = sessionStorage.getItem(STORAGE_KEY);
			const el = getScrollContainer();
			if (el) {
				if (savedPos !== null && !isNaN(savedPos) && Number(savedPos) > 0) {
					el.scrollTop = parseInt(savedPos, 10);
				} else {
					// If no previous scroll, ensure active item is visible in viewport
					const activeItem = document.querySelector('.sidebar-item.active');
					if (activeItem) {
						activeItem.scrollIntoView({ block: 'nearest', inline: 'nearest' });
					}
				}
			}
		}

		function attachScrollListener() {
			const el = getScrollContainer();
			if (el && !el._scrollListenerAttached) {
				el.addEventListener('scroll', function () {
					sessionStorage.setItem(STORAGE_KEY, el.scrollTop);
				}, { passive: true });
				el._scrollListenerAttached = true;
			}
		}

		// Save scroll on unload and before link clicks
		window.addEventListener('beforeunload', saveScrollPos);

		document.addEventListener('DOMContentLoaded', function () {
			// Ensure feather icons rendered
			if (window.feather) {
				try { feather.replace(); } catch (e) {}
			}

			// Store active state in session storage
			const activeMenu = sessionStorage.getItem('activeMenu');
			if (activeMenu) {
				const menuItem = document.querySelector(`[href="${activeMenu}"]`);
				if (menuItem) {
					menuItem.closest('.sidebar-item').classList.add('active');
				}
			}

			// Update active menu & save scroll on click
			document.querySelectorAll('.sidebar-link').forEach(link => {
				link.addEventListener('click', function (e) {
					saveScrollPos();
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

			// Live Search Filtering for Sidebar Items
			const searchInput = document.getElementById('sidebarMenuSearch');
			const clearBtn = document.getElementById('sidebarSearchClear');
			if (searchInput) {
				function filterMenuItems(query) {
					const term = (query || '').toLowerCase().trim();
					const nav = document.querySelector('.sidebar-nav');
					if (!nav) return;

					if (clearBtn) {
						clearBtn.style.display = term.length > 0 ? 'inline-block' : 'none';
					}

					const headers = nav.querySelectorAll('.sidebar-header');
					const items = Array.from(nav.children).filter(el => el.classList.contains('sidebar-item'));

					if (!term) {
						headers.forEach(h => h.style.display = '');
						items.forEach(item => {
							item.style.display = '';
							const dropdown = item.querySelector('.sidebar-dropdown');
							if (dropdown) {
								dropdown.querySelectorAll('.sidebar-item').forEach(sub => sub.style.display = '');
								if (!item.classList.contains('active') && !dropdown.querySelector('.sidebar-item.active')) {
									dropdown.classList.remove('show');
								}
							}
						});
						return;
					}

					items.forEach(item => {
						const dropdown = item.querySelector('.sidebar-dropdown');
						const parentLink = item.querySelector(':scope > .sidebar-link');
						const parentText = parentLink ? parentLink.textContent.toLowerCase() : '';

						if (dropdown) {
							const subItems = dropdown.querySelectorAll('.sidebar-item');
							let anySubMatch = false;

							subItems.forEach(sub => {
								const subLink = sub.querySelector('.sidebar-link');
								const subText = subLink ? subLink.textContent.toLowerCase() : '';
								if (subText.includes(term) || parentText.includes(term)) {
									sub.style.display = '';
									anySubMatch = true;
								} else {
									sub.style.display = 'none';
								}
							});

							if (anySubMatch || parentText.includes(term)) {
								item.style.display = '';
								dropdown.classList.add('show');
								if (parentLink && parentLink.hasAttribute('data-bs-toggle')) {
									parentLink.classList.remove('collapsed');
									parentLink.setAttribute('aria-expanded', 'true');
								}
							} else {
								item.style.display = 'none';
							}
						} else {
							if (parentText.includes(term)) {
								item.style.display = '';
							} else {
								item.style.display = 'none';
							}
						}
					});

					// Update header visibility
					let currentHeader = null;
					let currentHeaderHasVisible = false;

					Array.from(nav.children).forEach(node => {
						if (node.classList.contains('sidebar-header')) {
							if (currentHeader) {
								currentHeader.style.display = currentHeaderHasVisible ? '' : 'none';
							}
							currentHeader = node;
							currentHeaderHasVisible = false;
						} else if (node.classList.contains('sidebar-item')) {
							if (node.style.display !== 'none') {
								currentHeaderHasVisible = true;
							}
						}
					});
					if (currentHeader) {
						currentHeader.style.display = currentHeaderHasVisible ? '' : 'none';
					}
				}

				searchInput.addEventListener('input', function () {
					filterMenuItems(this.value);
				});

				searchInput.addEventListener('keydown', function (e) {
					if (e.key === 'Escape') {
						this.value = '';
						filterMenuItems('');
						this.blur();
					}
				});

				if (clearBtn) {
					clearBtn.addEventListener('click', function () {
						searchInput.value = '';
						filterMenuItems('');
						searchInput.focus();
					});
				}
			}

			// Restore scroll position with multiple timing stages to account for SimpleBar initialization
			restoreScrollPos();
			attachScrollListener();
			setTimeout(restoreScrollPos, 30);
			setTimeout(restoreScrollPos, 100);
			setTimeout(restoreScrollPos, 250);
			setTimeout(function() {
				restoreScrollPos();
				attachScrollListener();
			}, 500);

			// Mobile backdrop handling
			const backdrop = document.createElement('div');
			backdrop.className = 'sidebar-mobile-backdrop';
			document.body.appendChild(backdrop);

			const sidebarEl = document.querySelector('.js-sidebar');
			backdrop.addEventListener('click', function () {
				if (sidebarEl && sidebarEl.classList.contains('collapsed')) {
					sidebarEl.classList.remove('collapsed');
					backdrop.classList.remove('show');
				}
			});

			document.querySelectorAll('.js-sidebar-toggle').forEach(btn => {
				btn.addEventListener('click', function () {
					if (window.innerWidth < 992 && sidebarEl) {
						setTimeout(() => {
							if (sidebarEl.classList.contains('collapsed')) {
								backdrop.classList.add('show');
							} else {
								backdrop.classList.remove('show');
							}
						}, 50);
					}
				});
			});
		});

		window.addEventListener('load', function () {
			restoreScrollPos();
			attachScrollListener();
			setTimeout(restoreScrollPos, 100);
		});
	})();
</script>