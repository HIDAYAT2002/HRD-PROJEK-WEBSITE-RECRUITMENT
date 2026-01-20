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
  margin-bottom: 28px;
  text-transform: uppercase;
  opacity: .95;
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

/* GROUP SEPARATOR */
.sidebar .menu-sep{
  margin: 14px 0 10px;
  font-size: 11px;
  letter-spacing: .5px;
  text-transform: uppercase;
  color: rgba(255,255,255,.45);
  padding-left: 6px;
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

/* RESPONSIVE */
@media(max-width:900px){
  .sidebar{
    position: relative;
    width: 100%;
    height: auto;
    flex-direction: row;
    gap: 8px;
    padding: 14px;
  }

  .sidebar-title{
    display:none;
  }

  .sidebar a{
    margin-bottom: 0;
    flex: 1;
    justify-content: center;
    font-size: 13px;
  }

  .menu-sep,
  .sidebar-footer{
    display:none;
  }
}
</style>

<div class="sidebar">
    <div class="sidebar-title">WGI Admin Panel</div>

    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>">
        Dashboard
    </a>

    <a href="lowongan.php" class="<?= basename($_SERVER['PHP_SELF'])=='lowongan.php'?'active':'' ?>">
        Kelola Lowongan
    </a>

    <a href="pelamar.php" class="<?= basename($_SERVER['PHP_SELF'])=='pelamar.php'?'active':'' ?>">
        Data Pelamar
    </a>

    <div class="menu-sep">Manajemen</div>

    <a href="data_akun.php" class="<?= basename($_SERVER['PHP_SELF'])=='data_akun.php'?'active':'' ?>">
        Data Akun
    </a>

    <a href="akun.php" class="<?= basename($_SERVER['PHP_SELF'])=='akun.php'?'active':'' ?>">
        Pengaturan Akun
    </a>

    <div class="sidebar-footer">
        <a href="../auth/logout.php">Logout</a>
    </div>
</div>
