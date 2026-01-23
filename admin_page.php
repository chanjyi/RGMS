<?php
session_start();
require 'config.php';

/* =========================
   1) SECURITY CHECK (Admin)
   ========================= */
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$message  = "";
$msg_type = ""; // 'success' | 'error'

/* ==========================================
   2) LOGIC: ASSIGN NEW PROPOSAL
   ========================================== */
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
            // Update proposal status safely
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
                $msg = "New Assignment: You have been assigned a proposal.";
                $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
                $notif->bind_param("ss", $rev_email, $msg);
                $notif->execute();
            }

            $message  = "Proposal assigned successfully!";
            $msg_type = "success";
        } else {
            $message  = "Database Error (Assign Proposal): " . $stmt->error;
            $msg_type = "error";
        }
    }
}

/* ==========================================
   3) LOGIC: ASSIGN APPEAL CASE (ROBUST FIX)
   ========================================== */
if (isset($_POST['assign_appeal'])) {
    $prop_id     = (int)($_POST['proposal_id'] ?? 0);
    $reviewer_id = (int)($_POST['reviewer_id'] ?? 0);

    if ($prop_id <= 0 || $reviewer_id <= 0) {
        $message  = "Error: Invalid appeal case or reviewer.";
        $msg_type = "error";
    } else {
        // Check history
        $check_hist = $conn->prepare("SELECT id FROM reviews WHERE proposal_id = ? AND reviewer_id = ?");
        $check_hist->bind_param("ii", $prop_id, $reviewer_id);

        if (!$check_hist->execute()) {
            $message  = "Database Error (Check): " . $check_hist->error;
            $msg_type = "error";
        } else {
            $check_hist->store_result();

            if ($check_hist->num_rows > 0) {
                $message  = "Error: This reviewer has already reviewed this proposal. Please choose someone else.";
                $msg_type = "error";
            } else {
                $check_hist->close();

                $stmt = $conn->prepare("
                    INSERT INTO reviews (proposal_id, reviewer_id, status, assigned_date, type)
                    VALUES (?, ?, 'Pending', NOW(), 'Appeal')
                ");
                $stmt->bind_param("ii", $prop_id, $reviewer_id);

                if ($stmt->execute()) {
                    // Update status & priority
                    $up = $conn->prepare("UPDATE proposals SET status = 'ASSIGNED', priority = 'High' WHERE id = ?");
                    $up->bind_param("i", $prop_id);
                    $up->execute();

                    // Notify reviewer
                    $rev_q = $conn->prepare("SELECT email FROM users WHERE id = ?");
                    $rev_q->bind_param("i", $reviewer_id);
                    $rev_q->execute();
                    $rev_res = $rev_q->get_result();

                    if ($rev_res && $rev_res->num_rows > 0) {
                        $rev_email = $rev_res->fetch_assoc()['email'];
                        $msg = "Appeal Case: You have been assigned to review proposal #$prop_id.";
                        $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
                        $notif->bind_param("ss", $rev_email, $msg);
                        $notif->execute();
                    }

                    $message  = "Appeal assigned successfully!";
                    $msg_type = "success";
                } else {
                    $message  = "Database Error (Assign Appeal): " . $stmt->error;
                    $msg_type = "error";
                }
            }
        }
    }
}

/* ==========================================
   4) FETCH DATA
   ========================================== */
$reviewers     = $conn->query("SELECT * FROM users WHERE role = 'Reviewer' ORDER BY name ASC");
$new_proposals = $conn->query("SELECT * FROM proposals WHERE status = 'SUBMITTED'");
$appeals       = $conn->query("SELECT * FROM proposals WHERE status = 'PENDING_REASSIGNMENT'");

/* Alert colors */
$bg_color     = ($msg_type === 'error') ? '#f8d7da' : '#d4edda';
$text_color   = ($msg_type === 'error') ? '#721c24' : '#155724';
$border_color = ($msg_type === 'error') ? '#f5c6cb' : '#c3e6cb';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

<?php include 'sidebar.php'; ?>

<section class="home-section">
  <div class="welcome-text">Admin Dashboard</div>
  <hr style="opacity: 0.3; margin: 20px 0;">

  <!-- Alert -->
  <?php if (!empty($message)): ?>
    <div class="alert" style="background: <?= $bg_color ?>; color: <?= $text_color ?>; border: 1px solid <?= $border_color ?>; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <!-- ALL cards in Windows Settings style -->
  <div class="dashboard-cards">

    <a class="dash-card" href="users_list.php">
    <div class="dash-icon"><i class='bx bx-user'></i></div>
    <div class="dash-text">
        <h3>Total Users</h3>
        <p class="dash-value">View Users</p>
    </div>
    </a>


    <a class="dash-card" href="proposals_list.php">
      <div class="dash-icon"><i class='bx bx-file'></i></div>
      <div class="dash-text">
        <h3>Total Proposals</h3>
        <p class="dash-value">View proposals list</p>
      </div>
    </a>

    <a class="dash-card" href="grants_list.php">
      <div class="dash-icon"><i class='bx bx-money'></i></div>
      <div class="dash-text">
        <h3>Total Grants</h3>
        <p class="dash-value">View grants</p>
      </div>
    </a>

    <a class="dash-card" href="activity_logs.php">
      <div class="dash-icon"><i class='bx bx-notepad'></i></div>
      <div class="dash-text">
        <h3>System Activity Logs</h3>
        <p class="dash-value">View logs</p>
      </div>
    </a>

    <a class="dash-card" href="help_issues.php">
    <div class="dash-icon"><i class='bx bx-support'></i></div>
    <div class="dash-text">
        <h3>Help Issues</h3>
        <p class="dash-value">Handle help requests</p>
    </div>
    </a>


    <a class="dash-card" href="pending_users.php">
      <div class="dash-icon"><i class='bx bx-user-check'></i></div>
      <div class="dash-text">
        <h3>User Approvals</h3>
        <p class="dash-value">Pending users</p>
      </div>
    </a>
    

    <a class="dash-card" href="assign_new_proposal.php">
    <div class="dash-icon"><i class='bx bx-task'></i></div>
    <div class="dash-text">
      <h3>Assign New Proposal</h3>
      <p class="dash-value"><?= (int)$new_proposals->num_rows ?> proposals waiting</p>
    </div>
  </a>


</section>

<script src="script.js"></script>
<script>
function toggleDashCard(bodyId, toggleId){
  const body = document.getElementById(bodyId);
  const toggle = document.getElementById(toggleId);
  if(!body) return;

  body.classList.toggle("hidden");
  if(toggle) toggle.classList.toggle("open");
}
</script>

</body>
</html>
