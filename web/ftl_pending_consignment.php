<?php
require_once("include/connect.php");
require_once("include/function.php");
?>
<!DOCTYPE html>
<html>

<head>
    <?php include("include/title.php"); ?>
    <?php include("include/css_js.php"); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
        .invoice_exist {
            border: 1px solid #e71717 !important;
        }

        .invoice_valid {
            border: 1px solid #00CB01 !important;
        }

        .invoice_new {
            border: 1px solid #8E8D8D !important;
        }

        #pkg_req label {
            display: none !important;
        }

        #inv_req label {
            display: none !important;
        }

        .image_preview {
            width: 145px;
            height: 73px;
        }
        .img_pre_div {
            margin-bottom: 10px;
        }

        .attach_required:after {
				content: "This field is required.";
				color: #d9534f;
				position: relative;
				display: block;
				margin: 0;
				padding: 0;
				list-style: none;
				font-size: 14px;
				line-height: 20px;

			}
        .remove-image {
            margin-left: 90px;
        }

        /* Disable Option Selection */
        #mode_of_trasport,#dropp,#origin,#destination,#mode_of_consignment,.type_of_package {
            pointer-events: none;
        }
        
        /* Volumetric Design CSS */

        .shp_lbl {
            padding: 0px 0px;
        }

        label.col-md-4.control-label.shp_lbl {
            padding-left: 10px;
        }

        .form-group.kl {
            display: flex;
            width: 100%;
            flex-wrap: wrap;
            /* justify-content: end; */
        }

        .volumetric_width {

            display: flex;
            width: 30%;
            align-items: center;
            justify-content: center;
            /* column-gap: 3px; */
            text-align: center;
        }

        label.control-label.col-sm-4.v_label {
            text-align: left;
        }

        .dimensions_col {
            display: flex;
            width: 100%;
            /* column-gap: 4px; */

        }

        .volumetric_width:nth-child(6) {
            display: none;

        }


        .volumetric_width:nth-child(7) {
            width: 10%;
            /* width: 23%; */
            display: flex;
            justify-content: end;

        }

        .volumetric_width span {
            margin: 0 2px;
            font-size: 11px;
        }

        .r_p {
            padding: 0px 0px;
            text-align: center;
        }

        .dimen_img {
            width: 17px;
            margin-left: 2px;
        }

        .vlm_total_val {
            width: 64px;
            padding-right: 12px;
        }


        .form-horizontal .control-label {
            text-align: left;
        }

        .ship_address {
            display: flex;
            align-items: center;
            justify-content: left;
            column-gap: 8px;
        }

        input#ship_adddress {
            margin-top: 0;
        }

        .ship_address label {
            margin-bottom: 0px;
        }

        div#shipadd {
            display: none;
        }

        div#shipadd {
            margin-top: 4px;
        }

        .my-fieldset .form-control {
            margin: 1px;
        }

        .main_vlm_box {
            display: flex;
            justify-content: end;
            width: 100%;
        }
        .fa_calend{
            height: 25px;
    position: absolute;
    right: 0;
    width: 8%;
    top: 0;
    display: grid;
    justify-content: center;
    align-items: center;
    padding-top: 2px;
        }
   .cals_csss{
    width: 100%;
   }

 @media (min-width: 320px) and (max-width: 575px) {
.pak_info_tblee{
    width: 100%;
    overflow-x: scroll;
}
.widget-container .tabs {

    table-layout: fixed;
    width: 153%;
}

.widget-container .tabs {
    background: whitesmoke;
    border-bottom: 1px solid #dddddd;
    table-layout: fixed;
    width: 153%;
}
 }
        /* End */
    </style>
</head>

