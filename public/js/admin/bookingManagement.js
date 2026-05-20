let currentBookingId = null;
let bookingsData = [];

/**
 * Check if a booking date has passed
 */
function isBookingExpired(eventDate) {
  const today = new Date();
  today.setHours(0, 0, 0, 0); // Reset time to midnight for accurate comparison

  const bookingDate = new Date(eventDate);
  bookingDate.setHours(0, 0, 0, 0);

  return bookingDate < today;
}

// Initialize page
document.addEventListener("DOMContentLoaded", function () {
  loadSettings(); // Load settings first
  setupChecklistValidation();
  loadBookings();
  setupDeleteConfirmation(); // Add this line
});

// API Configuration - Update these URLs to match your CodeIgniter routes
const API_BASE_URL = "/api/bookings"; // Update this to your actual API base URL

// Load settings from database
async function loadSettings() {
  try {
    const response = await fetch("/admin/plans/getSettings", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    if (response.ok) {
      const result = await response.json();
      if (result.success) {
        window.MAINTENANCE_FEE =
          parseFloat(result.data.maintenance_fee) || 2000;
        window.HOURLY_RATE = parseFloat(result.data.extended_hours_rate) || 500;
        window.OVERTIME_RATE = parseFloat(result.data.overtime_rate) || 5000;
        console.log("Settings loaded:", {
          MAINTENANCE_FEE: window.MAINTENANCE_FEE,
          HOURLY_RATE: window.HOURLY_RATE,
          OVERTIME_RATE: window.OVERTIME_RATE,
        });
      }
    }
  } catch (error) {
    console.error("Error loading settings:", error);
    // Set default values if loading fails
    window.MAINTENANCE_FEE = 2000;
    window.HOURLY_RATE = 500;
    window.OVERTIME_RATE = 5000;
  }
}

/**
 * ============================================
 * BOOKING CALCULATION HELPER FUNCTIONS
 * ============================================
 */

/**
 * Parse duration string to hours
 * Examples: "4 hours" -> 4, "8 hours" -> 8, "1 day" -> 24, "1 month" -> 720
 */
function parseDurationToHours(duration) {
  if (typeof duration === "number") {
    return duration;
  }

  if (typeof duration !== "string") {
    return 4; // Default to 4 hours
  }

  duration = duration.toLowerCase().trim();

  // Extract the numeric part
  const match = duration.match(/(\d+(?:\.\d+)?)/);
  if (!match) {
    return 4; // Default to 4 hours
  }

  const number = parseFloat(match[1]);

  // Determine unit and convert to hours
  if (duration.includes("month")) {
    return number * 720; // 30 days * 24 hours
  } else if (duration.includes("week")) {
    return number * 168; // 7 days * 24 hours
  } else if (duration.includes("day")) {
    return number * 24;
  } else if (duration.includes("hour")) {
    return number;
  } else if (duration.includes("minute")) {
    return number / 60;
  }

  // Default: assume it's hours
  return number;
}

/**
 * Calculate event end time
 * @param {string} startTime - Time in HH:MM or HH:MM:SS format
 * @param {number} durationHours - Duration in hours
 * @returns {string} End time in HH:MM format
 */
function calculateEventEndTime(startTime, durationHours) {
  try {
    // Parse start time
    const [hours, minutes] = startTime.split(":").map(Number);

    const startDate = new Date();
    startDate.setHours(hours, minutes, 0);

    // Add duration in minutes
    const durationMinutes = durationHours * 60;
    const endDate = new Date(startDate.getTime() + durationMinutes * 60000);

    // Format end time
    const endHours = String(endDate.getHours()).padStart(2, "0");
    const endMinutes = String(endDate.getMinutes()).padStart(2, "0");

    return `${endHours}:${endMinutes}`;
  } catch (error) {
    console.error("Error calculating event end time:", error);
    return null;
  }
}

/**
 * Calculate total duration hours from base and additional
 */
function calculateTotalDurationHours(baseDuration, additionalHours = 0) {
  const baseHours = parseDurationToHours(baseDuration);
  return baseHours + (Number(additionalHours) || 0);
}

/**
 * Check if two time ranges conflict (including grace period)
 * @param {string} startTime1 - Start time of first event (HH:MM)
 * @param {string} endTime1 - End time of first event (HH:MM)
 * @param {string} startTime2 - Start time of second event (HH:MM)
 * @param {string} endTime2 - End time of second event (HH:MM)
 * @param {number} gracePeriodHours - Grace period in hours
 * @returns {boolean} True if there is a conflict
 */
function hasTimeConflict(
  startTime1,
  endTime1,
  startTime2,
  endTime2,
  gracePeriodHours = 2,
) {
  try {
    const parseTime = (timeStr) => {
      const [h, m] = timeStr.split(":").map(Number);
      return h * 60 + m; // Convert to minutes from midnight
    };

    const start1 = parseTime(startTime1);
    const end1 = parseTime(endTime1);
    const start2 = parseTime(startTime2);
    const end2 = parseTime(endTime2);

    // Add grace period to end times (in minutes)
    const graceMinutes = gracePeriodHours * 60;
    const end1WithGrace = end1 + graceMinutes;
    const end2WithGrace = end2 + graceMinutes;

    // Check for overlap
    return !(end1WithGrace <= start2 || end2WithGrace <= start1);
  } catch (error) {
    console.error("Error checking time conflict:", error);
    return false;
  }
}

// Toggle sidebar
function toggleSidebar() {
  document.querySelector(".sidebar").classList.toggle("active");
  document.querySelector(".main-content").classList.toggle("active");
}

// Load bookings from backend
async function loadBookings() {
  showLoading(true);
  hideError();

  try {
    const response = await fetch(`${API_BASE_URL}/list`);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (data.success) {
      bookingsData = data.bookings;
      displayBookings(bookingsData);
      updateStatistics(bookingsData);
      showTable(true);
    } else {
      throw new Error(data.message || "Failed to load bookings");
    }
  } catch (error) {
    console.error("Error loading bookings:", error);
    showError("Failed to load bookings. Please try again later.");
  } finally {
    showLoading(false);
  }
}

// Display bookings in table
function displayBookings(bookings) {
  const tbody = document.getElementById("bookingsTableBody");
  tbody.innerHTML = "";

  if (bookings.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="7" class="text-center">No bookings found</td></tr>';
    return;
  }

  bookings.forEach((booking) => {
    const row = createBookingRow(booking);
    tbody.appendChild(row);
  });

  // Render all dropdown menus to body after table is rendered
  renderDropdownMenusToBody();
}

// Render all dropdown menus to body so they're not constrained by table
function renderDropdownMenusToBody() {
  if (!window.dropdownMenus || Object.keys(window.dropdownMenus).length === 0) {
    return;
  }

  // Create container for all dropdowns if it doesn't exist
  let dropdownContainer = document.getElementById("dropdown-menus-container");
  if (!dropdownContainer) {
    dropdownContainer = document.createElement("div");
    dropdownContainer.id = "dropdown-menus-container";
    dropdownContainer.style.position = "fixed";
    dropdownContainer.style.pointerEvents = "none";
    dropdownContainer.style.zIndex = "999999";
    document.body.appendChild(dropdownContainer);
  }

  // Clear existing menus
  dropdownContainer.innerHTML = "";

  // Add all dropdown menus to container
  Object.keys(window.dropdownMenus).forEach((menuId) => {
    const menuHTML = window.dropdownMenus[menuId];
    const temp = document.createElement("div");
    temp.innerHTML = menuHTML;
    const menuElement = temp.firstElementChild;
    menuElement.style.pointerEvents = "auto";
    dropdownContainer.appendChild(menuElement);
  });
}

// Create booking table row
function createBookingRow(booking) {
  const row = document.createElement("tr");
  row.setAttribute("data-status", booking.status);
  row.setAttribute("data-booking-id", booking.id);

  // Check if booking is expired
  const isExpired = isBookingExpired(booking.event_date);
  if (isExpired) {
    row.style.opacity = "0.7";
    row.style.backgroundColor = "#f8f9fa";
  }

  const statusBadge = `<span class="status-badge status-${booking.status}">${
    booking.status.charAt(0).toUpperCase() + booking.status.slice(1)
  }</span>`;

  // Booking type badge
  const isStudentBooking = booking.booking_type === "student";
  const isEmployeeBooking = booking.booking_type === "employee";
  let typeBadge;
  if (isStudentBooking) {
    typeBadge = '<span class="user-type-badge type-student">🎓 Student</span>';
  } else if (isEmployeeBooking) {
    typeBadge =
      '<span class="user-type-badge type-employee">👨‍💼 Employee</span>';
  } else {
    typeBadge = '<span class="user-type-badge type-external">👤 User</span>';
  }

  // Files status badge
  const filesCount = booking.files_count || 0;
  const requiredFiles = isStudentBooking || isEmployeeBooking ? 3 : 7;
  const filesBadge =
    filesCount >= requiredFiles
      ? `<span style="color: #28a745; font-size: 1.2rem;" title="${filesCount}/${requiredFiles} files uploaded">✅</span>`
      : `<span style="color: #dc3545; font-size: 1.2rem;" title="${filesCount}/${requiredFiles} files uploaded">❌</span>`;

  const actionButtons = createActionButtons(booking);

  row.innerHTML = `
                <td>#BK${String(booking.id).padStart(3, "0")}</td>
                <td>
                    <div>${booking.client_name}</div>
                    <small style="color: var(--gray);">${
                      booking.email_address
                    }</small>
                </td>
                <td>${typeBadge}</td>
                <td>${booking.facility_name || "N/A"}</td>
                <td>${formatDate(booking.event_date)}</td>
                <td>${statusBadge}</td>
                <td>${
                  isStudentBooking || isEmployeeBooking
                    ? '<span style="color: #28a745; font-weight: 600;">FREE</span>'
                    : "₱" + formatNumber(booking.total_cost)
                }</td>
                <td>${actionButtons}</td>
            `;

  return row;
}

// Toggle dropdown menu
function toggleDropdownMenu(event, bookingId) {
  event.stopPropagation();
  const button = event.currentTarget;
  let menu = document.getElementById(`dropdown-menu-${bookingId}`);

  // If menu doesn't exist yet, wait a moment and try again
  if (!menu) {
    setTimeout(() => toggleDropdownMenu(event, bookingId), 10);
    return;
  }

  // Close all other menus
  document.querySelectorAll(".dropdown-menu.show").forEach((m) => {
    if (m.id !== `dropdown-menu-${bookingId}`) {
      m.classList.remove("show");
    }
  });

  // Remove active class from all buttons
  document.querySelectorAll(".dropdown-trigger.active").forEach((btn) => {
    if (btn !== button) {
      btn.classList.remove("active");
    }
  });

  // Toggle current menu and button state
  const isShowing = !menu.classList.contains("show");
  menu.classList.toggle("show");
  button.classList.toggle("active");

  // Position the dropdown using fixed positioning
  if (isShowing) {
    const positionMenu = () => {
      const rect = button.getBoundingClientRect();
      menu.style.position = "fixed";
      menu.style.top = rect.bottom + 8 + "px";
      menu.style.left = rect.right - 220 + "px";
      menu.style.zIndex = "999999";
    };

    // Position immediately
    setTimeout(positionMenu, 0);

    // Reposition on scroll
    const scrollHandler = positionMenu;
    window.addEventListener("scroll", scrollHandler, {
      once: false,
      capture: true,
    });
    menu.dataset.scrollListenerActive = "true";
    menu.dataset.scrollHandler = scrollHandler;
  } else {
    // Remove scroll listener when closing
    if (menu.dataset.scrollListenerActive === "true") {
      const scrollHandler = menu.dataset.scrollHandler;
      window.removeEventListener("scroll", scrollHandler, { capture: true });
      menu.dataset.scrollListenerActive = "false";
    }
  }
}

// Close dropdown when clicking outside
document.addEventListener("click", function (event) {
  if (!event.target.closest(".action-buttons")) {
    document.querySelectorAll(".dropdown-menu.show").forEach((menu) => {
      menu.classList.remove("show");
    });
    document.querySelectorAll(".dropdown-trigger.active").forEach((btn) => {
      btn.classList.remove("active");
    });
  }
});

// Create action buttons based on booking status
function createActionButtons(booking) {
  const isEmployeeBooking = booking.booking_type === "employee";
  const isStudentBooking = booking.booking_type === "student";
  const isFreebookingType = isStudentBooking || isEmployeeBooking;

  let menuItems = [];

  // View action (always available)
  menuItems.push(
    `<button class="info" onclick="viewBooking(${booking.id})" title="View Booking Details">👁️ View</button>`,
  );

  // Upload action based on booking type
  if (isFreebookingType) {
    menuItems.push(
      `<button class="info" onclick="openStudentUploadModal(${booking.id})" title="Upload Student Documents">📤 Upload</button>`,
    );
  } else {
    menuItems.push(
      `<button class="info" onclick="openUploadModal('BK${String(
        booking.id,
      ).padStart(3, "0")}')" title="Upload Documents">📤 Upload</button>`,
    );
  }

  // Reschedule action (for pending and confirmed)
  if (booking.status === "pending" || booking.status === "confirmed") {
    menuItems.push(
      `<button class="warning" onclick="openRescheduleModal(${booking.id})" title="Reschedule Booking">📅 Reschedule</button>`,
    );
  }

  // Approval actions (for pending bookings)
  if (booking.status === "pending") {
    menuItems.push(`<div class="divider"></div>`);
    menuItems.push(
      `<button class="success" onclick="setCurrentBookingAndOpen(${booking.id}, 'approvalModal')" title="Approve Booking">✅ Approve</button>`,
    );
    menuItems.push(
      `<button class="danger" onclick="setCurrentBookingAndOpen(${booking.id}, 'declineModal')" title="Decline Booking">❌ Decline</button>`,
    );
  }

  // Decline reason view (for cancelled bookings)
  if (booking.status === "cancelled") {
    menuItems.push(
      `<button class="warning" onclick="viewDeclineReason(${booking.id})" title="View Decline Reason">❌ View Decline Reason</button>`,
    );
  }

  // Delete action (for cancelled and confirmed bookings)
  if (booking.status === "cancelled" || booking.status === "confirmed") {
    menuItems.push(`<div class="divider"></div>`);
    menuItems.push(
      `<button class="danger" onclick="openDeleteModal(${booking.id})" title="Delete Booking">🗑️ Delete</button>`,
    );
  }

  const dropdownId = `dropdown-menu-${booking.id}`;
  const dropdownMenuHTML = `<div class="dropdown-menu" id="${dropdownId}">
    ${menuItems.join("\n")}
  </div>`;

  // Create button HTML
  const buttonHTML = `
    <div class="action-buttons">
      <button class="dropdown-trigger" onclick="toggleDropdownMenu(event, ${booking.id})">⚙️ Actions</button>
    </div>
  `;

  // Store the dropdown menu HTML to be appended to body later
  if (!window.dropdownMenus) {
    window.dropdownMenus = {};
  }
  window.dropdownMenus[dropdownId] = dropdownMenuHTML;

  return buttonHTML;
}

// Update statistics
function updateStatistics(bookings) {
  const stats = {
    pending: bookings.filter((b) => b.status === "pending").length,
    confirmed: bookings.filter((b) => b.status === "confirmed").length,
    cancelled: bookings.filter((b) => b.status === "cancelled").length,
    totalRevenue: bookings
      .filter(
        (b) =>
          b.status === "confirmed" &&
          !["student", "employee"].includes(b.booking_type),
      )
      .reduce((sum, b) => sum + parseFloat(b.total_cost || 0), 0),
  };

  document.getElementById("pendingCount").textContent = stats.pending;
  document.getElementById("confirmedCount").textContent = stats.confirmed;
  document.getElementById("cancelledCount").textContent = stats.cancelled;
  document.getElementById("totalRevenue").textContent =
    "₱" + formatNumber(stats.totalRevenue);
}

// View booking details
async function viewBooking(bookingId) {
  currentBookingId = bookingId;

  try {
    showLoading(true); // Show loading indicator if you have one

    const response = await fetch(`${API_BASE_URL}/detail/${bookingId}`);

    console.log("API Response Status:", response.status); // Debug log
    console.log("API Response URL:", response.url); // Debug log

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    console.log("API Response Data:", data); // Debug log

    if (data.success) {
      displayBookingDetails(data.booking);

      // Display extensions tab for this booking (Phase 5)
      if (typeof displayExtensionsTab === "function") {
        displayExtensionsTab(data.booking.id);
      }

      document.getElementById("viewBookingModal").style.display = "block";

      // Show/hide action buttons based on status
      const approveBtn = document.getElementById("approveBtn");
      const declineBtn = document.getElementById("declineBtn");
      const deleteBtn = document.getElementById("deleteBtn");
      const downloadBtn = document.getElementById("downloadBtn");

      // Hide download button for student bookings, show for user bookings
      if (downloadBtn) {
        downloadBtn.style.display =
          data.booking.booking_type === "student" ? "none" : "inline-flex";
      }

      if (data.booking.status === "pending") {
        if (approveBtn) approveBtn.style.display = "inline-flex";
        if (declineBtn) declineBtn.style.display = "inline-flex";
        if (deleteBtn) deleteBtn.style.display = "none";
      } else if (data.booking.status === "cancelled") {
        if (approveBtn) approveBtn.style.display = "none";
        if (declineBtn) declineBtn.style.display = "none";
        if (deleteBtn) deleteBtn.style.display = "inline-flex";
      } else {
        if (approveBtn) approveBtn.style.display = "none";
        if (declineBtn) declineBtn.style.display = "none";
        if (deleteBtn) deleteBtn.style.display = "none";
      }
    } else {
      throw new Error(data.message || "Failed to load booking details");
    }
  } catch (error) {
    console.error("Error loading booking details:", error);

    // Still show the modal but with error info
    const detailsContainer = document.getElementById("bookingDetailsContent");
    detailsContainer.innerHTML = `
      <div class="error-message" style="text-align: center; padding: 40px;">
        <h3>Error Loading Details</h3>
        <p>Unable to load booking details: ${error.message}</p>
        <button class="btn btn-primary" onclick="viewBooking(${bookingId})" style="margin-top: 15px;">
          🔄 Retry
        </button>
      </div>
    `;

    // Show the modal anyway so user can retry
    document.getElementById("viewBookingModal").style.display = "block";

    // Don't show the alert immediately, let user see the error in modal
    // alert("Failed to load booking details: " + error.message);
  } finally {
    showLoading(false); // Hide loading indicator
  }
}

// Display booking details in modal
function displayBookingDetails(booking) {
  const detailsContainer = document.getElementById("bookingDetailsContent");

  // Initialize tabs container
  const tabsContainer = document.getElementById("bookingTabs");
  if (tabsContainer) {
    tabsContainer.innerHTML = `
      <button class="tab-button active" data-tab="details" onclick="switchBookingTab('details')">
        <i class="fas fa-info-circle"></i> Details
      </button>
    `;
  }

  // Clear and prepare the details container for tab content
  detailsContainer.innerHTML = "";

  // Calculate end time if not already set (for pending bookings)
  if (!booking.event_end_time && booking.event_time && booking.duration) {
    const totalHours = calculateTotalDurationHours(
      booking.duration,
      booking.additional_hours || 0,
    );
    booking.event_end_time = calculateEventEndTime(
      booking.event_time,
      totalHours,
    );
    booking.total_duration_hours = totalHours;
  }

  // Check if this is a student booking
  const isStudentBooking = booking.booking_type === "student";
  const isEmployeeBooking = booking.booking_type === "employee";
  const isFreebookingType = isStudentBooking || isEmployeeBooking;

  // Calculate overtime only for non-free bookings
  let overtimeFee = 0;
  let hasOvertime = false;
  let dayOfWeek = null;
  let isWeekend = false;

  if (!isFreebookingType && booking.event_date && booking.event_time) {
    try {
      const eventDate = new Date(booking.event_date + " " + booking.event_time);
      dayOfWeek = eventDate.getDay();
      const hour = eventDate.getHours();
      isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
      const isAfter5PM = hour >= 17;
      hasOvertime = isWeekend || isAfter5PM;
      // Get overtime rate from settings, default to 5000 if not available
      const overtimeRate = window.OVERTIME_RATE || 5000;
      overtimeFee = booking.overtime_fee || (hasOvertime ? overtimeRate : 0);
    } catch (error) {
      console.warn("Error calculating overtime fee:", error);
      overtimeFee = booking.overtime_fee || 0;
    }
  } else if (!isFreebookingType) {
    // Use stored overtime_fee if available
    overtimeFee = booking.overtime_fee || 0;
  }

  detailsContainer.innerHTML = `
    <div class="tab-content" data-tab="details" style="display: block;">
    ${
      isStudentBooking
        ? '<span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #e3f2fd; color: #1976d2; border-radius: 4px; font-size: 0.8rem; font-weight: 500; margin-bottom: 12px; border: 1px solid #90caf9;">🎓 Student Booking</span>'
        : isEmployeeBooking
          ? '<span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #f3e5f5; color: #7b1fa2; border-radius: 4px; font-size: 0.8rem; font-weight: 500; margin-bottom: 12px; border: 1px solid #ce93d8;">👨‍💼 Employee Booking</span>'
          : ""
    }
    
    <div class="detail-section">
        <div class="detail-title">📋 Basic Information</div>
        <div class="detail-item">
            <span class="detail-label">Booking ID:</span>
            <span class="detail-value">#BK${String(booking.id).padStart(
              3,
              "0",
            )}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Client Name:</span>
            <span class="detail-value">${booking.client_name}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Email:</span>
            <span class="detail-value">${booking.email_address}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Contact:</span>
            <span class="detail-value">${booking.contact_number}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Organization:</span>
            <span class="detail-value">${booking.organization || "N/A"}</span>
        </div>
        ${
          !isStudentBooking
            ? `
        <div class="detail-item">
            <span class="detail-label">Address:</span>
            <span class="detail-value">${booking.address || "N/A"}</span>
        </div>
        `
            : ""
        }
    </div>

    <div class="detail-section">
        <div class="detail-title">📅 Event Information</div>
        <div class="detail-item">
            <span class="detail-label">Facility:</span>
            <span class="detail-value">${booking.facility_name || "N/A"}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Event Date:</span>
            <span class="detail-value">${
              booking.event_date ? formatDate(booking.event_date) : "N/A"
            }</span>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0;">
            <div class="detail-item" style="background-color: #e3f2fd; padding: 12px; border-radius: 6px; border-left: 4px solid #1976d2;">
                <span class="detail-label" style="display: block; color: #1976d2; font-weight: 600;">⏰ Start Time</span>
                <span class="detail-value" style="font-weight: bold; color: #1565c0; font-size: 1.1em;">${
                  booking.event_time || "N/A"
                }</span>
            </div>
            <div class="detail-item" style="background-color: #f0f8ff; padding: 12px; border-radius: 6px; border-left: 4px solid #2196F3;">
                <span class="detail-label" style="display: block; color: #2196F3; font-weight: 600;">🏁 End Time</span>
                <span class="detail-value" style="font-weight: bold; color: #1565c0; font-size: 1.1em;">${
                  booking.event_end_time || "N/A"
                }</span>
            </div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Duration:</span>
            <span class="detail-value">${booking.duration || "N/A"}</span>
        </div>
        ${
          booking.total_duration_hours
            ? `
            <div class="detail-item" style="background-color: #fffacd; padding: 8px; border-radius: 4px; border-left: 4px solid #FF9800;">
                <span class="detail-label">Total Duration:</span>
                <span class="detail-value" style="font-weight: bold; color: #FF9800;">${booking.total_duration_hours} hours</span>
            </div>
            `
            : ""
        }
        ${
          booking.additional_hours && booking.additional_hours > 0
            ? `
            <div class="detail-item">
                <span class="detail-label">Additional Hours:</span>
                <span class="detail-value">${
                  booking.additional_hours
                } hours (₱${booking.additional_hours * 500}/hour)</span>
            </div>
            `
            : ""
        }
        <div class="detail-item">
            <span class="detail-label">Attendees:</span>
            <span class="detail-value">${
              booking.attendees || "N/A"
            } people</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Event Title:</span>
            <span class="detail-value">${booking.event_title || "N/A"}</span>
        </div>
    </div>

    ${
      !isStudentBooking
        ? `
    <div class="detail-section">
        <div class="detail-title">💰 Cost Breakdown</div>
        <div class="detail-item">
            <span class="detail-label">Plan Selected:</span>
            <span class="detail-value">${booking.plan_name || "N/A"}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Plan Cost:</span>
            <span class="detail-value">₱${formatNumber(
              booking.plan_price || 0,
            )}</span>
        </div>
        ${
          booking.maintenance_fee && booking.maintenance_fee > 0
            ? `
        <div class="detail-item">
            <span class="detail-label">Maintenance Fee:</span>
            <span class="detail-value">₱${formatNumber(
              booking.maintenance_fee,
            )}</span>
        </div>
        `
            : ""
        }
        ${
          hasOvertime || (booking.overtime_fee && booking.overtime_fee > 0)
            ? `
        <div class="detail-item">
            <span class="detail-label">Overtime Fee:</span>
            <span class="detail-value" style="color: var(--warning);">
                ₱${formatNumber(overtimeFee)}
            </span>
            <small style="display: block; color: var(--warning); margin-top: 4px; font-style: italic;">
                ${
                  isWeekend
                    ? "⏰ Weekend event - Overtime staff required"
                    : "⏰ After 5PM event - Overtime staff required"
                }
            </small>
        </div>
        `
            : ""
        }
        <div class="detail-item" style="border-top: 2px solid var(--primary); margin-top: 10px; padding-top: 10px;">
            <span class="detail-label"><strong>Total Cost:</strong></span>
            <span class="detail-value"><strong style="color: var(--primary); font-size: 1.2em;">₱${formatNumber(
              booking.total_cost,
            )}</strong></span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span class="detail-value">
                <span class="status-badge status-${booking.status}">
                    ${
                      booking.status.charAt(0).toUpperCase() +
                      booking.status.slice(1)
                    }
                </span>
            </span>
        </div>
    </div>
    `
        : `
    <div class="detail-section">
        <div class="detail-title">📊 Booking Status</div>
        <div class="detail-item">
            <span class="detail-label">Plan Selected:</span>
            <span class="detail-value">${booking.plan_name || "N/A"}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span class="detail-value">
                <span class="status-badge status-${booking.status}">
                    ${
                      booking.status.charAt(0).toUpperCase() +
                      booking.status.slice(1)
                    }
                </span>
            </span>
        </div>
        <div class="info-message" style="margin-top: 15px; padding: 10px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
            <strong>🎓 Student Booking:</strong> This is a student organization booking. No payment required.
        </div>
    </div>
    `
    }

    ${
      booking.equipment && booking.equipment.length > 0
        ? `
    <div class="detail-section">
        <div class="detail-title">🔧 Equipment & Logistics</div>
        ${booking.equipment
          .map(
            (eq) => `
            <div class="detail-item">
                <span class="detail-label">${eq.name}:</span>
                <span class="detail-value">${eq.quantity} units${
                  !isStudentBooking && eq.rate > 0
                    ? ` � ?${formatNumber(eq.rate)} = ?${formatNumber(
                        eq.total_cost,
                      )}`
                    : ""
                }</span>
            </div>
        `,
          )
          .join("")}
    </div>
    `
        : `
    <div class="detail-section">
        <div class="detail-title">🔧 Equipment & Logistics</div>
        <p style="color: var(--gray); font-style: italic;">No additional equipment selected.</p>
    </div>
    `
    }

    ${
      isStudentBooking || isEmployeeBooking
        ? `
    <div class="detail-section">
        <div class="detail-title">📄 Submitted Documents</div>
        <div id="studentFilesContainer">
            <div class="loading-spinner" style="margin: 20px auto;"></div>
            <p style="text-align: center; color: var(--gray);">Loading documents...</p>
        </div>
    </div>
    `
        : ""
    }
${
  !isStudentBooking && !isEmployeeBooking
    ? `
    <div class="detail-section">
        <div class="detail-title">📎 Uploaded Documents (Admin)</div>
        <div id="adminUploadedFiles">
            <div class="loading" style="text-align: center; padding: 20px;">
                <div class="loading-spinner" style="margin: 0 auto 10px;"></div>
                Loading documents...
            </div>
        </div>
    </div>
`
    : ""
}
    ${
      booking.special_requirements
        ? `
    <div class="detail-section">
        <div class="detail-title">⚙️ Special Requirements</div>
        <p style="color: var(--dark); line-height: 1.6;">${booking.special_requirements}</p>
    </div>
    `
        : ""
    }

    <div id="surveySection" class="detail-section">
        <div class="detail-title">📝 Facility Evaluation Survey</div>
        <div id="surveyContent">
            <div class="loading-spinner" style="margin: 20px auto;"></div>
            <p style="text-align: center; color: var(--gray);">Loading survey data...</p>
        </div>
    </div>
    </div>
  `;

  // Load free booking files if it's a student or employee booking
  const isFreeBooking = isStudentBooking || isEmployeeBooking;
  if (isFreeBooking) {
    loadFreeBookingFiles(booking.id);
  } else {
    loadAdminUploadedFiles(booking.id);
  }

  // Load survey data
  loadSurveyData(booking.id);
}
function updateModalButtons(booking, isStudentBooking, isEmployeeBooking) {
  const downloadBtn = document.getElementById("downloadBtn");
  const approveBtn = document.getElementById("approveBtn");
  const declineBtn = document.getElementById("declineBtn");

  // Set currentBookingId for modal actions
  currentBookingId = booking.id;

  // For student and employee bookings, hide download button
  const isFreeBooking = isStudentBooking || isEmployeeBooking;
  if (isFreeBooking) {
    if (downloadBtn) downloadBtn.style.display = "none";
  } else {
    if (downloadBtn) {
      downloadBtn.style.display = "inline-block";
      // Add click handler to ensure it works
      downloadBtn.onclick = function () {
        console.log("Download button clicked");
        openDownloadModal();
      };
    }
  }

  // Show approve/decline buttons only for pending bookings
  if (booking.status === "pending") {
    if (approveBtn) {
      approveBtn.style.display = "inline-block";
      approveBtn.onclick = function () {
        openApprovalModal();
      };
    }
    if (declineBtn) {
      declineBtn.style.display = "inline-block";
      declineBtn.onclick = function () {
        openDeclineModal();
      };
    }
  } else {
    if (approveBtn) approveBtn.style.display = "none";
    if (declineBtn) declineBtn.style.display = "none";
  }
}
// Set current booking and open modal
function setCurrentBookingAndOpen(bookingId, modalId) {
  currentBookingId = bookingId;
  document.getElementById(modalId).style.display = "block";
}

// Open approval modal
function openApprovalModal() {
  if (!currentBookingId) return;

  // Reset form
  document
    .querySelectorAll('#approvalModal input[type="checkbox"]')
    .forEach((cb) => (cb.checked = false));
  document.getElementById("approvalNotes").value = "";
  document.getElementById("approveBookingBtn").disabled = true;

  closeModal("viewBookingModal");
  document.getElementById("approvalModal").style.display = "block";

  // Enable approve button when checkboxes are checked
  const checkboxes = document.querySelectorAll(
    '#approvalModal input[type="checkbox"]',
  );
  const approveBtn = document.getElementById("approveBookingBtn");

  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      const allChecked = Array.from(checkboxes).every((cb) => cb.checked);
      approveBtn.disabled = !allChecked;
    });
  });
}

