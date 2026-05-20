<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC Admin - Facilities Management</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/booking.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/facilities-management.css') ?>">
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>
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
                <li><a href="<?= base_url('/admin/facilities-management') ?>" class="menu-item active"><i>🏗️</i> Facilities</a></li>

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
                <input type="text" id="searchFacilities" placeholder="Search facilities...">
            </div>
        </div>

        <!-- Facilities Management Content -->
        <div class="facilities-management-page">
            <div class="page-header">
                <div class="page-title">
                    <h2>Facilities Management</h2>
                    <p>Manage all bookable facilities, maintenance status, and availability</p>
                </div>
                <button class="btn btn-primary" onclick="openAddFacilityModal()">
                    <span>➕</span> Add New Facility
                </button>
            </div>

            <div class="facilities-stats">
                <div class="stat-card">
                    <div class="stat-icon">🏢</div>
                    <div class="stat-info">
                        <div class="stat-value" id="totalFacilities">0</div>
                        <div class="stat-label">Total Facilities</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <div class="stat-value" id="activeFacilities">0</div>
                        <div class="stat-label">Active</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔧</div>
                    <div class="stat-info">
                        <div class="stat-value" id="maintenanceFacilities">0</div>
                        <div class="stat-label">Under Maintenance</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-info">
                        <div class="stat-value" id="inactiveFacilities">0</div>
                        <div class="stat-label">Inactive</div>
                    </div>
                </div>
            </div>

            <!-- Facilities Table -->
            <div class="facilities-table-container">
                <table class="facilities-table" id="facilitiesTable">
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Facility Name</th>
                            <th>Facility Key</th>
                            <th>Capacity</th>
                            <th></th>Description</th>
                            <th>Status</th>
                            <th>Maintenance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="facilitiesTableBody">
                        <!-- Table rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Facility Modal -->
    <div id="facilityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add New Facility</h2>
                <span class="close" onclick="closeFacilityModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="facilityForm" class="facility-form">
                    <input type="hidden" id="facilityId">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Facility Name *</label>
                            <input type="text" class="form-control" id="facilityName" required onkeyup="generateFacilityKey()">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Facility Key (Auto-generated)</label>
                            <input type="text" class="form-control" id="facilityKey" readonly>
                            <small>Automatically generated from facility name</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Icon *</label>
                            <input type="text" class="form-control" id="facilityIcon" required placeholder="🏢">
                            <small>Enter an emoji or icon</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Capacity (persons) *</label>
                            <input type="number" class="form-control" id="facilityCapacity" required min="1" value="1">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Additional Hours Rate (₱/hour) *</label>
                            <input type="number" class="form-control" id="additionalHoursRate" required min="0" step="0.01" value="0">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="facilityDescription" rows="3" placeholder="Brief description of the facility"></textarea>
                    </div>

                    <!-- Facility Gallery Section -->
                    <div class="form-group full-width">
                        <label class="form-label">Facility Gallery</label>
                        <div class="gallery-upload-section">
                            <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 15px;">Add up to 6 images to showcase your facility</p>
                            
                            <div class="gallery-preview-grid" id="galleryPreview">
                                <div class="gallery-preview-item">
                                    <div class="gallery-upload-placeholder" onclick="document.getElementById('galleryInput').click()">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Click to upload</p>
                                    </div>
                                    <input type="file" id="galleryInput" accept="image/*" style="display: none;" onchange="handleGalleryUpload(event)">
                                </div>
                                <div id="galleryItems"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" id="isActive" checked> Active
                            </label>
                            <small>Unchecking will hide this facility from booking</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" id="isMaintenance"> Under Maintenance
                            </label>
                            <small>Prevents new bookings for this facility</small>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeFacilityModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveFacility()">Save Facility</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2 class="modal-title">Confirm Delete</h2>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this facility?</p>
                <p class="warning-text">⚠️ This action cannot be undone. However, all associated bookings will be preserved without a facility reference.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <script src="<?= base_url('js/facilities-management.js') ?>"></script>
</body>
</html>
