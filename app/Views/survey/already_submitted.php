<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Already Submitted</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #003366 0%, #004d99 100%);
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 50px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 600px;
        }

        .info-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        h1 {
            color: #003366;
            font-size: 32px;
            margin-bottom: 15px;
        }

        p {
            color: #666;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .info-box {
            background-color: #f0f4f8;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #003366;
        }

        .info-box p {
            margin: 10px 0;
            font-size: 14px;
        }

        .info-box strong {
            color: #003366;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-primary {
            background-color: #003366;
            color: white;
        }

        .btn-primary:hover {
            background-color: #002244;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="info-icon">ℹ️</div>
        <h1>Survey Already Submitted</h1>
        <p>Thank you for your response! We have already recorded your survey submission for this booking.</p>
        
        <div class="info-box">
            <p><strong>Booking ID:</strong> #BK<?= str_pad($booking['id'], 4, '0', STR_PAD_LEFT) ?></p>
            <p><strong>Submitted on:</strong> <?= date('F d, Y \a\t g:i A', strtotime($survey['created_at'])) ?></p>
        </div>

        <p>Each survey can only be submitted once. Your feedback has been saved and will be reviewed by our team to help improve our services.</p>

        <div>
            <a href="<?= base_url('/') ?>" class="btn btn-primary">Return to Home</a>
        </div>
    </div>
</body>
</html>
