<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC External Booking - Booking Management</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/booking.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/external-modern.css') ?>">
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
                        <li><a href="<?= base_url('/admin/external') ?>" class="submenu-item active">🌐 External</a></li>
                        <li><a href="<?= base_url('/admin/internal') ?>" class="submenu-item ">🏛️ Internal</a></li>
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
 
        
        <!-- Booking Page Content -->
        <div class="booking-page">
            <div class="page-title">
                <h2>Facility Booking Management</h2>
                <p>Manage and create new facility bookings for CSPC facilities</p>
            </div>


            <!-- Facilities Grid -->
            <div class="facilities-grid" id="externalFacilitiesGrid">
                <!-- Will be populated dynamically by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal external-booking-modal">
        <div class="modal-content external-modal-content">
            <div class="modal-header external-modal-header">
                <div class="header-content">
                    <h2 class="modal-title" id="modalTitle">External Facility Booking</h2>
                    <p class="modal-subtitle">Create a professional event booking - Step by step</p>
                </div>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>

            <!-- Progress Steps Indicator -->
            <div class="progress-steps external-progress">
                <div class="progress-step active" data-step="1">
                    <div class="step-number">1</div>
                    <span>Package</span>
                </div>
                <div class="progress-step" data-step="2">
                    <div class="step-number">2</div>
                    <span>Info</span>
                </div>
                <div class="progress-step" data-step="3">
                    <div class="step-number">3</div>
                    <span>Services</span>
                </div>
                <div class="progress-step" data-step="4">
                    <div class="step-number">4</div>
                    <span>Summary</span>
                </div>
            </div>

            <div class="modal-body external-modal-body">
                <!-- Step 1: Plan Selection -->
                <div class="form-step external-step active" data-step="1">
                    <div class="section-card external-card">
                        <h3 class="card-title">📋 Choose Your Package</h3>
                        <p class="card-description">Select the package that best fits your event</p>
                        <div class="plans-grid" id="plansGrid">
                            <!-- Plans will be populated dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Step 2: Booking Information -->
                <div class="form-step external-step" data-step="2">
                    <div class="section-card external-card">
                        <h3 class="card-title">📝 Event & Client Information</h3>
                        <p class="card-description">Provide details about your event and contact information</p>
                        <form class="booking-form" id="bookingForm">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Client Name *</label>
                                    <input type="text" class="form-control form-control-external" id="clientName" placeholder="Full name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" class="form-control form-control-external" id="emailAddress" placeholder="client@example.com" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Contact Number *</label>
                                    <input type="tel" class="form-control form-control-external" id="contactNumber" placeholder="+63 (xxx) xxx-xxxx" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Organization/Company</label>
                                    <input type="text" class="form-control form-control-external" id="organization" placeholder="Company or organization name">
                                </div>

                                <div class="form-group full-width">
                                    <label class="form-label">Complete Address *</label>
                                    <textarea class="form-control form-control-external textarea" id="address" rows="2" placeholder="Street, Barangay, City, Province" required></textarea>
                                    <small class="form-hint">Provide your complete mailing address</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Event Date *</label>
                                    <input type="date" class="form-control form-control-external" id="eventDate" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Event Time *</label>
                                    <input type="time" class="form-control form-control-external" id="eventTime" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Expected Attendees</label>
                                    <input type="number" class="form-control form-control-external" id="attendees" min="1" placeholder="Estimated guest count">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Event Title/Purpose *</label>
                                    <input type="text" class="form-control form-control-external" id="eventTitle" placeholder="Wedding, Corporate Meeting, etc." required>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Special Requirements/Notes</label>
                                    <textarea class="form-control form-control-external textarea" id="specialRequirements" placeholder="Any special requests or setup instructions..."></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Step 3: Services & Equipment -->
                <div class="form-step external-step" data-step="3">
                    <!-- Additional Services Section -->
                    <div class="section-card external-card">
                        <h3 class="card-title">✨ Additional Services</h3>
                        <p class="card-description">Enhance your event with extra add-ons</p>
                        <div class="addons-grid" id="addonsGrid">
                            <!-- Add-ons will be populated dynamically -->
                        </div>
                    </div>

                    <!-- Equipment & Logistics Section -->
                    <div class="section-card external-card" style="margin-top: 24px;">
                        <h3 class="card-title">🔧 Equipment & Logistics</h3>
                        <p class="card-description">Select necessary equipment for your event</p>
                        <div class="equipment-grid" id="equipmentGrid">
                            <!-- Equipment cards will be populated dynamically -->
                            <div class="equipment-card-external">
                                <div class="equipment-header">
                                    <h4 class="equipment-name">Sound System</h4>
                                    <span class="equipment-price">₱500/day</span>
                                </div>
                                <p class="equipment-description">Professional audio equipment for events</p>
                                <div class="equipment-control">
                                    <input type="number" class="form-control qty-input" id="qty-sound" min="0" max="5" value="0" onchange="updateEquipment('sound')">
                                    <label>Qty</label>
                                </div>
                            </div>
                            
                            <div class="equipment-card-external">
                                <div class="equipment-header">
                                    <h4 class="equipment-name">Projector</h4>
                                    <span class="equipment-price">₱300/day</span>
                                </div>
                                <p class="equipment-description">HD projector for presentations</p>
                                <div class="equipment-control">
                                    <input type="number" class="form-control qty-input" id="qty-projector" min="0" max="3" value="0" onchange="updateEquipment('projector')">
                                    <label>Qty</label>
                                </div>
                            </div>
                            
                            <div class="equipment-card-external">
                                <div class="equipment-header">
                                    <h4 class="equipment-name">Microphone</h4>
                                    <span class="equipment-price">₱200/day</span>
                                </div>
                                <p class="equipment-description">Wireless microphone system</p>
                                <div class="equipment-control">
                                    <input type="number" class="form-control qty-input" id="qty-microphone" min="0" max="10" value="0" onchange="updateEquipment('microphone')">
                                    <label>Qty</label>
                                </div>
                            </div>
                            
                            <div class="equipment-card-external">
                                <div class="equipment-header">
                                    <h4 class="equipment-name">Tables & Chairs</h4>
                                    <span class="equipment-price">₱50/set</span>
                                </div>
                                <p class="equipment-description">Additional seating arrangements</p>
                                <div class="equipment-control">
                                    <input type="number" class="form-control qty-input" id="qty-furniture" min="0" max="20" value="0" onchange="updateEquipment('furniture')">
                                    <label>Sets</label>
                                </div>
                            </div>
                            
                            <div class="equipment-card-external">
                                <div class="equipment-header">
                                    <h4 class="equipment-name">Lighting Equipment</h4>
                                    <span class="equipment-price">₱800/day</span>
                                </div>
                                <p class="equipment-description">Professional stage lighting</p>
                                <div class="equipment-control">
                                    <input type="number" class="form-control qty-input" id="qty-lighting" min="0" max="2" value="0" onchange="updateEquipment('lighting')">
                                    <label>Qty</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Extended Hours Section -->
                    <div class="section-card external-card" style="margin-top: 24px;">
                        <h3 class="card-title">⏰ Extended Hours</h3>
                        <p class="card-description">Add extra hours beyond your selected package</p>
                        <div class="hours-input-group">
                            <div class="form-group">
                                <label class="form-label">Additional Hours (Rate: <span id="hourlyRateLabel" class="rate-highlight">₱500</span>/hour)</label>
                                <div class="input-with-unit">
                                    <input type="number" class="form-control form-control-external" id="additionalHours" min="0" max="12" value="0" onchange="updateCostSummary()">
                                    <span class="unit-label">hrs</span>
                                </div>
                                <small class="form-hint">Add hours beyond your package duration</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Cost Summary -->
                <div class="form-step external-step" data-step="4">
                    <div class="section-card external-card">
                        <h3 class="card-title">💰 Billing Summary</h3>
                        <p class="card-description">Review your complete booking details and costs</p>
                        <div class="cost-summary-card">
                            <div id="costBreakdown" class="cost-breakdown">
                                <div class="cost-row">
                                    <span class="cost-label">Base Package:</span>
                                    <span class="cost-value" id="baseCost">₱0</span>
                                </div>
                                <div class="cost-row">
                                    <span class="cost-label mandatory-label">🔒 Maintenance Fee:</span>
                                    <span class="cost-value" id="maintenanceCost">₱0</span>
                                </div>
                                <div id="addonCosts"></div>
                                <div class="cost-row cost-total">
                                    <span class="cost-label">Total Amount:</span>
                                    <span class="cost-value-total" id="totalCost">₱0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer external-modal-footer">
                <div class="footer-nav">
                    <button type="button" class="btn btn-tertiary" id="extPrevBtn" onclick="externalPrevStep()" style="display: none;">← Previous</button>
                </div>
                <div class="footer-actions">
                    <button type="button" class="btn btn-outline external-btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-next external-btn-next" id="extNextBtn" onclick="externalNextStep()">Next →</button>
                    <button type="button" class="btn btn-primary external-btn-primary" id="submitBookingBtn" onclick="submitBooking()" style="display: none;">
                        🎉 Create Booking
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url('js/admin/external-facilities.js') ?>"></script>
    <script src="<?= base_url('js/booking.js?v=' . time()) ?>"></script>
    <script src="<?= base_url('js/admin/external-steps.js') ?>"></script>
</body>
</html>