// Open decline modal
function openDeclineModal() {
  if (!currentBookingId) return;

  // Reset form
  document.getElementById("declineReason").value = "";
  document.getElementById("customDeclineReason").value = "";
  document.getElementById("declineNotes").value = "";
  document.getElementById("sendNotification").checked = true;
  document.getElementById("customReasonGroup").style.display = "none";

  closeModal("viewBookingModal");
  document.getElementById("declineModal").style.display = "block";
}

// Open delete modal
function openDeleteModal(bookingId) {
  currentBookingId = bookingId;

  // Find booking data
  const booking = bookingsData.find((b) => b.id == bookingId);
  if (booking) {
    document.getElementById("deleteBookingId").textContent = `#BK${String(
      booking.id,
    ).padStart(3, "0")}`;
    document.getElementById("deleteClientName").textContent =
      booking.client_name;
  }

  // Reset form
  document.getElementById("deleteReason").value = "";
  document.getElementById("confirmDelete").checked = false;
  document.getElementById("confirmDeleteBtn").disabled = true;

  document.getElementById("deleteConfirmModal").style.display = "block";
}

// Delete booking
function deleteBooking() {
  if (!currentBookingId) return;
  closeModal("viewBookingModal");
  openDeleteModal(currentBookingId);
}

// Confirm delete booking
async function confirmDeleteBooking() {
  if (!currentBookingId) return;

  const reason = document.getElementById("deleteReason").value;
  const confirmBtn = document.getElementById("confirmDeleteBtn");

  // Show loading state
  const originalText = confirmBtn.innerHTML;
  confirmBtn.innerHTML = "🗑️ Deleting...";
  confirmBtn.disabled = true;

  try {
    const response = await fetch(`${API_BASE_URL}/delete/${currentBookingId}`, {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        reason: reason,
      }),
    });

    const data = await response.json();

    if (data.success) {
      alert("Booking deleted successfully!");
      closeModal("deleteConfirmModal");
      loadBookings(); // Reload bookings
    } else {
      alert(data.message || "Failed to delete booking");
    }
  } catch (error) {
    console.error("Error deleting booking:", error);
    alert("Failed to delete booking");
  } finally {
    // Reset button state
    confirmBtn.innerHTML = originalText;
    confirmBtn.disabled = false;
  }
}

