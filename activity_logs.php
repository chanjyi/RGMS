<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$dashboardLink = 'admin_page.php';

// Fetch filter parameters
$keyword  = trim($_GET['q'] ?? '');
$actionF  = trim($_GET['action'] ?? '');
$roleF    = trim($_GET['role'] ?? '');
$dateFrom = trim($_GET['from'] ?? '');
$dateTo   = trim($_GET['to'] ?? '');

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 20);
$allowedPerPage = [10, 20, 50, 100];
if (!in_array($perPage, $allowedPerPage)) $perPage = 20;

$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
$types  = "";

if ($actionF !== "") {
    // case-insensitive match
    $where[] = "UPPER(action) = UPPER(?)";
    $params[] = $actionF;
    $types .= "s";
}

if ($roleF !== "") {
    // case-insensitive match
    $where[] = "LOWER(actor_role) = LOWER(?)";
    $params[] = $roleF;
    $types .= "s";
}

if ($dateFrom !== "") {
    $where[] = "created_at >= ?";
    $params[] = $dateFrom . " 00:00:00";
    $types .= "s";
}
if ($dateTo !== "") {
    $where[] = "created_at <= ?";
    $params[] = $dateTo . " 23:59:59";
    $types .= "s";
}

if ($keyword !== "") {
    $where[] = "(actor_email LIKE ? OR label LIKE ? OR description LIKE ? OR entity_type LIKE ? OR ip_address LIKE ?)";
    $k = "%" . $keyword . "%";
    array_push($params, $k, $k, $k, $k, $k);
    $types .= "sssss";
}

$whereSql = !empty($where) ? ("WHERE " . implode(" AND ", $where)) : "";

//-------- Get total rows --------
$countSql  = "SELECT COUNT(*) AS total FROM activity_logs $whereSql";
$countStmt = $conn->prepare($countSql);
if (!$countStmt) die("Prepare failed: " . $conn->error);

if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

/* -------- Fetch rows -------- */
$dataSql = "SELECT * FROM activity_logs
            $whereSql
            ORDER BY created_at DESC, id DESC
            LIMIT ? OFFSET ?";

$dataStmt = $conn->prepare($dataSql);
if (!$dataStmt) die("Prepare failed: " . $conn->error);

$types2 = $types . "ii";
$params2 = $params;
$params2[] = $perPage;
$params2[] = $offset;

$dataStmt->bind_param($types2, ...$params2);
$dataStmt->execute();
$res = $dataStmt->get_result();

$logs = [];
while ($row = $res->fetch_assoc()) $logs[] = $row;
$dataStmt->close();

// Helper functions
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Returns CSS class for action badge
function badgeClass($action){
    $action = strtoupper($action);
    $base = "badge";
    if ($action === "CREATE") return "$base badge-create";
    if ($action === "UPDATE") return "$base badge-update";
    if ($action === "DELETE") return "$base badge-delete";
    if ($action === "COMPLETE") return "$base badge-complete";
    if ($action === "ASSIGN") return "$base badge-assign";
    if ($action === "LOGIN") return "$base badge-login";
    if ($action === "LOGOUT") return "$base badge-logout";
    return $base;
}

