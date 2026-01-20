<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

// ambil filter
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$lowongan_id = isset($_GET['lowongan_id']) ? (int)$_GET['lowongan_id'] : 0;

/* ===== FIX: filter lokasi (0 = semua) ===== */
$lokasi = isset($_GET['lokasi']) ? trim((string)$_GET['lokasi']) : '';
if($lokasi === '0') $lokasi = '';
if(strlen($lokasi) > 120) $lokasi = substr($lokasi, 0, 120);

/* ===== helper cek kolom ===== */
function db_has_col($conn, $table, $col){
  $table = preg_replace('/[^a-zA-Z0-9_]/','', $table);
  $col   = preg_replace('/[^a-zA-Z0-9_]/','', $col);
  $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$col'");
  return ($q && mysqli_num_rows($q) > 0);
}

/* ===== tentukan kolom lokasi lowongan sesuai DB (kota atau lokasi) ===== */
$lokCol = null;
if(db_has_col($conn, 'lowongan', 'kota')) $lokCol = 'kota';
elseif(db_has_col($conn, 'lowongan', 'lokasi')) $lokCol = 'lokasi';

// list posisi
$listPosisi = mysqli_query($conn, "SELECT id, posisi FROM lowongan ORDER BY posisi ASC");

/* ===== list lokasi dari lowongan (sesuai tambah lowongan) ===== */
$listLokasi = null;
if($lokCol){
  $c = preg_replace('/[^a-zA-Z0-9_]/','', $lokCol);
  $listLokasi = mysqli_query($conn, "
    SELECT DISTINCT TRIM(`$c`) AS lokasi
    FROM lowongan
    WHERE `$c` IS NOT NULL AND TRIM(`$c`) <> ''
    ORDER BY TRIM(`$c`) ASC
  ");
}

// where
$where = "WHERE 1=1 ";
if($bulan >= 1 && $bulan <= 12) $where .= " AND MONTH(pelamar.tanggal) = $bulan ";
if($tahun >= 2000 && $tahun <= 2100) $where .= " AND YEAR(pelamar.tanggal) = $tahun ";
if($lowongan_id > 0) $where .= " AND pelamar.lowongan_id = $lowongan_id ";

/* ===== apply filter lokasi (dari lowongan.kota / lowongan.lokasi) ===== */
if($lokasi !== '' && $lokCol){
  $c = preg_replace('/[^a-zA-Z0-9_]/','', $lokCol);
  $lokSafe = mysqli_real_escape_string($conn, $lokasi);
  $where .= " AND TRIM(lowongan.`$c`) = '$lokSafe' ";
}

/* =========================================================
   FITUR: TELAH DIBACA (TANPA DB) - simpan JSON di admin/_data
========================================================= */
$readDir  = __DIR__ . "/_data";
$readFile = $readDir . "/pelamar_dibaca.json";
$readMap  = [];

if(is_file($readFile)){
  $raw = @file_get_contents($readFile);
  $tmp = json_decode((string)$raw, true);
  if(is_array($tmp)) $readMap = $tmp;
}

// back url biar habis klik gak ilang filter
$backUrl = $_SERVER['REQUEST_URI'] ?? 'pelamar.php';
$backEnc = urlencode($backUrl);

// helper status dibaca
function pelamar_is_read($readMap, $id){
  $id = (string)(int)$id;
  return isset($readMap[$id]) && is_array($readMap[$id]);
}

/* =========================================================
   DATA: ambil dari DB (tanpa urutan favorit)
   NANTI kita urutkan di PHP sesuai rule:
   - Kalau FAVORIT + DIBACA => paling atas
   - Kalau DIBACA doang => turun ke bawah
========================================================= */
$dataQ = mysqli_query($conn,"
  SELECT pelamar.*, pelamar.tgl_lahir AS tgl_lahir, lowongan.posisi
  FROM pelamar
  JOIN lowongan ON pelamar.lowongan_id = lowongan.id
  $where
  ORDER BY pelamar.tanggal DESC
");

$rows = [];
if($dataQ){
  while($r = mysqli_fetch_assoc($dataQ)){
    $rows[] = $r;
  }
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

// qs buat excel
$q = [];
if($bulan) $q[]="bulan=".$bulan;
if($tahun) $q[]="tahun=".$tahun;
if($lowongan_id) $q[]="lowongan_id=".$lowongan_id;
if($lokasi !== '') $q[]="lokasi=".urlencode($lokasi);
$qs = count($q) ? "?".implode("&",$q) : "";

/* =========================================================
   SORTING CUSTOM:
   RULE YANG LU MAU:
   - "Telah dibaca" doang => card turun (bawah)
   - card naik ke atas cuma kalau FAVORIT + DIBACA
   (Favorit doang tetap bagus (di tengah atas), tapi tidak ngalahin fav+read)
========================================================= */
function pelamar_weight($fav, $read){
  $fav  = $fav ? 1 : 0;
  $read = $read ? 1 : 0;

  // 0 = paling atas, 3 = paling bawah
  if($fav === 1 && $read === 1) return 0; // fav + read => TOP
  if($fav === 1 && $read === 0) return 1; // fav doang => setelah top
  if($fav === 0 && $read === 0) return 2; // normal => tengah
  return 3;                                // read doang => BAWAH
}

usort($rows, function($a, $b) use ($readMap){
  $aid = (int)($a['id'] ?? 0);
  $bid = (int)($b['id'] ?? 0);

  $aFav  = !empty($a['favorit']);
  $bFav  = !empty($b['favorit']);
  $aRead = pelamar_is_read($readMap, $aid);
  $bRead = pelamar_is_read($readMap, $bid);

  $wa = pelamar_weight($aFav, $aRead);
  $wb = pelamar_weight($bFav, $bRead);

  if($wa !== $wb) return $wa <=> $wb;

  // dalam grup yang sama, urutkan berdasarkan tanggal terbaru
  $ta = !empty($a['tanggal']) ? strtotime($a['tanggal']) : 0;
  $tb = !empty($b['tanggal']) ? strtotime($b['tanggal']) : 0;
  if($ta !== $tb) return $tb <=> $ta;

  // tie breaker biar stabil
  return $bid <=> $aid;
});

$totalPelamar = count($rows);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Data Pelamar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/style.css">

  <style>
    /* ===== PATCH: benerin layout biar gak ketimpa sidebar ===== */
    .content{
      position:relative;
      z-index:1;
      width:calc(100% - 260px);
      margin-left:260px;
      padding:24px;
      box-sizing:border-box;
    }
    @media (max-width: 980px){
      .content{ width:100%; margin-left:0; padding:16px; }
    }

    /* ===== HERO BIRU (HEADER) ===== */
    .pelamar-hero{
      position:relative;
      border-radius:22px;
      padding:28px 22px;
      background: linear-gradient(90deg, #4f46e5, #2563eb);
      box-shadow:0 18px 60px rgba(2,6,23,.14);
      border:1px solid rgba(255,255,255,.10);
      overflow:hidden;
      margin-bottom:14px;
    }
    .pelamar-hero:after{
      content:"";
      position:absolute;
      right:-120px;
      top:-140px;
      width:420px;
      height:420px;
      background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.22), rgba(255,255,255,0) 60%);
      transform: rotate(12deg);
      pointer-events:none;
    }
    .pelamar-hero-row{
      position:relative;
      z-index:1;
      display:grid;
      grid-template-columns: 1fr auto 1fr;
      align-items:center;
      gap:14px;
    }

    .pelamar-hero-title{
      justify-self:center;
      text-align:center;
      font-size:44px;
      font-weight:900;
      letter-spacing:.4px;
      color:#fff;
      margin:0;
      line-height:1.05;
    }
    @media (max-width: 980px){
      .pelamar-hero-title{ font-size:34px; }
    }

    .pelamar-badge{
      justify-self:center;
      align-self:start;
      margin-top:10px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:8px 12px;
      border-radius:999px;
      font-weight:900;
      font-size:12px;
      color:#fff;
      background:rgba(255,255,255,.12);
      border:1px solid rgba(255,255,255,.20);
      backdrop-filter: blur(8px);
      z-index:2;
    }

    @media (max-width: 980px){
      .pelamar-hero-row{ grid-template-columns: 1fr; justify-items:start; }
      .pelamar-hero-title{ justify-self:start; text-align:left; }
      .pelamar-badge{ justify-self:start; }
    }

    /* ===== FILTER BAR HITAM ===== */
    .pelamar-filter{
      background: linear-gradient(180deg, #0b1220, #070d18);
      border-radius:18px;
      padding:14px;
      border:1px solid rgba(255,255,255,.10);
      box-shadow:0 16px 50px rgba(2,6,23,.18);
      margin-bottom:18px;
    }

    .filter-bar{
      display:grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap:12px;
      align-items:end;
      width:100%;
      margin:0;
    }
    @media (max-width: 980px){
      .filter-bar{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 560px){
      .filter-bar{ grid-template-columns: 1fr; }
    }

    .filter-item{
      display:flex;
      flex-direction:column;
      gap:6px;
      min-width:0;
    }
    .filter-item label{
      font-size:11px;
      font-weight:900;
      letter-spacing:.35px;
      color:rgba(255,255,255,.85);
      text-align:left;
    }
    .filter-item select{
      height:42px;
      padding:0 12px;
      border-radius:12px;
      border:1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.08);
      color:#fff;
      font-weight:900;
      outline:none;
      appearance:auto;
    }
    .filter-item select option{
      background:#ffffff;
      color:#0f172a;
      font-weight:800;
    }

    .btn-apply{
      grid-column: 1 / -1;
      height:44px;
      border-radius:12px;
      font-weight:900;
      cursor:pointer;
      background: linear-gradient(180deg, rgba(37,99,235,.55), rgba(30,58,138,.55));
      color:#fff;
      border:1px solid rgba(255,255,255,.14);
      letter-spacing:.2px;
    }
    .btn-apply:hover{ filter:brightness(1.04); }

    .filter-bottom{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      margin-top:12px;
    }
    @media (max-width: 560px){
      .filter-bottom{ flex-direction:column; align-items:stretch; }
    }

    .btn-reset{
      height:42px;
      padding:0 14px;
      border-radius:12px;
      font-weight:900;
      text-decoration:none;
      background:rgba(255,255,255,.10);
      color:#fff;
      border:1px solid rgba(255,255,255,.18);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      white-space:nowrap;
      width:max-content;
    }
    @media (max-width: 560px){
      .btn-reset{ width:100%; }
    }

    .btn-excel{
      height:42px;
      padding:0 16px;
      border-radius:12px;
      text-decoration:none;
      font-weight:900;
      background: rgba(16,185,129,.14);
      border:1px solid rgba(16,185,129,.28);
      color:#d1fae5;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      white-space:nowrap;
      width:max-content;
    }
    .btn-excel:hover{ filter:brightness(1.05); }
    @media (max-width: 560px){
      .btn-excel{ width:100%; }
    }

    .btn-fav{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:6px 12px;
      border-radius:999px;
      font-size:12px;
      font-weight:900;
      text-decoration:none;
      margin-top:6px;
      white-space:nowrap;
    }
    .fav-on{
      background:rgba(234,179,8,.18);
      color:#92400e;
      border:1px solid rgba(234,179,8,.35);
    }
    .fav-off{
      background:rgba(100,116,139,.12);
      color:#334155;
      border:1px solid rgba(100,116,139,.25);
    }

    /* ===== tombol "Telah dibaca" mirip favorit ===== */
    .btn-read{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:6px 12px;
      border-radius:999px;
      font-size:12px;
      font-weight:900;
      text-decoration:none;
      margin-top:6px;
      white-space:nowrap;
    }
    .read-on{
      background: rgba(16,185,129,.16);
      color:#065f46;
      border:1px solid rgba(16,185,129,.32);
    }
    .read-off{
      background: rgba(148,163,184,.12);
      color:#334155;
      border:1px solid rgba(148,163,184,.25);
    }

    /* baris kanan: tanggal + pill */
    .pelamar-action .topline{
      display:flex;
      align-items:center;
      justify-content:flex-end;
      gap:10px;
      flex-wrap:wrap;
    }
    .pelamar-action .tgl-submit{
      font-weight:800;
      font-size:12px;
      color:#0f172a;
      opacity:.85;
      white-space:nowrap;
    }
    .pelamar-action .pillwrap{
      display:flex;
      gap:8px;
      align-items:center;
      justify-content:flex-end;
      flex-wrap:wrap;
    }

    .meta{
      display:grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap:10px;
      margin-top:10px;
      color:#334155;
    }
    @media (max-width: 1100px){
      .meta{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 680px){
      .meta{ grid-template-columns: 1fr; }
    }
    .meta span{
      display:flex;
      align-items:center;
      gap:8px;
      padding:8px 10px;
      border-radius:12px;
      background:rgba(148,163,184,.10);
      border:1px solid rgba(148,163,184,.20);
      font-weight:800;
      font-size:12px;
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
    }

    .modal-overlay{
      position:fixed;
      inset:0;
      background:rgba(2,6,23,.55);
      display:none;
      align-items:center;
      justify-content:center;
      padding:18px;
      z-index:9999;
    }
    .modal-overlay.show{ display:flex; }

    .modal-box{
      width:min(1100px, 96vw);
      max-height:90vh;
      background:#fff;
      border-radius:18px;
      overflow:hidden;
      box-shadow:0 30px 80px rgba(0,0,0,.35);
      border:1px solid rgba(15,23,42,.12);
      display:flex;
      flex-direction:column;
    }
    .modal-head{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      padding:12px 14px;
      background:linear-gradient(90deg, rgba(15,23,42,.95), rgba(30,41,59,.95));
      color:#fff;
    }
    .modal-title{
      font-weight:900;
      font-size:13px;
      letter-spacing:.3px;
    }
    .modal-close{
      border:0;
      background:rgba(255,255,255,.14);
      color:#fff;
      font-weight:900;
      padding:8px 12px;
      border-radius:12px;
      cursor:pointer;
    }
    .modal-body{
      padding:14px;
      overflow:auto;
      background:#f8fafc;
    }
    .modal-loading{
      padding:14px;
      border-radius:14px;
      background:#fff;
      border:1px solid #e5e7eb;
      font-weight:900;
      color:#0f172a;
    }
    .modal-error{
      padding:14px;
      border-radius:14px;
      background:#fee2e2;
      border:1px solid #fecaca;
      font-weight:900;
      color:#991b1b;
    }

    /* FIX: Screening + Hapus sejajar rapi */
    .pelamar-action .action-row{
      display:flex;
      gap:10px;
      align-items:center;
      margin-top:8px;
    }
    .btn-screening{
      height:38px;
      padding:0 14px;
      border-radius:999px;
      font-weight:900;
      font-size:12px;
      letter-spacing:.2px;
      background:#0b1220;
      color:#fff;
      border:1px solid rgba(255,255,255,.10);
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      white-space:nowrap;
    }
    .btn-screening:hover{ filter:brightness(1.05); }
  </style>
</head>

<body class="admin-body">
<?php include 'sidebar.php'; ?>

<div class="content">

  <!-- HERO BIRU -->
  <div class="pelamar-hero">
    <div class="pelamar-hero-row">
      <div></div>
      <div style="display:flex; flex-direction:column; align-items:center;">
        <h2 class="pelamar-hero-title">Data Pelamar</h2>
        <div class="pelamar-badge"><?= $totalPelamar; ?> Pelamar</div>
      </div>
      <div></div>
    </div>
  </div>

  <!-- FILTER HITAM -->
  <div class="pelamar-filter">
    <form class="filter-bar" method="GET" action="pelamar.php">
      <div class="filter-item">
        <label>Bulan</label>
        <select name="bulan">
          <option value="0">Semua</option>
          <?php
            $bulanNama = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            for($m=1;$m<=12;$m++):
              $sel = ($bulan === $m) ? 'selected' : '';
          ?>
            <option value="<?= $m; ?>" <?= $sel; ?>><?= $bulanNama[$m]; ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="filter-item">
        <label>Tahun</label>
        <select name="tahun">
          <option value="0">Semua</option>
          <?php
            $startYear = 2025;
            $thisYear  = (int)date('Y');
            $endYear   = $thisYear + 10;
            for($y=$startYear; $y<=$endYear; $y++):
              $sel = ($tahun === $y) ? 'selected' : '';
          ?>
            <option value="<?= $y; ?>" <?= $sel; ?>><?= $y; ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="filter-item">
        <label>Posisi</label>
        <select name="lowongan_id">
          <option value="0">Semua Posisi</option>
          <?php if($listPosisi && mysqli_num_rows($listPosisi)>0): ?>
            <?php while($lp = mysqli_fetch_assoc($listPosisi)): ?>
              <option value="<?= (int)$lp['id']; ?>" <?= ($lowongan_id==(int)$lp['id'])?'selected':''; ?>>
                <?= htmlspecialchars($lp['posisi']); ?>
              </option>
            <?php endwhile; ?>
          <?php endif; ?>
        </select>
      </div>

      <div class="filter-item">
        <label>Lokasi</label>
        <select name="lokasi" <?= $lokCol ? '' : 'disabled'; ?>>
          <option value="0">Semua Lokasi</option>
          <?php if($listLokasi && mysqli_num_rows($listLokasi)>0): ?>
            <?php while($ll = mysqli_fetch_assoc($listLokasi)): ?>
              <?php
                $lokOpt = trim($ll['lokasi'] ?? '');
                if($lokOpt === '') continue;
                $sel = ($lokasi === $lokOpt) ? 'selected' : '';
              ?>
              <option value="<?= htmlspecialchars($lokOpt); ?>" <?= $sel; ?>>
                <?= htmlspecialchars($lokOpt); ?>
              </option>
            <?php endwhile; ?>
          <?php endif; ?>
        </select>
      </div>

      <button class="btn-apply" type="submit">Terapkan</button>

      <div class="filter-bottom">
        <a class="btn-reset" href="pelamar.php">Reset</a>
        <a href="pelamar_export_excel.php<?= $qs; ?>" class="btn-excel">⬇ Download Excel</a>
      </div>
    </form>
  </div>

  <?php if($success === 'deleted'): ?>
    <div class="alert-success">✅ Data pelamar berhasil dihapus.</div>
  <?php endif; ?>

  <?php if($error === 'notfound'): ?>
    <div class="alert-danger">⚠️ Data pelamar tidak ditemukan.</div>
  <?php endif; ?>

  <?php if($error === 'deletefailed'): ?>
    <div class="alert-danger">❌ Gagal menghapus data.</div>
  <?php endif; ?>

  <div class="pelamar-list">
    <?php if(count($rows) > 0): ?>
      <?php foreach($rows as $p): ?>
        <?php
          $pid = (int)($p['id'] ?? 0);
          $inisial = strtoupper(substr((string)$p['nama'], 0, 1));
          $pendidikanFull = ($p['pendidikan'] ?? '') . (!empty($p['jurusan']) ? ' - '.$p['jurusan'] : '');

          $favClass = !empty($p['favorit']) ? 'fav-on' : 'fav-off';
          $favText  = !empty($p['favorit']) ? '⭐ Favorit' : '☆ Tandai';

          $isRead = pelamar_is_read($readMap, $pid);
          $readClass = $isRead ? 'read-on' : 'read-off';
          $readText  = $isRead ? '✅ Telah dibaca' : '👁 Belum dibaca';

          $tgl_lahir = $p['tgl_lahir'] ?? '';
          $tglLahirView = '-';
          $umurView = '-';

          if(!empty($tgl_lahir) && $tgl_lahir !== '0000-00-00'){
            $tglLahirView = date('d M Y', strtotime($tgl_lahir));
            try{
              $birthDate = new DateTime($tgl_lahir);
              $today = new DateTime();
              $umurView = $today->diff($birthDate)->y . ' Tahun';
            } catch(Exception $e){
              $umurView = '-';
            }
          }

          $tglSubmit = !empty($p['tanggal']) ? date('d M Y', strtotime($p['tanggal'])) : '';
        ?>
        <div class="pelamar-item">
          <div class="avatar"><?= $inisial; ?></div>

          <div class="pelamar-info">
            <h3><?= htmlspecialchars($p['nama']); ?></h3>
            <small><?= htmlspecialchars($p['posisi']); ?></small>

            <div class="meta">
              <span>📧 <?= htmlspecialchars($p['email']); ?></span>
              <span>📞 <?= htmlspecialchars($p['telepon']); ?></span>
              <span>📍 <?= htmlspecialchars($p['kota']); ?></span>
              <span>🎓 <?= htmlspecialchars($pendidikanFull); ?></span>
              <span>🎂 <?= htmlspecialchars($tglLahirView); ?></span>
              <span>🧮 <?= htmlspecialchars($umurView); ?></span>
            </div>
          </div>

          <div class="pelamar-action">
            <div class="topline">
              <?php if($tglSubmit !== ''): ?>
                <small class="tgl-submit"><?= $tglSubmit; ?></small>
              <?php endif; ?>

              <div class="pillwrap">
                <a href="pelamar_toggle_favorit.php?id=<?= $pid; ?>&back=<?= $backEnc; ?>" class="btn-fav <?= $favClass; ?>">
                  <?= $favText; ?>
                </a>

                <a href="pelamar_toggle_dibaca.php?id=<?= $pid; ?>&back=<?= $backEnc; ?>" class="btn-read <?= $readClass; ?>">
                  <?= $readText; ?>
                </a>
              </div>
            </div>

            <?php if(!empty($p['cv'])): ?>
              <button type="button"
                      class="btn-cv"
                      data-id="<?= $pid; ?>"
                      onclick="openDetail(this)">
                Detail
              </button>

              <div class="action-row">
                <button type="button"
                        class="btn-screening"
                        data-id="<?= $pid; ?>"
                        onclick="openDetail(this)">
                  Screening
                </button>

                <a href="hapus_pelamar.php?id=<?= $pid; ?>"
                   class="btn-hapus"
                   onclick="return confirm('Yakin mau hapus pelamar ini? Data dan file CV akan dihapus.');">
                  Hapus
                </a>
              </div>

            <?php else: ?>
              <span class="no-cv">CV tidak ada</span>

              <a href="hapus_pelamar.php?id=<?= $pid; ?>"
                 class="btn-hapus"
                 onclick="return confirm('Yakin mau hapus pelamar ini? Data dan file CV akan dihapus.');">
                Hapus
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">Tidak ada data Pelamar.</div>
    <?php endif; ?>
  </div>

</div>

<!-- MODAL DETAIL -->
<div class="modal-overlay" id="detailModal" aria-hidden="true">
  <div class="modal-box" role="dialog" aria-modal="true">
    <div class="modal-head">
      <div class="modal-title">DETAIL PELAMAR</div>
      <button class="modal-close" type="button" onclick="closeDetail()">Tutup ✕</button>
    </div>
    <div class="modal-body" id="detailBody">
      <div class="modal-loading">Memuat detail…</div>
    </div>
  </div>
</div>

<script>
  // auto submit pas ganti pilihan
  const bar = document.querySelector('.filter-bar');
  if(bar){
    bar.querySelectorAll('select').forEach(s=>{
      s.addEventListener('change', ()=> bar.submit());
    });
  }

  const modal = document.getElementById('detailModal');
  const body  = document.getElementById('detailBody');

  function openDetail(btn){
    const id = btn.getAttribute('data-id');
    if(!id) return;

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    body.innerHTML = '<div class="modal-loading">Memuat detail…</div>';

    fetch('pelamar_detail.php?id=' + encodeURIComponent(id), { credentials:'same-origin' })
      .then(r => r.text())
      .then(html => {
        body.innerHTML = html;

        // ======================================================
        // PATCH PENTING: eksekusi ulang <script> dari HTML hasil fetch
        // (karena script dalam innerHTML tidak jalan otomatis)
        // Ini bikin tombol OCR & event JS di pelamar_detail.php jadi bisa diklik
        // ======================================================
        body.querySelectorAll('script').forEach(old => {
          const s = document.createElement('script');
          // copy atribut (kalau ada src/type dll)
          [...old.attributes].forEach(a => s.setAttribute(a.name, a.value));
          // copy isi script inline
          s.text = old.textContent || '';
          old.replaceWith(s);
        });
      })
      .catch(() => {
        body.innerHTML = '<div class="modal-error">Gagal ambil detail. Cek file pelamar_detail.php.</div>';
      });
  }

  function closeDetail(){
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
  }

  modal.addEventListener('click', (e)=>{
    if(e.target === modal) closeDetail();
  });

  document.addEventListener('keydown', (e)=>{
    if(e.key === 'Escape' && modal.classList.contains('show')) closeDetail();
  });
</script>

</body>
</html>
