<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
  header("Location: ../auth/login.php");
  exit;
}

// PK lowongan
$pk = 'id';
$k = mysqli_query($conn, "SHOW KEYS FROM lowongan WHERE Key_name='PRIMARY'");
if($k && mysqli_num_rows($k) > 0){
  $kk = mysqli_fetch_assoc($k);
  if(!empty($kk['Column_name'])) $pk = $kk['Column_name'];
}

// Data
$data = mysqli_query($conn, "SELECT * FROM lowongan ORDER BY `$pk` DESC");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Kelola Lowongan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/style.css">

<style>
/* ================= HEADER DASH ================= */
.dash-head{
  position:relative;
  background:linear-gradient(135deg,#4f46e5,#2563eb);
  border-radius:22px;
  min-height:140px;
  margin-bottom:22px;
  box-shadow:0 14px 40px rgba(2,6,23,.12);
}

/* JUDUL KIRI */
.dash-title{
  position:absolute;
  top:26px;
  left:28px;
  margin:0;

  font-size:44px;
  font-weight:950;
  color:#fff !important;
  line-height:1.05;
  white-space:nowrap;
  text-shadow:0 12px 28px rgba(2,6,23,.35);
}

/* KANAN ATAS */
.dash-right{
  position:absolute;
  top:18px;
  right:18px;

  display:flex;
  flex-direction:column;
  align-items:flex-end;
  gap:10px;
}

/* BUTTON */
.btn-add{
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding:12px 18px;
  border-radius:999px;

  font-size:14px;
  font-weight:900;
  text-decoration:none;
  color:#fff !important;

  background:rgba(255,255,255,.18);
  border:1px solid rgba(255,255,255,.28);
  box-shadow:0 12px 30px rgba(2,6,23,.25);
}
.btn-add:hover{filter:brightness(1.05)}

/* TEKS KECIL – ISI TIDAK DIUBAH */
.dash-sub{
  padding:8px 14px;
  border-radius:999px;

  font-size:14px;
  font-weight:900;
  color:#fff !important;
  white-space:nowrap;

  background:rgba(2,6,23,.18);
  border:1px solid rgba(255,255,255,.18);
  backdrop-filter:blur(8px);
  text-shadow:0 8px 22px rgba(2,6,23,.35);
}

/* ================= TABLE ================= */
.table-lowongan{
  width:100%;
  border-collapse:collapse;
  background:#fff;
  border-radius:16px;
  overflow:hidden;
  box-shadow:0 12px 30px rgba(2,6,23,.08)
}

.table-lowongan th{
  background:linear-gradient(180deg,#020617,#020617);
  color:#fff!important;
  text-transform:uppercase;
  font-size:12px;
  letter-spacing:.7px;
  padding:14px 16px;
}

.table-lowongan td{
  padding:14px 16px;
  border-bottom:1px solid #e5e7eb;
  font-size:14px;
  font-weight:600;
  text-align:center;
}

.table-lowongan tr:hover td{background:#f8fafc}

.pill-date{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:7px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:900;
  background:#f8fafc;
  border:1px solid #e5e7eb;
}

.btn-edit-small{
  padding:8px 12px;
  border-radius:10px;
  font-weight:800;
  background:#dbeafe;
  color:#1e40af;
  text-decoration:none;
}
.btn-hapus-small{
  padding:8px 12px;
  border-radius:10px;
  font-weight:800;
  background:#fee2e2;
  color:#7f1d1d;
  text-decoration:none;
}

.btn-status-aktif{
  padding:8px 12px;
  border-radius:10px;
  background:#dcfce7;
  color:#166534;
  font-weight:900;
}
.btn-status-nonaktif{
  padding:8px 12px;
  border-radius:10px;
  background:#e5e7eb;
  color:#334155;
  font-weight:900;
}
</style>
</head>

<body class="admin-body">
<?php include 'sidebar.php'; ?>

<div class="content">

  <!-- ===== HEADER ===== -->
  <div class="dash-head">
    <h1 class="dash-title">Kelola Lowongan</h1>

    <div class="dash-right">
      <a href="lowongan_tambah.php" class="btn-add">+ Tambah Lowongan</a>

      <div class="dash-sub">
        Kelola posisi, lokasi, status, dan deadline • <?= date('d M Y'); ?>
      </div>
    </div>
  </div>

  <!-- ===== TABLE ===== -->
  <table class="table-lowongan">
    <tr>
      <th>Posisi</th>
      <th>Lokasi</th>
      <th>Publish</th>
      <th>Deadline</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>

<?php if($data && mysqli_num_rows($data)>0): ?>
<?php while($l=mysqli_fetch_assoc($data)): ?>
<?php
$id=(int)$l[$pk];
$status=$l['status'];
$deadlineText=$l['deadline']?date('d M Y',strtotime($l['deadline'])):'-';
$createdText=$l['created_at']?date('d M Y',strtotime($l['created_at'])):'-';
?>
<tr>
<td><?=htmlspecialchars($l['posisi'])?></td>
<td><?=htmlspecialchars($l['kota']??'')?></td>
<td><span class="pill-date">📌 <?=$createdText?></span></td>
<td><span class="pill-date">🗓 <?=$deadlineText?></span></td>
<td>
<?php if($status==='aktif'): ?>
<a class="btn-status-aktif" href="lowongan_status.php?id=<?=$id?>&to=nonaktif">Aktif</a>
<?php else: ?>
<a class="btn-status-nonaktif" href="lowongan_status.php?id=<?=$id?>&to=aktif">Nonaktif</a>
<?php endif; ?>
</td>
<td>
<a href="lowongan_edit.php?id=<?=$id?>" class="btn-edit-small">Edit</a>
<a href="lowongan_hapus.php?id=<?=$id?>" class="btn-hapus-small"
 onclick="return confirm('Hapus lowongan ini?')">Hapus</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6">Belum ada lowongan</td></tr>
<?php endif; ?>
</table>

</div>
</body>
</html>
