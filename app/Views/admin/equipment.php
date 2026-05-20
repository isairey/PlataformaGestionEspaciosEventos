<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC Admin - Equipment Management</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/equipment.css') ?>">

</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

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
                <li><a href="<?= base_url('/admin/equipment') ?>" class="menu-item active"><i>🔧</i> Equipment</a></li>
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
            <button class="toggle-btn">☰</button>
            
            <div class="search-box">
                <i>🔍</i>
                <input type="text" placeholder="Search equipment..." id="searchInput">
            </div>
            
    
        </div>
        
        <!-- Equipment Management Content -->
        <div class="dashboard">
            <div class="dashboard-title">
                <h2>Equipment Management</h2>
                <p>Manage all facility equipment and track their status.</p>
            </div>
            
<!-- Equipment Actions -->
<div class="equipment-actions">
    <button class="btn-primary" onclick="openAddModal()">
        ➕ Add Equipment
    </button>
    <button class="btn-secondary" onclick="generateReport()">
        📄 Generate Report
    </button>

    </div>
</div>
            
            
            <!-- Equipment Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Equipment List</h3>
                    <div class="table-actions">
                        <select id="statusFilter" onchange="filterEquipment()">
                            <option value="">All Status</option>
                            <option value="good">Good</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="equipment-table" id="equipmentTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Good</th>
                                <th>Damaged</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="equipmentTableBody">
                            <!-- Equipment rows will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Equipment Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Equipment</h3>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            
            <form id="addEquipmentForm" enctype="multipart/form-data">
                <div style="padding: 20px;">
                    <div class="form-group">
                        <label for="equipmentName">Equipment Name *</label>
                        <input type="text" id="equipmentName" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="equipmentQuantity">Total Quantity *</label>
                        <input type="number" id="equipmentQuantity" name="quantity" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="equipmentPrice">Price (₱) *</label>
                        <input type="number" id="equipmentPrice" name="price" step="0.01" min="0" required>
                    </div>
                    
                    
                    <div class="form-group">
                        <label for="equipmentGood">Good Condition *</label>
                        <input type="number" id="equipmentGood" name="good" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="equipmentDamaged">Damaged/Maintenance</label>
                        <input type="number" id="equipmentDamaged" name="damaged" min="0" value="0">
                        <div id="quantityFeedback" class="validation-feedback"></div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Add Equipment</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Equipment Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Equipment Details</h3>
                <span class="close" onclick="closeViewModal()">&times;</span>
            </div>
            
            <div class="modal-body">
                <div class="equipment-details" id="equipmentDetails">
                    <!-- Equipment details will be populated here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Equipment Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Equipment</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            
            <form id="editEquipmentForm" enctype="multipart/form-data">
                <input type="hidden" id="editEquipmentId" name="id">
                
                <div style="padding: 20px;">
                    <div class="form-group">
                        <label for="editEquipmentName">Equipment Name *</label>
                        <input type="text" id="editEquipmentName" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editEquipmentQuantity">Total Quantity *</label>
                        <input type="number" id="editEquipmentQuantity" name="quantity" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editEquipmentPrice">Price (₱) *</label>
                        <input type="number" id="editEquipmentPrice" name="price" step="0.01" min="0" required>
                    </div>
                    
                    
                    <div class="form-group">
                        <label for="editEquipmentGood">Good Condition *</label>
                        <input type="number" id="editEquipmentGood" name="good" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editEquipmentDamaged">Damaged/Maintenance</label>
                        <input type="number" id="editEquipmentDamaged" name="damaged" min="0" value="0">
                        <div id="editQuantityFeedback" class="validation-feedback"></div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update Equipment</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            
            <div class="modal-body">
                <p>Are you sure you want to delete this equipment? This action cannot be undone.</p>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-danger" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <script src="<?= base_url('js/admin/equipment.js') ?>"></script>
    
</body>
</html>