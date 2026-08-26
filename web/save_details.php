<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('include/connect.php');
// require_once("save_admin.php");
include('include/function.php');
require_once('appMail.php');
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

// sms setup
// require '../plivo_sms/vendor/autoload.php';
// use Plivo\RestClient;
// $client = new RestClient("MAM2I1NDG2Y2UZODVIZW", "ZjM1MTM0MjBkM2YxNDBlMmM2NWI2ZTI3YjNlNDcz");
// sms setup end

// if ($form_name == 'login') {
//     $username = $_POST['email'];
//     $password = $_POST['password'];
//     $password = enc_name($password);
//     if (isset($_POST['remember']))
//         $remember = 1;
//     else
//         $remember = 0;
//     $id = $_POST['login'];
//     $select_query = "select * from users where md5(user_id) = '" . $id . "'";
//     $select_result = mysqli_query($conn, $select_query);
//     $select_row = mysqli_fetch_array($select_result);
//     if ($id != '') {
//         if ($select_row['password_status'] == 1) {
//             $update_query = "update users set password_status = 0,password='" . $password . "' where md5(user_id) = '" . $id . "'";
//             $update_result = mysqli_query($conn, $update_query);
//         } else {
//             echo 2;
//         }
//     }

//     $query = "select * from users where email='$username' and password='$password' and status=0";
//     $result = mysqli_query($conn, $query) or die(mysqli_error());

//     if (mysqli_num_rows($result) == 1) {
//         $row = mysqli_fetch_array($result);
//         $result = mysqli_query($conn, $query) or die(mysqli_error());
//         $_SESSION['LAST_ACTIVITY'] = time();
//         $_SESSION['role'] = $row['role'];
//         $_SESSION['user_id'] = $row['user_id'];
//         $uid = $row['user_id'];
//         $_SESSION['company_id'] = $row['company_name'];
//         if ($remember == 1)
//             setcookie('persistID', $uid, time() + (30 * 24 * 60 * 60), '/');

//         echo 1;
//     } else {
//         if ($id == '') {
//             echo 0;
//         }
//     }
// }

if ($form_name == 'login') {
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
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update_query = "update users set password_status = 0,password='" . $hashed . "' where md5(user_id) = '" . $id . "'";
            $update_result = mysqli_query($conn, $update_query);
        } else {
            echo 2;
        }
    }

    // Look up by email only — verify password separately with password_verify()
    $query = "select * from users where email='$username' and status=0";
    $result = mysqli_query($conn, $query) or die(mysqli_error());

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_array($result);

        if (password_verify($password, $row['password'])) {
            $_SESSION['LAST_ACTIVITY'] = time();
            $_SESSION['role'] = $row['role'];
            $_SESSION['user_id'] = $row['user_id'];
            $uid = $row['user_id'];
            $_SESSION['company_id'] = $row['company_name'];
            if ($remember == 1)
                setcookie('persistID', $uid, time() + (30 * 24 * 60 * 60), '/');

            echo 1;
        } else {
            echo 0;
        }
    } else {
        if ($id == '') {
            echo 0;
        }
    }
}
if ($form_name == 'recover') {
    $mail_id = $_POST['mail'];

    $query = "select * from tv_admins where email='$mail_id'";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));
    $num_row = mysqli_num_rows($result);
    $row = mysqli_fetch_array($result);
    if ($num_row > 0) {
        $password = $row['password'];
        $to = $row['email'];
        $subject = 'Forgot Password';
        $from = 'IMPLEMENTER';
        $body = 'Hi ' . $row['contact_person'] . ",<br><h3 style='color:#FF0000'>Your Login Password is :</h3>" . $password . '';

        sendAppMail('User', $to, $subject, $body);
        echo 0;
    } else {
        echo 1;
    }
}
if ($form_name == 'forgot_password') {
    $mail_id = $_POST['email'];

    $query = "select * from users where email='$mail_id'";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));
    $num_row = mysqli_num_rows($result);
    $row = mysqli_fetch_array($result);
    if ($num_row > 0) {
        $password = $row['password'];
        $to = $mail_id;
        $subject = 'Forgot Password';
        $from = 'Elite Wave 360';
        $body = 'Hi ' . $row['user_name'] . ",
\t\t\t         Click this link to login with new password: https://elitewave360.in/user/password_change.php?login=" . md5($row['user_id']) . '';

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
if ($form_name == 'password_change') {
    $edit_id = mysqli_real_escape_string($conn, $_POST['login_id']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = enc_name($confirm_password);
    $query = "update users set  password='$confirm_password' where user_id='$edit_id'";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));

    if ($result)
        echo 1;
}

if ($form_name == 'verify_login_otp') {
    $user_id = $_POST['user_id'];
    $otp = $_POST['otp'];
    $time = $_POST['time'];
    if ($time == '1') {
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
        echo 'OTP Remove';
        $remove_otp1 = "update users SET `otp`='' where `user_id` = '$user_id'";
        $update_otp1 = mysqli_query($conn, $remove_otp1);
        if ($update_otp1) {
            echo 1;
            unset($_SESSION['otp']);
        }
    }
}

if ($form_name == 'add_branch') {
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

    $query = "insert into branch(
branch_code,
branch_name,
contact_person,
contact_no,
address1,
address2,
city,
state,
pincode,
email,
created_at,
created_by,
updated_at,
updated_by,
status
) values (
'$branch_code',
'$branch_name',
'$contact_person',
'$contact_no',
'$address1',
'$address2',
'$city',
'$state',
'$pincode',
'$email',
'$created_at',
'$created_by',
'$updated_at',
'$updated_by',
'0'
)";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo mysqli_error($conn);
}
if ($form_name == 'edit_branch') {
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
if ($form_name == 'del_branch') {
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
    // }
}
if ($form_name == 'inacv_branch') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  branch set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where branch_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'add_state') {
    $state_name = $_POST['state_name'];
    $query = "insert into state(state_name,created_at,created_by,status)values('" . $state_name . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'del_state') {
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
        echo '404-del';
    }
}
if ($form_name == 'edit_state') {
    $state_name = $_POST['state_name'];
    $edit_id = $_POST['edit_id'];
    $query = "update  state set state_name='" . $state_name . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where state_id='" . $edit_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == 'inacv_state') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  state set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where state_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'add_city') {
    $select_query = mysqli_fetch_array(mysqli_query($conn, 'select max(city_code_id) as code_id from city'));
    $id = $select_query['code_id'] + 1;
    $city_code = 'GEC' . sprintf('%03d', $id);
    $city_name = $_POST['city_name'];
    $state_name = $_POST['state_name'];
    $city = $_POST['city'];
    $automation = isset($_POST['automation']) ? 1 : 0;
    $railway_station = $_POST['railway_station'];
    $airport = $_POST['airport'];
    $unloading_point = $_POST['unloading_point'];
    $warehouse = $_POST['warehouse'];
    $port = $_POST['port'];

    $query = "insert into city(
city_code,
city_code_id,
city_name,
state,
automation,
via_city,
railway_station,
airport,
unloading_point,
warehouse,
port,
created_at,
created_by,
status) values(
'$city_code',
'$id',
'$city_name',
'$state_name',
'$automation',
'$city',
'$railway_station',
'$airport',
'$unloading_point',
'$warehouse',
'$port',
'$created_at',
'$created_by',
'0')";
    $result = mysqli_query($conn, $query);

    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'edit_city') {

    $edit_id = $_POST['edit_id'];
    $city_name = $_POST['city_name'];
    $state_name = $_POST['state_name'];
    $city = $_POST['city'];
    $automation = isset($_POST['automation']) ? 1 : 0;
    $railway_station = $_POST['railway_station'];
    $airport = $_POST['airport'];
    $unloading_point = $_POST['unloading_point'];
    $warehouse = $_POST['warehouse'];
    $port = $_POST['port'];

    $query = "UPDATE city SET
        city_name = '$city_name',
        state = '$state_name',
        automation = '$automation',
        via_city = '$city',
        railway_station='$railway_station',
airport='$airport',
unloading_point='$unloading_point',
warehouse='$warehouse',
port='$port',
updated_at='$updated_at',
updated_by='$updated_by'
WHERE city_id='$edit_id'";

    $result = mysqli_query($conn, $query);

    if ($result) {
        echo 1;
    } else {
        echo mysqli_error($conn);   // Use this while testing
    }
}
if ($form_name == 'del_city') {
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
        echo '404-del';
    }
}
if ($form_name == 'inacv_city') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  city set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where city_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == 'add_hub') {

    $hub_name      = trim($_POST['hub_name']);
    $contact_name  = trim($_POST['contact_name']);
    $contact_no    = trim($_POST['contact_no']);
    $route         = $_POST['route'];

    $main_hub = !empty($_POST['main_hub'])
        ? $_POST['main_hub']
        : 0;

    $cities = isset($_POST['cities'])
        ? implode(',', $_POST['cities'])
        : '';

    $hubcode_q = mysqli_query(
        $conn,
        "SELECT IFNULL(MAX(hub_no),0) AS hub_no FROM hub"
    );

    $hub_r = mysqli_fetch_assoc($hubcode_q);

    $hub_no   = $hub_r['hub_no'] + 1;
    $hub_code = $hub_no;

    $query = "INSERT INTO hub
    (
        hub_code,
        hub_no,
        name,
        contact_person,
        contact_no,
        route,
        main_hubs,
        covered_cities,
        created_at,
        created_by,
        updated_at,
        updated_by,
        status
    )
    VALUES
    (
        '$hub_code',
        '$hub_no',
        '$hub_name',
        '$contact_name',
        '$contact_no',
        '$route',
        '$main_hub',
        '$cities',
        '$created_at',
        '$created_by',
        '$updated_at',
        '$updated_by',
        0
    )";

    $result = mysqli_query($conn, $query);

    if ($result)
        echo 1;
    else
        echo mysqli_error($conn);
}

if ($form_name == 'edit_hub') {
    $edit_id = $_POST['edit_id'];
    $hub_name = $_POST['hub_name'];
    $contact_name = $_POST['contact_name'];
    $contact_no = $_POST['contact_no'];
    $route = $_POST['route'];
    $main_hub = !empty($_POST['main_hub'])
        ? $_POST['main_hub']
        : 0;

    $cities = isset($_POST['cities'])
        ? implode(',', $_POST['cities'])
        : '';

    $query = "UPDATE hub SET
    name='$hub_name',
    contact_person='$contact_name',
    contact_no='$contact_no',
    main_hubs='$main_hub',
    route='$route',
    covered_cities='$cities',
    updated_at='$updated_at',
    updated_by='$updated_by'
WHERE hub_id='$edit_id'";

    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo mysqli_error($conn);
}
if ($form_name == 'del_hub') {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete  from hub where hub_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
}
if ($form_name == 'inacv_hub') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update hub set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where hub_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

// Role

if ($form_name == 'add_role') {
    $role_name = $_POST['role_name'];
    echo $role = strtoupper(explode(' ', $role_name));
    echo $query = "insert into role (role_name,role,created_at,created_by,status) values
\t('$role_name','$role','$created_at','$created_by','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == 'edit_role') {
    $edit_id = $_POST['edit_id'];
    $role_name = $_POST['role_name'];

    $query = "update hub set role_name='" . $role_name . "',updated_at='" . $updated_at . "',updated_by='" . $udpated_by . "' where role_id='" . $edit_id . "'";

    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'del_role') {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete  from role where role_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
}
if ($form_name == 'inacv_role') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update role set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where role_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

// Train

if ($form_name == 'add_train') {
    $train_name = $_POST['train_name'];
    $train_number = $_POST['train_number'];
    $loading_point1 = $_POST['loading_point1'];
    $loading_point2 = $_POST['loading_point2'];
    $loading_point3 = $_POST['loading_point3'];
    $loading_point4 = $_POST['loading_point4'];
    $journey_hours = $_POST['journey_hours'];

    $query = "INSERT INTO train (
    train_name,
    train_number,
    loading_point1,
    loading_point2,
    loading_point3,
    loading_point4,
    journey_hours,
    created_at,
    created_by,
    updated_at,
    updated_by,
    status
) VALUES (
    '$train_name',
    '$train_number',
    '$loading_point1',
    '$loading_point2',
    '$loading_point3',
    '$loading_point4',
    '$journey_hours',
    '$created_at',
    '$created_by',
    '$updated_at',
    '$updated_by',
    '0'
)";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die(mysqli_error($conn));
    }

    echo 1;
}

if ($form_name == 'edit_train') {
    $edit_id = $_POST['edit_id'];
    $train_name = $_POST['train_name'];
    $train_number = $_POST['train_number'];
    $loading_point1 = $_POST['loading_point1'];
    $loading_point2 = $_POST['loading_point2'];
    $loading_point3 = $_POST['loading_point3'];
    $loading_point4 = $_POST['loading_point4'];
    $journey_hours = $_POST['journey_hours'];

    $query = "update train set train_name='" . $train_name . "',train_number='" . $train_number . "',loading_point1='" . $loading_point1 . "',loading_point2='" . $loading_point2 . "',loading_point3='" . $loading_point3 . "',loading_point4='" . $loading_point4 . "',journey_hours='" . $journey_hours . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where train_id='" . $edit_id . "'";

    $result = mysqli_query($conn, $query);
    if (!$result) {
        die(mysqli_error($conn));
    }

    echo 1;
}
if ($form_name == 'del_train') {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete  from train where train_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
}
if ($form_name == 'inacv_train') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update train set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where train_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
// Flight

if ($form_name == 'add_flight') {
    $flight_name = $_POST['flight_name'];
    $flight_number = $_POST['flight_number'];
    $loading_point1 = $_POST['loading_point1'];
    $loading_point2 = $_POST['loading_point2'];
    $loading_point3 = $_POST['loading_point3'];
    $loading_point4 = $_POST['loading_point4'];
    $journey_hours = $_POST['journey_hours'];

    $query = "INSERT INTO flight(
    flight_name,
    flight_number,
    loading_point1,
    loading_point2,
    loading_point3,
    loading_point4,
    journey_hours,
    created_at,
    created_by,
    updated_at,
    updated_by,
    status
)
VALUES(
    '$flight_name',
    '$flight_number',
    '$loading_point1',
    '$loading_point2',
    '$loading_point3',
    '$loading_point4',
    '$journey_hours',
    '$created_at',
    '$created_by',
    '$updated_at',
    '$updated_by',
    '0'
)";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die(mysqli_error($conn));
    }

    echo 1;
}

if ($form_name == 'edit_flight') {
    $edit_id = $_POST['edit_id'];
    $flight_name = $_POST['flight_name'];
    $flight_number = $_POST['flight_number'];
    $loading_point1 = $_POST['loading_point1'];
    $loading_point2 = $_POST['loading_point2'];
    $loading_point3 = $_POST['loading_point3'];
    $loading_point4 = $_POST['loading_point4'];
    $journey_hours = $_POST['journey_hours'];

    $query = "update flight set flight_name='" . $flight_name . "',flight_number='" . $flight_number . "',loading_point1='" . $loading_point1 . "',loading_point2='" . $loading_point2 . "',loading_point3='" . $loading_point3 . "',loading_point4='" . $loading_point4 . "',journey_hours='" . $journey_hours . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where flight_id='" . $edit_id . "'";

    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        // echo 0;
        die(mysqli_error($conn));
}
if ($form_name == 'del_flight') {
    $tbl_id = $_POST['tbl_id'];

    $query = "delete  from flight where flight_id='" . $tbl_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
}
if ($form_name == 'inacv_flight') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update flight set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where flight_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == 'add_mode_of_transportation') {
    $mode_type = $_POST['mode_type'];
    $delivery = $_POST['delivery'];
    $sac_code = $_POST['sac_code'];

    $query = "insert into mode_of_transportation(mode_type
\t,max_hrs_delivery,sac_code,created_at,created_by,status)values
\t('" . $mode_type . "','" . $delivery . "','" . $sac_code . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'edit_mode') {

    $edit_id   = $_POST['edit_id'];
    $mode_type = trim($_POST['mode_type']);
    $delivery  = trim($_POST['delivery']);
    $sac_code = trim($_POST['sac_code']);

    $query = "UPDATE mode_of_transportation SET
        mode_type='$mode_type',
        max_hrs_delivery='$delivery',
        sac_code='$sac_code',
        updated_at='$updated_at',
        updated_by='$updated_by'
        WHERE mode_id='$edit_id'";

    $result = mysqli_query($conn, $query);

    if ($result) {
        echo 1;
    } else {
        echo mysqli_error($conn);
    }
}
if ($form_name == 'del_mode') {
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
        echo '404-del';
    }
}
if ($form_name == 'inacv_mode') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  mode_of_transportation set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where mode_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'add_client') {
    $company_name = $_POST['company_name'];
    $contact_person = $_POST['contact_person'];
    $address1 = $_POST['address1'];
    $address2 = $_POST['address2'];
    $state = $_POST['state'];
    $city = $_POST['city'];
    $billing_code = strtoupper($_POST['billing_code']);
    $pincode = $_POST['pincode'];
    $email = $_POST['email'];
$email1 = $_POST['email1'];
    $contact_no = $_POST['contact_no'];
$contact_no1 = $_POST['contact_no1'];
    $gst_no = $_POST['gst_no'];
    $pan_no = $_POST['pan_no'];
    // $multiple_branches = $_POST['multiple_branches'];
    $multiple_branches = isset($_POST['multiple_branches']) ? 1 : 0;
    // $automation = $_POST['transit_automation'];
    $automation = isset($_POST['automation']) ? 1 : 0;
    if ($_POST['edit_id'] != '') {
        $query = "update client set client_company_name='" . $company_name . "',contact_person='" . $contact_person . "',address1='" . $address1 . "',address2='" . $address2 . "',state='" . $state . "',city='" . $city . "',pincode='" . $pincode . "',billing_code='" . $billing_code . "',email='" . $email . "',email1='" . $email1 . "',contact_no='" . $contact_no . "',contact_no1='" . $contact_no1 . "',gst_no='" . $gst_no . "',pan_no='" . $pan_no . "',multiple_branches='" . $multiple_branches . "',automation='" . $automation . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where md5(client_id)='" . $_POST['edit_id'] . "'";
        // $result = mysqli_query($conn, $query);
        $result = mysqli_query($conn, $query);

        if (!$result) {
            die(mysqli_error($conn));
        }
    } else {
        if ($_SESSION['role'] == 'AD') {
            $query = "insert into client(
                client_company_name,
                contact_person,
                address1,
                address2,
                state,
                city,
                pincode,
                billing_code,
                email,
                email1,
                contact_no,
                contact_no1,
                gst_no,
                pan_no,
                multiple_branches,
                automation,
                created_at,
                created_by,
                status,
                approve_status
                ) values (
                '" . $company_name . "',
                '" . $contact_person . "',
                '" . $address1 . "',
                '" . $address2 . "',
                '" . $state . "',
                '" . $city . "',
                '" . $pincode . "',
                '" . $billing_code . "',
                '" . $email . "',
                
                 '" . $email1 . "',
                 '" . $contact_no . "',
                '" . $contact_no1 . "',
                '" . $gst_no . "',
                '" . $pan_no . "',
                '" . $multiple_branches . "',
                '" . $automation . "',
                '" . $created_at . "',
                '" . $created_by . "',
                '0',
                '0'
                )";
            // $result = mysqli_query($conn, $query);
            $result = mysqli_query($conn, $query);

            if (!$result) {
                die(mysqli_error($conn));
            }
        } else {

            $query = "insert into client(
                client_company_name,
                contact_person,
                address1,
                address2,
                state,
                city,
                pincode,
                billing_code,
                email,
                email1,
                contact_no,
                contact_no1,
                gst_no,
                pan_no,
                multiple_branches,
                automation,
                created_at,
                created_by,
                status,
                approve_status
                ) values (
                '" . $company_name . "',
                '" . $contact_person . "',
                '" . $address1 . "',
                '" . $address2 . "',
                '" . $state . "',
                '" . $city . "',
                '" . $pincode . "',
                '" . $billing_code . "',
                '" . $email . "',
                '" . $email1 . "',
                '" . $contact_no . "',
                 '" . $contact_no1 . "',
                '" . $gst_no . "',
                '" . $pan_no . "',
                '" . $multiple_branches . "',
                '" . $automation . "',
                '" . $created_at . "',
                '" . $created_by . "',
                '0',
                '0'
                )";
            // $result = mysqli_query($conn, $query);
            $result = mysqli_query($conn, $query);

            if (!$result) {
                die(mysqli_error($conn));
            }
        }
    }
    if ($result) {
        echo 1;
    } else {
        echo 'MYSQL ERROR: ' . mysqli_error($conn);
    }
}

if ($form_name == 'del_client') {
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
        echo '404-del';
    }
}
if ($form_name == 'inacv_client') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  client set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where client_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == 'restrict_inv_client') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  client set invoice_status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where client_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'add_user') {
    //  echo "A";

    $edit_id = $_POST['edit_id'];
    $user_name = $_POST['user_name'];
    $role = $_POST['role'];
    $company_type = $_POST['company_type'];
    $company_name = empty($_POST['company_name']) ? 0 : (int)$_POST['company_name'];
    $branch_name = $_POST['branch'];
    $contact_no = $_POST['contact_no'];
    $assigned_vehicle = isset($_POST['assigned_vehicle']) ? $_POST['assigned_vehicle'] : '';
    $user_email = $_POST['user_email'];
    $original_password = $_POST['password'];

    // echo "B";

    $password = password_hash($original_password, PASSWORD_DEFAULT);

    // echo "C";
    // exit;


    if ($_POST['edit_id'] != '' && $original_password === '') {
        // editing, no new password typed -> don't touch the password column
        $query = "update users set company_name='" . $company_name . "',company_type='" . $company_type . "',branch_name='" . $branch_name . "',role='" . $role . "',contact_no='" . $contact_no . "',email='" . $user_email . "',user_name='" . $user_name . "',assigned_vehicle='" . $assigned_vehicle . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where md5(user_id)='" . $_POST['edit_id'] . "'";

        $result = mysqli_query($conn, $query);

        if (!$result) {
            die(mysqli_error($conn));
        }
    } else {
        $password = password_hash($original_password, PASSWORD_DEFAULT);
        if ($_POST['edit_id'] != '') {
            $query = "update users set company_name='" . $company_name . "',company_type='" . $company_type . "',branch_name='" . $branch_name . "',role='" . $role . "',contact_no='" . $contact_no . "',email='" . $user_email . "',password='" . $password . "',user_name='" . $user_name . "',assigned_vehicle='" . $assigned_vehicle . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where md5(user_id)='" . $_POST['edit_id'] . "'";
        } else {
            $query = "INSERT INTO users(
company_name,
branch_name,
company_type,
role,
contact_no,
email,
password,
user_name,
assigned_vehicle,
created_at,
created_by,
status,
password_status,
credential_status
)
VALUES(
'$company_name',
'$branch_name',
'$company_type',
'$role',
'$contact_no',
'$user_email',
'$password',
'$user_name',
'$assigned_vehicle',
'$created_at',
'$created_by',
'0',
'0',
'1'
)";
        }
        $result = mysqli_query($conn, $query);

        if (!$result) {
            die(mysqli_error($conn));
        }
    }
    if ($result) {
        $subject = 'User Credential';
        $body = "<p style=\"line-height: 24px; margin-bottom:15px;\">Please Find Your Credential Below to access User Dashboard.</p>
        <hr>
        <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
        <tr>\t
        <td >Username:\t</td><td >\t" . $user_email . "\t</td>\t
        </tr><tr>\t
        <td >Password:</td><td >" . $original_password . "</td>\t
        </tr>
        </table>
        <br>
        <br>\t
        <small><b>Note:</b>Do not Share Your Credential with anyone.</small>
        <br>
        <ul>
        <li>Click <a href=\"https://elitewave360.in/user/login.php\">here</a> for User Dashboard.</li>
        </ul>";
        sendAppMail($user_name, $user_email, $subject, $body);
        echo 1;
    } else {
        echo mysqli_error($conn);
    }
}

