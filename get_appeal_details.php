<?php
session_start();
require 'config.php';
require 'activity_helper.php';

// Verify HOD access
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'hod') {
    http_response_code(403);
    die('Unauthorized');
}

$proposal_id = intval($_POST['proposal_id'] ?? 0);
$appeal_id = intval($_POST['appeal_id'] ?? 0);

if ($proposal_id <= 0 || $appeal_id <= 0) {
    die('Invalid parameters');
}

// Fetch HOD id for rubric lookups
$hod_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'hod' LIMIT 1");
$hod_stmt->bind_param("s", $_SESSION['email']);
$hod_stmt->execute();
$hod_row = $hod_stmt->get_result()->fetch_assoc();
$hod_id = $hod_row['id'] ?? 0;

// Fetch appeal and proposal details
$detail_sql = "
    SELECT 
        p.id AS proposal_id,
        p.title,
        p.researcher_email,
        p.file_path,
        p.created_at,
        p.budget_requested,
        ar.id AS appeal_id,
        ar.justification,
        ar.submitted_at,
        pr.total_score,
        pr.hod_notes
    FROM appeal_requests ar
    JOIN proposals p ON ar.proposal_id = p.id
    LEFT JOIN proposal_rubric pr ON pr.proposal_id = p.id AND pr.hod_id = ?
    WHERE ar.id = ? AND ar.proposal_id = ?
    LIMIT 1
";

$detail_stmt = $conn->prepare($detail_sql);
$detail_stmt->bind_param("iii", $hod_id, $appeal_id, $proposal_id);
$detail_stmt->execute();
$row = $detail_stmt->get_result()->fetch_assoc();

if (!$row) {
    die('Appeal not found');

    // ✅ LOG: HOD viewed appeal details (runs only when row exists)
    log_activity(
        $conn,
        "VIEW",
        "APPEAL_REQUEST",
        (int)$appeal_id,
        "View Appeal Details",
        "HOD viewed appeal #$appeal_id for proposal #$proposal_id ({$row['title']})"
    );

}

// Fetch all reviewer feedback
$history_sql = "
    SELECT r.feedback, r.decision, r.review_date, r.annotated_file, COALESCE(r.type, 'Proposal') AS review_type, u.name AS reviewer_name
    FROM reviews r
    LEFT JOIN users u ON r.reviewer_id = u.id
    WHERE r.proposal_id = ?
    ORDER BY r.review_date DESC, r.id DESC
";

$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $proposal_id);
$history_stmt->execute();
$rev_history = $history_stmt->get_result();
?>

<span class="rubric-close" onclick="closeAppealModal()">&times;</span>

<div class="rubric-header">
    <div>
        <h2 class="rubric-title"><?= htmlspecialchars($row['title']) ?></h2>
        <p class="appeal-subtitle">Proposal #<?= (int)$row['proposal_id'] ?> • Appeal Submitted: <?= date('M d, Y', strtotime($row['submitted_at'])) ?></p>
    </div>
    <span class="appeal-badge">
        <i class='bx bx-error-circle'></i> Appeal Pending
    </span>
</div>

<div class="rubric-layout">
    <!-- Left Panel: Proposal Display -->
    <div class="proposal-panel">
        <div class="proposal-meta">
            <div><strong>Researcher:</strong> <?= htmlspecialchars($row['researcher_email']) ?></div>
            <div><strong>Requested Budget:</strong> RM<?= number_format((float)($row['budget_requested'] ?? 0), 2) ?></div>
        </div>
        <div class="proposal-viewer">
            <iframe src="<?= htmlspecialchars($row['file_path']) ?>" class="appeal-proposal-iframe"></iframe>
        </div>
    </div>

    <!-- Right Panel: Appeal Details & Evaluation History -->
    <div class="rubric-panel">
        <div class="appeal-modal-section">
            <div class="section-heading">
                <i class='bx bx-comment-detail'></i> Researcher Justification
            </div>
            <p class="justification-text"><?= nl2br(htmlspecialchars($row['justification'])) ?></p>
        </div>

        <div class="appeal-modal-section">
            <div class="section-heading">
                <i class='bx bx-history'></i> Evaluation History
            </div>
            <?php if ($rev_history && $rev_history->num_rows > 0): ?>
                <?php while ($rev = $rev_history->fetch_assoc()): ?>
                    <div class="evaluation-block">
                        <div class="eval-label">Reviewer Decision (<?= htmlspecialchars($rev['review_type']) ?>)</div>
                        <p class="eval-meta">By <?= htmlspecialchars($rev['reviewer_name'] ?? 'Reviewer') ?> • <?= htmlspecialchars($rev['decision'] ?? 'N/A') ?></p>
                        <?php if (!empty($rev['feedback'])): ?>
                            <p class="eval-text"><?= nl2br(htmlspecialchars($rev['feedback'])) ?></p>
                        <?php else: ?>
                            <p class="eval-text">No written feedback provided.</p>
                        <?php endif; ?>
                        <?php if (!empty($rev['annotated_file'])): ?>
                            <a class="eval-link" href="<?= htmlspecialchars($rev['annotated_file']) ?>" target="_blank">View annotated file</a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="evaluation-block">
                    <div class="eval-label">Reviewer Decision</div>
                    <p class="eval-text">No reviewer feedback recorded.</p>
                </div>
            <?php endif; ?>

            <div class="evaluation-block">
                <div class="eval-label">HOD Feedback & Score</div>
                <?php if (!empty($row['hod_notes']) || $row['total_score'] !== null): ?>
                    <p class="eval-meta">Total Score: <?= $row['total_score'] !== null ? (int)$row['total_score'] : 'N/A' ?></p>
                    <?php if (!empty($row['hod_notes'])): ?>
                        <p class="eval-text"><?= nl2br(htmlspecialchars($row['hod_notes'])) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="eval-text">No prior HOD evaluation saved.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="appeal-actions">
    <button type="button" class="btn-approve" onclick="submitAppealDecision(<?= (int)$row['proposal_id'] ?>, <?= (int)$row['appeal_id'] ?>, 'approve')">
        <i class='bx bx-check-circle'></i> Approve Appeal
    </button>
    <button type="button" class="btn-reject" onclick="submitAppealDecision(<?= (int)$row['proposal_id'] ?>, <?= (int)$row['appeal_id'] ?>, 'reject')">
        <i class='bx bx-block'></i> Uphold Rejection
    </button>
</div>
