let users = [];
let currentPage = 1;
let usersPerPage = 10;
let filteredUsers = [];
let isLoading = false;
let selectedRole = "all";

// 🔹 Toast Notification System
function showToast(message, type = "success", title = "") {
  const toastContainer = document.getElementById("toastContainer");

  if (!toastContainer) {
    console.error("Toast container not found");
    return;
  }

  const toastId = "toast-" + Date.now();
  const icons = {
    success: "✓",
    error: "✕",
    warning: "⚠",
    info: "ℹ",
  };

  const titles = {
    success: title || "Success",
    error: title || "Error",
    warning: title || "Warning",
    info: title || "Info",
  };

  const toast = document.createElement("div");
  toast.className = `toast ${type}`;
  toast.id = toastId;
  toast.innerHTML = `
    <div class="toast-icon">${icons[type]}</div>
    <div class="toast-content">
      <div class="toast-title">${titles[type]}</div>
      <div class="toast-message">${message}</div>
    </div>
    <button class="toast-close" onclick="closeToast('${toastId}')">×</button>
  `;

  toastContainer.appendChild(toast);

  // Auto remove after 3 seconds
  setTimeout(() => {
    closeToast(toastId);
  }, 3000);
}

function closeToast(toastId) {
  const toast = document.getElementById(toastId);
  if (toast) {
    toast.style.animation = "fadeOut 0.3s ease-out";
    setTimeout(() => {
      toast.remove();
    }, 300);
  }
}

// 🔹 Loading indicator functions
function showLoading(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.style.opacity = "0.5";
    element.style.pointerEvents = "none";
  }
  isLoading = true;
}

function hideLoading(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.style.opacity = "1";
    element.style.pointerEvents = "auto";
  }
  isLoading = false;
}

function clearAllLoadingStates() {
  const elements = ["usersTableBody", "addModal", "editModal"];
  elements.forEach((elementId) => {
    const element = document.getElementById(elementId);
    if (element) {
      element.style.opacity = "1";
      element.style.pointerEvents = "auto";
    }
  });
  isLoading = false;
}

// 🔹 Calculate role statistics
function updateRoleStats() {
  const stats = {
    total: users.length,
    students: users.filter((u) => u.role === "student").length,
    facilitators: users.filter((u) => u.role === "facilitator").length,
    employee: users.filter((u) => u.role === "employee").length,
    admins: users.filter((u) => u.role === "admin").length,
  };

  document.getElementById("totalUsersCount").textContent = stats.total;
  document.getElementById("studentsCount").textContent = stats.students;
  document.getElementById("facilitatorsCount").textContent = stats.facilitators;
  document.getElementById("employeeCount").textContent = stats.employee;
  document.getElementById("adminsCount").textContent = stats.admins;
}

// 🔹 Load users from backend
async function loadUsers(showLoadingIndicator = true) {
  try {
    if (showLoadingIndicator) {
      showLoading("usersTableBody");
    }

    const response = await fetch("/admin/users/getUsers");

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    // Handle new response format with success flag
    if (data.success === false) {
      throw new Error(data.message || "Failed to load users");
    }

    // Get users array from response (new format) or use data directly (backward compatibility)
    users = data.users || data || [];
    filteredUsers = [...users];
    applyFilters();
    updateRoleStats();
    renderUsers();

    hideLoading("usersTableBody");
  } catch (err) {
    console.error("Failed to load users:", err);
    showToast("Could not fetch users. Please try again.", "error");
    hideLoading("usersTableBody");
  }
}

