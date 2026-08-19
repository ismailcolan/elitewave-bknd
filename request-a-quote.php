<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
    <title>Request A Quote ~ Gracious Express</title>

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

        #mobile:invalid {
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


        #name:invalid {
            color: red;
        }

        .form-request .form-control,
        .form-request .select-control {
            margin-bottom: 8px;
        }

        .error {
            color: red;
        }

        @media (min-width: 360px) and (max-width: 575.98px) {
            .selectp .btn-group.bootstrap-select {
                margin-bottom: 25px;
            }
        }
    </style>


</head>


<body>

    <?php include "includes/header.php";
    include "includes/connect.php";
    ?>


    <div class="section-title parallax-bg parallax-light">
        <ul class="bg-slideshow">
            <li>
                <div style="background-image:url(assets/media/bg/request-quote-graciousepxress.jpg)" class="bg-slide"></div>
            </li>
        </ul>
        <div class="section__inner">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12">
                        <h1 class="ui-title-page">Request A Quote</h1>
                        <div class="ui-subtitle-page">Free Shipping Quote</div>
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
                                    <h3 class="ui-title-block ui-title-block_mod-c" style="text-align:center;">Quote Request Form</h3>
                                    <div class="decor-1 decor-1_mod-b" style="text-align:center; margin: 0 auto;"><i class="icon"><i class="fa fa-envelope-o" aria-hidden="true"></i></i></div>
                                    <p>For quote requests, please submit detailed shipment information for a fast response time. We respond to quote requests within 24 hours.</p>
                                </div>

                                <form class="form-request" id="form-request" method="post">
                                    <input type="hidden" name="form_name" id="form_name" value="quote_request" />
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" name="company" id="company" placeholder="Name Of Company " required>
                                        </div><!-- end col -->
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" name="name" id="name" placeholder="Contact Person" required>
                                        </div><!-- end col -->
                                    </div><!-- end row -->
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <input class="form-control" type="email" name="email" id="email" placeholder="Email " required>
                                        </div><!-- end col -->
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" name="mobile" id="mobile" placeholder="phone no " required pattern="\d{10}" minlength=10 maxlength=10 inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;">
                                        </div><!-- end col -->
                                    </div><!-- end row -->
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" name="origin" id="origin" placeholder="Origin [From]" required>
                                        </div><!-- end col -->
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" name="destination" id="destination" placeholder="Destination [To]" required>
                                        </div><!-- end col -->
                                    </div><!-- end row -->

                                    <div class="row">

                                        <div class="col-sm-6 selectp">
                                            <select class="selectpicker" name="mode" id="mode" placeholder="Mode of Transport" required>

                                                <?php
                                                $query = mysqli_query($conn, "select * from mode_of_transportation where status='0'");
                                                while ($row = mysqli_fetch_array($query)) {
                                                    echo '<option value="' . $row['mode_id'] . '">' . $row['mode_type'] . '</option>';
                                                }
                                                ?>


                                            </select>
                                        </div><!-- end col -->
                                        <div class="col-sm-6">
                                            <select class="selectpicker" name="type" id="type" placeholder="Package Type" required>
                                                <?php
                                                $query = mysqli_query($conn, "select * from package where status='0'");
                                                while ($row = mysqli_fetch_array($query)) {
                                                    echo '<option value="' . $row['package_id'] . '">' . $row['package_code'] . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div><!-- end col -->


                                    </div><!-- end row -->
                                    </br>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <input class="form-control" type="text" name="weight" id="weight" placeholder="Approx. Weight [Kg]" required inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                        </div><!-- end col -->

                                    </div><!-- end row -->


                                    <div class="row">
                                        <div class="col-xs-12">
                                            <textarea class="form-control" placeholder="Description " name="description" id="description" required rows="6"></textarea>
                                            <button type="button" id="quote" class="btn btn_mod-a btn-sm btn-effect pull-right"><span class="btn__inner">REQUEST QUOTE</span></button>
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
    <script src="assets/js/jquery.validate.min.js"></script>


    <script type="text/javascript">
        $(document).ready(function() {
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

            $(document).on('keypress', '#mobile,#weight', function(evt) {
                var value = $(this).val();
                var length = value.length;
                //alert(length);
                var charCode = (evt.which) ? evt.which : event.keyCode;
                if ((value.indexOf('.') != -1) && (charCode != 45 && (charCode < 48 || charCode > 57))) {
                    return false;
                } else if (charCode != 45 && (charCode != 46 || $(this).val().indexOf('.') != -1) && (charCode < 48 || charCode > 57)) {
                    return false;
                } else if (length > 9) {
                    return false;
                }
                return true;
            });

            $(document).on('click', '#quote', function(ev) {
                if ($("#form-request").valid() == true) {
                    //$(".form-data-saving").show();
                    var data = $("#form-request").serialize();

                    $.ajax({
                        cache: false,
                        url: 'includes/save_details.php', // url where to submit the request
                        type: "post", // type of action POST || GET
                        async: false,
                        dataType: 'json', // data type
                        data: data, // post data || get data
                        success: function(data) {
                            console.log(data);
                            alert("Quote Request Sent Successfully : Your Request Ref.NO is " + data['request_no']);
                            location.reload();

                        },
                        error: function(xhr, resp, text) {
                            console.log(xhr, resp, text);
                            chk_val = false;
                        }
                    });

                }
            });

        });
    </script>


</body>

</html>