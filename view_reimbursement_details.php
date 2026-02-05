<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}

$request_id = intval($_GET['id'] ?? 0);

if ($request_id <= 0) {
    die('Invalid request ID');
}

// Fetch reimbursement request details
$request_sql = "
    SELECT 
        rr.*,
        p.title AS proposal_title,
        u.name AS researcher_name
    FROM reimbursement_requests rr
    JOIN proposals p ON rr.grant_id = p.id
    LEFT JOIN users u ON rr.researcher_email = u.email
    WHERE rr.id = ?
";
$stmt = $conn->prepare($request_sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
    die('Reimbursement request not found');
}

// Fetch associated expenditures
$expenditures_sql = "
    SELECT 
        e.*,
        bi.category,
        bi.allocated_amount,
        bi.spent_amount
    FROM expenditures e
    JOIN budget_items bi ON e.budget_item_id = bi.id
    WHERE e.reimbursement_request_id = ?
    ORDER BY bi.category, e.transaction_date DESC
";
$stmt = $conn->prepare($expenditures_sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$expenditures_result = $stmt->get_result();

// Fetch all results into array and aggregate by category
$expenditures_list = [];
$budget_summary = [];
while ($row = $expenditures_result->fetch_assoc()) {
    $expenditures_list[] = $row;
    
    $budget_id = $row['budget_item_id'];
    if (!isset($budget_summary[$budget_id])) {
        $budget_summary[$budget_id] = [
            'category' => $row['category'],
            'allocated' => $row['allocated_amount'],
            'spent' => $row['spent_amount'],
            'claims' => 0
        ];
    }
    $budget_summary[$budget_id]['claims'] += $row['amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reimbursement Details</title>
    <link rel="stylesheet" href="styling/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f5f5f5; }
        .detail-card { background: white; padding: 25px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .detail-header { font-size: 24px; font-weight: 700; color: #2c3e50; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #27ae60; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
        .info-item { padding: 12px; background: #f8f9fa; border-radius: 8px; }
        .info-label { font-size: 12px; color: #7f8c8d; margin-bottom: 5px; }
        .info-value { font-size: 16px; font-weight: 600; color: #2c3e50; }
        .table-wrapper { overflow-x: auto; margin: 20px 0; border-radius: 8px; }
        .budget-summary-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .budget-summary-table th, .budget-summary-table td { padding: 12px; border-bottom: 1px solid #ddd; }
        .budget-summary-table th { background: #27ae60; color: white; font-weight: 600; text-align: left; }
        .budget-summary-table td.amount { text-align: right; font-weight: 600; }
        .budget-summary-table tr.total-row { background: #e8f5e9; font-weight: 700; }
        .budget-summary-table tr.total-row td { border-top: 2px solid #27ae60; border-bottom: 2px solid #27ae60; }
        .expenditure-table { width: 100%; border-collapse: collapse; }
        .expenditure-table th, .expenditure-table td { padding: 12px; border-bottom: 1px solid #ddd; }
        .expenditure-table th { background: #3498db; color: white; font-weight: 600; text-align: left; }
        .expenditure-table td:nth-child(2) { white-space: normal; min-width: 200px; }
        .expenditure-table tr:hover { background: #f8f9fa; }
        .expenditure-table td.amount { text-align: right; font-weight: 600; color: #2c3e50; }
        .receipt-btn { padding: 6px 10px; background: #95a5a6; color: white; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .section-title { font-size: 18px; font-weight: 600; color: #2c3e50; margin: 25px 0 15px; padding-left: 10px; border-left: 4px solid #3498db; }
    </style>
</head>
<body>

    <div class="detail-card">
        <div class="detail-header">Reimbursement Request Details</div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Project</div>
                <div class="info-value"><?= htmlspecialchars($request['proposal_title']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Researcher</div>
                <div class="info-value"><?= htmlspecialchars($request['researcher_name']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Request Date</div>
                <div class="info-value"><?= date('M d, Y', strtotime($request['requested_at'])) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value"><?= htmlspecialchars($request['status']) ?></div>
            </div>
        </div>

        <div style="margin: 20px 0;">
            <strong>Justification:</strong>
            <p><?= htmlspecialchars($request['justification']) ?></p>
        </div>

        <div style="background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;">
            <div style="font-size: 18px; margin-bottom: 10px;">Total Reimbursement Amount</div>
            <div style="font-size: 36px; font-weight: 700;">RM<?= number_format($request['total_amount'], 2) ?></div>
        </div>
    </div>

    <div class="detail-card">
        <div class="detail-header">Budget Impact Summary</div>
        
        <?php if (count($expenditures_list) === 0): ?>
            <p style="color: #7f8c8d;">No expenditures found.</p>
        <?php else: ?>
            <!-- Budget Summary by Category -->
            <table class="budget-summary-table">
                <thead>
                    <tr>
                        <th>Budget Category</th>
                        <th class="amount">Allocated Budget</th>
                        <th class="amount">Already Spent</th>
                        <th class="amount">This Claim</th>
                        <th class="amount">After Approval</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_allocated = 0;
                    $total_spent = 0;
                    $total_claim = 0;
                    foreach ($budget_summary as $budget): 
                        $after_approval = $budget['allocated'] - ($budget['spent'] + $budget['claims']);
                        $total_allocated += $budget['allocated'];
                        $total_spent += $budget['spent'];
                        $total_claim += $budget['claims'];
                    ?>
                        <tr>
                            <td><span style="padding: 4px 10px; background: #27ae60; color: white; border-radius: 4px; font-size: 13px; font-weight: 600;"><?= htmlspecialchars($budget['category']) ?></span></td>
                            <td class="amount">RM<?= number_format($budget['allocated'], 2) ?></td>
                            <td class="amount" style="color: #7f8c8d;">RM<?= number_format($budget['spent'], 2) ?></td>
                            <td class="amount" style="color: #f39c12; font-weight: 700;">RM<?= number_format($budget['claims'], 2) ?></td>
                            <td class="amount" style="color: <?= $after_approval >= 0 ? '#27ae60' : '#e74c3c' ?>; font-weight: 700;">RM<?= number_format($after_approval, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php $total_after = $total_allocated - ($total_spent + $total_claim); ?>
                    <tr class="total-row">
                        <td style="text-align: right; padding-right: 12px;">TOTAL:</td>
                        <td class="amount">RM<?= number_format($total_allocated, 2) ?></td>
                        <td class="amount" style="color: #7f8c8d;">RM<?= number_format($total_spent, 2) ?></td>
                        <td class="amount" style="color: #f39c12;">RM<?= number_format($total_claim, 2) ?></td>
                        <td class="amount" style="color: <?= $total_after >= 0 ? '#27ae60' : '#e74c3c' ?>;">RM<?= number_format($total_after, 2) ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Detailed Expenditure List -->
            <div class="section-title">Expenditure Details</div>
            <div class="table-wrapper">
                <table class="expenditure-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Transaction Date</th>
                            <th class="amount">Amount</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenditures_list as $exp): ?>
                            <tr>
                                <td><span style="padding: 4px 8px; background: #3498db; color: white; border-radius: 4px; font-size: 12px; font-weight: 600;"><?= htmlspecialchars($exp['category']) ?></span></td>
                                <td><?= htmlspecialchars($exp['description']) ?></td>
                                <td><?= date('M d, Y', strtotime($exp['transaction_date'])) ?></td>
                                <td class="amount" style="font-weight: 700; color: #27ae60;">RM<?= number_format($exp['amount'], 2) ?></td>
                                <td>
                                    <?php if ($exp['receipt_path']): ?>
                                        <a href="<?= htmlspecialchars($exp['receipt_path']) ?>" target="_blank" class="receipt-btn">
                                            <i class='bx bx-receipt'></i> View
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d; font-size: 12px;">--</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($request['hod_remarks']): ?>
        <div class="detail-card">
            <div class="detail-header">HOD Remarks</div>
            <p><?= htmlspecialchars($request['hod_remarks']) ?></p>
        </div>
    <?php endif; ?>
</body>
</html>
