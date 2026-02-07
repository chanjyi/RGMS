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
            $grant_query = "SELECT fund_id FROM fund WHERE proposal_id = ?";
            $grant_stmt = $conn->prepare($grant_query);
            $grant_stmt->bind_param("i", $pid);
            $grant_stmt->execute();
            $grant_result = $grant_stmt->get_result();

            if ($grant_result->num_rows === 0) {
                // Generate grant number
                $grant_number = 'GRT-' . date('Y') . '-' . str_pad($pid, 5, '0', STR_PAD_LEFT);

                // Create grant record
                $grant_insert = "INSERT INTO fund (proposal_id, grant_number, amount_allocated, amount_spent, amount_remaining, status)
                                 VALUES (?, ?, ?, 0, ?, 'Active')";
                $grant_insert_stmt = $conn->prepare($grant_insert);
                $grant_insert_stmt->bind_param("isdd", $pid, $grant_number, $approved_budget, $approved_budget);
                $grant_insert_stmt->execute();
                $grant_id = $conn->insert_id;
            } else {
                $grant = $grant_result->fetch_assoc();
                $grant_id = $grant['fund_id'];

                // Update grant amount
                $grant_update = "UPDATE fund SET amount_allocated = ?, amount_remaining = ? WHERE fund_id = ?";
                $grant_update_stmt = $conn->prepare($grant_update);
                $grant_update_stmt->bind_param("ddi", $approved_budget, $approved_budget, $grant_id);
                $grant_update_stmt->execute();
            }

            // Create grant allocation record
            $today = date('Y-m-d');
            $alloc_insert = "INSERT INTO grant_allocation (fund_id, requested_budget, approved_budget, approved_by, approval_date)
                             VALUES (?, ?, ?, ?, ?)";
            $alloc_stmt = $conn->prepare($alloc_insert);
            $alloc_stmt->bind_param("iddis", $grant_id, $approved_budget, $approved_budget, $hod_id, $today);
            $alloc_stmt->execute();

            // Update tier assignment
            $tier_insert = "INSERT INTO hod_tier_assignment (proposal_id, hod_id, tier, approved_budget, is_approved)
                            VALUES (?, ?, 'top', ?, 1)
                            ON DUPLICATE KEY UPDATE tier = 'top', approved_budget = ?, is_approved = 1";
            $tier_stmt = $conn->prepare($tier_insert);
            $tier_stmt->bind_param("iidd", $pid, $hod_id, $approved_budget, $approved_budget);
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
        
        // Get weightages from frontend to calculate weighted total_score
        $weight_outcome = floatval($_POST['weightage_outcome'] ?? 1.0);
        $weight_impact = floatval($_POST['weightage_impact'] ?? 1.0);
        $weight_alignment = floatval($_POST['weightage_alignment'] ?? 1.0);
        $weight_funding = floatval($_POST['weightage_funding'] ?? 1.0);

        if ($proposal_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid proposal ID']);
            exit();
        }

        // Get the proposal details to check requested budget
        $prop_query = "SELECT budget_requested FROM proposals WHERE id = ?";
        $prop_stmt = $conn->prepare($prop_query);
        $prop_stmt->bind_param("i", $proposal_id);
        $prop_stmt->execute();
        $prop_result = $prop_stmt->get_result();
        $proposal = $prop_result->fetch_assoc();

        if (!$proposal) {
            echo json_encode(['success' => false, 'message' => 'Proposal not found']);
            exit();
        }

        $requested_budget = floatval($proposal['budget_requested'] ?? 0);

        // Validation: Approved budget must be less than or equal to requested amount
        if ($approved_budget > $requested_budget) {
            echo json_encode([
                'success' => false, 
                'message' => 'Approved budget cannot exceed the requested amount ($' . number_format($requested_budget, 2) . ')'
            ]);
            exit();
        }

        // Calculate weighted total_score using the same weightages as frontend
        // Use floatval to ensure decimal precision is maintained
        $total_score = ($outcome * $weight_outcome) + 
                       ($impact * $weight_impact) + 
                       ($alignment * $weight_alignment) + 
                       ($funding * $weight_funding);
        // Keep one decimal place for precision
        $total_score = round($total_score, 1);

        // Check if rubric record exists first
        $check_query = "SELECT proposal_id FROM proposal_rubric WHERE proposal_id = ? AND hod_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $proposal_id, $hod_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $rubric_exists = $check_result->num_rows > 0;

        if ($rubric_exists) {
            // Update existing rubric record
            $rubric_update = "UPDATE proposal_rubric 
                              SET outcome_score = ?, impact_score = ?, alignment_score = ?, funding_score = ?, total_score = ?, hod_notes = ?, is_evaluated = 1,
                                  weight_outcome = ?, weight_impact = ?, weight_alignment = ?, weight_funding = ?
                              WHERE proposal_id = ? AND hod_id = ?";
            
            $update_stmt = $conn->prepare($rubric_update);
            if (!$update_stmt) {
                echo json_encode(['success' => false, 'message' => 'Error preparing update: ' . $conn->error]);
                exit();
            }

            $update_stmt->bind_param(
                "iiiidsddddii",
                $outcome, $impact, $alignment, $funding, $total_score, $hod_notes,
                $weight_outcome, $weight_impact, $weight_alignment, $weight_funding,
                $proposal_id, $hod_id
            );
            
            if (!$update_stmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Error executing update: ' . $update_stmt->error]);
                exit();
            }
        } else {
            // Insert new rubric record
            $rubric_insert = "INSERT INTO proposal_rubric 
                              (proposal_id, hod_id, outcome_score, impact_score, alignment_score, funding_score, total_score, hod_notes, is_evaluated, weight_outcome, weight_impact, weight_alignment, weight_funding)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)";
            
            $insert_stmt = $conn->prepare($rubric_insert);
            if (!$insert_stmt) {
                echo json_encode(['success' => false, 'message' => 'Error preparing insert: ' . $conn->error]);
                exit();
            }

            $insert_stmt->bind_param(
                "iiiiidsdddd",
                $proposal_id, $hod_id, $outcome, $impact, $alignment, $funding, $total_score, $hod_notes,
                $weight_outcome, $weight_impact, $weight_alignment, $weight_funding
            );
            
            if (!$insert_stmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Error executing insert: ' . $insert_stmt->error]);
                exit();
            }
        }

        // Optionally update proposal approved_budget to reflect HOD input
        if ($approved_budget >= 0) {
            $upd = $conn->prepare("UPDATE proposals SET approved_budget = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param("di", $approved_budget, $proposal_id);
                $upd->execute();
            }
        }

        echo json_encode(['success' => true, 'message' => 'Rubric saved successfully']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
else {
    // Extra action: get_rubric
    if ($action === 'get_rubric') {
        try {
            $proposal_id = intval($_POST['proposal_id'] ?? 0);
            if ($proposal_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid proposal ID']);
                exit();
            }

            $rubric = null;
            $stmt = $conn->prepare("SELECT outcome_score, impact_score, alignment_score, funding_score, total_score, hod_notes, weight_outcome, weight_impact, weight_alignment, weight_funding FROM proposal_rubric WHERE proposal_id = ? AND hod_id = ?");
            $stmt->bind_param("ii", $proposal_id, $hod_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $rubric = $res->fetch_assoc();
            }

            $approved_budget = null;
            $pstmt = $conn->prepare("SELECT approved_budget FROM proposals WHERE id = ?");
            $pstmt->bind_param("i", $proposal_id);
            $pstmt->execute();
            $pres = $pstmt->get_result();
            if ($pres && ($prow = $pres->fetch_assoc())) {
                $approved_budget = floatval($prow['approved_budget']);
            }

            // Get annotated proposal file from reviewer
            $annotated_file = null;
            $astmt = $conn->prepare("SELECT annotated_file FROM reviews WHERE proposal_id = ? ORDER BY review_date DESC LIMIT 1");
            $astmt->bind_param("i", $proposal_id);
            $astmt->execute();
            $ares = $astmt->get_result();
            if ($ares && ($arow = $ares->fetch_assoc())) {
                $annotated_file = $arow['annotated_file'];
            }

            echo json_encode(['success' => true, 'rubric' => $rubric, 'approved_budget' => $approved_budget, 'annotated_proposal_file' => $annotated_file]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'save_tier_assignments') {
        try {
            $assignments = json_decode($_POST['assignments'] ?? '{}', true);

            if (empty($assignments)) {
                echo json_encode(['success' => false, 'message' => 'No tier assignments provided']);
                exit();
            }

            foreach ($assignments as $proposal_id => $tier) {
                $proposal_id = intval($proposal_id);
                
                // Insert or update tier assignment
                $tier_insert = "INSERT INTO hod_tier_assignment (proposal_id, hod_id, tier)
                                VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE tier = ?";
                $tier_stmt = $conn->prepare($tier_insert);
                $tier_stmt->bind_param("isis", $proposal_id, $hod_id, $tier, $tier);
                $tier_stmt->execute();
            }

            echo json_encode(['success' => true, 'message' => 'Tier assignments saved successfully']);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'reject_bottom_tier') {
        try {
            $conn->begin_transaction();

            $proposal_ids = explode(',', $_POST['proposal_ids'] ?? '');

            foreach ($proposal_ids as $pid) {
                $pid = intval($pid);

                // Get proposal details
                $prop_query = "SELECT * FROM proposals WHERE id = ?";
                $prop_stmt = $conn->prepare($prop_query);
                $prop_stmt->bind_param("i", $pid);
                $prop_stmt->execute();
                $prop_result = $prop_stmt->get_result();
                $proposal = $prop_result->fetch_assoc();

                if (!$proposal) continue;

                // Update proposal status to REJECTED and set approved_budget to 0
                $update_query = "UPDATE proposals SET status = 'REJECTED', approved_budget = 0 WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("i", $pid);
                $update_stmt->execute();

                // Update tier assignment as rejected
                $tier_insert = "INSERT INTO hod_tier_assignment (proposal_id, hod_id, tier, is_approved)
                                VALUES (?, ?, 'bottom', 0)
                                ON DUPLICATE KEY UPDATE tier = 'bottom', is_approved = 0";
                $tier_stmt = $conn->prepare($tier_insert);
                $tier_stmt->bind_param("ii", $pid, $hod_id);
                $tier_stmt->execute();

                // Send notification to researcher
                $researcher_email = $proposal['researcher_email'];
                $notif_msg = "Final Decision: Your proposal '" . $proposal['title'] . "' has been REJECTED by the Head of Department.";
                $notif_insert = "INSERT INTO notifications (user_email, message, type) VALUES (?, ?, 'warning')";
                $notif_stmt = $conn->prepare($notif_insert);
                $notif_stmt->bind_param("ss", $researcher_email, $notif_msg);
                $notif_stmt->execute();
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => count($proposal_ids) . ' proposals rejected successfully'
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>
