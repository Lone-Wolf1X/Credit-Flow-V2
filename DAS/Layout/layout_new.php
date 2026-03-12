<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>Document Automation System</title>
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="<?php echo $assetPath ?? '../../asstes'; ?>/images/sbifavicon.ico">
  <link rel="shortcut icon" type="image/x-icon" href="<?php echo $assetPath ?? '../../asstes'; ?>/images/sbifavicon.ico">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f4f5f9;
      overflow-x: hidden;
    }
    
    .logo-img {
      height: 40px;
      width: auto;
      max-width: 180px;
      object-fit: contain;
      border-radius: 4px;
      transition: all 0.3s ease;
      background: rgba(255,255,255,0.1);
      padding: 2px 8px;
    }
    
    .logo-img:hover {
      transform: scale(1.05);
      background: rgba(255,255,255,0.15);
    }
    
    .navbar-custom {
      background: linear-gradient(90deg, #c0264c, #7b145d 50%, #29006e);
      color: white;
      height: 56px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }
    
    .navbar-custom .navbar-brand,
    .navbar-custom .dropdown-toggle {
      color: white;
      font-weight: 600;
      transition: color 0.3s ease;
    }
    
    .navbar-custom .navbar-brand:hover {
      color: #f8f9fa;
      transform: scale(1.02);
      transition: all 0.3s ease;
    }
    
    .navbar-custom .dropdown-menu a:hover {
      background-color: #f2f2f2;
      transform: translateX(5px);
      transition: all 0.3s ease;
    }
    
    .sidebar {
      position: fixed;
      top: 56px;
      left: 0;
      width: 240px;
      height: calc(100vh - 56px - 40px);
      background: linear-gradient(180deg, #7b145d, #29006e);
      color: white;
      border-right: 1px solid #dee2e6;
      overflow-y: auto;
      transition: width 0.3s ease-in-out;
      z-index: 100;
      box-shadow: 2px 0 8px rgba(0,0,0,0.04);
    }
    
    .sidebar.collapsed {
      width: 70px;
    }
    
    .sidebar.collapsed .nav.flex-column {
      padding-left: 0 !important;
    }
    
    .sidebar.collapsed .nav-link {
      justify-content: center;
      padding-left: 0;
      padding-right: 0;
    }
    
    .sidebar.collapsed .nav-link i {
      margin: 0 auto;
    }
    
    .sidebar.collapsed .nav-link span {
      opacity: 0;
      transform: translateX(-20px);
      display: none;
    }
    
    .sidebar .nav-link {
      color: #f1f1f1;
      padding: 14px 24px;
      border-radius: 8px;
      margin: 4px 8px;
      border-bottom: none;
      transition: all 0.2s ease-in-out;
      display: flex;
      align-items: center;
      font-size: 16px;
      font-weight: 500;
      position: relative;
      overflow: hidden;
    }
    
    .sidebar .nav-link::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
      transition: left 0.5s ease;
    }
    
    .sidebar .nav-link:hover::before {
      left: 100%;
    }
    
    .sidebar .nav-link i {
      font-size: 20px;
      min-width: 24px;
      text-align: center;
      transition: all 0.3s ease;
    }
    
    .sidebar .nav-link span {
      margin-left: 16px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      opacity: 1;
      transform: translateX(0);
    }
    
    .sidebar .nav-link:hover {
      background-color: rgba(255,255,255,0.15);
      color: #fff;
      transform: translateX(5px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .sidebar .nav-link:hover i {
      transform: scale(1.1);
    }
    
    .sidebar .nav-link.active {
      background-color: rgba(255,255,255,0.2);
      color: #fff;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    .sidebar.collapsed .nav-link span {
      display: none;
    }
    
    .sidebar-toggle {
      background: none;
      border: none;
      color: #fff;
      font-size: 22px;
      width: 100%;
      text-align: center;
      padding: 12px 24px;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .sidebar-toggle::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
      transition: left 0.5s ease;
    }
    
    .sidebar-toggle:hover::before {
      left: 100%;
    }
    
    .sidebar-toggle:hover {
      background-color: rgba(255,255,255,0.1);
      transform: scale(1.05);
    }
    
    .sidebar-toggle i {
      transition: transform 0.3s ease;
    }
    
    .sidebar.collapsed .sidebar-toggle i {
      transform: rotate(180deg);
    }
    
    .layout {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      padding-top: 56px;
      padding-left: 240px;
      transition: padding-left 0.3s ease-in-out;
    }
    
    .layout.collapsed {
      padding-left: 70px;
    }
    
    .main-content {
      flex: 1 0 auto;
      padding: 30px 40px;
      padding-bottom: 100px;
      margin-bottom: 40px;
      opacity: 1;
      transform: translateY(0);
      transition: all 0.3s ease;
    }
    
    .main-content.loading {
      opacity: 0.7;
      transform: translateY(10px);
    }
    
    .footer-custom {
      background: linear-gradient(90deg, #c0264c, #7b145d 50%, #29006e);
      color: white;
      height: 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
      font-size: 14px;
      flex-shrink: 0;
      position: fixed;
      left: 0;
      right: 0;
      bottom: 0;
      width: 100%;
      z-index: 101;
      transition: all 0.3s ease;
    }
    
    .avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 8px;
      transition: all 0.3s ease;
      border: 2px solid rgba(255,255,255,0.3);
    }
    
    .avatar:hover {
      transform: scale(1.1);
      border-color: rgba(255,255,255,0.8);
    }
    
    .dropdown-toggle {
      transition: all 0.3s ease;
    }
    
    .dropdown-toggle:hover {
      transform: scale(1.05);
    }
    
    .dropdown-menu {
      animation: fadeInDown 0.3s ease;
      border: none;
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .dropdown-item {
      transition: all 0.3s ease;
    }
    
    .dropdown-item:hover {
      background-color: #f8f9fa;
      transform: translateX(5px);
    }
    
    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
    
    .nav-item {
      animation: slideIn 0.3s ease forwards;
      animation-delay: calc(var(--i) * 0.1s);
      opacity: 0;
    }
    
    .error-message {
      display: none;
      background-color: #f8d7da;
      color: #721c24;
      padding: 10px;
      border-radius: 5px;
      margin: 10px;
      border: 1px solid #f5c6cb;
      animation: shake 0.5s ease;
    }
    
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }
    
    @media (max-width: 768px) {
      .sidebar {
        width: 70px;
      }
      .layout {
        padding-left: 70px;
      }
      .main-content {
        padding: 20px 15px;
        padding-bottom: 100px;
        margin-bottom: 40px;
      }
    }
    
    /* Modern Loading Animation */
    .loading-spinner {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.98);
      z-index: 9999;
    }

    .spinner-container {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
    }

    .spinner-logo {
      width: 100px;
      height: auto;
      margin-bottom: 30px;
      opacity: 0.9;
    }

    .spinner {
      position: relative;
      width: 60px;
      height: 60px;
      margin: 0 auto 20px;
    }

    .spinner:before {
      content: "";
      display: block;
      position: absolute;
      width: 100%;
      height: 100%;
      border: 3px solid #f3f3f3;
      border-top: 3px solid #280071;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    .spinner-text {
      color: #666;
      font-size: 14px;
      font-weight: 400;
      margin-top: 10px;
      letter-spacing: 0.5px;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <!-- Loading Spinner -->
  <div class="loading-spinner" id="loadingSpinner">
    <div class="spinner-container">
      <img src="<?php echo $assetPath ?? '../../asstes'; ?>/images/sbilogo.png" alt="SBI Logo" class="spinner-logo">
      <div class="spinner"></div>
      <div class="spinner-text">Loading, please wait...</div>
    </div>
  </div>

  <!-- Error Message -->
  <div class="error-message" id="errorMessage"></div>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top px-3">
    <a class="navbar-brand d-flex align-items-center" href="#" aria-label="Document Automation System Home">
      <img src="<?php echo $assetPath ?? '../../asstes'; ?>/images/sbilogo.png" class="logo-img me-2" alt="SBI Logo" />
      <span class="d-none d-md-inline">Document Automation System</span>
    </a>
    <div class="ms-auto d-flex align-items-center">
      <!-- Notification Icon -->
      <a href="#" class="text-white me-3 position-relative" title="Notifications">
        <i class="bi bi-bell fs-5"></i>
        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
          <span class="visually-hidden">New alerts</span>
        </span>
      </a>

      <img src="<?php echo $userAvatar ?? '../../assets/images/img_avatar.png'; ?>" class="avatar" alt="User Avatar" />
      <div class="dropdown">
        <button class="btn btn-sm dropdown-toggle text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User Menu">
          <?php echo htmlspecialchars($userName ?? 'User'); ?>
          <span class="badge bg-danger ms-2"><?php echo htmlspecialchars($badgeText ?? $_SESSION['role_name'] ?? 'Guest'); ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="../common/profile.php"><i class="bi bi-person-circle me-2"></i>My Profile</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
          <li><a class="dropdown-item" href="../../logout.php" onclick="return confirmLogout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Sidebar -->
  <div class="sidebar collapsed" id="sidebar">
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
      <i class="bi bi-list"></i>
    </button>
    <ul class="nav flex-column px-2">
      <?php
        $role = $_SESSION['role_name'] ?? 'Guest';
        
        // Default Links
        $dashboardLink = 'dashboard.php';
        $createLink = '#';
        $createLabel = 'Create';
        $showUsers = false;
        $showRecords = false;
        $showTemplates = false;
        $showReports = false;

        if ($role === 'Maker') {
            $dashboardLink = '../dashboard/maker_dashboard.php';
            $createLink = '../customer/create_customer.php';
            $createLabel = 'New Customer';
            $showRecords = true;
        } elseif ($role === 'Checker') {
            $dashboardLink = '../dashboard/checker_dashboard.php';
            $showRecords = true;
            $showReports = true;
        } elseif ($role === 'Admin') {
            $dashboardLink = '../dashboard/admin_dashboard.php';
            $showTemplates = true;
            $showUsers = true;
            $showReports = true;
        } elseif ($role === 'Super Admin') {
            $dashboardLink = '../superadmin/dashboard.php';
            $createLink = '../superadmin/create_user.php';
            $createLabel = 'New User';
            $showUsers = true;
            $showTemplates = true;
            $showReports = true;
        }
      ?>

      <!-- Dashboard -->
      <li class="nav-item" style="--i: 1;">
        <a class="nav-link <?php echo ($activeMenu ?? '') === 'dashboard' ? 'active' : ''; ?>" 
           href="<?php echo ($activeMenu ?? '') === 'dashboard' ? '#' : $dashboardLink; ?>" aria-label="Dashboard">
          <i class="bi bi-speedometer2"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <!-- Create (Contextual) -->
      <?php if ($role === 'Maker' || $role === 'Super Admin'): ?>
      <li class="nav-item" style="--i: 2;">
        <a class="nav-link <?php echo ($activeMenu ?? '') === 'create_customer' || ($activeMenu ?? '') === 'create_user' ? 'active' : ''; ?>" 
           href="<?php echo $createLink; ?>" aria-label="<?php echo $createLabel; ?>">
          <i class="bi bi-plus-circle"></i>
          <span><?php echo $createLabel; ?></span>
        </a>
      </li>
      <?php endif; ?>

      <!-- Customer Records -->
      <?php if ($showRecords): ?>
      <li class="nav-item" style="--i: 3;">
        <a class="nav-link <?php echo ($activeMenu ?? '') === 'customer_list' ? 'active' : ''; ?>" 
           href="../customer/customer_list.php" aria-label="Customer Records">
          <i class="bi bi-person-lines-fill"></i>
          <span>Customers</span>
        </a>
      </li>
      <?php endif; ?>

      <!-- Pending Tasks & Recent Activity (Maker & Checker) -->
      <?php if ($role === 'Maker' || $role === 'Checker'): ?>
      <li class="nav-item" style="--i: 4;">
        <a class="nav-link <?php echo ($activeMenu ?? '') === 'pending_tasks' ? 'active' : ''; ?>" 
           href="../dashboard/pending_tasks.php" aria-label="Pending Tasks">
          <i class="bi bi-list-check"></i>
          <span>Pending Tasks</span>
        </a>
      </li>
      <li class="nav-item" style="--i: 4.5;">
        <a class="nav-link <?php echo ($activeMenu ?? '') === 'recent_activity' ? 'active' : ''; ?>" 
           href="../dashboard/recent_activity.php" aria-label="Recent Activity">
          <i class="bi bi-activity"></i>
          <span>Recent Activity</span>
        </a>
      </li>
      <?php endif; ?>

      <!-- Templates (Admin) -->
      <?php if ($showTemplates): ?>
      <li class="nav-item" style="--i: 5;">
        <a class="nav-link <?php echo ($activeMenu ?? '') === 'templates' ? 'active' : ''; ?>" 
           href="../templates/template_list.php" aria-label="Templates">
          <i class="bi bi-file-earmark-text"></i>
          <span>Templates</span>
        </a>
      </li>
      <?php endif; ?>

      <!-- Documents -->
      <?php if ($showRecords || $showTemplates): ?>
      <li class="nav-item" style="--i: 6;">
        <a class="nav-link <?php echo ($activeMenu ?? '') === 'documents' ? 'active' : ''; ?>" 
           href="../documents/document_list.php" aria-label="Documents">
          <i class="bi bi-file-earmark-pdf"></i>
          <span>Documents</span>
        </a>
      </li>
      <?php endif; ?>

      <!-- Reports -->
      <?php if ($showReports): ?>
      <li class="nav-item" style="--i: 7;">
        <a class="nav-link <?php echo ($activeMenu ?? '') === 'reports' ? 'active' : ''; ?>" 
           href="../reports/reports.php" aria-label="Reports">
          <i class="bi bi-graph-up"></i>
          <span>Reports</span>
        </a>
      </li>
      <?php endif; ?>

      <!-- History Hub (Checker & Admin) -->
      <?php if ($role === 'Checker' || $role === 'Admin' || $role === 'Super Admin'): ?>
      <li class="nav-item" style="--i: 7.5;">
        <a class="nav-link <?php echo ($activeMenu ?? '') === 'profile_history' ? 'active' : ''; ?>" 
           href="../history/profile_history_list.php" aria-label="Profile History">
          <i class="bi bi-clock-history"></i>
          <span>Profile History</span>
        </a>
      </li>
      <?php endif; ?>

      <!-- Users (Admin Only) -->
      <?php if ($showUsers): ?>
      <li class="nav-item" style="--i: 8;">
        <a class="nav-link <?php echo ($activeMenu ?? '') === 'user_management' ? 'active' : ''; ?>" 
           href="<?php echo ($activeMenu ?? '') === 'user_management' ? '#' : '../superadmin/user_management.php'; ?>" aria-label="User Management">
          <i class="bi bi-people"></i>
          <span>Users</span>
        </a>
      </li>
      <?php endif; ?>

      <!-- Settings / Profile -->
      <li class="nav-item" style="--i: 10;">
        <a class="nav-link <?php echo ($activeMenu ?? '') === 'settings' ? 'active' : ''; ?>" 
           href="../common/profile.php" aria-label="My Profile">
          <i class="bi bi-person-circle"></i>
          <span>My Profile</span>
        </a>
      </li>
    </ul>
  </div>

  <!-- Layout (main content area for other pages) -->
  <div class="layout collapsed" id="layout">
    <div class="main-content" id="mainContent">
      <?php if (isset($mainContent)): ?>
        <?php echo $mainContent; ?>
      <?php else: ?>
        <div class="container-fluid">
          <h2>Welcome to Document Automation System</h2>
          <p>Select an option from the sidebar to get started.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer-custom">
    <div>© Abhii - Document Automation System</div>
    <div id="datetime"></div>
  </footer>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Error handling and utility functions
    function showError(message) {
      const errorElement = document.getElementById('errorMessage');
      if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        setTimeout(() => {
          errorElement.style.display = 'none';
        }, 5000);
      }
    }

    function showLoading(show = true) {
      const spinner = document.getElementById('loadingSpinner');
      const mainContent = document.getElementById('mainContent');
      
      if (spinner && mainContent) {
        spinner.style.display = show ? 'block' : 'none';
        mainContent.classList.toggle('loading', show);
      }
    }

    // Sidebar Toggle with error handling
    function initializeSidebar() {
      const sidebarToggle = document.getElementById("sidebarToggle");
      const sidebar = document.getElementById("sidebar");
      const layout = document.getElementById("layout");
      
      if (!sidebarToggle || !sidebar || !layout) {
        showError('Sidebar elements not found');
        return;
      }

      sidebarToggle.addEventListener("click", () => {
        try {
          sidebar.classList.toggle("collapsed");
          layout.classList.toggle("collapsed");
          
          // Removed artificial loading delay for smoother toggle
          
        } catch (error) {
          showError('Error toggling sidebar: ' + error.message);
        }
      });
    }

    // Navigation with smooth transitions
    function initializeNavigation() {
      const navLinks = document.querySelectorAll('.sidebar .nav-link');
      
      navLinks.forEach((link, index) => {
        // Animate nav items on load
        setTimeout(() => {
          link.style.opacity = '1';
        }, index * 100);
        
        link.addEventListener('click', function(e) {
          try {
            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Show loading animation
            showLoading(true);
            
            // Simulate page load (replace with actual navigation logic)
            setTimeout(() => {
              showLoading(false);
            }, 800);
            
          } catch (error) {
            showError('Navigation error: ' + error.message);
          }
        });
      });
    }

    // Date/Time with error handling
    function initializeDateTime() {
      function updateDateTime() {
        try {
          const now = new Date();
          const datetimeElement = document.getElementById("datetime");
          
          if (datetimeElement) {
            datetimeElement.textContent = now.toLocaleString('en-GB', {
              year: 'numeric',
              month: '2-digit',
              day: '2-digit',
              hour: '2-digit',
              minute: '2-digit',
              second: '2-digit'
            });
          }
        } catch (error) {
          showError('Error updating date/time: ' + error.message);
        }
      }
      
      updateDateTime();
      setInterval(updateDateTime, 1000);
    }

    // Initialize everything when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
      try {
        // Show initial loading
        showLoading(true);
        
        // Initialize components
        initializeSidebar();
        initializeNavigation();
        initializeDateTime();
        
        // Collapse sidebar by default
        const sidebar = document.getElementById("sidebar");
        const layout = document.getElementById("layout");
        
        if (sidebar && layout) {
          sidebar.classList.add("collapsed");
          layout.classList.add("collapsed");
        }
        
        // Hide loading after initialization
        setTimeout(() => {
          showLoading(false);
        }, 1000);
        
      } catch (error) {
        showError('Initialization error: ' + error.message);
        showLoading(false);
      }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
      try {
        const sidebar = document.getElementById("sidebar");
        const layout = document.getElementById("layout");
        
        if (window.innerWidth <= 768) {
          if (sidebar && layout) {
            sidebar.classList.add("collapsed");
            layout.classList.add("collapsed");
          }
        }
      } catch (error) {
        showError('Resize error: ' + error.message);
      }
    });

    // Handle errors globally
    window.addEventListener('error', function(e) {
      showError('Unexpected error: ' + e.message);
    });
    
    // Enhanced Logout Confirmation
    function confirmLogout() {
      const modal = new bootstrap.Modal(document.getElementById('logoutModal'));
      modal.show();
      return false;
    }

    function executeLogout() {
      const loadingOverlay = document.getElementById('logoutLoadingOverlay');
      if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
      }
      
      setTimeout(() => {
        window.location.href = '../../logout.php';
      }, 800);
    }
  </script>

  <!-- Enhanced Logout Modal -->
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px;">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold">
            <i class="bi bi-box-arrow-right me-2"></i>Confirm Logout
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body py-4 text-center">
          <div class="mb-3">
            <i class="bi bi-question-circle-fill" style="font-size: 3rem; color: #ffd700;"></i>
          </div>
          <p class="mb-3 fs-6">Are you sure you want to logout?</p>
          <p class="mb-0 opacity-75 small">Any unsaved changes will be lost.</p>
        </div>
        <div class="modal-footer border-0 justify-content-center">
          <button type="button" class="btn btn-light btn-lg px-4 me-3" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-2"></i>Cancel
          </button>
          <button type="button" class="btn btn-danger btn-lg px-4" onclick="executeLogout()">
            <i class="bi bi-box-arrow-right me-2"></i>Yes, Logout
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Logout Loading Overlay -->
  <div id="logoutLoadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 9999; backdrop-filter: blur(5px);">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white;">
      <div class="logout-spinner mb-3" style="width: 60px; height: 60px; margin: 0 auto;">
        <div style="width: 100%; height: 100%; border: 3px solid transparent; border-top: 3px solid #ffffff; border-radius: 50%; animation: logoutSpin 1s linear infinite;"></div>
      </div>
      <p class="mb-0 fw-bold">Logging out...</p>
      <p class="small opacity-75">Thank you for using our system</p>
    </div>
  </div>

  <style>
    @keyframes logoutSpin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .btn-danger:hover, .btn-light:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
  </style>
</body>
</html>
