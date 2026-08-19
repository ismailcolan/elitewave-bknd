<?php
require_once ('include/connect.php');
require_once ('include/function.php');

$read_status = mysqli_query($conn, 'SELECT read_status FROM `user_registrations` WHERE read_status = 0');
$count_read = mysqli_num_rows($read_status);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
  :root {
    --sidebar-width:    260px;
    --sidebar-collapsed:64px;
    --top-bar-h:        64px;
    --ew-navy:          #06416F;
    --ew-navy-deep:     #042C4A;
    --ew-navy-light:    #0A5688;
    --ew-primary:       #06416F;
    --ew-primary-dark:  #042C4A;
    --ew-primary-light: #E8EDF4;
    --ew-accent:        #DD111E;
    --ew-accent-deep:   #A50D17;
    --ew-teal:          #0891B2;
    --ew-text:          #1A2332;
    --ew-text-muted:    #6B7A8D;
    --ew-border:        #D8DDE5;
    --ew-border-light:  #E9ECF0;
    --ew-steel:         #C7D5E3;
    --ew-steel-dim:     #7E97AD;
    --font-display:     'Space Grotesk', sans-serif;
  }

  /* ---- Sidebar shell ---- */
  .sidebar {
    position: fixed;
    top: var(--top-bar-h);
    left: 0;
    bottom: 0;
    width: var(--sidebar-width);
    background: linear-gradient(185deg, var(--ew-navy) 0%, var(--ew-navy-deep) 100%);
    border-right: none;
    display: flex;
    flex-direction: column;
    z-index: 1000;
    transition: width 0.28s cubic-bezier(.4,0,.2,1);
    overflow: hidden;
    box-shadow: 4px 0 24px rgba(4,20,38,.14);
    transform: none;
  }
  .sidebar.collapsed { width: var(--sidebar-collapsed); }

  /* Scroll area */
  .sidebar-scroll {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 14px 0 20px;
    position: relative;
  }
  .sidebar-scroll::-webkit-scrollbar { width: 3px; }
  .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
  .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }

  /* Route line signature — vertical shipping route threading through the icons.
     Position is locked to the rail column's center (see --ew-rail-x below) so it
     always lines up with the waypoint dots regardless of nav-link padding. */

  .sidebar-scroll::before {
    content: "";
    position: absolute;
    left: var(--ew-rail-x);
    top: 74px;
    bottom: 16px;
    width: 1.5px;
    background: repeating-linear-gradient(
      to bottom,
      rgba(255,255,255,.16) 0, rgba(255,255,255,.16) 4px,
      transparent 4px, transparent 9px
    );
    pointer-events: none;
  }
  .sidebar.collapsed .sidebar-scroll::before { display: none; }

  /* Route progress — the segment of the line between an open section's dot and
     whichever item inside it is active lights up red, like a traveled route. */
  .route-progress {
    position: absolute;
    left: var(--ew-rail-x);
    width: 2px;
    top: 0;
    height: 0;
    background: linear-gradient(to bottom, var(--ew-accent), var(--ew-accent-deep));
    border-radius: 2px;
    opacity: 0;
    transition: top .2s ease, height .2s ease, opacity .2s ease;
    box-shadow: 0 0 8px rgba(221,17,30,.55);
    pointer-events: none;
  }
  .route-progress.visible { opacity: 1; }
  .sidebar.collapsed .route-progress { display: none; }

  /* Section label */
  .sidebar-label {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.8px;
    text-transform: uppercase;
    color: var(--ew-steel-dim);
    padding: 20px 22px 8px;
    white-space: nowrap;
    overflow: hidden;
    transition: opacity 0.2s;
    position: relative;
  }
  .sidebar.collapsed .sidebar-label { opacity: 0; pointer-events: none; }

  /* Nav item */
  .nav-item { position: relative; }

  .nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px 10px 0;
    color: var(--ew-steel);
    text-decoration: none;
    font-size: 0.93rem;
    font-weight: 500;
    border-radius: 10px;
    margin: 2px 10px;
    transition: all 0.18s ease;
    white-space: nowrap;
    cursor: pointer;
    position: relative;
    font-family: 'Inter', sans-serif;
  }
  .nav-link:hover {
    background: rgba(255,255,255,.06);
    color: #fff;
    text-decoration: none;
  }
  .nav-link.active {
    background: #DD111E;
    color: #fff !important;
    font-weight: 600;
    box-shadow: inset 0 0 0 1px rgba(221,17,30,.25);
  }
  .nav-link.active::before {
    content: "";
    position: absolute;
    left: -10px;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 60%;
    background: var(--ew-accent);
    border-radius: 0 3px 3px 0;
  }

  /* waypoint dot — fixed-width rail column so the dot always centers on the
     route line, independent of the link's own padding/indent */

  .nav-icon {
    width: 20px; height: 20px;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    opacity: 0.75;
    transition: opacity 0.18s ease, transform 0.18s ease;
  }
  .nav-link img.nav-icon {
    filter: brightness(0) invert(1);
    opacity: 0.55;
    transition: opacity 0.18s ease;
  }
  .nav-link:hover img.nav-icon { opacity: 0.9; }
  .nav-link.active img.nav-icon { opacity: 1; }
  .nav-link:hover .nav-icon { opacity: 1; transform: scale(1.08); }
  .nav-link.active .nav-icon { opacity: 1; }

  .nav-text { overflow: hidden; flex: 1; }

  .nav-arrow {
    font-size: 10px;
    color: var(--ew-steel-dim);
    transition: transform 0.25s cubic-bezier(.4,0,.2,1), color 0.15s;
    flex-shrink: 0;
    margin-left: auto;
    opacity: 0.7;
  }
  .nav-item.open > .nav-link .nav-arrow { transform: rotate(90deg); color: #fff; opacity: 1; }

  .nav-badge {
    background: var(--ew-accent);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    border-radius: 20px;
    padding: 2px 7px;
    flex-shrink: 0;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 2px 6px rgba(221,17,30,.5);
    animation: badge-pulse 2s ease-in-out infinite;
  }
  @keyframes badge-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
  }

  /* Submenu */
 .submenu {
  overflow: hidden;
  max-height: 0;
/* masks the dotted rail + route-progress line behind it */
  transition: max-height 0.35s cubic-bezier(.4,0,.2,1);
}
    .submenu .nav-waypoint {
  display: none;
} .sidebar-scroll::before {
  display: none;
}.route-progress {
  display: none;
}
                                         
