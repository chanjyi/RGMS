<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}

$banner = ['type' => '', 'text' => ''];

$proposal_id = intval($_GET['id'] ?? 0);

if ($proposal_id <= 0) {
    die('Invalid proposal ID');
}

// Handle follow-up flag from details view
if (isset($_POST['flag_research_detail'])) {
    $flag_note = trim($_POST['flag_note'] ?? 'Follow-up required');

    $stmt = $conn->prepare("UPDATE proposals SET health_status = 'AT_RISK', health_notes = ? WHERE id = ?");
    $stmt->bind_param("si", $flag_note, $proposal_id);

    if ($stmt->execute()) {
        $prop_query = $conn->prepare("SELECT researcher_email, title FROM proposals WHERE id = ?");
        $prop_query->bind_param("i", $proposal_id);
        $prop_query->execute();
        $prop_data = $prop_query->get_result()->fetch_assoc();

        $msg = "Your research '{$prop_data['title']}' has been flagged for follow-up. Reason: $flag_note";
        $notif = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, 'warning')");
        $notif->bind_param("ss", $prop_data['researcher_email'], $msg);
        $notif->execute();

        $banner = ['type' => 'success', 'text' => 'Research flagged for follow-up. Researcher notified.'];
    } else {
        $banner = ['type' => 'error', 'text' => 'Unable to flag research. Please try again.'];
    }
}

// Handle progress report review
if (isset($_POST['review_report'])) {
    $report_id = intval($_POST['report_id']);
    $decision = $_POST['decision']; // APPROVED or FOLLOW_UP_REQUIRED
    $remarks = trim($_POST['hod_remarks'] ?? '');
    $hod_email = $_SESSION['email'];

    $stmt = $conn->prepare("UPDATE progress_reports SET status = ?, hod_remarks = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
    $stmt->bind_param("sssi", $decision, $remarks, $hod_email, $report_id);

    if ($stmt->execute()) {
        $report_query = $conn->prepare("SELECT pr.*, p.title AS proposal_title FROM progress_reports pr JOIN proposals p ON pr.proposal_id = p.id WHERE pr.id = ?");
        $report_query->bind_param("i", $report_id);
        $report_query->execute();
        $report_data = $report_query->get_result()->fetch_assoc();

        $status_text = $decision === 'APPROVED' ? 'approved' : 'flagged for follow-up';
        $msg = "Your progress report '{$report_data['title']}' for '{$report_data['proposal_title']}' has been $status_text by HOD.";
        if ($remarks) {
            $msg .= " Remarks: $remarks";
        }

        $notif = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, ?)");
        $type = $decision === 'APPROVED' ? 'success' : 'warning';
        $notif->bind_param("sss", $report_data['researcher_email'], $msg, $type);
        $notif->execute();

        $banner = ['type' => 'success', 'text' => "Progress report $status_text. Researcher notified."];
    } else {
        $banner = ['type' => 'error', 'text' => 'Unable to review report. Please try again.'];
    }
}

