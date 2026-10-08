<?php

/**
 * Browser tabs cannot show a site favicon when the URL returns raw application/pdf.
 * Wrap inline viewing in a minimal HTML shell (favicon + iframe); stream PDF with embed=1.
 *
 * iPad / tablet Safari mis-centers PDFs inside iframes — those clients open embed=1 directly.
 */

function ew_pdf_stream_requested()
{
	if (isset($_GET['embed']) && (string) $_GET['embed'] === '1') {
		return true;
	}
	if (isset($_GET['download']) && (string) $_GET['download'] === '1') {
		return true;
	}
	return false;
}

function ew_pdf_favicon_href()
{
	if (defined('site_path') && trim((string) site_path) !== '') {
		return rtrim((string) site_path, '/') . '/images/elw_360_32_32-1.png';
	}
	return 'images/elw_360_32_32-1.png';
}

function ew_pdf_stream_url_from_request()
{
	$params = $_GET;
	$params['embed'] = '1';
	unset($params['download']);
	$path = isset($_SERVER['REQUEST_URI']) ? strtok((string) $_SERVER['REQUEST_URI'], '?') : (string) ($_SERVER['SCRIPT_NAME'] ?? '');
	if ($path === '') {
		$path = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
	}

	return $path . '?' . http_build_query($params);
}

/**
 * Phones, tablets, and iPadOS (desktop UA) — inline PDF in the top window, not an iframe.
 */
function ew_pdf_client_prefers_direct_pdf()
{
	$ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
	if ($ua === '') {
		return false;
	}
	if (preg_match('/iPhone|iPod|iPad|Android|webOS|BlackBerry|IEMobile|Opera Mini|Mobile Safari/i', $ua)) {
		return true;
	}
	if (isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']) && trim((string) $_SERVER['HTTP_SEC_CH_UA_MOBILE']) === '?1') {
		return true;
	}
	// Android tablets (no "Mobile" in UA)
	if (preg_match('/Android/i', $ua) && !preg_match('/Mobile/i', $ua)) {
		return true;
	}

	return false;
}

function ew_pdf_browser_view_shell($tab_title = 'Document PDF')
{
	$tab_title = trim((string) $tab_title);
	if ($tab_title === '') {
		$tab_title = 'Document PDF';
	}
	$favicon = ew_pdf_favicon_href();
	$streamUrl = ew_pdf_stream_url_from_request();
	$safeTitle = htmlspecialchars($tab_title, ENT_QUOTES, 'UTF-8');
	$safeFavicon = htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8');
	$safeStream = htmlspecialchars($streamUrl, ENT_QUOTES, 'UTF-8');
	$streamJson = json_encode($streamUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

	header('Content-Type: text/html; charset=UTF-8');
	header('X-Frame-Options: SAMEORIGIN');

	echo '<!DOCTYPE html><html lang="en"><head>'
		. '<meta charset="UTF-8">'
		. '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">'
		. '<link rel="shortcut icon" href="' . $safeFavicon . '">'
		. '<link rel="icon" type="image/png" href="' . $safeFavicon . '">'
		. '<title>' . $safeTitle . ' — EliteWave360</title>'
		. '<style>'
		. 'html,body{margin:0;height:100%;width:100%;overflow:hidden;background:#525659;}'
		. '.ew-pdf-viewport{position:fixed;inset:0;display:flex;justify-content:center;align-items:stretch;}'
		. '.ew-pdf-viewport iframe{display:block;width:100%;max-width:100%;height:100%;border:0;margin:0 auto;}'
		. '</style>'
		. '<script>(function(){'
		. 'var u=' . $streamJson . ';'
		. 'var ua=navigator.userAgent||"";'
		. 'var tablet=/iPad|iPhone|iPod|Android/i.test(ua);'
		. 'var ipadOs=navigator.maxTouchPoints>1&&/Macintosh/i.test(ua);'
		. 'var coarse=window.matchMedia&&window.matchMedia("(pointer:coarse)").matches;'
		. 'var narrow=window.innerWidth>0&&window.innerWidth<=1366;'
		. 'if(tablet||ipadOs||(coarse&&narrow)){window.location.replace(u);}'
		. '})();</script>'
		. '</head><body>'
		. '<div class="ew-pdf-viewport"><iframe src="' . $safeStream . '" title="' . $safeTitle . '"></iframe></div>'
		. '</body></html>';
	exit;
}

function ew_pdf_maybe_browser_shell($tab_title = 'Document PDF')
{
	if (ew_pdf_stream_requested()) {
		return;
	}
	if (ew_pdf_client_prefers_direct_pdf()) {
		header('Location: ' . ew_pdf_stream_url_from_request(), true, 302);
		exit;
	}
	ew_pdf_browser_view_shell($tab_title);
}

function ew_pdf_url_with_embed($url)
{
	$url = trim((string) $url);
	if ($url === '' || strpos($url, 'embed=1') !== false) {
		return $url;
	}
	$sep = (strpos($url, '?') !== false) ? '&' : '?';

	return $url . $sep . 'embed=1';
}
