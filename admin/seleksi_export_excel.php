<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
  header("Location: ../auth/login.php");
  exit;
}

/* =========================
   Helpers
========================= */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function safe_int($v){ return (int)($v ?? 0); }

function ensure_dir($dir){
  if(!is_dir($dir)) @mkdir($dir, 0775, true);
}
function read_json($file){
  if(!is_file($file)) return [];
  $raw = @file_get_contents($file);
  $j = json_decode((string)$raw, true);
  return is_array($j) ? $j : [];
}
function human_date($ymd){
  if(!$ymd) return '';
  $t = strtotime($ymd);
  if(!$t) return (string)$ymd;
  $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  $m = (int)date('n',$t);
  return date('d',$t).' '.($bulan[$m]??date('M',$t)).' '.date('Y',$t);
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
function norm_stage($s){
  $s = trim((string)$s);
  if($s === "Interview") return "Interview HRD & User";
  return $s;
}
function lowongan_label($conn, $lowongan_id, $KOTA_COL){
  $lowongan_id = (int)$lowongan_id;
  if($lowongan_id<=0) return "Semua Posisi";
  $lokSel = $KOTA_COL ? "`$KOTA_COL` AS kota" : "'' AS kota";
  $q = mysqli_query($conn, "SELECT id,posisi,$lokSel FROM lowongan WHERE id=$lowongan_id LIMIT 1");
  $r = $q ? mysqli_fetch_assoc($q) : null;
  if(!$r) return "Posisi Tidak Ditemukan";
  $pos = trim((string)($r['posisi'] ?? ''));
  $kot = trim((string)($r['kota'] ?? ''));
  return $kot ? ($pos . " — " . $kot) : ($pos ?: "Posisi");
}
function is_upload_stage($stage){
  return in_array($stage, ["Interview HRD & User","Psikotest","MCU"], true);
}

/* =========================
   Input
========================= */
$posisiParam = $_GET['posisi'] ?? '';     // "all" / lowongan_id
$namaParam   = trim((string)($_GET['nama'] ?? ''));
$stageRaw    = $_GET['stage'] ?? 'SELURUH';

$stageUpper = strtoupper(trim((string)$stageRaw));
$stageParam = ($stageUpper === "SELURUH") ? "SELURUH" : norm_stage($stageRaw);

/* =========================
   Paths JSON (HANYA board + uploads)
========================= */
$DATA_DIR = __DIR__ . "/_data";
ensure_dir($DATA_DIR);

$BOARD_FILE  = $DATA_DIR . "/seleksi_board.json";
$UPLOAD_FILE = $DATA_DIR . "/seleksi_uploads.json";

$board = read_json($BOARD_FILE);
$upl   = read_json($UPLOAD_FILE);

/* =========================
   Lowongan label helper
========================= */
$KOTA_COL = pick_first_col($conn,'lowongan',['kota','lokasi','lokasi_posisi','kota_lokasi']);

/* =========================
   Lowongan list
========================= */
$LOWONGAN_IDS = [];
if($posisiParam === "all"){
  $q = mysqli_query($conn, "SELECT id FROM lowongan ORDER BY id DESC");
  if($q){
    while($r = mysqli_fetch_assoc($q)){
      $LOWONGAN_IDS[] = (int)$r['id'];
    }
  }
} else {
  $lid = safe_int($posisiParam);
  if($lid > 0) $LOWONGAN_IDS[] = $lid;
}

/* =========================
   Build rows from BOARD ONLY
   (Favorit tidak dipakai)
========================= */
$STAGES_ALL = ["Initiate Call","Interview HRD & User","Psikotest","MCU","Onboarding"];

$rows = [];

foreach($LOWONGAN_IDS as $lowId){
  $lk = (string)$lowId;
  $lowLabel = lowongan_label($conn, $lowId, $KOTA_COL);

  // kumpulin semua pelamar_id yang pernah muncul di tahapan
  $idsMap = [];
  foreach($STAGES_ALL as $st){
    if(!empty($board[$lk][$st]) && is_array($board[$lk][$st])){
      foreach($board[$lk][$st] as $it){
        $pid = (int)($it['id'] ?? 0);
        if($pid>0) $idsMap[$pid] = true;
      }
    }
  }
  if(empty($idsMap)) continue;

  foreach(array_keys($idsMap) as $pid){

    // nama dari board item manapun
    $nama = "";
    foreach($STAGES_ALL as $st){
      if(!empty($board[$lk][$st]) && is_array($board[$lk][$st])){
        foreach($board[$lk][$st] as $it){
          if((int)($it['id'] ?? 0) === (int)$pid){
            $nama = (string)($it['nama'] ?? '');
            break 2;
          }
        }
      }
    }

    if($namaParam !== ""){
      if(stripos($nama, $namaParam) === false) continue;
    }

    // status per tahap
    $stageStatus = [];
    foreach($STAGES_ALL as $st){
      $stageStatus[$st] = "";
      if(!empty($board[$lk][$st]) && is_array($board[$lk][$st])){
        foreach($board[$lk][$st] as $it){
          if((int)($it['id'] ?? 0) === (int)$pid){
            $ak = $it['aksi'] ?? null;
            if(is_array($ak) && !empty($ak['status'])){
              $dt = $ak['date_human'] ?? '';
              if(!$dt && !empty($ak['date'])) $dt = human_date($ak['date']);
              $stageStatus[$st] = trim($ak['status'] . ' ' . $dt);
            }
            break;
          }
        }
      }
    }

    // link upload (Interview/Psikotest/MCU)
    $uploadLink = [
      "Interview HRD & User" => "",
      "Psikotest" => "",
      "MCU" => "",
    ];
    foreach(["Interview HRD & User","Psikotest","MCU"] as $st){
      if(isset($upl[$lk][$st][(string)$pid]) && is_array($upl[$lk][$st][(string)$pid])){
        $uploadLink[$st] = (string)($upl[$lk][$st][(string)$pid]['file'] ?? '');
      }
    }

    $rows[] = [
      "nama" => $nama ?: "(Tanpa Nama)",
      "posisi_alamat" => $lowLabel,
      "stage_status" => $stageStatus,
      "upload_link" => $uploadLink,
    ];
  }
}

/* =========================
   Header texts
========================= */
$posLabel = ($posisiParam === "all") ? "Semua Posisi" : lowongan_label($conn, safe_int($posisiParam), $KOTA_COL);

if($stageParam === "SELURUH"){
  $judulAtas = "LAPORAN DATA SELEKSI HASIL SELURUH TAHAPAN";
  $infoLine  = "Data Seleksi : Sesuai Filter Posisi";
  $colCount = 11;
} else {
  $judulAtas = "LAPORAN DATA SELEKSI HASIL " . strtoupper($stageParam);
  $infoLine  = "Data Seleksi : Sesuai Filter Posisi";
  $colCount = 5;
}

/* =========================
   Output Excel (HTML)
========================= */
$tsName = date('Ymd_His');
$fnameStage = ($stageParam === "SELURUH") ? "seluruh_tahapan" : preg_replace('/[^a-zA-Z0-9]+/','_', strtolower($stageParam));
$fnamePos   = ($posisiParam === "all") ? "semua_posisi" : ("posisi_" . (int)$posisiParam);
$filename = "laporan_seleksi_{$fnameStage}_{$fnamePos}_{$tsName}.xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html>
<head>
<meta charset="utf-8">
<style>
  body{ font-family: Calibri, Arial, sans-serif; font-size:11pt; }
  table{ border-collapse:collapse; }
  .title{ font-size:14pt; font-weight:700; text-align:center; }
  .subtitle{ font-size:12pt; font-weight:700; text-align:center; }
  .info{ font-size:11pt; font-weight:700; }

  th, td{ border:1px solid #000; padding:6px 8px; vertical-align:middle; }
  th{ font-weight:700; text-align:center; white-space:nowrap; }

  .no{ width:45px; text-align:center; }
  .nama{ width:220px; }
  .pos{ width:360px; }
  .stagecol{ width:220px; }
  .linkcol{ width:320px; }

  td{ white-space:nowrap; }
</style>
</head>
<body>

<table>
  <tr>
    <td colspan="<?=$colCount?>" class="title"><?=$judulAtas?></td>
  </tr>
  <tr>
    <td colspan="<?=$colCount?>" class="subtitle">PT Wiraswasta Gemilang Indonesia</td>
  </tr>
  <tr>
    <td colspan="<?=$colCount?>" class="info"><?=e($infoLine)?> (<?=e($posLabel)?>)</td>
  </tr>
  <tr><td colspan="<?=$colCount?>" style="border:none;height:6px;"></td></tr>

  <?php if($stageParam === "SELURUH"): ?>
    <tr>
      <th class="no">NO</th>
      <th class="nama">Nama</th>
      <th class="pos">Posisi &amp; Alamat</th>
      <th class="stagecol">Initiate Call</th>
      <th class="stagecol">Interview</th>
      <th class="stagecol">Psikotest</th>
      <th class="stagecol">MCU</th>
      <th class="stagecol">Onboarding</th>
      <th class="linkcol">Hasil Interview (Link)</th>
      <th class="linkcol">Hasil Psikotes (Link)</th>
      <th class="linkcol">Hasil MCU (Link)</th>
    </tr>

    <?php
      $no=1;
      foreach($rows as $r):
        $st = $r['stage_status'];
        $ln = $r['upload_link'];
        $linkInt  = $ln["Interview HRD & User"] ?? "";
        $linkPsi  = $ln["Psikotest"] ?? "";
        $linkMcu  = $ln["MCU"] ?? "";
    ?>
      <tr>
        <td class="no"><?=$no++?></td>
        <td class="nama"><?=e($r['nama'])?></td>
        <td class="pos"><?=e($r['posisi_alamat'])?></td>
        <td><?=e($st["Initiate Call"] ?? "")?></td>
        <td><?=e($st["Interview HRD & User"] ?? "")?></td>
        <td><?=e($st["Psikotest"] ?? "")?></td>
        <td><?=e($st["MCU"] ?? "")?></td>
        <td><?=e($st["Onboarding"] ?? "")?></td>
        <td><?php if($linkInt): ?><a href="<?=e($linkInt)?>"><?=e($linkInt)?></a><?php endif; ?></td>
        <td><?php if($linkPsi): ?><a href="<?=e($linkPsi)?>"><?=e($linkPsi)?></a><?php endif; ?></td>
        <td><?php if($linkMcu): ?><a href="<?=e($linkMcu)?>"><?=e($linkMcu)?></a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>

    <?php if(empty($rows)): ?>
      <tr><td colspan="<?=$colCount?>" style="text-align:center;font-weight:700;">Tidak ada data.</td></tr>
    <?php endif; ?>

  <?php else: ?>
    <tr>
      <th class="no">NO</th>
      <th class="nama">Nama</th>
      <th class="pos">Posisi &amp; Alamat</th>
      <th class="stagecol"><?=e($stageParam)?></th>
      <th class="linkcol">Hasil Upload Link</th>
    </tr>

    <?php
      $no=1;
      foreach($rows as $r):
        $st = $r['stage_status'];
        $ln = $r['upload_link'];

        $statusStage = $st[$stageParam] ?? "";
        $link = "";
        if(is_upload_stage($stageParam)){
          $link = $ln[$stageParam] ?? "";
        }
    ?>
      <tr>
        <td class="no"><?=$no++?></td>
        <td class="nama"><?=e($r['nama'])?></td>
        <td class="pos"><?=e($r['posisi_alamat'])?></td>
        <td><?=e($statusStage)?></td>
        <td><?php if($link): ?><a href="<?=e($link)?>"><?=e($link)?></a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>

    <?php if(empty($rows)): ?>
      <tr><td colspan="<?=$colCount?>" style="text-align:center;font-weight:700;">Tidak ada data.</td></tr>
    <?php endif; ?>

  <?php endif; ?>
</table>

</body>
</html>
