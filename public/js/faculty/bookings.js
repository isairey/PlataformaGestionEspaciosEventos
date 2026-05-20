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

// Render bookings - FACULTY VERSION (handles both free and paid)
function renderBookings(bookingsList) {
  const container = document.getElementById("bookingsContainer");

  if (bookingsList.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h4>No Bookings Found</h4>
        <p>You haven't made any bookings yet. Start by booking a facility!</p>
        <a href="/faculty/book" class="btn btn-primary">
          <i class="fas fa-plus"></i> Make a Booking
        </a>
      </div>
    `;
    return;
  }

  const rows = bookingsList
    .map((booking) => {
      // Determine if booking is free (total_cost = 0) or paid
      const isFreeBooking = parseFloat(booking.total_cost || 0) === 0;
      const bookingTypeLabel = isFreeBooking
        ? '<span class="badge bg-success"><i class="fas fa-graduation-cap"></i> Free</span>'
        : '<span class="badge bg-warning text-dark"><i class="fas fa-dollar-sign"></i> Paid</span>';

      return `
        <tr>
          <td class="fw-bold">#${booking.id}</td>
          <td>${booking.facility_name}</td>
          <td>${booking.event_title}</td>
          <td>${bookingTypeLabel}</td>
          <td>${formatDate(booking.event_date)}</td>
          <td>${formatTime(booking.event_time)}</td>
          <td>${booking.duration}h</td>
          <td>${
            isFreeBooking
              ? "₱0"
              : "₱" + parseFloat(booking.total_cost || 0).toLocaleString()
          }</td>
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
                isFreeBooking &&
                (booking.status === "pending" || booking.status === "confirmed")
                  ? `<button class="btn btn-sm btn-upload" onclick="openDocumentUploadModal(${booking.id})" title="Upload Documents">
                      <i class="fas fa-upload"></i>
                    </button>`
                  : ""
              }
              ${
                !isFreeBooking &&
                (booking.status === "pending" || booking.status === "confirmed")
                  ? `<button class="btn btn-sm btn-upload" onclick="openUploadModalForBooking(${booking.id})" title="Upload Receipt">
                      <i class="fas fa-receipt"></i>
                    </button>`
                  : ""
              }
              ${
                !isFreeBooking && booking.status === "confirmed"
                  ? `<button class="btn btn-sm btn-info" onclick="openExtensionRequestModal(${booking.id})" title="Request Extension">
                      <i class="fas fa-clock"></i>
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
      `;
    })
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
              <th>Type</th>
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

// Receipt Upload Functions (for paid bookings)
async function openUploadModalForBooking(bookingId) {
  currentBookingId = bookingId;
  document.getElementById("receiptBookingId").value = bookingId;
  document.getElementById("receiptFile").value = "";
  document.getElementById("receiptPreview").style.display = "none";
  document.getElementById("submitReceiptBtn").disabled = true;

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
        ).href = `/api/user/bookings/${bookingId}/download-receipt`;

        document.getElementById("submitReceiptBtn").disabled = false;
        document.getElementById("submitReceiptBtn").innerHTML =
          '<i class="fas fa-sync"></i> Replace Receipt';
      } else {
        // Hide receipt status section if no receipt
        document.getElementById("receiptStatusSection").style.display = "none";
        document.getElementById("submitReceiptBtn").innerHTML =
          '<i class="fas fa-check"></i> Upload Receipt';
      }
    }
  } catch (error) {
    console.error("Error fetching booking details:", error);
  }

  const modal = new bootstrap.Modal(document.getElementById("uploadModal"));
  modal.show();
}

function handleReceiptSelect(input) {
  const file = input.files[0];
  const preview = document.getElementById("receiptPreview");
  const submitBtn = document.getElementById("submitReceiptBtn");

  if (file) {
    // Validate file size (10MB)
    if (file.size > 10 * 1024 * 1024) {
      showAlert("error", "File size must be less than 10MB");
      input.value = "";
      return;
    }

    // Show preview
    preview.style.display = "flex";
    preview.querySelector(".file-name").textContent = file.name;
    preview.querySelector(".file-size").textContent = `${(
      file.size /
      1024 /
      1024
    ).toFixed(2)} MB`;
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-check"></i> Upload Receipt';
  } else {
    preview.style.display = "none";
    submitBtn.disabled = true;
  }
}

