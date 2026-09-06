<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
ob_start();
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

// session_start();

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
	<meta name="author" content="AdminKit">
	<meta name="keywords"
		content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

	<link rel="preconnect" href="https://fonts.gstatic.com/">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<link rel="canonical" href="index.html" />

	<title>Dashboard | Printmont Admin</title>

	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

	<link class="js-stylesheet" href="css/light.css" rel="stylesheet">
	<script src="js/settings.js"></script>
	<style>
		body {
			opacity: 0;
		}
	</style>
	<!-- END SETTINGS -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-120946860-10"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag() { dataLayer.push(arguments); }
		gtag('js', new Date());

		gtag('config', 'UA-120946860-10', { 'anonymize_ip': true });
	</script>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
	<div class="wrapper">
		<?php
		include_once "includes/side-navbar.php";
		?>

		<div class="main">
			<?php
			include_once "includes/top-navbar.php";
			?>

			<main class="content">
				<div class="container-fluid p-0">

					<!-- Dashboard Header -->
					<div class="row mb-2 mb-xl-3">
						<div class="col-auto d-none d-sm-block">
							<h3><strong>E-Commerce</strong> Dashboard</h3>
						</div>
						<div class="col-auto ms-auto text-end mt-n1">
							<span class="text-muted">Last Updated: <?php echo date('M d, Y h:i A'); ?></span>
						</div>
					</div>

					<!-- Key Metrics Row -->
					<div class="row">
						<!-- Total Revenue -->
						<div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Total Revenue</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="dollar-sign"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3">
										<?php
										$totalRevenue = 0;
										// Calculate from orders table
										$revenueQuery = "SELECT SUM(grand_total) as total FROM orders WHERE payment_status = 'paid'";
										// Execute query and display result
										?>
										$2,850
									</h1>
									<div class="mb-0">
										<span class="badge badge-success-light">+12.5%</span>
										<span class="text-muted">From last month</span>
									</div>
								</div>
							</div>
						</div>

						<!-- Total Orders -->
						<div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Total Orders</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="shopping-cart"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3">
										<?php
										$ordersQuery = "SELECT COUNT(*) as total FROM orders";
										// Execute query
										?>
										1,248
									</h1>
									<div class="mb-0">
										<span class="badge badge-primary-light">+8.2%</span>
										<span class="text-muted">From last month</span>
									</div>
								</div>
							</div>
						</div>

						<!-- Total Products -->
						<div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Total Products</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="package"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3">
										<?php
										$productsQuery = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
										// Execute query
										?>
										263
									</h1>
									<div class="mb-0">
										<span class="badge badge-warning-light">+5.1%</span>
										<span class="text-muted">Active products</span>
									</div>
								</div>
							</div>
						</div>

						<!-- Total Customers -->
						<div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Total Customers</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="users"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3">
										<?php
										$customersQuery = "SELECT COUNT(DISTINCT user_id) as total FROM orders";
										// Execute query
										?>
										856
									</h1>
									<div class="mb-0">
										<span class="badge badge-success-light">+15.3%</span>
										<span class="text-muted">Registered users</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Second Row: Charts -->
					<div class="row">
						<!-- Total Revenue -->
						<div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Total Revenue</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="dollar-sign"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3">
										<?php
										$totalRevenue = 0;
										// Calculate from orders table
										$revenueQuery = "SELECT SUM(grand_total) as total FROM orders WHERE payment_status = 'paid'";
										// Execute query and display result
										?>
										$2,850
									</h1>
									<div class="mb-0">
										<span class="badge badge-success-light">+12.5%</span>
										<span class="text-muted">From last month</span>
									</div>
								</div>
							</div>
						</div>

						<!-- Total Orders -->
						<div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Total Orders</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="shopping-cart"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3">
										<?php
										$ordersQuery = "SELECT COUNT(*) as total FROM orders";
										// Execute query
										?>
										1,248
									</h1>
									<div class="mb-0">
										<span class="badge badge-primary-light">+8.2%</span>
										<span class="text-muted">From last month</span>
									</div>
								</div>
							</div>
						</div>

						<!-- Total Products -->
						<div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Total Products</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="package"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3">
										<?php
										$productsQuery = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
										// Execute query
										?>
										263
									</h1>
									<div class="mb-0">
										<span class="badge badge-warning-light">+5.1%</span>
										<span class="text-muted">Active products</span>
									</div>
								</div>
							</div>
						</div>

						<!-- Total Customers -->
						<div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
							<div class="card">
								<div class="card-body">
									<div class="row">
										<div class="col mt-0">
											<h5 class="card-title">Total Customers</h5>
										</div>
										<div class="col-auto">
											<div class="stat text-primary">
												<i class="align-middle" data-feather="users"></i>
											</div>
										</div>
									</div>
									<h1 class="mt-1 mb-3">
										<?php
										$customersQuery = "SELECT COUNT(DISTINCT user_id) as total FROM orders";
										// Execute query
										?>
										856
									</h1>
									<div class="mb-0">
										<span class="badge badge-success-light">+15.3%</span>
										<span class="text-muted">Registered users</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Third Row: Recent Data -->
					<div class="row">
						<!-- Recent Orders -->
						<div class="col-12 col-lg-8 col-xxl-9 d-flex">
							<div class="card flex-fill">
								<div class="card-header">
									<div class="card-actions float-end">
										<a href="orders.php" class="btn btn-sm btn-primary">View All</a>
									</div>
									<h5 class="card-title mb-0">Recent Orders</h5>
								</div>
								<div class="table-responsive">
									<table class="table table-hover my-0">
										<thead>
											<tr>
												<th>Order ID</th>
												<th>Customer</th>
												<th class="d-none d-xl-table-cell">Date</th>
												<th>Amount</th>
												<th>Status</th>
												<th class="d-none d-md-table-cell">Action</th>
											</tr>
										</thead>
										<tbody>
											<?php
											// Recent orders query
											$recentOrdersQuery = "SELECT o.*, u.first_name, u.last_name 
                                                     FROM orders o 
                                                     LEFT JOIN users u ON o.user_id = u.id 
                                                     ORDER BY o.created_at DESC LIMIT 5";
											// Execute and display
											?>
											<tr>
												<td><strong>ORD-1008</strong></td>
												<td>Jennifer Taylor</td>
												<td class="d-none d-xl-table-cell">Jan 22, 2024</td>
												<td>$149.38</td>
												<td><span class="badge bg-info">Shipped</span></td>
												<td class="d-none d-md-table-cell">
													<a href="#" class="btn btn-sm btn-light">View</a>
												</td>
											</tr>
											<tr>
												<td><strong>ORD-1004</strong></td>
												<td>Emily Davis</td>
												<td class="d-none d-xl-table-cell">Jan 18, 2024</td>
												<td>$54.66</td>
												<td><span class="badge bg-warning">Pending</span></td>
												<td class="d-none d-md-table-cell">
													<a href="#" class="btn btn-sm btn-light">View</a>
												</td>
											</tr>
											<tr>
												<td><strong>ORD-1003</strong></td>
												<td>Mike Wilson</td>
												<td class="d-none d-xl-table-cell">Jan 17, 2024</td>
												<td>$244.46</td>
												<td><span class="badge bg-success">Delivered</span></td>
												<td class="d-none d-md-table-cell">
													<a href="#" class="btn btn-sm btn-light">View</a>
												</td>
											</tr>
											<tr>
												<td><strong>ORD-1002</strong></td>
												<td>Sarah Johnson</td>
												<td class="d-none d-xl-table-cell">Jan 16, 2024</td>
												<td>$93.17</td>
												<td><span class="badge bg-success">Delivered</span></td>
												<td class="d-none d-md-table-cell">
													<a href="#" class="btn btn-sm btn-light">View</a>
												</td>
											</tr>
											<tr>
												<td><strong>ORD-1001</strong></td>
												<td>John Smith</td>
												<td class="d-none d-xl-table-cell">Jan 15, 2024</td>
												<td>$171.96</td>
												<td><span class="badge bg-success">Delivered</span></td>
												<td class="d-none d-md-table-cell">
													<a href="#" class="btn btn-sm btn-light">View</a>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>

						<!-- Top Products -->
						<div class="col-12 col-lg-4 col-xxl-3 d-flex">
							<div class="card flex-fill w-100">
								<div class="card-header">
									<div class="card-actions float-end">
										<a href="products.php" class="btn btn-sm btn-primary">View All</a>
									</div>
									<h5 class="card-title mb-0">Top Selling Products</h5>
								</div>
								<div class="card-body">
									<div class="mb-3">
										<div class="d-flex align-items-center mb-3">
											<div class="flex-shrink-0">
												<img src="uploads/products/690ee7078e3aa_tshirt1.webp" class="rounded"
													width="50" height="50" alt="Product">
											</div>
											<div class="flex-grow-1 ms-3">
												<strong>T shirt</strong>
												<div class="text-muted">Electronics</div>
											</div>
											<div class="text-end">
												<strong>$599</strong>
												<div class="text-success small">45 sold</div>
											</div>
										</div>
										<div class="d-flex align-items-center mb-3">
											<div class="flex-shrink-0">
												<img src="uploads/products/691ae533c3857_wireless.jpg" class="rounded"
													width="50" height="50" alt="Product">
											</div>
											<div class="flex-grow-1 ms-3">
												<strong>Bluetooth Headphones</strong>
												<div class="text-muted">Electronics</div>
											</div>
											<div class="text-end">
												<strong>$149.97</strong>
												<div class="text-success small">38 sold</div>
											</div>
										</div>
										<div class="d-flex align-items-center mb-3">
											<div class="flex-shrink-0">
												<img src="uploads/products/691ae624b9dc1_gamingkeykoard1.jpg"
													class="rounded" width="50" height="50" alt="Product">
											</div>
											<div class="flex-grow-1 ms-3">
												<strong>Gaming Keyboard</strong>
												<div class="text-muted">Electronics</div>
											</div>
											<div class="text-end">
												<strong>$199.99</strong>
												<div class="text-success small">32 sold</div>
											</div>
										</div>
										<div class="d-flex align-items-center">
											<div class="flex-shrink-0">
												<img src="uploads/products/692378c6b3c66_shirt-1.jpeg" class="rounded"
													width="50" height="50" alt="Product">
											</div>
											<div class="flex-grow-1 ms-3">
												<strong>AzureSpark Polo</strong>
												<div class="text-muted">Men's Fashion</div>
											</div>
											<div class="text-end">
												<strong>$587</strong>
												<div class="text-success small">28 sold</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Fourth Row: Quick Stats -->
					<div class="row">
						<!-- Category Distribution -->
						<div class="col-xl-4 col-lg-6">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title mb-0">Category Distribution</h5>
								</div>
								<div class="card-body">
									<div class="chart chart-sm">
										<canvas id="categoryChart"></canvas>
									</div>
								</div>
							</div>
						</div>

						<!-- Low Stock Alert -->
						<div class="col-xl-4 col-lg-6">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title mb-0">Low Stock Alert</h5>
								</div>
								<div class="card-body">
									<div class="list-group list-group-flush">
										<?php
										// Low stock products query
										$lowStockQuery = "SELECT name, stock_quantity FROM products 
                                            WHERE stock_quantity < 10 AND status = 'active' 
                                            ORDER BY stock_quantity ASC LIMIT 5";
										// Execute and display
										?>
										<div class="list-group-item px-0">
											<div class="d-flex align-items-center">
												<div class="flex-grow-1">
													<strong>iPhone 15 Pro</strong>
													<div class="text-muted">Electronics</div>
												</div>
												<div class="text-end">
													<span class="badge bg-danger">2 left</span>
												</div>
											</div>
										</div>
										<div class="list-group-item px-0">
											<div class="d-flex align-items-center">
												<div class="flex-grow-1">
													<strong>Gaming Laptop RTX</strong>
													<div class="text-muted">Electronics</div>
												</div>
												<div class="text-end">
													<span class="badge bg-warning">5 left</span>
												</div>
											</div>
										</div>
										<div class="list-group-item px-0">
											<div class="d-flex align-items-center">
												<div class="flex-grow-1">
													<strong>Designer Evening Gown</strong>
													<div class="text-muted">Women's Fashion</div>
												</div>
												<div class="text-end">
													<span class="badge bg-warning">8 left</span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Recent Customers -->
						<div class="col-xl-4 col-lg-6">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title mb-0">Recent Customers</h5>
								</div>
								<div class="card-body">
									<div class="list-group list-group-flush">
										<?php
										// Recent customers query
										$recentCustomersQuery = "SELECT u.*, c.registration_date 
                                                   FROM customers c 
                                                   JOIN users u ON c.user_id = u.id 
                                                   ORDER BY c.registration_date DESC LIMIT 5";
										// Execute and display
										?>
										<div class="list-group-item px-0">
											<div class="d-flex align-items-center">
												<div class="flex-shrink-0">
													<div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
														style="width: 40px; height: 40px;">
														<span class="fw-bold">NG</span>
													</div>
												</div>
												<div class="flex-grow-1 ms-3">
													<strong>Nikhil Gupta</strong>
													<div class="text-muted">iamnikhilgupta8@gmail.com</div>
												</div>
												<div class="text-end">
													<small class="text-muted">Nov 24</small>
												</div>
											</div>
										</div>
										<div class="list-group-item px-0">
											<div class="d-flex align-items-center">
												<div class="flex-shrink-0">
													<div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
														style="width: 40px; height: 40px;">
														<span class="fw-bold">MK</span>
													</div>
												</div>
												<div class="flex-grow-1 ms-3">
													<strong>Mohit Kumar</strong>
													<div class="text-muted">mohit@gmail.com</div>
												</div>
												<div class="text-end">
													<small class="text-muted">Nov 27</small>
												</div>
											</div>
										</div>
										<div class="list-group-item px-0">
											<div class="d-flex align-items-center">
												<div class="flex-shrink-0">
													<div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
														style="width: 40px; height: 40px;">
														<span class="fw-bold">AS</span>
													</div>
												</div>
												<div class="flex-grow-1 ms-3">
													<strong>Amit Singh</strong>
													<div class="text-muted">amit.testing@example.com</div>
												</div>
												<div class="text-end">
													<small class="text-muted">Nov 27</small>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>
			</main>

			<!-- JavaScript for Charts -->
			<script>
				document.addEventListener('DOMContentLoaded', function () {
					// Revenue Chart
					var revenueCtx = document.getElementById('revenueChart').getContext('2d');
					var revenueChart = new Chart(revenueCtx, {
						type: 'line',
						data: {
							labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
							datasets: [{
								label: 'Revenue ($)',
								data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
								borderColor: '#4e73df',
								backgroundColor: 'rgba(78, 115, 223, 0.05)',
								pointBackgroundColor: '#4e73df',
								pointBorderColor: '#4e73df',
								pointHoverBackgroundColor: '#fff',
								pointHoverBorderColor: '#4e73df',
								fill: true,
								tension: 0.4
							}]
						},
						options: {
							maintainAspectRatio: false,
							plugins: {
								legend: {
									display: false
								}
							},
							scales: {
								y: {
									beginAtZero: true,
									ticks: {
										callback: function (value) {
											return '$' + value.toLocaleString();
										}
									}
								}
							}
						}
					});

					// Order Status Chart
					var orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
					var orderStatusChart = new Chart(orderStatusCtx, {
						type: 'doughnut',
						data: {
							labels: ['Pending', 'Processing', 'Shipped', 'Delivered'],
							datasets: [{
								data: [24, 18, 32, 26],
								backgroundColor: [
									'#4e73df',
									'#36b9cc',
									'#1cc88a',
									'#f6c23e'
								],
								hoverBackgroundColor: [
									'#2e59d9',
									'#2c9faf',
									'#17a673',
									'#dda20a'
								],
								borderWidth: 2
							}]
						},
						options: {
							maintainAspectRatio: false,
							cutout: '70%',
							plugins: {
								legend: {
									display: false
								}
							}
						}
					});

					// Category Chart
					var categoryCtx = document.getElementById('categoryChart').getContext('2d');
					var categoryChart = new Chart(categoryCtx, {
						type: 'bar',
						data: {
							labels: ['Electronics', 'Fashion', 'Home Decor', 'Gifts', 'Stationary'],
							datasets: [{
								label: 'Products',
								data: [45, 32, 18, 15, 12],
								backgroundColor: [
									'#4e73df',
									'#1cc88a',
									'#36b9cc',
									'#f6c23e',
									'#e74a3b'
								],
								borderWidth: 0
							}]
						},
						options: {
							maintainAspectRatio: false,
							plugins: {
								legend: {
									display: false
								}
							},
							scales: {
								y: {
									beginAtZero: true,
									ticks: {
										stepSize: 10
									}
								}
							}
						}
					});
				});
			</script>

			<!-- CSS for Dashboard -->
			<style>
				.card {
					border: 1px solid #e3e6f0;
					border-radius: 0.35rem;
					box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
				}

				.card:hover {
					box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
					transition: box-shadow 0.3s ease-in-out;
				}

				.stat {
					width: 3rem;
					height: 3rem;
					background: #f8f9fc;
					border-radius: 50%;
					display: flex;
					align-items: center;
					justify-content: center;
				}

				.badge-success-light {
					color: #1cc88a;
					background-color: rgba(28, 200, 138, 0.1);
				}

				.badge-primary-light {
					color: #4e73df;
					background-color: rgba(78, 115, 223, 0.1);
				}

				.badge-warning-light {
					color: #f6c23e;
					background-color: rgba(246, 194, 62, 0.1);
				}

				.badge-danger-light {
					color: #e74a3b;
					background-color: rgba(231, 74, 59, 0.1);
				}

				.list-group-item {
					border: none;
					padding: 0.75rem 0;
				}

				.list-group-item:first-child {
					padding-top: 0;
				}

				.list-group-item:last-child {
					padding-bottom: 0;
				}
			</style>

			<?php
			include_once "includes/footer.php";
			?>
		</div>
	</div>

	<script src="js/app.js"></script>

	<script>
		document.addEventListener("DOMContentLoaded", function () {
			var ctx = document.getElementById("chartjs-dashboard-line").getContext("2d");
			var gradientLight = ctx.createLinearGradient(0, 0, 0, 225);
			gradientLight.addColorStop(0, "rgba(215, 227, 244, 1)");
			gradientLight.addColorStop(1, "rgba(215, 227, 244, 0)");
			var gradientDark = ctx.createLinearGradient(0, 0, 0, 225);
			gradientDark.addColorStop(0, "rgba(51, 66, 84, 1)");
			gradientDark.addColorStop(1, "rgba(51, 66, 84, 0)");
			// Line chart
			new Chart(document.getElementById("chartjs-dashboard-line"), {
				type: "line",
				data: {
					labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
					datasets: [{
						label: "Sales ($)",
						fill: true,
						backgroundColor: window.theme.id === "light" ? gradientLight : gradientDark,
						borderColor: window.theme.primary,
						data: [
							2115,
							1562,
							1584,
							1892,
							1587,
							1923,
							2566,
							2448,
							2805,
							3438,
							2917,
							3327
						]
					}]
				},
				options: {
					maintainAspectRatio: false,
					legend: {
						display: false
					},
					tooltips: {
						intersect: false
					},
					hover: {
						intersect: true
					},
					plugins: {
						filler: {
							propagate: false
						}
					},
					scales: {
						xAxes: [{
							reverse: true,
							gridLines: {
								color: "rgba(0,0,0,0.0)"
							}
						}],
						yAxes: [{
							ticks: {
								stepSize: 1000
							},
							display: true,
							borderDash: [3, 3],
							gridLines: {
								color: "rgba(0,0,0,0.0)",
								fontColor: "#fff"
							}
						}]
					}
				}
			});
		});
	</script>
	<script>
		document.addEventListener("DOMContentLoaded", function () {
			// Pie chart
			new Chart(document.getElementById("chartjs-dashboard-pie"), {
				type: "pie",
				data: {
					labels: ["Chrome", "Firefox", "IE", "Other"],
					datasets: [{
						data: [4306, 3801, 1689, 3251],
						backgroundColor: [
							window.theme.primary,
							window.theme.warning,
							window.theme.danger,
							"#E8EAED"
						],
						borderWidth: 5,
						borderColor: window.theme.white
					}]
				},
				options: {
					responsive: !window.MSInputMethodContext,
					maintainAspectRatio: false,
					legend: {
						display: false
					},
					cutoutPercentage: 70
				}
			});
		});
	</script>
	<script>
		document.addEventListener("DOMContentLoaded", function () {
			// Bar chart
			new Chart(document.getElementById("chartjs-dashboard-bar"), {
				type: "bar",
				data: {
					labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
					datasets: [{
						label: "This year",
						backgroundColor: window.theme.primary,
						borderColor: window.theme.primary,
						hoverBackgroundColor: window.theme.primary,
						hoverBorderColor: window.theme.primary,
						data: [54, 67, 41, 55, 62, 45, 55, 73, 60, 76, 48, 79],
						barPercentage: .75,
						categoryPercentage: .5
					}]
				},
				options: {
					maintainAspectRatio: false,
					legend: {
						display: false
					},
					scales: {
						yAxes: [{
							gridLines: {
								display: false
							},
							stacked: false,
							ticks: {
								stepSize: 20
							}
						}],
						xAxes: [{
							stacked: false,
							gridLines: {
								color: "transparent"
							}
						}]
					}
				}
			});
		});
	</script>
	<script>
		document.addEventListener("DOMContentLoaded", function () {
			var markers = [{
				coords: [31.230391, 121.473701],
				name: "Shanghai"
			},
			{
				coords: [28.704060, 77.102493],
				name: "Delhi"
			},
			{
				coords: [6.524379, 3.379206],
				name: "Lagos"
			},
			{
				coords: [35.689487, 139.691711],
				name: "Tokyo"
			},
			{
				coords: [23.129110, 113.264381],
				name: "Guangzhou"
			},
			{
				coords: [40.7127837, -74.0059413],
				name: "New York"
			},
			{
				coords: [34.052235, -118.243683],
				name: "Los Angeles"
			},
			{
				coords: [41.878113, -87.629799],
				name: "Chicago"
			},
			{
				coords: [51.507351, -0.127758],
				name: "London"
			},
			{
				coords: [40.416775, -3.703790],
				name: "Madrid "
			}
			];
			var map = new jsVectorMap({
				map: "world",
				selector: "#world_map",
				zoomButtons: true,
				markers: markers,
				markerStyle: {
					initial: {
						r: 9,
						stroke: window.theme.white,
						strokeWidth: 7,
						stokeOpacity: .4,
						fill: window.theme.primary
					},
					hover: {
						fill: window.theme.primary,
						stroke: window.theme.primary
					}
				},
				regionStyle: {
					initial: {
						fill: window.theme["gray-200"]
					}
				},
				zoomOnScroll: false
			});
			window.addEventListener("resize", () => {
				map.updateSize();
			});
			setTimeout(function () {
				map.updateSize();
			}, 250);
		});
	</script>
	<script>
		document.addEventListener("DOMContentLoaded", function () {
			var date = new Date(Date.now() - 5 * 24 * 60 * 60 * 1000);
			var defaultDate = date.getUTCFullYear() + "-" + (date.getUTCMonth() + 1) + "-" + date.getUTCDate();
			document.getElementById("datetimepicker-dashboard").flatpickr({
				inline: true,
				prevArrow: "<span class=\"fas fa-chevron-left\" title=\"Previous month\"></span>",
				nextArrow: "<span class=\"fas fa-chevron-right\" title=\"Next month\"></span>",
				defaultDate: defaultDate
			});
		});
	</script>

	<script>
		document.addEventListener("DOMContentLoaded", function (event) {
			setTimeout(function () {
				if (localStorage.getItem('popState') !== 'shown') {
					window.notyf.open({
						type: "success",
						message: "Get access to all 500+ components and 45+ pages with AdminKit PRO. <u><a class=\"text-white\" href=\"https://adminkit.io/pricing\" target=\"_blank\">More info</a></u> 🚀",
						duration: 10000,
						ripple: true,
						dismissible: false,
						position: {
							x: "left",
							y: "bottom"
						}
					});

					localStorage.setItem('popState', 'shown');
				}
			}, 15000);
		});
	</script>
</body>

</html>