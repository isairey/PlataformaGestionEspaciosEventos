let currentEventId = null;
let guests = [];
let html5QrCode = null;
let isScanning = false;
let currentGuestForQR = null;

document.addEventListener("DOMContentLoaded", function () {
  loadUserEvents();
  // Initialize QR Code scanner
  html5QrCode = new Html5Qrcode("qrScanner");
});

// Load user's confirmed events
async function loadUserEvents() {
  try {
    const response = await fetch("/user/bookings/list");
    const data = await response.json();

    if (data.success) {
      const confirmedEvents = data.bookings.filter(
        (b) => b.status === "confirmed"
      );
      const selector = document.getElementById("eventSelector");

      if (confirmedEvents.length === 0) {
        selector.innerHTML =
          '<option value="">No confirmed events available</option>';
        return;
      }

      confirmedEvents.forEach((event) => {
        const option = document.createElement("option");
        option.value = event.id;
        option.textContent = `${event.event_title} - ${formatDate(
          event.event_date
        )}`;
        option.dataset.event = JSON.stringify(event);
        selector.appendChild(option);
      });
    }
  } catch (error) {
    console.error("Error loading events:", error);
    showAlert("error", "Failed to load events");
  }
}

// Load event attendance data
async function loadEventAttendance() {
  const selector = document.getElementById("eventSelector");
  const selectedOption = selector.options[selector.selectedIndex];

  if (!selectedOption.value) {
    showAlert("error", "Please select an event");
    return;
  }

  currentEventId = selectedOption.value;
  const eventData = JSON.parse(selectedOption.dataset.event);

  // Display event details
  document.getElementById("eventDetails").style.display = "block";
  document.getElementById("eventName").textContent = eventData.event_title;
  document.getElementById("facilityName").textContent = eventData.facility_name;
  document.getElementById("eventDate").textContent = formatDate(
    eventData.event_date
  );
  document.getElementById("eventTime").textContent = formatTime(
    eventData.event_time
  );

  // Show attendance section
  document.getElementById("attendanceSection").style.display = "block";

  // Load guests from API
  await loadGuests();

  showAlert(
    "success",
    "Event loaded successfully! You can now start scanning."
  );
}

// Load guests from API
async function loadGuests() {
  try {
    console.log(`Loading guests for booking ID: ${currentEventId}...`);
    const response = await fetch(`/api/bookings/${currentEventId}/guests`);
    console.log("Guests API response status:", response.status);

    const data = await response.json();
    console.log("Guests API response data:", data);

    if (data.success) {
      console.log(`Found ${data.guests.length} guests`);
      guests = data.guests.map((g) => ({
        id: g.id,
        name: g.guest_name,
        email: g.guest_email,
        phone: g.guest_phone,
        qr_code: g.qr_code,
        qr_code_path: g.qr_code_path,
        attended: g.attended == 1,
        time: g.attendance_time
          ? formatAttendanceTime(g.attendance_time)
          : null,
      }));

      renderGuestList();
      await updateStatistics();
    } else {
      console.error("Guests API returned error:", data.message);
      showAlert(
        "error",
        `Failed to load guests: ${data.message || "Unknown error"}`
      );
      guests = [];
      renderGuestList();
      updateStatistics();
    }
  } catch (error) {
    console.error("Error loading guests:", error);
    showAlert("error", `Failed to load guests: ${error.message}`);
    guests = [];
    renderGuestList();
    updateStatistics();
  }
}

// Render guest list
function renderGuestList() {
  const tbody = document.getElementById("guestTableBody");

  if (guests.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <i class="fas fa-users-slash fa-2x text-muted mb-2"></i>
                    <p class="text-muted">No guests have registered yet</p>
                    <p class="text-muted"><small>Share the registration link with your guests to get started</small></p>
                </td>
            </tr>
        `;
    return;
  }

  tbody.innerHTML = guests
    .map(
      (guest, index) => `
        <tr class="${guest.attended ? "attended" : ""}" id="guest-row-${
        guest.id
      }">
            <td>${index + 1}</td>
            <td>
                <strong>${guest.name}</strong>
                <br>
                <small class="text-muted">QR: ${guest.qr_code}</small>
                ${
                  guest.email
                    ? `<br><small class="text-muted"><i class="fas fa-envelope"></i> ${guest.email}</small>`
                    : ""
                }
            </td>
            <td>
                <span class="status-badge ${
                  guest.attended ? "attended" : "pending"
                }">
                    <i class="fas fa-${
                      guest.attended ? "check-circle" : "clock"
                    }"></i>
                    ${guest.attended ? "Attended" : "Pending"}
                </span>
            </td>
            <td>
                ${
                  guest.attended && guest.time
                    ? `<span class="time-badge"><i class="far fa-clock"></i> ${guest.time}</span>`
                    : '<span class="text-muted">Not checked in</span>'
                }
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    ${
                      !guest.attended
                        ? `<button class="btn btn-action btn-success" onclick="openManualCheckIn(${guest.id})" title="Check In">
                            <i class="fas fa-user-check"></i>
                        </button>`
                        : '<span class="text-success"><i class="fas fa-check"></i></span>'
                    }
                    <button class="btn btn-action btn-danger" onclick="deleteGuest(${
                      guest.id
                    })" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `
    )
    .join("");
}

