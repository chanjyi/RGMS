<?php
session_start();
require 'config.php';
require 'activity_helper.php';


if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}

$query = "SELECT p.*, r.feedback 
          FROM proposals p 
          JOIN reviews r ON p.id = r.proposal_id 
          WHERE p.status IN ('RECOMMEND', 'APPEALED') 
          ORDER BY p.priority DESC, p.created_at ASC";

$result = $conn->query($query);
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
        <div class="welcome-text">
            Welcome, HOD
        </div>
        <hr style="opacity: 0.3; margin: 20px 0;">

        <h2>Proposals Awaiting Action</h2>
        
        <div class="card-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    
                    <?php 
                        // ==========================================
                        // VISUAL LOGIC: APPEAL vs NORMAL
                        // ==========================================
                        $is_appeal = ($row['status'] == 'APPEALED');

                        if ($is_appeal) {
                            // RED STYLE FOR APPEALS
                            $border_color = '#dc3545'; // Red
                            $bg_color = '#fff5f5';     // Light Pink
                            $badge_text = "APPEAL CASE";
                            $badge_class = 'bx-error-circle';
                            $badge_bg = '#dc3545';
                            $btn_text = "Review Appeal Request";
                            $btn_color = "#dc3545"; // Red Button
                        } else {
                            // GREEN/ORANGE STYLE FOR NORMAL RECOMMENDATIONS
                            $is_urgent = ($row['priority'] == 'High');
                            $border_color = $is_urgent ? '#e74c3c' : '#28a745'; 
                            $bg_color = 'white';
                            $badge_text = $is_urgent ? "URGENT" : "RECOMMENDED";
                            $badge_class = $is_urgent ? 'bxs-flame' : 'bx-check-circle';
                            $badge_bg = $is_urgent ? '#e74c3c' : '#28a745';
                            $btn_text = "View & Finalize";
                            $btn_color = "#3C5B6F"; // Default Blue Button
                        }
                    ?>

                    <div class="card" style="width: 350px; border-left: 5px solid <?= $border_color ?>; background: <?= $bg_color ?>;">
                        
                        <span style="background: <?= $badge_bg ?>; color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; float: right; margin-top: -5px; display: flex; align-items: center; gap: 5px;">
                            <i class='bx <?= $badge_class ?>'></i> <?= $badge_text ?>
                        </span>

                        <h3 style="color: #153448; margin-top: 5px; clear: both;"><?= htmlspecialchars($row['title']) ?></h3>
                        <p style="font-size: 13px; color: #666; margin-bottom:10px;">Researcher: <?= htmlspecialchars($row['researcher_email']) ?></p>
                        
                        <div style="background: rgba(0,0,0,0.05); padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; font-style: italic;">
                            "<?= htmlspecialchars(substr($row['feedback'], 0, 80)) ?>..."
                        </div>

                        <a href="hod_approve.php?id=<?= $row['id'] ?>" class="btn-action" style="width: 100%; text-decoration: none; background-color: <?= $btn_color ?>; color: white; display: block; text-align: center; padding: 10px; border-radius: 5px;">
                           <?= $btn_text ?>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; width: 100%; color: #666; margin-top: 20px;">
                    <i class='bx bx-folder-open' style="font-size: 40px; color: #ccc;"></i>
                    <p style="font-style: italic;">No pending proposals or appeals.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>