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

// Fetch History
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review History</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
        <div style="margin-bottom: 20px;">
            <a href="reviewer_page.php" class="btn-back"><i class='bx bx-arrow-back'></i> Back to Dashboard</a>
        </div>
        
        <div class="welcome-text">My Review History</div>
        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 25px;">

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
                                    if($row['review_status'] == 'Reported'): ?>
                                        <span style="color: #888;">Under Investigation</span>
                                    <?php elseif($row['decision'] == 'AMENDMENT' || $row['decision'] == 'REJECT'): ?>
                                        <span></span> 
                                    <?php elseif ($row['final_status'] == 'RECOMMEND'): ?>
                                        <span class="status-badge assigned">Pending</span>
                                    <?php else: ?>
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
    </section>
</body>
</html>