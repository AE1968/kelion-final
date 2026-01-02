<?php
/**
 * KELION AI - Email/SMTP Functions
 * Supports both SMTP and file-based logging when SMTP is disabled
 */

/**
 * Send email using SMTP or log to file
 * @param string $to - Recipient email
 * @param string $subject - Email subject
 * @param string $body - Email body (plain text or HTML)
 * @param bool $isHtml - Whether body is HTML
 * @return bool - Success/failure
 */
function mail_send(string $to, string $subject, string $body, bool $isHtml = false): bool
{
  global $CONFIG;

  $smtp = $CONFIG['mail']['smtp'] ?? [];
  $smtpEnabled = !empty($smtp['enabled']);

  if (!$smtpEnabled) {
    // Log to file when SMTP is disabled
    $dir = __DIR__ . '/../../storage/logs';
    if (!is_dir($dir))
      @mkdir($dir, 0775, true);
    $logEntry = sprintf(
      "[%s] EMAIL (NOT SENT - SMTP DISABLED)\nTO: %s\nSUBJECT: %s\nBODY:\n%s\n----\n\n",
      date('Y-m-d H:i:s'),
      $to,
      $subject,
      $body
    );
    file_put_contents($dir . '/mail.log', $logEntry, FILE_APPEND | LOCK_EX);
    return true; // Return true to indicate "processed" even if not sent
  }

  // SMTP Configuration
  $host = $smtp['host'] ?? '';
  $port = (int) ($smtp['port'] ?? 587);
  $username = $smtp['username'] ?? '';
  $password = $smtp['password'] ?? '';
  $encryption = $smtp['encryption'] ?? 'tls';
  $from = $CONFIG['mail']['from'] ?? 'noreply@kelionai.app';

  if ($host === '' || $username === '') {
    // Missing SMTP configuration
    return false;
  }

  try {
    // Build email headers
    $headers = [];
    $headers[] = "From: KELION AI <$from>";
    $headers[] = "Reply-To: $from";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = $isHtml
      ? "Content-Type: text/html; charset=UTF-8"
      : "Content-Type: text/plain; charset=UTF-8";
    $headers[] = "X-Mailer: KELION-AI-Mailer";

    // Use PHP's mail() function with SMTP relay (works on most servers)
    // For production, consider using PHPMailer or similar library
    $success = @mail($to, $subject, $body, implode("\r\n", $headers));

    // Log attempt
    $dir = __DIR__ . '/../../storage/logs';
    if (!is_dir($dir))
      @mkdir($dir, 0775, true);
    $status = $success ? 'SENT' : 'FAILED';
    $logEntry = sprintf(
      "[%s] EMAIL %s\nTO: %s\nSUBJECT: %s\n----\n\n",
      date('Y-m-d H:i:s'),
      $status,
      $to,
      $subject
    );
    file_put_contents($dir . '/mail.log', $logEntry, FILE_APPEND | LOCK_EX);

    return $success;

  } catch (Throwable $e) {
    // Log error
    $dir = __DIR__ . '/../../storage/logs';
    if (!is_dir($dir))
      @mkdir($dir, 0775, true);
    file_put_contents($dir . '/mail.log', "[ERROR] " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    return false;
  }
}

/**
 * Alias for mail_send (compatibility)
 */
function send_mail(string $to, string $subject, string $body, bool $isHtml = false): bool
{
  return mail_send($to, $subject, $body, $isHtml);
}

