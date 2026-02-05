<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}

$proposal_id = intval($_GET['id'] ?? 0);

if ($proposal_id <= 0) {
    die('Invalid proposal ID');
}

// Fetch completed research information
$proposal_sql = "
    SELECT 
        p.*,
        u.name AS researcher_name,
        u.email AS researcher_email
    FROM proposals p
    LEFT JOIN users u ON p.researcher_email = u.email
    WHERE p.id = ? AND p.status IN ('COMPLETED', 'ARCHIVED', 'TERMINATED')
";
$stmt = $conn->prepare($proposal_sql);
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$proposal = $stmt->get_result()->fetch_assoc();

if (!$proposal) {
    die('Completed research not found');
}

// Fetch all progress reports (research outputs)
$reports_sql = "SELECT * FROM progress_reports WHERE proposal_id = ? ORDER BY submitted_at DESC";
$stmt = $conn->prepare($reports_sql);
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$reports = $stmt->get_result();

// Fetch budget summary
$budget_sql = "SELECT category, allocated_amount, spent_amount FROM budget_items WHERE proposal_id = ?";
$stmt = $conn->prepare($budget_sql);
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$budget_items = $stmt->get_result();

// Calculate totals
$total_allocated = 0;
$total_spent = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Research - <?= htmlspecialchars($proposal['title']) ?></title>
    <link rel="stylesheet" href="styling/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f5f5f5; }
        .detail-card { background: white; padding: 25px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .detail-header { font-size: 24px; font-weight: 700; color: #2c3e50; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #95a5a6; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
        .info-item { padding: 12px; background: #f8f9fa; border-radius: 8px; }
        .info-label { font-size: 12px; color: #7f8c8d; margin-bottom: 5px; }
        .info-value { font-size: 16px; font-weight: 600; color: #2c3e50; }
        .summary-box { background: linear-gradient(135deg, #95a5a6, #7f8c8d); color: white; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .output-item { background: #f8f9fa; padding: 20px; margin-bottom: 15px; border-radius: 8px; border-left: 4px solid #27ae60; }
        .budget-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .budget-table th, .budget-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .budget-table th { background: #95a5a6; color: white; font-weight: 600; }
    </style>
</head>
<body>
    <button onclick="window.close()" style="padding: 10px 20px; background: #95a5a6; color: white; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 20px;">
        <i class='bx bx-x'></i> Close
    </button>

    <div class="detail-card">
        <div class="detail-header"><?= htmlspecialchars($proposal['title']) ?></div>
        
        <div style="text-align: center; margin: 20px 0;">
            <span style="padding: 10px 30px; background: #95a5a6; color: white; border-radius: 25px; font-size: 18px; font-weight: 600;">
                <?= htmlspecialchars($proposal['status']) ?>
            </span>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Researcher</div>
                <div class="info-value"><?= htmlspecialchars($proposal['researcher_name']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Duration</div>
                <div class="info-value"><?= $proposal['duration_months'] ?> months</div>
            </div>
            <div class="info-item">
                <div class="info-label">Approved Date</div>
                <div class="info-value"><?= $proposal['approved_at'] ? date('M d, Y', strtotime($proposal['approved_at'])) : 'N/A' ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Completion Date</div>
                <div class="info-value"><?= date('M d, Y') ?></div>
            </div>
        </div>

        <?php if ($proposal['description']): ?>
            <div style="margin: 20px 0;">
                <strong>Project Description:</strong>
                <p><?= htmlspecialchars($proposal['description']) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="detail-card">
        <div class="detail-header">Financial Summary</div>
        
        <div class="summary-box">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; text-align: center;">
                <div>
                    <div style="font-size: 14px; margin-bottom: 10px; opacity: 0.9;">Approved Budget</div>
                    <div style="font-size: 28px; font-weight: 700;">RM<?= number_format($proposal['approved_budget'], 2) ?></div>
                </div>
                <div>
                    <div style="font-size: 14px; margin-bottom: 10px; opacity: 0.9;">Total Spent</div>
                    <div style="font-size: 28px; font-weight: 700;">RM<?= number_format($proposal['amount_spent'], 2) ?></div>
                </div>
                <div>
                    <div style="font-size: 14px; margin-bottom: 10px; opacity: 0.9;">Remaining/Returned</div>
                    <div style="font-size: 28px; font-weight: 700;">
                        RM<?= number_format($proposal['approved_budget'] - $proposal['amount_spent'], 2) ?>
                    </div>
                </div>
            </div>
        </div>

        <table class="budget-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Allocated</th>
                    <th>Spent</th>
                    <th>Variance</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($budget = $budget_items->fetch_assoc()): 
                    $variance = $budget['allocated_amount'] - $budget['spent_amount'];
                    $total_allocated += $budget['allocated_amount'];
                    $total_spent += $budget['spent_amount'];
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($budget['category']) ?></strong></td>
                        <td>RM<?= number_format($budget['allocated_amount'], 2) ?></td>
                        <td>RM<?= number_format($budget['spent_amount'], 2) ?></td>
                        <td style="color: <?= $variance >= 0 ? '#27ae60' : '#e74c3c' ?>">
                            RM<?= number_format($variance, 2) ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <tr style="background: #f8f9fa; font-weight: 700;">
                    <td>TOTAL</td>
                    <td>RM<?= number_format($total_allocated, 2) ?></td>
                    <td>RM<?= number_format($total_spent, 2) ?></td>
                    <td style="color: <?= ($total_allocated - $total_spent) >= 0 ? '#27ae60' : '#e74c3c' ?>">
                        RM<?= number_format($total_allocated - $total_spent, 2) ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="detail-card">
        <div class="detail-header">Research Outputs & Reports</div>
        
        <?php if ($reports->num_rows === 0): ?>
            <p style="color: #7f8c8d;">No research outputs submitted.</p>
        <?php else: ?>
            <?php while ($report = $reports->fetch_assoc()): ?>
                <div class="output-item">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0 0 10px 0; color: #2c3e50;"><?= htmlspecialchars($report['title']) ?></h3>
                            <p style="color: #7f8c8d; margin: 5px 0;">
                                <i class='bx bx-calendar'></i> Submitted: <?= date('M d, Y', strtotime($report['submitted_at'])) ?>
                            </p>
                            
                            <?php if ($report['achievements']): ?>
                                <div style="margin: 10px 0;">
                                    <strong>Key Achievements:</strong>
                                    <p><?= htmlspecialchars($report['achievements']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="<?= htmlspecialchars($report['file_path']) ?>" target="_blank" style="padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; white-space: nowrap;">
                                <i class='bx bx-download'></i> Download Output
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <div class="detail-card">
        <div class="detail-header">Research Impact & Outcomes</div>
        <div style="background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107;">
            <p style="margin: 0; color: #856404;">
                <strong>Note:</strong> This section can be enhanced to include publications, patents, citations, 
                societal impact metrics, commercialization outcomes, and other research deliverables as per institutional requirements.
            </p>
        </div>
    </div>
</body>
</html>
