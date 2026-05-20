let bookings = [];
let currentBookingId = null;
let isLoading = false;

document.addEventListener("DOMContentLoaded", function () {
  loadBookings();
  initializeUploadArea();
});

// Load user bookings
async function loadBookings() {
  try {
    const response = await fetch("/api/student/bookings", {
      credentials: "include",
    });
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

// Render bookings
// Updated renderBookings function - Single card with table layout
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
                booking.status === "pending" || booking.status === "confirmed"
                  ? `<button class="btn btn-sm btn-warning" onclick="openRescheduleModal(${booking.id})" title="Reschedule Booking">
                      <i class="fas fa-redo"></i>
                    </button>`
                  : ""
              }
              ${
                booking.status === "pending" || booking.status === "confirmed"
                  ? `<button class="btn btn-sm btn-cancel" onclick="confirmCancelBooking(${booking.id}, '${booking.status}')" title="Cancel">
                      <i class="fas fa-times-circle"></i>
                    </button>`
                  : ""
              }
              ${
                booking.status === "pending" || booking.status === "confirmed"
                  ? `<button class="btn btn-sm btn-upload" onclick="openDocumentUploadModal(${booking.id})" title="Upload">
                      <i class="fas fa-upload"></i>
                    </button>`
                  : ""
              }
              ${
                booking.status === "cancelled"
                  ? `<button class="btn btn-sm btn-danger" onclick="confirmDeleteBooking(${booking.id})" title="Delete">
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
    const response = await fetch(`/api/student/bookings/${bookingId}`, {
      credentials: "include",
    });
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

  const formData = new FormData();
  formData.append("receipt", fileInput.files[0]);
  formData.append("booking_id", bookingId);

  const submitBtn = document.getElementById("submitReceiptBtn");
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

  try {
    const response = await fetch(`/user/bookings/upload-receipt/${bookingId}`, {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      showAlert("success", "Receipt uploaded successfully!");

      // Close upload modal
      const uploadModal = bootstrap.Modal.getInstance(
        document.getElementById("uploadReceiptModal")
      );
      uploadModal.hide();

      // Reload bookings to reflect the change
      await loadBookings();

      // If details modal is open, refresh it to show download button
      if (currentBookingId) {
        // Small delay to ensure bookings are loaded
        setTimeout(() => {
          viewBookingDetails(currentBookingId);
        }, 500);
      }

      // Reset form
      removeFile();
    } else {
      showAlert("error", data.message || "Failed to upload receipt");
    }
  } catch (error) {
    console.error("Upload error:", error);
    showAlert("error", "Failed to upload receipt");
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-check"></i> Upload Receipt';
  }
}
// Open upload modal
function openUploadModal() {
  const uploadModal = new bootstrap.Modal(
    document.getElementById("uploadReceiptModal")
  );
  document.getElementById("uploadBookingId").value = currentBookingId;
  uploadModal.show();
}

// Open upload modal for specific booking
function openUploadModalForBooking(bookingId) {
  currentBookingId = bookingId;
  openUploadModal();
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
let cancelLetterFile = null;

function confirmCancelBooking(bookingId, status) {
  console.log(
    "confirmCancelBooking called with bookingId:",
    bookingId,
    "status:",
    status
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

  // Set booking ID and status in the hidden input
  document.getElementById("cancelBookingId").value = bookingId;
  document.getElementById("cancelBookingStatus").value = status;

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

    const response = await fetch(`/api/student/bookings/${bookingId}/cancel`, {
      method: "POST",
      credentials: "include",
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

// Open document upload modal for specific booking
function openDocumentUploadModal(bookingId) {
  currentBookingId = bookingId;
  document.getElementById("uploadBookingId").value = bookingId;

  // Reset file inputs and clear status badges first
  document.getElementById("permissionLetter").value = "";
  document.getElementById("requestLetter").value = "";
  document.getElementById("approvalLetter").value = "";

  // Hide all preview cards and reset status badges
  document.getElementById("permissionPreview").style.display = "none";
  document.getElementById("requestPreview").style.display = "none";
  document.getElementById("approvalPreview").style.display = "none";
  document.getElementById("permission_letter_status").innerHTML = "";
  document.getElementById("request_letter_status").innerHTML = "";
  document.getElementById("approval_letter_status").innerHTML = "";
  document.getElementById("submitDocumentsBtn").disabled = true;

  // Load existing files to show which ones are uploaded
  loadExistingDocuments(bookingId);

  const uploadModal = new bootstrap.Modal(
    document.getElementById("uploadDocumentsModal")
  );
  uploadModal.show();
} // Load existing documents to show upload status
async function loadExistingDocuments(bookingId) {
  try {
    const response = await fetch(`/api/student-bookings/${bookingId}/files`, {
      credentials: "include",
    });
    const data = await response.json();

    if (data.success && data.files) {
      // Map file types to their input and preview IDs
      const fileTypeMap = {
        permission_letter: {
          inputId: "permissionLetter",
          previewId: "permissionPreview",
        },
        request_letter: {
          inputId: "requestLetter",
          previewId: "requestPreview",
        },
        approval_letter: {
          inputId: "approvalLetter",
          previewId: "approvalPreview",
        },
      };

      // Mark which files are already uploaded
      data.files.forEach((file) => {
        // Update status badge
        const statusElement = document.getElementById(
          `${file.file_type}_status`
        );
        if (statusElement) {
          statusElement.innerHTML =
            '<i class="fas fa-check-circle text-success"></i> Uploaded';
        }

        // Show file preview for uploaded files
        const mapping = fileTypeMap[file.file_type];
        if (mapping) {
          const previewElement = document.getElementById(mapping.previewId);
          if (previewElement) {
            previewElement.querySelector(".file-name").textContent =
              file.filename;
            previewElement.querySelector(".file-size").textContent =
              file.size_formatted;
            previewElement.setAttribute("data-file-id", file.id);

            // Show download button for existing files
            const downloadBtn =
              previewElement.querySelector(".download-doc-btn");
            if (downloadBtn) {
              downloadBtn.style.display = "inline-flex";
            }

            previewElement.style.display = "flex";
          }
        }
      });
    }
  } catch (error) {
    console.error("Error loading existing documents:", error);
  }
}

// Handle individual file selection
function handleDocumentSelect(fileInput, previewId) {
  const file = fileInput.files[0];
  if (!file) return;

  const maxSize = 10 * 1024 * 1024; // 10MB
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
    fileInput.value = "";
    return;
  }

  if (file.size > maxSize) {
    showAlert("error", "File size exceeds 10MB limit.");
    fileInput.value = "";
    return;
  }

  // Show preview
  const preview = document.getElementById(previewId);
  preview.querySelector(".file-name").textContent = file.name;
  preview.querySelector(".file-size").textContent = formatFileSize(file.size);
  preview.style.display = "block";

  // Enable submit button if at least one file is selected
  checkSubmitButton();
}

// Check if submit button should be enabled
function checkSubmitButton() {
  // Check for newly selected files
  const hasPermission =
    document.getElementById("permissionLetter").files.length > 0;
  const hasRequest = document.getElementById("requestLetter").files.length > 0;
  const hasApproval =
    document.getElementById("approvalLetter").files.length > 0;

  // Enable submit if there are newly selected files
  const hasNewFiles = hasPermission || hasRequest || hasApproval;
  document.getElementById("submitDocumentsBtn").disabled = !hasNewFiles;
}

// Remove selected document
async function removeDocument(fileInputId, previewId) {
  const previewElement = document.getElementById(previewId);
  const fileId = previewElement.getAttribute("data-file-id");
  const bookingId = document.getElementById("uploadBookingId").value;

  // If this is an already uploaded file, delete it from server
  if (fileId) {
    try {
      const response = await fetch(
        `/api/student-bookings/${bookingId}/files/${fileId}`,
        {
          method: "DELETE",
          credentials: "include",
        }
      );
      const data = await response.json();

      if (data.success) {
        showAlert("success", "Document removed successfully");
      } else {
        showAlert("error", "Failed to remove document");
        return;
      }
    } catch (error) {
      console.error("Error removing document:", error);
      showAlert("error", "Failed to remove document");
      return;
    }
  }

  // Clear the file input and hide preview
  document.getElementById(fileInputId).value = "";
  previewElement.style.display = "none";
  previewElement.setAttribute("data-file-id", "");

  // Clear status badge
  const fileTypeMap = {
    permissionPreview: "permission_letter",
    requestPreview: "request_letter",
    approvalPreview: "approval_letter",
  };
  const fileType = fileTypeMap[previewId];
  if (fileType) {
    document.getElementById(`${fileType}_status`).innerHTML = "";
  }

  checkSubmitButton();
}

function downloadDocument(previewId) {
  const previewElement = document.getElementById(previewId);
  const fileId = previewElement.getAttribute("data-file-id");
  const bookingId = document.getElementById("uploadBookingId").value;

  if (fileId) {
    window.location.href = `/api/student/bookings/${bookingId}/files/${fileId}/download`;
  }
}

// Upload documents
async function uploadDocuments() {
  const bookingId = document.getElementById("uploadBookingId").value;
  const permissionFile = document.getElementById("permissionLetter").files[0];
  const requestFile = document.getElementById("requestLetter").files[0];
  const approvalFile = document.getElementById("approvalLetter").files[0];

  if (!permissionFile && !requestFile && !approvalFile) {
    showAlert("error", "Please select at least one document to upload");
    return;
  }

  const formData = new FormData();
  if (permissionFile) formData.append("files[]", permissionFile);
  if (requestFile) formData.append("files[]", requestFile);
  if (approvalFile) formData.append("files[]", approvalFile);

  const submitBtn = document.getElementById("submitDocumentsBtn");
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

  try {
    const response = await fetch(
      `/api/student-bookings/${bookingId}/upload-documents`,
      {
        method: "POST",
        credentials: "include",
        body: formData,
      }
    );

    const data = await response.json();

    if (data.success) {
      showAlert("success", "Documents uploaded successfully!");

      // Close modal
      const uploadModal = bootstrap.Modal.getInstance(
        document.getElementById("uploadDocumentsModal")
      );
      uploadModal.hide();

      // Reload bookings
      await loadBookings();

      // Refresh details if modal is open
      if (currentBookingId) {
        setTimeout(() => viewBookingDetails(currentBookingId), 500);
      }
    } else {
      showAlert("error", data.message || "Failed to upload documents");
    }
  } catch (error) {
    console.error("Upload error:", error);
    showAlert("error", "Failed to upload documents");
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-check"></i> Upload Documents';
  }
}

// Get document status HTML for booking details
function getDocumentStatus(booking) {
  return `
    <div class="detail-section">
      <h6 class="detail-section-title">
        <i class="fas fa-file-alt"></i> Required Documents
      </h6>
      <div id="documentStatusContainer">
        <div class="text-center py-3">
          <i class="fas fa-spinner fa-spin"></i> Loading documents...
        </div>
      </div>
      <div class="mt-3">
        <button class="btn btn-sm btn-primary" onclick="openDocumentUploadModal(${booking.id})">
          <i class="fas fa-upload"></i> Upload/Update Documents
        </button>
      </div>
    </div>
  `;
}

// Open reschedule modal for pending/confirmed bookings
async function openRescheduleModal(bookingId) {
  let booking = bookings.find((b) => b.id == bookingId);

  // If booking not found in local array, fetch from API
  if (!booking) {
    try {
      const response = await fetch(`/api/student/bookings/${bookingId}`, {
        credentials: "include",
      });
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

    const response = await fetch(
      `/api/student/bookings/${bookingId}/reschedule`,
      {
        method: "POST",
        credentials: "include",
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
      }
    );

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

// Load and display document status
async function loadDocumentStatus(bookingId) {
  try {
    const response = await fetch(`/api/student-bookings/${bookingId}/files`, {
      credentials: "include",
    });
    const data = await response.json();

    const container = document.getElementById("documentStatusContainer");

    if (data.success) {
      const fileTypes = {
        permission_letter: "Permission Letter",
        request_letter: "Request Letter",
        approval_letter: "Approval Letter",
      };

      let html = '<div class="document-list">';

      Object.keys(fileTypes).forEach((type) => {
        const file = data.files.find((f) => f.file_type === type);

        if (file) {
          html += `
            <div class="document-item">
              <div class="d-flex align-items-center">
                <i class="fas fa-check-circle text-success me-2"></i>
                <div class="flex-grow-1">
                  <strong>${fileTypes[type]}</strong>
                  <br>
                  <small class="text-muted">${file.filename} (${file.size_formatted})</small>
                </div>
                <a href="/api/student/bookings/${bookingId}/files/${file.id}/download" 
                   class="btn btn-sm btn-outline-primary me-2" 
                   target="_blank">
                  <i class="fas fa-download"></i>
                </a>
              </div>
            </div>
          `;
        } else {
          html += `
            <div class="document-item">
              <div class="d-flex align-items-center">
                <i class="fas fa-times-circle text-danger me-2"></i>
                <div class="flex-grow-1">
                  <strong>${fileTypes[type]}</strong>
                  <br>
                  <small class="text-muted">Not uploaded yet</small>
                </div>
              </div>
            </div>
          `;
        }
      });

      html += "</div>";
      container.innerHTML = html;
    }
  } catch (error) {
    console.error("Error loading documents:", error);
    document.getElementById("documentStatusContainer").innerHTML =
      '<div class="alert alert-warning">Failed to load documents</div>';
  }
}

// Confirm delete booking
function confirmDeleteBooking(bookingId) {
  document.getElementById("deleteBookingId").value = bookingId;
  const deleteModal = new bootstrap.Modal(
    document.getElementById("deleteBookingModal")
  );
  deleteModal.show();
}

// Delete booking
async function deleteBooking() {
  const bookingId = document.getElementById("deleteBookingId").value;

  if (!bookingId) {
    showAlert("error", "Booking ID is missing");
    return;
  }

  try {
    const confirmBtn = document.getElementById("confirmDeleteBtn");
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    confirmBtn.disabled = true;

    const response = await fetch(`/api/student/bookings/${bookingId}`, {
      method: "DELETE",
      credentials: "include",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const data = await response.json();

    if (data.success) {
      showAlert("success", "Booking deleted successfully");

      // Close modal
      const deleteModal = bootstrap.Modal.getInstance(
        document.getElementById("deleteBookingModal")
      );
      deleteModal.hide();

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
      showAlert("error", data.message || "Failed to delete booking");
    }
  } catch (error) {
    console.error("Error deleting booking:", error);
    showAlert("error", "Failed to delete booking. Please try again.");
  } finally {
    const confirmBtn = document.getElementById("confirmDeleteBtn");
    confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete Booking';
    confirmBtn.disabled = false;
  }
}
