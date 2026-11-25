<?php
// Private analytics dashboard for aha.yowtech.io. Password set with deploy/set-admin-password.sh.
require dirname(__DIR__) . '/api/_stats.php';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; style-src 'unsafe-inline'; script-src 'unsafe-inline'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'");

session_name('aha_admin');
session_set_cookie_params(['lifetime' => 0, 'path' => '/admin', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
session_start();

$hashFile = aha_data_dir() . '/admin.hash';
$error = '';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

if (($_POST['action'] ?? '') === 'logout' && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
  $_SESSION = []; session_destroy();
  header('Location: ./'); exit;
}

if (empty($_SESSION['ok']) && ($_POST['action'] ?? '') === 'login') {
  $db = aha_db();
  $who = substr(hash('sha256', aha_client_ip()), 0, 16);
  $db->prepare('DELETE FROM logins WHERE ts < ?')->execute([time() - 86400]);
  $st = $db->prepare('SELECT COUNT(*) FROM logins WHERE who = ? AND ok = 0 AND ts > ?');
  $st->execute([$who, time() - 900]);
  if ($st->fetchColumn() >= 5) {
    $error = 'Too many attempts. Try again in 15 minutes.';
  } else {
    $hash = is_file($hashFile) ? trim(file_get_contents($hashFile)) : '';
    $ok = $hash !== '' && password_verify((string)($_POST['password'] ?? ''), $hash);
    $db->prepare('INSERT INTO logins (ts, who, ok) VALUES (?,?,?)')->execute([time(), $who, $ok ? 1 : 0]);
    if ($ok) {
      session_regenerate_id(true);
      $_SESSION['ok'] = 1;
      $_SESSION['csrf'] = bin2hex(random_bytes(16));
      header('Location: ./'); exit;
    }
    usleep(800000);
    $error = 'Wrong password.';
  }
}

function page_start(string $title): void { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow"><title><?= htmlspecialchars($title) ?></title>
<style>
  :root{--bg:oklch(0.982 0.006 165);--ink:oklch(0.25 0.03 210);--muted:oklch(0.45 0.025 200);--line:oklch(0.91 0.01 180);--teal:oklch(0.5 0.1 185);--mint:oklch(0.93 0.045 170);--tang:oklch(0.76 0.16 55)}
  *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:500 15px/1.5 system-ui,-apple-system,sans-serif}
  .wrap{max-width:1100px;margin:0 auto;padding:24px}
  header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
  h1{margin:0;font:800 26px/1.1 system-ui;letter-spacing:-.02em}h2{margin:0 0 12px;font:800 17px system-ui}
  .card{background:#fff;border-radius:20px;padding:20px;box-shadow:0 1px 4px rgba(20,60,60,.08)}
  .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:14px}
  .kpi .n{font:800 32px/1.1 system-ui;letter-spacing:-.02em}.kpi .l{color:var(--muted);font-weight:600;font-size:13px}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:14px;margin-top:14px}
  table{width:100%;border-collapse:collapse}td,th{padding:7px 4px;border-bottom:1px solid var(--line);text-align:left}
  th{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em}td.n,th.n{text-align:right;font-variant-numeric:tabular-nums}
  .bar{height:6px;border-radius:3px;background:var(--mint);margin-top:4px}.bar i{display:block;height:100%;border-radius:3px;background:var(--teal)}
  .tabs{display:flex;gap:6px;flex-wrap:wrap}.tabs a{padding:7px 14px;border-radius:16px;background:#fff;color:var(--ink);text-decoration:none;font-weight:700;font-size:13px;border:1px solid var(--line)}
  .tabs a.on{background:var(--ink);color:#fff;border-color:var(--ink)}
  button,input{font:inherit}input[type=password]{width:100%;padding:12px 14px;border-radius:14px;border:1px solid var(--line);margin:8px 0 12px}
  .btn{padding:11px 18px;border:0;border-radius:16px;background:var(--teal);color:#fff;font-weight:800;cursor:pointer}
  .ghost{background:transparent;color:var(--muted);border:1px solid var(--line)}
  .err{color:#b3261e;font-weight:700}.muted{color:var(--muted);font-size:13px}
  svg text{font:600 10px system-ui;fill:var(--muted)}
  .legend{display:flex;gap:14px;font-size:13px;color:var(--muted);margin-bottom:6px}.legend b{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:5px;vertical-align:-1px}
</style></head><body><div class="wrap">
<?php }

if (empty($_SESSION['ok'])) {
  page_start('aha! admin');
  $setup = is_file($hashFile); ?>
  <div class="card" style="max-width:380px;margin:12vh auto">
    <h1>aha! admin</h1>
    <?php if (!$setup): ?>
      <p class="muted">No password is set yet. Run <code>deploy/set-admin-password.sh</code> from the aha-site folder.</p>
    <?php else: ?>
      <form method="post" autocomplete="off">
        <input type="hidden" name="action" value="login">
        <label class="muted" for="pw">Password</label>
        <input id="pw" type="password" name="password" required autofocus autocomplete="current-password">
        <?php if ($error): ?><p class="err"><?= $e($error) ?></p><?php endif; ?>
        <button class="btn" type="submit">Sign in</button>
      </form>
    <?php endif; ?>
  </div></div></body></html>
  <?php exit;
}

// ---- Dashboard ----
$ranges = ['1' => 'Today', '7' => '7 days', '30' => '30 days', '90' => '90 days', '365' => '12 months'];
$d = array_key_exists($_GET['d'] ?? '', $ranges) ? $_GET['d'] : '30';
$from = gmdate('Y-m-d', time() - ((int)$d - 1) * 86400);
$db = aha_db();
$q = function (string $sql, array $args = []) use ($db) { $st = $db->prepare($sql); $st->execute($args); return $st->fetchAll(PDO::FETCH_ASSOC); };

// Visitors = anonymous daily codes, summed over days (codes reset every day by design).
$tot = $q("SELECT
    (SELECT COUNT(*) FROM (SELECT DISTINCT day, vh FROM hits WHERE kind='view' AND day >= :f)) AS visitors,
    SUM(kind='view') AS views, SUM(kind='appstore') AS clicks, SUM(kind='video') AS videos
  FROM hits WHERE day >= :f", [':f' => $from])[0];
$visitors = (int)$tot['visitors']; $views = (int)$tot['views']; $clicks = (int)$tot['clicks']; $videos = (int)$tot['videos'];

$daily = [];
foreach ($q("SELECT day, COUNT(DISTINCT CASE WHEN kind='view' THEN vh END) AS v, SUM(kind='appstore') AS c FROM hits WHERE day >= ? GROUP BY day", [$from]) as $r) $daily[$r['day']] = $r;
$countries = $q("SELECT COALESCE(NULLIF(cc,''),'??') AS cc,
    COUNT(DISTINCT CASE WHEN kind='view' THEN day||vh END) AS v, SUM(kind='view') AS pv, SUM(kind='appstore') AS c
  FROM hits WHERE day >= ? GROUP BY 1 ORDER BY v DESC, c DESC LIMIT 60", [$from]);
$pages = $q("SELECT path, COUNT(*) AS n FROM hits WHERE kind='view' AND day >= ? GROUP BY path ORDER BY n DESC LIMIT 15", [$from]);
$refs = $q("SELECT ref, COUNT(DISTINCT day||vh) AS n FROM hits WHERE kind='view' AND ref <> '' AND day >= ? GROUP BY ref ORDER BY n DESC LIMIT 15", [$from]);
$devices = $q("SELECT device, COUNT(DISTINCT day||vh) AS n FROM hits WHERE kind='view' AND day >= ? GROUP BY device ORDER BY n DESC", [$from]);

$cname = function (string $cc): string {
  if ($cc === '??') return 'Unknown';
  $n = class_exists('Locale') ? Locale::getDisplayRegion('-' . $cc, 'en') : '';
  return ($n && $n !== $cc) ? $n : $cc;
};
$flag = fn(string $cc) => strlen($cc) === 2 && ctype_alpha($cc)
  ? mb_chr(0x1F1E6 + ord(strtoupper($cc[0])) - 65) . mb_chr(0x1F1E6 + ord(strtoupper($cc[1])) - 65) : '🌐';
$fmt = fn($n) => number_format((int)$n);
$pct = fn($a, $b) => $b ? round($a / $b * 100, 1) . '%' : '–';

page_start('aha! analytics'); ?>
<header>
  <h1>aha! analytics</h1>
  <div class="tabs"><?php foreach ($ranges as $k => $label): ?><a class="<?= $k === $d ? 'on' : '' ?>" href="?d=<?= $k ?>"><?= $label ?></a><?php endforeach; ?></div>
  <form method="post"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>"><button class="btn ghost" type="submit">Sign out</button></form>
</header>

<div class="kpis">
  <div class="card kpi"><div class="n"><?= $fmt($visitors) ?></div><div class="l">Visitors</div></div>
  <div class="card kpi"><div class="n"><?= $fmt($views) ?></div><div class="l">Page views</div></div>
  <div class="card kpi"><div class="n"><?= $fmt($clicks) ?></div><div class="l">App Store clicks</div></div>
  <div class="card kpi"><div class="n"><?= $pct($clicks, $visitors) ?></div><div class="l">Visitors who clicked download</div></div>
  <div class="card kpi"><div class="n"><?= $fmt($videos) ?></div><div class="l">Video plays with sound</div></div>
</div>

<div class="card">
  <h2>Daily visitors and App Store clicks</h2>
  <div class="legend"><span><b style="background:var(--teal)"></b>Visitors</span><span><b style="background:var(--tang)"></b>App Store clicks</span></div>
  <?php
    $days = []; for ($t = strtotime($from . ' UTC'); $t <= time(); $t += 86400) $days[] = gmdate('Y-m-d', $t);
    $max = 1; foreach ($days as $dd) $max = max($max, (int)($daily[$dd]['v'] ?? 0), (int)($daily[$dd]['c'] ?? 0));
    $W = 1000; $H = 180; $n = count($days); $bw = $W / max($n, 1);
  ?>
  <svg viewBox="0 0 <?= $W ?> <?= $H + 18 ?>" width="100%" role="img" aria-label="Daily visitors and clicks">
    <?php foreach ($days as $i => $dd): $v = (int)($daily[$dd]['v'] ?? 0); $c = (int)($daily[$dd]['c'] ?? 0);
      $x = $i * $bw; $hv = $v / $max * $H; $hc = $c / $max * $H; ?>
      <rect x="<?= $x + $bw * .12 ?>" y="<?= $H - $hv ?>" width="<?= $bw * .45 ?>" height="<?= $hv ?>" rx="2" fill="oklch(0.5 0.1 185)"><title><?= $dd ?>: <?= $v ?> visitors, <?= $c ?> clicks</title></rect>
      <rect x="<?= $x + $bw * .57 ?>" y="<?= $H - $hc ?>" width="<?= $bw * .3 ?>" height="<?= $hc ?>" rx="2" fill="oklch(0.76 0.16 55)"><title><?= $dd ?>: <?= $c ?> clicks</title></rect>
      <?php if ($n <= 31 || $i % (int)ceil($n / 12) === 0): ?><text x="<?= $x + $bw / 2 ?>" y="<?= $H + 14 ?>" text-anchor="middle"><?= substr($dd, 5) ?></text><?php endif; ?>
    <?php endforeach; ?>
  </svg>
</div>

<div class="grid">
  <div class="card" style="grid-column:1/-1">
    <h2>Countries</h2>
    <?php if (!$countries): ?><p class="muted">No visits yet in this period.</p><?php else:
      $cmax = max(array_map(fn($r) => (int)$r['v'], $countries)) ?: 1; ?>
    <table><thead><tr><th>Country</th><th class="n">Visitors</th><th class="n">Page views</th><th class="n">App Store clicks</th><th class="n">Click rate</th></tr></thead><tbody>
    <?php foreach ($countries as $r): ?>
      <tr><td><?= $flag($r['cc']) ?> <?= $e($cname($r['cc'])) ?><div class="bar"><i style="width:<?= round($r['v'] / $cmax * 100) ?>%"></i></div></td>
      <td class="n"><?= $fmt($r['v']) ?></td><td class="n"><?= $fmt($r['pv']) ?></td><td class="n"><?= $fmt($r['c']) ?></td><td class="n"><?= $pct($r['c'], $r['v']) ?></td></tr>
    <?php endforeach; ?></tbody></table><?php endif; ?>
  </div>
  <div class="card"><h2>Pages</h2><table><tbody>
    <?php foreach ($pages as $r): ?><tr><td><?= $e($r['path']) ?></td><td class="n"><?= $fmt($r['n']) ?></td></tr><?php endforeach; ?>
    <?php if (!$pages): ?><tr><td class="muted">No data yet.</td></tr><?php endif; ?></tbody></table></div>
  <div class="card"><h2>Where visitors came from</h2><table><tbody>
    <?php foreach ($refs as $r): ?><tr><td><?= $e($r['ref']) ?></td><td class="n"><?= $fmt($r['n']) ?></td></tr><?php endforeach; ?>
    <?php if (!$refs): ?><tr><td class="muted">Direct visits only so far.</td></tr><?php endif; ?></tbody></table></div>
  <div class="card"><h2>Devices</h2><table><tbody>
    <?php foreach ($devices as $r): ?><tr><td><?= $e($r['device']) ?></td><td class="n"><?= $fmt($r['n']) ?></td><td class="n"><?= $pct($r['n'], $visitors) ?></td></tr><?php endforeach; ?>
    <?php if (!$devices): ?><tr><td class="muted">No data yet.</td></tr><?php endif; ?></tbody></table></div>
</div>

<p class="muted" style="margin-top:18px">Times are UTC. "Visitors" counts anonymous daily codes, so one person visiting on 3 days counts 3 times. Your own visits from this browser are not counted. App Store clicks are taps on the download badge, not installs: see App Store Connect → Analytics for installs.
  Country data: <a href="https://db-ip.com">IP Geolocation by DB-IP</a> (CC BY 4.0).</p>
<script>try{localStorage.setItem('aha_notrack','1')}catch(e){}</script>
</div></body></html>
