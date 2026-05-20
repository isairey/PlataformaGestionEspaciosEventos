let allEvents = [];
let filteredEvents = [];
let currentDate = new Date();
let selectedEvent = null;

// Initialize
document.addEventListener("DOMContentLoaded", function () {
  initializeCalendar();
  loadEvents();
});

// Load events from API
async function loadEvents() {
  try {
    document.getElementById("loadingSpinner").style.display = "block";
    document.getElementById("calendarContainer").style.display = "none";

    const response = await fetch("/api/events/list");
    const data = await response.json();

    if (data.success) {
      allEvents = data.events;
      filteredEvents = [...allEvents];
      populateFacilityFilter();
      renderCalendar();
    } else {
      console.error("Failed to load events:", data.message);
      showNoEventsMessage();
    }
  } catch (error) {
    console.error("Error loading events:", error);
    showNoEventsMessage();
  } finally {
    document.getElementById("loadingSpinner").style.display = "none";
    document.getElementById("calendarContainer").style.display = "block";
  }
}

// Populate facility filter dropdown
function populateFacilityFilter() {
  const facilityFilter = document.getElementById("filterFacility");
  const facilities = [...new Set(allEvents.map((event) => event.facility_name))]
    .filter((f) => f)
    .sort();

  facilityFilter.innerHTML = '<option value="">All Facilities</option>';
  facilities.forEach((facility) => {
    const option = document.createElement("option");
    option.value = facility;
    option.textContent = facility;
    facilityFilter.appendChild(option);
  });
}

// Initialize calendar
function initializeCalendar() {
  updateMonthDisplay();
}

// Update month display
function updateMonthDisplay() {
  const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];
  const monthDisplay =
    monthNames[currentDate.getMonth()] + " " + currentDate.getFullYear();
  document.getElementById("monthDisplay").textContent = monthDisplay;
  document.getElementById("currentMonthYear").textContent = monthDisplay;
}

// Render calendar
function renderCalendar() {
  const calendarGrid = document.getElementById("calendarGrid");
  const MAX_EVENTS_DISPLAY = 3; // Maximum events to show per day

  // Clear existing days (keep headers)
  const days = calendarGrid.querySelectorAll(".calendar-day");
  days.forEach((day) => day.remove());

  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startDate = new Date(firstDay);
  startDate.setDate(startDate.getDate() - firstDay.getDay());

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  // Generate 42 days (6 weeks)
  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate);
    date.setDate(startDate.getDate() + i);

    const dayDiv = document.createElement("div");
    dayDiv.className = "calendar-day";

    // Add classes
    if (date.getMonth() !== month) {
      dayDiv.classList.add("other-month");
    }
    if (date.toDateString() === today.toDateString()) {
      dayDiv.classList.add("today");
    }

    // Create day wrapper
    const dayWrapper = document.createElement("div");
    dayWrapper.className = "calendar-day-wrapper";

    // Add day header with number and count
    const dayHeaderRow = document.createElement("div");
    dayHeaderRow.className = "calendar-day-header-row";

    const dayNumber = document.createElement("div");
    dayNumber.className = "calendar-day-number";
    dayNumber.textContent = date.getDate();
    dayHeaderRow.appendChild(dayNumber);

    // Get events for this date
    const dayEvents = filteredEvents.filter((event) => {
      const eventDate = new Date(event.event_date + "T00:00:00");
      return eventDate.toDateString() === date.toDateString();
    });

    // Add event count badge if there are events
    if (dayEvents.length > 0) {
      const countBadge = document.createElement("div");
      countBadge.className = "event-count-badge";
      countBadge.textContent = dayEvents.length;
      dayHeaderRow.appendChild(countBadge);
    }

    dayWrapper.appendChild(dayHeaderRow);

    // Show only first MAX_EVENTS_DISPLAY events
    const visibleEvents = dayEvents.slice(0, MAX_EVENTS_DISPLAY);

    visibleEvents.forEach((event) => {
      const eventDiv = document.createElement("div");
      eventDiv.className = `calendar-event status-${event.status}`;
      eventDiv.innerHTML = `
                        <div class="event-title">${escapeHtml(
                          event.event_title
                        )}</div>
                        <div class="event-time">${formatTime(
                          event.event_time
                        )}</div>
                    `;
      eventDiv.onclick = (e) => {
        e.stopPropagation();
        showEventDetails(event);
      };
      dayWrapper.appendChild(eventDiv);
    });

    // Add "show more" indicator if there are more events
    if (dayEvents.length > MAX_EVENTS_DISPLAY) {
      const moreIndicator = document.createElement("div");
      moreIndicator.className = "more-events-indicator";
      moreIndicator.textContent = `+${
        dayEvents.length - MAX_EVENTS_DISPLAY
      } more`;
      moreIndicator.onclick = (e) => {
        e.stopPropagation();
        showDayEvents(date, dayEvents);
      };
      dayWrapper.appendChild(moreIndicator);
    }

    dayDiv.appendChild(dayWrapper);
    calendarGrid.appendChild(dayDiv);
  }

  // Show no events message if no events
  if (filteredEvents.length === 0) {
    showNoEventsMessage();
  }
}

