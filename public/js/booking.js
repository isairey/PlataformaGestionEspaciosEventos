// Global variables
let facilityData = {};
let addonsData = [];
let equipmentData = [];
let selectedPlan = null;
let selectedAddons = [];
let selectedEquipment = {};
let currentFacility = null;
let MAINTENANCE_FEE = 2000; // Will be loaded from settings
let HOURLY_RATE = 500; // Will be loaded from settings
let OVERTIME_RATE = 5000; // Will be loaded from settings

// Load data from database on page load
document.addEventListener("DOMContentLoaded", function () {
  loadSettings(); // Load settings first
  loadFacilityData();
  loadAddonsData();
  // DO NOT load equipment on page load - only load when user selects a date in the modal

  // Set minimum date to today
  const eventDateField = document.getElementById("eventDate");
  if (eventDateField) {
    const today = new Date().toISOString().split("T")[0];
    eventDateField.min = today;
  }

  // Sidebar toggle functionality
  const toggleBtn = document.querySelector(".toggle-btn");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", function () {
      document.querySelector(".sidebar").classList.toggle("active");
      document.querySelector(".main-content").classList.toggle("active");
    });
  }
});

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
        MAINTENANCE_FEE = parseFloat(result.data.maintenance_fee) || 2000;
        HOURLY_RATE = parseFloat(result.data.extended_hours_rate) || 500;
        OVERTIME_RATE = parseFloat(result.data.overtime_rate) || 5000;

        // Update the hourly rate label if it exists
        const hourlyRateLabel = document.getElementById("hourlyRateLabel");
        if (hourlyRateLabel) {
          hourlyRateLabel.textContent = `₱${HOURLY_RATE}`;
        }

        // Update the maintenance fee display if it exists
        const maintenanceCost = document.getElementById("maintenanceCost");
        if (maintenanceCost) {
          maintenanceCost.textContent = `₱${MAINTENANCE_FEE.toLocaleString()}`;
        }

        console.log("Settings loaded:", {
          MAINTENANCE_FEE,
          HOURLY_RATE,
          OVERTIME_RATE,
        });
      }
    }
  } catch (error) {
    console.error("Error loading settings:", error);
    // Keep default values if loading fails
  }
}

// Load facility data from database
async function loadFacilityData() {
  try {
    // First, get the list of active facilities from API
    const listResponse = await fetch("/api/facilities/list");

    if (!listResponse.ok) {
      console.error(
        `Failed to load facilities list: HTTP ${listResponse.status}`
      );
      showToast("Warning: Could not load facilities list", "warning");
      return;
    }

    const listData = await listResponse.json();
    console.log("Facilities list from API:", listData);

    if (!listData.success || !listData.facilities) {
      console.warn("No facilities returned from API");
      showToast("Warning: No facilities available", "warning");
      return;
    }

    // Extract facility keys from the list
    const facilityKeys = listData.facilities.map((f) => f.facility_key);
    console.log("Facility keys to load:", facilityKeys);

    facilityData = {};

    // Load detailed data for each facility
    for (const facilityKey of facilityKeys) {
      try {
        const response = await fetch(`/api/facilities/${facilityKey}/data`);

        if (!response.ok) {
          console.error(
            `Failed to load ${facilityKey}: HTTP ${response.status}`
          );
          continue;
        }

        const data = await response.json();
        console.log(`Response for ${facilityKey}:`, data);

        if (data.success && data.facility) {
          facilityData[facilityKey] = data.facility;

          // DEBUG LOG: Check if additional_hours_rate is present
          console.log(
            `[DEBUG] ${facilityKey} additional_hours_rate:`,
            data.facility.additional_hours_rate
          );
          console.log(
            `[DEBUG] ${facilityKey} full facility object:`,
            data.facility
          );
        } else {
          console.warn(`No facility data returned for ${facilityKey}`);
        }
      } catch (error) {
        console.error(`Error loading ${facilityKey}:`, error);
      }
    }

    console.log("Facility data loaded:", facilityData);

    if (Object.keys(facilityData).length > 0) {
      showToast("Facilities loaded successfully", "success");
    } else {
      showToast("Warning: No facility data loaded", "warning");
    }
  } catch (error) {
    console.error("Error loading facility data:", error);
    showToast("Failed to load facility data", "error");
  }
}
// Load addons data from database
async function loadAddonsData() {
  try {
    const response = await fetch("/api/addons");

    // Debug: log response status and headers
    console.log("Addons API - Response status:", response.status);
    if (!response.ok) {
      console.error(`API returned status ${response.status}`);
      const text = await response.text();
      console.error("Response body (first 300 chars):", text.substring(0, 300));
      showToast(`Failed to load addons (HTTP ${response.status})`, "error");
      return;
    }

    const result = await response.json();

    // Debug: log what we received
    console.log("Raw API response:", result);
    console.log("Response type:", typeof result);
    console.log("Is array?:", Array.isArray(result));

    // Handle wrapped response format {success, addons, count}
    if (result.success === false) {
      console.error("API returned success: false", result.message);
      showToast(result.message || "Failed to load addons", "error");
      return;
    }

    let data = result.addons || result.data || result;

    // If data is still not an array, convert it
    if (!Array.isArray(data)) {
      console.warn("Data is not an array, converting:", data);
      // If it's an object with items, try to extract array
      if (data && typeof data === "object" && !Array.isArray(data)) {
        // Try to find array properties
        const arrays = Object.values(data).filter((v) => Array.isArray(v));
        if (arrays.length > 0) {
          data = arrays[0];
        } else {
          data = [];
        }
      } else {
        data = Array.isArray(data) ? data : [];
      }
    }

    console.log("Extracted data:", data);
    console.log("Data is array?:", Array.isArray(data));

    addonsData = data
      .filter((addon) => addon.addon_key !== "additional-hours")
      .map((addon) => ({
        id: addon.addon_key,
        name: addon.name,
        description: addon.description,
        price: parseFloat(addon.price),
      }));

    console.log("Addons data loaded successfully:", addonsData);
  } catch (error) {
    console.error("Error loading addons data:", error);
    console.error("Error stack:", error.stack);
    showToast("Failed to load addons data: " + error.message, "error");
  }
}

