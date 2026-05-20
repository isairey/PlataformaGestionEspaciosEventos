let selectedStudentFacility = null;
let selectedStudentFacilityId = null;
let selectedStudentPlanId = null;
let selectedStudentEquipment = {};
let uploadedStudentFiles = {
  permission: null,
  request: null,
  approval: null,
};

// Debug logging for file uploads
function addUploadDebugLog(message, type = "info") {
  const debugPanel = document.getElementById("uploadDebugPanel");
  const debugLog = document.getElementById("uploadDebugLog");

  if (debugLog) {
    const timestamp = new Date().toLocaleTimeString();
    const icon =
      type === "success"
        ? "✅"
        : type === "error"
        ? "❌"
        : type === "warning"
        ? "⚠️"
        : "ℹ️";
    const color =
      type === "success"
        ? "#28a745"
        : type === "error"
        ? "#dc3545"
        : type === "warning"
        ? "#ffc107"
        : "#0066cc";

    const logEntry = document.createElement("div");
    logEntry.style.color = color;
    logEntry.style.marginBottom = "5px";
    logEntry.innerHTML = `[${timestamp}] ${icon} ${message}`;

    debugLog.appendChild(logEntry);

    // Show debug panel
    if (debugPanel) {
      debugPanel.style.display = "block";
    }

    // Scroll to bottom
    debugLog.scrollTop = debugLog.scrollHeight;

    // Also log to console
    console.log(`[${type.toUpperCase()}] ${message}`);
  }
}

function openStudentBookingModal(facilityKey, facilityId) {
  selectedStudentFacility = facilityKey;
  selectedStudentFacilityId = facilityId;

  // Load facility data
  loadStudentFacilityData(facilityKey);

  // Show modal
  document.getElementById("studentBookingModal").style.display = "block";

  // Initialize drag and drop for file uploads
  setTimeout(() => {
    addDragDropStyles();
    initializeStudentDragDrop();
  }, 100);
}

function closeStudentModal() {
  document.getElementById("studentBookingModal").style.display = "none";
  resetStudentForm();
}

async function loadStudentFacilityData(facilityKey) {
  try {
    const response = await fetch(`/api/student/facilities/${facilityKey}/data`);
    const data = await response.json();

    if (data.success && data.facility) {
      document.getElementById(
        "modalTitle"
      ).textContent = `Book ${data.facility.name}`;

      // Set first plan as default
      if (data.facility.plans && data.facility.plans.length > 0) {
        selectedStudentPlanId = data.facility.plans[0].id;
      }

      // Don't load equipment yet - wait for user to select event date
      // This is now date-based, so equipment availability depends on the selected date
      loadStudentEquipment(); // Will show message to select date first
    }
  } catch (error) {
    console.error("Error loading facility:", error);
    alert("Failed to load facility details");
  }
}

async function loadStudentEquipment(eventDate = null) {
  const container = document.getElementById("studentEquipmentGrid");
  if (!container) return;

  // If no event date is selected, show a message
  if (!eventDate) {
    container.innerHTML =
      '<p style="text-align: center; color: var(--gray); padding: 20px;">📅 Please select an event date first to see available equipment for that date.</p>';
    return;
  }

  // Show loading state
  container.innerHTML =
    '<p style="text-align: center; color: var(--gray); padding: 20px;">Loading equipment availability...</p>';

  try {
    const response = await fetch(
      `/api/student/equipment?event_date=${eventDate}`
    );
    const data = await response.json();

    if (data.success && data.equipment) {
      displayStudentEquipment(data.equipment, eventDate);
    } else {
      console.warn("No equipment data returned");
      displayStudentEquipment([], eventDate);
    }
  } catch (error) {
    console.error("Error loading equipment:", error);
    showToast("Failed to load equipment list", "warning");
    container.innerHTML =
      '<p style="text-align: center; color: var(--gray);">Unable to load equipment at this time.</p>';
  }
}

