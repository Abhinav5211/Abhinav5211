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
// Fetch customers for dropdown
$customers = $conn->query("SELECT id, username FROM customers WHERE role = 'customer'");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message_text = $_POST['message'];
    
    if (isset($_POST['send_to_all']) && $_POST['send_to_all'] == '1') {
        // Send to all customers
        $stmt = $conn->prepare("INSERT INTO notifications (customer_id, message) SELECT id, ? FROM customers WHERE role = 'customer'");
        $stmt->bind_param("s", $message_text);
        if ($stmt->execute()) {
            $message = "Notification sent to all customers!";
            $type = 'success';
        } else {
            $message = "Error: " . $stmt->error;
            $type = 'error';
        }
    } else {
        // Send to specific customer
        $customer_id = $_POST['customer_id'];
        $stmt = $conn->prepare("INSERT INTO notifications (customer_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $customer_id, $message_text);
        if ($stmt->execute()) {
            $message = "Notification sent!";
            $type = 'success';
        } else {
            $message = "Error: " . $stmt->error;
            $type = 'error';
        }
    }
    $stmt->close();
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Notification</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <div class="form-container">
            <h2>Send Notification</h2>
            <form method="POST">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="send_to_all" value="1" id="send_to_all" onchange="toggleUserSelect()">
                        Send to all customers
                    </label>
                </div>
                <div id="user_select_container">
                    Customer: <select name="customer_id" id="user_select">
                        <?php while ($row = $customers->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['username']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    Message: <textarea name="message" required></textarea>
                </div>
                <button type="submit">Send</button>
            </form>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
<script>
function toggleUserSelect() {
    const sendToAll = document.getElementById('send_to_all');
    const userSelectContainer = document.getElementById('user_select_container');
    const userSelect = document.getElementById('user_select');
    
    if (sendToAll.checked) {
        userSelectContainer.style.display = 'none';
        userSelect.removeAttribute('required');
    } else {
        userSelectContainer.style.display = 'block';
        userSelect.setAttribute('required', 'required');
    }
}
</script>
<?php if ($message): ?>
<script>showAlert("<?php echo addslashes($message); ?>", "<?php echo $type; ?>");</script>
<?php endif; ?>
</body>
</html> 