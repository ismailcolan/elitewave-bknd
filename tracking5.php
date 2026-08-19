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
              

<style class="cp-pen-styles">body {
  color: #768390;
  background: #FFF;
  font-family: "Effra", Helvetica, sans-serif;
  padding: 0;
  -webkit-font-smoothing: antialiased;
}

h1, h2, h3, h4, h5, h6 {
  color: #3D4351;
  margin-top: 0;
}

a {
  color: #FF6B6B;
}
a:hover {
  color: #ff9a9a;
  text-decoration: none;
}

.example-header {
  background: #3D4351;
  color: #FFF;
  font-weight: 300;
  padding: 3em 1em;
  text-align: center;
}
.example-header h1 {
  color: #FFF;
  font-weight: 300;
  margin-bottom: 20px;
}
.example-header p {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 3px;
  font-weight: 700;
}

.container-fluid .row {
  padding: 0 0 4em 0;
}
.container-fluid .row:nth-child(even) {
  background: #F1F4F5;
}

.example-title {
  text-align: center;
  margin-bottom: 60px;
  padding: 3em 0;
  border-bottom: 1px solid #E4EAEC;
}
.example-title p {
  margin: 0 auto;
  font-size: 16px;
  max-width: 400px;
}

/*==================================
    TIMELINE
==================================*/
/*-- GENERAL STYLES
------------------------------*/
.timeline {
  line-height: 1.4em;
  list-style: none;
  margin: 0;
  padding: 0;
  width: 100%;
}
.timeline h1, .timeline h2, .timeline h3, .timeline h4, .timeline h5, .timeline h6 {
  line-height: inherit;
}

/*----- TIMELINE ITEM -----*/
.timeline-item {
  padding-left: 40px;
  position: relative;
}
.timeline-item:last-child {
  padding-bottom: 0;
}

/*----- TIMELINE INFO -----*/
.timeline-info {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 3px;
  margin: 0 0 .5em 0;
  text-transform: uppercase;
  white-space: nowrap;
}

/*----- TIMELINE MARKER -----*/
.timeline-marker {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  width: 15px;
}
.timeline-marker:before {
  background: #FF6B6B;
  border: 3px solid transparent;
  border-radius: 100%;
  content: "";
  display: block;
  height: 15px;
  position: absolute;
  top: 4px;
  left: 0;
  width: 15px;
  transition: background 0.3s ease-in-out, border 0.3s ease-in-out;
}
.timeline-marker:after {
  content: "";
  width: 3px;
  background: #CCD5DB;
  display: block;
  position: absolute;
  top: 24px;
  bottom: 0;
  left: 6px;
}
.timeline-item:last-child .timeline-marker:after {
  content: none;
}

.timeline-item:not(.period):hover .timeline-marker:before {
  background: transparent;
  border: 3px solid #FF6B6B;
}

/*----- TIMELINE CONTENT -----*/
.timeline-content {
  padding-bottom: 40px;
}
.timeline-content p:last-child {
  margin-bottom: 0;
}

/*----- TIMELINE PERIOD -----*/
.period {
  padding: 0;
}
.period .timeline-info {
  display: none;
}
.period .timeline-marker:before {
  background: transparent;
  content: "";
  width: 15px;
  height: auto;
  border: none;
  border-radius: 0;
  top: 0;
  bottom: 30px;
  position: absolute;
  border-top: 3px solid #CCD5DB;
  border-bottom: 3px solid #CCD5DB;
}
.period .timeline-marker:after {
  content: "";
  height: 32px;
  top: auto;
}
.period .timeline-content {
  padding: 40px 0 70px;
}
.period .timeline-title {
  margin: 0;
}

/*----------------------------------------------
    MOD: TIMELINE SPLIT
----------------------------------------------*/
@media (min-width: 768px) {
  .timeline-split .timeline, .timeline-centered .timeline {
    display: table;
  }
  .timeline-split .timeline-item, .timeline-centered .timeline-item {
    display: table-row;
    padding: 0;
  }
  .timeline-split .timeline-info, .timeline-centered .timeline-info,
  .timeline-split .timeline-marker,
  .timeline-centered .timeline-marker,
  .timeline-split .timeline-content,
  .timeline-centered .timeline-content,
  .timeline-split .period .timeline-info,
  .timeline-centered .period .timeline-info {
    display: table-cell;
    vertical-align: top;
  }
  .timeline-split .timeline-marker, .timeline-centered .timeline-marker {
    position: relative;
  }
  .timeline-split .timeline-content, .timeline-centered .timeline-content {
    padding-left: 30px;
  }
  .timeline-split .timeline-info, .timeline-centered .timeline-info {
    padding-right: 30px;
  }
  .timeline-split .period .timeline-title, .timeline-centered .period .timeline-title {
    position: relative;
    left: -45px;
  }
}

