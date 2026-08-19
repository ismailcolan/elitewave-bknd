    <?php
    require_once ('include/connect.php');
    require_once ('include/function.php');

    // ── Stats queries ─────────────────────────────────────────────────────────────
    $booked = 0;
    $query2 = 'SELECT * FROM transaction_tbls';
    $result2 = mysqli_query($conn, $query2) or die(mysqli_error($conn));
    while ($row2 = mysqli_fetch_assoc($result2)) {
        $q = 'SELECT transaction_id FROM transaction_' . $row2['table_name'];
        $r = mysqli_query($conn, $q);
        $booked += mysqli_num_rows($r);
    }

    $in_transit = 0;
    $delivered = 0;
    $result2 = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
    while ($row2 = mysqli_fetch_assoc($result2)) {
        $tbl = 'transaction_' . $row2['table_name'];
        $r1 = mysqli_query($conn, "SELECT COUNT(*) as c FROM $tbl WHERE status LIKE '%Transit%' OR (status!='8' AND status!='0' AND status!='1')");
        $in_transit += mysqli_fetch_assoc($r1)['c'] ?? 0;
        $r2 = mysqli_query($conn, "SELECT COUNT(*) as c FROM $tbl WHERE status='8'");
        $delivered += mysqli_fetch_assoc($r2)['c'] ?? 0;
    }

    $today_booked = 0;
    $result2 = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
    while ($row2 = mysqli_fetch_assoc($result2)) {
        $tbl = 'transaction_' . $row2['table_name'];
        $today_date = date('d-m-Y');
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM $tbl WHERE grn_date='$today_date'");
        $today_booked += mysqli_fetch_assoc($r)['c'] ?? 0;
    }

    $active_vehicles = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as c FROM vehicle WHERE status='0'"))['c'] ?? 0;

    // ── Chart data ────────────────────────────────────────────────────────────────
    $Y = date('Y');
    $Month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $dataPoints1 = [];
    $dataPoints2 = [];
    for ($i = 1; $i <= 12; $i++) {
        $Date = sprintf('%02d', $i) . '-' . $Y;
        $full_date = '01-' . $Date;
        $tbl2 = get_trans_table_name($conn, $full_date);
        $r1 = mysqli_query($conn, "SELECT COUNT(grn_no) as c FROM $tbl2[0] WHERE grn_date LIKE '%$Date' AND status='8'");
        $r2 = mysqli_query($conn, "SELECT COUNT(grn_no) as c FROM $tbl2[0] WHERE grn_date LIKE '%$Date' AND status!='8'");
        $dataPoints1[] = ['label' => $Month[$i - 1] . ' ' . $Y, 'y' => (int) (mysqli_fetch_assoc($r1)['c'] ?? 0)];
        $dataPoints2[] = ['label' => $Month[$i - 1] . ' ' . $Y, 'y' => (int) (mysqli_fetch_assoc($r2)['c'] ?? 0)];
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
    <?php include ('include/title.php'); ?>
    <?php include ('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font/flaticon.css" />
    <style>
    :root{
    --sidebar-width:260px;
    --sidebar-collapsed:64px;
    --top-bar-h:64px;
    --ew-navy:#0A1E3D;
    --ew-navy-light:#132D54;
    --ew-primary:#0A1E3D;
    --ew-primary-dark:#06122A;
    --ew-primary-light:#E8EDF4;
    --ew-accent:#DD111E;
    --ew-accent-dark:#B80E19;
    --ew-teal:#0891B2;
    --ew-green:#16A34A;
    --ew-orange:#EA580C;
    --ew-text:#1A2332;
    --ew-text-muted:#6B7A8D;
    --ew-border:#D8DDE5;
    --ew-page-bg:#F1F3F7;
    }

    body{ background:var(--ew-page-bg); margin:0; font-family:'Inter','Segoe UI',sans-serif !important; }

    .main-content{
    margin-left: var(--sidebar-width);
    padding: 24px 28px 48px;
    transition: margin-left .25s ease;
    box-sizing: border-box;
    }
    .main-content.collapsed{ margin-left: var(--sidebar-collapsed); }

    .dash-header{ margin-bottom:24px; }
    .dash-header h1{ font-size:26px; font-weight:800; color:var(--ew-text); margin:0 0 4px; letter-spacing:-.3px; }
    .dash-header p{ margin:0; color:var(--ew-text-muted); font-size:14px; }

    /* Hero welcome banner */
    .dash-hero{
    background:linear-gradient(135deg,var(--ew-navy) 0%,var(--ew-navy-light) 50%,#1B4B7A 100%);
    border-radius:20px; padding:28px 32px; margin-bottom:24px;
    display:flex; align-items:center; justify-content:space-between;
    color:#fff; position:relative; overflow:hidden;
    box-shadow:0 8px 32px rgba(10,30,61,.2);
    }
    .dash-hero::before{
    content:''; position:absolute; top:-40%; right:-10%; width:300px; height:300px;
    background:radial-gradient(circle,rgba(255,255,255,.06) 0%,transparent 70%);
    border-radius:50%;
    }
    .dash-hero::after{
    content:''; position:absolute; bottom:-60%; left:20%; width:400px; height:400px;
    background:radial-gradient(circle,rgba(221,17,30,.08) 0%,transparent 70%);
    border-radius:50%;
    }
    .dash-hero h2{ font-size:24px; font-weight:700; margin:0 0 6px; position:relative; z-index:1; color: #fff;}
    .dash-hero p{ font-size:14px; opacity:.8; margin:0; position:relative; z-index:1; }

    /* KPI cards — brand-colored with top accent stripe */
    .kpi-row{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }

    .kpi-card{
    border-radius:16px; padding:22px 20px 18px;
    display:flex; flex-direction:column; justify-content:space-between;
    position:relative; overflow:hidden;
    text-decoration:none; color:inherit;
    transition: transform .2s ease, box-shadow .2s ease;
    min-height:130px;
    background:#fff;
    border:1px solid var(--ew-border);
    box-shadow:var(--ew-shadow-sm);
    }
    .kpi-card:hover{ transform:translateY(-4px); box-shadow:var(--ew-shadow-lg); }

    .kpi-card .kpi-stripe{ position:absolute; top:0; left:0; right:0; height:4px; }
    .kpi-card.brand-navy .kpi-stripe{ background:linear-gradient(90deg,var(--ew-navy),var(--ew-navy-light)); }
    .kpi-card.brand-red .kpi-stripe{ background:linear-gradient(90deg,var(--ew-accent),#F87171); }
    .kpi-card.brand-teal .kpi-stripe{ background:linear-gradient(90deg,var(--ew-teal),#22D3EE); }
    .kpi-card.brand-green .kpi-stripe{ background:linear-gradient(90deg,var(--ew-green),#4ADE80); }

    .kpi-card .kpi-icon{
    width:48px; height:48px; border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    font-size:20px; margin-bottom:14px;
    }
    .kpi-card.brand-navy .kpi-icon{ background:var(--ew-primary-light); color:var(--ew-navy); }
    .kpi-card.brand-red .kpi-icon{ background:#FEF2F2; color:var(--ew-accent); }
    .kpi-card.brand-teal .kpi-icon{ background:#ECFEFF; color:var(--ew-teal); }
    .kpi-card.brand-green .kpi-icon{ background:#F0FDF4; color:var(--ew-green); }

    .kpi-card .kpi-label{
    font-size:12px; font-weight:600; color:var(--ew-text-muted);
    text-transform:uppercase; letter-spacing:.6px;
    margin-bottom:4px;
    }
    .kpi-card .kpi-value{
    font-size:34px; font-weight:800; color:var(--ew-text); line-height:1;
    margin:0 0 6px; letter-spacing:-.5px;
    }
    .kpi-card .kpi-sub{ font-size:12px; color:var(--ew-text-muted); }

    /* Quick action strip */
    .quick-strip{ display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
    .qa-btn{
    display:flex; align-items:center; gap:14px;
    padding:18px 20px; border-radius:14px;
    text-decoration:none; color:var(--ew-text);
    background:#fff;
    border:1px solid var(--ew-border-light);
    box-shadow:var(--ew-shadow-xs);
    font-size:14px; font-weight:700;
    transition: all .2s ease;
    }
    .qa-btn:hover{
    transform:translateY(-3px); box-shadow:var(--ew-shadow-lg);
    text-decoration:none; border-color:var(--ew-navy);
    }
    .qa-btn .qa-icon{
    width:44px; height:44px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:18px; flex-shrink:0;
    }
    .qa-btn.qa-1 .qa-icon{ background:linear-gradient(135deg,var(--ew-navy),var(--ew-navy-light)); }
    .qa-btn.qa-2 .qa-icon{ background:linear-gradient(135deg,var(--ew-accent),#F87171); }
    .qa-btn.qa-3 .qa-icon{ background:linear-gradient(135deg,var(--ew-teal),#22D3EE); }
    .qa-btn.qa-4 .qa-icon{ background:linear-gradient(135deg,var(--ew-orange),#FB923C); }

    /* Section cards */
    .section-row{ display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:18px; }
    .section-row-full{ display:grid; grid-template-columns:1fr; gap:18px; margin-bottom:18px; }
    .section-card{
    background:#fff; border-radius:16px;
    border:1px solid var(--ew-border-light);
    box-shadow:var(--ew-shadow-sm); overflow:hidden;
    }
    .section-card .sc-head{
    padding:18px 22px; border-bottom:1px solid var(--ew-border-light);
    display:flex; align-items:center; justify-content:space-between;
    }
    .section-card .sc-head h3{
    margin:0; font-size:15px; font-weight:700; color:var(--ew-text);
    display:flex; align-items:center; gap:10px;
    }
    .section-card .sc-head h3 i{ color:var(--ew-navy); }
    .section-card .sc-body{ padding:0; }

    .dash-table{ width:100%; border-collapse:collapse; font-size:13px; }
    .dash-table thead th{
    background:var(--ew-primary-light); padding:12px 16px;
    text-align:left; font-weight:700; color:var(--ew-navy);
    border-bottom:2px solid var(--ew-border); font-size:11px;
    text-transform:uppercase; letter-spacing:.5px;
    }
    .dash-table tbody td{ padding:12px 16px; border-bottom:1px solid var(--ew-border-light); color:var(--ew-text); }
    .dash-table tbody tr:last-child td{ border-bottom:none; }
    .dash-table tbody tr:hover td{ background:#F7F9FC; }

    .badge-transit{
    display:inline-block; padding:4px 12px; border-radius:999px;
    background:#EFF6FF; color:#1E5CB3; font-size:11px; font-weight:600;
    }
    .badge-delivered{
    display:inline-block; padding:4px 12px; border-radius:999px;
    background:#ECFDF5; color:#16A34A; font-size:11px; font-weight:600;
    }

    #chartContainer{ height:100%; width:100%; min-height:300px; }

    /* Activity feed */
    .activity-item{
    display:flex; align-items:flex-start; gap:14px; padding:14px 22px;
    border-bottom:1px solid var(--ew-border-light);
    }
    .activity-item:last-child{ border-bottom:none; }
    .activity-dot{
    width:10px; height:10px; border-radius:50%; margin-top:6px; flex-shrink:0;
    }
    .activity-dot.navy{ background:var(--ew-navy); }
    .activity-dot.red{ background:var(--ew-accent); }
    .activity-dot.teal{ background:var(--ew-teal); }
    .activity-dot.green{ background:var(--ew-green); }

    /* Stats mini-row */
    .mini-row{ display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
    .mini-card{
    background:#fff; border-radius:14px; padding:18px 20px;
    display:flex; align-items:center; gap:16px;
    box-shadow:var(--ew-shadow-xs);
    border:1px solid var(--ew-border-light);
    }
    .mini-card .mini-icon{
    width:48px; height:48px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:20px; flex-shrink:0;
    }
    .mini-card .mini-icon.navy{ background:var(--ew-primary-light); color:var(--ew-navy); }
    .mini-card .mini-icon.red{ background:#FEF2F2; color:var(--ew-accent); }
    .mini-card .mini-icon.teal{ background:#ECFEFF; color:var(--ew-teal); }
    .mini-card .mini-val{ font-size:22px; font-weight:800; color:var(--ew-text); line-height:1; letter-spacing:-.3px; }
    .mini-card .mini-lbl{ font-size:12.5px; color:var(--ew-text-muted); margin-top:3px; }

    @media(max-width:1100px){
    .kpi-row{ grid-template-columns:repeat(2,1fr); }
    .section-row{ grid-template-columns:1fr; }
    .quick-strip{ grid-template-columns:repeat(2,1fr); }
    .mini-row{ grid-template-columns:1fr; }
    }
    </style>

  
    </style>
    </head>

    <body class="page-header-fixed">

    <!-- Toast container handled globally by css_js.php -->

    <div class="modal-shiftfix">
    <div class="navbar navbar-fixed-top">
        <?php require_once ('include/header.php'); ?>
        <?php require_once ('include/menu.php'); ?>
    </div>

    <?php if ($_SESSION['role'] == 'AD' || $_SESSION['role'] == 'USER'): ?>

    <div class="container-fluid main-content" id="mainContent">

    <!-- Hero Banner -->
    <div class="dash-hero">
        <div>
        <h2>Welcome back, <?php echo htmlspecialchars(get_user($conn, $_SESSION['user_id'])); ?> 👋</h2>
        <p>Here's what's happening with your logistics today</p>
        </div>
        <div style="text-align:right;position:relative;z-index:1;">
        <div style="font-size:22px;font-weight:800;color:#fff;"><?php echo date('d M Y'); ?></div>
        <div style="font-size:13px;opacity:.7;"><?php echo date('l'); ?></div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-row">
        <a class="kpi-card brand-navy" href="transaction_list.php" style="cursor:pointer;">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-cubes"></i></div>
        <div>
            <div class="kpi-label">Total Booked</div>
            <div class="kpi-value"><?php echo number_format($booked); ?></div>
            <div class="kpi-sub">All consignments</div>
        </div>
        </a>
        <a class="kpi-card brand-teal" href="status_sheet.php" style="cursor:pointer;">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-truck"></i></div>
        <div>
            <div class="kpi-label">In Transit</div>
            <div class="kpi-value"><?php echo number_format($in_transit); ?></div>
            <div class="kpi-sub">Currently moving</div>
        </div>
        </a>
        <a class="kpi-card brand-green" href="consignment_report.php" style="cursor:pointer;">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-check-circle"></i></div>
        <div>
            <div class="kpi-label">Delivered</div>
            <div class="kpi-value"><?php echo number_format($delivered); ?></div>
            <div class="kpi-sub">Successfully completed</div>
        </div>
        </a>
        <div class="kpi-card brand-red" style="cursor:default;">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-calendar-check-o"></i></div>
        <div>
            <div class="kpi-label">Today's Bookings</div>
            <div class="kpi-value"><?php echo number_format($today_booked); ?></div>
            <div class="kpi-sub"><?php echo date('d M Y'); ?></div>
        </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-strip">
        <a class="qa-btn qa-1" href="transactions.php">
        <span class="qa-icon"><i class="fa fa-plus-circle"></i></span>
        <span>Book Consignment</span>
        </a>
        <a class="qa-btn qa-2" href="track_consignment.php">
        <span class="qa-icon"><i class="fa fa-map-marker"></i></span>
        <span>Track Shipment</span>
        </a>
        <a class="qa-btn qa-3" href="status_sheet.php">
        <span class="qa-icon"><i class="fa fa-list-alt"></i></span>
        <span>Status Sheet</span>
        </a>
        <a class="qa-btn qa-4" href="consignment_report.php">
        <span class="qa-icon"><i class="fa fa-bar-chart"></i></span>
        <span>MIS Report</span>
        </a>
    </div>

    <!-- Table + Chart row -->
    <div class="section-row">

        <div class="section-card">
        <div class="sc-head">
            <h3><i class="fa fa-clock-o"></i> Delivery Pending Consignments</h3>
        </div>
        <div class="sc-body" style="max-height:350px; overflow-y:auto;">
            <table class="dash-table">
            <thead>
                <tr>
                <th>#</th>
                <th>GRN No.</th>
                <th>Date</th>
                <th>Consignor → Consignee</th>
                <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $i = 1;
            $result2 = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
            while ($row2 = mysqli_fetch_assoc($result2)) {
                $mb = mysqli_query($conn, 'SELECT * FROM transaction_' . $row2['table_name'] . " WHERE status!='8' AND active_status=0 ORDER BY str_to_date(grn_date,'%d-%m-%Y') DESC LIMIT 50");
                while ($br = mysqli_fetch_array($mb)) {
                    ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo $br['grn_no']; ?></strong></td>
                    <td><?php echo $br['grn_date']; ?></td>
                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?php echo get_client_name($conn, $br['consigner']); ?> → <?php echo get_client_name($conn, $br['consignee']); ?>
                    </td>
                    <td>
                    <span class="badge-transit"><?php echo get_trans_status($br['status']); ?></span>
                    <a href="track_consignment.php?grn_no=<?php echo $br['grn_no']; ?>" title="Track">
                        <i class="fa fa-street-view" style="color:#059669;margin-left:4px;"></i>
                    </a>
                    </td>
                </tr>
                <?php }
            } ?>
            </tbody>
            </table>
        </div>
        </div>

        <div class="section-card">
        <div class="sc-head">
            <h3><i class="fa fa-bar-chart"></i> <?php echo $Y; ?> Year Summary</h3>
        </div>
        <div class="sc-body" style="padding:16px;">
            <div id="chartContainer"></div>
        </div>
        </div>

    </div><!-- /section-row -->

    </div><!-- /main-content -->

    <?php endif; ?>

    <?php
    if ($_SESSION['role'] == 'CL'):
        $query22 = 'SELECT * FROM users WHERE user_id=' . $_SESSION['user_id'];
        $result22 = mysqli_query($conn, $query22);
        $row22 = mysqli_fetch_assoc($result22);
        $booked_cl = 0;
        $arrivals_cl = 0;
        $result2 = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
        while ($row2 = mysqli_fetch_assoc($result2)) {
            $tbl = 'transaction_' . $row2['table_name'];
            $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM $tbl WHERE consigner='" . $row22['company_name'] . "'");
            $booked_cl += mysqli_fetch_assoc($r)['c'] ?? 0;
            $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM $tbl WHERE consignee='" . $row22['company_name'] . "'");
            $arrivals_cl += mysqli_fetch_assoc($r)['c'] ?? 0;
        }
        // rebuild chart points for CL
        $dataPoints1 = [];
        $dataPoints2 = [];
        for ($i = 1; $i <= 12; $i++) {
            $Date = sprintf('%02d', $i) . '-' . $Y;
            $full_date = '01-' . $Date;
            $tbl2 = get_trans_table_name($conn, $full_date);
            $r1 = mysqli_query($conn, "SELECT COUNT(grn_no) as c FROM $tbl2[0] WHERE grn_date LIKE '%$Date' AND consigner='" . $row22['company_name'] . "'");
            $r2 = mysqli_query($conn, "SELECT COUNT(grn_no) as c FROM $tbl2[0] WHERE grn_date LIKE '%$Date' AND consignee='" . $row22['company_name'] . "'");
            $dataPoints1[] = ['label' => $Month[$i - 1] . ' ' . $Y, 'y' => (int) (mysqli_fetch_assoc($r1)['c'] ?? 0)];
            $dataPoints2[] = ['label' => $Month[$i - 1] . ' ' . $Y, 'y' => (int) (mysqli_fetch_assoc($r2)['c'] ?? 0)];
        }
        $data_name = 'Booked';
        $data_name1 = 'Arrivals';
        ?>
    <div class="container-fluid main-content" id="mainContent">
    <div class="dash-hero">
        <div>
        <h2>Welcome back, <?php echo htmlspecialchars(get_user($conn, $_SESSION['user_id'])); ?> 👋</h2>
        <p>Track your bookings and arrivals in real time</p>
        </div>
    </div>
    <div class="kpi-row">
        <a class="kpi-card brand-navy" href="transaction_list.php">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-cubes"></i></div>
        <div><div class="kpi-label">My Bookings</div><div class="kpi-value"><?php echo $booked_cl; ?></div></div>
        </a>
        <a class="kpi-card brand-teal" href="javascript:void(0)">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-plane"></i></div>
        <div><div class="kpi-label">Arrivals</div><div class="kpi-value"><?php echo $arrivals_cl; ?></div></div>
        </a>
        <a class="kpi-card brand-green" href="transactions.php">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-plus-circle"></i></div>
        <div><div class="kpi-label">Book Now</div><div class="kpi-value"><i class="fa fa-plus" style="font-size:28px"></i></div></div>
        </a>
        <a class="kpi-card brand-red" href="track_consignment.php">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-map-marker"></i></div>
        <div><div class="kpi-label">Track Shipment</div><div class="kpi-value"><i class="fa fa-arrow-right" style="font-size:28px"></i></div></div>
        </a>
    </div>
    <div class="section-row">
        <div class="section-card">
        <div class="sc-head"><h3><i class="fa fa-list"></i> My Bookings & Arrivals</h3></div>
        <div class="sc-body" style="max-height:350px;overflow-y:auto;">
            <table class="dash-table">
            <thead><tr><th>#</th><th>GRN No.</th><th>Date</th><th>Route</th><th>Status</th></tr></thead>
            <tbody>
            <?php $i = 1;
            $result2 = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
            while ($row2 = mysqli_fetch_assoc($result2)) {
                $mb = mysqli_query($conn, 'SELECT * FROM transaction_' . $row2['table_name'] . " WHERE (consigner='" . $row22['company_name'] . "' OR consignee='" . $row22['company_name'] . "') ORDER BY str_to_date(grn_date,'%d-%m-%Y') DESC LIMIT 30");
                while ($br = mysqli_fetch_array($mb)) { ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo $br['grn_no']; ?></strong></td>
                    <td><?php echo $br['grn_date']; ?></td>
                    <td><?php echo get_city_name($conn, $br['origin']); ?> → <?php echo get_city_name($conn, $br['destination']); ?></td>
                    <td><span class="badge-transit"><?php echo get_trans_status($br['status']); ?></span></td>
                </tr>
            <?php }
            } ?>
            </tbody>
            </table>
        </div>
        </div>
        <div class="section-card">
        <div class="sc-head"><h3><i class="fa fa-bar-chart"></i> <?php echo $Y; ?> Activity</h3></div>
        <div class="sc-body" style="padding:16px;"><div id="chartContainer"></div></div>
        </div>
    </div>
    </div>
    <?php endif; ?>

    <?php
    if ($_SESSION['role'] == 'DR'):
        $user_id = $_SESSION['user_id'];
        $dr_q = mysqli_query($conn, "SELECT assigned_vehicle, user_name FROM users WHERE user_id='$user_id'");
        $dr_r = mysqli_fetch_assoc($dr_q);
        $assigned_veh = $dr_r['assigned_vehicle'] ?? '';
        $driver_name = $dr_r['user_name'] ?? 'Driver';

        // Collect active consignments for this driver's vehicle
        $consignments = [];
        $seen_grn = [];
        $total_assigned = 0;
        $total_delivered_dr = 0;
        $today_deliveries = 0;
        $today_date = date('d-m-Y');

        if (!empty($assigned_veh)) {
            $seen_stats = [];  // separate tracker just for stats counting
            $result2 = mysqli_query($conn, 'SELECT * FROM transaction_tbls');
            while ($row2 = mysqli_fetch_assoc($result2)) {
                $tbl = 'transaction_' . $row2['table_name'];
                // stats pass — count totals (deduped)
                $rall = mysqli_query($conn, "SELECT grn_no,status,grn_date FROM $tbl WHERE truck='$assigned_veh'");
                while ($ra = mysqli_fetch_assoc($rall)) {
                    if (in_array($ra['grn_no'], $seen_stats))
                        continue;
                    $seen_stats[] = $ra['grn_no'];
                    $total_assigned++;
                    if ($ra['status'] == '8') {
                        $total_delivered_dr++;
                        if ($ra['grn_date'] == $today_date)
                            $today_deliveries++;
                    }
                }
                // table pass — ALL consignments for this vehicle (deduped), newest first
                $res = mysqli_query($conn, "SELECT * FROM $tbl WHERE truck='$assigned_veh' AND active_status=0 ORDER BY str_to_date(grn_date,'%d-%m-%Y') DESC");
                if ($res) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        if (in_array($row['grn_no'], $seen_grn))
                            continue;
                        $seen_grn[] = $row['grn_no'];
                        $row['table_name'] = $tbl;
                        $consignments[] = $row;
                    }
                }
            }
        }
        $pending_dr = 0;
        foreach ($consignments as $c) {
            if ($c['status'] != '8')
                $pending_dr++;
        }
        $completion_pct = $total_assigned > 0 ? round(($total_delivered_dr / $total_assigned) * 100) : 0;
        ?>

    <div class="container-fluid main-content" id="mainContent">

    <script>
        /* Hide the loading spinner immediately for driver — no chart to wait for */
        $(function(){ $(".loading-page").hide(); });
        /* Sync sidebar margin */
        (function(){
        if(localStorage.getItem('sidebar_collapsed')==='1'){
            document.getElementById('mainContent') && document.getElementById('mainContent').classList.add('collapsed');
        }
        })();
    </script>

    <!-- Welcome banner -->
    <div class="dash-hero">
        <div>
        <div style="font-size:13px;opacity:.75;letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px;">Driver Dashboard</div>
        <h2>Welcome back, <?php echo htmlspecialchars($driver_name); ?> 👋</h2>
        <div style="display:flex;align-items:center;gap:10px;font-size:14px;opacity:.85;margin-top:6px;">
            <i class="fa fa-truck"></i>
            Assigned Vehicle:
            <span style="background:rgba(255,255,255,.2);padding:4px 12px;border-radius:8px;font-weight:600;">
            <?php echo $assigned_veh ?: 'None assigned'; ?>
            </span>
        </div>
        </div>
        <div style="text-align:right;position:relative;z-index:1;">
        <div style="font-size:22px;font-weight:800;color:#fff;"><?php echo date('d M Y'); ?></div>
        <div style="font-size:13px;opacity:.7;"><?php echo date('l'); ?></div>
        </div>
    </div>

    <!-- KPI row -->
    <div class="kpi-row">
        <div class="kpi-card brand-navy" style="cursor:default;">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-cubes"></i></div>
        <div>
            <div class="kpi-label">Total Assigned</div>
            <div class="kpi-value"><?php echo $total_assigned; ?></div>
            <div class="kpi-sub">All-time consignments</div>
        </div>
        </div>

        <div class="kpi-card brand-red" style="cursor:default;">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-clock-o"></i></div>
        <div>
            <div class="kpi-label">Active / Pending</div>
            <div class="kpi-value"><?php echo $pending_dr; ?></div>
            <div class="kpi-sub">Awaiting delivery</div>
        </div>
        </div>

        <div class="kpi-card brand-green" style="cursor:default;">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-check-circle"></i></div>
        <div>
            <div class="kpi-label">Total Delivered</div>
            <div class="kpi-value"><?php echo $total_delivered_dr; ?></div>
            <div class="kpi-sub">Successfully completed</div>
        </div>
        </div>

        <div class="kpi-card brand-teal" style="cursor:default;">
        <div class="kpi-stripe"></div>
        <div class="kpi-icon"><i class="fa fa-line-chart"></i></div>
        <div>
            <div class="kpi-label">Completion Rate</div>
            <div class="kpi-value"><?php echo $completion_pct; ?>%</div>
            <div class="kpi-sub">Overall performance</div>
        </div>
        </div>
    </div>

    <!-- Progress bar row -->
    <div class="section-card" style="margin-bottom:24px;">
        <div class="sc-head">
        <h3><i class="fa fa-tachometer"></i> Delivery Performance</h3>
        <span style="font-size:13px;color:#6b7280;"><?php echo $total_delivered_dr; ?> of <?php echo $total_assigned; ?> completed</span>
        </div>
        <div class="sc-body" style="padding:20px 24px;">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
            <div style="grid-column:1/-1;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="font-size:13px;color:#374151;font-weight:600;">Overall Completion</span>
                <span style="font-size:13px;font-weight:700;color:var(--ew-primary);"><?php echo $completion_pct; ?>%</span>
            </div>
            <div style="background:#f1f5f9;border-radius:999px;height:10px;overflow:hidden;">
                <div style="height:100%;width:<?php echo $completion_pct; ?>%;
                            background:linear-gradient(90deg,var(--ew-navy),var(--ew-accent));
                            border-radius:999px;transition:width .6s ease;"></div>
            </div>
            </div>
            <div style="background:#f8fafc;border-radius:12px;padding:14px 18px;text-align:center;border:1px solid #e2e8f0;">
            <div style="font-size:22px;font-weight:700;color:var(--ew-primary);"><?php echo $total_assigned; ?></div>
            <div style="font-size:12px;color:#6b7280;margin-top:3px;">Total Assigned</div>
            </div>
            <div style="background:#ecfdf5;border-radius:12px;padding:14px 18px;text-align:center;border:1px solid #d1fae5;">
            <div style="font-size:22px;font-weight:700;color:var(--ew-teal);"><?php echo $total_delivered_dr; ?></div>
            <div style="font-size:12px;color:#6b7280;margin-top:3px;">Delivered</div>
            </div>
            <div style="background:#fef2f2;border-radius:12px;padding:14px 18px;text-align:center;border:1px solid #fecaca;">
            <div style="font-size:22px;font-weight:700;color:var(--ew-accent);"><?php echo $pending_dr; ?></div>
            <div style="font-size:12px;color:#6b7280;margin-top:3px;">Pending</div>
            </div>
        </div>
        </div>
    </div>

    <!-- Active deliveries table -->
    <div class="section-card">
        <div class="sc-head">
        <h3><i class="fa fa-truck"></i> Active Delivery Queue
            <span style="background:var(--ew-accent);color:#fff;font-size:11px;font-weight:700;
                        padding:2px 9px;border-radius:999px;margin-left:8px;"><?php echo $pending_dr; ?></span>
        </h3>
        </div>
        <!-- <a href="pod_master.php" class="qa-btn" style="padding:8px 16px;border-radius:8px;border:1.5px solid #DD111E;
            color:#DD111E;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;box-shadow:none;">
            <i class="fa fa-upload"></i> Upload POD
        </a> -->
        </div>
        <div class="sc-body">
        <?php if (empty($consignments)): ?>
            <div style="text-align:center;padding:60px 20px;color:#6b7280;">
            <i class="fa fa-truck" style="font-size:52px;color:#cbd5e1;display:block;margin-bottom:14px;"></i>
            <div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:6px;">No consignments assigned yet.</div>
            <div style="font-size:14px;">No consignments have been assigned to your vehicle.</div>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="dash-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>GRN Number</th>
                    <th>Date</th>
                    <th>Consignee</th>
                    <th>Origin → Destination</th>
                    <th>Status</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($consignments as $idx => $cons):
                    $is_delivered = ($cons['status'] == '8');
                    $pod_uploaded = $is_delivered ? get_pod_status($conn, $cons['grn_no']) : false;
                    ?>
                <tr style="<?php echo $is_delivered ? 'opacity:0.72;' : ''; ?>">
                    <td><?php echo $idx + 1; ?></td>
                    <td><strong style="color:var(--ew-primary);"><?php echo htmlspecialchars($cons['grn_no']); ?></strong></td>
                    <td><?php echo htmlspecialchars($cons['grn_date']); ?></td>
                    <td><?php echo htmlspecialchars(get_client_name($conn, $cons['consignee'])); ?></td>
                    <td>
                    <?php echo htmlspecialchars(get_city_name($conn, $cons['origin'])); ?>
                    <span style="color:#9ca3af;"> → </span>
                    <?php echo htmlspecialchars(get_city_name($conn, $cons['destination'])); ?>
                    </td>
                    <td>
                    <?php if ($is_delivered): ?>
                        <span class="badge-delivered"><i class="fa fa-check"></i> Delivered</span>
                    <?php else: ?>
                        <span class="badge-transit"><?php echo htmlspecialchars(get_trans_status($cons['status'])); ?></span>
                    <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                    <?php if (!$is_delivered): ?>
                        <button class="btn-update-status"
                    data-grn-id="<?php echo $cons['grn_id']; ?>"
                    data-grn-no="<?php echo $cons['grn_no']; ?>"
                    data-status="<?php echo $cons['status']; ?>"
                    data-table="<?php echo $cons['table_name']; ?>"
                    style="background:var(--ew-primary);color:#fff;border:none;padding:7px 14px;border-radius:8px;font-size:12.5px;font-weight:600;cursor:pointer;">
                <i class="fa fa-edit"></i> Update Status
            </button>
        <?php elseif (!$pod_uploaded): ?>
            <button class="btn-upload-pod"
                    data-grn-id="<?php echo $cons['grn_id']; ?>"
                    data-grn-no="<?php echo htmlspecialchars($cons['grn_no']); ?>"
                    style="background:#059669;color:#fff;border:none;padding:7px 14px;border-radius:8px;font-size:12.5px;font-weight:600;cursor:pointer;">
                <i class="fa fa-upload"></i> Upload POD
            </button>
        <?php else: ?>
            <span style="color:#059669;font-size:12.5px;font-weight:600;">
                <i class="fa fa-check-circle"></i> POD Uploaded
            </span>
        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
        </div>
    </div>

    </div><!-- /main-content driver -->

    <!-- Status Update Modal -->
    <div class="modal fade" id="driverStatusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;
            box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div class="modal-header" style="background:linear-gradient(135deg,var(--ew-primary),var(--ew-accent));
            color:#fff;padding:20px 24px;border:none;">
            <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:.8;">
            <span>&times;</span>
            </button>
            <h4 class="modal-title" style="margin:0;font-weight:700;font-size:17px;">
            <i class="fa fa-refresh"></i> &nbsp;Update Delivery Status
            </h4>
        </div>
        <form id="driver_status_form">
            <input type="hidden" name="form_name" value="status_change_consignment">
            <input type="hidden" id="modal_grn_id"      name="grn_id">
            <input type="hidden" id="modal_grn_no"      name="grn_no">
            <input type="hidden" id="modal_table_names" name="table_names">
            <div class="modal-body" style="padding:24px;">
            <div style="margin-bottom:18px;">
                <label style="font-size:12.5px;font-weight:600;color:var(--ew-text-muted);
                            text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;display:block;">
                Consignment
                </label>
                <input type="text" id="modal_grn_display" class="form-control" readonly
                    style="background:#f8fafc;border-radius:10px;border:1.5px solid #e5e7eb;
                            font-weight:700;font-size:14px;color:var(--ew-primary);padding:10px 14px;">
            </div>
            <!-- <div style="margin-bottom:18px;">
                <label style="font-size:12.5px;font-weight:600;color:#374151;
                            text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;display:block;">
                New Status <span style="color:#DD111E;">*</span></label> -->
                <!--
                Fixed: the list previously started at value="2", so any consignment
                whose current status was "0" or "1" had no matching <option> and the  
                dropdown rendered blank even though data-status was set correctly.
                Adding the missing early-stage statuses fixes the "selected value not
                showing" issue reported on the driver dashboard.
                -->
                 <div class="form-group" style="margin-bottom: 16px;">
                           <label style="font-size:12.5px;font-weight:600;color:var(--ew-text-muted);
                            text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;display:block;">
                New Status <span style="color:var(--ew-accent);">*</span></label>
                            <select class="form-control" name="status" id="modal_status" required style="border-radius: 6px;">
                                <option value="2">Consignment Picked Up</option>
                                <option value="3">In Transit - 1 (Consignment at Origin State)</option>
                                <option value="4">In Transit - 2 (Towards Destination State)</option>
                                <option value="5">In Transit - 3 (Towards Destination)</option>
                                <option value="6">At Destination</option>
                                <option value="7">Out for Delivery</option>
                                <option value="8">Consignment Delivered Successfully</option>
                            </select>
                        </div>
            <!-- </div> -->
            <div>
                <label style="font-size:12.5px;font-weight:600;color:#374151;
                            text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;display:block;">
                Remarks <span style="color:var(--ew-accent);">*</span></label>
                <textarea class="form-control" name="remarks" id="modal_remarks" required rows="3"
                        style="border-radius:10px;border:1.5px solid #e5e7eb;padding:10px 14px;
                                font-size:14px;resize:none;"
                        placeholder="e.g., Loaded at origin hub / Out for delivery"></textarea>
            </div>
            </div>
            <div class="modal-footer" style="background:#f8f9fb;border:none;padding:16px 24px;
                display:flex;justify-content:flex-end;gap:10px;">
            <button type="button" class="btn btn-default" data-dismiss="modal"
                    style="border-radius:8px;font-weight:600;padding:8px 20px;">Cancel</button>
            <button type="button" id="btn_submit_status"
                    style="background:linear-gradient(135deg,var(--ew-primary),var(--ew-accent));color:#fff;
                            border:none;border-radius:8px;font-weight:700;padding:8px 22px;
                            font-size:14px;cursor:pointer;">
                Save Changes
            </button>
            </div>
        </form>
        </div>
    </div>
    </div>

    <div class="modal fade" id="driverPodModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.2);">
      <div class="modal-header" style="background:linear-gradient(135deg,var(--ew-primary),var(--ew-accent));color:#fff;padding:20px 24px;border:none;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:.8;"><span>&times;</span></button>
        <h4 class="modal-title" style="margin:0;font-weight:700;font-size:17px;"><i class="fa fa-upload"></i> &nbsp;Upload Proof of Delivery</h4>
      </div>
      <form id="driver_pod_form" enctype="multipart/form-data">
        <input type="hidden" name="form_name" value="driver_pod_upload">
        <input type="hidden" id="pod_grn_id" name="grn_id">
        <input type="hidden" id="pod_grn_no" name="grn_no">
        <div class="modal-body" style="padding:24px;">
          <div style="margin-bottom:18px;">
            <label style="font-size:12.5px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;display:block;">GRN Number</label>
            <input type="text" id="pod_grn_display" class="form-control" readonly
                   style="background:#f8fafc;border-radius:10px;border:1.5px solid #e5e7eb;font-weight:700;font-size:14px;color:var(--ew-primary);padding:10px 14px;">
          </div>
          <div>
            <label style="font-size:12.5px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;display:block;">
              Select POD Image <span style="color:var(--ew-accent);">*</span></label>
            <input type="file" name="pod_file" id="pod_file_input" accept=".jpg,.jpeg,.png" class="form-control" required style="border-radius:6px;">
            <small style="color:#6b7280;">Allowed: JPEG, JPG, PNG</small>
          </div>
        </div>
        <div class="modal-footer" style="background:#f8fafc;border:none;padding:16px 24px;display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius:8px;font-weight:600;padding:8px 20px;">Cancel</button>
          <button type="button" id="btn_submit_pod" style="background:linear-gradient(135deg,var(--ew-primary),var(--ew-accent));color:#fff;border:none;border-radius:8px;font-weight:700;padding:8px 22px;font-size:14px;cursor:pointer;">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

    <script>
    /* Toast uses global ewToast from css_js.php */

    $(document).ready(function() {
        $(document).on('click', '.btn-update-status', function() {
            var grn_id = $(this).attr('data-grn-id');
            var grn_no = $(this).attr('data-grn-no');
            var current_status = $(this).attr('data-status');
            var table_name = $(this).attr('data-table');
            
            $('#modal_grn_id').val(grn_id);
            $('#modal_grn_no').val(grn_no);
            $('#modal_grn_display').val(grn_no);
            $('#modal_table_names').val(table_name);
            $('#modal_status').val(current_status);
            $('#modal_remarks').val('');
            
            $('#driverStatusModal').modal('show');
        });
        
        $(document).on('click', '#btn_submit_status', function() {
            if ($('#driver_status_form').valid() == true) {
                $(".loading-page").show();
                var data = $('#driver_status_form').serialize();
                $.ajax({
                    url: "save_details.php",
                    type: "post",
                    data: data,
                    success: function(result) {
                        $(".loading-page").hide();
                        $('#driverStatusModal').modal('hide');
                        if(result == 1) {
                            ewToast("Status updated successfully!","success");
                            setTimeout(function(){ location.reload(); }, 1100);
                        } else {
                            ewToast("Failed to update. Please try again.","error");
                        }
                    },
                    error: function(jqxhr) {
                        $(".loading-page").hide();
                      ewToast("An error occurred.Please try again.","error");
                    }
                });
            }
        });

        $(document).on('click', '.btn-upload-pod', function() {
    $('#pod_grn_id').val($(this).attr('data-grn-id'));
    $('#pod_grn_no').val($(this).attr('data-grn-no'));
    $('#pod_grn_display').val($(this).attr('data-grn-no'));
    $('#pod_file_input').val('');
    $('#driverPodModal').modal('show');
});

$(document).on('click', '#btn_submit_pod', function() {
    var fileInput = document.getElementById('pod_file_input');
    if (!fileInput.files.length) {
        ewToast("Please select a POD image.", "error");
        return;
    }
    var formdata = new FormData(document.getElementById('driver_pod_form'));
    $(".loading-page").show();
    $.ajax({
        url: "save_details.php",
        type: "post",
        data: formdata,
        contentType: false,
        processData: false,
        success: function(result) {
            $(".loading-page").hide();
            $('#driverPodModal').modal('hide');
            if (result == 1) {
                ewToast("POD uploaded successfully!", "success");
                setTimeout(function() { location.reload(); }, 1100);
            } else {
                ewToast("Upload failed or POD already exists for this GRN.", "error");
            }
        },
        error: function() {
            $(".loading-page").hide();
            ewToast("An error occurred. Please try again.", "error");
        }
    });
});
    });
    </script>

    <?php endif; ?>

    <?php require_once ('include/footer.php'); ?>
    </div><!-- /modal-shiftfix -->

    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script>
    // ── Sync main-content margin with sidebar ────────────────────────────────────
    (function(){
    var mc = document.getElementById('mainContent');
    if(!mc) return;
    // read current state from localStorage (set by sidebar.php)
    if(localStorage.getItem('sidebar_collapsed')==='1'){
        mc.classList.add('collapsed');
    }
    // listen for sidebar toggle
    window.addEventListener('sidebarToggled', function(e){
        if(e.detail && e.detail.collapsed) mc.classList.add('collapsed');
        else mc.classList.remove('collapsed');
    });
    })();

    // ── Chart ─────────────────────────────────────────────────────────────────────
    window.onload = function(){
    $(".loading-page").hide(); /* fallback hide for all roles */
    if(!document.getElementById('chartContainer')) return; /* driver has no chart */
    var dp1 = <?php echo json_encode($dataPoints1, JSON_NUMERIC_CHECK); ?>;
    var dp2 = <?php echo json_encode($dataPoints2, JSON_NUMERIC_CHECK); ?>;
    var cats = dp1.map(function(p){ return p.label; });
    Highcharts.chart('chartContainer',{
        chart:{ type:'column', backgroundColor:'transparent', style:{ fontFamily:'Inter,sans-serif' } },
        title:{ text:'', margin:0 },
        xAxis:{ categories:cats, labels:{ style:{ fontSize:'11px', color:'#6b7280' } }, lineColor:'#e5e7eb', tickColor:'#e5e7eb' },
        yAxis:{ min:0, title:{ text:'' }, gridLineColor:'#f3f4f6', labels:{ style:{ color:'#6b7280' } } },
        legend:{ align:'center', verticalAlign:'bottom', itemStyle:{ color:'#374151', fontWeight:'600' } },
        tooltip:{ shared:true, backgroundColor:'#fff', borderRadius:10, shadow:true },
        plotOptions:{ column:{ borderRadius:6, pointPadding:.15, groupPadding:.1 } },
        colors:['#0A1E3D','#0891B2'],
        series:[
        { name:'<?php echo $data_name ?? 'Delivered'; ?>', data:dp1.map(function(p){ return p.y; }) },
        { name:'<?php echo $data_name1 ?? 'Pending'; ?>',  data:dp2.map(function(p){ return p.y; }) }
        ],
        credits:{ enabled:false }
    });
    $(".loading-page").hide();
    };
    </script>

    <script>
    // Patch toggleSidebar to fire custom event so dashboard can react
    var _origToggle = window.toggleSidebar;
    window.toggleSidebar = function(){
    if(_origToggle) _origToggle();
    var mc = document.getElementById('mainContent');
    if(!mc) return;
    var collapsed = document.getElementById('sidebar') && document.getElementById('sidebar').classList.contains('collapsed');
    if(collapsed) mc.classList.add('collapsed');
    else mc.classList.remove('collapsed');
    };
    </script>
    </body>
    </html>