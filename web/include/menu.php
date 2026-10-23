<?php
require_once ('include/connect.php');
require_once ('include/function.php');
$read_status = mysqli_query($conn, 'SELECT read_status FROM `user_registrations` WHERE read_status = 0');
$count_read = mysqli_num_rows($read_status);

$current_page = basename($_SERVER['PHP_SELF']);

$page_module_map = array(
  'dashboard.php' => 'dashboard',
  'dashboard_booking.php' => 'consignment',
  'company.php' => 'setup', 'company_bank.php' => 'setup', 'company_bank_form.php' => 'setup',
  'branch.php' => 'setup',
  'state.php' => 'setup', 'city.php' => 'setup', 'country.php' => 'setup', 'hub.php' => 'setup',
  'package_type.php' => 'setup',
  'mode_of_transportation.php' => 'setup', 'consignment_mode.php' => 'setup',
  'vehicle.php' => 'setup', 'vehicle_list.php' => 'setup',
  'vehicle_type_master.php' => 'setup', 'vehicle_type.php' => 'setup',
  'cfs_master.php' => 'setup', 'cfs.php' => 'setup',
  'train.php' => 'setup', 'flight.php' => 'setup',
  'quotation_list.php' => 'marketing', 'quotation.php' => 'marketing', 'quotation_pdf.php' => 'marketing',
  'quotation_all_modes_list.php' => 'marketing', 'quotation_all_modes.php' => 'marketing',
  'quotation_consignor_multi_dest_list.php' => 'marketing', 'quotation_consignor_multi_dest.php' => 'marketing',
  'client.php' => 'party', 'client_list.php' => 'party',
  'client_branch.php' => 'party', 'client_branch_list.php' => 'party',
  'vendor.php' => 'party', 'vendor_list.php' => 'party',
  'gst_tax_master.php' => 'invoice', 'gst_tax_form.php' => 'invoice',
  'consigner_payment_form.php' => 'portal', 'consigner_payment_list.php' => 'portal',
  'rate_calculator_form.php' => 'portal', 'rate_calc_list.php' => 'portal',
  'expected_delivery_form.php' => 'portal', 'expected_delivery_list.php' => 'portal',
  'expense_type_master.php' => 'setup', 'expense_type_form.php' => 'setup',
  'expense_group_master.php' => 'setup',
  'expense_gcn.php' => 'expense', 'expense_gcn_list.php' => 'expense',
  'expense_general.php' => 'expense', 'expense_general_list.php' => 'expense',
  'qrcode_master.php' => 'tools', 'bulkmail.php' => 'tools', 'users.php' => 'tools', 'user_list.php' => 'tools',
  'database_backup.php' => 'tools',
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
  'create_proforma_invoice.php' => 'invoice', 'proforma_invoice_list.php' => 'invoice',
  'credit_note_list.php' => 'invoice', 'create_credit_note.php' => 'invoice',
  'receipt_list.php' => 'invoice', 'create_receipt.php' => 'invoice',
  'consignment_report.php' => 'mis', 'client_arrival_report.php' => 'mis',
  'cargo_booking_report.php' => 'mis', 'gst_tax_report.php' => 'mis',
  'invoice_register.php' => 'mis',
  'pending_payments.php' => 'invoice',
  'client_payment_transactions.php' => 'mis',
  'customer_mapping.php' => 'party',
  'customer_mapping_form.php' => 'party',
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

function ew_menu_active_scope($page, $scope, $current_page) {
  if ($page !== $current_page) {
    return '';
  }
  $cur = function_exists('ew_geo_scope_from_request') ? ew_geo_scope_from_request() : 'domestic';
  return ($cur === $scope) ? ' active' : '';
}

function ew_module_active($module, $active_module) {
  return ($module === $active_module) ? ' active' : '';
}

function ew_rail_icon($name) {
  $inner = array(
    'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
    'bookings' => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
    'invoicing' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
    'marketing' => '',
    'mis' => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
    'general' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
    'customers' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'expense' => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
    'tools' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
    'portal' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>',
  );
  if ($name === 'marketing') {
    return '<span class="rail-icon" aria-hidden="true"><svg class="rail-icon-fill" viewBox="0 0 576 512"><path fill="currentColor" d="M80 96A48 48 0 1 0 80 0a48 48 0 1 0 0 96zM64 128c-35.3 0-64 28.7-64 64V320c0 17.7 14.3 32 32 32c9.8 0 18.5-4.4 24.4-11.2L80.4 485.3c2.9 17.4 19.4 29.2 36.8 26.3s29.2-19.4 26.3-36.8L123.1 352h15.7l30 134.9c3.8 17.3 20.9 28.1 38.2 24.3s28.1-20.9 24.3-38.2l-57.3-258 116.3 53.8c.5 .3 1.1 .5 1.6 .7c8.6 3.6 18 3.1 25.9-.7c3.4-1.6 6.6-3.9 9.3-6.7c3.1-3.2 5.5-7 7.1-11.4c.1-.3 .2-.7 .3-1l2.5-7.5c5.7-17.1 18.3-30.9 34.7-38.2l8-3.5c1-.4 1.9-.8 2.9-1.2l-16.9 63.5c-5.6 21.1-.1 43.6 14.7 59.7l70.7 77.1 22 88.1c4.3 17.1 21.7 27.6 38.8 23.3s27.6-21.7 23.3-38.8l-23-92.1c-1.9-7.8-5.8-14.9-11.2-20.8l-49.5-54 19.3-65.5 9.6 23c4.4 10.6 12.5 19.3 22.8 24.5l26.7 13.3c15.8 7.9 35 1.5 42.9-14.3s1.5-35-14.3-42.9L537 232.7l-15.3-36.8C504.5 154.8 464.3 128 419.7 128c-22.8 0-45.3 4.8-66.1 14l-8 3.5c-24.4 10.9-44.6 29-58.1 51.6L157.3 136.9C144.7 131 130.9 128 117 128H64zM464 96a48 48 0 1 0 0-96 48 48 0 1 0 0 96zM349.7 335.6l-25 62.4-59.4 59.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L372.3 441c4.6-4.6 8.2-10.1 10.6-16.1l14.5-36.2-40.7-44.4c-2.5-2.7-4.8-5.6-7-8.6z"/></svg></span>';
  }
  $paths = isset($inner[$name]) ? $inner[$name] : $inner['dashboard'];
  return '<span class="rail-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg></span>';
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
    padding: 8px 0 16px;
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
    transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    position: relative;
  }
  .rail-item:hover:not(.active) {
    background: #fff;
    color: var(--ew-navy);
    text-decoration: none;
    box-shadow: 0 6px 16px rgba(6, 65, 111, 0.16);
    transform: translateY(-2px);
  }
  .rail-item.active,
  .rail-item.active:hover {
    background: var(--ew-navy);
    color: #fff;
    box-shadow: 0 8px 18px rgba(6, 65, 111, 0.28);
    transform: translateY(-2px);
  }
  .rail-item.active .rail-label {
    font-weight: 700;
  }

  .rail-item[data-tooltip]::after,
  .rail-item[data-tooltip]:hover::after {
    content: none;
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
    transition: filter 0.2s ease, opacity 0.2s ease, transform 0.2s ease;
  }
  .rail-icon .fa,
  .rail-icon svg {
    font-size: 17px;
    line-height: 1;
    color: #1A2332;
    opacity: 0.92;
    transition: color 0.15s ease, opacity 0.15s ease;
  }
  .rail-icon svg {
    width: 22px;
    height: 22px;
    display: block;
  }
  .rail-item:hover:not(.active) .rail-icon img {
    filter: brightness(0);
    opacity: 1;
    transform: scale(1.12);
  }
  .rail-item:hover:not(.active) .rail-icon .fa,
  .rail-item:hover:not(.active) .rail-icon svg {
    color: var(--ew-navy);
    opacity: 1;
    transform: scale(1.12);
  }
  .rail-item:hover .rail-label {
    font-weight: 700;
  }
  .rail-item.active .rail-icon img,
  .rail-item.active:hover .rail-icon img {
    filter: brightness(0) invert(1);
    opacity: 1;
  }
  .rail-item.active .rail-icon .fa,
  .rail-item.active:hover .rail-icon .fa,
  .rail-item.active .rail-icon svg,
  .rail-item.active:hover .rail-icon svg {
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

  .panel-accordion {
    margin: 4px 10px 8px;
  }
  .panel-accordion.is-filtered-out {
    display: none !important;
  }
  .panel-accordion-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    background: #F1F5F9;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    text-align: left;
    transition: background 0.15s ease, border-color 0.15s ease;
  }
  .panel-accordion-toggle:hover {
    background: #E8EEF4;
    border-color: #CBD5E1;
  }
  .panel-accordion.is-open .panel-accordion-toggle {
    background: #E8EEF4;
    border-color: #CBD5E1;
  }
  .panel-accordion-chevron {
    font-size: 11px;
    color: #64748B;
    transition: transform 0.2s ease;
    flex-shrink: 0;
  }
  .panel-accordion.is-open .panel-accordion-chevron {
    transform: rotate(180deg);
  }
  .panel-accordion-body {
    display: none;
    padding: 6px 0 2px 4px;
  }
  .panel-accordion.is-open .panel-accordion-body {
    display: block;
  }
  .panel-link--nested {
    padding: 8px 12px 8px 22px;
    margin: 2px 0;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    position: relative;
  }
  .panel-link--nested::before {
    content: '';
    display: block !important;
    position: absolute;
    left: 10px;
    top: 50%;
    width: 4px;
    height: 4px;
    margin-top: -2px;
    border-radius: 50%;
    background: #94A3B8;
  }
  .panel-link--nested.active::before {
    background: #DD111E;
  }
  .panel-link--nested.active {
    box-shadow: none;
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
      <?php echo ew_rail_icon('dashboard'); ?>
      <span class="rail-label">Dashboard</span>
    </a>
    <?php if ($_SESSION['role'] == 'AD' || $_SESSION['role'] == 'USER'): ?>
    <button type="button" class="rail-item<?php echo ew_module_active('marketing', $active_module); ?>" data-module="marketing" data-tooltip="Marketing" title="Marketing">
      <?php echo ew_rail_icon('marketing'); ?>
      <span class="rail-label">Marketing</span>
    </button>
    <?php endif; ?>
    <button type="button" class="rail-item<?php echo ew_module_active('consignment', $active_module); ?>" data-module="consignment" data-tooltip="Consignment" title="Consignment">
      <?php echo ew_rail_icon('bookings'); ?>
      <span class="rail-label">Bookings</span>
    </button>
    <?php if ($_SESSION['role'] == 'AD'): ?>
    <button type="button" class="rail-item<?php echo ew_module_active('expense', $active_module); ?>" data-module="expense" data-tooltip="Expense" title="Expense">
      <?php echo ew_rail_icon('expense'); ?>
      <span class="rail-label">Expense</span>
    </button>
    <?php endif; ?>
    <?php if ($_SESSION['role'] != 'DR'): ?>
    <button type="button" class="rail-item<?php echo ew_module_active('mis', $active_module); ?>" data-module="mis" data-tooltip="MIS Report" title="MIS Report">
      <?php echo ew_rail_icon('mis'); ?>
      <span class="rail-label">MIS</span>
    </button>
    <?php endif; ?>
    <?php if ($_SESSION['role'] == 'AD' || $_SESSION['role'] == 'USER'): ?>
    <button type="button" class="rail-item<?php echo ew_module_active('invoice', $active_module); ?>" data-module="invoice" data-tooltip="Invoice" title="Invoice">
      <?php echo ew_rail_icon('invoicing'); ?>
      <span class="rail-label">Invoicing</span>
    </button>
    <button type="button" class="rail-item<?php echo ew_module_active('party', $active_module); ?>" data-module="party" data-tooltip="Customer Masters" title="Customer Masters">
      <?php echo ew_rail_icon('customers'); ?>
      <span class="rail-label">Customers</span>
    </button>
    <?php endif; ?>
    <?php if ($_SESSION['role'] == 'AD'): ?>
    <button type="button" class="rail-item<?php echo ew_module_active('portal', $active_module); ?>" data-module="portal" data-tooltip="User Portal" title="User Portal">
      <?php echo ew_rail_icon('portal'); ?>
      <span class="rail-label">Portal</span>
      <?php if ($count_read > 0): ?><span class="rail-badge"><?php echo $count_read; ?></span><?php endif; ?>
    </button>
    <button type="button" class="rail-item<?php echo ew_module_active('tools', $active_module); ?>" data-module="tools" data-tooltip="Tools &amp; Users" title="Tools &amp; Users">
      <?php echo ew_rail_icon('tools'); ?>
      <span class="rail-label">Tools</span>
    </button>
    <button type="button" class="rail-item<?php echo ew_module_active('setup', $active_module); ?>" data-module="setup" data-tooltip="General" title="General">
      <?php echo ew_rail_icon('general'); ?>
      <span class="rail-label">General</span>
    </button>
    <?php endif; ?>

  </nav>

  <div class="sub-panel" id="subPanel">
    <div class="panel-module<?php echo ew_module_active('dashboard', $active_module); ?>" data-module="dashboard">
      <?php ew_panel_head('Dashboard'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active('dashboard.php', $current_page); ?>" href="dashboard.php">Operations Dashboard</a>
        <a class="panel-link<?php echo ew_menu_active('dashboard_booking.php', $current_page); ?>" href="dashboard_booking.php">Consignment Bookings</a>
      </div>
    </div>

    <?php if ($_SESSION['role'] == 'AD'): ?>
    <div class="panel-module<?php echo ew_module_active('setup', $active_module); ?>" data-module="setup">
      <?php ew_panel_head('General'); ?>
      <div class="panel-scroll">
        <?php $ew360_details_open = in_array($current_page, array('company.php', 'company_bank.php', 'company_bank_form.php', 'hub.php'), true); ?>
        <div class="panel-accordion<?php echo $ew360_details_open ? ' is-open' : ''; ?>" data-accordion-id="general-ew360-details" data-accordion-label="EW360 Details">
          <button type="button" class="panel-accordion-toggle" aria-expanded="<?php echo $ew360_details_open ? 'true' : 'false'; ?>">
            <span>EW360 Details</span>
            <i class="fa fa-chevron-down panel-accordion-chevron" aria-hidden="true"></i>
          </button>
          <div class="panel-accordion-body">
            <a class="panel-link panel-link--nested<?php echo ew_menu_active('company.php', $current_page); ?>" href="company.php">Company Details</a>
            <a class="panel-link panel-link--nested<?php echo ew_menu_active_any(array('company_bank.php', 'company_bank_form.php'), $current_page); ?>" href="company_bank.php">Bank Details</a>
            <a class="panel-link panel-link--nested<?php echo ew_menu_active('hub.php', $current_page); ?>" href="hub.php">Hub Details</a>
          </div>
        </div>
        <?php $area_master_open = in_array($current_page, array('state.php', 'city.php', 'branch.php', 'cfs_master.php', 'cfs.php'), true); ?>
        <div class="panel-accordion<?php echo $area_master_open ? ' is-open' : ''; ?>" data-accordion-id="general-area-master" data-accordion-label="Area Master">
          <button type="button" class="panel-accordion-toggle" aria-expanded="<?php echo $area_master_open ? 'true' : 'false'; ?>">
            <span>Area Master</span>
            <i class="fa fa-chevron-down panel-accordion-chevron" aria-hidden="true"></i>
          </button>
          <div class="panel-accordion-body">
            <a class="panel-link panel-link--nested<?php echo ew_menu_active_scope('state.php', 'domestic', $current_page); ?>" href="state.php?scope=domestic">State Master</a>
            <a class="panel-link panel-link--nested<?php echo ew_menu_active_scope('city.php', 'domestic', $current_page); ?>" href="city.php?scope=domestic">City Master</a>
            <a class="panel-link panel-link--nested<?php echo ew_menu_active_scope('branch.php', 'domestic', $current_page); ?>" href="branch.php?scope=domestic">Branch Master</a>
            <a class="panel-link panel-link--nested<?php echo ew_menu_active_any(array('cfs_master.php', 'cfs.php'), $current_page); ?>" href="cfs_master.php">CFS Master</a>
          </div>
        </div>
        <a class="panel-link<?php echo ew_menu_active('package_type.php', $current_page); ?>" href="package_type.php">Package Type Master</a>
        <a class="panel-link<?php echo ew_menu_active('mode_of_transportation.php', $current_page); ?>" href="mode_of_transportation.php">Mode of Transport Master</a>
        <a class="panel-link<?php echo ew_menu_active('consignment_mode.php', $current_page); ?>" href="consignment_mode.php">Consignment Mode Master</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('vehicle_list.php', 'vehicle.php'), $current_page); ?>" href="vehicle_list.php">EW360 Vehicles</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('vehicle_type_master.php', 'vehicle_type.php'), $current_page); ?>" href="vehicle_type_master.php">Vehicle Type Master</a>
        <a class="panel-link<?php echo ew_menu_active('train.php', $current_page); ?>" href="train.php">Train Master</a>
        <a class="panel-link<?php echo ew_menu_active('flight.php', $current_page); ?>" href="flight.php">Airlines Master</a>
        <?php $expense_group_masters_open = in_array($current_page, array('expense_group_master.php', 'expense_type_master.php', 'expense_type_form.php'), true); ?>
        <div class="panel-accordion<?php echo $expense_group_masters_open ? ' is-open' : ''; ?>" data-accordion-id="general-expense-group-masters" data-accordion-label="Expense Group Masters">
          <button type="button" class="panel-accordion-toggle" aria-expanded="<?php echo $expense_group_masters_open ? 'true' : 'false'; ?>">
            <span>Expense Group Masters</span>
            <i class="fa fa-chevron-down panel-accordion-chevron" aria-hidden="true"></i>
          </button>
          <div class="panel-accordion-body">
            <a class="panel-link panel-link--nested<?php echo ew_menu_active('expense_group_master.php', $current_page); ?>" href="expense_group_master.php">Expense Group GCN Master</a>
            <a class="panel-link panel-link--nested<?php echo ew_menu_active_any(array('expense_type_master.php', 'expense_type_form.php'), $current_page); ?>" href="expense_type_master.php">Expense Type GCN Master</a>
          </div>
        </div>
      </div>
    </div>

    <div class="panel-module<?php echo ew_module_active('expense', $active_module); ?>" data-module="expense">
      <?php ew_panel_head('Expense'); ?>
      <div class="panel-scroll">
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
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'AD') { ?>
        <a class="panel-link<?php echo ew_menu_active('database_backup.php', $current_page); ?>" href="database_backup.php">Database Backup</a>
        <?php } ?>
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
        <a class="panel-link<?php echo ew_menu_active_any(array('rate_calc_list.php', 'rate_calculator_form.php'), $current_page); ?>" href="rate_calc_list.php">Rate List</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('expected_delivery_list.php', 'expected_delivery_form.php'), $current_page); ?>" href="expected_delivery_list.php">Expected Delivery</a>
        <a class="panel-link<?php echo ew_menu_active('request_for_new_pickup.php', $current_page); ?>" href="request_for_new_pickup.php">Request For Pickup</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == 'AD' || $_SESSION['role'] == 'USER'): ?>
    <div class="panel-module<?php echo ew_module_active('party', $active_module); ?>" data-module="party">
      <?php ew_panel_head('Customer Masters'); ?>
      <div class="panel-scroll">
        <?php if ($_SESSION['role'] == 'AD'): ?>
        <a class="panel-link<?php echo ew_menu_active_any(array('client_list.php', 'client.php'), $current_page); ?>" href="client_list.php">Customer</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('client_branch_list.php', 'client_branch.php'), $current_page); ?>" href="client_branch_list.php">Customer Branch</a>
        
        <a class="panel-link<?php echo ew_menu_active_any(array('customer_mapping.php', 'customer_mapping_form.php'), $current_page); ?>" href="customer_mapping.php">Customer Mapping</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('vendor_list.php', 'vendor.php'), $current_page); ?>" href="vendor_list.php">Vendor</a>
        <?php endif; ?>
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
          <a class="panel-link<?php echo ew_menu_active('transaction_status.php', $current_page); ?>" href="transaction_status.php">GCN Transit Update</a>
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
    <div class="panel-module<?php echo ew_module_active('marketing', $active_module); ?>" data-module="marketing">
      <?php ew_panel_head('Marketing'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active_any(array('quotation_list.php', 'quotation.php'), $current_page); ?>" href="quotation_list.php">Single Quotation</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('quotation_all_modes_list.php', 'quotation_all_modes.php'), $current_page); ?>" href="quotation_all_modes_list.php">Multi Mode Quotation</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('quotation_consignor_multi_dest_list.php', 'quotation_consignor_multi_dest.php'), $current_page); ?>" href="quotation_consignor_multi_dest_list.php">Multi Destination Quotation</a>
      </div>
    </div>
    <div class="panel-module<?php echo ew_module_active('invoice', $active_module); ?>" data-module="invoice">
      <?php ew_panel_head('Invoicing'); ?>
      <div class="panel-scroll">
        <a class="panel-link<?php echo ew_menu_active_any(array('proforma_invoice_list.php', 'create_proforma_invoice.php'), $current_page); ?>" href="proforma_invoice_list.php">Proforma Invoice</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('invoice_list.php', 'create_invoice.php'), $current_page); ?>" href="invoice_list.php">Tax Invoice</a>
        <a class="panel-link<?php echo ew_menu_active('pending_payments.php', $current_page); ?>" href="pending_payments.php">Bill Wise</a>
        <span class="panel-link" title="Screen coming soon">Ledger Statement</span>
        <a class="panel-link<?php echo ew_menu_active_any(array('credit_note_list.php', 'create_credit_note.php'), $current_page); ?>" href="credit_note_list.php">Credit Note</a>
        <a class="panel-link<?php echo ew_menu_active_any(array('receipt_list.php', 'create_receipt.php'), $current_page); ?>" href="receipt_list.php">Payment Receipt</a>
        <span class="panel-link" title="Screen coming soon">Payment History</span>
        <a class="panel-link<?php echo ew_menu_active_any(array('gst_tax_master.php', 'gst_tax_form.php'), $current_page); ?>" href="gst_tax_master.php">GST Tax Master</a>
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
          <a class="panel-link<?php echo ew_menu_active('invoice_register.php', $current_page); ?>" href="invoice_register.php">Invoice Register</a>
          <a class="panel-link<?php echo ew_menu_active('client_payment_transactions.php', $current_page); ?>" href="client_payment_transactions.php">Payment History</a>
        <?php endif; ?>
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

  function setPanelAccordionOpen(accordion, open, persist) {
    if (!accordion) return;
    accordion.classList.toggle('is-open', open);
    const btn = accordion.querySelector('.panel-accordion-toggle');
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (persist) {
      const id = accordion.getAttribute('data-accordion-id');
      if (id) {
        try { localStorage.setItem('ew_accordion_' + id, open ? '1' : '0'); } catch (e) {}
      }
    }
  }

  function initPanelAccordions() {
    document.querySelectorAll('.panel-accordion').forEach(function (accordion) {
      const id = accordion.getAttribute('data-accordion-id');
      const hasActiveChild = !!accordion.querySelector('.panel-link.active');
      if (hasActiveChild) {
        setPanelAccordionOpen(accordion, true, false);
        return;
      }
      if (accordion.classList.contains('is-open')) return;
      if (!id) return;
      try {
        if (localStorage.getItem('ew_accordion_' + id) === '1') {
          setPanelAccordionOpen(accordion, true, false);
        }
      } catch (e) {}
    });

    document.querySelectorAll('.panel-accordion-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const accordion = btn.closest('.panel-accordion');
        if (!accordion) return;
        setPanelAccordionOpen(accordion, !accordion.classList.contains('is-open'), true);
      });
    });
  }

  function filterPanelScreens(input) {
    const module = input.closest('.panel-module');
    if (!module) return;

    const query = input.value.trim();
    const queryLower = query.toLowerCase();
    const links = module.querySelectorAll('.panel-scroll > .panel-link, .panel-accordion .panel-link');
    let visible = 0;

    links.forEach(function (link) {
      const label = (link.dataset.searchLabel || link.textContent.replace(/\s+/g, ' ').trim()).toLowerCase();
      const match = !queryLower || label.indexOf(queryLower) !== -1;
      link.classList.toggle('is-filtered-out', !match);
      renderPanelLinkLabel(link, match ? query : '');
      if (match) visible += 1;
    });

    module.querySelectorAll('.panel-accordion').forEach(function (accordion) {
      const groupLabel = (accordion.getAttribute('data-accordion-label') || '').toLowerCase();
      const childLinks = accordion.querySelectorAll('.panel-link');
      let childVisible = 0;
      childLinks.forEach(function (link) {
        if (!link.classList.contains('is-filtered-out')) childVisible += 1;
      });
      const groupMatch = queryLower && groupLabel.indexOf(queryLower) !== -1;
      if (queryLower) {
        accordion.classList.toggle('is-filtered-out', childVisible === 0 && !groupMatch);
        if (childVisible > 0 || groupMatch) {
          setPanelAccordionOpen(accordion, true, false);
        }
      } else {
        accordion.classList.remove('is-filtered-out');
      }
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

  function syncHamburgerState() {
    const sb = document.getElementById('sidebar');
    if (!sb) return;
    let open;
    if (isMobileNav()) {
      open = sb.classList.contains('mobile-open');
    } else {
      open = !sb.classList.contains('collapsed');
    }
    const hb = document.getElementById('hamburgerBtn');
    if (!hb) return;
    hb.classList.remove('is-open');
    hb.setAttribute('aria-expanded', open ? 'true' : 'false');
    hb.title = 'Toggle menu';
  }

  function syncSidebarLayout() {
    const sb = document.getElementById('sidebar');
    const mc = document.querySelector('.main-content');
    const collapsed = !!(sb && sb.classList.contains('collapsed') && !isMobileNav());
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    if (mc) mc.classList.toggle('collapsed', collapsed);
    syncHamburgerState();
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
    syncHamburgerState();
    localStorage.setItem('sidebar_collapsed', sb.classList.contains('collapsed') ? '1' : '0');
    window.dispatchEvent(new CustomEvent('sidebarToggled', { detail: { collapsed: sb.classList.contains('collapsed') } }));
  }

  document.addEventListener('DOMContentLoaded', function () {
    initPanelLinkLabels();
    initPanelAccordions();

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