/*----------------------------------------------
    MOD: TIMELINE CENTERED
----------------------------------------------*/
@media (min-width: 992px) {
  .timeline-centered,
  .timeline-centered .timeline-item,
  .timeline-centered .timeline-info,
  .timeline-centered .timeline-marker,
  .timeline-centered .timeline-content {
    display: block;
    margin: 0;
    padding: 0;
  }
  .timeline-centered .timeline-item {
    padding-bottom: 40px;
    overflow: hidden;
  }
  .timeline-centered .timeline-marker {
    position: absolute;
    left: 50%;
    margin-left: -7.5px;
  }
  .timeline-centered .timeline-info,
  .timeline-centered .timeline-content {
    width: 50%;
  }
  .timeline-centered > .timeline-item:nth-child(odd) .timeline-info {
    float: left;
    text-align: right;
    padding-right: 30px;
  }
  .timeline-centered > .timeline-item:nth-child(odd) .timeline-content {
    float: right;
    text-align: left;
    padding-left: 30px;
  }
  .timeline-centered > .timeline-item:nth-child(even) .timeline-info {
    float: right;
    text-align: left;
    padding-left: 30px;
  }
  .timeline-centered > .timeline-item:nth-child(even) .timeline-content {
    float: left;
    text-align: right;
    padding-right: 30px;
  }
  .timeline-centered > .timeline-item.period .timeline-content {
    float: none;
    padding: 0;
    width: 100%;
    text-align: center;
  }
  .timeline-centered .timeline-item.period {
    padding: 50px 0 90px;
  }
  .timeline-centered .period .timeline-marker:after {
    height: 30px;
    bottom: 0;
    top: auto;
  }
  .timeline-centered .period .timeline-title {
    left: auto;
  }
}

/*----------------------------------------------
    MOD: MARKER OUTLINE
----------------------------------------------*/
.marker-outline .timeline-marker:before {
  background: transparent;
  border-color: #FF6B6B;
}
.marker-outline .timeline-item:hover .timeline-marker:before {
  background: #FF6B6B;
}
</style>

<br/><br/>
<div class="container-fluid">
    <div class="row example-basic">
        <div class="col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2">
            <ul class="timeline">
                <li class="timeline-item">
                    <div class="timeline-info">
                        <span>27 July 2018, 23:21</span>
                    </div>
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h3 class="timeline-title">Consignment Booked</h3>
                        <p>Booked Chennai - Tamil Nadu</p>
                    </div>
                </li>
                <li class="timeline-item">
                    <div class="timeline-info">
                        <span>27 July 2018, 12:01</span>
                    </div>
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h3 class="timeline-title">Consignment Picked Up</h3>
                        <p>Pickedup Chennai - Tamil Nadu</p>
                    </div>
                </li>
               
                <li class="timeline-item">
                    <div class="timeline-info">
                        <span>28 July 2018, 12:01</span>
                    </div>
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h3 class="timeline-title">In Transit -1 (consignment at Origin State) </h3>
                        <p>Arrived Hub Tamil Nadu</p>
                    </div>
                </li>
                <li class="timeline-item">
                    <div class="timeline-info">
                        <span>29 July 2018, 12:01</span>
                    </div>
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h3 class="timeline-title">In Transit -2 (Towards Destination State)</h3>
                        <p>Sorted to Destination</p>
                    </div>
                </li>
                <li class="timeline-item">
                    <div class="timeline-info">
                        <span>30 July 2018, 12:01</span>
                    </div>
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h3 class="timeline-title">In Transit -3 (Towards Destination)</h3>
                        <p>In Transit at Delhi</p>
                    </div>
                </li>
                <li class="timeline-item">
                    <div class="timeline-info">
                        <span>30 July 2018, 12:01</span>
                    </div>
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h3 class="timeline-title">Arrived at Destination </h3>
                        <p>Arrived Hub Delhi </p>
                    </div>
                </li>
               
                <li class="timeline-item">
                    <div class="timeline-info">
                        <span>30 July 2018, 12:01</span>
                    </div>
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h3 class="timeline-title">Out for Delivery </h3>
                        <p>New Delhi</p>
                    </div>
                </li>
                <li class="timeline-item">
                    <div class="timeline-info">
                        <span>31 July 2018, 11:01</span>
                    </div>
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h3 class="timeline-title">Consignment Delivered Successfully</h3>
                        <p>Le Meridien, New Delhi</p>
                    </div>
                </li>
            </ul>
        </div>
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

