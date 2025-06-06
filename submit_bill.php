<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isGuest = isset($_POST['guest_name']) && !empty($_POST['guest_name']);
    $customerId = $isGuest ? null : $_POST['customer_id'];
    $guestName = $isGuest ? trim($_POST['guest_name']) : null;
    $guestEmail = $isGuest ? trim($_POST['guest_email']) : null;
    $paymentMethod = $_POST['payment_method'] ?? '';
    $paymentStatus = $_POST['payment_status'] ?? '';
    $orderNo = $_POST['order_no'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $dueDate = $_POST['due_date'] ?? '';

    // Validate: Payment & Products
    if (empty($paymentMethod) || empty($paymentStatus)) {
        echo "<script>alert('Please select payment method and status.'); window.history.back();</script>";
        exit();
    }

    $subtotal = 0;
    $totalDiscount = 0;
    $grandTotal = 0;
    $tax = 0;
    $products = [];
    $hasProduct = false;

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'product_') === 0) {
            $hasProduct = true;
            $index = explode('_', $key)[1];
            $productId = (int)$value;
            $quantity = (int)$_POST['quantity_' . $index];

            if ($quantity <= 0) continue;

            $result = $conn->query("SELECT price, discount FROM products WHERE id = $productId");
            if ($result && $row = $result->fetch_assoc()) {
                $price = $row['price'];
                $discount = $row['discount'];
                $netPrice = $price - ($price * $discount / 100);
                $itemTotal = $quantity * $price;
                $itemDiscount = $quantity * ($price * $discount / 100);

                $subtotal += $itemTotal;
                $totalDiscount += $itemDiscount;

                $products[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount' => $discount,
                    'net_price' => $netPrice
                ];
            }
        }
    }

    if (!$hasProduct) {
        echo "<script>alert('Please add at least one product to the bill.'); window.history.back();</script>";
        exit();
    }

    $tax = ($subtotal - $totalDiscount) * 0.13;
    $grandTotal = $subtotal - $totalDiscount + $tax;

    // Validate customer if not guest
    if (!$isGuest && $customerId) {
        $checkCustomer = $conn->prepare("SELECT id FROM customers WHERE id = ?");
        $checkCustomer->bind_param("i", $customerId);
        $checkCustomer->execute();
        $checkCustomer->store_result();
        if ($checkCustomer->num_rows === 0) {
            echo "<script>alert('Invalid customer ID.'); window.history.back();</script>";
            exit();
        }
    }

    // Insert into bills
    $stmt = $conn->prepare("INSERT INTO bills (
        customer_id, guest_name, guest_email, payment_method, payment_status, order_no, notes, due_date,
        subtotal, discount_amount, tax_amount, grand_total
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "issssssssddd",
        $customerId,
        $guestName,
        $guestEmail,
        $paymentMethod,
        $paymentStatus,
        $orderNo,
        $notes,
        $dueDate,
        $subtotal,
        $totalDiscount,
        $tax,
        $grandTotal
    );
    $stmt->execute();
    $billId = $stmt->insert_id;

    // Insert bill items
    $itemStmt = $conn->prepare("INSERT INTO bill_items (bill_id, product_id, quantity, price, discount, net_price) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($products as $item) {
        $itemStmt->bind_param("iiiddd", $billId, $item['product_id'], $item['quantity'], $item['price'], $item['discount'], $item['net_price']);
        $itemStmt->execute();
    }

    // Redirect
    header("Location: print_bill.php?bill_id=$billId");
    exit();
}
?>
