<!DOCTYPE html>
<html lang="en">
	<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
	<title>Gracious Express - Expected Delivery</title>

	<link href="assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">
	<link href="assets/css/master.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@3.5.2/animate.min.css">
	
	<!-- <link rel="stylesheet" href="web/stylesheets/bootstrap.min.css"> -->
	<link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	
	<!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> -->
	
	<link href="https://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css" rel="Stylesheet"></link>

	<script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
	<script src="assets/js/modernizr.custom.js"></script>
	<style>
	body {
	margin: 0;
	font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
	font-size: 1rem;
	font-weight: 400;
	line-height: 1.5;
	color: #212529;
	text-align: left;
	background-color: #fff;
}
.error{
    border:1px solid red !important;
}
#error-date
{
    font-size: 80%;
    font-weight: 400;
    float: left;
    position: relative;
    top: -16px;
    color: red;
}
#error-origin{
    font-size: 80%;
    font-weight: 400;
    float: left;
    position: relative;
    top: -16px;
    color: red;
}
#error-destination{
    font-size: 80%;
    font-weight: 400;
    float: left;
    position: relative;
    top: -13px;
    color: red;
}
#error-kgs{
    font-size: 80%;
    font-weight: 400;
    float: left;
    position: relative;
    top: -16px;
    color: red;
}
#error-kgs1{
    font-size: 80%;
    font-weight: 400;
    float: left;
    position: relative;
    top: -16px;
    color: red;
}



*{
    box-sizing: border-box;
}
.ratecalOverlay{
    position: fixed; /* Sit on top of the page content */
    display: none; /* Hidden by default */
    width: 100%; /* Full width (cover the whole page) */
    height: 100%; /* Full height (cover the whole page) */
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0,0,0,0.8); /* Black background with opacity */
    z-index: 2; /* Specify a stack order in case you're using a different order for other elements */
    cursor: pointer; /* Add a pointer on hover */
    justify-content: center;
    align-items: center;
    
}
.ratecalOverlay__content{
    color : #fff;
}
.ratecalOverlay__content i{
    animation: mymove 1s linear infinite;
}

@keyframes mymove {
    100% {transform: rotate(360deg);} 
  }

