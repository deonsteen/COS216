<?php
// Name: [Your Name] | Surname: [Your Surname] | Student Number: u25135742
// COS216 PA4 - logout.php
// Clears localStorage and session, then redirects to login

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging out...</title>
</head>
<body>
<script>
    localStorage.removeItem('apikey');
    localStorage.removeItem('user_name');
    window.location.href = '../PA3/login.php';
</script>
</body>
</html>
