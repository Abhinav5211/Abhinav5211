<?php
session_start();
include 'db.php';
$message = '';
$type = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM customers WHERE username = ? AND role = 'customer'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['customer_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'customer';
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
    <title>Customer Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container">
    <h2>Customer Login</h2>
    <form method="POST">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit">Login</button>
    </form>
    <a href="admin_login.php">Admin Login</a>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
<?php if ($message): ?>
<script>showAlert("<?php echo addslashes($message); ?>", "<?php echo $type; ?>");</script>
<?php endif; ?>
</body>
</html> 