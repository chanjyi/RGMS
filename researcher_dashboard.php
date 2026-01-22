<?php
session_start();
require 'config.php';

// Security Check
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'researcher') {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email'];

// Fetch user details for the header
$stmt = $conn->prepare("SELECT name, profile_pic FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile picture fallback
$profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.png';
$profile_pic_path = "uploads/profile_pics/" . $profile_pic;

// Fallback to a generic placeholder if file doesn't exist locally
if (!file_exists($profile_pic_path)) {
    $profile_pic_path = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=3C5B6F&color=fff";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Researcher Dashboard | RGMS</title>
    
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="dashboard.css?v=<?= time(); ?>"> 
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        
        <div class="dashboard-header-group">
            <h1 class="main-title">Researcher Dashboard</h1>
            <div class="user-identity-row">
                <img src="<?= $profile_pic_path ?>" alt="Profile" class="identity-img">
                <div class="identity-text">
                    <span class="identity-name">Welcome, <?= htmlspecialchars($user['name']) ?></span>
                    <span class="identity-role">Researcher Account</span>
                </div>
            </div>
        </div>

        <hr class="dashboard-divider">

        <div class="nav-menu-grid">
            
            <a href="researcher_page.php" class="nav-card-btn">
                <div class="nav-content">
                    <span class="nav-text">My Research Management</span>
                    <span class="nav-desc">Manage proposals, grants & expenses</span>
                </div>
                <i class='bx bx-file'></i>
            </a>

            <a href="researcher_analytics.php" class="nav-card-btn">
                <div class="nav-content">
                    <span class="nav-text">Analytics & Reports</span>
                    <span class="nav-desc">View grant statistics & budget usage</span>
                </div>
                <i class='bx bx-bar-chart-alt-2'></i>
            </a>

            <a href="profile.php" class="nav-card-btn">
                <div class="nav-content">
                    <span class="nav-text">My Profile</span>
                    <span class="nav-desc">Update account details</span>
                </div>
                <i class='bx bx-user'></i>
            </a>

        </div>

    </section>

    <script src="script.js"></script>
</body>
</html>