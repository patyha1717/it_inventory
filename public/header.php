<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

if (empty($_SESSION['user_id'])) {
    header("Location: /itms.arukustech.com/public/login.php");
    exit;
}

/* Detect Active Menu */
function active($page) {
    return (strpos($_SERVER['REQUEST_URI'], $page) !== false) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IT Inventory</title>

    <!-- FIXED: Correct asset paths -->
    <link rel="stylesheet" href="/itms.arukustech.com/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/itms.arukustech.com/public/assets/css/bulkmailer.css">
    <link rel="stylesheet" href="/itms.arukustech.com/public/assets/css/theme.css">
    <link rel="stylesheet" href="/itms.arukustech.com/public/assets/css/style.css">
    <link rel="stylesheet" href="/itms.arukustech.com/public/assets/css/custom.css">
    <link rel="stylesheet" href="/itms.arukustech.com/public/assets/css/icons.css">

    <!-- Favicon -->
      <link href="/itms.arukustech.com/public/assets/images/Logo.png" rel="icon">
    <!-- <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon.png?v=4"> -->


     <!-- <link rel="icon" href="/favicon.ico?v=2"> -->

    <!-- <link rel="icon" type="image/x-icon" href="https://itassets.tsdemo.co.in/favicon.ico"> -->



    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ========================
   BODY + GLOBALS
======================== */
body {
    background: #eef1f7;
    font-family: "Inter", sans-serif;
    margin: 0;
    padding: 0;
}

html, body {
    height: 100%;
    overflow-x: hidden;
}

/* ========================
   MODERN BRIGHT SIDEBAR
======================== */
.sidebar {
    width: 245px;
    min-height: 100vh;
    /* Use a rich, modern gradient instead of flat dark grey */
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    position: fixed;
    top: 0;
    left: 0;
    overflow-y: auto;
    z-index: 2000;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.3s ease;
}

/* Sidebar Logo Area */
.sidebar-header {
    padding: 25px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    margin-bottom: 10px;
}

/* Menu Item Styling */
.sidebar a, .sidebar-parent {
    color: #94a3b8; /* Soft blue-grey */
    padding: 12px 20px;
    display: flex;
    align-items: center;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin: 4px 12px;
    border-radius: 8px;
    cursor: pointer;
}

/* Bright Advance Icons */
.sidebar i:not(.arrow) {
    font-size: 18px;
    width: 28px;
    margin-right: 12px;
    /* This creates a subtle "glow" behind the icon */
    color: #38bdf8; 
    transition: all 0.3s ease;
}

/* Hover State */
.sidebar a:hover, .sidebar-parent:hover {
    background: rgba(56, 189, 248, 0.1);
    color: #f8fafc;
}

.sidebar a:hover i {
    color: #7dd3fc;
    transform: scale(1.1);
}

/* Active Menu Highlight */
.sidebar a.active {
    background: #3b82f6 !important; /* Electric Blue */
    color: #fff !important;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
}

.sidebar a.active i {
    color: #fff !important;
}

/* Section Titles (Brightened) */
.sidebar .section-title {
    padding: 20px 25px 8px;
    color: #38bdf8;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    opacity: 0.8;
}

/* Submenu Styling */
.submenu {
    background: rgba(0, 0, 0, 0.15);
    margin: 5px 12px 10px 12px;
    border-radius: 8px;
    padding: 5px 0;
}

.submenu a {
    padding: 10px 15px 10px 45px;
    font-size: 13px;
    margin: 2px 5px;
}

/* ========================
   TOPBAR (FIXED HEADER)
======================== */
.topbar {
    position: fixed;
    top: 0;                /* Attached to the very top */
    left: 246px;           /* Increased by ~38px (1cm) from the standard 250px */
    right: 0px;              /* Attached to the right edge */
    height: 60px;
    
    /* UPDATED BACKGROUND COLOR */
    background: #c3e6e8;
    
    color: #1f2937;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 0 30px;
    
    /* FIXED EDGE STYLING */
    border-bottom: 1px solid #e5e7eb;
    border-radius: 0;      /* Removed rounded corners for fixed look */
    z-index: 1030;
    transition: all 0.3s ease;
}

/* ========================
   USER MENU
======================== */
.user-menu {
    position: relative;
    padding: 6px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.user-menu:hover {
    background: #f1f5f9;
}

.user-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #374151;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.user-name {
    font-size: 14px;
    font-weight: 500;
}

.user-arrow {
    font-size: 12px;
    color: #6b7280;
}

/* Dropdown */
.user-menu .dropdown {
    position: absolute;
    top: 45px;
    right: 0;
    width: 190px;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    display: none;
    z-index: 5000;
    overflow: hidden;
}

.user-menu .dropdown::before {
    content: "";
    position: absolute;
    top: -6px;
    right: 18px;
    width: 12px;
    height: 12px;
    background: #ffffff;
    transform: rotate(45deg);
    border-left: 1px solid #e5e7eb;
    border-top: 1px solid #e5e7eb;
}

.user-menu .dropdown a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    font-size: 14px;
    color: #374151;
    text-decoration: none;
}