// Load equipment data from database
async function loadEquipmentData(eventDate = null, facilityId = null) {
  try {
    // Build URL with query parameters if date/facility provided
    let url = "/api/equipment";
    const params = new URLSearchParams();

    if (eventDate) {
      params.append("event_date", eventDate);
    }
    if (facilityId) {
      params.append("facility_id", facilityId);
    }

    if (params.toString()) {
      url += "?" + params.toString();
    }

    const response = await fetch(url);
    const result = await response.json();

    console.log("=== EQUIPMENT API DEBUG ===");
    console.log("API Success:", result.success);
    console.log("Equipment count:", result.equipment?.length);
    console.log("Filtered by date:", result.filtered_by_date);
    console.log("Event date:", result.event_date);

    if (!result.success) {
      throw new Error(result.message || "Failed to load equipment");
    }

    // Filter equipment
    equipmentData = result.equipment
      .filter((equipment) => {
        const isRentable =
          equipment.is_rentable == 1 ||
          equipment.is_rentable === true ||
          equipment.is_rentable === "1";
        const hasRate = parseFloat(equipment.rate || 0) > 0;
        const isFurniture =
          equipment.category === "furniture" ||
          equipment.category === "logistics";

        return isFurniture && isRentable && hasRate;
      })
      .map((equipment) => ({
        id: equipment.id.toString(),
        name: equipment.name,
        rate: parseFloat(equipment.rate || 0),
        unit: equipment.unit || "piece",
        available: result.filtered_by_date
          ? parseInt(equipment.available_on_date || 0)
          : parseInt(equipment.available || 0),
        booked_quantity: parseInt(equipment.booked_quantity || 0),
        category: equipment.category,
      }));

    console.log("Filtered equipment:", equipmentData.length);

    // If modal is open, refresh equipment display
    if (document.getElementById("bookingModal").style.display === "block") {
      populateEquipment();
    }
  } catch (error) {
    console.error("Error loading equipment data:", error);
    showToast("Failed to load equipment data", "error");
  }
}
async function debugEquipmentData() {
  console.log("=== EQUIPMENT DEBUG START ===");

  try {
    const response = await fetch("/api/equipment");
    const result = await response.json();

    console.log("API Response Success:", result.success);
    console.log("Total Equipment from API:", result.equipment?.length || 0);

    if (result.equipment && result.equipment.length > 0) {
      console.log("\n--- RAW EQUIPMENT DATA ---");
      result.equipment.forEach((eq, index) => {
        console.log(`\n${index + 1}. ${eq.name}`);
        console.log("   Category:", eq.category);
        console.log("   Rate:", eq.rate);
        console.log("   Is Rentable:", eq.is_rentable);
        console.log("   Available:", eq.available);
        console.log(
          "   Pass Filter?",
          (eq.category === "furniture" || eq.category === "logistics") &&
            eq.is_rentable === 1 &&
            parseFloat(eq.rate || 0) > 0
        );
      });

      // Filter same way as loadEquipmentData
      const filtered = result.equipment.filter((equipment) => {
        return (
          (equipment.category === "furniture" ||
            equipment.category === "logistics") &&
          equipment.is_rentable === 1 &&
          parseFloat(equipment.rate || 0) > 0
        );
      });

      console.log("\n--- FILTERED EQUIPMENT ---");
      console.log("Total after filter:", filtered.length);
      filtered.forEach((eq) => {
        console.log(`- ${eq.name}: ₱${eq.rate} ${eq.unit}`);
      });
    } else {
      console.log("No equipment returned from API!");
    }
  } catch (error) {
    console.error("Debug Error:", error);
  }

  console.log("=== EQUIPMENT DEBUG END ===\n");
}

// Debug function removed from auto-load - equipment now only loads when user selects a date