.nav-item.open > .submenu { max-height: 1200px; }

.submenu .nav-link {
  padding: 9px 16px 9px 40px;   /* shifted right */
  font-size: 0.87rem;
  font-weight: 400;
  margin: 1px 10px;
  color: var(--ew-steel);
  border-radius: 8px;
}
.submenu .nav-waypoint {
  display: flex;
  height: 20px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
}
.submenu .nav-waypoint::before {
  content: "";
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: rgba(255,255,255,.55);
  border: none;
  box-shadow: none;
  transition: background .18s ease;
}
 .submenu .nav-link:hover .nav-waypoint::before {
  background: #fff;
}
.submenu .nav-link.active .nav-waypoint::before {
  background: #fff;
}

  /* Collapsed state */
  .sidebar.collapsed .nav-text,
  .sidebar.collapsed .nav-arrow,
  .sidebar.collapsed .nav-badge { display: none; }

  .sidebar.collapsed .nav-link {
    justify-content: center;
    padding: 12px;
    margin: 2px 8px;
    border-radius: 10px;
  }
  .sidebar.collapsed .nav-waypoint { display: none; }
  .sidebar.collapsed .submenu { display: none; }
  .sidebar.collapsed .nav-link.active::before { display: none; }

  .sidebar.collapsed .nav-item:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    left: calc(var(--sidebar-collapsed) + 10px);
    top: 50%;
    transform: translateY(-50%);
    background: var(--ew-navy-deep);
    color: #fff;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 7px 14px;
    border-radius: 8px;
    white-space: nowrap;
    z-index: 9999;
    pointer-events: none;
    box-shadow: 0 8px 28px rgba(4,20,38,.35);
    border: 1px solid rgba(255,255,255,.08);
    font-family: 'Inter', sans-serif;
  }

  .sidebar.collapsed .nav-item:hover { cursor: pointer; }

  .sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,.08);
    margin: 8px 22px;
  }

  .main-content {
    margin-left: var(--sidebar-width);
    transition: margin-left 0.28s cubic-bezier(.4,0,.2,1);
  }
  .main-content.collapsed { margin-left: var(--sidebar-collapsed); }

  .sidebar-backdrop {
    display: none;
  }

  @media (max-width: 767px) {
    .sidebar,
    .sidebar.collapsed {
      width: min(280px, 86vw);
      transform: translateX(-100%);
      pointer-events: none;
      box-shadow: none;
      transition: transform 0.28s cubic-bezier(.4,0,.2,1);
    }
    .sidebar.mobile-open,
    .sidebar.collapsed.mobile-open {
      transform: translateX(0);
      pointer-events: auto;
      box-shadow: 8px 0 28px rgba(4,20,38,.28);
    }
    .sidebar.collapsed .sidebar-label,
    .sidebar.collapsed .nav-text,
    .sidebar.collapsed .nav-arrow,
    .sidebar.collapsed .nav-badge,
    .sidebar.collapsed .nav-waypoint {
      display: block;
      opacity: 1;
      pointer-events: auto;
    }
    .sidebar.collapsed .nav-link {
      justify-content: flex-start;
      padding: 10px 18px;
      margin: 2px 10px;
    }
    .sidebar.collapsed .submenu { display: none; }
    .sidebar.collapsed .nav-item.open .submenu { display: block; }
    .sidebar.collapsed .nav-item:hover::after { display: none; }
    .main-content,
    .main-content.collapsed {
      margin-left: 0 !important;
    }
    .sidebar-backdrop {
      display: none;
      position: fixed;
      top: var(--top-bar-h);
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(10, 20, 32, .45);
      z-index: 999;
    }
    body.sidebar-mobile-open {
      overflow: hidden;
    }
    body.sidebar-mobile-open .sidebar-backdrop {
      display: block;
    }
  }
