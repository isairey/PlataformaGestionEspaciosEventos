// Global state variables
let allBookings = []; // Store all bookings for filtering
let currentMonth = {
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1,
};
let selectedDate = null;

// Main initialization - DO NOT ADD ANOTHER DOMContentLoaded, see bottom of file
document.addEventListener("DOMContentLoaded", function () {
  console.log("Initializing admin dashboard...");

  // Initialize calendar FIRST before loading data
  console.log(
    "Generating calendar for:",
    currentMonth.year,
    currentMonth.month
  );
  generateCalendar(currentMonth.year, currentMonth.month);

  // Set up month navigation buttons
  const prevBtn = document.getElementById("prevMonthBtn");
  const nextBtn = document.getElementById("nextMonthBtn");

  if (prevBtn) {
    prevBtn.addEventListener("click", previousMonth);
  }

  if (nextBtn) {
    nextBtn.addEventListener("click", nextMonth);
  }

  // Then load data
  loadDashboardStats();
  loadRecentBookings();

  // Load events with proper parameters
  console.log("Loading events for calendar...");
  loadUpcomingEvents(currentMonth.year, currentMonth.month);

  loadEquipmentStatus();
});

/**
 * Load dashboard statistics
 */
function loadDashboardStats() {
  fetch("/api/admin/dashboard-stats")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateStatCards(data.stats);
      } else {
        console.error("Failed to load stats:", data.message);
      }
    })
    .catch((error) => {
      console.error("Error loading dashboard stats:", error);
    });
}

/**
 * Update stat cards with real data
 */
function updateStatCards(stats) {
  // Update total users
  const usersCard = document.querySelector(
    ".stat-card:nth-child(1) .stat-value"
  );
  if (usersCard) {
    animateValue(usersCard, 0, stats.totalUsers, 1000);
  }

  // Update active bookings
  const bookingsCard = document.querySelector(
    ".stat-card:nth-child(2) .stat-value"
  );
  if (bookingsCard) {
    animateValue(bookingsCard, 0, stats.activeBookings, 1000);
  }

  // Update total facilities
  const facilitiesCard = document.querySelector(
    ".stat-card:nth-child(3) .stat-value"
  );
  if (facilitiesCard) {
    animateValue(facilitiesCard, 0, stats.totalFacilities, 1000);
  }

  // Update equipment items
  const equipmentCard = document.querySelector(
    ".stat-card:nth-child(4) .stat-value"
  );
  if (equipmentCard) {
    animateValue(equipmentCard, 0, stats.totalEquipment, 1000);
  }
}

/**
 * Animate number counting
 */
function animateValue(element, start, end, duration) {
  const range = end - start;
  const increment = range / (duration / 16);
  let current = start;

  const timer = setInterval(() => {
    current += increment;
    if (current >= end) {
      element.textContent = end;
      clearInterval(timer);
    } else {
      element.textContent = Math.floor(current);
    }
  }, 16);
}

/**
 * Load recent bookings
 */
function loadRecentBookings() {
  // Fetch all bookings (not just recent 10) for filtering capability
  fetch("/api/events/list")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Store all bookings/events globally for filtering
        allBookings = data.events;
        console.log(`Loaded ${allBookings.length} bookings for filtering`);
        // Display only the most recent ones in the table initially
        const recentBookings = allBookings
          .slice()
          .sort((a, b) => new Date(b.event_date) - new Date(a.event_date))
          .slice(0, 10);
        displayRecentBookings(recentBookings);
      } else {
        console.error("Failed to load bookings:", data.message);
      }
    })
    .catch((error) => {
      console.error("Error loading recent bookings:", error);
    });
}

/**
 * Display recent bookings in table
 */
