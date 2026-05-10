<!DOCTYPE html>
<!-- Name: [Your Name] | Surname: [Your Surname] | Student Number: u25135742 -->
<!-- COS216 PA4 - login.php -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O1 Airlines - Login</title>
    <link rel="stylesheet" href="../PA1/css/navbar.css">
    <link rel="stylesheet" href="../PA1/css/global.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="login-wrapper">
        <div class="login-card">
            <h2 class="login-title">Login</h2>

            <form id="loginForm" class="login-form">
                <input type="email"    id="email"    placeholder="Email Address" required>
                <input type="password" id="password" placeholder="Password"      required>
                <button type="submit" class="button">Login</button>
            </form>

            <div id="error-message" class="error-text"></div>

            <p style="text-align: center; margin-top: 15px; color: var(--ice-white);">
                Don't have an account?
                <a href="signup.php" style="color: var(--primary-gold);">Register here</a>
            </p>
            <p style="text-align: center;">
                <a href="README.txt" style="color: var(--primary-gold); font-size: 13px;">README</a>
            </p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const email    = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('error-message');

            errorDiv.textContent = '';

            fetch('<?php echo API_URL; ?>', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ type: 'Login', email: email, password: password })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    localStorage.setItem('apikey',    data.data[0].apikey);
                    localStorage.setItem('user_name', data.data[0].name);
                    window.location.href = '../PA1/index.php';
                } else {
                    errorDiv.textContent = data.data;
                }
            })
            .catch(function () {
                errorDiv.textContent = 'Could not reach the server. Please try again.';
            });
        });
    </script>

</body>
</html>
