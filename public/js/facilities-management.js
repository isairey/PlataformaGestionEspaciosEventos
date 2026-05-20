// Facilities Management JavaScript

let facilities = [];
let currentFacilityId = null;
let deleteTargetId = null;

// Load facilities on page load
document.addEventListener("DOMContentLoaded", function () {
  loadFacilities();

  // Setup search functionality
  const searchInput = document.getElementById("searchFacilities");
  if (searchInput) {
    searchInput.addEventListener("input", filterFacilities);
  }

  // Sidebar toggle
  const toggleBtn = document.querySelector(".toggle-btn");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", function () {
      document.querySelector(".sidebar").classList.toggle("active");
      document.querySelector(".main-content").classList.toggle("active");
    });
  }
});

// Load all facilities from API
async function loadFacilities() {
  try {
    const response = await fetch("/api/facilities/all", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    if (response.ok) {
      const result = await response.json();
      if (result.success) {
        facilities = result.data;
        renderFacilities(facilities);
        updateStats(facilities);
      } else {
        showToast(result.message || "Failed to load facilities", "error");
      }
    } else {
      showToast("Failed to load facilities", "error");
    }
  } catch (error) {
    console.error("Error loading facilities:", error);
    showToast("Error loading facilities", "error");
  }
}

// Render facilities table
function renderFacilities(facilitiesData) {
  const tbody = document.getElementById("facilitiesTableBody");

  if (!facilitiesData || facilitiesData.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-state">
                    <div class="empty-state-icon">🏢</div>
                    <h3>No Facilities Found</h3>
                    <p>Add your first facility to get started</p>
                </td>
            </tr>
        `;
    return;
  }

  tbody.innerHTML = facilitiesData
    .map(
      (facility) => `
        <tr>
            <td class="facility-icon">${facility.icon || "🏢"}</td>
            <td class="facility-name">${facility.name}</td>
            <td><span class="facility-key">${facility.facility_key}</span></td>
            <td class="facility-capacity"><strong>${
              facility.capacity || "N/A"
            }</strong> persons</td>
            <td class="facility-description" title="${
              facility.description || ""
            }">${facility.description || "No description"}</td>
            <td>
                <span class="status-badge ${
                  facility.is_active == 1 ? "active" : "inactive"
                }">
                    ${facility.is_active == 1 ? "✅ Active" : "❌ Inactive"}
                </span>
            </td>
            <td>
                <span class="maintenance-badge ${
                  facility.is_maintenance == 1 ? "yes" : "no"
                }">
                    ${facility.is_maintenance == 1 ? "🔧 Yes" : "✓ No"}
                </span>
            </td>
            <td>
                <div class="action-btns">
                    <button class="btn-action btn-edit" onclick="editFacility(${
                      facility.id
                    })" title="Edit">
                        ✏️ Edit
                    </button>
                    <button class="btn-action btn-toggle" onclick="toggleActive(${
                      facility.id
                    }, ${facility.is_active})" title="Toggle Status">
                        ${facility.is_active == 1 ? "🚫" : "✅"}
                    </button>
                    <button class="btn-action btn-maintenance" onclick="toggleMaintenance(${
                      facility.id
                    }, ${facility.is_maintenance})" title="Toggle Maintenance">
                        ${facility.is_maintenance == 1 ? "✓" : "🔧"}
                    </button>
                    <button class="btn-action btn-delete" onclick="deleteFacility(${
                      facility.id
                    })" title="Delete">
                        🗑️
                    </button>
                </div>
            </td>
        </tr>
    `
    )
    .join("");
}

// Update statistics
function updateStats(facilitiesData) {
  const total = facilitiesData.length;
  const active = facilitiesData.filter(
    (f) => f.is_active == 1 && f.is_maintenance == 0
  ).length;
  const maintenance = facilitiesData.filter(
    (f) => f.is_maintenance == 1
  ).length;
  const inactive = facilitiesData.filter((f) => f.is_active == 0).length;

  document.getElementById("totalFacilities").textContent = total;
  document.getElementById("activeFacilities").textContent = active;
  document.getElementById("maintenanceFacilities").textContent = maintenance;
  document.getElementById("inactiveFacilities").textContent = inactive;
}

// Filter facilities based on search
function filterFacilities() {
  const searchTerm = document
    .getElementById("searchFacilities")
    .value.toLowerCase();
  const filtered = facilities.filter(
    (facility) =>
      facility.name.toLowerCase().includes(searchTerm) ||
      facility.facility_key.toLowerCase().includes(searchTerm) ||
      (facility.description &&
        facility.description.toLowerCase().includes(searchTerm))
  );
  renderFacilities(filtered);
}

// Open add facility modal
function openAddFacilityModal() {
  currentFacilityId = null;
  document.getElementById("modalTitle").textContent = "Add New Facility";
  document.getElementById("facilityForm").reset();
  document.getElementById("facilityId").value = "";
  document.getElementById("facilityKey").value = "";
  document.getElementById("isActive").checked = true;
  document.getElementById("isMaintenance").checked = false;
  document.getElementById("facilityModal").style.display = "block";
}

// Generate facility key from name
function generateFacilityKey() {
  const name = document.getElementById("facilityName").value.trim();
  if (name) {
    // Convert to lowercase, replace spaces and special characters with hyphens
    const key = name
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, ""); // Remove leading/trailing hyphens
    document.getElementById("facilityKey").value = key;
  } else {
    document.getElementById("facilityKey").value = "";
  }
}

// Edit facility
function editFacility(facilityId) {
  const facility = facilities.find(
    (f) => parseInt(f.id) === parseInt(facilityId)
  );
  if (!facility) {
    showToast("Facility not found", "error");
    console.error(
      "Facility ID not found:",
      facilityId,
      "Available IDs:",
      facilities.map((f) => f.id)
    );
    return;
  }

  currentFacilityId = facilityId;
  document.getElementById("modalTitle").textContent = "Edit Facility";
  document.getElementById("facilityId").value = facility.id;
  document.getElementById("facilityName").value = facility.name;
  document.getElementById("facilityKey").value = facility.facility_key;
  document.getElementById("facilityIcon").value = facility.icon || "";
  document.getElementById("facilityCapacity").value = facility.capacity || 1;
  document.getElementById("additionalHoursRate").value =
    facility.additional_hours_rate || 0;
  document.getElementById("facilityDescription").value =
    facility.description || "";
  document.getElementById("isActive").checked = facility.is_active == 1;
  document.getElementById("isMaintenance").checked =
    facility.is_maintenance == 1;

  // Clear previous gallery and load existing images
  galleryImages = [];
  loadExistingGallery(facility.facility_key);

  document.getElementById("facilityModal").style.display = "block";
}

// Load existing gallery images when editing
async function loadExistingGallery(facilityKey) {
  try {
    const response = await fetch(`/api/facilities/gallery/${facilityKey}`);
    const data = await response.json();

    if (data.success && data.gallery && data.gallery.length > 0) {
      // Load existing images into galleryImages array
      data.gallery.forEach((image) => {
        galleryImages.push({
          data: image.path, // Store path for preview
          file: null, // Existing images don't have file objects
          preview: image.path,
          isExisting: true,
          name: image.name,
        });
      });

      // Render using the existing function
      renderGalleryPreview();
      console.log(`Loaded ${data.gallery.length} existing gallery images`);
    }
  } catch (error) {
    console.error("Error loading existing gallery:", error);
  }
}

// Close facility modal
function closeFacilityModal() {
  document.getElementById("facilityModal").style.display = "none";
  document.getElementById("facilityForm").reset();
  galleryImages = [];
  const galleryContainer = document.getElementById("galleryItems");
  if (galleryContainer) {
    galleryContainer.innerHTML = "";
  }
  currentFacilityId = null;
}

// Save facility (add or update)
async function saveFacility() {
  const facilityId = document.getElementById("facilityId").value;
  const name = document.getElementById("facilityName").value.trim();
  const facilityKey = document.getElementById("facilityKey").value.trim();
  const icon = document.getElementById("facilityIcon").value.trim();
  const capacity =
    parseInt(document.getElementById("facilityCapacity").value) || 1;
  const additionalHoursRate = document.getElementById(
    "additionalHoursRate"
  ).value;
  const description = document
    .getElementById("facilityDescription")
    .value.trim();
  const isActive = document.getElementById("isActive").checked ? 1 : 0;
  const isMaintenance = document.getElementById("isMaintenance").checked
    ? 1
    : 0;

  // Debug logging
  console.log("Form values:", {
    facilityId,
    name,
    facilityKey,
    icon,
    additionalHoursRate,
    description,
    isActive,
    isMaintenance,
  });

  // Validation
  if (!name) {
    showToast("Facility name is required", "error");
    return;
  }

  if (!facilityKey) {
    showToast("Facility key is required", "error");
    return;
  }

  if (!icon) {
    showToast("Facility icon is required", "error");
    return;
  }

  if (!additionalHoursRate || parseFloat(additionalHoursRate) < 0) {
    showToast("Valid additional hours rate is required", "error");
    return;
  }

  // Use FormData to handle both JSON and file uploads
  const formData = new FormData();
  formData.append("name", name);
  formData.append("facility_key", facilityKey);
  formData.append("icon", icon);
  formData.append("capacity", capacity);
  formData.append("additional_hours_rate", parseFloat(additionalHoursRate));
  formData.append("description", description);
  formData.append("is_active", isActive);
  formData.append("is_maintenance", isMaintenance);

  // Add only NEW gallery images (not existing ones)
  galleryImages.forEach((image, index) => {
    if (image.file) {
      // Only add if it has a file object (new image)
      formData.append(`gallery_images[]`, image.file);
    }
  });

  // Debug: Log FormData contents
  console.log("FormData contents:");
  for (let [key, value] of formData.entries()) {
    if (value instanceof File) {
      console.log(`  ${key}: File(${value.name})`);
    } else {
      console.log(`  ${key}: ${value}`);
    }
  }

  try {
    const url = facilityId
      ? `/api/facilities/update/${facilityId}`
      : "/api/facilities/create";
    const method = "POST";

    console.log("Sending to:", url);

    const response = await fetch(url, {
      method: method,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      body: formData,
    });

    const result = await response.json();

    console.log("Server response:", result);

    if (result.success) {
      showToast(
        result.message ||
          (facilityId
            ? "Facility updated successfully"
            : "Facility created successfully"),
        "success"
      );
      closeFacilityModal();
      await loadFacilities();
    } else {
      showToast(result.message || "Failed to save facility", "error");
    }
  } catch (error) {
    console.error("Error saving facility:", error);
    showToast("Error saving facility", "error");
  }
}

// Toggle facility active status
async function toggleActive(facilityId, currentStatus) {
  const newStatus = currentStatus == 1 ? 0 : 1;
  await updateFacilityStatus(facilityId, { is_active: newStatus });
}

// Toggle facility maintenance status
async function toggleMaintenance(facilityId, currentStatus) {
  const newStatus = currentStatus == 1 ? 0 : 1;
  await updateFacilityStatus(facilityId, { is_maintenance: newStatus });
}

// Update facility status
async function updateFacilityStatus(facilityId, statusData) {
  try {
    const response = await fetch(`/api/facilities/update/${facilityId}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(statusData),
    });

    const result = await response.json();

    if (result.success) {
      showToast(result.message || "Status updated successfully", "success");
      loadFacilities();
    } else {
      showToast(result.message || "Failed to update status", "error");
    }
  } catch (error) {
    console.error("Error updating status:", error);
    showToast("Error updating status", "error");
  }
}

