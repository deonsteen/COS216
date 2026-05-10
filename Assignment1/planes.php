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
                <input type="text" placeholder="Search planes...">
            </div>
            
            <div class="filter-item">
                <label>Manufacturer</label>
                <select>
                    <option>All</option>
                    <option>Boeing</option>
                    <option>Airbus</option>
                </select>
            </div>

            <div class="filter-item">
                <label>Seat Range (50 - 500)</label>
                <input type="range" min="50" max="500">
            </div>

            <div class="filter-item">
                <label>Cabin Class</label>
                <div class="checkbox-group">
                    <label><input type="checkbox"> Economy</label>
                    <label><input type="checkbox"> Business</label>
                    <label><input type="checkbox"> First</label>
                </div>
            </div>

            <div class="filter-item" style="flex: 0;">
                <button class="button">Apply Filters</button>
            </div>
        </section>

        <div class="planes-grid" id = "planes-container">
            <div class="premium-card">
                <img src="img/planes/plane2.png" alt="Embraer E195">
                <h3>Embraer E195</h3>
                <p><strong>Capacity:</strong> 116 Seats</p>
                <div class="card-action-group">
                    <button class="button">Add to Favourites</button>
                    <a href="view.html" class="button">View</a>
                </div>
            </div>

            <div class="premium-card">
                <img src="img/planes/plane3.png" alt="Bombardier CRJ900">
                <h3>Bombardier CRJ900</h3>
                <p><strong>Capacity:</strong> 90 Seats</p>
                <div class="card-action-group">
                    <button class="button">Add to Favourites</button>
                    <a href="view.html" class="button">View</a>
                </div>
            </div>

            <div class="premium-card">
                <img src="img/planes/plane4.png" alt="Airbus A380">
                <h3>Airbus A380</h3>
                <p><strong>Capacity:</strong> 525 Seats</p>
                <div class="card-action-group">
                    <button class="button">Add to Favourites</button>
                    <a href="view.html" class="button">View</a>
                </div>
            </div>

            <div class="premium-card">
                <img src="img/planes/plane1.png" alt="Boeing 747">
                <h3>Boeing 747</h3>
                <p><strong>Capacity:</strong> 416 Seats</p>
                <div class="card-action-group">
                    <button class="button">Add to Favourites</button>
                    <a href="view.html" class="button">View</a>
                </div>
            </div>

            <div class="premium-card">
                <img src="img/planes/plane5.png" alt="Airbus A220">
                <h3>Airbus A220</h3>
                <p><strong>Capacity:</strong> 135 Seats</p>
                <div class="card-action-group">
                    <button class="button">Add to Favourites</button>
                    <a href="view.html" class="button">View</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script src="../PA2/script.js"></script>
</body>
</html>