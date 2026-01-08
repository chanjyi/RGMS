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

// 1. FETCH DATA (Charts & Stats)
$p_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE reviewer_id = ? AND status = 'Pending'");
$p_stmt->bind_param("i", $user_id);
$p_stmt->execute();
$pending_count = $p_stmt->get_result()->fetch_assoc()['count'];

$c_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE reviewer_id = ? AND status = 'Completed'");
$c_stmt->bind_param("i", $user_id);
$c_stmt->execute();
$completed_count = $c_stmt->get_result()->fetch_assoc()['count'];

$hod_app_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews r JOIN proposals p ON r.proposal_id = p.id WHERE r.reviewer_id = ? AND r.decision = 'RECOMMEND' AND p.status = 'APPROVED'");
$hod_app_stmt->bind_param("i", $user_id);
$hod_app_stmt->execute();
$hod_approved = $hod_app_stmt->get_result()->fetch_assoc()['count'];

$hod_rej_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews r JOIN proposals p ON r.proposal_id = p.id WHERE r.reviewer_id = ? AND r.decision = 'RECOMMEND' AND (p.status = 'REJECTED' OR p.status = 'APPEAL_REJECTED')");
$hod_rej_stmt->bind_param("i", $user_id);
$hod_rej_stmt->execute();
$hod_rejected = $hod_rej_stmt->get_result()->fetch_assoc()['count'];

// 2. FETCH PENDING LIST
$query = "SELECT r.id as review_id, p.title, p.file_path, p.researcher_email, r.status, r.type, p.status as proposal_status 
          FROM reviews r 
          JOIN proposals p ON r.proposal_id = p.id 
          WHERE r.reviewer_id = ? AND r.status = 'Pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// 3a. FETCH COMPLETED REVIEWS (Normal History)
$hist_query = "SELECT r.decision, r.feedback, r.status as review_status, r.review_date, r.annotated_file,
                      p.title, p.researcher_email, p.status as final_status, p.file_path
               FROM reviews r 
               JOIN proposals p ON r.proposal_id = p.id 
               WHERE r.reviewer_id = ? AND r.status = 'Completed'
               ORDER BY r.review_date DESC"; 
$hist_stmt = $conn->prepare($hist_query);
$hist_stmt->bind_param("i", $user_id);
$hist_stmt->execute();
$history = $hist_stmt->get_result();

// 3b. FETCH REPORTED CASES (Misconduct Table)
$rep_query = "SELECT m.created_at as review_date, p.title, p.researcher_email, m.category, m.details
              FROM misconduct_reports m 
              JOIN proposals p ON m.proposal_id = p.id 
              WHERE m.reviewer_email = ? 
              ORDER BY m.created_at DESC";

