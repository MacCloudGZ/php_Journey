<?php
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

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST["name"]; // changed from username to name
        $email = $_POST["email"];
        $password = $_POST["password"]; // **IMPORTANT: Hash and salt this password before storing it!**

        // **IMPORTANT: Sanitize and validate the input data to prevent SQL injection and other vulnerabilities!**

        //**Password hashing (example using password_hash - requires PHP 5.5+)**
        //$hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // **VERY IMPORTANT:  Use prepared statements to prevent SQL injection!**
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"; // Assuming 'name' becomes 'username' in the database
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $password); // "sss" indicates three strings

        if ($stmt->execute()) {
            echo "New record created successfully";
            header("Location: login.php"); // Redirect to login page after registration
            exit();
        } else {
            echo "Error: " . $stmt->error; // Use $stmt->error for prepared statement errors
        }

        $stmt->close();
    }

    $conn->close();
?>
<html">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SignUp</title>
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
                                <input type="password" name="password" class="input_box"required>
                            </div>
                            <button type="submit"class="input_button">REGISTER</button>
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