if ($form_name == 'del_user') {
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
        echo '404-del';
    }
}
if ($form_name == 'inacv_user') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  users set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where user_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == 'add_consignment') {
    $consignment = $_POST['consignment'];
    $description = $_POST['description'];
    $query = "insert into consignment_mode(consignment_mode,description,created_at,created_by,status)values
\t\t('" . $consignment . "','" . $description . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'edit_consignment') {
    $edit_id = $_POST['edit_id'];
    $consignment = $_POST['consignment'];
    $description = $_POST['description'];
    $query = "update consignment_mode set consignment_mode='" . $consignment . "',description='" . $description . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where consignment_id='" . $edit_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'del_consignment') {
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
        echo '404-del';
    }
}
if ($form_name == 'inacv_consignment') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  consignment_mode set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where consignment_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'add_package') {
    $package_code = $_POST['package_code'];
    $description = $_POST['description'];
    $query = "insert into package(package_code,description,created_at,created_by,status)values
\t\t('" . $package_code . "','" . $description . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'edit_package') {
    $edit_id = $_POST['edit_id'];
    $package_code = $_POST['package_code'];
    $description = $_POST['description'];
    $query = "update package set package_code='" . $package_code . "',description='" . $description . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where package_id='" . $edit_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'del_package') {
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
if ($form_name == 'inacv_package') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  package set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where package_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

// vehicle
if ($form_name == 'add_vehicle') {
    extract($_POST);
    if ($edit_id != '') {
        $query = "update  vehicle set vehicle_number='" . $vehicle_number . "',vehicle_type='" . $vehicle_type . "',model='" . $model . "',fitness='" . $fitness . "',insurance='" . $insurance . "',road_tax='" . $road_tax . "',permit='" . $permit . "',emission='" . $emission . "',pollution_certificate='" . $pollution_certificate . "',finance='" . $finance . "',vehicle_status='" . $vehicle_status . "',registration='" . $registration . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where md5(vehicle_id)='" . $edit_id . "'";
        $result = mysqli_query($conn, $query);
    } else {
        $query = "insert into vehicle(vehicle_number,vehicle_type,model,fitness,insurance,road_tax,permit,emission,pollution_certificate,finance,vehicle_status,registration,created_at,created_by,status)values
\t\t('" . $vehicle_number . "','" . $vehicle_type . "','" . $model . "','" . $fitness . "','" . $insurance . "','" . $road_tax . "','" . $permit . "','" . $emission . "','" . $pollution_certificate . "','" . $finance . "','" . $vehicle_status . "','" . $registration . "','" . $created_at . "','" . $created_by . "','0')";
        $result = mysqli_query($conn, $query);
    }
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == 'del_vehicle') {
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
if ($form_name == 'inacv_vehicle') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  vehicle set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where vehicle_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'add_delivery') {
    $sequence = $_POST['sequence'];
    $status_type = $_POST['status_type'];

    $query = "insert into delivery_status(status_type,sequence,created_at,created_by,status)values
\t\t('" . $status_type . "','" . $sequence . "','" . $created_at . "','" . $created_by . "','0')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'edit_delivery') {
    $edit_id = $_POST['edit_id'];
    $vehicle_no = $_POST['vehicle_no'];
    $branch = $_POST['branch'];
    $rc_book_no = $_POST['rc_book_no'];
    $rc_book_expires = $_POST['rc_book_expires'];
    $insurance_no = $_POST['insurance_no'];
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
if ($form_name == 'del_delivery') {
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
if ($form_name == 'inacv_delivery') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update  delivery_status set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where delivery_status_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'add_client_branch') {
    $edit_id = $_POST['edit_id'];
    $company_id = $_POST['company_id'];
    $branch_name = $_POST['branch_name'];
    $contact_person = $_POST['contact_person'];
    $contact_no = $_POST['contact_no'];
    $address1 = $_POST['address1'];
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
\t\t('" . $company_id . "','" . $branch_name . "','" . $contact_person . "','" . $contact_no . "','" . $address1 . "','" . $address2 . "','" . $city . "','" . $state . "','" . $pincode . "','" . $email . "','" . $created_at . "','" . $created_by . "','0')";
        $result = mysqli_query($conn, $query);
    }
    if ($result)
        echo 1;
    else
        echo 0;
}
if ($form_name == 'del_client_branch') {
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

// ========================================
// ADD CUSTOMER MAPPING
// ========================================

if ($form_name == 'add_customer_mapping') {

    $client = isset($_POST['client']) ? $_POST['client'] : '';

    // Always keep these as arrays
    $new_id = !empty($_POST['new_id'])
        ? explode(',', $_POST['new_id'])
        : array();

    $del_id = !empty($_POST['del_id'])
        ? explode(',', trim($_POST['del_id']))
        : array();


    // ========================================
    // VALIDATE CUSTOMER
    // ========================================

    if ($client == '') {
        echo "ERROR: Client ID is empty";
        exit;
    }


    // ========================================
    // CHECK EXISTING CUSTOMER MAPPING
    // ========================================

    $select_query = "SELECT * FROM customer_mapping
                     WHERE client='" . $client . "'";

    $select_result = mysqli_query($conn, $select_query);

    if (!$select_result) {
        echo "SELECT ERROR: " . mysqli_error($conn);
        exit;
    }

    $select_count = mysqli_num_rows($select_result);


    // ========================================
    // GET OR CREATE MAPPING ID
    // ========================================

    if ($select_count > 0) {

        // Customer mapping already exists
        $select_row = mysqli_fetch_array($select_result);

        $last_insert_id = $select_row['mapping_id'];
    } else {

        // Customer mapping doesn't exist
        // Create parent mapping FIRST

        $mapping_sql = "INSERT INTO customer_mapping
                        (
                            client,
                            created_at,
                            created_by,
                            status
                        )
                        VALUES
                        (
                            '" . $client . "',
                            '" . $created_at . "',
                            '" . $created_by . "',
                            '0'
                        )";

        $mapping_query = mysqli_query($conn, $mapping_sql);

        if (!$mapping_query) {
            echo "MAPPING INSERT ERROR: " . mysqli_error($conn);
            exit;
        }

        // Get newly created mapping_id
        $last_insert_id = mysqli_insert_id($conn);
    }


    // ========================================
    // DELETE REMOVED CONSIGNEES
    // ========================================

    foreach ($del_id as $delete_id) {

        $delete_id = trim($delete_id);

        if ($delete_id != '' && $delete_id > 0) {

            $delete_sql = "DELETE FROM customer_mapping_lists
                           WHERE list_id='" . $delete_id . "'";

            $delete_result = mysqli_query($conn, $delete_sql);

            if (!$delete_result) {
                echo "DELETE ERROR: " . mysqli_error($conn);
                exit;
            }
        }
    }


    // ========================================
    // INSERT NEW CONSIGNEES
    // ========================================

    foreach ($new_id as $mapped_client_id) {

        $mapped_client_id = trim($mapped_client_id);

        if ($mapped_client_id != '' && $mapped_client_id > 0) {

            $insert_sql = "INSERT INTO customer_mapping_lists
                           (
                               client_id,
                               mapping_id,
                               created_at,
                               created_by,
                               updated_at,
                               updated_by,
                               status
                           )
                           VALUES
                           (
                               '" . $mapped_client_id . "',
                               '" . $last_insert_id . "',
                               '" . $created_at . "',
                               '" . $created_by . "',
                               '" . $created_at . "',
                               '" . $created_by . "',
                               '0'
                           )";

            $insert_result = mysqli_query($conn, $insert_sql);

            if (!$insert_result) {
                echo "LIST INSERT ERROR: " . mysqli_error($conn);
                exit;
            }
        }
    }


    // ========================================
    // SUCCESS
    // ========================================

    echo 1;
    exit;
}


// ========================================
// ACTIVE / INACTIVE MAPPED CLIENT
// ========================================

if ($form_name == 'inacv_mapped_client') {

    $id = $_POST['tbl_id'];
    $status = $_POST['status'];

    $query = "UPDATE customer_mapping_lists
              SET status='" . $status . "',
                  updated_at='" . $updated_at . "',
                  updated_by='" . $updated_by . "'
              WHERE list_id='" . $id . "'";

    $result = mysqli_query($conn, $query);

    if ($result) {
        echo 1;
    } else {
        echo "UPDATE ERROR: " . mysqli_error($conn);
    }

    exit;
}


// ========================================
// DELETE MAPPED CLIENT
// ========================================

if ($form_name == 'del_mapped_client') {

    $id = $_POST['tbl_id'];

    $query = "DELETE FROM customer_mapping_lists
              WHERE list_id='" . $id . "'";

    $result = mysqli_query($conn, $query);

    if ($result) {
        echo 1;
    } else {
        echo "DELETE ERROR: " . mysqli_error($conn);
    }

    exit;
}

if ($form_name == 'inacv_client_branch') {
    $id = $_POST['tbl_id'];
    $status = $_POST['status'];
    $query = "update client_branch  set status='" . $status . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "' where client_branch_id='" . $id . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}
// Approve client
if ($form_name == 'approve_client') {
    $company_name = $_POST['company_name'];
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
    $grn_mode = $_POST['grn_mode'];  // New field

    $query = "update client set client_company_name='" . $company_name . "',contact_person='" . $contact_person . "',address1='" . $address1 . "',address2='" . $address2 . "',state='" . $state . "',city='" . $city . "',pincode='" . $pincode . "',email='" . $email . "',contact_no='" . $contact_no . "',gst_no='" . $gst_no . "',pan_no='" . $pan_no . "',multiple_branches='" . $multiple_branches . "',automation='" . $automation . "',grn_mode='" . $grn_mode . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "',approve_status='0'  where  md5(client_id)='" . $_POST['edit_id'] . "'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == 'bulk_list_status_update') {
    $c_date = date('d-m-Y H:i:s A');
    $status = $_POST['status'];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
    $grn_ids = isset($_POST['grn_id']) ? $_POST['grn_id'] : array();
    $grn_nos = isset($_POST['grn_no']) ? $_POST['grn_no'] : array();
    $tab_names = isset($_POST['tab_name']) ? $_POST['tab_name'] : array();
    $trans_ids = isset($_POST['trans_id']) ? $_POST['trans_id'] : array();

    if (empty($status) || empty($trans_ids)) {
        echo 0;
        // exit;
    }

    // sheet header row, same pattern as change_grn_status
    $sheetq = 'SELECT max(sheet_id) as id FROM transaction_status';
    $sheetres = mysqli_query($conn, $sheetq);
    $sheetr = mysqli_fetch_array($sheetres);
    $sheet_id = $sheetr['id'] + 1;
    $sheet_no = 'SN/' . sprintf('%04d', $sheet_id);

    $insq1 = "INSERT INTO `transaction_status`(`sheet_id`,`sheet_no`,`remarks`,`status`,`created_at`,`created_by`)
              VALUES ('$sheet_id','$sheet_no','" . mysqli_real_escape_string($conn, $remarks) . "','$status','$c_date','$created_by')";
    mysqli_query($conn, $insq1);

    $success_count = 0;
    $fail_count = 0;

    for ($i = 0; $i < count($trans_ids); $i++) {
        $trans_id = mysqli_real_escape_string($conn, $trans_ids[$i]);
        $grn_id = isset($grn_ids[$i]) ? mysqli_real_escape_string($conn, $grn_ids[$i]) : '';
        $grn_no = isset($grn_nos[$i]) ? mysqli_real_escape_string($conn, $grn_nos[$i]) : '';
        $tab_name = isset($tab_names[$i]) ? $tab_names[$i] : '';

        if ($tab_name === '') {
            $fail_count++;
            continue;
        }

        // tab_name comes from result['tab_name'] which is row2['table_name'] from transaction_tbls
        // (e.g. "1_2026"), matching how every other handler in this file builds table names
        $trans_table = 'transaction_' . $tab_name;

        $row_q = "SELECT status, client_id FROM $trans_table WHERE transaction_id='$trans_id'";
        $row_res = mysqli_query($conn, $row_q);

        if (!$row_res || mysqli_num_rows($row_res) == 0) {
            $fail_count++;
            continue;
        }
        $row = mysqli_fetch_assoc($row_res);

        $upd_query = "UPDATE $trans_table SET status='$status' WHERE transaction_id='$trans_id'";
        $upd_result = mysqli_query($conn, $upd_query);

        if ($upd_result) {
$insq = "INSERT INTO `transaction_status_log`
(
    `sheet_id`,
    `grn_id`,
    `grn_no`,
    `from_status`,
    `to_status`,
    `delivery_type`,
    `delivered_packages`,
    `total_packages`,
    `client_id`,
    `updated_at`,
    `updated_by`
)
VALUES
(
    '$sheet_id',
    '" . $row['grn_id'] . "',
    '" . $row['grn_no'] . "',
    '" . $row['status'] . "',
    '$status',
    '" . (($status == 8) ? $delivery_type : '') . "',
    '" . (($status == 8) ? $delivered_packages : 0) . "',
    '" . (($status == 8) ? $total_packages : 0) . "',
    '" . $row['client_id'] . "',
    '$created_at',
    '$created_by'
)";
            mysqli_query($conn, $insq);
            $success_count++;
        } else {
            $fail_count++;
        }
    }

    if ($success_count > 0) {
        echo 1;
    } else {
        echo 0;
    }
}

// Consignment Booking
if ($form_name == 'add_new_consignment') {
    $out_put = array();
    extract($_POST);

    if ($_SESSION['company_id'] != '') {
        $other_train_names = ($other_train_name != '') ? $other_train_name : null;
        $tables = get_trans_table_name($conn, $grn_date);
        $get_m_y = explode('_', $tables[0]);
        $month = $get_m_y[1];
        $year = $get_m_y[2];

        $consignorquery = "select * from client where client_id='$consignor'";
        $consignorresult = mysqli_query($conn, $consignorquery);
        $consignorrow = mysqli_fetch_array($consignorresult);
        $billing_code = $consignorrow['billing_code'];
        $address1 = $consignorrow['address1'];
        $address2 = $consignorrow['address2'];
        $city = $consignorrow['city'];
        $pincode = $consignorrow['pincode'];
        $state = $consignorrow['state'];
        $phone = $consignorrow['contact_no'];
        $gst_no = $consignorrow['gst_no'];

        // Fetch active company details
        $company_query = mysqli_query($conn, 'SELECT company_id, company_code, grn_mode FROM company WHERE status=0 LIMIT 1');
        $company_row = mysqli_fetch_array($company_query);
        $comp_id = isset($company_row['company_id']) ? $company_row['company_id'] : 2;
        $comp_code = isset($company_row['company_code']) ? $company_row['company_code'] : '';
        $comp_grn_mode = isset($company_row['grn_mode']) ? $company_row['grn_mode'] : 'company';

        if ($comp_grn_mode === 'company') {
            $billing_code = $comp_code;
            $grn_client_id = $_SESSION['company_id'];
            $grn_type_db = 'company';
        } else {
            $billing_code = $consignorrow['billing_code'];
            $grn_client_id = $consignor;
            $grn_type_db = 'party';
        }

        // Generate unique 12-digit numeric tracking code
        $tracking_code = '';
        do {
            $tracking_code = '';
            for ($i = 0; $i < 12; $i++) {
                $tracking_code .= random_int(0, 9);
            }
            $chk_unique = mysqli_query($conn, "SELECT id FROM transaction_log WHERE tracking_code='$tracking_code'");
        } while (mysqli_num_rows($chk_unique) > 0);

        // Get Latest GCN No based on selected client (consignor or main company)
        $id_01 = get_next_grn_id($conn, $comp_grn_mode === 'company' ? 'COMPANY' : $grn_client_id);
        if ($comp_grn_mode === 'company') {
            $grn_num1 = strtoupper($billing_code . sprintf('%04d', $id_01));
        } else {
            $grn_num1 = strtoupper($billing_code . sprintf('%05d', $id_01));
        }
        // echo $grn_num1;

        $consigneequery = "select * from client where client_id='$consignee'";
        $consigneeresult = mysqli_query($conn, $consigneequery);
        $consigneerow = mysqli_fetch_array($consigneeresult);

        $con_address1 = $consigneerow['address1'];
        $con_address2 = $consigneerow['address2'];
        $con_city = $consigneerow['city'];
        $con_state = $consigneerow['state'];
        $con_pincode = $consigneerow['pincode'];
        $con_phone = $consigneerow['contact_no'];
        $con_gst = $consigneerow['gst_no'];
        $ftl_type = $_POST['truck_type'];

        // Train type and Charges
        $train_name = $_POST['train_name'];
        $rajdhani_charges = $_POST['rajdhani_charges'];

        // Volumetric Values
        $len = $_POST['length'] ? $_POST['length'] : null;
        $wid = $_POST['width'] ? $_POST['width'] : null;
        $hei = $_POST['height'] ? $_POST['height'] : null;
        $quanti = $_POST['quanti'] ? $_POST['quanti'] : null;
        $vlm_wei = $_POST['vlm_weight'] ? $_POST['vlm_weight'] : null;

        // Shipping Address
        $ship_address = $_POST['shipping_address'] ? $_POST['shipping_address'] : null;
        $shipping_address_name = isset($_POST['shipping_address_name']) ? $_POST['shipping_address_name'] : '';
        $shipping_gst_no = isset($_POST['shipping_gst_no']) ? $_POST['shipping_gst_no'] : '';
        $shipping_phone = isset($_POST['shipping_phone']) ? $_POST['shipping_phone'] : '';

        // Eway Expiry
        $eway_expiryDate = $_POST['eway_expiryDate'] ? $_POST['eway_expiryDate'] : null;
        $vehicle_type = isset($_POST['vehicle_type']) ? $_POST['vehicle_type'] : null;
        $freight_paid_by = isset($_POST['freight_paid_by']) ? $_POST['freight_paid_by'] : null;
        $insurance_number = isset($_POST['insurance_number']) ? $_POST['insurance_number'] : null;
        $lc_number = isset($_POST['lc_number']) ? $_POST['lc_number'] : null;
        $cfs = isset($_POST['cfs']) ? $_POST['cfs'] : null;

        $mamul_charge = (isset($_POST['mamul_charge']) && is_numeric($_POST['mamul_charge'])) ? $_POST['mamul_charge'] : '0';
        $vehicle_halting_charge = (isset($_POST['vehicle_halting_charge']) && is_numeric($_POST['vehicle_halting_charge'])) ? $_POST['vehicle_halting_charge'] : '0';
        $vehicle_loading_unloading = (isset($_POST['vehicle_loading_unloading']) && is_numeric($_POST['vehicle_loading_unloading'])) ? $_POST['vehicle_loading_unloading'] : '0';
        $bill_to = isset($_POST['bill_to']) ? $_POST['bill_to'] : '';
        $vehicle_purchase_contact_person = isset($_POST['vehicle_purchase_contact_person']) ? $_POST['vehicle_purchase_contact_person'] : '';
        $quotation_approval = isset($_POST['quotation_approval']) ? $_POST['quotation_approval'] : '';
        $highload_challan = isset($_POST['highload_challan']) ? $_POST['highload_challan'] : '';
        $supplier_invoice_value = isset($_POST['supplier_invoice_value']) ? $_POST['supplier_invoice_value'] : '';
        $volumetric_weight = isset($_POST['volumetric_weight']) ? $_POST['volumetric_weight'] : '';
        $description_of_goods = isset($_POST['description_of_goods']) ? $_POST['description_of_goods'] : '';
        $table0 = $tables[0];
        $booking_time = date('H:i:s');

        require_once('include/gst_tax_functions.php');
        ensure_transaction_gst_columns($conn, $table0);
        $company_state_q = mysqli_query($conn, 'SELECT state FROM company WHERE status=0 LIMIT 1');
        $company_state_row = mysqli_fetch_assoc($company_state_q);
        $company_state_id = (int) ($company_state_row['state'] ?? 0);
        $gst_snapshot = gst_tax_build_booking_snapshot($conn, $_POST, $company_state_id);
        $gst_type = mysqli_real_escape_string($conn, $gst_snapshot['gst_type'] ?? '');
        $gst_tax_id = (int) ($gst_snapshot['gst_tax_id'] ?? 0);
        $gst_tax_code = mysqli_real_escape_string($conn, $gst_snapshot['gst_tax_code'] ?? '');
        $cgst_rate = (float) ($gst_snapshot['cgst_rate'] ?? 0);
        $sgst_rate = (float) ($gst_snapshot['sgst_rate'] ?? 0);
        $igst_rate = (float) ($gst_snapshot['igst_rate'] ?? 0);
        $cess_rate = (float) ($gst_snapshot['cess_rate'] ?? 0);
        $cgst_amount = (float) ($gst_snapshot['cgst_amount'] ?? 0);
        $sgst_amount = (float) ($gst_snapshot['sgst_amount'] ?? 0);
        $igst_amount = (float) ($gst_snapshot['igst_amount'] ?? 0);
        $cess_amount = (float) ($gst_snapshot['cess_amount'] ?? 0);
        $taxable_value = (float) ($gst_snapshot['taxable_value'] ?? 0);
        $bill_to_state_id = (int) ($gst_snapshot['bill_to_state_id'] ?? 0);
        $gst_rate = (float) ($gst_snapshot['gst_rate'] ?? 0);
        $gst_amount = (float) ($gst_snapshot['gst_amount'] ?? 0);
        $total = (float) ($gst_snapshot['grand_total'] ?? $total);

        $chk_dup = mysqli_query($conn, "SELECT transaction_id FROM $table0 WHERE grn_no = '$grn_num1'");
        if (mysqli_num_rows($chk_dup) > 0) {
            $out_put['result'] = '0';
            $out_put['sql_error'] = 'Duplicate GRN: this booking was already saved.';
            ob_clean();
            echo json_encode($out_put);
            // exit;
        }

        $query = "insert into $table0 (grn_no,grn_id,grn_date,booking_time,mode_of_transportation,train_type,ftl_type,origin,destination,mode_of_consignment,consigner,address1,address2,city,pincode,state,phone,gst_no,consignee,con_address1,con_address2,shipping_address,shipping_address_name, shipping_gst_no, shipping_phone,con_city,con_state,con_pincode,con_phone,con_gst_no,goods_dedared_value,bill_to,
supplier_invoice_value,
description_of_goods,octroi,dimension1,dimension2,dimension3,dimension4,volumetric_weight,consignment_weight,frieght_rate,frieght_amount,loading_unloading_rate,
            loading_unloading_amount, crane_fork_lift_rate, crane_fork_lift_amount,cod_rate,cod_amount,fov_rate,fov_amount,doc_charges,doc_amount,cartage_rate,cartage_amount,labour_handling_rate,labour_handling_amount,octroi_rate,octroi_amount,other_charge_rate,other_charge_amount,rajdhani_charges,gst_rate,gst_amount,gst_type,gst_tax_id,gst_tax_code,cgst_rate,sgst_rate,igst_rate,cess_rate,cgst_amount,sgst_amount,igst_amount,cess_amount,taxable_value,bill_to_state_id,total,paid_amount, balance, paid_status,total_words,note1,note2,truck,vehicle_purchase_contact_person,
quotation_approval,
highload_challan,consigner_signature,client_id,created_at,created_by,updated_at,updated_by,status,eway_number,eway_expirydate,vehicle_type,
freight_paid_by,
insurance_number,
lc_number,
cfs,
mamul_charge,
vehicle_halting_charge,
vehicle_loading_unloading,other_train_name) values('" . $grn_num1 . "','" . $id_01 . "','" . $grn_date . "','" . $booking_time . "','" . $mode_of_trasport . "','$train_name','$ftl_type','" . $origin . "','" . $destination . "','" . $mode_of_consignment . "','" . $consignor . "','" . $address1 . "','" . $address2 . "','" . $city . "','" . $pincode . "','" . $state . "','" . $phone . "','" . $gst_no . "','" . $consignee . "','" . $con_address1 . "','" . $con_address2 . "','$ship_address','$shipping_address_name', '$shipping_gst_no', '$shipping_phone','" . $con_city . "','" . $con_state . "','" . $con_pincode . "','" . $con_phone . "','" . $con_gst . "','" . $goods_dedared_value . "','" . $bill_to . "',
'" . $supplier_invoice_value . "',
'" . $description_of_goods . "','" . $octroi . "','$len','$wid','$hei','$quanti','$volumetric_weight','$vlm_wei','" . $frieght_rate . "','" . $frieght_amount . "','" . $loading_unload_rate . "','" . $loading_unload_chrg . "','" . $crane_forklift_rate . "','" . $crane_forklift_chrg . "','" . $cod_rate . "','" . $cod_amount . "','" . $fov_rate . "','" . $fov_amount . "','" . $doc_rate . "','" . $doc_amount . "','" . $cartage_rate . "','" . $cartage_amount . "','" . $labour_rate . "','" . $labour_amount . "','" . $octroi_rate . "','" . $octroi_amount . "','" . $other_rate . "','" . $other_amount . "','$rajdhani_charges','" . $gst_rate . "','" . $gst_amount . "','$gst_type','$gst_tax_id','$gst_tax_code','$cgst_rate','$sgst_rate','$igst_rate','$cess_rate','$cgst_amount','$sgst_amount','$igst_amount','$cess_amount','$taxable_value','$bill_to_state_id','" . $total . "','0','" . $total . "','0','" . $amount_in_words . "','" . $note1 . "','" . $note2 . "','" . $vehicle_no . "','" . $vehicle_purchase_contact_person . "',
'" . $quotation_approval . "',
'" . $highload_challan . "','" . $signature . "','" . $consignor . "','" . $created_at . "','" . $created_by . "','" . $updated_at . "','" . $updated_by . "','1','" . $eway_number . "','$eway_expiryDate','$vehicle_type',
'$freight_paid_by',
'$insurance_number',
'$lc_number',
'$cfs',
'$mamul_charge',
'$vehicle_halting_charge',
'$vehicle_loading_unloading','$other_train_names')";
        // $result = mysqli_query($conn, $query) or die(mysqli_error($conn));
        $result = mysqli_query($conn, $query);
        $transaction_id = mysqli_insert_id($conn);
        if (!$result) {
            $out_put['result'] = '0';
            $out_put['sql_error'] = mysqli_error($conn);
            ob_clean();
            echo json_encode($out_put);
            // exit;
        }
//         $attachment_id = NULL;
// $invoice_id    = NULL;
$attachment_id = null;
$invoice_id = 0;
        if ($result) {
            for ($k = 0; $k < count($_FILES['file_receipt']['name']); $k++) {
                $file_name = uniqid() . $_FILES['file_receipt']['name'][$k];
                if (move_uploaded_file($_FILES['file_receipt']['tmp_name'][$k], 'invoice_image/' . $file_name)) {  // images/

                    $table1 = $tables[1];
                    $fr_query = "insert into $table1 (transaction_id,attachment,created_at,created_by,status) values ('$transaction_id','$file_name','$created_at','$created_by','0')";
                    // $fr_result = mysqli_query($conn, $fr_query) or die(mysqli_error($conn));
                    $fr_result = mysqli_query($conn, $fr_query);
                    if (!$fr_result) {
                        // echo "trans_image_insert_error";
                    }
                    // $attachment_id = mysqli_insert_id($conn);
                    if ($fr_result) {
    $attachment_id = mysqli_insert_id($conn);
}
                }
            }
        } else {
            // echo "transaction_insert_error";
        }
        // $total_pkg=0;

        for ($j = 0; $j < count($_POST['no_of_pkg']); $j++) {
            $table2 = $tables[2];
            $no_of_pkgs1 = $no_of_pkg[$j];
            $type_of_pkgs1 = $type_of_pkg[$j];
            $party_invoices1 = $party_invoice[$j];
            $contents1 = $content[$j];
            $qtys1 = $qty[$j];
            $grosss1 = $gross[$j];
            $chargeds1 = $charged[$j];

            // Skip entirely blank rows
            if ($no_of_pkgs1 === '' && $type_of_pkgs1 === '' && $party_invoices1 === '' && $qtys1 === '' && $grosss1 === '') {
                continue;
            }
            $party_invoice_dates1 = isset($_POST['party_invoice_date'][$j])
                ? trim($_POST['party_invoice_date'][$j])
                : '';

            if (!empty($party_invoice_dates1)) {
                $party_invoice_dates1 = date(
                    'Y-m-d',
                    strtotime(str_replace('/', '-', $party_invoice_dates1))
                );
            } else {
                $party_invoice_dates1 = null;
            }
            $f_query = "insert into $table2 (transaction_id,no_of_pkge,type_of_pkge,party_invoice_no,party_invoice_date,said_contents,qty,gross_weight,charged_weight,created_at,created_by,status) values('" . $transaction_id . "','" . $no_of_pkgs1 . "','" . $type_of_pkgs1 . "','" . $party_invoices1 . "','" . $party_invoice_dates1 . "','" . $contents1 . "','" . $qtys1 . "','" . $grosss1 . "','" . $chargeds1 . "','" . $created_at . "','" . $created_by . "','0')";
            // $f_result = mysqli_query($conn, $f_query) or die(mysqli_error($conn));
            $f_result = mysqli_query($conn, $f_query);
            if (!$f_result) {
                // echo "trans_invoice_insert_error";
                $f_result = mysqli_query($conn, $f_query) or die(mysqli_error($conn));
            }

            $package[] = $no_of_pkg[$j];  // Get the package number
            $pkg_name[] = $type_of_pkg[$j];
        }

        // Qrcode Start
        // require 'vendor/autoload.php'; For Barcode
        include('libs/phpqrcode/qrlib.php');
        $result_bar = [];

        foreach ($pkg_name as $index => $val) {
            $result_bar[$val] = ($result_bar[$val] ?? 0) + $package[$index];
        }
        $package_type1 = (array_keys($result_bar));
        $packge_qty = (array_values($result_bar));
        $name = $grn_num1;

        foreach ($packge_qty as $key => $val) {
            $get_qty = $val;
            // var_dump($get_qty);
            // echo "KEY".$key. "value". $val;
            if (array_key_exists($key, $package_type1)) {
                $get_package = $package_type1[$key];
                // var_dump($get_package);

                switch ($get_package) {
                    case '1':
                        $pack_name = 'CBX';
                        break;
                    case '2':
                        $pack_name = 'PBG';
                        break;
                    case '3':
                        $pack_name = 'ROL';
                        break;
                    case '5':
                        $pack_name = 'SHT';
                        break;
                    case '6':
                        $pack_name = 'BDL';
                        break;
                    case '7':
                        $pack_name = 'CVR';
                        break;
                    case '8':
                        $pack_name = 'PBL';
                        break;
                    case '9':
                        $pack_name = 'CAN';
                        break;
                    case '10':
                        $pack_name = 'BOX';
                        break;
                    case '11':
                        $pack_name = 'BAG';
                        break;
                    case '12':
                        $pack_name = 'MLD';
                        break;
                    case '13':
                        $pack_name = 'PKT';
                        break;
                    case '14':
                        $pack_name = 'CES';
                        break;
                    case '15':
                        $pack_name = 'CAT';
                        break;
                    case '16':
                        $pack_name = 'GRL';
                        break;
                    case '17':
                        $pack_name = 'P.B';
                        break;
                    case '18':
                        $pack_name = 'PRL';
                        break;
                    default:
                        $pack_name = 'No Package Type Found!';
                }

                // $productData = "098{$get_qty}10{$name}55{$rate}";
                $tempDir = 'qrcode/';
                $productData = strtoupper($name);
                $j = 1;
                for ($i = 0; $i < $get_qty; $i++) {
                    $change_index[$j] = $i + 1;
                    $names = $productData . $pack_name . '-00' . $change_index[$j];
                    $contents = 'https://elitewave360.in/web/testqrcode.php?grn_no=' . $name . '&grn_date=' . $grn_date;
                    // var_dump($names);
                    // Barcode
                    // file_put_contents('barcode/'.$names.'.jpg', $generator->getBarcode($names, $generator::TYPE_CODE_128,3,100,$redColor));

                    // Qrcode
                    $qr = QRcode::png($contents, $tempDir . '' . $names . '.png', QR_ECLEVEL_L, 5);
                }
            }
        }
        // Qrcode End

        $invoice_id = mysqli_insert_id($conn);

        if ($transaction_id) {
            if ($_SESSION['company_id'] != '') {
                $log_client_id = ($_SESSION['company_id'] != '') ? $grn_client_id : 0;
                if (empty($attachment_id)) {
    $attachment_id = null;
}

if (empty($invoice_id)) {
    $invoice_id = 0;
}

$attachment_sql = ($attachment_id === null)
    ? "NULL"
    : (int)$attachment_id;

//     echo "<pre>";
// var_dump($attachment_id);
// echo "</pre>";
// exit;
                $query_log = mysqli_query($conn, "INSERT INTO transaction_log
    (transaction_id, attachment_id, invoice_id, grn_id, grn_no, client_id, grn_type, company_id, tracking_code)
    VALUES ('$transaction_id',$attachment_sql,'$invoice_id','$id_01','$grn_num1','$log_client_id','$grn_type_db','$comp_id','$tracking_code')");

    if (!$query_log) {
    die(mysqli_error($conn));
}
            } else {

            $attachment_sql = ($attachment_id === null)
    ? "NULL"
    : (int)$attachment_id;

//     echo "<pre>";
// var_dump($attachment_id);
// echo "</pre>";
// exit;

                $query_log = mysqli_query($conn, "UPDATE transaction_log SET transaction_id='$transaction_id',attachment_id=$attachment_sql,invoice_id='$invoice_id',grn_id='$id_01',grn_no='$grn_num1',grn_type='" . $grn_type_db . "',company_id='" . $comp_id . "',tracking_code='" . $tracking_code . "' WHERE client_id='0'");

                if (!$query_log) {
    die(mysqli_error($conn));
}
            }

            $inv = '';
            for ($i = 0; $i < count($party_invoice); $i++) {
                if ($party_invoice[$i] != '') {
                    $inv .= $party_invoice[$i] . ',';
                }
            }
            $inv = rtrim($inv, ',');
            $url = 'https://elitewave360.in/web/transaction_pdf.php?month=' . $month . '&year=' . $year . '&id=' . $transaction_id . '&copy=consignor';
            $path = 'transaction_pdf/' . $month . '_' . $year . '_' . $transaction_id . 'transaction.pdf';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec($ch);
            curl_close($ch);
            $result_url = file_put_contents($path, $data);

            // *Invoice Section Start
            // Sequence Generation

            if ($mode_of_trasport == '1' || $mode_of_trasport == '2' || $mode_of_trasport == '3') {
                $type = 'GST';
                // $sac = "996812";
                // $sac_text = '996812 - COURIER SERVICES';
            } else {
                $type = 'GTA';
                // $sac = "9965";
                // $sac_text = '9965 - Good Transport Agency Service';
            }
            // $conn1 = mysqli_connect("localhost","root","","bookconsignment");

            $grn_date_expl = explode('-', $grn_date);
            $cur_year = $grn_date_expl[2];

            $current_year = $cur_year;

            $previous_year = $cur_year - 1;

            $p_y = substr($previous_year, 2);
            $c_y = substr($current_year, 2);

            $year_insert = $p_y . '-' . $c_y;

            $invoice_table = invoice_table_function($conn, $grn_date);

            $select = mysqli_query($conn, 'select * from ' . $invoice_table);
            $get_count = mysqli_num_rows($select);
            if ($get_count == 0) {
                $insert_data = 'INSERT INTO ' . $invoice_table . "(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('0','HRGST','$year_insert','GST','$created_at','$created_by'),('0','HRGTA','$year_insert','GTA','$created_at','$created_by')";
                // $insert_data .= "INSERT INTO ".$invoice_table."(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('1','HRGTA','$year_insert','GTA','$created_at','$created_by')";
                // $res = mysqli_multi_query($conn,$insert_data);
                $res = mysqli_query($conn, $insert_data);
                if ($res) {
                    $inv_query = 'select * from trans_invoice_tbl' . $year . " where inv_type='$type'";
                    $inv_query_result = mysqli_query($conn, $inv_query);
                    $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                    $inv_seq = $inv_query_row['invoice_no'] + 1;
                    // print_r($inv_seq);
                    // $inv_seq = '100';
                    $inv_text = $inv_query_row['gst_text'];
                    $inv_year = $inv_query_row['gst_year'];
                    $sequence = sprintf('%05d', $inv_seq);
                    $unique_invoice_no = $inv_text . '/' . $sequence . '/' . $inv_year;
                    // print_r($unique_invoice_no);
                }
            } else {
                $inv_query = 'select * from trans_invoice_tbl' . $year . " where inv_type='$type'";
                $inv_query_result = mysqli_query($conn, $inv_query);
                $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                $inv_seq = $inv_query_row['invoice_no'] + 1;
                $inv_text = $inv_query_row['gst_text'];
                $inv_year = $inv_query_row['gst_year'];
                $sequence = sprintf('%05d', $inv_seq);
                $unique_invoice_no = $inv_text . '/' . $sequence . '/' . $inv_year;
            }

            // Sequence Generation

            $directory = 'digital_invoice/';
            $invoice_url = 'https://elitewave360.in/web/gst_invoice_page.php?month=' . $month . '&year=' . $year . '&id=' . $transaction_id . '&invoice_no=' . $unique_invoice_no . '';
            $invoice_file_name = $month . '_' . $year . '_' . $transaction_id . 'invoice';
            $download_path = $directory . $invoice_file_name . '.pdf';
            $file_inv_download = curl_init($invoice_url);
            curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($file_inv_download, CURLOPT_REFERER, true);
            curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
            $store_inv = curl_exec($file_inv_download);
            curl_close($file_inv_download);
            $save_inv_file = file_put_contents($download_path, $store_inv);
            if ($save_inv_file) {
                $update = mysqli_query($conn, 'update trans_invoice_tbl' . $year . " SET invoice_no = '$inv_seq', updated_by = '$updated_by', updated_at = '$updated_at' where inv_type = '$type'");
                $tables_0 = $tables[0];
                $query_inv = "update $tables_0 set `invoice_no` = '$unique_invoice_no' where transaction_id ='$transaction_id'";
                $res = mysqli_query($conn, $query_inv);
            }

            // *Invoice Section End
            $image = array();
            $tables_1 = $tables[1];
            $img_query = mysqli_query($conn, "select * from $tables_1 where transaction_id ='" . $transaction_id . "'");
            if (mysqli_num_rows($img_query) > 0) {
                while ($img_result = mysqli_fetch_array($img_query)) {
                    array_push($image, 'invoice_image/' . $img_result['attachment']);
                }
            }
            // print_r($image);
            $msg = "<p style=\"line-height: 24px; margin-bottom:15px;\">

            Thank you for booking the consignment, please find the booking information and the attached GR copy for your reference below.\t\t\t\t
            <table width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
            <tr>
            <td >GCN No\t</td><td >" . $grn_num1 . "</td>
            </tr><tr>\t
            <td >GCN Date:\t</td><td >\t" . $grn_date . "\t</td>\t
            </tr>
            <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . "</td>\t</tr>\t
            <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . "</td>\t</tr>\t
            <tr>\t\t
            <td >Your Invoice No\t</td><td >\t" . $inv . "\t</td>\t
            </tr><tr>\t\t
            <td >Status\t</td><td >Consignment Booked</td>\t\t
                </td>
                                </tr>
                            </table>\t
            <br>
            <br>

            </p>";
            $to_name = array();
            $to_email = array();

            if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                // sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
                array_push($to_email, get_client_email($conn, $consignor), get_client_email($conn, $consignee));
                array_push($to_name, get_client_name($conn, $consignor), get_client_name($conn, $consignee));

                $mail = sendAttachments($to_name, $to_email, 'Consignment Booking Notification', $path, $image, $msg, $name);
//                 var_dump($mail);
// exit;
                // send sms
                // 			if ($phone != '' && $con_phone !='') {
                //                 if (strstr($phone, '+91')) {
                //                     $consigner_no  =  $phone;
                //                 } else {
                //                     $consigner_no  =  "+91" . $phone;
                //                 }

                //                 if (strstr($con_phone, '+91')) {
                //                     $consignee_no  =  $con_phone;
                //                 } else {
                //                     $consignee_no  =  "+91" . $con_phone;
                //                 }

                //                 $sms_number = array();
                //                 array_push($sms_number, $consigner_no);
                //                 array_push($sms_number, $consignee_no);

                //                 $consignor_name = get_client_name($conn, $consignor);
                //                 $consignor_name_wrap = strlen($consignor_name) > 27 ? substr($consignor_name,0,27)."..." : $consignor_name;
                //                 $grno_date = $grn_num1.' - '.$grn_date;
                //                 try{
                //                         $message_created = $client->messages->create([
                //                             'src' => "GRACIX",
                //                             "dst" => $sms_number,
                //                             "text"  => "Your shipment has been successfully booked.\nHere are the details:\nConsignor Name: $consignor_name_wrap\nGR No & Date: $grno_date\n\nThank you for choosing Elite Wave 360 for your shipment. Have a great day!",
                //                             "dlt_entity_id"=>"1201168767372626314",
                //                             "dlt_template_id"=>"1207169175728319365",
                //                             "dlt_template_category"=>"service_implicit",
                //                         ]);
                //                 }catch(Exception $err){
                //                     $error =  $err->getMessage();
                //                 }
                //            }
                // send sms
            }

            // *Send Invoice Instanly
            if ($mode_of_consignment == '3' || $mode_of_consignment == '4') {
                if ($mode_of_consignment == '3') {  // Pay at Booking

                    $check_partywise_frq = checkPartyWiseFrequency($conn, $consignor);  // Check Frequency set or not
                    if ($check_partywise_frq == 0) {  // Frequncy is Set
                        // Invoice Sent as per frequency
                        // echo "Frequency is Set";
                    } else {
                        // Other Process Goes here
                        $check_restricted = check_invoice_restricted($conn, $consignor);
                        if ($check_restricted == 0) {
                            // $msg = '<p style="line-height: 24px; margin-bottom:15px;">
                            // 				Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
                            // 				Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email.
                            // 				<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
                            // 				<tr>
                            // 				<td >GCN No	</td><td >' . $grn_num1 . '</td>
                            // 				</tr><tr>
                            // 				<td >GCN Date:	</td><td >	' . $grn_date . '	</td>
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

                            // Need to Send Payment Link to User

                            // }
                        } else {
                            // echo "Restricted Client";
                        }
                    }
                } else {  // Cash on Delivery

                    //                 //$check_partywise_frq = checkPartyWiseFrequency($conn, $consignee); // Check Frequency set or not
                    //                 // if ($check_partywise_frq == 0) { // Frequncy is Set
                    //                 //     //Invoice Sent as per frequency
                    //                 //     echo "Frequency is Set";
                    //                 // } else {
                    //                 // $outstanding = SetOutStandingInfo($conn, $consignee, $total); //Set Outstanding For COD

                    //                 // $check_restricted = check_invoice_restricted($conn, $consignee);
                    //                 // if ($check_restricted == 0) {

                    //                 //     //Need to create Payment Link for COD

                    //                 //     //End Payment Link

                    //                 $msg = '<p style="line-height: 24px; margin-bottom:15px;">
                    // 										Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
                    // 										Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email.
                    // 										<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
                    // 										<tr>
                    // 										<td >GCN No	</td><td >' . $grn_num1 . '</td>
                    // 										</tr><tr>
                    // 										<td >GCN Date:	</td><td >	' . $grn_date . '	</td>
                    // 										</tr>
                    // 										<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>
                    // 										<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>
                    // 										<tr>
                    // 										<td >Status	</td><td >Consignment Booked</td>
                    // 											</td>
                    // 															</tr>
                    // 														</table>
                    // 										<br>
                    // 										<br>';

                    //                 $to_name = array();
                    //                 $to_email = array();

                    //                 if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                    //                     //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)

                    //                     array_push($to_email, get_client_email($conn, $consignee), get_client_email($conn, $consignor));
                    //                     array_push($to_name, get_client_name($conn, $consignee), get_client_name($conn, $consignor));

                    //                     $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $download_path, $image, $msg, $name);
                    //                 }
                    //                 // } else {

                    //                 //     //echo "Restricted Client";

                    //                 // }
                    //                 // }
                }
            } else {
                // Payment Mode 1 and 2

                if ($mode_of_consignment == 1) {  // To Pay

                    // echo "Consignee";
                    $outstanding = SetOutStandingInfo($conn, $consignee, $total);
                } else {  // By Sender

                    // echo "Consignor";
                    $outstanding = SetOutStandingInfo($conn, $consignor, $total);
                }
            }
            // *End

            $out_put['result'] = 1;
            $out_put['data'] = $grn_num1;
            $out_put['tracking_code'] = $tracking_code;
        } else {
            $out_put['result'] = '0';
        }
    } else {
        $out_put['logout'] = 1;
    }

    echo json_encode($out_put);
}

// Consignment Booking manual
if ($form_name == 'add_new_consignment_manual') {
    $out_put = array();
    extract($_POST);
    if ($_SESSION['company_id'] != '') {
        // Fetch active company details
        $company_query = mysqli_query($conn, 'SELECT company_id, company_code, grn_mode FROM company WHERE status=0 LIMIT 1');
        $company_row = mysqli_fetch_array($company_query);
        $comp_id = isset($company_row['company_id']) ? $company_row['company_id'] : 2;
        $comp_code = isset($company_row['company_code']) ? $company_row['company_code'] : '';
        $comp_grn_mode = isset($company_row['grn_mode']) ? $company_row['grn_mode'] : 'company';

        if ($comp_grn_mode === 'company') {
            $grn_type_db = 'company';
        } else {
            $grn_type_db = 'party';
        }

        // Generate unique 12-digit numeric tracking code
        $tracking_code = '';
        do {
            $tracking_code = '';
            for ($i = 0; $i < 12; $i++) {
                $tracking_code .= rand(0, 9);
            }
            $chk_unique = mysqli_query($conn, "SELECT id FROM transaction_log WHERE tracking_code='$tracking_code'");
        } while (mysqli_num_rows($chk_unique) > 0);

        $other_train_names = ($other_train_name != '') ? $other_train_name : null;  // other train name

        $tables = get_trans_table_name($conn, $grn_date);
        $get_m_y = explode('_', $tables[0]);
        $month = $get_m_y[1];
        $year = $get_m_y[2];

        $consignorquery = "select * from client where client_id='$consignor'";
        $consignorresult = mysqli_query($conn, $consignorquery);
        $consignorrow = mysqli_fetch_array($consignorresult);
        $billing_code = $consignorrow['billing_code'];
        $address1 = $consignorrow['address1'];
        $address2 = $consignorrow['address2'];
        $city = $consignorrow['city'];
        $pincode = $consignorrow['pincode'];
        $state = $consignorrow['state'];
        $phone = $consignorrow['contact_no'];
        $gst_no = $consignorrow['gst_no'];

        // Get Latest GCN No
        // $query_max = mysqli_query($conn, "select * from transaction_log where client_id='$consignor'"); //duplicate-1
        // $r_max = mysqli_fetch_array($query_max);
        // $id_01 = $r_max['grn_id'] + 1;
        $id_01 = get_next_grn_id($conn, $comp_grn_mode === 'company' ? 'COMPANY' : $consignor);
        // $grn_num1 = strtoupper($billing_code . sprintf("%05d", $id_01));
        $grn_num1 = $grn_no;

        $consigneequery = "select * from client where client_id='$consignee'";
        $consigneeresult = mysqli_query($conn, $consigneequery);
        $consigneerow = mysqli_fetch_array($consigneeresult);

        $con_address1 = $consigneerow['address1'];
        $con_address2 = $consigneerow['address2'];
        $con_city = $consigneerow['city'];
        $con_state = $consigneerow['state'];
        $con_pincode = $consigneerow['pincode'];
        $con_phone = $consigneerow['contact_no'];
        $con_gst = $consigneerow['gst_no'];
        $ftl_type = $_POST['truck_type'];

        // Train type and Charges
        $train_name = $_POST['train_name'];
        $rajdhani_charges = $_POST['rajdhani_charges'];

        // Volumetric Values
        $len = $_POST['length'] ? $_POST['length'] : null;
        $wid = $_POST['width'] ? $_POST['width'] : null;
        $hei = $_POST['height'] ? $_POST['height'] : null;
        $quanti = $_POST['quanti'] ? $_POST['quanti'] : null;
        $vlm_wei = $_POST['vlm_weight'] ? $_POST['vlm_weight'] : null;

        // Shipping Address
        $ship_address = $_POST['shipping_address'] ? $_POST['shipping_address'] : null;
        $shipping_address_name = isset($_POST['shipping_address_name']) ? $_POST['shipping_address_name'] : '';
        $shipping_gst_no = isset($_POST['shipping_gst_no']) ? $_POST['shipping_gst_no'] : '';
        $shipping_phone = isset($_POST['shipping_phone']) ? $_POST['shipping_phone'] : '';

        // Eway Expiry
        $eway_expiryDate = $_POST['eway_expiryDate'] ? $_POST['eway_expiryDate'] : null;
        $table0 = $tables[0];

        $query = "insert into $table0 (grn_no,grn_date,mode_of_transportation,train_type,ftl_type,origin,destination,mode_of_consignment,consigner,address1,address2,city,pincode,state,phone,gst_no,consignee,con_address1,con_address2,shipping_address,shipping_address_name, shipping_gst_no, shipping_phone,con_city,con_state,con_pincode,con_phone,con_gst_no,goods_dedared_value,octroi,dimension1,dimension2,dimension3,dimension4,consignment_weight,frieght_rate,frieght_amount,loading_unloading_rate,
            loading_unloading_amount, crane_fork_lift_rate, crane_fork_lift_amount,cod_rate,cod_amount,fov_rate,fov_amount,doc_charges,doc_amount,cartage_rate,cartage_amount,labour_handling_rate,labour_handling_amount,octroi_rate,octroi_amount,other_charge_rate,other_charge_amount,rajdhani_charges,gst_rate,gst_amount,total,paid_amount, balance, paid_status,total_words,note1,note2,truck,consigner_signature,client_id,created_at,created_by,status,eway_number,eway_expirydate,vehicle_type,
freight_paid_by,
insurance_number,
lc_number,
cfs,
mamul_charge,
vehicle_halting_charge,
vehicle_loading_unloading,other_train_name,book_manual) values('" . $grn_num1 . "','" . $grn_date . "','" . $mode_of_trasport . "','$train_name','$ftl_type','" . $origin . "','" . $destination . "','" . $mode_of_consignment . "','" . $consignor . "','" . $address1 . "','" . $address2 . "','" . $city . "','" . $pincode . "','" . $state . "','" . $phone . "','" . $gst_no . "','" . $consignee . "','" . $con_address1 . "','" . $con_address2 . "','$ship_address','$shipping_address_name', '$shipping_gst_no', '$shipping_phone','" . $con_city . "','" . $con_state . "','" . $con_pincode . "','" . $con_phone . "','" . $con_gst . "','" . $goods_dedared_value . "','" . $octroi . "','$len','$wid','$hei','$quanti','$vlm_wei','" . $frieght_rate . "','" . $frieght_amount . "','" . $loading_unload_rate . "','" . $loading_unload_chrg . "','" . $crane_forklift_rate . "','" . $crane_forklift_chrg . "','" . $cod_rate . "','" . $cod_amount . "','" . $fov_rate . "','" . $fov_amount . "','" . $doc_rate . "','" . $doc_amount . "','" . $cartage_rate . "','" . $cartage_amount . "','" . $labour_rate . "','" . $labour_amount . "','" . $octroi_rate . "','" . $octroi_amount . "','" . $other_rate . "','" . $other_amount . "','$rajdhani_charges','" . $gst_rate . "','" . $gst_amount . "','" . $total . "','" . $total . "','0','1','" . $amount_in_words . "','" . $note1 . "','" . $note2 . "','" . $vehicle_no . "','" . $signature . "','" . $consignor . "','" . $created_at . "','" . $created_by . "','1','" . $eway_number . "','$eway_expiryDate','$vehicle_type',
'$freight_paid_by',
'$insurance_number',
'$lc_number',
'$cfs',
'$mamul_charge',
'$vehicle_halting_charge',
'$vehicle_loading_unloading','$other_train_names',2)";

        $result = mysqli_query($conn, $query);
        $transaction_id = mysqli_insert_id($conn);
        $attachment_id = NULL;
$invoice_id    = NULL;
        if ($result) {
            for ($k = 0; $k < count($_FILES['file_receipt']['name']); $k++) {
                $file_name = uniqid() . $_FILES['file_receipt']['name'][$k];
                if (move_uploaded_file($_FILES['file_receipt']['tmp_name'][$k], 'invoice_image/' . $file_name)) {  // images/

                    $table1 = $tables[1];
                    $fr_query = "insert into $table1 (transaction_id,attachment,created_at,created_by,status) values ('$transaction_id','$file_name','$created_at','$created_by','0')";
                    $fr_result = mysqli_query($conn, $fr_query);
                    if (!$fr_result) {
                        // echo "trans_image_insert_error";
                    }
                    $attachment_id = mysqli_insert_id($conn);
                }
            }
        }

        // status select while booking start
        $sheetq = 'SELECT max(sheet_id) AS id FROM transaction_status';
        $sheetres = mysqli_query($conn, $sheetq) or die(mysqli_error($conn));
        $sheetr = mysqli_fetch_array($sheetres);
        $sheet_id = $sheetr['id'] + 1;
        $sheet_no = 'SN/' . sprintf('%04d', $sheet_id);
        $c_date = date('d-m-Y H:i:s A');
        $insq1 = "INSERT INTO `transaction_status`(`sheet_id`,`sheet_no`, `status`, `created_at`, `created_by`) VALUES ('$sheet_id','$sheet_no','$status','$c_date','$created_by')";
        $insr1 = mysqli_query($conn, $insq1);

        $insq = "INSERT INTO `transaction_status_log`(`sheet_id`, `grn_no`, `from_status`, `to_status`,`client_id`,`updated_at`, `updated_by`) VALUES ('$sheet_id','$grn_num1','1','$status','$consignor','$created_at','$created_by')";
        $insr = mysqli_query($conn, $insq);

        $status_upd_query = "UPDATE $table0 SET `status`='$status' WHERE grn_no='$grn_num1' AND client_id='$consignor'";
        $results = mysqli_query($conn, $status_upd_query);

        // status select while booking end

        for ($j = 0; $j < count($_POST['no_of_pkg']); $j++) {
            $table2 = $tables[2];
            $no_of_pkgs1 = $no_of_pkg[$j];
            $type_of_pkgs1 = $type_of_pkg[$j];
            $party_invoices1 = $party_invoice[$j];
            $contents1 = $content[$j];
            $qtys1 = $qty[$j];
            $grosss1 = $gross[$j];
            $chargeds1 = $charged[$j];
            $f_query = "insert into $table2 (transaction_id,no_of_pkge,type_of_pkge,party_invoice_no,said_contents,qty,gross_weight,charged_weight,created_at,created_by,status) values('" . $transaction_id . "','" . $no_of_pkgs1 . "','" . $type_of_pkgs1 . "','" . $party_invoices1 . "','" . $contents1 . "','" . $qtys1 . "','" . $grosss1 . "','" . $chargeds1 . "','" . $created_at . "','" . $created_by . "','0')";
            $f_result = mysqli_query($conn, $f_query);
            if (!$f_result) {
                // echo "trans_invoice_insert_error";
            }

            $package[] = $no_of_pkg[$j];  // Get the package number

            $pkg_name[] = $type_of_pkg[$j];
        }

        // Qrcode Start
        // require 'vendor/autoload.php'; For Barcode
        include('libs/phpqrcode/qrlib.php');
        $result_bar = [];

        foreach ($pkg_name as $index => $val) {
            $result_bar[$val] = ($result_bar[$val] ?? 0) + $package[$index];
        }
        $package_type1 = (array_keys($result_bar));
        $packge_qty = (array_values($result_bar));
        $name = $grn_num1;

        foreach ($packge_qty as $key => $val) {
            $get_qty = $val;
            if (array_key_exists($key, $package_type1)) {
                $get_package = $package_type1[$key];
                // var_dump($get_package);

                switch ($get_package) {
                    case '1':
                        $pack_name = 'CBX';
                        break;
                    case '2':
                        $pack_name = 'PBG';
                        break;
                    case '3':
                        $pack_name = 'ROL';
                        break;
                    case '5':
                        $pack_name = 'SHT';
                        break;
                    case '6':
                        $pack_name = 'BDL';
                        break;
                    case '7':
                        $pack_name = 'CVR';
                        break;
                    case '8':
                        $pack_name = 'PBL';
                        break;
                    case '9':
                        $pack_name = 'CAN';
                        break;
                    case '10':
                        $pack_name = 'BOX';
                        break;
                    case '11':
                        $pack_name = 'BAG';
                        break;
                    case '12':
                        $pack_name = 'MLD';
                        break;
                    case '13':
                        $pack_name = 'PKT';
                        break;
                    case '14':
                        $pack_name = 'CES';
                        break;
                    case '15':
                        $pack_name = 'CAT';
                        break;
                    case '16':
                        $pack_name = 'GRL';
                        break;
                    case '17':
                        $pack_name = 'P.B';
                        break;
                    case '18':
                        $pack_name = 'PRL';
                        break;
                    default:
                        $pack_name = 'No Package Type Found!';
                }

                $tempDir = 'qrcode/';
                $productData = strtoupper($name);
                $j = 1;
                for ($i = 0; $i < $get_qty; $i++) {
                    $change_index[$j] = $i + 1;
                    $names = $productData . $pack_name . '-00' . $change_index[$j];
                    $contents = 'https://elitewave360.in/web/testqrcode.php?grn_no=' . $name . '&grn_date=' . $grn_date;

                    // Qrcode
                    QRcode::png($contents, $tempDir . '' . $names . '.png', QR_ECLEVEL_L, 5);
                }
            }
        }
        // Qrcode End

        $invoice_id = mysqli_insert_id($conn);

        if ($transaction_id) {
            $inv = '';
            for ($i = 0; $i < count($party_invoice); $i++) {
                if ($party_invoice[$i] != '') {
                    $inv .= $party_invoice[$i] . ',';
                }
            }
            $inv = rtrim($inv, ',');
            $url = 'https://elitewave360.in/web/transaction_pdf.php?month=' . $month . '&year=' . $year . '&id=' . $transaction_id . '&copy=consignor';
            $path = 'transaction_pdf/' . $month . '_' . $year . '_' . $transaction_id . 'transaction.pdf';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec($ch);
            curl_close($ch);
            $result_url = file_put_contents($path, $data);

            // *Invoice Section Start
            // Sequence Generation

            if ($mode_of_trasport == '1' || $mode_of_trasport == '2' || $mode_of_trasport == '3') {
                $type = 'GST';
            } else {
                $type = 'GTA';
            }

            $grn_date_expl = explode('-', $grn_date);
            $cur_year = $grn_date_expl[2];

            $current_year = $cur_year;

            $previous_year = $cur_year - 1;

            $p_y = substr($previous_year, 2);
            $c_y = substr($current_year, 2);

            $year_insert = $p_y . '-' . $c_y;

            $invoice_table = invoice_table_function($conn, $grn_date);

            $select = mysqli_query($conn, 'select * from ' . $invoice_table);
            $get_count = mysqli_num_rows($select);
            if ($get_count == 0) {
                $insert_data = 'INSERT INTO ' . $invoice_table . "(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('0','HRGST','$year_insert','GST','$created_at','$created_by'),('0','HRGTA','$year_insert','GTA','$created_at','$created_by')";
                $res = mysqli_query($conn, $insert_data);
                if ($res) {
                    $inv_query = 'select * from trans_invoice_tbl' . $year . " where inv_type='$type'";
                    $inv_query_result = mysqli_query($conn, $inv_query);
                    $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                    $inv_seq = $inv_query_row['invoice_no'] + 1;
                    $inv_text = $inv_query_row['gst_text'];
                    $inv_year = $inv_query_row['gst_year'];
                    $sequence = sprintf('%05d', $inv_seq);
                    $unique_invoice_no = $inv_text . '/' . $sequence . '/' . $inv_year;
                }
            } else {
                $inv_query = 'select * from trans_invoice_tbl' . $year . " where inv_type='$type'";
                $inv_query_result = mysqli_query($conn, $inv_query);
                $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                $inv_seq = $inv_query_row['invoice_no'] + 1;
                $inv_text = $inv_query_row['gst_text'];
                $inv_year = $inv_query_row['gst_year'];
                $sequence = sprintf('%05d', $inv_seq);
                $unique_invoice_no = $inv_text . '/' . $sequence . '/' . $inv_year;
            }

            // Sequence Generation

            $directory = 'digital_invoice/';
            $invoice_url = 'https://elitewave360.in/web/gst_invoice_page.php?month=' . $month . '&year=' . $year . '&id=' . $transaction_id . '&invoice_no=' . $unique_invoice_no . '';
            $invoice_file_name = $month . '_' . $year . '_' . $transaction_id . 'invoice';
            $download_path = $directory . $invoice_file_name . '.pdf';
            $file_inv_download = curl_init($invoice_url);
            curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($file_inv_download, CURLOPT_REFERER, true);
            curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
            $store_inv = curl_exec($file_inv_download);
            curl_close($file_inv_download);
            $save_inv_file = file_put_contents($download_path, $store_inv);
            if ($save_inv_file) {
                $update = mysqli_query($conn, 'update trans_invoice_tbl' . $year . " SET invoice_no = '$inv_seq', updated_by = '$updated_by', updated_at = '$updated_at' where inv_type = '$type'");
                $tables_0 = $tables[0];
                $query_inv = "update $tables_0 set `invoice_no` = '$unique_invoice_no' where transaction_id ='$transaction_id'";
                $res = mysqli_query($conn, $query_inv);
            }

            // *Invoice Section End

            $image = array();
            $tables_1 = $tables[1];
            $img_query = mysqli_query($conn, "select * from $tables_1 where transaction_id ='" . $transaction_id . "'");
            if (mysqli_num_rows($img_query) > 0) {
                while ($img_result = mysqli_fetch_array($img_query)) {
                    array_push($image, 'invoice_image/' . $img_result['attachment']);
                }
            }

            // *Send Invoice Instanly
            // if ($mode_of_consignment == '3' || $mode_of_consignment  == '4') {

            // } else {

            //     //Payment Mode 1 and 2

            //     if ($mode_of_consignment == 1) { // To Pay
            //         //echo "Consignee";
            //         $outstanding = SetOutStandingInfo($conn, $consignee, $total);
            //     } else { // By Sender
            //         //echo "Consignor";
            //         $outstanding = SetOutStandingInfo($conn, $consignor, $total);
            //     }
            // }
            // *End

            $out_put['result'] = 1;
            $out_put['data'] = $grn_num1;
        } else {
            $out_put['result'] = '0';
        }
    } else {
        $out_put['logout'] = 1;
    }

    echo json_encode($out_put);
}

if ($form_name == 'add_new_user_consignment') {
    $out_put = array();
    $edit_user_id = $_POST['edit_id'];
    extract($_POST);
    //	print_r($_POST);die;
    if (!empty($_SESSION['company_id'])) {
        // Fetch active company details
        $company_query = mysqli_query($conn, 'SELECT company_id, company_code, grn_mode FROM company WHERE status=0 LIMIT 1');
        $company_row = mysqli_fetch_array($company_query);
        $comp_id = isset($company_row['company_id']) ? $company_row['company_id'] : 2;
        $comp_code = isset($company_row['company_code']) ? $company_row['company_code'] : '';
        $comp_grn_mode = isset($company_row['grn_mode']) ? $company_row['grn_mode'] : 'company';

        if ($comp_grn_mode === 'company') {
            $grn_type_db = 'company';
        } else {
            $grn_type_db = 'party';
        }

        // Generate unique 12-digit numeric tracking code
        $tracking_code = '';
        do {
            $tracking_code = '';
            for ($i = 0; $i < 12; $i++) {
                $tracking_code .= rand(0, 9);
            }
            $chk_unique = mysqli_query($conn, "SELECT id FROM transaction_log WHERE tracking_code='$tracking_code'");
        } while (mysqli_num_rows($chk_unique) > 0);

        $other_train_names = ($other_train_name != '') ? $other_train_name : null;
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
                $query_max = mysqli_query($conn, 'select * from transaction_log where client_id=0');
                $r_max = mysqli_fetch_array($query_max);
                $id = $r_max['grn_id'] + 1;
                $grn_no = 'LA' . $_POST['grn_no1'];
            }
        }

        $consignorquery = "select * from client where client_id='$consignor'";
        $consignorresult = mysqli_query($conn, $consignorquery);
        $consignorrow = mysqli_fetch_array($consignorresult);
        $billing_code = $consignorrow['billing_code'];
        $address1 = $consignorrow['address1'];
        $address2 = $consignorrow['address2'];
        $city = $consignorrow['city'];
        $pincode = $consignorrow['pincode'];
        $state = $consignorrow['state'];
        $phone = $consignorrow['contact_no'];
        $gst_no = $consignorrow['gst_no'];

        // Get Latest GCN No
        $id = get_next_grn_id($conn, $consignor);
        $grn_no = strtoupper($billing_code . sprintf('%05d', $id));

        $consigneequery = "select * from client where client_id='$consignee'";
        $consigneeresult = mysqli_query($conn, $consigneequery);
        $consigneerow = mysqli_fetch_array($consigneeresult);

        $con_address1 = $consigneerow['address1'];
        $con_address2 = $consigneerow['address2'];
        $con_city = $consigneerow['city'];
        $con_state = $consigneerow['state'];
        $con_pincode = $consigneerow['pincode'];
        $con_phone = $consigneerow['contact_no'];
        $con_gst = $consigneerow['gst_no'];
        $ftl_type = $_POST['truck_type'];
        $guest_user_id = $_POST['edit_id'];

        // Train type and Charges
        $train_name = $_POST['train_name'];
        $rajdhani_charges = $_POST['rajdhani_charges'];

        // $frieght_weight = $_POST['weight1'];
        // $weight1 = $_POST['weight1'];
        // Volumetric Values
        $len = $_POST['length'] ? $_POST['length'] : null;
        $wid = $_POST['width'] ? $_POST['width'] : null;
        $hei = $_POST['height'] ? $_POST['height'] : null;
        $quanti = $_POST['quanti'] ? $_POST['quanti'] : null;
        $vlm_wei = $_POST['vlm_weight'] ? $_POST['vlm_weight'] : null;

        // Shipping Address
        $ship_address = $_POST['shipping_address'] ? $_POST['shipping_address'] : null;

        $eway_expiryDate = $_POST['eway_expiryDate'] ? $_POST['eway_expiryDate'] : null;

        $query = "insert into $tables[0](grn_no,grn_id,grn_date,mode_of_transportation,train_type,ftl_type,origin,destination,mode_of_consignment,consigner,address1,address2,city,pincode,state,phone,gst_no,consignee,con_address1,con_address2,shipping_address,shipping_address_name, shipping_gst_no, shipping_phone,con_city,con_state,con_pincode,con_phone,con_gst_no,goods_dedared_value,octroi,dimension1,dimension2,dimension3,dimension4,consignment_weight,frieght_rate,frieght_amount,loading_unloading_rate,
\t\t loading_unloading_amount, crane_fork_lift_rate, crane_fork_lift_amount,cod_rate,cod_amount,fov_rate,fov_amount,doc_charges,doc_amount,cartage_rate,cartage_amount,labour_handling_rate,labour_handling_amount,octroi_rate,octroi_amount,other_charge_rate,other_charge_amount,rajdhani_charges,gst_rate,gst_amount,total,paid_amount, balance, paid_status,total_words,note1,note2,truck,consigner_signature,client_id,created_at,created_by,status,eway_number,eway_expirydate,other_train_name) values('" . $grn_no . "','" . $id . "','" . $grn_date . "','" . $mode_of_trasport . "','$train_name','$ftl_type','" . $origin . "','" . $destination . "','" . $mode_of_consignment . "','" . $consignor . "','" . $address1 . "','" . $address2 . "','" . $city . "','" . $pincode . "','" . $state . "','" . $phone . "','" . $gst_no . "','" . $consignee . "','" . $con_address1 . "','" . $con_address2 . "','$ship_address','$shipping_address_name', '$shipping_gst_no', '$shipping_phone','" . $con_city . "','" . $con_state . "','" . $con_pincode . "','" . $con_phone . "','" . $con_gst . "','" . $goods_dedared_value . "','" . $octroi . "','$len','$wid','$hei','$quanti','$vlm_wei','" . $frieght_rate . "','" . $frieght_amount . "','" . $loading_unload_rate . "','" . $loading_unload_chrg . "','" . $crane_forklift_rate . "','" . $crane_forklift_chrg . "','" . $cod_rate . "','" . $cod_amount . "','" . $fov_rate . "','" . $fov_amount . "','" . $doc_rate . "','" . $doc_amount . "','" . $cartage_rate . "','" . $cartage_amount . "','" . $labour_rate . "','" . $labour_amount . "','" . $octroi_rate . "','" . $octroi_amount . "','" . $other_rate . "','" . $other_amount . "','$rajdhani_charges','" . $gst_rate . "','" . $gst_amount . "','" . $total . "','0','" . $total . "','0','" . $amount_in_words . "','" . $note1 . "','" . $note2 . "','" . $vehicle_no . "','" . $signature . "','" . $consignor . "','" . $created_at . "','" . $created_by . "','1','" . $eway_number . "','$eway_expiryDate','$other_train_names')";
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
            // $file_name = 'abc.jpg';
        }
        // for($k=0;$k<count($_FILES["file_receipt"]["name"]);$k++) {

        // $file_name = uniqid().$_FILES["file_receipt"]["name"][$k];
        // if(move_uploaded_file($_FILES["file_receipt"]["tmp_name"][$k], "invoice_image/".$file_name)){ //images/
        // }
        $attachment_id = NULL;