// Open download modal - FIXED FUNCTION
function openDownloadModal() {
  console.log("Opening download modal, currentBookingId:", currentBookingId);

  if (!currentBookingId) {
    alert("No booking selected");
    return;
  }

  // Get booking data for the current booking
  const booking = bookingsData.find((b) => b.id == currentBookingId);

  if (!booking) {
    displayBookingDetails;
    alert("Booking data not found");
    return;
  }

  // Update modal with booking-specific information
  document.getElementById("downloadBookingId").textContent = `BK${String(
    booking.id,
  ).padStart(3, "0")}`;
  document.getElementById("downloadClientName").textContent =
    booking.client_name;

  // Load evaluation files
  loadEvaluationFiles(currentBookingId);

  // Close the view modal first
  closeModal("viewBookingModal");

  // Show the download modal with a slight delay to ensure smooth transition
  setTimeout(() => {
    document.getElementById("downloadModal").style.display = "block";
  }, 100);
}

// Load and display evaluation files
async function loadEvaluationFiles(bookingId) {
  try {
    const response = await fetch(`/api/survey-files/${bookingId}`);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (data.success && data.files && data.files.length > 0) {
      const section = document.getElementById("uploadedEvaluationFilesSection");
      const filesContainer = document.getElementById("uploadedEvaluationFiles");

      // Clear previous files
      filesContainer.innerHTML = "";

      // Create file items
      data.files.forEach((file) => {
        const fileItem = document.createElement("div");
        fileItem.className = "file-item";
        fileItem.innerHTML = `
          <div class="file-info">
            <div class="file-icon">📄</div>
            <div class="file-details">
              <div class="file-name">${file.name}</div>
              <div class="file-description">Size: ${formatFileSize(
                file.size,
              )}</div>
            </div>
          </div>
          <div class="file-actions">
            <button class="btn btn-sm btn-outline" onclick="downloadEvaluationFile('${
              file.path
            }')" title="Download">
              📥
            </button>
          </div>
        `;
        filesContainer.appendChild(fileItem);
      });

      // Show the section
      section.style.display = "block";
    } else {
      // Hide the section if no files
      document.getElementById("uploadedEvaluationFilesSection").style.display =
        "none";
    }
  } catch (error) {
    console.error("Error loading evaluation files:", error);
    document.getElementById("uploadedEvaluationFilesSection").style.display =
      "none";
  }
}

// Format file size
function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
}

// Download evaluation file
function downloadEvaluationFile(filename) {
  const url = `/uploads/${filename}`;
  window.open(url, "_blank");
}

// Open upload modal
function openUploadModal(bookingId) {
  if (!bookingId) return;

  // Set the booking ID in the upload modal
  document.getElementById("uploadBookingId").textContent = bookingId;

  // Close the view modal first
  closeModal("viewBookingModal");

  // Show the upload modal
  document.getElementById("uploadModal").style.display = "block";
}

// Setup checklist validation
function setupChecklistValidation() {
  const checkboxes = document.querySelectorAll(
    '#approvalModal input[type="checkbox"]',
  );
  const approveBtn = document.getElementById("approveBookingBtn");

  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      const allChecked = Array.from(checkboxes).every((cb) => cb.checked);
      approveBtn.disabled = !allChecked;
    });
  });
}

// Toggle custom reason field
function toggleCustomReason() {
  const reason = document.getElementById("declineReason").value;
  const customGroup = document.getElementById("customReasonGroup");

  if (reason === "other") {
    customGroup.style.display = "block";
    document.getElementById("customDeclineReason").required = true;
  } else {
    customGroup.style.display = "none";
    document.getElementById("customDeclineReason").required = false;
  }
}

// Approve booking
async function approveBooking() {
  if (!currentBookingId) return;

  const notes = document.getElementById("approvalNotes").value;

  try {
    const response = await fetch(
      `${API_BASE_URL}/approve/${currentBookingId}`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          notes: notes,
        }),
      },
    );

    const data = await response.json();

    if (data.success) {
      // Show success message with calculated times if available
      let successMsg = "Booking approved successfully!";
      if (data.event_end_time && data.total_duration_hours) {
        successMsg = `Booking approved successfully!\n\nCalculated Details:\nEnd Time: ${data.event_end_time}\nTotal Duration: ${data.total_duration_hours} hours`;
      }
      alert(successMsg);
      closeModal("approvalModal");
      loadBookings(); // Reload bookings
    } else {
      alert(data.message || "Failed to approve booking");
    }
  } catch (error) {
    console.error("Error approving booking:", error);
    alert("Failed to approve booking");
  }
}

// Decline booking
async function declineBooking() {
  if (!currentBookingId) return;

  const reason = document.getElementById("declineReason").value;
  const customReason = document.getElementById("customDeclineReason").value;
  const notes = document.getElementById("declineNotes").value;
  const sendNotification = document.getElementById("sendNotification").checked;

  if (!reason || !notes) {
    alert("Please fill in all required fields.");
    return;
  }

  if (reason === "other" && !customReason) {
    alert("Please specify the custom reason.");
    return;
  }

  try {
    const response = await fetch(
      `${API_BASE_URL}/decline/${currentBookingId}`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          reason: reason === "other" ? customReason : reason,
          notes: notes,
          send_notification: sendNotification,
        }),
      },
    );

    const data = await response.json();

    if (data.success) {
      alert("Booking declined successfully!");
      closeModal("declineModal");
      loadBookings(); // Reload bookings
    } else {
      alert(data.message || "Failed to decline booking");
    }
  } catch (error) {
    console.error("Error declining booking:", error);
    alert("Failed to decline booking");
  }
}

// Filter bookings
function filterBookings() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();
  const statusFilter = document.getElementById("statusFilter").value;
  const dateFromFilter = document.getElementById("dateFromFilter").value;
  const dateToFilter = document.getElementById("dateToFilter").value;

  let filteredBookings = bookingsData;

  // Search filter
  if (searchTerm) {
    filteredBookings = filteredBookings.filter(
      (booking) =>
        booking.client_name.toLowerCase().includes(searchTerm) ||
        booking.email_address.toLowerCase().includes(searchTerm) ||
        booking.event_title.toLowerCase().includes(searchTerm) ||
        String(booking.id).includes(searchTerm),
    );
  }

  // Status filter
  if (statusFilter) {
    filteredBookings = filteredBookings.filter(
      (booking) => booking.status === statusFilter,
    );
  }

  // Date range filter
  if (dateFromFilter) {
    filteredBookings = filteredBookings.filter(
      (booking) => new Date(booking.event_date) >= new Date(dateFromFilter),
    );
  }

  if (dateToFilter) {
    filteredBookings = filteredBookings.filter(
      (booking) => new Date(booking.event_date) <= new Date(dateToFilter),
    );
  }

  displayBookings(filteredBookings);
}

// Clear filters
function clearFilters() {
  document.getElementById("searchInput").value = "";
  document.getElementById("statusFilter").value = "";
  document.getElementById("dateFromFilter").value = "";
  document.getElementById("dateToFilter").value = "";
  displayBookings(bookingsData);
}

// Generate report
async function generateReport() {
  // Open modal for month/year selection
  populateYearDropdown();
  openModal("facilityRentalReportModal");
}

