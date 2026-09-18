<?php
require_once("include/connect.php");
require_once("include/function.php");
$mapping_overview = ew_customer_mapping_overview($conn);
?>
<!DOCTYPE html>
<html>
<head>
	<?php include("include/title.php"); ?>
	<?php include("include/css_js.php"); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
</head>
<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php
			require_once("include/header.php");
			require_once("include/menu.php");
			?>
		</div>
		<div class="container-fluid main-content new_dpt_bottom">
			<div class="row">
				<div class="col-md-12">
					<div class="ew-page-v2 ew-page-v2--mapping">
						<div class="ew-page-head">
							<div class="ew-page-head-left">
								<h1 class="ew-page-title">Customer Mapping</h1>
							</div>
						</div>
						<div class="ew-card ew-erp-list">
							<div class="ew-card-toolbar">
								<h2>Mapped Customers</h2>
								<div class="ew-toolbar-right">
									<div class="ew-list-toolbar__tools"></div>
									<a href="customer_mapping_form.php" class="ew-btn-v2 ew-btn-v2-primary">Create <i class="fa fa-plus"></i></a>
								</div>
							</div>
							<div class="ew-table-wrap widget-content padded clearfix new_dept">
								<table class="table table-bordered table-striped" id="dataTable1">
									<thead>
										<tr>
											<th class="table-title" style="width:8%">S.No</th>
											<th class="table-title" style="width:28%">Customer</th>
											<th class="table-title">Mapped Consignees</th>
											<th class="table-title" style="width:10%">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$i = 1;
										foreach ($mapping_overview as $map) {
										?>
											<tr>
												<td class="text-center"><?php echo $i; ?></td>
												<td><?php echo htmlspecialchars($map['customer_name']); ?></td>
												<td><?php echo htmlspecialchars($map['consignee_names']); ?></td>
												<td class="actions center-content">
													<div class="action-buttons">
														<a title="Edit" href="customer_mapping_form.php?id=<?php echo (int) $map['customer_id']; ?>" class="table-actions btn-edit"><i class="fa fa-pencil"></i></a>
													</div>
												</td>
											</tr>
										<?php
											$i++;
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php require_once("include/footer.php"); ?>
	</div>
</body>
</html>
