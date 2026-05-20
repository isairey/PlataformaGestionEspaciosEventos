// Extension Management for Admin (Phase 5)
// Handles viewing, approving, rejecting extensions and uploading receipts

let extensionModalOpen = false;
let currentExtensionBookingId = null;

/**
 * Display Extensions tab in booking details modal
 * Called after booking details are loaded
 */
function displayExtensionsTab(bookingId) {
  try {
    // Create tab button if doesn't exist
    let tabButton = document.querySelector('[data-tab="extensions"]');
    if (!tabButton) {
      const tabContainer = document.querySelector(".booking-tabs");
      if (tabContainer) {
        const extensionTabBtn = document.createElement("button");
        extensionTabBtn.className = "tab-button";
        extensionTabBtn.setAttribute("data-tab", "extensions");
        extensionTabBtn.innerHTML = '<i class="fas fa-clock"></i> Extensions';
        extensionTabBtn.onclick = () => switchBookingTab("extensions");
        tabContainer.appendChild(extensionTabBtn);
      }
    }

    // Load and display extensions for this booking
    loadBookingExtensions(bookingId);
    currentExtensionBookingId = bookingId;
  } catch (error) {
    console.error("Error displaying extensions tab:", error);
  }
}

/**
 * Load extensions for a specific booking
 */
async function loadBookingExtensions(bookingId) {
  try {
    const response = await fetch(
      `/api/extensions/pending?booking_id=${bookingId}`
    );
    const data = await response.json();

    if (data.success) {
      displayExtensionsTabContent(data.extensions || [], bookingId);
    } else {
      showExtensionsError(data.message || "Failed to load extensions");
    }
  } catch (error) {
    console.error("Error loading extensions:", error);
    showExtensionsError("Failed to load extensions");
  }
}

/**
 * Display extensions tab content
 */
function displayExtensionsTabContent(extensions, bookingId = null) {
  if (!bookingId && currentExtensionBookingId) {
    bookingId = currentExtensionBookingId;
  }

  let content = `
    <div class="extensions-tab-content">
      <div class="extensions-header">
        <h3><i class="fas fa-hourglass-half"></i> Booking Extensions</h3>
        <p>Manage and approve hours extension requests</p>
      </div>
  `;

  if (extensions.length === 0) {
    content += `
      <div class="no-extensions">
        <i class="fas fa-info-circle"></i>
        <p>No extension requests for this booking</p>
      </div>
    `;
  } else {
    extensions.forEach((ext) => {
      content += generateExtensionCard(ext, bookingId);
    });
  }

  content += `</div>`;

  // Update tab content area
  let tabContent = document.querySelector(
    '.tab-content[data-tab="extensions"]'
  );
  if (!tabContent) {
    tabContent = document.createElement("div");
    tabContent.className = "tab-content";
    tabContent.setAttribute("data-tab", "extensions");
    tabContent.style.display = "none"; // Hidden by default
    document.querySelector(".booking-details").appendChild(tabContent);
  }
  tabContent.innerHTML = content;
}

/**
 * Generate individual extension card
 */
