<?php
session_start();

// Check if the user is logged in (has a session)
if (!isset($_SESSION["username"])) {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

// Logout Functionality
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout"])) {
    // Destroy the session
    session_destroy();

    // Redirect to login page
    header("Location: login.php");
    exit();
}

$username = $_SESSION["username"]; // Get the username from the session
?>

<html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby</title>
    <link rel="stylesheet" href="style/style.css">
    <link href="style/center.css" rel="stylesheet"/>
    <link rel="stylesheet" href="style/lobby.css">
</head>
<body>
    <div class="squire-bg"> </div>
    <div class="index-container">
        <div class="side_box">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <button type="submit" class="logout-button" name="logout">!</button>
            </form>
            <!--Here's will display the account name-->
            <p style="text-align:center; color:white;">Welcome, <?php echo htmlspecialchars($username); ?></p> <!-- Display Username -->
        </div>
        <div class="actual_box">
            <div class="action_box">
                s
            </div>
        </div>
    </div>
</body>
</html>