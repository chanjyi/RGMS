<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// This function records user activities into the activity_logs table for audit purposes
function log_activity(mysqli $conn, string $action, string $entity_type, ?int $entity_id = null, ?string $label = null, ?string $description = null): void
{
    // Get the current user's email and role from the session, or use 'unknown' if they're not available
    $actor_email = $_SESSION['email'] ?? 'unknown';
    $actor_role  = $_SESSION['role'] ?? 'unknown';

    // Attempt to capture the user's IP address, checking multiple sources to account for proxy servers
    $ip = null;
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Prepare the SQL statement to insert a new activity log record
    $sql = "INSERT INTO activity_logs
            (actor_email, actor_role, ip_address, action, entity_type, entity_id, label, description, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }

    // Bind the parameters to the prepared statement with their appropriate data types
    $stmt->bind_param(
        "sssssiss",
        $actor_email,
        $actor_role,
        $ip,
        $action,
        $entity_type,
        $entity_id,
        $label,
        $description
    );

    // Execute the query and close the statement
    $stmt->execute();
    $stmt->close();
}
