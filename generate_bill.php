<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
if (!isset($_SESSION['admin_id'])) {
    echo '<script>alert("Access denied: Admins only.");window.location.href="dashboard.php";</script>';
    exit();
}
include 'db.php';
$message = '';
$type = 'success';
$users = $conn->query("SELECT id, username, name FROM customers WHERE role = 'customer'");
$products = $conn->query("SELECT id, name, price, discount FROM products");
$products_arr = [];
while ($row = $products->fetch_assoc()) {
    $products_arr[] = $row;
}
$invoiceNo = 'INV' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
?>
<?php include 'header.php'; ?>
<?php include 'footer.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Bill</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .bill-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }
        .bill-table th, .bill-table td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: center;
            font-size: 1em;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        .card {
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 20px auto;
            width: 100%;
        }
        .section-title {
            font-size: 1.5em;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .content-container {
            width: 100vw;
            margin: 0;
            padding: 20px;
        }
        input[type="number"], select, input[type="text"], input[type="date"], input[type="email"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }
        .action-btn {
            padding: 6px 12px;
            font-size: 0.9em;
            cursor: pointer;
            margin-right: 8px;
        }
        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-bottom: 10px;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }
        #qrImageBox {
            display: none;
            text-align: center;
            margin-top: 12px;
        }
        #qrImageBox img {
            max-width: 220px;
            width: 100%;
            border: 1px solid #ccc;
            padding: 8px;
        }
    </style>
    <script>
document.getElementById("billForm").addEventListener("submit", function(e) {
    let errors = [];

    // Validate customer or guest
    const customerSelected = document.querySelector('[name="customer_id"]')?.value;
    const guestName = document.querySelector('[name="guest_name"]')?.value;
    if (!customerSelected && !guestName) {
        errors.push("Select a registered customer or enter guest name.");
    }

    // Validate products
    const productRows = document.querySelectorAll("#itemsBody tr");
    if (productRows.length === 0) {
        errors.push("Add at least one product.");
    } else {
        productRows.forEach((row, index) => {
            const qty = row.querySelector('[name^="quantity_"]');
            if (!qty || qty.value <= 0) {
                errors.push(`Product row ${index + 1} has invalid quantity.`);
            }
        });
    }

    // Validate payment method and payment status
    const paymentMethod = document.querySelector('[name="payment_method"]:checked');
    const paymentStatus = document.querySelector('[name="payment_status"]');
    if (!paymentMethod || !paymentStatus?.value) {
        errors.push("Select a payment method and status.");
    }

    // If any errors, prevent submission and alert
    if (errors.length > 0) {
        e.preventDefault();
        alert("Error:\n" + errors.join("\n"));
    }
});
</script>

</head>

