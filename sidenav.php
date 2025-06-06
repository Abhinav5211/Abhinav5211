<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
?>
<style>
.sidenav {
    height: 100%;
    width: 240px !important;
    min-width: 240px;
    max-width: 240px;
    position: relative !important;
    z-index: 1000;
    top: 0;
    left: 0;
    background: #2d3e50;
    overflow-x: hidden;
    padding-top: 60px;
    transition: none !important;
}
.sidenav a {
    padding: 12px 32px;
    text-decoration: none;
    font-size: 1.1em;
    color: #fff;
    display: block;
    border-radius: 6px;
    margin: 2px 12px;
    transition: background 0.2s, color 0.2s;
}
.sidenav a:hover, .sidenav a.active {
    background: #1abc9c;
    color: #fff;
}
.sidenav .closebtn {
    display: none !important;
}
.main {
    transition: none !important;
    padding: 24px;
}
.hamburger {
    display: none !important;
}
@media (max-width: 600px) {
    .main { padding: 12px; }
    .sidenav { padding-top: 40px; }
}
</style>
<div id="mySidenav" class="sidenav">
    <div style="padding: 16px 32px; color: #fff; font-weight: bold;">Hello, <?php echo htmlspecialchars($username); ?>!</div>
    <a href="dashboard.php">Dashboard</a>
    <?php if ($role == 'customer'): ?>
        <a href="view_stock.php">View Store Items</a>
        <a href="view_notifications.php">View Notifications</a>
        <a href="update_request.php">Submit Update Request</a>
        <a href="view_bills.php">View Bills</a>
    <?php elseif ($role == 'admin'): ?>
        <a href="products_crud.php">Manage Products</a>
        <a href="inventory_crud.php">Manage Inventory</a>
        <a href="customers_crud.php">Manage Customers</a>
        <a href="suppliers_crud.php">Manage Suppliers</a>
        <a href="accept_request.php">Accept Update Request</a>
        <a href="send_notification.php">Send Notifications</a>
        <a href="view_customers.php">View Customer Details</a>
        <a href="view_stock.php">View Stock Availability</a>
        <a href="generate_bill.php">Generate Bill</a>
    <?php endif; ?>
    <a href="logout.php">Logout</a>
</div> 