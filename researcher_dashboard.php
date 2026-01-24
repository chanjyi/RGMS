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
$profile_pic_path = "images/" . $profile_pic;

// Fallback to a generic placeholder if file doesn't exist locally
if (!file_exists($profile_pic_path)) {
    // This ensures it still shows something nice if the image is missing
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
    
    <link rel="stylesheet" href="styling/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="styling/dashboard.css?v=<?= time(); ?>"> 
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
                    <span class="identity-role">Researcher</span>
                </div>
            </div>
        </div>

        <hr class="dashboard-divider">

        <div style="max-width: 800px;">
            
            <a href="researcher_page.php" class="header-card">
                <div class="header-left">
                    <div class="header-icon-box"><i class='bx bx-file'></i></div>
                    <div class="header-text-group">
                        <h3>My Research Management</h3>
                        <span>Manage proposals, grants & expenses</span>
                    </div>
                </div>
                <i class='bx bx-chevron-right header-arrow'></i>
            </a>

            <a href="researcher_analytics.php" class="header-card">
                <div class="header-left">
                    <div class="header-icon-box"><i class='bx bx-bar-chart-alt-2'></i></div>
                    <div class="header-text-group">
                        <h3>Analytics & Reports</h3>
                        <span>View grant statistics & budget usage</span>
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

    <script src="script.js"></script>
</body>
</html>