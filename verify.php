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

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Prepare and execute the SQL statement to update the is_verified status
    $sql = "UPDATE users SET is_verified = TRUE WHERE verification_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);

    if ($stmt->execute()) {
        // Verification successful
        $_SESSION["verification_success"] = "Your email has been verified. Please log in."; // set session message

        // Redirect to login page
        header("Location: login.php");
        exit();
    } else {
       echo $stmt->error; // If the is an issue, this should pop up
    }

    $stmt->close();
} else {
    // If no token is provided, redirect to register.php or display an error
    header("Location: register.php");
    exit();
}

$conn->close();
?>