<body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
        <!-- Navigation -->
        <div class="navbar navbar-fixed-top scroll-hide">
            <?php
            require_once("include/header.php");
            require_once("include/menu.php");
            ?>

        </div>
        <div class="container-fluid main-content new_dpt_bottom">

            <div class="row">
                <div class="col-md-offset-1 col-md-10">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"> <i class="fa fa-plus"></i>Book a Consignment:
                            <!--<span class="align-right"><i class="fa fa-plus" ></i><a href="transaction_list.php">View List</a></span>-->
                        </div>

                        <div class="widget-content padded">

                            <div id="response" class="alert alert-danger" style="display:none;">
                                <div class="message" style="text-align:center"></div>
                            </div>
                            <?php
                            $m = $_REQUEST['m'];
                            $y = $_REQUEST['y'];
                            $query = "select * from transaction_" . $m . "_" . $y . " where md5(transaction_id) = '" . $_REQUEST['key'] . "'";
                            $result = mysqli_query($conn, $query);
                            $row = mysqli_fetch_assoc($result);
                            //print_r($row);
                            $transaction_id = $row['transaction_id'];
                            $ftl_type = $row['ftl_type'];
                            if ($row['transaction_id'] > 0)
                                $form_name = "edit_ftl_consignment_details";
                            else $form_name = "add_new_ftl_consignment";
                            ?>
                            <form id="ftl_details" class="form-horizontal" enctype="multipart/form-data">
                                <input type="hidden" name="truck_type" id="truck_type" value="<?php echo $ftl_type;?>"/>
                                <input type="hidden" name="form_name" value="<?php echo $form_name; ?>" id="form_name">
                                <input type="hidden" name="edit_id" value="<?php echo $row['transaction_id']; ?>">
                                <input type="hidden" name="grn_id" id="grn_id" value="<?php echo $row['grn_id']; ?>">
                                <fieldset class="my-fieldset">
                                    <legend>GRN Information</legend>
                                    <div class="row">

                                        <div class="col-md-offset-1 col-md-5">
                                            <div class="form-group">
                                                <label class="control-label col-sm-4">GRN.No <span style="color:red;">*</span> :</label>
                                                <div class="col-lg-8">
                                                    <?php
                                                    if ($_REQUEST['key'] != '') {
                                                    ?>
                                                        <input type="text" id="grn_no" value="<?php echo $row['grn_no']; ?>" name="grn_no" class="form-control" readonly />
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <input type="text" id="grn_no" <?php if ($_SESSION['role'] == "CL") echo 'value="Autogenerate on submit" disabled'  ?> required name="grn_no" minlength="6" maxlength="6" class="form-control" />
                                                    <?php
                                                    }
                                                    ?>
                                                    <span id="grn_error"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-lg-4 col-sm-12">GRN.Date <span style="color:red;">*</span> :</label>
                                               <div class="col-lg-8 col-sm-12">
                                                <div class="input-group date date-picker table-height" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
                                                    <input class="form-control table-height final" type="text" name="grn_date" value="<?php if ($row['grn_date'] != '')
                                                                                                                                            echo $row['grn_date'];
                                                                                                                                  else echo date('d-m-Y'); ?>" id="grn_date" required readonly> <span class="input-group-addon table-height"><i class="fa fa-calendar"></i></span>
                                                </div>
                                                 </div>     
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-sm-4">Mode of Transportation <span style="color:red;">*</span> :</label>
                                                <div class="col-lg-8">

                                                    <select name="mode_of_trasport" id="mode_of_trasport" class="form-control" required onchange="handleSelectChange(event);">
                                                        <option value="">Mode of Transport</option>
                                                        <?php
                                                        $transport_query = "select * from mode_of_transportation where status=0";
                                                        $transport_result = mysqli_query($conn, $transport_query);
                                                        while ($transport_row = mysqli_fetch_array($transport_result)) {
                                                        ?>
                                                            <option value="<?php echo $transport_row['mode_id']; ?>" <?php if ($transport_row['mode_id'] == $row['mode_of_transportation']) echo "selected"; ?>><?php echo $transport_row['mode_type']; ?></option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group" id="ftl_menu" style="display:none;">
                                                <label class="control-label col-sm-4">FTL Type <span style="color:red;">*</span> :</label>
                                                <div class="col-lg-8">
                                                    <select class="dropp form-control" role="menu" aria-labelledby="menu1" id="dropp" required>
                                                        <option value="" selected="true" disabled="disabled">Select Truck Type...</option>
                                                        <option value="Single Axle Vehicle: 07MT" <?php if ("Single Axle Vehicle: 07MT" == $row['ftl_type']) echo "selected"; ?>>Single Axle Vehicle: 07MT</option>
                                                        <option value="Multi Axle Vehicle : 10MT/14MT/17MT" <?php if ("Multi Axle Vehicle : 10MT/14MT/17MT" == $row['ftl_type']) echo "selected"; ?>>Multi Axle Vehicle : 10MT/14MT/17MT</option>
                                                        <option value="22ft Vehicle : 07MT" <?php if ("22ft Vehicle : 07MT" == $row['ftl_type']) echo "selected"; ?>> 22ft Vehicle : 07MT</option>
                                                        <option value="18ft Vehicle : 06MT" <?php if ("18ft Vehicle : 06MT" == $row['ftl_type']) echo "selected"; ?>>18ft Vehicle : 06MT</option>
                                                        <option value="Eicher 19 Vehicle : 7MT/8MT/9MT" <?php if ("Eicher 19 Vehicle : 7MT/8MT/9MT" == $row['ftl_type']) echo "selected"; ?>>Eicher 19 Vehicle : 7MT/8MT/9MT</option>
                                                        <option value="Eicher 17 Vehicle : 5MT" <?php if ("Eicher 17 Vehicle : 5MT" == $row['ftl_type']) echo "selected"; ?>>Eicher 17 Vehicle : 5MT</option>
                                                        <option value="Eicher 19 Vechicle:4MT" <?php if ("Eicher 19 Vechicle:4MT" == $row['ftl_type']) echo "selected"; ?>>Eicher 19 Vechicle:4MT</option>

                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label col-sm-4">Origin <span style="color:red;">*</span> :</label>
                                                <div class="col-lg-8">
                                                    <select name="origin" id="origin" class="form-control">
                                                        <option value="">Select Origin</option>
                                                        <?php
                                                        $city_query = "select * from city where status=0 order by city_name asc";
                                                        $city_result = mysqli_query($conn, $city_query);
                                                        while ($city_row = mysqli_fetch_array($city_result)) {
                                                        ?>
                                                            <option value="<?php echo $city_row['city_id']; ?>" <?php if ($city_row['city_id'] == $row['origin']) echo "selected"; ?>><?php echo $city_row['city_name']; ?></option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-sm-4">Destination <span style="color:red;">*</span> :</label>
                                                <div class="col-lg-8">
                                                    <select name="destination" id="destination" class="form-control">
                                                        <option value="">Select Destination</option>

                                                        <?php
                                                        //	if($row['destination']>0){
                                                        $city_query1 = "select * from city where status=0 and city_id!='" . $row['origin'] . "' order by city_name asc";
                                                        $city_result1 = mysqli_query($conn, $city_query1);
                                                        while ($city_row1 = mysqli_fetch_array($city_result1)) {
                                                        ?>
                                                            <option value="<?php echo $city_row1['city_id']; ?>" <?php if ($city_row1['city_id'] == $row['destination']) echo "selected"; ?>><?php echo $city_row1['city_name']; ?></option>
                                                        <?php
                                                        }
                                                        //	}
                                                        ?>


                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-sm-4">Mode of Consignment <span style="color:red;">*</span> :</label>
                                                <div class="col-lg-8">
                                                    <select name="mode_of_consignment" id="mode_of_consignment" class="form-control" required>
                                                        <option value="">Select Consignment</option>
                                                        <?php
                                                        $consignment_query = "select * from consignment_mode where status=0";
                                                        $consignment_result = mysqli_query($conn, $consignment_query);
                                                        while ($consignment_row = mysqli_fetch_array($consignment_result)) {
                                                        ?>
                                                            <option value="<?php echo $consignment_row['consignment_id']; ?>" <?php if ($consignment_row['consignment_id'] == $row['mode_of_consignment']) echo "selected"; ?>><?php echo $consignment_row['consignment_mode']; ?></option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </fieldset>

                                <fieldset class="my-fieldset">
                                    <legend>Consignor & Consignee Information</legend>
                                    <div class="row">
                                        <div class="col-md-offset-1 col-md-5">
                                            <div class="form-group">
                                                <label class="control-label col-sm-4">Consignor <span style="color:red;">*</span> :</label>
                                                <div class="col-lg-8">
                                                    <input type="text" name="consignor_name" class="form-control" required id="consignor_name" value="<?php echo get_client_name($conn, $row['consigner']) ?>" />

                                                    <input name="consignor" id="consignor" required value="<?php echo $row['consigner']; ?>" type="hidden" />

                                                </div>
                                            </div>
                                            <div id="con_details" style="display:none">
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">Address 1 <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">
                                                        <label class="control-label" id="address1"> </label>

                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">Address 2 <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">
                                                        <label class="control-label" id="address2"> </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">State <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">
                                                        <label class="control-label" id="state"> </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">City <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">
                                                        <label class="control-label" id="city"> </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">Pincode <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">
                                                        <label class="control-label" id="pincode"> </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">Phone <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">
                                                        <label class="control-label" id="phone"> </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">GST No <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">
                                                        <label class="control-label" id="gst_no"> </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label col-sm-4">Consignee <span style="color:red;">*</span> :</label>
                                                <div class="col-lg-8">


                                                    <input type="text" name="consignee_name" class="form-control" required id="consignee_name" value="<?php echo get_client_name($conn, $row['consignee']) ?>" />


                                                    <input name="consignee" id="consignee" required value="<?php echo $row['consignee']; ?>" type="hidden" />
                                                </div>
                                            </div>
                                            <div id="con_details1" style="display:none">
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">Address 1 <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">

                                                        <label class="control-label" id="con_address1"> </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">Address 2 <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">

                                                        <label class="control-label" id="con_address2"> </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">State <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">

                                                        <label class="control-label" id="con_state"> </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">City <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">

                                                        <label class="control-label" id="con_city"> </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">Pincode <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">

                                                        <label class="control-label" id="con_pincode"> </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">Phone <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">

                                                        <label class="control-label" id="con_phone"> </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-sm-4">GST No <span style="color:red;">*</span> :</label>
                                                    <div class="col-lg-8">

                                                        <label class="control-label" id="con_gst"> </label>
                                                    </div>
                                                </div>
                                                <!-- Shipping Address -->
                                                <?php
                                                if ($_REQUEST['key'] == '') { ?>
                                                    <div class="form-group">
                                                        <div class="ship_address col-md-4">
                                                            <input type="checkbox" id="ship_adddress" name="ship_adddress">
                                                            <label for="ship_adddress">Any Delivery Address</label>
                                                        </div>
                                                        <div class="col-lg-8" id="shipadd" style="display: none;">
                                                            <textarea class="form-control" rows="4" name="shipping_address" id="shipping_address" placeholder="Shipping Address"><?php echo $row['shipping_address']; ?></textarea>
                                                        </div>
                                                    </div>
                                                    <?php
                                                } else {
                                                    if ($row['shipping_address'] !== '') {
                                                    ?>
                                                        <div class="form-group">
                                                            <div class="ship_address col-md-4">
                                                                <input type="checkbox" id="ship_adddress" name="ship_adddress" checked="checked">
                                                                <label for="ship_adddress"> <strong>Shipping Address</strong></label>

                                                            </div>
                                                            <div class="col-lg-8" id="shipadd" style="display: none;">
                                                                <textarea class="form-control" rows="4" name="shipping_address" id="shipping_address" placeholder="Shipping Address"><?php echo $row['shipping_address']; ?></textarea>
                                                            </div>
                                                        </div>
                                                    <?php
                                                    } else { ?>
                                                        <div class="form-group">
                                                            <div class="ship_address col-md-4">
                                                                <input type="checkbox" id="ship_adddress" name="ship_adddress">
                                                                <label for="ship_adddress"> shipping address</label>

                                                            </div>
                                                        </div>
                                                <?php }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="my-fieldset">
                                    <legend>Package Information</legend>
                                    <div class="row pak_info_tblee">
                                        <div class="col-md-offset-1 col-md-10">
                                            <table class="table table-bordered tabs" width="100%">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center" width="5%">S.No</th>
                                                        <th class="text-center" width="10%">No of Pkgs</th>
                                                        <th class="text-center" width="18%">Type of Pkgs</th>
                                                        <th class="text-center" width="13%">Party Invoice No</th>
                                                        <th class="text-center" width="13%">Said to Contents</th>
                                                        <th class="text-center" width="10%">Qty</th>
                                                        <th class="text-center" width="13%">Gross Wt.(Kgs)</th>
                                                        <th class="text-center" width="13%">Charged wt.(Kgs)</th>
                                                    </tr>
                                                </thead>
                                                <?php

                                                if ($_REQUEST['key'] == '') {
                                                    $pkg_option = '<option> Select Package Type</option>';
                                                    $pkg_type_q = mysqli_query($conn, "select * from package where status='0'");
                                                    while ($pkg_r = mysqli_fetch_array($pkg_type_q)) {
                                                        $pkg_option .= '<option value="' . $pkg_r['package_id'] . '">' . $pkg_r['package_code'] . '</option>';
                                                    }

                                                ?>
                                                    <tbody>
                                                        <?php
                                                        for ($i = 1; $i <= 5; $i++) {
                                                        ?>
                                                            <tr>
                                                                <td class="text-center"><?php echo $i; ?></td>
                                                                <td><input type="text" name="no_of_pkg[]" id="no_of_pkg<?php echo $i; ?>" class="form-control num_only text-right" inputmode="numeric" autocomplete="off" onpaste="return false;"></td>
                                                                <td><select type="text" name="type_of_pkg[]" id="type_of_pkg<?php echo $i; ?>" class="form-control"> <?php echo $pkg_option; ?> </select></td>
                                                                <td><input type="text" name="party_invoice[]" id="party_invoice<?php echo $i; ?>" class="form-control" autocomplete="off" ></td>
                                                                <td><input type="text" name="content[]" id="content<?php echo $i; ?>" class="form-control"  autocomplete="off" ></td>
                                                                <td><input type="text" name="qty[]" id="qty<?php echo $i; ?>" class="form-control num_only text-right" inputmode="numeric" autocomplete="off" onpaste="return false;"></td>
                                                                <td><input type="text" name="gross[]" id="gross<?php echo $i; ?>" class="form-control  text-right num_only" inputmode="numeric" autocomplete="off" onpaste="return false;"></td>
                                                                <td><input type="text" name="charged[]" id="charged<?php echo $i; ?>" class="form-control  text-right num_only charged_w" onkeyup="calculate_charge_weight();" ></td>
                                                                <td style="display:none;"><input type="hidden" name="cumulative_charged" id="cumulative_charged" class="form-control  text-right num_only"></td>
                                                            </tr>
                                                        <?php
                                                        }

                                                        ?>
                                                    </tbody>
                                                <?php
                                                } else {

                                                ?>
                                                    <tbody>
                                                        <?php
                                                        $invoice_query = "select * from transaction_invoice_" . $m . "_" . $y . " where md5(transaction_id)='" . $_REQUEST['key'] . "'";
                                                        $invoice_result = mysqli_query($conn, $invoice_query);
                                                        $j = 1;

                                                        while ($invoice_row = mysqli_fetch_array($invoice_result)) {

                                                        ?>
                                                            <tr>
                                                                <td class="text-center"><?php echo $j; ?></td>
                                                                <td><input type="text" name="no_of_pkg[]" value="<?php echo $invoice_row['no_of_pkge']; ?>" id="no_of_pkg<?php echo $j; ?>" class="form-control  text-right num_only" readonly></td>
                                                                <td>
                                                                    <select type="text" name="type_of_pkg[]" id="type_of_pkg<?php echo $i; ?>" class="form-control type_of_package" >
                                                                        <option value="">Select Package Type</option>
                                                                        <?php
                                                                        $pkg_type_q = mysqli_query($conn, "select * from package where status='0'");
                                                                        while ($pkg_r = mysqli_fetch_array($pkg_type_q)) {
                                                                        ?>
                                                                            <option value="<?php echo $pkg_r['package_id']; ?>" <?php if ($invoice_row['type_of_pkge'] == $pkg_r['package_id']) echo "selected"; ?>><?php echo $pkg_r['package_code']; ?></option>
                                                                        <?php
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </td>
                                                                <td><input type="text" name="party_invoice[]" value="<?php echo $invoice_row['party_invoice_no']; ?>" id="party_invoice<?php echo $j; ?>" class="form-control" onchange="party_invoice_details();" onkeydown="party_invoice_details();" onkeyup="party_invoice_details();"></td>

                                                                <td><input type="text" name="content[]" value="<?php echo $invoice_row['said_contents']; ?>" id="content<?php echo $j; ?>" class="form-control" readonly></td>
                                                                <td><input type="text" name="qty[]" value="<?php echo $invoice_row['qty']; ?>" id="qty<?php echo $j; ?>" class="form-control num_only text-right" readonly></td>
                                                                <td><input type="text" name="gross[]" value="<?php echo $invoice_row['gross_weight']; ?>" id="gross<?php echo $j; ?>" class="form-control text-right num_only" readonly></td>
                                                                <td><input type="text" name="charged[]" value="<?php echo $invoice_row['charged_weight']; ?>" id="charged<?php echo $j; ?>" class="form-control text-right num_only charged_w" onkeyup="calculate_charge_weight();"></td>
                                                            
                                                            </tr>
                                                        <?php
                                                            $j++;
                                                        }

                                                        ?>
                                                        <td style="display:block;"><input type="hidden" name="cumulative_charged" id="cumulative_charged" class="form-control  text-right num_only" value="<?php echo $invoice_row['charged_weight']; ?>"></td>
                                                    </tbody>
                                                <?php
                                                }
                                                ?>
                                            </table>

                                        </div>
                                </fieldset>

                                <div class="row">
                                     <!-- Need to update in server -->
                                    <div class="col-md-offset-1 col-md-5 mega_lable">

                                        <fieldset class="my-fieldset">
                                            <legend>Volumetric Consignment(If Any)</legend>
                                            <div class="form-group">
                                                <label class="control-label col-sm-4 ">E-Way Number:</label>
                                                <div class="col-lg-8">
                                                    <input type="text" id="eway_number" value="<?php echo $row['eway_number'] ?>" name="eway_number" class="form-control text-left" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label col-sm-12 col-lg-4 v_label">E-Way Expiry Date:</label>
                                                <div class="col-sm-12 col-lg-8">
                                                    <div class="input-group date date-picker table-height" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
                                                        <input type="text" id="eway_expiryDate" name="eway_expiryDate" class="form-control" value="<?php if ($row['eway_expirydate'] != '')
                                                                                                                                                        echo $row['eway_expirydate'];
                                                                                                                                                    else echo date('d-m-Y'); ?>" autocomplete="off"  onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" />
                                                        <span class="input-group-addon table-height"><i class="fa fa-calendar"></i></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                    <label class="control-label col-sm-4">Goods Dedared value(INR):</label>
                                                <div class="col-lg-8">
                                                        <input type="text" id="goods_dedared_value" value="<?php echo $row['goods_dedared_value'] ?>" name="goods_dedared_value" class="form-control text-right" onchange="fov_calc();" required autocomplete="off" />
                                                </div>
                                            </div>
                                                <div class="form-group kl"> 
                                                   
                                                    <label class="control-label col-sm-4 v_label">Dimensions(L X W X H in cms):</label>
                                                    <div class="df col-lg-8">
                                                        <?php
                                                        $dimension1 = $row['dimension1'];
                                                        $length_dimension1 = explode(',', $dimension1);
                                                        $dimension2 = $row['dimension2'];
                                                        $width_dimension2 = explode(',', $dimension2);
                                                        $dimension3 = $row['dimension3'];
                                                        $height_dimension3 = explode(',', $dimension3);
                                                        $dimension4 = $row['dimension4'];
                                                        $quantity_dimension4 = explode(',', $dimension4);
                                                        $count = 0;
                                                        foreach ($length_dimension1 as $key => $values) {
                                                            $count++;
                                                        ?>
                                                            <div class="form-group dimensions_col" id="dimensions_col">
                                                                <div class="volumetric_width">
                                                                    <input type="text" placeholder="L" class="form-control r_p length num_only " id="length" name="length[]" onchange="vlm_calculation();" value="<?php echo $length_dimension1[$key] ?>" readonly /><span>X</span>
                                                </div>
                                                                <div class="volumetric_width">
                                                                    <input type="text" placeholder="W" class="form-control r_p width  num_only" id="width" name="width[]" onchange="vlm_calculation();" value="<?php echo $width_dimension2[$key]; ?>" readonly /><span>X</span>
                                            </div>
                                                                <div class="volumetric_width">
                                                                    <input type="text" placeholder="H" class="form-control r_p height num_only" id="height" name="height[]" onchange="vlm_calculation();" value="<?php echo $height_dimension3[$key]; ?>" readonly /><span>X</span>
                                                                </div>
                                                                <div class="volumetric_width">
                                                                    <input type="text" placeholder="Q" class="form-control r_p quantity num_only" id="quantity" name="quantity[]" onchange="vlm_calculation();" value="<?php echo $quantity_dimension4[$key]; ?>" readonly /><span>=</span>
                                                                </div>
                                                                <div class="volumetric_width">
                                                                    <input type="text" placeholder="" class="form-control r_p weight num_only" id="weight " name="weight[]" onchange="vlm_calculation();" readonly />
                                                                </div>
                                                                <div class="volumetric_width">
                                                                    <input type="text" class="form-control  r_p  volume_weight num_only" id="volume_weight" name="volume_weight[]" readonly />
                                                                </div>

                                                                <?php
                                                                if ($count < 2) {
                                                                ?>
                                                                    <!-- <div class="volumetric_width">
                                                                    <a href="javascript:void(0);" class="add_button" title="Add field"> <img src="icons/pluss.png" class="dimen_img" /></a>
                                                                </div> -->
                                                                <?php

                                                                } else { ?>
                                                                    <!-- <div class="volumetric_width">
                                                                    <a href="javascript:void(0);" class="remove" title="Add field"> <img src="icons/minus.png" class="dimen_img" /></a>
                                                                </div> -->

                                                                <?php
                                                                }
                                                                ?>
                                                            </div>
                                                        <?php
                                                        }
                                                        ?>

                                                    </div>
                                                    <div class="main_vlm_box">
                                                        <div class="vlm_total_val">
                                                            <input type="text" placeholder="" class="form-control r_p v_weight" id="v_weight" name="v_weight[]" onchange="vlm_calculation();" readonly />
                                                        </div>
                                                    </div>

                                                </div>



                                            <div class="form-group">
                                                <label class="control-label col-sm-4">Amount In Words:</label>
                                                <div class="col-lg-8">
                                                    <textarea name="amount_in_words" id="amount_in_words" rows="3" readonly class="form-control"><?php echo $row['total_words']; ?></textarea>
                                                </div>
                                            </div>

                                        </fieldset>

                                    </div>
                                    <div class="col-md-5">

                                        <fieldset class="my-fieldset">
                                            <legend>Payment Information</legend>
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Particulars</th>
                                                        <th>Rate</th>
                                                        <th>Amount(INR)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Freight</td>
                                                        <td><input type="text" name="frieght_rate" id="frieght_rate" value="<?php echo $row['frieght_rate']; ?>" class="form-control text-right" onchange="calculate_rate();" /></td>
                                                        <td><input type="text" name="frieght_amount" id="frieght_amount" value="<?php echo $row['frieght_amount']; ?>" class="form-control  text-right calculation" required onchange="sum_amount();" readonly /></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Loading / Unloading Charges</td>
                                                        <td><input type="text" name="loading_unload_rate" id="loading_unload_rate" class="form-control text-right" value="<?php echo $row['loading_unloading_rate']; ?>" autocomplete="off" /></td>
                                                        <td><input type="text" name="loading_unload_chrg" id="loading_unload_chrg" value="<?php echo $row['loading_unloading_amount']; ?>" class="form-control text-right calculation"  onchange="sum_amount();" autocomplete="off" /></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Crane / Fork Lift Charges</td>
                                                        <td><input type="text" name="crane_forklift_rate" id="crane_forklift_rate" class="form-control text-right" value="<?php echo $row['crane_fork_lift_rate']; ?>" /></td>
                                                        <td><input type="text" name="crane_forklift_chrg" id="crane_forklift_chrg" value="<?php echo $row['crane_fork_lift_amount']; ?>" class="form-control text-right calculation"  onchange="sum_amount();" autocomplete="off" /></td>
                                                    </tr>
                                                    <tr>
                                                        <td>C.O.D</td>
                                                        <td><input type="text" name="cod_rate" id="cod_rate" class="form-control text-right" value="<?php echo $row['cod_rate']; ?>" autocomplete="off" /></td>
                                                        <td><input type="text" name="cod_amount" id="cod_amount" value="<?php echo $row['cod_amount']; ?>" class="form-control text-right calculation"  onchange="sum_amount();" autocomplete="off" /></td>
                                                    </tr>
                                                    <tr>
                                                        <td>F.O.V</td>
                                                        <td><input type="text" name="fov_rate" id="fov_rate" class="form-control text-right" value="<?php echo $row['fov_rate']; ?>" /></td>
                                                        <td><input type="text" name="fov_amount" id="fov_amount" value="<?php echo $row['fov_amount']; ?>" class="form-control text-right calculation"  onchange="sum_amount();" readonly/></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Doc.Charges</td>
                                                        <td><input type="text" name="doc_rate" id="doc_rate" value="<?php echo $row['doc_charges']; ?>" class="form-control text-right" autocomplete="off" /></td>
                                                        <td><input type="text" name="doc_amount" id="doc_amount" value="<?php echo $row['doc_amount']; ?>" class="form-control text-right calculation"  onchange="sum_amount();" autocomplete="off" /></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Cartage</td>
                                                        <td><input type="text" name="cartage_rate" id="cartage_rate" value="<?php echo $row['cartage_rate']; ?>" class="form-control text-right" autocomplete="off" /></td>
                                                        <td><input type="text" name="cartage_amount" id="cartage_amount" value="<?php echo $row['cartage_amount']; ?>" class="form-control text-right calculation" onchange="sum_amount();" autocomplete="off" /></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Labour Handling</td>
                                                        <td><input type="text" name="labour_rate" id="labour_rate" value="<?php echo $row['labour_handling_rate']; ?>" class="form-control text-right" autocomplete="off" /></td>
                                                        <td><input type="text" name="labour_amount" id="labour_amount" value="<?php echo $row['labour_handling_amount']; ?>" class="form-control text-right calculation"  onchange="sum_amount();" autocomplete="off"/></td>
                                                    </tr>
                                                    <!--	<tr>
											<td>Octroi</td>
											<td><input type="text" name="octroi_rate" id="octroi_rate"  value="<?php echo $row['octroi_rate']; ?>"  class="form-control"/></td>
											<td><input type="text" name="octroi_amount" id="octroi_amount" value="<?php echo $row['octroi_amount']; ?>"  class="form-control calculation"/></td>
										</tr> -->
                                                    <tr>
                                                        <td>Any Other charges</td>
                                                        <td><input type="text" name="other_rate" id="other_rate" value="<?php echo $row['other_charge_rate']; ?>" class="form-control text-right" /></td>
                                                        <td><input type="text" name="other_amount" id="other_amount" value="<?php echo $row['other_charge_amount']; ?>" class=" text-right form-control calculation"  onchange="sum_amount();" autocomplete="off" /></td>
                                                    </tr>
                                                    <tr>
                                                        <td>GST (as applicable)</td>
                                                        <td><input type="text" name="gst_rate" id="gst_rate" value="<?php echo $row['gst_rate']; ?>" class="form-control text-right" readonly/></td>
                                                        <td><input type="text" name="gst_amount" id="gst_amount" value="<?php echo $row['gst_amount']; ?>" class="form-control text-right calculation"  onchange="sum_amount();" readonly /></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2"><span class="align-right">Total</span></td>
                                                        <td><input type="text" name="total" class="form-control text-right" value="<?php echo $row['total']; ?>" readonly id="total"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </fieldset>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-offset-1 col-md-5">
                                        <div class="form-group">
                                            <label class="control-label col-sm-4">Truck/ Vehicle No:</label>
                                            <div class="col-lg-8">
                                                <input type="text" name="vehicle_no" id="vehicle_no" value="<?php echo $row['truck']; ?>" class="form-control" autocomplete="off">

                                            </div>
                                        </div>
                                        <br />

                                        <img src="<?php echo $row['consiner_signature'];  ?>" id="signature_image" style="display:none" width="40%" height="20%">
                                        <label class="control-label col-md-12" style="text-align:  left;">Consignor Signature</label>
                                        <div id="content" class="col-md-12">
                                            <div id="signatureparent">
                                                <div id="signature">
                                                </div>
                                            </div>
                                            <div id="display_signature"></div>
                                            <div id="tools"></div>
                                        </div>
                                        <input type="hidden" name="signature" id="signature_val">
                                    </div>

                                    <?php
                                    if ($_REQUEST['key'] == '') {
                                    ?>
                                        <div class="col-md-5">
                                            <label class="control-label col-sm-12" style="text-align: left;font-weight: 600;">Attachments (Image & Documents)</label>
                                            <div class="file-container">
                                                <div class="col-md-12 file-group" id="file-no1" data-file-no="1">
                                                    <div class="col-md-6">
                                                        <input type="file" id="file_receipt1" name="file_receipt[]" class="filestyle" data-id="1" data-buttonBefore="true" data-buttonName="btn-primary">
                                                        <label></label>
                                                    </div>

                                                    <div class="col-md-2 img_pre_div">
                                                        <img src="images/no_image.png" class="image_preview" id="image_preview1">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button data-id="1" class="btn btn-danger remove-image">Remove</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-8">
                                                <button id="add_more" type="button" class="btn btn-primary">Add More</button>
                                            </div>
                                        </div>
                                    <?php
                                    } else {
                                    ?>
                                        <div class="col-md-5">
                                            <label class="control-label col-sm-12">Attachments (Image & Documents):</label>
                                            <div class="file-container">
                                                <?php
                                                $transaction_image_query = "select * from transaction_images_" . $m . "_" . $y . " where md5(transaction_id) = '" . $_REQUEST['key'] . "'";
                                                $transaction_image_result = mysqli_query($conn, $transaction_image_query);
                                                $k = 1;
                                                if (mysqli_num_rows($transaction_image_result) > 0) {
                                                while ($transaction_image_row = mysqli_fetch_array($transaction_image_result)) {
                                                ?>
                                                    <div class="col-md-12 file-group" id="file-no<?php echo $k; ?>" data-file-no="<?php echo $k; ?>">
                                                        <div class="col-md-6">
                                                                <input type="file" id="file_receipt<?php echo $k; ?>" name="file_receipt[]" class="filestyle" data-id="<?php echo $k; ?>" data-buttonBefore="true" data-buttonName="btn-primary">
                                                                <label></label>
                                                        </div>

                                                            <div class="col-md-2 img_pre_div">
                                                            <img src="invoice_image/<?php echo $transaction_image_row['attachment'];  ?>" class="image_preview" id="image_preview<?php echo $k; ?>">
                                                        </div>
                                                            <div class="col-md-2 remov">
                                                            <button data-id="<?php echo $k; ?>" id="<?php echo $transaction_image_row['attachment_id'];  ?>" class="btn btn-danger remove-image" type="button">Remove</button>
                                                        </div>
                                                    </div>
                                                <?php
                                                }
                                                } else {
                                                ?>
                                                    <div class="col-md-12 file-group" id="file-no1" data-file-no="1">
                                                        <div class="col-md-6">
                                                            <input type="file" id="file_receipt1" name="file_receipt[]" class="filestyle" data-id="1" data-buttonBefore="true" data-buttonName="btn-primary">
                                                            <label></label>
                                                        </div>

                                                        <div class="col-md-2 img_pre_div">
                                                            <img src="images/no_image.png" class="image_preview" id="image_preview1">
                                                        </div>
                                                        <div class="col-md-2 remov">
                                                            <button data-id="1" class="btn btn-danger remove-image">Remove</button>
                                                        </div>
                                                    </div>
                                            </div>
                                            <div class="col-lg-8">
                                                <button id="add_more" type="button" class="btn btn-primary">Add More</button>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                            }
                                    ?>
                                </div>
                        </div>
                    </div>
                        <br />
                        <div class="row">
                            <div class="col-md-12 form-action">
                                <button class="btn btn-primary" type="button" id="update">Update</button>
                                <button class="btn btn-default-outline  btn-reset" type="button">Cancel</button>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>


        <?php require_once("include/footer.php"); ?>
    </div>


    <script type="text/javascript">
         var ftl_flag = '<?php echo $ftl_type;?>';
				if(ftl_flag != ''){
					$("#ftl_menu").show();
				}
				//Show FTL Dropdown 
				$(document).on('change', '#mode_of_trasport', function() {
					//alert("change");
					var transport_type = $('#mode_of_trasport :selected').val();
					//alert(transport_type);
					if(transport_type == '7'){
						$("#ftl_menu").show();
					}else{
						$("#ftl_menu").hide();
					}


					// $('#truck_type').val(sel_ids);
					// $('#select-payment-mode').addClass('show')

				});
                $(document).on('change', '#dropp', function() {
					//alert("change");
					var sel_ids = $('#dropp :selected').text();
					//alert(sel_ids);

					$('#truck_type').val(sel_ids);
					$('#select-payment-mode').addClass('show')

				});
        //Selected Dropdown Part
        var transportMode = $('#mode_of_trasport').find(":selected").val();
        //console.log(transportMode);
        if (transportMode === '1' || transportMode === '2' || transportMode === '3') {
            gst = 18;
        } else {
            gst = 12;
        }
        if (!isNaN(gst)) {
            $('#gst_rate').val(gst);

        }

        //End Selected Dropdown Part

        //Get Charged Weight
        var charged_weight = $('.charged_w').val()

        if (!isNaN(charged_weight)) {
            $('#cumulative_charged').val(charged_weight);
        }
        //End Charged Weight

        //Start Calculate rate
        function calculate_rate() {
            //alert("cal")
            var vlm_val = $('#v_weight').val();
            var package_val = $('#cumulative_charged').val();
            var rates = $("#frieght_rate").val();
            console.log("VLM " + package_val);
            if (vlm_val > package_val) {

                var total_amts = parseInt(rates) * parseInt(vlm_val);

            } else if (vlm_val == package_val) {
                var total_amts = parseInt(rates) * parseInt(package_val);

            } else {
                var total_amts = parseInt(rates) * parseInt(package_val);
            }
            // console.log("chrg", package_val);

            // console.log("rate", rates);

            // console.log("total", total_amts);
            //console.log("total",total_amt);

            if (!isNaN(total_amts)) {
                $('#frieght_amount').val(addZeroes(total_amts));
                // $('#frieght_amount').keypress();
                sum_amount();
            }
        }

        //End Calculate rate


        //AddZero Function    
        function addZeroes(num) {
            var num = Number(num);
            if (String(num).split(".").length < 2 || String(num).split(".")[1].length <= 2) {
                num = num.toFixed(2);
            }
            return num;
        }
        //End AddZero Function   

        //Fov Calculation 
        function fov_calc() {
            var fov = 0.2;
            var goods_val = $("#goods_dedared_value").val()
            fov_chrge = (fov / 100) * goods_val;
            if (!isNaN(fov_chrge)) {
                $("#fov_amount").val(addZeroes(fov_chrge));

                sum_amount();
            }

        }

        //End Fov
            //Charge Weight Part
	        function calculate_charge_weight() {

	            var titles = $('input[name^=charged]').map(function(idx, elem) {
	                return $(elem).val();
	            }).get();

	            var res = titles.map(function(x) {
	                return parseInt(x);
	            });

	            var unique_weight = res.filter(function(value) {
	                return !Number.isNaN(value);
	            });
	            var total = 0;

	            for (let i = 0; i < unique_weight.length; i++) {
	                if (isNaN(unique_weight[i])) {
	                    total = total + 0;

	                } else {

	                    total = total + unique_weight[i];
	                }
	            }
	            //console.log(total);


	            if (!isNaN(total)) {
	                $('#cumulative_charged').val(total);

	            }

	            calculate_rate();
	        }

	        //End Charge Weight Part

        //Sum Amount
        function sum_amount() {

            var fright_amt = $('#frieght_amount').val() ? $('#frieght_amount').val() : 0;
            var l = $('#loading_unload_chrg').val() ? $('#loading_unload_chrg').val() : 0;
            console.log(l);
            var cr = $('#crane_forklift_chrg').val() ? $('#crane_forklift_chrg').val() : 0;
            var cod = $('#cod_amount').val() ? $('#cod_amount').val() : 0;
            var fov = $('#fov_amount').val() ? $('#fov_amount').val() : 0;
            var dc = $('#doc_amount').val() ? $('#doc_amount').val() : 0;
            var cartge = $('#cartage_amount').val() ? $('#cartage_amount').val() : 0;
            var lc = $('#labour_amount').val() ? $('#labour_amount').val() : 0;
            var oc = $('#other_amount').val() ? $('#other_amount').val() : 0;
            var gst_rate = $("#gst_rate").val();

            // console.log("f_amount " + fright_amt +" l: " + l + "cr " + cr + " dc " + dc + " lc " + lc + " cartge " + cartge + " gst_rate " + gst_rate + "fov " + fov + "oc " + oc + "cod " + cod) ;

            var totals = 0;
            //if (fov != '')
            var totals = parseFloat(fright_amt) + parseFloat(l) + parseFloat(cr) + parseFloat(cod) + parseFloat(fov) + parseFloat(dc) + parseFloat(cartge) + parseFloat(lc) + parseFloat(oc);
            // else
            // var totals = parseFloat(fright_amt);

            //console.log(totals,"tt");

            var gsts = (gst_rate / 100) * totals;
            // console.log(gsts);

            if (!isNaN(gsts)) {
                var gst1 = $("#gst_amount").val(addZeroes(gsts.toFixed(2)));
            }
            //console.log(gst1)
            // //addZeroes(totals_pay.toFixed(0))

            var totals_pay = parseFloat(gsts) + parseFloat(totals);


            if (!isNaN(totals_pay)) {
                //console.log(totals_pay);
                $("#total").val(addZeroes(totals_pay.toFixed(0)));
                get_total();
                //console.log(addZeroes(totals_pay));
            }
        }

        //End Sum Amount

        // Payment in Words

        function get_total() {
            let sum = $('#total').val();
            //alert(sum);
            $.ajax({
                url: 'fetch_details.php',
                type: "post",
                data: {
                    cmd: "get_amount_words",
                    val: sum
                },
                success: function(result) {
                    console.log(result);
                    $('#amount_in_words').val(result);
                },
                error: function(jqxhr) {
                    //alert(jqxhr.responseText);
                }
            });
        }
        //Auto Calculation Part End

        //Party Invoice No Checking
        function party_invoice_details() {
            let conr_id = $("#consignor").val();
            let cmd = "check_consginor_invoice_no";
            if (conr_id != "" && conr_id != null) {
                var all_party_invoice = $('input[name^=party_invoice]').map(function(idx, elem) {
                    return $(elem).val();
                }).get();
                console.log("party New", all_party_invoice);
                $.ajax({
                    url: 'fetch_details.php',
                    type: "GET",
                    dataType: "JSON",
                    data: {
                        cmd: cmd,
                        conr_id: conr_id,
                        all_party_invoice: all_party_invoice
                    },
                    success: function(result_data) {
                        var form_name = $("#form_name").val();
                        if (result_data) {
                            $.each(result_data, function(index, value) {
                                if ($.inArray($.trim(all_party_invoice[index]), load_party_inv) == -1) {
                                    // console.log(" From DB : " + index);
                                    //console.log(" From Old Values : " + load_party_inv[index]);
                                    var adddd = index + 1;
                                    var party_invoice = '#party_invoice' + adddd;

                                    if (value == "EMPTY") {
                                        $(party_invoice).removeClass("invoice_exist invoice_valid").addClass("invoice_new");

                                    } else if (value == "NO") {
                                        $(party_invoice).removeClass("invoice_exist invoice_new").addClass("invoice_valid");

                                    } else {
                                        $(party_invoice).removeClass("invoice_valid invoice_new").addClass("invoice_exist");

                                    }
                                } else {

                                }
                            });
                            //console.log('Arr',party_invoice_valid)
                            //$('#party_invoice_valid').val(party_invoice_valid)
                        } else {
                            console.log("no data found");
                        }
                    },
                    error: function(jqxhr) {
                        ewToast(jqxhr.responseText, 'error');
                    }

                });
            }

        }

        //Party Invoice Checking End

        //Edit VLM Calculation
        var V_mode, T;

        $(function() {

            $('#mode_of_trasport').change(function() {
                V_mode1 = $('#mode_of_trasport').val();
                V_mode = V_mode1;

                T = $('#mode_of_trasport :selected').text();
                vlm_calculation();
            });

            var V_modee = $('#mode_of_trasport').find(":selected").val();
            if (V_modee !== "") {
                V_mode = V_modee;
                //alert(V_mode);
                vlm_calculation();
            } else {
                // alert('ff');
            }
        });

        function vlm_calculation() {
            var sum = 0;
            array_weight = [];
            var sum1 = 0;

            var arr = [];
            var totalprice = 0;

            $.each($(".df .dimensions_col"), function(index, element) {
                element = $(element);
                var lengthd = parseInt(element.find('.length').val());
                var width = parseInt(element.find('.width').val());
                var height = parseInt(element.find('.height').val());
                var quantity = parseInt(element.find('.quantity').val());
                var weight1 = lengthd * width * height;
                var weight2 = lengthd * width * height * quantity;
                // alert(weight1);
                // alert(weight2);
                // alert(V_mode);
                // element.find('.weight').val(weight2);
                var quant = parseInt(element.find('.weight').val());
                totalprice += Number(quant);
                element.find('.volume_weight').val(totalprice);
                var de = 1000000;
                var divide = parseInt(weight2) / parseInt(de);

                //convert to feet
                var feet = divide / 2;
                //convert cms to kgs 
                var cms = parseInt(lengthd) * parseInt(width) * parseInt(height) / 28000;
                //var cms = sum1 / 28000; 
                var cms_to_6times = cms * 6;

                //convert air to kgs 
                var air_kgs = parseInt(lengthd) * parseInt(width) * parseInt(height) / 5000;

                if (V_mode == '7') {
                    // alert("BY SURFACE FTL");
                    // alert(lengthd);
                    if (!isNaN(lengthd) && !isNaN(width) && !isNaN(height) && !isNaN(quantity)) {
                        var result = divide / 2; // CBM to Feet
                        console.log("FTL: " + result)

                        if (result > 10) {
                            result;

                        } else {
                            result = 10;
                        }

                    } else {
                        result = 0
                    }
                } else if (V_mode == '8') {
                    // alert("BY SURFACE PTL");
                    if (!isNaN(lengthd) && !isNaN(width) && !isNaN(height) && !isNaN(quantity)) {
                        var result = divide / 2; // CBM to Feet
                        console.log("PTL: " + result)
                        if (result != '') {
                            if (result > 10) {
                                result;

                            } else {
                                result = 10;
                            }

                        }
                    } else {
                        result = 0;
                    }
                } else if (V_mode == '1') {
                    if (!isNaN(lengthd) && !isNaN(width) && !isNaN(height) && !isNaN(quantity)) {
                        var result = air_kgs * Number(element.find('.quantity').val());
                    } else {
                        result = 0;
                    }
                } else if (V_mode == '2' || V_mode == '3' || V_mode == '4' || V_mode == '5' || V_mode == '6') {
                    // alert("else");

                    if (!isNaN(lengthd) && !isNaN(width) && !isNaN(height) && !isNaN(quantity)) {
                        var result = cms_to_6times * Number(element.find('.quantity').val());
                        console.log("Train Express Local Delivery" + result);
                        $('.charged').show();
                    } else {
                        result = 0;
                    }

                } else {
                    // alert("else");
                    if (!isNaN(lengthd) && !isNaN(width) && !isNaN(height) && !isNaN(quantity)) {
                        var result = weight2;
                        console.log("No Transport Selected" + result);
                        $('.charged').show();
                    } else {
                        result = 0;
                    }
                }
                console.log("Result :" + result.toFixed(0));
                if (!isNaN(result)) {
                    console.log("total_wei", result);
                    arr.push(result);
                    element.find('.weight').val(result.toFixed(0));
                    get_all_total(arr);

                }
            });

            function get_all_total(array_data) {

                var total_weight = 0;

                for (let i = 0; i < array_data.length; i++) {
                    total_weight += array_data[i];
                }
                $(".volume_weight").val(total_weight.toFixed(0));
                $('#v_weight').val(total_weight.toFixed(0));
            
                calculate_rate();
                calculate_charge_weight();
            }
        }

        //End
        // //Get Highest
        // function highest_val(){
        //     var vlm_val = $('#v_weight').val();
        //     var package_val = $('#cumulative_charged').val();
        //     if(vlm_val > package_val){

        //         console.log("vlm"+vlm_val);

        //     }else if(vlm_val == package_val){
        //         console.log("pack"+package_val);

        //     }else{
        //         console.log("pack "+package_val);
        //     }
        //}

        //Payment Fetch Client Charges Start
        function load_payment_info() {
            if ($('#destination').val() != "" && $('#destination').val() != null) {
                var consignor_and_consinee_des = $('#destination').val();
                var consignor_id = $('#consignor').val();
                var consignee_id = $('#consignee').val();
                var cmd = "client_charges_auto_fetch";
                $.ajax({
                    url: 'fetch_details.php',
                    type: "GET",
                    dataType: "JSON",
                    data: {
                        consinee_dec_id: consignor_and_consinee_des,
                        consignor_get_id: consignor_id,
                        consignee__get_id: consignee_id,
                        cmd: cmd
                    },
                    success: function(pay_inv_data) {
                        console.log(pay_inv_data);
                        if (pay_inv_data != "No_Destination") {
                            console.log(pay_inv_data);
                            $("#loading_unload_chrg").val(parseFloat(pay_inv_data.loading_unloading_chrgs).toFixed(2));
                            $("#crane_forklift_chrg").val(parseFloat(pay_inv_data.crane_fork_lift_chrgs).toFixed(2));
                            $("#doc_amount").val(parseFloat(pay_inv_data.doc_chrgs).toFixed(2));
                            $("#labour_amount").val(parseFloat(pay_inv_data.labour_charges).toFixed(2));
                            $("#other_amount").val(parseFloat(pay_inv_data.other_chrgs).toFixed(2));

                            if ($("#mode_of_trasport").val() != "") {
                                if ($("#mode_of_trasport").val() == 7) { // air
                                    $("#frieght_rate").val(parseFloat(pay_inv_data.air).toFixed(2));
                                } else if ($("#mode_of_trasport").val() == 2) //train
                                {
                                    $("#frieght_rate").val(parseFloat(pay_inv_data.train).toFixed(2));
                                } else if ($("#mode_of_trasport").val() == 3) { // exp
                                    $("#frieght_rate").val(parseFloat(pay_inv_data.express).toFixed(2));
                                } else if ($("#mode_of_trasport").val() == 5) { //local
                                    $("#frieght_rate").val(parseFloat(pay_inv_data.local_delivery).toFixed(2));
                                } else if ($("#mode_of_trasport").val() == 8) { // ptl
                                    $("#frieght_rate").val(parseFloat(pay_inv_data.ptl).toFixed(2));
                                } else {
                                    $("#frieght_rate").val(parseFloat("0.00").toFixed(2));
                                    $("#loading_unload_chrg").val("");
                                    $("#crane_forklift_chrg").val("");
                                    $("#doc_amount").val("");
                                    $("#labour_amount").val("");
                                    $("#other_amount").val("");
                                }
                            } else {
                                $("#frieght_rate").val(parseFloat("0.00").toFixed(2));

                            }
                            calculate_charge_weight();
                            sum_amount();

                        } else {
                            ewToast("Consignor Does Not Have That Destination", 'error');
                        }


                    },
                    error: function(jqxhr) {
                        ewToast(jqxhr.responseText, 'error');
                    }

                });
            }
        }
        //Payment Fetch End


        $(document).ready(function() {

            var selected_consignee_id = $('#consignee').val();
            //alert(selected_consignee_id);
            if (selected_consignee_id != null && selected_consignee_id != '') {
                load_payment_info()

            }
            //PartyInvoice Checking
            var form_name = $("#form_name").val();
            load_party_inv = new Array();
            load_party_inv = $('input[name^=party_invoice]').map(function(idx, elem) {
                return $(elem).val();
            }).get();

            console.log("party", load_party_inv);
            //End

            $('.grn_no_popup').hide();

            function isChar(evt, element) {
                var charCode = (evt.which) ? evt.which : event.keyCode

                if ((charCode != 45 || $(element).val().indexOf('-') != -1) && // “-” CHECK MINUS, AND ONLY ONE.
                    (charCode != 46 || $(element).val().indexOf('.') != -1) && // “.” CHECK DOT, AND ONLY ONE.
                    (charCode < 48 || charCode > 57) && (charCode < 64 || charCode > 90))
                    return false;
                return true;
            }
            $('#vehicle_no').keypress(function(event) {
                return isChar(event, this)
            });
            var role = '<?php echo $_SESSION['role']; ?>';
            var id = '<?php echo $_SESSION['user_id']; ?>';
            console.log(role);
            var date = '<?php date('d-m-Y'); ?>';
            $('#eway_expiryDate').datepicker({
                // startDate: date,
                format: "dd-mm-yyyy",
                autoclose: true
            });
            if (role == 'CL') {
                $('#consignor_name').attr("disabled", "disabled");
            }

            if (role == "CL") {
                // $('#grn_date').datepicker({
                //     startDate: date,
                //     format: "dd-mm-yyyy",
                //     endDate: date
                // });

                $.ajax({
                    async: false,
                    url: 'fetch_details.php',
                    type: "GET",
                    dataType: "JSON",
                    data: {
                        cmd: "get_client_user_details",
                        tbl_id: id
                    },

                    success: function(result) {
                        console.log(result);

                        setTimeout(function() {
                            //$("#origin").val(result['city']).trigger("change"); 
                            $("#origin").val(result['city']);

                            $("#con_details").show();
                            $("#consignor").val(result['client_id']);
                            $("#consignor_name").val(result['client_company_name']).prop("readonly");
                            $('#address1').html(result['address1']);
                            $('#address2').html(result['address2']);
                            $('#city').html(result['city_name']);

                            $('#state').html(result['state_name']);
                            $('#pincode').html(result['pincode']);

                            $('#phone').html(result['contact_no']);
                            $('#gst_no').html(result['gst_no']);
                            $('#consignee_name').focus();

                        }, 300);


                    },
                    error: function(jqxhr) {
                        console.log(jqxhr.responseText);
                    }
                });



            } else {
                if (role == 'AD') {
                    // $('#grn_date').datepicker({
                    //     format: "dd-mm-yyyy"
                    // });

                } else {
                    // $('#grn_date').datepicker({
                    //     startDate: date,
                    //     format: "dd-mm-yyyy"
                    // });
                }


            }
            $(document).on('change', '#destination', function(e) {
                reset_consignee();
            });
            $(document).on('change', '#origin', function(e) {
                var id = $(this).val();
                reset_consignor();
                reset_consignee();
                $.ajax({
                    async: false,
                    url: 'fetch_details.php',

                    type: "GET",
                    dataType: "JSON",

                    data: {
                        cmd: "get_destination_consignor",
                        id: id
                    },

                    success: function(result) {
                        //alert(result['destination']);
                        setTimeout(function() {
                            $('#destination').html(result['destination']);
                            // $('#destination').val(result['destination']).attr("selected","selected");	
                            //$('#consignor').html(result['consignor']);	
                            //$('#vehicle_no').html(result['vehicle']);	



                        }, 3000);

                    }
                });

            });
            $(document).on('keyup', '#consignor_name', function() {
                var origin = $('#origin').val();
                var term = $(this).val();
                //console.log('autocomplete_list.php?autocomplete=consignor_autocomplete&origin='+origin+'&term='+term);
                $("#consignor_name").autocomplete({
                    source: 'autocomplete_list.php?autocomplete=consignor_autocomplete&origin=' + origin + '&term=' + term,
                    minLength: 0,
                    select: function(event, ui) {
                        $("#consignor_name").val(ui.item.value);
                        $("#consignor").val(ui.item.id);

                        $.ajax({
                            url: 'fetch_details.php',
                            type: "GET",
                            dataType: "JSON",
                            data: {
                                cmd: "get_client_details_consignment",
                                tbl_id: ui.item.id
                            },
                            async: false,
                            success: function(result) {
                                console.log(result);
                                $("#con_details").show();
                                if ($('#origin').val() == "")
                                    $('#origin').val(result['city']).trigger("change");
                                $("#consignor").val(ui.item.id);
                                $('#address1').html(result['address1']);
                                $('#address2').html(result['address2']);
                                $('#city').html(result['city_name']);

                                $('#state').html(result['state']);
                                $('#pincode').html(result['pincode']);
                                $('#phone').html(result['contact_no']);
                                $('#gst_no').html(result['gst_no']);
                                $('#consignee_name').focus();

                            }
                        });

                    },

                });

            });

            $(document).on('keyup', '#consignor_name', function(event) {
                var key = event.keyCode;
                if (key == 8 || key == 46)
                    reset_consignor();
            });

            $(document).on('keyup', '#consignee_name', function(event) {
                var key = event.keyCode;
                if (key == 8 || key == 46)
                    reset_consignee();
            });

            $(document).on('keyup', '#consignee_name', function() {
                var destination = $('#destination').val();
                var consignor = $('#consignor').val();
                var term = $(this).val();
                console.log('autocomplete_list.php?autocomplete=consignee_autocomplete&destination=' + destination + '&consignor=' + consignor + '&term=' + term);
                $("#consignee_name").autocomplete({
                    source: 'autocomplete_list.php?autocomplete=consignee_autocomplete&destination=' + destination + '&consignor=' + consignor + '&term=' + term,
                    minLength: 0,
                    select: function(event, ui) {
                        $("#consignee_name").val(ui.item.value);
                        $("#consignee").val(ui.item.id);

                        $.ajax({
                            url: 'fetch_details.php',
                            type: "GET",
                            dataType: "JSON",
                            data: {
                                cmd: "get_client_details_consignment",
                                tbl_id: ui.item.id
                            },
                            async: false,
                            success: function(result) {
                                console.log(result);

                                $("#con_details1").show();
                                if ($('#destination').val() == "")
                                    $('#destination').val(result['city']);
                                $("#consignee").val(ui.item.id);
                                $('#con_address1').html(result['address1']);
                                $('#con_address2').html(result['address2']);
                                $('#con_city').html(result['city_name']);
                                $('#con_state').html(result['state']);
                                $('#con_pincode').html(result['pincode']);
                                $('#con_phone').html(result['contact_no']);
                                $('#con_gst').html(result['gst_no']);
                                $('#no_of_pkg1').focus();
                            }
                        });
                    },

                });

            });
            //Volumetric Add More Field

            var maxField = 100; //Input fields increment limitation
            var addButton = $('.add_button'); //Add button selector
            var wrapper = $('.df'); //Input field wrapper
            var fieldHTML = '<div class="form-group dimensions_col" id="dimensions_col"><div class="volumetric_width"> <input  type="text" placeholder="L" class="form-control num_only r_p length " id="length" name="length[]" onchange="vlm_calculation();" /><span>X</span> </div><div class="volumetric_width"><input  type="text" placeholder="w" class="form-control num_only r_p width " id="width" name="width[]" onchange="vlm_calculation();"  /><span>X</span> </div><div class="volumetric_width"><input type="text" placeholder="H" class="form-control num_only r_p height" id="height" name="height[]" onchange="vlm_calculation();" /><span>X</span></div><div class="volumetric_width"><input type="text" placeholder="Q" class="form-control num_only r_p quantity" id="quantity" name="quantity[]" onchange="vlm_calculation();" /><span>=</span></div><div class="volumetric_width"><input type="text" id="weight" value="" class="form-control num_only r_p weight" name="weight[]" readonly /></div><div class="volumetric_width"> <input type="text" class="form-control  r_p volume_weight num_only" id="volume_weight" name="volume_weight[]" /></div><div class="volumetric_width"><a href="javascript:void(0);" class="remove" title="Add field"><img src="icons/minus.png" class="dimen_img"/></a></div>'; //New input field html 
            var x = 1; //Initial field counter is 1

            //Once add button is clicked
            $(addButton).click(function() {
                //Check maximum text of input fields
                if (x < maxField) {
                    x++; //Increment field counter
                    $(wrapper).append(fieldHTML); //Add field html
                }
            });
            //End Volumetric

            //Remove AddMore Field
            $("body").on("click", ".remove", function() {

                $(this).parents(".dimensions_col").remove();


                vlm_calculation();
                console.log("dd", array_weight);

            });
            //End

            //Edit Shipping Address
            var edit_form = '<?php echo $form_name; ?>';
            if (edit_form == 'edit_ftl_consignment_details') {
                var checkboxcheked = $('#ship_adddress').is(':checked');
                if (checkboxcheked == true) {
                    $('#shipadd').show();
                } else {
                    $('#shipadd').hide();
                }
            }

            //End


            //Shipping Address
            $('#ship_adddress').change(function() {
                if ($(this).is(':checked')) {
                    $('div#shipadd').show();
                    // alert('d');
                } else {
                    $('div#shipadd').hide();
                }
            })
            //End

            function reset_consignor() {

                $('#consignor').val('');
                $('#consignor_name').html('');
                $('#address1').html('');
                $('#address2').html('');
                $('#city').html('');
                $('#state').html('');
                $('#pincode').html('');
                $('#phone').html('');
                $('#gst_no').html('');
            }

            function reset_consignee() {
                $('#consignee').html('');
                $('#consignee_name').html('');
                $('#con_address1').html('');
                $('#con_address2').html('');
                $('#con_state').html('');
                $('#con_pincode').html('');
                $('#con_phone').html('');
                $('#con_gst').html('');
            }

            var chck_key = true;
            $(document).on('keyup', '#grn_no', function(e) {
                var grn_no = $(this).val();
                var grn_id = $("#grn_id").val();

                $.ajax({
                    url: 'check_existing.php',
                    type: "GET",
                    dataType: "JSON",
                    data: {
                        cmd: "chk_grn_no",
                        grn_no: grn_no,
                        grn_id: grn_id
                    },
                    async: false,
                    success: function(result) {
                        console.log(result);
                        if (result[0] == "1") {
                            $("#grn_error").html(result[1]).attr("style", "color:red");
                            chck_key = false;

                        } else {
                            chck_key = true;
                            $("#grn_error").html('');
                        }

                    }
                });
            });

            // $(document).on("change", ".calculation", function() {
            //     var sum = 0;

            //     $(".calculation").each(function() {
            //         sum += +$(this).val();
            //     });
            //     parseFloat($("#total").val(sum)).toFixed(2);
            //     $.ajax({
            //         url: 'fetch_details.php',
            //         type: "post",
            //         data: {
            //             cmd: "get_amount_words",
            //             val: sum
            //         },
            //         success: function(result) {
            //             console.log(result);
            //             $('#amount_in_words').val(result);
            //         },
            //         error: function(jqxhr) {
            //             alert(jqxhr.responseText);
            //         }
            //     });
            //     //$('#total').val(parseFloat($('#total').val(sum)).toFixed(2));
            // });

            var signature_image = '<?php echo $row['consigner_signature'];  ?>';

            $('#display_signature').html('<img src=' + signature_image + '>');
            if (signature_image == "") {
                $('div#signatureparent').removeClass('height_check');
            } else {
                $('div#signatureparent').addClass('height_check');
            }
            $('.datepicker').on("click", function() {
                $(this).datepicker({
                    startDate: new Date(),
                    changeMonth: true,
                    changeYear: true,
                    gotoCurrent: true,
                    dateFormat: 'yy-mm-dd',
                    maxDate: new Date(),
                    yearRange: '1980:c',
                    defaultDate: '-10y'
                }).datepicker('show');
            });

            $('.num_only,#grn_no,#goods_dedared_value').keypress(function(event) {
                return isNumber(event, this)
            });

            $('.calculation').keypress(function(event) {
                return isNumber(event, this)
            });


            function isNumber(evt, element) {
                var charCode = (evt.which) ? evt.which : event.keyCode

                if ((charCode != 45 || $(element).val().indexOf('-') != -1) && // “-” CHECK MINUS, AND ONLY ONE.
                    (charCode != 46 || $(element).val().indexOf('.') != -1) && // “.” CHECK DOT, AND ONLY ONE.
                    (charCode < 48 || charCode > 57))
                    return false;
                return true;
            }
            $(document).on('blur', 'input.calculation', function(ev) {
                if ($(this).val() != "")
                    $(this).val(parseFloat($(this).val()).toFixed(2));
                else
                    $(this).val("0.00");

            });
            $(document).on('click', '.btn-del-file', function(evt) {
                $(this).closest(".file-group").remove();
            });
            //image Preview
            function readURL(input, portfolio_no) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#' + portfolio_no).attr('src', e.target.result);
                    }

                    reader.readAsDataURL(input.files[0]);
                }
            }

            $(document).on('change', '.upload-image', function(ev) {
                var portfolio_no = $(this).attr("data-img-preview-id");
                readURL(this, portfolio_no);
            });
            $(document).on('change', '#goods_dedared_value', function(ev) {

                if ($(this).val() != "")
                    $(this).val(parseFloat($(this).val()).toFixed(2));
                else
                    $(this).val("0.00");

            });
            //signature

            var $sigdiv = $("#signature").jSignature({
                    'background-color': 'transparent',
                    'decor-color': 'transparent'
                }),
                $tools = $('#tools')

            $('#signature img').attr('src', "");
            $('#signature img').attr('style', '');
            $('#tools').html('<br/><input type="button" id="clear_signature" value="Clear">');
            $(document).on('click', '#clear_signature', function() {
                $('#display_signature').html('');
                $("#signature").show();
                //$sigdiv.jSignature('reset')
                $('#signature').jSignature('clear');
                $("#signature_capture").val('');
                $("#display_signature").attr("style", "");

                $('div#signatureparent').removeClass('height_check');

            });

            var attach_div_count = $(".file-container .file-group").length;
				//alert(attach_div_count);
				if (attach_div_count == 1) {

					$('.file-container .file-group').children(".remov").find("button").attr("disabled", "disabled");
				}

            //addmore
            var attachment_id = [];
            $(document).on('click', '.remove-image', function() {
                var attach_div_count = $(".file-container .file-group").length;
					//alert(attach_div_count);
					if (attach_div_count == 2) {
						$(this).parent().parent().siblings(".file-group").children(".remov").find("button").attr("disabled", true);
					} else {
						$(this).parent().parent().siblings(".file-group").children(".remov").find("button").attr("disabled", false);
					}
                var id = $(this).attr('data-id');
                $('#file-no' + id).remove();
                var image_id = $(this).attr('id');
                attachment_id.push(image_id);
                //alert(attachment_id);
            });


            // 			$(document).on('click','#save',function(){
            // 				var data1 = $sigdiv.jSignature('getData');
            // 			//alert(chck_key);
            // 				$('#signature_val').val(signature_image);
            // 		if(($sigdiv.jSignature('getData', 'native').length != 0)){
            // 			$('#signature_val').val(data1);
            // //alert(data1);
            // 		}
            // 				var edit_id = $('#edit_id').val();

            // 				var formData = new FormData(document.getElementById("grn_details")); 
            // 				console.log(formData);

            // 				if($('#grn_details').valid() == true && chck_key == true)
            // 				{
            // 					$(".loading-page").show();
            // 					$(this).prop("disabled",true);
            // 					$.ajax({
            // 						url:"save_details.php?id="+attachment_id,
            // 						type:"post",
            // 						dataType:"json",
            // 						data:formData,
            // 						processData: false,
            // 						contentType: false,
            // 						success:function(result){
            // 							console.log(result);
            // 							if(result['result'] == 1){
            // 							    $(".loading-page").hide();
            // 								if(edit_id==''){

            // 								$('.grn_no_popup').show();
            // 								$('#show_grn_no').text(result['data']);
            // 								}
            // 								else{
            // 									$(".form-data-saving").hide();
            // 									$("#alert-status").text("");
            // 									$("#alert-message").text("Saved Successfully");
            // 									$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
            // 									$("#alert-container").hide();
            // 									$("#alert-container").removeClass("alert-success");
            // 									location.reload();
            // 									});

            // 								}

            // 							}
            // 							else{
            // 								$(".form-data-saving").hide();
            // 								$("#alert-status").text("Alert !!! ");
            // 								$("#alert-message").text("Booking Failed");
            // 								$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
            // 								$("#alert-container").hide();
            // 								$("#alert-container").removeClass("alert-danger");
            // 								});
            // 							}

            // 						},
            // 					error:function(jqxhr)
            // 					{
            // 						console.log(jqxhr.responseText);
            // 					}
            // 					});
            // 				}

            // 			});


            $('#update').on('click', function(e) {
                //alert("Hi");
                var data1 = $sigdiv.jSignature('getData');
                //alert(chck_key);
                $('#signature_val').val(signature_image);
                if (($sigdiv.jSignature('getData', 'native').length != 0)) {
                    $('#signature_val').val(data1);
                    //alert(data1);
                }
                var edit_id = $('#edit_id').val();
                var values_file = $("input[name='file_receipt[]']")
						.map(function() {

							var imag_pre = $(this).parent().siblings('.img_pre_div').find('img').attr('src');
							//alert(imag_pre);

							if ($(this).val() == "" && imag_pre == 'images/no_image.png') {

								$(this).parent().find("label").addClass("attach_required");

							} else {

								$(this).parent().find("label").removeClass("attach_required");
							}
							return $(this).val() + imag_pre;

						}).get();

					// alert(values);
					//console.log(values_file);
					// var file_receipt_validate = values.some(item =>"images/no_image.png" )
					var file_receipt_validate = values_file.some(item => {
						if (item == "images/no_image.png" || item == '') {
							return true;

						}
					});
					//console.log("file_receipt_validate", file_receipt_validate);
				const length = $.map($('input[type=text][name="length[]"]'), function(el) {
						return el.value;
					});
					const width = $.map($('input[type=text][name="width[]"]'), function(el) {
						return el.value;
					});
					const height = $.map($('input[type=text][name="height[]"]'), function(el) {
						return el.value;
					});
					const quanti = $.map($('input[type=text][name="quantity[]"]'), function(el) {
						return el.value;
					});
					const vlm_weight = $.map($('input[type=text][name="weight[]"]'), function(el) {
						return el.value;
					});
					//console.log("weight", vlm_weight);
            
                var formData = new FormData(document.getElementById("ftl_details"));
                    formData.append('length', length);
                    formData.append('width', width);
                    formData.append('height', height);
                    formData.append('quanti', quanti);
                    formData.append('vlm_weight', vlm_weight);
                	$('#party_invoice1').attr('required', 'required');

					if ($("#v_weight").val() == '') {
						//alert("YEs");
						$('#charged1').attr('required', 'required');
					} else {
						$('#charged1').removeAttr('required');
						$('#charged1').removeClass('error');
					}
                    $('#no_of_pkg1').attr('required', 'required');

                let party_invoice_validate = $('input[name="party_invoice[]"].invoice_exist');

                // console.log("InvoiceValidate",party_invoice_validate.length);
                // return;

                if ($('#ftl_details').valid() == true && chck_key == true && file_receipt_validate == false) {
                    if (party_invoice_validate.length == 0) {
                    $(".loading-page").show();
                    $(this).prop("disabled", true);
                    $.ajax({
                        url: "save_details.php?id=" + attachment_id,
                        type: "post",
                        dataType: "json",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(result) {
                            console.log(result);
                            if (result['result'] == 1) {
                                $(".loading-page").hide();
                                //if(edit_id==''){

                                $('.grn_no_popup').show();
                                $('#show_grn_no').text(result['data']);
                                //}
                                //else{

                                $(".form-data-saving").hide();
                                $("#alert-status").text("");
                                $("#alert-message").text("Saved Successfully");
                                $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-success");
                                    //location.reload();
                                    window.location.href = "user_ftl_list.php";
                                });

                                //}
                            } else {
                                $(".form-data-saving").hide();
                                $("#alert-status").text("Alert !!! ");
                                $("#alert-message").text("Booking Failed");
                                $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-danger");
                                });
                            }

                        },
                        error: function(jqxhr) {
                            console.log(jqxhr.responseText);
                        }
                    });
                    } else {
                        ewToast("Party Invoice Already Exist", 'warning');
                    }
                }
            });

            //popupclose
            $(document).on('click', '.grn_close_popup', function() {
                $(".grn_no_popup").hide();
                $(".form-data-saving").hide();
                $("#alert-status").text("");
                $("#alert-message").text("Booked Successfully");
                $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                    $("#alert-container").hide();
                    $("#alert-container").removeClass("alert-success");
                    location.reload();
                });

            });

            function readURL(id, input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function(e) {
                        $('#image_preview' + id + '').attr('src', e.target.result);
                    }

                    reader.readAsDataURL(input.files[0]);
                }
            }

            $(document).on('change', '.filestyle', function() {
                var id = $(this).attr("data-id");
                readURL(id, this);
            });

            $(document).on('click', '#add_more', function(evt) {

                $file_no = $(".file-group:last").data("file-no");
                $file_no = isNaN($file_no) ? 1 : (parseInt($file_no) + 1);
                //alert($file_no);
                $new_file = '<br/><div class="col-md-12 file-group" id="file-no' + $file_no + '" data-file-no="' + $file_no + '" style="margin-top:58px;margin-left: -4px;">\
									<div class="col-md-6">\
										<input type="file" id="file_receipt' + $file_no + '" name="file_receipt[]" data-id="' + $file_no + '" class="filestyle" data-buttonBefore="true" data-buttonName="btn-primary">\
                                        <label></label>\
									</div>\
									<div class="col-md-2 img_pre_div">\
										<img src="images/no_image.png" class="image_preview" id="image_preview' + $file_no + '">\
									</div>\
									<div class="col-md-2 remove">\
										<button data-id=' + $file_no + ' class="btn btn-danger remove-image" type="button">Remove</button>\
									</div>\
								</div>';

                $(".file-container").append($new_file);
                //$("#file_receipt"+$file_no).filestyle({buttonBefore: true,buttonName: "btn-primary"});
            });




        });
        $(window).load(function() {
            $(".loading-page").hide();
        });
    </script>
    <div class="alert" id="alert-container" style="display:none;">
        <button type="button" class="close" data-dismiss="alert">x</button>
        <strong id="alert-status"></strong>
        <span id="alert-message"></span>
    </div>


    <div class="modal fade popup_close" id="myModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    <h4 class="modal-title" style="color:#fff">
                        Alert!
                    </h4>
                </div>

                <div class="modal-body">
                    <h5 text-align="center">
                        Do you want to Delete This Record ?
                    </h5>
                    <div class="modal-footer">
                        <button class="btn btn-primary btn-confirm-delete" data-dismiss="modal" type="button" id="">Yes</button>
                        <button class="btn btn-default-outline" data-dismiss="modal" type="button" id="">No</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="delete-error-popup">
        <div class="popup_overlay" id="popup_overlay"></div>
        <div class="popup" id="popup">
            <div class="popup_message">
                <h5 class="popup-title">Alert ! </h5>
                This Data Cannot Delete.Used by another record. so you can't Delete !!! <br /> &nbsp; <br />
                <button class="btn btn-sm btn-danger delete-error-popup-close" id="">Close</button> <br /> &nbsp; <br />
            </div>
            <!--<span class="popup_close" id="popup_close">X</span>-->
        </div>
    </div>
    <div class="grn_no_popup" style="display:none">
        <div class="popup_overlay" id="popup_overlay"></div>
        <div class="popup" id="popup">
            <div class="popup_message">
                <h5 class="popup-title">GRN Number</h5>
                <span id="show_grn_no"></span> <br /> &nbsp; <br />
                <button class="btn btn-sm btn-primary delete-error-popup-close grn_close_popup">Close</button> <br /> &nbsp; <br />
            </div>

        </div>
    </div>

</body>

</html>