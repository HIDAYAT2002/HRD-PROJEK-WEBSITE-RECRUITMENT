<?php
require_once __DIR__ . "/guard.php";

// ==== config file JSON ====
$readDir  = __DIR__ . "/_data";
$readFile = $readDir . "/pelamar_dibaca.json";

// id aman
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0){
  header("Location: pelamar.php");
  exit;
}

// pastikan folder ada
if(!is_dir($readDir)){
  @mkdir($readDir, 0755, true);
}

// load data lama
$readMap = [];
if(is_file($readFile)){
  $raw = @file_get_contents($readFile);
  $tmp = json_decode((string)$raw, true);
  if(is_array($tmp)) $readMap = $tmp;
}

$key = (string)$id;

// toggle
if(isset($readMap[$key])){
  unset($readMap[$key]); // jadi "belum dibaca"
}else{
  // jadi "telah dibaca"
  if(function_exists('date_default_timezone_set')){
    date_default_timezone_set('Asia/Jakarta');
  }
  $readMap[$key] = [
    'ts' => date('c'),
    // optional: nyimpen siapa yang klik (kalau session ada)
    'by' => $_SESSION['email'] ?? ($_SESSION['user']['email'] ?? 'admin')
  ];
}

// simpan (LOCK_EX biar aman)
@file_put_contents($readFile, json_encode($readMap, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);

// balik ke halaman sebelumnya (biar filter gak ilang)
$back = $_GET['back'] ?? 'pelamar.php';
$back = (string)$back;

// keamanan sederhana: hanya izinkan redirect internal
if(strpos($back, 'http://') === 0 || strpos($back, 'https://') === 0){
  $back = 'pelamar.php';
}
header("Location: " . $back);
exit;
