// Extension Request Management
let extensionModalInstance = null;
let currentExtensionBookingData = null;

/**
 * Open extension request modal for a specific booking
 */
async function openExtensionRequestModal(bookingId) {
  try {
    // Fetch booking data from API to ensure we have the latest data
    const response = await fetch(`/user/bookings/details/${bookingId}`);
    const data = await response.json();

    if (!data.success) {
      showAlert("error", data.message || "Booking not found");
      return;
    }

    const booking = data.booking;

    // Check if extension already exists for this booking
    const extensionResponse = await fetch(
      `/api/extensions/check-booking/${bookingId}`
    );
    const extensionData = await extensionResponse.json();

    const hasExistingExtension =
      extensionData.has_extension ||
      (extensionData.extensions && extensionData.extensions.length > 0);

    // Store current booking data for cost calculation
    currentExtensionBookingData = booking;

    // Set booking ID in hidden input
    document.getElementById("extensionBookingId").value = bookingId;

    // Reset form
    document.getElementById("extensionHours").value = 1;
    document.getElementById("extensionReason").value = "";
    document.getElementById("extensionErrorAlert").style.display = "none";

    // Set hourly rate from booking facility data
    const hourlyRate = booking.hourly_rate || 0;
    document.getElementById("extensionHourlyRate").textContent = `₱${parseFloat(
      hourlyRate
    ).toLocaleString()}`;

    // Calculate initial cost
    calculateExtensionCost();

    // Show modal
    const modal = new bootstrap.Modal(
      document.getElementById("extensionRequestModal")
    );
    modal.show();
    extensionModalInstance = modal;

    // Show warning if extension already exists
    if (hasExistingExtension) {
      const extension = extensionData.extensions[0];
      const errorAlert = document.getElementById("extensionErrorAlert");
      const errorText = document.getElementById("extensionErrorText");

      errorText.innerHTML = `
        <strong>⚠️ Extension Already Exists</strong><br>
        You have already requested an extension for this booking (Status: <strong>${formatStatus(
          extension.status
        )}</strong>). 
        Only one extension request is allowed per booking.
      `;
      errorAlert.style.display = "block";
      errorAlert.className = "alert alert-warning";

      document.getElementById("submitExtensionBtn").disabled = true;
      document.getElementById("extensionHours").disabled = true;
      document.getElementById("extensionReason").disabled = true;
    } else {
      document.getElementById("submitExtensionBtn").disabled = false;
      document.getElementById("extensionHours").disabled = false;
      document.getElementById("extensionReason").disabled = false;
    }
  } catch (error) {
    console.error("Error opening extension modal:", error);
    showAlert("error", "Failed to open extension request modal");
  }
}

/**
 * Calculate extension cost based on hours and hourly rate
 */
function calculateExtensionCost() {
  try {
    const hours =
      parseInt(document.getElementById("extensionHours").value) || 1;
    const booking = currentExtensionBookingData;

    if (!booking) {
      console.error("Booking data not available");
      return;
    }

    const hourlyRate = parseFloat(booking.hourly_rate || 0);
    const totalCost = hours * hourlyRate;

    // Update display
    document.getElementById("extensionHoursDisplay").textContent = hours;
    document.getElementById(
      "extensionTotalCost"
    ).textContent = `₱${totalCost.toLocaleString()}`;

    // Enable/disable submit button based on validity
    const submitBtn = document.getElementById("submitExtensionBtn");
    if (hours >= 1 && hours <= 12 && totalCost > 0) {
      submitBtn.disabled = false;
    } else {
      submitBtn.disabled = true;
    }
  } catch (error) {
    console.error("Error calculating extension cost:", error);
  }
}

/**
 * Submit extension request to API
 */
async function submitExtensionRequest() {
  try {
    const bookingId = document.getElementById("extensionBookingId").value;
    const extensionHours = parseInt(
      document.getElementById("extensionHours").value
    );
    const reason = document.getElementById("extensionReason").value.trim();

    // Validation
    if (!bookingId) {
      showAlert("error", "Booking ID is missing");
      return;
    }

    if (extensionHours < 1 || extensionHours > 12) {
      showAlert("error", "Please select between 1 and 12 hours");
      return;
    }

    // Double-check: Verify no extension already exists before submitting
    const extensionCheckResponse = await fetch(
      `/api/extensions/check-booking/${bookingId}`
    );
    const extensionCheckData = await extensionCheckResponse.json();

    if (
      extensionCheckData.has_extension ||
      (extensionCheckData.extensions &&
        extensionCheckData.extensions.length > 0)
    ) {
      showAlert(
        "error",
        "This booking already has an extension request. Only one extension per booking is allowed."
      );
      return;
    }

    // Disable button and show loading state
    const submitBtn = document.getElementById("submitExtensionBtn");
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    // Send request to API
    const response = await fetch("/api/extensions/request", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        booking_id: bookingId,
        extension_hours: extensionHours,
        reason: reason || null,
      }),
    });

    const data = await response.json();

    if (data.success) {
      showAlert(
        "success",
        `Extension request submitted! Cost: ₱${parseFloat(
          data.extension_cost
        ).toLocaleString()}`
      );

      // Close modal
      if (extensionModalInstance) {
        extensionModalInstance.hide();
      }

      // Reload bookings to show updated status
      setTimeout(() => {
        loadBookings();
      }, 1000);
    } else {
      showAlert("error", data.message || "Failed to submit extension request");
      // Reset button
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  } catch (error) {
    console.error("Error submitting extension request:", error);
    showAlert("error", "Failed to submit extension request");

    // Reset button
    const submitBtn = document.getElementById("submitExtensionBtn");
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Request';
  }
}

/**
 * Add extension request button to booking details modal (to be called from viewBookingDetails)
 */
function getExtensionButton(booking) {
  // Only show extension button for confirmed bookings
  if (booking.status !== "confirmed" && booking.status !== "pending") {
    return "";
  }

  return `
    <button type="button" class="btn btn-info" onclick="openExtensionRequestModal(${booking.id})">
      <i class="fas fa-clock"></i> Request Extension
    </button>
  `;
}

/**
 * Format status text for display
 */
function formatStatus(status) {
  const statusMap = {
    pending: "Pending",
    approved: "Approved",
    rejected: "Rejected",
    completed: "Completed",
    confirmed: "Confirmed",
    cancelled: "Cancelled",
  };
  return statusMap[status] || status.charAt(0).toUpperCase() + status.slice(1);
}
