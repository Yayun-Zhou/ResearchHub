<?php
/**
 * Template. Copy to config.php and fill in real values.
 * Copy config.example.php to config.php and fill in your own values.
 */

// Database
define('DB_HOST',       'localhost');
define('DB_NAME',       'projectDB3');

define('DB_ADMIN_USER', 'admin');
define('DB_ADMIN_PASS', 'CHANGE-ME');

define('DB_APP_USER',   'app_user');
define('DB_APP_PASS',   'CHANGE-ME');

// HMAC key used to sign password-reset tokens.
define('SECRET_KEY',    'CHANGE-ME-run: php -r "echo bin2hex(random_bytes(32));"');
