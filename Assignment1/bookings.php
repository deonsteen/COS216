<!DOCTYPE html>
<!-- Name: [Your Name] | Surname: [Your Surname] | Student Number: u25135742 -->
<!-- COS216 PA4 - bookings.php -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>O1 Airlines - My Bookings</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/bookings.css">
    <link rel="stylesheet" href="css/global.css">
</head>
<body>

    <?php include '../PA3/header.php'; ?>

    <main class="booking-container">
        <h1 style="color: var(--primary-gold); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; text-align: center;">
            My Bookings
        </h1>

        <div class="card-container" id="bookings-container">
            <p style="color: var(--primary-gold);">Loading bookings...</p>
        </div>
    </main>

    <script src="../PA2/script.js"></script>

</body>
</html>