function displayRecentBookings(bookings, showFilterHeader = false) {
  const tbody = document.querySelector(".recent-table tbody");
  if (!tbody) return;

  // Update filter header if it exists
  const filterHeader = document.querySelector(".date-filter-header");
  if (filterHeader) {
    if (showFilterHeader && selectedDate) {
      filterHeader.style.display = "flex";
      const dateDisplay = filterHeader.querySelector(".filter-date");
      if (dateDisplay) {
        dateDisplay.textContent = formatDate(selectedDate);
      }
    } else {
      filterHeader.style.display = "none";
    }
  }

  if (bookings.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 2rem;">
                    ${
                      showFilterHeader
                        ? "No bookings found for the selected date"
                        : "No recent bookings found"
                    }
                </td>
            </tr>
        `;
    return;
  }

  tbody.innerHTML = bookings
    .map(
      (booking) => `
        <tr>
            <td>${escapeHtml(
              booking.client_name || booking.user_name || "N/A"
            )}</td>
            <td>${escapeHtml(booking.facility_name || "N/A")}</td>
            <td>${formatDate(booking.event_date)}</td>
            <td>${formatTime(booking.event_time)} - ${formatEndTime(
        booking.event_time,
        booking.duration
      )}</td>
            <td><span class="status-badge ${booking.status}">${capitalizeFirst(
        booking.status
      )}</span></td>
        </tr>
    `
    )
    .join("");
}
function formatEndTime(startTime, duration) {
  if (!startTime || !duration) return "N/A";

  try {
    // Parse start time
    const [startHours, startMinutes] = startTime.split(":").map(Number);

    // Parse duration
    const [durationHours, durationMinutes] = duration.split(":").map(Number);

    // Calculate end time
    let endHours = startHours + durationHours;
    let endMinutes = startMinutes + (durationMinutes || 0);

    // Handle minute overflow
    if (endMinutes >= 60) {
      endHours += Math.floor(endMinutes / 60);
      endMinutes = endMinutes % 60;
    }

    // Handle hour overflow (past midnight)
    endHours = endHours % 24;

    // Format the end time
    const ampm = endHours >= 12 ? "PM" : "AM";
    const displayHour = endHours % 12 || 12;
    const displayMinutes = endMinutes.toString().padStart(2, "0");

    return `${displayHour}:${displayMinutes} ${ampm}`;
  } catch (error) {
    console.error("Error calculating end time:", error);
    return "N/A";
  }
}
/**
 * Load upcoming events for calendar
 * Using the same approach as events page - fetch all events and filter client-side
 */
function loadUpcomingEvents(year = null, month = null) {
  // Use current month if not specified
  if (!year) year = currentMonth.year;
  if (!month) month = currentMonth.month;

  console.log(`Loading events for calendar: ${year}-${month}`);

  // Use the events API endpoint like the events page does
  fetch("/api/events/list")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        console.log(`Received ${data.events.length} total events from API`);

        // Filter events to show pending, confirmed, and completed (exclude cancelled)
        const filteredEvents = data.events.filter((event) => {
          const eventDate = new Date(event.event_date + "T00:00:00");
          const isInCurrentMonth =
            eventDate.getFullYear() === year &&
            eventDate.getMonth() + 1 === month;
          const isValidStatus = ["pending", "confirmed", "completed"].includes(
            event.status
          );

          return isInCurrentMonth && isValidStatus;
        });

        console.log(
          `Filtered to ${filteredEvents.length} events for ${year}-${month}`
        );
        console.log(
          "Event statuses:",
          filteredEvents.map((e) => e.status)
        );
        displayCalendarEvents(filteredEvents);
      } else {
        console.error("Failed to load events:", data.message);
      }
    })
    .catch((error) => {
      console.error("Error loading upcoming events:", error);
    });
}

/**
 * Display events on calendar
 */
function displayCalendarEvents(events) {
  const MAX_DOTS_DISPLAY = 5; // Maximum event dots to show before adding "+X more"

  // Clear existing event markers and badges
  document.querySelectorAll(".day-events").forEach((el) => (el.innerHTML = ""));
  document.querySelectorAll(".event-count-badge").forEach((el) => el.remove());

  if (!events || events.length === 0) {
    console.log("No events to display on calendar");
    initCalendarClickHandlers();
    return;
  }

  console.log(
    `Displaying ${events.length} events on calendar for ${currentMonth.year}-${currentMonth.month}`
  );

  // Group events by day
  const eventsByDay = {};
  events.forEach((event) => {
    const eventDate = new Date(event.event_date + "T00:00:00");
    const eventYear = eventDate.getFullYear();
    const eventMonth = eventDate.getMonth() + 1;
    const day = eventDate.getDate();

    // Only process events that belong to the current displayed month
    if (eventYear !== currentMonth.year || eventMonth !== currentMonth.month) {
      return;
    }

    if (!eventsByDay[day]) {
      eventsByDay[day] = [];
    }
    eventsByDay[day].push(event);
  });

  // Display events for each day
  Object.keys(eventsByDay).forEach((day) => {
    const dayEvents = eventsByDay[day];
    const dayNumber = parseInt(day);

    // Find the day element by matching the day number
    const allDays = document.querySelectorAll(".calendar-day:not(.empty)");
    let dayContainer = null;
    let dayEventsElement = null;

    for (let i = 0; i < allDays.length; i++) {
      const dayNumberEl = allDays[i].querySelector(".day-number");
      if (dayNumberEl && parseInt(dayNumberEl.textContent) === dayNumber) {
        dayContainer = allDays[i];
        dayEventsElement = allDays[i].querySelector(".day-events");
        break;
      }
    }

    if (!dayEventsElement) {
      console.warn(`Could not find calendar day element for day ${dayNumber}`);
      return;
    }

    // Store events data on the day element for later reference
    dayContainer.dataset.eventCount = dayEvents.length;
    dayContainer.dataset.dateString = `${currentMonth.year}-${String(
      currentMonth.month
    ).padStart(2, "0")}-${String(dayNumber).padStart(2, "0")}`;

    // Add event count badge if there are events
    if (dayEvents.length > 0) {
      const countBadge = document.createElement("div");
      countBadge.className = "event-count-badge";
      countBadge.textContent = dayEvents.length;
      countBadge.title = `${dayEvents.length} event(s)`;
      dayContainer.appendChild(countBadge);
    }

    // Show only first MAX_DOTS_DISPLAY event dots
    const visibleEvents = dayEvents.slice(0, MAX_DOTS_DISPLAY);
    visibleEvents.forEach((event) => {
      const eventDot = document.createElement("div");
      eventDot.className = `event-dot ${event.status}`;
      eventDot.title = `${event.event_title || event.facility_name} - ${
        event.status
      }`;
      eventDot.dataset.bookingId = event.id;
      dayEventsElement.appendChild(eventDot);
    });

    // Add "more events" indicator if there are more than MAX_DOTS_DISPLAY
    if (dayEvents.length > MAX_DOTS_DISPLAY) {
      const moreIndicator = document.createElement("div");
      moreIndicator.className = "more-events-count";
      moreIndicator.textContent = `+${dayEvents.length - MAX_DOTS_DISPLAY}`;
      moreIndicator.title = `${
        dayEvents.length - MAX_DOTS_DISPLAY
      } more event(s). Click to filter.`;
      dayEventsElement.appendChild(moreIndicator);
    }

    console.log(
      `Added ${visibleEvents.length} event dots for day ${dayNumber} (${dayEvents.length} total)`
    );
  });

  // Initialize calendar click handlers after events are loaded
  initCalendarClickHandlers();
}

/**
 * Initialize click handlers for calendar days using event delegation
 */
function initCalendarClickHandlers() {
  // Remove existing listener on calendar grid
  const calendarGrid = document.getElementById("calendarGrid");
  if (!calendarGrid) return;

  // Clone and replace to remove old listeners
  const newCalendarGrid = calendarGrid.cloneNode(true);
  calendarGrid.parentNode.replaceChild(newCalendarGrid, calendarGrid);

  // Add single event listener to calendar grid (event delegation)
  newCalendarGrid.addEventListener("click", function (e) {
    // Safety check for null target
    if (!e.target) return;

    // Handle event dot clicks
    if (e.target.classList.contains("event-dot")) {
      e.stopPropagation();
      const bookingId = e.target.dataset.bookingId;
      if (bookingId) {
        console.log("Event dot clicked, booking ID:", bookingId);
        viewBooking(bookingId);
      }
      return;
    }

    // Handle "more events" indicator clicks
    if (e.target.classList.contains("more-events-count")) {
      e.stopPropagation();
      const dayElement = e.target.closest(".calendar-day");
      if (dayElement && dayElement.dataset.dateString) {
        console.log(
          "+X more clicked, filtering date:",
          dayElement.dataset.dateString
        );
        filterBookingsByDate(dayElement.dataset.dateString);
      }
      return;
    }

    // Handle event count badge clicks
    if (e.target.classList.contains("event-count-badge")) {
      e.stopPropagation();
      const dayElement = e.target.closest(".calendar-day");
      if (dayElement && dayElement.dataset.dateString) {
        console.log(
          "Event count badge clicked, filtering date:",
          dayElement.dataset.dateString
        );
        filterBookingsByDate(dayElement.dataset.dateString);
      }
      return;
    }

    // Handle calendar day clicks
    const dayElement = e.target.closest(".calendar-day");
    if (dayElement && !dayElement.classList.contains("empty")) {
      const dayNumber = dayElement.querySelector(".day-number")?.textContent;
      if (!dayNumber) return;

      // Build the date string
      const year = currentMonth.year;
      const month = String(currentMonth.month).padStart(2, "0");
      const dayPadded = String(dayNumber).padStart(2, "0");
      const dateString = `${year}-${month}-${dayPadded}`;

      console.log("Calendar day clicked:", dateString);
      filterBookingsByDate(dateString);
    }
  });
}

/**
 * Filter bookings by selected date
 */
function filterBookingsByDate(dateString) {
  selectedDate = dateString;

  console.log("Filtering bookings by date:", dateString);
  console.log("All bookings available:", allBookings ? allBookings.length : 0);

  // Check if bookings are loaded
  if (!allBookings || allBookings.length === 0) {
    console.warn("No bookings loaded yet. Reloading bookings...");
    // Reload bookings and try again
    fetch("/api/events/list")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          allBookings = data.events;
          filterBookingsByDate(dateString); // Retry filtering
        }
      })
      .catch((error) => {
        console.error("Error loading bookings:", error);
      });
    return;
  }

  // Remove previous selected state
  document.querySelectorAll(".calendar-day").forEach((day) => {
    day.classList.remove("selected");
  });

  // Add selected state to clicked day - find by day number instead of position
  const date = new Date(dateString + "T00:00:00");
  const day = date.getDate();
  const month = date.getMonth() + 1;
  const year = date.getFullYear();

  // Only highlight if the date is in the current displayed month
  if (year === currentMonth.year && month === currentMonth.month) {
    const allDays = document.querySelectorAll(".calendar-day:not(.empty)");
    for (let i = 0; i < allDays.length; i++) {
      const dayNumberEl = allDays[i].querySelector(".day-number");
      if (dayNumberEl && parseInt(dayNumberEl.textContent) === day) {
        allDays[i].classList.add("selected");
        break;
      }
    }
  }

  // Filter bookings - try multiple date formats for compatibility
  const filtered = allBookings.filter((booking) => {
    if (!booking.event_date) return false;

    // Normalize the booking date (remove time if present)
    const bookingDate = booking.event_date.split("T")[0].split(" ")[0];

    // Compare dates
    const matches = bookingDate === dateString;

    if (matches) {
      console.log("Match found:", booking.event_title, bookingDate);
    }

    return matches;
  });

  console.log(
    `Filtered ${filtered.length} bookings out of ${allBookings.length} total for date: ${dateString}`
  );

  if (filtered.length === 0) {
    console.warn("No bookings found for date:", dateString);
    console.log(
      "Sample booking dates from first 5:",
      allBookings.slice(0, 5).map((b) => ({
        date: b.event_date,
        title: b.event_title || b.facility_name,
      }))
    );
  }

  // Sort filtered bookings by time
  filtered.sort((a, b) => {
    const timeA = a.event_time || "";
    const timeB = b.event_time || "";
    return timeA.localeCompare(timeB);
  });

  displayRecentBookings(filtered, true);

  // Scroll to bookings table
  const bookingsTable = document.querySelector(".recent-table");
  if (bookingsTable) {
    setTimeout(() => {
      bookingsTable.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }, 100);
  }
}

/**
 * Clear date filter
 */
function clearDateFilter() {
  selectedDate = null;

  console.log("Clearing date filter...");

  // Remove selected state from all days
  document.querySelectorAll(".calendar-day").forEach((day) => {
    day.classList.remove("selected");
  });

  // Show recent bookings (top 10)
  if (allBookings && allBookings.length > 0) {
    const recentBookings = allBookings
      .slice()
      .sort((a, b) => new Date(b.event_date) - new Date(a.event_date))
      .slice(0, 10);
    displayRecentBookings(recentBookings, false);
  } else {
    // Reload bookings if not available
    loadRecentBookings();
  }
}

// Make clearDateFilter available globally for onclick handlers
window.clearDateFilter = clearDateFilter;

/**
 * Load equipment status
 */
function loadEquipmentStatus() {
  fetch("/api/admin/equipment-status")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        displayEquipmentStatus(data.equipment);
      } else {
        console.error("Failed to load equipment:", data.message);
      }
    })
    .catch((error) => {
      console.error("Error loading equipment status:", error);
    });
}

/**
 * Display equipment status
 */
function displayEquipmentStatus(equipment) {
  const container = document.querySelector(".equipment-grid");
  if (!container) return;

  if (equipment.length === 0) {
    container.innerHTML =
      '<p style="text-align: center; padding: 2rem;">No equipment found</p>';
    return;
  }

  container.innerHTML = equipment
    .map((item) => {
      const icon = getEquipmentIcon(item.name);
      const statusClass = item.availability_status;
      const statusText = getStatusText(item.availability_status);

      return `
            <div class="equipment-card">
                <div class="equipment-icon">${icon}</div>
                <div class="equipment-details">
                    <div class="equipment-name">${escapeHtml(item.name)} (${
        item.available_quantity
      }/${item.quantity})</div>
                    <div class="equipment-status">
                        <span class="status-dot ${statusClass}"></span> ${statusText}
                    </div>
                </div>
            </div>
        `;
    })
    .join("");
}

/**
 * Get equipment icon based on name
 */
function getEquipmentIcon(name) {
  const nameLower = name.toLowerCase();

  if (nameLower.includes("microphone") || nameLower.includes("mic"))
    return "🎤";
  if (nameLower.includes("projector")) return "📽️";
  if (nameLower.includes("speaker")) return "🔊";
  if (nameLower.includes("camera")) return "📷";
  if (nameLower.includes("laptop") || nameLower.includes("computer"))
    return "💻";
  if (nameLower.includes("chair")) return "🪑";
  if (nameLower.includes("table")) return "🪑";
  if (nameLower.includes("light")) return "💡";
  if (nameLower.includes("screen")) return "🖥️";

  return "📦"; // Default icon
}

/**
 * Get status text
 */
function getStatusText(status) {
  const statusMap = {
    available: "Available",
    limited: "Limited",
    low: "Low Stock",
    unavailable: "Unavailable",
  };
  return statusMap[status] || "Unknown";
}

/**
 * Format date
 */
function formatDate(dateString) {
  if (!dateString) return "N/A";

  const date = new Date(dateString);
  const options = { year: "numeric", month: "short", day: "numeric" };
  return date.toLocaleDateString("en-US", options);
}

/**
 * Format time
 */
function formatTime(timeString) {
  if (!timeString) return "N/A";

  const [hours, minutes] = timeString.split(":");
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? "PM" : "AM";
  const displayHour = hour % 12 || 12;

  return `${displayHour}:${minutes} ${ampm}`;
}

/**
 * Capitalize first letter
 */
function capitalizeFirst(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Escape HTML to prevent XSS
 */
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

/**
 * View booking details
 */
function viewBooking(bookingId) {
  window.location.href = `/admin/bookings/view/${bookingId}`;
}

/**
 * Edit booking
 */
function editBooking(bookingId) {
  window.location.href = `/admin/bookings/edit/${bookingId}`;
}

/**
 * Generate calendar for specified month
 */
function generateCalendar(year, month) {
  const calendarGrid = document.getElementById("calendarGrid");
  if (!calendarGrid) return;

  // Clear existing calendar
  calendarGrid.innerHTML = "";

  // Get first day of month and total days
  const firstDay = new Date(year, month - 1, 1);
  const lastDay = new Date(year, month, 0);
  const daysInMonth = lastDay.getDate();
  const startingDayOfWeek = firstDay.getDay(); // 0 = Sunday

  // Get today's date for comparison
  const today = new Date();
  const isCurrentMonth =
    today.getFullYear() === year && today.getMonth() + 1 === month;
  const todayDate = today.getDate();

  // Add empty cells for days before month starts
  for (let i = 0; i < startingDayOfWeek; i++) {
    const emptyDay = document.createElement("div");
    emptyDay.className = "calendar-day empty";
    calendarGrid.appendChild(emptyDay);
  }

  // Add days of the month
  for (let day = 1; day <= daysInMonth; day++) {
    const dayElement = document.createElement("div");
    dayElement.className = "calendar-day";

    // Add 'today' class if it's today's date
    if (isCurrentMonth && day === todayDate) {
      dayElement.classList.add("today");
    }

    dayElement.innerHTML = `
      <div class="day-number">${day}</div>
      <div class="day-events"></div>
    `;

    calendarGrid.appendChild(dayElement);
  }

  // Update calendar header with month/year
  const monthYearDisplay = document.getElementById("calendar-month-year");
  if (monthYearDisplay) {
    const date = new Date(year, month - 1, 1);
    const options = { year: "numeric", month: "long" };
    monthYearDisplay.textContent = date.toLocaleDateString("en-US", options);
  }

  // Re-initialize click handlers after calendar is regenerated
  initCalendarClickHandlers();
}

/**
 * Navigate to previous month
 */
function previousMonth() {
  currentMonth.month--;
  if (currentMonth.month < 1) {
    currentMonth.month = 12;
    currentMonth.year--;
  }

  generateCalendar(currentMonth.year, currentMonth.month);
  loadUpcomingEvents(currentMonth.year, currentMonth.month);
}

/**
 * Navigate to next month
 */
function nextMonth() {
  currentMonth.month++;
  if (currentMonth.month > 12) {
    currentMonth.month = 1;
    currentMonth.year++;
  }

  generateCalendar(currentMonth.year, currentMonth.month);
  loadUpcomingEvents(currentMonth.year, currentMonth.month);
}

/**
 * Refresh dashboard data every 5 minutes
 */

// Sidebar and calendar navigation setup (merged with main DOMContentLoaded above)
document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.querySelector(".toggle-btn");
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (toggleBtn && sidebar && mainContent) {
    toggleBtn.addEventListener("click", function () {
      sidebar.classList.toggle("collapsed");
      mainContent.classList.toggle("expanded");
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", function (event) {
      const isMobile = window.innerWidth <= 768;
      const clickedOutside =
        event.target &&
        !sidebar.contains(event.target) &&
        !toggleBtn.contains(event.target);

      if (
        isMobile &&
        clickedOutside &&
        sidebar.classList.contains("collapsed") === false
      ) {
        sidebar.classList.add("collapsed");
        mainContent.classList.add("expanded");
      }
    });
  }

  // Wire up calendar navigation buttons
  const prevMonthBtn = document.getElementById("prevMonthBtn");
  const nextMonthBtn = document.getElementById("nextMonthBtn");

  if (prevMonthBtn) {
    prevMonthBtn.addEventListener("click", previousMonth);
  }

  if (nextMonthBtn) {
    nextMonthBtn.addEventListener("click", nextMonth);
  }
});

setInterval(() => {
  loadDashboardStats();
  loadRecentBookings();
  loadUpcomingEvents(currentMonth.year, currentMonth.month);
  loadEquipmentStatus();
}, 300000);

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
    if (!e.target || !e.target.closest(".dropdown")) {
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
