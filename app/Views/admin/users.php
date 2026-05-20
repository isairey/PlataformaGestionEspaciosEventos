<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - CSPC Admin</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/admin/users.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
</head>
<body>
    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>CSPC Admin</h3>
        </div>
        
        <div class="sidebar-menu">
            <ul>
                <li><a href="<?= base_url('/admin') ?>" class="menu-item"><i>📊</i> Dashboard</a></li>
                <li><a href="<?= base_url('/admin/users') ?>" class="menu-item active"><i>👥</i> Users</a></li>

                <!-- Dropdown for Booking -->
                <li class="dropdown">
                    <button class="dropdown-toggle">
                        <span class="dropdown-label">
                            <i>🏢</i> Booking
                        </span>
                        <span class="arrow">▾</span>
                    </button>
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
        <!-- Users Content -->
        <div class="dashboard">
            <!-- Header Section -->
            <div class="users-header">
                <div>
                    <h2>Users Management</h2>
                    <p>Manage system users and their permissions</p>
                </div>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <span>➕</span> Add New User
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="users-stats-cards">
                <div class="stat-card-mini">
                    <div class="stat-icon-mini total">👥</div>
                    <div class="stat-content-mini">
                        <div class="stat-value-mini" id="totalUsersCount">0</div>
                        <div class="stat-label-mini">Total Users</div>
                    </div>
                </div>
                <div class="stat-card-mini">
                    <div class="stat-icon-mini students">🎓</div>
                    <div class="stat-content-mini">
                        <div class="stat-value-mini" id="studentsCount">0</div>
                        <div class="stat-label-mini">Students</div>
                    </div>
                </div>
                <div class="stat-card-mini">
                    <div class="stat-icon-mini facilitators">👨‍🏫</div>
                    <div class="stat-content-mini">
                        <div class="stat-value-mini" id="facilitatorsCount">0</div>
                        <div class="stat-label-mini">Facilitators</div>
                    </div>
                </div>
                <div class="stat-card-mini">
                    <div class="stat-icon-mini employee">👨‍🎓</div>
                    <div class="stat-content-mini">
                        <div class="stat-value-mini" id="employeeCount">0</div>
                        <div class="stat-label-mini">Employee</div>
                    </div>
                </div>
                <div class="stat-card-mini">
                    <div class="stat-icon-mini admins">👑</div>
                    <div class="stat-content-mini">
                        <div class="stat-value-mini" id="adminsCount">0</div>
                        <div class="stat-label-mini">Admins</div>
                    </div>
                </div>
            </div>
            
            <!-- Search and Filter Section -->
            <div class="search-filter-section">
                <input 
                    type="text" 
                    id="searchInput" 
                    class="search-input" 
                    placeholder="🔍 Search by name, email, or contact..." 
                    onkeyup="filterUsers()"
                >
                
                <div class="filter-group">
                    <select id="roleFilter" class="filter-select" onchange="filterByRole()">
                        <option value="all">All Roles</option>
                        <option value="student">Students</option>
                        <option value="facilitator">Facilitators</option>
                        <option value="employee">Employees</option>
                        <option value="user">Users</option>
                        <option value="admin">Admins</option>
                    </select>
                </div>

                <button class="btn btn-secondary" onclick="clearFilters()">
                    <span>🔄</span> Clear Filters
                </button>
                
                <div style="margin-left: auto; color: #666; font-size: 14px;">
                    Showing <strong id="totalUsers">0</strong> users
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <!-- Users will be populated here by JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination" id="pagination"></div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New User</h2>
                <button class="close" onclick="closeModal('addModal')">×</button>
            </div>
            <form id="addForm" onsubmit="addUser(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" required placeholder="Enter full name">
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required placeholder="Enter email address">
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" placeholder="Enter contact number">
                    </div>
                    
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" required placeholder="Enter password">
                    </div>
                    
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" required>
                            <option value="student">Student</option>
                            <option value="facilitator">Facilitator</option>
                            <option value="employee">Employee</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span>➕</span> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <button class="close" onclick="closeModal('editModal')">×</button>
            </div>
            <form id="editForm" onsubmit="updateUser(event)">
                <div class="modal-body">
                    <input type="hidden" name="id" id="editUserId">
                    
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" id="editfull_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" id="editEmail" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" id="editContactNumber">
                    </div>
                    
                    <div class="form-group">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="password" id="editPassword" placeholder="Leave blank to keep current password">
                    </div>
                    
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" id="editRole" required>
                            <option value="student">Student</option>
                            <option value="facilitator">Facilitator</option>
                            <option value="employee">Employee</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <span>💾</span> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= base_url('js/admin/users.js') ?>"></script>
</body>
</html>