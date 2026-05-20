// Attendance Management JavaScript

let allEvents = [];
let currentEventId = null;
let currentEventGuests = [];

// Initialize page on load
document.addEventListener("DOMContentLoaded", function () {
  loadEvents();
  setupDateFilters();
});

// Load all confirmed events with attendance data
async function loadEvents() {
  showLoading();

  try {
    const response = await fetch("/api/bookings/list");
    const result = await response.json();

    if (result.success) {
      // Filter only confirmed bookings
      allEvents = result.bookings.filter(
        (booking) =>
          booking.status === "confirmed" || booking.status === "completed",
      );

      // Load attendance stats for each event
      await loadAttendanceStats();

      displayEvents(allEvents);
      updateOverallStatistics();
    } else {
      showError(
        "Failed to load events: " + (result.message || "Unknown error"),
      );
    }
  } catch (error) {
    console.error("Error loading events:", error);
    showError("Failed to load events. Please try again.");
  } finally {
    hideLoading();
  }
}

// Load attendance statistics for all events
async function loadAttendanceStats() {
  try {
    // First, fetch all events once to map booking IDs to event IDs
    const eventsResponse = await fetch("/api/events/list");
    const eventsResult = await eventsResponse.json();

    const eventIdMap = {};
    if (eventsResult.success && eventsResult.events) {
      eventsResult.events.forEach((event) => {
        if (event.booking_id) {
          eventIdMap[event.booking_id] = event.id;
        }
      });
    }

    // Now fetch guests for each booking
    const promises = allEvents.map(async (booking) => {
      try {
        // Store event ID if it exists
        if (eventIdMap[booking.id]) {
          booking.event_id = eventIdMap[booking.id];
        }

        // Use booking ID to get guests directly
        const response = await fetch(`/api/bookings/${booking.id}/guests`);
        const result = await response.json();

        if (result.success) {
          const guests = result.guests || [];
          const total = guests.length;
          const attended = guests.filter((g) => g.attended == 1).length;
          const pending = total - attended;
          const rate = total > 0 ? ((attended / total) * 100).toFixed(1) : 0;

          booking.attendanceStats = {
            total: total,
            attended: attended,
            pending: pending,
            attendance_rate: rate,
          };
        } else {
          booking.attendanceStats = {
            total: 0,
            attended: 0,
            pending: 0,
            attendance_rate: 0,
          };
        }
      } catch (error) {
        console.error(`Error loading stats for booking ${booking.id}:`, error);
        booking.attendanceStats = {
          total: 0,
          attended: 0,
          pending: 0,
          attendance_rate: 0,
        };
      }
    });

    await Promise.all(promises);
  } catch (error) {
    console.error("Error in loadAttendanceStats:", error);
  }
}

// Display events in the table
function displayEvents(events) {
  const tbody = document.getElementById("eventsTableBody");
  const table = document.getElementById("eventsTable");
  const noDataMessage = document.getElementById("noEventsMessage");

  tbody.innerHTML = "";

  if (events.length === 0) {
    table.style.display = "none";
    noDataMessage.style.display = "block";
    return;
  }

  table.style.display = "table";
  noDataMessage.style.display = "none";

  events.forEach((event, index) => {
    const stats = event.attendanceStats || {
      total: 0,
      attended: 0,
      pending: 0,
      attendance_rate: 0,
    };

    const eventDate = new Date(event.event_date);
    const eventDateTime = formatDateTime(event.event_date, event.event_time);
    const eventStatus = getEventStatus(event.event_date);

    const attendanceRate = parseFloat(stats.attendance_rate) || 0;
    const rateClass =
      attendanceRate >= 80 ? "high" : attendanceRate >= 50 ? "medium" : "low";

    const row = `
            <tr>
                <td>#${event.id}</td>
                <td><strong>${escapeHtml(
                  event.event_title || "N/A",
                )}</strong></td>
                <td>${escapeHtml(event.facility_name || "N/A")}</td>
                <td>${escapeHtml(event.client_name || "N/A")}</td>
                <td>${eventDateTime}</td>
                <td><strong>${stats.total}</strong></td>
                <td><span class="guest-status attended">${
                  stats.attended
                }</span></td>
                <td><span class="guest-status pending">${
                  stats.pending
                }</span></td>
                <td><span class="attendance-rate ${rateClass}">${attendanceRate.toFixed(
                  1,
                )}%</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-action btn-view" onclick="viewGuests(${
                          event.id
                        })">
                            <i>👁️</i> View
                        </button>
                        <button class="btn-action btn-download" onclick="exportSingleEvent(${
                          event.id
                        })">
                            <i>📥</i> Export
                        </button>
                    </div>
                </td>
            </tr>
        `;

    tbody.innerHTML += row;
  });
}

