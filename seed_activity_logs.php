<?php
session_start();
require 'config.php';
require_once 'activity_helper.php';

// allow only admin
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Admin only.");
}

// Insert a few sample logs
log_activity($conn, "LOGIN", "SYSTEM", null, "Admin Login", "Admin logged in successfully");
log_activity($conn, "CREATE", "PROPOSAL", 12, "Create Proposal", "Created proposal #12");
log_activity($conn, "UPDATE", "USER", 5, "Update User", "Updated user #5 status");
log_activity($conn, "ASSIGN", "REVIEW", 20, "Assign Reviewer", "Assigned reviewer to proposal #20");
log_activity($conn, "LOGOUT", "SYSTEM", null, "Admin Logout", "Admin logged out");

echo "Seeded logs successfully. Now open admin_activity_logs.php";
