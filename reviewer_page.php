<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'reviewer') {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email'];

// 1. FETCH USER DETAILS (Name & Profile Pic)
$user_q = $conn->prepare("SELECT id, name, profile_pic FROM users WHERE email = ?");
$user_q->bind_param("s", $email);
$user_q->execute();
$user_data = $user_q->get_result()->fetch_assoc();

$user_id = $user_data['id'];
$username = $user_data['name'];
$user_role = ucfirst($_SESSION['role']); // e.g. "Reviewer"

// Handle Profile Picture Path (Default to default.png if empty)
$profile_pic = !empty($user_data['profile_pic']) ? "images/" . $user_data['profile_pic'] : "images/default.png";

// 2. FETCH COUNTS
$p_count = $conn->query("SELECT COUNT(*) as c FROM reviews WHERE reviewer_id = $user_id AND status = 'Pending'")->fetch_assoc()['c'];
$h_count = $conn->query("SELECT COUNT(*) as c FROM reviews WHERE reviewer_id = $user_id AND status = 'Completed'")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reviewer Dashboard</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
        
        <div class="dashboard-header-group">
            <h1 class="main-title">Reviewer Dashboard</h1>
            
            <div class="user-identity-row">
                <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile" class="identity-img">
                <div class="identity-text">
                    <div class="identity-name"><?= htmlspecialchars($username) ?></div>
                    <div class="identity-role"><?= htmlspecialchars($user_role) ?></div>
                </div>
            </div>
        </div>
        
        <hr class="dashboard-divider">

        <div style="max-width: 800px;">
            <a href="reviewer_pending.php" class="header-card">
                <div class="header-left">
                    <div class="header-icon-box"><i class='bx bx-time-five'></i></div>
                    <div class="header-text-group">
                        <h3>Assigned Proposals (Pending)</h3>
                        <span><?= $p_count ?> tasks waiting for review</span>
                    </div>
                </div>
                <i class='bx bx-chevron-right header-arrow'></i>
            </a>

            <a href="reviewer_stats.php" class="header-card">
                <div class="header-left">
                    <div class="header-icon-box"><i class='bx bx-pie-chart-alt-2'></i></div>
                    <div class="header-text-group">
                        <h3>Performance Charts</h3>
                        <span>Workload distribution & HOD stats</span>
                    </div>
                </div>
                <i class='bx bx-chevron-right header-arrow'></i>
            </a>

            <a href="reviewer_history.php" class="header-card">
                <div class="header-left">
                    <div class="header-icon-box"><i class='bx bx-history'></i></div>
                    <div class="header-text-group">
                        <h3>Review History</h3>
                        <span><?= $h_count ?> past decisions logged</span>
                    </div>
                </div>
                <i class='bx bx-chevron-right header-arrow'></i>
            </a>

            <a href="reviewer_misconduct.php" class="header-card">
                <div class="header-left">
                    <div class="header-icon-box"><i class='bx bx-error-circle'></i></div>
                    <div class="header-text-group">
                        <h3>Reported Misconduct</h3>
                        <span>View flagged cases</span>
                    </div>
                </div>
                <i class='bx bx-chevron-right header-arrow'></i>
            </a>

            <a href="profile.php" class="header-card">
                <div class="header-left">
                    <div class="header-icon-box"><i class='bx bx-user'></i></div>
                    <div class="header-text-group">
                        <h3>My Profile</h3>
                        <span>Update account details</span>
                    </div>
                </div>
                <i class='bx bx-chevron-right header-arrow'></i>
            </a>

        </div>
    </section>
</body>
</html>