$invoice_id    = NULL;
        $fr_query = "insert into $tables[1](transaction_id,attachment,created_at,created_by,status) values ('$transaction_id','$file_name','$created_at','$created_by','0')";
        $fr_result = mysqli_query($conn, $fr_query) or die(mysqli_error($conn));
        $attachment_id = mysqli_insert_id($conn);
        // }
        // $total_pkg=0;

        for ($j = 0; $j < count($_POST['no_of_pkg']); $j++) {
            $f_query = "insert into $tables[2](transaction_id,no_of_pkge,type_of_pkge,party_invoice_no,said_contents,qty,gross_weight,charged_weight,created_at,created_by,status) values('" . $transaction_id . "','" . $no_of_pkg[$j] . "','" . $type_of_pkg[$j] . "','" . $party_invoice[$j] . "','" . $content[$j] . "','" . $qty[$j] . "','" . $gross[$j] . "','" . $charged[$j] . "','" . $created_at . "','" . $created_by . "','0')";
            $f_result = mysqli_query($conn, $f_query) or die(mysqli_error($conn));

            // $package[] = $qty[$j];
            $package[] = $no_of_pkg[$j];

            $pkg_name[] = $type_of_pkg[$j];

            // $total_pkg +=$_POST['no_of_pkg'];
        }

        // Barcode Start
        // require 'vendor/autoload.php'; For Barcode
        include('libs/phpqrcode/qrlib.php');
        $result_bar = [];

        foreach ($pkg_name as $index => $val) {
            $result_bar[$val] = ($result_bar[$val] ?? 0) + $package[$index];
        }
        $package_type1 = (array_keys($result_bar));
        $packge_qty = (array_values($result_bar));
        // $redColor = [0, 0, 0];
        // $generator = new Picqer\Barcode\BarcodeGeneratorJPG();
        $name = $grn_no;
        // var_dump($qty);

        // $rate = 10;

        foreach ($packge_qty as $key => $val) {
            $get_qty = $val;
            // var_dump($get_qty);
            // echo "KEY".$key. "value". $val;
            if (array_key_exists($key, $package_type1)) {
                $get_package = $package_type1[$key];
                // var_dump($get_package);

                switch ($get_package) {
                    case '1':
                        $pack_name = 'CBX';
                        break;
                    case '2':
                        $pack_name = 'PBG';
                        break;
                    case '3':
                        $pack_name = 'ROL';
                        break;
                    case '5':
                        $pack_name = 'SHT';
                        break;
                    case '6':
                        $pack_name = 'BDL';
                        break;
                    case '7':
                        $pack_name = 'CVR';
                        break;
                    case '8':
                        $pack_name = 'PBL';
                        break;
                    case '9':
                        $pack_name = 'CAN';
                        break;
                    case '10':
                        $pack_name = 'BOX';
                        break;
                    case '11':
                        $pack_name = 'BAG';
                        break;
                    case '12':
                        $pack_name = 'MLD';
                        break;
                    case '13':
                        $pack_name = 'PKT';
                        break;
                    case '14':
                        $pack_name = 'CES';
                        break;
                    case '15':
                        $pack_name = 'CAT';
                        break;
                    case '16':
                        $pack_name = 'GRL';
                        break;
                    case '17':
                        $pack_name = 'P.B';
                        break;
                    case '18':
                        $pack_name = 'PRL';
                        break;
                    default:
                        $pack_name = 'No Package Type Found!';
                }

                // $productData = "098{$get_qty}10{$name}55{$rate}";
                $tempDir = 'qrcode/';
                $productData = strtoupper($name);
                $j = 1;
                for ($i = 0; $i < $get_qty; $i++) {
                    $change_index[$j] = $i + 1;
                    $names = $productData . $pack_name . '-00' . $change_index[$j];
                    $contents = 'https://elitewave360.in/web/testqrcode.php?grn_no=' . $name . '&grn_date=' . $grn_date;
                    // var_dump($names);
                    // Barcode
                    // file_put_contents('barcode/'.$names.'.jpg', $generator->getBarcode($names, $generator::TYPE_CODE_128,3,100,$redColor));

                    // Qrcode
                    QRcode::png($contents, $tempDir . '' . $names . '.png', QR_ECLEVEL_L, 5);

                    $j++;
                }
            }
        }
        // Barcode End

        $invoice_id = mysqli_insert_id($conn);
        // echo $total_pkg;

        if ($transaction_id) {
            if ($_SESSION['company_id'] != '') {
                $log_client_id = ($_SESSION['company_id'] != '')
                    ? (($comp_grn_mode === 'company') ? $_SESSION['company_id'] : $consignor)
                    : $consignor;
                $query_log = mysqli_query($conn, "INSERT INTO transaction_log
    (transaction_id, attachment_id, invoice_id, grn_id, grn_no, client_id, grn_type, company_id, tracking_code)
    VALUES ('$transaction_id','$attachment_id','$invoice_id','$id','$grn_no','$log_client_id','$grn_type_db','$comp_id','$tracking_code')");

    if (!$query_log) {
    die(mysqli_error($conn));
}
            } else {
                $query_log = mysqli_query($conn, "UPDATE transaction_log SET transaction_id='$transaction_id',attachment_id='$attachment_id',invoice_id='$invoice_id',grn_id='$id',grn_no='$grn_no',grn_type='" . $grn_type_db . "',company_id='" . $comp_id . "',tracking_code='" . $tracking_code . "' WHERE client_id='$consignor'");
                if (!$query_log) {
    die(mysqli_error($conn));
}
            }

            $invi = '';
            for ($i = 0; $i < count($party_invoice); $i++) {
                if ($party_invoice[$i] != '') {
                    $invi .= $party_invoice[$i] . ',';
                }
            }

            $invi = rtrim($invi, ',');
            $url = 'https://elitewave360.in/web/transaction_pdf.php?month=' . $month . '&year=' . $year . '&id=' . $transaction_id . '&copy=consignor';
            $path = 'transaction_pdf/' . $month . '_' . $year . '_' . $transaction_id . 'transaction.pdf';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec($ch);
            curl_close($ch);
            $result_url = file_put_contents($path, $data);

            // *Invoice Section Start
            // Sequence Generation

            if ($mode_of_trasport == '1' || $mode_of_trasport == '2' || $mode_of_trasport == '3') {
                $type = 'GST';
                // $sac = "996812";
                // $sac_text = '996812 - COURIER SERVICES';
            } else {
                $type = 'GTA';
                // $sac = "9965";
                // $sac_text = '9965 - Good Transport Agency Service';
            }
            // $conn1 = mysqli_connect("localhost","root","","bookconsignment");

            $grn_date_expl = explode('-', $grn_date);
            $cur_year = $grn_date_expl[2];

            $current_year = $cur_year;

            $previous_year = $cur_year - 1;

            $p_y = substr($previous_year, 2);
            $c_y = substr($current_year, 2);

            $year_insert = $p_y . '-' . $c_y;

            $invoice_table = invoice_table_function($conn, $grn_date);

            $select = mysqli_query($conn, 'select * from ' . $invoice_table);
            $get_count = mysqli_num_rows($select);
            if ($get_count == 0) {
                $insert_data = 'INSERT INTO ' . $invoice_table . "(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('0','HRGST','$year_insert','GST','$created_at','$created_by'),('0','HRGTA','$year_insert','GTA','$created_at','$created_by')";
                // $insert_data .= "INSERT INTO ".$invoice_table."(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('1','HRGTA','$year_insert','GTA','$created_at','$created_by')";
                // $res = mysqli_multi_query($conn,$insert_data);
                $res = mysqli_query($conn, $insert_data);
                if ($res) {
                    $inv_query = 'select * from trans_invoice_tbl' . $year . " where inv_type='$type'";
                    $inv_query_result = mysqli_query($conn, $inv_query);
                    $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                    $inv_seq = $inv_query_row['invoice_no'] + 1;
                    // print_r($inv_seq);
                    // $inv_seq = '100';
                    $inv_text = $inv_query_row['gst_text'];
                    $inv_year = $inv_query_row['gst_year'];
                    $sequence = sprintf('%05d', $inv_seq);
                    $unique_invoice_no = $inv_text . '/' . $sequence . '/' . $inv_year;
                    // print_r($unique_invoice_no);
                }
            } else {
                $inv_query = 'select * from trans_invoice_tbl' . $year . " where inv_type='$type'";
                $inv_query_result = mysqli_query($conn, $inv_query);
                $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                $inv_seq = $inv_query_row['invoice_no'] + 1;
                // print_r($inv_seq);
                // $inv_seq = '100';
                $inv_text = $inv_query_row['gst_text'];
                $inv_year = $inv_query_row['gst_year'];
                $sequence = sprintf('%05d', $inv_seq);
                $unique_invoice_no = $inv_text . '/' . $sequence . '/' . $inv_year;
            }

            // Sequence Generation

            $directory = 'digital_invoice/';
            $invoice_url = 'https://elitewave360.in/web/gst_invoice_page.php?month=' . $month . '&year=' . $year . '&id=' . $transaction_id . '&invoice_no=' . $unique_invoice_no . '';
            $invoice_file_name = $month . '_' . $year . '_' . $transaction_id . 'invoice';
            $download_path = $directory . $invoice_file_name . '.pdf';
            $file_inv_download = curl_init($invoice_url);
            curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($file_inv_download, CURLOPT_REFERER, true);
            curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
            $store_inv = curl_exec($file_inv_download);
            curl_close($file_inv_download);
            $save_inv_file = file_put_contents($download_path, $store_inv);

            if ($save_inv_file) {
                $update = mysqli_query($conn, 'update trans_invoice_tbl' . $year . " SET invoice_no = '$inv_seq', updated_by = '$updated_by', updated_at = '$updated_at' where inv_type = '$type'");

                $query_inv = "update $tables[0] set `invoice_no` = '$unique_invoice_no' where transaction_id ='$transaction_id'";
                $res = mysqli_query($conn, $query_inv);
            }

            // $attachments = array($download_path,$path);
            // *Invoice Section End

            $image = array();
            $img_query = mysqli_query($conn, "select * from $tables[1] where transaction_id ='" . $transaction_id . "'");
            if (mysqli_num_rows($img_query) > 0) {
                while ($img_result = mysqli_fetch_array($img_query)) {
                    array_push($image, 'invoice_image/' . $img_result['attachment']);
                }
            }
            // print_r($image);
            $msg = "<p style=\"line-height: 24px; margin-bottom:15px;\">
\t\t\t\t\t\t  
\t\t\tThank you for booking the consignment, please find the booking information and the attached GR copy for your reference below.\t\t\t\t\t
\t\t\t<table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
\t\t\t<tr>
\t\t\t<td >GCN No\t</td><td >" . $grn_no . "</td>
\t\t\t</tr><tr>\t
\t\t\t<td >GCN Date:\t</td><td >\t" . $grn_date . "\t</td>\t
\t\t\t</tr>
\t\t\t<tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . "</td>\t</tr>\t
\t\t\t<tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . "</td>\t</tr>\t
\t\t\t<tr>\t\t
\t\t\t<td >Your Invoice No\t</td><td >\t" . $invi . "\t</td>\t
\t\t\t</tr><tr>\t\t
\t\t\t<td >Status\t</td><td >Consignment Booked.</td>\t\t
\t\t\t\t</td>
\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t</table>\t
\t\t\t<br>
\t\t\t<br>";
            $to_name = array();
            $to_email = array();

            if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                // sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
                array_push($to_email, get_client_email($conn, $consignor), get_client_email($conn, $consignee));
                array_push($to_name, get_client_name($conn, $consignor), get_client_name($conn, $consignee));

                $mail = sendAttachments($to_name, $to_email, 'Consignment Booking Notification', $path, $image, $msg, $name);

//                 var_dump($mail);
// exit;
                // echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst');
            }
            /*if(!empty(get_client_email($conn,$consignor))){
                    $mail = sendAppMail(get_client_name($conn,$consignor),get_client_email($conn,$consignor), 'Consignment Booking Notification | '.$grn_no.' To {'.get_client_name($conn,$consignee).'}', $msg);
            }
            if(!empty(get_client_email($conn,$consignee))){
                    $mail = sendAppMail(get_client_name($conn,$consignee),get_client_email($conn,$consignee), 'Consignment Booking Notification | '.$grn_no.' To {'.get_client_name($conn,$consignee).'}', $msg);
            }*/

            // *Send Invoice Instanly
            if ($mode_of_consignment == '3' || $mode_of_consignment == '4') {
                if ($mode_of_consignment == '3') {  // Pay at Booking

                    // $check_partywise_frq = checkPartyWiseFrequency($conn, $consignor); // Check Frequency set or not
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
                    // 						Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
                    // 						Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email.
                    // 						<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
                    // 						<tr>
                    // 						<td >GCN No	</td><td >' . $grn_no . '</td>
                    // 						</tr><tr>
                    // 						<td >GCN Date:	</td><td >	' . $grn_date . '	</td>
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
                } else {  // Cash on Delivery

                    //                 // $check_partywise_frq = checkPartyWiseFrequency($conn, $consignee); // Check Frequency set or not
                    //                 // if ($check_partywise_frq == 0) { // Frequncy is Set
                    //                 //     //Invoice Sent as per frequency
                    //                 //     echo "Frequency is Set";
                    //                 // } else {
                    //                 //     $outstanding = SetOutStandingInfo($conn, $consignee, $total); //Set Outstanding For COD

                    //                 //     $check_restricted = check_invoice_restricted($conn, $consignee);
                    //                 //     if ($check_restricted == 0) {

                    //                 //         //Need to createPayment Link

                    //                 //         //End Payment Link

                    //                 $msg = '<p style="line-height: 24px; margin-bottom:15px;">
                    // 										Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
                    // 										Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email.
                    // 										<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
                    // 										<tr>
                    // 										<td >GCN No	</td><td >' . $grn_no . '</td>
                    // 										</tr><tr>
                    // 										<td >GCN Date:	</td><td >	' . $grn_date . '	</td>
                    // 										</tr>
                    // 										<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>
                    // 										<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>
                    // 										<tr>
                    // 										<td >Status	</td><td >Consignment Booked</td>
                    // 											</td>
                    // 															</tr>
                    // 														</table>
                    // 										<br>
                    // 										<br>';

                    //                 $to_name = array();
                    //                 $to_email = array();

                    //                 if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                    //                     //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)

                    //                     array_push($to_email, get_client_email($conn, $consignee), get_client_email($conn, $consignor));
                    //                     array_push($to_name, get_client_name($conn, $consignee), get_client_name($conn, $consignor));

                    //                     $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $download_path, $image, $msg, $name);
                    //                 }
                    //                 //     } else {

                    //                 //         //echo "Restricted Client";

                    //                 //     }
                    //                 // }
                }
            } else {
                // Payment Mode 1 and 2

                if ($mode_of_consignment == 1) {  // To Pay

                    // echo "Consignee";
                    $outstanding = SetOutStandingInfo($conn, $consignee, $total);
                } else {  // By Sender

                    // echo "Consignor";
                    $outstanding = SetOutStandingInfo($conn, $consignor, $total);
                }
            }
            // *End

            // update user inquiry consignment list
            $update_user_inquiry = mysqli_query($conn, "UPDATE `user_inquiry_list` SET `status`='2' WHERE `user_id` = '$guest_user_id'");
            // End
            $out_put['result'] = 1;
            $out_put['data'] = $grn_no;
            $out_put['tracking_code'] = $tracking_code;
        } else {
            $out_put['result'] = '0';
        }
    } else {
        $out_put['logout'] = 1;
    }

    echo json_encode($out_put);
}

