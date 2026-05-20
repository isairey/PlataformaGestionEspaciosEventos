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
    <title>Contact Us - CSPC Digital Booking System</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= base_url('css/style.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('css/contact.css'); ?>">
  </head>
  <body>
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
                        <a class="nav-link" href="<?= site_url('/facilities') ?>">Facilities</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/event') ?>">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= site_url('/contact') ?>">Contact</a>
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

    <!-- Page Header -->
    <section class="page-header">
      <div class="container">
        <div class="page-header-content">
          <h1>Get in Touch</h1>
          <p>
            We're here to help you with all your facility booking needs. 
            Reach out to us and we'll respond as soon as possible.
          </p>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
      <div class="container">
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="contact-card">
              <h2 class="section-title">Send us a Message</h2>
              <p class="section-subtitle">
                Have a question about our facilities or need assistance with booking? 
                Fill out the form below and we'll get back to you promptly.
              </p>
              
              <form id="contactForm">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="firstName">First Name *</label>
                      <input type="text" id="firstName" name="firstName" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="lastName">Last Name *</label>
                      <input type="text" id="lastName" name="lastName" required>
                    </div>
                  </div>
                </div>
                
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="email">Email Address *</label>
                      <input type="email" id="email" name="email" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="phone">Phone Number</label>
                      <input type="tel" id="phone" name="phone">
                    </div>
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="subject">Subject *</label>
                  <select id="subject" name="subject" required>
                    <option value="">Select a subject</option>
                    <option value="facility-booking">Facility Booking Inquiry</option>
                    <option value="event-planning">Event Planning</option>
                    <option value="technical-support">Technical Support</option>
                    <option value="billing">Billing & Payment</option>
                    <option value="general">General Information</option>
                    <option value="feedback">Feedback & Suggestions</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="message">Message *</label>
                  <textarea id="message" name="message" placeholder="Please provide details about your inquiry..." required></textarea>
                </div>
                
                <button type="submit" class="submit-btn">
                  <i class="fas fa-paper-plane"></i> Send Message
                </button>
              </form>
            </div>
          </div>
          
          <div class="col-lg-4">
            <div class="contact-info-card">
              <div class="contact-info-content">
                <h3 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 30px;">Contact Information</h3>
                
                <div class="contact-info-item">
                  <div class="contact-info-icon">
                    <i class="fas fa-map-marker-alt"></i>
                  </div>
                  <div class="contact-info-text">
                    <h4>Our Location</h4>
                    <p>Camarines Sur Polytechnic Colleges<br>
                    Nabua, Camarines Sur<br>
                    Philippines, 4434</p>
                  </div>
                </div>
                
                <div class="contact-info-item">
                  <div class="contact-info-icon">
                    <i class="fas fa-phone"></i>
                  </div>
                  <div class="contact-info-text">
                    <h4>Phone Numbers</h4>
                    <p>Main Line: +63 (54) 123-4567<br>
                    Booking Hotline: +63 (54) 123-4568</p>
                  </div>
                </div>
                
                <div class="contact-info-item">
                  <div class="contact-info-icon">
                    <i class="fas fa-envelope"></i>
                  </div>
                  <div class="contact-info-text">
                    <h4>Email Addresses</h4>
                    <p>General: info@cspc.edu.ph<br>
                    Bookings: bookings@cspc.edu.ph</p>
                  </div>
                </div>
                
                <div class="contact-info-item">
                  <div class="contact-info-icon">
                    <i class="fas fa-clock"></i>
                  </div>
                  <div class="contact-info-text">
                    <h4>Response Time</h4>
                    <p>We typically respond within<br>
                    24 hours during business days</p>
                  </div>
                </div>
              </div>

              
            </div>
            
            <div class="office-hours">
              <h4><i class="fas fa-business-time" style="color: #1e3c72; margin-right: 10px;"></i>Office Hours</h4>
              <div class="hours-item">
                <span class="hours-day">Monday - Friday</span>
                <span class="hours-time">8:00 AM - 5:00 PM</span>
              </div>
              <div class="hours-item">
                <span class="hours-day">Saturday</span>
                <span class="hours-time">9:00 AM - 1:00 PM</span>
              </div>
              <div class="hours-item">
                <span class="hours-day">Sunday</span>
                <span class="hours-time">Closed</span>
              </div>
              <div class="hours-item">
                <span class="hours-day">Holidays</span>
                <span class="hours-time">Limited Hours</span>
              </div>
            </div>
            
 
          </div>
        </div>
      </div>
    </section>

    <!-- Find Us Section -->
    <section class="map-section">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="text-center mb-5">
              <h2 class="section-title">Find Us</h2>
              <p class="section-subtitle">
                Located in the heart of Nabua, Camarines Sur, our campus is easily accessible 
                and well-connected to major transportation routes.
              </p>
            </div>
            
           <div class="map-container">
              <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d9230.912773538894!2d123.377251702153!3d13.404850463519942!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33a199a3e62f19ed%3A0xd70d166421a51a16!2sCamarines%20Sur%20Polytechnic%20Colleges!5e0!3m2!1sen!2sph!4v1763550280419!5m2!1sen!2sph" 
                width="100%" 
                height="450" 
                style="border:0; border-radius: 15px;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
              </iframe>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
   <footer class="footer" id="contact">
      <div class="container">
        <div class="footer-content">
          <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
              <div class="footer-section">
                <div class="footer-logo">
                  <div class="cspc-logo-nav">
                    <i class="fas fa-graduation-cap"></i>
                  </div>
                  CSPC
                </div>
                <p
                  style="color: #94a3b8; line-height: 1.7; margin-bottom: 25px"
                >
                  Empowering education through innovative digital solutions.
                  Your trusted partner for seamless facility management and
                  booking experiences.
                </p>
                <div class="footer-social">
                  <a href="#"><i class="fab fa-facebook-f"></i></a>
                  <a href="#"><i class="fab fa-twitter"></i></a>
                  <a href="#"><i class="fab fa-instagram"></i></a>
                  <a href="#"><i class="fab fa-linkedin-in"></i></a>
                  <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
              </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
              <div class="footer-section">
                <h5>Quick Links</h5>
                <a href="#home">Home</a>
                <a href="#facilities">Facilities</a>
                <a href="#about">About Us</a>
                <a href="#contact">Contact</a>
                <a href="#" onclick="showLoginModal()">Login</a>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="footer-section">
                <h5>Services</h5>
                <a href="#">Facility Booking</a>
                <a href="#">Event Management</a>
                <a href="#">Equipment Rental</a>
                <a href="#">Technical Support</a>
                <a href="#">User Training</a>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="footer-section">
                <h5>Contact Info</h5>
                <p
                  style="
                    color: #94a3b8;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 15px;
                  "
                >
                  <i class="fas fa-map-marker-alt" style="color: #2a5298"></i>
                  Nabua, Camarines Sur, Philippines
                </p>
                <p
                  style="
                    color: #94a3b8;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 15px;
                  "
                >
                  <i class="fas fa-phone" style="color: #2a5298"></i>
                  +63 (54) 123-4567
                </p>
                <p
                  style="
                    color: #94a3b8;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 15px;
                  "
                >
                  <i class="fas fa-envelope" style="color: #2a5298"></i>
                  info@cspc.edu.ph
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="footer-bottom">
          <p>
            &copy; 2024 Camarines Sur Polytechnic Colleges. All rights reserved.
            | Designed with ❤️ for education
          </p>
        </div>
      </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
  // Toast Notification System
  function showToast(message, type = 'success') {
    // Remove existing toasts
    const existingToast = document.querySelector('.custom-toast');
    if (existingToast) {
      existingToast.remove();
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `custom-toast ${type}`;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    const bgColor = type === 'success' ? 'linear-gradient(135deg, #059669, #10b981)' : 'linear-gradient(135deg, #dc2626, #ef4444)';
    
    toast.innerHTML = `
      <div class="toast-content">
        <i class="fas ${icon} toast-icon"></i>
        <div class="toast-message">${message}</div>
        <button class="toast-close" onclick="this.parentElement.parentElement.remove()">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;
    
    toast.style.background = bgColor;
    
    document.body.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    }, 5000);
  }

  // Form submission handler
  document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(this);
    
    // Basic validation
    const firstName = formData.get('firstName');
    const lastName = formData.get('lastName');
    const email = formData.get('email');
    const subject = formData.get('subject');
    const message = formData.get('message');
    
    if (!firstName || !lastName || !email || !subject || !message) {
      showToast('Please fill in all required fields.', 'error');
      return;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      showToast('Please enter a valid email address.', 'error');
      return;
    }
    
    const submitBtn = document.querySelector('.submit-btn');
    const originalText = submitBtn.innerHTML;
    
    // Loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    submitBtn.disabled = true;
    
    // Send to server
    fetch('<?= site_url('contact/send') ?>', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Message Sent!';
        submitBtn.style.background = 'linear-gradient(45deg, #059669, #10b981)';
        
        // Reset form
        document.getElementById('contactForm').reset();
        
        // Show success toast
        showToast(data.message, 'success');
        
        // Reset button after 3 seconds
        setTimeout(() => {
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
          submitBtn.style.background = 'linear-gradient(45deg, #1e3c72, #2a5298)';
        }, 3000);
      } else {
        throw new Error(data.message);
      }
    })
    .catch(error => {
      showToast(error.message || 'Sorry, there was an error sending your message. Please try again.', 'error');
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    });
  });
  
  // Add smooth scrolling to anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
  
  // Animate elements on scroll
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };
  
  const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.animation = 'fadeInUp 0.8s ease-out forwards';
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);
  
  // Observe elements for animation
  document.addEventListener('DOMContentLoaded', function() {
    const animateElements = document.querySelectorAll('.contact-card, .contact-info-card, .office-hours, .quick-links, .map-container');
    animateElements.forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(30px)';
      observer.observe(el);
    });
  });
  
  // Add hover effects to contact info items
  document.querySelectorAll('.contact-info-item').forEach(item => {
    item.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-5px) scale(1.02)';
    });
    
    item.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(-3px) scale(1)';
    });
  });
</script>

    </script>
  </body>
</html>