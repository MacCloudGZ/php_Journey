<?php
    $host ="locahost";
    $user = "root";
    $password ="";
    $dbname = "act1";

    // if ($conn->connect_error) {
    //     die("Connection failed: " . $conn->connect_error);
    // }
    
    // $conn = new mysql($host,$user,$password,$dbname);
    // if(['REQUIRE_METHOD'] == 'POST'){
    //     $name = $_POST['name'];
    //     $email = $_POST['email'];
    //     $password = $_POST['password'];
    // }
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
                    <input type="email" placeholder="EMAIL" id="">
                    <input type="password" placeholder="PASSWORD" id="">
                    <button type="submit">LOGIN</button>
                </div>
            </div>
            <div class="log-switch">
                <div class="toggle-box">
                    LOGIN
                </div>
                <a href="Signup_zabala.php">
                    <div class="toggle-box notactive">
                        REGISTER AN ACCOUNT
                    </div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>