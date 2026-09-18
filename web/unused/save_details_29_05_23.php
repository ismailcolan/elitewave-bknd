<?php
require_once("include/connect.php");
//require_once("save_admin.php");
include("include/function.php");
require_once("appMail.php");
require_once '../Twillio/vendor/autoload.php';
require_once('../Twillio/constant.php');
$form_name = $_POST['form_name'];
$created_at = $updated_at = date('d-m-Y');
$updated_by = $created_by = $_SESSION['user_id'];
date_default_timezone_set('Asia/Kolkata');
$c_date = date('d-m-Y');
$date = new DateTime();
$c_time = $date->format('H:i:s A');
$c_date_string = strtotime($c_date);
$company_id = $_SESSION['company_id'];

use Twilio\Rest\Client;

if ($form_name == "login") {

    $username = $_POST['email'];
    $password = $_POST['password'];
    if (isset($_POST['remember']))
        $remember = 1;
    else
        $remember = 0;
    $id = $_POST['login'];
    $select_query = "select * from users where md5(user_id) = '" . $id . "'";
    $select_result = mysqli_query($conn, $select_query);
    $select_row = mysqli_fetch_array($select_result);
    if ($id != '') {

        if ($select_row['password_status'] == 1) {
            $update_query = "update users set password_status = 0,password='" . $password . "' where md5(user_id) = '" . $id . "'";
            $update_result = mysqli_query($conn, $update_query);
        } else {
            echo 2;
        }
    }

    $query = "select * from users where email='$username' and password='$password' and status=0";
    $result = mysqli_query($conn, $query) or die(mysqli_error());

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_array($result);
        $result = mysqli_query($conn, $query) or die(mysqli_error());
        $_SESSION['LAST_ACTIVITY'] = time();
        $_SESSION['role'] = $row['role'];
        $_SESSION['user_id'] = $row['user_id'];
        $uid = $row['user_id'];
        $_SESSION['company_id'] = $row['company_name'];
        if ($remember == 1)
            setcookie('persistID', $uid, time() + (30 * 24 * 60 * 60), '/');

        echo 1;
    } else {
        if ($id == '') {
            echo 0;
        }
    }
}
if ($form_name == "recover") {
    $mail_id = $_POST['mail'];

    $query = "select * from tv_admins where email='$mail_id'";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));
    $num_row = mysqli_num_rows($result);
    $row = mysqli_fetch_array($result);
    if ($num_row > 0) {
        $password = $row['password'];
        $to = $row['email'];
        $subject = "Forget Password";
        $from = 'IMPLEMENTER';
        $body = "Hi " . $row['contact_person'] . ",<br><h3 style='color:#FF0000'>Your Login Password is :</h3>" . $password . "";

        sendAppMail('User', $to, $subject, $body);
        echo 0;
    } else {
        echo 1;
    }
}
if ($form_name == "forgot_password") {
    $mail_id = $_POST['email'];

    $query = "select * from users where email='$mail_id'";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));
    $num_row = mysqli_num_rows($result);
    $row = mysqli_fetch_array($result);
    if ($num_row > 0) {
        $password = $row['password'];
        $to = $mail_id;
        $subject = "Forget Password";
        $from = 'Elite Wave 360';
        $body = "Hi " . $row['contact_person'] . ",
			         Click this link to login with new password: https://staging.graciousexpress.com/user/password_change.php?login=" . md5($row['user_id']) . "";

        $mail = sendAppMail('User', $to, $subject, $body);
        if ($mail) {
            $query = "update users set password_status=1 where user_id ='" . $row['user_id'] . "'";
            $result = mysqli_query($conn, $query);
            echo 1;
        } else {
            echo 2;
        }
    } else {
        echo 0;
    }
}
if ($form_name == "password_change") {
    $edit_id            =   mysqli_real_escape_string($conn, $_POST['login_id']);
    $confirm_password   =  mysqli_real_escape_string($conn, $_POST['new_password']);
    $query = "update users set  password='$confirm_password' where user_id='$edit_id'";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));


    if ($result)
        echo 1;
}

if ($form_name == 'verify_login_otp') {
    $user_id = $_POST['user_id'];
    $otp = $_POST['otp'];
    $time = $_POST['time'];
    if ($time == "1") {
        $verify_otp = "select * from users where otp = '$otp' and user_id = '$user_id' ";
        $res_otp = mysqli_query($conn, $verify_otp);
        $count = mysqli_num_rows($res_otp);
        if ($count > 0) {
            echo 1;
            $remove_otp = "update users SET `otp`='' where `user_id` = '$user_id'";
            $update_otp = mysqli_query($conn, $remove_otp);
            unset($_SESSION['otp']);
        } else {
            echo 0;
        }
    } else {
        echo "OTP Remove";
        $remove_otp1 = "update users SET `otp`='' where `user_id` = '$user_id'";
        $update_otp1 = mysqli_query($conn, $remove_otp1);
        if ($update_otp1) {
            echo 1;
            unset($_SESSION['otp']);
        }
    }
}

if ($form_name == "add_branch") {
    $branch_code = $_POST['branch_code'];
    $branch_name = $_POST['branch_name'];
    $contact_person = $_POST['contact_person'];
    $contact_no = $_POST['contact_no'];
    $address1 = $_POST['address1'];
    $address2 = $_POST['address2'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $email = $_POST['email'];

    $query = "insert into branch(branch_code
	,branch_name,contact_person,contact_no,address1,address2,city,state,pincode,email,created_at,created_by,status)values
	('" . $branch_code . "','" . $branch_name . "','" . $contact_person . "','" . $contact_no . "','" . $address1 . "','" . $address2 . "','" . $city . "','" . $state . "','" . $pincode . "','" . $email . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "edit_branch") {
    $edit_id = $_POST['edit_id'];
    $branch_code = $_POST['branch_code'];
    $branch_name = $_POST['branch_name'];
    $contact_person = $_POST['contact_person'];
    $contact_no = $_POST['contact_no'];
    $address1 = $_POST['address1'];
    $address2 = $_POST['address2'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $email = $_POST['email'];
    $query = "update branch set branch_code='" . $branch_code . "',branch_name='" . $branch_name . "',contact_person='" . $contact_person . "',contact_no='" . $contact_no . "',address1='" . $address1 . "',address2='" . $address2 . "',city='" . $city . "',state='" . $state . "',pincode='" . $pincode . "',email='" . $email . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where branch_id='" . $edit_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_branch") {
    $tbl_id = $_POST['tbl_id'];
    /*$query = "select * from transaction where branch_id='".$tbl_id."'";
	$result = mysqli_query($conn,$query);
	$count  = mysqli_num_rows($result);
	if($city_count >0)
	{
		echo "404-del";
	}
	else{*/
    $query = "delete  from branch where branch_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
    //}
}
if ($form_name == "inacv_branch") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  branch set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where branch_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "add_state") {
    $state_name = $_POST['state_name'];
    $query = "insert into state(state_name,created_at,created_by,status)values('" . $state_name . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_state") {
    $id = $_POST['tbl_id'];

    $city_query = "select * from city where state='" . $id . "'";
    $city_result = mysqli_query($conn, $city_query);
    $count += mysqli_num_rows($city_result);

    $branch_query = "select * from branch where state='" . $id . "'";
    $branch_result = mysqli_query($conn, $branch_query);
    $count += mysqli_num_rows($branch_result);
    if ($count == 0) {
        $query = "delete from state where state_id='" . $id . "'";
        $result = mysqli_query($conn, $query);
        if ($result)
            echo 1;
        else
            echo 0;
    } else {
        echo "404-del";
    }
}
if ($form_name == "edit_state") {
    $state_name = $_POST['state_name'];
    $edit_id = $_POST['edit_id'];
    $query = "update  state set state_name='" . $state_name . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where state_id='" . $edit_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "inacv_state") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  state set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where state_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "add_city") {
    $select_query = mysqli_fetch_array(mysqli_query($conn, "select max(city_code_id) as code_id from city"));
    $id = $select_query['code_id'] + 1;
    $city_code = "GEC" . sprintf("%03d", $id);
    $city_name = $_POST['city_name'];
    $state_name = $_POST['state_name'];
    $city = $_POST['city'];
    $automation = $_POST['automation'];

    $query = "insert into city(city_code,city_code_id
	,city_name,state,automation,via_city,created_at,created_by,status)values
	('" . $city_code . "','" . $id . "','" . $city_name . "','" . $state_name . "','" . $automation . "','" . $city . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);

    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "edit_city") {
    $edit_id = $_POST['edit_id'];
    //$city_code =  $_POST['city_code'];
    $city_name = $_POST['city_name'];
    $state_name = $_POST['state_name'];
    $city = $_POST['city'];
    $automation = $_POST['automation'];
    $query = "update city set city_name='" . $city_name . "',state='" . $state_name . "',automation='" . $automation . "',via_city='" . $city . "',updated_at='" . $updated_at . "',updated_by='" . $udpated_by . "' where city_id='" . $edit_id . "'";

    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_city") {
    $tbl_id = $_POST['tbl_id'];

    $branch_query = "select * from branch where city='" . $id . "'";
    $branch_result = mysqli_query($conn, $branch_query);
    $count += mysqli_num_rows($branch_result);
    if ($count == 0) {
        $query = "delete  from city where city_id='" . $tbl_id . "'";
        $result = mysqli_query($conn, $query);
        if ($result)
            echo 1;
        else
            echo 0;
    } else {
        echo "404-del";
    }
}
if ($form_name == "inacv_city") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  city set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where city_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}


if ($form_name == "add_hub") {

    $hub_name = $_POST['hub_name'];
    $contact_name = $_POST['contact_name'];
    $contact_no = $_POST['contact_no'];
    $route = $_POST['route'];
    $main_hub = $_POST['main_hub'];
    $cities = implode(",", $_POST['cities']);


    $hubcode_q = mysqli_query($conn, "select max(hub_no) as hub_no from hub ");
    $hub_r = mysqli_fetch_array($hubcode_q);
    $hub_no =  $hub_r['hub_no'] + 1;
    $hub_code =  $hub_no;

    $query = "insert into hub (hub_code,hub_no,name,contact_person,contact_no,route,main_hubs,covered_cities,created_at,created_by,status) values
	('$hub_code','$hub_no','$hub_name','$contact_name','$contact_no','$route','$main_hub','$cities','$created_at','$created_by','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "edit_hub") {
    $edit_id = $_POST['edit_id'];
    $hub_name = $_POST['hub_name'];
    $contact_name = $_POST['contact_name'];
    $contact_no = $_POST['contact_no'];
    $route = $_POST['route'];
    $main_hub = $_POST['main_hub'];
    $cities = implode(",", $_POST['cities']);


    $query = "update hub set name='" . $hub_name . "',contact_person='" . $contact_name . "',contact_no='" . $contact_no . "',main_hubs='" . $main_hub . "',route='" . $route . "',covered_cities='" . $cities . "',updated_at='" . $updated_at . "',updated_by='" . $udpated_by . "' where hub_id='" . $edit_id . "'";

    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_hub") {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete  from hub where hub_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
}
if ($form_name == "inacv_hub") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update hub set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where hub_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

//Role

if ($form_name == "add_role") {
    $role_name = $_POST['role_name'];
    echo $role = strtoupper(explode(" ", $role_name));
    echo $query = "insert into role (role_name,role,created_at,created_by,status) values
	('$role_name','$role','$created_at','$created_by','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "edit_role") {
    $edit_id = $_POST['edit_id'];
    $role_name = $_POST['role_name'];


    $query = "update hub set role_name='" . $role_name . "',updated_at='" . $updated_at . "',updated_by='" . $udpated_by . "' where role_id='" . $edit_id . "'";

    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_role") {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete  from role where role_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
}
if ($form_name == "inacv_role") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update role set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where role_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}


//Train

if ($form_name == "add_train") {

    $train_name = $_POST['train_name'];
    $train_number = $_POST['train_number'];
    $loading_point1 = $_POST['loading_point1'];
    $loading_point2 = $_POST['loading_point2'];
    $loading_point3 = $_POST['loading_point3'];
    $loading_point4 = $_POST['loading_point4'];
    $journey_hours = $_POST['journey_hours'];


    $query = "insert into train (train_name,train_number,loading_point1,loading_point2,loading_point3,loading_point4,journey_hours,created_at,created_by,status) values
	('$train_name','$train_number','$loading_point1','$loading_point2','$loading_point3','$loading_point4','$journey_hours','$created_at','$created_by','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "edit_train") {
    $edit_id = $_POST['edit_id'];
    $train_name = $_POST['train_name'];
    $train_number = $_POST['train_number'];
    $loading_point1 = $_POST['loading_point1'];
    $loading_point2 = $_POST['loading_point2'];
    $loading_point3 = $_POST['loading_point3'];
    $loading_point4 = $_POST['loading_point4'];
    $journey_hours = $_POST['journey_hours'];


    $query = "update train set train_name='" . $train_name . "',train_number='" . $train_number . "',loading_point1='" . $loading_point1 . "',loading_point2='" . $loading_point2 . "',loading_point3='" . $loading_point3 . "',loading_point4='" . $loading_point4 . "',journey_hours='" . $journey_hours . "',updated_at='" . $updated_at . "',updated_by='" . $udpated_by . "' where train_id='" . $edit_id . "'";

    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_train") {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete  from train where train_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
}
if ($form_name == "inacv_train") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update train set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where train_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
//Flight

if ($form_name == "add_flight") {

    $flight_name = $_POST['flight_name'];
    $flight_number = $_POST['flight_number'];
    $loading_point1 = $_POST['loading_point1'];
    $loading_point2 = $_POST['loading_point2'];
    $loading_point3 = $_POST['loading_point3'];
    $loading_point4 = $_POST['loading_point4'];
    $journey_hours = $_POST['journey_hours'];


    $query = "insert into flight (flight_name,flight_number,loading_point1,loading_point2,loading_point3,loading_point4,journey_hours,created_at,created_by,status) values
	('$flight_name','$flight_number','$loading_point1','$loading_point2','$loading_point3','$loading_point4','$journey_hours','$created_at','$created_by','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "edit_flight") {
    $edit_id = $_POST['edit_id'];
    $flight_name = $_POST['flight_name'];
    $flight_number = $_POST['flight_number'];
    $loading_point1 = $_POST['loading_point1'];
    $loading_point2 = $_POST['loading_point2'];
    $loading_point3 = $_POST['loading_point3'];
    $loading_point4 = $_POST['loading_point4'];
    $journey_hours = $_POST['journey_hours'];


    $query = "update flight set flight_name='" . $flight_name . "',flight_number='" . $flight_number . "',loading_point1='" . $loading_point1 . "',loading_point2='" . $loading_point2 . "',loading_point3='" . $loading_point3 . "',loading_point4='" . $loading_point4 . "',journey_hours='" . $journey_hours . "',updated_at='" . $updated_at . "',updated_by='" . $udpated_by . "' where flight_id='" . $edit_id . "'";

    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_flight") {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete  from flight where flight_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
}
if ($form_name == "inacv_flight") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update flight set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where flight_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "add_mode_of_transportation") {
    $mode_type =  $_POST['mode_type'];
    $delivery = $_POST['delivery'];

    $query = "insert into mode_of_transportation(mode_type
	,max_hrs_delivery,created_at,created_by,status)values
	('" . $mode_type . "','" . $delivery . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "edit_mode") {
    $edit_id = $_POST['edit_id'];
    $mode_type =  $_POST['mode_type'];
    $delivery = $_POST['delivery'];

    $query = "update mode_of_transportation set mode_type='" . $mode_type . "'
	,max_hrs_delivery='" . $delivery . "',updated_at='" . $updated_at . "',updated_by='" . $udpated_by . "' where mode_id='" . $edit_id . "'";

    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_mode") {
    $tbl_id = $_POST['tbl_id'];

    $branch_query = "select * from transaction where mode_of_transportation='" . $id . "'";
    $branch_result = mysqli_query($conn, $branch_query);
    $count += mysqli_num_rows($branch_result);
    if ($count == 0) {
        $query = "delete  from mode_of_transportation where mode_id='" . $tbl_id . "'";
        $result = mysqli_query($conn, $query);
        if ($result)
            echo 1;
        else
            echo 0;
    } else {
        echo "404-del";
    }
}
if ($form_name == "inacv_mode") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  mode_of_transportation set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where mode_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "add_client") {
    $company_name =  $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $address1 = $_POST['address1'];
    $address2 = $_POST['address2'];
    $state = $_POST['state'];
    $city = $_POST['city'];
    $billing_code = $_POST['billing_code'];
    $pincode = $_POST['pincode'];
    $email = $_POST['email'];
    $contact_no = $_POST['contact_no'];
    $gst_no = $_POST['gst_no'];
    $pan_no = $_POST['pan_no'];
    $multiple_branches = $_POST['multiple_branches'];
    $automation = $_POST['transit_automation'];
    if ($_POST['edit_id'] != '') {
        $query = "update client set client_company_name='" . $company_name . "',contact_person='" . $contact_person . "',address1='" . $address1 . "',address2='" . $address2 . "',state='" . $state . "',city='" . $city . "',pincode='" . $pincode . "',billing_code='" . $billing_code . "',email='" . $email . "',contact_no='" . $contact_no . "',gst_no='" . $gst_no . "',pan_no='" . $pan_no . "',multiple_branches='" . $multiple_branches . "',automation='" . $automation . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where md5(client_id)='" . $_POST['edit_id'] . "'";
        $result = mysqli_query($conn, $query);
    } else {
        if ($_SESSION['role'] == 'AD') {

            $query = "insert into client(client_company_name,contact_person,address1,address2,state,city,pincode,billing_code,email,contact_no,gst_no,pan_no,multiple_branches,automation,created_at,created_by,status,approve_status,invoice_frequency)values
			('" . $company_name . "','" . $contact_person . "','" . $address1 . "','" . $address2 . "','" . $state . "','" . $city . "','" . $pincode . "','" . $billing_code . "','" . $email . "','" . $contact_no . "','" . $gst_no . "','" . $pan_no . "','" . $multiple_branches . "','" . $automation . "','" . $created_at . "','" . $created_by . "','0','0','0')";
            $result = mysqli_query($conn, $query);
        } else {
            $query = "insert into client(client_company_name,contact_person,address1,address2,state,city,pincode,billing_code,email,contact_no,gst_no,pan_no,multiple_branches,automation,created_at,created_by,status,approve_status,invoice_frequency)values
			('" . $company_name . "','" . $contact_person . "','" . $address1 . "','" . $address2 . "','" . $state . "','" . $city . "','" . $pincode . "','" . $billing_code . "','" . $email . "','" . $contact_no . "','" . $gst_no . "','" . $pan_no . "','" . $multiple_branches . "','" . $automation . "','" . $created_at . "','" . $created_by . "','0','0','0')";
            $result = mysqli_query($conn, $query);
        }
    }
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "del_client") {
    $tbl_id = $_POST['tbl_id'];

    $branch_query = "select * from client where client_id='" . $id . "'";
    $branch_result = mysqli_query($conn, $branch_query);
    $count += mysqli_num_rows($branch_result);
    if ($count == 0) {
        $query = "delete  from client where client_id='" . $tbl_id . "'";
        $result = mysqli_query($conn, $query);
        if ($result)
            echo 1;
        else
            echo 0;
    } else {
        echo "404-del";
    }
}
if ($form_name == "inacv_client") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  client set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where client_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "restrict_inv_client") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  client set invoice_status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where client_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "add_user") {
    $edit_id = $_POST['edit_id'];
    $user_name =  $_POST['user_name'];
    $role = $_POST['role'];
    $company_type = $_POST['company_type'];
    $company_name = $_POST['company_name'];
    $branch_name = $_POST['branch'];
    $contact_no = $_POST['contact_no'];

    $user_email = $_POST['user_email'];
    $password =  $_POST['password'];

    if ($_POST['edit_id'] != '') {
        $query = "update users set company_name='" . $company_name . "',company_type='" . $company_type . "',branch_name='" . $branch_name . "',role='" . $role . "',contact_no='" . $contact_no . "',email='" . $user_email . "',password='" . $password . "',user_name='" . $user_name . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where md5(user_id)='" . $_POST['edit_id'] . "'";
        $result = mysqli_query($conn, $query);
    } else {
        $query = "insert into users(company_name,branch_name,company_type,role,contact_no,email,password,user_name,created_at,created_by,status)values
		('" . $company_name . "','" . $branch_name . "','" . $company_type . "','" . $role . "','" . $contact_no . "','" . $user_email . "','" . $password . "','" . $user_name . "','" . $created_at . "','" . $created_by . "','0')";
        $result = mysqli_query($conn, $query);
    }
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "del_user") {
    $tbl_id = $_POST['tbl_id'];

    $branch_query = "select * from transaction where mode_of_transportation='" . $id . "'";
    $branch_result = mysqli_query($conn, $branch_query);
    $count += mysqli_num_rows($branch_result);
    if ($count == 0) {
        $query = "delete  from users where user_id='" . $tbl_id . "'";
        $result = mysqli_query($conn, $query);
        if ($result)
            echo 1;
        else
            echo 0;
    } else {
        echo "404-del";
    }
}
if ($form_name == "inacv_user") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  users set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where user_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "add_consignment") {
    $consignment =  $_POST['consignment'];
    $description = $_POST['description'];
    $query = "insert into consignment_mode(consignment_mode,description,created_at,created_by,status)values
		('" . $consignment . "','" . $description . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "edit_consignment") {
    $edit_id = $_POST['edit_id'];
    $consignment =  $_POST['consignment'];
    $description = $_POST['description'];
    $query = "update consignment_mode set consignment_mode='" . $consignment . "',description='" . $description . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where consignment_id='" . $edit_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_consignment") {
    $tbl_id = $_POST['tbl_id'];

    $branch_query = "select * from transaction where mode_of_transportation='" . $id . "'";
    $branch_result = mysqli_query($conn, $branch_query);
    $count += mysqli_num_rows($branch_result);
    if ($count == 0) {
        $query = "delete  from consignment_mode where consignment_id='" . $tbl_id . "'";
        $result = mysqli_query($conn, $query);
        if ($result)
            echo 1;
        else
            echo 0;
    } else {
        echo "404-del";
    }
}
if ($form_name == "inacv_consignment") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  consignment_mode set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where consignment_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "add_package") {
    $package_code =  $_POST['package_code'];
    $description = $_POST['description'];
    $query = "insert into package(package_code,description,created_at,created_by,status)values
		('" . $package_code . "','" . $description . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "edit_package") {
    $edit_id = $_POST['edit_id'];
    $package_code =  $_POST['package_code'];
    $description = $_POST['description'];
    $query = "update package set package_code='" . $package_code . "',description='" . $description . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where package_id='" . $edit_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_package") {
    $tbl_id = $_POST['tbl_id'];

    /* $branch_query= "select * from package where package_id='".$id."'";
	$branch_result = mysqli_query($conn,$branch_query);
	$count += mysqli_num_rows($branch_result);
	if($count == 0){ */
    $query = "delete  from package where package_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
    /* }
	else{
		echo "404-del";
	} */
}
if ($form_name == "inacv_package") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  package set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where package_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

//vehicle
if ($form_name == "add_vehicle") {
    extract($_POST);
    if ($edit_id != '') {
        $query = "update  vehicle set vehicle_number='" . $vehicle_number . "',vehicle_type='" . $vehicle_type . "',model='" . $model . "',fitness='" . $fitness . "',insurance='" . $insurance . "',road_tax='" . $road_tax . "',permit='" . $permit . "',emission='" . $emission . "',pollution_certificate='" . $pollution_certificate . "',finance='" . $finance . "',vehicle_status='" . $vehicle_status . "',registration='" . $registration . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where md5(vehicle_id)='" . $edit_id . "'";
        $result = mysqli_query($conn, $query);
    } else {

        $query = "insert into vehicle(vehicle_number,vehicle_type,model,fitness,insurance,road_tax,permit,emission,pollution_certificate,finance,vehicle_status,registration,created_at,created_by,status)values
		('" . $vehicle_number . "','" . $vehicle_type . "','" . $model . "','" . $fitness . "','" . $insurance . "','" . $road_tax . "','" . $permit . "','" . $emission . "','" . $pollution_certificate . "','" . $finance . "','" . $vehicle_status . "','" . $registration . "','" . $created_at . "','" . $created_by . "','0')";
        $result = mysqli_query($conn, $query);
    }
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "del_vehicle") {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete  from vehicle where vehicle_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
    /* }
	else{
		echo "404-del";
	} */
}
if ($form_name == "inacv_vehicle") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  vehicle set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where vehicle_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "add_delivery") {
    $sequence =  $_POST['sequence'];
    $status_type = $_POST['status_type'];

    $query = "insert into delivery_status(status_type,sequence,created_at,created_by,status)values
		('" . $status_type . "','" . $sequence . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "edit_delivery") {
    $edit_id = $_POST['edit_id'];
    $vehicle_no =  $_POST['vehicle_no'];
    $branch = $_POST['branch'];
    $rc_book_no = $_POST['rc_book_no'];
    $rc_book_expires = $_POST['rc_book_expires'];
    $insurance_no  = $_POST['insurance_no'];
    $insurance_expires = $_POST['insurance_expires'];
    $environment = $_POST['environment'];
    $environment_expires = $_POST['environment_expires'];

    $query = "update  delivery_status set status_type='" . $status_type . "',sequence='" . $sequence . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where delivery_status_id='" . $edit_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_delivery") {
    $tbl_id = $_POST['tbl_id'];

    /* $branch_query= "select * from package where package_id='".$id."'";
	$branch_result = mysqli_query($conn,$branch_query);
	$count += mysqli_num_rows($branch_result);
	if($count == 0){ */
    $query = "delete  from delivery_status where delivery_status_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
    /* }
	else{
		echo "404-del";
	} */
}
if ($form_name == "inacv_delivery") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  delivery_status set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where delivery_status_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "add_client_branch") {
    $edit_id = $_POST['edit_id'];
    $company_id =  $_POST['company_id'];
    $branch_name = $_POST['branch_name'];
    $contact_person = $_POST['contact_person'];
    $contact_no = $_POST['contact_no'];
    $address1  = $_POST['address1'];
    $address2 = $_POST['address2'];
    $state = $_POST['state'];
    $city = $_POST['city'];
    $pincode = $_POST['pincode'];
    $email = $_POST['email'];
    if ($edit_id != '') {
        $query = "update client_branch set company_id='" . $company_id . "',branch_name='" . $branch_name . "',branch_contact_person='" . $contact_person . "',contact_no='" . $contact_no . "',address1='" . $address1 . "',address2='" . $address2 . "',city='" . $city . "',state='" . $state . "',pincode='" . $pincode . "',email='" . $email . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where md5(client_branch_id) = '" . $edit_id . "'";
        $result = mysqli_query($conn, $query);
    } else {
        $query = "insert into client_branch(company_id,branch_name,branch_contact_person,contact_no,address1,address2,city,state,pincode,email,created_at,created_by,status)values
		('" . $company_id . "','" . $branch_name . "','" . $contact_person . "','" . $contact_no . "','" . $address1 . "','" . $address2 . "','" . $city . "','" . $state . "','" . $pincode . "','" . $email . "','" . $created_at . "','" . $created_by . "','0')";
        $result = mysqli_query($conn, $query);
    }
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "del_client_branch") {
    $tbl_id = $_POST['tbl_id'];

    /* $branch_query= "select * from package where package_id='".$id."'";
	$branch_result = mysqli_query($conn,$branch_query);
	$count += mysqli_num_rows($branch_result);
	if($count == 0){ */
    $query = "delete  from client_branch where client_branch_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
    /* }
	else{
		echo "404-del";
	} */
}


