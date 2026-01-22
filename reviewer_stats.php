<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'reviewer') {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email'];
$user_q = $conn->query("SELECT id FROM users WHERE email = '$email'");
$user_id = $user_q->fetch_assoc()['id'];

// 1. FETCH STATS FOR CHARTS
// Pending Count
$p_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE reviewer_id = ? AND status = 'Pending'");
$p_stmt->bind_param("i", $user_id);
$p_stmt->execute();
$pending_count = $p_stmt->get_result()->fetch_assoc()['count'];

// Completed Count
$c_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE reviewer_id = ? AND status = 'Completed'");
$c_stmt->bind_param("i", $user_id);
$c_stmt->execute();
$completed_count = $c_stmt->get_result()->fetch_assoc()['count'];

// HOD Approved (Agreement)
$hod_app_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews r JOIN proposals p ON r.proposal_id = p.id WHERE r.reviewer_id = ? AND r.decision = 'RECOMMEND' AND p.status = 'APPROVED'");
$hod_app_stmt->bind_param("i", $user_id);
$hod_app_stmt->execute();
$hod_approved = $hod_app_stmt->get_result()->fetch_assoc()['count'];

// HOD Rejected (Disagreement)
$hod_rej_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews r JOIN proposals p ON r.proposal_id = p.id WHERE r.reviewer_id = ? AND r.decision = 'RECOMMEND' AND (p.status = 'REJECTED' OR p.status = 'APPEAL_REJECTED')");
$hod_rej_stmt->bind_param("i", $user_id);
$hod_rej_stmt->execute();
$hod_rejected = $hod_rej_stmt->get_result()->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Performance Stats</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawWorkChart);
      google.charts.setOnLoadCallback(drawHODChart);

      function drawWorkChart() {
        var data = google.visualization.arrayToDataTable([
          ['Task', 'Count'],
          ['Pending',   <?= $pending_count ?>],
          ['Completed', <?= $completed_count ?>]
        ]);
        var options = {
          title: 'My Work Progress',
          pieHole: 0.4,
          colors: ['#dc3545', '#28a745'],
          backgroundColor: 'transparent',
          legend: {position: 'bottom'}
        };
        var chart = new google.visualization.PieChart(document.getElementById('work_chart'));
        chart.draw(data, options);
      }

      function drawHODChart() {
        var data = google.visualization.arrayToDataTable([
          ['Outcome', 'Count'],
          ['HOD Approved', <?= $hod_approved ?>],
          ['HOD Rejected', <?= $hod_rejected ?>]
        ]);
        var options = {
          title: 'HOD Agreement Rate',
          pieHole: 0.4,
          colors: ['#3C5B6F', '#e74c3c'],
          backgroundColor: 'transparent',
          legend: {position: 'bottom'}
        };
        var chart = new google.visualization.PieChart(document.getElementById('hod_chart'));
        chart.draw(data, options);
      }
    </script>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
        <div style="margin-bottom: 20px;">
            <a href="reviewer_page.php" class="btn-back"><i class='bx bx-arrow-back'></i> Back to Dashboard</a>
        </div>
        
        <div class="welcome-text">Performance Charts</div>
        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 25px;">

        <div style="display: flex; gap: 20px; margin-bottom: 30px;">
            <div class="form-box" style="flex: 2; padding: 0; height: 320px;">
                <div id="work_chart" style="width: 100%; height: 100%;"></div>
            </div>
            
            <div style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
                <div class="form-box stat-card completed" style="flex: 1;">
                    <div><span>Completed</span><h3><?= $completed_count ?></h3></div>
                    <i class='bx bx-check-circle'></i>
                </div>
                <div class="form-box stat-card pending" style="flex: 1;">
                    <div><span>Pending</span><h3><?= $pending_count ?></h3></div>
                    <i class='bx bx-time'></i>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 20px; margin-bottom: 40px;">
            <div class="form-box" style="flex: 2; padding: 0; height: 320px;">
                <div id="hod_chart" style="width: 100%; height: 100%;"></div>
            </div>
            
            <div style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
                <div class="form-box stat-card approved" style="flex: 1;">
                    <div><span>HOD Approved</span><h3><?= $hod_approved ?></h3></div>
                    <i class='bx bx-like'></i>
                </div>
                <div class="form-box stat-card rejected" style="flex: 1;">
                    <div><span>HOD Disagreed</span><h3><?= $hod_rejected ?></h3></div>
                    <i class='bx bx-dislike'></i>
                </div>
            </div>
        </div>

    </section>
</body>
</html>