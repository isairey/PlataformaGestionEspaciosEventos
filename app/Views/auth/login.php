<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login & Signup - CSPC Digital Booking System</title>
  <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
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

.auth-container {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  border-radius: 25px;
  box-shadow: 0 25px 80px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  max-width: 500px;
  width: 100%;
  position: relative;
  border: 1px solid rgba(255, 255, 255, 0.8);
}

.auth-header {
  text-align: center;
  padding: 40px 40px 30px;
  background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
  color: white;
  position: relative;
  overflow: hidden;
}

.auth-header::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="15" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="20" fill="rgba(255,255,255,0.08)"/><circle cx="70" cy="20" r="10" fill="rgba(255,255,255,0.12)"/></svg>');
  z-index: 1;
}

.cspc-logo {
  width: 80px;
  height: 80px;
  margin: 0 auto 20px;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 2;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.cspc-logo i {
  font-size: 2.5rem;
  color: #1e3c72;
}

.brand-logo {
  font-size: 2.2rem;
  font-weight: 800;
  margin-bottom: 8px;
  text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.3);
  position: relative;
  z-index: 2;
}

.brand-subtitle {
  font-size: 1rem;
  opacity: 0.95;
  font-weight: 500;
  position: relative;
  z-index: 2;
}

.form-toggle {
  display: flex;
  background: #f1f5f9;
  border-radius: 20px;
  padding: 8px;
  margin: 30px 40px 30px 40px;
  position: relative;
  box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.1);
}

.toggle-btn {
  flex: 1;
  padding: 16px 20px;
  border: none;
  background: transparent;
  border-radius: 16px;
  font-weight: 700;
  color: #64748b;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  z-index: 2;
  font-size: 0.95rem;
}

.toggle-btn.active {
  color: white;
}

.toggle-slider {
  position: absolute;
  top: 8px;
  left: 8px;
  width: calc(50% - 8px);
  height: calc(100% - 16px);
  background: linear-gradient(45deg, #1e3c72, #2a5298);
  border-radius: 16px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 15px rgba(30, 60, 114, 0.4);
}

.toggle-slider.signup {
  transform: translateX(calc(100% + 8px));
}

.form-container {
  position: relative;
  padding: 0 40px 40px;
  min-height: 400px;
}

.auth-form {
  position: absolute;
  top: 0;
  left: 40px;
  right: 40px;
  opacity: 0;
  visibility: hidden;
  transform: translateX(50px);
  transition: all 0.4s ease;
}

.auth-form.active {
  opacity: 1;
  visibility: visible;
  transform: translateX(0);
  position: relative;
  left: 0;
  right: 0;
}

.form-group {
  margin-bottom: 25px;
  position: relative;
}

.form-label {
  display: block;
  margin-bottom: 12px;
  color: #1e293b;
  font-weight: 600;
  font-size: 0.95rem;
}

.form-input {
  width: 100%;
  padding: 16px 50px 16px 20px;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #f8fafc;
  font-family: inherit;
}

.form-input:focus {
  outline: none;
  border-color: #1e3c72;
  background: white;
  box-shadow: 0 0 0 4px rgba(30, 60, 114, 0.1);
  transform: translateY(-2px);
}

.input-icon {
  position: absolute;
  right: 18px;
  top: calc(50% + 12px);
  transform: translateY(-50%);
  color: #94a3b8;
  cursor: pointer;
  transition: color 0.3s ease;
  font-size: 1.1rem;
}

.input-icon:hover {
  color: #1e3c72;
}

.submit-btn {
  width: 100%;
  padding: 18px;
  background: linear-gradient(45deg, #1e3c72, #2a5298);
  color: white;
  border: none;
  border-radius: 16px;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 6px 20px rgba(30, 60, 114, 0.4);
  margin-bottom: 25px;
}

.submit-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(30, 60, 114, 0.5);
}

.submit-btn:active {
  transform: translateY(-1px);
}

.checkbox-group {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 25px;
}

.checkbox-group input[type="checkbox"] {
  width: 20px;
  height: 20px;
  accent-color: #1e3c72;
  margin-top: 2px;
  cursor: pointer;
}

.checkbox-group label {
  font-size: 0.9rem;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
  cursor: pointer;
}

.checkbox-group a {
  color: #1e3c72;
  text-decoration: none;
  font-weight: 600;
}

.checkbox-group a:hover {
  text-decoration: underline;
}
/* Enhanced Alert Styles */
.custom-alert {
  margin: 0 40px 20px;
  border-radius: 16px;
  border: none;
  padding: 20px 24px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(10px);
  animation: slideInFromTop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
  position: relative;
  overflow: hidden;
}

.custom-alert::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 5px;
  background: currentColor;
}

