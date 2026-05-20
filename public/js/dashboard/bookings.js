let bookings = [];
let currentBookingId = null;
let isLoading = false;
let cancelLetterFile = null;

document.addEventListener("DOMContentLoaded", function () {
  loadBookings();
  initializeUploadArea();
});

// Load user bookings
async function loadBookings() {
  try {
    const response = await fetch("/user/bookings/list");
    const data = await response.json();

    if (data.success) {
      bookings = data.bookings;
      renderBookings(bookings);
    } else {
      showAlert("error", data.message || "Failed to load bookings");
    }
  } catch (error) {
    console.error("Error loading bookings:", error);
    showAlert("error", "Failed to load bookings");
  }
}

// Render bookings - Single card with table layout
function renderBookings(bookingsList) {
  const container = document.getElementById("bookingsContainer");

  if (bookingsList.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h4>No Bookings Found</h4>
        <p>You haven't made any bookings yet. Start by browsing our facilities!</p>
        <a href="/facilities" class="btn btn-primary">
          <i class="fas fa-plus"></i> Make a Booking
        </a>
      </div>
    `;
    return;
  }

  const rows = bookingsList
    .map(
      (booking) => `
        <tr>
          <td class="fw-bold">#${booking.id}</td>
          <td>${booking.facility_name}</td>
          <td>${booking.event_title}</td>
          <td>${formatDate(booking.event_date)}</td>
          <td>${formatTime(booking.event_time)}</td>
          <td>${booking.duration}h</td>
          <td>₱${parseFloat(booking.total_cost || 0).toLocaleString()}</td>
          <td><span class="badge status-badge status-${
            booking.status
          }">${formatStatus(booking.status)}</span></td>
          <td>
            <div class="action-buttons">
              <button class="btn btn-sm btn-view-details" onclick="viewBookingDetails(${
                booking.id
              })" title="View Details">
                <i class="fas fa-eye"></i>
              </button>
              ${
                booking.status === "confirmed" || booking.status === "pending"
                  ? `<button class="btn btn-sm btn-info" onclick="openExtensionRequestModal(${booking.id})" title="Request Extension">
                  <i class="fas fa-clock"></i>
                </button>`
                  : ""
              }
              ${
                booking.status === "confirmed" || booking.status === "pending"
                  ? `<button class="btn btn-sm btn-cancel" onclick="confirmCancelBooking(${
                      booking.id
                    }, '${booking.status}', ${parseFloat(
                      booking.total_cost || 0
                    )})" title="Cancel">
                      <i class="fas fa-times-circle"></i>
                    </button>`
                  : ""
              }
              ${
                booking.status === "pending" || booking.status === "confirmed"
                  ? `<button class="btn btn-sm btn-upload" onclick="openUploadModalForBooking(${booking.id})" title="Upload Receipt">
                  <i class="fas fa-receipt"></i>
                </button>`
                  : ""
              }
              ${
                booking.status === "confirmed" || booking.status === "pending"
                  ? `<button class="btn btn-sm btn-warning" onclick="openRescheduleModal(${booking.id})" title="Reschedule Booking">
                  <i class="fas fa-redo"></i>
                </button>`
                  : ""
              }
              ${
                booking.status === "cancelled"
                  ? `<button class="btn btn-sm btn-danger" onclick="confirmDeleteBooking(${booking.id})" title="Delete Booking">
                  <i class="fas fa-trash"></i>
                </button>`
                  : ""
              }
            </div>
          </td>
        </tr>
      `
    )
    .join("");

  container.innerHTML = `
    <div class="booking-card">
      <div class="booking-card-header">
        <h5><i class="fas fa-list"></i> All Bookings</h5>
        <span class="booking-count badge bg-primary">${bookingsList.length}</span>
      </div>
      <div class="table-responsive">
        <table class="bookings-table">
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Facility</th>
              <th>Event</th>
              <th>Date</th>
              <th>Time</th>
              <th>Duration</th>
              <th>Cost</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

