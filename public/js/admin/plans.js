// Plans Management JavaScript
document.addEventListener("DOMContentLoaded", function () {
  loadPlans();
  loadServices();
  loadSettings();
  loadFacilities();
  loadFacilitiesRates();
  loadEquipmentOptions();
  initializeEventListeners();
});

let plansData = [];
let servicesData = [];
let facilitiesData = [];
let equipmentData = [];
let currentPlanId = null;
let currentServiceId = null;

// Initialize event listeners
function initializeEventListeners() {
  // Search functionality
  document.getElementById("searchInput").addEventListener("input", filterPlans);

  // Form submissions
  document.getElementById("addPlanForm").addEventListener("submit", handleAddPlan);
  document.getElementById("editPlanForm").addEventListener("submit", handleEditPlan);
  document.getElementById("addServiceForm").addEventListener("submit", handleAddService);
  document.getElementById("editServiceForm").addEventListener("submit", handleEditService);
  document.getElementById("settingsForm").addEventListener("submit", handleUpdateSettings);
  document.getElementById("editFacilityRateForm").addEventListener("submit", handleEditFacilityRate);

  // Modal close on background click
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("modal")) {
      closeAllModals();
    }
  });

  // Toggle sidebar
  const toggleBtn = document.querySelector(".toggle-btn");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", function () {
      document.querySelector(".sidebar").classList.toggle("collapsed");
    });
  }
}

// Tab switching
function switchTab(tabName) {
  // Hide all tabs
  document.querySelectorAll(".tab-content").forEach(tab => {
    tab.classList.remove("active");
  });

  // Remove active from all buttons
  document.querySelectorAll(".tab-btn").forEach(btn => {
    btn.classList.remove("active");
  });

  // Show selected tab
  document.getElementById(tabName + "Tab").classList.add("active");

  // Set active button
  event.target.classList.add("active");

  // Load data for the tab
  if (tabName === "plans") {
    loadPlans();
  } else if (tabName === "facilities") {
    loadFacilitiesRates();
  } else if (tabName === "services") {
    loadServices();
  } else if (tabName === "settings") {
    loadSettings();
  }
}

