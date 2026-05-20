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
                        <a class="nav-link" href="<?= site_url('/student/dashboard') ?>">
                            <i class="fas fa-tachometer-alt"></i> Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/student/book') ?>">
                            <i class="fas fa-calendar-plus"></i> Book Facility
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="<?= site_url('/student/bookings') ?>">
                            <i class="fas fa-calendar-check"></i> My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/student/profile') ?>">
                            <i class="fas fa-user-edit"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/student/history') ?>">
                            <i class="fas fa-history"></i> Booking History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/student/attendance') ?>">
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

<div class="modal fade" id="uploadDocumentsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-upload"></i> Upload Required Documents</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="uploadDocumentsForm" enctype="multipart/form-data">
          <input type="hidden" id="uploadBookingId" name="booking_id">
          
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Please upload the following required documents. Accepted formats: PDF, JPG, PNG (Max 10MB each)
          </div>
          
          <!-- Permission Letter -->
          <div class="mb-4">
            <label class="form-label fw-bold">
              1. Permission Letter <span id="permission_letter_status"></span>
            </label>
            <div class="upload-area-small" onclick="document.getElementById('permissionLetter').click()">
              <i class="fas fa-cloud-upload-alt"></i>
              <span>Click to upload Permission Letter</span>
            </div>
            <input type="file" 
                   id="permissionLetter" 
                   name="permission_letter" 
                   accept=".pdf,.jpg,.jpeg,.png" 
                   hidden 
                   onchange="handleDocumentSelect(this, 'permissionPreview')">
            
            <div id="permissionPreview" class="file-preview-card mt-2" style="display: none;">
              <i class="fas fa-file-pdf fa-2x"></i>
              <div class="flex-grow-1 ms-3">
                <p class="mb-0 file-name"></p>
                <small class="text-muted file-size"></small>
              </div>
              <button type="button" class="btn btn-sm btn-danger" 
                      onclick="removeDocument('permissionLetter', 'permissionPreview')">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          
          <!-- Request Letter -->
          <div class="mb-4">
            <label class="form-label fw-bold">
              2. Request Letter <span id="request_letter_status"></span>
            </label>
            <div class="upload-area-small" onclick="document.getElementById('requestLetter').click()">
              <i class="fas fa-cloud-upload-alt"></i>
              <span>Click to upload Request Letter</span>
            </div>
            <input type="file" 
                   id="requestLetter" 
                   name="request_letter" 
                   accept=".pdf,.jpg,.jpeg,.png" 
                   hidden 
                   onchange="handleDocumentSelect(this, 'requestPreview')">
            
            <div id="requestPreview" class="file-preview-card mt-2" style="display: none;">
              <i class="fas fa-file-pdf fa-2x"></i>
              <div class="flex-grow-1 ms-3">
                <p class="mb-0 file-name"></p>
                <small class="text-muted file-size"></small>
              </div>
              <button type="button" class="btn btn-sm btn-danger" 
                      onclick="removeDocument('requestLetter', 'requestPreview')">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          
          <!-- Approval Letter -->
          <div class="mb-4">
            <label class="form-label fw-bold">
              3. Approval Letter <span id="approval_letter_status"></span>
            </label>
            <div class="upload-area-small" onclick="document.getElementById('approvalLetter').click()">
              <i class="fas fa-cloud-upload-alt"></i>
              <span>Click to upload Approval Letter</span>
            </div>
            <input type="file" 
                   id="approvalLetter" 
                   name="approval_letter" 
                   accept=".pdf,.jpg,.jpeg,.png" 
                   hidden 
                   onchange="handleDocumentSelect(this, 'approvalPreview')">
            
            <div id="approvalPreview" class="file-preview-card mt-2" style="display: none;">
              <i class="fas fa-file-pdf fa-2x"></i>
              <div class="flex-grow-1 ms-3">
                <p class="mb-0 file-name"></p>
                <small class="text-muted file-size"></small>
              </div>
              <button type="button" class="btn btn-sm btn-danger" 
                      onclick="removeDocument('approvalLetter', 'approvalPreview')">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="submitDocumentsBtn" onclick="uploadDocuments()" disabled>
          <i class="fas fa-check"></i> Upload Documents
        </button>
      </div>
    </div>
  </div>
</div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        const currentUserEmail = '<?= $userEmail ?>';
    </script>
    <script src="<?= base_url('js/student/bookings.js'); ?>"></script>
</body>
</html>