// Update statistics
async function updateStatistics() {
  try {
    const response = await fetch(
      `/api/events/${currentEventId}/attendance-stats`,
      { credentials: "include" }
    );

    if (!response.ok) {
      throw new Error("Failed to fetch stats");
    }

    const data = await response.json();

    if (data.success) {
      const stats = data.stats;
      document.getElementById("totalGuests").textContent = stats.total;
      document.getElementById("attendedCount").textContent = stats.attended;
      document.getElementById("pendingCount").textContent = stats.pending;
      document.getElementById("attendanceRate").textContent =
        stats.attendance_rate + "%";
    } else {
      throw new Error("API returned error");
    }
  } catch (error) {
    // Silently use fallback calculation for booking-based stats
    // Fallback to local calculation
    const total = guests.length;
    const attended = guests.filter((g) => g.attended).length;
    const pending = total - attended;
    const rate = total > 0 ? Math.round((attended / total) * 100) : 0;

    document.getElementById("totalGuests").textContent = total;
    document.getElementById("attendedCount").textContent = attended;
    document.getElementById("pendingCount").textContent = pending;
    document.getElementById("attendanceRate").textContent = rate + "%";
  }
}

// ============================================
// QR CODE VIEWING
// ============================================

// View Guest QR Code
async function viewGuestQR(guestId) {
  const guest = guests.find((g) => g.id === guestId);
  if (!guest) return;

  currentGuestForQR = guest;

  document.getElementById("qrGuestName").textContent = guest.name;
  document.getElementById("qrCodeValue").textContent = guest.qr_code;

  // Load QR code image
  if (guest.qr_code_path) {
    const qrImageContainer = document.getElementById("qrCodeImageContainer");
    qrImageContainer.innerHTML = `<img src="/${guest.qr_code_path}" alt="QR Code" style="max-width: 300px;" />`;
  } else {
    showAlert("error", "QR code not available for this guest");
    return;
  }

  const modal = new bootstrap.Modal(document.getElementById("qrCodeModal"));
  modal.show();
}

// Download Guest QR Code
async function downloadGuestQR() {
  if (!currentGuestForQR) return;

  try {
    const response = await fetch(
      `/api/guests/${currentGuestForQR.id}/qr-download`
    );

    if (response.ok) {
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `${currentGuestForQR.qr_code}.png`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);

      showAlert("success", "QR code downloaded successfully");
    } else {
      showAlert("error", "Failed to download QR code");
    }
  } catch (error) {
    console.error("Error downloading QR:", error);
    showAlert("error", "Failed to download QR code");
  }
}

