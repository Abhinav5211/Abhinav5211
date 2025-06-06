<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Billing System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    $header_link = isset($_SESSION['admin_id']) || isset($_SESSION['customer_id']) ? 'dashboard.php' : 'login.php';
    ?>
    <header>
        <div class="header-content">
            <a href="<?php echo $header_link; ?>" class="logo">Store Billing System</a>
            <div class="header-right">
                <?php if (isset($_SESSION['admin_id']) || isset($_SESSION['customer_id'])): ?>
                    <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main> 