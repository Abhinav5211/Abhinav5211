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
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: suppliers_crud.php");
    exit();
}

// Handle add
if (isset($_POST['add_supplier'])) {
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $stmt = $conn->prepare("INSERT INTO suppliers (name, contact, email, address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $contact, $email, $address);
    if ($stmt->execute()) {
        $message = "Supplier added successfully!";
        $type = 'success';
    } else {
        $message = "Error: " . $stmt->error;
        $type = 'error';
    }
    $stmt->close();
}

// Handle edit
if ($edit_id > 0 && isset($_POST['edit_supplier'])) {
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $stmt = $conn->prepare("UPDATE suppliers SET name = ?, contact = ?, email = ?, address = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $contact, $email, $address, $edit_id);
    if ($stmt->execute()) {
        $message = "Supplier updated successfully!";
        $type = 'success';
    } else {
        $message = "Error: " . $stmt->error;
        $type = 'error';
    }
    $stmt->close();
    $edit_id = 0; // Reset edit mode
}

// Fetch suppliers for table
if ($search) {
    $suppliers = $conn->query("SELECT * FROM suppliers WHERE name LIKE '%$search%' OR contact LIKE '%$search%' OR email LIKE '%$search%' OR address LIKE '%$search%' ORDER BY created_at DESC");
} else {
    $suppliers = $conn->query("SELECT * FROM suppliers ORDER BY created_at DESC");
}
// If editing, fetch the record
$edit_row = null;
if ($edit_id > 0) {
    $result = $conn->query("SELECT * FROM suppliers WHERE id = $edit_id");
    $edit_row = $result->fetch_assoc();
}
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suppliers Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div id="main" class="main">
        <div class="form-container">
            <h2>Manage Suppliers</h2>
            <!-- Add Supplier Form -->
            <form method="POST" style="margin-bottom:24px;">
                <h3>Add Supplier</h3>
                Name: <input type="text" name="name" required>
                Contact: <input type="text" name="contact">
                Email: <input type="email" name="email">
                Address: <input type="text" name="address">
                <button type="submit" name="add_supplier">Add</button>
            </form>
            <!-- Edit Supplier Form -->
            <?php if ($edit_row): ?>
            <form method="POST" style="margin-bottom:24px; background:#f9f9f9; padding:16px; border-radius:6px;">
                <h3>Edit Supplier</h3>
                Name: <input type="text" name="name" value="<?php echo htmlspecialchars($edit_row['name']); ?>" required>
                Contact: <input type="text" name="contact" value="<?php echo htmlspecialchars($edit_row['contact_person']); ?>">
                Email: <input type="email" name="email" value="<?php echo htmlspecialchars($edit_row['email']); ?>">
                Address: <input type="text" name="address" value="<?php echo htmlspecialchars($edit_row['address']); ?>">
                <button type="submit" name="edit_supplier">Update</button>
                <a href="suppliers_crud.php" style="margin-left:16px;">Cancel</a>
            </form>
            <?php endif; ?>
            <!-- Search Bar -->
            <form method="GET" style="margin-bottom:16px; display:inline-flex; align-items:center; gap:6px;">
                <input type="text" name="search" placeholder="Search suppliers..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="padding:4px 8px; font-size:0.95em; border-radius:4px; border:1px solid #ccc; width:160px;">
                <button type="submit" style="padding:4px 12px; font-size:0.95em; border-radius:4px; background:#1abc9c; color:#fff; border:none; cursor:pointer;">Search</button>
            </form>
            <!-- Suppliers Table -->
            <table border="1">
                <tr><th>Name</th><th>Contact</th><th>Email</th><th>Address</th><th>Created At</th><th>Actions</th></tr>
                <?php while ($row = $suppliers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['address']); ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                        <td>
                            <a href="suppliers_crud.php?edit=<?php echo $row['id']; ?>">Edit</a> |
                            <a href="suppliers_crud.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this supplier?');">Delete</a>
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