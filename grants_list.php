<?php
session_start();
require 'config.php';

// Security Check
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}


// 1) All budget items
$items_q = $conn->query("SELECT * FROM budget_items ORDER BY id DESC");

// 2) Summary for charts (group by category)
$sum_q = $conn->query("
    SELECT 
        category,
        SUM(allocated_amount) AS total_allocated,
        SUM(spent_amount) AS total_spent,
        COUNT(*) AS total_items
    FROM budget_items
    GROUP BY category
    ORDER BY category ASC
");

// Prepare arrays for charts
$categories = [];
$allocatedTotals = [];
$spentTotals = [];
$itemCounts = [];

if ($sum_q && $sum_q->num_rows > 0) {
    while($row = $sum_q->fetch_assoc()){
        $categories[] = $row['category'];
        $allocatedTotals[] = (float)$row['total_allocated'];
        $spentTotals[] = (float)$row['total_spent'];
        $itemCounts[] = (int)$row['total_items'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Grants</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>


    <style>
        /* Tabs */
        .tab-btn { padding: 12px 24px; cursor: pointer; border: none; background: #eee; font-size:15px; margin-right:2px; border-radius:5px 5px 0 0; transition: 0.3s; }
        .tab-btn:hover { background: #ddd; }
        .tab-btn.active { background: #3C5B6F; color: white; }
        .tab-content { display: none; padding: 25px; border: 1px solid #ddd; margin-top: -1px; border-radius: 0 5px 5px 5px; background: white; }
        .tab-content.active { display: block; }

        /* Table */
        .styled-table { width:100%; border-collapse: collapse; margin-top: 10px; }
        .styled-table th, .styled-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align:left; font-size: 14px; }
        .styled-table th { background: #f8f9fa; }

        /* Badges */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display:inline-block; }
        .badge-equipment { background:#d1ecf1; color:#0c5460; }
        .badge-materials  { background:#fff3cd; color:#856404; }
        .badge-travel     { background:#d4edda; color:#155724; }
        .badge-personnel  { background:#e2e3e5; color:#383d41; }
        .badge-other      { background:#f8d7da; color:#721c24; }

        /* Chart layout */
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 18px;
        }
        .chart-card {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 16px;
            background: #fff;
            height: 340px;
        }
        .chart-card h4 {
            margin: 0 0 10px;
            color: #3C5B6F;
        }

        @media (max-width: 1100px) {
            .chart-grid { grid-template-columns: 1fr; }
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
        <i class='bx bx-money' style="font-size:24px; vertical-align:middle;"></i>
        Admin Dashboard | Grants
    </div>

    <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 25px;">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
       
    </div>

    <!-- Tabs -->
    <div style="margin-bottom: 0;">
        <button class="tab-btn active" onclick="openTab(event, 'allGrants')">
            <i class='bx bx-list-ul'></i> All Budget Items
        </button>
        <button class="tab-btn" onclick="openTab(event, 'chartsTab')">
            <i class='bx bx-pie-chart-alt-2'></i> Charts
        </button>
    </div>

    <!-- TAB 1: ALL DATA -->
    <div id="allGrants" class="tab-content active">
        <h3 style="color:#3C5B6F; margin-top:0;">
            <i class='bx bx-table'></i> Budget Items (From Database)
        </h3>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proposal ID</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Allocated</th>
                    <th>Spent</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items_q && $items_q->num_rows > 0): ?>
                    <?php while($it = $items_q->fetch_assoc()): ?>
                        <?php
                            $cat = strtolower(trim($it['category'] ?? ''));
                            $badgeClass = "badge";
                            if ($cat === "equipment") $badgeClass .= " badge-equipment";
                            elseif ($cat === "materials") $badgeClass .= " badge-materials";
                            elseif ($cat === "travel") $badgeClass .= " badge-travel";
                            elseif ($cat === "personnel") $badgeClass .= " badge-personnel";
                            else $badgeClass .= " badge-other";
                        ?>
                        <tr>
                            <td><?= (int)$it['id'] ?></td>
                            <td><?= (int)$it['proposal_id'] ?></td>
                            <td><span class="<?= $badgeClass ?>"><?= htmlspecialchars($it['category'] ?? '-') ?></span></td>
                            <td><?= htmlspecialchars($it['description'] ?? '-') ?></td>
                            <td><?= number_format((float)$it['allocated_amount'], 2) ?></td>
                            <td><?= number_format((float)$it['spent_amount'], 2) ?></td>
                            <td><?= htmlspecialchars($it['created_at'] ?? '-') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;color:#999;">No budget items found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- TAB 2: PIE CHARTS -->
    <div id="chartsTab" class="tab-content">
        <h3 style="color:#3C5B6F; margin-top:0;">
            <i class='bx bx-pie-chart-alt-2'></i> Budget Charts
        </h3>

        <?php if (count($categories) === 0): ?>
            <p style="color:#999;">No data available for charts.</p>
        <?php else: ?>
            <div class="chart-grid">
                <div class="chart-card">
                    <h4>Category Distribution (Count of Items)</h4>
                    <canvas id="pieCategoryCount"></canvas>
                </div>

                <div class="chart-card">
                    <h4>Allocated Amount by Category</h4>
                    <canvas id="pieAllocated"></canvas>
                </div>

                <div class="chart-card">
                    <h4>Spent Amount by Category</h4>
                    <canvas id="pieSpent"></canvas>
                </div>
            </div>
        <?php endif; ?>
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
</script>

<script>
const labels = <?= json_encode($categories) ?>;
const countData = <?= json_encode($itemCounts) ?>;
const allocatedData = <?= json_encode($allocatedTotals) ?>;
const spentData = <?= json_encode($spentTotals) ?>;

// Register plugin (required)
Chart.register(ChartDataLabels);

function formatNumber(num){
  return new Intl.NumberFormat('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
}

function makeFancyDonut(canvasId, dataArr, titleText) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;

  const total = dataArr.reduce((a,b) => a + b, 0);

  new Chart(canvas, {
    type: 'pie', 
    data: {
      labels: labels,
      datasets: [{
        data: dataArr,
        borderWidth: 2,
        hoverOffset: 14,     // makes it "pop" on hover
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false, // makes it fill chart-card nicely
      layout: {
        padding: 10
      },
      plugins: {
    legend: {
        position: 'bottom',
        labels: {
        boxWidth: 14,
        padding: 14,
        font: { size: 12, weight: '600' }
        }
    },
    title: {
        display: true,
        text: titleText,
        font: { size: 15, weight: '700' },
        padding: { bottom: 10 }
    },

    datalabels: {
        display: false
    },

    tooltip: {
        callbacks: {
        label: function(ctx){
            const value = ctx.parsed;
            const percent = total > 0 ? (value / total * 100) : 0;
            return ` ${ctx.label}: ${formatNumber(value)} (${percent.toFixed(1)}%)`;
        }
        }
    }
    }

    }
  });
}

// Build charts
makeFancyDonut('pieCategoryCount', countData, 'Category Distribution (Count)');
makeFancyDonut('pieAllocated', allocatedData, 'Allocated Amount by Category');
makeFancyDonut('pieSpent', spentData, 'Spent Amount by Category');
</script>


<script src="script.js"></script>

</body>
</html>
