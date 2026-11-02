/**
 * RBAC preview catalog — sourced from web/include/menu.php $page_module_map + menu labels.
 * Design-only; not loaded by production app.
 */
var EW_RBAC_MODULE_NAMES = {
	dashboard: "Dashboard",
	marketing: "Marketing",
	consignment: "Bookings",
	expense: "Expense",
	invoice: "Invoicing",
	mis: "MIS Reports",
	party: "Customers",
	portal: "User Portal",
	tools: "Tools & Users",
	setup: "General Masters"
};

/** page, module, label, menu (sidebar link), superOnly */
var EW_RBAC_PAGES = [
	["dashboard.php", "dashboard", "Operations Dashboard", 1, 0],
	["dashboard_booking.php", "consignment", "Consignment Bookings Dashboard", 1, 0],
	["dashboard_invoice.php", "invoice", "Invoice Dashboard", 1, 0],
	["dashboard_payments.php", "invoice", "Payments Dashboard", 1, 0],
	["dashboard_pl.php", "expense", "Expenses & P&L Dashboard", 1, 0],

	["quotation_list.php", "marketing", "Single Quotation — List", 1, 0],
	["quotation.php", "marketing", "Single Quotation — Form", 0, 0],
	["quotation_pdf.php", "marketing", "Single Quotation — PDF", 0, 0],
	["quotation_all_modes_list.php", "marketing", "Multi Mode Quotation — List", 1, 0],
	["quotation_all_modes.php", "marketing", "Multi Mode Quotation — Form", 0, 0],
	["quotation_consignor_multi_dest_list.php", "marketing", "Multi Destination Quotation — List", 1, 0],
	["quotation_consignor_multi_dest.php", "marketing", "Multi Destination Quotation — Form", 0, 0],
	["quotation_destination_master_list.php", "marketing", "Quotation Masters — List", 1, 0],
	["quotation_destination_master.php", "marketing", "Quotation Masters — Form", 0, 0],

	["transactions.php", "consignment", "Book a Consignment", 1, 0],
	["transaction_list.php", "consignment", "List of Consignments", 1, 0],
	["transactions_manual.php", "consignment", "Book Manual Consignment", 1, 0],
	["track_consignment.php", "consignment", "Track Consignment", 1, 0],
	["status_sheet_list.php", "consignment", "Consignment Status Sheet — List", 1, 0],
	["status_sheet.php", "consignment", "Consignment Status Sheet", 0, 0],
	["edit_status_sheet.php", "consignment", "Edit Status Sheet", 0, 0],
	["trip_summary_list.php", "consignment", "Trip Summary Sheet — List", 1, 0],
	["trip_summary.php", "consignment", "Trip Summary Sheet", 0, 0],
	["trip_summary_print.php", "consignment", "Trip Summary — Print", 0, 0],
	["transaction_status.php", "consignment", "GCN Transit Update", 1, 0],
	["request_for_new_pickup.php", "consignment", "Request For Pickup", 1, 0],

	["expense_gcn_list.php", "expense", "Expense against GCN — List", 1, 0],
	["expense_gcn.php", "expense", "Expense against GCN — Form", 0, 0],
	["expense_general_list.php", "expense", "General Expense — List", 1, 0],
	["expense_general.php", "expense", "General Expense — Form", 0, 0],

	["proforma_invoice_list.php", "invoice", "Proforma Invoice — List", 1, 0],
	["create_proforma_invoice.php", "invoice", "Proforma Invoice — Create", 0, 0],
	["invoice_list.php", "invoice", "Tax Invoice — List", 1, 0],
	["create_invoice.php", "invoice", "Tax Invoice — Create", 0, 0],
	["pending_payments.php", "invoice", "Bill Wise (Pending Payments)", 1, 0],
	["credit_note_list.php", "invoice", "Credit Note — List", 1, 0],
	["create_credit_note.php", "invoice", "Credit Note — Create", 0, 0],
	["receipt_list.php", "invoice", "Payment Receipt — List", 1, 0],
	["create_receipt.php", "invoice", "Payment Receipt — Create", 0, 0],
	["gst_tax_master.php", "invoice", "GST Tax Master", 1, 0],
	["gst_tax_form.php", "invoice", "GST Tax Master — Form", 0, 0],

	["consignment_report.php", "mis", "Booking Status Report", 1, 0],
	["client_arrival_report.php", "mis", "Arrival Report", 1, 0],
	["cargo_booking_report.php", "mis", "Cargo Booking Report", 1, 0],
	["gst_tax_report.php", "mis", "GST Tax Report", 1, 0],
	["invoice_register.php", "mis", "Invoice Register", 1, 0],
	["client_payment_transactions.php", "mis", "Payment History", 1, 0],

	["client_list.php", "party", "Customer — List", 1, 0],
	["client.php", "party", "Customer — Form", 0, 0],
	["client_branch_list.php", "party", "Customer Branch — List", 1, 0],
	["client_branch.php", "party", "Customer Branch — Form", 0, 0],
	["customer_mapping.php", "party", "Customer Mapping — List", 1, 0],
	["customer_mapping_form.php", "party", "Customer Mapping — Form", 0, 0],
	["vendor_list.php", "party", "Vendor — List", 1, 0],
	["vendor.php", "party", "Vendor — Form", 0, 0],

	["user-draftconsignment.php", "portal", "Draft Consignment List", 1, 0],
	["user-inquiry.php", "portal", "User Inquiry List", 1, 0],
	["user-approval.php", "portal", "User Approval", 1, 0],
	["user-requestpickup-list.php", "portal", "Request For Pickup List", 1, 0],
	["user_ftl_list.php", "portal", "Pending FTL Quotation", 1, 0],
	["pod_list.php", "portal", "Proof of Delivery — List", 1, 0],
	["pod_master.php", "portal", "Upload Proof of Delivery", 1, 0],
	["edit_pod_list.php", "portal", "Edit POD List", 0, 0],
	["user_registration_request.php", "portal", "Registration Request", 1, 0],
	["consigner_payment_list.php", "portal", "Client Charges — List", 1, 0],
	["consigner_payment_form.php", "portal", "Client Charges — Form", 0, 0],
	["rate_calc_list.php", "portal", "Rate List", 1, 0],
	["rate_calculator_form.php", "portal", "Rate Calculator — Form", 0, 0],
	["expected_delivery_list.php", "portal", "Expected Delivery — List", 1, 0],
	["expected_delivery_form.php", "portal", "Expected Delivery — Form", 0, 0],

	["qrcode_master.php", "tools", "Print QR Code", 1, 0],
	["bulkmail.php", "tools", "Bulk Email", 1, 0],
	["user_list.php", "tools", "Users — List", 1, 0],
	["users.php", "tools", "Users — Form", 0, 0],
	["role_permissions.php", "tools", "Role Permissions Matrix", 1, 1],
	["database_backup.php", "tools", "Database Backup", 1, 1],

	["company.php", "setup", "Company Details", 1, 0],
	["company_bank.php", "setup", "Bank Details — List", 1, 0],
	["company_bank_form.php", "setup", "Bank Details — Form", 0, 0],
	["branch.php", "setup", "Branch Master", 1, 0],
	["state.php", "setup", "State Master", 1, 0],
	["city.php", "setup", "City Master", 1, 0],
	["country.php", "setup", "Country Master", 1, 0],
	["hub.php", "setup", "Warehouse / Hub Master", 1, 0],
	["package_type.php", "setup", "Package Type Master", 1, 0],
	["mode_of_transportation.php", "setup", "Mode of Transport Master", 1, 0],
	["consignment_mode.php", "setup", "Consignment Mode Master", 1, 0],
	["vehicle_list.php", "setup", "EW360 Vehicles — List", 1, 0],
	["vehicle.php", "setup", "EW360 Vehicles — Form", 0, 0],
	["vehicle_type_master.php", "setup", "Vehicle Type Master", 1, 0],
	["vehicle_type.php", "setup", "Vehicle Type — Form", 0, 0],
	["cfs_master.php", "setup", "CFS Master — List", 1, 0],
	["cfs.php", "setup", "CFS Master — Form", 0, 0],
	["train.php", "setup", "Train Master", 1, 0],
	["flight.php", "setup", "Airlines Master", 1, 0],
	["expense_group_master.php", "setup", "Expense Group GCN Master", 1, 0],
	["expense_type_master.php", "setup", "Expense Type GCN Master", 1, 0],
	["expense_type_form.php", "setup", "Expense Type GCN — Form", 0, 0]
];

