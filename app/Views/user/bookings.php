<?php
$session = session();
$isLoggedIn = $session->get('user_id') !== null;
$userRole = $session->get('role');
$userName = $session->get('full_name');
$userId = $session->get('user_id');
$userEmail = $session->get('email');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | CSPC Digital Booking System</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
   <link rel="stylesheet" href="<?= base_url('css/dashboard/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('css/dashboard/bookings.css'); ?>">
</head>
<body>
    <!-- Navigation -->
   <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('/') ?>">
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
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/') ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/facilities') ?>">Facilities</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/event') ?>">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/contact') ?>">Contact</a>
                    </li>
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
                        <a class="nav-link" href="<?= site_url('/user/dashboard') ?>">
                            <i class="fas fa-tachometer-alt"></i> Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= site_url('/user/bookings') ?>">
                            <i class="fas fa-calendar-check"></i> My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/user/profile') ?>">
                            <i class="fas fa-user-edit"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/user/history') ?>">
                            <i class="fas fa-history"></i> Booking History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/user/attendance') ?>">
                            <i class="fas fa-qrcode"></i> Attendance
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="dashboard-header">
                    <h2>My Bookings</h2>
                    <p>View and manage your facility bookings</p>
                </div>

                <!-- Alert Messages -->
                <div id="alertContainer"></div>

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" id="dateFilter">
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="searchFilter" placeholder="Search by facility or event...">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100" onclick="filterBookings()">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bookings List -->
                <div id="bookingsContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Loading your bookings...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingDetailsContent">
                <!-- Content loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

    <!-- Upload Receipt Modal -->
    <div class="modal fade" id="uploadReceiptModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload"></i> Upload Payment Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Please upload your payment receipt. Accepted formats: PDF, JPG, PNG (Max 5MB)
                    </div>

                    <!-- Receipt Status Section -->
                    <div id="receiptStatusSection" style="display: none;" class="mb-4 p-3 bg-light border rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="fas fa-check-circle text-success"></i> Receipt on File</h6>
                                <small class="text-muted" id="receiptStatusText"></small>
                            </div>
                            <a href="#" id="downloadReceiptBtn" class="btn btn-sm btn-success" onclick="downloadReceipt(event)" target="_blank">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>

                    <form id="uploadReceiptForm" enctype="multipart/form-data">
                        <input type="hidden" id="uploadBookingId" name="booking_id">
                        
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                            <h5>Drop your receipt here or click to browse</h5>
                            <p class="text-muted">Accepted formats: PDF, JPG, PNG (Max 5MB)</p>
                            <input type="file" id="receiptFile" name="receipt" accept=".pdf,.jpg,.jpeg,.png" hidden required>
                        </div>

                        <div id="filePreview" class="mt-3" style="display: none;">
                            <div class="file-preview-card">
                                <i class="fas fa-file-pdf fa-2x"></i>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-0" id="fileName"></p>
                                    <small class="text-muted" id="fileSize"></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeFile()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitReceiptBtn" onclick="uploadReceipt()" disabled>
                        <i class="fas fa-check"></i> Upload Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelBookingModalLabel">
                    <i class="fas fa-times-circle"></i> Cancel Booking
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cancelBookingId">
                <input type="hidden" id="cancelBookingStatus">
                <input type="hidden" id="cancelBookingCost">
                
                <!-- Refund Policy Section (shown only for paid bookings) -->
                <div id="refundPolicySection" class="alert alert-warning border-0" style="background-color: #fff3cd; border-left: 4px solid #ffc107; display: none;">
                    <h6 class="alert-heading mb-3">
                        <i class="fas fa-info-circle" style="color: #856404;"></i> Refund Policy
                    </h6>
                    <div style="color: #856404; font-size: 14px; line-height: 1.6;">
                        <p class="mb-2"><strong>If approved, a refund shall be allowed on the following conditions:</strong></p>
                        <ul class="mb-0" style="margin-left: 20px;">
                            <li class="mb-2">
                                <strong>80% Refund:</strong> If cancellation is done <strong>15 working days</strong> prior to the event (in case full payment has been made)
                            </li>
                            <li>
                                <strong>50% Refund:</strong> If cancellation is done <strong>5 working days</strong> prior to the event (in case full payment has been made)
                            </li>
                        </ul>
                    </div>
                </div>
                
                <p class="text-muted mb-4">Please note: You must submit a cancellation letter to the office for review.</p>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Reason for Cancellation *</label>
                    <select class="form-select" id="cancelReason" required onchange="updateCancelSubmitButtonState()">
                        <option value="">-- Select a reason --</option>
                        <option value="schedule-conflict">Schedule Conflict</option>
                        <option value="facility-unavailable">Facility Unavailable</option>
                        <option value="policy-violation">Policy Violation</option>
                        <option value="incomplete-requirements">Incomplete Requirements</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Additional Notes (Optional)</label>
                    <textarea class="form-control" id="cancelNotes" rows="3" placeholder="Add any additional notes..."></textarea>
                </div>
                
                <!-- Cancellation Letter Upload -->
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-file-pdf text-danger"></i> Cancellation Letter *
                    </label>
                    <p style="font-size: 13px; color: #666; margin-bottom: 10px;">
                        Upload a formal letter requesting cancellation to be submitted to the office (PDF, JPG, PNG - Max 10MB)
                    </p>
                    <div class="upload-area" onclick="document.getElementById('cancelLetterFile').click()" style="border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 8px; cursor: pointer; background-color: #f9f9f9; transition: all 0.3s;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #007bff; margin-bottom: 10px;"></i>
                        <p class="mb-0" style="font-weight: 500;">Click to upload or drag & drop</p>
                        <small style="color: #999;">PDF, JPG, PNG (Max 10MB)</small>
                    </div>
                    <input type="file" id="cancelLetterFile" name="cancel_letter" accept=".pdf,.jpg,.jpeg,.png" hidden onchange="handleCancelLetterSelect(this)">
                    
                    <div id="cancelLetterPreview" style="display: none; margin-top: 15px; padding: 12px; background-color: #e8f5e9; border-radius: 8px; border-left: 4px solid #4caf50;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: #4caf50; font-size: 20px;"></i>
                            <div style="flex-grow: 1;">
                                <p id="cancelLetterName" style="margin: 0; font-weight: 500; font-size: 14px;"></p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCancelLetter()" style="padding: 4px 8px;">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left"></i> Keep Booking
                </button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn" onclick="cancelBooking()" disabled>
                    <i class="fas fa-check"></i> Confirm Cancellation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Extension Request Modal -->
