<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
include 'db.php';
$customer_id = $_SESSION['customer_id'];
$bills = $conn->query("SELECT * FROM bills WHERE customer_id = $customer_id ORDER BY created_at DESC");
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Bills</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .pay-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.4);
            align-items: center; justify-content: center;
        }
        .pay-modal-content {
            background: #fff;
            border-radius: 8px;
            padding: 32px 24px;
            min-width: 320px;
            max-width: 90vw;
            text-align: center;
            position: relative;
        }
        .pay-modal-close {
            position: absolute;
            top: 8px; right: 16px;
            font-size: 1.5em;
            color: #888;
            cursor: pointer;
        }
        .pay-options button {
            margin: 8px;
            padding: 10px 18px;
            border-radius: 4px;
            border: none;
            background: #2d3e50;
            color: #fff;
            font-size: 1em;
            cursor: pointer;
        }
        .pay-options button:hover {
            background: #1abc9c;
        }
        .qr-img {
            width: 160px;
            height: 160px;
            margin: 16px auto;
            display: block;
        }
        .card-info, .cod-info {
            margin: 16px 0;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <div class="content-container">
            <h2>View Bills</h2>
            <?php while ($bill = $bills->fetch_assoc()): ?>
                <div style="border:1px solid #d1d5db; border-radius:6px; margin-bottom:24px; padding:16px;">
                    <strong>Date:</strong> <?php echo $bill['created_at']; ?><br>
                    <strong>Total:</strong> Rs. <?php echo $bill['grand_total']; ?><br>
                    <strong>Items:</strong>
                    <table>
                        <tr><th>Product</th><th>Quantity</th><th>Price</th></tr>
                        <?php
                        $bill_id = $bill['id'];
                        $items = $conn->query("SELECT b.quantity, b.price, p.name FROM bill_items b JOIN products p ON b.product_id = p.id WHERE b.bill_id = $bill_id");
                        while ($item = $items->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>Rs. <?php echo number_format($item['price'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php endwhile; ?>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</div>
<!-- Payment Modal -->
<div id="payModal" class="pay-modal">
    <div class="pay-modal-content">
        <span class="pay-modal-close" onclick="closePayModal()">&times;</span>
        <h3>Choose Payment Method</h3>
        <div class="pay-options">
            <button onclick="showPayOption('qr')">Pay via QR</button>
            <button onclick="showPayOption('card')">Visa/Debit Card</button>
            <button onclick="showPayOption('cod')">Cash on Delivery</button>
        </div>
        <div id="payOptionContent" style="margin-top:24px;"></div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
<script>
let currentBillId = null;
let currentBillTotal = null;
function openPayModal(billId, billTotal) {
    currentBillId = billId;
    currentBillTotal = billTotal;
    document.getElementById('payModal').style.display = 'flex';
    document.getElementById('payOptionContent').innerHTML = '';
}
function closePayModal() {
    document.getElementById('payModal').style.display = 'none';
}
function showPayOption(option) {
    let content = '';
    if (option === 'qr') {
        content = `<div><b>Scan this QR to pay Rs. ${currentBillTotal}</b><br><img class='qr-img' src='https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=upi://pay?pa=demo@upi&am=${currentBillTotal}&cu=INR' alt='QR Code'></div>`;
    } else if (option === 'card') {
        content = `<div class='card-info'><b>Enter your card details (imaginary):</b><br><input type='text' placeholder='Card Number' style='width:80%;margin:8px 0;'><br><input type='text' placeholder='MM/YY' style='width:40%;margin:8px 0;'> <input type='text' placeholder='CVV' style='width:30%;margin:8px 0;'><br><button style='margin-top:8px;'>Pay Rs. ${currentBillTotal}</button></div>`;
    } else if (option === 'cod') {
        content = `<div class='cod-info'><b>Cash on Delivery selected.</b><br>Please keep Rs. ${currentBillTotal} ready at the time of delivery.</div>`;
    }
    document.getElementById('payOptionContent').innerHTML = content;
}
window.onclick = function(event) {
    let modal = document.getElementById('payModal');
    if (event.target === modal) closePayModal();
}
</script>
</body>
</html>
