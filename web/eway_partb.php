<?php
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/function.php';
require_once __DIR__ . '/include/eway/eway_schema.php';

ew_eway_ensure_schema($conn);
$settings = ew_eway_get_settings($conn);
$moduleOn = $settings && (int) ($settings['module_enabled'] ?? 0) === 1;

$states = array();
$stateRes = mysqli_query($conn, 'SELECT state_id, state_name FROM state WHERE status=0 ORDER BY state_name');
while ($stateRes && ($st = mysqli_fetch_assoc($stateRes))) {
    $states[] = $st;
}

$defMode = $settings['default_trans_mode'] ?? '1';
$defVtype = $settings['default_vehicle_type'] ?? 'R';
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . '/include/title.php'; ?>
    <?php include __DIR__ . '/include/css_js.php'; ?>
    <link href="stylesheets/eway-partb.css?v=1" rel="stylesheet" type="text/css" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
</head>
<body class="page-header-fixed bg-1" data-partb-module="<?php echo $moduleOn ? '1' : '0'; ?>"
      data-default-trans-mode="<?php echo htmlspecialchars($defMode); ?>"
      data-default-vehicle-type="<?php echo htmlspecialchars($defVtype); ?>">
<div class="modal-shiftfix">
    <div class="navbar navbar-fixed-top scroll-hide">
        <?php require_once __DIR__ . '/include/header.php'; require_once __DIR__ . '/include/menu.php'; ?>
    </div>
    <div class="container-fluid main-content new_dpt_bottom">
        <div class="row">
            <div class="col-md-12">
                <div class="ew-page-v2 ew-page-v2--wide-table">
                    <div class="ew-page-head">
                        <div class="ew-page-head-left">
                            <h1 class="ew-page-title">E-Way Bill Part-B update</h1>
                            <p class="ew-page-sub">Update vehicle / Part-B by E-Way Bill number (same as the GST portal).</p>
                        </div>
                    </div>

                    <?php if (!$moduleOn) { ?>
                        <div class="alert alert-warning ew-partb-banner">
                            Part-B module is <strong>disabled</strong>. An administrator can enable it under
                            <a href="eway_partb_settings.php">Tools → E-Way API settings</a>.
                        </div>
                    <?php } ?>

                    <div class="ew-card ew-partb-card">
                        <div class="ew-card-toolbar"><h2>Look up E-Way Bill</h2></div>
                        <div class="ew-partb-lookup">
                            <div class="form-group">
                                <label>E-Way Bill number</label>
                                <input type="text" id="ewbNoInput" class="form-control" maxlength="14" placeholder="12-digit E-Way Bill number" inputmode="numeric" />
                            </div>
                            <button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="btnLoadEwb" <?php echo $moduleOn ? '' : 'disabled'; ?>>Load</button>
                        </div>
                        <div id="ewbContext" class="ew-partb-context hidden">
                            <div class="ew-partb-summary">
                                <div><span class="lbl">E-Way Bill</span> <strong id="ctxEwbNo">—</strong></div>
                                <div><span class="lbl">Current vehicle</span> <strong id="ctxCurrentVehicle">—</strong></div>
                                <div><span class="lbl">Valid upto</span> <strong id="ctxValidUpto">—</strong></div>
                                <div class="muted" id="ctxSource"></div>
                            </div>
                        </div>
                    </div>

                    <div class="ew-card ew-partb-card" id="updateCard">
                        <div class="ew-card-toolbar"><h2>Update Part-B</h2></div>
                        <form id="partbUpdateForm">
                            <input type="hidden" name="ewb_no" id="fieldEwbNo" value="" />
                            <input type="hidden" name="current_vehicle" id="fieldCurrentVehicle" value="" />
                            <div class="ew-partb-vehicle-compare">
                                <div>Current vehicle: <strong id="compareOld">—</strong></div>
                                <div>New vehicle: <strong id="compareNew">—</strong></div>
                            </div>
                            <div class="ew-partb-grid">
                                <div class="form-group">
                                    <label>New vehicle number <span class="req">*</span></label>
                                    <input type="text" name="vehicle_no" id="fieldVehicleNo" class="form-control" maxlength="15" required />
                                </div>
                                <div class="form-group">
                                    <label>Reason <span class="req">*</span></label>
                                    <select name="reason_code" class="form-control" required>
                                        <option value="1">1 — Breakdown</option>
                                        <option value="2">2 — Transshipment</option>
                                        <option value="3">3 — Others</option>
                                        <option value="4" selected>4 — First time</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Transport mode <span class="req">*</span></label>
                                    <select name="trans_mode" id="fieldTransMode" class="form-control">
                                        <option value="1">1 — Road</option>
                                        <option value="2">2 — Rail</option>
                                        <option value="3">3 — Air</option>
                                        <option value="4">4 — Ship</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Vehicle type</label>
                                    <select name="vehicle_type" class="form-control">
                                        <option value="R">R — Regular</option>
                                        <option value="O">O — ODC</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>From place <span class="req">*</span></label>
                                    <input type="text" name="from_place" class="form-control" maxlength="50" required />
                                </div>
                                <div class="form-group">
                                    <label>From state <span class="req">*</span></label>
                                    <select name="from_state" class="form-control" required>
                                        <option value="">Select state</option>
                                        <?php foreach ($states as $st) { ?>
                                            <option value="<?php echo (int) $st['state_id']; ?>"><?php echo htmlspecialchars($st['state_name']); ?> (<?php echo (int) $st['state_id']; ?>)</option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group span-2">
                                    <label>Reason remarks <span class="req">*</span></label>
                                    <input type="text" name="reason_rem" class="form-control" maxlength="50" required />
                                </div>
                                <div class="form-group trans-doc-field">
                                    <label>Transport document no.</label>
                                    <input type="text" name="trans_doc_no" class="form-control" maxlength="15" />
                                </div>
                                <div class="form-group trans-doc-field">
                                    <label>Transport document date</label>
                                    <input type="text" name="trans_doc_date" class="form-control" placeholder="dd/mm/yyyy" maxlength="10" />
                                </div>
                            </div>
                            <div class="ew-partb-actions">
                                <button type="submit" class="ew-btn-v2 ew-btn-v2-primary" id="btnUpdatePartB" <?php echo $moduleOn ? '' : 'disabled'; ?>>Update Part-B</button>
                            </div>
                            <div id="partbFormMessage" class="ew-partb-message hidden" role="alert"></div>
                        </form>
                    </div>

                    <div class="ew-card ew-partb-card">
                        <div class="ew-card-toolbar">
                            <h2>Update history</h2>
                            <div class="ew-toolbar-right">
                                <input type="text" id="historyFilterEwb" class="form-control input-sm" placeholder="Filter by EWB no." style="max-width:160px;display:inline-block;" />
                                <button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-sm" id="btnRefreshHistory">Refresh</button>
                            </div>
                        </div>
                        <div class="ew-table-wrap">
                            <table class="table table-bordered table-striped" id="partbHistoryTable">
                                <thead>
                                    <tr>
                                        <th>Date / time</th>
                                        <th>E-Way Bill</th>
                                        <th>Old vehicle</th>
                                        <th>New vehicle</th>
                                        <th>From</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody id="partbHistoryBody">
                                    <tr><td colspan="7" class="text-center text-muted">Loading…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="javascripts/eway-partb.js?v=1"></script>
</body>
</html>
