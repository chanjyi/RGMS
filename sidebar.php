<?php
// sidebar1.php (Merged Version)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
$current = basename($_SERVER['PHP_SELF']);

// Determine the correct dashboard link
$dashboardLink = 'index.php'; 
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin': $dashboardLink = 'admin_page.php'; break;
        case 'reviewer': $dashboardLink = 'reviewer_page.php'; break;
        case 'hod': $dashboardLink = 'hod_page.php'; break;
        case 'researcher': $dashboardLink = 'researcher_dashboard.php'; break; // Updated to match friend's file
    }
}

// Fetch Notification Count
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
            <a href="<?= $dashboardLink ?>" class="<?= $current == basename($dashboardLink) ? 'active' : '' ?>">
                <i class='bx bx-grid-alt'></i>
                <span class="links_name">Dashboard</span>
            </a>
            <span class="tooltip">Dashboard</span>
        </li>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'researcher'): ?>
            <li>
                <a href="researcher_page.php" class="<?= $current == 'researcher_page.php' ? 'active' : '' ?>">
                    <i class='bx bx-file'></i>
                    <span class="links_name">My Research</span>
                </a>
                <span class="tooltip">My Research</span>
            </li>
            <li>
                <a href="researcher_analytics.php" class="<?= $current == 'researcher_analytics.php' ? 'active' : '' ?>">
                    <i class='bx bx-bar-chart-alt-2'></i>
                    <span class="links_name">Analytics</span>
                </a>
                <span class="tooltip">Analytics</span>
            </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'reviewer'): ?>
            <li>
                <a href="reviewer_pending.php" class="<?= $current == 'reviewer_pending.php' ? 'active' : '' ?>">
                    <i class='bx bx-time-five'></i>
                    <span class="links_name">Pending Tasks</span>
                </a>
                <span class="tooltip">Pending Tasks</span>
            </li>
            <li>
                <a href="reviewer_stats.php" class="<?= $current == 'reviewer_stats.php' ? 'active' : '' ?>">
                    <i class='bx bx-pie-chart-alt-2'></i>
                    <span class="links_name">Performance</span>
                </a>
                <span class="tooltip">Performance</span>
            </li>
            <li>
                <a href="reviewer_history.php" class="<?= $current == 'reviewer_history.php' ? 'active' : '' ?>">
                    <i class='bx bx-history'></i>
                    <span class="links_name">History</span>
                </a>
                <span class="tooltip">History</span>
            </li>
            <li>
                <a href="reviewer_misconduct.php" class="<?= $current == 'reviewer_misconduct.php' ? 'active' : '' ?>">
                    <i class='bx bx-error-circle'></i>
                    <span class="links_name">Misconduct</span>
                </a>
                <span class="tooltip">Misconduct</span>
            </li>
        <?php endif; ?>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'hod'): ?>
            <li>
                <a href="hod_proposal_management.php" class="<?= $current == 'hod_proposal_management.php' ? 'active' : '' ?>">
                    <i class='bx bx-list-check'></i>
                    <span class="links_name">Proposal Management</span>
                </a>
                <span class="tooltip">Proposal Management</span>
            </li>
            <li>
                <a href="hod_research_tracking.php" class="<?= $current == 'hod_research_tracking.php' ? 'active' : '' ?>">
                    <i class='bx bx-bar-chart-alt-2'></i>
                    <span class="links_name">Research Progress</span>
                </a>
                <span class="tooltip">Research Progress</span>
            </li>
            <li>
                <a href="hod_appeal_cases.php" class="<?= $current == 'hod_appeal_cases.php' ? 'active' : '' ?>">
                    <i class='bx bx-message-square-dots'></i>
                    <span class="links_name">Appeal Cases</span>
                </a>
                <span class="tooltip">Appeal Cases</span>
            </li>
        <?php endif; ?>

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
        sidebar.classList.toggle("open");
        if (sidebar.classList.contains("open")) {
            overlay.style.display = "block";
        } else {
            overlay.style.display = "none";
        }
    }
</script>