</style>

<div class="sidebar" id="sidebar">
  <div class="sidebar-scroll" id="sidebarScroll">
    <div class="route-progress" id="routeProgress"></div>

    <!-- Dashboard -->
    <div class="sidebar-label">Main</div>
    <div class="nav-item" data-tooltip="Dashboard">
      <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
        <span class="nav-waypoint"></span>
        <img src="./images/dashboard.png" class="nav-icon" alt="">
        <span class="nav-text">Dashboard</span>
      </a>
    </div>

    <?php if ($_SESSION['role'] == 'AD'): ?>

    <div class="sidebar-label">Administration</div>

    <!-- Master -->
    <div class="nav-item" data-tooltip="Master" id="item-master">
      <div class="nav-link" onclick="toggleMenu('master')">
        <span class="nav-waypoint"></span>
        <img src="./images/master2.png" class="nav-icon" alt="">
        <span class="nav-text">Master</span>
        <i class="fa fa-chevron-right nav-arrow"></i>
      </div>
      <div class="submenu" id="menu-master">
        <a class="nav-link" href="company.php"><span class="nav-waypoint"></span>Company</a>
        <a class="nav-link" href="branch.php"><span class="nav-waypoint"></span>Branch</a>
        <a class="nav-link" href="state.php"><span class="nav-waypoint"></span>State</a>
        <a class="nav-link" href="city.php"><span class="nav-waypoint"></span>City</a>
        <a class="nav-link" href="hub.php"><span class="nav-waypoint"></span>Hub</a>
        <a class="nav-link" href="mode_of_transportation.php"><span class="nav-waypoint"></span>Mode of Transport</a>
        <a class="nav-link" href="client.php"><span class="nav-waypoint"></span>Client</a>
        <a class="nav-link" href="client_branch.php"><span class="nav-waypoint"></span>Client Branch</a>
        <a class="nav-link" href="rate_calculator_form.php"><span class="nav-waypoint"></span>Rate Calculator</a>
        <a class="nav-link" href="expected_delivery_form.php"><span class="nav-waypoint"></span>Expected Delivery</a>
        <a class="nav-link" href="consigner_payment_form.php"><span class="nav-waypoint"></span>Client Charges</a>
        <a class="nav-link" href="qrcode_master.php"><span class="nav-waypoint"></span>Print QR Code</a>
        <a class="nav-link" href="bulkmail.php"><span class="nav-waypoint"></span>Bulk Email</a>
        <a class="nav-link" href="users.php"><span class="nav-waypoint"></span>Users</a>
        <a class="nav-link" href="consignment_mode.php"><span class="nav-waypoint"></span>Consignment Mode</a>
        <a class="nav-link" href="package_type.php"><span class="nav-waypoint"></span>Package Type</a>
        <a class="nav-link" href="vehicle.php"><span class="nav-waypoint"></span>Vehicle</a>
        <a class="nav-link" href="train.php"><span class="nav-waypoint"></span>Train</a>
        <a class="nav-link" href="flight.php"><span class="nav-waypoint"></span>Flight</a>
      </div>
    </div>

    <!-- User Management -->
    <div class="nav-item" data-tooltip="User" id="item-user">
      <div class="nav-link" onclick="toggleMenu('user')">
        <span class="nav-waypoint"></span>
        <img src="icons/user-check.png" class="nav-icon" alt="">
        <span class="nav-text">User</span>
        <?php if ($count_read > 0): ?>
          <span class="nav-badge"><?php echo $count_read; ?></span>
        <?php endif; ?>
        <i class="fa fa-chevron-right nav-arrow"></i>
      </div>
      <div class="submenu" id="menu-user">
        <a class="nav-link" href="user-draftconsignment.php"><span class="nav-waypoint"></span>Draft Consignment List</a>
        <a class="nav-link" href="user-inquiry.php"><span class="nav-waypoint"></span>User Inquiry List</a>
        <a class="nav-link" href="user-approval.php"><span class="nav-waypoint"></span>User Approval</a>
        <a class="nav-link" href="user-requestpickup-list.php"><span class="nav-waypoint"></span>Request For Pickup List</a>
        <a class="nav-link" href="user_ftl_list.php"><span class="nav-waypoint"></span>Pending FTL Quotation</a>
        <a class="nav-link" href="pod_master.php"><span class="nav-waypoint"></span>Proof of Delivery</a>
        <a class="nav-link" href="user_registration_request.php">
          <span class="nav-waypoint"></span>
          Registration Request
          <?php if ($count_read > 0): ?>
            <span class="nav-badge" style="margin-left:4px"><?php echo $count_read; ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>

    <?php endif; ?>

    <!-- Transactions -->
    <div class="sidebar-divider"></div>
    <div class="sidebar-label">Operations</div>

    <div class="nav-item" data-tooltip="Transactions" id="item-transactions">
      <div class="nav-link" onclick="toggleMenu('transactions')">
        <span class="nav-waypoint"></span>
        <img src="./icons/009-truck-2.png" class="nav-icon" alt="">
        <span class="nav-text">Transactions</span>
        <i class="fa fa-chevron-right nav-arrow"></i>
      </div>
      <div class="submenu" id="menu-transactions">
        <?php if ($_SESSION['role'] != 'DR'): ?>
          <a class="nav-link" href="transactions.php"><span class="nav-waypoint"></span>Book a Consignment</a>
          <a class="nav-link" href="transaction_list.php"><span class="nav-waypoint"></span>List of Consignments</a>
          <a class="nav-link" href="request_for_new_pickup.php"><span class="nav-waypoint"></span>Request For Pickup</a>
        <?php endif; ?>
        <a class="nav-link" href="track_consignment.php"><span class="nav-waypoint"></span>Track Consignment</a>
        <?php if ($_SESSION['role'] == 'AD' || $_SESSION['role'] == 'USER'): ?>
          <a class="nav-link" href="status_sheet.php"><span class="nav-waypoint"></span>Consignment Status Sheet</a>
          <a class="nav-link" href="transaction_status.php"><span class="nav-waypoint"></span>Transaction Status</a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] == 'AD'): ?>
          <a class="nav-link" href="transactions_manual.php"><span class="nav-waypoint"></span>Book Manual Consignment</a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] == 'DR'): ?>
          <a class="nav-link" href="pod_master.php"><span class="nav-waypoint"></span>Upload Proof of Delivery</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- MIS Report -->
    <?php if ($_SESSION['role'] != 'DR'): ?>
    <div class="nav-item" data-tooltip="MIS Report" id="item-mis">
      <div class="nav-link" onclick="toggleMenu('mis')">
        <span class="nav-waypoint"></span>
        <img src="./images/master2.png" class="nav-icon" alt="">
        <span class="nav-text">MIS Report</span>
        <i class="fa fa-chevron-right nav-arrow"></i>
      </div>
      <div class="submenu" id="menu-mis">
        <?php if ($_SESSION['role'] == 'CL'): ?>
          <a class="nav-link" href="consignment_report.php"><span class="nav-waypoint"></span>My Booking Report</a>
          <a class="nav-link" href="client_arrival_report.php"><span class="nav-waypoint"></span>My Arrival Report</a>
        <?php else: ?>
          <a class="nav-link" href="consignment_report.php"><span class="nav-waypoint"></span>Booking Report</a>
         <a class="nav-link" href="cargo_booking_report.php"><span class="nav-waypoint"></span>Cargo Booking Report</a>
          <a class="nav-link" href="client_payment_transactions.php"><span class="nav-waypoint"></span>Payment History</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Customer Mapping -->
    <?php if ($_SESSION['role'] == 'AD' || $_SESSION['role'] == 'USER'): ?>
    <div class="nav-item" data-tooltip="Customer Mapping" id="item-cm">
      <div class="nav-link" onclick="toggleMenu('cm')">
        <span class="nav-waypoint"></span>
        <img src="./icons/008-box-2.png" class="nav-icon" alt="">
        <span class="nav-text">Customer Mapping</span>
        <i class="fa fa-chevron-right nav-arrow"></i>
      </div>
      <div class="submenu" id="menu-cm">
        <a class="nav-link" href="customer_mapping.php"><span class="nav-waypoint"></span>Customer Mapping</a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeMobileSidebar()"></div>

