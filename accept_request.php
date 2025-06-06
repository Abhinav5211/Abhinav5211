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
if (isset($_GET['accept'])) {
    $id = intval($_GET['accept']);
    $stmt = $conn->prepare("UPDATE requests SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $message = "Request accepted!";
    $type = 'success';
}
$result = $conn->query("SELECT r.id, c.username, r.request_type, r.details, r.status FROM requests r JOIN customers c ON r.customer_id = c.id WHERE r.status = 'pending'");
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Update Requests</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <div class="form-container">
            <h2>Accept Update Request</h2>
            <table border="1">
                <tr><th>ID</th><th>Customer</th><th>Type</th><th>Details</th><th>Status</th><th>Action</th></tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['request_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['details']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><a href="accept_request.php?accept=<?php echo $row['id']; ?>">Accept</a></td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
<?php if ($message): ?>
<script>showAlert("<?php echo addslashes($message); ?>", "<?php echo $type; ?>");</script>
<?php endif; ?>
</body>
</html> 