function generateExtensionCard(extension, bookingId = null) {
  if (!bookingId && currentExtensionBookingId) {
    bookingId = currentExtensionBookingId;
  }

  const statusClass = `status-${extension.status}`;
  const paymentStatusClass = `payment-status-${extension.payment_status}`;

  let card = `
    <div class="extension-card ${statusClass}">
      <div class="extension-header">
        <div class="extension-info">
          <h4>Extension Request #${extension.id}</h4>
          <p class="extension-date">Requested: ${formatDate(
            extension.created_at
          )}</p>
        </div>
        <div class="extension-status">
          <span class="status-badge ${statusClass}">${formatStatus(
    extension.status
  )}</span>
          <span class="payment-badge ${paymentStatusClass}">${formatPaymentStatus(
    extension.payment_status
  )}</span>
        </div>
      </div>

      <div class="extension-details">
        <div class="detail-row">
          <span class="label">Hours Requested:</span>
          <span class="value">${extension.extension_hours} hours</span>
        </div>
        <div class="detail-row">
          <span class="label">Extension Cost:</span>
          <span class="value">₱${parseFloat(
            extension.extension_cost || 0
          ).toLocaleString()}</span>
        </div>
        ${
          extension.reason
            ? `
          <div class="detail-row">
            <span class="label">Reason:</span>
            <span class="value">${extension.reason}</span>
          </div>
        `
            : ""
        }
        <div class="detail-row">
          <span class="label">Requested By:</span>
          <span class="value">${extension.requested_by_name || "Unknown"}</span>
        </div>
      </div>
  `;

  // Add action buttons based on status
  if (extension.status === "pending") {
    card += `
      <div class="extension-actions">
        <button class="btn btn-success btn-sm" onclick="approveExtension(${extension.id}, '${extension.extension_hours}')">
          <i class="fas fa-check"></i> Approve
        </button>
        <button class="btn btn-danger btn-sm" onclick="openRejectExtensionModal(${extension.id})">
          <i class="fas fa-times"></i> Reject
        </button>
      </div>
    `;
  } else if (extension.status === "approved") {
    card += `
      <div class="extension-actions">
        <button class="btn btn-primary btn-sm" onclick="openUploadReceiptModal(${
          extension.id
        }, ${bookingId})">
          <i class="fas fa-upload"></i> Upload Receipt
        </button>
        <button class="btn btn-info btn-sm" onclick="downloadExtensionPaymentOrder(${
          extension.id
        })">
          <i class="fas fa-file-download"></i> Payment Order
        </button>
        ${
          extension.payment_status !== "received"
            ? `
          <button class="btn btn-warning btn-sm" onclick="markExtensionPaid(${extension.id})">
            <i class="fas fa-check-circle"></i> Mark as Paid
          </button>
        `
            : ""
        }
      </div>
    `;
  }

  // Show files if they exist
  if (extension.files && extension.files.length > 0) {
    card += `
      <div class="extension-files">
        <h5>Uploaded Files</h5>
        <ul class="files-list">
    `;
    extension.files.forEach((file) => {
      card += `
          <li class="file-item">
            <i class="fas fa-file"></i>
            <a href="#" onclick="downloadExtensionFile(${
              file.id
            }); return false;" title="Download ${file.original_filename}">
              ${file.original_filename}
            </a>
            <small>(${formatFileSize(file.file_size)})</small>
            <button class="btn btn-sm btn-danger" onclick="deleteExtensionFile(${
              file.id
            }); return false;" title="Delete file" style="margin-left: 10px;">
              <i class="fas fa-times"></i> Delete
            </button>
          </li>
      `;
    });
    card += `
        </ul>
      </div>
    `;
  }

  card += `</div>`;
  return card;
}

/**
 * Approve extension request
 */
async function approveExtension(extensionId, extensionHours) {
  if (
    !confirm(
      `Approve this extension request for ${extensionHours} hours?\n\nThis will update the booking's additional hours and cost.`
    )
  ) {
    return;
  }

  try {
    const response = await fetch(`/api/extensions/${extensionId}/approve`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({}),
    });

    const data = await response.json();

    if (data.success) {
      showAlert(
        "success",
        `Extension approved! Booking updated with ${extensionHours} additional hours.`
      );
      // Reload extensions tab immediately without closing modal
      if (currentExtensionBookingId) {
        loadBookingExtensions(currentExtensionBookingId);
      }
    } else {
      showAlert("error", data.message || "Failed to approve extension");
    }
  } catch (error) {
    console.error("Error approving extension:", error);
    showAlert("error", "Failed to approve extension");
  }
}

/**
 * Open modal to reject extension
 */
function openRejectExtensionModal(extensionId) {
  const reason = prompt(
    "Please provide a reason for rejecting this extension request:"
  );
  if (reason === null) return; // User cancelled

  if (!reason.trim()) {
    showAlert("warning", "Please provide a rejection reason");
    return;
  }

  rejectExtension(extensionId, reason.trim());
}

/**
 * Reject extension request
 */
async function rejectExtension(extensionId, rejectionReason) {
  try {
    const response = await fetch(`/api/extensions/${extensionId}/reject`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        rejection_reason: rejectionReason,
      }),
    });

    const data = await response.json();

    if (data.success) {
      showAlert("success", "Extension request rejected");
      // Reload extensions immediately without closing modal
      if (currentExtensionBookingId) {
        loadBookingExtensions(currentExtensionBookingId);
      }
    } else {
      showAlert("error", data.message || "Failed to reject extension");
    }
  } catch (error) {
    console.error("Error rejecting extension:", error);
    showAlert("error", "Failed to reject extension");
  }
}

/**
 * Open modal to upload receipt
 */
function openUploadReceiptModal(extensionId, bookingId) {
  openExtensionUploadModal(extensionId, bookingId);
}

/**
 * Create upload modal if it doesn't exist (Legacy function for backwards compatibility)
 */
function createUploadModal() {
  // Use existing HTML modal instead of creating dynamically
  const modal = document.getElementById("extensionUploadModal");
  if (!modal) {
    console.error("Extension upload modal not found in HTML");
  }
  return modal;
}

/**
 * Handle file selection for upload
 */
