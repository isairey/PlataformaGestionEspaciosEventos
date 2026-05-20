<?php
// Check if user is logged in
$session = session();
$isLoggedIn = $session->get('user_id') !== null;
$userRole = $session->get('role');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Events Calendar - CSPC Facility Booking System</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?= base_url('css/event.css'); ?>">

</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('/') ?>">
                <div class="cspc-logo-nav">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                CSPC Sphere
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/') ?>">Home</a>
                    </li>
                                <li class="nav-item">
              <a class="nav-link" href="<?= site_url('/about') ?>">About</a>
            </li>    
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/facilities') ?>">Facilities</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= site_url('/event') ?>">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('/contact') ?>">Contact</a>
                    </li>
                    <li class="nav-item">
                        <?php if ($isLoggedIn): ?>
                            <button class="nav-link dashboard-btn btn px-3 py-2" onclick="window.location.href='<?= site_url('/user/dashboard') ?>'">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </button>
                        <?php else: ?>
                            <button class="nav-link login-btn btn px-3 py-2" onclick="window.location.href='<?= site_url('/login') ?>'">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="breadcrumb-nav">
                <a href="<?= site_url('/') ?>">Home</a>
                <span class="separator">•</span>
                <span>Events</span>
            </div>
            <h1>Events Calendar</h1>
            <p>
                View all upcoming and ongoing facility bookings. Stay updated with the latest events happening across our campus facilities.
            </p>
        </div>
    </section>

    <!-- Calendar Section -->
    <section class="calendar-section">
        <div class="container">
            <div class="section-header">
                <h2>Facility Booking Calendar</h2>
                <p>Browse scheduled events, bookings, and facility reservations</p>
            </div>

            <!-- Filters -->
            <div class="calendar-filters">
                <div class="filter-title">
                    <i class="fas fa-filter"></i>
                    Filter Events
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-day"></i> Date From
                        </label>
                        <input type="date" class="form-control" id="filterDateFrom">
                    </div>
                    <div class="filter-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-day"></i> Date To
                        </label>
                        <input type="date" class="form-control" id="filterDateTo">
                    </div>
                    <div class="filter-group">
                        <label class="form-label">
                            <i class="fas fa-building"></i> Facility
                        </label>
                        <select class="form-select" id="filterFacility">
                            <option value="">All Facilities</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="form-label">
                            <i class="fas fa-tasks"></i> Status
                        </label>
                        <select class="form-select" id="filterStatus">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="filter-buttons">
                    <button class="btn-search" onclick="applyFilters()">
                        <i class="fas fa-search"></i>
                        Apply Filters
                    </button>
                    <button class="btn-reset" onclick="resetFilters()">
                        <i class="fas fa-undo"></i>
                        Reset
                    </button>
                </div>
            </div>

            <!-- Calendar Navigation Header -->
            <div class="calendar-nav-header">
                <h3><i class="fas fa-calendar-alt"></i> <span id="currentMonthYear"></span></h3>
                <div class="month-navigation">
                    <button class="nav-button" onclick="previousMonth()">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <div class="current-month-display" id="monthDisplay"></div>
                    <button class="nav-button" onclick="nextMonth()">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="today-button" onclick="goToToday()">
                        <i class="fas fa-calendar-day"></i> Today
                    </button>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loadingSpinner" class="loading-spinner" style="display: none;">
                <div class="spinner"></div>
                <p>Loading events...</p>
            </div>

            <!-- Calendar Container -->
            <div class="calendar-container" id="calendarContainer">
                <div class="calendar-grid" id="calendarGrid">
                    <div class="calendar-day-header">Sun</div>
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                </div>
            </div>

            <!-- Calendar Legend -->
            <div class="calendar-legend">
                <div class="legend-item">
                    <div class="legend-color pending"></div>
                    <span class="legend-label">Pending</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color confirmed"></div>
                    <span class="legend-label">Confirmed</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color completed"></div>
                    <span class="legend-label">Completed</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Event Detail Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-calendar-check"></i> Event Details</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Event details will be populated here -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-logo">
                        <div class="cspc-logo-nav">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        CSPC Sphere
                    </div>
                    <p style="color: #94a3b8; line-height: 1.7; margin-bottom: 25px">
                        Your trusted platform for seamless facility booking and resource management at Camarines Sur Polytechnic Colleges.
                    </p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <a href="<?= site_url('/') ?>">Home</a>
                    <a href="<?= site_url('/facilities') ?>">Facilities</a>
                    <a href="<?= site_url('/event') ?>">Events</a>
                    <a href="<?= site_url('/contact') ?>">Contact</a>
                    <?php if (!$isLoggedIn): ?>
                    <a href="<?= site_url('/login') ?>">Login</a>
                    <?php endif; ?>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Facilities</h5>
                    <a href="/facilities/gymnasium">University Gymnasium</a>
                    <a href="/facilities/FunctionHall">Function Hall</a>
                    <a href="/facilities/Auditorium">Auditorium</a>
                    <a href="/facilities/classroom">Classrooms</a>
                    <a href="<?= site_url('/facilities') ?>">View All</a>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Contact Info</h5>
                    <p style="color: #94a3b8; display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <i class="fas fa-map-marker-alt" style="color: #2a5298"></i>
                        Nabua, Camarines Sur, Philippines
                    </p>
                    <p style="color: #94a3b8; display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <i class="fas fa-phone" style="color: #2a5298"></i>
                        +63 (54) 123-4567
                    </p>
                    <p style="color: #94a3b8; display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <i class="fas fa-envelope" style="color: #2a5298"></i>
                        info@cspc.edu.ph
                    </p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Camarines Sur Polytechnic Colleges. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        let allEvents = [];
        let filteredEvents = [];
        let currentDate = new Date();
        let selectedEvent = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeCalendar();
            loadEvents();
        });

        // Load events from API
        async function loadEvents() {
            try {
                document.getElementById('loadingSpinner').style.display = 'block';
                document.getElementById('calendarContainer').style.display = 'none';

                const response = await fetch('/api/events/list');
                const data = await response.json();

                if (data.success) {
                    allEvents = data.events;
                    filteredEvents = [...allEvents];
                    populateFacilityFilter();
                    renderCalendar();
                } else {
                    console.error('Failed to load events:', data.message);
                    showNoEventsMessage();
                }
            } catch (error) {
                console.error('Error loading events:', error);
                showNoEventsMessage();
            } finally {
                document.getElementById('loadingSpinner').style.display = 'none';
                document.getElementById('calendarContainer').style.display = 'block';
            }
        }

        // Populate facility filter dropdown
        function populateFacilityFilter() {
            const facilityFilter = document.getElementById('filterFacility');
            const facilities = [...new Set(allEvents.map(event => event.facility_name))].filter(f => f).sort();

            facilityFilter.innerHTML = '<option value="">All Facilities</option>';
            facilities.forEach(facility => {
                const option = document.createElement('option');
                option.value = facility;
                option.textContent = facility;
                facilityFilter.appendChild(option);
            });
        }

        // Initialize calendar
        function initializeCalendar() {
            updateMonthDisplay();
        }

        // Update month display
        function updateMonthDisplay() {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];
            const monthDisplay = monthNames[currentDate.getMonth()] + ' ' + currentDate.getFullYear();
            document.getElementById('monthDisplay').textContent = monthDisplay;
            document.getElementById('currentMonthYear').textContent = monthDisplay;
        }

        // Render calendar
        function renderCalendar() {
            const calendarGrid = document.getElementById('calendarGrid');
            const MAX_EVENTS_DISPLAY = 2; // Maximum events to show per day (2 to avoid overcrowding)

            // Clear existing days (keep headers)
            const days = calendarGrid.querySelectorAll('.calendar-day');
            days.forEach(day => day.remove());

            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Generate 42 days (6 weeks)
            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);

                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day';

                // Add classes
                if (date.getMonth() !== month) {
                    dayDiv.classList.add('other-month');
                }
                if (date.toDateString() === today.toDateString()) {
                    dayDiv.classList.add('today');
                }

                // Create day wrapper
                const dayWrapper = document.createElement('div');
                dayWrapper.className = 'calendar-day-wrapper';

                // Add day header with number and count
                const dayHeaderRow = document.createElement('div');
                dayHeaderRow.className = 'calendar-day-header-row';

                const dayNumber = document.createElement('div');
                dayNumber.className = 'calendar-day-number';
                dayNumber.textContent = date.getDate();
                dayHeaderRow.appendChild(dayNumber);

                // Get events for this date
                const dayEvents = filteredEvents.filter(event => {
                    const eventDate = new Date(event.event_date + 'T00:00:00');
                    return eventDate.toDateString() === date.toDateString();
                });

                // Add event count badge if there are events
                if (dayEvents.length > 0) {
                    const countBadge = document.createElement('div');
                    countBadge.className = 'event-count-badge';
                    countBadge.textContent = dayEvents.length;
                    dayHeaderRow.appendChild(countBadge);
                }

                dayWrapper.appendChild(dayHeaderRow);

                // Show only first MAX_EVENTS_DISPLAY events
                const visibleEvents = dayEvents.slice(0, MAX_EVENTS_DISPLAY);

                visibleEvents.forEach(event => {
                    const eventDiv = document.createElement('div');
                    eventDiv.className = `calendar-event status-${event.status}`;
                    
                    // Truncate long titles for display
                    const maxTitleLength = 30;
                    let displayTitle = event.event_title || 'Untitled Event';
                    if (displayTitle.length > maxTitleLength) {
                        displayTitle = displayTitle.substring(0, maxTitleLength) + '...';
                    }
                    
                    eventDiv.innerHTML = `
                        <div class="event-title">${escapeHtml(displayTitle)}</div>
                        <div class="event-time">${formatTime(event.event_time)}</div>
                    `;
                    eventDiv.onclick = (e) => {
                        e.stopPropagation();
                        showEventDetails(event);
                    };
                    dayWrapper.appendChild(eventDiv);
                });

                // Add "show more" indicator if there are more events
                if (dayEvents.length > MAX_EVENTS_DISPLAY) {
                    const moreIndicator = document.createElement('div');
                    moreIndicator.className = 'more-events-indicator';
                    moreIndicator.textContent = `+${dayEvents.length - MAX_EVENTS_DISPLAY} more`;
                    moreIndicator.onclick = (e) => {
                        e.stopPropagation();
                        showDayEvents(date, dayEvents);
                    };
                    dayWrapper.appendChild(moreIndicator);
                }

                dayDiv.appendChild(dayWrapper);
                calendarGrid.appendChild(dayDiv);
            }

            // Show no events message if no events
            if (filteredEvents.length === 0) {
                showNoEventsMessage();
            }
        }

        // Show all events for a specific day
        function showDayEvents(date, events) {
            const formattedDate = date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <h3 style="color: #1e3c72; margin-bottom: 10px;">
                        <i class="fas fa-calendar-day"></i> ${formattedDate}
                    </h3>
                    <p style="color: #64748b; font-size: 0.95rem;">${events.length} event(s) scheduled</p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    ${events.map(event => `
                        <div style="padding: 12px; border-left: 4px solid ${getStatusBorderColor(event.status)}; background: ${getStatusBackground(event.status)}; border-radius: 6px; cursor: pointer;" onclick="showEventDetails(${JSON.stringify(event).replace(/"/g, '&quot;')})">
                            <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px;">${escapeHtml(event.event_title)}</div>
                            <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 4px;">
                                <i class="fas fa-clock"></i> ${formatTime(event.event_time)} (${event.duration}h)
                            </div>
                            <div style="font-size: 0.85rem; color: #64748b;">
                                <i class="fas fa-building"></i> ${escapeHtml(event.facility_name || 'N/A')}
                            </div>
                            <div style="margin-top: 6px;">
                                <span class="status-badge-modal ${event.status}">
                                    ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}
                                </span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;

            document.getElementById('eventModal').style.display = 'block';
        }

        // Helper functions for colors
        function getStatusBorderColor(status) {
            const colors = {
                'pending': '#f59e0b',
                'confirmed': '#10b981',
                'completed': '#6b7280'
            };
            return colors[status] || '#64748b';
        }

        function getStatusBackground(status) {
            const backgrounds = {
                'pending': '#fffbeb',
                'confirmed': '#ecfdf5',
                'completed': '#f3f4f6'
            };
            return backgrounds[status] || '#f8fafc';
        }

        // Show event details in modal
        function showEventDetails(event) {
            selectedEvent = event;
            const modalBody = document.getElementById('modalBody');

            const eventDate = new Date(event.event_date);
            const formattedDate = eventDate.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            modalBody.innerHTML = `
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-heading"></i> Event Title</div>
                    <div class="event-detail-value">${escapeHtml(event.event_title)}</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-info-circle"></i> Status</div>
                    <div class="event-detail-value">
                        <span class="status-badge-modal ${event.status}">
                            ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}
                        </span>
                    </div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-calendar"></i> Date</div>
                    <div class="event-detail-value">${formattedDate}</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-clock"></i> Time</div>
                    <div class="event-detail-value">${formatTime(event.event_time)} (${event.duration} hours)</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-building"></i> Facility</div>
                    <div class="event-detail-value">${escapeHtml(event.facility_name || 'N/A')}</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-user"></i> Client</div>
                    <div class="event-detail-value">${escapeHtml(event.client_name)}</div>
                </div>
                ${event.organization ? `
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-users"></i> Organization</div>
                    <div class="event-detail-value">${escapeHtml(event.organization)}</div>
                </div>
                ` : ''}
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-users"></i> Attendees</div>
                    <div class="event-detail-value">${event.attendees || 'Not specified'} people</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-envelope"></i> Contact Email</div>
                    <div class="event-detail-value">${escapeHtml(event.email_address)}</div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-phone"></i> Contact Number</div>
                    <div class="event-detail-value">${escapeHtml(event.contact_number)}</div>
                </div>
                ${event.special_requirements ? `
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-clipboard-list"></i> Special Requirements</div>
                    <div class="event-detail-value">${escapeHtml(event.special_requirements)}</div>
                </div>
                ` : ''}
                <div class="event-detail-item">
                    <div class="event-detail-label"><i class="fas fa-money-bill-wave"></i> Total Cost</div>
                    <div class="event-detail-value">
                        ${event.booking_type === 'internal'
                            ? '<span style="color: #10b981; font-weight: 700;">FREE</span>'
                            : '₱' + Number(event.total_cost).toLocaleString()}
                    </div>
                </div>
            `;

            document.getElementById('eventModal').style.display = 'block';
        }

        // Close modal
        function closeModal() {
            document.getElementById('eventModal').style.display = 'none';
            selectedEvent = null;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('eventModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Apply filters
        function applyFilters() {
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo = document.getElementById('filterDateTo').value;
            const facility = document.getElementById('filterFacility').value;
            const status = document.getElementById('filterStatus').value;

            filteredEvents = allEvents.filter(event => {
                // Date from filter
                if (dateFrom && event.event_date < dateFrom) return false;

                // Date to filter
                if (dateTo && event.event_date > dateTo) return false;

                // Facility filter
                if (facility && event.facility_name !== facility) return false;

                // Status filter
                if (status && event.status !== status) return false;

                return true;
            });

            renderCalendar();
        }

        // Reset filters
        function resetFilters() {
            document.getElementById('filterDateFrom').value = '';
            document.getElementById('filterDateTo').value = '';
            document.getElementById('filterFacility').value = '';
            document.getElementById('filterStatus').value = '';
            filteredEvents = [...allEvents];
            renderCalendar();
        }

        // Navigation functions
        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            updateMonthDisplay();
            renderCalendar();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            updateMonthDisplay();
            renderCalendar();
        }

        function goToToday() {
            currentDate = new Date();
            updateMonthDisplay();
            renderCalendar();
        }

        // Utility functions
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTime(time) {
            if (!time) return 'N/A';
            return time.substring(0, 5);
        }

        function showNoEventsMessage() {
            const calendarGrid = document.getElementById('calendarGrid');
            const existingMessage = document.querySelector('.no-events-message');
            if (!existingMessage) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'no-events-message';
                messageDiv.innerHTML = `
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Events Found</h3>
                    <p>There are no events matching your current filters.</p>
                `;
                calendarGrid.parentElement.appendChild(messageDiv);
            }
        }
    </script>
</body>
</html>
