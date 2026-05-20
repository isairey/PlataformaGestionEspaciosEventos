<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC Rental Facility Evaluation Form</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
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
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 0;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            text-align: center;
            padding: 40px 30px;
            border-bottom: 4px solid var(--secondary);
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .header h1 i {
            margin-right: 12px;
            font-size: 28px;
        }

        .header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 15px;
            opacity: 0.9;
        }

        .content-wrapper {
            padding: 40px;
        }

        .booking-info {
            background: linear-gradient(135deg, #f0f4f8 0%, #e8eef5 100%);
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 5px solid var(--secondary);
        }

        .booking-info p {
            margin: 10px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .booking-info p i {
            color: var(--secondary);
            margin-right: 10px;
            width: 16px;
        }

        .booking-info strong {
            color: var(--primary);
            font-weight: 600;
        }

        .form-section {
            margin-bottom: 35px;
        }

        .section-title {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 15px 20px;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 25px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(10, 43, 122, 0.15);
        }

        .section-title i {
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 28px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
            border-radius: 8px;
            border-left: 4px solid #e0e6ed;
            transition: all 0.3s ease;
        }

        .form-group:hover {
            border-left-color: var(--secondary);
            background: linear-gradient(135deg, #fff 0%, #f8faff 100%);
        }

        .question-label {
            display: block;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary);
            font-size: 15px;
        }

        .question-label i {
            margin-right: 8px;
            color: var(--secondary);
        }

        .rating-options {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .rating-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rating-option input[type="radio"],
        .rating-option input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--secondary);
            border: 2px solid #dee2e6;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .rating-option input[type="radio"] {
            border-radius: 50%;
        }

        .rating-option input[type="radio"]:checked,
        .rating-option input[type="checkbox"]:checked {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }

        .rating-option label {
            cursor: pointer;
            margin: 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            color: #333;
            transition: all 0.3s ease;
        }

        .rating-option input[type="radio"]:checked ~ label,
        .rating-option input[type="checkbox"]:checked ~ label {
            color: var(--primary);
            font-weight: 600;
        }

        .subquestion {
            margin-left: 30px;
            margin-top: 15px;
            padding: 16px;
            background: white;
            border-radius: 6px;
            border-left: 3px solid var(--secondary);
        }

        .subquestion .question-label {
            font-size: 14px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        textarea {
            width: 100%;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 14px;
            border: 2px solid #e0e6ed;
            border-radius: 6px;
            resize: vertical;
            min-height: 100px;
            transition: all 0.3s ease;
            background: white;
        }

        textarea:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
            background: white;
        }

        select {
            width: 100%;
            padding: 10px 14px;
            font-family: inherit;
            font-size: 14px;
            border: 2px solid #e0e6ed;
            border-radius: 6px;
            transition: all 0.3s ease;
            background: white;
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e0e6ed;
        }

        button {
            padding: 12px 35px;
            font-size: 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(10, 43, 122, 0.2);
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(10, 43, 122, 0.3);
        }

        .btn-submit:disabled {
            background: #999;
            cursor: not-allowed;
            transform: none;
        }

        .btn-reset {
            background: #f0f0f0;
            color: var(--primary);
            border: 2px solid #dee2e6;
        }

        .btn-reset:hover {
            background: #e8e8e8;
            border-color: var(--primary);
        }

        .required-note {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .required-note i {
            color: var(--danger);
        }

        .loading {
            display: none;
            text-align: center;
            padding: 30px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 45px;
            height: 45px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
            color: #721c24;
            padding: 15px 16px;
            border-radius: 6px;
            margin-bottom: 25px;
            border-left: 4px solid var(--danger);
            display: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .error-message i {
            font-size: 18px;
            flex-shrink: 0;
        }

        .success-message {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #155724;
            padding: 15px 16px;
            border-radius: 6px;
            margin-bottom: 25px;
            border-left: 4px solid var(--success);
            display: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .success-message i {
            font-size: 18px;
            flex-shrink: 0;
        }

        .rating-scale {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        @media (max-width: 600px) {
            body {
                padding: 20px 10px;
            }

            .container {
                border-radius: 10px;
            }

            .content-wrapper {
                padding: 25px;
            }

            .header {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 26px;
            }

            .form-actions {
                flex-direction: column;
            }

            button {
                width: 100%;
                justify-content: center;
            }

            .rating-options {
                gap: 10px;
            }

            .subquestion {
                margin-left: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-star"></i> Facility Evaluation Form</h1>
            <p>Thank you for choosing CSPC as the venue of your occasion/event. In CSPC, we are committed to provide excellent services and open to suggestions for the continual improvement of our system. To help us serve you better, may we ask you to take a few minutes to answer this survey.</p>
        </div>

        <div class="content-wrapper">
            <div class="booking-info">
                <p><i class="fas fa-hashtag"></i> <strong>Booking ID:</strong> #BK<?= str_pad($booking['id'], 4, '0', STR_PAD_LEFT) ?></p>
                <p><i class="fas fa-building"></i> <strong>Facility Rented:</strong> <?= htmlspecialchars($booking['event_title']) ?></p>
                <p><i class="fas fa-calendar"></i> <strong>Event Date:</strong> <?= date('F d, Y', strtotime($booking['event_date'])) ?></p>
                <p><i class="fas fa-user"></i> <strong>Your Name:</strong> <?= htmlspecialchars($booking['client_name']) ?></p>
            </div>

            <div class="error-message" id="errorMessage"><i class="fas fa-exclamation-circle"></i><span></span></div>
            <div class="success-message" id="successMessage"><i class="fas fa-check-circle"></i><span></span></div>

            <form id="surveyForm">
                <input type="hidden" name="survey_token" value="<?= htmlspecialchars($token) ?>">
                <?= csrf_field() ?>

                <p class="required-note"><i class="fas fa-asterisk"></i> All fields are required to complete the survey</p>

                <!-- STAFF SECTION -->
                <div class="form-section">
                    <div class="section-title"><i class="fas fa-users"></i> Staff Evaluation</div>

                    <div class="form-group">
                        <label class="question-label"><i class="fas fa-question-circle"></i> 1. Punctuality of the staff (Property Staff and Audio Operator) *</label>
                        <div class="rating-options">
                        <div class="rating-option">
                            <input type="radio" id="staff_punctuality_excellent" name="staff_punctuality" value="Excellent" required>
                            <label for="staff_punctuality_excellent">Excellent</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="staff_punctuality_very_good" name="staff_punctuality" value="Very Good">
                            <label for="staff_punctuality_very_good">Very Good</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="staff_punctuality_good" name="staff_punctuality" value="Good">
                            <label for="staff_punctuality_good">Good</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="staff_punctuality_fair" name="staff_punctuality" value="Fair">
                            <label for="staff_punctuality_fair">Fair</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="staff_punctuality_poor" name="staff_punctuality" value="Poor">
                            <label for="staff_punctuality_poor">Poor</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="staff_punctuality_na" name="staff_punctuality" value="N/A">
                            <label for="staff_punctuality_na">N/A</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="question-label">2. Level of courtesy, respect, and helpfulness of the following</label>

                    <div class="subquestion">
                        <label class="question-label">a. Property Staff *</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_property_excellent" name="staff_courtesy_property" value="Excellent" required>
                                <label for="staff_courtesy_property_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_property_very_good" name="staff_courtesy_property" value="Very Good">
                                <label for="staff_courtesy_property_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_property_good" name="staff_courtesy_property" value="Good">
                                <label for="staff_courtesy_property_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_property_fair" name="staff_courtesy_property" value="Fair">
                                <label for="staff_courtesy_property_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_property_poor" name="staff_courtesy_property" value="Poor">
                                <label for="staff_courtesy_property_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_property_na" name="staff_courtesy_property" value="N/A">
                                <label for="staff_courtesy_property_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">b. Audio Operator *</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_audio_excellent" name="staff_courtesy_audio" value="Excellent" required>
                                <label for="staff_courtesy_audio_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_audio_very_good" name="staff_courtesy_audio" value="Very Good">
                                <label for="staff_courtesy_audio_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_audio_good" name="staff_courtesy_audio" value="Good">
                                <label for="staff_courtesy_audio_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_audio_fair" name="staff_courtesy_audio" value="Fair">
                                <label for="staff_courtesy_audio_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_audio_poor" name="staff_courtesy_audio" value="Poor">
                                <label for="staff_courtesy_audio_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_audio_na" name="staff_courtesy_audio" value="N/A">
                                <label for="staff_courtesy_audio_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">c. Janitor *</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_janitor_excellent" name="staff_courtesy_janitor" value="Excellent" required>
                                <label for="staff_courtesy_janitor_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_janitor_very_good" name="staff_courtesy_janitor" value="Very Good">
                                <label for="staff_courtesy_janitor_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_janitor_good" name="staff_courtesy_janitor" value="Good">
                                <label for="staff_courtesy_janitor_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_janitor_fair" name="staff_courtesy_janitor" value="Fair">
                                <label for="staff_courtesy_janitor_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_janitor_poor" name="staff_courtesy_janitor" value="Poor">
                                <label for="staff_courtesy_janitor_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="staff_courtesy_janitor_na" name="staff_courtesy_janitor" value="N/A">
                                <label for="staff_courtesy_janitor_na">N/A</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FACILITY SECTION -->
            <div class="form-section">
                <div class="section-title">🏢 FACILITY EVALUATION</div>

                <div class="form-group">
                    <label class="question-label">1. Level at which the facility met your expectations *</label>
                    <div class="rating-options">
                        <div class="rating-option">
                            <input type="radio" id="facility_level_expectations_excellent" name="facility_level_expectations" value="Excellent" required>
                            <label for="facility_level_expectations_excellent">Excellent</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="facility_level_expectations_very_good" name="facility_level_expectations" value="Very Good">
                            <label for="facility_level_expectations_very_good">Very Good</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="facility_level_expectations_good" name="facility_level_expectations" value="Good">
                            <label for="facility_level_expectations_good">Good</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="facility_level_expectations_fair" name="facility_level_expectations" value="Fair">
                            <label for="facility_level_expectations_fair">Fair</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="facility_level_expectations_poor" name="facility_level_expectations" value="Poor">
                            <label for="facility_level_expectations_poor">Poor</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="facility_level_expectations_na" name="facility_level_expectations" value="N/A">
                            <label for="facility_level_expectations_na">N/A</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="question-label">2. The cleanliness of the following</label>

                    <div class="subquestion">
                        <label class="question-label">a. Function Hall / Gym / Auditorium / Seminar Hall / ATS *</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_function_hall_excellent" name="facility_cleanliness_function_hall" value="Excellent" required>
                                <label for="facility_cleanliness_function_hall_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_function_hall_very_good" name="facility_cleanliness_function_hall" value="Very Good">
                                <label for="facility_cleanliness_function_hall_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_function_hall_good" name="facility_cleanliness_function_hall" value="Good">
                                <label for="facility_cleanliness_function_hall_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_function_hall_fair" name="facility_cleanliness_function_hall" value="Fair">
                                <label for="facility_cleanliness_function_hall_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_function_hall_poor" name="facility_cleanliness_function_hall" value="Poor">
                                <label for="facility_cleanliness_function_hall_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_function_hall_na" name="facility_cleanliness_function_hall" value="N/A">
                                <label for="facility_cleanliness_function_hall_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">b. Classrooms & Rooms *</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_classrooms_excellent" name="facility_cleanliness_classrooms" value="Excellent" required>
                                <label for="facility_cleanliness_classrooms_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_classrooms_very_good" name="facility_cleanliness_classrooms" value="Very Good">
                                <label for="facility_cleanliness_classrooms_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_classrooms_good" name="facility_cleanliness_classrooms" value="Good">
                                <label for="facility_cleanliness_classrooms_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_classrooms_fair" name="facility_cleanliness_classrooms" value="Fair">
                                <label for="facility_cleanliness_classrooms_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_classrooms_poor" name="facility_cleanliness_classrooms" value="Poor">
                                <label for="facility_cleanliness_classrooms_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_classrooms_na" name="facility_cleanliness_classrooms" value="N/A">
                                <label for="facility_cleanliness_classrooms_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">c. Restrooms *</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_restrooms_excellent" name="facility_cleanliness_restrooms" value="Excellent" required>
                                <label for="facility_cleanliness_restrooms_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_restrooms_very_good" name="facility_cleanliness_restrooms" value="Very Good">
                                <label for="facility_cleanliness_restrooms_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_restrooms_good" name="facility_cleanliness_restrooms" value="Good">
                                <label for="facility_cleanliness_restrooms_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_restrooms_fair" name="facility_cleanliness_restrooms" value="Fair">
                                <label for="facility_cleanliness_restrooms_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_restrooms_poor" name="facility_cleanliness_restrooms" value="Poor">
                                <label for="facility_cleanliness_restrooms_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_restrooms_na" name="facility_cleanliness_restrooms" value="N/A">
                                <label for="facility_cleanliness_restrooms_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">d. Reception Area *</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_reception_excellent" name="facility_cleanliness_reception" value="Excellent" required>
                                <label for="facility_cleanliness_reception_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_reception_very_good" name="facility_cleanliness_reception" value="Very Good">
                                <label for="facility_cleanliness_reception_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_reception_good" name="facility_cleanliness_reception" value="Good">
                                <label for="facility_cleanliness_reception_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_reception_fair" name="facility_cleanliness_reception" value="Fair">
                                <label for="facility_cleanliness_reception_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_reception_poor" name="facility_cleanliness_reception" value="Poor">
                                <label for="facility_cleanliness_reception_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="facility_cleanliness_reception_na" name="facility_cleanliness_reception" value="N/A">
                                <label for="facility_cleanliness_reception_na">N/A</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="question-label">3. Please rate the function of the following equipment *</label>

                    <div class="subquestion">
                        <label class="question-label">a. Aircondition unit</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="equipment_airconditioning_excellent" name="equipment_airconditioning" value="Excellent">
                                <label for="equipment_airconditioning_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_airconditioning_very_good" name="equipment_airconditioning" value="Very Good">
                                <label for="equipment_airconditioning_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_airconditioning_good" name="equipment_airconditioning" value="Good">
                                <label for="equipment_airconditioning_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_airconditioning_fair" name="equipment_airconditioning" value="Fair">
                                <label for="equipment_airconditioning_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_airconditioning_poor" name="equipment_airconditioning" value="Poor">
                                <label for="equipment_airconditioning_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_airconditioning_na" name="equipment_airconditioning" value="N/A">
                                <label for="equipment_airconditioning_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">b. Lightings</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="equipment_lighting_excellent" name="equipment_lighting" value="Excellent">
                                <label for="equipment_lighting_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_lighting_very_good" name="equipment_lighting" value="Very Good">
                                <label for="equipment_lighting_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_lighting_good" name="equipment_lighting" value="Good">
                                <label for="equipment_lighting_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_lighting_fair" name="equipment_lighting" value="Fair">
                                <label for="equipment_lighting_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_lighting_poor" name="equipment_lighting" value="Poor">
                                <label for="equipment_lighting_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_lighting_na" name="equipment_lighting" value="N/A">
                                <label for="equipment_lighting_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">c. Electric Fans</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="equipment_electric_fans_excellent" name="equipment_electric_fans" value="Excellent">
                                <label for="equipment_electric_fans_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_electric_fans_very_good" name="equipment_electric_fans" value="Very Good">
                                <label for="equipment_electric_fans_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_electric_fans_good" name="equipment_electric_fans" value="Good">
                                <label for="equipment_electric_fans_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_electric_fans_fair" name="equipment_electric_fans" value="Fair">
                                <label for="equipment_electric_fans_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_electric_fans_poor" name="equipment_electric_fans" value="Poor">
                                <label for="equipment_electric_fans_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_electric_fans_na" name="equipment_electric_fans" value="N/A">
                                <label for="equipment_electric_fans_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">d. Tables</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="equipment_tables_excellent" name="equipment_tables" value="Excellent">
                                <label for="equipment_tables_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_tables_very_good" name="equipment_tables" value="Very Good">
                                <label for="equipment_tables_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_tables_good" name="equipment_tables" value="Good">
                                <label for="equipment_tables_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_tables_fair" name="equipment_tables" value="Fair">
                                <label for="equipment_tables_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_tables_poor" name="equipment_tables" value="Poor">
                                <label for="equipment_tables_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_tables_na" name="equipment_tables" value="N/A">
                                <label for="equipment_tables_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">e. Monobloc Chairs</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="equipment_monobloc_chairs_excellent" name="equipment_monobloc_chairs" value="Excellent">
                                <label for="equipment_monobloc_chairs_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_monobloc_chairs_very_good" name="equipment_monobloc_chairs" value="Very Good">
                                <label for="equipment_monobloc_chairs_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_monobloc_chairs_good" name="equipment_monobloc_chairs" value="Good">
                                <label for="equipment_monobloc_chairs_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_monobloc_chairs_fair" name="equipment_monobloc_chairs" value="Fair">
                                <label for="equipment_monobloc_chairs_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_monobloc_chairs_poor" name="equipment_monobloc_chairs" value="Poor">
                                <label for="equipment_monobloc_chairs_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_monobloc_chairs_na" name="equipment_monobloc_chairs" value="N/A">
                                <label for="equipment_monobloc_chairs_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">f. Chair Cover</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="equipment_chair_cover_excellent" name="equipment_chair_cover" value="Excellent">
                                <label for="equipment_chair_cover_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_chair_cover_very_good" name="equipment_chair_cover" value="Very Good">
                                <label for="equipment_chair_cover_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_chair_cover_good" name="equipment_chair_cover" value="Good">
                                <label for="equipment_chair_cover_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_chair_cover_fair" name="equipment_chair_cover" value="Fair">
                                <label for="equipment_chair_cover_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_chair_cover_poor" name="equipment_chair_cover" value="Poor">
                                <label for="equipment_chair_cover_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_chair_cover_na" name="equipment_chair_cover" value="N/A">
                                <label for="equipment_chair_cover_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">g. Podium</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="equipment_podium_excellent" name="equipment_podium" value="Excellent">
                                <label for="equipment_podium_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_podium_very_good" name="equipment_podium" value="Very Good">
                                <label for="equipment_podium_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_podium_good" name="equipment_podium" value="Good">
                                <label for="equipment_podium_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_podium_fair" name="equipment_podium" value="Fair">
                                <label for="equipment_podium_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_podium_poor" name="equipment_podium" value="Poor">
                                <label for="equipment_podium_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_podium_na" name="equipment_podium" value="N/A">
                                <label for="equipment_podium_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">h. Multimedia Projector</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="equipment_multimedia_projector_excellent" name="equipment_multimedia_projector" value="Excellent">
                                <label for="equipment_multimedia_projector_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_multimedia_projector_very_good" name="equipment_multimedia_projector" value="Very Good">
                                <label for="equipment_multimedia_projector_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_multimedia_projector_good" name="equipment_multimedia_projector" value="Good">
                                <label for="equipment_multimedia_projector_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_multimedia_projector_fair" name="equipment_multimedia_projector" value="Fair">
                                <label for="equipment_multimedia_projector_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_multimedia_projector_poor" name="equipment_multimedia_projector" value="Poor">
                                <label for="equipment_multimedia_projector_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_multimedia_projector_na" name="equipment_multimedia_projector" value="N/A">
                                <label for="equipment_multimedia_projector_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">i. Sound System</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="equipment_sound_system_excellent" name="equipment_sound_system" value="Excellent">
                                <label for="equipment_sound_system_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_sound_system_very_good" name="equipment_sound_system" value="Very Good">
                                <label for="equipment_sound_system_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_sound_system_good" name="equipment_sound_system" value="Good">
                                <label for="equipment_sound_system_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_sound_system_fair" name="equipment_sound_system" value="Fair">
                                <label for="equipment_sound_system_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_sound_system_poor" name="equipment_sound_system" value="Poor">
                                <label for="equipment_sound_system_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_sound_system_na" name="equipment_sound_system" value="N/A">
                                <label for="equipment_sound_system_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">j. Microphone</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="equipment_microphone_excellent" name="equipment_microphone" value="Excellent">
                                <label for="equipment_microphone_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_microphone_very_good" name="equipment_microphone" value="Very Good">
                                <label for="equipment_microphone_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_microphone_good" name="equipment_microphone" value="Good">
                                <label for="equipment_microphone_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_microphone_fair" name="equipment_microphone" value="Fair">
                                <label for="equipment_microphone_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_microphone_poor" name="equipment_microphone" value="Poor">
                                <label for="equipment_microphone_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_microphone_na" name="equipment_microphone" value="N/A">
                                <label for="equipment_microphone_na">N/A</label>
                            </div>
                        </div>
                    </div>

                    <div class="subquestion">
                        <label class="question-label">k. Others</label>
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" id="equipment_others_excellent" name="equipment_others" value="Excellent">
                                <label for="equipment_others_excellent">Excellent</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_others_very_good" name="equipment_others" value="Very Good">
                                <label for="equipment_others_very_good">Very Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_others_good" name="equipment_others" value="Good">
                                <label for="equipment_others_good">Good</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_others_fair" name="equipment_others" value="Fair">
                                <label for="equipment_others_fair">Fair</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_others_poor" name="equipment_others" value="Poor">
                                <label for="equipment_others_poor">Poor</label>
                            </div>
                            <div class="rating-option">
                                <input type="radio" id="equipment_others_na" name="equipment_others" value="N/A">
                                <label for="equipment_others_na">N/A</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OVERALL EXPERIENCE SECTION -->
            <div class="form-section">
                <div class="section-title">⭐ OVERALL EXPERIENCE</div>

                <div class="form-group">
                    <label class="question-label">1. Would you rent this facility again? *</label>
                    <div class="rating-options">
                        <div class="rating-option">
                            <input type="radio" id="overall_would_rent_again_yes" name="overall_would_rent_again" value="Yes" required>
                            <label for="overall_would_rent_again_yes">Yes</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="overall_would_rent_again_no" name="overall_would_rent_again" value="No">
                            <label for="overall_would_rent_again_no">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="question-label">2. Would you recommend this facility to others? *</label>
                    <div class="rating-options">
                        <div class="rating-option">
                            <input type="radio" id="overall_would_recommend_yes" name="overall_would_recommend" value="Yes" required>
                            <label for="overall_would_recommend_yes">Yes</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="overall_would_recommend_no" name="overall_would_recommend" value="No">
                            <label for="overall_would_recommend_no">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="question-label">How did you find out about this facility? *</label>
                    <select name="overall_how_found_facility" required>
                        <option value="">-- Select an option --</option>
                        <option value="Website">Website</option>
                        <option value="Brochure">Brochure</option>
                        <option value="Friend">Friend</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
            </div>

            <!-- COMMENTS SECTION -->
            <div class="form-section">
                <div class="section-title">💬 COMMENTS/SUGGESTIONS</div>

                <div class="form-group">
                    <label class="question-label">Please share any comments or suggestions for improvement:</label>
                    <textarea name="comments_suggestions" placeholder="Your feedback is valuable to us..."></textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="reset" class="btn btn-reset">Clear Form</button>
                <button type="submit" class="btn btn-submit">Submit Survey</button>
            </div>

            <div class="loading" id="loadingIndicator">
                <div class="spinner"></div>
                <p>Submitting your survey...</p>
            </div>
        </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('surveyForm');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');

        // Add focus listeners to form groups
        document.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('focus', function() {
                const group = this.closest('.form-group');
                if (group) group.classList.add('focused');
            });
            field.addEventListener('blur', function() {
                const group = this.closest('.form-group');
                if (group) group.classList.remove('focused');
            });
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validate all required fields
            const requiredFields = form.querySelectorAll('[required]');
            let allFilled = true;

            requiredFields.forEach(field => {
                if (field.type === 'radio') {
                    const radioGroup = form.querySelector(`input[name="${field.name}"]:checked`);
                    if (!radioGroup) {
                        allFilled = false;
                    }
                } else if (!field.value) {
                    allFilled = false;
                }
            });

            if (!allFilled) {
                showError('Please fill in all required fields before submitting.');
                return;
            }

            const formData = new FormData(form);

            loadingIndicator.style.display = 'block';
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';

            try {
                const response = await fetch('<?= base_url("survey/submit") ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    showError(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('An error occurred while submitting the survey. Please try again.');
            } finally {
                loadingIndicator.style.display = 'none';
            }
        });

        function showError(message) {
            errorMessage.querySelector('span').textContent = message;
            errorMessage.style.display = 'flex';
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function showSuccess(message) {
            successMessage.querySelector('span').textContent = message;
            successMessage.style.display = 'flex';
            successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    </script>
</body>
</html>
