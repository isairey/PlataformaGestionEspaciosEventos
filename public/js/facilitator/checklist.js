// Global variables
let currentEvents = [];
let currentBookingId = null;
let currentEventId = null;
let equipmentChecks = [];

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  loadPendingEvents();
  loadStats();
});

/**
 * Load pending events
 */
function loadPendingEvents() {
  showLoading();

  fetch("/facilitator/checklist/events")
    .then((response) => response.json())
    .then((data) => {
      console.log("Events loaded:", data); // Debug log
      if (data.success) {
        currentEvents = data.events;
        displayEvents(data.events);
        updateStats(data.events);
      } else {
        showError("Failed to load events");
      }
    })
    .catch((error) => {
      console.error("Error loading events:", error);
      showError("Failed to load events");
    })
    .finally(() => {
      hideLoading();
    });
}

/**
 * Display events in grid
 */
function displayEvents(events) {
  const container = document.getElementById("eventsContainer");
  const noEventsMsg = document.getElementById("noEventsMessage");

  if (events.length === 0) {
    container.style.display = "none";
    noEventsMsg.style.display = "block";
    return;
  }

  container.style.display = "grid";
  noEventsMsg.style.display = "none";

  container.innerHTML = events
    .map(
      (event) => `
<div class="event-card" onclick="openChecklistModal(${event.id}, ${
        event.event_id || "null"
      })">
            <div class="event-icon">${event.facility_icon || "📍"}</div>
            <div class="event-details">
                <h3 class="event-title">${escapeHtml(event.event_title)}</h3>
                <div class="event-meta">
                    <span class="meta-item">
                        <i>📅</i> ${formatDate(event.event_date)}
                    </span>
                    <span class="meta-item">
                        <i>🕐</i> ${formatTime(event.event_time)}
                    </span>
                    <span class="meta-item">
                        <i>⏱️</i> ${event.duration} hours
                    </span>
                    <span class="meta-item">
                        <i>👤</i> ${escapeHtml(event.client_name)}
                    </span>
                </div>
                <div class="event-meta">
                    <span class="meta-item">
                        <i>🏢</i> ${escapeHtml(event.facility_name)}
                    </span>
                    <span class="meta-item">
                        <i>👥</i> ${event.attendees || "N/A"} attendees
                    </span>
                </div>
                <span class="event-status status-${event.event_status}">
                    ${capitalizeFirst(event.event_status)}
                </span>
            </div>
<div class="event-actions" onclick="event.stopPropagation()">
    <button class="btn btn-primary" onclick="openChecklistModal(${event.id}, ${
        event.event_id || "null"
      })">
        📋 Start Checklist
    </button>
</div>
        </div>
    `
    )
    .join("");
}

/**
 * Open checklist modal and load equipment
 */
function openChecklistModal(bookingId, eventId) {
  console.log("Opening modal for booking:", bookingId, "event:", eventId);
  console.log("Current events:", currentEvents);

  currentBookingId = bookingId;
  currentEventId = eventId;
  equipmentChecks = [];

  // Try multiple ways to find the event
  const event = currentEvents.find((e) => e.id === bookingId);


  if (!event) {
    // Try finding by booking_id if id doesn't work
    event = currentEvents.find((e) => e.booking_id === bookingId);
  }

  if (!event) {
    // Try finding by event_id
    event = currentEvents.find((e) => e.event_id === eventId);
  }

  console.log("Found event:", event);

  if (!event) {
    console.error("Event not found. Available events:");
    console.table(
      currentEvents.map((e) => ({
        id: e.id,
        booking_id: e.booking_id,
        event_id: e.event_id,
        title: e.event_title,
      }))
    );
    alert("Event not found. Please refresh the page and try again.");
    return;
  }

  // Populate event info
  document.getElementById("modalEventTitle").textContent =
    event.event_title || "N/A";
  document.getElementById("modalClientName").textContent =
    event.client_name || "N/A";
  document.getElementById("modalFacility").textContent =
    event.facility_name || "N/A";
  document.getElementById("modalDateTime").textContent = `${formatDate(
    event.event_date
  )} at ${formatTime(event.event_time)}`;
  document.getElementById(
    "modalDuration"
  ).textContent = `${event.duration} hours`;
  document.getElementById("modalAttendees").textContent =
    event.attendees || "N/A";

  // Load equipment
  loadEquipmentForEvent(bookingId);

  // Show modal
  document.getElementById("checklistModal").style.display = "block";
}

