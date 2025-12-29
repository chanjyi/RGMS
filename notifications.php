<?php
session_start();
require 'config.php';

// 1. SECURITY
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email'];
$message = "";

// 2. LOGIC: Mark Read / Delete
if (isset($_POST['mark_read'])) {
    $id = $_POST['notif_id'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_email = ?");
    $stmt->bind_param("is", $id, $email);
    $stmt->execute();
    header("Location: notifications.php");
    exit();
}

if (isset($_POST['delete_notif'])) {
    $id = $_POST['notif_id'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_email = ?");
    $stmt->bind_param("is", $id, $email);
    $stmt->execute();
    $message = "Notification removed.";
}

// 3. FETCH NOTIFICATIONS
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
        <div class="welcome-text">Notifications Center</div>
        <hr style="opacity: 0.3; margin: 20px 0;">

        <?php if ($message): ?>
            <div class="alert" style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="notif-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    
                    <?php 
                        $is_appeal = stripos($row['message'], 'appeal') !== false;
                        $card_class = $is_appeal ? 'appeal' : '';
                        
                        if ($row['is_read'] == 1) {
                            $card_class .= ' read';
                        }

                        $icon = $is_appeal ? 'bx-error-circle' : 'bx-bell';
                        if ($row['is_read'] == 1) $icon = 'bx-check-circle';
                    ?>

                    <div class="notif-card <?= $card_class ?>">
                        <div class="notif-icon">
                            <i class='bx <?= $icon ?>'></i>
                        </div>
                        
                        <div class="notif-content">
                            <p class="notif-msg"><?= htmlspecialchars($row['message']) ?></p>
                            <span class="notif-time">
                                <?= date('M d, h:i A', strtotime($row['created_at'])) ?>
                            </span>
                        </div>

                        <div class="notif-actions">
                            <form method="POST">
                                <input type="hidden" name="notif_id" value="<?= $row['id'] ?>">
                                
                                <?php if ($row['is_read'] == 0): ?>
                                    <button type="submit" name="mark_read" class="btn-icon" title="Mark as Read">
                                        <i class='bx bx-check'></i>
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
                <div style="text-align: center; color: #999; margin-top: 50px;">
                    <i class='bx bx-bell-off' style="font-size: 50px;"></i>
                    <p>No notifications yet.</p>
                </div>
            <?php endif; ?>
        </div>

    </section>

</body>
</html>