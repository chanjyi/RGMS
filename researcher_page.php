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

// Helper: Notify Admin/HOD
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
// 1. USE CASE 7: SUBMIT PROPOSAL
// =========================================================
if (isset($_POST['submit_proposal'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_name = basename($_FILES["proposal_file"]["name"]);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $clean_email = str_replace(['@', '.'], '_', $email);
    $new_file_name = "prop_" . time() . "_" . $clean_email . "." . $file_type;
    $target_file = $target_dir . $new_file_name;

    if ($file_type != "pdf") {
        $message = "Error: Only PDF allowed."; $messageType = "error";
    } else {
        if (move_uploaded_file($_FILES["proposal_file"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO proposals (title, researcher_email, file_path, status) VALUES (?, ?, ?, 'SUBMITTED')");
            $stmt->bind_param("sss", $title, $email, $target_file);
            if ($stmt->execute()) {
                notifySystem($conn, 'admin', "New Proposal Submitted: '$title' by $email");
                $message = "Proposal submitted successfully!"; $messageType = "success";
            } else {
                $message = "DB Error: " . $conn->error; $messageType = "error";
            }
        }
    }
}

// =========================================================
// 2. USE CASE 11: AMEND PROPOSAL
// =========================================================
if (isset($_POST['amend_proposal'])) {
    $prop_id = $_POST['proposal_id'];
    $target_dir = "uploads/";
    
    $file_name = basename($_FILES["amend_file"]["name"]);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_file_name = "amend_" . time() . "_" . $prop_id . "." . $file_type;
    $target_file = $target_dir . $new_file_name;

    if ($file_type != "pdf") {
        $message = "Error: PDF only."; $messageType = "error";
    } else {
        if (move_uploaded_file($_FILES["amend_file"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("UPDATE proposals SET file_path = ?, status = 'RESUBMITTED' WHERE id = ?");
            $stmt->bind_param("si", $target_file, $prop_id);
            if ($stmt->execute()) {
                notifySystem($conn, 'admin', "Proposal #$prop_id Amended by $email");
                $message = "Amendment submitted!"; $messageType = "success";
            }
        }
    }
}

// =========================================================
// 3. USE CASE 10: APPEAL PROPOSAL REJECTION (CORRECTED)
// =========================================================
if (isset($_POST['appeal_proposal'])) {
    $prop_id = $_POST['proposal_id'];
    $justification = mysqli_real_escape_string($conn, $_POST['justification']); // The missing piece
    
    // 1. Insert detailed justification into appeal_requests table
    $stmt = $conn->prepare("INSERT INTO appeal_requests (proposal_id, researcher_email, justification) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $prop_id, $email, $justification);
    
    if ($stmt->execute()) {
        // 2. Lock status to 'APPEALED' ("Under Appeal")
        $update = $conn->prepare("UPDATE proposals SET status = 'APPEALED' WHERE id = ?");
        $update->bind_param("i", $prop_id);
        $update->execute();

        // 3. Notify HOD (as per SRS: "Routes to HOD")
        notifySystem($conn, 'hod', "Appeal Request: $email has contested rejection of Proposal #$prop_id.");
        $message = "Appeal submitted with justification."; $messageType = "success";
    } else {
        $message = "Error submitting appeal: " . $conn->error; $messageType = "error";
    }
}

// =========================================================
// 4. USE CASE 8: SUBMIT PROGRESS REPORT
// =========================================================
if (isset($_POST['submit_report'])) {
    $grant_id = $_POST['grant_id'];
    $rep_title = mysqli_real_escape_string($conn, $_POST['report_title']);
    
    $target_dir = "uploads/reports/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_name = basename($_FILES["report_file"]["name"]);
    $new_file_name = "rep_" . time() . "_" . $grant_id . ".pdf";
    $target_file = $target_dir . $new_file_name;

    if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO progress_reports (proposal_id, researcher_email, title, file_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $grant_id, $email, $rep_title, $target_file);
        $stmt->execute();
        $message = "Progress Report uploaded."; $messageType = "success";
    }
}

// =========================================================
// 5. USE CASE 12: REQUEST DEADLINE EXTENSION
// =========================================================
if (isset($_POST['request_extension'])) {
    $report_id = $_POST['report_id'];
    $new_date = $_POST['new_date'];
    $reason = mysqli_real_escape_string($conn, $_POST['justification']);

    $stmt = $conn->prepare("INSERT INTO extension_requests (report_id, researcher_email, new_deadline, justification) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $report_id, $email, $new_date, $reason);
    if ($stmt->execute()) {
        $message = "Extension request submitted."; $messageType = "success";
    }
}

// =========================================================
// DATA FETCHING
// =========================================================
$sql_props = "SELECT p.*, r.decision as reviewer_decision, r.feedback 
              FROM proposals p 
              LEFT JOIN reviews r ON p.id = r.proposal_id 
              WHERE p.researcher_email = '$email' 
              ORDER BY p.created_at DESC";
$my_props = $conn->query($sql_props);

$sql_grants = "SELECT * FROM proposals WHERE researcher_email = '$email' AND status = 'APPROVED'";
$my_grants = $conn->query($sql_grants);

$sql_reports = "SELECT pr.*, p.title as grant_title 
                FROM progress_reports pr 
                JOIN proposals p ON pr.proposal_id = p.id 
                WHERE pr.researcher_email = '$email'";
$my_reports = $conn->query($sql_reports);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Researcher Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .tab-btn { padding: 10px 20px; cursor: pointer; border: none; background: #eee; font-size:16px; }
        .tab-btn.active { background: #3C5B6F; color: white; }
        .tab-content { display: none; padding: 20px; border: 1px solid #ccc; margin-top: -1px; }
        .tab-content.active { display: block; }
        .modal { display: none; position: fixed; z-index: 99; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 10% auto; padding: 20px; width: 50%; border-radius: 8px; }
        .close { float: right; font-size: 28px; cursor: pointer; }
        .budget-card { background: #f8f9fa; padding: 15px; margin-bottom: 10px; border-left: 5px solid #28a745; }
        textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; resize: vertical; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
        <div class="welcome-text">Researcher Dashboard | <?= htmlspecialchars($_SESSION['name']); ?></div>
        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 20px;">

        <?php if ($message): ?>
            <div class="alert" style="padding:15px; margin-bottom:20px; border-radius:5px; 
                background: <?= $messageType=='success'?'#d4edda':'#f8d7da' ?>; 
                color: <?= $messageType=='success'?'#155724':'#721c24' ?>;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div>
            <button class="tab-btn active" onclick="openTab(event, 'proposals')">My Proposals</button>
            <button class="tab-btn" onclick="openTab(event, 'grants')">Active Grants & Reports</button>
        </div>

        <div id="proposals" class="tab-content active">
            <div class="form-box" style="margin-bottom: 30px;">
                <h3>Submit New Proposal</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <label>Title</label> <input type="text" name="title" required>
                    </div>
                    <div class="input-group">
                        <label>PDF File</label> <input type="file" name="proposal_file" accept=".pdf" required>
                    </div>
                    <button type="submit" name="submit_proposal" class="btn-save">Submit</button>
                </form>
            </div>

            <table class="styled-table">
                <thead><tr><th>Title</th><th>Status</th><th>Reviewer Feedback</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while($row = $my_props->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($row['status']) ?>">
                                    <?= str_replace('_', ' ', $row['status']) ?>
                                </span>
                            </td>
                            <td style="font-size:12px; color:#555;">
                                <?= !empty($row['reviewer_feedback']) ? htmlspecialchars($row['reviewer_feedback']) : "<em>None</em>" ?>
                            </td>
                            <td>
                                <?php if($row['status'] == 'REQUIRES_AMENDMENT'): ?>
                                    <button onclick="openModal('amendModal', <?= $row['id'] ?>)" class="btn-edit">Amend</button>
                                
                                <?php elseif($row['status'] == 'REJECTED' && $row['reviewer_decision'] == 'REJECT'): ?>
                                    <button onclick="openAppealModal(<?= $row['id'] ?>)" style="background:#e74c3c; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">
                                        Appeal
                                    </button>
                                
                                <?php elseif($row['status'] == 'APPEALED'): ?>
                                    <span style="color:#e67e22; font-size:12px;">Under Appeal</span>
                                
                                <?php else: ?>
                                    <span style="color:#999;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div id="grants" class="tab-content">
            <h3>Active Grants (Funding)</h3>
            <?php if ($my_grants->num_rows > 0): ?>
                <?php while($grant = $my_grants->fetch_assoc()): ?>
                    <div class="budget-card">
                        <h4><?= htmlspecialchars($grant['title']) ?> (ID: <?= $grant['id'] ?>)</h4>
                        <p><strong>Budget:</strong> $<?= number_format($grant['approved_budget'], 2) ?> | 
                           <strong>Spent:</strong> $<?= number_format($grant['amount_spent'], 2) ?></p>
                        <button onclick="openReportModal(<?= $grant['id'] ?>)" class="btn-save" style="margin-top:10px; font-size:12px;">+ Submit Progress Report</button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No active grants.</p>
            <?php endif; ?>

            <h3 style="margin-top:30px;">My Submitted Reports</h3>
            <table class="styled-table">
                <thead><tr><th>Grant</th><th>Report Title</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while($rep = $my_reports->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($rep['grant_title']) ?></td>
                            <td><?= htmlspecialchars($rep['title']) ?></td>
                            <td><?= $rep['status'] ?></td>
                            <td>
                                <button onclick="openExtModal(<?= $rep['id'] ?>)" style="background:#f39c12; color:white; border:none; padding:5px; border-radius:3px; cursor:pointer;">Request Extension</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div id="appealModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('appealModal')">&times;</span>
            <h3 style="color:#e74c3c;">Appeal Rejection</h3>
            <p style="font-size:13px; color:#555;">Please provide a detailed rebuttal/justification for why this proposal should be reconsidered.</p>
            <form method="POST">
                <input type="hidden" name="proposal_id" id="appeal_prop_id">
                <label>Justification:</label><br>
                <textarea name="justification" rows="5" required placeholder="Explain why the review was factually incorrect..."></textarea><br><br>
                <button type="submit" name="appeal_proposal" class="btn-save" style="background:#e74c3c;">Submit Appeal</button>
            </form>
        </div>
    </div>

    <div id="amendModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('amendModal')">&times;</span>
            <h3>Submit Amendment</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="proposal_id" id="amend_prop_id">
                <label>Upload Revised PDF:</label><br>
                <input type="file" name="amend_file" accept=".pdf" required><br><br>
                <button type="submit" name="amend_proposal" class="btn-save">Resubmit Proposal</button>
            </form>
        </div>
    </div>

    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('reportModal')">&times;</span>
            <h3>Upload Progress Report</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="grant_id" id="report_grant_id">
                <label>Report Title:</label> <input type="text" name="report_title" required style="width:100%; margin-bottom:10px;">
                <label>File (PDF):</label> <input type="file" name="report_file" accept=".pdf" required><br><br>
                <button type="submit" name="submit_report" class="btn-save">Upload</button>
            </form>
        </div>
    </div>

    <div id="extModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('extModal')">&times;</span>
            <h3>Request Deadline Extension</h3>
            <form method="POST">
                <input type="hidden" name="report_id" id="ext_report_id">
                <label>New Deadline:</label> <input type="date" name="new_date" required><br><br>
                <label>Justification:</label> <textarea name="justification" required></textarea><br><br>
                <button type="submit" name="request_extension" class="btn-save">Submit Request</button>
            </form>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) tabcontent[i].style.display = "none";
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) tablinks[i].className = tablinks[i].className.replace(" active", "");
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
        function openModal(id, propId) {
            document.getElementById(id).style.display = "block";
            if(propId) document.getElementById('amend_prop_id').value = propId;
        }
        function openAppealModal(propId) {
            document.getElementById('appealModal').style.display = "block";
            document.getElementById('appeal_prop_id').value = propId;
        }
        function openReportModal(grantId) {
            document.getElementById('reportModal').style.display = "block";
            document.getElementById('report_grant_id').value = grantId;
        }
        function openExtModal(repId) {
            document.getElementById('extModal').style.display = "block";
            document.getElementById('ext_report_id').value = repId;
        }
        function closeModal(id) { document.getElementById(id).style.display = "none"; }
    </script>
</body>
</html>