function openBookingModal(facilityId) {
  currentFacility = facilityId;
  const facility = facilityData[facilityId];

  if (!facility) {
    showToast("Facility data not found", "error");
    return;
  }

  // DEBUG LOG: Log the entire facility object
  console.log("=== OPENING BOOKING MODAL DEBUG ===");
  console.log("Current Facility ID:", facilityId);
  console.log("Facility Object:", facility);
  console.log(
    "additional_hours_rate from facility:",
    facility.additional_hours_rate
  );
  console.log(
    "Type of additional_hours_rate:",
    typeof facility.additional_hours_rate
  );

  // Update the hourly rate for this specific facility
  if (facility.additional_hours_rate) {
    HOURLY_RATE = parseFloat(facility.additional_hours_rate);
    console.log(
      `✓ Facility-specific rate loaded: ₱${HOURLY_RATE}/hour for ${facility.name}`
    );
  } else {
    HOURLY_RATE = 500; // Fallback to default
    console.log(
      `✗ additional_hours_rate is missing! Using default rate: ₱${HOURLY_RATE}/hour for ${facility.name}`
    );
    console.log("Facility keys available:", Object.keys(facility));
  }

  // Update the hourly rate label in the UI
  const hourlyRateLabel = document.getElementById("hourlyRateLabel");
  if (hourlyRateLabel) {
    hourlyRateLabel.textContent = `₱${HOURLY_RATE.toLocaleString()}`;
    console.log("Updated hourly rate label to:", hourlyRateLabel.textContent);
  }
  console.log("=== END OPENING BOOKING MODAL DEBUG ===");

  document.getElementById("modalTitle").textContent = `Book ${facility.name}`;
  document.getElementById("bookingModal").style.display = "block";

  // Populate plans
  populatePlans(facility.plans);

  // Populate add-ons
  populateAddons();

  // DO NOT populate equipment initially - user must select date first
  showEquipmentDatePrompt();

  // Reset selections
  selectedPlan = null;
  selectedAddons = [];
  selectedEquipment = {};
  document.getElementById("additionalHours").value = 0;
  document.getElementById("eventDate").value = ""; // Clear date field
  updateCostSummary();

  // Initialize validation listeners
  setTimeout(() => {
    addBookingValidationListeners();
    initializeAddressCounter();
    addEventDateListener(); // Add listener for date changes
  }, 100);
}

// Show prompt to select date before displaying equipment
function showEquipmentDatePrompt() {
  const equipmentGrid = document.getElementById("equipmentGrid");
  if (!equipmentGrid) return;

  equipmentGrid.innerHTML = `
    <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px; border: 2px dashed #dee2e6;">
      <div style="font-size: 48px; margin-bottom: 1rem;">📅</div>
      <h3 style="color: #495057; margin-bottom: 0.5rem;">Select Event Date First</h3>
      <p style="color: #6c757d; margin: 0;">
        Please select your event date above to see available equipment for that specific date.
      </p>
    </div>
  `;
}

