let isLoading = false;

document.addEventListener("DOMContentLoaded", function () {
  loadUserProfile();
});

// Load user profile data
// Load user profile data
async function loadUserProfile() {
  const userId = document.getElementById("userId").value;
  
  // Debug: Log the userId to make sure it's valid
  console.log("Loading profile for user ID:", userId);
  
  if (!userId || userId === 'null' || userId === 'undefined') {
    showAlert("error", "User ID not found. Please log in again.");
    setTimeout(() => {
      window.location.href = '/login';
    }, 2000);
    return;
  }

  try {
    // Use absolute URL path
const url = `/api/profile/${userId}`;
console.log("Fetching from:", url);

const response = await fetch(url, {
  credentials: "include", // Include cookies/session
  headers: {
    Accept: "application/json",
  },
});
    
    // Log response status
    console.log("Response status:", response.status);
    
    // Check if response is ok
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    // Check content type
    const contentType = response.headers.get("content-type");
    console.log("Content-Type:", contentType);
    
    if (!contentType || !contentType.includes("application/json")) {
      const text = await response.text();
      console.error("Received non-JSON response:", text.substring(0, 200));
      throw new Error("Server returned HTML instead of JSON. Check your route configuration.");
    }
    
    const data = await response.json();
    console.log("Profile data received:", data);

    if (data.success) {
      const user = data.user;

      // Fill form fields
      document.getElementById("fullName").value = user.full_name || "";
      document.getElementById("email").value = user.email || "";
      document.getElementById("contactNumber").value = user.contact_number || "";

      // Fill account info
      document.getElementById("memberSince").textContent = formatDate(user.created_at);
      document.getElementById("lastUpdated").textContent = formatDate(user.updated_at);
      
      console.log("Profile loaded successfully");
    } else {
      showAlert("error", data.message || "Failed to load profile");
    }
  } catch (error) {
    console.error("Error loading profile:", error);
    showAlert("error", "Failed to load profile data: " + error.message);
  }
}

// Handle profile form submission
document
  .getElementById("profileForm")
  .addEventListener("submit", async function (e) {
    e.preventDefault();

    if (isLoading) return;

    const userId = document.getElementById("userId").value;
    const saveBtn = document.getElementById("saveBtn");
    const originalText = saveBtn.innerHTML;

    const formData = {
      full_name: document.getElementById("fullName").value,
      email: document.getElementById("email").value,
      contact_number: document.getElementById("contactNumber").value,
    };

    try {
      isLoading = true;
      saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
      saveBtn.disabled = true;

const response = await fetch(`/api/profile/${userId}`, {
  method: "PUT",
  credentials: "include",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  body: JSON.stringify(formData),
});

      const data = await response.json();

      if (data.success) {
        showAlert("success", data.message || "Profile updated successfully!");

        // Update session name if changed
        if (formData.full_name) {
          updateNavbarName(formData.full_name);
        }

        // Reload profile data
        await loadUserProfile();
      } else {
        showAlert("error", data.message || "Failed to update profile");
      }
    } catch (error) {
      console.error("Error updating profile:", error);
      showAlert("error", "Failed to update profile");
    } finally {
      isLoading = false;
      saveBtn.innerHTML = originalText;
      saveBtn.disabled = false;
    }
  });

// Handle password change form submission
document
  .getElementById("passwordForm")
  .addEventListener("submit", async function (e) {
    e.preventDefault();

    if (isLoading) return;

    const userId = document.getElementById("userId").value;
    const changePasswordBtn = document.getElementById("changePasswordBtn");
    const originalText = changePasswordBtn.innerHTML;

    const currentPassword = document.getElementById("currentPassword").value;
    const newPassword = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    // Validate passwords match
    if (newPassword !== confirmPassword) {
      showAlert("error", "New passwords do not match!");
      return;
    }

    // Validate password length
    if (newPassword.length < 6) {
      showAlert("error", "Password must be at least 6 characters long!");
      return;
    }

    const passwordData = {
      current_password: currentPassword,
      new_password: newPassword,
    };

    try {
      isLoading = true;
      changePasswordBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Changing...';
      changePasswordBtn.disabled = true;

const response = await fetch(`/api/profile/change-password/${userId}`, {
  method: "POST",
  credentials: "include",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  body: JSON.stringify(passwordData),
});

      const data = await response.json();

      if (data.success) {
        showAlert("success", data.message || "Password changed successfully!");
        // Clear password form
        document.getElementById("passwordForm").reset();
      } else {
        showAlert("error", data.message || "Failed to change password");
      }
    } catch (error) {
      console.error("Error changing password:", error);
      showAlert("error", "Failed to change password");
    } finally {
      isLoading = false;
      changePasswordBtn.innerHTML = originalText;
      changePasswordBtn.disabled = false;
    }
  });

