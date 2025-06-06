<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
include 'db.php';
$message = '';
$type = 'success';
$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;

// Mark all notifications as read for this customer
if ($customer_id) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE customer_id = $customer_id");
    $result = $conn->query("SELECT * FROM notifications WHERE customer_id = $customer_id OR customer_id IS NULL ORDER BY created_at DESC");
} else {
    // For admin, show all notifications
    $result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Notifications</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <div class="content-container">
            <h2>Notifications</h2>
            <table border="1">
                <tr><th>Title</th><th>Message</th><th>Date</th><th>Status</th></tr>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['message']); ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td><?php echo $row['is_read'] ? 'Read' : 'Unread'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4">No notifications found.</td></tr>
                <?php endif; ?>
            </table>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
</body>
</html> 