.ratecal__banner{
    background: url('images/brandbg.jpg') no-repeat center center ;
    height: 250px;
    width: 100%;
    background-size: cover;
}
.ratecal__bannerNote{
    background-color: rgb(245, 245, 245);
}
.ratecal__bannerNote p{
    font-size: 12px;
    font-weight: 500;
    padding: 13px 0 13px 25px;
    letter-spacing: 1px;
}
.ratecal__body{
    padding-top: 80px;
    padding-bottom: 80px;
}
.ratecal__body__partitions{
    
}
.ratecal__body__partitions input{
    margin-bottom: 20px;
    border: 1px solid #ccc !important;
    width: 100%;
    height: 44px;
    font-size: 12px;
    padding: 0 15px;
}
.ratecal__body__partitions button{
    height: 44px;
    width: 100%;
    border-radius: 1px;
}
.ratecal__body__partitions input:focus {
    outline: none !important;
    border:1px solid red !important;
  }
  .ratecal__body__tarif h5{
    color : seagreen;
    display: inline-block;
    border-bottom : 2px solid seagreen;
    font-size: 28px ! important;
    font-weight: 700;
    margin-bottom: 1rem;
  }
  .ratecal__body__tarif p{
    margin-bottom: 4px;
    font-size: 22px;
    font-weight: 700;
    color: dimgrey;
}
  .ratecal__footerNote{
    background-color: rgb(245, 245, 245);
  }
  .ratecal__footerNote__left__btn{
    background-color: #01743d;
    color : #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 45px;
  }
  .ratecal__footerNote__right p{
      padding-top: 5px;
      padding-bottom: 5px;
    text-align: justify;
    font-size: 12px;
    letter-spacing: 0.8px;
}
.input-wrapper{
    position: relative;
}
.autocomplete{
    position: absolute;
    top : 44px;
    left : 0px;
}
.btn-outline-danger:hover {
    color: #fff;
    background-color: #dc3545;
    border-color: #dc3545;
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
								<h1 class="ui-title-page">Expected Delivery</h1>
								<div class="ui-subtitle-page">Search Your Origin and Destination</div>
								<div class="decor-2 decor-2_mod-a decor-2_mod_white"></div>
							</div><!-- end col -->
						</div><!-- end row -->
					</div><!-- end container -->
				</div><!-- end section__inner -->
			</div><!-- end section-title -->

			<div class="ratecalOverlay">
        <div class="ratecalOverlay__content">
            <i class="fas fa-cog"></i>
            Please wait
        </div>
    </div>

	<div class="ratecal__body">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-sm-12 col-md-4 ratecal__body__partitions">
                        
                    </div>
                    <div class="col-sm-12 col-md-4 ratecal__body__partitions border-right text-center">
                        <form id="rate-form">
                        <input type="text"  name="datepicker" id="datepicker" class="date" autocomplete="off" placeholder="DD-MM-YYYY" >
                        <small id = "error-date"></small>
                        <div class="input-wrapper">
                            <span id = "error"></span>
                            <input type="text" placeholder="Origin (city/pincode)" name="origin" id="origin" class="origin search">
                            <div id="autocomplete" class="autocomplete"></div>
                            <small id = "error-origin"></small>
                        </div>
                        <div class="input-wrapper">
                            <input type="text" placeholder="Destination (city/pincode)" name="destination" id="destination" value="" class="destination search1" >
                            <small id = "error-kgs"></small>
                            <div id="autocomplete1" class="autocomplete"></div>
                        </div>
                        <input type="submit"  class="btn btn-outline-danger" id="calculate" value="Find">
                        </form>
                    </div>
  					<div class="col-sm-12 col-md-4 ratecal__body__partitions ratecal__body__tarif" id="no_location" style="display:none">
                    <p>Sorry : <span id="error_delivery"> <i class="fa fa-frown-o"></i> </span></p>
                    </div>
                    <div class="col-sm-12 col-md-4 ratecal__body__partitions ratecal__body__tarif" id="estimation" style="display:none">
                    <h5 class="">Estimated Delivery</h5>
  							
  						
                        <p>Surface Date : <span id="surface_rate"> <i class="fas fa-rupee-sign"></i></span></p>
                        <p>Express Date : <span id="express_rate"><i class="fas fa-rupee-sign"></i></span></p>
                        <p>Train Date : <span id="train_rate"><i class="fas fa-rupee-sign"></i> </span></p>
                        <p>Air Date : <span id="air_rate"><i class="fas fa-rupee-sign"></i> </span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="ratecal__footerNote">
            <div class="col-sm-12 ">
                <div class="row">
                    <div class="col-sm-12 col-md-2 ratecal__footerNote__left px-0">
                        <div class="ratecal__footerNote__left__btn">
                            <p class="mb-0"> <i class="fa fa-exclamation-circle"></i> Note</p> 
                        </div>                          
                    </div>
                    <div class="col-sm-12 col-md-10 ratecal__footerNote__right">
                        <p id="notes">
                            The rates
                            shown are indicative and not the final price and include freight, basic, DFS, Risk
                            on Value, State and ODA Charges. The final price would depend on, the exact volume, the
                            value
                            of the shipment and special service charges(if any). Charges are exclusive of taxes
                            (Minimum
                            weight of 20 Kgs is considered).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>

		 <div class="section-clients section-bg_mod-a wow">
			
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
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
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
		<script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
     <script src="https://code.jquery.com/ui/1.10.2/jquery-ui.js" ></script>	
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" /> -->
		
		<script type="text/javascript">
        $(document).ready(function(){

            $("#datepicker").datepicker({
                minDate : "-3M -15D" ,
                maxDate : "+3M +15D",
                changeMonth:true,
                changeYear:true,
                dateFormat: "dd-mm-yy" 
            });
            $("#calculate").click(function(e){
                e.preventDefault();
                //alert("hi");

                var origin = $("#origin").val();
                var destination = $("#destination").val();
                var date = $("#datepicker").val();

                if(date == ""){
                var message = "Please Select Date";
                $('#error-date').text(message);
                }else if(origin == ""){
                $('#error-date').text("");
                var message = "Fill Origin Fields";
                $('#error-origin').text(message);
                }else if(destination == ""){
                $('#error-origin').text("");
                var message = "Fill Destination Fields";
                $('#error-kgs').text(message);
                // let findBtn = document.querySelector('#calculate');
                // let ratecalOverlay = document.querySelector('.ratecalOverlay');
                // findBtn.addEventListener('click', () => {
                //     ratecalOverlay.style.display = 'flex';
                //     setTimeout( () => {
                //         ratecalOverlay.style.display = 'none';
                //     }, 2000)
                // });
                }else{
                    $.ajax({
                        url:"fetch-details.php",
                        type:"post",
                        dataType:"json",
                        data:{cmd:"get_expected_delivery_form",origin:origin,destination:destination,date:date},
                        success:function(data){
                        if(data == 0){
                       
                       console.log("Test");
                         $("#no_location").show();
                        $("#estimation").hide();
                       
                         $("#error_delivery").html("<i class='fa fa-frown-o'> Location Not Found </i>");
                        }else{
                        //console.log(data);
                            $('#error-kgs').text("");
                            $("#estimation").show();
                         	$("#no_location").hide();
                            $("#surface_rate").html("<i class='fa fa-truck'> " + data.surface + "</i>");
                            $("#express_rate").html("<i class='fa fa-truck'> " + data.express + "</i>");
                            $("#train_rate").html("<i class='fa fa-train'> " + data.train + "</i>");
                            $("#air_rate").html("<i class='fa fa-plane'> " + data.air + "</i>");
                            $("#notes").html(data.note);
                            $('#error-kgs1').text("");
                            $('#error-origin').text("");
                            $('#error-date').text("");
                        }
                            
                        }
                    });
                }
            });

            $(".search").on('keyup',function(){
                var term = $(this).val();
                console.log("http://localhost/graciousexpress/autocomplete.php?autocomplete=origin_expected_delivery&term="+term);
                $("#origin").autocomplete({
                    source:"http://localhost/graciousexpress/autocomplete.php?autocomplete=origin_expected_delivery&term="+term,
                    minLength : 0,
                    appendTo:"#autocomplete",
                    open:function(){
                        var position = $("#autocomplete").position(),
                        left = position.left, top = position.top;

                        $("#autocomplete > ul").css({
                            left:left + 20 + "px",
                            top:top + -14 + "px"
                        });
                    },
                    select : function(event,ui){
                        console.log(ui.item.value);
                       // alert(ui.item.value);
                        $("#origin").val(ui.item.value);
                        $("#origin").val(ui.item.id);
                    }
                });

            });


            $(".search1").on('keyup',function(){
                var term = $(this).val();
                console.log("http://localhost/graciousexpress/autocomplete.php?autocomplete=destination_expected_delivery&term="+term);


                $("#destination").autocomplete({
                    source:"http://localhost/graciousexpress/autocomplete.php?autocomplete=destination_expected_delivery&term="+term,
                    minLength : 0,
                    appendTo:"#autocomplete1",
                    open:function(){
                        var position = $("#autocomplete1").position(),
                        left = position.left, top = position.top;

                        $("#autocomplete1 > ul").css({
                            left:left + 20 + "px",
                            top:top + -14 + "px"
                        });
                    },
                    select : function(event,ui){
                        //console.log(ui.item.value);
                       // alert(ui.item.value);
                        $("#destination").val(ui.item.value);
                        $("#destination").val(ui.item.id);
                    }
                });
            });
        }); 

    </script>   

	</body>
</html>

