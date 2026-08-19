<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
	<title>Gracious Express ~ Contact Us</title>

	<link href="assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">
	<link href="assets/css/master.css" rel="stylesheet">

	<script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
	<script src="assets/js/modernizr.custom.js"></script>

	<style>
		/* Tabs panel */
		.tabbable-panel {
			border: 1px solid #eee;
			padding: 10px;
		}

		#tel:invalid {
			color: red !important;
		}

		/* Default mode */
		.tabbable-line>.nav-tabs {
			border: none;
			margin: 0px;
		}

		.tabbable-line>.nav-tabs>li {
			margin-right: 2px;
		}

		.tabbable-line>.nav-tabs>li>a {
			border: 0;
			border-radius: 0px !important;
			border-bottom: 4px solid #b93207;
			margin-right: 0;
			color: #ffffff;
			background: #ff4308;
		}

		.tabbable-line>.nav-tabs>li>a>i {
			color: #a6a6a6;
		}

		.tabbable-line>.nav-tabs>li.open,
		.tabbable-line>.nav-tabs>li:hover {
			border-bottom: 4px solid #fbcdcf;
		}

		.tabbable-line>.nav-tabs>li.open>a,
		.tabbable-line>.nav-tabs>li:hover>a {
			border: 0;
			background: none !important;
			color: #333333;
		}

		.tabbable-line>.nav-tabs>li.open>a>i,
		.tabbable-line>.nav-tabs>li:hover>a>i {
			color: #a6a6a6;
		}

		.tabbable-line>.nav-tabs>li.open .dropdown-menu,
		.tabbable-line>.nav-tabs>li:hover .dropdown-menu {
			margin-top: 0px;
		}

		.tabbable-line>.nav-tabs>li.active {
			border-bottom: 4px solid #132c86;
			position: relative;
			background: #233e9a;
		}

		.tabbable-line>.nav-tabs>li.active>a {
			border: 0;
			color: #ffffff;
			background: #233e9a;
		}

		.tabbable-line>.nav-tabs>li.active>a>i {
			color: #404040;
		}

		.tabbable-line>.tab-content {
			margin-top: -3px;
			background-color: #fff;
			border: 0;
			border-top: 1px solid #eee;
			padding: 15px 0;
		}

		.portlet .tabbable-line>.tab-content {
			padding-bottom: 0;
		}

		#mobile:invalid {
			color: red;
		}

		.form-request .form-control,
		.form-request .select-control {
			margin-bottom: 8px;
		}

		.error {
			color: red;
		}
	</style>


</head>


