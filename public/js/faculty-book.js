// ==================== GLOBAL VARIABLES ====================
let selectedBookingType = null; // 'free' or 'paid'
let currentFacilityKey = null;
let currentFacilityId = null;
let facilityData = {};
let addonsData = [];
let equipmentData = [];
let selectedPlan = null;
let selectedAddons = [];
let selectedEquipment = {};
let selectedFreeEquipment = {};
let MAINTENANCE_FEE = 2000;
let HOURLY_RATE = 500;

// ==================== BOOKING TYPE SELECTION ====================
function selectBookingType(type) {
  selectedBookingType = type;

  // Update UI
  document.getElementById("freeBookingCard").classList.remove("selected");
  document.getElementById("paidBookingCard").classList.remove("selected");

  if (type === "free") {
    document.getElementById("freeBookingCard").classList.add("selected");
    document.getElementById("selectedTypeText").textContent =
      "Academic/Free Booking - No payment required";
  } else {
    document.getElementById("paidBookingCard").classList.add("selected");
    document.getElementById("selectedTypeText").textContent =
      "Commercial/Paid Booking - Standard rates apply";
  }

  // Show selected type and facilities
  document.getElementById("selectedTypeDisplay").style.display = "block";
  document.getElementById("facilitiesGrid").style.display = "grid";

  // Update price display on facility cards
  updateFacilityPriceDisplay();
}

function clearBookingType() {
  selectedBookingType = null;
  document.getElementById("freeBookingCard").classList.remove("selected");
  document.getElementById("paidBookingCard").classList.remove("selected");
  document.getElementById("selectedTypeDisplay").style.display = "none";
  document.getElementById("facilitiesGrid").style.display = "none";
}

function updateFacilityPriceDisplay() {
  const priceRanges = document.querySelectorAll(".price-range");
  priceRanges.forEach((el) => {
    if (selectedBookingType === "free") {
      el.textContent = "FREE (Academic)";
      el.style.color = "#16a34a";
    } else {
      el.textContent = "View Packages";
      el.style.color = "#f59e0b";
    }
  });
}

// ==================== FACILITY STATUS FUNCTIONS ====================
async function loadFacilityStatus(facilityKey, targetElementId) {
  try {
    console.log(
      `[loadFacilityStatus] Loading status for facility: ${facilityKey}`
    );
    const response = await fetch(`/api/facilities/list`);
    const data = await response.json();

    console.log(
      `[loadFacilityStatus] API Response - Success: ${
        data.success
      }, Facilities count: ${data.facilities ? data.facilities.length : 0}`
    );

    if (data.success && data.facilities) {
      data.facilities.forEach((f) => {
        console.log(
          `[loadFacilityStatus] Available - ${f.name} (${f.facility_key}, is_maintenance: ${f.is_maintenance})`
        );
      });

      const facility = data.facilities.find(
        (f) => f.facility_key === facilityKey
      );

      console.log(
        `[loadFacilityStatus] Found facility? ${facility ? "YES" : "NO"}`
      );
      if (facility) {
        console.log(
          `[loadFacilityStatus] Facility details - Name: ${facility.name}, is_maintenance: ${facility.is_maintenance}`
        );
      }

      if (facility && document.getElementById(targetElementId)) {
        const statusElement = document.getElementById(targetElementId);
        const statusText = statusElement.querySelector(".status-text");
        const isMaintenance =
          facility.is_maintenance == 1 || facility.is_maintenance === true;

        // Update status text
        statusText.textContent = isMaintenance ? "Inactive" : "Available";

        // Remove old classes and add new one
        statusElement.classList.remove("available", "maintenance");
        statusElement.classList.add(
          isMaintenance ? "maintenance" : "available"
        );

        // Disable/enable the booking form based on facility status
        handleFacilityStatusChange(isMaintenance, targetElementId);

        console.log(
          `✓ [loadFacilityStatus] Facility status updated: ${
            isMaintenance ? "Inactive" : "Available"
          }`
        );
      } else {
        console.warn(
          `[loadFacilityStatus] Could not find facility in response or element ${targetElementId} not found`
        );
      }
    } else {
      console.error(
        `[loadFacilityStatus] API returned success=false or no facilities`
      );
    }
  } catch (error) {
    console.error(`[loadFacilityStatus] Error: ${error.message}`);
  }
}