function renderUsers() {
  const tbody = document.getElementById("usersTableBody");
  const startIndex = (currentPage - 1) * usersPerPage;
  const endIndex = startIndex + usersPerPage;
  const pageUsers = filteredUsers.slice(startIndex, endIndex);

  tbody.innerHTML = "";

  if (pageUsers.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="5">
          <div class="empty-state">
            <div class="empty-state-icon">👥</div>
            <h3>No users found</h3>
            <p>Try adjusting your search or filter criteria</p>
          </div>
        </td>
      </tr>
    `;
    return;
  }

  pageUsers.forEach((user) => {
    const row = document.createElement("tr");
    const initials = user.full_name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase();
    const createdDate = new Date(user.created_at).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });

    row.innerHTML = `
      <td>
        <div class="user-info">
          <div class="user-avatar-small">${initials}</div>
          <div>
            <div class="user-name">${user.full_name}</div>
            <div class="user-email">${user.email}</div>
          </div>
        </div>
      </td>
      <td>${user.contact_number || "N/A"}</td>
      <td>
        <span class="status-badge ${user.role.toLowerCase()}">
          ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
        </span>
      </td>
      <td>${createdDate}</td>
      <td style="text-align: center;">
        <div class="action-buttons">
          <button class="action-btn view user-action-btn" data-action="view" data-user-id="${
            user.id
          }" data-user-name="${user.full_name}" title="View Details">
            👁️
          </button>
          <button class="action-btn edit user-action-btn" data-action="edit" data-user-id="${
            user.id
          }" title="Edit User">
            ✏️
          </button>
          <button class="action-btn delete user-action-btn" data-action="delete" data-user-id="${
            user.id
          }" data-user-name="${user.full_name}" title="Delete User">
            🗑️
          </button>
        </div>
      </td>
    `;
    tbody.appendChild(row);
  });

  updateTotalUsers();
  renderPagination();
}

function updateTotalUsers() {
  document.getElementById("totalUsers").textContent = filteredUsers.length;
}

function renderPagination() {
  const pagination = document.getElementById("pagination");
  const totalPages = Math.ceil(filteredUsers.length / usersPerPage);

  pagination.innerHTML = "";
  if (totalPages <= 1) return;

  for (let i = 1; i <= totalPages; i++) {
    const link = document.createElement("a");
    link.href = "#";
    link.textContent = i;
    link.className = i === currentPage ? "active" : "";
    link.onclick = (e) => {
      e.preventDefault();
      currentPage = i;
      renderUsers();
    };
    pagination.appendChild(link);
  }
}

// 🔹 Apply filters (search + role)
function applyFilters() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();

  filteredUsers = users.filter((user) => {
    const matchesSearch =
      user.full_name.toLowerCase().includes(searchTerm) ||
      user.email.toLowerCase().includes(searchTerm) ||
      (user.contact_number &&
        user.contact_number.toLowerCase().includes(searchTerm));

    const matchesRole = selectedRole === "all" || user.role === selectedRole;

    return matchesSearch && matchesRole;
  });

  currentPage = 1;
}

function filterUsers() {
  applyFilters();
  renderUsers();
}

function filterByRole() {
  selectedRole = document.getElementById("roleFilter").value;
  applyFilters();
  renderUsers();

  if (selectedRole !== "all") {
    showToast(`Filtered by ${selectedRole} role`, "info");
  }
}

function clearFilters() {
  document.getElementById("searchInput").value = "";
  document.getElementById("roleFilter").value = "all";
  selectedRole = "all";
  filteredUsers = [...users];
  currentPage = 1;
  renderUsers();
  showToast("Filters cleared", "info");
}

function toggleSidebar() {
  document.getElementById("sidebar").classList.toggle("active");
}

function openAddModal() {
  document.getElementById("addModal").style.display = "block";
}

async function openEditModal(userId) {
  try {
    showLoading("editModal");

    const response = await fetch(`/admin/users/view/${userId}`);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (!result.success) {
      showToast(result.message || "User not found", "error");
      hideLoading("editModal");
      return;
    }

    const user = result.data;

    if (!user) {
      showToast("User data not found", "error");
      hideLoading("editModal");
      return;
    }

    document.getElementById("editUserId").value = user.id;
    document.getElementById("editfull_name").value = user.full_name || "";
    document.getElementById("editEmail").value = user.email || "";
    document.getElementById("editContactNumber").value =
      user.contact_number || "";
    document.getElementById("editRole").value = user.role || "user";
    document.getElementById("editPassword").value = "";

    document.getElementById("editModal").style.display = "block";
    hideLoading("editModal");
  } catch (err) {
    console.error("Error in openEditModal:", err);
    showToast("Could not load user details: " + err.message, "error");
    hideLoading("editModal");
  }
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none";
}

async function addUser(event) {
  event.preventDefault();

  if (isLoading) return;

  const form = event.target;
  const submitButton = form.querySelector('button[type="submit"]');
  const originalButtonText = submitButton.innerHTML;

  try {
    submitButton.innerHTML = "<span>⏳</span> Adding...";
    submitButton.disabled = true;
    isLoading = true;

    // Get form data
    const formData = new FormData(form);
    const data = {
      full_name: formData.get("full_name"),
      email: formData.get("email"),
      password: formData.get("password"),
      contact_number: formData.get("contact_number") || null,
      role: formData.get("role"),
    };

    const response = await fetch("/admin/users/add", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    });
    const result = await response.json();

    if (!response.ok || !result.success) {
      showToast(
        result.message || result.error || "Failed to add user",
        "error"
      );
    } else {
      closeModal("addModal");
      form.reset();
      showToast(result.message || "User added successfully!", "success");
      await loadUsers(true);
    }
  } catch (err) {
    console.error(err);
    showToast("Failed to add user. Please try again.", "error");
  } finally {
    submitButton.innerHTML = originalButtonText;
    submitButton.disabled = false;
    isLoading = false;
    clearAllLoadingStates();
  }
}

async function updateUser(event) {
  event.preventDefault();

  if (isLoading) return;

  const formData = new FormData(event.target);
  const userId = formData.get("id");
  const submitButton = event.target.querySelector('button[type="submit"]');
  const originalButtonText = submitButton.innerHTML;

  if (!userId) {
    showToast("User ID is missing. Please try again.", "error");
    return;
  }

  const dataToSend = {};
  formData.forEach((value, key) => {
    if (value && value.trim() !== "") {
      dataToSend[key] = value;
    }
  });

  try {
    submitButton.innerHTML = "<span>⏳</span> Updating...";
    submitButton.disabled = true;
    isLoading = true;

    const response = await fetch(`/admin/users/update/${userId}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(dataToSend),
    });

    const responseText = await response.text();
    const result = JSON.parse(responseText);

    if (!response.ok || !result.success) {
      let errorMessage =
        result.message || result.error || "Failed to update user";
      if (result.details) {
        const validationErrors = Object.values(result.details).join(", ");
        errorMessage += ": " + validationErrors;
      }
      showToast(errorMessage, "error");
    } else {
      closeModal("editModal");
      showToast(result.message || "User updated successfully!", "success");
      await loadUsers(true);
    }
  } catch (err) {
    console.error("Network or parsing error:", err);
    showToast(
      "Failed to update user. Please check your connection and try again.",
      "error"
    );
  } finally {
    submitButton.innerHTML = originalButtonText;
    submitButton.disabled = false;
    isLoading = false;
    clearAllLoadingStates();
  }
}