// Generate Excel report from data
function generateExcelReport(reportData) {
  // Create CSV content with BOM for proper UTF-8 encoding
  let csv = "\uFEFF"; // BOM for UTF-8

  // Report Header with styling separators
  csv +=
    "-------------------------------------------------------------------------------\n";
  csv += "                     CSPC BOOKING MANAGEMENT REPORT\n";
  csv +=
    "-------------------------------------------------------------------------------\n";
  csv += `Generated on:,${new Date().toLocaleString()}\n`;
  csv +=
    "-------------------------------------------------------------------------------\n\n";

  // Summary Section with better formatting
  csv +=
    "+-----------------------------------------------------------------------------+\n";
  csv +=
    "�                              SUMMARY STATISTICS                              �\n";
  csv +=
    "+-----------------------------------------------------------------------------�\n";
  csv += `� Total Bookings:,${reportData.total_bookings}\n`;
  csv +=
    "+-----------------------------------------------------------------------------�\n";
  csv += `� � Pending:,${reportData.status_summary.pending}\n`;
  csv += `� � Confirmed:,${reportData.status_summary.confirmed}\n`;
  csv += `� � Cancelled:,${reportData.status_summary.cancelled}\n`;
  csv +=
    "+-----------------------------------------------------------------------------+\n\n";

  // Revenue Section
  csv +=
    "+-----------------------------------------------------------------------------+\n";
  csv +=
    "�                    REVENUE SUMMARY (Excluding Students)                      �\n";
  csv +=
    "+-----------------------------------------------------------------------------�\n";
  csv += `� Total Revenue:,?${reportData.revenue.total.toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}\n`;
  csv += `� Confirmed Revenue:,?${reportData.revenue.confirmed.toLocaleString(
    "en-US",
    { minimumFractionDigits: 2, maximumFractionDigits: 2 },
  )}\n`;
  csv += `� Pending Revenue:,?${reportData.revenue.pending.toLocaleString(
    "en-US",
    { minimumFractionDigits: 2, maximumFractionDigits: 2 },
  )}\n`;
  csv +=
    "+-----------------------------------------------------------------------------+\n\n";

  // Booking Details Section
  csv +=
    "-------------------------------------------------------------------------------\n";
  csv += "                            DETAILED BOOKING LIST\n";
  csv +=
    "-------------------------------------------------------------------------------\n\n";

  // Table Header with better column names
  csv +=
    "Booking ID,Client Name,Email,Contact Number,Organization,Type,Facility,Event Title,Event Date,Event Time,Duration (hrs),Attendees,Status,Total Cost,Created Date\n";
  csv +=
    "-----------,--------------------------,------------------------------,---------------,--------------------------,--------,--------------------------,----------------------------------,----------,----------,--------------,---------,----------,--------------,---------------------\n";

  // Booking Details Rows with proper formatting
  reportData.bookings.forEach((booking, index) => {
    // Format date nicely
    let formattedDate = booking.event_date;
    if (formattedDate && formattedDate !== "N/A") {
      try {
        const date = new Date(formattedDate);
        formattedDate = date.toLocaleDateString("en-US", {
          year: "numeric",
          month: "short",
          day: "numeric",
        });
      } catch (e) {
        // Keep original if parsing fails
      }
    }

    // Format time nicely
    let formattedTime = booking.event_time;
    if (formattedTime && formattedTime !== "N/A") {
      try {
        const [hours, minutes] = formattedTime.split(":");
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? "PM" : "AM";
        const displayHour = hour % 12 || 12;
        formattedTime = `${displayHour}:${minutes} ${ampm}`;
      } catch (e) {
        // Keep original if parsing fails
      }
    }

    csv += `"${booking.booking_id}",`;
    csv += `"${booking.client_name}",`;
    csv += `"${booking.email}",`;
    csv += `"${booking.contact}",`;
    csv += `"${booking.organization}",`;
    csv += `"${booking.booking_type}",`;
    csv += `"${booking.facility}",`;
    csv += `"${booking.event_title}",`;
    csv += `"${formattedDate}",`;
    csv += `"${formattedTime}",`;
    csv += `${booking.duration},`;
    csv += `${booking.attendees},`;
    csv += `"${booking.status}",`;
    csv += `"${booking.total_cost}",`;
    csv += `"${formatDateTime(booking.created_at)}"\n`;
  });

  // Footer
  csv +=
    "\n-------------------------------------------------------------------------------\n";
  csv += `Total Records: ${reportData.bookings.length}\n`;
  csv +=
    "-------------------------------------------------------------------------------\n";
  csv += "\n\nNote: This report was generated by CSPC Digital Booking System\n";
  csv +=
    "For any inquiries, please contact the CSPC Facilities Management Office.\n";

  // Create and download file
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const link = document.createElement("a");
  const fileName = `CSPC_Booking_Report_${
    new Date().toISOString().split("T")[0]
  }.csv`;

  if (navigator.msSaveBlob) {
    // IE 10+
    navigator.msSaveBlob(blob, fileName);
  } else {
    link.href = URL.createObjectURL(blob);
    link.download = fileName;
    link.style.display = "none";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  alert(
    `? Report downloaded successfully!\n\nFile: ${fileName}\nTotal Bookings: ${reportData.bookings.length}`,
  );
}

// Helper function to format datetime
function formatDateTime(dateTimeString) {
  if (!dateTimeString || dateTimeString === "N/A") return "N/A";
  try {
    const date = new Date(dateTimeString);
    return date.toLocaleString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    });
  } catch (e) {
    return dateTimeString;
  }
}

// Close modal
function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none";
  if (modalId !== "viewBookingModal") {
    currentBookingId = null;
  }
}

// Open modal
function openModal(modalId) {
  document.getElementById(modalId).style.display = "block";
}

// Download Functions
function selectAllFiles() {
  const checkboxes = document.querySelectorAll(".file-checkbox");
  checkboxes.forEach((checkbox) => {
    checkbox.checked = true;
    checkbox.closest(".file-item").classList.add("selected");
  });
}

function deselectAllFiles() {
  const checkboxes = document.querySelectorAll(".file-checkbox");
  checkboxes.forEach((checkbox) => {
    checkbox.checked = false;
    checkbox.closest(".file-item").classList.remove("selected");
  });
}

function downloadSingleFile(fileType) {
  if (!currentBookingId) return;

  if (fileType === "billing") {
    downloadBillingStatement(currentBookingId);
    return;
  }

  if (fileType === "equipment") {
    downloadEquipmentRequestForm(currentBookingId);
    return;
  }

  if (fileType === "moa") {
    downloadMoa(currentBookingId);
    return;
  }

  if (fileType === "evaluation") {
    downloademployeeEvaluation(currentBookingId);
    return;
  }
  if (fileType === "inspection") {
    downloadInspectionEvaluation(currentBookingId);
    return;
  }
  if (fileType === "orderofpayment") {
    downloadOrderOfPayment(currentBookingId);
    return;
  }

  alert(
    `Downloading ${fileType} file for booking BK${String(
      currentBookingId,
    ).padStart(3, "0")}...`,
  );
}

/**
 * Download billing statement for a booking
 */
function downloadBillingStatement(bookingId) {
  if (!bookingId) {
    console.error("[Billing Statement] No booking ID provided");
    alert("? Error: No booking ID provided");
    return;
  }

  try {
    console.log(
      `[Billing Statement] Starting download for booking #${bookingId}`,
    );

    // Show loading state
    alert(
      `Generating billing statement for booking BK${String(bookingId).padStart(
        3,
        "0",
      )}...`,
    );

    // Create download URL
    const downloadUrl = `${API_BASE_URL}/${bookingId}/billing-statement`;
    console.log(`[Billing Statement] Download URL: ${downloadUrl}`);

    // Create temporary link and trigger download
    const a = document.createElement("a");
    a.href = downloadUrl;
    a.target = "_blank";
    a.style.display = "none";
    document.body.appendChild(a);

    console.log(
      `[Billing Statement] Download element created with href: ${a.href}`,
    );

    a.click();

    console.log(`[Billing Statement] Download click triggered`);

    setTimeout(() => {
      document.body.removeChild(a);
      console.log(`[Billing Statement] Download element removed from DOM`);
    }, 100);

    console.log(
      `[Billing Statement] Billing statement download initiated for booking: ${bookingId}`,
    );
  } catch (error) {
    console.error(`[Billing Statement] Error initiating download:`, error);
    console.error(`[Billing Statement] Error message: ${error.message}`);
    alert(`? Failed to download Billing Statement: ${error.message}`);
  }
}

async function downloadSelectedFiles() {
  const selectedCheckboxes = document.querySelectorAll(
    ".file-checkbox:checked",
  );

  if (selectedCheckboxes.length === 0) {
    alert("Please select at least one file to download.");
    return;
  }

  if (!currentBookingId) {
    alert("No booking selected.");
    return;
  }

  // Create array of selected file types
  const selectedFiles = Array.from(selectedCheckboxes).map((cb) =>
    cb.id.replace("file-", ""),
  );

  // Show loading message
  const originalAlert = alert;
  alert(`Preparing ${selectedFiles.length} file(s) for download...`);

  try {
    // Create download URL with selected types as query parameter
    const types = selectedFiles.join(",");
    const downloadUrl = `${API_BASE_URL}/${currentBookingId}/download-zip?types=${types}`;

    // Create temporary link and trigger download
    const a = document.createElement("a");
    a.href = downloadUrl;
    a.download = `booking_BK${String(currentBookingId).padStart(
      3,
      "0",
    )}_selected.zip`;
    a.style.display = "none";
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    console.log(`ZIP download initiated with types: ${types}`);
  } catch (error) {
    console.error("Error downloading files:", error);
    alert("Failed to download files: " + error.message);
  }
}

async function downloadAllFiles() {
  if (!currentBookingId) {
    alert("No booking selected.");
    return;
  }

  // Show loading message
  alert("Preparing all files for download...");

  try {
    // Define all available file types
    const allTypes =
      "moa,billing,equipment,evaluation,inspection,receipt,orderofpayment";
    const downloadUrl = `${API_BASE_URL}/${currentBookingId}/download-zip?types=${allTypes}`;

    // Create temporary link and trigger download
    const a = document.createElement("a");
    a.href = downloadUrl;
    a.download = `booking_BK${String(currentBookingId).padStart(
      3,
      "0",
    )}_all_documents.zip`;
    a.style.display = "none";
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    console.log(`ZIP download initiated for all files`);
  } catch (error) {
    console.error("Error downloading all files:", error);
    alert("Failed to download files: " + error.message);
  }
}

// Upload Modal JavaScript Functions
let uploadedFiles = {};

async function openUploadModal(bookingId) {
  if (!bookingId) return;

  // Extract numeric ID if needed
  const numericId = bookingId.toString().replace("BK", "").replace(/^0+/, "");
  currentBookingId = numericId;

  // Get booking data
  const booking = bookingsData.find((b) => b.id == numericId);
  if (!booking) {
    alert("Booking not found");
    return;
  }

  // Set the booking ID in the upload modal
  document.getElementById("uploadBookingId").textContent = `BK${String(
    numericId,
  ).padStart(3, "0")}`;
  document.getElementById("uploadClientName").textContent = booking.client_name;

  // **Check booking type: show student/employee files or admin files**
  const isFreeBooking =
    booking.booking_type === "student" || booking.booking_type === "employee";

  if (isFreeBooking) {
    // Show student file items for free bookings
    hideAdminUploadItems();
    showStudentUploadItems();
    document.getElementById("totalCount").textContent = "3"; // 3 files for student/employee
  } else {
    // Show admin items for user bookings
    showAdminUploadItems();
    hideStudentUploadItems();
    document.getElementById("totalCount").textContent = "7"; // 7 files for admin/user
  }

  // Reset upload state first
  resetUploadModal();

  // Show modal
  document.getElementById("uploadModal").style.display = "block";

  // Load existing files after modal is shown
  if (isFreeBooking) {
    await loadExistingFreeBookingFiles(bookingId);
  } else {
    await loadExistingFiles(bookingId);
  }
}

function closeUploadModal() {
  document.getElementById("uploadModal").style.display = "none";
  resetUploadModal();
}

function resetUploadModal() {
  uploadedFiles = {};
  updateUploadProgress();

  // Reset ALL file inputs (both admin and student)
  const fileInputs = document.querySelectorAll(".upload-file-item .file-input");
  fileInputs.forEach((input) => {
    input.value = "";
    const uploadItem = input.closest(".upload-file-item");
    if (!uploadItem) return;

    uploadItem.classList.remove("file-uploaded");

    // Hide download button and cancel button, show upload button
    const downloadBtn = uploadItem.querySelector(".download-uploaded-btn");
    const cancelBtn = uploadItem.querySelector(".cancel-file-btn");
    const uploadWrapper = uploadItem.querySelector(".file-input-wrapper");
    if (downloadBtn) downloadBtn.style.display = "none";
    if (cancelBtn) cancelBtn.style.display = "none";
    if (uploadWrapper) uploadWrapper.style.display = "block";

    // Reset status
    const status = uploadItem.querySelector(".file-upload-status");
    if (status) {
      status.className = "file-upload-status status-required";
      status.textContent = "Required for approval";
    }
  });
}

function handleFileUpload(input, fileType) {
  const file = input.files[0];
  if (!file) return;

  // Validate file size (e.g., max 10MB)
  const maxSize = 10 * 1024 * 1024; // 10MB
  if (file.size > maxSize) {
    alert("File size must be less than 10MB");
    input.value = "";
    return;
  }

  // Store file information
  uploadedFiles[fileType] = {
    file: file,
    name: file.name,
    size: file.size,
    type: file.type,
    uploadDate: new Date(),
  };

  // Update UI
  const uploadItem = input.closest(".upload-file-item");
  uploadItem.classList.add("file-uploaded");

  // Update status
  const status = uploadItem.querySelector(".file-upload-status");
  status.className = "file-upload-status status-uploaded";
  status.textContent = `Uploaded: ${file.name}`;

  // Show download and cancel buttons, hide upload wrapper
  const downloadBtn = uploadItem.querySelector(".download-uploaded-btn");
  const cancelBtn = uploadItem.querySelector(".cancel-file-btn");
  const uploadWrapper = uploadItem.querySelector(".file-input-wrapper");
  if (downloadBtn) downloadBtn.style.display = "block";
  if (cancelBtn) cancelBtn.style.display = "block";
  if (uploadWrapper) uploadWrapper.style.display = "none";
  // Update progress
  updateUploadProgress();

  console.log(`File uploaded for ${fileType}:`, file.name);
}

