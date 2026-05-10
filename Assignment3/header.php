<?php
// Name: [Your Name]
// Surname: [Your Surname]
// Student Number: u25135742
// COS216 PA4 - header.php
// Shared navigation bar included by all pages

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

$currentPage = basename($_SERVER['PHP_SELF']);

// Detect whether this file is being included from a PA3 page or a PA1 page
// so nav links resolve correctly on both wheatley (PA1/PA3) and locally (Assignment1/Assignment3)
$self = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$inPA3 = strpos($self, '/PA3/') !== false || strpos($self, '/Assignment3/') !== false;
$pa1 = $inPA3 ? '../PA1/' : '';
$pa3 = $inPA3 ? '' : '../PA3/';
?>
<nav class="navbar" id="main-nav">
    <a href="<?php echo $pa1; ?>index.php"
       class="<?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">Book Flights</a>
    <a href="<?php echo $pa1; ?>bookings.php"
       class="<?php echo ($currentPage === 'bookings.php') ? 'active' : ''; ?>">Bookings</a>
    <a href="<?php echo $pa1; ?>planes.php"
       class="<?php echo ($currentPage === 'planes.php') ? 'active' : ''; ?>">Planes</a>
    <a href="<?php echo $pa1; ?>favourites.php"
       class="<?php echo ($currentPage === 'favourites.php') ? 'active' : ''; ?>">Favourite Planes</a>

    <span id="nav-user-section">
        <a href="<?php echo $pa3; ?>login.php"
           id="nav-login-link"
           class="<?php echo ($currentPage === 'login.php') ? 'active' : ''; ?>">Login</a>
        <a href="<?php echo $pa3; ?>signup.php"
           id="nav-signup-link"
           class="<?php echo ($currentPage === 'signup.php') ? 'active' : ''; ?>">Register</a>
    </span>
</nav>

<script>
// Replace login/register links with welcome + logout if the user is logged in
(function () {
    var name   = localStorage.getItem('user_name');
    var apikey = localStorage.getItem('apikey');
    if (name && apikey) {
        document.getElementById('nav-user-section').innerHTML =
            '<span style="color: var(--ice-white); padding: 8px;">Welcome, ' + name + '</span>' +
            '<a href="<?php echo $pa3; ?>logout.php">Logout</a>';
    }
})();
</script>
