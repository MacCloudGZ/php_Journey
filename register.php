<?php
session_start();

// Database connection details (replace with your actual credentials)
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

$error_message = ""; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"]; // Get the confirm password

    // Check if passwords match
    if ($password != $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // **IMPORTANT: Sanitize and validate the input data!**

        // Password hashing (example using password_hash - requires PHP 5.5+)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // VERY IMPORTANT: Use prepared statements!
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $hashed_password); // Use hashed password

        if ($stmt->execute()) {
            echo "New record created successfully";
            header("Location: login.php"); // Redirect to login page after registration
            exit();
        } else {
            $error_message = "Error: " . $stmt->error;
        }

        $stmt->close();
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
                    <h1>SIGN-UP</h1>
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
                         <?php if ($error_message != "") {
                                    echo "<p style='color:red;'>$error_message</p>";
                                } ?>
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