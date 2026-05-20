<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSPC Admin - File Templates Management</title>
    <link rel="shortcut icon" href="<?= base_url('images/CSPCLOGO.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/filetemp.css') ?>">
    <style>
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #c7254e;
        }

        .clear-file-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            padding: 0;
            line-height: 1;
        }

        .clear-file-btn:hover {
            background-color: #c82333;
            transform: scale(1.1);
        }

        .clear-file-btn:active {
            transform: scale(0.95);
        }

        /* Header Section Improvements */
        .filetemplates-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 30px;
        }

        .filetemplates-header-content h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .filetemplates-header-content p {
            font-size: 14px;
            color: var(--gray);
            margin: 0;
        }

        .filetemplates-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-import {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-import:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }

        .btn-refresh {
            background-color: var(--gray-light);
            color: var(--dark);
            border: 1px solid #ddd;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-refresh:hover {
            background-color: #e8ecf1;
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Search and Filter Section */
        .search-filter-section {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input-wrapper {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 10px 16px 10px 38px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(10, 43, 122, 0.1);
        }

        .search-input-wrapper::before {
            content: "🔍";
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            pointer-events: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #f0f4f8 100%);
            border-radius: 12px;
            border: 2px dashed #ddd;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--gray);
            font-size: 14px;
            margin: 0;
        }

        /* Card Container */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #e8e8e8;
        }

        /* Improved template grid */
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .template-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            padding: 24px;
            transition: all 0.3s ease;
            border: 2px solid #f0f4f8;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .template-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .template-card:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 24px rgba(10, 43, 122, 0.12);
            transform: translateY(-4px);
        }

        .template-card:hover::before {
            opacity: 1;
        }

        .template-icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 16px;
        }

        .template-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
            text-align: center;
        }

        .template-filename {
            font-size: 12px;
            color: var(--gray);
            text-align: center;
            margin-bottom: 16px;
            font-family: "Courier New", monospace;
            background: var(--gray-light);
            padding: 6px 12px;
            border-radius: 6px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .template-info {
            font-size: 13px;
            color: var(--gray);
            margin: 8px 0;
            padding: 8px 12px;
            background: var(--gray-light);
            border-radius: 6px;
            border-left: 3px solid var(--primary);
        }

        .template-info strong {
            color: var(--dark);
            font-weight: 600;
        }

        .template-footer {
            text-align: center;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-light);
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        /* Alert Improvements */
        .alert {
            padding: 14px 18px;
            margin-bottom: 16px;
            border-radius: 8px;
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #198754;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        .alert span {
            font-weight: 600;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

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
                <li class="dropdown">
                    <a href="#" class="menu-item dropdown-toggle" onclick="toggleDropdown(event)">
                        <i>🏢</i> Booking <span class="arrow">▾</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?= base_url('/admin/external') ?>" class="submenu-item">🌐 External</a></li>
                        <li><a href="<?= base_url('/admin/internal') ?>" class="submenu-item active">🏛️ Internal</a></li>
                    </ul>
                </li>

                <li><a href="<?= base_url('/admin/events') ?>" class="menu-item"><i>📅</i> Events</a></li>
                <li><a href="<?= base_url('/admin/equipment') ?>" class="menu-item"><i>🔧</i> Equipment</a></li>
                <li><a href="<?= base_url('/admin/plans') ?>" class="menu-item"><i>📋</i> Plans</a></li>
                <li><a href="<?= base_url('/admin/facilities-management') ?>" class="menu-item"><i>🏗️</i> Facilities</a></li>

                <div class="sidebar-divider"></div>

                <li><a href="<?= base_url('admin/booking-management') ?>" class="menu-item"><i>📝</i> Bookings</a></li>
                <li><a href="<?= base_url('/admin/attendance') ?>" class="menu-item"><i>📋</i> Attendance</a></li>
                <li><a href="<?= base_url('/admin/file-templates') ?>" class="menu-item active"><i>📄</i> File Templates</a></li>
                
            </ul>
        </div>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr(session('full_name') ?? 'AD', 0, 2)) ?>
                </div>
                <div class="user-details">
                    <?= session('full_name') ?? 'Administrator'; ?>
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
            <div style="margin-left: auto;"></div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard">
            <!-- Page Header Section -->
            <div class="filetemplates-header">
                <div class="filetemplates-header-content">
                    <h2>📄 File Templates Management</h2>
                    <p>Manage document templates used for generating booking-related files</p>
                </div>
                <div class="filetemplates-actions">
                    <button class="btn-refresh" onclick="location.reload()" title="Refresh templates">
                        🔄 Refresh
                    </button>
                </div>
            </div>

            <div id="alertContainer"></div>

            <!-- Search Section -->
            <?php if (!empty($templates)): ?>
                <div class="search-filter-section">
                    <div class="search-input-wrapper">
                        <input 
                            type="text" 
                            placeholder="Search templates by name or file..." 
                            id="searchInput"
                        >
                    </div>
                    <button class="btn-refresh" onclick="clearSearch()" title="Clear search">
                        ✕ Clear
                    </button>
                </div>
            <?php endif; ?>

            <!-- Templates Card -->
            <div class="card">

                <?php if (empty($templates)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📂</div>
                        <h3>No Template Files Found</h3>
                        <p>No template files found in /public/assets/templates/</p>
                    </div>
                <?php else: ?>
                    <div class="templates-grid" id="templatesGrid">
                        <?php foreach ($templates as $template): ?>
                            <?php 
                            // Skip templates without signatories
                            $excludeTemplates = ['report_summary', 'faculty_evaluation', 'report_summary_template', 'faculty_evaluation_template'];
                            $templateBaseName = strtolower(pathinfo($template['name'], PATHINFO_FILENAME));
                            if (in_array($templateBaseName, $excludeTemplates)) {
                                continue;
                            }
                            ?>
                            <div class="template-card" data-filename="<?= esc($template['name']) ?>" data-display-name="<?= esc($template['display_name'], 'js') ?>" onclick="openSignatoriesModal('<?= esc($template['name'], 'js') ?>', '<?= esc($template['display_name'], 'js') ?>')">
                                <div class="template-icon">
                                    <?php
                                        $icon = '📄';
                                        if ($template['extension'] === 'xlsx' || $template['extension'] === 'xls') {
                                            $icon = '📊';
                                        } elseif ($template['extension'] === 'docx' || $template['extension'] === 'doc') {
                                            $icon = '📝';
                                        } elseif ($template['extension'] === 'pdf') {
                                            $icon = '📕';
                                        }
                                        echo $icon;
                                    ?>
                                </div>
                                <div class="template-name"><?= esc($template['display_name']) ?></div>
                                <div class="template-filename"><?= esc($template['name']) ?></div>
                                <div class="template-info">
                                    <strong>Type:</strong> <?= esc($template['type']) ?>
                                </div>
                                <div class="template-info">
                                    <strong>Size:</strong> <?= esc($template['size_formatted']) ?>
                                </div>
                                <div class="template-info">
                                    <strong>Modified:</strong> <?= esc($template['modified_formatted']) ?>
                                </div>
                                <div class="template-footer">
                                    ➜ CLICK TO EDIT SIGNATORIES
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Signatories Modal -->
    <div id="signatoriesModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
            <div class="modal-header" style="border-bottom: 1px solid #e8e8e8; padding: 24px 28px;">
                <h3 id="modalTitle" style="font-size: 20px; font-weight: 700; color: var(--dark); margin: 0;">Edit Signatories</h3>
                <span class="close" onclick="closeSignatoriesModal()" style="font-size: 28px; cursor: pointer; color: var(--gray);">&times;</span>
            </div>
            <div class="modal-body" style="padding: 28px;">
                <form id="signatoriesEditForm">
                    <input type="hidden" id="modalTemplateName" name="template_name">
                    <div id="signatoriesContainer"></div>
                    
                    <div style="margin-top: 32px; display: flex; gap: 12px; border-top: 1px solid #e8e8e8; padding-top: 20px;">
                        <button type="button" onclick="closeSignatoriesModal()" class="btn btn-secondary" style="flex: 1; padding: 11px 20px; border-radius: 8px; background-color: var(--gray-light); color: var(--dark); border: 1px solid #ddd; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                            ✕ Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 11px 20px; border-radius: 8px; background-color: var(--primary); color: white; border: none; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                            💾 Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Dropdown toggle function
        function toggleDropdown(event) {
            event.preventDefault();
            const dropdown = event.target.closest('.dropdown');
            dropdown.classList.toggle('open');
        }

        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        // Open signatories modal for a template
        function openSignatoriesModal(templateName, displayName) {
            document.getElementById('modalTitle').textContent = `Edit Signatories - ${displayName}`;
            document.getElementById('modalTemplateName').value = templateName;
            
            showLoading();
            console.log('Loading config for template:', templateName);

            fetch('<?= base_url('admin/file-templates/get-template-config') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    template_name: templateName
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(result => {
                console.log('Response data:', result);
                hideLoading();
                
                if (result.success) {
                    buildSignatoriesForm(result.data);
                    document.getElementById('signatoriesModal').style.display = 'block';
                } else {
                    showAlert('Error: ' + (result.message || 'Failed to load template configuration'), 'error');
                    console.error('Error from server:', result);
                }
            })
            .catch(error => {
                hideLoading();
                showAlert('Error loading template configuration: ' + error.message, 'error');
                console.error('Fetch error:', error);
            });
        }

        // Build the signatory form based on template configuration
        function buildSignatoriesForm(templateConfig) {
            const container = document.getElementById('signatoriesContainer');
            container.innerHTML = '';

            if (!templateConfig.signatories || templateConfig.signatories.length === 0) {
                container.innerHTML = '<p style="color: var(--danger); font-weight: 500; text-align: center;">No signatories configured for this template</p>';
                return;
            }

            templateConfig.signatories.forEach((sig, index) => {
                const fieldHtml = `
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="signatory_${index}" style="display: block; font-weight: 600; color: var(--dark); margin-bottom: 6px; font-size: 14px;">${sig.label}</label>
                        ${sig.subtitle ? `<small style="color: #6c757d; display: block; margin-bottom: 10px; font-style: italic; font-size: 13px;">${sig.subtitle}</small>` : ''}
                        <input type="text" 
                               id="signatory_${index}" 
                               name="signatories[${index}]" 
                               class="form-control" 
                               placeholder="${sig.placeholder || 'Enter ' + sig.label.toLowerCase()}"
                               value="${sig.current_value || ''}"
                               style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; transition: all 0.3s ease; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
                        <small style="color: #6c757d; display: block; margin-top: 6px; font-size: 12px; font-weight: 500;">${sig.cell_location}</small>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', fieldHtml);
            });

            // Add focus styles via event listeners
            document.querySelectorAll('#signatoriesContainer input').forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = 'var(--primary)';
                    this.style.boxShadow = '0 0 0 3px rgba(10, 43, 122, 0.1)';
                });
                input.addEventListener('blur', function() {
                    this.style.borderColor = '#ddd';
                    this.style.boxShadow = 'none';
                });
            });
        }

        // Close signatories modal
        function closeSignatoriesModal() {
            document.getElementById('signatoriesModal').style.display = 'none';
            document.getElementById('signatoriesEditForm').reset();
        }

        // Handle form submission
        document.getElementById('signatoriesEditForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const templateName = document.getElementById('modalTemplateName').value;
            const formData = new FormData(this);
            
            // Collect signatory values
            const signatories = {};
            const inputs = this.querySelectorAll('input[name^="signatories"]');
            inputs.forEach((input, index) => {
                signatories[index] = input.value;
            });

            console.log('Submitting signatories:', {
                template_name: templateName,
                signatories: signatories
            });

            showLoading();

            try {
                const response = await fetch('<?= base_url('admin/file-templates/update-signatories') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        template_name: templateName,
                        signatories: signatories
                    })
                });

                const result = await response.json();

                hideLoading();

                console.log('Server response:', result);

                if (result.success) {
                    showAlert('✓ Signatories updated successfully!', 'success');
                    closeSignatoriesModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    // Show detailed error message if available
                    let errorMsg = result.message || 'Failed to update signatories';
                    if (result.details) {
                        errorMsg += '\n\nDetails: ' + result.details;
                    }
                    showAlert(errorMsg, 'error');
                    console.error('Update failed:', result);
                }
            } catch (error) {
                hideLoading();
                showAlert('An error occurred while updating signatories', 'error');
                console.error(error);
            }
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.template-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.querySelector('.template-name').textContent.toLowerCase();
                const filename = card.querySelector('.template-filename').textContent.toLowerCase();

                if (name.includes(searchTerm) || filename.includes(searchTerm)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Show message if no results
            const grid = document.getElementById('templatesGrid');
            if (grid) {
                let noResults = grid.querySelector('.no-results-message');
                if (visibleCount === 0 && searchTerm) {
                    if (!noResults) {
                        noResults = document.createElement('div');
                        noResults.className = 'no-results-message';
                        noResults.style.cssText = 'grid-column: 1/-1; text-align: center; padding: 40px 20px; color: var(--gray);';
                        noResults.innerHTML = '<p style="font-size: 16px; margin: 0;">🔍 No templates found matching your search</p>';
                        grid.insertBefore(noResults, grid.firstChild);
                    }
                } else if (noResults) {
                    noResults.remove();
                }
            }
        });

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('searchInput').dispatchEvent(new Event('input'));
        }

        // Modal close on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('signatoriesModal');
            if (event.target == modal) {
                closeSignatoriesModal();
            }
        }

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            
            const icon = type === 'success' ? '✓' : '✕';
            alert.innerHTML = `<span>${icon}</span><div>${message}</div>`;
            
            alertContainer.insertBefore(alert, alertContainer.firstChild);

            setTimeout(() => {
                alert.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }

        // Add slide-out animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOut {
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }
    </script>
</body>
</html>
