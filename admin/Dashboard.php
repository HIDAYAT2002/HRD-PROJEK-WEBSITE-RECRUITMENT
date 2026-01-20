<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
    header("Location: http://ptwgi.com/career/auth/login.php");
    exit;
}

/* ======================
   STAT DATA
====================== */
$qTotalLow = mysqli_query($conn,"SELECT COUNT(*) AS c FROM lowongan");
$totalLow  = (int)mysqli_fetch_assoc($qTotalLow)['c'];

$qTotalPel = mysqli_query($conn,"
  SELECT COUNT(*) AS c
  FROM pelamar
  JOIN lowongan ON pelamar.lowongan_id = lowongan.id
");
$totalPel = (int)mysqli_fetch_assoc($qTotalPel)['c'];

$qPelHariIni = mysqli_query($conn,"
  SELECT COUNT(*) AS c
  FROM pelamar
  JOIN lowongan ON pelamar.lowongan_id = lowongan.id
  WHERE DATE(pelamar.tanggal)=CURDATE()
");
$pelHariIni = (int)mysqli_fetch_assoc($qPelHariIni)['c'];

$qPelBulanIni = mysqli_query($conn,"
  SELECT COUNT(*) AS c
  FROM pelamar
  JOIN lowongan ON pelamar.lowongan_id = lowongan.id
  WHERE MONTH(pelamar.tanggal)=MONTH(CURDATE())
    AND YEAR(pelamar.tanggal)=YEAR(CURDATE())
");
$pelBulanIni = (int)mysqli_fetch_assoc($qPelBulanIni)['c'];

/* ======================
   STAT SELEKSI (PENDING AKSI)
   Hanya yang BELUM dipilih aksinya
====================== */
$pendingSel = [
  'Initiate Call' => 0,
  'Interview HRD & User' => 0,
  'Psikotest' => 0,
  'MCU' => 0,
  'Onboarding' => 0,
];

$boardFile = __DIR__ . '/_data/seleksi_board.json';
$allBoards = read_json_file($boardFile);
if(is_array($allBoards)){
  foreach($allBoards as $lowKey => $board){
    if(!is_array($board)) continue;
    foreach($pendingSel as $stage => $v){
      $items = $board[$stage] ?? [];
      if(!is_array($items)) continue;
      foreach($items as $it){
        $st = $it['aksi']['status'] ?? '';
        if($st === '' || $st === null){
          $pendingSel[$stage]++;
        }
      }
    }
  }
}

$pendingInitiate = (int)($pendingSel['Initiate Call'] ?? 0);
$pendingInterview = (int)($pendingSel['Interview HRD & User'] ?? 0);
$pendingPsikotest = (int)($pendingSel['Psikotest'] ?? 0);
$pendingMCU = (int)($pendingSel['MCU'] ?? 0);
$pendingOnboarding = (int)($pendingSel['Onboarding'] ?? 0);


/* ======================
   DATA GRAFIK
====================== */
$qGrafik = mysqli_query($conn,"
    SELECT lowongan.posisi, COUNT(pelamar.id) AS total
    FROM pelamar
    JOIN lowongan ON pelamar.lowongan_id = lowongan.id
    GROUP BY pelamar.lowongan_id
    ORDER BY total DESC
");

$labels = [];
$values = [];
while($g = mysqli_fetch_assoc($qGrafik)){
    $labels[] = $g['posisi'];
    $values[] = (int)$g['total'];
}

/* =========================================================
   REMINDER STORAGE (TANPA DB) - SIMPAN KE FILE JSON SERVER
========================================================= */

/** bikin userKey biar per akun (ga campur) */
$userKey = 'role:' . ($_SESSION['role'] ?? 'user');
if(isset($_SESSION['id'])) {
  $userKey = 'id:' . (int)$_SESSION['id'];
} elseif(isset($_SESSION['user_id'])) {
  $userKey = 'id:' . (int)$_SESSION['user_id'];
} elseif(isset($_SESSION['email'])) {
  $userKey = 'email:' . strtolower(trim($_SESSION['email']));
}

/** folder data (buat file) */
$dataDir  = __DIR__ . '/_data';
$dataFile = $dataDir . '/reminders.json';

/** pastiin folder ada */
if(!is_dir($dataDir)){
  @mkdir($dataDir, 0755, true);
}

/** helper file read/write */
function read_json_file($file){
  if(!file_exists($file)) return [];
  $raw = @file_get_contents($file);
  if($raw === false || trim($raw)==='') return [];
  $arr = json_decode($raw, true);
  return is_array($arr) ? $arr : [];
}

function write_json_file_atomic($file, $data){
  $tmp = $file . '.tmp';
  $json = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  if(@file_put_contents($tmp, $json, LOCK_EX) === false) return false;
  return @rename($tmp, $file);
}

/** endpoint AJAX */
if(isset($_GET['ajax']) && $_GET['ajax']==='reminder'){
  header('Content-Type: application/json; charset=utf-8');

  $action = $_GET['action'] ?? '';

  $all = read_json_file($dataFile);
  if(!isset($all[$userKey]) || !is_array($all[$userKey])) $all[$userKey] = [];
  $list = $all[$userKey];

  $fail = function($msg, $code=400){
    http_response_code($code);
    echo json_encode(['ok'=>false,'error'=>$msg]);
    exit;
  };
  $ok = function($extra=[]){
    echo json_encode(array_merge(['ok'=>true], $extra));
    exit;
  };

  if($action === 'list'){
    $date = $_GET['date'] ?? '';
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $fail('Tanggal tidak valid');

    $items = array_values(array_filter($list, function($x) use ($date){
      return isset($x['date']) && $x['date'] === $date;
    }));
    usort($items, function($a,$b){
      return strcmp(($a['time'] ?? ''), ($b['time'] ?? ''));
    });

    $ok(['items'=>$items]);
  }

  if($action === 'month'){
    $ym = $_GET['ym'] ?? ''; // YYYY-MM
    if(!preg_match('/^\d{4}-\d{2}$/', $ym)) $fail('Parameter bulan tidak valid');

    $dates = [];
    foreach($list as $x){
      $d = $x['date'] ?? '';
      if(is_string($d) && strpos($d, $ym.'-')===0){
        $dates[$d] = true;
      }
    }
    $ok(['dates'=>array_keys($dates)]);
  }

  if($action === 'add'){
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '09:00';
    $note = trim($_POST['note'] ?? '');

    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $fail('Tanggal tidak valid');
    if(!preg_match('/^\d{2}:\d{2}$/', $time)) $fail('Jam tidak valid');
    if($note === '') $fail('Catatan wajib diisi');

    if(mb_strlen($note) > 220) $note = mb_substr($note, 0, 220);

    $id = (string)(time() . mt_rand(1000,9999));

    $list[] = [
      'id'   => $id,
      'date' => $date,
      'time' => $time,
      'note' => $note,
    ];

    $all[$userKey] = $list;

    if(!write_json_file_atomic($dataFile, $all)){
      $fail('Gagal menyimpan (folder tidak bisa ditulis). Cek permission hosting.', 500);
    }

    $ok(['id'=>$id]);
  }

  if($action === 'delete'){
    $id = $_POST['id'] ?? '';
    if($id==='') $fail('ID kosong');

    $list = array_values(array_filter($list, function($x) use ($id){
      return isset($x['id']) && $x['id'] !== $id;
    }));

    $all[$userKey] = $list;

    if(!write_json_file_atomic($dataFile, $all)){
      $fail('Gagal menghapus (folder tidak bisa ditulis).', 500);
    }

    $ok([]);
  }

  if($action === 'clear'){
    $all[$userKey] = [];
    if(!write_json_file_atomic($dataFile, $all)){
      $fail('Gagal hapus semua (folder tidak bisa ditulis).', 500);
    }
    $ok([]);
  }

  $fail('Action tidak dikenal');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
      :root{
        --bg:#f4f7ff;
        --text:#0f172a;
        --muted:#64748b;
        --brand:#4f46e5;
        --brand2:#2563eb;
        --shadow:0 14px 40px rgba(2,6,23,.08);
        --radius:20px;
      }

      body.admin-body{
        background: radial-gradient(1200px 600px at 20% -10%, rgba(79,70,229,.18), transparent 55%),
                    radial-gradient(900px 500px at 100% 10%, rgba(37,99,235,.12), transparent 55%),
                    var(--bg) !important;
        color: var(--text);
      }
      .content{ padding: 22px 22px 34px !important; }

      .dash-head{
        background: linear-gradient(135deg, rgba(79,70,229,.95), rgba(37,99,235,.92));
        color:#fff;
        border-radius: 22px;
        padding: 18px 18px;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
        margin-bottom: 18px;
      }
      .dash-head:after{
        content:"";
        position:absolute;
        inset:-120px -120px auto auto;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.22), transparent 60%);
        transform: rotate(18deg);
      }
      .dash-title{ color:#fff !important; margin: 6px 0 2px !important; }
      .dash-sub{ color: rgba(255,255,255,.88) !important; }

      .stat-card{
        border-radius: var(--radius) !important;
        box-shadow: var(--shadow) !important;
        border: 1px solid rgba(255,255,255,.7);
        background: rgba(255,255,255,.92) !important;
        backdrop-filter: blur(10px);
      }
      .stat-label{ color: var(--muted) !important; font-weight: 800; }
      .stat-value{ color: var(--text) !important; }
      .stat-foot{ color: var(--muted) !important; }
      .stat-foot b{ color: var(--text) !important; }

      .dash-panels{
        display:grid;
        grid-template-columns: 1.35fr .65fr;
        gap:16px;
        align-items:start;
        margin-top: 14px;
      }
      @media(max-width: 980px){
        .dash-panels{ grid-template-columns: 1fr; }
      }

      /* ===== STATS SELEKSI (PENDING) ===== */
      .stage-grid{
        display:grid;
        grid-template-columns: repeat(5, 1fr);
        gap:16px;
        margin-top: 16px;
      }
      @media(max-width: 1180px){
        .stage-grid{ grid-template-columns: repeat(3, 1fr); }
      }
      @media(max-width: 720px){
        .stage-grid{ grid-template-columns: repeat(2, 1fr); }
      }
      @media(max-width: 420px){
        .stage-grid{ grid-template-columns: 1fr; }
      }
      .stage-card .stat-value{ font-size: 38px; }
      .stage-card .stat-label{ letter-spacing: .6px; text-transform: uppercase; }
      .stage-chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        margin-top:10px;
        padding:6px 10px;
        border-radius:999px;
        font-weight: 900;
        font-size:12px;
        border:1px solid rgba(2,6,23,.08);
        background: rgba(255,255,255,.7);
      }
      .stage-dot{ width:10px; height:10px; border-radius:999px; background: var(--brand); }

      /* Chart */
      .chart-card{
        background:#fff;
        border-radius: var(--radius);
        padding:20px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(2,6,23,.05);
      }
      .chart-head{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:14px;
      }
      .chart-head h2{
        margin:0;
        font-size:18px;
        color:#0f172a;
        font-weight: 900;
      }
      .chart-sub{ font-size:13px; color: var(--muted); }
      .chart-wrap{ height:420px; }

      /* Calendar */
      .cal-card{
        background: rgba(255,255,255,.92);
        border: 1px solid rgba(2,6,23,.05);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 16px;
      }
      .cal-head{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        margin-bottom: 10px;
      }
      .cal-title{
        font-weight: 950;
        color: var(--text);
        font-size: 16px;
      }
      .cal-nav{ display:flex; gap:8px; }
      .cal-btn{
        width:36px;height:36px;
        border-radius:12px;
        border:1px solid rgba(2,6,23,.08);
        background:#fff;
        cursor:pointer;
        font-weight: 900;
        color: var(--text);
        box-shadow: 0 10px 18px rgba(2,6,23,.06);
      }
      .cal-grid{
        display:grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        margin-top: 10px;
      }
      .cal-dow{
        font-size:11px;
        color: var(--muted);
        font-weight: 800;
        text-align:center;
      }
      .cal-day{
        border:1px solid rgba(2,6,23,.06);
        background:#fff;
        border-radius: 14px;
        min-height: 44px;
        padding: 8px 8px 6px;
        position:relative;
        cursor:pointer;
        box-shadow: 0 12px 18px rgba(2,6,23,.05);
        transition: transform .12s ease;
      }
      .cal-day:hover{ transform: translateY(-1px); }
      .cal-day.muted{ opacity:.35; cursor: default; }
      .cal-num{ font-weight: 900; font-size: 13px; color: var(--text); }
      .cal-dot{
        position:absolute;
        left:10px;
        bottom:8px;
        width:7px;height:7px;
        border-radius:999px;
        background: var(--brand);
      }
      .cal-day.today{
        outline: 2px solid rgba(79,70,229,.35);
        background: rgba(79,70,229,.06);
      }
      .cal-hint{ margin-top:10px; font-size:12px; color: var(--muted); }

      /* Reminder form */
      .rem-box{
        margin-top: 14px;
        border-top: 1px solid rgba(2,6,23,.06);
        padding-top: 14px;
      }
      .rem-title h3{ margin:0; font-size:14px; font-weight:950; color:var(--text); }
      .rem-form{ display:grid; gap:10px; margin-top: 10px; }
      .rem-row{ display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
      @media(max-width: 980px){ .rem-row{ grid-template-columns:1fr; } }
      .rem-input, .rem-text{
        border: 1px solid rgba(2,6,23,.10);
        background:#fff;
        border-radius: 14px;
        padding: 10px 12px;
        font-size: 13px;
        outline: none;
      }
      .rem-actions{ display:flex; gap:10px; align-items:center; }
      .rem-save{
        background: linear-gradient(135deg, rgba(79,70,229,.98), rgba(37,99,235,.95));
        color:#fff;
        border:none;
        border-radius: 14px;
        padding: 10px 14px;
        font-weight: 900;
        cursor:pointer;
        box-shadow: 0 14px 30px rgba(79,70,229,.25);
      }
      .rem-clear{
        background:#fff;
        color: var(--text);
        border:1px solid rgba(2,6,23,.10);
        border-radius: 14px;
        padding: 10px 14px;
        font-weight: 900;
        cursor:pointer;
      }

      /* ================= MODAL (SCROLLABLE) ================= */
      .modal{ position:fixed; inset:0; display:none; z-index:9999; }
      .modal.show{ display:block; }
      .modal-backdrop{ position:absolute; inset:0; background:rgba(2,6,23,.55); backdrop-filter: blur(3px); }

      .modal-card{
        position:relative;
        width:min(560px,92vw);
        margin:7vh auto 0;
        background:#fff;
        border-radius: 20px;
        box-shadow: 0 28px 70px rgba(2,6,23,.35);
        overflow:hidden;
        max-height: 86vh;
        display:flex;
        flex-direction:column;
      }

      .modal-head{
        padding: 14px 16px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        border-bottom:1px solid rgba(2,6,23,.08);
        background: linear-gradient(135deg, rgba(79,70,229,.12), rgba(37,99,235,.10));
        flex: 0 0 auto;
      }
      .modal-head b{ font-size:15px; font-weight:950; color:var(--text); }
      .modal-close{
        width:36px;height:36px;
        border-radius:12px;
        border:1px solid rgba(2,6,23,.10);
        background:#fff;
        cursor:pointer;
        font-weight:900;
      }

      .modal-body{
        padding: 14px 16px 16px;
        overflow:auto;
        flex: 1 1 auto;
      }

      .modal-empty{ color: var(--muted); font-size: 13px; padding: 8px 2px; }
      .modal-list{ display:flex; flex-direction:column; gap:10px; margin-top:10px; }

      .ev-item{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:14px;
        border:1px solid rgba(2,6,23,.06);
        background:#fff;
        border-radius:16px;
        padding: 12px 14px;
        box-shadow: 0 12px 18px rgba(2,6,23,.05);
      }
      .ev-meta{
        display:flex;
        flex-direction:column;
        gap:6px;
        flex:1 1 auto;
        min-width:0;
      }
      .ev-when{
        font-size:18px;
        font-weight:950;
        color:var(--text);
        line-height:1.1;
      }
      .ev-note{
        font-size:14px;
        font-weight:800;
        color: rgba(15,23,42,.78);
        white-space: normal;
        overflow: hidden;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
        line-clamp: 3;
        word-break: break-word;
      }
      .ev-del{
        flex:0 0 auto !important;
        width:auto !important;
        display:inline-flex !important;
        align-items:center;
        justify-content:center;
        border: 1px solid rgba(239,68,68,.25);
        background: rgba(239,68,68,.08);
        color: #ef4444;
        border-radius: 12px;
        padding: 8px 12px;
        height: 36px;
        font-weight: 900;
        font-size: 12px;
        cursor:pointer;
        white-space: nowrap;
        margin-top: 2px;
      }
      .ev-del:hover{ background: rgba(239,68,68,.12); }

      /* ===== TOAST PERSISTENT ===== */
      .toast-wrap{
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 99999;
        display:flex;
        flex-direction:column;
        gap:10px;
        pointer-events:none;
      }
      .toast{
        pointer-events:auto;
        width:min(380px, 92vw);
        background:#0b1220;
        color:#fff;
        border:1px solid rgba(255,255,255,.14);
        border-radius: 16px;
        padding: 12px 12px;
        box-shadow: 0 22px 50px rgba(2,6,23,.28);
        overflow:hidden;
      }
      .toast-top{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
      }
      .toast b{ font-weight: 950; }
      .toast small{ color: rgba(255,255,255,.78); font-weight: 700; }
      .toast .x{
        border:none;
        background: rgba(255,255,255,.10);
        color:#fff;
        font-weight: 900;
        border-radius: 10px;
        width:34px;
        height:34px;
        cursor:pointer;
      }
      .toast .msg{
        margin-top: 6px;
        font-size: 13px;
        font-weight: 800;
        color: rgba(255,255,255,.92);
        word-break: break-word;
      }
      .toast-actions{
        margin-top: 10px;
        display:flex;
        justify-content:flex-end;
        gap:8px;
      }
      .toast-btn{
        border:none;
        border-radius: 12px;
        padding:9px 12px;
        cursor:pointer;
        font-weight: 950;
        font-size: 12px;
      }
      .toast-btn.primary{
        background: rgba(255,255,255,.92);
        color:#0f172a;
      }
      .toast-btn.secondary{
        background: rgba(255,255,255,.10);
        color:#fff;
        border:1px solid rgba(255,255,255,.18);
      }
    </style>
</head>
<body class="admin-body">

<?php include 'sidebar.php'; ?>

<div class="content">

    <div class="dash-head">
        <div class="dash-head-left">
            <h1 class="dash-title">Dashboard</h1>
            <div class="dash-sub">
                Selamat datang, <b><?= ucfirst($_SESSION['role']); ?></b> • <?= date('d M Y'); ?>
            </div>
        </div>
    </div>

    <div class="dash-grid">
        <div class="stat-card stat-red">
            <div class="stat-top">
                <div class="stat-icon">📌</div>
                <div class="stat-meta">
                    <div class="stat-label">Total Lowongan</div>
                    <div class="stat-value"><?= $totalLow; ?></div>
                </div>
            </div>
            <div class="stat-foot">Data lowongan di sistem.</div>
        </div>

        <div class="stat-card stat-blue">
            <div class="stat-top">
                <div class="stat-icon">👥</div>
                <div class="stat-meta">
                    <div class="stat-label">Total Pelamar</div>
                    <div class="stat-value"><?= $totalPel; ?></div>
                </div>
            </div>
            <div class="stat-foot">
                Hari ini: <b><?= $pelHariIni; ?></b> • Bulan ini: <b><?= $pelBulanIni; ?></b>
            </div>
        </div>
    </div>

    

  <!-- STATS SELEKSI (pending aksi) -->
  <div class="stage-grid">
    <div class="stat-card stage-card">
      <div class="stat-top">
        <div class="stat-icon">📞</div>
        <div class="stat-meta">
          <div class="stat-label">Initiate Call</div>
          <div class="stat-value"><?php echo (int)$pendingSel['Initiate Call']; ?></div>
        </div>
      </div>
      <div class="stat-foot">Sedang Menunggu</div>
    </div>
    <div class="stat-card stage-card">
      <div class="stat-top">
        <div class="stat-icon">🎙️</div>
        <div class="stat-meta">
          <div class="stat-label">Interview</div>
          <div class="stat-value"><?php echo (int)$pendingSel['Interview HRD & User']; ?></div>
        </div>
      </div>
      <div class="stat-foot">Sedang Menunggu</div>
    </div>
    <div class="stat-card stage-card">
      <div class="stat-top">
        <div class="stat-icon">🧠</div>
        <div class="stat-meta">
          <div class="stat-label">Psikotest</div>
          <div class="stat-value"><?php echo (int)$pendingSel['Psikotest']; ?></div>
        </div>
      </div>
      <div class="stat-foot">Sedang Menunggu</div>
    </div>
    <div class="stat-card stage-card">
      <div class="stat-top">
        <div class="stat-icon">🏥</div>
        <div class="stat-meta">
          <div class="stat-label">MCU</div>
          <div class="stat-value"><?php echo (int)$pendingSel['MCU']; ?></div>
        </div>
      </div>
      <div class="stat-foot">Sedang Menunggu</div>
    </div>
    <div class="stat-card stage-card">
      <div class="stat-top">
        <div class="stat-icon">✅</div>
        <div class="stat-meta">
          <div class="stat-label">Onboarding</div>
          <div class="stat-value"><?php echo (int)$pendingSel['Onboarding']; ?></div>
        </div>
      </div>
      <div class="stat-foot">Sedang Menunggu</div>
    </div>
  </div>
<div class="dash-panels">

      <div class="chart-card">
          <div class="chart-head">
              <h2>Grafik Pelamar per Posisi</h2>
              <span class="chart-sub">Jumlah pelamar berdasarkan posisi</span>
          </div>
          <div class="chart-wrap">
              <canvas id="pelamarChart"></canvas>
          </div>
      </div>

      <div class="cal-card">
        <div class="cal-head">
          <div class="cal-title" id="calTitle">Calendar</div>
          <div class="cal-nav">
            <button class="cal-btn" type="button" id="calPrev">‹</button>
            <button class="cal-btn" type="button" id="calNext">›</button>
          </div>
        </div>

        <div class="cal-grid" id="calDow"></div>
        <div class="cal-grid" id="calDays"></div>
        <div class="cal-hint">Klik tanggal untuk lihat acara / hapus acara.</div>

        <div class="rem-box">
          <div class="rem-title"><h3>Tambah Pengingat</h3></div>

          <div class="rem-form">
            <div class="rem-row">
              <input class="rem-input" type="date" id="remDate" />
              <input class="rem-input" type="time" id="remTime" value="09:00" />
            </div>
            <input class="rem-text" type="text" id="remNote" placeholder="Contoh: Interview kandidat A jam 10" />
            <div class="rem-actions">
              <button class="rem-save" type="button" id="remSave">Simpan</button>
              <button class="rem-clear" type="button" id="remClear">Hapus semua</button>
            </div>
          </div>
        </div>
      </div>

    </div>

</div>

<!-- MODAL -->
<div class="modal" id="evModal" aria-hidden="true">
  <div class="modal-backdrop" id="evBackdrop"></div>
  <div class="modal-card">
    <div class="modal-head">
      <b id="evTitle">Acara</b>
      <button class="modal-close" type="button" id="evClose">✕</button>
    </div>
    <div class="modal-body">
      <div class="modal-empty" id="evEmpty" style="display:none;">Belum ada acara di tanggal ini.</div>
      <div class="modal-list" id="evList"></div>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast-wrap" id="toastWrap"></div>

<script>
/* ===== Chart ===== */
const labels = <?= json_encode($labels); ?>;
const values = <?= json_encode($values); ?>;
const ctx = document.getElementById('pelamarChart');

if(!labels.length){
  ctx.parentElement.innerHTML = '<div style="padding:18px;color:#64748b;">Belum ada data pelamar.</div>';
}else{
  new Chart(ctx, {
    type: 'bar',
    data: { labels: labels, datasets: [{ data: values, backgroundColor: 'rgba(37,99,235,.85)', borderRadius: 10, maxBarThickness: 46 }] },
    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, scales:{ y:{beginAtZero:true} } }
  });
}

/* ==========================================================
   BELL SOUND (gantikan suara kalender)
   - Bunyi "tring" (bell) kenceng
   - Standby sampai user klik Tutup/X
   - Browser butuh gesture user 1x untuk unlock audio
========================================================== */
const BELL = (()=>{
  let ctx=null;
  let unlocked=false;

  function ensure(){
    if(ctx) return;
    const AC = window.AudioContext || window.webkitAudioContext;
    if(!AC) return;
    ctx = new AC();
  }

  function unlock(){
    try{
      ensure();
      if(!ctx) return;
      if(ctx.state === 'suspended') ctx.resume();
      unlocked = true;
    }catch(e){}
  }

  function ringOnce(){
    try{
      ensure();
      if(!ctx) return;
      if(ctx.state === 'suspended') return;

      // Master chain (biar kenceng tapi ga pecah)
      const comp = ctx.createDynamicsCompressor();
      comp.threshold.value = -20;
      comp.knee.value = 25;
      comp.ratio.value = 10;
      comp.attack.value = 0.003;
      comp.release.value = 0.25;

      const master = ctx.createGain();
      master.gain.value = 1.0; // volume utama

      comp.connect(master);
      master.connect(ctx.destination);

      const now = ctx.currentTime;

      function bellTone(freq, startGain, decay){
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.type = 'sine';
        o.frequency.value = freq;
        g.gain.setValueAtTime(startGain, now);
        g.gain.exponentialRampToValueAtTime(0.0001, now + decay);
        o.connect(g);
        g.connect(comp);
        o.start(now);
        o.stop(now + decay + 0.05);
      }

      // Kombinasi partial biar berasa "bell/tring"
      bellTone(1568, 0.85, 1.20);
      bellTone( 784, 0.55, 1.45);
      bellTone(2352, 0.35, 0.95);

    }catch(e){}
  }

  return {
    unlock,
    ringOnce,
    get unlocked(){ return unlocked; }
  };
})();

// unlock saat user klik/keydown pertama kali
document.addEventListener('click', ()=>{ BELL.unlock(); }, { once:true });
document.addEventListener('keydown', ()=>{ BELL.unlock(); }, { once:true });
/* ==========================================================
   TOAST PERSISTENT (STANDBY SAMPE DI CLOSE)
   - Active toast disimpan ke localStorage
   - Refresh / balik dashboard -> toast muncul lagi
========================================================== */
function ymdNow(){
  const d = new Date();
  const pad = (n)=> String(n).padStart(2,'0');
  return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
}
function storageKeyActive(){ return 'REM_ACTIVE_TOASTS_' + ymdNow(); }
function storageKeyDismissed(){ return 'REM_DISMISSED_TOASTS_' + ymdNow(); }

function loadJSON(key, fallback){
  try{
    const v = localStorage.getItem(key);
    if(!v) return fallback;
    const j = JSON.parse(v);
    return (j && typeof j === 'object') ? j : fallback;
  }catch(e){ return fallback; }
}
function saveJSON(key, obj){
  try{ localStorage.setItem(key, JSON.stringify(obj)); }catch(e){}
}

function isDismissed(toastId){
  const dis = loadJSON(storageKeyDismissed(), {});
  return !!dis[toastId];
}
function markDismissed(toastId){
  const dis = loadJSON(storageKeyDismissed(), {});
  dis[toastId] = 1;
  saveJSON(storageKeyDismissed(), dis);

  // remove from active too
  const act = loadJSON(storageKeyActive(), {});
  if(act[toastId]){
    delete act[toastId];
    saveJSON(storageKeyActive(), act);
  }
}

function addActiveToast(payload){
  // payload: {id,title,subtitle,msg,dateStr}
  if(!payload || !payload.id) return;
  if(isDismissed(payload.id)) return;

  const act = loadJSON(storageKeyActive(), {});
  act[payload.id] = payload;
  saveJSON(storageKeyActive(), act);
}

function removeActiveToast(toastId){
  const act = loadJSON(storageKeyActive(), {});
  if(act[toastId]){
    delete act[toastId];
    saveJSON(storageKeyActive(), act);
  }
}

function renderToast(payload){
  if(!payload || !payload.id) return;
  if(isDismissed(payload.id)) return;

  const wrap = document.getElementById('toastWrap');
  if(!wrap) return;

  // kalau udah ada di DOM, skip
  if(document.querySelector('.toast[data-id="'+payload.id+'"]')) return;

  const el = document.createElement('div');
  el.className = 'toast';
  el.setAttribute('data-id', payload.id);

  const title = payload.title || 'Pengingat';
  const subtitle = payload.subtitle || '';
  const msg = payload.msg || '';
  const dateStr = payload.dateStr || null;

  el.innerHTML = `
    <div class="toast-top">
      <div>
        <b>${title}</b><br>
        <small>${subtitle}</small>
      </div>
      <button class="x" type="button">✕</button>
    </div>
    <div class="msg">${msg}</div>
    <div class="toast-actions">
      ${dateStr ? `<button class="toast-btn secondary" type="button" data-act="open">Lihat</button>` : ``}
      <button class="toast-btn primary" type="button" data-act="close">Tutup</button>
    </div>
  `;

  /* ==========================
     BELL 1 MENIT (LONCENG)
     - bunyi "tring" kenceng
     - standby sampai di-close
  ========================== */
  BELL.ring(1200);
  const bellEndAt = Date.now() + 60000;
  let bellTimer = setInterval(()=>{
    if(Date.now() >= bellEndAt){
      clearInterval(bellTimer);
      return;
    }
    BELL.ring(1200);
  }, 1700);

  const stopBell = ()=>{
    try{ clearInterval(bellTimer); }catch(e){}
    BELL.stop();
  };

  const closeAll = ()=>{
    // user minta: hilang hanya kalau klik X/close -> berarti saat klik, kita "dismiss"
    stopBell();
    markDismissed(payload.id);
    el.remove();
  };

  el.querySelector('.x').addEventListener('click', closeAll);
  el.querySelector('[data-act="close"]').addEventListener('click', closeAll);

  const openBtn = el.querySelector('[data-act="open"]');
  if(openBtn && dateStr){
    openBtn.addEventListener('click', async ()=>{
      try{
        await openModal(dateStr);
      }catch(e){
        alert(e.message || 'Gagal buka detail.');
      }
    });
  }

  wrap.appendChild(el);
}

function rebuildToastsFromStorage(){
  const act = loadJSON(storageKeyActive(), {});
  Object.keys(act).forEach(id=>{
    if(isDismissed(id)) return;
    renderToast(act[id]);
  });
}

/* ===== Calendar + Reminder (SERVER JSON, TANPA DB) ===== */
(function(){
  const DOW = ['MON','TUE','WED','THU','FRI','SAT','SUN'];
  const elTitle = document.getElementById('calTitle');
  const elDow   = document.getElementById('calDow');
  const elDays  = document.getElementById('calDays');
  const btnPrev = document.getElementById('calPrev');
  const btnNext = document.getElementById('calNext');

  const remDate = document.getElementById('remDate');
  const remTime = document.getElementById('remTime');
  const remNote = document.getElementById('remNote');
  const remSave = document.getElementById('remSave');
  const remClear= document.getElementById('remClear');

  const evModal = document.getElementById('evModal');
  const evBackdrop = document.getElementById('evBackdrop');
  const evClose = document.getElementById('evClose');
  const evTitle = document.getElementById('evTitle');
  const evEmpty = document.getElementById('evEmpty');
  const evList  = document.getElementById('evList');

  let activeDate = null;

  function pad(n){ return String(n).padStart(2,'0'); }
  function ymd(d){ return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate()); }
  function hmToMin(hm){
    const m = /^(\d{2}):(\d{2})$/.exec(hm||'');
    if(!m) return null;
    return (parseInt(m[1],10)*60) + parseInt(m[2],10);
  }
  function niceDateIndo(dateStr){
    try{
      const [y,m,d] = dateStr.split('-').map(x=>parseInt(x,10));
      const dt = new Date(y, (m-1), d);
      return dt.toLocaleDateString('id-ID', { weekday:'short', day:'2-digit', month:'short', year:'numeric' });
    }catch(e){
      return dateStr;
    }
  }

  async function api(params, method='GET', body=null){
    const url = new URL(window.location.href);
    url.searchParams.set('ajax','reminder');
    Object.keys(params||{}).forEach(k=> url.searchParams.set(k, params[k]));
    const opt = { method };
    if(method === 'POST'){
      opt.headers = {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'};
      opt.body = new URLSearchParams(body||{}).toString();
    }
    const res = await fetch(url.toString(), opt);
    const js = await res.json().catch(()=>null);
    if(!js || !js.ok){
      const msg = (js && js.error) ? js.error : 'Terjadi kesalahan.';
      throw new Error(msg);
    }
    return js;
  }

  let monthDots = new Set();

  async function loadMonthDots(y,m){
    const ym = `${y}-${pad(m+1)}`;
    const js = await api({action:'month', ym});
    monthDots = new Set(js.dates || []);
  }

  async function getByDate(dateStr){
    const js = await api({action:'list', date:dateStr});
    return (js.items || []).sort((a,b)=> (a.time||'').localeCompare(b.time||''));
  }

  function hasDot(dateStr){
    return monthDots.has(dateStr);
  }

  async function openModal(dateStr){
    activeDate = dateStr;
    evTitle.textContent = 'Acara: ' + dateStr;

    const items = await getByDate(dateStr);

    if(!items.length){
      evEmpty.style.display='block';
      evList.innerHTML='';
    }else{
      evEmpty.style.display='none';
      evList.innerHTML = items.map(x=>{
        const note = (x.note || '').trim() || '(Tanpa judul)';
        const safeTitle = note.replaceAll('"','&quot;');
        return `
          <div class="ev-item">
            <div class="ev-meta">
              <div class="ev-when">${x.time || '00:00'}</div>
              <div class="ev-note" title="${safeTitle}">${note}</div>
            </div>
            <button class="ev-del" type="button" data-id="${x.id}">Hapus</button>
          </div>
        `;
      }).join('');

      evList.querySelectorAll('.ev-del').forEach(btn=>{
        btn.addEventListener('click', async ()=>{
          const id = btn.getAttribute('data-id');
          try{
            await api({action:'delete'}, 'POST', {id});
            await loadMonthDots(viewYear, viewMonth);
            await drawCalendar(viewYear, viewMonth);
            await openModal(dateStr);
          }catch(err){
            alert(err.message);
          }
        });
      });
    }

    evModal.classList.add('show');
  }

  window.openModal = openModal;

  function closeModal(){ evModal.classList.remove('show'); }

  evBackdrop.addEventListener('click', closeModal);
  evClose.addEventListener('click', closeModal);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && evModal.classList.contains('show')) closeModal(); });

  elDow.innerHTML = DOW.map(d=>`<div class="cal-dow">${d}</div>`).join('');

  const today = new Date();
  let viewYear  = today.getFullYear();
  let viewMonth = today.getMonth();

  function monthName(y,m){ return new Date(y,m,1).toLocaleString('en-US',{month:'long', year:'numeric'}); }
  function firstDayIndex(y,m){ const js=new Date(y,m,1).getDay(); return (js===0)?6:js-1; }
  function daysInMonth(y,m){ return new Date(y, m+1, 0).getDate(); }

  async function drawCalendar(y,m){
    elTitle.textContent = monthName(y,m);
    const firstIdx = firstDayIndex(y,m);
    const dim = daysInMonth(y,m);

    const prevM = (m-1+12)%12;
    const prevY = m===0 ? y-1 : y;
    const prevDim = daysInMonth(prevY, prevM);

    let cells=[];
    for(let i=0;i<firstIdx;i++){
      const dayNum = prevDim - (firstIdx-1-i);
      cells.push({num:dayNum, muted:true, dateStr:null});
    }
    for(let d=1; d<=dim; d++){
      const dateObj = new Date(y,m,d);
      const ds = ymd(dateObj);
      cells.push({num:d, muted:false, today:(ds===ymd(today)), dot:hasDot(ds), dateStr:ds});
    }
    while(cells.length%7!==0) cells.push({num: (cells.length-(firstIdx+dim)+1), muted:true, dateStr:null});
    while(cells.length<42) cells.push({num: (cells.length-(firstIdx+dim)+1), muted:true, dateStr:null});

    elDays.innerHTML = cells.map(c=>{
      const cls=['cal-day', c.muted?'muted':'', c.today?'today':''].join(' ').trim();
      return `
        <div class="${cls}" ${c.dateStr?`data-date="${c.dateStr}"`:''}>
          <div class="cal-num">${c.num}</div>
          ${c.dot?`<span class="cal-dot"></span>`:''}
        </div>
      `;
    }).join('');

    elDays.querySelectorAll('.cal-day').forEach(day=>{
      day.addEventListener('click', async ()=>{
        const ds = day.getAttribute('data-date');
        if(!ds) return;
        remDate.value = ds;
        try{
          await openModal(ds);
        }catch(err){
          alert(err.message);
        }
      });
    });
  }

  btnPrev.addEventListener('click', async ()=>{
    viewMonth--;
    if(viewMonth<0){viewMonth=11; viewYear--;}
    try{
      await loadMonthDots(viewYear, viewMonth);
      await drawCalendar(viewYear, viewMonth);
    }catch(err){ alert(err.message); }
  });

  btnNext.addEventListener('click', async ()=>{
    viewMonth++;
    if(viewMonth>11){viewMonth=0; viewYear++;}
    try{
      await loadMonthDots(viewYear, viewMonth);
      await drawCalendar(viewYear, viewMonth);
    }catch(err){ alert(err.message); }
  });

  remSave.addEventListener('click', async ()=>{
    const d = remDate.value || ymd(new Date(viewYear, viewMonth, 1));
    const t = remTime.value || '09:00';
    const note = (remNote.value || '').trim();
    if(!note){ alert('Isi dulu catatan pengingatnya.'); remNote.focus(); return; }

    try{
      await api({action:'add'}, 'POST', {date:d, time:t, note:note});
      remNote.value='';
      await loadMonthDots(viewYear, viewMonth);
      await drawCalendar(viewYear, viewMonth);
      await openModal(d);
    }catch(err){
      alert(err.message);
    }
  });

  remClear.addEventListener('click', async ()=>{
    if(!confirm('Hapus semua pengingat?')) return;
    try{
      await api({action:'clear'}, 'POST', {});
      await loadMonthDots(viewYear, viewMonth);
      await drawCalendar(viewYear, viewMonth);
      if(evModal.classList.contains('show') && activeDate) await openModal(activeDate);
    }catch(err){
      alert(err.message);
    }
  });

  /* ===== NOTIFIKASI: 1 JAM SEBELUM + PAS JAMNYA
     - disimpan ACTIVE -> gak hilang sampai di close
  */
  function firedKey(){ return 'REM_FIRED_' + ymd(new Date()); }
  function loadFired(){
    try{ return JSON.parse(localStorage.getItem(firedKey()) || '{}') || {}; }catch(e){ return {}; }
  }
  function saveFired(obj){
    try{ localStorage.setItem(firedKey(), JSON.stringify(obj||{})); }catch(e){}
  }

  async function checkNotifications(){
    const ds = ymd(new Date());
    let items = [];
    try{
      items = await getByDate(ds);
    }catch(e){
      return;
    }
    if(!items.length) return;

    const fired = loadFired();
    const now = new Date();
    const nowM = now.getHours()*60 + now.getMinutes();

    items.forEach(it=>{
      const baseId = it.id || (it.date+'_'+it.time+'_'+it.note);
      const tmin = hmToMin(it.time || '');
      if(tmin === null) return;

      const note = (it.note || '').trim() || '(Tanpa catatan)';
      const sub  = (it.time || '--:--') + ' • ' + niceDateIndo(ds);

      // 1 jam sebelum
      const preMin = tmin - 60;
      if(preMin >= 0 && nowM === preMin){
        const k = baseId + '_pre';
        if(!fired[k]){
          fired[k] = 1;

          const toastId = 'toast_' + k;
          const payload = {
            id: toastId,
            title: 'Pengingat (1 jam lagi)',
            subtitle: sub,
            msg: note,
            dateStr: ds
          };
          addActiveToast(payload);
          renderToast(payload);
        }
      }

      // pas jamnya
      if(nowM === tmin){
        const k = baseId + '_on';
        if(!fired[k]){
          fired[k] = 1;

          const toastId = 'toast_' + k;
          const payload = {
            id: toastId,
            title: 'Pengingat Sekarang',
            subtitle: sub,
            msg: note,
            dateStr: ds
          };
          addActiveToast(payload);
          renderToast(payload);
        }
      }
    });

    saveFired(fired);
  }

  // init
  (async ()=>{
    remDate.value = ymd(today);
    try{
      await loadMonthDots(viewYear, viewMonth);
      await drawCalendar(viewYear, viewMonth);

      // bangunin toast yang masih active (STANDBY) pas load/refresh
      rebuildToastsFromStorage();

      // cek langsung saat login/refresh (kalau tepat waktunya)
      await checkNotifications();

      // cek tiap 30 detik
      setInterval(checkNotifications, 30000);
    }catch(err){
      console.error(err);
    }
  })();

})();
</script>

</body>
</html>
