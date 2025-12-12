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
    <link rel="stylesheet" href="sidebar.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    </head>

    <body>
    <?php include 'sidebar.php'; ?>
        <div class="container">
            <h1>Welcome, reviewer!<span> <?= $_SESSION['name'];?></span></h1>
            <p>This is the reviewer dashboard.</p>
        </div>
        <script src="script.js"></script>
    </body>
</html>
