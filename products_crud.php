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
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: products_crud.php");
    exit();
}

// Handle add
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $supplier_id = $_POST['supplier_id'] ? intval($_POST['supplier_id']) : null;
    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0.00;
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, supplier_id, discount) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdii", $name, $description, $price, $supplier_id, $discount);
    if ($stmt->execute()) {
        $message = "Product added successfully!";
        $type = 'success';
    } else {
        $message = "Error: " . $stmt->error;
        $type = 'error';
    }
    $stmt->close();
}

// Handle edit
if ($edit_id > 0 && isset($_POST['edit_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $supplier_id = $_POST['supplier_id'] ? intval($_POST['supplier_id']) : null;
    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0.00;
    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, supplier_id = ?, discount = ? WHERE id = ?");
    $stmt->bind_param("ssdiid", $name, $description, $price, $supplier_id, $discount, $edit_id);
    if ($stmt->execute()) {
        $message = "Product updated successfully!";
        $type = 'success';
    } else {
        $message = "Error: " . $stmt->error;
        $type = 'error';
    }
    $stmt->close();
    $edit_id = 0; // Reset edit mode
}

// Fetch suppliers for dropdown
$suppliers = $conn->query("SELECT id, name FROM suppliers");
// Fetch products for table
if ($search) {
    $products = $conn->query("SELECT p.*, s.name as supplier_name FROM products p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.name LIKE '%$search%' OR p.description LIKE '%$search%' OR s.name LIKE '%$search%'");
} else {
    $products = $conn->query("SELECT p.*, s.name as supplier_name FROM products p LEFT JOIN suppliers s ON p.supplier_id = s.id");
}
// If editing, fetch the record
$edit_row = null;
if ($edit_id > 0) {
    $result = $conn->query("SELECT * FROM products WHERE id = $edit_id");
    $edit_row = $result->fetch_assoc();
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <div class="form-container">
            <h2>Manage Products</h2>
            <!-- Add Product Form -->
            <form method="POST" style="margin-bottom:24px;">
                <h3>Add Product</h3>
                Name: <input type="text" name="name" required>
                Description: <input type="text" name="description">
                Price: <input type="number" step="0.01" name="price" required>
                Discount: <input type="number" step="0.01" name="discount" value="0.00" required>
                Supplier: <select name="supplier_id">
                    <option value="">None</option>
                    <?php $suppliers->data_seek(0); while ($row = $suppliers->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="add_product">Add</button>
            </form>
            <!-- Edit Product Form -->
            <?php if ($edit_row): ?>
            <form method="POST" style="margin-bottom:24px; background:#f9f9f9; padding:16px; border-radius:6px;">
                <h3>Edit Product</h3>
                Name: <input type="text" name="name" value="<?php echo htmlspecialchars($edit_row['name']); ?>" required>
                Description: <input type="text" name="description" value="<?php echo htmlspecialchars($edit_row['description']); ?>">
                Price: <input type="number" step="0.01" name="price" value="<?php echo $edit_row['price']; ?>" required>
                Discount: <input type="number" step="0.01" name="discount" value="<?php echo isset($edit_row['discount']) ? htmlspecialchars($edit_row['discount']) : '0.00'; ?>" required>
                Supplier: <select name="supplier_id">
                    <option value="">None</option>
                    <?php $suppliers->data_seek(0); while ($row = $suppliers->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>" <?php if ($edit_row['supplier_id'] == $row['id']) echo 'selected'; ?>><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="edit_product">Update</button>
                <a href="products_crud.php" style="margin-left:16px;">Cancel</a>
            </form>
            <?php endif; ?>
            <!-- Search Form -->
            <form method="GET" style="margin-bottom:16px; display:inline-flex; align-items:center; gap:6px;">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="padding:4px 8px; font-size:0.95em; border-radius:4px; border:1px solid #ccc; width:160px;">
                <button type="submit" style="padding:4px 12px; font-size:0.95em; border-radius:4px; background:#1abc9c; color:#fff; border:none; cursor:pointer;">Search</button>
            </form>
            <!-- Products Table -->
            <table border="1">
                <tr><th>Name</th><th>Description</th><th>Price</th><th>Discount</th><th>Supplier</th><th>Actions</th></tr>
                <?php while ($row = $products->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo $row['price']; ?></td>
                        <td><?php echo $row['discount']; ?></td>
                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <td>
                            <a href="products_crud.php?edit=<?php echo $row['id']; ?>">Edit</a> |
                            <a href="products_crud.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this product?');">Delete</a>
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