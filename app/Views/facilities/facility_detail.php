<?php
// Check if user is logged in
$session = session();
$isLoggedIn = $session->get('user_id') !== null;
$userRole = $session->get('role');
$userName = $session->get('full_name');
$userEmail = $session->get('email');
$userContact = $session->get('contact_number');

// Check if facility data is provided
if (empty($facility)) {
    throw new \CodeIgniter\Exceptions\PageNotFoundException('Facility not found');
}

// Get facility key for API calls
$facilityKey = $facility['facility_key'];
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
    <style>
      /* Toast Notification Styles */
      .toast-notification {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        animation: slideIn 0.3s ease-out;
        font-size: 14px;
        font-weight: 500;
        min-width: 300px;
      }

      .toast-content {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
      }

      .toast-icon {
        font-size: 18px;
        flex-shrink: 0;
      }

      .toast-message {
        color: inherit;
      }

      .toast-close {
        cursor: pointer;
        font-size: 20px;
        font-weight: bold;
        margin-left: 12px;
        opacity: 0.7;
        transition: opacity 0.2s;
      }

      .toast-close:hover {
        opacity: 1;
      }

      .toast-info {
        background: #e0f2fe;
        border-left: 4px solid #0284c7;
        color: #0c4a6e;
      }

      .toast-success {
        background: #dcfce7;
        border-left: 4px solid #16a34a;
        color: #15803d;
      }

      .toast-warning {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        color: #92400e;
      }

      .toast-error {
        background: #fee2e2;
        border-left: 4px solid #dc2626;
        color: #7f1d1d;
      }

      @keyframes slideIn {
        from {
          transform: translateX(400px);
          opacity: 0;
        }
        to {
          transform: translateX(0);
          opacity: 1;
        }
      }

      @keyframes slideOut {
        from {
          transform: translateX(0);
          opacity: 1;
        }
        to {
          transform: translateX(400px);
          opacity: 0;
        }
      }

      /* Enhanced Package Cards */
      .featured-package {
        border: 2px solid #1e3c72 !important;
        transform: scale(1.05);
        box-shadow: 0 10px 30px rgba(30, 60, 114, 0.2) !important;
      }

      .featured-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .package-card {
        position: relative;
        background: white;
        border-radius: 16px;
        padding: 30px 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 2px solid #e2e8f0;
        height: 100%;
      }

      .package-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        transform: translateY(-5px);
      }

      .package-header {
        margin-bottom: 20px;
        text-align: center;
      }

      .package-name {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1e3c72;
        margin-bottom: 10px;
      }

      .package-price {
        font-size: 2rem;
        font-weight: 800;
        color: #2a5298;
        margin-bottom: 5px;
      }

      .package-duration {
        font-size: 0.95rem;
        color: #64748b;
        font-weight: 500;
      }

      .package-features {
        margin: 20px 0;
        padding: 20px 0;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
      }

      .package-features h5 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 15px;
      }

      .feature-list {
        list-style: none;
        padding: 0;
        margin: 0;
      }

      .feature-list li {
        font-size: 0.9rem;
        color: #475569;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
      }

      .feature-list i {
        color: #16a34a;
        margin-right: 10px;
        font-size: 0.8rem;
      }

      .book-package-btn {
        width: 100%;
        padding: 12px 20px;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        cursor: pointer;
        margin-top: 15px;
      }

      .book-package-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(30, 60, 114, 0.3);
        color: white;
      }

      .charges-card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border: 2px solid #e2e8f0;
      }

      .charge-item {
        padding: 15px 0;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .charge-item:last-child {
        border-bottom: none;
      }

      .charge-item p {
        margin: 0;
        color: #1e293b;
        font-weight: 500;
      }

      .charge-item small {
        color: #64748b;
        display: block;
        margin-top: 3px;
      }

      /* Lightbox Styles */
      .lightbox-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .lightbox-container {
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
      }

      .lightbox-image {
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      }

      .lightbox-close {
        position: absolute;
        top: -40px;
        right: 0;
        background: none;
        border: none;
        color: white;
        font-size: 36px;
        cursor: pointer;
        padding: 0;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        z-index: 10000;
      }

      .lightbox-close:hover {
        transform: scale(1.2);
        color: #ff6b6b;
      }

      .lightbox-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid white;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 12px 16px;
        border-radius: 4px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10001;
      }

      .lightbox-nav:hover {
        background: rgba(255, 255, 255, 0.4);
        transform: translateY(-50%) scale(1.1);
      }

      .lightbox-prev {
        left: -60px;
      }

      .lightbox-next {
        right: -60px;
      }

      @media (max-width: 768px) {
        .lightbox-prev {
          left: 10px;
        }

        .lightbox-next {
          right: 10px;
        }

        .lightbox-close {
          top: 10px;
          right: 10px;
        }
      }

      .lightbox-counter {
        position: absolute;
        bottom: -40px;
        left: 50%;
        transform: translateX(-50%);
        color: white;
        font-size: 14px;
        font-weight: 600;
        background: rgba(0, 0, 0, 0.6);
        padding: 8px 16px;
        border-radius: 20px;
        z-index: 10000;
      }

      .lightbox-caption {
        position: absolute;
        bottom: -80px;
        left: 50%;
        transform: translateX(-50%);
        color: rgba(255, 255, 255, 0.8);
        font-size: 14px;
        text-align: center;
        max-width: 90vw;
        padding: 0 16px;
        z-index: 10000;
      }
    </style>
  </head>
  <body>
    <div class="toast-container" id="toastContainer"></div>

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
                <button class="nav-link dashboard-btn btn btn-success px-3 py-2" onclick="window.location.href='<?= site_url('/dashboard') ?>'">
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
            <?= esc($facility['description'] ?? 'Book this world-class facility equipped with professional systems and premium amenities for your events and activities.') ?>
          </p>
        </div>
      </div>
    </section>

    <!-- Facility Details Section -->
    <section class="facility-details-section">
      <div class="container">
        <!-- Facility Overview -->
        <div class="facility-hero">
          <div class="facility-icon-large" style="font-size: 5rem;">
            <?= $facility['icon'] ?? '🏢' ?>
          </div>
          <h2><?= esc($facility['name']) ?> - Premium Event Venue</h2>
          <?php if (!empty($facility['capacity'])): ?>
          <div style="display: inline-block; background: linear-gradient(45deg, #1e3c72, #2a5298); color: white; padding: 8px 16px; border-radius: 8px; font-weight: 600; margin: 15px 0; font-size: 1rem;">
            <i class="fas fa-users"></i> Capacity: <strong><?= $facility['capacity'] ?> persons</strong>
          </div>
          <?php endif; ?>
          <p>
            <?= esc($facility['description'] ?? 'Our facility offers a state-of-the-art environment perfect for various events, meetings, and activities with professional-grade equipment and flexible setup options.') ?>
          </p>
        </div>

        <!-- Gallery Section - Enhanced -->
        <div class="gallery-section" style="margin-top: 60px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); padding: 80px 0; border-radius: 32px; position: relative; overflow: hidden;">
          <!-- Decorative background elements -->
          <div style="position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; background: rgba(30, 60, 114, 0.08); border-radius: 50%; z-index: 0;"></div>
          <div style="position: absolute; bottom: -50px; left: -50px; width: 200px; height: 200px; background: rgba(42, 82, 152, 0.08); border-radius: 50%; z-index: 0;"></div>
          
          <div style="max-width: 1200px; margin: 0 auto; padding: 0 40px; position: relative; z-index: 1;">
            <div style="text-align: center; margin-bottom: 50px;">
              <div style="display: inline-block; padding: 10px 20px; background: rgba(30, 60, 114, 0.1); border-radius: 20px; margin-bottom: 20px;">
                <span style="color: #1e3c72; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">Gallery Showcase</span>
              </div>
              <h3 style="color: #1e293b; font-size: 2.8rem; font-weight: 800; margin-bottom: 20px; background: linear-gradient(135deg, #1e3c72, #2a5298); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                <i class="fas fa-images me-2" style="color: #1e3c72; -webkit-text-fill-color: #1e3c72;"></i>
                Explore Our Facility
              </h3>
              <p style="color: #64748b; font-size: 1.15rem; max-width: 650px; margin: 0 auto; line-height: 1.6; font-weight: 500;">
                Take a visual tour of our world-class venue and discover the perfect setting for your next unforgettable event
              </p>
            </div>
            <div class="gallery-grid-container" id="facilityGallery">
              <!-- Gallery images will be loaded dynamically -->
              <div style="grid-column: 1/-1; text-align: center; padding: 5rem 2rem; color: #94a3b8; background: linear-gradient(135deg, rgba(30, 60, 114, 0.08), rgba(42, 82, 152, 0.08)); border-radius: 20px; border: 2px dashed rgba(30, 60, 114, 0.2);">
                <i class="fas fa-image" style="font-size: 4rem; margin-bottom: 1.5rem; display: block; opacity: 0.5; color: #1e3c72;"></i>
                <p style="font-size: 1.2rem; margin: 0; font-weight: 500;">Loading gallery images...</p>
                <p style="font-size: 0.95rem; margin: 10px 0 0 0; opacity: 0.7;">Preparing your visual tour</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Packages Section -->
        <div class="packages-section">
          <h3>Rental Packages</h3>
          <div class="row g-4" id="packagesContainer">
            <!-- Packages dynamically populated -->
          </div>
        </div>

        <!-- Additional Charges Section -->
        <div class="charges-section">
          <div class="container">
            <h3 class="text-center mb-5" style="color: #1e293b;">Additional Services & Charges</h3>
            <div class="row justify-content-center">
              <div class="col-lg-8">
                <div class="charges-card" id="chargesContainer">
                  <!-- Charges dynamically populated -->
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Facility Features & Amenities Section -->
        <div class="features-amenities-section" style="margin-top: 50px;">
          <h3 class="text-center mb-5" style="color: #1e293b;">
            <i class="fas fa-star me-2" style="color: #fbbf24;"></i>
            Premium Features & Amenities
          </h3>
          <div class="row g-4">
            <div class="col-md-6 col-lg-4">
              <div class="feature-card" style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); border-left: 4px solid #1e3c72; height: 100%;">
                <div style="font-size: 2.5rem; color: #1e3c72; margin-bottom: 15px;">
                  <i class="fas fa-wifi"></i>
                </div>
                <h5 style="color: #1e293b; font-weight: 700; margin-bottom: 10px;">High-Speed WiFi</h5>
                <p style="color: #64748b; margin: 0; font-size: 0.95rem;">
                  Reliable high-speed internet connectivity throughout the facility for seamless presentations and connectivity.
                </p>
              </div>
            </div>
            <div class="col-md-6 col-lg-4">
              <div class="feature-card" style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); border-left: 4px solid #2a5298; height: 100%;">
                <div style="font-size: 2.5rem; color: #2a5298; margin-bottom: 15px;">
                  <i class="fas fa-video"></i>
                </div>
                <h5 style="color: #1e293b; font-weight: 700; margin-bottom: 10px;">Audio-Visual Equipment</h5>
                <p style="color: #64748b; margin: 0; font-size: 0.95rem;">
                  Professional-grade projectors, screens, and sound systems ready for your events and presentations.
                </p>
              </div>
            </div>
            <div class="col-md-6 col-lg-4">
              <div class="feature-card" style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); border-left: 4px solid #16a34a; height: 100%;">
                <div style="font-size: 2.5rem; color: #16a34a; margin-bottom: 15px;">
                  <i class="fas fa-wheelchair"></i>
                </div>
                <h5 style="color: #1e293b; font-weight: 700; margin-bottom: 10px;">Accessibility</h5>
                <p style="color: #64748b; margin: 0; font-size: 0.95rem;">
                  Fully accessible facilities with wheelchair access, restrooms, and accommodations for all guests.
                </p>
              </div>
            </div>
            <div class="col-md-6 col-lg-4">
              <div class="feature-card" style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); border-left: 4px solid #f59e0b; height: 100%;">
                <div style="font-size: 2.5rem; color: #f59e0b; margin-bottom: 15px;">
                  <i class="fas fa-snowflake"></i>
                </div>
                <h5 style="color: #1e293b; font-weight: 700; margin-bottom: 10px;">Climate Control</h5>
                <p style="color: #64748b; margin: 0; font-size: 0.95rem;">
                  Modern air conditioning and heating systems to maintain comfortable temperature year-round.
                </p>
              </div>
            </div>
            <div class="col-md-6 col-lg-4">
              <div class="feature-card" style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); border-left: 4px solid #06b6d4; height: 100%;">
                <div style="font-size: 2.5rem; color: #06b6d4; margin-bottom: 15px;">
                  <i class="fas fa-utensils"></i>
                </div>
                <h5 style="color: #1e293b; font-weight: 700; margin-bottom: 10px;">Catering Services</h5>
                <p style="color: #64748b; margin: 0; font-size: 0.95rem;">
                  Partner with our approved catering providers for refreshments and meal services during your event.
                </p>
              </div>
            </div>
            <div class="col-md-6 col-lg-4">
              <div class="feature-card" style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); border-left: 4px solid #ec4899; height: 100%;">
                <div style="font-size: 2.5rem; color: #ec4899; margin-bottom: 15px;">
                  <i class="fas fa-camera"></i>
                </div>
                <h5 style="color: #1e293b; font-weight: 700; margin-bottom: 10px;">Media Support</h5>
                <p style="color: #64748b; margin: 0; font-size: 0.95rem;">
                  Space and support for photography, videography, and live streaming of your events.
                </p>
              </div>
            </div>
          </div>

        <!-- Quick Contact Section -->
        <div class="quick-contact-section" style="margin-top: 50px; background: linear-gradient(135deg, #1e3c72, #2a5298); border-radius: 20px; padding: 40px; color: white; text-align: center;">
          <h3 class="mb-3" style="color: white;">
            <i class="fas fa-phone me-2"></i>
            Need Help? Get in Touch
          </h3>
          <p style="color: rgba(255, 255, 255, 0.9); font-size: 1.05rem; margin-bottom: 30px;">
            Have questions about this facility? Our booking specialists are ready to assist you with any inquiries.
          </p>
          <div class="row g-3 justify-content-center">
            <div class="col-md-3">
              <a href="tel:+63123456789" style="background: rgba(255, 255, 255, 0.2); border: 2px solid rgba(255, 255, 255, 0.5); border-radius: 12px; padding: 15px 20px; color: white; text-decoration: none; display: inline-block; transition: all 0.3s ease; font-weight: 600;">
                <i class="fas fa-phone me-2"></i>
                Call Us
              </a>
            </div>
            <div class="col-md-3">
              <a href="mailto:bookings@cspc.edu.ph" style="background: rgba(255, 255, 255, 0.2); border: 2px solid rgba(255, 255, 255, 0.5); border-radius: 12px; padding: 15px 20px; color: white; text-decoration: none; display: inline-block; transition: all 0.3s ease; font-weight: 600;">
                <i class="fas fa-envelope me-2"></i>
                Email Us
              </a>
            </div>
            <div class="col-md-3">
              <a href="<?= site_url('/contact') ?>" style="background: rgba(255, 255, 255, 0.2); border: 2px solid rgba(255, 255, 255, 0.5); border-radius: 12px; padding: 15px 20px; color: white; text-decoration: none; display: inline-block; transition: all 0.3s ease; font-weight: 600;">
                <i class="fas fa-message me-2"></i>
                Contact Form
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Image Lightbox Modal -->
    <div id="imageLightbox" class="lightbox-modal" style="display: none;">
      <div class="lightbox-container">
        <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
        <button class="lightbox-nav lightbox-prev" onclick="previousImage()">
          <i class="fas fa-chevron-left"></i>
        </button>
        <img id="lightboxImage" src="" alt="Enlarged view" class="lightbox-image">
        <button class="lightbox-nav lightbox-next" onclick="nextImage()">
          <i class="fas fa-chevron-right"></i>
        </button>
        <div class="lightbox-counter" id="lightboxCounter">1 / 1</div>
        <div class="lightbox-caption" id="lightboxCaption"></div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <div class="row">
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="footer-section">
              <div class="footer-logo">
                <div class="cspc-logo-nav">
                  <i class="fas fa-graduation-cap"></i>
                </div>
                CSPC Sphere
              </div>
              <p style="color: #94a3b8; line-height: 1.6;">
                Your premier destination for world-class facilities and exceptional event experiences. 
                Book with confidence at Camarines Sur Polytechnic Colleges.
              </p>
              <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
              </div>
            </div>
          </div>
          <div class="col-lg-2 col-md-6 mb-4">
            <div class="footer-section">
              <h5>Quick Links</h5>
              <a href="<?= site_url('/') ?>">Home</a>
              <a href="<?= site_url('/facilities') ?>">Facilities</a>
              <a href="<?= site_url('/event') ?>">Events</a>
              <a href="<?= site_url('/contact') ?>">Contact</a>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="footer-section">
              <h5>Other Facilities</h5>
              <a href="<?= site_url('/facilities') ?>">View All</a>
              <a href="<?= site_url('/contact') ?>">Request Custom Booking</a>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="footer-section">
              <h5>Contact Info</h5>
              <a href="tel:+63123456789">
                <i class="fas fa-phone" style="margin-right: 8px;"></i>
                +63 123 456 7890
              </a>
              <a href="mailto:info@cspc.edu.ph">
                <i class="fas fa-envelope" style="margin-right: 8px;"></i>
                info@cspc.edu.ph
              </a>
              <a href="#">
                <i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i>
                Nabua, Camarines Sur
              </a>
            </div>
          </div>
        </div>
        <div class="footer-bottom">
          <p>&copy; 2024 CSPC Sphere. All rights reserved.</p>
        </div>
      </div>
    </footer>

    <!-- Login Required Modal -->
    <div class="modal fade" id="loginRequiredModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none;">
          <div class="modal-header" style="background: linear-gradient(45deg, #1e3c72, #2a5298); color: white; border-radius: 20px 20px 0 0;">
            <h5 class="modal-title">
              <i class="fas fa-sign-in-alt me-2"></i>
              Login Required
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" style="padding: 40px;">
            <div class="alert alert-info" style="border-radius: 12px; border: none; background: rgba(30, 60, 114, 0.1);">
              <i class="fas fa-info-circle me-2"></i>
              Please log in to complete your booking.
            </div>
            <div class="selected-package mb-4">
              <h6 style="color: #1e3c72; font-weight: 700;">Selected Package:</h6>
              <p id="loginRequiredPackage" style="font-size: 1.1rem; color: #1e293b; font-weight: 600;"></p>
            </div>
          </div>
          <div class="modal-footer" style="padding: 20px 40px; border: none;">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="redirectToLogin()" style="background: linear-gradient(45deg, #1e3c72, #2a5298); border: none;">
              <i class="fas fa-sign-in-alt me-2"></i>
              Proceed to Login
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="bookingModalLabel">
              <i class="fas fa-calendar-check me-2"></i>
              Book <?= esc($facility['name']) ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="selected-package mb-4 p-3" style="background: rgba(30, 60, 114, 0.05); border-radius: 12px;">
              <h6 style="color: #1e3c72; font-weight: 700;">Selected Package:</h6>
              <p id="selectedPackage" style="font-size: 1.1rem; color: #1e293b; font-weight: 600; margin: 0;"></p>
            </div>

            <form id="bookingForm">

              <hr class="my-4">

              <!-- Booking Information -->
              <h6 class="mb-3" style="color: #1e3c72; font-weight: 700;">
                <i class="fas fa-info-circle me-2"></i>
                Booking Details
              </h6>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="organization" class="form-label" style="color: #1e293b; font-weight: 600;">
                    <i class="fas fa-building me-1"></i>
                    Organization/Company
                  </label>
                  <input type="text" class="form-control" id="organization">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="attendees" class="form-label" style="color: #1e293b; font-weight: 600;">
                    <i class="fas fa-users me-1"></i>
                    Expected Attendees
                  </label>
                  <input type="number" class="form-control" id="attendees" min="1">
                </div>
              </div>

              <div class="mb-3">
                <label for="address" class="form-label" style="color: #1e293b; font-weight: 600;">
                  <i class="fas fa-map-marker-alt me-1"></i>
                  Complete Address *
                </label>
                <textarea class="form-control" id="address" rows="2" placeholder="Street, Barangay, City, Province" required></textarea>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="eventDate" class="form-label" style="color: #1e293b; font-weight: 600;">
                    <i class="fas fa-calendar me-1"></i>
                    Event Date * <span class="text-danger">Required first</span>
                  </label>
                  <input type="date" class="form-control" id="eventDate" required onchange="handleDateSelection()">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="eventTime" class="form-label" style="color: #1e293b; font-weight: 600;">
                    <i class="fas fa-clock me-1"></i>
                    Start Time *
                  </label>
                  <input type="time" class="form-control" id="eventTime" required>
                </div>
              </div>

              <hr class="my-4">

              <!-- Additional Services -->
              <div class="mb-4">
                <label class="form-label" style="color: #1e293b; font-weight: 600;">
                  <i class="fas fa-plus-circle me-1"></i>
                  Additional Services
                </label>
                <div class="row" id="addonsContainer">
                  <!-- Dynamically populated -->
                </div>
              </div>

              <!-- Equipment (Hidden until date is selected) -->
              <div class="mb-4" id="equipmentSection" style="display: none;">
                <label class="form-label" style="color: #1e293b; font-weight: 600;">
                  <i class="fas fa-tools me-1"></i>
                  Equipment
                </label>
                <div class="row" id="equipmentContainer">
                  <!-- Dynamically populated -->
                </div>
              </div>

              <!-- Equipment Date Placeholder (shown until date is selected) -->
              <div class="mb-4" id="equipmentPlaceholder">
                <div class="alert alert-info" style="border-radius: 12px; border: none; background: rgba(59, 130, 246, 0.1); padding: 15px;">
                  <i class="fas fa-calendar-check me-2" style="color: #3b82f6;"></i>
                  <span style="color: #1e40af;"><strong>Please select an event date first</strong> to view available equipment and quantities for that date.</span>
                </div>
              </div>

              <!-- Additional Hours -->
              <div class="mb-4">
                <label for="additionalHours" class="form-label" style="color: #1e293b; font-weight: 600;">
                  <i class="fas fa-clock me-1"></i>
                  Additional Hours (<span id="additionalHoursRateLabel">₱0</span>/hour)
                </label>
                <input type="number" class="form-control" id="additionalHours" min="0" max="12" value="0" onchange="updateCostSummary()">
                <small class="text-muted" style="color: #64748b !important;">Add extra hours beyond your selected plan duration</small>
              </div>

              <div class="mb-3">
                <label for="eventTitle" class="form-label" style="color: #1e293b; font-weight: 600;">
                  <i class="fas fa-heading me-1"></i>
                  Event Title/Purpose *
                </label>
                <input type="text" class="form-control" id="eventTitle" required>
              </div>

              <div class="mb-3">
                <label for="specialRequirements" class="form-label" style="color: #1e293b; font-weight: 600;">
                  <i class="fas fa-comment me-1"></i>
                  Special Requirements/Notes
                </label>
                <textarea class="form-control" id="specialRequirements" rows="3" placeholder="Please specify any special requirements..."></textarea>
              </div>

              <!-- Booking Summary -->
              <div class="booking-summary" style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px;">
                <h6 class="mb-3" style="color: #1e3c72; font-weight: 700;">
                  <i class="fas fa-receipt me-2"></i>
                  Booking Summary
                </h6>
                <div class="summary-item" style="display: flex; justify-content: space-between; padding: 8px 0; color: #1e293b; font-weight: 500;">
                  <span>Selected Package:</span>
                  <span id="summaryPackage" style="color: #1e293b; font-weight: 600;">-</span>
                </div>
                <div class="summary-item" style="display: flex; justify-content: space-between; padding: 8px 0; color: #1e293b; font-weight: 500;">
                  <span>Base Price:</span>
                  <span id="summaryBasePrice" style="color: #1e293b; font-weight: 600;">₱0</span>
                </div>
                <div class="summary-item" style="display: flex; justify-content: space-between; padding: 8px 0; color: #1e293b; font-weight: 500;">
                  <span>Additional Services:</span>
                  <span id="summaryAddons" style="color: #1e293b; font-weight: 600;">₱0</span>
                </div>
                <div class="summary-item" style="display: flex; justify-content: space-between; padding: 8px 0; color: #1e293b; font-weight: 500;">
                  <span>Equipment:</span>
                  <span id="summaryEquipment" style="color: #1e293b; font-weight: 600;">₱0</span>
                </div>
                <div class="summary-item" style="display: flex; justify-content: space-between; padding: 8px 0; color: #1e293b; font-weight: 500;">
                  <span>Additional Hours:</span>
                  <span id="summaryAdditionalHours" style="color: #1e293b; font-weight: 600;">₱0</span>
                </div>
                <div class="summary-item" style="display: flex; justify-content: space-between; padding: 8px 0; color: #1e293b; font-weight: 500;">
                  <span>Maintenance Fee:</span>
                  <span id="summaryMaintenance" style="color: #1e293b; font-weight: 600;">₱2,000</span>
                </div>
                <hr style="border-color: #cbd5e1; margin: 12px 0;">
                <div class="summary-item" style="display: flex; justify-content: space-between; padding: 8px 0; color: #1e293b; font-weight: 700; font-size: 1.2rem;">
                  <span>Total Amount:</span>
                  <span id="summaryTotal" style="color: #1e3c72; font-weight: 700;">₱2,000</span>
                </div>
              </div>

              <div class="alert alert-warning mt-3" role="alert" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #f59e0b; border-left: 4px solid #f59e0b; color: #78350f; border-radius: 8px;">
                <h6 style="color: #92400e; font-weight: 700; margin-bottom: 10px;">
                  <i class="fas fa-exclamation-triangle me-2" style="color: #f59e0b;"></i>
                  Important Notice
                </h6>
                <p style="margin-bottom: 10px; font-size: 14px;">
                  <i class="fas fa-building me-2" style="color: #f59e0b;"></i>
                  After submitting this booking, you must visit the office within <strong style="color: #92400e;">7 days</strong> to:
                </p>
                <ul style="margin-left: 25px; margin-bottom: 10px; font-size: 14px;">
                  <li>Sign the booking agreement</li>
                  <li>Pay the required amount</li>
                </ul>
                <p style="margin-bottom: 0; font-weight: 600; color: #dc2626; font-size: 14px;">
                  <i class="fas fa-times-circle me-2"></i>
                  Failure to comply will result in automatic cancellation of your booking.
                </p>
              </div>

            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fas fa-times me-1"></i>
              Cancel
            </button>
            <button type="button" class="btn btn-primary" onclick="submitBooking()">
              <i class="fas fa-check me-1"></i>
              Submit Booking Request
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header" style="background: linear-gradient(45deg, #22c55e, #16a34a); color: white;">
            <h5 class="modal-title" id="successModalLabel">
              <i class="fas fa-check-circle me-2"></i>
              Booking Request Submitted Successfully!
            </h5>
          </div>
          <div class="modal-body text-center">
            <div style="font-size: 4rem; color: #22c55e; margin-bottom: 20px;">
              <i class="fas fa-check-circle"></i>
            </div>
            <h4 style="color: #1e293b; margin-bottom: 15px;">Thank You!</h4>
            <p style="color: #64748b; margin-bottom: 20px;">
              Your booking request has been submitted successfully. Our team will review your request and contact you within 24 hours to confirm availability and payment details.
            </p>
            <div style="background: rgba(34, 197, 94, 0.1); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
              <p style="margin: 0; color: #1e293b; font-weight: 600;">
                <i class="fas fa-envelope me-2" style="color: #22c55e;"></i>
                A confirmation email has been sent to your registered email address.
              </p>
            </div>
            <p style="color: #64748b; font-size: 0.9rem;">
              Reference Number: <strong id="referenceNumber" style="color: #1e3c72;"></strong>
            </p>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-primary" onclick="closeSuccessModal()">
              <i class="fas fa-home me-1"></i>
              Continue Browsing
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
// Global variables
let facilityData = {};
let addonsData = [];
let equipmentData = [];
let selectedPlan = null;
let selectedAddons = [];
let selectedEquipment = {};
let currentFacility = '<?= $facilityKey ?>';
const MAINTENANCE_FEE = 2000;
let HOURLY_RATE = 1000;

// Lightbox variables
let galleryImages = [];
let currentImageIndex = 0;

// Check if user is logged in
const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
const userRole = '<?php echo $userRole ?? ''; ?>';
const canBook = isLoggedIn && (userRole === 'user' || userRole === 'employee');
const userEmail = '<?php echo $userEmail ?? ''; ?>';
const userContact = '<?php echo $userContact ?? ''; ?>'

// Toast notification system
function showToast(message, type = 'info') {
  const toastContainer = document.getElementById('toastContainer') || createToastContainer();
  
  const toastId = 'toast-' + Date.now();
  const toastHTML = `
    <div id="${toastId}" class="toast-notification toast-${type}">
      <div class="toast-content">
        <span class="toast-icon">
          ${type === 'error' ? '❌' : type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️'}
        </span>
        <span class="toast-message">${message}</span>
      </div>
      <div class="toast-close" onclick="closeToast('${toastId}')">×</div>
    </div>
  `;
  
  toastContainer.insertAdjacentHTML('beforeend', toastHTML);
  
  // Auto remove after 5 seconds
  setTimeout(() => closeToast(toastId), 5000);
}

function createToastContainer() {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 10px;
      max-width: 400px;
    `;
    document.body.appendChild(container);
  }
  return container;
}

function closeToast(toastId) {
  const toast = document.getElementById(toastId);
  if (toast) {
    toast.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => toast.remove(), 300);
  }
}

// Load data on page load
document.addEventListener("DOMContentLoaded", function () {
  loadFacilityData();
  loadAddonsData();
  loadEquipmentData();
  loadFacilityGallery();

  // Set minimum date to today
  const today = new Date().toISOString().split("T")[0];
  const eventDateInput = document.getElementById("eventDate");
  if (eventDateInput) {
    eventDateInput.min = today;
  }
});

// Load facility data from database
async function loadFacilityData() {
  try {
    const response = await fetch("<?= base_url('api/facilities/data/' . $facilityKey) ?>");
    const data = await response.json();
    facilityData = data.facility || data;
    if (facilityData.extended_hour_rate) {
      HOURLY_RATE = parseFloat(facilityData.extended_hour_rate);
    }
    console.log("Facility data loaded:", facilityData);
    renderPackages();
  } catch (error) {
    console.error("Error loading facility data:", error);
  }
}

// Load addons data from database
async function loadAddonsData() {
  try {
    const response = await fetch("<?= base_url('api/addons') ?>");
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    
    // Handle both array and object responses
    const addonsArray = Array.isArray(data) ? data : (data.addons || data.data || []);
    
    addonsData = addonsArray
      .filter((addon) => addon.addon_key !== "additional-hours")
      .map((addon) => ({
        id: addon.addon_key,
        name: addon.name,
        description: addon.description,
        price: parseFloat(addon.price),
      }));
    console.log("Addons data loaded:", addonsData);
    renderCharges();
  } catch (error) {
    console.error("Error loading addons data:", error);
    addonsData = [];
  }
}

// Load equipment data from database
async function loadEquipmentData() {
  try {
    const response = await fetch("<?= base_url('api/bookings/equipment') ?>");
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const result = await response.json();

    // Handle both wrapped response and direct array
    let equipmentArray = [];
    if (Array.isArray(result)) {
      equipmentArray = result;
    } else if (result && result.equipment && Array.isArray(result.equipment)) {
      if (!result.success) {
        throw new Error(result.message || "Failed to load equipment");
      }
      equipmentArray = result.equipment;
    } else {
      throw new Error("Invalid equipment response format");
    }

    equipmentData = equipmentArray
      .filter((equipment) => {
        const isRentable = equipment.is_rentable == 1 || equipment.is_rentable === true || equipment.is_rentable === "1";
        const hasRate = parseFloat(equipment.rate || 0) > 0;
        const isFurnitureOrLogistics = equipment.category === "furniture" || equipment.category === "logistics";
        return isFurnitureOrLogistics && isRentable && hasRate;
      })
      .map((equipment) => ({
        id: equipment.id.toString(),
        name: equipment.name,
        rate: parseFloat(equipment.rate || 0),
        unit: equipment.unit || "piece",
        available: parseInt(equipment.available || 0),
        category: equipment.category,
      }));

    console.log("Rentable equipment loaded:", equipmentData);
  } catch (error) {
    console.error("Error loading equipment data:", error);
    equipmentData = [];
  }
}

// Check equipment availability for a specific date - loads fresh data
async function checkEquipmentAvailabilityOnDate(eventDate) {
  try {
    const response = await fetch("<?= base_url('api/bookings/equipment-availability') ?>", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        event_date: eventDate,
        facility_id: facilityData.id || facilityData.facility_id
      })
    });

    const result = await response.json();
    
    if (result.success && result.equipment) {
      // Load fresh equipment data for the selected date
      equipmentData = result.equipment
        .filter((equipment) => {
          const isRentable = equipment.is_rentable == 1 || equipment.is_rentable === true || equipment.is_rentable === "1";
          const hasRate = parseFloat(equipment.rate || 0) > 0;
          const isFurnitureOrLogistics = equipment.category === "furniture" || equipment.category === "logistics";
          return isFurnitureOrLogistics && isRentable && hasRate;
        })
        .map((equipment) => ({
          id: equipment.id.toString(),
          name: equipment.name,
          rate: parseFloat(equipment.rate || 0),
          unit: equipment.unit || "piece",
          available: parseInt(equipment.available_on_date || 0),
          booked_quantity: parseInt(equipment.booked_quantity || 0),
          category: equipment.category,
        }));
      
      console.log("Fresh equipment availability on " + eventDate + ":", equipmentData);
      populateEquipment();
      updateCostSummary();
    } else {
      console.warn("Could not get equipment availability for date:", eventDate);
      populateEquipment();
    }
  } catch (error) {
    console.error("Error checking equipment availability:", error);
    populateEquipment();
  }
}

function renderPackages() {
  const packagesContainer = document.getElementById('packagesContainer');
  if (!packagesContainer || !facilityData.plans) return;
  
  packagesContainer.innerHTML = '';
  
  let html = '';
  facilityData.plans.forEach((plan, index) => {
    const features = plan.features || [];
    const includedEquipment = plan.included_equipment || [];
    const isMostPopular = index === 0;
    
    let featuresList = features.map(feature => `<li><i class="fas fa-check"></i> ${feature}</li>`).join('');
    let equipmentList = includedEquipment.map(eq => 
      `<li><i class="fas fa-check"></i> ${eq.quantity_included} ${eq.unit} - ${eq.name}</li>`
    ).join('');
    
    const packageCol = `
      <div class="col-lg-4 col-md-6">
        <div class="package-card ${isMostPopular ? 'featured-package' : ''}">
          ${isMostPopular ? '<div class="featured-badge">Most Popular</div>' : ''}
          <div class="package-header">
            <div class="package-name">${plan.name}</div>
            <div class="package-price">₱${parseFloat(plan.price).toLocaleString()}</div>
            <div class="package-duration">${plan.duration}</div>
          </div>
          <div class="package-features">
            <h5>Includes:</h5>
            <ul class="feature-list">
              ${featuresList}
              ${equipmentList}
            </ul>
          </div>
          <button class="book-package-btn" onclick="openBookingModal('${plan.name}')">
            <i class="fas fa-calendar-check"></i> Book Now
          </button>
        </div>
      </div>
    `;
    
    html += packageCol;
  });
  
  packagesContainer.innerHTML = html;
}

function renderCharges() {
  const chargesContainer = document.getElementById('chargesContainer');
  if (!chargesContainer || !addonsData) return;
  
  chargesContainer.innerHTML = '';
  
  addonsData.forEach((addon) => {
    const addonPrice = parseFloat(addon.price || 0);
    
    const chargeItem = document.createElement('div');
    chargeItem.className = 'row charge-item';
    chargeItem.innerHTML = `
      <div class="col-md-8">
        <p style="margin: 0; color: #333; font-weight: 500;">${addon.name}</p>
        ${addon.description ? `<small style="color: #999;">${addon.description}</small>` : ''}
      </div>
      <div class="col-md-4 text-end">
        <p style="margin: 0; font-weight: 600;">₱${addonPrice.toLocaleString()}</p>
      </div>
    `;
    
    chargesContainer.appendChild(chargeItem);
  });
  
  const additionalHoursRate = facilityData.additional_hours_rate || 500;
  const additionalHoursItem = document.createElement('div');
  additionalHoursItem.className = 'row charge-item';
  additionalHoursItem.innerHTML = `
    <div class="col-md-8">
      <p style="margin: 0; color: #333; font-weight: 500;">Additional Hours</p>
      <small style="color: #999;">Per hour extension beyond package duration</small>
    </div>
    <div class="col-md-4 text-end">
      <p style="margin: 0; font-weight: 600;">₱${additionalHoursRate.toLocaleString()}/hr</p>
    </div>
  `;
  
  chargesContainer.appendChild(additionalHoursItem);
}

async function openBookingModal(packageName) {
  console.log('Opening booking modal for:', packageName);
  
  if (!isLoggedIn) {
    showLoginRequiredModal(packageName);
    return;
  }
  
  if (!canBook) {
    showToast('Only regular users and employees can make bookings. Admin accounts cannot create bookings.', 'warning');
    return;
  }

  // Load fresh facility data when opening modal
  try {
    const response = await fetch("<?= base_url('api/facilities/data/' . $facilityKey) ?>");
    const data = await response.json();
    facilityData = data.facility || data;
    console.log("Fresh facility data loaded:", facilityData);
  } catch (error) {
    console.error("Error loading facility data:", error);
    showToast('Error loading facility data. Please try again.', 'error');
    return;
  }

  selectedPlan = facilityData.plans ? facilityData.plans.find(p => p.name === packageName) : null;
  
  if (!selectedPlan) {
    alert('Unable to find plan details. Please try again.');
    console.error('Plan not found for:', packageName, 'Available plans:', facilityData.plans);
    return;
  }

  document.getElementById('selectedPackage').textContent = packageName;
  document.getElementById('additionalHours').value = 0;
  document.getElementById('bookingForm').reset();
  
  const additionalHoursRate = facilityData.additional_hours_rate || 500;
  document.getElementById('additionalHoursRateLabel').textContent = `₱${additionalHoursRate.toLocaleString()}`;
  
  selectedAddons = [];
  selectedEquipment = {};
  
  document.getElementById('equipmentSection').style.display = 'none';
  document.getElementById('equipmentPlaceholder').style.display = 'block';
  document.getElementById('equipmentContainer').innerHTML = '';
  
  // Dynamically populate addons in modal
  await populateAddonsInModal();
  updateCostSummary();
  
  const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
  modal.show();
}

function showLoginRequiredModal(packageName) {
  document.getElementById('loginRequiredPackage').textContent = packageName;
  const modal = new bootstrap.Modal(document.getElementById('loginRequiredModal'));
  modal.show();
}

function redirectToLogin() {
  window.location.href = '<?= site_url('/login') ?>';
}

async function handleDateSelection() {
  const eventDate = document.getElementById('eventDate').value;
  const equipmentSection = document.getElementById('equipmentSection');
  const equipmentPlaceholder = document.getElementById('equipmentPlaceholder');
  
  if (eventDate) {
    try {
      const conflictCheck = await fetch("<?= base_url('api/bookings/checkDateConflict') ?>", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          facility_id: facilityData.id || facilityData.facility_id,
          event_date: eventDate,
          event_time: document.getElementById('eventTime').value || '08:00',
          duration: selectedPlan?.duration?.match(/\d+/)?.[0] || 8
        })
      });

      const conflictResult = await conflictCheck.json();
      
      if (conflictResult.hasConflict) {
        showToast('⚠️ There is a pending or accepted booking on this date. Please select another date.', 'warning');
        equipmentSection.style.display = 'none';
        equipmentPlaceholder.style.display = 'block';
        document.getElementById('equipmentContainer').innerHTML = '';
        return;
      }
    } catch (error) {
      console.error('Error checking date conflict:', error);
    }

    // Load fresh equipment data for the selected date
    equipmentSection.style.display = 'block';
    equipmentPlaceholder.style.display = 'none';
    selectedEquipment = {};
    
    // Show loading state
    document.getElementById('equipmentContainer').innerHTML = '<div class="col-12"><p style="color: #64748b; text-align: center;">Loading equipment availability...</p></div>';
    
    // Fetch fresh equipment data for selected date
    await checkEquipmentAvailabilityOnDate(eventDate);
    updateCostSummary();
  } else {
    equipmentSection.style.display = 'none';
    equipmentPlaceholder.style.display = 'block';
    document.getElementById('equipmentContainer').innerHTML = '';
  }
}

function populateAddons() {
  const addonsGrid = document.getElementById("addonsContainer");
  if (!addonsGrid) return;
  
  addonsGrid.innerHTML = "";

  if (!addonsData || addonsData.length === 0) {
    addonsGrid.innerHTML = '<p class="no-data" style="color: #64748b;">No additional services available.</p>';
    return;
  }

  addonsData.forEach((addon) => {
    const addonCard = document.createElement("div");
    addonCard.className = "col-md-6";

    addonCard.innerHTML = `
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" id="addon-${addon.id}" 
               onchange="toggleAddon('${addon.id}')">
        <label class="form-check-label" for="addon-${addon.id}" style="color: #000;">
          ${addon.name} - ₱${addon.price.toLocaleString()}
        </label>
      </div>
    `;

    addonsGrid.appendChild(addonCard);
  });
}

// Populate addons dynamically when modal opens
async function populateAddonsInModal() {
  try {
    const response = await fetch("<?= base_url('api/addons') ?>");
    const data = await response.json();
    addonsData = data
      .filter((addon) => addon.addon_key !== "additional-hours")
      .map((addon) => ({
        id: addon.addon_key,
        name: addon.name,
        description: addon.description,
        price: parseFloat(addon.price),
      }));
    
    console.log("Fresh addons data loaded for modal:", addonsData);
    populateAddons();
  } catch (error) {
    console.error("Error loading addons data in modal:", error);
    showToast('Error loading additional services', 'error');
  }
}

function populateEquipment() {
  const equipmentGrid = document.getElementById("equipmentContainer");
  if (!equipmentGrid) return;
  
  equipmentGrid.innerHTML = "";

  if (!equipmentData || equipmentData.length === 0) {
    equipmentGrid.innerHTML = `
      <div class="col-12">
        <div class="alert alert-info" style="border-radius: 12px; border: none; background: rgba(59, 130, 246, 0.1);">
          <i class="fas fa-info-circle me-2" style="color: #3b82f6;"></i>
          <span style="color: #1e40af;">No additional rental equipment available at this time.</span>
        </div>
      </div>
    `;
    return;
  }

  equipmentData.forEach((equipment) => {
    const equipmentCard = document.createElement("div");
    equipmentCard.className = "col-md-6";

    const isAvailable = equipment.available > 0;
    const stockInfo = isAvailable
      ? `Available: ${equipment.available}`
      : "Out of Stock";

    equipmentCard.innerHTML = `
      <div class="equipment-item mb-3">
        <label class="form-label" style="color: #000;">
          ${equipment.name}
          <span class="text-primary" style="font-weight: 600;">(₱${equipment.rate.toLocaleString()} / ${equipment.unit})</span>
        </label>
        ${!isAvailable
          ? `<input type="number" class="form-control" value="0" disabled style="border-radius: 8px; border: 2px solid #e2e8f0;">
             <small class="text-danger">${stockInfo}</small>`
          : `<input type="number" class="form-control quantity-input" id="qty-${equipment.id}" 
                   min="0" max="${equipment.available}" value="0" 
                   onchange="updateEquipment('${equipment.id}')"
                   style="border-radius: 8px; border: 2px solid #e2e8f0;">
             <small class="text-muted">${stockInfo}</small>`
        }
      </div>
    `;

    equipmentGrid.appendChild(equipmentCard);
  });
}

function updateEquipment(equipmentId) {
  const quantityInput = document.getElementById(`qty-${equipmentId}`);
  const quantity = parseInt(quantityInput.value) || 0;
  const equipment = equipmentData.find((e) => e.id === equipmentId);

  if (!equipment) return;

  if (quantity > equipment.available) {
    alert(`Only ${equipment.available} units available for ${equipment.name}`);
    quantityInput.value = equipment.available;
    selectedEquipment[equipmentId] = equipment.available;
  } else if (quantity > 0) {
    selectedEquipment[equipmentId] = quantity;
  } else {
    delete selectedEquipment[equipmentId];
  }

  updateCostSummary();
}

function toggleAddon(addonId) {
  const checkbox = document.getElementById(`addon-${addonId}`);

  if (checkbox.checked) {
    selectedAddons.push(addonId);
  } else {
    selectedAddons = selectedAddons.filter((id) => id !== addonId);
  }

  updateCostSummary();
}

function updateCostSummary() {
  const summaryPackage = document.getElementById("summaryPackage");
  const summaryBasePrice = document.getElementById("summaryBasePrice");
  const summaryAddons = document.getElementById("summaryAddons");
  const summaryEquipment = document.getElementById("summaryEquipment");
  const summaryAdditionalHours = document.getElementById("summaryAdditionalHours");
  const summaryMaintenance = document.getElementById("summaryMaintenance");
  const summaryTotal = document.getElementById("summaryTotal");

  let basePrice = 0;
  let packageName = '-';
  let addonsPrice = 0;
  let equipmentPrice = 0;
  let additionalHoursPrice = 0;

  if (selectedPlan) {
    basePrice = selectedPlan.price;
    packageName = selectedPlan.name;
    summaryBasePrice.textContent = `₱${basePrice.toLocaleString()}`;
    summaryPackage.textContent = packageName;
  } else {
    summaryBasePrice.textContent = "₱0";
    summaryPackage.textContent = "-";
  }

  const additionalHours = parseInt(document.getElementById("additionalHours")?.value) || 0;
  if (additionalHours > 0) {
    const additionalHoursRate = facilityData.additional_hours_rate || 500;
    additionalHoursPrice = additionalHours * additionalHoursRate;
  }
  summaryAdditionalHours.textContent = `₱${additionalHoursPrice.toLocaleString()}`;

  selectedAddons.forEach((addonId) => {
    const addon = addonsData.find((a) => a.id === addonId);
    if (addon) {
      addonsPrice += addon.price;
    }
  });
  summaryAddons.textContent = `₱${addonsPrice.toLocaleString()}`;

  Object.keys(selectedEquipment).forEach((equipmentId) => {
    const equipment = equipmentData.find((e) => e.id === equipmentId);
    const quantity = selectedEquipment[equipmentId];
    if (equipment && quantity > 0 && equipment.rate > 0) {
      const itemCost = equipment.rate * quantity;
      equipmentPrice += itemCost;
    }
  });
  summaryEquipment.textContent = `₱${equipmentPrice.toLocaleString()}`;

  summaryMaintenance.textContent = `₱${MAINTENANCE_FEE.toLocaleString()}`;

  const total = basePrice + addonsPrice + equipmentPrice + additionalHoursPrice + MAINTENANCE_FEE;
  summaryTotal.textContent = `₱${total.toLocaleString()}`;
}

function calculateTotalDuration() {
  if (!selectedPlan) return 0;

  const durationMatch = selectedPlan.duration.match(/\d+/);
  const planDuration = durationMatch ? parseInt(durationMatch[0]) : 0;
  const additionalHours = parseInt(document.getElementById("additionalHours")?.value) || 0;

  return planDuration + additionalHours;
}

/**
 * Check for date/time conflicts before booking
 */
async function checkBookingConflict(eventDate, eventTime, totalDuration) {
  try {
    const facilityId = facilityData.id;
    
    // Calculate end time with 2-hour grace period
    const startTime = new Date(`2000-01-01 ${eventTime}`);
    const endTime = new Date(startTime.getTime() + (totalDuration * 60 * 60 * 1000));
    const endTimeWithGrace = new Date(endTime.getTime() + (2 * 60 * 60 * 1000)); // Add 2 hour grace
    
    const endTimeStr = String(endTime.getHours()).padStart(2, '0') + ':' + String(endTime.getMinutes()).padStart(2, '0');
    const endTimeWithGraceStr = String(endTimeWithGrace.getHours()).padStart(2, '0') + ':' + String(endTimeWithGrace.getMinutes()).padStart(2, '0');

    const response = await fetch("<?= base_url('api/bookings/checkDateConflict') ?>", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        facility_id: facilityId,
        event_date: eventDate,
        event_time: eventTime,
        duration: totalDuration
      }),
    });

    const result = await response.json();
    
    if (result.hasConflict) {
      return {
        hasConflict: true,
        message: `Facility has conflicting booking on this date/time. Your requested time: ${eventTime} - ${endTimeStr}. With 2-hour grace period, available from: ${endTimeWithGraceStr}`
      };
    }

    return { hasConflict: false };
  } catch (error) {
    console.error("Error checking conflict:", error);
    return { hasConflict: false }; // Don't block booking on check error
  }
}

async function submitBooking() {
  console.log("Starting booking submission...");

  const form = document.getElementById("bookingForm");
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  if (!selectedPlan) {
    alert("Please select a plan before proceeding.");
    return;
  }

  // Check for date/time conflicts before submitting
  const eventDate = document.getElementById("eventDate").value;
  const eventTime = document.getElementById("eventTime").value;
  const totalDuration = calculateTotalDuration();
  
  const conflictCheck = await checkBookingConflict(eventDate, eventTime, totalDuration);
  if (conflictCheck.hasConflict) {
    alert("⚠️ Conflict Detected\n\n" + conflictCheck.message);
    return;
  }

  const selectedAddonIds = selectedAddons;
  const additionalHours = parseInt(document.getElementById("additionalHours")?.value) || 0;

  const formData = {
    facility_key: currentFacility,
    plan_id: selectedPlan.id,
    organization: document.getElementById("organization").value,
    address: document.getElementById("address").value,
    event_date: eventDate,
    event_time: eventTime,
    duration: totalDuration,
    attendees: document.getElementById("attendees").value || null,
    event_title: document.getElementById("eventTitle").value,
    special_requirements: document.getElementById("specialRequirements").value,
    selected_addons: selectedAddonIds,
    selected_equipment: selectedEquipment,
    additional_hours: additionalHours,
    maintenance_fee: MAINTENANCE_FEE,
    total_cost: calculateTotalCost(),
  };

  console.log("Booking data to submit:", formData);

  try {
    const response = await fetch("<?= base_url('api/bookings') ?>", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(formData),
    });

    console.log("Response status:", response.status);

    if (!response.ok) {
      const errorData = await response.json();
      console.error("Server error:", errorData);
      throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    console.log("Server response:", result);

    if (result.success) {
      document.getElementById('referenceNumber').textContent = 'BK' + String(result.booking_id).padStart(3, '0');
      bootstrap.Modal.getInstance(document.getElementById('bookingModal')).hide();
      new bootstrap.Modal(document.getElementById('successModal')).show();
      
      form.reset();
      selectedPlan = null;
      selectedAddons = [];
      selectedEquipment = {};
      updateCostSummary();
    } else {
      alert(result.message || "Failed to create booking");
    }
  } catch (error) {
    console.error("Error submitting booking:", error);
    alert("Error: " + error.message);
  }
}

function calculateTotalCost() {
  let basePrice = selectedPlan ? selectedPlan.price : 0;

  const additionalHours = parseInt(document.getElementById("additionalHours")?.value) || 0;
  const additionalHoursRate = facilityData.additional_hours_rate || 500;
  const additionalHoursPrice = additionalHours * additionalHoursRate;

  const addonsPrice = selectedAddons.reduce((sum, addonId) => {
    const addon = addonsData.find((a) => a.id === addonId);
    return sum + (addon ? addon.price : 0);
  }, 0);

  const equipmentPrice = Object.keys(selectedEquipment).reduce(
    (sum, equipmentId) => {
      const equipment = equipmentData.find((e) => e.id === equipmentId);
      const quantity = selectedEquipment[equipmentId];
      if (equipment && quantity > 0 && equipment.rate > 0) {
        return sum + equipment.rate * quantity;
      }
      return sum;
    },
    0
  );

  return basePrice + addonsPrice + equipmentPrice + additionalHoursPrice + MAINTENANCE_FEE;
}

function closeSuccessModal() {
  bootstrap.Modal.getInstance(document.getElementById('successModal')).hide();
  window.location.href = '<?= site_url('/dashboard') ?>';
}

// Event listener for additional hours
const additionalHoursInput = document.getElementById('additionalHours');
if (additionalHoursInput) {
  additionalHoursInput.addEventListener('input', updateCostSummary);
}

// Load facility gallery from database
async function loadFacilityGallery() {
  try {
    const response = await fetch("<?= base_url('api/facilities/gallery/' . $facilityKey) ?>");
    const data = await response.json();
    
    const galleryContainer = document.getElementById('facilityGallery');
    
    if (data.success && data.gallery && data.gallery.length > 0) {
      // Store gallery images globally for lightbox navigation
      galleryImages = data.gallery;
      
      // Display uploaded gallery images with click handlers
      // Alternate between 16:9 (widescreen) and 4:3 (standard) ratios
      galleryContainer.innerHTML = data.gallery.map((image, index) => {
        const aspectClass = index % 2 === 0 ? 'aspect-16-9' : 'aspect-4-3';
        return `
          <div class="gallery-item ${aspectClass}" onclick="openLightbox(${index})" style="cursor: pointer;">
            <img src="${image.path}" alt="Gallery ${index + 1}" style="width: 100%; height: 100%; object-fit: cover;">
            <span>${image.name || 'Facility Image ' + (index + 1)}</span>
          </div>
        `;
      }).join('');
    } else {
      // Show placeholder gallery if no images uploaded
      galleryImages = [];
      galleryContainer.innerHTML = `
        <div class="gallery-item aspect-16-9">
          <div class="gallery-placeholder">
            <i class="fas fa-image"></i>
          </div>
          <span>Main View</span>
        </div>
        <div class="gallery-item aspect-4-3">
          <div class="gallery-placeholder">
            <i class="fas fa-lightbulb"></i>
          </div>
          <span>Professional Lighting</span>
        </div>
        <div class="gallery-item aspect-16-9">
          <div class="gallery-placeholder">
            <i class="fas fa-volume-up"></i>
          </div>
          <span>Sound System</span>
        </div>
        <div class="gallery-item aspect-4-3">
          <div class="gallery-placeholder">
            <i class="fas fa-users"></i>
          </div>
          <span>Seating Area</span>
        </div>
        <div class="gallery-item aspect-16-9">
          <div class="gallery-placeholder">
            <i class="fas fa-wifi"></i>
          </div>
          <span>WiFi & Internet</span>
        </div>
        <div class="gallery-item aspect-4-3">
          <div class="gallery-placeholder">
            <i class="fas fa-video"></i>
          </div>
          <span>Projector & Screen</span>
        </div>
      `;
    }
  } catch (error) {
    console.error('Error loading gallery:', error);
    // Show default placeholder on error
    const galleryContainer = document.getElementById('facilityGallery');
    galleryImages = [];
    galleryContainer.innerHTML = `
      <div class="gallery-item aspect-16-9">
        <div class="gallery-placeholder">
          <i class="fas fa-image"></i>
        </div>
        <span>Gallery</span>
      </div>
    `;
  }
}

// Lightbox functions
function openLightbox(index) {
  if (galleryImages.length === 0) return;
  
  currentImageIndex = index;
  displayLightboxImage();
  
  const lightbox = document.getElementById('imageLightbox');
  lightbox.style.display = 'flex';
  
  // Prevent body scroll when lightbox is open
  document.body.style.overflow = 'hidden';
  
  // Add keyboard navigation
  document.addEventListener('keydown', handleLightboxKeydown);
}

function closeLightbox() {
  const lightbox = document.getElementById('imageLightbox');
  lightbox.style.display = 'none';
  
  // Restore body scroll
  document.body.style.overflow = 'auto';
  
  // Remove keyboard listener
  document.removeEventListener('keydown', handleLightboxKeydown);
}

function displayLightboxImage() {
  if (galleryImages.length === 0) return;
  
  const image = galleryImages[currentImageIndex];
  const lightboxImage = document.getElementById('lightboxImage');
  const counter = document.getElementById('lightboxCounter');
  const caption = document.getElementById('lightboxCaption');
  
  lightboxImage.src = image.path;
  lightboxImage.alt = image.name || 'Gallery Image';
  counter.textContent = `${currentImageIndex + 1} / ${galleryImages.length}`;
  caption.textContent = image.name || 'Facility Image ' + (currentImageIndex + 1);
}

function nextImage() {
  if (galleryImages.length === 0) return;
  
  currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
  displayLightboxImage();
}

function previousImage() {
  if (galleryImages.length === 0) return;
  
  currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
  displayLightboxImage();
}

function handleLightboxKeydown(event) {
  if (event.key === 'ArrowRight') {
    nextImage();
  } else if (event.key === 'ArrowLeft') {
    previousImage();
  } else if (event.key === 'Escape') {
    closeLightbox();
  }
}

// Close lightbox when clicking outside the image
document.addEventListener('click', function(event) {
  const lightbox = document.getElementById('imageLightbox');
  const container = document.querySelector('.lightbox-container');
  
  if (lightbox && lightbox.style.display === 'flex' && 
      event.target === lightbox && !container.contains(event.target)) {
    closeLightbox();
  }
});
    </script>
  </body>
</html>