$rep_stmt = $conn->prepare($rep_query);
// Note: misconduct_reports uses email, so we bind "s" (string) and use $email instead of $user_id
$rep_stmt->bind_param("s", $email); 
$rep_stmt->execute();
$reports = $rep_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reviewer Dashboard</title>
    <link rel="stylesheet" href="style.css">
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
        <div class="welcome-text">
            Reviewer Dashboard 
        </div>
        <hr style="opacity: 0.3; margin: 20px 0;">

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

        <h2>Assigned Proposals (Pending)</h2>
        <div class="card-container" style="margin-bottom: 50px;">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    // FIX: Strict check. Only show red if DB Type is Appeal OR Status is in appeal mode.
                    $is_appeal = ($row['type'] == 'Appeal') || ($row['proposal_status'] == 'APPEALED');
                    
                    $card_style = $is_appeal ? "border-left: 5px solid #dc3545; background: #fff5f5;" : "border-left: 5px solid #3C5B6F;";
                    $badge = $is_appeal ? "<span style='color:white; background:#dc3545; padding:2px 6px; font-size:11px; border-radius:4px; margin-left:10px;'>APPEAL CASE</span>" : "";
                ?>   
                    <div class="card" style="width: 300px; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); background: white; <?= $card_style ?>">
                        <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom: 5px;">
                            <h3><?= htmlspecialchars($row['title']) ?></h3>
                        </div>
                        <div style="margin-bottom: 10px;"><?= $badge ?></div>
                        <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                            Researcher: <?= htmlspecialchars($row['researcher_email']) ?>
                        </p>
                        <a href="review_proposal.php?review_id=<?= $row['review_id'] ?>" 
                           style="display: block; text-align: center; background: #3C5B6F; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                           Open & Review
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #666; font-style: italic;">No pending reviews found.</p>
            <?php endif; ?>
        </div>

        <h2>My Review History</h2>
        <div style="overflow-x:auto;">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>My Decision</th>
                        <th>HOD Final Status</th>
                        <th>My Feedback</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history->num_rows > 0): ?>
                        <?php while($row = $history->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['title']) ?></strong><br>
                                    <span style="font-size: 12px; color: #888;"><?= htmlspecialchars($row['researcher_email']) ?></span>
                                    <br>
                                    <a href="<?= $row['file_path'] ?>" target="_blank" style="font-size: 11px; color: #3C5B6F;">View PDF</a>
                                </td>

                                <td>
                                    <?php if($row['review_status'] == 'Reported'): ?>
                                        <span class="status-badge rejected" style="background:#000; color:#fff;">Reported</span>
                                    <?php elseif($row['decision'] == 'AMENDMENT'): ?>
                                        <span class="status-badge requires_amendment">REQUESTED AMENDMENT</span>
                                    <?php else: ?>
                                        <span class="status-badge <?= $row['decision'] == 'RECOMMEND' ? 'approved' : 'rejected' ?>">
                                            <?= $row['decision'] ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php 
                                    // 1. If Reported -> "Under Investigation"
                                    if($row['review_status'] == 'Reported'): ?>
                                        <span style="color: #888;">Under Investigation</span>
                                    
                                    <?php 
                                    // 2. If Reviewer Rejected OR Requested Amendment -> SHOW NOTHING
                                    // (Added the check for 'REJECT' here)
                                    elseif($row['decision'] == 'AMENDMENT' || $row['decision'] == 'REJECT'): ?>
                                        <span></span> 

                                    <?php 
                                    // 3. If Pending HOD -> "Pending"
                                    elseif ($row['final_status'] == 'RECOMMEND'): ?>
                                        <span class="status-badge assigned">Pending</span>

                                    <?php 
                                    // 4. Otherwise -> Approved/Rejected (HOD Decision)
                                    else: ?>
                                        <?php $badge_class = strtolower($row['final_status']); ?>
                                        <span class="status-badge <?= $badge_class ?>">
                                            <?= str_replace('_', ' ', $row['final_status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div style="background: #f9f9f9; padding: 10px; border-radius: 5px; border: 1px solid #eee; font-size: 13px; max-height: 80px; overflow-y: auto;">
                                        <?= nl2br(htmlspecialchars($row['feedback'])) ?>
                                    </div>

                                    <?php if (!empty($row['annotated_file'])): ?>
                                        <div style="margin-top: 8px;">
                                            <a href="<?= htmlspecialchars($row['annotated_file']) ?>" target="_blank" 
                                            style="font-size: 11px; color: #3C5B6F; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 3px;">
                                            <i class='bx bx-paperclip'></i> View Annotated PDF
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td style="font-size: 13px;">
                                    <?= date('M d, Y', strtotime($row['review_date'])) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: #777;">No history yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 style="color: #dc3545; margin-top: 40px;">Reported Misconduct Cases</h2>
        <div style="overflow-x:auto; margin-bottom: 40px;">
            <table class="styled-table" style="border-top: 5px solid #dc3545;">
                <thead>
                    <tr style="background: #dc3545; color: white;">
                        <th>Proposal</th>
                        <th>Researcher</th>
                        <th>Violation Category</th>
                        <th>Report Details</th>
                        <th>Date Reported</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reports->num_rows > 0): ?>
                        <?php while($row = $reports->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                                <td><?= htmlspecialchars($row['researcher_email']) ?></td>
                                <td>
                                    <span class="status-badge rejected" style="background:#000; color:#fff;">
                                        <?= htmlspecialchars($row['category']) ?>
                                    </span>
                                </td>
                                <td><?= nl2br(htmlspecialchars($row['details'])) ?></td>
                                <td><?= date('M d, Y', strtotime($row['review_date'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666; font-style: italic;">
                                No reported misconduct cases.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>
</body>
</html>