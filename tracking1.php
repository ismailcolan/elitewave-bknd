<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
		<title>Gracious Express ~ Services</title>

		<link href="assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">
		
		<link href="assets/css/master.css" rel="stylesheet">
			
		<script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
		<script src="assets/js/modernizr.custom.js"></script>






        
<style>
.flex-nowrap{
    -ms-flex-wrap:nowrap!important;
    flex-wrap:nowrap!important
}
.d-flex{
    display:-ms-flexbox!important;
    display:flex!important
}
.step {
  list-style: none;
  margin: 1.3rem 0;
  width: 100%;
}

.step .step-item {
  -ms-flex: 1 1 0;
  flex: 1 1 0;
  margin-top: 0;
  min-height: 1rem;
  position: relative; 
  text-align: center;
}

.step .step-item:not(:first-child)::before {
  background: #0069d9;
  content: "";
  height: 2px;
  left: -50%;
  position: absolute;
  top: 9px;
  width: 100%;
}

.step .step-item a {
  color: #acb3c2;
  display: inline-block;
  padding: 20px 10px 0;
  text-decoration: none;
}

.step .step-item a::before {
  background: #0069d9;
  border: .1rem solid #fff;
  border-radius: 50%;
  content: "";
  display: block;
  height: .9rem;
  left: 50%;
  position: absolute;
  top: .2rem;
  transform: translateX(-50%);
  width: .9rem;
  z-index: 1;
}

.step .step-item.active a::before {
  background: #fff;
  border: .1rem solid #0069d9;
}

.step .step-item.active ~ .step-item::before {
  background: #e7e9ed;
}

.step .step-item.active ~ .step-item a::before {
  background: #e7e9ed;
}  
.track-orderme::placeholder {
    color: #2b3338;
}  
.trackcontainer {
    margin: 0 auto;
    text-align: center;
}



/* Tracking Table status */

.near_by_hotel_wrapper{
	background:#f5f5f5;
	}
.custom_table {
    border-collapse: separate;
    border-spacing: 0 10px;
    margin-top: -3px !important;
}
.custom_table thead tr th {
	padding: 0px 8px;
	font-size: 16px;
	border: 0 none !important;
	border-top: 0 none !important;
}
.custom_table tbody tr {
    -moz-box-shadow: 0 2px 3px #e0e0e0;
    -webkit-box-shadow: 0 2px 3px #e0e0e0;
    box-shadow: 0 2px 3px #e0e0e0;
}
.near_by_hotel_wrapper table tr td {
	border-right: 1px solid #d2d1d1;
}

.custom_table tbody tr td {
	background: #fff none repeat scroll 0 0;
	border-top: 0 none !important;
	margin-bottom: 20px;
	padding: 10px 8px;
	font-size: 16px;
}
.near_by_hotel_wrapper table tr td {
    border-right: 1px solid #d2d1d1;
}

</style>
        
        
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
								<h1 class="ui-title-page">TRACK YOUR ORDER</h1>
								<div class="ui-subtitle-page">Enter a tracking number, and get tracking results.</div>
								<div class="decor-2 decor-2_mod-a decor-2_mod_white"></div>
							</div><!-- end col -->
						</div><!-- end row -->
					</div><!-- end container -->
				</div><!-- end section__inner -->
			 </div><!-- end section-title -->

<br/>
			
				<section>
					<div class="container">
						<div class="row">
						

							

						<div class="container">
		
        
        
        							<section class="section-form-request">
								<!-- <div class="wrap-title-block wrap-title-block_mod-c">
									<h3 class="ui-title-block ui-title-block_mod-c" style="text-align:center;">Track Your Order</h3>
