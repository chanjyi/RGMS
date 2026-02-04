<?php
session_start();
require 'config.php';
require 'activity_helper.php';


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

            log_activity(
                $conn,
                "ASSIGN_REVIEWER",                      // action
                "PROPOSAL",                             // entity_type
                (int)$prop_id,                          // entity_id (int)
                "Assign Proposal",                      // label
                "Assigned proposal #$prop_id ($proposal_title) to reviewer_id=$reviewer_id"
            );



            header("Location: assign_new_proposal.php?success=proposal&tab=assignProposal");
            exit();
        } else {
            $message  = "Database Error (Assign Proposal): " . $stmt->error;
            $msg_type = "error";
        }
    }
}

/* =========================
   2) HANDLE ASSIGN APPEAL CASES
   ========================= */
if (isset($_POST['assign_appeal'])) {
    $prop_id     = (int)($_POST['proposal_id'] ?? 0);
    $reviewer_id = (int)($_POST['reviewer_id'] ?? 0);

    if ($prop_id <= 0 || $reviewer_id <= 0) {
        $message  = "Error: Invalid appeal case or reviewer.";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO reviews (proposal_id, reviewer_id, status, assigned_date, type)
            VALUES (?, ?, 'Pending', NOW(), 'Appeal')
        ");
        $stmt->bind_param("ii", $prop_id, $reviewer_id);

        if ($stmt->execute()) {
            // Make it disappear from APPEALED list after assign
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
                $notif_msg = "New Assignment: You have been assigned an appeal case.";

                $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
                $notif->bind_param("ss", $rev_email, $notif_msg);
                $notif->execute();
            }

            /log_activity(
                $conn,
                "ASSIGN_REVIEWER",                      // action
                "APPEAL",                               // entity_type
                (int)$prop_id,                          // entity_id (int)
                "Assign Appeal Case",                   // label
                "Assigned appeal case #$prop_id ($proposal_title) to reviewer_id=$reviewer_id"
            );



            header("Location: assign_new_proposal.php?success=appeal&tab=assignAppeal");
            exit();
        } else {
            $message  = "Database Error (Assign Appeal): " . $stmt->error;
            $msg_type = "error";
        }
    }
}

/* =========================
   3) SUCCESS MESSAGE
   ========================= */
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'proposal') {
        $message  = "Proposal assigned successfully!";
        $msg_type = "success";
    } elseif ($_GET['success'] === 'appeal') {
        $message  = "Appeal case assigned successfully!";
        $msg_type = "success";
    }
}

/* =========================
   4) FETCH DATA
   IMPORTANT: use unique variable names to avoid sidebar.php overriding them
   ========================= */

// Reviewers
$reviewers_rs = $conn->query("SELECT id, name, email FROM users WHERE role = 'reviewer' ORDER BY name ASC");

