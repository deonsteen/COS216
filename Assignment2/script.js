const API_URL = 'https://wheatley.cs.up.ac.za/u25135742/api.php';

function getApiKey() {
    return localStorage.getItem('apikey');
}

function requireLogin() {
    const key = getApiKey();
    if (!key) {
        window.location.href = '../PA3/login.php';
        return null;
    }
    return key;
}

// ── Planes Page ──────────────────────────────────────────────────────────────

function loadPlanes() {
    const apikey = requireLogin();
    if (!apikey) return;

    const searchVal   = (document.getElementById('search-input')       || {}).value || '';
    const manufacturer= (document.getElementById('manufacturer-filter') || {}).value || '';
    const seatMax     = (document.getElementById('seat-range')          || {}).value || 500;

    const requestBody = {
        type:   'GetAllPlanes',
        apikey: apikey,
        fuzzy:  true,
        return: '*'
    };

    const search = {};

    if (searchVal.trim() !== '') {
        search['model'] = searchVal.trim();
    }

    if (manufacturer !== '' && manufacturer !== 'All') {
        search['manufacturer'] = manufacturer;
    }

    search['seats'] = { min: 1, max: parseInt(seatMax) };

    if (Object.keys(search).length > 0) {
        requestBody.search = search;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (this.readyState !== 4) return;

        const container = document.getElementById('planes-container');

        if (this.status !== 200) {
            container.innerHTML = '<p style="color:red;">Could not load planes. Please try again.</p>';
            return;
        }

        const response = JSON.parse(this.responseText);

        if (response.status !== 'success') {
            container.innerHTML = '<p style="color:red;">' + response.data + '</p>';
            return;
        }

        const planes = response.data;
        container.innerHTML = '';

        if (planes.length === 0) {
            container.innerHTML = '<p style="color: var(--primary-gold);">No planes match your filters.</p>';
            return;
        }

        planes.forEach(function (plane) {
            const card = document.createElement('div');
            card.className = 'premium-card';
            card.innerHTML =
                '<img src="' + plane.image_url + '" alt="' + plane.manufacturer + ' ' + plane.model + '" style="width:100%">' +
                '<h3>' + plane.manufacturer + ' ' + plane.model + '</h3>' +
                '<p><strong>Capacity:</strong> ' + plane.seats + ' Seats</p>' +
                '<p><strong>Max Range:</strong> ' + plane.max_range_km + ' km</p>' +
                '<div class="card-action-group">' +
                    '<button class="button fav-btn" data-id="' + plane.id + '">Add to Favourites</button>' +
                    '<button class="button" onclick="viewPlane(' + plane.id + ')">View</button>' +
                '</div>';
            container.appendChild(card);
        });

        // wire up favourites buttons
        document.querySelectorAll('.fav-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const planeId = this.getAttribute('data-id');
                addToFavourites(planeId, this);
            });
        });
    };

    xhr.send(JSON.stringify(requestBody));
}

function addToFavourites(planeId, btn) {
    const apikey = getApiKey();
    if (!apikey) {
        alert('You must be logged in to add favourites.');
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (this.readyState !== 4) return;
        const response = JSON.parse(this.responseText);
        if (response.status === 'success') {
            btn.textContent = 'Favourited ✓';
            btn.disabled = true;
        } else {
            alert('Could not add to favourites: ' + response.data);
        }
    };

    xhr.send(JSON.stringify({
        type:     'AddFavourite',
        apikey:   apikey,
        plane_id: planeId
    }));
}

// ── View Page ────────────────────────────────────────────────────────────────