// Load plans
async function loadPlans() {
  showLoading(true);

  try {
    const response = await fetch("/admin/plans/getPlans", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      plansData = result.data;
      displayPlansTable(plansData);
    } else {
      showNotification("Error loading plans: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error loading plans:", error);
    showNotification("Failed to load plans data", "error");
  } finally {
    showLoading(false);
  }
}

// Display plans table
function displayPlansTable(plans) {
  const tbody = document.getElementById("plansTableBody");

  if (plans.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center">No plans found</td></tr>';
    return;
  }

  tbody.innerHTML = plans
    .map(
      (item) => `
        <tr>
            <td>
                <div class="equipment-name-cell">
                    <span class="equipment-icon-small">📋</span>
                    <span>${escapeHtml(item.name)}</span>
                </div>
            </td>
            <td>${escapeHtml(item.facility_name || 'N/A')}</td>
            <td>${escapeHtml(item.duration)}</td>
            <td class="text-right">₱${parseFloat(item.price).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td class="table-actions">
                <div class="action-buttons">
                    <button class="btn-sm btn-view" onclick="viewPlan(${item.id})" title="View Details">👁️</button>
                    <button class="btn-sm btn-edit" onclick="editPlan(${item.id})" title="Edit">✏️</button>
                    <button class="btn-sm btn-delete" onclick="deletePlan(${item.id})" title="Delete">🗑️</button>
                </div>
            </td>
        </tr>
    `
    )
    .join("");
}

// Load facilities for dropdown
async function loadFacilities() {
  try {
    const response = await fetch("/admin/plans/getFacilities", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      facilitiesData = result.data;
      populateFacilityDropdowns();
    }
  } catch (error) {
    console.error("Error loading facilities:", error);
  }
}

// Populate facility dropdowns
function populateFacilityDropdowns() {
  const dropdowns = [
    document.getElementById("planFacility"),
    document.getElementById("editPlanFacility"),
    document.getElementById("facilityFilter"),
  ];

  dropdowns.forEach((dropdown) => {
    if (dropdown) {
      const options = facilitiesData
        .map((facility) => `<option value="${facility.id}">${escapeHtml(facility.name)}</option>`)
        .join("");

      if (dropdown.id === "facilityFilter") {
        dropdown.innerHTML = '<option value="">All Facilities</option>' + options;
      } else {
        dropdown.innerHTML = '<option value="">Select Facility</option>' + options;
      }
    }
  });
}

// Load equipment options
async function loadEquipmentOptions() {
  try {
    const response = await fetch("/admin/plans/getEquipmentList", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      equipmentData = result.data;
    }
  } catch (error) {
    console.error("Error loading equipment:", error);
  }
}

// Add feature field
function addFeature() {
  const featuresList = document.getElementById("featuresList");
  const featureItem = document.createElement("div");
  featureItem.className = "feature-item";
  featureItem.innerHTML = `
        <input type="text" class="feature-input" placeholder="Feature description">
        <select class="feature-type">
            <option value="amenity">Amenity</option>
            <option value="service">Service</option>
            <option value="access">Access</option>
            <option value="description">Description</option>
        </select>
        <button type="button" class="btn-sm btn-danger" onclick="removeFeature(this)">✕</button>
    `;
  featuresList.appendChild(featureItem);
}

// Add feature field for edit
function addEditFeature() {
  const featuresList = document.getElementById("editFeaturesList");
  const featureItem = document.createElement("div");
  featureItem.className = "feature-item";
  featureItem.innerHTML = `
        <input type="text" class="feature-input" placeholder="Feature description">
        <select class="feature-type">
            <option value="amenity">Amenity</option>
            <option value="service">Service</option>
            <option value="access">Access</option>
            <option value="description">Description</option>
        </select>
        <button type="button" class="btn-sm btn-danger" onclick="removeFeature(this)">✕</button>
    `;
  featuresList.appendChild(featureItem);
}

// Remove feature
function removeFeature(button) {
  button.parentElement.remove();
}

// Add equipment field
function addEquipment() {
  const equipmentList = document.getElementById("equipmentList");
  const equipmentItem = document.createElement("div");
  equipmentItem.className = "equipment-item";

  const equipmentOptions = equipmentData
    .map((eq) => `<option value="${eq.id}">${escapeHtml(eq.name)}</option>`)
    .join("");

  equipmentItem.innerHTML = `
        <select class="equipment-select">
            <option value="">Select Equipment</option>
            ${equipmentOptions}
        </select>
        <input type="number" class="equipment-quantity" placeholder="Quantity" min="1" value="1">
        <label class="checkbox-label">
            <input type="checkbox" class="equipment-mandatory" checked>
            Mandatory
        </label>
        <button type="button" class="btn-sm btn-danger" onclick="removeEquipment(this)">✕</button>
    `;
  equipmentList.appendChild(equipmentItem);
}

// Add equipment field for edit
function addEditEquipment() {
  const equipmentList = document.getElementById("editEquipmentList");
  const equipmentItem = document.createElement("div");
  equipmentItem.className = "equipment-item";

  const equipmentOptions = equipmentData
    .map((eq) => `<option value="${eq.id}">${escapeHtml(eq.name)}</option>`)
    .join("");

  equipmentItem.innerHTML = `
        <select class="equipment-select">
            <option value="">Select Equipment</option>
            ${equipmentOptions}
        </select>
        <input type="number" class="equipment-quantity" placeholder="Quantity" min="1" value="1">
        <label class="checkbox-label">
            <input type="checkbox" class="equipment-mandatory" checked>
            Mandatory
        </label>
        <button type="button" class="btn-sm btn-danger" onclick="removeEquipment(this)">✕</button>
    `;
  equipmentList.appendChild(equipmentItem);
}

// Remove equipment
function removeEquipment(button) {
  button.parentElement.remove();
}

// Filter plans
function filterPlans() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();
  const facilityFilter = document.getElementById("facilityFilter").value;

  let filteredPlans = plansData.filter((item) => {
    const matchesSearch =
      item.name.toLowerCase().includes(searchTerm) ||
      (item.facility_name && item.facility_name.toLowerCase().includes(searchTerm));
    const matchesFacility = !facilityFilter || item.facility_id == facilityFilter;
    return matchesSearch && matchesFacility;
  });

  displayPlansTable(filteredPlans);
}