// Helper function to format status
function formatStatus(status) {
  const statusMap = {
    pending: "Pending",
    confirmed: "Confirmed",
    cancelled: "Cancelled",
    completed: "Completed",
  };
  return statusMap[status] || status;
}

// Helper function to format date
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

// Helper function to format time
function formatTime(timeString) {
  const [hours, minutes] = timeString.split(":");
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? "PM" : "AM";
  const displayHour = hour % 12 || 12;
  return `${displayHour}:${minutes} ${ampm}`;
}

// Helper function to check if booking has receipt
function hasReceipt(bookingId) {
  const booking = bookings.find((b) => b.id === bookingId);
  return booking && booking.receipt_uploaded;
}
// View booking details
async function viewBookingDetails(bookingId) {
  try {
    const response = await fetch(`/user/bookings/details/${bookingId}`);
    const data = await response.json();

    if (data.success) {
      const booking = data.booking;
      currentBookingId = bookingId;

      const modalBody = document.getElementById("bookingDetailsContent");
      modalBody.innerHTML = `
                <div class="detail-section">
                    <h6 class="detail-section-title">
                        <i class="fas fa-info-circle"></i> Booking Information
                    </h6>
                    <div class="detail-row">
                        <span class="detail-label">Booking ID:</span>
                        <span class="detail-value">#${booking.id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="booking-status status-${
                              booking.status
                            }">
                                ${formatStatus(booking.status)}
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Created:</span>
                        <span class="detail-value">${formatDateTime(
                          booking.created_at
                        )}</span>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h6 class="detail-section-title">
                        <i class="fas fa-building"></i> Facility Details
                    </h6>
                    <div class="detail-row">
                        <span class="detail-label">Facility:</span>
                        <span class="detail-value">${
                          booking.facility_name
                        }</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Plan:</span>
                        <span class="detail-value">${booking.plan_name}</span>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h6 class="detail-section-title">
                        <i class="fas fa-calendar-alt"></i> Event Details
                    </h6>
                    <div class="detail-row">
                        <span class="detail-label">Event Title:</span>
                        <span class="detail-value">${booking.event_title}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date:</span>
                        <span class="detail-value">${formatDate(
                          booking.event_date
                        )}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Time:</span>
                        <span class="detail-value">${formatTime(
                          booking.event_time
                        )}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Duration:</span>
                        <span class="detail-value">${
                          booking.duration
                        } hours</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Attendees:</span>
                        <span class="detail-value">${booking.attendees}</span>
                    </div>
                    ${
                      booking.special_requirements
                        ? `
                    <div class="detail-row">
                        <span class="detail-label">Special Requirements:</span>
                        <span class="detail-value">${booking.special_requirements}</span>
                    </div>
                    `
                        : ""
                    }
                </div>
                
                <div class="detail-section">
                    <h6 class="detail-section-title">
                        <i class="fas fa-user"></i> Contact Information
                    </h6>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value">${booking.client_name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">${
                          booking.email_address
                        }</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Contact:</span>
                        <span class="detail-value">${
                          booking.contact_number
                        }</span>
                    </div>
                    ${
                      booking.organization
                        ? `
                    <div class="detail-row">
                        <span class="detail-label">Organization:</span>
                        <span class="detail-value">${booking.organization}</span>
                    </div>
                    `
                        : ""
                    }
                </div>
                
                <div class="detail-section">
                    <h6 class="detail-section-title">
                        <i class="fas fa-calculator"></i> Cost Breakdown
                    </h6>
                    <div class="detail-row">
                        <span class="detail-label">Plan Cost:</span>
                        <span class="detail-value">₱${parseFloat(
                          booking.plan_price || 0
                        ).toLocaleString()}</span>
                    </div>
                    ${
                      booking.addons_cost > 0
                        ? `
                    <div class="detail-row">
                        <span class="detail-label">Add-ons:</span>
                        <span class="detail-value">₱${parseFloat(
                          booking.addons_cost
                        ).toLocaleString()}</span>
                    </div>
                    `
                        : ""
                    }
                    ${
                      booking.equipment_cost > 0
                        ? `
                    <div class="detail-row">
                        <span class="detail-label">Equipment:</span>
                        <span class="detail-value">₱${parseFloat(
                          booking.equipment_cost
                        ).toLocaleString()}</span>
                    </div>
                    `
                        : ""
                    }
                    <div class="total-cost">
                        <div class="detail-row">
                            <span class="detail-label">Total Cost:</span>
                            <span class="detail-value">₱${parseFloat(
                              booking.total_cost
                            ).toLocaleString()}</span>
                        </div>
                    </div>
                </div>
                
                ${getReceiptStatus(booking)}
                
                ${
                  booking.status === "cancelled" && booking.decline_reason
                    ? `
                <div class="detail-section">
                    <h6 class="detail-section-title">
                        <i class="fas fa-times-circle"></i> Cancellation Details
                    </h6>
                    <div class="detail-row">
                        <span class="detail-label">Reason:</span>
                        <span class="detail-value">${formatDeclineReason(
                          booking.decline_reason
                        )}</span>
                    </div>
                    ${
                      booking.decline_notes
                        ? `
                    <div class="detail-row">
                        <span class="detail-label">Notes:</span>
                        <span class="detail-value">${booking.decline_notes}</span>
                    </div>
                    `
                        : ""
                    }
                </div>
                `
                    : ""
                }
            `;

      // Update modal footer with action buttons
      const modalFooter = document.querySelector(".modal-footer");
      let actionButtons = ``;

      // Add Request Extension button for confirmed/pending bookings
      if (booking.status === "confirmed" || booking.status === "pending") {
        actionButtons += `
          <button type="button" class="btn btn-info" onclick="openExtensionRequestModal(${booking.id})">
            <i class="fas fa-clock"></i> Request Extension
          </button>
        `;
      }

      // Update footer with buttons
      if (modalFooter) {
        const closeButton = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
        modalFooter.innerHTML = actionButtons + closeButton;
      }

      const modal = new bootstrap.Modal(
        document.getElementById("bookingDetailsModal")
      );
      modal.show();
    } else {
      showAlert("error", data.message || "Failed to load booking details");
    }
  } catch (error) {
    console.error("Error loading booking details:", error);
    showAlert("error", "Failed to load booking details");
  }
}

