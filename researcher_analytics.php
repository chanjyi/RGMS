<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'researcher') {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email'];

// Fetch comprehensive statistics
$stats = [];

// 1. Proposal Status Distribution
$status_query = "SELECT status, COUNT(*) as count FROM proposals WHERE researcher_email = ? GROUP BY status";
$stmt = $conn->prepare($status_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$stats['status'] = [];
while($row = $result->fetch_assoc()) {
    $stats['status'][$row['status']] = $row['count'];
}

// 2. Budget Overview
$budget_query = "SELECT 
    COUNT(*) as total_proposals,
    SUM(budget_requested) as total_requested,
    SUM(CASE WHEN status = 'APPROVED' THEN approved_budget ELSE 0 END) as total_approved,
    SUM(CASE WHEN status = 'APPROVED' THEN amount_spent ELSE 0 END) as total_spent
    FROM proposals WHERE researcher_email = ?";
$stmt = $conn->prepare($budget_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$stats['budget'] = $stmt->get_result()->fetch_assoc();

// 3. Budget by Category (for approved grants)
$category_query = "SELECT 
    b.category,
    SUM(b.allocated_amount) as allocated,
    SUM(b.spent_amount) as spent
    FROM budget_items b
    JOIN proposals p ON b.proposal_id = p.id
    WHERE p.researcher_email = ? AND p.status = 'APPROVED'
    GROUP BY b.category";
$stmt = $conn->prepare($category_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$stats['categories'] = [];
while($row = $result->fetch_assoc()) {
    $stats['categories'][] = $row;
}

// 4. Monthly Submission Trends
$trend_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as submissions
    FROM proposals 
    WHERE researcher_email = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month";
$stmt = $conn->prepare($trend_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$stats['trends'] = [];
while($row = $result->fetch_assoc()) {
    $stats['trends'][] = $row;
}

// 5. Milestone Progress
$milestone_query = "SELECT 
    COUNT(*) as total_milestones,
    SUM(CASE WHEN m.status = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN m.status = 'PENDING' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN m.status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN m.status = 'DELAYED' THEN 1 ELSE 0 END) as 'delayed'
    FROM milestones m
    JOIN proposals p ON m.grant_id = p.id
    WHERE p.researcher_email = ? AND p.status = 'APPROVED'";
$stmt = $conn->prepare($milestone_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$stats['milestones'] = $stmt->get_result()->fetch_assoc();

// 6. Expenditure Summary
$exp_query = "SELECT 
    COUNT(*) as total_expenditures,
    SUM(e.amount) as total_amount,
    SUM(CASE WHEN e.status = 'APPROVED' THEN e.amount ELSE 0 END) as approved_amount,
    SUM(CASE WHEN e.status = 'PENDING_REIMBURSEMENT' THEN e.amount ELSE 0 END) as pending_amount
    FROM expenditures e
    JOIN budget_items b ON e.budget_item_id = b.id
    JOIN proposals p ON b.proposal_id = p.id
    WHERE p.researcher_email = ?";
$stmt = $conn->prepare($exp_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$stats['expenditures'] = $stmt->get_result()->fetch_assoc();

// 7. Recent Activity
$activity_query = "
    (SELECT 'Proposal' as type, title as name, created_at as date, status FROM proposals WHERE researcher_email = ? ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'Report' as type, title as name, submitted_at as date, status FROM progress_reports WHERE researcher_email = ? ORDER BY submitted_at DESC LIMIT 5)
    ORDER BY date DESC LIMIT 10";
$stmt = $conn->prepare($activity_query);
$stmt->bind_param("ss", $email, $email);
$stmt->execute();
$result = $stmt->get_result();
$stats['recent_activity'] = [];
while($row = $result->fetch_assoc()) {
    $stats['recent_activity'][] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Research Analytics Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .stat-card.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.orange { background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%); }
        .stat-card.blue { background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%); }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }
        .stat-value { font-size: 42px; font-weight: bold; margin: 10px 0; }
        .stat-label { font-size: 13px; opacity: 0.8; }
        
        .chart-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .chart-container h3 { margin: 0 0 20px 0; color: #3C5B6F; font-size: 18px; }
        .chart-wrapper { position: relative; height: 300px; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        
        .activity-list { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .activity-item { padding: 15px; border-left: 3px solid #3C5B6F; margin-bottom: 10px; background: #f8f9fa; border-radius: 5px; }
        .activity-item .type { display: inline-block; padding: 3px 10px; background: #3C5B6F; color: white; border-radius: 12px; font-size: 11px; margin-right: 10px; }
        .activity-item .date { color: #999; font-size: 12px; float: right; }
        
        .progress-ring { width: 120px; height: 120px; margin: 0 auto; }
        .progress-ring circle { fill: none; stroke-width: 8; }
        .progress-ring .bg { stroke: #e9ecef; }
        .progress-ring .progress { stroke: #28a745; stroke-dasharray: 0 251.2; transition: stroke-dasharray 0.3s; transform: rotate(-90deg); transform-origin: 50% 50%; }
        
        .category-bar { background: #e9ecef; height: 30px; border-radius: 15px; margin: 10px 0; overflow: hidden; position: relative; }
        .category-fill { height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); transition: width 0.5s; display: flex; align-items: center; padding: 0 15px; color: white; font-size: 12px; font-weight: bold; }
        
        .summary-box { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #3C5B6F; margin-bottom: 20px; }
        .summary-box h4 { margin: 0 0 15px 0; color: #3C5B6F; }
        .summary-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .summary-stat { text-align: center; }
        .summary-stat .value { font-size: 28px; font-weight: bold; color: #3C5B6F; }
        .summary-stat .label { font-size: 12px; color: #666; text-transform: uppercase; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <section class="home-section">
        <div class="welcome-text">
            <i class='bx bx-bar-chart-alt-2' style="font-size:24px; vertical-align:middle;"></i>
            Research Analytics & Statistics | <?= htmlspecialchars($_SESSION['name']); ?>
        </div>
        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 25px;">

        <!-- Key Performance Indicators -->
        <div class="analytics-grid">
            <div class="stat-card">
                <h3><i class='bx bx-file'></i> Total Proposals</h3>
                <div class="stat-value"><?= $stats['budget']['total_proposals'] ?? 0 ?></div>
                <div class="stat-label">Submitted to date</div>
            </div>
            
            <div class="stat-card green">
                <h3><i class='bx bx-dollar-circle'></i> Total Approved</h3>
                <div class="stat-value">$<?= number_format($stats['budget']['total_approved'] ?? 0, 0) ?></div>
                <div class="stat-label">Budget allocated</div>
            </div>
            
            <div class="stat-card orange">
                <h3><i class='bx bx-receipt'></i> Total Spent</h3>
                <div class="stat-value">$<?= number_format($stats['budget']['total_spent'] ?? 0, 0) ?></div>
                <div class="stat-label">Reimbursed to date</div>
            </div>
            
            <div class="stat-card blue">
                <h3><i class='bx bx-trending-up'></i> Success Rate</h3>
                <div class="stat-value">
                    <?php 
                    $approved = $stats['status']['APPROVED'] ?? 0;
                    $total = $stats['budget']['total_proposals'] ?? 1;
                    echo round(($approved / $total) * 100, 1);
                    ?>%
                </div>
                <div class="stat-label">Approval percentage</div>
            </div>
        </div>

        <!-- Budget Summary -->
        <div class="summary-box">
            <h4><i class='bx bx-wallet'></i> Budget Overview</h4>
            <div class="summary-stats">
                <div class="summary-stat">
                    <div class="value">$<?= number_format($stats['budget']['total_requested'] ?? 0, 0) ?></div>
                    <div class="label">Total Requested</div>
                </div>
                <div class="summary-stat">
                    <div class="value">$<?= number_format($stats['budget']['total_approved'] ?? 0, 0) ?></div>
                    <div class="label">Total Approved</div>
                </div>
                <div class="summary-stat">
                    <div class="value">$<?= number_format($stats['budget']['total_spent'] ?? 0, 0) ?></div>
                    <div class="label">Total Claimed</div>
                </div>
                <div class="summary-stat">
                    <div class="value">$<?= number_format(($stats['budget']['total_approved'] ?? 0) - ($stats['budget']['total_spent'] ?? 0), 0) ?></div>
                    <div class="label">Remaining Balance</div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid-2">
            <!-- Proposal Status Distribution -->
            <div class="chart-container">
                <h3><i class='bx bx-pie-chart'></i> Proposal Status Distribution</h3>
                <div class="chart-wrapper">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Budget by Category -->
            <div class="chart-container">
                <h3><i class='bx bx-bar-chart'></i> Budget Allocation by Category</h3>
                <div class="chart-wrapper">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid-2">
            <!-- Submission Trends -->
            <div class="chart-container">
                <h3><i class='bx bx-line-chart'></i> Submission Trends (Last 12 Months)</h3>
                <div class="chart-wrapper">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Milestone Progress -->
            <div class="chart-container">
                <h3><i class='bx bx-target-lock'></i> Milestone Progress</h3>
                <div class="chart-wrapper">
                    <canvas id="milestoneChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Expenditure Breakdown -->
        <div class="chart-container">
            <h3><i class='bx bx-receipt'></i> Expenditure Status</h3>
            <div style="max-width: 600px; margin: 0 auto;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; text-align: center; margin-bottom: 20px;">
                    <div>
                        <div style="font-size: 28px; font-weight: bold; color: #3C5B6F;">
                            <?= $stats['expenditures']['total_expenditures'] ?? 0 ?>
                        </div>
                        <div style="font-size: 12px; color: #666;">Total Expenditures</div>
                    </div>
                    <div>
                        <div style="font-size: 28px; font-weight: bold; color: #28a745;">
                            $<?= number_format($stats['expenditures']['approved_amount'] ?? 0, 0) ?>
                        </div>
                        <div style="font-size: 12px; color: #666;">Approved Claims</div>
                    </div>
                    <div>
                        <div style="font-size: 28px; font-weight: bold; color: #ffc107;">
                            $<?= number_format($stats['expenditures']['pending_amount'] ?? 0, 0) ?>
                        </div>
                        <div style="font-size: 12px; color: #666;">Pending Claims</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Spending Breakdown -->
        <div class="chart-container">
            <h3><i class='bx bx-category'></i> Budget Utilization by Category</h3>
            <?php if (!empty($stats['categories'])): ?>
                <?php foreach ($stats['categories'] as $cat): 
                    $percentage = $cat['allocated'] > 0 ? ($cat['spent'] / $cat['allocated']) * 100 : 0;
                ?>
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <strong><?= $cat['category'] ?></strong>
                            <span style="color: #666;">$<?= number_format($cat['spent'], 2) ?> / $<?= number_format($cat['allocated'], 2) ?></span>
                        </div>
                        <div class="category-bar">
                            <div class="category-fill" style="width: <?= min($percentage, 100) ?>%;">
                                <?= round($percentage, 1) ?>%
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No budget data available yet</p>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="activity-list">
            <h3 style="margin: 0 0 20px 0; color: #3C5B6F;">
                <i class='bx bx-history'></i> Recent Activity
            </h3>
            <?php if (!empty($stats['recent_activity'])): ?>
                <?php foreach ($stats['recent_activity'] as $activity): ?>
                    <div class="activity-item">
                        <span class="type"><?= $activity['type'] ?></span>
                        <strong><?= htmlspecialchars($activity['name']) ?></strong>
                        <span class="date"><?= date('M d, Y', strtotime($activity['date'])) ?></span>
                        <br>
                        <small style="color: #666;">Status: <span style="text-transform: uppercase; color: #3C5B6F;"><?= $activity['status'] ?></span></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">No recent activity</p>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Status Distribution Pie Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($stats['status'])) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($stats['status'])) ?>,
                    backgroundColor: [
                        '#4CAF50', '#2196F3', '#FFC107', '#F44336', '#9C27B0', '#FF9800', '#00BCD4'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        // Budget by Category Bar Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($stats['categories'], 'category')) ?>,
                datasets: [
                    {
                        label: 'Allocated',
                        data: <?= json_encode(array_column($stats['categories'], 'allocated')) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)'
                    },
                    {
                        label: 'Spent',
                        data: <?= json_encode(array_column($stats['categories'], 'spent')) ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Submission Trends Line Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($stats['trends'], 'month')) ?>,
                datasets: [{
                    label: 'Proposals Submitted',
                    data: <?= json_encode(array_column($stats['trends'], 'submissions')) ?>,
                    borderColor: '#3C5B6F',
                    backgroundColor: 'rgba(60, 91, 111, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        // Milestone Progress Pie Chart
        const milestoneCtx = document.getElementById('milestoneChart').getContext('2d');
        new Chart(milestoneCtx, {
            type: 'pie',
            data: {
                labels: ['Completed', 'In Progress', 'Pending', 'Delayed'],
                datasets: [{
                    data: [
                        <?= $stats['milestones']['completed'] ?? 0 ?>,
                        <?= $stats['milestones']['in_progress'] ?? 0 ?>,
                        <?= $stats['milestones']['pending'] ?? 0 ?>,
                        <?= $stats['milestones']['delayed'] ?? 0 ?>
                    ],
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>