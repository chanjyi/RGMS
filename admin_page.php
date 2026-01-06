<?php
session_start();
require 'config.php';

// 1. Security Check
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

$message = "";

// ==========================================
// 2. LOGIC: ASSIGN NEW PROPOSAL
// ==========================================
if (isset($_POST['assign_proposal'])) {
    $prop_id = $_POST['proposal_id'];
    $reviewer_id = $_POST['reviewer_id'];

    // Insert into REVIEWS table
    $stmt = $conn->prepare("INSERT INTO reviews (proposal_id, reviewer_id, status, assigned_date, type) VALUES (?, ?, 'Pending', NOW(), 'Proposal')");
    $stmt->bind_param("ii", $prop_id, $reviewer_id);
    
    if ($stmt->execute()) {
        $conn->query("UPDATE proposals SET status = 'ASSIGNED' WHERE id = $prop_id");
        
        // Notify Reviewer
        $rev_q = $conn->query("SELECT email FROM users WHERE id = $reviewer_id");
        $rev_email = $rev_q->fetch_assoc()['email'];
        $conn->query("INSERT INTO notifications (user_email, message) VALUES ('$rev_email', 'New Assignment: You have been assigned a proposal.')");

        $message = "Proposal assigned successfully!";
    } else {
        $message = "Error assigning proposal.";
    }
}

// ==========================================
// 3. LOGIC: ASSIGN APPEAL CASE (ROBUST FIX)
// ==========================================
if (isset($_POST['assign_appeal'])) {
    $prop_id = $_POST['proposal_id'];
    $reviewer_id = $_POST['reviewer_id'];

    // 1. VALIDATION: Check History using store_result() (Compatible with all PHP versions)
    $check_hist = $conn->prepare("SELECT id FROM reviews WHERE proposal_id = ? AND reviewer_id = ?");
    $check_hist->bind_param("ii", $prop_id, $reviewer_id);
    
    if (!$check_hist->execute()) {
        // Debugging: Show SQL error if query fails
        $message = "Database Error (Check): " . $check_hist->error;
    } else {
        $check_hist->store_result(); // Store result to check num_rows safely

        if ($check_hist->num_rows > 0) {
            // 2. BLOCK ASSIGNMENT: History found
            $message = "Error: This reviewer has already reviewed this proposal. Please choose someone else.";
            $msg_type = "error";
        } else {
            // 3. ALLOW ASSIGNMENT: No history found
            $check_hist->close(); // Close previous statement

            $stmt = $conn->prepare("INSERT INTO reviews (proposal_id, reviewer_id, status, assigned_date, type) VALUES (?, ?, 'Pending', NOW(), 'Appeal')");
            $stmt->bind_param("ii", $prop_id, $reviewer_id);
            
            if ($stmt->execute()) {
                // Update Proposal Status & Priority
                $conn->query("UPDATE proposals SET status = 'ASSIGNED', priority = 'High' WHERE id = $prop_id");

                // Notify Reviewer
                $rev_q = $conn->query("SELECT email FROM users WHERE id = $reviewer_id");
                if ($rev_q && $rev_q->num_rows > 0) {
                    $rev_email = $rev_q->fetch_assoc()['email'];
                    $msg = "Appeal Case: You have been assigned to review proposal #$prop_id.";
                    $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
                    $notif->bind_param("ss", $rev_email, $msg);
                    $notif->execute();
                }

                $message = "Appeal assigned successfully!";
                $msg_type = "success"; //
            } else {
                $message = "Database Error (Assign): " . $stmt->error;
                $msg_type = "error"; //
            }
        }
    }
}

// ==========================================
// 4. FETCH DATA FOR DROPDOWNS
// ==========================================
$reviewers = $conn->query("SELECT * FROM users WHERE role = 'Reviewer' ORDER BY name ASC");
$new_proposals = $conn->query("SELECT * FROM proposals WHERE status = 'SUBMITTED'");
$appeals = $conn->query("SELECT * FROM proposals WHERE status = 'PENDING_REASSIGNMENT'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        
        <div class="welcome-text">Admin Dashboard</div>
        <hr style="opacity: 0.3; margin: 20px 0;">

        <?php if ($message): ?>
            <?php 
                // Determine colors based on type
                // If it's an error: RED background (#f8d7da), RED text (#721c24)
                // If it's success: GREEN background (#d4edda), GREEN text (#155724)
                $bg_color = (isset($msg_type) && $msg_type == 'error') ? '#f8d7da' : '#d4edda';
                $text_color = (isset($msg_type) && $msg_type == 'error') ? '#721c24' : '#155724';
                $border_color = (isset($msg_type) && $msg_type == 'error') ? '#f5c6cb' : '#c3e6cb';
            ?>
            
            <div class="alert" style="background: <?= $bg_color ?>; color: <?= $text_color ?>; border: 1px solid <?= $border_color ?>; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="form-box" style="margin-bottom: 30px;">
            <h3 style="color: #153448; margin-bottom: 15px;">Assign New Proposal</h3>
            
            <?php if ($new_proposals->num_rows > 0): ?>
            <form method="POST">
                <div class="input-group">
                    <label>Proposal</label>
                    <select name="proposal_id" required>
                        <?php while($row = $new_proposals->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?> (<?= $row['researcher_email'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label>Reviewer</label>
                    <select name="reviewer_id" required>
                        <option value="">-- Choose Reviewer --</option>
                        <?php 
                        // Reset pointer to use reviewers list again later
                        $reviewers->data_seek(0); 
                        while($r = $reviewers->fetch_assoc()): 
                        ?>
                            <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" name="assign_proposal" class="btn-save">Assign</button>
            </form>
            <?php else: ?>
                <p style="color: #666; font-style: italic;">No new proposals waiting.</p>
            <?php endif; ?>
        </div>

        <div class="form-box" style="border-left: 5px solid #dc3545;">
            <h3 style="color: #dc3545; margin-bottom: 15px;">Assign Appeal Cases</h3>
            
            <?php if ($appeals->num_rows > 0): ?>
            <form method="POST">
                <div class="input-group">
                    <label>Appeal Case</label>
                    <select name="proposal_id" required>
                        <?php while($row = $appeals->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?> (<?= $row['researcher_email'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label>Assign to Reviewer</label>
                    <select name="reviewer_id" required>
                        <option value="">-- Choose Reviewer --</option>
                        <?php 
                        // Reset pointer again
                        $reviewers->data_seek(0); 
                        while($r = $reviewers->fetch_assoc()): 
                        ?>
                            <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" name="assign_appeal" class="btn-save" style="background: #dc3545;">Assign Appeal</button>
            </form>
            <?php else: ?>
                <p style="color: #666; font-style: italic;">No active appeals.</p>
            <?php endif; ?>
        </div>

    </section>

</body>
</html>