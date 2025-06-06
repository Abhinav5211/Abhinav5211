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

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
if ($search) {
    $result = $conn->query("SELECT p.name, i.quantity FROM inventory i JOIN products p ON i.product_id = p.id WHERE p.name LIKE '%$search%'");
} else {
    $result = $conn->query("SELECT p.name, i.quantity FROM inventory i JOIN products p ON i.product_id = p.id");
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Availability</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ccc;
        }
        .content-container {
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <div class="content-container">
            <h2>Stock Availability</h2>
            <form method="GET" style="margin-bottom:16px; display:inline-flex; align-items:center; gap:6px;">
                <input type="text" name="search" placeholder="Search stock..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="padding:4px 8px; font-size:0.95em; border-radius:4px; border:1px solid #ccc; width:160px;">
                <button type="submit" style="padding:4px 12px; font-size:0.95em; border-radius:4px; background:#1abc9c; color:#fff; border:none; cursor:pointer;">Search</button>
            </form>
            <table>
                <tr><th>Product Name</th><th><?php echo ($role === 'admin') ? 'Quantity' : 'Availability'; ?></th></tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <?php
                            $qty = $row['quantity'];
                            if ($role === 'admin') {
                                if ($qty == 0) {
                                    echo "<span style='color:red;'>$qty (Out of Stock)</span>";
                                } elseif ($qty <= 5) {
                                    echo "<span style='color:orange;'>$qty (Low)</span>";
                                } else {
                                    echo "<span style='color:green;'>$qty</span>";
                                }
                            } else {
                                echo ($qty > 0) ? 'Available' : 'Not Available';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
</body>
</html>
