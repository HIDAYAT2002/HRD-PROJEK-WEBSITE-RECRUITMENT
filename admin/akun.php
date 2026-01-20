<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
    header("Location: ../auth/login.php");
    exit;
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$email_sess = $_SESSION['email'] ?? '';

if($user_id > 0){
    $me = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id"));
} else {
    $email_safe = mysqli_real_escape_string($conn, $email_sess);
    $me = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE email='$email_safe'"));
}

if(!$me){
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akun</title>
    <link rel="stylesheet" href="../assets/style.css">

    <!-- CSS KHUSUS HALAMAN AKUN -->
    <style>
      :root{
        --bg:#f5f7fb;
        --card:rgba(255,255,255,.98);
        --text:#0f172a;
        --muted:rgba(15,23,42,.62);
        --line:rgba(15,23,42,.10);
        --shadow:0 14px 40px rgba(2,6,23,.10);
        --shadow2:0 10px 25px rgba(2,6,23,.06);
        --radius:18px; --radius2:14px;

        --accent:#ef4444; --accent2:#b91c1c;

        /* tema biru kaya halaman lain */
        --blue1:#3d4fe6;
        --blue2:#2f69f0;
        --blue3:#2742c7;
      }

      body{ background:var(--bg); }

      /* wrap konten kanan */
      .akun-wrap{
        position:relative;
        padding: 24px;
        padding-left: 284px; /* aman sidebar fixed (260 + gap) */
        max-width: 1280px;
        margin: 0 auto;
        box-sizing:border-box;
      }
      @media (max-width:980px){
        .akun-wrap{ padding-left:16px; padding-right:16px; }
      }

      /* ===== HERO HEADER (BIRU) ===== */
      .akun-hero{
        background: linear-gradient(110deg, var(--blue1), var(--blue2));
        border-radius: 22px;
        padding: 26px 22px;
        box-shadow: 0 18px 55px rgba(37,99,235,.22);
        border: 1px solid rgba(15,23,42,.08);
        position: relative;
        overflow:hidden;
        margin-bottom: 14px;
      }
      .akun-hero::after{
        content:"";
        position:absolute;
        right:-120px;
        top:-80px;
        width:360px;
        height:360px;
        background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.28), rgba(255,255,255,0) 60%);
        transform: rotate(18deg);
        pointer-events:none;
      }

      .akun-hero-row{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        position:relative;
        z-index:1;
      }
      @media (max-width:900px){
        .akun-hero-row{ flex-direction:column; align-items:flex-start; }
      }

      .akun-hero-title h2{
        margin:0;
        font-size:34px;
        letter-spacing:-.6px;
        color:#fff;
        font-weight: 1000;
        text-shadow: 0 10px 22px rgba(2,6,23,.30);
      }
      .akun-hero-title p{
        margin:8px 0 0;
        font-size:13px;
        color: rgba(255,255,255,.88);
        font-weight:800;
      }

      .akun-meta{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        align-items:center;
        justify-content:flex-end;
      }
      @media (max-width:900px){
        .akun-meta{ justify-content:flex-start; }
      }

      .badge{
        display:inline-flex; align-items:center; gap:8px;
        padding:10px 12px;
        border-radius:999px;
        background: rgba(255,255,255,.16);
        border: 1px solid rgba(255,255,255,.22);
        box-shadow: 0 10px 22px rgba(2,6,23,.14);
        font-size:12px;
        color:#fff;
        font-weight:900;
        backdrop-filter: blur(8px);
      }
      .dot{
        width:8px; height:8px; border-radius:999px;
        background:#22c55e;
        box-shadow:0 0 0 4px rgba(34,197,94,.18);
      }

      .akun-alert{
        background:rgba(59,130,246,.12);
        border:1px solid rgba(59,130,246,.22);
        padding:12px 14px;
        border-radius:var(--radius2);
        margin: 10px 0 16px;
        font-size:13px;
        color:var(--text);
        box-shadow: var(--shadow2);
      }

      /* grid: profil kecil, form besar */
      .akun-grid{
        display:grid;
        grid-template-columns: .9fr 1.1fr;
        gap:18px;
        align-items:start;
      }
      @media (max-width:1050px){
        .akun-grid{ grid-template-columns:1fr; }
      }

      .akun-card{
        background:var(--card);
        border:1px solid var(--line);
        border-radius:var(--radius);
        padding:18px;
        box-shadow:var(--shadow);
      }

      /* garis aksen kecil biar premium */
      .akun-card::before{
        content:"";
        display:block;
        height:3px;
        width:56px;
        border-radius:999px;
        background:rgba(239,68,68,.35);
        margin-bottom:12px;
      }

      .akun-card-head{
        display:flex; align-items:center; justify-content:space-between;
        margin-bottom:12px;
      }
      .akun-card h3{ margin:0; font-size:16px; color:var(--text); letter-spacing:-.2px; font-weight:1000; }
      .subtle{ font-size:12px; color:var(--muted); font-weight:800; }

      .akun-row{
        display:flex; justify-content:space-between; align-items:center;
        padding:12px 0; border-bottom:1px solid rgba(15,23,42,.08); font-size:14px;
      }
      .akun-row:last-child{ border-bottom:0; }
      .akun-row span{ color:var(--muted); font-weight:800; }
      .akun-row b{ color:var(--text); font-weight:1000; }

      .role-pill{
        display:inline-flex; align-items:center;
        padding:6px 10px; border-radius:999px;
        font-size:12px; border:1px solid rgba(239,68,68,.18);
        background:rgba(239,68,68,.10);
        color:#7f1d1d; font-weight:1000;
        text-transform: uppercase; letter-spacing:.6px;
      }

      .akun-form{ margin-top:6px; }
      .field{ margin-bottom:12px; }
      .label{ display:block; font-size:12px; color:var(--muted); margin-bottom:6px; font-weight:900; }

      .input-wrap{ position:relative; }
      .akun-form input, .akun-form select{
        width:100%; box-sizing:border-box;
        padding:12px 44px 12px 13px;
        border-radius:14px;
        border:1px solid rgba(15,23,42,.12);
        background:rgba(255,255,255,.92);
        outline:none; transition:.15s ease;
        font-size:14px;
        font-weight:800;
      }
      .akun-form select{ padding-right:13px; }
      .akun-form input:focus, .akun-form select:focus{
        border-color:rgba(239,68,68,.45);
        box-shadow:0 0 0 5px rgba(239,68,68,.12);
      }

      .eye-btn{
        position:absolute; right:10px; top:50%; transform:translateY(-50%);
        border:none; background:transparent; cursor:pointer;
        font-size:12px; color:var(--muted);
        padding:6px 8px; border-radius:10px;
        font-weight:900;
      }
      .eye-btn:hover{ background:rgba(15,23,42,.06); color:var(--text); }

      .hint{ margin-top:6px; font-size:12px; color:var(--muted); font-weight:800; }

      .strength{
        margin-top:8px; height:10px; border-radius:999px;
        background:rgba(15,23,42,.08); overflow:hidden;
      }
      .strength > div{
        height:100%; width:0%;
        background: linear-gradient(90deg, #f97316, #ef4444);
        transition: width .2s ease;
      }
      .strength-text{ margin-top:6px; font-size:12px; color:var(--muted); font-weight:900; }

      .akun-btn{
        width:100%;
        border:none; border-radius:999px;
        padding:13px 16px;
        font-weight:1000; letter-spacing:.3px;
        color:#fff; cursor:pointer;
        background: linear-gradient(180deg, var(--accent), var(--accent2));
        box-shadow: 0 14px 26px rgba(185,28,28,.22);
        transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
      }
      .akun-btn:hover{
        transform:translateY(-1px);
        filter:brightness(1.02);
        box-shadow: 0 18px 34px rgba(185,28,28,.28);
      }
      .akun-btn:active{ transform:translateY(0); }

      .akun-full{ grid-column: 1 / -1; }

      /* kecilin jarak sidebar kalau sidebar width beda di style.css */
      @media (max-width:1050px){
        .akun-wrap{ padding-left:16px; padding-right:16px; }
      }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-title">WGI ADMIN PANEL</div>

    <a href="Dashboard.php">Dashboard</a>
    <a href="lowongan.php">Kelola Lowongan</a>
    <a href="pelamar.php">Pelamar</a>
    <a href="akun.php" class="active">Akun</a>

    <div class="sidebar-footer">
        <a href="../auth/logout.php">Logout</a>
    </div>
</div>

<div class="akun-wrap">

  <!-- HERO BIRU (tema sama kayak halaman lain) -->
  <div class="akun-hero">
    <div class="akun-hero-row">
      <div class="akun-hero-title">
        <h2>Pengaturan Akun</h2>
        <p>Kelola keamanan akun kamu — ganti password dengan aman.</p>
      </div>

      <div class="akun-meta">
        <span class="badge"><span class="dot"></span> Login aktif</span>
        <span class="badge"><?= htmlspecialchars($me['email']); ?></span>
      </div>
    </div>
  </div>

  <?php if(isset($_GET['msg'])): ?>
    <div class="akun-alert"><?= htmlspecialchars($_GET['msg']); ?></div>
  <?php endif; ?>

  <div class="akun-grid">
    <div class="akun-card">
      <div class="akun-card-head">
        <h3>Profil</h3>
        <span class="subtle">Informasi akun</span>
      </div>
      <div class="akun-row"><span>Email</span><b><?= htmlspecialchars($me['email']); ?></b></div>
      <div class="akun-row"><span>Role</span><b class="role-pill"><?= htmlspecialchars($me['role']); ?></b></div>
    </div>

    <div class="akun-card">
      <div class="akun-card-head">
        <h3>Update Password</h3>
        <span class="subtle">Disarankan berkala</span>
      </div>

      <form action="akun_update_password.php" method="POST" class="akun-form" id="akunForm">
        <div class="field">
          <label class="label">Password Lama</label>
          <div class="input-wrap">
            <input type="password" name="old_password" placeholder="Masukkan password lama" required>
            <button class="eye-btn" type="button" data-toggle="old_password">Tampil</button>
          </div>
        </div>

        <div class="field">
          <label class="label">Password Baru</label>
          <div class="input-wrap">
            <input type="password" name="new_password" id="newPass" placeholder="Minimal 6 karakter" required>
            <button class="eye-btn" type="button" data-toggle="new_password">Tampil</button>
          </div>

          <div class="strength"><div id="strengthBar"></div></div>
          <div class="strength-text" id="strengthText">Kekuatan password: -</div>
          <div class="hint">Gunakan kombinasi huruf besar, kecil, angka, dan simbol.</div>
        </div>

        <div class="field">
          <label class="label">Ulangi Password Baru</label>
          <div class="input-wrap">
            <input type="password" name="confirm_password" id="confirmPass" placeholder="Ulangi password baru" required>
            <button class="eye-btn" type="button" data-toggle="confirm_password">Tampil</button>
          </div>
          <div class="hint" id="matchHint"></div>
        </div>

        <button class="akun-btn" type="submit">Simpan Password</button>
      </form>
    </div>

    <?php if(($me['role'] ?? '') === 'manager'): ?>
      <div class="akun-card akun-full">
        <div class="akun-card-head">
          <h3>Tambah Akun</h3>
          <span class="subtle">HRD / Manager</span>
        </div>

        <form action="akun_tambah.php" method="POST" class="akun-form">
          <div class="field">
            <label class="label">Email</label>
            <input type="email" name="email" placeholder="email@domain.com" required style="padding-right:13px;">
          </div>

          <div class="field">
            <label class="label">Password</label>
            <input type="password" name="password" placeholder="Minimal 6 karakter" required style="padding-right:13px;">
          </div>

          <div class="field">
            <label class="label">Role</label>
            <select name="role" required>
              <option value="">Pilih Role</option>
              <option value="hrd">HRD</option>
              <option value="manager">Manager</option>
            </select>
          </div>

          <button class="akun-btn" type="submit">Tambah Akun</button>
          <div class="hint" style="margin-top:10px;">Hanya role manager yang dapat menambah akun.</div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  // show/hide password
  document.querySelectorAll(".eye-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const name = btn.getAttribute("data-toggle");
      const input = document.querySelector(`input[name="${name}"]`);
      if(!input) return;

      if(input.type === "password"){
        input.type = "text";
        btn.textContent = "Sembunyi";
      } else {
        input.type = "password";
        btn.textContent = "Tampil";
      }
    });
  });

  // password strength
  const newPass = document.getElementById("newPass");
  const confirmPass = document.getElementById("confirmPass");
  const bar = document.getElementById("strengthBar");
  const text = document.getElementById("strengthText");
  const matchHint = document.getElementById("matchHint");

  function calcStrength(p){
    let score = 0;
    if(p.length >= 6) score++;
    if(p.length >= 10) score++;
    if(/[A-Z]/.test(p)) score++;
    if(/[0-9]/.test(p)) score++;
    if(/[^A-Za-z0-9]/.test(p)) score++;
    return score;
  }

  function updateStrength(){
    if(!newPass) return;
    const p = newPass.value || "";
    const s = calcStrength(p);
    bar.style.width = Math.min(100, s * 20) + "%";

    let label = "-";
    if(s <= 1) label = "Lemah";
    else if(s === 2) label = "Cukup";
    else if(s === 3) label = "Bagus";
    else label = "Kuat";

    text.textContent = "Kekuatan password: " + label;
  }

  function updateMatch(){
    if(!newPass || !confirmPass) return;
    const a = newPass.value || "";
    const b = confirmPass.value || "";
    if(!b){ matchHint.textContent=""; return; }

    if(a === b){
      matchHint.textContent = "Password cocok ✅";
      matchHint.style.color = "rgba(34,197,94,.9)";
    } else {
      matchHint.textContent = "Password belum cocok ❌";
      matchHint.style.color = "rgba(239,68,68,.95)";
    }
  }

  if(newPass){
    newPass.addEventListener("input", () => { updateStrength(); updateMatch(); });
  }
  if(confirmPass){
    confirmPass.addEventListener("input", updateMatch);
  }
  updateStrength(); updateMatch();
</script>

</body>
</html>