if ($form_name == 'edit_consignment_details') {
    $out_put = array();
    extract($_POST);
    // var_dump($_POST);
    $tables = get_trans_table_name($conn, $grn_date);
    $get_m_y = explode('_', $tables[0]);
    $month = $get_m_y[1];
    $year = $get_m_y[2];

    $other_train = "other_train_name='$other_train_name'";
    $other_train_names = ($other_train_name != '') ? $other_train : '';

    $consignorquery = "select * from client where client_id='$consignor'";
    $consignorresult = mysqli_query($conn, $consignorquery);
    $consignorrow = mysqli_fetch_array($consignorresult);

    $address1 = $consignorrow['address1'];
    $address2 = $consignorrow['address2'];
    $city = $consignorrow['city'];
    $pincode = $consignorrow['pincode'];
    $state = $consignorrow['state'];
    $phone = $consignorrow['contact_no'];
    $gst_no = $consignorrow['gst_no'];

    $consigneequery = "select * from client where client_id='$consignee'";
    $consigneeresult = mysqli_query($conn, $consigneequery);
    $consigneerow = mysqli_fetch_array($consigneeresult);

    $con_address1 = $consigneerow['address1'];
    $con_address2 = $consigneerow['address2'];
    $con_city = $consigneerow['city'];
    $con_state = $consigneerow['state'];
    $con_pincode = $consigneerow['pincode'];
    $con_phone = $consigneerow['contact_no'];
    $con_gst = $consigneerow['gst_no'];
    $grn_no = $_POST['grn_no'];
    $ftl_type = $_POST['truck_type'];
    $train_name = $_POST['train_name'];
    $rajdhani_charges = $_POST['rajdhani_charges'];

    // Volumetric Values
    $len = $_POST['length'] ? $_POST['length'] : null;
    $wid = $_POST['width'] ? $_POST['width'] : null;
    $hei = $_POST['height'] ? $_POST['height'] : null;
    $quanti = $_POST['quanti'] ? $_POST['quanti'] : null;
    $vlm_wei = $_POST['vlm_weight'] ? $_POST['vlm_weight'] : null;

    // Shipping Address
    $ship_address = $_POST['shipping_address'] ? $_POST['shipping_address'] : null;
    $shipping_address_name = isset($_POST['shipping_address_name']) ? $_POST['shipping_address_name'] : '';
    $shipping_gst_no = isset($_POST['shipping_gst_no']) ? $_POST['shipping_gst_no'] : '';
    $shipping_phone = isset($_POST['shipping_phone']) ? $_POST['shipping_phone'] : '';

    $eway_expiryDate = $_POST['eway_expiryDate'] ? $_POST['eway_expiryDate'] : null;
    $eway_number = $_POST['eway_number'] ? $_POST['eway_number'] : null;

    $vehicle_type = isset($_POST['vehicle_type']) ? $_POST['vehicle_type'] : null;
    $freight_paid_by = isset($_POST['freight_paid_by']) ? $_POST['freight_paid_by'] : null;
    $insurance_number = isset($_POST['insurance_number']) ? $_POST['insurance_number'] : null;
    $lc_number = isset($_POST['lc_number']) ? $_POST['lc_number'] : null;
    $cfs = isset($_POST['cfs']) ? $_POST['cfs'] : null;

    $mamul_charge = (isset($_POST['mamul_charge']) && is_numeric($_POST['mamul_charge'])) ? $_POST['mamul_charge'] : '0';
    $vehicle_halting_charge = (isset($_POST['vehicle_halting_charge']) && is_numeric($_POST['vehicle_halting_charge'])) ? $_POST['vehicle_halting_charge'] : '0';
    $vehicle_loading_unloading = (isset($_POST['vehicle_loading_unloading']) && is_numeric($_POST['vehicle_loading_unloading'])) ? $_POST['vehicle_loading_unloading'] : '0';
    $bill_to = isset($_POST['bill_to']) ? $_POST['bill_to'] : '';
    $vehicle_purchase_contact_person = isset($_POST['vehicle_purchase_contact_person']) ? $_POST['vehicle_purchase_contact_person'] : '';
    $quotation_approval = isset($_POST['quotation_approval']) ? $_POST['quotation_approval'] : '';
    $highload_challan = isset($_POST['highload_challan']) ? $_POST['highload_challan'] : '';
    $supplier_invoice_value = isset($_POST['supplier_invoice_value']) ? $_POST['supplier_invoice_value'] : '';
    $volumetric_weight = isset($_POST['volumetric_weight']) ? $_POST['volumetric_weight'] : '';
    $description_of_goods = isset($_POST['description_of_goods']) ? $_POST['description_of_goods'] : '';

    require_once('include/gst_tax_functions.php');
    ensure_transaction_gst_columns($conn, $tables[0]);
    $company_state_q = mysqli_query($conn, 'SELECT state FROM company WHERE status=0 LIMIT 1');
    $company_state_row = mysqli_fetch_assoc($company_state_q);
    $company_state_id = (int) ($company_state_row['state'] ?? 0);
    $gst_snapshot = gst_tax_build_booking_snapshot($conn, $_POST, $company_state_id);
    $gst_type = mysqli_real_escape_string($conn, $gst_snapshot['gst_type'] ?? '');
    $gst_tax_id = (int) ($gst_snapshot['gst_tax_id'] ?? 0);
    $gst_tax_code = mysqli_real_escape_string($conn, $gst_snapshot['gst_tax_code'] ?? '');
    $cgst_rate = (float) ($gst_snapshot['cgst_rate'] ?? 0);
    $sgst_rate = (float) ($gst_snapshot['sgst_rate'] ?? 0);
    $igst_rate = (float) ($gst_snapshot['igst_rate'] ?? 0);
    $cess_rate = (float) ($gst_snapshot['cess_rate'] ?? 0);
    $cgst_amount = (float) ($gst_snapshot['cgst_amount'] ?? 0);
    $sgst_amount = (float) ($gst_snapshot['sgst_amount'] ?? 0);
    $igst_amount = (float) ($gst_snapshot['igst_amount'] ?? 0);
    $cess_amount = (float) ($gst_snapshot['cess_amount'] ?? 0);
    $taxable_value = (float) ($gst_snapshot['taxable_value'] ?? 0);
    $bill_to_state_id = (int) ($gst_snapshot['bill_to_state_id'] ?? 0);
    $gst_rate = (float) ($gst_snapshot['gst_rate'] ?? 0);
    $gst_amount = (float) ($gst_snapshot['gst_amount'] ?? 0);
    $total = (float) ($gst_snapshot['grand_total'] ?? $total);

    $billing_only_edit = isset($_POST['billing_only_edit']) && (string) $_POST['billing_only_edit'] === '1';

    // After delivery: update payment/billing + GST amounts only — never clear origin/destination/parties
    if ($billing_only_edit) {
        $existing_q = mysqli_query($conn, "SELECT gst_tax_id, gst_type, gst_tax_code, origin, destination, consigner, consignee FROM {$tables[0]} WHERE transaction_id='" . mysqli_real_escape_string($conn, $edit_id) . "' LIMIT 1");
        $existing_row = mysqli_fetch_assoc($existing_q);
        if ($existing_row) {
            if (empty($gst_tax_id) && !empty($existing_row['gst_tax_id'])) {
                $gst_tax_id = (int) $existing_row['gst_tax_id'];
            }
            if ($gst_type === '' && !empty($existing_row['gst_type'])) {
                $gst_type = mysqli_real_escape_string($conn, $existing_row['gst_type']);
            }
            if ($gst_tax_code === '' && !empty($existing_row['gst_tax_code'])) {
                $gst_tax_code = mysqli_real_escape_string($conn, $existing_row['gst_tax_code']);
            }
        }

        $frieght_rate = mysqli_real_escape_string($conn, isset($frieght_rate) ? $frieght_rate : '');
        $frieght_amount = mysqli_real_escape_string($conn, isset($frieght_amount) ? $frieght_amount : '');
        $doc_rate = mysqli_real_escape_string($conn, isset($doc_rate) ? $doc_rate : '');
        $doc_amount = mysqli_real_escape_string($conn, isset($doc_amount) ? $doc_amount : '');
        $other_rate = mysqli_real_escape_string($conn, isset($other_rate) ? $other_rate : '');
        $other_amount = mysqli_real_escape_string($conn, isset($other_amount) ? $other_amount : '');
        $rajdhani_charges = mysqli_real_escape_string($conn, isset($rajdhani_charges) ? $rajdhani_charges : '');
        $amount_in_words = mysqli_real_escape_string($conn, isset($amount_in_words) ? $amount_in_words : '');
        $mamul_charge = mysqli_real_escape_string($conn, $mamul_charge);
        $vehicle_halting_charge = mysqli_real_escape_string($conn, $vehicle_halting_charge);
        $vehicle_loading_unloading = mysqli_real_escape_string($conn, $vehicle_loading_unloading);

        $query = "UPDATE {$tables[0]} SET
            frieght_rate='$frieght_rate',
            frieght_amount='$frieght_amount',
            doc_charges='$doc_rate',
            doc_amount='$doc_amount',
            other_charge_rate='$other_rate',
            other_charge_amount='$other_amount',
            rajdhani_charges='$rajdhani_charges',
            mamul_charge='$mamul_charge',
            vehicle_halting_charge='$vehicle_halting_charge',
            vehicle_loading_unloading='$vehicle_loading_unloading',
            gst_rate='$gst_rate',
            gst_amount='$gst_amount',
            gst_type='$gst_type',
            gst_tax_id='$gst_tax_id',
            gst_tax_code='$gst_tax_code',
            cgst_rate='$cgst_rate',
            sgst_rate='$sgst_rate',
            igst_rate='$igst_rate',
            cess_rate='$cess_rate',
            cgst_amount='$cgst_amount',
            sgst_amount='$sgst_amount',
            igst_amount='$igst_amount',
            cess_amount='$cess_amount',
            taxable_value='$taxable_value',
            bill_to_state_id='$bill_to_state_id',
            total='$total',
            balance='$total',
            total_words='$amount_in_words',
            updated_at='$updated_at',
            updated_by='$updated_by'
            WHERE transaction_id='" . mysqli_real_escape_string($conn, $edit_id) . "'";
        $result = mysqli_query($conn, $query) or die(mysqli_error($conn));
        $out_put['result'] = 1;
        $out_put['data'] = isset($grn_no) ? $grn_no : '';
        echo json_encode($out_put);
        exit;
    }

    $query = "update $tables[0] set mode_of_transportation='" . $mode_of_trasport . "',train_type = '$train_name',ftl_type = '$ftl_type',origin='" . $origin . "',destination='" . $destination . "',mode_of_consignment='" . $mode_of_consignment . "',consigner='" . $consignor . "',address1='$address1',address2='$address2',city='$city',pincode='$pincode',state='$state',phone='$phone',gst_no='$gst_no',consignee='$consignee',con_address1='$con_address1',con_address2='$con_address2',shipping_address='$ship_address',shipping_address_name='$shipping_address_name',
shipping_gst_no='$shipping_gst_no',
shipping_phone='$shipping_phone',bill_to='$bill_to',con_city='$con_city',con_state='$con_state',con_pincode='$con_pincode',con_phone='$con_phone',con_gst_no='" . $con_gst . "',goods_dedared_value='$goods_dedared_value',supplier_invoice_value='$supplier_invoice_value',
description_of_goods='$description_of_goods',octroi='$octroi',dimension1='$len',dimension2='$wid',dimension3='$hei',dimension4='$quanti',volumetric_weight='$volumetric_weight',consignment_weight='$vlm_wei',frieght_rate='$frieght_rate',frieght_amount='$frieght_amount',`loading_unloading_rate`='" . $loading_unload_rate . "',`loading_unloading_amount`='" . $loading_unload_chrg . "',`crane_fork_lift_rate`='" . $crane_forklift_rate . "',`crane_fork_lift_amount`='" . $crane_forklift_chrg . "',cod_rate='$cod_rate',cod_amount='$cod_amount',fov_rate='$fov_rate',fov_amount='$fov_amount',doc_charges='" . $doc_rate . "',doc_amount='" . $doc_amount . "',cartage_rate='$cartage_rate',cartage_amount='$cartage_amount',labour_handling_rate='" . $labour_rate . "',labour_handling_amount='" . $labour_amount . "',octroi_rate='$octroi_rate',octroi_amount='$octroi_amount',other_charge_rate='" . $other_rate . "',other_charge_amount='" . $other_amount . "',rajdhani_charges='$rajdhani_charges',gst_rate='$gst_rate',gst_amount='$gst_amount',gst_type='$gst_type',gst_tax_id='$gst_tax_id',gst_tax_code='$gst_tax_code',cgst_rate='$cgst_rate',sgst_rate='$sgst_rate',igst_rate='$igst_rate',cess_rate='$cess_rate',cgst_amount='$cgst_amount',sgst_amount='$sgst_amount',igst_amount='$igst_amount',cess_amount='$cess_amount',taxable_value='$taxable_value',bill_to_state_id='$bill_to_state_id',total='$total',
    paid_amount = '0', balance = '$total',paid_status = '0' ,total_words='" . $amount_in_words . "',note1='$note1',note2='$note2',truck='" . $vehicle_no . "',
    vehicle_purchase_contact_person='$vehicle_purchase_contact_person',
quotation_approval='$quotation_approval',
highload_challan='$highload_challan',
    vehicle_type='$vehicle_type',
freight_paid_by='$freight_paid_by',
insurance_number='$insurance_number',
lc_number='$lc_number',
cfs='$cfs',

mamul_charge='$mamul_charge',
vehicle_halting_charge='$vehicle_halting_charge',
vehicle_loading_unloading='$vehicle_loading_unloading',  consigner_signature='" . $signature . "',updated_at = '" . $updated_at . "',updated_by ='" . $updated_by . "', eway_number = '$eway_number', eway_expirydate = '$eway_expiryDate', other_train_name = '$other_train_names' where transaction_id='$edit_id'";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));

    // Update Packages
    for ($up_p = 0; $up_p < count($_POST['no_of_pkg']); $up_p++) {
        $update_q = mysqli_query($conn, "UPDATE $tables[2] set `no_of_pkge`='" . $_POST['no_of_pkg'][$up_p] . "',
\t\t`type_of_pkge`='" . $_POST['type_of_pkg'][$up_p] . "',`party_invoice_no`='" . $_POST['party_invoice'][$up_p] . "',`said_contents`='" . $_POST['content'][$up_p] . "',`qty`='" . $_POST['qty'][$up_p] . "',`gross_weight`='" . $_POST['gross'][$up_p] . "',`charged_weight`='" . $_POST['charged'][$up_p] . "',`updated_by`='" . $updated_by . "',`updated_at`='$updated_at ' WHERE transaction_id = '" . $edit_id . "' ");

        $package[] = $_POST['no_of_pkg'][$up_p];  // 2 old to new 4

        $pkg_name[] = $_POST['type_of_pkg'][$up_p];  // 2 to 3
    }

    // Remove Old Qrcode
    $dir = 'qrcode/';
    $path = 'qrcode';

    $files = scandir($path);

    // $grn_no ='Soar00001';

    $uppercase_grn = strtoupper($grn_no);

    foreach ($files as $file) {
        $filename = substr($file, 0, 9);
        if (strpos($uppercase_grn, $filename) !== false) {
            // file found
            //  echo $dir.$file;
            //  echo "<br>";
            unlink($dir . $file);
            // echo "file_found";
        }
    }

    // End Remove Old Qrcode

    // Barcode Start
    // require 'vendor/autoload.php'; For Barcode
    include('libs/phpqrcode/qrlib.php');
    $result_bar = [];

    foreach ($pkg_name as $index => $val) {
        $result_bar[$val] = ($result_bar[$val] ?? 0) + $package[$index];
    }
    $package_type1 = (array_keys($result_bar));
    $packge_qty = (array_values($result_bar));
    // $redColor = [0, 0, 0];
    // $generator = new Picqer\Barcode\BarcodeGeneratorJPG();
    $name = $grn_no;
    // var_dump($qty);

    // $rate = 10;

    foreach ($packge_qty as $key => $val) {
        $get_qty = $val;
        // var_dump($get_qty);
        // echo "KEY".$key. "value". $val;
        if (array_key_exists($key, $package_type1)) {
            $get_package = $package_type1[$key];
            // var_dump($get_package);

            switch ($get_package) {
                case '1':
                    $pack_name = 'CBX';
                    break;
                case '2':
                    $pack_name = 'PBG';
                    break;
                case '3':
                    $pack_name = 'ROL';
                    break;
                case '5':
                    $pack_name = 'SHT';
                    break;
                case '6':
                    $pack_name = 'BDL';
                    break;
                case '7':
                    $pack_name = 'CVR';
                    break;
                case '8':
                    $pack_name = 'PBL';
                    break;
                case '9':
                    $pack_name = 'CAN';
                    break;
                case '10':
                    $pack_name = 'BOX';
                    break;
                case '11':
                    $pack_name = 'BAG';
                    break;
                case '12':
                    $pack_name = 'MLD';
                    break;
                case '13':
                    $pack_name = 'PKT';
                    break;
                case '14':
                    $pack_name = 'CES';
                    break;
                case '15':
                    $pack_name = 'CAT';
                    break;
                case '16':
                    $pack_name = 'GRL';
                    break;
                case '17':
                    $pack_name = 'P.B';
                    break;
                case '18':
                    $pack_name = 'PRL';
                    break;
                default:
                    $pack_name = 'No Package Type Found!';
            }

            // $productData = "098{$get_qty}10{$name}55{$rate}";
            $tempDir = 'qrcode/';
            $productData = strtoupper($name);
            $j = 1;
            for ($i = 0; $i < $get_qty; $i++) {
                $change_index[$j] = $i + 1;
                $names = $productData . $pack_name . '-00' . $change_index[$j];
                $contents = 'https://elitewave360.in/web/testqrcode.php?grn_no=' . $name . '&grn_date=' . $grn_date;
                // var_dump($names);
                // Barcode
                // file_put_contents('barcode/'.$names.'.jpg', $generator->getBarcode($names, $generator::TYPE_CODE_128,3,100,$redColor));

                // Qrcode
                QRcode::png($contents, $tempDir . '' . $names . '.png', QR_ECLEVEL_L, 5);

                $j++;
            }
        }
    }
    // Barcode End

    // End

    for ($k = 0; $k < count($_FILES['file_receipt']['name']); $k++) {
        $file_name = uniqid() . $_FILES['file_receipt']['name'][$k];
        if (move_uploaded_file($_FILES['file_receipt']['tmp_name'][$k], 'invoice_image/' . $file_name)) {  // images/

            $fr_query = "insert into $tables[1](transaction_id,attachment,created_at,created_by,status) values('$edit_id','$file_name','$created_at','$created_by','0')";
            $fr_result = mysqli_query($conn, $fr_query) or die(mysqli_error($conn));
        }
    }
    // $attachment_id = $_REQUEST['id'];
    $attachment_id = $_REQUEST['del_id'];
    // var_dump($attachment_id);
    $sql_delete = "delete from $tables[1] where attachment_id IN($attachment_id)";
    $del_image = mysqli_query($conn, $sql_delete);
    $del_q = mysqli_query($conn, "delete from $tables[2] where transaction_id='$edit_id'");

    for ($j = 0; $j < count($_POST['no_of_pkg']); $j++) {
        $party_inv_date = '';

        if (!empty($_POST['party_invoice_date'][$j])) {
            $party_inv_date = date(
                'd-m-Y',
                strtotime(
                    str_replace('/', '-', $_POST['party_invoice_date'][$j])
                )
            );
        }
        $f_query = "insert into $tables[2](transaction_id,no_of_pkge,type_of_pkge,party_invoice_no,party_invoice_date,said_contents,qty,gross_weight,charged_weight,created_at,created_by,status) values('" . $edit_id . "','" . $no_of_pkg[$j] . "','" . $type_of_pkg[$j] . "','" . $party_invoice[$j] . "','" . $party_inv_date . "','" . $content[$j] . "','" . $qty[$j] . "','" . $gross[$j] . "','" . $charged[$j] . "','" . $created_at . "','" . $created_by . "','0')";
        $f_result = mysqli_query($conn, $f_query) or die(mysqli_error($conn));
    }

    // *Start Invoice

    $check_inv_no_avlble = "select * from $tables[0] where transaction_id= '$edit_id'";
    $inv_res = mysqli_query($conn, $check_inv_no_avlble);
    $fetch_det = mysqli_fetch_assoc($inv_res);
    $check_inv_no = $fetch_det['invoice_no'];

    if ($check_inv_no != 'NULL') {
        $directory = 'digital_invoice/';
        $invoice_url = 'https://elitewave360.in/web/gst_invoice_page.php?month=' . $month . '&year=' . $year . '&id=' . $edit_id . '&invoice_no=' . $unique_invoice_no . '';
        $invoice_file_name = $month . '_' . $year . '_' . $edit_id . 'invoice';
        $download_path = $directory . $invoice_file_name . '.pdf';
        $file_inv_download = curl_init($invoice_url);
        curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($file_inv_download, CURLOPT_REFERER, true);
        curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);

        $store_inv = curl_exec($file_inv_download);
        curl_close($file_inv_download);
        $save_inv_file = file_put_contents($download_path, $store_inv);
    }

    // *End Invoice

    // Update Outstanding
    if ($mode_of_consignment == '1') {  // To Pay

        $update_OutStanding = UpdateOutStandingInfo($conn, $consignee, '1');
    } else {  // TBB

        $update_OutStanding = UpdateOutStandingInfo($conn, $consignor, '2');
    }

    // End

    if ($result) {
        $out_put['result'] = 1;
        // $out_put['data'] = "delete from $tables[2] where transaction_id='$edit_id'";
    } else {
        $out_put['result'] = '0';
    }

    echo json_encode($out_put);
}

