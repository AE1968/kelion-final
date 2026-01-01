<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

$CONFIG = require __DIR__ . '/../config.php';
date_default_timezone_set($CONFIG['app']['timezone'] ?? 'Europe/London');

session_name($CONFIG['security']['session_name'] ?? 'KELIONSESS');
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => $isHttps,
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

require __DIR__ . '/lib/util.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/security.php';
security_headers();
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/mailer.php';
require __DIR__ . '/lib/openai.php';

db_init();
