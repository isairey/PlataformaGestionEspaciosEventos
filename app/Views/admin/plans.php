<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC Admin - Plans Management</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/plans.css') ?>">
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
                <li><a href="<?= base_url('/admin/equipment') ?>" class="menu-item"><i>🔧</i> Equipment</a></li>
                <li><a href="<?= base_url('/admin/plans') ?>" class="menu-item active"><i>📋</i> Plans</a></li>
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
                <input type="text" placeholder="Search plans..." id="searchInput">
            </div>
        </div>

        <!-- Plans Management Content -->
        <div class="dashboard">
            <div class="dashboard-title">
                <h2>Plans & Services Management</h2>
                <p>Manage facility plans, additional services, and pricing settings.</p>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn active" onclick="switchTab('plans')">📋 Facility Plans</button>
                <button class="tab-btn" onclick="switchTab('facilities')">🏢 Facilities Rates</button>
                <button class="tab-btn" onclick="switchTab('services')">➕ Additional Services</button>
                <button class="tab-btn" onclick="switchTab('settings')">⚙️ Settings</button>
            </div>

            <!-- Plans Tab -->
            <div id="plansTab" class="tab-content active">
                <div class="equipment-actions">
                    <button class="btn-primary" onclick="openAddPlanModal()">
                        ➕ Add Plan
                    </button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Facility Plans</h3>
                        <div class="table-actions">
                            <select id="facilityFilter" onchange="filterPlans()">
                                <option value="">All Facilities</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="equipment-table" id="plansTable">
                            <thead>
                                <tr>
                                    <th>Plan Name</th>
                                    <th>Facility</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="plansTableBody">
                                <!-- Plans will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Facilities Rates Tab -->
            <div id="facilitiesTab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Facility Additional Hours Rates</h3>
                        <p style="margin: 10px 0 0 0; font-size: 14px; color: #666;">Set different additional hours rates for each facility</p>
                    </div>

                    <div class="table-container">
                        <table class="equipment-table" id="facilitiesTable">
                            <thead>
                                <tr>
                                    <th>Facility Name</th>
                                    <th>Additional Hours Rate (₱/hour)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="facilitiesTableBody">
                                <!-- Facilities will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Additional Services Tab -->
            <div id="servicesTab" class="tab-content">
                <div class="equipment-actions">
                    <button class="btn-primary" onclick="openAddServiceModal()">
                        ➕ Add Service
                    </button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Additional Services</h3>
                    </div>

                    <div class="table-container">
                        <table class="equipment-table" id="servicesTable">
                            <thead>
                                <tr>
                                    <th>Service Name</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="servicesTableBody">
                                <!-- Services will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="settingsTab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pricing Settings</h3>
                    </div>

                    <div style="padding: 30px;">
                        <form id="settingsForm">
                            <div class="settings-grid">
                                <div class="form-group">
                                    <label for="extendedHoursRate">Extended Hours Rate (₱/hour)</label>
                                    <input type="number" id="extendedHoursRate" name="extended_hours_rate" step="0.01" min="0" required>
                                    <small>Rate charged per additional hour beyond plan duration</small>
                                </div>

                                <div class="form-group">
                                    <label for="overtimeRate">Overtime Staff Rate (₱)</label>
                                    <input type="number" id="overtimeRate" name="overtime_rate" step="0.01" min="0" required>
                                    <small>Rate for overtime staff support (weekends/holidays/after 5PM)</small>
                                </div>

                                <div class="form-group">
                                    <label for="maintenanceFee">Maintenance Fee (₱)</label>
                                    <input type="number" id="maintenanceFee" name="maintenance_fee" step="0.01" min="0" required>
                                    <small>Standard maintenance fee added to bookings</small>
                                </div>
                            </div>

                            <div class="modal-actions" style="margin-top: 30px;">
                                <button type="submit" class="btn-primary">💾 Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Plan Modal -->
    <div id="addPlanModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Add New Plan</h3>
                <span class="close" onclick="closeAddPlanModal()">&times;</span>
            </div>

            <form id="addPlanForm">
                <div style="padding: 20px;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="planFacility">Facility *</label>
                            <select id="planFacility" name="facility_id" required>
                                <option value="">Select Facility</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="planKey">Plan Key *</label>
                            <input type="text" id="planKey" name="plan_key" required>
                            <small>Unique identifier (e.g., gym-basic-4h)</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="planName">Plan Name *</label>
                            <input type="text" id="planName" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="planDuration">Duration *</label>
                            <input type="text" id="planDuration" name="duration" placeholder="e.g., 4 hours, 8 hours" required>
                        </div>

                        <div class="form-group">
                            <label for="planPrice">Price (₱) *</label>
                            <input type="number" id="planPrice" name="price" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Features</label>
                        <div id="featuresList">
                            <div class="feature-item">
                                <input type="text" class="feature-input" placeholder="Feature description">
                                <select class="feature-type">
                                    <option value="amenity">Amenity</option>
                                    <option value="service">Service</option>
                                    <option value="access">Access</option>
                                    <option value="description">Description</option>
                                </select>
                                <button type="button" class="btn-sm btn-danger" onclick="removeFeature(this)">✕</button>
                            </div>
                        </div>
                        <button type="button" class="btn-secondary" onclick="addFeature()">➕ Add Feature</button>
                    </div>

                    <div class="form-group">
                        <label>Included Equipment</label>
                        <div id="equipmentList">
                            <!-- Equipment items will be added dynamically -->
                        </div>
                        <button type="button" class="btn-secondary" onclick="addEquipment()">➕ Add Equipment</button>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeAddPlanModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Add Plan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Plan Modal -->
    <div id="editPlanModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Edit Plan</h3>
                <span class="close" onclick="closeEditPlanModal()">&times;</span>
            </div>

            <form id="editPlanForm">
                <input type="hidden" id="editPlanId" name="id">

                <div style="padding: 20px;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editPlanFacility">Facility *</label>
                            <select id="editPlanFacility" name="facility_id" required>
                                <option value="">Select Facility</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editPlanKey">Plan Key *</label>
                            <input type="text" id="editPlanKey" name="plan_key" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="editPlanName">Plan Name *</label>
                            <input type="text" id="editPlanName" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="editPlanDuration">Duration *</label>
                            <input type="text" id="editPlanDuration" name="duration" required>
                        </div>

                        <div class="form-group">
                            <label for="editPlanPrice">Price (₱) *</label>
                            <input type="number" id="editPlanPrice" name="price" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Features</label>
                        <div id="editFeaturesList">
                            <!-- Features will be populated here -->
                        </div>
                        <button type="button" class="btn-secondary" onclick="addEditFeature()">➕ Add Feature</button>
                    </div>

                    <div class="form-group">
                        <label>Included Equipment</label>
                        <div id="editEquipmentList">
                            <!-- Equipment will be populated here -->
                        </div>
                        <button type="button" class="btn-secondary" onclick="addEditEquipment()">➕ Add Equipment</button>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditPlanModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update Plan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Plan Modal -->
    <div id="viewPlanModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Plan Details</h3>
                <span class="close" onclick="closeViewPlanModal()">&times;</span>
            </div>

            <div class="modal-body">
                <div id="planDetailsContent">
                    <!-- Plan details will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Plan Modal -->
    <div id="deletePlanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <span class="close" onclick="closeDeletePlanModal()">&times;</span>
            </div>

            <div class="modal-body">
                <p>Are you sure you want to delete this plan? This action cannot be undone.</p>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeDeletePlanModal()">Cancel</button>
                <button type="button" class="btn-danger" onclick="confirmDeletePlan()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div id="addServiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Additional Service</h3>
                <span class="close" onclick="closeAddServiceModal()">&times;</span>
            </div>

            <form id="addServiceForm">
                <div style="padding: 20px;">
                    <div class="form-group">
                        <label for="serviceKey">Service Key *</label>
                        <input type="text" id="serviceKey" name="addon_key" required>
                        <small>Unique identifier (e.g., photo-booth, live-band)</small>
                    </div>

                    <div class="form-group">
                        <label for="serviceName">Service Name *</label>
                        <input type="text" id="serviceName" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="serviceDescription">Description</label>
                        <textarea id="serviceDescription" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="servicePrice">Price (₱) *</label>
                        <input type="number" id="servicePrice" name="price" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeAddServiceModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Add Service</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div id="editServiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Additional Service</h3>
                <span class="close" onclick="closeEditServiceModal()">&times;</span>
            </div>

            <form id="editServiceForm">
                <input type="hidden" id="editServiceId" name="id">

                <div style="padding: 20px;">
                    <div class="form-group">
                        <label for="editServiceKey">Service Key *</label>
                        <input type="text" id="editServiceKey" name="addon_key" required>
                    </div>

                    <div class="form-group">
                        <label for="editServiceName">Service Name *</label>
                        <input type="text" id="editServiceName" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="editServiceDescription">Description</label>
                        <textarea id="editServiceDescription" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="editServicePrice">Price (₱) *</label>
                        <input type="number" id="editServicePrice" name="price" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditServiceModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update Service</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Service Modal -->
    <div id="deleteServiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <span class="close" onclick="closeDeleteServiceModal()">&times;</span>
            </div>

            <div class="modal-body">
                <p>Are you sure you want to delete this service? This action cannot be undone.</p>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeDeleteServiceModal()">Cancel</button>
                <button type="button" class="btn-danger" onclick="confirmDeleteService()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Edit Facility Rate Modal -->
    <div id="editFacilityRateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Additional Hours Rate</h3>
                <span class="close" onclick="closeEditFacilityRateModal()">&times;</span>
            </div>

            <form id="editFacilityRateForm">
                <input type="hidden" id="editFacilityId" name="facility_id">

                <div style="padding: 20px;">
                    <div class="form-group">
                        <label for="editFacilityName">Facility</label>
                        <input type="text" id="editFacilityName" readonly disabled style="background: #f5f5f5;">
                    </div>

                    <div class="form-group">
                        <label for="editAdditionalHoursRate">Additional Hours Rate (₱/hour) *</label>
                        <input type="number" id="editAdditionalHoursRate" name="additional_hours_rate" step="0.01" min="0" required>
                        <small>Rate charged per additional hour beyond plan duration for this facility</small>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditFacilityRateModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update Rate</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= base_url('js/admin/plans.js') ?>"></script>

</body>
</html>
