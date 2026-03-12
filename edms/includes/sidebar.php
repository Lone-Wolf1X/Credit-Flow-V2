<div class="sidebar" id="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-between p-3">
        <a class="navbar-brand fw-bold text-white d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>dashboard.php">
            <div class="icon-shape bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="fas fa-layer-group fa-sm"></i>
            </div>
            <span class="logo-text ms-1">EDMS Pro</span>
        </a>
        <button class="btn btn-link text-white-50 d-lg-none" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="sidebar-body">
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-2 text-muted text-uppercase smaller">
            <span>Main Menu</span>
        </h6>
        
        <ul class="nav flex-column mb-auto">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>dashboard.php" data-tooltip="Dashboard">
                    <i class="fas fa-home fa-fw"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 <?php echo basename($_SERVER['PHP_SELF']) == 'create_customer.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>modules/customer/create_customer.php" data-tooltip="New Customer">
                    <i class="fas fa-user-plus fa-fw"></i>
                    <span>New Customer</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 <?php echo basename($_SERVER['PHP_SELF']) == 'customer_list.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>modules/customer/customer_list.php" data-tooltip="Customer List">
                    <i class="fas fa-users fa-fw"></i>
                    <span>Customer List</span>
                </a>
            </li>
        </ul>

        <?php 
        $edms_role_check = getEdmsRole(getCurrentUser()['role']);
        if ($edms_role_check === 'Checker' || $edms_role_check === 'Admin'): 
        ?>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-2 text-muted text-uppercase smaller">
                <span>Legal Review</span>
            </h6>
            <ul class="nav flex-column mb-auto">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-3 <?php echo basename($_SERVER['PHP_SELF']) == 'legal_vetting.php' ? 'active' : ''; ?>"
                        href="<?php echo BASE_URL; ?>modules/legal/legal_vetting.php" data-tooltip="Legal Vetting">
                        <i class="fas fa-gavel fa-fw"></i>
                        <span>Legal Vetting</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer mt-auto p-3">
        <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-outline-light w-100 d-flex align-items-center justify-content-center gap-2" data-tooltip="Logout">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>
