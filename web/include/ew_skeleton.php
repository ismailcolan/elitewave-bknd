<?php
/**
 * Elite Wave skeleton loader.
 * Overlay is injected from header.php for .loading-page / .form-data-saving.
 */
function ew_logo_loader_html()
{
	return '<div class="ew-logo-loader" role="status" aria-live="polite" aria-label="Loading">'
		. '<div class="ew-logo-loader-ring">'
		. '<img src="images/elite-loader.png?v=3" alt="EliteWave360">'
		. '</div>'
		. '<div class="ew-logo-loader-text">Please wait</div>'
		. '</div>';
}

function ew_skeleton_card_inner()
{
	return ew_logo_loader_html();
}

function ew_skeleton_dashboard()
{
	$html = '<div class="ew-skel-page" role="status" aria-label="Loading dashboard">';
	$html .= '<div class="ew-skel-flow">';
	for ($i = 0; $i < 6; $i++) {
		$html .= '<div class="ew-skel ew-skel-block"></div>';
	}
	$html .= '</div>';
	$html .= '<div class="ew-skel-grid2">';
	$html .= '<div class="ew-skel ew-skel-block ew-skel-chart"></div>';
	$html .= '<div class="ew-skel ew-skel-block ew-skel-chart"></div>';
	$html .= '</div>';
	$html .= '<div class="ew-skel-grid3">';
	$html .= '<div class="ew-skel ew-skel-block ew-skel-chart sm"></div>';
	$html .= '<div class="ew-skel ew-skel-block ew-skel-chart sm"></div>';
	$html .= '<div class="ew-skel ew-skel-block ew-skel-chart sm"></div>';
	$html .= '</div></div>';
	return $html;
}
