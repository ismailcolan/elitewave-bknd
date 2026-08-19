<?php
include_once ('include/connect.php');
include_once ('include/function.php');

mysqli_query($conn, 'UPDATE user_registrations SET read_status = 1 WHERE read_status= 0');  // this is to mark readed the new user registration and it hides the count in menu bar.
?>
<!doctype html>
<html lang="en">

<head>
    <?php include ('include/title.php'); ?>
    <?php include ('include/css_js.php'); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <style>
        a.disable {
            pointer-events: none;
            cursor: default;
        }

        .widget-container .widget-content {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .user-inquiry_tbl {
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

            .user-inquiry_tbl {
                margin: 0 auto;
                width: max-content !important;
                max-width: unset !important;

                clear: both;
                border-collapse: collapse;
                table-layout: fixed;
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
                    <div class="heading"> <i class="fa fa-table"></i>User Registraion Request</div>
                    <div class="widget-content padded clearfix new_dept ">
                        <table class="table table-bordered table-striped w-100" id="dataTable1"> <!--user-inquiry_tbl-->
                            <thead>
                                <tr>
                                    <th class="table-title">S.No</th>
                                    <th class="table-title" style="width:10%">Name</th>
                                    <th class="table-title" style="width:5%">Email</th>
                                    <th class="table-title" style="width:10%">Mobile</th>
                                    <th class="table-title" style="width:10%">Company Name</th>
                                    <th class="table-title" style="width:10%">Contact Person</th>
                                    <th class="table-title" style="width:10%">Address</th>
                                    <th class="table-title" style="width:10%">State</th>
                                    <th class="table-title" style="width:10%">city</th>
                                    <th class="table-title" style="width:5%">Pincode</th>
                                    <th class="table-title" style="width:5%">GST</th>
                                    <th class="table-title" style="width:5%">PAN</th>
                                    <th class="table-title" style="width:10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = mysqli_query($conn, 'select * from user_registrations order by id desc');
                                $i = 1;
                                while ($row = mysqli_fetch_array($query)) {
                                    $get_user_city = get_city_name($conn, $row['city']);
                                    $get_user_state = get_statename($conn, $row['state']);
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $i; ?></td>
                                        <td class="text-center"><?php echo $row['name']; ?></td>
                                        <td class="text-center"><?php echo $row['email']; ?></td>
                                        <td class="text-center"><?php echo $row['mobile']; ?></td>
                                        <td class="text-center"><?php echo $row['company_name']; ?></td>
                                        <td class="text-center"><?php echo $row['contact_person']; ?></td>
                                        <td class="text-center"><?php echo $row['address']; ?></td>
                                        <td class="text-center"><?php echo $get_user_state ?></td>
                                        <td class="text-center"><?php echo $get_user_city ?></td>
                                        <td class="text-center"><?php echo $row['pincode']; ?></td>
                                        <td class="text-center"><?php echo $row['gst']; ?></td>
                                        <td class="text-center"><?php echo $row['pan']; ?></td>

                                        <td class="actions center-content ">

                                            <div class="action-buttons" style="width: 100%;">
                                                <?php
                                                $status_client = $row['client_status'];
                                                if ($status_client == 0) {
                                                    ?>
                                                    <a title="Add to Client" href="add_user_client.php?key=<?php echo md5($row['id']); ?>" class="table-actions btn-edit" id="<?php echo $row['id']; ?>"><i class="fa fa-plus"> Client</i></a>
                                                <?php
                                                } else {
                                                    ?>
                                                    <a title="Client" href="#" class="table-actions disable" id=""><i class="fa fa-check"> Client</i></a>
                                                <?php
                                                }
                                                ?>

                                                <?php
                                                $status_user = $row['user_status'];
                                                if ($status_user == 0) {
                                                    ?>
                                                    <a title="Add to user" href="add_users.php?key=<?php echo md5($row['id']); ?>" class="table-actions btn-edit" id="<?php echo $row['id']; ?>"><i class="fa fa-plus"> User</i></a>
                                                <?php
                                                } else {
                                                    ?>
                                                    <a title="User" href="#" class="table-actions disable" id=""><i class="fa fa-check"> User</i></a>
                                                <?php
                                                }
                                                ?>

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
        $(document).ready(function() {
            		// DataTable initialized by main.js
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
</body>

</html>