/**
 * Student Booking Form - Step Navigation
 * Handles multi-step form progression
 */

let currentStep = 1;
const totalSteps = 4;

/**
 * Initialize step navigation on page load
 */
document.addEventListener("DOMContentLoaded", function () {
  if (document.getElementById("studentBookingModal")) {
    updateStepUI();
  }
});

/**
 * Move to next step
 */
function nextStep() {
  if (validateCurrentStep()) {
    currentStep++;
    updateStepUI();
  }
}

/**
 * Move to previous step
 */
function prevStep() {
  currentStep--;
  updateStepUI();
}

/**
 * Update UI based on current step
 */
function updateStepUI() {
  // Hide all steps
  document.querySelectorAll(".form-step").forEach((step) => {
    step.classList.remove("active");
  });

  // Show current step
  const activeStep = document.querySelector(
    `.form-step[data-step="${currentStep}"]`
  );
  if (activeStep) {
    activeStep.classList.add("active");
  }

  // Update progress indicators
  document.querySelectorAll(".progress-step").forEach((step) => {
    const stepNum = parseInt(step.dataset.step);
    step.classList.remove("active", "completed");

    if (stepNum === currentStep) {
      step.classList.add("active");
    } else if (stepNum < currentStep) {
      step.classList.add("completed");
    }
  });

  // Update button visibility
  updateButtonVisibility();
}

/**
 * Update button visibility based on current step
 */
function updateButtonVisibility() {
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");
  const submitBtn = document.getElementById("submitStudentBtn");

  // Show/hide Previous button
  if (prevBtn) {
    prevBtn.style.display = currentStep > 1 ? "block" : "none";
  }

  if (currentStep === totalSteps) {
    // Last step - show submit button and enable it
    if (nextBtn) nextBtn.style.display = "none";
    if (submitBtn) {
      submitBtn.style.display = "flex";
      submitBtn.disabled = false;
      submitBtn.style.opacity = "1";
      submitBtn.style.cursor = "pointer";
    }
  } else {
    // Not last step - show next button
    if (nextBtn) nextBtn.style.display = "flex";
    if (submitBtn) submitBtn.style.display = "none";
  }
}

/**
 * Validate current step before moving to next
 */
function validateCurrentStep() {
  switch (currentStep) {
    case 1:
      return validateStep1();
    case 2:
      return validateStep2();
    case 3:
      return validateStep3();
    case 4:
      return validateStep4();
    default:
      return true;
  }
}

/**
 * Validate Step 1: Basic Information
 */
function validateStep1() {
  const bookingType = document.getElementById("bookingType");
  const clientName = document.getElementById("clientName");
  const clientEmail = document.getElementById("clientEmail");
  const organization = document.getElementById("organization");
  const contactNumber = document.getElementById("contactNumber");

  let isValid = true;

  // Check booking type
  if (!bookingType.value) {
    showFieldError(bookingType, "Please select a booking type");
    isValid = false;
  } else {
    clearFieldError(bookingType);
  }

  // Check client name
  if (!clientName.value.trim()) {
    showFieldError(clientName, "Full name is required");
    isValid = false;
  } else {
    clearFieldError(clientName);
  }

  // Check email
  if (!clientEmail.value.trim()) {
    showFieldError(clientEmail, "Email is required");
    isValid = false;
  } else if (!isValidEmail(clientEmail.value)) {
    showFieldError(clientEmail, "Please enter a valid email");
    isValid = false;
  } else {
    clearFieldError(clientEmail);
  }

  // Check organization
  if (!organization.value.trim()) {
    showFieldError(organization, "Organization name is required");
    isValid = false;
  } else {
    clearFieldError(organization);
  }

  // Check contact number
  if (!contactNumber.value.trim()) {
    showFieldError(contactNumber, "Contact number is required");
    isValid = false;
  } else {
    clearFieldError(contactNumber);
  }

  // Validate address if provided
  const address = document.getElementById("address");
  if (address.value.trim() && address.value.trim().length < 10) {
    showFieldError(address, "Address must be at least 10 characters");
    isValid = false;
  } else {
    clearFieldError(address);
  }

  if (!isValid) {
    showToast("Please fix the errors above", "error");
  }

  return isValid;
}

/**
 * Validate Step 2: Event Details
 */
