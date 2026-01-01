<?php
function current_user(): ?array {
  if (empty($_SESSION['uid'])) return null;
  $uid = (int)$_SESSION['uid'];
  $stmt = db()->prepare("SELECT id,username,email,role,status,email_verified FROM users WHERE id=:i");
  $stmt->bindValue(':i',$uid,SQLITE3_INTEGER);
  $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
  return $row ?: null;
}
function require_login(): void {
  if (empty($_SESSION['uid'])) redirect('k.php?r=login');
}
function require_admin(): void {
  require_login();
  $u = current_user();
  if (!$u || $u['role'] !== 'admin') { http_response_code(403); exit("Forbidden"); }
}
function login_attempt(string $username, string $password): bool {
  $stmt = db()->prepare("SELECT id,passhash,role,status FROM users WHERE username=:u");
  $stmt->bindValue(':u',$username,SQLITE3_TEXT);
  $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
  if (!$row) return false;
  if (($row['status'] ?? '') !== 'active') return false;
  if (!password_verify($password, $row['passhash'])) return false;

  $_SESSION['uid'] = (int)$row['id'];
  $_SESSION['role'] = $row['role'];
  $_SESSION['ui_lang'] = 'English';
  $_SESSION['voice'] = null;

  $stmt2 = db()->prepare("UPDATE users SET last_login_at=datetime('now') WHERE id=:i");
  $stmt2->bindValue(':i',(int)$row['id'],SQLITE3_INTEGER);
  $stmt2->execute();
  traffic_log('login_success', 'login');
  return true;
}
function logout_now(): void {
  traffic_log('logout', 'logout');
  session_unset();
  session_destroy();
}
function subscription_get_last(int $userId): ?array {
  $stmt = db()->prepare("
    SELECT s.*, p.name as plan_name, p.duration_days, p.price_minor, p.currency
    FROM subscriptions s
    JOIN plans p ON p.id=s.plan_id
    WHERE s.user_id=:u
    ORDER BY s.id DESC
    LIMIT 1
  ");
  $stmt->bindValue(':u',$userId,SQLITE3_INTEGER);
  $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
  if (!$row) return null;

  if (($row['status'] ?? '') === 'active' && !empty($row['ends_at'])) {
    $ends = strtotime($row['ends_at'].' UTC');
    if ($ends !== false && $ends < time()) {
      $stmt2 = db()->prepare("UPDATE subscriptions SET status='expired' WHERE id=:i");
      $stmt2->bindValue(':i',(int)$row['id'],SQLITE3_INTEGER);
      $stmt2->execute();
      $row['status'] = 'expired';
    }
  }
  return $row;
}
function require_active_subscription(): void {
  $u = current_user();
  if (!$u) redirect('k.php?r=login');
  if ($u['role'] === 'admin' || $u['role'] === 'demo') return;
  $sub = subscription_get_last((int)$u['id']);
  if (!$sub || ($sub['status'] ?? '') !== 'active') redirect('k.php?r=reconnect');
}
