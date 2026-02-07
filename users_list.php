<?php
session_start();
require 'config.php';

// Security Check
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

// Fetch users
$users_q = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Users</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    
    <style>
        .tab-btn { padding: 12px 24px; cursor: pointer; border: none; background: #eee; font-size:15px; margin-right:2px; border-radius:5px 5px 0 0; transition: 0.3s; }
        .tab-btn:hover { background: #ddd; }
        .tab-btn.active { background: #3C5B6F; color: white; }
        .tab-content { display: none; padding: 25px; border: 1px solid #ddd; margin-top: -1px; border-radius: 0 5px 5px 5px; background: white; }
        .tab-content.active { display: block; }

        /* Table style */
        .styled-table { width:100%; border-collapse: collapse; margin-top: 10px; }
        .styled-table th, .styled-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align:left; font-size: 14px; }
        .styled-table th { background: #f8f9fa; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display:inline-block; }
        .badge-admin { background: #d1ecf1; color:#0c5460; }
        .badge-reviewer { background:#fff3cd; color:#856404; }
        .badge-researcher { background:#d4edda; color:#155724; }
        .badge-hod { background:#e2e3e5; color:#383d41; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<section class="home-section">
    <a href="<?= $dashboardLink ?>" class="btn-back" style="display: inline-flex; align-items: center; text-decoration: none; color: #3C5B6F; font-weight: 600; margin-bottom: 15px;">
            <i class='bx bx-left-arrow-alt' style="font-size: 20px; margin-right: 5px;"></i> 
            Back to Dashboard
        </a>
    <div class="welcome-text">
        <i class='bx bx-group' style="font-size:24px; vertical-align:middle;"></i>
        Admin Dashboard | Users
    </div>
    <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 25px;">
   


    <!-- Tab Navigation  -->
    <div style="margin-bottom: 0;">
        <button class="tab-btn active" onclick="openTab(event, 'allUsers')">
            <i class='bx bx-user'></i> All Users
        </button>
        <button class="tab-btn" onclick="openTab(event, 'admins')">
            <i class='bx bx-shield'></i> Admins
        </button>
        <button class="tab-btn" onclick="openTab(event, 'reviewers')">
            <i class='bx bx-check-shield'></i> Reviewers
        </button>
        <button class="tab-btn" onclick="openTab(event, 'researchers')">
            <i class='bx bx-book-reader'></i> Researchers
        </button>
    </div>

    <!-- TAB 1: ALL USERS -->
    <div id="allUsers" class="tab-content active">
        <h3 style="color:#3C5B6F; margin-top:0;"><i class='bx bx-list-ul'></i> All Users</h3>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users_q && $users_q->num_rows > 0): ?>
                    <?php
                    // reset pointer just in case
                    $users_q->data_seek(0);
                    while($u = $users_q->fetch_assoc()):
                        $role = strtolower($u['role'] ?? '');
                        $badgeClass = "badge";
                        if ($role === "admin") $badgeClass .= " badge-admin";
                        elseif ($role === "reviewer") $badgeClass .= " badge-reviewer";
                        elseif ($role === "researcher") $badgeClass .= " badge-researcher";
                        elseif ($role === "hod") $badgeClass .= " badge-hod";
                    ?>
                        <tr>
                            <td><?= (int)$u['id'] ?></td>
                            <td><?= htmlspecialchars($u['name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                            <td><span class="<?= $badgeClass ?>"><?= htmlspecialchars($u['role'] ?? '-') ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;color:#999;">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- TAB 2: ADMINS -->
    <div id="admins" class="tab-content">
        <h3 style="color:#3C5B6F; margin-top:0;"><i class='bx bx-shield'></i> Admins</h3>

        <table class="styled-table">
            <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>
            </thead>
            <tbody>
            <?php
            $has = false;
            if ($users_q) {
                $users_q->data_seek(0);
                while($u = $users_q->fetch_assoc()){
                    if (strtolower($u['role'] ?? '') === 'admin'){
                        $has = true;
                        echo "<tr>
                                <td>".(int)$u['id']."</td>
                                <td>".htmlspecialchars($u['name'] ?? '-')."</td>
                                <td>".htmlspecialchars($u['email'] ?? '-')."</td>
                                <td><span class='badge badge-admin'>Admin</span></td>
                              </tr>";
                    }
                }
            }
            if (!$has) echo "<tr><td colspan='4' style='text-align:center;color:#999;'>No admins found.</td></tr>";
            ?>
            </tbody>
        </table>
    </div>

    <!-- TAB 3: REVIEWERS -->
    <div id="reviewers" class="tab-content">
        <h3 style="color:#3C5B6F; margin-top:0;"><i class='bx bx-check-shield'></i> Reviewers</h3>

        <table class="styled-table">
            <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>
            </thead>
            <tbody>
            <?php
            $has = false;
            if ($users_q) {
                $users_q->data_seek(0);
                while($u = $users_q->fetch_assoc()){
                    if (strtolower($u['role'] ?? '') === 'reviewer'){
                        $has = true;
                        echo "<tr>
                                <td>".(int)$u['id']."</td>
                                <td>".htmlspecialchars($u['name'] ?? '-')."</td>
                                <td>".htmlspecialchars($u['email'] ?? '-')."</td>
                                <td><span class='badge badge-reviewer'>Reviewer</span></td>
                              </tr>";
                    }
                }
            }
            if (!$has) echo "<tr><td colspan='4' style='text-align:center;color:#999;'>No reviewers found.</td></tr>";
            ?>
            </tbody>
        </table>
    </div>

    <!-- TAB 4: RESEARCHERS -->
    <div id="researchers" class="tab-content">
        <h3 style="color:#3C5B6F; margin-top:0;"><i class='bx bx-book-reader'></i> Researchers</h3>

        <table class="styled-table">
            <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>
            </thead>
            <tbody>
            <?php
            $has = false;
            if ($users_q) {
                $users_q->data_seek(0);
                while($u = $users_q->fetch_assoc()){
                    if (strtolower($u['role'] ?? '') === 'researcher'){
                        $has = true;
                        echo "<tr>
                                <td>".(int)$u['id']."</td>
                                <td>".htmlspecialchars($u['name'] ?? '-')."</td>
                                <td>".htmlspecialchars($u['email'] ?? '-')."</td>
                                <td><span class='badge badge-researcher'>Researcher</span></td>
                              </tr>";
                    }
                }
            }
            if (!$has) echo "<tr><td colspan='4' style='text-align:center;color:#999;'>No researchers found.</td></tr>";
            ?>
            </tbody>
        </table>
    </div>

</section>

<script>

// Tab functionality
function openTab(evt, tabName) {
    var tabcontent = document.getElementsByClassName("tab-content");
    for (var i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    var tablinks = document.getElementsByClassName("tab-btn");
    for (var i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}
</script>

</body>
</html>