// Handle facility status changes - disable form if inactive
function handleFacilityStatusChange(isInactive, targetElementId) {
  const modal = targetElementId.includes("free")
    ? document.getElementById("freeBookingModal")
    : document.getElementById("paidBookingModal");

  if (!modal) return;

  const form = modal.querySelector("form");
  const modalBody = modal.querySelector(".modal-body");
  const modalFooter = modal.querySelector(".modal-footer");
  const allInputs = modalBody
    ? modalBody.querySelectorAll("input, textarea, select")
    : [];
  const allButtons = modalFooter ? modalFooter.querySelectorAll("button") : [];
  const unavailableNotice = modal.querySelector(".facility-unavailable-notice");

  if (isInactive) {
    // Show unavailable notice
    if (!unavailableNotice && modalBody) {
      const notice = document.createElement("div");
      notice.className = "facility-unavailable-notice";
      notice.innerHTML = `
        <div style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border: 2px solid #dc2626; border-radius: 10px; padding: 20px; margin-bottom: 20px; text-align: center;">
          <div style="font-size: 24px; margin-bottom: 10px;">🚫</div>
          <h4 style="color: #991b1b; margin-bottom: 8px; font-weight: 700;">Facility Currently Unavailable</h4>
          <p style="color: #7f1d1d; margin-bottom: 0; font-size: 14px;">This facility is currently inactive and cannot be booked. Please try again later or select a different facility.</p>
        </div>
      `;
      const firstSection = modalBody.querySelector(".plan-section");
      if (firstSection) {
        modalBody.insertBefore(notice, firstSection);
      } else {
        modalBody.insertBefore(notice, modalBody.firstChild);
      }
    }

    // Disable all form elements except hidden inputs
    allInputs.forEach((input) => {
      if (input.type !== "hidden") {
        input.disabled = true;
        input.style.opacity = "0.6";
        input.style.cursor = "not-allowed";
      }
    });

    // Disable all buttons except close button
    allButtons.forEach((btn) => {
      if (
        !btn.classList.contains("close") &&
        !btn.textContent.includes("Cancel")
      ) {
        btn.disabled = true;
        btn.style.opacity = "0.5";
        btn.style.cursor = "not-allowed";
        btn.setAttribute("data-disabled-by-status", "true");
      }
    });
  } else {
    // Remove unavailable notice if exists
    const notice = modal.querySelector(".facility-unavailable-notice");
    if (notice) {
      notice.remove();
    }

    // Re-enable form elements
    allInputs.forEach((input) => {
      if (input.type !== "hidden") {
        input.disabled = false;
        input.style.opacity = "1";
        input.style.cursor = "auto";
      }
    });

    // Re-enable buttons that were disabled by status
    allButtons.forEach((btn) => {
      if (btn.getAttribute("data-disabled-by-status") === "true") {
        btn.disabled = false;
        btn.style.opacity = "1";
        btn.style.cursor = "pointer";
        btn.removeAttribute("data-disabled-by-status");
      }
    });
  }
}

// ==================== OPEN BOOKING MODAL ====================
async function openFacultyBookingModal(facilityKey, facilityId) {
  if (!selectedBookingType) {
    alert("Please select a booking type first (Free or Paid)");
    return;
  }

  currentFacilityKey = facilityKey;
  currentFacilityId = facilityId;

  // Check facility status first
  const facilityStatus = await checkFacilityStatus(facilityKey);

  if (selectedBookingType === "free") {
    openFreeBookingModal(facilityKey, facilityId, facilityStatus);
  } else {
    openPaidBookingModal(facilityKey, facilityId, facilityStatus);
  }
}

// Check facility status and return status object
async function checkFacilityStatus(facilityKey) {
  try {
    const response = await fetch(`/api/facilities/list`);
    const data = await response.json();

    if (data.success && data.facilities) {
      const facility = data.facilities.find(
        (f) => f.facility_key === facilityKey
      );
      if (facility) {
        return {
          is_active: facility.is_active,
          is_maintenance: facility.is_maintenance,
          name: facility.name,
        };
      }
    }
    return { is_active: 1, is_maintenance: 0, name: "Unknown" };
  } catch (error) {
    console.error("Error checking facility status:", error);
    return { is_active: 1, is_maintenance: 0, name: "Unknown" };
  }
}

// ==================== FREE BOOKING FUNCTIONS ====================
function openFreeBookingModal(facilityKey, facilityId, facilityStatus = {}) {
  document.getElementById("freeFacilityKey").value = facilityKey;
  document.getElementById("freeFacilityId").value = facilityId;

  // Show modal
  document.getElementById("freeBookingModal").style.display = "block";

  // Load and display facility status
  loadFacilityStatus(facilityKey, "freeAvailabilityStatus");

  // Check facility availability
  const isInactive = facilityStatus.is_active == 0;
  const isMaintenance = facilityStatus.is_maintenance == 1;

  const modalBody = document
    .getElementById("freeBookingModal")
    .querySelector(".modal-body");
  const statusMessageDiv = modalBody.querySelector(".facility-status-message");

  if (isInactive) {
    // Show inactive message
    if (statusMessageDiv) statusMessageDiv.remove();
    const inactiveMsg = document.createElement("div");
    inactiveMsg.className = "alert alert-danger facility-status-message";
    inactiveMsg.innerHTML =
      '<i class="fas fa-ban"></i> <strong>Sorry!</strong> This facility is not available right now.';
    modalBody.insertBefore(inactiveMsg, modalBody.firstChild);

    // Disable the form
    disableFreeBookingForm();
  } else if (isMaintenance) {
    // Show maintenance message
    if (statusMessageDiv) statusMessageDiv.remove();
    const maintenanceMsg = document.createElement("div");
    maintenanceMsg.className = "alert alert-warning facility-status-message";
    maintenanceMsg.innerHTML =
      '<i class="fas fa-wrench"></i> <strong>Under Maintenance</strong> This facility is currently under maintenance. Please check back later.';
    modalBody.insertBefore(maintenanceMsg, modalBody.firstChild);

    // Disable the form
    disableFreeBookingForm();
  } else {
    // Remove any existing status messages
    if (statusMessageDiv) statusMessageDiv.remove();

    // Load equipment for free booking
    loadFreeEquipment();

    // Set minimum date to today
    const today = new Date().toISOString().split("T")[0];
    document.getElementById("freeEventDate").min = today;

    // Enable form validation
    validateFreeForm();

    // Enable the form
    enableFreeBookingForm();
  }
}

function disableFreeBookingForm() {
  const form = document.getElementById("freeBookingForm");
  const inputs = form.querySelectorAll("input, textarea, select, button");
  inputs.forEach((input) => {
    if (input.id !== "submitFreeBtn") {
      input.disabled = true;
    }
  });

  const submitBtn = document.getElementById("submitFreeBtn");
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Facility Unavailable";
  }
}

