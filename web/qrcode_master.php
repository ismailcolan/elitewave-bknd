<?php
require_once("include/connect.php");
require_once("include/function.php");

$key = $_REQUEST['key'];
if ($key != '') {
	$client_query = "select * from client where md5(client_id)='" . $key . "'";
	$client_result = mysqli_query($conn, $client_query);
	$client_count = mysqli_num_rows($client_result);
	if ($client_count == 0) {
		header('Location:client_list.php');
	}
}
?>
<!DOCTYPE html>
<html>

<head>
	<?php include("include/title.php"); ?>
	<?php include("include/css_js.php"); ?>
	<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">

	<style>
		/* @page {
			margin-top: 3cm;
			margin-bottom: 5cm;
		} */

		/* @page :first {
  margin-top: -5cm;
} */


		th {
			padding: 4px 30px;
		}

		td {
			text-align: center;
		}

		.grid-container {
			display: grid;
			grid-template-columns: auto auto auto auto auto;
			grid-gap: 5px;
			padding: 20px;
		}

		/* @media print {
	.no-printme  {
		display: none;
	}
	.printme  {
		display: block;
	}
} */
		/* .printme {
    height: 400px;
    overflow: auto;
} */
		div#image_div {
			display: flex;
			flex-wrap: wrap;
			margin: 0;
			padding: 0;
		}


		.qrcode_cls {
			display: flex;
			width: 12.5%;
			padding: 0.5rem;
			flex-direction: column;
		}

		.btnprint {
			display: flex;
			justify-content: flex-end;
			padding-top: 10px;
		}

		p.p_text {
			font-size: 11px;
			text-align: center;
		}


		div#image_div {
			display: flex;
			flex-wrap: wrap;
			margin: 0;
			padding: 0;
			width: 100%;
			column-gap: 25px;
			justify-content: center;
			row-gap: 26px;
			/* height: 100%; */
		}

		.g-card1 {
			width: 48%;
			display: flex;
			flex-direction: column;
			border: 1px solid #c7c7c7;
			background-color: #fbfbfb;

		}

		.g-card {
			width: 100%;
			display: flex;
			flex-direction: row;
			margin-top: 14px;
			justify-content: space-around;
		}

		.g-img {
			width: 269px;
			height: auto;
			padding-top: 11px;
		}

		.subdiv {
			width: 55%;
		}

		.subdivv {
			width: 38%;

		}

		p#cbx {
			font-size: 12px;
		}

		.qr-code {
			padding: 5px;
			text-align: center;

		}

		p#div {
			text-align: right;
			padding-top: 7px;
			font-weight: 400;

		}

		.detail p {
			margin-bottom: 0px;
			font-weight: 400;
			line-height: 26.5px;
		}

		.detail {
			text-align: justify;
		}

		.qr_img {
			width: 100%;
		}

		/**End */



		@media only screen and (max-width: 600px) {

			/* .qrcode_cls {
				display: flex;
				width: 33.33%;
				padding: 1rem;
			}

			p.p_text {
				font-size: 7px;
				text-align: center;
			} */
			.g-card1 {
				width: 96%;
				display: flex;
				flex-direction: column;
			}

			.g-img {
				width: 100%;
			}

			.g-card {
				width: 100%;
				display: flex;
				flex-direction: column;
				justify-content: space-around;
			}

			.subdiv {
				width: 100%;
			}

			.subdivv {
				width: 100%;
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
			}

			.qr-code {
				padding: 5px;
				width: 50%;
			}

			.qr_img {
				width: 100%;
			}
		}

		@media (min-width: 768px) and (max-width: 991.98px) {
			.g-card1 {
				width: 48%;
				display: flex;
				flex-direction: column;
				border: 1px solid #c7c7c7;
				background-color: #fbfbfb;
			}

			.g-img {
				width: 90%;
			}

			.g-card {
				width: 100%;
				display: flex;
				flex-direction: column;
				justify-content: space-around;
			}

			.subdiv {
				width: 100%;
			}

			.subdivv {
				width: 100%;
				display: flex;
				justify-content: center;
				align-items: center;
				flex-direction: column;
			}

			.qr-code {
				width: 50%;
			}

			.qr_img {
				width: 100%;
			}

		}

		@media (min-width: 992px) and (max-width: 1199.98px) {
			.detail p {
				line-height: 25.5px;
			}

			.g-card1 {
				width: 48%;
				display: flex;
				flex-direction: column;
				border: 1px solid #c7c7c7;
				background-color: #fbfbfb;
			}

			.g-img {
				width: 65%;

				padding-top: 7px;
			}

			.g-card {
				width: 100%;
				display: flex;
				flex-direction: column;
				justify-content: space-around;
				margin-top: 4px;
			}

			.subdiv {
				width: 100%;
				margin-bottom: 7px;
			}

			.subdivv {
				width: 100%;
				display: flex;
				justify-content: center;
				align-items: center;
				flex-direction: column;
				margin-bottom: 0px;
			}

			.qr-code {
				width: 35%;
				padding: 5px;
				text-align: center;
			}

			.qr_img {
				width: 100%;
			}

			p#div {
				text-align: right;
				padding-top: 7px;
				font-weight: 400;
			}
		}
		.main-content-search{
	min-height: auto;
	margin-left: var(--sidebar-width);
}
	</style>

	<style type="text/css" media="print">
		@page {
			size: 'A4';
			/* size: 8.5in 11in; */
			margin-bottom: 178px;

		}

		@media print {
			.no-printme {
				display: none;
			}

			footer {
				display: none;
			}

			#image_div {
				margin-top: 0px !important;
				padding-top: 0px !important;
			}

			.grid-container>div {
				background-color: rgba(255, 255, 255, 0.8);
				text-align: center;
				padding: 10px 0;
				font-size: 30px;
			}

			.widget-container {
				border: unset !important;
				margin-bottom: 5px;
			}

			#image_div {
				margin-top: -160px !important;

			}

			div#image_div {
				display: flex;
				flex-wrap: wrap;
				padding: 0;
				width: 100%;
				column-gap: 25px;
				justify-content: center;
				row-gap: 20px;
				height: 100%;
			}

			.printme {
				height: auto !important;
				overflow: unset !important;
			}

			.qrcode_cls {
				display: flex !important;
				width: 16.66% !important;
				padding: 1rem !important;
			}

			p.p_text {
				font-size: 9px;
				text-align: center;
			}

			.detail p {
				margin-bottom: 0px;
				font-weight: 400;
				line-height: 18.2px;
				font-size: 12px;
			}

			.detail {
				text-align: justify;
			}

			.g-card1 {
				width: 48%;
				display: flex;
				flex-direction: column;
				border: 1px solid #c7c7c7;
				background-color: #fbfbfb !important;

				-webkit-print-color-adjust: exact;
			}

			.g-card {
				width: 100%;
				display: flex;
				flex-direction: row;
				margin-top: 14px;
				justify-content: space-around;
			}

			.g-img {
				width: 269px;
				height: auto;
				padding-top: 11px;
			}

			.subdiv {
				width: 55%;
			}

			.subdivv {
				width: 38%;

			}

			p#cbx {
				font-size: 8px;
				font-weight: 400;
				margin-top: 5px;
			}

			.qr-code {

				text-align: center;
				/* margin: 7px; */
				padding: 5px;
			}

			p#div {
				text-align: right;
				padding-top: 12px;
				font-weight: 400;
				margin-bottom: 0px !important;
			}

			.detail {
				text-align: justify;
			}

			/* img.qrcode_cls {
			margin-left: auto !important;
		} */

		}
		
		.main-content {
    min-height: auto;
}
.ew-footer{
	    position: fixed;
		width: 100%;
}

