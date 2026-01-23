<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

/* Fetch proposals */
$props = $conn->query("SELECT * FROM proposals ORDER BY id DESC");

/* Fetch status count for chart */
$status = [];
$q = $conn->query("SELECT status, COUNT(*) total FROM proposals GROUP BY status");
while($r = $q->fetch_assoc()){
    $status[$r['status']] = $r['total'];
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin | Proposals</title>
<link rel="stylesheet" href="style.css">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* ===== TABS (INLINE, SAME AS RESEARCHER DASHBOARD) ===== */
.tab-btn{
  padding:12px 24px;
  cursor:pointer;
  border:none;
  background:#eee;
  font-size:15px;
  margin-right:2px;
  border-radius:5px 5px 0 0;
}
.tab-btn:hover{background:#ddd;}
.tab-btn.active{background:#3C5B6F;color:white;}
.tab-content{
  display:none;
  padding:25px;
  border:1px solid #ddd;
  margin-top:-1px;
  border-radius:0 5px 5px 5px;
  background:white;
}
.tab-content.active{display:block;}

/* ===== TABLE ===== */
.table{
  width:100%;
  border-collapse:collapse;
}
.table th,.table td{
  padding:12px;
  border-bottom:1px solid #e5e7eb;
  text-align:left;
}
.table th{background:#f8f9fa;}

/* ===== RETURN BUTTON ===== */
.btn-return{
  background:#3C5B6F;
  color:white;
  border:none;
  padding:10px 18px;
  border-radius:8px;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  gap:6px;
}
.btn-return:hover{opacity:.9}

/* ===== Custom chart legend ===== */
.chart-legend {
  list-style: none;
  padding: 0;
  margin: 0;
}

.chart-legend li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 0;
  font-size: 14px;
  border-bottom: 1px solid #eee;
}

.chart-legend li:last-child {
  border-bottom: none;
}

.legend-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.legend-count {
  font-weight: 600;
  color: #3C5B6F;
}

</style>
</head>

<body>
<?php include 'sidebar.php'; ?>

<section class="home-section">
  <div class="welcome-text">
    <i class='bx bx-file'></i> Admin Dashboard | Proposals
  </div>
  <hr style="border:1px solid #3C5B6F;opacity:.3">

  <button class="btn-return" onclick="location.href='admin_page.php'">
    <i class='bx bx-arrow-back'></i> Return
  </button>

  <!-- TABS -->
  <div style="margin-top:20px">
    <button class="tab-btn active" onclick="openTab(event,'tableTab')">
      <i class='bx bx-table'></i> Proposal Table
    </button>
    <button class="tab-btn" onclick="openTab(event,'chartTab')">
      <i class='bx bx-pie-chart-alt-2'></i> Status Chart
    </button>
  </div>

  <!-- TAB 1: TABLE -->
  <div id="tableTab" class="tab-content active">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Researcher</th>
          <th>Status</th>
          <th>Created</th>
          <th>File</th>
        </tr>
      </thead>
      <tbody>
        <?php while($p=$props->fetch_assoc()): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['title']) ?></td>
          <td><?= htmlspecialchars($p['researcher_email']) ?></td>
          <td><?= $p['status'] ?></td>
          <td><?= $p['created_at'] ?></td>
          <td>
            <?php if($p['file_path']): ?>
              <a href="<?= $p['file_path'] ?>" target="_blank">View</a>
            <?php else: ?>-<?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- TAB 2: CHART -->
 <div id="chartTab" class="tab-content">
  <div style="display:flex; gap:40px; align-items:center; justify-content:center; flex-wrap:wrap;">

    <!-- Pie chart -->
    <div style="max-width:360px;">
      <canvas id="statusChart"></canvas>
    </div>

    <!-- Custom legend list -->
    <div id="chartLegend" style="min-width:260px;"></div>

  </div>
</div>

</section>

<script>
let statusChart = null;

function openTab(evt, id){
  // hide all tab contents
  document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');

  // remove active class from all tab buttons
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

  // show selected tab
  const tab = document.getElementById(id);
  tab.style.display = 'block';
  evt.currentTarget.classList.add('active');

  // IMPORTANT: render chart only when chart tab is opened
  if(id === 'chartTab'){
    renderStatusChart();
  }
}

function renderStatusChart(){
  if(statusChart) return;

  const labels = <?= json_encode(array_keys($status)) ?>;
  const data   = <?= json_encode(array_values($status)) ?>;

  const colors = [
    '#4e73df', // ASSIGNED
    '#1cc88a', // APPROVED
    '#e74a3b', // REJECTED
    '#f6c23e', // RESUBMITTED
    '#36b9cc', // REQUIRES_AMENDMENT
    '#9b59b6'  // APPEAL_REJECTED
  ];

  const ctx = document.getElementById('statusChart').getContext('2d');

  statusChart = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: colors
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false } // 🔴 disable default legend
      }
      
    }

    
  });

  renderLegend(labels, data, colors);
}

function renderLegend(labels, data, colors){
  const legend = document.getElementById('chartLegend');
  let html = '<ul class="chart-legend">';

  labels.forEach((label, i) => {
    html += `
      <li>
        <div class="legend-left">
          <span class="legend-color" style="background:${colors[i]}"></span>
          ${label.replaceAll('_',' ')}
        </div>
        <span class="legend-count">${data[i]}</span>
      </li>
    `;
  });

  html += '</ul>';
  legend.innerHTML = html;
}

// Make sure the default active tab is visible on load
document.addEventListener("DOMContentLoaded", () => {
  // Ensure the first tab is visible (tableTab)
  document.getElementById('tableTab').style.display = 'block';
});
</script>


</body>
</html>