function enableFreeBookingForm() {
  const form = document.getElementById("freeBookingForm");
  const inputs = form.querySelectorAll("input, textarea, select");
  inputs.forEach((input) => {
    input.disabled = false;
  });

  const submitBtn = document.getElementById("submitFreeBtn");
  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.textContent = "Submit Free Booking";
  }
}

function closeFreeModal() {
  document.getElementById("freeBookingModal").style.display = "none";
  document.getElementById("freeBookingForm").reset();
}

async function handleFreeEventDateChange() {
  const eventDate = document.getElementById("freeEventDate").value;

  if (eventDate) {
    // Reset selected equipment when date changes
    selectedFreeEquipment = {};
    const equipmentInputs = document.querySelectorAll('[id^="free-qty-"]');
    equipmentInputs.forEach((input) => (input.value = "0"));

    // Load equipment for the new date
    await loadFreeEquipmentForDate(eventDate);

    // Show equipment grid
    document.getElementById("freeEquipmentGrid").style.display = "grid";
    document.getElementById("freeEquipmentDatePlaceholder").style.display =
      "none";
  } else {
    // Hide equipment grid and show placeholder
    document.getElementById("freeEquipmentGrid").style.display = "none";
    document.getElementById("freeEquipmentDatePlaceholder").style.display =
      "block";
  }
}

async function loadFreeEquipmentForDate(eventDate) {
  try {
    const facilityId = document.getElementById("freeFacilityId").value;

    // Fetch all equipment available on this specific date (including rentable and non-rentable)
    const response = await fetch(
      `/api/equipment?event_date=${eventDate}&facility_id=${facilityId}`
    );

    const result = await response.json();

    if (result.success && result.equipment) {
      displayFreeEquipment(result.equipment, eventDate);
    } else {
      displayFreeEquipment([], eventDate);
    }
  } catch (error) {
    console.error("Error loading free equipment:", error);
    displayFreeEquipment([], eventDate);
  }
}
function displayFreeEquipment(equipmentList, eventDate) {
  const equipmentGrid = document.getElementById("freeEquipmentGrid");
  if (!equipmentGrid) return;

  equipmentGrid.innerHTML = "";

  // Show ALL equipment regardless of availability (including zero stock)
  const allEquipment = equipmentList;

  // Group by category
  const grouped = {};
  allEquipment.forEach((eq) => {
    const category = eq.category || "other";
    if (!grouped[category]) {
      grouped[category] = [];
    }
    grouped[category].push(eq);
  });

  // Display date info
  if (eventDate) {
    const dateHeader = document.createElement("div");
    dateHeader.style.gridColumn = "1 / -1";
    dateHeader.style.marginBottom = "15px";
    dateHeader.style.padding = "10px";
    dateHeader.style.background = "#e3f2fd";
    dateHeader.style.borderRadius = "8px";
    dateHeader.style.textAlign = "center";
    dateHeader.innerHTML = `<strong>📅 Equipment available on ${new Date(
      eventDate
    ).toLocaleDateString()}</strong>`;
    equipmentGrid.appendChild(dateHeader);
  }

  if (allEquipment.length === 0) {
    equipmentGrid.innerHTML =
      '<p style="color: #6c757d; padding: 20px; text-align: center;">No equipment found.</p>';
    return;
  }

  // Display equipment grouped by category
  Object.keys(grouped).forEach((category) => {
    const header = document.createElement("h4");
    header.className = "equipment-category-header";
    header.style.gridColumn = "1 / -1";
    header.style.marginTop = "20px";
    header.style.marginBottom = "10px";
    header.style.color = "var(--primary)";
    header.textContent = category.replace("_", " ").toUpperCase();
    equipmentGrid.appendChild(header);

    grouped[category].forEach((equipment) => {
      const equipmentCard = document.createElement("div");
      equipmentCard.className = "equipment-card";

      const availableQty = parseInt(equipment.available_on_date || 0);
      const isOutOfStock = availableQty === 0;

      equipmentCard.innerHTML = `
        <div class="equipment-info">
          <h4 class="equipment-name">${equipment.name}</h4>
          <p class="equipment-description">${
            isOutOfStock
              ? "Out of Stock"
              : availableQty + " available on this date"
          }</p>
          ${
            equipment.rate > 0
              ? `<span class="equipment-price">₱${equipment.rate}/${equipment.unit}</span>`
              : '<span class="included-badge">Included (FREE)</span>'
          }
        </div>
        <div class="equipment-actions-card">
          <input type="number" class="form-control qty-input"
                 id="free-qty-${equipment.id}"
                 min="0" max="${availableQty}" value="0"
                 ${isOutOfStock ? "disabled" : ""}
                 onchange="updateFreeEquipment('${equipment.id}')">
          <label class="equipment-label">${
            isOutOfStock ? "Not Available" : "Quantity"
          }</label>
        </div>
      `;

      equipmentGrid.appendChild(equipmentCard);
    });
  });
}

async function loadFreeEquipment() {
  // This function is called when modal opens to initialize
  // It will now depend on date selection
  const placeholder = document.getElementById("freeEquipmentDatePlaceholder");
  const equipmentGrid = document.getElementById("freeEquipmentGrid");

  if (equipmentGrid) equipmentGrid.style.display = "none";
  if (placeholder) placeholder.style.display = "block";
}

function updateFreeEquipment(equipmentId) {
  const input = document.getElementById(`free-qty-${equipmentId}`);
  const quantity = parseInt(input.value) || 0;

  if (quantity > 0) {
    selectedFreeEquipment[equipmentId] = quantity;
  } else {
    delete selectedFreeEquipment[equipmentId];
  }

  validateFreeForm();
}

