<?php
function csrf_token(): string
{
  global $CONFIG;
  $k = $CONFIG['security']['csrf_key'];
  if (empty($_SESSION[$k]))
    $_SESSION[$k] = bin2hex(random_bytes(16));
  return $_SESSION[$k];
}
function csrf_check(): void
{
  global $CONFIG;
  $k = $CONFIG['security']['csrf_key'];
  $t = (string) ($_POST['csrf'] ?? '');
  if (!$t || !hash_equals($_SESSION[$k] ?? '', $t)) {
    http_response_code(400);
    exit("CSRF invalid.");
  }
}
function ip_hash(): string
{
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  return hash('sha256', $ip . '|kelion');
}
function ua_hash(): string
{
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  return hash('sha256', $ua . '|kelion');
}
function traffic_log(string $event, ?string $path = null): void
{
  $db = db();
  $uid = $_SESSION['uid'] ?? null;
  $sid = session_id();
  $stmt = $db->prepare("INSERT INTO traffic_events(user_id,session_id,event_type,path,ip_hash,ua_hash) VALUES(:u,:s,:e,:p,:ip,:ua)");
  $stmt->bindValue(':u', $uid, $uid === null ? SQLITE3_NULL : SQLITE3_INTEGER);
  $stmt->bindValue(':s', $sid, SQLITE3_TEXT);
  $stmt->bindValue(':e', $event, SQLITE3_TEXT);
  $stmt->bindValue(':p', $path ?? ($_SERVER['REQUEST_URI'] ?? ''), SQLITE3_TEXT);
  $stmt->bindValue(':ip', ip_hash(), SQLITE3_TEXT);
  $stmt->bindValue(':ua', ua_hash(), SQLITE3_TEXT);
  $stmt->execute();
}

// Complete visitor tracking with geolocation
function track_visitor(): void
{
  $db = db();
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $today = date('Y-m-d');

  // Check if visitor already recorded today
  $stmt = $db->prepare("SELECT id, page_views FROM visitors WHERE ip_address=:ip AND visit_date=:d LIMIT 1");
  $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
  $stmt->bindValue(':d', $today, SQLITE3_TEXT);
  $existing = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

  if ($existing) {
    // Update existing visitor
    $stmt = $db->prepare("UPDATE visitors SET page_views=page_views+1, last_activity_at=datetime('now'), user_id=:uid WHERE id=:id");
    $stmt->bindValue(':uid', $_SESSION['uid'] ?? null, $_SESSION['uid'] ? SQLITE3_INTEGER : SQLITE3_NULL);
    $stmt->bindValue(':id', (int) $existing['id'], SQLITE3_INTEGER);
    $stmt->execute();
    return;
  }

  // Parse user agent
  $parsedUA = parse_user_agent($ua);

  // Get geolocation (using free IP-API)
  $geo = get_ip_geolocation($ip);

  // Detect if bot
  $isBot = preg_match('/(bot|crawler|spider|scraper|curl|wget)/i', $ua) ? 1 : 0;

  // Insert new visitor
  $stmt = $db->prepare("INSERT INTO visitors(
    ip_address, ip_hash, country, country_code, city, region, timezone, isp,
    user_agent, browser, browser_version, os, os_version, device_type,
    referrer, landing_page, user_id, is_bot, visit_date
  ) VALUES(
    :ip, :iph, :country, :cc, :city, :region, :tz, :isp,
    :ua, :browser, :bv, :os, :osv, :device,
    :ref, :landing, :uid, :bot, :date
  )");

  $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
  $stmt->bindValue(':iph', ip_hash(), SQLITE3_TEXT);
  $stmt->bindValue(':country', $geo['country'] ?? 'Unknown', SQLITE3_TEXT);
  $stmt->bindValue(':cc', $geo['countryCode'] ?? '??', SQLITE3_TEXT);
  $stmt->bindValue(':city', $geo['city'] ?? '', SQLITE3_TEXT);
  $stmt->bindValue(':region', $geo['regionName'] ?? '', SQLITE3_TEXT);
  $stmt->bindValue(':tz', $geo['timezone'] ?? '', SQLITE3_TEXT);
  $stmt->bindValue(':isp', $geo['isp'] ?? '', SQLITE3_TEXT);
  $stmt->bindValue(':ua', $ua, SQLITE3_TEXT);
  $stmt->bindValue(':browser', $parsedUA['browser'], SQLITE3_TEXT);
  $stmt->bindValue(':bv', $parsedUA['browser_version'], SQLITE3_TEXT);
  $stmt->bindValue(':os', $parsedUA['os'], SQLITE3_TEXT);
  $stmt->bindValue(':osv', $parsedUA['os_version'], SQLITE3_TEXT);
  $stmt->bindValue(':device', $parsedUA['device'], SQLITE3_TEXT);
  $stmt->bindValue(':ref', $_SERVER['HTTP_REFERER'] ?? '', SQLITE3_TEXT);
  $stmt->bindValue(':landing', $_SERVER['REQUEST_URI'] ?? '/', SQLITE3_TEXT);
  $stmt->bindValue(':uid', $_SESSION['uid'] ?? null, empty($_SESSION['uid']) ? SQLITE3_NULL : SQLITE3_INTEGER);
  $stmt->bindValue(':bot', $isBot, SQLITE3_INTEGER);
  $stmt->bindValue(':date', $today, SQLITE3_TEXT);
  $stmt->execute();
}

