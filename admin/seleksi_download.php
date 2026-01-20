<?php
require_once __DIR__ . "/guard.php";

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
  http_response_code(403);
  exit("Forbidden");
}

$lowongan_id = (int)($_GET['lowongan_id'] ?? 0);
$pelamar_id  = (int)($_GET['pelamar_id'] ?? 0);
$stage       = trim((string)($_GET['stage'] ?? ''));

if($lowongan_id<=0 || $pelamar_id<=0 || $stage===''){
  http_response_code(400);
  exit("Bad request");
}

$dataDir = __DIR__ . "/_data";
$uploadDir = $dataDir . "/seleksi_uploads";
$uploadFile = $dataDir . "/seleksi_uploads.json";

if(!is_file($uploadFile)){
  http_response_code(404);
  exit("Not found");
}

$uploads = json_decode((string)@file_get_contents($uploadFile), true);
if(!is_array($uploads)) $uploads=[];

$key = $lowongan_id . ":" . $pelamar_id;
$u = $uploads[$key][$stage] ?? null;
if(!is_array($u) || empty($u['file'])){
  http_response_code(404);
  exit("File not found");
}

$fname = basename((string)$u['file']);
$path  = $uploadDir . "/" . $fname;

if(!is_file($path)){
  http_response_code(404);
  exit("File missing");
}

$downloadName = (string)($u['name'] ?? $fname);
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

$mime = "application/octet-stream";
if($ext === "csv") $mime = "text/csv";
if($ext === "xls") $mime = "application/vnd.ms-excel";
if($ext === "xlsx") $mime = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";

header("Content-Type: ".$mime);
header("Content-Length: ".filesize($path));
header('Content-Disposition: attachment; filename="'.str_replace('"','',$downloadName).'"');
header("X-Content-Type-Options: nosniff");

readfile($path);
exit;
