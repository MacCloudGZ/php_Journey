<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login_zabala.php"); // Redirect to login if not logged in
    exit();
}
$userName = $_SESSION["user_name"]; // Get user's name from session
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Welcome</title>
    </head>
    <body>
        <h1>Welcome, <?php echo htmlspecialchars($userName); ?>!</h1>
        <p>You are now logged in.</p>
        <p><a href="logout.php">Logout</a></p>
    </body>
</html>