function handleExtensionFileSelect() {
  // This function is now handled by setupExtensionUploadModal
  // Kept for backwards compatibility
  const fileInput = document.getElementById("extensionFile");
  if (fileInput && fileInput.files[0]) {
    handleExtensionFileSelect(fileInput.files[0]);
  }
}

/**
 * Upload extension file
 */
async function uploadExtensionFile() {
  await uploadExtensionFileFromModal();
}

/**
 * Mark extension as paid
 */
async function markExtensionPaid(extensionId) {
  if (!confirm("Mark this extension request as paid?")) {
    return;
  }

  try {
    const response = await fetch(`/api/extensions/${extensionId}/mark-paid`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({}),
    });

    const data = await response.json();

    if (data.success) {
      showAlert("success", "Extension marked as paid");
      // Reload extensions immediately without closing modal
      if (currentExtensionBookingId) {
        loadBookingExtensions(currentExtensionBookingId);
      }
    } else {
      showAlert("error", data.message || "Failed to mark as paid");
    }
  } catch (error) {
    console.error("Error marking as paid:", error);
    showAlert("error", "Failed to mark as paid");
  }
}

/**
 * Download extension file
 */
function downloadExtensionFile(fileId) {
  window.location.href = `/api/extensions/files/${fileId}/download`;
}

/**
 * Delete extension file
 */
