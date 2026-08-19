<?php
require_once("include/connect.php");
//$con = mysqli_connect('localhost','root','','bookconsignment');
if (isset($_COOKIE['persistID'])) {

    $user_id =   $_COOKIE['persistID'];

    //    $query = "select * from users where user_id='$user_id' and status = 1 ";
    // 	$result = mysqli_query($conn,$query) or die(mysqli_error());

    // 	if(mysqli_num_rows($result) == 1)
    // 	{ 
    // 		$row = mysqli_fetch_array($result);
    // 		$result = mysqli_query($conn,$query) or die(mysqli_error());
    // 		$_SESSION['LAST_ACTIVITY'] = time();
    // 		 $_SESSION['user_id'] = $row['user_id'];
    // 		echo '<script> location.href="user-dashboard.php"; </script>';
    // 	}

}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <!-- Bobules -->
    <link href="https://fonts.googleapis.com/css?family=Raleway:400,400i,500,500i,600,600i,700,700i,800" rel="stylesheet">
    <link rel="stylesheet" href="loginme/movingbubbles.css" type="text/css" />
    <link rel="stylesheet" href="loginme/login.css" type="text/css" />
    <link rel="shortcut icon" href="../web/images/favi.png">
    <title>Gracious Express</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">

    <script>
        window.console = window.console || function(t) {};
        if (document.location.search.match(/type=embed/gi)) {
            window.parent.postMessage("resize", "*");
        }
    </script>
    <style>
        input#email {
            font-size: 15px !important;
        }

        .loading-page,
        .form-data-saving {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0px;
            left: 0px;
            background: rgba(255, 255, 255, 0.92);
            padding: 20% 45%;
            z-index: 1610;
        }

        .form-data-saving {
            background: rgba(0, 0, 0, 0.23) !important;
            z-index: 1610 !important;
        }

        .form-data-saving img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #FFF;
        }
    </style>
</head>

<body translate="no">
    <!-- <div class="topnav">
        <a href="#"><img width="250px" src="images/gracious_express.png"></a>
        <div class="topnav-right">

        </div>
    </div> -->


    <div id="fundo">
        <div id="login">
            <form class="login100-form validate-form" method="post" id="login_form">
                <input type="hidden" name="form_name" id="from_name" value="login">

                <div id="logo"></div>
                <h1>Welcome to Gracious Express</h1>
                <br />

                <div class="form-data-saving" style="display:none;"><img src="images/loading.png" /></div>
                <div class="input" id="mail">
                    <input type="email" name="email" id="email" placeholder="Email " autocomplete="off" autofocus="" />
                    <div class="btn" id="next" type="button"></div>
                </div>
                <div class="input" id="pass" style="display: none;">
                    <input type="password" name="password" id="password" placeholder="Password" />
                    <div class="btn" id="save" type="button"></div>
                </div>
                <div id="response" class="alert alert-danger">
                    <div class="message" style="text-align:center"></div>
                </div>

                <div class="si-remember-password">
                    <input type="checkbox" name="remember" id="remember-me" class="form-choice form-choice-checkbox" value="1">
                    <span id="remember-me-label" class="form-label" for="remember-me">
                        Keep me signed in
                    </span>
                </div>
                <div class="separator "></div>

                <div id="footer">
                    <label>
                        <span><a href="../web/forgot_password.php">Forgot password?</a></span>

                    </label>
                </div>
            </form>
        </div>
    </div>


    <script src="loginme/movingbubbles.js" type="text/javascript"></script>
    <script src="javascripts/jquery-1.10.2.min.js" type="text/javascript"></script>
    <script src="javascripts/jquery.validate.js" type="text/javascript"></script>
    <script type="text/javascript">
        localStorage.clear();
        jQuery(function($) {

            $(document).on('click', '#next', function(e) {
                e.preventDefault();

                var email = $("#email").val();
                var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                if (!filter.test(email)) {
                    $("#mail").addClass("input-error");
                    $('.message').addClass("error").html("Please Check Your Email ID");
                    $('#mail').focus();
                    return false;
                } else {
                    $.ajax({
                        url: '../fetch-details.php',
                        type: 'post',
                        data: {
                            cmd: "get_user_email",
                            email: email
                        },
                        success: function(result) {
                            console.log(result);
                            if (result == 1) {
                                $("#mail").hide(100);
                                $("#pass").show().fadeIn(1000);
                                $("#pass").focus();
                                $("#mail").removeClass("input-error");
                                $(".message").removeClass("error").html('');
                            } else {
                                $('#mail').addClass("input-error");
                                $('.message').addClass("error").html('User Not Registered');
                            }

                        }
                    });
                }
            });

            $(document).on('keyup', '#email', function(e) {
                e.preventDefault();
                if (e.keyCode === 13) {
                    $("#next").trigger("click");
                }
            });
            $(document).on('keyup', '#password', function(e) {
                e.preventDefault();
                if (e.keyCode === 13) {
                    $("#save").trigger("click");
                }
            });
            $(document).on('click submit', '#save', function(event) {
                if ($("#login_form").valid() == true) {
                    $('.form-data-saving').show();
                    var data = $("#login_form").serialize();

                    $.post('../save_details.php', data, function(data) {
                        console.log(data);
                        if (data == 1) {
                            $("#pass").removeClass('input-error');
                            $('.message').html('<img src =images/btn-ajax-loader.gif" height="10px" width="10px"/> &nbsp; Loading Please Wait ...');
                            $('.form-data-saving').hide();
                            setTimeout(function() {
                                // window.location.href = "http://localhost/graciousexpress/user/verify_otp.php";
                                window.location.href = "https://graciousexpress.colanapps.in/user/user-dashboard.php";                            
                            }, 2000);
                        } else if (data == 2) {
                            $('.form-data-saving').hide();
                            $('.message').removeClass('error').addClass('success');
                            $('.message').html("<strong>Your Password has been already changed using this url</strong>");
                            $('.message').fadeIn('slow').delay(200000).fadeOut('slow');
                            
                        } else {
                            $('.form-data-saving').hide();
                            $("#pass").addClass("input-error");
                            $(".message").removeClass("success").addClass("error");
                            $(".message").html("Invalid Password / Your Account is Inactive !!!");
                            $('.message').fadeIn('slow').delay(200000).fadeOut('slow');
                        }
                    });
                }
            });

        });
    </script>
    
</body>

</html>