// Proposals waiting assignment
$proposals_rs = $conn->query("
    SELECT id, title, researcher_email
    FROM proposals
    WHERE status = 'SUBMITTED'
    ORDER BY id DESC
");

// Appeals waiting assignment (change status value if your system uses another word)
$appeals_rs = $conn->query("
    SELECT id, title, researcher_email
    FROM proposals
    WHERE status = 'APPEALED'
    ORDER BY id DESC
");

// History (latest 200)
$history_rs = $conn->query("
    SELECT
        r.id               AS review_id,
        r.type             AS assign_type,
        r.status           AS review_status,
        r.assigned_date    AS assigned_date,
        p.id               AS proposal_id,
        p.title            AS proposal_title,
        p.researcher_email AS researcher_email,
        u.name             AS reviewer_name,
        u.email            AS reviewer_email
    FROM reviews r
    JOIN proposals p ON p.id = r.proposal_id
    JOIN users u     ON u.id = r.reviewer_id
    ORDER BY r.assigned_date DESC
    LIMIT 200
");

/* Alert colors */
$bg_color     = ($msg_type === 'error') ? '#f8d7da' : '#d4edda';
$text_color   = ($msg_type === 'error') ? '#721c24' : '#155724';
$border_color = ($msg_type === 'error') ? '#f5c6cb' : '#c3e6cb';

// Keep selected tab after refresh / redirect
$defaultTab = $_GET['tab'] ?? 'assignProposal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Assign</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <!-- SAME TAB STYLE AS YOUR USERS PAGE -->
    <style>
        .tab-btn { padding: 12px 24px; cursor: pointer; border: none; background: #eee; font-size:15px; margin-right:2px; border-radius:5px 5px 0 0; transition: 0.3s; }
        .tab-btn:hover { background: #ddd; }
        .tab-btn.active { background: #3C5B6F; color: white; }
        .tab-content { display: none; padding: 25px; border: 1px solid #ddd; margin-top: -1px; border-radius: 0 5px 5px 5px; background: white; }
        .tab-content.active { display: block; }

        /* History table (simple + consistent) */
        .styled-table { width:100%; border-collapse: collapse; margin-top: 10px; }
        .styled-table th, .styled-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align:left; font-size: 14px; }
        .styled-table th { background: #f8f9fa; }

        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display:inline-block; }
        .badge-proposal { background:#d1ecf1; color:#0c5460; }
        .badge-appeal { background:#fff3cd; color:#856404; }
        .badge-pending { background:#e2e3e5; color:#383d41; }
        .badge-approved { background:#d4edda; color:#155724; }
        .badge-rejected { background:#f8d7da; color:#721c24; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<section class="home-section">
    <a href="<?= $dashboardLink ?>" class="btn-back" style="display: inline-flex; align-items: center; text-decoration: none; color: #3C5B6F; font-weight: 600; margin-bottom: 15px;">
        <i class='bx bx-left-arrow-alt' style="font-size: 20px; margin-right: 5px;"></i>
        Back to Dashboard
    </a>

    <div class="welcome-text">
        <i class='bx bx-task' style="font-size:24px; vertical-align:middle;"></i>
        Admin Dashboard | Assign
    </div>

    <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 18px;">

    <!-- Alert -->
    <?php if (!empty($message)): ?>
        <div class="alert" style="background: <?= $bg_color ?>; color: <?= $text_color ?>; border: 1px solid <?= $border_color ?>; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation (Users-page style) -->
    <div style="margin-bottom: 0;">
        <button class="tab-btn" id="btn-assignProposal" onclick="openTab(event, 'assignProposal')">
            <i class='bx bx-user-plus'></i> Assign Proposal
        </button>
        <button class="tab-btn" id="btn-assignAppeal" onclick="openTab(event, 'assignAppeal')">
            <i class='bx bx-error-circle'></i> Assign Appeal Cases
        </button>
        <button class="tab-btn" id="btn-assignHistory" onclick="openTab(event, 'assignHistory')">
            <i class='bx bx-history'></i> Assign History
        </button>
    </div>

    <!-- TAB 1: Assign Proposal -->
    <div id="assignProposal" class="tab-content">
        <h3 style="color:#3C5B6F; margin-top:0;">
            <i class='bx bx-user-plus'></i> Assign Proposal to Reviewer
        </h3>

        <div class="form-box" style="max-width: 700px; padding:0; border:none; box-shadow:none;">
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
                                <?php $reviewers_rs->data_seek(0); ?>
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
    </div>

    <!-- TAB 2: Assign Appeal Cases -->
    <div id="assignAppeal" class="tab-content">
        <h3 style="color:#dc3545; margin-top:0;">
            <i class='bx bx-error-circle'></i> Assign Appeal Cases
        </h3>

        <div class="form-box" style="max-width: 700px; padding:0; border:none; box-shadow:none;">
            <?php if ($appeals_rs && $appeals_rs->num_rows > 0): ?>
                <form method="POST">
                    <div class="input-group">
                        <label>Appeal Case</label>
                        <select name="proposal_id" required>
                            <?php while($row = $appeals_rs->fetch_assoc()): ?>
                                <option value="<?= (int)$row['id'] ?>">
                                    <?= htmlspecialchars($row['title']) ?> (<?= htmlspecialchars($row['researcher_email']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Assign to Reviewer</label>
                        <select name="reviewer_id" required>
                            <option value="">-- Choose Reviewer --</option>
                            <?php if ($reviewers_rs && $reviewers_rs->num_rows > 0): ?>
                                <?php $reviewers_rs->data_seek(0); ?>
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

                    <button type="submit" name="assign_appeal" class="btn-save" style="background:#dc3545;">
                        Assign Appeal
                    </button>
                </form>
            <?php else: ?>
                <p style="color:#666; font-style:italic; margin:0;">No active appeals.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB 3: Assign History -->
    <div id="assignHistory" class="tab-content">
        <h3 style="color:#3C5B6F; margin-top:0;">
            <i class='bx bx-history'></i> Assign History (Latest 200)
        </h3>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>Assigned Date</th>
                    <th>Type</th>
                    <th>Proposal</th>
                    <th>Researcher</th>
                    <th>Reviewer</th>
                    <th>Review Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($history_rs && $history_rs->num_rows > 0): ?>
                    <?php while($h = $history_rs->fetch_assoc()): ?>
                        <?php
                            $type = strtolower($h['assign_type'] ?? '');
                            $status = strtolower($h['review_status'] ?? '');
                            $typeBadge = ($type === 'appeal') ? "badge badge-appeal" : "badge badge-proposal";

                            $statusBadge = "badge badge-pending";
                            if ($status === 'approved') $statusBadge = "badge badge-approved";
                            elseif ($status === 'rejected') $statusBadge = "badge badge-rejected";
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($h['assigned_date'] ?? '-') ?></td>
                            <td><span class="<?= $typeBadge ?>"><?= htmlspecialchars($h['assign_type'] ?? '-') ?></span></td>
                            <td>#<?= (int)$h['proposal_id'] ?> — <?= htmlspecialchars($h['proposal_title'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($h['researcher_email'] ?? '-') ?></td>
                            <td>
                                <?= htmlspecialchars($h['reviewer_name'] ?? '-') ?>
                                <div style="color:#666; font-size:12px; margin-top:2px;">
                                    <?= htmlspecialchars($h['reviewer_email'] ?? '') ?>
                                </div>
                            </td>
                            <td><span class="<?= $statusBadge ?>"><?= htmlspecialchars($h['review_status'] ?? '-') ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#999;">No assignment records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>

<script>
function openTab(evt, tabName) {
    // hide all tab contents
    var tabcontent = document.getElementsByClassName("tab-content");
    for (var i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        tabcontent[i].classList.remove("active");
    }

    // remove active from all buttons
    var tablinks = document.getElementsByClassName("tab-btn");
    for (var i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // show selected
    var el = document.getElementById(tabName);
    el.style.display = "block";
    el.classList.add("active");
    evt.currentTarget.className += " active";

    // keep tab in URL (so refresh stays same tab)
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url.toString());
    } catch (e) {}
}

// Open default tab (from ?tab=)
(function initTab(){
    var tab = "<?= htmlspecialchars($defaultTab) ?>";
    var btn = document.getElementById("btn-" + tab);
    if (!btn) {
        tab = "assignProposal";
        btn = document.getElementById("btn-assignProposal");
    }
    // fake click
    btn.className += " active";
    var el = document.getElementById(tab);
    el.style.display = "block";
    el.classList.add("active");
})();
</script>

</body>
</html>