function loadSinglePlane() {
    const apikey = requireLogin();
    if (!apikey) return;

    const urlParams = new URLSearchParams(window.location.search);
    const planeID   = urlParams.get('id');

    if (!planeID) {
        document.getElementById('plane-title').innerText = 'Error: No plane ID provided.';
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (this.readyState !== 4) return;

        if (this.status !== 200) {
            document.getElementById('plane-title').innerText = 'Failed to load plane data.';
            return;
        }

        const response = JSON.parse(this.responseText);

        if (response.status !== 'success' || response.data.length === 0) {
            document.getElementById('plane-title').innerText = 'Plane not found.';
            return;
        }

        const plane = response.data.find(function (p) { return p.id == planeID; });

        if (!plane) {
            document.getElementById('plane-title').innerText = 'Plane not found in database.';
            return;
        }

        document.getElementById('plane-title').innerText  = plane.manufacturer + ' ' + plane.model;
        document.getElementById('plane-desc').innerText   = plane.description;
        document.getElementById('plane-img').src          = plane.image_url;
        document.getElementById('spec-seats').innerText   = plane.seats;
        document.getElementById('spec-classes').innerText = plane.classes;
        document.getElementById('spec-range').innerText   = plane.max_range_km + ' km';
        document.getElementById('spec-speed').innerText   = plane.max_speed_kmh + ' km/h';
        document.getElementById('spec-cargo').innerText   = plane.max_cargo_kg + ' kg';

        // wire up the favourites button on the view page
        const favBtn = document.querySelector('.action-buttons .button:last-child');
        if (favBtn) {
            favBtn.setAttribute('data-id', plane.id);
            favBtn.addEventListener('click', function () {
                addToFavourites(plane.id, favBtn);
            });
        }
    };

    xhr.send(JSON.stringify({
        type:   'GetAllPlanes',
        apikey: apikey,
        return: '*'
    }));
}

function viewPlane(planeID) {
    window.location.href = 'view.php?id=' + planeID;
}

// ── Favourites Page ──────────────────────────────────────────────────────────

function loadFavourites() {
    const apikey = requireLogin();
    if (!apikey) return;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (this.readyState !== 4) return;

        const container = document.getElementById('favourites-container');

        if (this.status !== 200) {
            container.innerHTML = '<p style="color:red;">Could not load favourites.</p>';
            return;
        }

        const response = JSON.parse(this.responseText);

        if (response.status !== 'success') {
            container.innerHTML = '<p style="color:red;">' + response.data + '</p>';
            return;
        }

        const planes = response.data;
        container.innerHTML = '';

        if (planes.length === 0) {
            container.innerHTML = '<p style="color: var(--primary-gold);">You have no favourite planes yet. Add some from the Planes page!</p>';
            return;
        }

        planes.forEach(function (plane) {
            const card = document.createElement('div');
            card.className = 'premium-card';
            card.id = 'fav-card-' + plane.id;
            card.innerHTML =
                '<img src="' + plane.image_url + '" alt="' + plane.manufacturer + ' ' + plane.model + '" style="width:100%">' +
                '<h3>' + plane.manufacturer + ' ' + plane.model + '</h3>' +
                '<p><strong>Capacity:</strong> ' + plane.seats + ' Seats</p>' +
                '<p><strong>Max Range:</strong> ' + plane.max_range_km + ' km</p>' +
                '<div class="card-action-group">' +
                    '<button class="button" style="background-color:#8B0000; color:var(--ice-white);" ' +
                        'onclick="removeFromFavourites(' + plane.id + ')">Remove</button>' +
                    '<button class="button" onclick="viewPlane(' + plane.id + ')">View Details</button>' +
                '</div>';
            container.appendChild(card);
        });
    };

    xhr.send(JSON.stringify({
        type:   'GetFavourites',
        apikey: apikey
    }));
}

function removeFromFavourites(planeId) {
    const apikey = getApiKey();
    if (!apikey) return;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (this.readyState !== 4) return;
        const response = JSON.parse(this.responseText);
        if (response.status === 'success') {
            const card = document.getElementById('fav-card-' + planeId);
            if (card) card.remove();

            const container = document.getElementById('favourites-container');
            if (container && container.children.length === 0) {
                container.innerHTML = '<p style="color: var(--primary-gold);">You have no favourite planes yet. Add some from the Planes page!</p>';
            }
        } else {
            alert('Could not remove: ' + response.data);
        }
    };

    xhr.send(JSON.stringify({
        type:     'RemoveFavourite',
        apikey:   apikey,
        plane_id: planeId
    }));
}

