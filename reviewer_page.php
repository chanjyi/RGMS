<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'reviewer') {
    header('Location: index.php');
    exit();
}

// 1. Get the Logged-in Reviewer's ID
$email = $_SESSION['email'];
$user_q = $conn->query("SELECT id FROM users WHERE email = '$email'");

if ($user_q->num_rows > 0) {
    $user_id = $user_q->fetch_assoc()['id'];
} else {
    die("Error: User not found in database.");
}

// 2. FETCH ASSIGNED PROPOSALS (Includes 'type' for Appeal logic)
$query = "SELECT r.id as review_id, p.title, p.file_path, p.researcher_email, r.status, r.type 
          FROM reviews r 
          JOIN proposals p ON r.proposal_id = p.id 
          WHERE r.reviewer_id = ? AND r.status = 'Pending'";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reviewer Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
        <div class="welcome-text">Reviewer Dashboard</div>
        <hr style="opacity: 0.3; margin: 20px 0;">

        <h2>Assigned Proposals (Pending)</h2>
        <div class="card-container" style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
            
            <?php if ($result->num_rows > 0): ?>
                
                <?php 
                // START OF THE LOOP
                while($row = $result->fetch_assoc()): 
                    // Logic to distinguish Appeals (Red vs Blue)
                    $is_appeal = (isset($row['type']) && $row['type'] == 'Appeal');
                    $card_style = $is_appeal ? "border-left: 5px solid #dc3545; background: #fff5f5;" : "border-left: 5px solid #3C5B6F;";
                    $badge = $is_appeal ? "<span style='color:white; background:#dc3545; padding:2px 6px; font-size:11px; border-radius:4px; margin-left:10px;'>APPEAL CASE</span>" : "";
                ?>

                    <div class="card" style="width: 300px; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); background: white; <?= $card_style ?>">
                        
                        <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom: 5px;">
                            <h3 style="color: #3C5B6F; font-size: 18px; margin: 0;"><?= htmlspecialchars($row['title']) ?></h3>
                        </div>
                        
                        <div style="margin-bottom: 10px;"><?= $badge ?></div>

                        <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                            Researcher: <?= htmlspecialchars($row['researcher_email']) ?>
                        </p>
                        
                        <p style="margin-bottom: 15px; font-size: 14px;">Status: <strong>Pending Review</strong></p>
                        
                        <a href="review_proposal.php?review_id=<?= $row['review_id'] ?>" 
                           style="display: block; text-align: center; background: #3C5B6F; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">
                           Open & Review
                        </a>
                    </div>

                <?php 
                // END OF THE LOOP
                endwhile; 
                ?>

            <?php else: ?>
                <p>No pending reviews found assigned to you.</p>
            <?php endif; ?>
            
        </div>
    </section>
</body>
</html>