<?php
include_once ('include/connect.php');
include_once ('include/function.php');
?>
<!doctype html>
<html lang="en">

<head>
    <?php include ('include/title.php'); ?>
    <?php include ('include/css_js.php'); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <style>
        .widget-container .widget-content {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .user_drafttable {
            margin: 0 auto;
            width: max-content !important;
            max-width: unset !important;

            clear: both;
            border-collapse: collapse;
            table-layout: fixed;
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
        @media (min-width: 360px) and (max-width:575.98px) { 
            div#dataTable1_filter {
    display: block;
}


div#dataTable1_length {
    display: block;
}
.dataTables_filter input {
    width: 112px;
 
}
.dataTables_length {
    width: 43%;
    float: left;
    margin: 5px 0 10px;
}  
.dataTables_filter {
    width: 56%;
    float: right;
    text-align: right;
    color: #5f5f5f;
}
        }
    </style>
</head>

<body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
        <div class="navbar navbar-fixed-top scroll-hide">
            <?php include_once ('include/header.php'); ?>
            <?php include_once ('include/menu.php'); ?>
        </div>
    <div class="container-fluid main-content new_dpt_bottom">
        <div class="row">
            <div class="col-md-offset-1 col-md-10">
                <div class="widget-container fluid-height clearfix">
                    <div class="heading"> <i class="fa fa-table"></i> List of Draft Consignment 
                    <!-- <span class="align-right"><i class="fa fa-plus"></i> <a href="client.php">Add Client</a></span> -->
                 </div>
                    <div class="widget-content padded clearfix new_dept">
                        <table class="table table-bordered table-striped user_drafttable" id="dataTable1">
                            <thead>
                                <th class="table-title">S.No</th>
                                <th class="table-title" style="width:5%">Date</th>
								<th class="table-title" style="width:8%">Consignor Name</th>
								<th class="table-title" style="width:17%">Consignor Address</th>
								<th class="table-title" style="width:8%">Consignor Contact</th>
								<th class="table-title" style="width:7%">Consignor City</th>
                                <th class="table-title" style="width:8%">Consignee Name</th>
								<th class="table-title" style="width:17%">Consignee Address</th>
								<th class="table-title" style="width:8%">Consignee Contact</th>
								<th class="table-title" style="width:6%">Consignee City</th>
								<th class="table-title text-center" style="width:8%">Status</th> 
                                <th class="table-title" style="width:5%">Action</th>
                            </thead>
                            <tbody>
                                <?php
                                // $con = mysqli_connect("localhost","root","","bookconsignment");
                                $query = mysqli_query($conn, 'select * from draft_consignment order by id DESC');
                                $i = 1;
                                while ($row = mysqli_fetch_array($query)) {
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $i; ?></td>
                                        <td class=""><?php echo $row['created_at']; ?></td>
                                        <td><?php echo $row['consignor_name']; ?></td>
                                        <td><?php echo $row['consignor_address']; ?></td>
                                        <td><?php echo $row['consignor_contact']; ?></td>
                                        <td><?php echo $row['consignor_city']; ?></td>
                                        <td><?php echo $row['consignee_name']; ?></td>
                                        <td><?php echo $row['consignee_address']; ?></td>
                                        <td><?php echo $row['consignee_contact']; ?></td>
                                        <td><?php echo $row['consignee_city']; ?></td>
                                        <td class=" text-center"><?php
                                    if ($row['status'] == 1)
                                        echo '<button class="btn btn-warning">Draft</button>';
                                    else if ($row['status'] == 0)
                                        echo '<button class="btn btn-danger">Cancelled</button>';
                                    else if ($row['status'] == 3)
                                        echo '<button class="btn btn-success">Picked Up</button>';
                                    ?></td>

                                        <td>
                                            <div class="action-buttons">



                                                <a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo $row['id'] ?>"><i class="fa fa-trash-o"></i></a>

                                            </div>
                                        </td>


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
        $(document).ready(function() {
            		// DataTable initialized by main.js
            $(document).on('click', '.btn-trash', function(e) {
                var del_id = $(this).attr('id');
                //alert(del_id);
                $(".btn-confirm-delete").attr("id", del_id);
            });
            $(document).on('click', '.btn-confirm-delete', function(e) {
                $(".form-data-saving").show();
                $.post('https://elitewave360.in/php/save_details.php', {
                    form_name: "delete_draft_consignment",
                    tbl_id: $(this).attr("id")
                }, function(data, status) {
                    console.log(data);
                    if (data != 0) {
                        $(".form-data-saving").hide();
                        $("#alert-status").text("");
                        $("#alert-message").text("Draft Consignment Deleted Successfully....");
                        $("#alert-container").addClass('alert-success').slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                            $("#alert-container").hide();
                            $("#alert-container").removeClass('alert-success');
                            location.reload();
                        });
                    } else if (data == "404-del") {
                        $(".delete-error-popup").show();
                        $(".form-data-saving").hide();
                    } else {
                        $(".form-data-saving").hide();
                        $("#alert-status").text("");
                        $("#alert-message").text("Draft Consingment Delete Failed");
                        $("#alert-container").addClass('alert-danger').slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                            $("#alert-container").hide();
                            $("#alert-container").removeClass('alert-danger');
                            location.reload();
                        });
                    }
                });
            })
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
</body>

</html>