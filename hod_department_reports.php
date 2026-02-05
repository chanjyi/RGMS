<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'hod') {
    header('Location: index.php');
    exit();
}

// Fetch HOD details
$hod_stmt = $conn->prepare("SELECT id, name, department_id FROM users WHERE email = ? AND role = 'hod' LIMIT 1");
$hod_stmt->bind_param("s", $_SESSION['email']);
$hod_stmt->execute();
$hod = $hod_stmt->get_result()->fetch_assoc();
$hod_name = $hod['name'] ?? 'HOD';
$department_id = $hod['department_id'] ?? null;

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$download_format = $_GET['download'] ?? '';

// Helpers
function build_where($department_id, $start_date, $end_date, &$types, &$params, $prefix = '', $with_join = false) {
    $types = 'i';
    $params = [$department_id];
    $p = $prefix;
    
    // If using JOIN pattern, filter on user's department_id instead of proposal's department_id
    if ($with_join) {
        $parts = ["u.department_id = ?"];
    } else {
        $parts = ["{$p}department_id = ?"];
    }
    
    if (!empty($start_date)) {
        $parts[] = "DATE(COALESCE({$p}approved_at, {$p}created_at)) >= ?";
        $types .= 's';
        $params[] = $start_date;
    }
    if (!empty($end_date)) {
        $parts[] = "DATE(COALESCE({$p}approved_at, {$p}created_at)) <= ?";
        $types .= 's';
        $params[] = $end_date;
    }
    return 'WHERE ' . implode(' AND ', $parts);
}

function run_query($conn, $sql, $types, $params) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) { return false; }
    if ($types !== '' && $params) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) { return false; }
    return $stmt->get_result();
}

if (!$department_id) {
    die('Department not found for this HOD');
}

$types = '';
$params = [];
$base_where = build_where($department_id, $start_date, $end_date, $types, $params, '', true);
$types_p = $types;
$params_p = $params;
$base_where_p = build_where($department_id, $start_date, $end_date, $types_p, $params_p, 'p.', true);
$research_statuses = "('APPROVED','REJECTED','APPEAL_REJECTED','RECOMMENDED','RESUBMITTED','PENDING_REVIEW','ASSIGNED','SUBMITTED','REQUIRES_AMENDMENT')";
$full_statuses = $research_statuses;

// Status distribution
$status_res = run_query(
    $conn,
    "SELECT p.status, COUNT(*) AS c FROM proposals p JOIN users u ON p.researcher_email = u.email $base_where AND p.status IN $full_statuses GROUP BY p.status",
    $types,
    $params
);
$status_counts = [];
if ($status_res) {
    while ($row = $status_res->fetch_assoc()) {
        $status_counts[$row['status']] = (int)$row['c'];
    }
}

// Budget overview for research projects
$budget_res = run_query(
    $conn,
    "SELECT 
        COUNT(*) AS total_projects,
        SUM(COALESCE(p.approved_budget,0)) AS total_approved,
        SUM(COALESCE(p.amount_spent,0)) AS total_spent,
        SUM(COALESCE(p.budget_requested,0)) AS total_requested
     FROM proposals p 
     JOIN users u ON p.researcher_email = u.email
     $base_where AND p.status IN $research_statuses",
    $types,
    $params
);
$budget = $budget_res ? $budget_res->fetch_assoc() : ['total_projects'=>0,'total_approved'=>0,'total_spent'=>0,'total_requested'=>0];

// Category allocation (approved/active projects only)
$cat_res = run_query(
    $conn,
    "SELECT b.category, SUM(b.allocated_amount) AS allocated, SUM(b.spent_amount) AS spent
     FROM budget_items b
     JOIN proposals p ON b.proposal_id = p.id
     JOIN users u ON p.researcher_email = u.email
     $base_where_p AND p.status IN $research_statuses
     GROUP BY b.category",
    $types_p,
    $params_p
);
$categories = $cat_res ? $cat_res->fetch_all(MYSQLI_ASSOC) : [];

// Monthly trends (projects approved/created)
$trend_res = run_query(
    $conn,
    "SELECT DATE_FORMAT(COALESCE(p.approved_at, p.created_at), '%Y-%m') AS month, COUNT(*) AS submissions
     FROM proposals p
     JOIN users u ON p.researcher_email = u.email
     $base_where AND p.status IN $research_statuses
     GROUP BY DATE_FORMAT(COALESCE(p.approved_at, p.created_at), '%Y-%m')
     ORDER BY month",
    $types,
    $params
);
$trends = $trend_res ? $trend_res->fetch_all(MYSQLI_ASSOC) : [];

