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
    
    // Redirect Logic (Optional: keeps user on same page but clears post data)
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

// 3. FETCH ALL NOTIFICATIONS (Single List)
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
    <style>
        /* Container for the list */
        .notif-container {
            max-width: 800px;
            margin: 0 auto; /* Center the list */
        }

        /* Default Card Style (Blue / Normal) */
        .notif-card {
            display: flex;
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 5px solid #007bff; /* Blue Border Default */
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            align-items: center;
            transition: transform 0.2s;
        }
        
        .notif-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Red Style for Appeals */
        .notif-card.appeal {
            border-left-color: #dc3545; /* Red Border */
            background: #fff5f5; /* Light Red Background hint */
        }

        /* Icon Styles */
        .notif-icon {
            font-size: 24px;
            margin-right: 15px;
            color: #007bff; /* Blue Icon Default */
        }
        .notif-card.appeal .notif-icon {
            color: #dc3545; /* Red Icon */
        }

        /* Text Styles */
        .notif-content { flex: 1; }
        .notif-msg { margin: 0; font-size: 15px; color: #333; font-weight: 500; }
        .notif-time { font-size: 12px; color: #888; display: block; margin-top: 5px; }

        /* Read vs Unread Visuals */
        .notif-card.read {
            opacity: 0.7;
            background: #f9f9f9;
            border-left-color: #ccc !important; /* Grey out border if read */
        }
        .notif-card.read .notif-icon { color: #aaa !important; }

        /* Action Buttons */
        .notif-actions { display: flex; gap: 10px; }
        .btn-icon { background: none; border: none; cursor: pointer; font-size: 20px; color: #888; transition: 0.3s; }
        .btn-icon:hover { color: #007bff; }
        .btn-icon.delete:hover { color: #dc3545; }
    </style>
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
                        // LOGIC: Check if it is an appeal to change color
                        // stripos returns position or false. If not false, "Appeal" exists.
                        $is_appeal = stripos($row['message'], 'appeal') !== false;
                        $card_class = $is_appeal ? 'appeal' : '';
                        
                        // Add 'read' class if is_read = 1
                        if ($row['is_read'] == 1) {
                            $card_class .= ' read';
                        }

                        // Icon Logic
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