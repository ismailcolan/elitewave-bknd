<?php

/**
 * Newest journey step on top: status 8 → 1, then latest scan time, then sheet_id.
 *
 * @param array<int, array{status_id?:int,ts?:int,sheet_id?:int}> $items
 */
function ew_tracking_timeline_sort(array &$items)
{
    usort($items, function ($a, $b) {
        $sa = (int) ($a['status_id'] ?? 0);
        $sb = (int) ($b['status_id'] ?? 0);
        if ($sa !== $sb) {
            return $sb <=> $sa;
        }
        $ta = (int) ($a['ts'] ?? 0);
        $tb = (int) ($b['ts'] ?? 0);
        if ($ta !== $tb) {
            return $tb <=> $ta;
        }
        return (int) ($b['sheet_id'] ?? 0) <=> (int) ($a['sheet_id'] ?? 0);
    });
}

function tracking_template($status, $data)
{
    extract($data);

    $grn = '<strong style="color:#000;">' . $grn . '</strong>';
    $consignor = '<strong style="color:#000;">' . $consignor . '</strong>';
    $consignee = '<strong style="color:#000;">' . $consignee . '</strong>';
    $origin = '<strong style="color:#000;">' . $origin . '</strong>';
    $destination = '<strong style="color:#000;">' . $destination . '</strong>';
    $mode = '<strong style="color:#000;">' . $mode . '</strong>';
    $loadingHub = '<strong style="color:#000;">' . $loadingHub . '</strong>';
    $destinationHub = '<strong style="color:#000;">' . $destinationHub . '</strong>';
    $loadingSuffix = isset($loadingSuffix) ? $loadingSuffix : '';
    $destinationSuffix = isset($destinationSuffix) ? $destinationSuffix : '';
    $status = (int) $status;
    switch ($status) {

    case 1:

        return "Your consignment $grn from $consignor ($origin) to $consignee ($destination) has been booked successfully via $mode. Thank you for choosing EliteWave360 Logistics.";

    case 2:

        return "Your consignment $grn has been picked up from $origin and is on its way to $loadingHub$loadingSuffix for further processing.";

    case 3:

        return "Your consignment $grn has reached $loadingHub$loadingSuffix and has been dispatched towards $destinationHub$destinationSuffix via $mode.";

    case 4:

        return "Your consignment $grn has arrived at $destinationHub$destinationSuffix and is currently being prepared for onward transportation to $destination.";

    case 5:

        return "Good news! Your consignment $grn has reached $destinationHub$destinationSuffix and is currently undergoing final processing before delivery.";

    case 6:

        return "Your consignment $grn has arrived at $destinationHub$destinationSuffix and has been scheduled for final delivery.";

    case 7:

        return "Your consignment $grn is out for delivery and will be delivered today to $consignee at $destination.";

    case 8:

        return "Your consignment $grn from $consignor ($origin) has been delivered successfully to $consignee at $destination. Thank you for choosing EliteWave360 Logistics.";

    default:

        return "";
}
}

function tracking_hours_number($hours)
{
    $hours = (float) $hours;
    if (abs($hours - round($hours)) < 0.001) {
        return (string) (int) round($hours);
    }
    return rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.');
}

function tracking_hours_strong($hours, $suffix = ' hours')
{
    return '<strong style="color:#000;">' . tracking_hours_number($hours) . $suffix . '</strong>';
}

function tracking_offload_notice($reason)
{
    $reason = trim((string) $reason);
    $reasonHtml = $reason !== ''
        ? '<strong style="color:#9a3412;">' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</strong>'
        : 'an operational issue';

    return '<span style="display:block;margin-top:8px;padding:8px 10px;background:#fff7ed;border-left:3px solid #c2410c;border-radius:6px;color:#7c2d12;">'
        . '<strong style="display:block;color:#c2410c;margin-bottom:4px;">Offload – Shipment Update</strong>'
        . 'Kindly accept our apologies for the temporary interruption in transit. The consignment has been held/offloaded due to ' . $reasonHtml . '. Our team is actively working on the matter and making every effort to resume the movement at the earliest.<br>'
        . 'We regret the inconvenience and appreciate your patience and understanding.'
        . '</span>';
}

