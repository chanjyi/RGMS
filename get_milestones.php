<?php
// get_milestones.php - Helper file for loading milestones via AJAX
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'researcher') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['grant_id'])) {
    echo json_encode(['error' => 'Grant ID required']);
    exit();
}

$grant_id = intval($_GET['grant_id']);
$email = $_SESSION['email'];

// Verify this grant belongs to the researcher
$verify = $conn->prepare("SELECT id FROM proposals WHERE id = ? AND researcher_email = ? AND status = 'APPROVED'");
$verify->bind_param("is", $grant_id, $email);
$verify->execute();

if ($verify->get_result()->num_rows === 0) {
    echo json_encode(['error' => 'Grant not found']);
    exit();
}

// Fetch milestones for this grant
$query = $conn->prepare("SELECT id, title, description, target_date, completion_date, status FROM milestones WHERE grant_id = ? ORDER BY target_date ASC");
$query->bind_param("i", $grant_id);
$query->execute();
$result = $query->get_result();

$milestones = [];
while ($row = $result->fetch_assoc()) {
    $milestones[] = $row;
}

echo json_encode($milestones);
?>