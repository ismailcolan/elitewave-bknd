<?php
// Headers for API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection
require_once('../../web/include/connect.php');

// Initialize response array
$response = array();

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST data
    $data = json_decode(file_get_contents("php://input"));

    // Check if JSON payload was sent, otherwise fall back to $_POST (for form-data)
    $email = isset($data->email) ? $data->email : (isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($data->password) ? $data->password : (isset($_POST['password']) ? $_POST['password'] : '');

    if (!empty($email) && !empty($password)) {
        // Sanitize inputs
        $email = mysqli_real_escape_string($conn, trim($email));
        $password = trim($password);

        // Query the database based on the current web flow logic (lookup by email only, verify password separately)
        $query = "SELECT * FROM users WHERE email='$email' AND status=0";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            if (password_verify($password, $row['password'])) {
                // Set session variables for authentication
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['company_id'] = $row['company_name'];
                $_SESSION['user_name'] = $row['user_name'];
                $_SESSION['email'] = $row['email'];

                $response['status'] = 'success';
                $response['message'] = 'Login successful';
                $response['data'] = array(
                    'user_id' => $row['user_id'],
                    'role' => $row['role'],
                    'company_id' => $row['company_name'], // from the current web logic
                    'user_name' => $row['user_name'],
                    'email' => $row['email'],
                    'contact_no' => $row['contact_no'],
                    'session_id' => session_id() // Provide session ID to client if needed
                );
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Invalid email or password';
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Invalid email or password';
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Email and password are required';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method. Please use POST.';
}

echo json_encode($response);
?>
