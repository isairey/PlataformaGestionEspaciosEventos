
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    loadRecentBookings();
});

function loadDashboardData() {
    // Fetch dashboard statistics
    fetch('/api/user/dashboard-stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatCards(data.stats);
            }
        })
        .catch(error => {
            console.error('Error loading dashboard data:', error);
        });
}

function updateStatCards(stats) {
    document.getElementById('activeBookings').textContent = stats.active || 0;
    document.getElementById('pendingBookings').textContent = stats.pending || 0;
    document.getElementById('completedBookings').textContent = stats.completed || 0;
    document.getElementById('totalSpent').textContent = '₱' + (stats.totalSpent || 0).toLocaleString();
}

function loadRecentBookings() {
    const container = document.getElementById('recentBookings');
    
    fetch('/api/user/recent-bookings')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.bookings.length > 0) {
                container.innerHTML = generateBookingsTable(data.bookings);
            } else {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5>No Recent Bookings</h5>
                        <p class="text-muted">You haven't made any bookings yet.</p>
                        <a href="/facilities" class="btn btn-primary">Make Your First Booking</a>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading recent bookings:', error);
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                    <p class="text-muted">Error loading bookings. Please try again later.</p>
                </div>
            `;
        });
}

function generateBookingsTable(bookings) {
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Facility</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    bookings.forEach(booking => {
        const statusClass = getStatusClass(booking.status);
        html += `
            <tr>
                <td><strong>#${booking.id}</strong></td>
                <td>${booking.facility_name}</td>
                <td>${formatDate(booking.event_date)}</td>
                <td><span class="badge ${statusClass}">${
          booking.status
        }</span></td>
                <td>₱${parseFloat(booking.total_cost).toLocaleString()}</td>
                <td>${getActionButtons(booking)}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    return html;
}

function getStatusClass(status) {
    switch(status.toLowerCase()) {
        case 'confirmed':
            return 'bg-success';
        case 'pending':
            return 'bg-warning';
        case 'cancelled':
            return 'bg-danger';
        case 'completed':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function viewBooking(id) {
    window.location.href = `/bookings/${id}`;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function viewBooking(bookingId) {
  // Redirect to booking details page
  window.location.href = `/dashboard/bookings/${bookingId}`;
}

// Smooth animations for stat cards
function animateStatCards() {
  const statCards = document.querySelectorAll(".stat-card");
  statCards.forEach((card, index) => {
    setTimeout(() => {
      card.style.opacity = "0";
      card.style.transform = "translateY(20px)";
      card.style.transition = "all 0.5s ease";

      setTimeout(() => {
        card.style.opacity = "1";
        card.style.transform = "translateY(0)";
      }, 100);
    }, index * 100);
  });
}

// Initialize animations when page loads
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(animateStatCards, 500);
});

// Refresh dashboard data every 5 minutes
setInterval(loadDashboardData, 300000);

// Enhanced stat card animations
function animateStatCards() {
    const statCards = document.querySelectorAll(".stat-card");
    statCards.forEach((card, index) => {
        card.style.opacity = "0";
        card.style.transform = "translateY(30px)";
        
        setTimeout(() => {
            card.style.transition = "all 0.6s cubic-bezier(0.4, 0, 0.2, 1)";
            card.style.opacity = "1";
            card.style.transform = "translateY(0)";
        }, index * 150);
    });
}

// Animate cards on load
document.addEventListener("DOMContentLoaded", function() {
    setTimeout(animateStatCards, 300);
    
    // Add stagger animation to other elements
    const cards = document.querySelectorAll(".card");
    cards.forEach((card, index) => {
        card.style.opacity = "0";
        card.style.transform = "translateY(20px)";
        
        setTimeout(() => {
            card.style.transition = "all 0.5s ease";
            card.style.opacity = "1";
            card.style.transform = "translateY(0)";
        }, 600 + (index * 100));
    });
});


function getActionButtons(booking) {
  return `
    <button class="btn btn-sm btn-outline-primary" onclick="viewBooking(${booking.id})" title="View Details">
      <i class="fas fa-eye"></i>
    </button>
  `;
}

