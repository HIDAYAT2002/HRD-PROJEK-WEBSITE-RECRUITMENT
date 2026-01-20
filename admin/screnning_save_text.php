<?php
require_once __DIR__ . "/guard.php";
header('Content-Type: application/json; charset=utf-8');

function json_out($ok, $msg, $extra=[]){
  echo json_encode(array_merge(['ok'=>$ok,'msg'=>$msg], $extra));
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$text = (string)($_POST['text'] ?? '');

if($id <= 0) json_out(false, "ID tidak valid.");
$text = trim($text);
if($text === '' || strlen($text) < 30) json_out(false, "Teks terlalu pendek / kosong.");

$dir = __DIR__ . "/_cv_text";
if(!is_dir($dir)){
  // coba buat kalau belum ada
  @mkdir($dir, 0755, true);
}
if(!is_dir($dir) || !is_writable($dir)){
  json_out(false, "Folder cache tidak bisa ditulis: admin/_cv_text");
}

// batasi biar aman
if(strlen($text) > 500000){
  $text = substr($text, 0, 500000);
}

$file = $dir . "/cv_" . $id . ".txt";
$ok = @file_put_contents($file, $text, LOCK_EX);

if(!$ok){
  json_out(false, "Gagal menyimpan cache teks.");
}

json_out(true, "OK", ['file'=>basename($file), 'len'=>strlen($text)]);
