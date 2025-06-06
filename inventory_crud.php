<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
include 'db.php';
$message = '';
$type = 'success';
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$delete_id = isset($_GET['delete']) ? intval($_GET['delete']) : 0;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Handle delete
if ($delete_id > 0) {
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: inventory_crud.php");
    exit();
}

// Handle add
if (isset($_POST['add_inventory'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    // Check if product already exists in inventory
    $check = $conn->prepare("SELECT id, quantity FROM inventory WHERE product_id = ?");
    $check->bind_param("i", $product_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->bind_result($inv_id, $existing_qty);
        $check->fetch();
        $new_qty = $existing_qty + $quantity;
        $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_qty, $inv_id);
        if ($stmt->execute()) {
            $message = "Inventory updated successfully!";
            $type = 'success';
        } else {
            $message = "Error: " . $stmt->error;
            $type = 'error';
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO inventory (product_id, quantity) VALUES (?, ?)");
        $stmt->bind_param("ii", $product_id, $quantity);
        if ($stmt->execute()) {
            $message = "Inventory added successfully!";
            $type = 'success';
        } else {
            $message = "Error: " . $stmt->error;
            $type = 'error';
        }
        $stmt->close();
    }
    $check->close();
}

// Handle edit
if ($edit_id > 0 && isset($_POST['edit_inventory'])) {
    $quantity = intval($_POST['quantity']);
    $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $quantity, $edit_id);
    if ($stmt->execute()) {
        $message = "Inventory updated successfully!";
        $type = 'success';
    } else {
        $message = "Error: " . $stmt->error;
        $type = 'error';
    }
    $stmt->close();
    $edit_id = 0; // Reset edit mode
}

// Fetch products for dropdown
$products = $conn->query("SELECT id, name FROM products");
// Fetch inventory for table
if ($search) {
    $inventory = $conn->query("SELECT i.id, p.name as product_name, i.quantity FROM inventory i JOIN products p ON i.product_id = p.id WHERE p.name LIKE '%$search%'");
} else {
    $inventory = $conn->query("SELECT i.id, p.name as product_name, i.quantity FROM inventory i JOIN products p ON i.product_id = p.id");
}
// If editing, fetch the record
$edit_row = null;
if ($edit_id > 0) {
    $result = $conn->query("SELECT i.id, p.name as product_name, i.quantity FROM inventory i JOIN products p ON i.product_id = p.id WHERE i.id = $edit_id");
    $edit_row = $result->fetch_assoc();
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <div class="form-container">
            <h2>Manage Inventory</h2>
            <!-- Add Inventory Form -->
            <form method="POST" style="margin-bottom:24px;">
                <h3>Add Inventory</h3>
                Product: <select name="product_id" required>
                    <?php $products->data_seek(0); while ($row = $products->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                Quantity: <input type="number" name="quantity" required>
                <button type="submit" name="add_inventory">Add/Update</button>
            </form>
            <!-- Edit Inventory Form -->
            <?php if ($edit_row): ?>
            <form method="POST" style="margin-bottom:24px; background:#f9f9f9; padding:16px; border-radius:6px;">
                <h3>Edit Inventory</h3>
                <label>Product: <?php echo htmlspecialchars($edit_row['product_name']); ?></label><br>
                Quantity: <input type="number" name="quantity" value="<?php echo $edit_row['quantity']; ?>" required>
                <button type="submit" name="edit_inventory">Update</button>
                <a href="inventory_crud.php" style="margin-left:16px;">Cancel</a>
            </form>
            <?php endif; ?>
            <!-- Search Form -->
            <form method="GET" style="margin-bottom:16px; display:inline-flex; align-items:center; gap:6px;">
                <input type="text" name="search" placeholder="Search inventory..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="padding:4px 8px; font-size:0.95em; border-radius:4px; border:1px solid #ccc; width:160px;">
                <button type="submit" style="padding:4px 12px; font-size:0.95em; border-radius:4px; background:#1abc9c; color:#fff; border:none; cursor:pointer;">Search</button>
            </form>
            <!-- Inventory Table -->
            <table border="1">
                <tr><th>Product</th><th>Quantity</th><th>Actions</th></tr>
                <?php while ($row = $inventory->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td>
                            <a href="inventory_crud.php?edit=<?php echo $row['id']; ?>">Edit</a> |
                            <a href="inventory_crud.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this inventory record?');">Delete</a>
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
<?php if ($message): ?>
<script>showAlert("<?php echo addslashes($message); ?>", "<?php echo $type; ?>");</script>
<?php endif; ?>
</body>
</html> 