// Parse user agent to extract browser, OS, device
function parse_user_agent(string $ua): array
{
  $result = ['browser' => 'Unknown', 'browser_version' => '', 'os' => 'Unknown', 'os_version' => '', 'device' => 'Desktop'];

  // Detect browser
  if (preg_match('/Firefox\/(\d+)/i', $ua, $m)) {
    $result['browser'] = 'Firefox';
    $result['browser_version'] = $m[1];
  } elseif (preg_match('/Edg\/(\d+)/i', $ua, $m)) {
    $result['browser'] = 'Edge';
    $result['browser_version'] = $m[1];
  } elseif (preg_match('/Chrome\/(\d+)/i', $ua, $m)) {
    $result['browser'] = 'Chrome';
    $result['browser_version'] = $m[1];
  } elseif (preg_match('/Safari\/(\d+)/i', $ua, $m) && strpos($ua, 'Chrome') === false) {
    $result['browser'] = 'Safari';
    $result['browser_version'] = $m[1];
  } elseif (preg_match('/MSIE (\d+)/i', $ua, $m)) {
    $result['browser'] = 'IE';
    $result['browser_version'] = $m[1];
  } elseif (preg_match('/Trident.*rv:(\d+)/i', $ua, $m)) {
    $result['browser'] = 'IE';
    $result['browser_version'] = $m[1];
  } elseif (preg_match('/Opera\/(\d+)/i', $ua, $m)) {
    $result['browser'] = 'Opera';
    $result['browser_version'] = $m[1];
  }

  // Detect OS
  if (preg_match('/Windows NT ([\d.]+)/i', $ua, $m)) {
    $result['os'] = 'Windows';
    $result['os_version'] = $m[1];
  } elseif (preg_match('/Mac OS X ([\d_]+)/i', $ua, $m)) {
    $result['os'] = 'macOS';
    $result['os_version'] = str_replace('_', '.', $m[1]);
  } elseif (preg_match('/Linux/i', $ua)) {
    $result['os'] = 'Linux';
  } elseif (preg_match('/Android ([\d.]+)/i', $ua, $m)) {
    $result['os'] = 'Android';
    $result['os_version'] = $m[1];
    $result['device'] = 'Mobile';
  } elseif (preg_match('/iPhone|iPad/i', $ua)) {
    $result['os'] = 'iOS';
    $result['device'] = 'Mobile';
    if (preg_match('/OS ([\d_]+)/i', $ua, $m))
      $result['os_version'] = str_replace('_', '.', $m[1]);
  }

  // Detect device type
  if (preg_match('/Mobile|Android|iPhone|iPod/i', $ua))
    $result['device'] = 'Mobile';
  elseif (preg_match('/iPad|Tablet/i', $ua))
    $result['device'] = 'Tablet';

  return $result;
}

// Get geolocation from IP using free API
function get_ip_geolocation(string $ip): array
{
  // Skip local IPs
  if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
    return ['country' => 'Local', 'countryCode' => 'LO', 'city' => 'localhost'];
  }

  // Use ip-api.com (free, no API key required, 45 requests/minute)
  $url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city,timezone,isp";

  $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
  $json = @file_get_contents($url, false, $ctx);

  if ($json) {
    $data = json_decode($json, true);
    if ($data && ($data['status'] ?? '') === 'success') {
      return $data;
    }
  }

  return ['country' => 'Unknown', 'countryCode' => '??'];
}


function security_headers(): void
{
  // Basic hardening. Adjust CSP when you add external CDNs.
  $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  header("X-Content-Type-Options: nosniff");
  header("X-Frame-Options: DENY");
  header("Referrer-Policy: strict-origin-when-cross-origin");
  header("Permissions-Policy: geolocation=(), microphone=(self), camera=()");
  header("Cross-Origin-Opener-Policy: same-origin");
  header("Cross-Origin-Resource-Policy: same-origin");
  header("Cross-Origin-Embedder-Policy: unsafe-none");

  // CSP: allow self + inline scripts/styles + CDNs for Three.js and Google Fonts
  header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; media-src 'self' blob:; connect-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; base-uri 'self'; frame-ancestors 'none'");

  if ($isHttps)
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

function rate_limit_hit(string $key, int $limit, int $windowSec): bool
{
  // Returns true if allowed, false if blocked
  $db = db();
  $now = time();
  $db->exec('CREATE TABLE IF NOT EXISTS rate_limits(key TEXT PRIMARY KEY, count INTEGER NOT NULL, reset_at INTEGER NOT NULL)');
  $stmt = $db->prepare("SELECT count, reset_at FROM rate_limits WHERE key=:k");
  $stmt->bindValue(':k', $key, SQLITE3_TEXT);
  $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

  if (!$row || (int) $row['reset_at'] <= $now) {
    $stmt2 = $db->prepare("INSERT OR REPLACE INTO rate_limits(key,count,reset_at) VALUES(:k,1,:r)");
    $stmt2->bindValue(':k', $key, SQLITE3_TEXT);
    $stmt2->bindValue(':r', $now + $windowSec, SQLITE3_INTEGER);
    $stmt2->execute();
    return true;
  }

  $count = (int) $row['count'];
  if ($count >= $limit)
    return false;

  $stmt3 = $db->prepare("UPDATE rate_limits SET count=count+1 WHERE key=:k");
  $stmt3->bindValue(':k', $key, SQLITE3_TEXT);
  $stmt3->execute();
  return true;
}
