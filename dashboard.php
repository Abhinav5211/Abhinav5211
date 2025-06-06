<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
include 'db.php';
$role = $_SESSION['role'];
$username = $_SESSION['username'];
$customer_id = $_SESSION['customer_id'] ?? null;

function get_single_value($conn, $query) {
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_array()[0];
    }
    return 0;
}

$tiles = [];
if ($role == 'admin') {
    $tiles['Total Sales'] = 'Rs. ' . number_format(get_single_value($conn, "SELECT IFNULL(SUM(grand_total), 0) FROM bills"), 2);
    $tiles['Total Products'] = get_single_value($conn, "SELECT COUNT(*) FROM products");
    $tiles['Total Inventory Items'] = get_single_value($conn, "SELECT IFNULL(SUM(quantity),0) FROM inventory");
    $tiles['Total Customers'] = get_single_value($conn, "SELECT COUNT(*) FROM bills"); // 1 bill = 1 customer (including guests)
    $tiles['Total Suppliers'] = get_single_value($conn, "SELECT COUNT(*) FROM suppliers");
    $tiles['Pending Requests'] = get_single_value($conn, "SELECT COUNT(*) FROM requests WHERE status='pending'");
} else {
    $customer_query = "SELECT * FROM customers WHERE id = ?";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();

    if ($customer) {
        $tiles['Username'] = htmlspecialchars($customer['username']);
        $tiles['Name'] = htmlspecialchars($customer['name']);
        $tiles['Email'] = htmlspecialchars($customer['email']);
    } else {
        $tiles['Username'] = 'N/A';
        $tiles['Name'] = 'N/A';
        $tiles['Email'] = 'N/A';
    }

    $tiles['Total Spending'] = 'Rs. ' . number_format(get_single_value($conn, "SELECT IFNULL(SUM(grand_total),0) FROM bills WHERE customer_id = $customer_id"), 2);
    $tiles['Number of Purchases'] = get_single_value($conn, "SELECT COUNT(*) FROM bills WHERE customer_id = $customer_id");
    $tiles['Last Purchase'] = get_single_value($conn, "SELECT IFNULL(MAX(created_at),'N/A') FROM bills WHERE customer_id = $customer_id");
}

if ($role == 'admin') {
    $recent_bills_query = "SELECT b.id, b.grand_total, b.created_at, COALESCE(c.name, b.guest_name) as customer_name 
                          FROM bills b 
                          LEFT JOIN customers c ON b.customer_id = c.id 
                          ORDER BY b.created_at DESC LIMIT 5";
    $recent_bills = $conn->query($recent_bills_query);
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .main {
            transition: margin-left 0.3s;
            padding: 24px;
        }
        @media (max-width: 600px) {
            .main { padding: 12px; }
        }
        .dashboard-tiles {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-bottom: 32px;
        }
        .dashboard-tile {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            padding: 24px 32px;
            min-width: 180px;
            flex: 1 1 180px;
            text-align: center;
        }
        .dashboard-tile h3 {
            margin: 0 0 8px 0;
            color: #2d3e50;
            font-size: 1.1em;
            font-weight: 500;
        }
        .dashboard-tile .tile-value {
            font-size: 1.6em;
            color: #1abc9c;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <div class="content-container">
            <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p>Your role: <strong><?php echo htmlspecialchars($role); ?></strong></p>
            <div class="dashboard-cards">
                <?php foreach ($tiles as $label => $value): ?>
                    <div class="dashboard-card">
                        <h3><?php echo $label; ?></h3>
                        <div class="tile-value"><?php echo $value; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p>Use the menu to navigate.</p>

            <?php if ($role == 'admin'): ?>
            <div class="recent-bills">
                <h3>Recent Bills</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Bill ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($bill = $recent_bills->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $bill['id']; ?></td>
                                <td><?php echo htmlspecialchars($bill['customer_name']); ?></td>
                                <td>Rs. <?php echo number_format($bill['grand_total'], 2); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($bill['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="script.js"></script>
</body>
</html>
