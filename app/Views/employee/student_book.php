<?php
// Check if user is logged in
$session = session();
$isLoggedIn = $session->get('user_id') !== null;
$userRole = $session->get('role');
$userName = $session->get('full_name');
$userEmail = $session->get('email');
$userPhone = $session->get('contact_number');

// Redirect if not logged in
if (!$isLoggedIn) {
    return redirect()->to('login');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Facility | CSPC Digital Booking System</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/dashboard/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/student.css'); ?>">

</head>
<body>
    <div class="toast-container" id="toastContainer"></div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="">
                <div class="cspc-logo-nav">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                CSPC Sphere
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?= $userName ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= site_url('/student/dashboard') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                                                  <li><a class="dropdown-item" href="<?= site_url('/logout') ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-header">
                    <h5>Dashboard</h5>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/faculty/dashboard') ?>">
                            <i class="fas fa-tachometer-alt"></i> Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= site_url('/faculty/book') ?>">
                            <i class="fas fa-calendar-plus"></i> Book Facility
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/faculty/bookings') ?>">
                            <i class="fas fa-calendar-check"></i> My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/faculty/profile') ?>">
                            <i class="fas fa-user-edit"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/faculty/history') ?>">
                            <i class="fas fa-history"></i> Booking History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/faculty/attendance') ?>">
                            <i class="fas fa-qrcode"></i> Attendance
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="booking-page">
                    <div class="page-title">
                        <h2>Book a Facility</h2>
                        <p>Select a facility below to start your booking</p>
                    </div>

                    <!-- Facilities Grid -->
                  <div class="facilities-grid">
    <?php if (!empty($facilities)): ?>
        <?php foreach ($facilities as $facility): ?>
            <div class="facility-card" onclick="openStudentBookingModal('<?= esc($facility['facility_key']) ?>', <?= $facility['id'] ?>)">
                <div class="facility-image">
                    <?php
                    // Map facility icons
                    $icons = [
                        'auditorium' => '🎭',
                        'gymnasium' => '🏀',
                        'function-hall' => '🏛️',
                        'pearl-restaurant' => '🍽️',
                        'staff-house' => '🏠',
                        'classrooms' => '📖'
                    ];
                    echo $icons[$facility['facility_key']] ?? '🏢';
                    ?>
                </div>
                <div class="facility-info">
                    <h3 class="facility-title"><?= esc($facility['name']) ?></h3>
                    <p class="facility-description"><?= esc($facility['description'] ?? 'No description available') ?></p>
                    <div class="facility-features">
                        <span class="feature-tag">Air Conditioned</span>
                        <span class="feature-tag">Sound System</span>
                        <span class="feature-tag">Projector</span>
                    </div>
                    <div class="facility-price">
                        <span class="price-range">Free Booking</span>
                        <button class="book-btn">Book Now</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No facilities available for booking at this time.</p>
    <?php endif; ?>
</div>
  <!-- Student Booking Modal -->
<div id="studentBookingModal" class="modal student-booking-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Book Facility</h2>
            <span class="close" onclick="closeStudentModal()">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Basic Information Section -->
            <div class="plan-section">
                <h3 class="section-title">📝 Event Information</h3>
                <form id="studentBookingForm">
                    <!-- Hidden fields - auto-filled from session -->
                    <input type="hidden" id="clientName" value="<?= esc($userName ?? '') ?>">
                    <input type="hidden" id="clientEmail" value="<?= esc($userEmail ?? '') ?>">

                    <!-- Contact Number - NOW VISIBLE AND EDITABLE -->
                    <div class="form-group">
                        <label class="form-label">Contact Number *</label>
                        <input type="tel" class="form-control" id="contactNumber" 
                               value="<?= esc($userPhone ?? '') ?>" 
                               placeholder="e.g., 09123456789" 
                               required>
                        <small style="color: var(--gray); font-size: 12px;">Please enter a valid mobile number</small>
                    </div>

                    <!-- Organization (visible for editing) -->
                    <div class="form-group">
                        <label class="form-label">Organization/Group Name *</label>
                        <input type="text" class="form-control" id="organization" required>
                    </div>

                    <!-- Event Date -->
                    <div class="form-group">
                        <label class="form-label">Event Date *</label>
                        <input type="date" class="form-control" id="eventDate" required>
                    </div>

                    <!-- Event Time -->
                    <div class="form-group">
                        <label class="form-label">Event Time *</label>
                        <input type="time" class="form-control" id="eventTime" required>
                    </div>

                    <!-- Duration -->
                    <div class="form-group">
                        <label class="form-label">Duration (hours) *</label>
                        <input type="number" class="form-control" id="duration" min="1" max="12" value="4" required>
                    </div>

                    <!-- Attendees -->
                    <div class="form-group">
                        <label class="form-label">Expected Attendees</label>
                        <input type="number" class="form-control" id="attendees" min="1">
                    </div>

                    <!-- Address -->
                    <div class="form-group full-width">
                        <label class="form-label">Address</label>
                        <textarea class="form-control textarea" id="address" rows="2" placeholder="Optional, but if provided, must be at least 10 characters"></textarea>
                    </div>

                    <!-- Event Title -->
                    <div class="form-group full-width">
                        <label class="form-label">Event Title/Purpose *</label>
                        <input type="text" class="form-control" id="eventTitle" required>
                    </div>

                    <!-- Special Requirements -->
                    <div class="form-group full-width">
                        <label class="form-label">Special Requirements</label>
                        <textarea class="form-control textarea" id="specialRequirements"></textarea>
                    </div>
                </form>
            </div>

            <!-- Equipment Section -->
            <div class="plan-section">
                <h3 class="section-title">🔧 Equipment Needed</h3>
                <div class="equipment-grid" id="studentEquipmentGrid">
                    <!-- Will be populated dynamically -->
                </div>
            </div>

            <!-- Important Notice Section -->
<div class="plan-section" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 20px; margin-bottom: 20px; border-radius: 8px;">
    <h3 class="section-title" style="color: #92400e; margin-bottom: 15px;">
        <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Important Notice
    </h3>
    <div style="color: #78350f; font-size: 14px; line-height: 1.6;">
        <p style="margin-bottom: 10px; font-weight: 600;">
            <i class="fas fa-building" style="color: #f59e0b; margin-right: 8px;"></i>
            After submitting this booking, you must be able to pass the required documents wit <strong style="color: #92400e;">7 days</strong>
        </p>
        <p style="margin-bottom: 0; font-weight: 600; color: #dc2626;">
            <i class="fas fa-times-circle" style="margin-right: 8px;"></i>
            Failure to comply will result in automatic cancellation of your booking.
        </p>
    </div>
</div>

            <!-- Document Upload Section -->
<!-- Document Upload Section -->
<div class="plan-section upload-section">
    <h3 class="section-title">📎 Required Documents</h3>
    <p style="color: var(--gray); font-size: 14px; margin-bottom: 20px;">
        Please upload the following documents (PDF, JPG, PNG - Max 10MB each)
    </p>

    <!-- Permission Document -->
    <div class="upload-item" id="upload-permission">
        <div class="upload-header">
            <div class="upload-title">📄 Approved Permission to Conduct</div>
            <span class="upload-status">Not uploaded</span>
        </div>
        <p style="font-size: 13px; color: var(--gray); margin-bottom: 10px;">
            Official permission letter from your organization's adviser
        </p>
        <input type="file" id="file-permission" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileSelect(this, 'permission')" style="margin-bottom: 5px;">
        <div class="file-name-display" id="filename-permission" style="font-size: 12px; color: var(--gray); font-style: italic;"></div>
    </div>

    <!-- Request Letter -->
    <div class="upload-item" id="upload-request">
        <div class="upload-header">
            <div class="upload-title">📝 Letter Request for Venue</div>
            <span class="upload-status">Not uploaded</span>
        </div>
        <p style="font-size: 13px; color: var(--gray); margin-bottom: 10px;">
            Formal letter requesting the use of the facility
        </p>
        <input type="file" id="file-request" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileSelect(this, 'request')" style="margin-bottom: 5px;">
        <div class="file-name-display" id="filename-request" style="font-size: 12px; color: var(--gray); font-style: italic;"></div>
    </div>

    <!-- Approval Letter -->
    <div class="upload-item" id="upload-approval">
        <div class="upload-header">
            <div class="upload-title">✅ Approval Letter of the Venue</div>
            <span class="upload-status">Not uploaded</span>
        </div>
        <p style="font-size: 13px; color: var(--gray); margin-bottom: 10px;">
            Pre-approval or recommendation letter from authorized personnel
        </p>
        <input type="file" id="file-approval" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileSelect(this, 'approval')" style="margin-bottom: 5px;">
        <div class="file-name-display" id="filename-approval" style="font-size: 12px; color: var(--gray); font-style: italic;"></div>
    </div>
</div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeStudentModal()">Cancel</button>
            <button type="button" class="btn btn-success" onclick="submitStudentBooking()" id="submitStudentBtn" disabled>
                Submit Booking
            </button>
        </div>
    </div>
</div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('js/student-book.js') ?>"></script>
</body>
</html>