<?php
// Fix redirection to point to the correct login page
// Add a debug message to confirm script execution
session_start();
session_unset();
session_destroy();
header("Location: index.php");
exit();
?> 