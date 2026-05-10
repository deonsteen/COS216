document.getElementById('signupForm').addEventListener('submit', function (event) {
    event.preventDefault();

    const name     = document.getElementById('name').value.trim();
    const surname  = document.getElementById('surname').value.trim();
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const type     = document.getElementById('type').value;
    const errorDiv = document.getElementById('error-message');

    errorDiv.textContent = "";

    if (!name || !surname || !email || !password) {
        errorDiv.textContent = "All fields are required.";
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorDiv.textContent = "Please enter a valid email address containing an '@'.";
        return;
    }

    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{9,}$/;
    if (!passwordRegex.test(password)) {
        errorDiv.textContent = "Password must be more than 8 characters and contain at least one uppercase letter, one lowercase letter, one digit, and one symbol.";
        return;
    }

    const requestData = {
        type:      "Register",
        name:      name,
        surname:   surname,
        email:     email,
        password:  password,
        user_type: type
    };

    fetch('https://wheatley.cs.up.ac.za/u25135742/api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(function(response) {
        return response.json();
    })

    
    .then(function(data) {
        if (data.status === "success") {
            window.location.href = "../PA1/index.php";
        } else if (data.status === "error") {
            errorDiv.textContent = data.data;
        } else {
            errorDiv.textContent = "An unexpected response was received from the server.";
        }
    })
    .catch(function(error) {
        console.error("Fetch error:", error);
        errorDiv.textContent = "Could not reach the server. Please check your connection and try again.";
    });
});