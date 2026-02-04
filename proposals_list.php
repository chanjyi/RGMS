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
$status_counts = [];
$q = $conn->query("SELECT status, COUNT(*) total FROM proposals GROUP BY status");
while($r = $q->fetch_assoc()){
    $status_counts[$r['status']] = $r['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Proposals Management</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* ===== TABS ===== */
        .tab-btn {
            padding: 12px 24px;
            cursor: pointer;
            border: none;
            background: #eee;
            font-size: 15px;
            margin-right: 2px;
            border-radius: 5px 5px 0 0;
            transition: 0.3s;
        }
        .tab-btn.active { background: #3C5B6F; color: white; }
        
        .tab-content {
            display: none;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 0 5px 5px 5px;
            background: white;
        }
        .tab-content.active { display: block; }

        /* ===== LMS STYLE TABLE ===== */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th {
            background: #f1f3f5;
            color: #495057;
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid #dee2e6;
        }
        .table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        /* Status Badges (Matching Image) */
        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        .status-submitted { background-color: #cfefcf; color: #155724; } /* Green */
        .status-pending { background-color: #f8f9fa; color: #333; border: 1px solid #ddd; }
        .status-warning { background-color: #fff3cd; color: #856404; }

        /* File Submission UI (Matching Image Item #2) */
        .file-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: fit-content;
        }
        .file-info { display: flex; flex-direction: column; }
        .file-name {
            color: #0056b3;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
        }
        .file-name:hover { text-decoration: underline; cursor: pointer; }
        .turnitin-box {
            font-size: 11px;
            color: #666;
            margin-top: 2px;
        }
        .similarity-tag {
            background: #343a40;
            color: white;
            padding: 2px 4px;
            font-size: 10px;
            border-radius: 2px;
            width: fit-content;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: #3C5B6F;
            font-weight: 600;
            margin-bottom: 15px;
        }

        /* Chart Legend */
        .chart-legend { list-style: none; padding: 0; }
        .chart-legend li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .legend-color { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 8px; }
    </style>
</head>

<body>
<?php include 'sidebar.php'; ?>

<section class="home-section">
  <a href="admin_page.php" class="btn-back">
        <i class='bx bx-left-arrow-alt' style="font-size: 20px; margin-right: 5px;"></i> 
        Back to Dashboard
    </a>
    <div class="welcome-text">
        <i class='bx bx-file'></i> Admin Dashboard | Proposals
    </div>
    <hr style="border:1px solid #3C5B6F; opacity:.3">


    <div style="margin-top:20px">
        <button class="tab-btn active" onclick="openTab(event,'tableTab')">
            <i class='bx bx-table'></i> Proposal Table
        </button>
        <button class="tab-btn" onclick="openTab(event,'chartTab')">
            <i class='bx bx-pie-chart-alt-2'></i> Status Chart
        </button>
    </div>

    <div id="tableTab" class="tab-content active">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proposal Details</th>
                    <th>Submission Status</th>
                    <th>File Submissions</th>
                    <th>Last Modified</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $props->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $p['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($p['title']) ?></strong><br>
                        <small style="color:#6c757d;"><?= htmlspecialchars($p['researcher_email']) ?></small>
                    </td>
                    <td>
                        <?php 
                            $statusClass = ($p['status'] == 'APPROVED') ? 'status-submitted' : 'status-pending';
                            $statusLabel = ($p['status'] == 'APPROVED') ? 'Submitted for grading' : htmlspecialchars($p['status']);
                        ?>
                        <span class="status-badge <?= $statusClass ?>">
                            <?= $statusLabel ?>
                        </span>
                    </td>
                    <td>
                        <div class="file-container">
                            <i class='bx bxs-file-doc' style="color: #2b579a; font-size: 24px;"></i>
                            <div class="file-info">
                                <a class="file-name" onclick="viewFakePDF('<?= addslashes($p['title']) ?>')">
                                    <?= htmlspecialchars($p['title']) ?>_final.pdf
                                </a>
                                <div class="turnitin-box">
                                    Turnitin ID: <?= rand(20000000, 29999999) ?>
                                </div>
                                <div class="similarity-tag"><?= rand(5, 25) ?>%</div>
                            </div>
                        </div>
                    </td>
                    <td><?= date("j F Y, g:i A", strtotime($p['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div id="chartTab" class="tab-content">
        <div style="display:flex; gap:40px; align-items:center; justify-content:center; flex-wrap:wrap;">
            <div style="max-width:300px;"><canvas id="statusChart"></canvas></div>
            <div id="chartLegend" style="min-width:250px;"></div>
        </div>
    </div>
</section>

<script>
// --- TAB LOGIC ---
function openTab(evt, id) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).style.display = 'block';
    evt.currentTarget.classList.add('active');
    if(id === 'chartTab') renderStatusChart();
}

// --- FAKE PDF VIEW LOGIC ---
function viewFakePDF(title) {
    // This is a Base64 string representing a simple PDF page
    const fakePdfData = "data:application/pdf;base64,JVBERi0xLjQKMSAwIG9iago8PAovVGl0bGUgKFRlc3QgUERGKQovQ3JlYXRvciAoR2VtaW5pKQo+PgplbmRvYmoKMiAwIG9iago8PAovVHlwZSAvQ2F0YWxvZwovUGFnZXMgMyAwIFIKPj4KZW5kb2JqCjMgMCBvYmoKPDwKL1R5cGUgL1BhZ2VzCi9LaWRzIFs0IDAgUl0KL0NvdW50IDEKPj4KZW5kb2JqCjQgMCBvYmoKPDwKL1R5cGUgL1BhZ2UKL1BhcmVudCAzIDAgUgovTWVkaWFCb3ggWzAgMCA1OTUgODQyXQovQ29udGVudHMgNSAwIFIKPj4KZW5kb2JqCjUgMCBvYmoKPDwKL0xlbmd0aCA2MD4+CnN0cmVhbQpCVAovRjEgMTggVGYKNTAgODAwIFREClZpZXdpbmcgU3VibWlzc2lvbjogKEV4aGliaXRvbiBQcm9wb3NhbCkgCkVUCmVuZHN0cmVhbQplbmRvYmoKNiAwIG9iago8PAovVHlwZSAvRm9udAovU3VidHlwZSAvVHlwZTEKL0Jhc2VGb250IC9IZWx2ZXRpY2EKPj4KZW5kb2JqCnhyZWYKMCA3CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDYyIDAwMDAw nIAowMDAwMDAwMTExIDAwMDAwIG4gCjAwMDAwMDAxNzAgMDAwMDAgbiAKMDAwMDAwMDI3MiAwMDAwMCBuIAowMDAwMDAwMzgzIDAwMDAwIG4gCnRyYWlsZXIKPDwKL1NpemUgNwolUm9vdCAyIDAgUgovSW5mbyAxIDAgUgo+PgpzdGFydHhyZWYKNDY1CiUlRU9G";
    
    const win = window.open("", "_blank");
    win.document.write(`
        <html>
            <title>Preview: ${title}</title>
            <body style="margin:0;">
                <iframe width="100%" height="100%" src="${fakePdfData}" frameborder="0"></iframe>
            </body>
        </html>
    `);
}

// --- CHART LOGIC ---
let statusChart = null;
function renderStatusChart() {
    if(statusChart) return;
    const ctx = document.getElementById('statusChart').getContext('2d');
    const labels = <?= json_encode(array_keys($status_counts)) ?>;
    const data = <?= json_encode(array_values($status_counts)) ?>;
    const colors = ['#1cc88a', '#4e73df', '#f6c23e', '#e74a3b', '#36b9cc'];

    statusChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{ data: data, backgroundColor: colors }]
        },
        options: { plugins: { legend: { display: false } } }
    });

    // Custom Legend
    let legendHtml = '<ul class="chart-legend">';
    labels.forEach((l, i) => {
        legendHtml += `<li><span><span class="legend-color" style="background:${colors[i]}"></span>${l}</span> <b>${data[i]}</b></li>`;
    });
    document.getElementById('chartLegend').innerHTML = legendHtml + '</ul>';
}

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById('tableTab').style.display = 'block';
});
</script>

</body>
</html>