async function deleteUser(userId, userName) {
  // Create custom confirmation modal
  const modal = document.createElement("div");
  modal.className = "modal";
  modal.id = "deleteConfirmModal";
  modal.style.display = "block";
  modal.innerHTML = `
    <div class="delete-modal-content">
      <div class="delete-modal-header">
        <div class="delete-modal-icon">⚠️</div>
        <h2>Confirm Deletion</h2>
      </div>
      <div class="delete-modal-body">
        <div class="user-delete-info">
          <strong>Are you sure you want to delete "${userName}"?</strong>
        </div>
        <div class="delete-warning">
          <div class="delete-warning-title">
            <span>⚠️</span>
            <span>Warning: This action cannot be undone</span>
          </div>
          <div class="delete-warning-text">
            Deleting this user will permanently remove:
            <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
              <li>User account and credentials</li>
              <li>All associated bookings and reservations</li>
              <li>Event registrations and attendance records</li>
              <li>Any related user data from the system</li>
            </ul>
          </div>
        </div>
      </div>
      <div class="delete-modal-footer">
        <button class="btn-cancel" id="cancelDeleteBtn">
          <span>✕</span> Cancel
        </button>
        <button class="btn-delete-confirm" id="confirmDeleteBtn">
          <span>🗑️</span> Yes, Delete User
        </button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  // Handle cancel button
  document.getElementById("cancelDeleteBtn").onclick = () => {
    modal.remove();
  };

  // Handle confirm button
  document.getElementById("confirmDeleteBtn").onclick = async () => {
    if (isLoading) return;

    const confirmBtn = document.getElementById("confirmDeleteBtn");
    const cancelBtn = document.getElementById("cancelDeleteBtn");
    const originalText = confirmBtn.innerHTML;

    try {
      confirmBtn.innerHTML = "<span>⏳</span> Deleting...";
      confirmBtn.disabled = true;
      cancelBtn.disabled = true;
      isLoading = true;

      const response = await fetch(`/admin/users/delete/${userId}`, {
        method: "DELETE",
      });
      const result = await response.json();

      if (!response.ok || !result.success) {
        showToast(
          result.message || result.error || "Failed to delete user",
          "error"
        );
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
        cancelBtn.disabled = false;
        isLoading = false;
      } else {
        modal.remove();
        showToast(
          result.message ||
            "User and all associated records deleted successfully!",
          "success"
        );
        await loadUsers(true);
      }
    } catch (err) {
      console.error(err);
      showToast("Failed to delete user. Please try again.", "error");
      confirmBtn.innerHTML = originalText;
      confirmBtn.disabled = false;
      cancelBtn.disabled = false;
      isLoading = false;
    } finally {
      clearAllLoadingStates();
    }
  };

  // Close modal when clicking outside
  modal.onclick = function (event) {
    if (event.target === modal) {
      modal.remove();
    }
  };
}

async function viewUser(userId) {
  if (isLoading) return;

  try {
    const response = await fetch(`/admin/users/view/${userId}`);
    const result = await response.json();

    if (!result.success || !result.data) {
      showToast("User not found", "error");
      return;
    }

    const user = result.data;
    const createdDate = new Date(user.created_at).toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });

    const viewContent = `
      <div style="padding: 20px;">
        <div style="margin-bottom: 20px;">
          <strong style="color: #666; display: block; margin-bottom: 5px;">Full Name</strong>
          <div style="color: #333; font-size: 16px;">${user.full_name}</div>
        </div>
        <div style="margin-bottom: 20px;">
          <strong style="color: #666; display: block; margin-bottom: 5px;">Email</strong>
          <div style="color: #333; font-size: 16px;">${user.email}</div>
        </div>
        <div style="margin-bottom: 20px;">
          <strong style="color: #666; display: block; margin-bottom: 5px;">Contact Number</strong>
          <div style="color: #333; font-size: 16px;">${
            user.contact_number || "Not provided"
          }</div>
        </div>
        <div style="margin-bottom: 20px;">
          <strong style="color: #666; display: block; margin-bottom: 5px;">Role</strong>
          <span class="status-badge ${user.role.toLowerCase()}">${
      user.role.charAt(0).toUpperCase() + user.role.slice(1)
    }</span>
        </div>
        <div style="margin-bottom: 20px;">
          <strong style="color: #666; display: block; margin-bottom: 5px;">Created At</strong>
          <div style="color: #333; font-size: 16px;">${createdDate}</div>
        </div>
      </div>
    `;

    const modal = document.createElement("div");
    modal.className = "modal";
    modal.style.display = "block";
    modal.innerHTML = `
      <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
          <h2>User Details</h2>
          <button class="close" onclick="this.closest('.modal').remove()">×</button>
        </div>
        <div class="modal-body">
          ${viewContent}
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" onclick="this.closest('.modal').remove()">Close</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    modal.onclick = function (event) {
      if (event.target === modal) {
        modal.remove();
      }
    };
  } catch (err) {
    console.error(err);
    showToast("Failed to fetch user details.", "error");
  }
}