// Show all events for a specific day
function showDayEvents(date, events) {
  const formattedDate = date.toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  const modalBody = document.getElementById("modalBody");
  modalBody.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <h3 style="color: #1e3c72; margin-bottom: 10px;">
                        <i class="fas fa-calendar-day"></i> ${formattedDate}
                    </h3>
                    <p style="color: #64748b; font-size: 0.95rem;">${
                      events.length
                    } event(s) scheduled</p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    ${events
                      .map(
                        (event) => `
                        <div style="padding: 12px; border-left: 4px solid ${getStatusBorderColor(
                          event.status
                        )}; background: ${getStatusBackground(
                          event.status
                        )}; border-radius: 6px; cursor: pointer;" onclick="showEventDetails(${JSON.stringify(
                          event
                        ).replace(/"/g, "&quot;")})">
                            <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px;">${escapeHtml(
                              event.event_title
                            )}</div>
                            <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 4px;">
                                <i class="fas fa-clock"></i> ${formatTime(
                                  event.event_time
                                )} (${event.duration}h)
                            </div>
                            <div style="font-size: 0.85rem; color: #64748b;">
                                <i class="fas fa-building"></i> ${escapeHtml(
                                  event.facility_name || "N/A"
                                )}
                            </div>
                            <div style="margin-top: 6px;">
                                <span class="status-badge-modal ${
                                  event.status
                                }">
                                    ${
                                      event.status.charAt(0).toUpperCase() +
                                      event.status.slice(1)
                                    }
                                </span>
                            </div>
                        </div>
                    `
                      )
                      .join("")}
                </div>
            `;

  document.getElementById("eventModal").style.display = "block";
}

// Helper functions for colors
function getStatusBorderColor(status) {
  const colors = {
    pending: "#f59e0b",
    confirmed: "#10b981",
    completed: "#6b7280",
  };
  return colors[status] || "#64748b";
}

function getStatusBackground(status) {
  const backgrounds = {
    pending: "#fffbeb",
    confirmed: "#ecfdf5",
    completed: "#f3f4f6",
  };
  return backgrounds[status] || "#f8fafc";
}

// Show event details in modal
function showEventDetails(event) {
  selectedEvent = event;
  const modalBody = document.getElementById("modalBody");

  const eventDate = new Date(event.event_date);
  const formattedDate = eventDate.toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  modalBody.innerHTML = `
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-heading"></i> Event Title</div>
                    <div class="event-detail-value">${escapeHtml(
                      event.event_title
                    )}</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-info-circle"></i> Status</div>
                    <div class="event-detail-value">
                        <span class="status-badge-modal ${event.status}">
                            ${
                              event.status.charAt(0).toUpperCase() +
                              event.status.slice(1)
                            }
                        </span>
                    </div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-calendar"></i> Date</div>
                    <div class="event-detail-value">${formattedDate}</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-clock"></i> Time</div>
                    <div class="event-detail-value">${formatTime(
                      event.event_time
                    )} (${event.duration} hours)</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-building"></i> Facility</div>
                    <div class="event-detail-value">${escapeHtml(
                      event.facility_name || "N/A"
                    )}</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-user"></i> Client</div>
                    <div class="event-detail-value">${escapeHtml(
                      event.client_name
                    )}</div>
                </div>
                ${
                  event.organization
                    ? `
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-users"></i> Organization</div>
                    <div class="event-detail-value">${escapeHtml(
                      event.organization
                    )}</div>
                </div>
                `
                    : ""
                }
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-users"></i> Attendees</div>
                    <div class="event-detail-value">${
                      event.attendees || "Not specified"
                    } people</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-envelope"></i> Contact Email</div>
                    <div class="event-detail-value">${escapeHtml(
                      event.email_address
                    )}</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-phone"></i> Contact Number</div>
                    <div class="event-detail-value">${escapeHtml(
                      event.contact_number
                    )}</div>
                </div>
                ${
                  event.special_requirements
                    ? `
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-clipboard-list"></i> Special Requirements</div>
                    <div class="event-detail-value">${escapeHtml(
                      event.special_requirements
                    )}</div>
                </div>
                `
                    : ""
                }
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-money-bill-wave"></i> Total Cost</div>
                    <div class="event-detail-value">
                        ${
                          event.booking_type === "student"
                            ? '<span style="color: #10b981; font-weight: 700;">FREE</span>'
                            : "₱" + Number(event.total_cost).toLocaleString()
                        }
                    </div>
                </div>
            `;

  document.getElementById("eventModal").style.display = "block";
}

// Close modal
function closeModal() {
  document.getElementById("eventModal").style.display = "none";
  selectedEvent = null;
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modal = document.getElementById("eventModal");
  if (event.target === modal) {
    closeModal();
  }
};

// Apply filters
function applyFilters() {
  const dateFrom = document.getElementById("filterDateFrom").value;
  const dateTo = document.getElementById("filterDateTo").value;
  const facility = document.getElementById("filterFacility").value;
  const status = document.getElementById("filterStatus").value;

  filteredEvents = allEvents.filter((event) => {
    // Date from filter
    if (dateFrom && event.event_date < dateFrom) return false;

    // Date to filter
    if (dateTo && event.event_date > dateTo) return false;

    // Facility filter
    if (facility && event.facility_name !== facility) return false;

    // Status filter
    if (status && event.status !== status) return false;

    return true;
  });

  renderCalendar();
}

// Reset filters
function resetFilters() {
  document.getElementById("filterDateFrom").value = "";
  document.getElementById("filterDateTo").value = "";
  document.getElementById("filterFacility").value = "";
  document.getElementById("filterStatus").value = "";
  filteredEvents = [...allEvents];
  renderCalendar();
}

// Navigation functions
function previousMonth() {
  currentDate.setMonth(currentDate.getMonth() - 1);
  updateMonthDisplay();
  renderCalendar();
}

function nextMonth() {
  currentDate.setMonth(currentDate.getMonth() + 1);
  updateMonthDisplay();
  renderCalendar();
}

function goToToday() {
  currentDate = new Date();
  updateMonthDisplay();
  renderCalendar();
}

// Utility functions
function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function formatTime(time) {
  if (!time) return "N/A";
  return time.substring(0, 5);
}

function showNoEventsMessage() {
  const calendarGrid = document.getElementById("calendarGrid");
  const existingMessage = document.querySelector(".no-events-message");
  if (!existingMessage) {
    const messageDiv = document.createElement("div");
    messageDiv.className = "no-events-message";
    messageDiv.innerHTML = `
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Events Found</h3>
                    <p>There are no events matching your current filters.</p>
                `;
    calendarGrid.parentElement.appendChild(messageDiv);
  }
}