.main-content-search{
	min-height: auto;
	margin-left: var(--sidebar-width);
}
	</style>

</head>

<body class="page-header-fixed bg-1">
	<div class="modal-shiftfix">
		<!-- Navigation -->
		<div class="navbar navbar-fixed-top scroll-hide">
			<?php
			require_once("include/header.php");
			require_once("include/menu.php");

			?>

		</div>
		<div class="container-fluid main-content-search new_dpt_bottom">

			<div class="row no-printme">
				<div class="col-md-offset-1 col-md-10">
					<div class="widget-container fluid-height clearfix">
						<div class="heading"> <i class="fa fa-plus"></i>Qrcode Master</div>

						<div class="widget-content padded">
							<form class="form-horizontal" id="barcode_form">

								<input type="hidden" id="form_name" name="form_name" value="barcode_retrive">
								<input type="hidden" id="edit_id" name="edit_id" value="<?php echo $_REQUEST['key']; ?>">

								<div id="response" class="alert alert-danger" style="display:none;">
									<div class="message" style="text-align:center"></div>
								</div>

								<br />

								<div class="row">
									<div class="col-md-offset-4 col-md-3">
										<!-- <div class="form-group" >
						<label class="control-label">Month:</label>
						<div class="input-group date  date-picker" data-date-autoclose="true" data-date-format="mm-yyyy">
										<input class="form-control" type="text" id="month" name="month" value="<?php echo date('m-Y'); ?>" required><span class="input-group-addon"><i class="fa fa-calendar"></i></span>
						</div>
						</div> -->
										<div class="form-group">
											<label class="control-label">GRN No:</label>
											<div class="input-group ">
                                            <input class="form-control" type="text" id="search_grn_no" name="search_grn_no" value="" required placeholder="Enter GRN NO" autocomplete="off" ><span class="input-group-addon"><i class="fa fa-search"></i></span>

											</div>
										</div>
									</div>
									<div class=" col-md-4">
										<button class="btn btn-primary" type="button" id="search" style="margin-top:  20px;">Search</button>
									</div>

								</div>
								<br />

							</form>

						</div>
					</div>
				</div>

			</div>
		</div>

		<!--Qrcode Print Section -->
	</div>
	<div class="container-fluid main-content new_dpt_bottom">
		<div class="col-md-offset-1 col-md-10 ">
			<div class="widget-container fluid-height clearfix">
				<div class="row printme">
					<div class="btnprint" style="display:none;">
						<div>
							<input type="submit" value="Print" onclick="window.print()" class="btn btn-primary no-printme">
						</div>
					</div>


					<div id="image_div">


					</div>


				</div>

			</div>
		</div>
	</div>
	<?php require_once("include/footer.php"); ?>
	</div>

	<script type="text/javascript">
		$(document).ready(function() {
			//Add Height

			//button Save
			$("#search").keypress(function(event) {
				if (event.keyCode == 13) {
					event.preventDefault();
				}
			});
            $('#search').prop('disabled',true);
			$('#search_grn_no').keyup(function(){
            $('#search').prop('disabled', this.value == "" ? true : false);     
            })
			$(document).on('click', '#search', function(e) {
				e.preventDefault();
				$('.printme').css('height', '400px');
				$('.printme').css('overflow', 'auto');
				//alert("test");
				$(".form-data-saving").show();
				var data = $('#barcode_form').serialize();
				//console.log(data);
				// if($('#add_client_form').valid() == true && dup_chk)
				// {
				// $(this).attr("disabled",true);
				$.ajax({
					url: "save_details.php",
					type: "post",
					data: data,

					success: function(result) {
						console.log(result);
						$('.btnprint').show();
						$(".form-data-saving").hide();
						$('#image_div').html(result);
                        
                        if(result == 0 ){
					     $('#image_div').html("<h5 class='text-danger'>GRN No Mismatch or Data Not Found</h5>");
					   }
					},
					error: function(jqxhr) {
						ewToast(jqxhr.responseText, 'error');
					}
				});
				//}
			});
			$(document).on('click', '.close-popup', function() {
				$(".form-data-saving").hide();
				$("#alert-status").text("");
				$("#alert-message").text("Saved Successfully please wait until page refresh");
				$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
					$("#alert-container").hide();
					$("#alert-container").removeClass("alert-success");
					location.reload();
				});
			});


		});
		$(window).load(function() {
			$(".loading-page").hide();
		});
	</script>
	<div class="alert" id="alert-container" style="display:none;">
		<button type="button" class="close" data-dismiss="alert">x</button>
		<strong id="alert-status"></strong>
		<span id="alert-message"></span>
	</div>


	<div class="modal fade popup_close" id="myModal">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
					<h4 class="modal-title" style="color:#fff">
						Alert!
					</h4>
				</div>

				<div class="modal-body">
					<h5 text-align="center">
						Do you want to Delete This Record ?
					</h5>
					<div class="modal-footer">
						<button class="btn btn-primary btn-confirm-delete" data-dismiss="modal" type="button" id="">Yes</button>
						<button class="btn btn-default-outline" data-dismiss="modal" type="button" id="">No</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="delete-error-popup">
		<div class="popup_overlay" id="popup_overlay"></div>
		<div class="popup" id="popup">
			<div class="popup_message">
				<h5 class="popup-title">Alert ! </h5>
				This Data Cannot Delete.Used by another record. so you can't Delete !!! <br /> &nbsp; <br />
				<button class="btn btn-sm btn-danger delete-error-popup-close" id="">Close</button> <br /> &nbsp; <br />
			</div>
			<!--<span class="popup_close" id="popup_close">X</span>-->
		</div>
	</div>

</body>

</html>