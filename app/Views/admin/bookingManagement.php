<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC Admin - Booking Management</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>"> 
    <link rel="stylesheet" href="<?= base_url('css/admin/booking.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <li class="sidebar-dropdown">
                    <a href="#" class="menu-item sidebar-dropdown-toggle" onclick="toggleSidebarDropdown(event)">
                        <i>🏢</i> Booking <span class="arrow">▾</span>
                    </a>
                    <ul class="sidebar-dropdown-menu">
                        <li><a href="<?= base_url('/admin/external') ?>" class="submenu-item">🌐 External</a></li>
                        <li><a href="<?= base_url('/admin/internal') ?>" class="submenu-item active">🏛️ Internal</a></li>
                    </ul>
                </li>

                <li><a href="<?= base_url('/admin/events') ?>" class="menu-item"><i>📅</i> Events</a></li>
                <li><a href="<?= base_url('/admin/equipment') ?>" class="menu-item"><i>🔧</i> Equipment</a></li>
                <li><a href="<?= base_url('/admin/plans') ?>" class="menu-item"><i>📋</i> Plans</a></li>
                <li><a href="<?= base_url('/admin/facilities-management') ?>" class="menu-item"><i>🏗️</i> Facilities</a></li>

                <div class="sidebar-divider"></div>

                <li><a href="<?= base_url('admin/booking-management') ?>" class="menu-item active"><i>📝</i> Bookings</a></li>
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
                <input type="text" placeholder="Search bookings..." id="searchInput" onkeyup="filterBookings()">
            </div>
            

        </div>
        
        <!-- Booking Management Page Content -->
        <div class="booking-management-page">
            <div class="page-title">
                <h2>Booking Management</h2>
                <p>Review, approve, and manage facility booking requests</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number" id="pendingCount">-</div>
                    <div class="stat-label">Pending Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number" id="confirmedCount">-</div>
                    <div class="stat-label">Confirmed Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-number" id="cancelledCount">-</div>
                    <div class="stat-label">Cancelled Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-number" id="totalRevenue">-</div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="filter-control" id="statusFilter" onchange="filterBookings()">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Date Range</label>
                        <input type="date" class="filter-control" id="dateFromFilter" onchange="filterBookings()">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Date To</label>
                        <input type="date" class="filter-control" id="dateToFilter" onchange="filterBookings()">
                    </div>
                    <div class="filter-group" style="display: flex; align-items: end;">
                        <button class="btn btn-secondary" onclick="clearFilters()">Clear Filters</button>
                    </div>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="bookings-table">
                <div class="table-header">
                    <div class="table-title">Booking Requests</div>
                    <button class="btn btn-warning" onclick="generateReport()">
                        📄 Generate Report
                    </button>
                </div>
                <div class="table-content">
                    <div id="loadingIndicator" class="loading" style="display: none;">
                        <div class="loading-spinner"></div>
                        Loading bookings...
                    </div>
                    <div id="errorMessage" class="error-message" style="display: none;"></div>
                    <table id="bookingsTable" style="display: none;">
                        <thead>
                            <tr>
