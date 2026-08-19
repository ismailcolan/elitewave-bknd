<?php
require_once ('include/connect.php');
require_once ('include/function.php');
?>
<!DOCTYPE html>
<html>

<head>
    <?php include ('include/title.php'); ?>
    <?php include ('include/css_js.php'); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
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

        .paging_full_numbers {
            width: 50%;
        }
        
    @media (min-width: 360px) and (max-width: 575.98px) {  
   .widget-container .widget-content {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}
.status_shtable{
	margin: 0 auto;
	width: max-content!important;
    max-width: unset!important;

    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
} 
th.table-title.sorting  {
    width: 109px!important;
}
 th.table-title.sorting_disabled {
    width: 51px!important;
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
                        <div class="heading"> <i class="fa fa-table"></i> List of Status Sheet(s) <span class="align-right"><i class="fa fa-plus"></i> <a href="status_sheet.php">Add New Status Sheet</a></span> </div>
                        <div class="widget-content padded clearfix new_dept">
                            <table class="table table-bordered table-striped status_shtable" id="dataTable1">
                                <thead>
                                    <th class="table-title" style="width:10%">S.No</th>
                                    <th class="table-title" style="width:10%">Sheet No</th>
                                    <th class="table-title" style="width:15%">Origin</th>
                                    <th class="table-title" style="width:15%">Destination</th>
                                    <th class="table-title" style="width:15%">Mode</th>
                                    <th class="table-title" style="width:20%">Status</th>
                                    <th class="table-title" style="width:20%">Remarks</th>
                                    <th class="table-title" style="width:10%">Action</th>
                                </thead>
                                <tbody>
                                    <?php

                                    $query = 'select * from transaction_status ';

                                    $result = mysqli_query($conn, $query);
                                    $i = 1;
                                    while ($row = mysqli_fetch_array($result)) {
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i; ?></td>
                                            <td class="text-center"><?php echo $row['sheet_no']; ?></td>
                                            <td><?php echo get_city_name($conn, $row['origin']); ?></td>
                                            <td><?php echo get_city_name($conn, $row['destination']); ?></td>
                                            <td><?php echo get_mode($conn, $row['mode']); ?></td>
                                            <td><?php echo get_trans_status($row['status']); ?></td>
                                            <td><?php echo $row['remarks']; ?></td>
                                            <td class="actions center-content ">
                                                <div class="action-buttons">
                                                    <a title="Edit" href="edit_status_sheet.php?key=<?php echo md5($row['sheet_id']); ?>" class="table-actions btn-edit"><i class="fa fa-pencil"></i></a>

                                                </div>

                                            </td>
                                        </tr>
                                    <?php
                                        $i++;
                                    }
                                    ?>

                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>

            </div>


            <?php require_once ('include/footer.php'); ?>
        </div>


        <script type="text/javascript">
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

</body>

</html>