if ($form_name == "add_customer_mapping") {
    $client = $_POST['client'];
    $new_id = $_POST['new_id'] != "" ? explode(",", $_POST['new_id']) : "";
    $del_id = $_POST['del_id'] != "" ? explode(",", trim($_POST['del_id'])) : '';
    //print_r($new_id);
    $select_query = "select * from customer_mapping where client='" . $client . "'";
    $select_result = mysqli_query($conn, $select_query);
    $select_count = mysqli_num_rows($select_result);
    if ($select_count > 0) {
        $select_row = mysqli_fetch_array($select_result);
        $last_insert_id = $select_row['mapping_id'];
    } else {

        $mapping_query = mysqli_query($conn, "insert  into customer_mapping(client,created_at,created_by,status)values('" . $client . "','" . $created_at . "','" . $created_by . "','0')");
        $last_insert_id = mysqli_insert_id($conn);
    }
    for ($i = 0; $i < count($del_id); $i++) {    //echo "delete from customer_mapping_lists where list_id='$del_id[$i]'";	
        $query = mysqli_query($conn, "delete from customer_mapping_lists where list_id='$del_id[$i]'");
    }


    for ($i = 0; $i < count($new_id); $i++) {
        if ($new_id[$i] > 0) {
            $query = mysqli_query($conn, "insert into customer_mapping_lists(client_id,mapping_id,created_at,created_by,status) values('$new_id[$i]','$last_insert_id','$created_at','$created_by','0')");
        }
    }
    if ($query)
        echo 1;
    else
        echo 0;
}


