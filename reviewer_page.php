<?php
session_start();
if (!isset($_SESSION['email'] )) {
    // Redirect to login page if not logged in as admin
    header('Location: index.php');
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>reviewer Page</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container">
            <h1>Welcome, reviewer!<span> <?= $_SESSION['name'];?></span></h1>
            <p>This is the reviewer dashboard.</p>
            <button onlick ="Window.location.href="logout.php" >Logout</button>
        </div>
    </body>
</html>
