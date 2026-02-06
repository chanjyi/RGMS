<?php
session_start();
require 'config.php';

// Verify HOD access
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}

// Get HOD's department information
$hod_query = "SELECT id, department_id FROM users WHERE email = ? AND role = 'hod'";
$hod_stmt = $conn->prepare($hod_query);
$hod_stmt->bind_param("s", $_SESSION['email']);
$hod_stmt->execute();
$hod_result = $hod_stmt->get_result();
$hod_data = $hod_result->fetch_assoc();
$department_id = $hod_data['department_id'] ?? null;
$hod_id = $hod_data['id'] ?? null;

// Get all proposals for this HOD's department that are in RECOMMEND status
$proposal_query = "SELECT p.*, 
                                                     u.name AS researcher_name,
                                                     COALESCE(pr.is_evaluated, 0) AS is_evaluated,
                                                     COALESCE(pr.total_score, 0) AS total_score,
                                                     (SELECT rr.feedback
                                                            FROM reviews rr
                                                         WHERE rr.proposal_id = p.id
                                                             AND rr.decision = 'RECOMMEND'
                                                             AND (rr.type IS NULL OR rr.type = 'Proposal')
                                                         ORDER BY rr.review_date DESC
                                                         LIMIT 1) AS reviewer_feedback
                                        FROM proposals p 
                                        LEFT JOIN users u ON p.researcher_email = u.email 
                                        LEFT JOIN (
                                                SELECT proposal_id, is_evaluated, total_score
                                                FROM proposal_rubric
                                                WHERE hod_id = ?
                                        ) pr ON p.id = pr.proposal_id
                                        WHERE EXISTS (
                                                SELECT 1 FROM reviews r
                                                WHERE r.proposal_id = p.id
                                                    AND r.decision = 'RECOMMEND'
                                                    AND (r.type IS NULL OR r.type = 'Proposal')
                                        )
                                            AND (p.status IS NULL OR p.status = '')
                                        ORDER BY COALESCE(pr.is_evaluated, 0) ASC, (p.priority = 'HIGH') DESC, p.created_at ASC";
// $proposal_query = "SELECT * FROM proposals WHERE id = ?";
$proposal_stmt = $conn->prepare($proposal_query);
$proposal_stmt->bind_param("i", $hod_id);
$proposal_stmt->execute();
$proposal_result = $proposal_stmt->get_result();

$proposals = [];
while ($row = $proposal_result->fetch_assoc()) {
    $proposals[] = $row;
}

// Get department balance (you may need to adjust based on your actual schema)
$balance_query = "SELECT available_budget FROM departments WHERE id = ?";
$balance_stmt = $conn->prepare($balance_query);
$balance_stmt->bind_param("i", $department_id);
$balance_stmt->execute();
$balance_result = $balance_stmt->get_result();
$balance_data = $balance_result->fetch_assoc();
$department_balance = $balance_data['available_budget'] ?? 0;

// ============ APPEAL CASES HANDLING ============
$appeal_banner = ['type' => '', 'text' => ''];

// Handle appeal decisions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appeal_action'])) {
    $action = $_POST['appeal_action']; // approve | reject
    $proposal_id = intval($_POST['proposal_id'] ?? 0);
    $appeal_id = intval($_POST['appeal_id'] ?? 0);

    if ($proposal_id > 0 && $appeal_id > 0) {
        // Pull key proposal info for notifications
        $info_stmt = $conn->prepare("SELECT title, researcher_email FROM proposals WHERE id = ? LIMIT 1");
        $info_stmt->bind_param("i", $proposal_id);
        $info_stmt->execute();
        $info = $info_stmt->get_result()->fetch_assoc();

        if ($info) {
            try {
                $conn->begin_transaction();

                if ($action === 'approve') {
                    // Mark appeal approved and send for reassignment
                    $upd_prop = $conn->prepare("UPDATE proposals SET status = 'PENDING_REASSIGNMENT', priority = 'High' WHERE id = ?");
                    $upd_prop->bind_param("i", $proposal_id);

                    $upd_appeal = $conn->prepare("UPDATE appeal_requests SET status = 'APPROVED' WHERE id = ?");
                    $upd_appeal->bind_param("i", $appeal_id);

                    if (!$upd_prop->execute()) {
                        throw new Exception('Unable to update proposal status: ' . $upd_prop->error);
                    }
                    if (!$upd_appeal->execute()) {
                        throw new Exception('Unable to update appeal status: ' . $upd_appeal->error);
                    }

                    // Notify researcher
                    $msg = "Appeal Update: The HOD accepted your appeal for '{$info['title']}'. The proposal will be reassigned to a new reviewer.";
                    $notif = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, 'info')");
                    $notif->bind_param("ss", $info['researcher_email'], $msg);
                    if (!$notif->execute()) {
                        throw new Exception('Unable to send notification: ' . $notif->error);
                    }

                    $conn->commit();
                    $appeal_banner = ['type' => 'success', 'text' => 'Appeal approved and sent to admin for reassignment.'];
                } elseif ($action === 'reject') {
                    // Uphold rejection
                    $upd_prop = $conn->prepare("UPDATE proposals SET status = 'APPEAL_REJECTED' WHERE id = ?");
                    $upd_prop->bind_param("i", $proposal_id);

                    $upd_appeal = $conn->prepare("UPDATE appeal_requests SET status = 'REJECTED' WHERE id = ?");
                    $upd_appeal->bind_param("i", $appeal_id);

                    if (!$upd_prop->execute()) {
                        throw new Exception('Unable to update proposal status: ' . $upd_prop->error);
                    }
                    if (!$upd_appeal->execute()) {
                        throw new Exception('Unable to update appeal status: ' . $upd_appeal->error);
                    }

                    $msg = "Appeal Update: The HOD upheld the original decision for '{$info['title']}'.";
                    $notif = $conn->prepare("INSERT INTO notifications (user_email, message, type) VALUES (?, ?, 'warning')");
                    $notif->bind_param("ss", $info['researcher_email'], $msg);
                    if (!$notif->execute()) {
                        throw new Exception('Unable to send notification: ' . $notif->error);
                    }

                    $conn->commit();
                    $appeal_banner = ['type' => 'error', 'text' => 'Appeal dismissed. Researcher notified.'];
                }
            } catch (Exception $e) {
                $conn->rollback();
                $appeal_banner = ['type' => 'error', 'text' => 'Error processing appeal: ' . $e->getMessage()];
            }
        }
    } else {
        $appeal_banner = ['type' => 'error', 'text' => 'Missing appeal identifiers.'];
    }
}

