<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in and is a researcher
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'researcher') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$email = $_SESSION['email'];

// Get grant_id from request
if (!isset($_GET['grant_id']) || empty($_GET['grant_id'])) {
    echo json_encode(['error' => 'Grant ID is required']);
    exit();
}

$grant_id = intval($_GET['grant_id']);

// Verify that this grant belongs to the researcher
$verify_stmt = $conn->prepare("SELECT id FROM proposals WHERE id = ? AND researcher_email = ? AND status = 'APPROVED'");
$verify_stmt->bind_param("is", $grant_id, $email);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['error' => 'Grant not found or does not belong to you']);
    exit();
}

// Fetch milestones for this grant
$milestones_query = "SELECT id, title, description, report_deadline, status 
                     FROM milestones 
                     WHERE grant_id = ? 
                     ORDER BY report_deadline ASC";
                      
$m_stmt = $conn->prepare($milestones_query);
$m_stmt->bind_param("i", $grant_id);
$m_stmt->execute();
$m_result = $m_stmt->get_result();

$milestones = [];
while ($milestone = $m_result->fetch_assoc()) {
    $milestones[] = [
        'id' => $milestone['id'],
        'title' => $milestone['title'],
        'description' => $milestone['description'],
        // REMOVED 'target_date' => $milestone['target_date'],
        'report_deadline' => $milestone['report_deadline'],
        'status' => $milestone['status']
    ];
}

echo json_encode($milestones);

$m_stmt->close();
$verify_stmt->close();
$conn->close();
?>