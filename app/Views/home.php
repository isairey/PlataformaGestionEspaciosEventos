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
    <title>CSPC Facility Booking - Reserve Campus Facilities Online</title>
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

      /* Hero Section */
      .hero {
        text-align: center;
        padding: 80px 20px;
        color: white;
        position: relative;
        overflow: visible;
      }

      .hero::before {
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

      .hero-content {
        position: relative;
        z-index: 2;
        max-width: 1200px;
        margin: 0 auto;
      }

      .hero h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 20px;
        text-shadow: 2px 2px 20px rgba(0, 0, 0, 0.3);
        animation: fadeInUp 1s ease-out;
        line-height: 1.2;
      }

      .hero p {
        font-size: 1.3rem;
        margin-bottom: 40px;
        opacity: 0.95;
        animation: fadeInUp 1s ease-out 0.2s both;
        line-height: 1.6;
      }

      .cta-buttons {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 40px;
        animation: fadeInUp 1s ease-out 0.4s both;
      }

      .btn-primary-custom {
        background: rgba(255, 255, 255, 0.95);
        color: #1e3c72;
        padding: 18px 40px;
        border: none;
        border-radius: 16px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
      }

      .btn-secondary-custom {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        padding: 18px 40px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 16px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
      }

      .btn-primary-custom:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 35px rgba(255, 255, 255, 0.3);
        background: white;
      }

      .btn-secondary-custom:hover {
        background: rgba(255, 255, 255, 0.95);
        color: #1e3c72;
        transform: translateY(-5px);
      }

      /* Quick Stats Banner */
      .stats-banner {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        padding: 50px 0;
        margin-top: -40px;
        position: relative;
        z-index: 10;
      }

      .stats-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
      }

      .stat-box {
        text-align: center;
        padding: 20px;
      }

      .stat-box i {
        font-size: 2.5rem;
        color: #1e3c72;
        margin-bottom: 15px;
      }

      .stat-box h3 {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1e3c72;
        margin-bottom: 5px;
      }

      .stat-box p {
        color: #64748b;
        font-weight: 600;
        font-size: 1rem;
      }

      /* How It Works Section */
      .how-it-works {
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

      .steps-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        max-width: 1400px;
        margin: 0 auto;
        position: relative;
      }

      .step-card {
        text-align: center;
        padding: 40px 30px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 2px solid transparent;
        position: relative;
      }

      .step-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        border-color: #1e3c72;
      }

      .step-number {
        width: 60px;
        height: 60px;
        background: linear-gradient(45deg, #1e3c72, #2a5298);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0 auto 25px;
      }

      .step-card i {
        font-size: 2.5rem;
        color: #1e3c72;
        margin-bottom: 20px;
      }

      .step-card h3 {
        font-size: 1.3rem;
        color: #1e293b;
        margin-bottom: 15px;
        font-weight: 700;
      }

      .step-card p {
        color: #64748b;
        line-height: 1.6;
      }

      /* Featured Facilities */
      .featured-facilities {
        padding: 100px 0;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      }

      .facilities-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        max-width: 1400px;
        margin: 0 auto;
      }

      .facility-card {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        transition: all 0.4s ease;
        border: 1px solid rgba(255, 255, 255, 0.8);
      }

      .facility-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
      }

      .facility-image {
        height: 200px;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3.5rem;
        color: white;
        position: relative;
      }

      .availability-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(34, 197, 94, 0.95);
        color: white;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: 700;
      }

      .facility-content {
        padding: 25px;
      }

      .facility-content h3 {
        font-size: 1.4rem;
        color: #1e293b;
        margin-bottom: 12px;
        font-weight: 700;
      }

      .facility-content p {
        color: #64748b;
        line-height: 1.6;
        margin-bottom: 20px;
      }

      .facility-features {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 20px;
      }

      .feature-tag {
        background: rgba(30, 60, 114, 0.1);
        color: #1e3c72;
        padding: 5px 12px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
      }

      .facility-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
      }

      .price-tag {
        color: #1e3c72;
        font-weight: 700;
        font-size: 1.1rem;
      }

      .book-btn {
        background: linear-gradient(45deg, #1e3c72, #2a5298);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
      }

      .book-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(30, 60, 114, 0.4);
        color: white;
      }

      .view-all-facilities {
        text-align: center;
        margin-top: 50px;
      }

      .view-all-btn {
        background: transparent;
        color: #1e3c72;
        border: 2px solid #1e3c72;
        padding: 15px 40px;
        border-radius: 16px;
        font-weight: 700;
        font-size: 1.1rem;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
      }

      .view-all-btn:hover {
        background: #1e3c72;
        color: white;
        transform: translateY(-3px);
      }

      /* Benefits Section */
      .benefits-section {
        padding: 100px 0;
        background: white;
      }

      .benefits-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
      }

      .benefit-card {
        text-align: center;
        padding: 40px 30px;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 20px;
        transition: all 0.3s ease;
      }

      .benefit-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
      }

      .benefit-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(45deg, #1e3c72, #2a5298);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        font-size: 2rem;
        color: white;
      }

      .benefit-card h3 {
        font-size: 1.4rem;
        color: #1e293b;
        margin-bottom: 15px;
        font-weight: 700;
      }

      .benefit-card p {
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
      }

      .search-form-wrapper {
        max-width: 1100px;
        margin: 60px auto 0;
        padding: 45px;
        background: white;
        border-radius: 24px;
        box-shadow: 0 15px 50px rgba(30, 60, 114, 0.15);
        border: 2px solid rgba(30, 60, 114, 0.08);
      }

      .search-form-title {
        font-size: 1.8rem;
        color: #1e293b;
        font-weight: 800;
        margin-bottom: 35px;
        display: flex;
        align-items: center;
        gap: 14px;
      }

      .search-form-title i {
        width: 50px;
        height: 50px;
        background: linear-gradient(45deg, #1e3c72, #2a5298);
        color: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
      }

      .search-form-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        align-items: flex-end;
      }

      .search-form-group {
        display: flex;
        flex-direction: column;
      }

      .search-form-group label {
        font-size: 0.9rem;
        color: #475569;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 7px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .search-form-group label i {
        color: #1e3c72;
        font-size: 0.95rem;
      }

      .search-form-group input,
      .search-form-group select {
        padding: 13px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: white;
        color: #1e293b;
        font-weight: 500;
      }

      .search-form-group input::placeholder {
        color: #94a3b8;
      }

      .search-form-group input:focus,
      .search-form-group select:focus {
        outline: none;
        border-color: #1e3c72;
        box-shadow: 0 0 0 4px rgba(30, 60, 114, 0.12);
        background: rgba(30, 60, 114, 0.02);
      }

      .search-btn {
        background: linear-gradient(45deg, #1e3c72, #2a5298);
        color: white;
        border: none;
        padding: 13px 30px;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        font-size: 1rem;
        box-shadow: 0 6px 20px rgba(30, 60, 114, 0.25);
      }

      .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(30, 60, 114, 0.35);
      }

      .search-btn:active {
        transform: translateY(0);
      }

      .availability-result {
        margin-top: 30px;
        padding: 20px 22px;
        border-radius: 12px;
        display: none;
        font-weight: 600;
        font-size: 0.98rem;
        animation: slideDown 0.3s ease-out;
      }

      @keyframes slideDown {
        from {
          opacity: 0;
          transform: translateY(-10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .availability-result.available {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%);
        border: 2px solid #22c55e;
        color: #16a34a;
      }

      .availability-result.unavailable {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
        border: 2px solid #ef4444;
        color: #dc2626;
      }

      .availability-result.loading {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
        border: 2px solid #3b82f6;
        color: #2563eb;
      }

      .availability-result i {
        margin-right: 10px;
        font-size: 1.1rem;
      }

      .availability-result a {
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
      }

      .availability-result.available a {
        color: #16a34a;
      }

      .availability-result.available a:hover {
        text-decoration: underline;
      }

      @media (max-width: 1024px) {
        .search-form-grid {
          grid-template-columns: repeat(2, 1fr);
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

        .search-form-grid {
          grid-template-columns: 1fr;
        }

        .search-form-wrapper {
          padding: 20px;
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
              <a class="nav-link active" href="<?= site_url('/') ?>">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?= site_url('/about') ?>">About</a>
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

    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-content">
        <h1>Reserve Campus Facilities in Minutes</h1>
        <p>
          Streamlined facility booking for CSPC students, employees, and organizations. 
          Find available spaces, check real-time schedules, and secure your reservation instantly.
        </p>
        <div class="cta-buttons">
          <button class="btn-primary-custom" onclick="window.location.href='<?= site_url('/facilities') ?>'">
            <i class="fas fa-search"></i> Browse Facilities
          </button>
          <button class="btn-secondary-custom" onclick="scrollToSection('how-it-works')">
            <i class="fas fa-info-circle"></i> How It Works
          </button>
        </div>

        <!-- Availability Search Section -->
        <div class="search-form-wrapper">
          <div class="search-form-title">
            <i class="fas fa-search"></i>
            Check Facility Availability
          </div>
          <form id="availabilityForm">
            <div class="search-form-grid">
              <div class="search-form-group">
                <label for="facilitySelect">
                  <i class="fas fa-building"></i>
                  Facility
                </label>
                <select id="facilitySelect" required>
                  <option value="">Select a facility</option>
                  <?php if (!empty($facilities)): ?>
                    <?php foreach ($facilities as $facility): ?>
                      <option value="<?= esc($facility['id']) ?>" data-key="<?= esc($facility['facility_key']) ?>">
                        <?= esc($facility['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>

              <div class="search-form-group">
                <label for="dateSelect">
                  <i class="fas fa-calendar"></i>
                  Date
                </label>
                <input type="date" id="dateSelect" required />
              </div>

              <div class="search-form-group">
                <label for="timeSelect">
                  <i class="fas fa-clock"></i>
                  Time
                </label>
                <input type="time" id="timeSelect" required />
              </div>

              <div class="search-form-group">
                <button type="button" class="search-btn" onclick="checkAvailability()">
                  <i class="fas fa-check-circle"></i> Check Availability
                </button>
              </div>
            </div>

            <div class="availability-result" id="availabilityResult"></div>
          </form>
        </div>
      </div>
    </section>

       <!-- Featured Facilities -->
    <section class="featured-facilities">
      <div class="container">
        <div class="section-header">
          <h2>Featured Facilities</h2>
          <p>Most popular spaces for events, meetings, and academic activities</p>
        </div>
        <div class="facilities-grid">
          <?php if (!empty($facilities)): ?>
            <?php foreach (array_slice($facilities, 0, 3) as $facility): ?>
              <div class="facility-card">
                <div class="facility-image">
                  <?= $facility['icon'] ?? '🏢' ?>
                  <div class="availability-badge"><?= $facility['is_maintenance'] ? 'Maintenance' : 'Available' ?></div>
                </div>
                <div class="facility-content">
                  <h3><?= esc($facility['name']) ?></h3>
                  <p><?= esc($facility['description'] ?? 'Book this facility for your events and activities') ?></p>
                  <div class="facility-features">
                    <span class="feature-tag"><i class="fas fa-snowflake"></i> Air Conditioned</span>
                    <span class="feature-tag"><i class="fas fa-volume-up"></i> Sound System</span>
                    <span class="feature-tag"><i class="fas fa-video"></i> Projector</span>
                  </div>
                  <div class="facility-footer">
                    <div class="price-tag">₱<?= number_format($facility['additional_hours_rate'] ?? 500, 0) ?>/hr</div>
                    <a href="<?= site_url('/facility/' . esc($facility['facility_key'])) ?>" class="book-btn">Book Now</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
              <p style="color: #64748b; font-size: 1.1rem;">No facilities available at this time.</p>
            </div>
          <?php endif; ?>
        </div>

        <div class="view-all-facilities">
          <a href="<?= site_url('/facilities') ?>" class="view-all-btn">
            <i class="fas fa-th-large"></i> View All <?= count($facilities) ?> Facilities
          </a>
        </div>
      </div>
    </section>


    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
      <div class="container">
        <div class="section-header">
          <h2>How to Book a Facility</h2>
          <p>Simple, fast, and secure reservation process in just four easy steps</p>
        </div>
        <div class="steps-container">
          <div class="step-card">
            <div class="step-number">1</div>
            <i class="fas fa-search"></i>
            <h3>Search Facilities</h3>
            <p>Browse available facilities or use filters to find the perfect space for your needs</p>
          </div>
          <div class="step-card">
            <div class="step-number">2</div>
            <i class="fas fa-calendar-alt"></i>
            <h3>Check Availability</h3>
            <p>View real-time schedules and select your preferred date and time slot</p>
          </div>
          <div class="step-card">
            <div class="step-number">3</div>
            <i class="fas fa-file-alt"></i>
            <h3>Submit Request</h3>
            <p>Fill out the booking form with event details and submit for approval</p>
          </div>
          <div class="step-card">
            <div class="step-number">4</div>
            <i class="fas fa-check-circle"></i>
            <h3>Get Confirmation</h3>
            <p>Receive instant notification and confirmation email for your reservation</p>
          </div>
        </div>
      </div>
    </section>

 
    <!-- Benefits Section -->
    <section class="benefits-section">
      <div class="container">
        <div class="section-header">
          <h2>Why Choose Our Booking System?</h2>
          <p>Experience hassle-free facility reservations with powerful features</p>
        </div>
        <div class="benefits-grid">
          <div class="benefit-card">
            <div class="benefit-icon">
              <i class="fas fa-bolt"></i>
            </div>
            <h3>Instant Booking</h3>
            <p>Reserve facilities in under 2 minutes with our streamlined booking process. No more paperwork or long waiting times.</p>
          </div>

          <div class="benefit-card">
            <div class="benefit-icon">
              <i class="fas fa-calendar-check"></i>
            </div>
            <h3>Real-Time Availability</h3>
            <p>Check live schedules and availability instantly. Know exactly which time slots are open before you book.</p>
          </div>

          <div class="benefit-card">
            <div class="benefit-icon">
              <i class="fas fa-bell"></i>
            </div>
            <h3>Smart Notifications</h3>
            <p>Receive instant email and system notifications for booking confirmations, approvals, and reminders.</p>
          </div>

          <div class="benefit-card">
            <div class="benefit-icon">
              <i class="fas fa-mobile-alt"></i>
            </div>
            <h3>Mobile Friendly</h3>
            <p>Book from anywhere, anytime using your smartphone, tablet, or desktop. Fully responsive design.</p>
          </div>

          <div class="benefit-card">
            <div class="benefit-icon">
              <i class="fas fa-shield-alt"></i>
            </div>
            <h3>Secure Platform</h3>
            <p>Your booking information is protected with enterprise-level security and encrypted data transmission.</p>
          </div>

          <div class="benefit-card">
            <div class="benefit-icon">
              <i class="fas fa-history"></i>
            </div>
            <h3>Booking History</h3>
            <p>Track all your past and upcoming reservations in one place. Easy access to booking receipts and details.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <div class="container">
        <h2>Ready to Book Your Facility?</h2>
        <p>
          Join thousands of CSPC students and employees who trust our platform for their facility reservations. 
          Start booking today and experience the difference!
        </p>
        <button class="btn-cta" onclick="window.location.href='<?= site_url('/facilities') ?>'">
          <i class="fas fa-rocket"></i> Start Booking Now
        </button>
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
    <script>
      function scrollToSection(sectionId) {
        document.getElementById(sectionId).scrollIntoView({
          behavior: 'smooth'
        });
      }

      // Animate stats on scroll
      document.addEventListener('DOMContentLoaded', function() {
        const observerOptions = {
          threshold: 0.5,
          rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              const statBoxes = entry.target.querySelectorAll('.stat-box h3');
              statBoxes.forEach(stat => {
                const finalText = stat.textContent;
                const match = finalText.match(/(\d+)(.*)$/);
                
                if (match) {
                  const number = parseInt(match[1]);
                  const suffix = match[2];
                  let current = 0;
                  const increment = Math.ceil(number / 30);
                  
                  const timer = setInterval(() => {
                    current += increment;
                    if (current >= number) {
                      stat.textContent = finalText;
                      clearInterval(timer);
                    } else {
                      stat.textContent = current + suffix;
                    }
                  }, 50);
                }
              });
              observer.unobserve(entry.target);
            }
          });
        }, observerOptions);

        const statsContainer = document.querySelector('.stats-container');
        if (statsContainer) {
          observer.observe(statsContainer);
        }
      });

      // Set minimum date to today
      document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('dateSelect').setAttribute('min', today);
      });

      // Check availability function
      function checkAvailability() {
        const facilityId = document.getElementById('facilitySelect').value;
        const facilityOption = document.getElementById('facilitySelect').selectedOptions[0];
        const facilityKey = facilityOption.getAttribute('data-key');
        const date = document.getElementById('dateSelect').value;
        const time = document.getElementById('timeSelect').value;
        const resultDiv = document.getElementById('availabilityResult');

        // Validate inputs
        if (!facilityId || !date || !time) {
          resultDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please fill in all fields';
          resultDiv.className = 'availability-result unavailable';
          resultDiv.style.display = 'block';
          return;
        }

        // Show loading state
        resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
        resultDiv.className = 'availability-result loading';
        resultDiv.style.display = 'block';

        // Make AJAX request to check availability
        fetch('<?= site_url('/bookings/check-availability') ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            facility_id: facilityId,
            date: date,
            time: time
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.available) {
            resultDiv.innerHTML = `
              <i class="fas fa-check-circle"></i>
              <span>
                <strong>${facilityOption.textContent}</strong> is available on <strong>${formatDate(date)}</strong> at <strong>${formatTime(time)}</strong>
                <a href="<?= site_url('/facility/') ?>${facilityKey}" style="margin-left: 15px; font-size: 1rem;">Book Now →</a>
              </span>
            `;
            resultDiv.className = 'availability-result available';
          } else {
            resultDiv.innerHTML = `
              <i class="fas fa-times-circle"></i>
              <span>
                <strong>${facilityOption.textContent}</strong> is not available on <strong>${formatDate(date)}</strong> at <strong>${formatTime(time)}</strong>. 
                Please select a different date or time.
              </span>
            `;
            resultDiv.className = 'availability-result unavailable';
          }
          resultDiv.style.display = 'block';
        })
        .catch(error => {
          console.error('Error:', error);
          resultDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error checking availability. Please try again.';
          resultDiv.className = 'availability-result unavailable';
          resultDiv.style.display = 'block';
        });
      }

      // Format date to readable format
      function formatDate(dateString) {
        const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
        return new Date(dateString + 'T00:00:00').toLocaleDateString('en-US', options);
      }

      // Format time to 12-hour format
      function formatTime(timeString) {
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
      }

      // Allow Enter key to trigger search
      document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('availabilityForm').addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            checkAvailability();
          }
        });
      });
    </script>
  </body>
</html>

