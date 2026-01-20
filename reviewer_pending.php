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

// Fetch Pending Reviews
$query = "SELECT r.id as review_id, p.title, p.file_path, p.researcher_email, r.status, r.type, p.status as proposal_status 
          FROM reviews r 
          JOIN proposals p ON r.proposal_id = p.id 
          WHERE r.reviewer_id = ? AND r.status = 'Pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Reviews</title>
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
        
        <div class="welcome-text">Assigned Proposals</div>
        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 25px;">

        <div class="card-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
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
                <p style="color: #666;">No pending tasks.</p>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>