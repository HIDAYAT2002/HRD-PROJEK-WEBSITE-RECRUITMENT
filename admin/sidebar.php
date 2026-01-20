<?php
// WAJIB: kunci admin
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

$cur = basename($_SERVER['PHP_SELF'] ?? '');

function is_active($f){
  global $cur;
  return ($cur === $f) ? 'active' : '';
}
function is_in($arr){
  global $cur;
  return in_array($cur, $arr, true);
}
?>
<style>
/* ===== SIDEBAR PROFESSIONAL ===== */
.sidebar{
  position: fixed;
  top: 0;
  left: 0;
  width: 280px;
  height: 100vh;
  background: linear-gradient(180deg, #020617, #020b18);
  padding: 24px 18px;
  box-sizing: border-box;
  box-shadow: 6px 0 30px rgba(2,6,23,.35);
  display: flex;
  flex-direction: column;
  z-index: 999;
}

.sidebar-title{
  font-size: 15px;
  font-weight: 900;
  letter-spacing: .6px;
  color: #fff;
  margin-bottom: 10px;
  text-transform: uppercase;
  opacity: .95;
}
.sidebar-sub{
  font-size: 12px;
  font-weight: 650;
  color: rgba(255,255,255,.55);
  margin-bottom: 18px;
}

/* MENU */
.sidebar a{
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px 14px;
  margin-bottom: 6px;
  border-radius: 14px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
  color: rgba(255,255,255,.78);
  transition: all .15s ease;
}

.sidebar a:hover{
  background: rgba(255,255,255,.06);
  color: #fff;
}

.sidebar a.active{
  background: linear-gradient(135deg, #1d4ed8, #2563eb);
  color: #fff;
  box-shadow: 0 10px 24px rgba(37,99,235,.35);
}

/* GROUP SEPARATOR (tetap ada, tapi sekarang jadi tombol accordion) */
.sidebar .menu-sep{
  margin: 14px 0 10px;
  font-size: 11px;
  letter-spacing: .5px;
  text-transform: uppercase;
  color: rgba(255,255,255,.45);
  padding-left: 6px;
}

/* ====== TAMBAHAN: ACCORDION STYLE (MINIMAL) ====== */
.menu-group{
  margin-bottom: 10px;
}

.menu-sep.btn-acc{
  display:flex;
  align-items:center;
  justify-content: space-between;
  gap: 10px;
  width: 100%;
  border: 0;
  background: transparent;
  cursor: pointer;
  padding: 10px 8px;
  border-radius: 14px;
  transition: .15s ease;
  margin: 12px 0 6px;
}
.menu-sep.btn-acc:hover{
  background: rgba(255,255,255,.04);
}

.acc-left{
  display:flex;
  flex-direction: column;
  gap: 2px;
  text-align:left;
}
.acc-left b{
  font-size: 12px;
  font-weight: 900;
  letter-spacing: .6px;
  color: rgba(255,255,255,.85);
  text-transform: uppercase;
}
.acc-left small{
  font-size: 11px;
  font-weight: 650;
  color: rgba(255,255,255,.50);
  text-transform: none;
  letter-spacing: .2px;
}

.acc-caret{
  width: 38px;
  height: 38px;
  border-radius: 14px;
  display:flex;
  align-items:center;
  justify-content:center;
  border: 1px solid rgba(255,255,255,.08);
  background: rgba(255,255,255,.03);
}
.acc-caret svg{
  width: 16px;
  height: 16px;
  transition: transform .15s ease;
  stroke: rgba(255,255,255,.8);
}

.menu-group.open .acc-caret svg{
  transform: rotate(180deg);
}

/* submenu container */
.submenu{
  display:none;
  margin-left: 10px;
  padding-left: 12px;
  border-left: 1px dashed rgba(255,255,255,.16);
}
.menu-group.open .submenu{
  display:block;
}

.submenu a{
  margin-bottom: 6px;
}

/* FOOTER */
.sidebar-footer{
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px solid rgba(255,255,255,.08);
}

.sidebar-footer a{
  background: rgba(239,68,68,.12);
  color: #fecaca;
}

.sidebar-footer a:hover{
  background: rgba(239,68,68,.2);
  color: #fff;
}

/* RESPONSIVE (tetap punya kamu, tapi biar accordion ga ancur) */
@media(max-width:900px){
  .sidebar{
    position: relative;
    width: 100%;
    height: auto;
    flex-direction: column;   /* penting: jangan row supaya submenu bisa kebawah */
    gap: 8px;
    padding: 14px;
  }

  .sidebar-title{
    margin-bottom: 6px;
  }

  .sidebar a{
    margin-bottom: 6px;
    width: 100%;
    justify-content: flex-start;
    font-size: 14px;
  }

  .submenu{
    margin-left: 6px;
  }

  /* footer tetep tampil di mobile */
}
</style>

<div class="sidebar" id="wgiSidebar">
  <div class="sidebar-title">WGI Admin Panel</div>
  <div class="sidebar-sub">Recruitment System</div>

  <a href="Dashboard.php" class="<?= is_active('Dashboard.php') ?>">
    Dashboard
  </a>

  <!-- GROUP: CAREER -->
  <?php $openCareer = is_in(['lowongan.php']); ?>
  <div class="menu-group <?= $openCareer ? 'open' : '' ?>" data-group="career">
    <button type="button" class="menu-sep btn-acc" data-toggle="career">
      <span class="acc-left">
        <b>Manajemen Career</b>
        <small>Kelola lowongan</small>
      </span>
      <span class="acc-caret" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round">
          <path d="M6 9l6 6 6-6"></path>
        </svg>
      </span>
    </button>

    <div class="submenu">
      <a href="lowongan.php" class="<?= is_active('lowongan.php') ?>">
        Kelola Lowongan
      </a>
    </div>
  </div>

  <!-- GROUP: PELAMAR -->
  <?php $openPelamar = is_in(['pelamar.php','seleksi.php']); ?>
  <div class="menu-group <?= $openPelamar ? 'open' : '' ?>" data-group="pelamar">
    <button type="button" class="menu-sep btn-acc" data-toggle="pelamar">
      <span class="acc-left">
        <b>Manajemen Pelamar</b>
        <small>Pelamar & seleksi</small>
      </span>
      <span class="acc-caret" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round">
          <path d="M6 9l6 6 6-6"></path>
        </svg>
      </span>
    </button>

    <div class="submenu">
      <a href="pelamar.php" class="<?= is_active('pelamar.php') ?>">
        Data Pelamar
      </a>

      <a href="seleksi.php" class="<?= is_active('seleksi.php') ?>">
        Data Seleksi
      </a>
    </div>
  </div>

  <!-- GROUP: AKUN -->
  <?php $openAkun = is_in(['data_akun.php','akun.php']); ?>
  <div class="menu-group <?= $openAkun ? 'open' : '' ?>" data-group="akun">
    <button type="button" class="menu-sep btn-acc" data-toggle="akun">
      <span class="acc-left">
        <b>Manajemen Akun</b>
        <small>Role & pengaturan</small>
      </span>
      <span class="acc-caret" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round">
          <path d="M6 9l6 6 6-6"></path>
        </svg>
      </span>
    </button>

    <div class="submenu">
      <a href="data_akun.php" class="<?= is_active('data_akun.php') ?>">
        Data Akun
      </a>

      <a href="akun.php" class="<?= is_active('akun.php') ?>">
        Pengaturan Akun
      </a>
    </div>
  </div>

  <div class="sidebar-footer">
    <a href="../auth/logout.php">Logout</a>
  </div>
</div>

<script>
(function(){
  // toggle accordion + simpan state
  function loadState(){
    try { return JSON.parse(localStorage.getItem('wgi_acc_state') || '{}'); }
    catch(e){ return {}; }
  }
  function saveState(key, val){
    try{
      var s = loadState();
      s[key] = !!val;
      localStorage.setItem('wgi_acc_state', JSON.stringify(s));
    }catch(e){}
  }

  var saved = loadState();

  document.querySelectorAll('.menu-group').forEach(function(g){
    var key = g.getAttribute('data-group');
    if(!key) return;

    // kalau group sudah open karena halaman aktif (PHP), jangan ditutup
    if(g.classList.contains('open')) return;

    // restore state
    if(saved[key] === true) g.classList.add('open');
  });

  document.querySelectorAll('[data-toggle]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var key = btn.getAttribute('data-toggle');
      var g = document.querySelector('.menu-group[data-group="'+key+'"]');
      if(!g) return;

      var willOpen = !g.classList.contains('open');
      g.classList.toggle('open', willOpen);
      saveState(key, willOpen);
    });
  });
})();
</script>
