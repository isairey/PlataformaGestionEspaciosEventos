<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC Admin - Attendance Management</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/attendance.css') ?>">
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
                <li><a href="<?= base_url('/admin') ?>" class="menu-item"><i>📊</i> Dashboard</a></li>
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
                <li><a href="<?= base_url('/admin/attendance') ?>" class="menu-item active"><i>📋</i> Attendance</a></li>
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

        <!-- Attendance Management Page Content -->
        <div class="attendance-management-page">
            <div class="page-title">
                <h2>Attendance Management</h2>
                <p>Track and manage attendance for confirmed bookings and events</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-number" id="totalEventsCount">-</div>
                    <div class="stat-label">Total Events</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number" id="totalGuestsCount">-</div>
                    <div class="stat-label">Total Registered Guests</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number" id="totalAttendedCount">-</div>
                    <div class="stat-label">Total Attended</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number" id="overallAttendanceRate">-</div>
                    <div class="stat-label">Overall Attendance Rate</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filters-section">
                <div class="filter-group">
                    <label>Date Range:</label>
                    <input type="date" id="startDate" class="filter-input">
                    <span>to</span>
                    <input type="date" id="endDate" class="filter-input">
                </div>
                <div class="filter-group">
                    <label>Status:</label>
                    <select id="statusFilter" class="filter-input" onchange="filterEvents()">
                        <option value="">All Events</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <button class="btn-primary" onclick="applyDateFilter()">Apply Filter</button>
                <button class="btn-secondary" onclick="clearFilters()">Clear</button>
            </div>

            <!-- Events Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Confirmed Events with Attendance</h3>
                    <button class="btn-export" onclick="exportAllAttendance()">
                        <i>📥</i> Export to Excel
                    </button>
                </div>

                <div class="loading-spinner" id="loadingSpinner" style="display: none;">
                    <div class="spinner"></div>
                    <p>Loading events...</p>
                </div>

                <div id="noEventsMessage" style="display: none;" class="no-data-message">
                    <i>📅</i>
                    <p>No confirmed events found</p>
                </div>

                <table id="eventsTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>Event ID</th>
                            <th>Event Title</th>
                            <th>Facility</th>
                            <th>Client</th>
                            <th>Date & Time</th>
                            <th>Total Guests</th>
                            <th>Attended</th>
                            <th>Pending</th>
                            <th>Attendance Rate</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="eventsTableBody">
                        <!-- Dynamic content will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Guest List Modal -->
    <div id="guestModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Guest List & Attendance</h3>
                <span class="close" onclick="closeGuestModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Event Details -->
                <div class="event-details-card">
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Event:</strong>
                            <span id="modalEventTitle"></span>
                        </div>
                        <div class="detail-item">
                            <strong>Facility:</strong>
                            <span id="modalFacility"></span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Client:</strong>
                            <span id="modalClient"></span>
                        </div>
                        <div class="detail-item">
                            <strong>Date & Time:</strong>
                            <span id="modalDateTime"></span>
                        </div>
                    </div>
                </div>

                <!-- Guest Statistics -->
                <div class="guest-stats">
                    <div class="guest-stat-item">
                        <div class="stat-value" id="modalTotalGuests">0</div>
                        <div class="stat-label">Total Guests</div>
                    </div>
                    <div class="guest-stat-item success">
                        <div class="stat-value" id="modalAttended">0</div>
                        <div class="stat-label">Attended</div>
                    </div>
                    <div class="guest-stat-item warning">
                        <div class="stat-value" id="modalPending">0</div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="guest-stat-item info">
                        <div class="stat-value" id="modalAttendanceRate">0%</div>
                        <div class="stat-label">Attendance Rate</div>
                    </div>
                </div>

                <!-- Guest Table -->
                <div class="guest-table-container">
                    <table class="guest-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Guest Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>QR Code</th>
                                <th>Status</th>
                                <th>Check-in Time</th>
                            </tr>
                        </thead>
                        <tbody id="guestTableBody">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeGuestModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qrModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h3>Guest QR Code</h3>
                <span class="close" onclick="closeQRModal()">&times;</span>
            </div>
            <div class="modal-body text-center">
                <h4 id="qrGuestName"></h4>
                <div id="qrCodeContainer" class="qr-code-container">
                    <!-- QR code image will be loaded here -->
                </div>
                <p class="qr-code-value">QR Code: <strong id="qrCodeValue"></strong></p>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeQRModal()">Close</button>
                <button class="btn-primary" onclick="downloadQRCode()">
                    <i>⬇️</i> Download QR Code
                </button>
            </div>
        </div>
    </div>

    <script src="<?= base_url('js/admin/sidebar.js') ?>"></script>
    <script src="<?= base_url('js/admin/attendance.js') ?>"></script>
</body>
</html>
