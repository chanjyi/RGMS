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

/* ========= Handle Actions (Report Action) ========= */
if (isset($_POST['report_action'], $_POST['report_id'])) {
    $report_id = (int)$_POST['report_id'];
    $action = $_POST['report_action'];

    $rep_stmt = $conn->prepare("SELECT id, reviewer_email, researcher_email, status FROM misconduct_reports WHERE id = ?");
    $rep_stmt->bind_param("i", $report_id);
    $rep_stmt->execute();
    $rep = $rep_stmt->get_result()->fetch_assoc();
    $rep_stmt->close();

    if (!$rep) {
        $msg_type = "error";
        $message = "Report not found.";
    } else {
        // adjust if target is different
        $target_email = $rep['researcher_email'];

        if ($action === 'mark_resolved') {
            $up = $conn->prepare("UPDATE misconduct_reports SET status='RESOLVED' WHERE id=?");
            $up->bind_param("i", $report_id);
            $up->execute();
            $up->close();

            $msg_type = "success";
            $message = "Report marked as resolved.";

        } elseif ($action === 'remove_user') {
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

/* ========= Upload Helpers ========= */
function ensure_dir($path) {
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function safe_filename($name) {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    return $name ?: ("file_" . time());
}

function process_upload_group($conn, $message_id, $files, $absUploadDir, $relUploadDir) {
    if (!isset($files['name']) || empty($files['name'][0])) return;

    $allowedExt = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip'];
    $maxSize = 10 * 1024 * 1024; // 10MB each

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;

        $origName = $files['name'][$i];
        $tmpPath  = $files['tmp_name'][$i];
        $size     = (int)($files['size'][$i] ?? 0);

        if ($size <= 0 || $size > $maxSize) continue;

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) continue;

        $safeOrig = safe_filename($origName);
        $unique = $message_id . "_" . time() . "_" . bin2hex(random_bytes(4)) . "_" . $safeOrig;

        $absPath = rtrim($absUploadDir, "/\\") . DIRECTORY_SEPARATOR . $unique;
        $relPath = rtrim($relUploadDir, "/") . "/" . $unique;

        if (move_uploaded_file($tmpPath, $absPath)) {
            $mime = @mime_content_type($absPath);
            if ($mime === false) $mime = null;

            $att = $conn->prepare("
                INSERT INTO issue_attachments (message_id, file_path, original_name, mime_type)
                VALUES (?, ?, ?, ?)
            ");
            $att->bind_param("isss", $message_id, $relPath, $origName, $mime);
            $att->execute();
            $att->close();
        }
    }
}

/* ========= Handle Send Message (with attachments) ========= */
if (isset($_POST['send_message'], $_POST['report_id'])) {
    $report_id = (int)$_POST['report_id'];
    $chat_message = trim($_POST['chat_message'] ?? '');

    $hasFiles =
        (!empty($_FILES['attachments']['name'][0]) ||
         !empty($_FILES['folder_attachments']['name'][0]));

    if ($report_id <= 0 || ($chat_message === '' && !$hasFiles)) {
        $msg_type = "error";
        $message = "Message cannot be empty (unless you attach files).";
    } else {
        $ins = $conn->prepare("
            INSERT INTO issue_messages (report_id, sender_role, sender_email, message)
            VALUES (?, 'admin', ?, ?)
        ");
        $ins->bind_param("iss", $report_id, $admin_email, $chat_message);
        $ins->execute();
        $message_id = $conn->insert_id;
        $ins->close();

        $absUploadDir = __DIR__ . "/uploads/issues";
        $relUploadDir = "uploads/issues";
        ensure_dir($absUploadDir);

        if (isset($_FILES['attachments'])) {
            process_upload_group($conn, $message_id, $_FILES['attachments'], $absUploadDir, $relUploadDir);
        }
        if (isset($_FILES['folder_attachments'])) {
            process_upload_group($conn, $message_id, $_FILES['folder_attachments'], $absUploadDir, $relUploadDir);
        }

        $msg_type = "success";
        $message = "Message sent.";
    }
}

/* ========= Fetch Reports ========= */
$reports_q = $conn->query("SELECT * FROM misconduct_reports ORDER BY created_at DESC");

/* ========= For Communication Tab ========= */
$selected_report_id = (int)($_GET['report_id'] ?? 0);
$selected_report = null;
$chat_res = null;

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
  <link rel="stylesheet" href="styling/style.css">
  <link rel="stylesheet" href="styling/dashboard.css">
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
    .chat-time { font-size:11px; opacity:0.7; margin-top:6px; }

    /* input row */
    .chat-input { display:flex; gap:10px; padding:12px; border-top:1px solid #f1f1f1; background:#fff; align-items:center; }
    .chat-input textarea { flex:1; resize:none; height:44px; padding:10px 12px; border-radius:10px; border:1px solid #ddd; }

    .chat-attach { display:flex; align-items:center; gap:8px; }
    .attach-btn {
      width: 42px; height: 42px;
      border-radius: 50%;
      border: none;
      background: #3C5B6F;
      color: #fff;
      font-size: 22px;
      cursor: pointer;
      display:flex; align-items:center; justify-content:center;
    }
    .attach-btn:hover { background: #2f4a59; }
    .file-count { font-size:12px; color:#666; white-space:nowrap; }

    .chat-input button.send-btn {
      min-width:110px;
      border:none;
      border-radius:10px;
      background:#28a745;
      color:#fff;
      font-weight:700;
      cursor:pointer;
      height:44px;
    }
    .chat-input button.send-btn:hover { opacity:0.9; }

    @media(max-width: 1100px){
      .chat-wrap{ grid-template-columns:1fr; }
      .chat-box{ height:520px; }
      .file-count{ display:none; }
    }
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
    <i class='bx bx-support' style="font-size:24px; vertical-align:middle;"></i>
    Admin Dashboard | Help Issues
  </div>
  <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 20px;">

  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
   
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

  <!-- TAB 1 -->
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

  <!-- TAB 2 -->
  <div id="chatTab" class="tab-content">
    <h3 style="color:#3C5B6F; margin-top:0;"><i class='bx bx-message-dots'></i> Communication</h3>

    <div class="chat-wrap">

      <!-- Left list -->
      <div class="chat-list">
        <?php
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
        <?php endwhile; else: ?>
          <div class="item">No report threads.</div>
        <?php endif; ?>
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
                  <?php if (!empty($m['message'])): ?>
                    <?= nl2br(htmlspecialchars($m['message'])) ?>
                  <?php endif; ?>

                  <?php
                    $att_q = $conn->prepare("SELECT * FROM issue_attachments WHERE message_id=?");
                    $att_q->bind_param("i", $m['id']);
                    $att_q->execute();
                    $att_res = $att_q->get_result();
                  ?>

                  <?php if ($att_res && $att_res->num_rows > 0): ?>
                    <div style="margin-top:8px;">
                      <?php while($a = $att_res->fetch_assoc()):
                        $path = $a['file_path'];
                        $name = $a['original_name'];
                        $mime = $a['mime_type'] ?? '';
                        $isImg = (strpos($mime, 'image/') === 0);
                      ?>
                        <div style="margin-top:6px;">
                          <?php if ($isImg): ?>
                            <a href="<?= htmlspecialchars($path) ?>" target="_blank" style="display:inline-block; text-decoration:none;">
                              <img src="<?= htmlspecialchars($path) ?>" alt="<?= htmlspecialchars($name) ?>"
                                   style="max-width:220px; border-radius:10px; border:1px solid #eee;">
                            </a>
                            <div style="font-size:12px; opacity:0.85;"><?= htmlspecialchars($name) ?></div>
                          <?php else: ?>
                            <a href="<?= htmlspecialchars($path) ?>" target="_blank"
                               style="text-decoration:underline; font-size:13px; color:inherit;">
                               📎 <?= htmlspecialchars($name) ?>
                            </a>
                          <?php endif; ?>
                        </div>
                      <?php endwhile; ?>
                    </div>
                  <?php endif; ?>

                  <?php $att_q->close(); ?>

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
          <form method="POST" class="chat-input" enctype="multipart/form-data">
            <input type="hidden" name="report_id" value="<?= (int)$selected_report['id'] ?>">

            <textarea name="chat_message" placeholder="Type a reply... (or attach files)"></textarea>

            <div class="chat-attach">
              <input type="file" id="fileInput" name="attachments[]" multiple
                     accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
                     hidden>

              <input type="file" id="folderInput" name="folder_attachments[]" multiple
                     webkitdirectory directory hidden>

              <button type="button" class="attach-btn" onclick="openFilePicker()">
                <i class='bx bx-plus'></i>
              </button>

              <span id="fileCount" class="file-count"></span>
            </div>

            <button type="submit" name="send_message" class="send-btn">Send</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

</section>

<script>
/* Tabs */
function openTab(evt, tabName) {
  var tabcontent = document.getElementsByClassName("tab-content");
  for (var i = 0; i < tabcontent.length; i++) tabcontent[i].style.display = "none";

  var tablinks = document.getElementsByClassName("tab-btn");
  for (var i = 0; i < tablinks.length; i++) tablinks[i].className = tablinks[i].className.replace(" active", "");

  document.getElementById(tabName).style.display = "block";
  evt.currentTarget.className += " active";
}

/* Plus icon upload */
function openFilePicker() {
  document.getElementById('fileInput').click();
}

// right click for folder picker
document.addEventListener('contextmenu', function(e) {
  if (e.target.closest('.attach-btn')) {
    e.preventDefault();
    document.getElementById('folderInput').click();
  }
});

function updateFileCount() {
  const files =
    document.getElementById('fileInput').files.length +
    document.getElementById('folderInput').files.length;

  document.getElementById('fileCount').textContent =
    files > 0 ? files + " file(s) selected" : "";
}

document.getElementById('fileInput').addEventListener('change', updateFileCount);
document.getElementById('folderInput').addEventListener('change', updateFileCount);

/* auto-open chat tab + scroll bottom */
window.addEventListener("load", () => {
  if (window.location.hash === "#chatTab") {
    const btns = document.getElementsByClassName("tab-btn");
    if (btns.length >= 2) {
      openTab({ currentTarget: btns[1] }, 'chatTab');
      btns[1].className += " active";
    }
  }
  const chat = document.getElementById("chatMessages");
  if (chat) chat.scrollTop = chat.scrollHeight;
});
</script>

<script src="script.js"></script>
</body>
</html>
