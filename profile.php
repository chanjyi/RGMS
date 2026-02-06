<?php
session_start();
require 'config.php';

// 1. Security Check
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email'];

// 2. Fetch User Data
$query = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// 3. Prepare Display Data
$avatar_file = !empty($user['profile_pic']) ? "images/" . htmlspecialchars($user['profile_pic']) : "images/default.png";
$role_nice   = ucfirst($user['role']);
$name        = htmlspecialchars($user['name']);

// Handle Joined Date (Safe Fallback)
$joined_date = "January 2026"; 
if (!empty($user['joined_at'])) {
    $joined_date = date("F Y", strtotime($user['joined_at']));
}

// Dashboard Link Logic
$dashboardLink = 'index.php'; // Default
if ($user['role'] == 'admin') $dashboardLink = 'admin_page.php';
if ($user['role'] == 'reviewer') $dashboardLink = 'reviewer_page.php';
if ($user['role'] == 'hod') $dashboardLink = 'hod_page.php';
if ($user['role'] == 'researcher') $dashboardLink = 'researcher_dashboard.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | RGMS</title>
    <link rel="stylesheet" href="styling/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        
        <div style="margin-bottom: 20px;">
            <a href="<?= $dashboardLink ?>" class="btn-back">
                <i class='bx bx-arrow-back'></i> Back to Dashboard
            </a>
        </div>

        <div class="welcome-text">My Profile</div>

        <div class="profile-wrapper">
            
            <div class="profile-photo-card">
                <div class="profile-img-container">
                    <img src="<?= $avatar_file ?>" alt="Profile Picture">
                </div>
                
                <a href="settings.php?view=profile" class="btn-save" style="text-decoration: none; width: 100%; display: block;">
                    Edit Profile
                </a>
            </div>

            <div class="profile-info-card">
                
                <div class="info-header">Personal Details</div>

                <div class="detail-row">
                    <div class="detail-icon"><i class='bx bx-user'></i></div>
                    <div class="detail-content">
                        <label>Full Name</label>
                        <span><?= $name ?></span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-icon"><i class='bx bx-id-card'></i></div>
                    <div class="detail-content">
                        <label>System Role</label>
                        <span><?= $role_nice ?></span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-icon"><i class='bx bx-envelope'></i></div>
                    <div class="detail-content">
                        <label>Email Address</label>
                        <span><?= $email ?></span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-icon"><i class='bx bx-calendar'></i></div>
                    <div class="detail-content">
                        <label>Member Since</label>
                        <span><?= $joined_date ?></span>
                    </div>
                </div>

            </div>
        </div>

    </section>

</body>
</html>