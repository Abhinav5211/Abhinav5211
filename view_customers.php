<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
include 'db.php';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($role === 'admin') {
    if ($search !== '') {
        $search_param = "%$search%";
        $stmt = $conn->prepare("SELECT id, username, name, email, role, created_at FROM customers WHERE username LIKE ? OR name LIKE ? OR email LIKE ? ORDER BY id DESC");
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT id, username, name, email, role, created_at FROM customers ORDER BY id DESC");
    }
} else {
    $customer_id = $_SESSION['customer_id'];
    if ($search !== '') {
        $search_param = "%$search%";
        $stmt = $conn->prepare("SELECT id, username, name, email, role, created_at FROM customers WHERE id = ? AND (username LIKE ? OR name LIKE ? OR email LIKE ?)");
        $stmt->bind_param("isss", $customer_id, $search_param, $search_param, $search_param);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT id, username, name, email, role, created_at FROM customers WHERE id = $customer_id");
    }
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Customers</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .content-container { max-width: 900px; margin: 32px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(44,62,80,0.08); padding: 32px; }
        .search-container { margin-bottom: 18px; display: flex; justify-content: flex-end; }
        .search-form input[type="text"] { padding: 6px 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1em; margin-right: 8px; }
        .search-form button { padding: 6px 18px; border-radius: 5px; background: #1abc9c; color: #fff; border: none; font-size: 1em; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #eaeaea; text-align: left; }
        th { background: #f3f6fa; color: #2d3e50; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        @media (max-width: 700px) { .content-container { padding: 12px 4px; } th, td { padding: 8px 4px; font-size: 0.98em; } }
    </style>
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <div class="content-container">
            <h2>Customer Details</h2>
            <div class="search-container">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>
            <table border="1">
                <tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Role</th><th>Created At</th></tr>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">No customer data found.</td></tr>
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