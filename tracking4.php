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
              

<style>
.main-timeline .timeline{
    padding: 50px 60px;
    position: relative;
}
.main-timeline .timeline:first-child{
    padding-top: 0;
}
.main-timeline .timeline:last-child{
    padding-bottom: 0;
}
.main-timeline .timeline:before,
.main-timeline .timeline:after{
    content: "";
    display: block;
    width: 100%;
    clear: both;
}
.main-timeline .timeline-icon{
    width: 70px;
    height: 70px;
    line-height: 70px;
    border-radius: 50%;
    background: #837cb6;
    box-sizing: border-box;
    border: 2px solid transparent;
    box-shadow: 0 0 0 2px transparent;
    text-align: center;
    margin: auto 0;
    font-size: 25px;
    color: #fff;
    position: absolute;
    top: 0;
    left: -34px;
    bottom: 0;
    z-index: 2;
}
.main-timeline .timeline:first-child .timeline-icon,
.main-timeline .timeline:last-child .timeline-icon{
    box-sizing: content-box;
    margin: 0;
    top: 0;
    left: -36px;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #837cb6;
}
.main-timeline .timeline:last-child .timeline-icon{
    top: auto;
    bottom: 0;
}
.main-timeline .timeline:nth-child(2n) .timeline-icon{
    left: auto;
    right: -34px;
}
.main-timeline .timeline:last-child:nth-child(2n) .timeline-icon{
    right: -36px;
}
.main-timeline .timeline-content{
    width: 50%;
}
.main-timeline .timeline:nth-child(2n) .timeline-content{
    float: right;
    text-align: right;
}
.main-timeline .title{
    font-size: 18px;
    color: #837cb6;
    margin-top: 0;
    text-transform: uppercase;
}
.main-timeline .description{
    font-size: 15px;
    color: #636363;
    line-height: 25px;
    margin: 0;
}
.main-timeline .border{
    width: 50%;
    border-top: 2px solid #837cb6;
    border-bottom: 2px solid #837cb6;
    border-left: 2px solid #837cb6;
    border-radius: 6px 0 0 6px;
    position: absolute;
    top: 0;
    bottom: -2px;
    left: 0;
    z-index: 1;
}
.main-timeline .timeline:nth-child(2n) .border{
    border-left: none;
    border-right: 2px solid #837cb6;
    border-radius: 0 6px 6px 0;
    left: 50%;
}
.main-timeline .timeline:first-child .border{
    border-top: none;
}
.main-timeline .timeline:last-child .border{
    border-bottom: none;
}
@media only screen and (max-width: 990px){
    .main-timeline .timeline{
        padding: 40px 60px;
        margin: 0 0 0 35px;
    }
    .main-timeline .timeline:nth-child(2n){
        margin: 0 35px 0 0;
    }
    .main-timeline .timeline-content{
        width: 100%;
    }
}
</style>




<br/>
<br/>


<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="main-timeline">
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-thumbs-up"></i></div>
                    <div class="timeline-content">
                        <h4 class="title">Consignment Booked</h4>
                        <p class="description">
                            Booked Chennai - Tamil Nadu (<span>27 JULY 2018, 23:21</span>)
                        </p>
                    </div>
                    <div class="border"></div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-truck"></i></div>
                    <div class="timeline-content">
                        <h4 class="title">Consignment Picked Up</h4>
                        <p class="description">
                            Pickedup Chennai - Tamil Nadu (<span>27 JULY 2018, 23:21</span>)
                        </p>
                    </div>
                    <div class="border"></div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-exchange"></i></div>
                    <div class="timeline-content">
                        <h4 class="title">In Transit -1 (consignment at Origin State)</h4>
                        <p class="description">
                            Arrived Hub Tamil Nadu (<span>27 JULY 2018, 23:21</span>)
                        </p>
                    </div>
                    <div class="border"></div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-exchange"></i></div>
                    <div class="timeline-content">
                        <h4 class="title">In Transit -2 (Towards Destination State)</h4>
                        <p class="description">
                             Sorted to Destination (<span>27 JULY 2018, 23:21</span>)
                        </p>
                    </div>
                    <div class="border"></div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-exchange"></i></div>
                    <div class="timeline-content">
                        <h4 class="title">In Transit -3 (Towards Destination)</h4>
                        <p class="description">
                            In Transit at Delhi (<span>27 JULY 2018, 23:21</span>)
                        </p>
                    </div>
                    <div class="border"></div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-flag"></i></div>
                    <div class="timeline-content">
                        <h4 class="title">Arrived at Destination</h4>
                        <p class="description">
                             Arrived Hub Delhi (<span>27 JULY 2018, 23:21</span>)
                        </p>
                    </div>
                    <div class="border"></div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-truck"></i></div>
                    <div class="timeline-content">
                        <h4 class="title">Out for Delivery</h4>
                        <p class="description">
                            New Delhi (<span>27 JULY 2018, 23:21</span>)
                        </p>
                    </div>
                    <div class="border"></div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-check"></i></div>
                    <div class="timeline-content">
                        <h4 class="title">Consignment Delivered Successfully</h4>
                        <p class="description">
                             Le Meridien, New Delhi (<span>27 JULY 2018, 23:21</span>)
                        </p>
                    </div>
                    <div class="border"></div>
                </div>
            </div>
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