window.onclick = function (event) {
  if (event.target.classList.contains("modal")) {
    if (event.target.id === "addModal" || event.target.id === "editModal") {
      event.target.style.display = "none";
    }
  }
};

function initializeEventDelegation() {
  const tbody = document.getElementById("usersTableBody");

  if (tbody) {
    tbody.removeEventListener("click", handleTableClick);
    tbody.addEventListener("click", handleTableClick);
  }
}

function handleTableClick(event) {
  if (event.target.classList.contains("user-action-btn")) {
    event.preventDefault();
    event.stopPropagation();

    const action = event.target.dataset.action;
    const userId = event.target.dataset.userId;
    const userName = event.target.dataset.userName;

    if (!userId || userId === "undefined") {
      showToast(
        "Invalid user ID. Please refresh the page and try again.",
        "error"
      );
      return;
    }

    switch (action) {
      case "edit":
        openEditModal(parseInt(userId));
        break;
      case "delete":
        deleteUser(parseInt(userId), userName);
        break;
      case "view":
        viewUser(parseInt(userId));
        break;
      default:
        console.error("Unknown action:", action);
    }
  }
}

window.onload = function () {
  initializeEventDelegation();
  loadUsers();

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      const modals = ["addModal", "editModal"];
      modals.forEach((modalId) => {
        const modal = document.getElementById(modalId);
        if (modal && modal.style.display === "block") {
          closeModal(modalId);
        }
      });

      // Close delete confirmation modal
      const deleteModal = document.getElementById("deleteConfirmModal");
      if (deleteModal) {
        deleteModal.remove();
      }

      const viewModals = document.querySelectorAll(
        ".modal:not(#addModal):not(#editModal):not(#deleteConfirmModal)"
      );
      viewModals.forEach((modal) => modal.remove());

      clearAllLoadingStates();
    }
  });

  setInterval(() => {
    if (!isLoading) {
      clearAllLoadingStates();
    }
  }, 5000);
};

