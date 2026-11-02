<?php
require_once("include/connect.php");
require_once("include/function.php");
require_once("include/ew_master_usage_helpers.php");
ew_geo_scope_ensure_column($conn, 'city');
ew_geo_scope_ensure_column($conn, 'state');
ew_area_ensure_country_name($conn, 'city');
$geo_scope = ew_geo_scope_from_request();
$city_page_title = 'City Master';
?>
<!DOCTYPE html>
<html>

<head>
    <?php include("include/title.php"); ?>
    <?php include("include/css_js.php"); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <style>
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
                <div class="col-md-12">
                    <div class="ew-page-v2 ew-page-v2--wide-table">
                        <div class="ew-page-head">
                            <div class="ew-page-head-left">
                                <h1 class="ew-page-title"><?php echo htmlspecialchars($city_page_title); ?></h1>
                            </div>
                        </div>
                        <div class="ew-card">
                            <div class="ew-card-toolbar">
                                <h2>City List</h2>
                                <div class="ew-toolbar-right">
                                    <a href="city_export.php" class="ew-btn-v2 ew-btn-v2-outline" title="Download Excel copy">
                                        Export Excel <i class="fa fa-download"></i>
                                    </a>
                                    <button type="button" class="ew-btn-v2 ew-btn-v2-primary" id="openCreateCity">
                                        Create <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="ew-table-wrap widget-content padded clearfix new_dept">
                            <table class="table table-bordered table-striped city_tble" id="dataTable1">
                                <thead>
                                    <th class="table-title" style="width:10%">S.No</th>
                                    <th class="table-title" style="width:10%">City Code</th>
                                    <th class="table-title" style="width:10%">City Name</th>
                                    <th class="table-title" style="width:10%">Country</th>
                                    <th class="table-title" style="width:10%">State</th>
                           <th class="table-title" style="width:20%">Railway Station</th>
<th class="table-title" style="width:10%">Airport</th>
<th class="table-title" style="width:14%">FTL / FCL Loading / Unloading City</th>
<th class="table-title" style="width:10%">Warehouse</th>
<th class="table-title" style="width:10%">Port</th>

                                    <th class="table-title" style="width:10%">Action</th>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "select * from city where " . ew_geo_scope_where($conn);
                                    $result = mysqli_query($conn, $query);
                                    $city_rows = array();
                                    if ($result) {
                                        while ($row = mysqli_fetch_array($result)) {
                                            $city_rows[] = $row;
                                        }
                                    }
                                    $city_usage = ew_master_usage_map($conn, 'city', array_column($city_rows, 'city_id'));
                                    $i = 1;
                                    foreach ($city_rows as $row) {
                                        $state = get_state_name($conn, $row['state']);

                                    ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i; ?></td>
                                            <td><?php echo $row['city_code']; ?></td>
                                            <td><?php echo $row['city_name']; ?></td>
                                            <td><?php echo htmlspecialchars($row['country_name'] ?? ''); ?></td>
                                            <td><?php echo $state; ?></td>
                                            <td><?php echo $row['railway_station']; ?></td>
<td><?php echo $row['airport']; ?></td>
<td><?php echo $row['unloading_point']; ?></td>
<td><?php echo $row['warehouse']; ?></td>
<td><?php echo $row['port']; ?></td>

                                            <td class="actions center-content ">
                                                <div class="action-buttons">
                                                    <?php echo ew_master_edit_action_html(array(
                                                        'in_use' => !empty($city_usage[(int) $row['city_id']]),
                                                        'button_id' => (int) $row['city_id'],
                                                        'button_class' => 'table-actions btn-edit',
                                                    )); ?>
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


                $('#openCreateCity').on('click', function() {
                    $('#form_name').val('add_city');
                    $('#edit_id').val('');
                    $('#city_name').val('');
                    $('#country_name').val('');
                    $.getJSON('fetch_details.php', { cmd: 'get_city_next_code', scope: $('#geo_scope').val() }, function(data) {
                        $('#city_code').val(data && data.city_code ? data.city_code : '');
                    });
                    $('#state_name').val('');
                    $('#city').val('');
                    $('#railway_station').val('');
                    $('#airport').val('');
                    $('#unloading_point').val('');
                    $('#warehouse').val('');
                    $('#port').val('');
                    $('#automation').prop('checked', false);
                    $('#cityModalTitle').text('Create City');
                    ewV2OpenModal('cityModal');
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
                            $("#country_name").val(result['country_name'] || '');
                            $("#city_code").val(result['city_code']);
                            $("#state_name").val(result['state']);
                        $("#railway_station").val(result['railway_station']);
$("#airport").val(result['airport']);
$("#unloading_point").val(result['unloading_point']);
$("#warehouse").val(result['warehouse']);
$("#port").val(result['port']);
                            if (result['automation'] == 1) {
                                $("#automation").prop("checked", true);
                            } else {
                                $("#automation").prop("checked", false);
                            }
                            $('#cityModalTitle').text('Edit City');
                            ewV2OpenModal('cityModal');
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
                    $('#country_name').val('');
                    $('#city_code').val('');
                    $('#state_name').val('');
                $("#railway_station").val('');
$("#airport").val('');
$("#unloading_point").val('');
$("#warehouse").val('');
$("#port").val('');
                    $('#city').val('');
                    $('#automation').prop('checked', false);
                    ewV2CloseModal('cityModal');
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

        <div class="ew-v2-modal-backdrop" id="cityModal">
            <div class="ew-v2-modal ew-v2-modal--lg">
                <div class="ew-v2-modal-head">
                    <h3 id="cityModalTitle">Create City</h3>
                    <button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
                </div>
                <div class="ew-v2-modal-body">
                    <form class="form-horizontal" id="city_form">
                        <input type="hidden" id="form_name" name="form_name" value="add_city">
                        <input type="hidden" id="edit_id" name="edit_id" value="">
                        <input type="hidden" id="geo_scope" name="geo_scope" value="<?php echo htmlspecialchars($geo_scope, ENT_QUOTES, 'UTF-8'); ?>">
                        <div id="response" class="alert alert-danger" style="display:none;">
                            <div class="message" style="text-align:center"></div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">City Code <span style="color:red;">*</span> :</label>
                            <input type="text" id="city_code" placeholder="<?php echo htmlspecialchars(ew_master_format_code(ew_master_code_prefix('City Master'), 1)); ?>" name="city_code" class="form-control" disabled />
                        </div>
                        <div class="form-group">
                            <label class="control-label">City Name <span style="color:red;">*</span> :</label>
                            <input type="text" name="city_name" id="city_name" class="form-control" required />
                            <span class="dup-check"></span>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Country <span style="color:red;">*</span> :</label>
                            <input type="text" name="country_name" id="country_name" class="form-control" required autocomplete="off" />
                        </div>
                        <div class="form-group">
                            <label class="control-label">State <span style="color:red;">*</span> :</label>
                            <select name="state_name" class="form-control" id="state_name" required>
                                <option value="">Select State</option>
                                <?php
                                $state_query = "select * from state where status=0 and " . ew_geo_scope_where($conn) . " order by state_name";
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
                                $city_query = "select * from city where status=0 and " . ew_geo_scope_where($conn) . " order by city_name";
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
                            <label class="control-label">FTL / FCL Loading / Unloading City :</label>
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
                    </form>
                </div>
                <div class="ew-v2-modal-foot">
                    <button type="button" class="btn btn-default-outline btn-reset" data-ew-v2-close>Cancel</button>
                    <button class="btn btn-primary" type="button" id="save">Submit</button>
                </div>
            </div>
        </div>

</body>

</html>