function displayStudentEquipment(equipmentList, eventDate) {
  const container = document.getElementById("studentEquipmentGrid");
  container.innerHTML = "";

  // Filter equipment that has availability on the selected date
  const availableEquipment = equipmentList.filter(
    (eq) => eq.is_trackable && eq.available > 0
  );

  // Group by category
  const grouped = {};
  availableEquipment.forEach((eq) => {
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
    container.appendChild(dateHeader);
  }

  // Display by category
  Object.keys(grouped).forEach((category) => {
    // Add category header
    const header = document.createElement("h4");
    header.className = "equipment-category-header";
    header.style.gridColumn = "1 / -1";
    header.style.marginTop = "20px";
    header.style.marginBottom = "10px";
    header.style.color = "var(--primary)";
    header.textContent = category.replace("_", " ").toUpperCase();
    container.appendChild(header);

    // Add equipment items
    grouped[category].forEach((equipment) => {
      const equipDiv = document.createElement("div");
      equipDiv.className = "equipment-card";
      equipDiv.innerHTML = `
                <div class="equipment-info">
                    <h4 class="equipment-name">${equipment.name}</h4>
                    <p class="equipment-description">${
                      equipment.available
                    } available on this date</p>
                    ${
                      equipment.rate > 0
                        ? `<span class="equipment-rate">₱${equipment.rate}/${equipment.unit}</span>`
                        : '<span class="included-badge">Included</span>'
                    }
                </div>
                <div class="equipment-actions-card">
                    <input type="number" class="form-control qty-input"
                           id="student-qty-${equipment.id}"
                           min="0" max="${equipment.available}" value="0"
                           onchange="updateStudentEquipment(${equipment.id})">
                    <label class="equipment-label">Quantity</label>
                </div>
            `;
      container.appendChild(equipDiv);
    });
  });

  if (availableEquipment.length === 0) {
    container.innerHTML =
      '<p style="text-align: center; color: var(--gray); padding: 20px;">No equipment available for the selected date.</p>';
  }
}

function updateStudentEquipment(equipmentId) {
  const input = document.getElementById(`student-qty-${equipmentId}`);
  const quantity = parseInt(input.value);

  if (quantity > 0) {
    selectedStudentEquipment[equipmentId] = quantity;
  } else {
    delete selectedStudentEquipment[equipmentId];
  }
}

// ========================================
// FILE UPLOAD HANDLER (UPDATED)
// ========================================
function handleStudentFileSelect(input, fileType) {
  const file = input.files[0];

  if (!file) {
    addUploadDebugLog(`File input cleared for ${fileType}`, "info");
    return;
  }

  addUploadDebugLog(`File selected for ${fileType}: ${file.name}`, "info");

  // Validate size
  if (file.size > 10 * 1024 * 1024) {
    addUploadDebugLog(
      `❌ File too large: ${file.name} (${(file.size / 1024 / 1024).toFixed(
        2
      )} MB)`,
      "error"
    );
    showToast("File size must be less than 10MB", "error");
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
    addUploadDebugLog(
      `❌ Invalid file type: ${file.type} (allowed: PDF, JPG, PNG)`,
      "error"
    );
    showToast("Only PDF, JPG, and PNG files are allowed", "error");
    input.value = "";
    return;
  }

  // Store file
  uploadedStudentFiles[fileType] = file;
  addUploadDebugLog(
    `✅ ${fileType} file validated and ready: ${file.name} (${(
      file.size / 1024
    ).toFixed(2)} KB)`,
    "success"
  );

  // Update UI
  const uploadItem = document.getElementById(`upload-${fileType}`);
  uploadItem.classList.add("uploaded");
  uploadItem.querySelector(".upload-status").textContent = "Ready";
  uploadItem.querySelector(".upload-status").style.color = "var(--success)";
  document.getElementById(`filename-${fileType}`).textContent = file.name;

  showToast(`${file.name} ready for upload`, "success");
  checkStudentFilesComplete();
}

function checkStudentFilesComplete() {
  // Make file uploads optional - always enable submit button if form is valid
  const submitBtn = document.getElementById("submitStudentBtn");
  submitBtn.disabled = false;
  submitBtn.style.opacity = "1";
  submitBtn.style.cursor = "pointer";
}

// Initialize drag and drop handlers when modal opens
function initializeStudentDragDrop() {
  const uploadAreas = document.querySelectorAll(".upload-area");

  // Attach change listeners to file inputs (in addition to onchange attribute)
  const fileInputs = document.querySelectorAll('input[type="file"]');
  fileInputs.forEach((input) => {
    const docType = input.id.replace("file-", "");

    input.addEventListener("change", (e) => {
      console.log(
        "File input change event fired for",
        docType,
        "files:",
        e.target.files.length
      );
      handleStudentFileSelect(e.target, docType);
    });
  });

  uploadAreas.forEach((area) => {
    // Prevent default drag behaviors
    ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
      area.addEventListener(eventName, preventDefaults, false);
    });

    // Highlight drop area when item is dragged over it
    ["dragenter", "dragover"].forEach((eventName) => {
      area.addEventListener(
        eventName,
        () => area.parentElement.classList.add("drag-over"),
        false
      );
    });

    ["dragleave", "drop"].forEach((eventName) => {
      area.addEventListener(
        eventName,
        () => area.parentElement.classList.remove("drag-over"),
        false
      );
    });

    // Handle dropped files
    area.addEventListener(
      "drop",
      (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;

        // Find the file input in this upload area
        const fileInput = area.querySelector('input[type="file"]');
        if (fileInput && files.length > 0) {
          // Note: Cannot assign to fileInput.files (read-only)
          // Instead, manually handle the dropped file
          const file = files[0];
          const docType = fileInput.id.replace("file-", "");

          // Simulate file selection by directly calling the handler
          // Create a mock input object with files property
          const mockInput = {
            files: files,
            value: file.name,
          };

          handleStudentFileSelect(mockInput, docType);
        }
      },
      false
    );
  });
}

