<?php
    $host ="locahost";
    $user = "root";
    $password ="";
    $dbname = "act1";

    $conn = new mysql($host,$user,$password,$dbname);
    if(['REQUIRE_METHOD'] == 'POST'){
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        $sql = "INSET INTO users (name,email,password) value (?,?,?)";
        $stmt = $conn->prepare($sql);
        $stst = 
    }
?>
<html>
    <head>
        <title>SIGNUP</title>
        <link href="center.css" rel="stylesheet"/>
    </head>
    <body>
        <div class="center_cointainer">
            <h1>SIGN-UP</h1>
            <input type="name" placeholder="NAME">
            <input type="email" placeholder="EMAIL">
            <input type="password" placeholder="PASSWORD">
            <button type="submit">SIGN-UP</button>
            <p>
                <a href="Login_zabala.php">Login an Account</a>
            </p>
        </div>
    </body>
</html>