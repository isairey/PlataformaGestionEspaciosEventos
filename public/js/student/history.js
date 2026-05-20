let allBookings = [];
let filteredBookings = [];

document.addEventListener("DOMContentLoaded", function () {
  loadBookingHistory();
});

// Load booking history
async function loadBookingHistory() {
  try {
    const response = await fetch("/user/bookings/list");
    const data = await response.json();

    if (data.success) {
      allBookings = data.bookings;
      filteredBookings = allBookings;
      updateStatistics();
      renderTimeline(filteredBookings);
    } else {
      showAlert("error", data.message || "Failed to load booking history");
    }
  } catch (error) {
    console.error("Error loading history:", error);
    showAlert("error", "Failed to load booking history");
  }
}

// Update statistics
function updateStatistics() {
  const completed = allBookings.filter((b) => b.status === "completed").length;
  const cancelled = allBookings.filter((b) => b.status === "cancelled").length;
  const total = allBookings.length;
  const totalSpent = allBookings
    .filter((b) => b.status !== "cancelled")
    .reduce((sum, b) => sum + parseFloat(b.total_cost), 0);

  document.getElementById("totalCompleted").textContent = completed;
  document.getElementById("totalCancelled").textContent = cancelled;
  document.getElementById("totalBookings").textContent = total;
  document.getElementById("totalSpent").textContent =
    "₱" + totalSpent.toLocaleString();
}

// Render timeline
function renderTimeline(bookings) {
  const container = document.getElementById("historyTimeline");

  if (bookings.length === 0) {
    container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h4>No Booking History</h4>
                <p class="text-muted">You haven't made any bookings yet.</p>
                <a href="/facilities" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> Make Your First Booking
                </a>
            </div>
        `;
    return;
  }

  // Sort by event date (newest first)
  const sortedBookings = bookings.sort(
    (a, b) => new Date(b.event_date) - new Date(a.event_date)
  );

  const timelineHTML = `
        <div class="timeline">
            ${sortedBookings
              .map((booking) => createTimelineItem(booking))
              .join("")}
        </div>
    `;

  container.innerHTML = timelineHTML;
}

// Create timeline item
function createTimelineItem(booking) {
  const statusIcon = getStatusIcon(booking.status);
  const markerClass =
    booking.status === "completed"
      ? "completed"
      : booking.status === "cancelled"
      ? "cancelled"
      : "";

  return `
        <div class="timeline-item">
            <div class="timeline-marker ${markerClass}">
                ${statusIcon}
            </div>
            <div class="timeline-content">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="timeline-date">
                            <i class="fas fa-calendar"></i> ${formatDate(
                              booking.event_date
                            )}
                        </div>
                        <div class="timeline-title">${booking.event_title}</div>
                    </div>
                    <span class="booking-status status-${booking.status}">
                        ${formatStatus(booking.status)}
                    </span>
                </div>
                
                <div class="timeline-details">
                    <div class="detail-badge">
                        <i class="fas fa-building"></i>
                        <span>${booking.facility_name}</span>
                    </div>
                    <div class="detail-badge">
                        <i class="fas fa-clock"></i>
                        <span>${formatTime(booking.event_time)}</span>
                    </div>
                    <div class="detail-badge">
                        <i class="fas fa-hourglass-half"></i>
                        <span>${booking.duration} hours</span>
                    </div>
                </div>

                ${
                  booking.status === "cancelled" && booking.decline_reason
                    ? `
                    <div class="alert alert-danger mt-3 mb-0">
                        <i class="fas fa-exclamation-circle"></i> 
                        <strong>Cancelled:</strong> ${formatDeclineReason(
                          booking.decline_reason
                        )}
                    </div>
                `
                    : ""
                }

                <div class="mt-3">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewHistoryDetails(${
                      booking.id
                    })">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    ${
                      booking.status === "confirmed"
                        ? `
                        <a href="/dashboard/attendance/${booking.id}" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-qrcode"></i> QR Code
                        </a>
                    `
                        : ""
                    }
                </div>
            </div>
        </div>
    `;
}

// View history details
async function viewHistoryDetails(bookingId) {
  try {
    const response = await fetch(`/user/bookings/details/${bookingId}`);
    const data = await response.json();

    if (data.success) {
      const booking = data.booking;
      const modalBody = document.getElementById("historyDetailsContent");

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
        document.getElementById("historyDetailsModal")
      );
      modal.show();
    } else {
      showAlert("error", data.message || "Failed to load booking details");
    }
  } catch (error) {
    console.error("Error loading details:", error);
    showAlert("error", "Failed to load booking details");
  }
}

// Apply filters
function applyFilters() {
  const status = document.getElementById("statusFilter").value;
  const dateFrom = document.getElementById("dateFromFilter").value;
  const dateTo = document.getElementById("dateToFilter").value;
  const search = document.getElementById("searchFilter").value.toLowerCase();

  filteredBookings = allBookings.filter((booking) => {
    // Status filter
    if (status && booking.status !== status) return false;

    // Date range filter
    if (dateFrom && booking.event_date < dateFrom) return false;
    if (dateTo && booking.event_date > dateTo) return false;

    // Search filter
    if (search) {
      const searchableText = `
                ${booking.facility_name}
                ${booking.event_title}
                ${booking.client_name}
            `.toLowerCase();
      if (!searchableText.includes(search)) return false;
    }

    return true;
  });

  renderTimeline(filteredBookings);
}

// Reset filters
function resetFilters() {
  document.getElementById("statusFilter").value = "";
  document.getElementById("dateFromFilter").value = "";
  document.getElementById("dateToFilter").value = "";
  document.getElementById("searchFilter").value = "";

  filteredBookings = allBookings;
  renderTimeline(filteredBookings);
}

// Export history (placeholder)
function exportHistory() {
  showAlert("info", "Export feature coming soon!");
}

// Helper functions
function getStatusIcon(status) {
  switch (status) {
    case "completed":
      return '<i class="fas fa-check"></i>';
    case "cancelled":
      return '<i class="fas fa-times"></i>';
    default:
      return '<i class="fas fa-circle"></i>';
  }
}

function formatStatus(status) {
  const statusMap = {
    pending: "Pending",
    confirmed: "Confirmed",
    cancelled: "Cancelled",
    completed: "Completed",
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

function formatDeclineReason(reason) {
  const reasonMap = {
    "schedule-conflict": "Schedule Conflict",
    "facility-unavailable": "Facility Unavailable",
    "policy-violation": "Policy Violation",
    "incomplete-requirements": "Incomplete Requirements",
    "no-longer-needed": "No Longer Needed",
    "found-alternative": "Found Alternative Venue",
    "budget-constraints": "Budget Constraints",
    "personal-reasons": "Personal Reasons",
    other: "Other",
  };
  return reasonMap[reason] || reason;
}

function showAlert(type, message) {
  const alertContainer = document.getElementById("alertContainer");
  const alertClass =
    type === "success"
      ? "alert-success"
      : type === "info"
      ? "alert-info"
      : "alert-danger";
  const icon =
    type === "success"
      ? "check-circle"
      : type === "info"
      ? "info-circle"
      : "exclamation-circle";

  const alert = document.createElement("div");
  alert.className = `alert ${alertClass} alert-dismissible fade show`;
  alert.innerHTML = `
        <i class="fas fa-${icon}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

  alertContainer.appendChild(alert);

  setTimeout(() => {
    alert.remove();
  }, 5000);
}
