<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>O1 Airlines - Favourite Planes</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/planes.css">
    <link rel="stylesheet" href="css/global.css">
</head>

<body>
    <?php include '../PA3/header.php'; ?>

    <main class="planes-page-container">

        <h1
            style="color: var(--primary-gold); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; text-align: center;">
            Your Favourite Planes
        </h1>

        <div class="planes-grid">

            <div class="premium-card">
                <img src="img/planes/plane1.png" alt="Boeing 747">
                <h3>Boeing 747</h3>
                <p><strong>Capacity:</strong> 416 Seats</p>
                <div class="card-action-group">
                    <button class="button" style="background-color: #8B0000; color: var(--ice-white);">Remove</button>
                    <a href="view.html" class="button">View Details</a>
                </div>
            </div>

            <div class="premium-card">
                <img src="img/planes/plane4.png" alt="Airbus A380">
                <h3>Airbus A380</h3>
                <p><strong>Capacity:</strong> 525 Seats</p>
                <div class="card-action-group">
                    <button class="button" style="background-color: #8B0000; color: var(--ice-white);">Remove</button>
                    <a href="view.html" class="button">View Details</a>
                </div>
            </div>

        </div>
    </main>
</body>

</html>