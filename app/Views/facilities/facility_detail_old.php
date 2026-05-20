<?php
// Check if user is logged in
$session = session();
$isLoggedIn = $session->get('user_id') !== null;
$userRole = $session->get('role');
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($facility['name']) ?> - CSPC Digital Booking System</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= base_url('css/facilities/gymnasium.css'); ?>">
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
                    <li class="nav-item">
                        <?php if ($isLoggedIn): ?>
                            <button class="nav-link dashboard-btn btn btn-success px-3 py-2" onclick="window.location.href='<?= site_url('/user/dashboard') ?>'">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </button>
                        <?php else: ?>
                            <button class="nav-link login-btn btn btn-primary px-3 py-2" onclick="window.location.href='<?= site_url('/login') ?>'">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="hero-content">
          <div class="breadcrumb-nav">
            <a href="<?= site_url('/') ?>">Home</a>
            <span class="separator">•</span>
            <a href="<?= site_url('/facilities') ?>">Facilities</a>
            <span class="separator">•</span>
            <span><?= esc($facility['name']) ?></span>
          </div>
          <h1><?= esc($facility['name']) ?></h1>
          <p>
            <?= esc($facility['description'] ?? 'Book this facility for your events, meetings, and activities.') ?>
          </p>
        </div>
      </div>
    </section>

    <!-- Facility Details Section -->
    <section class="facility-details-section">
      <div class="container">
        <!-- Facility Overview -->
        <div class="facility-hero">
          <div class="facility-icon-large">
            <i class="fas fa-building"></i>
          </div>
          <h2><?= esc($facility['name']) ?></h2>
          <p>
            <?= esc($facility['description'] ?? 'Modern facility available for your events and activities.') ?>
          </p>
        </div>

        <!-- Packages Section -->
        <div class="packages-section">
          <h3>Rental Packages</h3>

          <?php if (empty($plans)): ?>
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i> No packages available at the moment. Please contact us for pricing.
            </div>
          <?php else: ?>
            <div class="row g-4">
              <?php foreach ($plans as $plan): ?>
                <div class="col-lg-4 col-md-6">
                  <div class="package-card">
                    <div class="package-header">
                      <div class="package-name"><?= esc($plan['name']) ?></div>
                      <div class="package-price">₱<?= number_format($plan['price'], 0) ?></div>
                      <div class="package-duration"><?= esc($plan['duration']) ?></div>
                    </div>
                    <div class="package-features">
                      <h5>Includes:</h5>
                      <ul class="feature-list">
                        <?php if (isset($plan['features']) && !empty($plan['features'])): ?>
                          <?php foreach ($plan['features'] as $feature): ?>
                            <li><i class="fas fa-check"></i> <?= esc($feature['feature_name']) ?></li>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <li><i class="fas fa-check"></i> Standard facility access</li>
                          <li><i class="fas fa-check"></i> Basic amenities</li>
                        <?php endif; ?>
                      </ul>
                    </div>
                    <button class="book-package-btn" onclick="openBookingModal('<?= esc($plan['name']) ?>', '<?= esc($facility['facility_key']) ?>', '<?= esc($plan['plan_key']) ?>')">
                      <i class="fas fa-calendar-check"></i> Book Now
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Additional Information -->
        <div class="additional-info mt-5">
          <div class="row">
            <div class="col-md-6">
              <div class="info-card">
                <h4><i class="fas fa-info-circle"></i> Facility Information</h4>
                <ul>
                  <li><strong>Status:</strong> <?= $facility['is_maintenance'] ? '<span class="badge bg-warning">Under Maintenance</span>' : '<span class="badge bg-success">Available</span>' ?></li>
                  <li><strong>Additional Hours Rate:</strong> ₱<?= number_format($facility['additional_hours_rate'] ?? 0, 2) ?> / hour</li>
                  <?php if (isset($facility['extended_hour_rate']) && $facility['extended_hour_rate']): ?>
                    <li><strong>Extended Hours Rate:</strong> ₱<?= number_format($facility['extended_hour_rate'], 2) ?> / hour</li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-card">
                <h4><i class="fas fa-question-circle"></i> Need Help?</h4>
                <p>Have questions about booking this facility? Our team is here to help!</p>
                <a href="<?= site_url('/contact') ?>" class="btn btn-primary">
                  <i class="fas fa-envelope"></i> Contact Us
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Book <?= esc($facility['name']) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div id="bookingFormContainer">
              <!-- Booking form will be loaded here -->
              <p>Redirecting to booking page...</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <div class="row">
          <div class="col-lg-4 col-md-6 footer-section">
            <div class="footer-logo">
              <div class="cspc-logo-nav">
                <i class="fas fa-graduation-cap"></i>
              </div>
              CSPC Sphere
            </div>
            <p class="mb-4">
              The ultimate platform for booking and managing campus facilities at Camarines Sur Polytechnic College.
            </p>
          </div>
          <div class="col-lg-4 col-md-6 footer-section">
            <h5>Quick Links</h5>
            <a href="<?= site_url('/') ?>">Home</a>
            <a href="<?= site_url('/facilities') ?>">Facilities</a>
            <a href="<?= site_url('/contact') ?>">Contact</a>
          </div>
          <div class="col-lg-4 col-md-6 footer-section">
            <h5>Contact Us</h5>
            <p><i class="fas fa-map-marker-alt me-2"></i> Nabua, Camarines Sur, Philippines</p>
            <p><i class="fas fa-phone me-2"></i> (054) 361-2101</p>
            <p><i class="fas fa-envelope me-2"></i> cspc@edu.ph</p>
          </div>
        </div>
        <div class="footer-bottom">
          <p>&copy; 2024 CSPC Sphere. All rights reserved.</p>
        </div>
      </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
      function openBookingModal(packageName, facilityKey, planKey) {
        // Redirect to the booking page with facility and plan parameters
        window.location.href = '<?= site_url('booking') ?>?facility=' + encodeURIComponent(facilityKey) + '&plan=' + encodeURIComponent(planKey);
      }

      // Navbar scroll effect
      window.addEventListener("scroll", function () {
        const navbar = document.querySelector(".navbar");
        if (window.scrollY > 50) {
          navbar.classList.add("scrolled");
        } else {
          navbar.classList.remove("scrolled");
        }
      });
    </script>
  </body>
</html>
