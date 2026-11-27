<?php
// App Store Connect sales for the /admin dashboard.
// Credentials live OUTSIDE the web root, set with deploy/set-asc-key.sh:
//   ~/aha-analytics/asc/AuthKey.p8   App Store Connect API key (Sales role), chmod 600
//   ~/aha-analytics/asc/config.json  {"issuer": "...", "key_id": "...", "vendor": "...", "app_id": "..."}
// Apple's daily Sales reports are dated in Pacific time and appear about a day later.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === '_asc.php') { http_response_code(404); exit; }

const ASC_FIRST_DAY = '2026-11-20'; // a few days before release, so nothing is missed

function asc_dir(): string { return aha_data_dir() . '/asc'; }

function asc_config(): ?array {
  $f = asc_dir() . '/config.json';
  $k = asc_dir() . '/AuthKey.p8';
  if (!is_file($f) || !is_file($k)) return null;
  $c = json_decode((string)file_get_contents($f), true);
  if (!is_array($c) || empty($c['issuer']) || empty($c['key_id']) || empty($c['vendor'])) return null;
  $c['key_file'] = $k;
  return $c;
}

function asc_tables(PDO $db): void {
  $db->exec('CREATE TABLE IF NOT EXISTS asc_sales (
      day TEXT NOT NULL, ptype TEXT NOT NULL, cc TEXT NOT NULL, units INTEGER NOT NULL,
      proceeds REAL NOT NULL, pcur TEXT NOT NULL, price REAL NOT NULL, ccur TEXT NOT NULL, device TEXT)');
  $db->exec('CREATE INDEX IF NOT EXISTS asc_sales_day ON asc_sales(day)');
  $db->exec('CREATE TABLE IF NOT EXISTS asc_days (day TEXT PRIMARY KEY, status TEXT NOT NULL, checked INTEGER NOT NULL)');
  $db->exec('CREATE TABLE IF NOT EXISTS asc_meta (k TEXT PRIMARY KEY, v TEXT)');
}

function b64url(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }

/** ES256 JWT for the App Store Connect API (valid 15 minutes). */
function asc_jwt(array $c): string {
  $head = b64url(json_encode(['alg' => 'ES256', 'kid' => $c['key_id'], 'typ' => 'JWT']));
  $body = b64url(json_encode(['iss' => $c['issuer'], 'iat' => time() - 30, 'exp' => time() + 900, 'aud' => 'appstoreconnect-v1']));
  $key = openssl_pkey_get_private((string)file_get_contents($c['key_file']));
  if (!$key || !openssl_sign("$head.$body", $der, $key, OPENSSL_ALGO_SHA256)) throw new RuntimeException('Could not sign with the API key.');
  // openssl returns a DER sequence of two integers; JWT wants raw r||s, 32 bytes each.
  $pos = 2 + (ord($der[1]) & 0x80 ? (ord($der[1]) & 0x7f) : 0);
  $raw = '';
  for ($i = 0; $i < 2; $i++) {
    $len = ord($der[$pos + 1]);
    $int = substr($der, $pos + 2, $len);
    $raw .= str_pad(ltrim($int, "\0"), 32, "\0", STR_PAD_LEFT);
    $pos += 2 + $len;
  }
  return "$head.$body." . b64url($raw);
}

/** Returns [http status, body]. */
function asc_get(string $url, string $jwt): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer $jwt", 'Accept: application/a-gzip, application/json'],
  ]);
  $body = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  $err = curl_error($ch);
  curl_close($ch);
  if ($body === false) throw new RuntimeException('App Store Connect did not answer: ' . $err);
  return [$code, (string)$body];
}

function asc_pt_today(): string { return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->format('Y-m-d'); }

