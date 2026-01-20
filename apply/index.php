<?php
include '../config/koneksi.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Lowongan tidak ditemukan');

// ambil lowongan (prepared statement, aman)
$stmt = mysqli_prepare($conn, "SELECT * FROM lowongan WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$low = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$low) die('Lowongan tidak ditemukan');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Apply - <?= htmlspecialchars($low['posisi']); ?> | WGI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    :root{
      /* background tetap gelap */
      --bg-image: url("../assets/bg-login.jpg");
      --overlay1: rgba(15,10,40,.58);
      --overlay2: rgba(5,8,18,.78);

      /* card putih clean */
      --card:#ffffff;
      --text:#0f172a;
      --muted:#dc0909;
      --border:#e5e7eb;
      --shadow: 0 24px 80px rgba(0,0,0,.35);
      --radius: 18px;

      /* ungu WGI */
      --brand:#c90303;
      --brand2:#a855f7;
      --brandRing: rgba(124,58,237,.12);
    }

    *{ box-sizing:border-box; }
    html,body{ height:100%; }
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
      background:#0b1020;
      color:var(--text);
    }

    .page{
      min-height:100%;
      display:flex;
      flex-direction:column;
      align-items:center;
      padding: 18px 14px 40px;
      position:relative;
      isolation:isolate;
    }

    .page::before{
      content:"";
      position:absolute;
      inset:0;
      z-index:-2;
      background: var(--bg-image) center/cover no-repeat;
      opacity:.95;
    }
    .page::after{
      content:"";
      position:absolute;
      inset:0;
      z-index:-1;
      background:
        linear-gradient(180deg, var(--overlay1), var(--overlay2)),
        radial-gradient(900px 520px at 20% 20%, rgba(168,85,247,.25), transparent 55%),
        radial-gradient(900px 520px at 80% 25%, rgba(99,102,241,.22), transparent 55%);
    }

    /* running text */
    .running-text{
      width:100%;
      max-width:520px;
      overflow:hidden;
      background:rgba(15,23,42,.85);
      color:#fff;
      padding:10px 0;
      font-size:13px;
      border-radius:999px;
      margin-bottom:14px;
      text-align:center;
    }
    .running-track{
      white-space:nowrap;
      display:inline-block;
      padding-left:100%;
      animation:marquee 18s linear infinite;
    }
    @keyframes marquee{
      from{ transform:translateX(0); }
      to{ transform:translateX(-100%); }
    }

    /* card */
    .apply-card{
      width:100%;
      max-width:420px;
      background: var(--card);
      border: 1px solid rgba(255,255,255,.65);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 20px 18px 18px;
    }

    .title{
      margin: 2px 0 0;
      font-size: 22px;
      font-weight: 900;
      color: var(--text);
    }
    .subtitle{
      margin: 6px 0 0;
      font-size: 13px;
      color: var(--muted);
      font-weight: 700;
    }

    .form{ margin-top: 14px; }
    .field{ margin-top: 12px; }

    .input, select{
      width:100%;
      padding: 12px 14px;
      border: 1px solid var(--border);
      border-radius: 12px;
      font-size: 14px;
      outline: none;
      background: #fff;
      color: var(--text);
      transition: box-shadow .12s ease, border-color .12s ease;
    }
    .input::placeholder{ color:#94a3b8; }

    .input:focus, select:focus{
      border-color: rgba(222, 9, 16, 0.55);
      box-shadow: 0 0 0 4px var(--brandRing);
    }

    .label{
      display:block;
      margin: 0 0 8px;
      font-size: 13px;
      font-weight: 800;
      color: #0f172a;
    }

    /* upload */
    .upload-label{
      display:block;
      margin-top: 14px;
      font-size: 13px;
      font-weight: 800;
      color: var(--text);
    }
    .upload{
      margin-top: 8px;
      width:100%;
      padding: 12px 14px;
      border: 1px solid var(--border);
      border-radius: 12px;
      background: #fff;
    }
    .upload input[type=file]{ width:100%; }

    /* tombol KIRIM (PASTI UNGU) */
    .btn{
      width:100%;
      margin-top: 18px;
      padding: 14px 16px;
      border: 0;
      border-radius: 999px;
      cursor:pointer;
      color:#fff;
      font-weight: 900;
      font-size: 14px;
      letter-spacing:.2px;

      /* ini yang bikin ungu, bukan merah */
      background: linear-gradient(90deg, var(--brand), var(--brand2)) !important;

      box-shadow: 0 16px 40px rgba(124,58,237,.28);
      transition: transform .08s ease, filter .12s ease;
      -webkit-appearance: none;
      appearance: none;
    }
    .btn:hover{ filter: brightness(1.05); }
    .btn:active{ transform: translateY(1px); }

    .bottom-back{
      display:block;
      text-align:center;
      margin-top: 14px;
      font-size: 13px;
      color: #ae0808;
      text-decoration:none;
      font-weight: 800;
    }
    .bottom-back:hover{ text-decoration: underline; }

    @media (max-width:420px){
      .apply-card{ padding: 18px 16px 16px; }
      .title{ font-size: 20px; }
    }
  </style>
</head>

<body>
<div class="page">

  <div class="running-text">
    <div class="running-track">
      Selamat Datang di proses Rekrutmen PT Wiraswasta Gemilang Indonesia — Pastikan data yang Anda isi sudah benar 🚀
    </div>
  </div>

  <div class="apply-card">
    <div class="title">Apply Posisi</div>
    <div class="subtitle"><?= htmlspecialchars($low['posisi']); ?></div>

    <form class="form" action="proses.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="lowongan_id" value="<?= $id; ?>">

      <div class="field">
        <input class="input" type="text" name="nama" placeholder="Nama Lengkap" required>
      </div>

      <div class="field">
        <input class="input" type="email" name="email" placeholder="Email Aktif" required>
      </div>

      <div class="field">
        <input class="input" type="text" name="telepon" placeholder="No Telepon / WhatsApp" required>
      </div>

      <div class="field">
        <input class="input" type="text" name="kota" placeholder="Kota / Kabupaten" required>
      </div>

      <div class="field">
        <label class="label">Tanggal Lahir</label>
        <input class="input" type="date" name="tgl_lahir" required>
      </div>

      <div class="field">
        <select name="pendidikan" id="pendidikan" required>
          <option value="">Pendidikan Terakhir</option>
          <option value="SMA / SMK">SMA / SMK</option>
          <option value="D3">D3</option>
          <option value="S1">S1</option>
          <option value="S2">S2</option>
        </select>
      </div>

      <div class="field" id="jurusanWrap" style="display:none;">
        <input class="input" type="text" name="jurusan" id="jurusan" placeholder="Program Studi / Jurusan">
      </div>

      <label class="upload-label">Upload CV (PDF)</label>
      <div class="upload">
        <input type="file" name="cv" accept=".pdf" required>
      </div>

      <button class="btn" type="submit">Kirim Lamaran</button>

      <a href="../index.php" class="bottom-back">← Kembali ke Halaman Depan</a>
    </form>
  </div>

</div>

<script>
const pendidikan = document.getElementById('pendidikan');
const jurusanWrap = document.getElementById('jurusanWrap');
const jurusan = document.getElementById('jurusan');

pendidikan.addEventListener('change', function(){
  if(this.value !== ''){
    jurusanWrap.style.display = 'block';
    jurusan.required = true;
    jurusan.placeholder = (this.value === 'SMA / SMK')
      ? 'Jurusan (IPA / IPS / SMK)'
      : 'Program Studi / Jurusan';
  } else {
    jurusanWrap.style.display = 'none';
    jurusan.required = false;
    jurusan.value = '';
  }
});
</script>

</body>
</html>
