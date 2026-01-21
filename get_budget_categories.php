<?php
// get_budget_categories.php - Fetch budget categories for a specific grant
session_start();
require 'config.php';

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'researcher') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['grant_id']) || empty($_GET['grant_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Grant ID is required']);
    exit();
}

$grant_id = intval($_GET['grant_id']);
$email = $_SESSION['email'];

// Validate grant_id is a positive integer
if ($grant_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Grant ID']);
    exit();
}

try {
    // Verify this grant belongs to the researcher and is approved
    $verify = $conn->prepare("SELECT id FROM proposals WHERE id = ? AND researcher_email = ? AND status = 'APPROVED'");
    $verify->bind_param("is", $grant_id, $email);
    $verify->execute();
    $verify_result = $verify->get_result();

    if ($verify_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Grant not found or not accessible']);
        exit();
    }

    // Fetch budget categories for this grant
    $query = $conn->prepare("
        SELECT id, category, description, allocated_amount, spent_amount 
        FROM budget_items 
        WHERE proposal_id = ? 
        ORDER BY category ASC
    ");
    $query->bind_param("i", $grant_id);
    $query->execute();
    $result = $query->get_result();

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    http_response_code(200);
    echo json_encode($categories);

} catch (Exception $e) {
    error_log("Error fetching budget categories: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching budget categories']);
}
?>