document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.querySelector(".toggle-btn");
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (toggleBtn && sidebar && mainContent) {
    toggleBtn.addEventListener("click", function () {
      sidebar.classList.toggle("collapsed");
      mainContent.classList.toggle("expanded");
    });

    document.addEventListener("click", function (event) {
      const isMobile = window.innerWidth <= 768;
      const clickedOutside =
        !sidebar.contains(event.target) && !toggleBtn.contains(event.target);

      if (
        isMobile &&
        clickedOutside &&
        sidebar.classList.contains("collapsed") === false
      ) {
        sidebar.classList.add("collapsed");
        mainContent.classList.add("expanded");
      }
    });
  }

  const dropdownToggles = document.querySelectorAll(".dropdown-toggle");
  dropdownToggles.forEach((toggle) => {
    toggle.addEventListener("click", function (e) {
      e.preventDefault();
      const dropdown = this.closest(".dropdown");
      document.querySelectorAll(".dropdown").forEach((otherDropdown) => {
        if (otherDropdown !== dropdown) {
          otherDropdown.classList.remove("open");
        }
      });
      dropdown.classList.toggle("open");
    });
  });

  document.addEventListener("click", function (e) {
    if (!e.target.closest(".dropdown")) {
      document.querySelectorAll(".dropdown").forEach((dropdown) => {
        dropdown.classList.remove("open");
      });
    }
  });
});
