<?php
require_once ('include/connect.php');
require_once ('include/function.php');

$c_date = date('d-m-Y');
$c_mY = date('m-Y');

?>
<!DOCTYPE html>
<html>

<head>
    <?php include ('include/title.php'); ?>
    <?php include ('include/css_js.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
        .ui-monthpicker-trigger {
            display: none;
        }

        .select2-container .select2-choice .select2-arrow b {
            border-top: 4px solid #ffffff;
        }

        .select2-container .select2-choice .select2-arrow {
            background: #237fe3;
        }

        button#search {
            padding: 7px 15px;
            font-weight: 800;
        }

        #radio_class {
            position: relative;
            right: 96px;
        }

        .dataTable th.sorting:after,
        .dataTable th.sorting_desc:after {
            top: 17px;
            right: 3px;
        }

        .dataTable th.sorting:before,
        .dataTable th.sorting_asc:after {
            top: 10px;
            right: 3px;
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
        @media only screen and (min-width: 360px) and (max-width: 640px) {

            #radio_class {
                position: relative;
                right: 0px;
            }


        }
	@media only screen and (min-width: 375px) and (max-width: 667px) and (orientation: landscape) {
			#radio_class {
    position: relative;
    right: 0px;
}
		}

        @media only screen and (min-width: 768px) and (max-width: 1024px) {

            #radio_class {
                position: relative;
                right: 0px;
            }


        }

        /* Portrait and Landscape */
        @media only screen and (min-width: 1024px) and (max-height: 1366px) and (-webkit-min-device-pixel-ratio: 1.5) {
            #radio_class {
                position: relative;
                right: 36px;
                width: 80%;
            }
        }

        /* Landscape */
        @media only screen and (min-width: 1024px) and (max-height: 1366px) and (orientation: landscape) and (-webkit-min-device-pixel-ratio: 1.5) {
            #radio_class {
                position: relative;
                right: 46px;
                width: 80%;
            }

        }

    </style>
</head>

