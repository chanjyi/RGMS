<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}

$prop_id = $_GET['id'];

// Fetch Proposal + Review Details
$query = "SELECT p.*, r.feedback, r.annotated_file, u.name as reviewer_name 
          FROM proposals p 
          JOIN reviews r ON p.id = r.proposal_id 
          JOIN users u ON r.reviewer_id = u.id
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $prop_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// HANDLE APPROVAL / REJECTION
if (isset($_POST['final_decision'])) {
    $decision = $_POST['final_decision']; // 'APPROVED' or 'REJECTED'
    
    // 1. Update Proposal Status
    $upd = $conn->prepare("UPDATE proposals SET status = ? WHERE id = ?");
    $upd->bind_param("si", $decision, $prop_id);
    
    if ($upd->execute()) {
        // 2. Notify Researcher
        $msg = "Final Decision: Your proposal '{$data['title']}' has been $decision by the Head of Department.";
        $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
        $notif->bind_param("ss", $data['researcher_email'], $msg);
        $notif->execute();

        header("Location: hod_page.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HOD Approval</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section" style="padding: 20px;">
        <a href="hod_page.php" class="btn-back" style="text-decoration:none; color:#333;">&larr; Back</a>
        
        <h1>Final Approval: <?= htmlspecialchars($data['title']) ?></h1>

        <div style="display: flex; gap: 20px;">
            <div style="flex: 2;">
                <h3>Proposal Document</h3>
                <iframe src="<?= $data['file_path'] ?>" style="width:100%; height:500px; border:1px solid #ccc;"></iframe>
            </div>
            
            <div style="flex: 1; background: white; padding: 20px; border-radius: 10px; height: fit-content;">
                <h3 style="margin-top:0; color: #3C5B6F;">Reviewer Feedback</h3>
                <p><strong>Reviewer:</strong> <?= htmlspecialchars($data['reviewer_name']) ?></p>
                
                <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <?= nl2br(htmlspecialchars($data['feedback'])) ?>
                </div>

                <?php if($data['annotated_file']): ?>
                    <a href="<?= $data['annotated_file'] ?>" target="_blank" style="display:block; margin-bottom:20px; color: blue;">
                        View Annotated PDF
                    </a>
                <?php endif; ?>

                <hr>
                
                <form method="POST">
                    <label style="display:block; margin-bottom:10px; font-weight:bold;">HOD Action:</label>
                    <button type="submit" name="final_decision" value="APPROVED" 
                        style="width:100%; padding:10px; background:#28a745; color:white; border:none; margin-bottom:10px; cursor:pointer;">
                        Final Approve
                    </button>
                    <button type="submit" name="final_decision" value="REJECTED" 
                        style="width:100%; padding:10px; background:#dc3545; color:white; border:none; cursor:pointer;">
                        Reject
                    </button>
                </form>
            </div>
        </div>
    </section>
</body>
</html>