<p style="text-align:center;">Enter a tracking number, and get tracking results.</p>
									<div class="decor-1 decor-1_mod-b" style="text-align:center; margin: 0 auto;"><i class="icon"><i class="fa fa-envelope-o" aria-hidden="true"></i></i></div>
								</div> -->

								<form class="form-request" method="post">
									<div class="row">
										<div class="clearfix trackcontainer">
									
									<form class="form-subscribe" method="post">
										<input class="form-subscribe__input form-control track-orderme" type="text" placeholder="enter your track id" required="">
										<button class="form-subscribe__btn btn btn_mod-c btn-sm btn-effect"><span class="btn__inner">Track Now</span></button>
									</form>
									
								</div>
									</div><!-- end row -->
									
								</form><!-- end form-request -->
							</section>
        
        
           
                <div class="mt-5 mb-5 text-center">
                    <h2>Tracking Status</h2>
                </div>
                <ul class="step d-flex flex-nowrap">
              <li class="step-item">
                <a href="#!" class="">Consignment Booked</a>
              </li>
              <li class="step-item">
                <a href="#!" class="">Consignment Picked Up</a>
              </li>
              <li class="step-item active">
                <a href="#!" class="">In Transit -1 (consignment at Origin State)</a>
              </li>
              <li class="step-item">
                <a href="#!" class="">In Transit -2 (Towards Destination State)</a>
              </li>
              <li class="step-item">
                <a href="#!" class="">In Transit -3 (Towards Destination)</a>
              </li>
              <li class="step-item">
                <a href="#!" class="">Arrived at Destination</a>
              </li>
              <li class="step-item">
                <a href="#!" class="">Out for Delivery</a>
              </li>
              <li class="step-item">
                <a href="#!" class="">Consignment Delivered Successfully</a>
              </li>
            </ul> 
           
         <br/>
         <br/>
         
         
         <h2>Tracking Details</h2>
         
         <div class="near_by_hotel_wrapper">
<div class="near_by_hotel_container">
  <table class="table no-border custom_table dataTable no-footer dtr-inline">
    <colgroup>
    <col width="20%">
    <col width="10%">
    <col width="">
    </colgroup>
    <thead>
      <tr>
        <th class="text-center">Date</th>
        <th class="text-center">Time</th>
        <th class="text-center">Location/Supplied via</th>
        <th class="text-center">Activity</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="text-center">27 JULY 2018</td>
        <td class="text-center">23:21</td>
        <td class="text-center">Chennai</td>
        <td class="text-center">Booked Chennai - Tamil Nadu</td>
        
        
      </tr>
      <tr>
        <td class="text-center">27 JULY 2018</td>
        <td class="text-center">12:01</td>
        <td class="text-center">Chennai</td>
        <td class="text-center">Pickedup Chennai - Tamil Nadu</td>
      </tr>
      <tr>
        <td class="text-center">28 JULY 2018</td>
        <td class="text-center">12:01</td>
        <td class="text-center">Telangana</td>
        <td class="text-center">Arrived Hub Tamil Nadu</td>
      </tr>
      <tr>
        <td class="text-center">29 JULY 2018</td>
        <td class="text-center">12:01</td>
        <td class="text-center">Telangana</td>
        <td class="text-center">Sorted to Destination</td>
      </tr>
      <tr>
        <td class="text-center">30 JULY 2018</td>
        <td class="text-center">12:01</td>
        <td class="text-center">New Delhi</td>
        <td class="text-center">In Transit at Delhi</td>
      </tr>
      <tr>
        <td class="text-center">30 JULY 2018</td>
        <td class="text-center">12:01</td>
        <td class="text-center">New Delhi</td>
        <td class="text-center">Arrived Hub Delhi</td>
      </tr>
      <tr>
        <td class="text-center">28 JULY 2018</td>
        <td class="text-center">12:01</td>
        <td class="text-center">New Delhi</td>
        <td class="text-center">Out for Delivery</td>
      </tr>
      <tr>
        <td class="text-center">31 JULY 2018</td>
        <td class="text-center">11:01</td>
        <td class="text-center">Le Meridien, New Delhi</td>
        <td class="text-center">Delivered</td>
      </tr>
    </tbody>
  </table>
</div>
</div> 
        
        
        
        
	</div>
</div>	

							

							
						</div>
					</div>
				
			</section>
<br/>
<br/>
<br/>





			<section class="section-bg">
				<div class="parallax-bg parallax-primary">
					<ul class="bg-slideshow">
						<li>
							<div style="background-image:url(assets/media/bg/bg-7.jpg)" class="bg-slide"></div>
						</li>
					</ul>
				</div>
				<div class="section__inner">
					<div class="container">
						<div class="row">
							<div class="col-xs-12">
								<div class="block-download clearfix">
									<div class="block-download__inner">
										<h2 class="block-download__title">If You Need Any Information.. We are Available For You</h2>
										
									</div>
									<div class="block-download__btn">
										<a class="btn btn_mod-c btn-sm btn-effect" href="contact.php"><span class="btn__inner">GET A QUOTE</span></a>
									</div>
									<i class="block-download__icon flaticon-map2"></i>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>




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

	</body>
</html>

