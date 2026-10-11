<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Normalize SCRIPT_NAME when subdomain DocumentRoot points to project root via .htaccess
if (
    isset($_SERVER['SCRIPT_NAME'], $_SERVER['REQUEST_URI'])
    && str_ends_with($_SERVER['SCRIPT_NAME'], '/public/index.php')
    && ! str_starts_with($_SERVER['REQUEST_URI'], '/public')
) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
}

// Automatically create .env from .env.example on first deploy if missing
$envPath = __DIR__.'/../.env';
$envExamplePath = __DIR__.'/../.env.example';
if (! file_exists($envPath) && file_exists($envExamplePath)) {
    @copy($envExamplePath, $envPath);
}

if (PHP_VERSION_ID < 80300) {
    http_response_code(500);
    echo '<h3>Versi PHP Server Saat Ini: '.PHP_VERSION.'</h3>';
    echo '<p>Aplikasi Laravel 13 ini membutuhkan <strong>PHP 8.3 atau PHP 8.4</strong>. Silakan ubah versi PHP subdomain <code>ujian.nazwagraha.com</code> menjadi <strong>PHP 8.3 / 8.4</strong> melalui menu <strong>MultiPHP Manager</strong> atau <strong>Select PHP Version</strong> di cPanel.</p>';
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
if (! file_exists(__DIR__.'/../vendor/autoload.php')) {
    http_response_code(500);
    echo '<h3>Folder vendor/ belum terunduh</h3><p>Silakan jalankan <code>git pull origin main</code> di terminal hosting Anda.</p>';
    exit;
}

require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
