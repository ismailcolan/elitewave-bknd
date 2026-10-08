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