// View guests for an event
async function viewGuests(bookingId) {
  currentEventId = bookingId;

  try {
    // First try to find in allEvents
    let booking = allEvents.find((e) => e.id === bookingId);

    // If not found in allEvents, fetch directly from API
    if (!booking) {
      console.warn(
        `Booking ${bookingId} not found in allEvents, fetching from API...`,
      );
      const response = await fetch(`/api/bookings/detail/${bookingId}`);
      const result = await response.json();

      if (!result.success || !result.booking) {
        showError("Booking not found");
        return;
      }

      booking = result.booking;
    }

    // Set event details in modal
    document.getElementById("modalEventTitle").textContent =
      booking.event_title || "N/A";
    document.getElementById("modalFacility").textContent =
      booking.facility_name || "N/A";
    document.getElementById("modalClient").textContent =
      booking.client_name || "N/A";
    document.getElementById("modalDateTime").textContent = formatDateTime(
      booking.event_date,
      booking.event_time,
    );

    // Load guests first to calculate fresh stats
    await loadEventGuests(bookingId);

    // Calculate statistics from loaded guests
    const guests = currentEventGuests || [];
    const total = guests.length;
    const attended = guests.filter((g) => g.attended == 1).length;
    const pending = total - attended;
    const rate = total > 0 ? ((attended / total) * 100).toFixed(1) : 0;

    // Set statistics in modal
    document.getElementById("modalTotalGuests").textContent = total;
    document.getElementById("modalAttended").textContent = attended;
    document.getElementById("modalPending").textContent = pending;
    document.getElementById("modalAttendanceRate").textContent = rate + "%";

    // Show modal
    document.getElementById("guestModal").style.display = "block";
  } catch (error) {
    console.error("Error viewing guests:", error);
    showError("Failed to load event details. Please try again.");
  }
}

// Load guests for an event
async function loadEventGuests(bookingId) {
  try {
    const response = await fetch(`/api/bookings/${bookingId}/guests`);
    const result = await response.json();

    if (result.success) {
      currentEventGuests = result.guests;
      displayGuests(currentEventGuests);
    } else {
      showError(
        "Failed to load guests: " + (result.message || "Unknown error"),
      );
    }
  } catch (error) {
    console.error("Error loading guests:", error);
    showError("Failed to load guests. Please try again.");
  }
}

// Display guests in the modal table
function displayGuests(guests) {
  const tbody = document.getElementById("guestTableBody");
  tbody.innerHTML = "";

  if (guests.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="7" class="text-center">No guests registered for this event</td></tr>';
    return;
  }

  guests.forEach((guest, index) => {
    const statusClass = guest.attended ? "attended" : "pending";
    const statusText = guest.attended ? "Attended" : "Pending";
    const checkInTime = guest.attendance_time
      ? formatTime(guest.attendance_time)
      : "-";

    const row = `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${escapeHtml(
                  guest.guest_name || "N/A",
                )}</strong></td>
                <td>${escapeHtml(guest.guest_email || "N/A")}</td>
                <td>${escapeHtml(guest.guest_phone || "N/A")}</td>
                <td>
                    <a href="#" class="qr-link" onclick="viewQRCode(${
                      guest.id
                    }, '${escapeHtml(guest.guest_name)}', '${
                      guest.qr_code
                    }'); return false;">
                        ${guest.qr_code}
                    </a>
                </td>
                <td><span class="guest-status ${statusClass}">${statusText}</span></td>
                <td>${checkInTime}</td>
            </tr>
        `;

    tbody.innerHTML += row;
  });
}

