/**
 * External Booking Form - Step Navigation
 * Handles multi-step form progression for external bookings
 */

let externalCurrentStep = 1;
const externalTotalSteps = 4;

/**
 * Initialize step navigation on page load
 */
document.addEventListener("DOMContentLoaded", function () {
  if (document.getElementById("bookingModal")) {
    updateExternalStepUI();
  }
});

/**
 * Move to next step
 */
function externalNextStep() {
  if (validateExternalStep()) {
    externalCurrentStep++;
    updateExternalStepUI();
  }
}

/**
 * Move to previous step
 */
function externalPrevStep() {
  externalCurrentStep--;
  updateExternalStepUI();
}

/**
 * Update UI based on current step
 */
function updateExternalStepUI() {
  // Hide all steps
  document.querySelectorAll(".external-step").forEach((step) => {
    step.classList.remove("active");
  });

  // Show current step
  const activeStep = document.querySelector(
    `.external-step[data-step="${externalCurrentStep}"]`
  );
  if (activeStep) {
    activeStep.classList.add("active");
  }

  // Update progress indicators
  document
    .querySelectorAll(".external-progress .progress-step")
    .forEach((step) => {
      const stepNum = parseInt(step.dataset.step);
      step.classList.remove("active", "completed");

      if (stepNum === externalCurrentStep) {
        step.classList.add("active");
      } else if (stepNum < externalCurrentStep) {
        step.classList.add("completed");
      }
    });

  // Update button visibility
  updateExternalButtonVisibility();
}

/**
 * Update button visibility based on current step
 */
function updateExternalButtonVisibility() {
  const prevBtn = document.getElementById("extPrevBtn");
  const nextBtn = document.getElementById("extNextBtn");
  const submitBtn = document.getElementById("submitBookingBtn");

  // Show/hide Previous button
  if (prevBtn) {
    prevBtn.style.display = externalCurrentStep > 1 ? "block" : "none";
  }

  if (externalCurrentStep === externalTotalSteps) {
    // Last step - show submit button
    if (nextBtn) nextBtn.style.display = "none";
    if (submitBtn) submitBtn.style.display = "flex";
  } else {
    // Not last step - show next button
    if (nextBtn) nextBtn.style.display = "flex";
    if (submitBtn) submitBtn.style.display = "none";
  }
}

/**
 * Validate current step before moving to next
 */
function validateExternalStep() {
  switch (externalCurrentStep) {
    case 1:
      return validateExternalStep1();
    case 2:
      return validateExternalStep2();
    case 3:
      return validateExternalStep3();
    case 4:
      return validateExternalStep4();
    default:
      return true;
  }
}

/**
 * Validate Step 1: Package Selection
 */
function validateExternalStep1() {
  // Check if a plan is selected
  const selectedPlan = document.querySelector(".plan-card.selected");
  if (!selectedPlan) {
    showExternalToast("Please select a package", "error");
    return false;
  }
  return true;
}

/**
 * Validate Step 2: Client Information
 */
function validateExternalStep2() {
  const clientName = document.getElementById("clientName");
  const emailAddress = document.getElementById("emailAddress");
  const contactNumber = document.getElementById("contactNumber");
  const address = document.getElementById("address");
  const eventDate = document.getElementById("eventDate");
  const eventTime = document.getElementById("eventTime");
  const eventTitle = document.getElementById("eventTitle");

  let isValid = true;

  // Check client name
  if (!clientName.value.trim()) {
    showExternalFieldError(clientName, "Client name is required");
    isValid = false;
  } else {
    clearExternalFieldError(clientName);
  }

  // Check email
  if (!emailAddress.value.trim()) {
    showExternalFieldError(emailAddress, "Email is required");
    isValid = false;
  } else if (!isValidEmail(emailAddress.value)) {
    showExternalFieldError(emailAddress, "Please enter a valid email");
    isValid = false;
  } else {
    clearExternalFieldError(emailAddress);
  }

  // Check contact number
  if (!contactNumber.value.trim()) {
    showExternalFieldError(contactNumber, "Contact number is required");
    isValid = false;
  } else {
    clearExternalFieldError(contactNumber);
  }

  // Check address
  if (!address.value.trim()) {
    showExternalFieldError(address, "Address is required");
    isValid = false;
  } else {
    clearExternalFieldError(address);
  }

  // Check event date
  if (!eventDate.value) {
    showExternalFieldError(eventDate, "Event date is required");
    isValid = false;
  } else {
    clearExternalFieldError(eventDate);
  }

  // Check event time
  if (!eventTime.value) {
    showExternalFieldError(eventTime, "Event time is required");
    isValid = false;
  } else {
    clearExternalFieldError(eventTime);
  }

  // Check event title
  if (!eventTitle.value.trim()) {
    showExternalFieldError(eventTitle, "Event title/purpose is required");
    isValid = false;
  } else {
    clearExternalFieldError(eventTitle);
  }

  if (!isValid) {
    showExternalToast("Please fill in all required fields", "error");
  }

  return isValid;
}

/**
 * Validate Step 3: Services & Equipment
 */
function validateExternalStep3() {
  // Equipment and services are optional, just continue
  return true;
}

/**
 * Validate Step 4: Review
 */
function validateExternalStep4() {
  // Review step, everything is already validated
  return true;
}

/**
 * Show field error styling
 */
function showExternalFieldError(field, message) {
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
function clearExternalFieldError(field) {
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
const originalCloseModal = window.closeModal;
window.closeModal = function () {
  externalCurrentStep = 1;
  updateExternalStepUI();
  if (originalCloseModal) {
    originalCloseModal();
  }
};

/**
 * Show toast notification
 */
function showExternalToast(message, type = "info") {
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