function tracking_breakdown_notice($reason)
{
    return '<span style="display:block;margin-top:8px;padding:8px 10px;background:#fff7ed;border-left:3px solid #c2410c;border-radius:6px;color:#7c2d12;">'
        . '<strong style="display:block;color:#c2410c;margin-bottom:4px;">Transit Interruption – Shipment Update</strong>'
        . 'Kindly accept our sincere apologies for the temporary interruption in transit. The vehicle has been halted due to a breakdown, resulting in a delay in movement.<br>'
        . 'Our team is actively working on the issue and taking all necessary measures for faster recovery and to resume the journey at the earliest.<br>'
        . 'We regret the inconvenience caused and appreciate your patience and understanding.'
        . '</span>';
}

function tracking_resume_notice()
{
    return '<span style="display:block;margin-top:8px;padding:8px 10px;background:#f0fdf4;border-left:3px solid #15803d;border-radius:6px;color:#14532d;">'
        . '<strong style="display:block;color:#15803d;margin-bottom:4px;">Transit Resumed – Consignment Update</strong>'
        . 'We are pleased to inform you that the issue has been resolved and the consignment has resumed its onward movement.<br>'
        . 'We are closely monitoring the shipment and will ensure every possible effort is made for timely delivery. Thank you for your patience and understanding.'
        . '</span>';
}

function tracking_onload_notice($carrier)
{
    $carrier = ($carrier === 'train') ? 'train' : 'flight';

    return '<span style="display:block;margin-top:8px;padding:8px 10px;background:#f0fdf4;border-left:3px solid #15803d;border-radius:6px;color:#14532d;">'
        . '<strong style="display:block;color:#15803d;margin-bottom:4px;">Onload – Shipment Update</strong>'
        . 'This consignment has been onloaded on the next ' . $carrier . ' and is moving again.'
        . '</span>';
}

function tracking_breakdown_event_notes($events)
{
    $html = '';
    if (!is_array($events)) {
        return $html;
    }
    foreach ($events as $event) {
        $html .= tracking_breakdown_notice(isset($event['reason']) ? $event['reason'] : '');
        if (empty($event['open'])) {
            $html .= tracking_resume_notice();
        }
    }
    return $html;
}

function tracking_offload_event_notes($events, $carrier)
{
    $html = '';
    if (!is_array($events)) {
        return $html;
    }
    foreach ($events as $event) {
        $html .= tracking_offload_notice(isset($event['reason']) ? $event['reason'] : '');
        if (empty($event['open'])) {
            $html .= tracking_onload_notice($carrier);
        }
    }
    return $html;
}

/**
 * Road freight messages. Hours are entered at Picked Up.
 * Step = total / 6. Transit-1 through Out for Delivery each subtract one step.
 */
function tracking_template_road($status, $data)
{
    extract($data);

    $grn = '<strong style="color:#000;">' . $grn . '</strong>';
    $origin = '<strong style="color:#000;">' . $origin . '</strong>';
    $destination = '<strong style="color:#000;">' . $destination . '</strong>';
    $mode = '<strong style="color:#000;">' . $mode . '</strong>';
    $total = isset($totalHours) ? round((float) $totalHours) : 0;
    $step = $total > 0 ? (int) round($total / 6) : 0;
    $cuts = 0;
    $status = (int) $status;
    if ($status >= 3 && $status <= 7) {
        $cuts = $status - 2;
    }
    $delay = isset($breakdownDelay) ? (int) round((float) $breakdownDelay) : 0;
    $resumeDelay = isset($breakdownResumeDelay) ? (int) round((float) $breakdownResumeDelay) : 0;
    $breakdownOpen = !empty($breakdownOpen);
    $remaining = $total > 0 ? (int) round($total + $delay - ($cuts * $step)) : 0;
    if ($remaining < 0) {
        $remaining = 0;
    }
    $hourLine = '';
    if (!$breakdownOpen && $total > 0 && $status >= 3 && $status <= 7) {
        $reach = ($status >= 6) ? 'the consignee' : 'its destination';
        $hourLine = ' ' . tracking_hours_strong($step) . ' have been reduced from the transit time, and your consignment will reach ' . $reach . ' within ' . tracking_hours_strong($remaining) . '.';
    }

    switch ($status) {

    case 1:
        return "Your consignment $grn has been booked from $origin and is scheduled for transportation to $destination via $mode. Your consignment will reach its destination within the Scheduled Time.";

    case 2:
        if ($breakdownOpen) {
            $within = '';
        } else {
            $within = $total > 0
                ? ' Your consignment will reach its destination within ' . tracking_hours_strong($remaining) . '.'
                : '';
        }
        return "Your consignment $grn has been picked up from $origin and is now in transit towards $destination via $mode.$within";

    case 3:
        return "Your consignment $grn is currently in transit from the origin state towards the destination.$hourLine";

    case 4:
        return "Your consignment $grn is currently in transit towards the destination state and is being prepared for onward transportation.$hourLine";

    case 5:
        return "Good news! Your consignment $grn is progressing towards the destination and is currently undergoing transit processing.$hourLine";

    case 6:
        return "Your consignment $grn has arrived at the destination hub and has been scheduled for final delivery.$hourLine";

    case 7:
        return "Your consignment $grn is out for delivery and is currently on its way to the consignee.$hourLine";

    case 8:
        if ($total > 0 && $delay > 0) {
            $cycle = ' The full ' . tracking_hours_strong($total, '-hour') . ' transit cycle has been completed, including a breakdown delay of ' . tracking_hours_strong($delay) . '.';
        } else {
            $cycle = $total > 0
                ? ' The full ' . tracking_hours_strong($total, '-hour') . ' transit cycle has been completed.'
                : '';
        }
        return "Your consignment $grn has been successfully delivered to the consignee at $destination.$cycle Thank you for choosing EliteWave360 Logistics.";

    default:
        return "";
    }
}

