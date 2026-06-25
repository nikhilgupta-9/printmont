<?php
// Add this at the top of your file where you include other controllers
require_once(__DIR__ . '/../controllers/NotificationController.php');

// Initialize notification controller
$notificationController = new NotificationController();

// Get notifications for current user
$notifications = $notificationController->getUserNotifications($_SESSION['user_id'], 5);
$unread_count = $notificationController->getUnreadCount($_SESSION['user_id']);
?>
<nav class="navbar navbar-expand navbar-light navbar-bg">
	<a class="sidebar-toggle js-sidebar-toggle">
		<i class="hamburger align-self-center"></i>
	</a>

	<form class="d-none d-sm-inline-block position-relative" id="globalSearchForm">
		<div class="input-group input-group-navbar">
			<input type="text" class="form-control" placeholder="Search menu items..." aria-label="Search"
				id="globalSearchInput">
			<button class="btn" type="submit">
				<i class="align-middle" data-feather="search"></i>
			</button>
		</div>

		<!-- Search Results Dropdown -->
		<div class="search-results dropdown-menu" id="searchResults"
			style="display: none; width: 100%; max-height: 400px; overflow-y: auto;">
			<div class="search-results-header p-2 border-bottom">
				<small class="text-muted">Menu Search Results</small>
			</div>
			<div class="search-results-body" id="searchResultsBody">
				<!-- Results will be populated here -->
			</div>
			<div class="search-results-footer p-2 border-top text-center">
				<small class="text-muted" id="resultsCount">0 results found</small>
			</div>
		</div>
	</form>

	<ul class="navbar-nav d-none d-lg-flex">
		<li class="nav-item px-2 dropdown">
			<a class="nav-link dropdown-toggle" href="#" id="megaDropdown" role="button" data-bs-toggle="dropdown"
				aria-haspopup="true" aria-expanded="false">
				Mega Menu
			</a>
			<div class="dropdown-menu dropdown-menu-start dropdown-mega" aria-labelledby="megaDropdown">
				<div class="d-md-flex align-items-start justify-content-start">
					<div class="dropdown-mega-list">
						<div class="dropdown-header">UI Elements</div>
						<a class="dropdown-item" href="#">Alerts</a>
						<a class="dropdown-item" href="#">Buttons</a>
						<a class="dropdown-item" href="#">Cards</a>
						<a class="dropdown-item" href="#">Carousel</a>
						<a class="dropdown-item" href="#">General</a>
						<a class="dropdown-item" href="#">Grid</a>
						<a class="dropdown-item" href="#">Modals</a>
						<a class="dropdown-item" href="#">Tabs</a>
						<a class="dropdown-item" href="#">Typography</a>
					</div>
					<div class="dropdown-mega-list">
						<div class="dropdown-header">Forms</div>
						<a class="dropdown-item" href="#">Layouts</a>
						<a class="dropdown-item" href="#">Basic Inputs</a>
						<a class="dropdown-item" href="#">Input Groups</a>
						<a class="dropdown-item" href="#">Advanced Inputs</a>
						<a class="dropdown-item" href="#">Editors</a>
						<a class="dropdown-item" href="#">Validation</a>
						<a class="dropdown-item" href="#">Wizard</a>
					</div>
					<div class="dropdown-mega-list">
						<div class="dropdown-header">Tables</div>
						<a class="dropdown-item" href="#">Basic Tables</a>
						<a class="dropdown-item" href="#">Responsive Table</a>
						<a class="dropdown-item" href="#">Table with Buttons</a>
						<a class="dropdown-item" href="#">Column Search</a>
						<a class="dropdown-item" href="#">Muulti Selection</a>
						<a class="dropdown-item" href="#">Ajax Sourced Data</a>
					</div>
				</div>
			</div>
		</li>

		<li class="nav-item dropdown">
			<a class="nav-link dropdown-toggle" href="#" id="resourcesDropdown" role="button" data-bs-toggle="dropdown"
				aria-haspopup="true" aria-expanded="false">
				Resources
			</a>
			<div class="dropdown-menu" aria-labelledby="resourcesDropdown">
				<a class="dropdown-item" href="https://adminkit.io/" target="_blank"><i class="align-middle me-1"
						data-feather="home"></i>
					Homepage</a>
				<a class="dropdown-item" href="https://adminkit.io/docs/" target="_blank"><i class="align-middle me-1"
						data-feather="book-open"></i>
					Documentation</a>
				<a class="dropdown-item" href="https://adminkit.io/docs/getting-started/changelog/" target="_blank"><i
						class="align-middle me-1" data-feather="edit"></i> Changelog</a>
			</div>
		</li>
	</ul>

	<div class="navbar-collapse collapse">
		<ul class="navbar-nav navbar-align">
			<li class="nav-item dropdown">
				<a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
					<div class="position-relative">
						<i class="align-middle" data-feather="bell"></i>
						<span class="indicator">4</span>
					</div>
				</a>


			<li class="nav-item dropdown">
				<a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
					<div class="position-relative">
						<i class="align-middle" data-feather="bell"></i>
						<?php if ($unread_count > 0): ?>
							<span class="indicator"><?php echo $unread_count > 9 ? '9+' : $unread_count; ?></span>
						<?php endif; ?>
					</div>
				</a>
				<div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
					<div class="dropdown-menu-header">
						<?php echo $unread_count; ?> New Notification<?php echo $unread_count != 1 ? 's' : ''; ?>
					</div>
					<div class="list-group" id="notificationList">
						<?php if (empty($notifications)): ?>
							<div class="text-center p-3 text-muted">
								<i class="fas fa-bell-slash fa-2x mb-2"></i>
								<div>No notifications</div>
							</div>
						<?php else: ?>
							<?php foreach ($notifications as $notification): ?>
								<a href="<?php echo $notification['link'] ?: '#'; ?>"
									class="list-group-item notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>"
									data-notification-id="<?php echo $notification['id']; ?>">
									<div class="row g-0 align-items-center">
										<div class="col-2">
											<i class="text-<?php echo $notificationController->getNotificationColor($notification['type']); ?>"
												data-feather="<?php echo $notificationController->getNotificationIcon($notification['type'], $notification['icon']); ?>">
											</i>
										</div>
										<div class="col-10">
											<div class="text-dark"><?php echo htmlspecialchars($notification['title']); ?></div>
											<div class="text-muted small mt-1">
												<?php echo htmlspecialchars($notification['message']); ?>
											</div>
											<div class="text-muted small mt-1">
												<?php echo $notificationController->formatNotificationTime($notification['created_at']); ?>
												<?php if (!$notification['is_read']): ?>
													<span class="badge bg-primary ms-2">New</span>
												<?php endif; ?>
											</div>
										</div>
									</div>
								</a>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div class="dropdown-menu-footer">
						<a href="notifications.php" class="text-muted me-3">Show all notifications</a>
						<?php if ($unread_count > 0): ?>
							<a href="#" class="text-muted" id="markAllRead">Mark all as read</a>
						<?php endif; ?>
					</div>
				</div>
			</li>
			</li>
			<!-- <li class="nav-item dropdown">
							<a class="nav-icon dropdown-toggle" href="#" id="messagesDropdown" data-bs-toggle="dropdown">
								<div class="position-relative">
									<i class="align-middle" data-feather="message-square"></i>
								</div>
							</a>
							<div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="messagesDropdown">
								<div class="dropdown-menu-header">
									<div class="position-relative">
										4 New Messages
									</div>
								</div>
								<div class="list-group">
									<a href="#" class="list-group-item">
										<div class="row g-0 align-items-center">
											<div class="col-2">
												<img src="img/avatars/avatar-5.jpg" class="avatar img-fluid rounded-circle" alt="Vanessa Tucker">
											</div>
											<div class="col-10 ps-2">
												<div class="text-dark">Vanessa Tucker</div>
												<div class="text-muted small mt-1">Nam pretium turpis et arcu. Duis arcu tortor.</div>
												<div class="text-muted small mt-1">15m ago</div>
											</div>
										</div>
									</a>
									<a href="#" class="list-group-item">
										<div class="row g-0 align-items-center">
											<div class="col-2">
												<img src="img/avatars/avatar-2.jpg" class="avatar img-fluid rounded-circle" alt="William Harris">
											</div>
											<div class="col-10 ps-2">
												<div class="text-dark">William Harris</div>
												<div class="text-muted small mt-1">Curabitur ligula sapien euismod vitae.</div>
												<div class="text-muted small mt-1">2h ago</div>
											</div>
										</div>
									</a>
									<a href="#" class="list-group-item">
										<div class="row g-0 align-items-center">
											<div class="col-2">
												<img src="img/avatars/avatar-4.jpg" class="avatar img-fluid rounded-circle" alt="Christina Mason">
											</div>
											<div class="col-10 ps-2">
												<div class="text-dark">Christina Mason</div>
												<div class="text-muted small mt-1">Pellentesque auctor neque nec urna.</div>
												<div class="text-muted small mt-1">4h ago</div>
											</div>
										</div>
									</a>
									<a href="#" class="list-group-item">
										<div class="row g-0 align-items-center">
											<div class="col-2">
												<img src="img/avatars/avatar-3.jpg" class="avatar img-fluid rounded-circle" alt="Sharon Lessman">
											</div>
											<div class="col-10 ps-2">
												<div class="text-dark">Sharon Lessman</div>
												<div class="text-muted small mt-1">Aenean tellus metus, bibendum sed, posuere ac, mattis non.</div>
												<div class="text-muted small mt-1">5h ago</div>
											</div>
										</div>
									</a>
								</div>
								<div class="dropdown-menu-footer">
									<a href="#" class="text-muted">Show all messages</a>
								</div>
							</div>
						</li> -->

			<li class="nav-item">
				<a class="nav-icon js-fullscreen d-none d-lg-block" href="#">
					<div class="position-relative">
						<i class="align-middle" data-feather="maximize"></i>
					</div>
				</a>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-icon pe-md-0 dropdown-toggle" href="#" data-bs-toggle="dropdown">
					<img src="img/avatars/avatar.jpg" class="avatar img-fluid rounded" alt="Charles Hall" />
				</a>
				<div class="dropdown-menu dropdown-menu-end">
					<a class='dropdown-item' href='pages-profile.php'><i class="align-middle me-1"
							data-feather="user"></i> Profile</a>
					<a class="dropdown-item" href="#"><i class="align-middle me-1" data-feather="pie-chart"></i>
						Analytics</a>
					<div class="dropdown-divider"></div>
					<a class='dropdown-item' href='footer-management.php'><i class="align-middle me-1"
							data-feather="settings"></i> Settings &
						Privacy</a>
					<a class="dropdown-item" href="#"><i class="align-middle me-1" data-feather="help-circle"></i> Help
						Center</a>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item logout" href="#">Log out</a>
				</div>
			</li>
		</ul>
	</div>
</nav>