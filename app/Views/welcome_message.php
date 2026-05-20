<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC Admin - Booking Management</title>

</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>CSPC Admin</h3>
        </div>
        
        <div class="sidebar-menu">
            <ul>
                <li><a href="#" class="menu-item"><i>📊</i> Dashboard</a></li>
                <li><a href="#" class="menu-item"><i>👥</i> Users</a></li>
                <li><a href="#" class="menu-item"><i>🏢</i> Facilities</a></li>
                <li><a href="#" class="menu-item"><i>📅</i> Events</a></li>
                <li><a href="#" class="menu-item"><i>🔧</i> Equipment</a></li>
                
                <div class="sidebar-divider"></div>
                
                <li><a href="#" class="menu-item active"><i>📝</i> Bookings</a></li>
                <li><a href="#" class="menu-item"><i>🔔</i> Notifications</a></li>
                <li><a href="#" class="menu-item"><i>📊</i> Reports</a></li>
                
                <div class="sidebar-divider"></div>
                
                <li><a href="#" class="menu-item"><i>⚙️</i> Settings</a></li>
                <li><a href="#" class="menu-item"><i>❓</i> Help</a></li>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">JD</div>
                <div class="user-details">
                    John Doe
                    <div class="role">Administrator</div>
                </div>
            </div>
            <button class="logout-btn" title="Logout">🚪</button>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <button class="toggle-btn">☰</button>
            
            <div class="search-box">
                <i>🔍</i>
                <input type="text" placeholder="Search bookings, facilities...">
            </div>
            
            <div class="header-actions">
                <button class="header-btn">
                    🔔
                    <span class="notification-badge">3</span>
                </button>
                <button class="header-btn">📧</button>
                <button class="header-btn">⚙️</button>
            </div>
        </div>
        
        <!-- Booking Page Content -->
        <div class="booking-page">
            <div class="page-title">
                <h2>Facility Booking Management</h2>
                <p>Manage and create new facility bookings for CSPC facilities</p>
            </div>

            <div class="booking-actions">
                <button class="btn btn-primary">
                    <span>➕</span> New Booking
                </button>
                <button class="btn btn-outline">
                    <span>📋</span> View All Bookings
                </button>
                <button class="btn btn-outline">
                    <span>📊</span> Booking Reports
                </button>
            </div>

            <!-- Facilities Grid -->
            <div class="facilities-grid">
                <!-- University Auditorium -->
                <div class="facility-card" onclick="openBookingModal('auditorium')">
                    <div class="facility-image">🎭</div>
                    <div class="facility-info">
                        <h3 class="facility-title">University Auditorium</h3>
                        <p class="facility-description">Large capacity venue perfect for concerts, graduations, and major events with professional sound and lighting systems.</p>
                        <div class="facility-features">
                            <span class="feature-tag">Air Conditioned</span>
                            <span class="feature-tag">Sound System</span>
                            <span class="feature-tag">Projector</span>
                            <span class="feature-tag">Professional Lighting</span>
                        </div>
                        <div class="facility-price">
                            <span class="price-range">₱7,000 - ₱25,000</span>
                            <button class="book-btn">Book Now</button>
                        </div>
                    </div>
                </div>

                <!-- University Gymnasium -->
                <div class="facility-card" onclick="openBookingModal('gymnasium')">
                    <div class="facility-image">🏀</div>
                    <div class="facility-info">
                        <h3 class="facility-title">University Gymnasium</h3>
                        <p class="facility-description">Spacious indoor sports facility suitable for sports events, large gatherings, and exhibitions.</p>
                        <div class="facility-features">
                            <span class="feature-tag">Heavy Duty Fans</span>
                            <span class="feature-tag">Sound System</span>
                            <span class="feature-tag">Projector</span>
                            <span class="feature-tag">Sports Equipment</span>
                        </div>
                        <div class="facility-price">
                            <span class="price-range">₱7,000 - ₱35,000</span>
                            <button class="book-btn">Book Now</button>
                        </div>
                    </div>
                </div>

                <!-- AVR Library -->
                <div class="facility-card" onclick="openBookingModal('avr-library')">
                    <div class="facility-image">📚</div>
                    <div class="facility-info">
                        <h3 class="facility-title">AVR Library</h3>
                        <p class="facility-description">Audio-Visual Room perfect for presentations, seminars, and educational events.</p>
                        <div class="facility-features">
                            <span class="feature-tag">Air Conditioned</span>
                            <span class="feature-tag">Projector</span>
                            <span class="feature-tag">Tables & Chairs</span>
                            <span class="feature-tag">Sound System</span>
                        </div>
                        <div class="facility-price">
                            <span class="price-range">₱1,000 - ₱2,000</span>
                            <button class="book-btn">Book Now</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Book Facility</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Plan Selection -->
                <div class="plan-section">
                    <h3 class="section-title">
                        <span>📋</span> Select Your Plan
                    </h3>
                    <div class="plans-grid" id="plansGrid">
                        <!-- Plans will be populated dynamically -->
                    </div>
                </div>

                <!-- Add-ons Section -->
                <div class="plan-section">
                    <h3 class="section-title">
                        <span>✨</span> Additional Services
                    </h3>
                    <div class="addons-grid" id="addonsGrid">
                        <!-- Add-ons will be populated dynamically -->
                    </div>
                </div>

                <!-- Equipment Section -->
                <div class="plan-section">
                    <h3 class="section-title">
                        <span>🔧</span> Equipment & Logistics
                    </h3>
                    <div class="equipment-grid" id="equipmentGrid">
                        <!-- Equipment will be populated dynamically -->
                    </div>
                </div>

                <!-- Cost Summary -->
                <div class="cost-summary">
                    <h3 class="section-title">
                        <span>💰</span> Cost Summary
                    </h3>
                    <div id="costBreakdown">
                        <div class="cost-row">
                            <span>Base Plan:</span>
                            <span id="baseCost">₱0</span>
                        </div>
                        <div id="addonCosts"></div>
                        <div class="cost-row total">
                            <span>Total Amount:</span>
                            <span id="totalCost">₱0</span>
                        </div>
                    </div>
                </div>

                <!-- Booking Form -->
                <div class="plan-section">
                    <h3 class="section-title">
                        <span>📝</span> Booking Information
                    </h3>
                    <form class="booking-form" id="bookingForm">
                        <div class="form-group">
                            <label class="form-label">Client Name *</label>
                            <input type="text" class="form-control" id="clientName" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Contact Number *</label>
                            <input type="tel" class="form-control" id="contactNumber" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="emailAddress">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Organization/Company</label>
                            <input type="text" class="form-control" id="organization">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Event Date *</label>
                            <input type="date" class="form-control" id="eventDate" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Event Time *</label>
                            <input type="time" class="form-control" id="eventTime" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Duration (hours) *</label>
                            <input type="number" class="form-control" id="duration" min="1" max="24" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Expected Attendees</label>
                            <input type="number" class="form-control" id="attendees" min="1">
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">Event Title/Purpose *</label>
                            <input type="text" class="form-control" id="eventTitle" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">Special Requirements/Notes</label>
                            <textarea class="form-control textarea" id="specialRequirements" placeholder="Please specify any special requirements, setup instructions, or additional notes..."></textarea>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitBooking()">Create Booking</button>
            </div>
        </div>
    </div>

   
