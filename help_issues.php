<?php
session_start();
require 'config.php';

/* Admin protection */
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_email = $_SESSION['email'];
$message = "";
$msg_type = "";

/* ========= Handle Actions ========= */
if (isset($_POST['report_action'], $_POST['report_id'])) {
    $report_id = (int)$_POST['report_id'];
    $action = $_POST['report_action'];

    // Fetch report target (who is accused / related)
    $rep_stmt = $conn->prepare("SELECT id, reviewer_email, researcher_email, status FROM misconduct_reports WHERE id = ?");
    $rep_stmt->bind_param("i", $report_id);
    $rep_stmt->execute();
    $rep = $rep_stmt->get_result()->fetch_assoc();
    $rep_stmt->close();

    if (!$rep) {
        $msg_type = "error";
        $message = "Report not found.";
    } else {

        // Decide target email: (example: researcher is the reported person; adjust as your system logic)
        $target_email = $rep['researcher_email'];

        if ($action === 'mark_resolved') {
            $up = $conn->prepare("UPDATE misconduct_reports SET status='RESOLVED' WHERE id=?");
            $up->bind_param("i", $report_id);
            $up->execute();
            $up->close();

            $msg_type = "success";
            $message = "Report marked as resolved.";

        } elseif ($action === 'remove_user') {
            // Remove user (dangerous): usually set status = inactive instead of delete.
            // If you have no 'status' column in users for active/inactive, you can DELETE.
            // Recommended: add users.account_status (ACTIVE/REMOVED).
            $del = $conn->prepare("DELETE FROM users WHERE email=?");
            $del->bind_param("s", $target_email);
            $del->execute();
            $del->close();

            $up = $conn->prepare("UPDATE misconduct_reports SET status='ACTION_TAKEN' WHERE id=?");
            $up->bind_param("i", $report_id);
            $up->execute();
            $up->close();

            $msg_type = "success";
            $message = "User removed and action recorded.";

        } elseif ($action === 'warn_user') {
            // Optional: send notification if you have notifications table
            $warnMsg = "Admin Warning: Please review your recent activity. Report ID: #".$report_id;
            $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
            $notif->bind_param("ss", $target_email, $warnMsg);
            $notif->execute();
            $notif->close();

            $up = $conn->prepare("UPDATE misconduct_reports SET status='ACTION_TAKEN' WHERE id=?");
            $up->bind_param("i", $report_id);
            $up->execute();
            $up->close();

            $msg_type = "success";
            $message = "Warning sent to user.";
        } else {
            $msg_type = "error";
            $message = "Invalid action.";
        }
    }
}

