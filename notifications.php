<?php
session_start();
require 'config.php';

// 1. SECURITY: Check Login
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email'];
$message = "";

// 2. LOGIC: Mark as Read / Delete
if (isset($_POST['mark_read'])) {
    $id = $_POST['notif_id'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_email = ?");
    $stmt->bind_param("is", $id, $email);
    $stmt->execute();
}

if (isset($_POST['delete_notif'])) {
    $id = $_POST['notif_id'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_email = ?");
    $stmt->bind_param("is", $id, $email);
    $stmt->execute();
    $message = "Notification removed.";
}

// 3. FETCH NOTIFICATIONS
// Get all messages for this user, newest first
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_email = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications | RGMS</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        
        <div class="settings-container">
            <h1>Notifications</h1>
            <p style="margin-bottom: 30px;">Alerts and updates for your account.</p>

            <?php if ($message): ?>
                <div class="alert success" style="margin-bottom: 20px; padding: 10px; background: #d4edda; color: #155724; border-radius: 5px;">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="notif-list">
                
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        
                        <div class="notif-card <?= $row['is_read'] == 0 ? 'unread' : '' ?>">
                            <div class="notif-icon">
                                <i class='bx bx-bell'></i>
                            </div>
                            <div class="notif-content">
                                <p class="notif-msg"><?= htmlspecialchars($row['message']) ?></p>
                                <span class="notif-time"><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></span>
                            </div>
                            <div class="notif-actions">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="notif_id" value="<?= $row['id'] ?>">
                                    
                                    <?php if ($row['is_read'] == 0): ?>
                                        <button type="submit" name="mark_read" class="btn-icon" title="Mark as Read">
                                            <i class='bx bx-check-circle'></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="submit" name="delete_notif" class="btn-icon delete" title="Delete">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #666; margin-top: 50px;">
                        <i class='bx bx-bell-off' style="font-size: 40px; margin-bottom: 10px;"></i>
                        <p>No new notifications.</p>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </section>

</body>
</html>