if ($form_name == "inacv_mapped_client") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update customer_mapping_lists  set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where list_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "del_mapped_client") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "delete from customer_mapping_lists  where list_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "inacv_client_branch") {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update client_branch  set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where client_branch_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
//Approve client
if ($form_name == "approve_client") {
    $company_name =  $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $address1 = $_POST['address1'];
    $address2 = $_POST['address2'];
    $state = $_POST['state'];
    $city = $_POST['city'];
    $pincode = $_POST['pincode'];
    $email = $_POST['email'];
    $contact_no = $_POST['contact_no'];
    $gst_no = $_POST['gst_no'];
    $pan_no = $_POST['pan_no'];
    $multiple_branches = $_POST['multiple_branches'];
    $automation = $_POST['transit_automation'];

    $query = "update client set client_company_name='" . $company_name . "',contact_person='" . $contact_person . "',address1='" . $address1 . "',address2='" . $address2 . "',state='" . $state . "',city='" . $city . "',pincode='" . $pincode . "',email='" . $email . "',contact_no='" . $contact_no . "',gst_no='" . $gst_no . "',pan_no='" . $pan_no . "',multiple_branches='" . $multiple_branches . "',automation='" . $automation . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "',approve_status='0'  where  md5(client_id)='" . $_POST['edit_id'] . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
//Consignment Booking
if ($form_name == "add_new_consignment") {
    $out_put = array();
    extract($_POST);
    // var_dump($_POST);
    // exit();

    //	print_r($_POST);die;
    $tables = get_trans_table_name($conn, $grn_date);
    $get_m_y = explode('_', $tables[0]);
    $month = $get_m_y[1];
    $year = $get_m_y[2];
    if (!empty($_SESSION['company_id'])) {
        /*$query_code=mysqli_query($conn,"select * from client where client_id='".$_SESSION['company_id']."'");
			$r_code=mysqli_fetch_array($query_code);
			$query_max=mysqli_query($conn,"select * from transaction_log where client_id='".$_SESSION['company_id']."'");
			$r_max=mysqli_fetch_array($query_max);
			$id=$r_max['grn_id']+1;
			$billing_code = $r_code['billing_code'];
			$grn_no=$billing_code.sprintf("%05d",$id);*/
        $grn_no = $_POST['grn_no'];
    } else {
        // echo "select * from transaction_log where client_id=0";
        if (!empty($id)) {
            $grn_no = $_POST['grn_no'];
        } else {
            $query_max = mysqli_query($conn, "select * from transaction_log where client_id=0");
            $r_max = mysqli_fetch_array($query_max);
            $id = $r_max['grn_id'] + 1;
            $grn_no = "LA" . $_POST['grn_no1'];
        }
    }


    $consignorquery = "select * from client where client_id='$consignor'";
    $consignorresult = mysqli_query($conn, $consignorquery);
    $consignorrow = mysqli_fetch_array($consignorresult);

    $address1 = $consignorrow['address1'];
    $address2 = $consignorrow['address2'];
    $city = $consignorrow['city'];
    $pincode = $consignorrow['pincode'];
    $state = $consignorrow['state'];
    $phone = $consignorrow['phone'];
    $gst_no = $consignorrow['gst_no'];

    $consigneequery = "select * from client where client_id='$consignee'";
    $consigneeresult = mysqli_query($conn, $consigneequery);
    $consigneerow = mysqli_fetch_array($consigneeresult);

    $con_address1 = $consigneerow['address1'];
    $con_address2 = $consigneerow['address2'];
    $con_city = $consigneerow['city'];
    $con_state = $consigneerow['state'];
    $con_pincode = $consigneerow['pincode'];
    $con_phone = $consigneerow['phone'];
    $con_gst = $consigneerow['gst_no'];
    $ftl_type = $_POST['truck_type'];



    $query = "insert into $tables[0](grn_no,grn_id,grn_date,mode_of_transportation,ftl_type,origin,destination,mode_of_consignment,consigner,address1,address2,city,pincode,state,phone,gst_no,consignee,con_address1,con_address2,con_city,con_state,con_pincode,con_phone,con_gst_no,goods_dedared_value,octroi,dimension1,dimension2,dimension3,frieght_rate,frieght_amount,loading_unloading_rate,
		 loading_unloading_amount, crane_fork_lift_rate, crane_fork_lift_amount,cod_rate,cod_amount,fov_rate,fov_amount,doc_charges,doc_amount,cartage_rate,cartage_amount,labour_handling_rate,labour_handling_amount,octroi_rate,octroi_amount,other_charge_rate,other_charge_amount,gst_rate,gst_amount,total,paid_amount, balance, paid_status,total_words,note1,note2,truck,consigner_signature,client_id,created_at,created_by,status,eway_number) values('" . $grn_no . "','" . $id . "','" . $grn_date . "','" . $mode_of_trasport . "','$ftl_type','" . $origin . "','" . $destination . "','" . $mode_of_consignment . "','" . $consignor . "','" . $address1 . "','" . $address2 . "','" . $city . "','" . $pincode . "','" . $state . "','" . $phone . "','" . $gst_no . "','" . $consignee . "','" . $con_address1 . "','" . $con_address2 . "','" . $con_city . "','" . $con_state . "','" . $con_pincode . "','" . $con_phone . "','" . $con_gst . "','" . $goods_dedared_value . "','" . $octroi . "','" . $dimension1 . "','" . $dimension2 . "','" . $dimension3 . "','" . $frieght_rate . "','" . $frieght_amount . "','" . $loading_unload_rate . "','" . $loading_unload_chrg . "','" . $crane_forklift_rate . "','" . $crane_forklift_chrg . "','" . $cod_rate . "','" . $cod_amount . "','" . $fov_rate . "','" . $fov_amount . "','" . $doc_rate . "','" . $doc_amount . "','" . $cartage_rate . "','" . $cartage_amount . "','" . $labour_rate . "','" . $labour_amount . "','" . $octroi_rate . "','" . $octroi_amount . "','" . $other_rate . "','" . $other_amount . "','" . $gst_rate . "','" . $gst_amount . "','" . $total . "','0','" . $total . "','0','" . $amount_in_words . "','" . $note1 . "','" . $note2 . "','" . $vehicle_no . "','" . $signature . "','" . $consignor . "','" . $created_at . "','" . $created_by . "','1','" . $eway_number . "')";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));
    $transaction_id = mysqli_insert_id($conn);

    for ($k = 0; $k < count($_FILES["file_receipt"]["name"]); $k++) {
        $file_name = uniqid() . $_FILES["file_receipt"]["name"][$k];
        if (move_uploaded_file($_FILES["file_receipt"]["tmp_name"][$k], "invoice_image/" . $file_name)) { //images/


            $fr_query = "insert into $tables[1](transaction_id,attachment,created_at,created_by,status) values ('$transaction_id','$file_name','$created_at','$created_by','0')";
            $fr_result = mysqli_query($conn, $fr_query) or die(mysqli_error($conn));
            $attachment_id = mysqli_insert_id($conn);
        }
    }
    //$total_pkg=0;

    for ($j = 0; $j < count($_POST['no_of_pkg']); $j++) {

        $f_query = "insert into $tables[2](transaction_id,no_of_pkge,type_of_pkge,party_invoice_no,said_contents,qty,gross_weight,charged_weight,created_at,created_by,status) values('" . $transaction_id . "','" . $no_of_pkg[$j] . "','" . $type_of_pkg[$j] . "','" . $party_invoice[$j] . "','" . $content[$j] . "','" . $qty[$j] . "','" . $gross[$j] . "','" . $charged[$j] . "','" . $created_at . "','" . $created_by . "','0')";
        $f_result = mysqli_query($conn, $f_query) or die(mysqli_error($conn));

        //$total_pkg +=$_POST['no_of_pkg'];	
        $package[] = $qty[$j];

        $pkg_name[] = $type_of_pkg[$j];
    }

    //Qrcode Start
    //require 'vendor/autoload.php'; For Barcode
    include('libs/phpqrcode/qrlib.php');
    $result_bar = [];

    foreach ($pkg_name as $index => $val) {
        $result_bar[$val] = ($result_bar[$val] ?? 0) + $package[$index];
    }
    $package_type1 = (array_keys($result_bar));
    $packge_qty = (array_values($result_bar));
    //$redColor = [0, 0, 0];
    //$generator = new Picqer\Barcode\BarcodeGeneratorJPG();
    $name = $grn_no;
    //var_dump($qty);

    //$rate = 10;

    foreach ($packge_qty as $key => $val) {
        $get_qty = $val;
        //var_dump($get_qty);
        //echo "KEY".$key. "value". $val;
        if (array_key_exists($key, $package_type1)) {
            $get_package = $package_type1[$key];
            //var_dump($get_package);

            switch ($get_package) {
                case "1":
                    $pack_name = "CBX";
                    break;
                case "2":
                    $pack_name = "PBG";
                    break;
                case "3":
                    $pack_name = "ROL";
                    break;
                case "5":
                    $pack_name = "SHT";
                    break;
                case "6":
                    $pack_name = "BDL";
                    break;
                case "7":
                    $pack_name = "CVR";
                    break;
                case "8":
                    $pack_name = "PBL";
                    break;
                case "9":
                    $pack_name = "CAN";
                    break;
                case "10":
                    $pack_name = "BOX";
                    break;
                case "11":
                    $pack_name = "BAG";
                    break;
                case "12":
                    $pack_name = "MLD";
                    break;
                case "13":
                    $pack_name = "PKT";
                    break;
                case "14":
                    $pack_name = "CES";
                    break;
                case "15":
                    $pack_name = "CAT";
                    break;
                case "16":
                    $pack_name = "GRL";
                    break;
                case "17":
                    $pack_name = "P.B";
                    break;
                case "18":
                    $pack_name = "PRL";
                    break;
                default:
                    $pack_name = "No Package Type Found!";
            }

            //$productData = "098{$get_qty}10{$name}55{$rate}";
            $tempDir = 'qrcode/';
            $productData = strtoupper($name);
            $j = 1;
            for ($i = 0; $i < $get_qty; $i++) {
                $change_index[$j] = $i + 1;
                $names =  $productData . $pack_name . '-00' . $change_index[$j];
                $contents = 'https://staging.graciousexpress.com/web/testqrcode.php?grn_no=' . $name . '&grn_date=' . $grn_date;
                //var_dump($names);
                //Barcode
                //file_put_contents('barcode/'.$names.'.jpg', $generator->getBarcode($names, $generator::TYPE_CODE_128,3,100,$redColor));

                //Qrcode
                QRcode::png($contents, $tempDir . '' . $names . '.png', QR_ECLEVEL_L, 5);
            }
        }
    }
    //Qrcode End

    $invoice_id = mysqli_insert_id($conn);
    //echo $total_pkg;

    if ($transaction_id) {
        if ($_SESSION['company_id'] != '') {
            if ($_SESSION['role'] == 'AD') {
                if ($consignor == '3631') {
                    $log_query = mysqli_query($conn, "select * from transaction_log where client_id='3631'");
                    $log_count = mysqli_num_rows($log_query);
                    if ($log_count == 0) {
                        $query_log = mysqli_query($conn, "insert into transaction_log(transaction_id,attachment_id,invoice_id,grn_id,grn_no,client_id) values('$transaction_id','$attachment_id','$invoice_id','1','$grn_no','3631')") or die(mysqli_error($conn));
                    } else {
                        $query_log = mysqli_query($conn, "update transaction_log set transaction_id='$transaction_id',attachment_id='$attachment_id',invoice_id='$invoice_id',grn_id='$id',grn_no='$grn_no'  where client_id='3631'") or die(mysqli_error($conn));
                    }
                } else {
                    $log_query = mysqli_query($conn, "select * from transaction_log where client_id='" . $consignor . "'");
                    $log_count = mysqli_num_rows($log_query);
                    if ($log_count == 0) {
                        $query_log = mysqli_query($conn, "insert into transaction_log(transaction_id,attachment_id,invoice_id,grn_id,grn_no,client_id) values('$transaction_id','$attachment_id','$invoice_id','1','$grn_no','" . $consignor . "')") or die(mysqli_error($conn));
                    } else {
                        $query_log = mysqli_query($conn, "update transaction_log set transaction_id='$transaction_id',attachment_id='$attachment_id',invoice_id='$invoice_id',grn_id='$id',grn_no='$grn_no'  where client_id='" . $consignor . "'") or die(mysqli_error($conn));
                    }
                }
            } else {
                $log_query = mysqli_query($conn, "select * from transaction_log where client_id='" . $_SESSION['company_id'] . "'");
                $log_count = mysqli_num_rows($log_query);
                if ($log_count == 0) {
                    $query_log = mysqli_query($conn, "insert into transaction_log(transaction_id,attachment_id,invoice_id,grn_id,grn_no,client_id) values('$transaction_id','$attachment_id','$invoice_id','1','$grn_no','" . $_SESSION['company_id'] . "')") or die(mysqli_error($conn));
                } else {
                    $query_log = mysqli_query($conn, "update transaction_log set transaction_id='$transaction_id',attachment_id='$attachment_id',invoice_id='$invoice_id',grn_id='$id',grn_no='$grn_no'  where client_id='" . $_SESSION['company_id'] . "'") or die(mysqli_error($conn));
                }
            }
        } else {

            $query_log = mysqli_query($conn, "update transaction_log set transaction_id='$transaction_id',attachment_id='$attachment_id',invoice_id='$invoice_id',grn_id='$id',grn_no='$grn_no'  where client_id='0") or die(mysqli_error($conn));
        }

        $inv = '';
        for ($i = 0; $i < count($party_invoice); $i++) {
            if ($party_invoice[$i] != "") {
                $inv .= $party_invoice[$i] . ',';
            }
        }
        $inv = rtrim($inv, ",");
        $url = "https://staging.graciousexpress.com/web/transaction_pdf.php?month=" . $month . "&year=" . $year . "&id=" . $transaction_id . "";
        $path = "transaction_pdf/" . $month . "_" . $year . "_" . $transaction_id . "transaction.pdf";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        $result_url = file_put_contents($path, $data);

        //*Invoice Section Start
        //Sequence Generation

        if ($mode_of_trasport == '1' || $mode_of_trasport == '2' || $mode_of_trasport == '3') {
            $type = 'GST';
            // $sac = "996812";
            // $sac_text = '996812 - COURIER SERVICES';
        } else {
            $type = 'GTA';
            // $sac = "9965";
            // $sac_text = '9965 - Good Transport Agency Service';
        }
        //$conn1 = mysqli_connect("localhost","root","","bookconsignment");


        $grn_date_expl = explode("-", $grn_date);
        $cur_year = $grn_date_expl[2];

        $current_year = $cur_year;

        $previous_year =  $cur_year - 1;


        $p_y = substr($previous_year, 2);
        $c_y = substr($current_year, 2);

        $year_insert = $p_y . "-" . $c_y;

        $invoice_table = invoice_table_function($conn, $grn_date);

        $select = mysqli_query($conn, "select * from " . $invoice_table);
        $get_count = mysqli_num_rows($select);
        if ($get_count == 0) {
            $insert_data = "INSERT INTO " . $invoice_table . "(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('0','HRGST','$year_insert','GST','$created_at','$created_by'),('0','HRGTA','$year_insert','GTA','$created_at','$created_by')";
            //$insert_data .= "INSERT INTO ".$invoice_table."(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('1','HRGTA','$year_insert','GTA','$created_at','$created_by')"; 
            //$res = mysqli_multi_query($conn,$insert_data);
            $res = mysqli_query($conn, $insert_data);
            if ($res) {
                $inv_query = "select * from trans_invoice_tbl" . $year . " where inv_type='$type'";
                $inv_query_result = mysqli_query($conn, $inv_query);
                $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                $inv_seq = $inv_query_row['invoice_no'] + 1;
                //print_r($inv_seq);
                //$inv_seq = '100';
                $inv_text = $inv_query_row['gst_text'];
                $inv_year = $inv_query_row['gst_year'];
                $sequence = sprintf('%05d', $inv_seq);
                $unique_invoice_no = $inv_text . "/" . $sequence . "/" . $inv_year;
                //print_r($unique_invoice_no);
            }
        } else {

            $inv_query = "select * from trans_invoice_tbl" . $year . " where inv_type='$type'";
            $inv_query_result = mysqli_query($conn, $inv_query);
            $inv_query_row = mysqli_fetch_assoc($inv_query_result);

            $inv_seq = $inv_query_row['invoice_no'] + 1;
            //print_r($inv_seq);
            //$inv_seq = '100';
            $inv_text = $inv_query_row['gst_text'];
            $inv_year = $inv_query_row['gst_year'];
            $sequence = sprintf('%05d', $inv_seq);
            $unique_invoice_no = $inv_text . "/" . $sequence . "/" . $inv_year;
        }

        //Sequence Generation

        $directory = 'digital_invoice/';
        $invoice_url = "https://staging.graciousexpress.com/web/gst_invoice_page.php?month=" . $month . "&year=" . $year . "&id=" . $transaction_id . "&invoice_no=" . $unique_invoice_no . "";
        $invoice_file_name = $month . "_" . $year . "_" . $transaction_id . "invoice";
        $download_path =  $directory . $invoice_file_name . '.pdf';
        $file_inv_download = curl_init($invoice_url);
        curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($file_inv_download, CURLOPT_REFERER, true);
        curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
        $store_inv = curl_exec($file_inv_download);
        curl_close($invoice_url);
        $save_inv_file = file_put_contents($download_path, $store_inv);
        if ($save_inv_file) {
            $update = mysqli_query($conn, "update trans_invoice_tbl" . $year . " SET invoice_no = '$inv_seq', updated_by = '$updated_by', updated_at = '$updated_at' where inv_type = '$type'");

            $query_inv = "update $tables[0] set `invoice_no` = '$unique_invoice_no' where transaction_id ='$transaction_id'";
            $res = mysqli_query($conn, $query_inv);
        }

        //*Invoice Section End

        $image = array();
        $img_query = mysqli_query($conn, "select * from $tables[1] where transaction_id ='" . $transaction_id . "'");
        if (mysqli_num_rows($img_query) > 0) {
            while ($img_result = mysqli_fetch_array($img_query)) {
                array_push($image, "invoice_image/" . $img_result['attachment']);
            }
        }
        //print_r($image);
        $msg = '<p style="line-height: 24px; margin-bottom:15px;">
						  
		Thank you for booking the consignment, please find the booking information and the attached GR copy for your reference below.				
		<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
		<tr>
		<td >GRN No	</td><td >' . $grn_no . '</td>
		</tr><tr>	
		<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
		</tr>
		<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
		<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
		<tr>		
		<td >Your Invoice No	</td><td >	' . $inv . '	</td>	
		</tr><tr>		
		<td >Status	</td><td >Consignment Booked</td>		
			</td>
							</tr>
						</table>	
		<br>
        <br>
						
		</p>';
        $to_name = array();
        $to_email = array();

        if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
            //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
            array_push($to_email, get_client_email($conn, $consignor), get_client_email($conn, $consignee));
            array_push($to_name, get_client_name($conn, $consignor), get_client_name($conn, $consignee));

            $mail = sendAttachments($to_name, $to_email, 'Consignment Booking Notification', $path, $image, $msg, $name);

            //echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst'); 

        }
        /*if(!empty(get_client_email($conn,$consignor))){
				$mail = sendAppMail(get_client_name($conn,$consignor),get_client_email($conn,$consignor), 'Consignment Booking Notification | '.$grn_no.' To {'.get_client_name($conn,$consignee).'}', $msg); 
		}
		if(!empty(get_client_email($conn,$consignee))){
				$mail = sendAppMail(get_client_name($conn,$consignee),get_client_email($conn,$consignee), 'Consignment Booking Notification | '.$grn_no.' To {'.get_client_name($conn,$consignee).'}', $msg); 
		}*/

        //*Send Invoice Instanly
        if ($mode_of_consignment == '3' || $mode_of_consignment  == '4') {
            if ($mode_of_consignment == '3') { //Pay at Booking 

                $check_partywise_frq = checkPartyWiseFrequency($conn, $consignor); // Check Frequency set or not
                if ($check_partywise_frq == 0) { // Frequncy is Set
                    //Invoice Sent as per frequency
                    echo "Frequency is Set";
                } else {

                    //Other Process Goes here
                    $check_restricted = check_invoice_restricted($conn, $consignor);
                    if ($check_restricted == 0) {
                        // $msg = '<p style="line-height: 24px; margin-bottom:15px;">
                        // 				Thank You for Your Order On <a href = "https://graciousexpress.colanapps.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
                        // 				Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
                        // 				<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
                        // 				<tr>
                        // 				<td >GRN No	</td><td >' . $grn_no . '</td>
                        // 				</tr><tr>	
                        // 				<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
                        // 				</tr>
                        // 				<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
                        // 				<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
                        // 				<tr>		
                        // 				<td >Status	</td><td >Consignment Booked.</td>		
                        // 					</td>
                        // 									</tr>
                        // 								</table>	
                        // 				<br>
                        // 				<br>';

                        // $to_name = array();
                        // $to_email = array();

                        // if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                        //     //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)

                        //     array_push($to_email, get_client_email($conn, $consignor), get_client_email($conn, $consignee));

                        //     array_push($to_name, get_client_name($conn, $consignor), get_client_name($conn, $consignee));

                        //     $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $download_path, $image, $msg, $name);



                        //Need to Send Payment Link to User

                        //}
                    } else {

                        //echo "Restricted Client";
                    }
                }
            } else { // Cash on Delivery

                //$check_partywise_frq = checkPartyWiseFrequency($conn, $consignee); // Check Frequency set or not
                // if ($check_partywise_frq == 0) { // Frequncy is Set
                //     //Invoice Sent as per frequency
                //     echo "Frequency is Set";
                // } else {
                    // $outstanding = SetOutStandingInfo($conn, $consignee, $total); //Set Outstanding For COD

                    // $check_restricted = check_invoice_restricted($conn, $consignee);
                    // if ($check_restricted == 0) {

                    //     //Need to create Payment Link for COD

                    //     //End Payment Link

                        $msg = '<p style="line-height: 24px; margin-bottom:15px;">
										Thank You for Your Order On <a href = "https://graciousexpress.colanapps.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
										Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
										<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
										<tr>
										<td >GRN No	</td><td >' . $grn_no . '</td>
										</tr><tr>	
										<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
										</tr>
										<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
										<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
										<tr>		
										<td >Status	</td><td >Consignment Booked</td>		
											</td>
															</tr>
														</table>	
										<br>
										<br>';

                        $to_name = array();
                        $to_email = array();

                        if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                            //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)

                            array_push($to_email, get_client_email($conn, $consignee), get_client_email($conn, $consignor));
                            array_push($to_name, get_client_name($conn, $consignee), get_client_name($conn, $consignor));

                            $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $download_path, $image, $msg, $name);
                        }
                    // } else {

                    //     //echo "Restricted Client";

                    // }
               // }
            }
        } else {

            //Payment Mode 1 and 2

            if ($mode_of_consignment == 1) { // To Pay

                //echo "Consignee";
                $outstanding = SetOutStandingInfo($conn, $consignee, $total);
            } else { // By Sender

                //echo "Consignor";
                $outstanding = SetOutStandingInfo($conn, $consignor, $total);
            }
        }
        //*End 

        $out_put['result'] = 1;
        $out_put['data'] = $grn_no;
    } else {
        $out_put['result'] = "0";
    }

    echo json_encode($out_put);
}
if ($form_name == "add_new_user_consignment") {
    $out_put = array();
    $edit_user_id = $_POST['edit_id'];
    extract($_POST);
    //	print_r($_POST);die;
    $tables = get_trans_table_name($conn, $grn_date);
    $get_m_y = explode('_', $tables[0]);
    $month = $get_m_y[1];
    $year = $get_m_y[2];
    if (!empty($_SESSION['company_id'])) {
        /*$query_code=mysqli_query($conn,"select * from client where client_id='".$_SESSION['company_id']."'");
			$r_code=mysqli_fetch_array($query_code);
			$query_max=mysqli_query($conn,"select * from transaction_log where client_id='".$_SESSION['company_id']."'");
			$r_max=mysqli_fetch_array($query_max);
			$id=$r_max['grn_id']+1;
			$billing_code = $r_code['billing_code'];
			$grn_no=$billing_code.sprintf("%05d",$id);*/
        $grn_no = $_POST['grn_no'];
    } else {
        // echo "select * from transaction_log where client_id=0";
        if (!empty($id)) {
            $grn_no = $_POST['grn_no'];
        } else {
            $query_max = mysqli_query($conn, "select * from transaction_log where client_id=0");
            $r_max = mysqli_fetch_array($query_max);
            $id = $r_max['grn_id'] + 1;
            $grn_no = "LA" . $_POST['grn_no1'];
        }
    }


    $consignorquery = "select * from client where client_id='$consignor'";
    $consignorresult = mysqli_query($conn, $consignorquery);
    $consignorrow = mysqli_fetch_array($consignorresult);

    $address1 = $consignorrow['address1'];
    $address2 = $consignorrow['address2'];
    $city = $consignorrow['city'];
    $pincode = $consignorrow['pincode'];
    $state = $consignorrow['state'];
    $phone = $consignorrow['phone'];
    $gst_no = $consignorrow['gst_no'];

    $consigneequery = "select * from client where client_id='$consignee'";
    $consigneeresult = mysqli_query($conn, $consigneequery);
    $consigneerow = mysqli_fetch_array($consigneeresult);

    $con_address1 = $consigneerow['address1'];
    $con_address2 = $consigneerow['address2'];
    $con_city = $consigneerow['city'];
    $con_state = $consigneerow['state'];
    $con_pincode = $consigneerow['pincode'];
    $con_phone = $consigneerow['phone'];
    $con_gst = $consigneerow['gst_no'];
    $ftl_type = $_POST['truck_type'];
    //$frieght_weight = $_POST['weight1'];
    // $weight1 = $_POST['weight1'];

    $query = "insert into $tables[0](grn_no,grn_id,grn_date,mode_of_transportation,ftl_type,origin,destination,mode_of_consignment,consigner,address1,address2,city,pincode,state,phone,gst_no,consignee,con_address1,con_address2,con_city,con_state,con_pincode,con_phone,con_gst_no,goods_dedared_value,octroi,dimension1,dimension2,dimension3,frieght_rate,frieght_amount,loading_unloading_rate,
		 loading_unloading_amount, crane_fork_lift_rate, crane_fork_lift_amount,cod_rate,cod_amount,fov_rate,fov_amount,doc_charges,doc_amount,cartage_rate,cartage_amount,labour_handling_rate,labour_handling_amount,octroi_rate,octroi_amount,other_charge_rate,other_charge_amount,gst_rate,gst_amount,total,paid_amount, balance, paid_status,total_words,note1,note2,truck,consigner_signature,client_id,created_at,created_by,status,eway_number) values('" . $grn_no . "','" . $id . "','" . $grn_date . "','" . $mode_of_trasport . "','$ftl_type','" . $origin . "','" . $destination . "','" . $mode_of_consignment . "','" . $consignor . "','" . $address1 . "','" . $address2 . "','" . $city . "','" . $pincode . "','" . $state . "','" . $phone . "','" . $gst_no . "','" . $consignee . "','" . $con_address1 . "','" . $con_address2 . "','" . $con_city . "','" . $con_state . "','" . $con_pincode . "','" . $con_phone . "','" . $con_gst . "','" . $goods_dedared_value . "','" . $octroi . "','" . $dimension1 . "','" . $dimension2 . "','" . $dimension3 . "','" . $frieght_rate . "','" . $frieght_amount . "','" . $loading_unload_rate . "','" . $loading_unload_chrg . "','" . $crane_forklift_rate . "','" . $crane_forklift_chrg . "','" . $cod_rate . "','" . $cod_amount . "','" . $fov_rate . "','" . $fov_amount . "','" . $doc_rate . "','" . $doc_amount . "','" . $cartage_rate . "','" . $cartage_amount . "','" . $labour_rate . "','" . $labour_amount . "','" . $octroi_rate . "','" . $octroi_amount . "','" . $other_rate . "','" . $other_amount . "','" . $gst_rate . "','" . $gst_amount . "','" . $total . "','0','" . $total . "','0','" . $amount_in_words . "','" . $note1 . "','" . $note2 . "','" . $vehicle_no . "','" . $signature . "','" . $consignor . "','" . $created_at . "','" . $created_by . "','1','" . $eway_number . "')";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));
    $transaction_id = mysqli_insert_id($conn);

    if ($_FILES['file_receipt']['name'] != '') {
        $test = explode('.', $_FILES['file_receipt']['name']);
        $extension = end($test);
        $file_name = rand(100, 999) . '.' . $extension;

        $location = 'invoice_image/' . $file_name;
        move_uploaded_file($_FILES['file_receipt']['tmp_name'], $location);
    } else {
        $query = "select attchment from user_inquiry_list where user_id = '$edit_user_id'";
        $result = mysqli_query($conn, $query);
        $oldimage = mysqli_fetch_assoc($result);
        $file_name = $oldimage['attchment'];
        //$file_name = 'abc.jpg';
    }
    //for($k=0;$k<count($_FILES["file_receipt"]["name"]);$k++) {

    //$file_name = uniqid().$_FILES["file_receipt"]["name"][$k];
    //if(move_uploaded_file($_FILES["file_receipt"]["tmp_name"][$k], "invoice_image/".$file_name)){ //images/	
    //}
    $fr_query = "insert into $tables[1](transaction_id,attachment,created_at,created_by,status) values ('$transaction_id','$file_name','$created_at','$created_by','0')";
    $fr_result = mysqli_query($conn, $fr_query) or die(mysqli_error($conn));
    $attachment_id = mysqli_insert_id($conn);
    //}
    //$total_pkg=0;

    for ($j = 0; $j < count($_POST['no_of_pkg']); $j++) {

        $f_query = "insert into $tables[2](transaction_id,no_of_pkge,type_of_pkge,party_invoice_no,said_contents,qty,gross_weight,charged_weight,created_at,created_by,status) values('" . $transaction_id . "','" . $no_of_pkg[$j] . "','" . $type_of_pkg[$j] . "','" . $party_invoice[$j] . "','" . $content[$j] . "','" . $qty[$j] . "','" . $gross[$j] . "','" . $charged[$j] . "','" . $created_at . "','" . $created_by . "','0')";
        $f_result = mysqli_query($conn, $f_query) or die(mysqli_error($conn));

        $package[] = $qty[$j];

        $pkg_name[] = $type_of_pkg[$j];

        //$total_pkg +=$_POST['no_of_pkg'];	
    }

    //Barcode Start
    //require 'vendor/autoload.php'; For Barcode
    include('libs/phpqrcode/qrlib.php');
    $result_bar = [];

    foreach ($pkg_name as $index => $val) {
        $result_bar[$val] = ($result_bar[$val] ?? 0) + $package[$index];
    }
    $package_type1 = (array_keys($result_bar));
    $packge_qty = (array_values($result_bar));
    //$redColor = [0, 0, 0];
    //$generator = new Picqer\Barcode\BarcodeGeneratorJPG();
    $name = $grn_no;
    //var_dump($qty);

    //$rate = 10;

    foreach ($packge_qty as $key => $val) {
        $get_qty = $val;
        //var_dump($get_qty);
        //echo "KEY".$key. "value". $val;
        if (array_key_exists($key, $package_type1)) {
            $get_package = $package_type1[$key];
            //var_dump($get_package);

            switch ($get_package) {
                case "1":
                    $pack_name = "CBX";
                    break;
                case "2":
                    $pack_name = "PBG";
                    break;
                case "3":
                    $pack_name = "ROL";
                    break;
                case "5":
                    $pack_name = "SHT";
                    break;
                case "6":
                    $pack_name = "BDL";
                    break;
                case "7":
                    $pack_name = "CVR";
                    break;
                case "8":
                    $pack_name = "PBL";
                    break;
                case "9":
                    $pack_name = "CAN";
                    break;
                case "10":
                    $pack_name = "BOX";
                    break;
                case "11":
                    $pack_name = "BAG";
                    break;
                case "12":
                    $pack_name = "MLD";
                    break;
                case "13":
                    $pack_name = "PKT";
                    break;
                case "14":
                    $pack_name = "CES";
                    break;
                case "15":
                    $pack_name = "CAT";
                    break;
                case "16":
                    $pack_name = "GRL";
                    break;
                case "17":
                    $pack_name = "P.B";
                    break;
                case "18":
                    $pack_name = "PRL";
                    break;
                default:
                    $pack_name = "No Package Type Found!";
            }

            //$productData = "098{$get_qty}10{$name}55{$rate}";
            $tempDir = 'qrcode/';
            $productData = strtoupper($name);
            $j = 1;
            for ($i = 0; $i < $get_qty; $i++) {
                $change_index[$j] = $i + 1;
                $names =  $productData . $pack_name . '-00' . $change_index[$j];
                $contents = 'https://staging.graciousexpress.com/web/testqrcode.php?grn_no=' . $name . '&grn_date=' . $grn_date;
                //var_dump($names);
                //Barcode
                //file_put_contents('barcode/'.$names.'.jpg', $generator->getBarcode($names, $generator::TYPE_CODE_128,3,100,$redColor));

                //Qrcode
                QRcode::png($contents, $tempDir . '' . $names . '.png', QR_ECLEVEL_L, 5);

                $j++;
            }
        }
    }
    //Barcode End




    $invoice_id = mysqli_insert_id($conn);
    //echo $total_pkg;

    if ($transaction_id) {
        if ($_SESSION['company_id'] != '') {
            if ($_SESSION['role'] == 'AD') {
                if ($consignor == '3631') {
                    $log_query = mysqli_query($conn, "select * from transaction_log where client_id='3631'");
                    $log_count = mysqli_num_rows($log_query);
                    if ($log_count == 0) {
                        $query_log = mysqli_query($conn, "insert into transaction_log(transaction_id,attachment_id,invoice_id,grn_id,grn_no,client_id) values('$transaction_id','$attachment_id','$invoice_id','1','$grn_no','3631')") or die(mysqli_error($conn));
                    } else {
                        $query_log = mysqli_query($conn, "update transaction_log set transaction_id='$transaction_id',attachment_id='$attachment_id',invoice_id='$invoice_id',grn_id='$id',grn_no='$grn_no'  where client_id='3631'") or die(mysqli_error($conn));
                    }
                } else {
                    $log_query = mysqli_query($conn, "select * from transaction_log where client_id='$consignor'");
                    $log_count = mysqli_num_rows($log_query);
                    if ($log_count == 0) {
                        $query_log = mysqli_query($conn, "insert into transaction_log(transaction_id,attachment_id,invoice_id,grn_id,grn_no,client_id) values('$transaction_id','$attachment_id','$invoice_id','1','$grn_no','$consignor')") or die(mysqli_error($conn));
                    } else {
                        $query_log = mysqli_query($conn, "update transaction_log set transaction_id='$transaction_id',attachment_id='$attachment_id',invoice_id='$invoice_id',grn_id='$id',grn_no='$grn_no'  where client_id='$consignor'") or die(mysqli_error($conn));
                    }
                }
            } else {
                $log_query = mysqli_query($conn, "select * from transaction_log where client_id='$consignor'");
                $log_count = mysqli_num_rows($log_query);
                if ($log_count == 0) {
                    $query_log = mysqli_query($conn, "insert into transaction_log(transaction_id,attachment_id,invoice_id,grn_id,grn_no,client_id) values('$transaction_id','$attachment_id','$invoice_id','1','$grn_no','$consignor')") or die(mysqli_error($conn));
                } else {
                    $query_log = mysqli_query($conn, "update transaction_log set transaction_id='$transaction_id',attachment_id='$attachment_id',invoice_id='$invoice_id',grn_id='$id',grn_no='$grn_no'  where client_id='$consignor'") or die(mysqli_error($conn));
                }
            }
        } else {

            $query_log = mysqli_query($conn, "update transaction_log set transaction_id='$transaction_id',attachment_id='$attachment_id',invoice_id='$invoice_id',grn_id='$id',grn_no='$grn_no'  where client_id='$consignor'") or die(mysqli_error($conn));
        }

        $invi = '';
        for ($i = 0; $i < count($party_invoice); $i++) {
            if ($party_invoice[$i] != "") {
                $invi .= $party_invoice[$i] . ',';
            }
        }

        $invi = rtrim($invi, ",");
        $url = "https://staging.graciousexpress.com/web/transaction_pdf.php?month=" . $month . "&year=" . $year . "&id=" . $transaction_id . "";
        $path = "transaction_pdf/" . $month . "_" . $year . "_" . $transaction_id . "transaction.pdf";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        $result_url = file_put_contents($path, $data);

        //*Invoice Section Start
        //Sequence Generation

        if ($mode_of_trasport == '1' || $mode_of_trasport == '2' || $mode_of_trasport == '3') {
            $type = 'GST';
            // $sac = "996812";
            // $sac_text = '996812 - COURIER SERVICES';
        } else {
            $type = 'GTA';
            // $sac = "9965";
            // $sac_text = '9965 - Good Transport Agency Service';
        }
        //$conn1 = mysqli_connect("localhost","root","","bookconsignment");


        $grn_date_expl = explode("-", $grn_date);
        $cur_year = $grn_date_expl[2];

        $current_year = $cur_year;

        $previous_year =  $cur_year - 1;


        $p_y = substr($previous_year, 2);
        $c_y = substr($current_year, 2);

        $year_insert = $p_y . "-" . $c_y;

        $invoice_table = invoice_table_function($conn, $grn_date);

        $select = mysqli_query($conn, "select * from " . $invoice_table);
        $get_count = mysqli_num_rows($select);
        if ($get_count == 0) {
            $insert_data = "INSERT INTO " . $invoice_table . "(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('0','HRGST','$year_insert','GST','$created_at','$created_by'),('0','HRGTA','$year_insert','GTA','$created_at','$created_by')";
            //$insert_data .= "INSERT INTO ".$invoice_table."(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('1','HRGTA','$year_insert','GTA','$created_at','$created_by')"; 
            //$res = mysqli_multi_query($conn,$insert_data);
            $res = mysqli_query($conn, $insert_data);
            if ($res) {
                $inv_query = "select * from trans_invoice_tbl" . $year . " where inv_type='$type'";
                $inv_query_result = mysqli_query($conn, $inv_query);
                $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                $inv_seq = $inv_query_row['invoice_no'] + 1;
                //print_r($inv_seq);
                //$inv_seq = '100';
                $inv_text = $inv_query_row['gst_text'];
                $inv_year = $inv_query_row['gst_year'];
                $sequence = sprintf('%05d', $inv_seq);
                $unique_invoice_no = $inv_text . "/" . $sequence . "/" . $inv_year;
                //print_r($unique_invoice_no);
            }
        } else {

            $inv_query = "select * from trans_invoice_tbl" . $year . " where inv_type='$type'";
            $inv_query_result = mysqli_query($conn, $inv_query);
            $inv_query_row = mysqli_fetch_assoc($inv_query_result);

            $inv_seq = $inv_query_row['invoice_no'] + 1;
            //print_r($inv_seq);
            //$inv_seq = '100';
            $inv_text = $inv_query_row['gst_text'];
            $inv_year = $inv_query_row['gst_year'];
            $sequence = sprintf('%05d', $inv_seq);
            $unique_invoice_no = $inv_text . "/" . $sequence . "/" . $inv_year;
        }

        //Sequence Generation

        $directory = 'digital_invoice/';
        $invoice_url = "https://staging.graciousexpress.com/web/gst_invoice_page.php?month=" . $month . "&year=" . $year . "&id=" . $transaction_id . "&invoice_no=" . $unique_invoice_no . "";
        $invoice_file_name = $month . "_" . $year . "_" . $transaction_id . "invoice";
        $download_path =  $directory . $invoice_file_name . '.pdf';
        $file_inv_download = curl_init($invoice_url);
        curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($file_inv_download, CURLOPT_REFERER, true);
        curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
        $store_inv = curl_exec($file_inv_download);
        curl_close($invoice_url);
        $save_inv_file = file_put_contents($download_path, $store_inv);

        if ($save_inv_file) {
            $update = mysqli_query($conn, "update trans_invoice_tbl" . $year . " SET invoice_no = '$inv_seq', updated_by = '$updated_by', updated_at = '$updated_at' where inv_type = '$type'");

            $query_inv = "update $tables[0] set `invoice_no` = '$unique_invoice_no' where transaction_id ='$transaction_id'";
            $res = mysqli_query($conn, $query_inv);
        }

        //$attachments = array($download_path,$path);
        //*Invoice Section End


        $image = array();
        $img_query = mysqli_query($conn, "select * from $tables[1] where transaction_id ='" . $transaction_id . "'");
        if (mysqli_num_rows($img_query) > 0) {
            while ($img_result = mysqli_fetch_array($img_query)) {
                array_push($image, "invoice_image/" . $img_result['attachment']);
            }
        }
        //print_r($image);
        $msg = '<p style="line-height: 24px; margin-bottom:15px;">
						  
			Thank you for booking the consignment, please find the booking information and the attached GR copy for your reference below.					
			<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
			<tr>
			<td >GRN No	</td><td >' . $grn_no . '</td>
			</tr><tr>	
			<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
			</tr>
			<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
			<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
			<tr>		
			<td >Your Invoice No	</td><td >	' . $invi . '	</td>	
			</tr><tr>		
			<td >Status	</td><td >Consignment Booked.</td>		
				</td>
								</tr>
							</table>	
			<br>
			<br>';
        $to_name = array();
        $to_email = array();

        if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
            //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
            array_push($to_email, get_client_email($conn, $consignor), get_client_email($conn, $consignee));
            array_push($to_name, get_client_name($conn, $consignor), get_client_name($conn, $consignee));

            $mail = sendAttachments($to_name, $to_email, 'Consignment Booking Notification', $path, $image, $msg, $name);

            //echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst'); 

        }
        /*if(!empty(get_client_email($conn,$consignor))){
					$mail = sendAppMail(get_client_name($conn,$consignor),get_client_email($conn,$consignor), 'Consignment Booking Notification | '.$grn_no.' To {'.get_client_name($conn,$consignee).'}', $msg); 
			}
			if(!empty(get_client_email($conn,$consignee))){
					$mail = sendAppMail(get_client_name($conn,$consignee),get_client_email($conn,$consignee), 'Consignment Booking Notification | '.$grn_no.' To {'.get_client_name($conn,$consignee).'}', $msg); 
			}*/


        //*Send Invoice Instanly
        if ($mode_of_consignment == '3' || $mode_of_consignment  == '4') {
            if ($mode_of_consignment == '3') { //Pay at Booking 

                //$check_partywise_frq = checkPartyWiseFrequency($conn, $consignor); // Check Frequency set or not
                // if ($check_partywise_frq == 0) { // Frequncy is Set
                //     //Invoice Sent as per frequency
                //     echo "Frequency is Set";
                // } else {

                //     //Other Process Goes here
                //     $check_restricted = check_invoice_restricted($conn, $consignor);
                //     if ($check_restricted == 0) {

                //         //Need to createPayment Link


                //         //End Payment Link
                //         $msg = '<p style="line-height: 24px; margin-bottom:15px;">
				// 						Thank You for Your Order On <a href = "https://staging.graciousexpress.com" >Elite Wave 360</a> on ' . $grn_date . '! <br>
				// 						Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
				// 						<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
				// 						<tr>
				// 						<td >GRN No	</td><td >' . $grn_no . '</td>
				// 						</tr><tr>	
				// 						<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
				// 						</tr>
				// 						<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
				// 						<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
				// 						<tr>		
				// 						<td >Status	</td><td >Consignment Booked.</td>		
				// 							</td>
				// 											</tr>
				// 										</table>	
				// 						<br>
				// 						<br>';

                //         $to_name = array();
                //         $to_email = array();

                //         if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                //             //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)

                //             array_push($to_email, get_client_email($conn, $consignor), get_client_email($conn, $consignee));

                //             array_push($to_name, get_client_name($conn, $consignor), get_client_name($conn, $consignee));

                //             $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $download_path, $image, $msg, $name);



                //             //Need to Send Payment Link to User

                //         }
                //     } else {

                //         //echo "Restricted Client";
                //     }
                // }
            } else { // Cash on Delivery

                // $check_partywise_frq = checkPartyWiseFrequency($conn, $consignee); // Check Frequency set or not
                // if ($check_partywise_frq == 0) { // Frequncy is Set
                //     //Invoice Sent as per frequency
                //     echo "Frequency is Set";
                // } else {
                //     $outstanding = SetOutStandingInfo($conn, $consignee, $total); //Set Outstanding For COD

                //     $check_restricted = check_invoice_restricted($conn, $consignee);
                //     if ($check_restricted == 0) {

                //         //Need to createPayment Link


                //         //End Payment Link

                        $msg = '<p style="line-height: 24px; margin-bottom:15px;">
										Thank You for Your Order On <a href = "https://staging.graciousexpress.com" >Elite Wave 360</a> on ' . $grn_date . '! <br>
										Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
										<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
										<tr>
										<td >GRN No	</td><td >' . $grn_no . '</td>
										</tr><tr>	
										<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
										</tr>
										<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
										<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
										<tr>		
										<td >Status	</td><td >Consignment Booked</td>		
											</td>
															</tr>
														</table>	
										<br>
										<br>';

                        $to_name = array();
                        $to_email = array();

                        if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                            //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)

                            array_push($to_email, get_client_email($conn, $consignee), get_client_email($conn, $consignor));
                            array_push($to_name, get_client_name($conn, $consignee), get_client_name($conn, $consignor));



                            $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $download_path, $image, $msg, $name);
                        }
                //     } else {

                //         //echo "Restricted Client";

                //     }
                // }
            }
        } else {

            //Payment Mode 1 and 2

            if ($mode_of_consignment == 1) { // To Pay

                //echo "Consignee";
                $outstanding = SetOutStandingInfo($conn, $consignee, $total);
            } else { // By Sender

                //echo "Consignor";
                $outstanding = SetOutStandingInfo($conn, $consignor, $total);
            }
        }
        //*End 


        $out_put['result'] = 1;
        $out_put['data'] = $grn_no;
    } else {
        $out_put['result'] = "0";
    }

    echo json_encode($out_put);
}