function preventDefaults(e) {
  e.preventDefault();
  e.stopPropagation();
}

// Add CSS for drag-over state if not exists
function addDragDropStyles() {
  if (document.getElementById("dragdrop-styles")) return;

  const style = document.createElement("style");
  style.id = "dragdrop-styles";
  style.textContent = `
    .document-upload-card.drag-over {
      border-color: #3b82f6 !important;
      background: #eff6ff !important;
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2) !important;
    }
    
    .document-upload-card.drag-over .upload-area::before {
      background: #dbeafe !important;
      color: #0a2b7a !important;
    }
  `;
  document.head.appendChild(style);
}

async function submitStudentBooking() {
  const btn = document.getElementById("submitStudentBtn");
  btn.disabled = true;
  btn.textContent = "Processing...";

  try {
    // Validate form
    if (!validateStudentForm()) {
      throw new Error("Please fill in all required fields correctly");
    }

    // Prepare booking data
    const bookingData = {
      facility_id: selectedStudentFacilityId,
      plan_id: selectedStudentPlanId,
      client_name: document.getElementById("clientName").value.trim(),
      email_address: document.getElementById("clientEmail").value.trim(),
      organization: document.getElementById("organization").value.trim(),
      contact_number: document.getElementById("contactNumber").value.trim(),
      address: document.getElementById("address").value.trim() || "",
      event_date: document.getElementById("eventDate").value,
      event_time: document.getElementById("eventTime").value,
      duration: document.getElementById("duration").value,
      attendees: document.getElementById("attendees").value || null,
      event_title: document.getElementById("eventTitle").value.trim(),
      special_requirements:
        document.getElementById("specialRequirements").value.trim() || "",
      selected_equipment: selectedStudentEquipment,
      booking_type: document.getElementById("bookingType").value || "student",
    };

    // Create booking
    console.log("🔵 STEP 1: Creating Booking");
    console.log("Booking Data:", bookingData);

    const bookingResponse = await fetch("/api/student/bookings/create", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(bookingData),
    });

    console.log("Booking Response Status:", bookingResponse.status);

    const bookingResult = await bookingResponse.json();

    console.log("🟢 Booking Created:", bookingResult);

    if (!bookingResult.success) {
      throw new Error(bookingResult.message || "Failed to create booking");
    }

    const bookingId = bookingResult.booking_id;
    addUploadDebugLog(`Booking created with ID: ${bookingId}`, "success");

    // Upload files if provided
    const hasFiles =
      uploadedStudentFiles.permission ||
      uploadedStudentFiles.request ||
      uploadedStudentFiles.approval;

    addUploadDebugLog(
      `📁 File Check - permission: ${
        uploadedStudentFiles.permission?.name || "none"
      }, request: ${uploadedStudentFiles.request?.name || "none"}, approval: ${
        uploadedStudentFiles.approval?.name || "none"
      }`,
      "info"
    );
    addUploadDebugLog(
      `hasFiles = ${hasFiles}`,
      hasFiles ? "success" : "warning"
    );

    if (hasFiles) {
      try {
        const formData = new FormData();

        // Log files being uploaded
        addUploadDebugLog("Starting file upload process...", "info");

        // Add files in the correct array format that the server expects
        let fileList = [];

        if (uploadedStudentFiles.permission) {
          formData.append("files[]", uploadedStudentFiles.permission);
          fileList.push(
            `Permission (${(
              uploadedStudentFiles.permission.size / 1024
            ).toFixed(2)} KB)`
          );
          addUploadDebugLog(
            `✓ Added permission file: ${uploadedStudentFiles.permission.name}`,
            "success"
          );
        }
        if (uploadedStudentFiles.request) {
          formData.append("files[]", uploadedStudentFiles.request);
          fileList.push(
            `Request (${(uploadedStudentFiles.request.size / 1024).toFixed(
              2
            )} KB)`
          );
          addUploadDebugLog(
            `✓ Added request file: ${uploadedStudentFiles.request.name}`,
            "success"
          );
        }
        if (uploadedStudentFiles.approval) {
          formData.append("files[]", uploadedStudentFiles.approval);
          fileList.push(
            `Approval (${(uploadedStudentFiles.approval.size / 1024).toFixed(
              2
            )} KB)`
          );
          addUploadDebugLog(
            `✓ Added approval file: ${uploadedStudentFiles.approval.name}`,
            "success"
          );
        }

        addUploadDebugLog(`Total files to upload: ${fileList.length}`, "info");

        // Use the same working admin endpoint as booking management modal
        const uploadUrl = `/api/bookings/${bookingId}/upload`;
        addUploadDebugLog(`Sending request to: ${uploadUrl}`, "info");

        // Log FormData contents in detail
        const formDataEntries = Array.from(formData.entries());
        addUploadDebugLog(
          `FormData has ${formDataEntries.length} entries`,
          "info"
        );

        formDataEntries.forEach((entry, idx) => {
          const [key, value] = entry;
          if (value instanceof File) {
            addUploadDebugLog(
              `  [${idx}] ${key}: File - ${value.name} (${value.size} bytes, ${value.type})`,
              "info"
            );
          } else {
            addUploadDebugLog(`  [${idx}] ${key}: ${value}`, "info");
          }
        });

        addUploadDebugLog("=== NETWORK REQUEST STARTING ===", "info");

        console.log("🚀 NETWORK REQUEST DETAILS:");
        console.log("URL:", uploadUrl);
        console.log("Method: POST");
        console.log("Headers: X-Requested-With: XMLHttpRequest");
        console.log("Body Type: FormData");
        console.log(
          "FormData Entries:",
          formDataEntries.map(([k, v]) =>
            v instanceof File
              ? `${k}: File(${v.name}, ${v.size}b)`
              : `${k}: ${v}`
          )
        );

        const uploadResponse = await fetch(uploadUrl, {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          body: formData,
        });

        console.log("📡 NETWORK RESPONSE RECEIVED:");
        console.log("Status:", uploadResponse.status);
        console.log("Status Text:", uploadResponse.statusText);
        console.log("Headers:", {
          "content-type": uploadResponse.headers.get("content-type"),
          "content-length": uploadResponse.headers.get("content-length"),
        });

        addUploadDebugLog(
          `Server response status: ${uploadResponse.status}`,
          uploadResponse.ok ? "success" : "warning"
        );

        if (!uploadResponse.ok) {
          const errorText = await uploadResponse.text();
          console.error(
            "❌ HTTP ERROR:",
            uploadResponse.status,
            uploadResponse.statusText
          );
          console.error("Response Body:", errorText);
          addUploadDebugLog(`HTTP Error Response: ${errorText}`, "error");
          throw new Error(
            `HTTP ${uploadResponse.status}: ${uploadResponse.statusText}`
          );
        }

        const uploadResult = await uploadResponse.json();

        console.log("✅ PARSED RESPONSE:");
        console.log("Response JSON:", uploadResult);
        console.log("Upload Success:", uploadResult.success);
        console.log("Upload Message:", uploadResult.message);
        console.log(
          "Upload Files Count:",
          uploadResult.files ? uploadResult.files.length : "N/A"
        );

        addUploadDebugLog(
          `Server response: ${JSON.stringify(uploadResult)}`,
          "info"
        );

        if (!uploadResult.success) {
          addUploadDebugLog(`Upload failed: ${uploadResult.message}`, "error");
          console.error("❌ Upload failed:", uploadResult.message);
          showToast(
            "Booking created but some files failed to upload: " +
              uploadResult.message,
            "warning"
          );
        } else {
          console.log(
            "✅ Upload success - added",
            uploadResult.files.length,
            "file(s)"
          );
          addUploadDebugLog(
            `✅ All ${uploadResult.files.length} file(s) uploaded successfully!`,
            "success"
          );
          uploadResult.files.forEach((file, index) => {
            addUploadDebugLog(
              `  File ${index + 1}: ${file.file_type} - ${file.filename}`,
              "success"
            );
          });

          // Verify files are in database by fetching them
          addUploadDebugLog(`Verifying files are saved in database...`, "info");
          try {
            const verifyResponse = await fetch(
              `/api/bookings/${bookingId}/files`,
              {
                headers: {
                  "X-Requested-With": "XMLHttpRequest",
                },
              }
            );
            const verifyResult = await verifyResponse.json();

            if (verifyResult.success) {
              addUploadDebugLog(
                `✅ Database verification: Found ${
                  verifyResult.count || verifyResult.files.length
                } file(s) in database`,
                "success"
              );
              verifyResult.files.forEach((file, index) => {
                addUploadDebugLog(
                  `  DB File ${index + 1}: ${file.file_type} - ${
                    file.original_filename
                  } (${file.file_size} bytes)`,
                  "success"
                );
              });
            } else {
              addUploadDebugLog(
                `❌ Database verification failed: ${verifyResult.message}`,
                "error"
              );
            }
          } catch (verifyError) {
            addUploadDebugLog(
              `❌ Verification error: ${verifyError.message}`,
              "error"
            );
          }

          showToast(
            `${uploadResult.files.length} file(s) uploaded successfully`,
            "success"
          );
        }
      } catch (uploadError) {
        console.error("❌ UPLOAD ERROR:", uploadError);
        console.error("Error Stack:", uploadError.stack);
        addUploadDebugLog(`❌ Upload error: ${uploadError.message}`, "error");
        showToast(
          "Booking created but file upload failed: " + uploadError.message,
          "warning"
        );
      }
    } else {
      addUploadDebugLog("No files selected - skipping upload", "info");
      console.log("⚠️ No files to upload");
    }

    // Reset button state before showing success
    btn.disabled = false;
    btn.textContent = "✓ Submit Booking";

    // Show success
    showStudentSuccess(bookingId);
    showToast("Booking submitted successfully!", "success");
  } catch (error) {
    console.error("❌ FORM SUBMISSION ERROR:", error);
    console.error("Error Message:", error.message);
    console.error("Error Stack:", error.stack);
    addUploadDebugLog(`Form submission error: ${error.message}`, "error");
    showToast(error.message || "Failed to submit booking", "error");
    btn.disabled = false;
    btn.textContent = "✓ Submit Booking";
  }
}

