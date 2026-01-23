<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

// Fetch logs (latest first)
$logs_q = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC, id DESC");

// Also fetch logs into array for timeline grouping
$logs = [];
if ($logs_q) {
    while($row = $logs_q->fetch_assoc()){
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Activity Logs</title>
<link rel="stylesheet" href="style.css">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<style>
/* ===== Tabs (inline like your researcher dashboard) ===== */
.tab-btn{
  padding:12px 24px; cursor:pointer; border:none; background:#eee;
  font-size:15px; margin-right:2px; border-radius:5px 5px 0 0; transition:.3s;
}
.tab-btn:hover{ background:#ddd; }
.tab-btn.active{ background:#3C5B6F; color:#fff; }

.tab-content{
  display:none; padding:25px; border:1px solid #ddd; margin-top:-1px;
  border-radius:0 5px 5px 5px; background:#fff;
}
.tab-content.active{ display:block; }

/* ===== Return button ===== */
.btn-return{
  background:#3C5B6F; color:white; border:none; padding:10px 18px;
  border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;
}
.btn-return:hover{ opacity:.9; }

/* ===== Table like pic 1 ===== */
.table{
  width:100%; border-collapse: collapse; font-size:14px;
}
.table th,.table td{
  padding:12px; border-bottom:1px solid #e5e7eb; text-align:left; vertical-align:top;
}
.table th{ background:#f8f9fa; color:#333; font-weight:700; }
.small{ font-size:12px; color:#666; }

.badge{
  display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700;
  border:1px solid #e5e7eb; background:#f8f9fa;
}
.badge-create{ background:#d4edda; color:#155724; border-color:#c3e6cb; }
.badge-update{ background:#cce5ff; color:#004085; border-color:#b8daff; }
.badge-delete{ background:#f8d7da; color:#721c24; border-color:#f5c6cb; }
.badge-complete{ background:#fff3cd; color:#856404; border-color:#ffeeba; }
.badge-assign{ background:#d1ecf1; color:#0c5460; border-color:#bee5eb; }

/* ===== Timeline like pic 2 ===== */
.timeline-wrap{
  max-width: 980px;
  margin: 0 auto;
}
.timeline-date{
  font-weight:800; color:#3C5B6F; margin:22px 0 10px;
}
.timeline-item{
  display:flex; gap:14px; padding:12px 14px; border:1px solid #e5e7eb;
  border-radius:10px; background:#fff; margin-bottom:10px;
}
.timeline-dot{
  width:38px; height:38px; border-radius:50%;
  background:#3C5B6F; color:#fff;
  display:flex; align-items:center; justify-content:center;
  flex:0 0 38px;
}
.timeline-body{ flex:1; }
.timeline-top{
  display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;
}
.timeline-title{
  font-weight:800; color:#333;
}
.timeline-meta{
  color:#777; font-size:12px;
}
.timeline-desc{
  margin-top:6px; color:#555; font-size:13px; line-height:1.5;
}
</style>
</head>

<body>
<?php include 'sidebar.php'; ?>

<section class="home-section">
  <div class="welcome-text">
    <i class='bx bx-notepad' style="font-size:24px; vertical-align:middle;"></i>
    Admin Dashboard | Activity Logs
  </div>
  <hr style="border:1px solid #3C5B6F;opacity:.3; margin-bottom:15px;">

  <button class="btn-return" onclick="location.href='admin_page.php'">
    <i class='bx bx-arrow-back'></i> Return
  </button>

  <!-- Tabs -->
  <div style="margin-top:20px;">
    <button class="tab-btn active" onclick="openTab(event,'tabTable')">
      <i class='bx bx-table'></i> Logs Table
    </button>
    <button class="tab-btn" onclick="openTab(event,'tabTimeline')">
      <i class='bx bx-time-five'></i> Activity Timeline
    </button>
  </div>

  <!-- TAB 1: TABLE -->
  <div id="tabTable" class="tab-content active">
    <h3 style="color:#3C5B6F; margin-top:0;"><i class='bx bx-list-ul'></i> System Activity Log</h3>

    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Actor</th>
          <th>IP</th>
          <th>Action</th>
          <th>Entity</th>
          <th>Label</th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>
      <?php if (count($logs) > 0): ?>
        <?php foreach($logs as $l): ?>
          <?php
            $action = strtoupper($l['action'] ?? '');
            $badgeClass = "badge";
            if ($action === "CREATE") $badgeClass .= " badge-create";
            else if ($action === "UPDATE") $badgeClass .= " badge-update";
            else if ($action === "DELETE") $badgeClass .= " badge-delete";
            else if ($action === "COMPLETE") $badgeClass .= " badge-complete";
            else if ($action === "ASSIGN") $badgeClass .= " badge-assign";
          ?>
          <tr>
            <td>
              <?= htmlspecialchars($l['created_at']) ?>
            </td>
            <td>
              <strong><?= htmlspecialchars($l['actor_email']) ?></strong><br>
              <span class="small"><?= htmlspecialchars($l['actor_role']) ?></span>
            </td>
            <td><?= htmlspecialchars($l['ip_address'] ?? '-') ?></td>
            <td><span class="<?= $badgeClass ?>"><?= htmlspecialchars($action) ?></span></td>
            <td><?= htmlspecialchars($l['entity_type'] ?? '-') ?><?= $l['entity_id'] ? " #".(int)$l['entity_id'] : "" ?></td>
            <td><?= htmlspecialchars($l['label'] ?? '-') ?></td>
            <td><?= htmlspecialchars($l['description'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7" style="text-align:center;color:#999;">No activity logs yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- TAB 2: TIMELINE -->
  <div id="tabTimeline" class="tab-content">
    <h3 style="color:#3C5B6F; margin-top:0;"><i class='bx bx-time-five'></i> Activity Timeline</h3>

    <div class="timeline-wrap">
      <?php
      if (count($logs) === 0) {
          echo "<p style='color:#999;'>No activity logs yet.</p>";
      } else {
          $currentDate = "";
          foreach($logs as $l){
              $dateKey = date('F d, Y', strtotime($l['created_at']));
              if ($dateKey !== $currentDate){
                  $currentDate = $dateKey;
                  echo "<div class='timeline-date'>{$currentDate}</div>";
              }

              $action = strtoupper($l['action'] ?? '');
              $entity = strtoupper($l['entity_type'] ?? 'SYSTEM');
              $label  = $l['label'] ?? '';
              $desc   = $l['description'] ?? '';
              $who    = $l['actor_email'] ?? '';
              $time   = date('h:i A', strtotime($l['created_at']));

              // icon letter
              $letter = substr($entity, 0, 1);
              ?>
              <div class="timeline-item">
                <div class="timeline-dot"><?= htmlspecialchars($letter) ?></div>
                <div class="timeline-body">
                  <div class="timeline-top">
                    <div class="timeline-title">
                      <?= htmlspecialchars($action) ?> • <?= htmlspecialchars($entity) ?>
                      <?= $l['entity_id'] ? "#".(int)$l['entity_id'] : "" ?>
                      <?= $label ? " — ".htmlspecialchars($label) : "" ?>
                    </div>
                    <div class="timeline-meta">
                      <?= htmlspecialchars($time) ?> • <?= htmlspecialchars($who) ?>
                    </div>
                  </div>
                  <div class="timeline-desc">
                    <?= htmlspecialchars($desc ?: '-') ?>
                  </div>
                </div>
              </div>
              <?php
          }
      }
      ?>
    </div>
  </div>

</section>

<script>
function openTab(evt, tabName) {
  document.querySelectorAll(".tab-content").forEach(t => t.style.display="none");
  document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
  document.getElementById(tabName).style.display="block";
  evt.currentTarget.classList.add("active");
}

// ensure first tab visible
document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("tabTable").style.display = "block";
});
</script>

</body>
</html>