// Fetch pending appeal cases with latest reviewer feedback and HOD rubric
$appeals_sql = "
    SELECT 
        p.id AS proposal_id,
        p.title,
        p.researcher_email,
        p.file_path,
        p.created_at,
        p.priority,
        p.budget_requested,
        ar.id AS appeal_id,
        ar.justification,
        ar.submitted_at,
        rv.feedback AS reviewer_feedback,
        rv.decision AS reviewer_decision,
        rv.review_date,
        rv.annotated_file,
        rv.reviewer_name,
        pr.total_score,
        pr.hod_notes
    FROM appeal_requests ar
    JOIN proposals p ON ar.proposal_id = p.id
    LEFT JOIN (
        SELECT r1.*, u.name AS reviewer_name
        FROM reviews r1
        LEFT JOIN users u ON r1.reviewer_id = u.id
        WHERE (r1.type IS NULL OR r1.type = 'Proposal')
          AND r1.id = (
              SELECT r2.id FROM reviews r2
              WHERE r2.proposal_id = r1.proposal_id AND (r2.type IS NULL OR r2.type = 'Proposal')
              ORDER BY r2.review_date DESC, r2.id DESC LIMIT 1
          )
    ) rv ON rv.proposal_id = p.id
    LEFT JOIN proposal_rubric pr ON pr.proposal_id = p.id AND pr.hod_id = ?
    WHERE ar.status = 'PENDING'
    ORDER BY ar.submitted_at DESC
";

