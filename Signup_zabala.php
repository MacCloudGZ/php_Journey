<?php
    $host ="locahost";
    $user = "root";
    $password ="";
    $dbname = "act1";

    // $conn = new mysql($host,$user,$password,$dbname);
    // if(['REQUIRE_METHOD'] == 'POST'){
    //     $name = $_POST['name'];
    //     $email = $_POST['email'];
    //     $password = $_POST['password'];

    //     $sql = "INSET INTO users (name,email,password) value (?,?,?)";
    //     $stmt = $conn->prepare($sql);
    //     $stst = 
    // }
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
                        <input type="name" placeholder="NAME">
                        <input type="email" placeholder="EMAIL">
                        <input type="password" placeholder="PASSWORD">
                        <button type="submit">REGISTER</button>
                    </div>
                </div>
                <div class="log-switch">
                    
                    <a href="Login_zabala.php">
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