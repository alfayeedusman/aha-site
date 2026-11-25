<?php
// Shared helpers for the cookie-free visit counter and the admin dashboard.
// Data lives OUTSIDE the web root in ~/aha-analytics (kept across deploys):
//   stats.sqlite  visits and clicks (no IP addresses stored)
//   geo.sqlite    IP -> country ranges (DB-IP Lite, CC BY 4.0)
//   admin.hash    password hash for /admin (set with deploy/set-admin-password.sh)
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === '_stats.php') { http_response_code(404); exit; }

function aha_data_dir(): string {
  $home = getenv('HOME');
  if (!$home && function_exists('posix_getpwuid')) $home = posix_getpwuid(posix_geteuid())['dir'] ?? '';
  if (!$home) $home = dirname(__DIR__, 3);
  return rtrim($home, '/') . '/aha-analytics';
}

function aha_db(): PDO {
  static $db = null;
  if ($db) return $db;
  $dir = aha_data_dir();
  if (!is_dir($dir)) mkdir($dir, 0700, true);
  $db = new PDO('sqlite:' . $dir . '/stats.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  $db->exec('PRAGMA journal_mode=WAL; PRAGMA busy_timeout=3000;');
  $db->exec('CREATE TABLE IF NOT EXISTS hits (
      id INTEGER PRIMARY KEY, day TEXT NOT NULL, ts INTEGER NOT NULL, kind TEXT NOT NULL,
      path TEXT, ref TEXT, cc TEXT, device TEXT, vh TEXT)');
  $db->exec('CREATE INDEX IF NOT EXISTS hits_day ON hits(day, kind)');
  $db->exec('CREATE TABLE IF NOT EXISTS salts (day TEXT PRIMARY KEY, salt BLOB NOT NULL)');
  $db->exec('CREATE TABLE IF NOT EXISTS logins (ts INTEGER NOT NULL, who TEXT NOT NULL, ok INTEGER NOT NULL)');
  return $db;
}

function aha_client_ip(): string {
  $cands = [$_SERVER['HTTP_X_REAL_IP'] ?? '', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0], $_SERVER['REMOTE_ADDR'] ?? ''];
  foreach ($cands as $ip) {
    $ip = trim($ip);
    if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
  }
  return '0.0.0.0';
}

function aha_country(string $ip): string {
  $file = aha_data_dir() . '/geo.sqlite';
  $bin = @inet_pton($ip);
  if (!is_file($file) || $bin === false) return '';
  if (strlen($bin) === 4) $bin = str_repeat("\0", 10) . "\xff\xff" . $bin;
  try {
    $g = new PDO('sqlite:' . $file, null, null, [PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY]);
    $st = $g->prepare('SELECT e, cc FROM r WHERE s <= ? ORDER BY s DESC LIMIT 1');
    $st->bindValue(1, $bin, PDO::PARAM_LOB);
    $st->execute();
    $row = $st->fetch(PDO::FETCH_NUM);
    return ($row && strcmp($row[0], $bin) >= 0) ? $row[1] : '';
  } catch (Throwable $e) { return ''; }
}

/** Anonymous visitor code: changes every day and the day's salt is deleted afterwards. */
function aha_visitor_hash(string $ip, string $ua): string {
  $db = aha_db();
  $day = gmdate('Y-m-d');
  $st = $db->prepare('SELECT salt FROM salts WHERE day = ?');
  $st->execute([$day]);
  $salt = $st->fetchColumn();
  if ($salt === false) {
    $salt = random_bytes(32);
    $db->prepare('INSERT OR IGNORE INTO salts (day, salt) VALUES (?, ?)')->execute([$day, $salt]);
    $db->prepare('DELETE FROM salts WHERE day < ?')->execute([$day]);
    $st->execute([$day]);
    $salt = $st->fetchColumn();
  }
  return substr(hash('sha256', $salt . '|' . $ip . '|' . $ua), 0, 16);
}
