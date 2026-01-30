<?php
// get_milestones.php - Helper file for loading milestones via AJAX
session_start();
require 'config.php';

header('Content-Type: application/json');

// 1. Security check (From both files, improved with HTTP 401)
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'researcher') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// 2. Input Validation (From get_milestones.php)
if (!isset($_GET['grant_id']) || empty($_GET['grant_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Grant ID is required']);
    exit();
}

$grant_id = intval($_GET['grant_id']);
$email = $_SESSION['email'];

if ($grant_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Grant ID']);
    exit();
}

try {
    // 3. Ownership Verification
    $verify = $conn->prepare("SELECT id FROM proposals WHERE id = ? AND researcher_email = ? AND status = 'APPROVED'");
    $verify->bind_param("is", $grant_id, $email);
    $verify->execute();
    $verify_result = $verify->get_result();

    if ($verify_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Grant not found or not accessible']);
        exit();
    }

    // 4. Fetch Milestones
    $query = $conn->prepare("SELECT id, title, description, target_date, completion_date, status FROM milestones WHERE grant_id = ? ORDER BY target_date ASC");
    $query->bind_param("i", $grant_id);
    $query->execute();
    $result = $query->get_result();

    $milestones = [];
    while ($row = $result->fetch_assoc()) {
        // Feature from get_milestones.php: Pre-format dates for the frontend
        $row['target_date_formatted'] = date('M d, Y', strtotime($row['target_date']));
        
        if ($row['completion_date']) {
            $row['completion_date_formatted'] = date('M d, Y', strtotime($row['completion_date']));
        } else {
            $row['completion_date_formatted'] = '-';
        }
        
        $milestones[] = $row;
    }

    http_response_code(200);
    echo json_encode($milestones);

} catch (Exception $e) {
    // Error logging (From get_milestones.php)
    error_log("Error fetching milestones: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching milestones']);
}
?>