 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilitator Dashboard | CSPC Facilities Management</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --accent-color: #f39c12;
            --success-color: #22c55e;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --light-bg: #f8fafc;
            --dark-text: #1e293b;
            --border-color: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.8);
            padding: 15px 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1e3c72 !important;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cspc-logo-nav {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
        }

        .navbar-nav .nav-link {
            color: #1e293b !important;
            font-weight: 600;
            margin: 0 15px;
            padding: 12px 20px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: #1e3c72 !important;
            background: rgba(30, 60, 114, 0.1);
            transform: translateY(-2px);
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
            border-radius: 16px;
            padding: 10px;
        }

        .dropdown-item {
            padding: 12px 20px;
            border-radius: 12px;
            transition: all 0.3s ease;
            margin-bottom: 5px;
        }

        .dropdown-item:hover {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            color: white;
            transform: translateX(5px);
        }

        .dropdown-item i {
            margin-right: 10px;
            width: 20px;
        }

        /* Main Container */
        .main-content {
            padding: 40px;
            min-height: calc(100vh - 76px);
        }

        .dashboard-header {
            margin-bottom: 40px;
            animation: fadeInUp 0.6s ease-out;
        }

        .dashboard-header h2 {
            color: var(--dark-text);
            font-weight: 800;
            margin-bottom: 8px;
            font-size: 2.2rem;
        }

        .dashboard-header p {
            color: #64748b;
            margin: 0;
            font-size: 1.1rem;
        }

        /* Content Layout */
        .content-wrapper {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 30px;
            min-height: 600px;
        }

        /* Events Panel */
        .events-panel {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(226, 232, 240, 0.8);
            padding: 30px;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 250px);
        }

        .events-panel h3 {
            color: var(--dark-text);
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Search Box */
        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 18px;
            transition: all 0.3s ease;
        }

        .search-box:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.1);
        }

        .search-box i {
            color: var(--primary-color);
            margin-right: 10px;
            font-size: 14px;
        }

        .search-box input {
            border: none;
            outline: none;
            font-size: 13px;
            flex: 1;
            color: var(--dark-text);
            background: transparent;
        }

        .search-box input::placeholder {
            color: #cbd5e1;
        }

        .events-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            overflow-y: auto;
            flex: 1;
            padding-right: 8px;
        }

        .events-list::-webkit-scrollbar {
            width: 8px;
        }

        .events-list::-webkit-scrollbar-track {
            background: #f1f3f5;
            border-radius: 10px;
        }

        .events-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .events-list::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .event-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 16px;
            padding: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            min-height: auto;
        }

        .event-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.15);
            transform: translateX(5px);
        }

        .event-item.active {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.1), rgba(42, 82, 152, 0.05));
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.2);
        }

        .event-item.inspected-event {
            border-color: rgba(34, 197, 94, 0.3);
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.08), rgba(34, 197, 94, 0.03));
        }

        .event-item.inspected-event:hover {
            border-color: #22c55e;
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.2);
            transform: translateX(5px);
        }

        .event-item-title {
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 10px;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .event-item-info {
            font-size: 13px;
            color: #64748b;
            line-height: 1.8;
            margin-bottom: 12px;
        }

        .event-item-info i {
            display: inline-flex;
            align-items: center;
            width: 18px;
            margin-right: 8px;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .event-item-date {
            display: inline-block;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 11px;
            margin-top: auto;
            font-weight: 700;
            text-align: center;
            width: 100%;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h5 {
            color: var(--dark-text);
            margin-bottom: 10px;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 40px 20px;
            color: var(--primary-color);
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Checklist Panel */
        .checklist-panel {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(226, 232, 240, 0.8);
            padding: 30px;
            display: none;
            animation: slideIn 0.4s ease;
            max-height: calc(100vh - 250px);
            overflow-y: auto;
        }

        .checklist-panel.active {
            display: flex;
            flex-direction: column;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Toast Notifications */
        #toastContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 400px;
            pointer-events: none;
        }

        .toast {
            padding: 16px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            animation: slideInRight 0.3s ease;
            pointer-events: auto;
            backdrop-filter: blur(10px);
        }

        .toast i {
            font-size: 18px;
            flex-shrink: 0;
        }

        .toast-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
        }

        .toast-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
        }

        .toast-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        .toast.hide {
            animation: slideOutRight 0.3s ease forwards;
        }

        /* Confirmation Modal */
        .confirm-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .confirm-overlay.show {
            background: rgba(0, 0, 0, 0.5);
            opacity: 1;
        }

        .confirm-modal {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 420px;
            width: 90%;
            animation: slideUp 0.3s ease;
            overflow: hidden;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .confirm-header {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .confirm-header i {
            font-size: 28px;
        }

        .confirm-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .confirm-body {
            padding: 24px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        .confirm-body p {
            margin: 0;
        }

        .confirm-footer {
            padding: 20px 24px;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .confirm-footer .btn {
            min-width: 120px;
            padding: 10px 16px;
            font-size: 13px;
        }

        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert i {
            font-size: 18px;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            color: #15803d;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            color: #7f1d1d;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.15);
            color: #1e40af;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        /* Event Details Card */
        .event-details {
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.08), rgba(42, 82, 152, 0.05));
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid var(--primary-color);
        }

        .event-details h3 {
            color: var(--dark-text);
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 800;
        }

        .event-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            font-size: 14px;
        }

        .detail-item {
            color: #64748b;
        }

        .detail-label {
            font-weight: 700;
            color: var(--dark-text);
            display: inline-block;
            min-width: 90px;
        }

        /* Progress Bar */
        .progress-container {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }

        .progress {
            background: #e2e8f0;
            height: 10px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success-color), #16a34a);
            transition: width 0.4s ease;
            border-radius: 10px;
        }

        .progress-text {
            font-size: 12px;
            color: var(--dark-text);
            font-weight: 600;
            margin-top: 5px;
        }

        /* Equipment Container */
        .equipment-container {
            flex: 1;
            margin-bottom: 20px;
        }

        /* Inspected Event Message */
        .inspected-message {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05));
            border: 2px solid rgba(34, 197, 94, 0.3);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            animation: slideIn 0.4s ease;
        }

        .inspected-icon {
            font-size: 60px;
            color: #22c55e;
            margin-bottom: 20px;
            animation: bounceIn 0.6s ease;
        }

        .inspected-message h3 {
            color: #22c55e;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .inspected-message p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .inspected-info {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .inspected-info p {
            margin-bottom: 12px;
            text-align: left;
        }

        .inspected-info strong {
            color: var(--dark-text);
        }

        .status-badge.completed {
            background: linear-gradient(45deg, #22c55e, #16a34a);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 12px;
            display: inline-block;
            margin-top: 5px;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0.3);
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            70% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        .equipment-section {
            margin-bottom: 25px;
        }

        .equipment-section-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--dark-text);
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.1), rgba(42, 82, 152, 0.05));
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .equipment-item {
            background: #f8fafc;
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .equipment-item:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .equipment-name {
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 15px;
        }

        .quantity-badge {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-options {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }

        .status-btn {
            padding: 12px 8px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: white;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .status-btn i {
            font-size: 20px;
        }

        .status-btn:hover {
            border-color: var(--primary-color);
            background: rgba(30, 60, 114, 0.05);
            transform: translateY(-2px);
        }

        .status-btn.selected {
            border-width: 3px;
        }

        .status-btn.good.selected {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }

        .status-btn.damaged.selected {
            background: var(--danger-color);
            border-color: var(--danger-color);
            color: white;
        }

        .status-btn.missing.selected {
            background: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        .notes-input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
            min-height: 50px;
            transition: all 0.3s ease;
        }

        .notes-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(30, 60, 114, 0.1);
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid var(--border-color);
        }

        .btn {
            border-radius: 12px;
            font-weight: 700;
            padding: 14px 28px;
            transition: all 0.3s ease;
            border: none;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.3);
            flex: 1;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(30, 60, 114, 0.4);
            color: white;
        }

        .btn-outline-secondary {
            border: 2px solid #64748b;
            color: #64748b;
            background: transparent;
        }

        .btn-outline-secondary:hover {
            background: #64748b;
            border-color: #64748b;
            color: white;
            transform: translateY(-3px);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Generated Files Section */
        .generated-files-section {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.05), rgba(34, 197, 94, 0.02));
            border: 2px solid rgba(34, 197, 94, 0.2);
            border-radius: 12px;
            animation: slideIn 0.4s ease;
        }

        .generated-files-section h4 {
            color: #22c55e;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .generated-files-section i {
            margin-right: 8px;
        }

        .files-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .file-item {
            background: white;
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid rgba(34, 197, 94, 0.3);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            gap: 12px;
        }

        .file-item:hover {
            background: rgba(34, 197, 94, 0.05);
            border-color: rgba(34, 197, 94, 0.6);
            transform: translateX(5px);
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }

        .file-icon {
            font-size: 20px;
            color: #f59e0b;
            flex-shrink: 0;
        }

        .file-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
            flex: 1;
        }

        .file-name {
            font-weight: 600;
            color: var(--dark-text);
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-meta {
            font-size: 11px;
            color: #94a3b8;
        }

        .file-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn-download {
            background: linear-gradient(45deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-download:active {
            transform: translateY(0);
        }

        .btn-delete {
            background: linear-gradient(45deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-delete:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
            color: white;
        }

        .btn-delete:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-delete:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-wrapper {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .main-content {
                padding: 20px;
            }

            .events-panel, .checklist-panel {
                max-height: 500px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header h2 {
                font-size: 1.8rem;
            }

            .status-options {
                grid-template-columns: repeat(2, 1fr);
            }

            .event-details-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Equipment Reports Summary */
        .reports-summary-section {
            background: white;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 28px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 2px solid var(--border-color);
            animation: slideIn 0.4s ease;
        }

        .reports-summary-section h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .reports-summary-section i {
            color: var(--primary-color);
            font-size: 24px;
        }

        .summary-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary-color);
            line-height: 1;
        }

        .stat-card.good .stat-value { color: var(--success-color); }
        .stat-card.damaged .stat-value { color: var(--danger-color); }
        .stat-card.missing .stat-value { color: #6c757d; }

        /* Equipment Quantity Breakdown */
        .equipment-quantity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .equipment-qty-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            transition: all 0.3s ease;
        }

        .equipment-qty-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .equipment-qty-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .equipment-qty-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            font-size: 12px;
        }

        .qty-stat {
            text-align: center;
            padding: 8px;
            border-radius: 8px;
            background: #f8fafc;
        }

        .qty-stat-label {
            font-weight: 600;
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .qty-stat-value {
            font-size: 16px;
            font-weight: 700;
        }

        .qty-stat.good {
            background: rgba(34, 197, 94, 0.1);
        }

        .qty-stat.good .qty-stat-value {
            color: var(--success-color);
        }

        .qty-stat.damaged {
            background: rgba(239, 68, 68, 0.1);
        }

        .qty-stat.damaged .qty-stat-value {
            color: var(--danger-color);
        }

        .qty-stat.missing {
            background: rgba(107, 114, 128, 0.1);
        }

        .qty-stat.missing .qty-stat-value {
            color: #6c757d;
        }

        .qty-stat.total {
            background: rgba(30, 60, 114, 0.1);
        }

        .qty-stat.total .qty-stat-value {
            color: var(--primary-color);
        }

        .reports-table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .reports-table thead {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .reports-table thead th {
            padding: 16px;
            font-weight: 700;
            text-align: left;
            white-space: nowrap;
        }

        .reports-table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .reports-table tbody tr:hover {
            background: #f8fafc;
            box-shadow: inset 0 0 10px rgba(30, 60, 114, 0.05);
        }

        .reports-table tbody td {
            padding: 14px 16px;
        }

        .equipment-name-cell {
            font-weight: 600;
            color: var(--dark-text);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            min-width: 80px;
        }

        .status-badge.good {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .status-badge.damaged {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-badge.missing {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            border: 1px solid rgba(108, 117, 125, 0.3);
        }

        .empty-reports {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }

        .empty-reports i {
            font-size: 48px;
            color: var(--border-color);
            margin-bottom: 12px;
            display: block;
        }

        .btn-download-report {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .btn-download-report:hover:not(:disabled) {
            background-color: #15b34b;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
        }

        .btn-download-report:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="/">
                <div class="cspc-logo-nav">
                    <i class="fas fa-building"></i>
                </div>
                CSPC Sphere
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> Profile
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-header">
            <h2><i class="fas fa-clipboard-check"></i> Equipment Inspection Dashboard</h2>
            <p>Review and report on facility equipment condition after events</p>
        </div>

        <!-- Equipment Reports Summary Section -->
        <div class="reports-summary-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3><i class="fas fa-chart-bar"></i> Equipment Reports Summary</h3>
                <button class="btn-download-report" onclick="downloadReportSummary()" title="Download as Excel">
                    <i class="fas fa-download"></i> Download Report
                </button>
            </div>
            
            <!-- Summary Statistics -->
            <div class="summary-stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Equipment Inspected</div>
                    <div class="stat-value" id="totalEquipmentCount">0</div>
                </div>
                <div class="stat-card good">
                    <div class="stat-label">In Good Condition</div>
                    <div class="stat-value" id="goodConditionCount">0</div>
                </div>
                <div class="stat-card damaged">
                    <div class="stat-label">Damaged/Maintenance</div>
                    <div class="stat-value" id="damagedCount">0</div>
                </div>
                <div class="stat-card missing">
                    <div class="stat-label">Missing Equipment</div>
                    <div class="stat-value" id="missingCount">0</div>
                </div>
            </div>

        </div>

        <div class="content-wrapper">
            <!-- Events List Panel -->
            <div class="events-panel">
                <h3><i class="fas fa-calendar-check"></i> Completed Events</h3>
                
                <!-- Search Bar -->
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="facilitySearch" placeholder="Search facility or event..." onkeyup="filterEvents()">
                </div>

                <div class="events-list" id="eventsList">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Loading events...</p>
                    </div>
                </div>
            </div>

            <!-- Checklist Panel -->
            <div class="checklist-panel" id="checklistPanel">
                <div id="alertContainer"></div>

                <!-- Event Details -->
                <div class="event-details" id="eventDetails" style="display: none;">
                    <h3 id="eventTitle"></h3>
                    <div class="event-details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Facility:</span> <span id="eventFacility"></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date:</span> <span id="eventDate"></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Organizer:</span> <span id="eventOrganizer"></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Attendees:</span> <span id="eventAttendees"></span>
                        </div>
                    </div>
                    <div class="progress-container">
                        <span class="detail-label">Inspection Progress:</span>
                        <div class="progress">
                            <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                        </div>
                        <div class="progress-text" id="progressText">0 of 0 items checked</div>
                    </div>
                </div>

                <!-- Equipment Checklist -->
                <div class="equipment-container" id="equipmentContainer"></div>

                <!-- Generated Files Section -->
                <div class="generated-files-section" id="generatedFilesSection" style="display: none;">
                    <h4><i class="fas fa-file-pdf"></i> Generated Inspection Reports</h4>
                    <div id="generatedFilesList" class="files-list"></div>
                </div>

                <!-- Action Buttons -->
                <div class="actions" id="actions" style="display: none;">
                    <button class="btn btn-primary" id="generateBtn" onclick="generateReport()">
                        <i class="fas fa-file-excel"></i> Generate Report
                    </button>
                    <button class="btn btn-success" id="doneBtn" onclick="markInspectionDone()">
                        <i class="fas fa-check"></i> Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedEventId = null;
        let equipmentStatuses = {};
        let totalEquipment = 0;

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadEvents();
            loadEquipmentReportsSummary();
        });

        // Load completed events
        function loadEvents() {
            const eventsList = document.getElementById('eventsList');
            eventsList.innerHTML = '<div class="loading"><div class="spinner"></div><p>Loading events...</p></div>';

            fetch('/api/events/completed')
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.events.length) {
                        eventsList.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <h5>No Events Available</h5>
                                <p>No completed events require inspection at this time.</p>
                            </div>`;
                        return;
                    }

                    eventsList.innerHTML = data.events.map(event => {
                        const isInspected = event.is_inspected == 1;
                        const lastInspectionDate = isInspected ? new Date(event.last_inspection_date).toLocaleString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : '';
                        
                        // Show booking status badge
                        const bookingStatus = event.booking_status === 'confirmed' ? 
                            '<span class="booking-status-badge" style="display: inline-block; background: linear-gradient(45deg, #22c55e, #16a34a); color: white; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; margin-left: 5px;">✓ CONFIRMED</span>' : '';
                        
                        return `
                            <div class="event-item" onclick="selectEvent(${event.id}, this)" data-event-id="${event.id}">
                                <div class="event-item-title">
                                    ${event.event_title}
                                    ${bookingStatus}
                                    <span class="inspect-label" style="margin-left: 8px; background: #f59e0b; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">🔍 INSPECT</span>
                                    <span class="inspected-label" style="display: none; margin-left: 8px; background: #22c55e; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">✓ INSPECTED</span>
                                </div>
                                <div class="event-item-info">
                                    <div><i class="fas fa-map-marker-alt"></i>${event.facility_name}</div>
                                    <div><i class="fas fa-user"></i>${event.client_name}</div>
                                    <div style="font-size: 12px; color: #22c55e; margin-top: 4px;"><i class="fas fa-bookmark"></i> Booking: #${event.booking_id}</div>
                                    <div class="inspection-date" style="display: none; color: #22c55e; font-size: 12px; margin-top: 4px;"><i class="fas fa-check-circle"></i> Inspected: <span>${lastInspectionDate}</span></div>
                                </div>
                                <div class="event-item-date"><i class="fas fa-calendar"></i> ${formatDate(event.event_date)}</div>
                            </div>
                        `;
                    }).join('');

                    // Check each event for generated files
                    data.events.forEach(event => {
                        const eventElement = document.querySelector(`[data-event-id="${event.id}"]`);
                        if (eventElement) {
                            fetch(`/api/events/${event.id}/generated-files`)
                                .then(r => r.json())
                                .then(fileData => {
                                    if (fileData.success && fileData.files && fileData.files.length > 0) {
                                        // Show the inspected label and hide inspect label
                                        eventElement.classList.add('inspected-event');
                                        eventElement.querySelector('.inspected-label').style.display = 'inline-block';
                                        eventElement.querySelector('.inspect-label').style.display = 'none';
                                        eventElement.querySelector('.inspection-date').style.display = 'block';
                                    }
                                    // If no files, keep the "Inspect" label showing (already default)
                                })
                                .catch(e => console.error('Error checking files for event', event.id, e));
                        }
                    });
                })
                .catch(e => {
                    console.error('Error loading events:', e);
                    eventsList.innerHTML = '<div class="empty-state"><p>Error loading events. Please refresh the page.</p></div>';
                });
        }

        // Select event and load equipment
        function selectEvent(eventId, element) {
            selectedEventId = eventId;
            
            // Update active state
            document.querySelectorAll('.event-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');

            // Show checklist panel
            document.getElementById('checklistPanel').classList.add('active');
            equipmentStatuses = {};

            // First, check if event has generated files
            fetch(`/api/events/${eventId}/generated-files`)
                .then(r => r.json())
                .then(filesData => {
                    const hasGeneratedFiles = filesData.success && filesData.files && filesData.files.length > 0;

                    // Load event details
                    return fetch(`/api/events/checklist/${eventId}`)
                        .then(r => r.json())
                        .then(data => {
                            if (!data.success) throw new Error(data.message);

                            const event = data.event;
                            
                            // Populate event details
                            document.getElementById('eventTitle').textContent = event.event_title;
                            document.getElementById('eventFacility').textContent = event.facility_name;
                            document.getElementById('eventDate').textContent = formatDate(event.event_date) + ' at ' + event.event_time;
                            document.getElementById('eventOrganizer').textContent = event.organization || event.client_name;
                            document.getElementById('eventAttendees').textContent = event.attendees || 'N/A';
                            document.getElementById('eventDetails').style.display = 'block';

                            // Check if event has generated files (actual inspection completed)
                            if (hasGeneratedFiles) {
                                // Show read-only mode with message and generated files only
                                showInspectedEventView(eventId);
                            } else {
                                // Show editable inspection form
                                // Render equipment
                                totalEquipment = data.equipment.length;
                                renderEquipment(data.equipment);
                                document.getElementById('actions').style.display = 'flex';
                                document.getElementById('generatedFilesSection').style.display = 'none';
                                document.getElementById('equipmentContainer').style.display = 'block';
                                updateProgress();

                                showAlert('Event loaded successfully! Please inspect all equipment.', 'success');
                            }

                            // Load generated files for both inspected and non-inspected
                            loadGeneratedFiles(eventId);
                        });
                })
                .catch(e => {
                    console.error('Error:', e);
                    showAlert('Failed to load event details: ' + e.message, 'error');
                });
        }

        // Show read-only view for already inspected events
        function showInspectedEventView(eventId) {
            // Hide the equipment form and action buttons
            document.getElementById('equipmentContainer').style.display = 'none';
            document.getElementById('actions').style.display = 'none';
            
            // Show a message that event is already inspected
            const container = document.getElementById('equipmentContainer');
            container.innerHTML = `
                <div class="inspected-message">
                    <div class="inspected-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Event Already Inspected</h3>
                    <p>This event has been inspected and the equipment condition report has been generated.</p>
                    <div class="inspected-info">
                        <p><strong>Status:</strong> <span class="status-badge completed">✓ Completed</span></p>
                        <p><strong>View your inspection reports below:</strong></p>
                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                            <button class="btn btn-primary" onclick="startNewInspection(${eventId})" style="flex: 1;">
                                <i class="fas fa-plus-circle"></i> Create New Inspection
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.style.display = 'block';
        }

        // Start a new inspection for an already inspected event
        function startNewInspection(eventId) {
            // Show warning dialog before starting new inspection
            const overlay = document.createElement('div');
            overlay.className = 'confirm-overlay';
            overlay.id = 'newInspectionOverlay';
            overlay.style.zIndex = '1050';

            const modal = document.createElement('div');
            modal.className = 'confirm-modal';
            modal.style.maxWidth = '500px';
            modal.innerHTML = `
                <div class="confirm-header" style="background: linear-gradient(45deg, #f59e0b, #d97706); color: white;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3 style="margin: 0; color: white;">Start New Inspection?</h3>
                </div>
                <div class="confirm-body" style="padding: 20px; color: #1e293b;">
                    <p style="margin-bottom: 15px;"><strong>⚠️ Warning:</strong> This event has already been inspected. Starting a new inspection will:</p>
                    <ul style="margin: 15px 0; padding-left: 20px;">
                        <li>Reset all equipment quantities to 0</li>
                        <li>Allow you to record new equipment conditions</li>
                        <li>Update the equipment inventory with new values</li>
                        <li>Keep the previous inspection report for reference</li>
                    </ul>
                    <p style="margin-top: 15px; padding: 12px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                        <strong>Previous inspection data will remain accessible</strong> but the equipment table will be updated with the new inspection results.
                    </p>
                </div>
                <div class="confirm-footer">
                    <button class="btn btn-outline-secondary" onclick="closeNewInspectionDialog()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-warning" onclick="proceedNewInspection(${eventId})" style="background: linear-gradient(45deg, #f59e0b, #d97706); border: none; color: white;">
                        <i class="fas fa-check"></i> Start New Inspection
                    </button>
                </div>
            `;

            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            // Show with animation
            setTimeout(() => overlay.classList.add('show'), 10);
        }

        // Close new inspection dialog
        function closeNewInspectionDialog() {
            const overlay = document.getElementById('newInspectionOverlay');
            if (overlay) {
                overlay.classList.remove('show');
                setTimeout(() => overlay.remove(), 300);
            }
        }

        // Proceed with new inspection
        function proceedNewInspection(eventId) {
            closeNewInspectionDialog();

            // Reload the event checklist to show the inspection form
            fetch(`/api/events/checklist/${eventId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message);

                    // Show the inspection form
                    totalEquipment = data.equipment.length;
                    renderEquipment(data.equipment);
                    document.getElementById('actions').style.display = 'flex';
                    document.getElementById('generatedFilesSection').style.display = 'none';
                    document.getElementById('equipmentContainer').style.display = 'block';
                    updateProgress();

                    showAlert('✓ New inspection started! Please update the equipment condition.', 'success');
                })
                .catch(e => {
                    console.error('Error:', e);
                    showAlert('Failed to start new inspection: ' + e.message, 'error');
                });
        }

        // Custom confirmation dialog (styled modal)
        function showConfirmDialog(title, message, onConfirm) {
            // Create modal overlay
            const overlay = document.createElement('div');
            overlay.className = 'confirm-overlay';
            overlay.id = 'confirmOverlay';

            // Create modal dialog
            const modal = document.createElement('div');
            modal.className = 'confirm-modal';
            modal.innerHTML = `
                <div class="confirm-header">
                    <i class="fas fa-question-circle"></i>
                    <h3>${title}</h3>
                </div>
                <div class="confirm-body">
                    <p>${message}</p>
                </div>
                <div class="confirm-footer">
                    <button class="btn btn-outline-secondary" onclick="closeConfirmDialog()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-primary" onclick="confirmDialog()">
                        <i class="fas fa-check"></i> Confirm
                    </button>
                </div>
            `;

            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            // Store the callback
            window.confirmCallback = onConfirm;

            // Show with animation
            setTimeout(() => overlay.classList.add('show'), 10);
        }

        // Close confirm dialog
        function closeConfirmDialog() {
            const overlay = document.getElementById('confirmOverlay');
            if (overlay) {
                overlay.classList.remove('show');
                setTimeout(() => overlay.remove(), 300);
                window.confirmCallback = null;
            }
        }

        // Execute confirm callback
        function confirmDialog() {
            if (window.confirmCallback) {
                window.confirmCallback();
            }
            closeConfirmDialog();
        }
        function renderEquipment(equipment) {
            const container = document.getElementById('equipmentContainer');
            
            if (!equipment.length) {
                container.innerHTML = '<div class="empty-state"><p>No equipment recorded for this event</p></div>';
                return;
            }

            const grouped = {};
            equipment.forEach(item => {
                const cat = item.category || 'Other';
                if (!grouped[cat]) grouped[cat] = [];
                grouped[cat].push(item);
            });

            let html = '';
            for (const [category, items] of Object.entries(grouped)) {
                html += `
                    <div class="equipment-section">
                        <div class="equipment-section-title">
                            <i class="fas fa-box"></i> ${category.replace('_', ' ').toUpperCase()}
                        </div>`;
                
                items.forEach(item => {
                    const sourceType = item.source_type || 'unknown';
                    const sourceBadge = sourceType === 'rental' ? '🛒 Rented' : (sourceType === 'plan' ? '📦 Plan' : '');
                    html += `
                        <div class="equipment-item">
                            <div class="equipment-name">
                                <span><i class="fas fa-tools"></i> ${item.name}</span>
                                <span class="quantity-badge">Expected: ${item.quantity || 1}</span>
                                ${sourceBadge ? `<span class="quantity-badge" style="margin-left: 5px; background: ${sourceType === 'rental' ? '#28a745' : '#007bff'}">${sourceBadge}</span>` : ''}
                            </div>
                            <div style="margin-bottom: 12px; background: #f8f9fa; padding: 12px; border-radius: 8px;">
                                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--dark-text); margin-bottom: 12px;">
                                    <i class="fas fa-calculator"></i> Quantity Breakdown (Total: ${item.quantity || 1}):
                                </label>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                                    <div>
                                        <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Good</label>
                                        <input
                                            type="number"
                                            class="quantity-input good-qty"
                                            id="good-qty-${item.equipment_id}"
                                            placeholder="0"
                                            value="0"
                                            min="0"
                                            max="${item.quantity || 1}"
                                            onchange="validateQuantityTotal(${item.equipment_id}, ${item.quantity || 1}); updateEquipmentStatus(${item.equipment_id}, ${item.quantity || 1}, '${sourceType}')"
                                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; text-align: center;"
                                        />
                                    </div>
                                    <div>
                                        <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Damaged</label>
                                        <input
                                            type="number"
                                            class="quantity-input damaged-qty"
                                            id="damaged-qty-${item.equipment_id}"
                                            placeholder="0"
                                            value="0"
                                            min="0"
                                            max="${item.quantity || 1}"
                                            onchange="validateQuantityTotal(${item.equipment_id}, ${item.quantity || 1}); updateEquipmentStatus(${item.equipment_id}, ${item.quantity || 1}, '${sourceType}')"
                                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; text-align: center;"
                                        />
                                    </div>
                                    <div>
                                        <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Missing</label>
                                        <input
                                            type="number"
                                            class="quantity-input missing-qty"
                                            id="missing-qty-${item.equipment_id}"
                                            placeholder="0"
                                            value="0"
                                            min="0"
                                            max="${item.quantity || 1}"
                                            onchange="validateQuantityTotal(${item.equipment_id}, ${item.quantity || 1}); updateEquipmentStatus(${item.equipment_id}, ${item.quantity || 1}, '${sourceType}')"
                                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; text-align: center;"
                                        />
                                    </div>
                                </div>
                                <div id="qty-error-${item.equipment_id}" style="font-size: 12px; color: #ef4444; margin-top: 8px; display: none;">
                                    Total cannot exceed ${item.quantity || 1}
                                </div>
                            </div>
                            <textarea class="notes-input" placeholder="Add notes (optional)" id="notes-${item.equipment_id}" onchange="updateEquipmentStatus(${item.equipment_id}, ${item.quantity || 1}, '${sourceType}')"></textarea>
                        </div>`;
                });
                
                html += '</div>';
            }

            container.innerHTML = html;
        }

        // Update equipment status when quantity changes
        function updateEquipmentStatus(equipmentId, expectedQty, source) {
            const goodQty = parseInt(document.getElementById(`good-qty-${equipmentId}`)?.value) || 0;
            const damagedQty = parseInt(document.getElementById(`damaged-qty-${equipmentId}`)?.value) || 0;
            const missingQty = parseInt(document.getElementById(`missing-qty-${equipmentId}`)?.value) || 0;
            const totalQty = goodQty + damagedQty + missingQty;

            // Only add to statuses if at least one quantity is set
            if (totalQty > 0) {
                // Determine overall status
                let status = 'good';
                if (damagedQty > 0 && missingQty > 0) {
                    status = 'damaged'; // If both damaged and missing, mark as damaged
                } else if (damagedQty > 0) {
                    status = 'damaged';
                } else if (missingQty > 0) {
                    status = 'missing';
                }

                equipmentStatuses[equipmentId] = {
                    equipment_id: equipmentId,
                    status: status,
                    expected_quantity: expectedQty,
                    good_quantity: goodQty,
                    damaged_quantity: damagedQty,
                    missing_quantity: missingQty,
                    total_quantity: totalQty,
                    source: source,
                    notes: document.getElementById(`notes-${equipmentId}`)?.value || ''
                };
            } else {
                // Remove from statuses if no quantities set
                delete equipmentStatuses[equipmentId];
            }

            updateProgress();
        }

        // Validate quantity total
        function validateQuantityTotal(equipmentId, expectedQty) {
            const goodQty = parseInt(document.getElementById(`good-qty-${equipmentId}`)?.value) || 0;
            const damagedQty = parseInt(document.getElementById(`damaged-qty-${equipmentId}`)?.value) || 0;
            const missingQty = parseInt(document.getElementById(`missing-qty-${equipmentId}`)?.value) || 0;
            const totalQty = goodQty + damagedQty + missingQty;

            const errorDiv = document.getElementById(`qty-error-${equipmentId}`);
            const inputs = document.querySelectorAll(`#good-qty-${equipmentId}, #damaged-qty-${equipmentId}, #missing-qty-${equipmentId}`);

            if (totalQty > expectedQty) {
                errorDiv.style.display = 'block';
                inputs.forEach(input => {
                    input.style.borderColor = '#ef4444';
                    input.style.backgroundColor = '#fee2e2';
                });
                
                // Reset the last edited field to prevent exceeding
                const lastInput = event.target;
                if (lastInput) {
                    const currentValue = parseInt(lastInput.value) || 0;
                    const maxAllowed = expectedQty - (totalQty - currentValue);
                    if (currentValue > maxAllowed) {
                        lastInput.value = Math.max(0, maxAllowed);
                    }
                }
            } else {
                errorDiv.style.display = 'none';
                inputs.forEach(input => {
                    input.style.borderColor = '#ddd';
                    input.style.backgroundColor = '';
                });
            }
        }

        // Update progress bar
        function updateProgress() {
            const completed = Object.keys(equipmentStatuses).length;
            const percentage = totalEquipment > 0 ? (completed / totalEquipment) * 100 : 0;
            
            document.getElementById('progressFill').style.width = percentage + '%';
            document.getElementById('progressText').textContent = `${completed} of ${totalEquipment} items checked`;
        }

        // Clear checklist
        function clearChecklist() {
            equipmentStatuses = {};
            document.querySelectorAll('.status-btn.selected').forEach(b => b.classList.remove('selected'));
            document.querySelectorAll('.notes-input').forEach(input => input.value = '');

            // Reset quantity inputs
            document.querySelectorAll('[id^="good-qty-"]').forEach(input => input.value = '0');
            document.querySelectorAll('[id^="damaged-qty-"]').forEach(input => input.value = '0');
            document.querySelectorAll('[id^="missing-qty-"]').forEach(input => input.value = '0');
            document.querySelectorAll('[id^="qty-error-"]').forEach(div => div.style.display = 'none');

            updateProgress();
        }

        // Mark inspection as done and update equipment table
        function markInspectionDone() {
            if (!selectedEventId) {
                showAlert('No event selected', 'error');
                return;
            }

            const statuses = Object.values(equipmentStatuses);

            if (!statuses.length) {
                showAlert('Please inspect at least one equipment item before marking as done', 'error');
                return;
            }

            if (statuses.length < totalEquipment) {
                if (!confirm(`You have only checked ${statuses.length} of ${totalEquipment} items. Mark as done anyway?`)) {
                    return;
                }
            }

            const btn = document.getElementById('doneBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            fetch(`/api/events/${selectedEventId}/update-equipment`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ equipment_statuses: statuses })
            })
                .then(async response => {
                    const contentType = response.headers.get('content-type');
                    let data;
                    
                    if (contentType && contentType.includes('application/json')) {
                        data = await response.json();
                    } else {
                        const text = await response.text();
                        console.error('Response text:', text);
                        throw new Error('Invalid response format from server');
                    }
                    
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to update equipment (HTTP ' + response.status + ')');
                    }
                    return data;
                })
                .then(data => {
                    // Show inspection completed message with info
                    const submittedDate = new Date(data.submitted_at).toLocaleString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });

                    const container = document.getElementById('alertContainer');
                    container.innerHTML = `
                        <div style="background: rgba(34, 197, 94, 0.15); border: 2px solid #22c55e; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: flex-start; gap: 12px;">
                                <i class="fas fa-check-circle" style="font-size: 24px; color: #22c55e; flex-shrink: 0; margin-top: 5px;"></i>
                                <div style="flex: 1;">
                                    <h4 style="color: #15803d; margin: 0 0 10px 0; font-weight: 700;">Facility Inspection Completed ✓</h4>
                                    <p style="color: #15803d; margin: 8px 0; font-size: 14px;">
                                        <strong>Equipment Status:</strong> Successfully updated and recorded
                                    </p>
                                    <p style="color: #15803d; margin: 8px 0; font-size: 14px;">
                                        <strong>Inspector:</strong> ${data.facilitator_name}
                                    </p>
                                    <p style="color: #15803d; margin: 8px 0; font-size: 14px;">
                                        <strong>Inspection Date & Time:</strong> ${submittedDate}
                                    </p>
                                    <p style="color: #15803d; margin: 8px 0; font-size: 14px;">
                                        <strong>Checklist ID:</strong> #${String(data.checklist_id).padStart(6, '0')}
                                    </p>
                                    <p style="color: #15803d; margin: 12px 0 0 0; font-size: 13px;">
                                        📋 The inspection evaluation template has been saved to the booking management folder and is ready for download.
                                    </p>
                                    <button onclick="closeInspectionPanel()" style="margin-top: 15px; padding: 10px 20px; background: #22c55e; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px;">
                                        <i class="fas fa-times"></i> Close & Return to Events
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;

                    // Disable the Done button to prevent further clicks
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                    
                    // Reload equipment reports summary
                    setTimeout(() => {
                        loadEquipmentReportsSummary();
                    }, 500);
                })
                .catch(e => {
                    console.error('Error updating equipment:', e);
                    showAlert('Failed to update equipment: ' + e.message, 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check"></i> Done';
                });
        }

        // Close inspection panel
        function closeInspectionPanel() {
            clearChecklist();
            document.getElementById('checklistPanel').classList.remove('active');
            document.getElementById('eventsList').style.display = 'block';
            document.querySelectorAll('.event-item').forEach(el => el.classList.remove('active'));
            selectedEventId = null;
            loadEquipmentReportsSummary();
            loadEvents();
        }

        // Generate report
        function generateReport() {
            if (!selectedEventId) {
                showAlert('No event selected', 'error');
                return;
            }

            const statuses = Object.values(equipmentStatuses);

            if (!statuses.length) {
                showAlert('Please inspect at least one equipment item before generating the report', 'error');
                return;
            }

            if (statuses.length < totalEquipment) {
                if (!confirm(`You have only checked ${statuses.length} of ${totalEquipment} items. Generate report anyway?`)) {
                    return;
                }
            }

            const btn = document.getElementById('generateBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Inspection Evaluation...';

            fetch(`/api/events/${selectedEventId}/equipment-report`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ equipment_statuses: statuses })
            })
                .then(async response => {
                    if (!response.ok) {
                        // Try to parse error message from JSON
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || 'Failed to generate report');
                        }
                        throw new Error('Failed to generate report (HTTP ' + response.status + ')');
                    }
                    return response.blob();
                })
                .then(blob => {
                    // Download file
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `Inspection_Evaluation_Event${selectedEventId}_${Date.now()}.xlsx`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);

                    showAlert('✓ Inspection evaluation template generated and saved to booking management folder! Rental equipment and plan equipment populated with status summary.', 'success');
                })
                .catch(e => {
                    console.error('Error generating report:', e);
                    showAlert('Failed to generate report: ' + e.message, 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-file-excel"></i> Generate Report';
                });
        }

        // Helper: Format date
        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }

        // Helper: Show alert
        // Create toast container if it doesn't exist
        function ensureToastContainer() {
            if (!document.getElementById('toastContainer')) {
                const container = document.createElement('div');
                container.id = 'toastContainer';
                document.body.appendChild(container);
            }
        }

        // Show toast notification
        function showAlert(message, type = 'info') {
            ensureToastContainer();
            
            const container = document.getElementById('toastContainer');
            const iconMap = {
                success: 'check-circle',
                error: 'exclamation-circle',
                info: 'info-circle'
            };
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `<i class="fas fa-${iconMap[type]}"></i><span>${message}</span>`;
            
            container.appendChild(toast);
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                toast.classList.add('hide');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Load generated files for event
        function loadGeneratedFiles(eventId) {
            fetch(`/api/events/${eventId}/generated-files`)
                .then(r => r.json())
                .then(data => {
                    const filesList = document.getElementById('generatedFilesList');
                    const section = document.getElementById('generatedFilesSection');

                    if (!data.success || data.files.length === 0) {
                        section.style.display = 'none';
                        return;
                    }

                    let html = '';
                    data.files.forEach(file => {
                        const fileSize = (file.size / 1024).toFixed(2);
                        html += `
                            <div class="file-item">
                                <div class="file-info">
                                    <div class="file-icon"><i class="fas fa-file-excel"></i></div>
                                    <div class="file-details">
                                        <div class="file-name">${file.name}</div>
                                        <div class="file-meta">${file.date} • ${fileSize} KB</div>
                                    </div>
                                </div>
                                <div class="file-actions">
                                    <a href="${file.url}" class="btn-download">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <button onclick="deleteInspectionFile(${eventId}, '${file.name.replace(/'/g, "\\'")}', this)" class="btn-delete" title="Delete this file">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        `;
                    });

                    filesList.innerHTML = html;
                    section.style.display = 'block';
                })
                .catch(e => {
                    console.error('Error loading generated files:', e);
                    document.getElementById('generatedFilesSection').style.display = 'none';
                });
        }

        // Delete an inspection file
        function deleteInspectionFile(eventId, fileName, button) {
            if (!confirm(`Delete "${fileName}"? This action cannot be undone.`)) {
                return;
            }

            // Show loading state
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            fetch(`/api/events/${eventId}/delete-file/${encodeURIComponent(fileName)}`, {
                method: 'DELETE'
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showAlert('✓ File deleted successfully!', 'success');
                        // Reload files and refresh equipment reports summary
                        loadGeneratedFiles(eventId);
                        loadEquipmentReportsSummary();
                    } else {
                        throw new Error(data.message || 'Failed to delete file');
                    }
                })
                .catch(e => {
                    console.error('Error:', e);
                    showAlert('Failed to delete file: ' + e.message, 'error');
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                });
        }

        // Filter events by facility or event name
        function filterEvents() {
            const searchInput = document.getElementById('facilitySearch');
            const searchTerm = searchInput.value.toLowerCase().trim();
            const eventItems = document.querySelectorAll('.event-item');

            if (searchTerm === '') {
                // Show all events
                eventItems.forEach(item => {
                    item.style.display = '';
                });
            } else {
                eventItems.forEach(item => {
                    const title = item.querySelector('.event-item-title').textContent.toLowerCase();
                    const facility = item.querySelector('.event-item-info').textContent.toLowerCase();
                    
                    if (title.includes(searchTerm) || facility.includes(searchTerm)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
        }

        // Load Equipment Reports Summary
        function loadEquipmentReportsSummary() {
            fetch('/api/events/equipment-reports-summary')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        updateReportsSummary(data);
                    } else {
                        console.error('Failed to load reports summary:', data.message);
                    }
                })
                .catch(e => {
                    console.error('Error loading equipment reports:', e);
                });
        }

        // Update Reports Summary Display
        function updateReportsSummary(data) {
            // Update stat cards
            document.getElementById('totalEquipmentCount').textContent = data.summary.total || 0;
            document.getElementById('goodConditionCount').textContent = data.summary.good || 0;
            document.getElementById('damagedCount').textContent = data.summary.damaged || 0;
            document.getElementById('missingCount').textContent = data.summary.missing || 0;

            // Update table
            const tbody = document.getElementById('reportsTableBody');
            
            if (!data.reports || data.reports.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-reports">
                            <i class="fas fa-inbox"></i>
                            <p>No equipment reports yet. Start by inspecting an event's equipment.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            // Group reports by event and equipment
            const groupedData = {};
            data.reports.forEach(report => {
                const key = report.event_title + '||' + report.equipment_name;
                if (!groupedData[key]) {
                    groupedData[key] = {
                        event_title: report.event_title,
                        equipment_name: report.equipment_name,
                        total: 0,
                        good: 0,
                        damaged: 0,
                        missing: 0,
                        inspection_date: report.created_at
                    };
                }
                groupedData[key].total += report.expected_quantity || 0;
                if (report.equipment_condition === 'good') {
                    groupedData[key].good += report.expected_quantity || 0;
                } else if (report.equipment_condition === 'damaged') {
                    groupedData[key].damaged += report.expected_quantity || 0;
                } else if (report.equipment_condition === 'missing') {
                    groupedData[key].missing += report.expected_quantity || 0;
                }
            });

            // Build table rows
            let html = '';
            Object.values(groupedData).forEach(detail => {
                const inspectionDate = new Date(detail.inspection_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });

                html += `
                    <tr>
                        <td>${escapeHtml(detail.event_title)}</td>
                        <td>${escapeHtml(detail.equipment_name)}</td>
                        <td>${detail.total}</td>
                        <td><span style="color: #22c55e; font-weight: 600;">${detail.good}</span></td>
                        <td><span style="color: #ef4444; font-weight: 600;">${detail.damaged}</span></td>
                        <td><span style="color: #6b7280; font-weight: 600;">${detail.missing}</span></td>
                        <td>${inspectionDate}</td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Download Equipment Reports Summary
        function downloadReportSummary() {
            const btn = document.querySelector('.btn-download-report');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

            fetch('/api/events/download-equipment-report')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to download report');
                    }
                    return response.blob();
                })
                .then(blob => {
                    // Create download link
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `Equipment_Inspection_Summary_${new Date().toISOString().split('T')[0]}.xlsx`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);

                    showAlert('✓ Report downloaded successfully!', 'success');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Failed to download report: ' + error.message, 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-download"></i> Download Report';
                });
        }
    </script>
</body>
</html>