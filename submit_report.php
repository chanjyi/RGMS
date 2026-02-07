<?php
session_start();
require 'config.php';
require 'activity_helper.php';

if (isset($_POST['submit_report'])) {
    
    if (!isset($_SESSION['email'])) {
        die("Error: You are not logged in.");
    }
    
$reviewer_email = $_SESSION['email']; 
    $researcher_email = $_POST['researcher_email'];
    $category = $_POST['category'];
    $details = $_POST['details'];
    $proposal_id = $_POST['proposal_id'];

    // 1. Insert into misconduct_reports
    $stmt = $conn->prepare("INSERT INTO misconduct_reports (proposal_id, reviewer_email, researcher_email, category, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $proposal_id, $reviewer_email, $researcher_email, $category, $details);
    
    if ($stmt->execute()) {
        
        // 2. GET REVIEWER ID (Add trim to be safe)
        $reviewer_email = $conn->real_escape_string($_SESSION['email']); // Sanitize
        $u_q = $conn->query("SELECT id FROM users WHERE email = '$reviewer_email'");
        
        if ($u_q->num_rows > 0) {
            $reviewer_id = $u_q->fetch_assoc()['id'];

            // 3. UPDATE REVIEW TABLE
            $update_rev = $conn->prepare("UPDATE reviews SET status = 'Reported', decision = 'REJECT', review_date = NOW() WHERE proposal_id = ? AND reviewer_id = ?");
            $update_rev->bind_param("ii", $proposal_id, $reviewer_id);
            $update_rev->execute();
            
            if (!$update_rev->execute()) {
                die("Error updating review status: " . $conn->error); // Debugging line
            }
        } else {
            die("Error: Reviewer account not found.");
        }

        // 3. UPDATE REVIEW TABLE (Fixes Date & Decision)
        // We set review_date = NOW() to fix the 1970 bug
        $update_rev = $conn->prepare("UPDATE reviews SET status = 'Reported', decision = 'REJECT', review_date = NOW() WHERE proposal_id = ? AND reviewer_id = ?");
        $update_rev->bind_param("ii", $proposal_id, $reviewer_id);
        $update_rev->execute();

        // 4. UPDATE PROPOSAL STATUS (Fixes HOD Status)
        // We set status = 'UNDER_INVESTIGATION' so it goes to Admin, not HOD
        $update_prop = $conn->prepare("UPDATE proposals SET status = 'UNDER_INVESTIGATION' WHERE id = ?");
        $update_prop->bind_param("i", $proposal_id);
        $update_prop->execute();

        // 5. NOTIFY ADMIN
        $admin_query = $conn->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
        if ($admin_query->num_rows > 0) {
            $admin_email = $admin_query->fetch_assoc()['email'];
            $msg = "ALERT: Misconduct reported by $reviewer_email against $researcher_email. Category: $category";
            
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, 'alert')");
            $notif_stmt->bind_param("ss", $admin_email, $msg);
            $notif_stmt->execute();
        }

        // ✅ ACTIVITY LOG (after everything succeeded)
        log_activity(
            $conn,
            "SUBMIT_MISCONDUCT_REPORT",
            "MISCONDUCT_REPORT",
            (int)$report_id,
            "Submit Misconduct Report",
            "Reviewer submitted misconduct report #$report_id for proposal #$proposal_id against $researcher_email. Category=$category. Proposal set to UNDER_INVESTIGATION and review marked REPORTED/REJECTED."
        );


        echo "<script>alert('Report submitted. This case has been escalated to Admin.'); window.location.href='reviewer_page.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>