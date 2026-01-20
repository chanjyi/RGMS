<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'reviewer') {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['review_id'])) {
    die("Error: No review selected.");
}

$review_id = $_GET['review_id'];

// 1. FETCH REVIEW DATA
$query = "SELECT r.*, r.type as review_type, p.title, p.file_path, p.researcher_email, p.id as prop_id 
          FROM reviews r 
          JOIN proposals p ON r.proposal_id = p.id 
          WHERE r.id = ?";

$q = $conn->prepare($query);
$q->bind_param("i", $review_id);
$q->execute();
$data = $q->get_result()->fetch_assoc();

if (!$data) die("Review not found.");

// ==========================================
// 2. UNIFIED FORM HANDLING
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_type'])) {
    
    $action  = $_POST['action_type']; // Values: RECOMMEND, REJECT, AMENDMENT
    $feedback = $_POST['feedback'];
    $prop_id = $data['prop_id'];

    // --- 1. HANDLE FILE UPLOAD (Moved here so it works for ALL actions) ---
    $annotated_path = NULL;
    if (!empty($_FILES['annotated_pdf']['name'])) {
        $target_dir = "uploads/reviews/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $annotated_path = $target_dir . "rev_" . time() . "_" . basename($_FILES['annotated_pdf']['name']);
        move_uploaded_file($_FILES['annotated_pdf']['tmp_name'], $annotated_path);
    }
    
    // --- CASE A: AMENDMENT REQUEST ---
    if ($action == 'AMENDMENT') {
        // Update Proposal
        $upd_prop = $conn->prepare("UPDATE proposals SET status = 'REQUIRES_AMENDMENT', reviewer_feedback = ? WHERE id = ?");
        $upd_prop->bind_param("si", $feedback, $prop_id);

        // Update Review (Mark Completed)
        // FIX: Added annotated_file to the query
        $upd_rev = $conn->prepare("UPDATE reviews SET status = 'Completed', decision = 'AMENDMENT', feedback = ?, annotated_file = ?, review_date = NOW() WHERE id = ?");
        $upd_rev->bind_param("ssi", $feedback, $annotated_path, $review_id);

        if ($upd_prop->execute() && $upd_rev->execute()) {
            // Notify
            $msg = "Action Required: The reviewer requested amendments on '$data[title]'. Please check the dashboard.";
            $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
            $notif->bind_param("ss", $data['researcher_email'], $msg);
            $notif->execute();

            header("Location: reviewer_page.php?msg=amendment_sent");
            exit();
        }
    } 
    
    // --- CASE B: RECOMMEND OR REJECT ---
    else {
        $priority = (isset($_POST['priority']) && $action == 'RECOMMEND') ? 'High' : 'Normal';
        
        // Update Reviews Table
        $upd_rev = $conn->prepare("UPDATE reviews SET feedback = ?, annotated_file = ?, decision = ?, status = 'Completed', review_date = NOW() WHERE id = ?");
        $upd_rev->bind_param("sssi", $feedback, $annotated_path, $action, $review_id);
        
        if ($upd_rev->execute()) {
            // Determine Final Status
            $is_appeal_case = ($data['review_type'] == 'Appeal');
            
            if ($action == 'RECOMMEND') {
                $final_status = 'RECOMMEND'; // Wait for HOD
                $final_priority = $priority; 
            } else {
                $final_status = $is_appeal_case ? 'APPEAL_REJECTED' : 'REJECTED';
                $final_priority = 'Normal'; 
            }

            // Update Proposal
            $upd_prop = $conn->prepare("UPDATE proposals SET status = ?, priority = ? WHERE id = ?");
            $upd_prop->bind_param("ssi", $final_status, $final_priority, $prop_id);
            
            if ($upd_prop->execute()) {
                // Notify
                $msg = "Update on '$data[title]': Your proposal status is now " . strtolower($final_status) . ".";
                $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
                $notif->bind_param("ss", $data['researcher_email'], $msg);
                $notif->execute();
                
                header("Location: reviewer_page.php");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Proposal</title>
    <link rel="stylesheet" href="styling/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section" style="padding: 20px;">
        <a href="reviewer_page.php" class="btn-back" style="margin-left: 20px;">
            <i class='bx bx-arrow-back'></i> Back
        </a>
        
        <h1>Reviewing: <?= htmlspecialchars($data['title']) ?></h1>
        
        <button type="button" onclick="document.getElementById('reportModal').style.display='block'" 
            style="background-color: #dc3545; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; float: right; margin-top: -45px;">
            <i class='bx bx-flag'></i> Report Misconduct
        </button>
        
        <div style="margin-bottom: 25px;">
            <iframe src="<?= $data['file_path'] ?>" style="width:100%; height:500px; border:1px solid #ccc;"></iframe>
        </div>

        <div class="form-box">
            
            <h3 style="margin-top: 0; margin-bottom: 15px;">Step 1: Select Decision</h3>
            <div class="selection-container">
                
                <button type="button" class="selection-btn" onclick="selectAction('RECOMMEND', this)">
                    <i class='bx bx-check-circle'></i>
                    Recommend
                </button>

                <button type="button" class="selection-btn" onclick="selectAction('AMENDMENT', this)">
                    <i class='bx bx-edit'></i>
                    Request Amendment
                </button>

                <button type="button" class="selection-btn" onclick="selectAction('REJECT', this)">
                    <i class='bx bx-x-circle'></i>
                    Reject
                </button>

            </div>

            <div id="step-2-container">
                <form method="POST" enctype="multipart/form-data">
                    
                    <input type="hidden" name="action_type" id="action_input">

                    <h3 style="margin-top: 0;">Step 2: Provide Feedback</h3>
                    
                    <div class="input-group">
                        <label>Written Feedback / Comments</label>
                        <textarea name="feedback" rows="5" required placeholder="Enter your feedback here..."></textarea>
                    </div>

                    <div class="input-group">
                        <label>Annotated PDF (Optional)</label>
                        <input type="file" name="annotated_pdf" accept=".pdf">
                    </div>
                    
                    <div id="urgent-container">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #856404; font-weight: 600;">
                            <input type="checkbox" name="priority" value="High" style="width: 20px; height: 20px;">
                            <i class='bx bx-up-arrow-circle'></i> Mark as Urgent / Prioritize for HOD
                        </label>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn-save" id="submit-btn" style="width: 100%;">Submit Review</button>
                    </div>
                </form>
            </div>

        </div>
    </section>

    <div id="reportModal" class="modal">
      <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('reportModal').style.display='none'">&times;</span>
        
        <h3 style="color: #dc3545; margin-top: 0;">Report Misconduct</h3>
        <p style="font-size: 14px; color: #666; margin-bottom: 20px;">
            Reporting Researcher: <strong><?= htmlspecialchars($data['researcher_email']) ?></strong>
        </p>
        
        <form action="submit_report.php" method="POST">
            <input type="hidden" name="proposal_id" value="<?= $data['prop_id'] ?>">
            <input type="hidden" name="researcher_email" value="<?= htmlspecialchars($data['researcher_email']) ?>">
            <input type="hidden" name="proposal_title" value="<?= htmlspecialchars($data['title']) ?>">

          <label style="display:block; margin-bottom: 5px; font-weight: bold;">Violation Category</label>
          <select name="category" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 15px;">
            <option value="" disabled selected>-- Select Violation --</option>
            <option value="Plagiarism">Plagiarism</option>
            <option value="Data Fabrication">Data Fabrication</option>
            <option value="Falsification">Falsification</option>
            <option value="Other">Other</option>
          </select>

          <label style="display:block; margin-bottom: 5px; font-weight: bold;">Details / Evidence</label>
          <textarea name="details" rows="5" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 20px;"></textarea>

          <div style="text-align: right;">
              <button type="button" onclick="document.getElementById('reportModal').style.display='none'" style="padding: 10px 20px; background: #ccc; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">Cancel</button>
              <button type="submit" name="submit_report" class="btn-danger btn-action">Submit Report</button>
          </div>
        </form>
      </div>
    </div>

    <script>
        function selectAction(action, btnElement) {
            document.querySelectorAll('.selection-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');
            document.getElementById('action_input').value = action;
            document.getElementById('step-2-container').style.display = 'block';

            const urgentDiv = document.getElementById('urgent-container');
            const submitBtn = document.getElementById('submit-btn');

            if (action === 'RECOMMEND') {
                urgentDiv.style.display = 'block';
                submitBtn.innerText = "Confirm Recommendation";
                submitBtn.style.background = "#28a745"; 
            } else if (action === 'AMENDMENT') {
                urgentDiv.style.display = 'none';
                submitBtn.innerText = "Send Amendment Request";
                submitBtn.style.background = "#f39c12"; 
            } else if (action === 'REJECT') {
                urgentDiv.style.display = 'none';
                submitBtn.innerText = "Confirm Rejection";
                submitBtn.style.background = "#dc3545"; 
            }
            document.getElementById('step-2-container').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>