.alert-content {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.alert-icon {
  font-size: 1.6rem;
  margin-top: 2px;
  flex-shrink: 0;
  animation: iconPulse 0.6s ease;
}

.alert-text {
  flex: 1;
  line-height: 1.6;
}

.alert-text strong {
  display: block;
  margin-bottom: 6px;
  font-size: 1.05rem;
  font-weight: 700;
}

.alert-text p {
  margin: 0;
  font-size: 0.95rem;
  opacity: 0.95;
}

/* Danger/Error Alert */
.alert-danger.custom-alert {
  background: linear-gradient(
    135deg,
    rgba(239, 68, 68, 0.12) 0%,
    rgba(220, 38, 38, 0.08) 100%
  );
  color: #991b1b;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.alert-danger.custom-alert::before {
  background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
}

.alert-danger .alert-icon {
  color: #dc2626;
  filter: drop-shadow(0 2px 4px rgba(220, 38, 38, 0.2));
}

/* Success Alert */
.alert-success.custom-alert {
  background: linear-gradient(
    135deg,
    rgba(34, 197, 94, 0.12) 0%,
    rgba(22, 163, 74, 0.08) 100%
  );
  color: #14532d;
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.alert-success.custom-alert::before {
  background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%);
}

.alert-success .alert-icon {
  color: #16a34a;
  filter: drop-shadow(0 2px 4px rgba(22, 163, 74, 0.2));
}

/* Warning Alert */
.alert-warning.custom-alert {
  background: linear-gradient(
    135deg,
    rgba(251, 191, 36, 0.12) 0%,
    rgba(245, 158, 11, 0.08) 100%
  );
  color: #78350f;
  border: 1px solid rgba(251, 191, 36, 0.25);
}

.alert-warning.custom-alert::before {
  background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
}

.alert-warning .alert-icon {
  color: #f59e0b;
  filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.2));
}

