<?php
session_start();

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "act1";

// PHPMailer Configuration (Important: Update with your settings)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = ""; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($password != $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Generate OTP
        $otp = rand(10000, 99999);

        // Store OTP in session
        $_SESSION["otp"] = $otp;
        $_SESSION["name"] = $name;
        $_SESSION["email"] = $email;
        $_SESSION["password"] = $password; // Store password for later hashing

        // Send OTP via Email (PHPMailer)
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = 0;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.example.com';                     //Set the SMTP server to send through
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
            echo 'Message has been sent';

            header("Location: otp.php"); // Redirect to OTP page
            exit();

        } catch (Exception $e) {
            $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}

$conn->close();
?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SignUp</title>
    <link rel="stylesheet" href="style/style.css">
    <link href="style/center.css" rel="stylesheet"/>
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