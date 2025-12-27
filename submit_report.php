<?php
session_start();
require 'config.php';

if (isset($_POST['submit_report'])) {
    
    if (!isset($_SESSION['email'])) {
        die("Error: You are not logged in.");
    }
    
    $reviewer_email = $_SESSION['email']; 
    $researcher_email = $_POST['researcher_email'];
    $category = $_POST['category'];
    $details = $_POST['details'];
    $proposal_id = $_POST['proposal_id']; // <--- New Variable

    // 1. Insert into misconduct_reports
    $stmt = $conn->prepare("INSERT INTO misconduct_reports (reviewer_email, researcher_email, category, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $reviewer_email, $researcher_email, $category, $details);
    
    if ($stmt->execute()) {
        
        // 2. NEW STEP: Mark the Review as "Reported" (So it vanishes from Dashboard)
        // First, get reviewer's ID
        $u_q = $conn->query("SELECT id FROM users WHERE email = '$reviewer_email'");
        $reviewer_id = $u_q->fetch_assoc()['id'];

        // Update the review status
        $update_rev = $conn->prepare("UPDATE reviews SET status = 'Reported' WHERE proposal_id = ? AND reviewer_id = ?");
        $update_rev->bind_param("ii", $proposal_id, $reviewer_id);
        $update_rev->execute();

        // 3. Send Notification to Admin
        $admin_query = $conn->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
        if ($admin_query->num_rows > 0) {
            $admin_email = $admin_query->fetch_assoc()['email'];
            $msg = "ALERT: Misconduct reported by $reviewer_email against $researcher_email. Category: $category";
            
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, 'alert')");
            $notif_stmt->bind_param("ss", $admin_email, $msg);
            $notif_stmt->execute();
        }

        echo "<script>alert('Report submitted. This proposal has been removed from your pending list.'); window.location.href='reviewer_page.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>