<div class="modal fade" id="extensionRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clock"></i> Request Hours Extension</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Request additional hours for your existing booking. The system will calculate the cost automatically based on the facility's hourly rate.
                </div>

                <input type="hidden" id="extensionBookingId" value="">
                
                <div class="mb-3">
                    <label for="extensionHours" class="form-label">
                        Additional Hours <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="extensionHours" 
                               min="1" max="12" value="1" required 
                               onchange="calculateExtensionCost()">
                        <span class="input-group-text">hours</span>
                    </div>
                    <small class="form-text text-muted">You can request 1 to 12 additional hours</small>
                </div>

                <div class="mb-3">
                    <label for="extensionReason" class="form-label">
                        Reason for Extension
                    </label>
                    <textarea class="form-control" id="extensionReason" 
                              rows="3" placeholder="Why do you need additional hours? (optional)"></textarea>
                </div>

                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-calculator"></i> Cost Calculation</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-row mb-2">
                                    <span class="detail-label">Hourly Rate:</span>
                                    <span class="detail-value" id="extensionHourlyRate">₱0</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-row mb-2">
                                    <span class="detail-label">Hours:</span>
                                    <span class="detail-value" id="extensionHoursDisplay">1</span>
                                </div>
                            </div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><strong>Total Extension Cost:</strong></span>
                            <span class="detail-value"><strong id="extensionTotalCost">₱0</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Loading and Error States -->
                <div id="extensionErrorAlert" class="alert alert-danger mt-3" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i> <span id="extensionErrorText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="submitExtensionBtn" 
                        onclick="submitExtensionRequest()" disabled>
                    <i class="fas fa-check"></i> Submit Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reschedule Booking Modal -->
<div class="modal fade" id="rescheduleBookingModal" tabindex="-1" aria-labelledby="rescheduleBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="rescheduleBookingModalLabel">
                    <i class="fas fa-redo"></i> Reschedule Booking
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rescheduleBookingId">
                
                <!-- Current Booking Info -->
                <div class="alert alert-info border-0" style="background-color: #e3f2fd; border-left: 4px solid #2196F3;">
                    <h6 class="alert-heading mb-3">
                        <i class="fas fa-info-circle" style="color: #1976D2;"></i> Current Booking
                    </h6>
                    <div id="currentBookingInfo" style="color: #0d47a1; font-size: 14px; line-height: 1.6;">
                        <!-- Dynamically filled -->
                    </div>
                </div>
                
                <p class="text-muted mb-4">Please provide the reason and your preferred new date and time for rescheduling.</p>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Reason for Rescheduling *</label>
                    <select class="form-select" id="rescheduleReason" required onchange="updateRescheduleSubmitButtonState()">
                        <option value="">-- Select a reason --</option>
                        <option value="schedule-conflict">Schedule Conflict</option>
                        <option value="venue-change">Venue Change</option>
                        <option value="date-preference">Date Preference</option>
                        <option value="event-change">Event Change</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Preferred Date *</label>
                            <input type="date" class="form-control" id="rescheduleDate" required onchange="updateRescheduleSubmitButtonState()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Preferred Time *</label>
                            <input type="time" class="form-control" id="rescheduleTime" required onchange="updateRescheduleSubmitButtonState()">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Additional Notes (Optional)</label>
                    <textarea class="form-control" id="rescheduleNotes" rows="3" placeholder="Add any additional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-warning text-dark" id="submitRescheduleBtn" onclick="submitReschedule()" disabled>
                    <i class="fas fa-check"></i> Submit Reschedule Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container for notifications -->
<div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('js/dashboard/bookings.js'); ?>"></script>
    <script src="<?= base_url('js/dashboard/extensionRequest.js'); ?>"></script>


</body>
</html>