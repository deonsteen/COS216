<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>O1 Airlines - Bookings</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/bookings.css">
    <link rel="stylesheet" href="css/global.css">
</head>

<body>

    <?php include '../PA3/header.php'; ?>

    <main class="booking-container">
        <h1 class="page-title" style="color: var(--primary-gold); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; text-align: center;">My Bookings</h1>

        <div class="card-container">

            <div class="premium-card">
                <img src="img/planes/plane1.png" alt="Boeing 747">
                <h2>Boeing 747</h2>
                <p><strong>Distance:</strong> 8,000 km</p>
                <p><strong>Price:</strong> R15,400</p>
                <p><strong>Estimated Flight Time:</strong> 10h 15m</p>
                <button class="button">Cancel Flight</button>
            </div>

            <div class="premium-card">
                <img src="img/planes/plane5.png" alt="Airbus A320">
                <h2>Airbus A320</h2>
                <p><strong>Distance:</strong> 1,200 km</p>
                <p><strong>Price:</strong> R3,200</p>
                <p><strong>Estimated Flight Time:</strong> 2h 10m</p>
                <button class="button">Cancel Flight</button>
            </div>

            <div class="premium-card">
                <img src="img/planes/plane3.png" alt="Boeing 737">
                <h2>Boeing 737</h2>
                <p><strong>Distance:</strong> 3,500 km</p>
                <p><strong>Price:</strong> R7,800</p>
                <p><strong>Estimated Flight Time:</strong> 4h 45m</p>
                <button class="button">Cancel Flight</button>
            </div>

        </div>
    </main>

</body>