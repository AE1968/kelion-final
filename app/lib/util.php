<?php
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $to): never { header("Location: $to"); exit; }
function json_out(array $x, int $code=200): never {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($x, JSON_UNESCAPED_UNICODE);
  exit;
}
function base_url(): string {
  global $CONFIG;
  $b = trim((string)($CONFIG['app']['base_url'] ?? ''));
  if ($b !== '') return rtrim($b,'/');
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
  return $scheme.'://'.$host.$path;
}
function asset(string $p): string { return base_url().'/'.$p; }
