<?php
session_start();

include 'config.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Flight System</title>
</head>

<body>

    <nav class="navbar">
        <a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Book Flights</a>
        <a href="bookings.php" class="<?php echo ($currentPage == 'bookings.php') ? 'active' : ''; ?>">Bookings</a>
        <a href="planes.php" class="<?php echo ($currentPage == 'planes.php') ? 'active' : ''; ?>">Planes</a>
        <a href="favourites.php" class="<?php echo ($currentPage == 'favourites.php') ? 'active' : ''; ?>">Favourite Planes</a>

        <?php if (isset($_SESSION['user_name'])): ?>

            <span style="color: var(--ice-white); padding: 8px;">Welcome, <?php echo $_SESSION['user_name']; ?></span>
            <a href="logout.php">Logout</a>

        <?php else: ?>

            <a href="login.php" class="<?php echo ($currentPage == 'login.php') ? 'active' : ''; ?>">Login</a>
            <a href="signup.php" class="<?php echo ($currentPage == 'signup.php') ? 'active' : ''; ?>">Register</a>

        <?php endif; ?>
    </nav>