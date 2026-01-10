<?php
session_start();
require 'config.php';

// Verify HOD access
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

// Get HOD information
$hod_query = "SELECT id, department_id FROM users WHERE email = ? AND role = 'hod'";
$hod_stmt = $conn->prepare($hod_query);
$hod_stmt->bind_param("s", $_SESSION['email']);
$hod_stmt->execute();
$hod_result = $hod_stmt->get_result();
$hod_data = $hod_result->fetch_assoc();

if (!$hod_data) {
    echo json_encode(['success' => false, 'message' => 'HOD record not found']);
    exit();
}

$hod_id = $hod_data['id'];
$department_id = $hod_data['department_id'];

// Handle different actions
$action = $_POST['action'] ?? '';

if ($action === 'approve_top_tier') {
    try {
        $conn->begin_transaction();

        $proposal_ids = explode(',', $_POST['proposal_ids'] ?? '');
        $adjustments = json_decode($_POST['adjustments'] ?? '{}', true);

        // Get department budget
        $dept_query = "SELECT available_budget FROM departments WHERE id = ?";
        $dept_stmt = $conn->prepare($dept_query);
        $dept_stmt->bind_param("i", $department_id);
        $dept_stmt->execute();
        $dept_result = $dept_stmt->get_result();
        $dept_data = $dept_result->fetch_assoc();
        $available_budget = $dept_data['available_budget'] ?? 0;

        // Calculate total approved amount
        $total_approved = 0;
        foreach ($proposal_ids as $pid) {
            $pid = intval($pid);
            $approved_budget = $adjustments[$pid] ?? 0;
            $total_approved += $approved_budget;
        }

        // Check if exceeds budget
        if ($total_approved > $available_budget) {
            echo json_encode([
                'success' => false,
                'message' => 'Total approved budget ($' . number_format($total_approved, 2) . ') exceeds department budget ($' . number_format($available_budget, 2) . ')'
            ]);
            exit();
        }

        // Process each proposal
        foreach ($proposal_ids as $pid) {
            $pid = intval($pid);
            $approved_budget = $adjustments[$pid] ?? 0;

            // Get proposal details
            $prop_query = "SELECT * FROM proposals WHERE id = ?";
            $prop_stmt = $conn->prepare($prop_query);
            $prop_stmt->bind_param("i", $pid);
            $prop_stmt->execute();
            $prop_result = $prop_stmt->get_result();
            $proposal = $prop_result->fetch_assoc();

            if (!$proposal) continue;

            // Update proposal status to APPROVED
            $update_query = "UPDATE proposals SET status = 'APPROVED' WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $pid);
            $update_stmt->execute();

            // Create or update grant record if not exists
            $grant_query = "SELECT grantId FROM grant WHERE proposalId = ?";
            $grant_stmt = $conn->prepare($grant_query);
            $grant_stmt->bind_param("i", $pid);
            $grant_stmt->execute();
            $grant_result = $grant_stmt->get_result();

            if ($grant_result->num_rows === 0) {
                // Generate grant number
                $grant_number = 'GRT-' . date('Y') . '-' . str_pad($pid, 5, '0', STR_PAD_LEFT);

                // Create grant record
                $grant_insert = "INSERT INTO grant (proposalId, grantNumber, amountAllocated, amountSpent, amountRemaining, status)
                                 VALUES (?, ?, ?, 0, ?, 'Active')";
                $grant_insert_stmt = $conn->prepare($grant_insert);
                $grant_insert_stmt->bind_param("isdd", $pid, $grant_number, $approved_budget, $approved_budget);
                $grant_insert_stmt->execute();
                $grant_id = $conn->insert_id;
            } else {
                $grant = $grant_result->fetch_assoc();
                $grant_id = $grant['grantId'];

                // Update grant amount
                $grant_update = "UPDATE grant SET amountAllocated = ?, amountRemaining = ? WHERE grantId = ?";
                $grant_update_stmt = $conn->prepare($grant_update);
                $grant_update_stmt->bind_param("ddi", $approved_budget, $approved_budget, $grant_id);
                $grant_update_stmt->execute();
            }

            // Create grant allocation record
            $today = date('Y-m-d');
            $alloc_insert = "INSERT INTO grant_allocation (grantId, requestedBudget, approvedBudget, approvedBy, approvalDate)
                             VALUES (?, ?, ?, ?, ?)";
            $alloc_stmt = $conn->prepare($alloc_insert);
            $alloc_stmt->bind_param("iddis", $grant_id, $approved_budget, $approved_budget, $hod_id, $today);
            $alloc_stmt->execute();

            // Update tier assignment
            $tier_insert = "INSERT INTO hod_tier_assignment (proposalId, hod_id, tier, approved_budget, is_approved)
                            VALUES (?, ?, 'top', ?, 1)
                            ON DUPLICATE KEY UPDATE tier = 'top', approved_budget = ?, is_approved = 1";
            $tier_stmt = $conn->prepare($tier_insert);
            $tier_stmt->bind_param("iddd", $pid, $hod_id, $approved_budget, $approved_budget);
            $tier_stmt->execute();

            // Send notification to researcher
            $researcher_email = $proposal['researcher_email'];
            $notif_msg = "Final Decision: Your proposal '" . $proposal['title'] . "' has been APPROVED by the Head of Department.";
            $notif_insert = "INSERT INTO notifications (user_email, message, type) VALUES (?, ?, 'success')";
            $notif_stmt = $conn->prepare($notif_insert);
            $notif_stmt->bind_param("ss", $researcher_email, $notif_msg);
            $notif_stmt->execute();
        }

        // Deduct from department budget
        $new_budget = $available_budget - $total_approved;
        $budget_update = "UPDATE departments SET available_budget = ? WHERE id = ?";
        $budget_stmt = $conn->prepare($budget_update);
        $budget_stmt->bind_param("di", $new_budget, $department_id);
        $budget_stmt->execute();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => count($proposal_ids) . ' proposals approved successfully',
            'total_approved' => $total_approved,
            'remaining_budget' => $new_budget
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
elseif ($action === 'save_rubric') {
    try {
        $proposal_id = intval($_POST['proposal_id'] ?? 0);
        $outcome = intval($_POST['outcome_score'] ?? 0);
        $impact = intval($_POST['impact_score'] ?? 0);
        $alignment = intval($_POST['alignment_score'] ?? 0);
        $funding = intval($_POST['funding_score'] ?? 0);
        $hod_notes = $_POST['hod_notes'] ?? '';
        $approved_budget = floatval($_POST['approved_budget'] ?? 0);

        if ($proposal_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid proposal ID']);
            exit();
        }

        $total_score = $outcome + $impact + $alignment + $funding;

        // Insert or update rubric
        $rubric_insert = "INSERT INTO proposal_rubric 
                          (proposalId, hod_id, outcome_score, impact_score, alignment_score, funding_score, total_score, hod_notes)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE 
                          outcome_score = ?, impact_score = ?, alignment_score = ?, funding_score = ?, total_score = ?, hod_notes = ?";
        
        $rubric_stmt = $conn->prepare($rubric_insert);
        $rubric_stmt->bind_param(
            "iiiiiiisiiiiis",
            $proposal_id, $hod_id, $outcome, $impact, $alignment, $funding, $total_score, $hod_notes,
            $outcome, $impact, $alignment, $funding, $total_score, $hod_notes
        );
        $rubric_stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Rubric saved successfully']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
