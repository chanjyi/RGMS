<?php
session_start();
require 'config.php';
require 'activity_helper.php';

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

// HANDLE HOD ACTIONS
if (isset($_POST['final_decision'])) {
    $decision = $_POST['final_decision']; // Values: APPROVED, REJECTED, or GRANT_APPEAL
    
    // CASE 1: HOD Grants the Appeal (Sends to Admin for Reassignment)
    if ($decision == 'GRANT_APPEAL') {
        // Update status so Admin can see it in "Pending Reassignment" list
        $stmt = $conn->prepare("UPDATE proposals SET status = 'PENDING_REASSIGNMENT' WHERE id = ?");
        $stmt->bind_param("i", $prop_id);
        
        if ($stmt->execute()) {
            // Notify Researcher
            $msg = "Appeal Update: The HOD has accepted your appeal. Your proposal will be reassigned to a new reviewer.";
            $conn->query("INSERT INTO notifications (user_email, message) VALUES ('{$data['researcher_email']}', '$msg')");
            
           log_activity(
            $conn,
            "GRANT_APPEAL",
            "PROPOSAL",
            (int)$prop_id,
            "Grant Appeal",
            "HOD accepted appeal for proposal #$prop_id ({$data['title']}) and set status=PENDING_REASSIGNMENT"
        );


            header("Location: hod_page.php");
            exit();
        }
    }
    // CASE 2: Normal Approve/Reject
    else {
        // Update Proposal Status
        $upd = $conn->prepare("UPDATE proposals SET status = ? WHERE id = ?");
        $upd->bind_param("si", $decision, $prop_id);
        
        if ($upd->execute()) {
            // A. Notify Researcher
            $msg = "Final Decision: Your proposal '{$data['title']}' has been $decision by the Head of Department.";
            $conn->query("INSERT INTO notifications (user_email, message) VALUES ('{$data['researcher_email']}', '$msg')");

            // B. Notify Reviewer (Respecting Settings)
            $rev_stmt = $conn->prepare("SELECT u.email, u.notify_hod_approve, u.notify_hod_reject FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.proposal_id = ?");
            $rev_stmt->bind_param("i", $prop_id);
            $rev_stmt->execute();
            $reviewer = $rev_stmt->get_result()->fetch_assoc();

            log_activity(
                $conn,
                "FINAL_DECISION",
                "PROPOSAL",
                (int)$prop_id,
                "Final Decision",
                "HOD set final decision=$decision for proposal #$prop_id ({$data['title']})"
            );


            if ($reviewer) {
                $rev_msg = "";
                // Check if HOD Approved AND Reviewer wants to know
                if ($decision == 'APPROVED' && $reviewer['notify_hod_approve'] == 1) {
                    $rev_msg = "Great news! The HOD has APPROVED the proposal you reviewed.";
                } 
                // Check if HOD Rejected AND Reviewer wants to know
                elseif ($decision == 'REJECTED' && $reviewer['notify_hod_reject'] == 1) {
                    $rev_msg = "Update: The HOD has REJECTED the proposal you reviewed.";
                }

                if (!empty($rev_msg)) {
                    $conn->query("INSERT INTO notifications (user_email, message) VALUES ('{$reviewer['email']}', '$rev_msg')");
                }
            }

            header("Location: hod_page.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HOD Approval</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styling/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section" style="padding: 20px;">
        <a href="hod_page.php" class="btn-back" style="text-decoration:none; color:#333;">&larr; Back</a>
        
        <h1>Final Approval: <?= htmlspecialchars($data['title']) ?></h1>

        <div style="display: flex; gap: 20px;">
            <div style="flex: 2;">
                <h3>Proposal Document</h3>
                <iframe src="<?= $data['file_path'] ?>#toolbar=0" style="width:100%; height:500px; border:1px solid #ccc;"></iframe>            </div>
            
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

                    <?php if ($data['status'] == 'APPEALED'): ?>
                        <p style="color: #d9534f; font-size: 14px; margin-bottom: 10px;">
                            <i class='bx bx-error-circle'></i> This is an Appeal Case.
                        </p>
                        <button type="submit" name="final_decision" value="GRANT_APPEAL" 
                            style="width:100%; padding:10px; background:#f39c12; color:white; border:none; margin-bottom:10px; cursor:pointer;">
                            Accept Appeal (Send to Admin)
                        </button>
                        <button type="submit" name="final_decision" value="REJECTED" 
                            style="width:100%; padding:10px; background:#dc3545; color:white; border:none; cursor:pointer;">
                            Dismiss Appeal (Final Reject)
                        </button>

                    <?php else: ?>
                        <button type="submit" name="final_decision" value="APPROVED" 
                            style="width:100%; padding:10px; background:#28a745; color:white; border:none; margin-bottom:10px; cursor:pointer;">
                            Final Approve
                        </button>
                        <button type="submit" name="final_decision" value="REJECTED" 
                            style="width:100%; padding:10px; background:#dc3545; color:white; border:none; cursor:pointer;">
                            Reject
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </section>
</body>
</html>