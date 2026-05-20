<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invalid Survey Link</title>
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

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        h1 {
            color: #d9534f;
            font-size: 32px;
            margin-bottom: 15px;
        }

        p {
            color: #666;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .error-box {
            background-color: #fee;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #d9534f;
        }

        .error-box p {
            margin: 10px 0;
            font-size: 14px;
            color: #c33;
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
        <div class="error-icon">❌</div>
        <h1>Invalid or Expired Link</h1>
        <p>The survey link you're trying to access is either invalid, expired, or has already been submitted.</p>
        
        <div class="error-box">
            <p><strong>Possible reasons:</strong></p>
            <p>• The survey link is incorrect or has been modified</p>
            <p>• The survey has already been completed</p>
            <p>• The booking may not exist in our system</p>
        </div>

        <p>If you believe this is an error, please contact CSPC directly or request a new survey link from your booking confirmation email.</p>

        <div>
            <a href="<?= base_url('/') ?>" class="btn btn-primary">Return to Home</a>
        </div>
    </div>
</body>
</html>
