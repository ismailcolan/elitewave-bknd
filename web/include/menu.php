<?php
/* Dual-rail sidebar (2026-09-10).
   Rollback: menu.php.rollback-dual-rail | menu.php.rollback-modules | menu.php.rollback */
require_once ('include/connect.php');
require_once ('include/function.php');

$read_status = mysqli_query($conn, 'SELECT read_status FROM `user_registrations` WHERE read_status = 0');
$count_read = mysqli_num_rows($read_status);

$current_page = basename($_SERVER['PHP_SELF']);

$page_module_map = array(
  'dashboard.php' => 'dashboard',
  'company.php' => 'setup', 'company_bank.php' => 'setup', 'branch.php' => 'setup',
  'state.php' => 'setup', 'city.php' => 'setup', 'hub.php' => 'setup',
  'mode_of_transportation.php' => 'transport', 'consignment_mode.php' => 'transport',
  'package_type.php' => 'transport', 'vehicle.php' => 'transport', 'vehicle_list.php' => 'transport',
  'train.php' => 'transport', 'flight.php' => 'transport',
  'client.php' => 'party', 'client_list.php' => 'party',
  'client_branch.php' => 'party', 'client_branch_list.php' => 'party',
  'vendor.php' => 'party', 'vendor_list.php' => 'party',
  'gst_tax_master.php' => 'billing', 'gst_tax_form.php' => 'billing',
  'consigner_payment_form.php' => 'billing', 'consigner_payment_list.php' => 'billing',
  'rate_calculator_form.php' => 'billing', 'rate_calc_list.php' => 'billing',
  'expected_delivery_form.php' => 'billing', 'expected_delivery_list.php' => 'billing',
  'expense_type_master.php' => 'expense', 'expense_type_form.php' => 'expense',
  'expense_group_master.php' => 'expense',
  'expense_gcn.php' => 'expense', 'expense_gcn_list.php' => 'expense',
  'expense_general.php' => 'expense', 'expense_general_list.php' => 'expense',
  'qrcode_master.php' => 'tools', 'bulkmail.php' => 'tools', 'users.php' => 'tools', 'user_list.php' => 'tools',
  'pod_list.php' => 'portal', 'pod_master.php' => 'portal', 'edit_pod_list.php' => 'portal',
  'user-draftconsignment.php' => 'portal', 'user-inquiry.php' => 'portal',
  'user-approval.php' => 'portal', 'user-requestpickup-list.php' => 'portal',
  'user_ftl_list.php' => 'portal', 'user_registration_request.php' => 'portal',
  'transactions.php' => 'consignment', 'transaction_list.php' => 'consignment',
  'request_for_new_pickup.php' => 'consignment', 'track_consignment.php' => 'consignment',
  'status_sheet_list.php' => 'consignment', 'status_sheet.php' => 'consignment',
  'edit_status_sheet.php' => 'consignment',
  'trip_summary_list.php' => 'consignment', 'trip_summary.php' => 'consignment',
  'trip_summary_print.php' => 'consignment',
  'transaction_status.php' => 'consignment', 'transactions_manual.php' => 'consignment',
  'create_invoice.php' => 'invoice', 'invoice_list.php' => 'invoice',
  'credit_note_list.php' => 'invoice', 'create_credit_note.php' => 'invoice',
  'consignment_report.php' => 'mis', 'client_arrival_report.php' => 'mis',
  'cargo_booking_report.php' => 'mis', 'gst_tax_report.php' => 'mis',
  'client_payment_transactions.php' => 'mis',
  'customer_mapping.php' => 'mapping',
  'customer_mapping_form.php' => 'mapping',
);

if ($current_page === 'pod_master.php') {
  $active_module = ($_SESSION['role'] == 'DR') ? 'consignment' : 'portal';
} elseif (isset($page_module_map[$current_page])) {
  $active_module = $page_module_map[$current_page];
} else {
  $active_module = 'dashboard';
}

function ew_menu_active($page, $current_page) {
  return ($page === $current_page) ? ' active' : '';
}

function ew_menu_active_any($pages, $current_page) {
  return in_array($current_page, $pages, true) ? ' active' : '';
}

function ew_module_active($module, $active_module) {
  return ($module === $active_module) ? ' active' : '';
}