</body>
</html>book-btn">Book Now</button>
                        </div>
                    </div>
                </div>

                <!-- Function Hall -->
                <div class="facility-card" onclick="openBookingModal('function-hall')">
                    <div class="facility-image">🏛️</div>
                    <div class="facility-info">
                        <h3 class="facility-title">Function Hall (ACAD Bldg.)</h3>
                        <p class="facility-description">Versatile event space for meetings, conferences, and medium-sized gatherings.</p>
                        <div class="facility-features">
                            <span class="feature-tag">Air Conditioned</span>
                            <span class="feature-tag">Tables & Chairs</span>
                            <span class="feature-tag">Sound System</span>
                            <span class="feature-tag">Projector</span>
                        </div>
                        <div class="facility-price">
                            <span class="price-range">₱1,000 - ₱2,000</span>
                            <button class="book-btn">Book Now</button>
                        </div>
                    </div>
                </div>

                <!-- AVR Engineering -->
                <div class="facility-card" onclick="openBookingModal('avr-engineering')">
                    <div class="facility-image">⚙️</div>
                    <div class="facility-info">
                        <h3 class="facility-title">AVR Calibo Engineering</h3>
                        <p class="facility-description">Technical presentation room in the Engineering building for specialized events and seminars.</p>
                        <div class="facility-features">
                            <span class="feature-tag">Air Conditioned</span>
                            <span class="feature-tag">Projector</span>
                            <span class="feature-tag">Technical Setup</span>
                            <span class="feature-tag">Sound System</span>
                        </div>
                        <div class="facility-price">
                            <span class="price-range">₱1,000 - ₱2,000</span>
                            <button class="book-btn">Book Now</button>
                        </div>
                    </div>
                </div>

                <!-- Pearl Mini Restaurant -->
                <div class="facility-card" onclick="openBookingModal('pearl-restaurant')">
                    <div class="facility-image">🍽️</div>
                    <div class="facility-info">
                        <h3 class="facility-title">Pearl Mini Restaurant</h3>
                        <p class="facility-description">Cozy dining venue perfect for small gatherings, parties, and intimate events.</p>
                        <div class="facility-features">
                            <span class="feature-tag">Air Conditioned</span>
                            <span class="feature-tag">Tables & Chairs</span>
                            <span class="feature-tag">Kitchen Access</span>
                            <span class="feature-tag">Dining Setup</span>
                        </div>
                        <div class="facility-price">
                            <span class="price-range">₱1,000 - ₱2,000</span>
                            <button class="book-btn">Book Now</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Book Facility</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Plan Selection -->
                <div class="plan-section">
                    <h3 class="section-title">
                        <span>📋</span> Select Your Plan
                    </h3>
                    <div class="plans-grid" id="plansGrid">
                        <!-- Plans will be populated dynamically -->
                    </div>
                </div>

                <!-- Add-ons Section -->
                <div class="plan-section">
                    <h3 class="section-title">
                        <span>✨</span> Additional Services
                    </h3>
                    <div class="addons-grid" id="addonsGrid">
                        <!-- Add-ons will be populated dynamically -->
                    </div>
                </div>

                <!-- Equipment Section -->
                <div class="plan-section">
                    <h3 class="section-title">
                        <span>🔧</span> Equipment & Logistics
                    </h3>
                    <div class="equipment-grid" id="equipmentGrid">
                        <!-- Equipment will be populated dynamically -->
                    </div>
                </div>

                <!-- Cost Summary -->
                <div class="cost-summary">
                    <h3 class="section-title">
                        <span>💰</span> Cost Summary
                    </h3>
                    <div id="costBreakdown">
                        <div class="cost-row">
                            <span>Base Plan:</span>
                            <span id="baseCost">₱0</span>
                        </div>
                        <div id="addonCosts"></div>
                        <div class="cost-row total">
                            <span>Total Amount:</span>
                            <span id="totalCost">₱0</span>
                        </div>
                    </div>
                </div>

                <!-- Booking Form -->
                <div class="plan-section">
                    <h3 class="section-title">
                        <span>📝</span> Booking Information
                    </h3>
                    <form class="booking-form" id="bookingForm">
                        <div class="form-group">
                            <label class="form-label">Client Name *</label>
                            <input type="text" class="form-control" id="clientName" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Contact Number *</label>
                            <input type="tel" class="form-control" id="contactNumber" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="emailAddress">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Organization/Company</label>
                            <input type="text" class="form-control" id="organization">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Event Date *</label>
                            <input type="date" class="form-control" id="eventDate" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Event Time *</label>
                            <input type="time" class="form-control" id="eventTime" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Duration (hours) *</label>
                            <input type="number" class="form-control" id="duration" min="1" max="24" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Expected Attendees</label>
                            <input type="number" class="form-control" id="attendees" min="1">
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">Event Title/Purpose *</label>
                            <input type="text" class="form-control" id="eventTitle" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">Special Requirements/Notes</label>
                            <textarea class="form-control textarea" id="specialRequirements" placeholder="Please specify any special requirements, setup instructions, or additional notes..."></textarea>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitBooking()">Create Booking</button>
            </div>
        </div>
    </div>
        