<?php
// Check if user is logged in
$session = session();
$isLoggedIn = $session->get('user_id') !== null;
$userRole = $session->get('role');
$userName = $session->get('full_name');
$userEmail = $session->get('email');
$userPhone = $session->get('contact_number');

// Redirect if not logged in or not employee
if (!$isLoggedIn || ($userRole !== 'employee' && $userRole !== 'admin')) {
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
    <link rel="stylesheet" href="<?= base_url('css/faculty-book.css'); ?>">
    <style>
        .booking-type-selector {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .booking-type-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid transparent;
            height: 100%;
        }

        .booking-type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .booking-type-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8eeff 100%);
        }

        .booking-type-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .booking-type-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #1e293b;
        }

        .booking-type-description {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .booking-type-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 10px;
        }

        .badge-free {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-paid {
            background: #fef3c7;
            color: #92400e;
        }
    </style>
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
                            <li><a class="dropdown-item" href="<?= site_url('/employee/dashboard') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
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
                        <a class="nav-link" href="<?= site_url('/employee/dashboard') ?>">
                            <i class="fas fa-tachometer-alt"></i> Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= site_url('/employee/book') ?>">
                            <i class="fas fa-calendar-plus"></i> Book Facility
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/employee/bookings') ?>">
                            <i class="fas fa-calendar-check"></i> My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/employee/profile') ?>">
                            <i class="fas fa-user-edit"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/employee/history') ?>">
                            <i class="fas fa-history"></i> Booking History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/employee/attendance') ?>">
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
                        <p>Choose your booking type and select a facility below</p>
                    </div>

                    <!-- Booking Type Selector -->
                    <div class="booking-type-selector">
                        <h3 style="color: white; margin-bottom: 20px; text-align: center;">
                            <i class="fas fa-hand-pointer"></i> Select Booking Type
                        </h3>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="booking-type-card" id="freeBookingCard" onclick="selectBookingType('free')">
                                    <div class="text-center">
                                        <div class="booking-type-icon" style="color: #16a34a;">
                                            <i class="fas fa-graduation-cap"></i>
                                        </div>
                                        <h4 class="booking-type-title">Academic/Free Booking</h4>
                                        <p class="booking-type-description">
                                            For academic activities, student events, and institutional programs. No payment required, but requires proper documentation and approval.
                                        </p>
                                        <span class="booking-type-badge badge-free">
                                            <i class="fas fa-check-circle"></i> FREE
                                        </span>
                                    </div>
                                    <div class="mt-3">
                                        <small style="color: #64748b;">
                                            <i class="fas fa-info-circle"></i> Requires: Permission letter, Request letter, Approval letter
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="booking-type-card" id="paidBookingCard" onclick="selectBookingType('paid')">
                                    <div class="text-center">
                                        <div class="booking-type-icon" style="color: #f59e0b;">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <h4 class="booking-type-title">Commercial/Paid Booking</h4>
                                        <p class="booking-type-description">
                                            For private events, commercial activities, and external programs. Standard rental rates apply with full service amenities.
                                        </p>
                                        <span class="booking-type-badge badge-paid">
                                            <i class="fas fa-dollar-sign"></i> PAID
                                        </span>
                                    </div>
                                    <div class="mt-3">
                                        <small style="color: #64748b;">
                                            <i class="fas fa-info-circle"></i> Includes: Package pricing, Equipment rental, Additional services
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Booking Type Display -->
                    <div id="selectedTypeDisplay" style="display: none;" class="alert alert-info mb-4">
                        <i class="fas fa-info-circle"></i> <strong>Selected:</strong> <span id="selectedTypeText"></span>
                        <button type="button" class="btn btn-sm btn-outline-primary float-end" onclick="clearBookingType()">
                            <i class="fas fa-redo"></i> Change Booking Type
                        </button>
                    </div>

                    <!-- Facilities Grid -->
                    <div class="facilities-grid" id="facilitiesGrid" style="display: none;">
                        <?php if (!empty($facilities)): ?>
                            <?php foreach ($facilities as $facility): ?>
                                <div class="facility-card" onclick="openFacultyBookingModal('<?= esc($facility['facility_key']) ?>', <?= $facility['id'] ?>)">
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
                                            <span class="price-range" id="priceRange">Select booking type</span>
                                            <button class="book-btn">Book Now</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No facilities available for booking at this time.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include modals from student_book.php and Auditorium.php -->
    <!-- Free Booking Modal (Student-style) -->
    <?php include('faculty_free_booking_modal.php'); ?>

    <!-- Paid Booking Modal (User-style) -->
    <?php include('faculty_paid_booking_modal.php'); ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('js/faculty-book.js?v=' . time()) ?>"></script>
</body>
</html>
