<?php
/**
 * City name → [longitude, latitude] for Live Tracking map.
 * Keys are lowercase trimmed city_name values from `city`.
 */

function ew_city_coords_map()
{
	static $map = null;
	if ($map !== null) {
		return $map;
	}
	$map = array(
		'agra' => array(78.0081, 27.1767),
		'ahamedabad' => array(72.5714, 23.0225),
		'ahmedabad' => array(72.5714, 23.0225),
		'aluva' => array(76.3550, 10.1004),
		'alwar' => array(76.6346, 27.5530),
		'ambala' => array(76.7767, 30.3782),
		'ambur' => array(78.7166, 12.7904),
		'amritsar' => array(74.8723, 31.6340),
		'aurangabad' => array(75.3433, 19.8762),
		'baddi' => array(76.7914, 30.9578),
		'bahadurgarh' => array(76.9356, 28.6928),
		'bangarapet' => array(78.2270, 12.9910),
		'banglore' => array(77.5946, 12.9716),
		'bengaluru' => array(77.5946, 12.9716),
		'banthar' => array(80.5160, 26.7600),
		'bawal' => array(76.5830, 28.0830),
		'bharuch' => array(72.9959, 21.7051),
		'bhavnagar' => array(72.1519, 21.7645),
		'bhilwara' => array(74.6399, 25.3478),
		'bhiwadi' => array(76.8720, 28.2100),
		'bhiwandi' => array(73.0480, 19.2813),
		'bhopal' => array(77.4126, 23.2599),
		'bilaspur' => array(82.1409, 22.0797),
		'calicut' => array(75.7804, 11.2588),
		'chandigarh' => array(76.7794, 30.7333),
		'charoti' => array(72.9500, 20.0500),
		'chennai' => array(80.2707, 13.0827),
		'cochin' => array(76.2673, 9.9312),
		'kochi (cochin)' => array(76.2673, 9.9312),
		'coimbatore' => array(76.9558, 11.0168),
		'dahej' => array(72.5800, 21.7100),
		'daman' => array(72.8328, 20.3974),
		'darbhanga' => array(85.8918, 26.1542),
		'dehradun' => array(78.0322, 30.3165),
		'deoria' => array(83.7870, 26.5024),
		'dewas' => array(76.0508, 22.9676),
		'dharuhera' => array(76.7900, 28.2050),
		'dholpur' => array(77.8800, 26.7000),
		'dimapur' => array(93.7300, 25.9100),
		'dindugal' => array(78.0766, 10.3673),
		'doddaballapur' => array(77.5430, 13.2920),
		'ernakulam' => array(76.2999, 9.9816),
		'erode' => array(77.7172, 11.3410),
		'faridabad' => array(77.3178, 28.4089),
		'faridkot' => array(74.7530, 30.6760),
		'gandhinagar' => array(72.6369, 23.2156),
		'ghaziabad' => array(77.4538, 28.6692),
		'goa' => array(73.8567, 15.2993),
		'gorakhpur' => array(83.3732, 26.7606),
		'gudiyatham' => array(78.8730, 12.9440),
		'gulbarga' => array(76.8343, 17.3297),
		'guntur' => array(80.4365, 16.3067),
		'gurgaon' => array(77.0266, 28.4595),
		'gurugram' => array(77.0266, 28.4595),
		'guwahati' => array(91.7362, 26.1445),
		'gwalior' => array(78.1828, 26.2183),
		'haridwar' => array(78.1642, 29.9457),
		'hooghly' => array(88.3900, 22.9000),
		'hosur' => array(77.8253, 12.7409),
		'hubli' => array(75.1240, 15.3647),
		'hyderabad' => array(78.4867, 17.3850),
		'imphal' => array(93.9063, 24.8170),
		'imt manesar' => array(76.9300, 28.3500),
		'indore' => array(75.8577, 22.7196),
		'jabalpur' => array(79.9864, 23.1815),
		'jaipur' => array(75.7873, 26.9124),
		'jalandhar' => array(75.5762, 31.3260),
		'jammu' => array(74.8570, 32.7266),
		'jamnagar' => array(70.0577, 22.4707),
		'jamshedpur' => array(86.1840, 22.8046),
		'jhajjar' => array(76.6565, 28.6063),
		'jhansi' => array(78.5696, 25.4484),
		'jodhpur' => array(73.0243, 26.2389),
		'kakinada' => array(82.2475, 16.9891),
		'kanchipuram' => array(79.7036, 12.8342),
		'kanpur' => array(80.3319, 26.4499),
		'karimnagar' => array(79.1288, 18.4386),
		'karnal' => array(76.9905, 29.6857),
		'karur' => array(78.0766, 10.9601),
		'kashmir' => array(74.7973, 34.0837),
		'kolkata' => array(88.3639, 22.5726),
		'krishna district' => array(80.7200, 16.4000),
		'kundli' => array(77.0200, 28.9900),
		'kushinagar' => array(83.8900, 26.7400),
		'lucknow' => array(80.9462, 26.8467),
		'ludhiana' => array(75.8573, 30.9010),
		'madurai' => array(78.1198, 9.9252),
		'malappuram' => array(76.0740, 11.0410),
		'mangalore' => array(74.8560, 12.9141),
		'meerut' => array(77.7064, 28.9845),
		'melvisharam' => array(79.2800, 12.9400),
		'mewat' => array(77.0000, 28.1000),
		'miraj' => array(74.6470, 16.8300),
		'mohali' => array(76.7170, 30.7046),
		'moradabad' => array(78.7733, 28.8386),
		'morbi' => array(70.8380, 22.8170),
		'mumbai' => array(72.8777, 19.0760),
		'mundaka' => array(77.0300, 28.6800),
		'mundka' => array(77.0300, 28.6800),
		'mundra' => array(69.7210, 22.8390),
		'murthal' => array(77.0700, 29.0300),
		'mysore' => array(76.6394, 12.2958),
		'nagpur' => array(79.0882, 21.1458),
		'nalanda' => array(85.4560, 25.2000),
		'nashik' => array(73.7898, 19.9975),
		'nellore' => array(79.9865, 14.4426),
		'new delhi' => array(77.2090, 28.6139),
		'new delhi ncr' => array(77.2090, 28.6139),
		'noida' => array(77.3910, 28.5355),
		'odisha' => array(85.8245, 20.2961),
		'ooty' => array(76.6950, 11.4102),
		'oragadam' => array(79.9800, 12.8300),
		'palghar' => array(72.7600, 19.6900),
		'pallikonda' => array(78.9500, 12.9000),
		'panchkula' => array(76.8530, 30.6942),
		'panipat' => array(76.9635, 29.3909),
		'pantranagar' => array(79.4500, 29.0000),
		'parganas' => array(88.4300, 22.5700),
		'patiala' => array(76.3869, 30.3398),
		'patna' => array(85.1376, 25.5941),
		'periyapalayam' => array(79.9500, 13.3200),
		'pernambut' => array(78.7000, 12.9300),
		'petlad' => array(72.8000, 22.4700),
		'pondicherry' => array(79.8083, 11.9416),
		'puducherry' => array(79.8083, 11.9416),
		'pune' => array(73.8567, 18.5204),
		'raipur' => array(81.6296, 21.2514),
		'rajkot' => array(70.8022, 22.3039),
		'ranchi' => array(85.3096, 23.3441),
		'ranipet' => array(79.3333, 12.9224),
		'rewari' => array(76.6200, 28.2000),
		'rohatak' => array(76.6066, 28.8955),
		'rohtak' => array(76.6066, 28.8955),
		'saharanpur' => array(77.5460, 29.9680),
		'salem' => array(78.1460, 11.6643),
		'shahjahanpur' => array(79.9100, 27.8800),
		'shillong' => array(91.8800, 25.5788),
		'silvassa' => array(73.0169, 20.2760),
		'sirmour' => array(77.3000, 30.5600),
		'sirsa' => array(75.0280, 29.5330),
		'sitapura' => array(75.8500, 26.7800),
		'sivaskasi' => array(77.8000, 9.4500),
		'solinghur' => array(79.3000, 13.0800),
		'sonipat' => array(77.0151, 28.9931),
		'srinagar' => array(74.7973, 34.0837),
		'sullurpet' => array(80.0200, 13.7000),
		'surat' => array(72.8311, 21.1702),
		'tata nagar' => array(86.1840, 22.8046),
		'thane' => array(72.9781, 19.2183),
		'thiruvallur' => array(79.9100, 13.1400),
		'thodupuzha' => array(76.7200, 9.9000),
		'tirupati' => array(79.4192, 13.6288),
		'tiruppur' => array(77.3411, 11.1085),
		'trichy' => array(78.7047, 10.7905),
		'tumkur' => array(77.1000, 13.3400),
		'una' => array(76.2700, 31.4700),
		'unnao' => array(80.4900, 26.5500),
		'vadodara' => array(73.1812, 22.3072),
		'valsad' => array(72.9300, 20.6100),
		'vaniyambadi' => array(78.6190, 12.6820),
		'vasai' => array(72.8397, 19.3919),
		'vapi' => array(72.9100, 20.3700),
		'varanasi' => array(82.9739, 25.3176),
		'vellore' => array(79.1325, 12.9165),
		'vijayawada' => array(80.6480, 16.5062),
		'villupuram' => array(79.4900, 11.9400),
		'visakhapatnam (vizag)' => array(83.2185, 17.6868),
		'vizag' => array(83.2185, 17.6868),
		'walajapet' => array(79.4200, 12.9300),
		'warangal' => array(79.5941, 17.9689),
		'wardha' => array(78.6000, 20.7500),
		'zaheerabad' => array(77.6100, 17.6800),
		'zirakpur' => array(76.8200, 30.6500),
	);
	return $map;
}