/**
 * Load equipment for event
 */
function loadEquipmentForEvent(bookingId) {
  console.log("Loading equipment for booking:", bookingId); // Debug log

  fetch(`/facilitator/checklist/equipment/${bookingId}`)
    .then((response) => response.json())
    .then((data) => {
      console.log("Equipment data:", data); // Debug log
      if (data.success) {
        displayPlanEquipment(data.equipment.plan_included);
        displayRentedEquipment(data.equipment.additional_rented);
      } else {
        alert("Failed to load equipment: " + (data.message || "Unknown error"));
      }
    })
    .catch((error) => {
      console.error("Error loading equipment:", error);
      alert("Failed to load equipment. Please try again.");
    });
}

/**
 * Display plan included equipment
 */
function displayPlanEquipment(equipment) {
  const container = document.getElementById("planEquipmentList");

  if (!equipment || equipment.length === 0) {
    container.innerHTML =
      '<p style="color: #64748b;">No plan equipment included</p>';
    return;
  }

  container.innerHTML = equipment
    .map(
      (item, index) => `
        <div class="equipment-item">
            <div class="equipment-info">
                <h4>${escapeHtml(item.name)}</h4>
                <div class="equipment-meta">
                    <span>Category: ${capitalizeFirst(item.category)}</span>
                    <span>Expected: ${item.quantity} ${
        item.unit || "pcs"
      }</span>
                    <span>Available in Stock: ${item.available}</span>
                    ${
                      item.is_mandatory
                        ? '<span style="color: var(--danger-color); font-weight: 700;">⚠️ Mandatory</span>'
                        : ""
                    }
                </div>
            </div>
            <div>
                <label style="font-size: 0.85rem; color: #64748b; display: block; margin-bottom: 5px;">Actual Qty</label>
                <input type="number" 
                       class="quantity-input" 
                       value="${item.quantity}" 
                       min="0" 
                       max="${item.available}"
                       data-equipment-id="${item.id}"
                       data-equipment-name="${escapeHtml(item.name)}"
                       data-expected="${item.quantity}"
                       onchange="updateEquipmentCheck(this, 'plan', ${index})">
            </div>
            <div>
                <label style="font-size: 0.85rem; color: #64748b; display: block; margin-bottom: 5px;">Condition</label>
                <select class="condition-select" onchange="updateEquipmentCheck(this, 'plan', ${index})">
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                    <option value="damaged">Damaged</option>
                </select>
            </div>
            <div style="text-align: center;">
                <label style="font-size: 0.85rem; color: #64748b; display: block; margin-bottom: 5px;">Available</label>
                <input type="checkbox" 
                       class="availability-checkbox" 
                       checked 
                       onchange="updateEquipmentCheck(this, 'plan', ${index})">
            </div>
        </div>
    `
    )
    .join("");

  // Initialize equipment checks
  equipment.forEach((item, index) => {
    equipmentChecks.push({
      equipment_id: item.id,
      equipment_name: item.name,
      expected_quantity: item.quantity,
      actual_quantity: item.quantity,
      condition: "good",
      is_available: true,
      remarks: "",
      type: "plan",
    });
  });

  console.log("Plan equipment checks initialized:", equipmentChecks); // Debug log
}

/**
 * Display rented equipment
 */
