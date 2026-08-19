<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
		<title>Gracious Express - Book Consignment</title>

		<link href="favicon.png" type="image/x-icon" rel="shortcut icon">
		<link href="assets/css/master.css" rel="stylesheet">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@3.5.2/animate.min.css">
		<!-- book consignment css and js starts here -->
		<link rel="stylesheet" href="assets/css/book-consignment.css">
		<link rel="stylesheet" href="f5/css/all.css">
		<!-- book consignment css and js finished here -->
		<script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
		<script src="assets/js/modernizr.custom.js"></script>
	</head>


	<body>

		<?php include "includes/header.php" ?>


			<div class="section-title parallax-bg parallax-light">
				<ul class="bg-slideshow">
					<li>
						<div style="background-image:url(assets/media/bg/about-us.jpg)" class="bg-slide"></div>
					</li>
				</ul>
				<div class="section__inner">
					<div class="container">
						<div class="row">
							<div class="col-xs-12">
								<h1 class="ui-title-page">Book Consignment ?</h1>
								<div class="ui-subtitle-page">Do it yourself</div>
								<div class="decor-2 decor-2_mod-a decor-2_mod_white"></div>
							</div><!-- end col -->
						</div><!-- end row -->
					</div><!-- end container -->
				</div><!-- end section__inner -->
			</div><!-- end section-title -->


			<section class="section_mod-e">
				<div class="block-about about_page">
					<div class="container">
					<h5 class="text-center ">In order to get you to the right tool or expert in Gracious, we need to ask you a few short questions.</h5>
					<div class="decor-1"><i class='icon'><i class="fa fa-users" aria-hidden="true"></i></i></div>
					
					<br/>
					
					<div class="parent-block">
						<div class="block" id="select-customer">
							<h5>I Am a ...</h5>
							<div class="row">
								<div class="col-xs-6 col-sm-6 cust-padding-margin" id="new-cust">
									<div class="sub-block">
										<h6>New Customer </h6>
										<i class="fas fa-check"></i>
									</div>
								</div>
								<div class="col-xs-6 col-sm-6 cust-padding-margin" id="exist-cust">
									<div class="sub-block">
										<h6>Existing Customer</h6>
										<i class="fas fa-check"></i>
									</div>
								</div>
							</div>			
						</div>

						<div class="block" id="select-shipping">
							<h5>I Prefer Mode Of Shipping Through ...</h5>
							<div class="row">
								<div class="col-xs-2 col-sm-2 cust-padding-margin">
									<div class="sub-block">
										<span class="fas fa-plane custom-icon-size"></span>
										<!-- <p> &nbsp; </p> -->
										<h6>By Air</h6>
										<i class="fas fa-check"></i>
									</div>
								</div>
								<div class="col-xs-2 col-sm-2 cust-padding-margin">
									<div class="sub-block">
										<span class="fas fa-subway custom-icon-size"></span>
										<!-- <p> &nbsp; </p> -->
										<h6>By Train</h6>
										<i class="fas fa-check"></i>
									</div>
								</div>
								<div class="col-xs-2 col-sm-2 cust-padding-margin">
									<div class="sub-block">
										<span class="fas fa-road custom-icon-size"></span>
										<h6>By Road</h6>
										<p class="text-center">Surface</p>
										<i class="fas fa-check"></i>
									</div>
								</div>
								<div class="col-xs-2 col-sm-2 cust-padding-margin">
									<div class="sub-block">
										<span class="fas fa-truck custom-icon-size"></span>
										<h6>By Road</h6>
										<p class="text-center">Express</p>
										<i class="fas fa-check"></i>
									</div>
								</div>
								<div class="col-xs-2 col-sm-2 cust-padding-margin">
									<div class="sub-block">
										<span class="fas fa-motorcycle custom-icon-size"></span>
										<!-- <p class="text-center">&nbsp;</p> -->
										<h6>Local Delivery</h6>
										<i class="fas fa-check"></i>
									</div>
								</div>
							</div>			
						</div>

						<div class="block" id="visit-login-page">
							<div class="col-sm-12 cust-padding-margin py-0">
								<div class="sub-block">
									<a href="#" type="button" class="btn btn-danger btn-lg">Visit Login Page</a>

									<p class="text-center">Visit our login page to access our tools and portals in order to book new shipments.</p>
								</div>
							</div>
						</div>								

						<div class="block" id="select-payment-mode">
							<h5>I Wish To Pay By...</h5>
							<div class="row">
								<div class="col-xs-4 col-sm-4 cust-padding-margin">
									<div class="sub-block">
										<h6>To Billed</h6>
										<p class="text-center">By Sender</p>
										<i class="fa fa-check"></i>
									</div>
								</div>
								<div class="col-xs-4 col-sm-4 cust-padding-margin">
									<div class="sub-block">
										<h6>To Pay</h6>
										<p class="text-center">By Receiver</p>
										<i class="fa fa-check"></i>
									</div>
								</div>
								<div class="col-xs-4 col-sm-4 cust-padding-margin">
									<div class="sub-block">
										<h6>Cash On </h6>
										<h6 class="text-center"><strong>Delivery</strong></h6>
										<i class="fa fa-check"></i>
									</div>
								</div>
							</div>			
						</div>

						<div class="block" id="select-sender-dtl">
							<h5>Consignor / Sender Details.</h5>
							<div class="row">
								<div class="col-sm-12 cust-padding-margin">
									<div class="sub-block">
										<form>

											<div id="sender-details">
												<div class="details-hdr">
													<!-- <span class="far fa-address-card"></span> -->
													<h5>Sender Details</h5>
												</div>
												<div class="send-rcv-dtl">
													<div class="form-group col-sm-6">
														<label for="sender-name">Consignor / Sender Name</label>
														<input type="text" class="form-control" id="sender-name">
													</div>
													<div class="form-group col-sm-6">
														<label for="sender-contact-no">Contact No:</label>
														<input type="number" class="form-control" id="sender-contact-no">
													</div>
													<div class="form-group col-sm-6">
														<label for="sender-email">Email</label>
														<input type="email" class="form-control" id="sender-email">
													</div>
													<div class="form-group col-sm-6">
														<label for="sender-city">City</label>
														<input type="text" class="form-control" id="sender-city">
													</div>
													<div class="form-group col-sm-6">
														<label for="sender-address">Address </label>
														<input type="text" class="form-control" id="sender-address">
													</div>
													<div class="form-group col-sm-6">
														<label for="sender-area">Village / Town / Area</label>
														<input type="text" class="form-control" id="sender-area">
													</div>
													<div class="form-group col-sm-6">
														<label for="package-qty">No. of Packages</label>
														<input type="number" class="form-control" id="package-qty">
													</div>
													<div class="form-group col-sm-6 ">
														<label for="package-qty"><input type="checkbox" value="" id="volumetric-check-box"> &nbsp; Volumetric (in cm)</label>
														<div class="volumetric-input-boxes disabled">
														<input type="number" placeholder="length" class="form-control" id="length" disabled><span>X</span>
														<input type="number" placeholder="width" class="form-control" id="width" disabled><span>X</span>
														<input type="number" placeholder="height" class="form-control" id="height" disabled>
														</div>
													</div>
													<div class="form-group col-sm-6 package-wgt">
														<label for="package-wgt">Weight (In Kgs)</label>
														<div class="input-group">
															<input type="number" class="form-control" id="package-wgt">
															<span class="input-group-addon">Kgs *</span>
														</div>
													</div>
												</div>	
											</div>

											<div id="reciever-details" class="disabled">
												<div class="details-hdr">
													<!-- <span class="far fa-address-card"></span> -->
													<h5>Receiver Details</h5>
												</div>
												<div class="send-rcv-dtl ">
													<div class="form-group col-sm-6">
														<label for="reciever-name">Consignee / Receiver Name</label>
														<input type="text" class="form-control" id="reciever-name">
													</div>
													<div class="form-group col-sm-6">
														<label for="reciever-contact-no">Contact No:</label>
														<input type="number" class="form-control" id="reciever-contact-no">
													</div>
													<div class="form-group col-sm-6">
														<label for="reciever-email">Email</label>
														<input type="email" class="form-control" id="reciever-email">
													</div>
													<div class="form-group col-sm-6">
														<label for="reciever-city">City</label>
														<input type="text" class="form-control" id="reciever-city">
													</div>
													<div class="form-group col-sm-6">
														<label for="reciever-address">Address </label>
														<input type="text" class="form-control" id="reciever-address">
													</div>
													<div class="form-group col-sm-6">
														<label for="reciever-area">Village / Town / Area</label>
														<input type="text" class="form-control" id="reciever-area">
													</div>
												</div>
											</div>
											
											<div id="supporting-document" class="disabled">
												<div class="details-hdr">
													<!-- <span class="far fa-address-card"></span> -->
													<h5>Supporting Documents</h5>
												</div>
												<div class="send-rcv-dtl">
													<div class="form-group col-sm-6">
														<label for="doc-name">Doc / Nos</label>
														<input type="text" class="form-control" id="doc-name">
													</div>
													<div class="form-group col-sm-6">
														<label for="doc-data">Doc Data:</label>
														<input type="number" class="form-control" id="doc-data">
													</div>
													<div class="form-group col-sm-6">
														<label for="reciever-email">Invoice / D.C Copy</label>
														<input type="file" class="form-control-file" id="exampleFormControlFile1">
													</div>
												</div>
												<a class="btn btn-primary" onclick="submitDetails()">Submit</a>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>

						<div class="block" id="contact-expert">
							<h5 class="text-center">Get in Touch to Find out More</h5>
							<p class="text-center">Our colleagues from Blue Dart will be in touch to answer your inquiry.</p>
							<div class="col-sm-12 cust-padding-margin">
								<div class="sub-block">
									<button type="button" class="btn btn-danger btn-lg">Contact Our Experts</button>
								</div>
							</div>
						</div>
					</div>

					</div><!-- end row -->
				</div><!-- end container -->
			</section><!-- end section-default -->
			<hr class="hr_line">
			


		<div class="section-clients section-bg_mod-a wow">
				<div class="container">
					<div class="row">
						<div class="col-xs-12">

							<div class="carusel-clients slider_mod-a owl-carousel owl-theme enable-owl-carousel"
								data-min480="1"
								data-min768="4"
								data-min992="4"
								data-min1200="4"
								data-pagination="false"
								data-navigation="false"
								data-auto-play="4000"
								data-stop-on-hover="true">

								<a class="carusel-clients__item" href="https://www.havells.com/" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/Havells-India.jpg" alt="logo">
									<span class="helper-2"></span>
								</a>
								<a class="carusel-clients__item" href="http://www.farida.co.in/" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/Farida-Group.jpg" alt="logo">
									<span class="helper-2"></span>
								</a>
								<a class="carusel-clients__item" href="https://arvindbrands.com/" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/arvind-lifestyle.jpg" alt="logo">
									<span class="helper-2"></span
								></a>
								<a class="carusel-clients__item" href="http://www.hflgoa.com/" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/Hindustan-Foods-Ltd.jpg" alt="logo">
									<span class="helper-2"></span>
								</a>
                                <a class="carusel-clients__item" href="http://kljgroup.com/" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/KLJ-Polymers.jpg" alt="logo">
									<span class="helper-2"></span>
								</a>
                                <a class="carusel-clients__item" href="home.html" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/Mahendra-Mahendra.jpg" alt="logo">
									<span class="helper-2"></span>
								</a>
								<a class="carusel-clients__item" href="https://www.paragonfootwear.com/" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/Paragon-Polymers.jpg" alt="logo">
									<span class="helper-2"></span
								></a>
								<a class="carusel-clients__item" href="http://ranegroup.com/" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/rane.jpg" alt="logo">
									<span class="helper-2"></span>
								</a>
                                <a class="carusel-clients__item" href="http://www.saragroup.in/" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/Sara-suole.jpg" alt="logo">
									<span class="helper-2"></span>
								</a>
                                <a class="carusel-clients__item" href="http://tatainternational.com/" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/tata-international.jpg" alt="logo">
									<span class="helper-2"></span
								></a>
								<a class="carusel-clients__item" href="http://www.vivagroupindia.com/" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/vivabooks.jpg" alt="logo">
									<span class="helper-2"></span>
								</a>
                                <a class="carusel-clients__item" href="http://www.wilhelmindia.co.in/" target="_blank">
									<img class="carusel-clients__img" src="assets/media/clients/Wilhelm-Textiles.jpg" alt="logo">
									<span class="helper-2"></span>
								</a>

							</div><!-- end  -->
						</div><!-- end col -->
					</div><!-- end row -->
				</div><!-- end container -->
			</div><!-- end section-clients -->
			
            <?php include 'includes/footer.php'; ?> 
			
		</div>
		<!-- end layout-theme -->

 <script>
              var wow = new WOW(
  {
    boxClass:     'wow',      // animated element css class (default is wow)
    animateClass: 'animated', // animation css class (default is animated)
    offset:       0,          // distance to the element when triggering the animation (default is 0)
    mobile:       true,       // trigger animations on mobile devices (default is true)
    live:         true,       // act on asynchronously loaded content (default is true)
    callback:     function(box) {
      // the callback is fired every time an animation is started
      // the argument that is passed in is the DOM node being animated
    },
    scrollContainer: null,    // optional scroll container selector, otherwise use window,
    resetAnimation: true,     // reset animation on end (default is true)
  }
);
wow.init();
              </script>
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
		<script src="assets/js/book-consignment.js"></script>
		<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
		
             

	</body>
</html>

