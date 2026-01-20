<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
  header("Location: ../auth/login.php");
  exit;
}

/*
  Kolom yang dipakai di tabel users:
  - nama
  - email
  - email_verified (0/1)
  - access_status ('pending','approved','rejected')
  - requested_at (opsional)
  - approved_at (opsional)
*/

// aksi approve/reject
$act = $_GET['act'] ?? '';
$id  = (int)($_GET['id'] ?? 0);

if($id > 0 && in_array($act, ['approve','reject'], true)){
  if($act === 'approve'){
    mysqli_query($conn, "UPDATE users SET access_status='approved', approved_at=NOW() WHERE id=$id");
    header("Location: akses_user.php?msg=approved");
    exit;
  } else {
    mysqli_query($conn, "UPDATE users SET access_status='rejected' WHERE id=$id");
    header("Location: akses_user.php?msg=rejected");
    exit;
  }
}

$msg = $_GET['msg'] ?? '';

// list user: pending dulu biar fokus
$data = mysqli_query($conn, "
  SELECT id, nama, email, email_verified, access_status, requested_at, approved_at
  FROM users
  ORDER BY 
    CASE access_status 
      WHEN 'pending' THEN 1
      WHEN 'approved' THEN 2
      WHEN 'rejected' THEN 3
      ELSE 4
    END,
    id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Approval Akses</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/style.css">

  <style>
    .page-header{
      display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
      margin-bottom:14px;
    }
    .badge{
      display:inline-flex;align-items:center;gap:8px;
      padding:7px 12px;border-radius:999px;
      font-size:12px;font-weight:900;
      border:1px solid #e5e7eb;background:#f8fafc;color:#0f172a;
    }
    .alert{
      padding:12px 14px;border-radius:14px;margin:12px 0;
      border:1px solid #e5e7eb;background:#f8fafc;font-weight:800;
    }
    .alert.ok{
      background:rgba(34,197,94,.10);
      border-color:rgba(34,197,94,.25);
      color:#14532d;
    }
    .alert.no{
      background:rgba(239,68,68,.10);
      border-color:rgba(239,68,68,.25);
      color:#7f1d1d;
    }

    .table-users{width:100%;border-collapse:collapse;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 12px 30px rgba(2,6,23,.08)}
    .table-users th{background:linear-gradient(180deg,#0a1f3d,#020617);color:#fff!important;text-transform:uppercase;font-size:12px;letter-spacing:.7px;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.12)}
    .table-users td{padding:14px 16px;border-bottom:1px solid #e5e7eb;text-align:left;font-size:14px;color:#0f172a;font-weight:700;vertical-align:middle}
    .table-users tr:hover td{background:#f8fafc}

    /* biar status rapih */
    .pill{
      display:inline-flex;align-items:center;justify-content:center;
      padding:7px 12px;border-radius:999px;font-size:12px;font-weight:900;
      border:1px solid #e5e7eb;background:#f8fafc;color:#0f172a;
      white-space:nowrap;
    }
    .pill.pending{background:rgba(234,179,8,.12);border-color:rgba(234,179,8,.25);color:#92400e;}
    .pill.approved{background:rgba(34,197,94,.12);border-color:rgba(34,197,94,.25);color:#166534;}
    .pill.rejected{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.25);color:#7f1d1d;}

    /* tombol */
    .btn-approve{
      display:inline-block;padding:8px 12px;border-radius:12px;
      font-size:13px;font-weight:900;text-decoration:none;
      background:rgba(34,197,94,.14);color:#166534;border:1px solid rgba(34,197,94,.28);
      margin-right:6px;
    }
    .btn-reject{
      display:inline-block;padding:8px 12px;border-radius:12px;
      font-size:13px;font-weight:900;text-decoration:none;
      background:rgba(239,68,68,.14);color:#7f1d1d;border:1px solid rgba(239,68,68,.28);
    }

    /* center cuma beberapa kolom */
    .table-users th:nth-child(3),
    .table-users td:nth-child(3),
    .table-users th:nth-child(4),
    .table-users td:nth-child(4),
    .table-users th:nth-child(5),
    .table-users td:nth-child(5){
      text-align:center;
    }
  </style>
</head>

<body class="admin-body">
<?php include 'sidebar.php'; ?>

<div class="content">
  <div class="page-header">
    <div>
      <h2>Approval Akses User</h2>
      <span class="badge">User harus: ✅ verified email + ✅ approved admin</span>
    </div>
  </div>

  <?php if($msg === 'approved'): ?>
    <div class="alert ok">✅ User berhasil di-Approve.</div>
  <?php elseif($msg === 'rejected'): ?>
    <div class="alert no">⛔ User berhasil di-Reject.</div>
  <?php endif; ?>

  <table class="table-users">
    <tr>
      <th>Nama</th>
      <th>Email</th>
      <th>Verified</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>

    <?php if($data && mysqli_num_rows($data) > 0): ?>
      <?php while($u = mysqli_fetch_assoc($data)): ?>
        <?php
          $id = (int)($u['id'] ?? 0);
          $verified = (int)($u['email_verified'] ?? 0);
          $st = $u['access_status'] ?? 'pending';

          $stClass = 'pending';
          if($st === 'approved') $stClass = 'approved';
          if($st === 'rejected') $stClass = 'rejected';
        ?>
        <tr>
          <td><?= htmlspecialchars($u['nama'] ?? '-'); ?></td>
          <td><?= htmlspecialchars($u['email'] ?? '-'); ?></td>
          <td><?= $verified === 1 ? '✅' : '❌'; ?></td>
          <td>
            <span class="pill <?= $stClass; ?>">
              <?= htmlspecialchars($st); ?>
            </span>
          </td>
          <td>
            <?php if($verified === 1 && $st !== 'approved'): ?>
              <a class="btn-approve"
                 href="akses_user.php?act=approve&id=<?= $id; ?>"
                 onclick="return confirm('Approve akses user ini?')">Approve</a>
            <?php endif; ?>

            <?php if($st !== 'rejected'): ?>
              <a class="btn-reject"
                 href="akses_user.php?act=reject&id=<?= $id; ?>"
                 onclick="return confirm('Reject user ini?')">Reject</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="5" style="text-align:center;padding:20px;">Belum ada user.</td>
      </tr>
    <?php endif; ?>
  </table>
</div>

</body>
</html>