function handleStudentFileSelect(input, docType) {
  const file = input.files[0];
  const uploadItem = document.getElementById(`upload-${docType}`);
  const statusSpan = uploadItem.querySelector(".upload-status");
  const filenameDisplay = document.getElementById(`filename-${docType}`);

  if (file) {
    // Validate file size (10MB)
    if (file.size > 10 * 1024 * 1024) {
      alert("File size must be less than 10MB");
      input.value = "";
      return;
    }

    statusSpan.textContent = "Uploaded";
    statusSpan.style.color = "#16a34a";
    filenameDisplay.textContent = `File: ${file.name}`;
    uploadItem.style.background = "#f0fdf4";
  } else {
    statusSpan.textContent = "Not uploaded";
    statusSpan.style.color = "#dc2626";
    filenameDisplay.textContent = "";
    uploadItem.style.background = "";
  }

  validateFreeForm();
}

function validateFreeForm() {
  const form = document.getElementById("freeBookingForm");
  const submitBtn = document.getElementById("submitFreeBtn");

  // Form fields are required, files are optional
  const formValid = form.checkValidity();

  submitBtn.disabled = !formValid;
}

async function submitFreeBooking() {
  const form = document.getElementById("freeBookingForm");
  const submitBtn = document.getElementById("submitFreeBtn");
  const statusIndicator = document.getElementById("freeAvailabilityStatus");

  // Check if facility is inactive
  if (statusIndicator && statusIndicator.classList.contains("maintenance")) {
    showToast(
      "Cannot book an inactive facility. Please select another facility.",
      "error"
    );
    return;
  }

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  submitBtn.disabled = true;
  submitBtn.textContent = "Processing...";

  try {
    showToast("Validating booking details...", "info");

    // STEP 0: Check for date + time + grace period conflicts
    const facilityId = parseInt(
      document.getElementById("freeFacilityId").value
    );
    const eventDate = document.getElementById("freeEventDate").value;
    const eventTime = document.getElementById("freeEventTime").value;
    const duration = parseInt(document.getElementById("freeDuration").value);

    // Calculate end time with 2-hour grace period
    const startTime = new Date(`2000-01-01 ${eventTime}`);
    const endTime = new Date(startTime.getTime() + duration * 60 * 60 * 1000);
    const endTimeWithGrace = new Date(endTime.getTime() + 2 * 60 * 60 * 1000);

    const endTimeStr =
      String(endTime.getHours()).padStart(2, "0") +
      ":" +
      String(endTime.getMinutes()).padStart(2, "0");
    const endTimeWithGraceStr =
      String(endTimeWithGrace.getHours()).padStart(2, "0") +
      ":" +
      String(endTimeWithGrace.getMinutes()).padStart(2, "0");

    const conflictCheck = await fetch("/api/bookings/checkDateConflict", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        facility_id: facilityId,
        event_date: eventDate,
        event_time: eventTime,
        duration: duration,
      }),
    });

    const conflictResult = await conflictCheck.json();

    if (
      conflictResult.hasConflict ||
      conflictResult.hasPendingOrApprovedBooking
    ) {
      showToast(
        "⚠️ Conflict Detected: Facility has a conflicting booking on this date/time. Your requested time: " +
          eventTime +
          " - " +
          endTimeStr +
          ". With 2-hour grace period, available from: " +
          endTimeWithGraceStr,
        "error"
      );
      submitBtn.disabled = false;
      submitBtn.textContent = "Submit Free Booking";
      return;
    }

    // Get equipment selections from global variable
    const equipmentSelections = { ...selectedFreeEquipment };

    // STEP 1: Create the booking with JSON data
    const bookingData = {
      facility_id: parseInt(document.getElementById("freeFacilityId").value),
      plan_id: 1, // Free plan (you may need to adjust this)
      client_name: document.getElementById("freeClientName").value,
      email_address: document.getElementById("freeClientEmail").value,
      organization: document.getElementById("freeOrganization").value,
      contact_number: document.getElementById("freeContactNumber").value,
      address: document.getElementById("freeAddress").value || "",
      event_date: document.getElementById("freeEventDate").value,
      event_time: document.getElementById("freeEventTime").value,
      duration: parseInt(document.getElementById("freeDuration").value),
      attendees:
        parseInt(document.getElementById("freeAttendees").value) || null,
      event_title: document.getElementById("freeEventTitle").value,
      special_requirements:
        document.getElementById("freeSpecialRequirements").value || "",
      selected_equipment: equipmentSelections,
      booking_type: "employee", // Employee booking type
    };

    console.log("Sending booking data:", bookingData);

    const bookingResponse = await fetch("/api/student/bookings/create", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(bookingData),
    });

    const bookingResult = await bookingResponse.json();
    console.log("Booking result:", bookingResult);

    if (!bookingResult.success) {
      throw new Error(bookingResult.message || "Failed to create booking");
    }

    const bookingId = bookingResult.booking_id;
    showToast(
      `Booking created! ID: BK${String(bookingId).padStart(3, "0")}`,
      "success"
    );

    // STEP 2: Upload files if any are selected
    const permissionFile = document.getElementById("file-permission").files[0];
    const requestFile = document.getElementById("file-request").files[0];
    const approvalFile = document.getElementById("file-approval").files[0];
    const hasFiles = permissionFile || requestFile || approvalFile;

    if (hasFiles) {
      try {
        const formData = new FormData();
        if (permissionFile) formData.append("files[]", permissionFile);
        if (requestFile) formData.append("files[]", requestFile);
        if (approvalFile) formData.append("files[]", approvalFile);

        showToast("Uploading documents...", "info");

        const uploadResponse = await fetch(
          `/api/student-bookings/${bookingId}/upload-documents`,
          {
            method: "POST",
            credentials: "include",
            body: formData,
          }
        );

        const uploadResult = await uploadResponse.json();
        if (!uploadResult.success) {
          showToast(
            "Booking created but some files failed to upload",
            "warning"
          );
        } else {
          showToast(
            `${uploadResult.files.length} file(s) uploaded successfully`,
            "success"
          );
        }
      } catch (uploadError) {
        console.error("File upload error:", uploadError);
        showToast("Booking created but file upload failed", "warning");
      }
    }

    // SUCCESS!
    closeFreeModal();
    showToast(
      `Booking submitted successfully! Reference: BK${String(
        bookingId
      ).padStart(3, "0")}`,
      "success"
    );

    setTimeout(() => {
      window.location.href = "/employee/bookings";
    }, 2000);
  } catch (error) {
    console.error("Error submitting free booking:", error);
    showToast(
      error.message ||
        "An error occurred while submitting your booking. Please try again.",
      "error"
    );

    submitBtn.disabled = false;
    submitBtn.textContent = "Submit Free Booking";
  }
}

