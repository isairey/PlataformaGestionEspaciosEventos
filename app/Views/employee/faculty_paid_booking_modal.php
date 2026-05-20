<!-- Paid Booking Modal (Dynamic like external.php) -->
<div id="paidBookingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <h2 class="modal-title" id="paidModalTitle">Book Facility</h2>
                <div class="availability-indicator" id="paidAvailabilityStatus" style="margin: 0;">
                    <span class="status-text">Loading...</span>
                    <span class="status-icon"></span>
                </div>
            </div>
            <span class="close" onclick="closePaidModal()">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Status Message Container (will be populated dynamically) -->
            <!-- Plan Selection -->
            <div class="plan-section">
                <h3 class="section-title">
                    <span>📋</span> Select Your Plan
                </h3>
                <div class="plans-grid" id="paidPlansGrid">
                    <!-- Plans will be populated dynamically -->
                </div>
            </div>

            <!-- Booking Information -->
            <div class="plan-section">
                <h3 class="section-title">
                    <span>📝</span> Booking Information
                </h3>
                <form class="booking-form" id="paidBookingForm">
                    <input type="hidden" id="paidFacilityKey" value="">
                    <input type="hidden" id="paidFacilityId" value="">
                    <input type="hidden" id="paidSelectedPlanId" value="">

                    <div class="form-group">
                        <label class="form-label">Client Name *</label>
                        <input type="text" class="form-control" id="paidClientName" value="<?= esc(session('full_name') ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Number *</label>
                        <input type="tel" class="form-control" id="paidContactNumber" value="<?= esc(session('contact_number') ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="paidEmailAddress" value="<?= esc(session('email') ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Organization/Company</label>
                        <input type="text" class="form-control" id="paidOrganization">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Complete Address *</label>
                        <textarea class="form-control textarea" id="paidAddress" rows="3"
                                  placeholder="Street, Barangay, City, Province" required></textarea>
                        <small style="color: #6c757d; font-size: 0.875rem;">Please provide your complete mailing address</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Event Date *</label>
                        <input type="date" class="form-control" id="paidEventDate" required onchange="handlePaidDateChange()">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Event Time *</label>
                        <input type="time" class="form-control" id="paidEventTime" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Expected Attendees</label>
                        <input type="number" class="form-control" id="paidAttendees" min="1">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Event Title/Purpose *</label>
                        <input type="text" class="form-control" id="paidEventTitle" required>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Special Requirements/Notes</label>
                        <textarea class="form-control textarea" id="paidSpecialRequirements" placeholder="Please specify any special requirements, setup instructions, or additional notes..."></textarea>
                    </div>
                </form>
            </div>

            <!-- Add-ons Section -->
            <div class="plan-section">
                <h3 class="section-title">
                    <span>✨</span> Additional Services
                </h3>
                <div class="addons-grid" id="paidAddonsGrid">
                    <!-- Add-ons will be populated dynamically -->
                </div>
            </div>

            <!-- Equipment Section -->
            <div class="plan-section">
                <h3 class="section-title">
                    <span>🔧</span> Equipment & Logistics
                </h3>
                <div id="paidEquipmentDatePlaceholder">
                    <div class="alert alert-info" style="background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 8px; padding: 15px; color: #1e40af;">
                        <i class="fas fa-calendar-check" style="margin-right: 8px;"></i>
                        <strong>Please select an event date first</strong> to view available equipment and quantities for that date.
                    </div>
                </div>
                <div class="equipment-grid" id="paidEquipmentGrid" style="display: none;">
                    <!-- Equipment cards will be populated dynamically -->
                </div>
            </div>

            <!-- Extended Hours Section -->
            <div class="plan-section">
                <h3 class="section-title">
                    <span>⏰</span> Extended Hours
                </h3>
                <div class="form-group">
                    <label class="form-label">Additional Hours (Rate: <span id="paidHourlyRateLabel">₱500</span>/hour)</label>
                    <input type="number" class="form-control" id="paidAdditionalHours" min="0" max="12" value="0" onchange="updatePaidCostSummary()">
                    <small>Add extra hours beyond your selected plan duration</small>
                </div>
            </div>

            <!-- Cost Summary -->
            <div class="cost-summary">
                <h3 class="section-title">
                    <span>💰</span> Cost Summary
                </h3>
                <div id="paidCostBreakdown">
                    <div class="cost-row">
                        <span>Base Plan:</span>
                        <span id="paidBaseCost">₱0</span>
                    </div>
                    <div class="cost-row mandatory">
                        <span>Maintenance Fee (Required):</span>
                        <span id="paidMaintenanceCost">₱2,000</span>
                    </div>
                    <div id="paidAddonCosts"></div>
                    <div class="cost-row total">
                        <span>Total Amount:</span>
                        <span id="paidTotalCost">₱2,000</span>
                    </div>
                </div>
            </div>

            <!-- Important Notice -->
            <div class="alert alert-warning mt-3" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #f59e0b; border-left: 4px solid #f59e0b; color: #78350f; border-radius: 8px; padding: 20px; margin-top: 20px;">
                <h6 style="color: #92400e; font-weight: 700; margin-bottom: 10px;">
                    <i class="fas fa-exclamation-triangle" style="margin-right: 8px; color: #f59e0b;"></i>
                    Important Notice
                </h6>
                <p style="margin-bottom: 10px; font-size: 14px;">
                    <i class="fas fa-building" style="margin-right: 8px; color: #f59e0b;"></i>
                    After submitting this booking, you must visit the office within <strong style="color: #92400e;">7 days</strong> to:
                </p>
                <ul style="margin-left: 25px; margin-bottom: 10px; font-size: 14px;">
                    <li>Sign the booking agreement</li>
                    <li>Pay the required amount</li>
                </ul>
                <p style="margin-bottom: 0; font-weight: 600; color: #dc2626; font-size: 14px;">
                    <i class="fas fa-times-circle" style="margin-right: 8px;"></i>
                    Failure to comply will result in automatic cancellation of your booking.
                </p>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePaidModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitPaidBooking()" id="submitPaidBtn" style="min-width: 160px;">Create Booking</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="paidSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(45deg, #22c55e, #16a34a); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>
                    Booking Request Submitted Successfully!
                </h5>
            </div>
            <div class="modal-body text-center">
                <div style="font-size: 4rem; color: #22c55e; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h4 style="color: #1e293b; margin-bottom: 15px;">Thank You!</h4>
                <p style="color: #64748b; margin-bottom: 20px;">
                    Your booking request has been submitted successfully. Our team will review your request and contact you within 24 hours to confirm availability and payment details.
                </p>
                <div style="background: rgba(34, 197, 94, 0.1); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #1e293b; font-weight: 600;">
                        <i class="fas fa-envelope me-2" style="color: #22c55e;"></i>
                        A confirmation email has been sent to your registered email address.
                    </p>
                </div>
                <p style="color: #64748b; font-size: 0.9rem;">
                    Reference Number: <strong id="paidReferenceNumber" style="color: #1e3c72;"></strong>
                </p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" onclick="closePaidSuccessModal()">
                    <i class="fas fa-home me-1"></i>
                    Back to Dashboard
                </button>
            </div>
        </div>
    </div>
</div>
