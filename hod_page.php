<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}

// FETCH RECOMMENDED PROPOSALS
// ORDER BY: Priority 'High' first, then by Date
$query = "SELECT p.*, r.feedback 
          FROM proposals p 
          JOIN reviews r ON p.id = r.proposal_id 
          WHERE p.status = 'RECOMMEND' 
          ORDER BY p.priority DESC, p.created_at ASC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HOD Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
        <div class="welcome-text">
            Welcome, HOD
        </div>
        <hr style="opacity: 0.3; margin: 20px 0;">

        <h2>Proposals Awaiting Final Approval</h2>
        
        <div class="card-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    
                    <?php 
                        $is_urgent = ($row['priority'] == 'High');
                        $border_color = $is_urgent ? '#e74c3c' : '#28a745'; 
                        $bg_color = $is_urgent ? '#fff5f5' : 'white';
                    ?>

                    <div class="card" style="width: 350px; border-left: 5px solid <?= $border_color ?>; background: <?= $bg_color ?>;">
                        
                        <?php if($is_urgent): ?>
                            <span style="background: #e74c3c; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; float: right; margin-top: -5px;">
                                <i class='bx bxs-flame'></i> URGENT
                            </span>
                        <?php endif; ?>

                        <h3 style="color: #153448; margin-top: 5px;"><?= htmlspecialchars($row['title']) ?></h3>
                        <p style="font-size: 13px; color: #666; margin-bottom:10px;">Researcher: <?= htmlspecialchars($row['researcher_email']) ?></p>
                        
                        <div style="background: rgba(0,0,0,0.05); padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; font-style: italic;">
                            "<?= htmlspecialchars(substr($row['feedback'], 0, 80)) ?>..."
                        </div>

                        <a href="hod_approve.php?id=<?= $row['id'] ?>" class="btn-primary btn-action" style="width: 100%; text-decoration: none;">
                           View & Finalize
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #666; font-style: italic;">No pending approvals.</p>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>