<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>O1 Airlines - Planes</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/planes.css">
    <link rel="stylesheet" href="css/global.css">
</head>
<body>
    <?php include '../PA3/header.php'; ?>

    <div class="planes-page-container">

        <section class="top-filter-bar">
            <div class="filter-item">
                <label>Search Planes</label>
                <input type="text" id="search-input" placeholder="Search by model...">
            </div>

            <div class="filter-item">
                <label>Manufacturer</label>
                <select id="manufacturer-filter">
                    <option value="All">All</option>
                    <option value="Boeing">Boeing</option>
                    <option value="Airbus">Airbus</option>
                    <option value="Embraer">Embraer</option>
                    <option value="Bombardier">Bombardier</option>
                </select>
            </div>

            <div class="filter-item">
                <label>Max Seats: <span id="seat-range-label">500</span></label>
                <input type="range" id="seat-range" min="50" max="600" value="600"
                       oninput="document.getElementById('seat-range-label').textContent = this.value">
            </div>

            <div class="filter-item" style="flex: 0;">
                <button class="button" id="apply-filters">Apply Filters</button>
            </div>
        </section>

        <div class="planes-grid" id="planes-container">
            <p style="color: var(--primary-gold);">Loading planes...</p>
        </div>
    </div>

    <script src="../PA2/script.js"></script>
</body>
</html>
