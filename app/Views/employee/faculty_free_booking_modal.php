<!-- Free Booking Modal (Student-style for Academic Events) -->
<div id="freeBookingModal" class="modal student-booking-modal">
    <div class="modal-content">
        <div class="modal-header">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <h2 class="modal-title" id="freeModalTitle">Academic/Free Booking</h2>
                <div class="availability-indicator" id="freeAvailabilityStatus" style="margin: 0;">
                    <span class="status-text">Loading...</span>
                    <span class="status-icon"></span>
                </div>
            </div>
            <span class="close" onclick="closeFreeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Status Message Container (will be populated dynamically) -->
            <!-- Basic Information Section -->
            <div class="plan-section">
                <h3 class="section-title">📝 Event Information</h3>
                <form id="freeBookingForm">
                    <!-- Hidden fields - auto-filled from session -->
                    <input type="hidden" id="freeClientName" value="<?= esc($userName ?? '') ?>">
                    <input type="hidden" id="freeClientEmail" value="<?= esc($userEmail ?? '') ?>">
                    <input type="hidden" id="freeFacilityKey" value="">
                    <input type="hidden" id="freeFacilityId" value="">
                    <input type="hidden" id="freeBookingType" value="faculty">

                    <!-- Contact Number - VISIBLE AND EDITABLE -->
                    <div class="form-group">
                        <label class="form-label">Contact Number *</label>
                        <input type="tel" class="form-control" id="freeContactNumber"
                               value="<?= esc($userPhone ?? '') ?>"
                               placeholder="e.g., 09123456789"
                               required>
                        <small style="color: var(--gray); font-size: 12px;">Please enter a valid mobile number</small>
                    </div>

                    <!-- Organization (visible for editing) -->
                    <div class="form-group">
                        <label class="form-label">Department/Organization *</label>
                        <input type="text" class="form-control" id="freeOrganization"
                               placeholder="e.g., Computer Science Department" required>
                    </div>

                    <!-- Event Date -->
                    <div class="form-group">
                        <label class="form-label">Event Date *</label>
                        <input type="date" class="form-control" id="freeEventDate" required onchange="handleFreeEventDateChange()">
                    </div>

                    <!-- Event Time -->
                    <div class="form-group">
                        <label class="form-label">Event Time *</label>
                        <input type="time" class="form-control" id="freeEventTime" required>
                    </div>

                    <!-- Duration -->
                    <div class="form-group">
                        <label class="form-label">Duration (hours) *</label>
                        <input type="number" class="form-control" id="freeDuration" min="1" max="12" value="4" required>
                    </div>

                    <!-- Attendees -->
                    <div class="form-group">
                        <label class="form-label">Expected Attendees</label>
                        <input type="number" class="form-control" id="freeAttendees" min="1">
                    </div>

                    <!-- Address -->
                    <div class="form-group full-width">
                        <label class="form-label">Address</label>
                        <textarea class="form-control textarea" id="freeAddress" rows="2" placeholder="Optional, but if provided, must be at least 10 characters"></textarea>
                    </div>

                    <!-- Event Title -->
                    <div class="form-group full-width">
                        <label class="form-label">Event Title/Purpose *</label>
                        <input type="text" class="form-control" id="freeEventTitle" required>
                    </div>

                    <!-- Special Requirements -->
                    <div class="form-group full-width">
                        <label class="form-label">Special Requirements</label>
                        <textarea class="form-control textarea" id="freeSpecialRequirements"></textarea>
                    </div>
                </form>
            </div>

            <!-- Equipment Section -->
            <div class="plan-section">
                <h3 class="section-title">🔧 Equipment Needed</h3>
                <div id="freeEquipmentDatePlaceholder">
                    <div class="alert alert-info" style="background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 8px; padding: 15px; color: #1e40af;">
                        <i class="fas fa-calendar-check" style="margin-right: 8px;"></i>
                        <strong>Please select an event date first</strong> to view available equipment and quantities for that date.
                    </div>
                </div>
                <div class="equipment-grid" id="freeEquipmentGrid" style="display: none;">
                    <!-- Will be populated dynamically based on selected date -->
                </div>
            </div>

            <!-- Important Notice Section -->
            <div class="plan-section" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 20px; margin-bottom: 20px; border-radius: 8px;">
                <h3 class="section-title" style="color: #92400e; margin-bottom: 15px;">
                    <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Important Notice
                </h3>
                <div style="color: #78350f; font-size: 14px; line-height: 1.6;">
                    <p style="margin-bottom: 10px; font-weight: 600;">
                        <i class="fas fa-building" style="color: #f59e0b; margin-right: 8px;"></i>
                        After submitting this booking, you must submit the required documents within <strong style="color: #92400e;">7 days</strong>
                    </p>
                    <p style="margin-bottom: 0; font-weight: 600; color: #dc2626;">
                        <i class="fas fa-times-circle" style="margin-right: 8px;"></i>
                        Failure to comply will result in automatic cancellation of your booking.
                    </p>
                </div>
            </div>

            <!-- Document Upload Section -->
            <div class="plan-section upload-section">
                <h3 class="section-title">📎 Required Documents</h3>
                <p style="color: var(--gray); font-size: 14px; margin-bottom: 20px;">
                    Please upload the following documents (PDF, JPG, PNG - Max 10MB each)
                </p>

                <!-- Permission Document -->
                <div class="upload-item" id="upload-permission">
                    <div class="upload-header">
                        <div class="upload-title">📄 Approved Permission to Conduct</div>
                        <span class="upload-status">Not uploaded</span>
                    </div>
                    <p style="font-size: 13px; color: var(--gray); margin-bottom: 10px;">
                        Official permission letter from your department head or adviser
                    </p>
                    <input type="file" id="file-permission" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileSelect(this, 'permission')" style="margin-bottom: 5px;">
                    <div class="file-name-display" id="filename-permission" style="font-size: 12px; color: var(--gray); font-style: italic;"></div>
                </div>

                <!-- Request Letter -->
                <div class="upload-item" id="upload-request">
                    <div class="upload-header">
                        <div class="upload-title">📝 Letter Request for Venue</div>
                        <span class="upload-status">Not uploaded</span>
                    </div>
                    <p style="font-size: 13px; color: var(--gray); margin-bottom: 10px;">
                        Formal letter requesting the use of the facility
                    </p>
                    <input type="file" id="file-request" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileSelect(this, 'request')" style="margin-bottom: 5px;">
                    <div class="file-name-display" id="filename-request" style="font-size: 12px; color: var(--gray); font-style: italic;"></div>
                </div>

                <!-- Approval Letter -->
                <div class="upload-item" id="upload-approval">
                    <div class="upload-header">
                        <div class="upload-title">✅ Approval Letter of the Venue</div>
                        <span class="upload-status">Not uploaded</span>
                    </div>
                    <p style="font-size: 13px; color: var(--gray); margin-bottom: 10px;">
                        Pre-approval or recommendation letter from authorized personnel
                    </p>
                    <input type="file" id="file-approval" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="handleStudentFileSelect(this, 'approval')" style="margin-bottom: 5px;">
                    <div class="file-name-display" id="filename-approval" style="font-size: 12px; color: var(--gray); font-style: italic;"></div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeFreeModal()">Cancel</button>
            <button type="button" class="btn btn-success" onclick="submitFreeBooking()" id="submitFreeBtn" disabled>
                Submit Free Booking
            </button>
        </div>
    </div>
</div>
