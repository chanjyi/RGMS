<?php
function logActivity($conn, $actor_email, $actor_role, $action, $entity_type, $entity_id = null, $label = null, $description = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO activity_logs
        (actor_email, actor_role, ip_address, action, entity_type, entity_id, label, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
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
}
