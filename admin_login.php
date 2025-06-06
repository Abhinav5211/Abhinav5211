<?php
// IMPORTANT: Ensure there is no whitespace before this tag or in included files (db.php, header.php)
session_start();
include 'db.php';
$message = '';
$type = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM customers WHERE username = ? AND role = 'admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['admin_id'] = $id;
            $_SESSION['customer_id'] = $id; // For dashboard compatibility
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'admin';
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Wrong username or password.";
            $type = 'error';
        }
    } else {
        $message = "Wrong username or password.";
        $type = 'error';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container">
    <h2>Admin Login</h2>
    <form method="POST">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit">Login</button>
    </form>
    <a href="customer_login.php">Customer Login</a>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
<?php if ($message): ?>
<script>showAlert("<?php echo addslashes($message); ?>", "<?php echo $type; ?>");</script>
<?php endif; ?>
</body>
</html> 