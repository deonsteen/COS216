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

            <div id="booking-message" style="color: var(--primary-gold); text-align: center; min-height: 20px;"></div>

            <form id="booking-form" class="flight-form">

                <div class="form-group">
                    <label class="label" for="plane-type">Aircraft Type</label>
                    <select name="plane" id="plane-type" class="form-input">
                        <option>Loading planes...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="departure-date">Departure Date</label>
                    <input type="date" id="departure-date" name="departure-date" class="form-input" required />
                </div>

                <div class="form-group">
                    <label class="label" for="departure-airport">Departure Airport</label>
                    <select name="departure" id="departure-airport" class="form-input">
                        <option>Loading airports...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="arrival-airport">Arrival Airport</label>
                    <select name="arrival" id="arrival-airport" class="form-input">
                        <option>Loading airports...</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label class="label" style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="return-flight-toggle" name="return-flight" class="accent-checkbox">
                        Include Return Flight
                    </label>
                </div>

                <div class="form-group" id="return-date-group" style="display: none;">
                    <label class="label" for="return-date">Return Date</label>
                    <input type="date" id="return-date" name="return-date" class="form-input" />
                </div>

                <div class="form-group">
                    <label class="label" for="passengers">Number of Passengers</label>
                    <input type="number" id="passengers" name="passengers" class="form-input" min="1" value="1" required />
                </div>

                <div class="form-group full-width">
                    <button type="submit" class="button submit-btn">Book Flight</button>
                </div>

            </form>
        </div>
    </main>

    <script src="../PA2/script.js"></script>
    <script>
        // show/hide return date when checkbox is toggled
        document.getElementById('return-flight-toggle').addEventListener('change', function () {
            document.getElementById('return-date-group').style.display = this.checked ? '' : 'none';
        });

        // booking form submit — wired up in Task 5
        document.getElementById('booking-form').addEventListener('submit', function (e) {
            e.preventDefault();
            if (typeof bookFlight === 'function') {
                bookFlight();
            } else {
                document.getElementById('booking-message').textContent = 'Booking not yet implemented.';
            }
        });
    </script>
</body>
</html>