// Initialize upload area
function initializeUploadArea() {
  const uploadArea = document.getElementById("uploadArea");
  const fileInput = document.getElementById("receiptFile");

  uploadArea.addEventListener("click", () => fileInput.click());

  uploadArea.addEventListener("dragover", (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = "var(--primary-color)";
    uploadArea.style.background = "rgba(74, 144, 226, 0.1)"; // Continue from where it cuts off (after uploadArea dragover event)
  });

  uploadArea.addEventListener("dragleave", (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = "#ddd";
    uploadArea.style.background = "#f8f9fa";
  });

  uploadArea.addEventListener("drop", (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = "#ddd";
    uploadArea.style.background = "#f8f9fa";

    const files = e.dataTransfer.files;
    if (files.length > 0) {
      fileInput.files = files;
      handleFileSelect(files[0]);
    }
  });

  fileInput.addEventListener("change", (e) => {
    if (e.target.files.length > 0) {
      handleFileSelect(e.target.files[0]);
    }
  });
}

// Handle file selection
function handleFileSelect(file) {
  const maxSize = 5 * 1024 * 1024; // 5MB
  const allowedTypes = [
    "application/pdf",
    "image/jpeg",
    "image/jpg",
    "image/png",
  ];

  if (!allowedTypes.includes(file.type)) {
    showAlert(
      "error",
      "Invalid file type. Please upload PDF, JPG, or PNG files only."
    );
    return;
  }

  if (file.size > maxSize) {
    showAlert("error", "File size exceeds 5MB limit.");
    return;
  }

  // Show file preview
  document.getElementById("fileName").textContent = file.name;
  document.getElementById("fileSize").textContent = formatFileSize(file.size);
  document.getElementById("filePreview").style.display = "block";
  document.getElementById("submitReceiptBtn").disabled = false;
}