// ==================== PAID BOOKING FUNCTIONS (LIKE EXTERNAL.PHP) ====================

async function openPaidBookingModal(
  facilityKey,
  facilityId,
  facilityStatus = {}
) {
  currentFacilityKey = facilityKey;
  currentFacilityId = facilityId;

  // Load facility status
  loadFacilityStatus(facilityKey, "paidAvailabilityStatus");

  // Load facility data
  await loadPaidFacilityData(facilityKey);

  document.getElementById("paidFacilityKey").value = facilityKey;
  document.getElementById("paidFacilityId").value = facilityId;

  const facility = facilityData[facilityKey];

  if (!facility) {
    showToast("Facility data not found", "error");
    return;
  }

  // Check facility availability
  const isInactive = facilityStatus.is_active == 0;
  const isMaintenance = facilityStatus.is_maintenance == 1;

  const modalBody = document
    .getElementById("paidBookingModal")
    .querySelector(".modal-body");
  const statusMessageDiv = modalBody.querySelector(".facility-status-message");

  // Show modal first
  document.getElementById("paidBookingModal").style.display = "block";

  document.getElementById(
    "paidModalTitle"
  ).textContent = `Book ${facility.name}`;

  if (isInactive) {
    // Show inactive message
    if (statusMessageDiv) statusMessageDiv.remove();
    const inactiveMsg = document.createElement("div");
    inactiveMsg.className = "alert alert-danger facility-status-message";
    inactiveMsg.innerHTML =
      '<i class="fas fa-ban"></i> <strong>Sorry!</strong> This facility is not available right now.';
    modalBody.insertBefore(inactiveMsg, modalBody.firstChild);

    // Disable the form
    disablePaidBookingForm();
  } else if (isMaintenance) {
    // Show maintenance message
    if (statusMessageDiv) statusMessageDiv.remove();
    const maintenanceMsg = document.createElement("div");
    maintenanceMsg.className = "alert alert-warning facility-status-message";
    maintenanceMsg.innerHTML =
      '<i class="fas fa-wrench"></i> <strong>Under Maintenance</strong> This facility is currently under maintenance. Please check back later.';
    modalBody.insertBefore(maintenanceMsg, modalBody.firstChild);

    // Disable the form
    disablePaidBookingForm();
  } else {
    // Remove any existing status messages
    if (statusMessageDiv) statusMessageDiv.remove();

    // Update the hourly rate for this specific facility
    if (facility.additional_hours_rate) {
      HOURLY_RATE = parseFloat(facility.additional_hours_rate);
    }

    // Update the hourly rate label
    const hourlyRateLabel = document.getElementById("paidHourlyRateLabel");
    if (hourlyRateLabel) {
      hourlyRateLabel.textContent = `₱${HOURLY_RATE.toLocaleString()}`;
    }

    // Populate plans
    populatePaidPlans(facility.plans);

    // Populate add-ons
    populatePaidAddons();

    // Show date prompt for equipment
    showPaidEquipmentDatePrompt();

    // Reset selections
    selectedPlan = null;
    selectedAddons = [];
    selectedEquipment = {};
    document.getElementById("paidAdditionalHours").value = 0;
    document.getElementById("paidEventDate").value = "";
    updatePaidCostSummary();

    // Set minimum date
    const today = new Date().toISOString().split("T")[0];
    document.getElementById("paidEventDate").min = today;

    // Enable the form
    enablePaidBookingForm();
  }
}

function disablePaidBookingForm() {
  const form = document.getElementById("paidBookingForm");
  if (!form) return;

  const inputs = form.querySelectorAll("input, textarea, select, button");
  inputs.forEach((input) => {
    if (input.id !== "submitPaidBtn") {
      input.disabled = true;
    }
  });

  const submitBtn = document.getElementById("submitPaidBtn");
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Facility Unavailable";
  }
}

function enablePaidBookingForm() {
  const form = document.getElementById("paidBookingForm");
  if (!form) return;

  const inputs = form.querySelectorAll("input, textarea, select");
  inputs.forEach((input) => {
    input.disabled = false;
  });

  const submitBtn = document.getElementById("submitPaidBtn");
  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.textContent = "Submit Booking";
  }
}

