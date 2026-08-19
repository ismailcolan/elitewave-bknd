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
        a.disable {
            pointer-events: none;
            cursor: default;
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
    </style>
</head>

<body class="page-header-fixed bg-1">
    <div class="modal-shiftfix">
        <div class="navbar navbar-fixed-top scroll-hide">
            <?php include_once ('include/header.php'); ?>
            <?php include_once ('include/menu.php'); ?>
        </div>
    </div>
    <div class="container-fluid main-content new_dpt_bottom">
        <div class="row">
            <div class="col-md-offset-1 col-md-10">
                <div class="widget-container fluid-height clearfix">
                    <div class="heading"> <i class="fa fa-table"></i> List of POD <span class="align-right"><i class="fa fa-plus"></i> <a href="pod_master.php">Add POD</a></span> </div>
                    <div class="widget-content padded clearfix new_dept">
                        <table class="table table-bordered table-striped" id="dataTable1">
                            <thead>
                                <th class="table-title" style="width:1%">S.No</th>
                                <th class="table-title" style="width:5%">Date</th>
                                <th class="table-title" style="width:5%">Images Count</th>


                                <!-- <th class="table-title" style="width:1%">Consignor</th>
								<th class="table-title" style="width:1%">Consignee</th> -->
                                <th class="table-title" style="width:1%">Action</th>
                            </thead>
                            <tbody>
                                <?php

                                // $conn1 = mysqli_connect("localhost","root","","bookconsignment");

                                $query = 'select *from pod_files order by id desc';
                                $query_result1 = mysqli_query($conn, $query);
                                $i = 1;
                                while ($pod_list = mysqli_fetch_assoc($query_result1)) {
                                    $screens = explode('@@', $pod_list['screens']);
                                    $unique_screens = array_filter($screens);
                                    ?>

                                    <tr>
                                        <td class="text-center"><?php echo $i; ?></td>
                                        <td class="text-center"><?php echo $pod_list['created_at']; ?></td>
                                        <td class="text-center"><?php echo count($unique_screens); ?></td>
                                       <!-- <td class="text-center"><?php echo $pod_list['screens']; ?></td>
                                         -->
                                        <td class="text-center">

                                            <a title="Edit" href="edit_pod_list.php?key=<?php echo md5($pod_list['id']); ?>" class="table-actions btn-edit" id="<?php echo $pod_list['id']; ?>"><i class="fa fa-pencil"></i></a>

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
            // e.preventDefault();


            // $(document).on('click','.btn-edit',function(e){
            //     $(".form-data-saving").show();
            //     var tbl_id = $(this).attr("id");
            //     alert(tbl_id);
            //     $.ajax({
            //         url:"../fetch-details.php",
            //         cache: false,
            //         type: "post",
            //         dataType:"json",
            //         data:{cmd:"get_new_user_detail",tbl_id:tbl_id},
            //         success:function(data){
            //             console.log(data);
            //             $(".form-data-saving").hide();
            //             //$("#form_name").val("new_client");
            //             $("#edit_id").val(data['user_id']);

            //         },
            //         error: function(jqxhr) {
            // 				alert(jqxhr.responseText);
            // 			}
            //     });

            // });


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