// Delete Guest
async function deleteGuest(guestId) {
  if (!confirm("Are you sure you want to delete this guest?")) {
    return;
  }

  try {
    const response = await fetch(`/api/guests/${guestId}/delete`, {
      method: "DELETE",
      credentials: "include",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const data = await response.json();

    if (data.success || response.ok) {
      showAlert("success", "Guest deleted successfully");

      // Reload guests from API to refresh the list
      await loadGuests();
    } else {
      showAlert("error", data.message || "Failed to delete guest");
    }
  } catch (error) {
    console.error("Error deleting guest:", error);
    showAlert("error", "Failed to delete guest: " + error.message);
  }
}

// ============================================
// QR SCANNER FUNCTIONS
// ============================================

async function startScanner() {
  if (isScanning) return;

  try {
    console.log("Starting QR Scanner...");

    // Initialize html5QrCode if not already done
    if (!html5QrCode) {
      console.log("Initializing Html5Qrcode with element: qrScanner");
      html5QrCode = new Html5Qrcode("qrScanner");
    }

    const config = {
      fps: 30,
      qrbox: { width: 300, height: 300 },
      aspectRatio: 1.0,
      facingMode: "environment",
      disableFlip: false,
      rememberLastUsedCamera: true,
      showTorchButtonIfSupported: true,
    };

    console.log("Starting scanner with config:", config);

    await html5QrCode.start(
      { facingMode: "environment" },
      config,
      onScanSuccess,
      onScanError
    );

    isScanning = true;
    console.log("Scanner started successfully!");
    document.getElementById("startScanBtn").style.display = "none";
    document.getElementById("stopScanBtn").style.display = "inline-block";
    document.getElementById("scanResult").style.display = "none";

    showAlert("success", "Scanner started. Point camera at QR code.");
  } catch (error) {
    console.error("Error starting scanner:", error);
    console.error("Error details:", error.message, error.name);

    // Check if it's a permission denied error
    if (
      error.name === "NotAllowedError" ||
      error.name === "PermissionDeniedError"
    ) {
      showAlert(
        "error",
        "Camera access was denied. Please enable camera permissions when prompted by your browser."
      );
    } else if (
      error.name === "NotFoundError" ||
      error.name === "DevicesNotFoundError"
    ) {
      showAlert("error", "No camera found on this device.");
    } else {
      showAlert("error", "Could not access camera: " + error.message);
    }
  }
}

async function stopScanner() {
  if (!isScanning) return;

  try {
    await html5QrCode.stop();
    isScanning = false;

    document.getElementById("startScanBtn").style.display = "inline-block";
    document.getElementById("stopScanBtn").style.display = "none";
    document.getElementById("scanResult").style.display = "none";

    showAlert("success", "Scanner stopped.");
  } catch (error) {
    console.error("Error stopping scanner:", error);
  }
}

function onScanSuccess(decodedText, decodedResult) {
  // Process the scanned QR code
  console.log("========== QR CODE SCANNED ==========");
  console.log("Decoded Text:", decodedText);
  console.log("Decoded Result:", decodedResult);
  console.log("====================================");
  processQRCode(decodedText);
}

function onScanError(errorMessage) {
  // Log all errors for debugging
  console.log("Scanner Error:", errorMessage);
}

async function processQRCode(qrData) {
  try {
    // Extract QR code from URL if needed
    let qrCode = qrData;

    // If the scanned data is a URL, extract just the QR code portion
    if (qrData.includes("/")) {
      // Try to extract from URL pattern like: site.com/qr/ABCD1234
      const parts = qrData.split("/");
      qrCode = parts[parts.length - 1];
    }

    // Remove any query parameters
    qrCode = qrCode.split("?")[0];

    // Trim whitespace and convert to uppercase for consistency
    qrCode = qrCode.trim().toUpperCase();

    console.log("Scanned QR data (raw):", qrData);
    console.log("Extracted QR code (processed):", qrCode);
    console.log(
      "Available guests:",
      guests.map((g) => ({
        id: g.id,
        name: g.name,
        qr_code: g.qr_code.toUpperCase(),
      }))
    );

    const response = await fetch("/api/attendance/scan", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ qr_code: qrCode }),
    });

    const data = await response.json();
    console.log("API Response:", data);

    if (data.success) {
      // Update local guest data - try case-insensitive match first
      const guest = guests.find((g) => g.qr_code.toUpperCase() === qrCode);
      if (guest) {
        guest.attended = true;
        guest.time = formatAttendanceTime(data.guest.attendance_time);

        renderGuestList();
        await updateStatistics();

        // Highlight the row
        const row = document.getElementById(`guest-row-${guest.id}`);
        if (row) {
          row.classList.add("new-entry");
          setTimeout(() => row.classList.remove("new-entry"), 1000);
        }

        showScanResult(
          "success",
          "Check-in Successful!",
          `${guest.name} has been checked in at ${guest.time}`
        );
      } else {
        console.warn("Guest not found in local list for QR code:", qrCode);
      }

      // Hide result after 3 seconds
      setTimeout(() => {
        document.getElementById("scanResult").style.display = "none";
      }, 3000);
    } else {
      console.error("API returned error:", data.message);
      showScanResult(
        "error",
        "Check-in Failed",
        data.message || "Invalid QR code"
      );
    }
  } catch (error) {
    console.error("Error processing QR code:", error);
    showScanResult("error", "Error", "Failed to process QR code scan");
  }
}

