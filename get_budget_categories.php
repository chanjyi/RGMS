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

// Fetch budget categories with allocated and spent amounts
$budget_query = "SELECT 
                    id,
                    category_name,
                    allocated_amount,
                    spent_amount,
                    (allocated_amount - spent_amount) AS remaining_amount
                 FROM budget_items 
                 WHERE grant_id = ?
                 ORDER BY category_name ASC";
                     
$b_stmt = $conn->prepare($budget_query);
$b_stmt->bind_param("i", $grant_id);
$b_stmt->execute();
$b_result = $b_stmt->get_result();

$budget_categories = [];
while ($budget = $b_result->fetch_assoc()) {
    $budget_categories[] = [
        'id' => $budget['id'],
        'category_name' => $budget['category_name'],
        'allocated_amount' => floatval($budget['allocated_amount']),
        'spent_amount' => floatval($budget['spent_amount']),
        'remaining_amount' => floatval($budget['remaining_amount'])
    ];
}

echo json_encode($budget_categories);

$b_stmt->close();
$verify_stmt->close();
$conn->close();
?>