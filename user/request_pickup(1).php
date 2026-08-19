<?php
if(session_id() == '') {
    session_start();
}
include_once('include/user-function.php');
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
		<title>Gracious Express - Request Pickup</title>
        <?php include("include/title.php"); ?>
        <?php include("include/css_js_forgetpassword.php"); ?>
		<link href="../assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">
		<link href="assets/css/master.css" rel="stylesheet">
        <link href="https://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css" rel="Stylesheet"></link>
        
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@3.5.2/animate.min.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />  
        <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>  
        <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>            
        <link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" /> 
        <link href="stylesheets/datatables.css" media="all" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha512-aOG0c6nPNzGk+5zjwyJaoRUgCdOrfSDhmMID2u4+OIslr0GjpLKo7Xm0Ao3xmpM4T8AmIouRkqwj1nrdVsLKEQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
		<!-- book consignment css and js starts here -->
		<link rel="stylesheet" href="assets/css/book-consignment.css">
		<link rel="stylesheet" href="f5/fontawesome.min.css">
		<!-- book consignment css and js finished here -->
		<script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
		<script src="assets/js/jquery.validate.min.js"></script>
		<script src="assets/js/modernizr.custom.js"></script>
	</head>
    <style>
        .error
        {
            color:red;
        }
        .form-group { 
            position: relative;
        }
        div#autocomplete 
        {
            position: absolute;
            bottom: 0;
        }
        div#autocomplete1 
        {
            position: absolute;
            bottom: 0;
        }

        #consignor_contact:invalid {
        color: red;
        }
        #consignee_contact:invalid {
        color: red;
        }
       
    </style>
  <body>
      
  <?php include "user-db-header.php"?>
      <div class="container-fluid">

          <div class="row">
              <div class="col-xs-12 request-pickup ">
                 
                  
                  <div class="parent-block bg-light" style="background-color:rgb(238 238 238); padding : 15px;">
                  <div class="d-block text-right view-pickup-btn">
                    <a href="request_pickup_list.php" class="btn btn-primary btn-sm" style="">View Pickup List</a>
                    </div>
                  <form id="requestpickup">
                  <input type="hidden" name="form_name" id="form_name" value="add_new_request_pickup">
                     <div class="row">
                         
                     
                             <div class="col-xs-12  col-md-6">
                                <!-- <label for="RFP">Select Consignor Type <span class="error">*</span>:</label>
                                <select name="usertype" id="usertype" class="form-control"  required>
                                    <option value="registered_user">Register Consignor</option>
                                    <option value="new_user">New Consignor</option> 
                                 </select> -->
                                 <label for="RFP">Pickup Request ID <span class="error">*</span>:</label>
                                 <?php
                                 //$con =mysqli_connect("localhost","root","","bookconsignment");
                                 $ref_id = get_rfp_id($conn);
                                 $pickup_id = $ref_id+1;
                                 $pickup_ref_id ="RFP/".sprintf("%'.05d\n",$pickup_id);
                                 ?>
                                 <input type="text" name="request_id" id="request_id" placeholder="E.g (RFP/00001)" value="<?php echo $pickup_ref_id;?>" class="form-control" disabled/>
                                 <label for="Origin">Origin <span class="error">*</span>:</label>
                                 <select name="origin" id="origin" class="form-control"  required>
                                    <option value="">Select Origin</option>
                                    <?php
                                    $origin = mysqli_query($conn,"select *from city where status=0 order by city_name asc");
                                    while($city_list = mysqli_fetch_assoc($origin)) {
                                        ?>
                                    <option value="<?php echo $city_list['city_id'];?>"><?php echo $city_list['city_name'];?></option>
                                    <?php
                                    } 
                                    ?>
                                 </select>
                                 <?php
                                 $username= $_SESSION['user_id'];
                                 $users = mysqli_query($conn,"select *from users where user_id = '$username'");
                                 $user_email = mysqli_fetch_assoc($users);
                                 $email = $user_email['email'];
                            
                                 $query_client = mysqli_query($conn,"select *from client where email = '$email'");
                                 $query_client_id = mysqli_fetch_assoc($query_client);
                                 $client_id =$query_client_id['client_id'];
                                 $client_name =$query_client_id['client_company_name'];
                                 $client_address=$query_client_id['address1'];
                                 $client_contact=$query_client_id['contact_no'];
                                 ?>
                                 <div class="form-group">
                                 <label for="Consignor">Consignor <span class="error">*</span>:</label>
                                 <input type="text" name="consignor" id="consignor" placeholder = "Enter Consignor Name" class="form-control search" value="<?php echo $client_name;?>" required> 
                                 <input type="hidden" name="consignor_id" id="consignor_id">
                                 <div id="autocomplete"></div>
                                 </div>
                                 <div id="newuser_div" style="display:block">
                                <label for="Consignor Email">Consignor Address<span class="error">*</span>:</label>
                                <input type="text" name="consignor_address" id="consignor_address" placeholder="Enter Consignor Address" class="form-control " value="<?php echo $client_address;?>" required>
                                <label for="Consignor Contact">Consignor Contact<span class="error">*</span>:</label>
                                <input type="text" name="consignor_contact" pattern="\d{10}" maxlength="10"  id="consignor_contact" placeholder="Enter Consignor Contact" class="form-control numbers" value="<?php echo $client_contact;?>" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" required>
                                </div>
                                 <label for="Shipping Mode">Mode Of Transport<span class="error">*</span>:</label>
                                 <select name="shipping_mode" id="shipping_mode" class="form-control" required>
                                    <option value="">Select Transport</option>
                                    <?php 
                                    $transport_query ="select * from mode_of_transportation where status=0";
                                    $transport_result = mysqli_query($conn,$transport_query);
                                   while($transport_row = mysqli_fetch_array($transport_result))
                                    {
                                        if( $transport_row['mode_id'] == '4' || $transport_row['mode_id'] == '6') {
                                    ?>
                                    
                                    <?php
                                        }else { ?>
                                   <option value="<?php echo $transport_row['mode_id']; ?> <?php if($transport_row['mode_id']==$row['mode_of_transportation']) echo "selected"; ?>"><?php echo $transport_row['mode_type']; ?></option>
                                <?php 
                                }
                                }    ?>
                                 </select>
                                 <label for="Desc">Description:</label>
                                 <textarea rows="4" name="description" id="description" placeholder="Enter Short Description" class="form-control"></textarea>
                                </div>
                             <div class="col-xs-12 col-md-6">
                            <div class="form-group">
                             <label for="company name">Consignee <span class="error">*</span>:</label>
                             <input type="text" name="consignee" id="consignee" placeholder="Enter Consignee Name" class="form-control search" required>
                             <input type="hidden" name="consignee_id" id="consignee_id" placeholder="Enter Consignee Name" class="form-control search">
                             <div id="autocomplete1"></div> 
                            </div>
                             <div id="newuser_div1" style="display:block">
                                <label for="Consignee Email">Consignee Address<span class="error">*</span>:</label>
                                <input type="text" name="consignee_address" id="consignee_address" placeholder="Enter Consignee Address" class="form-control " required>
                                <label for="Consignee Contact">Consignee Contact<span class="error">*</span>:</label>
                                <input type="text" name="consignee_contact" pattern="\d{10}" maxlength="10"  id="consignee_contact" placeholder="Enter Consignee Contact" class="form-control numbers" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" required>
                            </div>
                             <label for="Destination">Destination <span class="error">*</span>:</label>
                                 <select name="destination" id="destination" class="form-control" required>
                                    <option value="">Select Destination</option>
                                    <?php
                                    $destination = mysqli_query($conn,"select *from city where status=0 order by city_name asc");
                                    while($city_list = mysqli_fetch_assoc($destination)) {
                                        ?>
                                    <option value="<?php echo $city_list['city_id'];?>"><?php echo $city_list['city_name'];?></option>
                                    <?php
                                    } 
                                    ?>
                                 </select>
                                 <label for="Package">No. Of Packages:</label>
                                 <input type="text" name="package_qty" id="package_qty" placeholder="Enter Package Qty" class="form-control" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off">
                                 <label for="Package Type">Package Type<span class="error">*</span>:</label>
                                 <select name="package_type" id="package_type" class="form-control" required>
                                     <option value="">Select Package Type</option>
                                    <?php
                                    $package_type = mysqli_query($conn,"select * from package where status='0'");
                                    while($package_list = mysqli_fetch_assoc($package_type)){
                                        ?>
                                        <option value="<?php echo $package_list['package_id'];?>"><?php echo $package_list['package_code'];?></option>
                                        <?php
                                    }
                                    ?>
                                 </select>
                                 <label for="Weight">Approx.Weight:</label>
                                 <input type="text" name="weight" id="weight" placeholder="Enter Weight in Kgs" class="form-control" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9\.]+/g, '');" onpaste="return false;" autocomplete="off" >
                             
                                </div>
                            
                             <hr>
                             <input type="submit" class="form-control btn-info btn-block" name="submit" id="submit" Value="Request Pickup"  >
                     </div>
                    </form>
                  </div>
                  
              </div>
          </div>

      </div>

	<script>
        $(document).ready(function(){
            $(document).submit('click #submit',function(e){
                 e.preventDefault();
                $(".form-data-saving").show();
                // alert("clicked");
                var form_data = new FormData(document.getElementById("requestpickup"));
                // if($('#requestpickup').valid() == true){
                    $.ajax({
                        url:"<?php print_r(site_paths) ?>save_details.php",
                        processData: false,
                        contentType: false,
                        cache: false,
                        type:"post",
                        data: form_data,
                        success:function(data){
                            console.log(data);
                            if(data != 0){
                            $(".form-data-saving").hide();
                            $("#alert-status").text("");
                            $("#alert-message").text("Request For Pickup Successfully Sent.");
                            $("#alert-container").addClass('alert-success').slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
                            $('#alert-container').hide();
                            $('#alert-container').removeClass('alert-success');
                            setTimeout(function(){ window.location.href="<?php print_r(site_paths) ?>user/request_pickup_list.php";},2000);

                            });
                            //$('#requestpickup')[0].reset();

                            //setTimeout(function(){document.location.href = "http://localhost/graciousexpress/user/request_pickup_list.php"},1000);

                            }else{
                            $(".form-data-saving").hide();
                            $("#alert-status").text("");
                            $("#alert-message").text("Request For Pickup Failed.");
                            $("#alert-container").addClass('alert-danger').slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
                            $('#alert-container').hide();
                            $('#alert-container').removeClass('alert-danger');
                            });
                            location.reload();
                            }
                           
                        }
                    });
               // }
            });

            $('.search').on('keyup',function(e){
                var term = $(this).val();
                var user_id = <?php echo $username;?>;
                //alert(user_id);
                //alert(term);
                console.log('<?php print_r(site_paths) ?>autocomplete.php?autocomplete=get_consigner&user_id='+user_id+'&term='+term);
                $('#consignor').autocomplete({
                    source:'<?php print_r(site_paths) ?>autocomplete.php?autocomplete=get_consigner&user_id='+user_id+'&term='+term,
                    minLength : 0,
                    appendTo: "#autocomplete",
                    open: function() {
                        var position = $("#autocomplete").position(),
                            left = position.left, top = position.top;

                        $("#autocomplete > ul").css({left: left + 20 + "px",
                                                top: top + 4 + "px" });

                    },
                    select : function(event,ui){
                        
                        $("#consignor").val(ui.item.value);
                        $("#consignor").val(ui.item.id);
                        $.ajax({
                            url:"<?php print_r(site_paths) ?>fetch-details.php",
                            type:'GET',
                            dataType:"JSON",
                            data:{cmd:"get_consignor",tbl_id:ui.item.id},
                            async:false,
                            success:function(data){
                                console.log(data);
                                $("input[id=consignor_id]").val(data['client_id']);
                                $("#consignor_address").val(data['address1']);
                                $("#consignor_contact").val(data['contact_no']);
                                
                            }   
                        });
                    }
                });

                $('#consignee').autocomplete({
                    source:'<?php print_r(site_paths) ?>autocomplete.php?autocomplete=get_username&user_id='+user_id+'&term'+term,
                    minLength : 0,
                    appendTo: "#autocomplete1",
                    open: function() {
                        var position = $("#autocomplete1").position(),
                            left = position.left, top = position.top;

                        $("#autocomplete1 > ul").css({left: left + 20 + "px",
                                                top: top + 4 + "px" });

                    },
                    select : function(event,ui){
                        
                        $("#consignee").val(ui.item.value);
                        $("#consignee").val(ui.item.id);

                        $.ajax({
                            url:"<?php print_r(site_paths) ?>fetch-details.php",
                            type:'GET',
                            dataType:'json',
                            data:{cmd:"get_consignor",tbl_id:ui.item.id},
                            async:false,
                            success:function(data){
                                console.log(data);
                                $("input[id=consignee_id]").val(data['client_id']);
                                $("#consignee_address").val(data['address1']);
                                $("#consignee_contact").val(data['contact_no']);
                                
                            }   
                        });
                    }
                });
            });
            
        
            $('#usertype').on('change',function(){
                // alert("hi")
                if( $(this).val()==="new_user"){
                    $('#newuser_div').show();
                    $('#newuser_div1').show();
                }else{
                    $('#newuser_div').hide();
                    $('#newuser_div1').hide();

                }
            });
            $('input.numbers').keypress(function(event){
                return /\d/.test(String.fromCharCode(event.keyCode));
            });
        });
         $(window).load(function() {
				$(".loading-page").hide();
			});
    </script>
     <script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
     <script src="https://code.jquery.com/ui/1.10.2/jquery-ui.js" ></script>	
             <?php include_once('include/user-footer-js.php');?>
        
        <div class="alert" id="alert-container" style="display:none;">
		<button type="button" class="close" data-dismiss="alert">x</button>
		<strong id="alert-status"></strong>
		<span id="alert-message"></span>
		</div>

	</body>
</html>
