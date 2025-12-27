<?php
session_start();
require 'config.php';

// Security Check
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'researcher') {
    header('Location: index.php');
    exit();
}

$message = "";
$email = $_SESSION['email'];

// ==========================================
// 1. HANDLE NEW UPLOAD (Admin Notification Included)
// ==========================================
if (isset($_POST['submit_proposal'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_name = basename($_FILES["proposal_file"]["name"]);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $clean_email = str_replace(['@', '.'], '_', $email);
    $new_file_name = "prop_" . time() . "_" . $clean_email . "." . $file_type;
    $target_file = $target_dir . $new_file_name;

    if ($file_type != "pdf") {
        $message = "Error: Only PDF files are allowed.";
    } else {
        if (move_uploaded_file($_FILES["proposal_file"]["tmp_name"], $target_file)) {
            // A. Insert Proposal
            $stmt = $conn->prepare("INSERT INTO proposals (title, researcher_email, file_path, status) VALUES (?, ?, ?, 'SUBMITTED')");
            $stmt->bind_param("sss", $title, $email, $target_file);
            
            if ($stmt->execute()) {
                // B. Notify Admin
                $admin_query = $conn->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
                if ($admin_query->num_rows > 0) {
                    $admin_email = $admin_query->fetch_assoc()['email'];
                    $n_stmt = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
                    $msg = "New Proposal Submitted: '$title' by $email";
                    $n_stmt->bind_param("ss", $admin_email, $msg);
                    $n_stmt->execute();
                }
                $message = "Success! Proposal submitted and Admin notified.";
            } else {
                $message = "Database Error: " . $conn->error;
            }
        } else {
            $message = "Error uploading file.";
        }
    }
}

// ==========================================
// 2. HANDLE APPEAL LOGIC
// ==========================================
if (isset($_POST['appeal_proposal'])) {
    $prop_id = $_POST['proposal_id'];
    $prop_title = $_POST['proposal_title'];

    // Update Status
    $stmt = $conn->prepare("UPDATE proposals SET status = 'APPEALED' WHERE id = ?");
    $stmt->bind_param("i", $prop_id);
    
    if ($stmt->execute()) {
        // Notify Admin
        $admin_query = $conn->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
        if ($admin_query->num_rows > 0) {
            $admin_email = $admin_query->fetch_assoc()['email'];
            $msg = "Appeal Request: Researcher ($email) has appealed the rejection of '$prop_title'.";
            $notif = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
            $notif->bind_param("ss", $admin_email, $msg);
            $notif->execute();
        }
        $message = "Appeal submitted successfully.";
    }
}

// ==========================================
// 3. FETCH PROPOSALS (With Reviewer Decision)
// ==========================================
// We JOIN with 'reviews' table to see if the reviewer said 'RECOMMEND' or 'REJECT'
$sql = "SELECT p.*, r.decision as reviewer_decision 
        FROM proposals p 
        LEFT JOIN reviews r ON p.id = r.proposal_id 
        WHERE p.researcher_email = '$email' 
        ORDER BY p.created_at DESC";

$my_props = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Researcher Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="home-section">
        <div class="welcome-text">
            Welcome, <?= $_SESSION['name']; ?>
        </div>
        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin-bottom: 20px;">

        <?php if ($message): ?>
            <div class="alert success" style="background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:5px;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="form-box" style="margin-bottom: 30px;">
            <h2 style="margin-bottom: 20px;">Submit New Proposal</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="input-group">
                    <label>Proposal Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="input-group">
                    <label>Upload PDF (PDF only)</label>
                    <input type="file" name="proposal_file" accept=".pdf" required>
                </div>
                <button type="submit" name="submit_proposal" class="btn-save">Submit</button>
            </form>
        </div>

        <h3>My Proposals</h3>
        <div style="overflow-x:auto;">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($my_props->num_rows > 0): ?>
                        <?php while($row = $my_props->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td>
                                    <span class="status-badge <?= strtolower($row['status']) ?>">
                                        <?= $row['status'] == 'ASSIGNED' ? 'Assigned (Pending)' : $row['status'] ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                <td>
    <?php 
        // CASE 1: Normal Rejection by Reviewer -> Can Appeal
        if ($row['status'] == 'REJECTED' && $row['reviewer_decision'] == 'REJECT'): 
    ?>
        <form method="POST" onsubmit="return confirm('Are you sure you want to appeal this decision?');">
            <input type="hidden" name="proposal_id" value="<?= $row['id'] ?>">
            <input type="hidden" name="proposal_title" value="<?= $row['title'] ?>">
            <button type="submit" name="appeal_proposal" 
                    style="background: #e74c3c; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 13px;">
                <i class='bx bx-redo'></i> Appeal
            </button>
        </form>

            <?php 
                // CASE 2: HOD Rejection -> Final (Cannot Appeal)
                elseif ($row['status'] == 'REJECTED' && $row['reviewer_decision'] == 'RECOMMEND'): 
            ?>
                <span style="color: #c0392b; font-size: 13px; font-weight: bold;">
                    <i class='bx bx-block'></i> Final Rejection (HOD)
                </span>

            <?php 
                // CASE 3: Appeal Rejected -> Final (Cannot Appeal Again)
                elseif ($row['status'] == 'APPEAL_REJECTED'): 
            ?>
                <span style="color: #c0392b; font-size: 13px; font-weight: bold;">
                    <i class='bx bx-x-circle'></i> Appeal Denied
                </span>

            <?php elseif ($row['status'] == 'APPEALED'): ?>
                <span style="color: #e67e22; font-size: 13px; font-weight: 500;">
                    <i class='bx bx-time-five'></i> Under Appeal
                </span>
                
            <?php else: ?>
                <span style="color: #999; font-size: 13px;">-</span>
            <?php endif; ?>
        </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;">No proposals found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>
</body>
</html>