<th>Booking ID</th>
<th>Client</th>
<th>Type</th>
<th>Facility</th>
<th>Event Date</th>
<th>Status</th>
<th>Total Cost</th>

                            </tr>
                        </thead>
                        <tbody id="bookingsTableBody">
                            <!-- Dynamic content will be inserted here -->
                        </tbody>
                    </table>
                    <div id="noBookingsMessage" class="no-data-message" style="display: none;">
                        <div class="no-data-icon">📋</div>
                        <h3>No Bookings Found</h3>
                        <p>There are no bookings matching your current filters.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Booking Modal -->
    <div id="viewBookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Booking Details</h2>
                <button class="close" onclick="closeModal('viewBookingModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Tabs Container -->
                <div class="booking-tabs" id="bookingTabs"></div>
                <div class="booking-details" id="bookingDetailsContent">
                    <!-- Dynamic content will be inserted here -->
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewBookingModal')">Close</button>
                <button type="button" class="btn btn-info" onclick="openDownloadModal()" id="downloadBtn">📥 Download Files</button>
                <button type="button" class="btn btn-success" onclick="openApprovalModal()" id="approveBtn" style="display: none;">✅ Approve Booking</button>
                <button type="button" class="btn btn-danger" onclick="openDeclineModal()" id="declineBtn" style="display: none;">❌ Decline Booking</button>
            </div>
        </div>
    </div>

    <!-- Approval Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Approve Booking</h2>
                <button class="close" onclick="closeModal('approvalModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="requirements-checklist">
                    <div class="checklist-title">
                        📋 Verification Checklist
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="req1" required>
                        <label for="req1">All required information is complete and verified</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="req2" required>
                        <label for="req2">Event date and time availability confirmed</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="req3" required>
                        <label for="req3">Facility requirements match event needs</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="req4" required>
                        <label for="req4">Payment terms and conditions acknowledged</label>
                    </div>
                    <div class="checklist-item">
                        <input type="checkbox" id="req5" required>
                        <label for="req5">Safety and security guidelines reviewed</label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Approval Notes (Optional)</label>
                    <textarea class="form-control textarea" id="approvalNotes" placeholder="Add any additional notes or conditions for the approval..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('approvalModal')">Cancel</button>
                <button type="button" class="btn btn-success" onclick="approveBooking()" id="approveBookingBtn" disabled>Approve Booking</button>
            </div>
        </div>
    </div>

    <!-- Decline Modal -->
    <div id="declineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Decline Booking</h2>
                <button class="close" onclick="closeModal('declineModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Reason for Decline *</label>
                    <select class="form-control" id="declineReason" required onchange="toggleCustomReason()">
                        <option value="">Select a reason</option>
                        <option value="facility-unavailable">Facility not available</option>
                        <option value="incomplete-information">Incomplete information</option>
                        <option value="policy-violation">Violates university policies</option>
                        <option value="insufficient-notice">Insufficient advance notice</option>
                        <option value="conflicting-event">Conflicting with existing event</option>
                        <option value="maintenance-scheduled">Scheduled maintenance</option>
                        <option value="other">Other (specify below)</option>
                    </select>
                </div>

                <div class="form-group" id="customReasonGroup" style="display: none;">
                    <label class="form-label">Custom Reason *</label>
                    <input type="text" class="form-control" id="customDeclineReason" placeholder="Please specify the reason">
                </div>

                <div class="form-group">
                    <label class="form-label">Additional Notes *</label>
                    <textarea class="form-control textarea" id="declineNotes" required placeholder="Please provide detailed explanation for the client..."></textarea>
                </div>

                <div class="form-group">
                    <input type="checkbox" id="sendNotification" checked>
                    <label for="sendNotification" style="margin-left: 8px;">Send email notification to client</label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('declineModal')">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="declineBooking()">Decline Booking</button>
            </div>
        </div>
    </div>

    <div id="downloadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">📥 Download Booking Documents</h2>
            <button class="close" onclick="closeModal('downloadModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="download-info">
                <p class="download-description">Select the documents you want to download for this booking:</p>
                <div class="booking-info-summary">
                    <strong>Booking ID:</strong> <span id="downloadBookingId">-</span> | 
                    <strong>Client:</strong> <span id="downloadClientName">-</span>
                </div>
            </div>

            <div class="download-files-list">
                <div class="file-item">
                    <div class="file-info">
                        <div class="file-icon">📄</div>
                        <div class="file-details">
                            <div class="file-name">Memorandum of Agreement</div>
                            <div class="file-description">Legal agreement between CSPC and client</div>
                        </div>
                    </div>
                    <div class="file-actions">
                        <input type="checkbox" id="file-moa" class="file-checkbox">
                        <button class="btn btn-sm btn-outline" onclick="downloadSingleFile('moa')" title="Download">
                            📥
                        </button>
                    </div>
                </div>

                <div class="file-item">
                    <div class="file-info">
                        <div class="file-icon">💰</div>
                        <div class="file-details">
                            <div class="file-name">Billing Statement</div>
                            <div class="file-description">Detailed cost breakdown and payment information</div>
                        </div>
                    </div>
                    <div class="file-actions">
                        <input type="checkbox" id="file-billing" class="file-checkbox">
                        <button class="btn btn-sm btn-outline" onclick="downloadSingleFile('billing')" title="Download">
                            📥
                        </button>
                    </div>
                </div>

                <div class="file-item">
                    <div class="file-info">
                        <div class="file-icon">🔧</div>
                        <div class="file-details">
                            <div class="file-name">Facilities Equipment Request Form</div>
                            <div class="file-description">Equipment and facility requirements form</div>
                        </div>
                    </div>
                    <div class="file-actions">
                        <input type="checkbox" id="file-equipment" class="file-checkbox">
                        <button class="btn btn-sm btn-outline" onclick="downloadSingleFile('equipment')" title="Download">
                            📥
                        </button>
                    </div>
                </div>

                <div class="file-item">
                    <div class="file-info">
                        <div class="file-icon">⭐</div>
                        <div class="file-details">
                            <div class="file-name">CSPC Rental Facility Evaluation Form</div>
                            <div class="file-description">Facility evaluation and feedback form</div>
                        </div>
                    </div>
                    <div class="file-actions">
                        <input type="checkbox" id="file-evaluation" class="file-checkbox">
                        <button class="btn btn-sm btn-outline" onclick="downloadSingleFile('evaluation')" title="Download">
                            📥
                        </button>
                    </div>
                </div>

                <div class="file-item">
                    <div class="file-info">
                        <div class="file-icon">🔍</div>
                        <div class="file-details">
                            <div class="file-name">Inspection and Evaluation Test</div>
                            <div class="file-description">Pre and post-event inspection report</div>
                        </div>
                    </div>
                    <div class="file-actions">
                        <input type="checkbox" id="file-inspection" class="file-checkbox">
                        <button class="btn btn-sm btn-outline" onclick="downloadSingleFile('inspection')" title="Download">
                            📥
                        </button>
                    </div>
                </div>
