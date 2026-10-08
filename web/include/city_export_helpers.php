<?php

require_once __DIR__ . '/../PhpSpreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @return array<int, string>
 */
function ew_city_master_export_headers()
{
    return array(
        'City Code',
        'City Name',
        'State',
        'Via City',
        'Railway Station',
        'Airport',
        'Loading/Unloading Point',
        'Warehouse',
        'Port',
        'Created At',
        'Updated At',
        'Status',
    );
}

/**
 * Export city master to xlsx (no internal IDs).
 *
 * @return array{count:int,path?:string}
 */
function ew_city_master_export_xlsx($conn, $savePath = null)
{
    $sql = 'SELECT c.city_code, c.city_name, c.via_city, c.railway_station, c.airport, c.unloading_point, '
        . 'c.warehouse, c.port, c.created_at, c.updated_at, c.status, s.state_name '
        . 'FROM city c '
        . 'LEFT JOIN state s ON s.state_id = c.state '
        . 'ORDER BY c.city_name ASC';

    $res = mysqli_query($conn, $sql);
    if (!$res) {
        throw new RuntimeException(mysqli_error($conn));
    }

    $headers = ew_city_master_export_headers();
    $sheet = new Spreadsheet();
    $ws = $sheet->getActiveSheet();
    $ws->setTitle('City Master');

    foreach ($headers as $i => $h) {
        $ws->setCellValueByColumnAndRow($i + 1, 1, $h);
    }

    $rowNum = 2;
    while ($row = mysqli_fetch_assoc($res)) {
        $statusLabel = ((int) ($row['status'] ?? 0) === 0) ? 'Active' : 'Inactive';
        $values = array(
            $row['city_code'] ?? '',
            $row['city_name'] ?? '',
            $row['state_name'] ?? '',
            $row['via_city'] ?? '',
            $row['railway_station'] ?? '',
            $row['airport'] ?? '',
            $row['unloading_point'] ?? '',
            $row['warehouse'] ?? '',
            $row['port'] ?? '',
            $row['created_at'] ?? '',
            $row['updated_at'] ?? '',
            $statusLabel,
        );
        foreach ($values as $col => $val) {
            $ws->setCellValueByColumnAndRow($col + 1, $rowNum, $val);
        }
        $rowNum++;
    }

    $count = $rowNum - 2;
    $writer = new Xlsx($sheet);

    if ($savePath !== null) {
        $writer->save($savePath);
        return array('count' => $count, 'path' => $savePath);
    }

    $tmp = tempnam(sys_get_temp_dir(), 'ew_city_xlsx_');
    if ($tmp === false) {
        throw new RuntimeException('Could not create temp file.');
    }
    $writer->save($tmp);
    return array('count' => $count, 'path' => $tmp);
}

function ew_city_master_export_filename()
{
    return 'city_master_' . date('Y-m-d_His') . '.xlsx';
}
