<?php
session_start();

// Database connection details (Remember to replace these)
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

    if ($otp == $stored_otp) {
        // OTP is correct, register the user
        $name = $_SESSION["name"];
        $email = $_SESSION["email"];
        $password = $_SESSION["password"];

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and execute the SQL statement
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $hashed_password);

        if ($stmt->execute()) {
            // Registration successful
            echo "Account registered successfully!";
            header("Location: login.php");
            exit();
        } else {
            $error_message = "Error registering account: " . $stmt->error;
        }

        $stmt->close();

        // Clear session variables (optional, but recommended)
        unset($_SESSION["otp"]);
        unset($_SESSION["name"]);
        unset($_SESSION["email"]);
        unset($_SESSION["password"]);

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
</head>
<body>
<div class="squire-bg"> </div>
    <div class="box-container_otp">
        <div class="box_otp">
             
            <div class="action-box">
                <div class="center_cointainer_otp">
                    <h1>OTP Authentication</h1>
                    <p style="text-align: center;">The 5-digit code will be sent to your email. Please enter the code you received.</p>
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