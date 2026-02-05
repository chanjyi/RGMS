<?php
session_start();
require 'config.php';
require 'activity_helper.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}

// Fetch HOD profile info
$hod_stmt = $conn->prepare("SELECT id, name, profile_pic, department_id FROM users WHERE email = ? AND role = 'hod' LIMIT 1");
$hod_stmt->bind_param("s", $_SESSION['email']);
$hod_stmt->execute();
$hod = $hod_stmt->get_result()->fetch_assoc();

$hod_name = $hod['name'] ?? 'HOD';
$department_id = $hod['department_id'] ?? null;
$profile_pic = !empty($hod['profile_pic']) ? 'images/' . $hod['profile_pic'] : 'images/default.png';
if (!file_exists($profile_pic)) {
    $profile_pic = 'https://ui-avatars.com/api/?name=' . urlencode($hod_name) . '&background=3C5B6F&color=fff';
}

// Lightweight counts for dashboard cards
$recommended_res = $conn->query(
        "SELECT COUNT(DISTINCT p.id) AS c
         FROM proposals p
         WHERE EXISTS (
                 SELECT 1 FROM reviews r
                 WHERE r.proposal_id = p.id
                     AND r.decision = 'RECOMMEND'
                     AND (r.type IS NULL OR r.type = 'Proposal')
         )
             AND (p.status IS NULL OR p.status = '')"
);
$recommended_count = $recommended_res ? ($recommended_res->fetch_assoc()['c'] ?? 0) : 0;

$active_res = $conn->query("SELECT COUNT(*) AS c FROM proposals WHERE status = 'APPROVED'");
$active_research_count = $active_res ? ($active_res->fetch_assoc()['c'] ?? 0) : 0;

$dept_projects_count = 0;
if ($department_id) {
    $dept_proj_res = $conn->prepare("SELECT COUNT(*) AS c FROM proposals WHERE department_id = ? AND status IN ('APPROVED','COMPLETED','ARCHIVED','TERMINATED')");
    $dept_proj_res->bind_param("i", $department_id);
    $dept_proj_res->execute();
    $dept_projects_count = $dept_proj_res->get_result()->fetch_assoc()['c'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HOD Dashboard</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
        <div class="dashboard-header-group">
            <h1 class="main-title">HOD dashboard | <?= htmlspecialchars($hod_name) ?></h1>
            <div class="user-identity-row">
                <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile" class="identity-img">
                <div class="identity-text">
                    <div class="identity-name">Welcome, <?= htmlspecialchars($hod_name) ?></div>
                    <div class="identity-role">Head of Department</div>
                </div>
            </div>
        </div>

        <hr class="dashboard-divider">

        <div style="max-width: 820px;">
            <a href="hod_proposal_management.php" class="header-card">
                <div class="header-left">
                    <div class="header-icon-box"><i class='bx bx-slider-alt'></i></div>
                    <div class="header-text-group">
                        <h3>Proposal management & prioritization</h3>
                        <span><?= $recommended_count ?> items waiting for your decision</span>
                    </div>
                </div>
                <i class='bx bx-chevron-right header-arrow'></i>
            </a>

            <a href="hod_research_tracking.php" class="header-card">
                <div class="header-left">
                    <div class="header-icon-box"><i class='bx bx-bar-chart-alt-2'></i></div>
                    <div class="header-text-group">
                        <h3>Research progress tracking</h3>
                        <span><?= $active_research_count ?> active projects to monitor</span>
                    </div>
                </div>
                <i class='bx bx-chevron-right header-arrow'></i>
            </a>

            <a href="hod_department_reports.php" class="header-card">
                <div class="header-left">
                    <div class="header-icon-box"><i class='bx bx-spreadsheet'></i></div>
                    <div class="header-text-group">
                        <h3>Department reports & dashboards</h3>
                        <span><?= $dept_projects_count ?> tracked research projects</span>
                    </div>
                </div>
                <i class='bx bx-chevron-right header-arrow'></i>
            </a>

            <a href="profile.php" class="header-card">
                <div class="header-left">
                    <div class="header-icon-box"><i class='bx bx-user'></i></div>
                    <div class="header-text-group">
                        <h3>Profile & settings</h3>
                        <span>Update your account and notification preferences</span>
                    </div>
                </div>
                <i class='bx bx-chevron-right header-arrow'></i>
            </a>
        </div>
    </section>
</body>
</html>