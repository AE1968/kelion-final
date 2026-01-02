<?php
function current_user(): ?array
{
  if (empty($_SESSION['uid']))
    return null;
  $uid = (int) $_SESSION['uid'];
  $stmt = db()->prepare("SELECT id,username,email,role,status,email_verified FROM users WHERE id=:i");
  $stmt->bindValue(':i', $uid, SQLITE3_INTEGER);
  $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
  return $row ?: null;
}
function require_login(): void
{
  if (empty($_SESSION['uid']))
    redirect('k.php?r=login');
}
function require_admin(): void
{
  require_login();
  $u = current_user();
  if (!$u || $u['role'] !== 'admin') {
    http_response_code(403);
    exit("Forbidden");
  }
}
function login_attempt(string $username, string $password): bool
{
  $stmt = db()->prepare("SELECT id,passhash,role,status FROM users WHERE username=:u");
  $stmt->bindValue(':u', $username, SQLITE3_TEXT);
  $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
  if (!$row)
    return false;
  if (($row['status'] ?? '') !== 'active')
    return false;
  if (!password_verify($password, $row['passhash']))
    return false;

  $_SESSION['uid'] = (int) $row['id'];
  $_SESSION['role'] = $row['role'];
  $_SESSION['ui_lang'] = 'English';
  $_SESSION['voice'] = null;

  $stmt2 = db()->prepare("UPDATE users SET last_login_at=datetime('now') WHERE id=:i");
  $stmt2->bindValue(':i', (int) $row['id'], SQLITE3_INTEGER);
  $stmt2->execute();
  traffic_log('login_success', 'login');
  return true;
}
function logout_now(): void
{
  traffic_log('logout', 'logout');
  session_unset();
  session_destroy();
}
function subscription_get_last(int $userId): ?array
{
  $stmt = db()->prepare("
    SELECT s.*, p.name as plan_name, p.duration_days, p.price_minor, p.currency
    FROM subscriptions s
    JOIN plans p ON p.id=s.plan_id
    WHERE s.user_id=:u
    ORDER BY s.id DESC
    LIMIT 1
  ");
  $stmt->bindValue(':u', $userId, SQLITE3_INTEGER);
  $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
  if (!$row)
    return null;

  if (($row['status'] ?? '') === 'active' && !empty($row['ends_at'])) {
    $ends = strtotime($row['ends_at'] . ' UTC');
    if ($ends !== false && $ends < time()) {
      $stmt2 = db()->prepare("UPDATE subscriptions SET status='expired' WHERE id=:i");
      $stmt2->bindValue(':i', (int) $row['id'], SQLITE3_INTEGER);
      $stmt2->execute();
      $row['status'] = 'expired';
    }
  }
  return $row;
}
function require_active_subscription(): void
{
  $u = current_user();
  if (!$u)
    redirect('k.php?r=login');
  if ($u['role'] === 'admin' || $u['role'] === 'demo')
    return;
  $sub = subscription_get_last((int) $u['id']);
  if (!$sub || ($sub['status'] ?? '') !== 'active')
    redirect('k.php?r=reconnect');
}

/**
 * Generate password reset token for a user
 * @param string $email - User's email address
 * @return array - ['ok'=>bool, 'token'=>string] or ['ok'=>false, 'error'=>string]
 */
function password_reset_create(string $email): array
{
  $db = db();
  $stmt = $db->prepare("SELECT id, username FROM users WHERE email=:e AND status='active'");
  $stmt->bindValue(':e', $email, SQLITE3_TEXT);
  $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

  if (!$row) {
    return ['ok' => false, 'error' => 'No active account found with this email.'];
  }

  // Generate secure token
  $token = bin2hex(random_bytes(32));
  $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

  // Store token (simple approach - store in users table or create reset_tokens table)
  $stmt = $db->prepare("UPDATE users SET reset_token=:t, reset_expires=:e WHERE id=:id");
  $stmt->bindValue(':t', password_hash($token, PASSWORD_DEFAULT), SQLITE3_TEXT);
  $stmt->bindValue(':e', $expiry, SQLITE3_TEXT);
  $stmt->bindValue(':id', (int) $row['id'], SQLITE3_INTEGER);
  $stmt->execute();

  return ['ok' => true, 'token' => $token, 'user_id' => $row['id'], 'username' => $row['username']];
}

/**
 * Verify password reset token and set new password
 * @param string $email - User's email
 * @param string $token - Reset token
 * @param string $newPassword - New password
 * @return array - ['ok'=>bool] or ['ok'=>false, 'error'=>string]
 */
function password_reset_complete(string $email, string $token, string $newPassword): array
{
  $db = db();
  $stmt = $db->prepare("SELECT id, reset_token, reset_expires FROM users WHERE email=:e AND status='active'");
  $stmt->bindValue(':e', $email, SQLITE3_TEXT);
  $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

  if (!$row || empty($row['reset_token'])) {
    return ['ok' => false, 'error' => 'Invalid or expired reset request.'];
  }

  // Check expiry
  $expires = strtotime($row['reset_expires']);
  if ($expires < time()) {
    return ['ok' => false, 'error' => 'Reset link has expired. Please request a new one.'];
  }

  // Verify token
  if (!password_verify($token, $row['reset_token'])) {
    return ['ok' => false, 'error' => 'Invalid reset token.'];
  }

  // Update password and clear token
  $stmt = $db->prepare("UPDATE users SET passhash=:p, reset_token=NULL, reset_expires=NULL WHERE id=:id");
  $stmt->bindValue(':p', password_hash($newPassword, PASSWORD_DEFAULT), SQLITE3_TEXT);
  $stmt->bindValue(':id', (int) $row['id'], SQLITE3_INTEGER);
  $stmt->execute();

  return ['ok' => true];
}

/**
 * Register a new user
 * @param string $username - Desired username
 * @param string $email - Email address
 * @param string $password - Password
 * @return array - ['ok'=>bool, 'user_id'=>int] or ['ok'=>false, 'error'=>string]
 */
function user_register(string $username, string $email, string $password): array
{
  $db = db();

  // Validate
  if (strlen($username) < 3) {
    return ['ok' => false, 'error' => 'Username must be at least 3 characters.'];
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return ['ok' => false, 'error' => 'Invalid email address.'];
  }
  if (strlen($password) < 6) {
    return ['ok' => false, 'error' => 'Password must be at least 6 characters.'];
  }

  // Check if username exists
  $exists = $db->querySingle("SELECT id FROM users WHERE username='" . SQLite3::escapeString($username) . "'");
  if ($exists) {
    return ['ok' => false, 'error' => 'Username already taken.'];
  }

  // Check if email exists
  $exists = $db->querySingle("SELECT id FROM users WHERE email='" . SQLite3::escapeString($email) . "'");
  if ($exists) {
    return ['ok' => false, 'error' => 'Email already registered.'];
  }

  // Generate verification token
  $verifyToken = bin2hex(random_bytes(16));

  // Create user
  $stmt = $db->prepare("INSERT INTO users(username, email, passhash, role, status, email_verified, verify_token) VALUES(:u, :e, :p, 'user', 'active', 0, :v)");
  $stmt->bindValue(':u', $username, SQLITE3_TEXT);
  $stmt->bindValue(':e', $email, SQLITE3_TEXT);
  $stmt->bindValue(':p', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
  $stmt->bindValue(':v', $verifyToken, SQLITE3_TEXT);

  try {
    $stmt->execute();
    $userId = (int) $db->lastInsertRowID();
    return ['ok' => true, 'user_id' => $userId, 'verify_token' => $verifyToken];
  } catch (Throwable $e) {
    return ['ok' => false, 'error' => 'Registration failed: ' . $e->getMessage()];
  }
}

/**
 * Verify email with token
 */
function email_verify(string $token): bool
{
  $db = db();
  $user = $db->querySingle("SELECT id FROM users WHERE verify_token='" . SQLite3::escapeString($token) . "'", true);
  if (!$user)
    return false;

  $stmt = $db->prepare("UPDATE users SET email_verified=1, verify_token=NULL WHERE id=:id");
  $stmt->bindValue(':id', (int) $user['id'], SQLITE3_INTEGER);
  $stmt->execute();
  return true;
}

