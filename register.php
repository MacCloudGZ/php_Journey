<?php
session_start();

include 'database.php'; // Include your database connection file
// PHPMailer Configuration
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// reCAPTCHA Secret Key (KEEP THIS SECRET!)
$recaptchaSecretKey = "6LfDoPAqAAAAAIdxXYBAieFnsruSM4LXm24fj0WM"; // **SECRET KEY**

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // reCAPTCHA Response
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    // Verify reCAPTCHA (Server-Side)
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $recaptchaSecretKey,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $verify = file_get_contents($url, false, $context);
    $captcha_success = json_decode($verify);

    if ($captcha_success->success == true) {  
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $dateToday = date('Y-m-d');
        
        // Check how many times this IP has registered today
        $check_sql = "SELECT COUNT(*) FROM registrations WHERE ip_address = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $ip_address, $dateToday);
        $check_stmt->execute();
        $check_stmt->bind_result($ip_count);
        $check_stmt->fetch();
        $check_stmt->close();
        
        if ($ip_count >= 3) {
            $error_message = "You have reached the maximum number of registration attempts for today.";
        } else {
            // Record the registration attempt
            $log_sql = "INSERT INTO registrations (ip_address, date) VALUES (?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("ss", $ip_address, $dateToday);
            $log_stmt->execute();
            $log_stmt->close();
        
            $name = $_POST["name"];
            $email = $_POST["email"];
            $password = $_POST["password"];
            $confirm_password = $_POST["confirm_password"];

            if ($password != $confirm_password) {
                $error_message = "Passwords do not match.";
            } else {
                // Generate a unique verification token
                $verification_token = bin2hex(random_bytes(32)); // Generate a random 64-character hexadecimal string

                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Prepare and execute the SQL statement
                $sql = "INSERT INTO users (username, email, password, verification_token) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $name, $email, $hashed_password, $verification_token);

                if ($stmt->execute()) {
                    // Registration successful, send email verification

                    // PHPMailer
                    $mail = new PHPMailer(true);

                    try {
                        //Server settings
                        $mail->SMTPDebug = 0;                      //Enable verbose debug output
                        $mail->isSMTP();                                            //Send using SMTP
                        $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
                        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
                        $mail->Username   = 'zkurtgabrielle@gmail.com';                     //SMTP username
                        $mail->Password   = 'sxkxsgjkaluauvul';                               //SMTP password
                        $mail->SMTPSecure = 'tls';            //Enable implicit TLS encryption
                        $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
                        
                        //Recipients
                        $mail->setFrom('zkurtgabrielle@gmail.com', 'Kurt Zabala');
                        $mail->addAddress($email, $name);     //Add a recipient

                        // Content
                        $mail->isHTML(true);                                  //Set email format to HTML
                        $mail->Subject = 'Verify Your Email';
                        $verification_link = "http://localhost/php_Journey/verify.php?token=" . $verification_token;  // Use Correctly
                        $mail->Body    = "Please click on the following link to verify your email: <a href='" . $verification_link . "'>" . $verification_link . "</a>";

                        $mail->send();
                        // Set session variables to show a verification prompt to the users in the page
                        $_SESSION["success_message"] = "Registration successful! Please verify your email address to continue.";
                        //after they have put the info set to email and redirect
                        header("Location: email_verification.php");
                        exit();

                    } catch (Exception $e) {
                        $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $error_message = "Error registering account: " . $stmt->error;
                }

                $stmt->close();
            }
        }
      
    } else {
        // reCAPTCHA verification failed
        $error_message = "reCAPTCHA verification failed. Please try again.";
    }
}

$conn->close();
?>
<html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SignUp</title>
    <link rel="stylesheet" href="style/style.css">
    <link href="style/center.css" rel="stylesheet"/>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="squire-bg"> </div>
    <div class="box-container">
        <div class="box">
             
            <div class="action-box">
                <div class="center_cointainer">
                    <div class="top_area">
                        <span>SIGN-UP</span>
                        <?php if ($error_message != "") {
                            echo "<p style='color:red;'>$error_message</p>";
                        }else {
                            echo "<p stlye = 'color:black;'>Enter the following</p>";
                        } ?>
                    </div>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="input_container">
                            <span class="input_label">NAME</span>
                            <input type="text" name="name" class="input_box" required>
                        </div>
                        <div class="input_container">
                            <span class="input_label">EMAIL</span>
                            <input type="email" name="email" class="input_box" required>
                        </div>
                        <div class="input_container">
                            <span class="input_label">PASSWORD</span>
                            <input type="password" name="password" class="input_box" required>
                        </div>
                        <div class="input_container">
                            <span class="input_label">CONFIRM PASSWORD</span>
                            <input type="password" name="confirm_password" class="input_box" required>
                        </div>
                        <div class="g-recaptcha" data-sitekey="6LfDoPAqAAAAABRFBJ7ZBCIsHx_XFNc3S9TS8gPL"></div>
                        <br>
                        <button type="submit" class="input_button">REGISTER</button>
                    </form>
                </div>
            </div>
            <div class="log-switch">
                <a href="login.php">
                    <div class="toggle-box notactive">
                        LOGIN
                    </div>
                </a>
                <div class="toggle-box">
                    REGISTER AN ACCOUNT
                </div>
            </div>
        </div>
    </div>
</body>
</html> 