// Builds query string with overrides
function buildQuery(array $override = []){
    $q = $_GET;
    foreach($override as $k => $v){
        if ($v === null) unset($q[$k]);
        else $q[$k] = $v;
    }
    return "?" . http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Activity Logs</title>
<link rel="stylesheet" href="styling/style.css">
<link rel="stylesheet" href="styling/dashboard.css">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<style>
.tab-btn{
  padding:12px 24px; cursor:pointer; border:none; background:#eee;
  font-size:15px; margin-right:2px; border-radius:8px 8px 0 0; transition:.2s;
}
.tab-btn:hover{ background:#e5e5e5; }
.tab-btn.active{ background:#3C5B6F; color:#fff; }

.tab-content{
  display:none; padding:20px; border:1px solid #e5e7eb; margin-top:-1px;
  border-radius:0 10px 10px 10px; background:#fff;
}
.tab-content.active{ display:block; }

.filters{
  background:#fff; border:1px solid #e5e7eb; border-radius:12px;
  padding:14px; display:flex; gap:10px; flex-wrap:wrap; align-items:end;
  margin:14px 0 16px;
}
.filters .group{ display:flex; flex-direction:column; gap:6px; }
.filters label{ font-size:12px; color:#667085; font-weight:700; }
.filters input, .filters select{
  padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px;
  outline:none; font-size:14px; min-width:170px;
}
.filters input:focus, .filters select:focus{ border-color:#3C5B6F; }

.btn{
  padding:10px 14px; border:none; border-radius:10px; cursor:pointer;
  font-weight:700; display:inline-flex; gap:8px; align-items:center;
}
.btn-primary{ background:#3C5B6F; color:#fff; }
.btn-ghost{ background:#f2f4f7; color:#344054; text-decoration:none; }
.btn-primary:hover{ opacity:.92; }
.btn-ghost:hover{ background:#eaecf0; }

.table{ width:100%; border-collapse: collapse; font-size:14px; }
.table th,.table td{ padding:12px; border-bottom:1px solid #eef2f6; text-align:left; vertical-align:top; }
.table th{ background:#f8f9fa; color:#333; font-weight:800; }
.small{ font-size:12px; color:#667085; }

.badge{
  display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:800;
  border:1px solid #e5e7eb; background:#f8f9fa;
}
.badge-create{ background:#d4edda; color:#155724; border-color:#c3e6cb; }
.badge-update{ background:#cce5ff; color:#004085; border-color:#b8daff; }
.badge-delete{ background:#f8d7da; color:#721c24; border-color:#f5c6cb; }
.badge-complete{ background:#fff3cd; color:#856404; border-color:#ffeeba; }
.badge-assign{ background:#d1ecf1; color:#0c5460; border-color:#bee5eb; }
.badge-login{ background:#e0f2fe; color:#075985; border-color:#bae6fd; }
.badge-logout{ background:#fee2e2; color:#991b1b; border-color:#fecaca; }

.timeline-wrap{ max-width: 980px; margin: 0 auto; }
.timeline-date{ font-weight:900; color:#3C5B6F; margin:22px 0 10px; }
.timeline-item{
  display:flex; gap:14px; padding:12px 14px; border:1px solid #e5e7eb;
  border-radius:12px; background:#fff; margin-bottom:10px;
}
.timeline-dot{
  width:38px; height:38px; border-radius:50%;
  background:#3C5B6F; color:#fff; display:flex; align-items:center; justify-content:center;
  flex:0 0 38px; font-weight:900;
}
.timeline-body{ flex:1; }
.timeline-top{ display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.timeline-title{ font-weight:900; color:#101828; }
.timeline-meta{ color:#667085; font-size:12px; }
.timeline-desc{ margin-top:6px; color:#475467; font-size:13px; line-height:1.5; }

.pager{ display:flex; gap:8px; justify-content:flex-end; align-items:center; margin-top:14px; flex-wrap:wrap; }
.pager a, .pager span{
  padding:8px 12px; border:1px solid #e5e7eb; border-radius:10px; text-decoration:none;
  color:#344054; background:#fff; font-weight:800; font-size:13px;
}
.pager a:hover{ background:#f2f4f7; }
.pager .active{ background:#3C5B6F; color:#fff; border-color:#3C5B6F; }
</style>
</head>

<body>
<?php include 'sidebar.php'; ?>

<section class="home-section">

  <a href="<?= h($dashboardLink) ?>" class="btn-back"
     style="display:inline-flex;align-items:center;text-decoration:none;color:#3C5B6F;font-weight:700;margin-bottom:15px;">
    <i class='bx bx-left-arrow-alt' style="font-size: 20px; margin-right: 5px;"></i>
    Back to Dashboard
  </a>

  <div class="welcome-text">
    <i class='bx bx-notepad' style="font-size:24px; vertical-align:middle;"></i>
    Admin Dashboard | Activity Logs
  </div>
  <hr style="border:1px solid #3C5B6F;opacity:.3; margin-bottom:15px;">

  <form class="filters" method="GET" action="">

    <div class="group">
      <label>Action</label>
      <select name="action">
        <option value="">All</option>
        <?php
          $actions = ["CREATE","UPDATE","DELETE","ASSIGN","COMPLETE","LOGIN","LOGOUT","APPROVE","REJECT"];
          foreach($actions as $a){
              $sel = (strcasecmp($actionF, $a) === 0) ? "selected" : "";
              echo "<option value='".h($a)."' $sel>".h($a)."</option>";
          }
        ?>
      </select>
    </div>

    <div class="group">
      <label>Actor Role</label>
      <select name="role">
        <option value="">All</option>
        <?php
          $roles = ["admin","reviewer","hod","researcher"];
          foreach($roles as $r){
              $sel = (strcasecmp($roleF, $r) === 0) ? "selected" : "";
              echo "<option value='".h($r)."' $sel>".h(ucfirst($r))."</option>";
          }
        ?>
      </select>
    </div>

    <div class="group">
      <label>Date From</label>
      <input type="date" name="from" value="<?= h($dateFrom) ?>">
    </div>

    <div class="group">
      <label>Date To</label>
      <input type="date" name="to" value="<?= h($dateTo) ?>">
    </div>

    <div class="group">
      <label>Per Page</label>
      <select name="per_page">
        <?php foreach([10,20,50,100] as $n): ?>
          <option value="<?= $n ?>" <?= ($perPage===$n) ? "selected" : "" ?>><?= $n ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="group" style="min-width:120px;">
      <button class="btn btn-primary" type="submit">
        <i class='bx bx-filter-alt'></i> Apply
      </button>
    </div>

    <div class="group" style="min-width:120px;">
      <a class="btn btn-ghost" href="<?= h($_SERVER['PHP_SELF']) ?>">
        <i class='bx bx-reset'></i> Reset
      </a>
    </div>
  </form>

  <div style="margin-top:10px;">
    <button class="tab-btn active" data-tab="tabTable">
      <i class='bx bx-table'></i> Logs Table
    </button>
    <button class="tab-btn" data-tab="tabTimeline">
      <i class='bx bx-time-five'></i> Activity Timeline
    </button>
  </div>

  <div id="tabTable" class="tab-content active">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
      <h3 style="color:#3C5B6F; margin:0;"><i class='bx bx-list-ul'></i> System Activity Log</h3>
      <div class="small">Showing <b><?= count($logs) ?></b> of <b><?= $totalRows ?></b> results</div>
    </div>

    <div style="overflow:auto; margin-top:12px;">
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
              $badge  = badgeClass($action);
              $entity = $l['entity_type'] ?? '-';
              $eid    = !empty($l['entity_id']) ? (" #".(int)$l['entity_id']) : "";
            ?>
            <tr>
              <td>
                <b><?= h(date('Y-m-d', strtotime($l['created_at']))) ?></b><br>
                <span class="small"><?= h(date('h:i A', strtotime($l['created_at']))) ?></span>
              </td>
              <td>
                <b><?= h($l['actor_email'] ?? '-') ?></b><br>
                <span class="small"><?= h(ucfirst($l['actor_role'] ?? '-')) ?></span>
              </td>
              <td><?= h($l['ip_address'] ?? '-') ?></td>
              <td><span class="<?= h($badge) ?>"><?= h($action ?: '-') ?></span></td>
              <td><?= h($entity) . h($eid) ?></td>
              <td><?= h($l['label'] ?? '-') ?></td>
              <td><?= h($l['description'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" style="text-align:center;color:#999;">No activity logs found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="pager">
        <?php if ($page > 1): ?>
          <a href="<?= h(buildQuery(['page' => 1])) ?>">&laquo; First</a>
          <a href="<?= h(buildQuery(['page' => $page - 1])) ?>">&lsaquo; Prev</a>
        <?php endif; ?>

        <?php
          $start = max(1, $page - 2);
          $end   = min($totalPages, $page + 2);
          for ($p = $start; $p <= $end; $p++):
        ?>
          <?php if ($p == $page): ?>
            <span class="active"><?= $p ?></span>
          <?php else: ?>
            <a href="<?= h(buildQuery(['page' => $p])) ?>"><?= $p ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?= h(buildQuery(['page' => $page + 1])) ?>">Next &rsaquo;</a>
          <a href="<?= h(buildQuery(['page' => $totalPages])) ?>">Last &raquo;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div id="tabTimeline" class="tab-content">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
      <h3 style="color:#3C5B6F; margin:0;"><i class='bx bx-time-five'></i> Activity Timeline</h3>
      <div class="small">(Timeline uses the same filtered results + pagination)</div>
    </div>

    <div class="timeline-wrap" style="margin-top:12px;">
      <?php
      if (count($logs) === 0) {
          echo "<p style='color:#999;'>No activity logs found.</p>";
      } else {
          $currentDate = "";
          foreach($logs as $l){
              $dateKey = date('F d, Y', strtotime($l['created_at']));
              if ($dateKey !== $currentDate){
                  $currentDate = $dateKey;
                  echo "<div class='timeline-date'>".h($currentDate)."</div>";
              }

              $action = strtoupper($l['action'] ?? '');
              $entity = strtoupper($l['entity_type'] ?? 'SYSTEM');
              $label  = $l['label'] ?? '';
              $desc   = $l['description'] ?? '';
              $who    = $l['actor_email'] ?? '';
              $time   = date('h:i A', strtotime($l['created_at']));
              $letter = substr($entity, 0, 1);
              $eid    = !empty($l['entity_id']) ? "#".(int)$l['entity_id'] : "";
              ?>
              <div class="timeline-item">
                <div class="timeline-dot"><?= h($letter) ?></div>
                <div class="timeline-body">
                  <div class="timeline-top">
                    <div class="timeline-title">
                      <?= h($action ?: '-') ?> • <?= h($entity) ?> <?= h($eid) ?>
                      <?= $label ? " — ".h($label) : "" ?>
                    </div>
                    <div class="timeline-meta">
                      <?= h($time) ?> • <?= h($who) ?>
                    </div>
                  </div>
                  <div class="timeline-desc"><?= h($desc ?: '-') ?></div>
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
document.querySelectorAll(".tab-btn").forEach(btn => {
  btn.addEventListener("click", () => {
    const tabId = btn.dataset.tab;
    document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
    document.querySelectorAll(".tab-content").forEach(t => t.classList.remove("active"));
    btn.classList.add("active");
    document.getElementById(tabId).classList.add("active");
  });
});
</script>
</body>
</html>
