<?php

function ew_geo_scope_from_request()
{
	$raw = '';
	if (isset($_GET['scope'])) {
		$raw = $_GET['scope'];
	} elseif (isset($_POST['geo_scope'])) {
		$raw = $_POST['geo_scope'];
	}
	$s = strtolower(trim((string) $raw));
	return $s === 'international' ? 'international' : 'domestic';
}

function ew_geo_scope_ensure_column($conn, $table)
{
	$allowed = array(
		'state' => true,
		'city' => true,
		'branch' => true,
		'cfs_master' => true,
		'mode_of_transportation' => true,
		'consignment_mode' => true,
		'vehicle' => true,
		'vehicle_type_master' => true,
		'train' => true,
		'flight' => true,
	);
	if (!isset($allowed[$table])) {
		return;
	}
	$chk = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE 'geo_scope'");
	if ($chk && mysqli_num_rows($chk) === 0) {
		mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN geo_scope VARCHAR(20) NOT NULL DEFAULT 'domestic'");
	}
}

function ew_geo_scope_sql($conn, $scope)
{
	return "'" . mysqli_real_escape_string($conn, $scope === 'international' ? 'international' : 'domestic') . "'";
}

function ew_geo_scope_where($conn, $alias = '')
{
	$prefix = $alias !== '' ? $alias . '.' : '';
	return $prefix . 'geo_scope=' . ew_geo_scope_sql($conn, ew_geo_scope_from_request());
}

function ew_geo_scope_hidden_input()
{
	return '<input type="hidden" name="geo_scope" value="' . htmlspecialchars(ew_geo_scope_from_request(), ENT_QUOTES, 'UTF-8') . '">';
}

function ew_geo_scope_tabs_html($page)
{
	$scope = ew_geo_scope_from_request();
	$dom_on = $scope === 'domestic' ? ' is-active' : '';
	$int_on = $scope === 'international' ? ' is-active' : '';
	$page = htmlspecialchars($page, ENT_QUOTES, 'UTF-8');
	return '<div class="ew-geo-tabs" role="tablist">'
		. '<a class="ew-geo-tab' . $dom_on . '" href="' . $page . '?scope=domestic">Domestic</a>'
		. '<a class="ew-geo-tab' . $int_on . '" href="' . $page . '?scope=international">International</a>'
		. '</div>';
}

function ew_geo_scope_tabs_css()
{
	return '
		.ew-geo-tabs {
			display: flex;
			align-items: stretch;
			justify-content: flex-start;
			gap: 4px;
			width: 100%;
			margin: 0;
			padding: 0 24px;
			background: #fff;
			border-bottom: 1px solid #E9ECF0;
			box-sizing: border-box;
		}
		.ew-geo-tab {
			position: relative;
			display: inline-flex;
			align-items: center;
			padding: 14px 14px 12px;
			font-size: 14px;
			font-weight: 600;
			line-height: 1.2;
			color: #64748B;
			text-decoration: none;
			background: transparent;
			border: 0;
			margin-bottom: -1px;
		}
		.ew-geo-tab:hover { color: #06416F; text-decoration: none; }
		.ew-geo-tab.is-active,
		.ew-geo-tab.is-active:hover { color: #06416F; background: transparent; }
		.ew-geo-tab.is-active::after {
			content: "";
			position: absolute;
			left: 10px;
			right: 10px;
			bottom: 0;
			height: 2px;
			background: #06416F;
			border-radius: 2px 2px 0 0;
		}
		.ew-page-v2 .ew-card > .ew-geo-tabs + .ew-card-toolbar { padding-top: 16px; }
	';
}