function closePaidModal() {
  document.getElementById("paidBookingModal").style.display = "none";
  document.getElementById("paidBookingForm").reset();
}

async function loadPaidFacilityData(facilityKey) {
  try {
    const response = await fetch(`/api/facilities/${facilityKey}/data`);
    const data = await response.json();

    if (data.success && data.facility) {
      facilityData[facilityKey] = data.facility;

      // Also load addons and equipment
      await loadPaidAddonsData();
    }
  } catch (error) {
    console.error("Error loading facility data:", error);
  }
}

async function loadPaidAddonsData() {
  try {
    const response = await fetch("/api/addons");
    const result = await response.json();

    // Debug: log what we received
    console.log("Raw API response:", result);
    console.log("Response type:", typeof result);
    console.log("Is array?:", Array.isArray(result));

    // Handle wrapped response format {success, addons, count}
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
  } catch (error) {
    console.error("Error loading addons:", error);
  }
}

function populatePaidPlans(plans) {
  const plansGrid = document.getElementById("paidPlansGrid");
  if (!plansGrid || !plans) return;

  plansGrid.innerHTML = "";

  plans.forEach((plan) => {
    const planCard = document.createElement("div");
    planCard.className = "plan-card";
    planCard.onclick = () => selectPaidPlan(plan);

    const features = plan.features || [];
    const includedEquipment = plan.included_equipment || [];

    let featuresList = features
      .map((f) => `<li><span class="check">✓</span> ${f}</li>`)
      .join("");
    let equipmentList = includedEquipment
      .map(
        (eq) =>
          `<li><span class="check">✓</span> ${eq.quantity_included} ${eq.unit} - ${eq.name}</li>`
      )
      .join("");

    planCard.innerHTML = `
            <div class="plan-header">
                <h3 class="plan-name">${plan.name}</h3>
                <div class="plan-price">₱${parseFloat(
                  plan.price
                ).toLocaleString()}</div>
                <p class="plan-duration">${plan.duration}</p>
            </div>
            <div class="plan-features">
                <ul>
                    ${featuresList}
                    ${equipmentList}
                </ul>
            </div>
        `;

    plansGrid.appendChild(planCard);
  });
}

function selectPaidPlan(plan) {
  selectedPlan = plan;
  document.getElementById("paidSelectedPlanId").value = plan.id;

  // Update UI to show selected plan
  document.querySelectorAll(".plan-card").forEach((card) => {
    card.classList.remove("selected");
  });
  event.target.closest(".plan-card").classList.add("selected");

  updatePaidCostSummary();
  showToast(`Selected: ${plan.name}`, "success");
}

function populatePaidAddons() {
  const addonsGrid = document.getElementById("paidAddonsGrid");
  if (!addonsGrid) return;

  addonsGrid.innerHTML = "";

  if (!addonsData || addonsData.length === 0) {
    addonsGrid.innerHTML =
      '<p style="color: #6c757d;">No add-ons available.</p>';
    return;
  }

  addonsData.forEach((addon) => {
    const addonCard = document.createElement("div");
    addonCard.className = "addon-card";

    addonCard.innerHTML = `
            <div class="addon-checkbox">
                <input type="checkbox" id="paid-addon-${
                  addon.id
                }" onchange="togglePaidAddon('${addon.id}')">
            </div>
            <div class="addon-info">
                <h4 class="addon-name">${addon.name}</h4>
                <p class="addon-description">${addon.description || ""}</p>
            </div>
            <div class="addon-price">₱${addon.price.toLocaleString()}</div>
        `;

    addonsGrid.appendChild(addonCard);
  });
}

function togglePaidAddon(addonId) {
  const checkbox = document.getElementById(`paid-addon-${addonId}`);

  if (checkbox.checked) {
    selectedAddons.push(addonId);
  } else {
    selectedAddons = selectedAddons.filter((id) => id !== addonId);
  }

  updatePaidCostSummary();
}

function showPaidEquipmentDatePrompt() {
  const equipmentGrid = document.getElementById("paidEquipmentGrid");
  const placeholder = document.getElementById("paidEquipmentDatePlaceholder");

  if (equipmentGrid) equipmentGrid.style.display = "none";
  if (placeholder) placeholder.style.display = "block";
}

async function handlePaidDateChange() {
  const eventDate = document.getElementById("paidEventDate").value;

  if (eventDate) {
    // Load equipment for this date
    await loadPaidEquipmentForDate(eventDate);

    // Show equipment grid
    document.getElementById("paidEquipmentGrid").style.display = "grid";
    document.getElementById("paidEquipmentDatePlaceholder").style.display =
      "none";
  }
}

async function loadPaidEquipmentForDate(eventDate) {
  try {
    const response = await fetch("/api/bookings/equipment-availability", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        event_date: eventDate,
        facility_id: currentFacilityId,
      }),
    });

    const result = await response.json();

    if (result.success && result.equipment) {
      // Filter for rentable equipment only
      equipmentData = result.equipment
        .filter((eq) => eq.is_rentable == 1 && parseFloat(eq.rate || 0) > 0)
        .filter(
          (eq) => eq.category === "furniture" || eq.category === "logistics"
        )
        .map((equipment) => ({
          id: equipment.id.toString(),
          name: equipment.name,
          rate: parseFloat(equipment.rate || 0),
          unit: equipment.unit || "piece",
          available: parseInt(equipment.available_on_date || 0),
          category: equipment.category,
        }));

      populatePaidEquipment();
    }
  } catch (error) {
    console.error("Error loading equipment:", error);
  }
}

