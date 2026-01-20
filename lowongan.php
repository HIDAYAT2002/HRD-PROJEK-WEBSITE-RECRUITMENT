<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
  header("Location: ../auth/login.php");
  exit;
}

// PK lowongan (aman)
$pk = 'id';
$k = mysqli_query($conn, "SHOW KEYS FROM lowongan WHERE Key_name='PRIMARY'");
if($k && mysqli_num_rows($k) > 0){
  $kk = mysqli_fetch_assoc($k);
  if(!empty($kk['Column_name'])) $pk = $kk['Column_name'];
}

// Ambil data lowongan
$data = mysqli_query($conn, "SELECT * FROM lowongan ORDER BY `$pk` DESC");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Kelola Lowongan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/style.css">

  <style>
    .table-lowongan{
      width:100%;
      border-collapse:collapse;
      background:#fff;
      border-radius:16px;
      overflow:hidden;
      box-shadow:0 12px 30px rgba(2,6,23,.08)
    }

    .table-lowongan th{
      background:linear-gradient(180deg,#0a1f3d,#020617);
      color:#fff!important;
      text-transform:uppercase;
      font-size:12px;
      letter-spacing:.7px;
      padding:14px 16px;
      border-bottom:1px solid rgba(255,255,255,.12)
    }

    .table-lowongan td{
      padding:14px 16px;
      border-bottom:1px solid #e5e7eb;
      text-align:left;
      font-size:14px;
      color:#0f172a;
      font-weight:600;
      vertical-align:middle
    }

    .table-lowongan tr:hover td{background:#f8fafc}

    /* ===== PATCH: TENGAHIN SEMUA KOLOM (POSISI, LOKASI, PUBLISH, DEADLINE, STATUS, AKSI) ===== */
    .table-lowongan th,
    .table-lowongan td{
      vertical-align:middle;
    }

    .table-lowongan th:nth-child(1),
    .table-lowongan td:nth-child(1),
    .table-lowongan th:nth-child(2),
    .table-lowongan td:nth-child(2),
    .table-lowongan th:nth-child(3),
    .table-lowongan td:nth-child(3),
    .table-lowongan th:nth-child(4),
    .table-lowongan td:nth-child(4),
    .table-lowongan th:nth-child(5),
    .table-lowongan td:nth-child(5),
    .table-lowongan th:nth-child(6),
    .table-lowongan td:nth-child(6){
      text-align:center;
    }

    .pill-date{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:8px 10px;
      border-radius:999px;
      font-size:12px;
      font-weight:900;
      border:1px solid #e5e7eb;
      background:#f8fafc;
      color:#0f172a;
      white-space:nowrap;
    }

    .btn-edit-small{
      display:inline-block;
      padding:8px 12px;
      border-radius:10px;
      font-size:13px;
      font-weight:800;
      text-decoration:none;
      margin-right:6px;
      background:rgba(37,99,235,.14);
      color:#1e40af;
      border:1px solid rgba(37,99,235,.22)
    }
    .btn-edit-small:hover{filter:brightness(.95)}

    .btn-hapus-small{
      display:inline-block;
      padding:8px 12px;
      border-radius:10px;
      font-size:13px;
      font-weight:800;
      text-decoration:none;
      background:rgba(239,68,68,.16);
      color:#7f1d1d;
      border:1px solid rgba(239,68,68,.24)
    }
    .btn-hapus-small:hover{filter:brightness(.95)}

    .btn-status-aktif{
      display:inline-block;
      padding:8px 12px;
      border-radius:10px;
      font-size:13px;
      font-weight:900;
      text-decoration:none;
      background:rgba(34,197,94,.16);
      color:#166534;
      border:1px solid rgba(34,197,94,.28)
    }
    .btn-status-nonaktif{
      display:inline-block;
      padding:8px 12px;
      border-radius:10px;
      font-size:13px;
      font-weight:900;
      text-decoration:none;
      background:rgba(148,163,184,.22);
      color:#334155;
      border:1px solid rgba(148,163,184,.40)
    }
  </style>
</head>

<body class="admin-body">
<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="page-header">
    <h2>Kelola Lowongan</h2>
    <a href="lowongan_tambah.php" class="btn-primary">+ Tambah Lowongan</a>
  </div>

  <?php if(isset($_GET['msg']) && $_GET['msg'] === 'status_ok'): ?>
    <div class="alert-success">Status lowongan berhasil diubah.</div>
  <?php endif; ?>

  <table class="table-lowongan">
    <tr>
      <th>Posisi</th>
      <th>Lokasi</th>
      <th>Publish</th>
      <th>Deadline</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>

    <?php if($data && mysqli_num_rows($data) > 0): ?>
      <?php while($l = mysqli_fetch_assoc($data)): ?>
        <?php
          $id = (int)($l[$pk] ?? 0);

          // DB lu ENUM('aktif','nonaktif')
          $status = $l['status'] ?? 'nonaktif';

          $deadline = $l['deadline'] ?? '';
          $created  = $l['created_at'] ?? '';

          $lokasi_tampil = $l['kota'] ?? ($l['lokasi'] ?? '');

          $deadlineText = $deadline ? date('d M Y', strtotime($deadline)) : '-';
          $createdText  = $created  ? date('d M Y', strtotime($created))  : '-';
        ?>
        <tr>
          <td><?= htmlspecialchars($l['posisi'] ?? ''); ?></td>
          <td><?= htmlspecialchars($lokasi_tampil); ?></td>

          <td>
            <span class="pill-date">📌 <?= $createdText; ?></span>
          </td>

          <td>
            <span class="pill-date">🗓 <?= $deadlineText; ?></span>
          </td>

          <td>
            <?php if($status === 'aktif'): ?>
              <a class="btn-status-aktif"
                 href="lowongan_status.php?id=<?= $id; ?>&to=nonaktif"
                 onclick="return confirm('Nonaktifkan lowongan ini?')">
                 Aktif
              </a>
            <?php else: ?>
              <a class="btn-status-nonaktif"
                 href="lowongan_status.php?id=<?= $id; ?>&to=aktif"
                 onclick="return confirm('Aktifkan lowongan ini?')">
                 Nonaktif
              </a>
            <?php endif; ?>
          </td>

          <td>
            <a href="lowongan_edit.php?id=<?= $id; ?>" class="btn-edit-small">Edit</a>
            <a href="lowongan_hapus.php?id=<?= $id; ?>"
               onclick="return confirm('Yakin mau hapus lowongan ini?')"
               class="btn-hapus-small">Hapus</a>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="6" style="text-align:center; padding:20px;">
          Belum ada lowongan
        </td>
      </tr>
    <?php endif; ?>
  </table>
</div>

</body>
</html>