<div class="file-item">
    <div class="file-info">
        <div class="file-icon">💳</div>
        <div class="file-details">
            <div class="file-name">Order of Payment</div>
            <div class="file-description">Official order of payment document</div>
        </div>
    </div>
    <div class="file-actions">
        <input type="checkbox" id="file-orderofpayment" class="file-checkbox">
        <button class="btn btn-sm btn-outline" onclick="downloadSingleFile('orderofpayment')" title="Download">
            📥
        </button>
    </div>
</div>
                
            </div>

            <div class="download-actions">
                <div class="select-actions">
                    <button class="btn btn-sm btn-secondary" onclick="selectAllFiles()">Select All</button>
                    <button class="btn btn-sm btn-secondary" onclick="deselectAllFiles()">Deselect All</button>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('downloadModal')">Cancel</button>
<button type="button" class="btn btn-secondary" onclick="closeModal('downloadModal')">Close</button>
        </div>
    </div>
</div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content upload-modal-content">
            <div class="modal-header upload-header">
                <h2 class="modal-title upload-title">📤 Upload Booking Documents</h2>
                <button class="close" onclick="closeUploadModal()">&times;</button>
            </div>
            <div class="modal-body upload-body">
                <div class="upload-info">
                    <p class="upload-description">Upload the required documents for booking approval process:</p>
                    <div class="booking-info-summary">
                        <strong>Booking ID:</strong> <span id="uploadBookingId">-</span> | 
                        <strong>Client:</strong> <span id="uploadClientName">-</span>
                    </div>
                </div>

                <div class="upload-files-list">
                    <!-- Student/Employee File Items -->
                    <div class="upload-file-item" data-file-type="permission_letter">
                        <div class="file-upload-info">
                            <div class="file-upload-icon">📋</div>
                            <div class="file-upload-details">
                                <div class="file-upload-name">Permission Letter</div>
                                <div class="file-upload-description">Letter of permission from organization</div>
                                <div class="file-upload-status status-required">Required for approval</div>
                            </div>
                        </div>
                        <div class="file-upload-actions">
                            <div class="file-input-wrapper">
                                <input type="file" class="file-input" id="permission_letter-file" accept=".pdf,.doc,.docx" onchange="handleStudentFileUpload(this, 'permission_letter')">
                                <label for="permission_letter-file" class="file-upload-btn">📤 Choose File</label>
                            </div>
                            <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadStudentUploadedFile('permission_letter')" style="display: none;" title="Download">
                                📥
                            </button>
                            <button class="btn btn-sm cancel-file-btn" onclick="cancelStudentFileUpload('permission_letter')" style="display: none;" title="Remove File">
                                ❌
                            </button>
                        </div>
                    </div>

                    <div class="upload-file-item" data-file-type="request_letter">
                        <div class="file-upload-info">
                            <div class="file-upload-icon">📝</div>
                            <div class="file-upload-details">
                                <div class="file-upload-name">Request Letter</div>
                                <div class="file-upload-description">Official request letter for booking</div>
                                <div class="file-upload-status status-required">Required for approval</div>
                            </div>
                        </div>
                        <div class="file-upload-actions">
                            <div class="file-input-wrapper">
                                <input type="file" class="file-input" id="request_letter-file" accept=".pdf,.doc,.docx" onchange="handleStudentFileUpload(this, 'request_letter')">
                                <label for="request_letter-file" class="file-upload-btn">📤 Choose File</label>
                            </div>
                            <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadStudentUploadedFile('request_letter')" style="display: none;" title="Download">
                                📥
                            </button>
                            <button class="btn btn-sm cancel-file-btn" onclick="cancelStudentFileUpload('request_letter')" style="display: none;" title="Remove File">
                                ❌
                            </button>
                        </div>
                    </div>

                    <div class="upload-file-item" data-file-type="approval_letter">
                        <div class="file-upload-info">
                            <div class="file-upload-icon">✅</div>
                            <div class="file-upload-details">
                                <div class="file-upload-name">Approval Letter</div>
                                <div class="file-upload-description">Approval letter from authorized personnel</div>
                                <div class="file-upload-status status-required">Required for approval</div>
                            </div>
                        </div>
                        <div class="file-upload-actions">
                            <div class="file-input-wrapper">
                                <input type="file" class="file-input" id="approval_letter-file" accept=".pdf,.doc,.docx" onchange="handleStudentFileUpload(this, 'approval_letter')">
                                <label for="approval_letter-file" class="file-upload-btn">📤 Choose File</label>
                            </div>
                            <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadStudentUploadedFile('approval_letter')" style="display: none;" title="Download">
                                📥
                            </button>
                            <button class="btn btn-sm cancel-file-btn" onclick="cancelStudentFileUpload('approval_letter')" style="display: none;" title="Remove File">
                                ❌
                            </button>
                        </div>
                    </div>

                    <!-- Admin/User File Items -->
                    <div class="upload-file-item" data-file-type="receipt">
                        <div class="file-upload-info">
                            <div class="file-upload-icon">🧾</div>
                            <div class="file-upload-details">
                                <div class="file-upload-name">Receipt</div>
                                <div class="file-upload-description">Payment receipt or proof of payment</div>
                                <div class="file-upload-status status-required">Required for approval</div>
                            </div>
                        </div>
