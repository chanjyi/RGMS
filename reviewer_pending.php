<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'reviewer') {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email'];
$user_q = $conn->query("SELECT id FROM users WHERE email = '$email'");
$user_id = $user_q->fetch_assoc()['id'];

// 1. Fetch All Pending Reviews
$query = "SELECT r.id as review_id, p.title, p.file_path, p.researcher_email, r.status, r.type, p.status as proposal_status 
          FROM reviews r 
          JOIN proposals p ON r.proposal_id = p.id 
          WHERE r.reviewer_id = ? AND r.status = 'Pending'
          ORDER BY r.assigned_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// 2. Separate into Categories
$new_proposals = [];
$amendments = [];
$appeals = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if ($row['type'] == 'Appeal' || $row['proposal_status'] == 'APPEALED') {
            $appeals[] = $row;
        } elseif ($row['proposal_status'] == 'RESUBMITTED') {
            $amendments[] = $row;
        } else {
            $new_proposals[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Reviews</title>
    <link rel="stylesheet" href="styling/style.css">
    <link rel="stylesheet" href="styling/dashboard.css">
    <link rel="stylesheet" href="styling/reviewer.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        /* Specific Tab Styles for this page */
        .tab-btn {
            background: #fff;
            border: 1px solid #ddd;
            border-bottom: none;
            padding: 12px 25px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            color: #666;
            border-radius: 8px 8px 0 0;
            margin-right: 5px;
            transition: all 0.3s ease;
        }
        .tab-btn:hover { background: #f1f1f1; }
        
        .tab-btn.active {
            background: #3C5B6F;
            color: white;
            border-color: #3C5B6F;
        }

        /* Color-coded tabs */
        .tab-btn.amend-tab.active { background: #f39c12; border-color: #f39c12; }
        .tab-btn.appeal-tab.active { background: #dc3545; border-color: #dc3545; }

        .tab-content {
            display: none;
            background: #fff;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 0 8px 8px 8px;
            min-height: 300px;
        }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
        <div style="margin-bottom: 20px;">
            <a href="reviewer_page.php" class="btn-back"><i class='bx bx-arrow-back'></i> Back to Dashboard</a>
        </div>
        
        <div class="welcome-text">Assigned Proposals</div>
        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 25px;">

        <div style="margin-bottom: 0;">
            <button class="tab-btn active" onclick="openTab(event, 'tab_new')">
                <i class='bx bx-star'></i> New Proposals 
                <span style="font-size:12px; background:rgba(0,0,0,0.1); padding:2px 6px; border-radius:10px; margin-left:5px;"><?= count($new_proposals) ?></span>
            </button>
            <button class="tab-btn amend-tab" onclick="openTab(event, 'tab_amend')">
                <i class='bx bx-revision'></i> Amendments
                <span style="font-size:12px; background:rgba(0,0,0,0.1); padding:2px 6px; border-radius:10px; margin-left:5px;"><?= count($amendments) ?></span>
            </button>
            <button class="tab-btn appeal-tab" onclick="openTab(event, 'tab_appeal')">
                <i class='bx bx-error-circle'></i> Appeals
                <span style="font-size:12px; background:rgba(0,0,0,0.1); padding:2px 6px; border-radius:10px; margin-left:5px;"><?= count($appeals) ?></span>
            </button>
        </div>

        <div id="tab_new" class="tab-content active">
            <div class="card-container">
                <?php if (count($new_proposals) > 0): ?>
                    <?php foreach ($new_proposals as $row): ?>
                        <div class="card" style="width: 300px; padding: 20px; border-left: 5px solid #3C5B6F;">
                            <div style="margin-bottom: 10px;">
                                <span style='background:#3C5B6F; color:white; padding:4px 8px; font-size:11px; border-radius:4px; font-weight:bold;'>
                                    NEW PROPOSAL
                                </span>
                            </div>
                            <h3 style="margin: 0 0 10px 0; font-size: 18px; color: #333;"><?= htmlspecialchars($row['title']) ?></h3>
                            <p style="color: #666; font-size: 13px; margin-bottom: 20px;">
                                <i class='bx bx-user'></i> <?= htmlspecialchars($row['researcher_email']) ?>
                            </p>
                            <a href="review_proposal.php?review_id=<?= $row['review_id'] ?>" 
                               class="btn-save" style="width:100%; text-decoration:none; display:block; box-sizing:border-box;">
                               Start Review
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#999; padding:20px;">No new proposals assigned.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="tab_amend" class="tab-content">
            <div class="card-container">
                <?php if (count($amendments) > 0): ?>
                    <?php foreach ($amendments as $row): ?>
                        <div class="card" style="width: 300px; padding: 20px; border-left: 5px solid #f39c12; background: #fffbe6;">
                            <div style="margin-bottom: 10px;">
                                <span style='background:#f39c12; color:white; padding:4px 8px; font-size:11px; border-radius:4px; font-weight:bold;'>
                                    <i class='bx bx-revision'></i> AMENDMENT / V2
                                </span>
                            </div>
                            <h3 style="margin: 0 0 10px 0; font-size: 18px; color: #333;"><?= htmlspecialchars($row['title']) ?></h3>
                            <p style="color: #666; font-size: 13px; margin-bottom: 20px;">
                                <i class='bx bx-user'></i> <?= htmlspecialchars($row['researcher_email']) ?>
                            </p>
                            <a href="review_proposal.php?review_id=<?= $row['review_id'] ?>" 
                               class="btn-save" style="width:100%; background:#f39c12; text-decoration:none; display:block; box-sizing:border-box;">
                               Review Amendment
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#999; padding:20px;">No amendments pending.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="tab_appeal" class="tab-content">
            <div class="card-container">
                <?php if (count($appeals) > 0): ?>
                    <?php foreach ($appeals as $row): ?>
                        <div class="card" style="width: 300px; padding: 20px; border-left: 5px solid #dc3545; background: #fff5f5;">
                            <div style="margin-bottom: 10px;">
                                <span style='background:#dc3545; color:white; padding:4px 8px; font-size:11px; border-radius:4px; font-weight:bold;'>
                                    <i class='bx bx-error-circle'></i> APPEAL CASE
                                </span>
                            </div>
                            <h3 style="margin: 0 0 10px 0; font-size: 18px; color: #333;"><?= htmlspecialchars($row['title']) ?></h3>
                            <p style="color: #666; font-size: 13px; margin-bottom: 20px;">
                                <i class='bx bx-user'></i> <?= htmlspecialchars($row['researcher_email']) ?>
                            </p>
                            <a href="review_proposal.php?review_id=<?= $row['review_id'] ?>" 
                               class="btn-save" style="width:100%; background:#dc3545; text-decoration:none; display:block; box-sizing:border-box;">
                               Review Appeal
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#999; padding:20px;">No appeals assigned.</p>
                <?php endif; ?>
            </div>
        </div>

    </section>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            
            // 1. Hide all tab content
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            
            // 2. Remove 'active' class from all buttons
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            
            // 3. Show current tab and add 'active' class to button
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.className += " active";
        }
    </script>
</body>
</html>