function populatePaidEquipment() {
  const equipmentGrid = document.getElementById("paidEquipmentGrid");
  if (!equipmentGrid) return;

  equipmentGrid.innerHTML = "";

  if (!equipmentData || equipmentData.length === 0) {
    equipmentGrid.innerHTML =
      '<p style="color: #6c757d;">No rental equipment available.</p>';
    return;
  }

  equipmentData.forEach((equipment) => {
    const equipmentCard = document.createElement("div");
    equipmentCard.className = "equipment-card";

    const isAvailable = equipment.available > 0;

    equipmentCard.innerHTML = `
            <div class="equipment-info">
                <h4 class="equipment-name">${equipment.name}</h4>
                <p class="equipment-description">${equipment.category}</p>
                <span class="equipment-price">₱${equipment.rate.toLocaleString()}/${
      equipment.unit
    }</span>
            </div>
            <div class="equipment-actions-card">
                ${
                  !isAvailable
                    ? `<input type="number" class="form-control qty-input" value="0" disabled>
                       <label class="equipment-label" style="color: #dc2626;">Out of Stock</label>`
                    : `<input type="number" class="form-control qty-input"
                              id="paid-qty-${equipment.id}"
                              min="0" max="${equipment.available}" value="0"
                              onchange="updatePaidEquipment('${equipment.id}')">
                       <label class="equipment-label">Available: ${equipment.available}</label>`
                }
            </div>
        `;

    equipmentGrid.appendChild(equipmentCard);
  });
}

function updatePaidEquipment(equipmentId) {
  const quantityInput = document.getElementById(`paid-qty-${equipmentId}`);
  const quantity = parseInt(quantityInput.value) || 0;
  const equipment = equipmentData.find((e) => e.id === equipmentId);

  if (!equipment) return;

  if (quantity > equipment.available) {
    alert(`Only ${equipment.available} units available for ${equipment.name}`);
    quantityInput.value = equipment.available;
    selectedEquipment[equipmentId] = equipment.available;
  } else if (quantity > 0) {
    selectedEquipment[equipmentId] = quantity;
  } else {
    delete selectedEquipment[equipmentId];
  }

  updatePaidCostSummary();
}

function updatePaidCostSummary() {
  let basePrice = selectedPlan ? selectedPlan.price : 0;

  // Calculate addon cost
  let addonsPrice = 0;
  selectedAddons.forEach((addonId) => {
    const addon = addonsData.find((a) => a.id === addonId);
    if (addon) addonsPrice += addon.price;
  });

  // Calculate equipment cost
  let equipmentPrice = 0;
  Object.keys(selectedEquipment).forEach((equipmentId) => {
    const equipment = equipmentData.find((e) => e.id === equipmentId);
    const quantity = selectedEquipment[equipmentId];
    if (equipment && quantity > 0) {
      equipmentPrice += equipment.rate * quantity;
    }
  });

  // Calculate additional hours cost
  const additionalHours =
    parseInt(document.getElementById("paidAdditionalHours")?.value) || 0;
  const additionalHoursPrice = additionalHours * HOURLY_RATE;

  // Update display
  document.getElementById(
    "paidBaseCost"
  ).textContent = `₱${basePrice.toLocaleString()}`;
  document.getElementById(
    "paidMaintenanceCost"
  ).textContent = `₱${MAINTENANCE_FEE.toLocaleString()}`;

  // Build addon costs display
  let addonCostsHTML = "";
  if (addonsPrice > 0) {
    addonCostsHTML += `<div class="cost-row"><span>Add-ons:</span><span>₱${addonsPrice.toLocaleString()}</span></div>`;
  }
  if (equipmentPrice > 0) {
    addonCostsHTML += `<div class="cost-row"><span>Equipment:</span><span>₱${equipmentPrice.toLocaleString()}</span></div>`;
  }
  if (additionalHoursPrice > 0) {
    addonCostsHTML += `<div class="cost-row"><span>Additional Hours:</span><span>₱${additionalHoursPrice.toLocaleString()}</span></div>`;
  }
  document.getElementById("paidAddonCosts").innerHTML = addonCostsHTML;

  const total =
    basePrice +
    addonsPrice +
    equipmentPrice +
    additionalHoursPrice +
    MAINTENANCE_FEE;
  document.getElementById(
    "paidTotalCost"
  ).textContent = `₱${total.toLocaleString()}`;
}

