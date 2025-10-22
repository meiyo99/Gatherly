<?php

// load env vars
require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'gatherly');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

define('BASE_URL', $_ENV['APP_URL'] ?? 'http://localhost/gatherly');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 7200));
define('SESSION_NAME', 'gatherly_session');
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SECURE', false); // Set to true in production with HTTPS

define('HASH_COST', (int)($_ENV['HASH_COST'] ?? 10));

define('SENDGRID_API_KEY', $_ENV['SENDGRID_API_KEY'] ?? '');
define('SENDGRID_FROM_EMAIL', $_ENV['SENDGRID_FROM_EMAIL'] ?? 'noreply@gatherly.com');
define('SENDGRID_FROM_NAME', $_ENV['SENDGRID_FROM_NAME'] ?? 'Gatherly');

define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', (int)($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_SECURE', $_ENV['SMTP_SECURE'] ?? 'tls');
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@gatherly.com');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'Gatherly');

define('GOOGLE_MAPS_KEY', $_ENV['GOOGLE_MAPS_KEY'] ?? '');
define('WEATHER_API_KEY', $_ENV['WEATHER_API_KEY'] ?? '');

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

date_default_timezone_set('America/Toronto');
