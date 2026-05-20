<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #ff9800;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .status-box {
            background-color: #ffe0b2;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 15px 0;
            border-radius: 3px;
        }
        .booking-details {
            background-color: white;
            padding: 15px;
            border: 1px solid #ddd;
            margin: 15px 0;
            border-radius: 3px;
        }
        .booking-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .booking-details table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .booking-details table td:first-child {
            font-weight: bold;
            width: 40%;
            background-color: #f5f5f5;
        }
        .notes-box {
            background-color: #fbe9e7;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 15px 0;
            border-radius: 3px;
        }
        .footer {
            background-color: #f0f0f0;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-radius: 0 0 5px 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✗ Cancellation Request Declined</h1>
        </div>

        <div class="content">
            <p>Dear <?= htmlspecialchars($booking['client_name']) ?>,</p>

            <p>We regret to inform you that your booking cancellation request has been <strong>declined</strong>.</p>

            <div class="status-box">
                <strong>Status:</strong> Your booking remains active<br>
                <strong>Booking ID:</strong> #<?= htmlspecialchars($booking['id']) ?><br>
                <strong>Decision Date:</strong> <?= date('F d, Y \a\t H:i A') ?>
            </div>

            <h3>Booking Details</h3>
            <div class="booking-details">
                <table>
                    <tr>
                        <td>Facility</td>
                        <td><?= htmlspecialchars($booking['facility_name'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <td>Event Title</td>
                        <td><?= htmlspecialchars($booking['event_title']) ?></td>
                    </tr>
                    <tr>
                        <td>Event Date</td>
                        <td><?= date('F d, Y', strtotime($booking['event_date'])) ?></td>
                    </tr>
                    <tr>
                        <td>Event Time</td>
                        <td><?= date('h:i A', strtotime($booking['event_time'])) ?></td>
                    </tr>
                    <tr>
                        <td>Duration</td>
                        <td><?= htmlspecialchars($booking['duration']) ?></td>
                    </tr>
                    <tr>
                        <td>Attendees</td>
                        <td><?= htmlspecialchars($booking['attendees'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <td>Total Cost</td>
                        <td>₱<?= number_format($booking['total_cost'], 2) ?></td>
                    </tr>
                </table>
            </div>

            <div class="notes-box">
                <strong>Reason for Declining Cancellation:</strong><br>
                <?= htmlspecialchars($rejectionNotes) ?>
            </div>

            <p><strong>What This Means:</strong></p>
            <ul>
                <li>Your booking remains active and confirmed</li>
                <li>You are still expected to proceed with your event on the scheduled date</li>
                <li>If you have further concerns or questions, please contact our facilities office</li>
                <li>To appeal this decision, please reach out to us directly with additional information</li>
            </ul>

            <p><strong>Contact Information:</strong></p>
            <p>
                Email: cspcsphere@gmail.com<br>
                For urgent matters, please reach out to our facilities management office during business hours.
            </p>

            <p>Thank you for your understanding.</p>
        </div>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>CSPC Facilities Management System<br>
            Email: cspcsphere@gmail.com</p>
        </div>
    </div>
</body>
</html>