// Download receipt
function downloadReceipt(event) {
  event.preventDefault();
  const bookingId = document.getElementById("uploadBookingId").value;
  window.location.href = `/user/bookings/download-receipt/${bookingId}`;
}

// Remove selected file
function removeFile() {
  document.getElementById("receiptFile").value = "";
  document.getElementById("filePreview").style.display = "none";
  document.getElementById("submitReceiptBtn").disabled = true;
}

// Upload receipt
async function uploadReceipt() {
  const bookingId = document.getElementById("uploadBookingId").value;
  const fileInput = document.getElementById("receiptFile");

  if (!fileInput.files || fileInput.files.length === 0) {
    showAlert("error", "Please select a file to upload");
    return;
  }

  const submitBtn = document.getElementById("submitReceiptBtn");
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

  try {
    // Check if there's an existing receipt to delete first
    const booking = bookings.find((b) => b.id == bookingId);
    if (booking && booking.receipt_uploaded) {
      // Delete existing receipt
      const deleteResponse = await fetch(
        `/user/bookings/delete-receipt/${bookingId}`,
        {
          method: "DELETE",
        }
      );

      const deleteResult = await deleteResponse.json();
      if (!deleteResult.success) {
        console.warn(
          "Warning: Could not delete old receipt:",
          deleteResult.message
        );
      }
    }

    // Upload new receipt
    const formData = new FormData();
    formData.append("receipt", fileInput.files[0]);

    const response = await fetch(`/user/bookings/upload-receipt/${bookingId}`, {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      showAlert("success", "Receipt uploaded successfully!");

      // Close modal
      bootstrap.Modal.getInstance(
        document.getElementById("uploadReceiptModal")
      ).hide();

      // Reload bookings to get updated receipt data
      await loadBookings();

      // Refresh modal display after a short delay to show new receipt
      setTimeout(() => {
        openUploadModalForBooking(bookingId);
      }, 500);
    } else {
      showAlert("error", data.message || "Failed to upload receipt");
    }
  } catch (error) {
    console.error("Upload error:", error);
    showAlert("error", "An error occurred while uploading");
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-check"></i> Upload Receipt';
  }
}

// Open upload modal for specific booking with auto-population
async function openUploadModalForBooking(bookingId) {
  currentBookingId = bookingId;
  document.getElementById("uploadBookingId").value = bookingId;
  document.getElementById("receiptFile").value = "";
  document.getElementById("filePreview").style.display = "none";
  document.getElementById("submitReceiptBtn").disabled = true;
  document.getElementById("receiptStatusSection").style.display = "none";

  try {
    // Fetch fresh booking data to check for receipt
    const response = await fetch(`/user/bookings/details/${bookingId}`);
    const data = await response.json();

    if (data.success) {
      const booking = data.booking;

      // Update the local bookings array with fresh data
      const bookingIndex = bookings.findIndex((b) => b.id === bookingId);
      if (bookingIndex >= 0) {
        bookings[bookingIndex] = booking;
      }

      // Load existing receipt if available
      if (booking && booking.receipt_uploaded) {
        // Show receipt status section
        document.getElementById("receiptStatusSection").style.display = "block";
        document.getElementById("receiptStatusText").textContent =
          "Uploaded on " + formatDateTime(booking.receipt_uploaded_at);
        document.getElementById(
          "downloadReceiptBtn"
        ).href = `/user/bookings/download-receipt/${bookingId}`;

        document.getElementById("submitReceiptBtn").disabled = false;
        document.getElementById("submitReceiptBtn").innerHTML =
          '<i class="fas fa-sync"></i> Replace Receipt';
      } else {
        document.getElementById("submitReceiptBtn").innerHTML =
          '<i class="fas fa-check"></i> Upload Receipt';
      }
    }
  } catch (error) {
    console.error("Error fetching booking details:", error);
  }

  const uploadModal = new bootstrap.Modal(
    document.getElementById("uploadReceiptModal")
  );
  uploadModal.show();
}

// Check if booking has receipt
function hasReceipt(bookingId) {
  const booking = bookings.find((b) => b.id === bookingId);
  return booking && booking.receipt_uploaded;
}

// Get receipt status HTML
function getReceiptStatus(booking) {
  if (!booking.receipt_uploaded) {
    return `
            <div class="detail-section">
                <h6 class="detail-section-title">
                    <i class="fas fa-receipt"></i> Payment Receipt
                </h6>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No receipt uploaded yet
                </div>
            </div>
        `;
  }

  return `
        <div class="detail-section">
            <h6 class="detail-section-title">
                <i class="fas fa-receipt"></i> Payment Receipt
            </h6>
            <div class="alert alert-success mb-3">
                <i class="fas fa-check-circle"></i> Receipt uploaded on ${formatDateTime(
                  booking.receipt_uploaded_at
                )}
            </div>
            <div class="d-flex gap-2">
                <a href="/user/bookings/download-receipt/${booking.id}" 
                   class="btn btn-sm btn-primary" 
                   target="_blank">
                    <i class="fas fa-download"></i> Download Receipt
                </a>
                <button class="btn btn-sm btn-outline-primary" 
                        onclick="openUploadModalForBooking(${booking.id})">
                    <i class="fas fa-upload"></i> Replace Receipt
                </button>
            </div>
        </div>
    `;
}

// Filter bookings
function filterBookings() {
  const status = document.getElementById("statusFilter").value;
  const date = document.getElementById("dateFilter").value;
  const search = document.getElementById("searchFilter").value.toLowerCase();

  let filtered = bookings;

  if (status) {
    filtered = filtered.filter((b) => b.status === status);
  }

  if (date) {
    filtered = filtered.filter((b) => b.event_date === date);
  }

  if (search) {
    filtered = filtered.filter(
      (b) =>
        b.facility_name.toLowerCase().includes(search) ||
        b.event_title.toLowerCase().includes(search)
    );
  }

  renderBookings(filtered);
}

// Utility functions
function formatStatus(status) {
  const statusMap = {
    pending: "Pending",
    confirmed: "Confirmed",
    cancelled: "Cancelled",
  };
  return statusMap[status] || status;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

function formatTime(timeString) {
  const [hours, minutes] = timeString.split(":");
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? "PM" : "AM";
  const displayHour = hour % 12 || 12;
  return `${displayHour}:${minutes} ${ampm}`;
}

function formatDateTime(dateTimeString) {
  const date = new Date(dateTimeString);
  return date.toLocaleString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "numeric",
    minute: "numeric",
  });
}

