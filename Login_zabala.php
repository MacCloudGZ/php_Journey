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

<html>
    <head>
        <title>LOGIN</title>
        <link href="center.css" rel="stylesheet"/>
    </head>
    <body>
        <div class="center_cointainer">
        <h1>LOGIN</h1>
            <input type="email" placeholder="EMAIL" id="">
            <input type="password" placeholder="PASSWORD" id="">
            <button type="submit">Login</button>
            <p>
                <a href="Signup_zabala.php">Register another Account</a>
            </p>
        </div>
    </body>
</html>