if ($form_name == "edit_consignment_details") {
    $out_put = array();
    extract($_POST);
    //var_dump($_POST);
    $tables = get_trans_table_name($conn, $grn_date);
    $get_m_y = explode('_', $tables[0]);
    $month = $get_m_y[1];
    $year = $get_m_y[2];

    $consignorquery = "select * from client where client_id='$consignor'";
    $consignorresult = mysqli_query($conn, $consignorquery);
    $consignorrow = mysqli_fetch_array($consignorresult);

    $address1 = $consignorrow['address1'];
    $address2 = $consignorrow['address2'];
    $city = $consignorrow['city'];
    $pincode = $consignorrow['pincode'];
    $state = $consignorrow['state'];
    $phone = $consignorrow['phone'];
    $gst_no = $consignorrow['gst_no'];

    $consigneequery = "select * from client where client_id='$consignee'";
    $consigneeresult = mysqli_query($conn, $consigneequery);
    $consigneerow = mysqli_fetch_array($consigneeresult);

    $con_address1 = $consigneerow['address1'];
    $con_address2 = $consigneerow['address2'];
    $con_city = $consigneerow['city'];
    $con_state = $consigneerow['state'];
    $con_pincode = $consigneerow['pincode'];
    $con_phone = $consigneerow['phone'];
    $con_gst = $consigneerow['gst_no'];
    $grn_no = $_POST['grn_no'];
    $ftl_type = $_POST['truck_type'];

    $query = "update $tables[0] set mode_of_transportation='" . $mode_of_trasport . "',ftl_type = '$ftl_type',origin='" . $origin . "',destination='" . $destination . "',mode_of_consignment='" . $mode_of_consignment . "',consigner='" . $consignor . "',address1='$address1',address2='$address2',city='$city',pincode='$pincode',state='$state',phone='$phone',gst_no='$gst_no',consignee='$consignee',con_address1='$con_address1',con_address2='$con_address2',con_city='$con_city',con_state='$con_state',con_pincode='$con_pincode',con_phone='$con_phone',con_gst_no='" . $con_gst . "',goods_dedared_value='$goods_dedared_value',octroi='$octroi',dimension1='$dimension1',dimension2='$dimension2',dimension3='$dimension3',frieght_rate='$frieght_rate',frieght_amount='$frieght_amount',`loading_unloading_rate`='" . $loading_unload_rate . "',`loading_unloading_amount`='" . $loading_unload_chrg . "',`crane_fork_lift_rate`='" . $crane_forklift_rate . "',`crane_fork_lift_amount`='" . $crane_forklift_chrg . "',cod_rate='$cod_rate',cod_amount='$cod_amount',fov_rate='$fov_rate',fov_amount='$fov_amount',doc_charges='" . $doc_rate . "',doc_amount='" . $doc_amount . "',cartage_rate='$cartage_rate',cartage_amount='$cartage_amount',labour_handling_rate='" . $labour_rate . "',labour_handling_amount='" . $labour_amount . "',octroi_rate='$octroi_rate',octroi_amount='$octroi_amount',other_charge_rate='" . $other_rate . "',other_charge_amount='" . $other_amount . "',gst_rate='$gst_rate',gst_amount='$gst_amount',total='$total',
    paid_amount = '0', balance = '$total',paid_status = '0' ,total_words='" . $amount_in_words . "',note1='$note1',note2='$note2',truck='" . $vehicle_no . "',consigner_signature='" . $signature . "',updated_at = '" . $updated_at . "',updated_by ='" . $updated_by . "' where transaction_id='$edit_id'";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));

    //Update Packages 
    for ($up_p = 0; $up_p < count($_POST['no_of_pkg']); $up_p++) {
        $update_q = "UPDATE $tables[2] set `no_of_pkge`='" . $_POST['no_of_pkg'][$up_p] . "',
		`type_of_pkge`='" . $_POST['type_of_pkg'][$up_p] . "',`party_invoice_no`='" . $_POST['party_invoice'][$up_p] . "',`said_contents`='" . $_POST['content'][$up_p] . "',`qty`='" . $_POST['qty'][$up_p] . "',`gross_weight`='" . $_POST['gross'][$up_p] . "',`charged_weight`='" . $_POST['charged'][$up_p] . "',`updated_by`='" . $updated_by . "',`updated_at`='" . $updated_at . "' WHERE transaction_id = '" . $edit_id . "' ";

        $package[] = $_POST['qty'][$up_p];  // 2 old to new 4

        $pkg_name[] = $_POST['type_of_pkg'][$up_p]; // 2 to 3

    }

    // Remove Old Qrcode 
    $dir = 'qrcode/';
    $path = "qrcode";

    $files = scandir($path);

    // $grn_no ='Soar00001';

    $uppercase_grn = strtoupper($grn_no);

    foreach ($files as $file) {

        $filename =  substr($file, 0, 9);
        if (strpos($uppercase_grn, $filename) !== false) {
            //file found
            //  echo $dir.$file;
            //  echo "<br>";
            unlink($dir . $file);
            //echo "file_found";
        }
    }


    //End Remove Old Qrcode

    //Barcode Start
    //require 'vendor/autoload.php'; For Barcode
    include('libs/phpqrcode/qrlib.php');
    $result_bar = [];

    foreach ($pkg_name as $index => $val) {
        $result_bar[$val] = ($result_bar[$val] ?? 0) + $package[$index];
    }
    $package_type1 = (array_keys($result_bar));
    $packge_qty = (array_values($result_bar));
    //$redColor = [0, 0, 0];
    //$generator = new Picqer\Barcode\BarcodeGeneratorJPG();
    $name = $grn_no;
    //var_dump($qty);

    //$rate = 10;

    foreach ($packge_qty as $key => $val) {
        $get_qty = $val;
        //var_dump($get_qty);
        //echo "KEY".$key. "value". $val;
        if (array_key_exists($key, $package_type1)) {
            $get_package = $package_type1[$key];
            //var_dump($get_package);

            switch ($get_package) {
                case "1":
                    $pack_name = "CBX";
                    break;
                case "2":
                    $pack_name = "PBG";
                    break;
                case "3":
                    $pack_name = "ROL";
                    break;
                case "5":
                    $pack_name = "SHT";
                    break;
                case "6":
                    $pack_name = "BDL";
                    break;
                case "7":
                    $pack_name = "CVR";
                    break;
                case "8":
                    $pack_name = "PBL";
                    break;
                case "9":
                    $pack_name = "CAN";
                    break;
                case "10":
                    $pack_name = "BOX";
                    break;
                case "11":
                    $pack_name = "BAG";
                    break;
                case "12":
                    $pack_name = "MLD";
                    break;
                case "13":
                    $pack_name = "PKT";
                    break;
                case "14":
                    $pack_name = "CES";
                    break;
                case "15":
                    $pack_name = "CAT";
                    break;
                case "16":
                    $pack_name = "GRL";
                    break;
                case "17":
                    $pack_name = "P.B";
                    break;
                case "18":
                    $pack_name = "PRL";
                    break;
                default:
                    $pack_name = "No Package Type Found!";
            }

            //$productData = "098{$get_qty}10{$name}55{$rate}";
            $tempDir = 'qrcode/';
            $productData = strtoupper($name);
            $j = 1;
            for ($i = 0; $i < $get_qty; $i++) {
                $change_index[$j] = $i + 1;
                $names =  $productData . $pack_name . '-00' . $change_index[$j];
                $contents = 'https://staging.graciousexpress.com/web/testqrcode.php?grn_no=' . $name . '&grn_date=' . $grn_date;
                //var_dump($names);
                //Barcode
                //file_put_contents('barcode/'.$names.'.jpg', $generator->getBarcode($names, $generator::TYPE_CODE_128,3,100,$redColor));

                //Qrcode
                QRcode::png($contents, $tempDir . '' . $names . '.png', QR_ECLEVEL_L, 5);

                $j++;
            }
        }
    }
    //Barcode End

    //End


    for ($k = 0; $k < count($_FILES["file_receipt"]["name"]); $k++) {
        $file_name = uniqid() . $_FILES["file_receipt"]["name"][$k];
        if (move_uploaded_file($_FILES["file_receipt"]["tmp_name"][$k], "invoice_image/" . $file_name)) { //images/

            $fr_query = "insert into $tables[1](transaction_id,attachment,created_at,created_by,status) values('$edit_id','$file_name','$created_at','$created_by','0')";
            $fr_result = mysqli_query($conn, $fr_query) or die(mysqli_error($conn));
        }
    }
    //$attachment_id = $_REQUEST['id'];
    $attachment_id = $_REQUEST['del_id'];
    //var_dump($attachment_id);
    $sql_delete = "delete from $tables[1] where attachment_id IN($attachment_id)";
    $del_image = mysqli_query($conn, $sql_delete);
    $del_q = mysqli_query($conn, "delete from $tables[2] where transaction_id='$edit_id'");

    for ($j = 0; $j < count($_POST['no_of_pkg']); $j++) {

        $f_query = "insert into $tables[2](transaction_id,no_of_pkge,type_of_pkge,party_invoice_no,said_contents,qty,gross_weight,charged_weight,created_at,created_by,status) values('" . $edit_id . "','" . $no_of_pkg[$j] . "','" . $type_of_pkg[$j] . "','" . $party_invoice[$j] . "','" . $content[$j] . "','" . $qty[$j] . "','" . $gross[$j] . "','" . $charged[$j] . "','" . $created_at . "','" . $created_by . "','0')";
        $f_result = mysqli_query($conn, $f_query) or die(mysqli_error($conn));
    }

    //*Start Invoice 

    $check_inv_no_avlble = "select * from $tables[0] where transaction_id= '$edit_id'";
    $inv_res = mysqli_query($conn, $check_inv_no_avlble);
    $fetch_det = mysqli_fetch_assoc($inv_res);
    $check_inv_no = $fetch_det['invoice_no'];

    if ($check_inv_no != 'NULL') {

        $directory = 'digital_invoice/';
        $invoice_url = "https://staging.graciousexpress.com/web/gst_invoice_page.php?month=" . $month . "&year=" . $year . "&id=" . $edit_id . "&invoice_no=" . $unique_invoice_no . "";
        $invoice_file_name = $month . "_" . $year . "_" . $edit_id . "invoice";
        $download_path =  $directory . $invoice_file_name . '.pdf';
        $file_inv_download = curl_init($invoice_url);
        curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($file_inv_download, CURLOPT_REFERER, true);
        curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);

        $store_inv = curl_exec($file_inv_download);
        curl_close($invoice_url);
        $save_inv_file = file_put_contents($download_path, $store_inv);
    }

    //*End Invoice

    //Update Outstanding
    if ($mode_of_consignment == '1') { // To Pay

        $update_OutStanding = UpdateOutStandingInfo($conn, $consignee, '1');
    } else { //TBB

        $update_OutStanding = UpdateOutStandingInfo($conn, $consignor, '2');
    }

    //End

    if ($update_OutStanding == 1) {

        $out_put['result'] = 1;
        //$out_put['data'] = "delete from $tables[2] where transaction_id='$edit_id'";			
    } else {
        $out_put['result'] = "0";
    }

    echo json_encode($out_put);
}
if ($form_name == "add_eway_bill") {

    $id = $_POST['attachment_id'];
    $table_name = $_POST['table_name'];
    $issue_date = $_POST['issue_date'];
    $expire_date = $_POST['expire_date'];

    $eway_bill_no = $_POST['eway_bill_no'];

    foreach ($_FILES["attachment"]["error"] as $key => $error) {
        if ($error == UPLOAD_ERR_OK) {
            $name = $eway_bill_no . $id . $_FILES['attachment']['name'][$key];
            $target_dir = "eway/";
            if (move_uploaded_file($_FILES['attachment']['tmp_name'][$key], $target_dir . $name)) {
                $fr_query = "insert into $table_name (transaction_id,attachment,eway_bill_no,issue_date,eway_status,expire_date,created_at,created_by,status) values('$id','$name','$eway_bill_no','$issue_date','1','$expire_date','$created_at','$created_by','0')";
                $fr_result = mysqli_query($conn, $fr_query) or die(mysqli_error());
            }
        }
    }


    if ($fr_result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "add_company") {
    $comp_code = $_POST['comp_code'];
    $comp_name = $_POST['comp_name'];
    $contact_person = $_POST['contact_person'];
    $address1 = $_POST['address1'];
    $address2 = $_POST['address2'];
    $pan_no = $_POST['pan_no'];
    $auto_change_hours = $_POST['autochange_hours'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $mobile_no = $_POST['mobile_no'];
    $email = $_POST['email'];

    if ($_FILES['logo']['size'] != 0) {
        $logo = uniqid() . $_FILES["logo"]["name"];
        move_uploaded_file($_FILES["logo"]["tmp_name"], "images/" . $logo);
    }

    if ($_FILES['flag']['size'] != 0) {
        $flag = uniqid() . $_FILES["flag"]["name"];
        move_uploaded_file($_FILES["flag"]["tmp_name"], "images/" . $flag);
    }



    $query = "insert into company(company_name,company_code,contact_person,address1,address2,state,city,pincode,mobile_no,logo,flag,gst_no,pan_no,email,created_at,created_by,status) values('" . $comp_name . "','" . $comp_code . "','" . $contact_person . "','" . $address1 . "','" . $address2 . "','" . $state . "','" . $city . "','" . $pincode . "','" . $mobile_no . "','" . $logo . "','" . $flag . "','" . $gst_no . "','" . $pan_no . "','" . $email . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == "edit_company") {
    $comp_code = $_POST['comp_code'];
    $comp_name = $_POST['comp_name'];
    $contact_person = $_POST['contact_person'];
    $address1 = $_POST['address1'];
    $address2 = $_POST['address2'];
    $pan_no = $_POST['pan_no'];
    $auto_change_hours = $_POST['autochange_hours'];
    $edit_id = $_POST['edit_id'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $mobile_no = $_POST['mobile_no'];
    $email = $_POST['email'];
    $gst_no = $_POST['gst_no'];
    $edit_id = $_POST['edit_id'];
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['size'] != 0) {
        $logo = uniqid() . $_FILES["logo"]["name"];
        move_uploaded_file($_FILES["logo"]["tmp_name"], "images/" . $logo);
        $query = "update company set logo='" . $logo . "' where company_id='" . $edit_id . "'";
        $result = mysqli_query($conn, $query);
    }


    if (!empty($_FILES['flag']['name']) && $_FILES['flag']['size'] != 0) {
        $flag = uniqid() . $_FILES["flag"]["name"];
        move_uploaded_file($_FILES["flag"]["tmp_name"], "images/" . $flag);
        $query = "update company set flag='" . $flag . "' where company_id='" . $edit_id . "'";
        $result = mysqli_query($conn, $query);
    }


    $query = "update company set company_name='" . $comp_name . "',company_code='" . $comp_code . "',contact_person='" . $contact_person . "',address1='" . $address1 . "',address2='" . $address2 . "',state= '" . $state . "',city='" . $city . "',pincode='" . $pincode . "',mobile_no='" . $mobile_no . "',gst_no='" . $gst_no . "',pan_no='" . $pan_no . "',email='" . $email . "',updated_at='" . $updated_at . "',updated_by='" . $updated_at . "' where company_id='" . $edit_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == "request_for_new_pickup_for_existing_client") {
    extract($_POST);
    $id_q = mysqli_query($conn, "select max(pickup) as pickup from pickup ");
    $id_r = mysqli_fetch_array($id_q);
    $pickup = $id_r['pickup'] + 1;
    $pickup_ref_id = "RFP/" . sprintf("%'.05d\n", $pickup);


    $query = "INSERT INTO `pickup`(`pickup_ref_id`, `pickup`, `consignee`, `consignor`,`origin`, `destination`, `mode`, `no_of_package`, `package`, `approx_weight`, `created_at`, `created_by`, `description`, `status`) VALUES ('$pickup_ref_id','$pickup','$created_by','$consignor','$origin','$destination','$mode','$no_of_package','$package','$approx_weight','$created_at','$created_by','$description','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}



if ($form_name == "change_grn_status") {
    $c_date = date('d-m-Y H:i:s A');
    $grn_id = implode(",", $_POST['grn_id']);
    $status = $_POST['status'];
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $mode = $_POST['mode'];
    $remarks = $_POST['remarks'];
    $client_id = implode(",", $_POST['client_id']);
    $sheetq = "SELECT max(sheet_id) as id FROM transaction_status";
    $sheetres = mysqli_query($conn, $sheetq) or die(mysqli_error($conn));
    $sheetr = mysqli_fetch_array($sheetres);
    $sheet_id = $sheetr['id'] + 1;
    $sheet_no = "SN/" . sprintf("%04d", $sheet_id);

    $insq1 = "INSERT INTO `transaction_status`(`sheet_id`,`sheet_no`, `origin`, `destination`, `mode`,`remarks`, `status`, `created_at`, `created_by`) VALUES ('$sheet_id','$sheet_no','$origin','$destination','$mode','$remarks','$status','$c_date','$created_by')";
    $insr1 = mysqli_query($conn, $insq1);


    $query2 = "SELECT * FROM transaction_tbls";
    $result2 = mysqli_query($conn, $query2) or die(mysqli_error($conn));
    while ($row2 = mysqli_fetch_assoc($result2)) {

        $query = "select * from transaction_" . $row2['table_name'] . " where grn_id IN ($grn_id) and client_id IN($client_id)";
        // exit();
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {

            while ($row = mysqli_fetch_array($result)) {

                $consigners = $row['consigner'];
                $grn_number = $row['grn_no'];
                //Get client details
                $client_det = get_client($conn, $consigners);
                $contact_no = $client_det['contact_no'];
                $client_name = $client_det['client_company_name'];

                $insq = "INSERT INTO `transaction_status_log`(`sheet_id`,`grn_id`, `grn_no`, `from_status`, `to_status`,`client_id`,`updated_at`, `updated_by`) VALUES ('$sheet_id','" . $row['grn_id'] . "','" . $row['grn_no'] . "','" . $row['status'] . "','$status','" . $row['client_id'] . "','$created_at','$created_by')";
                $insr = mysqli_query($conn, $insq);

                $query1 = "update transaction_" . $row2['table_name'] . " set status='$status' where grn_id='" . $row['grn_id'] . "' and client_id='" . $row['client_id'] . "'";
                $result1 = mysqli_query($conn, $query1);

                if($result1){
             //Start Message Part Every Status Change

                // echo "Message need to send";

                // if ($contact_no != '') {

                //     if (strstr($contact_no, '+91')) {
                //         $phone  =  $contact_no;
                //     } else {
                //         //echo "Text not found";
                //         $phone  =  "+91" . $contact_no;
                //     }

                //     $sid = constant("SID");

                //     $token = constant("Auth");

                //     $twilio = new Client($sid, $token);

                //     $link = 'https://staging.graciousexpress.com/tracking.php';
                //     $msg = "Dear ".$client_name.", \r\n Your Booking Status: " . get_trans_status($status) . " - Against Your GRN No: ".$grn_number. "\r\n You Can Track At ".$link;

                //     $message = $twilio->messages
                //         ->create(
                //             $phone, // to
                //             ["body" => $msg, "from" => "+17853776942"]
                //         );

                //  }

                //End Send OTP through SMS

                //End Message Part

                //*Send invoice

                $get_status_query = "select `total`,`balance`,`invoice_no`,`origin`,`destination`,`mode_of_consignment`,`transaction_id`,`grn_no`,`grn_date`,`consigner`,`consignee`,`status` from transaction_" . $row2['table_name'] . " where grn_id='" . $row['grn_id'] . "' and client_id='" . $row['client_id'] . "'";
                $result_query = mysqli_query($conn, $get_status_query);
                $get_result_query = mysqli_fetch_assoc($result_query);
                $get_status = $get_result_query['status'];
                $get_transaction_id = $get_result_query['transaction_id'];
                $consignor_name = $get_result_query['consigner'];
                $consignee_name = $get_result_query['consignee'];
                $origin_name = $get_result_query['origin'];
                $destination_name = $get_result_query['destination'];
                $grnn_no = $get_result_query['grn_no'];
                $grnn_date = $get_result_query['grn_date'];
                $mode_of_consignment = $get_result_query['mode_of_consignment'];
                $unique_invoice_no = $get_result_query['invoice_no'];
                $total = $get_result_query['total'];
                $balance = $get_result_query['balance'];

                //$mode_of_consignment;

                if ($get_status == '8') {
                    //echo "You are inside in get status";
                    
                    if($mode_of_consignment == '1' || $mode_of_consignment == '2') { //To pay-1 , By Sender-2

                        if ($mode_of_consignment == '1') {
                            $check_partywise_frq = checkPartyWiseFrequency($conn, $consignee_name); // Check Frequency set or not
                            if ($check_partywise_frq == 0) { // Frequncy is Set
                                //Invoice Sent as per frequency
                                echo "Frequency is Set";
                            } else {
                                $check_restricted = check_invoice_restricted($conn, $consignee_name);
                                if ($check_restricted == 0) {

                                    //Sent Payment Link to Consignee
                                    $get_client_details =  get_client($conn, $consignee_name);
                                    $company_name = $get_client_details['client_company_name'];
                                    $email = $get_client_details['email'];
                                    $phone = $get_client_details['contact_no'];
                                    $amount_array = array($balance);
                                    $get_transaction_id_arr = array($get_transaction_id);
                                    $grnn_date_array = array($grnn_date);
                                    //$total_array = array($total);
                                    $grnn_no_array = array($grnn_no);
                                    $unique_invoice_no_array = array($unique_invoice_no);

                                    $data = array(
                                        'transaction_id' => $get_transaction_id_arr,
                                        'company_name' => $company_name,
                                        'grn_date' => $grnn_date_array,
                                        'email' => $email,
                                        'phone' => $phone,
                                        'amount' => $amount_array,
                                        'grn_no' => $grnn_no_array,
                                        'invoice_no' => $unique_invoice_no_array,
                                        'client_id' => $consignee_name
                                    );

                                    $data_serialize = serialize($data);
                                    $link_wit_data = http_build_query(array('aParam' => $data_serialize));

                                    //End Sent Payment Link to Consignee

                                    //echo "Not Restricted";
                                    $dir = 'digital_invoice/';
                                    $pdf_file_name = $dir . $row2['table_name'] . "_" . $get_transaction_id . "invoice.pdf";

                                    //echo "pdf_file_name: ".$pdf_file_name;

                                    //Send Mail
                                    $msg = '<p style="line-height: 24px; margin-bottom:15px;">
										Thank You for Your Order On <a href = "https://graciousexpress.com" >Elite Wave 360</a> on ' . $grnn_date . '! <br>
										Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
										<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
										<tr>
										<td >GRN No	</td><td >' . $grnn_no . '</td>
										</tr><tr>	
										<td >GRN Date:	</td><td >	' . $grnn_date . '	</td>	
										</tr>
										<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . '</td>	</tr>	
										<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . '</td>	</tr>	
										<tr>		
										<td >Status	</td><td >Consignment Delivered Successfully.</td>		
											</td>
										</tr>
										</table>	
										<br>
										<br>
										Payment Link : <a href = "https://staging.graciousexpress.com/verify_paylink.php?data=' . urlencode($link_wit_data) . '" >Payment Link</a>';

                                    $to_name = array();
                                    $to_email = array();

                                    if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                        //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
                                        array_push($to_email, get_client_email($conn, $consignee_name), get_client_email($conn, $consignor_name));
                                        array_push($to_name, get_client_name($conn, $consignee_name), get_client_name($conn, $consignor_name));

                                        $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification With Payment Link', $pdf_file_name, $image, $msg, $name);

                                        //echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst'); 

                                    }
                                } else {
                                    //echo "Restricted";
                                }
                            }

                            //End
                        } else {
                            $check_partywise_frq = checkPartyWiseFrequency($conn, $consignor_name); // Check Frequency set or not
                            //echo $check_partywise_frq;
                            if ($check_partywise_frq == 0) { // Frequncy is Set
                                //Invoice Sent as per frequency
                                echo "Frequency is Set";
                            } else {

                                $check_restricted = check_invoice_restricted($conn, $consignor_name);
                                if ($check_restricted == 0) {

                                    //Sent Payment Link to Consignee
                                    $get_client_details =  get_client($conn, $consignor_name);
                                    $company_name = $get_client_details['client_company_name'];
                                    $email = $get_client_details['email'];
                                    $phone = $get_client_details['contact_no'];
                                    $amount_array = array($balance);
                                    $get_transaction_id_arr = array($get_transaction_id);
                                    $grnn_date_array = array($grnn_date);
                                    //$total_array = array($total);
                                    $grnn_no_array = array($grnn_no);
                                    $unique_invoice_no_array = array($unique_invoice_no);
                                    $data = array(
                                        'transaction_id' => $get_transaction_id_arr,
                                        'company_name' => $company_name,
                                        'grn_date' => $grnn_date_array,
                                        'email' => $email,
                                        'phone' => $phone,
                                        'amount' => $amount_array,
                                        'grn_no' => $grnn_no_array,
                                        'invoice_no' => $unique_invoice_no_array,
                                        'client_id' => $consignor_name
                                    );

                                    $data_serialize = serialize($data);
                                    $link_wit_data = http_build_query(array('aParam' => $data_serialize));


                                    //End Sent Payment Link to Consignee
                                    $dir = 'digital_invoice/';
                                    $pdf_file_name = $dir . $row2['table_name'] . "_" . $get_transaction_id . "invoice.pdf";

                                    //echo "pdf_file_name: ".$pdf_file_name;

                                    //Send Mail
                                    $msg = '<p style="line-height: 24px; margin-bottom:15px;">
											Thank You for Your Order On <a href = "https://graciousexpress.com" >Elite Wave 360</a> on ' . $grnn_date . '! <br>
											Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
											<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
											<tr>
											<td >GRN No	</td><td >' . $grnn_no . '</td>
											</tr><tr>	
											<td >GRN Date:	</td><td >	' . $grnn_date . '	</td>	
											</tr>
											<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . '</td>	</tr>	
											<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . '</td>	</tr>	
											<tr>		
											<td >Status	</td><td >Consignment Delivered Successfully.</td>		
												</td>
																</tr>
															</table>	
											<br>
											<br>
											Payment Link : <a href = "https://staging.graciousexpress.com/verify_paylink.php?data=' . urlencode($link_wit_data) . '" >Payment Link</a>';
                                    $to_name = array();
                                    $to_email = array();

                                    if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                        //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
                                        array_push($to_email, get_client_email($conn, $consignor_name), get_client_email($conn, $consignee_name));
                                        array_push($to_name, get_client_name($conn, $consignor_name), get_client_name($conn, $consignee_name));

                                        $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification  With Payment Link', $pdf_file_name, $image, $msg, $name);

                                        //echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst'); 

                                    }
                                } else {

                                    //echo "Restricted";

                                }
                            }

                            //End
                        }
                    } else {
                        echo "Already Invoice Sent";
                    }
                } else {

                    //Status Report send through email
                    $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                            <b style="color:black;">Your Booking Status:  <span>' . get_trans_status($status) . ' </span> - </br>
                            Against Your GRN No: <span>' . $grn_number . ' </span></p></b></br>
                            <p>You Can Track At: <a href="https://staging.graciousexpress.com/tracking.php">Link</a></p>';

                    $to_name = (get_client_name($conn, $consigners));

                    $to_email = (get_client_email($conn, $consigners));
                    //print_r($to_email);

                    $mail = sendAppMail($to_name, $to_email, 'Booking Status', $msg1);
                    //End
                }
                //*End Send invoice
                    echo 1;
                }else{
                    echo 0;
                }
            }
        }
    }

    // if ($query1)
    //     echo 1;
    // else
    //     echo 0;
}


if ($form_name == "edit_change_grn_status") {

    $grn_id_arr = $_POST['grn_id'];
    $grn_id = implode(",", $_POST['grn_id']);
    $status = $_POST['status'];
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $mode = $_POST['mode'];
    $remarks = $_POST['remarks'];
    $sheet_id = $_POST['edit_id'];
    $del_ids = implode(",", $_POST['del_ids']);
    $del_ids_arr = explode(",", $del_ids);

    $insq1 = "update `transaction_status` set `status`='$status',remarks='$remarks' where sheet_id='$sheet_id'";
    $insr1 = mysqli_query($conn, $insq1);

    $oldq = "select * from `transaction_status_log` where sheet_id='$sheet_id'";
    $oldr = mysqli_query($conn, $oldq);
    if (mysqli_num_rows($oldr) > 0) {
        while ($oldrow = mysqli_fetch_array($oldr)) {
            $old_grn_id = $oldrow['grn_id'];
            if (in_array($old_grn_id, $grn_id_arr)) {

                $uq = "update `transaction_status_log` set to_status='$status' where id='" . $oldrow['id'] . "'";
                $ur = mysqli_query($conn, $uq);
            }
            if (in_array($old_grn_id, $del_ids_arr)) {

                $query2 = "SELECT * FROM transaction_tbls";
                $result2 = mysqli_query($conn, $query2) or die(mysqli_error($conn));
                while ($row2 = mysqli_fetch_assoc($result2)) {
                    $query = "select * from transaction_" . $row2['table_name'] . " where grn_id ='$old_grn_id'";
                    $result = mysqli_query($conn, $query);
                    if (mysqli_num_rows($result) > 0) {
                        $row = mysqli_fetch_array($result);

                        $query1 = "update transaction_" . $row2['table_name'] . " set status='" . $oldrow['from_status'] . "' where grn_id='$old_grn_id'";
                        $result1 = mysqli_query($conn, $query1);
                    }
                }

                $dq = "delete from `transaction_status_log` where id='" . $oldrow['id'] . "'";
                $dr = mysqli_query($conn, $dq);
            }
        }
    }
    if ($insr1)
        echo 1;
    else
        echo 0;
}


if ($form_name == 'add_user_consignment_form') {
    $out_put = array();
    extract($_POST);
    $id = $_POST['id'];
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];

    $tables = get_trans_table_name($conn, $grn_date);
    $get_m_y = explode('_', $tables[0]);
    $month = $get_m_y[1];
    $year = $get_m_y[2];
    if (!empty($_SESSION['company_id'])) {
        /*$query_code=mysqli_query($conn,"select * from client where client_id='".$_SESSION['company_id']."'");
			$r_code=mysqli_fetch_array($query_code);
			$query_max=mysqli_query($conn,"select * from transaction_log where client_id='".$_SESSION['company_id']."'");
			$r_max=mysqli_fetch_array($query_max);
			$id=$r_max['grn_id']+1;
			$billing_code = $r_code['billing_code'];
			$grn_no=$billing_code.sprintf("%05d",$id);*/
        $grn_no = $_POST['grn_no'];
    } else {
        // echo "select * from transaction_log where client_id=0";
        if (!empty($id)) {
            $grn_no = $_POST['grn_no'];
        } else {
            $query_max = mysqli_query($conn, "select * from transaction_log where client_id=0");
            $r_max = mysqli_fetch_array($query_max);
            $id = $r_max['grn_id'] + 1;
            $grn_no = "LA" . $_POST['grn_no1'];
        }
    }

    //$grn_no = $_POST['grn_no'];
    $grn_date = $_POST['grn_date'];
    $consignor = $_POST['consignor'];
    $consignee = $_POST['sel-consignee'];


    $consignorquery = "select * from client where client_id='$consignor'";
    $consignorresult = mysqli_query($conn, $consignorquery);
    $consignorrow = mysqli_fetch_array($consignorresult);

    $address1 = $consignorrow['address1'];
    $address2 = $consignorrow['address2'];
    $city = $consignorrow['city'];
    $pincode = $consignorrow['pincode'];
    $state = $consignorrow['state'];
    $phone = $consignorrow['phone'];
    $gst_no = $consignorrow['gst_no'];

    $consigneequery = "select * from client where client_id='$consignee'";
    $consigneeresult = mysqli_query($conn, $consigneequery);
    $consigneerow = mysqli_fetch_array($consigneeresult);

    $con_address1 = $consigneerow['address1'];
    $con_address2 = $consigneerow['address2'];
    $con_city = $consigneerow['city'];
    $con_state = $consigneerow['state'];
    $con_pincode = $consigneerow['pincode'];
    $con_phone = $consigneerow['phone'];
    $con_gst = $consigneerow['gst_no'];




    if ($_POST['air'] != '') { //*GR Copy Send to Consignor Without Payment Info

        $shipping_mode = $_POST['air'];
    } else if ($_POST['train'] != '') {

        $shipping_mode = $_POST['train'];
    } else if ($_POST['roadsurface'] != '') {

        if ($_POST['ftl'] != '') { //*FTL Quotation Send to Admin

            $shipping_mode = $_POST['ftl'];

            // if ($_POST['type1'] != '') {

            //     $ftl_type = $_POST['type1'];
            // } else if ($_POST['type2'] != '') {

            //     $ftl_type = $_POST['type2'];
            // } else if ($_POST['type3'] != '') {

            //     $ftl_type = $_POST['type3'];
            // } else if ($_POST['type4'] != '') {

            //     $ftl_type = $_POST['type4'];
            // } else if ($_POST['type5'] != '') {

            //     $ftl_type = $_POST['type5'];
            // } else if ($_POST['type6'] != '') {

            //     $ftl_type = $_POST['type6'];
            // } else if ($_POST['type7'] != '') {

            //     $ftl_type = $_POST['type7'];
            // } else if ($_POST['type8'] != '') {

            //     $ftl_type = $_POST['type8'];
            // } else if ($_POST['type9'] != '') {

            //     $ftl_type = $_POST['type9'];
            // } else if ($_POST['type10'] != '') {

            //     $ftl_type = $_POST['type10'];
            // } else {

            //     $ftl_type = $_POST['type11'];
            // }
            $ftl_type = $_POST['truck_type'];
        } else if ($_POST['ptl'] != '') {

            $shipping_mode = $_POST['ptl'];
        } else {

            $shipping_mode = $_POST['roadsurface'];
        }
    } else if ($_POST['roadexpress'] != '') {

        $shipping_mode = $_POST['roadexpress'];
    } else {

        $shipping_mode = $_POST['localdelivery'];
    }
    // }else if($_POST['ftl'] != ''){   

    // 	$shipping_mode = $_POST['ftl'];

    // }else if($_POST['ptl'] != ''){

    // 	$shipping_mode = $_POST['ptl'];

    // }

    if ($_POST['tobilled'] != '') {

        $pay_mode = $_POST['tobilled'];
    } else if ($_POST['topay'] != '') {

        $pay_mode = $_POST['topay'];
    } else if ($_POST['paid'] != '') { //*GR + Invoice Send to Consignor //Paid Replace to Pay at Booking

        $pay_mode = $_POST['paid'];
    } else {

        $pay_mode = $_POST['cod'];
    }

    //*Package Details

    $no_of_pkg = $_POST['package-qty'];

    $type_of_pkg = $_POST['package_type'];
    $party_invoice = $_POST['invoice'];
    $content = $_POST['contents'];
    $qty = $_POST['qty'];
    $gross = $_POST['gross_kg'];
    $charged = $_POST['charged_kg'];

    $goods_dedared_value = $_POST['declared_val'];
    $eway_number = $_POST['eway_number'];

    $frieght_weight = $_POST['weight1'];
    $frieght_rate = $_POST['rate'];
    $frieght_amount = $_POST['amount'];
    $loading_unloading_amount = $_POST['loading_unload_chrg'];
    $crane_fork_lift_amount = $_POST['crane_forklift_chrg'];
    $doc_amount = $_POST['doc_charges'];
    $fov_amount = $_POST['fov_charges'];
    $labour_amount = $_POST['labour-charges'];
    $other_amount = $_POST['other-charges'];
    $gst_amount = $_POST['gst'];
    $total = $_POST['total_payment'];
    $total_amount_word = $_POST['total_payment_in_words'];
    //$consignmet_weight1 = $_POST['weight1'];

    $query = "insert into $tables[0](grn_no,grn_id,grn_date,mode_of_transportation,ftl_type,origin,destination,mode_of_consignment,consigner,address1,address2,
				city,pincode,state,phone,gst_no,consignee,con_address1,con_address2,con_city,con_state,con_pincode,con_phone,
				con_gst_no,goods_dedared_value,octroi,dimension1,dimension2,dimension3,consignment_weight,frieght_rate,frieght_amount,loading_unloading_rate,
				loading_unloading_amount, crane_fork_lift_rate, crane_fork_lift_amount,cod_rate,cod_amount,fov_rate,fov_amount,doc_charges,
				doc_amount,cartage_rate,cartage_amount,labour_handling_rate,labour_handling_amount,octroi_rate,octroi_amount,other_charge_rate,
				other_charge_amount,gst_rate,gst_amount,total,paid_amount, balance, paid_status,total_words,note1,note2,truck,consigner_signature,client_id,created_at,created_by,status,eway_number) 
				values('" . $grn_no . "','" . $id . "','" . $grn_date . "','" . $shipping_mode . "','" . $ftl_type . "','" . $origin . "','" . $destination . "','" . $pay_mode . "','" . $consignor . "',
				'" . $address1 . "','" . $address2 . "','" . $city . "','" . $pincode . "','" . $state . "','" . $phone . "','" . $gst_no . "','" . $consignee . "','" . $con_address1 . "',
				'" . $con_address2 . "','" . $con_city . "','" . $con_state . "','" . $con_pincode . "','" . $con_phone . "','" . $con_gst . "','" . $goods_dedared_value . "',
				'" . $octroi . "','" . $dimension1 . "','" . $dimension2 . "','" . $dimension3 . "','" . $frieght_weight . "','" . $frieght_rate . "','" . $frieght_amount . "','" . $loading_unloading_rate . "','" . $loading_unloading_amount . "','" . $crane_fork_lift_rate . "','" . $crane_fork_lift_amount . "','" . $cod_rate . "',
				'" . $cod_amount . "','" . $fov_rate . "','" . $fov_amount . "','" . $doc_rate . "','" . $doc_amount . "','" . $cartage_rate . "','" . $cartage_amount . "',
				'" . $labour_rate . "','" . $labour_amount . "','" . $octroi_rate . "','" . $octroi_amount . "','" . $other_rate . "','" . $other_amount . "',
				'" . $gst_rate . "','" . $gst_amount . "','" . $total . "','0','" . $total . "','0','" . $total_amount_word . "','" . $note1 . "','" . $note2 . "','" . $vehicle_no . "',
				'" . $signature . "','" . $consignor . "','" . $created_at . "','" . $created_by . "','1','" . $eway_number . "')";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));
    $transaction_id = mysqli_insert_id($conn);

    for ($k = 0; $k < count($_FILES["file_receipt"]["name"]); $k++) {
        $file_name = uniqid() . $_FILES["file_receipt"]["name"][$k];
        if (move_uploaded_file($_FILES["file_receipt"]["tmp_name"][$k], "invoice_image/" . $file_name)) { //images/


            $fr_query = "insert into $tables[1](transaction_id,attachment,created_at,created_by,status) values ('$transaction_id','$file_name','$created_at','$created_by','0')";
            $fr_result = mysqli_query($conn, $fr_query) or die(mysqli_error($conn));
            $attachment_id = mysqli_insert_id($conn);
        }
    }
    for ($j = 0; $j < count($_POST['package-qty']); $j++) {


        //$countss[] = $qty[$j];

        $f_query = "insert into $tables[2](transaction_id,no_of_pkge,type_of_pkge,party_invoice_no,said_contents,qty,gross_weight,charged_weight,created_at,created_by,status) values('" . $transaction_id . "','" . $no_of_pkg[$j] . "','" . $type_of_pkg[$j] . "','" . $party_invoice[$j] . "','" . $content[$j] . "','" . $qty[$j] . "','" . $gross[$j] . "','" . $charged[$j] . "','" . $created_at . "','" . $created_by . "','0')";
        $f_result = mysqli_query($conn, $f_query) or die(mysqli_error($conn));


        $package[] = $qty[$j];

        $pkg_name[] = $type_of_pkg[$j];

        //var_dump($pkg_name);



        //$total_pkg +=$_POST['no_of_pkg'];	
    }
    //Barcode Start
    //require 'vendor/autoload.php'; For Barcode
    include('libs/phpqrcode/qrlib.php');
    $result_bar = [];

    foreach ($pkg_name as $index => $val) {
        $result_bar[$val] = ($result_bar[$val] ?? 0) + $package[$index];
    }
    $package_type1 = (array_keys($result_bar));
    $packge_qty = (array_values($result_bar));
    //$redColor = [0, 0, 0];
    //$generator = new Picqer\Barcode\BarcodeGeneratorJPG();
    $name = $grn_no;
    //var_dump($qty);

    //$rate = 10;

    foreach ($packge_qty as $key => $val) {
        $get_qty = $val;
        //var_dump($get_qty);
        // "KEY".$key. "value". $val;
        if (array_key_exists($key, $package_type1)) {
            $get_package = $package_type1[$key];
            //var_dump($get_package);

            switch ($get_package) {
                case "1":
                    $pack_name = "CBX";
                    break;
                case "2":
                    $pack_name = "PBG";
                    break;
                case "3":
                    $pack_name = "ROL";
                    break;
                case "5":
                    $pack_name = "SHT";
                    break;
                case "6":
                    $pack_name = "BDL";
                    break;
                case "7":
                    $pack_name = "CVR";
                    break;
                case "8":
                    $pack_name = "PBL";
                    break;
                case "9":
                    $pack_name = "CAN";
                    break;
                case "10":
                    $pack_name = "BOX";
                    break;
                case "11":
                    $pack_name = "BAG";
                    break;
                case "12":
                    $pack_name = "MLD";
                    break;
                case "13":
                    $pack_name = "PKT";
                    break;
                case "14":
                    $pack_name = "CES";
                    break;
                case "15":
                    $pack_name = "CAT";
                    break;
                case "16":
                    $pack_name = "GRL";
                    break;
                case "17":
                    $pack_name = "P.B";
                    break;
                case "18":
                    $pack_name = "PRL";
                    break;
                default:
                    $pack_name = "No Package Type Found!";
            }

            //$productData = "098{$get_qty}10{$name}55{$rate}";
            $tempDir = 'qrcode/';
            $productData = strtoupper($name);
            $j = 1;
            for ($i = 0; $i < $get_qty; $i++) {
                $change_index[$j] = $i + 1;
                $names =  $productData . $pack_name . '-00' . $change_index[$j];
                $contents = 'https://staging.graciousexpress.com/web/testqrcode.php?grn_no=' . $name . '&grn_date=' . $grn_date;

                //var_dump($names);
                //Barcode
                //file_put_contents('barcode/'.$names.'.jpg', $generator->getBarcode($names, $generator::TYPE_CODE_128,3,100,$redColor));

                //Qrcode
                QRcode::png($contents, $tempDir . '' . $names . '.png', QR_ECLEVEL_L, 5);

                $j++;
            }
        }
    }
    //Barcode End


    $invoice_id = mysqli_insert_id($conn);

    if ($transaction_id) {

        if ($consignor != '') {

            $log_query = mysqli_query($conn, "select * from transaction_log where client_id='$consignor'");
            $log_count = mysqli_num_rows($log_query);
            if ($log_count == 0) {
                $query_log = mysqli_query($conn, "insert into transaction_log(transaction_id,attachment_id,invoice_id,grn_id,grn_no,client_id) values('$transaction_id','$attachment_id','$invoice_id','1','$grn_no','$consignor')") or die(mysqli_error($conn));
            } else {
                $query_log = mysqli_query($conn, "update transaction_log set transaction_id='$transaction_id',attachment_id='$attachment_id',invoice_id='$invoice_id',grn_id='$id',grn_no='$grn_no'  where client_id='$consignor'") or die(mysqli_error($conn));
            }
        } else {

            $query_log = mysqli_query($conn, "update transaction_log set transaction_id='$transaction_id',attachment_id='$attachment_id',invoice_id='$invoice_id',grn_id='$id',grn_no='$grn_no'  where client_id='$consignor'") or die(mysqli_error($conn));
        }


        //Barcode Started
        // require 'vendor/autoload.php';

        // $redColor = [0, 0, 0];

        // This will output the barcode as HTML output to display in the browser

        // $generator = new Picqer\Barcode\BarcodeGeneratorHTML();
        // echo $generator->getBarcode($name, $generator::TYPE_CODE_128);

        // $generator = new Picqer\Barcode\BarcodeGeneratorJPG();
        // 	foreach($type_of_pkg as $pkg_val){
        // 		$pack[] = $pkg_val;
        // 	}

        // 	var_dump($pack);
        // 	exit();
        // 	if($pack == "1"){
        // 		$package_name = 'Box';
        // 	}else if($pack == '2'){
        // 		$package_name = 'Bag';
        // 	}else if($pack == '5'){
        // 		$package_name = 'Roll';
        // 	}else if($pack == '6'){
        // 		$package_name = 'Sheet';
        // 	}else{
        // 		$package_name = 'Bundle';
        // 	}
        // }else if($no_of_pkg == 7){
        // 	$package_name = 'Bundle';
        // }else if($no_of_pkg == 8){
        // 	$package_name = 'Poly';
        // }else if($no_of_pkg == 9){
        // 	$package_name = 'Can';
        // }else if($no_of_pkg == 10){
        // 	$package_name = 'Can';
        // }else if($no_of_pkg == 11){
        // 	$package_name = 'Can';
        // }else if($no_of_pkg == 12){
        // 	$package_name = 'Can';
        // }else if($no_of_pkg == 13){
        // 	$package_name = 'Can';
        // }else if($no_of_pkg == 14){
        // 	$package_name = 'Can';
        // }

        // 	$pac_qty = array_sum($countss);

        // 	//var_dump($pac_qty);
        // 	$qty = $pac_qty;
        // 	$name = $grn_no;
        // 	$rate = 10;
        // 	$pkg_name = $package_name;
        // 	$productData = "098{$qty}10{$name}55{$rate}";

        // for($i=0; $i<$qty; $i++){

        // 	$names =  $productData.$pkg_name.'-00'.$i;
        // 	//var_dump($names);
        // 	file_put_contents('barcode/'.$names.'.jpg', $generator->getBarcode($names, $generator::TYPE_CODE_128,3,100,$redColor));

        // }
        //Barcode Started

        $invi = '';
        for ($i = 0; $i < count($party_invoice); $i++) {
            if ($party_invoice[$i] != "") {
                $invi .= $party_invoice[$i] . ',';
            }
        }

        $invi = rtrim($invi, ",");
        $url = "https://staging.graciousexpress.com/web/user_transaction_pdf.php?month=" . $month . "&year=" . $year . "&id=" . $transaction_id . "";
        $path = "transaction_pdf/" . $month . "_" . $year . "_" . $transaction_id . "transaction.pdf";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        $result_url = file_put_contents($path, $data);

        //*Invoice Section Start
        //Sequence Generation

        if ($shipping_mode != '7') {   //Check if not FTL


            if ($shipping_mode == '1' || $shipping_mode == '2' || $shipping_mode == '3') {
                $type = 'GST';
                // $sac = "996812";
                // $sac_text = '996812 - COURIER SERVICES';
            } else {
                $type = 'GTA';
                // $sac = "9965";
                // $sac_text = '9965 - Good Transport Agency Service';
            }
            //$conn1 = mysqli_connect("localhost","root","","bookconsignment");


            $grn_date_expl = explode("-", $grn_date);
            $cur_year = $grn_date_expl[2];

            $current_year = $cur_year;

            $previous_year =  $cur_year - 1;


            $p_y = substr($previous_year, 2);
            $c_y = substr($current_year, 2);

            $year_insert = $p_y . "-" . $c_y;

            $invoice_table = invoice_table_function($conn, $grn_date);

            $select = mysqli_query($conn, "select * from " . $invoice_table);
            $get_count = mysqli_num_rows($select);
            if ($get_count == 0) {
                $insert_data = "INSERT INTO " . $invoice_table . "(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('0','HRGST','$year_insert','GST','$created_at','$created_by'),('0','HRGTA','$year_insert','GTA','$created_at','$created_by')";
                //$insert_data .= "INSERT INTO ".$invoice_table."(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('1','HRGTA','$year_insert','GTA','$created_at','$created_by')"; 
                //$res = mysqli_multi_query($conn,$insert_data);
                $res = mysqli_query($conn, $insert_data);
                if ($res) {
                    $inv_query = "select * from trans_invoice_tbl" . $year . " where inv_type='$type'";
                    $inv_query_result = mysqli_query($conn, $inv_query);
                    $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                    $inv_seq = $inv_query_row['invoice_no'] + 1;
                    //print_r($inv_seq);
                    //$inv_seq = '100';
                    $inv_text = $inv_query_row['gst_text'];
                    $inv_year = $inv_query_row['gst_year'];
                    $sequence = sprintf('%05d', $inv_seq);
                    $unique_invoice_no = $inv_text . "/" . $sequence . "/" . $inv_year;
                    //print_r($unique_invoice_no);
                }
            } else {

                $inv_query = "select * from trans_invoice_tbl" . $year . " where inv_type='$type'";
                $inv_query_result = mysqli_query($conn, $inv_query);
                $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                $inv_seq = $inv_query_row['invoice_no'] + 1;
                //print_r($inv_seq);
                //$inv_seq = '100';
                $inv_text = $inv_query_row['gst_text'];
                $inv_year = $inv_query_row['gst_year'];
                $sequence = sprintf('%05d', $inv_seq);
                $unique_invoice_no = $inv_text . "/" . $sequence . "/" . $inv_year;
            }



            //Sequence Generation

            $directory = 'digital_invoice/';
            $invoice_url = "https://staging.graciousexpress.com/web/gst_invoice_page.php?month=" . $month . "&year=" . $year . "&id=" . $transaction_id . "&invoice_no=" . $unique_invoice_no . "";
            $invoice_file_name = $month . "_" . $year . "_" . $transaction_id . "invoice";
            $download_path =  $directory . $invoice_file_name . '.pdf';
            $file_inv_download = curl_init($invoice_url);
            curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($file_inv_download, CURLOPT_REFERER, true);
            curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
            $store_inv = curl_exec($file_inv_download);
            curl_close($invoice_url);
            $save_inv_file = file_put_contents($download_path, $store_inv);
            if ($save_inv_file) {
                $update = mysqli_query($conn, "update trans_invoice_tbl" . $year . " SET invoice_no = '$inv_seq', updated_by = '$updated_by', updated_at = '$updated_at' where inv_type = '$type'");

                $query_inv = "update $tables[0] set `invoice_no` = '$unique_invoice_no' where transaction_id ='$transaction_id'";
                $res = mysqli_query($conn, $query_inv);
            }
        }
        //*Invoice Section End

        $image = array();
        $img_query = mysqli_query($conn, "select * from $tables[1] where transaction_id ='" . $transaction_id . "'");
        if (mysqli_num_rows($img_query) > 0) {
            while ($img_result = mysqli_fetch_array($img_query)) {
                array_push($image, "invoice_image/" . $img_result['attachment']);
            }
        }
        //print_r($image);
        $msg = '<p style="line-height: 24px; margin-bottom:15px;">
						  
			Thank you for booking the consignment, please find the booking information and the attached GR copy for your reference below.					
			<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
			<tr>
			<td >GRN No	</td><td >' . $grn_no . '</td>
			</tr><tr>	
			<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
			</tr>
			<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
			<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
			<tr>		
			<td >Your Invoice No	</td><td >	' . $invi . '	</td>	
			</tr><tr>		
			<td >Status	</td><td >Consignment Booked	</td>		
				</td>
								</tr>
							</table>	
			<br>
			<br>';

        $to_name = array();
        $to_email = array();

        if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
            //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
            array_push($to_email, get_client_email($conn, $consignor), get_client_email($conn, $consignee));
            array_push($to_name, get_client_name($conn, $consignor), get_client_name($conn, $consignee));

            $mail = sendAttachments($to_name, $to_email, 'Consignment Booking Notification', $path, $image, $msg, $name);

            //echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst'); 

        }
        /*if(!empty(get_client_email($conn,$consignor))){
		$mail = sendAppMail(get_client_name($conn,$consignor),get_client_email($conn,$consignor), 'Consignment Booking Notification | '.$grn_no.' To {'.get_client_name($conn,$consignee).'}', $msg); 
}
if(!empty(get_client_email($conn,$consignee))){
		$mail = sendAppMail(get_client_name($conn,$consignee),get_client_email($conn,$consignee), 'Consignment Booking Notification | '.$grn_no.' To {'.get_client_name($conn,$consignee).'}', $msg); 
}*/
        //*Send Invoice Instanly
        if ($pay_mode == '3' || $pay_mode == '4') { // Pay at Booking || Cash on Delivery

            if ($pay_mode == '3') {  //Paid or Pay at Booking

                //echo "3";
                // 	$msg = '<p style="line-height: 24px; margin-bottom:15px;">
                // Thank You for Your Order On <a href = "https://graciousexpress.colanapps.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
                // Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
                // <table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
                // <tr>
                // <td >GRN No	</td><td >' . $grn_no . '</td>
                // </tr><tr>	
                // <td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
                // </tr>
                // <tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
                // <tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
                // <tr>		
                // <td >Status	</td><td >Consignment Booked.</td>		
                // 	</td>
                // 					</tr>
                // 				</table>	
                // <br>
                // <br>';

                // 	$to_name = array();
                // 	$to_email = array();

                // 	if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                // 		//sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
                // 		array_push($to_email, get_client_email($conn, $consignor), get_client_email($conn, $consignee));
                // 		array_push($to_name, get_client_name($conn, $consignor), get_client_name($conn, $consignee));

                // 		$mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $download_path, $image, $msg, $name);

                // 		//$mail = sendAttachments($to_name,$to_email, 'Consignment Invoice Notification',$attachments,$image ,$msg,$name); 


                // 		//echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst'); 

                // 	}

            } else {
                //echo "4";
                //$check_partywise_frq = checkPartyWiseFrequency($conn, $consignee); // Check Frequency set or not
                // if ($check_partywise_frq == 0) { // Frequncy is Set
                //     //Invoice Sent as per frequency
                //     echo "Frequency is Set";
                // } else {
                    //Invoice Sent Instantly if not restricted
                //     $check_restricted = check_invoice_restricted($conn, $consignee);

                //     if ($check_restricted == 0) {

                //         //Need to createPayment Link


                //         //End Payment Link
                        $msg = '<p style="line-height: 24px; margin-bottom:15px;">
								Thank You for Your Order On <a href = "https://graciousexpress.colanapps.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
								Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
								<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
								<tr>
								<td >GRN No	</td><td >' . $grn_no . '</td>
								</tr><tr>	
								<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
								</tr>
								<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
								<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
								<tr>		
								<td >Status	</td><td >Consignment Booked.</td>		
									</td>
													</tr>
												</table>	
								<br>
								<br>';

                        $to_name = array();
                        $to_email = array();

                        if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {

                            //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)

                            array_push($to_email, get_client_email($conn, $consignee), get_client_email($conn, $consignor));
                            array_push($to_name, get_client_name($conn, $consignee), get_client_name($conn, $consignor));

                            $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $download_path, $image, $msg, $name);
                        }
                //     } else {

                //         //echo "Restricted";

                //     }
                // }
            }
        }
        //*End  

        if ($pay_mode == '2' || $pay_mode == '3' || $pay_mode == '1') {            //Check Pay at Booking here

            if ($pay_mode == '3') {
                //$outstanding = SetOutStandingInfo($conn23,$consignor,$total); //Insert or Update Payment Details

                $get_client_details =  get_client($conn, $consignor);
                $company_name = $get_client_details['client_company_name'];
                $email = $get_client_details['email'];
                $phone = $get_client_details['contact_no'];

            	//Update Client OutStanding

                $outstanding = SetOutStandingInfo($conn, $consignor, $total); //Insert or Update Payment Details
            
                $data = array(
                    'transaction_id' => $transaction_id,
                    'company_name' => $company_name,
                    'grn_date' => $grn_date,
                    'email' => $email,
                    'phone' => $phone,
                    'amount' => $total,
                    'grn_no' => $grn_no,
                    'invoice_no' => $unique_invoice_no,
                    'client_id' => $consignor
                );

                $out_put['result'] = http_build_query(array('aParam' => $data));
            } else if($pay_mode == '2') {

                //Update Client OutStanding

                $outstanding = SetOutStandingInfo($conn, $consignor, $total); //Insert or Update Payment Details

                //End

                $out_put['result'] = 1;
                $out_put['data'] = $grn_no;
            }else{

                $outstanding = SetOutStandingInfo($conn, $consignee, $total);
                $out_put['result'] = 1;
                $out_put['data'] = $grn_no;
            }
        }else{
            $out_put['result'] = 1;
            $out_put['data'] = $grn_no;
        }
    }else {
        $out_put['result'] = 0;
    }

    echo json_encode($out_put);
}


