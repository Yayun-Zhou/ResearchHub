<?php
session_start();

/*
|--------------------------------------------------------------------------
| Research Hub - Entry Point
|--------------------------------------------------------------------------
| This file is the first page users load when visiting:
|     http://localhost/ResearchHub/
|
| - If user is logged in → go to dashboard.php
| - If not logged in → go to login.php
|--------------------------------------------------------------------------
*/

// User logged in → go to dashboard
if (isset($_SESSION['UserID']) && !empty($_SESSION['UserID'])) {
    header("Location: dashboard.php");
    exit;
}

// User not logged in → go to login
header("Location: login.php");
exit;

?>