<?php
session_start();
include 'db.php';
$message = '';
$type = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = 'admin';
    $name     = $_POST['name'];
    $email    = $_POST['email'];

    // Check for duplicate username
    $check_stmt = $conn->prepare("SELECT id FROM customers WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        $message = "Username already exists. Please choose another.";
        $type = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO customers (username, password, role, name, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $password, $role, $name, $email);

        if ($stmt->execute()) {
            $message = "Admin registration successful!";
            $type = 'success';
            header("Location: admin_login.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
            $type = 'error';
        }
        $stmt->close();
    }
    $check_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container">
    <h2>Admin Registration</h2>
    <form method="POST">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        Name: <input type="text" name="name"><br>
        Email: <input type="email" name="email"><br>
        <button type="submit">Register</button>
    </form>
    <a href="admin_login.php">Already have an account? Login</a>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
<?php if ($message): ?>
<script>showAlert("<?php echo addslashes($message); ?>", "<?php echo $type; ?>");</script>
<?php endif; ?>
</body>
</html> 