function ew_city_coords_key($city_name)
{
	$key = strtolower(trim((string) $city_name));
	$key = preg_replace('/\s*\([^)]*\)/', '', $key);
	$key = trim(preg_replace('/\s+/', ' ', $key));
	return $key;
}

/** City name → state/UT label matching india-states-map.svg data-s. */
function ew_city_state_map()
{
	return array(
		'chennai' => 'Tamil Nadu',
		'ranipet' => 'Tamil Nadu',
		'ambur' => 'Tamil Nadu',
		'vellore' => 'Tamil Nadu',
		'vaniyambadi' => 'Tamil Nadu',
		'hosur' => 'Tamil Nadu',
		'bengaluru' => 'Karnataka',
		'bangalore' => 'Karnataka',
		'banglore' => 'Karnataka',
		'mumbai' => 'Maharashtra',
		'vasai' => 'Maharashtra',
		'delhi' => 'NCT of Delhi',
		'new delhi' => 'NCT of Delhi',
		'gurugram' => 'Haryana',
		'gurgaon' => 'Haryana',
		'sonipat' => 'Haryana',
		'bahadurgarh' => 'Haryana',
		'noida' => 'Uttar Pradesh',
		'kanpur' => 'Uttar Pradesh',
		'alwar' => 'Rajasthan',
		'jalandhar' => 'Punjab',
		'kolkata' => 'West Bengal',
		'pondicherry' => 'Puducherry',
		'puducherry' => 'Puducherry',
	);
}

