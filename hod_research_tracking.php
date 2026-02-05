<?php
session_start();
require 'config.php';

// Verify HOD access
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}

// Get HOD information
$hod_query = "SELECT id, department_id FROM users WHERE email = ? AND role = 'hod'";
$hod_stmt = $conn->prepare($hod_query);
$hod_stmt->bind_param("s", $_SESSION['email']);
$hod_stmt->execute();
$hod_result = $hod_stmt->get_result();
$hod_data = $hod_result->fetch_assoc();
$hod_id = $hod_data['id'] ?? null;
$department_id = $hod_data['department_id'] ?? null;

$banner = ['type' => '', 'text' => ''];

// ============ HANDLE ACTIONS ============

// 1. FLAG RESEARCH AS FOLLOW-UP REQUIRED
if (isset($_POST['flag_research'])) {
    $proposal_id = intval($_POST['proposal_id']);
    $flag_note = mysqli_real_escape_string($conn, $_POST['flag_note'] ?? 'Follow-up required');
    
    $stmt = $conn->prepare("UPDATE proposals SET health_status = 'AT_RISK', health_notes = ? WHERE id = ?");
    $stmt->bind_param("si", $flag_note, $proposal_id);
    
    if ($stmt->execute()) {
        // Notify researcher
        $prop_query = $conn->prepare("SELECT researcher_email, title FROM proposals WHERE id = ?");
        $prop_query->bind_param("i", $proposal_id);
        $prop_query->execute();
        $prop_data = $prop_query->get_result()->fetch_assoc();
        
        $msg = "Your research '{$prop_data['title']}' has been flagged for follow-up. Reason: $flag_note";
        $notif = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, 'warning')");
        $notif->bind_param("ss", $prop_data['researcher_email'], $msg);
        $notif->execute();
        
        $banner = ['type' => 'success', 'text' => 'Research flagged for follow-up. Researcher notified.'];
    }
}

