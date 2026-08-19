<?php require_once("include/connect.php");
if($_REQUEST['login'] != ''){
	$user_id = $_REQUEST['login'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Password Change</title>
	<?php include("include/title.php"); ?>
     <?php include("include/css_js_forgetpassword.php"); ?>
     <link href="favicon.png" type="image/x-icon" rel="shortcut icon">
     <link href="assets/css/master.css" rel="stylesheet">
    
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
     <!-- <script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script> -->
     <script src="assets/js/jquery.validate.min.js"></script>
     <script src="assets/js/modernizr.custom.js"></script>
	 <style>
	body{
		background-image:url('http://localhost/GraciousExpress/web/images/back_blue.jpg');
	}
		 .main_div{
			 display:flex;
			 justify-content:center;
			 align-items:center;
			 margin:auto;
			 position: absolute;
  			 top: 0; left: 0; bottom: 0; right: 0;
			
			 
		 }
		 .second_div{
			width: 34.5rem;
			padding:10px;
			border:1px solid blue;
			background-color:white;
			color:#1762E5;  
			box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
			border-radius:20px;

		 }
		 .img_src img {
			height: auto;
			padding: 10px;
			width: 33.5rem;
		}
		.form-control {
		display: block;
		width: 100%;
		height: 34px;
		padding: 6px 12px;
		font-size: 14px;
		line-height: 1.42857143;
		color: #555;
		background-color: #fff;
		background-image: none;
		border: 1px solid #739eed;
		border-radius: 4px;
		}


 </style>
</head>
<body>
	
		<div class="main_div">
			
			<div class="second_div">
			<div id="response" class="alert " role="alert" style="display:none;">
			<div class="message" style="text-align:center"></div>
			</div>
			<div class="img_src">
		 	<img src="http://staging.graciousexpress.com/web/images/gracious.png" alt="">	
			</div>	
			<form action="" class="form-group" method="post" id="change_user_pass">
			<input type="hidden" name="user_id" id="user_id" value="<?php echo $user_id;?>">
			<div class="form-input">
			<label for="">New Password:</label>
			 <input type="password" name="new_pass" id="new_pass" value="" class="form-control" />
			 <span class="pass_check"></span>
			</div>
		 	
			 <label for="">Confirm Password:</label>
			 <input type="password" name="confirm_pass" id="confirm_pass" value="" class="form-control" />
			 <span class="pass_check"></span>
			 <input type="submit" name="submit" value="Update" id="submit" class="btn btn-primary"/>
		 
			</form>
			</div>
			
		
	</div>
</body>
</html>
<script>
	$(document).ready(function(){
			$('#submit').on('click',function(e){
				e.preventDefault();
				// alert('testt');
				
				var form_name = 'change_user_pass';
				var user_id = $('#user_id').val();
				var new_pass = $('#new_pass').val();
				var confirm_pass = $('#confirm_pass').val();
				if(new_pass && confirm_pass !=''){
				if(new_pass == confirm_pass){
					$('.pass_check').html('<p style="color:green;">Password Match</p>');
					//alert('Trest');
					
					$.ajax({
						url:'../web/save_details.php',
						type:'post',
						data:{form_name:form_name,user_id:user_id,new_pass:new_pass,confirm_pass:confirm_pass},
						success:function(data){
							if(data != 0){
								$("#response").removeClass("alert-danger");
								$("#response").addClass("alert-success");
								$('#response .message').html('Successfully Password Changed.');
								$('#response').fadeIn('slow').delay(200000).fadeOut('slow');
                            	setTimeout(function(){
										window.location.href = "../user/login.php";

									}, 2000);
							}else{
								$("#response").removeClass("alert-success");
								$("#response").addClass("alert-danger");
								$('#response .message').html('Password Update Failure!');
								$('#response').fadeIn('slow').delay(200000).fadeOut('slow');
							}
						}
					});
				}else{
					$('.pass_check').html('<p style="color:red;">Password does not Match</p>');

				}
			
		 }
				
			});
	});
</script>