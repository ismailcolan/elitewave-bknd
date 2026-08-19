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
.main-timeline{
    overflow: hidden;
    position: relative;
}
.main-timeline .timeline{
    width: 50%;
    float: left;
    z-index: 1;
    position: relative;
}
.main-timeline .timeline:before,
.main-timeline .timeline:after{
    content: "";
    display: block;
    clear: both;
}
.main-timeline .timeline:before{
    content: "";
    width: 40px;
    height: 90%;
    background: #727cb6;
    position: absolute;
    top: 10%;
    right: -20px;
}
.main-timeline .timeline:last-child:before{ height: 0; }
.main-timeline .timeline-icon{
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #727cb6;
    overflow: hidden;
    text-align: center;
    position: absolute;
    top: 0;
    right: -40px;
    z-index: 3;
}
.main-timeline .timeline-icon:before{
    content: "";
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #727cb6;
    box-shadow: 0 0 0 4px #a5afe4;
    margin: auto;
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
}
.main-timeline .timeline-icon i{
    font-size: 35px;
    color: #303a3b;
    line-height: 80px;
    z-index: 1;
    position: relative;
}
.main-timeline .year{
    display: block;
    padding: 0 60px 0 30px;
    font-size: 30px;
    color: #303a3b;
    text-align: right;
    border-bottom: 2px solid #303a3b;
    z-index: 2;
    position: relative;
}
.main-timeline .year:before{
    content: "";
    display: block;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #727cb6;
    border: 5px solid #fff;
    box-shadow: 0 0 0 4px #727cb6;
    margin: auto;
    position: absolute;
    bottom: -15px;
    left: 4px;
}
.main-timeline .year:after{
    content: "";
    border-left: 10px solid #303a3b;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    position: absolute;
    bottom: -11px;
    left: 50px;
}
.main-timeline .timeline-content{
    padding: 18px 60px 18px 40px;
    text-align: right;
    position: relative;
    z-index: 1;
}
.main-timeline .timeline-content:before,
.main-timeline .timeline-content:after{
    content: "";
    width: 80px;
    height: 150px;
    border-radius: 50%;
    background: #fff;
    position: absolute;
    top: -7%;
    right: 15px;
    z-index: -1;
}
.main-timeline .timeline-content:after{
    left: auto;
    right: -95px;
}
.main-timeline .timeline:last-child .timeline-content:before,
.main-timeline .timeline:last-child .timeline-content:after{
    width: 0;
    height: 0;
}
.main-timeline .title{
    font-size: 22px;
    font-weight: bold;
    color: #727cb6;
    margin-top: 0;
}
.main-timeline .description{
    font-size: 15px;
    color: #7f8386;
    line-height: 25px;
}
.main-timeline .timeline:nth-child(2){ margin-top: 140px; }
.main-timeline .timeline:nth-child(even){ margin-bottom: 80px; }
.main-timeline .timeline:nth-child(odd){ margin: -140px 0 0 0; }
.main-timeline .timeline:first-child,
.main-timeline .timeline:last-child:nth-child(even){
    margin: 0 !important;
}
.main-timeline .timeline:nth-child(2n):before,
.main-timeline .timeline:nth-child(2n) .timeline-icon{
    right: auto;
    left: -20px;
}
.main-timeline .timeline:nth-child(2n) .timeline-icon{ left: -40px }
.main-timeline .timeline:nth-child(2n) .year{
    padding: 0 30px 0 60px;
    text-align: left;
}
.main-timeline .timeline:nth-child(2n) .year:before{
    left: auto;
    right: 3px;
}
.main-timeline .timeline:nth-child(2n) .year:after{
    border-left: none;
    border-right: 10px solid #303a3b;
    right: 50px;
}
.main-timeline .timeline:nth-child(2n) .timeline-content{
    padding: 18px 40px 18px 60px;
    text-align: left;
}
.main-timeline .timeline:nth-child(2n) .timeline-content:before{ left: -95px; }
.main-timeline .timeline:nth-child(2n) .timeline-content:after{ left: 15px; }
.main-timeline .timeline:nth-child(2n):before,
.main-timeline .timeline:nth-child(2n) .timeline-icon{ background: #e77e21; }
.main-timeline .timeline:nth-child(2n) .timeline-icon:before{
    border-color: #e77e21;
    box-shadow: 0 0 0 4px #f1a563;
}
.main-timeline .timeline:nth-child(2n) .year:before{
    background: #e77e21;
    box-shadow: 0 0 0 4px #e77e21;
}
.main-timeline .timeline:nth-child(2n) .title{ color: #e77e21; }
.main-timeline .timeline:nth-child(3n):before,
.main-timeline .timeline:nth-child(3n) .timeline-icon{ background: #008b8b; }
.main-timeline .timeline:nth-child(3n) .timeline-icon:before{
    border-color: #008b8b;
    box-shadow: 0 0 0 4px #50b5b4;
}
.main-timeline .timeline:nth-child(3n) .year:before{
    background: #008b8b;
    box-shadow: 0 0 0 4px #008b8b;
}
.main-timeline .timeline:nth-child(3n) .title{ color: #008b8b; }
.main-timeline .timeline:nth-child(4n):before,
.main-timeline .timeline:nth-child(4n) .timeline-icon{
    background: #ed687c;
}
.main-timeline .timeline:nth-child(4n) .timeline-icon:before{
    border-color: #ed687c;
    box-shadow: 0 0 0 4px #f798a8;
}
.main-timeline .timeline:nth-child(4n) .year:before{
    background: #ed687c;
    box-shadow: 0 0 0 4px #ed687c;
}
.main-timeline .timeline:nth-child(4n) .title{ color: #ed687c; }
@media only screen and (max-width: 990px){
    .main-timeline .timeline{ width: 100%; }
    .main-timeline .timeline:nth-child(even),
    .main-timeline .timeline:nth-child(odd){
        margin: 0;
    }
    .main-timeline .timeline:before,
    .main-timeline .timeline:nth-child(2n):before{
        width: 30px;
        height: 100%;
        left: 25px;
    }
    .main-timeline .timeline-icon,
    .main-timeline .timeline:nth-child(2n) .timeline-icon{
        left: 0;
    }
    .main-timeline .year,
    .main-timeline .timeline:nth-child(2n) .year{
        text-align: left;
        padding: 0 30px 0 100px;
    }
    .main-timeline .year:before,
    .main-timeline .timeline:nth-child(2n) .year:before{
        left: auto;
        right: 4px;
    }
    .main-timeline .year:after{
        left: auto;
        right: 50px;
        border-right: 10px solid #303a3b;
        border-left: none;
    }
    .main-timeline .timeline-content,
    .main-timeline .timeline:nth-child(2n) .timeline-content{
        text-align: left;
        padding: 18px 40px 18px 100px;
    }
    .main-timeline .timeline-content:before,
    .main-timeline .timeline-content:after{
        width: 0;
        height: 0;
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
                    <div class="timeline-icon"><i class="fa fa-globe"></i></div>
                    <span class="year">27 JULY 2018, 23:21</span>
                    <div class="timeline-content">
                        <h5 class="title">Consignment Booked</h5>
                        <p class="description">
                            Booked Chennai - Tamil Nadu </p>
                    </div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-rocket"></i></div>
                    <span class="year">2016</span>
                    <div class="timeline-content">
                        <h5 class="title">Consignment Picked Up
</h5>
                        <p class="description">
                          Arrived Hub Tamil Nadu


                        </p>
                    </div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-briefcase"></i></div>
                    <span class="year">2015</span>
                    <div class="timeline-content">
                        <h5 class="title">In Transit -1 (consignment at Origin State)
</h5>
                        <p class="description">
                           Arrived Hub Tamil Nadu


                        </p>
                    </div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-globe"></i></div>
                    <span class="year">2017</span>
                    <div class="timeline-content">
                        <h5 class="title">In Transit -2 (Towards Destination State)
</h5>
                        <p class="description">
                           Sorted to Destination


                        </p>
                    </div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-rocket"></i></div>
                    <span class="year">2016</span>
                    <div class="timeline-content">
                        <h5 class="title">In Transit -3 (Towards Destination)</h5>
                        <p class="description">
                           In Transit at Delhi


                        </p>
                    </div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-briefcase"></i></div>
                    <span class="year">2015</span>
                    <div class="timeline-content">
                        <h5 class="title">Arrived at Destination</h5>
                        <p class="description">
                            Arrived Hub Delhi


                        </p>
                    </div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-rocket"></i></div>
                    <span class="year">2016</span>
                    <div class="timeline-content">
                        <h5 class="title">Out for Delivery</h5>
                        <p class="description">
                           New Delhi


                        </p>
                    </div>
                </div>
                <div class="timeline">
                    <div class="timeline-icon"><i class="fa fa-briefcase"></i></div>
                    <span class="year">2015</span>
                    <div class="timeline-content">
                        <h5 class="title">Consignment Delivered Successfully</h5>
                        <p class="description">
                           Le Meridien, New Delhi


                        </p>
                    </div>
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

