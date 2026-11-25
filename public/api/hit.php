<?php
// Cookie-free counter: page views, App Store clicks and video plays. Stores no IP address.
require __DIR__ . '/_stats.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }

$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 400);
if ($ua === '' || preg_match('/bot|crawl|spider|slurp|headless|lighthouse|preview|facebookexternalhit|fetch|curl|wget|python|httpclient|monitor|scan/i', $ua)) {
  http_response_code(204); exit;
}
$in = json_decode(substr(file_get_contents('php://input') ?: '', 0, 2048), true);
if (!is_array($in)) { http_response_code(400); exit; }

$kind = in_array($in['k'] ?? '', ['view', 'appstore', 'video'], true) ? $in['k'] : null;
if (!$kind) { http_response_code(400); exit; }
$path = '/' . ltrim(substr(strtok((string)($in['p'] ?? '/'), '?#'), 0, 200), '/');
$ref = '';
$rh = parse_url((string)($in['r'] ?? ''), PHP_URL_HOST);
if ($rh && strcasecmp($rh, $_SERVER['HTTP_HOST'] ?? '') !== 0) $ref = strtolower(preg_replace('/^www\./i', '', $rh));
$device = preg_match('/iPad|Tablet/i', $ua) ? 'Tablet' : (preg_match('/Mobi|iPhone|Android/i', $ua) ? 'Mobile' : 'Desktop');

try {
  $ip = aha_client_ip();
  aha_db()->prepare('INSERT INTO hits (day, ts, kind, path, ref, cc, device, vh) VALUES (?,?,?,?,?,?,?,?)')
    ->execute([gmdate('Y-m-d'), time(), $kind, $path, $ref, aha_country($ip), $device, aha_visitor_hash($ip, $ua)]);
} catch (Throwable $e) { /* never break the page */ }
http_response_code(204);