if ($form_name == 'edit_ftl_consignment_details') {
    $out_put = array();
    $edit_id  = $_POST['edit_id'];
    $grn_id = $_POST['grn_id'];
    $grn_date = $_POST['grn_date'];
    $grn_no = $_POST['grn_no'];

    $tables = get_trans_table_name($conn, $grn_date);
    $get_m_y = explode('_', $tables[0]);
    $month = $get_m_y[1];
    $year = $get_m_y[2];


    $frieght_rate = $_POST['frieght_rate'];
    $frieght_amount = $_POST['frieght_amount'];
    $cod_rate = $_POST['cod_rate'];
    $cod_amount = $_POST['cod_amount'];
    $fov_rate = $_POST['fov_rate'];
    $fov_amount = $_POST['fov_amount'];
    $doc_rate = $_POST['doc_rate'];
    $doc_amount = $_POST['doc_amount'];
    $cartage_rate = $_POST['cartage_rate'];
    $cartage_amount = $_POST['cartage_amount'];
    $labour_rate = $_POST['labour_rate'];
    $labour_amount = $_POST['labour_amount'];
    $loading_unloading_rate = $_POST['loading_unload_rate'];
    $loading_unloading_amount = $_POST['loading_unload_chrg'];
    $crane_fork_lift_rate = $_POST['crane_forklift_chrg'];
    $crane_fork_lift_amount = $_POST['crane_forklift_chrg'];
    $other_amount = $_POST['other_amount'];
    $gst_amount = $_POST['gst_amount'];
    $total = $_POST['total'];
    $amount_in_words = $_POST['amount_in_words'];
    $vehicle_no = $_POST['vehicle_no'];
    $ftl_type = $_POST['truck_type'];
    //$status = 2;


    $query = "update $tables[0] set frieght_rate='" . $frieght_rate . "',frieght_amount='" . $frieght_amount . "',loading_unloading_rate='" . $loading_unloading_rate . "',loading_unloading_amount = '" . $loading_unloading_amount . "',crane_fork_lift_rate='" . $crane_fork_lift_rate . "',crane_fork_lift_amount='" . $crane_fork_lift_amount . "',cod_rate='" . $cod_rate . "',cod_amount='" . $cod_amount . "',fov_rate='" . $fov_rate . "',fov_amount='" . $fov_amount . "',doc_charges='" . $doc_rate . "',doc_amount='" . $doc_amount . "',cartage_rate='" . $cartage_rate . "',cartage_amount='" . $cartage_amount . "',labour_handling_rate='" . $labour_rate . "',labour_handling_amount='" . $labour_amount . "',octroi_rate='" . $octroi_rate . "',octroi_amount='" . $octroi_amount . "',other_charge_rate='" . $other_rate . "',other_charge_amount='" . $other_amount . "',gst_rate='" . $gst_rate . "',gst_amount='" . $gst_amount . "',total='" . $total . "',paid_amount='0',balance='" . $total . "',paid_status='0',total_words='" . $amount_in_words . "',note1='" . $note1 . "',note2='" . $note2 . "',truck='" . $vehicle_no . "',consigner_signature='" . $signature . "',updated_at = '" . $updated_at . "', updated_by = '" . $updated_by . "' where transaction_id='$edit_id'";

    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));

    $check_inv_no_avlble = "select * from $tables[0] where transaction_id= '$edit_id'";
    $inv_res = mysqli_query($conn, $check_inv_no_avlble);
    $fetch_det = mysqli_fetch_assoc($inv_res);
    //print_r($fetch_det);

    $check_inv_no = $fetch_det['invoice_no'];
    // print_r($check_inv_no);
    // echo $check_inv_no;
    $mode_of_consignment = $fetch_det['mode_of_consignment'];
    // $grn_date = $fetch_det['grn_date'];
    // $grn_no = $fetch_det['grn_no'];
    $consignor = $fetch_det['consigner'];
    $consignee = $fetch_det['consignee'];
    $origin = $fetch_det['origin'];
    $destination = $fetch_det['destination'];



    if ($check_inv_no == '') {

        //*Start Invoice
        //$transport_type = '7';
        $type = 'GTA';

        //$conn1 = mysqli_connect("localhost","root","","bookconsignment");


        $grn_date_expl = explode("-", $grn_date);
        $cur_year = $grn_date_expl[2];

        $current_year = $cur_year;
        $previous_year =  $cur_year - 1;

        $p_y = substr($previous_year, 2);
        $c_y = substr($current_year, 2);

        $year_insert = $p_y . "-" . $c_y;

        $invoice_table = invoice_table_function($conn, $grn_date);

        $select = mysqli_query($conn, "select * from " . $invoice_table);
        $get_count = mysqli_num_rows($select);
        if ($get_count == 0) {
            $insert_data = "INSERT INTO " . $invoice_table . "(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('0','HRGST','$year_insert','GST','$created_at','$created_by'),('0','HRGTA','$year_insert','GTA','$created_at','$created_by')";
            //$insert_data .= "INSERT INTO ".$invoice_table."(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('1','HRGTA','$year_insert','GTA','$created_at','$created_by')"; 
            //$res = mysqli_multi_query($conn,$insert_data);
            $res = mysqli_query($conn, $insert_data);
            if ($res) {
                $inv_query = "select * from trans_invoice_tbl" . $year . " where inv_type='$type'";
                $inv_query_result = mysqli_query($conn, $inv_query);
                $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                $inv_seq = $inv_query_row['invoice_no'] + 1;
                //print_r($inv_seq);
                //$inv_seq = '100';
                $inv_text = $inv_query_row['gst_text'];
                $inv_year = $inv_query_row['gst_year'];
                $sequence = sprintf('%05d', $inv_seq);
                $unique_invoice_no = $inv_text . "/" . $sequence . "/" . $inv_year;
                //print_r($unique_invoice_no);
            }
        } else {

            $inv_query = "select * from trans_invoice_tbl" . $year . " where inv_type='$type'";
            $inv_query_result = mysqli_query($conn, $inv_query);
            $inv_query_row = mysqli_fetch_assoc($inv_query_result);

            $inv_seq = $inv_query_row['invoice_no'] + 1;
            //print_r($inv_seq);
            //$inv_seq = '100';
            $inv_text = $inv_query_row['gst_text'];
            $inv_year = $inv_query_row['gst_year'];
            $sequence = sprintf('%05d', $inv_seq);
            $unique_invoice_no = $inv_text . "/" . $sequence . "/" . $inv_year;
        }

        //Sequence Generation

        $directory = 'digital_invoice/';
        $invoice_url = "https://staging.graciousexpress.com/web/gst_invoice_page.php?month=" . $month . "&year=" . $year . "&id=" . $edit_id . "&invoice_no=" . $unique_invoice_no . "";
        $invoice_file_name = $month . "_" . $year . "_" . $edit_id . "invoice";
        $download_path =  $directory . $invoice_file_name . '.pdf';
        $file_inv_download = curl_init($invoice_url);
        curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($file_inv_download, CURLOPT_REFERER, true);
        curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
        $store_inv = curl_exec($file_inv_download);
        curl_close($invoice_url);
        $save_inv_file = file_put_contents($download_path, $store_inv);
        if ($save_inv_file) {
            $update = mysqli_query($conn, "update trans_invoice_tbl" . $year . " SET invoice_no = '$inv_seq', updated_by = '$updated_by', updated_at = '$updated_at' where inv_type = '$type'");

            $query_inv = "update $tables[0] set `invoice_no` = '$unique_invoice_no' where transaction_id ='$edit_id'";
            $res = mysqli_query($conn, $query_inv);
        }
    } else {

        $directory = 'digital_invoice/';
        $invoice_url = "https://staging.graciousexpress.com/web/gst_invoice_page.php?month=" . $month . "&year=" . $year . "&id=" . $edit_id . "&invoice_no=" . $unique_invoice_no . "";
        $invoice_file_name = $month . "_" . $year . "_" . $edit_id . "invoice";
        $download_path =  $directory . $invoice_file_name . '.pdf';
        $file_inv_download = curl_init($invoice_url);
        curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($file_inv_download, CURLOPT_REFERER, true);
        curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
        $store_inv = curl_exec($file_inv_download);
        curl_close($invoice_url);
        $save_inv_file = file_put_contents($download_path, $store_inv);
    }
    //*End Invoice

    //*Send Invoice Instanly
    if ($mode_of_consignment == '3' || $mode_of_consignment  == '4') {
        if ($mode_of_consignment == '3') { //Pay at Booking 

            $check_partywise_frq = checkPartyWiseFrequency($conn, $consignor); // Check Frequency set or not
            if ($check_partywise_frq == 0) { // Frequncy is Set
                //Invoice Sent as per frequency
                echo "Frequency is Set";
            } else {

                //Other Process Goes here
                // $check_restricted = check_invoice_restricted($conn, $consignor);
                // if ($check_restricted == 0) {
                // 	$msg = '<p style="line-height: 24px; margin-bottom:15px;">
                // 					Thank You for Your Order On <a href = "https://graciousexpress.colanapps.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
                // 					Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
                // 					<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
                // 					<tr>
                // 					<td >GRN No	</td><td >' . $grn_no . '</td>
                // 					</tr><tr>	
                // 					<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
                // 					</tr>
                // 					<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
                // 					<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
                // 					<tr>		
                // 					<td >Status	</td><td >Consignment Booked.</td>		
                // 						</td>
                // 										</tr>
                // 									</table>	
                // 					<br>
                // 					<br>';

                // 	$to_name = array();
                // 	$to_email = array();

                // 	if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                // 		//sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)

                // 		array_push($to_email, get_client_email($conn, $consignor), get_client_email($conn, $consignee));

                // 		array_push($to_name, get_client_name($conn, $consignor), get_client_name($conn, $consignee));

                // 		$mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $download_path, $image, $msg, $name);



                // 		//Need to Send Payment Link to User

                // 	}
                // } else {

                // 	//echo "Restricted Client";
                // }
            }
        } else { // Cash on Delivery

            // $check_partywise_frq = checkPartyWiseFrequency($conn, $consignee); // Check Frequency set or not
            // if ($check_partywise_frq == 0) { // Frequncy is Set
            // 	//Invoice Sent as per frequency
            // 	echo "Frequency is Set";
            // } else {
            // 	$outstanding = SetOutStandingInfo($conn, $consignee, $total); //Set Outstanding For COD

            //$check_restricted = check_invoice_restricted($conn, $consignee);
            //if ($check_restricted == 0) {
                $msg = '<p style="line-height: 24px; margin-bottom:15px;">
									Thank You for Your Order On <a href = "https://graciousexpress.colanapps.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
									Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email. 				
									<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
									<tr>
									<td >GRN No	</td><td >' . $grn_no . '</td>
									</tr><tr>	
									<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
									</tr>
									<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
									<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
									<tr>		
									<td >Status	</td><td >Consignment Booked</td>		
										</td>
														</tr>
													</table>	
									<br>
									<br>';

                $to_name = array();
                $to_email = array();

                if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                    //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)

                    array_push($to_email, get_client_email($conn, $consignee), get_client_email($conn, $consignor));
                    array_push($to_name, get_client_name($conn, $consignee), get_client_name($conn, $consignor));



                    $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $download_path, $image, $msg, $name);

                    //$mail = sendAttachments($to_name,$to_email, 'Consignment Invoice Notification',$attachments,$image ,$msg,$name); 


                    //echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst'); 

                }
            // } else {

            //     //echo "Restricted Client";

            // }
            //}
        }
    } else {

        //Payment Mode 1 and 2

        if ($mode_of_consignment == 1) { // To Pay

            //echo "Consignee";
            $outstanding = SetOutStandingInfo($conn, $consignee, $total);
        } else { // By Sender

            //echo "Consignor";
            $outstanding = SetOutStandingInfo($conn, $consignor, $total);
        }
    }
    //*End 

    if ($result) {
        $out_put['result'] = 1;
        $out_put['data'] = $grn_no;
    } else {
        $out_put['result'] = "0";
    }
    echo json_encode($out_put);
}
if ($form_name == 'barcode_retrive') {
    // echo "hello";
    $serarch_grn = strtoupper($_POST['search_grn_no']);
    $table_barcode = '';
    function getBarcodeImages($serarch_grn)
    {
        $barcode_dir = 'qrcode/';

        $get_exact_files = preg_grep('~^' . $serarch_grn . '.*\.png$~', scandir($barcode_dir));

        $array_new = array_values($get_exact_files);
        $count_q = count($array_new);

        if($count_q){

            $grn_no = substr($array_new[0], 0, 9);
        // print_r($get_exact_files);

        $s = 1;

        foreach ($array_new as $barcode_images) {
            //var_dump($barcode_images);
            $file =  $barcode_images;
            $ext = pathinfo($file, PATHINFO_FILENAME);

            echo "<div class='g-card1'>
            <div class='card-img'>
                <div class='card-title text-center'>
                    <img src='https://staging.graciousexpress.com/web/images/gracious.png' class='g-img' >
                </div>
            </div>
        <div class='g-card'>
            <div class='col-md-8  subdiv'>

                <div class='detail'>
                    <p><b> GRN No :</b> $grn_no</p>
                    <p><b> Company Address :</b> BASEMENT AND GROUND FLOOR, PLOT NO. 68,PACECITY- I, SECTOR 37, Gurgaon, Haryana, 122001</p>
                    <p><b> Phone No :</b> +91 11 41513422 / 2355 4355</p>
                    <p><b> GST :</b> 07AUIPM7033M1Z8</p>
                    <p><b> Email:</b> info@graciousexpress.com</p>
                </div>
            </div>
            <div class='qrcode subdivv col-md-4 '>
            <div class='qr-code' style='border: 1px solid #c7c7c7;'>
                <img src=$barcode_dir$barcode_images  class='qr_img'/>
                <p id='cbx'>$ext</p>
            </div>
            <p id='div'>$s/$count_q</p>
            </div>
            
           </div>
        </div>";


            $s++;
        }

        }else{
            echo "0";
        }
        
    }
    getBarcodeImages($serarch_grn);
}

