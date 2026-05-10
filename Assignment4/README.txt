COS216 PA4 - README
Student Number: u25135742
========================================

HOW TO USE THE WEBSITE
----------------------------------------
1. Register an account on the Register page or use the default login below.
2. Log in with your email and password.
3. Browse planes on the Planes page — use the search bar and filters to narrow results.
4. Click View on any plane to see its full technical specifications.
5. Click Add to Favourites to save a plane to your Favourites page.
6. Use the Book Flights page to select a plane, departure airport, arrival airport,
   departure date, and optionally a return date and number of passengers.
7. View your booked flights on the Bookings page and cancel them if needed.
8. Click Logout in the navbar to log out.

DEFAULT LOGIN DETAILS
----------------------------------------
Email:    tony@starkindustries.com
Password: LoveYou3000!

LOCAL STORAGE VS COOKIE - EXPLANATION
----------------------------------------
I chose to store the API key in localStorage rather than a cookie for the
following reasons:

1. The API key only needs to be sent with explicit JavaScript fetch/XHR requests,
   not with every single HTTP request the browser makes. Cookies are automatically
   attached to every request (page loads, image requests, etc.), which is
   unnecessary overhead for an API key that only matters for API calls.

2. localStorage is simpler to read and write in JavaScript using
   localStorage.setItem() and localStorage.getItem(), with no expiry or
   path attributes to manage.

3. localStorage persists across browser tabs, so the user stays logged in
   if they open the site in a new tab.

4. The trade-off is that localStorage is accessible to any JavaScript running
   on the page (no HttpOnly flag like cookies have). However, since the API
   validates the key on every request server-side, and our pages do not load
   third-party scripts that could steal it, this risk is acceptable for this
   application.

FUNCTIONALITY NOT IMPLEMENTED
----------------------------------------
- (Fill in anything you did not complete)

BONUS FEATURES IMPLEMENTED
----------------------------------------
- (Fill in any bonus features you added)