async function submitPaidBooking() {
  const form = document.getElementById("paidBookingForm");
  const statusIndicator = document.getElementById("paidAvailabilityStatus");

  // Check if facility is inactive
  if (statusIndicator && statusIndicator.classList.contains("maintenance")) {
    showToast(
      "Cannot book an inactive facility. Please select another facility.",
      "error"
    );
    return;
  }

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  if (!selectedPlan) {
    showToast("Please select a package first.", "error");
    return;
  }

  // STEP 1: Check for date + time + grace period conflicts
  const facilityId = parseInt(document.getElementById("paidFacilityId").value);
  const eventDate = document.getElementById("paidEventDate").value;
  const eventTime = document.getElementById("paidEventTime").value;
  const additionalHours =
    parseInt(document.getElementById("paidAdditionalHours")?.value) || 0;
  const durationMatch = selectedPlan.duration.match(/\d+/);
  const planDuration = durationMatch ? parseInt(durationMatch[0]) : 0;
  const totalDuration = planDuration + additionalHours;

  // Calculate end time with 2-hour grace period
  const startTime = new Date(`2000-01-01 ${eventTime}`);
  const endTime = new Date(
    startTime.getTime() + totalDuration * 60 * 60 * 1000
  );
  const endTimeWithGrace = new Date(endTime.getTime() + 2 * 60 * 60 * 1000);

  const endTimeStr =
    String(endTime.getHours()).padStart(2, "0") +
    ":" +
    String(endTime.getMinutes()).padStart(2, "0");
  const endTimeWithGraceStr =
    String(endTimeWithGrace.getHours()).padStart(2, "0") +
    ":" +
    String(endTimeWithGrace.getMinutes()).padStart(2, "0");

  try {
    showToast("Checking date availability...", "info");

    const conflictCheck = await fetch("/api/bookings/checkDateConflict", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        facility_id: facilityId,
        event_date: eventDate,
        event_time: eventTime,
        duration: totalDuration,
      }),
    });

    const conflictResult = await conflictCheck.json();

    if (
      conflictResult.hasConflict ||
      conflictResult.hasPendingOrApprovedBooking
    ) {
      showToast(
        "⚠️ Conflict Detected: Facility has a conflicting booking on this date/time. Your requested time: " +
          eventTime +
          " - " +
          endTimeStr +
          ". With 2-hour grace period, available from: " +
          endTimeWithGraceStr,
        "error"
      );
      return;
    }

    // Continue with booking submission if no conflicts
    // Calculate total cost
    let basePrice = selectedPlan.price;
    let addonsPrice = 0;
    selectedAddons.forEach((addonId) => {
      const addon = addonsData.find((a) => a.id === addonId);
      if (addon) addonsPrice += addon.price;
    });

    let equipmentPrice = 0;
    Object.keys(selectedEquipment).forEach((equipmentId) => {
      const equipment = equipmentData.find((e) => e.id === equipmentId);
      const quantity = selectedEquipment[equipmentId];
      if (equipment && quantity > 0) {
        equipmentPrice += equipment.rate * quantity;
      }
    });

    const additionalHoursPrice = additionalHours * HOURLY_RATE;
    const totalCost =
      basePrice +
      addonsPrice +
      equipmentPrice +
      additionalHoursPrice +
      MAINTENANCE_FEE;

    const formData = {
      facility_key: currentFacilityKey,
      plan_id: selectedPlan.id,
      client_name: document.getElementById("paidClientName").value,
      contact_number: document.getElementById("paidContactNumber").value,
      email_address: document.getElementById("paidEmailAddress").value,
      organization: document.getElementById("paidOrganization").value,
      address: document.getElementById("paidAddress").value,
      event_date: document.getElementById("paidEventDate").value,
      event_time: document.getElementById("paidEventTime").value,
      duration: totalDuration,
      attendees: document.getElementById("paidAttendees").value || null,
      event_title: document.getElementById("paidEventTitle").value,
      special_requirements: document.getElementById("paidSpecialRequirements")
        .value,
      selected_addons: selectedAddons,
      selected_equipment: selectedEquipment,
      additional_hours: additionalHours,
      maintenance_fee: MAINTENANCE_FEE,
      total_cost: totalCost,
    };

    const response = await fetch("/api/bookings", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(formData),
    });

    const result = await response.json();

    if (result.success) {
      document.getElementById("paidReferenceNumber").textContent =
        "BK" + String(result.booking_id).padStart(3, "0");
      closePaidModal();

      // Show Bootstrap success modal
      const successModal = new bootstrap.Modal(
        document.getElementById("paidSuccessModal")
      );
      successModal.show();

      // Reset form
      form.reset();
      selectedPlan = null;
      selectedAddons = [];
      selectedEquipment = {};
    } else {
      showToast(result.message || "Failed to create booking", "error");
    }
  } catch (error) {
    console.error("Error submitting paid booking:", error);
    showToast(
      "An error occurred while submitting your booking. Please try again.",
      "error"
    );
  }
}

function closePaidSuccessModal() {
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("paidSuccessModal")
  );
  if (modal) modal.hide();
  window.location.href = "/faculty/bookings";
}

// ==================== UTILITY FUNCTIONS ====================

function showToast(message, type = "info") {
  const toastContainer =
    document.getElementById("toastContainer") || createToastContainer();

  const toastId = "toast-" + Date.now();
  const icons = {
    error: "❌",
    success: "✅",
    warning: "⚠️",
    info: "ℹ️",
  };

  const toastHTML = `
        <div id="${toastId}" class="toast-notification toast-${type}">
            <div class="toast-content">
                <span class="toast-icon">${icons[type]}</span>
                <span class="toast-message">${message}</span>
            </div>
            <div class="toast-close" onclick="closeToast('${toastId}')">×</div>
        </div>
    `;

  toastContainer.insertAdjacentHTML("beforeend", toastHTML);
  setTimeout(() => closeToast(toastId), 5000);
}

function createToastContainer() {
  let container = document.getElementById("toastContainer");
  if (!container) {
    container = document.createElement("div");
    container.id = "toastContainer";
    container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
        `;
    document.body.appendChild(container);
  }
  return container;
}

function closeToast(toastId) {
  const toast = document.getElementById(toastId);
  if (toast) {
    toast.style.animation = "slideOut 0.3s ease-out";
    setTimeout(() => toast.remove(), 300);
  }
}

// ==================== FORM VALIDATION LISTENERS ====================
document.addEventListener("DOMContentLoaded", function () {
  // Free form validation
  const freeFormInputs = document.querySelectorAll(
    "#freeBookingForm input, #freeBookingForm textarea"
  );
  freeFormInputs.forEach((input) => {
    input.addEventListener("input", validateFreeForm);
    input.addEventListener("change", validateFreeForm);
  });
});
