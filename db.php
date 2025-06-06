<?php
// db.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "store_billing";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?> 