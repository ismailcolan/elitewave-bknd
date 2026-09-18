<?php
/**
 * Elite Wave skeleton loader.
 * Overlay is injected from header.php for .loading-page / .form-data-saving.
 */
function ew_skeleton_card_inner()
{
	return '<div class="ew-skel-card" role="status" aria-label="Loading">'
		. '<div class="ew-skel ew-skel-kicker"></div>'
		. '<span class="ew-skel-line lg"></span>'
		. '<span class="ew-skel-line md"></span>'
		. '<span class="ew-skel-line sm"></span>'
		. '<div class="ew-skel-rows">'
		. '<div class="ew-skel ew-skel-tile"></div>'
		. '<div class="ew-skel ew-skel-tile"></div>'
		. '<div class="ew-skel ew-skel-tile"></div>'
		. '</div></div>';
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
