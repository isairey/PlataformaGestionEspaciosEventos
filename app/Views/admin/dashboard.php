<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC Admin Dashboard</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>CSPC Admin</h3>
        </div>
        
       <div class="sidebar-menu">
            <ul>
                <li><a href="<?= base_url('/admin') ?>" class="menu-item active"><i>📊</i> Dashboard</a></li>
                <li><a href="<?= base_url('/admin/users') ?>" class="menu-item"><i>👥</i> Users</a></li>

                <!-- Dropdown for Booking -->
                <li class="dropdown">
                    <a href="#" class="menu-item dropdown-toggle" onclick="toggleDropdown(event)">
                        <i>🏢</i> Booking <span class="arrow">▾</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?= base_url('/admin/external') ?>" class="submenu-item">🌐 External</a></li>
                        <li><a href="<?= base_url('/admin/internal') ?>" class="submenu-item active">🏛️ Internal</a></li>
                    </ul>
                </li>

                <li><a href="<?= base_url('/admin/events') ?>" class="menu-item"><i>📅</i> Events</a></li>
                <li><a href="<?= base_url('/admin/equipment') ?>" class="menu-item"><i>🔧</i> Equipment</a></li>
                <li><a href="<?= base_url('/admin/plans') ?>" class="menu-item"><i>📋</i> Plans</a></li>
                <li><a href="<?= base_url('/admin/facilities-management') ?>" class="menu-item"><i>🏗️</i> Facilities</a></li>

                <div class="sidebar-divider"></div>

                <li><a href="<?= base_url('admin/booking-management') ?>" class="menu-item"><i>📝</i> Bookings</a></li>
                <li><a href="<?= base_url('/admin/attendance') ?>" class="menu-item"><i>📋</i> Attendance</a></li>
                <li><a href="<?= base_url('/admin/file-templates') ?>" class="menu-item"><i>📄</i> File Templates</a></li>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr(session('full_name'), 0, 2)) ?>
                </div>
                <div class="user-details">
                    <?= session('full_name'); ?>
                    <div class="role">Administrator</div>
                </div>
            </div>
            <a href="<?= site_url('logout') ?>" class="logout-btn" title="Logout">🚪</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
            

        </div>
        
        <!-- Dashboard Content -->
        <div class="dashboard">
            <div class="dashboard-title">
                <h2>Dashboard</h2>
                <p>Welcome back, <?= session('full_name') ?>! Here's what's happening with your facilities.</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon users">👥</div>
                    <div class="stat-data">
                        <div class="stat-value">0</div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bookings">📅</div>
                    <div class="stat-data">
                        <div class="stat-value">0</div>
                        <div class="stat-label">Active Bookings</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon facilities">🏢</div>
                    <div class="stat-data">
                        <div class="stat-value">0</div>
                        <div class="stat-label">Total Facilities</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon equipment">🔧</div>
                    <div class="stat-data">
                        <div class="stat-value">0</div>
                        <div class="stat-label">Equipment Items</div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Bookings & Calendar -->
            <div class="dashboard-row">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Bookings</h3>
                        <a href="<?= base_url('admin/booking-management') ?>" class="card-action">View All</a>
                    </div>

                    <!-- Date Filter Header -->
                    <div class="date-filter-header" style="display: none;">
                        <div class="filter-info">
                            <i class="fas fa-calendar-day"></i>
                            <span>Showing bookings for <strong class="filter-date"></strong></span>
                        </div>
                        <button class="clear-filter-btn" onclick="clearDateFilter()">
                            <i class="fas fa-times"></i> Clear Filter
                        </button>
                    </div>

                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Facility</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loading state -->
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem;">
                                    Loading bookings...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Upcoming Events</h3>
                        <a href="<?= base_url('admin/events') ?>" class="card-action">View Calendar</a>
                    </div>
                    
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <div class="calendar-nav">
                                <button id="prevMonthBtn">◀</button>
                                <span id="calendar-month-year">Loading...</span>
                                <button id="nextMonthBtn">▶</button>
                            </div>
                        </div>
                        
                        <div class="calendar-weekdays">
                            <div class="weekday">Sun</div>
                            <div class="weekday">Mon</div>
                            <div class="weekday">Tue</div>
                            <div class="weekday">Wed</div>
                            <div class="weekday">Thu</div>
                            <div class="weekday">Fri</div>
                            <div class="weekday">Sat</div>
                        </div>
                        
                        <div class="calendar" id="calendarGrid">
                            <!-- Calendar days will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Equipment Status -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Equipment Status</h3>
                    <a href="<?= base_url('admin/equipment') ?>" class="card-action">View All Equipment</a>
                </div>
                
                <div class="equipment-grid">
                    <!-- Loading state -->
                    <div style="text-align: center; padding: 2rem; width: 100%;">
                        Loading equipment status...
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add the JavaScript file -->
    <script src="<?= base_url('js/admin/admin-dashboard.js') ?>"></script>
    
    <!-- Optional: Add some inline JavaScript to set current month -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set current month/year in calendar header
            const monthYear = document.getElementById('calendar-month-year');
            if (monthYear) {
                const date = new Date();
                const options = { year: 'numeric', month: 'long' };
                monthYear.textContent = date.toLocaleDateString('en-US', options);
            }

            // Initialize admin notifications
            loadAdminNotifications();
            
            // Refresh notifications every 10 seconds
            setInterval(loadAdminNotifications, 10000);
        });

        // Admin Notification System Functions
        let adminNotificationsData = [];

        function toggleAdminNotificationDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('adminNotificationDropdown');
            dropdown.classList.toggle('show');

            // Close when clicking outside
            document.addEventListener('click', function closeDropdown(e) {
                if (!e.target.closest('#adminNotificationBell') && !e.target.closest('#adminNotificationDropdown')) {
                    dropdown.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }

        function loadAdminNotifications() {
            const notifications = [];

            // Example notifications - you can replace with API calls
            const systemHealth = localStorage.getItem('systemHealth');
            const pendingBookings = localStorage.getItem('pendingBookings');
            const equipmentAlerts = localStorage.getItem('equipmentAlerts');

            // System health notification
            if (systemHealth === 'warning') {
                notifications.push({
                    id: 'system-health',
                    title: 'System Health Alert',
                    message: 'Database connection is running slow',
                    type: 'warning',
                    icon: 'fas fa-exclamation-triangle',
                    timestamp: new Date().toISOString(),
                    unread: !localStorage.getItem('system-health-read')
                });
            }

            // Pending bookings notification
            if (pendingBookings) {
                const count = parseInt(pendingBookings);
                if (count > 0) {
                    notifications.push({
                        id: 'pending-bookings',
                        title: 'Pending Bookings',
                        message: `You have ${count} booking(s) awaiting approval`,
                        type: 'info',
                        icon: 'fas fa-calendar-check',
                        timestamp: new Date().toISOString(),
                        unread: !localStorage.getItem('pending-bookings-read')
                    });
                }
            }

            // Equipment alerts notification
            if (equipmentAlerts) {
                const count = parseInt(equipmentAlerts);
                if (count > 0) {
                    notifications.push({
                        id: 'equipment-alerts',
                        title: 'Equipment Maintenance',
                        message: `${count} item(s) need maintenance`,
                        type: 'warning',
                        icon: 'fas fa-tools',
                        timestamp: new Date().toISOString(),
                        unread: !localStorage.getItem('equipment-alerts-read')
                    });
                }
            }

            adminNotificationsData = notifications;
            renderAdminNotifications();
        }

        function renderAdminNotifications() {
            const notificationList = document.getElementById('adminNotificationList');
            const badge = document.getElementById('adminNotificationBadge');
            const unreadCount = adminNotificationsData.filter(n => n.unread).length;

            // Update badge
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }

            // Render notifications
            if (adminNotificationsData.length === 0) {
                notificationList.innerHTML = `
                    <li class="notification-item">
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>No notifications yet</p>
                        </div>
                    </li>
                `;
            } else {
                notificationList.innerHTML = adminNotificationsData.map(notification => `
                    <li class="notification-item ${notification.unread ? 'unread' : ''} ${notification.type}" onclick="markAdminAsRead('${notification.id}')">
                        <div class="notification-icon">
                            <i class="${notification.icon}"></i>
                        </div>
                        <div class="notification-content">
                            <p class="notification-title">${notification.title}</p>
                            <p class="notification-message">${notification.message}</p>
                            <div class="notification-time">${getAdminTimeAgo(notification.timestamp)}</div>
                        </div>
                    </li>
                `).join('');
            }
        }

        function markAdminAsRead(notificationId) {
            localStorage.setItem(notificationId + '-read', 'true');
            loadAdminNotifications();
        }

        function clearAdminNotifications() {
            adminNotificationsData.forEach(n => {
                localStorage.removeItem(n.id);
                localStorage.removeItem(n.id + '-read');
            });
            adminNotificationsData = [];
            renderAdminNotifications();
        }

        function getAdminTimeAgo(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const secondsAgo = Math.floor((now - date) / 1000);

            if (secondsAgo < 60) return 'Just now';
            if (secondsAgo < 3600) return Math.floor(secondsAgo / 60) + ' min ago';
            if (secondsAgo < 86400) return Math.floor(secondsAgo / 3600) + ' h ago';
            if (secondsAgo < 604800) return Math.floor(secondsAgo / 86400) + ' d ago';
            
            return date.toLocaleDateString();
        }

        // Function to trigger notifications from other scripts
        function addAdminNotification(title, message, type = 'info') {
            const id = 'notification-' + Date.now();
            localStorage.setItem(id, JSON.stringify({
                id: id,
                title: title,
                message: message,
                type: type,
                timestamp: new Date().toISOString()
            }));
            localStorage.removeItem(id + '-read');
            loadAdminNotifications();
        }

        // Function to set system status
        function setSystemAlert(level) {
            localStorage.setItem('systemHealth', level); // 'warning', 'success', null
            loadAdminNotifications();
        }

        function setPendingBookings(count) {
            if (count > 0) {
                localStorage.setItem('pendingBookings', count);
            } else {
                localStorage.removeItem('pendingBookings');
            }
            loadAdminNotifications();
        }

        function setEquipmentAlerts(count) {
            if (count > 0) {
                localStorage.setItem('equipmentAlerts', count);
            } else {
                localStorage.removeItem('equipmentAlerts');
            }
            loadAdminNotifications();
        }
    </script>
</body>
</html>