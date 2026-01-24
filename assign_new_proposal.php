<?php
session_start();
require 'config.php';

// Security Check (Admin)
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$message  = "";
$msg_type = ""; // success | error

/* =========================
   1) HANDLE ASSIGN NEW PROPOSAL
   ========================= */
if (isset($_POST['assign_proposal'])) {
    $prop_id     = (int)($_POST['proposal_id'] ?? 0);
    $reviewer_id = (int)($_POST['reviewer_id'] ?? 0);

    if ($prop_id <= 0 || $reviewer_id <= 0) {
        $message  = "Error: Invalid proposal or reviewer.";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO reviews (proposal_id, reviewer_id, status, assigned_date, type)
            VALUES (?, ?, 'Pending', NOW(), 'Proposal')
        ");
        $stmt->bind_param("ii", $prop_id, $reviewer_id);

        if ($stmt->execute()) {
            // Update proposal status
            $up = $conn->prepare("UPDATE proposals SET status = 'ASSIGNED' WHERE id = ?");
            $up->bind_param("i", $prop_id);
            $up->execute();

            // Notify reviewer
            $rev_q = $conn->prepare("SELECT email FROM users WHERE id = ?");
            $rev_q->bind_param("i", $reviewer_id);
            $rev_q->execute();
            $rev_res = $rev_q->get_result();

            if ($rev_res && $rev_res->num_rows > 0) {
                $rev_email = $rev_res->fetch_assoc()['email'];
                $notif_msg = "New Assignment: You have been assigned a proposal.";

                $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
                $notif->bind_param("ss", $rev_email, $notif_msg);
                $notif->execute();
            }

            // Redirect (prevents double submit refresh)
            header("Location: assign_new_proposal.php?success=1");
            exit();
        } else {
            $message  = "Database Error (Assign Proposal): " . $stmt->error;
            $msg_type = "error";
        }
    }
}

/* =========================
   2) SUCCESS MESSAGE
   ========================= */
if (isset($_GET['success'])) {
    $message  = "Proposal assigned successfully!";
    $msg_type = "success";
}

/* =========================
   3) FETCH DATA
   IMPORTANT: use unique variable names to avoid sidebar.php overriding them
   ========================= */

// If your database stores role as 'reviewer' (lowercase), keep it lowercase
$reviewers_rs = $conn->query("SELECT id, name FROM users WHERE role = 'reviewer' ORDER BY name ASC");

// For proposals waiting assignment
$proposals_rs = $conn->query("SELECT id, title, researcher_email FROM proposals WHERE status = 'SUBMITTED' ORDER BY id DESC");

/* Alert colors */
$bg_color     = ($msg_type === 'error') ? '#f8d7da' : '#d4edda';
$text_color   = ($msg_type === 'error') ? '#721c24' : '#155724';
$border_color = ($msg_type === 'error') ? '#f5c6cb' : '#c3e6cb';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Assign New Proposal</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

<?php include 'sidebar.php'; ?>

<section class="home-section">

    <!-- Header (same style as your Users page) -->
    <div class="welcome-text">
        <i class='bx bx-task' style="font-size:24px; vertical-align:middle;"></i>
        Admin Dashboard | Assign New Proposal
    </div>

    <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 25px;">

    <!-- Return button (same style as Users page) -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
        <a href="<?= $dashboardLink ?>" class="btn-back" style="display: inline-flex; align-items: center; text-decoration: none; color: #3C5B6F; font-weight: 600; margin-bottom: 15px;">
            <i class='bx bx-left-arrow-alt' style="font-size: 20px; margin-right: 5px;"></i> 
            Back to Dashboard
        </a>
    </div>

    <!-- Alert -->
    <?php if (!empty($message)): ?>
        <div class="alert" style="background: <?= $bg_color ?>; color: <?= $text_color ?>; border: 1px solid <?= $border_color ?>; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Form Box -->
    <div class="form-box" style="max-width: 700px;">
        <h3 style="color:#3C5B6F; margin-top:0;">
            <i class='bx bx-user-plus'></i> Assign Proposal to Reviewer
        </h3>

        <?php if ($proposals_rs && $proposals_rs->num_rows > 0): ?>
            <form method="POST">
                <div class="input-group">
                    <label>Proposal</label>
                    <select name="proposal_id" required>
                        <?php while($row = $proposals_rs->fetch_assoc()): ?>
                            <option value="<?= (int)$row['id'] ?>">
                                <?= htmlspecialchars($row['title']) ?> (<?= htmlspecialchars($row['researcher_email']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label>Reviewer</label>
                    <select name="reviewer_id" required>
                        <option value="">-- Choose Reviewer --</option>

                        <?php if ($reviewers_rs && $reviewers_rs->num_rows > 0): ?>
                            <?php while($r = $reviewers_rs->fetch_assoc()): ?>
                                <option value="<?= (int)$r['id'] ?>">
                                    <?= htmlspecialchars($r['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No reviewers found</option>
                        <?php endif; ?>

                    </select>
                </div>

                <button type="submit" name="assign_proposal" class="btn-save">Assign</button>
            </form>
        <?php else: ?>
            <p style="color:#666; font-style:italic; margin:0;">No new proposals waiting.</p>
        <?php endif; ?>
    </div>

</section>

</body>
</html>