if ($form_name == 'pod_form') {
    extract($_FILES);
    $allowed_ex = array('jpg', 'png', 'jpeg');


    for ($pd = 0; $pd < count($_FILES["pod_file"]["name"]); $pd++) {
        $pod_file_name[] = $_FILES["pod_file"]["name"][$pd];
    }
    foreach ($pod_file_name as $key => $pod_file) {
        $pod_file = $_FILES['pod_file']['tmp_name'][$key];
        $pod_file_name1 = $_FILES['pod_file']['name'][$key];
        $ext[] = pathinfo($pod_file_name1, PATHINFO_EXTENSION);
        //print_r($ext[$key]);
        if (!in_array($ext[$key], $allowed_ex)) {
            $message['msg'] = 0;
        } else {
            $file_name_pod[] = $_FILES["pod_file"]["name"][$key];
            if (move_uploaded_file($_FILES["pod_file"]["tmp_name"][$key], "../pod_uploads/" . $pod_file_name1)) {

                $message['msg'] = 1;
            } else {

                $message['msg'] = 0;
            }
        }
    }


    $implode_pod_file = implode('@@', $file_name_pod);

    if ($implode_pod_file) {

        //$conn1 = mysqli_connect("localhost","root","","bookconsignment");
        //$implode_file = "insert into $tables[1](transaction_id,attachment,created_at,created_by,status) values('$edit_id','$file_name','$created_at','$created_by','0')";
        //$implode_file = "insert into pod_check(`screens`, `created`) values('$implode_pod_file','28-12-2021')";
        $implode_file = "INSERT INTO `pod_files`(`screens`, `created_at`, `created_by`,`status`)values('$implode_pod_file','$created_at','$created_by','1')";
        $pod_result = mysqli_query($conn, $implode_file) or die(mysqli_error($conn));
        $message['msg'] = 1;
    } else {
        $message['msg'] = 0;
    }

    echo $message['msg'];
}

