<footer class="footer">
				<div class="container-fluid">
					<div class="row text-muted">
						<div class="col-6 text-start">
							<p class="mb-0">
								<a href="https://adminkit.io/" target="_blank" class="text-muted"><strong>AdminKit</strong></a> &copy;
							</p>
						</div>
						<div class="col-6 text-end">
							<ul class="list-inline">
								<li class="list-inline-item">
									<a class="text-muted" href="#">Support</a>
								</li>
								<li class="list-inline-item">
									<a class="text-muted" href="#">Help Center</a>
								</li>
								<li class="list-inline-item">
									<a class="text-muted" href="#">Privacy</a>
								</li>
								<li class="list-inline-item">
									<a class="text-muted" href="#">Terms</a>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</footer>



			<!-- this is search bar on admin top bar  -->

			<script>
// Global search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('globalSearchInput');
    const searchForm = document.getElementById('globalSearchForm');
    const searchResults = document.getElementById('searchResults');
    const searchResultsBody = document.getElementById('searchResultsBody');
    const resultsCount = document.getElementById('resultsCount');
    
    // Extract all menu items from sidebar
    const menuItems = extractMenuItems();
    
    // Search input event listener
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.trim().toLowerCase();
        
        if (searchTerm.length < 2) {
            hideSearchResults();
            return;
        }
        
        const results = searchMenuItems(searchTerm, menuItems);
        displaySearchResults(results, searchTerm);
    });
    
    // Focus events
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            const searchTerm = this.value.trim().toLowerCase();
            const results = searchMenuItems(searchTerm, menuItems);
            displaySearchResults(results, searchTerm);
        }
    });
    
    // Click outside to close results
    document.addEventListener('click', function(e) {
        if (!searchForm.contains(e.target)) {
            hideSearchResults();
        }
    });
    
    // Form submission prevention
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        performSearch();
    });
    
    // Extract all menu items from sidebar
    function extractMenuItems() {
        const items = [];
        const sidebar = document.getElementById('sidebar');
        
        if (!sidebar) return items;
        
        // Get all sidebar links
        const links = sidebar.querySelectorAll('.sidebar-link');
        
        links.forEach(link => {
            const text = link.textContent.trim();
            const href = link.getAttribute('href');
            const parentLi = link.closest('.sidebar-item');
            const parentHeader = getParentHeader(link);
            
            if (text && href && !href.startsWith('#')) {
                items.push({
                    text: text,
                    href: href,
                    category: parentHeader,
                    element: link
                });
            }
        });
        
        return items;
    }
    
    // Get parent header/category for menu item
    function getParentHeader(element) {
        let current = element.parentElement;
        while (current) {
            if (current.classList.contains('sidebar-header')) {
                return current.textContent.trim();
            }
            if (current.classList.contains('sidebar-dropdown')) {
                const header = current.previousElementSibling;
                if (header && header.classList.contains('sidebar-link')) {
                    return header.textContent.trim();
                }
            }
            current = current.parentElement;
        }
        return 'General';
    }
    
    // Search through menu items
    function searchMenuItems(searchTerm, items) {
        return items.filter(item => {
            const itemText = item.text.toLowerCase();
            const itemCategory = item.category.toLowerCase();
            
            return itemText.includes(searchTerm) || 
                   itemCategory.includes(searchTerm) ||
                   itemText.split(' ').some(word => word.startsWith(searchTerm));
        });
    }
    
    // Display search results
    function displaySearchResults(results, searchTerm) {
        searchResultsBody.innerHTML = '';
        
        if (results.length === 0) {
            searchResultsBody.innerHTML = `
                <div class="p-3 text-center text-muted">
                    <i class="fas fa-search me-2"></i>
                    No results found for "<strong>${searchTerm}</strong>"
                </div>
            `;
        } else {
            // Group results by category
            const groupedResults = groupByCategory(results);
            
            Object.keys(groupedResults).forEach(category => {
                const categoryResults = groupedResults[category];
                
                // Add category header
                const categoryHeader = document.createElement('div');
                categoryHeader.className = 'search-category-header p-2 bg-light border-bottom';
                categoryHeader.innerHTML = `<small class="fw-bold text-uppercase">${category}</small>`;
                searchResultsBody.appendChild(categoryHeader);
                
                // Add category items
                categoryResults.forEach(item => {
                    const resultItem = document.createElement('a');
                    resultItem.className = 'dropdown-item search-result-item p-2';
                    resultItem.href = item.href;
                    resultItem.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="fas fa-link text-muted me-2" style="font-size: 12px;"></i>
                            <div class="flex-grow-1">
                                <div class="fw-medium">${highlightText(item.text, searchTerm)}</div>
                                <small class="text-muted">${item.href}</small>
                            </div>
                        </div>
                    `;
                    
                    resultItem.addEventListener('click', function(e) {
                        hideSearchResults();
                        searchInput.value = '';
                    });
                    
                    searchResultsBody.appendChild(resultItem);
                });
            });
        }
        
        resultsCount.textContent = `${results.length} result${results.length !== 1 ? 's' : ''} found`;
        showSearchResults();
    }
    
    // Group results by category
    function groupByCategory(results) {
        return results.reduce((groups, item) => {
            const category = item.category || 'Other';
            if (!groups[category]) {
                groups[category] = [];
            }
            groups[category].push(item);
            return groups;
        }, {});
    }
    
    // Highlight matching text
    function highlightText(text, searchTerm) {
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.replace(regex, '<mark class="bg-warning px-1 rounded">$1</mark>');
    }
    
    // Show search results dropdown
    function showSearchResults() {
        searchResults.style.display = 'block';
        searchResults.style.position = 'absolute';
        searchResults.style.top = '100%';
        searchResults.style.left = '0';
        searchResults.style.zIndex = '1000';
        searchResults.style.minWidth = '300px';
    }
    
    // Hide search results dropdown
    function hideSearchResults() {
        searchResults.style.display = 'none';
    }
    
    // Perform search and navigate
    function performSearch() {
        const searchTerm = searchInput.value.trim();
        if (searchTerm.length < 2) return;
        
        const results = searchMenuItems(searchTerm.toLowerCase(), menuItems);
        if (results.length > 0) {
            // Navigate to first result
            window.location.href = results[0].href;
        }
    }
    
    // Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const firstResult = searchResultsBody.querySelector('.search-result-item');
            if (firstResult) firstResult.focus();
        }
        
        if (e.key === 'Escape') {
            hideSearchResults();
            searchInput.blur();
        }
    });
    
    // Close results when pressing Enter without specific result focused
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
});
</script>

<script>
// Notification functionality
document.addEventListener('DOMContentLoaded', function() {
    const notificationDropdown = document.getElementById('alertsDropdown');
    const notificationList = document.getElementById('notificationList');
    const markAllReadBtn = document.getElementById('markAllRead');
    
    // Mark notification as read when clicked
    document.addEventListener('click', function(e) {
        const notificationItem = e.target.closest('.notification-item');
        if (notificationItem) {
            const notificationId = notificationItem.getAttribute('data-notification-id');
            markAsRead(notificationId, notificationItem);
        }
    });
    
    // Mark all as read
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            markAllAsRead();
        });
    }
    
    // Auto-refresh notifications every 30 seconds
    setInterval(refreshNotifications, 30000);
    
    // Refresh notifications when dropdown is opened
    notificationDropdown.addEventListener('show.bs.dropdown', function() {
        refreshNotifications();
    });
    
    function markAsRead(notificationId, element) {
        fetch('api/notifications/mark_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.classList.remove('unread');
                const badge = element.querySelector('.badge');
                if (badge) badge.remove();
                updateNotificationCount();
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    function markAllAsRead() {
        fetch('api/notifications/mark_all_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                    const badge = item.querySelector('.badge');
                    if (badge) badge.remove();
                });
                updateNotificationCount();
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    function refreshNotifications() {
        fetch('api/notifications/get.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationList(data.notifications);
                updateNotificationCount(data.unread_count);
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    function updateNotificationList(notifications) {
        if (notifications.length === 0) {
            notificationList.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-bell-slash fa-2x mb-2"></i>
                    <div>No notifications</div>
                </div>
            `;
        } else {
            let html = '';
            notifications.forEach(notification => {
                const isUnread = !notification.is_read;
                html += `
                    <a href="${notification.link || '#'}" 
                       class="list-group-item notification-item ${isUnread ? 'unread' : ''}" 
                       data-notification-id="${notification.id}">
                        <div class="row g-0 align-items-center">
                            <div class="col-2">
                                <i class="text-${notification.color}" data-feather="${notification.icon}"></i>
                            </div>
                            <div class="col-10">
                                <div class="text-dark">${notification.title}</div>
                                <div class="text-muted small mt-1">${notification.message}</div>
                                <div class="text-muted small mt-1">
                                    ${notification.time_ago}
                                    ${isUnread ? '<span class="badge bg-primary ms-2">New</span>' : ''}
                                </div>
                            </div>
                        </div>
                    </a>
                `;
            });
            notificationList.innerHTML = html;
            
            // Refresh Feather icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    }
    
    function updateNotificationCount(count = null) {
        const indicator = document.querySelector('.indicator');
        
        if (count === null) {
            // Fetch current count
            fetch('api/notifications/unread_count.php')
            .then(response => response.json())
            .then(data => {
                count = data.unread_count;
                updateIndicator(count);
            })
            .catch(error => console.error('Error:', error));
        } else {
            updateIndicator(count);
        }
        
        function updateIndicator(count) {
            if (count > 0) {
                if (!indicator) {
                    const iconContainer = document.querySelector('.nav-icon .position-relative');
                    iconContainer.innerHTML += `<span class="indicator">${count > 9 ? '9+' : count}</span>`;
                } else {
                    indicator.textContent = count > 9 ? '9+' : count;
                    indicator.style.display = 'block';
                }
                
                // Update dropdown header
                const header = document.querySelector('.dropdown-menu-header');
                if (header) {
                    header.textContent = `${count} New Notification${count !== 1 ? 's' : ''}`;
                }
            } else {
                if (indicator) {
                    indicator.style.display = 'none';
                }
                const header = document.querySelector('.dropdown-menu-header');
                if (header) {
                    header.textContent = 'No new notifications';
                }
            }
        }
    }
});
</script>