if ($form_name == 'edit_consignment_details_manual') {
    $out_put = array();
    extract($_POST);

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
    $phone = $consignorrow['contact_no'];
    $gst_no = $consignorrow['gst_no'];

    $consigneequery = "select * from client where client_id='$consignee'";
    $consigneeresult = mysqli_query($conn, $consigneequery);
    $consigneerow = mysqli_fetch_array($consigneeresult);

    $con_address1 = $consigneerow['address1'];
    $con_address2 = $consigneerow['address2'];
    $con_city = $consigneerow['city'];
    $con_state = $consigneerow['state'];
    $con_pincode = $consigneerow['pincode'];
    $con_phone = $consigneerow['contact_no'];
    $con_gst = $consigneerow['gst_no'];
    $grn_no = $_POST['grn_no'];
    $ftl_type = $_POST['truck_type'];
    $train_name = $_POST['train_name'];
    $rajdhani_charges = $_POST['rajdhani_charges'];

    // Volumetric Values
    $len = $_POST['length'] ? $_POST['length'] : null;
    $wid = $_POST['width'] ? $_POST['width'] : null;
    $hei = $_POST['height'] ? $_POST['height'] : null;
    $quanti = $_POST['quanti'] ? $_POST['quanti'] : null;
    $vlm_wei = $_POST['vlm_weight'] ? $_POST['vlm_weight'] : null;

    // Shipping Address
    $ship_address = $_POST['shipping_address'] ? $_POST['shipping_address'] : null;

    $eway_expiryDate = $_POST['eway_expiryDate'] ? $_POST['eway_expiryDate'] : null;
    $eway_number = $_POST['eway_number'] ? $_POST['eway_number'] : null;

    $vehicle_type = isset($_POST['vehicle_type']) ? $_POST['vehicle_type'] : null;
    $freight_paid_by = isset($_POST['freight_paid_by']) ? $_POST['freight_paid_by'] : null;
    $insurance_number = isset($_POST['insurance_number']) ? $_POST['insurance_number'] : null;
    $lc_number = isset($_POST['lc_number']) ? $_POST['lc_number'] : null;
    $cfs = isset($_POST['cfs']) ? $_POST['cfs'] : null;

    $mamul_charge = isset($_POST['mamul_charge']) ? $_POST['mamul_charge'] : null;
    $vehicle_halting_charge = isset($_POST['vehicle_halting_charge']) ? $_POST['vehicle_halting_charge'] : null;
    $vehicle_loading_unloading = isset($_POST['vehicle_loading_unloading']) ? $_POST['vehicle_loading_unloading'] : null;

    $query = "UPDATE $tables[0] SET mode_of_transportation='" . $mode_of_trasport . "',train_type = '$train_name',ftl_type = '$ftl_type',origin='" . $origin . "',destination='" . $destination . "',mode_of_consignment='" . $mode_of_consignment . "',consigner='" . $consignor . "',address1='$address1',address2='$address2',city='$city',pincode='$pincode',state='$state',phone='$phone',gst_no='$gst_no',consignee='$consignee',con_address1='$con_address1',con_address2='$con_address2',shipping_address='$ship_address',con_city='$con_city',con_state='$con_state',con_pincode='$con_pincode',con_phone='$con_phone',con_gst_no='" . $con_gst . "',goods_dedared_value='$goods_dedared_value',octroi='$octroi',dimension1='$len',dimension2='$wid',dimension3='$hei',dimension4='$quanti',consignment_weight='$vlm_wei',frieght_rate='$frieght_rate',frieght_amount='$frieght_amount',`loading_unloading_rate`='" . $loading_unload_rate . "',`loading_unloading_amount`='" . $loading_unload_chrg . "',`crane_fork_lift_rate`='" . $crane_forklift_rate . "',`crane_fork_lift_amount`='" . $crane_forklift_chrg . "',cod_rate='$cod_rate',cod_amount='$cod_amount',fov_rate='$fov_rate',fov_amount='$fov_amount',doc_charges='" . $doc_rate . "',doc_amount='" . $doc_amount . "',cartage_rate='$cartage_rate',cartage_amount='$cartage_amount',labour_handling_rate='" . $labour_rate . "',labour_handling_amount='" . $labour_amount . "',octroi_rate='$octroi_rate',octroi_amount='$octroi_amount',other_charge_rate='" . $other_rate . "',other_charge_amount='" . $other_amount . "',rajdhani_charges='$rajdhani_charges',gst_rate='$gst_rate',gst_amount='$gst_amount',total='$total',
    paid_amount = '$total', balance = '0',paid_status = '1' ,total_words='" . $amount_in_words . "',note1='$note1',note2='$note2',truck='" . $vehicle_no . "',vehicle_type='$vehicle_type',
freight_paid_by='$freight_paid_by',
insurance_number='$insurance_number',
lc_number='$lc_number',
cfs='$cfs',
mamul_charge='$mamul_charge',
vehicle_halting_charge='$vehicle_halting_charge',
vehicle_loading_unloading='$vehicle_loading_unloading',  consigner_signature='" . $signature . "',updated_at = '" . $updated_at . "',updated_by ='" . $updated_by . "', eway_number = '$eway_number', eway_expirydate = '$eway_expiryDate' WHERE transaction_id='$edit_id'";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));

    // status select while booking start
    $sheetq = 'SELECT max(sheet_id) AS id FROM transaction_status';
    $sheetres = mysqli_query($conn, $sheetq) or die(mysqli_error($conn));
    $sheetr = mysqli_fetch_array($sheetres);
    $sheet_id = $sheetr['id'] + 1;
    $sheet_no = 'SN/' . sprintf('%04d', $sheet_id);
    $c_date = date('d-m-Y H:i:s A');

    $check_stlog_q = "SELECT grn_no,to_status FROM `transaction_status_log` WHERE grn_no = '$grn_no' ORDER BY to_status DESC LIMIT 1";
    $check_stlog_res = mysqli_query($conn, $check_stlog_q);
    $get_count_log = mysqli_num_rows($check_stlog_res);
    $get_log_status = mysqli_fetch_assoc($check_stlog_res);
    $log_status = $get_log_status['to_status'];

    if ($get_count_log > 0) {
        if ($status > $log_status) {
            $insq1 = "INSERT INTO `transaction_status`(`sheet_id`,`sheet_no`,`status`,`created_at`,`created_by`) VALUES ('$sheet_id','$sheet_no','$status','$c_date','$created_by')";
            $insr1 = mysqli_query($conn, $insq1) or die(mysqli_error($conn));

            $insq = "INSERT INTO `transaction_status_log`(`sheet_id`,`grn_no`,`from_status`,`to_status`,`client_id`,`updated_at`,`updated_by`) VALUES ('$sheet_id','$grn_no','1','$status','$consignor','$created_at','$created_by')";
            $insr = mysqli_query($conn, $insq) or die(mysqli_error($conn));

            $status_upd_query = "UPDATE $tables[0] SET `status`='$status' WHERE grn_no='$grn_no' AND client_id='$consignor'";
            $results = mysqli_query($conn, $status_upd_query) or die(mysqli_error($conn));
        }
    } else {
        $insq1 = "INSERT INTO `transaction_status`(`sheet_id`,`sheet_no`,`status`,`created_at`,`created_by`) VALUES ('$sheet_id','$sheet_no','$status','$c_date','$created_by')";
        $insr1 = mysqli_query($conn, $insq1);

        $insq = "INSERT INTO `transaction_status_log`(`sheet_id`,`grn_no`,`from_status`,`to_status`,`client_id`,`updated_at`,`updated_by`) VALUES ('$sheet_id','$grn_no','1','$status','$consignor','$created_at','$created_by')";
        $insr = mysqli_query($conn, $insq);

        $status_upd_query = "UPDATE $tables[0] SET `status`='$status' WHERE grn_no='$grn_no' AND client_id='$consignor'";
        $results = mysqli_query($conn, $status_upd_query);
    }

    // status select while booking end

    // Update Packages
    for ($up_p = 0; $up_p < count($_POST['no_of_pkg']); $up_p++) {
        $update_q = mysqli_query($conn, "UPDATE $tables[2] set `no_of_pkge`='" . $_POST['no_of_pkg'][$up_p] . "',
\t\t`type_of_pkge`='" . $_POST['type_of_pkg'][$up_p] . "',`party_invoice_no`='" . $_POST['party_invoice'][$up_p] . "',`said_contents`='" . $_POST['content'][$up_p] . "',`qty`='" . $_POST['qty'][$up_p] . "',`gross_weight`='" . $_POST['gross'][$up_p] . "',`charged_weight`='" . $_POST['charged'][$up_p] . "',`updated_by`='" . $updated_by . "',`updated_at`='$updated_at ' WHERE transaction_id = '" . $edit_id . "' ");

        $package[] = $_POST['no_of_pkg'][$up_p];  // 2 old to new 4

        $pkg_name[] = $_POST['type_of_pkg'][$up_p];  // 2 to 3
    }

    // Remove Old Qrcode
    $dir = 'qrcode/';
    $path = 'qrcode';

    $files = scandir($path);

    $uppercase_grn = strtoupper($grn_no);

    foreach ($files as $file) {
        $filename = substr($file, 0, 9);
        if (strpos($uppercase_grn, $filename) !== false) {
            // file found
            //  echo $dir.$file;
            //  echo "<br>";
            unlink($dir . $file);
            // echo "file_found";
        }
    }

    // End Remove Old Qrcode

    // Barcode Start
    include('libs/phpqrcode/qrlib.php');
    $result_bar = [];

    foreach ($pkg_name as $index => $val) {
        $result_bar[$val] = ($result_bar[$val] ?? 0) + $package[$index];
    }
    $package_type1 = (array_keys($result_bar));
    $packge_qty = (array_values($result_bar));
    $name = $grn_no;

    foreach ($packge_qty as $key => $val) {
        $get_qty = $val;
        if (array_key_exists($key, $package_type1)) {
            $get_package = $package_type1[$key];

            switch ($get_package) {
                case '1':
                    $pack_name = 'CBX';
                    break;
                case '2':
                    $pack_name = 'PBG';
                    break;
                case '3':
                    $pack_name = 'ROL';
                    break;
                case '5':
                    $pack_name = 'SHT';
                    break;
                case '6':
                    $pack_name = 'BDL';
                    break;
                case '7':
                    $pack_name = 'CVR';
                    break;
                case '8':
                    $pack_name = 'PBL';
                    break;
                case '9':
                    $pack_name = 'CAN';
                    break;
                case '10':
                    $pack_name = 'BOX';
                    break;
                case '11':
                    $pack_name = 'BAG';
                    break;
                case '12':
                    $pack_name = 'MLD';
                    break;
                case '13':
                    $pack_name = 'PKT';
                    break;
                case '14':
                    $pack_name = 'CES';
                    break;
                case '15':
                    $pack_name = 'CAT';
                    break;
                case '16':
                    $pack_name = 'GRL';
                    break;
                case '17':
                    $pack_name = 'P.B';
                    break;
                case '18':
                    $pack_name = 'PRL';
                    break;
                default:
                    $pack_name = 'No Package Type Found!';
            }

            $tempDir = 'qrcode/';
            $productData = strtoupper($name);
            $j = 1;
            for ($i = 0; $i < $get_qty; $i++) {
                $change_index[$j] = $i + 1;
                $names = $productData . $pack_name . '-00' . $change_index[$j];
                $contents = 'https://elitewave360.in/web/testqrcode.php?grn_no=' . $name . '&grn_date=' . $grn_date;
                // var_dump($names);
                // Barcode
                // Qrcode
                QRcode::png($contents, $tempDir . '' . $names . '.png', QR_ECLEVEL_L, 5);

                $j++;
            }
        }
    }
    // Barcode End
    // End

    for ($k = 0; $k < count($_FILES['file_receipt']['name']); $k++) {
        $file_name = uniqid() . $_FILES['file_receipt']['name'][$k];
        if (move_uploaded_file($_FILES['file_receipt']['tmp_name'][$k], 'invoice_image/' . $file_name)) {  // images/

            $fr_query = "insert into $tables[1](transaction_id,attachment,created_at,created_by,status) values('$edit_id','$file_name','$created_at','$created_by','0')";
            $fr_result = mysqli_query($conn, $fr_query) or die(mysqli_error($conn));
        }
    }

    $attachment_id = $_REQUEST['del_id'];

    $sql_delete = "delete from $tables[1] where attachment_id IN($attachment_id)";
    $del_image = mysqli_query($conn, $sql_delete);
    $del_q = mysqli_query($conn, "delete from $tables[2] where transaction_id='$edit_id'");

    for ($j = 0; $j < count($_POST['no_of_pkg']); $j++) {
        $f_query = "insert into $tables[2](transaction_id,no_of_pkge,type_of_pkge,party_invoice_no,said_contents,qty,gross_weight,charged_weight,created_at,created_by,status) values('" . $edit_id . "','" . $no_of_pkg[$j] . "','" . $type_of_pkg[$j] . "','" . $party_invoice[$j] . "','" . $content[$j] . "','" . $qty[$j] . "','" . $gross[$j] . "','" . $charged[$j] . "','" . $created_at . "','" . $created_by . "','0')";
        $f_result = mysqli_query($conn, $f_query) or die(mysqli_error($conn));
    }

    // *Start Invoice
    $check_inv_no_avlble = "select * from $tables[0] where transaction_id= '$edit_id'";
    $inv_res = mysqli_query($conn, $check_inv_no_avlble);
    $fetch_det = mysqli_fetch_assoc($inv_res);
    $check_inv_no = $fetch_det['invoice_no'];

    if ($check_inv_no != 'NULL') {
        $directory = 'digital_invoice/';
        $invoice_url = 'https://elitewave360.in/web/gst_invoice_page.php?month=' . $month . '&year=' . $year . '&id=' . $edit_id . '&invoice_no=' . $unique_invoice_no . '';
        $invoice_file_name = $month . '_' . $year . '_' . $edit_id . 'invoice';
        $download_path = $directory . $invoice_file_name . '.pdf';
        $file_inv_download = curl_init($invoice_url);
        curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($file_inv_download, CURLOPT_REFERER, true);
        curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);

        $store_inv = curl_exec($file_inv_download);
        curl_close($file_inv_download);
        $save_inv_file = file_put_contents($download_path, $store_inv);
    }

    // *End Invoice
    // Update Outstanding
    // if ($mode_of_consignment == '1') { // To Pay
    //     $update_OutStanding = UpdateOutStandingInfo($conn, $consignee, '1');
    // } else { //TBB
    //     $update_OutStanding = UpdateOutStandingInfo($conn, $consignor, '2');
    // }

    // End

    // if ($update_OutStanding == 1) {
    $out_put['result'] = 1;
    // } else {
    //     $out_put['result'] = "0";
    // }

    echo json_encode($out_put);
}

