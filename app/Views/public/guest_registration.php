<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Guest Registration | CSPC Digital Booking System</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0a2b7a;
            --primary-light: #1e50a2;
            --primary-dark: #061d54;
            --secondary: #0d6efd;
            --secondary-light: #2680ff;
            --secondary-dark: #0b5ed7;
            --success: #198754;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #212529;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .registration-container {
            max-width: 650px;
            margin: 0 auto;
        }

        .registration-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .registration-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            border-bottom: 4px solid var(--secondary);
        }

        .card-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .card-header i {
            margin-right: 10px;
            font-size: 28px;
        }

        .card-header p {
            margin: 0;
            opacity: 0.95;
            font-size: 15px;
        }

        .card-body {
            padding: 40px;
        }

        .event-info {
            background: linear-gradient(135deg, #f0f4f8 0%, #e8eef5 100%);
            border-left: 5px solid var(--secondary);
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 8px;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .event-info h5 {
            color: var(--primary);
            margin-bottom: 18px;
            font-weight: 700;
            font-size: 17px;
        }

        .event-info i {
            margin-right: 8px;
            color: var(--secondary);
        }

        .event-detail {
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            font-size: 14px;
        }

        .event-detail strong {
            color: var(--primary);
            min-width: 100px;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
            font-size: 15px;
        }

        .form-control {
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: #999;
        }

        .required {
            color: var(--danger);
            font-weight: 700;
        }

        small.text-muted {
            color: #6c757d;
            font-size: 13px;
        }

        .privacy-notice {
            background: linear-gradient(135deg, #f0f8ff 0%, #e6f2ff 100%);
            border-left: 4px solid var(--secondary);
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 25px;
            font-size: 13px;
            line-height: 1.6;
            color: #333;
        }

        .privacy-notice strong {
            color: var(--primary);
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .privacy-notice ul {
            margin: 8px 0 0 20px;
            padding: 0;
        }

        .privacy-notice li {
            margin-bottom: 6px;
        }

        .privacy-notice i {
            color: var(--secondary);
            margin-right: 8px;
        }

        .privacy-checkbox-wrapper {
            margin: 25px 0;
            padding: 18px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #e0e6ed;
            transition: all 0.3s ease;
        }

        .privacy-checkbox-wrapper:hover {
            background: #fff;
            border-color: var(--secondary);
        }

        .form-check {
            display: flex;
            align-items: flex-start;
            margin: 0;
        }

        .form-check-input {
            margin-top: 3px;
            margin-right: 12px;
            width: 20px;
            height: 20px;
            accent-color: var(--secondary);
            cursor: pointer;
            border: 2px solid #dee2e6;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .form-check-input:checked {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
            outline: none;
        }

        .form-check-label {
            margin: 0;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
            cursor: pointer;
            flex: 1;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            color: white;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(10, 43, 122, 0.2);
        }

        .btn-register:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(10, 43, 122, 0.3);
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            color: white;
        }

        .btn-register:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
            padding: 14px 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
            color: #721c24;
            border-left: 4px solid var(--danger);
        }

        .alert i {
            font-size: 18px;
        }

        .footer-text {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 25px;
            font-size: 13px;
        }

        .success-message {
            display: none;
            text-align: center;
            padding: 50px 30px;
        }

        .success-message i {
            font-size: 80px;
            color: var(--success);
            margin-bottom: 20px;
            display: block;
        }

        .success-message h3 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 28px;
        }

        .success-message p {
            color: #333;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .success-message p strong {
            color: var(--secondary);
            font-weight: 600;
        }

        .success-message .text-muted {
            color: #6c757d;
            font-size: 14px;
            margin-top: 15px;
        }

        .spinner-border {
            display: none;
            margin-right: 8px;
        }

        .btn-check {
            display: none;
        }

        .btn-check:disabled + .form-check-label {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 576px) {
            body {
                padding: 20px 10px;
            }

            .registration-card {
                border-radius: 10px;
            }

            .card-header {
                padding: 30px 20px;
            }

            .card-header h1 {
                font-size: 26px;
            }

            .card-body {
                padding: 25px;
            }

            .success-message i {
                font-size: 60px;
            }

            .success-message h3 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-card">
            <div class="card-header">
                <h1><i class="fas fa-calendar-check"></i> Event Registration</h1>
                <p>Complete your registration to receive your QR code</p>
            </div>
            <div class="card-body">
                <!-- Alert Container -->
                <div id="alertContainer"></div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                <?php elseif (isset($booking)): ?>

                    <!-- Event Information -->
                    <div class="event-info">
                        <h5><i class="fas fa-info-circle"></i> Event Details</h5>
                        <div class="event-detail">
                            <strong>Event:</strong> <?= esc($booking['event_title']) ?>
                        </div>
                        <div class="event-detail">
                            <strong>Facility:</strong> <?= esc($booking['facility_name']) ?>
                        </div>
                        <div class="event-detail">
                            <strong>Date:</strong> <?= date('F d, Y', strtotime($booking['event_date'])) ?>
                        </div>
                        <div class="event-detail">
                            <strong>Time:</strong> <?= esc($booking['event_time']) ?>
                        </div>
                    </div>

                    <!-- Registration Form -->
                    <form id="registrationForm" onsubmit="submitRegistration(event)">
                        <input type="hidden" id="bookingId" value="<?= $booking['id'] ?>">

                        <div class="mb-3">
                            <label for="guestName" class="form-label"><i class="fas fa-user"></i> Full Name <span class="required">*</span></label>
                            <input type="text" class="form-control" id="guestName" required placeholder="Enter your full name">
                        </div>

                        <div class="mb-3">
                            <label for="guestEmail" class="form-label"><i class="fas fa-envelope"></i> Email Address <span class="required">*</span></label>
                            <input type="email" class="form-control" id="guestEmail" required placeholder="Enter your email address">
                            <small class="text-muted"><i class="fas fa-info-circle"></i> Your QR code will be sent to this email</small>
                        </div>

                        <div class="mb-3">
                            <label for="guestPhone" class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" class="form-control" id="guestPhone" placeholder="Enter your phone number (optional)">
                        </div>

                        <!-- Data Privacy Notice -->
                        <div class="privacy-notice">
                            <strong><i class="fas fa-shield-alt"></i> Data Privacy & Protection</strong>
                            <p style="margin: 8px 0 0 0;">We respect your privacy and are committed to protecting your personal data. By registering for this event, you acknowledge that:</p>
                            <ul style="margin-top: 8px;">
                                <li>Your personal information will be used solely for event registration and check-in purposes</li>
                                <li>Your data will be securely stored and not shared with third parties without your consent</li>
                                <li>You may withdraw consent or request data deletion at any time</li>
                                <li>We comply with applicable data protection regulations</li>
                            </ul>
                        </div>

                        <!-- Privacy Consent Checkbox -->
                        <div class="privacy-checkbox-wrapper">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="privacyConsent" required>
                                <label class="form-check-label" for="privacyConsent">
                                    <i class="fas fa-check-circle" style="color: var(--secondary);"></i> I agree to the collection and use of my personal data as outlined in the Data Privacy Notice <span class="required">*</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-register" id="submitBtn">
                            <span id="btnText">
                                <i class="fas fa-check-circle"></i> Register for Event
                            </span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        </button>
                    </form>

                    <!-- Success Message (hidden initially) -->
                    <div id="successMessage" class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <h3>Registration Successful!</h3>
                        <p>Your registration has been confirmed.</p>
                        <p><strong>Check your email</strong> for your unique QR code.</p>
                        <p class="text-muted">Please bring your QR code (digital or printed) to the event for check-in.</p>
                    </div>

                <?php endif; ?>
            </div>
        </div>
        <p class="footer-text">
            <small>CSPC Digital Booking System &copy; <?= date('Y') ?></small>
        </p>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        async function submitRegistration(event) {
            event.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            const form = document.getElementById('registrationForm');

            // Disable button and show spinner
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';

            const guestData = {
                booking_id: document.getElementById('bookingId').value,
                guest_name: document.getElementById('guestName').value.trim(),
                guest_email: document.getElementById('guestEmail').value.trim(),
                guest_phone: document.getElementById('guestPhone').value.trim()
            };

            try {
                const response = await fetch('/api/guest-registration/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(guestData)
                });

                const data = await response.json();

                if (data.success) {
                    // Hide form and show success message
                    form.style.display = 'none';
                    document.querySelector('.event-info').style.display = 'none';
                    document.getElementById('successMessage').style.display = 'block';
                } else {
                    showAlert('danger', data.message || 'Registration failed. Please try again.');
                    // Re-enable button
                    submitBtn.disabled = false;
                    btnText.style.display = 'inline-block';
                    btnSpinner.style.display = 'none';
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred. Please try again.');
                // Re-enable button
                submitBtn.disabled = false;
                btnText.style.display = 'inline-block';
                btnSpinner.style.display = 'none';
            }
        }

        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alert);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    </script>
</body>
</html>
