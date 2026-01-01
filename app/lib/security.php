<?php
function csrf_token(): string {
  global $CONFIG;
  $k = $CONFIG['security']['csrf_key'];
  if (empty($_SESSION[$k])) $_SESSION[$k] = bin2hex(random_bytes(16));
  return $_SESSION[$k];
}
function csrf_check(): void {
  global $CONFIG;
  $k = $CONFIG['security']['csrf_key'];
  $t = (string)($_POST['csrf'] ?? '');
  if (!$t || !hash_equals($_SESSION[$k] ?? '', $t)) {
    http_response_code(400);
    exit("CSRF invalid.");
  }
}
function ip_hash(): string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  return hash('sha256', $ip.'|kelion');
}
function ua_hash(): string {
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  return hash('sha256', $ua.'|kelion');
}
function traffic_log(string $event, ?string $path=null): void {
  $db = db();
  $uid = $_SESSION['uid'] ?? null;
  $sid = session_id();
  $stmt = $db->prepare("INSERT INTO traffic_events(user_id,session_id,event_type,path,ip_hash,ua_hash) VALUES(:u,:s,:e,:p,:ip,:ua)");
  $stmt->bindValue(':u', $uid, $uid===null?SQLITE3_NULL:SQLITE3_INTEGER);
  $stmt->bindValue(':s', $sid, SQLITE3_TEXT);
  $stmt->bindValue(':e', $event, SQLITE3_TEXT);
  $stmt->bindValue(':p', $path ?? ($_SERVER['REQUEST_URI'] ?? ''), SQLITE3_TEXT);
  $stmt->bindValue(':ip', ip_hash(), SQLITE3_TEXT);
  $stmt->bindValue(':ua', ua_hash(), SQLITE3_TEXT);
  $stmt->execute();
}


function security_headers(): void {
  // Basic hardening. Adjust CSP when you add external CDNs.
  $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  header("X-Content-Type-Options: nosniff");
  header("X-Frame-Options: DENY");
  header("Referrer-Policy: strict-origin-when-cross-origin");
  header("Permissions-Policy: geolocation=(), microphone=(self), camera=()");
  header("Cross-Origin-Opener-Policy: same-origin");
  header("Cross-Origin-Resource-Policy: same-origin");
  header("Cross-Origin-Embedder-Policy: unsafe-none");

  // CSP: allow self + inline scripts/styles (we use inline for HUD clock). Tighten later.
  header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; media-src 'self' blob:; connect-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; base-uri 'self'; frame-ancestors 'none'");

  if ($isHttps) header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

function rate_limit_hit(string $key, int $limit, int $windowSec): bool {
  // Returns true if allowed, false if blocked
  $db = db();
  $now = time();
  $db->exec('CREATE TABLE IF NOT EXISTS rate_limits(key TEXT PRIMARY KEY, count INTEGER NOT NULL, reset_at INTEGER NOT NULL)');
  $stmt = $db->prepare("SELECT count, reset_at FROM rate_limits WHERE key=:k");
  $stmt->bindValue(':k',$key,SQLITE3_TEXT);
  $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

  if (!$row || (int)$row['reset_at'] <= $now) {
    $stmt2 = $db->prepare("INSERT OR REPLACE INTO rate_limits(key,count,reset_at) VALUES(:k,1,:r)");
    $stmt2->bindValue(':k',$key,SQLITE3_TEXT);
    $stmt2->bindValue(':r',$now+$windowSec,SQLITE3_INTEGER);
    $stmt2->execute();
    return true;
  }

  $count = (int)$row['count'];
  if ($count >= $limit) return false;

  $stmt3 = $db->prepare("UPDATE rate_limits SET count=count+1 WHERE key=:k");
  $stmt3->bindValue(':k',$key,SQLITE3_TEXT);
  $stmt3->execute();
  return true;
}
