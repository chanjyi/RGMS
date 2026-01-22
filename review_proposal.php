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

// Fetch Previous Reviews for this Proposal
$hist_query = "SELECT r.decision, r.feedback, r.annotated_file, r.review_date, u.name as reviewer_name 
               FROM reviews r 
               JOIN users u ON r.reviewer_id = u.id 
               WHERE r.proposal_id = ? 
               AND r.status = 'Completed' 
               ORDER BY r.review_date DESC";
$hist_stmt = $conn->prepare($hist_query);
$hist_stmt->bind_param("i", $data['prop_id']);
$hist_stmt->execute();
$history_result = $hist_stmt->get_result();

// ==========================================
// 2. UNIFIED FORM HANDLING
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_type'])) {
    
    $action  = $_POST['action_type']; // Values: RECOMMEND, REJECT, AMENDMENT
    $feedback = $_POST['feedback'];
    $prop_id = $data['prop_id'];

    // Handle File Upload
    $annotated_path = NULL;
    if (!empty($_FILES['annotated_pdf']['name'])) {
        $target_dir = "uploads/reviews/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $annotated_path = $target_dir . "rev_" . time() . "_" . basename($_FILES['annotated_pdf']['name']);
        move_uploaded_file($_FILES['annotated_pdf']['tmp_name'], $annotated_path);
    }
    
    // CASE A: AMENDMENT
    if ($action == 'AMENDMENT') {
        $upd_prop = $conn->prepare("UPDATE proposals SET status = 'REQUIRES_AMENDMENT', reviewer_feedback = ? WHERE id = ?");
        $upd_prop->bind_param("si", $feedback, $prop_id);

        $upd_rev = $conn->prepare("UPDATE reviews SET status = 'Completed', decision = 'AMENDMENT', feedback = ?, annotated_file = ?, review_date = NOW() WHERE id = ?");
        $upd_rev->bind_param("ssi", $feedback, $annotated_path, $review_id);

        if ($upd_prop->execute() && $upd_rev->execute()) {
            $msg = "Action Required: The reviewer requested amendments on '$data[title]'. Please check the dashboard.";
            $conn->query("INSERT INTO notifications (user_email, message) VALUES ('{$data['researcher_email']}', '$msg')");
            header("Location: reviewer_page.php?msg=amendment_sent");
            exit();
        }
    } 
    // CASE B: RECOMMEND / REJECT
    else {
        $priority = (isset($_POST['priority']) && $action == 'RECOMMEND') ? 'High' : 'Normal';
        
        $upd_rev = $conn->prepare("UPDATE reviews SET feedback = ?, annotated_file = ?, decision = ?, status = 'Completed', review_date = NOW() WHERE id = ?");
        $upd_rev->bind_param("sssi", $feedback, $annotated_path, $action, $review_id);
        
        if ($upd_rev->execute()) {
            $is_appeal_case = ($data['review_type'] == 'Appeal');
            $final_status = ($action == 'RECOMMEND') ? 'RECOMMEND' : ($is_appeal_case ? 'APPEAL_REJECTED' : 'REJECTED');
            $final_priority = ($action == 'RECOMMEND') ? $priority : 'Normal';

            $upd_prop = $conn->prepare("UPDATE proposals SET status = ?, priority = ? WHERE id = ?");
            $upd_prop->bind_param("ssi", $final_status, $final_priority, $prop_id);
            
            if ($upd_prop->execute()) {
                $msg = "Update on '$data[title]': Your proposal status is now " . strtolower($final_status) . ".";
                $conn->query("INSERT INTO notifications (user_email, message) VALUES ('{$data['researcher_email']}', '$msg')");
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
    <link rel="stylesheet" href="styling/hod_pages.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section" style="padding: 20px;">
        <a href="reviewer_page.php" class="btn-back" style="margin-left: 20px;">
            <i class='bx bx-arrow-back'></i> Back
        </a>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0;">Reviewing: <?= htmlspecialchars($data['title']) ?></h1>
            
            <button type="button" onclick="document.getElementById('reportModal').style.display='block'" 
                style="background-color: #dc3545; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer;">
                <i class='bx bx-flag'></i> Report Misconduct
            </button>
        </div>

        <div style="margin-bottom: 0;">
            <button class="tab-btn active" onclick="openTab(event, 'evaluation_tab')">
                <i class='bx bx-edit'></i> Evaluation & Document
            </button>
            <button class="tab-btn" onclick="openTab(event, 'history_tab')">
                <i class='bx bx-history'></i> Previous Review History 
                <?php if ($history_result->num_rows > 0): ?>
                    <span style="background: rgba(0,0,0,0.1); font-size: 11px; padding: 2px 6px; border-radius: 10px; margin-left: 5px;">
                        <?= $history_result->num_rows ?>
                    </span>
                <?php endif; ?>
            </button>
        </div>

        <div id="evaluation_tab" class="tab-content active">
            <div style="margin-bottom: 25px;">
                <h3 style="margin-top: 0; color: #3C5B6F; margin-bottom: 10px;">Proposal Document</h3>
                <iframe src="<?= $data['file_path'] ?>" style="width:100%; height:600px; border:1px solid #ccc; border-radius: 5px;"></iframe>
            </div>

            <div class="form-box" style="box-shadow: none; border: 1px solid #eee; max-width: 100%;">
                <h3 style="margin-top: 0; margin-bottom: 15px;">Step 1: Select Decision</h3>
                
                <div class="selection-container">
                    <button type="button" class="selection-btn" onclick="selectAction('RECOMMEND', this)">
                        <i class='bx bx-check-circle'></i> Recommend
                    </button>
                    <button type="button" class="selection-btn" onclick="selectAction('AMENDMENT', this)">
                        <i class='bx bx-edit'></i> Request Amendment
                    </button>
                    <button type="button" class="selection-btn" onclick="selectAction('REJECT', this)">
                        <i class='bx bx-x-circle'></i> Reject
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
        </div>

        <div id="history_tab" class="tab-content">
            <?php if ($history_result->num_rows > 0): ?>
                
                <?php $history_result->data_seek(0); // Reset pointer ?>

                <h3 style="color: #3C5B6F; margin-top: 0; display: flex; align-items: center; gap: 10px;">
                    <i class='bx bx-history'></i> Past Review Records
                </h3>
                <p style="color: #666; margin-bottom: 20px;">Below is the timeline of previous reviews and decisions for this proposal.</p>

                <?php while($hist = $history_result->fetch_assoc()): ?>
                    <div style="background: white; border: 1px solid #ddd; border-left: 4px solid #3C5B6F; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                            <strong style="color: #153448; font-size: 15px;">
                                <i class='bx bx-user'></i> <?= htmlspecialchars($hist['reviewer_name']) ?>
                            </strong>
                            <span style="font-size: 13px; color: #888;">
                                <?= date('M d, Y h:i A', strtotime($hist['review_date'])) ?>
                            </span>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <?php 
                                $d = $hist['decision'];
                                $badge_style = "background: #e2e6ea; color: #333;"; // Default
                                if ($d == 'RECOMMEND') {
                                    $badge_style = "background: #d4edda; color: #155724; border: 1px solid #c3e6cb;";
                                } elseif ($d == 'REJECT' || $d == 'APPEAL_REJECTED') {
                                    $badge_style = "background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;";
                                } elseif ($d == 'AMENDMENT') {
                                    $badge_style = "background: #fff3cd; color: #856404; border: 1px solid #ffeeba;";
                                }
                            ?>
                            <?php if (!empty($d)): ?>
                                <span style="font-weight: 600; font-size: 11px; padding: 5px 12px; border-radius: 20px; text-transform: uppercase; display: inline-block; <?= $badge_style ?>">
                                    <?= $d ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div style="background: #f9f9f9; padding: 15px; border-radius: 6px; color: #444; line-height: 1.6;">
                            <?= nl2br(htmlspecialchars($hist['feedback'])) ?>
                        </div>

                        <?php if (!empty($hist['annotated_file'])): ?>
                            <div style="margin-top: 15px;">
                                <a href="<?= htmlspecialchars($hist['annotated_file']) ?>" target="_blank" 
                                   style="display: inline-flex; align-items: center; gap: 8px; color: #3C5B6F; font-weight: 600; text-decoration: none; padding: 8px 12px; background: #eef4f8; border-radius: 5px; transition: 0.2s;">
                                    <i class='bx bxs-file-pdf'></i> View Annotated PDF
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: #999;">
                    <i class='bx bx-info-circle' style="font-size: 48px; opacity: 0.5;"></i>
                    <p>No previous review history found for this proposal.</p>
                </div>
            <?php endif; ?>
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
        // TAB SWITCHING SCRIPT
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }

            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }

            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.className += " active";
        }

        // FORM ACTION SELECTION SCRIPT
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