.user-menu .dropdown a:hover {
    background: #f3f4f6;
}

.user-menu .dropdown a.text-danger {
    color: #dc2626;
    font-weight: 500;
}

.user-menu .dropdown a.text-danger:hover {
    background: #fee2e2;
}

/* ========================
   MAIN CONTENT
======================== */
.main-content {
    margin-left: 250px;
    margin-top: 60px;          /* header height */
     padding: 15px;
     height: calc(100vh - 60px - 45px);
     overflow-y: auto;
}

/* ========================
   DASHBOARD CARDS
======================== */
.stat-card {
    border-radius: 28px;
    padding: 20px;
    color: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    position: relative;
}

.stat-card .icon {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 50px;
    opacity: 0.2;
}

.stat-card h3 {
    font-size: 34px;
    font-weight: 700;
    margin: 0;
}

.stat-card p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

/* ========================
   TABLE + ARROWS
======================== */
.table thead th {
    background: #1e293b !important;
    color: white !important;
    font-weight: bold;
}

.arrow {
    transition: 0.25s;
}

.arrow.rotate {
    transform: rotate(90deg);
}


/* FIX: Make all page titles visible (black + bold) */
h1, h2, h3, h4, .page-title, .main-title {
    color: #000 !important;
    font-weight: 700 !important;
}

  .chart-container {
    width: 100%;
    max-width: 420px;
    height: 320px;
    margin: auto;
}