// ========================================
function validateStudentForm() {
  const validations = [
    {
      id: "bookingType",
      label: "Booking Type",
      test: (val) => val.length > 0,
      message: "Please select a booking type",
    },
    {
      id: "clientName",
      label: "Full Name",
      test: (val) => val.length >= 3,
      message: "Full name must be at least 3 characters",
    },
    {
      id: "clientEmail",
      label: "Email Address",
      test: (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val),
      message: "Please enter a valid email address",
    },
    {
      id: "organization",
      label: "Organization",
      test: (val) => val.length >= 3,
      message: "Organization name must be at least 3 characters",
    },
    {
      id: "contactNumber",
      label: "Contact Number",
      test: (val) => val.replace(/\D/g, "").length >= 7,
      message: "Contact number must contain at least 7 digits",
    },
    {
      id: "eventDate",
      label: "Event Date",
      test: (val) => {
        const eventDate = new Date(val);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return eventDate >= today;
      },
      message: "Event date cannot be in the past",
    },
    {
      id: "eventTime",
      label: "Event Time",
      test: (val) => val.length > 0,
      message: "Please select an event time",
    },
    {
      id: "duration",
      label: "Duration",
      test: (val) => {
        const num = parseInt(val);
        return num >= 1 && num <= 12;
      },
      message: "Duration must be between 1 and 12 hours",
    },
    {
      id: "eventTitle",
      label: "Event Title",
      test: (val) => val.length >= 5,
      message: "Event title must be at least 5 characters",
    },
  ];

  // Clear all previous errors
  validations.forEach((v) => hideInlineError(v.id));

  let isValid = true;
  let firstErrorField = null;

  for (const validation of validations) {
    const field = document.getElementById(validation.id);
    if (!field) continue;

    const value = field.value.trim();

    // Check if empty
    if (!value) {
      showInlineError(validation.id, `${validation.label} is required`);
      if (!firstErrorField) firstErrorField = field;
      isValid = false;
      continue;
    }

    // Check validation test
    if (!validation.test(value)) {
      showInlineError(validation.id, validation.message);
      if (!firstErrorField) firstErrorField = field;
      isValid = false;
    }
  }

  // Validate address if provided (optional but must meet minimum)
  const address = document.getElementById("address").value.trim();
  if (address && address.length < 10) {
    showInlineError(
      "address",
      "Address must be at least 10 characters. Include street, city, and province."
    );
    if (!firstErrorField) firstErrorField = document.getElementById("address");
    isValid = false;
  }

  // Validate attendees if provided
  const attendees = document.getElementById("attendees").value.trim();
  if (attendees && (isNaN(attendees) || parseInt(attendees) < 1)) {
    showInlineError(
      "attendees",
      "Number of attendees must be a positive number"
    );
    if (!firstErrorField)
      firstErrorField = document.getElementById("attendees");
    isValid = false;
  }

  if (!isValid && firstErrorField) {
    firstErrorField.focus();
    showToast("Please fix the errors in the form", "error");
  }

  return isValid;
}

