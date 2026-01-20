<?php
// sidebar.php
// 1. Ensure Session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Ensure Database Connection exists (ADD THIS LINE)
require_once 'config.php';
$current = basename($_SERVER['PHP_SELF']);

// Determine the correct dashboard link based on the user's role
$dashboardLink = 'index.php'; // Default fallback

if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            $dashboardLink = 'admin_page.php';
            break;
        case 'reviewer':
            $dashboardLink = 'reviewer_page.php';
            break;
        case 'hod':
            $dashboardLink = 'hod_page.php';
            break;
        case 'researcher':
            $dashboardLink = 'researcher_page.php';
            break;
        default:
            $dashboardLink = 'index.php';
    }
}
$unread_count = 0;
if (isset($_SESSION['email']) && isset($conn)) {
    $n_stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_email = ? AND is_read = 0");
    $n_stmt->bind_param("s", $_SESSION['email']);
    $n_stmt->execute();
    $unread_count = $n_stmt->get_result()->fetch_assoc()['count'];
}
?>

<div class="sidebar" id="sidebar">
    <div class="logo-details">
        <i class='bx bx-menu' id="btn" onclick="toggleSidebar()"></i>
        <span class="logo_name">RGMS</span>
    </div>

    <ul class="nav-list">
        <li>
            <a href="<?= $dashboardLink ?>" class="<?= $current == $dashboardLink ? 'active' : '' ?>">
                <i class='bx bx-grid-alt'></i>
                <span class="links_name">Dashboard</span>
            </a>
            <span class="tooltip">Dashboard</span>
        </li>

        <li>
            <a href="researcher_analytics.php" class="<?= basename($_SERVER['PHP_SELF']) == 'researcher_analytics.php' ? 'active' : '' ?>">
                <i class='bx bx-bar-chart-alt-2'></i>
                <span class="links_name">Analytics</span>
            </a>
            <span class="tooltip">Analytics</span>
         </li>

        <li>
            <a href="profile.php" class="<?= $current == 'profile.php' ? 'active' : '' ?>">
                <i class='bx bx-user'></i>
                <span class="links_name">Profile</span>
            </a>
            <span class="tooltip">Profile</span>
        </li>

        <li>
            <a href="settings.php" class="<?= $current == 'settings.php' ? 'active' : '' ?>">
                <i class='bx bx-cog'></i>
                <span class="links_name">Settings</span>
            </a>
            <span class="tooltip">Settings</span>
        </li>

        <li>
            <a href="notifications.php" class="<?= $current == 'notifications.php' ? 'active' : '' ?>" style="position: relative;">
                <i class='bx bx-bell'></i>
                <span class="links_name">Notifications</span>
                
                <?php if ($unread_count > 0): ?>
                    <span class="notif-badge"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>
            <span class="tooltip">Notifications</span>
        </li>

        <li class="profile">
            <a href="logout.php">
                <i class='bx bx-log-out' id="log_out"></i>
                <span class="links_name">Logout</span>
            </a>
        </li>
    </ul>
</div>

<div id="overlay" onclick="toggleSidebar()" 
     style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998;">
</div>
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("overlay");
        
        // 1. Toggle the class 'open' on the sidebar
        sidebar.classList.toggle("open");
        
        // 2. Check if the sidebar is now open or closed
        if (sidebar.classList.contains("open")) {
            // If open, SHOW the overlay
            overlay.style.display = "block";
        } else {
            // If closed, HIDE the overlay
            overlay.style.display = "none";
        }
    }
</script>