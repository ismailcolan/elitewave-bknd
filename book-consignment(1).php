<?php
$conn = mysqli_connect("localhost", "staging", "vySzrpsqDRupDHS", "staging");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
    <title>Gracious Express - Book Consignment</title>

    <link href="favicon.png" type="image/x-icon" rel="shortcut icon">
    <link href="assets/css/master.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@3.5.2/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- book consignment css and js starts here -->
    <link rel="stylesheet" href="assets/css/book-consignment.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <!-- book consignment css and js finished here -->
    <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
    <script src="assets/js/jquery.validate.min.js"></script>
    <script src="assets/js/modernizr.custom.js"></script>
</head>
<style>
    #sender-contact-no:invalid {
        color: red;
    }


    #reciever-contact-no:invalid {
        color: red;
    }


    	#sender-email-error,
	#reciever-email-error,
	#reciever-city-error,
	#no-of-package-error,
	#package-invoice-error,
	#package-content-error,
	#package-qty-error,
	#package-net-wgt-error,
	#package-gross-wgt-error,
	#reciever-name-error,
	#reciever-contact-no-error,
	#package_type-error,
	#sender-contact-no-error {
		color: red !important;
		;
		font-size: 10px !important;
		margin-top: -23px !important;
		margin-left: 3px !important;
	}

    .p_css {
        position: absolute;
        top: 73px;
        left: 18px;
        font-size: 11px;
    }
    label#length-error {
    position: absolute;
    bottom: 4px;
    left: 21px;
    font-size: 8px;
    color: red;
}

    label#width-error {
        position: absolute;
        bottom: 4px;
        left: 120px;
        font-size: 8px;
        color: red;

    }

    label#height-error {
        position: absolute;
        bottom: 4px;
        left: 225px;
        font-size: 8px;
        color: red;

    }

    label#quantity-error {
        position: absolute;
        bottom: 4px;
        left: 328px;
        font-size: 8px;
        color: red;

    }

    .select2-container {

        width: 64% !important;
    }

    .select2-container--default .select2-selection--single {
        height: 45px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: white;
        line-height: 28px;
    }

    .select2-container--default .select2-selection--single {
        background-color: #fff;
        border: 1px solid #aaa;
        border-radius: 4px;
        width: 100%;
        display: flex;
        justify-content: space-between;
        text-align: left;
        border-color: #1259cf6b;
        background-color: #163a85;
        color: white;
        text-indent: 12px;
        padding: 0.5em 0.5em;
        border-radius: 0.1em;
        cursor: pointer;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #fff transparent transparent transparent;
        top: 100%;

    }

    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: #ffffff00 transparent #fff7f7 transparent;
        border-width: 0 4px 5px 4px;
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: #2e589b;
        color: white;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: -4px;
    }

    .select2-container--open .select2-dropdown {
        left: 0;
        top: 9px;
    }

    .select2-container--default .select2-results>.select2-results__options {
        max-height: 253px;
        overflow-y: auto;
        font-size: 1.0rem;
    }

    .select2-results {
        max-height: 253px;
        padding: 0 0 0 4px;
        margin: 4px 4px 4px 0;
        position: relative;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
    }
    @media (min-width: 360px) and (max-width: 576.98px) {
        .send-rcv-dtl .volumetric-input-boxes {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    border-bottom: 1px solid gray;
    margin-top: 10px;
}
label#width-error, label#length-error, label#quantity-error, label#height-error {
    display: none!important;
} 
.volumetric-input-boxes .form-control {
    width: 40%;
}
.form-control.has-error, .form-control.error {
    border-color: #d9534f;
}
    }