/* ========= Handle Send Message ========= */
if (isset($_POST['send_message'], $_POST['report_id'], $_POST['chat_message'])) {
    $report_id = (int)$_POST['report_id'];
    $chat_message = trim($_POST['chat_message']);

    if ($report_id > 0 && $chat_message !== '') {
        $ins = $conn->prepare("
            INSERT INTO issue_messages (report_id, sender_role, sender_email, message)
            VALUES (?, 'admin', ?, ?)
        ");
        $ins->bind_param("iss", $report_id, $admin_email, $chat_message);
        $ins->execute();
        $ins->close();

        $msg_type = "success";
        $message = "Message sent.";
    } else {
        $msg_type = "error";
        $message = "Message cannot be empty.";
    }
}

/* ========= Fetch Reports ========= */
$reports_q = $conn->query("SELECT * FROM misconduct_reports ORDER BY created_at DESC");

/* ========= For Communication Tab: choose report ========= */
$selected_report_id = (int)($_GET['report_id'] ?? 0);
$selected_report = null;
$chat_q = null;

if ($selected_report_id > 0) {
    $st = $conn->prepare("SELECT * FROM misconduct_reports WHERE id=?");
    $st->bind_param("i", $selected_report_id);
    $st->execute();
    $selected_report = $st->get_result()->fetch_assoc();
    $st->close();

    $chat_q = $conn->prepare("SELECT * FROM issue_messages WHERE report_id=? ORDER BY created_at ASC");
    $chat_q->bind_param("i", $selected_report_id);
    $chat_q->execute();
    $chat_res = $chat_q->get_result();
} else {
    $chat_res = null;
}

/* Alert colors */
$bg_color     = ($msg_type === 'error') ? '#f8d7da' : '#d4edda';
$text_color   = ($msg_type === 'error') ? '#721c24' : '#155724';
$border_color = ($msg_type === 'error') ? '#f5c6cb' : '#c3e6cb';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin | Help Issues</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <style>
    .tab-btn { padding: 12px 24px; cursor: pointer; border: none; background: #eee; font-size:15px; margin-right:2px; border-radius:5px 5px 0 0; transition: 0.3s; }
    .tab-btn:hover { background: #ddd; }
    .tab-btn.active { background: #3C5B6F; color: white; }
    .tab-content { display: none; padding: 25px; border: 1px solid #ddd; margin-top: -1px; border-radius: 0 5px 5px 5px; background: white; }
    .tab-content.active { display: block; }

    .styled-table { width:100%; border-collapse: collapse; margin-top: 10px; }
    .styled-table th, .styled-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align:left; font-size: 14px; }
    .styled-table th { background: #f8f9fa; }

    .action-select { padding:8px 10px; border-radius:8px; border:1px solid #ddd; }
    .btn-action { padding:8px 14px; border:none; border-radius:8px; background:#3C5B6F; color:#fff; cursor:pointer; font-weight:600; }
    .btn-action:hover { opacity:0.9; }

    /* Chat UI */
    .chat-wrap { display:grid; grid-template-columns: 320px 1fr; gap: 16px; }
    .chat-list { border:1px solid #eee; border-radius:10px; overflow:hidden; background:#fff; }
    .chat-list .item { padding:12px; border-bottom:1px solid #f1f1f1; cursor:pointer; }
    .chat-list .item:hover { background:#f8f9fa; }
    .chat-list .item.active { background:#eaf1f6; }

    .chat-box { border:1px solid #eee; border-radius:10px; background:#fff; display:flex; flex-direction:column; height:520px; overflow:hidden; }
    .chat-header { padding:12px 14px; border-bottom:1px solid #f1f1f1; font-weight:700; color:#3C5B6F; }
    .chat-messages { flex:1; padding:14px; overflow:auto; background:#fbfbfb; }
    .bubble { max-width:70%; padding:10px 12px; border-radius:12px; margin:8px 0; font-size:14px; line-height:1.3; }
    .bubble.admin { background:#3C5B6F; color:#fff; margin-left:auto; border-bottom-right-radius:4px; }
    .bubble.user { background:#fff; border:1px solid #eee; color:#222; margin-right:auto; border-bottom-left-radius:4px; }
    .chat-time { font-size:11px; opacity:0.7; margin-top:4px; }

    .chat-input { display:flex; gap:10px; padding:12px; border-top:1px solid #f1f1f1; background:#fff; }
    .chat-input textarea { flex:1; resize:none; height:44px; padding:10px 12px; border-radius:10px; border:1px solid #ddd; }
    .chat-input button { min-width:110px; border:none; border-radius:10px; background:#28a745; color:#fff; font-weight:700; cursor:pointer; }
    .chat-input button:hover { opacity:0.9; }

    @media(max-width: 1100px){
      .chat-wrap{ grid-template-columns:1fr; }
      .chat-box{ height:520px; }
    }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<section class="home-section">
  <div class="welcome-text">
    <i class='bx bx-support' style="font-size:24px; vertical-align:middle;"></i>
    Admin Dashboard | Help Issues
  </div>
  <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 20px;">

  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
    <button class="btn-return" onclick="window.location.href='admin_page.php'">
      <i class='bx bx-arrow-back'></i> Return
    </button>
  </div>

  <?php if (!empty($message)): ?>
    <div class="alert" style="background: <?= $bg_color ?>; color: <?= $text_color ?>; border: 1px solid <?= $border_color ?>; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <!-- Tabs -->
  <div style="margin-bottom: 0;">
    <button class="tab-btn active" onclick="openTab(event, 'reportsTab')">
      <i class='bx bx-table'></i> Reports
    </button>
    <button class="tab-btn" onclick="openTab(event, 'chatTab')">
      <i class='bx bx-message-dots'></i> Communication
    </button>
  </div>

  <!-- TAB 1: REPORTS TABLE -->
  <div id="reportsTab" class="tab-content active">
    <h3 style="color:#3C5B6F; margin-top:0;"><i class='bx bx-list-ul'></i> Misconduct / Help Reports</h3>

    <table class="styled-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Proposal</th>
          <th>Reviewer</th>
          <th>Researcher</th>
          <th>Category</th>
          <th>Details</th>
          <th>Status</th>
          <th>Created</th>
          <th>Take Action</th>
          <th>Chat</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($reports_q && $reports_q->num_rows > 0): ?>
        <?php while($r = $reports_q->fetch_assoc()): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['proposal_id'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['reviewer_email'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['researcher_email'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['category'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['details'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['status'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['created_at'] ?? '-') ?></td>

            <td>
              <form method="POST" style="display:flex; gap:8px; align-items:center; margin:0;">
                <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                <select name="report_action" class="action-select" required>
                  <option value="">Choose</option>
                  <option value="warn_user">Warn User</option>
                  <option value="remove_user">Remove User</option>
                  <option value="mark_resolved">Mark Resolved</option>
                </select>
                <button class="btn-action" type="submit">Apply</button>
              </form>
            </td>

            <td>
              <a class="btn-action" style="text-decoration:none; display:inline-block;"
                 href="help_issues.php?report_id=<?= (int)$r['id'] ?>#chatTab">
                 Open
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="10" style="text-align:center;color:#999;">No reports found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- TAB 2: CHAT -->
  <div id="chatTab" class="tab-content">
    <h3 style="color:#3C5B6F; margin-top:0;"><i class='bx bx-message-dots'></i> Communication</h3>

    <div class="chat-wrap">

      <!-- Left list -->
      <div class="chat-list">
        <?php
          // list all reports as "threads"
          $list_q = $conn->query("SELECT id, category, reviewer_email, researcher_email, status, created_at FROM misconduct_reports ORDER BY created_at DESC");
          if ($list_q && $list_q->num_rows > 0):
            while($t = $list_q->fetch_assoc()):
              $active = ($selected_report_id === (int)$t['id']) ? "active" : "";
        ?>
          <div class="item <?= $active ?>" onclick="window.location.href='help_issues.php?report_id=<?= (int)$t['id'] ?>#chatTab'">
            <div style="font-weight:700;">#<?= (int)$t['id'] ?> • <?= htmlspecialchars($t['category'] ?? '-') ?></div>
            <div style="font-size:12px; color:#666; margin-top:3px;">
              Reporter: <?= htmlspecialchars($t['reviewer_email'] ?? '-') ?><br>
              Target: <?= htmlspecialchars($t['researcher_email'] ?? '-') ?>
            </div>
            <div style="font-size:12px; color:#888; margin-top:6px;">
              <?= htmlspecialchars($t['status'] ?? '-') ?> • <?= htmlspecialchars($t['created_at'] ?? '-') ?>
            </div>
          </div>
        <?php
            endwhile;
          else:
            echo "<div class='item'>No report threads.</div>";
          endif;
        ?>
      </div>

      <!-- Right chat box -->
      <div class="chat-box">
        <div class="chat-header">
          <?php if ($selected_report): ?>
            Report #<?= (int)$selected_report['id'] ?> • <?= htmlspecialchars($selected_report['category'] ?? '-') ?>
          <?php else: ?>
            Select a report from the left to view conversation
          <?php endif; ?>
        </div>

        <div class="chat-messages" id="chatMessages">
          <?php if ($selected_report && $chat_res): ?>
            <?php if ($chat_res->num_rows > 0): ?>
              <?php while($m = $chat_res->fetch_assoc()): ?>
                <?php $isAdmin = (strtolower($m['sender_role']) === 'admin'); ?>
                <div class="bubble <?= $isAdmin ? 'admin' : 'user' ?>">
                  <?= nl2br(htmlspecialchars($m['message'])) ?>
                  <div class="chat-time">
                    <?= htmlspecialchars($m['sender_email']) ?> • <?= htmlspecialchars($m['created_at']) ?>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <p style="color:#888; margin:0;">No messages yet.</p>
            <?php endif; ?>
          <?php else: ?>
            <p style="color:#888; margin:0;">Choose a report thread to open chat.</p>
          <?php endif; ?>
        </div>

        <?php if ($selected_report): ?>
          <form method="POST" class="chat-input">
            <input type="hidden" name="report_id" value="<?= (int)$selected_report['id'] ?>">
            <textarea name="chat_message" placeholder="Type a reply..." required></textarea>
            <button type="submit" name="send_message">Send</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

</section>

<script>
function openTab(evt, tabName) {
  var tabcontent = document.getElementsByClassName("tab-content");
  for (var i = 0; i < tabcontent.length; i++) tabcontent[i].style.display = "none";

  var tablinks = document.getElementsByClassName("tab-btn");
  for (var i = 0; i < tablinks.length; i++) tablinks[i].className = tablinks[i].className.replace(" active", "");

  document.getElementById(tabName).style.display = "block";
  evt.currentTarget.className += " active";
}

// Auto-open chat tab if URL contains #chatTab
window.addEventListener("load", () => {
  if (window.location.hash === "#chatTab") {
    const btns = document.getElementsByClassName("tab-btn");
    if (btns.length >= 2) {
      // simulate click to open chat tab
      openTab({ currentTarget: btns[1] }, 'chatTab');
      btns[1].className += " active";
    }
  }
  // scroll chat bottom
  const chat = document.getElementById("chatMessages");
  if (chat) chat.scrollTop = chat.scrollHeight;
});
</script>

<script src="script.js"></script>
</body>
</html>
