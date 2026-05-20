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
    <title>Our Facilities - CSPC Digital Booking System</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
      rel="stylesheet"
    />
   <link rel="stylesheet" href="<?= base_url(relativePath: 'css/facilities.css'); ?>">
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
              <a class="nav-link" href="<?= site_url('/about') ?>">About</a>
            </li>    
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= site_url('/facilities') ?>">Facilities</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/event') ?>">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/contact') ?>">Contact</a>
                    </li>
                    <li class="nav-item">
                        <?php if ($isLoggedIn): ?>
                            <!-- Show Dashboard button for logged-in users -->
                            <button class="nav-link dashboard-btn btn btn-success px-3 py-2" onclick="window.location.href='<?= site_url('/user/dashboard') ?>'">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </button>
                        <?php else: ?>
                            <!-- Show Login button for guests -->
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
            <a href="#home">Home</a>
            <span class="separator">•</span>
            <span>Facilities</span>
          </div>
          <h1>Premium Facilities</h1>
          <p>
            Discover our state-of-the-art facilities equipped with cutting-edge
            technology and premium amenities designed to enhance your academic
            and professional experience.
          </p>
        </div>
      </div>
    </section>

    <!-- Filters Section -->
    <section class="filters-section">
      <div class="container">
        <div class="filter-tabs">
          <button class="filter-btn active" onclick="filterFacilities('all')">
            <i class="fas fa-th-large"></i> All Facilities
          </button>
          <button class="filter-btn" onclick="filterFacilities('academic')">
            <i class="fas fa-graduation-cap"></i> Academic
          </button>
          <button class="filter-btn" onclick="filterFacilities('technology')">
            <i class="fas fa-laptop"></i> Technology
          </button>
          <button class="filter-btn" onclick="filterFacilities('events')">
            <i class="fas fa-calendar"></i> Events
          </button>
          <button class="filter-btn" onclick="filterFacilities('sports')">
            <i class="fas fa-dumbbell"></i> Sports
          </button>
          <button class="filter-btn" onclick="filterFacilities('hospitality')">
            <i class="fas fa-concierge-bell"></i> Hospitality
          </button>
        </div>
      </div>
    </section>

    <!-- Facilities Section -->
    <section class="facilities-section" id="facilities">
      <div class="container">
        <div class="section-header">
          <h2>Our Facilities</h2>
          <p>
            Choose from our comprehensive range of world-class facilities, each
            designed to meet your specific needs and requirements.
          </p>
        </div>

  <div class="row g-4" id="facilitiesGrid">
          <?php if (!empty($facilities)): ?>
            <?php foreach ($facilities as $facility): ?>
              <div class="col-lg-4 col-md-6" data-facility-id="<?= esc($facility['id']) ?>" data-facility-key="<?= esc($facility['facility_key']) ?>">
                <div class="facility-card">
                  <div class="facility-image">
                    <span class="facility-icon" style="font-size: 3rem;">
                      <?= $facility['icon'] ?? '🏢' ?>
                    </span>
                    <div class="availability-indicator" data-status="<?= esc($facility['facility_key']) ?>">
                      <span class="status-text"><?= $facility['is_maintenance'] ? 'Maintenance' : 'Available' ?></span>
                      <span class="status-icon"></span>
                    </div>
                  </div>
                  <div class="facility-content">
                    <h3><?= esc($facility['name']) ?></h3>
                    <?php if (!empty($facility['capacity'])): ?>
                    <div style="display: inline-block; background: linear-gradient(45deg, #1e3c72, #2a5298); color: white; padding: 6px 12px; border-radius: 6px; font-weight: 600; margin-bottom: 10px; font-size: 0.9rem;">
                      <i class="fas fa-users"></i> Capacity: <strong><?= $facility['capacity'] ?> persons</strong>
                    </div>
                    <?php endif; ?>
                    <p>
                      <?= esc($facility['description'] ?? 'Book this facility for your events, meetings, and activities.') ?>
                    </p>
                    <div class="facility-features">
                      <span class="feature-tag">
                        <i class="fas fa-snowflake"></i> Air Conditioned
                      </span>
                      <span class="feature-tag">
                        <i class="fas fa-volume-up"></i> Sound System
                      </span>
                      <span class="feature-tag">
                        <i class="fas fa-video"></i> Projector
                      </span>
                      <span class="feature-tag">
                        <i class="fas fa-wifi"></i> WiFi Available
                      </span>
                    </div>
                    <div class="pricing-info">
                      <span class="price-tag">₱<?= number_format($facility['additional_hours_rate'] ?? 500, 0) ?>/hour</span>
                    </div>
                    <a href="<?= site_url('/facility/' . esc($facility['facility_key'])) ?>" class="book-now-btn">
                      <i class="fas fa-calendar-check"></i> Book Now
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12" style="text-align: center; padding: 40px;">
              <p style="color: #64748b; font-size: 1.1rem;">No facilities available at this time.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>


    </section>
  

    <!-- Stats Section -->
    <section class="stats-section">
      <div class="container">
        <div class="section-header">
          <h2>Facility Metrics</h2>
          <p>
            Our campus facilities are actively used by students, employees, and
            staff for various academic and extracurricular activities.
          </p>
        </div>
        <div class="stats-grid">
          <div class="stat-item">
            <div class="stat-number">2,500+</div>
            <div class="stat-label">Bookings per Month</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">17</div>
            <div class="stat-label">Premium Facilities</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">98%</div>
            <div class="stat-label">Satisfaction Rate</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Online Booking</div>
          </div>
        </div>
      </div>
    </section>

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
              The ultimate platform for booking and managing campus facilities
              at Camarines Sur Polytechnic College, enhancing the academic
              experience.
            </p>
            <div class="footer-social">
              <a href="#"><i class="fab fa-facebook-f"></i></a>
              <a href="#"><i class="fab fa-twitter"></i></a>
              <a href="#"><i class="fab fa-instagram"></i></a>
              <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
          </div>
          <div class="col-lg-2 col-md-6 footer-section">
            <h5>Quick Links</h5>
            <a href="#">Home</a>
            <a href="#">Facilities</a>
            <a href="#">About Us</a>
            <a href="#">Contact</a>
            <a href="#">FAQs</a>
          </div>
          <div class="col-lg-3 col-md-6 footer-section">
            <h5>Facilities</h5>
            <a href="#">Auditorium</a>
            <a href="#">Conference Rooms</a>
            <a href="#">Computer Labs</a>
            <a href="#">Sports Facilities</a>
            <a href="#">Science Labs</a>
          </div>
          <div class="col-lg-3 col-md-6 footer-section">
            <h5>Contact Us</h5>
            <a href="#"
              ><i class="fas fa-map-marker-alt me-2"></i> Nabua, Camarines Sur, Philippines</a
            >
            <a href="#"><i class="fas fa-phone me-2"></i> (054) 361-2101</a>
            <a href="#"><i class="fas fa-envelope me-2"></i> cspc@edu.ph</a>
            <a href="#"
              ><i class="fas fa-clock me-2"></i> Mon-Fri: 8:00 AM - 5:00 PM</a
            >
          </div>
        </div>
        <div class="footer-bottom">
          <p>
            © 2023 CSPC Sphere. All rights reserved. Camarines Sur Polytechnic
            College.
          </p>
        </div>
      </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
      // Add dynamic status styling
      const style = document.createElement('style');
      style.textContent = `
        .availability-indicator {
          display: flex;
          align-items: center;
          gap: 8px;
          position: absolute;
          top: 15px;
          right: 15px;
          padding: 8px 14px;
          border-radius: 20px;
          font-size: 0.85rem;
          font-weight: 600;
          backdrop-filter: blur(10px);
          z-index: 10;
          transition: all 0.3s ease;
        }
        
        .availability-indicator.available {
          background: linear-gradient(135deg, rgba(34, 197, 94, 0.9), rgba(22, 163, 74, 0.9));
          color: white;
          box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }
        
        .availability-indicator.maintenance {
          background: linear-gradient(135deg, rgba(245, 158, 11, 0.9), rgba(217, 119, 6, 0.9));
          color: white;
          box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        
        .status-icon {
          display: inline-block;
          width: 8px;
          height: 8px;
          border-radius: 50%;
          background: currentColor;
          animation: pulse-status 2s infinite;
        }
        
        @keyframes pulse-status {
          0% {
            box-shadow: 0 0 0 0 currentColor;
            opacity: 1;
          }
          70% {
            box-shadow: 0 0 0 6px currentColor;
            opacity: 0;
          }
          100% {
            box-shadow: 0 0 0 0 currentColor;
            opacity: 0;
          }
        }
      `;
      document.head.appendChild(style);

      // Load facility status dynamically
      async function loadFacilityStatuses() {
        try {
          const response = await fetch('<?= base_url('api/facilities/list') ?>');
          const data = await response.json();
          
          if (data.success && data.facilities) {
            // Update each facility's status that exists on page
            data.facilities.forEach((facility) => {
              const facilityCard = document.querySelector(`[data-facility-key="${facility.facility_key}"]`);
              if (facilityCard) {
                const statusElement = facilityCard.querySelector('[data-status]');
                if (statusElement) {
                  const statusText = statusElement.querySelector('.status-text');
                  const isMaintenance = facility.is_maintenance == 1 || facility.is_maintenance === true;
                  
                  // Update status text
                  statusText.textContent = isMaintenance ? 'Maintenance' : 'Available';
                  
                  // Update indicator class for styling
                  statusElement.classList.remove('available', 'maintenance');
                  statusElement.classList.add(isMaintenance ? 'maintenance' : 'available');
                  
                  console.log(`✓ Facility "${facility.name}" status updated: ${isMaintenance ? 'Maintenance' : 'Available'}`);
                }
              }
            });
          }
        } catch (error) {
          console.error('Error loading facility statuses:', error);
        }
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

      // Filter facilities
      function filterFacilities(category) {
        const facilities = document.querySelectorAll("#facilitiesGrid > div");
        const filterButtons = document.querySelectorAll(".filter-btn");

        // Reset active state
        filterButtons.forEach((btn) => btn.classList.remove("active"));
        
        // Find and activate the clicked button
        filterButtons.forEach((btn) => {
          if (btn.getAttribute("data-category") === category) {
            btn.classList.add("active");
          }
        });

        facilities.forEach((facility) => {
          if (category === "all" || facility.dataset.category === category) {
            facility.style.display = "block";
            setTimeout(() => {
              facility.style.opacity = "1";
              facility.style.transform = "translateY(0)";
            }, 100);
          } else {
            facility.style.opacity = "0";
            facility.style.transform = "translateY(20px)";
            setTimeout(() => {
              facility.style.display = "none";
            }, 300);
          }
        });
      }

      // Animation for facilities on page load
      document.addEventListener("DOMContentLoaded", function () {
        const facilities = document.querySelectorAll(".facility-card");
        facilities.forEach((facility, index) => {
          setTimeout(() => {
            facility.style.opacity = "1";
            facility.style.transform = "translateY(0)";
          }, 100 * index);
        });

        // Apply initial CSS classes to status indicators
        document.querySelectorAll('[data-status]').forEach((statusElement) => {
          const statusText = statusElement.querySelector('.status-text');
          const text = statusText.textContent.trim();
          
          // Remove any existing classes
          statusElement.classList.remove('available', 'maintenance');
          
          // Add appropriate class based on current text
          if (text === 'Maintenance') {
            statusElement.classList.add('maintenance');
          } else {
            statusElement.classList.add('available');
          }
        });

        // Load facility statuses dynamically
        loadFacilityStatuses();

        // Refresh status every 30 seconds
        setInterval(loadFacilityStatuses, 30000);
      });

      // Login alert
      function showLoginAlert() {
        alert(
          "Login functionality will be available soon. Please check back later!"
        );
      }

      // Booking modal
      function openBookingModal(facilityName) {
        alert(
          `Booking system for ${facilityName} will be implemented soon. Thank you for your interest!`
        );
      }

      // Animation for facilities on page load
      document.addEventListener("DOMContentLoaded", function () {
        const facilities = document.querySelectorAll(".facility-card");
        facilities.forEach((facility, index) => {
          setTimeout(() => {
            facility.style.opacity = "1";
            facility.style.transform = "translateY(0)";
          }, 100 * index);
        });
      });
    </script>
  </body>
</html>