async function cancelFileUpload(fileType) {
  const fileData = uploadedFiles[fileType];

  if (fileData && fileData.isExisting && fileData.fileId) {
    // Delete existing file from server
    try {
      const response = await fetch(`${API_BASE_URL}/files/${fileData.fileId}`, {
        method: "DELETE",
      });

      if (!response.ok) {
        throw new Error("Failed to delete file from server");
      }
    } catch (error) {
      console.error("Error deleting file from server:", error);
      alert("Failed to delete file from server");
      return; // Don't remove from UI if server deletion failed
    }
  }

  // Remove file from uploadedFiles
  delete uploadedFiles[fileType];

  // Find the upload item and reset it
  const uploadItem = document.querySelector(`[data-file-type="${fileType}"]`);
  if (uploadItem) {
    uploadItem.classList.remove("file-uploaded");

    // Reset file input
    const fileInput = uploadItem.querySelector(".file-input");
    if (fileInput) fileInput.value = "";

    // Show upload wrapper, hide download and cancel buttons
    const downloadBtn = uploadItem.querySelector(".download-uploaded-btn");
    const cancelBtn = uploadItem.querySelector(".cancel-file-btn");
    const uploadWrapper = uploadItem.querySelector(".file-input-wrapper");
    if (downloadBtn) downloadBtn.style.display = "none";
    if (cancelBtn) cancelBtn.style.display = "none";
    if (uploadWrapper) uploadWrapper.style.display = "block";

    // Reset status
    const status = uploadItem.querySelector(".file-upload-status");
    status.className = "file-upload-status status-required";
    status.textContent = "Required for approval";
  }

  updateUploadProgress();
  console.log(`File cancelled for ${fileType}`);
}

async function downloadUploadedFile(fileType) {
  const fileData = uploadedFiles[fileType];
  if (!fileData) {
    alert("No file uploaded for this document type.");
    return;
  }

  try {
    if (fileData.isExisting && fileData.fileId) {
      // Download from server
      const numericBookingId = currentBookingId
        .toString()
        .replace("BK", "")
        .replace(/^0+/, "");
      const downloadUrl = `/api/bookings/${numericBookingId}/files/${fileData.fileId}/download`;

      console.log("Downloading existing file from:", downloadUrl);

      // Create a temporary link to download the file
      const a = document.createElement("a");
      a.href = downloadUrl;
      a.download = fileData.name;
      a.target = "_blank";
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    } else if (fileData.file) {
      // Download from local file object (newly selected)
      console.log("Downloading locally selected file:", fileData.name);
      const url = URL.createObjectURL(fileData.file);
      const a = document.createElement("a");
      a.href = url;
      a.download = fileData.name;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } else {
      alert("File data is incomplete");
    }

    console.log(`Downloaded file: ${fileData.name}`);
  } catch (error) {
    console.error("Error downloading file:", error);
    alert("Failed to download file: " + error.message);
  }
}

function updateUploadProgress() {
  // **FIX: Get total from DOM - will be 3 for students, 7 for users**
  const totalFiles =
    parseInt(document.getElementById("totalCount").textContent) || 7;
  const uploadedCount = Object.keys(uploadedFiles).length;

  document.getElementById("uploadedCount").textContent = uploadedCount;
  // totalCount is already set in the HTML element

  const progressPercentage =
    totalFiles > 0 ? (uploadedCount / totalFiles) * 100 : 0;
  document.getElementById("uploadProgressFill").style.width =
    progressPercentage + "%";

  // Enable/disable save button
  const saveBtn = document.getElementById("saveUploadBtn");
  if (saveBtn) {
    saveBtn.disabled = uploadedCount === 0;
  }
}

// Save uploaded files - routes to correct function based on booking type
async function saveUploadedFiles() {
  // Check if this is a student/employee booking by checking which file types are visible
  const freeBookingFileTypes = [
    "permission_letter",
    "request_letter",
    "approval_letter",
  ];
  const isFreeBookingUpload = freeBookingFileTypes.some((type) => {
    const item = document.querySelector(`[data-file-type="${type}"]`);
    return item && item.style.display !== "none";
  });

  if (isFreeBookingUpload) {
    return await saveFreeBookingUploadedFiles();
  } else {
    return await saveAdminUserUploadedFiles();
  }
}

