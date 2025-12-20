<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Dashboard</title>
    
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        
        <div class="welcome-text">
            Welcome <?= $_SESSION['name']; ?>, 
            <span>(<?= isset($_SESSION['role']) ? $_SESSION['role'] : 'Reviewer'; ?>)</span>
        </div>

        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 20px;">

        <p>This is the reviewer dashboard.</p>
        
    </section>

</body>
</html>