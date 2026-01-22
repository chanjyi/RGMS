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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .analytics-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        
        .stat-card { 
            background: linear-gradient(135deg, #3C5B6F 0%, #2c4555 100%); 
            color: white; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        
        .stat-card.green { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
        }
        
        .stat-card.orange { 
            background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%); 
        }
        
        .stat-card.blue { 
            background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%); 
        }
        
        .stat-card h3 { 
            margin: 0 0 10px 0; 
            font-size: 14px; 
            opacity: 0.9; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
        }
        
        .stat-value { 
            font-size: 42px; 
            font-weight: bold; 
            margin: 10px 0; 
        }
        
        .stat-label { 
            font-size: 13px; 
            opacity: 0.8; 
        }
        
        .chart-container { 
            background: white; 
            padding: 20px; 
            border-radius: 12px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
            margin-bottom: 25px;
            /* FIX: Override fixed height from style.css to fit content */
            height: auto !important; 
            min-height: fit-content;
        }
        
        .chart-container h3 { 
            margin: 0 0 15px 0; 
            color: #3C5B6F; 
            font-size: 16px; 
        }
        
        .chart-wrapper { 
            position: relative; 
            height: 250px;
            width: 100%;
        }
        
        .grid-2 { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 25px; 
        }
        
        .activity-list { 
            background: white; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        }
        
        .activity-item { 
            padding: 15px; 
            border-left: 3px solid #3C5B6F; 
            margin-bottom: 10px; 
            background: #f8f9fa; 
            border-radius: 5px; 
        }
        
        .activity-item .type { 
            display: inline-block; 
            padding: 3px 10px; 
            background: #3C5B6F; 
            color: white; 
            border-radius: 12px; 
            font-size: 11px; 
            margin-right: 10px; 
        }
        
        .activity-item .date { 
            color: #999; 
            font-size: 12px; 
            float: right; 
        }
        
        .category-bar { 
            background: #e9ecef; 
            height: 30px; 
            border-radius: 15px; 
            margin: 10px 0; 
            overflow: hidden; 
            position: relative; 
        }
        
        .category-fill { 
            height: 100%; 
            background: linear-gradient(90deg, #3C5B6F, #20c997); 
            transition: width 0.5s; 
            display: flex; 
            align-items: center; 
            padding: 0 15px; 
            color: white; 
            font-size: 12px; 
            font-weight: bold; 
        }
        
        .summary-box { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            border-left: 4px solid #3C5B6F; 
            margin-bottom: 20px; 
        }
        
        .summary-box h4 { 
            margin: 0 0 15px 0; 
            color: #3C5B6F; 
        }
        
        .summary-stats { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 15px; 
        }
        
        .summary-stat { 
            text-align: center; 
        }
        
        .summary-stat .value { 
            font-size: 28px; 
            font-weight: bold; 
            color: #3C5B6F; 
        }
        
        .summary-stat .label { 
            font-size: 12px; 
            color: #666; 
            text-transform: uppercase; 
        }

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <section class="home-section">
        <div class="welcome-text">
            Research Analytics & Statistics | <?php echo htmlspecialchars($_SESSION['name']); ?>
        </div>
        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 25px;">

        <div class="analytics-grid">
            <div class="stat-card">
                <h3><i class='bx bx-file'></i> Total Proposals</h3>
                <div class="stat-value"><?php echo $stats['budget']['total_proposals'] ?? 0; ?></div>
                <div class="stat-label">Submitted to date</div>
            </div>
            
            <div class="stat-card green">
                <h3><i class='bx bx-dollar-circle'></i> Total Approved</h3>
                <div class="stat-value">RM<?php echo number_format($stats['budget']['total_approved'] ?? 0, 0); ?></div>
                <div class="stat-label">Budget allocated</div>
            </div>
            
            <div class="stat-card orange">
                <h3><i class='bx bx-receipt'></i> Total Spent</h3>
                <div class="stat-value">RM<?php echo number_format($stats['budget']['total_spent'] ?? 0, 0); ?></div>
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

        <div class="summary-box">
            <h4><i class='bx bx-wallet'></i> Budget Overview</h4>
            <div class="summary-stats">
                <div class="summary-stat">
                    <div class="value">RM<?php echo number_format($stats['budget']['total_requested'] ?? 0, 0); ?></div>
                    <div class="label">Total Requested</div>
                </div>
                <div class="summary-stat">
                    <div class="value">RM<?php echo number_format($stats['budget']['total_approved'] ?? 0, 0); ?></div>
                    <div class="label">Total Approved</div>
                </div>
                <div class="summary-stat">
                    <div class="value">RM<?php echo number_format($stats['budget']['total_spent'] ?? 0, 0); ?></div>
                    <div class="label">Total Claimed</div>
                </div>
                <div class="summary-stat">
                    <div class="value">RM<?php echo number_format(($stats['budget']['total_approved'] ?? 0) - ($stats['budget']['total_spent'] ?? 0), 0); ?></div>
                    <div class="label">Remaining Balance</div>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div class="chart-container">
                <h3><i class='bx bx-pie-chart'></i> Proposal Status Distribution</h3>
                <div class="chart-wrapper">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <h3><i class='bx bx-bar-chart'></i> Budget Allocation by Category</h3>
                <div class="chart-wrapper">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div class="chart-container">
                <h3><i class='bx bx-line-chart'></i> Submission Trends (Last 12 Months)</h3>
                <div class="chart-wrapper">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <h3><i class='bx bx-target-lock'></i> Milestone Progress</h3>
                <div class="chart-wrapper">
                    <canvas id="milestoneChart"></canvas>
                </div>
            </div>
        </div>

        <div class="chart-container">
            <h3><i class='bx bx-receipt'></i> Expenditure Status</h3>
            <div style="max-width: 600px; margin: 0 auto;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; text-align: center; margin-bottom: 20px;">
                    <div>
                        <div style="font-size: 28px; font-weight: bold; color: #3C5B6F;">
                            <?php echo $stats['expenditures']['total_expenditures'] ?? 0; ?>
                        </div>
                        <div style="font-size: 12px; color: #666;">Total Expenditures</div>
                    </div>
                    <div>
                        <div style="font-size: 28px; font-weight: bold; color: #28a745;">
                            RM<?php echo number_format($stats['expenditures']['approved_amount'] ?? 0, 0); ?>
                        </div>
                        <div style="font-size: 12px; color: #666;">Approved Claims</div>
                    </div>
                    <div>
                        <div style="font-size: 28px; font-weight: bold; color: #ffc107;">
                            RM<?php echo number_format($stats['expenditures']['pending_amount'] ?? 0, 0); ?>
                        </div>
                        <div style="font-size: 12px; color: #666;">Pending Claims</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-container">
            <h3><i class='bx bx-category'></i> Budget Utilization by Category</h3>
            <?php if (!empty($stats['categories'])): ?>
                <?php foreach ($stats['categories'] as $cat): 
                    $percentage = $cat['allocated'] > 0 ? ($cat['spent'] / $cat['allocated']) * 100 : 0;
                ?>
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <strong><?php echo $cat['category']; ?></strong>
                            <span style="color: #666;">RM<?php echo number_format($cat['spent'], 2); ?> / RM<?php echo number_format($cat['allocated'], 2); ?></span>
                        </div>
                        <div class="category-bar">
                            <div class="category-fill" style="width: <?php echo min($percentage, 100); ?>%;">
                                <?php echo round($percentage, 1); ?>%
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No budget data available yet</p>
            <?php endif; ?>
        </div>

        <div class="activity-list">
            <h3 style="margin: 0 0 20px 0; color: #3C5B6F;">
                <i class='bx bx-history'></i> Recent Activity
            </h3>
            <?php if (!empty($stats['recent_activity'])): ?>
                <?php foreach ($stats['recent_activity'] as $activity): ?>
                    <div class="activity-item">
                        <span class="type"><?php echo $activity['type']; ?></span>
                        <strong><?php echo htmlspecialchars($activity['name']); ?></strong>
                        <span class="date"><?php echo date('M d, Y', strtotime($activity['date'])); ?></span>
                        <br>
                        <small style="color: #666;">Status: <span style="text-transform: uppercase; color: #3C5B6F;"><?php echo $activity['status']; ?></span></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">No recent activity</p>
            <?php endif; ?>
        </div>
    </section>

    <script>
    window.addEventListener('load', function() {
        const colors = {
            primary: '#3C5B6F',
            success: '#28a745',
            warning: '#ffc107',
            danger: '#dc3545',
            info: '#17a2b8',
            secondary: '#6c757d',
            dark: '#2c4555'
        };

        // Status Chart
        const statusEl = document.getElementById('statusChart');
        if (statusEl) {
            new Chart(statusEl, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_keys($stats['status'])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($stats['status'])); ?>,
                        backgroundColor: [colors.success, colors.info, colors.warning, colors.danger, colors.primary, colors.secondary, colors.dark],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'right',
                            labels: { padding: 8, font: { size: 10 }, boxWidth: 12 }
                        }
                    }
                }
            });
        }

        // Category Chart
        const categoryEl = document.getElementById('categoryChart');
        if (categoryEl) {
            new Chart(categoryEl, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($stats['categories'], 'category')); ?>,
                    datasets: [
                        { label: 'Allocated', data: <?php echo json_encode(array_map('floatval', array_column($stats['categories'], 'allocated'))); ?>, backgroundColor: colors.info },
                        { label: 'Spent', data: <?php echo json_encode(array_map('floatval', array_column($stats['categories'], 'spent'))); ?>, backgroundColor: colors.success }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: true,
                            ticks: { callback: function(val) { return 'RM' + val; }, font: { size: 9 } }
                        },
                        x: { ticks: { font: { size: 9 } } }
                    },
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, padding: 8, font: { size: 10 } } }
                    }
                }
            });
        }

        // Trend Chart
        const trendEl = document.getElementById('trendChart');
        if (trendEl) {
            new Chart(trendEl, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($stats['trends'], 'month')); ?>,
                    datasets: [{
                        label: 'Proposals',
                        data: <?php echo json_encode(array_map('intval', array_column($stats['trends'], 'submissions'))); ?>,
                        borderColor: colors.primary,
                        backgroundColor: 'rgba(60, 91, 111, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { 
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 9 } } },
                        x: { ticks: { font: { size: 9 } } }
                    },
                    plugins: { legend: { position: 'top', labels: { boxWidth: 12, padding: 8, font: { size: 10 } } } }
                }
            });
        }

        // Milestone Chart
        const milestoneEl = document.getElementById('milestoneChart');
        if (milestoneEl) {
            new Chart(milestoneEl, {
                type: 'pie',
                data: {
                    labels: ['Completed', 'In Progress', 'Pending', 'Delayed'],
                    datasets: [{
                        data: [
                            <?php echo intval($stats['milestones']['completed'] ?? 0); ?>,
                            <?php echo intval($stats['milestones']['in_progress'] ?? 0); ?>,
                            <?php echo intval($stats['milestones']['pending'] ?? 0); ?>,
                            <?php echo intval($stats['milestones']['delayed'] ?? 0); ?>
                        ],
                        backgroundColor: [colors.success, colors.info, colors.warning, colors.danger],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { padding: 8, font: { size: 10 }, boxWidth: 12 } } }
                }
            });
        }
    });
    </script>
</body>
</html>