// Add event listener for event date changes to reload equipment
function addEventDateListener() {
  const eventDateField = document.getElementById("eventDate");
  if (!eventDateField) return;

  // Remove old listener if exists
  const newEventDateField = eventDateField.cloneNode(true);
  eventDateField.parentNode.replaceChild(newEventDateField, eventDateField);

  // Add new listener
  newEventDateField.addEventListener("change", async function () {
    const selectedDate = this.value;

    if (!selectedDate || !currentFacility) return;

    // Get facility ID from facility data
    const facilityKey = currentFacility;
    const facilityId = getFacilityIdFromKey(facilityKey);

    // Show loading message in equipment grid
    const equipmentGrid = document.getElementById("equipmentGrid");
    if (equipmentGrid) {
      equipmentGrid.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
          <div class="spinner" style="margin: 0 auto 1rem;">⏳</div>
          <p>Loading available equipment for ${selectedDate}...</p>
        </div>
      `;
    }

    // Reload equipment with date filter (no facility filter - global availability)
    await loadEquipmentData(selectedDate, null);

    // Update equipment display
    populateEquipment();

    showToast("Equipment availability updated for " + selectedDate, "success");
  });
}

// Helper function to get facility ID from key
function getFacilityIdFromKey(facilityKey) {
  const facilityIds = {
    auditorium: 1,
    gymnasium: 2,
    "function-hall": 4,
    "pearl-restaurant": 6,
    "staff-house": 7,
    classrooms: 8,
  };
  return facilityIds[facilityKey] || null;
}
function closeModal() {
  document.getElementById("bookingModal").style.display = "none";
  document.getElementById("bookingForm").reset();
}

function populatePlans(plans) {
  const plansGrid = document.getElementById("plansGrid");
  plansGrid.innerHTML = "";

  if (!plans || plans.length === 0) {
    plansGrid.innerHTML =
      '<p class="no-data">No plans available for this facility.</p>';
    return;
  }

  plans.forEach((plan) => {
    const planCard = document.createElement("div");
    planCard.className = "plan-card";
    planCard.onclick = () => selectPlan(plan.id);

    // Build features list
    let featuresHTML = "";
    if (plan.features && plan.features.length > 0) {
      featuresHTML = `
        <div class="plan-section">
          <strong>Features:</strong>
          <ul class="plan-features">
            ${plan.features.map((feature) => `<li>${feature}</li>`).join("")}
          </ul>
        </div>
      `;
    }

    // Build included equipment list
    let equipmentHTML = "";
    if (plan.included_equipment && plan.included_equipment.length > 0) {
      equipmentHTML = `
        <div class="plan-section">
          <strong>Included Equipment:</strong>
          <ul class="plan-equipment">
            ${plan.included_equipment
              .map(
                (eq) =>
                  `<li>${eq.quantity_included} ${eq.unit} - ${eq.name}</li>`
              )
              .join("")}
          </ul>
        </div>
      `;
    }

    planCard.innerHTML = `
      <div class="plan-header">
        <div class="plan-name">${plan.name}</div>
        <div class="plan-duration">${plan.duration}</div>
      </div>
      <div class="plan-price">₱${parseFloat(plan.price).toLocaleString()}</div>
      ${featuresHTML}
      ${equipmentHTML}
    `;

    plansGrid.appendChild(planCard);
  });
}

function populateAddons() {
  const addonsGrid = document.getElementById("addonsGrid");
  addonsGrid.innerHTML = "";

  if (!addonsData || addonsData.length === 0) {
    addonsGrid.innerHTML = '<p class="no-data">No add-ons available.</p>';
    return;
  }

  addonsData.forEach((addon) => {
    const addonCard = document.createElement("div");
    addonCard.className = "addon-card";

    addonCard.innerHTML = `
            <input type="checkbox" class="addon-checkbox" id="addon-${
              addon.id
            }" 
                onchange="toggleAddon('${addon.id}')">
            <div class="addon-info">
                <div class="addon-name">${addon.name}</div>
                <div class="addon-description">${addon.description}</div>
                <div class="addon-price">₱${addon.price.toLocaleString()}</div>
            </div>
        `;

    addonsGrid.appendChild(addonCard);
  });
}

function populateEquipment() {
  const equipmentGrid = document.getElementById("equipmentGrid");
  equipmentGrid.innerHTML = "";

  if (!equipmentData || equipmentData.length === 0) {
    equipmentGrid.innerHTML = '<p class="no-data">No equipment available.</p>';
    return;
  }

  equipmentData.forEach((equipment) => {
    const equipmentCard = document.createElement("div");
    equipmentCard.className = "equipment-card";

    equipmentCard.innerHTML = `
            <div class="equipment-info">
                <div class="equipment-name">${equipment.name}</div>
                <div class="equipment-rate">
                    ${
                      equipment.rate === 0
                        ? "Included"
                        : `₱${equipment.rate} ${equipment.unit}`
                    }
                </div>
            </div>
            <div class="equipment-quantity">
                ${
                  equipment.rate === 0
                    ? '<span class="included-badge">Included</span>'
                    : `<input type="number" class="quantity-input" id="qty-${equipment.id}" 
                     min="0" max="999" value="0" onchange="updateEquipment('${equipment.id}')">`
                }
            </div>
        `;

    equipmentGrid.appendChild(equipmentCard);
  });
}

function updateEquipment(equipmentId) {
  const quantityInput = document.getElementById(`qty-${equipmentId}`);
  const quantity = parseInt(quantityInput.value) || 0;

  if (quantity > 0) {
    selectedEquipment[equipmentId] = quantity;
  } else {
    delete selectedEquipment[equipmentId];
  }

  updateCostSummary();
}

function selectPlan(planId) {
  // Remove previous selection
  document.querySelectorAll(".plan-card").forEach((card) => {
    card.classList.remove("selected");
  });

  // Add selection to clicked plan
  event.target.closest(".plan-card").classList.add("selected");

  // Find and store selected plan
  const facility = facilityData[currentFacility];
  selectedPlan = facility.plans.find((plan) => plan.id == planId);

  // Ensure price is stored as a number
  if (selectedPlan) {
    selectedPlan.price = parseFloat(selectedPlan.price);
    console.log(
      "Plan selected:",
      selectedPlan.name,
      "Price:",
      selectedPlan.price
    );
    showToast(`Selected: ${selectedPlan.name}`, "success");
  }

  updateCostSummary();
}

function toggleAddon(addonId) {
  const checkbox = document.getElementById(`addon-${addonId}`);
  const addon = addonsData.find((a) => a.id === addonId);

  if (checkbox.checked) {
    selectedAddons.push(addonId);
    if (addon) {
      showToast(`Added: ${addon.name}`, "success");
    }
  } else {
    selectedAddons = selectedAddons.filter((id) => id !== addonId);
    if (addon) {
      showToast(`Removed: ${addon.name}`, "info");
    }
  }

  updateCostSummary();
}

function updateCostSummary() {
  const baseCost = document.getElementById("baseCost");
  const maintenanceCost = document.getElementById("maintenanceCost");
  const addonCosts = document.getElementById("addonCosts");
  const totalCost = document.getElementById("totalCost");

  let basePrice = 0;
  let addonsPrice = 0;
  let equipmentPrice = 0;
  let additionalHoursPrice = 0;

  // Calculate base cost
  if (selectedPlan && selectedPlan.price) {
    basePrice = parseFloat(selectedPlan.price);
    baseCost.textContent = `₱${basePrice.toLocaleString()}`;
  } else {
    baseCost.textContent = "₱0";
  }

  // Update maintenance fee display
  if (maintenanceCost) {
    maintenanceCost.textContent = `₱${MAINTENANCE_FEE.toLocaleString()}`;
  }

  // Calculate additional hours cost
  const additionalHours =
    parseInt(document.getElementById("additionalHours").value) || 0;
  if (additionalHours > 0) {
    additionalHoursPrice = additionalHours * HOURLY_RATE;
  }

  // Clear previous addon costs
  addonCosts.innerHTML = "";

  // Add additional hours if any
  if (additionalHoursPrice > 0) {
    const hoursRow = document.createElement("div");
    hoursRow.className = "cost-row";
    hoursRow.innerHTML = `
      <span>Additional Hours (${additionalHours}h):</span>
      <span>₱${additionalHoursPrice.toLocaleString()}</span>
    `;
    addonCosts.appendChild(hoursRow);
  }

  // Calculate addons cost
  selectedAddons.forEach((addonId) => {
    const addon = addonsData.find((a) => a.id === addonId);
    if (addon) {
      const addonPrice = parseFloat(addon.price);
      addonsPrice += addonPrice;

      const addonRow = document.createElement("div");
      addonRow.className = "cost-row";
      addonRow.innerHTML = `
        <span>${addon.name}:</span>
        <span>₱${addonPrice.toLocaleString()}</span>
      `;
      addonCosts.appendChild(addonRow);
    }
  });

  // Calculate equipment cost
  Object.keys(selectedEquipment).forEach((equipmentId) => {
    const equipment = equipmentData.find((e) => e.id === equipmentId);
    const quantity = selectedEquipment[equipmentId];

    if (equipment && quantity > 0 && equipment.rate > 0) {
      const rate = parseFloat(equipment.rate);
      const itemCost = rate * quantity;
      equipmentPrice += itemCost;

      const equipmentRow = document.createElement("div");
      equipmentRow.className = "cost-row";
      equipmentRow.innerHTML = `
        <span>${equipment.name} (${quantity}x):</span>
        <span>₱${itemCost.toLocaleString()}</span>
      `;
      addonCosts.appendChild(equipmentRow);
    }
  });

  // Calculate total (always include maintenance fee)
  const total =
    basePrice +
    addonsPrice +
    equipmentPrice +
    additionalHoursPrice +
    MAINTENANCE_FEE;
  totalCost.textContent = `₱${total.toLocaleString()}`;

  // Debug log
  console.log("Cost Summary Updated:", {
    basePrice,
    addonsPrice,
    equipmentPrice,
    additionalHoursPrice,
    maintenanceFee: MAINTENANCE_FEE,
    total,
  });
}

// Calculate total duration from plan + extended hours
function calculateTotalDuration() {
  if (!selectedPlan) return 0;

  // Extract hours from plan duration with better error handling
  const durationMatch = selectedPlan.duration.match(/\d+/);
  const planDuration = durationMatch ? parseInt(durationMatch[0]) : 0;
  const additionalHours =
    parseInt(document.getElementById("additionalHours").value) || 0;

  const totalDuration = planDuration + additionalHours;
  console.log("Duration calculation:", {
    planDuration,
    additionalHours,
    totalDuration,
  });

  return totalDuration;
}

async function submitBooking() {
  console.log("Starting booking submission...");

  // Clear all previous errors
  document.querySelectorAll(".field-error").forEach((field) => {
    hideInlineError(field.id);
  });

  const form = document.getElementById("bookingForm");
  let isValid = true;
  let firstErrorField = null;

  // Validate Client Name
  const clientName = document.getElementById("clientName");
  const clientNameValue = clientName.value.trim();
  if (!clientNameValue) {
    showInlineError("clientName", "Client name is required");
    isValid = false;
    if (!firstErrorField) firstErrorField = clientName;
  } else if (clientNameValue.length < 3) {
    showInlineError("clientName", "Name must be at least 3 characters");
    isValid = false;
    if (!firstErrorField) firstErrorField = clientName;
  }

  // Validate Contact Number
  const contactNumber = document.getElementById("contactNumber");
  const contactValue = contactNumber.value.trim();
  if (!contactValue) {
    showInlineError("contactNumber", "Contact number is required");
    isValid = false;
    if (!firstErrorField) firstErrorField = contactNumber;
  } else {
    const digitsOnly = contactValue.replace(/\D/g, "");
    if (digitsOnly.length < 7) {
      showInlineError(
        "contactNumber",
        "Contact number must have at least 7 digits"
      );
      isValid = false;
      if (!firstErrorField) firstErrorField = contactNumber;
    }
  }

  // Validate Email Address
  const emailAddress = document.getElementById("emailAddress");
  const emailValue = emailAddress.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailValue) {
    showInlineError("emailAddress", "Email address is required");
    isValid = false;
    if (!firstErrorField) firstErrorField = emailAddress;
  } else if (!emailRegex.test(emailValue)) {
    showInlineError(
      "emailAddress",
      "Please enter a valid email address (example@domain.com)"
    );
    isValid = false;
    if (!firstErrorField) firstErrorField = emailAddress;
  }

  // Validate Address (Required)
  const address = document.getElementById("address");
  const addressValue = address.value.trim();
  if (!addressValue) {
    showInlineError("address", "Complete address is required");
    isValid = false;
    if (!firstErrorField) firstErrorField = address;
  } else if (addressValue.length < 10) {
    showInlineError(
      "address",
      "Address must be at least 10 characters. Include street, city, and province."
    );
    isValid = false;
    if (!firstErrorField) firstErrorField = address;
  }

  // Validate Event Date
  const eventDate = document.getElementById("eventDate");
  const eventDateValue = eventDate.value;
  if (!eventDateValue) {
    showInlineError("eventDate", "Event date is required");
    isValid = false;
    if (!firstErrorField) firstErrorField = eventDate;
  } else {
    const selectedDate = new Date(eventDateValue);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (selectedDate < today) {
      showInlineError("eventDate", "Event date cannot be in the past");
      isValid = false;
      if (!firstErrorField) firstErrorField = eventDate;
    }
  }

  // Validate Event Time
  const eventTime = document.getElementById("eventTime");
  if (!eventTime.value) {
    showInlineError("eventTime", "Event time is required");
    isValid = false;
    if (!firstErrorField) firstErrorField = eventTime;
  }

  // Validate Event Title
  const eventTitle = document.getElementById("eventTitle");
  const eventTitleValue = eventTitle.value.trim();
  if (!eventTitleValue) {
    showInlineError("eventTitle", "Event title is required");
    isValid = false;
    if (!firstErrorField) firstErrorField = eventTitle;
  } else if (eventTitleValue.length < 5) {
    showInlineError("eventTitle", "Event title must be at least 5 characters");
    isValid = false;
    if (!firstErrorField) firstErrorField = eventTitle;
  }

  // Validate Plan Selection
  if (!selectedPlan) {
    console.log("No plan selected");
    showToast("Please select a plan before proceeding", "error");
    isValid = false;
  }

  // If form is invalid, show toast and focus first error
  if (!isValid) {
    if (firstErrorField) {
      firstErrorField.focus();
      firstErrorField.scrollIntoView({ behavior: "smooth", block: "center" });
    }
    showToast("Please fix the errors in the form", "error");
    return;
  }

  // Show loading toast
  showToast("Creating booking...", "info");

  // Collect addon IDs for selected addons
  const selectedAddonIds = selectedAddons;

  // Get additional hours
  const additionalHours =
    parseInt(document.getElementById("additionalHours").value) || 0;

  // Calculate total duration
  const totalDuration = calculateTotalDuration();

  // Prepare booking data
  const formData = {
    facility_key: currentFacility,
    plan_id: selectedPlan.id,
    client_name: clientNameValue,
    contact_number: contactValue,
    email_address: emailValue,
    organization: document.getElementById("organization").value.trim() || "",
    address: addressValue,
    event_date: eventDateValue,
    event_time: eventTime.value,
    duration: totalDuration,
    attendees: document.getElementById("attendees").value || null,
    event_title: eventTitleValue,
    special_requirements:
      document.getElementById("specialRequirements").value.trim() || "",
    selected_addons: selectedAddonIds,
    selected_equipment: selectedEquipment,
    additional_hours: additionalHours,
    maintenance_fee: MAINTENANCE_FEE,
    total_cost: calculateTotalCost(),
  };

  console.log("Booking data to submit:", formData);

  try {
    const response = await fetch("/api/bookings", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(formData),
    });

    console.log("Response status:", response.status);

    // Parse response JSON regardless of status
    const result = await response.json();
    console.log("Server response:", result);

    if (!response.ok) {
      // Show the actual error message from the server
      console.log("Response not OK:", response.status, response.statusText);
      console.log("Error message:", result.message);
      showToast(
        result.message || `HTTP error! status: ${response.status}`,
        "error"
      );
      return;
    }

    if (result.success) {
      showToast(
        `Booking created successfully! Booking ID: ${result.booking_id}`,
        "success"
      );

      // Show detailed success message
      setTimeout(() => {
        showToast(
          `${
            facilityData[currentFacility].name
          } - ${totalDuration} hours - ₱${formData.total_cost.toLocaleString()}`,
          "info"
        );
      }, 500);

      closeModal();

      // Optionally reload page after 2 seconds
      setTimeout(() => {
        location.reload();
      }, 2000);
    } else {
      console.log("Booking failed:", result.message);
      showToast(result.message || "Failed to create booking", "error");
    }
  } catch (error) {
    console.error("Error submitting booking:", error);
    showToast(
      "An error occurred while creating the booking. Please try again.",
      "error"
    );
  }
}

function calculateTotalCost() {
  // Base price from selected plan
  let basePrice = 0;
  if (selectedPlan && selectedPlan.price) {
    basePrice = parseFloat(selectedPlan.price);
  }
  console.log("Base price:", basePrice);

  // Calculate additional hours cost
  const additionalHours =
    parseInt(document.getElementById("additionalHours").value) || 0;
  const additionalHoursPrice = additionalHours * HOURLY_RATE;
  console.log("Additional hours price:", additionalHoursPrice);

  // Calculate addons cost
  const addonsPrice = selectedAddons.reduce((sum, addonId) => {
    const addon = addonsData.find((a) => a.id === addonId);
    const addonPrice = addon ? parseFloat(addon.price) : 0;
    console.log("Addon:", addon?.name, "Price:", addonPrice);
    return sum + addonPrice;
  }, 0);
  console.log("Total addons price:", addonsPrice);

  // Calculate equipment cost
  const equipmentPrice = Object.keys(selectedEquipment).reduce(
    (sum, equipmentId) => {
      const equipment = equipmentData.find((e) => e.id === equipmentId);
      const quantity = selectedEquipment[equipmentId];

      if (equipment && quantity > 0 && equipment.rate > 0) {
        const itemCost = parseFloat(equipment.rate) * quantity;
        console.log(
          "Equipment:",
          equipment.name,
          "Qty:",
          quantity,
          "Cost:",
          itemCost
        );
        return sum + itemCost;
      }
      return sum;
    },
    0
  );
  console.log("Total equipment price:", equipmentPrice);

  // Calculate total (always include maintenance fee)
  const total =
    basePrice +
    addonsPrice +
    equipmentPrice +
    additionalHoursPrice +
    MAINTENANCE_FEE;
  console.log("TOTAL COST:", total, "Breakdown:", {
    basePrice,
    addonsPrice,
    equipmentPrice,
    additionalHoursPrice,
    MAINTENANCE_FEE,
  });

  return total;
}

function showSuccessMessage(message) {
  showToast(message, "success");
}

function showErrorMessage(message) {
  showToast(message, "error");
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modal = document.getElementById("bookingModal");
  if (event.target === modal) {
    closeModal();
  }
};

function populateEquipment() {
  const equipmentGrid = document.getElementById("equipmentGrid");
  equipmentGrid.innerHTML = "";

  if (!equipmentData || equipmentData.length === 0) {
    equipmentGrid.innerHTML = '<p class="no-data">No equipment available.</p>';
    return;
  }

  equipmentData.forEach((equipment) => {
    const equipmentCard = document.createElement("div");
    equipmentCard.className = "equipment-card";

    // Check if equipment is available and has stock
    const isAvailable = equipment.available > 0;
    const stockInfo = isAvailable
      ? `Available: ${equipment.available}`
      : "Out of Stock";

    equipmentCard.innerHTML = `
            <div class="equipment-info">
                <div class="equipment-name">${equipment.name}</div>
                <div class="equipment-rate">
                    ${
                      equipment.rate === 0
                        ? "Included"
                        : `₱${equipment.rate} ${equipment.unit}`
                    }
                </div>
                <div class="equipment-stock ${
                  isAvailable ? "in-stock" : "out-of-stock"
                }">
                    ${stockInfo}
                </div>
            </div>
            <div class="equipment-quantity">
                ${
                  equipment.rate === 0
                    ? '<span class="included-badge">Included</span>'
                    : isAvailable
                    ? `<input type="number" class="quantity-input" id="qty-${equipment.id}" 
                     min="0" max="${equipment.available}" value="0" onchange="updateEquipment('${equipment.id}')">`
                    : '<span class="out-of-stock-badge">Out of Stock</span>'
                }
            </div>
        `;

    equipmentGrid.appendChild(equipmentCard);
  });
}

function updateEquipment(equipmentId) {
  const quantityInput = document.getElementById(`qty-${equipmentId}`);
  const quantity = parseInt(quantityInput.value) || 0;
  const equipment = equipmentData.find((e) => e.id === equipmentId);

  if (!equipment) return;

  // Validate quantity doesn't exceed available stock
  if (quantity > equipment.available) {
    showToast(
      `Only ${equipment.available} units available for ${equipment.name}`,
      "warning"
    );
    quantityInput.value = equipment.available;
    selectedEquipment[equipmentId] = equipment.available;
  } else if (quantity > 0) {
    selectedEquipment[equipmentId] = quantity;
    showToast(`Added ${quantity}x ${equipment.name}`, "success");
  } else {
    delete selectedEquipment[equipmentId];
  }

  updateCostSummary();
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

// ========================================
// TOAST NOTIFICATION SYSTEM
// ========================================
function showToast(message, type = "info") {
  const toastContainer = document.getElementById("toastContainer");

  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;

  const icon =
    {
      success: "✅",
      error: "❌",
      warning: "⚠️",
      info: "ℹ️",
    }[type] || "ℹ️";

  toast.innerHTML = `
    <span class="toast-icon">${icon}</span>
    <span class="toast-message">${message}</span>
    <button class="toast-close" onclick="this.parentElement.remove()">×</button>
  `;

  toastContainer.appendChild(toast);

  // Auto remove after 5 seconds
  setTimeout(() => {
    toast.classList.add("toast-fade-out");
    setTimeout(() => toast.remove(), 300);
  }, 5000);
}

// ========================================
// INLINE VALIDATION HELPERS
// ========================================
function showInlineError(fieldId, message) {
  const field = document.getElementById(fieldId);
  if (!field) return;

  // Remove existing error
  hideInlineError(fieldId);

  // Add error styling
  field.classList.add("field-error");

  // Create error message
  const errorDiv = document.createElement("div");
  errorDiv.className = "inline-error";
  errorDiv.innerHTML = `<span class="error-icon">⚠️</span> ${message}`;

  field.parentNode.appendChild(errorDiv);
}

function hideInlineError(fieldId) {
  const field = document.getElementById(fieldId);
  if (!field) return;

  field.classList.remove("field-error");

  const error = field.parentNode.querySelector(".inline-error");
  if (error) error.remove();
}

function addBookingValidationListeners() {
  // Client Name validation
  const clientName = document.getElementById("clientName");
  if (clientName) {
    clientName.addEventListener("blur", function () {
      const value = this.value.trim();
      if (value && value.length < 3) {
        showInlineError("clientName", "Name must be at least 3 characters");
      } else if (value) {
        hideInlineError("clientName");
      }
    });

    clientName.addEventListener("input", function () {
      if (this.classList.contains("field-error")) {
        hideInlineError("clientName");
      }
    });
  }

  // Email validation
  const emailAddress = document.getElementById("emailAddress");
  if (emailAddress) {
    emailAddress.addEventListener("blur", function () {
      const value = this.value.trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (value && !emailRegex.test(value)) {
        showInlineError(
          "emailAddress",
          "Please enter a valid email address (example@domain.com)"
        );
      } else if (value) {
        hideInlineError("emailAddress");
      }
    });

    emailAddress.addEventListener("input", function () {
      if (this.classList.contains("field-error")) {
        hideInlineError("emailAddress");
      }
    });
  }

  // Contact Number validation
  const contactNumber = document.getElementById("contactNumber");
  if (contactNumber) {
    contactNumber.addEventListener("blur", function () {
      const value = this.value.trim();
      const digitsOnly = value.replace(/\D/g, "");
      if (value && digitsOnly.length < 7) {
        showInlineError(
          "contactNumber",
          "Contact number must have at least 7 digits"
        );
      } else if (value) {
        hideInlineError("contactNumber");
      }
    });

    contactNumber.addEventListener("input", function () {
      if (this.classList.contains("field-error")) {
        hideInlineError("contactNumber");
      }
    });
  }

  // Address validation
  const address = document.getElementById("address");
  if (address) {
    address.addEventListener("blur", function () {
      const value = this.value.trim();
      if (value && value.length < 10) {
        showInlineError(
          "address",
          "Address must be at least 10 characters. Include street, city, and province."
        );
      } else if (value) {
        hideInlineError("address");
      }
    });

    address.addEventListener("input", function () {
      if (this.classList.contains("field-error")) {
        hideInlineError("address");
      }
    });
  }

  // Event Date validation
  const eventDate = document.getElementById("eventDate");
  if (eventDate) {
    eventDate.addEventListener("change", function () {
      const selectedDate = new Date(this.value);
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      if (selectedDate < today) {
        showInlineError("eventDate", "Event date cannot be in the past");
      } else {
        hideInlineError("eventDate");
      }
    });
  }

  // Event Title validation
  const eventTitle = document.getElementById("eventTitle");
  if (eventTitle) {
    eventTitle.addEventListener("blur", function () {
      const value = this.value.trim();
      if (value && value.length < 5) {
        showInlineError(
          "eventTitle",
          "Event title must be at least 5 characters"
        );
      } else if (value) {
        hideInlineError("eventTitle");
      }
    });

    eventTitle.addEventListener("input", function () {
      if (this.classList.contains("field-error")) {
        hideInlineError("eventTitle");
      }
    });
  }
}

function initializeAddressCounter() {
  const addressField = document.getElementById("address");
  if (!addressField) return;

  // Create character counter element
  const counter = document.createElement("small");
  counter.style.color = "var(--gray)";
  counter.style.fontSize = "11px";
  counter.style.marginTop = "3px";
  counter.style.display = "block";
  counter.id = "address-counter";

  addressField.parentNode.appendChild(counter);

  // Update counter on input
  addressField.addEventListener("input", function () {
    const length = this.value.trim().length;
    const minLength = 10;

    if (length === 0) {
      counter.textContent = "Please provide your complete address";
      counter.style.color = "var(--gray)";
    } else if (length < minLength) {
      counter.textContent = `${length}/10 characters (${
        minLength - length
      } more needed)`;
      counter.style.color = "#dc3545"; // red
    } else {
      counter.textContent = `${length} characters ✓`;
      counter.style.color = "#28a745"; // green
    }
  });

  // Trigger initial update
  addressField.dispatchEvent(new Event("input"));
}