function ew_panel_head($title) {
  echo '<div class="panel-head">';
  echo '<div class="panel-head-normal">';
  echo '<div class="panel-head-left">';
  echo '<span class="panel-pill">' . $title . '</span>';
  echo '</div>';
  echo '<button type="button" class="panel-search-open" aria-label="Search screens"><i class="fa fa-search"></i></button>';
  echo '</div>';
  echo '<div class="panel-head-search">';
  echo '<input type="search" class="panel-search" placeholder="Search" autocomplete="off" aria-label="Search screens">';
  echo '<button type="button" class="panel-search-close" aria-label="Close search"><i class="fa fa-times"></i></button>';
  echo '</div>';
  echo '</div>';
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
  :root {
    --rail-w:             88px;
    --panel-w:            248px;
    --sidebar-width:      calc(var(--rail-w) + var(--panel-w));
    --sidebar-collapsed:  var(--rail-w);
    --top-bar-h:          72px;
    --ew-navy:            #06416F;
    --ew-navy-deep:       #042C4A;
    --ew-accent:          #DD111E;
    --ew-accent-deep:     #A50D17;
    --ew-text:            #1A2332;
    --ew-text-muted:      #6B7A8D;
    --ew-border:          #D8DDE5;
    --rail-bg:            #DDE7F0;
    --rail-hover-bg:      #CFDBE8;
    --panel-bg:           #FAFCFE;
    --panel-head-bg:      #F3F7FB;
    --panel-link-hover:   #EEF3F8;
    --panel-border:       #C5D3E0;
    --rail-panel-divider: #B8C9DA;
    --font-display:       'Space Grotesk', sans-serif;
  }

  .dual-sidebar {
    position: fixed;
    top: var(--top-bar-h);
    left: 0;
    bottom: 0;
    width: var(--sidebar-width);
    display: flex;
    z-index: 1000;
    transition: width 0.28s cubic-bezier(.4,0,.2,1);
    box-shadow: 4px 0 24px rgba(4,20,38,.1);
  }
  .dual-sidebar.collapsed {
    width: var(--sidebar-collapsed);
  }

  .icon-rail {
    width: var(--rail-w);
    flex-shrink: 0;
    background: var(--rail-bg);
    border-right: 1px solid var(--rail-panel-divider);
    box-shadow: inset -1px 0 0 rgba(255,255,255,.45);
    overflow-y: auto;
    overflow-x: hidden;
    padding: 12px 0 16px;
  }
  .icon-rail::-webkit-scrollbar { width: 3px; }
  .icon-rail::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 2px; }

  .rail-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    width: calc(100% - 16px);
    padding: 10px 6px;
    margin: 3px 8px;
    border: none;
    background: transparent;
    border-radius: 12px;
    cursor: pointer;
    color: var(--ew-navy);
    text-decoration: none;
    font-family: 'Inter', sans-serif;
    transition: background 0.15s, color 0.15s, box-shadow 0.15s;
    position: relative;
  }
  .rail-item:hover:not(.active) {
    background: var(--rail-hover-bg);
    color: var(--ew-navy);
    text-decoration: none;
    box-shadow: none;
  }
  .rail-item.active,
  .rail-item.active:hover {
    background: var(--ew-navy);
    color: #fff;
    box-shadow: 0 4px 12px rgba(6,65,111,.2);
  }
  .rail-item.active .rail-label {
    font-weight: 700;
  }

  .rail-item[data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    left: calc(100% + 10px);
    top: 50%;
    transform: translateY(-50%);
    background: var(--ew-navy-deep);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    padding: 7px 12px;
    border-radius: 8px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.15s ease, visibility 0.15s ease;
    z-index: 1100;
    box-shadow: 0 8px 24px rgba(4,20,38,.22);
    font-family: 'Inter', sans-serif;
  }
  .rail-item[data-tooltip]:hover::after {
    opacity: 1;
    visibility: visible;
  }

  .rail-icon {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .rail-icon img {
    width: 22px;
    height: 22px;
    object-fit: contain;
    filter: brightness(0);
    opacity: 0.92;
    transition: filter 0.15s ease, opacity 0.15s ease;
  }
  .rail-icon .fa {
    font-size: 17px;
    line-height: 1;
    color: #1A2332;
    opacity: 0.92;
    transition: color 0.15s ease, opacity 0.15s ease;
  }
  .rail-item:hover:not(.active) .rail-icon img {
    filter: brightness(0);
    opacity: 1;
  }
  .rail-item:hover:not(.active) .rail-icon .fa {
    color: var(--ew-navy);
    opacity: 1;
  }
  .rail-item.active .rail-icon img,
  .rail-item.active:hover .rail-icon img {
    filter: brightness(0) invert(1);
    opacity: 1;
  }
  .rail-item.active .rail-icon .fa,
  .rail-item.active:hover .rail-icon .fa {
    color: #fff;
    opacity: 1;
  }

  .rail-label {
    font-size: 10px;
    font-weight: 600;
    line-height: 1.15;
    text-align: center;
    max-width: 72px;
  }

  .rail-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #C7D9EC;
    color: var(--ew-navy);
    font-size: 9px;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
  }
  .rail-item.active .rail-badge {
    background: rgba(255,255,255,.28);
    color: #fff;
  }

  .sub-panel {
    width: var(--panel-w);
    flex-shrink: 0;
    background: var(--panel-bg);
    border-right: 1px solid var(--panel-border);
    border-left: 1px solid rgba(255,255,255,.65);
    box-shadow: 2px 0 12px rgba(6,65,111,.04);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: width 0.28s cubic-bezier(.4,0,.2,1), opacity 0.2s;
  }
  .dual-sidebar.collapsed .sub-panel {
    width: 0;
    opacity: 0;
    pointer-events: none;
    border-right: none;
  }

  .panel-module {
    display: none;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
  }
  .panel-module.active {
    display: flex;
  }

  .panel-head {
    background: var(--panel-head-bg);
    border-bottom: 1px solid var(--panel-border);
    flex-shrink: 0;
  }
  .panel-head-normal,
  .panel-head-search {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 12px 12px 10px;
    min-height: 48px;
    background: var(--panel-head-bg);
  }
  .panel-head-left {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    flex: 1;
  }
  .panel-head-search {
    display: none;
  }
  .panel-head.is-search-open .panel-head-normal {
    display: none;
  }
  .panel-head.is-search-open .panel-head-search {
    display: flex;
  }
  .panel-search-open,
  .panel-search-close {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    color: var(--ew-text);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    padding: 0;
  }
  .panel-search-open:hover,
  .panel-search-close:hover {
    background: var(--panel-link-hover);
    color: var(--ew-navy);
  }
  .panel-search-open .fa { font-size: 15px; opacity: 0.85; }
  .panel-search-close .fa { font-size: 14px; opacity: 0.7; }
  .panel-search {
    flex: 1;
    min-width: 0;
    height: 32px;
    padding: 0;
    border: none;
    background: transparent;
    font-size: 13px;
    font-family: 'Inter', sans-serif;
    color: var(--ew-text);
  }
  .panel-search::placeholder { color: #94A3B8; }
  .panel-search:focus {
    outline: none;
  }
  .panel-link.is-filtered-out { display: none !important; }
  .panel-no-results {
    display: none;
    padding: 14px 12px 6px;
    text-align: center;
    font-size: 12px;
    color: var(--ew-text-muted);
  }
  .panel-no-results.visible { display: block; }
  .panel-pill {
    background: #E2ECF6;
    color: var(--ew-navy);
    border: 1px solid #C9D9EA;
    font-size: 12px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 20px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 160px;
  }
  .panel-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0px 16px;
    background: var(--panel-bg);
  }
  .panel-scroll::-webkit-scrollbar { width: 4px; }
  .panel-scroll::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 2px; }

  .panel-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px 10px 18px;
    margin: 0;
    border-radius: 0;
    font-size: 13px;
    font-weight: 500;
    color: #1A2332;
    text-decoration: none;
    position: relative;
    font-family: 'Inter', sans-serif;
  }
  .panel-link::before {
    content: none;
    display: none;
  }
  .panel-link:hover {
    background: var(--panel-link-hover);
    color: var(--ew-navy);
    text-decoration: none;
    box-shadow: none;
  }
  .panel-link.active {
    background: #FFF5F5;
    color: #DD111E !important;
    font-weight: 700;
    box-shadow: inset 3px 0 0 #DD111E;
  }
  .panel-link.active:hover {
    background: #FFF5F5;
    color: #DD111E !important;
    box-shadow: inset 3px 0 0 #DD111E;
  }
  .panel-link.active::before,
  .panel-link.active::after {
    content: none;
    display: none;
  }
  .panel-link-text .panel-search-hit {
    background: #FEF3C7;
    color: #B45309;
    font-weight: 700;
    padding: 0 2px;
    border-radius: 3px;
  }
  .panel-link.active .panel-link-text .panel-search-hit {
    background: rgba(255,255,255,.28);
    color: #fff;
  }

  .panel-badge {
    margin-left: auto;
    background: var(--ew-accent);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    border-radius: 20px;
    padding: 2px 7px;
    flex-shrink: 0;
  }

  .sidebar-backdrop { display: none; }

  @media (max-width: 767px) {
    .dual-sidebar,
    .dual-sidebar.collapsed {
      width: calc(var(--rail-w) + var(--panel-w));
      transform: translateX(-100%);
      pointer-events: none;
      transition: transform 0.28s cubic-bezier(.4,0,.2,1);
    }
    .dual-sidebar.mobile-open,
    .dual-sidebar.collapsed.mobile-open {
      transform: translateX(0);
      pointer-events: auto;
    }
    .dual-sidebar.collapsed .sub-panel {
      width: var(--panel-w);
      opacity: 1;
      pointer-events: auto;
      border-right: 1px solid var(--ew-border);
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
    body.sidebar-mobile-open { overflow: hidden; }
    body.sidebar-mobile-open .sidebar-backdrop { display: block; }
  }
</style>

<div class="dual-sidebar" id="sidebar">
  <nav class="icon-rail" id="iconRail">

    <a class="rail-item<?php echo ew_module_active('dashboard', $active_module); ?>" href="dashboard.php" data-module="dashboard" data-tooltip="Dashboard" title="Dashboard">
      <span class="rail-icon"><img src="./images/dashboard.png" alt=""></span>
      <span class="rail-label">Dashboard</span>
    </a>
    <button type="button" class="rail-item<?php echo ew_module_active('consignment', $active_module); ?>" data-module="consignment" data-tooltip="Consignment" title="Consignment">
      <span class="rail-icon"><img src="./icons/png/009-delivery-packaging-box.png" alt=""></span>
      <span class="rail-label">Bookings</span>
    </button>
    <?php if ($_SESSION['role'] == 'AD' || $_SESSION['role'] == 'USER'): ?>
    <button type="button" class="rail-item<?php echo ew_module_active('invoice', $active_module); ?>" data-module="invoice" data-tooltip="Invoice" title="Invoice">
      <span class="rail-icon"><img src="./icons/png/006-commercial-delivery-symbol-of-a-list-on-clipboard-on-a-box-package.png" alt=""></span>
      <span class="rail-label">Invoicing</span>
    </button>
    <?php endif; ?>

    <?php if ($_SESSION['role'] != 'DR'): ?>
    <button type="button" class="rail-item<?php echo ew_module_active('mis', $active_module); ?>" data-module="mis" data-tooltip="MIS Report" title="MIS Report">
      <span class="rail-icon"><img src="./icons/review-topic.png" alt=""></span>
      <span class="rail-label">MIS</span>
    </button>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == 'AD'): ?>
    <button type="button" class="rail-item<?php echo ew_module_active('setup', $active_module); ?>" data-module="setup" data-tooltip="Setup Masters" title="Setup Masters">
      <span class="rail-icon"><img src="./icons/department.png" alt=""></span>
      <span class="rail-label">Setup</span>
    </button>
    <button type="button" class="rail-item<?php echo ew_module_active('transport', $active_module); ?>" data-module="transport" data-tooltip="Transport Masters" title="Transport Masters">
      <span class="rail-icon"><img src="./icons/png/003-delivery-truck-with-circular-clock.png" alt=""></span>
      <span class="rail-label">Transport</span>
    </button>
    <button type="button" class="rail-item<?php echo ew_module_active('party', $active_module); ?>" data-module="party" data-tooltip="Party Masters" title="Party Masters">
      <span class="rail-icon"><img src="./icons/png/007-delivery-worker-giving-a-box-to-a-receiver.png" alt=""></span>
      <span class="rail-label">Party</span>
    </button>
    <button type="button" class="rail-item<?php echo ew_module_active('billing', $active_module); ?>" data-module="billing" data-tooltip="Billing &amp; Tax" title="Billing &amp; Tax">
      <span class="rail-icon"><img src="./icons/002-cart.png" alt=""></span>
      <span class="rail-label">Billing</span>
    </button>
    <button type="button" class="rail-item<?php echo ew_module_active('expense', $active_module); ?>" data-module="expense" data-tooltip="Expense" title="Expense">
      <span class="rail-icon"><img src="./icons/pickup.png" alt=""></span>
      <span class="rail-label">Expense</span>
    </button>
    <button type="button" class="rail-item<?php echo ew_module_active('tools', $active_module); ?>" data-module="tools" data-tooltip="Tools &amp; Users" title="Tools &amp; Users">
      <span class="rail-icon"><i class="fa fa-wrench" aria-hidden="true"></i></span>
      <span class="rail-label">Tools</span>
    </button>
    <?php if ($_SESSION['role'] == 'AD' || $_SESSION['role'] == 'USER'): ?>
    <button type="button" class="rail-item<?php echo ew_module_active('mapping', $active_module); ?>" data-module="mapping" data-tooltip="Customer Mapping" title="Customer Mapping">
      <span class="rail-icon"><img src="./icons/png/004-delivery-package-opened.png" alt=""></span>
      <span class="rail-label">Mapping</span>
    </button>
    <?php endif; ?>
    <button type="button" class="rail-item<?php echo ew_module_active('portal', $active_module); ?>" data-module="portal" data-tooltip="User Portal" title="User Portal">
      <span class="rail-icon"><img src="./icons/user-check.png" alt=""></span>
      <span class="rail-label">Portal</span>
      <?php if ($count_read > 0): ?><span class="rail-badge"><?php echo $count_read; ?></span><?php endif; ?>
    </button>
    <?php endif; ?>

  </nav>

  <div class="sub-panel" id="subPanel">
    <div class="panel-module<?php echo ew_module_active('dashboard', $active_module); ?>" data-module="dashboard">
      <?php ew_panel_head('Dashboard'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active('dashboard.php', $current_page); ?>" href="dashboard.php">Dashboard</a>
      </div>
    </div>

    <?php if ($_SESSION['role'] == 'AD'): ?>
    <div class="panel-module<?php echo ew_module_active('setup', $active_module); ?>" data-module="setup">
      <?php ew_panel_head('Setup Masters'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active('company.php', $current_page); ?>" href="company.php">Company</a>
        <a class="panel-link<?php echo ew_menu_active('company_bank.php', $current_page); ?>" href="company_bank.php">Company Bank</a>
        <a class="panel-link<?php echo ew_menu_active('branch.php', $current_page); ?>" href="branch.php">Branch</a>
        <a class="panel-link<?php echo ew_menu_active('state.php', $current_page); ?>" href="state.php">State</a>
        <a class="panel-link<?php echo ew_menu_active('city.php', $current_page); ?>" href="city.php">City</a>
        <a class="panel-link<?php echo ew_menu_active('hub.php', $current_page); ?>" href="hub.php">Hub</a>
      </div>
    </div>

    <div class="panel-module<?php echo ew_module_active('transport', $active_module); ?>" data-module="transport">
      <?php ew_panel_head('Transport Masters'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active('mode_of_transportation.php', $current_page); ?>" href="mode_of_transportation.php">Mode of Transport</a>
        <a class="panel-link<?php echo ew_menu_active('consignment_mode.php', $current_page); ?>" href="consignment_mode.php">Consignment Mode</a>
        <a class="panel-link<?php echo ew_menu_active('package_type.php', $current_page); ?>" href="package_type.php">Package Type</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('vehicle_list.php', 'vehicle.php'), $current_page); ?>" href="vehicle_list.php">Vehicle</a>
        <a class="panel-link<?php echo ew_menu_active('train.php', $current_page); ?>" href="train.php">Train</a>
        <a class="panel-link<?php echo ew_menu_active('flight.php', $current_page); ?>" href="flight.php">Flight</a>
      </div>
    </div>

    <div class="panel-module<?php echo ew_module_active('party', $active_module); ?>" data-module="party">
      <?php ew_panel_head('Party Masters'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active_any(array('client_list.php', 'client.php'), $current_page); ?>" href="client_list.php">Client</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('client_branch_list.php', 'client_branch.php'), $current_page); ?>" href="client_branch_list.php">Client Branch</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('vendor_list.php', 'vendor.php'), $current_page); ?>" href="vendor_list.php">Vendor</a>
      </div>
    </div>

    <div class="panel-module<?php echo ew_module_active('billing', $active_module); ?>" data-module="billing">
      <?php ew_panel_head('Billing &amp; Tax'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active_any(array('gst_tax_master.php', 'gst_tax_form.php'), $current_page); ?>" href="gst_tax_master.php">GST Tax Master</a>
       
      </div>
    </div>

    <div class="panel-module<?php echo ew_module_active('expense', $active_module); ?>" data-module="expense">
      <?php ew_panel_head('Expense'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active('expense_group_master.php', $current_page); ?>" href="expense_group_master.php">Expense Group Master</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('expense_type_master.php', 'expense_type_form.php'), $current_page); ?>" href="expense_type_master.php">Expense Type Master</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('expense_gcn_list.php', 'expense_gcn.php'), $current_page); ?>" href="expense_gcn_list.php">Expense against GCN</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('expense_general_list.php', 'expense_general.php'), $current_page); ?>" href="expense_general_list.php">General Expense</a>
      </div>
    </div>

    <div class="panel-module<?php echo ew_module_active('tools', $active_module); ?>" data-module="tools">
      <?php ew_panel_head('Tools &amp; Users'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active('qrcode_master.php', $current_page); ?>" href="qrcode_master.php">Print QR Code</a>
        <a class="panel-link<?php echo ew_menu_active('bulkmail.php', $current_page); ?>" href="bulkmail.php">Bulk Email</a>
        <a class="panel-link<?php echo ew_menu_active('user_list.php', $current_page); ?>" href="user_list.php">Users</a>
      </div>
    </div>

    <div class="panel-module<?php echo ew_module_active('portal', $active_module); ?>" data-module="portal">
      <?php ew_panel_head('User Portal'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active('user-draftconsignment.php', $current_page); ?>" href="user-draftconsignment.php">Draft Consignment List</a>
        <a class="panel-link<?php echo ew_menu_active('user-inquiry.php', $current_page); ?>" href="user-inquiry.php">User Inquiry List</a>
        <a class="panel-link<?php echo ew_menu_active('user-approval.php', $current_page); ?>" href="user-approval.php">User Approval</a>
        <a class="panel-link<?php echo ew_menu_active('user-requestpickup-list.php', $current_page); ?>" href="user-requestpickup-list.php">Request For Pickup List</a>
        <a class="panel-link<?php echo ew_menu_active('user_ftl_list.php', $current_page); ?>" href="user_ftl_list.php">Pending FTL Quotation</a>
        <a class="panel-link<?php echo ew_menu_active('pod_list.php', $current_page); ?>" href="pod_list.php">Proof of Delivery</a>
        <a class="panel-link<?php echo ew_menu_active('user_registration_request.php', $current_page); ?>" href="user_registration_request.php">
          Registration Request
          <?php if ($count_read > 0): ?><span class="panel-badge"><?php echo $count_read; ?></span><?php endif; ?>
        </a>
        <a class="panel-link<?php echo ew_menu_active_any(array('consigner_payment_list.php', 'consigner_payment_form.php'), $current_page); ?>" href="consigner_payment_list.php">Client Charges</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('rate_calc_list.php', 'rate_calculator_form.php'), $current_page); ?>" href="rate_calc_list.php">Rate Calculator</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('expected_delivery_list.php', 'expected_delivery_form.php'), $current_page); ?>" href="expected_delivery_list.php">Expected Delivery</a>
        <a class="panel-link<?php echo ew_menu_active('request_for_new_pickup.php', $current_page); ?>" href="request_for_new_pickup.php">Request For Pickup</a>
      </div>
    </div>
    <?php endif; ?>

    <div class="panel-module<?php echo ew_module_active('consignment', $active_module); ?>" data-module="consignment">
      <?php ew_panel_head('Consignment'); ?>
      <div class="panel-scroll">
        <?php if ($_SESSION['role'] != 'DR'): ?>
          <a class="panel-link<?php echo ew_menu_active('transactions.php', $current_page); ?>" href="transactions.php">Book a Consignment</a>
          <a class="panel-link<?php echo ew_menu_active('transaction_list.php', $current_page); ?>" href="transaction_list.php">List of Consignments</a>
         
        <?php endif; ?>
        <a class="panel-link<?php echo ew_menu_active('track_consignment.php', $current_page); ?>" href="track_consignment.php">Track Consignment</a>
        <?php if ($_SESSION['role'] == 'AD' || $_SESSION['role'] == 'USER'): ?>
          <a class="panel-link<?php echo ew_menu_active_any(array('status_sheet_list.php', 'status_sheet.php', 'edit_status_sheet.php'), $current_page); ?>" href="status_sheet_list.php">Consignment Status Sheet</a>
          <a class="panel-link<?php echo ew_menu_active_any(array('trip_summary_list.php', 'trip_summary.php', 'trip_summary_print.php'), $current_page); ?>" href="trip_summary_list.php">Trip Summary Sheet</a>
          <a class="panel-link<?php echo ew_menu_active('transaction_status.php', $current_page); ?>" href="transaction_status.php">Transaction Status</a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] == 'AD'): ?>
          <a class="panel-link<?php echo ew_menu_active('transactions_manual.php', $current_page); ?>" href="transactions_manual.php">Book Manual Consignment</a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] == 'DR'): ?>
          <a class="panel-link<?php echo ew_menu_active('pod_master.php', $current_page); ?>" href="pod_master.php">Upload Proof of Delivery</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($_SESSION['role'] == 'AD' || $_SESSION['role'] == 'USER'): ?>
    <div class="panel-module<?php echo ew_module_active('invoice', $active_module); ?>" data-module="invoice">
      <?php ew_panel_head('Invoice'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active_any(array('invoice_list.php', 'create_invoice.php'), $current_page); ?>" href="invoice_list.php">Tax Invoice</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('credit_note_list.php', 'create_credit_note.php'), $current_page); ?>" href="credit_note_list.php">Credit Note</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($_SESSION['role'] != 'DR'): ?>
    <div class="panel-module<?php echo ew_module_active('mis', $active_module); ?>" data-module="mis">
      <?php ew_panel_head('MIS Report'); ?>
      <div class="panel-scroll">
        <?php if ($_SESSION['role'] == 'CL'): ?>
          <a class="panel-link<?php echo ew_menu_active('consignment_report.php', $current_page); ?>" href="consignment_report.php">My Booking Report</a>
          <a class="panel-link<?php echo ew_menu_active('client_arrival_report.php', $current_page); ?>" href="client_arrival_report.php">My Arrival Report</a>
        <?php else: ?>
          <a class="panel-link<?php echo ew_menu_active('consignment_report.php', $current_page); ?>" href="consignment_report.php">Booking Status Report</a>
          <a class="panel-link<?php echo ew_menu_active('cargo_booking_report.php', $current_page); ?>" href="cargo_booking_report.php">Cargo Booking Report</a>
          <a class="panel-link<?php echo ew_menu_active('gst_tax_report.php', $current_page); ?>" href="gst_tax_report.php">GST Tax Report</a>
          <a class="panel-link<?php echo ew_menu_active('client_payment_transactions.php', $current_page); ?>" href="client_payment_transactions.php">Payment History</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == 'AD' || $_SESSION['role'] == 'USER'): ?>
    <div class="panel-module<?php echo ew_module_active('mapping', $active_module); ?>" data-module="mapping">
      <?php ew_panel_head('Customer Mapping'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active_any(array('customer_mapping.php', 'customer_mapping_form.php'), $current_page); ?>" href="customer_mapping.php">Customer Mapping</a>
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

  function closePanelSearch(moduleEl) {
    if (!moduleEl) return;
    const head = moduleEl.querySelector('.panel-head');
    const input = moduleEl.querySelector('.panel-search');
    if (head) head.classList.remove('is-search-open');
    if (input) {
      input.value = '';
      filterPanelScreens(input);
    }
  }

  function openPanelSearch(moduleEl) {
    if (!moduleEl) return;
    const head = moduleEl.querySelector('.panel-head');
    const input = moduleEl.querySelector('.panel-search');
    if (head) head.classList.add('is-search-open');
    if (input) {
      setTimeout(function () { input.focus(); }, 0);
    }
  }

  function resetPanelSearch(moduleEl) {
    closePanelSearch(moduleEl);
  }

  function escapeHtml(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function highlightSearchLabel(text, query) {
    if (!query) return escapeHtml(text);

    const lowerText = text.toLowerCase();
    const lowerQuery = query.toLowerCase();
    let idx = 0;
    let html = '';

    while (idx < text.length) {
      const matchAt = lowerText.indexOf(lowerQuery, idx);
      if (matchAt === -1) {
        html += escapeHtml(text.slice(idx));
        break;
      }
      html += escapeHtml(text.slice(idx, matchAt));
      html += '<mark class="panel-search-hit">' + escapeHtml(text.slice(matchAt, matchAt + query.length)) + '</mark>';
      idx = matchAt + query.length;
    }

    return html;
  }

  function initPanelLinkLabels() {
    document.querySelectorAll('.panel-link').forEach(function (link) {
      if (link.dataset.searchLabel) return;

      const badge = link.querySelector('.panel-badge');
      if (badge) link.dataset.searchBadge = badge.outerHTML;

      const clone = link.cloneNode(true);
      const cloneBadge = clone.querySelector('.panel-badge');
      if (cloneBadge) cloneBadge.remove();
      link.dataset.searchLabel = clone.textContent.replace(/\s+/g, ' ').trim();
    });
  }

  function renderPanelLinkLabel(link, query) {
    const label = link.dataset.searchLabel;
    if (!label) return;

    const badge = link.dataset.searchBadge || '';
    const labelHtml = query ? highlightSearchLabel(label, query) : escapeHtml(label);
    link.innerHTML = '<span class="panel-link-text">' + labelHtml + '</span>' + badge;
  }

  function filterPanelScreens(input) {
    const module = input.closest('.panel-module');
    if (!module) return;

    const query = input.value.trim();
    const queryLower = query.toLowerCase();
    const links = module.querySelectorAll('.panel-link');
    let visible = 0;

    links.forEach(function (link) {
      const label = (link.dataset.searchLabel || link.textContent.replace(/\s+/g, ' ').trim()).toLowerCase();
      const match = !queryLower || label.indexOf(queryLower) !== -1;
      link.classList.toggle('is-filtered-out', !match);
      renderPanelLinkLabel(link, match ? query : '');
      if (match) visible += 1;
    });

    let noResults = module.querySelector('.panel-no-results');
    if (!noResults) {
      noResults = document.createElement('div');
      noResults.className = 'panel-no-results';
      noResults.textContent = 'No screens match your search.';
      const scroll = module.querySelector('.panel-scroll');
      if (scroll) scroll.insertAdjacentElement('afterend', noResults);
    }
    noResults.classList.toggle('visible', !!query && visible === 0);
  }

  function selectModule(moduleId) {
    document.querySelectorAll('.rail-item[data-module]').forEach(function (el) {
      el.classList.toggle('active', el.getAttribute('data-module') === moduleId);
    });
    document.querySelectorAll('.panel-module').forEach(function (el) {
      const isActive = el.getAttribute('data-module') === moduleId;
      el.classList.toggle('active', isActive);
      if (!isActive) resetPanelSearch(el);
    });
    try { localStorage.setItem('sidebar_module', moduleId); } catch (e) {}
  }

  function syncSidebarLayout() {
    const sb = document.getElementById('sidebar');
    const mc = document.querySelector('.main-content');
    const collapsed = !!(sb && sb.classList.contains('collapsed') && !isMobileNav());
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    if (mc) mc.classList.toggle('collapsed', collapsed);
  }

  function closeMobileSidebar() {
    const sb = document.getElementById('sidebar');
    if (sb) sb.classList.remove('mobile-open');
    document.body.classList.remove('sidebar-mobile-open');
    syncSidebarLayout();
  }

  function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const tb = document.getElementById('topBar');

    if (isMobileNav()) {
      const opening = !sb.classList.contains('mobile-open');
      sb.classList.toggle('mobile-open', opening);
      document.body.classList.toggle('sidebar-mobile-open', opening);
      syncSidebarLayout();
      return;
    }

    sb.classList.toggle('collapsed');
    if (tb) tb.classList.toggle('collapsed');
    syncSidebarLayout();
    localStorage.setItem('sidebar_collapsed', sb.classList.contains('collapsed') ? '1' : '0');
    window.dispatchEvent(new CustomEvent('sidebarToggled', { detail: { collapsed: sb.classList.contains('collapsed') } }));
  }

  document.addEventListener('DOMContentLoaded', function () {
    initPanelLinkLabels();

    document.querySelectorAll('.rail-item[data-module]').forEach(function (btn) {
      if (btn.tagName === 'BUTTON') {
        btn.addEventListener('click', function () {
          const moduleId = btn.getAttribute('data-module');
          selectModule(moduleId);
          const sb = document.getElementById('sidebar');
          if (sb && sb.classList.contains('collapsed') && !isMobileNav()) {
            toggleSidebar();
          }
        });
      }
    });

    document.querySelectorAll('.panel-link[href]').forEach(function (link) {
      link.addEventListener('click', function () {
        if (isMobileNav()) closeMobileSidebar();
      });
    });

    document.querySelectorAll('.panel-search-open').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openPanelSearch(btn.closest('.panel-module'));
      });
    });

    document.querySelectorAll('.panel-search-close').forEach(function (btn) {
      btn.addEventListener('click', function () {
        closePanelSearch(btn.closest('.panel-module'));
      });
    });

    document.querySelectorAll('.panel-search').forEach(function (input) {
      input.addEventListener('input', function () {
        filterPanelScreens(input);
      });
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          closePanelSearch(input.closest('.panel-module'));
        }
      });
    });
  });

  (function () {
    if (localStorage.getItem('sidebar_collapsed') === '1') {
      const sb = document.getElementById('sidebar');
      const tb = document.getElementById('topBar');
      if (sb) sb.classList.add('collapsed');
      if (tb) tb.classList.add('collapsed');
    }
    syncSidebarLayout();
    closeMobileSidebar();
    window.addEventListener('resize', function () {
      if (!isMobileNav()) closeMobileSidebar();
    });
  })();
</script>
