<?php
// print_bill.php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

if (!isset($_GET['bill_id'])) {
    echo "<script>alert('No bill selected.');window.location.href='generate_bill.php';</script>";
    exit();
}

$bill_id = intval($_GET['bill_id']);
$bill = $conn->query("SELECT * FROM bills WHERE id = $bill_id")->fetch_assoc();
$items = $conn->query("SELECT bi.*, p.name FROM bill_items bi JOIN products p ON bi.product_id = p.id WHERE bi.bill_id = $bill_id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bill #<?= $bill['order_no'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .bill-box { max-width: 800px; margin: auto; border: 1px solid #ccc; padding: 20px; }
        h2, h3 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        .summary { margin-top: 20px; float: right; }
    </style>
</head>
<body>
<div class="bill-box">
    <h2>Store Bill Receipt</h2>
    <h3>Invoice No: <?= $bill['order_no'] ?> | Date: <?= $bill['created_at'] ?></h3>

    <p><strong>Customer:</strong> <?= $bill['guest_name'] ?: 'Registered User' ?><br>
       <strong>Email:</strong> <?= $bill['guest_email'] ?: 'N/A' ?><br>
       <strong>Payment Method:</strong> <?= ucfirst($bill['payment_method']) ?><br>
       <strong>Status:</strong> <?= ucfirst($bill['payment_status']) ?></p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Price (Rs.)</th>
                <th>Discount (%)</th>
                <th>Quantity</th>
                <th>Net Price (Rs.)</th>
                <th>Total (Rs.)</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 1; while ($item = $items->fetch_assoc()): 
            $net_price = $item['price'] - ($item['price'] * $item['discount'] / 100);
            $total_price = $net_price * $item['quantity'];
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= $item['name'] ?></td>
                <td><?= number_format($item['price'], 2) ?></td>
                <td><?= $item['discount'] ?>%</td>
                <td><?= $item['quantity'] ?></td>
                <td><?= number_format($net_price, 2) ?></td>
                <td><?= number_format($total_price, 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div class="summary">
    <p><strong>Subtotal:</strong> Rs. <?= number_format($bill['subtotal'], 2) ?></p>
<p><strong>Total Discount:</strong> Rs. <?= number_format($bill['discount_amount'], 2) ?></p>
<p><strong>Tax (13%):</strong> Rs. <?= number_format($bill['tax_amount'], 2) ?></p>
<p><strong>Grand Total:</strong> Rs. <?= number_format($bill['grand_total'], 2) ?></p>

    </div>

    
</div>
</body>
</html>
<script>
window.onload = function () {
    window.print();

    // After print, redirect to a fresh bill form
    setTimeout(function () {
        window.location.href = "generate_bill.php";
    }, 1000); // 1 second delay to allow print
};
</script>
