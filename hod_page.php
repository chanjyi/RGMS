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
        <title>hod Page</title>
        <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    </head>

    <body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
            
            <div class="welcome-text">
                Welcome <?= $_SESSION['name']; ?>, 
                <span>(<?= isset($_SESSION['role']) ? $_SESSION['role'] : 'Head of Department'; ?>)</span>
            </div>

            <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 20px;">

            <p>This is the HOD dashboard.</p>
            
        </section>
    </body>
</html>