if ($form_name == 'add_eway_bill') {
    $id = $_POST['attachment_id'];
    $table_name = $_POST['table_name'];
    $issue_date = $_POST['issue_date'];
    $expire_date = $_POST['expire_date'];

    $eway_bill_no = $_POST['eway_bill_no'];

    foreach ($_FILES['attachment']['error'] as $key => $error) {
        if ($error == UPLOAD_ERR_OK) {
            $name = $eway_bill_no . $id . $_FILES['attachment']['name'][$key];
            $target_dir = 'eway/';
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

if ($form_name == 'add_company') {
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
    $grn_mode = isset($_POST['grn_mode']) ? $_POST['grn_mode'] : 'company';

    if ($_FILES['logo']['size'] != 0) {
        $logo = uniqid() . $_FILES['logo']['name'];
        move_uploaded_file($_FILES['logo']['tmp_name'], 'images/' . $logo);
    }

    if ($_FILES['flag']['size'] != 0) {
        $flag = uniqid() . $_FILES['flag']['name'];
        move_uploaded_file($_FILES['flag']['tmp_name'], 'images/' . $flag);
    }

    $query = "insert into company(company_name,company_code,contact_person,address1,address2,state,city,pincode,mobile_no,logo,flag,gst_no,pan_no,email,created_at,created_by,status,grn_mode) values('" . $comp_name . "','" . $comp_code . "','" . $contact_person . "','" . $address1 . "','" . $address2 . "','" . $state . "','" . $city . "','" . $pincode . "','" . $mobile_no . "','" . $logo . "','" . $flag . "','" . $gst_no . "','" . $pan_no . "','" . $email . "','" . $created_at . "','" . $created_by . "','0','" . $grn_mode . "')";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo 0;
}

if ($form_name == 'edit_company') {
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
    $grn_mode = isset($_POST['grn_mode']) ? $_POST['grn_mode'] : 'company';
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['size'] != 0) {
        $logo = uniqid() . $_FILES['logo']['name'];
        move_uploaded_file($_FILES['logo']['tmp_name'], 'images/' . $logo);
        $query = "update company set logo='" . $logo . "' where company_id='" . $edit_id . "'";
        $result = mysqli_query($conn, $query);
    }

    if (!empty($_FILES['flag']['name']) && $_FILES['flag']['size'] != 0) {
        $flag = uniqid() . $_FILES['flag']['name'];
        move_uploaded_file($_FILES['flag']['tmp_name'], 'images/' . $flag);
        $query = "update company set flag='" . $flag . "' where company_id='" . $edit_id . "'";
        $result = mysqli_query($conn, $query);
    }

    $query = "update company set company_name='" . $comp_name . "',company_code='" . $comp_code . "',contact_person='" . $contact_person . "',address1='" . $address1 . "',address2='" . $address2 . "',state= '" . $state . "',city='" . $city . "',pincode='" . $pincode . "',mobile_no='" . $mobile_no . "',gst_no='" . $gst_no . "',pan_no='" . $pan_no . "',email='" . $email . "',updated_at='" . $updated_at . "',updated_by='" . $updated_by . "',grn_mode='" . $grn_mode . "' where company_id='" . $edit_id . "'";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo 1;
    } else {
        die(mysqli_error($conn));
    }
}
if ($form_name == 'request_for_new_pickup_for_existing_client') {
    $consignor      = mysqli_real_escape_string($conn, $_POST['consignor']);
    $consignee      = mysqli_real_escape_string($conn, $_POST['consignee']);
    $origin         = mysqli_real_escape_string($conn, $_POST['origin']);
    $destination    = mysqli_real_escape_string($conn, $_POST['city']);
    $mode           = ($_POST['mode'] !== '') ? (int) $_POST['mode'] : 0;
    $no_of_package  = ($_POST['no_of_package'] !== '') ? (int) $_POST['no_of_package'] : 0;
    $package        = ($_POST['package'] !== '') ? (int) $_POST['package'] : 0;
    $approx_weight  = ($_POST['approx_weight'] !== '') ? (float) $_POST['approx_weight'] : 0;
    $description    = mysqli_real_escape_string($conn, $_POST['description']);

    if ($consignor === '' || $consignee === '' || $origin === '' || $destination === '') {
        echo 'Please fill all required fields.';
        exit;
    }

    $id_q = mysqli_query($conn, 'select max(pickup) as pickup from pickup');
    $id_r = mysqli_fetch_array($id_q);
    $pickup = $id_r['pickup'] + 1;
    $pickup_ref_id = 'RFP/' . sprintf('%05d', $pickup);

    $query = "INSERT INTO `pickup`
        (`pickup_ref_id`, `pickup`, `consignee`, `consignor`, `origin`,
         `destination`, `mode`, `no_of_package`, `package`, `approx_weight`,
         `created_at`, `created_by`, `updated_at`, `updated_by`, `description`, `status`)
        VALUES
        ('$pickup_ref_id','$pickup','$consignee','$consignor','$origin',
         '$destination','$mode','$no_of_package','$package','$approx_weight',
         '$created_at','$created_by','$updated_at','$updated_by','$description','0')";

    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo mysqli_error($conn);   // keep temporarily until confirmed working, then revert to echo 0;
}

if ($form_name == 'edit_pickup') {
    $edit_id        = mysqli_real_escape_string($conn, $_POST['edit_id']);
    $consignor      = mysqli_real_escape_string($conn, $_POST['consignor']);
    $consignee      = mysqli_real_escape_string($conn, $_POST['consignee']);
    $origin         = mysqli_real_escape_string($conn, $_POST['origin']);
    $destination    = mysqli_real_escape_string($conn, $_POST['city']);
    $mode           = ($_POST['mode'] !== '') ? (int) $_POST['mode'] : 0;
    $no_of_package  = ($_POST['no_of_package'] !== '') ? (int) $_POST['no_of_package'] : 0;
    $package        = ($_POST['package'] !== '') ? (int) $_POST['package'] : 0;
    $approx_weight  = ($_POST['approx_weight'] !== '') ? (float) $_POST['approx_weight'] : 0;
    $description    = mysqli_real_escape_string($conn, $_POST['description']);

    $query = "UPDATE `pickup` SET
        `consignee`     = '$consignee',
        `consignor`     = '$consignor',
        `origin`        = '$origin',
        `destination`   = '$destination',
        `mode`          = '$mode',
        `no_of_package` = '$no_of_package',
        `package`       = '$package',
        `approx_weight` = '$approx_weight',
        `description`   = '$description',
        `updated_at`    = '$updated_at',
        `updated_by`    = '$updated_by'
        WHERE `pickup_id` = '$edit_id'";

    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo mysqli_error($conn);
}
if ($form_name == 'del_pickup') {
    $tbl_id = mysqli_real_escape_string($conn, $_POST['tbl_id']);
    $query = "DELETE FROM `pickup` WHERE `pickup_id`='$tbl_id'";
    $result = mysqli_query($conn, $query);
    if ($result)
        echo 1;
    else
        echo mysqli_error($conn);
}

if ($form_name == 'change_grn_status') {
    $c_date = date('d-m-Y H:i:s A');
    $grn_id = implode(',', $_POST['grn_id']);
    $status = $_POST['status'];
    $origin      = ($_POST['origin'] === '') ? "NULL" : (int)$_POST['origin'];
    $destination = ($_POST['destination'] === '') ? "NULL" : (int)$_POST['destination'];
    $mode        = ($_POST['mode'] === '') ? "NULL" : (int)$_POST['mode'];
    // $remarks = $_POST['remarks'];
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks']));
    //     echo "<pre>";
    // print_r($_POST);
    // echo "</pre>";

    // echo "Remarks = ".$_POST['remarks'];
    // exit;
    $client_id = implode(',', $_POST['client_id']);
    $sheetq = 'SELECT max(sheet_id) as id FROM transaction_status';
    $sheetres = mysqli_query($conn, $sheetq) or die(mysqli_error($conn));
    $sheetr = mysqli_fetch_array($sheetres);
    $sheet_id = $sheetr['id'] + 1;
    $sheet_no = 'SN/' . sprintf('%04d', $sheet_id);

    $insq1 = "INSERT INTO `transaction_status`(`sheet_id`,`sheet_no`, `origin`, `destination`, `mode`,`remarks`, `status`, `created_at`, `created_by`) VALUES ('$sheet_id','$sheet_no',$origin,$destination,$mode,'$remarks','$status','$c_date','$created_by')";

    //    $res = mysqli_query($conn, $insq1);

    // if($res){
    //     echo 1;
    // }else{
    //     echo mysqli_error($conn);
    // }
    // exit;
    $insr1 = mysqli_query($conn, $insq1);

    $query2 = 'SELECT * FROM transaction_tbls';
    $result2 = mysqli_query($conn, $query2) or die(mysqli_error($conn));
    while ($row2 = mysqli_fetch_assoc($result2)) {
        $query = 'select * from transaction_' . $row2['table_name'] . " where grn_id IN ($grn_id) and client_id IN($client_id)";
        // exit();
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
                $consigners = $row['consigner'];
                $grn_number = $row['grn_no'];
                // Get client details
                $client_det = get_client($conn, $consigners);
                $contact_no = $client_det['contact_no'];
                $client_name = $client_det['client_company_name'];

                $insq = "INSERT INTO `transaction_status_log`(`sheet_id`,`grn_id`, `grn_no`, `from_status`, `to_status`,`client_id`,`updated_at`, `updated_by`) VALUES ('$sheet_id','" . $row['grn_id'] . "','" . $row['grn_no'] . "','" . $row['status'] . "','$status','" . $row['client_id'] . "','$created_at','$created_by')";
                $insr = mysqli_query($conn, $insq);

                $query1 = 'update transaction_' . $row2['table_name'] . " set status='$status' where grn_id='" . $row['grn_id'] . "' and client_id='" . $row['client_id'] . "'";
                $result1 = mysqli_query($conn, $query1);

                if ($result1) {
                    // Start Message Part Every Status Change

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

                    //     $link = 'https://elitewave360.in/tracking.php';
                    //     $msg = "Dear ".$client_name.", \r\n Your Booking Status: " . get_trans_status($status) . " - Against Your GCN No: ".$grn_number. "\r\n You Can Track At ".$link;

                    //     $message = $twilio->messages
                    //         ->create(
                    //             $phone, // to
                    //             ["body" => $msg, "from" => "+17853776942"]
                    //         );

                    //  }

                    // End Send OTP through SMS

                    // End Message Part

                    // *Send invoice

                    $get_status_query = 'select `total`,`balance`,`invoice_no`,`origin`,`destination`,`mode_of_consignment`,`transaction_id`,`grn_no`,`grn_date`,`consigner`,`consignee`,`status` from transaction_' . $row2['table_name'] . " where grn_id='" . $row['grn_id'] . "' and client_id='" . $row['client_id'] . "'";
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

                    // $mode_of_consignment;

                    if ($get_status == '8') {
                        // echo "You are inside in get status";

                        if ($mode_of_consignment == '1' || $mode_of_consignment == '2' || $mode_of_consignment == '3' || $mode_of_consignment == '4') {  // To pay-1 , By Sender-2, Pay at booking-3, COD-4

                            if ($mode_of_consignment == '1' || $mode_of_consignment == '4') {
                                $check_partywise_frq = checkPartyWiseFrequency($conn, $consignee_name);  // Check Frequency set or not
                                if ($check_partywise_frq == 0) {  // Frequncy is Set
                                    // Invoice Sent as per frequency
                                    // echo "Frequency is Set";
                                    // send deliver mail and sms to both consigner and consignee
                                    if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                        // $get_consignee_details =  get_client($conn, $consignee_name);
                                        // $phone = $get_consignee_details['contact_no'];
                                        // if ($phone != '' && $contact_no  !='') {
                                        //     if (strstr($contact_no, '+91')) {
                                        //         $consigner_no  =  $contact_no;
                                        //     } else {
                                        //         //echo "Text not found";
                                        //         $consigner_no  =  "+91" . $contact_no;
                                        //     }

                                        //     if (strstr($phone, '+91')) {
                                        //         $consignee_no  =  $phone;
                                        //     } else {
                                        //         //echo "Text not found";
                                        //         $consignee_no  =  "+91" . $phone;
                                        //     }

                                        //     $sms_number = array();
                                        //     array_push($sms_number, $consigner_no);
                                        //     array_push($sms_number, $consignee_no);

                                        //     // $sms_message = "GR No: " . $grnn_no . " Your consignment has been delivered. We hope you are satisfied with our services.\n\nThank you for choosing us.\nElite Wave 360";
                                        // try{
                                        //     $message_created = $client->messages->create([
                                        //         'src' => "GRACIX",
                                        //         "dst" => $sms_number,
                                        //         "text"  => "GR No: $grnn_no\nYour consignment has been delivered. We hope you are satisfied with our services.\nThank you for choosing us.\n\nElite Wave 360",
                                        //         "dlt_entity_id"=>"1201168767372626314",
                                        //         "dlt_template_id"=>"1207169175750985141",
                                        //         "dlt_template_category"=>"service_implicit",
                                        //     ]);
                                        // }catch(Exception $err){
                                        //     $error =  $err->getMessage();
                                        // }
                                        // }

                                        // send mail to both consigner and consignee
                                        $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                        Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                        <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                        <tr>
                                        <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                        </tr><tr>\t
                                        <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                        </tr>
                                        <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                        <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                        <tr>\t\t
                                        <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                            </td>
                                        </tr>
                                        </table>\t
                                        <br>";

                                        $to_name1 = array();
                                        $to_email1 = array();

                                        array_push($to_email1, get_client_email($conn, $consignee_name), get_client_email($conn, $consignor_name));
                                        array_push($to_name1, get_client_name($conn, $consignee_name), get_client_name($conn, $consignor_name));

                                        sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);

                                        // send invoice only for cod to consignee
                                        if ($mode_of_consignment == '4') {
                                            $to_name = array();
                                            $to_email = array();

                                            array_push($to_email, get_client_email($conn, $consignee_name));
                                            array_push($to_name, get_client_name($conn, $consignee_name));

                                            $get_client_details = get_client($conn, $consignee_name);
                                            $company_name = $get_client_details['client_company_name'];
                                            $email = $get_client_details['email'];
                                            $phone = $get_client_details['contact_no'];
                                            $amount_array = array($balance);
                                            $get_transaction_id_arr = array($get_transaction_id);
                                            $grnn_date_array = array($grnn_date);
                                            // $total_array = array($total);
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
                                            // End Sent Payment Link to Consignee
                                            $dir = 'digital_invoice/';
                                            // $pdf_file_name = $dir . $row2['table_name'] . "_" . $get_transaction_id . "invoice.pdf";
                                            $pdf_file_name = '';

                                            // , Please Find Your Invoice Attached (in PDF Format) to this email
                                            // Send Mail to consignee
                                            $msg = '<p style="line-height: 24px; margin-bottom:15px;">
                                            Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>
                                            Following Your Successful Consignment Delivery. \t\t\t\t
                                            <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                            <tr>
                                            <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                            </tr><tr>\t
                                            <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                            </tr>
                                            <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                            <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                            <tr>\t\t
                                            <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                                </td>
                                            </tr>
                                            </table>";

                                            $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $pdf_file_name, $image, $msg, $name);
                                        }
                                    }
                                } else {
                                    $check_restricted = check_invoice_restricted($conn, $consignee_name);
                                    if ($check_restricted == 0) {
                                        // Sent Payment Link to Consignee
                                        $get_client_details = get_client($conn, $consignee_name);
                                        $company_name = $get_client_details['client_company_name'];
                                        $email = $get_client_details['email'];
                                        $phone = $get_client_details['contact_no'];
                                        $amount_array = array($balance);
                                        $get_transaction_id_arr = array($get_transaction_id);
                                        $grnn_date_array = array($grnn_date);
                                        // $total_array = array($total);
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

                                        // End Sent Payment Link to Consignee

                                        // echo "Not Restricted";
                                        $dir = 'digital_invoice/';
                                        // $pdf_file_name = $dir . $row2['table_name'] . "_" . $get_transaction_id . "invoice.pdf";
                                        $pdf_file_name = '';

                                        // echo "pdf_file_name: ".$pdf_file_name;
                                        if ($mode_of_consignment != '4') {  // payment link should not send for COD
                                            $pay_link = '<br><br>
                                            Payment Link : <a href = "https://elitewave360.in/verify_paylink1.php?data=' . urlencode($link_wit_data) . '" >Payment Link</a>';
                                            $mail_subject = 'Consignment Invoice Notification With Payment Link';
                                        } else {
                                            $pay_link = '';
                                            $mail_subject = 'Consignment Invoice Notification';
                                        }
                                        // , Please Find Your Invoice Attached (in PDF Format) to this email
                                        // Send Mail
                                        $msg = "<p style=\"line-height: 24px; margin-bottom:15px;\">
\t\t\t\t\t\t\t\t\t\tThank You for Your Order On <a href = \"https://elitewave360.in\" >Elite Wave 360</a> on " . $grnn_date . "! <br>
\t\t\t\t\t\t\t\t\t\tFollowing Your Successful Consignment Delivery. \t\t\t\t
\t\t\t\t\t\t\t\t\t\t<table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
\t\t\t\t\t\t\t\t\t\t<tr>
\t\t\t\t\t\t\t\t\t\t<td >GCN No\t</td><td >" . $grnn_no . "</td>
\t\t\t\t\t\t\t\t\t\t</tr><tr>\t
\t\t\t\t\t\t\t\t\t\t<td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t<tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
\t\t\t\t\t\t\t\t\t\t<tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
\t\t\t\t\t\t\t\t\t\t<tr>\t\t
\t\t\t\t\t\t\t\t\t\t<td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
\t\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t</table>\t
\t\t\t\t\t\t\t\t\t\t<br>" . $pay_link;

                                        $to_name = array();
                                        $to_email = array();

                                        if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                            // sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
                                            array_push($to_email, get_client_email($conn, $consignee_name));
                                            array_push($to_name, get_client_name($conn, $consignee_name));
                                            $mail = sendAttachments($to_name, $to_email, $mail_subject, $pdf_file_name, $image, $msg, $name);

                                            // send sms
                                            // if ($phone != '' && $contact_no  !='') {
                                            //     if (strstr($contact_no, '+91')) {
                                            //         $consigner_no  =  $contact_no;
                                            //     } else {
                                            //         //echo "Text not found";
                                            //         $consigner_no  =  "+91" . $contact_no;
                                            //     }

                                            //     if (strstr($phone, '+91')) {
                                            //         $consignee_no  =  $phone;
                                            //     } else {
                                            //         //echo "Text not found";
                                            //         $consignee_no  =  "+91" . $phone;
                                            //     }
                                            // 	$sms_number = array();
                                            //     array_push($sms_number, $consigner_no);
                                            //     array_push($sms_number, $consignee_no);
                                            // try{
                                            // $message_created = $client->messages->create([
                                            //         'src' => "GRACIX",
                                            //         "dst" => $sms_number,
                                            //         "text"  => "GR No: $grnn_no\nYour consignment has been delivered. We hope you are satisfied with our services.\nThank you for choosing us.\n\nElite Wave 360",
                                            //         "dlt_entity_id"=>"1201168767372626314",
                                            //         "dlt_template_id"=>"1207169175750985141",
                                            //         "dlt_template_category"=>"service_implicit",
                                            // ]);
                                            // }catch(Exception $err){
                                            //     $error =  $err->getMessage();
                                            // }
                                            // }
                                            // send sms

                                            // send mail to both consigner and consignee
                                            $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                            Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                            <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                            <tr>
                                            <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                            </tr><tr>\t
                                            <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                            </tr>
                                            <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                            <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                            <tr>\t\t
                                            <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                                </td>
                                            </tr>
                                            </table>\t
\t\t\t\t\t\t\t\t\t\t    <br>";

                                            $to_name1 = array();
                                            $to_email1 = array();

                                            array_push($to_email1, get_client_email($conn, $consignee_name), get_client_email($conn, $consignor_name));
                                            array_push($to_name1, get_client_name($conn, $consignee_name), get_client_name($conn, $consignor_name));

                                            sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);
                                            // echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst');
                                        }
                                    } else {
                                        // send deliver mail and sms to both consigner and consignee
                                        if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                            // $get_consignee_details =  get_client($conn, $consignee_name);
                                            // $phone = $get_consignee_details['contact_no'];
                                            // if ($phone != '' && $contact_no  !='') {
                                            //     if (strstr($contact_no, '+91')) {
                                            //         $consigner_no  =  $contact_no;
                                            //     } else {
                                            //         //echo "Text not found";
                                            //         $consigner_no  =  "+91" . $contact_no;
                                            //     }

                                            //     if (strstr($phone, '+91')) {
                                            //         $consignee_no  =  $phone;
                                            //     } else {
                                            //         //echo "Text not found";
                                            //         $consignee_no  =  "+91" . $phone;
                                            //     }

                                            //     $sms_number = array();
                                            //     array_push($sms_number, $consigner_no);
                                            //     array_push($sms_number, $consignee_no);

                                            //     $sms_message = "GR No: " . $grnn_no . " Your consignment has been delivered. We hope you are satisfied with our services.\n\nThank you for choosing us.\nElite Wave 360";
                                            // try{
                                            //     $message_created = $client->messages->create([
                                            //         'src' => "GRACIX",
                                            //         "dst" => $sms_number,
                                            //         "text"  => "GR No: $grnn_no\nYour consignment has been delivered. We hope you are satisfied with our services.\nThank you for choosing us.\n\nElite Wave 360",
                                            //         "dlt_entity_id"=>"1201168767372626314",
                                            //         "dlt_template_id"=>"1207169175750985141",
                                            //         "dlt_template_category"=>"service_implicit",
                                            //     ]);
                                            // }catch(Exception $err){
                                            //     $error =  $err->getMessage();
                                            // }
                                            // }

                                            // send mail to both consigner and consignee
                                            $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                            Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                            <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                            <tr>
                                            <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                            </tr><tr>\t
                                            <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                            </tr>
                                            <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                            <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                            <tr>\t\t
                                            <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                                </td>
                                            </tr>
                                            </table>\t
\t\t\t\t\t\t\t\t\t\t    <br>";

                                            $to_name1 = array();
                                            $to_email1 = array();

                                            array_push($to_email1, get_client_email($conn, $consignee_name), get_client_email($conn, $consignor_name));
                                            array_push($to_name1, get_client_name($conn, $consignee_name), get_client_name($conn, $consignor_name));

                                            sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);
                                        }
                                    }
                                }

                                // End
                            } else {
                                $check_partywise_frq = checkPartyWiseFrequency($conn, $consignor_name);  // Check Frequency set or not
                                // echo $check_partywise_frq;
                                if ($check_partywise_frq == 0) {  // Frequncy is Set
                                    // Invoice Sent as per frequency
                                    // echo "Frequency is Set";
                                    // send deliver mail and sms to both consigner and consignee
                                    if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                        // $get_consigner_details =  get_client($conn, $consignor_name);
                                        // $phone = $get_consigner_details['contact_no'];
                                        // $get_consignee_details =  get_client($conn, $consignee_name);
                                        // $contact_no = $get_consignee_details['contact_no'];

                                        // if ($phone != '' && $contact_no  !='') {
                                        //     if (strstr($phone, '+91')) {
                                        //         $consigner_no  =  $phone;
                                        //     } else {
                                        //         //echo "Text not found";
                                        //         $consigner_no  =  "+91" . $phone;
                                        //     }

                                        //     if (strstr($contact_no, '+91')) {
                                        //         $consignee_no  =  $contact_no;
                                        //     } else {
                                        //         //echo "Text not found";
                                        //         $consignee_no  =  "+91" . $contact_no;
                                        //     }

                                        //     $sms_number = array();
                                        //     array_push($sms_number, $consigner_no);
                                        //     array_push($sms_number, $consignee_no);

                                        //     $sms_message = "GR No: " . $grnn_no . " Your consignment has been delivered. We hope you are satisfied with our services.\n\nThank you for choosing us.\nElite Wave 360";
                                        // try{
                                        // $message_created = $client->messages->create([
                                        //         'src' => "GRACIX",
                                        //         "dst" => $sms_number,
                                        //         "text"  => "GR No: $grnn_no\nYour consignment has been delivered. We hope you are satisfied with our services.\nThank you for choosing us.\n\nElite Wave 360",
                                        //         "dlt_entity_id"=>"1201168767372626314",
                                        //         "dlt_template_id"=>"1207169175750985141",
                                        //         "dlt_template_category"=>"service_implicit",
                                        //     ]);
                                        // }catch(Exception $err){
                                        //     $error =  $err->getMessage();
                                        // }
                                        // }

                                        // send mail to both consigner and consignee
                                        $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                        Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                        <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                        <tr>
                                        <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                        </tr><tr>\t
                                        <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                        </tr>
                                        <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                        <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                        <tr>\t\t
                                        <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                            </td>
                                        </tr>
                                        </table>\t
                                        <br>";

                                        $to_name1 = array();
                                        $to_email1 = array();

                                        array_push($to_email1, get_client_email($conn, $consignor_name), get_client_email($conn, $consignee_name));
                                        array_push($to_name1, get_client_name($conn, $consignor_name), get_client_name($conn, $consignee_name));

                                        sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);
                                    }
                                } else {
                                    $check_restricted = check_invoice_restricted($conn, $consignor_name);
                                    if ($check_restricted == 0 && $mode_of_consignment != '3') {
                                        // Sent Payment Link to Consignor
                                        $get_client_details = get_client($conn, $consignor_name);
                                        $company_name = $get_client_details['client_company_name'];
                                        $email = $get_client_details['email'];
                                        $phone = $get_client_details['contact_no'];
                                        $amount_array = array($balance);
                                        $get_transaction_id_arr = array($get_transaction_id);
                                        $grnn_date_array = array($grnn_date);
                                        // $total_array = array($total);
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

                                        // End Sent Payment Link to Consignee
                                        $dir = 'digital_invoice/';
                                        // $pdf_file_name = $dir . $row2['table_name'] . "_" . $get_transaction_id . "invoice.pdf";
                                        $pdf_file_name = '';

                                        // echo "pdf_file_name: ".$pdf_file_name;

                                        // , Please Find Your Invoice Attached (in PDF Format) to this email
                                        // Send Mail
                                        $msg = "<p style=\"line-height: 24px; margin-bottom:15px;\">
\t\t\t\t\t\t\t\t\t\t\tThank You for Your Order On <a href = \"https://elitewave360.in\" >Elite Wave 360</a> on " . $grnn_date . "! <br>
\t\t\t\t\t\t\t\t\t\t\tFollowing Your Successful Consignment Delivery. \t\t\t\t
\t\t\t\t\t\t\t\t\t\t\t<table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
\t\t\t\t\t\t\t\t\t\t\t<tr>
\t\t\t\t\t\t\t\t\t\t\t<td >GCN No\t</td><td >" . $grnn_no . "</td>
\t\t\t\t\t\t\t\t\t\t\t</tr><tr>\t
\t\t\t\t\t\t\t\t\t\t\t<td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
\t\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t\t<tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
\t\t\t\t\t\t\t\t\t\t\t<tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
\t\t\t\t\t\t\t\t\t\t\t<tr>\t\t
\t\t\t\t\t\t\t\t\t\t\t<td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
\t\t\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t</table>\t
\t\t\t\t\t\t\t\t\t\t\t<br>
\t\t\t\t\t\t\t\t\t\t\t<br>
\t\t\t\t\t\t\t\t\t\t\tPayment Link : <a href = \"https://elitewave360.in/verify_paylink1.php?data=" . urlencode($link_wit_data) . '" >Payment Link</a>';
                                        $to_name = array();
                                        $to_email = array();

                                        if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                            // sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
                                            array_push($to_email, get_client_email($conn, $consignor_name));
                                            array_push($to_name, get_client_name($conn, $consignor_name));
                                            $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification  With Payment Link', $pdf_file_name, $image, $msg, $name);

                                            // send sms
                                            //  	$get_consignee_details =  get_client($conn, $consignee_name);
                                            //     $contact_no = $get_consignee_details['contact_no'];

                                            //    if ($phone != '' && $contact_no  !='') {
                                            //         if (strstr($phone, '+91')) {
                                            //             $consigner_no  =  $phone;
                                            //         } else {
                                            //             //echo "Text not found";
                                            //             $consigner_no  =  "+91" . $phone;
                                            //         }

                                            //         if (strstr($contact_no, '+91')) {
                                            //             $consignee_no  =  $contact_no;
                                            //         } else {
                                            //             //echo "Text not found";
                                            //             $consignee_no  =  "+91" . $contact_no;
                                            //         }

                                            //         $sms_number = array();
                                            //         array_push($sms_number, $consigner_no);
                                            //         array_push($sms_number, $consignee_no);
                                            // try{
                                            //    $message_created = $client->messages->create([
                                            // 		'src' => "GRACIX",
                                            // 		"dst" => $sms_number,
                                            // 		"text"  => "GR No: $grnn_no\nYour consignment has been delivered. We hope you are satisfied with our services.\nThank you for choosing us.\n\nElite Wave 360",
                                            // 		"dlt_entity_id"=>"1201168767372626314",
                                            // 		"dlt_template_id"=>"1207169175750985141",
                                            // 		"dlt_template_category"=>"service_implicit",
                                            //    ]);
                                            // }catch(Exception $err){
                                            //     $error =  $err->getMessage();
                                            // }
                                            //     }
                                            // send sms
                                            // send mail to both consigner and consignee
                                            $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                            Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                            <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                            <tr>
                                            <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                            </tr><tr>\t
                                            <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                            </tr>
                                            <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                            <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                            <tr>\t\t
                                            <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                                </td>
                                            </tr>
                                            </table>\t
\t\t\t\t\t\t\t\t\t\t    <br>";

                                            $to_name1 = array();
                                            $to_email1 = array();

                                            array_push($to_email1, get_client_email($conn, $consignor_name), get_client_email($conn, $consignee_name));
                                            array_push($to_name1, get_client_name($conn, $consignor_name), get_client_name($conn, $consignee_name));

                                            sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);
                                            // echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst');
                                        }
                                    } else {
                                        // send deliver mail and sms to both consigner and consignee
                                        if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                            // $get_consigner_details =  get_client($conn, $consignor_name);
                                            // $phone = $get_consigner_details['contact_no'];
                                            // $get_consignee_details =  get_client($conn, $consignee_name);
                                            // $contact_no = $get_consignee_details['contact_no'];

                                            // if ($phone != '' && $contact_no  !='') {
                                            //     if (strstr($phone, '+91')) {
                                            //         $consigner_no  =  $phone;
                                            //     } else {
                                            //         //echo "Text not found";
                                            //         $consigner_no  =  "+91" . $phone;
                                            //     }

                                            //     if (strstr($contact_no, '+91')) {
                                            //         $consignee_no  =  $contact_no;
                                            //     } else {
                                            //         //echo "Text not found";
                                            //         $consignee_no  =  "+91" . $contact_no;
                                            //     }

                                            //     $sms_number = array();
                                            //     array_push($sms_number, $consigner_no);
                                            //     array_push($sms_number, $consignee_no);

                                            //     $sms_message = "GR No: " . $grnn_no . " Your consignment has been delivered. We hope you are satisfied with our services.\n\nThank you for choosing us.\nElite Wave 360";

                                            // try{
                                            // $message_created = $client->messages->create([
                                            //         'src' => "GRACIX",
                                            //         "dst" => $sms_number,
                                            //         "text"  => "GR No: ".$grnn_no."\nYour consignment has been delivered. We hope you are satisfied with our services.\nThank you for choosing us.\n\nElite Wave 360",
                                            //         "dlt_entity_id"=>"1201168767372626314",
                                            //         "dlt_template_id"=>"1207168907785857725",
                                            //         "dlt_template_category"=>"service_implicit",
                                            // ]);
                                            // }catch(Exception $err){
                                            //     $error =  $err->getMessage();
                                            // }
                                            // }

                                            // send mail to both consigner and consignee
                                            $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                            Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                            <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                            <tr>
                                            <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                            </tr><tr>\t
                                            <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                            </tr>
                                            <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                            <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                            <tr>\t\t
                                            <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                                </td>
                                            </tr>
                                            </table>\t
\t\t\t\t\t\t\t\t\t\t    <br>";

                                            $to_name1 = array();
                                            $to_email1 = array();

                                            array_push($to_email1, get_client_email($conn, $consignor_name), get_client_email($conn, $consignee_name));
                                            array_push($to_name1, get_client_name($conn, $consignor_name), get_client_name($conn, $consignee_name));

                                            sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);
                                        }
                                    }
                                }

                                // End
                            }
                        } else {
                            // echo "Already Invoice Sent";
                        }
                    } else {
                        // Status Report send through email
                        $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                            <p style="color:black;">Your Booking Status:  <span>' . get_trans_status($status) . ' </span> - </br>
                            Against Your GCN No: <span>' . $grn_number . $phone . ' </span></p></p></br>
                            <p><a href="https://elitewave360.in/tracking.php">Click to track consignment</a></p>';

                        //                         $to_name = (get_client_name($conn, $consigners));

                        //                         $to_email = (get_client_email($conn, $consigners));
                        $to_name = array();
                        $to_email = array();

                        if (!empty(get_client_email($conn, $consigners)) && !empty(get_client_email($conn, $consignee_name))) {
                            // sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
                            array_push($to_email, get_client_email($conn, $consigners), get_client_email($conn, $consignee_name));
                            array_push($to_name, get_client_name($conn, $consigners), get_client_name($conn, $consignee_name));
                        }
                        // print_r($to_email);

                        // send sms
                        // $get_consignee_details =  get_client($conn, $consignee_name);
                        // $con_phone = $get_consignee_details['contact_no'];
                        // if ($con_phone != '' && $contact_no  !='') {
                        // if (strstr($contact_no, '+91')) {
                        //     $consigner_no  =  $contact_no;
                        // } else {
                        //     $consigner_no  =  "+91" . $contact_no;
                        // }

                        // if (strstr($con_phone, '+91')) {
                        //     $consignee_no  =  $con_phone;
                        // } else {
                        //     $consignee_no  =  "+91" . $con_phone;
                        // }

                        // $sms_number = array();
                        // array_push($sms_number, $consigner_no);
                        // array_push($sms_number, $consignee_no);

                        // $grn_status = get_cons_status_sms($status);
                        // $grn_no = $grn_number;

                        // if($get_status == 2){
                        //     $sms_message="We would like to inform you that your consignment has been $grn_status. Please find the tracking details provided below.\nGR No: $grn_no\n\nThank you for choosing our service!\nElite Wave 360";

                        //     $template_id = "1207169175732830186";
                        // }else if($get_status >= 3 && $get_status <= 6 ){
                        //     $sms_message="We would like to inform you that your consignment is in $grn_status. Please find the tracking details provided below.\nGR No: $grn_no\n\nThank you for choosing our service!\nElite Wave 360";

                        //     $template_id = "1207169175736268132";
                        // }else if($get_status == 7){
                        //     $sms_message="We would like to inform you that your consignment is currently $grn_status. Please find the tracking details provided below.\nGR No: $grn_no\n\nThank you for choosing our service!\nElite Wave 360";

                        //     $template_id = "1207169175746615267";
                        // }

                        // try{
                        // $message_created = $client->messages->create([
                        //     'src' => "GRACIX",
                        //     "dst" => $sms_number,
                        //     "text"  => $sms_message,
                        //     "dlt_entity_id"=>"1201168767372626314",
                        //     "dlt_template_id"=>$template_id,
                        //     "dlt_template_category"=>"service_implicit",
                        // ]);
                        // }catch(Exception $err){
                        //     $error =  $err->getMessage();
                        // }
                        // }
                        // send sms
                        // $mail = sendAppMail($to_name, $to_email, 'Booking Status', $msg1);
                        // End
                    }
                    // *End Send invoice
                    echo 1;
                } else {
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

if ($form_name == 'edit_change_grn_status') {
    $grn_id_arr = $_POST['grn_id'];
    $grn_id = implode(',', $_POST['grn_id']);
    $status = $_POST['status'];
    $origin      = ($_POST['origin'] === '') ? "NULL" : (int)$_POST['origin'];
    $destination = ($_POST['destination'] === '') ? "NULL" : (int)$_POST['destination'];
    $mode        = ($_POST['mode'] === '') ? "NULL" : (int)$_POST['mode'];
    $remarks = $_POST['remarks'];
    $sheet_id = $_POST['edit_id'];
    $del_ids = implode(',', $_POST['del_ids']);
    $del_ids_arr = explode(',', $del_ids);

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
                $query2 = 'SELECT * FROM transaction_tbls';
                $result2 = mysqli_query($conn, $query2) or die(mysqli_error($conn));
                while ($row2 = mysqli_fetch_assoc($result2)) {
                    $query = 'select * from transaction_' . $row2['table_name'] . " where grn_id ='$old_grn_id'";
                    $result = mysqli_query($conn, $query);
                    if (mysqli_num_rows($result) > 0) {
                        $row = mysqli_fetch_array($result);

                        $query1 = 'update transaction_' . $row2['table_name'] . " set status='" . $oldrow['from_status'] . "' where grn_id='$old_grn_id'";
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
            $query_max = mysqli_query($conn, 'select * from transaction_log where client_id=0');
            $r_max = mysqli_fetch_array($query_max);
            $id = $r_max['grn_id'] + 1;
            $grn_no = 'LA' . $_POST['grn_no1'];
        }
    }

    // $grn_no = $_POST['grn_no'];
    $grn_date = $_POST['grn_date'];
    $consignor = $_POST['consignor'];
    $consignee = $_POST['sel-consignee'];

    $consignor_branch_id=$_POST['consignor_branch'];
$consignee_branch_id=$_POST['consignee_branch'];

    // Fetch active company details
    $company_query = mysqli_query($conn, 'SELECT company_id, company_code, grn_mode FROM company WHERE status=0 LIMIT 1');
    $company_row = mysqli_fetch_array($company_query);
    $comp_id = isset($company_row['company_id']) ? $company_row['company_id'] : 2;
    $comp_code = isset($company_row['company_code']) ? $company_row['company_code'] : '';
    $comp_grn_mode = isset($company_row['grn_mode']) ? $company_row['grn_mode'] : 'company';

    if ($comp_grn_mode === 'company') {
        $grn_type_db = 'company';
    } else {
        $grn_type_db = 'party';
    }

    // Generate unique 12-digit numeric tracking code
    $tracking_code = '';
    do {
        $tracking_code = '';
        for ($i = 0; $i < 12; $i++) {
            $tracking_code .= rand(0, 9);
        }
        $chk_unique = mysqli_query($conn, "SELECT id FROM transaction_log WHERE tracking_code='$tracking_code'");
    } while (mysqli_num_rows($chk_unique) > 0);

    $consignorquery = "select * from client where client_id='$consignor'";
    $consignorresult = mysqli_query($conn, $consignorquery);
    $consignorrow = mysqli_fetch_array($consignorresult);
    $billing_code = $consignorrow['billing_code'];

    $address1 = $consignorrow['address1'];
    $address2 = $consignorrow['address2'];
    $city = $consignorrow['city'];
    $pincode = $consignorrow['pincode'];
    $state = $consignorrow['state'];
    $phone = $consignorrow['contact_no'];
    $gst_no = $consignorrow['gst_no'];

    // Get Latest GCN No
    // Get Latest GCN No
    $id = get_next_grn_id($conn, $consignor);
    $grn_no = strtoupper($billing_code . sprintf('%05d', $id));
    // echo $grn_no;

    $consigneequery = "select * from client where client_id='$consignee'";
    $consigneeresult = mysqli_query($conn, $consigneequery);
    $consigneerow = mysqli_fetch_array($consigneeresult);

    $con_address1 = $consigneerow['address1'];
    $con_address2 = $consigneerow['address2'];
    $con_city = $consigneerow['city'];
    $con_state = $consigneerow['state'];
    $con_pincode = $consigneerow['pincode'];
    $con_phone = $consigneerow['contact_no'];
    $con_gst = $consigneerow['gst_no'];

    if ($_POST['air'] != '') {  // *GR Copy Send to Consignor Without Payment Info

        $shipping_mode = $_POST['air'];
    } else if ($_POST['train'] != '') {
        $shipping_mode = $_POST['train'];
    } else if ($_POST['roadsurface'] != '') {
        if ($_POST['ftl'] != '') {  // *FTL Quotation Send to Admin

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
    } else if ($_POST['paid'] != '') {  // *GR + Invoice Send to Consignor //Paid Replace to Pay at Booking

        $pay_mode = $_POST['paid'];
    } else {
        $pay_mode = $_POST['cod'];
    }

    // *Package Details

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

    // Train type and Charges
    $train_name = $_POST['train_type'];
    $rajdhani_charges = $_POST['rajdhani-express-charges'];

    $len = $_POST['length'] ? $_POST['length'] : null;
    $wid = $_POST['width'] ? $_POST['width'] : null;
    $hei = $_POST['height'] ? $_POST['height'] : null;
    $quanti = $_POST['quanti'] ? $_POST['quanti'] : null;
    $vlm_wei = $_POST['vlm_weight'] ? $_POST['vlm_weight'] : null;

    // Shipping Address
    $ship_address = $_POST['shipping_address'] ? $_POST['shipping_address'] : null;

    $eway_expiryDate = $_POST['eway_expiryDates'] ? $_POST['eway_expiryDates'] : null;
    // $consignmet_weight1 = $_POST['weight1'];

    $query = "insert into $tables[0](grn_no,grn_id,grn_date,mode_of_transportation,train_type,ftl_type,origin,destination,mode_of_consignment,consigner,address1,address2,
\t\t\t\tcity,pincode,state,phone,gst_no,consignee,con_address1,con_address2,shipping_address,con_city,con_state,con_pincode,con_phone,
\t\t\t\tcon_gst_no,goods_dedared_value,octroi,dimension1,dimension2,dimension3,dimension4,consignment_weight,frieght_rate,frieght_amount,loading_unloading_rate,
\t\t\t\tloading_unloading_amount, crane_fork_lift_rate, crane_fork_lift_amount,cod_rate,cod_amount,fov_rate,fov_amount,doc_charges,
\t\t\t\tdoc_amount,cartage_rate,cartage_amount,labour_handling_rate,labour_handling_amount,octroi_rate,octroi_amount,other_charge_rate,
\t\t\t\tother_charge_amount,rajdhani_charges,gst_rate,gst_amount,total,paid_amount, balance, paid_status,total_words,note1,note2,truck,consigner_signature,client_id,created_at,created_by,status,eway_number,eway_expirydate, consignor_branch_id, consignee_branch_id) 
\t\t\t\tvalues('" . $grn_no . "','" . $id . "','" . $grn_date . "','" . $shipping_mode . "','$train_name','" . $ftl_type . "','" . $origin . "','" . $destination . "','" . $pay_mode . "','" . $consignor . "',
\t\t\t\t'" . $address1 . "','" . $address2 . "','" . $city . "','" . $pincode . "','" . $state . "','" . $phone . "','" . $gst_no . "','" . $consignee . "','" . $con_address1 . "',
\t\t\t\t'" . $con_address2 . "','$ship_address','" . $con_city . "','" . $con_state . "','" . $con_pincode . "','" . $con_phone . "','" . $con_gst . "','" . $goods_dedared_value . "',
\t\t\t\t'" . $octroi . "','$len','$wid','$hei','$quanti','$vlm_wei','" . $frieght_rate . "','" . $frieght_amount . "','" . $loading_unloading_rate . "','" . $loading_unloading_amount . "','" . $crane_fork_lift_rate . "','" . $crane_fork_lift_amount . "','" . $cod_rate . "',
\t\t\t\t'" . $cod_amount . "','" . $fov_rate . "','" . $fov_amount . "','" . $doc_rate . "','" . $doc_amount . "','" . $cartage_rate . "','" . $cartage_amount . "',
\t\t\t\t'" . $labour_rate . "','" . $labour_amount . "','" . $octroi_rate . "','" . $octroi_amount . "','" . $other_rate . "','" . $other_amount . "','$rajdhani_charges',
\t\t\t\t'" . $gst_rate . "','" . $gst_amount . "','" . $total . "','0','" . $total . "','0','" . $total_amount_word . "','" . $note1 . "','" . $note2 . "','" . $vehicle_no . "',
\t\t\t\t'" . $signature . "','" . $consignor . "','" . $created_at . "','" . $created_by . "','1','" . $eway_number . "','$eway_expiryDate', '" . $consignor_branch_id . "', '" . $consignee_branch_id . "')";
    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));
    $transaction_id = mysqli_insert_id($conn);