// View QR Code
function viewQRCode(guestId, guestName, qrCode) {
  document.getElementById("qrGuestName").textContent = guestName;
  document.getElementById("qrCodeValue").textContent = qrCode;

  // Load QR code image
  const qrContainer = document.getElementById("qrCodeContainer");
  qrContainer.innerHTML = `<img src="/api/guests/${guestId}/qr-url" alt="QR Code" onerror="this.src='data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"200\" height=\"200\"><rect width=\"200\" height=\"200\" fill=\"%23f0f0f0\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\".3em\" fill=\"%23999\" font-size=\"14\">QR Code Not Available</text></svg>'" />`;

  // Store current guest ID for download
  window.currentQRGuestId = guestId;

  document.getElementById("qrModal").style.display = "block";
}

// Download QR Code
async function downloadQRCode() {
  if (!window.currentQRGuestId) {
    showError("No QR code selected");
    return;
  }

  try {
    window.location.href = `/api/guests/${window.currentQRGuestId}/qr-download`;
  } catch (error) {
    console.error("Error downloading QR code:", error);
    showError("Failed to download QR code");
  }
}

// Export single event attendance
async function exportSingleEvent(bookingId) {
  try {
    // Fetch guests data directly for the booking
    const response = await fetch(`/api/bookings/${bookingId}/guests`);
    const result = await response.json();

    if (!result.success) {
      showError("Failed to fetch guest data");
      return;
    }

    const guests = result.guests || [];

    if (guests.length === 0) {
      showError("No guests found for this event");
      return;
    }

    // Find booking details
    let booking = allEvents.find((e) => e.id === bookingId);

    if (!booking) {
      const bookingResponse = await fetch(`/api/bookings/detail/${bookingId}`);
      const bookingResult = await bookingResponse.json();

      if (!bookingResult.success || !bookingResult.booking) {
        showError("Booking not found");
        return;
      }

      booking = bookingResult.booking;
    }

    // Export using booking ID directly
    window.location.href = `/api/bookings/${bookingId}/export-attendance`;
    showSuccess("Downloading attendance report...");
  } catch (error) {
    console.error("Error exporting event:", error);
    showError("Failed to export attendance report");
  }
}

// Export event attendance from modal
async function exportEventAttendance(type = "all") {
  if (!currentEventId) {
    showError("No event selected");
    return;
  }

  try {
    let booking = allEvents.find((e) => e.id === currentEventId);

    // If not found in allEvents, fetch directly from API
    if (!booking) {
      console.warn(
        `Booking ${currentEventId} not found in allEvents, fetching from API...`,
      );
      const response = await fetch(`/api/bookings/detail/${currentEventId}`);
      const result = await response.json();

      if (!result.success || !result.booking) {
        showError("Booking not found");
        return;
      }

      booking = result.booking;
    }

    if (!booking.event_id) {
      showError("Event has not been created yet for this booking");
      return;
    }

    let url = `/api/events/${booking.event_id}/attendance-export`;

    if (type !== "all") {
      url += `?filter=${type}`;
    }

    window.location.href = url;
    showSuccess("Downloading attendance report...");
  } catch (error) {
    console.error("Error exporting attendance:", error);
    showError("Failed to export attendance report");
  }
}

// Export all events attendance
async function exportAllAttendance() {
  if (allEvents.length === 0) {
    showError("No events to export");
    return;
  }

  try {
    // Use the new export all endpoint that generates proper Excel file
    window.location.href = `/api/bookings/export-all-attendance`;
    showSuccess("Downloading all attendance reports...");
  } catch (error) {
    console.error("Error exporting all events:", error);
    showError("Failed to export all attendance reports");
  }
}

// Update overall statistics
function updateOverallStatistics() {
  let totalEvents = allEvents.length;
  let totalGuests = 0;
  let totalAttended = 0;

  allEvents.forEach((event) => {
    const stats = event.attendanceStats || {};
    totalGuests += parseInt(stats.total) || 0;
    totalAttended += parseInt(stats.attended) || 0;
  });

  const overallRate =
    totalGuests > 0 ? ((totalAttended / totalGuests) * 100).toFixed(1) : 0;

  document.getElementById("totalEventsCount").textContent = totalEvents;
  document.getElementById("totalGuestsCount").textContent = totalGuests;
  document.getElementById("totalAttendedCount").textContent = totalAttended;
  document.getElementById("overallAttendanceRate").textContent =
    overallRate + "%";
}

