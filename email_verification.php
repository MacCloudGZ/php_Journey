<?php
session_start();

// Check if the user has already registered
if(!isset($_SESSION["success_message"])) {
    header("Location: register.php");
    exit();
}

$success_message = $_SESSION["success_message"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link rel="stylesheet" href="style/style.css">
    <link href="style/center.css" rel="stylesheet"/>
    <link rel="stylesheet" href="style/otp.css">
    <link rel="stylesheet" href="style/loading.css">
</head>
<body>
<div class="squire-bg"> </div>
    <div class="box-container_otp">
        <div class="box_otp">
             
            <div class="action-box">
                <div class="center_cointainer_otp">
                    <h1>Email Verification</h1>
                    <?php if (isset($success_message)) {
                        echo "<p style='color:green; text-align: center'>$success_message</p>";
                    } ?>
                    <p style="text-align: center;">Please check your email to verify your account.</p>
                    <p>Didn't receive the email? <a href="#">Resend Email</a></p>

                    <div class="input_container">
                        <div class="verified_condition" style="display:flex; flex-direction: column; align-items: center;">
                            <span class="loader"></span>
                        </div>
                    </div>
                    <a href="login.php"><button type="submit" class="input_button">CONTINUE</button></a>
                </div>
            </div>
        </div>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = "register.php";
        }, 180000); // 3 minutes in milliseconds (3 * 60 * 1000 = 180000)
    </script>
</body>
</html>