<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
  echo json_encode(["ok"=>false,"error"=>"Unauthorized"]);
  exit;
}

function jout($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function now_date(){ return date('Y-m-d'); }
function safe_int($v){ return (int)($v ?? 0); }

function human_date($ymd){
  if(!$ymd) return '';
  $t = strtotime($ymd);
  if(!$t) return $ymd;
  $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  $m = (int)date('n',$t);
  return date('d',$t).' '.($bulan[$m]??date('M',$t)).' '.date('Y',$t);
}

function ensure_dir($dir){
  if(!is_dir($dir)){
    @mkdir($dir, 0775, true);
  }
}
function read_json($file){
  if(!is_file($file)) return [];
  $raw = @file_get_contents($file);
  $j = json_decode((string)$raw, true);
  return is_array($j) ? $j : [];
}
function write_json($file, $data){
  $tmp = $file . ".tmp";
  $ok = @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
  if($ok === false) return false;
  @rename($tmp, $file);
  return true;
}

function db_has_col($conn, $table, $col){
  $table = preg_replace('/[^a-zA-Z0-9_]/','', $table);
  $col   = preg_replace('/[^a-zA-Z0-9_]/','', $col);
  $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$col'");
  return ($q && mysqli_num_rows($q) > 0);
}
function pick_first_col($conn, $table, $candidates){
  foreach($candidates as $c){
    if(db_has_col($conn, $table, $c)) return $c;
  }
  return null;
}

function stage_labels($stage){
  if($stage === "Onboarding"){
    return ["ok"=>"Diterima", "no"=>"Tidak Diterima"];
  }
  return ["ok"=>"Lolos", "no"=>"Tidak Lolos"];
}
function stage_next($stage){
  $order = ["Initiate Call","Interview HRD & User","Psikotest","MCU","Onboarding"];
  $i = array_search($stage, $order, true);
  if($i === false) return null;
  return $order[$i+1] ?? null;
}

function board_get_item_index(&$boardStageArr, $pid){
  foreach((array)$boardStageArr as $idx => $it){
    if((int)($it['id'] ?? 0) === (int)$pid) return $idx;
  }
  return -1;
}
function ensure_board_defaults(&$boardLow){
  $order = ["Initiate Call","Interview HRD & User","Psikotest","MCU","Onboarding"];
  foreach($order as $st){
    if(!isset($boardLow[$st]) || !is_array($boardLow[$st])) $boardLow[$st] = [];
  }
}

/* =========================
   PATHS
========================= */
$DATA_DIR = __DIR__ . "/_data";
ensure_dir($DATA_DIR);

$BOARD_FILE   = $DATA_DIR . "/seleksi_board.json";
$CAT_FILE     = $DATA_DIR . "/seleksi_catatan.json";
$UPLOAD_FILE  = $DATA_DIR . "/seleksi_uploads.json";
$FAVSTAT_FILE = $DATA_DIR . "/seleksi_favorit_status.json";
$ZOOM_FILE    = $DATA_DIR . "/seleksi_zoom.json";

// Optional (ADVANCED): auto-create meeting Zoom (join_url valid)
// Buat file: ../config/zoom_api.php berisi array config:
// return [
//   'enabled' => true,
//   'account_id' => '...',
//   'client_id' => '...',
//   'client_secret' => '...',
//   'user_id' => 'me' // atau email user zoom
// ];
// Jika file ini tidak ada / disabled, sistem tetap akan "generate" link draft unik per pelamar
// (buat dibedain & bisa lu edit/replace dengan link Zoom beneran kapan aja).

$UPLOAD_DIR   = $DATA_DIR . "/uploads";
ensure_dir($UPLOAD_DIR);

$STAGES = ["Initiate Call","Interview HRD & User","Psikotest","MCU","Onboarding"];

function zoom_read_all(){
  global $ZOOM_FILE;
  return read_json($ZOOM_FILE);
}
function zoom_write_all($data){
  global $ZOOM_FILE;
  return write_json($ZOOM_FILE, $data);
}
function zoom_get_one($lowongan_id, $pelamar_id){
  $all = zoom_read_all();
  $lk = (string)(int)$lowongan_id;
  $pk = (string)(int)$pelamar_id;
  $z = $all[$lk][$pk] ?? null;
  return is_array($z) ? $z : null;
}
function zoom_set_one($lowongan_id, $pelamar_id, $zoom){
  $all = zoom_read_all();
  $lk = (string)(int)$lowongan_id;
  $pk = (string)(int)$pelamar_id;
  if(!isset($all[$lk]) || !is_array($all[$lk])) $all[$lk] = [];
  $all[$lk][$pk] = $zoom;
  return zoom_write_all($all);
}

function rand_digits($n){
  $s='';
  for($i=0;$i<$n;$i++) $s .= (string)random_int(0,9);
  return $s;
}
function rand_urlsafe($n){
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
  $out='';
  $max = strlen($chars)-1;
  for($i=0;$i<$n;$i++) $out .= $chars[random_int(0,$max)];
  return $out;
}

function zoom_config(){
  $cfgFile = __DIR__ . '/../config/zoom_api.php';
  if(!is_file($cfgFile)) return ['enabled'=>false];
  $cfg = include $cfgFile;
  if(!is_array($cfg)) return ['enabled'=>false];
  $cfg['enabled'] = (bool)($cfg['enabled'] ?? false);
  return $cfg;
}

function zoom_get_access_token($cfg){
  // Server-to-Server OAuth (account_credentials)
  $account_id = $cfg['account_id'] ?? '';
  $client_id = $cfg['client_id'] ?? '';
  $client_secret = $cfg['client_secret'] ?? '';
  if($account_id==='' || $client_id==='' || $client_secret==='') return null;

  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id='.rawurlencode($account_id),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_USERPWD => $client_id.':'.$client_secret,
    CURLOPT_TIMEOUT => 20,
  ]);
  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if($resp===false || $code<200 || $code>=300) return null;
  $j = json_decode($resp, true);
  $tok = $j['access_token'] ?? null;
  return $tok ? (string)$tok : null;
}

function zoom_create_meeting($access_token, $user_id, $topic){
  // Type 1 = instant meeting (langsung jadi, join_url valid)
  $payload = [
    'topic' => $topic,
    'type' => 1,
    'settings' => [
      'join_before_host' => true,
      'waiting_room' => false,
      'approval_type' => 2,
    ]
  ];
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.zoom.us/v2/users/'.rawurlencode($user_id).'/meetings',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer '.$access_token,
      'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 20,
  ]);
  $resp = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if($resp===false || $code<200 || $code>=300) return null;
  $j = json_decode($resp, true);
  if(!is_array($j)) return null;
  return $j;
}

function normalize_upload_path_abs($rel){
  if(!$rel) return '';
  $rel = str_replace(['..','\\'], ['','/'], (string)$rel);
  if(strpos($rel, '_data/') === 0){
    return __DIR__ . "/" . $rel;
  }
  if(strpos($rel, 'uploads/') === 0){
    return __DIR__ . "/_data/" . $rel;
  }
  return __DIR__ . "/_data/uploads/" . basename($rel);
}
function unlink_if_exists($abs){
  if($abs && is_file($abs)){
    @unlink($abs);
  }
}