function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
}

function formatDeclineReason(reason) {
  const reasonMap = {
    "schedule-conflict": "Schedule Conflict",
    "facility-unavailable": "Facility Unavailable",
    "policy-violation": "Policy Violation",
    "incomplete-requirements": "Incomplete Requirements",
    other: "Other",
  };
  return reasonMap[reason] || reason;
}

// Confirm cancel booking - Opens modal for better UX
function confirmCancelBooking(bookingId, status, totalCost) {
  console.log(
    "confirmCancelBooking called with bookingId:",
    bookingId,
    "status:",
    status,
    "totalCost:",
    totalCost
  );

  // Find the booking details
  const booking = bookings.find((b) => b.id == bookingId);

  if (!booking) {
    console.error("Booking not found in bookings array:", bookingId);
    console.log("Available bookings:", bookings);
    showAlert("error", "Booking not found");
    return;
  }

  if (booking.status !== "pending" && booking.status !== "confirmed") {
    showAlert("error", "Only pending or confirmed bookings can be cancelled");
    return;
  }

  // Set booking details in hidden inputs
  document.getElementById("cancelBookingId").value = bookingId;
  document.getElementById("cancelBookingStatus").value = status;
  document.getElementById("cancelBookingCost").value = totalCost;

  // Show/hide refund policy based on whether it's a paid booking
  const refundSection = document.getElementById("refundPolicySection");
  if (totalCost > 0) {
    refundSection.style.display = "block";
  } else {
    refundSection.style.display = "none";
  }

  // Reset form fields
  document.getElementById("cancelReason").value = "";
  document.getElementById("cancelNotes").value = "";
  cancelLetterFile = null;
  document.getElementById("cancelLetterFile").value = "";
  document.getElementById("cancelLetterPreview").style.display = "none";
  document.getElementById("confirmCancelBtn").disabled = true;

  // Show modal
  const cancelModal = new bootstrap.Modal(
    document.getElementById("cancelBookingModal")
  );
  cancelModal.show();
}

