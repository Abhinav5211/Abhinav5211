<?php
// login.php
session_start();
include 'db.php';
$message = '';
$type = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM customers WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $role);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['customer_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Invalid password.";
            $type = 'error';
        }
    } else {
        $message = "Customer not found.";
        $type = 'error';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container" style="text-align:center;max-width:400px;margin:auto;">
    <h2>Welcome to Store Billing</h2>
    <p>Please select your login type:</p>
    <a href="admin_login.php" class="btn" style="margin:10px 0;display:block;">Admin Login</a>
    <a href="customer_login.php" class="btn" style="margin:10px 0;display:block;">Customer Login</a>
    <br>
    <div style="margin-top:20px;">
        <p>New to the system? Register here:</p>
        <a href="admin_register.php" class="btn" style="margin:10px 0;display:block;">Register as Admin</a>
        <a href="customer_register.php" class="btn" style="margin:10px 0;display:block;">Register as Customer</a>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
<?php if ($message): ?>
<script>showAlert("<?php echo addslashes($message); ?>", "<?php echo $type; ?>");</script>
<?php endif; ?>
</body>
</html> 