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
    } else if (email.includes("@")) {
      hintElement.innerHTML =
        '<i class="fas fa-info-circle"></i> User account will be created';
      hintElement.style.color = "#64748b";
    } else {
      hintElement.innerHTML =
        '<i class="fas fa-info-circle"></i> Use @my.cspc.edu.ph email for student account';
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