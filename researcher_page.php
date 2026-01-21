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

// Helper: Notify System
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
// USE CASE 7: SUBMIT PROPOSAL WITH BUDGET BREAKDOWN & MILESTONES
// =========================================================
if (isset($_POST['submit_proposal'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $duration_months = intval($_POST['duration_months']);
    $budget_requested = floatval($_POST['budget_requested']);
    
    // Budget breakdown by category
    $budget_equipment = floatval($_POST['budget_equipment'] ?? 0);
    $budget_materials = floatval($_POST['budget_materials'] ?? 0);
    $budget_travel = floatval($_POST['budget_travel'] ?? 0);
    $budget_personnel = floatval($_POST['budget_personnel'] ?? 0);
    $budget_other = floatval($_POST['budget_other'] ?? 0);
    
    // Milestones
    $milestone_titles = $_POST['milestone_title'] ?? [];
    $milestone_descs = $_POST['milestone_desc'] ?? [];
    $milestone_dates = $_POST['milestone_date'] ?? [];
    
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_name = basename($_FILES["proposal_file"]["name"]);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $clean_email = str_replace(['@', '.'], '_', $email);
    $new_file_name = "prop_" . time() . "_" . $clean_email . "." . $file_type;
    $target_file = $target_dir . $new_file_name;

    if ($file_type != "pdf") {
        $message = "Error: Only PDF allowed."; 
        $messageType = "error";
    } else {
        if (move_uploaded_file($_FILES["proposal_file"]["tmp_name"], $target_file)) {
            // Insert proposal
            $stmt = $conn->prepare("INSERT INTO proposals (title, description, researcher_email, file_path, budget_requested, duration_months, status) VALUES (?, ?, ?, ?, ?, ?, 'SUBMITTED')");
            $stmt->bind_param("ssssdi", $title, $description, $email, $target_file, $budget_requested, $duration_months);
            
            if ($stmt->execute()) {
                $proposal_id = $conn->insert_id;
                
                // Insert budget breakdown into budget_items table
                $categories = [
                    'Equipment' => $budget_equipment,
                    'Materials' => $budget_materials,
                    'Travel' => $budget_travel,
                    'Personnel' => $budget_personnel,
                    'Other' => $budget_other
                ];
                
                foreach ($categories as $category => $amount) {
                    if ($amount > 0) {
                        $budget_stmt = $conn->prepare("INSERT INTO budget_items (proposal_id, category, allocated_amount, description) VALUES (?, ?, ?, ?)");
                        $desc = "$category budget";
                        $budget_stmt->bind_param("isds", $proposal_id, $category, $amount, $desc);
                        $budget_stmt->execute();
                    }
                }
                
                // Insert milestones
                for ($i = 0; $i < count($milestone_titles); $i++) {
                    if (!empty($milestone_titles[$i]) && !empty($milestone_dates[$i])) {
                        $m_stmt = $conn->prepare("INSERT INTO milestones (grant_id, title, description, target_date, status) VALUES (?, ?, ?, ?, 'PENDING')");
                        $m_stmt->bind_param("isss", $proposal_id, $milestone_titles[$i], $milestone_descs[$i], $milestone_dates[$i]);
                        $m_stmt->execute();
                    }
                }
                
                // Create initial document version
                $version_stmt = $conn->prepare("INSERT INTO document_versions (proposal_id, version_number, file_path, uploaded_by) VALUES (?, 'v1.0', ?, ?)");
                $version_stmt->bind_param("iss", $proposal_id, $target_file, $email);
                $version_stmt->execute();
                
                notifySystem($conn, 'admin', "New Proposal Submitted: '$title' by $email");
                $message = "Proposal submitted successfully with budget breakdown and milestones!"; 
                $messageType = "success";
            } else {
                $message = "DB Error: " . $conn->error; 
                $messageType = "error";
            }
        }
    }
}

// =========================================================
// DELETE DRAFT PROPOSAL (USE CASE 7)
// =========================================================
if (isset($_POST['delete_proposal'])) {
    $prop_id = intval($_POST['proposal_id']);
    
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
            // Delete from database (CASCADE will handle related records)
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
            // Get current version number and increment
            $version_query = $conn->prepare("SELECT MAX(CAST(SUBSTRING(version_number, 2) AS DECIMAL(10,2))) as max_ver FROM document_versions WHERE proposal_id = ?");
            $version_query->bind_param("i", $prop_id);
            $version_query->execute();
            $ver_result = $version_query->get_result()->fetch_assoc();
            $new_version = "v" . number_format($ver_result['max_ver'] + 0.1, 1);
            
            // Insert new version
            $version_stmt = $conn->prepare("INSERT INTO document_versions (proposal_id, version_number, file_path, uploaded_by, change_notes) VALUES (?, ?, ?, ?, ?)");
            $version_stmt->bind_param("issss", $prop_id, $new_version, $target_file, $email, $amendment_notes);
            $version_stmt->execute();
            
            // Update proposal
            $stmt = $conn->prepare("UPDATE proposals SET file_path = ?, status = 'RESUBMITTED', amendment_notes = ?, resubmitted_at = NOW() WHERE id = ? AND researcher_email = ?");
            $stmt->bind_param("ssis", $target_file, $amendment_notes, $prop_id, $email);
            
            if ($stmt->execute()) {
                notifySystem($conn, 'reviewer', "Proposal #$prop_id has been amended and resubmitted by $email (Version $new_version). Please verify corrections.");
                $message = "Amendment submitted successfully! Version: $new_version"; 
                $messageType = "success";
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
    $milestone_ids = $_POST['milestones'] ?? []; // Array of completed milestone IDs
    
    $target_dir = "uploads/reports/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_name = basename($_FILES["report_file"]["name"]);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_file_name = "rep_" . time() . "_" . $grant_id . ".pdf";
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
                // Mark selected milestones as completed
                foreach ($milestone_ids as $milestone_id) {
                    $mile_update = $conn->prepare("UPDATE milestones SET status = 'COMPLETED', completion_date = CURDATE() WHERE id = ? AND grant_id = ?");
                    $mile_update->bind_param("ii", $milestone_id, $grant_id);
                    $mile_update->execute();
                }
                
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
// LOG EXPENDITURE (OUT-OF-POCKET SPENDING)
// =========================================================
if (isset($_POST['log_expenditure'])) {
    $grant_id = intval($_POST['grant_id']);
    $budget_item_id = intval($_POST['budget_item_id']);
    $amount = floatval($_POST['expenditure_amount']);
    $trans_date = $_POST['transaction_date'];
    $exp_description = mysqli_real_escape_string($conn, $_POST['expenditure_description']);
    
    // Handle receipt upload
    $receipt_path = null;
    if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['size'] > 0) {
        $receipt_dir = "uploads/receipts/";
        if (!is_dir($receipt_dir)) mkdir($receipt_dir, 0777, true);
        
        $receipt_name = "receipt_" . time() . "_" . basename($_FILES["receipt_file"]["name"]);
        $receipt_path = $receipt_dir . $receipt_name;
        move_uploaded_file($_FILES["receipt_file"]["tmp_name"], $receipt_path);
    }
    
    // Insert expenditure (status = PENDING_REIMBURSEMENT, not updating budget yet)
    $stmt = $conn->prepare("INSERT INTO expenditures (budget_item_id, amount, transaction_date, description, receipt_path, status) VALUES (?, ?, ?, ?, ?, 'PENDING_REIMBURSEMENT')");
    $stmt->bind_param("idsss", $budget_item_id, $amount, $trans_date, $exp_description, $receipt_path);
    
    if ($stmt->execute()) {
        $message = "Expenditure logged successfully! You can now request reimbursement."; 
        $messageType = "success";
    } else {
        $message = "Error logging expenditure."; 
        $messageType = "error";
    }
}

// =========================================================
// REQUEST FUND REIMBURSEMENT
// =========================================================
if (isset($_POST['request_reimbursement'])) {
    $grant_id = intval($_POST['grant_id']);
    $expenditure_ids = $_POST['expenditure_ids'] ?? [];
    $justification = mysqli_real_escape_string($conn, $_POST['reimbursement_justification']);
    
    if (empty($expenditure_ids)) {
        $message = "Please select at least one expenditure to claim.";
        $messageType = "error";
    } else {
        // Calculate total claim amount
        $total_claim = 0;
        $exp_ids_str = implode(',', array_map('intval', $expenditure_ids));
        
        $calc_query = $conn->query("SELECT SUM(amount) as total FROM expenditures WHERE id IN ($exp_ids_str) AND status='PENDING_REIMBURSEMENT'");
        $total_claim = $calc_query->fetch_assoc()['total'] ?? 0;
        
        // Create reimbursement request
        $stmt = $conn->prepare("INSERT INTO reimbursement_requests (grant_id, researcher_email, total_amount, justification, status, requested_at) VALUES (?, ?, ?, ?, 'PENDING', NOW())");
        $stmt->bind_param("isds", $grant_id, $email, $total_claim, $justification);
        
        if ($stmt->execute()) {
            $request_id = $conn->insert_id;
            
            // Link expenditures to this request
            foreach ($expenditure_ids as $exp_id) {
                $link_stmt = $conn->prepare("UPDATE expenditures SET reimbursement_request_id = ?, status = 'UNDER_REVIEW' WHERE id = ?");
                $link_stmt->bind_param("ii", $request_id, $exp_id);
                $link_stmt->execute();
            }
            
            notifySystem($conn, 'hod', "Reimbursement Request: $email requests $" . number_format($total_claim, 2) . " for Grant #$grant_id");
            $message = "Reimbursement request submitted successfully! Awaiting HOD approval.";
            $messageType = "success";
        }
    }
}

// =========================================================
// DATA FETCHING FOR DASHBOARD
// =========================================================

// Fetch all proposals with review details
$sql_props = "SELECT p.*, r.decision as reviewer_decision, r.feedback 
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
$sql_reports = "SELECT pr.*, p.title as grant_title 
                FROM progress_reports pr 
                JOIN proposals p ON pr.proposal_id = p.id 
                WHERE pr.researcher_email = ?
                ORDER BY pr.submitted_at DESC";
$stmt = $conn->prepare($sql_reports);
$stmt->bind_param("s", $email);
$stmt->execute();
$my_reports = $stmt->get_result();

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
        .modal-content { background: white; margin: 3% auto; padding: 30px; width: 70%; max-width: 900px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        .close { float: right; font-size: 32px; cursor: pointer; color: #999; line-height: 20px; }
        .close:hover { color: #333; }
        
        .budget-card { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px; margin-bottom: 15px; border-left: 5px solid #28a745; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .budget-bar { width: 100%; height: 25px; background: #e9ecef; border-radius: 15px; overflow: hidden; margin: 10px 0; position: relative; }
        .budget-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px; }
        .budget-warning { background: linear-gradient(90deg, #ffc107, #ff9800); }
        .budget-danger { background: linear-gradient(90deg, #dc3545, #c82333); }
        
        .budget-breakdown { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0; }
        .budget-item { background: white; padding: 15px; border-radius: 8px; border-left: 3px solid #3C5B6F; }
        
        textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; resize: vertical; font-family: inherit; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .input-group input, .input-group textarea, .input-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        
        .milestone-list { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .milestone-item { padding: 10px; background: white; margin: 5px 0; border-radius: 5px; border-left: 3px solid #17a2b8; }
        .milestone-item.completed { border-left-color: #28a745; opacity: 0.7; }
        
        .milestone-input { background: #f8f9fa; padding: 15px; margin-bottom: 10px; border-radius: 5px; border-left: 3px solid #17a2b8; position: relative; }
        .milestone-input input, .milestone-input textarea { width: 100%; margin-bottom: 8px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .milestone-input .remove-btn { position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        
        .expenditure-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .expenditure-table th, .expenditure-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .expenditure-table th { background: #f8f9fa; font-weight: 600; }
        
        .expenditure-card { background: white; padding: 15px; margin: 10px 0; border-left: 3px solid #ffc107; border-radius: 5px; }
        .reimburse-checkbox { margin-right: 10px; transform: scale(1.2); }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .status-badge.submitted { background: #cce5ff; color: #004085; }
        .status-badge.approved { background: #d4edda; color: #155724; }
        .status-badge.rejected { background: #f8d7da; color: #721c24; }
        .status-badge.requires_amendment { background: #ffeeba; color: #856404; }
        .status-badge.resubmitted { background: #d1ecf1; color: #0c5460; }
        .status-badge.appealed { background: #e2e3e5; color: #383d41; }
        .status-badge.pending_review { background: #fff3cd; color: #856404; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.pending_reimbursement { background: #ffeeba; color: #856404; }
        .status-badge.under_review { background: #d1ecf1; color: #0c5460; }
        
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
            <button class="tab-btn" onclick="openTab(event, 'expenditures')">
                <i class='bx bx-receipt'></i> Expenditures & Claims
            </button>
            <button class="tab-btn" onclick="openTab(event, 'reports')">
                <i class='bx bx-chart'></i> Progress Reports
            </button>
        </div>

        <!-- ========== TAB 1: PROPOSALS ========== -->
        <div id="proposals" class="tab-content active">
            <div class="form-box" style="margin-bottom: 30px; background:#f8f9fa; padding:25px; border-radius:8px;">
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
                    <div class="grid-2">
                        <div class="input-group">
                            <label>Project Duration (Months) *</label>
                            <input type="number" name="duration_months" min="1" max="60" required placeholder="12">
                        </div>
                        <div class="input-group">
                            <label>Total Budget Requested ($) *</label>
                            <input type="number" name="budget_requested" step="0.01" min="0" required placeholder="0.00" id="totalBudget" readonly>
                        </div>
                    </div>
                    
                    <h4 style="color:#3C5B6F; margin-top:20px;">Budget Breakdown by Category</h4>
                    <div class="grid-3">
                        <div class="input-group">
                            <label>Equipment ($)</label>
                            <input type="number" name="budget_equipment" step="0.01" min="0" value="0" class="budget-category" onchange="calculateTotal()">
                        </div>
                        <div class="input-group">
                            <label>Materials ($)</label>
                            <input type="number" name="budget_materials" step="0.01" min="0" value="0" class="budget-category" onchange="calculateTotal()">
                        </div>
                        <div class="input-group">
                            <label>Travel ($)</label>
                            <input type="number" name="budget_travel" step="0.01" min="0" value="0" class="budget-category" onchange="calculateTotal()">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="input-group">
                            <label>Personnel ($)</label>
                            <input type="number" name="budget_personnel" step="0.01" min="0" value="0" class="budget-category" onchange="calculateTotal()">
                        </div>
                        <div class="input-group">
                            <label>Other Expenses ($)</label>
                            <input type="number" name="budget_other" step="0.01" min="0" value="0" class="budget-category" onchange="calculateTotal()">
                        </div>
                    </div>
                    
                    <h4 style="color:#3C5B6F; margin-top:20px;">Project Milestones</h4>
                    <p style="color:#666; font-size:13px; margin-bottom:10px;">Define key deliverables and timeline checkpoints for your project</p>
                    <div id="milestones_container">
                        <div class="milestone-input">
                            <input type="text" name="milestone_title[]" placeholder="Milestone Title (e.g., Literature Review Complete)" required>
                            <textarea name="milestone_desc[]" rows="2" placeholder="Brief description of this milestone"></textarea>
                            <input type="date" name="milestone_date[]" required>
                        </div>
                    </div>
                    <button type="button" onclick="addMilestone()" class="btn-save" style="background:#17a2b8; margin-bottom:15px;">
                        <i class='bx bx-plus'></i> Add Another Milestone
                    </button>
                    
                    <div class="input-group" style="margin-top:20px;">
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
                        <th>Budget Requested</th>
                        <th>Reviewer Feedback</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $my_props->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td style="max-width:250px;">
                                <strong><?= htmlspecialchars($row['title']) ?></strong>
                                <?php if($row['duration_months']): ?>
                                    <br><small style="color:#666;"><?= $row['duration_months'] ?> months</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?= strtolower($row['status']) ?>">
                                    <?= str_replace('_', ' ', $row['status']) ?>
                                </span>
                            </td>
                            <td>$<?= number_format($row['budget_requested'], 2) ?></td>
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
                                    <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['title']) ?>')" class="btn-action btn-delete">
                                        <i class='bx bx-trash'></i> Delete
                                    </button>
                                
                                <?php else: ?>
                                    <span style="color:#999;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- ========== TAB 2: GRANTS & BUDGET ========== -->
        <div id="grants" class="tab-content">
            <h3 style="color:#3C5B6F; margin-top:0;">
                <i class='bx bx-wallet'></i> Active Grants - Financial Overview
            </h3>
            <p style="color:#666; margin-bottom:20px;">Track your allocated budget and approved reimbursements</p>
            
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
                        <p style="color:#666; font-size:13px; margin:5px 0;">
                            Grant ID: #<?= $grant['id'] ?> | Approved: <?= date('M d, Y', strtotime($grant['approved_at'])) ?>
                        </p>
                        
                        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; margin:15px 0;">
                            <div>
                                <strong style="color:#28a745;">Total Allocated:</strong><br>
                                <span style="font-size:20px; font-weight:bold;">$<?= number_format($budget, 2) ?></span>
                            </div>
                            <div>
                                <strong style="color:#dc3545;">Reimbursed:</strong><br>
                                <span style="font-size:20px; font-weight:bold;">$<?= number_format($spent, 2) ?></span>
                            </div>
                            <div>
                                <strong style="color:#17a2b8;">Remaining:</strong><br>
                                <span style="font-size:20px; font-weight:bold;">$<?= number_format($remaining, 2) ?></span>
                            </div>
                        </div>
                        
                        <div class="budget-bar">
                            <div class="budget-fill <?= $bar_class ?>" style="width: <?= min($percentage, 100) ?>%;">
                                <?= round($percentage, 1) ?>% Claimed
                            </div>
                        </div>
                        
                        <!-- BUDGET BREAKDOWN BY CATEGORY -->
                        <h5 style="margin-top:20px; color:#3C5B6F;">Budget Breakdown</h5>
                        <div class="budget-breakdown">
                            <?php
                            $budget_query = $conn->prepare("SELECT * FROM budget_items WHERE proposal_id = ?");
                            $budget_query->bind_param("i", $grant['id']);
                            $budget_query->execute();
                            $budget_items = $budget_query->get_result();
                            
                            while($item = $budget_items->fetch_assoc()):
                                $item_percentage = $item['allocated_amount'] > 0 ? ($item['spent_amount'] / $item['allocated_amount']) * 100 : 0;
                            ?>
                                <div class="budget-item">
                                    <strong><?= $item['category'] ?></strong><br>
                                    <small style="color:#666;">
                                        Claimed: $<?= number_format($item['spent_amount'], 2) ?><br>
                                        Allocated: $<?= number_format($item['allocated_amount'], 2) ?>
                                    </small><br>
                                    <div style="background:#e9ecef; height:8px; border-radius:5px; margin-top:5px; overflow:hidden;">
                                        <div style="background:#3C5B6F; height:100%; width:<?= min($item_percentage, 100) ?>%;"></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- PROJECT MILESTONES -->
                        <h5 style="margin-top:20px; color:#3C5B6F;">Project Milestones</h5>
                        <?php
                        $m_query = $conn->prepare("SELECT * FROM milestones WHERE grant_id = ? ORDER BY target_date");
                        $m_query->bind_param("i", $grant['id']);
                        $m_query->execute();
                        $milestones = $m_query->get_result();
                        
                        if ($milestones->num_rows > 0):
                        ?>
                            <div class="milestone-list">
                                <?php while ($m = $milestones->fetch_assoc()): ?>
                                    <div class="milestone-item <?= $m['status'] == 'COMPLETED' ? 'completed' : '' ?>">
                                        <strong><?= htmlspecialchars($m['title']) ?></strong>
                                        <span style="float:right; font-size:12px; padding:3px 8px; border-radius:10px; background:<?= $m['status']=='COMPLETED'?'#d4edda':'#fff3cd' ?>; color:<?= $m['status']=='COMPLETED'?'#155724':'#856404' ?>;">
                                            <?= $m['status'] ?>
                                        </span>
                                        <br><small style="color:#666;">Due: <?= date('M d, Y', strtotime($m['target_date'])) ?></small>
                                        <?php if ($m['description']): ?>
                                            <br><small style="color:#888;"><?= htmlspecialchars($m['description']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p style="color:#999; font-style:italic;">No milestones defined for this grant</p>
                        <?php endif; ?>
                        
                        <div style="margin-top:20px; text-align:right;">
                            <button onclick="openReportModal(<?= $grant['id'] ?>)" class="btn-save">
                                <i class='bx bx-plus'></i> Submit Progress Report
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px; color:#999;">
                    <i class='bx bx-info-circle' style="font-size:48px;"></i>
                    <p>No active grants. Submit a proposal to get started!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ========== TAB 3: EXPENDITURES & CLAIMS ========== -->
        <div id="expenditures" class="tab-content">
            <?php 
            $my_grants->data_seek(0); // Reset pointer
            if ($my_grants->num_rows > 0): 
                $grant = $my_grants->fetch_assoc();
            ?>
                <h3 style="color:#3C5B6F; margin-top:0;">
                    <i class='bx bx-receipt'></i> Log Out-of-Pocket Expenditure
                </h3>
                <p style="color:#666; margin-bottom:15px;">Record expenses you paid from personal funds. Upload receipts and submit for reimbursement.</p>
                
                <form method="POST" enctype="multipart/form-data" style="background:#f8f9fa; padding:20px; border-radius:8px; margin-bottom:30px;">
                    <input type="hidden" name="grant_id" value="<?= $grant['id'] ?>">
                    
                    <div class="grid-2">
                        <div class="input-group">
                            <label>Budget Category *</label>
                            <select name="budget_item_id" required>
                                <option value="">Select Category</option>
                                <?php
                                $bi_query = $conn->prepare("SELECT * FROM budget_items WHERE proposal_id = ?");
                                $bi_query->bind_param("i", $grant['id']);
                                $bi_query->execute();
                                $budget_items = $bi_query->get_result();
                                while($bi = $budget_items->fetch_assoc()):
                                    $remaining = $bi['allocated_amount'] - $bi['spent_amount'];
                                ?>
                                    <option value="<?= $bi['id'] ?>"><?= $bi['category'] ?> (Remaining: $<?= number_format($remaining, 2) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Amount Spent ($) *</label>
                            <input type="number" name="expenditure_amount" step="0.01" min="0.01" required placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="grid-2">
                        <div class="input-group">
                            <label>Transaction Date *</label>
                            <input type="date" name="transaction_date" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="input-group">
                            <label>Upload Receipt (PDF/Image) *</label>
                            <input type="file" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Description *</label>
                        <textarea name="expenditure_description" rows="2" required placeholder="What did you purchase? (e.g., Laboratory equipment, Conference registration)"></textarea>
                    </div>
                    
                    <button type="submit" name="log_expenditure" class="btn-save">
                        <i class='bx bx-save'></i> Log Expenditure
                    </button>
                </form>

                <hr style="margin:30px 0;">
                
                <h3 style="color:#3C5B6F;">
                    <i class='bx bx-dollar'></i> Request Reimbursement
                </h3>
                <p style="color:#666; margin-bottom:15px;">Select logged expenditures and submit for HOD approval to claim your funds.</p>
                
                <?php
                // Get pending expenditures
                $exp_query = $conn->prepare("
                    SELECT e.*, b.category 
                    FROM expenditures e 
                    JOIN budget_items b ON e.budget_item_id = b.id 
                    WHERE b.proposal_id = ? AND e.status = 'PENDING_REIMBURSEMENT'
                    ORDER BY e.transaction_date DESC
                ");
                $exp_query->bind_param("i", $grant['id']);
                $exp_query->execute();
                $pending_exp = $exp_query->get_result();

                if ($pending_exp->num_rows > 0):
                ?>
                    <form method="POST">
                        <input type="hidden" name="grant_id" value="<?= $grant['id'] ?>">
                        
                        <?php 
                        $total_selected = 0;
                        while($exp = $pending_exp->fetch_assoc()): 
                        ?>
                            <div class="expenditure-card">
                                <label style="display:flex; align-items:flex-start; cursor:pointer;">
                                    <input type="checkbox" name="expenditure_ids[]" value="<?= $exp['id'] ?>" class="reimburse-checkbox" onchange="updateTotal()">
                                    <div style="flex:1;">
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <strong style="font-size:16px; color:#2c3e50;"><?= $exp['category'] ?></strong>
                                            <span style="font-size:20px; font-weight:bold; color:#28a745;">$<?= number_format($exp['amount'], 2) ?></span>
                                        </div>
                                        <p style="margin:5px 0; color:#666; font-size:13px;">
                                            <i class='bx bx-calendar'></i> <?= date('M d, Y', strtotime($exp['transaction_date'])) ?>
                                        </p>
                                        <p style="margin:5px 0; color:#555;"><?= htmlspecialchars($exp['description']) ?></p>
                                        <?php if($exp['receipt_path']): ?>
                                            <a href="<?= $exp['receipt_path'] ?>" target="_blank" style="color:#3C5B6F; font-size:13px; text-decoration:none;">
                                                <i class='bx bx-receipt'></i> View Receipt
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            </div>
                        <?php endwhile; ?>
                        
                        <div style="background:#fff3cd; padding:15px; border-radius:5px; margin:15px 0;">
                            <strong>Total Amount to Claim: <span id="totalClaim" style="font-size:20px; color:#28a745;">$0.00</span></strong>
                        </div>
                        
                        <div class="input-group">
                            <label>Justification for Reimbursement *</label>
                            <textarea name="reimbursement_justification" rows="3" required placeholder="Explain why these expenses were necessary for your research..."></textarea>
                        </div>
                        
                        <button type="submit" name="request_reimbursement" class="btn-save" style="background:#28a745;">
                            <i class='bx bx-send'></i> Submit Reimbursement Request
                        </button>
                    </form>
                <?php else: ?>
                    <div style="text-align:center; padding:40px; background:#f8f9fa; border-radius:8px;">
                        <i class='bx bx-info-circle' style="font-size:48px; color:#999;"></i>
                        <p style="color:#999;">No pending expenditures. Log your expenses above first!</p>
                    </div>
                <?php endif; ?>

                <hr style="margin:30px 0;">
                
                <h3 style="color:#3C5B6F;">
                    <i class='bx bx-history'></i> Reimbursement History
                </h3>
                <?php
                $reimb_query = $conn->prepare("
                    SELECT * FROM reimbursement_requests 
                    WHERE grant_id = ? 
                    ORDER BY requested_at DESC
                ");
                $reimb_query->bind_param("i", $grant['id']);
                $reimb_query->execute();
                $reimb_history = $reimb_query->get_result();

                if ($reimb_history->num_rows > 0):
                ?>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Request Date</th>
                                <th>Amount Claimed</th>
                                <th>Status</th>
                                <th>HOD Remarks</th>
                                <th>Reviewed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($req = $reimb_history->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($req['requested_at'])) ?></td>
                                    <td><strong style="color:#28a745;">$<?= number_format($req['total_amount'], 2) ?></strong></td>
                                    <td>
                                        <span class="status-badge <?= strtolower($req['status']) ?>">
                                            <?= $req['status'] ?>
                                        </span>
                                    </td>
                                    <td style="font-size:12px; color:#666; max-width:200px;">
                                        <?= $req['hod_remarks'] ? htmlspecialchars($req['hod_remarks']) : '-' ?>
                                    </td>
                                    <td style="font-size:12px;">
                                        <?= $req['reviewed_at'] ? date('M d, Y', strtotime($req['reviewed_at'])) : '-' ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align:center; color:#999; padding:20px;">No reimbursement requests submitted yet</p>
                <?php endif; ?>

            <?php else: ?>
                <div style="text-align:center; padding:60px;">
                    <i class='bx bx-info-circle' style="font-size:64px; color:#ccc;"></i>
                    <h4 style="color:#999;">No Active Grants</h4>
                    <p style="color:#999;">Submit a proposal and get it approved first to access expenditure tracking.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ========== TAB 4: PROGRESS REPORTS ========== -->
        <div id="reports" class="tab-content">
            <h3 style="color:#3C5B6F; margin-top:0;">
                <i class='bx bx-bar-chart-alt-2'></i> My Progress Reports
            </h3>
            
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Grant</th>
                        <th>Title</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $my_reports->data_seek(0);
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
                            <td>
                                <button onclick="openExtModal(<?= $rep['id'] ?>, '<?= date('Y-m-d', strtotime($rep['deadline'])) ?>')" class="btn-action" style="background:#f39c12; color:white;">
                                    <i class='bx bx-time'></i> Request Extension
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" style="text-align:center; color:#999;">No reports submitted yet.</td></tr>
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
                        <th>Original Deadline</th>
                        <th>Requested Deadline</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $ext_query = $conn->prepare("
                        SELECT er.*, pr.title as report_title, pr.deadline as original_deadline
                        FROM extension_requests er 
                        JOIN progress_reports pr ON er.report_id = pr.id 
                        WHERE er.researcher_email = ?
                        ORDER BY er.requested_at DESC
                    ");
                    $ext_query->bind_param("s", $email);
                    $ext_query->execute();
                    $extensions = $ext_query->get_result();
                    
                    if($extensions->num_rows > 0): 
                        while($ext = $extensions->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($ext['report_title']) ?></td>
                            <td style="font-size:12px; color:#dc3545;">
                                <?= date('M d, Y', strtotime($ext['original_deadline'])) ?>
                            </td>
                            <td style="font-size:12px; font-weight:bold; color:#28a745;">
                                <?= date('M d, Y', strtotime($ext['new_deadline'])) ?>
                            </td>
                            <td>
                                <?php if($ext['status'] == 'APPROVED'): ?>
                                    <span style="color:#28a745; font-weight:bold;"><i class='bx bx-check-circle'></i> Approved</span>
                                <?php elseif($ext['status'] == 'REJECTED'): ?>
                                    <span style="color:#dc3545; font-weight:bold;"><i class='bx bx-x-circle'></i> Rejected</span>
                                <?php else: ?>
                                    <span style="color:#ffc107; font-weight:bold;"><i class='bx bx-time'></i> Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" style="text-align:center; color:#999;">No extension requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- MODALS -->
    
    <!-- MODAL: DELETE CONFIRMATION -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="width:40%; max-width:500px;">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <h3 style="color:#dc3545; margin-top:0;">
                <i class='bx bx-trash'></i> Confirm Deletion
            </h3>
            <p style="font-size:14px; color:#666;">Are you sure you want to delete this proposal?</p>
            <p style="font-weight:bold; color:#333;" id="deleteProposalName"></p>
            <p style="font-size:13px; color:#dc3545;">This action cannot be undone!</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="proposal_id" id="delete_prop_id">
                <button type="submit" name="delete_proposal" class="btn-action btn-delete" style="padding:10px 20px;">
                    <i class='bx bx-trash'></i> Yes, Delete
                </button>
                <button type="button" onclick="closeModal('deleteModal')" style="background:#6c757d; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; margin-left:10px;">
                    Cancel
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL: APPEAL PROPOSAL -->
    <div id="appealModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('appealModal')">&times;</span>
            <h3 style="color:#e74c3c; margin-top:0;">
                <i class='bx bx-error-alt'></i> Appeal Proposal Rejection
            </h3>
            <p style="font-size:14px; color:#666; line-height:1.6;">
                Provide a detailed rebuttal explaining why the review decision was factually incorrect.
            </p>
            <form method="POST">
                <input type="hidden" name="proposal_id" id="appeal_prop_id">
                <div class="input-group">
                    <label>Detailed Justification / Rebuttal *</label>
                    <textarea name="justification" rows="6" required placeholder="Explain why this proposal should be reconsidered..."></textarea>
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

    <!-- MODAL: AMEND PROPOSAL -->
    <div id="amendModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('amendModal')">&times;</span>
            <h3 style="color:#17a2b8; margin-top:0;">
                <i class='bx bx-edit-alt'></i> Submit Amendment
            </h3>
            <p style="font-size:14px; color:#666;">
                Upload your corrected proposal document. System will track this as a new version.
            </p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="proposal_id" id="amend_prop_id">
                <div class="input-group">
                    <label>Amendment Notes</label>
                    <textarea name="amendment_notes" rows="3" placeholder="Describe the changes you made..."></textarea>
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

    <!-- MODAL: SUBMIT PROGRESS REPORT -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('reportModal')">&times;</span>
            <h3 style="color:#3C5B6F; margin-top:0;">
                <i class='bx bx-file-plus'></i> Submit Progress Report
            </h3>
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
                    <textarea name="challenges" rows="3" placeholder="Describe any obstacles encountered..."></textarea>
                </div>
                
                <!-- MILESTONE TRACKING -->
                <div class="input-group">
                    <label>Mark Completed Milestones</label>
                    <div class="milestone-list" id="milestone_container">
                        <p style="color:#999; font-style:italic;">Loading milestones...</p>
                    </div>
                </div>
                
                <div class="input-group">
                    <label>Report Deadline *</label>
                    <input type="date" name="report_deadline" required>
                </div>
                <div class="input-group">
                    <label>Evidence of Work (PDF) *</label>
                    <input type="file" name="report_file" accept=".pdf" required>
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

    <!-- MODAL: REQUEST DEADLINE EXTENSION -->
    <div id="extModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('extModal')">&times;</span>
            <h3 style="color:#f39c12; margin-top:0;">
                <i class='bx bx-time-five'></i> Request Deadline Extension
            </h3>
            <p style="color:#666; font-size:14px;">
                Original Deadline: <strong style="color:#dc3545;" id="original_deadline_display"></strong>
            </p>
            <form method="POST">
                <input type="hidden" name="report_id" id="ext_report_id">
                <div class="input-group">
                    <label>New Target Deadline *</label>
                    <input type="date" name="new_date" id="new_deadline_input" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    <small style="color:#666;">Must be a future date</small>
                </div>
                <div class="input-group">
                    <label>Justification for Extension *</label>
                    <textarea name="justification" rows="4" required placeholder="e.g., Fieldwork delayed due to weather, Equipment malfunction, etc."></textarea>
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

        // ========== TAB SWITCHING ==========
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

        // ========== BUDGET CALCULATION ==========
        function calculateTotal() {
            var categories = document.getElementsByClassName('budget-category');
            var total = 0;
            for (var i = 0; i < categories.length; i++) {
                total += parseFloat(categories[i].value) || 0;
            }
            document.getElementById('totalBudget').value = total.toFixed(2);
        }

        // ========== MILESTONE MANAGEMENT ==========
        function addMilestone() {
            var container = document.getElementById('milestones_container');
            var newMilestone = document.createElement('div');
            newMilestone.className = 'milestone-input';
            newMilestone.innerHTML = `
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button>
                <input type="text" name="milestone_title[]" placeholder="Milestone Title" required>
                <textarea name="milestone_desc[]" rows="2" placeholder="Brief description"></textarea>
                <input type="date" name="milestone_date[]" required>
            `;
            container.appendChild(newMilestone);
        }

        // ========== REIMBURSEMENT TOTAL CALCULATION - FIXED ==========
        function updateTotal() {
            var checkboxes = document.getElementsByClassName('reimburse-checkbox');
            var total = 0;
            
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) {
                    var card = checkboxes[i].closest('.expenditure-card');
                    var amountText = card.querySelector('span[style*="font-size:20px"]').textContent;
                    // Remove $ and commas, then parse
                    var amount = parseFloat(amountText.replace(/[$,]/g, ''));
                    if (!isNaN(amount)) {
                        total += amount;
                    }
                }
            }
            
            // Format with commas
            var formatted = '$' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            document.getElementById('totalClaim').textContent = formatted;
        }

        // ========== DELETE CONFIRMATION ==========
        function confirmDelete(propId, propTitle) {
            document.getElementById('deleteModal').style.display = "block";
            document.getElementById('delete_prop_id').value = propId;
            document.getElementById('deleteProposalName').textContent = propTitle;
        }

        // ========== AMEND MODAL ==========
        function openAmendModal(propId) {
            document.getElementById('amendModal').style.display = "block";
            document.getElementById('amend_prop_id').value = propId;
        }

        // ========== APPEAL MODAL ==========
        function openAppealModal(propId) {
            document.getElementById('appealModal').style.display = "block";
            document.getElementById('appeal_prop_id').value = propId;
        }

        // ========== PROGRESS REPORT MODAL WITH MILESTONE LOADING ==========
        function openReportModal(grantId) {
            document.getElementById('reportModal').style.display = "block";
            document.getElementById('report_grant_id').value = grantId;
            
            // Load milestones for this grant via AJAX
            fetch('get_milestones.php?grant_id=' + grantId)
                .then(response => response.json())
                .then(data => {
                    var container = document.getElementById('milestone_container');
                    if (data.error) {
                        container.innerHTML = '<p style="color:#dc3545;">' + data.error + '</p>';
                        return;
                    }
                    
                    if (data.length > 0) {
                        var html = '';
                        data.forEach(function(milestone) {
                            var completedClass = milestone.status === 'COMPLETED' ? 'completed' : '';
                            var checked = milestone.status === 'COMPLETED' ? 'checked disabled' : '';
                            html += '<div class="milestone-item ' + completedClass + '">';
                            html += '<label style="cursor:pointer;"><input type="checkbox" name="milestones[]" value="' + milestone.id + '" ' + checked + '> ';
                            html += milestone.title + ' <small style="color:#666;">(' + milestone.status + ')</small></label>';
                            html += '</div>';
                        });
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '<p style="color:#999; font-style:italic;">No milestones defined for this grant.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading milestones:', error);
                    document.getElementById('milestone_container').innerHTML = '<p style="color:#dc3545;">Error loading milestones. Please try again.</p>';
                });
        }

        // ========== EXTENSION MODAL ==========
        function openExtModal(reportId, originalDeadline) {
            document.getElementById('extModal').style.display = "block";
            document.getElementById('ext_report_id').value = reportId;
            
            // Format and display original deadline
            var date = new Date(originalDeadline);
            var formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('original_deadline_display').textContent = formattedDate;
            
            // Set minimum date for new deadline (day after original)
            var minDate = new Date(date.getTime() + 86400000);
            var minDateString = minDate.toISOString().split('T')[0];
            document.getElementById('new_deadline_input').min = minDateString;
        }

        // ========== CLOSE MODAL ==========
        function closeModal(id) { 
            document.getElementById(id).style.display = "none"; 
        }

        // ========== CLOSE MODAL WHEN CLICKING OUTSIDE ==========
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