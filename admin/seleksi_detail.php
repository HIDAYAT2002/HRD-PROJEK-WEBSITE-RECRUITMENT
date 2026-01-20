<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

header('Content-Type: text/html; charset=utf-8');

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$lowongan_id = (int)($_GET['lowongan_id'] ?? 0);
$pelamar_id  = (int)($_GET['pelamar_id'] ?? 0);
if($lowongan_id<=0 || $pelamar_id<=0){
  echo '<div style="padding:14px;background:#fee2e2;border:1px solid #fecaca;border-radius:12px;color:#991b1b;font-weight:900;">Param tidak valid.</div>';
  exit;
}

function read_json($file){
  if(!is_file($file)) return [];
  $raw = @file_get_contents($file);
  $j = json_decode((string)$raw, true);
  return is_array($j) ? $j : [];
}

$TEL_COL = null;
$try = ['telepon','no_hp','hp','phone'];
foreach($try as $c){
  $q = mysqli_query($conn, "SHOW COLUMNS FROM pelamar LIKE '".mysqli_real_escape_string($conn,$c)."'");
  if($q && mysqli_num_rows($q)>0){ $TEL_COL = $c; break; }
}
$KOTA_COL = null;
foreach(['kota','lokasi','domisili','kota_lokasi'] as $c){
  $q = mysqli_query($conn, "SHOW COLUMNS FROM pelamar LIKE '".mysqli_real_escape_string($conn,$c)."'");
  if($q && mysqli_num_rows($q)>0){ $KOTA_COL = $c; break; }
}
$PEND_COL = null;
foreach(['pendidikan','pendidikan_terakhir','jurusan','pendidikan_jurusan'] as $c){
  $q = mysqli_query($conn, "SHOW COLUMNS FROM pelamar LIKE '".mysqli_real_escape_string($conn,$c)."'");
  if($q && mysqli_num_rows($q)>0){ $PEND_COL = $c; break; }
}
$TGL_COL = null;
foreach(['tgl_lahir','tanggal_lahir','birthdate'] as $c){
  $q = mysqli_query($conn, "SHOW COLUMNS FROM pelamar LIKE '".mysqli_real_escape_string($conn,$c)."'");
  if($q && mysqli_num_rows($q)>0){ $TGL_COL = $c; break; }
}

$selTel  = $TEL_COL ? "p.`$TEL_COL` AS hp" : "'' AS hp";
$selKota = $KOTA_COL ? "p.`$KOTA_COL` AS kota" : "'' AS kota";
$selPend = $PEND_COL ? "p.`$PEND_COL` AS pendidikan" : "'' AS pendidikan";
$selTgl  = $TGL_COL ? "p.`$TGL_COL` AS tgl_lahir" : "'' AS tgl_lahir";