// 2. APPROVE/REJECT EXTENSION REQUEST
if (isset($_POST['extension_decision'])) {
    $extension_id = intval($_POST['extension_id']);
    $decision = $_POST['decision']; // APPROVED or REJECTED
    $hod_remarks = mysqli_real_escape_string($conn, $_POST['hod_remarks'] ?? '');
    
    $stmt = $conn->prepare("UPDATE extension_requests SET status = ?, reviewed_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $decision, $extension_id);
    
    if ($stmt->execute()) {
        // Get extension details
        $ext_query = $conn->prepare("SELECT er.*, pr.title, pr.proposal_id FROM extension_requests er 
                                     JOIN progress_reports pr ON er.report_id = pr.id WHERE er.id = ?");
        $ext_query->bind_param("i", $extension_id);
        $ext_query->execute();
        $ext_data = $ext_query->get_result()->fetch_assoc();
        
        // Update progress report deadline if approved
        if ($decision === 'APPROVED') {
            $conn->prepare("UPDATE progress_reports SET deadline = ? WHERE id = ?")->execute([$ext_data['new_deadline'], $ext_data['report_id']]);
            $msg = "Extension request APPROVED for '{$ext_data['title']}'. New deadline: {$ext_data['new_deadline']}";
        } else {
            $msg = "Extension request REJECTED for '{$ext_data['title']}'. Remarks: $hod_remarks";
        }
        
        $notif = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, ?)");
        $type = $decision === 'APPROVED' ? 'success' : 'warning';
        $notif->bind_param("sss", $ext_data['researcher_email'], $msg, $type);
        $notif->execute();
        
        $banner = ['type' => 'success', 'text' => "Extension request $decision."];
    }
}

// 3. APPROVE/REJECT REIMBURSEMENT REQUEST
if (isset($_POST['reimbursement_decision'])) {
    $request_id = intval($_POST['request_id']);
    $decision = $_POST['decision']; // APPROVED or REJECTED
    $hod_remarks = mysqli_real_escape_string($conn, $_POST['hod_remarks'] ?? '');
    
    $conn->begin_transaction();
    
    try {
        // Update request status
        $stmt = $conn->prepare("UPDATE reimbursement_requests SET status = ?, hod_remarks = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $decision, $hod_remarks, $request_id);
        $stmt->execute();
        
        // Get request details
        $req_query = $conn->prepare("SELECT * FROM reimbursement_requests WHERE id = ?");
        $req_query->bind_param("i", $request_id);
        $req_query->execute();
        $req_data = $req_query->get_result()->fetch_assoc();
        
        if ($decision === 'APPROVED') {
            // Update expenditures status
            $conn->query("UPDATE expenditures SET status = 'APPROVED' WHERE reimbursement_request_id = $request_id");
            
            // Update budget_items.spent_amount
            $exp_query = $conn->query("SELECT e.*, bi.id as budget_item_id FROM expenditures e 
                                       JOIN budget_items bi ON e.budget_item_id = bi.id 
                                       WHERE e.reimbursement_request_id = $request_id");
            
            while ($exp = $exp_query->fetch_assoc()) {
                $conn->query("UPDATE budget_items SET spent_amount = spent_amount + {$exp['amount']} WHERE id = {$exp['budget_item_id']}");
            }
            
            // Update proposals.amount_spent
            $conn->query("UPDATE proposals SET amount_spent = amount_spent + {$req_data['total_amount']} WHERE id = {$req_data['grant_id']}");
            
            $msg = "Reimbursement APPROVED: RM" . number_format($req_data['total_amount'], 2) . " released.";
            $notif_type = 'success';
        } else {
            // Reject expenditures
            $conn->query("UPDATE expenditures SET status = 'REJECTED' WHERE reimbursement_request_id = $request_id");
            $msg = "Reimbursement REJECTED. Reason: $hod_remarks";
            $notif_type = 'warning';
        }
        
        // Notify researcher
        $notif = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, ?)");
        $notif->bind_param("sss", $req_data['researcher_email'], $msg, $notif_type);
        $notif->execute();
        
        $conn->commit();
        $banner = ['type' => 'success', 'text' => "Reimbursement request $decision."];
        
    } catch (Exception $e) {
        $conn->rollback();
        $banner = ['type' => 'error', 'text' => 'Error processing reimbursement: ' . $e->getMessage()];
    }
}

// 4. MARK RESEARCH AS ARCHIVED
if (isset($_POST['archive_research'])) {
    $proposal_id = intval($_POST['proposal_id']);
    
    $stmt = $conn->prepare("UPDATE proposals SET status = 'ARCHIVED' WHERE id = ?");
    $stmt->bind_param("i", $proposal_id);
    
    if ($stmt->execute()) {
        $banner = ['type' => 'success', 'text' => 'Research project archived successfully.'];
    }
}

// ============ FETCH DATA ============

// Active Research Projects (APPROVED + RECOMMENDED status)
$active_research_sql = "
    SELECT 
        p.id,
        p.title,
        p.researcher_email,
        p.status,
        p.health_status,
        p.created_at,
        p.approved_at,
        p.approved_budget,
        p.amount_spent,
        p.duration_months,
        p.priority,
        u.name AS researcher_name,
        COUNT(DISTINCT pr.id) AS report_count,
        COUNT(DISTINCT m.id) AS total_milestones,
        COUNT(DISTINCT CASE WHEN UPPER(TRIM(m.status)) = 'COMPLETED' THEN m.id END) AS completed_milestones,
        MAX(pr.submitted_at) AS last_report_date,
        (SELECT COUNT(*) FROM extension_requests er 
         JOIN progress_reports pr2 ON er.report_id = pr2.id 
         WHERE pr2.proposal_id = p.id AND er.status = 'PENDING') AS pending_extensions,
        (SELECT COUNT(*) FROM reimbursement_requests rr 
         WHERE rr.grant_id = p.id AND rr.status = 'PENDING') AS pending_reimbursements
    FROM proposals p
    LEFT JOIN users u ON p.researcher_email = u.email
    LEFT JOIN progress_reports pr ON p.id = pr.proposal_id
    LEFT JOIN milestones m ON p.id = m.grant_id
    WHERE UPPER(TRIM(p.status)) IN ('APPROVED','RECOMMENDED')
    GROUP BY p.id
    ORDER BY p.health_status DESC, p.approved_at DESC
";;
$active_research = $conn->query($active_research_sql);

// Completed/Archived Research Projects (mapped to available statuses)
$completed_research_sql = "
    SELECT 
        p.id,
        p.title,
        p.researcher_email,
        p.status,
        p.approved_at,
        p.approved_budget,
        p.amount_spent,
        u.name AS researcher_name,
        COUNT(DISTINCT pr.id) AS report_count,
        (p.approved_budget - p.amount_spent) AS remaining_budget
    FROM proposals p
    LEFT JOIN users u ON p.researcher_email = u.email
    LEFT JOIN progress_reports pr ON p.id = pr.proposal_id
    -- Map completed/archived concept to available enums: use APPROVED (finished/active) and REJECTED/APPEAL_REJECTED as archived bucket
    WHERE UPPER(TRIM(p.status)) IN ('APPROVED', 'REJECTED', 'APPEAL_REJECTED')
    GROUP BY p.id
    ORDER BY p.approved_at DESC
";
$completed_research = $conn->query($completed_research_sql);

// Pending Progress Reports
$pending_reports_sql = "
    SELECT 
        pr.*,
        p.title AS proposal_title,
        p.id AS proposal_id,
        u.name AS researcher_name
    FROM progress_reports pr
    JOIN proposals p ON pr.proposal_id = p.id
    LEFT JOIN users u ON pr.researcher_email = u.email
    WHERE pr.status = 'PENDING_REVIEW'
    ORDER BY pr.submitted_at DESC
";
$pending_reports = $conn->query($pending_reports_sql);

// Pending Extension Requests
$pending_extensions_sql = "
    SELECT 
        er.*,
        pr.title AS report_title,
        pr.deadline AS current_deadline,
        p.title AS proposal_title,
        p.id AS proposal_id
    FROM extension_requests er
    JOIN progress_reports pr ON er.report_id = pr.id
    JOIN proposals p ON pr.proposal_id = p.id
    WHERE er.status = 'PENDING'
    ORDER BY er.requested_at DESC
";
$pending_extensions = $conn->query($pending_extensions_sql);

// Pending Reimbursement Requests
$pending_reimbursements_sql = "
    SELECT 
        rr.*,
        p.title AS proposal_title,
        u.name AS researcher_name
    FROM reimbursement_requests rr
    JOIN proposals p ON rr.grant_id = p.id
    LEFT JOIN users u ON rr.researcher_email = u.email
    WHERE rr.status = 'PENDING'
    ORDER BY rr.requested_at DESC
";
$pending_reimbursements = $conn->query($pending_reimbursements_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Progress Tracking - RGMS</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/hod_pages.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .progress-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .progress-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .progress-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .progress-researcher {
            font-size: 14px;
            color: #7f8c8d;
        }
        .progress-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
        }
        .budget-bar {
            width: 100%;
            height: 25px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin: 10px 0;
            position: relative;
        }
        .budget-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-success {
            background: #e8f5e9;
            color: #27ae60;
        }
        .badge-warning {
            background: #fff3e0;
            color: #f39c12;
        }
        .badge-danger {
            background: #ffebee;
            color: #e74c3c;
        }
        .badge-info {
            background: #e3f2fd;
            color: #3498db;
        }
        .badge-secondary {
            background: #f5f5f5;
            color: #95a5a6;
        }
        .badge-pending {
            background: #fff3e0;
            color: #e65100;
        }
        .action-btns {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-view {
            background: #3498db;
            color: white;
        }
        .btn-view:hover {
            background: #2980b9;
        }
        .btn-flag {
            background: #e74c3c;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-flag:hover {
            background: #c0392b;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
        }
        .modal.show {
            display: block;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 3% auto;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 900px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        .modal-close {
            font-size: 32px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            line-height: 20px;
        }
        .modal-close:hover {
            color: #000;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .info-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }
        .decision-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .btn-approve {
            background: #27ae60;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-approve:hover {
            background: #229954;
        }
        .btn-reject {
            background: #e74c3c;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-reject:hover {
            background: #c0392b;
        }
        .expenditure-list {
            margin: 20px 0;
        }
        .expenditure-item {
            background: white;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .milestone-list {
            margin: 15px 0;
        }
        .milestone-item {
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            margin-bottom: 8px;
            border-radius: 4px;
        }
        .milestone-completed {
            border-left-color: #27ae60;
            background: #e8f5e9;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <section class="home-section">
        <div class="welcome-text">
            Research Progress Tracking
        </div>
        <hr style="opacity: 0.3; margin: 20px 0;">

        <?php if (!empty($banner['text'])): ?>
            <div class="alert <?= $banner['type'] === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($banner['text']) ?>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div style="margin-bottom: 0;">
            <button class="tab-btn active" data-tab="active-research" onclick="openTab(event, 'active-research')">
                <i class='bx bx-trending-up'></i> Active Research
            </button>
            <button class="tab-btn" data-tab="pending-extensions" onclick="openTab(event, 'pending-extensions')">
                <i class='bx bx-time'></i> Extension Requests
            </button>
            <button class="tab-btn" data-tab="pending-reimbursements" onclick="openTab(event, 'pending-reimbursements')">
                <i class='bx bx-wallet'></i> Reimbursements
            </button>
            <button class="tab-btn" data-tab="completed-research" onclick="openTab(event, 'completed-research')">
                <i class='bx bx-archive'></i> Completed/Archived
            </button>
        </div>

        <!-- TAB 1: ACTIVE RESEARCH -->
        <div id="active-research" class="tab-content active">
            <?php if ($active_research->num_rows === 0): ?>
                <div class="page-placeholder">
                    <div class="placeholder-icon">
                        <i class='bx bx-trending-up'></i>
                    </div>
                    <h2 class="placeholder-title">No Active or Recommended Projects</h2>
                    <p class="placeholder-text">
                        Approved or reviewer-recommended research projects will appear here for tracking and monitoring.
                    </p>
                </div>
            <?php else: ?>
                <?php while ($project = $active_research->fetch_assoc()): 
                    $budget_percent = ($project['approved_budget'] > 0) 
                        ? ($project['amount_spent'] / $project['approved_budget']) * 100 
                        : 0;
                    $milestone_percent = ($project['total_milestones'] > 0) 
                        ? ($project['completed_milestones'] / $project['total_milestones']) * 100 
                        : 0;
                ?>
                    <div class="progress-card">
                        <div class="progress-header">
                            <div>
                                <div class="progress-title"><?= htmlspecialchars($project['title']) ?></div>
                                <div class="progress-researcher">
                                    <i class='bx bx-user'></i> <?= htmlspecialchars($project['researcher_name']) ?>
                                </div>
                            </div>
                            <div>
                                <?php 
                                    $health = $project['health_status'] ?? 'ON_TRACK';
                                    $health_colors = [
                                        'ON_TRACK' => ['class' => 'badge-success', 'icon' => 'bx-check-circle', 'label' => 'ON TRACK'],
                                        'AT_RISK' => ['class' => 'badge-warning', 'icon' => 'bx-alert-circle', 'label' => 'AT RISK'],
                                        'DELAYED' => ['class' => 'badge-danger', 'icon' => 'bx-x-circle', 'label' => 'DELAYED'],
                                        'COMPLETED' => ['class' => 'badge-info', 'icon' => 'bx-check', 'label' => 'COMPLETED'],
                                        'ARCHIVED' => ['class' => 'badge-secondary', 'icon' => 'bx-archive', 'label' => 'ARCHIVED']
                                    ];
                                    $config = $health_colors[$health] ?? $health_colors['ON_TRACK'];
                                ?>
                                <span class="badge <?= $config['class'] ?>">
                                    <i class='bx <?= $config['icon'] ?>'></i> <?= $config['label'] ?>
                                </span>
                            </div>
                        </div>

                        <div class="progress-stats">
                            <div class="stat-box">
                                <div class="stat-label">Approved Budget</div>
                                <div class="stat-value">RM<?= number_format($project['approved_budget'], 2) ?></div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Amount Spent</div>
                                <div class="stat-value">RM<?= number_format($project['amount_spent'], 2) ?></div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Milestones Progress</div>
                                <div class="stat-value">
                                    <?= $project['completed_milestones'] ?>/<?= $project['total_milestones'] ?>
                                    (<?= round($milestone_percent) ?>%)
                                </div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Progress Reports</div>
                                <div class="stat-value"><?= $project['report_count'] ?> Submitted</div>
                            </div>
                        </div>

                        <div>
                            <div class="stat-label">Budget Utilization</div>
                            <div class="budget-bar">
                                <div class="budget-fill" style="width: <?= min($budget_percent, 100) ?>%">
                                    <?= round($budget_percent, 1) ?>%
                                </div>
                            </div>
                        </div>

                        <?php if ($project['pending_extensions'] > 0 || $project['pending_reimbursements'] > 0): ?>
                            <div style="margin-top: 15px;">
                                <?php if ($project['pending_extensions'] > 0): ?>
                                    <span class="badge badge-pending">
                                        <i class='bx bx-time'></i> <?= $project['pending_extensions'] ?> Extension Request(s)
                                    </span>
                                <?php endif; ?>
                                <?php if ($project['pending_reimbursements'] > 0): ?>
                                    <span class="badge badge-pending">
                                        <i class='bx bx-wallet'></i> <?= $project['pending_reimbursements'] ?> Reimbursement(s)
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="action-btns">
                            <button class="btn-action btn-view" onclick="viewResearchDetails(<?= $project['id'] ?>)">
                                <i class='bx bx-show'></i> View Details
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- TAB 2: PENDING EXTENSION REQUESTS -->
        <div id="pending-extensions" class="tab-content">
            <?php if ($pending_extensions->num_rows === 0): ?>
                <div class="page-placeholder">
                    <div class="placeholder-icon">
                        <i class='bx bx-time'></i>
                    </div>
                    <h2 class="placeholder-title">No Pending Extension Requests</h2>
                    <p class="placeholder-text">
                        Deadline extension requests from researchers will appear here for approval.
                    </p>
                </div>
            <?php else: ?>
                <?php while ($extension = $pending_extensions->fetch_assoc()): ?>
                    <div class="progress-card">
                        <div class="progress-header">
                            <div>
                                <div class="progress-title">Extension Request</div>
                                <div class="progress-researcher">
                                    Project: <?= htmlspecialchars($extension['proposal_title']) ?><br>
                                    Report: <?= htmlspecialchars($extension['report_title']) ?>
                                </div>
                            </div>
                            <div>
                                <span class="badge badge-pending">PENDING</span>
                            </div>
                        </div>

                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Current Deadline</div>
                                <div class="info-value"><?= date('M d, Y', strtotime($extension['current_deadline'])) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Requested New Deadline</div>
                                <div class="info-value" style="color: #e67e22;">
                                    <?= date('M d, Y', strtotime($extension['new_deadline'])) ?>
                                </div>
                            </div>
                        </div>

                        <div style="margin: 15px 0;">
                            <strong>Justification:</strong>
                            <p><?= htmlspecialchars($extension['justification']) ?></p>
                        </div>

                        <div class="action-btns">
                            <button class="btn-action btn-approve" onclick="approveExtension(<?= $extension['id'] ?>)">
                                <i class='bx bx-check'></i> Approve
                            </button>
                            <button class="btn-action btn-reject" onclick="rejectExtension(<?= $extension['id'] ?>)">
                                <i class='bx bx-x'></i> Reject
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- TAB 3: PENDING REIMBURSEMENTS -->
        <div id="pending-reimbursements" class="tab-content">
            <?php if ($pending_reimbursements->num_rows === 0): ?>
                <div class="page-placeholder">
                    <div class="placeholder-icon">
                        <i class='bx bx-wallet'></i>
                    </div>
                    <h2 class="placeholder-title">No Pending Reimbursement Requests</h2>
                    <p class="placeholder-text">
                        Reimbursement requests from researchers will appear here for approval and fund release.
                    </p>
                </div>
            <?php else: ?>
                <?php while ($reimbursement = $pending_reimbursements->fetch_assoc()): ?>
                    <div class="progress-card">
                        <div class="progress-header">
                            <div>
                                <div class="progress-title">Reimbursement Request</div>
                                <div class="progress-researcher">
                                    Project: <?= htmlspecialchars($reimbursement['proposal_title']) ?><br>
                                    Researcher: <?= htmlspecialchars($reimbursement['researcher_name']) ?>
                                </div>
                            </div>
                            <div>
                                <span class="badge badge-pending">PENDING APPROVAL</span>
                            </div>
                        </div>

                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Total Amount</div>
                                <div class="info-value" style="color: #27ae60; font-size: 24px;">
                                    RM<?= number_format($reimbursement['total_amount'], 2) ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Requested On</div>
                                <div class="info-value"><?= date('M d, Y', strtotime($reimbursement['requested_at'])) ?></div>
                            </div>
                        </div>

                        <div style="margin: 15px 0;">
                            <strong>Justification:</strong>
                            <p><?= htmlspecialchars($reimbursement['justification']) ?></p>
                        </div>

                        <div class="action-btns">
                            <button class="btn-action btn-view" onclick="viewReimbursementDetails(<?= $reimbursement['id'] ?>)">
                                <i class='bx bx-receipt'></i> View Expenditures
                            </button>
                            <button class="btn-action btn-approve" onclick="approveReimbursement(<?= $reimbursement['id'] ?>)">
                                <i class='bx bx-check'></i> Approve & Release Funds
                            </button>
                            <button class="btn-action btn-reject" onclick="rejectReimbursement(<?= $reimbursement['id'] ?>)">
                                <i class='bx bx-x'></i> Reject
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- TAB 4: COMPLETED/ARCHIVED RESEARCH -->
        <div id="completed-research" class="tab-content">
            <?php if ($completed_research->num_rows === 0): ?>
                <div class="page-placeholder">
                    <div class="placeholder-icon">
                        <i class='bx bx-archive'></i>
                    </div>
                    <h2 class="placeholder-title">No Completed/Archived Projects</h2>
                    <p class="placeholder-text">
                        Approved and closed-out (rejected/appeal-rejected) projects will appear here.
                    </p>
                </div>
            <?php else: ?>
                <?php while ($project = $completed_research->fetch_assoc()): ?>
                    <div class="progress-card">
                        <div class="progress-header">
                            <div>
                                <div class="progress-title"><?= htmlspecialchars($project['title']) ?></div>
                                <div class="progress-researcher">
                                    Researcher: <?= htmlspecialchars($project['researcher_name']) ?>
                                </div>
                            </div>
                            <div>
                                <span class="badge" style="background: #95a5a6; color: white;">
                                    <?= $project['status'] ?>
                                </span>
                            </div>
                        </div>

                        <div class="progress-stats">
                            <div class="stat-box">
                                <div class="stat-label">Approved Budget</div>
                                <div class="stat-value">RM<?= number_format($project['approved_budget'], 2) ?></div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Total Spent</div>
                                <div class="stat-value">RM<?= number_format($project['amount_spent'], 2) ?></div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Remaining</div>
                                <div class="stat-value" style="color: <?= $project['remaining_budget'] > 0 ? '#27ae60' : '#e74c3c' ?>">
                                    RM<?= number_format($project['remaining_budget'], 2) ?>
                                </div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Progress Reports</div>
                                <div class="stat-value"><?= $project['report_count'] ?> Submitted</div>
                            </div>
                        </div>

                        <div class="action-btns">
                            <button class="btn-action btn-view" onclick="viewCompletedResearch(<?= $project['id'] ?>)">
                                <i class='bx bx-show'></i> View Final Output
                            </button>
                            <?php if ($project['status'] !== 'ARCHIVED'): ?>
                                <button class="btn-action" style="background: #95a5a6; color: white;" onclick="archiveResearch(<?= $project['id'] ?>)">
                                    <i class='bx bx-archive'></i> Archive
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modals will be added via JavaScript -->
    <div id="flagModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Flag Research for Follow-up</h2>
                <span class="modal-close" onclick="closeFlagModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="proposal_id" id="flag_proposal_id">
                <div style="margin: 20px 0;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">Research Project:</label>
                    <p id="flag_project_title" style="color: #7f8c8d; margin-bottom: 20px;"></p>
                    
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">Reason for Flag:</label>
                    <textarea name="flag_note" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required placeholder="e.g., Behind schedule, needs additional monitoring..."></textarea>
                </div>
                <div style="text-align: right;">
                    <button type="button" onclick="closeFlagModal()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 5px; cursor: pointer; margin-right: 10px;">Cancel</button>
                    <button type="submit" name="flag_research" class="btn-flag">
                        <i class='bx bx-flag'></i> Flag Research
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="extensionDecisionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="extensionDecisionTitle">Extension Request Decision</h2>
                <span class="modal-close" onclick="closeExtensionDecisionModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="extension_id" id="extension_decision_id">
                <input type="hidden" name="decision" id="extension_decision">
                
                <div style="margin: 20px 0;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">HOD Remarks (Optional):</label>
                    <textarea name="hod_remarks" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" placeholder="Enter any comments or conditions..."></textarea>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" onclick="closeExtensionDecisionModal()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 5px; cursor: pointer; margin-right: 10px;">Cancel</button>
                    <button type="submit" name="extension_decision" id="extensionSubmitBtn" style="padding: 10px 20px; border: none; border-radius: 5px; color: white; cursor: pointer;">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Research Details Modal -->
    <div id="researchDetailModal" class="modal">
        <div class="modal-content" style="max-width: 1100px; width: 92%; height: 85vh; padding: 0; overflow: hidden;">
            <div class="modal-header" style="padding: 16px 20px; border-bottom: 1px solid #eee;">
                <h2>Research Details</h2>
                <span class="modal-close" onclick="document.getElementById('researchDetailModal').classList.remove('show')">&times;</span>
            </div>
            <iframe id="researchDetailFrame" src="" style="width: 100%; height: calc(85vh - 60px); border: none;"></iframe>
        </div>
    </div>

    <div id="reimbursementDecisionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="reimbursementDecisionTitle">Reimbursement Decision</h2>
                <span class="modal-close" onclick="closeReimbursementDecisionModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="request_id" id="reimbursement_decision_id">
                <input type="hidden" name="decision" id="reimbursement_decision">
                
                <div style="margin: 20px 0;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">HOD Remarks (Required for rejection):</label>
                    <textarea name="hod_remarks" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" placeholder="Enter decision remarks..."></textarea>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" onclick="closeReimbursementDecisionModal()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 5px; cursor: pointer; margin-right: 10px;">Cancel</button>
                    <button type="submit" name="reimbursement_decision" id="reimbursementSubmitBtn" style="padding: 10px 20px; border: none; border-radius: 5px; color: white; cursor: pointer;">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab Switching
        function openTab(evt, tabName) {
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }

            const tabButtons = document.getElementsByClassName('tab-btn');
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove('active');
            }

            document.getElementById(tabName).classList.add('active');
            evt.currentTarget.classList.add('active');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab');
            if (!tab) {
                return;
            }
            const tabButton = document.querySelector(`.tab-btn[data-tab="${tab}"]`);
            if (tabButton && document.getElementById(tab)) {
                openTab({ currentTarget: tabButton }, tab);
            }
        });

        // Flag Research Modal
        function flagResearch(proposalId, title) {
            document.getElementById('flag_proposal_id').value = proposalId;
            document.getElementById('flag_project_title').textContent = title;
            document.getElementById('flagModal').classList.add('show');
        }

        function closeFlagModal() {
            document.getElementById('flagModal').classList.remove('show');
        }

        // Extension Decision
        function approveExtension(extensionId) {
            document.getElementById('extension_decision_id').value = extensionId;
            document.getElementById('extension_decision').value = 'APPROVED';
            document.getElementById('extensionDecisionTitle').textContent = 'Approve Extension Request';
            document.getElementById('extensionSubmitBtn').style.background = '#27ae60';
            document.getElementById('extensionDecisionModal').classList.add('show');
        }

        function rejectExtension(extensionId) {
            document.getElementById('extension_decision_id').value = extensionId;
            document.getElementById('extension_decision').value = 'REJECTED';
            document.getElementById('extensionDecisionTitle').textContent = 'Reject Extension Request';
            document.getElementById('extensionSubmitBtn').style.background = '#e74c3c';
            document.getElementById('extensionDecisionModal').classList.add('show');
        }

        function closeExtensionDecisionModal() {
            document.getElementById('extensionDecisionModal').classList.remove('show');
        }

        // Reimbursement Decision
        function approveReimbursement(requestId) {
            if (!confirm('Are you sure you want to APPROVE this reimbursement and release funds?')) return;
            document.getElementById('reimbursement_decision_id').value = requestId;
            document.getElementById('reimbursement_decision').value = 'APPROVED';
            document.getElementById('reimbursementDecisionTitle').textContent = 'Approve Reimbursement';
            document.getElementById('reimbursementSubmitBtn').style.background = '#27ae60';
            document.getElementById('reimbursementDecisionModal').classList.add('show');
        }

        function rejectReimbursement(requestId) {
            document.getElementById('reimbursement_decision_id').value = requestId;
            document.getElementById('reimbursement_decision').value = 'REJECTED';
            document.getElementById('reimbursementDecisionTitle').textContent = 'Reject Reimbursement';
            document.getElementById('reimbursementSubmitBtn').style.background = '#e74c3c';
            document.getElementById('reimbursementDecisionModal').classList.add('show');
        }

        function closeReimbursementDecisionModal() {
            document.getElementById('reimbursementDecisionModal').classList.remove('show');
        }

        // View Functions
        function viewResearchDetails(proposalId) {
            const modal = document.getElementById('researchDetailModal');
            const frame = document.getElementById('researchDetailFrame');
            if (!modal || !frame) return;
            frame.src = 'view_research_details.php?id=' + proposalId;
            modal.classList.add('show');
        }

        function viewReport(reportId) {
            // Fetch report details and open PDF
            fetch('get_report_path.php?id=' + reportId)
                .then(r => r.json())
                .then(data => {
                    if (data.file_path) {
                        window.open(data.file_path, '_blank');
                    }
                });
        }

        function viewReimbursementDetails(requestId) {
            window.open('view_reimbursement_details.php?id=' + requestId, '_blank');
        }

        function viewCompletedResearch(proposalId) {
            window.open('view_completed_research.php?id=' + proposalId, '_blank');
        }

        function archiveResearch(proposalId) {
            if (!confirm('Are you sure you want to archive this research project?')) return;
            
            const formData = new FormData();
            formData.append('archive_research', '1');
            formData.append('proposal_id', proposalId);
            
            fetch('hod_research_tracking.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.text())
            .then(() => {
                window.location.reload();
            });
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>
