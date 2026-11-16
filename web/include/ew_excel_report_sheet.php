<?php
/**
 * Shared Excel look for MIS report exports.
 * Keep a normal Excel sheet (white cells + gridlines).
 * Report colors are applied only on the used title/header/data range by each export.
 * No freeze pane — avoids Excel's gray split line across empty columns.
 */

if (!function_exists('ew_excel_apply_report_sheet_look')) {
	/**
	 * Call after the sheet is created. Does not paint unused columns/rows.
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @param string $lastCol Unused (kept for call-site compatibility)
	 * @param int $dataRowCount Unused (kept for call-site compatibility)
	 * @return array Empty canvas metadata (compat with older call sites)
	 */
	function ew_excel_apply_report_sheet_look($spreadsheet, $sheet, $lastCol = 'A', $dataRowCount = 0)
	{
		$sheet->setShowGridlines(true);
		return array(
			'canvasLastCol' => '',
			'canvasLastRow' => 0,
			'sheetBgStyle' => array(),
			'afterDataCol' => '',
		);
	}
}

if (!function_exists('ew_excel_fill_rows_below_report')) {
	/** No-op: do not paint empty rows/columns beyond the report. */
	function ew_excel_fill_rows_below_report($sheet, $lastWrittenRow, $canvas)
	{
		// Intentionally empty — unused sheet area stays default Excel.
	}
}
