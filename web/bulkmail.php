<?php
require_once("include/connect.php");
require_once("include/function.php"); 
require_once '../swift_mailer/vendor/autoload.php';

$key = $_REQUEST['key'];
if($key !=''){
	$client_query = "select * from client where md5(client_id)='".$key."'";
	$client_result = mysqli_query($conn,$client_query);
	$client_count = mysqli_num_rows($client_result);
	if($client_count == 0){
		header('Location:client_list.php');
	}
}

?>
<!DOCTYPE html>
<html>
  <head>
  <?php include("include/title.php"); ?>
  <?php include("include/css_js.php"); ?>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
<style>
	.top-buffer { margin-top:20px; }
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


			<div class="widget-container fluid-height clearfix" id="add_expected_delivery_form">
			  <div class="heading"> <i class="fa fa-plus"></i>Send Bulk Emails <span class="align-right"></span></div>
			  
			  <div class="widget-content padded">
				<form class="form-horizontal" id="" method="post" >
				
				<?php if($_REQUEST['key'] != ''){?>
					<input type="hidden" id="form_name" name="form_name" value="edit_mail_form">
					<input type="hidden" id="edit_id" name="edit_id" value="<?php echo $_REQUEST['key']; ?>">
					<?php } else{?>
					<input type="hidden" id="form_name" name="form_name" value="mail_form">
					<input type="hidden" id="edit_id" name="edit_id" value="">
			       <?php
				}?>
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
				<br/>
				 <div class="row">
					 
						<!-- <div class="col-md-offset-1 col-md-5">
						<?php
							//$conn = mysqli_connect("localhost","root","","bookconsignment");
							// $query = "select * from expectded_delivery where md5(id)='".$_REQUEST['key']."'";
							// $result = mysqli_query($conn,$query);
							// $row = mysqli_fetch_array($result);
							
							//$unique_billing_code = sprintf("%02d",1).'-'.$billing_code;

						?>
						<div class="form-group">
								<label class="control-label">To <span style="color:red;">*</span> :</label>
								<select name="email_group" id="email_group" class="form-control">
								<option value="">Select Mail Group:</option>
								<option value="1">Group 1</option>
								<option value="2">Group 2</option>
								<option value="3">Group 3</option>
								</select>
				
								<span class="dup-check"></span>
							</div>
						</div> -->
						<div class="col-md-offset-1 col-md-10">
							
						<div class="form-group mt-2">
								<label class="control-label">Subject:</label>
								<input type="text" name="subject" id="subject" class="form-control" value=""  placeholder="Enter Subject" required autocomplete="
                                off"/>
								<span class="dup-check"></span>
							</div>
							<div class="form-group ">
								<br>
							</div>
						 
						</div>
						<div>

						</div>			
						<div class="col-md-offset-1 col-md-10">
					 <div class="form-group">
							<textarea  id="email_content" name="email_content" class="bootstrap-tagsinput" data-role="tagsinput" required></textarea>
							</div>
					 </div>
				 </div>
				   <div class="row">
					<div class="col-md-12 form-action">
					<?php if($_REQUEST['key']== ''){?>
						<!-- <input type="submit" class="btn btn-primary"  name="submit" value="Send" /> -->
						<button class="btn btn-primary" id="save" >Send</button>
						<button  class="btn btn-default-outline  btn-reset" type="button" onclick="window.location.href='bulkmail.php';">Cancel</button>
					</div>
					<?php 
					}else{?>
						<button class="btn btn-primary" type="button" id="update">Update</button>
						<button  class="btn btn-default-outline  btn-reset" type="button" onclick="window.location.href='bulkmail.php';">Cancel</button>
					<?php }?>
					</div>
				  </div>
				</form>
			  </div>
			</div>
		  </div>

		</div>
	</div>

		<?php require_once("include/footer.php"); ?>
	</div>	
	<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
	<script>
    CKEDITOR.replace( 'email_content' );
