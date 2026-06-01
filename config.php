<?php
declare(strict_types=1);

// Configuration MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'boutique');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuration application
define('APP_NAME', ' Accessoires');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = str_replace('\\', '/', $scriptDir);
$scriptDir = rtrim($scriptDir, '/');
define('APP_URL', $scheme . '://' . $host . (($scriptDir === '' || $scriptDir === '.') ? '' : $scriptDir));

define('UPLOAD_DIR', __DIR__ . '/uploads');
define('UPLOAD_URL', APP_URL . '/uploads');

define('CATEGORIES', ['sacs', 'bijoux', 'montres', 'lunettes', 'ceintures']);

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Africa/Casablanca');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
