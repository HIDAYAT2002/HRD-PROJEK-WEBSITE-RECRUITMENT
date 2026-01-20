<?php
include 'config/koneksi.php';

$posisi = isset($_GET['posisi']) ? trim($_GET['posisi']) : '';
$kota   = isset($_GET['kota']) ? trim($_GET['kota']) : '';

// amankan input
$posisi_safe = mysqli_real_escape_string($conn, $posisi);
$kota_safe   = mysqli_real_escape_string($conn, $kota);

/* FILTER:
   - hanya yang status = 'aktif'
   - deadline belum lewat (atau deadline kosong)
*/
$where = "WHERE status='aktif' AND (deadline IS NULL OR deadline >= CURDATE())";

if($posisi_safe !== ''){
    $where .= " AND posisi LIKE '%$posisi_safe%'";
}
if($kota_safe !== ''){
    $where .= " AND kota LIKE '%$kota_safe%'";
}

$data = mysqli_query($conn, "
    SELECT * FROM lowongan 
    $where 
    ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Karier WGI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/style.css">

    <style>
      /* FOOTER CLEAN (NAVY) */
      .footer-clean{
        background: linear-gradient(180deg, #020617, #050b18);
        margin-top: 60px;
        border-top: 1px solid rgba(255,255,255,.06);
      }
      .footer-inner{
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        text-align: center;
        color: rgba(255,255,255,.7);
        font-size: 13px;
        letter-spacing: .4px;
      }

      /* LANG SWITCH */
      .lang-switch{
        display:flex;
        gap:8px;
        align-items:center;
        font-weight:900;
        font-size:13px;
      }
      .lang-switch a{
        color:#64748b;
        text-decoration:none;
        cursor:pointer;
      }
      .lang-switch a.active{
        color:#0f172a;
        text-decoration:underline;
      }

      /* HIDE GOOGLE TRANSLATE UI */
      .goog-te-banner-frame.skiptranslate { display:none !important; }
      body { top:0px !important; }
      .goog-logo-link, .goog-te-gadget { display:none !important; }
      #google_translate_element{ display:none !important; }
    </style>
</head>
<body>

<!-- hidden translate element -->
<div id="google_translate_element"></div>

<!-- TOP BAR -->
<header class="topbar">
    <div class="brand">
        <img src="assets/img/logo-wgi.jpg" alt="WGI">
    </div>

    <div style="display:flex; gap:16px; align-items:center;">
      <div class="lang-switch">
        <a id="btnID" class="active" onclick="setLang('id');return false;">IND</a> |
        <a id="btnEN" onclick="setLang('en');return false;">ENG</a>
      </div>
      <a href="auth/login.php" class="btn-login">Login</a>
    </div>
</header>

<div class="running-text">
  <div class="running-track">
    <span>
      Selamat datang di Karier PT Wiraswasta Gemilang Indonesia — 
      Wujudkan karier impianmu bersama tim profesional & lingkungan kerja yang berkembang 🚀
    </span>
  </div>
</div>

<!-- HERO -->
<section class="hero"
    style="
        position: relative;
        overflow: hidden;
        background: 
            linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
            url('assets/img/hero.jpg') center / cover no-repeat;
        min-height: 360px;
        max-height: 420px;
        padding: 60px 20px;
        color: #fff;
        border-radius: 16px;
    "
>
    <h1>RECRUITMENT PT WIRASWASTA GEMILANG INDONESIA</h1>
    <p>Temukan posisi terbaik sesuai kemampuanmu</p>

    <form class="search-box" method="GET">
        <input type="text" name="posisi" placeholder="Cari posisi..." value="<?= htmlspecialchars($posisi); ?>">
        <input type="text" name="kota" placeholder="Kota / Kabupaten" value="<?= htmlspecialchars($kota); ?>">
        <button type="submit">Cari Lowongan</button>
    </form>
</section>

<!-- LOWONGAN -->
<section class="lowongan-section">
    <h2 class="section-title">Lowongan Tersedia</h2>

    <div class="lowongan-grid">

        <?php if(mysqli_num_rows($data)==0){ ?>
            <p class="empty">Belum ada lowongan tersedia</p>
        <?php } ?>

        <?php while($l=mysqli_fetch_assoc($data)){ ?>
        <div class="job-card">

            <div class="job-header">
                <h3><?= htmlspecialchars($l['posisi']); ?></h3>
                <span class="badge">Full Time</span>
            </div>

            <!-- META (deadline DIHAPUS dari sini) -->
            <div class="job-meta">
              <span>📍 <?= htmlspecialchars($l['kota']); ?></span>

              <?php if(!empty($l['created_at'])): ?>
                <span>📌 <?= date('d M Y', strtotime($l['created_at'])); ?></span>
              <?php endif; ?>
            </div>

            <!-- JOB DESK & KRITERIA -->
            <div class="job-info">

                <div class="job-block">
                    <div class="job-block-head">
                        <span class="job-block-title">Job Desc</span>
                        <label for="desk<?= (int)$l['id']; ?>" class="toggle-btn">Lihat detail</label>
                    </div>

                    <input type="checkbox" id="desk<?= (int)$l['id']; ?>" class="toggle">

                    <div class="job-text">
                        <?= trim(strip_tags($l['pekerjaan'])); ?>
                    </div>
                </div>

                <div class="job-block">
                    <div class="job-block-head">
                        <span class="job-block-title">Kualifikasi</span>
                        <label for="krit<?= (int)$l['id']; ?>" class="toggle-btn">Lihat detail</label>
                    </div>

                    <input type="checkbox" id="krit<?= (int)$l['id']; ?>" class="toggle">

                    <div class="job-text">
                        <?php if(!empty($l['kriteria'])){ ?>
                            <?= trim(strip_tags($l['kriteria'])); ?>
                        <?php } else { ?>
                            Lihat detail kriteria di halaman apply.
                        <?php } ?>
                    </div>
                </div>

            </div>

            <?php
              $deadline = $l['deadline'] ?? '';
              $isClosed = false;

              if($deadline){
                $isClosed = (strtotime($deadline) < strtotime(date('Y-m-d')));
              }
            ?>

            <!-- FOOTER: Apply + Ditutup (rapi & kecil, INLINE STYLE) -->
            <div class="job-footer" style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:nowrap; margin-top:14px;">

              <?php if(!$isClosed): ?>
                <a href="apply/index.php?id=<?= (int)$l['id']; ?>"
                   class="btn-apply"
                   style="font-size:13px; padding:8px 14px; border-radius:10px; font-weight:800; line-height:1;">
                  Apply Sekarang
                </a>
              <?php else: ?>
                <button disabled
                  style="font-size:13px; padding:8px 14px; border-radius:10px; font-weight:800; line-height:1;
                         background:#e5e7eb; color:#64748b; cursor:not-allowed; border:none;">
                  Ditutup
                </button>
              <?php endif; ?>

              <?php if($deadline): ?>
                <span style="
                  font-size:11px;
                  font-weight:800;
                  color:<?= $isClosed ? '#991b1b' : '#475569'; ?>;
                  background:<?= $isClosed ? 'rgba(239,68,68,.10)' : '#f8fafc'; ?>;
                  padding:5px 9px;
                  border-radius:999px;
                  border:1px solid <?= $isClosed ? 'rgba(239,68,68,.22)' : '#e5e7eb'; ?>;
                  white-space:nowrap;
                  line-height:1;
                ">
                  Ditutup: <?= date('d M Y', strtotime($deadline)); ?>
                </span>
              <?php endif; ?>

            </div>

        </div>
        <?php } ?>

    </div>
</section>

<footer class="footer-clean">
  <div class="footer-inner">
    © <?= date('Y'); ?> PT Wiraswasta Gemilang Indonesia | HRD PROJECT
  </div>
</footer>

<script>
  // highlight button
  function highlight(lang){
    const btnID = document.getElementById('btnID');
    const btnEN = document.getElementById('btnEN');

    btnID.classList.toggle('active', lang === 'id');
    btnEN.classList.toggle('active', lang === 'en');
  }

  // set language via google translate combo
  function setLang(lang){
    localStorage.setItem('lang', lang);
    highlight(lang);

    const combo = document.querySelector('.goog-te-combo');
    if(!combo){
      setTimeout(() => setLang(lang), 700);
      return;
    }
    combo.value = (lang === 'en') ? 'en' : 'id';
    combo.dispatchEvent(new Event('change'));
  }

  function googleTranslateElementInit() {
    new google.translate.TranslateElement({
      pageLanguage: 'id',
      autoDisplay: false
    }, 'google_translate_element');

    const saved = localStorage.getItem('lang') || 'id';
    highlight(saved);
    if(saved === 'en'){
      setTimeout(() => setLang('en'), 500);
    }
  }
</script>

<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

</body>
</html>
