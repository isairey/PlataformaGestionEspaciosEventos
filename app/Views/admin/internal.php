<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC Internal Booking - Facility Booking</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/student.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/student-modern.css') ?>">

</head>
<body>
     <div class="toast-container" id="toastContainer"></div>
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
        
        <div class="booking-page">
            <div class="page-title">
                <h2>Facility Booking Management</h2>
                <p>Book CSPC facilities for FREE - Manage and create new facility bookings</p>
            </div>

            <!-- Facilities Grid -->
            <div class="facilities-grid" id="studentFacilitiesGrid">
                <!-- Will be populated dynamically by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Student Booking Modal -->
    <div id="studentBookingModal" class="modal student-booking-modal">
        <div class="modal-content student-modal-content">
            <div class="modal-header student-modal-header">
                <div class="header-badge">🎓</div>
                <div>
                    <h2 class="modal-title" id="modalTitle">Student Facility Booking</h2>
                    <p class="modal-subtitle">Book facilities FREE - Complete your event booking request</p>
                </div>
                <span class="close" onclick="closeStudentModal()">&times;</span>
            </div>
            <div class="modal-body student-modal-body">
                <!-- Facility Availability Alert -->
                <div id="bookingConflictAlert" class="alert-conflict" style="display: none; margin-bottom: 20px;">
                    <div class="alert-icon">⚠️</div>
                    <div class="alert-content">
                        <strong>Facility Not Available</strong>
                        <p id="bookingConflictAlertMessage"></p>
                    </div>
                </div>

                <!-- Progress Indicator -->
                <div class="progress-steps">
                    <div class="progress-step active" data-step="1">
                        <div class="step-number">1</div>
                        <span>Basic Info</span>
                    </div>
                    <div class="progress-step" data-step="2">
                        <div class="step-number">2</div>
                        <span>Event Details</span>
                    </div>
                    <div class="progress-step" data-step="3">
                        <div class="step-number">3</div>
                        <span>Equipment</span>
                    </div>
                    <div class="progress-step" data-step="4">
                        <div class="step-number">4</div>
                        <span>Documents</span>
                    </div>
                </div>

                <!-- Step 1: Basic Information -->
                <div class="form-step active" data-step="1">
                    <div class="section-card">
                        <h3 class="card-title">👤 Personal Information</h3>
                        <form id="studentBookingForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Booking Type *</label>
                                    <select class="form-control form-control-modern" id="bookingType" required>
                                        <option value="" disabled selected>Select booking type</option>
                                        <option value="student">🎓 Student Organization</option>
                                        <option value="employee">👨‍🏫 Employee</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Your Full Name *</label>
                                    <input type="text" class="form-control form-control-modern" id="clientName" placeholder="Enter your full name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact Number *</label>
                                    <input type="tel" class="form-control form-control-modern" id="contactNumber" placeholder="+63 (xxx) xxx-xxxx" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label class="form-label">Your Email Address *</label>
                                    <input type="email" class="form-control form-control-modern" id="clientEmail" placeholder="your.email@example.com" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label class="form-label">Organization/Group Name *</label>
                                    <input type="text" class="form-control form-control-modern" id="organization" placeholder="Enter organization or group name" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control form-control-modern textarea" id="address" rows="2" placeholder="San Nicolas, Iriga City, Camarines Sur"></textarea>
                                    <small class="form-hint">Optional. If provided, must be at least 10 characters (street, city, province)</small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Step 2: Event Details -->
                <div class="form-step" data-step="2">
                    <div class="section-card">
                        <h3 class="card-title">📅 Event Information</h3>
                        <form>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Event Date *</label>
                                    <input type="date" class="form-control form-control-modern" id="eventDate" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Event Time *</label>
                                    <input type="time" class="form-control form-control-modern" id="eventTime" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Duration (hours) *</label>
                                    <div class="input-with-unit">
                                        <input type="number" class="form-control form-control-modern" id="duration" min="1" max="12" value="4" required>
                                        <span class="unit-label">hrs</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Expected Attendees</label>
                                    <input type="number" class="form-control form-control-modern" id="attendees" min="1" placeholder="Enter estimated count">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label class="form-label">Event Title/Purpose *</label>
                                    <input type="text" class="form-control form-control-modern" id="eventTitle" placeholder="What is your event about?" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label class="form-label">Special Requirements</label>
                                    <textarea class="form-control form-control-modern textarea" id="specialRequirements" placeholder="Any special setup or requirements?"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Step 3: Equipment -->
                <div class="form-step" data-step="3">
                    <div class="section-card">
                        <h3 class="card-title">🔧 Equipment & Resources</h3>
                        <div class="equipment-grid" id="studentEquipmentGrid">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Step 4: Document Upload -->
                <div class="form-step" data-step="4">
                    <div class="section-card">
                        <h3 class="card-title">📎 Required Documents</h3>
                        <p class="card-description">Upload the following documents (PDF, JPG, PNG - Max 10MB each)</p>

                        <!-- Debug Panel -->
                        <div id="uploadDebugPanel" style="background-color: #f0f7ff; border: 2px solid #0066cc; border-radius: 8px; padding: 15px; margin-bottom: 20px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto; display: none;">
                            <div style="margin-bottom: 10px; font-weight: bold; color: #0066cc;">📊 Upload Debug Log:</div>
                            <div id="uploadDebugLog" style="color: #333; line-height: 1.6;"></div>
                        </div>

                        <div class="documents-container">
                            <!-- Permission Document -->
                            <div class="document-upload-card" id="upload-permission">
                                <div class="document-icon">📄</div>
                                <h4 class="document-title">Approved Permission to Conduct</h4>
                                <p class="document-desc">Official permission letter from your organization's adviser</p>
                                <div class="upload-area">
                                    <input type="file" id="file-permission" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileSelect(this, 'permission')">
                                    <div class="file-name-display" id="filename-permission"></div>
                                </div>
                                <span class="upload-status">Not uploaded</span>
                            </div>

                            <!-- Request Letter -->
                            <div class="document-upload-card" id="upload-request">
                                <div class="document-icon">📝</div>
                                <h4 class="document-title">Letter Request for Venue</h4>
                                <p class="document-desc">Formal letter requesting the use of the facility</p>
                                <div class="upload-area">
                                    <input type="file" id="file-request" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileSelect(this, 'request')">
                                    <div class="file-name-display" id="filename-request"></div>
                                </div>
                                <span class="upload-status">Not uploaded</span>
                            </div>

                            <!-- Approval Letter -->
                            <div class="document-upload-card" id="upload-approval">
                                <div class="document-icon">✅</div>
                                <h4 class="document-title">Approval Letter of the Venue</h4>
                                <p class="document-desc">Pre-approval or recommendation letter from authorized personnel</p>
                                <div class="upload-area">
                                    <input type="file" id="file-approval" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileSelect(this, 'approval')">
                                    <div class="file-name-display" id="filename-approval"></div>
                                </div>
                                <span class="upload-status">Not uploaded</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer student-modal-footer">
                <div class="footer-nav">
                    <button type="button" class="btn btn-tertiary" id="prevBtn" onclick="prevStep()" style="display: none;">← Previous</button>
                </div>
                <div class="footer-actions">
                    <button type="button" class="btn btn-outline" onclick="closeStudentModal()">Cancel</button>
                    <button type="button" class="btn btn-next" id="nextBtn" onclick="nextStep()">Next →</button>
                    <button type="button" class="btn btn-success" id="submitStudentBtn" onclick="submitStudentBooking()" style="display: none;" disabled>
                        ✓ Submit Booking
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url('js/admin/student-facilities.js') ?>"></script>
    <script src="<?= base_url('js/admin/student.js') ?>"></script>
    <script src="<?= base_url('js/admin/student-steps.js') ?>"></script>
</body>
</html>