$q = mysqli_query($conn, "
  SELECT p.id, p.nama, p.email, $selTel, $selKota, $selPend, $selTgl, l.posisi
  FROM pelamar p
  JOIN lowongan l ON p.lowongan_id = l.id
  WHERE p.id = $pelamar_id AND p.lowongan_id = $lowongan_id
  LIMIT 1
");
$p = $q ? mysqli_fetch_assoc($q) : null;
if(!$p){
  echo '<div style="padding:14px;background:#fee2e2;border:1px solid #fecaca;border-radius:12px;color:#991b1b;font-weight:900;">Data tidak ditemukan.</div>';
  exit;
}

// umur + format tgl
$umur = '';
$tglHuman = '';
if(!empty($p['tgl_lahir'])){
  $ts = strtotime($p['tgl_lahir']);
  if($ts){
    $bulan = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $m = (int)date('n',$ts);
    $tglHuman = date('d',$ts).' '.($bulan[$m]??date('M',$ts)).' '.date('Y',$ts);

    $born = new DateTime(date('Y-m-d',$ts));
    $now  = new DateTime(date('Y-m-d'));
    $diff = $born->diff($now);
    $umur = $diff->y . ' Tahun';
  }
}

// load catatan initiate
$DATA_DIR = __DIR__ . "/_data";
$CAT_FILE = $DATA_DIR . "/seleksi_catatan.json";
$cat = read_json($CAT_FILE);
$lk = (string)$lowongan_id;
$pk = (string)$pelamar_id;
$catText = '';
if(isset($cat[$lk][$pk]['stages']['Initiate Call']['text'])){
  $catText = (string)$cat[$lk][$pk]['stages']['Initiate Call']['text'];
} elseif(isset($cat[$lk][$pk]['text'])){
  $catText = (string)$cat[$lk][$pk]['text'];
}

// load uploads tahapan
$UPLOAD_FILE = $DATA_DIR . "/seleksi_uploads.json";
$upl = read_json($UPLOAD_FILE);

function stage_upload($upl, $lk, $stage, $pk){
  if(isset($upl[$lk][$stage][$pk]) && is_array($upl[$lk][$stage][$pk])){
    return $upl[$lk][$stage][$pk];
  }
  return null;
}
$uInterview = stage_upload($upl,$lk,'Interview HRD & User',$pk);
$uPsiko     = stage_upload($upl,$lk,'Psikotest',$pk);
$uMCU       = stage_upload($upl,$lk,'MCU',$pk);

// UI
?>
<style>
  .wrap{ display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
  .card{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:18px;
    padding:16px;
    box-shadow:0 12px 30px rgba(2,6,23,.08);
  }
  .dark{
    background:linear-gradient(180deg,#0b1220,#050b17);
    color:#fff;
    border:none;
  }
  .title{ font-weight:950; font-size:18px; margin-bottom:12px; }
  .grid{ display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
  .box{
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:10px 12px;
    background:#f8fafc;
  }
  .lbl{ font-size:12px; font-weight:950; color:#64748b; margin-bottom:4px; }
  .val{ font-weight:950; color:#0f172a; }
  .dark .val{ color:#fff; }
  .dark .lbl{ color:rgba(255,255,255,.75); }
  .hr{ height:1px; background:#e5e7eb; margin:14px 0; opacity:.8; }
  .resultTitle{ font-weight:950; margin-bottom:8px; }
  .muted{ opacity:.75; font-weight:900; }
  .fileLink{
    display:inline-flex;
    margin-top:6px;
    text-decoration:none;
    font-weight:900;
    font-size:12px;
    padding:10px 12px;
    border-radius:14px;
    background:rgba(37,99,235,.10);
    border:1px solid rgba(37,99,235,.18);
    color:#1d4ed8;
    max-width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
  }
  .noteBox{
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.10);
    border-radius:16px;
    padding:14px;
    min-height:160px;
    font-weight:900;
  }
  @media(max-width:980px){
    .wrap{ grid-template-columns:1fr; }
  }
</style>

<div class="wrap">
  <div class="card">
    <div class="title">DETAIL PELAMAR</div>
    <div class="grid">
      <div class="box"><div class="lbl">Nama</div><div class="val"><?=e($p['nama'])?></div></div>
      <div class="box"><div class="lbl">Posisi</div><div class="val"><?=e($p['posisi'] ?? '-')?></div></div>

      <div class="box"><div class="lbl">Email</div><div class="val"><?=e($p['email'] ?? '-')?></div></div>
      <div class="box"><div class="lbl">No. HP</div><div class="val"><?=e($p['hp'] ?? '-')?></div></div>

      <div class="box"><div class="lbl">Lokasi</div><div class="val"><?=e($p['kota'] ?? '-')?></div></div>
      <div class="box"><div class="lbl">Pendidikan</div><div class="val"><?=e($p['pendidikan'] ?? '-')?></div></div>

      <div class="box"><div class="lbl">Tanggal Lahir</div><div class="val"><?=e($tglHuman ?: '-')?></div></div>
      <div class="box"><div class="lbl">Umur</div><div class="val"><?=e($umur ?: '-')?></div></div>
    </div>

    <div class="hr"></div>

    <div class="resultTitle">Hasil Tahapan:</div>

    <div style="margin-bottom:10px;">
      <div style="font-weight:950;">Hasil Interview HRD & User:</div>
      <?php if($uInterview && !empty($uInterview['file'])): ?>
        <a class="fileLink" href="<?=e($uInterview['file'])?>" target="_blank">⬇ <?=e($uInterview['name'] ?? 'Download')?></a>
      <?php else: ?>
        <div class="muted">Belum ada.</div>
      <?php endif; ?>
    </div>

    <div style="margin-bottom:10px;">
      <div style="font-weight:950;">Hasil Psikotest:</div>
      <?php if($uPsiko && !empty($uPsiko['file'])): ?>
        <a class="fileLink" href="<?=e($uPsiko['file'])?>" target="_blank">⬇ <?=e($uPsiko['name'] ?? 'Download')?></a>
      <?php else: ?>
        <div class="muted">Belum ada.</div>
      <?php endif; ?>
    </div>

    <div>
      <div style="font-weight:950;">Hasil MCU:</div>
      <?php if($uMCU && !empty($uMCU['file'])): ?>
        <a class="fileLink" href="<?=e($uMCU['file'])?>" target="_blank">⬇ <?=e($uMCU['name'] ?? 'Download')?></a>
      <?php else: ?>
        <div class="muted">Belum ada.</div>
      <?php endif; ?>
    </div>

  </div>

  <div class="card dark">
    <div class="title">Hasil Catatan Initiate Call</div>
    <div class="muted" style="margin-bottom:10px;">Isi dari catatan yang kamu simpan di Initiate Call.</div>
    <div class="noteBox">
      <?= $catText ? nl2br(e($catText)) : '<span class="muted">Belum ada catatan.</span>' ?>
    </div>
  </div>
</div>
