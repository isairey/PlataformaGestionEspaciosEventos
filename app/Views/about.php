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
    <title>About Us - CSPC Facility Booking System</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
      rel="stylesheet"
    />
    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      }

      body {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        min-height: 100vh;
        position: relative;
      }

      body::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.08)"/><circle cx="10" cy="50" r="0.5" fill="rgba(255,255,255,0.08)"/><circle cx="90" cy="30" r="0.5" fill="rgba(255,255,255,0.08)"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grain)"/></svg>');
        z-index: -1;
      }

      /* Navigation */
      .navbar {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        padding: 15px 0;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        border-bottom: 1px solid rgba(255, 255, 255, 0.8);
      }

      .navbar-brand {
        font-size: 28px;
        font-weight: 800;
        color: #1e3c72 !important;
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .cspc-logo-nav {
        width: 40px;
        height: 40px;
        background: linear-gradient(45deg, #1e3c72, #2a5298);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
      }

      .navbar-nav .nav-link {
        color: #1e293b !important;
        font-weight: 600;
        margin: 0 15px;
        padding: 12px 20px;
        border-radius: 12px;
        transition: all 0.3s ease;
      }

      .navbar-nav .nav-link:hover {
        color: #1e3c72 !important;
        background: rgba(30, 60, 114, 0.1);
        transform: translateY(-2px);
      }

      .login-btn, .dashboard-btn {
        background: linear-gradient(45deg, #1e3c72, #2a5298) !important;
        color: white !important;
        padding: 12px 25px;
        border-radius: 16px;
        font-weight: 600;
        box-shadow: 0 6px 20px rgba(30, 60, 114, 0.4);
        transition: all 0.3s ease;
        border: 2px solid transparent;
      }

      .login-btn:hover, .dashboard-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(30, 60, 114, 0.5);
      }

      /* Page Header Section */
      .page-header {
        text-align: center;
        padding: 120px 20px;
        color: white;
        position: relative;
        overflow: hidden;
        background: transparent;
      }

      .page-header::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('<?= base_url('images/header-landmark.jpg') ?>') center/cover no-repeat;
        z-index: 1;
        opacity: 0.3;
      }

      .breadcrumb-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 30px;
        animation: fadeInUp 1s ease-out 0.4s both;
      }

      .breadcrumb-nav a {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
      }

      .breadcrumb-nav a:hover {
        color: rgba(255, 255, 255, 1);
      }

      .breadcrumb-nav .separator {
        color: rgba(255, 255, 255, 0.5);
      }

      .page-header h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 25px;
        text-shadow: 2px 2px 20px rgba(0, 0, 0, 0.3);
        animation: fadeInUp 1s ease-out;
        line-height: 1.2;
        position: relative;
        z-index: 2;
        color: white;
      }

      .page-header p {
        font-size: 1.25rem;
        margin-bottom: 0;
        opacity: 0.95;
        animation: fadeInUp 1s ease-out 0.2s both;
        line-height: 1.6;
        position: relative;
        z-index: 2;
        color: rgba(255, 255, 255, 0.95);
        font-weight: 400;
      }

      /* Section Styles */
      .about-section {
        padding: 100px 0;
        background: white;
      }

      .section-header {
        text-align: center;
        margin-bottom: 70px;
      }

      .section-header h2 {
        font-size: 2.8rem;
        color: #1e293b;
        margin-bottom: 20px;
        font-weight: 800;
      }

      .section-header p {
        color: #64748b;
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto;
      }

      /* Our Story Section */
      .story-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
        margin-bottom: 100px;
      }

      .story-content h3 {
        font-size: 2rem;
        color: #1e293b;
        margin-bottom: 25px;
        font-weight: 700;
      }

      .story-content p {
        color: #64748b;
        font-size: 1.1rem;
        line-height: 1.8;
        margin-bottom: 20px;
      }

      .story-image {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        border-radius: 20px;
        height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 5rem;
        color: white;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
      }

      /* Mission, Vision, Values Section */
      .mvv-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
      }

      .mvv-card {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        padding: 40px;
        border-radius: 20px;
        text-align: center;
        transition: all 0.3s ease;
        border: 2px solid transparent;
      }

      .mvv-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        border-color: #1e3c72;
      }

      .mvv-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(45deg, #1e3c72, #2a5298);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        font-size: 2.5rem;
        color: white;
      }

      .mvv-card h3 {
        font-size: 1.6rem;
        color: #1e293b;
        margin-bottom: 20px;
        font-weight: 700;
      }

      .mvv-card p {
        color: #64748b;
        line-height: 1.7;
      }

      /* Team Section */
      .team-section {
        padding: 100px 0;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      }

      .team-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        max-width: 1000px;
        margin: 0 auto;
      }

      .team-member {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        text-align: center;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
      }

      .team-member:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
      }

      .team-member-image {
        width: 100%;
        height: 250px;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3.5rem;
        color: white;
      }

      .team-member-info {
        padding: 30px 20px;
      }

      .team-member h4 {
        font-size: 1.3rem;
        color: #1e293b;
        margin-bottom: 8px;
        font-weight: 700;
      }

      .team-member p {
        color: #2a5298;
        font-weight: 600;
        margin-bottom: 15px;
      }

      .team-member-desc {
        color: #64748b;
        font-size: 0.95rem;
        line-height: 1.6;
      }

      /* Features/Highlights Section */
      .highlights-section {
        padding: 100px 0;
        background: white;
      }

      .highlights-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 40px;
      }

      .highlight-item {
        display: flex;
        gap: 25px;
      }

      .highlight-icon {
        flex-shrink: 0;
        width: 70px;
        height: 70px;
        background: linear-gradient(45deg, #1e3c72, #2a5298);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
      }

      .highlight-content h3 {
        font-size: 1.3rem;
        color: #1e293b;
        margin-bottom: 10px;
        font-weight: 700;
      }

      .highlight-content p {
        color: #64748b;
        line-height: 1.6;
      }

      /* CTA Section */
      .cta-section {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        padding: 100px 0;
        text-align: center;
      }

      .cta-section h2 {
        font-size: 2.8rem;
        font-weight: 800;
        margin-bottom: 25px;
      }

      .cta-section p {
        font-size: 1.2rem;
        margin-bottom: 40px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
      }

      .btn-cta {
        background: rgba(255, 255, 255, 0.95);
        color: #1e3c72;
        padding: 18px 45px;
        border: none;
        border-radius: 16px;
        font-size: 1.2rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 8px 30px rgba(255, 255, 255, 0.3);
      }

      .btn-cta:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(255, 255, 255, 0.4);
        background: white;
      }

      /* Footer */
      .footer {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: white;
        padding: 80px 0 30px;
      }

      .footer-content {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 50px;
        margin-bottom: 40px;
      }

      .footer-section h5 {
        color: #fff;
        font-weight: 700;
        margin-bottom: 25px;
        font-size: 1.2rem;
      }

      .footer-section a {
        color: #94a3b8;
        text-decoration: none;
        transition: all 0.3s ease;
        display: block;
        margin-bottom: 12px;
      }

      .footer-section a:hover {
        color: #fff;
        transform: translateX(8px);
      }

      .footer-logo {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .footer-social {
        display: flex;
        gap: 15px;
        margin-top: 25px;
      }

      .footer-social a {
        background: rgba(255, 255, 255, 0.1);
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s ease;
      }

      .footer-social a:hover {
        background: #2a5298;
        transform: scale(1.1);
      }

      .footer-bottom {
        text-align: center;
        color: #94a3b8;
      }

      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(40px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      /* Responsive Design */
      @media (max-width: 992px) {
        .stats-container {
          grid-template-columns: repeat(2, 1fr);
        }

        .steps-container {
          grid-template-columns: repeat(2, 1fr);
        }

        .facilities-grid {
          grid-template-columns: repeat(2, 1fr);
        }

        .benefits-grid {
          grid-template-columns: repeat(2, 1fr);
        }

        .story-grid {
          grid-template-columns: 1fr;
          gap: 40px;
        }

        .mvv-grid {
          grid-template-columns: repeat(2, 1fr);
        }

        .team-grid {
          grid-template-columns: repeat(2, 1fr);
        }

        .highlights-grid {
          grid-template-columns: 1fr;
        }
      }

      @media (max-width: 768px) {
        .hero h1 {
          font-size: 2.5rem;
        }

        .cta-buttons {
          flex-direction: column;
          gap: 15px;
        }

        .stats-container,
        .steps-container,
        .facilities-grid,
        .benefits-grid {
          grid-template-columns: 1fr;
        }

        .section-header h2 {
          font-size: 2.2rem;
        }

        .page-header h1 {
          font-size: 2.2rem;
        }

        .mvv-grid,
        .team-grid {
          grid-template-columns: 1fr;
        }

        .highlights-grid {
          grid-template-columns: 1fr;
        }

        .story-image {
          height: 300px;
          font-size: 3rem;
        }
      }
    </style>
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
              <a class="nav-link active" href="<?= site_url('/about') ?>">About</a>
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
                <button class="nav-link dashboard-btn btn px-3 py-2" onclick="window.location.href='<?= site_url('/user/dashboard') ?>'">
                  <i class="fas fa-tachometer-alt"></i> Dashboard
                </button>
              <?php else: ?>
                <button class="nav-link login-btn btn px-3 py-2" onclick="window.location.href='<?= site_url('/login') ?>'">
                  <i class="fas fa-sign-in-alt"></i> Login
                </button>
              <?php endif; ?>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
      <div class="container">
        <div class="breadcrumb-nav">
          <a href="<?= site_url('/') ?>">Home</a>
          <span class="separator">•</span>
          <a href="<?= site_url('/about') ?>">About</a>
        </div>
        <h1>About CSPC Sphere</h1>
        <p>
          Transforming campus facility management through innovative digital solutions.
          Discover our mission, values, and the team behind your booking experience.
        </p>
      </div>
    </section>

    <!-- Our Story Section -->
    <section class="about-section">
      <div class="container">
        <div class="section-header">
          <h2>Our Story</h2>
          <p>How CSPC Sphere came to life</p>
        </div>

        <div class="story-grid">
          <div class="story-content">
            <h3>Building the Future of Facility Booking</h3>
            <p>
              CSPC Sphere was created to address the challenges faced by students, faculty, and organizations 
              at Camarines Sur Polytechnic Colleges in booking and managing facility reservations.
            </p>
            <p>
              What started as a simple idea to streamline the booking process has evolved into a comprehensive 
              digital platform that serves thousands of users across campus. We understood that finding and 
              booking the right facility should be quick, easy, and accessible 24/7.
            </p>
            <p>
              Today, CSPC Sphere is proud to be the go-to platform for facility management, helping students 
              organize events, researchers secure lab spaces, and administrators manage resources efficiently.
            </p>
          </div>
          <div class="story-image">
            <img src="<?= base_url('images/CSPC.jpg') ?>" alt="CPSC Campus" style="width: 100%; height: 100%; object-fit: cover; border-radius: 20px;">
          </div>
        </div>
      </div>
    </section>

    <!-- Mission, Vision, Values Section -->
    <section class="about-section" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
      <div class="container">
        <div class="section-header">
          <h2>Our Mission, Vision & Values</h2>
          <p>What drives us every day</p>
        </div>

        <div class="mvv-grid">
          <div class="mvv-card">
            <div class="mvv-icon">
              <i class="fas fa-bullseye"></i>
            </div>
            <h3>Our Mission</h3>
            <p>
              To provide a seamless, innovative facility booking platform that empowers the CSPC community 
              to efficiently manage, reserve, and optimize campus resources.
            </p>
          </div>

          <div class="mvv-card">
            <div class="mvv-icon">
              <i class="fas fa-eye"></i>
            </div>
            <h3>Our Vision</h3>
            <p>
              To become the leading digital solution for campus facility management, setting new standards 
              for accessibility, reliability, and user experience in higher education institutions.
            </p>
          </div>

          <div class="mvv-card">
            <div class="mvv-icon">
              <i class="fas fa-heart"></i>
            </div>
            <h3>Our Values</h3>
            <p>
              Innovation, reliability, accessibility, and user-centricity. We believe in continuous improvement 
              and putting our community's needs at the center of everything we do.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Highlights Section -->
    <section class="highlights-section">
      <div class="container">
        <div class="section-header">
          <h2>Why Choose CSPC Sphere?</h2>
          <p>What makes our platform the best choice for facility booking</p>
        </div>

        <div class="highlights-grid">
          <div class="highlight-item">
            <div class="highlight-icon">
              <i class="fas fa-rocket"></i>
            </div>
            <div class="highlight-content">
              <h3>Lightning Fast</h3>
              <p>Reserve facilities in under 2 minutes with our streamlined, user-friendly interface designed for speed.</p>
            </div>
          </div>

          <div class="highlight-item">
            <div class="highlight-icon">
              <i class="fas fa-shield-alt"></i>
            </div>
            <div class="highlight-content">
              <h3>Secure & Reliable</h3>
              <p>Your data is protected with enterprise-level security. Enjoy 99.9% uptime and reliable service.</p>
            </div>
          </div>

          <div class="highlight-item">
            <div class="highlight-icon">
              <i class="fas fa-mobile-alt"></i>
            </div>
            <div class="highlight-content">
              <h3>Mobile Friendly</h3>
              <p>Access CSPC Sphere from any device. Book on-the-go with our fully responsive design.</p>
            </div>
          </div>

          <div class="highlight-item">
            <div class="highlight-icon">
              <i class="fas fa-headset"></i>
            </div>
            <div class="highlight-content">
              <h3>24/7 Support</h3>
              <p>Need help? Our dedicated support team is always available to assist you with any questions.</p>
            </div>
          </div>

          <div class="highlight-item">
            <div class="highlight-icon">
              <i class="fas fa-chart-line"></i>
            </div>
            <div class="highlight-content">
              <h3>Real-Time Analytics</h3>
              <p>Track bookings, view facility usage statistics, and optimize resource allocation with live data.</p>
            </div>
          </div>

          <div class="highlight-item">
            <div class="highlight-icon">
              <i class="fas fa-sync"></i>
            </div>
            <div class="highlight-content">
              <h3>Continuous Updates</h3>
              <p>We're always improving. Regular updates bring new features and enhancements based on your feedback.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
      <div class="container">
        <div class="section-header">
          <h2>Meet Our Team</h2>
          <p>Talented individuals dedicated to your success</p>
        </div>

        <div class="team-grid">
          <div class="team-member">
            <div class="team-member-image">
              <img src="<?= base_url('images/liza.jpg') ?>" alt="Liza Mae B. Cleofe" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div class="team-member-info">
              <h4>Liza Mae B. Cleofe</h4>
              <p>Project Lead</p>
              <p class="team-member-desc">Visionary leader with 10+ years of experience in educational technology.</p>
            </div>
          </div>

          <div class="team-member">
            <div class="team-member-image">
              <img src="<?= base_url('images/jom.png') ?>" alt="Jomel G. Tienes" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div class="team-member-info">
              <h4>Jomel G. Tienes</h4>
              <p>Lead Developer</p>
              <p class="team-member-desc">Expert software engineer passionate about creating user-centric solutions.</p>
            </div>
          </div>

          <div class="team-member">
            <div class="team-member-image">
              <img src="<?= base_url('images/kat.jpg') ?>" alt="Katrina G. Luzadas" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div class="team-member-info">
              <h4>Katrina G. Luzadas</h4>
              <p>UI/UX Designer</p>
              <p class="team-member-desc">Creative designer focused on crafting intuitive and beautiful interfaces.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <div class="container">
        <h2>Ready to Experience CSPC Sphere?</h2>
        <p>
          Join thousands of CSPC students and employees who are already enjoying seamless facility booking. 
          Start your journey today!
        </p>
        <a href="<?= site_url('/facilities') ?>" class="btn-cta">
          <i class="fas fa-rocket"></i> Browse Facilities Now
        </a>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
              <div class="footer-section">
                <div class="footer-logo">
                  <div class="cspc-logo-nav">
                    <i class="fas fa-graduation-cap"></i>
                  </div>
                  CSPC Sphere
                </div>
                <p style="color: #94a3b8; line-height: 1.7; margin-bottom: 25px">
                  Your trusted platform for seamless facility booking and resource management at Camarines Sur Polytechnic Colleges.
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
                <?php if (!$isLoggedIn): ?>
                <a href="<?= site_url('/login') ?>">Login</a>
                <?php endif; ?>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="footer-section">
                <h5>Facilities</h5>
                <a href="/facilities/gymnasium">University Gymnasium</a>
                <a href="/facilities/FunctionHall">Function Hall</a>
                <a href="/facilities/AVREngineering">AVR Engineering</a>
                <a href="/facilities/AVRLibrary">AVR Library</a>
                <a href="<?= site_url('/facilities') ?>">View All</a>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="footer-section">
                <h5>Contact Info</h5>
                <p style="color: #94a3b8; display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                  <i class="fas fa-map-marker-alt" style="color: #2a5298"></i>
                  Nabua, Camarines Sur, Philippines
                </p>
                <p style="color: #94a3b8; display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                  <i class="fas fa-phone" style="color: #2a5298"></i>
                  +63 (54) 123-4567
                </p>
                <p style="color: #94a3b8; display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                  <i class="fas fa-envelope" style="color: #2a5298"></i>
                  info@cspc.edu.ph
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="footer-bottom">
          <p>&copy; 2024 Camarines Sur Polytechnic Colleges. All rights reserved.</p>
        </div>
      </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
