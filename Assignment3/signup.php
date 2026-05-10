<!DOCTYPE html>
<!-- Name: [Your Name] | Surname: [Your Surname] | Student Number: u25135742 -->
<!-- COS216 PA4 - signup.php -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O1 Airlines - Create Account</title>
    <link rel="stylesheet" href="../PA1/css/navbar.css">
    <link rel="stylesheet" href="../PA1/css/global.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="login-wrapper">
        <div class="login-card">
            <h2 class="login-title">Create Account</h2>

            <form id="signupForm" class="login-form">
                <input type="text"     id="name"     name="name"     placeholder="First Name"   required>
                <input type="text"     id="surname"  name="surname"  placeholder="Surname"       required>
                <input type="email"    id="email"    name="email"    placeholder="Email Address" required>
                <input type="password" id="password" name="password" placeholder="Password"      required>

                <select id="type" name="type">
                    <option value="Passenger">Passenger</option>
                    <option value="ATC">ATC</option>
                </select>

                <button type="submit" class="button">Register</button>
            </form>

            <div id="error-message" class="error-text"></div>

            <p style="text-align: center; margin-top: 15px; color: var(--ice-white);">
                Already have an account?
                <a href="login.php" style="color: var(--primary-gold);">Login here</a>
            </p>
        </div>
    </div>

    <script src="validation.js"></script>

</body>
</html>