function build_base_url(){
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
  return $scheme.'://'.$host.$dir;
}
function make_abs_url($base, $rel){
  $rel = (string)$rel;
  if($rel === '') return '';
  if(preg_match('~^https?://~i', $rel)) return $rel;
  $rel = ltrim($rel, '/');
  return rtrim($base,'/').'/'.$rel;
}
function stage_cell_text($aksi){
  if(!is_array($aksi)) return '';
  $st = trim((string)($aksi['status'] ?? ''));
  $dt = trim((string)($aksi['date_human'] ?? ($aksi['date'] ?? '')));
  if($st && $dt) return $st.' '.$dt;
  return $st ?: $dt;
}

/* =========================
   ✅ Helper meta untuk chat
========================= */
function get_lowongan_meta($conn, $lowongan_id){
  $KOTA_COL = pick_first_col($conn,'lowongan',['kota','lokasi','lokasi_posisi']);
  $lokSel = $KOTA_COL ? "`$KOTA_COL` AS kota" : "'' AS kota";
  $q = mysqli_query($conn, "SELECT id, posisi, $lokSel FROM lowongan WHERE id=".(int)$lowongan_id." LIMIT 1");
  $r = $q ? mysqli_fetch_assoc($q) : null;
  if(!$r) return ["posisi"=>"","kota"=>""];
  return [
    "posisi" => (string)($r['posisi'] ?? ''),
    "kota"   => trim((string)($r['kota'] ?? '')),
  ];
}
function get_pelamar_meta($conn, $pelamar_id, $lowongan_id){
  $TEL_COL = pick_first_col($conn,'pelamar',['telepon','no_hp','hp','phone']);
  $telSel = $TEL_COL ? "p.`$TEL_COL` AS hp" : "'' AS hp";
  $q = mysqli_query($conn, "
    SELECT p.id, p.nama, p.email, $telSel
    FROM pelamar p
    WHERE p.id=".(int)$pelamar_id." AND p.lowongan_id=".(int)$lowongan_id."
    LIMIT 1
  ");
  $r = $q ? mysqli_fetch_assoc($q) : null;
  if(!$r) return ["nama"=>"","email"=>"","hp"=>""];
  return [
    "nama"  => (string)($r['nama'] ?? ''),
    "email" => (string)($r['email'] ?? ''),
    "hp"    => trim((string)($r['hp'] ?? '')),
  ];
}

/* =========================
   ✅ TEMPLATE CHAT (FINAL sesuai request)
========================= */
function safev($v, $fallback){
  $v = trim((string)$v);
  return $v !== '' ? $v : $fallback;
}

function chat_stage_name($stage){
  if($stage === 'Favorit') return 'SELEKSI ADMINISTRASI';
  if($stage === 'MCU') return 'MEDICAL CHECK UP (MCU)';
  if($stage === 'Interview HRD & User') return 'INTERVIEW HRD & USER';
  if($stage === 'Initiate Call') return 'INITIATE CALL';
  if($stage === 'Psikotest') return 'PSIKOTEST';
  if($stage === 'Onboarding') return 'ONBOARDING';
  return strtoupper((string)$stage);
}

function chat_next_stage($stage){
  if(in_array($stage, ['Favorit','Initiate Call','Interview HRD & User','Psikotest'], true)){
    return "INITIATE CALL (panggilan awal dari tim HR).";
  }
  if($stage === 'MCU'){
    return "Onboarding / Orientasi dan PKWT (Tandatangan Kontrak).";
  }
  return "";
}

function build_lolos_message($nama,$posisi,$lokasi,$stage){
  $nama   = safev($nama,'[nama]');
  $posisi = safev($posisi,'[posisi]');
  $lokasi = safev($lokasi,'[lokasi posisi]');

  if($stage === 'Onboarding'){
    return
"Yth. Bapak/Ibu {$nama}

Terima kasih telah mengikuti seluruh rangkaian proses rekrutmen
PT Wiraswasta Gemilang Indonesia
untuk posisi {$posisi} ({$lokasi}).

Berdasarkan hasil akhir seleksi, kami informasikan bahwa
Anda DINYATAKAN DITERIMA dan resmi bergabung bersama
PT Wiraswasta Gemilang Indonesia.

Tim HR akan menghubungi Anda kembali terkait jadwal onboarding/orientasi,
kelengkapan administrasi, serta informasi teknis lainnya.

Kami mengucapkan selamat bergabung dan menantikan kontribusi terbaik Anda.

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia";
  }

  $stageName = chat_stage_name($stage);
  $nextStage = chat_next_stage($stage);

  $nextBlock = $nextStage !== "" ? (
"Tahap selanjutnya adalah:
{$nextStage}

") : "";

  return
"Yth. Bapak/Ibu {$nama}

Terima kasih telah mengikuti proses rekrutmen
PT Wiraswasta Gemilang Indonesia
untuk posisi {$posisi} ({$lokasi}).

Berdasarkan hasil evaluasi, kami informasikan bahwa
Anda DINYATAKAN LOLOS pada tahap
{$stageName}.

{$nextBlock}Mohon menunggu informasi jadwal selanjutnya dari tim kami,
atau silakan membalas pesan ini untuk konfirmasi ketersediaan Anda.

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia";
}

function build_tidak_lolos_message($nama,$posisi,$lokasi,$stage){
  $nama   = safev($nama,'[nama]');
  $posisi = safev($posisi,'[posisi]');
  $lokasi = safev($lokasi,'[lokasi posisi]');
  $stageName = chat_stage_name($stage);

  $statusLine = ($stage === 'Onboarding')
    ? "Anda DINYATAKAN TIDAK DITERIMA pada tahap"
    : "Anda DINYATAKAN BELUM LOLOS pada tahap";

  return
"Yth. Bapak/Ibu {$nama}

Terima kasih telah mengikuti proses rekrutmen
PT Wiraswasta Gemilang Indonesia
untuk posisi {$posisi} ({$lokasi}).

Berdasarkan hasil evaluasi, kami informasikan bahwa
{$statusLine}
{$stageName}.

Kami mengucapkan terima kasih atas waktu dan partisipasi Anda.
Semoga sukses dan yang terbaik untuk langkah karier Anda ke depan.

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia";
}

/* =========================
   ✅ Upload helpers (BARU)
========================= */
function sanitize_stage_key($stage){
  return preg_replace('/[^a-zA-Z0-9 _&-]/', '', (string)$stage);
}
function safe_basename($name){
  $name = (string)$name;
  $name = str_replace(["\0", "\r", "\n"], '', $name);
  $name = preg_replace('/[^\w\-. ]+/u', '_', $name);
  $name = trim($name);
  if($name === '') $name = 'file';
  return $name;
}
function upload_store_file($UPLOAD_DIR, $lowongan_id, $pelamar_id, $stage, $fileField){
  if(empty($_FILES[$fileField]) || !is_uploaded_file($_FILES[$fileField]['tmp_name'])){
    return ["ok"=>false,"error"=>"File tidak ada"];
  }

  $origName = (string)($_FILES[$fileField]['name'] ?? 'file');
  $tmp      = (string)($_FILES[$fileField]['tmp_name'] ?? '');
  $size     = (int)($_FILES[$fileField]['size'] ?? 0);

  // batas ukuran (biar aman). Kamu bisa naikkan.
  $MAX_MB = 25;
  if($size > ($MAX_MB * 1024 * 1024)){
    return ["ok"=>false,"error"=>"Ukuran file terlalu besar (maks {$MAX_MB}MB)"];
  }

  $origNameSafe = safe_basename($origName);
  $ext = strtolower(pathinfo($origNameSafe, PATHINFO_EXTENSION));
  if($ext === '') $ext = 'bin';

  $stageKey = preg_replace('/[^a-zA-Z0-9]/','', sanitize_stage_key($stage));
  if($stageKey === '') $stageKey = 'Stage';

  $fn = "L{$lowongan_id}_P{$pelamar_id}_{$stageKey}_".date('YmdHis')."_".bin2hex(random_bytes(3)).".".$ext;
  $destAbs = rtrim($UPLOAD_DIR,'/')."/".$fn;

  if(!@move_uploaded_file($tmp, $destAbs)){
    return ["ok"=>false,"error"=>"Gagal simpan file"];
  }

  $rel = "_data/uploads/".$fn;
  return [
    "ok"=>true,
    "fileRel"=>$rel,
    "name"=>$origNameSafe,
    "ext"=>$ext,
    "size"=>$size
  ];
}

/* =========================
   Action Router
========================= */
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

/* =========================================================
   ACTION: list_lowongan
========================================================= */
if($action === 'list_lowongan'){
  $KOTA_COL = pick_first_col($conn,'lowongan',['kota','lokasi','lokasi_posisi']);
  $lokSel = $KOTA_COL ? "`$KOTA_COL` AS kota" : "'' AS kota";

  $q = mysqli_query($conn, "SELECT id, posisi, $lokSel FROM lowongan ORDER BY id DESC");
  $items = [];
  if($q){
    while($r = mysqli_fetch_assoc($q)){
      $items[] = [
        "id" => (int)$r['id'],
        "posisi" => (string)($r['posisi'] ?? ''),
        "kota" => trim((string)($r['kota'] ?? '')),
      ];
    }
  }
  jout(["ok"=>true,"items"=>$items]);
}

/* =========================================================
   ✅ ACTION: chat_meta
========================================================= */
if($action === 'chat_meta'){
  $lowongan_id = safe_int($_GET['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_GET['pelamar_id'] ?? 0);
  $stage       = (string)($_GET['stage'] ?? '');
  $decision    = (string)($_GET['decision'] ?? '');

  if($lowongan_id<=0 || $pelamar_id<=0) jout(["ok"=>false,"error"=>"Param invalid"]);

  $valid = array_merge(["Favorit"], $STAGES);
  if($stage !== '' && !in_array($stage, $valid, true)) jout(["ok"=>false,"error"=>"Stage invalid"]);

  $low = get_lowongan_meta($conn, $lowongan_id);
  $p   = get_pelamar_meta($conn, $pelamar_id, $lowongan_id);

  $posisi = trim($low['posisi'] ?? '');
  $lokasi = trim($low['kota'] ?? '');
  $nama   = trim($p['nama'] ?? '');

  $judul = "Informasi Hasil Seleksi";
  $isi   = "";

  $isPass = in_array($decision, ['lolos','terima'], true);
  $isFail = in_array($decision, ['tidak_lolos','ditolak'], true);

  if($isPass){
    $isi = build_lolos_message($nama, $posisi, $lokasi, $stage);
  } else if($isFail){
    $isi = build_tidak_lolos_message($nama, $posisi, $lokasi, $stage);
  } else {
    $isi =
"Yth. Bapak/Ibu ".safev($nama,'[nama]')."

Terima kasih telah mengikuti proses rekrutmen
PT Wiraswasta Gemilang Indonesia
untuk posisi ".safev($posisi,'[posisi]')." (".safev($lokasi,'[lokasi posisi]').").

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia";
  }

  jout([
    "ok"=>true,
    "pelamar"=>$p,
    "lowongan"=>$low,
    "stage_web"=>$stage,
    "decision"=>$decision,
    "title"=>$judul,
    "message"=>$isi
  ]);
}

/* =========================================================
   ✅ ACTION: get_zoom / save_zoom / zoom_meta
   - tanpa ubah database (disimpan JSON)
========================================================= */
if($action === 'get_zoom'){
  $lowongan_id = safe_int($_GET['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_GET['pelamar_id'] ?? 0);
  if($lowongan_id<=0 || $pelamar_id<=0) jout(["ok"=>false,"error"=>"Param invalid"]);
  $z = zoom_get_one($lowongan_id, $pelamar_id);
  jout(["ok"=>true,"zoom"=>$z]);
}



if($action === 'get_zoom_map'){
  $lowongan_id = safe_int($_GET['lowongan_id'] ?? 0);
  if($lowongan_id<=0) jout(["ok"=>false,"error"=>"Param invalid"]);
  $all = zoom_read_all();
  $map = [];
  if(isset($all[$lowongan_id]) && is_array($all[$lowongan_id])){
    foreach($all[$lowongan_id] as $pid=>$z){
      if(is_array($z)){
        // dianggap ada jika pernah tersimpan (online/offline)
        $map[(string)$pid] = 1;
      }
    }
  }
  jout(["ok"=>true, "map"=>$map]);
}
if($action === 'save_zoom'){
  $lowongan_id = safe_int($_POST['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_POST['pelamar_id'] ?? 0);
  $metode      = strtolower(trim((string)($_POST['metode'] ?? 'online')));
  $tanggal     = trim((string)($_POST['tanggal'] ?? ''));
  $jam         = trim((string)($_POST['jam'] ?? ''));
  $link        = trim((string)($_POST['link'] ?? ''));
  $lokasi      = trim((string)($_POST['lokasi'] ?? ''));

  if($lowongan_id<=0 || $pelamar_id<=0) jout(["ok"=>false,"error"=>"Param invalid"]);
  if($metode !== 'online' && $metode !== 'offline') $metode = 'online';

  // Validasi: Online wajib ada link, Offline bebas (link boleh kosong)
  if($metode === 'online'){
    if($link === '') jout(["ok"=>false,"error"=>"Metode Online: Link Zoom wajib diisi"]);
    if(!preg_match('~^https?://~i', $link)){
      if(strpos($link, 'zoom') !== false && strpos($link, '://') === false){
        $link = 'https://' . ltrim($link,'/');
      }
    }
  }else{
    // offline: kosongkan link kalau user isi sembarang
    if($link && !preg_match('~^https?://~i', $link)){
      $link = '';
    }
  }

  $zoom = [
    "metode"  => $metode,
    "tanggal" => $tanggal,
    "jam"     => $jam,
    "link"    => $link,
    "lokasi"  => $lokasi,
    "updated" => date('c')
  ];

  $ok = zoom_set_one($lowongan_id, $pelamar_id, $zoom);
  if(!$ok) jout(["ok"=>false,"error"=>"Gagal simpan zoom (file permission)"]);
  jout(["ok"=>true, "zoom"=>$zoom]);
}

if($action === 'zoom_meta'){
  $lowongan_id = safe_int($_GET['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_GET['pelamar_id'] ?? 0);
  $metode      = strtolower(trim((string)($_GET['metode'] ?? 'online')));
  $tanggal     = trim((string)($_GET['tanggal'] ?? ''));
  $jam         = trim((string)($_GET['jam'] ?? ''));
  $link        = trim((string)($_GET['link'] ?? ''));
  $lokasi      = trim((string)($_GET['lokasi'] ?? ''));
  if($lowongan_id<=0 || $pelamar_id<=0) jout(["ok"=>false,"error"=>"Param invalid"]);
  if($metode !== 'online' && $metode !== 'offline') $metode = 'online';

  $low = get_lowongan_meta($conn, $lowongan_id);
  $p   = get_pelamar_meta($conn, $pelamar_id, $lowongan_id);

  $nama   = safev($p['nama'] ?? '', '[nama]');
  $posisi = safev($low['posisi'] ?? '', '[posisi]');
  $kota   = safev($low['kota'] ?? '', '[lokasi posisi]');

  $tglLine = "Tanggal : " . ($tanggal !== '' ? $tanggal : "");
  $jamLine = "Jam     : " . ($jam !== '' ? $jam : "");

  if($metode === 'offline'){
    $lokLine = "Lokasi  : " . ($lokasi !== '' ? $lokasi : "");
    $msg =
"Yth. Bapak/Ibu {$nama},

Terima kasih telah mengikuti proses rekrutmen
PT Wiraswasta Gemilang Indonesia
untuk posisi {$posisi} ({$kota}).

Kami informasikan bahwa Anda diundang untuk mengikuti
Interview HRD & User secara OFFLINE.

Jadwal Interview:
{$tglLine}
{$jamLine}
{$lokLine}

Mohon konfirmasi kehadiran Anda dengan membalas pesan ini.

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia";
  }else{
    // online
    $msg =
"Yth. Bapak/Ibu {$nama},

Terima kasih telah mengikuti proses rekrutmen
PT Wiraswasta Gemilang Indonesia
untuk posisi {$posisi} ({$kota}).

Kami informasikan bahwa Anda diundang untuk mengikuti
Interview HRD & User melalui Zoom.

Jadwal Interview:
{$tglLine}
{$jamLine}

Berikut Link Zoom:
" . ($link !== '' ? $link : "") . "

Mohon konfirmasi kehadiran Anda dengan membalas pesan ini.

Hormat kami,
HR Recruitment
PT Wiraswasta Gemilang Indonesia";
  }

  jout([
    "ok"=>true,
    "pelamar"=>$p,
    "lowongan"=>$low,
    "message"=>$msg
  ]);
}

/* =========================================================
   ✅ ACTION: get_favorit
========================================================= */
if($action === 'get_favorit'){
  $lowongan_id = safe_int($_GET['lowongan_id'] ?? 0);
  if($lowongan_id<=0) jout(["ok"=>false,"error"=>"lowongan_id invalid"]);

  $favStat = read_json($FAVSTAT_FILE);
  $favLow  = $favStat[(string)$lowongan_id] ?? [];

  $TEL_COL = pick_first_col($conn,'pelamar',['telepon','no_hp','hp','phone']);
  $telSel  = $TEL_COL ? "p.`$TEL_COL` AS hp" : "'' AS hp";

  $q = mysqli_query($conn, "
    SELECT p.id, p.nama, p.email, $telSel
    FROM pelamar p
    WHERE p.lowongan_id = $lowongan_id AND p.favorit = 1
    ORDER BY p.id DESC
  ");

  $items = [];
  if($q){
    while($r = mysqli_fetch_assoc($q)){
      $pid = (int)$r['id'];
      $ak = $favLow[(string)$pid] ?? null;
      if(is_array($ak) && !empty($ak['date']) && empty($ak['date_human'])){
        $ak['date_human'] = human_date($ak['date']);
      }

      $items[] = [
        "id"=>$pid,
        "nama"=>(string)($r['nama'] ?? ''),
        "email"=>(string)($r['email'] ?? ''),
        "hp"=>trim((string)($r['hp'] ?? '')),
        "aksi"=>$ak
      ];
    }
  }
  jout(["ok"=>true,"items"=>$items]);
}

/* =========================================================
   ACTION: remove_favorit
========================================================= */
if($action === 'remove_favorit'){
  $lowongan_id = safe_int($_POST['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_POST['pelamar_id'] ?? 0);
  if($lowongan_id<=0 || $pelamar_id<=0) jout(["ok"=>false,"error"=>"Param invalid"]);

  if(!db_has_col($conn,'pelamar','favorit')){
    jout(["ok"=>false,"error"=>"Kolom favorit tidak ada di tabel pelamar"]);
  }

  $upd = mysqli_query($conn, "
    UPDATE pelamar
    SET favorit = 0
    WHERE id = $pelamar_id AND lowongan_id = $lowongan_id
    LIMIT 1
  ");
  if(!$upd){
    jout(["ok"=>false,"error"=>"Gagal update favorit (DB)"]);
  }

  $lk = (string)$lowongan_id;
  $pk = (string)$pelamar_id;
  $fav = read_json($FAVSTAT_FILE);
  if(isset($fav[$lk][$pk])){
    unset($fav[$lk][$pk]);
    write_json($FAVSTAT_FILE, $fav);
  }

  jout(["ok"=>true]);
}

/* =========================================================
   ACTION: get_board (inject upload)
========================================================= */
if($action === 'get_board'){
  $lowongan_id = safe_int($_GET['lowongan_id'] ?? 0);
  if($lowongan_id<=0) jout(["ok"=>false,"error"=>"lowongan_id invalid"]);

  $board = read_json($BOARD_FILE);
  $upl   = read_json($UPLOAD_FILE);

  $lowKey = (string)$lowongan_id;
  if(!isset($board[$lowKey]) || !is_array($board[$lowKey])) $board[$lowKey] = [];
  ensure_board_defaults($board[$lowKey]);

  foreach($board[$lowKey] as $st => &$arr){
    if(!is_array($arr)) $arr = [];
    foreach($arr as &$it){
      $pid = (string)((int)($it['id'] ?? 0));
      if(isset($it['aksi']) && is_array($it['aksi'])){
        if(!empty($it['aksi']['date']) && empty($it['aksi']['date_human'])){
          $it['aksi']['date_human'] = human_date($it['aksi']['date']);
        }
      }
      if(isset($upl[$lowKey][$st][$pid]) && is_array($upl[$lowKey][$st][$pid])){
        $it['upload'] = [
          "file" => (string)($upl[$lowKey][$st][$pid]['file'] ?? ''),
          "name" => (string)($upl[$lowKey][$st][$pid]['name'] ?? 'Download')
        ];
      }
    }
  }
  unset($arr, $it);

  $counts = [];
  foreach(["Initiate Call","Interview HRD & User","Psikotest","MCU","Onboarding"] as $st){
    $counts[$st] = is_array($board[$lowKey][$st]) ? count($board[$lowKey][$st]) : 0;
  }

  jout(["ok"=>true,"board"=>$board[$lowKey],"counts"=>$counts]);
}

/* =========================================================
   ACTION: fav_decision
========================================================= */
if($action === 'fav_decision'){
  $lowongan_id = safe_int($_POST['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_POST['pelamar_id'] ?? 0);
  $value       = (string)($_POST['value'] ?? '');
  if($lowongan_id<=0 || $pelamar_id<=0) jout(["ok"=>false,"error"=>"Param invalid"]);
  if($value !== 'lolos' && $value !== 'tidak_lolos') jout(["ok"=>false,"error"=>"Value invalid"]);

  $labelsFav = stage_labels("Initiate Call");
  $statusTextFav = ($value === 'lolos') ? $labelsFav['ok'] : $labelsFav['no'];

  $favStat = read_json($FAVSTAT_FILE);
  $lk = (string)$lowongan_id;
  if(!isset($favStat[$lk]) || !is_array($favStat[$lk])) $favStat[$lk] = [];
  $favStat[$lk][(string)$pelamar_id] = [
    "status"=>$statusTextFav,
    "date"=>now_date(),
    "date_human"=>human_date(now_date()),
  ];
  write_json($FAVSTAT_FILE, $favStat);

  if($value === 'lolos'){
    $q = mysqli_query($conn, "
      SELECT id, nama, email
      FROM pelamar
      WHERE id = $pelamar_id AND lowongan_id = $lowongan_id
      LIMIT 1
    ");
    $p = $q ? mysqli_fetch_assoc($q) : null;
    if(!$p) jout(["ok"=>false,"error"=>"Pelamar tidak ditemukan"]);

    $TEL_COL = pick_first_col($conn,'pelamar',['telepon','no_hp','hp','phone']);
    $hp = '';
    if($TEL_COL){
      $qq = mysqli_query($conn, "SELECT `$TEL_COL` AS hp FROM pelamar WHERE id=$pelamar_id LIMIT 1");
      $rr = $qq ? mysqli_fetch_assoc($qq) : null;
      $hp = (string)($rr['hp'] ?? '');
    }

    $board = read_json($BOARD_FILE);
    if(!isset($board[$lk]) || !is_array($board[$lk])) $board[$lk] = [];
    ensure_board_defaults($board[$lk]);

    $idx = board_get_item_index($board[$lk]["Initiate Call"], $pelamar_id);
    if($idx < 0){
      $board[$lk]["Initiate Call"][] = [
        "id" => (int)$pelamar_id,
        "nama" => (string)($p['nama'] ?? ''),
        "email"=> (string)($p['email'] ?? ''),
        "hp"   => (string)$hp,
        "aksi" => null,
        "upload"=> null,
        "from_favorit" => [
          "status" => $statusTextFav,
          "date" => now_date(),
          "date_human" => human_date(now_date())
        ]
      ];
    }else{
      if(!isset($board[$lk]["Initiate Call"][$idx]['from_favorit'])){
        $board[$lk]["Initiate Call"][$idx]['from_favorit'] = [
          "status" => $statusTextFav,
          "date" => now_date(),
          "date_human" => human_date(now_date())
        ];
      }
    }
    write_json($BOARD_FILE, $board);
  }

  jout(["ok"=>true]);
}

/* =========================================================
   ACTION: set_aksi
========================================================= */
if($action === 'set_aksi'){
  $lowongan_id = safe_int($_POST['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_POST['pelamar_id'] ?? 0);
  $stage       = (string)($_POST['stage'] ?? '');
  $value       = (string)($_POST['value'] ?? '');
  if($lowongan_id<=0 || $pelamar_id<=0 || $stage==='') jout(["ok"=>false,"error"=>"Param invalid"]);
  if(!in_array($stage, $STAGES, true)) jout(["ok"=>false,"error"=>"Stage invalid"]);

  $labels = stage_labels($stage);
  $isOk = in_array($value, ['lolos','terima'], true);
  $isNo = in_array($value, ['tidak_lolos','ditolak'], true);
  if(!$isOk && !$isNo) jout(["ok"=>false,"error"=>"Value invalid"]);

  $statusText = $isOk ? $labels['ok'] : $labels['no'];

  $board = read_json($BOARD_FILE);
  $lk = (string)$lowongan_id;
  if(!isset($board[$lk]) || !is_array($board[$lk])) $board[$lk] = [];
  ensure_board_defaults($board[$lk]);

  $idx = board_get_item_index($board[$lk][$stage], $pelamar_id);
  if($idx < 0){
    $q = mysqli_query($conn, "
      SELECT id, nama, email
      FROM pelamar
      WHERE id = $pelamar_id AND lowongan_id = $lowongan_id
      LIMIT 1
    ");
    $p = $q ? mysqli_fetch_assoc($q) : null;
    if(!$p) jout(["ok"=>false,"error"=>"Pelamar tidak ditemukan"]);

    $TEL_COL = pick_first_col($conn,'pelamar',['telepon','no_hp','hp','phone']);
    $hp = '';
    if($TEL_COL){
      $qq = mysqli_query($conn, "SELECT `$TEL_COL` AS hp FROM pelamar WHERE id=$pelamar_id LIMIT 1");
      $rr = $qq ? mysqli_fetch_assoc($qq) : null;
      $hp = (string)($rr['hp'] ?? '');
    }

    $board[$lk][$stage][] = [
      "id" => (int)$pelamar_id,
      "nama" => (string)($p['nama'] ?? ''),
      "email"=> (string)($p['email'] ?? ''),
      "hp"   => (string)$hp,
      "aksi" => null,
      "upload"=> null
    ];
    $idx = board_get_item_index($board[$lk][$stage], $pelamar_id);
  }

  $board[$lk][$stage][$idx]['aksi'] = [
    "status"=>$statusText,
    "date"=>now_date(),
    "date_human"=>human_date(now_date()),
  ];

  if($isOk){
    $next = stage_next($stage);
    if($next){
      $idx2 = board_get_item_index($board[$lk][$next], $pelamar_id);
      if($idx2 < 0){
        $src = $board[$lk][$stage][$idx];
        $copy = $src;
        $copy['aksi'] = null;
        $copy['upload'] = null;
        $copy['from'] = [
          "stage" => $stage,
          "status" => $statusText,
          "date" => now_date(),
          "date_human" => human_date(now_date())
        ];
        $board[$lk][$next][] = $copy;
      }
    }
  }

  write_json($BOARD_FILE, $board);
  jout(["ok"=>true]);
}

/* =========================================================
   ACTION: remove_from_stage
========================================================= */
if($action === 'remove_from_stage'){
  $lowongan_id = safe_int($_POST['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_POST['pelamar_id'] ?? 0);
  $stage       = (string)($_POST['stage'] ?? '');
  if($lowongan_id<=0 || $pelamar_id<=0 || $stage==='') jout(["ok"=>false,"error"=>"Param invalid"]);
  if(!in_array($stage, $STAGES, true)) jout(["ok"=>false,"error"=>"Stage invalid"]);

  $lk = (string)$lowongan_id;
  $pidKey = (string)$pelamar_id;

  $board = read_json($BOARD_FILE);
  if(!isset($board[$lk]) || !is_array($board[$lk])) $board[$lk] = [];
  ensure_board_defaults($board[$lk]);

  $idx = board_get_item_index($board[$lk][$stage], $pelamar_id);
  if($idx >= 0){
    $it = $board[$lk][$stage][$idx];
    if(!empty($it['upload']['file'])){
      unlink_if_exists(normalize_upload_path_abs($it['upload']['file']));
    }
    array_splice($board[$lk][$stage], $idx, 1);
    write_json($BOARD_FILE, $board);
  }

  $upl = read_json($UPLOAD_FILE);
  if(isset($upl[$lk][$stage][$pidKey])){
    $fileRel = $upl[$lk][$stage][$pidKey]['file'] ?? '';
    unlink_if_exists(normalize_upload_path_abs($fileRel));
    unset($upl[$lk][$stage][$pidKey]);
    write_json($UPLOAD_FILE, $upl);
  }

  $cat = read_json($CAT_FILE);
  if(isset($cat[$lk][$pidKey]['stages'][$stage])){
    unset($cat[$lk][$pidKey]['stages'][$stage]);
    write_json($CAT_FILE, $cat);
  }

  jout(["ok"=>true]);
}

/* =========================================================
   ACTION: clear_lowongan
========================================================= */
if($action === 'clear_lowongan'){
  $lowongan_id = safe_int($_POST['lowongan_id'] ?? 0);
  if($lowongan_id<=0) jout(["ok"=>false,"error"=>"lowongan_id invalid"]);

  $lk = (string)$lowongan_id;

  $upl = read_json($UPLOAD_FILE);
  if(isset($upl[$lk]) && is_array($upl[$lk])){
    foreach($upl[$lk] as $st => $map){
      foreach((array)$map as $pid => $info){
        if(!empty($info['file'])) unlink_if_exists(normalize_upload_path_abs($info['file']));
      }
    }
  }
  unset($upl[$lk]);
  write_json($UPLOAD_FILE, $upl);

  $board = read_json($BOARD_FILE);
  $board[$lk] = [];
  ensure_board_defaults($board[$lk]);
  write_json($BOARD_FILE, $board);

  $cat = read_json($CAT_FILE);
  unset($cat[$lk]);
  write_json($CAT_FILE, $cat);

  $fav = read_json($FAVSTAT_FILE);
  unset($fav[$lk]);
  write_json($FAVSTAT_FILE, $fav);

  $zoom = zoom_read_all();
  unset($zoom[$lk]);
  zoom_write_all($zoom);

  jout(["ok"=>true]);
}

/* =========================================================
   ACTION: get_catatan / save_catatan
========================================================= */
if($action === 'get_catatan'){
  $lowongan_id = safe_int($_GET['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_GET['pelamar_id'] ?? 0);
  $stage       = (string)($_GET['stage'] ?? '');
  if($lowongan_id<=0 || $pelamar_id<=0 || $stage==='') jout(["ok"=>false,"error"=>"Param invalid"]);

  $cat = read_json($CAT_FILE);
  $lk = (string)$lowongan_id;
  $pk = (string)$pelamar_id;

  $note = null;
  if(isset($cat[$lk][$pk]['stages'][$stage]) && is_array($cat[$lk][$pk]['stages'][$stage])){
    $note = $cat[$lk][$pk]['stages'][$stage];
  }elseif($stage === "Initiate Call" && isset($cat[$lk][$pk]['text'])){
    $note = ["text" => (string)$cat[$lk][$pk]['text']];
  }

  jout(["ok"=>true,"note"=>$note]);
}

if($action === 'save_catatan'){
  $lowongan_id = safe_int($_POST['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_POST['pelamar_id'] ?? 0);
  $stage       = (string)($_POST['stage'] ?? '');
  $text        = (string)($_POST['text'] ?? '');
  if($lowongan_id<=0 || $pelamar_id<=0 || $stage==='') jout(["ok"=>false,"error"=>"Param invalid"]);

  $cat = read_json($CAT_FILE);
  $lk = (string)$lowongan_id;
  $pk = (string)$pelamar_id;

  if(!isset($cat[$lk]) || !is_array($cat[$lk])) $cat[$lk] = [];
  if(!isset($cat[$lk][$pk]) || !is_array($cat[$lk][$pk])) $cat[$lk][$pk] = [];
  if(!isset($cat[$lk][$pk]['stages']) || !is_array($cat[$lk][$pk]['stages'])) $cat[$lk][$pk]['stages'] = [];

  $cat[$lk][$pk]['stages'][$stage] = [
    "text"=>$text,
    "updated_at"=>date('c')
  ];

  if($stage === "Initiate Call"){
    $cat[$lk][$pk]['text'] = $text;
  }

  write_json($CAT_FILE, $cat);
  jout(["ok"=>true]);
}

/* =========================================================
   ✅ ACTION: get_upload (BARU)
========================================================= */
if($action === 'get_upload'){
  $lowongan_id = safe_int($_GET['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_GET['pelamar_id'] ?? 0);
  $stage       = (string)($_GET['stage'] ?? '');
  if($lowongan_id<=0 || $pelamar_id<=0 || $stage==='') jout(["ok"=>false,"error"=>"Param invalid"]);
  if(!in_array($stage, $STAGES, true)) jout(["ok"=>false,"error"=>"Stage invalid"]);

  $upl = read_json($UPLOAD_FILE);
  $lk = (string)$lowongan_id;
  $pk = (string)$pelamar_id;

  $info = $upl[$lk][$stage][$pk] ?? null;
  if(!$info) jout(["ok"=>true, "upload"=>null]);

  $base = build_base_url();
  $link = make_abs_url($base, "seleksi_api.php?action=download_upload&lowongan_id=".$lowongan_id."&pelamar_id=".$pelamar_id."&stage=".rawurlencode($stage));
  $info['download'] = $link;

  jout(["ok"=>true,"upload"=>$info]);
}

/* =========================================================
   ✅ ACTION: download_upload (BARU) - file download aman
========================================================= */
if($action === 'download_upload'){
  $lowongan_id = safe_int($_GET['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_GET['pelamar_id'] ?? 0);
  $stage       = (string)($_GET['stage'] ?? '');

  if($lowongan_id<=0 || $pelamar_id<=0 || $stage===''){
    http_response_code(400);
    exit("Param invalid");
  }
  if(!in_array($stage, $STAGES, true)){
    http_response_code(400);
    exit("Stage invalid");
  }

  $upl = read_json($UPLOAD_FILE);
  $lk = (string)$lowongan_id;
  $pk = (string)$pelamar_id;

  $info = $upl[$lk][$stage][$pk] ?? null;
  if(!$info || empty($info['file'])){
    http_response_code(404);
    exit("File not found");
  }

  $abs = normalize_upload_path_abs($info['file']);
  if(!$abs || !is_file($abs)){
    http_response_code(404);
    exit("File missing");
  }

  $downloadName = safe_basename($info['name'] ?? basename($abs));
  $mime = @mime_content_type($abs);
  if(!$mime) $mime = "application/octet-stream";

  // kirim file
  header('Content-Type: '.$mime);
  header('Content-Length: '.filesize($abs));
  header('Content-Disposition: attachment; filename="'.str_replace('"','',$downloadName).'"');
  header('X-Content-Type-Options: nosniff');
  readfile($abs);
  exit;
}

/* =========================================================
   ✅ ACTION: delete_upload (BARU) - hapus file upload
========================================================= */
if($action === 'delete_upload'){
  $lowongan_id = safe_int($_POST['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_POST['pelamar_id'] ?? 0);
  $stage       = (string)($_POST['stage'] ?? '');
  if($lowongan_id<=0 || $pelamar_id<=0 || $stage==='') jout(["ok"=>false,"error"=>"Param invalid"]);
  if(!in_array($stage, $STAGES, true)) jout(["ok"=>false,"error"=>"Stage invalid"]);

  $upl = read_json($UPLOAD_FILE);
  $lk = (string)$lowongan_id;
  $pk = (string)$pelamar_id;

  if(isset($upl[$lk][$stage][$pk])){
    $fileRel = $upl[$lk][$stage][$pk]['file'] ?? '';
    unlink_if_exists(normalize_upload_path_abs($fileRel));
    unset($upl[$lk][$stage][$pk]);
    write_json($UPLOAD_FILE, $upl);
  }

  $board = read_json($BOARD_FILE);
  if(isset($board[$lk]) && is_array($board[$lk])){
    ensure_board_defaults($board[$lk]);
    $idx = board_get_item_index($board[$lk][$stage], $pelamar_id);
    if($idx >= 0){
      $board[$lk][$stage][$idx]['upload'] = null;
      write_json($BOARD_FILE, $board);
    }
  }

  jout(["ok"=>true]);
}

/* =========================================================
   ✅ ACTION: upload_file (BARU) - SEMUA FILE, SEMUA STAGE
   field file = $_FILES['file']
========================================================= */
if($action === 'upload_file'){
  $lowongan_id = safe_int($_POST['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_POST['pelamar_id'] ?? 0);
  $stage       = (string)($_POST['stage'] ?? '');

  if($lowongan_id<=0 || $pelamar_id<=0 || $stage==='') jout(["ok"=>false,"error"=>"Param invalid"]);
  if(!in_array($stage, $STAGES, true)) jout(["ok"=>false,"error"=>"Stage invalid"]);

  $store = upload_store_file($UPLOAD_DIR, $lowongan_id, $pelamar_id, $stage, 'file');
  if(empty($store['ok'])) jout(["ok"=>false,"error"=>$store['error'] ?? 'Upload gagal']);

  $upl = read_json($UPLOAD_FILE);
  $lk = (string)$lowongan_id;
  $pk = (string)$pelamar_id;

  if(!isset($upl[$lk]) || !is_array($upl[$lk])) $upl[$lk] = [];
  if(!isset($upl[$lk][$stage]) || !is_array($upl[$lk][$stage])) $upl[$lk][$stage] = [];

  // hapus file lama kalau ada
  if(isset($upl[$lk][$stage][$pk]['file'])){
    unlink_if_exists(normalize_upload_path_abs($upl[$lk][$stage][$pk]['file']));
  }

  $upl[$lk][$stage][$pk] = [
    "file"=>$store['fileRel'],
    "name"=>$store['name'],
    "ext"=>$store['ext'],
    "size"=>$store['size'],
    "uploaded_at"=>date('c')
  ];
  write_json($UPLOAD_FILE, $upl);

  // inject ke board
  $board = read_json($BOARD_FILE);
  if(!isset($board[$lk]) || !is_array($board[$lk])) $board[$lk] = [];
  ensure_board_defaults($board[$lk]);
  $idx = board_get_item_index($board[$lk][$stage], $pelamar_id);
  if($idx >= 0){
    $board[$lk][$stage][$idx]['upload'] = ["file"=>$store['fileRel'],"name"=>$store['name']];
    write_json($BOARD_FILE, $board);
  }

  $base = build_base_url();
  $download = make_abs_url($base, "seleksi_api.php?action=download_upload&lowongan_id=".$lowongan_id."&pelamar_id=".$pelamar_id."&stage=".rawurlencode($stage));

  jout(["ok"=>true,"file"=>$store['fileRel'],"name"=>$store['name'],"download"=>$download]);
}

/* =========================================================
   ACTION: upload_excel (punyamu) - tetap ada (kompatibel)
========================================================= */
if($action === 'upload_excel'){
  $lowongan_id = safe_int($_POST['lowongan_id'] ?? 0);
  $pelamar_id  = safe_int($_POST['pelamar_id'] ?? 0);
  $stage       = (string)($_POST['stage'] ?? '');
  if($lowongan_id<=0 || $pelamar_id<=0 || $stage==='') jout(["ok"=>false,"error"=>"Param invalid"]);
  if(!in_array($stage, ["Interview HRD & User","Psikotest","MCU"], true)){
    jout(["ok"=>false,"error"=>"Upload hanya untuk Interview/Psikotest/MCU"]);
  }

  if(empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])){
    jout(["ok"=>false,"error"=>"File tidak ada"]);
  }

  $name = (string)($_FILES['file']['name'] ?? 'file.xlsx');
  $tmp  = (string)($_FILES['file']['tmp_name'] ?? '');
  $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if(!in_array($ext, ['xls','xlsx','csv'], true)){
    jout(["ok"=>false,"error"=>"Format harus .xls/.xlsx/.csv"]);
  }

  $fn = "L{$lowongan_id}_P{$pelamar_id}_".preg_replace('/[^a-zA-Z0-9]/','', $stage)."_".date('YmdHis').".".$ext;
  $destAbs = $UPLOAD_DIR . "/" . $fn;
  if(!@move_uploaded_file($tmp, $destAbs)){
    jout(["ok"=>false,"error"=>"Gagal simpan file"]);
  }

  $fileRel = "_data/uploads/" . $fn;

  $upl = read_json($UPLOAD_FILE);
  $lk = (string)$lowongan_id;
  if(!isset($upl[$lk]) || !is_array($upl[$lk])) $upl[$lk] = [];
  if(!isset($upl[$lk][$stage]) || !is_array($upl[$lk][$stage])) $upl[$lk][$stage] = [];

  $pidKey = (string)$pelamar_id;
  if(isset($upl[$lk][$stage][$pidKey]['file'])){
    unlink_if_exists(normalize_upload_path_abs($upl[$lk][$stage][$pidKey]['file']));
  }

  $upl[$lk][$stage][$pidKey] = [
    "file"=>$fileRel,
    "name"=>safe_basename($name),
    "uploaded_at"=>date('c')
  ];
  write_json($UPLOAD_FILE, $upl);

  $board = read_json($BOARD_FILE);
  if(!isset($board[$lk]) || !is_array($board[$lk])) $board[$lk] = [];
  ensure_board_defaults($board[$lk]);
  $idx = board_get_item_index($board[$lk][$stage], $pelamar_id);
  if($idx >= 0){
    $board[$lk][$stage][$idx]['upload'] = ["file"=>$fileRel,"name"=>safe_basename($name)];
    write_json($BOARD_FILE, $board);
  }

  $base = build_base_url();
  $download = make_abs_url($base, "seleksi_api.php?action=download_upload&lowongan_id=".$lowongan_id."&pelamar_id=".$pelamar_id."&stage=".rawurlencode($stage));

  jout(["ok"=>true,"file"=>$fileRel,"name"=>safe_basename($name), "download"=>$download]);
}

/* =========================================================
   ACTION: export_report (punya kamu)
========================================================= */
if($action === 'export_report'){
  $lowParam = (string)($_GET['lowongan_id'] ?? '');
  $mode     = (string)($_GET['mode'] ?? 'stage');
  $stage    = (string)($_GET['stage'] ?? '');

  $validStages = ["Initiate Call","Interview HRD & User","Psikotest","MCU","Onboarding"];
  if($mode === 'stage'){
    if(!in_array($stage, $validStages, true)){
      http_response_code(400);
      exit("Stage invalid");
    }
  }

  $KOTA_COL = pick_first_col($conn,'lowongan',['kota','lokasi','lokasi_posisi']);
  $lokSel = $KOTA_COL ? "`$KOTA_COL` AS kota" : "'' AS kota";
  $lowMap = [];
  $qLow = mysqli_query($conn, "SELECT id, posisi, $lokSel FROM lowongan ORDER BY id DESC");
  if($qLow){
    while($r = mysqli_fetch_assoc($qLow)){
      $lowMap[(string)((int)$r['id'])] = [
        'posisi' => (string)($r['posisi'] ?? ''),
        'kota'   => trim((string)($r['kota'] ?? ''))
      ];
    }
  }

  $board = read_json($BOARD_FILE);
  $upl   = read_json($UPLOAD_FILE);
  $base  = build_base_url();

  $targetLows = [];
  if($lowParam === 'all'){
    $tmp = [];
    foreach($board as $lk => $_v){
      if(preg_match('/^\d+$/', (string)$lk)) $tmp[(string)$lk] = true;
    }
    foreach($lowMap as $lk => $_v){
      $tmp[(string)$lk] = true;
    }
    $targetLows = array_keys($tmp);
  } else {
    $lid = safe_int($lowParam);
    if($lid <= 0){ http_response_code(400); exit("lowongan_id invalid"); }
    $targetLows = [(string)$lid];
  }

  $rows = [];
  $seen = [];

  $getAksi = function($bLow, $st, $pid){
    $arr = (isset($bLow[$st]) && is_array($bLow[$st])) ? $bLow[$st] : [];
    foreach($arr as $it){
      if((int)($it['id'] ?? 0) === (int)$pid){
        $ax = $it['aksi'] ?? null;
        if(is_array($ax) && !empty($ax['date']) && empty($ax['date_human'])){
          $ax['date_human'] = human_date($ax['date']);
        }
        return $ax;
      }
    }
    return null;
  };

  // sekarang upload link pakai download_upload agar aman
  $getUploadLink = function($lk, $st, $pid) use ($base){
    return make_abs_url($base, "seleksi_api.php?action=download_upload&lowongan_id=".$lk."&pelamar_id=".$pid."&stage=".rawurlencode($st));
  };

  foreach($targetLows as $lk){
    $lk = (string)$lk;
    if($lk === '' || !preg_match('/^\d+$/', $lk)) continue;

    $bLow = (isset($board[$lk]) && is_array($board[$lk])) ? $board[$lk] : [];
    ensure_board_defaults($bLow);

    $pool = [];
    foreach(["Initiate Call","Interview HRD & User","Psikotest","MCU","Onboarding"] as $st){
      foreach(($bLow[$st] ?? []) as $it){
        $pid = (int)($it['id'] ?? 0);
        if($pid > 0) $pool[(string)$pid] = true;
      }
    }

    foreach(array_keys($pool) as $pidKey){
      $pid = (int)$pidKey;
      $uniq = $lk.':'.$pidKey;
      if(isset($seen[$uniq])) continue;
      $seen[$uniq] = true;

      $nama = '';
      foreach(["Initiate Call","Interview HRD & User","Psikotest","MCU","Onboarding"] as $st){
        foreach(($bLow[$st] ?? []) as $it){
          if((int)($it['id'] ?? 0) === $pid){
            $nama = (string)($it['nama'] ?? '');
            break 2;
          }
        }
      }

      $pos = $lowMap[$lk]['posisi'] ?? '';
      $kota = $lowMap[$lk]['kota'] ?? '';
      $posAlamat = trim($pos);
      if($kota !== '') $posAlamat = trim($posAlamat.' — '.$kota);

      if($mode === 'all'){
        $axInit = $getAksi($bLow, "Initiate Call", $pid);
        $axInt  = $getAksi($bLow, "Interview HRD & User", $pid);
        $axPs   = $getAksi($bLow, "Psikotest", $pid);
        $axMcu  = $getAksi($bLow, "MCU", $pid);
        $axOnb  = $getAksi($bLow, "Onboarding", $pid);

        $uInt = $getUploadLink($lk, "Interview HRD & User", $pid);
        $uPs  = $getUploadLink($lk, "Psikotest", $pid);
        $uMcu = $getUploadLink($lk, "MCU", $pid);

        $rows[] = [
          $nama,
          $posAlamat,
          stage_cell_text($axInit),
          stage_cell_text($axInt),
          stage_cell_text($axPs),
          stage_cell_text($axMcu),
          stage_cell_text($axOnb),
          $uInt,
          $uPs,
          $uMcu
        ];
      } else {
        $ax = $getAksi($bLow, $stage, $pid);
        $tahapText = stage_cell_text($ax);

        $uploadLink = $getUploadLink($lk, $stage, $pid);

        $rows[] = [
          $nama,
          $posAlamat,
          $tahapText,
          $uploadLink
        ];
      }
    }
  }

  if($mode === 'all'){
    $headers = ["NO","Nama","Posisi & Alamat","Initiate Call","Interview","Psikotest","MCU","Onboarding","Hasil Interview","Hasil Psikotes","Hasil MCU"];
    $title = "LAPORAN DATA SELEKSI HASIL SELURUH TAHAPAN";
    $meta  = ($lowParam==='all') ? "Data Seleksi : Semua Posisi" : "Data Seleksi : Sesuai Filter Posisi";
  } else {
    $headers = ["NO","Nama","Posisi & Alamat",$stage,"Hasil Upload (Link)"];
    $title = "LAPORAN DATA SELEKSI • ".$stage;
    $meta  = ($lowParam==='all') ? "Data Seleksi : Semua Posisi (Tahap ".$stage.")" : "Data Seleksi : Sesuai Filter Posisi (Tahap ".$stage.")";
  }

  $finalRows = [];
  $no=1;
  foreach($rows as $r){
    $final = [$no++];
    foreach($r as $v) $final[] = $v;
    $finalRows[] = $final;
  }

  $exportUrl = __DIR__ . "/seleksi_export_excel.php";
  $_GET['title'] = $title;
  $_GET['meta']  = $meta;
  $_GET['filename'] = ($mode==='all'
      ? ("laporan_seleksi_".($lowParam==='all'?'semua_posisi':'posisi_'.$lowParam)."_".date('Ymd_His').".xls")
      : ("laporan_seleksi_".preg_replace('/[^a-zA-Z0-9]/','_', $stage)."_".($lowParam==='all'?'semua_posisi':'posisi_'.$lowParam)."_".date('Ymd_His').".xls")
    );

  $_POST['headers_json'] = json_encode($headers, JSON_UNESCAPED_UNICODE);
  $_POST['rows_json']    = json_encode($finalRows, JSON_UNESCAPED_UNICODE);

  include $exportUrl;
  exit;
}

jout(["ok"=>false,"error"=>"Action tidak dikenali"]);