function showScanResult(type, title, message) {
  const resultDiv = document.getElementById("scanResult");
  resultDiv.className = `scan-result ${type}`;
  resultDiv.innerHTML = `
        <i class="fas fa-${
          type === "success" ? "check-circle" : "exclamation-circle"
        }"></i>
        <h6>${title}</h6>
        <p>${message}</p>
    `;
  resultDiv.style.display = "block";
}

// Submit manually entered QR code
function submitManualQR() {
  const input = document.getElementById("manualQRInput");
  const qrCode = input.value.trim();

  if (!qrCode) {
    showAlert("warning", "Please enter a QR code");
    return;
  }

  console.log("Manual QR code submitted:", qrCode);
  processQRCode(qrCode);
  input.value = "";
}

// Allow Enter key on manual QR input
document.addEventListener("DOMContentLoaded", function () {
  const manualQRInput = document.getElementById("manualQRInput");
  if (manualQRInput) {
    manualQRInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        submitManualQR();
      }
    });
  }
});

// Manual check-in
function openManualCheckIn(guestId) {
  const guest = guests.find((g) => g.id === guestId);

  if (!guest) return;

  document.getElementById("manualGuestId").value = guestId;
  document.getElementById("manualGuestName").textContent = guest.name;
  document.getElementById("manualNotes").value = "";

  const modal = new bootstrap.Modal(
    document.getElementById("manualCheckInModal")
  );
  modal.show();
}

async function confirmManualCheckIn() {
  const guestId = parseInt(document.getElementById("manualGuestId").value);
  const notes = document.getElementById("manualNotes").value;

  try {
    const response = await fetch("/api/attendance/manual-checkin", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ guest_id: guestId, notes: notes }),
    });

    const data = await response.json();

    if (data.success) {
      // Update local guest data
      const guest = guests.find((g) => g.id === guestId);
      if (guest) {
        guest.attended = true;
        guest.time = formatAttendanceTime(data.guest.attendance_time);

        renderGuestList();
        await updateStatistics();

        showAlert("success", `${guest.name} checked in successfully`);
      }
    } else {
      showAlert("error", data.message || "Failed to check in guest");
    }
  } catch (error) {
    console.error("Error checking in guest:", error);
    showAlert("error", "Failed to check in guest");
  }

  // Close modal
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("manualCheckInModal")
  );
  modal.hide();
}

// Download attendance
function downloadAttendance(type) {
  if (!currentEventId) {
    showAlert("error", "No event selected");
    return;
  }

  let filteredGuests = guests;
  let filename = "attendance";

  switch (type) {
    case "attended":
      filteredGuests = guests.filter((g) => g.attended);
      filename = "attended_guests";
      break;
    case "pending":
      filteredGuests = guests.filter((g) => !g.attended);
      filename = "pending_guests";
      break;
    case "all":
    default:
      filename = "all_guests";
  }

  if (filteredGuests.length === 0) {
    showAlert("warning", "No guests to download");
    return;
  }

  // Create CSV content
  let csv = "No,Guest Name,Email,Phone,QR Code,Status,Check-in Time\n";

  filteredGuests.forEach((guest, index) => {
    csv += `${index + 1},"${guest.name}","${guest.email || ""}","${
      guest.phone || ""
    }",${guest.qr_code},`;
    csv += `${guest.attended ? "Attended" : "Pending"},`;
    csv += `${guest.attended ? guest.time : "N/A"}\n`;
  });

  // Download file
  const blob = new Blob([csv], { type: "text/csv" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `${filename}_${currentEventId}_${
    new Date().toISOString().split("T")[0]
  }.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);

  showAlert("success", `Downloaded ${filteredGuests.length} guest records`);
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

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

function formatAttendanceTime(datetimeString) {
  const date = new Date(datetimeString);
  return date.toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
}

function showAlert(type, message) {
  const alertContainer = document.getElementById("alertContainer");
  const alertClass =
    type === "success"
      ? "alert-success"
      : type === "warning"
      ? "alert-warning"
      : type === "info"
      ? "alert-info"
      : "alert-danger";
  const icon =
    type === "success"
      ? "check-circle"
      : type === "warning"
      ? "exclamation-triangle"
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

// Cleanup on page unload
window.addEventListener("beforeunload", async function () {
  if (isScanning) {
    await stopScanner();
  }
});
