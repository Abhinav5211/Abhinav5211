<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    echo '<script>alert("Access denied: Admins only.");window.location.href="dashboard.php";</script>';
    exit();
}
include 'db.php';
$message = '';
$type = 'success';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ? AND role = 'customer'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Customer deleted successfully!";
    } else {
        $message = "Error deleting customer: " . $stmt->error;
        $type = 'error';
    }
    $stmt->close();
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['edit'])) {
        $id = $_POST['id'];
        $username = $_POST['username'];
        $name = $_POST['name'];
        $email = $_POST['email'];

        $stmt = $conn->prepare("UPDATE customers SET username = ?, name = ?, email = ? WHERE id = ? AND role = 'customer'");
        $stmt->bind_param("sssi", $username, $name, $email, $id);

        if ($stmt->execute()) {
            $message = "Customer updated successfully!";
        } else {
            $message = "Error updating customer: " . $stmt->error;
            $type = 'error';
        }
        $stmt->close();
    } else {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $name = $_POST['name'];
        $email = $_POST['email'];
        $role = 'customer';

        $stmt = $conn->prepare("INSERT INTO customers (username, password, role, name, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $password, $role, $name, $email);

        if ($stmt->execute()) {
            $message = "Customer added successfully!";
        } else {
            $message = "Error adding customer: " . $stmt->error;
            $type = 'error';
        }
        $stmt->close();
    }
}

$query = "SELECT * FROM customers WHERE role = 'customer' ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    die('MySQL Error: ' . $conn->error);
}
?>
<?php include 'header.php'; ?>
<div class="layout">
    <?php include 'sidenav.php'; ?>
    <div class="main-content">
        <h2>Customer Management</h2>
        <div class="form-container">
            <div>
                <h3 style="text-align: center;">Add New Customer</h3>
                <form method="POST" class="add-form">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <button type="submit">Add Customer</button>
                </form>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                            <td>
                                <button onclick="editCustomer(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="edit-btn">Edit</button>
                                <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this customer?')" class="delete-btn">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No customers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="script.js"></script>
<script>
function editCustomer(customer) {
    document.getElementById('edit_id').value = customer.id;
    document.getElementById('edit_username').value = customer.username;
    document.getElementById('edit_name').value = customer.name;
    document.getElementById('edit_email').value = customer.email;

    document.getElementById('editModal').style.display = 'block';
}

if (document.querySelector('.close')) {
    document.querySelector('.close').onclick = function() {
        document.getElementById('editModal').style.display = 'none';
    }
}

window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        document.getElementById('editModal').style.display = 'none';
    }
}
</script>
<?php if ($message): ?>
<script>showAlert("<?php echo addslashes($message); ?>", "<?php echo $type; ?>");</script>
<?php endif; ?>
</body>
</html>
