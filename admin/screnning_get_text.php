<?php
require_once __DIR__ . "/guard.php";

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if($id <= 0){
  http_response_code(400);
  echo json_encode(['ok'=>false,'text'=>'','msg'=>'Invalid id']);
  exit;
}

$file = __DIR__ . "/_cv_text/cv_" . $id . ".txt";
if(!is_file($file)){
  echo json_encode(['ok'=>true,'text'=>'']);
  exit;
}

$text = file_get_contents($file);
if($text === false) $text = '';

echo json_encode(['ok'=>true,'text'=>$text]);