function ew_tracking_hold_item($ts, $status_at, $title, $remarks)
{
    $ts = (int) $ts;

    return array(
        'ts' => $ts,
        'status_id' => (int) $status_at,
        'sheet_id' => 0,
        'title' => $title,
        'date' => $ts > 0 ? date('d-m-Y', $ts) : '',
        'time' => $ts > 0 ? date('H:i:s', $ts) : '',
        'remarks' => $remarks,
        'is_partial' => false,
    );
}

/** Separate timeline rows for breakdown, resume, offload, and onload. */
function ew_tracking_hold_timeline($conn, $grn_no, $carrier = '')
{
    $items = array();
    $grn_no = mysqli_real_escape_string($conn, trim((string) $grn_no));
    if ($grn_no === '') {
        return $items;
    }
    $carrier = ($carrier === 'train') ? 'train' : 'flight';

    if (function_exists('ew_road_breakdown_ensure')) {
        ew_road_breakdown_ensure($conn);
        $q = mysqli_query($conn, "SELECT reason, status_at, created_at, resumed_at, is_open FROM consignment_road_breakdown WHERE grn_no='$grn_no' ORDER BY id ASC");
        if ($q) {
            while ($row = mysqli_fetch_assoc($q)) {
                $at = (int) ($row['status_at'] ?? 0);
                $created = strtotime((string) ($row['created_at'] ?? ''));
                if ($created) {
                    $items[] = ew_tracking_hold_item($created, $at, 'Transit Interruption – Shipment Update', tracking_breakdown_notice(''));
                }
                if ((int) ($row['is_open'] ?? 0) === 0) {
                    $resumed = strtotime((string) ($row['resumed_at'] ?? ''));
                    if ($resumed) {
                        $items[] = ew_tracking_hold_item($resumed, $at, 'Transit Resumed – Consignment Update', tracking_resume_notice());
                    }
                }
            }
        }
    }

    if (function_exists('ew_air_offload_ensure')) {
        ew_air_offload_ensure($conn);
        $q = mysqli_query($conn, "SELECT reason, status_at, created_at, cleared_at, is_open FROM consignment_air_offload WHERE grn_no='$grn_no' ORDER BY id ASC");
        if ($q) {
            while ($row = mysqli_fetch_assoc($q)) {
                $at = (int) ($row['status_at'] ?? 0);
                $created = strtotime((string) ($row['created_at'] ?? ''));
                if ($created) {
                    $items[] = ew_tracking_hold_item($created, $at, 'Offload – Shipment Update', tracking_offload_notice((string) ($row['reason'] ?? '')));
                }
                if ((int) ($row['is_open'] ?? 0) === 0) {
                    $cleared = strtotime((string) ($row['cleared_at'] ?? ''));
                    if ($cleared) {
                        $items[] = ew_tracking_hold_item($cleared, $at, 'Onload – Shipment Update', tracking_onload_notice($carrier));
                    }
                }
            }
        }
    }

    return $items;
}