function validateStep2() {
  const eventDate = document.getElementById("eventDate");
  const eventTime = document.getElementById("eventTime");
  const duration = document.getElementById("duration");
  const eventTitle = document.getElementById("eventTitle");

  let isValid = true;

  // Check event date
  if (!eventDate.value) {
    showFieldError(eventDate, "Event date is required");
    isValid = false;
  } else {
    clearFieldError(eventDate);
  }

  // Check event time
  if (!eventTime.value) {
    showFieldError(eventTime, "Event time is required");
    isValid = false;
  } else {
    clearFieldError(eventTime);
  }

  // Check duration
  if (!duration.value || duration.value < 1) {
    showFieldError(duration, "Duration must be at least 1 hour");
    isValid = false;
  } else {
    clearFieldError(duration);
  }

  // Check event title
  if (!eventTitle.value.trim()) {
    showFieldError(eventTitle, "Event title/purpose is required");
    isValid = false;
  } else {
    clearFieldError(eventTitle);
  }

  if (!isValid) {
    showToast("Please fill in all event details", "error");
  }

  return isValid;
}

/**
 * Validate Step 3: Equipment
 */
function validateStep3() {
  // Equipment is optional, just continue
  return true;
}

/**
 * Validate Step 4: Documents
 */
function validateStep4() {
  // Documents validation can be added here
  return true;
}

/**
 * Show field error styling
 */
function showFieldError(field, message) {
  field.classList.add("field-error");

  let error = field.nextElementSibling;
  if (error && error.classList.contains("inline-error")) {
    error.remove();
  }

  const errorDiv = document.createElement("div");
  errorDiv.className = "inline-error";
  errorDiv.innerHTML = `<span class="error-icon">⚠️</span> ${message}`;
  field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

/**
 * Clear field error
 */
function clearFieldError(field) {
  field.classList.remove("field-error");

  let error = field.nextElementSibling;
  if (error && error.classList.contains("inline-error")) {
    error.remove();
  }
}

/**
 * Validate email format
 */
function isValidEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

/**
 * Handle modal close - reset to step 1
 */
const originalCloseStudentModal = window.closeStudentModal;
window.closeStudentModal = function () {
  currentStep = 1;
  updateStepUI();
  if (originalCloseStudentModal) {
    originalCloseStudentModal();
  }
};

/**
 * Show toast notification
 */
function showToast(message, type = "info") {
  const toastContainer = document.getElementById("toastContainer");
  if (!toastContainer) return;

  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;

  const icons = {
    success: "✓",
    error: "✕",
    warning: "⚠",
    info: "ℹ",
  };

  toast.innerHTML = `
        <span class="toast-icon">${icons[type] || "•"}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;

  toastContainer.appendChild(toast);

  setTimeout(() => {
    if (toast.parentElement) {
      toast.classList.add("toast-fade-out");
      setTimeout(() => toast.remove(), 300);
    }
  }, 4000);
}

/**
 * Handle file selection for documents
 */
window.handleStudentFileSelect = function (input, docType) {
  const file = input.files[0];
  const filenameDisplay = document.getElementById(`filename-${docType}`);
  const uploadCard = document.querySelector(`#upload-${docType}`);
  const statusSpan = document.getElementById(`status-${docType}`);

  if (file) {
    // Validate file type
    const allowedTypes = ["application/pdf", "image/jpeg", "image/png"];
    if (!allowedTypes.includes(file.type)) {
      showFieldError(input, "Only PDF, JPG, or PNG files are allowed");
      if (statusSpan) statusSpan.textContent = "Invalid file type";
      if (uploadCard) uploadCard.classList.remove("uploaded");
      return;
    }

    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      showFieldError(input, "File size must be less than 10MB");
      if (statusSpan) statusSpan.textContent = "File too large";
      if (uploadCard) uploadCard.classList.remove("uploaded");
      return;
    }

    clearFieldError(input);
    if (filenameDisplay) {
      filenameDisplay.textContent = `✓ ${file.name}`;
    }
    if (statusSpan) {
      statusSpan.textContent = "Uploaded";
      statusSpan.style.background = "#d1fae5";
      statusSpan.style.color = "#065f46";
    }
    if (uploadCard) {
      uploadCard.classList.add("uploaded");
    }
  }
};
