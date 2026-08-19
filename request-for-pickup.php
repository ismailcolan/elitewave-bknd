<!DOCTYPE html>
<html lang="en">
<?php
require_once('web/include/connect.php');
?>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
    <title>Request For Pickup ~ Gracious Express</title>

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

        #contact_no:invalid {
            color: red;
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

        .form-request .form-control,
        .form-request .select-control {
            margin-bottom: 8px;
        }

        .error {
            color: red;
        }

        @media (min-width: 360px) and (max-width: 575.98px) {
            .request .btn-group.bootstrap-select {
                margin-bottom: 25px;
            }
        }
    </style>


</head>


<body>

    <?php include "includes/header.php" ?>


    <div class="section-title parallax-bg parallax-light">
        <ul class="bg-slideshow">
            <li>
                <div style="background-image:url(assets/media/bg/request-for-pickup.jpg)" class="bg-slide"></div>
            </li>
        </ul>
        <div class="section__inner">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12">
                        <h1 class="ui-title-page">Request For Pickup</h1>
                        <div class="ui-subtitle-page">Request Now</div>
                        <div class="decor-2 decor-2_mod-a decor-2_mod_white"></div>
                    </div><!-- end col -->
                </div><!-- end row -->
            </div><!-- end container -->
        </div><!-- end section__inner -->
    </div><!-- end section-title -->




    <br /><br />


    <div>



        <div class="container">



            <div class="section_mod-c">
                <div class="container">
                    <div class="row">
                        <div class="col-md-offset-2 col-md-8">
                            <section class="section-form-request">
                                <div class="wrap-title-block wrap-title-block_mod-c">
                                    <h3 class="ui-title-block ui-title-block_mod-c" style="text-align:center;">Request For Pickup</h3>
                                    <div class="decor-1 decor-1_mod-b" style="text-align:center; margin: 0 auto;"><i class="icon"><i class="fa fa-envelope-o" aria-hidden="true"></i></i></div>
                                </div>

                                <form class="form-request" method="post" id="pickup_form">
                                    <input type="hidden" name="form_name" id="form_name" value="add_pickup">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <label class="radio-inline">
                                                <input type="radio" name="optradio" checked="checked"> New Customer
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" name="optradio" onclick="window.location='login.php';">Existing or registered Customer
                                            </label>
                                        </div>
                                    </div>
                                    </br>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" name="pickup_id" placeholder="RFP/000001" disabled>
                                        </div><!-- end col -->
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" id="company" name="company" placeholder="Name of Company*" required autocomplete="off">
                                        </div><!-- end col -->
                                    </div><!-- end row -->

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" name="contact_person" id="contact_person" placeholder="Contact Person" autocomplete="off">
                                        </div><!-- end col -->
                                        <div class="col-sm-6">
                                            <input class="form-control" type="email" name="email" id="email" placeholder="Email *" required autocomplete="off">
                                        </div><!-- end col -->
                                    </div><!-- end row -->

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" id="contact_no" name="contact_no" minlength=10 maxlength=10 placeholder="Contact No *" required inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                        </div><!-- end col -->
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" id="origin" name="origin" placeholder="Origin [From]" autocomplete="off">
                                        </div><!-- end col -->
                                    </div><!-- end row -->

                                    <div class="row">

                                        <div class="col-sm-6 ">
                                            <input class="form-control" type="text" id="destination" name="destination" placeholder="Destination [To]" autocomplete="off">
                                        </div><!-- end col -->

                                        <div class="col-sm-6 request">
                                            <select class="selectpicker" name="mode_of_transportation" id="mode_of_transportation">
                                                <option value="">Select Mode</option>
                                                <?php
                                                echo $mode_query = "select * from mode_of_transportation where status=0";
                                                $mode_result = mysqli_query($conn, $mode_query);
                                                while ($mode_row = mysqli_fetch_array($mode_result)) {
                                                ?>
                                                    <option value="<?php echo $mode_row['mode_id']; ?>"><?php echo $mode_row['mode_type']; ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                        </div><!-- end col -->

                                    </div><!-- end row -->
                                    <div class="row">
                                        <div class="col-sm-6 ">
                                            <input class="form-control" type="text" name="no_of_package" id="no_of_package" placeholder="No. of Packages" required autocomplete="off" onpaste="return false;">
                                        </div><!-- end col -->
                                        <div class="col-sm-6 request">
                                            <select class="selectpicker" name="package_type" id="package_type">

                                                <option value="">Select Package</option>
                                                <?php
                                                $package_query = "select * from package where status=0";
                                                $package_result = mysqli_query($conn, $package_query);
                                                while ($package_row = mysqli_fetch_array($package_result)) {
                                                ?>
                                                    <option value="<?php echo $package_row['package_id']; ?>"><?php echo $package_row['package_code']; ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                        </div><!-- end col -->
                                    </div><!-- end row -->
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" placeholder="Approx. Weight [Kg]" name="approx_weight" id="approx_weight" required onpaste="return false;" autocomplete="off">
                                        </div><!-- end col -->
                                    </div><!-- end row -->


                                    <div class="row">
                                        <div class="col-xs-12">
                                            <textarea class="form-control" placeholder="Additional Instructions/Arrival Information" required rows="6" name="description"></textarea>
                                            <button type="button" class="btn btn_mod-a btn-sm btn-effect pull-right" id="reset"><span class="btn__inner">Reset</span></button>
                                            <button type="button" class="btn btn_mod-a btn-sm btn-effect pull-right" id="save"><span class="btn__inner">Submit for Pickup</span></button>
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
    <script src="web/javascripts/jquery.validate.js"></script>


    <script type="text/javascript">
        $(document).ready(function() {
    	$(document).on('keypress', '#approx_weight,#no_of_package', function(evt){
				var value = $(this).val();
				var length = value.length;
				//alert(length);
				var charCode = (evt.which) ? evt.which : event.keyCode;
				if((value.indexOf('.')!=-1) && (charCode != 45 && (charCode < 48 || charCode > 57))){
					return false;
				}    
				else if(charCode != 45 && (charCode != 46 || $(this).val().indexOf('.') != -1) && (charCode < 48 || charCode > 57)){
					return false;
				}
				// else if(length > 9){
				// 	return false;
				// }
				return true;
			});
            $("select").change(function() {
                $(this).find("option:selected").each(function() {
                    var optionValue = $(this).attr("value");
                    if (optionValue) {
                        $(".map-box").not("." + optionValue).hide();
                        $("." + optionValue).show();
                    } else {
                        $(".map-box").hide();
                    }
                });
            }).change();
            $(document).on('click', '#save', function() {
                var data = $('#pickup_form').serialize();
                if ($('#pickup_form').valid() == true) {
                    $.ajax({
                        url: "includes/save_details.php",
                        type: "post",
                        data: data,
                        dataType: "json",
                        success: function(result) {
                            console.log(result);
                            if (result['pickup_ref_id'] != 0) {
                                alert("Data Saved Successfully your pickup ref id" + result['pickup_ref_id']);
                                location.reload();

                            } else {


                            }
                        },

                    });

                }
            });

        });
    </script>


</body>

</html>