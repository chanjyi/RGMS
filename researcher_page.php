<?php
session_start();
require 'config.php';

// Security Check
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'researcher') {
    header('Location: index.php');
    exit();
}

$message = "";
$messageType = ""; 
$email = $_SESSION['email'];

// Helper: Notify System Users
function notifySystem($conn, $role, $msg) {
    $q = $conn->prepare("SELECT email FROM users WHERE role = ?");
    $q->bind_param("s", $role);
    $q->execute();
    $result = $q->get_result();
    while ($row = $result->fetch_assoc()) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, 'alert')");
        $stmt->bind_param("ss", $row['email'], $msg);
        $stmt->execute();
    }
}

// =========================================================
// USE CASE 7: SUBMIT AND TRACK PROPOSALS
// =========================================================
if (isset($_POST['submit_proposal'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $budget_requested = floatval($_POST['budget_requested']);
    
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_name = basename($_FILES["proposal_file"]["name"]);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $clean_email = str_replace(['@', '.'], '_', $email);
    $new_file_name = "prop_" . time() . "_" . $clean_email . "." . $file_type;
    $target_file = $target_dir . $new_file_name;

    if ($file_type != "pdf") {
        $message = "Error: Only PDF files are allowed."; 
        $messageType = "error";
    } else {
        if (move_uploaded_file($_FILES["proposal_file"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO proposals (title, description, researcher_email, file_path, budget_requested, status) VALUES (?, ?, ?, ?, ?, 'SUBMITTED')");
            $stmt->bind_param("ssssd", $title, $description, $email, $target_file, $budget_requested);
            if ($stmt->execute()) {
                notifySystem($conn, 'admin', "New Proposal Submitted: '$title' by $email");
                $message = "Proposal submitted successfully! You can track its status in the table below."; 
                $messageType = "success";
            } else {
                $message = "Database Error: " . $conn->error; 
                $messageType = "error";
            }
        } else {
            $message = "Error uploading file."; 
            $messageType = "error";
        }
    }
}

// DELETE DRAFT PROPOSAL (Part of Use Case 7)
if (isset($_POST['delete_proposal'])) {
    $prop_id = intval($_POST['proposal_id']);
    
    // Only allow deletion of DRAFT or SUBMITTED proposals (not yet processed)
    $check = $conn->prepare("SELECT status, file_path FROM proposals WHERE id = ? AND researcher_email = ?");
    $check->bind_param("is", $prop_id, $email);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $prop = $result->fetch_assoc();
        if (in_array($prop['status'], ['DRAFT', 'SUBMITTED'])) {
            // Delete file
            if (file_exists($prop['file_path'])) {
                unlink($prop['file_path']);
            }
            // Delete from database
            $delete = $conn->prepare("DELETE FROM proposals WHERE id = ?");
            $delete->bind_param("i", $prop_id);
            if ($delete->execute()) {
                $message = "Proposal deleted successfully."; 
                $messageType = "success";
            }
        } else {
            $message = "Cannot delete proposal that is already being processed."; 
            $messageType = "error";
        }
    }
}

// =========================================================
// USE CASE 11: AMEND PROPOSAL
// =========================================================
if (isset($_POST['amend_proposal'])) {
    $prop_id = intval($_POST['proposal_id']);
    $amendment_notes = mysqli_real_escape_string($conn, $_POST['amendment_notes']);
    
    $target_dir = "uploads/";
    $file_name = basename($_FILES["amend_file"]["name"]);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_file_name = "amend_" . time() . "_" . $prop_id . "." . $file_type;
    $target_file = $target_dir . $new_file_name;

    if ($file_type != "pdf") {
        $message = "Error: PDF only."; 
        $messageType = "error";
    } else {
        if (move_uploaded_file($_FILES["amend_file"]["tmp_name"], $target_file)) {
            // Update proposal with new file and status
            $stmt = $conn->prepare("UPDATE proposals SET file_path = ?, status = 'RESUBMITTED', amendment_notes = ?, resubmitted_at = NOW() WHERE id = ? AND researcher_email = ?");
            $stmt->bind_param("ssis", $target_file, $amendment_notes, $prop_id, $email);
            
            if ($stmt->execute()) {
                // Notify the original reviewer
                notifySystem($conn, 'reviewer', "Proposal #$prop_id has been amended and resubmitted by $email. Please verify corrections.");
                $message = "Amendment submitted successfully! The reviewer will be notified."; 
                $messageType = "success";
            } else {
                $message = "Error submitting amendment."; 
                $messageType = "error";
            }
        }
    }
}

// =========================================================
// USE CASE 10: APPEAL PROPOSAL REJECTION
// =========================================================
if (isset($_POST['appeal_proposal'])) {
    $prop_id = intval($_POST['proposal_id']);
    $justification = mysqli_real_escape_string($conn, $_POST['justification']);
    
    // Verify that proposal is REJECTED and reviewer decision is "REJECT"
    $check = $conn->prepare("SELECT p.status, r.decision FROM proposals p LEFT JOIN reviews r ON p.id = r.proposal_id WHERE p.id = ? AND p.researcher_email = ?");
    $check->bind_param("is", $prop_id, $email);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $prop = $result->fetch_assoc();
        
        if ($prop['status'] == 'REJECTED' && $prop['decision'] == 'REJECT') {
            // Insert appeal with detailed justification
            $stmt = $conn->prepare("INSERT INTO appeal_requests (proposal_id, researcher_email, justification, status, submitted_at) VALUES (?, ?, ?, 'PENDING', NOW())");
            $stmt->bind_param("iss", $prop_id, $email, $justification);
            
            if ($stmt->execute()) {
                // Lock proposal status to 'APPEALED' (Under Appeal)
                $update = $conn->prepare("UPDATE proposals SET status = 'APPEALED' WHERE id = ?");
                $update->bind_param("i", $prop_id);
                $update->execute();

                // Route to HOD for validation
                notifySystem($conn, 'hod', "Appeal Request: $email has contested rejection of Proposal #$prop_id. Please review and potentially reassign to a new reviewer.");
                
                $message = "Appeal submitted successfully with your justification. The Head of Department will review your case."; 
                $messageType = "success";
            } else {
                $message = "Error submitting appeal: " . $conn->error; 
                $messageType = "error";
            }
        } else {
            $message = "This proposal cannot be appealed. Only explicitly rejected proposals are eligible."; 
            $messageType = "error";
        }
    }
}

// =========================================================
// USE CASE 8: SUBMIT PROGRESS REPORT
// =========================================================
if (isset($_POST['submit_report'])) {
    $grant_id = intval($_POST['grant_id']);
    $rep_title = mysqli_real_escape_string($conn, $_POST['report_title']);
    $achievements = mysqli_real_escape_string($conn, $_POST['achievements']);
    $challenges = mysqli_real_escape_string($conn, $_POST['challenges']);
    $deadline = $_POST['report_deadline'];
    
    $target_dir = "uploads/reports/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_name = basename($_FILES["report_file"]["name"]);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_file_name = "rep_" . time() . "_" . $grant_id . "." . $file_type;
    $target_file = $target_dir . $new_file_name;

    if ($file_type != "pdf") {
        $message = "Error: Only PDF files allowed for evidence."; 
        $messageType = "error";
    } else {
        // Check submission date against deadline
        $submission_date = date('Y-m-d');
        
        if (strtotime($submission_date) > strtotime($deadline)) {
            $message = "Warning: Report submitted past deadline. Extension may be required."; 
            $messageType = "error";
        }
        
        if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO progress_reports (proposal_id, researcher_email, title, achievements, challenges, file_path, deadline, status, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING_REVIEW', NOW())");
            $stmt->bind_param("issssss", $grant_id, $email, $rep_title, $achievements, $challenges, $target_file, $deadline);
            
            if ($stmt->execute()) {
                // Forward to HOD for monitoring
                notifySystem($conn, 'hod', "New Progress Report submitted by $email for Grant #$grant_id: '$rep_title'");
                $message = "Progress Report uploaded successfully and forwarded to Head of Department for review."; 
                $messageType = "success";
            } else {
                $message = "Error submitting report."; 
                $messageType = "error";
            }
        }
    }
}

// =========================================================
// USE CASE 12: REQUEST DEADLINE EXTENSION
// =========================================================
if (isset($_POST['request_extension'])) {
    $report_id = intval($_POST['report_id']);
    $new_date = $_POST['new_date'];
    $reason = mysqli_real_escape_string($conn, $_POST['justification']);

    // Validate that new date is in the future
    if (strtotime($new_date) <= strtotime(date('Y-m-d'))) {
        $message = "New deadline must be a future date."; 
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO extension_requests (report_id, researcher_email, new_deadline, justification, status, requested_at) VALUES (?, ?, ?, ?, 'PENDING', NOW())");
        $stmt->bind_param("isss", $report_id, $email, $new_date, $reason);
        
        if ($stmt->execute()) {
            // Forward to HOD for approval
            notifySystem($conn, 'hod', "Deadline Extension Request: $email requests extension for Report #$report_id to $new_date. Reason: $reason");
            $message = "Extension request submitted to Head of Department for approval."; 
            $messageType = "success";
        } else {
            $message = "Error submitting extension request."; 
            $messageType = "error";
        }
    }
}

// =========================================================
// USE CASE 9: VIEW GRANT ALLOCATION & BUDGET USAGE
// =========================================================
// This is handled in the display section below with real-time tracking

// =========================================================
// DATA FETCHING FOR DASHBOARD
// =========================================================

// Fetch all proposals with review details
$sql_props = "SELECT p.*, r.decision as reviewer_decision, r.feedback, r.reviewer_email
              FROM proposals p 
              LEFT JOIN reviews r ON p.id = r.proposal_id 
              WHERE p.researcher_email = ? 
              ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql_props);
$stmt->bind_param("s", $email);
$stmt->execute();
$my_props = $stmt->get_result();

// Fetch approved grants for budget tracking
$sql_grants = "SELECT * FROM proposals WHERE researcher_email = ? AND status = 'APPROVED' ORDER BY approved_at DESC";
$stmt = $conn->prepare($sql_grants);
$stmt->bind_param("s", $email);
$stmt->execute();
$my_grants = $stmt->get_result();

// Fetch progress reports
$sql_reports = "SELECT pr.*, p.title as grant_title, p.id as grant_id
                FROM progress_reports pr 
                JOIN proposals p ON pr.proposal_id = p.id 
                WHERE pr.researcher_email = ?
                ORDER BY pr.submitted_at DESC";
$stmt = $conn->prepare($sql_reports);
$stmt->bind_param("s", $email);
$stmt->execute();
$my_reports = $stmt->get_result();

// Fetch extension requests
$sql_ext = "SELECT er.*, pr.title as report_title 
            FROM extension_requests er 
            JOIN progress_reports pr ON er.report_id = pr.id 
            WHERE er.researcher_email = ?
            ORDER BY er.requested_at DESC";
$stmt = $conn->prepare($sql_ext);
$stmt->bind_param("s", $email);
$stmt->execute();
$my_extensions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Researcher Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .tab-btn { padding: 12px 24px; cursor: pointer; border: none; background: #eee; font-size:15px; margin-right:2px; border-radius:5px 5px 0 0; transition: 0.3s; }
        .tab-btn:hover { background: #ddd; }
        .tab-btn.active { background: #3C5B6F; color: white; }
        .tab-content { display: none; padding: 25px; border: 1px solid #ddd; margin-top: -1px; border-radius: 0 5px 5px 5px; background: white; }
        .tab-content.active { display: block; }
        
        .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); overflow-y: auto; }
        .modal-content { background: white; margin: 5% auto; padding: 30px; width: 60%; max-width: 700px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        .close { float: right; font-size: 32px; cursor: pointer; color: #999; line-height: 20px; }
        .close:hover { color: #333; }
        
        .budget-card { 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
            padding: 20px; 
            margin-bottom: 15px; 
            border-left: 5px solid #28a745; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
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
        .budget-warning { background: linear-gradient(90deg, #ffc107, #ff9800); }
        .budget-danger { background: linear-gradient(90deg, #dc3545, #c82333); }
        
        textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; resize: vertical; font-family: inherit; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .input-group input, .input-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        .status-badge { 
            padding: 5px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: bold; 
            text-transform: uppercase; 
        }
        .status-badge.submitted { background: #cce5ff; color: #004085; }
        .status-badge.under_review { background: #fff3cd; color: #856404; }
        .status-badge.approved { background: #d4edda; color: #155724; }
        .status-badge.rejected { background: #f8d7da; color: #721c24; }
        .status-badge.requires_amendment { background: #ffeeba; color: #856404; }
        .status-badge.resubmitted { background: #d1ecf1; color: #0c5460; }
        .status-badge.appealed { background: #e2e3e5; color: #383d41; }
        
        .form-box { background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 25px; }
        .btn-action { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; transition: 0.3s; }
        .btn-edit { background: #17a2b8; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-appeal { background: #e74c3c; color: white; }
        .btn-action:hover { opacity: 0.8; transform: translateY(-1px); }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <section class="home-section">
        <div class="welcome-text">
            <i class='bx bx-user-circle' style="font-size:24px; vertical-align:middle;"></i>
            Researcher Dashboard | <?= htmlspecialchars($_SESSION['name']); ?>
        </div>
        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 25px;">

        <?php if ($message): ?>
            <div class="alert" style="padding:15px; margin-bottom:20px; border-radius:8px; 
                background: <?= $messageType=='success'?'#d4edda':'#f8d7da' ?>; 
                color: <?= $messageType=='success'?'#155724':'#721c24' ?>; 
                border-left: 4px solid <?= $messageType=='success'?'#28a745':'#dc3545' ?>;">
                <i class='bx <?= $messageType=='success'?"bx-check-circle":"bx-error-circle" ?>' style="font-size:18px; vertical-align:middle;"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div style="margin-bottom: 0;">
            <button class="tab-btn active" onclick="openTab(event, 'proposals')">
                <i class='bx bx-file'></i> My Proposals
            </button>
            <button class="tab-btn" onclick="openTab(event, 'grants')">
                <i class='bx bx-dollar-circle'></i> Active Grants & Budget
            </button>
            <button class="tab-btn" onclick="openTab(event, 'reports')">
                <i class='bx bx-chart'></i> Progress Reports
            </button>
        </div>

        <!-- USE CASE 7: SUBMIT AND TRACK PROPOSALS -->
        <div id="proposals" class="tab-content active">
            <div class="form-box">
                <h3 style="margin-top:0; color:#3C5B6F;">
                    <i class='bx bx-plus-circle'></i> Submit New Proposal
                </h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <label>Proposal Title *</label>
                        <input type="text" name="title" required placeholder="Enter a descriptive title">
                    </div>
                    <div class="input-group">
                        <label>Description *</label>
                        <textarea name="description" rows="4" required placeholder="Provide a brief overview of your research proposal"></textarea>
                    </div>
                    <div class="input-group">
                        <label>Budget Requested ($) *</label>
                        <input type="number" name="budget_requested" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    <div class="input-group">
                        <label>Proposal Document (PDF) *</label>
                        <input type="file" name="proposal_file" accept=".pdf" required>
                    </div>
                    <button type="submit" name="submit_proposal" class="btn-save">
                        <i class='bx bx-upload'></i> Submit Proposal
                    </button>
                </form>
            </div>

            <h3 style="color:#3C5B6F; margin-top:30px;">
                <i class='bx bx-list-ul'></i> Track My Proposals
            </h3>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Reviewer Feedback</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $my_props->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td style="max-width:250px;"><?= htmlspecialchars($row['title']) ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($row['status']) ?>">
                                    <?= str_replace('_', ' ', $row['status']) ?>
                                </span>
                            </td>
                            <td style="font-size:12px; color:#555; max-width:200px;">
                                <?php if(!empty($row['feedback'])): ?>
                                    <?= htmlspecialchars(substr($row['feedback'], 0, 100)) ?>
                                    <?= strlen($row['feedback']) > 100 ? '...' : '' ?>
                                <?php else: ?>
                                    <em style="color:#999;">No feedback yet</em>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12px;"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <?php if($row['status'] == 'REQUIRES_AMENDMENT'): ?>
                                    <button onclick="openAmendModal(<?= $row['id'] ?>)" class="btn-action btn-edit">
                                        <i class='bx bx-edit'></i> Amend
                                    </button>
                                
                                <?php elseif($row['status'] == 'REJECTED' && $row['reviewer_decision'] == 'REJECT'): ?>
                                    <button onclick="openAppealModal(<?= $row['id'] ?>)" class="btn-action btn-appeal">
                                        <i class='bx bx-message-square-error'></i> Appeal
                                    </button>
                                
                                <?php elseif($row['status'] == 'APPEALED'): ?>
                                    <span style="color:#6c757d; font-size:12px;">
                                        <i class='bx bx-time'></i> Under Appeal
                                    </span>
                                
                                <?php elseif(in_array($row['status'], ['DRAFT', 'SUBMITTED'])): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this proposal?');">
                                        <input type="hidden" name="proposal_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="delete_proposal" class="btn-action btn-delete">
                                            <i class='bx bx-trash'></i> Delete
                                        </button>
                                    </form>
                                
                                <?php else: ?>
                                    <span style="color:#999;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- USE CASE 9: VIEW GRANT ALLOCATION & BUDGET USAGE -->
        <div id="grants" class="tab-content">
            <h3 style="color:#3C5B6F; margin-top:0;">
                <i class='bx bx-wallet'></i> Active Grants - Financial Overview
            </h3>
            <p style="color:#666; margin-bottom:20px;">Real-time tracking of allocated funds and expenditure for all approved projects.</p>
            
            <?php if ($my_grants->num_rows > 0): ?>
                <?php while($grant = $my_grants->fetch_assoc()): 
                    $budget = $grant['approved_budget'] ?? 0;
                    $spent = $grant['amount_spent'] ?? 0;
                    $remaining = $budget - $spent;
                    $percentage = $budget > 0 ? ($spent / $budget) * 100 : 0;
                    
                    $bar_class = '';
                    if ($percentage >= 90) $bar_class = 'budget-danger';
                    elseif ($percentage >= 70) $bar_class = 'budget-warning';
                ?>
                    <div class="budget-card">
                        <h4 style="margin-top:0; color:#2c3e50;">
                            <i class='bx bx-file-blank'></i> <?= htmlspecialchars($grant['title']) ?>
                        </h4>
                        <p style="color:#666; font-size:13px; margin:5px 0;">Grant ID: #<?= $grant['id'] ?> | Approved: <?= date('M d, Y', strtotime($grant['approved_at'])) ?></p>
                        
                        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; margin:15px 0;">
                            <div>
                                <strong style="color:#28a745;">Total Allocated:</strong><br>
                                <span style="font-size:20px; font-weight:bold;">$<?= number_format($budget, 2) ?></span>
                            </div>
                            <div>
                                <strong style="color:#dc3545;">Expenditure:</strong><br>
                                <span style="font-size:20px; font-weight:bold;">$<?= number_format($spent, 2) ?></span>
                            </div>
                            <div>
                                <strong style="color:#17a2b8;">Remaining Balance:</strong><br>
                                <span style="font-size:20px; font-weight:bold;">$<?= number_format($remaining, 2) ?></span>
                            </div>
                        </div>
                        
                        <div class="budget-bar">
                            <div class="budget-fill <?= $bar_class ?>" style="width: <?= min($percentage, 100) ?>%;">
                                <?= round($percentage, 1) ?>% Used
                            </div>
                        </div>
                        
                        <button onclick="openReportModal(<?= $grant['id'] ?>)" class="btn-save" style="margin-top:15px;">
                            <i class='bx bx-plus'></i> Submit Progress Report
                        </button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px; color:#999;">
                    <i class='bx bx-info-circle' style="font-size:48px;"></i>
                    <p>No active grants at the moment. Submit a proposal to get started!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- USE CASE 8 & 12: PROGRESS REPORTS AND EXTENSIONS -->
        <div id="reports" class="tab-content">
            <h3 style="color:#3C5B6F; margin-top:0;">
                <i class='bx bx-bar-chart-alt-2'></i> My Progress Reports
            </h3>
            
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Grant</th>
                        <th>Report Title</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $my_reports->data_seek(0); // Reset pointer
                    if($my_reports->num_rows > 0):
                        while($rep = $my_reports->fetch_assoc()): 
                            $is_overdue = strtotime($rep['deadline']) < strtotime(date('Y-m-d')) && $rep['status'] == 'PENDING_REVIEW';
                    ?>
                        <tr <?= $is_overdue ? 'style="background:#fff3cd;"' : '' ?>>
                            <td>#<?= $rep['id'] ?></td>
                            <td><?= htmlspecialchars($rep['grant_title']) ?></td>
                            <td><?= htmlspecialchars($rep['title']) ?></td>
                            <td style="font-size:12px;">
                                <?= date('M d, Y', strtotime($rep['deadline'])) ?>
                                <?php if($is_overdue): ?>
                                    <br><span style="color:#dc3545; font-weight:bold;">OVERDUE</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?= strtolower($rep['status']) ?>">
                                    <?= str_replace('_', ' ', $rep['status']) ?>
                                </span>
                            </td>
                            <td style="font-size:12px;"><?= date('M d, Y', strtotime($rep['submitted_at'])) ?></td>
                            <td>
                                <button onclick="openExtModal(<?= $rep['id'] ?>)" class="btn-action" style="background:#f39c12; color:white;">
                                    <i class='bx bx-time'></i> Request Extension
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="7" style="text-align:center; color:#999;">No reports submitted yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3 style="color:#3C5B6F; margin-top:40px;">
                <i class='bx bx-calendar-event'></i> Extension Requests
            </h3>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Report</th>
                        <th>Current Deadline</th>
                        <th>Requested Deadline</th>
                        <th>Justification</th>
                        <th>Status</th>
                        <th>Requested</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($my_extensions->num_rows > 0): 
                        while($ext = $my_extensions->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($ext['report_title']) ?></td>
                            <td style="font-size:12px;">
                                <?php
                                $report_check = $conn->prepare("SELECT deadline FROM progress_reports WHERE id = ?");
                                $report_check->bind_param("i", $ext['report_id']);
                                $report_check->execute();
                                $report_data = $report_check->get_result()->fetch_assoc();
                                echo date('M d, Y', strtotime($report_data['deadline']));
                                ?>
                            </td>
                            <td style="font-size:12px; font-weight:bold; color:#28a745;">
                                <?= date('M d, Y', strtotime($ext['new_deadline'])) ?>
                            </td>
                            <td style="font-size:12px; max-width:200px;"><?= htmlspecialchars(substr($ext['justification'], 0, 80)) ?>...</td>
                            <td>
                                <?php if($ext['status'] == 'APPROVED'): ?>
                                    <span style="color:#28a745; font-weight:bold;"><i class='bx bx-check-circle'></i> Approved</span>
                                <?php elseif($ext['status'] == 'REJECTED'): ?>
                                    <span style="color:#dc3545; font-weight:bold;"><i class='bx bx-x-circle'></i> Rejected</span>
                                <?php else: ?>
                                    <span style="color:#ffc107; font-weight:bold;"><i class='bx bx-time'></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12px;"><?= date('M d, Y', strtotime($ext['requested_at'])) ?></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" style="text-align:center; color:#999;">No extension requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- MODAL: APPEAL PROPOSAL (USE CASE 10) -->
    <div id="appealModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('appealModal')">&times;</span>
            <h3 style="color:#e74c3c; margin-top:0;">
                <i class='bx bx-error-alt'></i> Appeal Proposal Rejection
            </h3>
            <p style="font-size:14px; color:#666; line-height:1.6;">
                Use this form to formally contest the rejection of your proposal. Provide a detailed rebuttal 
                explaining why the review decision was factually incorrect or unfair. Your appeal will be 
                reviewed by the Head of Department.
            </p>
            <form method="POST">
                <input type="hidden" name="proposal_id" id="appeal_prop_id">
                <div class="input-group">
                    <label>Detailed Justification / Rebuttal *</label>
                    <textarea name="justification" rows="6" required placeholder="Explain in detail why this proposal should be reconsidered. Include specific factual corrections or evidence that the review was incorrect..."></textarea>
                </div>
                <button type="submit" name="appeal_proposal" class="btn-save" style="background:#e74c3c;">
                    <i class='bx bx-send'></i> Submit Appeal
                </button>
                <button type="button" onclick="closeModal('appealModal')" style="background:#6c757d; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; margin-left:10px;">
                    Cancel
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL: AMEND PROPOSAL (USE CASE 11) -->
    <div id="amendModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('amendModal')">&times;</span>
            <h3 style="color:#17a2b8; margin-top:0;">
                <i class='bx bx-edit-alt'></i> Submit Amendment
            </h3>
            <p style="font-size:14px; color:#666; line-height:1.6;">
                Upload your corrected proposal document addressing the reviewer's feedback. 
                The original reviewer will be notified to verify your corrections.
            </p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="proposal_id" id="amend_prop_id">
                <div class="input-group">
                    <label>Amendment Notes</label>
                    <textarea name="amendment_notes" rows="3" placeholder="Briefly describe the changes you made..."></textarea>
                </div>
                <div class="input-group">
                    <label>Upload Revised PDF Document *</label>
                    <input type="file" name="amend_file" accept=".pdf" required>
                </div>
                <button type="submit" name="amend_proposal" class="btn-save">
                    <i class='bx bx-upload'></i> Resubmit Proposal
                </button>
                <button type="button" onclick="closeModal('amendModal')" style="background:#6c757d; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; margin-left:10px;">
                    Cancel
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL: SUBMIT PROGRESS REPORT (USE CASE 8) -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('reportModal')">&times;</span>
            <h3 style="color:#3C5B6F; margin-top:0;">
                <i class='bx bx-file-plus'></i> Submit Progress Report
            </h3>
            <p style="font-size:14px; color:#666; line-height:1.6;">
                Report on your project's milestones, achievements, and challenges. Include evidence of completed work.
            </p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="grant_id" id="report_grant_id">
                <div class="input-group">
                    <label>Report Title *</label>
                    <input type="text" name="report_title" required placeholder="e.g., Q1 Progress Report">
                </div>
                <div class="input-group">
                    <label>Achievements / Milestones Completed *</label>
                    <textarea name="achievements" rows="4" required placeholder="Detail what has been accomplished..."></textarea>
                </div>
                <div class="input-group">
                    <label>Challenges Faced</label>
                    <textarea name="challenges" rows="3" placeholder="Describe any obstacles or issues encountered..."></textarea>
                </div>
                <div class="input-group">
                    <label>Report Deadline *</label>
                    <input type="date" name="report_deadline" required>
                </div>
                <div class="input-group">
                    <label>Evidence of Work (PDF) *</label>
                    <input type="file" name="report_file" accept=".pdf" required>
                    <small style="color:#666;">Upload documentation, data, or other evidence supporting your report</small>
                </div>
                <button type="submit" name="submit_report" class="btn-save">
                    <i class='bx bx-upload'></i> Submit Report
                </button>
                <button type="button" onclick="closeModal('reportModal')" style="background:#6c757d; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; margin-left:10px;">
                    Cancel
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL: REQUEST DEADLINE EXTENSION (USE CASE 12) -->
    <div id="extModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('extModal')">&times;</span>
            <h3 style="color:#f39c12; margin-top:0;">
                <i class='bx bx-time-five'></i> Request Deadline Extension
            </h3>
            <p style="font-size:14px; color:#666; line-height:1.6;">
                Request additional time to submit your progress report. Provide a valid justification for the delay.
            </p>
            <form method="POST">
                <input type="hidden" name="report_id" id="ext_report_id">
                <div class="input-group">
                    <label>New Target Deadline *</label>
                    <input type="date" name="new_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    <small style="color:#666;">Must be a future date</small>
                </div>
                <div class="input-group">
                    <label>Justification for Extension *</label>
                    <textarea name="justification" rows="4" required placeholder="e.g., Fieldwork delayed due to weather conditions, Equipment malfunction, etc."></textarea>
                </div>
                <button type="submit" name="request_extension" class="btn-save" style="background:#f39c12;">
                    <i class='bx bx-send'></i> Submit Extension Request
                </button>
                <button type="button" onclick="closeModal('extModal')" style="background:#6c757d; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; margin-left:10px;">
                    Cancel
                </button>
            </form>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function openTab(evt, tabName) {
            var tabcontent = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            var tablinks = document.getElementsByClassName("tab-btn");
            for (var i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        // Modal functions
        function openAmendModal(propId) {
            document.getElementById('amendModal').style.display = "block";
            document.getElementById('amend_prop_id').value = propId;
        }

        function openAppealModal(propId) {
            document.getElementById('appealModal').style.display = "block";
            document.getElementById('appeal_prop_id').value = propId;
        }

        function openReportModal(grantId) {
            document.getElementById('reportModal').style.display = "block";
            document.getElementById('report_grant_id').value = grantId;
        }

        function openExtModal(reportId) {
            document.getElementById('extModal').style.display = "block";
            document.getElementById('ext_report_id').value = reportId;
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modals = document.getElementsByClassName('modal');
            for(var i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>