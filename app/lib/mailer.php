<?php
function mail_send(string $to, string $subject, string $html): bool {
  global $CONFIG;
  if (empty($CONFIG['mail']['smtp']['enabled'])) {
    $dir = __DIR__ . '/../../storage/logs';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    file_put_contents($dir.'/mail.log', "TO: $to\nSUBJECT: $subject\n$html\n----\n", FILE_APPEND);
    return true;
  }
  return false;
}
