<?php
session_start();
require 'config.php';

/* Security Check - Verify that the user is logged in and has admin privileges */
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    // Redirect non-admin users back to the login page
    header('Location: index.php');
    exit();
}

// Initialize variables to store feedback messages and their types
$message = "";
$msg_type = ""; // success | error

/* Handle approve / reject - Process form submissions for user approval or rejection */
if (isset($_POST['action'], $_POST['user_id'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    // Validate that the user ID is a valid positive integer
    if ($user_id <= 0) {
        $msg_type = "error";
        $message = "Invalid user id.";
    } else {
        // Handle user approval request
        if ($action === 'approve') {
            // Prepare a database query to update the user status to APPROVED
            $stmt = $conn->prepare("UPDATE users SET status = 'APPROVED' WHERE id = ?");
            $stmt->bind_param("i", $user_id);

            // Execute the update and provide feedback based on the result
            if ($stmt->execute()) {
                $msg_type = "success";
                $message = "User approved successfully.";
            } else {
                $msg_type = "error";
                $message = "Database error: " . $stmt->error;
            }
            $stmt->close();

        // Handle user rejection request
        } elseif ($action === 'reject') {
            // Prepare a database query to update the user status to REJECTED
            $stmt = $conn->prepare("UPDATE users SET status = 'REJECTED' WHERE id = ?");
            $stmt->bind_param("i", $user_id);

            // Execute the update and provide feedback based on the result
            if ($stmt->execute()) {
                $msg_type = "success";
                $message = "User rejected successfully.";
            } else {
                $msg_type = "error";
                $message = "Database error: " . $stmt->error;
            }
            $stmt->close();

        // Handle invalid action requests
        } else {
            $msg_type = "error";
            $message = "Invalid action.";
        }
    }
}

// Retrieve all pending users who are waiting for admin approval
$pending_q = $conn->query("
    SELECT id, name, email, role
    FROM users
    WHERE status = 'PENDING'
    ORDER BY id DESC
");

// Retrieve all users with approved or rejected status for history display
$history_q = $conn->query("
    SELECT id, name, email, role, status
    FROM users
    WHERE status IN ('APPROVED', 'REJECTED')
    ORDER BY id DESC
");

// Determine message box styles based on message type
$bg_color     = ($msg_type === 'error') ? '#f8d7da' : '#d4edda';
$text_color   = ($msg_type === 'error') ? '#721c24' : '#155724';
$border_color = ($msg_type === 'error') ? '#f5c6cb' : '#c3e6cb';
?>