<body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
        <!-- Navigation -->
        <div class="navbar navbar-fixed-top scroll-hide">
            <?php
            require_once ('include/header.php');
            require_once ('include/menu.php');

            ?>

        </div>
        <div class="container-fluid main-content new_dpt_bottom">

            <div class="row">
                <div class="col-md-offset-1 col-md-10">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"> <i class="fa fa-table"></i> Payment Report </div>
                        <div class="widget-content padded">
                            <form class="form-horizontal" id="payment_history_transaction_form">

                                <input type="hidden" id="cmd" name="cmd" value="get_payment_report_details">
                                <div id="response" class="alert alert-danger" style="display:none;">
                                    <div class="message" style="text-align:center"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-offset-3 col-md-3">
                                        <div class="form-group">
                                            <label class="control-label"><input type="radio" name="report_type" class="report_type" value="NONE" /> None</label>
                                            <label class="control-label"><input type="radio" name="report_type" class="report_type" value="DAILY" checked /> DAILY</label>
                                            <label class="control-label "><input type="radio" class="report_type" name="report_type" value="MONTHLY" /> MONTHY</label>

                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group" id="radio_class">

                                            <div id="picker1" class="input-group date daily cals_csss date-picker" data-date-autoclose="true" data-date-format="dd-mm-yyyy">
                                                <input class="form-control" type="text" id="date" name="date" required onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return false;" ><span class="input-group-addon fa_calend"><i class="fa fa-calendar"></i></span>
                                            </div>
                                            <div id="picker2" class="input-group cals_csss">
                                                <input class="form-control" type="text" id="month" name="month" value="<?php echo date('m-Y'); ?>" required onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null :event.charCode >= 96 && event.charCode <= 105 && event.charCode >= 48 && event.charCode <= 57" onpaste="return false;"><span class="input-group-addon fa_calend"><i class="fa fa-calendar"></i></span>
                                            </div>

                                        </div>

                                    </div>
                                </div><br />
                                <div class="row">
                                    <div class="col-md-offset-3 col-md-5">
                                        <div class="form-group">
                                            <label class="control-label">Client:</label>
                                            <select name="client_wise_report" id="client_wise_report" class="client_wise_report">
                                                <option value="" selected="true" disabled="disabled">Select Client</option>
                                                <?php
                                                $query = 'select * from client';
                                                $result = mysqli_query($conn, $query);

                                                while ($row1 = mysqli_fetch_array($result)) {
                                                    ?>

                                                    <option value="<?php echo $row1['client_id']; ?>" <?php if ($row1['client_id'] == $row['consigner_id']) echo 'selected'; ?>><?php echo $row1['client_company_name']; ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <button class="btn btn-primary" type="button" style="margin-top: 18px;" id="search">Search</button>

                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-offset-1 col-md-10" id="table_div" style="display: none;">
                <div class="widget-container fluid-height clearfix">
                    <div class="heading"> <i class="fa fa-table"></i> Client Payment Report </div>
                    <div class="widget-content padded clearfix new_dept" id="get_month_details">
                        <table class="table table-bordered table-striped" id="dataTable1">
                            <thead>
                                <th class="table-title">S.No</th>
                                <th class="table-title">GRN NO</th>
                                <th class="table-title" width="100px">GRN Date</th>
                                <th class="table-title" width="100px">Invoice No.</th>

                                <th class="table-title">No.of.Pkgs</th>
                                <th class="table-title">Weight</th>
                                <th class="table-title">Mode</th>
                                <th class="table-title">Origin</th>
                                <th class="table-title">Consignor </th>
                                <th class="table-title">Consignee </th>
                                <th class="table-title">Destination</th>
                                <th class="table-title">Status</th>
                            </thead>
                            <tbody id="get_month_details">

                            </tbody>
                        </table>

                    </div>
                </div>
            </div>

        </div>


        <?php require_once ('include/footer.php'); ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src='javascripts/jquery.ui.monthpicker.js'></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('.client_wise_report').select2();
            $('#report_table').dataTable({ bPaginate: true, iDisplayLength: 10 });
            $("#date").val('<?php echo $c_date; ?>');
            $("#month").val('<?php echo $c_mY; ?>');
            $("#picker2").hide();
            $(document).on('change', '.report_type', function() {
                if ($(this).val() == 'MONTHLY') {
                    $("#picker1").hide();
                    $("#picker2").show();
                } else if ($(this).val() == 'DAILY') {
                    $("#picker2").hide();
                    $("#picker1").show();
                } else {
                    $("#picker1").hide();
                    $("#picker2").hide();
                }

            });
            $(document).on('click', '#search', function() {
                // alert("test");
                $("#table_div").show();
                var data = $('#payment_history_transaction_form').serialize();
                //alert(data);
                if ($('#payment_history_transaction_form').valid() == true) {

                    $.ajax({
                        url: 'fetch_details.php',
                        type: "GET",
                        data: data,
                        success: function(result) {
                            console.log(result);
                            $('#dataTable1').dataTable().fnDestroy();
                            $('#get_month_details').html(result);
                            $("#dataTable1").dataTable({ bPaginate: true, iDisplayLength: 10 });

                        }
                    });
                }

            });
            $('#month').on("click", function() {
                // $(this).datepicker({
                // 	changeMonth: true,
                // 	changeYear: true,
                // 	format: 'mm-yyyy',
                // }).datepicker('show');
                jQuery(this).monthpicker({
                    showOn: "both",
                    dateFormat: 'mm-yy'
                    // buttonImage: "calendar.png",
                    // buttonImageOnly: true
                }).monthpicker('show');
            });

            $('.daily').on("click", function() {
                $(this).datepicker({
                    changeMonth: true,
                    changeYear: true,
                    format: 'dd-mm-yyyy',
                }).datepicker('show');
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

    <div class="modal fade popup_close" id="eway_popup" style="display:none">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
                    <h4 class="modal-title" style="color:#fff">
                        Add Attachments
                    </h4>
                </div>

                <div class="modal-body" id="attachment_body">

                </div>
            </div>
        </div>
    </div>


</body>

</html>