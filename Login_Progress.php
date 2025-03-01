<?php
session_start(); // Start the session at the very beginning of the script

$host = "localhost";
$user = "root";
$password = "";
$dbname = "act1";

$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Basic input validation (you should add more robust validation)
    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        // Use prepared statements to prevent SQL injection
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            // Verify password
            if (password_verify($password, $user["password"])) {
                // Password is correct, start session and redirect
                $_SESSION["user_id"] = $user["id"]; // Store user ID in session (you can store other info)
                $_SESSION["user_name"] = $user["name"];
                header("Location: welcome.php"); // Redirect to welcome page after successful login
                exit(); // Important: Terminate script execution after redirection
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "Invalid email or password.";
        }
        mysqli_stmt_close($stmt); // Close the statement
    }
}

mysqli_close($conn); // Close the database connection
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Process</title>
</head>
<body>
    <div class="container">
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <!-- You can add a link to go back to the login form if needed -->
        <p><a href="login_zabala.php">Back to Login</a></p>
    </div>
</body>
</html>