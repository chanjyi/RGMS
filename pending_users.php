<?php
session_start();
require 'config.php';

/* Security Check */
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$message = "";
$msg_type = ""; // success | error

/* Handle approve / reject */
if (isset($_POST['action'], $_POST['user_id'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    if ($user_id <= 0) {
        $msg_type = "error";
        $message = "Invalid user id.";
    } else {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE users SET status = 'APPROVED' WHERE id = ?");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $msg_type = "success";
                $message = "User approved successfully.";
            } else {
                $msg_type = "error";
                $message = "Database error: " . $stmt->error;
            }
            $stmt->close();

        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE users SET status = 'REJECTED' WHERE id = ?");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $msg_type = "success";
                $message = "User rejected successfully.";
            } else {
                $msg_type = "error";
                $message = "Database error: " . $stmt->error;
            }
            $stmt->close();

        } else {
            $msg_type = "error";
            $message = "Invalid action.";
        }
    }
}

/* Fetch Pending */
$pending_q = $conn->query("
    SELECT id, name, email, role
    FROM users
    WHERE status = 'PENDING'
    ORDER BY id DESC
");

/* Fetch History (Approved + Rejected) */
$history_q = $conn->query("
    SELECT id, name, email, role, status
    FROM users
    WHERE status IN ('APPROVED', 'REJECTED')
    ORDER BY id DESC
");

/* Alert colors */
$bg_color     = ($msg_type === 'error') ? '#f8d7da' : '#d4edda';
$text_color   = ($msg_type === 'error') ? '#721c24' : '#155724';
$border_color = ($msg_type === 'error') ? '#f5c6cb' : '#c3e6cb';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | User Approvals</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <style>
        /* SAME tab style as your reference */
        .tab-btn { padding: 12px 24px; cursor: pointer; border: none; background: #eee; font-size:15px; margin-right:2px; border-radius:5px 5px 0 0; transition: 0.3s; }
        .tab-btn:hover { background: #ddd; }
        .tab-btn.active { background: #3C5B6F; color: white; }
        .tab-content { display: none; padding: 25px; border: 1px solid #ddd; margin-top: -1px; border-radius: 0 5px 5px 5px; background: white; }
        .tab-content.active { display: block; }

        /* Button sizing + colors */
        .action-wrap { display:flex; gap:10px; align-items:center; }
        .action-btn {
            min-width: 110px;
            padding: 10px 14px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }
        .btn-approve { background:#28a745; }
        .btn-reject  { background:#dc3545; }
        .action-btn:hover { opacity:0.9; }

        /* Badge style for history status */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display:inline-block; }
        .badge-approved { background:#d4edda; color:#155724; }
        .badge-rejected { background:#f8d7da; color:#721c24; }
        .badge-pending  { background:#fff3cd; color:#856404; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<section class="home-section">
    <div class="welcome-text">
        <i class='bx bx-user-check' style="font-size:24px; vertical-align:middle;"></i>
        Admin Dashboard | User Approvals
    </div>
    <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 25px;">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
        <!-- Return button (same as your reference pattern) -->
        <a href="<?= $dashboardLink ?>" class="btn-back" style="display: inline-flex; align-items: center; text-decoration: none; color: #3C5B6F; font-weight: 600; margin-bottom: 15px;">
            <i class='bx bx-left-arrow-alt' style="font-size: 20px; margin-right: 5px;"></i> 
            Back to Dashboard
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert"
             style="background:<?= $bg_color ?>; color:<?= $text_color ?>; border: 1px solid <?= $border_color ?>;
                    padding: 15px; margin-bottom: 20px; border-radius: 8px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- TAB NAV -->
    <div style="margin-bottom: 0;">
        <button class="tab-btn active" onclick="openTab(event, 'pendingTab')">
            <i class='bx bx-time-five'></i> Pending Approvals
        </button>
        <button class="tab-btn" onclick="openTab(event, 'historyTab')">
            <i class='bx bx-history'></i> History
        </button>
    </div>

    <!-- TAB 1: PENDING -->
    <div id="pendingTab" class="tab-content active">
        <h3 style="color:#3C5B6F; margin-top:0;"><i class='bx bx-list-check'></i> Pending Users</h3>

        <table class="styled-table">
            <thead>
                <tr>
                    <th style="width:18%;">Name</th>
                    <th>Email</th>
                    <th style="width:18%;">Role</th>
                    <th style="width:26%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pending_q && $pending_q->num_rows > 0): ?>
                    <?php while($u = $pending_q->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($u['role'] ?? '-') ?></td>
                            <td>
                                <div class="action-wrap">
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" name="action" value="approve" class="action-btn btn-approve">
                                            Approve
                                        </button>
                                    </form>

                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" name="action" value="reject"
                                                class="action-btn btn-reject"
                                                onclick="return confirm('Reject this user?');">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#999;">No pending users.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- TAB 2: HISTORY -->
    <div id="historyTab" class="tab-content">
        <h3 style="color:#3C5B6F; margin-top:0;"><i class='bx bx-history'></i> Approval History</h3>

        <table class="styled-table">
            <thead>
                <tr>
                    <th style="width:10%;">ID</th>
                    <th style="width:18%;">Name</th>
                    <th>Email</th>
                    <th style="width:15%;">Role</th>
                    <th style="width:18%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($history_q && $history_q->num_rows > 0): ?>
                    <?php while($h = $history_q->fetch_assoc()): ?>
                        <?php
                            $status = strtoupper($h['status'] ?? '');
                            $badgeClass = "badge";
                            if ($status === "APPROVED") $badgeClass .= " badge-approved";
                            elseif ($status === "REJECTED") $badgeClass .= " badge-rejected";
                            else $badgeClass .= " badge-pending";
                        ?>
                        <tr>
                            <td><?= (int)$h['id'] ?></td>
                            <td><?= htmlspecialchars($h['name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($h['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($h['role'] ?? '-') ?></td>
                            <td><span class="<?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#999;">No approval history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>

<script>
function openTab(evt, tabName) {
    var tabcontent = document.getElementsByClassName("tab-content");
    for (var i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    var tablinks = document.getElementsByClassName("tab-btn");
    for (var i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}
</script>

<!-- If your sidebar/hamburger needs this -->
<script src="script.js"></script>

</body>
</html>