.chart-card {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.chart-card .card-body {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}



/* ========================
   COLORS
======================== */
.bg-green { background: linear-gradient(135deg,#22c55e,#16a34a); }
.bg-blue  { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.bg-yellow{ background: linear-gradient(135deg,#facc15,#eab308); color:#332 !important; }
.bg-red   { background: linear-gradient(135deg,#ef4444,#dc2626); }

/* ========================
   PAGE TITLES
======================== */
h1, h2, h3, h4, .page-title, .main-title {
    color: #000 !important;
    font-weight: 700 !important;
}



/*-- Footer Section --*/

.custom-footer {
    position: fixed;
    bottom: 0;
    left: 250px;          
    right: 0;
    height: 45px;
    background: #ffffff;
    color:black;
    border-top: 1px solid #e5e7eb;
    z-index: 2000;
    padding: 0 30px;
    font-size: 13px;
}

/* Footer content alignment */
.custom-footer .footer-inner {
    height: 100%;
}

@media (max-width: 768px) {
    .mobile-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.4);
        z-index: 3500;
        display: none;
    }

    .mobile-overlay.show {
        display: block;
    }
}

.menu-toggle {
    display: none;
}



@media (max-width: 991px) {
    .menu-toggle {
        display: block;
        font-size: 26px;
        cursor: pointer;
        color: #000;
        margin-right: auto;
        z-index: 4001;
    }
}

@media (max-width: 991px) {
    .topbar {
        justify-content: space-between;
        align-items: center;
    }
}

@media (max-width: 768px) {

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;
        height: 100vh;
        background: #1f2a37;
        z-index: 4000;

        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .topbar {
        left: 0 !important;
        margin-left: 0 !important;
        width: 100%;
        justify-content: space-between;
    }

    .main-content {
        margin-left: 0 !important;
        margin-top: 60px;
    }
}



/* Modal Header */
.modal-header {
    /* background: #ffffff;       */
     background: linear-gradient(135deg,#1e293b,#0f172a);
     padding: 18px 22px;
    border-bottom: 1px solid #e5e7eb;
}

/* Modal Title */
.modal-title {
    /* color: #111827 !important;  */
     color: #ffffff;
     font-size: 16px;
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* Close (X) button */
.modal-header .btn-close,
.modal-header .close {
    filter: none;
    opacity: 0.7;
}

.modal-header .btn-close:hover,
.modal-header .close:hover {
    opacity: 1;
}

</style>

<script>
// function toggleMenu(id) {
//     const menu = document.getElementById(id);
//     const parent = menu.previousElementSibling;

//     // Toggle submenu visibility
//     menu.style.display = menu.style.display === "block" ? "none" : "block";

//     // Toggle arrow icon
//     if (menu.style.display === "block") {
//         parent.querySelector('.arrow').innerHTML = "?";
//     } else {
//         parent.querySelector('.arrow').innerHTML = "?";
//     }
// }

//function toggleUserMenu() {
   // let menu = document.getElementById("userDropdown");
   // menu.style.display = menu.style.display === "block" ? "none" : "block";
//}
</script>

<script>

function toggleUserMenu() {
    const menu = document.getElementById("userDropdown");
      if (!menu) return;
    menu.style.display = menu.style.display === "block" ? "none" : "block";
}

/* Close dropdown when clicking outside */
document.addEventListener("click", function(e) {
    const userMenu = document.querySelector(".user-menu");
    const dropdown = document.getElementById("userDropdown");

    if (!userMenu.contains(e.target)) {
        dropdown.style.display = "none";
    }
});
</script>

<script>
function toggleMenu(id, el) {
    const menu = document.getElementById(id);
        if (!menu || !el) return;

    // Fix visibility toggle
    if (menu.style.display === "block") {
        menu.style.display = "none";
        // el.classList.remove("open");
        el.querySelector(".arrow").classList.remove("rotate");
    } else {
        menu.style.display = "block";
        // el.classList.add("open");
        el.querySelector(".arrow").classList.add("rotate");
    }
}
</script>

<script>
// document.addEventListener("DOMContentLoaded", () => {
//     const sidebar = document.getElementById("sidebar");
//     if (!sidebar) return;

//     sidebar.classList.add("collapsed");

//     sidebar.addEventListener("mouseenter", () => sidebar.classList.remove("collapsed"));
//     sidebar.addEventListener("mouseleave", () => sidebar.classList.add("collapsed"));
// });
</script>

<script>
// function toggleSidebar() {
//     const sidebar = document.querySelector('.sidebar');
//     sidebar.classList.toggle('open');
// }

function toggleSidebar() {
        console.log("toggleSidebar");
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');

      if (!sidebar) return;

    sidebar.classList.toggle('open');
    if (overlay) {
        overlay.classList.toggle('show');
    }

}

function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');

    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('show');
}

</script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Buttons Export JS -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>


</head>
<body>

<div class="mobile-overlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header" style="padding: 20px 15px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 10px;">
        <h5 style="color:#38bdf8 !important; font-weight:700; margin:0; font-size:1.1rem; letter-spacing: 0.5px;">
            <i class="ri-shield-flash-line"></i> IT INVENTORY
        </h5>
    </div>

    <a href="/itms.arukustech.com/public/dashboard.php" class="<?= active('dashboard') ?> fw-bold">
        <i class="ri-dashboard-fill"></i> Dashboard
    </a>

    <a href="/itms.arukustech.com/public/categories.php" class="<?= active('categories') ?> fw-bold">
        <i class="ri-stack-fill"></i> Categories
    </a>

    <div onclick="toggleMenu('invMenu', this)" class="sidebar-parent <?= active('inventory') ?>">
        <span><i class="ri-archive-stack-fill"></i> Inventory</span>
        <i class="fa-solid fa-chevron-right arrow"></i>
    </div>
    <div id="invMenu" class="submenu" style="display: <?= (active('inventory_list') || active('inventory_bulk_upload')) ? 'block' : 'none' ?>;">
        <a href="/itms.arukustech.com/public/inventory_list.php" class="<?= active('inventory_list') ?>">Inventory List</a>
        <a href="/itms.arukustech.com/public/inventory_bulk_upload.php" class="<?= active('inventory_bulk_upload') ?>">Bulk Upload</a>
    </div>

     <div class="section-title text-uppercase x-small fw-bold opacity-50 mb-2">Reports & Registry</div>
    <a href="/itms.arukustech.com/public/inventory_master.php" class="<?= active('inventory_master') ?> fw-bold d-flex align-items-center gap-2">
    <i class="ri-database-2-fill text-primary"></i> 
    <span>Global Inventory</span>
    </a>

    <div class="section-title">Operations</div>
    <div onclick="toggleMenu('assignMenu', this)" class="sidebar-parent <?= active('assign') ?>">
        <span><i class="ri-user-shared-2-fill"></i> Asset Assignment</span>
        <i class="fa-solid fa-chevron-right arrow"></i>
    </div>
    <div id="assignMenu" class="submenu" style="display: <?= active('assign') ? 'block' : 'none' ?>;">
        <a href="/itms.arukustech.com/public/assign_items.php" class="<?= active('assign_items') ?>">Assign Single</a>
        <a href="/itms.arukustech.com/public/multi_assign.php" class="<?= active('multi_assign') ?>">Assign Bulk</a>
        <a href="/itms.arukustech.com/public/assigned_list.php" class="<?= active('assigned_list') ?>">Assigned List</a>
    </div>

<!--     <?php if (in_array($_SESSION['role'], ['admin','superadmin'])): ?>
    <div class="section-title">External</div>
    <div onclick="toggleMenu('rentedMenu', this)" class="sidebar-parent <?= active('rented') ?>">
        <span><i class="ri-exchange-funds-fill"></i> Rented Assets</span>
        <i class="fa-solid fa-chevron-right arrow"></i>
    </div>
    <div id="rentedMenu" class="submenu" style="display: <?= active('rented') ? 'block' : 'none' ?>;">
        <a href="/rented_category.php">Category</a>
        <a href="/rented_projects.php">Projects</a>
        <a href="/rented_inventory_add.php">Add Inventory</a>
        <a href="/rented_inventory_list.php">Inventory List</a>
        <a href="/rented_assign.php">Assign Assets</a>
        <a href="/rented_assign_list.php">Assign List</a>
        <a href="/rented_reports.php">Rental Reports</a>
        <a href="/rented_audit_report.php">Rental Audit Reports</a>
        <a href="/rented_master_report.php"> Rental Master Reports</a>
    </div>
    <?php endif; ?> -->

    <div class="section-title">Analytics</div>
    <div onclick="toggleMenu('reportMenu', this)" class="sidebar-parent <?= active('reports') ?>">
        <span><i class="ri-pie-chart-2-fill"></i> Reports</span>
        <i class="fa-solid fa-chevron-right arrow"></i>
    </div>
    <div id="reportMenu" class="submenu" style="display: <?= active('reports') ? 'block' : 'none' ?>;">
        <a href="/itms.arukustech.com/public/reports_summary.php"><i class="ri-table-fill"></i> Summary</a>
        <a href="/itms.arukustech.com/public/reports_assigned_unassigned.php"><i class="ri-list-check-2"></i> Status Wise</a>
        <a href="/itms.arukustech.com/public/reports_category.php"><i class="ri-folders-fill"></i> Category Wise</a>
        <a href="/itms.arukustech.com/public/reports_brand.php"><i class="ri-price-tag-3-fill"></i> Brand Wise</a>
        <a href="/itms.arukustech.com/public/reports_user_assigned.php"><i class="ri-user-settings-fill"></i> User Assets</a>
        <a href="/itms.arukustech.com/public/reports_purchase_warranty.php"><i class="ri-verified-badge-fill"></i> Warranty</a>
        <a href="/itms.arukustech.com/public/reports_monthly_activity.php"><i class="ri-calendar-todo-fill"></i> Monthly</a>
        <a href="/itms.arukustech.com/public/reports_login_activity.php"><i class="ri-login-box-fill"></i> Login Logs</a>
        <a href="/itms.arukustech.com/public/reports_usage.php"><i class="ri-pulse-fill"></i> Usage Report</a>
        <a href="/itms.arukustech.com/public/reports_depreciation.php"><i class="ri-funds-fill"></i> Depreciation</a>
        <a href="/itms.arukustech.com/public/reports_lifecycle.php"><i class="ri-loop-right-fill"></i> Lifecycle</a>
        <a href="/itms.arukustech.com/public/reports_audit.php"><i class="ri-history-fill"></i> Audit Logs</a>
        <a href="/itms.arukustech.com/public/reports_activity_log.php"><i class="ri-user-voice-fill"></i> User Activity</a>
    </div>

    <div class="section-title">System</div>
    <a href="/itms.arukustech.com/public/users.php" class="<?= active('users') ?> fw-bold">
        <i class="ri-group-fill"></i> User Management
    </a>

    <div style="margin: 20px 10px;">
        <a href="/itms.arukustech.com/public/logout.php" class="fw-bold text-danger" style="background: rgba(220, 38, 38, 0.1); border-radius: 8px;">
            <i class="ri-shut-down-line"></i> Logout
        </a>
    </div>
</div>

<!-- TOPBAR -->
<div class="topbar d-flex align-items-center justify-content-between px-4">

    <div class="d-flex align-items-center">
        <div class="menu-toggle me-3" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars" style="color: #1e293b;"></i>
        </div>
        
        <div class="d-none d-md-flex flex-column">
            <div class="d-flex align-items-center">
                <span id="topbar_greet" class="fw-bold text-dark me-1" style="font-size: 1.1rem; letter-spacing: -0.3px;">Hello</span>
                <span class="fw-bold text-primary" style="font-size: 1.1rem;">!</span>
            </div>
            <span id="topbar_date" class="text-secondary fw-medium" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Loading Date...</span>
        </div>
    </div>

    <div class="d-none d-lg-block">
        <div id="topbar_clock" class="fw-bold shadow-sm" 
             style="background: rgba(255, 255, 255, 0.6); 
                    backdrop-filter: blur(10px);
                    color: #0d6efd;
                    padding: 8px 24px; 
                    border-radius: 50px; 
                    font-family: 'Courier New', monospace; 
                    font-size: 1.3rem; 
                    border: 1px solid rgba(13, 110, 253, 0.2);
                    box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important;
                    min-width: 180px;
                    text-align: center;">
             00:00:00 AM
        </div>
    </div>

    <div class="user-menu" onclick="toggleUserMenu()">
        <div class="user-trigger">
            <div class="user-avatar shadow-sm" style="background: #3b82f6; color: white;">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <span class="user-name d-none d-sm-inline"><?= htmlspecialchars($_SESSION['name']) ?></span>
            <i class="fa-solid fa-chevron-down user-arrow"></i>
        </div>

        <div class="dropdown shadow-lg border-0" id="userDropdown" style="border-radius: 15px; margin-top: 10px;">
            <a href="/itms.arukustech.com/public/users.php" class="py-2 px-3"><i class="fa-solid fa-circle-user me-2 text-primary"></i> My Profile</a>
            <hr class="dropdown-divider opacity-50 m-0">
            <a href="/itms.arukustech.com/public/logout.php" class="text-danger py-2 px-3"><i class="fa-solid fa-power-off me-2"></i> Sign Out</a>
        </div>
    </div>
</div>



<!-- MAIN CONTENT -->
<div class="main-content">