<div class="file-upload-actions">
    <div class="file-input-wrapper">
        <input type="file" class="file-input" id="receipt-file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleFileUpload(this, 'receipt')">
        <label for="receipt-file" class="file-upload-btn">📤 Choose File</label>
    </div>
    <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadUploadedFile('receipt')" style="display: none;" title="Download">
        📥
    </button>
    <button class="btn btn-sm cancel-file-btn" onclick="cancelFileUpload('receipt')" style="display: none;" title="Remove File">
        ❌
    </button>
</div>
                    </div>

                    <div class="upload-file-item" data-file-type="moa">
                        <div class="file-upload-info">
                            <div class="file-upload-icon">📄</div>
                            <div class="file-upload-details">
                                <div class="file-upload-name">Memorandum of Agreement</div>
                                <div class="file-upload-description">Legal agreement between CSPC and client</div>
                                <div class="file-upload-status status-required">Required for approval</div>
                            </div>
                        </div>
                        <div class="file-upload-actions">
                            <div class="file-input-wrapper">
                                <input type="file" class="file-input" id="moa-file" accept=".pdf,.doc,.docx" onchange="handleFileUpload(this, 'moa')">
                                <label for="moa-file" class="file-upload-btn">📤 Choose File</label>
                            </div>
                            <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadUploadedFile('moa')" style="display: none;" title="Download">
                                📥
                            <button class="btn btn-sm cancel-file-btn" onclick="cancelFileUpload('moa')" style="display: none;" title="Remove File">