// ── Book Flights Page ────────────────────────────────────────────────────────

function loadBookFlightDropdowns() {
    const apikey = requireLogin();
    if (!apikey) return;

    loadPlaneOptions(apikey);
    loadAirportOptions(apikey);
}

function loadPlaneOptions(apikey) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (this.readyState !== 4 || this.status !== 200) return;

        const response = JSON.parse(this.responseText);
        if (response.status !== 'success') return;

        const select = document.getElementById('plane-type');
        if (!select) return;
        select.innerHTML = '';

        response.data.forEach(function (plane) {
            const opt = document.createElement('option');
            opt.value       = plane.id;
            opt.textContent = plane.manufacturer + ' ' + plane.model + ' (' + plane.seats + ' seats)';
            select.appendChild(opt);
        });
    };

    xhr.send(JSON.stringify({
        type:   'GetAllPlanes',
        apikey: apikey,
        return: ['id', 'manufacturer', 'model', 'seats']
    }));
}

function loadAirportOptions(apikey, page) {
    page = page || 1;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (this.readyState !== 4 || this.status !== 200) return;

        const response = JSON.parse(this.responseText);
        if (response.status !== 'success') return;

        const depSelect = document.getElementById('departure-airport');
        const arrSelect = document.getElementById('arrival-airport');
        if (!depSelect || !arrSelect) return;

        response.data.forEach(function (airport) {
            const label = airport.name + ' (' + airport.code + ')';

            const depOpt = document.createElement('option');
            depOpt.value       = airport.code;
            depOpt.textContent = label;
            depSelect.appendChild(depOpt);

            const arrOpt = document.createElement('option');
            arrOpt.value       = airport.code;
            arrOpt.textContent = label;
            arrSelect.appendChild(arrOpt);
        });

        // load next page if there are more airports
        if (response.data.length === 30) {
            loadAirportOptions(apikey, page + 1);
        }
    };

    xhr.send(JSON.stringify({
        type:   'GetAllAirports',
        apikey: apikey,
        return: ['name', 'code'],
        page:   page
    }));
}

// ── Book Flights form submit ──────────────────────────────────────────────────

function bookFlight() {
    const apikey = getApiKey();
    if (!apikey) { window.location.href = '../PA3/login.php'; return; }

    const planeId   = document.getElementById('plane-type').value;
    const depCode   = document.getElementById('departure-airport').value;
    const arrCode   = document.getElementById('arrival-airport').value;
    const depDate   = document.getElementById('departure-date').value;
    const passengers= document.getElementById('passengers').value;
    const retToggle = document.getElementById('return-flight-toggle');
    const retDate   = (retToggle && retToggle.checked)
                    ? document.getElementById('return-date').value : '';
    const msgDiv    = document.getElementById('booking-message');

    msgDiv.textContent = '';

    if (!planeId || !depCode || !arrCode || !depDate || !passengers) {
        msgDiv.style.color = 'red';
        msgDiv.textContent = 'Please fill in all required fields.';
        return;
    }
    if (depCode === arrCode) {
        msgDiv.style.color = 'red';
        msgDiv.textContent = 'Departure and arrival airports cannot be the same.';
        return;
    }
    if (retToggle && retToggle.checked && !retDate) {
        msgDiv.style.color = 'red';
        msgDiv.textContent = 'Please select a return date.';
        return;
    }

    msgDiv.style.color = 'var(--primary-gold)';
    msgDiv.textContent = 'Booking...';

    const body = {
        type:                     'BookFlight',
        apikey:                   apikey,
        plane_id:                 planeId,
        departure_airport_code:   depCode,
        arrival_airport_code:     arrCode,
        departure_date:           depDate,
        passengers:               parseInt(passengers)
    };
    if (retDate) body.return_date = retDate;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (this.readyState !== 4) return;
        const response = JSON.parse(this.responseText);
        if (response.status === 'success') {
            msgDiv.style.color = 'var(--primary-gold)';
            msgDiv.textContent = 'Flight booked successfully!';
            setTimeout(function () {
                window.location.href = 'bookings.php';
            }, 1200);
        } else {
            msgDiv.style.color = 'red';
            msgDiv.textContent = response.data;
        }
    };

    xhr.send(JSON.stringify(body));
}