</style>

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

                <br />

                <div class="parent-block">
                    <div class="block" id="select-customer">
                        <h5>I Am a ...</h5>
                        <div class="row">
                            <div class="col-xs-6 col-sm-6 cust-padding-margin" id="new-cust">
                                <div class="sub-block" id="a">
                                    <h6>New Customer </h6>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-6 cust-padding-margin" id="exist-cust">
                                <div class="sub-block">
                                    <h6>Existing Customer</h6>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block" id="select-shipping">
                        <h5>I Prefer Mode of Shipping Through ...</h5>
                        <div class="row">
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
                                <div class="sub-block" id="by-air">
                                    <span class="fa fa-plane custom-icon-size"></span>

                                    <h6>By Air</h6>
                                    <span id="span" style="display:none;">1</span>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
                                <div class="sub-block" id="by-train">
                                    <span class="fa fa-subway custom-icon-size"></span>

                                    <h6>By Train</h6>
                                    <span id="span" style="display:none;">2</span>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
                                <div class="sub-block" id="by-road-surface">
                                    <span class="fa fa-road custom-icon-size"></span>
                                    <h6>By Road</h6>
                                    <p class="text-center">Surface</p>
                                    <span id="span" style="display:none;">4</span>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
                                <div class="sub-block" id="by-road-express">
                                    <span class="fa fa-truck custom-icon-size"></span>
                                    <h6>By Road</h6>
                                    <p class="text-center">Express</p>
                                    <span id="span" style="display:none;">3</span>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
                                <div class="sub-block" id="by-local">
                                    <span class="fa fa-motorcycle custom-icon-size"></span>

                                    <h6>Local Delivery</h6>
                                    <span id="span" style="display:none;">5</span>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="block" id="select-load">
                        <h5>Select Load Type ...</h5>
                        <div class="row">
                            <div class="col-xs-6 col-sm-6 cust-padding-margin" id="full-truck">
                                <div class="sub-block" id="by-surface-ftl">
                                    <h6>Full Truck Load</h6>
                                    <span id="span" style="display:none;">7</span>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-6 cust-padding-margin" id="partial-truck">
                                <div class="sub-block" id="by-surface-ptl">
                                    <h6>Partial Truck Load</h6>
                                    <span id="span" style="display:none;">8</span>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block" id="visit-login-page">
                        <div class="col-sm-12 cust-padding-margin py-0">
                            <div class="sub-block">
                                <a href="http://localhost/graciousexpress/user/login.php" type="button" class="btn btn-danger btn-lg">Visit Login Page</a>

                                <p class="text-center">Visit our login page to access our tools and portals in order to book new shipments.</p>
                            </div>
                        </div>
                    </div>

                    <!--Truck Type-->
                    <div class="block" id="select-truck">
                        <h5>Select Truck Type ...</h5>
                        <div class="row">
                            <div class="dropdown">
                                <select class="dropp" role="menu" aria-labelledby="menu1" id="dropp">
                                    <option value="">Select Truck Type...</option>
                                    <option value="">Single Axle Vehicle: 07MT</option>
                                    <option value="">Multi Axle Vehicle : 10MT/14MT/17MT</option>
                                    <option value="">22ft Vehicle : 07MT</option>
                                    <option value="">18ft Vehicle : 06MT</option>
                                    <option value="">Eicher 19 Vehicle : 7MT/8MT/9MT</option>
                                    <option value="">Eicher 17 Vehicle : 5MT</option>
                                    <option value="">Eicher 19 Vechicle:4MT</option>
                                    <!-- <option value="">Eicher 19 Vechicle:4MT</option> -->
                                </select>
                            </div>
                        </div>
                        <!--- Truck Type -->
                        <!-- <div class="row">
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type1">
                                    <h6>Single Axle Vehicle: 07MT</h6>
                                    <small class="text-center">32ft L * 8ft W * 9.5ft H = 65CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type2">
                                    <h6>Multi Axle Vehicle : 10MT/14MT/17MT</h6>
                                    <small class="text-center">32ft L * 8ft W * 9.5ft H = 65CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type3">
                                    <h6>22ft Vehicle : 07MT</h6>
                                    <small class="text-center">22ft L * 8ft W * 8ft H = 38CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type4">
                                    <h6>18ft Vehicle : 06MT</h6>
                                    <small class="text-center">18ft L * 8ft W * 8ft H = 31CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type5">
                                    <h6>Eicher 19 Vehicle : 7MT/8MT/9MT</h6>
                                    <small class="text-center">19ft L * 7ft W * 7ft H = 25CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type6">
                                    <h6>Eicher 17 Vehicle : 5MT</h6>
                                    <small class="text-center">17ft L * 6ft W * 7ft H = 19CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" type="type7">
                                    <h6>Eicher 14 Vehicle : 4MT</h6>
                                    <small class="text-center">14ft L * 6ft W * 6.5ft H = 19CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type8">
                                    <h6>Tata 407 Vehicle : 2.5MT</h6>
                                    <small class="text-center">9ft L * 5.5ft W * 5.5ft H = 7.35CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="type9">
                                    <h6>Mahendra Bolero Vehicle : 1.5MT</h6>
                                    <small class="text-center">8ft L * 4.8ft W * 4.8ft H = 5CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-6 cust-padding-margin">
                                <div class="sub-block" id="type10">
                                    <h6>Tata Dost Vehicle : 1MT</h6>
                                    <small class="text-center">7ft L * 4.8ft W * 4.8ft H = 4CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 cust-padding-margin">
                                <div class="sub-block" id="type11">
                                    <h6>Tata Ace Vehicle : 850Kgs</h6>
                                    <small class="text-center">7ft L * 4.8ft W * 4.8ft H = 4CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div> -->
                        <!--- End Truck Type -->
                    </div>
                    <!--End-->
                    <div class="block" id="select-payment-mode">
                        <h5>I Wish To Pay By...</h5>
                        <div class="row">
                            <div class="col-xs-4 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="to-billed">
                                    <h6>To Billed</h6>
                                    <p class="text-center">By Sender</p>
                                    <span id="span" style="display:none;">2</span>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-4 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="to-pay">
                                    <h6>To Pay</h6>
                                    <p class="text-center">By Receiver</p>
                                    <span id="span" style="display:none;">1</span>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-4 col-sm-4 cust-padding-margin">
                                <div class="sub-block" id="cod">
                                    <h6>Cash On </h6>
                                    <h6 class="text-center"><strong>Delivery</strong></h6>
                                    <span id="span" style="display:none;">4</span>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block" id="select-sender-dtl">
                        <h5>Consignor / Sender Details.</h5>
                        <div class="row">
                            <div class="col-sm-12 cust-padding-margin">
                                <div class="sub-block" id="form_reload">
                                    <form id="fulldetails">
                                        <input type="hidden" name="truck_type" id="truck_type" value="" />
                                        <input type="hidden" name="form_name" id="form_name" value="add_new_form">
                                        <!-- <div id="sender-details">
												<div class="details-hdr">
													
													<h5>Sender Details</h5>
												</div>
												<div class="send-rcv-dtl">
													<div class="form-group col-sm-6">
														<label for="sender-name">Consignor / Sender Name</label>
														<input type="text" class="form-control" name="sender_name" id="sender-name">
														<input type="hidden"  class="form-control"  id="id">
													</div>
													<div class="form-group col-sm-6">
														<label for="sender-contact-no">Contact No:</label>
														<input   minlength=10 maxlength=10 data-rule- minlength=10 maxlength=10 type="number" class="form-control" name="sender-contact-no" id="sender-contact-no"  oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength); this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" >
													</div>
													<div class="form-group col-sm-6">
														<label for="sender-email">Email</label>
														<input type="email" class="form-control" name="sender-email" id="sender-email">
													</div>
													<div class="form-group col-sm-6">
														<label for="sender-city">City</label>
														<input type="text" class="form-control" name="sender-city" id="sender-city">
													</div>
													<div class="form-group col-sm-6">
														<label for="sender-address">Address </label>
														<input type="text" class="form-control" name="sender-address" id="sender-address">
													</div>
													<div class="form-group col-sm-6">
														<label for="sender-area">Village / Town / Area</label>
														<input type="text" class="form-control" name="sender-area" id="sender-area">
													</div>
													<div class="form-group col-sm-6">
														<label for="package-qty">No. of Packages</label>
														<input type="number" class="form-control" name="package-qty" id="package-qty">
													</div>
													<div class="form-group col-sm-6 ">
														<label for="package-qty"><input type="checkbox" value="" id="volumetric-check-box"> &nbsp; Volumetric (in cm)</label>
														<div class="volumetric-input-boxes disabled">
														<input type="number" placeholder="length" name ="length" class="form-control" id="length"disabled ><span>X</span>
														<input type="number" placeholder="width" name="width" class="form-control" id="width" disabled ><span>X</span>
														<input type="number" placeholder="height" name="height" class="form-control" id="height" disabled >
														</div>
													</div>
													<div class="form-group col-sm-6 package-wgt">
														<label for="package-wgt">Weight (In Kgs)</label>
														<div class="input-group">
															<input type="number" class="form-control" name="package-wgt" id="package-wgt">
															<span class="input-group-addon">Kgs *</span>
														</div>
													</div>
												</div>	
											</div> -->
                                        <div id="sender-details">
                                            <div class="details-hdr">
                                                <!-- <span class="far fa-address-card"></span> -->
                                                <h5>Sender Details</h5>
                                            </div>
                                            <div class="send-rcv-dtl">
                                                <div class="form-group col-sm-6">
                                                    <label for="sender-name">Consignor / Sender Name</label>
                                                    <input type="text" class="form-control" name="sender-name" id="sender-name">
                                                    <input type="hidden" class="form-control" id="id">

                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="sender-contact-no">Contact No:</label>
                                                    <input type="text" class="form-control"  minlength=10 maxlength=10 pattern="\d*" name="sender-contact-no" id="sender-contact-no" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" required autocomplete="off">
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="sender-email">Email</label>
                                                    <input type="email" class="form-control dup-check" name="sender-email" id="sender-email" autocomplete="off">
                                                    <span class="dup-check-text-status1 p_css"></span>
                                                    <input type="hidden" class="dup-check-status1" value="" />
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="sender-city">City</label>
                                                    <select name="sender-city" id="sender-city" class="form-control">
                                                        <option value="">Select City</option>
                                                        <?php

                                                        $city = mysqli_query($conn, "select *from city where status = 0 order by city_name");
                                                        while ($city_list = mysqli_fetch_assoc($city)) { ?>
                                                            <option value="<?php echo $city_list['city_id']; ?>"><?php echo $city_list['city_name']; ?></option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                    <!-- <input type="text" class="form-control" name="sender-city" id="sender-city"> -->
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="sender-address">Address </label>
                                                    <input type="text" class="form-control" name="sender-address" id="sender-address">
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="sender-area">Village / Town / Area</label>
                                                    <input type="text" class="form-control" name="sender-area" id="sender-area">
                                                </div>
                                                <!-- <div class="form-group col-sm-6">
														<label for="package-qty">No. of Packages</label>
														<input type="number" class="form-control" id="package-qty">
													</div> -->

                                                <!-- <div class="form-group col-sm-6 package-wgt">
														<label for="package-wgt">Weight (In Kgs)</label>
														<div class="input-group">
															<input type="number" class="form-control" id="package-wgt">
															<span class="input-group-addon">Kgs *</span>
														</div>
													</div> -->
                                            </div>
                                        </div>

                                        <div id="package-details" class="disabled">
                                            <div class="details-hdr">
                                                <!-- <span class="far fa-address-card"></span> -->
                                                <h5>Package Details</h5>
                                            </div>
                                            <div class="send-rcv-dtl ">
                                                <div id="package-info1" class="package-info">
                                                    <div class="form-group col-sm-6">
                                                        <label for="">No Of Packages</label>
                                                        <input type="text" required class="form-control" name="no-of-package" id="no-of-package" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;">

                                                    </div>
                                                    <div class="form-group col-sm-6">
                                                        <label for="">Type Of Package</label>
                                                        <select name="package_type" id="package_type" class="form-control">
                                                            <option>Select Package Type</option>
                                                            <?php
                                                            $package_type = mysqli_query($conn, "select * from package where status='0'");
                                                            while ($package_list = mysqli_fetch_assoc($package_type)) {
                                                            ?>
                                                                <option value="<?php echo $package_list['package_id']; ?>"><?php echo $package_list['package_code']; ?></option>
                                                            <?php
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-sm-6">
                                                        <label for="">Invoice No</label>
                                                        <input type="text" required class="form-control" name="package-invoice" id="package-invoice">
                                                    </div>
                                                    <div class="form-group col-sm-6">
                                                        <label for="">Contents</label>
                                                        <input type="text" required class="form-control" name="package-content" id="package-content">
                                                    </div>
                                                    <div class="form-group col-sm-6">
                                                        <label for="">Quantity </label>
                                                        <input type="text" required class="form-control" name="package-qty" id="package-qty" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;">

                                                    </div>
                                                    <div class="form-group col-sm-6">
                                                        <label for="">Gross Wt.(Kgs)</label>
                                                        <input type="text" required class="form-control" name="package-gross-wgt" id="package-gross-wgt" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;">

                                                    </div>
                                                    <div class="form-group col-sm-6 charged">
                                                        <label for="">Charged Wt.(Kgs)</label>
                                                        <input type="text" required class="form-control charged-weight" name="package-net-wgt" id="package-net-wgt" onchange="ss();" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;">

                                                    </div>
                                                    <div class="form-group col-sm-6 volumetric-info" id="ddd">
                                                        <label for=""><input type="checkbox" value="" id="volumetric-check-box"> &nbsp; Volumetric (in cm)</label>
                                                        <div class="volumetric-input-boxes disabled">
                                                            <input type="number" placeholder="L" name="length" class="form-control length" id="length" onchange="calculation();" disabled><span>X</span>
                                                            <input type="number" placeholder="W" name="width" class="form-control width" id="width" onchange="calculation();" disabled><span>X</span>
                                                            <input type="number" placeholder="H" name="height" class="form-control height" id="height" onchange="calculation();" disabled><span>X</span>
                                                            <input type="number" placeholder="Q" name="quantity" class="form-control quantity" id="quantity" onchange="calculation();" disabled><span>=</span>
                                                            <input type="number" placeholder="Feet/Kgs" name="weight" class="form-control weight" id="weight" onchange="calculation();" readonly>
                                                            <input type="hidden" class="form-control volume_weight" id="volume_weight" name="volume_weight" onchange="ss();" />
                                                        </div>
                                                        <input type="hidden" name="final_charged_weight" id="final_charged_weight" />
                                                    </div>
                                                    <!-- <div class="form-group col-sm-3">
															<label>&nbsp;</label>
															<a class="btn btn-danger" onclick="DelDiv(this)"><span class="fa fa-trash-o" aria-hidden="true"></span></a>
														</div> -->
                                                </div>
                                                <!-- <div class="col-sm-12 text-right package-info-add-del-btns">
														<a class="btn btn-primary" onclick="CloneDiv()"> <span class="fa fa-plus" aria-hidden="true"></span> Add Row</a>
														<a class="btn btn-danger disabled" onclick="DelDiv()"> <span class="fa fa-trash-o" aria-hidden="true"></span> Del Row</a>
													</div> -->
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
                                                    <input type="text" class="form-control" name="reciever-name" id="reciever-name" required>
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="reciever-contact-no">Contact No:</label>
                                                    <input  pattern="\d{10}" data-rule- minlength=10 maxlength=10 type="text" class="form-control" requried name="reciever-contact-no" id="reciever-contact-no" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;">

                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="reciever-email">Email</label>
                                                    <input type="email" class="form-control dup-check" name="reciever-email" id="reciever-email" required>
                                                    <span class="dup-check-text-status p_css"></span>
													<input type="hidden" class="dup-check-status" value="" />
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="reciever-city">City</label>
                                                    <select name="reciever-city" id="reciever-city" class="form-control" required>
                                                        <option value="">Select City</option>
                                                        <?php

                                                        $city = mysqli_query($conn, "select *from city where status = 0 order by city_name");
                                                        while ($city_list = mysqli_fetch_assoc($city)) { ?>
                                                            <option value="<?php echo $city_list['city_id']; ?>"><?php echo $city_list['city_name']; ?></option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                    <!-- <input type="text" class="form-control" name="reciever-city" id="reciever-city"> -->
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="reciever-address">Address </label>
                                                    <input type="text" class="form-control" name="reciever-address" id="reciever-address">
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="reciever-area">Village / Town / Area</label>
                                                    <input type="text" class="form-control" name="reciever-area" id="reciever-area">
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
                                                    <input type="text" class="form-control" name="doc-name" id="doc-name">
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="doc-data">Doc Data:</label>
                                                    <input type="number" class="form-control" name="doc-data" id="doc-data">
                                                </div>
                                                <div class="form-group col-sm-6">
                                                    <label for="reciever-email">Invoice / D.C Copy</label>
                                                    <input type="file" class="form-control-file" name="exampleFormControlFile1" id="exampleFormControlFile1">
                                                </div>
                                            </div>
                                            <a class="btn btn-primary" name="submit" id="submit">Submit</a>
                                            <span id="wait" style="display:none"></span>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block" id="contact-expert">
                        <h5 class="text-center">Get in Touch to Find out More</h5>
                        <p class="text-center">Our colleagues from GraciousExpress will be in touch to answer your inquiry.</p>
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

                    <div class="carusel-clients slider_mod-a owl-carousel owl-theme enable-owl-carousel" data-min480="1" data-min768="4" data-min992="4" data-min1200="4" data-pagination="false" data-navigation="false" data-auto-play="4000" data-stop-on-hover="true">

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
                            <span class="helper-2"></span></a>
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
                            <span class="helper-2"></span></a>
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
                            <span class="helper-2"></span></a>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // $(document).on('click', 'div#by-air,div#by-train,div#by-road-surface,div#by-surface-ftl,div#by-surface-ptl,div#by-road-express,#by-local', function() {
        // 	// alert("test");
        // 	$("#form_reload").load(location.href + " #form_reload");


        // })
    </script>
    <script>
        //Set Condition For PTL Mode

        $(document).on('click', function(e) {
            if ($("div#by-surface-ftl").hasClass('active')) {

            } else if ($("div#by-surface-ptl").hasClass('active')) {
                //alert("ptl");
                $('.v h5').addClass('vlm');
                $(".volumetric-info .length").prop('required', true);
                $(".volumetric-info .width").prop('required', true);
                $(".volumetric-info .height").prop('required', true);
                $(".volumetric-info .quantity").prop('required', true);

                $('.chng_label').text('G.S.T (12 %)');
                $('.charged').hide();
                // $('#ddd').toggleClass('col-sm-6 col-sm-12');
                $('#ddd').removeClass('col-sm-6').addClass('col-sm-12');
                $('#weight').attr('placeholder',
                    'Feet');
                $('#volumetric-check-box').prop("checked", true);
                $('.volumetric-input-boxes').removeClass('disabled');
                $('.volumetric-input-boxes input').attr('disabled', false);


            } else if ($("div#by-local").hasClass('active')) {
                $('.chng_label').text('G.S.T (12 %)')
            } else {
                $("#payment-info").show();
            }

        });
        //End

        //Calculation Part

        function calculation() {
            var l = $(".length").val();

            var w = $(".width").val();
            var h = $(".height").val();
            var q = $(".quantity").val();


            var toplam = 0;
            var toplam1 = 0;
            var toplam2 = 0;
            var toplam3 = 0;
            // $(".length").each(function() {
            toplam = toplam + parseInt(l);
            //     //console.log(toplam);
            // })
            // $(".width").each(function() {
            toplam1 = toplam1 + parseInt(w);
            //     //console.log(toplam1);
            // })
            // $(".height").each(function() {
            toplam2 = toplam2 + parseInt(h);
            //     //console.log(toplam2);
            // })
            // $(".quantity").each(function() {
            toplam3 = toplam3 + parseInt(q);
            //     // console.log(toplam3);
            // })


            var l_w_h_q = parseInt(toplam) * parseInt(toplam1) * parseInt(toplam2) * parseInt(toplam3);

            var de = 1000000;

            var divide = parseInt(l_w_h_q) / parseInt(de);

            //convert to feet

            var feet = divide / 2;


            //convert cms to kgs 

            var cms = parseInt(toplam) * parseInt(toplam1) * parseInt(toplam2) / 28000;

            var cms_to_6times = cms * 6;

            //convert air to kgs 

            var air_kgs = parseInt(toplam) * parseInt(toplam1) * parseInt(toplam2) / 5000;

            //var res_air_kgs = air_kgs * parseInt(toplam3) ;

            //var result1 = cms_to_6times *  parseInt(toplam3);


            //* Check Surface or Other Transport Mode    
            if ($("div#by-road-surface").hasClass('active')) {

                if ($("div#by-surface-ftl").hasClass('active')) {
                    // var data = $('#by-surface-ftl #span').text();
                    var result = divide / 2; // CBM to Feet
                    console.log("FTL: " + result)
                    if (result > 10) {
                        result;

                    } else {
                        result = 10;
                    }

                } else if ($("div#by-surface-ptl").hasClass('active')) {
                    $('.details-hdr h5').addClass('vlm');
                    // var data = $('#by-surface-ptl #span').text();

                    var result = divide / 2; // CBM to Feet
                    console.log("PTL: " + result)
                    if (result != '') {
                        if (result > 10) {
                            result;

                        } else {
                            result = 10;
                        }
                    }

                    // if(! isNaN(result)) {

                    //     console.log(result);
                    //     $(".volume_weight").val(result.toFixed(0));
                    //     $(".weight").val(result.toFixed(0));
                    //     // document.getElementById('weight').value = result;
                    // }

                    //console.log("yes PTL");
                }

            } else if ($("div#by-air").hasClass('active')) {
                var result = air_kgs * parseInt(toplam3);
                //console.log("air"+ result);

            } else {

                var result = cms_to_6times * parseInt(toplam3);
                console.log("Train Express Local Delivery" + result);
                $('.charged').show();

            }


            console.log("Result :" + result.toFixed(0));


            if (!isNaN(result)) {
                console.log(result);

                $(".volume_weight").val(result.toFixed(0));
                $(".weight").val(result.toFixed(0));

                // document.getElementById('weight').value = result;
                ss();

            }


        }

        //End

        //Get Highest Value
        function ss() {
            //alert('test');
            //var charg_w = $('.charged-weight').val();
            // alert("cal");

            var vol_weight = $('.volume_weight').val();
            var charg_w = $('.charged-weight').val();

            // console.log('first volume:' + vol_weight);
            // console.log('first weight:' + charg_w);


            //* Check Both Charged Weight and Volumetric
            if (charg_w != '' && vol_weight != '') {
                console.log('we are in: ' + charg_w + "and " + vol_weight);
                if (parseInt(charg_w) > parseInt(vol_weight)) {
                    // charg_w = Number.NaN;
                    if (!isNaN(charg_w)) {

                        console.log('charged_weight:' + charg_w);


                        $('#final_charged_weight').val(charg_w);

                    }

                } else {
                    if (!isNaN(vol_weight)) {

                        console.log('charged_weight:' + vol_weight);

                        $('#final_charged_weight').val(vol_weight);


                    }
                }

            } else if (charg_w != '' && vol_weight == '') {
                if (!isNaN(charg_w)) {

                    console.log('charged_weight_1:' + charg_w);

                    $('#final_charged_weight').val(charg_w);


                }

            } else if (vol_weight != '' && charg_w == '') {
                if (!isNaN(vol_weight)) {

                    console.log('charged_vol_1:' + vol_weight);

                    $('#final_charged_weight').val(vol_weight);

                }

            }


            if (!isNaN(res)) {
                // console.log(res);
                $("#amount").val(addZeroes(res));
                //alert(res);


            }
        }

        //End


        $(document).ready(function() {

            //Truck Type 
            $('.dropp').select2();

            //End

            // function myFunction(){
            // 	var NameValue = document.forms["fulldetails"]["length"].value;
            // 	alert(NameValue);
            // }

            // $('div#to-billed').on('click',function(){
            // 	var train = $('#to-billed #span').text();
            // 		alert(train);
            // });
        
        		//Check Consigner Email

            $(document).on('input', '#sender-email', function() {
                var chk_key = $(this).val();
                //alert(chk_key);
                if (chk_key != '') {
                    $(".dup-check-text-status").html('<p style="color:green;"><i class="fa fa-refresh fa-spin"></i> Checking...</p>');
                    $.ajax({
                        url: "http://localhost/graciousexpress/check_duplicate.php",
                        type: "post",
                        data: {
                            cmd: "check_email",
                            chk_key: chk_key
                        },
                        success: function(data) {
                            console.log(data);
                            if (data == 1) {
                                $(".dup-check-text-status1").html('<p style="color:red;"> <i class="fa fa-check-circle"></i> Email Already Exist , Use Existing Customer!</p>');
                                $(".dup-check-status1").val("0");
                            } else {
                                $(".dup-check-text-status1").html('<p style="color:green;"><i class="fa fa-times-circle"></i></p>');
                                $(".dup-check-status1").val("1");
                            }
                        },
                        error: function(jqxhr) {
                            console.log(jqxhr.responseText);
                        }
                    });
                }
            });
            //End
        
        //Check Consignee Email
         $(document).on('input', '#reciever-email', function() {
                var chk_key = $(this).val();
                //alert(chk_key);
                if (chk_key != '') {
                    $(".dup-check-text-status").html('<p style="color:green;"><i class="fa fa-refresh fa-spin"></i> Checking...</p>');
                    $.ajax({
                        url: "http://localhost/graciousexpress/check_duplicate.php",
                        type: "post",
                        data: {
                            cmd: "check_email",
                            chk_key: chk_key
                        },
                        success: function(data) {
                            console.log(data);
                            if (data == 1) {
                                $(".dup-check-text-status").html('<p style="color:red;"> <i class="fa fa-check-circle"></i> Email Already Exist , Use Existing Customer!</p>');
                                $(".dup-check-status").val("0");
                            } else {
                                $(".dup-check-text-status").html('<p style="color:green;"><i class="fa fa-times-circle"></i></p>');
                                $(".dup-check-status").val("1");
                            }
                        },
                        error: function(jqxhr) {
                            console.log(jqxhr.responseText);
                        }
                    });
                }
            });
        
        //End
          
            //Get Truck Type Values 
            $(document).on('change', '#dropp', function() {
                //alert("change");
                var sel_ids = $('#dropp :selected').text();
                //alert(sel_ids.length);

                $('#truck_type').val(sel_ids);
                $('#select-payment-mode').addClass('show')

            });

            //End

            $('#submit').click(function(e) {
                //alert("j");

                e.preventDefault();
                var _autosave;
                var consignoremailCheck = $('.dup-check-status1').val();
				var consigneeemailCheck = $('.dup-check-status').val();
				console.log(consignoremailCheck+" "+ consigneeemailCheck);
				if(consignoremailCheck == "1" && consigneeemailCheck == "1"){
                Swal.fire({
                        title: "Attention!",
                        text: "Don't Forget to Include Gracious Express GST Number While Generating Your E-Way Bill",
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'I Agree',
                        cancelButtonText: "No, cancel it!",
                        closeOnConfirm: false,
                        closeOnCancel: false
                    })
                    .then((isConfirm) => {
                        if (isConfirm) {
                            console.log("YES");


                            if ($("div#a").hasClass('active')) {
                                var customer = $('#new-cust h6').text();
                            } else {
                                var existing = $('#exist-cust h6').text();
                            }
                            if ($("div#by-air").hasClass('active')) {
                                var air = $('#by-air #span').text();
                            } else if ($("div#by-train").hasClass('active')) {
                                var train = $('#by-train #span').text();
                            } else if ($("div#by-road-surface").hasClass('active')) {
                                var roadsurface = $('#by-road-surface #span').text();

                            } else if ($("div#by-road-express").hasClass('active')) {
                                var roadexpress = $('#by-road-express #span').text();
                            } else {
                                var localdelivery = $('#by-local #span').text();

                            }
                            if ($("div#by-road-surface").hasClass('active')) {
                                if ($("div#by-surface-ftl").hasClass('active')) {
                                    var ftl = $('#by-surface-ftl #span').text();
                                } else {
                                    var ptl = $('#by-surface-ptl #span').text();
                                }
                            }
                            if ($("div#to-billed").hasClass('active')) {
                                var tobilled = $('#to-billed #span').text();
                            } else if ($("div#to-pay").hasClass('active')) {
                                var topay = $('#to-pay #span').text();
                            } else {
                                var cod = $('#cod #span').text();
                            }

                            var file_data = $('#exampleFormControlFile1').prop('files')[0];
                            var form_data = new FormData(document.getElementById('fulldetails'));
                            form_data.append('file', file_data);
                            if ($("div#a").hasClass('active')) {
                                form_data.append('customer', customer);
                            } else {
                                form_data.append('existing', existing);
                            }
                            //form_data.append('customer',customer);
                            if ($("div#by-air").hasClass('active')) {
                                form_data.append('air', air);
                            } else if ($("div#by-train").hasClass('active')) {
                                form_data.append('train', train);
                            } else if ($("div#by-road-surface").hasClass('active')) {
                                form_data.append('roadsurface', roadsurface);
                            } else if ($("div#by-road-express").hasClass('active')) {
                                form_data.append('roadexpress', roadexpress);
                            } else {
                                form_data.append('localdelivery', localdelivery);
                            }
                            if ($("div#by-surface-ftl").hasClass('active')) {
                                form_data.append('ftl', ftl);
                            } else if ($("div#by-surface-ptl").hasClass('active')) {
                                form_data.append('ptl', ptl);
                            } else {
                                console.log("");
                            }
                            if ($("div#to-billed").hasClass('active')) {
                                form_data.append('tobilled', tobilled);
                            } else if ($("div#to-pay").hasClass('active')) {
                                form_data.append('topay', topay);
                            } else {
                                form_data.append('cod', cod);
                            }

                            if ($('#fulldetails').valid() == true) {

                                $("#submit").attr("disabled", true);
                                $("#wait").show();
                                $("#wait").html('Please Wait...');
                                $.ajax({
                                    url: 'http://localhost/graciousexpress/save_details.php',
                                    contentType: false,
                                    cache: false,
                                    processData: false,
                                    type: "post",
                                    data: form_data,
                                    success: function(result) {
                                        if (result != 0) {
                                            swal({
                                                title: "Great!",
                                                text: "Your booking: " + result + ", Our Executive will reach you to pick the consignment in next 2-3 Hours !",
                                                icon: "success",
                                                buttons: "OK",
                                            }).then(function(isConfirm) {
                                                if (isConfirm) {
                                                    location.reload();
                                                    $("#submit").attr("disabled", false);

                                                    $("#wait").hide();
                                                } else {
                                                    //if no clicked => do something else
                                                    location.reload();
                                                }
                                            });
                                        } else {
                                            console.log("Booking Failed");
                                        }
                                    }
                                });
                            }
                        }
                        return false;
                    });
                }else{
					alert('Check Consignor / Consignee Email');
				}
            });

            function AutoSave() {
                _autosave = setInterval(function() {

                    var sendername = $('#sender-name').val();
                    var sendercontact = $('#sender-contact-no').val();
                    var senderemail = $('#sender-email').val();
                    var sendercity = $('#sender-city').val();
                    var senderaddress = $('#sender-address').val();
                    var senderarea = $('#sender-area').val();
                    var packageqty = $('#package-qty').val();
                    var packagewgt = $('#package-wgt').val();
                    var recievername = $('#reciever-name').val();
                    var recievercontact = $('#reciever-contact-no').val();
                    var recieveremail = $('#reciever-email').val();
                    var recievercity = $('#reciever-city').val();
                    var recieveraddress = $('#reciever-address').val();
                    var recieverarea = $('#reciever-area').val();
                    var docname = $('#doc-name').val();
                    var docdata = $('#doc-data').val();
                    var blog_id = $('#id').val();

                    if (sendername != "" && sendercontact != "" && senderemail != "") {
                        $.ajax({
                            url: 'http://localhost/graciousexpress/update_details.php',
                            type: 'POST',
                            data: {
                                sendername: sendername,
                                sendercontact: sendercontact,
                                senderemail: senderemail,
                                sendercity: sendercity,
                                senderaddress: senderaddress,
                                senderarea: senderarea,
                                packageqty: packageqty,
                                packagewgt: packagewgt,
                                recievername: recievername,
                                recievercontact: recievercontact,
                                recieveremail: recieveremail,
                                recievercity: recievercity,
                                recieveraddress: recieveraddress,
                                recieverarea: recieverarea,
                                docname: docname,
                                docdata: docdata,
                                blog_id: blog_id
                            },
                            success: function(data) {
                                if (data != "") {
                                    $('#id').val(data);
                                }
                            }
                        });
                    }
                }, 2000);
            }
            AutoSave();




           

        });

        // $(window).load(function(){
        // 	$('.form-data-saving').hide();
        // });
    </script>

    <script>
        var wow = new WOW({
            boxClass: 'wow', // animated element css class (default is wow)
            animateClass: 'animated', // animation css class (default is animated)
            offset: 0, // distance to the element when triggering the animation (default is 0)
            mobile: true, // trigger animations on mobile devices (default is true)
            live: true, // act on asynchronously loaded content (default is true)
            callback: function(box) {
                // the callback is fired every time an animation is started
                // the argument that is passed in is the DOM node being animated
            },
            scrollContainer: null, // optional scroll container selector, otherwise use window,
            resetAnimation: true, // reset animation on end (default is true)
        });
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
    <script src="assets/js/book-consignment.js"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>



</body>

</html>