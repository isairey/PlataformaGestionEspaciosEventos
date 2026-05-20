// Equipment Management JavaScript - Fixed Table Structure
document.addEventListener("DOMContentLoaded", function () {
  loadEquipment();
  initializeEventListeners();
});

let equipmentData = [];
let currentEquipmentId = null;

// Initialize event listeners
function initializeEventListeners() {
  // Search functionality
  document
    .getElementById("searchInput")
    .addEventListener("input", function (e) {
      filterEquipment();
    });

  // Quantity validation for add form
  document
    .getElementById("equipmentGood")
    .addEventListener("input", validateQuantity);
  document
    .getElementById("equipmentDamaged")
    .addEventListener("input", validateQuantity);
  document
    .getElementById("equipmentQuantity")
    .addEventListener("input", validateQuantity);

  // Quantity validation for edit form
  document
    .getElementById("editEquipmentGood")
    .addEventListener("input", validateEditQuantity);
  document
    .getElementById("editEquipmentDamaged")
    .addEventListener("input", validateEditQuantity);
  document
    .getElementById("editEquipmentQuantity")
    .addEventListener("input", validateEditQuantity);

  // Form submissions
  document
    .getElementById("addEquipmentForm")
    .addEventListener("submit", handleAddEquipment);
  document
    .getElementById("editEquipmentForm")
    .addEventListener("submit", handleEditEquipment);

  // Modal close on background click
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("modal")) {
      closeModals();
    }
  });

  // Toggle sidebar
  document.querySelector(".toggle-btn").addEventListener("click", function () {
    document.querySelector(".sidebar").classList.toggle("collapsed");
  });
}

