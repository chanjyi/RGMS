<?php
session_start();
require 'config.php';

// Verify HOD access
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Progress Tracking - RGMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="hod_pages.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <section class="home-section">
        <div class="welcome-text">
            <i class='bx bx-bar-chart-alt-2'></i>
            Research Progress Tracking
        </div>
        <hr style="opacity: 0.3; margin: 20px 0;">

        <div class="page-placeholder">
            <div class="placeholder-icon">
                <i class='bx bx-bar-chart-alt-2'></i>
            </div>
            <h2 class="placeholder-title">Research Progress Tracking</h2>
            <p class="placeholder-text">
                This feature will allow you to monitor and track the progress of ongoing research grants, 
                view milestones, track expenditures, and monitor research outcomes in real-time.
            </p>
            <p style="color: #ccc; margin-top: 20px; font-size: 14px;">
                <em>Coming Soon - Implementation in Progress</em>
            </p>
        </div>
    </section>
</body>
</html>
