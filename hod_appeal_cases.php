<?php
session_start();
require 'config.php';

// Verify HOD access
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    header('Location: index.php');
    exit();
}

// Fetch HOD id for rubric lookups
$hod_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'hod' LIMIT 1");
$hod_stmt->bind_param("s", $_SESSION['email']);
$hod_stmt->execute();
$hod_result = $hod_stmt->get_result();
$hod_row = $hod_result->fetch_assoc();
$hod_id = $hod_row['id'] ?? 0;

$banner = ['type' => '', 'text' => ''];

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
                    $banner = ['type' => 'success', 'text' => 'Appeal approved and sent to admin for reassignment.'];
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
                    $banner = ['type' => 'error', 'text' => 'Appeal dismissed. Researcher notified.'];
                }
            } catch (Exception $e) {
                $conn->rollback();
                $banner = ['type' => 'error', 'text' => 'Error processing appeal: ' . $e->getMessage()];
            }
        }
    } else {
        $banner = ['type' => 'error', 'text' => 'Missing appeal identifiers.'];
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

// Preload reviewer history statement for reuse
$history_stmt = $conn->prepare(
    "SELECT r.feedback, r.decision, r.review_date, r.annotated_file, COALESCE(r.type, 'Proposal') AS review_type, u.name AS reviewer_name
     FROM reviews r
     LEFT JOIN users u ON r.reviewer_id = u.id
     WHERE r.proposal_id = ?
     ORDER BY r.review_date DESC, r.id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appeal Cases - RGMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="hod_pages.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <section class="home-section">
        <div class="welcome-text">
            <i class='bx bx-message-square-dots'></i>
            Appeal Cases Management
        </div>

        <?php if (!empty($banner['text'])): ?>
            <div class="alert <?= $banner['type'] === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($banner['text']) ?>
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

            <script>
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
                
                console.log('Sending request to hod_appeal_cases.php');

                fetch('hod_appeal_cases.php', {
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
                    window.location.href = 'hod_appeal_cases.php';
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
        <?php endif; ?>
    </section>
</body>
</html>