<body>

	<?php
	include "includes/connect.php";

	include "includes/header.php";


	?>


	<div class="section-title parallax-bg parallax-light">
		<ul class="bg-slideshow">
			<li>
				<div style="background-image:url(assets/media/bg/contact-us-graciousepxress.jpg)" class="bg-slide"></div>
			</li>
		</ul>
		<div class="section__inner">
			<div class="container">
				<div class="row">
					<div class="col-xs-12">
						<h1 class="ui-title-page">Contact us</h1>
						<div class="ui-subtitle-page">Get in Touch</div>
						<div class="decor-2 decor-2_mod-a decor-2_mod_white"></div>
					</div><!-- end col -->
				</div><!-- end row -->
			</div><!-- end container -->
		</div><!-- end section__inner -->
	</div><!-- end section-title -->




	<br /><br />


	<div>



		<div class="container">



			<div class="tabbable-panel">
				<div class="tabbable-line">
					<ul class="nav nav-tabs ">
						<li class="active">
							<a href="#tab_default_1" data-toggle="tab">
								Corporate Office </a>
						</li>
						<li>
							<a href="#tab_default_2" data-toggle="tab">
								Gracious Express Other Branches </a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="tab_default_1">

							<br />

							<div class="container">
								<div class="col-md-4">
									<section class="section-contacts-block">
										<h3 class="contacts-block__title ui-title-inner">Gracious Express</h3>
										<div class="decor-2 decor-2_mod-b"></div>
										<div class="contacts-block clearfix">
											<i class="icon">
												<i class="fa fa-building-o" aria-hidden="true"></i>
											</i>
											<span class="contacts-block__inner">
												<span class="contacts-block__emphasis color-primary">HO: No. 68, Pace City - 1 | Sector 37, Gurgaon - 122 001 (Haryana)</span>
										</div>
										<div class="contacts-block clearfix">
											<i class="icon">
												<i class="fa fa-phone" aria-hidden="true"></i>
											</i>
											<span class="contacts-block__inner">
												<span class="contacts-block__emphasis color-primary">+91 96259 35011 / 96259 5015</span> 24/7 Free HelpLine
											</span>
										</div>
										<div class="contacts-block clearfix">
											<i class="icon">
												<i class="fa fa-envelope-o" aria-hidden="true"></i>
											</i>
											<span class="contacts-block__inner">
												<a class="contacts-block__emphasis color-primary" href="mailto:info@graciousexpress.com">info@graciousexpress.com</a> We usually reply within 24 hours
											</span>
										</div>
									</section>
									<!-- end contacts-block -->
								</div>
								<div class="col-md-8">
									<section class="section-form-request map-percent">
										<iframe height="320" frameborder="0" style="border:0" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3508.2248813093606!2d77.00661508266421!3d28.442636630218693!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390d194ebf4ffc0b%3A0x398a26cd4deff8f1!2sGracious%20express!5e0!3m2!1sen!2sin!4v1692164412745!5m2!1sen!2sin" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
									</section>
								</div>
							</div>



							<div class="section_mod-c">
								<div class="container">
									<div class="row">

										<div class="col-md-offset-2 col-md-8">
											<section class="section-form-request">
												<div class="wrap-title-block wrap-title-block_mod-c">
													<h3 class="ui-title-block ui-title-block_mod-c" style="text-align:center;">send a message</h3>
													<div class="decor-1 decor-1_mod-b" style="text-align:center; margin: 0 auto;"><i class="icon"><i class="fa fa-envelope-o" aria-hidden="true"></i></i></div>
												</div>

												<form class="form-request" method="post">
													<div class="row">
														<div class="col-sm-6">
															<input class="form-control" type="text" placeholder="first Name" required>
														</div><!-- end col -->
														<div class="col-sm-6">
															<input class="form-control" type="text" placeholder="last Name" required>
														</div><!-- end col -->
													</div><!-- end row -->
													<div class="row">
														<div class="col-sm-6">
															<input class="form-control" type="email" placeholder="Email address" required>
														</div><!-- end col -->
														<div class="col-sm-6">
															<input class="form-control" type="tel" id="tel" minlength=10 maxlength=10 placeholder="phone no." required inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;">
														</div><!-- end col -->
													</div><!-- end row -->
													<div class="row">
														<div class="col-xs-12">
															<input class="form-control" type="text" placeholder="Enquiry type / subject" required>
														</div><!-- end col -->
													</div><!-- end row -->
													<div class="row">
														<div class="col-xs-12">
															<textarea class="form-control" placeholder="message" required rows="6"></textarea>
															<button type="button" class="btn btn_mod-a btn-sm btn-effect pull-right"><span class="btn__inner">send message</span></button>
														</div><!-- end col -->
													</div><!-- end row -->
												</form><!-- end form-request -->
											</section>
										</div>
										<!-- end col -->

									</div>
									<!-- end row -->
								</div>
								<!-- end container -->
							</div><!-- end section-area -->



						</div>

						<div class="tab-pane" id="tab_default_2">

							<br />


							<div class="product-disc space-bottom-35">
								<div class="col-md-12 col-sm-6">
									<div class="contact-form">
										<select name="locations" class="form-control" id="location">
											<option>Choose Location</option>
											<?php
											$branch_query = "select * from branch where status=0";
											$branch_result = mysqli_query($conn, $branch_query);
											while ($branch_row = mysqli_fetch_array($branch_result)) {
											?>
												<option value="<?php echo $branch_row['branch_id'] ?>"> <?php echo $branch_row['branch_name']; ?> </option>

											<?php
											}
											?>
										</select>

									</div>
								</div>
							</div>

							<div id="map_data">
							</div>











						</div>

					</div>
				</div>
			</div>




		</div>
	</div>



	</div>












	<div class="map"></div>


	<?php include 'includes/footer.php'; ?>


	</div>
	<!-- end layout-theme -->


	<!-- SCRIPTS MAIN -->

	<script src="assets/js/jquery-migrate-1.2.1.js"></script>
	<script src="assets/plugins/bootstrap/js/bootstrap.min.js"></script>
	<script src="assets/js/waypoints.min.js"></script>
	<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
	<script src="assets/js/modernizr.custom.js"></script>
	<script src="assets/js/cssua.min.js"></script>


	<!--SCRIPTS THEME-->

	<!-- Home slider -->
	<script src="assets/plugins/slider-pro/dist/js/jquery.sliderPro.js"></script>
	<!-- Sliders -->
	<script src="assets/plugins/owl-carousel/owl.carousel.min.js"></script>

	<script src="assets/plugins/flexslider/jquery.flexslider.js"></script>
	<!-- Modal -->
	<script src="assets/plugins/prettyphoto/js/jquery.prettyPhoto.js"></script>
	<!-- Select customization -->
	<script src="assets/plugins/bootstrap-select/dist/js/bootstrap-select.js"></script>
	<!-- Chart -->
	<script src="assets/plugins/rendro-easy-pie-chart/dist/jquery.easypiechart.min.js"></script>
	<!-- Animation -->
	<script src="assets/plugins/scrollreveal/dist/scrollreveal.min.js"></script>
	<!-- Menu for android-->
	<script src="assets/js/doubletaptogo.js"></script>

	<!-- Custom -->
	<script src="assets/js/custom.js"></script>


	<script type="text/javascript">
		$(document).ready(function() {
			/* $("select").change(function(){
				$(this).find("option:selected").each(function(){
					var optionValue = $(this).attr("value");
					if(optionValue){
						$(".map-box").not("." + optionValue).hide();
						$("." + optionValue).show();
					} else{
						$(".map-box").hide();
					}
				});
			}).change(); */

			$(document).on('change', '#location', function() {
				var optionValue = $(this).val();
				alert(optionValue);
				$.ajax({
					async: false,
					url: "includes/fetch_details.php",
					data: {
						cmd: "get_map_details",
						val: optionValue
					},
					success: function(result) {
						console.log(result);
						$('#map_data').html(result);
					}
				})

			});
		});
	</script>


</body>

</html>