// Milestone progress for department projects
$milestone_res = run_query(
    $conn,
    "SELECT 
        COUNT(*) AS total_milestones,
        SUM(CASE WHEN m.status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN m.status = 'IN_PROGRESS' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN m.status = 'PENDING' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN m.status = 'DELAYED' THEN 1 ELSE 0 END) AS delayed_count
     FROM milestones m
     JOIN proposals p ON m.grant_id = p.id
     JOIN users u ON p.researcher_email = u.email
     $base_where_p AND p.status IN $research_statuses",
    $types_p,
    $params_p
);
$milestones = $milestone_res ? $milestone_res->fetch_assoc() : ['total_milestones'=>0,'completed'=>0,'in_progress'=>0,'pending'=>0,'delayed_count'=>0];

// Expenditures summary
$exp_res = run_query(
    $conn,
    "SELECT 
        COUNT(*) AS total_expenditures,
        SUM(e.amount) AS total_amount,
        SUM(CASE WHEN e.status = 'APPROVED' THEN e.amount ELSE 0 END) AS approved_amount,
        SUM(CASE WHEN e.status = 'PENDING_REIMBURSEMENT' THEN e.amount ELSE 0 END) AS pending_amount
     FROM expenditures e
     JOIN budget_items b ON e.budget_item_id = b.id
     JOIN proposals p ON b.proposal_id = p.id
     JOIN users u ON p.researcher_email = u.email
     $base_where_p AND p.status IN $research_statuses",
    $types_p,
    $params_p
);
$expenditures = $exp_res ? $exp_res->fetch_assoc() : ['total_expenditures'=>0,'total_amount'=>0,'approved_amount'=>0,'pending_amount'=>0];

// Recent activity (projects + reports)
$activity_sql = "(
    SELECT 'Project' AS type, p.title AS name, COALESCE(p.approved_at, p.created_at) AS date_val, p.status
    FROM proposals p
    JOIN users u ON p.researcher_email = u.email
    $base_where AND p.status IN $full_statuses
    ORDER BY date_val DESC
    LIMIT 5
) UNION ALL (
    SELECT 'Report' AS type, pr.title AS name, pr.submitted_at AS date_val, pr.status
    FROM progress_reports pr
    JOIN proposals p ON pr.proposal_id = p.id
    JOIN users u ON p.researcher_email = u.email
    $base_where_p
    ORDER BY pr.submitted_at DESC
    LIMIT 5
) ORDER BY date_val DESC LIMIT 10";
// Duplicate bind params because $base_where (with placeholders) is used in both SELECT parts
$activity_types = $types . $types_p;
$activity_params = array_merge($params, $params_p);
$activity_res = run_query($conn, $activity_sql, $activity_types, $activity_params);
$recent_activity = $activity_res ? $activity_res->fetch_all(MYSQLI_ASSOC) : [];

// Simple PDF generator (minimal 1-page text-only PDF)
function build_simple_pdf(array $lines): string {
    $escape = function($text) {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        return preg_replace('/[\r\n]+/', ' ', $text);
    };

    $y = 780;
    $content = "BT\n/F1 12 Tf\n";
    foreach ($lines as $line) {
        $safe = $escape($line);
        $content .= sprintf("1 0 0 1 50 %.2f Tm (%s) Tj\n", $y, $safe);
        $y -= 16;
    }
    $content .= "ET";

    $len = strlen($content);
    $pdf = "%PDF-1.4\n";
    $objects = [];
    $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
    $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
    $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n";
    $objects[] = "4 0 obj << /Length $len >> stream\n$content\nendstream endobj\n";
    $objects[] = "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";

    $xref = [];
    $offset = strlen($pdf);
    foreach ($objects as $obj) {
        $xref[] = $offset;
        $pdf .= $obj;
        $offset = strlen($pdf);
    }

    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    foreach ($xref as $off) {
        $pdf .= sprintf("%010d 00000 n \n", $off);
    }
    $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . strlen($pdf) . "\n%%EOF";
    return $pdf;
}