$attachment_id = NULL;
$invoice_id    = NULL;
    for ($k = 0; $k < count($_FILES['file_receipt']['name']); $k++) {
        $file_name = uniqid() . $_FILES['file_receipt']['name'][$k];
        if (move_uploaded_file($_FILES['file_receipt']['tmp_name'][$k], 'invoice_image/' . $file_name)) {  // images/

            $fr_query = "insert into $tables[1](transaction_id,attachment,created_at,created_by,status) values ('$transaction_id','$file_name','$created_at','$created_by','0')";
            $fr_result = mysqli_query($conn, $fr_query) or die(mysqli_error($conn));
            $attachment_id = mysqli_insert_id($conn);
        }
    }
    for ($j = 0; $j < count($_POST['package-qty']); $j++) {
        // $countss[] = $qty[$j];

        $f_query = "insert into $tables[2](transaction_id,no_of_pkge,type_of_pkge,party_invoice_no,said_contents,qty,gross_weight,charged_weight,created_at,created_by,status) values('" . $transaction_id . "','" . $no_of_pkg[$j] . "','" . $type_of_pkg[$j] . "','" . $party_invoice[$j] . "','" . $content[$j] . "','" . $qty[$j] . "','" . $gross[$j] . "','" . $charged[$j] . "','" . $created_at . "','" . $created_by . "','0')";
        $f_result = mysqli_query($conn, $f_query) or die(mysqli_error($conn));

        // $package[] = $qty[$j];
        $package[] = $no_of_pkg[$j];

        $pkg_name[] = $type_of_pkg[$j];

        // var_dump($pkg_name);

        // $total_pkg +=$_POST['no_of_pkg'];
    }
    // Barcode Start
    // require 'vendor/autoload.php'; For Barcode
    include('libs/phpqrcode/qrlib.php');
    $result_bar = [];

    foreach ($pkg_name as $index => $val) {
        $result_bar[$val] = ($result_bar[$val] ?? 0) + $package[$index];
    }
    $package_type1 = (array_keys($result_bar));
    $packge_qty = (array_values($result_bar));
    // $redColor = [0, 0, 0];
    // $generator = new Picqer\Barcode\BarcodeGeneratorJPG();
    $name = $grn_no;
    // var_dump($qty);

    // $rate = 10;

    foreach ($packge_qty as $key => $val) {
        $get_qty = $val;
        // var_dump($get_qty);
        // "KEY".$key. "value". $val;
        if (array_key_exists($key, $package_type1)) {
            $get_package = $package_type1[$key];
            // var_dump($get_package);

            switch ($get_package) {
                case '1':
                    $pack_name = 'CBX';
                    break;
                case '2':
                    $pack_name = 'PBG';
                    break;
                case '3':
                    $pack_name = 'ROL';
                    break;
                case '5':
                    $pack_name = 'SHT';
                    break;
                case '6':
                    $pack_name = 'BDL';
                    break;
                case '7':
                    $pack_name = 'CVR';
                    break;
                case '8':
                    $pack_name = 'PBL';
                    break;
                case '9':
                    $pack_name = 'CAN';
                    break;
                case '10':
                    $pack_name = 'BOX';
                    break;
                case '11':
                    $pack_name = 'BAG';
                    break;
                case '12':
                    $pack_name = 'MLD';
                    break;
                case '13':
                    $pack_name = 'PKT';
                    break;
                case '14':
                    $pack_name = 'CES';
                    break;
                case '15':
                    $pack_name = 'CAT';
                    break;
                case '16':
                    $pack_name = 'GRL';
                    break;
                case '17':
                    $pack_name = 'P.B';
                    break;
                case '18':
                    $pack_name = 'PRL';
                    break;
                default:
                    $pack_name = 'No Package Type Found!';
            }

            // $productData = "098{$get_qty}10{$name}55{$rate}";
            $tempDir = 'qrcode/';
            $productData = strtoupper($name);
            $j = 1;
            for ($i = 0; $i < $get_qty; $i++) {
                $change_index[$j] = $i + 1;
                $names = $productData . $pack_name . '-00' . $change_index[$j];
                $contents = 'https://elitewave360.in/web/testqrcode.php?grn_no=' . $name . '&grn_date=' . $grn_date;

                // var_dump($names);
                // Barcode
                // file_put_contents('barcode/'.$names.'.jpg', $generator->getBarcode($names, $generator::TYPE_CODE_128,3,100,$redColor));

                // Qrcode
                QRcode::png($contents, $tempDir . '' . $names . '.png', QR_ECLEVEL_L, 5);

                $j++;
            }
        }
    }
    // Barcode End

    $invoice_id = mysqli_insert_id($conn);

    if ($transaction_id) {
        $log_client_id = ($comp_grn_mode === 'company' && isset($_SESSION['company_id'])) ? $_SESSION['company_id'] : $consignor;
        $query_log = mysqli_query($conn, "INSERT INTO transaction_log
            (transaction_id, attachment_id, invoice_id, grn_id, grn_no, client_id, grn_type, company_id, tracking_code)
            VALUES ('$transaction_id','$attachment_id','$invoice_id','$id','$grn_no','$log_client_id','$grn_type_db','$comp_id','$tracking_code')");
if (!$query_log) {
    die(mysqli_error($conn));
}
        // Barcode Started
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
        // Barcode Started

        $invi = '';
        for ($i = 0; $i < count($party_invoice); $i++) {
            if ($party_invoice[$i] != '') {
                $invi .= $party_invoice[$i] . ',';
            }
        }

        $invi = rtrim($invi, ',');
        // $url = "https://elitewave360.in/web/user_transaction_pdf.php?month=" . $month . "&year=" . $year . "&id=" . $transaction_id . "&copy=consignor";
        $url = 'https://elitewave360.in/web/transaction_pdf.php?month=' . $month . '&year=' . $year . '&id=' . $transaction_id . '&copy=consignor';
        $path = 'transaction_pdf/' . $month . '_' . $year . '_' . $transaction_id . 'transaction.pdf';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        $result_url = file_put_contents($path, $data);

        // *Invoice Section Start
        // Sequence Generation

        if ($shipping_mode != '7') {  // Check if not FTL

            if ($shipping_mode == '1' || $shipping_mode == '2' || $shipping_mode == '3') {
                $type = 'GST';
                // $sac = "996812";
                // $sac_text = '996812 - COURIER SERVICES';
            } else {
                $type = 'GTA';
                // $sac = "9965";
                // $sac_text = '9965 - Good Transport Agency Service';
            }
            // $conn1 = mysqli_connect("localhost","root","","bookconsignment");

            $grn_date_expl = explode('-', $grn_date);
            $cur_year = $grn_date_expl[2];

            $current_year = $cur_year;

            $previous_year = $cur_year - 1;

            $p_y = substr($previous_year, 2);
            $c_y = substr($current_year, 2);

            $year_insert = $p_y . '-' . $c_y;

            $invoice_table = invoice_table_function($conn, $grn_date);

            $select = mysqli_query($conn, 'select * from ' . $invoice_table);
            $get_count = mysqli_num_rows($select);
            if ($get_count == 0) {
                $insert_data = 'INSERT INTO ' . $invoice_table . "(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('0','HRGST','$year_insert','GST','$created_at','$created_by'),('0','HRGTA','$year_insert','GTA','$created_at','$created_by')";
                // $insert_data .= "INSERT INTO ".$invoice_table."(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('1','HRGTA','$year_insert','GTA','$created_at','$created_by')";
                // $res = mysqli_multi_query($conn,$insert_data);
                $res = mysqli_query($conn, $insert_data);
                if ($res) {
                    $inv_query = 'select * from trans_invoice_tbl' . $year . " where inv_type='$type'";
                    $inv_query_result = mysqli_query($conn, $inv_query);
                    $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                    $inv_seq = $inv_query_row['invoice_no'] + 1;
                    // print_r($inv_seq);
                    // $inv_seq = '100';
                    $inv_text = $inv_query_row['gst_text'];
                    $inv_year = $inv_query_row['gst_year'];
                    $sequence = sprintf('%05d', $inv_seq);
                    $unique_invoice_no = $inv_text . '/' . $sequence . '/' . $inv_year;
                    // print_r($unique_invoice_no);
                }
            } else {
                $inv_query = 'select * from trans_invoice_tbl' . $year . " where inv_type='$type'";
                $inv_query_result = mysqli_query($conn, $inv_query);
                $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                $inv_seq = $inv_query_row['invoice_no'] + 1;
                // print_r($inv_seq);
                // $inv_seq = '100';
                $inv_text = $inv_query_row['gst_text'];
                $inv_year = $inv_query_row['gst_year'];
                $sequence = sprintf('%05d', $inv_seq);
                $unique_invoice_no = $inv_text . '/' . $sequence . '/' . $inv_year;
            }

            // Sequence Generation

            $directory = 'digital_invoice/';
            $invoice_url = 'https://elitewave360.in/web/gst_invoice_page.php?month=' . $month . '&year=' . $year . '&id=' . $transaction_id . '&invoice_no=' . $unique_invoice_no . '';
            $invoice_file_name = $month . '_' . $year . '_' . $transaction_id . 'invoice';
            $download_path = $directory . $invoice_file_name . '.pdf';
            $file_inv_download = curl_init($invoice_url);
            curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($file_inv_download, CURLOPT_REFERER, true);
            curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
            $store_inv = curl_exec($file_inv_download);
            curl_close($file_inv_download);
            $save_inv_file = file_put_contents($download_path, $store_inv);
            if ($save_inv_file) {
                $update = mysqli_query($conn, 'update trans_invoice_tbl' . $year . " SET invoice_no = '$inv_seq', updated_by = '$updated_by', updated_at = '$updated_at' where inv_type = '$type'");

                $query_inv = "update $tables[0] set `invoice_no` = '$unique_invoice_no' where transaction_id ='$transaction_id'";
                $res = mysqli_query($conn, $query_inv);
            }
        }
        // *Invoice Section End

        $image = array();
        $img_query = mysqli_query($conn, "select * from $tables[1] where transaction_id ='" . $transaction_id . "'");
        if (mysqli_num_rows($img_query) > 0) {
            while ($img_result = mysqli_fetch_array($img_query)) {
                array_push($image, 'invoice_image/' . $img_result['attachment']);
            }
        }
        // print_r($image);
        $msg = "<p style=\"line-height: 24px; margin-bottom:15px;\">
\t\t\t\t\t\t  
\t\t\tThank you for booking the consignment, please find the booking information and the attached GR copy for your reference below.\t\t\t\t\t
\t\t\t<table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
\t\t\t<tr>
\t\t\t<td >GCN No\t</td><td >" . $grn_no . "</td>
\t\t\t</tr><tr>\t
\t\t\t<td >GCN Date:\t</td><td >\t" . $grn_date . "\t</td>\t
\t\t\t</tr>
\t\t\t<tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . "</td>\t</tr>\t
\t\t\t<tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . "</td>\t</tr>\t
\t\t\t<tr>\t\t
\t\t\t<td >Your Invoice No\t</td><td >\t" . $invi . "\t</td>\t
\t\t\t</tr><tr>\t\t
\t\t\t<td >Status\t</td><td >Consignment Booked\t</td>\t\t
\t\t\t\t</td>
\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t</table>\t
\t\t\t<br>
\t\t\t<br>";

        $to_name = array();
        $to_email = array();

        if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
            // sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)
            array_push($to_email, get_client_email($conn, $consignor), get_client_email($conn, $consignee));
            array_push($to_name, get_client_name($conn, $consignor), get_client_name($conn, $consignee));

            $mail = sendAttachments($to_name, $to_email, 'Consignment Booking Notification', $path, $image, $msg, $name);

            // echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst');
            // send sms
            // if ($phone != '' && $con_phone  !='') {
            //     if (strstr($phone, '+91')) {
            //         $consigner_no  =  $phone;
            //     } else {
            //         $consigner_no  =  "+91" . $phone;
            //     }

            //     if (strstr($con_phone, '+91')) {
            //         $consignee_no  =  $con_phone;
            //     } else {
            //         $consignee_no  =  "+91" . $con_phone;
            //     }

            //     $sms_number = array();
            //     array_push($sms_number, $consigner_no);
            //     array_push($sms_number, $consignee_no);

            //     $consignor_name = get_client_name($conn, $consignor);
            //     $consignor_name_wrap = strlen($consignor_name) > 27 ? substr($consignor_name,0,27)."..." : $consignor_name;
            //     $grno_date = $grn_no.' - '.$grn_date;

            //     try{
            //         $message_created = $client->messages->create([
            //             'src' => "GRACIX",
            //             "dst" => $sms_number,
            //             "text"  => "Your shipment has been successfully booked.\nHere are the details:\nConsignor Name: $consignor_name_wrap\nGR No & Date: $grno_date\n\nThank you for choosing Elite Wave 360 for your shipment. Have a great day!",
            //             "dlt_entity_id"=>"1201168767372626314",
            //             "dlt_template_id"=>"1207169175728319365",
            //             "dlt_template_category"=>"service_implicit",
            //     ]);
            //     }catch(Exception $err){
            //         $error =  $err->getMessage();
            //     }
            // }
            // send sms
        }
        /*if(!empty(get_client_email($conn,$consignor))){
        $mail = sendAppMail(get_client_name($conn,$consignor),get_client_email($conn,$consignor), 'Consignment Booking Notification | '.$grn_no.' To {'.get_client_name($conn,$consignee).'}', $msg);
}
if(!empty(get_client_email($conn,$consignee))){
        $mail = sendAppMail(get_client_name($conn,$consignee),get_client_email($conn,$consignee), 'Consignment Booking Notification | '.$grn_no.' To {'.get_client_name($conn,$consignee).'}', $msg);
}*/
        // *Send Invoice Instanly
        if ($pay_mode == '3' || $pay_mode == '4') {  // Pay at Booking || Cash on Delivery

            if ($pay_mode == '3') {  // Paid or Pay at Booking

                // echo "3";
                // 	$msg = '<p style="line-height: 24px; margin-bottom:15px;">
                // Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
                // Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email.
                // <table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
                // <tr>
                // <td >GCN No	</td><td >' . $grn_no . '</td>
                // </tr><tr>
                // <td >GCN Date:	</td><td >	' . $grn_date . '	</td>
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
                //                 //echo "4";
                //                 //$check_partywise_frq = checkPartyWiseFrequency($conn, $consignee); // Check Frequency set or not
                //                 // if ($check_partywise_frq == 0) { // Frequncy is Set
                //                 //     //Invoice Sent as per frequency
                //                 //     echo "Frequency is Set";
                //                 // } else {
                //                 //Invoice Sent Instantly if not restricted
                //                 //     $check_restricted = check_invoice_restricted($conn, $consignee);

                //                 //     if ($check_restricted == 0) {

                //                 //         //Need to createPayment Link

                //                 //         //End Payment Link
                //                 $msg = '<p style="line-height: 24px; margin-bottom:15px;">
                // 								Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
                // 								Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email.
                // 								<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
                // 								<tr>
                // 								<td >GCN No	</td><td >' . $grn_no . '</td>
                // 								</tr><tr>
                // 								<td >GCN Date:	</td><td >	' . $grn_date . '	</td>
                // 								</tr>
                // 								<tr><td >Booked By	</td><td >' . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . '</td>	</tr>
                // 								<tr><td >Booked to	</td><td >	' . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . '</td>	</tr>
                // 								<tr>
                // 								<td >Status	</td><td >Consignment Booked.</td>
                // 									</td>
                // 													</tr>
                // 												</table>
                // 								<br>
                // 								<br>';

                //                 $to_name = array();
                //                 $to_email = array();

                //                 if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {

                //                     //sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)

                //                     array_push($to_email, get_client_email($conn, $consignee), get_client_email($conn, $consignor));
                //                     array_push($to_name, get_client_name($conn, $consignee), get_client_name($conn, $consignor));

                //                     $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $download_path, $image, $msg, $name);
                //                 }
                //                 //     } else {

                //                 //         //echo "Restricted";

                //                 //     }
                //                 // }
            }
        }
        // *End

        if ($pay_mode == '2' || $pay_mode == '3' || $pay_mode == '1') {  // Check Pay at Booking here

            if ($pay_mode == '3') {
                // $outstanding = SetOutStandingInfo($conn23,$consignor,$total); //Insert or Update Payment Details

                $get_client_details = get_client($conn, $consignor);
                $company_name = $get_client_details['client_company_name'];
                $email = $get_client_details['email'];
                $phone = $get_client_details['contact_no'];

                // Update Client OutStanding

                $outstanding = SetOutStandingInfo($conn, $consignor, $total);  // Insert or Update Payment Details

                $data = array(
                    'transaction_id' => array($transaction_id),
                    'company_name' => $company_name,
                    'grn_date' => array($grn_date),
                    'email' => $email,
                    'phone' => $phone,
                    'amount' => array($total),
                    'grn_no' => array($grn_no),
                    'invoice_no' => array($unique_invoice_no),
                    'client_id' => $consignor
                );
                // --------- New Code Start Here -------
                $data_serialize = serialize($data);
                // --------- New Code End Here ---------
                $link_wit_data = http_build_query(array('aParam' => $data_serialize));  // need to send
                $link_u = urlencode($link_wit_data);
                $out_put['result_url'] = 'https://elitewave360.in/verify_paylink1.php?data=' . $link_u;
                // $out_put['result_url'] = "https://elitewave360.in/verify_paylink1.php?data=' . urlencode($link_wit_data) . '";
                $out_put['result'] = 1;
                $out_put['data'] = $grn_no;

                //                 $merchant_key = "6078cc82-efbb-4816-bb6a-a7b51b48c05b";
                //                 $data = array(
                //                     "merchantTransactionId" => "$transaction_id", "merchantUserId" => "$transaction_id",
                //                     // "amount" => $total * 100,
                //                     "amount" => 1 * 100,
                //                     "merchantId" => "GRACIOUSONLINE",
                //                     "redirectUrl" => "https://elitewave360.in/redirect.php",
                //                     "redirectMode" => "POST",
                //                     "callbackUrl" => "https://elitewave360.in/redirect.php",
                //                     "paymentInstrument" => array(
                //                         "type" => "PAY_PAGE"
                //                     )
                //                 );

                //                 // $out_put['result'] = http_build_query(array('aParam' => $data));
                //                 $payloadMain = base64_encode(json_encode($data));
                //                 $payload = $payloadMain."/pg/v1/pay".$merchant_key;
                //                 $checksum = hash('sha256', $payload);
                //                 $Checksum = $checksum."###1";

                //                 $curl = curl_init();
                //                 curl_setopt_array($curl, array(
                //                 CURLOPT_URL => 'https://api.phonepe.com/apis/hermes/pg/v1/pay',
                //                 CURLOPT_RETURNTRANSFER => true,
                //                 CURLOPT_ENCODING => '',
                //                 CURLOPT_MAXREDIRS => 10,
                //                 CURLOPT_TIMEOUT => 0,
                //                 CURLOPT_FOLLOWLOCATION => true,
                //                 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                //                 CURLOPT_CUSTOMREQUEST => 'POST',
                //                 CURLOPT_POSTFIELDS =>'{
                //                     "request":"'.$payloadMain.'"
                //                 }',
                //                 CURLOPT_HTTPHEADER => array(
                //                     'X-verify: '.$Checksum,
                //                     'Content-Type: application/json'
                //                 ),
                //                 ));
                //                 $response = curl_exec($curl);
                //                 curl_close($curl);
                //                 $a =  json_decode($response);
                //                 $url = $a->data->instrumentResponse->redirectInfo->url;
                //                 $out_put['result'] = $url;
            } else if ($pay_mode == '2') {
                // Update Client OutStanding

                $outstanding = SetOutStandingInfo($conn, $consignor, $total);  // Insert or Update Payment Details

                // End

                $out_put['result'] = 1;
                $out_put['data'] = $grn_no;
                $out_put['result_url'] = '0';
            } else {
                $outstanding = SetOutStandingInfo($conn, $consignee, $total);
                $out_put['result'] = 1;
                $out_put['data'] = $grn_no;
                $out_put['result_url'] = '0';
            }
        } else {
            $out_put['result'] = 1;
            $out_put['data'] = $grn_no;
            $out_put['result_url'] = '0';
        }
    } else {
        $out_put['result'] = 0;
    }

    echo json_encode($out_put);
}

if ($form_name == 'edit_ftl_consignment_details') {
    $out_put = array();
    $edit_id = $_POST['edit_id'];
    $grn_id = $_POST['grn_id'];
    $grn_date = $_POST['grn_date'];
    $grn_no = $_POST['grn_no'];

    $tables = get_trans_table_name($conn, $grn_date);
    $get_m_y = explode('_', $tables[0]);
    $month = $get_m_y[1];
    $year = $get_m_y[2];

    $eway_number = $_POST['eway_number'];
    $goods_dedared_value = $_POST['goods_dedared_value'];
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

    // Volumetric Values
    $len = $_POST['length'] ? $_POST['length'] : null;
    $wid = $_POST['width'] ? $_POST['width'] : null;
    $hei = $_POST['height'] ? $_POST['height'] : null;
    $quanti = $_POST['quanti'] ? $_POST['quanti'] : null;
    $vlm_wei = $_POST['vlm_weight'] ? $_POST['vlm_weight'] : null;

    // Shipping Address
    $ship_address = $_POST['shipping_address'] ? $_POST['shipping_address'] : null;

    // Eway Expiry
    $eway_expiryDate = $_POST['eway_expiryDate'] ? $_POST['eway_expiryDate'] : null;
    // $status = 2;

    $query = "update $tables[0] set eway_number = '" . $eway_number . "',shipping_address = '" . $ship_address . "',eway_expirydate = '" . $eway_expiryDate . "',goods_dedared_value = '" . $goods_dedared_value . "',dimension1='$len',dimension2='$wid',dimension3='$hei',dimension4='$quanti',consignment_weight='$vlm_wei',frieght_rate='" . $frieght_rate . "',frieght_amount='" . $frieght_amount . "',loading_unloading_rate='" . $loading_unloading_rate . "',loading_unloading_amount = '" . $loading_unloading_amount . "',crane_fork_lift_rate='" . $crane_fork_lift_rate . "',crane_fork_lift_amount='" . $crane_fork_lift_amount . "',cod_rate='" . $cod_rate . "',cod_amount='" . $cod_amount . "',fov_rate='" . $fov_rate . "',fov_amount='" . $fov_amount . "',doc_charges='" . $doc_rate . "',doc_amount='" . $doc_amount . "',cartage_rate='" . $cartage_rate . "',cartage_amount='" . $cartage_amount . "',labour_handling_rate='" . $labour_rate . "',labour_handling_amount='" . $labour_amount . "',octroi_rate='" . $octroi_rate . "',octroi_amount='" . $octroi_amount . "',other_charge_rate='" . $other_rate . "',other_charge_amount='" . $other_amount . "',gst_rate='" . $gst_rate . "',gst_amount='" . $gst_amount . "',total='" . $total . "',paid_amount='0',balance='" . $total . "',paid_status='0',total_words='" . $amount_in_words . "',note1='" . $note1 . "',note2='" . $note2 . "',truck='" . $vehicle_no . "',consigner_signature='" . $signature . "',updated_at = '" . $updated_at . "', updated_by = '" . $updated_by . "' where transaction_id='$edit_id'";

    $result = mysqli_query($conn, $query) or die(mysqli_error($conn));

    for ($pk = 0; $pk < count($_POST['party_invoice']); $pk++) {
        $update_q = mysqli_query($conn, "UPDATE $tables[2] set `party_invoice_no`='" . $_POST['party_invoice'][$pk] . "',`charged_weight`='" . $_POST['charged'][$pk] . "',`updated_by`='" . $updated_by . "',`updated_at`='$updated_at ' WHERE transaction_id = '" . $edit_id . "' ");
    }

    $check_inv_no_avlble = "select * from $tables[0] where transaction_id= '$edit_id'";
    $inv_res = mysqli_query($conn, $check_inv_no_avlble);
    $fetch_det = mysqli_fetch_assoc($inv_res);
    // print_r($fetch_det);

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
        // *Start Invoice
        // $transport_type = '7';
        $type = 'GTA';

        // $conn1 = mysqli_connect("localhost","root","","bookconsignment");

        $grn_date_expl = explode('-', $grn_date);
        $cur_year = $grn_date_expl[2];

        $current_year = $cur_year;
        $previous_year = $cur_year - 1;

        $p_y = substr($previous_year, 2);
        $c_y = substr($current_year, 2);

        $year_insert = $p_y . '-' . $c_y;

        $invoice_table = invoice_table_function($conn, $grn_date);

        $select = mysqli_query($conn, 'select * from ' . $invoice_table);
        $get_count = mysqli_num_rows($select);
        if ($get_count == 0) {
            $insert_data = 'INSERT INTO ' . $invoice_table . "(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('0','HRGST','$year_insert','GST','$created_at','$created_by'),('0','HRGTA','$year_insert','GTA','$created_at','$created_by')";
            // $insert_data .= "INSERT INTO ".$invoice_table."(`invoice_no`, `gst_text`, `gst_year`, `inv_type`,`created_at`,`created_by`) VALUES ('1','HRGTA','$year_insert','GTA','$created_at','$created_by')";
            // $res = mysqli_multi_query($conn,$insert_data);
            $res = mysqli_query($conn, $insert_data);
            if ($res) {
                $inv_query = 'select * from trans_invoice_tbl' . $year . " where inv_type='$type'";
                $inv_query_result = mysqli_query($conn, $inv_query);
                $inv_query_row = mysqli_fetch_assoc($inv_query_result);

                $inv_seq = $inv_query_row['invoice_no'] + 1;
                // print_r($inv_seq);
                // $inv_seq = '100';
                $inv_text = $inv_query_row['gst_text'];
                $inv_year = $inv_query_row['gst_year'];
                $sequence = sprintf('%05d', $inv_seq);
                $unique_invoice_no = $inv_text . '/' . $sequence . '/' . $inv_year;
                // print_r($unique_invoice_no);
            }
        } else {
            $inv_query = 'select * from trans_invoice_tbl' . $year . " where inv_type='$type'";
            $inv_query_result = mysqli_query($conn, $inv_query);
            $inv_query_row = mysqli_fetch_assoc($inv_query_result);

            $inv_seq = $inv_query_row['invoice_no'] + 1;
            // print_r($inv_seq);
            // $inv_seq = '100';
            $inv_text = $inv_query_row['gst_text'];
            $inv_year = $inv_query_row['gst_year'];
            $sequence = sprintf('%05d', $inv_seq);
            $unique_invoice_no = $inv_text . '/' . $sequence . '/' . $inv_year;
        }

        // Sequence Generation

        $directory = 'digital_invoice/';
        $invoice_url = 'https://elitewave360.in/web/gst_invoice_page.php?month=' . $month . '&year=' . $year . '&id=' . $edit_id . '&invoice_no=' . $unique_invoice_no . '';
        $invoice_file_name = $month . '_' . $year . '_' . $edit_id . 'invoice';
        $download_path = $directory . $invoice_file_name . '.pdf';
        $inv_path = '';
        $file_inv_download = curl_init($invoice_url);
        curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($file_inv_download, CURLOPT_REFERER, true);
        curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
        $store_inv = curl_exec($file_inv_download);
        curl_close($file_inv_download);
        $save_inv_file = file_put_contents($download_path, $store_inv);
        if ($save_inv_file) {
            $update = mysqli_query($conn, 'update trans_invoice_tbl' . $year . " SET invoice_no = '$inv_seq', updated_by = '$updated_by', updated_at = '$updated_at' where inv_type = '$type'");

            $query_inv = "update $tables[0] set `invoice_no` = '$unique_invoice_no' where transaction_id ='$edit_id'";
            $res = mysqli_query($conn, $query_inv);
        }
    } else {
        $directory = 'digital_invoice/';
        $invoice_url = 'https://elitewave360.in/web/gst_invoice_page.php?month=' . $month . '&year=' . $year . '&id=' . $edit_id . '&invoice_no=' . $unique_invoice_no . '';
        $invoice_file_name = $month . '_' . $year . '_' . $edit_id . 'invoice';
        $download_path = $directory . $invoice_file_name . '.pdf';
        $file_inv_download = curl_init($invoice_url);
        curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($file_inv_download, CURLOPT_REFERER, true);
        curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
        $store_inv = curl_exec($file_inv_download);
        curl_close($file_inv_download);
        $save_inv_file = file_put_contents($download_path, $store_inv);
        $inv_path = '';
    }
    // *End Invoice

    // *Send Invoice Instanly
    if ($mode_of_consignment == '3' || $mode_of_consignment == '4') {
        if ($mode_of_consignment == '3') {  // Pay at Booking

            $check_partywise_frq = checkPartyWiseFrequency($conn, $consignor);  // Check Frequency set or not
            if ($check_partywise_frq == 0) {  // Frequncy is Set
                // Invoice Sent as per frequency
                // echo "Frequency is Set";
            } else {
                // Other Process Goes here
                // $check_restricted = check_invoice_restricted($conn, $consignor);
                // if ($check_restricted == 0) {
                // 	$msg = '<p style="line-height: 24px; margin-bottom:15px;">
                // 					Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grn_date . '! <br>
                // 					Following Your Successful Consignment Delivery, Please Find Your Invoice Attached (in PDF Format) to this email.
                // 					<table width="70%" cellpadding="5" cellspacing="0" border="1" align="center" style="color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;">
                // 					<tr>
                // 					<td >GCN No	</td><td >' . $grn_no . '</td>
                // 					</tr><tr>
                // 					<td >GCN Date:	</td><td >	' . $grn_date . '	</td>
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
        } else {  // Cash on Delivery

            // $check_partywise_frq = checkPartyWiseFrequency($conn, $consignee); // Check Frequency set or not
            // if ($check_partywise_frq == 0) { // Frequncy is Set
            // 	//Invoice Sent as per frequency
            // 	echo "Frequency is Set";
            // } else {
            // 	$outstanding = SetOutStandingInfo($conn, $consignee, $total); //Set Outstanding For COD

            // $check_restricted = check_invoice_restricted($conn, $consignee);
            // if ($check_restricted == 0) {
            // , Please Find Your Invoice Attached (in PDF Format) to this email
            $msg = "<p style=\"line-height: 24px; margin-bottom:15px;\">
\t\t\t\t\t\t\t\t\tThank You for Your Order On <a href = \"https://elitewave360.in\" >Elite Wave 360</a> on " . $grn_date . "! <br>
\t\t\t\t\t\t\t\t\tFollowing Your Successful Consignment Delivery. \t\t\t\t
\t\t\t\t\t\t\t\t\t<table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
\t\t\t\t\t\t\t\t\t<tr>
\t\t\t\t\t\t\t\t\t<td >GCN No\t</td><td >" . $grn_no . "</td>
\t\t\t\t\t\t\t\t\t</tr><tr>\t
\t\t\t\t\t\t\t\t\t<td >GCN Date:\t</td><td >\t" . $grn_date . "\t</td>\t
\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t<tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor) . ' , ' . get_city_name($conn, $origin) . "</td>\t</tr>\t
\t\t\t\t\t\t\t\t\t<tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . "</td>\t</tr>\t
\t\t\t\t\t\t\t\t\t<tr>\t\t
\t\t\t\t\t\t\t\t\t<td >Status\t</td><td >Consignment Booked</td>\t\t
\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t\t\t\t</table>\t
\t\t\t\t\t\t\t\t\t<br>
\t\t\t\t\t\t\t\t\t<br>";

            $to_name = array();
            $to_email = array();

            if (!empty(get_client_email($conn, $consignor)) && !empty(get_client_email($conn, $consignee))) {
                // sendAttachments($to_name, $to_mail, $subject,$file, $mail_content,$name)

                array_push($to_email, get_client_email($conn, $consignee), get_client_email($conn, $consignor));
                array_push($to_name, get_client_name($conn, $consignee), get_client_name($conn, $consignor));

                $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $inv_path, $image, $msg, $name);

                // $mail = sendAttachments($to_name,$to_email, 'Consignment Invoice Notification',$attachments,$image ,$msg,$name);

                // echo sendAttachments("Roselin","mailmeroselin3012@gmail.com",'test','transaction_pdf/4_2020_37transaction.pdf',array('images/5b6caecab1374lol.png'),'test','tst');
            }
            // } else {

            //     //echo "Restricted Client";

            // }
            // }
        }
    } else {
        // Payment Mode 1 and 2

        if ($mode_of_consignment == 1) {  // To Pay

            // echo "Consignee";
            $outstanding = SetOutStandingInfo($conn, $consignee, $total);
        } else {  // By Sender

            // echo "Consignor";
            $outstanding = SetOutStandingInfo($conn, $consignor, $total);
        }
    }
    // *End

    if ($result) {
        $out_put['result'] = 1;
        $out_put['data'] = $grn_no;
    } else {
        $out_put['result'] = '0';
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

        if ($count_q > 0) {
            $grn_no = substr($array_new[0], 0, 9);
            // print_r($get_exact_files);

            $s = 1;

            foreach ($array_new as $barcode_images) {
                // var_dump($barcode_images);
                $file = $barcode_images;
                $ext = pathinfo($file, PATHINFO_FILENAME);

                echo "<div class='g-card1'>
            <div class='card-img'>
                <div class='card-title text-center'>
                    <img src='https://elitewave360.in/web/images/elitewave-light.png' class='g-img' >
                </div>
            </div>
        <div class='g-card'>
            <div class='col-md-8  subdiv'>

                <div class='detail'>
                    <p><b> GCN No :</b> $grn_no</p>
                    <p><b> Company Address :</b> #10/35, M.V.Badran Street, Anaikar Complex 2nd Floor, Naval Hospital Road, Periamet, Chennai - 600 003</p>
                    <p><b> Phone No :</b> +91 98408 59711</p>
                    <p><b> GST :</b> </p>
                    <p><b> Email:</b> info@elitewave360.in</p>
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
        } else {
            echo '0';
        }
    }

    getBarcodeImages($serarch_grn);
}

if ($form_name == 'pod_form') {
    extract($_FILES);
    $allowed_ex = array('jpg', 'png', 'jpeg');

    for ($pd = 0; $pd < count($_FILES['pod_file']['name']); $pd++) {
        $pod_file_name[] = $_FILES['pod_file']['name'][$pd];
    }
    foreach ($pod_file_name as $key => $pod_file) {
        $pod_file = $_FILES['pod_file']['tmp_name'][$key];
        $pod_file_name1 = $_FILES['pod_file']['name'][$key];
        $ext[] = pathinfo($pod_file_name1, PATHINFO_EXTENSION);
        // print_r($ext[$key]);
        if (!in_array($ext[$key], $allowed_ex)) {
            $message['msg'] = 0;
        } else {
            $file_name_pod[] = $_FILES['pod_file']['name'][$key];
            if (move_uploaded_file($_FILES['pod_file']['tmp_name'][$key], '../pod_uploads/' . $pod_file_name1)) {
                $message['msg'] = 1;
            } else {
                $message['msg'] = 0;
            }
        }
    }

    $implode_pod_file = implode('@@', $file_name_pod);

    if ($implode_pod_file) {
        // $conn1 = mysqli_connect("localhost","root","","bookconsignment");
        // $implode_file = "insert into $tables[1](transaction_id,attachment,created_at,created_by,status) values('$edit_id','$file_name','$created_at','$created_by','0')";
        // $implode_file = "insert into pod_check(`screens`, `created`) values('$implode_pod_file','28-12-2021')";
        $implode_file = "INSERT INTO `pod_files`(`screens`, `created_at`, `created_by`,`status`)values('$implode_pod_file','$created_at','$created_by','1')";
        $pod_result = mysqli_query($conn, $implode_file) or die(mysqli_error($conn));
        $message['msg'] = 1;
    } else {
        $message['msg'] = 0;
    }

    echo $message['msg'];
}

