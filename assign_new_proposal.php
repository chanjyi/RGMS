<?php
// Start the user session to track login status and user information
session_start();
require 'config.php';
require 'activity_helper.php';


// Verify that the user is logged in and has admin privileges, otherwise redirect to login
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

// Initialize message variables to display feedback to the user
$message  = "";
$msg_type = ""; // success | error

// Handle the assignment of a proposal to a reviewer
if (isset($_POST['assign_proposal'])) {
    $prop_id     = (int)($_POST['proposal_id'] ?? 0);
    $reviewer_id = (int)($_POST['reviewer_id'] ?? 0);

    if ($prop_id <= 0 || $reviewer_id <= 0) {
        $message  = "Error: Invalid proposal or reviewer.";
        $msg_type = "error";
    } else {
        // Insert a new review record linking the proposal and reviewer
        $stmt = $conn->prepare("
            INSERT INTO reviews (proposal_id, reviewer_id, status, assigned_date, type)
            VALUES (?, ?, 'Pending', NOW(), 'Proposal')
        ");
        $stmt->bind_param("ii", $prop_id, $reviewer_id);

        if ($stmt->execute()) {
            // Update the proposal status to indicate it has been assigned
            $up = $conn->prepare("UPDATE proposals SET status = 'ASSIGNED' WHERE id = ?");
            $up->bind_param("i", $prop_id);
            $up->execute();

            // Retrieve the reviewer's email address to send notification
            $rev_q = $conn->prepare("SELECT email FROM users WHERE id = ?");
            $rev_q->bind_param("i", $reviewer_id);
            $rev_q->execute();
            $rev_res = $rev_q->get_result();

            if ($rev_res && $rev_res->num_rows > 0) {
                // Send a notification to the reviewer about the new assignment
                $rev_email = $rev_res->fetch_assoc()['email'];
                $notif_msg = "New Assignment: You have been assigned a proposal.";

                $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
                $notif->bind_param("ss", $rev_email, $notif_msg);
                $notif->execute();
            }

            log_activity(
                $conn,
                "ASSIGN_REVIEWER",                      // action
                "PROPOSAL",                             // entity_type
                (int)$prop_id,                          // entity_id (int)
                "Assign Proposal",                      // label
                "Assigned proposal #$prop_id ($proposal_title) to reviewer_id=$reviewer_id"
            );



            header("Location: assign_new_proposal.php?success=proposal&tab=assignProposal");
            exit();
        } else {
            $message  = "Database Error (Assign Proposal): " . $stmt->error;
            $msg_type = "error";
        }
    }
}

// Handle the assignment of an appeal case to a reviewer
if (isset($_POST['assign_appeal'])) {
    $prop_id     = (int)($_POST['proposal_id'] ?? 0);
    $reviewer_id = (int)($_POST['reviewer_id'] ?? 0);

    if ($prop_id <= 0 || $reviewer_id <= 0) {
        $message  = "Error: Invalid appeal case or reviewer.";
        $msg_type = "error";
    } else {
        // Insert a new review record for the appeal with type set to 'Appeal'
        $stmt = $conn->prepare("
            INSERT INTO reviews (proposal_id, reviewer_id, status, assigned_date, type)
            VALUES (?, ?, 'Pending', NOW(), 'Appeal')
        ");
        $stmt->bind_param("ii", $prop_id, $reviewer_id);

        if ($stmt->execute()) {
            // Update the proposal status to remove it from the appealed list
            $up = $conn->prepare("UPDATE proposals SET status = 'ASSIGNED' WHERE id = ?");
            $up->bind_param("i", $prop_id);
            $up->execute();

            // Retrieve the reviewer's email address to send notification
            $rev_q = $conn->prepare("SELECT email FROM users WHERE id = ?");
            $rev_q->bind_param("i", $reviewer_id);
            $rev_q->execute();
            $rev_res = $rev_q->get_result();

            if ($rev_res && $rev_res->num_rows > 0) {
                // Send a notification to the reviewer about the new appeal assignment
                $rev_email = $rev_res->fetch_assoc()['email'];
                $notif_msg = "New Assignment: You have been assigned an appeal case.";

                $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
                $notif->bind_param("ss", $rev_email, $notif_msg);
                $notif->execute();
            }

            /log_activity(
                $conn,
                "ASSIGN_REVIEWER",                      // action
                "APPEAL",                               // entity_type
                (int)$prop_id,                          // entity_id (int)
                "Assign Appeal Case",                   // label
                "Assigned appeal case #$prop_id ($proposal_title) to reviewer_id=$reviewer_id"
            );



            header("Location: assign_new_proposal.php?success=appeal&tab=assignAppeal");
            exit();
        } else {
            $message  = "Database Error (Assign Appeal): " . $stmt->error;
            $msg_type = "error";
        }
    }
}

// Display success message if assignment was completed successfully
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'proposal') {
        $message  = "Proposal assigned successfully!";
        $msg_type = "success";
    } elseif ($_GET['success'] === 'appeal') {
        $message  = "Appeal case assigned successfully!";
        $msg_type = "success";
    }
}

// Fetch all reviewers from the database to display in dropdown
$reviewers_rs = $conn->query("SELECT id, name, email FROM users WHERE role = 'reviewer' ORDER BY name ASC");

// Fetch all proposals with submitted status that are waiting to be assigned
$proposals_rs = $conn->query("
    SELECT id, title, researcher_email
    FROM proposals
    WHERE status = 'SUBMITTED'
    ORDER BY id DESC
");

// Fetch all appeal cases that are waiting to be assigned to a reviewer
$appeals_rs = $conn->query("
    SELECT id, title, researcher_email
    FROM proposals
    WHERE status = 'APPEALED'
    ORDER BY id DESC
");

// Retrieve the assignment history showing all previous assignments with review details
$history_rs = $conn->query("
    SELECT
        r.id               AS review_id,
        r.type             AS assign_type,
        r.status           AS review_status,
        r.assigned_date    AS assigned_date,
        p.id               AS proposal_id,
        p.title            AS proposal_title,
        p.researcher_email AS researcher_email,
        u.name             AS reviewer_name,
        u.email            AS reviewer_email
    FROM reviews r
    JOIN proposals p ON p.id = r.proposal_id
    JOIN users u     ON u.id = r.reviewer_id
    ORDER BY r.assigned_date DESC
    LIMIT 200
");

// Set alert colors based on message type (error or success)
$bg_color     = ($msg_type === 'error') ? '#f8d7da' : '#d4edda';
$text_color   = ($msg_type === 'error') ? '#721c24' : '#155724';
$border_color = ($msg_type === 'error') ? '#f5c6cb' : '#c3e6cb';

// Determine which tab to display based on URL parameter or default to assign proposal tab
$defaultTab = $_GET['tab'] ?? 'assignProposal';
?>
