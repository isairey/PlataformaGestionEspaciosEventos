// Sidebar toggle functionality
function toggleSidebar() {
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (sidebar && mainContent) {
    sidebar.classList.toggle("active");
    mainContent.classList.toggle("active");
  }
}

// Sidebar Dropdown toggle functionality
function toggleSidebarDropdown(event) {
  event.preventDefault();
  const dropdown = event.target.closest(".sidebar-dropdown");
  if (dropdown) {
    dropdown.classList.toggle("open");
  }
}

// Close sidebar dropdown when clicking outside
document.addEventListener("click", function (event) {
  // Check if click is on a sidebar dropdown toggle
  const isSidebarDropdownToggle = event.target.closest(
    ".sidebar-dropdown-toggle",
  );

  if (!isSidebarDropdownToggle) {
    // Close all sidebar dropdowns
    document.querySelectorAll(".sidebar-dropdown").forEach((dropdown) => {
      dropdown.classList.remove("open");
    });
  }
});