❌
</button>
                        </div>
                    </div>

                    <div class="upload-file-item" data-file-type="billing">
                        <div class="file-upload-info">
                            <div class="file-upload-icon">💰</div>
                            <div class="file-upload-details">
                                <div class="file-upload-name">Billing Statement</div>
                                <div class="file-upload-description">Detailed cost breakdown and payment information</div>
                                <div class="file-upload-status status-required">Required for approval</div>
                            </div>
                        </div>
                        <div class="file-upload-actions">
                            <div class="file-input-wrapper">
                                <input type="file" class="file-input" id="billing-file" accept=".pdf,.xls,.xlsx" onchange="handleFileUpload(this, 'billing')">
                                <label for="billing-file" class="file-upload-btn">📤 Choose File</label>
                            </div>
                            <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadUploadedFile('billing')" style="display: none;" title="Download">
                                📥
                            </button>
                                <button class="btn btn-sm cancel-file-btn" onclick="cancelFileUpload('billing')" style="display: none;" title="Remove File">
                               ❌
                             </button>
                        </div>
                    </div>

                    <div class="upload-file-item" data-file-type="equipment">
                        <div class="file-upload-info">
                            <div class="file-upload-icon">🔧</div>
                            <div class="file-upload-details">
                                <div class="file-upload-name">Facilities Equipment Request Form</div>
                                <div class="file-upload-description">Equipment and facility requirements form</div>
                                <div class="file-upload-status status-required">Required for approval</div>
                            </div>
                        </div>
                        <div class="file-upload-actions">
                            <div class="file-input-wrapper">
                                <input type="file" class="file-input" id="equipment-file" accept=".pdf,.doc,.docx" onchange="handleFileUpload(this, 'equipment')">
                                <label for="equipment-file" class="file-upload-btn">📤 Choose File</label>
                            </div>
                            <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadUploadedFile('equipment')" style="display: none;" title="Download">
                                📥
                            </button>
                                <button class="btn btn-sm cancel-file-btn" onclick="cancelFileUpload('equipment')" style="display: none;" title="Remove File">
                                 ❌
                             </button>
                        </div>
                    </div>

                    <div class="upload-file-item" data-file-type="evaluation">
                        <div class="file-upload-info">
                            <div class="file-upload-icon">⭐</div>
                            <div class="file-upload-details">
                                <div class="file-upload-name">CSPC Rental Facility Evaluation Form</div>
                                <div class="file-upload-description">Facility evaluation and feedback form</div>
                                <div class="file-upload-status status-required">Required for approval</div>
                            </div>
                        </div>
                        <div class="file-upload-actions">
                            <div class="file-input-wrapper">
                                <input type="file" class="file-input" id="evaluation-file" accept=".pdf,.doc,.docx" onchange="handleFileUpload(this, 'evaluation')">
                                <label for="evaluation-file" class="file-upload-btn">📤 Choose File</label>
                            </div>
                            <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadUploadedFile('evaluation')" style="display: none;" title="Download">
                                📥
                            </button>
                                <button class="btn btn-sm cancel-file-btn" onclick="cancelFileUpload('evaluation')" style="display: none;" title="Remove File">
                            ❌
                          </button>

                        </div>
                    </div>
                

<div class="upload-file-item" data-file-type="inspection">
    <div class="file-upload-info">
        <div class="file-upload-icon">🔍</div>
        <div class="file-upload-details">
            <div class="file-upload-name">Inspection and Evaluation Test</div>
            <div class="file-upload-description">Pre and post-event inspection report</div>
            <div class="file-upload-status status-required">Required for approval</div>
        </div>
    </div>
    <div class="file-upload-actions">
        <div class="file-input-wrapper">
            <input type="file" class="file-input" id="inspection-file" accept=".pdf,.doc,.docx" onchange="handleFileUpload(this, 'inspection')">
            <label for="inspection-file" class="file-upload-btn">📤 Choose File</label>
        </div>
        <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadUploadedFile('inspection')" style="display: none;" title="Download">
            📥
        </button>
        <button class="btn btn-sm cancel-file-btn" onclick="cancelFileUpload('inspection')" style="display: none;" title="Remove File">
            ❌
        </button>
    </div>
