<?php
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/function.php';
require_once __DIR__ . '/include/eway/eway_schema.php';

if (empty($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'AD')) {
    echo '<script>location.href="dashboard.php";</script>';
    exit;
}

ew_eway_ensure_schema($conn);
$s = ew_eway_get_settings($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . '/include/title.php'; ?>
    <?php include __DIR__ . '/include/css_js.php'; ?>
    <link href="stylesheets/eway-partb.css?v=1" rel="stylesheet" type="text/css" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
</head>
<body class="page-header-fixed bg-1">
<div class="modal-shiftfix">
    <div class="navbar navbar-fixed-top scroll-hide">
        <?php require_once __DIR__ . '/include/header.php'; require_once __DIR__ . '/include/menu.php'; ?>
    </div>
    <div class="container-fluid main-content new_dpt_bottom">
        <div class="row">
            <div class="col-md-12">
                <div class="ew-page-v2">
                    <div class="ew-page-head">
                        <div class="ew-page-head-left">
                            <h1 class="ew-page-title">E-Way API settings</h1>
                            <p class="ew-page-sub">NIC credentials and module toggle (server-side only).</p>
                        </div>
                    </div>
                    <div class="ew-card ew-partb-card">
                        <form id="ewaySettingsForm" autocomplete="off">
                            <div class="ew-partb-grid">
                                <div class="form-group">
                                    <label>Module</label>
                                    <select name="module_enabled" class="form-control">
                                        <option value="0" <?php echo (int)($s['module_enabled'] ?? 0) === 0 ? 'selected' : ''; ?>>Off</option>
                                        <option value="1" <?php echo (int)($s['module_enabled'] ?? 0) === 1 ? 'selected' : ''; ?>>On</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Environment</label>
                                    <select name="environment" class="form-control">
                                        <option value="sandbox" <?php echo ($s['environment'] ?? '') === 'sandbox' ? 'selected' : ''; ?>>Sandbox</option>
                                        <option value="production" <?php echo ($s['environment'] ?? '') === 'production' ? 'selected' : ''; ?>>Production</option>
                                    </select>
                                </div>
                                <div class="form-group span-2">
                                    <label>API base URL</label>
                                    <input type="url" name="api_base_url" class="form-control" value="<?php echo htmlspecialchars($s['api_base_url'] ?? ''); ?>" placeholder="https://… (no trailing slash)" />
                                </div>
                                <div class="form-group">
                                    <label>Auth path</label>
                                    <input type="text" name="auth_path" class="form-control" value="<?php echo htmlspecialchars($s['auth_path'] ?? '/authenticate'); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>VEHEWB path</label>
                                    <input type="text" name="eway_api_path" class="form-control" value="<?php echo htmlspecialchars($s['eway_api_path'] ?? '/ewaybillapi/EwayApi'); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Get EWB path</label>
                                    <input type="text" name="get_eway_path" class="form-control" value="<?php echo htmlspecialchars($s['get_eway_path'] ?? '/ewayapi/GetEwayBill'); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Requester GSTIN</label>
                                    <input type="text" name="gstin" class="form-control" maxlength="15" value="<?php echo htmlspecialchars($s['gstin'] ?? ''); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Client ID</label>
                                    <input type="text" name="client_id" class="form-control" value="<?php echo htmlspecialchars($s['client_id'] ?? ''); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Client secret</label>
                                    <input type="password" name="client_secret" class="form-control" placeholder="<?php echo ($s['client_secret'] ?? '') !== '' ? '•••••••• (leave blank to keep)' : ''; ?>" />
                                </div>
                                <div class="form-group">
                                    <label>API username</label>
                                    <input type="text" name="api_username" class="form-control" value="<?php echo htmlspecialchars($s['api_username'] ?? ''); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>API password</label>
                                    <input type="password" name="api_password" class="form-control" placeholder="<?php echo ($s['api_password'] ?? '') !== '' ? '•••••••• (leave blank to keep)' : ''; ?>" />
                                </div>
                                <div class="form-group span-2">
                                    <label>NIC public key (Base64)</label>
                                    <textarea name="nic_public_key" class="form-control" rows="4" placeholder="Paste Base64 public key from E-Way API portal"><?php echo htmlspecialchars($s['nic_public_key'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Default transport mode</label>
                                    <select name="default_trans_mode" class="form-control">
                                        <?php for ($m = 1; $m <= 4; $m++) { ?>
                                            <option value="<?php echo $m; ?>" <?php echo ($s['default_trans_mode'] ?? '1') == (string)$m ? 'selected' : ''; ?>><?php echo $m; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Default vehicle type</label>
                                    <select name="default_vehicle_type" class="form-control">
                                        <option value="R" <?php echo ($s['default_vehicle_type'] ?? 'R') === 'R' ? 'selected' : ''; ?>>R — Regular</option>
                                        <option value="O" <?php echo ($s['default_vehicle_type'] ?? '') === 'O' ? 'selected' : ''; ?>>O — ODC</option>
                                    </select>
                                </div>
                            </div>
                            <div class="ew-partb-actions">
                                <button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btnTestAuth">Test connection</button>
                                <button type="submit" class="ew-btn-v2 ew-btn-v2-primary">Save settings</button>
                            </div>
                            <p class="ew-partb-hint">Credentials are stored encrypted. They are never sent to the browser except during save.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="javascripts/eway-partb-settings.js?v=1"></script>
</body>
</html>