function displayRentedEquipment(equipment) {
  const container = document.getElementById("rentedEquipmentList");

  if (!equipment || equipment.length === 0) {
    container.innerHTML =
      '<p style="color: #64748b;">No additional rented equipment</p>';
    return;
  }

  const planEquipmentCount = equipmentChecks.length;

  container.innerHTML = equipment
    .map((item, index) => {
      const globalIndex = planEquipmentCount + index;
      return `
        <div class="equipment-item">
            <div class="equipment-info">
                <h4>${escapeHtml(item.name)}</h4>
                <div class="equipment-meta">
                    <span>Category: ${capitalizeFirst(item.category)}</span>
                    <span>Expected: ${item.quantity} ${
        item.unit || "pcs"
      }</span>
                    <span>Rate: ₱${parseFloat(item.rate).toFixed(2)}/${
        item.unit || "pc"
      }</span>
                    <span>Available in Stock: ${item.available}</span>
                </div>
            </div>
            <div>
                <label style="font-size: 0.85rem; color: #64748b; display: block; margin-bottom: 5px;">Actual Qty</label>
                <input type="number" 
                       class="quantity-input" 
                       value="${item.quantity}" 
                       min="0" 
                       max="${item.available}"
                       data-equipment-id="${item.id}"
                       data-equipment-name="${escapeHtml(item.name)}"
                       data-expected="${item.quantity}"
                       onchange="updateEquipmentCheck(this, 'rented', ${globalIndex})">
            </div>
            <div>
                <label style="font-size: 0.85rem; color: #64748b; display: block; margin-bottom: 5px;">Condition</label>
                <select class="condition-select" onchange="updateEquipmentCheck(this, 'rented', ${globalIndex})">
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                    <option value="damaged">Damaged</option>
                </select>
            </div>
            <div style="text-align: center;">
                <label style="font-size: 0.85rem; color: #64748b; display: block; margin-bottom: 5px;">Available</label>
                <input type="checkbox" 
                       class="availability-checkbox" 
                       checked 
                       onchange="updateEquipmentCheck(this, 'rented', ${globalIndex})">
            </div>
        </div>
    `;
    })
    .join("");

  // Add to equipment checks
  equipment.forEach((item, index) => {
    equipmentChecks.push({
      equipment_id: item.id,
      equipment_name: item.name,
      expected_quantity: item.quantity,
      actual_quantity: item.quantity,
      condition: "good",
      is_available: true,
      remarks: "",
      type: "rented",
    });
  });

  console.log("Rented equipment checks added:", equipmentChecks); // Debug log
}

/**
 * Update equipment check data
 */
function updateEquipmentCheck(element, type, index) {
  const row = element.closest(".equipment-item");
  const quantityInput = row.querySelector(".quantity-input");
  const conditionSelect = row.querySelector(".condition-select");
  const availabilityCheckbox = row.querySelector(".availability-checkbox");

  if (equipmentChecks[index]) {
    equipmentChecks[index].actual_quantity = parseInt(quantityInput.value) || 0;
    equipmentChecks[index].condition = conditionSelect.value;
    equipmentChecks[index].is_available = availabilityCheckbox.checked;

    // Auto-generate remarks for discrepancies
    if (
      equipmentChecks[index].actual_quantity !==
      equipmentChecks[index].expected_quantity
    ) {
      equipmentChecks[
        index
      ].remarks = `Quantity mismatch: Expected ${equipmentChecks[index].expected_quantity}, Found ${equipmentChecks[index].actual_quantity}`;
    } else if (equipmentChecks[index].condition !== "good") {
      equipmentChecks[
        index
      ].remarks = `Equipment condition: ${equipmentChecks[index].condition}`;
    } else if (!equipmentChecks[index].is_available) {
      equipmentChecks[index].remarks = "Equipment not available";
    } else {
      equipmentChecks[index].remarks = "";
    }

    console.log("Equipment check updated:", equipmentChecks[index]); // Debug log
  }
}

/**
 * Submit checklist
 */
function submitChecklist() {
  const notes = document.getElementById("facilitatorNotes").value;
  const signature = document
    .getElementById("facilitatorSignature")
    .value.trim();

  // Validation
  if (!signature) {
    alert("Please provide your signature");
    return;
  }

  if (!currentBookingId || !currentEventId) {
    alert("Booking or Event ID is missing. Please refresh and try again.");
    return;
  }

  // Check for critical issues
  const criticalIssues = equipmentChecks.filter(
    (check) =>
      !check.is_available ||
      check.condition === "damaged" ||
      check.actual_quantity < check.expected_quantity
  );

  if (criticalIssues.length > 0) {
    const confirmMsg = `Warning: ${criticalIssues.length} equipment issue(s) detected. Do you want to proceed?`;
    if (!confirm(confirmMsg)) {
      return;
    }
  }

  // Disable submit button
  const submitBtn = document.getElementById("submitChecklistBtn");
  submitBtn.disabled = true;
  submitBtn.textContent = "⏳ Submitting...";

  console.log("Submitting checklist:", {
    booking_id: currentBookingId,
    event_id: currentEventId,
    equipment_checks: equipmentChecks,
    notes: notes,
    facilitator_signature: signature,
  }); // Debug log

  // Submit data
  fetch("/facilitator/checklist/submit", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      booking_id: currentBookingId,
      event_id: currentEventId,
      equipment_checks: equipmentChecks,
      notes: notes,
      facilitator_signature: signature,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Submit response:", data); // Debug log
      if (data.success) {
        alert("✅ Checklist submitted successfully!");
        closeChecklistModal();
        loadPendingEvents();

        // Optionally open report
        if (data.report_url) {
          const openReport = confirm(
            "Checklist submitted! Would you like to download the report?"
          );
          if (openReport) {
            window.open(data.report_url, "_blank");
          }
        }
      } else {
        alert("Failed to submit checklist: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error submitting checklist:", error);
      alert(
        "Failed to submit checklist. Please check the console for details."
      );
    })
    .finally(() => {
      submitBtn.disabled = false;
      submitBtn.textContent = "✅ Submit Checklist & Confirm Event";
    });
}