// Load equipment data from server
async function loadEquipment() {
  showLoading(true);

  try {
    const response = await fetch("/admin/equipment/getEquipment", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

if (result.success) {
  equipmentData = result.data;
  displayEquipmentTable(equipmentData);
} else {
  showNotification("Error loading equipment: " + result.message, "error");
}
  } catch (error) {
    console.error("Error loading equipment:", error);
    showNotification("Failed to load equipment data", "error");
  } finally {
    showLoading(false);
  }
}


// Display equipment in table format - FIXED
function displayEquipmentTable(equipment) {
  const tbody = document.getElementById("equipmentTableBody");

  if (equipment.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="6" class="text-center">No equipment found</td></tr>';
    return;
  }

  tbody.innerHTML = equipment
    .map(
      (item) => `
        <tr>
            <td>
                <div class="equipment-name-cell">
                    <span class="equipment-icon-small">🔧</span>
                    <span>${escapeHtml(item.name)}</span>
                </div>
            </td>
            <td class="text-center">${item.quantity}</td>
            <td class="text-right">₱${parseFloat(
              item.price
            ).toLocaleString()}</td>
            <td class="text-center">
                <span class="status-count good">${item.good}</span>
            </td>
            <td class="text-center">
                <span class="status-count damaged">${item.damaged}</span>
            </td>
            <td class="table-actions">
                <div class="action-buttons">
                    <button class="btn-sm btn-view" onclick="viewEquipment(${
                      item.id
                    })" title="View Details">👁️</button>
                    <button class="btn-sm btn-edit" onclick="editEquipment(${
                      item.id
                    })" title="Edit">✏️</button>
                    <button class="btn-sm btn-delete" onclick="deleteEquipment(${
                      item.id
                    })" title="Delete">🗑️</button>
                </div>
            </td>
        </tr>
    `
    )
    .join("");
}

// Filter equipment based on search and status
function filterEquipment() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();
  const statusFilter = document.getElementById("statusFilter").value;

  let filteredEquipment = equipmentData.filter((item) => {
    const matchesSearch = item.name.toLowerCase().includes(searchTerm);
    const matchesStatus = !statusFilter || item.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

displayEquipmentTable(filteredEquipment);
}

// Validate quantity inputs
function validateQuantity() {
  const quantity =
    parseInt(document.getElementById("equipmentQuantity").value) || 0;
  const good = parseInt(document.getElementById("equipmentGood").value) || 0;
  const damaged =
    parseInt(document.getElementById("equipmentDamaged").value) || 0;
  const feedback = document.getElementById("quantityFeedback");

  if (good + damaged !== quantity && quantity > 0) {
    feedback.textContent = `Good (${good}) + Damaged (${damaged}) must equal Total Quantity (${quantity})`;
    feedback.className = "validation-feedback error";
  } else {
    feedback.textContent = "";
    feedback.className = "validation-feedback";
  }
}

// Validate edit quantity inputs
function validateEditQuantity() {
  const quantity =
    parseInt(document.getElementById("editEquipmentQuantity").value) || 0;
  const good =
    parseInt(document.getElementById("editEquipmentGood").value) || 0;
  const damaged =
    parseInt(document.getElementById("editEquipmentDamaged").value) || 0;
  const feedback = document.getElementById("editQuantityFeedback");

  if (good + damaged !== quantity && quantity > 0) {
    feedback.textContent = `Good (${good}) + Damaged (${damaged}) must equal Total Quantity (${quantity})`;
    feedback.className = "validation-feedback error";
  } else {
    feedback.textContent = "";
    feedback.className = "validation-feedback";
  }
}

// Modal functions
function openAddModal() {
  document.getElementById("addModal").style.display = "block";
  document.body.style.overflow = "hidden";
}

function closeAddModal() {
  document.getElementById("addModal").style.display = "none";
  document.body.style.overflow = "";
  document.getElementById("addEquipmentForm").reset();
  document.getElementById("quantityFeedback").textContent = "";
}

function closeViewModal() {
  document.getElementById("viewModal").style.display = "none";
  document.body.style.overflow = "";
}

function closeEditModal() {
  document.getElementById("editModal").style.display = "none";
  document.body.style.overflow = "";
  document.getElementById("editEquipmentForm").reset();
  document.getElementById("editQuantityFeedback").textContent = "";
}

function closeDeleteModal() {
  document.getElementById("deleteModal").style.display = "none";
  document.body.style.overflow = "";
  currentEquipmentId = null;
}

function closeModals() {
  closeAddModal();
  closeViewModal();
  closeEditModal();
  closeDeleteModal();
}

// View equipment details
async function viewEquipment(id) {
  try {
    const response = await fetch(`/admin/equipment/getEquipmentDetails/${id}`, {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      const equipment = result.data;
      const detailsHtml = `
                <div class="equipment-detail-card">
                    <div class="equipment-detail-icon">
                        <span class="equipment-emoji-large">🔧</span>
                    </div>
                    <div class="equipment-detail-info">
                        <h3>${escapeHtml(equipment.name)}</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Total Quantity:</label>
                                <span>${
                                  parseInt(equipment.quantity) || 0
                                }</span>
                            </div>
                            <div class="detail-item">
                                <label>Price:</label>
                                <span>₱${formatPrice(equipment.price)}</span>
                            </div>
                            <div class="detail-item">
                                <label>Good Condition:</label>
                                <span class="good">${
                                  parseInt(equipment.good) || 0
                                }</span>
                            </div>
                            <div class="detail-item">
                                <label>Damaged:</label>
                                <span class="damaged">${
                                  parseInt(equipment.damaged) || 0
                                }</span>
                            </div>
                            <div class="detail-item">
                                <label>Available:</label>
                                <span class="available">${
                                  parseInt(equipment.available) || 0
                                }</span>
                            </div>
                            <div class="detail-item">
                                <label>Currently Rented:</label>
                                <span class="rented">${
                                  parseInt(equipment.rented) || 0
                                }</span>
                            </div>
                            <div class="detail-item">
                                <label>Status:</label>
                                <span class="status-badge status-${
                                  equipment.status || "unknown"
                                }">
                                    ${formatStatus(equipment.status)}
                                </span>
                            </div>
                            <div class="detail-item">
                                <label>Created:</label>
                                <span>${formatDate(equipment.created_at)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

      document.getElementById("equipmentDetails").innerHTML = detailsHtml;
      document.getElementById("viewModal").style.display = "block";
      document.body.style.overflow = "hidden";
    } else {
      showNotification(
        "Error loading equipment details: " + result.message,
        "error"
      );
    }
  } catch (error) {
    console.error("Error loading equipment details:", error);
    showNotification("Failed to load equipment details", "error");
  }
}

// Edit equipment
async function editEquipment(id) {
  try {
    const response = await fetch(`/admin/equipment/getEquipmentDetails/${id}`, {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      const equipment = result.data;

      // Populate form fields
      document.getElementById("editEquipmentId").value = equipment.id;
      document.getElementById("editEquipmentName").value = equipment.name;
      document.getElementById("editEquipmentQuantity").value =
        equipment.quantity;
      document.getElementById("editEquipmentPrice").value = equipment.price;
      document.getElementById("editEquipmentGood").value = equipment.good;
      document.getElementById("editEquipmentDamaged").value = equipment.damaged;

      document.getElementById("editModal").style.display = "block";
      document.body.style.overflow = "hidden";
    } else {
      showNotification("Error loading equipment: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error loading equipment:", error);
    showNotification("Failed to load equipment data", "error");
  }
}

// Delete equipment
function deleteEquipment(id) {
  currentEquipmentId = id;
  document.getElementById("deleteModal").style.display = "block";
  document.body.style.overflow = "hidden";
}

// Confirm delete
async function confirmDelete() {
  if (!currentEquipmentId) return;

  showLoading(true);

  try {
    const response = await fetch(
      `/admin/equipment/deleteEquipment/${currentEquipmentId}`,
      {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
      }
    );

    const result = await response.json();

    if (result.success) {
      showNotification("Equipment deleted successfully", "success");
      closeDeleteModal();
      loadEquipment(); // Reload the equipment list
    } else {
      showNotification("Error: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error deleting equipment:", error);
    showNotification("Failed to delete equipment", "error");
  } finally {
    showLoading(false);
  }
}

// Handle add equipment form submission
async function handleAddEquipment(e) {
  e.preventDefault();

  const formData = new FormData(e.target);

  // Validate quantities
  const quantity = parseInt(formData.get("quantity"));
  const good = parseInt(formData.get("good"));
  const damaged = parseInt(formData.get("damaged") || 0);

  if (good + damaged !== quantity) {
    showNotification(
      "Good condition + Damaged must equal Total Quantity",
      "error"
    );
    return;
  }

  showLoading(true);

  try {
    const response = await fetch("/admin/equipment/addEquipment", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        name: formData.get("name"),
        quantity: quantity,
        price: parseFloat(formData.get("price")),
        good: good,
        damaged: damaged,
      }),
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Equipment added successfully", "success");
      closeAddModal();
      loadEquipment(); // Reload the equipment list
    } else {
      if (result.errors) {
        const errorMessages = Object.values(result.errors).join(", ");
        showNotification("Validation errors: " + errorMessages, "error");
      } else {
        showNotification("Error: " + result.message, "error");
      }
    }
  } catch (error) {
    console.error("Error adding equipment:", error);
    showNotification("Failed to add equipment", "error");
  } finally {
    showLoading(false);
  }
}

// Handle edit equipment form submission
async function handleEditEquipment(e) {
  e.preventDefault();

  const formData = new FormData(e.target);

  // Validate quantities
  const quantity = parseInt(formData.get("quantity"));
  const good = parseInt(formData.get("good"));
  const damaged = parseInt(formData.get("damaged") || 0);

  if (good + damaged !== quantity) {
    showNotification(
      "Good condition + Damaged must equal Total Quantity",
      "error"
    );
    return;
  }

  showLoading(true);

  try {
    const response = await fetch("/admin/equipment/updateEquipment", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        id: formData.get("id"),
        name: formData.get("name"),
        quantity: quantity,
        price: parseFloat(formData.get("price")),
        good: good,
        damaged: damaged,
      }),
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Equipment updated successfully", "success");
      closeEditModal();
      loadEquipment(); // Reload the equipment list
    } else {
      if (result.errors) {
        const errorMessages = Object.values(result.errors).join(", ");
        showNotification("Validation errors: " + errorMessages, "error");
      } else {
        showNotification("Error: " + result.message, "error");
      }
    }
  } catch (error) {
    console.error("Error updating equipment:", error);
    showNotification("Failed to update equipment", "error");
  } finally {
    showLoading(false);
  }
}

// Generate report - Download Excel file
function generateReport() {
  showLoading(true);

  try {
    // Create a temporary anchor element to trigger download
    const downloadUrl = "/admin/equipment/generateReport";
    window.location.href = downloadUrl;

    // Show success notification after a brief delay
    setTimeout(() => {
      showNotification("Equipment report generated successfully", "success");
      showLoading(false);
    }, 1000);
  } catch (error) {
    console.error("Error generating report:", error);
    showNotification("Failed to generate report", "error");
    showLoading(false);
  }
}

// Utility functions
function showLoading(show) {
  const overlay = document.getElementById("loadingOverlay");
  if (overlay) {
    overlay.style.display = show ? "flex" : "none";
  }
}

function showNotification(message, type = "info") {
  // Create notification element if it doesn't exist
  let notification = document.getElementById("notification");
  if (!notification) {
    notification = document.createElement("div");
    notification.id = "notification";
    notification.className = "notification";
    document.body.appendChild(notification);
  }

  notification.textContent = message;
  notification.className = `notification ${type} show`;

  // Auto hide after 5 seconds
  setTimeout(() => {
    notification.classList.remove("show");
  }, 5000);
}

function escapeHtml(text) {
  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  };
  return text.replace(/[&<>"']/g, function (m) {
    return map[m];
  });
}

function formatPrice(price) {
  return parseFloat(price || 0).toLocaleString();
}

function formatStatus(status) {
  if (!status) return "Unknown";
  return status.charAt(0).toUpperCase() + status.slice(1);
}

function formatDate(dateString) {
  if (!dateString) return "N/A";

  try {
    const date = new Date(dateString);
    return date.toLocaleDateString() + " " + date.toLocaleTimeString();
  } catch (error) {
    return "Invalid Date";
  }
}

function formatDateForFilename(date) {
  return date.toISOString().split("T")[0];
}

// Keyboard shortcuts
document.addEventListener("keydown", function (e) {
  // Escape key to close modals
  if (e.key === "Escape") {
    closeModals();
  }

  // Ctrl+N to open add modal
  if (e.ctrlKey && e.key === "n") {
    e.preventDefault();
    openAddModal();
  }

  // Ctrl+R to reload equipment
  if (e.ctrlKey && e.key === "r") {
    e.preventDefault();
    loadEquipment();
  }
});

document.addEventListener("DOMContentLoaded", function () {
  // Check if we're on the equipment page
  if (document.getElementById("equipmentTable")) {
    loadEquipment();
    initializeEventListeners();
  }
});


// Dropdown toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get all dropdown toggles
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the parent dropdown element
            const dropdown = this.closest('.dropdown');
            
            // Close other dropdowns
            document.querySelectorAll('.dropdown').forEach(otherDropdown => {
                if (otherDropdown !== dropdown) {
                    otherDropdown.classList.remove('open');
                }
            });
            
            // Toggle current dropdown
            dropdown.classList.toggle('open');
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                dropdown.classList.remove('open');
            });
        }
    });
    
    // Handle submenu item clicks
    const submenuItems = document.querySelectorAll('.submenu-item');
    submenuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Remove active class from all submenu items
            submenuItems.forEach(i => i.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
        });
    });
});