function ew_city_state_lookup($city_name)
{
	$key = ew_city_coords_key($city_name);
	if ($key === '' || $key === '—' || $key === '-') {
		return '';
	}
	$map = ew_city_state_map();
	if (isset($map[$key])) {
		return $map[$key];
	}
	$aliases = array(
		'pondy' => 'pondicherry',
		'madras' => 'chennai',
		'bombay' => 'mumbai',
		'calcutta' => 'kolkata',
	);
	if (isset($aliases[$key]) && isset($map[$aliases[$key]])) {
		return $map[$aliases[$key]];
	}
	return '';
}

function ew_city_coords_lookup($city_name)
{
	$key = ew_city_coords_key($city_name);
	if ($key === '' || $key === '—' || $key === '-') {
		return null;
	}
	$map = ew_city_coords_map();
	if (isset($map[$key])) {
		return $map[$key];
	}
	$aliases = array(
		'bangalore' => 'bengaluru',
		'delhi' => 'new delhi',
		'pondy' => 'pondicherry',
		'calcutta' => 'kolkata',
		'bombay' => 'mumbai',
		'madras' => 'chennai',
	);
	if (isset($aliases[$key]) && isset($map[$aliases[$key]])) {
		return $map[$aliases[$key]];
	}
	return null;
}

function ew_india_states_land_markup()
{
	$file = dirname(__DIR__) . '/images/side-ops-image/india-states-map.svg';
	if (!is_readable($file)) {
		return '';
	}
	$svg = file_get_contents($file);
	if (!is_string($svg) || $svg === '') {
		return '';
	}
	if (!preg_match('/<g\b[^>]*\bid="land"[^>]*>.*<\/g>/s', $svg, $m)) {
		return '';
	}
	$group = $m[0];
	if (strpos($group, 'class="land"') === false) {
		$group = preg_replace('/<g\b/', '<g class="land"', $group, 1);
	}
	return $group;
}

function ew_haversine_km($lon1, $lat1, $lon2, $lat2)
{
	$r = 6371.0;
	$p1 = deg2rad($lat1);
	$p2 = deg2rad($lat2);
	$dp = deg2rad($lat2 - $lat1);
	$dl = deg2rad($lon2 - $lon1);
	$a = sin($dp / 2) * sin($dp / 2) + cos($p1) * cos($p2) * sin($dl / 2) * sin($dl / 2);
	$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	return round($r * $c);
}