// Save uploaded files for admin/user bookings
async function saveAdminUserUploadedFiles() {
  const newFiles = Object.keys(uploadedFiles).filter(
    (fileType) => !uploadedFiles[fileType].isExisting,
  );

  if (newFiles.length === 0) {
    alert("No new files to save.");
    return;
  }

  const saveBtn = document.getElementById("saveUploadBtn");
  const originalText = saveBtn.innerHTML;
  saveBtn.innerHTML = "💾 Saving...";
  saveBtn.disabled = true;

  try {
    const formData = new FormData();

    // Admin/user files use file_type as key
    newFiles.forEach((fileType) => {
      const fileData = uploadedFiles[fileType];
      formData.append(fileType, fileData.file);
    });

    // Log for debugging
    console.log("Uploading files for booking:", currentBookingId);
    console.log("New files:", newFiles);
    console.log("FormData keys:", Array.from(formData.keys()));

    const response = await fetch(`${API_BASE_URL}/${currentBookingId}/upload`, {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const data = await response.json();

    if (data.success) {
      alert(
        `Successfully uploaded ${
          newFiles.length
        } document(s) for booking BK${String(currentBookingId).padStart(
          3,
          "0",
        )}`,
      );

      // Mark files as existing
      newFiles.forEach((fileType) => {
        uploadedFiles[fileType].isExisting = true;
        if (data.files) {
          const serverFile = data.files.find((f) => f.file_type === fileType);
          if (serverFile) {
            uploadedFiles[fileType].fileId = serverFile.id;
          }
        }
      });

      updateUploadProgress();

      // Show the download and cancel buttons for uploaded files
      newFiles.forEach((fileType) => {
        const uploadItem = document.querySelector(
          `[data-file-type="${fileType}"]`,
        );
        if (uploadItem) {
          const downloadBtn = uploadItem.querySelector(
            ".download-uploaded-btn",
          );
          const cancelBtn = uploadItem.querySelector(".cancel-file-btn");
          const uploadWrapper = uploadItem.querySelector(".file-input-wrapper");
          if (downloadBtn) downloadBtn.style.display = "block";
          if (cancelBtn) cancelBtn.style.display = "block";
          if (uploadWrapper) uploadWrapper.style.display = "none";
        }
      });

      // Reload the booking details to show new files
      if (
        document.getElementById("viewBookingModal").style.display === "block"
      ) {
        viewBooking(currentBookingId);
      }
    } else {
      alert(data.message || "Failed to upload files");
    }
  } catch (error) {
    console.error("Error uploading files:", error);
    alert("Failed to upload files: " + error.message);
  } finally {
    saveBtn.innerHTML = originalText;
    saveBtn.disabled = false;
  }
}

// Save uploaded files for free bookings (student and employee)
async function saveFreeBookingUploadedFiles() {
  const newFiles = Object.keys(uploadedFiles).filter(
    (fileType) => !uploadedFiles[fileType].isExisting,
  );

  if (newFiles.length === 0) {
    alert("No new files to save.");
    return;
  }

  const saveBtn = document.getElementById("saveUploadBtn");
  const originalText = saveBtn.innerHTML;
  saveBtn.innerHTML = "💾 Saving...";
  saveBtn.disabled = true;

  try {
    const formData = new FormData();

    // Free booking files (student and employee) use array format with proper indices
    newFiles.forEach((fileType, index) => {
      const fileData = uploadedFiles[fileType];
      formData.append("files[]", fileData.file);
    });

    console.log("Uploading free booking files for booking:", currentBookingId);
    console.log("New files:", newFiles);

    const response = await fetch(`/api/bookings/${currentBookingId}/upload`, {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const data = await response.json();

    if (data.success) {
      alert(
        `Successfully uploaded ${
          newFiles.length
        } document(s) for booking BK${String(currentBookingId).padStart(
          3,
          "0",
        )}`,
      );

      // Mark files as existing
      newFiles.forEach((fileType) => {
        uploadedFiles[fileType].isExisting = true;
        if (data.files) {
          const serverFile = data.files.find((f) => f.file_type === fileType);
          if (serverFile) {
            uploadedFiles[fileType].fileId = serverFile.id;
          }
        }
      });

      updateUploadProgress();

      // Show the download and cancel buttons for uploaded files
      newFiles.forEach((fileType) => {
        const uploadItem = document.querySelector(
          `[data-file-type="${fileType}"]`,
        );
        if (uploadItem) {
          const downloadBtn = uploadItem.querySelector(
            ".download-uploaded-btn",
          );
          const cancelBtn = uploadItem.querySelector(".cancel-file-btn");
          const uploadWrapper = uploadItem.querySelector(".file-input-wrapper");
          if (downloadBtn) downloadBtn.style.display = "block";
          if (cancelBtn) cancelBtn.style.display = "block";
          if (uploadWrapper) uploadWrapper.style.display = "none";
        }
      });

      // Reload the booking details to show new files
      if (
        document.getElementById("viewBookingModal").style.display === "block"
      ) {
        viewBooking(currentBookingId);
      }
    } else {
      alert(data.message || "Failed to upload files");
    }
  } catch (error) {
    console.error("Error uploading files:", error);
    alert("Failed to upload files: " + error.message);
  } finally {
    saveBtn.innerHTML = originalText;
    saveBtn.disabled = false;
  }
}

async function loadExistingFiles(bookingId) {
  try {
    const numericBookingId = bookingId.replace("BK", "").replace(/^0+/, "");
    const response = await fetch(`${API_BASE_URL}/${numericBookingId}/files`);

    if (response.ok) {
      const data = await response.json();

      if (data.success && data.files && data.files.length > 0) {
        // Process existing files
        data.files.forEach((file) => {
          const fileType = file.file_type;

          // Add to uploadedFiles object
          uploadedFiles[fileType] = {
            fileId: file.id,
            name: file.original_filename,
            size: file.file_size,
            type: file.mime_type,
            uploadDate: new Date(file.upload_date),
            isExisting: true, // Flag to indicate this is from server
          };

          // Update UI for this file type
          const uploadItem = document.querySelector(
            `[data-file-type="${fileType}"]`,
          );
          if (uploadItem) {
            uploadItem.classList.add("file-uploaded");

            // Update status
            const status = uploadItem.querySelector(".file-upload-status");
            status.className = "file-upload-status status-uploaded";
            status.textContent = `Uploaded: ${file.original_filename}`;

            // Show download and cancel buttons, hide upload wrapper
            const downloadBtn = uploadItem.querySelector(
              ".download-uploaded-btn",
            );
            const cancelBtn = uploadItem.querySelector(".cancel-file-btn");
            const uploadWrapper = uploadItem.querySelector(
              ".file-input-wrapper",
            );
            if (downloadBtn) downloadBtn.style.display = "block";
            if (cancelBtn) cancelBtn.style.display = "block";
            if (uploadWrapper) uploadWrapper.style.display = "none";
          }
        });

        // Update progress
        updateUploadProgress();
      }
    }
  } catch (error) {
    console.error("Error loading existing files:", error);
    // Don't show error to user, just log it
  }
}

async function loadExistingFreeBookingFiles(bookingId) {
  try {
    const numericBookingId = bookingId.replace("BK", "").replace(/^0+/, "");
    const response = await fetch(
      `${API_BASE_URL}/student/bookings/${numericBookingId}/files`,
    );

    if (response.ok) {
      const data = await response.json();

      if (data.success && data.files && data.files.length > 0) {
        // Process existing files
        data.files.forEach((file) => {
          const fileType = file.file_type;

          // Add to uploadedFiles object
          uploadedFiles[fileType] = {
            fileId: file.id,
            name: file.original_filename,
            size: file.file_size,
            type: file.mime_type,
            uploadDate: new Date(file.upload_date),
            isExisting: true, // Flag to indicate this is from server
          };

          // Update UI for this file type
          const uploadItem = document.querySelector(
            `[data-file-type="${fileType}"]`,
          );
          if (uploadItem) {
            uploadItem.classList.add("file-uploaded");

            // Update status
            const status = uploadItem.querySelector(".file-upload-status");
            status.className = "file-upload-status status-uploaded";
            status.textContent = `Uploaded: ${file.original_filename}`;

            // Show download and cancel buttons, hide upload wrapper
            const downloadBtn = uploadItem.querySelector(
              ".download-uploaded-btn",
            );
            const cancelBtn = uploadItem.querySelector(".cancel-file-btn");
            const uploadWrapper = uploadItem.querySelector(
              ".file-input-wrapper",
            );
            if (downloadBtn) downloadBtn.style.display = "block";
            if (cancelBtn) cancelBtn.style.display = "block";
            if (uploadWrapper) uploadWrapper.style.display = "none";
          }
        });

        // Update progress
        updateUploadProgress();
      }
    }
  } catch (error) {
    console.error("Error loading existing free booking files:", error);
    // Don't show error to user, just log it
  }
}

// Setup delete confirmation validation
function setupDeleteConfirmation() {
  const confirmCheckbox = document.getElementById("confirmDelete");
  const deleteBtn = document.getElementById("confirmDeleteBtn");

  if (confirmCheckbox && deleteBtn) {
    confirmCheckbox.addEventListener("change", function () {
      deleteBtn.disabled = !this.checked;
    });
  }
}

// Utility functions
function showLoading(show) {
  document.getElementById("loadingIndicator").style.display = show
    ? "block"
    : "none";
}

function showTable(show) {
  document.getElementById("bookingsTable").style.display = show
    ? "table"
    : "none";
}

function showError(message) {
  const errorElement = document.getElementById("errorMessage");
  errorElement.textContent = message;
  errorElement.style.display = "block";
}

function hideError() {
  document.getElementById("errorMessage").style.display = "none";
}

function formatDate(dateString) {
  if (!dateString) return "N/A";
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "Invalid Date";
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  } catch (error) {
    console.warn("Error formatting date:", error);
    return "Invalid Date";
  }
}

function formatNumber(number) {
  if (number === null || number === undefined || isNaN(number)) return "0";
  return new Intl.NumberFormat().format(number);
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modals = document.querySelectorAll(".modal");
  modals.forEach((modal) => {
    if (event.target === modal) {
      modal.style.display = "none";
    }
  });
};

// Add event listeners for file checkboxes
document.addEventListener("change", function (event) {
  if (event.target.classList.contains("file-checkbox")) {
    const fileItem = event.target.closest(".file-item");
    if (event.target.checked) {
      fileItem.classList.add("selected");
      fileItem.classList.add("selecting");
      setTimeout(() => fileItem.classList.remove("selecting"), 300);
    } else {
      fileItem.classList.remove("selected");
    }
  }
}); // <-- This closing brace and parenthesis was missing

// View decline reason for cancelled bookings

function viewDeclineReason(bookingId) {
  console.log("viewDeclineReason called with bookingId:", bookingId);

  // Find the booking data
  const booking = bookingsData.find((b) => b.id == bookingId);

  if (!booking) {
    alert("Booking not found");
    return;
  }

  if (booking.status !== "cancelled") {
    alert("This booking is not cancelled");
    return;
  }

  // Set content for decline reason modal
  document.getElementById("declineReasonBookingId").textContent = `#BK${String(
    booking.id,
  ).padStart(3, "0")}`;
  document.getElementById("declineReasonClientName").textContent =
    booking.client_name;
  document.getElementById("viewDeclineReason").textContent =
    booking.decline_reason || "No reason provided";
  document.getElementById("viewDeclineNotes").textContent =
    booking.decline_notes || "No additional notes";

  // Show the modal
  document.getElementById("declineReasonModal").style.display = "block";
}

function displayEquipmentOption(equipment) {
  const availabilityClass =
    equipment.status === "out_of_stock" ? "out-of-stock" : "available";
  const availabilityText =
    equipment.status === "out_of_stock"
      ? "Out of Stock"
      : `${equipment.available} available`;

  return `
        <div class="equipment-item ${availabilityClass}" data-id="${
          equipment.id
        }">
            <span class="equipment-name">${equipment.name}</span>
            <span class="equipment-price">?${equipment.rate} ${
              equipment.unit
            }</span>
            <span class="equipment-availability ${availabilityClass}">${availabilityText}</span>
            <input type="number" 
                   max="${equipment.available}" 
                   min="0" 
                   ${equipment.status === "out_of_stock" ? "disabled" : ""}
                   class="equipment-quantity">
        </div>
    `;
}
function downloadEquipmentRequestForm(bookingId) {
  if (!bookingId) {
    console.error("[Equipment Request Form] No booking ID provided");
    alert("? Error: No booking ID provided");
    return;
  }

  try {
    console.log(
      `[Equipment Request Form] Starting download for booking #${bookingId}`,
    );

    alert(
      `Generating equipment request form for booking BK${String(
        bookingId,
      ).padStart(3, "0")}...`,
    );

    const downloadUrl = `${API_BASE_URL}/${bookingId}/equipment-request-form`;
    console.log(`[Equipment Request Form] Download URL: ${downloadUrl}`);

    const a = document.createElement("a");
    a.href = downloadUrl;
    a.target = "_blank";
    a.style.display = "none";
    document.body.appendChild(a);

    console.log(
      `[Equipment Request Form] Download element created with href: ${a.href}`,
    );

    a.click();

    console.log(`[Equipment Request Form] Download click triggered`);

    setTimeout(() => {
      document.body.removeChild(a);
      console.log(`[Equipment Request Form] Download element removed from DOM`);
    }, 100);

    console.log(
      `[Equipment Request Form] Download initiated for booking: ${bookingId}`,
    );
  } catch (error) {
    console.error(`[Equipment Request Form] Error initiating download:`, error);
    console.error(`[Equipment Request Form] Error message: ${error.message}`);
    alert(`? Failed to download Equipment Request Form: ${error.message}`);
  }
}

function downloadMoa(bookingId) {
  if (!bookingId) {
    console.error("[MOA] No booking ID provided");
    alert("? Error: No booking ID provided");
    return;
  }

  try {
    console.log(`[MOA] Starting download for booking #${bookingId}`);

    alert(
      `Generating MOA for booking BK${String(bookingId).padStart(3, "0")}...`,
    );

    const downloadUrl = `${API_BASE_URL}/${bookingId}/moa`;
    console.log(`[MOA] Download URL: ${downloadUrl}`);

    const a = document.createElement("a");
    a.href = downloadUrl;
    a.target = "_blank";
    a.style.display = "none";
    document.body.appendChild(a);

    console.log(`[MOA] Download element created with href: ${a.href}`);

    a.click();

    console.log(`[MOA] Download click triggered`);

    setTimeout(() => {
      document.body.removeChild(a);
      console.log(`[MOA] Download element removed from DOM`);
    }, 100);

    console.log(`[MOA] Download initiated for booking: ${bookingId}`);
  } catch (error) {
    console.error(`[MOA] Error initiating download:`, error);
    console.error(`[MOA] Error message: ${error.message}`);
    alert(`? Failed to download MOA: ${error.message}`);
  }
}
async function downloademployeeEvaluation(bookingId) {
  if (!bookingId) {
    console.error("[Employee Evaluation] No booking ID provided");
    alert("? Error: No booking ID provided");
    return;
  }

  try {
    console.log(
      `[Employee Evaluation] Starting download for booking #${bookingId}`,
    );

    // First, check if there's a submitted survey evaluation file
    console.log(
      `[Employee Evaluation] Fetching survey files from: /api/survey-files/${bookingId}`,
    );

    const surveyFilesResponse = await fetch(`/api/survey-files/${bookingId}`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    console.log(
      `[Employee Evaluation] Survey files response status: ${surveyFilesResponse.status}`,
    );
    console.log(
      `[Employee Evaluation] Survey files response OK: ${surveyFilesResponse.ok}`,
    );

    if (surveyFilesResponse.ok) {
      const surveyResult = await surveyFilesResponse.json();
      console.log(`[Employee Evaluation] Survey result:`, surveyResult);

      // If survey file exists, download it
      if (
        surveyResult.success &&
        surveyResult.files &&
        surveyResult.files.length > 0
      ) {
        const surveyFile = surveyResult.files[0]; // Get the most recent one
        console.log(
          `[Employee Evaluation] Found survey evaluation file:`,
          surveyFile,
        );
        console.log(`[Employee Evaluation] File name: ${surveyFile.name}`);
        console.log(`[Employee Evaluation] File URL: ${surveyFile.url}`);
        console.log(
          `[Employee Evaluation] File size: ${surveyFile.size} bytes`,
        );

        // Validate file exists and is accessible
        try {
          const fileCheckResponse = await fetch(surveyFile.url, {
            method: "HEAD",
            headers: {
              "X-Requested-With": "XMLHttpRequest",
            },
          });
          console.log(
            `[Employee Evaluation] File accessibility check status: ${fileCheckResponse.status}`,
          );

          if (!fileCheckResponse.ok) {
            console.warn(
              `[Employee Evaluation] File may not be accessible (HTTP ${fileCheckResponse.status})`,
            );
          }
        } catch (checkError) {
          console.warn(
            `[Employee Evaluation] Could not verify file accessibility:`,
            checkError,
          );
        }

        // Download the survey file
        console.log(`[Employee Evaluation] Creating download link...`);
        const a = document.createElement("a");
        a.href = surveyFile.url;
        a.download = surveyFile.name;
        a.style.display = "none";
        document.body.appendChild(a);

        console.log(
          `[Employee Evaluation] Download element created with href: ${a.href}`,
        );
        console.log(`[Employee Evaluation] Download filename: ${a.download}`);

        a.click();

        console.log(`[Employee Evaluation] Download click triggered`);

        // Remove after a delay to allow download to start
        setTimeout(() => {
          document.body.removeChild(a);
          console.log(
            `[Employee Evaluation] Download element removed from DOM`,
          );
        }, 100);

        console.log(
          `[Employee Evaluation] Survey evaluation file download completed: ${surveyFile.name}`,
        );
        return;
      } else {
        console.log(
          `[Employee Evaluation] No survey files found in response. Success: ${
            surveyResult.success
          }, Files count: ${surveyResult.files ? surveyResult.files.length : 0}`,
        );
      }
    } else {
      console.warn(
        `[Employee Evaluation] Survey files API returned status ${surveyFilesResponse.status}`,
      );
      const errorText = await surveyFilesResponse.text();
      console.warn(`[Employee Evaluation] Response body:`, errorText);
    }
  } catch (error) {
    console.error(`[Employee Evaluation] Error fetching survey file:`, error);
    console.error(`[Employee Evaluation] Error message: ${error.message}`);
    console.error(`[Employee Evaluation] Error stack: ${error.stack}`);
    console.log(`[Employee Evaluation] Falling back to template generation...`);
  }

  // If no survey file exists, generate blank template
  console.log(
    `[Employee Evaluation] No survey file found or error occurred, generating blank template`,
  );
  alert(
    `Generating Employee Evaluation Form for booking BK${String(
      bookingId,
    ).padStart(3, "0")}...`,
  );

  const downloadUrl = `${API_BASE_URL}/${bookingId}/employee-evaluation`;
  console.log(`[Employee Evaluation] Template download URL: ${downloadUrl}`);

  try {
    const a = document.createElement("a");
    a.href = downloadUrl;
    a.target = "_blank";
    a.style.display = "none";
    document.body.appendChild(a);

    console.log(
      `[Employee Evaluation] Template download element created with href: ${a.href}`,
    );

    a.click();

    console.log(`[Employee Evaluation] Template download click triggered`);

    setTimeout(() => {
      document.body.removeChild(a);
      console.log(
        `[Employee Evaluation] Template download element removed from DOM`,
      );
    }, 100);

    console.log(
      `[Employee Evaluation] Employee Evaluation template download initiated for booking: ${bookingId}`,
    );
  } catch (error) {
    console.error(
      `[Employee Evaluation] Error initiating template download:`,
      error,
    );
    console.error(`[Employee Evaluation] Error message: ${error.message}`);
    alert(`? Failed to download Employee Evaluation Form: ${error.message}`);
  }
}

function downloadInspectionEvaluation(bookingId) {
  if (!bookingId) {
    console.error("[Inspection Evaluation] No booking ID provided");
    alert("? Error: No booking ID provided");
    return;
  }

  try {
    console.log(
      `[Inspection Evaluation] Starting download for booking #${bookingId}`,
    );

    alert(
      `Generating Inspection and Evaluation Form for booking BK${String(
        bookingId,
      ).padStart(3, "0")}...`,
    );

    const downloadUrl = `${API_BASE_URL}/${bookingId}/inspection-evaluation`;
    console.log(`[Inspection Evaluation] Download URL: ${downloadUrl}`);

    const a = document.createElement("a");
    a.href = downloadUrl;
    a.target = "_blank";
    a.style.display = "none";
    document.body.appendChild(a);

    console.log(
      `[Inspection Evaluation] Download element created with href: ${a.href}`,
    );

    a.click();

    console.log(`[Inspection Evaluation] Download click triggered`);

    setTimeout(() => {
      document.body.removeChild(a);
      console.log(`[Inspection Evaluation] Download element removed from DOM`);
    }, 100);

    console.log(
      `[Inspection Evaluation] Download initiated for booking: ${bookingId}`,
    );
  } catch (error) {
    console.error(`[Inspection Evaluation] Error initiating download:`, error);
    console.error(`[Inspection Evaluation] Error message: ${error.message}`);
    alert(`? Failed to download Inspection Evaluation Form: ${error.message}`);
  }
}

function downloadOrderOfPayment(bookingId) {
  if (!bookingId) {
    console.error("[Order of Payment] No booking ID provided");
    alert("? Error: No booking ID provided");
    return;
  }

  try {
    console.log(
      `[Order of Payment] Starting download for booking #${bookingId}`,
    );

    alert(
      `Generating Order of Payment for booking BK${String(bookingId).padStart(
        3,
        "0",
      )}...`,
    );

    const downloadUrl = `${API_BASE_URL}/${bookingId}/order-of-payment`;
    console.log(`[Order of Payment] Download URL: ${downloadUrl}`);

    const a = document.createElement("a");
    a.href = downloadUrl;
    a.target = "_blank";
    a.style.display = "none";
    document.body.appendChild(a);

    console.log(
      `[Order of Payment] Download element created with href: ${a.href}`,
    );

    a.click();

    console.log(`[Order of Payment] Download click triggered`);

    setTimeout(() => {
      document.body.removeChild(a);
      console.log(`[Order of Payment] Download element removed from DOM`);
    }, 100);

    console.log(
      `[Order of Payment] Download initiated for booking: ${bookingId}`,
    );
  } catch (error) {
    console.error(`[Order of Payment] Error initiating download:`, error);
    console.error(`[Order of Payment] Error message: ${error.message}`);
    alert(`? Failed to download Order of Payment: ${error.message}`);
  }
}

// Dropdown toggle functionality
document.addEventListener("DOMContentLoaded", function () {
  // Get all dropdown toggles
  const dropdownToggles = document.querySelectorAll(".dropdown-toggle");

  dropdownToggles.forEach((toggle) => {
    toggle.addEventListener("click", function (e) {
      e.preventDefault();

      // Get the parent dropdown element
      const dropdown = this.closest(".dropdown");

      // Close other dropdowns
      document.querySelectorAll(".dropdown").forEach((otherDropdown) => {
        if (otherDropdown !== dropdown) {
          otherDropdown.classList.remove("open");
        }
      });

      // Toggle current dropdown
      dropdown.classList.toggle("open");
    });
  });

  // Close dropdown when clicking outside
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".dropdown")) {
      document.querySelectorAll(".dropdown").forEach((dropdown) => {
        dropdown.classList.remove("open");
      });
    }
  });

  // Handle submenu item clicks
  const submenuItems = document.querySelectorAll(".submenu-item");
  submenuItems.forEach((item) => {
    item.addEventListener("click", function (e) {
      // Remove active class from all submenu items
      submenuItems.forEach((i) => i.classList.remove("active"));

      // Add active class to clicked item
      this.classList.add("active");
    });
  });
});