// Show alert message
function showAlert(type, message) {
  const alertContainer = document.getElementById("alertContainer");
  const alertClass = type === "success" ? "alert-success" : "alert-danger";
  const icon = type === "success" ? "fa-check-circle" : "fa-exclamation-circle";
  const uniqueId = "alert-" + Date.now();

  const alertHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert" id="${uniqueId}">
            <i class="fas ${icon}"></i>
            <div>${message}</div>
            <button type="button" class="btn-close" onclick="dismissAlert('${uniqueId}')"></button>
        </div>
    `;

  alertContainer.innerHTML = alertHTML;
  window.scrollTo({ top: 0, behavior: "smooth" });

  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    dismissAlert(uniqueId);
  }, 5000);
}

// Dismiss alert with animation
function dismissAlert(alertId) {
  const alert = document.getElementById(alertId);
  if (alert) {
    alert.style.animation = "slideDown 0.4s ease-out reverse";
    setTimeout(() => {
      alert.remove();
    }, 400);
  }
}
// Format role for display
function formatRole(role) {
  const roles = {
    student: "Student",
    facilitator: "Facilitator",
    user: "User",
    admin: "Administrator",
  };
  return roles[role] || role;
}

// Format date
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

// Update navbar name
function updateNavbarName(name) {
  const navbarDropdown = document.getElementById("navbarDropdown");
  if (navbarDropdown) {
    const icon = '<i class="fas fa-user-circle"></i>';
    navbarDropdown.innerHTML = `${icon} ${name}`;
  }
}
document.getElementById('confirmDelete')?.addEventListener('change', function() {
    document.getElementById('confirmDeleteBtn').disabled = !this.checked;
});

// Handle account deletion
document.getElementById('confirmDeleteBtn')?.addEventListener('click', async function() {
    const deleteOption = document.querySelector('input[name="deleteOption"]:checked').value;
    const password = document.getElementById('deletePassword').value;
    const userId = document.getElementById('userId').value;
    
    if (!password) {
        showAlert('Please enter your password', 'danger');
        return;
    }
    
    // Show loading state
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    
    try {
const response = await fetch(`/api/profile/${userId}`, {
  method: "DELETE",
  credentials: "include",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  body: JSON.stringify({
    password: password,
    delete_option: deleteOption,
  }),
});
        
        const result = await response.json();
        
        if (result.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('deleteAccountModal')).hide();
            
            // Show success message
            showAlert(result.message, 'success');
            
            // Redirect to home after 2 seconds
            setTimeout(() => {
                window.location.href = '/';
            }, 2000);
        } else {
            showAlert(result.message, 'danger');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Account';
        }
    } catch (error) {
        console.error('Delete error:', error);
        showAlert('An error occurred while deleting account', 'danger');
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Account';
    }
});

// Helper function for alerts
function showAlert(message, type) {
    const alertDiv = document.getElementById('alertContainer');
    alertDiv.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}



document.getElementById('confirmDeleteBtn')?.addEventListener('click', async function () {
    const deleteOption = document.querySelector('input[name="deleteOption"]:checked').value;
    const password = document.getElementById('deletePassword').value;
    const userId = document.getElementById('userId').value;

    if (!password) {
        showAlert('error', 'Please enter your password');
        return;
    }

    // Show loading state
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

    try {
        const response = await fetch(`/user/profile/delete/${userId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                password: password,
                delete_option: deleteOption,
            }),
        });

        const result = await response.json();

        if (result.success) {
            // Close modal
            const modalElement = document.getElementById('deleteAccountModal');
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide();
            }

            // Show success message
            showAlert('success', result.message);

            // Redirect to home after 2 seconds
            setTimeout(() => {
                window.location.href = '/';
            }, 2000);
        } else {
            showAlert('error', result.message);
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Account';
        }
    } catch (error) {
        console.error('Delete error:', error);
        showAlert('error', 'An error occurred while deleting account');
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Account';
    }
});