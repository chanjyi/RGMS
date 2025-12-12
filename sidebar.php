<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<!-- Toggle Button -->
<button class="toggle-btn" id="openSidebarBtn" onclick="openSidebar()">☰</button>


<!-- Sidebar -->
<aside id="sidebar" class="sidebar">
  <button class="close-btn" id="closeSidebarBtn" onclick="closeSidebar()">×</button>


  <h2 class="brand-title">My System</h2>

  <a href="admin.php" class="menu-item <?= $current=='admin.php'?'active':'' ?>">
    <i class='bx bx-home'></i> Home
  </a>

  <a href="admin.php" class="menu-item">
    <i class='bx bx-grid-alt'></i> Dashboard
  </a>

  <a href="#" class="menu-item">
    <i class='bx bx-cog'></i> Settings
  </a>

  <hr>

  <a href="logout.php" class="menu-item logout">
    <i class='bx bx-log-out'></i> Logout
  </a>
</aside>

<!-- Background Overlay -->
<div id="overlay" class="overlay"></div>
