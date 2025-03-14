<?php
session_start();

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "act1";

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

$error_message = ""; // Initialize error message

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
        $email = $_POST["email"];
        $password = $_POST["password"];

        $sql = "SELECT id, username, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $hashed_password = $row["password"];

            if (password_verify($password, $hashed_password)) {
                // Credentials are correct, now send OTP

                // Generate OTP
                $otp = rand(10000, 99999);

                // Store OTP and email in session
                $_SESSION["otp"] = $otp;
                $_SESSION["login_email"] = $email; // Store email in session, since the username can be access by email

                // Send OTP via Email (PHPMailer)
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

                    //Content
                    $mail->isHTML(true);                                  //Set email format to HTML
                    $mail->Subject = 'Your OTP Code';
                    $mail->Body    = 'Your OTP code is: ' . $otp;

                    $mail->send();
                    $otp_sent = true;

                    header("Location: otp.php"); // Redirect to OTP page
                    exit();

                } catch (Exception $e) {
                    $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

            } else {
                $error_message = "Incorrect password.";
            }
        } else {
            $error_message = "Account not found.";
        }

        $stmt->close();
    } else {
        // reCAPTCHA verification failed
        $error_message = "reCAPTCHA verification failed. Please try again.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
                        <span>LOGIN</span>
                        <?php if (isset($error_message)) {
                            echo "<p style='color:red;'>$error_message</p>";
                        }  else {
                            echo "<p stlye = 'color:black;'>WELCOME</p>";
                        } ?>
                    </div>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="input_container">
                            <span class="input_label">EMAIL</span>
                            <input type="email" name="email" class="input_box" required>
                        </div>
                        <div class="input_container">
                            <span class="input_label">PASSWORD</span>
                            <input type="password" name="password" class="input_box" required>
                        </div>
                        <div class="g-recaptcha" data-sitekey="6LfDoPAqAAAAABRFBJ7ZBCIsHx_XFNc3S9TS8gPL"></div>
                        <br>
                        <button type="submit" class="input_button">LOGIN</button>
                    </form>
                </div>
            </div>
            <div class="log-switch">
                <div class="toggle-box">
                    LOGIN
                </div>
                <a href="register.php">
                    <div class="toggle-box notactive">
                        REGISTER AN ACCOUNT
                    </div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>