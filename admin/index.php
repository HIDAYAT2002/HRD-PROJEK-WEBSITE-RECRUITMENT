<?php
include '../config/koneksi.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Lowongan tidak ditemukan');
}

// ambil lowongan (prepared statement, aman)
$stmt = mysqli_prepare($conn, "SELECT * FROM lowongan WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$low = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$low) {
    die('Lowongan tidak ditemukan');
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Apply - <?= htmlspecialchars($low['posisi']); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    body.apply-page{
      display:flex;
      flex-direction:column;
      align-items:center;
    }
    .apply-card{
      width:100%;
      max-width:420px;
      margin:20px auto 40px;
    }
    .running-text{
      width:100%;
      overflow:hidden;
      background:#0f172a;
      color:#fff;
      padding:10px 0;
      font-size:13px;
      border-radius:12px;
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
    .bottom-back{
      display:block;
      text-align:center;
      margin-top:14px;
      font-size:13px;
    }
    .field-label{
      display:block;
      font-size:12px;
      font-weight:800;
      color:#0f172a;
      margin:6px 0 6px;
      opacity:.85;
    }
  </style>
</head>

<body class="apply-page">

<div class="running-text">
  <div class="running-track">
    Selamat Datang di proses Rekrutmen PT Wiraswasta Gemilang Indonesia — Pastikan data yang Anda isi sudah benar 🚀
  </div>
</div>

<div class="apply-card">
  <h2>Apply Posisi</h2>
  <h3><?= htmlspecialchars($low['posisi']); ?></h3>

  <form action="proses.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="lowongan_id" value="<?= $id; ?>">

    <input type="text" name="nama" placeholder="Nama Lengkap" required>
    <input type="email" name="email" placeholder="Email Aktif" required>
    <input type="text" name="telepon" placeholder="No Telepon / WhatsApp" required>
    <input type="text" name="kota" placeholder="Kota / Kabupaten" required>

    <label class="field-label">Tanggal Lahir</label>
    <input type="date" name="tgl_lahir" required>

    <select name="pendidikan" id="pendidikan" required>
      <option value="">Pendidikan Terakhir</option>
      <option value="SMA / SMK">SMA / SMK</option>
      <option value="D3">D3</option>
      <option value="S1">S1</option>
      <option value="S2">S2</option>
    </select>

    <input type="text" name="jurusan" id="jurusan" placeholder="Program Studi / Jurusan" style="display:none;" required>

    <label class="upload">
      Upload CV (PDF)
      <input type="file" name="cv" accept=".pdf" required>
    </label>

    <button class="btn-primary">Kirim Lamaran</button>

    <a href="../index.php" class="btn-back bottom-back">
      ← Kembali ke Halaman Depan
    </a>
  </form>
</div>

<script>
const pendidikan = document.getElementById('pendidikan');
const jurusan = document.getElementById('jurusan');

pendidikan.addEventListener('change', function(){
  if(this.value !== ''){
    jurusan.style.display = 'block';
    jurusan.required = true;
    jurusan.placeholder = (this.value === 'SMA / SMK')
      ? 'Jurusan (IPA / IPS / SMK)'
      : 'Program Studi / Jurusan';
  } else {
    jurusan.style.display = 'none';
    jurusan.required = false;
    jurusan.value = '';
  }
});
</script>

</body>
</html>
