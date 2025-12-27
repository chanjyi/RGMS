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

// 2. HANDLE FORM SUBMISSION
if (isset($_POST['submit_review'])) {
    $feedback = $_POST['feedback'];
    $decision = $_POST['decision']; // 'RECOMMEND' or 'REJECT'
    $prop_id = $data['prop_id'];
    
    // Check priority checkbox
    $priority = isset($_POST['priority']) ? 'High' : 'Normal';

    // File Upload
    $annotated_path = NULL;
    if (!empty($_FILES['annotated_pdf']['name'])) {
        $target_dir = "uploads/reviews/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $annotated_path = $target_dir . "rev_" . time() . "_" . basename($_FILES['annotated_pdf']['name']);
        move_uploaded_file($_FILES['annotated_pdf']['tmp_name'], $annotated_path);
    }

    // A. UPDATE reviews table
    $upd_rev = $conn->prepare("UPDATE reviews SET feedback = ?, annotated_file = ?, decision = ?, status = 'Completed', review_date = NOW() WHERE id = ?");
    $upd_rev->bind_param("sssi", $feedback, $annotated_path, $decision, $review_id);
    
    if ($upd_rev->execute()) {
        
        // B. DETERMINE FINAL STATUS
        $is_appeal_case = ($data['review_type'] == 'Appeal');

        if ($decision == 'RECOMMEND') {
            $final_status = 'RECOMMENDED';
            $final_priority = $priority; 
        } else {
            // Rejection Logic
            if ($is_appeal_case) {
                $final_status = 'APPEAL_REJECTED';
            } else {
                $final_status = 'REJECTED';
            }
            $final_priority = 'Normal'; 
        }

        // C. UPDATE PROPOSAL STATUS
        $upd_prop = $conn->prepare("UPDATE proposals SET status = ?, priority = ? WHERE id = ?");
        $upd_prop->bind_param("ssi", $final_status, $final_priority, $prop_id);
        
        if ($upd_prop->execute()) {
            
            // D. NOTIFY RESEARCHER
            $res_email = $data['researcher_email'];
            $msg = "Update on '$data[title]': Your proposal status is now " . strtolower($final_status) . ".";
            
            $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
            $notif->bind_param("ss", $res_email, $msg);
            $notif->execute();
            
            header("Location: reviewer_page.php");
            exit();
        } else {
            die("Error Updating Proposal Status: " . $conn->error); 
        }
    } else {
        die("Error Saving Review: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Proposal</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section" style="padding: 20px;">
        <a href="reviewer_page.php" class="btn-back"><i class='bx bx-arrow-back'></i> Back</a>
        
        <h1>Reviewing: <?= htmlspecialchars($data['title']) ?></h1>
        <button type="button" onclick="document.getElementById('reportModal').style.display='block'" 
            style="background-color: #dc3545; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; float: right; margin-top: -40px;">
            <i class='bx bx-flag'></i> Report Misconduct
        </button>
        
        <?php if($data['review_type'] == 'Appeal'): ?>
            <div class="alert error" style="display:inline-block; padding: 5px 10px; margin-bottom: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                <i class='bx bx-error'></i> This is an <strong>Appeal Case</strong>. Rejection will be final.
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 25px;">
            <iframe src="<?= $data['file_path'] ?>" style="width:100%; height:500px; border:1px solid #ccc;"></iframe>
        </div>

        <div class="form-box">
            <h2 style="margin-top:0;">Submit Review</h2>
            <form method="POST" enctype="multipart/form-data">
                
                <div class="input-group">
                    <label>Written Feedback</label>
                    <textarea name="feedback" rows="5" required></textarea>
                </div>

                <div class="input-group">
                    <label>Annotated PDF (Optional)</label>
                    <input type="file" name="annotated_pdf" accept=".pdf">
                </div>
                
                <div style="margin-bottom: 20px; background: #fff3cd; padding: 10px; border-radius: 5px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #856404; font-weight: 600;">
                        <input type="checkbox" name="priority" value="High" style="width: 20px; height: 20px;">
                        <i class='bx bx-up-arrow-circle'></i> Mark as Urgent / Prioritize for HOD
                    </label>
                </div>

                <div style="display:flex; gap:15px;">
                    <button type="submit" name="decision" value="RECOMMEND" class="btn-success btn-action">
                        <i class='bx bx-check-circle'></i> Recommend
                    </button>
                    
                    <button type="submit" name="decision" value="REJECT" class="btn-danger btn-action">
                        <i class='bx bx-x-circle'></i> Reject
                    </button>
                </div>
                
                <input type="hidden" name="submit_review" value="1">
            </form>
        </div>
    </section>
    <div id="reportModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
  <div style="background-color: #fff; margin: 10% auto; padding: 25px; border-radius: 10px; width: 500px; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
    
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
        <option value="Unethical Conduct">Unethical Conduct</option>
        <option value="Other">Other</option>
      </select>

      <label style="display:block; margin-bottom: 5px; font-weight: bold;">Details / Evidence</label>
      <textarea name="details" rows="5" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 20px;" placeholder="Describe the misconduct..."></textarea>

      <div style="text-align: right;">
          <button type="button" onclick="document.getElementById('reportModal').style.display='none'" 
              style="background: #ccc; color: #333; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
              Cancel
          </button>
          <button type="submit" name="submit_report" 
              style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
              Submit Report
          </button>
      </div>
    </form>
  </div>
</div>
</body>
</html>