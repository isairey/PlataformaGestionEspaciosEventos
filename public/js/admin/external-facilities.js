// Initialize facilities on page load
document.addEventListener("DOMContentLoaded", function () {
  loadExternalFacilities();
});

// Fetch and render external facilities dynamically from API
async function loadExternalFacilities() {
  const grid = document.getElementById("externalFacilitiesGrid");

  if (!grid) return;

  try {
    // Show loading state
    grid.innerHTML =
      '<div style="text-align: center; padding: 40px; color: #666;">Loading facilities...</div>';

    // Fetch facilities from API
    const response = await fetch(
      `${
        typeof baseUrl !== "undefined" ? baseUrl : "/"
      }api/facilities/external`,
      {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
      }
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (!data.success || !data.data || data.data.length === 0) {
      grid.innerHTML =
        '<div style="text-align: center; padding: 40px; color: #666;">No facilities available.</div>';
      return;
    }

    const facilities = data.data;

    // Map facility icons
    const icons = {
      auditorium: "🎭",
      gymnasium: "🏀",
      "function-hall": "🏛️",
      "pearl-restaurant": "🍽️",
      "staff-house": "🏠",
      classrooms: "📖",
    };

    // Render facilities
    grid.innerHTML = facilities
      .map((facility) => {
        const facilityKey =
          facility.key || facility.facility_key || facility.id;
        const facilityIcon = facility.icon || icons[facilityKey] || "🏢";
        const facilityName = facility.name || facility.title || "";
        const facilityDescription =
          facility.description || "No description available";
        const facilityFeatures = Array.isArray(facility.features)
          ? facility.features
          : ["Air Conditioned", "Sound System", "Projector"];
        const facilityPrice =
          facility.price_range || facility.price || "Contact for pricing";

        return `
            <div class="facility-card" onclick="openBookingModal('${facilityKey}')">
                <div class="facility-image">${facilityIcon}</div>
                <div class="facility-info">
                    <h3 class="facility-title">${facilityName}</h3>
                    <p class="facility-description">${facilityDescription}</p>
                    <div class="facility-features">
                        ${facilityFeatures
                          .map(
                            (feature) =>
                              `<span class="feature-tag">${feature}</span>`
                          )
                          .join("")}
                    </div>
                    <div class="facility-price">
                        <span class="price-range">${facilityPrice}</span>
                        <button class="book-btn">Book Now</button>
                    </div>
                </div>
            </div>
          `;
      })
      .join("");
  } catch (error) {
    console.error("Error loading facilities:", error);
    grid.innerHTML =
      '<div style="text-align: center; padding: 40px; color: #dc3545;">Error loading facilities. Please try again later.</div>';
  }
}