// ── Bookings Page ─────────────────────────────────────────────────────────────

function formatFlightTime(minutes) {
    const h = Math.floor(minutes / 60);
    const m = Math.round(minutes % 60);
    return h + 'h ' + m + 'm';
}

function loadBookings() {
    const apikey = requireLogin();
    if (!apikey) return;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (this.readyState !== 4) return;

        const container = document.getElementById('bookings-container');

        if (this.status !== 200) {
            container.innerHTML = '<p style="color:red;">Could not load bookings.</p>';
            return;
        }

        const response = JSON.parse(this.responseText);

        if (response.status !== 'success') {
            container.innerHTML = '<p style="color:red;">' + response.data + '</p>';
            return;
        }

        const bookings = response.data;
        container.innerHTML = '';

        if (bookings.length === 0) {
            container.innerHTML = '<p style="color: var(--primary-gold);">You have no bookings yet. <a href="index.php" style="color:var(--primary-gold);">Book a flight!</a></p>';
            return;
        }

        bookings.forEach(function (b) {
            const card = document.createElement('div');
            card.className = 'premium-card';
            card.id = 'booking-card-' + b.booking_id;
            card.innerHTML =
                '<img src="' + b.image_url + '" alt="' + b.manufacturer + ' ' + b.model + '" style="width:100%">' +
                '<h2>' + b.manufacturer + ' ' + b.model + '</h2>' +
                '<p><strong>Route:</strong> ' + (b.departure_city || b.departure_airport_code) +
                    ' → ' + (b.arrival_city || b.arrival_airport_code) + '</p>' +
                '<p><strong>Departure Date:</strong> ' + b.departure_date + '</p>' +
                '<p><strong>Passengers:</strong> ' + b.passengers + '</p>' +
                '<p><strong>Distance:</strong> ' + parseFloat(b.distance).toFixed(0) + ' km</p>' +
                '<p><strong>Flight Time:</strong> ' + formatFlightTime(b.flight_time) + '</p>' +
                '<button class="button" style="background-color:#8B0000; color:var(--ice-white);" ' +
                    'onclick="cancelBooking(' + b.booking_id + ')">Cancel Booking</button>';
            container.appendChild(card);
        });
    };

    xhr.send(JSON.stringify({ type: 'GetBookings', apikey: apikey }));
}

function cancelBooking(bookingId) {
    const apikey = getApiKey();
    if (!apikey) return;

    if (!confirm('Are you sure you want to cancel this booking?')) return;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', API_URL, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (this.readyState !== 4) return;
        const response = JSON.parse(this.responseText);
        if (response.status === 'success') {
            const card = document.getElementById('booking-card-' + bookingId);
            if (card) card.remove();
            const container = document.getElementById('bookings-container');
            if (container && container.children.length === 0) {
                container.innerHTML = '<p style="color: var(--primary-gold);">You have no bookings yet. <a href="index.php" style="color:var(--primary-gold);">Book a flight!</a></p>';
            }
        } else {
            alert('Could not cancel: ' + response.data);
        }
    };

    xhr.send(JSON.stringify({ type: 'CancelBooking', apikey: apikey, booking_id: bookingId }));
}

// ── Init ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const path = window.location.pathname;

    if (path.includes('planes.php')) {
        loadPlanes();

        const applyBtn = document.getElementById('apply-filters');
        if (applyBtn) {
            applyBtn.addEventListener('click', loadPlanes);
        }

        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') loadPlanes();
            });
        }

    } else if (path.includes('view.php')) {
        loadSinglePlane();

    } else if (path.includes('favourites.php')) {
        loadFavourites();

    } else if (path.includes('bookings.php')) {
        loadBookings();

    } else if (path.includes('index.php')) {
        loadBookFlightDropdowns();
    }
});
