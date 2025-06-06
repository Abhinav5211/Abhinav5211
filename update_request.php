<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';
include 'header.php'; // ✅ included here

// Ensure only customers can submit update requests
if (!isset($_SESSION['customer_id'])) {
    echo "Only customers can submit requests.";
    exit();
}

$customer_id = $_SESSION['customer_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    if ($content !== '') {
        $stmt = $conn->prepare("INSERT INTO requests (customer_id, content, status, created_at) VALUES (?, ?, 'Pending', NOW())");
        $stmt->bind_param("is", $customer_id, $content);
        if ($stmt->execute()) {
            $message = "Request submitted successfully.";
        } else {
            $message = "Failed to submit request. Please try again.";
        }
    } else {
        $message = "Request content cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Update Request</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <div class="content-container">
            <h2>Submit Update Request</h2>
            <?php if ($message): ?>
                <p style="color: green;"><?php echo $message; ?></p>
            <?php endif; ?>
            <form method="POST">
                <textarea name="content" rows="6" style="width:100%; padding:10px;" placeholder="Enter your update request here..."></textarea><br><br>
                <button type="submit" style="padding: 10px 24px;">Submit Request</button>
            </form>
            <br>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
</body>
</html>
