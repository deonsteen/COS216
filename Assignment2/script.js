function loadPlanes() {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "https://wheatley.cs.up.ac.za/api/", true);
    xhr.setRequestHeader("Content-Type", "application/json");

    xhr.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            
            if (this.responseText.trim().startsWith("ERROR")) {
                console.error("The API crashed:", this.responseText);
                return;
            }

            const response = JSON.parse(this.responseText);
            const planes = response.data;

            const $container = $('#planes-container');
            $container.empty(); 

            planes.slice(0, 20).forEach(plane => {
                $container.append(`
                    <div class="premium-card">
                        <img src="${plane.image_url}" alt="${plane.model}" style="width:100%">
                        <h3>${plane.manufacturer}</h3>
                        <p><strong>Model:</strong> ${plane.model}</p>
                        <p><strong>Capacity:</strong> ${plane.seats} Seats</p>
                        <div class="card-action-group">
                            <button class="button">Add to Favourites</button>
                            <button class="button" onclick="viewPlane('${plane.id}')">View</button>
                        </div>
                    </div>
                `);
            });
        }
    };

    xhr.send(JSON.stringify({
        "studentnum": "u25135742",
        "apikey": "9199863f33a53c4381fbb5272a2b925c",
        "type": "GetAllPlanes",
        "return": "*" 
    }));
}

function loadSinglePlane() {
    const urlParams = new URLSearchParams(window.location.search);
    const planeID = urlParams.get('id');

    if (!planeID) {
        document.getElementById('plane-title').innerText = "Error: No Plane ID provided.";
        return; 
    }

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "https://wheatley.cs.up.ac.za/api/", true);
    xhr.setRequestHeader("Content-Type", "application/json");

    xhr.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            
            if (this.responseText.trim().startsWith("ERROR")) {
                console.error("API Error");
                return;
            }

            const response = JSON.parse(this.responseText);

            const plane = response.data.find(p => p.id == planeID);

            if (plane) {
                document.getElementById('plane-title').innerText = `${plane.manufacturer} ${plane.model}`;
                document.getElementById('plane-desc').innerText = plane.description;
                document.getElementById('plane-img').src = plane.image_url;
                document.getElementById('spec-seats').innerText = plane.seats;
                document.getElementById('spec-classes').innerText = plane.classes;
                
                document.getElementById('spec-range').innerText = `${plane.max_range_km} km`;
                document.getElementById('spec-speed').innerText = `${plane.max_speed_kmh} km/h`;
                document.getElementById('spec-cargo').innerText = `${plane.max_cargo_kg} kg`;
            } else {
                document.getElementById('plane-title').innerText = "Plane not found in database.";
            }
        }
    };

    xhr.send(JSON.stringify({
        "studentnum": "u25135742",
        "apikey": "9199863f33a53c4381fbb5272a2b925c",
        "type": "GetAllPlanes",
        "return": "*" 
    }));
}

function viewPlane(planeID) {
    window.location.href = `view.php?id=${planeID}`;
}

$(document).ready(function () {
    const path = window.location.pathname;
    
    if (path.includes('planes.php')) {
        loadPlanes();
    } else if (path.includes('view.php')) {
        loadSinglePlane();
    }
});