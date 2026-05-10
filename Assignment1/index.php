<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>O1 Airlines - Book Flights</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/book.css">
    <link rel="stylesheet" href="css/global.css">
</head>
<body>
    <?php include '../PA3/header.php'; ?>

    <main class="booking-page-container">
        <div class="booking-form-card">
            <h1 class="form-title">Book Your Flight</h1>
            
            <form action="bookings.html" class="flight-form">
                
                <div class="form-group">
                    <label class="label" for="plane-type">Aircraft Type</label>
                    <select name="plane" id="plane-type" class="form-input">
                        <option>Boeing 747</option>
                        <option>Airbus A320</option>
                        <option>Airbus A350</option>
                        <option>Boeing 737</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="departure-date">Departure Date</label>
                    <input type="date" id="departure-date" name="departure-date" class="form-input" />
                </div>

                <div class="form-group">
                    <label class="label" for="departure-airport">Departure Airport</label>
                    <select name="departure" id="departure-airport" class="form-input">
                        <option>New York (JFK)</option>
                        <option>Los Angeles (LAX)</option>
                        <option>Chicago (ORD)</option>
                        <option>London (LHR)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="arrival-airport">Arrival Airport</label>
                    <select name="arrival" id="arrival-airport" class="form-input">
                        <option>London (LHR)</option>
                        <option>New York (JFK)</option>
                        <option>Los Angeles (LAX)</option>
                        <option>Chicago (ORD)</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label class="label">Cabin Class</label>
                    <div class="checkbox-container">
                        <label><input type="radio" name="cabin" checked> Economy</label>
                        <label><input type="radio" name="cabin"> Business</label>
                        <label><input type="radio" name="cabin"> First Class</label>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label class="label" style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="return-flight" class="accent-checkbox"> Include Return Flight
                    </label>
                </div>

                <div class="form-group full-width">
                    <a href="bookings.html" class="button submit-btn">Search Available Flights</a>
                </div>

            </form>
        </div>
    </main>
</body>
</html>