</div>
 <div class="upload-file-item" data-file-type="orderofpayment">
    <div class="file-upload-info">
        <div class="file-upload-icon">💳</div>
        <div class="file-upload-details">
            <div class="file-upload-name">Order of Payment</div>
            <div class="file-upload-description">Official order of payment document</div>
            <div class="file-upload-status status-required">Required for approval</div>
        </div>
    </div>
    <div class="file-upload-actions">
        <div class="file-input-wrapper">
            <input type="file" class="file-input" id="orderofpayment-file" accept=".pdf,.jpg,.jpeg,.png" onchange="handleFileUpload(this, 'orderofpayment')">
            <label for="orderofpayment-file" class="file-upload-btn">📤 Choose File</label>
        </div>
        <button class="btn btn-sm btn-outline download-uploaded-btn" onclick="downloadUploadedFile('orderofpayment')" style="display: none;" title="Download">
            📥
        </button>
        <button class="btn btn-sm cancel-file-btn" onclick="cancelFileUpload('orderofpayment')" style="display: none;" title="Remove File">
            ❌
        </button>
    </div>
</div>
            <div class="modal-footer upload-footer">
                <div class="upload-progress">
                    <div class="progress-info">
                        Documents uploaded: <span id="uploadedCount">0</span> of <span id="totalCount">7</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="uploadProgressFill"></div>
                    </div>
                </div>
                <div class="upload-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="saveUploadedFiles()" id="saveUploadBtn" disabled>💾 Save All Files</button>
                </div>
            </div>
        </div>
    </div>

                        </div>
                    </div>
                </div>
            </div>
            


    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">📅 Reschedule Booking</h2>
                <button class="close" onclick="closeModal('rescheduleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="booking-info-summary">
                    <strong>Booking ID:</strong> <span id="rescheduleBookingId">-</span> | 
                    <strong>Client:</strong> <span id="rescheduleClientName">-</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Current Event Date</label>
                    <input type="text" class="form-control" id="currentEventDate" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">Current Start Time</label>
                    <input type="text" class="form-control" id="currentStartTime" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">New Event Date *</label>
                    <input type="date" class="form-control" id="newEventDate" required>
                </div>

                <div class="form-group">
                    <label class="form-label">New Start Time *</label>
                    <input type="time" class="form-control" id="newStartTime" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Reason for Rescheduling *</label>
                    <select class="form-control" id="rescheduleReason" required onchange="toggleCustomRescheduleReason()">
                        <option value="">Select a reason</option>
                        <option value="client-request">Client Request</option>
                        <option value="facility-unavailable">Facility Unavailable</option>
                        <option value="scheduling-conflict">Scheduling Conflict</option>
                        <option value="weather">Weather Related</option>
                        <option value="maintenance">Maintenance Issue</option>
                        <option value="other">Other (specify below)</option>
                    </select>
                </div>

                <div class="form-group" id="customRescheduleReasonGroup" style="display: none;">
                    <label class="form-label">Custom Reason *</label>
                    <input type="text" class="form-control" id="customRescheduleReason" placeholder="Please specify the reason">
                </div>

                <div class="form-group">
                    <label class="form-label">Additional Notes (Optional)</label>
                    <textarea class="form-control textarea" id="rescheduleNotes" placeholder="Add any additional notes..."></textarea>
                </div>

                <div class="form-group">
                    <input type="checkbox" id="notifyClient" checked>
                    <label for="notifyClient" style="margin-left: 8px;">Send notification to client</label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('rescheduleModal')">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="submitReschedule()">📅 Reschedule Booking</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Booking</h2>
                <button class="close" onclick="closeModal('deleteConfirmModal')">&times;</button>
            </div>
            <div class="modal-body">
<div class="delete-warning">
    <div class="warning-icon">⚠️</div>
    <h3>Are you sure you want to delete this booking?</h3>
    <p>This action cannot be undone. All booking information, associated data, and event records will be permanently removed.</p>
