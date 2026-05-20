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
            background-color: #4CAF50;
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
            background-color: #d4edda;
            border-left: 4px solid #4CAF50;
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
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
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
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Cancellation Approved</h1>
        </div>

        <div class="content">
            <p>Dear <?= htmlspecialchars($booking['client_name']) ?>,</p>

            <p>We are pleased to inform you that your booking cancellation request has been <strong>approved</strong>.</p>

            <div class="status-box">
                <strong>Status:</strong> Your booking has been successfully cancelled<br>
                <strong>Booking ID:</strong> #<?= htmlspecialchars($booking['id']) ?><br>
                <strong>Approved On:</strong> <?= date('F d, Y \a\t H:i A') ?>
            </div>

            <h3>Cancelled Booking Details</h3>
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

            <?php if (!empty($approvalNotes) && $approvalNotes !== 'Cancellation approved'): ?>
            <div class="notes-box">
                <strong>Admin Notes:</strong><br>
                <?= htmlspecialchars($approvalNotes) ?>
            </div>
            <?php endif; ?>

            <p><strong>Next Steps:</strong></p>
            <ul>
                <li>Your cancellation request has been processed</li>
                <li>You will receive a confirmation email with refund details (if applicable)</li>
                <li>Any deposits or payments made will be handled according to our refund policy</li>
                <li>For further inquiries, please contact our facilities office</li>
            </ul>

            <p>Thank you for using CSPC Facilities Management System.</p>
        </div>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>CSPC Facilities Management System<br>
            Email: cspcsphere@gmail.com</p>
        </div>
    </div>
</body>
</html>
