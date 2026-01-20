<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';
header('Content-Type: application/json; charset=utf-8');

function json_out($arr){
  echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// identitas user login (jangan bikin error kalau session beda-beda)
$me = [
  'id'   => $_SESSION['id'] ?? ($_SESSION['user_id'] ?? ''),
  'name' => $_SESSION['nama'] ?? $_SESSION['username'] ?? $_SESSION['email'] ?? 'User',
  'role' => $_SESSION['role'] ?? $_SESSION['level'] ?? 'HRD/Manager',
];

$action = $_REQUEST['action'] ?? 'list';

// lokasi file chat
$dataDir = __DIR__ . "/_data";
$file    = $dataDir . "/chat_messages.json";

// pastikan folder ada
if(!is_dir($dataDir)){
  @mkdir($dataDir, 0755, true);
}
if(!file_exists($file)){
  @file_put_contents($file, json_encode(['messages'=>[]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}

// read file json dengan aman
function read_chat_file($file){
  $raw = @file_get_contents($file);
  $data = json_decode($raw ?: '', true);
  if(!is_array($data)) $data = ['messages'=>[]];
  if(!isset($data['messages']) || !is_array($data['messages'])) $data['messages'] = [];
  return $data;
}

// write file json dengan lock
function write_chat_file($file, $data){
  $fp = @fopen($file, 'c+');
  if(!$fp) return false;
  if(!flock($fp, LOCK_EX)){
    fclose($fp);
    return false;
  }
  ftruncate($fp, 0);
  rewind($fp);
  fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
  fflush($fp);
  flock($fp, LOCK_UN);
  fclose($fp);
  return true;
}

if($action === 'list'){
  $after = (int)($_GET['after'] ?? 0);
  $data = read_chat_file($file);
  $msgs = $data['messages'];

  // batasi max (biar ringan)
  $maxKeep = 500;
  if(count($msgs) > $maxKeep){
    $msgs = array_slice($msgs, -$maxKeep);
  }

  // filter after ts (kalau mau)
  // tapi kita tetap kirim semua supaya UI stabil
  json_out([
    'ok' => true,
    'me' => $me,
    'messages' => $msgs,
  ]);
}

if($action === 'send'){
  $text = trim((string)($_POST['text'] ?? ''));
  if($text === ''){
    json_out(['ok'=>false,'error'=>'Pesan kosong.']);
  }
  if(mb_strlen($text) > 4000){
    json_out(['ok'=>false,'error'=>'Pesan kepanjangan (max 4000 karakter).']);
  }

  $ref = null;
  if(isset($_POST['ref']) && $_POST['ref'] !== ''){
    $tmp = json_decode((string)$_POST['ref'], true);
    if(is_array($tmp)){
      // whitelist keys biar aman
      $ref = [
        'pelamar_id'      => $tmp['pelamar_id'] ?? null,
        'pelamar_nama'    => $tmp['pelamar_nama'] ?? null,
        'lowongan_id'     => $tmp['lowongan_id'] ?? null,
        'lowongan_posisi' => $tmp['lowongan_posisi'] ?? null,
      ];
    }
  }

  $data = read_chat_file($file);

  $msg = [
    'id'   => bin2hex(random_bytes(8)),
    'ts'   => time(),
    'user' => [
      'id'   => $me['id'],
      'name' => $me['name'],
      'role' => $me['role'],
    ],
    'text' => $text,
    'ref'  => $ref,
  ];

  $data['messages'][] = $msg;

  // keep last 500
  if(count($data['messages']) > 500){
    $data['messages'] = array_slice($data['messages'], -500);
  }

  if(!write_chat_file($file, $data)){
    json_out(['ok'=>false,'error'=>'Gagal menyimpan chat (file lock/permission).']);
  }

  json_out(['ok'=>true]);
}

if($action === 'pelamar'){
  $lowId = (int)($_GET['lowongan_id'] ?? 0);
  $q = trim((string)($_GET['q'] ?? ''));

  if($lowId <= 0){
    json_out(['ok'=>true,'items'=>[]]);
  }

  // cari pelamar berdasarkan lowongan (tanpa ubah DB)
  $sql = "SELECT id, nama, kota FROM pelamar WHERE lowongan_id = ? ";
  $params = [];
  $types = "i";
  $params[] = $lowId;

  if($q !== ''){
    $sql .= " AND nama LIKE ? ";
    $types .= "s";
    $params[] = "%".$q."%";
  }
  $sql .= " ORDER BY id DESC LIMIT 200";

  $stmt = mysqli_prepare($conn, $sql);
  if(!$stmt){
    json_out(['ok'=>false,'error'=>'Query prepare gagal.']);
  }
  mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  $items = [];
  if($res){
    while($r = mysqli_fetch_assoc($res)){
      $items[] = [
        'id'   => (int)($r['id'] ?? 0),
        'nama' => (string)($r['nama'] ?? ''),
        'kota' => (string)($r['kota'] ?? ''),
      ];
    }
  }
  mysqli_stmt_close($stmt);

  json_out(['ok'=>true,'items'=>$items]);
}

if($action === 'detail'){
  $lowId = (int)($_GET['lowongan_id'] ?? 0);
  $pelId = (int)($_GET['pelamar_id'] ?? 0);

  if($lowId <= 0 || $pelId <= 0){
    json_out(['ok'=>false,'error'=>'ID tidak valid.']);
  }

  // lowongan
  $posisi = '';
  $stmt = mysqli_prepare($conn, "SELECT posisi FROM lowongan WHERE id = ? LIMIT 1");
  if($stmt){
    mysqli_stmt_bind_param($stmt, "i", $lowId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    $posisi = $row['posisi'] ?? '';
    mysqli_stmt_close($stmt);
  }

  // pelamar (pastikan pelamar sesuai lowongan)
  $nama = '';
  $stmt2 = mysqli_prepare($conn, "SELECT nama FROM pelamar WHERE id = ? AND lowongan_id = ? LIMIT 1");
  if($stmt2){
    mysqli_stmt_bind_param($stmt2, "ii", $pelId, $lowId);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    $row2 = $res2 ? mysqli_fetch_assoc($res2) : null;
    $nama = $row2['nama'] ?? '';
    mysqli_stmt_close($stmt2);
  }

  if($posisi === '' || $nama === ''){
    json_out(['ok'=>false,'error'=>'Data lowongan/pelamar tidak ditemukan atau tidak cocok.']);
  }

  $ref = [
    'pelamar_id'      => $pelId,
    'pelamar_nama'    => $nama,
    'lowongan_id'     => $lowId,
    'lowongan_posisi' => $posisi,
  ];

  json_out(['ok'=>true,'ref'=>$ref]);
}

// default
json_out(['ok'=>false,'error'=>'Action tidak dikenal.']);
