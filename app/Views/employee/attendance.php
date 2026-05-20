<?php
$session = session();
$isLoggedIn = $session->get('user_id') !== null;
$userRole = $session->get('role');
$userName = $session->get('full_name');
$userId = $session->get('user_id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance | CSPC Digital Booking System</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/dashboard/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('css/dashboard/attendance.css'); ?>">
</head>
<body>
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
                            <li><a class="dropdown-item" href="<?= site_url('/dashboard') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
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
                        <a class="nav-link " href="<?= site_url('/faculty/book') ?>">
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
                    <li class="nav-item active">
                        <a class="nav-link" href="<?= site_url('/faculty/attendance') ?>">
                            <i class="fas fa-qrcode"></i> Attendance
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="dashboard-header">
                    <h2>Event Attendance</h2>
                    <p>Scan QR codes to track guest attendance for your events</p>
                </div>

                <!-- Alert Messages -->
                <div id="alertContainer"></div>

                <!-- Event Selection Section -->
                <div class="card mb-4" id="eventSelectionCard">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt"></i> Select Event</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <select class="form-select" id="eventSelector">
                                    <option value="">Choose an event to track attendance...</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary w-100" onclick="loadEventAttendance()">
                                    <i class="fas fa-check"></i> Start Tracking
                                </button>
                            </div>
                        </div>
                        <div id="eventDetails" class="mt-3" style="display: none;">
                            <div class="event-info-card">
                                <h6>Event Details:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Event:</strong> <span id="eventName"></span></p>
                                        <p><strong>Facility:</strong> <span id="facilityName"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Date:</strong> <span id="eventDate"></span></p>
                                        <p><strong>Time:</strong> <span id="eventTime"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Tracking Section -->
                <div id="attendanceSection" style="display: none;">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-info">
                                    <h3 id="totalGuests">0</h3>
                                    <p>Total Guests</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stat-info">
                                    <h3 id="attendedCount">0</h3>
                                    <p>Attended</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                                <div class="stat-info">
                                    <h3 id="pendingCount">0</h3>
                                    <p>Pending</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-info">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="stat-info">
                                    <h3 id="attendanceRate">0%</h3>
                                    <p>Attendance Rate</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- QR Scanner and Guest List -->
                    <div class="row">
                        <!-- QR Scanner -->
                        <div class="col-md-5">
                            <div class="card scanner-card">
                                <div class="card-header">
                                    <h5><i class="fas fa-qrcode"></i> QR Code Scanner</h5>
                                </div>
                                <div class="card-body">
                                    <div id="qrScanner" class="qr-scanner">
                                        <video id="qrVideo" autoplay></video>
                                        <div class="scanner-overlay">
                                            <div class="scanner-frame"></div>
                                        </div>
                                    </div>
                                    <div class="scanner-controls mt-3">
                                        <button class="btn btn-success" id="startScanBtn" onclick="startScanner()">
                                            <i class="fas fa-play"></i> Start Scanner
                                        </button>
                                        <button class="btn btn-danger" id="stopScanBtn" onclick="stopScanner()" style="display: none;">
                                            <i class="fas fa-stop"></i> Stop Scanner
                                        </button>
                                    </div>
                                    <div id="scanResult" class="scan-result mt-3" style="display: none;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Guest List -->
                        <div class="col-md-7">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5><i class="fas fa-list"></i> Registered Guests</h5>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="downloadAttendance('all')">
                                            <i class="fas fa-download"></i> All
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="downloadAttendance('attended')">
                                            <i class="fas fa-download"></i> Attended
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="downloadAttendance('pending')">
                                            <i class="fas fa-download"></i> Pending
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Guest Name</th>
                                                    <th>Status</th>
                                                    <th>Time</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="guestTableBody">
                                                <!-- Dynamic content -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Check-in Modal -->
    <div class="modal fade" id="manualCheckInModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Manual Check-in</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="manualGuestId">
                    <p>Mark <strong id="manualGuestName"></strong> as attended?</p>
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea class="form-control" id="manualNotes" rows="2" placeholder="Add any notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="confirmManualCheckIn()">
                        <i class="fas fa-check"></i> Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code View Modal -->
    <div class="modal fade" id="qrCodeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-qrcode"></i> Guest QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p><strong id="qrGuestName"></strong></p>
                    <div id="qrCodeImageContainer">
                        <!-- QR code image will be loaded here -->
                    </div>
                    <p class="mt-2"><small>QR Code: <span id="qrCodeValue"></span></small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="downloadGuestQR()">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- Load html5-qrcode library (local) -->
<script src="<?= base_url('js/vendor/html5-qrcode.min.js'); ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('js/student/attendance.js'); ?>"></script>
</body>
</html>