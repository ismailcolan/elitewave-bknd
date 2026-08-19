<?php
require_once ('includes/connect.php');
require_once ('includes/function.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="UTF-8">
  <!-- <link rel="mask-icon" type="" href="https://static.codepen.io/assets/favicon/logo-pin-f2d2b6d2c61838f7e76325261b7195c27224080bc099486ddd6dccb469b8e8e6.svg" color="#111" /> -->
  <title>Registration - Gracious Express</title>
  <!-- <link href="https://netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet"> -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

  <link href="assets/img/GE_Small_Logo.png" type="image/x-icon" rel="shortcut icon">

  <style>
    /*LOGIN*/
    @import url(https://fonts.googleapis.com/css?family=Lato);

    #log_reg {
      background-image: url(https://graciousexpress.com/assets/media/main-slider/1.jpg);
      background-position: center;
      background-size: cover;
      height: 100vh;
    }

    .login-page {
      /* width: 720px; */
      padding: 5% 0 0;
      margin: auto;
    }

    .form {
      position: relative;
      z-index: 1;
      opacity: 0.91;
      background: #FFFFFF;
      /* max-width: 720px; */
      margin: 0 auto 100px;
      padding: 45px;
      text-align: center;
      box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
    }

    .form input,
    select,
    textarea {
        font-family:'Inter', sans-serif !important;
      outline: 0;
      background: #dcdcdc;
      /* width: 100%; */
      border: 0;
      margin: 0 0 15px;
      padding: 15px;
      box-sizing: border-box;
      font-size: 14px;
    }

    .title {
      margin-bottom: 15px;
      color: #68c39f;
    }

    .form button {
      font-family: "Roboto", sans-serif;
      text-transform: uppercase;
      outline: 0;
      background: #2a479d;
      /* width: 100%; */
      border: 0;
      padding: 15px;
      color: #FFFFFF;
      font-size: 14px;
      -webkit-transition: all 0.3 ease;
      transition: all 0.3 ease;
      cursor: pointer;
    }

    .form button:hover,
    .form button:active,
    .form button:focus {
      background: #68c39f;
    }

    body {
      background: #f3f3f3;
      font-family: "Roboto", sans-serif;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    .input_valid {
      border: 1px solid red !important;
    }
  </style>

</head>

<body translate="no">

  <div id="log_reg">
    <div class="login-page col-6">
      <div class="form">
        <form class="login-form" id="user_register">
          <input type="hidden" name="form_name" value="user_registration">
          <div class="title">
            <img src="https://graciousexpress.com/assets/img/logo_old.png" width="250px" />
          </div>
          <div class="w-100 inputs">
            <div class="d-flex col-12 m-auto justify-content-between">
              <input type="text" class="col-6" placeholder="Name" name="reg_name" id="reg_name" required autocomplete="off" />
              <input type="email" class="col-6 ms-1" placeholder="Email address" name="reg_email" id="reg_email" required autocomplete="off" />
            </div>
            <div class="d-flex col-12 m-auto justify-content-between">
              <input type="text" class="col-6" placeholder="Mobile" name="reg_mobile" required minlength="10" maxlength="10" autocomplete="off" required onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" id="reg_mobile" />
              <input type="text" class="col-6 ms-1" placeholder="Company Name" name="reg_company" required autocomplete="off" id="reg_company" />
            </div>
            <div class="d-flex col-12 m-auto justify-content-between">
              <input type="text" class="col-6" placeholder="Contact Person" name="reg_contact_person" required autocomplete="off" id="reg_contact_person" />
              <input type="text" class="col-6 ms-1" placeholder="Pincode" name="reg_pincode" minlength="6" maxlength="6" autocomplete="off" required onkeypress="return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57" id="reg_pincode" />
            </div>
            <div class="d-flex col-12 m-auto justify-content-between">
              <input type="text" class="col-6" placeholder="PAN" name="reg_pan" id="reg_pan" required />
              <input type="text" class="col-6 ms-1" placeholder="GST" name="reg_gst" autocomplete="off" id="reg_gst" required />
            </div>
            <div class="d-flex col-12 m-auto justify-content-between">
              <input type="password" class="col-6" placeholder="Password" name="password" id="password" autocomplete="off" required />
              <input type="password" class="col-6 ms-1" placeholder="Confirm password" name="confirm_password" autocomplete="off"  id="confirm_password" required />
            </div>
            <div class="d-flex col-12 m-auto justify-content-between">
              <div class="col-6">
                <textarea rows="4" class="w-100" placeholder="Address" name="reg_address"  autocomplete="off" id="reg_address" required></textarea>
              </div>
              <div class="col-6">
                <select name="reg_state" class="col-12 ms-1" id="reg_state" required>
                  <option value="">--Select the state--</option>
                  <?php
                  $state_query = 'select * from state where status=0 order by state_name';
                  $state_result = mysqli_query($conn, $state_query);
                  while ($state_row = mysqli_fetch_array($state_result)) {
                    ?>
                    <option value="<?php echo $state_row['state_id']; ?>"><?php echo $state_row['state_name']; ?></option>
                  <?php
                  }
                  ?>
                </select>
                <select name="reg_city" class="col-12 ms-1" id="reg_city" required>
                  <option value="">--Select the city--</option>
                  <?php
                  $city_query = 'select * from city where status=0 order by city_name';
                  $city_result = mysqli_query($conn, $city_query);
                  while ($city_row = mysqli_fetch_array($city_result)) {
                    ?>
                    <option value="<?php echo $city_row['city_id']; ?>"><?php echo $city_row['city_name']; ?></option>
                  <?php
                  }
                  ?>
                </select>
              </div>
            </div>
          </div>
          <button type="submit" class="" name="btn-login" id="save">Register</button>
        </form>
      </div>
    </div>
  </div>

  <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js'></script>
  <script src="web/javascripts/jquery.validate.js" type="text/javascript"></script>
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

  <script>
    $(document).ready(function() {
      $(document).on('click', '#save', function(e) {
        let reg_name = $("#reg_name").val();
        let reg_email = $("#reg_email").val();
        let reg_mobile = $("#reg_mobile").val();
        let reg_company = $("#reg_company").val();
        let reg_address = $("#reg_address").val();
        let reg_contact_person = $("#reg_contact_person").val();
        let reg_pincode = $("#reg_pincode").val();
        let reg_state = $("#reg_state").val();
        let reg_city = $("#reg_city").val();
        let reg_gst = $("#reg_gst").val();
        let reg_pan = $("#reg_pan").val();
        let password = $("#password").val();
        let confirm_password = $("#confirm_password").val();

        if (reg_name != '' && reg_email != '' && reg_mobile != '' && reg_company != '' && reg_address != '' && reg_contact_person != '' && reg_pincode != '' && reg_state != '' && reg_city != '' && reg_gst != '' && reg_pan != '' && password !='' && confirm_password !='' && password === confirm_password && password.length >= 8) {
          e.preventDefault();
          $("#reg_name").removeClass('input_valid');
          $("#reg_email").removeClass('input_valid');
          $("#reg_mobile").removeClass('input_valid');
          $("#reg_company").removeClass('input_valid');
          $("#reg_address").removeClass('input_valid');
          $("#reg_contact_person").removeClass('input_valid');
          $("#reg_pincode").removeClass('input_valid');
          $("#reg_state").removeClass('input_valid');
          $("#reg_city").removeClass('input_valid');
          $("#reg_gst").removeClass('input_valid');
          $("#reg_pan").removeClass('input_valid');
          $("#password").removeClass('input_valid');
          $("#confirm_password").removeClass('input_valid');
          swal({
            // title: "Great!",
            text: "Do you want to continue?",
            icon: "info",
            buttons: {
              cancel: "Cancel",
              confirm: "Register"
            },
          }).then(function(isConfirm) {
            if (isConfirm) {
              // location.reload();
              let data = $("#user_register").serialize();
              $.ajax({
                url: 'save_details.php',
                type: 'post',
                data: data,
                success: function(response) {
                  console.log(response)
                  if (response == 1) {
                    swal({
                      title: "Success!",
                      text: "Our gracious team will reach you out.",
                      icon: "success",
                      buttons: "OK",
                    }).then(function(isConfirm) {
                      if (isConfirm) {
                        location.reload();
                      }
                    });
                  } else if (response == 2) {
                    swal({
                      title: "This Email is already exist!",
                      text: "Please try again with another email.",
                      icon: "error",
                      buttons: "OK",
                    });
                    $("#reg_email").addClass('input_valid');
                    $("#reg_email").focus();
                  } else {
                    swal({
                      title: "Registration Failed!",
                      text: "Please try again.",
                      icon: "error",
                      buttons: "OK",
                    });
                  }
                },
                error: function(error) {
                  alert(error.responseText);
                }
              });
            }
          });
        } else {
          if (reg_name == '') {
            $("#reg_name").addClass('input_valid');
            $("#reg_name").focus();
          } else if (reg_email == '') {
            $("#reg_email").addClass('input_valid');
            $("#reg_email").focus();
          } else if (reg_mobile == '') {
            $("#reg_mobile").addClass('input_valid');
            $("#reg_mobile").focus();
          } else if (reg_company == '') {
            $("#reg_company").addClass('input_valid');
            $("#reg_company").focus();
          } else if (reg_contact_person == '') {
            $("#reg_contact_person").addClass('input_valid');
            $("#reg_contact_person").focus();
          } else if (reg_pincode == '') {
            $("#reg_pincode").addClass('input_valid');
            $("#reg_pincode").focus();
          } else if (reg_pan == '') {
            $("#reg_pan").addClass('input_valid');
            $("#reg_pan").focus();
          } else if (reg_gst == '') {
            $("#reg_gst").addClass('input_valid');
            $("#reg_gst").focus();
          } else if(password == '') {
            $("#password").addClass('input_valid');
            $("#password").focus();
          } else if(confirm_password == '') {
            $("#confirm_password").addClass('input_valid');
            $("#confirm_password").focus();
          } else if(password != confirm_password) {
            $("#password").focus();
            swal({
              title: "Password Mismatch",
              text: "Password and Confirm Password is not matching. Password and confirm password should match.",
              icon: "error",
              buttons: "OK",
            });
          } else if(password.length < 8) {
            $("#password").focus();
            swal({
              title: "",
              text: "Password length should atleast 8 characters",
              icon: "error",
              buttons: "OK",
            });
          } else if (reg_address == '') {
            $("#reg_address").addClass('input_valid');
            $("#reg_address").focus();
          } else if (reg_state == '') {
            $("#reg_state").addClass('input_valid');
            $("#reg_state").focus();
          } else if (reg_city == '') {
            $("#reg_city").addClass('input_valid');
            $("#reg_city").focus();
          }
          e.preventDefault();
        }
      })
    });
  </script>

</body>

</html>