// Delete facility
function deleteFacility(facilityId) {
  deleteTargetId = facilityId;
  document.getElementById("deleteModal").style.display = "block";
}

// Close delete modal
function closeDeleteModal() {
  document.getElementById("deleteModal").style.display = "none";
  deleteTargetId = null;
}

// Confirm delete
async function confirmDelete() {
  if (!deleteTargetId) return;

  try {
    const response = await fetch(`/api/facilities/delete/${deleteTargetId}`, {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      showToast(result.message || "Facility deleted successfully", "success");
      closeDeleteModal();
      loadFacilities();
    } else {
      showToast(result.message || "Failed to delete facility", "error");
    }
  } catch (error) {
    console.error("Error deleting facility:", error);
    showToast("Error deleting facility", "error");
  }
}

// Toast notification system
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

// Close modal when clicking outside
window.onclick = function (event) {
  const facilityModal = document.getElementById("facilityModal");
  const deleteModal = document.getElementById("deleteModal");

  if (event.target === facilityModal) {
    closeFacilityModal();
  }
  if (event.target === deleteModal) {
    closeDeleteModal();
  }
};

// Dropdown toggle functionality
function toggleDropdown(event) {
  event.preventDefault();
  const dropdown = event.currentTarget.closest(".dropdown");

  // Close other dropdowns
  document.querySelectorAll(".dropdown").forEach((otherDropdown) => {
    if (otherDropdown !== dropdown) {
      otherDropdown.classList.remove("open");
    }
  });

  // Toggle current dropdown
  dropdown.classList.toggle("open");
}

// Close dropdown when clicking outside
document.addEventListener("click", function (e) {
  if (!e.target.closest(".dropdown")) {
    document.querySelectorAll(".dropdown").forEach((dropdown) => {
      dropdown.classList.remove("open");
    });
  }
});

// Gallery Upload Handler
let galleryImages = [];

function handleGalleryUpload(event) {
  const files = event.target.files;
  const maxImages = 6;

  if (galleryImages.length >= maxImages) {
    showToast(`Maximum ${maxImages} images allowed`, "warning");
    return;
  }

  for (let file of files) {
    if (galleryImages.length >= maxImages) break;

    if (!file.type.startsWith("image/")) {
      showToast("Only image files are allowed", "error");
      continue;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
      galleryImages.push({
        data: e.target.result,
        file: file,
        name: file.name,
      });
      renderGalleryPreview();
    };
    reader.readAsDataURL(file);
  }

  // Reset input
  event.target.value = "";
}

function renderGalleryPreview() {
  const container = document.getElementById("galleryItems");

  if (!container) return;

  container.innerHTML = galleryImages
    .map((img, index) => {
      const aspectClass = index % 2 === 0 ? "aspect-16-9" : "aspect-4-3";
      return `
    <div class="gallery-preview-item ${aspectClass}">
      <div class="gallery-image-wrapper">
        <img src="${img.data}" alt="Gallery ${index + 1}">
        <button type="button" class="gallery-image-remove" onclick="removeGalleryImage(${index})" title="Remove">
          ×
        </button>
      </div>
    </div>
  `;
    })
    .join("");
}

function removeGalleryImage(index) {
  const image = galleryImages[index];
  const facilityKey = document.getElementById("facilityKey").value.trim();

  // If it's an existing image (has isExisting flag), delete from server
  if (image.isExisting && image.name) {
    deleteImageFromServer(image.name, index, facilityKey);
  } else {
    // New image, just remove from array
    galleryImages.splice(index, 1);
    renderGalleryPreview();
  }
}

// Delete image file from server
async function deleteImageFromServer(filename, index, facilityKey) {
  try {
    const response = await fetch(
      `/api/facilities/image/${facilityKey}/${filename}`,
      {
        method: "DELETE",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      }
    );

    const result = await response.json();

    if (result.success) {
      galleryImages.splice(index, 1);
      renderGalleryPreview();
      console.log(`Deleted image: ${filename}`);
    } else {
      showToast(`Failed to delete image: ${result.message}`, "error");
    }
  } catch (error) {
    console.error("Error deleting image:", error);
    showToast("Error deleting image", "error");
  }
}

// Clear gallery when closing modal
function closeFacilityModal() {
  document.getElementById("facilityModal").style.display = "none";
  document.getElementById("facilityForm").reset();
  galleryImages = [];
  const previewGrid = document.getElementById("galleryPreviewGrid");
  if (previewGrid) {
    previewGrid.innerHTML = "";
  }
  currentFacilityId = null;
}