function showStudentSuccess(bookingId) {
  const modalBody = document.querySelector("#studentBookingModal .modal-body");
  const modalFooter = document.querySelector(
    "#studentBookingModal .modal-footer"
  );

  modalBody.innerHTML = `
    <div class="success-message" style="text-align: center; padding: 40px 20px;">
      <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
      <h3 style="color: var(--success); margin-bottom: 15px;">Booking Submitted Successfully!</h3>
      <p style="margin-bottom: 10px; font-size: 18px;"><strong>Booking ID:</strong> #BK${String(
        bookingId
      ).padStart(3, "0")}</p>
      <p style="margin-bottom: 10px; color: var(--gray);">Your booking request has been submitted for approval.</p>
      <p style="margin-bottom: 30px; color: var(--gray);">You will receive a notification once it has been reviewed by the admin.</p>
    </div>
  `;

  // Completely hide and remove footer
  if (modalFooter) {
    modalFooter.innerHTML = "";
    modalFooter.style.display = "none";
    modalFooter.style.visibility = "hidden";
    modalFooter.style.height = "0";
    modalFooter.style.padding = "0";
  }

  // Auto-close modal after 3 seconds
  setTimeout(() => {
    closeAndReload();
  }, 3000);
}

// Add new function to close modal and redirect to booking management
function closeAndReload() {
  closeStudentModal();
  // Redirect to Booking Management page instead of reloading current page
  window.location.href = "/admin/booking-management";
}