if ($form_name == 'delete_pod_img') {
    $delete_pod_img = $_GET['delete_id'];
    $tbl_id = $_POST['tbl_id'];
    //$conn = mysqli_connect("localhost","root","","bookconsignment");
    $q = "select * from pod_files where md5(id) = '$tbl_id'";
    $sql = mysqli_query($conn, $q);

    $user_delete = $delete_pod_img;
    // print_r($user_delete);
    // exit();
    $newString = "";



    while ($row = mysqli_fetch_assoc($sql)) {

        $test_array = $row['screens'];

        $exploded = explode('@@', $test_array);
        //print_r($exploded);

        $counter = count($exploded);

        //print_r($counter);

        for ($x = 0; $x < $counter; $x++) {
            if ($user_delete != $exploded[$x]) {
                //print_r($exploded[$x]);
                $newString = $newString . "@@" . $exploded[$x];
                //print_r($newString);
                unlink('../pod_uploads/' . $user_delete);
            }
        }
        $sql = "UPDATE pod_files SET screens = '$newString' WHERE md5(id)='$tbl_id'";
        mysqli_query($conn, $sql);
    }
}

if ($form_name == 'pod_retrive') {
    $edit_id = $_POST['edit_id'];

    // echo $edit_id;
    // echo "<br>";
    for ($up = 0; $up < count($_FILES["pod_file"]["name"]); $up++) {
        $update_pod_file_name[] = $_FILES["pod_file"]["name"][$up];
    }

    $allowed_ex = array('jpg', 'png', 'jpeg');

    foreach ($update_pod_file_name as $update_key => $pod_file) {
        $update_pod_file = $_FILES['pod_file']['tmp_name'][$update_key];
        $update_pod_file_name1 = $_FILES['pod_file']['name'][$update_key];
        $update_ext[] = pathinfo($update_pod_file_name1, PATHINFO_EXTENSION);
        //print_r($ext[$key]);
        if (!in_array($update_ext[$update_key], $allowed_ex)) {
            $message['msg'] = 0;
        } else {
            $update_file_name_pod[] = $_FILES["pod_file"]["name"][$update_key];
            if (move_uploaded_file($_FILES["pod_file"]["tmp_name"][$update_key], "../pod_uploads/" . $update_pod_file_name1)) {

                $message['msg'] = 1;
            } else {

                $message['msg'] = 0;
            }
        }
    }

    $implode_pod = implode('@@', $update_file_name_pod);
    // echo $implode_pod;

    //$conn = mysqli_connect("localhost","root","","bookconsignment");
    $sql = "select * from pod_files where md5(id) ='$edit_id'";
    $res_sql = mysqli_query($conn, $sql);

    $row =  mysqli_fetch_assoc($res_sql);
    $old_data[] = $row['screens'];
    // print_r($old_data);
    foreach ($old_data as $data) {
        $oldd_data = $data;
        //print_r($oldd_data);
        $add_data = $oldd_data . "@@" . $implode_pod . "";
    }
    //echo "<pre>".$add_data."</pre>";
    $explode_new_data = explode('@@', $add_data);
    //print_r($explode_new_data);
    // echo "<br>";
    $remove_duplicates = array_unique($explode_new_data);
    //    print_r($remove_duplicates);
    //    echo "<br>";
    $update_new_images = implode('@@', $remove_duplicates);
    //echo $update_new_images;

    $update_pod_images = "update pod_files set screens = '$update_new_images' where md5(id) ='$edit_id'";
    $sql_query = mysqli_query($conn, $update_pod_images);
    if ($sql_query) {
        $message['msg'] = 1;
    } else {
        $message['msg'] =  0;
    }
    echo $message['msg'];
}

