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
            right: 2px;
        }

        .dataTable th.sorting:before,
        .dataTable th.sorting_asc:after {
            top: 10px;
            right: 2px;
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
    width: 53%;
    float: right;
    text-align: right;
    color: #5f5f5f;
}
.client_branch_litb{
	margin: 0 auto;
	width: max-content!important;
    max-width: unset!important;

    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
}
th.table-title.sorting {
    width: 127px!important;
}
th.table-title.sorting_disabled {
    width: 52px!important;
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
                <div class="col-md-offset-1 col-md-10 master_left">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"> <i class="fa fa-table"></i> List of Client Branch <span class="align-right"><i class="fa fa-plus"></i><a href="client_branch.php">Add Client Branch</a> </span></div>
                        <div class="widget-content padded clearfix new_dept">
                            <table class="table table-bordered table-striped client_branch_litb" id="dataTable1">
                                <thead>
                                    <th class="table-title" style="width:10%">S.No</th>
                                    <th class="table-title" style="width:20%">Company Name</th>
                                    <th class="table-title" style="width:30%">Branch Name</th>
                                    <th class="table-title" style="width:20%">Contact Person</th>

                                    <th class="table-title" style="width:10%">Action</th>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = 'select * from client_branch';
                                    $result = mysqli_query($conn, $query);
                                    $i = 1;
                                    while ($row = mysqli_fetch_array($result)) {
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i; ?></td>
                                            <td><?php echo get_client_name($conn, $row['company_id']); ?></td>
                                            <td><?php echo $row['branch_name']; ?></td>
                                            <td><?php echo $row['branch_contact_person'] ?></td>

                                            <td class="actions center-content ">
                                                <div class="action-buttons">
                                                    <a title="Edit" href="client_branch.php?key=<?php echo md5($row['client_branch_id']); ?>" class="table-actions btn-edit" id="<?php echo $row['client_branch_id']; ?>"><i class="fa fa-pencil"></i></a>
                                                    <?php
                                                    if ($row['status'] == 0) {
                                                        ?>
                                                        <a class="table-actions btn-active" data-status="<?php echo $row['status'] ?>" title="InActive" id="<?php echo $row['client_branch_id'] ?>"><i class="fa fa-check"></i></a>
                                                    <?php
                                                    } else {
                                                        ?>
                                                        <a class="table-actions btn-active" style="color:red;" data-status="<?php echo $row['status'] ?>" title="Active" id="<?php echo $row['client_branch_id'] ?>"><i class="fa fa-times"></i></a>
                                                    <?php
                                                    }
                                                    ?>
                                                    <a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo $row['client_branch_id'] ?>"><i class="fa fa-trash-o"></i></a>

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


                <?php require_once ('include/footer.php'); ?>
            </div>


            <script type="text/javascript">
                $(document).ready(function() {
                    //Button Delete
                    $(document).on('click', '.btn-trash', function(ev) {
                        var del_id = $(this).attr("id");
                        $(".btn-confirm-delete").attr("id", del_id);
                    });
                    $(document).on('click', '.delete-error-popup-close', function(ev) {
                        $(".delete-error-popup").hide();
                    });
                    $(document).on('click', '.btn-confirm-delete', function(ev) {
                        $(".form-data-saving").show();
                        $.post('save_details.php', {
                            form_name: "del_client_branch",
                            tbl_id: $(this).attr("id")
                        }, function(data, status) {
                            console.log(data);
                            if (data == 1) {
                                $(".form-data-saving").hide();
                                $("#alert-status").text("");
                                $("#alert-message").text("Cleint Branch Deleted successfully...");
                                $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-success");
                                    location.reload();
                                });
                            } else if (data == "404-del") {
                                $(".delete-error-popup").show();
                                $(".form-data-saving").hide();
                            } else {
                                $(".form-data-saving").hide();
                                $("#alert-status").text("Alert !!! ");
                                $("#alert-message").text("Cleint Branch deletion failed");
                                $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-danger");
                                });
                            }
                        });
                    });
                    //Active Inactive
                    $(document).on('click', '.btn-active', function(ev) {
                        $(".form-data-saving").show();
                        var status1 = '';
                        var msg = '';
                        var status = $(this).attr('data-status');
                        if (status == '1') {
                            status1 = '0';
                            msg = "Activated";
                        } else {
                            status1 = '1';
                            msg = "In-Activated";
                        }
                        $.post('save_details.php', {
                            form_name: "inacv_client_branch",
                            tbl_id: $(this).attr("id"),
                            status: status1
                        }, function(data, status) {
                            console.log(data);
                            if (data == 1) {
                                $(".form-data-saving").hide();
                                $("#alert-status").text("");
                                $("#alert-message").text("Cleint Branch  Is " + msg + "...");
                                $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-success");
                                    location.reload();
                                });
                            } else if (data == 2) {
                                $(".form-data-saving").hide();
                                $("#alert-status").text("");
                                $("#alert-message").text("Cleint Branch Is " + msg + "...");
                                $("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                    $("#alert-container").hide();
                                    $("#alert-container").removeClass("alert-danger");
                                    location.reload();
                                });
                            } else if (data == "404-del") {
                                $(".delete-error-popup").show();
                                $(".form-data-saving").hide();
                            }

                        });
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

</body>

</html>