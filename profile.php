<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// DETERMINE IMAGE (Using 'profile_pic' column and 'images/' folder)
$avatar_file = !empty($user['profile_pic']) ? "images/" . $user['profile_pic'] : "images/default.png";

// DETERMINE DASHBOARD LINK BASED ON ROLE
$dashboardLink = 'index.php';
if (isset($user['role'])) {
    switch ($user['role']) {
        case 'admin': $dashboardLink = 'admin_page.php'; break;
        case 'reviewer': $dashboardLink = 'reviewer_page.php'; break;
        case 'hod': $dashboardLink = 'hod_page.php'; break;
        case 'researcher': $dashboardLink = 'researcher_dashboard.php'; break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="styling/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
        
        <a href="<?= $dashboardLink ?>" class="btn-back" style="display: inline-flex; align-items: center; text-decoration: none; color: #3C5B6F; font-weight: 600; margin-bottom: 15px;">
            <i class='bx bx-left-arrow-alt' style="font-size: 20px; margin-right: 5px;"></i> 
            Back to Dashboard
        </a>

        <div class="welcome-text">My Profile</div>
        
        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin: 20px 0;">

        <div class="profile-container">
            
            <div class="form-box profile-card">
                
                <div class="profile-avatar" style="padding: 0; overflow: hidden; background: white; border: 5px solid #EFE6D5;">
                    <img src="<?= $avatar_file ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                </div>

                <h2 class="profile-name"><?= htmlspecialchars($user['name']) ?></h2>
                <div class="profile-role-badge">
                    <?= ucfirst($user['role']) ?>
                </div>
                
                <p class="profile-email">
                    <i class='bx bx-envelope'></i> <?= htmlspecialchars($user['email']) ?>
                </p>

                <div class="profile-actions">
                    <a href="settings.php?view=profile" style="color: #153448; text-decoration: none; font-weight: 600; font-size: 16px;">
                        <i class='bx bx-edit-alt'></i> Edit Profile
                    </a>
                </div>
            </div>

            <div class="form-box placeholder-area">
                <div class="placeholder-content">
                    <i class='bx bx-layer-plus'></i>
                    <h3>Additional Info Area</h3>
                    <p>You can add biography, recent activity, or stats here later.</p>
                </div>
            </div>

        </div>

    </section>
</body>
</html>