// Cancel booking - Called when user confirms in modal
async function cancelBooking() {
  const bookingId = document.getElementById("cancelBookingId").value;
  const reason = document.getElementById("cancelReason").value;
  const notes = document.getElementById("cancelNotes").value.trim();

  console.log("cancelBooking called with:", {
    bookingId,
    reason,
    notes,
    hasLetter: cancelLetterFile !== null,
  });

  // Validation
  if (!bookingId) {
    showAlert("error", "Booking ID is missing");
    return;
  }

  if (!reason) {
    showAlert("error", "Please select a reason for cancellation");
    return;
  }

  if (!cancelLetterFile) {
    showAlert("error", "Please upload a cancellation letter");
    return;
  }

  try {
    // Show loading state
    const confirmBtn = document.getElementById("confirmCancelBtn");
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
    confirmBtn.disabled = true;

    // Prepare FormData for file upload
    const formData = new FormData();
    formData.append("reason", reason);
    formData.append("notes", notes);
    formData.append("cancel_letter", cancelLetterFile);

    const response = await fetch(`/api/user/bookings/${bookingId}/cancel`, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      body: formData,
    });

    const data = await response.json();

    // Reset button state
    confirmBtn.innerHTML = originalText;
    confirmBtn.disabled = false;

    if (data.success) {
      showAlert("success", "Booking cancelled successfully");

      // Close modal
      const cancelModal = bootstrap.Modal.getInstance(
        document.getElementById("cancelBookingModal")
      );
      cancelModal.hide();

      // Reload bookings
      await loadBookings();

      // Close details modal if open
      const detailsModal = bootstrap.Modal.getInstance(
        document.getElementById("bookingDetailsModal")
      );
      if (detailsModal) {
        detailsModal.hide();
      }
    } else {
      showAlert("error", data.message || "Failed to cancel booking");
    }
  } catch (error) {
    console.error("Error cancelling booking:", error);

    // Reset button state
    const confirmBtn = document.getElementById("confirmCancelBtn");
    confirmBtn.innerHTML = '<i class="fas fa-check"></i> Confirm Cancellation';
    confirmBtn.disabled = false;

    showAlert("error", "Failed to cancel booking. Please try again.");
  }
}

// Handle cancellation letter file selection
function handleCancelLetterSelect(input) {
  const file = input.files[0];
  if (!file) return;

  // Validate file size (10MB)
  if (file.size > 10 * 1024 * 1024) {
    showAlert("error", "File size must be less than 10MB");
    input.value = "";
    return;
  }

  // Validate file type
  const allowedTypes = [
    "application/pdf",
    "image/jpeg",
    "image/png",
    "image/jpg",
  ];
  if (!allowedTypes.includes(file.type)) {
    showAlert("error", "Only PDF, JPG, and PNG files are allowed");
    input.value = "";
    return;
  }

  cancelLetterFile = file;
  document.getElementById("cancelLetterName").textContent = file.name;
  document.getElementById("cancelLetterPreview").style.display = "block";

  // Enable submit button if reason is also selected
  updateCancelSubmitButtonState();

  showAlert("success", `${file.name} uploaded successfully`);
}