function resetStudentForm() {
  selectedStudentFacility = null;
  selectedStudentFacilityId = null;
  selectedStudentPlanId = null;
  selectedStudentEquipment = {};
  uploadedStudentFiles = { permission: null, request: null, approval: null };

  // Safe form reset
  const form = document.getElementById("studentBookingForm");
  if (form) {
    form.reset();
  }

  // Reset upload items
  const uploadItems = document.querySelectorAll(".upload-item");
  if (uploadItems) {
    uploadItems.forEach((item) => {
      item.classList.remove("uploaded");
      const statusEl = item.querySelector(".upload-status");
      if (statusEl) {
        statusEl.textContent = "Not uploaded";
        statusEl.style.color = "";
      }
    });
  }

  // Reset file name displays
  const fileNameDisplays = document.querySelectorAll(".file-name-display");
  if (fileNameDisplays) {
    fileNameDisplays.forEach((el) => (el.textContent = ""));
  }

  // Reset file inputs
  const fileInputs = document.querySelectorAll(".file-input");
  if (fileInputs) {
    fileInputs.forEach((input) => (input.value = ""));
  }

  // Reset equipment quantities
  const equipmentInputs = document.querySelectorAll('[id^="student-qty-"]');
  if (equipmentInputs) {
    equipmentInputs.forEach((input) => (input.value = "0"));
  }

  // Re-enable submit button
  const submitBtn = document.getElementById("submitStudentBtn");
  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.textContent = "Submit Booking";
    submitBtn.style.opacity = "1";
    submitBtn.style.cursor = "pointer";
  }

  // Reset modal footer display
  const modalFooter = document.querySelector(
    "#studentBookingModal .modal-footer"
  );
  if (modalFooter) {
    modalFooter.style.display = "flex";
  }
}

