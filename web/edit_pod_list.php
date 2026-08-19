<?php
require_once("include/connect.php");
require_once("include/function.php"); 

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
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<script src="https://malsup.github.io/jquery.form.js"></script>
	<style>
img.img-thumbnail.imgheight {
    height: 208px;
    width: 204px;
}

.gra-btn{
	padding: 8px 12px;
}
.file-upload
{display:block;text-align:center;font-family: Helvetica, Arial, sans-serif;font-size: 12px;}

.file-upload .file-select
{
	display: block;
    border: 2px solid #34495e;
    color: #34495e;
    cursor: pointer;
    height: 40px;
    line-height: 40px;
    text-align: left;
    background: #FFFFFF;
    overflow: hidden;
    position: relative;
}

.file-upload .file-select .file-select-button
{
	background: #34495e;
    padding: 0 10px;
    color: white;
    display: inline-block;
    height: 40px;
    line-height: 40px;
}
.file-upload .file-select .file-select-name{line-height:40px;display:inline-block;padding:0 10px;}
.file-upload .file-select:hover{border-color:#0A1E3D;transition:all .2s ease-in-out;-moz-transition:all .2s ease-in-out;-webkit-transition:all .2s ease-in-out;-o-transition:all .2s ease-in-out;}
.file-upload .file-select:hover .file-select-button{background:#0A1E3D;color:#FFFFFF;transition:all .2s ease-in-out;-moz-transition:all .2s ease-in-out;-webkit-transition:all .2s ease-in-out;-o-transition:all .2s ease-in-out;}
.file-upload.active .file-select{border-color:#3fa46a;transition:all .2s ease-in-out;-moz-transition:all .2s ease-in-out;-webkit-transition:all .2s ease-in-out;-o-transition:all .2s ease-in-out;}
.file-upload.active .file-select .file-select-button{background:#3fa46a;color:#FFFFFF;transition:all .2s ease-in-out;-moz-transition:all .2s ease-in-out;-webkit-transition:all .2s ease-in-out;-o-transition:all .2s ease-in-out;}
.file-upload .file-select input[type=file]{z-index:100;cursor:pointer;position:absolute;height:100%;width:100%;top:0;left:0;opacity:0;filter:alpha(opacity=0);}
.file-upload .file-select.file-select-disabled{opacity:0.65;}
.file-upload .file-select.file-select-disabled:hover{cursor:default;display:block;border: 2px solid #dce4ec;color: #34495e;cursor:pointer;height:40px;line-height:40px;margin-top:5px;text-align:left;background:#FFFFFF;overflow:hidden;position:relative;}
.file-upload .file-select.file-select-disabled:hover .file-select-button{background:#dce4ec;color:#666666;padding:0 10px;display:inline-block;height:40px;line-height:40px;}
.file-upload .file-select.file-select-disabled:hover .file-select-name{line-height:40px;display:inline-block;padding:0 10px;}
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
  
		<div class="row" >
		  <div class="col-md-offset-1 col-md-10">
			<div class="widget-container fluid-height clearfix">
			<div class="heading"> <i class="fa fa-plus"></i>Update POD Images List<span class="align-right"><i class="fa fa-plus"></i><a href="pod_list.php">View List</a></span></div>	  
			  <div class="widget-content padded">
				<form class="form-horizontal" id="edit_barcode_form">
				
					<input type="hidden" id="form_name" name="form_name" value="pod_retrive">
					<input type="hidden" id="edit_id" name="edit_id" value="<?php echo $_REQUEST['key']; ?>"> 
					
					<div id="response" class="alert alert-danger" style="display:none;">
						<div class="message" style="text-align:center"></div>
					</div>
					
					
				<br/>
					
				<div class="row">
				<div class="col-sm-offset-4 col-sm-6">
						<div class="form-group">
										<div class="col-md-6">
										<label class="control-label">Select POD:</label>
										<!-- <input type="file" name="pod_file[]" id="pod_file"  class="form-control" multiple> -->
										<div class="file-upload">
										<div class="file-select">
											<div class="file-select-button" id="fileName">Choose File</div>
											<div class="file-select-name" id="noFile">No file chosen...</div> 
											<input type="file" name="pod_file[]" id="pod_file" multiple>
											
										</div>
										<small>Allowed Types : JPEG, JPG, PNG Format</small>
										</div>
										

										</div>

										<div class="col-md-6">						
										<button class="btn btn-primary gra-btn" type="submit" style="margin-top: 21px;"  id="upload"><i class="fa fa-refresh" aria-hidden="true"></i> Update</button>
										</div>
							</div>
							
							</div>	
</div><hr>
				<?php
		 if($_REQUEST['key'] != ''){
			$pod_id = $_REQUEST['key'];
			//$conn1 = mysqli_connect("localhost","root","","bookconsignment");
				
			$sql = "select * from pod_files where md5(id)='$pod_id'";
				$sql_rese = mysqli_query($conn,$sql);
				$row = mysqli_fetch_assoc($sql_rese);
				$screens = explode('@@',$row['screens']);
		
				$result = array_filter($screens);      
				// echo "<pre>";
				// print_r($result);
				// echo "</pre>";
				foreach($result as $key => $image2){
					 
						?>	
					
					<div class="col-md-2 col-xs-6">
					<div class="form-group" id="img_del_<?php echo $key;?>">
							<img src="../pod_uploads/<?php echo $image2;?>" class="img-thumbnail imgheight" />
                            <p><?php echo $image2;?></p>
								<div class="text-center " style="margin-top:10px">
								<input type="submit" data-id="<?php echo $image2;?>" name="delete_img" value="Remove" id="<?php echo $key;?>" class="img_delete btn btn-danger">
								</div>
								
							</div>
							
					</div>
					
			<?php
			
					
				}
		}
		?>	
				 </div>
				 <br/>
	
				</form>
				
			  </div>
			</div>
		  </div>

		</div>
	</div>
	
	<!--Qrcode Print Section -->	
	</div>	
	
		<?php require_once("include/footer.php"); ?>
	</div>
	
		<script type="text/javascript">
		$(document).ready(function(){
			$('body').bind('change',function(){
				
				var numFiles2 = $("input:file",this)[0].files.length;
				//console.log(numFiles2);
					if (/^\s*$/.test(numFiles2)) {
						
							$(".file-upload").removeClass('active');
						$("#noFile").text("No file chosen..."); 
					}
					else {
					
						$(".file-upload").addClass('active');
						$("#noFile").text(numFiles2 +" files "); 
					}
				});
			//Image Remove 
			
			$(document).on('click','.img_delete',function(e){
				e.preventDefault();
				
				var id = $(this).attr('data-id');
				
				var hideid = $(this).attr('id'); 
				var form_name = 'delete_pod_img';
				
				var tbl_id = $("#edit_id").val();
				
				
				if (!confirm("Do you want to delete"))
				{
					
					// result.remove();
                	return false;
				}else{
					$("#img_del_"+hideid).hide();
					
					$.ajax({
					url:'save_details.php?delete_id='+id,
					type:"post",
					data:{form_name:form_name,tbl_id:tbl_id},
					success:function(data){
						//console.log(data);
						//location.reload();
					}
				})
				}
	
			})


			//Update POD
			$('#upload').click(function(e){ 
				e.preventDefault();

				$(".loading-page").show();

				var formdata = new FormData(document.getElementById("edit_barcode_form"));
				// var files = $('#pod_file')[0].files;
				var files = $("#pod_file").prop('files')[0];
				var edit_id = $('#edit_id').val();
				
				var form_name = $("#form_name").val();;

				if($("#pod_file").prop('files')[0]){

					formdata.append('file',files);
					formdata.append('form_name',form_name);
					formdata.append('edit_id',edit_id);
					$.ajax({
						url:'save_details.php',
						type:'post',
						data:formdata,
						contentType:false,
						processData:false,
						success:function(data){
							console.log(data.messge);
							if(data != 0){
								$(".loading-page").hide();
								$("#alert-status").text("");
								$("#alert-message").text("File Upload Successfully ");
								$("#alert-container").addClass("alert-success").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-success");
								// location.reload();
								// window.location.href = "pod_master.php";
							 	});
							}else{
								
								$(".loading-page").hide();
								$(".form-data-saving").hide();
								$("#alert-status").text("Alert !!! ");
								$("#alert-message").text("Fail Upload Failure");
								$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-danger");
								// location.reload();
								// window.location.href = "pod_master.php";
								});
							}
							
								//\\alert(data);
						}
					});
				}else{
					// alert("Please Select File");
					
					$(".loading-page").hide();
					$(".form-data-saving").hide();
					$("#alert-status").text("Alert !!! ");
					$("#alert-message").text("Please Select File");
					$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
					$("#alert-container").hide();
					$("#alert-container").removeClass("alert-danger");
					// location.reload();
					// window.location.href = "pod_master.php";
					});
				}
			});	

			//End Update
			
			
		//button Save
		$("#search").keypress(function (event) {
                if (event.keyCode == 13) {
                    event.preventDefault();
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