// Load free booking files (student and employee)
async function loadFreeBookingFiles(bookingId) {
  try {
    const response = await fetch(`/api/bookings/${bookingId}/files`);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();

    const container = document.getElementById("studentFilesContainer");

    if (data.success && data.files && data.files.length > 0) {
      const fileTypeNames = {
        permission_letter: "Permission Letter",
        request_letter: "Request Letter",
        approval_letter: "Approval Letter",
      };

      container.innerHTML = data.files
        .map(
          (file) => `
        <div class="file-display-item" style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #f5f5f5; border-radius: 8px; margin-bottom: 10px;">
          <div class="file-icon" style="font-size: 2rem;">📄</div>
          <div class="file-info" style="flex: 1;">
            <div class="file-name" style="font-weight: 600; color: var(--dark);">
              ${fileTypeNames[file.file_type] || file.file_type}
            </div>
            <div class="file-meta" style="font-size: 0.85rem; color: var(--gray);">
              ${
                file.file_size ? formatFileSize(file.file_size) : "Unknown size"
              } � ${
                file.upload_date
                  ? new Date(file.upload_date).toLocaleDateString()
                  : "Unknown date"
              }
            </div>
          </div>
          <button class="btn btn-sm btn-primary" onclick="downloadFreeBookingFile(${bookingId}, ${
            file.id
          })">
            📥 Download
          </button>
        </div>
      `,
        )
        .join("");
    } else {
      container.innerHTML =
        '<p style="color: var(--gray); font-style: italic; text-align: center; padding: 20px;">No documents uploaded yet.</p>';
    }
  } catch (error) {
    console.error("Error loading free booking files:", error);
    document.getElementById("studentFilesContainer").innerHTML =
      '<p style="color: var(--danger); text-align: center; padding: 20px;">Failed to load documents.</p>';
  }
}

// Download free booking file (student or employee)
function downloadFreeBookingFile(bookingId, fileId) {
  const url = `/api/bookings/${bookingId}/files/${fileId}/download`;
  const a = document.createElement("a");
  a.href = url;
  a.target = "_blank";
  a.click();
}

// Download student file (alias for backward compatibility)
function downloadStudentFile(bookingId, fileId) {
  return downloadFreeBookingFile(bookingId, fileId);
}

// Load student submitted files (kept for backward compatibility)
async function loadStudentFiles(bookingId) {
  try {
    const response = await fetch(`/api/bookings/${bookingId}/files`);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();

    const container = document.getElementById("studentFilesContainer");

    if (data.success && data.files && data.files.length > 0) {
      const fileTypeNames = {
        permission_letter: "Permission Letter",
        request_letter: "Request Letter",
        approval_letter: "Approval Letter",
      };

      container.innerHTML = data.files
        .map(
          (file) => `
        <div class="file-display-item" style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #f5f5f5; border-radius: 8px; margin-bottom: 10px;">
          <div class="file-icon" style="font-size: 2rem;">📄</div>
          <div class="file-info" style="flex: 1;">
            <div class="file-name" style="font-weight: 600; color: var(--dark);">
              ${fileTypeNames[file.file_type] || file.file_type}
            </div>
            <div class="file-meta" style="font-size: 0.85rem; color: var(--gray);">
              ${
                file.file_size ? formatFileSize(file.file_size) : "Unknown size"
              } � ${
                file.upload_date
                  ? new Date(file.upload_date).toLocaleDateString()
                  : "Unknown date"
              }
            </div>
          </div>
          <button class="btn btn-sm btn-primary" onclick="downloadStudentFile(${bookingId}, ${
            file.id
          })">
            📥 Download
          </button>
        </div>
      `,
        )
        .join("");
    } else {
      container.innerHTML =
        '<p style="color: var(--gray); font-style: italic; text-align: center; padding: 20px;">No documents uploaded yet.</p>';
    }
  } catch (error) {
    console.error("Error loading student files:", error);
    document.getElementById("studentFilesContainer").innerHTML =
      '<p style="color: var(--danger); text-align: center; padding: 20px;">Failed to load documents.</p>';
  }
}

// Download student file
function downloadStudentFile(bookingId, fileId) {
  const url = `/api/bookings/${bookingId}/files/${fileId}/download`;
  const a = document.createElement("a");
  a.href = url;
  a.target = "_blank";
  a.click();
}

// Format file size
function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
}

async function loadUploadedFiles(bookingId) {
  try {
    const response = await fetch(`${API_BASE_URL}/${bookingId}/files`);
    const data = await response.json();

    const container = document.getElementById("uploadedFilesContainer");

    if (data.success && data.files && data.files.length > 0) {
      container.innerHTML = data.files
        .map(
          (file) => `
        <div class="file-display-item">
          <div class="file-icon">📄</div>
          <div class="file-info">
            <div class="file-name">${file.original_filename}</div>
            <div class="file-meta">${formatFileSize(
              file.file_size,
            )} � ${new Date(file.upload_date).toLocaleDateString()}</div>
          </div>
          <button class="btn btn-sm btn-primary" onclick="downloadUploadedDocument(${bookingId}, ${
            file.id
          })">
            📥 Download
          </button>
        </div>
      `,
        )
        .join("");
    } else {
      container.innerHTML =
        '<p style="color: var(--gray); font-style: italic; text-align: center;">No documents uploaded yet.</p>';
    }
  } catch (error) {
    console.error("Error loading uploaded files:", error);
    container.innerHTML =
      '<p style="color: var(--danger); text-align: center;">Failed to load documents.</p>';
  }
}

function downloadUploadedDocument(bookingId, fileId) {
  const url = `${API_BASE_URL}/${bookingId}/files/${fileId}/download`;
  const a = document.createElement("a");
  a.href = url;
  a.target = "_blank";
  a.click();
}

function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
}

async function loadAdminUploadedFiles(bookingId) {
  try {
    const response = await fetch(`${API_BASE_URL}/${bookingId}/files`);
    const data = await response.json();

    const container = document.getElementById("adminUploadedFiles");

    if (data.success && data.files && data.files.length > 0) {
      const fileTypeNames = {
        moa: "Memorandum of Agreement",
        billing: "Billing Statement",
        equipment: "Equipment Request Form",
        evaluation: "Employee Evaluation",
        inspection: "Inspection Report",
        receipt: "Payment Receipt",
        orderofpayment: "Order of Payment",
      };

      container.innerHTML = data.files
        .map(
          (file) => `
          <div class="file-display-item">
            <div class="file-icon">📄</div>
            <div class="file-info">
              <div class="file-name">${
                fileTypeNames[file.file_type] || file.file_type
              }</div>
              <div class="file-meta">${formatFileSize(
                file.file_size,
              )} � ${new Date(file.upload_date).toLocaleDateString()}</div>
            </div>
            <button class="btn btn-sm btn-primary" onclick="downloadAdminFile(${bookingId}, ${
              file.id
            })">
              📥 Download
            </button>
          </div>
        `,
        )
        .join("");
    } else {
      container.innerHTML =
        '<p style="color: var(--gray); font-style: italic; text-align: center;">No documents uploaded yet.</p>';
    }
  } catch (error) {
    console.error("Error loading admin files:", error);
    document.getElementById("adminUploadedFiles").innerHTML =
      '<p style="color: var(--danger); text-align: center;">Failed to load documents.</p>';
  }
}

// Download admin file
function downloadAdminFile(bookingId, fileId) {
  const url = `${API_BASE_URL}/${bookingId}/files/${fileId}/download`;
  const a = document.createElement("a");
  a.href = url;
  a.target = "_blank";
  a.click();
}

