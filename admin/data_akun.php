<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
    header("Location: ../auth/login.php");
    exit;
}

// ambil data user login
$uid = (int)($_SESSION['user_id'] ?? 0);
$me  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$uid"));

if(!$me){
    header("Location: ../auth/login.php");
    exit;
}

// hanya manager boleh akses
if(($me['role'] ?? '') !== 'manager'){
    die('Akses ditolak Karena Kamu bukan Manager');
}

// ambil semua akun
$data = mysqli_query($conn, "SELECT id,email,role FROM users ORDER BY role DESC, email ASC");
$total = mysqli_num_rows($data);
?>
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Data Akun</title>
  <link rel="stylesheet" href="../assets/style.css">

  <style>
    :root{
      /* background ala apply/login (BELAKANG) */
      --bg-image: url("../assets/bg-login.jpg");
      --overlay1: rgba(15,10,40,.58);
      --overlay2: rgba(5,8,18,.78);
    }

    body{
      background: transparent;
      position:relative;
      min-height:100vh;
    }

    /* BACKGROUND BELAKANG */
    .bg-layer{
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
      background: var(--bg-image) center/cover no-repeat;
      opacity: .95;
    }
    .bg-layer::after{
      content:"";
      position:absolute;
      inset:0;
      background:
        linear-gradient(180deg, var(--overlay1), var(--overlay2)),
        radial-gradient(900px 520px at 20% 20%, rgba(168,85,247,.25), transparent 55%),
        radial-gradient(900px 520px at 80% 25%, rgba(99,102,241,.22), transparent 55%);
    }

    /* pastiin konten di atas background */
    .sidebar, .page-wrap{ position:relative; z-index:2; }

    .page-wrap{
      padding:28px;
      padding-left:340px;
      max-width:1200px;
      margin:auto;
    }

    /* ===== HERO (FIX BENERAN: TIDAK BOLEH KEPANJANGAN) ===== */
    .hero{
      height:auto !important;
      min-height:0 !important;

      background:linear-gradient(120deg, #2436b8, #2f6cf0);
      border-radius:22px;
      padding:18px 22px;
      margin-bottom:16px;
      box-shadow:0 18px 50px rgba(37,99,235,.22);
      border:1px solid rgba(15,23,42,.08);

      display:flex;
      align-items:center;
    }

    .hero-row{
      width:100%;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:14px;
    }

    .hero h2{
      margin:0;
      font-size:30px;
      font-weight:900;
      color:#fff;
      letter-spacing:-.4px;
      line-height:1.1;
    }

    .hero-badge{
      padding:8px 14px;
      border-radius:999px;
      background:rgba(255,255,255,.18);
      border:1px solid rgba(255,255,255,.35);
      color:#fff;
      font-size:12px;
      font-weight:900;
      white-space:nowrap;
    }

    /* ===== TABLE ===== */
    .table-card{
      background: rgba(255,255,255,.96);
      border-radius:16px;
      box-shadow:0 12px 30px rgba(2,6,23,.18);
      overflow:hidden;
      border: 1px solid rgba(255,255,255,.55);
      backdrop-filter: blur(10px);
    }

    table{
      width:100%;
      border-collapse:collapse;
      font-size:14px;
    }

    th, td{
      padding:14px 16px;
      border-bottom:1px solid rgba(226,232,240,.9);
      text-align:left;
    }

    th{
      background:#0f172a;
      color:#fff;
      font-size:12px;
      text-transform:uppercase;
      letter-spacing:.6px;
    }

    tr:hover td{
      background:rgba(248,250,252,.8);
    }

    .role{
      display:inline-block;
      padding:6px 12px;
      border-radius:999px;
      font-size:11px;
      font-weight:900;
      letter-spacing:.6px;
      text-transform:uppercase;
    }

    .role.manager{
      background:rgba(37,99,235,.18);
      color:#1e40af;
    }

    .role.hrd{
      background:rgba(16,185,129,.18);
      color:#065f46;
    }

    .btn-del{
      padding:6px 12px;
      border-radius:999px;
      font-size:12px;
      text-decoration:none;
      background:rgba(239,68,68,.15);
      color:#7f1d1d;
      font-weight:900;
    }

    .btn-del:hover{
      background:rgba(239,68,68,.28);
    }

    .muted{
      color:#64748b;
      font-size:13px;
      font-weight:700;
    }

    @media(max-width:900px){
      .page-wrap{ padding-left:16px; }
      .hero-row{ flex-direction:column; align-items:flex-start; }
      .hero h2{ font-size:24px; }
    }
  </style>
</head>
<body>

<!-- BACKGROUND BELAKANG -->
<div class="bg-layer" aria-hidden="true"></div>

<?php include 'sidebar.php'; ?>

<div class="page-wrap">

  <div class="hero">
    <div class="hero-row">
      <h2>Data Akun</h2>
      <span class="hero-badge"><?= $total; ?> akun terdaftar</span>
    </div>
  </div>

  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>Email</th>
          <th>Role</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while($u=mysqli_fetch_assoc($data)): ?>
        <tr>
          <td><?= htmlspecialchars($u['email']); ?></td>
          <td>
            <span class="role <?= htmlspecialchars($u['role']); ?>">
              <?= strtoupper(htmlspecialchars($u['role'])); ?>
            </span>
          </td>
          <td>
            <?php if($u['id'] != $uid): ?>
              <a href="akun_hapus.php?id=<?= (int)$u['id']; ?>"
                 class="btn-del"
                 onclick="return confirm('Yakin hapus akun ini?');">
                 Hapus
              </a>
            <?php else: ?>
              <span class="muted">Akun ini</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

</body>
</html>