/* Info Alert */
.alert-info.custom-alert {
  background: linear-gradient(
    135deg,
    rgba(59, 130, 246, 0.12) 0%,
    rgba(37, 99, 235, 0.08) 100%
  );
  color: #1e3a8a;
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.alert-info.custom-alert::before {
  background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
}

.alert-info .alert-icon {
  color: #2563eb;
  filter: drop-shadow(0 2px 4px rgba(37, 99, 235, 0.2));
}

/* Close Button */
.custom-alert .btn-close {
  opacity: 0.5;
  padding: 0.5rem;
  margin: -0.5rem -0.5rem -0.5rem auto;
  transition: all 0.3s ease;
  border-radius: 8px;
}

.custom-alert .btn-close:hover {
  opacity: 1;
  background: rgba(0, 0, 0, 0.08);
  transform: rotate(90deg);
}

/* Alert with Action Button */
.alert-actions {
  margin-top: 12px;
  display: flex;
  gap: 10px;
}

.alert-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.alert-btn-primary {
  background: rgba(30, 60, 114, 0.15);
  color: #1e3c72;
}

.alert-btn-primary:hover {
  background: rgba(30, 60, 114, 0.25);
  transform: translateY(-1px);
}

/* Animations */
@keyframes slideInFromTop {
  0% {
    opacity: 0;
    transform: translateY(-40px) scale(0.95);
  }
  60% {
    transform: translateY(5px) scale(1.02);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes iconPulse {
  0% {
    transform: scale(0.8);
    opacity: 0;
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Shake animation for errors */
@keyframes shake {
  0%, 100% {
    transform: translateX(0);
  }
  10%, 30%, 50%, 70%, 90% {
    transform: translateX(-5px);
  }
  20%, 40%, 60%, 80% {
    transform: translateX(5px);
  }
}

.alert-danger.custom-alert.shake {
  animation: slideInFromTop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1),
             shake 0.5s ease 0.5s;
}

/* Progress bar for auto-dismiss */
.alert-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: currentColor;
  opacity: 0.3;
  animation: progressBar 5s linear;
}

@keyframes progressBar {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .custom-alert {
    margin: 0 30px 20px 30px;
    padding: 18px 20px;
  }

  .alert-content {
    gap: 14px;
  }

  .alert-icon {
    font-size: 1.4rem;
  }

  .alert-text strong {
    font-size: 1rem;
  }

  .alert-text p {
    font-size: 0.9rem;
  }
}

@media (max-width: 480px) {
  .custom-alert {
    margin: 0 20px 20px 20px;
    padding: 16px 18px;
  }

  .alert-icon {
    font-size: 1.3rem;
  }
}
/* Alert Colors */
.alert-danger.custom-alert {
  background: linear-gradient(
    135deg,
    rgba(220, 53, 69, 0.1) 0%,
    rgba(220, 53, 69, 0.05) 100%
  );
  border-left: 4px solid #dc3545;
  color: #721c24;
}

.alert-danger .alert-icon {
  color: #dc3545;
}

.alert-success.custom-alert {
  background: linear-gradient(
    135deg,
    rgba(25, 135, 84, 0.1) 0%,
    rgba(25, 135, 84, 0.05) 100%
  );
  border-left: 4px solid #198754;
  color: #0f5132;
}

.alert-success .alert-icon {
  color: #198754;
}

.alert-warning.custom-alert {
  background: linear-gradient(
    135deg,
    rgba(255, 193, 7, 0.1) 0%,
    rgba(255, 193, 7, 0.05) 100%
  );
  border-left: 4px solid #ffc107;
  color: #664d03;
}

.alert-warning .alert-icon {
  color: #ffc107;
}

.alert-info.custom-alert {
  background: linear-gradient(
    135deg,
    rgba(13, 202, 240, 0.1) 0%,
    rgba(13, 202, 240, 0.05) 100%
  );
  border-left: 4px solid #0dcaf0;
  color: #055160;
}

.alert-info .alert-icon {
  color: #0dcaf0;
}

/* Close Button */
.custom-alert .btn-close {
  opacity: 0.6;
  padding: 0.5rem;
  margin: -0.5rem -0.5rem -0.5rem auto;
}

.custom-alert .btn-close:hover {
  opacity: 1;
}

/* Animation */
@keyframes slideInFromTop {
  0% {
    opacity: 0;
    transform: translateY(-30px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .custom-alert {
    margin: 0 30px 20px 30px;
    padding: 16px;
  }

  .alert-content {
    gap: 12px;
  }

  .alert-icon {
    font-size: 1.3rem;
  }

  .alert-text strong {
    font-size: 0.95rem;
  }
}

@media (max-width: 480px) {
  .custom-alert {
    margin: 0 20px 20px 20px;
    padding: 14px;
  }
}

@media (max-width: 768px) {
  .auth-container {
    margin: 20px;
    max-width: 100%;
  }

  .auth-header {
    padding: 30px 30px 20px;
  }

  .form-container {
    padding: 0 30px 30px;
  }

  .form-toggle {
    margin: 20px 30px;
  }

  .brand-logo {
    font-size: 1.8rem;
  }

  .alert {
    margin: 0 30px 20px 30px;
  }
}

@media (max-width: 480px) {
  .form-container {
    padding: 0 20px 20px;
  }

  .form-toggle {
    margin: 20px;
  }

  .alert {
    margin: 0 20px 20px 20px;
  }
}

/* Modal Styles */
.modal-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  z-index: 9999;
  align-items: center;
  justify-content: center;
  padding: 20px;
  backdrop-filter: blur(5px);
  animation: fadeIn 0.3s ease;
}

.modal-overlay.active {
  display: flex;
}

.modal-content {
  background: white;
  border-radius: 20px;
  max-width: 700px;
  width: 100%;
  max-height: 85vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease;
  display: flex;
  flex-direction: column;
}

.modal-header {
  background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
  color: white;
  padding: 25px 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.modal-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 35px;
  height: 35px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  transition: all 0.3s ease;
}

.modal-close:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.modal-body {
  padding: 30px;
  overflow-y: auto;
  flex: 1;
}

.modal-body h3 {
  color: #1e3c72;
  font-size: 1.2rem;
  margin-top: 25px;
  margin-bottom: 12px;
  font-weight: 700;
}

.modal-body h3:first-child {
  margin-top: 0;
}

.modal-body p {
  color: #475569;
  line-height: 1.7;
  margin-bottom: 15px;
}

.modal-body ul {
  color: #475569;
  line-height: 1.7;
  margin-bottom: 15px;
  padding-left: 25px;
}

.modal-body ul li {
  margin-bottom: 8px;
}

.modal-body strong {
  color: #1e3c72;
  font-weight: 600;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    transform: translateY(50px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@media (max-width: 768px) {
  .modal-content {
    max-width: 100%;
    max-height: 90vh;
  }

  .modal-header {
    padding: 20px;
  }

  .modal-header h2 {
    font-size: 1.3rem;
  }

  .modal-body {
    padding: 20px;
  }

  .modal-body h3 {
    font-size: 1.1rem;
  }
}
/* Input Error States */
.form-input.error {
  border-color: #ef4444;
  background: rgba(239, 68, 68, 0.05);
}

.form-input.error:focus {
  border-color: #dc2626;
  box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15);
}

.form-input.success {
  border-color: #22c55e;
}

.form-input.success:focus {
  border-color: #16a34a;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
}

/* Social Login Divider */
.social-divider {
  display: flex;
  align-items: center;
  margin: 30px 0 25px;
  color: #94a3b8;
  font-size: 0.9rem;
  font-weight: 500;
}

.social-divider::before,
.social-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e2e8f0;
}

.social-divider span {
  padding: 0 15px;
  white-space: nowrap;
}

/* Google Login Button */
.google-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 16px 20px;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  color: #1f2937;
  text-decoration: none;
  font-weight: 600;
  font-size: 1rem;
  transition: all 0.3s ease;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.google-btn:hover {
  border-color: #4285F4;
  background: #f8f9fa;
  box-shadow: 0 4px 12px rgba(66, 133, 244, 0.2);
  transform: translateY(-2px);
}

.google-btn:active {
  transform: translateY(0);
}

.google-btn svg {
  display: inline-block;
  vertical-align: middle;
}
  </style>
</head>
<body>

  <!-- Back Button -->
  <button type="button" style="position: absolute; top: 20px; left: 20px; z-index: 1000; background: rgba(255, 255, 255, 0.95); border: 2px solid rgba(30, 60, 114, 0.2); color: #1e3c72; padding: 10px 20px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); display: inline-flex; align-items: center; gap: 8px;" onclick="handleBack()" onmouseover="this.style.background='linear-gradient(45deg, #1e3c72, #2a5298)'; this.style.color='white'; this.style.borderColor='transparent'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(30, 60, 114, 0.3)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.95)'; this.style.color='#1e3c72'; this.style.borderColor='rgba(30, 60, 114, 0.2)'; this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0, 0, 0, 0.1)';">
    <i class="fas fa-arrow-left"></i> Back
  </button>

  <div class="auth-container">
    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show custom-alert" role="alert">
        <div class="alert-content">
          <i class="fas fa-exclamation-circle alert-icon"></i>
          <div class="alert-text">
            <strong>Error!</strong>
            <?= session()->getFlashdata('error') ?>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success alert-dismissible fade show custom-alert" role="alert">
        <div class="alert-content">
          <i class="fas fa-check-circle alert-icon"></i>
          <div class="alert-text">
            <strong>Success!</strong>
            <?= session()->getFlashdata('success') ?>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('warning')): ?>
      <div class="alert alert-warning alert-dismissible fade show custom-alert" role="alert">
        <div class="alert-content">
          <i class="fas fa-exclamation-triangle alert-icon"></i>
          <div class="alert-text">
            <strong>Warning!</strong>
            <?= session()->getFlashdata('warning') ?>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('info')): ?>
      <div class="alert alert-info alert-dismissible fade show custom-alert" role="alert">
        <div class="alert-content">
          <i class="fas fa-info-circle alert-icon"></i>
          <div class="alert-text">
            <strong>Info!</strong>
            <?= session()->getFlashdata('info') ?>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- HEADER -->
    <div class="auth-header">
      <div class="cspc-logo"><i class="fas fa-graduation-cap"></i></div>
      <div class="brand-logo">CSPC</div>
      <div class="brand-subtitle">Digital Booking System</div>
    </div>

    <!-- FORM TOGGLE -->
    <div class="form-toggle">
      <div class="toggle-slider" id="toggleSlider"></div>
      <button class="toggle-btn active" id="loginToggle" onclick="showLogin()">
        <i class="fas fa-sign-in-alt"></i> Login
      </button>
      <button class="toggle-btn" id="signupToggle" onclick="showSignup()">
        <i class="fas fa-user-plus"></i> Sign Up
      </button>
    </div>

    <div class="form-container">

      <!-- LOGIN FORM -->
      <form class="auth-form active" id="loginForm" method="post" action="/login">
        <div class="form-group">
          <label class="form-label">Email or Student ID</label>
          <input type="text" name="email" class="form-input" placeholder="Enter your email or student ID" required>
          <i class="fas fa-user input-icon"></i>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-input" id="loginPassword" placeholder="Enter your password" required>
          <i class="fas fa-eye input-icon password-toggle" onclick="togglePassword('loginPassword', this)"></i>
        </div>

        <div class="form-group" style="margin-top: 10px;">
          <small class="text-muted">
            <i class="fas fa-info-circle"></i> 
            Use your registered email or student ID to login
          </small>
        </div>

        <button type="submit" class="submit-btn">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>

        <!-- Social Login Divider -->
        <div class="social-divider">
          <span>Or continue with</span>
        </div>

        <!-- Google Login Button -->
        <a href="<?= base_url('google/login') ?>" class="google-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px;">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          <span>Sign in with Google</span>
        </a>
      </form>

      <!-- SIGNUP FORM -->
      <form class="auth-form" id="signupForm" method="post" action="/register">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" required>
          <i class="fas fa-user input-icon"></i>
        </div>

        <div class="form-group">
          <label class="form-label">Contact Number</label>
          <input type="tel" name="contact_number" class="form-input" placeholder="Enter your contact number" required>
          <i class="fas fa-phone input-icon"></i>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-input" id="signupEmail" placeholder="Enter your email address" required>
          <i class="fas fa-envelope input-icon"></i>
          <small class="text-muted" style="display: block; margin-top: 5px;">
            <i class="fas fa-info-circle"></i>
            Use @my.cspc.edu.ph for student account | Use @cspc.edu.ph for employee account
          </small>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-input" id="signupPassword" placeholder="Create a password (min. 6 characters)" required>
          <i class="fas fa-eye input-icon password-toggle" onclick="togglePassword('signupPassword', this)"></i>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-input" id="confirmPassword" placeholder="Confirm your password" required>
          <i class="fas fa-eye input-icon password-toggle" onclick="togglePassword('confirmPassword', this)"></i>
        </div>

<div class="checkbox-group">
  <input type="checkbox" id="terms" required>
  <label for="terms">
    I agree to the 
    <a href="#" onclick="openModal('termsModal'); return false;">Terms of Service</a> 
    and 
    <a href="#" onclick="openModal('privacyModal'); return false;">Privacy Policy</a>
  </label>
</div>

        <button type="submit" class="submit-btn">
          <i class="fas fa-user-plus"></i> Create Account
        </button>

        <div class="social-divider">
          <span>or</span>
        </div>

        <a href="/google/login" class="google-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px;">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          <span>Sign up with Google</span>
        </a>
      </form>
    </div>
  </div>

  <!-- Terms of Service Modal -->
<div class="modal-overlay" id="termsModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2><i class="fas fa-file-contract"></i> Terms of Service</h2>
      <button class="modal-close" onclick="closeModal('termsModal')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="modal-body">
      <p><strong>Last Updated:</strong> November 2024</p>
      
      <h3>1. Acceptance of Terms</h3>
      <p>By creating an account and using the CSPC Digital Booking System, you agree to comply with and be bound by these Terms of Service.</p>

      <h3>2. User Accounts</h3>
      <ul>
        <li>You must provide accurate and complete information during registration</li>
        <li>You are responsible for maintaining the confidentiality of your account credentials</li>
        <li>Students must use their official @my.cspc.edu.ph email address</li>
        <li>One account per user is permitted</li>
      </ul>

      <h3>3. Booking Rules</h3>
      <ul>
        <li>All bookings are subject to availability and approval</li>
        <li>Users must honor confirmed bookings or cancel them in advance</li>
        <li>Misuse of facilities may result in account suspension</li>
        <li>The institution reserves the right to cancel or modify bookings when necessary</li>
      </ul>

      <h3>4. Acceptable Use</h3>
      <p>Users agree not to:</p>
      <ul>
        <li>Share account credentials with others</li>
        <li>Make false or fraudulent bookings</li>
        <li>Use the system for any illegal or unauthorized purpose</li>
        <li>Interfere with or disrupt the system's operation</li>
      </ul>

      <h3>5. Account Termination</h3>
      <p>We reserve the right to suspend or terminate accounts that violate these terms or engage in inappropriate conduct.</p>

      <h3>6. Modifications</h3>
      <p>CSPC reserves the right to modify these terms at any time. Continued use of the system constitutes acceptance of modified terms.</p>

      <h3>7. Contact</h3>
      <p>For questions about these terms, please contact the CSPC administration office.</p>
    </div>
  </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal-overlay" id="privacyModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2><i class="fas fa-shield-alt"></i> Privacy Policy</h2>
      <button class="modal-close" onclick="closeModal('privacyModal')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="modal-body">
      <p><strong>Last Updated:</strong> November 2024</p>
      
      <h3>1. Information We Collect</h3>
      <p>We collect the following information when you register:</p>
      <ul>
        <li>Full name</li>
        <li>Email address</li>
        <li>Contact number</li>
        <li>Student ID (for student accounts)</li>
        <li>Booking history and preferences</li>
      </ul>

      <h3>2. How We Use Your Information</h3>
      <p>Your information is used to:</p>
      <ul>
        <li>Create and manage your account</li>
        <li>Process and confirm bookings</li>
        <li>Send booking notifications and reminders</li>
        <li>Improve our services</li>
        <li>Communicate important system updates</li>
      </ul>

      <h3>3. Information Sharing</h3>
      <p>We do not sell or share your personal information with third parties. Your data is only accessible to:</p>
      <ul>
        <li>Authorized CSPC administrators</li>
        <li>Facility coordinators for booking management</li>
        <li>System administrators for technical support</li>
      </ul>

      <h3>4. Data Security</h3>
      <p>We implement appropriate security measures to protect your personal information from unauthorized access, alteration, or disclosure. Your password is encrypted and never stored in plain text.</p>

      <h3>5. Your Rights</h3>
      <p>You have the right to:</p>
      <ul>
        <li>Access your personal information</li>
        <li>Update or correct your information</li>
        <li>Request deletion of your account</li>
        <li>Opt-out of non-essential communications</li>
      </ul>

      <h3>6. Data Retention</h3>
      <p>We retain your information for as long as your account is active or as needed to provide services. Booking history may be retained for administrative and record-keeping purposes.</p>

      <h3>7. Cookies</h3>
      <p>We use essential cookies to maintain your session and improve user experience. No tracking or advertising cookies are used.</p>

      <h3>8. Changes to Privacy Policy</h3>
      <p>We may update this privacy policy periodically. Significant changes will be communicated through the system.</p>

      <h3>9. Contact Us</h3>
      <p>If you have questions about this privacy policy, please contact the CSPC administration office.</p>
    </div>
  </div>
</div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  <script>
    function handleBack() {
      const justLoggedOut = sessionStorage.getItem('justLoggedOut');
      
      if (justLoggedOut) {
        sessionStorage.removeItem('justLoggedOut');
        window.location.href = '<?= base_url() ?>';
        return;
      }
      
      try {
        if (document.referrer && new URL(document.referrer).origin === window.location.origin) {
          const referrerPath = new URL(document.referrer).pathname;
          const protectedPaths = ['/dashboard', '/admin', '/profile', '/booking', '/student', '/user', '/facilitator'];
          
          if (!protectedPaths.some(path => referrerPath.startsWith(path))) {
            window.location.href = document.referrer;
            return;
          }
        }
      } catch (e) {
        // Ignore URL parse errors
      }
      
      if (window.history.length > 1) {
        const currentPath = window.location.pathname;
        if (currentPath === '/login' || currentPath === '/auth/login') {
          window.location.href = '<?= base_url() ?>';
        } else {
          window.history.back();
        }
      } else {
        window.location.href = '<?= base_url() ?>';
      }
    }

    window.addEventListener('pageshow', function(event) {
      if (event.persisted) {
        const justLoggedOut = sessionStorage.getItem('justLoggedOut');
        if (justLoggedOut) {
          window.location.href = '<?= base_url() ?>';
        }
      }
    });

    // Toggle Login & Signup Forms
function showLogin() {
  const loginForm = document.getElementById("loginForm");
  const signupForm = document.getElementById("signupForm");
  const toggleSlider = document.getElementById("toggleSlider");
  const loginToggle = document.getElementById("loginToggle");
  const signupToggle = document.getElementById("signupToggle");

  loginToggle.classList.add("active");
  signupToggle.classList.remove("active");
  toggleSlider.classList.remove("signup");

  signupForm.classList.remove("active");

  setTimeout(() => {
    loginForm.classList.add("active");
  }, 200);
}

function showSignup() {
  const loginForm = document.getElementById("loginForm");
  const signupForm = document.getElementById("signupForm");
  const toggleSlider = document.getElementById("toggleSlider");
  const loginToggle = document.getElementById("loginToggle");
  const signupToggle = document.getElementById("signupToggle");

  signupToggle.classList.add("active");
  loginToggle.classList.remove("active");
  toggleSlider.classList.add("signup");

  loginForm.classList.remove("active");

  setTimeout(() => {
    signupForm.classList.add("active");
  }, 200);
}

// Toggle Password Visibility
function togglePassword(inputId, icon) {
  const input = document.getElementById(inputId);
  const isPassword = input.type === "password";
  input.type = isPassword ? "text" : "password";
  icon.classList.toggle("fa-eye");
  icon.classList.toggle("fa-eye-slash");
}

// Input animations and validation
document.querySelectorAll(".form-input").forEach((input) => {
  input.addEventListener("focus", function () {
    this.parentElement.classList.add("focused");
  });

  input.addEventListener("blur", function () {
    if (!this.value) {
      this.parentElement.classList.remove("focused");
    }
  });

  input.addEventListener("input", function () {
    if (this.checkValidity()) {
      this.style.borderColor = "#22c55e";
    } else {
      this.style.borderColor = "#e2e8f0";
    }
  });
});

// Email validation for signup - show role indication
const signupEmailInput = document.getElementById("signupEmail");
if (signupEmailInput) {
  signupEmailInput.addEventListener("input", function () {
    const email = this.value.toLowerCase();
    const hintElement = this.parentElement.querySelector(".text-muted");

    if (email.endsWith("@my.cspc.edu.ph")) {
      hintElement.innerHTML =
        '<i class="fas fa-check-circle" style="color: #22c55e;"></i> Student account will be created';
      hintElement.style.color = "#22c55e";
    } else if (email.endsWith("@cspc.edu.ph") && !email.endsWith("@my.cspc.edu.ph")) {
      hintElement.innerHTML =
        '<i class="fas fa-check-circle" style="color: #2563eb;"></i> Employee account will be created';
      hintElement.style.color = "#2563eb";
    } else if (email.includes("@")) {
      hintElement.innerHTML =
        '<i class="fas fa-info-circle"></i> User account will be created';
      hintElement.style.color = "#64748b";
    } else {
      hintElement.innerHTML =
        '<i class="fas fa-info-circle"></i> Use @my.cspc.edu.ph for student account | Use @cspc.edu.ph for employee account';
      hintElement.style.color = "#64748b";
    }
  });
}

// Enhanced Form validation
document.getElementById("signupForm").addEventListener("submit", function (e) {
  const password = document.getElementById("signupPassword").value;
  const confirmPassword = document.getElementById("confirmPassword").value;

  if (password !== confirmPassword) {
    e.preventDefault();
    showAlert(
      'danger',
      'Password Mismatch',
      'The passwords you entered do not match. Please make sure both password fields are identical.',
      'password-mismatch-error',
      true
    );
    return false;
  }

  if (password.length < 6) {
    e.preventDefault();
    showAlert(
      'danger',
      'Password Too Short',
      'Your password must be at least 6 characters long for security purposes.',
      'password-length-error',
      true
    );
    return false;
  }
});

// Enhanced Alert System
function showAlert(type, title, message, className, shake = false) {
  // Remove existing alert of same class
  const existingError = document.querySelector(`.${className}`);
  if (existingError) {
    existingError.remove();
  }

  const icons = {
    danger: 'fa-exclamation-circle',
    success: 'fa-check-circle',
    warning: 'fa-exclamation-triangle',
    info: 'fa-info-circle'
  };

  const errorDiv = document.createElement("div");
  errorDiv.className = `alert alert-${type} alert-dismissible fade show custom-alert ${className} ${shake ? 'shake' : ''}`;
  errorDiv.innerHTML = `
    <div class="alert-content">
      <i class="fas ${icons[type]} alert-icon"></i>
      <div class="alert-text">
        <strong>${title}</strong>
        <p>${message}</p>
      </div>
    </div>
    <div class="alert-progress"></div>
    <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
  `;

  document
    .querySelector(".auth-container")
    .insertBefore(errorDiv, document.querySelector(".auth-header"));

  // Auto-dismiss after 6 seconds
  setTimeout(() => {
    errorDiv.style.opacity = '0';
    errorDiv.style.transform = 'translateY(-20px)';
    setTimeout(() => errorDiv.remove(), 300);
  }, 6000);
}

// Enhanced auto-dismiss for server-side alerts
document.addEventListener("DOMContentLoaded", function () {
  const alerts = document.querySelectorAll(".alert.custom-alert");
  alerts.forEach((alert) => {
    // Add progress bar if it doesn't exist
    if (!alert.querySelector('.alert-progress')) {
      const progressBar = document.createElement('div');
      progressBar.className = 'alert-progress';
      alert.appendChild(progressBar);
    }

    // Add shake effect to error alerts
    if (alert.classList.contains('alert-danger')) {
      alert.classList.add('shake');
    }

    setTimeout(() => {
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-20px)';
      setTimeout(() => {
        if (alert.parentElement) {
          alert.remove();
        }
      }, 300);
    }, 6000);
  });
});


// Modal Functions
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    closeModal(e.target.id);
  }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.active').forEach(modal => {
      closeModal(modal.id);
    });
  }
});
  </script>

</body>
</html>

