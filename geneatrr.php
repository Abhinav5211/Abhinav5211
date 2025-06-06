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
// Fetch users and products
$users = $conn->query("SELECT id, username, name FROM customers WHERE role = 'customer'");
$products = $conn->query("SELECT id, name, price, discount FROM products");
$products_arr = [];
while ($row = $products->fetch_assoc()) {
    $products_arr[] = $row;
}

// Example offers for greedy algorithm
$offers = [
    ["desc" => "Flat Rs. 100 off", "type" => "flat", "value" => 100],
    ["desc" => "10% off", "type" => "percent", "value" => 10],
    // Add more offers as needed
];
$tax_percent = 18; // GST 18%

if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['user_id']) &&
    isset($_POST['items']) &&
    is_array($_POST['items'])
) {
    $customer_id = $_POST['user_id'];
    $items = $_POST['items']; // [product_id => quantity]
    $subtotal = 0;
    $bill_items = [];
    foreach ($items as $product_id => $item) {
        $quantity = $item['quantity'];
        $price = $item['price'];
        if ($quantity > 0) {
            $line_total = $price * $quantity;
            $subtotal += $line_total;
            $bill_items[] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $price
            ];
        }
    }
    // Greedy: Find best discount
    $best_discount = 0;
    $best_offer = null;
    foreach ($offers as $offer) {
        if ($offer['type'] == 'flat') {
            $discount = $offer['value'];
        } elseif ($offer['type'] == 'percent') {
            $discount = $subtotal * ($offer['value'] / 100);
        } else {
            $discount = 0;
        }
        if ($discount > $best_discount) {
            $best_discount = $discount;
            $best_offer = $offer['desc'];
        }
    }
    $after_discount = max($subtotal - $best_discount, 0);
    $tax = $after_discount * ($tax_percent / 100);
    $total = $after_discount + $tax;
    if ($subtotal > 0) {
        $stmt = $conn->prepare("INSERT INTO bills (customer_id, total_amount) VALUES (?, ?)");
        $stmt->bind_param("id", $customer_id, $total);
        if ($stmt->execute()) {
            $bill_id = $conn->insert_id;
            foreach ($bill_items as $item) {
                $stmt2 = $conn->prepare("INSERT INTO bill_items (bill_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("iiid", $bill_id, $item['product_id'], $item['quantity'], $item['price']);
                $stmt2->execute();
                $stmt2->close();
                // Reduce inventory
                $stmt3 = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE product_id = ?");
                $stmt3->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmt3->execute();
                $stmt3->close();
            }
            $message = "Bill generated successfully!<br>Subtotal: Rs. " . number_format($subtotal,2) .
                "<br>Discount (" . ($best_offer ? $best_offer : 'None') . "): Rs. " . number_format($best_discount,2) .
                "<br>Tax ($tax_percent%): Rs. " . number_format($tax,2) .
                "<br><b>Net Payable: Rs. " . number_format($total,2) . "</b>";
            $type = 'success';
        } else {
            $message = "Error: " . $stmt->error;
            $type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Please add at least one item with quantity.";
        $type = 'error';
    }
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Bill</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .card { background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(44,62,80,0.08); padding: 24px; margin-bottom: 24px; }
        .section-title { margin-bottom: 16px; color: #1abc9c; font-size: 1.2em; font-weight: 600; }
        .flex-row { display: flex; gap: 24px; flex-wrap: wrap; }
        .flex-col { flex: 1 1 300px; min-width: 260px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.3); align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: #fff; padding: 32px 24px; border-radius: 8px; min-width: 320px; max-width: 95vw; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 24px rgba(0,0,0,0.18); text-align: center; }
        .modal-content img { max-width: 320px; width: 100%; height: auto; margin: 16px 0; display: block; margin-left: auto; margin-right: auto; }
        .close-modal { float: right; cursor: pointer; color: #888; font-size: 1.6em; margin-top: -12px; margin-right: -8px; }
        .stock-warning { color: #e74c3c; font-size: 0.95em; }
        .payment-methods { display: flex; gap: 12px; margin-bottom: 12px; }
        .payment-methods label { margin-right: 8px; }
        @media (max-width: 500px) {
            .modal-content { padding: 16px 4px; min-width: 0; }
            .modal-content img { max-width: 90vw; }
        }
    </style>
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <form id="billForm" method="POST">
            <div class="content-container" id="printable-bill">
                <h2>Generate Bill</h2>
                <!-- Customer Section -->
                <div class="card">
                    <div class="section-title">Customer</div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <select name="user_id" id="customerSelect" required style="flex:1;">
                            <option value="">Select Customer</option>
                            <?php $users2 = $conn->query("SELECT id, username, name, email FROM customers WHERE role = 'customer'"); 
                            while ($row = $users2->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>">
                                    <?php echo htmlspecialchars($row['name'] . ' (' . $row['username'] . ', ' . $row['email'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="button" onclick="showCustomerModal()" style="width:auto;">+ Add New</button>
                    </div>
                    <div id="customerLedger" style="margin-top:8px;color:#888;font-size:0.98em;"></div>
                </div>
                <!-- Product Table Section -->
                <div class="card">
                    <div class="section-title">Products</div>
                    <div class="table-responsive">
                        <table class="bill-table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>BarCode</th>
                                    <th>Item Name</th>
                                    <th>Stock</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Discount</th>
                                    <th>Net Rate</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- JS will populate rows -->
                            </tbody>
                            <tfoot>
                                <tr class="summary-row">
                                    <td colspan="4"><b>Totals</b></td>
                                    <td id="totalQty">0</td>
                                    <td id="totalPrice">0.00</td>
                                    <td id="totalDiscount">0.00</td>
                                    <td></td>
                                    <td id="totalAmount">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <button type="button" class="add-row-btn" onclick="addRow()">Add Product</button>
                    </div>
                </div>
                <!-- Bill Summary & Payment -->
                <div class="flex-row">
                    <div class="flex-col card">
                        <div class="section-title">Bill Summary</div>
                        <div>Subtotal: <span id="subtotalBox">0.00</span></div>
                        <div>Total Discount: <span id="discountBox">0.00</span></div>
                        <div>Tax (GST): <span id="taxBox">0.00</span></div>
                        <div><b>Grand Total: <span id="grandTotalBox">0.00</span></b></div>
                    </div>
                    <div class="flex-col card">
                        <div class="section-title">Payment</div>
                        <div class="payment-methods">
                            <label><input type="radio" name="payment_method" value="cash" checked> Cash</label>
                            <label><input type="radio" name="payment_method" value="card"> Card</label>
                            <label><input type="radio" name="payment_method" value="QR">QR </label>
                            <label><input type="radio" name="payment_method" value="split"> Split</label>
                        </div>
                        <div id="splitPaymentFields" style="display:none;">
                            <input type="number" name="cash_amount" placeholder="Cash Amount" min="0" step="0.01">
                            <input type="number" name="card_amount" placeholder="Card Amount" min="0" step="0.01">
                            <input type="number" name="QR_amount" placeholder="QR Amount" min="0" step="0.01">
                        </div>
                        <label><input type="checkbox" name="round_off" id="roundOff"> Round Off</label>
                    </div>
                    <div class="flex-col card">
                        <div class="section-title">Additional Details</div>
                        <input type="text" name="salesman" placeholder="Salesman">
                        <input type="text" name="order_no" placeholder="Order/Invoice No">
                        <input type="text" name="notes" placeholder="Notes">
                        <input type="date" name="due_date" value="<?php echo date('Y-m-d'); ?>">
                        <input type="date" name="delivery_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="bill-actions" style="margin-top:24px;">
                    <button type="submit" class="primary-btn">Generate Bill</button>
                    <button type="button" class="secondary-btn" onclick="resetBillForm()">Clear</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Add Customer Modal -->
<div class="modal" id="customerModal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeCustomerModal()">&times;</span>
        <h3>Add New Customer</h3>
        <form id="addCustomerForm">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Add Customer</button>
        </form>
        <div id="addCustomerMsg"></div>
    </div>
</div>
<!-- Card Payment Modal -->
<div class="modal" id="cardModal">
    <div class="modal-content" style="text-align:center;min-width:320px;">
        <span class="close-modal" onclick="closeCardModal()">&times;</span>
        <h3>Card Payment</h3>
        <div id="cardStatus">
            <div id="cardConnecting">Connecting to POS machine...</div>
            <div id="cardVerified" style="display:none;">
                <span style="color:green;font-size:1.2em;">&#10003; Payment Verified</span><br>
                <label style="margin-top:12px;display:inline-block;"><input type="checkbox" id="cardVerifiedCheck"> Admin Confirmed</label>
            </div>
        </div>
    </div>
</div>
<!-- QR Code Modal -->
<div class="modal" id="qrModal">
    <div class="modal-content" style="text-align:center;">
        <span class="close-modal" onclick="closeQRModal()">&times;</span>
        <h3>Scan to Pay (Nabil Bank)</h3>
        <img src="nabil_qr.png" alt="Nabil Bank QR" style="max-width:250px;width:100%;margin:16px 0;">
        <div style="margin-top:8px;">
            <strong>Abhinav Humagain</strong><br>
            NABIL GEN N ACCOUNT<br>
            18910017505848
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
<script>
// --- Product Data for JS ---
const productsData = <?php
$products = $conn->query("SELECT p.id, p.name, p.price, p.discount, IFNULL(i.quantity,0) as stock FROM products p LEFT JOIN inventory i ON p.id = i.product_id");
$prodArr = [];
while ($row = $products->fetch_assoc()) $prodArr[] = $row;
echo json_encode($prodArr);
?>;

document.addEventListener('DOMContentLoaded', function() {
    let rowCount = 0;
    const itemsBody = document.getElementById('itemsBody');
    const addProductBtn = document.querySelector('.add-row-btn');
    const genBtn = document.querySelector('.primary-btn');
    const paymentRadios = Array.from(document.getElementsByName('payment_method'));

    function addRow() {
        rowCount++;
        let row = document.createElement('tr');
        row.innerHTML = `
            <td>${rowCount}</td>
            <td><input type="text" name="items[${rowCount}][barcode]" class="barcode-input" /></td>
            <td><select name="items[${rowCount}][product_id]" class="item-select"><option value="">Select</option>${productsData.map(p => `<option value="${p.id}" data-price="${p.price}" data-discount="${p.discount}" data-stock="${p.stock}">${p.name}</option>`).join('')}</select></td>
            <td class="stock-cell">-</td>
            <td><input type="number" name="items[${rowCount}][quantity]" value="1" min="1" class="qty-input" /></td>
            <td><input type="number" name="items[${rowCount}][price]" value="0" min="0" step="0.01" class="price-input" /></td>
            <td><input type="number" name="items[${rowCount}][discount]" value="0" min="0" step="0.01" class="discount-input" /></td>
            <td><span class="net-rate">0.00</span></td>
            <td><span class="amount">0.00</span></td>
            <td><button type="button" class="delete-btn">Delete</button></td>
        `;
        itemsBody.appendChild(row);
        row.querySelector('.item-select').addEventListener('change', function() { updateProductRow(this); });
        row.querySelector('.qty-input').addEventListener('input', function() { updateProductRow(this); });
        row.querySelector('.price-input').addEventListener('input', function() { updateProductRow(this); });
        row.querySelector('.discount-input').addEventListener('input', function() { updateProductRow(this); });
        row.querySelector('.delete-btn').addEventListener('click', function() { removeRow(this); });
        updateProductRow(row.querySelector('.item-select'));
    }
    window.addRow = addRow;
    function removeRow(btn) {
        btn.closest('tr').remove();
        updateBillSummary();
    }
    function updateProductRow(input) {
        let row = input.closest('tr');
        let select = row.querySelector('.item-select');
        let selected = select.options[select.selectedIndex];
        let price = parseFloat(selected.getAttribute('data-price')) || 0;
        let discount = parseFloat(selected.getAttribute('data-discount')) || 0;
        let stock = parseInt(selected.getAttribute('data-stock')) || 0;
        let qtyInput = row.querySelector('.qty-input');
        let qty = parseInt(qtyInput.value) || 0;
        let priceInput = row.querySelector('.price-input');
        let discountInput = row.querySelector('.discount-input');
        // Auto-fill price/discount/stock
        if (input.classList.contains('item-select')) {
            priceInput.value = price;
            discountInput.value = discount;
            row.querySelector('.stock-cell').innerText = stock;
        }
        // Stock warning
        let stockCell = row.querySelector('.stock-cell');
        if (qty > stock) {
            stockCell.innerHTML = stock + ' <span class="stock-warning">(Exceeds!)</span>';
            qtyInput.style.borderColor = '#e74c3c';
        } else {
            stockCell.innerText = stock;
            qtyInput.style.borderColor = '';
        }
        // Calculate net rate and amount
        let netRate = (parseFloat(priceInput.value) || 0) - (parseFloat(discountInput.value) || 0);
        let amount = netRate * qty;
        row.querySelector('.net-rate').innerText = netRate.toFixed(2);
        row.querySelector('.amount').innerText = amount.toFixed(2);
        updateBillSummary();
    }
    function updateBillSummary() {
        let subtotal = 0, discount = 0, qtyTotal = 0;
        let rows = document.querySelectorAll('#itemsBody tr');
        rows.forEach(row => {
            let qty = parseInt(row.querySelector('.qty-input').value) || 0;
            let price = parseFloat(row.querySelector('.price-input').value) || 0;
            let disc = parseFloat(row.querySelector('.discount-input').value) || 0;
            let netRate = price - disc;
            let amt = netRate * qty;
            subtotal += price * qty;
            discount += disc * qty;
            qtyTotal += qty;
        });
        let tax = subtotal * 0.18; // 18% GST
        let grandTotal = subtotal - discount + tax;
        document.getElementById('subtotalBox').innerText = subtotal.toFixed(2);
        document.getElementById('discountBox').innerText = discount.toFixed(2);
        document.getElementById('taxBox').innerText = tax.toFixed(2);
        document.getElementById('grandTotalBox').innerText = grandTotal.toFixed(2);
        document.getElementById('totalQty').innerText = qtyTotal;
        document.getElementById('totalPrice').innerText = subtotal.toFixed(2);
        document.getElementById('totalDiscount').innerText = discount.toFixed(2);
        document.getElementById('totalAmount').innerText = grandTotal.toFixed(2);
    }
    // Payment split logic and modals
    function showQRModal() {
        document.getElementById('qrModal').classList.add('show');
    }
    function closeQRModal() {
        document.getElementById('qrModal').classList.remove('show');
    }
    function showCardModal() {
        document.getElementById('cardModal').classList.add('show');
        document.getElementById('cardConnecting').style.display = 'block';
        document.getElementById('cardVerified').style.display = 'none';
        document.getElementById('cardVerifiedCheck').checked = false;
        setTimeout(function() {
            document.getElementById('cardConnecting').style.display = 'none';
            document.getElementById('cardVerified').style.display = 'block';
        }, 2000);
    }
    function closeCardModal() {
        document.getElementById('cardModal').classList.remove('show');
    }
    window.showQRModal = showQRModal;
    window.closeQRModal = closeQRModal;
    window.showCardModal = showCardModal;
    window.closeCardModal = closeCardModal;
    // Payment method logic
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('splitPaymentFields').style.display = (this.value === 'split') ? 'block' : 'none';
            if (this.value === 'QR') showQRModal();
            else closeQRModal();
            if (this.value === 'card') {
                showCardModal();
                setCardBtnState();
            } else {
                closeCardModal();
                setCardBtnState();
            }
        });
    });
    // Card payment: enable/disable Generate Bill
    function setCardBtnState() {
        const cardRadio = paymentRadios.find(r => r.value === 'card');
        if (cardRadio && cardRadio.checked) {
            genBtn.disabled = !document.getElementById('cardVerifiedCheck').checked;
        } else {
            genBtn.disabled = false;
        }
    }
    document.getElementById('cardVerifiedCheck').addEventListener('change', setCardBtnState);
    document.getElementById('cardModal').addEventListener('click', function(e) {
        if (e.target === this) { closeCardModal(); setCardBtnState(); }
    });
    // Add Product button
    if (addProductBtn) addProductBtn.addEventListener('click', addRow);
    // Add Customer modal logic
    document.getElementById('customerModal').querySelector('.close-modal').onclick = closeCustomerModal;
    document.getElementById('addCustomerForm').onsubmit = function(e) {
        e.preventDefault();
        let form = e.target;
        let data = new FormData(form);
        fetch('customer_register.php', {
            method: 'POST',
            body: data
        }).then(res => res.text()).then(res => {
            document.getElementById('addCustomerMsg').innerHTML = 'Customer added! Please refresh the dropdown.';
            form.reset();
        }).catch(() => {
            document.getElementById('addCustomerMsg').innerHTML = 'Error adding customer.';
        });
    };
    // Add first row on load
    addRow();
    updateBillSummary();
    // Reset bill form
    window.resetBillForm = function() {
        itemsBody.innerHTML = '';
        rowCount = 0;
        addRow();
        updateBillSummary();
    }
});
</script>
<?php if ($message && $type === 'success'): ?>
<script>
setTimeout(function() {
    var printContents = document.getElementById('printable-bill').innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    alert('Print successful and saved to database');
    window.location.href = 'generate_bill.php';
}, 500);
</script>
<?php endif; ?>
</body>
</html> 