</div>
                
                <div class="booking-info-summary">
                    <div class="info-item">
                        <span class="info-label">Booking ID:</span>
                        <span class="info-value" id="deleteBookingId">#BK000</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Client Name:</span>
                        <span class="info-value" id="deleteClientName">-</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Reason for Deletion (Optional)</label>
                    <textarea class="form-control textarea" id="deleteReason" placeholder="Please provide a reason for deleting this booking..."></textarea>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="confirmDelete" class="form-checkbox">
                    <label for="confirmDelete">I understand that this action is permanent and cannot be undone</label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteConfirmModal')">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteBooking()" id="confirmDeleteBtn" disabled>Delete Booking</button>
            </div>
        </div>
    </div>

    <!-- Decline Reason View Modal -->
<div id="declineReasonModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">❌ Booking Decline Information</h2>
            <button class="close" onclick="closeModal('declineReasonModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="decline-info">
                <div class="booking-info-summary">
                    <strong>Booking ID:</strong> <span id="declineReasonBookingId">-</span> | 
                    <strong>Client:</strong> <span id="declineReasonClientName">-</span>
                </div>
            </div>

            <div class="decline-section">
                <div class="detail-title">❌ Decline Information</div>
                <div class="detail-item">
                    <span class="detail-label">Reason for Decline:</span>
                    <div class="detail-value" id="viewDeclineReason">-</div>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Additional Notes:</span>
                    <div class="decline-notes" id="viewDeclineNotes">-</div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('declineReasonModal')">Close</button>
        </div>
    </div>
</div>

    <!-- Facility Rental Report Modal -->
    <div id="facilityRentalReportModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 class="modal-title">Generate Facility Rental Report</h2>
                <button class="close" onclick="closeModal('facilityRentalReportModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="report-filter-section">
                    <div class="form-group">
                        <label class="form-label">Select Month</label>
                        <select class="form-control" id="reportMonth">
                            <option value="">-- Select Month --</option>
                            <option value="01">January</option>
                            <option value="02">February</option>
                            <option value="03">March</option>
                            <option value="04">April</option>
                            <option value="05">May</option>
                            <option value="06">June</option>
                            <option value="07">July</option>
                            <option value="08">August</option>
                            <option value="09">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Select Year</label>
                        <select class="form-control" id="reportYear">
                            <option value="">-- Select Year --</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('facilityRentalReportModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateFacilityRentalReport()">📄 Generate Report</button>
            </div>
        </div>
    </div>

    <!-- Extension Upload Modal (Phase 5) -->
    <div id="extensionUploadModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 class="modal-title">Upload Extension Document</h2>
                <button class="close" onclick="closeExtensionModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select Document Type</label>
                    <select id="extensionDocumentType" class="form-control" required>
                        <option value="">-- Select Type --</option>
                        <option value="receipt">Payment Receipt</option>
                        <option value="approval">Manager Approval</option>
                        <option value="justification">Extension Justification</option>
                        <option value="evidence">Supporting Evidence</option>
                        <option value="other">Other Document</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Upload File</label>
                    <div class="upload-area" id="extensionUploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h5>Drop file here or click to browse</h5>
                        <p>Accepted formats: PDF, JPG, PNG (Max 10MB)</p>
                        <input type="file" id="extensionFileInput" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                    </div>
                </div>

                <div id="extensionFilePreview" style="display: none; margin-top: 16px;">
                    <div class="form-group">
                        <label class="form-label">Selected File</label>
                        <div class="file-item">
                            <i class="fas fa-file-alt"></i>
                            <div style="flex: 1;">
                                <div class="file-name" id="extensionFileName"></div>
                                <small id="extensionFileSize"></small>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeExtensionFile()">Remove</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExtensionModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="extensionUploadBtn" onclick="uploadExtensionFile()" disabled>
                    <i class="fas fa-upload"></i> Upload Document
                </button>
            </div>
        </div>
    </div>

    <script src="<?= base_url('js/admin/sidebar.js') ?>"></script>
    <script src="<?= base_url('js/admin/bookingManagement.js') ?>"></script>
    <script src="<?= base_url('js/admin/extensionManagement.js') ?>"></script>

</body>
</html>
