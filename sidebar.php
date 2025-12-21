<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar" id="sidebar">
    <div class="logo-details">
        <i class='bx bx-menu' id="btn" onclick="toggleSidebar()"></i>
        <span class="logo_name">RGMS</span>
    </div>

    <ul class="nav-list">
        <li>
            <a href="dashboard.php" class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">
                <i class='bx bx-grid-alt'></i>
                <span class="links_name">Dashboard</span>
            </a>
            <span class="tooltip">Dashboard</span>
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
<script src="script.js"></script>