if ($form_name == 'change_user_pass') {
    $user_id = $_POST['user_id'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];
    if ($user_id != '') {
        if ($new_pass == $confirm_pass) {
            $query = mysqli_query($conn, "update `users` set `password` = '$new_pass' where md5(user_id) = '$user_id' ");
            if ($query) {
                echo 1;
            } else {
                echo 0;
            }
        }
    } else {
        echo 0;
    }
}

if ($form_name == 'cancel_booking_consignment') {

    $updated_at = date('d-m-Y');
    $cancelled_by = $_POST['logged_id'];
    $transaction_id = $_POST['transaction_id'];
    $table_names = $_POST['table_names'];
    $grn_no = $_POST['grn_no'];
    $remarks = $_POST['remarks'];
    $tble_array = explode('_', $table_names);
    $m = $tble_array[1];
    $y = $tble_array[2];
    $trans_table1 = "transaction_" . $m . "_" . $y;
    $trans_table2 = "transaction_images_" . $m . "_" . $y;
    $trans_table3 = "transaction_invoice_" . $m . "_" . $y;

    //select transaction table

    $query = "select * from $trans_table1 where transaction_id = '$transaction_id' and grn_no = '$grn_no' and booking_status = '' ";
    $res = mysqli_query($conn, $query);
    $count = mysqli_num_rows($res);

    if ($count > 0) {

        $rowe = mysqli_fetch_assoc($res);

        $check_inv_no = $rowe['invoice_no'];

        $consigner = $rowe['consigner'];
        $consignee = $rowe['consignee'];
        $grn_date = $rowe['grn_date'];
        $origin = $rowe['origin'];
        $destination = $rowe['destination'];
        $pay_mode = $rowe['mode_of_consignment'];

        $client_det = get_client($conn, $consigner);
        $contact_no = $client_det['contact_no'];
        $client_name = $client_det['client_company_name'];

        $upd_query = "UPDATE $trans_table1 SET `booking_status`='1',`remarks`='$remarks', `cancelled_by` = '$cancelled_by', `updated_at` = '$updated_at'  WHERE `grn_no` = '$grn_no' and `transaction_id` = '$transaction_id'";
        $res_q = mysqli_query($conn, $upd_query);
        if ($res_q) {


            //GRN Regenerate Replace old to new grn
            $url = "https://staging.graciousexpress.com/web/transaction_pdf.php?month=" . $m . "&year=" . $y . "&id=" . $transaction_id . "";
            $path = "transaction_pdf/" . $m . "_" . $y . "_" . $transaction_id . "transaction.pdf";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec($ch);
            curl_close($ch);
            $result_url = file_put_contents($path, $data);

            //End

            //Invoice Regenerate Replace old to new invoice

            if ($check_inv_no != 'NULL') {

                $directory = 'digital_invoice/';
                $invoice_url = "https://staging.graciousexpress.com/web/gst_invoice_page.php?month=" . $m . "&year=" . $y . "&id=" . $transaction_id . "&invoice_no=" . $unique_invoice_no . "";
                $invoice_file_name = $m . "_" . $y . "_" . $transaction_id . "invoice";
                $download_path =  $directory . $invoice_file_name . '.pdf';
                $file_inv_download = curl_init($invoice_url);
                curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($file_inv_download, CURLOPT_REFERER, true);
                curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
                $store_inv = curl_exec($file_inv_download);
                curl_close($invoice_url);
                $save_inv_file = file_put_contents($download_path, $store_inv);
            }


            $multiple_attach = array($path, $download_path);
            //print_r($multiple_attach);

            //End

            //Send Notification Via SMS

            // if ($contact_no != '') {

            // 	if (strstr($contact_no, '+91')) {
            // 		$phone  =  $contact_no;
            // 	} else {
            // 		//echo "Text not found";
            // 		$phone  =  "+91" . $contact_no;
            // 	}

            // 	$sid = constant("SID");

            // 	$token = constant("Auth");

            // 	$twilio = new Client($sid, $token);

            // 	$msg = "Dear ".$client_name.", \r\n Your Booking is Cancelled - Against Your GRN No: ".$grn_no. "\r\n Reason For Cancellation : ".$remarks;

            // 	$message = $twilio->messages
            // 		->create(
            // 			$phone, // to
            // 			["body" => $msg, "from" => "+17853776942"]
            // 		);

            // 	}


            //End



            //Send Notification Via Email

            //*Send Invoice Instanly
            if ($pay_mode == '3' || $pay_mode == '4') {

                if ($pay_mode == '3') {
                    $check_restricted = check_invoice_restricted($conn, $consigner);
                    if ($check_restricted == 0) {
                        $msg = '<p style="line-height: 24px; margin-bottom:15px;">
							We are Sorry, but Your Consignment Booking On <a href = "https://graciousexpress.colanapps.in" >Elite Wave 360</a> on ' . $grn_date . ' 
							has been Cancelled! <br>
							Please Find Your Attachments (in PDF Format) to this email.
							<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
							<tr>
							<td >GRN No	</td><td >' . $grn_no . '</td>
							</tr>
							<tr>	
							<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
							</tr>
							<tr><td >Booked By	</td><td >' . get_client_name($conn, $consigner) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
							<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
							<tr>		
							<td >Status	</td><td style="color:red";  >Consignment Cancelled</td>		
		
												</tr>
												<tr>		
							<td >Cancellation Reason	</td><td >' . $remarks . '</td>		
							
												</tr>
											</table>	
							<br>
							<br>';

                        $to_name = array();
                        $to_email = array();

                        if (!empty(get_client_email($conn, $consigner)) && !empty(get_client_email($conn, $consignee))) {

                            array_push($to_email, get_client_email($conn, $consigner), get_client_email($conn, $consignee));
                            array_push($to_name, get_client_name($conn, $consigner), get_client_name($conn, $consignee));

                            $mail = sendAttachments($to_name, $to_email, 'Consignment Cancellation Notification', $multiple_attach, $image, $msg, $name);
                        }
                    }
                } else {

                    $check_restricted = check_invoice_restricted($conn, $consignee);
                    if ($check_restricted == 0) {
                        $msg = '<p style="line-height: 24px; margin-bottom:15px;">
							We are Sorry, but Your Consignment Booking On <a href = "https://graciousexpress.colanapps.in" >Elite Wave 360</a> on ' . $grn_date . ' 
							has been Cancelled!
							<br> Please Find Your Attachments (in PDF Format) to this email. 				
							<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
							<tr>
							<td >GRN No	</td><td >' . $grn_no . '</td>
							</tr><tr>	
							<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
							</tr>
							<tr><td >Booked By	</td><td >' . get_client_name($conn, $consigner) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
							<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
							<tr>		
							<td >Status	</td><td style="color:red"; >Consignment Cancelled</td>		
								</td>
									</tr>
												<tr>		
							<td >Cancellation Reason	</td><td>' . $remarks . '</td>		
												</tr>
											</table>	
							<br>
							<br>';

                        $to_name = array();
                        $to_email = array();

                        if (!empty(get_client_email($conn, $consigner)) && !empty(get_client_email($conn, $consignee))) {

                            array_push($to_email, get_client_email($conn, $consignee), get_client_email($conn, $consigner));
                            array_push($to_name, get_client_name($conn, $consignee), get_client_name($conn, $consigner));

                            $mail = sendAttachments($to_name, $to_email, 'Consignment Cancellation Notification', $download_path, $image, $msg, $name);
                        }
                    }
                }
            } else {

                //Send GRN Only

                $msg = '<p style="line-height: 24px; margin-bottom:15px;">
							We are Sorry, but Your Consignment Booking On <a href = "https://graciousexpress.colanapps.in" >Elite Wave 360</a> on ' . $grn_date . ' 
							has been Cancelled!
							<br> Please Find Your Attachments (in PDF Format) to this email. 				
							<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
							<tr>
							<td >GRN No	</td><td >' . $grn_no . '</td>
							</tr><tr>	
							<td >GRN Date:	</td><td >	' . $grn_date . '	</td>	
							</tr>
							<tr><td >Booked By	</td><td >' . get_client_name($conn, $consigner) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>	
							<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>	
							<tr>		
							<td >Status	</td><td style="color:red"; >Consignment Cancelled</td>		
	
									</tr>
												<tr>		
							<td >Cancellation Reason	</td><td  >' . $remarks . '</td>		
												</tr>
											</table>	
							<br>
							<br>';
                $to_name = array();
                $to_email = array();

                if (!empty(get_client_email($conn, $consigner)) && !empty(get_client_email($conn, $consignee))) {
                    array_push($to_email, get_client_email($conn, $consigner), get_client_email($conn, $consignee));
                    array_push($to_name, get_client_name($conn, $consigner), get_client_name($conn, $consignee));

                    $mail = sendAttachments($to_name, $to_email, 'Consignment Cancellation Notification', $path, $image, $msg, $name);
                }
                //End GRN
            }


            //*End Send Instant Invoice


            //End
            echo 1;
        } else {
            echo 0;
        }
    } else {
        echo 2;
    }
}
if ($form_name == 'set_invoice_frequency') {
    $f_updated_by = $_POST['logged_id'];
    $client_id = $_POST['client_id'];
    $frequency = $_POST['checked_value'];
    $query = "UPDATE `client` SET `invoice_frequency`='$frequency' ,  `updated_by` = '$f_updated_by', `updated_at` = '$updated_at' WHERE client_id = '$client_id'";
    $sql = mysqli_query($conn, $query);

    if ($sql) {
        echo "1";
    } else {
        echo "0";
    }
}
