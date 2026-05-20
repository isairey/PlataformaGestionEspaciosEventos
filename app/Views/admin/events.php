<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC Admin - Events Management</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/admin/booking.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/events.css') ?>">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>CSPC Admin</h3>
        </div>
        
       <div class="sidebar-menu">
            <ul>
                <li><a href="<?= base_url('/admin') ?>" class="menu-item"><i>📊</i> Dashboard</a></li>
                <li><a href="<?= base_url('/admin/users') ?>" class="menu-item"><i>👥</i> Users</a></li>

                <!-- Dropdown for Booking -->
                <li class="dropdown">
                    <a href="#" class="menu-item dropdown-toggle" onclick="toggleDropdown(event)">
                        <i>🏢</i> Booking <span class="arrow">▾</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?= base_url('/admin/external') ?>" class="submenu-item">🌐 External</a></li>
                        <li><a href="<?= base_url('/admin/internal') ?>" class="submenu-item">🏛️ Internal</a></li>
                    </ul>
                </li>

                <li><a href="<?= base_url('/admin/events') ?>" class="menu-item active"><i>📅</i> Events</a></li>
                <li><a href="<?= base_url('/admin/equipment') ?>" class="menu-item"><i>🔧</i> Equipment</a></li>
                <li><a href="<?= base_url('/admin/plans') ?>" class="menu-item"><i>📋</i> Plans</a></li>
                <li><a href="<?= base_url('/admin/facilities-management') ?>" class="menu-item"><i>🏗️</i> Facilities</a></li>

                <div class="sidebar-divider"></div>

                <li><a href="<?= base_url('admin/booking-management') ?>" class="menu-item"><i>📝</i> Bookings</a></li>
                <li><a href="<?= base_url('/admin/attendance') ?>" class="menu-item"><i>📋</i> Attendance</a></li>
                <li><a href="<?= base_url('/admin/file-templates') ?>" class="menu-item"><i>📄</i> File Templates</a></li>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr(session('full_name'), 0, 2)) ?>
                </div>
                <div class="user-details">
                    <?= session('full_name'); ?>
                    <div class="role">Administrator</div>
                </div>
            </div>
            <a href="<?= site_url('logout') ?>" class="logout-btn" title="Logout">🚪</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
            
            <div class="search-box">
                <i>🔍</i>
                <input type="text" placeholder="Search events..." id="searchInput" onkeyup="if(event.key === 'Enter') applyFilters()">
            </div>
        </div>
        
        <!-- Events Management Page Content -->
        <div class="booking-management-page">
            <div class="page-title">
                <h2>📅 Events Management</h2>
                <p>View and manage approved facility booking events</p>
            </div>


            <!-- View Controls -->
            <div class="events-view-controls">
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid" onclick="switchView('grid')">
                        <span>⊞</span> Grid View
                    </button>
                    <button class="view-btn" data-view="list" onclick="switchView('list')">
                        <span>☰</span> List View
                    </button>
                    <button class="view-btn" data-view="calendar" onclick="switchView('calendar')">
                        <span>📅</span> Calendar View
                    </button>
                </div>

                <div class="calendar-nav" id="calendarNav" style="display: none;">
                    <button onclick="previousMonth()">❮</button>
                    <span class="current-month" id="currentMonth">December 2024</span>
                    <button onclick="nextMonth()">❯</button>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="filter-control" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Facility</label>
                        <select class="filter-control" id="facilityFilter">
                            <option value="">All Facilities</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Date From</label>
                        <input type="date" class="filter-control" id="dateFromFilter">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Date To</label>
                        <input type="date" class="filter-control" id="dateToFilter">
                    </div>
                    <div class="filter-group" style="display: flex; align-items: end; gap: 10px;">
                        <button class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
                        <button class="btn btn-secondary" onclick="clearFilters()">Clear Filters</button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingIndicator" class="loading" style="display: none;">
                <div class="loading-spinner"></div>
                Loading events...
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="error-message" style="display: none;"></div>

            <!-- Grid View -->
            <div id="gridView" class="events-grid">
                <!-- Events will be populated here -->
            </div>

            <!-- List View -->
            <div id="listView" class="events-list" style="display: none;">
                <div class="events-list-header">
                    <h3 class="events-list-title">Events List</h3>
                </div>
                <div class="table-content">
                    <table class="events-table">
                        <thead>
                            <tr>
                                <th>Event Details</th>
                                <th>Client</th>
                                <th>Facility</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Revenue</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="eventsTableBody">
                            <!-- Dynamic content will be inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Calendar View -->
            <div id="calendarView" class="calendar-view" style="display: none;">
                <div class="calendar-grid">
                    <div class="calendar-day-header">Sun</div>
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                    <!-- Calendar days will be populated here -->
                </div>
            </div>

            <!-- No Events Message -->
            <div id="noEventsMessage" class="no-events" style="display: none;">
                <div class="no-events-icon">📅</div>
                <h3>No Events Found</h3>
                <p>No approved events to display at this time.</p>
            </div>
        </div>
    </div>

    <!-- View Event Details Modal -->
    <div id="viewEventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">📅 Event Details</h2>
                <button class="close" onclick="closeModal('viewEventModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="booking-details" id="eventDetailsContent">
                    <!-- Dynamic content will be inserted here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewEventModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        let eventsData = [];
        let currentView = 'grid';
        let currentDate = new Date();
        let currentEventId = null;

        // Initialize page
        document.addEventListener("DOMContentLoaded", function() {
            updateCurrentMonthDisplay();
            loadEvents();
        });

        // API Configuration
        const API_BASE_URL = "/api/events";

        // Load events
        async function loadEvents() {
            showLoading(true);
            hideError();

            try {
                const response = await fetch(`${API_BASE_URL}/list`);
                const data = await response.json();
                
                if (data.success) {
                    eventsData = data.events;
                    populateFacilityFilter();
                    displayEvents(eventsData);
                    showCurrentView();
                } else {
                    throw new Error(data.message || 'Failed to load events');
                }
            } catch (error) {
                console.error('Error loading events:', error);
                showError('Failed to load events. Please try again later.');
            } finally {
                showLoading(false);
            }
        }

        // Populate facility filter dropdown
        function populateFacilityFilter() {
            const facilityFilter = document.getElementById('facilityFilter');
            const facilities = [...new Set(eventsData.map(event => event.facility_name))].sort();
            
            // Clear existing options except the first one
            facilityFilter.innerHTML = '<option value="">All Facilities</option>';
            
            facilities.forEach(facility => {
                if (facility) {
                    const option = document.createElement('option');
                    option.value = facility;
                    option.textContent = facility;
                    facilityFilter.appendChild(option);
                }
            });
        }

        // Display events based on current view
        function displayEvents(events) {
            if (events.length === 0) {
                showNoEvents(true);
                return;
            } else {
                showNoEvents(false);
            }

            switch (currentView) {
                case 'grid':
                    displayGridView(events);
                    break;
                case 'list':
                    displayListView(events);
                    break;
                case 'calendar':
                    displayCalendarView(events);
                    break;
            }
        }

        // Display grid view
        function displayGridView(events) {
            const container = document.getElementById('gridView');
            container.innerHTML = '';

            events.forEach(event => {
                const eventCard = createEventCard(event);
                container.appendChild(eventCard);
            });
        }

        // Create event card for grid view
        function createEventCard(event) {
            const card = document.createElement('div');
            const eventDate = new Date(event.event_date);
            const today = new Date();
            const isToday = eventDate.toDateString() === today.toDateString();
            const isUpcoming = eventDate > today;
            
            let cardClass = 'event-card';
            let badgeClass = 'event-date-badge';
            
            if (isToday) {
                cardClass += ' today';
                badgeClass += ' today';
            } else if (isUpcoming) {
                cardClass += ' upcoming';
                badgeClass += ' upcoming';
            } else if (event.status === 'completed') {
                cardClass += ' completed';
            }

            card.className = cardClass;
            card.onclick = () => viewEventDetails(event.id);

            const formattedDate = eventDate.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });

            const formattedTime = event.event_time.substring(0, 5);
            const eventId = event.id; // Store event ID for button reference

            card.innerHTML = `
                <div class="event-status-badge status-${event.status}">${event.status}</div>
                <div class="event-header">
                    <div>
                        <h3 class="event-title">${event.event_title}</h3>
                        <div class="event-date-badge ${badgeClass.split(' ')[1] || ''}">${formattedDate}</div>
                    </div>
                </div>
                
                <div class="event-info">
                    <div class="event-info-item">
                        <span class="event-info-icon">👤</span>
                        <span class="event-client">${event.client_name}</span>
                    </div>
                    <div class="event-info-item">
                        <span class="event-info-icon">🏢</span>
                        <span>${event.facility_name || 'Unknown Facility'}</span>
                    </div>
                    <div class="event-info-item">
                        <span class="event-info-icon">⏰</span>
                        <span>${formattedTime} (${event.duration} hours)</span>
                    </div>
                    <div class="event-info-item">
                        <span class="event-info-icon">👥</span>
                        <span>${event.attendees || 'TBD'} attendees</span>
                    </div>
                    <div class="event-info-item">
                        <span class="event-info-icon">💰</span>
                        <span>${(event.booking_type === 'student' || event.booking_type === 'employee') ? '<span style="color: #28a745; font-weight: 600;">FREE</span>' : '₱' + formatNumber(event.total_cost)}</span>
                    </div>
                    ${event.organization ? `
                    <div class="event-info-item">
                        <span class="event-info-icon">🏛️</span>
                        <span>${event.organization}</span>
                    </div>
                    ` : ''}
                </div>
                
                <div class="event-actions">
                    <button class="btn btn-primary btn-sm" data-event-id="${eventId}">
                        👁️ View Details
                    </button>
                </div>
            `;

            // Add event listener to button after creating the card
            const button = card.querySelector('.btn');
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                viewEventDetails(eventId);
            });

            return card;
        }

        // Display list view
        function displayListView(events) {
            const tbody = document.getElementById('eventsTableBody');
            tbody.innerHTML = '';

            events.forEach(event => {
                const row = createEventRow(event);
                tbody.appendChild(row);
            });
        }

        // Create event row for list view
        function createEventRow(event) {
            const row = document.createElement('tr');
            const eventDate = new Date(event.event_date);
            const formattedDate = eventDate.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
            const formattedTime = event.event_time.substring(0, 5);
            const eventId = event.id; // Store event ID for button reference

            row.innerHTML = `
                <td>
                    <div class="event-row-title">${event.event_title}</div>
                    <div class="event-row-client">Booking ID: #BK${String(event.booking_id).padStart(3, '0')}</div>
                </td>
                <td>
                    <div class="event-row-title">${event.client_name}</div>
                    <div class="event-row-client">${event.email_address}</div>
                </td>
                <td>
                    <div class="facility-badge">
                        <span>${event.facility_icon || '🏢'}</span>
                        <span>${event.facility_name || 'Unknown'}</span>
                    </div>
                </td>
                <td>
                    <div class="event-row-title">${formattedDate}</div>
                    <div class="event-row-client">${formattedTime} (${event.duration}h)</div>
                </td>
                <td>
                    <span class="status-badge status-${event.status}">
                        ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}
                    </span>
                </td>
                <td>${(event.booking_type === 'student' || event.booking_type === 'employee') ? '<span style="color: #28a745; font-weight: 600;">FREE</span>' : '₱' + formatNumber(event.total_cost)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-primary btn-sm" data-event-id="${eventId}">
                            👁️ View
                        </button>
                    </div>
                </td>
            `;

            // Add event listener to button after creating the row
            const button = row.querySelector('.btn');
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                viewEventDetails(eventId);
            });

            return row;
        }

        // Display calendar view
        function displayCalendarView(events) {
            console.log('displayCalendarView called with', events.length, 'events');
            const calendarContainer = document.querySelector('#calendarView .calendar-grid');

            if (!calendarContainer) {
                console.error('Calendar container not found!');
                return;
            }

            // Clear existing calendar days (keep headers)
            const existingDays = calendarContainer.querySelectorAll('.calendar-day');
            existingDays.forEach(day => day.remove());

            // Use the events passed to this function (already filtered)
            const filteredEvents = events;
            console.log('Displaying', filteredEvents.length, 'events on calendar');

            // Generate calendar for current month
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            const today = new Date();

            // Generate 42 days (6 weeks)
            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);

                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day';

                // Add different month class
                if (date.getMonth() !== month) {
                    dayDiv.classList.add('other-month');
                }

                // Add today class
                if (date.toDateString() === today.toDateString()) {
                    dayDiv.classList.add('today');
                }

                // Add day number
                const dayNumber = document.createElement('div');
                dayNumber.className = 'calendar-day-number';
                dayNumber.textContent = date.getDate();
                dayDiv.appendChild(dayNumber);

                // Add filtered events for this date
                const dayEvents = filteredEvents.filter(event => {
                    const eventDate = new Date(event.event_date + 'T00:00:00');
                    return eventDate.toDateString() === date.toDateString();
                });

                if (dayEvents.length > 0) {
                    console.log(`Day ${date.getDate()}: ${dayEvents.length} events`);
                }

                dayEvents.forEach(event => {
                    const eventDiv = document.createElement('div');
                    eventDiv.className = `calendar-event status-${event.status}`;
                    eventDiv.innerHTML = `
                        <div class="event-title">${escapeHtml(event.event_title)}</div>
                        <div class="event-time">${event.event_time.substring(0, 5)}</div>
                        <div class="event-facility">${escapeHtml(event.facility_name)}</div>
                    `;
                    eventDiv.onclick = () => viewEventDetails(event.id);
                    dayDiv.appendChild(eventDiv);
                });

                calendarContainer.appendChild(dayDiv);
            }

            console.log('Calendar generation complete');
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Get event time class for styling
        function getEventTimeClass(eventDate) {
            const today = new Date();
            const event = new Date(eventDate);
            
            if (event.toDateString() === today.toDateString()) {
                return 'today';
            } else if (event > today) {
                return 'upcoming';
            }
            return '';
        }

        // View event details
        async function viewEventDetails(eventId) {
            currentEventId = eventId;
            const event = eventsData.find(e => e.id === eventId);
            
            if (!event) {
                alert('Event not found');
                return;
            }

            displayEventDetails(event);
            document.getElementById('viewEventModal').style.display = 'block';
        }

        // Display event details in modal
        function displayEventDetails(event) {
            const detailsContainer = document.getElementById('eventDetailsContent');
            const eventDate = new Date(event.event_date);
            
            detailsContainer.innerHTML = `
                <div class="detail-section">
                    <div class="detail-title">📋 Event Information</div>
                    <div class="detail-item">
                        <span class="detail-label">Event ID:</span>
                        <span class="detail-value">#EV${String(event.id).padStart(3, "0")}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Booking ID:</span>
                        <span class="detail-value">#BK${String(event.booking_id).padStart(3, "0")}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Event Title:</span>
                        <span class="detail-value">${event.event_title}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Event Date:</span>
                        <span class="detail-value">${formatDate(event.event_date)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Event Time:</span>
                        <span class="detail-value">${event.event_time}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Duration:</span>
                        <span class="detail-value">${event.duration} hours</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-badge status-${event.status}">
                                ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}
                            </span>
                        </span>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-title">👤 Client Information</div>
                    <div class="detail-item">
                        <span class="detail-label">Client Name:</span>
                        <span class="detail-value">${event.client_name}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">${event.email_address}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Contact:</span>
                        <span class="detail-value">${event.contact_number}</span>
                    </div>
                    ${event.organization ? `
                    <div class="detail-item">
                        <span class="detail-label">Organization:</span>
                        <span class="detail-value">${event.organization}</span>
                    </div>
                    ` : ''}
                </div>

                <div class="detail-section">
                    <div class="detail-title">🏢 Facility Information</div>
                    <div class="detail-item">
                        <span class="detail-label">Facility:</span>
                        <span class="detail-value">${event.facility_name || 'Unknown Facility'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Attendees:</span>
                        <span class="detail-value">${event.attendees || 'TBD'} people</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Cost:</span>
                        <span class="detail-value">${(event.booking_type === 'student' || event.booking_type === 'employee') ? '<span style="color: #28a745; font-weight: 600;">FREE</span>' : '₱' + formatNumber(event.total_cost)}</span>
                    </div>
                </div>

                ${event.special_requirements ? `
                <div class="detail-section">
                    <div class="detail-title">📋 Special Requirements</div>
                    <p style="color: var(--dark); line-height: 1.6;">${event.special_requirements}</p>
                </div>
                ` : ''}

                ${event.approval_notes ? `
                <div class="detail-section">
                    <div class="detail-title">📝 Approval Notes</div>
                    <p style="color: var(--dark); line-height: 1.6;">${event.approval_notes}</p>
                </div>
                ` : ''}
            `;
        }

        // Switch view
        function switchView(view) {
            currentView = view;

            // Update active button
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-view="${view}"]`).classList.add('active');

            // Show/hide calendar navigation based on view
            const calendarNav = document.getElementById('calendarNav');
            if (view === 'calendar') {
                calendarNav.style.display = 'flex';
            } else {
                calendarNav.style.display = 'none';
            }

            showCurrentView();
            displayEvents(getFilteredEvents());
        }

        // Show current view and hide others
        function showCurrentView() {
            document.getElementById('gridView').style.display = 'none';
            document.getElementById('listView').style.display = 'none';
            document.getElementById('calendarView').style.display = 'none';
            
            switch (currentView) {
                case 'grid':
                    document.getElementById('gridView').style.display = 'grid';
                    break;
                case 'list':
                    document.getElementById('listView').style.display = 'block';
                    break;
                case 'calendar':
                    document.getElementById('calendarView').style.display = 'block';
                    break;
            }
        }

        // Calendar navigation
        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            updateCurrentMonthDisplay();
            if (currentView === 'calendar') {
                displayEvents(getFilteredEvents());
            }
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            updateCurrentMonthDisplay();
            if (currentView === 'calendar') {
                displayEvents(getFilteredEvents());
            }
        }

        function updateCurrentMonthDisplay() {
            const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            const monthDisplay = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
            document.getElementById('currentMonth').textContent = monthDisplay;
        }

        // Apply filters
        function applyFilters() {
            const filteredEvents = getFilteredEvents();
            displayEvents(filteredEvents);
        }

        // Filter events (legacy - now just calls applyFilters)
        function filterEvents() {
            applyFilters();
        }

        // View event details from button (stops event propagation)
        function viewEventDetailsFromButton(e, eventId) {
            e.stopPropagation();
            viewEventDetails(eventId);
        }

        // Get filtered events based on current filters
        function getFilteredEvents() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const facilityFilter = document.getElementById('facilityFilter').value;
            const dateFromFilter = document.getElementById('dateFromFilter').value;
            const dateToFilter = document.getElementById('dateToFilter').value;

            console.log('Filter values:', {
                searchTerm,
                statusFilter,
                facilityFilter,
                dateFromFilter,
                dateToFilter
            });

            let filtered = [...eventsData]; // Create a copy

            // Search filter
            if (searchTerm) {
                filtered = filtered.filter(event =>
                    event.event_title.toLowerCase().includes(searchTerm) ||
                    event.client_name.toLowerCase().includes(searchTerm) ||
                    event.facility_name.toLowerCase().includes(searchTerm) ||
                    event.email_address.toLowerCase().includes(searchTerm)
                );
                console.log('After search filter:', filtered.length);
            }

            // Status filter
            if (statusFilter) {
                filtered = filtered.filter(event => event.status === statusFilter);
                console.log('After status filter:', filtered.length);
            }

            // Facility filter
            if (facilityFilter) {
                filtered = filtered.filter(event => event.facility_name === facilityFilter);
                console.log('After facility filter:', filtered.length);
            }

            // Date range filter - Fixed to handle date comparison properly
            if (dateFromFilter) {
                const fromDate = new Date(dateFromFilter);
                fromDate.setHours(0, 0, 0, 0);

                filtered = filtered.filter(event => {
                    const eventDate = new Date(event.event_date);
                    eventDate.setHours(0, 0, 0, 0);
                    const matches = eventDate >= fromDate;

                    if (!matches) {
                        console.log(`Event ${event.event_title} (${event.event_date}) filtered out: before ${dateFromFilter}`);
                    }

                    return matches;
                });
                console.log('After date from filter:', filtered.length, 'events (from:', dateFromFilter, ')');
            }

            if (dateToFilter) {
                const toDate = new Date(dateToFilter);
                toDate.setHours(23, 59, 59, 999);

                filtered = filtered.filter(event => {
                    const eventDate = new Date(event.event_date);
                    eventDate.setHours(0, 0, 0, 0);
                    const matches = eventDate <= toDate;

                    if (!matches) {
                        console.log(`Event ${event.event_title} (${event.event_date}) filtered out: after ${dateToFilter}`);
                    }

                    return matches;
                });
                console.log('After date to filter:', filtered.length, 'events (to:', dateToFilter, ')');
            }

            console.log('Total filtered events:', filtered.length);
            return filtered;
        }

        // Clear all filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('facilityFilter').value = '';
            document.getElementById('dateFromFilter').value = '';
            document.getElementById('dateToFilter').value = '';
            displayEvents(eventsData);
        }

        // Utility functions
        function showLoading(show) {
            document.getElementById('loadingIndicator').style.display = show ? 'block' : 'none';
        }

        function showError(message) {
            const errorElement = document.getElementById('errorMessage');
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }

        function hideError() {
            document.getElementById('errorMessage').style.display = 'none';
        }

        function showNoEvents(show) {
            document.getElementById('noEventsMessage').style.display = show ? 'block' : 'none';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function formatNumber(number) {
            return new Intl.NumberFormat().format(number);
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            if (modalId === 'viewEventModal') {
                currentEventId = null;
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        };

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });
        
    </script>

</body>
</html>