/**
 * Close checklist modal
 */
function closeChecklistModal() {
  document.getElementById("checklistModal").style.display = "none";
  document.getElementById("facilitatorNotes").value = "";
  document.getElementById("facilitatorSignature").value = "";
  currentBookingId = null;
  currentEventId = null;
  equipmentChecks = [];
}

/**
 * Filter events
 */
function filterEvents() {
  const filter = document.getElementById("dateFilter").value;
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  let filteredEvents = currentEvents;

  switch (filter) {
    case "today":
      filteredEvents = currentEvents.filter((event) => {
        const eventDate = new Date(event.event_date);
        eventDate.setHours(0, 0, 0, 0);
        return eventDate.getTime() === today.getTime();
      });
      break;
    case "tomorrow":
      const tomorrow = new Date(today);
      tomorrow.setDate(tomorrow.getDate() + 1);
      filteredEvents = currentEvents.filter((event) => {
        const eventDate = new Date(event.event_date);
        eventDate.setHours(0, 0, 0, 0);
        return eventDate.getTime() === tomorrow.getTime();
      });
      break;
    case "week":
      const weekEnd = new Date(today);
      weekEnd.setDate(weekEnd.getDate() + 7);
      filteredEvents = currentEvents.filter((event) => {
        const eventDate = new Date(event.event_date);
        return eventDate >= today && eventDate <= weekEnd;
      });
      break;
  }

  displayEvents(filteredEvents);
}

/**
 * Refresh events
 */
function refreshEvents() {
  loadPendingEvents();
}

/**
 * Load and update stats
 */
function loadStats() {
  // This would be implemented with actual API calls
  document.getElementById("pendingEventsCount").textContent = "-";
  document.getElementById("completedToday").textContent = "-";
  document.getElementById("equipmentIssues").textContent = "-";
}

function updateStats(events) {
  document.getElementById("pendingEventsCount").textContent = events.length;

  // Count today's events
  const today = new Date().toISOString().split("T")[0];
  const todayEvents = events.filter((e) => e.event_date === today).length;
  document.getElementById("completedToday").textContent = "0"; // Would need completed data

  document.getElementById("equipmentIssues").textContent = "0"; // Would need issues data
}

/**
 * Utility functions
 */
function showLoading() {
  document.getElementById("loadingIndicator").style.display = "block";
  document.getElementById("eventsContainer").style.display = "none";
  document.getElementById("noEventsMessage").style.display = "none";
}

function hideLoading() {
  document.getElementById("loadingIndicator").style.display = "none";
}

function showError(message) {
  alert(message);
}

function formatDate(dateString) {
  if (!dateString) return "N/A";
  const date = new Date(dateString);
  const options = { year: "numeric", month: "short", day: "numeric" };
  return date.toLocaleDateString("en-US", options);
}

function formatTime(timeString) {
  if (!timeString) return "N/A";
  const [hours, minutes] = timeString.split(":");
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? "PM" : "AM";
  const displayHour = hour % 12 || 12;
  return `${displayHour}:${minutes} ${ampm}`;
}

function capitalizeFirst(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.slice(1);
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
  return String(text).replace(/[&<>"']/g, (m) => map[m]);
}

/**
 * Toggle sidebar on mobile
 */
function toggleSidebar() {
  const sidebar = document.querySelector(".sidebar");
  sidebar.classList.toggle("active");
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modal = document.getElementById("checklistModal");
  if (event.target === modal) {
    closeChecklistModal();
  }
};
