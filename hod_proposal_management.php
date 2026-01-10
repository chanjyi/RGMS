<?php
session_start();
require 'config.php';

// Verify HOD access
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}

// Get HOD's department information
$hod_query = "SELECT department_id FROM users WHERE email = ? AND role = 'hod'";
$hod_stmt = $conn->prepare($hod_query);
$hod_stmt->bind_param("s", $_SESSION['email']);
$hod_stmt->execute();
$hod_result = $hod_stmt->get_result();
$hod_data = $hod_result->fetch_assoc();
$department_id = $hod_data['department_id'] ?? null;

// Get all proposals for this HOD's department that are in RECOMMEND status
// $proposal_query = "SELECT p.*, 
//                           r.feedback as reviewer_feedback,
//                           u.name as researcher_name,
//                           f.amount_allocated as grant_amount
//                    FROM proposals p 
//                    LEFT JOIN reviews r ON p.id = r.proposal_id AND r.status = 'RECOMMENDED'
//                    LEFT JOIN users u ON p.researcher_email = u.email 
//                    LEFT JOIN fund f ON p.id = f.proposal_id
//                    WHERE p.status IN ('RECOMMEND', 'PRIORITIZE') 
//                    AND p.department_id = ?
//                    ORDER BY (p.status = 'PRIORITIZE') DESC, p.created_at ASC";
$proposal_query = "SELECT * FROM proposals WHERE id = ?";
$proposal_stmt = $conn->prepare($proposal_query);
$proposal_stmt->bind_param("i", $department_id);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Management - RGMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="hod_pages.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <section class="home-section">
        <div class="welcome-text">
            <i class='bx bx-list-check'></i>
            Proposal Management & Prioritization
        </div>
        <hr style="opacity: 0.3; margin: 20px 0;">

        <!-- Filter Bar -->
        <div class="proposal-filter-bar">
            <div class="filter-group">
                <label class="filter-label">Filter:</label>
                <select id="statusFilter" class="filter-input">
                    <option value="">All Proposals</option>
                    <option value="PRIORITIZE">High Priority</option>
                    <option value="RECOMMEND">Standard</option>
                </select>
            </div>
            <div class="filter-group">
                <input type="text" id="searchFilter" class="filter-input" placeholder="Search by title or researcher...">
            </div>
        </div>

        <!-- Available Proposals Section -->
        <div class="available-proposals-section">
            <div class="available-header">
                <i class='bx bx-list-ul'></i> Available Proposals to Rank
                <span id="proposalCount" class="proposal-count">0 proposals</span>
            </div>
            <div id="availableProposals" class="available-proposals-container">
                <div class="empty-message">No proposals available for review</div>
            </div>
        </div>

        <!-- Budget Summary Section -->
        <div class="budget-summary">
            <div class="budget-summary-title">
                <i class='bx bx-wallet'></i> Budget Summary for Top Tier Approvals
            </div>
            <div class="budget-row">
                <span>Department Budget Balance:</span>
                <span id="deptBalance" style="font-weight: 700;">$<?= number_format($department_balance, 2) ?></span>
            </div>
            <div class="budget-row">
                <span>Total Top Tier Approved Amount:</span>
                <span id="topTierTotal" style="font-weight: 700;">$0.00</span>
            </div>
            <div class="budget-row">
                <span>Remaining Budget:</span>
                <span id="remainingBudget" style="font-weight: 700; color: #1b5e20;">$<?= number_format($department_balance, 2) ?></span>
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
                        <span class="tier-budget" id="topBudget">$0.00</span>
                    </div>
                    <div class="tier-items" id="topTier" data-tier="top" ondrop="allowDrop(event)" ondragover="dragOver(event)" ondragleave="dragLeave(event)">
                        <div class="empty-message">Drag proposals here</div>
                    </div>
                </div>

                <!-- MIDDLE TIER -->
                <div class="tier-section">
                    <div class="tier-label tier-middle">
                        <span><i class='bx bx-trending-up'></i> MIDDLE TIER</span>
                        <span class="tier-budget" id="middleBudget">$0.00</span>
                    </div>
                    <div class="tier-items" id="middleTier" data-tier="middle" ondrop="allowDrop(event)" ondragover="dragOver(event)" ondragleave="dragLeave(event)">
                        <div class="empty-message">Drag proposals here</div>
                    </div>
                </div>

                <!-- BOTTOM TIER -->
                <div class="tier-section">
                    <div class="tier-label tier-bottom">
                        <span><i class='bx bx-trending-down'></i> BOTTOM TIER</span>
                        <span class="tier-budget" id="bottomBudget">$0.00</span>
                    </div>
                    <div class="tier-items" id="bottomTier" data-tier="bottom" ondrop="allowDrop(event)" ondragover="dragOver(event)" ondragleave="dragLeave(event)">
                        <div class="empty-message">Drag proposals here</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn-reset" onclick="resetTiers()">
                <i class='bx bx-reset'></i> Reset Tiers
            </button>
            <button id="approveAllBtn" class="btn-approve-all" onclick="approveAllTopTier()" disabled>
                <i class='bx bx-check-circle'></i> Approve All Top Tier
            </button>
        </div>
    </section>

    <!-- Rubric Modal -->
    <div id="rubricModal" class="rubric-modal">
        <div class="rubric-content">
            <div class="rubric-header">
                <h2 class="rubric-title" id="rubricProposalTitle">Proposal Title</h2>
                <button class="rubric-close" onclick="closeRubricModal()">&times;</button>
            </div>

            <div style="margin-bottom: 20px;">
                <p style="font-size: 13px; color: #666;">
                    <strong>Researcher:</strong> <span id="rubricResearcher">-</span><br>
                    <strong>Requested Budget:</strong> <span id="rubricBudget">-</span>
                </p>
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
                        <input type="text" id="requestedBudget" disabled>
                    </div>
                    <div class="budget-field">
                        <label>Approved Amount</label>
                        <input type="number" id="approvedBudget" step="0.01" min="0">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="rubric-notes">
                <label class="rubric-notes-label">Reviewer Feedback</label>
                <textarea class="rubric-notes-text" id="rubricNotes" readonly></textarea>
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
        let deptBalance = <?= $department_balance ?>;
        let currentProposalId = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeProposals();
            updateBudgetDisplay();
        });

        // Initialize proposals into unassigned pool
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
            div.className = 'tier-item';
            div.draggable = true;
            div.dataset.proposalId = proposal.id;
            div.dataset.budget = proposal.grant_amount || 0;

            const priorityBadge = proposal.status === 'PRIORITIZE' 
                ? '<span class="priority-badge high">High Priority</span>' 
                : '<span class="priority-badge low">Standard</span>';

            div.innerHTML = `
                <div class="tier-item-content">
                    <div class="tier-item-title">${escapeHtml(proposal.title)}</div>
                    <div class="tier-item-researcher">${escapeHtml(proposal.researcher_name || proposal.researcher_email)}</div>
                    <div style="font-size: 12px; color: #999; margin-top: 5px;">
                        Requested: $${parseFloat(proposal.grant_amount || 0).toFixed(2)}
                    </div>
                </div>
                <div class="tier-item-actions">
                    <button class="tier-item-btn" onclick="openRubricModal(${proposal.id})" title="Edit Rubric & Budget">
                        <i class='bx bx-edit'></i> Edit
                    </button>
                </div>
            `;

            // Add drag event listeners
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
            document.getElementById('rubricBudget').textContent = `$${parseFloat(proposal.grant_amount || 0).toFixed(2)}`;
            document.getElementById('requestedBudget').value = `$${parseFloat(proposal.grant_amount || 0).toFixed(2)}`;
            document.getElementById('rubricNotes').value = proposal.reviewer_feedback || '';
            document.getElementById('approvedBudget').value = budgetAdjustments[proposalId] || proposal.grant_amount || 0;

            // Initialize rating stars
            initializeRatingStars(proposalId);

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
            });
        }

        function setRating(aspect, rating) {
            if (!currentProposalId) return;

            if (!rubricScores[currentProposalId]) {
                rubricScores[currentProposalId] = {};
            }
            rubricScores[currentProposalId][aspect] = rating;

            document.getElementById(`score-${aspect}`).textContent = rating;

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
            budgetAdjustments[currentProposalId] = approvedBudget;

            showAlert('Success', 'Rubric saved successfully!', 'success');
            closeRubricModal();
            updateBudgetDisplay();
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
            document.getElementById('topBudget').textContent = `$${topTierTotal.toFixed(2)}`;
            document.getElementById('middleBudget').textContent = `$${middleTierTotal.toFixed(2)}`;
            document.getElementById('bottomBudget').textContent = `$${bottomTierTotal.toFixed(2)}`;
            document.getElementById('topTierTotal').textContent = `$${topTierTotal.toFixed(2)}`;

            const remaining = deptBalance - topTierTotal;
            document.getElementById('remainingBudget').textContent = `$${remaining.toFixed(2)}`;

            // Show warning if exceeds budget
            const warningBox = document.getElementById('budgetWarning');
            const approveBtn = document.getElementById('approveAllBtn');

            if (topTierTotal > deptBalance) {
                warningBox.style.display = 'block';
                approveBtn.disabled = true;
            } else {
                warningBox.style.display = 'none';
                approveBtn.disabled = topTier.querySelectorAll('.tier-item').length === 0;
            }
        }

        // Action Functions
        function resetTiers() {
            ['topTier', 'middleTier', 'bottomTier'].forEach(tierId => {
                document.getElementById(tierId).innerHTML = '<div class="empty-message">Drag proposals here</div>';
            });
            tierAssignments = {};
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
    </script>
</body>
</html>