// Remove cancellation letter
function removeCancelLetter() {
  cancelLetterFile = null;
  document.getElementById("cancelLetterFile").value = "";
  document.getElementById("cancelLetterPreview").style.display = "none";
  updateCancelSubmitButtonState();
}

// Update cancel submit button state
function updateCancelSubmitButtonState() {
  const reason = document.getElementById("cancelReason").value;
  const hasLetter = cancelLetterFile !== null;
  const submitBtn = document.getElementById("confirmCancelBtn");

  submitBtn.disabled = !reason || !hasLetter;
}

// Open reschedule modal for pending/confirmed bookings
// Open reschedule modal for pending/confirmed bookings
async function openRescheduleModal(bookingId) {
  let booking = bookings.find((b) => b.id == bookingId);

  // If booking not found in local array, fetch from API
  if (!booking) {
    try {
      const response = await fetch(`/user/bookings/details/${bookingId}`);
      const data = await response.json();

      if (data.success) {
        booking = data.booking;
      } else {
        showAlert("error", "Booking not found");
        return;
      }
    } catch (error) {
      console.error("Error fetching booking:", error);
      showAlert("error", "Failed to load booking details");
      return;
    }
  }

  if (!booking) {
    showAlert("error", "Booking not found");
    return;
  }

  if (booking.status !== "confirmed" && booking.status !== "pending") {
    showAlert("error", "Only confirmed or pending bookings can be rescheduled");
    return;
  }

  // Set booking details in hidden inputs
  document.getElementById("rescheduleBookingId").value = bookingId;

  // Set minimum date to today
  const today = new Date().toISOString().split("T")[0];
  document.getElementById("rescheduleDate").min = today;

  // Reset form fields
  document.getElementById("rescheduleReason").value = "";
  document.getElementById("rescheduleDate").value = "";
  document.getElementById("rescheduleTime").value = "";
  document.getElementById("rescheduleNotes").value = "";
  document.getElementById("submitRescheduleBtn").disabled = true;

  // Show current booking details
  document.getElementById("currentBookingInfo").innerHTML = `
    <strong>Current Booking:</strong> ${booking.facility_name} - ${
    booking.event_title
  }<br>
    <strong>Current Date:</strong> ${formatDate(
      booking.event_date
    )} at ${formatTime(booking.event_time)}
  `;

  // Show modal
  const rescheduleModal = new bootstrap.Modal(
    document.getElementById("rescheduleBookingModal")
  );
  rescheduleModal.show();
}

// Update reschedule submit button state
function updateRescheduleSubmitButtonState() {
  const reason = document.getElementById("rescheduleReason").value;
  const date = document.getElementById("rescheduleDate").value;
  const time = document.getElementById("rescheduleTime").value;
  const submitBtn = document.getElementById("submitRescheduleBtn");

  submitBtn.disabled = !reason || !date || !time;
}