function removeReceipt() {
  document.getElementById("receiptFile").value = "";
  document.getElementById("receiptPreview").style.display = "none";
  document.getElementById("submitReceiptBtn").disabled = true;
}

function downloadReceipt(event) {
  event.preventDefault();
  const bookingId = document.getElementById("receiptBookingId").value;
  window.location.href = `/api/user/bookings/${bookingId}/download-receipt`;
}

async function uploadReceipt() {
  const bookingId = document.getElementById("receiptBookingId").value;
  const fileInput = document.getElementById("receiptFile");
  const file = fileInput.files[0];

  if (!file) {
    showAlert("error", "Please select a file to upload");
    return;
  }

  const submitBtn = document.getElementById("submitReceiptBtn");
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

  try {
    // Check if there's an existing receipt to delete first
    const booking = bookings.find((b) => b.id === bookingId);
    if (booking && booking.receipt_uploaded) {
      // Delete existing receipt
      const deleteResponse = await fetch(
        `/api/user/bookings/${bookingId}/delete-receipt`,
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
    formData.append("receipt", file);

    const response = await fetch(
      `/api/user/bookings/${bookingId}/upload-receipt`,
      {
        method: "POST",
        body: formData,
      }
    );

    const result = await response.json();

    if (result.success) {
      showAlert("success", "Receipt uploaded successfully!");

      // Close modal
      bootstrap.Modal.getInstance(
        document.getElementById("uploadModal")
      ).hide();

      // Reload bookings to get updated receipt data
      await loadBookings();

      // Refresh modal display after a short delay to show new receipt
      setTimeout(() => {
        openUploadModalForBooking(bookingId);
      }, 500);
    } else {
      showAlert("error", result.message || "Failed to upload receipt");
    }
  } catch (error) {
    console.error("Error uploading receipt:", error);
    showAlert("error", "An error occurred while uploading");
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-check"></i> Upload Receipt';
  }
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

// Open upload modal
function openUploadModal() {
  const uploadModal = new bootstrap.Modal(
    document.getElementById("uploadModal")
  );
  document.getElementById("receiptBookingId").value = currentBookingId;
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
                <a href="/api/user/bookings/${booking.id}/download-receipt" 
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
  console.log("confirmCancelBooking called with:", {
    bookingId,
    status,
    totalCost,
  });

  // Find the booking details
  const booking = bookings.find((b) => b.id == bookingId); // Use == for loose comparison

  if (!booking) {
    console.error("Booking not found in bookings array:", bookingId);
    console.log("Available bookings:", bookings);
    showAlert("error", "Booking not found");
    return;
  }

  // Set booking ID in the hidden input
  document.getElementById("cancelBookingId").value = bookingId;

  // Reset form fields
  document.getElementById("cancelReason").value = "";
  document.getElementById("cancelNotes").value = "";
  removeCancelLetter();

  // Show/hide refund policy based on booking cost
  const refundPolicySection = document.getElementById("refundPolicySection");
  if (refundPolicySection) {
    refundPolicySection.style.display = totalCost > 0 ? "block" : "none";
  }

  // Update submit button state
  updateCancelSubmitButtonState();

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
    hasFile: !!cancelLetterFile,
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

  // Create FormData for file upload
  const formData = new FormData();
  formData.append("reason", reason);
  formData.append("notes", notes);
  if (cancelLetterFile) {
    formData.append("cancel_letter", cancelLetterFile);
  }

  try {
    // Show loading state
    const confirmBtn = document.getElementById("confirmCancelBtn");
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
    confirmBtn.disabled = true;

    const response = await fetch(`/api/user/bookings/${bookingId}/cancel`, {
      method: "POST",
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

  // Reset form
  document.getElementById("permissionLetter").value = "";
  document.getElementById("requestLetter").value = "";
  document.getElementById("approvalLetter").value = "";
  document.getElementById("permissionPreview").style.display = "none";
  document.getElementById("requestPreview").style.display = "none";
  document.getElementById("approvalPreview").style.display = "none";
  document.getElementById("submitDocumentsBtn").disabled = true;

  // Load existing files to show which ones are uploaded
  loadExistingDocuments(bookingId);

  const uploadModal = new bootstrap.Modal(
    document.getElementById("uploadDocumentsModal")
  );
  uploadModal.show();
}

// Load existing documents to show upload status
async function loadExistingDocuments(bookingId) {
  try {
    const response = await fetch(`/api/student-bookings/${bookingId}/files`, {
      credentials: "include",
    });
    const data = await response.json();

    if (data.success && data.files) {
      // Mark which files are already uploaded and show file info
      data.files.forEach((file) => {
        const statusElement = document.getElementById(
          `${file.file_type}_status`
        );
        if (statusElement) {
          statusElement.innerHTML =
            '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Uploaded</span>';
        }

        // Show the uploaded file information in the preview area
        let previewId = "";
        if (file.file_type === "permission_letter") {
          previewId = "permissionPreview";
        } else if (file.file_type === "request_letter") {
          previewId = "requestPreview";
        } else if (file.file_type === "approval_letter") {
          previewId = "approvalPreview";
        }

        if (previewId) {
          const preview = document.getElementById(previewId);
          if (preview) {
            preview.style.display = "flex";
            preview.setAttribute("data-file-id", file.id);
            preview.querySelector(".file-name").textContent =
              file.file_name || "Previously uploaded file";
            preview.querySelector(".file-size").textContent = file.uploaded_at
              ? `Uploaded: ${new Date(file.uploaded_at).toLocaleDateString()}`
              : "Previously uploaded";

            // Show download button for existing files
            const downloadBtn = preview.querySelector(".download-doc-btn");
            if (downloadBtn) {
              downloadBtn.style.display = "inline-flex";
            }

            // Update the remove button to indicate it's replacing
            const removeBtn = preview.querySelector(
              "button:not(.download-doc-btn)"
            );
            if (removeBtn) {
              removeBtn.innerHTML = '<i class="fas fa-times"></i>';
              removeBtn.title = "Remove this file";
            }

            // Add a visual indicator that this is an existing file
            preview.style.background = "#e8f5e9";
            preview.style.border = "2px solid #4caf50";
          }
        }
      });

      // Update the info message
      const uploadedCount = data.files.length;
      if (uploadedCount > 0) {
        const alertDiv = document.querySelector(
          "#uploadDocumentsModal .alert-info"
        );
        if (alertDiv) {
          alertDiv.className = "alert alert-success";
          alertDiv.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <strong>${uploadedCount} of 3</strong> documents have been uploaded.
            You can upload missing documents or replace existing ones.
          `;
        }
      }
    }
  } catch (error) {
    console.error("Error loading existing documents:", error);
    // Even if loading fails, allow user to upload new documents
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

  // Show preview with new file info
  const preview = document.getElementById(previewId);
  preview.querySelector(".file-name").textContent = file.name;
  preview.querySelector(".file-size").textContent = formatFileSize(file.size);
  preview.style.display = "flex";

  // Reset styling to indicate this is a NEW file to be uploaded
  preview.style.background = "#f0f4ff";
  preview.style.border = "2px solid #667eea";

  // Reset button icon to remove/cancel
  const removeBtn = preview.querySelector("button");
  if (removeBtn) {
    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
    removeBtn.title = "Remove this file";
  }

  // Enable submit button if at least one file is selected
  checkSubmitButton();
}

// Check if submit button should be enabled
function checkSubmitButton() {
  const hasPermission =
    document.getElementById("permissionLetter").files.length > 0;
  const hasRequest = document.getElementById("requestLetter").files.length > 0;
  const hasApproval =
    document.getElementById("approvalLetter").files.length > 0;

  document.getElementById("submitDocumentsBtn").disabled = !(
    hasPermission ||
    hasRequest ||
    hasApproval
  );
}

// Remove selected document
function removeDocument(fileInputId, previewId) {
  document.getElementById(fileInputId).value = "";
  document.getElementById(previewId).style.display = "none";
  checkSubmitButton();
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

// Download document for free bookings
function downloadDocument(previewId) {
  const previewElement = document.getElementById(previewId);
  const fileId = previewElement.getAttribute("data-file-id");
  const bookingId = document.getElementById("uploadBookingId").value;

  if (fileId) {
    window.location.href = `/api/student-bookings/${bookingId}/files/${fileId}/download`;
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

// Load and display document status
async function loadDocumentStatus(bookingId) {
  try {
    const response = await fetch(`/api/student-bookings/${bookingId}/files`);
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
                <a href="/api/student-bookings/${bookingId}/download/${file.id}" 
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

// File handling functions for cancellation letter
function handleCancelLetterSelect(fileInput) {
  if (fileInput.files && fileInput.files.length > 0) {
    const file = fileInput.files[0];

    // Validate file type
    const allowedTypes = ["application/pdf", "image/jpeg", "image/png"];
    if (!allowedTypes.includes(file.type)) {
      showAlert("error", "Please upload a PDF, JPG, or PNG file");
      fileInput.value = "";
      return;
    }

    // Validate file size (10MB max)
    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
      showAlert("error", "File size must not exceed 10MB");
      fileInput.value = "";
      return;
    }

    // Store the file
    cancelLetterFile = file;

    // Show file preview
    const fileName = document.getElementById("cancelLetterFileName");
    if (fileName) {
      fileName.textContent = file.name;
      document.getElementById("cancelLetterPreview").style.display = "block";
    }

    // Update submit button state
    updateCancelSubmitButtonState();
  }
}

function removeCancelLetter() {
  cancelLetterFile = null;

  // Clear file input
  const fileInput = document.getElementById("cancelLetterInput");
  if (fileInput) {
    fileInput.value = "";
  }

  // Hide preview
  const preview = document.getElementById("cancelLetterPreview");
  if (preview) {
    preview.style.display = "none";
  }

  // Update submit button state
  updateCancelSubmitButtonState();
}

function updateCancelSubmitButtonState() {
  const reason = document.getElementById("cancelReason").value;
  const hasFile = !!cancelLetterFile;
  const submitBtn = document.getElementById("confirmCancelBtn");

  if (submitBtn) {
    submitBtn.disabled = !reason || !hasFile;
  }
}

// Open reschedule modal for pending/confirmed bookings (FREE bookings only)
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

// Open reschedule modal for pending/confirmed bookings
async function openRescheduleModal(bookingId) {
  let booking = bookings.find((b) => b.id == bookingId);

  // If booking not found in local array, fetch from API
  if (!booking) {
    try {
      const response = await fetch(`/api/user/bookings/${bookingId}`);
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
  const notes = document.getElementById("rescheduleNotes").value;

  if (!reason || !newDate || !newTime) {
    showAlert("error", "Please fill in all required fields");
    return;
  }

  // Show loading state
  const submitBtn = document.getElementById("submitRescheduleBtn");
  const originalText = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

  try {
    const response = await fetch(`/api/user/bookings/${bookingId}/reschedule`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
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
      showAlert("success", "Reschedule request submitted successfully");

      // Close modal
      const rescheduleModal = bootstrap.Modal.getInstance(
        document.getElementById("rescheduleBookingModal")
      );
      if (rescheduleModal) {
        rescheduleModal.hide();
      }

      // Reload bookings
      await loadBookings();
    } else {
      showAlert("error", data.message || "Failed to submit reschedule request");
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  } catch (error) {
    console.error("Error submitting reschedule request:", error);
    showAlert(
      "error",
      "Failed to submit reschedule request. Please try again."
    );
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}
