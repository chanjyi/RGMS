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

$email = $_SESSION['email'];
$message  = "";
$msg_type = ""; // 'success' | 'error'

/* =========================
   FETCH USER DETAILS
   ========================= */
$user_q = $conn->prepare("SELECT id, name, profile_pic FROM users WHERE email = ?");
$user_q->bind_param("s", $email);
$user_q->execute();
$user_data = $user_q->get_result()->fetch_assoc();

$user_id   = $user_data['id'] ?? 0;
$username  = $user_data['name'] ?? 'Admin';
$user_role = ucfirst($_SESSION['role']); // "Admin"
$profile_pic = !empty($user_data['profile_pic']) ? "images/" . $user_data['profile_pic'] : "images/default.png";

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
   3) LOGIC: ASSIGN APPEAL CASE
   ========================================== */
if (isset($_POST['assign_appeal'])) {
    $prop_id     = (int)($_POST['proposal_id'] ?? 0);
    $reviewer_id = (int)($_POST['reviewer_id'] ?? 0);

    if ($prop_id <= 0 || $reviewer_id <= 0) {
        $message  = "Error: Invalid appeal case or reviewer.";
        $msg_type = "error";
    } else {
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
   4) FETCH COUNTS (for subtitles)
   ========================================== */
$new_proposals = $conn->query("SELECT COUNT(*) as c FROM proposals WHERE status = 'SUBMITTED'")->fetch_assoc()['c'];
$pending_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE status = 'PENDING'")->fetch_assoc()['c'];
$appeals_count = $conn->query("SELECT COUNT(*) as c FROM proposals WHERE status = 'PENDING_REASSIGNMENT'")->fetch_assoc()['c'];

/* Alert colors */
$bg_color     = ($msg_type === 'error') ? '#f8d7da' : '#d4edda';
$text_color   = ($msg_type === 'error') ? '#721c24' : '#155724';
$border_color = ($msg_type === 'error') ? '#f5c6cb' : '#c3e6cb';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

<?php include 'sidebar.php'; ?>

<section class="home-section">

    <div class="dashboard-header-group">
        <h1 class="main-title">Admin Dashboard</h1>

        <div class="user-identity-row">
            <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile" class="identity-img">
            <div class="identity-text">
                <div class="identity-name"><?= htmlspecialchars($username) ?></div>
                <div class="identity-role"><?= htmlspecialchars($user_role) ?></div>
            </div>
        </div>
    </div>

    <hr class="dashboard-divider">

    <?php if (!empty($message)): ?>
        <div class="alert" style="background: <?= $bg_color ?>; color: <?= $text_color ?>; border: 1px solid <?= $border_color ?>; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div style="max-width: 800px;">

        <a href="users_list.php" class="header-card">
            <div class="header-left">
                <div class="header-icon-box"><i class='bx bx-user-pin'></i></div>
                <div class="header-text-group">
                    <h3>Total Users</h3>
                    <span>View all user accounts</span>
                </div>
            </div>
            <i class='bx bx-chevron-right header-arrow'></i>
        </a>

        <a href="pending_users.php" class="header-card">
            <div class="header-left">
                <div class="header-icon-box"><i class='bx bx-user-check'></i></div>
                <div class="header-text-group">
                    <h3>User Approvals</h3>
                    <span><?= (int)$pending_users ?> users pending approval</span>
                </div>
            </div>
            <i class='bx bx-chevron-right header-arrow'></i>
        </a>

        <a href="proposals_list.php" class="header-card">
            <div class="header-left">
                <div class="header-icon-box"><i class='bx bx-file'></i></div>
                <div class="header-text-group">
                    <h3>Total Proposals</h3>
                    <span>View proposals list</span>
                </div>
            </div>
            <i class='bx bx-chevron-right header-arrow'></i>
        </a>

        <a href="assign_new_proposal.php" class="header-card">
            <div class="header-left">
                <div class="header-icon-box"><i class='bx bx-task'></i></div>
                <div class="header-text-group">
                    <h3>Assign New Proposals</h3>
                    <span><?= (int)$new_proposals ?> proposals waiting to assign</span>
                </div>
            </div>
            <i class='bx bx-chevron-right header-arrow'></i>
        </a>

        <a href="grants_list.php" class="header-card">
            <div class="header-left">
                <div class="header-icon-box"><i class='bx bx-money'></i></div>
                <div class="header-text-group">
                    <h3>Total Grants</h3>
                    <span>View grants & details</span>
                </div>
            </div>
            <i class='bx bx-chevron-right header-arrow'></i>
        </a>

        <a href="activity_logs.php" class="header-card">
            <div class="header-left">
                <div class="header-icon-box"><i class='bx bx-notepad'></i></div>
                <div class="header-text-group">
                    <h3>System Activity Logs</h3>
                    <span>Track key actions in system</span>
                </div>
            </div>
            <i class='bx bx-chevron-right header-arrow'></i>
        </a>

        <a href="help_issues.php" class="header-card">
            <div class="header-left">
                <div class="header-icon-box"><i class='bx bx-support'></i></div>
                <div class="header-text-group">
                    <h3>Help Issues</h3>
                    <span>Handle help requests</span>
                </div>
            </div>
            <i class='bx bx-chevron-right header-arrow'></i>
        </a>

        <a href="profile.php" class="header-card">
            <div class="header-left">
                <div class="header-icon-box"><i class='bx bx-user-circle'></i></div>
                <div class="header-text-group">
                    <h3>My Profile</h3>
                    <span>Update account details</span>
                </div>
            </div>
            <i class='bx bx-chevron-right header-arrow'></i>
        </a>

    </div>
</section>

</body>
</html>