// Submit reschedule request
async function submitReschedule() {
  const bookingId = document.getElementById("rescheduleBookingId").value;
  const reason = document.getElementById("rescheduleReason").value;
  const newDate = document.getElementById("rescheduleDate").value;
  const newTime = document.getElementById("rescheduleTime").value;
  const notes = document.getElementById("rescheduleNotes").value.trim();

  if (!bookingId || !reason || !newDate || !newTime) {
    showAlert("error", "Please fill in all required fields");
    return;
  }

  try {
    const submitBtn = document.getElementById("submitRescheduleBtn");
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    submitBtn.disabled = true;

    const response = await fetch(`/api/user/bookings/${bookingId}/reschedule`, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        reason: reason,
        new_date: newDate,
        new_time: newTime,
        notes: notes,
      }),
    });

    const data = await response.json();

    if (data.success) {
      showAlert(
        "success",
        "Reschedule request submitted successfully! Email sent to office."
      );

      // Close modal
      const rescheduleModal = bootstrap.Modal.getInstance(
        document.getElementById("rescheduleBookingModal")
      );
      rescheduleModal.hide();

      // Close details modal if open
      const detailsModal = bootstrap.Modal.getInstance(
        document.getElementById("bookingDetailsModal")
      );
      if (detailsModal) {
        detailsModal.hide();
      }

      // Reload bookings
      await loadBookings();
    } else {
      showAlert("error", data.message || "Failed to submit reschedule request");
    }
  } catch (error) {
    console.error("Error submitting reschedule:", error);
    showAlert(
      "error",
      "Failed to submit reschedule request. Please try again."
    );
  } finally {
    const submitBtn = document.getElementById("submitRescheduleBtn");
    submitBtn.innerHTML =
      '<i class="fas fa-check"></i> Submit Reschedule Request';
    submitBtn.disabled = false;
  }
}

// Reschedule booking - sends email to client
async function rescheduleBooking(bookingId) {
  const booking = bookings.find((b) => b.id === bookingId);

  if (!booking) {
    showAlert("error", "Booking not found");
    return;
  }

  if (booking.status !== "cancelled") {
    showAlert("error", "Only cancelled bookings can be rescheduled");
    return;
  }

  try {
    const response = await fetch(`/api/user/bookings/${bookingId}/reschedule`, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Content-Type": "application/json",
      },
    });

    const data = await response.json();

    if (data.success) {
      showAlert(
        "success",
        "Reschedule request sent to client. An email has been sent with instructions."
      );

      // Close details modal if open
      const detailsModal = bootstrap.Modal.getInstance(
        document.getElementById("bookingDetailsModal")
      );
      if (detailsModal) {
        detailsModal.hide();
      }

      // Reload bookings
      await loadBookings();
    } else {
      showAlert("error", data.message || "Failed to send reschedule request");
    }
  } catch (error) {
    console.error("Error rescheduling booking:", error);
    showAlert("error", "Failed to send reschedule request. Please try again.");
  }
}

// Confirm delete booking
function confirmDeleteBooking(bookingId) {
  if (
    !confirm(
      "Are you sure you want to permanently delete this cancelled booking? This action cannot be undone."
    )
  ) {
    return;
  }
  deleteBooking(bookingId);
}

// Delete booking
async function deleteBooking(bookingId) {
  try {
    const response = await fetch(`/api/user/bookings/${bookingId}/delete`, {
      method: "DELETE",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Content-Type": "application/json",
      },
    });

    const data = await response.json();

    if (data.success) {
      showAlert("success", "Booking deleted successfully");

      // Close details modal if open
      const detailsModal = bootstrap.Modal.getInstance(
        document.getElementById("bookingDetailsModal")
      );
      if (detailsModal) {
        detailsModal.hide();
      }

      // Reload bookings
      await loadBookings();
    } else {
      showAlert("error", data.message || "Failed to delete booking");
    }
  } catch (error) {
    console.error("Error deleting booking:", error);
    showAlert("error", "Failed to delete booking. Please try again.");
  }
}

// Helper function to show alerts (if not already in your code)
function showAlert(type, message) {
  const alertContainer =
    document.getElementById("alertContainer") || document.body;
  const alertClass = type === "success" ? "alert-success" : "alert-danger";
  const icon = type === "success" ? "check-circle" : "exclamation-circle";

  const alert = document.createElement("div");
  alert.className = `alert ${alertClass} alert-dismissible fade show`;
  alert.style.position = "fixed";
  alert.style.top = "20px";
  alert.style.right = "20px";
  alert.style.zIndex = "9999";
  alert.innerHTML = `
    <i class="fas fa-${icon}"></i> ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;

  alertContainer.appendChild(alert);

  setTimeout(() => {
    alert.remove();
  }, 5000);
}
