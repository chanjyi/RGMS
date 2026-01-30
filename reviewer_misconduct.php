<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'reviewer') {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email'];

// Fetch Reports
$rep_query = "SELECT m.created_at as review_date, p.title, p.researcher_email, m.category, m.details
              FROM misconduct_reports m 
              JOIN proposals p ON m.proposal_id = p.id 
              WHERE m.reviewer_email = ? 
              ORDER BY m.created_at DESC";

$rep_stmt = $conn->prepare($rep_query);
$rep_stmt->bind_param("s", $email); 
$rep_stmt->execute();
$reports = $rep_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Misconduct Cases</title>
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

        <div class="welcome-text" style="color: #dc3545;">Reported Misconduct Cases</div>
        <hr style="border: 1px solid #dc3545; opacity: 0.3; margin-bottom: 25px;">

        <div style="overflow-x:auto;">
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
                        <tr><td colspan="5" style="text-align: center; color: #666;">No reported misconduct cases.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</body>
</html>