// Close modal on outside click
window.onclick = function (event) {
  const modal = document.getElementById("studentBookingModal");
  if (event.target === modal) {
    closeStudentModal();
  }
};

document.addEventListener("DOMContentLoaded", function () {
  console.log("Student booking page initialized");

  // Initialize sidebar functionality
  initializeSidebar();
  initializeDropdowns();
  initializeMobileMenu();

  // Initialize address character counter
  const addressField = document.getElementById("address");
  if (addressField) {
    initializeAddressCounter(addressField);
  }

  // Set minimum date for event date picker (today)
  const eventDateField = document.getElementById("eventDate");
  if (eventDateField) {
    const today = new Date().toISOString().split("T")[0];
    eventDateField.setAttribute("min", today);

    // Reload equipment when event date changes (date-based availability)
    eventDateField.addEventListener("change", function () {
      const selectedDate = this.value;
      if (selectedDate) {
        console.log("Event date changed to:", selectedDate);
        // Reset selected equipment when date changes
        selectedStudentEquipment = {};
        const equipmentInputs = document.querySelectorAll(
          '[id^="student-qty-"]'
        );
        equipmentInputs.forEach((input) => (input.value = "0"));

        // Load equipment for the new date
        loadStudentEquipment(selectedDate);
        showToast("Equipment availability updated for selected date", "info");
      }
    });
  }

  // Add input validation listeners
  addValidationListeners();
});

