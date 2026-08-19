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

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<script src="https://malsup.github.io/jquery.form.js"></script>
	<style>
		.gra-btn{
			padding: 8px 12px;
			
			background: mediumseagreen;
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
	  
			<div class="row">
			<div class="col-md-12">
			<div class="widget-container fluid-height clearfix">
			<div class="heading"> <i class="fa fa-plus"></i>Upload Proof of Delivery  <span class="align-right"><i class="fa fa-plus"></i><a href="pod_list.php">View List</a></span></div>
			
						  <div class="widget-content padded">
					<form class="form-horizontal" id="transaction_form" multipart="enctype">
						
					<div id="response" class="alert alert-danger" style="display:none;">
							<div class="message" style="text-align:center"></div>
						</div>
						
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
								<small>Allowed Types : JPEG, JPG, PNG Format</small><br>
                                <small>File Name must be GRN Number: XXXX00001 <span style="color:red">* * </span></small>
								</div>
                                   

								</div>
								<div class="col-md-6">						
								 <button class="btn btn-primary gra-btn" type="submit" style="margin-top: 21px;"  id="upload"><i class="fa fa-plus" aria-hidden="true"></i> Upload</button>
								</div>
							</div>
							
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

			
			<script type="text/javascript">
	$(document).ready(function(){
		
			$('body').bind('change', function () {
				
				var numFiles = $("input",this)[0].files.length;
			
					if (/^\s*$/.test(numFiles)) {
						
							$(".file-upload").removeClass('active');
						$("#noFile").text("No file chosen..."); 
					}
					else {
						
						$(".file-upload").addClass('active');
						$("#noFile").text(numFiles +" files "); 
					}
				});

			$('#upload').click(function(e){ 
				e.preventDefault();
				$(".loading-page").show();
				var formdata = new FormData(document.getElementById("transaction_form"));
				// var files = $('#pod_file')[0].files;
				var files = $("#pod_file").prop('files')[0];
				
				var form_name = 'pod_form';
				if($("#pod_file").prop('files')[0]){
					formdata.append('file',files);
					formdata.append('form_name',form_name);
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
								location.reload();
								window.location.href = "pod_master.php";
							 	});
							}else{
								
								$(".loading-page").hide();
								$(".form-data-saving").hide();
								$("#alert-status").text("Alert !!! ");
								$("#alert-message").text("Fail Upload Failure");
								$("#alert-container").addClass("alert-danger").slideDown(800).fadeTo(1000, 500).slideUp(800, function(){
								$("#alert-container").hide();
								$("#alert-container").removeClass("alert-danger");
								//location.reload();
								//window.location.href = "pod_master.php";
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

		
		/*$(document).on('keyup', '#grn_no', function(event){					
						 event.preventDefault();
							if (event.keyCode === 13) {
								$("#search").trigger("click");
							}
					});*/
			$(window).load(function() {
				$(".loading-page").hide();
			});
			});
			</script>
			
			<script>

			   function isNumber(evt, element) {
					var charCode = (evt.which) ? evt.which : event.keyCode

					if ((charCode != 45 || $(element).val().indexOf('-') != -1) &&      // “-” CHECK MINUS, AND ONLY ONE.
						(charCode != 46 || $(element).val().indexOf('.') != -1) &&      // “.” CHECK DOT, AND ONLY ONE.
						(charCode < 48 || charCode > 57))
						return false;
						return true;
					} 
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

	<div class="modal fade popup_close" id="eway_popup"  style="display:none">
				<div class="modal-dialog">
				<div class="modal-content">
				  <div class="modal-header">
					<button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
					<h4 class="modal-title" style="color:#fff">
					  Add Attachments 
					</h4>
				  </div>
					
					<div class="modal-body" id="attachment_body">
						 
					</div>
				</div>
				</div>
					</div>
					
			
	  </body>
	</html>