async function deleteExtensionFile(fileId) {
  if (!confirm("Are you sure you want to delete this file?")) {
    return;
  }

  try {
    const response = await fetch(`/api/extensions/files/${fileId}`, {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const data = await response.json();

    if (data.success) {
      showAlert("success", "File deleted successfully");
      // Reload extensions immediately to refresh the file list
      if (currentExtensionBookingId) {
        loadBookingExtensions(currentExtensionBookingId);
      }
    } else {
      showAlert("error", data.message || "Failed to delete file");
    }
  } catch (error) {
    console.error("Error deleting file:", error);
    showAlert("error", "Failed to delete file");
  }
}

/**
 * Close extension modal
 */
function closeExtensionModal() {
  const modal = document.getElementById("extensionUploadModal");
  if (modal) {
    modal.style.display = "none";
  }
  extensionModalOpen = false;
  currentExtensionBookingId = null;
}

/**
 * Show extensions error
 */
function showExtensionsError(message) {
  let tabContent = document.querySelector(
    '.tab-content[data-tab="extensions"]'
  );
  if (tabContent) {
    tabContent.innerHTML = `
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> ${message}
      </div>
    `;
  }
}

/**
 * Format status for display
 */
function formatStatus(status) {
  const statusMap = {
    pending: "Pending",
    approved: "Approved",
    rejected: "Rejected",
    completed: "Completed",
  };
  return statusMap[status] || status;
}

/**
 * Format payment status for display
 */
function formatPaymentStatus(status) {
  const statusMap = {
    pending: "Payment Pending",
    received: "Payment Received",
    processed: "Payment Processed",
  };
  return statusMap[status] || status;
}

/**
 * Format date
 */
function formatDate(dateStr) {
  if (!dateStr) return "N/A";
  const date = new Date(dateStr);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

/**
 * Format file size
 */
function formatFileSize(bytes) {
  if (!bytes) return "0 B";
  const k = 1024;
  const sizes = ["B", "KB", "MB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
}

/**
 * Show alert (use existing alert or create notification)
 */
function showAlert(type, message) {
  // Use native browser alert as fallback
  const prefix =
    type === "success"
      ? "✅ "
      : type === "error"
      ? "❌ "
      : type === "warning"
      ? "⚠️ "
      : "";
  alert(prefix + message);
}

/**
 * Switch to extensions tab (Deprecated - use switchBookingTab from bookingManagement.js)
 */
function switchTab(tabName, bookingId) {
  // Use the global switchBookingTab function
  if (typeof switchBookingTab === "function") {
    switchBookingTab(tabName);
  }

  // Load extensions if switching to extensions tab
  if (tabName === "extensions" && bookingId) {
    loadBookingExtensions(bookingId);
  }
}

/**
 * Handle extension upload modal interactions
 */

// Track selected file for extension upload
let selectedExtensionFile = null;
let currentExtensionUploadId = null;

/**
 * Set up upload area event listeners
 */
function setupExtensionUploadModal() {
  const uploadArea = document.getElementById("extensionUploadArea");
  const fileInput = document.getElementById("extensionFileInput");

  if (!uploadArea || !fileInput) return;

  // Click to open file picker
  uploadArea.addEventListener("click", () => fileInput.click());

  // Drag and drop
  uploadArea.addEventListener("dragover", (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = "var(--secondary)";
    uploadArea.style.backgroundColor = "#e7f3ff";
  });

  uploadArea.addEventListener("dragleave", () => {
    uploadArea.style.borderColor = "#dee2e6";
    uploadArea.style.backgroundColor = "#f8f9fa";
  });

  uploadArea.addEventListener("drop", (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = "#dee2e6";
    uploadArea.style.backgroundColor = "#f8f9fa";

    const files = e.dataTransfer.files;
    if (files.length > 0) {
      handleExtensionFileSelect(files[0]);
    }
  });

  // File input change
  fileInput.addEventListener("change", () => {
    if (fileInput.files.length > 0) {
      handleExtensionFileSelect(fileInput.files[0]);
    }
  });
}

/**
 * Handle file selection from modal
 */
function handleExtensionFileSelect(file) {
  const maxSize = 10 * 1024 * 1024; // 10MB
  const allowedTypes = ["application/pdf", "image/jpeg", "image/png"];

  // Validation
  if (file.size > maxSize) {
    showAlert("error", "File too large. Maximum size is 10MB.");
    return;
  }

  if (!allowedTypes.includes(file.type)) {
    showAlert(
      "error",
      "Invalid file type. Only PDF, JPG, and PNG are allowed."
    );
    return;
  }

  selectedExtensionFile = file;

  // Update preview
  document.getElementById("extensionFileName").textContent = file.name;
  document.getElementById("extensionFileSize").textContent = formatFileSize(
    file.size
  );
  document.getElementById("extensionFilePreview").style.display = "block";

  // Enable upload button
  document.getElementById("extensionUploadBtn").disabled = false;
}

/**
 * Remove selected file
 */
function removeExtensionFile() {
  selectedExtensionFile = null;
  document.getElementById("extensionFileInput").value = "";
  document.getElementById("extensionFilePreview").style.display = "none";
  document.getElementById("extensionUploadBtn").disabled = true;
}

/**
 * Upload extension file via modal
 */
async function uploadExtensionFileFromModal() {
  if (!selectedExtensionFile || !currentExtensionUploadId) {
    showAlert("warning", "Please select a file first.");
    return;
  }

  const documentType = document.getElementById("extensionDocumentType").value;
  if (!documentType) {
    showAlert("warning", "Please select a document type.");
    return;
  }

  const uploadBtn = document.getElementById("extensionUploadBtn");
  uploadBtn.disabled = true;
  uploadBtn.textContent = "Uploading...";

  try {
    const formData = new FormData();
    formData.append("file", selectedExtensionFile);
    formData.append("document_type", documentType);

    console.log("Uploading extension file:", {
      extensionId: currentExtensionUploadId,
      bookingId: currentExtensionBookingId,
      documentType: documentType,
      fileName: selectedExtensionFile.name,
    });

    const response = await fetch(
      `/api/extensions/${currentExtensionUploadId}/upload`,
      {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      }
    );

    const result = await response.json();

    console.log("Upload response:", result);

    if (result.success) {
      showAlert("success", "File uploaded successfully!");
      closeExtensionUploadModal();
      // Reload extensions list
      if (currentExtensionBookingId) {
        loadBookingExtensions(currentExtensionBookingId);
      }
    } else {
      showAlert("error", result.message || "Upload failed");
    }
  } catch (error) {
    console.error("Error uploading file:", error);
    showAlert("error", "Error uploading file: " + error.message);
  } finally {
    uploadBtn.disabled = false;
    uploadBtn.textContent = "Upload Document";
  }
}

/**
 * Open extension upload modal
 */
function openExtensionUploadModal(extensionId, bookingId) {
  currentExtensionUploadId = extensionId;
  currentExtensionBookingId = bookingId;

  // Reset form
  document.getElementById("extensionDocumentType").value = "";
  removeExtensionFile();

  // Show modal
  const modal = document.getElementById("extensionUploadModal");
  if (modal) {
    modal.style.display = "flex";
    setupExtensionUploadModal();
  }
}

/**
 * Close extension upload modal
 */
function closeExtensionUploadModal() {
  const modal = document.getElementById("extensionUploadModal");
  if (modal) {
    modal.style.display = "none";
  }
  selectedExtensionFile = null;
  currentExtensionUploadId = null;
}

/**
 * Alias for closing modal (for backwards compatibility)
 */
function closeExtensionModal() {
  closeExtensionUploadModal();
}

/**
 * Download extension payment order
 */
function downloadExtensionPaymentOrder(extensionId) {
  try {
    // Use the API endpoint to generate and download the payment order
    window.location.href = `/api/extensions/${extensionId}/download-order-of-payment`;
  } catch (error) {
    console.error("Error downloading extension payment order:", error);
    showAlert("Failed to download payment order", "danger");
  }
}
