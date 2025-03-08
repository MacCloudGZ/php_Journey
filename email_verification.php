<?php
session_start();

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "act1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST["otp1"] . $_POST["otp2"] . $_POST["otp3"] . $_POST["otp4"] . $_POST["otp5"]; // Combine OTP digits
    $stored_otp = $_SESSION["otp"];
    $login_email = $_SESSION["login_email"]; //get the data from the session

    if ($otp == $stored_otp) {
        // OTP is correct, login the user

        $sql = "SELECT username FROM users WHERE email = ?"; // get the email that has been saved from login
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $login_email); //bind the correct email
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            $row = $result->fetch_assoc();

             $_SESSION["username"] = $row["username"];
             unset($_SESSION["otp"]);
             unset($_SESSION["login_email"]);
             header("Location: lobby.php");
             exit();
        } else {
            $error_message = "Incorrect Email.";
        }


        $stmt->close();

    } else {
        $error_message = "Incorrect OTP. Please try again.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP</title>
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
                    <h1>OTP Authentication</h1>
                    <p style="text-align: center;">Please enter the 5-digit OTP code sent to your email.</p>
                    <p>Didn't receive the email? <a href="#">Click here</a></p>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <!-- 5-digit OTP input -->
                        <div class="otp-container">
                            <input type="text" id="otp1" name="otp1" maxlength="1" class="otp-input" required>
                            <input type="text" id="otp2" name="otp2" maxlength="1" class="otp-input" required>
                            <input type="text" id="otp3" name="otp3" maxlength="1" class="otp-input" required>
                            <input type="text" id="otp4" name="otp4" maxlength="1" class="otp-input" required>
                            <input type="text" id="otp5" name="otp5" maxlength="1" class="otp-input" required>
                        </div>
                         <?php if ($error_message != "") {
                            echo "<p style='color:red;'>$error_message</p>";
                            } ?>
                        <button type="submit" class="input_button">CONTINUE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>