/** Downloads any daily reports not stored yet. Returns an error message or ''. */
function asc_sync(int $budget = 10): string {
  $c = asc_config();
  if (!$c) return '';
  $db = aha_db();
  asc_tables($db);
  $done = [];
  foreach ($db->query('SELECT day, status, checked FROM asc_days') as $r) $done[$r['day']] = $r;
  $today = asc_pt_today();
  $todo = [];
  for ($t = strtotime(ASC_FIRST_DAY . ' UTC'); gmdate('Y-m-d', $t) < $today; $t += 86400) {
    $day = gmdate('Y-m-d', $t);
    $r = $done[$day] ?? null;
    if ($r && $r['status'] === 'done') continue;
    if ($r && time() - (int)$r['checked'] < 3600) continue; // not published yet: retry hourly
    $todo[] = $day;
  }
  if (!$todo) return '';
  try { $jwt = asc_jwt($c); } catch (Throwable $e) { return $e->getMessage(); }

  $appId = (string)($c['app_id'] ?? '');
  $mark = $db->prepare('INSERT OR REPLACE INTO asc_days (day, status, checked) VALUES (?,?,?)');
  foreach (array_slice(array_reverse($todo), 0, $budget) as $day) {
    $url = 'https://api.appstoreconnect.apple.com/v1/salesReports?' . http_build_query([
      'filter[frequency]' => 'DAILY', 'filter[reportType]' => 'SALES', 'filter[reportSubType]' => 'SUMMARY',
      'filter[vendorNumber]' => $c['vendor'], 'filter[reportDate]' => $day, 'filter[version]' => '1_1',
    ]);
    try { [$code, $body] = asc_get($url, $jwt); } catch (Throwable $e) { return $e->getMessage(); }
    if ($code === 401 || $code === 403) return 'App Store Connect refused the API key (HTTP ' . $code . '). Check that the key has the Sales role and that the Issuer ID and Key ID are right.';
    if ($code === 404) {
      // No sales that day, or the report is not out yet. Reports older than 3 days are final.
      $final = (strtotime($today . ' UTC') - strtotime($day . ' UTC')) / 86400 > 3;
      $mark->execute([$day, $final ? 'done' : 'pending', time()]);
      continue;
    }
    if ($code !== 200) {
      $msg = json_decode($body, true)['errors'][0]['detail'] ?? ('HTTP ' . $code);
      return 'App Store Connect error for ' . $day . ': ' . $msg;
    }
    $tsv = @gzdecode($body);
    if ($tsv === false) $tsv = $body;
    $lines = preg_split('/\r?\n/', trim($tsv));
    $cols = array_flip(explode("\t", array_shift($lines)));
    $col = fn(array $row, string $name) => $row[$cols[$name] ?? -1] ?? '';
    $db->beginTransaction();
    $db->prepare('DELETE FROM asc_sales WHERE day = ?')->execute([$day]);
    $ins = $db->prepare('INSERT INTO asc_sales (day, ptype, cc, units, proceeds, pcur, price, ccur, device) VALUES (?,?,?,?,?,?,?,?,?)');
    foreach ($lines as $line) {
      if ($line === '') continue;
      $row = explode("\t", $line);
      if ($appId !== '' && $col($row, 'Apple Identifier') !== $appId) continue;
      $ins->execute([$day, $col($row, 'Product Type Identifier'), $col($row, 'Country Code'), (int)$col($row, 'Units'),
        (float)$col($row, 'Developer Proceeds'), $col($row, 'Currency of Proceeds'), (float)$col($row, 'Customer Price'),
        $col($row, 'Customer Currency'), $col($row, 'Device')]);
    }
    $mark->execute([$day, 'done', time()]);
    $db->commit();
  }
  $db->prepare('INSERT OR REPLACE INTO asc_meta (k, v) VALUES (?, ?)')->execute(['synced', (string)time()]);
  return '';
}

/** Product types: 1* = first download (paid), 3* = re-download, 7* = update. */
function asc_kind(string $ptype): string {
  $p = $ptype[0] ?? '';
  return $p === '1' ? 'sale' : ($p === '3' ? 'redownload' : ($p === '7' ? 'update' : 'other'));
}