// Load survey data for booking - now only loads evaluation files
async function loadSurveyData(bookingId) {
  try {
    console.log(`[Survey] Loading survey files for booking ID: ${bookingId}`);

    const response = await fetch(`/api/survey-files/${bookingId}`);

    console.log(`[Survey] API Response Status: ${response.status}`);

    if (!response.ok) {
      console.error(`[Survey] HTTP error! status: ${response.status}`);
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    console.log(`[Survey] API Response Data:`, data);

    const container = document.getElementById("surveyContent");

    if (data.success && data.files && data.files.length > 0) {
      console.log(`[Survey] Found ${data.files.length} evaluation files`);

      // Display evaluation files with proper styling
      let filesHTML = "";

      data.files.forEach((file) => {
        console.log(`[Survey] Processing file:`, file);

        const fileDate = new Date(file.created * 1000).toLocaleDateString(
          "en-US",
          {
            year: "numeric",
            month: "short",
            day: "numeric",
          },
        );

        // Create secure download URL using the correct route
        const downloadUrl = `/api/bookings/evaluation-file/${encodeURIComponent(
          file.name,
        )}`;

        filesHTML += `
          <div class="file-list-item" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f9f9f9; border-radius: 4px; margin-bottom: 8px; border: 1px solid #e0e0e0;">
            <div style="display: flex; align-items: center; flex: 1;">
              <div class="file-icon" style="font-size: 24px; margin-right: 12px;">📄</div>
              <div>
                <div style="font-weight: 500; color: #333; margin-bottom: 2px;">${file.name}</div>
                <div style="font-size: 12px; color: #666;">📅 ${fileDate}</div>
              </div>
            </div>
            <button class="btn btn-sm btn-primary" onclick="downloadFileFromUrl('${downloadUrl}')" title="Download" style="background-color: #1e90ff; color: white; padding: 6px 12px; border: none; border-radius: 3px; cursor: pointer; font-weight: 500;">
              📥 Download
            </button>
          </div>
        `;
      });

      container.innerHTML = filesHTML;
      console.log(`[Survey] Files loaded successfully`);
    } else {
      console.log(
        `[Survey] No files found. Success: ${data.success}, Files: ${data.files}`,
      );
      container.innerHTML = `
        <p style="color: var(--gray); font-style: italic; text-align: center; padding: 20px;">
          📋 No evaluation forms submitted yet. Forms will appear here after the client completes the survey.
        </p>
      `;
    }
  } catch (error) {
    console.error("[Survey] Error loading survey data:", error);
    console.error("[Survey] Error Stack:", error.stack);
    const container = document.getElementById("surveyContent");
    container.innerHTML = `
      <p style="color: red; text-align: center; padding: 20px;">
        ? Error loading survey files: ${error.message}<br>
        <small style="color: #666; font-size: 11px;">Check browser console for details</small>
      </p>
    `;
  }
}

// Download file from URL with error logging
function downloadFileFromUrl(url) {
  try {
    console.log(`[Download] Attempting to download from URL: ${url}`);

    if (!url || url.trim() === "") {
      console.error(`[Download] Invalid URL: ${url}`);
      alert(
        "? Download URL is invalid. Please check the console logs for more details.",
      );
      return;
    }

    const a = document.createElement("a");
    a.href = url;
    a.download = url.split("/").pop();

    console.log(`[Download] Created download element with href: ${a.href}`);
    console.log(`[Download] Download filename: ${a.download}`);

    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    console.log(`[Download] Download initiated successfully`);
  } catch (error) {
    console.error(`[Download] Error during download:`, error);
    console.error(`[Download] Error Stack:`, error.stack);
    alert(`? Download failed: ${error.message}`);
  }
}

function openStudentUploadModal(bookingId) {
  if (!bookingId) return;

  currentBookingId = bookingId;

  // Get booking data
  const booking = bookingsData.find((b) => b.id == bookingId);
  if (!booking) {
    alert("Booking not found");
    return;
  }

  // Set modal info
  document.getElementById("uploadBookingId").textContent = `BK${String(
    bookingId,
  ).padStart(3, "0")}`;
  document.getElementById("uploadClientName").textContent = booking.client_name;

  // Hide admin file upload items, show only student file items
  hideAdminUploadItems();
  showStudentUploadItems();

  // **FIX: Update total count for student bookings**
  document.getElementById("totalCount").textContent = "3";

  // Reset and load existing student files
  resetUploadModal();

  // **FIX: Clear uploadedFiles before loading**
  uploadedFiles = {};

  // Show modal first
  document.getElementById("uploadModal").style.display = "block";

  // Then load existing files
  console.log("Opening student upload modal for booking:", bookingId);
  loadExistingStudentFilesForUpload(bookingId);
}

// Hide admin file upload items
function hideAdminUploadItems() {
  const adminFileTypes = [
    "receipt",
    "moa",
    "billing",
    "equipment",
    "evaluation",
    "inspection",
    "orderofpayment",
  ];
  adminFileTypes.forEach((type) => {
    const item = document.querySelector(`[data-file-type="${type}"]`);
    if (item) item.style.display = "none";
  });
}

// Show student file upload items
function showStudentUploadItems() {
  // Create student file upload items if they don't exist
  const uploadList = document.querySelector(".upload-files-list");
  if (!uploadList) return;

  // Check if student items already exist
  if (!document.querySelector('[data-file-type="permission_letter"]')) {
    const studentUploadHTML = `
      <div class="upload-file-item" data-file-type="permission_letter">
        <div class="file-upload-info">
          <div class="file-upload-icon">📁</div>
          <div class="file-upload-details">
            <div class="file-upload-name">Permission Letter</div>
            <div class="file-upload-description">Official permission from organization adviser</div>
            <div class="file-upload-status status-required">Required for approval</div>
          </div>
        </div>
        <div class="file-upload-actions">
          <div class="file-input-wrapper">
            <input type="file" class="file-input" id="permission_letter-file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileUpload(this, 'permission_letter')">
            <label for="permission_letter-file" class="file-upload-btn">📁 Choose File</label>
          </div>
          <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadStudentUploadedFile('permission_letter')" style="display: none;">📥</button>
          <button class="btn btn-sm cancel-file-btn" onclick="cancelStudentFileUpload('permission_letter')" style="display: none;">❌</button>
        </div>
      </div>

      <div class="upload-file-item" data-file-type="request_letter">
        <div class="file-upload-info">
          <div class="file-upload-icon">📁</div>
          <div class="file-upload-details">
            <div class="file-upload-name">Request Letter</div>
            <div class="file-upload-description">Formal request letter for facility booking</div>
            <div class="file-upload-status status-required">Required for approval</div>
          </div>
        </div>
        <div class="file-upload-actions">
          <div class="file-input-wrapper">
            <input type="file" class="file-input" id="request_letter-file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileUpload(this, 'request_letter')">
            <label for="request_letter-file" class="file-upload-btn">📁 Choose File</label>
          </div>
          <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadStudentUploadedFile('request_letter')" style="display: none;">📥</button>
          <button class="btn btn-sm cancel-file-btn" onclick="cancelStudentFileUpload('request_letter')" style="display: none;">❌</button>
        </div>
      </div>

      <div class="upload-file-item" data-file-type="approval_letter">
        <div class="file-upload-info">
          <div class="file-upload-icon">📁</div>
          <div class="file-upload-details">
            <div class="file-upload-name">Approval Letter</div>
            <div class="file-upload-description">Dean/Director approval letter</div>
            <div class="file-upload-status status-required">Required for approval</div>
          </div>
        </div>
        <div class="file-upload-actions">
          <div class="file-input-wrapper">
            <input type="file" class="file-input" id="approval_letter-file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileUpload(this, 'approval_letter')">
            <label for="approval_letter-file" class="file-upload-btn">📁 Choose File</label>
          </div>
          <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadStudentUploadedFile('approval_letter')" style="display: none;">📥</button>
          <button class="btn btn-sm cancel-file-btn" onclick="cancelStudentFileUpload('approval_letter')" style="display: none;">❌</button>
        </div>
      </div>
    `;
    uploadList.insertAdjacentHTML("afterbegin", studentUploadHTML);
  } else {
    // Just show them if they already exist
    const studentFileTypes = [
      "permission_letter",
      "request_letter",
      "approval_letter",
    ];
    studentFileTypes.forEach((type) => {
      const item = document.querySelector(`[data-file-type="${type}"]`);
      if (item) item.style.display = "flex";
    });
  }
}

// Handle student file upload
function handleStudentFileUpload(input, fileType) {
  const file = input.files[0];
  if (!file) return;

  const maxSize = 10 * 1024 * 1024;
  if (file.size > maxSize) {
    alert("File size must be less than 10MB");
    input.value = "";
    return;
  }

  uploadedFiles[fileType] = {
    file: file,
    name: file.name,
    size: file.size,
    type: file.type,
    uploadDate: new Date(),
  };

  const uploadItem = input.closest(".upload-file-item");
  uploadItem.classList.add("file-uploaded");

  const status = uploadItem.querySelector(".file-upload-status");
  status.className = "file-upload-status status-uploaded";
  status.textContent = `Uploaded: ${file.name}`;

  const downloadBtn = uploadItem.querySelector(".download-uploaded-btn");
  const cancelBtn = uploadItem.querySelector(".cancel-file-btn");
  const uploadWrapper = uploadItem.querySelector(".file-input-wrapper");
  if (downloadBtn) downloadBtn.style.display = "block";
  if (cancelBtn) cancelBtn.style.display = "block";
  if (uploadWrapper) uploadWrapper.style.display = "none";

  updateUploadProgress();
}

// Cancel student file upload
async function cancelStudentFileUpload(fileType) {
  const fileData = uploadedFiles[fileType];

  if (fileData && fileData.isExisting && fileData.fileId) {
    try {
      const response = await fetch(
        `/api/bookings/${currentBookingId}/files/${fileData.fileId}`,
        {
          method: "DELETE",
        },
      );

      if (!response.ok) {
        throw new Error("Failed to delete file from server");
      }
    } catch (error) {
      console.error("Error deleting student file:", error);
      alert("Failed to delete file from server");
      return;
    }
  }

  delete uploadedFiles[fileType];

  const uploadItem = document.querySelector(`[data-file-type="${fileType}"]`);
  if (uploadItem) {
    uploadItem.classList.remove("file-uploaded");

    const fileInput = uploadItem.querySelector(".file-input");
    if (fileInput) fileInput.value = "";

    const downloadBtn = uploadItem.querySelector(".download-uploaded-btn");
    const cancelBtn = uploadItem.querySelector(".cancel-file-btn");
    const uploadWrapper = uploadItem.querySelector(".file-input-wrapper");
    if (downloadBtn) downloadBtn.style.display = "none";
    if (cancelBtn) cancelBtn.style.display = "none";
    if (uploadWrapper) uploadWrapper.style.display = "block";

    const status = uploadItem.querySelector(".file-upload-status");
    status.className = "file-upload-status status-required";
    status.textContent = "Required for approval";
  }

  updateUploadProgress();
}

// Download student uploaded file
async function downloadStudentUploadedFile(fileType) {
  const fileData = uploadedFiles[fileType];
  if (!fileData) {
    alert("No file uploaded for this document type.");
    return;
  }

  try {
    if (fileData.isExisting && fileData.fileId) {
      const downloadUrl = `/api/bookings/${currentBookingId}/files/${fileData.fileId}/download`;
      const a = document.createElement("a");
      a.href = downloadUrl;
      a.download = fileData.name;
      a.target = "_blank";
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    } else {
      const url = URL.createObjectURL(fileData.file);
      const a = document.createElement("a");
      a.href = url;
      a.download = fileData.name;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }
  } catch (error) {
    console.error("Error downloading file:", error);
    alert("Failed to download file");
  }
}

// Load existing student files for upload modal
async function loadExistingStudentFilesForUpload(bookingId) {
  try {
    console.log("Loading existing files for booking:", bookingId);

    const response = await fetch(`/api/bookings/${bookingId}/files`);

    console.log("Files API response status:", response.status);

    if (response.ok) {
      const data = await response.json();

      console.log("Files API response data:", data);

      if (data.success && data.files && data.files.length > 0) {
        console.log("Found", data.files.length, "files");

        data.files.forEach((file) => {
          const fileType = file.file_type;

          console.log(
            "Processing file type:",
            fileType,
            "File:",
            file.filename,
          );

          uploadedFiles[fileType] = {
            fileId: file.id,
            name: file.filename,
            size: file.size,
            type: file.mime_type,
            uploadDate: new Date(file.upload_date),
            isExisting: true,
          };

          const uploadItem = document.querySelector(
            `[data-file-type="${fileType}"]`,
          );

          console.log(
            "Found upload item for",
            fileType,
            ":",
            uploadItem ? "YES" : "NO",
          );

          if (uploadItem) {
            uploadItem.classList.add("file-uploaded");

            const status = uploadItem.querySelector(".file-upload-status");
            if (status) {
              status.className = "file-upload-status status-uploaded";
              status.textContent = `Uploaded: ${file.filename}`;
            }

            const downloadBtn = uploadItem.querySelector(
              ".download-uploaded-btn",
            );
            const cancelBtn = uploadItem.querySelector(".cancel-file-btn");
            const uploadWrapper = uploadItem.querySelector(
              ".file-input-wrapper",
            );
            if (downloadBtn) downloadBtn.style.display = "block";
            if (cancelBtn) cancelBtn.style.display = "block";
            if (uploadWrapper) uploadWrapper.style.display = "none";
          }
        });

        updateUploadProgress();
      } else {
        console.log("No files found in API response");
      }
    } else {
      console.error(
        "API error response:",
        response.status,
        response.statusText,
      );
    }
  } catch (error) {
    console.error("Error loading existing student files:", error);
  }
}

function showAdminUploadItems() {
  const adminFileTypes = [
    "receipt",
    "moa",
    "billing",
    "equipment",
    "evaluation",
    "inspection",
    "orderofpayment",
  ];
  adminFileTypes.forEach((type) => {
    const item = document.querySelector(`[data-file-type="${type}"]`);
    if (item) item.style.display = "flex";
  });
}

function hideAdminUploadItems() {
  const adminFileTypes = [
    "receipt",
    "moa",
    "billing",
    "equipment",
    "evaluation",
    "inspection",
    "orderofpayment",
  ];
  adminFileTypes.forEach((type) => {
    const item = document.querySelector(`[data-file-type="${type}"]`);
    if (item) item.style.display = "none";
  });
}

function showStudentUploadItems() {
  console.log("Showing student upload items...");
  const studentFileTypes = [
    "permission_letter",
    "request_letter",
    "approval_letter",
  ];
  studentFileTypes.forEach((type) => {
    const item = document.querySelector(`[data-file-type="${type}"]`);
    console.log(`Student item [${type}]:`, item ? "FOUND" : "NOT FOUND");
    if (item) {
      item.style.display = "flex";
      console.log(`Set [${type}] to display: flex`);
    }
  });
}

function hideStudentUploadItems() {
  const studentFileTypes = [
    "permission_letter",
    "request_letter",
    "approval_letter",
  ];
  studentFileTypes.forEach((type) => {
    const item = document.querySelector(`[data-file-type="${type}"]`);
    if (item) item.style.display = "none";
  });
}

// ========== FACILITY RENTAL REPORT FUNCTIONS ==========

// Populate year dropdown with current and past years
function populateYearDropdown() {
  const currentYear = new Date().getFullYear();
  const yearSelect = document.getElementById("reportYear");

  // Clear existing options except the placeholder
  yearSelect.innerHTML = '<option value="">-- Select Year --</option>';

  // Add years from current year to 5 years back
  for (let i = 0; i < 6; i++) {
    const year = currentYear - i;
    const option = document.createElement("option");
    option.value = year;
    option.textContent = year;
    yearSelect.appendChild(option);
  }
}

// Generate facility rental report from modal selection
async function generateFacilityRentalReport() {
  try {
    const month = document.getElementById("reportMonth").value;
    const year = document.getElementById("reportYear").value;

    // Validate selections
    if (!month || !year) {
      alert("Please select both month and year");
      return;
    }

    console.log(`Generating report for ${month}/${year}`);

    // Fetch and download Excel report directly
    const response = await fetch(
      `${API_BASE_URL}/generateFacilityRentalReport?month=${month}&year=${year}`,
    );

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(
        errorData.message ||
          `HTTP ${response.status}: Failed to generate report`,
      );
    }

    // Get the blob (Excel file)
    const blob = await response.blob();

    // Create download link
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;

    // Extract filename from response headers or create one
    const contentDisposition = response.headers.get("content-disposition");
    let filename = "Facility_Rental_Report.xlsx";
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
      if (filenameMatch) filename = filenameMatch[1];
    }

    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

    // Close the modal
    closeModal("facilityRentalReportModal");
    alert("Report generated and downloaded successfully!");
  } catch (error) {
    console.error("Error generating facility rental report:", error);
    alert("Failed to generate report: " + error.message);
  }
}

// ===== RESCHEDULE FUNCTIONS =====

// Open reschedule modal
async function openRescheduleModal(bookingId) {
  currentBookingId = bookingId;

  try {
    // Fetch booking details
    const response = await fetch(`${API_BASE_URL}/detail/${bookingId}`);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();

    if (data.success) {
      const booking = data.booking;

      // Populate modal fields
      document.getElementById("rescheduleBookingId").textContent = `#BK${String(
        bookingId,
      ).padStart(3, "0")}`;
      document.getElementById("rescheduleClientName").textContent =
        booking.client_name;
      document.getElementById("currentEventDate").value = formatDate(
        booking.event_date,
      );
      document.getElementById("currentStartTime").value =
        booking.event_time || "N/A";
      document.getElementById("newEventDate").value = "";
      document.getElementById("newStartTime").value = "";
      document.getElementById("rescheduleReason").value = "";
      document.getElementById("rescheduleNotes").value = "";
      document.getElementById("notifyClient").checked = true;
      document.getElementById("customRescheduleReasonGroup").style.display =
        "none";

      // Open modal
      openModal("rescheduleModal");
    }
  } catch (error) {
    console.error("Error opening reschedule modal:", error);
    alert("Failed to load booking details for rescheduling.");
  }
}

// Toggle custom reschedule reason
function toggleCustomRescheduleReason() {
  const reason = document.getElementById("rescheduleReason").value;
  const customGroup = document.getElementById("customRescheduleReasonGroup");

  if (reason === "other") {
    customGroup.style.display = "block";
    document.getElementById("customRescheduleReason").required = true;
  } else {
    customGroup.style.display = "none";
    document.getElementById("customRescheduleReason").required = false;
  }
}

// Submit reschedule
async function submitReschedule() {
  const newDate = document.getElementById("newEventDate").value;
  const newTime = document.getElementById("newStartTime").value;
  const reason = document.getElementById("rescheduleReason").value;
  const customReason = document.getElementById("customRescheduleReason").value;
  const notes = document.getElementById("rescheduleNotes").value;
  const notifyClient = document.getElementById("notifyClient").checked;

  // Validate inputs
  if (!newDate) {
    alert("Please select a new event date");
    return;
  }

  if (!newTime) {
    alert("Please select a new start time");
    return;
  }

  if (!reason) {
    alert("Please select a reason for rescheduling");
    return;
  }

  if (reason === "other" && !customReason) {
    alert("Please specify the custom reason");
    return;
  }

  // Validate date is in the future
  const selectedDate = new Date(newDate);
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (selectedDate < today) {
    alert("Please select a future date");
    return;
  }

  try {
    showLoading(true);

    const payload = {
      booking_id: currentBookingId,
      new_event_date: newDate,
      new_event_time: newTime,
      reason: reason === "other" ? customReason : reason,
      notes: notes,
      notify_client: notifyClient,
    };

    const response = await fetch(`${API_BASE_URL}/reschedule`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(payload),
    });

    const result = await response.json();

    if (result.success) {
      alert("Booking rescheduled successfully!");
      closeModal("rescheduleModal");
      loadBookings(); // Reload the table
    } else {
      alert(result.message || "Failed to reschedule booking");
    }
  } catch (error) {
    console.error("Error rescheduling booking:", error);
    alert("An error occurred while rescheduling the booking.");
  } finally {
    showLoading(false);
  }
}

/**
 * Switch between tabs in booking details modal
 */
function switchBookingTab(tabName) {
  // Hide all tab contents
  const tabContents = document.querySelectorAll(".tab-content");
  tabContents.forEach((content) => {
    content.style.display = "none";
  });

  // Remove active class from all tab buttons
  const tabButtons = document.querySelectorAll(".tab-button");
  tabButtons.forEach((button) => {
    button.classList.remove("active");
  });

  // Show selected tab content
  const selectedContent = document.querySelector(
    `.tab-content[data-tab="${tabName}"]`,
  );
  if (selectedContent) {
    selectedContent.style.display = "block";
  }

  // Add active class to selected tab button
  const selectedButton = document.querySelector(
    `.tab-button[data-tab="${tabName}"]`,
  );
  if (selectedButton) {
    selectedButton.classList.add("active");
  }
}
