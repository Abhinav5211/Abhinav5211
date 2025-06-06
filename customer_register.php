<?php
session_start();
include 'db.php';
$message = '';
$type = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = 'customer';
    $name     = $_POST['name'];
    $email    = $_POST['email'];

    // Check if username already exists
    $check = $conn->prepare("SELECT id FROM customers WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        $message = "Username already exists. Please choose a different username.";
        $type = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO customers (username, password, role, name, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $password, $role, $name, $email);

        if ($stmt->execute()) {
            $message = "Registration successful! You can now login.";
            $type = 'success';
            header("Location: customer_login.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
            $type = 'error';
        }
        $stmt->close();
    }
    $check->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container">
    <h2>Customer Registration</h2>
    <form method="POST" class="registration-form">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <button type="submit" class="btn">Register</button>
    </form>
    <p style="text-align: center; margin-top: 20px;">
        Already have an account? <a href="customer_login.php">Login here</a>
    </p>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
<?php if ($message): ?>
<script>showAlert("<?php echo addslashes($message); ?>", "<?php echo $type; ?>");</script>
<?php endif; ?>
</body>
</html> 