// Download handlers
if (!empty($download_format)) {
    $common_rows = function() use ($budget, $recent_activity) {
        $rows = [
            ['Metric', 'Value'],
            ['Total Projects', $budget['total_projects'] ?? 0],
            ['Approved Budget', number_format((float)($budget['total_approved'] ?? 0), 2)],
            ['Amount Spent', number_format((float)($budget['total_spent'] ?? 0), 2)],
            ['Requested Budget', number_format((float)($budget['total_requested'] ?? 0), 2)],
        ];
        if (!empty($recent_activity)) {
            $rows[] = [];
            $rows[] = ['Recent Activity'];
            $rows[] = ['Type','Name','Date','Status'];
            foreach ($recent_activity as $row) {
                $rows[] = [$row['type'], $row['name'], $row['date_val'], $row['status']];
            }
        }
        return $rows;
    };

    if ($download_format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="department_report.csv"');
        $out = fopen('php://output', 'w');
        foreach ($common_rows() as $line) {
            fputcsv($out, $line);
        }
        fclose($out);
        exit();
    }

    if ($download_format === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="department_report.xls"');
        $out = fopen('php://output', 'w');
        foreach ($common_rows() as $line) {
            fputcsv($out, $line, "\t");
        }
        fclose($out);
        exit();
    }

    if ($download_format === 'pdf') {
        $lines = [
            'Department Report',
            'Total Projects: ' . ($budget['total_projects'] ?? 0),
            'Approved Budget: RM' . number_format((float)($budget['total_approved'] ?? 0), 2),
            'Amount Spent: RM' . number_format((float)($budget['total_spent'] ?? 0), 2),
            'Requested Budget: RM' . number_format((float)($budget['total_requested'] ?? 0), 2),
            'Milestones Completed: ' . ($milestones['completed'] ?? 0) . ' of ' . ($milestones['total_milestones'] ?? 0),
            'Approved Claims: RM' . number_format((float)($expenditures['approved_amount'] ?? 0), 2),
            'Pending Claims: RM' . number_format((float)($expenditures['pending_amount'] ?? 0), 2),
        ];
        $pdf = build_simple_pdf($lines);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="department_report.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Reports - RGMS</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/hod_pages.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 20px; }
        .stat-card { background: linear-gradient(135deg, #3C5B6F 0%, #2c4555 100%); color: white; padding: 22px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
        .stat-card.green { background: linear-gradient(135deg, #2e7d32 0%, #43a047 100%); }
        .stat-card.orange { background: linear-gradient(135deg, #ef6c00 0%, #f9a825 100%); }
        .stat-card h3 { margin: 0 0 8px 0; font-size: 14px; letter-spacing: 0.5px; text-transform: uppercase; opacity: 0.95; }
        .stat-value { font-size: 38px; font-weight: 700; margin: 6px 0 4px 0; }
        .stat-label { font-size: 12px; opacity: 0.85; }
        .chart-container { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 22px; }
        .chart-container h3 { margin: 0 0 12px 0; color: #3C5B6F; font-size: 16px; }
        .chart-wrapper { position: relative; height: 260px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .activity-item { padding: 14px; border-left: 3px solid #3C5B6F; margin-bottom: 10px; background: #f8f9fa; border-radius: 6px; }
        .activity-item .type { display: inline-block; padding: 3px 8px; background: #3C5B6F; color: white; border-radius: 12px; font-size: 11px; margin-right: 8px; }
        .activity-item .date { color: #999; font-size: 12px; float: right; }
        @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
        <div class="welcome-text">Department Reports & Analytics</div>
        <hr style="opacity: 0.3; margin: 16px 0;">

        <div class="proposal-filter-bar" style="align-items: flex-end; gap: 12px;">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="start_date" class="filter-label">Start date</label>
                    <input type="date" id="start_date" name="start_date" class="filter-input" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="end_date" class="filter-label">End date</label>
                    <input type="date" id="end_date" name="end_date" class="filter-input" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <button type="submit" class="btn-save-tiers" style="padding: 10px 18px;">Apply Filters</button>
                <div style="display: flex; gap: 8px;">
                    <?php $qs = ($start_date ? '&start_date=' . urlencode($start_date) : '') . ($end_date ? '&end_date=' . urlencode($end_date) : ''); ?>
                    <a href="?download=csv<?= $qs ?>" class="btn-approve-all" style="padding: 10px 14px; text-decoration: none;"><i class='bx bx-download'></i> CSV</a>
                    <a href="?download=excel<?= $qs ?>" class="btn-approve-all" style="padding: 10px 14px; text-decoration: none;"><i class='bx bx-table'></i> Excel</a>
                    <a href="?download=pdf<?= $qs ?>" class="btn-approve-all" style="padding: 10px 14px; text-decoration: none;"><i class='bx bxs-file-pdf'></i> PDF</a>
                </div>
            </form>
        </div>

        <div class="analytics-grid">
            <div class="stat-card">
                <h3><i class='bx bx-book-content'></i> Projects</h3>
                <div class="stat-value"><?= (int)($budget['total_projects'] ?? 0) ?></div>
                <div class="stat-label">Approved/active/archived projects in scope</div>
            </div>
            <div class="stat-card green">
                <h3><i class='bx bx-wallet'></i> Approved Budget</h3>
                <div class="stat-value">RM<?= number_format((float)($budget['total_approved'] ?? 0), 0) ?></div>
                <div class="stat-label">Total allocated to department projects</div>
            </div>
            <div class="stat-card orange">
                <h3><i class='bx bx-coin-stack'></i> Amount Spent</h3>
                <div class="stat-value">RM<?= number_format((float)($budget['total_spent'] ?? 0), 0) ?></div>
                <div class="stat-label">Recorded reimbursements</div>
            </div>
        </div>

        <div class="grid-2">
            <div class="chart-container">
                <h3><i class='bx bx-pie-chart'></i> Status Distribution</h3>
                <div class="chart-wrapper"><canvas id="statusChart"></canvas></div>
            </div>
            <div class="chart-container">
                <h3><i class='bx bx-bar-chart'></i> Budget by Category</h3>
                <div class="chart-wrapper"><canvas id="categoryChart"></canvas></div>
            </div>
        </div>

        <div class="grid-2">
            <div class="chart-container">
                <h3><i class='bx bx-line-chart'></i> Project Approvals (Monthly)</h3>
                <div class="chart-wrapper"><canvas id="trendChart"></canvas></div>
            </div>
            <div class="chart-container">
                <h3><i class='bx bx-target-lock'></i> Milestone Progress</h3>
                <div class="chart-wrapper"><canvas id="milestoneChart"></canvas></div>
            </div>
        </div>

        <div class="chart-container" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 14px;"><i class='bx bx-receipt'></i> Expenditure Status</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; text-align: center;">
                <div style="padding: 12px 0;">
                    <div style="font-size: 28px; font-weight: 700; color: #3C5B6F; margin-bottom: 4px;">
                        <?= (int)($expenditures['total_expenditures'] ?? 0) ?>
                    </div>
                    <div style="font-size: 12px; color: #666;">Total Expenditures</div>
                </div>
                <div style="padding: 12px 0;">
                    <div style="font-size: 28px; font-weight: 700; color: #2e7d32; margin-bottom: 4px;">
                        RM<?= number_format((float)($expenditures['approved_amount'] ?? 0), 0) ?>
                    </div>
                    <div style="font-size: 12px; color: #666;">Approved Claims</div>
                </div>
                <div style="padding: 12px 0;">
                    <div style="font-size: 28px; font-weight: 700; color: #f9a825; margin-bottom: 4px;">
                        RM<?= number_format((float)($expenditures['pending_amount'] ?? 0), 0) ?>
                    </div>
                    <div style="font-size: 12px; color: #666;">Pending Claims</div>
                </div>
            </div>
        </div>

        <div class="chart-container" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 14px;"><i class='bx bx-category'></i> Budget Utilization by Category</h3>
            <div style="max-height: 320px; overflow-y: auto; padding-right: 8px;">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): 
                        $percentage = ($cat['allocated'] ?? 0) > 0 ? (($cat['spent'] ?? 0) / $cat['allocated']) * 100 : 0;
                    ?>
                        <div style="margin-bottom: 14px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <strong><?= htmlspecialchars($cat['category']) ?></strong>
                                <span style="color: #666; font-size: 12px;">RM<?= number_format((float)$cat['spent'], 2) ?> / RM<?= number_format((float)$cat['allocated'], 2) ?></span>
                            </div>
                            <div class="category-bar" style="background: #e9ecef; height: 22px; border-radius: 12px; overflow: hidden;">
                                <div class="category-fill" style="width: <?= min($percentage, 100) ?>%; height: 100%; background: linear-gradient(90deg, #3C5B6F, #20c997); display: flex; align-items: center; padding: 0 8px; color: #fff; font-weight: 600; font-size: 11px;">
                                    <?= round($percentage, 1) ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">No budget data available for the selected range.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="chart-container" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 10px;"><i class='bx bx-history'></i> Recent Activity</h3>
            <div style="max-height: 230px; overflow-y: auto; margin: 0 -8px; padding: 0 8px;">
                <?php if (!empty($recent_activity)): ?>
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item" style="margin-bottom: 8px; padding: 10px 12px;">
                            <span class="type"><?= htmlspecialchars($activity['type']) ?></span>
                            <strong><?= htmlspecialchars($activity['name']) ?></strong>
                            <span class="date"><?= $activity['date_val'] ? date('M d, Y', strtotime($activity['date_val'])) : '-' ?></span>
                            <br>
                            <small style="color: #666; font-size: 11px;">Status: <span style="text-transform: uppercase; color: #3C5B6F;"><?= htmlspecialchars($activity['status']) ?></span></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">No recent activity for this range.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
    window.addEventListener('load', function() {
        const colors = {
            primary: '#3C5B6F',
            success: '#2e7d32',
            warning: '#f9a825',
            danger: '#c62828',
            info: '#20c997',
            gray: '#6c757d'
        };

        // Status chart
        const statusLabels = <?= json_encode(array_keys($status_counts)) ?>;
        const statusData = <?= json_encode(array_values($status_counts)) ?>;
        if (statusLabels.length) {
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusData,
                        backgroundColor: [colors.success, colors.info, colors.warning, colors.danger, colors.primary, colors.gray],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right', labels: { boxWidth: 12, padding: 10, font: { size: 11 } } } }
                }
            });
        }

        // Category chart
        const catLabels = <?= json_encode(array_column($categories, 'category')) ?>;
        const catAllocated = <?= json_encode(array_map('floatval', array_column($categories, 'allocated'))) ?>;
        const catSpent = <?= json_encode(array_map('floatval', array_column($categories, 'spent'))) ?>;
        if (catLabels.length) {
            new Chart(document.getElementById('categoryChart'), {
                type: 'bar',
                data: {
                    labels: catLabels,
                    datasets: [
                        { label: 'Allocated', data: catAllocated, backgroundColor: colors.info },
                        { label: 'Spent', data: catSpent, backgroundColor: colors.success }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: val => 'RM' + val, font: { size: 10 } } },
                        x: { ticks: { font: { size: 10 } } }
                    },
                    plugins: { legend: { position: 'top', labels: { boxWidth: 12, padding: 8, font: { size: 11 } } } }
                }
            });
        }

        // Trend chart
        const trendLabels = <?= json_encode(array_column($trends, 'month')) ?>;
        const trendData = <?= json_encode(array_map('intval', array_column($trends, 'submissions'))) ?>;
        if (trendLabels.length) {
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Projects',
                        data: trendData,
                        borderColor: colors.primary,
                        backgroundColor: 'rgba(60, 91, 111, 0.12)',
                        tension: 0.35,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } } },
                        x: { ticks: { font: { size: 10 } } }
                    },
                    plugins: { legend: { position: 'top', labels: { boxWidth: 12, padding: 8, font: { size: 11 } } } }
                }
            });
        }

        // Milestone chart
        const milestoneData = [
            <?= intval($milestones['completed'] ?? 0) ?>,
            <?= intval($milestones['in_progress'] ?? 0) ?>,
            <?= intval($milestones['pending'] ?? 0) ?>,
            <?= intval($milestones['delayed_count'] ?? 0) ?>
        ];
        new Chart(document.getElementById('milestoneChart'), {
            type: 'pie',
            data: {
                labels: ['Completed', 'In Progress', 'Pending', 'Delayed'],
                datasets: [{
                    data: milestoneData,
                    backgroundColor: [colors.success, colors.info, colors.warning, colors.danger],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 10, font: { size: 11 } } } }
            }
        });
    });
    </script>
</body>
</html>
