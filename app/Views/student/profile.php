<?php
$session = session();
$userId = $session->get('user_id');
$isLoggedIn = !empty($userId);
$userRole = $session->get('role');
$userName = $session->get('full_name');

// Debug logging
if (!$isLoggedIn) {
    log_message('error', 'Profile page accessed without valid user_id in session');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | CSPC Digital Booking System</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
   <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com data:; img-src 'self' data: https:; connect-src 'self';">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/dashboard/dashboard.css'); ?>">
     <link rel="stylesheet" href="<?= base_url('css/dashboard/profile.css'); ?>">
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
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/student/bookings') ?>">
                            <i class="fas fa-calendar-check"></i> My Bookings
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link active" href="<?= site_url('/student/profile') ?>">
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
                    <h2>Edit Profile</h2>
                    <p>Update your personal information and account settings</p>
                </div>

                <!-- Alert Messages -->
                <div id="alertContainer"></div>

                <!-- Profile Content -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card profile-card">
                            <div class="card-header">
                                <h5><i class="fas fa-user-edit"></i> Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <form id="profileForm">
                                    <input type="hidden" id="baseUrl" value="<?= base_url() ?>">
                                    <input type="hidden" id="userId" value="<?= $userId ?>">
                                    
                                    <div class="form-group mb-4">
                                        <label for="fullName" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="fullName" name="full_name" autocomplete="name"required>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email"class="form-control" id="email" name="email" autocomplete="email" required>
                                        <small class="form-text">We'll never share your email with anyone else.</small>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="contactNumber" class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" id="contactNumber" name="contact_number" autocomplete="tel" placeholder="e.g., 09171234567">
                                    </div>


                                    <div class="button-group">
                                        <button type="submit" class="btn btn-primary" id="saveBtn">
                                            <i class="fas fa-save"></i> Save Changes
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='<?= site_url('/dashboard') ?>'">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Change Password Card -->
                        <div class="card profile-card mt-4">
                            <div class="card-header">
                                <h5><i class="fas fa-lock"></i> Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form id="passwordForm">
                                    <div class="form-group mb-4">
                                        <label for="currentPassword" class="form-label">Current Password *</label>
                                        <input type="password" class="form-control" id="currentPassword" name="current_password" autocomplete="current-password" required>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="newPassword" class="form-label">New Password *</label>
                                        <input type="password"class="form-control" id="newPassword" name="new_password" autocomplete="new-password" required minlength="6">
                                        <small class="form-text">Minimum 6 characters</small>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="confirmPassword" class="form-label">Confirm New Password *</label>
                                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" autocomplete="new-password" required>
                                    </div>

                                    <button type="submit" class="btn btn-warning" id="changePasswordBtn">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card profile-card">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle"></i> Account Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-item">
                                    <label>Member Since</label>
                                    <p id="memberSince">Loading...</p>
                                </div>
                                <div class="info-item">
                                    <label>Last Updated</label>
                                    <p id="lastUpdated">Loading...</p>
                                </div>
                                <div class="info-item">
                                    <label>Account Status</label>
                                    <p><span class="badge bg-success">Active</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="card profile-card mt-4">
                            <div class="card-header">
                                <h5><i class="fas fa-shield-alt"></i> Security Tips</h5>
                            </div>
                            <div class="card-body">
                                <ul class="security-tips">
                                    <li><i class="fas fa-check-circle"></i> Use a strong password</li>
                                    <li><i class="fas fa-check-circle"></i> Never share your password</li>
                                    <li><i class="fas fa-check-circle"></i> Update your contact info</li>
                                    <li><i class="fas fa-check-circle"></i> Review your bookings regularly</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card profile-card mt-4 border-danger">
    <div class="card-header bg-danger text-white">
        <h5><i class="fas fa-exclamation-triangle"></i> Danger Zone</h5>
    </div>
    <div class="card-body">
        <h6 class="text-danger mb-3">Delete Account</h6>
        <p class="text-muted mb-3">
            Once you delete your account, there is no going back. Please be certain.
        </p>
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
            <i class="fas fa-trash-alt"></i> Delete My Account
        </button>
    </div>
</div>
                    </div>
                </div>
                
            </div>

        </div>
<!-- Add this in the col-lg-4 column, after Security Tips card -->

</div>
<!-- Delete Account Modal - Add before </body> -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Delete Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>
                
                <p class="mb-3">Choose how you want to delete your account:</p>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="deleteOption" id="deleteProfileOnly" value="profile_only" checked>
                    <label class="form-check-label" for="deleteProfileOnly">
                        <strong>Delete Profile Only</strong>
                        <small class="d-block text-muted">Your booking history will be retained for administrative purposes. Your personal information will be removed but bookings will remain anonymous.</small>
                    </label>
                </div>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="radio" name="deleteOption" id="deleteEverything" value="everything">
                    <label class="form-check-label" for="deleteEverything">
                        <strong>Delete Everything</strong>
                        <small class="d-block text-muted">Permanently delete your account and all booking history. This cannot be recovered.</small>
                    </label>
                </div>
                
 <div class="form-group mb-4">
    <label for="deletePassword" class="form-label">Confirm your password:</label>
    <input type="password" 
           class="form-control" 
           id="deletePassword" 
           name="delete_password"
           autocomplete="current-password" 
           required>
</div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmDelete">
                    <label class="form-check-label" for="confirmDelete">
                        I understand this action is permanent and cannot be undone
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                    <i class="fas fa-trash-alt"></i> Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('js/student/profile.js'); ?>"></script>
</body>
</html>