		<!-- Loader -->
		<div id="page-preloader"><span class="spinner"></span></div>
		<!-- Loader end -->


		<div class="layout-theme animated-css"  data-header="sticky" data-header-top="200">


			<!-- HEADER -->

			<div class="container">
			<!-- <div class="form-data-saving" ><img src="96x96.gif" /></div> -->
				<div class="row">
					<div class="col-xs-12">
						<header class="header">
							<div class="header__wrap">
								<div class="header-top clearfix">
									<div class="header-top__inner">
										<span class="header-top__contacts">Call :  +91 96259 35011 / 96259 5015</span>
										<span class="header-top__contacts">Email : <a class="header-top__contacts-link" href="mailto:info@graciousexpress.com"> info@graciousexpress.com</a></span>
                                    <span class="header-top__login"><a href="http://localhost/graciousexpress/book-consignment.php" style="    color: #213e9a; font-weight: 700; font-family: sans-serif;"><i class="fa fa-sign-in" aria-hidden="true"></i> Book Online</a></span>
									</div>

									<ul class="social-links list-inline">
										<li><a target="_blank" href="https://twitter.com/"><i class="icons fa fa-twitter"></i></a></li>
										<li><a target="_blank" href="https://www.facebook.com/"><i class="icons fa fa-facebook"></i></a></li>
										<li><a target="_blank" href="https://www.linkedin.com/"><i class="icons fa fa-linkedin"></i></a></li>
										<li><a target="_blank" href="https://www.google.com/"><i class="icons fa fa-google-plus"></i></a></li>
									</ul>
								</div>

								<a class="logo" href="index.php"><img class="logo__img" src="assets/img/gracious_express.png" alt="Logo"></a>

								<div class="header__inner clearfix">
									<nav class="navbar yamm">
										<div class="navbar-header hidden-md hidden-lg hidden-sm">
											<button type="button" data-toggle="collapse" data-target="#navbar-collapse-1" class="navbar-toggle"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button>
										</div>
										<div id="navbar-collapse-1" class="navbar-collapse collapse">
											<ul class="nav navbar-nav">
												<li class="head_li active"><a id="home" href="index.php">Home</a></li>
												<li class="head_li"><a href="about.php">ABOUT US</a></li>
												<li class="head_li dropdown"><a href="services.php">Services</a>
													<ul role="menu" class="dropdown-menu">
														<li> <a href="door_to_door_delivery.php">Door to Door Delivery</a> </li>
														<li> <a href="air_cargo_services.php">Air Cargo Services</a> </li>
														<li> <a href="train_cargo_services.php">Train Cargo Services</a> </li>
														<li> <a href="express_cargo_services.php">Express Cargo Services</a> </li>
														<li> <a href="port_pickup&delivery_services.php">Port Pickup & Delivery Services</a> </li>
														<li> <a href="surface_cargo_services.php">Surface Cargo Services</a> </li>
													</ul>
												</li>
												
												<li class="dropdown head_li"><a href="contact.php">Contact us</a>
													<ul role="menu" class="dropdown-menu">
													<li><a href="contact.php">Contact Details</a></li>
													<li><a href="request-a-quote.php">request a quote</a></li>
												<li><a href="request-for-pickup.php">Request Pickup</a></li>
												
														
													</ul>
												</li>
												<li class="dropdown head_li"><a href="rate_calculator.php">Tools</a>
													<ul role="menu" class="dropdown-menu">
													<li><a href="rate_calculator.php">Rate Calculator</a></li>
													<li><a href="estimated_delivery.php">Expected Delivery</a></li>
														
													</ul>
												</li>
												<li class="head_li"><a href="user/login.php">User Login</a></li>
                                        		<li class="head_li"><a href="register.php">Create User</a></li>
											</ul>
										</div>
									</nav>
									<a class="header__btn btn btn-primary btn-sm btn-effect-2" href="http://localhost/graciousexpress/tracking.php">Track Consignment</a>
								</div>
							</div>
						</header><!-- end header-->
					</div><!-- end col-->
				</div><!-- end row-->
			</div><!-- end container-->      
			<script>
				$(document).ready(function() {
						var path = window.location.href;
						 console.log(path);
						$('ul li a').each(function() {
							if (this.href === path) {

								$(this).parent().addClass('active');
							} else {
								$(this).parent().removeClass("active");
							}
						});
                        $('li:has(ul.dropdown-menu:has(li.active))').addClass('active');

						$('ul li a').each(function() {

							if (path === "http://localhost/graciousexpress/" || path === "http://localhost/graciousexpress/index.php") {
								$('#home').parent().addClass('active');
							} else {
								$('#home').parent().removeClass("active");
							}


						}); 
                        //$("li.active").parent().closest("li").find(">a").parent().addClass("active");

				});
			</script>