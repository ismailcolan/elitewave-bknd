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
        .dataTable th.sorting:before,
        .dataTable th.sorting_asc:after {
            top: 4px;
            right: 2px;
        }

        .dataTable th.sorting:after,
        .dataTable th.sorting_desc:after {
            top: 10px;
            right: 2px;
        }
        @media (min-width: 360px) and (max-width:575.98px) { 

 
 .widget-container .widget-content {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}
.city_tble{
	margin: 0 auto;
	width: max-content!important;
    max-width: unset!important;

    clear: both;
    border-collapse: collapse;
    table-layout: fixed;
}
            th.table-title.sorting {
            width: 111px!important;
           }
           div#dataTable1_filter {
    display: block;
    width: 58%;
}
div#dataTable1_length {
    display: block;
        }

        .dataTables_filter input {
    width: 129px;
 

}
.dataTables_length {
    width: 40%;
    float: left;
    margin: 3px 0 10px;
}

   }

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
                <div class="col-md-3 master_left">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"> <i class="fa fa-plus"></i>City</div>

                        <div class="widget-content padded">
                            <form class="form-horizontal" id="city_form">

                                <input type="hidden" id="form_name" name="form_name" value="add_city">
                                <input type="hidden" id="edit_id" name="edit_id" value="">

                                <div id="response" class="alert alert-danger" style="display:none;">
                                    <div class="message" style="text-align:center"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">City Code <span style="color:red;">*</span> :</label>
                                            <input type="text" id="city_code" Placeholder="Ex:GEC001" name="city_code" class="form-control" disabled />
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">City Name <span style="color:red;">*</span> :</label>
                                            <input type="text" name="city_name" id="city_name" class="form-control" required />
                                            <span class="dup-check"></span>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">State <span style="color:red;">*</span> :</label>
                                            <select name="state_name" class="form-control" id="state_name" required>
                                                <option value="">Select State</option>
                                                <?php
                                                $state_query = "select * from state where status=0 order by state_name";
                                                $state_result = mysqli_query($conn, $state_query);
                                                while ($state_row = mysqli_fetch_array($state_result)) {
                                                ?>
                                                    <option value="<?php echo $state_row['state_id'] ?>"><?php echo $state_row['state_name']; ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="control-label">Via City:</label>
                                            <select name="city" class="form-control" id="city">
                                                <option value="">Select City</option>
                                                <?php
                                                $city_query = "select * from city where status=0 order by city_name";
                                                $city_result = mysqli_query($conn, $city_query);
                                                while ($city_row = mysqli_fetch_array($city_result)) {
                                                ?>
                                                    <option value="<?php echo $city_row['city_id'] ?>"><?php echo $city_row['city_name']; ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                        </div>
                                                <div class="form-group">
    <label class="control-label">Railway Station :</label>
    <input type="text" name="railway_station" id="railway_station" class="form-control">
</div>

<div class="form-group">
    <label class="control-label">Airport :</label>
    <input type="text" name="airport" id="airport" class="form-control">
</div>

<div class="form-group">
    <label class="control-label">Un/Loading Point :</label>
    <input type="text" name="unloading_point" id="unloading_point" class="form-control">
</div>

<div class="form-group">
    <label class="control-label">Warehouse :</label>
    <input type="text" name="warehouse" id="warehouse" class="form-control">
</div>

<div class="form-group">
    <label class="control-label">Port :</label>
    <input type="text" name="port" id="port" class="form-control">
</div>
                                        <div class="form-group">
                                            <input type="checkbox" name="automation" id="automation" /><label>In Transit Automation, Not Required.</label>
                                        </div>
                                    </div>
                                </div><br />
                                <div class="row">
                                    <div class="col-md-12 form-action">
                                        <button class="btn btn-primary" type="button" id="save">Submit</button>
                                        <!-- <button class="btn btn-default-outline  btn-reset" type="button">Cancel</button> -->
                                        <a class="btn btn-default-outline  btn-reset" href="city.php" type="button">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class=" col-md-9 master_right">
                    <div class="widget-container fluid-height clearfix">
                        <div class="heading"> <i class="fa fa-table"></i> List of City </div>
                        <div class="widget-content padded clearfix new_dept">
                            <table class="table table-bordered table-striped city_tble" id="dataTable1">
                                <thead>
                                    <th class="table-title" style="width:10%">S.No</th>
                                    <th class="table-title" style="width:10%">City Code</th>
                                    <th class="table-title" style="width:10%">City Name</th>
                                    <th class="table-title" style="width:10%">State</th>
                           <th class="table-title" style="width:20%">Railway Station</th>
<th class="table-title" style="width:10%">Airport</th>
<th class="table-title" style="width:10%">Un/Loading Point</th>
<th class="table-title" style="width:10%">Warehouse</th>
<th class="table-title" style="width:10%">Port</th>

                                    <th class="table-title" style="width:10%">Action</th>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "select * from city";
                                    $result = mysqli_query($conn, $query);
                                    $i = 1;
                                    while ($row = mysqli_fetch_array($result)) {
                                        $state = get_state_name($conn, $row['state']);

                                    ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i; ?></td>
                                            <td><?php echo $row['city_code']; ?></td>
                                            <td><?php echo $row['city_name']; ?></td>
                                            <td><?php echo $state['state_name']; ?></td>
                                            <td><?php echo $row['railway_station']; ?></td>
<td><?php echo $row['airport']; ?></td>
<td><?php echo $row['unloading_point']; ?></td>
<td><?php echo $row['warehouse']; ?></td>
<td><?php echo $row['port']; ?></td>

                                            <td class="actions center-content ">
                                                <div class="action-buttons">
                                                    <a title="Edit" class="table-actions btn-edit" id="<?php echo $row['city_id']; ?>"><i class="fa fa-pencil"></i></a>
                                                    <?php
                                                    if ($row['status'] == 0) {
                                                    ?>
                                                        <a class="table-actions btn-active" data-status="<?php echo $row['status']  ?>" title="InActive" id="<?php echo $row['city_id'] ?>"><i class="fa fa-check"></i></a>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <a class="table-actions btn-active" style="color:red;" data-status="<?php echo $row['status']  ?>" title="Active" id="<?php echo $row['city_id'] ?>"><i class="fa fa-times"></i></a>
                                                    <?php
                                                    }
                                                    ?>
                                                    <a title="Delete" href="#myModal" class="table-actions btn-trash" data-toggle="modal" id="<?php echo $row['city_id'] ?>"><i class="fa fa-trash-o"></i></a>

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


            <?php require_once("include/footer.php"); ?>
        </div>


        <script type="text/javascript">
            $(document).ready(function() {

                //Duplication
                var dup_chk = true;

                function duplicate_check() {
                    var department_name = $("#department_name").val();
                    var edit_id = $("#edit_id").val();
                    $.ajax({
                        cache: false,
                        url: 'check_existing.php', // url where to submit the request
                        type: "GET", //type of action POST || GET
                        dataType: 'json', // data type
                        async: false,
                        data: {
                            cmd: "chk_department",
                            department_name: department_name,
                            edit_id: edit_id
                        }, // post data || get data
                        success: function(result) {
                            $(".form-data-saving").hide();
                            dup_chk = true;
                            console.log(result);
                            if (result[0] == 1) {
                                $(".dup-check").html(result[1]).css("color", "#f00");
                                dup_chk = false;
                            } else {
                                $(".dup-check").html(result[1]).css("color", "green");
                            }
                        },
                        error: function(jqxhr) {
                            console.log(jqxhr.responseText);
                        }
                    });
                }

                $(document).on('change', '#automation', function() {
                    if ($(this).is(':checked')) {
                        $(this).val('1');
                    } else {
                        $(this).val('0');
                    }
                });

                //button Save
                $(document).on('click', '#save', function() {
                    var data = $('#city_form').serialize();

                    if ($('#city_form').valid() == true) {
                        //$(this).attr("disabled",true);
                        $.ajax({
                            url: "save_details.php",
                            type: "post",
                            dataType: "json",
                            data: data,
                            success: function(result) {
                                console.log(result);
                                if (result == 1) {
                                    $(".form-data-saving").hide();
                                    $("#alert-status").text("");
                                    $("#alert-message").text("Saved Successfully please wait until page refresh");
                                    $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                        $("#alert-container").hide();
                                        $("#alert-container").removeClass("alert-success");
                                        location.reload();
                                    });
                                } else {
                                    $(".form-data-saving").hide();
                                    $("#alert-status").text("Alert !!! ");
                                    $("#alert-message").text("Data Saving Failed");
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
                    }
                });
                $(document).on('click', '.close-popup', function() {
                    $(".form-data-saving").hide();
                    $("#alert-status").text("");
                    $("#alert-message").text("Saved Successfully please wait until page refresh");
                    $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                        $("#alert-container").hide();
                        $("#alert-container").removeClass("alert-success");
                        location.reload();
                    });
                });
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
                        form_name: "del_city",
                        tbl_id: $(this).attr("id")
                    }, function(data, status) {
                        console.log(data);
                        if (data == 1) {
                            $(".form-data-saving").hide();
                            $("#alert-status").text("");
                            $("#alert-message").text("City Deleted successfully...");
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
                            $("#alert-message").text("City deletion failed");
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
                        form_name: "inacv_city",
                        tbl_id: $(this).attr("id"),
                        status: status1
                    }, function(data, status) {
                        console.log(data);
                        if (data == 1) {
                            $(".form-data-saving").hide();
                            $("#alert-status").text("");
                            $("#alert-message").text("City Is " + msg + "...");
                            $("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function() {
                                $("#alert-container").hide();
                                $("#alert-container").removeClass("alert-success");
                                location.reload();
                            });
                        } else if (data == 2) {
                            $(".form-data-saving").hide();
                            $("#alert-status").text("");
                            $("#alert-message").text("City Is " + msg + "...");
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


                //	Button Edit
                $(document).on('click', '.btn-edit', function(ev) {
                    $(".form-data-saving").show();
                    var tbl_id = $(this).attr("id");
                    $.ajax({
                        cache: false,
                        url: 'fetch_details.php', // url where to submit the request
                        type: "GET", // type of action POST || GET
                        dataType: 'json', // data type
                        data: {
                            cmd: "get_city_details",
                            tbl_id: tbl_id
                        }, // post data || get data
                        success: function(result) {
                            console.log(result);
                            $(".form-data-saving").hide();
                            $("#form_name").val("edit_city");
                            $("#edit_id").val(result['city_id']);
                            $("#city").val(result['via_city']);
                            $("#city_name").val(result['city_name']);
                            $("#city_code").val(result['city_code']);
                            $("#state_name").val(result['state']);
                        $("#railway_station").val(result['railway_station']);
$("#airport").val(result['airport']);
$("#unloading_point").val(result['unloading_point']);
$("#warehouse").val(result['warehouse']);
$("#port").val(result['port']);
                            if (result['automation'] == 1) {
                                $("#automation").prop("checked", true);
                            }
                        },
                        error: function(jqxhr) {
                            console.log(jqxhr.responseText);
                        }
                    });
                });


                //Button Reset
                $(document).on('click', '.btn-reset', function(ev) {
                    $('#form_name').val('add_city');
                    $('#edit_id').val('');
                    $('#city_name').val('');
                    $('#city_code').val('');
                    $('#state_name').val('');
                $("#railway_station").val('');
$("#airport").val('');
$("#unloading_point").val('');
$("#warehouse").val('');
$("#port").val('');

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