if ($form_name == 'driver_pod_upload') {
    $grn_no = mysqli_real_escape_string($conn, $_POST['grn_no']);
    $allowed_ex = array('jpg', 'png', 'jpeg');

    if (empty($_FILES['pod_file']['name'])) {
        echo 0;
        // exit;
    }

    // Server-side duplicate guard — don't trust the disabled button alone
    $already = false;
    $chk = mysqli_query($conn, "SELECT screens FROM pod_files WHERE screens LIKE '%" . strtoupper($grn_no) . "%'");
    while ($r = mysqli_fetch_assoc($chk)) {
        foreach (explode('@@', $r['screens']) as $f) {
            if (strpos(strtoupper($f), strtoupper($grn_no)) === 0) {
                $already = true;
                break 2;
            }
        }
    }
    if ($already) {
        echo 0;
        // exit;
    }

    $ext = strtolower(pathinfo($_FILES['pod_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ex)) {
        echo 0;
        // exit;
    }

    $file_name = strtoupper($grn_no) . '_' . time() . '.' . $ext;

    if (move_uploaded_file($_FILES['pod_file']['tmp_name'], '../pod_uploads/' . $file_name)) {
        $insert = "INSERT INTO `pod_files`(`screens`, `created_at`, `created_by`, `status`) VALUES ('$file_name','$created_at','$created_by','1')";
        $res = mysqli_query($conn, $insert);
        echo $res ? 1 : 0;
    } else {
        echo 0;
    }
}

if ($form_name == 'delete_pod_img') {
    $delete_pod_img = $_GET['delete_id'];
    $tbl_id = $_POST['tbl_id'];
    // $conn = mysqli_connect("localhost","root","","bookconsignment");
    $q = "select * from pod_files where md5(id) = '$tbl_id'";
    $sql = mysqli_query($conn, $q);

    $user_delete = $delete_pod_img;
    // print_r($user_delete);
    // exit();
    $newString = '';

    while ($row = mysqli_fetch_assoc($sql)) {
        $test_array = $row['screens'];

        $exploded = explode('@@', $test_array);
        // print_r($exploded);

        $counter = count($exploded);

        // print_r($counter);

        for ($x = 0; $x < $counter; $x++) {
            if ($user_delete != $exploded[$x]) {
                // print_r($exploded[$x]);
                $newString = $newString . '@@' . $exploded[$x];
                // print_r($newString);
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
    for ($up = 0; $up < count($_FILES['pod_file']['name']); $up++) {
        $update_pod_file_name[] = $_FILES['pod_file']['name'][$up];
    }

    $allowed_ex = array('jpg', 'png', 'jpeg');

    foreach ($update_pod_file_name as $update_key => $pod_file) {
        $update_pod_file = $_FILES['pod_file']['tmp_name'][$update_key];
        $update_pod_file_name1 = $_FILES['pod_file']['name'][$update_key];
        $update_ext[] = pathinfo($update_pod_file_name1, PATHINFO_EXTENSION);
        // print_r($ext[$key]);
        if (!in_array($update_ext[$update_key], $allowed_ex)) {
            $message['msg'] = 0;
        } else {
            $update_file_name_pod[] = $_FILES['pod_file']['name'][$update_key];
            if (move_uploaded_file($_FILES['pod_file']['tmp_name'][$update_key], '../pod_uploads/' . $update_pod_file_name1)) {
                $message['msg'] = 1;
            } else {
                $message['msg'] = 0;
            }
        }
    }

    $implode_pod = implode('@@', $update_file_name_pod);
    // echo $implode_pod;

    // $conn = mysqli_connect("localhost","root","","bookconsignment");
    $sql = "select * from pod_files where md5(id) ='$edit_id'";
    $res_sql = mysqli_query($conn, $sql);

    $row = mysqli_fetch_assoc($res_sql);
    $old_data[] = $row['screens'];
    // print_r($old_data);
    foreach ($old_data as $data) {
        $oldd_data = $data;
        // print_r($oldd_data);
        $add_data = $oldd_data . '@@' . $implode_pod . '';
    }
    // echo "<pre>".$add_data."</pre>";
    $explode_new_data = explode('@@', $add_data);
    // print_r($explode_new_data);
    // echo "<br>";
    $remove_duplicates = array_unique($explode_new_data);
    //    print_r($remove_duplicates);
    //    echo "<br>";
    $update_new_images = implode('@@', $remove_duplicates);
    // echo $update_new_images;

    $update_pod_images = "update pod_files set screens = '$update_new_images' where md5(id) ='$edit_id'";
    $sql_query = mysqli_query($conn, $update_pod_images);
    if ($sql_query) {
        $message['msg'] = 1;
    } else {
        $message['msg'] = 0;
    }
    echo $message['msg'];
}

if ($form_name == 'change_user_pass') {
    $user_id = $_POST['user_id'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];
    if ($user_id != '') {
        if ($new_pass == $confirm_pass) {
            $new_pass = enc_name($new_pass);
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
    $trans_table1 = 'transaction_' . $m . '_' . $y;
    $trans_table2 = 'transaction_images_' . $m . '_' . $y;
    $trans_table3 = 'transaction_invoice_' . $m . '_' . $y;

    // select transaction table

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
            // GRN Regenerate Replace old to new grn
            $url = 'https://elitewave360.in/web/transaction_pdf.php?month=' . $m . '&year=' . $y . '&id=' . $transaction_id . '&copy=consignor';
            $path = 'transaction_pdf/' . $m . '_' . $y . '_' . $transaction_id . 'transaction.pdf';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec($ch);
            curl_close($ch);
            $result_url = file_put_contents($path, $data);

            // End

            // Invoice Regenerate Replace old to new invoice

            if ($check_inv_no != 'NULL') {
                $directory = 'digital_invoice/';
                $invoice_url = 'https://elitewave360.in/web/gst_invoice_page.php?month=' . $m . '&year=' . $y . '&id=' . $transaction_id . '&invoice_no=' . $unique_invoice_no . '';
                $invoice_file_name = $m . '_' . $y . '_' . $transaction_id . 'invoice';
                $download_path = $directory . $invoice_file_name . '.pdf';
                $file_inv_download = curl_init($invoice_url);
                curl_setopt($file_inv_download, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($file_inv_download, CURLOPT_REFERER, true);
                curl_setopt($file_inv_download, CURLOPT_SSL_VERIFYPEER, false);
                $store_inv = curl_exec($file_inv_download);
                curl_close($file_inv_download);
                $save_inv_file = file_put_contents($download_path, $store_inv);
            }

            $multiple_attach = array($path, $download_path);
            // print_r($multiple_attach);

            // End

            // Send Notification Via SMS

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

            // 	$msg = "Dear ".$client_name.", \r\n Your Booking is Cancelled - Against Your GCN No: ".$grn_no. "\r\n Reason For Cancellation : ".$remarks;

            // 	$message = $twilio->messages
            // 		->create(
            // 			$phone, // to
            // 			["body" => $msg, "from" => "+17853776942"]
            // 		);

            // 	}

            // End

            // Send Notification Via Email

            // *Send Invoice Instanly
            if ($pay_mode == '3' || $pay_mode == '4') {
                if ($pay_mode == '3') {
                    $check_restricted = check_invoice_restricted($conn, $consigner);
                    if ($check_restricted == 0) {
                        $msg = "<p style=\"line-height: 24px; margin-bottom:15px;\">
\t\t\t\t\t\t\tWe are Sorry, but Your Consignment Booking On <a href = \"https://elitewave360.in\" >Elite Wave 360</a> on " . $grn_date . " 
\t\t\t\t\t\t\thas been Cancelled! <br>
\t\t\t\t\t\t\tPlease Find Your Attachments (in PDF Format) to this email.
\t\t\t\t\t\t\t<table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
\t\t\t\t\t\t\t<tr>
\t\t\t\t\t\t\t<td >GCN No\t</td><td >" . $grn_no . "</td>
\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t<tr>\t
\t\t\t\t\t\t\t<td >GCN Date:\t</td><td >\t" . $grn_date . "\t</td>\t
\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t<tr><td >Booked By\t</td><td >" . get_client_name($conn, $consigner) . ' , ' . get_city_name($conn, $origin) . "</td>\t</tr>\t
\t\t\t\t\t\t\t<tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . "</td>\t</tr>\t
\t\t\t\t\t\t\t<tr>\t\t
\t\t\t\t\t\t\t<td >Status\t</td><td style=\"color:red\";  >Consignment Cancelled</td>\t\t
\t\t
\t\t\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t\t\t<tr>\t\t
\t\t\t\t\t\t\t<td >Cancellation Reason\t</td><td >" . $remarks . "</td>\t\t
\t\t\t\t\t\t\t
\t\t\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t\t</table>\t
\t\t\t\t\t\t\t<br>
\t\t\t\t\t\t\t<br>";

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
                        $msg = "<p style=\"line-height: 24px; margin-bottom:15px;\">
\t\t\t\t\t\t\tWe are Sorry, but Your Consignment Booking On <a href = \"https://elitewave360.in\" >Elite Wave 360</a> on " . $grn_date . " 
\t\t\t\t\t\t\thas been Cancelled!
\t\t\t\t\t\t\t<br> Please Find Your Attachments (in PDF Format) to this email. \t\t\t\t
\t\t\t\t\t\t\t<table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
\t\t\t\t\t\t\t<tr>
\t\t\t\t\t\t\t<td >GCN No\t</td><td >" . $grn_no . "</td>
\t\t\t\t\t\t\t</tr><tr>\t
\t\t\t\t\t\t\t<td >GCN Date:\t</td><td >\t" . $grn_date . "\t</td>\t
\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t<tr><td >Booked By\t</td><td >" . get_client_name($conn, $consigner) . ' , ' . get_city_name($conn, $origin) . "</td>\t</tr>\t
\t\t\t\t\t\t\t<tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . "</td>\t</tr>\t
\t\t\t\t\t\t\t<tr>\t\t
\t\t\t\t\t\t\t<td >Status\t</td><td style=\"color:red\"; >Consignment Cancelled</td>\t\t
\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t\t\t<tr>\t\t
\t\t\t\t\t\t\t<td >Cancellation Reason\t</td><td>" . $remarks . "</td>\t\t
\t\t\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t\t</table>\t
\t\t\t\t\t\t\t<br>
\t\t\t\t\t\t\t<br>";

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
                // Send GRN Only

                $msg = "<p style=\"line-height: 24px; margin-bottom:15px;\">
\t\t\t\t\t\t\tWe are Sorry, but Your Consignment Booking On <a href = \"https://elitewave360.in\" >Elite Wave 360</a> on " . $grn_date . " 
\t\t\t\t\t\t\thas been Cancelled!
\t\t\t\t\t\t\t<br> Please Find Your Attachments (in PDF Format) to this email. \t\t\t\t
\t\t\t\t\t\t\t<table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
\t\t\t\t\t\t\t<tr>
\t\t\t\t\t\t\t<td >GCN No\t</td><td >" . $grn_no . "</td>
\t\t\t\t\t\t\t</tr><tr>\t
\t\t\t\t\t\t\t<td >GCN Date:\t</td><td >\t" . $grn_date . "\t</td>\t
\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t<tr><td >Booked By\t</td><td >" . get_client_name($conn, $consigner) . ' , ' . get_city_name($conn, $origin) . "</td>\t</tr>\t
\t\t\t\t\t\t\t<tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee) . ' , ' . get_city_name($conn, $destination) . "</td>\t</tr>\t
\t\t\t\t\t\t\t<tr>\t\t
\t\t\t\t\t\t\t<td >Status\t</td><td style=\"color:red\"; >Consignment Cancelled</td>\t\t
\t
\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t\t\t<tr>\t\t
\t\t\t\t\t\t\t<td >Cancellation Reason\t</td><td  >" . $remarks . "</td>\t\t
\t\t\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t\t</table>\t
\t\t\t\t\t\t\t<br>
\t\t\t\t\t\t\t<br>";
                $to_name = array();
                $to_email = array();

                if (!empty(get_client_email($conn, $consigner)) && !empty(get_client_email($conn, $consignee))) {
                    array_push($to_email, get_client_email($conn, $consigner), get_client_email($conn, $consignee));
                    array_push($to_name, get_client_name($conn, $consigner), get_client_name($conn, $consignee));

                    $mail = sendAttachments($to_name, $to_email, 'Consignment Cancellation Notification', $path, $image, $msg, $name);
                }
                // End GRN
            }

            // *End Send Instant Invoice

            // End
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
    $freq_date = date('d-m-Y', strtotime(date('d-m-Y') . "+$frequency days"));
    $query = "UPDATE `client` SET `invoice_frequency`='$frequency' , `frequency_date` = '$freq_date', `updated_by` = '$f_updated_by', `updated_at` = '$updated_at' WHERE client_id = '$client_id'";
    $sql = mysqli_query($conn, $query);

    if ($sql) {
        echo '1';
    } else {
        echo '0';
    }
}

if ($form_name == 'status_change_consignment') {
    $c_date = date('d-m-Y H:i:s A');
    $grn_id = $_POST['grn_id'];
    $grn_no = $_POST['grn_no'];
    $status = $_POST['status'];
    $table_names = $_POST['table_names'];

    $delivery_type = isset($_POST['delivery_type'])
    ? $_POST['delivery_type']
    : '';

$delivered_packages = isset($_POST['delivered_packages'])
    ? (int)$_POST['delivered_packages']
    : 0;

$total_packages = isset($_POST['total_packages'])
    ? (int)$_POST['total_packages']
    : 0;

    $origin = 0;
    $destination = 0;
    $mode = 0;
    $remarks = $_POST['remarks'];

    $status_date = $_POST['status_date'];
$status_time = $_POST['status_time'];

    $querys = "SELECT client_id FROM $table_names WHERE grn_no='$grn_no' AND `status` <= '$status' AND booking_status = '' ";
    $result = mysqli_query($conn, $querys);
    $transact_client = mysqli_fetch_array($result);
    $client_id = $transact_client['client_id'];
    $created_at = $status_date . ' ' . $status_time . ':00';

    if ((int)$status === 8) {

    if (
        $delivery_type !== 'partial' &&
        $delivery_type !== 'full'
    ) {
        echo 0;
        exit;
    }

    // Get actual total packages from invoice table
    $invoice_table = str_replace(
        'transaction_',
        'transaction_invoice_',
        $table_names
    );

    $pkg_query = mysqli_query(
        $conn,
        "SELECT SUM(no_of_pkge) AS total_packages
         FROM `$invoice_table`
         WHERE transaction_id='" . mysqli_real_escape_string(
             $conn,
             $_POST['transaction_id']
         ) . "'"
    );

    $pkg_result = mysqli_fetch_assoc($pkg_query);

    $actual_total_packages =
        (int)$pkg_result['total_packages'];

    if ($actual_total_packages <= 0) {
        echo 0;
        exit;
    }

    $total_packages = $actual_total_packages;

    // Check if this consignment is already fully delivered
$delivery_check_query = mysqli_query(
    $conn,
    "SELECT delivery_type
     FROM transaction_status_log
     WHERE grn_no='" . mysqli_real_escape_string($conn, $grn_no) . "'
       AND to_status='8'
     ORDER BY sheet_id DESC
     LIMIT 1"
);

$delivery_check = mysqli_fetch_assoc($delivery_check_query);

if (
    !empty($delivery_check) &&
    $delivery_check['delivery_type'] === 'full'
) {
    echo 0;
    exit;
}

    // Only one package cannot be partially delivered
if ($total_packages <= 1 && $delivery_type === 'partial') {
    echo 0;
    exit;
}

    if ($delivery_type === 'partial') {

        if (
            $delivered_packages <= 0 ||
            $delivered_packages >= $total_packages
        ) {
            echo 0;
            exit;
        }

    } else {

        // Full delivery means all packages delivered
        $delivered_packages = $total_packages;
    }
}

    if (empty($client_id)) {
        echo 0;
        // exit;
    }

    $sheetq = 'SELECT max(sheet_id) AS id FROM transaction_status';
    $sheetres = mysqli_query($conn, $sheetq);
    $sheetr = mysqli_fetch_array($sheetres);
    $sheet_id = $sheetr['id'] + 1;
    $sheet_no = 'SN/' . sprintf('%04d', $sheet_id);

    $insq1 = "INSERT INTO `transaction_status`(`sheet_id`,`sheet_no`, `origin`, `destination`, `mode`,`remarks`, `status`, `created_at`, `created_by`) VALUES ('$sheet_id','$sheet_no','$origin','$destination','$mode','$remarks','$status','$created_at','$created_by')";
    $insr1 = mysqli_query($conn, $insq1);

    $updated = false;  // FIX 3: single flag, replaces broken $matched block

    $query2 = 'SELECT * FROM transaction_tbls';
    $result2 = mysqli_query($conn, $query2);
    while ($row2 = mysqli_fetch_assoc($result2)) {
        // FIX 1: use grn_no (unique) instead of grn_id (non-unique across GRN modes)
        $query = 'SELECT * FROM transaction_' . $row2['table_name'] . " WHERE grn_no = '$grn_no' AND client_id = '$client_id'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
                $consigners = $row['consigner'];
                $grn_number = $row['grn_no'];
                $client_det = get_client($conn, $consigners);
                $contact_no = $client_det['contact_no'];
                $client_name = $client_det['client_company_name'];

                // $insq = "INSERT INTO `transaction_status_log`(`sheet_id`,`grn_id`, `grn_no`, `from_status`, `to_status`,`client_id`,`updated_at`, `updated_by`) VALUES ('$sheet_id','" . $row['grn_id'] . "','" . $row['grn_no'] . "','" . $row['status'] . "','$status','" . $row['client_id'] . "','$created_at','$created_by')";
                $insq = "INSERT INTO `transaction_status_log`
(
    `sheet_id`,
    `grn_id`,
    `grn_no`,
    `from_status`,
    `to_status`,
    `delivery_type`,
    `delivered_packages`,
    `total_packages`,
    `client_id`,
    `updated_at`,
    `updated_by`
)
VALUES
(
    '$sheet_id',
    '" . $row['grn_id'] . "',
    '" . $row['grn_no'] . "',
    '" . $row['status'] . "',
    '$status',
    '" . (($status == 8) ? $delivery_type : '') . "',
    '" . (($status == 8) ? $delivered_packages : 0) . "',
    '" . (($status == 8) ? $total_packages : 0) . "',
    '" . $row['client_id'] . "',
    '$created_at',
    '$created_by'
)";
                $insr = mysqli_query($conn, $insq);

                // FIX 1 continued: UPDATE also keyed on grn_no, not grn_id
                $query1 = 'UPDATE transaction_' . $row2['table_name'] . " SET status='$status' WHERE grn_no='" . $row['grn_no'] . "' AND client_id='" . $row['client_id'] . "'";
                $result1 = mysqli_query($conn, $query1);

                if ($result1) {
                    $updated = true;  // FIX 2: set flag, do NOT echo here

                    // *Send invoice
                    $get_status_query = 'SELECT `total`,`balance`,`invoice_no`,`origin`,`destination`,`mode_of_consignment`,`transaction_id`,`grn_no`,`grn_date`,`consigner`,`consignee`,`status` FROM transaction_' . $row2['table_name'] . " WHERE grn_no='" . $row['grn_no'] . "' AND client_id='" . $row['client_id'] . "'";
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

                    if ($get_status == '8' && $delivery_type == 'full') {
                        if ($mode_of_consignment == '1' || $mode_of_consignment == '2' || $mode_of_consignment == '3' || $mode_of_consignment == '4') {
                            if ($mode_of_consignment == '1' || $mode_of_consignment == '4') {
                                $check_partywise_frq = checkPartyWiseFrequency($conn, $consignee_name);
                                if ($check_partywise_frq == 0) {
                                    if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                        $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                        Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                        <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                        <tr>
                                        <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                        </tr><tr>\t
                                        <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                        </tr>
                                        <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                        <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                        <tr>\t\t
                                        <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                            </td>
                                        </tr>
                                        </table>\t
                                        <br>";

                                        $to_name1 = array();
                                        $to_email1 = array();
                                        array_push($to_email1, get_client_email($conn, $consignee_name), get_client_email($conn, $consignor_name));
                                        array_push($to_name1, get_client_name($conn, $consignee_name), get_client_name($conn, $consignor_name));
                                        sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);

                                        if ($mode_of_consignment == '4') {
                                            $to_name = array();
                                            $to_email = array();
                                            array_push($to_email, get_client_email($conn, $consignee_name));
                                            array_push($to_name, get_client_name($conn, $consignee_name));
                                            $get_client_details = get_client($conn, $consignee_name);
                                            $company_name = $get_client_details['client_company_name'];
                                            $email = $get_client_details['email'];
                                            $phone = $get_client_details['contact_no'];
                                            $amount_array = array($balance);
                                            $get_transaction_id_arr = array($get_transaction_id);
                                            $grnn_date_array = array($grnn_date);
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
                                            $dir = 'digital_invoice/';
                                            $pdf_file_name = '';
                                            $msg = '<p style="line-height: 24px; margin-bottom:15px;">
                                            Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>
                                            Following Your Successful Consignment Delivery. \t\t\t\t
                                            <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                            <tr>
                                            <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                            </tr><tr>\t
                                            <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                            </tr>
                                            <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                            <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                            <tr>\t\t
                                            <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                                </td>
                                            </tr>
                                            </table>";
                                            $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification', $pdf_file_name, $image, $msg, $name);
                                        }
                                    }
                                } else {
                                    $check_restricted = check_invoice_restricted($conn, $consignee_name);
                                    if ($check_restricted == 0) {
                                        $get_client_details = get_client($conn, $consignee_name);
                                        $company_name = $get_client_details['client_company_name'];
                                        $email = $get_client_details['email'];
                                        $phone = $get_client_details['contact_no'];
                                        $amount_array = array($balance);
                                        $get_transaction_id_arr = array($get_transaction_id);
                                        $grnn_date_array = array($grnn_date);
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
                                        $dir = 'digital_invoice/';
                                        $pdf_file_name = '';
                                        if ($mode_of_consignment != '4') {
                                            $pay_link = '<br><br>Payment Link : <a href = "https://elitewave360.in/verify_paylink1.php?data=' . urlencode($link_wit_data) . '" >Payment Link</a>';
                                            $mail_subject = 'Consignment Invoice Notification With Payment Link';
                                        } else {
                                            $pay_link = '';
                                            $mail_subject = 'Consignment Invoice Notification';
                                        }
                                        $msg = "<p style=\"line-height: 24px; margin-bottom:15px;\">
\t\t\t\t\t\t\t\t\t\tThank You for Your Order On <a href = \"https://elitewave360.in\" >Elite Wave 360</a> on " . $grnn_date . "! <br>
\t\t\t\t\t\t\t\t\t\tFollowing Your Successful Consignment Delivery. \t\t\t\t
\t\t\t\t\t\t\t\t\t\t<table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
\t\t\t\t\t\t\t\t\t\t<tr>
\t\t\t\t\t\t\t\t\t\t<td >GCN No\t</td><td >" . $grnn_no . "</td>
\t\t\t\t\t\t\t\t\t\t</tr><tr>\t
\t\t\t\t\t\t\t\t\t\t<td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t<tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
\t\t\t\t\t\t\t\t\t\t<tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
\t\t\t\t\t\t\t\t\t\t<tr>\t\t
\t\t\t\t\t\t\t\t\t\t<td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
\t\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t</table><br>" . $pay_link;
                                        $to_name = array();
                                        $to_email = array();
                                        if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                            array_push($to_email, get_client_email($conn, $consignee_name));
                                            array_push($to_name, get_client_name($conn, $consignee_name));
                                            $mail = sendAttachments($to_name, $to_email, $mail_subject, $pdf_file_name, $image, $msg, $name);
                                            $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                            Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                            <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                            <tr>
                                            <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                            </tr><tr>\t
                                            <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                            </tr>
                                            <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                            <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                            <tr>\t\t
                                            <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                                </td>
                                            </tr>
                                            </table>\t
\t\t\t\t\t\t\t\t\t\t    <br>";
                                            $to_name1 = array();
                                            $to_email1 = array();
                                            array_push($to_email1, get_client_email($conn, $consignee_name), get_client_email($conn, $consignor_name));
                                            array_push($to_name1, get_client_name($conn, $consignee_name), get_client_name($conn, $consignor_name));
                                            sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);
                                        }
                                    } else {
                                        if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                            $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                            Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                            <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                            <tr>
                                            <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                            </tr><tr>\t
                                            <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                            </tr>
                                            <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                            <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                            <tr>\t\t
                                            <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                                </td>
                                            </tr>
                                            </table>\t
\t\t\t\t\t\t\t\t\t\t    <br>";
                                            $to_name1 = array();
                                            $to_email1 = array();
                                            array_push($to_email1, get_client_email($conn, $consignee_name), get_client_email($conn, $consignor_name));
                                            array_push($to_name1, get_client_name($conn, $consignee_name), get_client_name($conn, $consignor_name));
                                            sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);
                                        }
                                    }
                                }
                            } else {
                                $check_partywise_frq = checkPartyWiseFrequency($conn, $consignor_name);
                                if ($check_partywise_frq == 0) {
                                    if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                        $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                        Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                        <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                        <tr>
                                        <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                        </tr><tr>\t
                                        <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                        </tr>
                                        <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                        <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                        <tr>\t\t
                                        <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                            </td>
                                        </tr>
                                        </table>\t
                                        <br>";
                                        $to_name1 = array();
                                        $to_email1 = array();
                                        array_push($to_email1, get_client_email($conn, $consignor_name), get_client_email($conn, $consignee_name));
                                        array_push($to_name1, get_client_name($conn, $consignor_name), get_client_name($conn, $consignee_name));
                                        sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);
                                    }
                                } else {
                                    $check_restricted = check_invoice_restricted($conn, $consignor_name);
                                    if ($check_restricted == 0 && $mode_of_consignment != '3') {
                                        $get_client_details = get_client($conn, $consignor_name);
                                        $company_name = $get_client_details['client_company_name'];
                                        $email = $get_client_details['email'];
                                        $phone = $get_client_details['contact_no'];
                                        $amount_array = array($balance);
                                        $get_transaction_id_arr = array($get_transaction_id);
                                        $grnn_date_array = array($grnn_date);
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
                                        $dir = 'digital_invoice/';
                                        $pdf_file_name = '';
                                        $msg = "<p style=\"line-height: 24px; margin-bottom:15px;\">
\t\t\t\t\t\t\t\t\t\t\tThank You for Your Order On <a href = \"https://elitewave360.in\" >Elite Wave 360</a> on " . $grnn_date . "! <br>
\t\t\t\t\t\t\t\t\t\t\tFollowing Your Successful Consignment Delivery. \t\t\t\t
\t\t\t\t\t\t\t\t\t\t\t<table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
\t\t\t\t\t\t\t\t\t\t\t<tr>
\t\t\t\t\t\t\t\t\t\t\t<td >GCN No\t</td><td >" . $grnn_no . "</td>
\t\t\t\t\t\t\t\t\t\t\t</tr><tr>\t
\t\t\t\t\t\t\t\t\t\t\t<td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
\t\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t\t<tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
\t\t\t\t\t\t\t\t\t\t\t<tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
\t\t\t\t\t\t\t\t\t\t\t<tr>\t\t
\t\t\t\t\t\t\t\t\t\t\t<td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
\t\t\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t</table>\t
\t\t\t\t\t\t\t\t\t\t\t<br>
\t\t\t\t\t\t\t\t\t\t\t<br>
\t\t\t\t\t\t\t\t\t\t\tPayment Link : <a href = \"https://elitewave360.in/verify_paylink1.php?data=" . urlencode($link_wit_data) . '" >Payment Link</a>';
                                        $to_name = array();
                                        $to_email = array();
                                        if (!empty(get_client_email($conn, $consignor_name))) {
                                            array_push($to_email, get_client_email($conn, $consignor_name));
                                            array_push($to_name, get_client_name($conn, $consignor_name));
                                            $mail = sendAttachments($to_name, $to_email, 'Consignment Invoice Notification  With Payment Link', $pdf_file_name, $image, $msg, $name);
                                            $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                            Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                            <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                            <tr>
                                            <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                            </tr><tr>\t
                                            <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                            </tr>
                                            <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                            <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                            <tr>\t\t
                                            <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                                </td>
                                            </tr>
                                            </table>\t
\t\t\t\t\t\t\t\t\t\t    <br>";
                                            $to_name1 = array();
                                            $to_email1 = array();
                                            array_push($to_email1, get_client_email($conn, $consignor_name), get_client_email($conn, $consignee_name));
                                            array_push($to_name1, get_client_name($conn, $consignor_name), get_client_name($conn, $consignee_name));
                                            sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);
                                        }
                                    } else {
                                        if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                                            $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                                            Thank You for Your Order On <a href = "https://elitewave360.in" >Elite Wave 360</a> on ' . $grnn_date . "! <br>Your consignment has been delivered successfully! \t\t\t\t
                                            <table width=\"70%\" cellpadding=\"5\" cellspacing=\"0\" border=\"1\" align=\"center\" style=\"color: #888888; font-size: 16px; font-family: Work Sans, Calibri, sans-serif; line-height: 24px;\">
                                            <tr>
                                            <td >GCN No\t</td><td >" . $grnn_no . "</td>
                                            </tr><tr>\t
                                            <td >GCN Date:\t</td><td >\t" . $grnn_date . "\t</td>\t
                                            </tr>
                                            <tr><td >Booked By\t</td><td >" . get_client_name($conn, $consignor_name) . ' , ' . get_city_name($conn, $origin_name) . "</td>\t</tr>\t
                                            <tr><td >Booked to\t</td><td >\t" . get_client_name($conn, $consignee_name) . ' , ' . get_city_name($conn, $destination_name) . "</td>\t</tr>\t
                                            <tr>\t\t
                                            <td >Status\t</td><td >Consignment Delivered Successfully.</td>\t\t
                                                </td>
                                            </tr>
                                            </table>\t
\t\t\t\t\t\t\t\t\t\t    <br>";
                                            $to_name1 = array();
                                            $to_email1 = array();
                                            array_push($to_email1, get_client_email($conn, $consignor_name), get_client_email($conn, $consignee_name));
                                            array_push($to_name1, get_client_name($conn, $consignor_name), get_client_name($conn, $consignee_name));
                                            sendAppMail($to_name1, $to_email1, 'Consignment Delivery Notification', $msg1);
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // Non-delivered status — email notification (no echo here)
                        $msg1 = '<p style="line-height: 24px; margin-bottom:15px;">
                            <p style="color:black;">Your Booking Status:  <span>' . get_trans_status($status) . ' </span> - </br>
                            Against Your GCN No: <span>' . $grn_number . ' </span></p></p></br>
                            <p><a href="https://elitewave360.in/tracking.php">Click to track consignment</a></p>';
                        $to_name = array();
                        $to_email = array();
                        if (!empty(get_client_email($conn, $consignor_name)) && !empty(get_client_email($conn, $consignee_name))) {
                            array_push($to_email, get_client_email($conn, $consignor_name), get_client_email($conn, $consignee_name));
                            array_push($to_name, get_client_name($conn, $consignor_name), get_client_name($conn, $consignee_name));
                        }
                    }
                    // *End Send invoice
                    // FIX 2: NO echo here anymore — $updated flag is set above
                }
            }

            break;  // FIX 1: found the right table, stop scanning further partition tables
        }
    }

    // FIX 2 + 3: single, clean response — replaces the broken "if (!$matched)" block
    echo $updated ? 1 : 0;
}

if ($form_name == 'add_gst_tax_master' || $form_name == 'edit_gst_tax_master') {
    require_once('include/gst_tax_functions.php');
    ensure_gst_tax_master_table($conn);
    header('Content-Type: application/json; charset=utf-8');

    $tax_code = strtoupper(trim($_POST['tax_code'] ?? ''));
    $tax_name = trim($_POST['tax_name'] ?? '');
    $gst_rate = (float) ($_POST['gst_rate'] ?? 0);
    $cgst_rate = (float) ($_POST['cgst_rate'] ?? 0);
    $sgst_rate = (float) ($_POST['sgst_rate'] ?? 0);
    $igst_rate = (float) ($_POST['igst_rate'] ?? 0);
    $cess_rate = (float) ($_POST['cess_rate'] ?? 0);
    $status = isset($_POST['status']) && (int) $_POST['status'] === 0 ? 0 : 1;
    $edit_id = (int) ($_POST['edit_id'] ?? 0);

    if ($tax_code === '' || $tax_name === '') {
        echo json_encode(array('status' => 0, 'message' => 'Tax Code and Tax Name are required.'));
        exit;
    }

    $validation_error = gst_tax_validate_payload($gst_rate, $cgst_rate, $sgst_rate, $igst_rate, $cess_rate);
    if ($validation_error !== '') {
        echo json_encode(array('status' => 0, 'message' => $validation_error));
        exit;
    }

    if (gst_tax_code_exists($conn, $tax_code, $form_name == 'edit_gst_tax_master' ? $edit_id : 0)) {
        echo json_encode(array('status' => 0, 'message' => 'Tax Code already exists.'));
        exit;
    }

    $tax_code_sql = mysqli_real_escape_string($conn, $tax_code);
    $tax_name_sql = mysqli_real_escape_string($conn, $tax_name);

    if ($form_name == 'add_gst_tax_master') {
        $sql = "INSERT INTO gst_tax_master
            (tax_code, tax_name, gst_rate, cgst_rate, sgst_rate, igst_rate, cess_rate, status, is_deleted, created_at, created_by)
            VALUES (
                '$tax_code_sql', '$tax_name_sql',
                '$gst_rate', '$cgst_rate', '$sgst_rate', '$igst_rate', '$cess_rate',
                '$status', 0, '$created_at', '$created_by'
            )";
        $ok = mysqli_query($conn, $sql);
        echo json_encode(array(
            'status' => $ok ? 1 : 0,
            'message' => $ok ? 'GST tax profile added successfully.' : 'Unable to add GST tax profile.',
        ));
        exit;
    }

    if ($edit_id <= 0) {
        echo json_encode(array('status' => 0, 'message' => 'Invalid record for update.'));
        exit;
    }

    $sql = "UPDATE gst_tax_master SET
        tax_code='$tax_code_sql',
        tax_name='$tax_name_sql',
        gst_rate='$gst_rate',
        cgst_rate='$cgst_rate',
        sgst_rate='$sgst_rate',
        igst_rate='$igst_rate',
        cess_rate='$cess_rate',
        status='$status',
        updated_at='$updated_at',
        updated_by='$updated_by'
        WHERE gst_tax_id='$edit_id' AND is_deleted=0";
    $ok = mysqli_query($conn, $sql);
    echo json_encode(array(
        'status' => $ok ? 1 : 0,
        'message' => $ok ? 'GST tax profile updated successfully.' : 'Unable to update GST tax profile.',
    ));
    exit;
}

if ($form_name == 'toggle_gst_tax_status') {
    require_once('include/gst_tax_functions.php');
    header('Content-Type: application/json; charset=utf-8');
    $tbl_id = (int) ($_POST['tbl_id'] ?? 0);
    $status = isset($_POST['status']) && (int) $_POST['status'] === 0 ? 0 : 1;
    if ($tbl_id <= 0) {
        echo json_encode(array('status' => 0, 'message' => 'Invalid record.'));
        exit;
    }
    $ok = mysqli_query($conn, "UPDATE gst_tax_master SET status='$status', updated_at='$updated_at', updated_by='$updated_by'
        WHERE gst_tax_id='$tbl_id' AND is_deleted=0");
    echo json_encode(array(
        'status' => $ok ? 1 : 0,
        'message' => $ok ? ($status ? 'Tax profile activated.' : 'Tax profile deactivated.') : 'Status update failed.',
    ));
    exit;
}

if ($form_name == 'soft_delete_gst_tax_master') {
    require_once('include/gst_tax_functions.php');
    header('Content-Type: application/json; charset=utf-8');
    $tbl_id = (int) ($_POST['tbl_id'] ?? 0);
    if ($tbl_id <= 0) {
        echo json_encode(array('status' => 0, 'message' => 'Invalid record.'));
        exit;
    }
    $ok = mysqli_query($conn, "UPDATE gst_tax_master SET is_deleted=1, status=0, updated_at='$updated_at', updated_by='$updated_by'
        WHERE gst_tax_id='$tbl_id' AND is_deleted=0");
    echo json_encode(array(
        'status' => $ok ? 1 : 0,
        'message' => $ok ? 'GST tax profile deleted (soft delete).' : 'Delete failed.',
    ));
    exit;
}

if ($form_name == 'restore_gst_tax_master') {
    require_once('include/gst_tax_functions.php');
    header('Content-Type: application/json; charset=utf-8');
    $tbl_id = (int) ($_POST['tbl_id'] ?? 0);
    if ($tbl_id <= 0) {
        echo json_encode(array('status' => 0, 'message' => 'Invalid record.'));
        exit;
    }
    $ok = mysqli_query($conn, "UPDATE gst_tax_master SET is_deleted=0, status=1, updated_at='$updated_at', updated_by='$updated_by'
        WHERE gst_tax_id='$tbl_id' AND is_deleted=1");
    echo json_encode(array(
        'status' => $ok ? 1 : 0,
        'message' => $ok ? 'GST tax profile restored.' : 'Restore failed.',
    ));
    exit;
}
