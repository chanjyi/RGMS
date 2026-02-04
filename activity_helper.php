<?php
// activity_helper.php
// Usage: require_once 'activity_helper.php'; then call log_activity($conn, ...);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Insert an activity log row into activity_logs.
 *
 * Table columns (your screenshot):
 * id, actor_email, actor_role, ip_address, action, entity_type, entity_id,
 * label, description, created_at
 */
function log_activity(mysqli $conn, string $action, string $entity_type, ?int $entity_id = null, ?string $label = null, ?string $description = null): void
{
    // Actor info from session (fallbacks if missing)
    $actor_email = $_SESSION['email'] ?? 'unknown';
    $actor_role  = $_SESSION['role'] ?? 'unknown';

    // Best effort IP capture (handles proxy)
    $ip = null;
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    $sql = "INSERT INTO activity_logs
            (actor_email, actor_role, ip_address, action, entity_type, entity_id, label, description, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // Don’t kill the page — logging failure should not break system
        return;
    }

    // Bind types: s s s s s i s s
    // entity_id is int and can be null
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

    $stmt->execute();
    $stmt->close();
}