<script>
  function isMobileNav() {
    return window.matchMedia('(max-width: 767px)').matches;
  }

  function closeMobileSidebar() {
    const sb = document.getElementById('sidebar');
    const hamburger = document.getElementById('hamburgerBtn');
    if (sb) sb.classList.remove('mobile-open');
    document.body.classList.remove('sidebar-mobile-open');
    if (hamburger) hamburger.classList.remove('is-open');
  }

  function toggleSidebar() {
    const sb  = document.getElementById('sidebar');
    const tb  = document.getElementById('topBar');
    const mc  = document.querySelector('.main-content');
    const hamburger = document.getElementById('hamburgerBtn');

    if (isMobileNav()) {
      const opening = !sb.classList.contains('mobile-open');
      sb.classList.toggle('mobile-open', opening);
      document.body.classList.toggle('sidebar-mobile-open', opening);
      if (hamburger) hamburger.classList.toggle('is-open', opening);
      return;
    }

    const isCollapsing = !sb.classList.contains('collapsed');

    sb.classList.toggle('collapsed');
    if (tb)  tb.classList.toggle('collapsed');
    if (mc)  mc.classList.toggle('collapsed');
    if (hamburger) hamburger.classList.toggle('is-open', !isCollapsing);

    localStorage.setItem('sidebar_collapsed', sb.classList.contains('collapsed') ? '1' : '0');
    window.dispatchEvent(new CustomEvent('sidebarToggled', { detail: { collapsed: sb.classList.contains('collapsed') } }));
  }

  document.addEventListener('DOMContentLoaded', function () {
    const sb = document.getElementById('sidebar');
    sb.addEventListener('click', function (e) {
      if (sb.classList.contains('collapsed')) {
        const link = e.target.closest('.nav-link');
        if (link) toggleSidebar();
      }
    });
  });

  function toggleMenu(id) {
    const sb = document.getElementById('sidebar');
    if (!isMobileNav() && sb.classList.contains('collapsed')) { toggleSidebar(); return; }
    const item = document.getElementById('item-' + id);
    const wasOpen = item.classList.contains('open');
    document.querySelectorAll('.nav-item.open').forEach(el => el.classList.remove('open'));
    if (!wasOpen) item.classList.add('open');
    updateRouteProgress();
  }

  /* Route progress: lights up the line from an open section's own dot down to
     whichever item inside it is currently active — a "traveled route" cue. */
  function updateRouteProgress() {
    const bar = document.getElementById('routeProgress');
    const scrollEl = document.getElementById('sidebarScroll');
    const sb = document.getElementById('sidebar');
    if (!bar || !scrollEl) return;

    if (sb.classList.contains('collapsed')) {
      bar.classList.remove('visible');
      return;
    }

    const openItem = document.querySelector('.nav-item.open');
    if (!openItem) { bar.classList.remove('visible'); return; }

    const parentDot = openItem.querySelector(':scope > .nav-link .nav-waypoint');
    const activeLink = openItem.querySelector('.submenu .nav-link.active');
    if (!parentDot) { bar.classList.remove('visible'); return; }

    const scrollRect = scrollEl.getBoundingClientRect();
    const parentRect = parentDot.getBoundingClientRect();
    const startY = (parentRect.top - scrollRect.top) + scrollEl.scrollTop + parentRect.height / 2;

    let endY = startY;
    if (activeLink) {
      const activeDot = activeLink.querySelector('.nav-waypoint');
      if (activeDot) {
        const activeRect = activeDot.getBoundingClientRect();
        endY = (activeRect.top - scrollRect.top) + scrollEl.scrollTop + activeRect.height / 2;
      }
    }

    const top = Math.min(startY, endY);
    const height = Math.max(3, Math.abs(endY - startY));
    bar.style.top = top + 'px';
    bar.style.height = height + 'px';
    bar.classList.add('visible');
  }

  document.getElementById('sidebarScroll').addEventListener('scroll', updateRouteProgress);
  window.addEventListener('resize', updateRouteProgress);
  window.addEventListener('sidebarToggled', updateRouteProgress);

  (function () {
    if (localStorage.getItem('sidebar_collapsed') === '1') {
      const sb = document.getElementById('sidebar');
      const tb = document.getElementById('topBar');
      const mc = document.querySelector('.main-content');
      const hb = document.getElementById('hamburgerBtn');
      if (sb) sb.classList.add('collapsed');
      if (tb) tb.classList.add('collapsed');
      if (mc) mc.classList.add('collapsed');
      if (hb) hb.classList.remove('is-open');
    }
    if (isMobileNav()) {
      closeMobileSidebar();
    }
    window.addEventListener('resize', function () {
      if (!isMobileNav()) {
        closeMobileSidebar();
      } else {
        closeMobileSidebar();
      }
    });
    document.querySelectorAll('.sidebar .nav-link[href]').forEach(function (link) {
      link.addEventListener('click', function () {
        if (isMobileNav()) closeMobileSidebar();
      });
    });
  })();

  (function () {
    const page = window.location.pathname.split('/').pop();
    document.querySelectorAll('.submenu .nav-link').forEach(link => {
      if (link.getAttribute('href') === page) {
        link.classList.add('active');
        const item = link.closest('.nav-item');
        if (item) item.classList.add('open');
      }
    });
    updateRouteProgress();
  })();
</script>