// Setup date filters
function setupDateFilters() {
  const today = new Date();
  const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);

  document.getElementById("startDate").valueAsDate = thirtyDaysAgo;
  document.getElementById("endDate").valueAsDate = today;
}

// Apply date filter
function applyDateFilter() {
  const startDate = document.getElementById("startDate").value;
  const endDate = document.getElementById("endDate").value;

  if (!startDate || !endDate) {
    showError("Please select both start and end dates");
    return;
  }

  const filtered = allEvents.filter((event) => {
    const eventDate = event.event_date;
    return eventDate >= startDate && eventDate <= endDate;
  });

  displayEvents(filtered);
}

// Clear filters
function clearFilters() {
  document.getElementById("searchInput").value = "";
  document.getElementById("statusFilter").value = "";
  setupDateFilters();
  displayEvents(allEvents);
}

// Filter events by search and status
function filterEvents() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();
  const statusFilter = document.getElementById("statusFilter").value;

  let filtered = allEvents;

  // Apply search filter
  if (searchTerm) {
    filtered = filtered.filter((event) => {
      return (
        (event.event_title &&
          event.event_title.toLowerCase().includes(searchTerm)) ||
        (event.client_name &&
          event.client_name.toLowerCase().includes(searchTerm)) ||
        (event.facility_name &&
          event.facility_name.toLowerCase().includes(searchTerm)) ||
        (event.id && event.id.toString().includes(searchTerm))
      );
    });
  }

  // Apply status filter
  if (statusFilter) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    filtered = filtered.filter((event) => {
      const eventDate = new Date(event.event_date);
      eventDate.setHours(0, 0, 0, 0);

      if (statusFilter === "upcoming") {
        return eventDate > today;
      } else if (statusFilter === "ongoing") {
        return eventDate.getTime() === today.getTime();
      } else if (statusFilter === "completed") {
        return eventDate < today;
      }
      return true;
    });
  }

  displayEvents(filtered);
}

// Get event status based on date
function getEventStatus(eventDate) {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const eDate = new Date(eventDate);
  eDate.setHours(0, 0, 0, 0);

  if (eDate > today) {
    return "upcoming";
  } else if (eDate.getTime() === today.getTime()) {
    return "ongoing";
  } else {
    return "completed";
  }
}

// Format date and time
function formatDateTime(date, time) {
  if (!date) return "N/A";

  const eventDate = new Date(date);
  const options = { year: "numeric", month: "short", day: "numeric" };
  const formattedDate = eventDate.toLocaleDateString("en-US", options);

  if (time) {
    return `${formattedDate} at ${time}`;
  }

  return formattedDate;
}

// Format time
function formatTime(datetime) {
  if (!datetime) return "N/A";

  const date = new Date(datetime);
  return date.toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: true,
  });
}

// Close guest modal
function closeGuestModal() {
  document.getElementById("guestModal").style.display = "none";
  currentEventId = null;
  currentEventGuests = [];
}

// Close QR modal
function closeQRModal() {
  document.getElementById("qrModal").style.display = "none";
  window.currentQRGuestId = null;
}

// Close modals when clicking outside
window.onclick = function (event) {
  const guestModal = document.getElementById("guestModal");
  const qrModal = document.getElementById("qrModal");

  if (event.target === guestModal) {
    closeGuestModal();
  }
  if (event.target === qrModal) {
    closeQRModal();
  }
};

// Utility functions
function showLoading() {
  document.getElementById("loadingSpinner").style.display = "block";
  document.getElementById("eventsTable").style.display = "none";
  document.getElementById("noEventsMessage").style.display = "none";
}

function hideLoading() {
  document.getElementById("loadingSpinner").style.display = "none";
}

function showError(message) {
  alert("Error: " + message);
}

function showSuccess(message) {
  console.log("Success: " + message);
}

function escapeHtml(text) {
  if (!text) return "";
  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  };
  return text.replace(/[&<>"']/g, (m) => map[m]);
}

// Sidebar toggle function (if not already defined)
function toggleSidebar() {
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  sidebar.classList.toggle("collapsed");
  mainContent.classList.toggle("expanded");
}

// Dropdown toggle function (if not already defined)
function toggleDropdown(event) {
  event.preventDefault();
  const dropdown = event.target.closest(".dropdown");
  dropdown.classList.toggle("open");
}
