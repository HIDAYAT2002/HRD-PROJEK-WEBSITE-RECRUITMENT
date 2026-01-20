<?php
require_once __DIR__ . "/guard.php";

header('Content-Type: application/json; charset=utf-8');

function out($ok, $msg){
  echo json_encode(['ok'=>$ok, 'msg'=>$msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$text = isset($_POST['text']) ? (string)$_POST['text'] : '';

$text = trim($text);
if($id <= 0) out(false, 'ID tidak valid');
if(strlen($text) < 30) out(false, 'Teks terlalu pendek / kosong');

$dir = __DIR__ . "/_cv_text";
if(!is_dir($dir)){
  @mkdir($dir, 0755, true);
}
if(!is_dir($dir) || !is_writable($dir)){
  out(false, 'Folder _cv_text tidak bisa ditulis (permission).');
}

$file = $dir . "/cv_" . $id . ".txt";

// limit size biar aman
if(strlen($text) > 250000){
  $text = substr($text, 0, 250000);
}

$ok = @file_put_contents($file, $text, LOCK_EX);
if($ok === false){
  out(false, 'Gagal menyimpan file cache.');
}

out(true, 'OK');
