<?php
session_start();

// Database connection details (Replace with your credentials)
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Prepare the SQL statement
    $sql = "SELECT id, username, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashed_password = $row["password"]; // Get the hashed password from the database

        // Verify the password using password_verify()
        if (password_verify($password, $hashed_password)) {
            // Account found
            $_SESSION["username"] = $row["username"];
            header("Location: lobby.php");
            exit();
        } else {
            $error_message = "Incorrect password.";
        }
    } else {
        $error_message = "Account not found.";
    }

    $stmt->close();
}

$conn->close();
?>

<html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
                        <span>LOGIN</span>
                        <?php if (isset($error_message)) {
                                    echo "<p style='color:red;'>$error_message</p>";
                        }else {
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
                                <input type="password" name="password" class="input_box"required>
                            </div>
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