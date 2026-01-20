<?php
require_once __DIR__ . "/guard.php";
header('X-Content-Type-Options: nosniff');

function fail($code, $msg){
  http_response_code($code);
  header('Content-Type: text/plain; charset=utf-8');
  echo $msg;
  exit;
}

$u = trim((string)($_GET['u'] ?? ''));
if($u === '') fail(400, 'Missing u');
if(strlen($u) > 2000) fail(400, 'URL too long');

$host = $_SERVER['HTTP_HOST'] ?? '';
$pu = parse_url($u);
if(!$pu || empty($pu['host'])) fail(400, 'Invalid URL');

// allow hanya domain sendiri (AMAN)
if(strtolower($pu['host']) !== strtolower($host)){
  fail(403, 'Forbidden host');
}

// allow path hanya /career/
$path = $pu['path'] ?? '';
if(stripos($path, '/career/') !== 0){
  fail(403, 'Forbidden path');
}

function fetch_url($url){
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_HEADER => true,

    // trik biar server gak ngasih 204 / aneh: pakai range
    CURLOPT_HTTPHEADER => [
      'Range: bytes=0-',
      'Accept: application/pdf,*/*'
    ],
  ]);

  $resp = curl_exec($ch);
  if($resp === false){
    $err = curl_error($ch);
    curl_close($ch);
    return [0, 0, '', '', "Fetch failed: $err"];
  }

  $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  $hdrSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $ctype = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  curl_close($ch);

  $rawHeader = substr($resp, 0, $hdrSize);
  $body      = substr($resp, $hdrSize);
  return [$code, $hdrSize, $ctype, $body, $rawHeader];
}

// Try 1
[$code, $hdrSize, $ctype, $body, $hdrRaw] = fetch_url($u);

// Kalau 204 / kosong, retry pakai cache-bust
if($code === 204 || $body === ''){
  $sep = (strpos($u,'?') !== false) ? '&' : '?';
  $u2 = $u . $sep . '_cb=' . time();
  [$code, $hdrSize, $ctype, $body, $hdrRaw] = fetch_url($u2);
}

// kalau tetap jelek
if($code === 204 || $body === ''){
  fail(502, "Upstream returned $code / empty body");
}
if($code < 200 || $code >= 300){
  fail(502, "Upstream status $code");
}

// filename aman
$filename = basename($path);
if($filename === '' || $filename === '/' || $filename === '.') $filename = 'cv.pdf';

// paksa inline PDF
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="'.$filename.'"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// kalau upstream range (206), tetap OK
echo $body;
exit;