(function buildCatalog() {
	var byMod = {};
	EW_RBAC_PAGES.forEach(function (row) {
		var page = row[0], module = row[1], label = row[2], menu = row[3], superOnly = row[4];
		if (!byMod[module]) byMod[module] = [];
		byMod[module].push({
			code: page,
			page: page,
			module: module,
			label: label,
			menu: !!menu,
			superOnly: !!superOnly
		});
	});
	var order = ["dashboard", "marketing", "consignment", "invoice", "expense", "mis", "party", "portal", "tools", "setup"];
	window.EW_RBAC_CATALOG = order.filter(function (c) { return byMod[c]; }).map(function (code) {
		return {
			code: code,
			name: EW_RBAC_MODULE_NAMES[code] || code,
			screens: byMod[code]
		};
	});
})();

/** Module-level defaults [view, create, edit, delete] per role — preview seed only */
window.EW_RBAC_DEFAULTS = {
	modules: {
		AD: {
			dashboard: [1, 0, 0, 0],
			marketing: [1, 1, 1, 0],
			consignment: [1, 1, 1, 0],
			expense: [0, 0, 0, 0],
			invoice: [1, 1, 1, 0],
			mis: [1, 0, 0, 0],
			party: [1, 1, 1, 0],
			portal: [0, 0, 0, 0],
			tools: [0, 0, 0, 0],
			setup: [0, 0, 0, 0]
		},
		EX: {
			dashboard: [1, 0, 0, 0],
			marketing: [1, 1, 1, 0],
			consignment: [1, 1, 1, 0],
			invoice: [0, 0, 0, 0],
			mis: [1, 0, 0, 0],
			party: [1, 1, 1, 0],
			portal: [1, 1, 1, 0],
			tools: [0, 0, 0, 0],
			setup: [0, 0, 0, 0],
			expense: [0, 0, 0, 0]
		},
		DR: {
			dashboard: [1, 0, 0, 0],
			consignment: [1, 0, 1, 0],
			portal: [1, 0, 1, 0]
		},
		CL: {
			dashboard: [1, 0, 0, 0],
			consignment: [1, 1, 0, 0],
			portal: [1, 0, 0, 0],
			mis: [1, 0, 0, 0],
			invoice: [1, 0, 0, 0]
		}
	}
};