// Handle add plan
async function handleAddPlan(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  const planData = {
    facility_id: formData.get("facility_id"),
    plan_key: formData.get("plan_key"),
    name: formData.get("name"),
    duration: formData.get("duration"),
    price: formData.get("price"),
    features: [],
    equipment: [],
  };

  // Collect features
  document.querySelectorAll("#featuresList .feature-item").forEach((item) => {
    const featureInput = item.querySelector(".feature-input").value.trim();
    const featureType = item.querySelector(".feature-type").value;
    if (featureInput) {
      planData.features.push({
        feature: featureInput,
        feature_type: featureType,
        is_physical: 0,
      });
    }
  });

  // Collect equipment
  document.querySelectorAll("#equipmentList .equipment-item").forEach((item) => {
    const equipmentId = item.querySelector(".equipment-select").value;
    const quantity = item.querySelector(".equipment-quantity").value;
    const mandatory = item.querySelector(".equipment-mandatory").checked ? 1 : 0;
    if (equipmentId) {
      planData.equipment.push({
        equipment_id: equipmentId,
        quantity_included: quantity,
        is_mandatory: mandatory,
      });
    }
  });

  showLoading(true);

  try {
    const response = await fetch("/admin/plans/addPlan", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(planData),
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Plan added successfully", "success");
      closeAddPlanModal();
      loadPlans();
    } else {
      showNotification("Error: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error adding plan:", error);
    showNotification("Failed to add plan", "error");
  } finally {
    showLoading(false);
  }
}

// Handle edit plan
async function handleEditPlan(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  const planData = {
    id: formData.get("id"),
    facility_id: formData.get("facility_id"),
    plan_key: formData.get("plan_key"),
    name: formData.get("name"),
    duration: formData.get("duration"),
    price: formData.get("price"),
    features: [],
    equipment: [],
  };

  // Collect features
  document.querySelectorAll("#editFeaturesList .feature-item").forEach((item) => {
    const featureInput = item.querySelector(".feature-input").value.trim();
    const featureType = item.querySelector(".feature-type").value;
    if (featureInput) {
      planData.features.push({
        feature: featureInput,
        feature_type: featureType,
        is_physical: 0,
      });
    }
  });

  // Collect equipment
  document.querySelectorAll("#editEquipmentList .equipment-item").forEach((item) => {
    const equipmentId = item.querySelector(".equipment-select").value;
    const quantity = item.querySelector(".equipment-quantity").value;
    const mandatory = item.querySelector(".equipment-mandatory").checked ? 1 : 0;
    if (equipmentId) {
      planData.equipment.push({
        equipment_id: equipmentId,
        quantity_included: quantity,
        is_mandatory: mandatory,
      });
    }
  });

  showLoading(true);

  try {
    const response = await fetch("/admin/plans/updatePlan", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(planData),
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Plan updated successfully", "success");
      closeEditPlanModal();
      loadPlans();
    } else {
      showNotification("Error: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error updating plan:", error);
    showNotification("Failed to update plan", "error");
  } finally {
    showLoading(false);
  }
}

// View plan
async function viewPlan(id) {
  showLoading(true);

  try {
    const response = await fetch(`/admin/plans/getPlanDetails/${id}`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      const plan = result.data;
      const content = document.getElementById("planDetailsContent");

      let featuresHTML = "";
      if (plan.features && plan.features.length > 0) {
        featuresHTML = plan.features
          .map(
            (f) => `
                    <li><strong>${escapeHtml(f.feature_type)}:</strong> ${escapeHtml(f.feature)}</li>
                `
          )
          .join("");
      } else {
        featuresHTML = "<li>No features</li>";
      }

      let equipmentHTML = "";
      if (plan.equipment && plan.equipment.length > 0) {
        equipmentHTML = plan.equipment
          .map(
            (e) => `
                    <li>${escapeHtml(e.equipment_name)} - Qty: ${e.quantity_included} ${e.is_mandatory ? "(Mandatory)" : "(Optional)"}</li>
                `
          )
          .join("");
      } else {
        equipmentHTML = "<li>No equipment</li>";
      }

      content.innerHTML = `
                <div class="plan-details-grid">
                    <div class="detail-item">
                        <label>Facility:</label>
                        <span>${escapeHtml(plan.facility_name)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Plan Key:</label>
                        <span>${escapeHtml(plan.plan_key)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Plan Name:</label>
                        <span>${escapeHtml(plan.name)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Duration:</label>
                        <span>${escapeHtml(plan.duration)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Price:</label>
                        <span>₱${parseFloat(plan.price).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="detail-item full-width">
                        <label>Features:</label>
                        <ul>${featuresHTML}</ul>
                    </div>
                    <div class="detail-item full-width">
                        <label>Included Equipment:</label>
                        <ul>${equipmentHTML}</ul>
                    </div>
                </div>
            `;

      openViewPlanModal();
    } else {
      showNotification("Error loading plan details", "error");
    }
  } catch (error) {
    console.error("Error:", error);
    showNotification("Failed to load plan details", "error");
  } finally {
    showLoading(false);
  }
}

// Edit plan
async function editPlan(id) {
  showLoading(true);

  try {
    const response = await fetch(`/admin/plans/getPlanDetails/${id}`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      const plan = result.data;

      document.getElementById("editPlanId").value = plan.id;
      document.getElementById("editPlanFacility").value = plan.facility_id;
      document.getElementById("editPlanKey").value = plan.plan_key;
      document.getElementById("editPlanName").value = plan.name;
      document.getElementById("editPlanDuration").value = plan.duration;
      document.getElementById("editPlanPrice").value = plan.price;

      // Populate features
      const featuresList = document.getElementById("editFeaturesList");
      featuresList.innerHTML = "";
      if (plan.features && plan.features.length > 0) {
        plan.features.forEach((feature) => {
          const featureItem = document.createElement("div");
          featureItem.className = "feature-item";
          featureItem.innerHTML = `
                        <input type="text" class="feature-input" placeholder="Feature description" value="${escapeHtml(feature.feature)}">
                        <select class="feature-type">
                            <option value="amenity" ${feature.feature_type === "amenity" ? "selected" : ""}>Amenity</option>
                            <option value="service" ${feature.feature_type === "service" ? "selected" : ""}>Service</option>
                            <option value="access" ${feature.feature_type === "access" ? "selected" : ""}>Access</option>
                            <option value="description" ${feature.feature_type === "description" ? "selected" : ""}>Description</option>
                        </select>
                        <button type="button" class="btn-sm btn-danger" onclick="removeFeature(this)">✕</button>
                    `;
          featuresList.appendChild(featureItem);
        });
      }

      // Populate equipment
      const equipmentList = document.getElementById("editEquipmentList");
      equipmentList.innerHTML = "";
      if (plan.equipment && plan.equipment.length > 0) {
        plan.equipment.forEach((equip) => {
          const equipmentItem = document.createElement("div");
          equipmentItem.className = "equipment-item";

          const equipmentOptions = equipmentData
            .map((eq) => `<option value="${eq.id}" ${eq.id == equip.equipment_id ? "selected" : ""}>${escapeHtml(eq.name)}</option>`)
            .join("");

          equipmentItem.innerHTML = `
                        <select class="equipment-select">
                            <option value="">Select Equipment</option>
                            ${equipmentOptions}
                        </select>
                        <input type="number" class="equipment-quantity" placeholder="Quantity" min="1" value="${equip.quantity_included}">
                        <label class="checkbox-label">
                            <input type="checkbox" class="equipment-mandatory" ${equip.is_mandatory ? "checked" : ""}>
                            Mandatory
                        </label>
                        <button type="button" class="btn-sm btn-danger" onclick="removeEquipment(this)">✕</button>
                    `;
          equipmentList.appendChild(equipmentItem);
        });
      }

      openEditPlanModal();
    } else {
      showNotification("Error loading plan details", "error");
    }
  } catch (error) {
    console.error("Error:", error);
    showNotification("Failed to load plan details", "error");
  } finally {
    showLoading(false);
  }
}

// Delete plan
function deletePlan(id) {
  currentPlanId = id;
  openDeletePlanModal();
}

// Confirm delete plan
async function confirmDeletePlan() {
  showLoading(true);

  try {
    const response = await fetch(`/admin/plans/deletePlan/${currentPlanId}`, {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Plan deleted successfully", "success");
      closeDeletePlanModal();
      loadPlans();
    } else {
      showNotification("Error: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error deleting plan:", error);
    showNotification("Failed to delete plan", "error");
  } finally {
    showLoading(false);
  }
}

// ============= ADDITIONAL SERVICES =============

// Load services
async function loadServices() {
  showLoading(true);

  try {
    const response = await fetch("/admin/plans/getAddons", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      servicesData = result.data;
      displayServicesTable(servicesData);
    } else {
      showNotification("Error loading services: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error loading services:", error);
    showNotification("Failed to load services data", "error");
  } finally {
    showLoading(false);
  }
}

// Display services table
function displayServicesTable(services) {
  const tbody = document.getElementById("servicesTableBody");

  if (services.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center">No services found</td></tr>';
    return;
  }

  tbody.innerHTML = services
    .map(
      (item) => `
        <tr>
            <td>
                <div class="equipment-name-cell">
                    <span class="equipment-icon-small">➕</span>
                    <span>${escapeHtml(item.name)}</span>
                </div>
            </td>
            <td>${escapeHtml(item.description || 'N/A')}</td>
            <td class="text-right">₱${parseFloat(item.price).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td class="table-actions">
                <div class="action-buttons">
                    <button class="btn-sm btn-edit" onclick="editService(${item.id})" title="Edit">✏️</button>
                    <button class="btn-sm btn-delete" onclick="deleteService(${item.id})" title="Delete">🗑️</button>
                </div>
            </td>
        </tr>
    `
    )
    .join("");
}

// Handle add service
async function handleAddService(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  const serviceData = {
    addon_key: formData.get("addon_key"),
    name: formData.get("name"),
    description: formData.get("description"),
    price: formData.get("price"),
  };

  showLoading(true);

  try {
    const response = await fetch("/admin/plans/addAddon", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(serviceData),
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Service added successfully", "success");
      closeAddServiceModal();
      loadServices();
    } else {
      showNotification("Error: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error adding service:", error);
    showNotification("Failed to add service", "error");
  } finally {
    showLoading(false);
  }
}

// Edit service
async function editService(id) {
  const service = servicesData.find((s) => s.id == id);
  if (service) {
    document.getElementById("editServiceId").value = service.id;
    document.getElementById("editServiceKey").value = service.addon_key;
    document.getElementById("editServiceName").value = service.name;
    document.getElementById("editServiceDescription").value = service.description || "";
    document.getElementById("editServicePrice").value = service.price;

    openEditServiceModal();
  }
}

// Handle edit service
async function handleEditService(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  const serviceData = {
    id: formData.get("id"),
    addon_key: formData.get("addon_key"),
    name: formData.get("name"),
    description: formData.get("description"),
    price: formData.get("price"),
  };

  showLoading(true);

  try {
    const response = await fetch("/admin/plans/updateAddon", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(serviceData),
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Service updated successfully", "success");
      closeEditServiceModal();
      loadServices();
    } else {
      showNotification("Error: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error updating service:", error);
    showNotification("Failed to update service", "error");
  } finally {
    showLoading(false);
  }
}

// Delete service
function deleteService(id) {
  currentServiceId = id;
  openDeleteServiceModal();
}

// Confirm delete service
async function confirmDeleteService() {
  showLoading(true);

  try {
    const response = await fetch(`/admin/plans/deleteAddon/${currentServiceId}`, {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Service deleted successfully", "success");
      closeDeleteServiceModal();
      loadServices();
    } else {
      showNotification("Error: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error deleting service:", error);
    showNotification("Failed to delete service", "error");
  } finally {
    showLoading(false);
  }
}

// ============= SETTINGS =============

// Load settings
async function loadSettings() {
  showLoading(true);

  try {
    const response = await fetch("/admin/plans/getSettings", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      document.getElementById("extendedHoursRate").value = result.data.extended_hours_rate;
      document.getElementById("overtimeRate").value = result.data.overtime_rate;
      document.getElementById("maintenanceFee").value = result.data.maintenance_fee;
    } else {
      showNotification("Error loading settings: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error loading settings:", error);
    showNotification("Failed to load settings data", "error");
  } finally {
    showLoading(false);
  }
}

// Handle update settings
async function handleUpdateSettings(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  const settingsData = {
    extended_hours_rate: formData.get("extended_hours_rate"),
    overtime_rate: formData.get("overtime_rate"),
    maintenance_fee: formData.get("maintenance_fee"),
  };

  showLoading(true);

  try {
    const response = await fetch("/admin/plans/updateSettings", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(settingsData),
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Settings updated successfully", "success");
    } else {
      showNotification("Error: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error updating settings:", error);
    showNotification("Failed to update settings", "error");
  } finally {
    showLoading(false);
  }
}

// ============= FACILITIES RATES =============

// Load facilities with rates
async function loadFacilitiesRates() {
  showLoading(true);

  try {
    const response = await fetch("/admin/plans/getFacilities", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (result.success) {
      displayFacilitiesTable(result.data);
    } else {
      showNotification("Error loading facilities: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error loading facilities:", error);
    showNotification("Failed to load facilities data", "error");
  } finally {
    showLoading(false);
  }
}

// Display facilities table
function displayFacilitiesTable(facilities) {
  const tbody = document.getElementById("facilitiesTableBody");

  if (facilities.length === 0) {
    tbody.innerHTML = '<tr><td colspan="3" class="text-center">No facilities found</td></tr>';
    return;
  }

  tbody.innerHTML = facilities
    .map(
      (item) => `
        <tr>
            <td>
                <div class="equipment-name-cell">
                    <span class="equipment-icon-small">${item.icon || '🏢'}</span>
                    <span>${escapeHtml(item.name)}</span>
                </div>
            </td>
            <td class="text-right">₱${parseFloat(item.additional_hours_rate || 500).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td class="table-actions">
                <div class="action-buttons">
                    <button class="btn-sm btn-edit" onclick="editFacilityRate(${item.id}, '${escapeHtml(item.name)}', ${item.additional_hours_rate || 500})" title="Edit Rate">✏️</button>
                </div>
            </td>
        </tr>
    `
    )
    .join("");
}

// Edit facility rate
function editFacilityRate(id, name, currentRate) {
  document.getElementById("editFacilityId").value = id;
  document.getElementById("editFacilityName").value = name;
  document.getElementById("editAdditionalHoursRate").value = currentRate;
  openEditFacilityRateModal();
}

// Handle edit facility rate
async function handleEditFacilityRate(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  const rateData = {
    facility_id: formData.get("facility_id"),
    additional_hours_rate: formData.get("additional_hours_rate"),
  };

  showLoading(true);

  try {
    const response = await fetch("/admin/plans/updateFacilityRate", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(rateData),
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Facility rate updated successfully", "success");
      closeEditFacilityRateModal();
      loadFacilitiesRates();
    } else {
      showNotification("Error: " + result.message, "error");
    }
  } catch (error) {
    console.error("Error updating facility rate:", error);
    showNotification("Failed to update facility rate", "error");
  } finally {
    showLoading(false);
  }
}

// ============= MODAL FUNCTIONS =============

function openAddPlanModal() {
  document.getElementById("addPlanModal").style.display = "block";
  document.getElementById("addPlanForm").reset();
  document.getElementById("featuresList").innerHTML = `
        <div class="feature-item">
            <input type="text" class="feature-input" placeholder="Feature description">
            <select class="feature-type">
                <option value="amenity">Amenity</option>
                <option value="service">Service</option>
                <option value="access">Access</option>
                <option value="description">Description</option>
            </select>
            <button type="button" class="btn-sm btn-danger" onclick="removeFeature(this)">✕</button>
        </div>
    `;
  document.getElementById("equipmentList").innerHTML = "";
}

function closeAddPlanModal() {
  document.getElementById("addPlanModal").style.display = "none";
}

function openEditPlanModal() {
  document.getElementById("editPlanModal").style.display = "block";
}

function closeEditPlanModal() {
  document.getElementById("editPlanModal").style.display = "none";
}

function openViewPlanModal() {
  document.getElementById("viewPlanModal").style.display = "block";
}

function closeViewPlanModal() {
  document.getElementById("viewPlanModal").style.display = "none";
}

function openDeletePlanModal() {
  document.getElementById("deletePlanModal").style.display = "block";
}

function closeDeletePlanModal() {
  document.getElementById("deletePlanModal").style.display = "none";
  currentPlanId = null;
}

function openAddServiceModal() {
  document.getElementById("addServiceModal").style.display = "block";
  document.getElementById("addServiceForm").reset();
}

function closeAddServiceModal() {
  document.getElementById("addServiceModal").style.display = "none";
}

function openEditServiceModal() {
  document.getElementById("editServiceModal").style.display = "block";
}

function closeEditServiceModal() {
  document.getElementById("editServiceModal").style.display = "none";
}

function openDeleteServiceModal() {
  document.getElementById("deleteServiceModal").style.display = "block";
}

function closeDeleteServiceModal() {
  document.getElementById("deleteServiceModal").style.display = "none";
  currentServiceId = null;
}

function openEditFacilityRateModal() {
  document.getElementById("editFacilityRateModal").style.display = "block";
}

function closeEditFacilityRateModal() {
  document.getElementById("editFacilityRateModal").style.display = "none";
}

function closeAllModals() {
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.style.display = "none";
  });
}

// ============= UTILITY FUNCTIONS =============

function showLoading(show) {
  const overlay = document.getElementById("loadingOverlay");
  if (overlay) {
    overlay.style.display = show ? "flex" : "none";
  }
}

function showNotification(message, type = "info") {
  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.textContent = message;

  // Add to body
  document.body.appendChild(notification);

  // Show notification
  setTimeout(() => {
    notification.classList.add("show");
  }, 100);

  // Remove after 3 seconds
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => {
      notification.remove();
    }, 300);
  }, 3000);
}

function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

// Dropdown toggle for sidebar
function toggleDropdown(event) {
  event.preventDefault();
  const dropdown = event.target.closest(".dropdown");
  dropdown.classList.toggle("open");
}
