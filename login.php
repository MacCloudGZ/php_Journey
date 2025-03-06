<?php
    session_start();
    // Database configuration
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
        $email = $_POST["email"]; // Changed from username to email
        $password = $_POST["password"]; // **IMPORTANT:  Hash and salt this password in registration and verify it securely here!**

        // **VERY IMPORTANT: Use prepared statements to prevent SQL injection!**
        $sql = "SELECT id, username, password FROM users WHERE email = ?"; //Assuming you have username and password
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email); // "s" indicates a string
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Verify the password (replace with your password verification logic)
            if ($password == $row["password"]) { //**replace this with password_verify
                // Account found
                $_SESSION["username"] = $row["username"]; // Store username in session
                header("Location: lobby.php"); // Redirect to lobby
                exit();
            }
            else {
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
    <div class="squire-bg">&nbsp</div>
    <div class="box-container">
        <div class="box">
            &nbsp
            <div class="action-box">
                <div class="center_cointainer">
                    <h1>LOGIN</h1>
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
                    <?php if (isset($error_message)) {
                            echo "<p style='color:red;'>$error_message</p>";
                        } ?>
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