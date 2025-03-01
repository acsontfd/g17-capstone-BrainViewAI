<?php
session_start();  // Start the session

// Destroy all session data
session_unset();  // Removes all session variables
session_destroy();  // Destroys the session

// Redirect the user to the login page
header("Location: login.html");  // Adjust this to your login page URL
exit();
?>