</script>
		
		<script type="text/javascript">
		$(document).ready(function(){

		//Duplication
		var dup_chk = true;
	   function duplicate_check(){
			/* var key = e.keyCode;
                     if (key >= 48 && key <= 57) {
                        e.preventDefault();
                    } */
			var edit_id = $('#edit_id').val();
		
			var origin = $('#origin').val();
			var destination = $('#destination').val();
			// alert(email_check);
		$.ajax({
				cache: false,
				url: 'check_existing.php', // url where to submit the request
				type : "POST", //type of action POST || GET
				dataType : 'json',// data type
				async: false,
				data : {cmd: "chk_estimated_origin_destination",origin:origin, destination: destination, edit_id: edit_id}, // post data || get data
				success : function(result) {
				      $(".form-data-saving").hide();
					dup_chk = true;
					console.log(result);
					
					if(result[0] == "1"){
						
						$("#origin_dup-check").html(result[1]).css("color","#f00");
						$("#destination_dup-check").html(result[1]).css("color","#f00");
						dup_chk = false;
					}
					else{
						$("#origin_dup-check").html(result[1]).css("color","green");
						$("#destination_dup-check").html(result[1]).css("color","green");
					}
				},
				error: function(jqxhr) {
					console.log(jqxhr.responseText);
				}
			});

		}
			$('#surface,#express,#air,#train').keypress(function (event) {
					return isNumber(event, this)
				});
				
			function isNumber(evt, element) {
				var charCode = (evt.which) ? evt.which : event.keyCode

				if ((charCode != 45 || $(element).val().indexOf('-') != -1) &&      // “-” CHECK MINUS, AND ONLY ONE.
					(charCode != 46 || $(element).val().indexOf('.') != -1) &&      // “.” CHECK DOT, AND ONLY ONE.
					(charCode < 48 || charCode > 57))
					return false;
					return true;
			} 


			//New

		// 	$("#save").on('click',function(e){
        //     $("#save").attr("disabled", true);
        //     $(".loading").show();  

        //     e.preventDefault();
        //    // alert("hi");
        //    var Content = CKEDITOR.instances['email_content'].getData();
        //    var user_id = '<?php echo $specific_user;?>';
        //     //alert(Content);

        //     $.ajax({
        //         url:"sendmail.php",
        //         type:"post",
        //         dataType:"JSON",
        //         //cache: false,
        //         data:{Content:Content,user_id:user_id},
        //         success:function(data){
        //             //console.log(data.message);
        //             //swal("Good job!", data.message , "success");
        //             swal({
        //                 title: "Great!",
        //                 text: data.message,
        //                 icon: "success",
        //                 button: "Ok",
        //             }).then(function(isConfirm){
        //                 if(isConfirm){
        //                 location.reload();
        //                 }else{
        //                 location.reload();
        //                 }
        //             });
        //             $(".loading").hide(); 
        //             $("#save").attr("disabled", false);

                    
        //         },
        //         error: function(XMLHttpRequest, textStatus, errorThrown) { 
        //             alert("Status: " + textStatus); alert("Error: " + errorThrown); 
        //         } 
              
        //     });
        // });


			//end

		//button Save
			$(document).on('click','#save',function(){
				//alert("hi");
				

				var form_name =$('#form_name').val();
				var Content = CKEDITOR.instances['email_content'].getData();
				var to_email = $("#email_group").val();
				var subject = $("#subject").val();
				if(Content && subject != ''){
                    $(".form-data-saving").show();
                    $(this).attr("disabled",true);
					$.ajax({
						url:"../save_details.php",
						type:"post",
						data:{form_name:form_name,Content:Content,to_email:to_email,subject:subject},
						success:function(result){
							//console.log(result);
							if(result != 0){
								$(".form-data-saving").hide();
								$("#alert-status").text("");
								$("#alert-message").text("Mail Sent Successfully please wait until page refresh");
								$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-success");

								location.href="bulkmail.php";
								$(this).attr("disabled",false);
								});
							}
							else
							{
								$(".form-data-saving").hide();
								$("#alert-status").text("Alert !!! ");
								$("#alert-message").text("Mail Sent Failed");
								$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-danger");

								});
							}
						},
						error:function(jqxhr)
						{
						ewToast(jqxhr.responseText, 'error');
					}
					});
                }else{
                    
							ewToast("Please Enter Details", 'warning');
						
                }
					
			
			});
		$(document).on('click','.close-popup',function(){
				$(".form-data-saving").hide();
				$("#alert-status").text("");
				$("#alert-message").text("Saved Successfully please wait until page refresh");
				$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
				$("#alert-container").hide();
				$("#alert-container").removeClass("alert-success");
				location.reload();
				});
			});
		
		//Active Inactive
			$(document).on('click', '.btn-active', function(ev){
				$(".form-data-saving").show();
				var status1='';
				var msg='';
				var status = $(this).attr('data-status');
				if(status == '1'){
					status1='0';
					msg = "Activated";
				}
				else{
					status1='1';
					msg = "In-Activated";
				}
				$.post('save_details.php', { form_name: "inacv_client", tbl_id: $(this).attr("id"),status:status1}, function(data,status){
					console.log(data);
					if(data == 1){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Client Is "+msg+"...");
						$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
						$("#alert-container").hide();
						$("#alert-container").removeClass("alert-success");
							location.reload();
						});
					}
					
					else if(data == 2){
						$(".form-data-saving").hide();
						$("#alert-status").text("");
						$("#alert-message").text("Client Is "+msg+"...");
						$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
						$("#alert-container").hide();
						$("#alert-container").removeClass("alert-danger");
							location.reload();
						});
					}
					else if(data == "404-del"){
						$(".delete-error-popup").show();
						$(".form-data-saving").hide();
					}
					
				});
			});
			

			//UPDATE

			// $(document).on('click','#update',function(){
			// 	var data = $('#add_expected_delivery_form').serialize();
			// 	//var edit_id = $("#edit_id").val();
			// 	//duplicate_check();
			// 	if($('#add_rate_form').valid() == true && dup_chk)
			// 	{
			// 		$(this).attr("disabled",true);
			// 		$.ajax({
			// 			url:"../save_details.php",
			// 			type:"post",
			// 			data:data,
			// 			success:function(result){
			// 				console.log(result);
			// 				if(result != 0){
			// 					$(".form-data-saving").hide();
			// 					$("#alert-status").text("");
			// 					$("#alert-message").text("Data Updated Successfully please wait until page refresh");
			// 					$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
			// 					$("#alert-container").hide();
			// 					$("#alert-container").removeClass("alert-success");
			// 					location.href="rate_calc_list.php";
			// 					});
			// 				}
			// 				else
			// 				{
			// 					$(".form-data-saving").hide();
			// 					$("#alert-status").text("Alert !!! ");
			// 					$("#alert-message").text("Data Update Failed");
			// 					$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
			// 					$("#alert-container").hide();
			// 					$("#alert-container").removeClass("alert-danger");
			// 					});
			// 				}
			// 			},
			// 			error:function(jqxhr)
			// 			{
			// 				alert(jqxhr.responseText);
			// 			}
			// 		});
			// 	}
			// });

			
			//Button Reset
			$(document).on('click', '.btn-reset', function(ev){
				$('#form_name').val('add_branch');
				$('#edit_id').val('');
				$('#department_name').val('');
				$('#department_code').val('');
			});


			//Send mail
			
            

				
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
				
		<div class="delete-error-popup" >
		    <div class="popup_overlay" id="popup_overlay"></div>
			<div class="popup" id="popup">
			    <div class="popup_message">
			    <h5 class="popup-title">Alert ! </h5>
				    This Data Cannot Delete.Used by another record. so you can't Delete !!! <br/> &nbsp; <br/>
			    <button class="btn btn-sm btn-danger delete-error-popup-close" id="">Close</button> <br/> &nbsp; <br/>
			    </div>
			    <!--<span class="popup_close" id="popup_close">X</span>-->
			</div>
		</div>
		
  </body>
</html>