// ========================================
// Address Character Counter
// ========================================
function initializeAddressCounter(addressField) {
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
      counter.textContent = "Optional field";
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

// ========================================
// Add Validation Listeners
// ========================================
function addValidationListeners() {
  const fields = [
    { id: "clientName", minLength: 3 },
    { id: "organization", minLength: 3 },
    { id: "eventTitle", minLength: 5 },
  ];

  fields.forEach(({ id, minLength }) => {
    const field = document.getElementById(id);
    if (!field) return;

    field.addEventListener("blur", function () {
      const value = this.value.trim();
      if (value && value.length < minLength) {
        showInlineError(id, `Must be at least ${minLength} characters`);
      } else if (value) {
        hideInlineError(id);
      }
    });

    field.addEventListener("input", function () {
      if (this.classList.contains("field-error")) {
        hideInlineError(id);
      }
    });
  });

  // Email validation
  const emailField = document.getElementById("clientEmail");
  if (emailField) {
    emailField.addEventListener("blur", function () {
      const value = this.value.trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (value && !emailRegex.test(value)) {
        showInlineError("clientEmail", "Please enter a valid email address");
      } else if (value) {
        hideInlineError("clientEmail");
      }
    });

    emailField.addEventListener("input", function () {
      if (this.classList.contains("field-error")) {
        hideInlineError("clientEmail");
      }
    });
  }

  // Contact number validation
  const contactField = document.getElementById("contactNumber");
  if (contactField) {
    contactField.addEventListener("blur", function () {
      const value = this.value.trim();
      const digitsOnly = value.replace(/\D/g, "");
      if (value && digitsOnly.length < 7) {
        showInlineError("contactNumber", "Must have at least 7 digits");
      } else if (value) {
        hideInlineError("contactNumber");
      }
    });

    contactField.addEventListener("input", function () {
      if (this.classList.contains("field-error")) {
        hideInlineError("contactNumber");
      }
    });
  }
}

// ========================================
// LOAD FACILITY DATA (UPDATED)
// ========================================
async function loadStudentFacilityData(facilityKey) {
  try {
    const response = await fetch(`/api/student/facilities/${facilityKey}/data`);
    const data = await response.json();

    if (data.success && data.facility) {
      document.getElementById(
        "modalTitle"
      ).textContent = `Book ${data.facility.name}`;

      if (data.facility.plans && data.facility.plans.length > 0) {
        selectedStudentPlanId = data.facility.plans[0].id;
      }

      loadStudentEquipment();
    } else {
      throw new Error("Failed to load facility details");
    }
  } catch (error) {
    console.error("Error loading facility:", error);
    showToast("Failed to load facility details", "error");
    closeStudentModal();
  }
}

// ========================================
// Helper Functions for Field Validation
// ========================================
function showFieldError(field, message) {
  // Remove existing error
  hideFieldError(field);

  // Create error element
  const error = document.createElement("small");
  error.className = "field-error";
  error.style.color = "#dc3545";
  error.style.fontSize = "12px";
  error.style.marginTop = "3px";
  error.style.display = "block";
  error.textContent = message;

  // Insert after field
  field.parentNode.insertBefore(error, field.nextSibling);
}

function hideFieldError(field) {
  const error = field.parentNode.querySelector(".field-error");
  if (error) {
    error.remove();
  }
}

function toggleSidebar() {
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (window.innerWidth <= 768) {
    // Mobile: slide in/out
    sidebar.classList.toggle("active");
  } else {
    // Desktop: collapse/expand
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("expanded");

    // Save state to localStorage
    const isCollapsed = sidebar.classList.contains("collapsed");
    localStorage.setItem("sidebarCollapsed", isCollapsed);
  }
}

function initializeSidebar() {
  // Restore sidebar state from localStorage
  if (window.innerWidth > 768) {
    const isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
    if (isCollapsed) {
      document.querySelector(".sidebar").classList.add("collapsed");
      document.querySelector(".main-content").classList.add("expanded");
    }
  }

  // Setup toggle button
  const toggleBtn = document.querySelector(".toggle-btn");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", toggleSidebar);
  }
}

function initializeDropdowns() {
  const dropdownToggles = document.querySelectorAll(".dropdown-toggle");

  dropdownToggles.forEach((toggle) => {
    toggle.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const dropdown = this.closest(".dropdown");
      const isOpen = dropdown.classList.contains("open");

      // Close all other dropdowns
      closeAllDropdowns();

      // Toggle current dropdown
      if (!isOpen) {
        dropdown.classList.add("open");
        this.classList.add("active");
      }
    });
  });

  // Setup submenu items
  const submenuItems = document.querySelectorAll(".submenu-item");
  const currentPath = window.location.pathname;

  submenuItems.forEach((item) => {
    const itemHref = item.getAttribute("href");
    if (itemHref && currentPath.includes(itemHref)) {
      item.classList.add("active");

      // Open parent dropdown
      const parentDropdown = item.closest(".dropdown");
      if (parentDropdown) {
        parentDropdown.classList.add("open");
        parentDropdown
          .querySelector(".dropdown-toggle")
          ?.classList.add("active");
      }
    }
  });
}

function closeAllDropdowns() {
  document.querySelectorAll(".dropdown").forEach((dropdown) => {
    dropdown.classList.remove("open");
    dropdown.querySelector(".dropdown-toggle")?.classList.remove("active");
  });
}

function initializeMobileMenu() {
  // Close sidebar when clicking outside on mobile
  document.addEventListener("click", function (e) {
    if (window.innerWidth <= 768) {
      const sidebar = document.querySelector(".sidebar");
      const toggleBtn = document.querySelector(".toggle-btn");

      if (
        sidebar &&
        sidebar.classList.contains("active") &&
        !sidebar.contains(e.target) &&
        !toggleBtn?.contains(e.target)
      ) {
        sidebar.classList.remove("active");
      }
    }
  });

  // Handle window resize
  window.addEventListener("resize", function () {
    const sidebar = document.querySelector(".sidebar");
    const mainContent = document.querySelector(".main-content");

    if (window.innerWidth <= 768) {
      sidebar?.classList.remove("collapsed");
      mainContent?.classList.remove("expanded");
    } else {
      sidebar?.classList.remove("active");
    }
  });
}

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