// Handle health status update
if (isset($_POST['update_health_status'])) {
    $new_status = $_POST['health_status'];
    
    $stmt = $conn->prepare("UPDATE proposals SET health_status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $proposal_id);
    
    if ($stmt->execute()) {
        $banner = ['type' => 'success', 'text' => 'Research health status updated successfully.'];
    } else {
        $banner = ['type' => 'error', 'text' => 'Unable to update status. Please try again.'];
    }
}

// Fetch detailed research information
$proposal_sql = "
    SELECT 
        p.*,
        u.name AS researcher_name,
        u.email AS researcher_email
    FROM proposals p
    LEFT JOIN users u ON p.researcher_email = u.email
    WHERE p.id = ?
";
$stmt = $conn->prepare($proposal_sql);
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$proposal = $stmt->get_result()->fetch_assoc();

if (!$proposal) {
    die('Proposal not found');
}

// Fetch milestones
$milestones_sql = "SELECT * FROM milestones WHERE grant_id = ? ORDER BY target_date ASC";
$stmt = $conn->prepare($milestones_sql);
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$milestones_result = $stmt->get_result();
$milestone_rows = [];
while ($row = $milestones_result->fetch_assoc()) {
    $milestone_rows[] = $row;
}

// Fetch progress reports
$reports_sql = "SELECT pr.*, u.name AS reviewer_name FROM progress_reports pr LEFT JOIN users u ON pr.reviewed_by = u.email WHERE pr.proposal_id = ? ORDER BY pr.submitted_at DESC";
$stmt = $conn->prepare($reports_sql);
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$reports_result = $stmt->get_result();
$report_rows = [];
while ($row = $reports_result->fetch_assoc()) {
    $report_rows[] = $row;
}

// Fetch pending extension requests for this proposal
$extensions_sql = "SELECT er.id, er.report_id FROM extension_requests er JOIN progress_reports pr ON er.report_id = pr.id WHERE pr.proposal_id = ? AND er.status = 'PENDING'";
$stmt = $conn->prepare($extensions_sql);
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$extensions_result = $stmt->get_result();
$extensions_by_report = [];
while ($row = $extensions_result->fetch_assoc()) {
    $extensions_by_report[$row['report_id']] = $row;
}

$reports_by_milestone = [];
foreach ($milestone_rows as $milestone) {
    $reports_by_milestone[$milestone['id']] = [];
}

$first_pending_id = null;
foreach ($milestone_rows as $milestone) {
    $status_raw = strtoupper($milestone['status'] ?? 'PENDING');
    if ($status_raw !== 'COMPLETED') {
        $first_pending_id = $milestone['id'];
        break;
    }
}

$last_milestone_id = !empty($milestone_rows) ? $milestone_rows[count($milestone_rows) - 1]['id'] : null;

foreach ($report_rows as $report) {
    $assigned_id = null;
    $report_date = date('Y-m-d', strtotime($report['submitted_at']));

    foreach ($milestone_rows as $milestone) {
        $status_raw = strtoupper($milestone['status'] ?? 'PENDING');
        $completion_date = $milestone['completion_date'] ?? null;
        if ($status_raw === 'COMPLETED' && $completion_date && $completion_date === $report_date) {
            $assigned_id = $milestone['id'];
            break;
        }
    }

    if (!$assigned_id) {
        $assigned_id = $first_pending_id ?? $last_milestone_id;
    }

    if ($assigned_id) {
        $reports_by_milestone[$assigned_id][] = $report;
    }
}

// Fetch budget breakdown
$budget_sql = "SELECT * FROM budget_items WHERE proposal_id = ? ORDER BY category";
$stmt = $conn->prepare($budget_sql);
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$budget_items = $stmt->get_result();

$approved_date = $proposal['approved_at'] ?: $proposal['created_at'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Details - <?= htmlspecialchars($proposal['title']) ?></title>
    <link rel="stylesheet" href="styling/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f8f9fa; }
        .detail-card { background: white; padding: 25px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .detail-header { font-size: 24px; font-weight: 700; color: #2c3e50; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #3498db; }
        .detail-header-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #3498db; }
        .detail-header-bar .detail-header { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
        .info-item { padding: 12px; background: #f8f9fa; border-radius: 8px; }
        .info-label { font-size: 12px; color: #7f8c8d; margin-bottom: 5px; }
        .info-value { font-size: 16px; font-weight: 600; color: #2c3e50; }
        .milestone-timeline { position: relative; margin: 10px 0 5px 10px; padding-left: 30px; }
        .milestone-timeline::before { content: ''; position: absolute; left: 12px; top: 0; bottom: 0; width: 2px; background: #dfe6e9; }
        .milestone-item { position: relative; margin-bottom: 20px; }
        .milestone-node { position: absolute; left: -24px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; border-radius: 50%; background: #95a5a6; border: 3px solid #fff; box-shadow: 0 0 0 2px #dfe6e9; z-index: 2; }
        .milestone-item.completed .milestone-node { background: #27ae60; box-shadow: 0 0 0 2px #c8e6c9; }
        .milestone-item.in-progress .milestone-node { background: #f39c12; box-shadow: 0 0 0 2px #ffe0b2; }
        .milestone-item.delayed .milestone-node { background: #e74c3c; box-shadow: 0 0 0 2px #f8c9c2; }
        .milestone-content { background: #f8f9fa; padding: 14px 16px; border-radius: 8px; margin-left: 18px; border: 1px solid #eef1f4; min-height: 60px; display: flex; flex-direction: column; justify-content: center; }
        .milestone-item.completed .milestone-content { background: #e8f5e9; border-color: #c8e6c9; }
        .milestone-item.in-progress .milestone-content { background: #fff3e0; border-color: #ffe0b2; }
        .milestone-item.delayed .milestone-content { background: #fdecea; border-color: #f5c6cb; }
        .milestone-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; }
        .milestone-status { padding: 4px 12px; border-radius: 14px; font-size: 11px; font-weight: 600; color: #fff; text-transform: uppercase; }
        .milestone-status.completed { background: #27ae60; }
        .milestone-status.in-progress { background: #f39c12; }
        .milestone-status.pending { background: #7f8c8d; }
        .milestone-status.delayed { background: #e74c3c; }
        .report-item { background: #ffffff; border: 1px solid #eef1f4; border-radius: 8px; margin-top: 10px; overflow: hidden; }
        .report-header { padding: 12px 14px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; }
        .report-header:hover { background: #eef1f4; }
        .report-body { padding: 12px 14px; display: none; }
        .report-body.expanded { display: block; }
        .report-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .report-title { font-weight: 600; color: #2c3e50; }
        .report-date { color: #7f8c8d; font-size: 12px; }
        .report-section { margin-top: 8px; }
        .report-label { font-size: 12px; font-weight: 600; color: #3c5b6f; margin-bottom: 4px; }
        .report-text { color: #5f6c7b; font-size: 13px; }
        .btn-warning { background: #f1c40f; color: #4b3d00; padding: 10px 18px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
        .btn-warning:hover { background: #d4ac0d; }
        .alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .alert.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert.error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: #fff; padding: 20px; border-radius: 10px; width: 90%; max-width: 520px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .modal-close { font-size: 26px; cursor: pointer; color: #999; }
        .modal-close:hover { color: #000; }
.report-header-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .report-header-left { flex: 1; }
        .report-actions { margin-top: 10px; display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-action { background: #3498db; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .btn-action:hover { background: #2980b9; }
        .btn-action.approve { background: #27ae60; }
        .btn-action.approve:hover { background: #229954; }
        .btn-action.flag { background: #f39c12; }
        .btn-action.flag:hover { background: #e67e22; }
        .btn-action.view-report { background: #3498db; }
        .btn-action.view-report:hover { background: #2980b9; }
.status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-approved { background: #e8f5e9; color: #27ae60; border: 1px solid #c8e6c9; }
        .status-pending { background: #fff3e0; color: #f39c12; border: 1px solid #ffe0b2; }
        .status-followup { background: #ffebee; color: #e74c3c; border: 1px solid #ffcdd2; }
        .extension-link { margin-top: 6px; display: inline-flex; align-items: center; gap: 6px; padding: 4px 8px; background: #fef5e7; color: #c26b00; border: 1px solid #f5c26b; border-radius: 10px; font-size: 11px; font-weight: 600; text-decoration: none; }
        .extension-link:hover { background: #fdebd0; }
        .leave-warning { background: #fff8e1; border: 1px solid #ffe0b2; color: #8a5a00; padding: 10px 12px; border-radius: 6px; font-size: 13px; margin: 10px 0 14px; }
        .health-dropdown-wrapper { position: relative; display: inline-block; }
        .health-dropdown-toggle { padding: 8px 16px; border: 1px solid #ddd; border-radius: 6px; background: #f8f9fa; cursor: pointer; font-size: 13px; font-weight: 600; color: #2c3e50; display: flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .health-dropdown-toggle:hover { background: #eef1f4; border-color: #999; }
        .health-dropdown-menu { position: absolute; top: 100%; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); min-width: 200px; margin-top: 4px; z-index: 100; display: none; }
        .health-dropdown-menu.show { display: block; }
        .health-option { padding: 12px 16px; cursor: pointer; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; color: #2c3e50; transition: background 0.2s; }
        .health-option:hover { background: #f5f5f5; }
        .health-option.on-track { color: #27ae60; }
        .health-option.at-risk { color: #f39c12; }
        .health-option.delayed { color: #e74c3c; }
        .health-option.selected { background: #e8f5e9; }
        .health-option.at-risk.selected { background: #fff3e0; }
        .health-option.delayed.selected { background: #ffebee; }
        .budget-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .budget-table th, .budget-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .budget-table th { background: #3498db; color: white; font-weight: 600; }
        .progress-bar { width: 100%; height: 25px; background: #e9ecef; border-radius: 15px; overflow: hidden; margin: 10px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>

    <?php if (!empty($banner['text'])): ?>
        <div class="alert <?= $banner['type'] === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($banner['text']) ?>
        </div>
    <?php endif; ?>

    <div class="detail-card">
        <div class="detail-header-bar">
            <div class="detail-header"><?= htmlspecialchars($proposal['title']) ?></div>
            <form method="POST" action="" id="healthStatusForm">
                <div class="health-dropdown-wrapper">
                    <button type="button" class="health-dropdown-toggle" onclick="toggleHealthDropdown()">
                        <?php 
                            $status_icon = '';
                            $status_color = '';
                            if ($proposal['health_status'] == 'ON_TRACK') {
                                $status_icon = 'bx-check-circle';
                                $status_color = '#27ae60';
                                $status_text = 'On Track';
                            } elseif ($proposal['health_status'] == 'AT_RISK') {
                                $status_icon = 'bx-error-circle';
                                $status_color = '#f39c12';
                                $status_text = 'At Risk';
                            } else {
                                $status_icon = 'bx-x-circle';
                                $status_color = '#e74c3c';
                                $status_text = 'Delayed';
                            }
                        ?>
                        <i class='bx <?= $status_icon ?>' style="font-size: 15px; color: <?= $status_color ?>;"></i>
                        <?= $status_text ?>
                        <i class='bx bx-chevron-down' style="font-size: 15px; margin-left: 4px;"></i>
                    </button>
                    <div class="health-dropdown-menu" id="healthDropdownMenu">
                        <div class="health-option on-track <?= ($proposal['health_status'] == 'ON_TRACK') ? 'selected' : '' ?>" onclick="selectHealth('ON_TRACK')">
                            <i class='bx bx-check-circle'></i> On Track
                        </div>
                        <div class="health-option at-risk <?= ($proposal['health_status'] == 'AT_RISK') ? 'selected' : '' ?>" onclick="selectHealth('AT_RISK')">
                            <i class='bx bx-error-circle'></i> At Risk
                        </div>
                        <div class="health-option delayed <?= ($proposal['health_status'] == 'DELAYED') ? 'selected' : '' ?>" onclick="selectHealth('DELAYED')">
                            <i class='bx bx-x-circle'></i> Delayed
                        </div>
                    </div>
                </div>
                <input type="hidden" name="health_status" id="health_status_input" value="<?= $proposal['health_status'] ?? 'ON_TRACK' ?>">
            </form>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Researcher</div>
                <div class="info-value"><?= htmlspecialchars($proposal['researcher_name']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value"><?= htmlspecialchars($proposal['status']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Approved Budget</div>
                <div class="info-value">RM<?= number_format($proposal['approved_budget'], 2) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Amount Spent</div>
                <div class="info-value">RM<?= number_format($proposal['amount_spent'], 2) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Approved Date</div>
                <div class="info-value"><?= $approved_date ? date('M d, Y', strtotime($approved_date)) : 'N/A' ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Duration</div>
                <div class="info-value"><?= $proposal['duration_months'] ?> months</div>
            </div>
        </div>

        <?php if ($proposal['description']): ?>
            <div style="margin: 20px 0;">
                <strong>Description:</strong>
                <p><?= nl2br(htmlspecialchars($proposal['description'])) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="detail-card">
        <div class="detail-header">Budget Breakdown</div>
        
        <table class="budget-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Allocated</th>
                    <th>Spent</th>
                    <th>Remaining</th>
                    <th>Utilization</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($budget = $budget_items->fetch_assoc()): 
                    $remaining = $budget['allocated_amount'] - $budget['spent_amount'];
                    $percent = ($budget['allocated_amount'] > 0) ? ($budget['spent_amount'] / $budget['allocated_amount']) * 100 : 0;
                ?>
                    <tr>
                        <td><strong><?= nl2br(htmlspecialchars($budget['category'])) ?></strong></td>
                        <td>RM<?= number_format($budget['allocated_amount'], 2) ?></td>
                        <td>RM<?= number_format($budget['spent_amount'], 2) ?></td>
                        <td>RM<?= number_format($remaining, 2) ?></td>
                        <td>
                            <div class="progress-bar" style="height: 20px;">
                                <div class="progress-fill" style="width: <?= min($percent, 100) ?>%">
                                    <?= round($percent, 1) ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="detail-card">
        <div class="detail-header">Milestones</div>
        
        <?php if (count($milestone_rows) === 0): ?>
            <p style="color: #7f8c8d;">No milestones defined.</p>
        <?php else: ?>
            <div class="milestone-timeline">
                <?php foreach ($milestone_rows as $milestone):
                    $status_raw = strtoupper($milestone['status'] ?? 'PENDING');
                    $status_class = 'pending';
                    if ($status_raw === 'COMPLETED') { $status_class = 'completed'; }
                    elseif ($status_raw === 'IN_PROGRESS') { $status_class = 'in-progress'; }
                    elseif ($status_raw === 'DELAYED') { $status_class = 'delayed'; }
                ?>
                    <div class="milestone-item <?= $status_class ?>">
                        <div class="milestone-node"></div>
                        <div class="milestone-content">
                            <strong><?= htmlspecialchars($milestone['title']) ?></strong>
                            <p style="color: #7f8c8d; margin: 6px 0 4px;"><?= nl2br(htmlspecialchars($milestone['description'])) ?></p>
                            <div class="milestone-meta">
                                <small>Target: <?= date('M d, Y', strtotime($milestone['target_date'])) ?></small>
                                <span class="milestone-status <?= $status_class ?>"><?= $status_raw ?></span>
                            </div>
                            <?php if (!empty($reports_by_milestone[$milestone['id']])): ?>
                                <?php foreach ($reports_by_milestone[$milestone['id']] as $report): ?>
                                    <div class="report-item">
        <div class="report-header" onclick="toggleReport(this)">
                                            <div class="report-header-top">
                                                <div class="report-header-left">
                                                    <div class="report-title"><?= htmlspecialchars($report['title']) ?></div>
                                                    <div class="report-date">Submitted: <?= date('M d, Y', strtotime($report['submitted_at'])) ?></div>
                                                    <?php 
                                                        $report_status = strtoupper($report['status'] ?? 'PENDING_REVIEW');
                                                        if ($report_status !== 'PENDING_REVIEW'): 
                                                    ?>
                                                        <span class="status-badge <?= $report_status === 'APPROVED' ? 'status-approved' : 'status-followup' ?>" style="margin-top: 6px;">
                                                            <?= $report_status === 'APPROVED' ? 'APPROVED' : 'FOLLOW-UP REQUIRED' ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($extensions_by_report[$report['id']])): ?>
                                                        <a class="extension-link" href="hod_research_tracking.php?tab=pending-extensions" onclick="return confirmLeave('hod_research_tracking.php?tab=pending-extensions');">
                                                            <i class='bx bx-time'></i> Extension Request
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                <i class='bx bx-chevron-down' style="font-size: 20px; color: #7f8c8d; flex-shrink: 0;"></i>
                                            </div>
                                        </div>
                                        <div class="report-body">
                                            <div class="report-section">
                                                <div class="report-label">Achievements</div>
                                                <div class="report-text">
                                                    <?= $report['achievements'] ? nl2br(htmlspecialchars($report['achievements'])) : 'No achievements provided.' ?>
                                                </div>
                                            </div>
                                            <div class="report-section">
                                                <div class="report-label">Challenges</div>
                                                <div class="report-text">
                                                    <?= $report['challenges'] ? nl2br(htmlspecialchars($report['challenges'])) : 'No challenges provided.' ?>
                                                </div>
                                            </div>
<div class="report-actions">
                                                <a href="<?= htmlspecialchars($report['file_path']) ?>" target="_blank" class="btn-action view-report">
                                                    <i class='bx bx-file-pdf'></i> View
                                                </a>
                                                <?php 
                                                    $report_status = strtoupper($report['status'] ?? 'PENDING_REVIEW');
                                                    if ($report_status === 'PENDING_REVIEW'): 
                                                ?>
                                                    <button type="button" class="btn-action approve" onclick="reviewReport(<?= $report['id'] ?>, 'APPROVED')">
                                                        <i class='bx bx-check'></i> Approve
                                                    </button>
                                                    <button type="button" class="btn-action flag" onclick="openReportFlagModal(<?= $report['id'] ?>, '<?= htmlspecialchars($report['title'], ENT_QUOTES) ?>')">
                                                        <i class='bx bx-flag'></i> Flag
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($report['hod_remarks']): ?>
                                                <p style="margin-top: 10px; color: #7f8c8d; font-size: 13px;"><strong>HOD Remarks:</strong> <?= htmlspecialchars($report['hod_remarks']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    
    <div id="leavePageModal" class="modal" onclick="if(event.target===this) closeLeaveModal()">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin: 0;">Leave This Page?</h3>
                <span class="modal-close" onclick="closeLeaveModal()">&times;</span>
            </div>
            <div class="leave-warning">
                You are about to leave this page to view the Extension Requests tab. Any unsaved changes here will be lost.
            </div>
            <div style="text-align: right;">
                <button type="button" onclick="closeLeaveModal()" style="padding: 10px 16px; border: 1px solid #ddd; background: #fff; border-radius: 5px; cursor: pointer; margin-right: 10px;">Cancel</button>
                <button type="button" onclick="proceedLeave()" class="btn-warning">
                    Continue
                </button>
            </div>
        </div>
    </div>

<div id="reportFlagModal" class="modal" onclick="if(event.target===this) closeReportFlagModal()">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin: 0;">Flag Progress Report</h3>
                <span class="modal-close" onclick="closeReportFlagModal()">&times;</span>
            </div>
            <p id="reportFlagTitle" style="color: #7f8c8d; margin-bottom: 10px;"></p>
            <form method="POST" action="" id="reportFlagForm">
                <input type="hidden" name="report_id" id="report_flag_id">
                <input type="hidden" name="decision" value="FOLLOW_UP_REQUIRED">
                <div style="margin: 10px 0 14px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Remarks (Optional)</label>
                    <textarea name="hod_remarks" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" placeholder="Enter any comments or required actions..."></textarea>
                </div>
                <div style="text-align: right;">
                    <button type="button" onclick="closeReportFlagModal()" style="padding: 10px 16px; border: 1px solid #ddd; background: #fff; border-radius: 5px; cursor: pointer; margin-right: 10px;">Cancel</button>
                    <button type="submit" name="review_report" class="btn-flag-report">
                        <i class='bx bx-flag'></i> Flag Report
                    </button>
                </div>
            </form>
        </div>
    </div>
<script>
        function toggleReport(header) {
            const body = header.nextElementSibling;
            const icon = header.querySelector('i');
            body.classList.toggle('expanded');
            if (body.classList.contains('expanded')) {
                icon.className = 'bx bx-chevron-up';
            } else {
                icon.className = 'bx bx-chevron-down';
            }
        }
        function reviewReport(reportId, decision) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="report_id" value="${reportId}">
                <input type="hidden" name="decision" value="${decision}">
                <input type="hidden" name="hod_remarks" value="">
                <input type="hidden" name="review_report" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        function openReportFlagModal(reportId, reportTitle) {
            document.getElementById('report_flag_id').value = reportId;
            document.getElementById('reportFlagTitle').textContent = 'Report: ' + reportTitle;
            document.getElementById('reportFlagModal').classList.add('show');
        }
        function closeReportFlagModal() {
            document.getElementById('reportFlagModal').classList.remove('show');
        }
        function toggleHealthDropdown() {
            const menu = document.getElementById('healthDropdownMenu');
            menu.classList.toggle('show');
        }
        window.addEventListener('click', function(e) {
            const wrapper = document.querySelector('.health-dropdown-wrapper');
            if (!wrapper.contains(e.target)) {
                document.getElementById('healthDropdownMenu').classList.remove('show');
            }
        });
        let leaveTargetUrl = '';
        function confirmLeave(url) {
            leaveTargetUrl = url;
            document.getElementById('leavePageModal').classList.add('show');
            return false;
        }
        function closeLeaveModal() {
            document.getElementById('leavePageModal').classList.remove('show');
            leaveTargetUrl = '';
        }
        function proceedLeave() {
            document.getElementById('leavePageModal').classList.remove('show');
            if (!leaveTargetUrl) {
                return;
            }
            if (window.parent && window.parent !== window) {
                const parentModal = window.parent.document.getElementById('researchDetailModal');
                if (parentModal) {
                    parentModal.classList.remove('show');
                }
                window.parent.location.href = leaveTargetUrl;
                return;
            }
            if (window.opener && !window.opener.closed) {
                window.opener.location.href = leaveTargetUrl;
                window.opener.focus();
                return;
            }
            window.location.href = leaveTargetUrl;
        }
        function selectHealth(status) {
            document.querySelectorAll('#healthDropdownMenu .health-option').forEach(opt => opt.classList.remove('selected'));
            event.target.closest('.health-option').classList.add('selected');
            document.getElementById('health_status_input').value = status;
            document.getElementById('healthDropdownMenu').classList.remove('show');
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="health_status" value="${status}">
                <input type="hidden" name="update_health_status" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