<body>
<div class="content-container">
    <div class="card">
        <div class="section-title">Product Details</div>
        <div class="table-responsive">
            <table class="bill-table" id="productTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Discount (Rs.)</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button class="action-btn" onclick="addProductRow()">+ Add Product</button>
        </div>
    </div>
    <div class="card">
        <div class="section-title">Billing & Payment</div>
        <form method="POST" action="submit_bill.php">
            <div class="checkbox-group">
                <label for="guestToggle">Guest Checkout</label>
                <input type="checkbox" id="guestToggle" onchange="toggleCustomerFields()">
            </div>
            <div id="registeredCustomer">
                <label for="customer">Select Registered Customer:</label>
                <select name="customer_id">
                    <option value="">-- Choose Customer --</option>
                    <?php while($cust = $users->fetch_assoc()): ?>
                        <option value="<?= $cust['id'] ?>"><?= htmlspecialchars($cust['name']) ?> (<?= $cust['username'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div id="guestCustomer" style="display:none;">
                <label for="guest_name">Guest Name:</label>
                <input type="text" name="guest_name" placeholder="Full Name">
                <label for="guest_email">Guest Email (optional):</label>
                <input type="email" name="guest_email" placeholder="example@mail.com">
            </div>
            <label for="payment">Payment Method:</label>
            <div class="radio-group">
                <label><input type="radio" name="payment_method" value="cash" onchange="handlePaymentMethodChange()" checked> Cash</label>
                <label><input type="radio" name="payment_method" value="card" onchange="handlePaymentMethodChange()"> Card</label>
                <label><input type="radio" name="payment_method" value="QR" onchange="handlePaymentMethodChange()"> QR</label>
            </div>
            <div id="qrImageBox">
                <img src="nabil_qr.png" alt="Scan QR to Pay">
                <p>Scan this QR code to pay.</p>
            </div>
            <label for="payment_status">Payment Status:</label>
            <div class="radio-group">
                <label><input type="radio" name="payment_status" value="paid" checked> Paid</label>
                <label><input type="radio" name="payment_status" value="unpaid"> Unpaid</label>
            </div>
            <label for="order_no">Order/Invoice No:</label>
            <input type="text" name="order_no" value="<?= $invoiceNo ?>" readonly>
            <label for="notes">Notes:</label>
            <input type="text" name="notes" placeholder="Any additional notes">
            <label for="due_date">Due Date:</label>
            <input type="date" name="due_date" value="<?= date('Y-m-d') ?>">
            <div class="section-title">Summary</div>
            <p>Subtotal: <span id="summary-subtotal">Rs. 0.00</span></p>
            <p>Total Discount: <span id="summary-discount">Rs. 0.00</span></p>
            <p>Tax (13%): <span id="summary-tax">Rs. 0.00</span></p>
            <p><strong>Grand Total: <span id="summary-total">Rs. 0.00</span></strong></p>
            <button type="submit" class="action-btn">Submit Bill</button>
            <button type="reset" class="action-btn">Reset</button>
        </form>
    </div>
</div>
<script>
const products = <?php echo json_encode($products_arr); ?>;
let rowIndex = 0;

function addProductRow() {
    const tableBody = document.querySelector('#productTable tbody');
    const row = document.createElement('tr');
    row.setAttribute('data-row', rowIndex);
    row.innerHTML = `
        <td>${rowIndex + 1}</td>
        <td>
            <select name="product_${rowIndex}" onchange="updateRow(this)">
                <option value="">Select</option>
                ${products.map(p => `<option value="${p.id}" data-price="${p.price}" data-discount="${p.discount}">${p.name}</option>`).join('')}
            </select>
        </td>
        <td class="price">0.00</td>
        <td class="discount">0</td>
        <td><input type="number" name="quantity_${rowIndex}" min="0" value="0" oninput="updateRow(this)"></td>
        <td class="total">Rs. 0.00</td>
        <td><button class="action-btn" type="button" onclick="removeRow(this)">Delete</button></td>
    `;
    tableBody.appendChild(row);
    rowIndex++;
}

function updateRow(el) {
    const row = el.closest('tr');
    const select = row.querySelector('select');
    const selectedOption = select.options[select.selectedIndex];
    const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
    const discount = parseFloat(selectedOption.getAttribute('data-discount')) || 0;
    const qty = parseFloat(row.querySelector('input[type="number"]').value) || 0;
    const netPrice = price - (price * discount / 100);
    const total = qty * netPrice;
    row.querySelector('.price').textContent = price.toFixed(2);
    row.querySelector('.discount').textContent = discount;
    row.querySelector('.total').textContent = 'Rs. ' + total.toFixed(2);

    updateSummary();
}

function removeRow(btn) {
    const row = btn.closest('tr');
    row.remove();
    updateSummary();
}

function updateSummary() {
    let subtotal = 0;
    let totalDiscount = 0;
    let grandTotal = 0;

    document.querySelectorAll('#productTable tbody tr').forEach(row => {
        const select = row.querySelector('select');
        const selectedOption = select.options[select.selectedIndex];
        const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        const discount = parseFloat(selectedOption.getAttribute('data-discount')) || 0;
        const qty = parseFloat(row.querySelector('input[type="number"]').value) || 0;
        const itemTotal = qty * price;
        const itemDiscount = qty * (price * discount / 100);
        subtotal += itemTotal;
        totalDiscount += itemDiscount;
    });

    const tax = (subtotal - totalDiscount) * 0.13;
    grandTotal = subtotal - totalDiscount + tax;

    document.getElementById('summary-subtotal').textContent = 'Rs. ' + subtotal.toFixed(2);
    document.getElementById('summary-discount').textContent = 'Rs. ' + totalDiscount.toFixed(2);
    document.getElementById('summary-tax').textContent = 'Rs. ' + tax.toFixed(2);
    document.getElementById('summary-total').textContent = 'Rs. ' + grandTotal.toFixed(2);
}

function toggleCustomerFields() {
    const isGuest = document.getElementById('guestToggle').checked;
    document.getElementById('registeredCustomer').style.display = isGuest ? 'none' : 'block';
    document.getElementById('guestCustomer').style.display = isGuest ? 'block' : 'none';
    document.querySelector('[name="customer_id"]').required = !isGuest;
    document.querySelector('[name="guest_name"]').required = isGuest;
}

function handlePaymentMethodChange() {
    const selected = document.querySelector('input[name="payment_method"]:checked').value;
    const qrBox = document.getElementById('qrImageBox');
    qrBox.style.display = (selected === 'QR') ? 'block' : 'none';
}

window.onload = () => {
    addProductRow();
    handlePaymentMethodChange();
}
document.querySelector('form').addEventListener('submit', function(e) {
    // Remove existing hidden inputs
    document.querySelectorAll('.product-hidden').forEach(e => e.remove());

    // Loop through each product row
    document.querySelectorAll('#productTable tbody tr').forEach((row, index) => {
        const select = row.querySelector('select');
        const qty = row.querySelector('input[type="number"]').value;

        if (select && select.value) {
            const productId = select.value;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `product_${index}`;
            input.value = productId;
            input.classList.add('product-hidden');
            this.appendChild(input);

            const qtyInput = document.createElement('input');
            qtyInput.type = 'hidden';
            qtyInput.name = `quantity_${index}`;
            qtyInput.value = qty;
            qtyInput.classList.add('product-hidden');
            this.appendChild(qtyInput);
        }
    });
});

</script>
</body>
</html>
