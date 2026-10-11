<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

define('LARAVEL_START', microtime(true));

// Normalize SCRIPT_NAME when subdomain DocumentRoot points to project root via .htaccess
if (
    isset($_SERVER['SCRIPT_NAME'], $_SERVER['REQUEST_URI'])
    && substr($_SERVER['SCRIPT_NAME'], -17) === '/public/index.php'
    && strpos($_SERVER['REQUEST_URI'], '/public') !== 0
) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
}

// Automatically create or repair .env on hosting (e.g., empty APP_KEY=)
$envPath = __DIR__.'/../.env';
$envExamplePath = __DIR__.'/../.env.example';
if (! file_exists($envPath) && file_exists($envExamplePath)) {
    @copy($envExamplePath, $envPath);
}

if (file_exists($envPath) && is_writable($envPath)) {
    $envContent = (string) file_get_contents($envPath);
    $envUpdated = false;

    if (preg_match('/^APP_KEY=\s*$/m', $envContent)) {
        $generatedKey = 'base64:'.base64_encode(random_bytes(32));
        $envContent = (string) preg_replace('/^APP_KEY=\s*$/m', 'APP_KEY='.$generatedKey, $envContent);
        $envUpdated = true;
    }

    if (preg_match('/^SESSION_DRIVER=database\s*$/m', $envContent)) {
        $envContent = (string) preg_replace('/^SESSION_DRIVER=database\s*$/m', 'SESSION_DRIVER=file', $envContent);
        $envUpdated = true;
    }

    if (preg_match('/^CACHE_STORE=database\s*$/m', $envContent)) {
        $envContent = (string) preg_replace('/^CACHE_STORE=database\s*$/m', 'CACHE_STORE=file', $envContent);
        $envUpdated = true;
    }

    if ($envUpdated) {
        @file_put_contents($envPath, $envContent);
    }
}

if (PHP_VERSION_ID < 80300) {
    http_response_code(500);
    echo '<div style="font-family:sans-serif;max-width:680px;margin:40px auto;padding:24px;background:#0f172a;color:#f8fafc;border:2px solid #38bdf8;border-radius:16px;">';
    echo '<h2 style="color:#fbbf24;margin-top:0;">⚠️ Versi PHP Server Saat Ini: '.PHP_VERSION.'</h2>';
    echo '<p>Aplikasi <strong>Ujian Online (Laravel 13)</strong> membutuhkan minimal <strong>PHP 8.3 atau PHP 8.4</strong>.</p>';
    echo '<p>Silakan buka <strong>cPanel &rarr; MultiPHP Manager</strong> (atau <strong>Select PHP Version</strong>), pilih domain <code>ujian.nazwagraha.com</code>, lalu ubah ke <strong>PHP 8.3</strong> atau <strong>PHP 8.4</strong>.</p>';
    echo '</div>';
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
if (! file_exists(__DIR__.'/../vendor/autoload.php')) {
    http_response_code(500);
    echo '<div style="font-family:sans-serif;max-width:680px;margin:40px auto;padding:24px;background:#0f172a;color:#f8fafc;border:2px solid #f43f5e;border-radius:16px;">';
    echo '<h2 style="color:#fb7185;margin-top:0;">⚠️ Folder vendor/ Belum Terunduh</h2>';
    echo '<p>Silakan jalankan perintah berikut di Terminal cPanel (<code>~/ujian.nazwagraha.com</code>):</p>';
    echo '<pre style="background:#070b19;padding:12px;border-radius:8px;color:#38bdf8;">git pull origin main</pre>';
    echo '</div>';
    exit;
}

require __DIR__.'/../vendor/autoload.php';

try {
    /** @var Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';

    $request = Request::capture();

    // Auto-migrate & seed database on first visit if tables do not exist yet
    $installedMarker = __DIR__.'/../storage/framework/cbt_migrated_v2.flag';
    if (! file_exists($installedMarker)) {
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $kernel->bootstrap();

        if (! Schema::hasTable('exams') || ! Schema::hasColumn('users', 'username')) {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
        }

        @file_put_contents($installedMarker, date('c'));
    }

    $app->handleRequest($request);
} catch (Throwable $e) {
    http_response_code(500);
    echo '<div style="font-family:sans-serif;max-width:760px;margin:40px auto;padding:24px;background:#0f172a;color:#f8fafc;border:2px solid #f43f5e;border-radius:16px;">';
    echo '<h2 style="color:#fb7185;margin-top:0;">⚠️ Terjadi Kesalahan Konfigurasi Hosting</h2>';
    echo '<p><strong>Pesan Error:</strong> <code>'.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</code></p>';
    echo '<p style="font-size:13px;color:#94a3b8;">File: '.htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8').' (Baris '.$e->getLine().')</p>';
    echo '</div>';
}