$appeals_stmt = $conn->prepare($appeals_sql);
$appeals_stmt->bind_param("i", $hod_id);
$appeals_stmt->execute();
$appeals = $appeals_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Management - RGMS</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/hod_pages.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <section class="home-section">
        <div class="welcome-text">
            Proposal Management & Prioritization
        </div>
        <hr style="opacity: 0.3; margin: 20px 0;">

        <!-- Tab Navigation -->
        <div style="margin-bottom: 0;">
            <button class="tab-btn active" onclick="openTab(event, 'proposal-review')">
                <i class='bx bx-file-blank'></i> Proposal Review
            </button>
            <button class="tab-btn" onclick="openTab(event, 'tier-ranking')">
                <i class='bx bx-bar-chart'></i> Tier Ranking & Approval
            </button>
            <button class="tab-btn" onclick="openTab(event, 'appeal-cases')">
                <i class='bx bx-message-square-dots'></i> Appeal Cases
            </button>
        </div>

        <!-- TAB 1: PROPOSAL REVIEW -->
        <div id="proposal-review" class="tab-content active">
            <!-- Rubric Weightage Customization Section -->
            <div class="weightage-customization-section">
                <div class="weightage-header">
                    <i class='bx bx-slider'></i> Rubric Scoring Weightage
                    <p class="weightage-message">⚠️ You are advised to set the weightage before starting the evaluation.</p>
                </div>
                <div class="weightage-controls">
                    <div class="weightage-item">
                        <label>Potential Research Outcome</label>
                        <div class="weightage-slider-group">
                            <input type="range" id="weightOutcome" class="weightage-slider" min="0.5" max="5" step="0.5" value="1" 
                                   oninput="updateWeightageDisplay('outcome', this.value)">
                            <span id="weightOutcomeValue" class="weightage-value">1.0</span>
                        </div>
                    </div>
                    <div class="weightage-item">
                        <label>Research Impact</label>
                        <div class="weightage-slider-group">
                            <input type="range" id="weightImpact" class="weightage-slider" min="0.5" max="5" step="0.5" value="1" 
                                   oninput="updateWeightageDisplay('impact', this.value)">
                            <span id="weightImpactValue" class="weightage-value">1.0</span>
                        </div>
                    </div>
                    <div class="weightage-item">
                        <label>Strategic Alignment</label>
                        <div class="weightage-slider-group">
                            <input type="range" id="weightAlignment" class="weightage-slider" min="0.5" max="5" step="0.5" value="1" 
                                   oninput="updateWeightageDisplay('alignment', this.value)">
                            <span id="weightAlignmentValue" class="weightage-value">1.0</span>
                        </div>
                    </div>
                    <div class="weightage-item">
                        <label>Funding Constraints</label>
                        <div class="weightage-slider-group">
                            <input type="range" id="weightFunding" class="weightage-slider" min="0.5" max="5" step="0.5" value="1" 
                                   oninput="updateWeightageDisplay('funding', this.value)">
                            <span id="weightFundingValue" class="weightage-value">1.0</span>
                        </div>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button class="btn-confirm-weightage" onclick="confirmWeightage()" style="padding: 8px 20px;">
                        <i class='bx bx-check'></i> Confirm Weightage
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="proposal-filter-bar">
                <div class="filter-group">
                    <label class="filter-label">Filters:</label>
                    <div class="filter-checkboxes">
                        <label class="filter-checkbox">
                            <input type="checkbox" id="filterHighPriority" value="high" onchange="applyFilters()">
                            <span>High Priority</span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" id="filterStandard" value="standard" onchange="applyFilters()">
                            <span>Standard</span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" id="filterEvaluated" value="evaluated" onchange="applyFilters()">
                            <span>Evaluated</span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" id="filterPendingEvaluation" value="PendingEvaluation" onchange="applyFilters()">
                            <span>Pending Evaluation</span>
                        </label>
                    </div>
                </div>
                <div class="filter-group">
                    <input type="text" id="searchFilter" class="filter-input" placeholder="Search by title/researcher" onkeyup="applyFilters()">
                </div>
            </div>

            <!-- Available Proposals Section -->
            <div class="available-proposals-section">
                <div class="available-header">
                    <i class='bx bx-list-ul'></i> Recommended Proposals by Reviewers
                    <span id="proposalCount" class="proposal-count">0 proposals</span>
                </div>
                <div id="availableProposals" class="available-proposals-container">
                    <div class="empty-message">No proposals available for review</div>
                </div>
            </div>
        </div>

        <!-- TAB 2: TIER RANKING & APPROVAL -->
        <div id="tier-ranking" class="tab-content">
            <!-- Budget Summary Section -->
            <div class="budget-summary">
                <div class="budget-summary-title">
                    <i class='bx bx-wallet'></i> Budget Summary for Top Tier Approvals
                </div>
                <div class="budget-row">
                    <span>Department Budget Balance:</span>
                    <span id="deptBalance" style="font-weight: 700;">RM<?= number_format($department_balance, 2) ?></span>
                </div>
                <div class="budget-row">
                    <span>Total Top Tier Approved Amount:</span>
                    <span id="topTierTotal" style="font-weight: 700;">RM0.00</span>
                </div>
                <div class="budget-row">
                    <span>Remaining Budget:</span>
                    <span id="remainingBudget" style="font-weight: 700; color: #1b5e20;">RM<?= number_format($department_balance, 2) ?></span>
                </div>
                <div id="budgetWarning" class="budget-warning" style="display: none;">
                    <i class='bx bx-exclamation-circle'></i> 
                    Warning: Total approved amount exceeds department budget! Please reduce allocations.
                </div>
            </div>

            <!-- Tier List Container -->
            <div class="tier-list-container">
                <div class="tier-list-wrapper">
                    <!-- TOP TIER -->
                    <div class="tier-section">
                        <div class="tier-label tier-top">
                            <span><i class='bx bx-crown'></i> TOP TIER</span>
                            <span class="tier-budget" id="topBudget">RM0.00</span>
                        </div>
                        <div class="tier-items" id="topTier" data-tier="top" ondrop="allowDrop(event)" ondragover="dragOver(event)" ondragleave="dragLeave(event)">
                            <div class="empty-message">Drag proposals here</div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; margin-top: 12px;">
                            <button id="approveAllBtn" class="btn-approve-all" onclick="approveAllTopTier()" disabled>
                                <i class='bx bx-check-circle'></i> Approve All
                            </button>
                        </div>
                    </div>

                    <!-- MIDDLE TIER -->
                    <div class="tier-section">
                        <div class="tier-label tier-middle">
                            <span><i class='bx bx-trending-up'></i> MIDDLE TIER</span>
                            <span class="tier-budget" id="middleBudget">RM0.00</span>
                        </div>
                        <div class="tier-items" id="middleTier" data-tier="middle" ondrop="allowDrop(event)" ondragover="dragOver(event)" ondragleave="dragLeave(event)">
                            <div class="empty-message">Drag proposals here</div>
                        </div>
                    </div>

                    <!-- BOTTOM TIER -->
                    <div class="tier-section">
                        <div class="tier-label tier-bottom">
                            <span><i class='bx bx-trending-down'></i> BOTTOM TIER</span>
                            <span class="tier-budget" id="bottomBudget">RM0.00</span>
                        </div>
                        <div class="tier-items" id="bottomTier" data-tier="bottom" ondrop="allowDrop(event)" ondragover="dragOver(event)" ondragleave="dragLeave(event)">
                            <div class="empty-message">Drag proposals here</div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; margin-top: 12px;">
                            <button id="rejectAllBtn" class="btn-reject-all" onclick="rejectAllBottomTier()" disabled>
                                <i class='bx bx-x-circle'></i> Reject All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn-reset" onclick="resetTiers()">
                    <i class='bx bx-reset'></i> Reset Tiers
                </button>
                <button class="btn-save-tiers" onclick="saveTierAssignments()">
                    <i class='bx bx-save'></i> Save
                </button>
            </div>
        </div>

        <!-- TAB 3: APPEAL CASES -->
        <div id="appeal-cases" class="tab-content">
            <div class="welcome-text" style="margin-top: 0;">
                <i class='bx bx-message-square-dots'></i>
                Appeal Cases Management
            </div>

            <?php if (!empty($appeal_banner['text'])): ?>
                <div class="alert <?= $appeal_banner['type'] === 'success' ? 'success' : 'error' ?>">
                    <?= htmlspecialchars($appeal_banner['text']) ?>
                </div>
            <?php endif; ?>

            <?php if ($appeals->num_rows === 0): ?>
                <div class="page-placeholder">
                    <div class="placeholder-icon">
                        <i class='bx bx-message-square-dots'></i>
                    </div>
                    <h2 class="placeholder-title">No appeal cases pending</h2>
                    <p class="placeholder-text">
                        When researchers contest a rejection, their justifications and past evaluations will appear here for your review.
                    </p>
                </div>
            <?php else: ?>
                <div class="card-container">
                    <?php while ($row = $appeals->fetch_assoc()): ?>
                        <div class="tier-item appeal-item-border">
                            <div class="tier-item-content">
                                <div class="tier-item-title"><?= htmlspecialchars($row['title']) ?></div>
                                <div class="tier-item-researcher"><?= htmlspecialchars($row['researcher_email']) ?></div>
                                <div class="appeal-budget">
                                    Requested: RM<?= number_format((float)($row['budget_requested'] ?? 0), 2) ?>
                                </div>
                                <div class="appeal-justification">
                                    <strong>Justification:</strong> <?= htmlspecialchars(substr($row['justification'], 0, 100)) ?><?= strlen($row['justification']) > 100 ? '...' : '' ?>
                                </div>

                            </div>
                            <div class="tier-item-actions">
                                <button type="button" class="tier-item-btn" onclick="openAppealModal(<?= (int)$row['proposal_id'] ?>, <?= (int)$row['appeal_id'] ?>)" title="Review Appeal">
                                    <i class='bx bx-search-alt'></i> Review
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Appeal Details Modal -->
                <div id="appealModal" class="rubric-modal">
                    <div class="rubric-content rubric-split">
                        <div id="appealModalBody"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Rubric Modal -->
    <div id="rubricModal" class="rubric-modal">
        <div class="rubric-content rubric-split">
            <div class="rubric-header">
                <h2 class="rubric-title" id="rubricProposalTitle">Proposal Title</h2>
                <button class="rubric-close" onclick="closeRubricModal()">&times;</button>
            </div>

            <div class="rubric-layout">
                <!-- Left Panel: Proposal Display -->
                <div class="proposal-panel">
                    <div class="proposal-viewer">
                        <iframe id="proposalIframe" style="width: 100%; height: 100%; border: 1px solid #ddd; border-radius: 5px;"></iframe>
                    </div>
                </div>

                <!-- Right Panel: All Rubric Info -->
                <div class="rubric-panel">
                    <div class="proposal-meta">
                        <div><strong>Researcher:</strong> <span id="rubricResearcher">-</span></div>
                        <div><strong>Requested Budget:</strong> <span id="rubricBudget">-</span></div>
                    </div>

            <table class="rubric-table">
                <thead>
                    <tr>
                        <th>Evaluation Aspect</th>
                        <th>Rating (1-5)</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="rubric-aspect">Potential Research Outcome</td>
                        <td>
                            <div class="rubric-stars" id="stars-outcome" data-aspect="outcome"></div>
                        </td>
                        <td class="rubric-score" id="score-outcome">0</td>
                    </tr>
                    <tr>
                        <td class="rubric-aspect">Research Impact</td>
                        <td>
                            <div class="rubric-stars" id="stars-impact" data-aspect="impact"></div>
                        </td>
                        <td class="rubric-score" id="score-impact">0</td>
                    </tr>
                    <tr>
                        <td class="rubric-aspect">Strategic Alignment</td>
                        <td>
                            <div class="rubric-stars" id="stars-alignment" data-aspect="alignment"></div>
                        </td>
                        <td class="rubric-score" id="score-alignment">0</td>
                    </tr>
                    <tr>
                        <td class="rubric-aspect">Funding Constraints</td>
                        <td>
                            <div class="rubric-stars" id="stars-funding" data-aspect="funding"></div>
                        </td>
                        <td class="rubric-score" id="score-funding">0</td>
                    </tr>
                </tbody>
            </table>

            <!-- Budget Adjustment -->
            <div class="budget-input-group">
                <label class="budget-label">Budget Adjustment</label>
                <div class="budget-inputs">
                    <div class="budget-field">
                        <label>Requested Amount</label>
                        <input type="text" id="requestedBudget" class="readonly-field" disabled>
                    </div>
                    <div class="budget-field">
                        <label>Approved Amount</label>
                        <input type="number" id="approvedBudget" step="100" min="0">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="rubric-notes">
                <label class="rubric-notes-label">Reviewer Feedback</label>
                <div id="annotatedProposalLink" style="margin-bottom: 10px; display: none;">
                    <a href="#" id="viewAnnotatedProposalBtn" target="_blank" style="color: #3C5B6F; text-decoration: underline; font-size: 13px; font-weight: 600; cursor: pointer;">
                        Click to view the annotated proposal
                    </a>
                </div>
                <textarea class="rubric-notes-text readonly-field" id="reviewerFeedback" readonly></textarea>
            </div>

            <div class="rubric-notes">
                <label class="rubric-notes-label">HOD Notes (Optional)</label>
                <textarea class="rubric-notes-text" id="hodNotes" placeholder="Add your evaluation notes..."></textarea>
            </div>

            <div class="action-buttons" style="margin-top: 20px;">
                <button class="btn-reset" onclick="closeRubricModal()">Close</button>
                <button class="btn-approve-all" onclick="saveRubric()">Save Rubric</button>
            </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Modal -->
    <div id="alertModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon" id="alertIcon">⚠️</div>
            <h3 class="modal-title" id="alertTitle">Alert</h3>
            <p class="modal-message" id="alertMessage">Alert message here</p>
            <div class="modal-buttons">
                <button class="modal-btn primary" onclick="closeAlertModal()">OK</button>
            </div>
        </div>
    </div>

    <script>
        // Data storage
        let proposalsData = <?= json_encode($proposals) ?>;
        let tierAssignments = {}; // { proposalId: 'top'|'middle'|'bottom' }
        let rubricScores = {}; // { proposalId: { outcome: 5, impact: 4, ... } }
        let budgetAdjustments = {}; // { proposalId: approvedAmount }
        let evaluatedProposals = {}; // { proposalId: true/false }
        let deptBalance = <?= $department_balance ?>;
        let currentProposalId = null;
        let rubricWeightages = {
            outcome: 1.0,
            impact: 1.0,
            alignment: 1.0,
            funding: 1.0
        };
        let hasEvaluatedProposals = false;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Restore weightages from localStorage first
            restoreWeightages();
            // Check if any proposals are already evaluated
            hasEvaluatedProposals = proposalsData.some(p => p.is_evaluated === 1 || p.is_evaluated === '1');
            initializeProposals();
            // Keep evaluated proposals visible in the review list but also clone them into tiers
            autoDistributeEvaluatedProposals();
            updateBudgetDisplay();
        });

        // Update weightage display
        function updateWeightageDisplay(aspect, value) {
            document.getElementById('weight' + aspect.charAt(0).toUpperCase() + aspect.slice(1) + 'Value').textContent = parseFloat(value).toFixed(1);
            rubricWeightages[aspect] = parseFloat(value);
        }

        // Confirm weightage and show warning if proposals already evaluated
        function confirmWeightage() {
            // Save weightages to localStorage
            saveWeightages();
            
            if (hasEvaluatedProposals) {
                showAlert('Warning', 'Changing weightage after evaluating proposals will affect the scoring. Previously evaluated proposals will need to be re-distributed based on the new weighted scores.', 'warning');
                // Recalculate and redistribute
                refreshProposalScores();
                autoDistributeEvaluatedProposals();
                updateBudgetDisplay();
            } else {
                showAlert('Success', 'Weightage configuration confirmed. The scores will be calculated using these weights as multipliers.', 'success');
            }
        }

        // Save weightages to localStorage
        function saveWeightages() {
            localStorage.setItem('rubricWeightages', JSON.stringify(rubricWeightages));
            console.log('Weightages saved:', rubricWeightages);
        }

        // Restore weightages from localStorage
        function restoreWeightages() {
            const stored = localStorage.getItem('rubricWeightages');
            if (stored) {
                try {
                    const saved = JSON.parse(stored);
                    rubricWeightages = Object.assign({}, rubricWeightages, saved);
                    // Update UI sliders and display values
                    Object.keys(rubricWeightages).forEach(aspect => {
                        const sliderId = 'weight' + aspect.charAt(0).toUpperCase() + aspect.slice(1);
                        const slider = document.getElementById(sliderId);
                        if (slider) {
                            slider.value = rubricWeightages[aspect];
                            const displayId = sliderId + 'Value';
                            const display = document.getElementById(displayId);
                            if (display) {
                                display.textContent = rubricWeightages[aspect].toFixed(1);
                            }
                        }
                    });
                    console.log('Weightages restored:', rubricWeightages);
                } catch (e) {
                    console.error('Failed to restore weightages:', e);
                }
            }
        }

        // Update proposal count
        function updateProposalCount() {
            // Count proposals in available section
            const availableItems = document.querySelectorAll('#availableProposals .tier-item');
            const evaluatedCount = Array.from(availableItems).filter(item => item.classList.contains('evaluated')).length;
            const pendingCount = availableItems.length - evaluatedCount;
            
            const countText = `${availableItems.length} proposal${availableItems.length !== 1 ? 's' : ''} (${evaluatedCount} evaluated, ${pendingCount} pending)`;
            document.getElementById('proposalCount').textContent = countText;
        }

        // Apply filters to proposals
        function applyFilters() {
            const availableContainer = document.getElementById('availableProposals');
            const items = availableContainer.querySelectorAll('.tier-item');
            
            // Get selected filters
            const filterHighPriority = document.getElementById('filterHighPriority').checked;
            const filterStandard = document.getElementById('filterStandard').checked;
            const filterEvaluated = document.getElementById('filterEvaluated').checked;
            const filterPendingEvaluation = document.getElementById('filterPendingEvaluation').checked;
            const searchText = document.getElementById('searchFilter').value.toLowerCase();
            
            let visibleCount = 0;

            items.forEach(item => {
                let shouldShow = true;
                
                // Check priority filter
                const isPriority = item.innerHTML.includes('High Priority');
                const priorityMatch = !filterHighPriority && !filterStandard || 
                                     (filterHighPriority && isPriority) || 
                                     (filterStandard && !isPriority);
                
                // Check evaluated status filter
                const isEvaluated = item.classList.contains('evaluated');
                const statusMatch = !filterEvaluated && !filterPendingEvaluation ||
                                   (filterEvaluated && isEvaluated) ||
                                   (filterPendingEvaluation && !isEvaluated);
                
                // Check search text
                const title = item.querySelector('.tier-item-title')?.textContent.toLowerCase() || '';
                const researcher = item.querySelector('.tier-item-researcher')?.textContent.toLowerCase() || '';
                const searchMatch = searchText === '' || title.includes(searchText) || researcher.includes(searchText);
                
                // If no filters selected, show all
                if (!filterHighPriority && !filterStandard && !filterEvaluated && !filterPendingEvaluation) {
                    shouldShow = searchMatch;
                } else {
                    shouldShow = priorityMatch && statusMatch && searchMatch;
                }
                
                item.style.display = shouldShow ? 'flex' : 'none';
                if (shouldShow) visibleCount++;
            });
            
            // Show/hide empty message
            const emptyMsg = availableContainer.querySelector('.empty-message');
            if (visibleCount === 0) {
                if (!emptyMsg) {
                    const msg = document.createElement('div');
                    msg.className = 'empty-message';
                    msg.textContent = 'No proposals match your filters';
                    availableContainer.appendChild(msg);
                }
            } else {
                if (emptyMsg) emptyMsg.remove();
            }
        }

        // Auto-distribute evaluated proposals into tiers without removing them from the review list
        function autoDistributeEvaluatedProposals() {
            const tiers = {
                top: document.getElementById('topTier'),
                middle: document.getElementById('middleTier'),
                bottom: document.getElementById('bottomTier')
            };

            // Remove previously auto-generated clones to prevent duplicates
            document.querySelectorAll('.tier-item.auto-generated').forEach(el => el.remove());

            // Calculate max possible score based on current weightages
            const maxPossibleScore = 
                5 * rubricWeightages.outcome +
                5 * rubricWeightages.impact +
                5 * rubricWeightages.alignment +
                5 * rubricWeightages.funding;

            proposalsData.forEach(proposal => {
                if (proposal.is_evaluated === 1 || proposal.is_evaluated === '1') {
                    const score = Number(proposal.total_score) || 0;
                    const percentile = maxPossibleScore > 0 ? (score / maxPossibleScore) * 100 : 0;
                    let tier = 'bottom';

                    if (percentile >= 80) {
                        tier = 'top';
                    } else if (percentile >= 60) {
                        tier = 'middle';
                    }

                    const tierElement = tiers[tier];
                    const alreadyPlaced = tierElement.querySelector(`.tier-item[data-proposal-id="${proposal.id}"]`);
                    if (!alreadyPlaced) {
                        const clone = createTierElement(proposal);
                        clone.classList.add('auto-generated');
                        tierElement.appendChild(clone);
                    }

                    const emptyMsg = tierElement.querySelector('.empty-message');
                    if (emptyMsg) emptyMsg.remove();
                    tierAssignments[proposal.id] = tier;
                }
            });

            updateEmptyMessages();
            updateBudgetDisplay();
        }
        // Refresh score display for all proposals in the available proposals section
        function refreshProposalScores() {
            const availableContainer = document.getElementById('availableProposals');
            const proposalItems = availableContainer.querySelectorAll('.proposal-item');
            
            proposalItems.forEach(item => {
                const proposalId = parseInt(item.dataset.proposalId);
                const proposal = proposalsData.find(p => p.id === proposalId);
                
                if (proposal) {
                    // Calculate updated score percentage
                    const maxPossibleScore = 
                        5 * rubricWeightages.outcome +
                        5 * rubricWeightages.impact +
                        5 * rubricWeightages.alignment +
                        5 * rubricWeightages.funding;
                    const score = Number(proposal.total_score) || 0;
                    const scorePercentage = maxPossibleScore > 0 ? ((score / maxPossibleScore) * 100).toFixed(1) : 0;
                    
                    // Find and update the score display element
                    const scoreElement = item.querySelector('[style*="margin-top: 5px; font-weight: 600;"]');
                    if (scoreElement && (proposal.is_evaluated === 1 || proposal.is_evaluated === '1')) {
                        scoreElement.innerHTML = `Score: ${scorePercentage}%`;
                    } else if (proposal.is_evaluated === 1 || proposal.is_evaluated === '1') {
                        // Add score display if not present but proposal is evaluated
                        const contentDiv = item.querySelector('.tier-item-content');
                        if (contentDiv) {
                            const newScoreElement = document.createElement('div');
                            newScoreElement.style.cssText = 'font-size: 12px; color: #3C5B6F; margin-top: 5px; font-weight: 600;';
                            newScoreElement.innerHTML = `Score: ${scorePercentage}%`;
                            contentDiv.appendChild(newScoreElement);
                        }
                    }
                }
            });
        }

        function initializeProposals() {
            const availableContainer = document.getElementById('availableProposals');
            const topTier = document.getElementById('topTier');
            const middleTier = document.getElementById('middleTier');
            const bottomTier = document.getElementById('bottomTier');

            // Clear existing items
            availableContainer.innerHTML = '';
            topTier.innerHTML = '';
            middleTier.innerHTML = '';
            bottomTier.innerHTML = '';

            // Create proposal items if any exist
            if (proposalsData.length === 0) {
                availableContainer.innerHTML = '<div class="empty-message">No proposals available for review</div>';
                topTier.innerHTML = '<div class="empty-message">Drag proposals here</div>';
                middleTier.innerHTML = '<div class="empty-message">Drag proposals here</div>';
                bottomTier.innerHTML = '<div class="empty-message">Drag proposals here</div>';
                document.getElementById('proposalCount').textContent = '0 proposals';
                return;
            }

            // Add each proposal as a draggable item to the available proposals section
            proposalsData.forEach(proposal => {
                const element = createProposalElement(proposal);
                availableContainer.appendChild(element);
            });

            // Update proposal count
            updateProposalCount();

            // Add placeholder messages to tier sections
            topTier.innerHTML = '<div class="empty-message">Drag proposals here</div>';
            middleTier.innerHTML = '<div class="empty-message">Drag proposals here</div>';
            bottomTier.innerHTML = '<div class="empty-message">Drag proposals here</div>';

            // Create proposal items for unassigned (show in all tiers as placeholders)
            if (proposalsData.length === 0) {
                return;
            }

            // For initial display, let user drag them
        }

        function createProposalElement(proposal) {
            const div = document.createElement('div');
            div.className = 'tier-item proposal-item';
            if (proposal.is_evaluated === 1 || proposal.is_evaluated === '1') {
                div.classList.add('evaluated');
                evaluatedProposals[proposal.id] = true;
            }
            div.dataset.proposalId = proposal.id;
            div.dataset.budget = proposal.budget_requested || 0;
            div.setAttribute('draggable', true);

            const priorityBadge = (proposal.priority && proposal.priority.toUpperCase() === 'HIGH')
                ? '<span class="priority-badge high">High Priority</span>'
                : '<span class="priority-badge low">Standard</span>';

            // Calculate score percentage
            const maxPossibleScore = 
                5 * rubricWeightages.outcome +
                5 * rubricWeightages.impact +
                5 * rubricWeightages.alignment +
                5 * rubricWeightages.funding;
            const score = Number(proposal.total_score) || 0;
            const scorePercentage = maxPossibleScore > 0 ? ((score / maxPossibleScore) * 100).toFixed(1) : 0;
            const scoreDisplay = proposal.is_evaluated === 1 || proposal.is_evaluated === '1' 
                ? `<div style="font-size: 12px; color: #3C5B6F; margin-top: 5px; font-weight: 600;">Score: ${scorePercentage}%</div>`
                : '';

            div.innerHTML = `
                <div class="tier-item-content">
                    <div class="tier-item-title">${escapeHtml(proposal.title)} ${priorityBadge}</div>
                    <div class="tier-item-researcher">${escapeHtml(proposal.researcher_name || proposal.researcher_email)}</div>
                    <div style="font-size: 12px; color: #999; margin-top: 5px;">
                        Requested: RM${parseFloat(proposal.budget_requested || 0).toFixed(2)}
                    </div>
                    ${scoreDisplay}
                </div>
                <div class="tier-item-actions">
                    <button class="tier-item-btn" onclick="openRubricModal(${proposal.id})" title="Evaluate & Set Budget">
                        <i class='bx bx-slider-alt'></i> Evaluate
                    </button>
                </div>
            `;

            div.addEventListener('dragstart', dragStart);
            div.addEventListener('dragend', dragEnd);

            return div;
        }

        // Build a tier entry (clone) so evaluated proposals can sit in tiers while originals stay in the review list
        function createTierElement(proposal) {
            const div = document.createElement('div');
            div.className = 'tier-item';
            if (proposal.is_evaluated === 1 || proposal.is_evaluated === '1') {
                div.classList.add('evaluated');
            }
            div.dataset.proposalId = proposal.id;
            div.dataset.budget = proposal.budget_requested || 0;
            div.setAttribute('draggable', true);

            // Calculate score percentage
            const maxPossibleScore = 
                5 * rubricWeightages.outcome +
                5 * rubricWeightages.impact +
                5 * rubricWeightages.alignment +
                5 * rubricWeightages.funding;
            const score = Number(proposal.total_score) || 0;
            const scorePercentage = maxPossibleScore > 0 ? ((score / maxPossibleScore) * 100).toFixed(1) : 0;
            const scoreDisplay = proposal.is_evaluated === 1 || proposal.is_evaluated === '1' 
                ? `<div style="font-size: 12px; color: #3C5B6F; margin-top: 5px; font-weight: 600;">Score: ${scorePercentage}%</div>`
                : '';

            div.innerHTML = `
                <div class="tier-item-content">
                    <div class="tier-item-title">${escapeHtml(proposal.title)}</div>
                    <div class="tier-item-researcher">${escapeHtml(proposal.researcher_name || proposal.researcher_email)}</div>
                    <div style="font-size: 12px; color: #999; margin-top: 5px;">
                        Requested: RM${parseFloat(proposal.budget_requested || 0).toFixed(2)}
                    </div>
                    ${scoreDisplay}
                </div>
                <div class="tier-item-actions">
                    <button class="tier-item-btn" onclick="openRubricModal(${proposal.id})" title="Edit & Update Budget">
                        <i class='bx bx-edit'></i> Edit
                    </button>
                </div>
            `;

            div.addEventListener('dragstart', dragStart);
            div.addEventListener('dragend', dragEnd);

            return div;
        }

        // Drag and Drop Functions
        function dragStart(e) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', e.currentTarget);
            e.currentTarget.classList.add('dragging');
        }

        function dragEnd(e) {
            e.currentTarget.classList.remove('dragging');
        }

        function dragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            e.currentTarget.classList.add('drag-over');
        }

        function dragLeave(e) {
            e.currentTarget.classList.remove('drag-over');
        }

        function allowDrop(e) {
            e.preventDefault();
            const tierElement = e.currentTarget;
            tierElement.classList.remove('drag-over');

            const draggedElement = document.querySelector('.tier-item.dragging');
            if (draggedElement) {
                const proposalId = parseInt(draggedElement.dataset.proposalId);
                const tierName = tierElement.id.replace('Tier', '').toLowerCase();

                // Remove from current tier
                draggedElement.remove();

                // Add to new tier
                tierElement.appendChild(draggedElement);
                tierAssignments[proposalId] = tierName;

                // Remove empty message if exists
                const emptyMsg = tierElement.querySelector('.empty-message');
                if (emptyMsg) emptyMsg.remove();

                // Add empty message to source if now empty
                updateEmptyMessages();
                updateBudgetDisplay();
            }
        }

        function updateEmptyMessages() {
            ['topTier', 'middleTier', 'bottomTier'].forEach(tierId => {
                const tier = document.getElementById(tierId);
                const hasItems = tier.querySelectorAll('.tier-item').length > 0;
                const emptyMsg = tier.querySelector('.empty-message');

                if (!hasItems && !emptyMsg) {
                    const msg = document.createElement('div');
                    msg.className = 'empty-message';
                    msg.textContent = 'Drag proposals here';
                    tier.appendChild(msg);
                }
            });
        }

        // Rubric Modal Functions
        function openRubricModal(proposalId) {
            currentProposalId = proposalId;
            const proposal = proposalsData.find(p => p.id === proposalId);

            if (!proposal) return;

            // Populate modal
            document.getElementById('rubricProposalTitle').textContent = proposal.title;
            document.getElementById('rubricResearcher').textContent = proposal.researcher_name || proposal.researcher_email;
            const requested = parseFloat(proposal.budget_requested || 0);
            document.getElementById('rubricBudget').textContent = `RM${requested.toFixed(2)}`;
            document.getElementById('requestedBudget').value = `RM${requested.toFixed(2)}`;
            document.getElementById('reviewerFeedback').value = proposal.reviewer_feedback || '';
            
            // Display proposal in iframe
            const iframe = document.getElementById('proposalIframe');
            const filePath = proposal.file_path || '';
            if (filePath) {
                iframe.src = filePath;
                iframe.style.display = 'block';
            } else {
                iframe.style.display = 'none';
            }

            // Default approved budget equals requested unless adjusted
            document.getElementById('approvedBudget').value = budgetAdjustments[proposalId] ?? requested;

            // Initialize rating stars
            initializeRatingStars(proposalId);

            // Load previously saved rubric from server
            loadRubric(proposalId);

            document.getElementById('rubricModal').classList.add('show');
        }

        function closeRubricModal() {
            document.getElementById('rubricModal').classList.remove('show');
            currentProposalId = null;
        }

        function initializeRatingStars(proposalId) {
            const aspects = ['outcome', 'impact', 'alignment', 'funding'];
            const scores = rubricScores[proposalId] || {};

            aspects.forEach(aspect => {
                const starsContainer = document.getElementById(`stars-${aspect}`);
                starsContainer.innerHTML = '';

                for (let i = 1; i <= 5; i++) {
                    const star = document.createElement('span');
                    star.className = 'star';
                    star.textContent = '★';
                    if (i <= (scores[aspect] || 0)) {
                        star.classList.add('active');
                    }
                    star.addEventListener('click', function() {
                        setRating(aspect, i);
                    });
                    starsContainer.appendChild(star);
                }
                // Display weighted score
                const rating = scores[aspect] || 0;
                const weightedScore = rating * (rubricWeightages[aspect] || 1.0);
                document.getElementById(`score-${aspect}`).textContent = weightedScore.toFixed(1);
            });
        }

        function setRating(aspect, rating) {
            if (!currentProposalId) return;

            if (!rubricScores[currentProposalId]) {
                rubricScores[currentProposalId] = {};
            }
            rubricScores[currentProposalId][aspect] = rating;

            // Calculate weighted score (rating × weightage)
            const weightedScore = rating * (rubricWeightages[aspect] || 1.0);
            document.getElementById(`score-${aspect}`).textContent = weightedScore.toFixed(1);

            // Update stars display
            const stars = document.querySelectorAll(`#stars-${aspect} .star`);
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        function saveRubric() {
            if (!currentProposalId) return;

            const approvedBudget = parseFloat(document.getElementById('approvedBudget').value) || 0;
            const requestedBudget = parseFloat(document.getElementById('requestedBudget').value.replace(/[RM$,]/g, '')) || 0;
            const hodNotes = document.getElementById('hodNotes').value || '';
            const scores = rubricScores[currentProposalId] || { outcome: 0, impact: 0, alignment: 0, funding: 0 };

            // Validation: Approved budget must be less than or equal to requested amount
            if (approvedBudget > requestedBudget) {
                showAlert('Invalid Budget', 'Approved budget cannot exceed the requested amount.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'save_rubric');
            formData.append('proposal_id', currentProposalId);
            formData.append('approved_budget', approvedBudget);
            formData.append('hod_notes', hodNotes);
            formData.append('outcome_score', scores.outcome || 0);
            formData.append('impact_score', scores.impact || 0);
            formData.append('alignment_score', scores.alignment || 0);
            formData.append('funding_score', scores.funding || 0);

            fetch('hod_proposal_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    budgetAdjustments[currentProposalId] = approvedBudget;
                    // Mark proposal as evaluated
                    const proposalElements = document.querySelectorAll(`[data-proposal-id="${currentProposalId}"]`);
                    proposalElements.forEach(el => {
                        el.classList.add('evaluated');
                    });

                    // Persist evaluation flags locally for subsequent auto-distribution
                    // Calculate total score using weightages as multipliers
                    const totalScore = 
                        (scores.outcome || 0) * rubricWeightages.outcome +
                        (scores.impact || 0) * rubricWeightages.impact +
                        (scores.alignment || 0) * rubricWeightages.alignment +
                        (scores.funding || 0) * rubricWeightages.funding;
                    const proposalRef = proposalsData.find(p => p.id === currentProposalId);
                    if (proposalRef) {
                        proposalRef.is_evaluated = 1;
                        proposalRef.total_score = totalScore;
                    }

                    showAlert('Success', 'Rubric saved successfully!', 'success');
                    closeRubricModal();
                    refreshProposalScores();
                    autoDistributeEvaluatedProposals();
                    updateBudgetDisplay();
                    updateProposalCount();
                } else {
                    showAlert('Error', data.message || 'Failed to save rubric', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showAlert('Error', 'An error occurred while saving rubric', 'error');
            });
        }

        function loadRubric(proposalId) {
            const formData = new FormData();
            formData.append('action', 'get_rubric');
            formData.append('proposal_id', proposalId);

            fetch('hod_proposal_handler.php', { method: 'POST', body: formData })
            .then(resp => resp.json())
            .then(data => {
                if (data.success && data.rubric) {
                    rubricScores[proposalId] = {
                        outcome: data.rubric.outcome_score || 0,
                        impact: data.rubric.impact_score || 0,
                        alignment: data.rubric.alignment_score || 0,
                        funding: data.rubric.funding_score || 0,
                    };
                    initializeRatingStars(proposalId);
                    document.getElementById('hodNotes').value = data.rubric.hod_notes || '';
                    if (typeof data.approved_budget === 'number') {
                        document.getElementById('approvedBudget').value = data.approved_budget;
                        budgetAdjustments[proposalId] = data.approved_budget;
                    }
                    
                    // Load annotated proposal link if available
                    const annotatedLink = document.getElementById('annotatedProposalLink');
                    const viewAnnotatedBtn = document.getElementById('viewAnnotatedProposalBtn');
                    if (data.annotated_proposal_file) {
                        viewAnnotatedBtn.href = data.annotated_proposal_file;
                        annotatedLink.style.display = 'block';
                    } else {
                        annotatedLink.style.display = 'none';
                    }
                }
            })
            .catch(err => console.error(err));
        }

        // Budget Display Functions
        function updateBudgetDisplay() {
            let topTierTotal = 0;
            const topTier = document.getElementById('topTier');
            topTier.querySelectorAll('.tier-item').forEach(item => {
                const proposalId = parseInt(item.dataset.proposalId);
                const budget = budgetAdjustments[proposalId] || parseFloat(item.dataset.budget) || 0;
                topTierTotal += budget;
            });

            // Calculate middle and bottom tiers
            let middleTierTotal = 0;
            document.getElementById('middleTier').querySelectorAll('.tier-item').forEach(item => {
                const proposalId = parseInt(item.dataset.proposalId);
                const budget = budgetAdjustments[proposalId] || parseFloat(item.dataset.budget) || 0;
                middleTierTotal += budget;
            });

            let bottomTierTotal = 0;
            document.getElementById('bottomTier').querySelectorAll('.tier-item').forEach(item => {
                const proposalId = parseInt(item.dataset.proposalId);
                const budget = budgetAdjustments[proposalId] || parseFloat(item.dataset.budget) || 0;
                bottomTierTotal += budget;
            });

            // Update display
            document.getElementById('topBudget').textContent = `RM${topTierTotal.toFixed(2)}`;
            document.getElementById('middleBudget').textContent = `RM${middleTierTotal.toFixed(2)}`;
            document.getElementById('bottomBudget').textContent = `RM${bottomTierTotal.toFixed(2)}`;
            document.getElementById('topTierTotal').textContent = `RM${topTierTotal.toFixed(2)}`;

            const remaining = deptBalance - topTierTotal;
            document.getElementById('remainingBudget').textContent = `RM${remaining.toFixed(2)}`;

            // Show warning if exceeds budget
            const warningBox = document.getElementById('budgetWarning');
            const approveBtn = document.getElementById('approveAllBtn');
            const rejectBtn = document.getElementById('rejectAllBtn');
            const bottomTier = document.getElementById('bottomTier');

            if (topTierTotal > deptBalance) {
                warningBox.style.display = 'block';
                approveBtn.disabled = true;
            } else {
                warningBox.style.display = 'none';
                approveBtn.disabled = topTier.querySelectorAll('.tier-item').length === 0;
            }

            // Enable/disable reject button based on bottom tier proposals
            rejectBtn.disabled = bottomTier.querySelectorAll('.tier-item').length === 0;
        }

        // Action Functions
        function resetTiers() {
            // Clear all tier sections
            ['topTier', 'middleTier', 'bottomTier'].forEach(tierId => {
                document.getElementById(tierId).innerHTML = '<div class="empty-message">Drag proposals here</div>';
            });
            tierAssignments = {};
            budgetAdjustments = {};
            
            // Restore initial system-generated tier distribution for evaluated proposals
            autoDistributeEvaluatedProposals();
            updateBudgetDisplay();
        }

        function approveAllTopTier() {
            const topTier = document.getElementById('topTier');
            const items = topTier.querySelectorAll('.tier-item');

            if (items.length === 0) {
                showAlert('No Proposals', 'Please add proposals to the top tier first.', 'warning');
                return;
            }

            let totalBudget = 0;
            items.forEach(item => {
                const proposalId = parseInt(item.dataset.proposalId);
                const budget = budgetAdjustments[proposalId] || parseFloat(item.dataset.budget) || 0;
                totalBudget += budget;
            });

            if (totalBudget > deptBalance) {
                showAlert('Budget Exceeded', 'Total approved amount exceeds department budget!', 'error');
                return;
            }

            // Prepare data for submission
            const proposalIds = Array.from(items).map(item => item.dataset.proposalId).join(',');
            
            // Send to server (you'll need to create a handler endpoint)
            const formData = new FormData();
            formData.append('action', 'approve_top_tier');
            formData.append('proposal_ids', proposalIds);
            formData.append('adjustments', JSON.stringify(budgetAdjustments));

            fetch('hod_proposal_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Success', `${items.length} proposals approved successfully!`, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('Error', data.message || 'Failed to approve proposals', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error', 'An error occurred while approving proposals', 'error');
            });
        }

        function saveTierAssignments() {
            const topTier = document.getElementById('topTier');
            const middleTier = document.getElementById('middleTier');
            const bottomTier = document.getElementById('bottomTier');

            const assignments = {};
            
            // Collect top tier proposals
            topTier.querySelectorAll('.tier-item').forEach(item => {
                const proposalId = parseInt(item.dataset.proposalId);
                assignments[proposalId] = 'top';
            });

            // Collect middle tier proposals
            middleTier.querySelectorAll('.tier-item').forEach(item => {
                const proposalId = parseInt(item.dataset.proposalId);
                assignments[proposalId] = 'middle';
            });

            // Collect bottom tier proposals
            bottomTier.querySelectorAll('.tier-item').forEach(item => {
                const proposalId = parseInt(item.dataset.proposalId);
                assignments[proposalId] = 'bottom';
            });

            const formData = new FormData();
            formData.append('action', 'save_tier_assignments');
            formData.append('assignments', JSON.stringify(assignments));

            fetch('hod_proposal_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Success', 'Tier assignments saved successfully!', 'success');
                } else {
                    showAlert('Error', data.message || 'Failed to save tier assignments', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error', 'An error occurred while saving tier assignments', 'error');
            });
        }

        function rejectAllBottomTier() {
            const bottomTier = document.getElementById('bottomTier');
            const items = bottomTier.querySelectorAll('.tier-item');

            if (items.length === 0) {
                showAlert('No Proposals', 'Please add proposals to the bottom tier first.', 'warning');
                return;
            }

            // Prepare data for submission
            const proposalIds = Array.from(items).map(item => item.dataset.proposalId).join(',');
            
            const formData = new FormData();
            formData.append('action', 'reject_bottom_tier');
            formData.append('proposal_ids', proposalIds);

            fetch('hod_proposal_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Success', `${items.length} proposals rejected successfully!`, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('Error', data.message || 'Failed to reject proposals', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error', 'An error occurred while rejecting proposals', 'error');
            });
        }

        // Alert Modal
        function showAlert(title, message, type = 'info') {
            const modal = document.getElementById('alertModal');
            document.getElementById('alertTitle').textContent = title;
            document.getElementById('alertMessage').textContent = message;
            
            const icons = {
                'success': '✓',
                'error': '✕',
                'warning': '⚠',
                'info': 'ℹ'
            };
            document.getElementById('alertIcon').textContent = icons[type] || 'ℹ';
            
            modal.classList.add('show');
        }

        function closeAlertModal() {
            document.getElementById('alertModal').classList.remove('show');
        }

        // Utility Functions
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Load initial proposal items to drag
        window.addEventListener('load', function() {
            // Create draggable proposal items if needed
            // This can be enhanced to show proposals in an "Available" section
        });

        // Tab Switching Function
        function openTab(evt, tabName) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }

            // Remove active class from all tab buttons
            const tabButtons = document.getElementsByClassName('tab-btn');
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove('active');
            }

            // Show the current tab content
            document.getElementById(tabName).classList.add('active');

            // Add active class to the clicked button
            evt.currentTarget.classList.add('active');
        }

        // ============ APPEAL CASES JAVASCRIPT ============
        function submitAppealDecision(proposalId, appealId, action) {
            console.log('Submit appeal decision called:', proposalId, appealId, action);
            
            if (!proposalId || !appealId || !action) {
                console.error('Missing parameters:', { proposalId, appealId, action });
                alert('Error: Missing required parameters');
                return;
            }
            
            if (!confirm('Are you sure you want to ' + (action === 'approve' ? 'APPROVE this appeal' : 'UPHOLD the rejection') + '?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('proposal_id', proposalId);
            formData.append('appeal_id', appealId);
            formData.append('appeal_action', action);
            
            console.log('Sending request to hod_proposal_management.php');

            fetch('hod_proposal_management.php', {
                method: 'POST',
                body: formData
            })
            .then(r => {
                console.log('Response received:', r.status, r.statusText);
                return r.text();
            })
            .then(html => {
                console.log('Response HTML length:', html.length);
                // Reload the page to show updated state
                window.location.href = 'hod_proposal_management.php#appeal-cases';
            })
            .catch(e => {
                console.error('Fetch error:', e);
                alert('Error processing appeal decision: ' + e.message);
            });
        }

        function openAppealModal(proposalId, appealId) {
            console.log('Opening appeal modal for proposal:', proposalId, 'appeal:', appealId);
            const modal = document.getElementById('appealModal');
            const body = document.getElementById('appealModalBody');
            
            if (!modal || !body) {
                console.error('Modal elements not found');
                return;
            }
            
            // Show loading message
            body.innerHTML = '<div class="loading-message">Loading...</div>';
            modal.classList.add('show');
            
            // Fetch appeal details via AJAX
            fetch('get_appeal_details.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'proposal_id=' + proposalId + '&appeal_id=' + appealId
            })
            .then(r => {
                console.log('Response status:', r.status);
                return r.text();
            })
            .then(html => {
                console.log('Response received, length:', html.length);
                body.innerHTML = html;
            })
            .catch(e => {
                console.error('Error loading appeal details:', e);
                body.innerHTML = '<div class="error-message">Error loading appeal details: ' + e.message + '</div>';
            });
        }

        function closeAppealModal() {
            const modal = document.getElementById('appealModal');
            if (modal) {
                modal.classList.remove('show');
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('appealModal');
            if (event.target === modal) {
                modal.classList.remove('show');
            }
        });
    </script>
</body>
</html>
