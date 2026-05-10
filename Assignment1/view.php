<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>O1 Airlines - Plane Details</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/view.css">
    <link rel="stylesheet" href="css/global.css">
</head>

<body>
    <?php include '../PA3/header.php'; ?>

    <main class="view-page-container">
        <a href="planes.php" class="back-btn">← Back to Fleet</a>

        <div class="detail-card">
            <div class="image-section">
                <img src="" alt="Plane Image" id="plane-img" class="view-img">
            </div>

            <div class="info-section">
                <h1 id="plane-title">Loading...</h1>
                <p class="description" id="plane-desc">Fetching telemetry data...</p>

                <hr class="gold-divider">

                <h3>Technical Specifications</h3>

                <div class="specs-grid">
                    <div class="spec-item">
                        <span class="spec-label">Seats</span>
                        <span class="spec-value" id="spec-seats">-</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Cabin Classes</span>
                        <span class="spec-value" id="spec-classes">-</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Max Range</span>
                        <span class="spec-value" id="spec-range">-</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Max Speed</span>
                        <span class="spec-value" id="spec-speed">-</span>
                    </div>
                    <div class="spec-item full-width">
                        <span class="spec-label">Cargo Capacity</span>
                        <span class="spec-value" id="spec-cargo">-</span>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="button">Book Flight</button>
                    <button class="button"
                        style="background: transparent; border: 1px solid var(--primary-gold); color: var(--primary-gold);">Add
                        to Favourites</button>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        
    <script src="../PA2/script.js"></script>
</body>

</html>