<?php
session_start();
require 'config.php'; // Make sure you include your database connection!

// 1. SECURITY: Check if Admin
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

$message = "";

// 2. LOGIC: Handle the "Assign" Button
if (isset($_POST['assign_proposal'])) {
    $proposal_title = $_POST['proposal_title'];
    $reviewer_email = $_POST['reviewer_email'];

    // A. (Optional) In a real app, you would insert the proposal into a 'proposals' table here.
    // For this demo, we skip that and just send the notification.

    // B. SEND NOTIFICATION
    // Check if the user has System Notifications turned ON (default is 1)
    $check_pref = $conn->prepare("SELECT notify_system FROM users WHERE email = ?");
    $check_pref->bind_param("s", $reviewer_email);
    $check_pref->execute();
    $pref = $check_pref->get_result()->fetch_assoc();

    // If preference is 1 (ON) or not set (default), send the alert
    if (!$pref || $pref['notify_system'] == 1) {
        $notif_msg = "New Assignment: You have been assigned to review '" . $proposal_title . "'";
        
        $stmt = $conn->prepare("INSERT INTO notifications (user_email, message) VALUES (?, ?)");
        $stmt->bind_param("ss", $reviewer_email, $notif_msg);
        
        if ($stmt->execute()) {
            $message = "Proposal assigned & notification sent to " . $reviewer_email;
        } else {
            $message = "Error sending notification.";
        }
    } else {
        $message = "Proposal assigned (User has notifications muted).";
    }
}

// 3. GET REVIEWERS: Fetch list of users with role 'Reviewer' for the dropdown
// (Assuming your users table has a 'role' column. If not, remove the 'WHERE role...' part)
$reviewers = $conn->query("SELECT * FROM users WHERE role = 'Reviewer'"); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        
        <div class="welcome-text">
            Welcome <?= $_SESSION['name']; ?>, 
            <span>(<?= isset($_SESSION['role']) ? $_SESSION['role'] : 'User'; ?>)</span>
        </div>

        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin: 20px 0;">

        <?php if ($message): ?>
            <div class="alert success" style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div style="background: #EFE6D5; padding: 30px; border-radius: 20px; max-width: 600px;">
            <h2 style="color: #153448; margin-bottom: 20px;">Assign Proposal to Reviewer</h2>
            
            <form method="POST" action="">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Proposal Title</label>
                    <input type="text" name="proposal_title" placeholder="e.g. AI in Healthcare Research" required 
                           style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Select Reviewer</label>
                    <select name="reviewer_email" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                        <option value="">-- Choose a Reviewer --</option>
                        <?php while($row = $reviewers->fetch_assoc()): ?>
                            <option value="<?= $row['email'] ?>">
                                <?= $row['name'] ?> (<?= $row['email'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" name="assign_proposal" class="btn-save" 
                        style="background: #3C5B6F; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                    Assign Proposal
                </button>

            </form>
        </div>

    </section>

</body>
</html>