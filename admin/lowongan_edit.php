<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
    header("Location: ../auth/login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0){
    header("Location: lowongan.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM lowongan WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$l = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if(!$l){
    header("Location: lowongan.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit Lowongan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/style.css">

    <style>
      /* Overlay biar background masih keliatan */
      .lw-overlay{
        position: fixed;
        inset: 0;
        background: rgba(2,6,23,.45);
        backdrop-filter: blur(2px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 22px;
        z-index: 9999;
      }

      /* Modal ukuran wajar */
      .lw-modal{
        width: min(760px, 92vw);
        max-height: min(82vh, 760px);
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 22px 70px rgba(0,0,0,.35);
        border: 1px solid rgba(15,23,42,.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
      }

      .lw-head{
        padding: 14px 16px;
        background: linear-gradient(180deg, #0b1b3a, #0a1530);
        color: #fff;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap: 10px;
      }

      .lw-title{
        font-weight: 900;
        letter-spacing: .3px;
        font-size: 14px;
        text-transform: uppercase;
      }

      .lw-actions{
        display:flex;
        gap: 10px;
        align-items:center;
      }

      .lw-btn{
        appearance:none;
        border: 0;
        cursor:pointer;
        padding: 8px 12px;
        border-radius: 10px;
        font-weight: 800;
        font-size: 13px;
        transition: .2s;
        text-decoration: none;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        white-space: nowrap;
      }

      .lw-btn-secondary{
        background: rgba(255,255,255,.10);
        color:#fff;
      }
      .lw-btn-secondary:hover{ background: rgba(255,255,255,.18); }

      .lw-body{
        padding: 18px 18px 16px;
        overflow: auto;
      }

      .lw-sub{
        margin: 0 0 14px;
        font-size: 13px;
        color: #6b7280;
      }

      /* Form layout rapi */
      .form-card{
        background: transparent;
        box-shadow: none;
        border-radius: 0;
        padding: 0;
        margin: 0;
      }

      .form-grid{
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
      }

      .form-group{
        margin-bottom: 14px;
      }

      .form-group label{
        display:block;
        font-size: 13px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 7px;
      }

      .form-group input,
      .form-group textarea{
        width:100%;
        padding: 12px 12px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        font-size: 14px;
        outline: none;
        background: #fff;
        transition: .2s;
      }

      .form-group textarea{
        min-height: 120px;
        resize: vertical;
      }

      .form-group input:focus,
      .form-group textarea:focus{
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37,99,235,.14);
      }

      .lw-foot{
        padding: 14px 18px;
        border-top: 1px solid #eef2f7;
        background: #fafbff;
      }

      /* Tombol biru */
      .btn-primary{
        width:100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 900;
        background: linear-gradient(135deg,#1e3a8a,#2563eb);
        color: #fff;
        transition:.25s;
      }
      .btn-primary:hover{
        transform: translateY(-1px);
        box-shadow: 0 14px 32px rgba(37,99,235,.30);
      }

      @media (max-width: 720px){
        .form-grid{ grid-template-columns: 1fr; }
      }
      @media (max-width: 480px){
        .lw-modal{ width: 94vw; border-radius: 14px; }
        .lw-body{ padding: 16px 14px 14px; }
        .lw-foot{ padding: 12px 14px; }
      }
    </style>
</head>

<body class="admin-body">

<?php include 'sidebar.php'; ?>

<!-- Modal overlay -->
<div class="lw-overlay" id="lwOverlay" role="dialog" aria-modal="true">

  <div class="lw-modal" onclick="event.stopPropagation()">
    <div class="lw-head">
      <div class="lw-title">Edit Lowongan</div>

      <div class="lw-actions">
                <button type="button" class="lw-btn lw-btn-secondary" onclick="closeModal()">Tutup ✕</button>
      </div>
    </div>

    <div class="lw-body">
      <p class="lw-sub">Ubah data lowongan, lalu simpan perubahan.</p>

      <!-- PENTING: action + name + hidden id tetap sama -->
      <form action="lowongan_update.php" method="POST" class="form-card" id="formEditLowongan">
        <input type="hidden" name="id" value="<?= (int)$l['id']; ?>">

        <div class="form-grid">
          <div class="form-group">
            <label>Posisi</label>
            <input type="text" name="posisi" required value="<?= htmlspecialchars($l['posisi']); ?>">
          </div>

          <div class="form-group">
            <label>Lokasi (Kota/Kab)</label>
            <input type="text" name="kota" required value="<?= htmlspecialchars($l['kota']); ?>">
          </div>

          <div class="form-group" style="grid-column: 1 / -1;">
            <label>Deadline</label>
            <input type="date" name="deadline" value="<?= htmlspecialchars($l['deadline']); ?>">
          </div>

          <div class="form-group" style="grid-column: 1 / -1;">
            <label>Job Desk</label>
            <textarea name="pekerjaan" rows="6" required><?= htmlspecialchars($l['pekerjaan']); ?></textarea>
          </div>

          <div class="form-group" style="grid-column: 1 / -1;">
            <label>Kriteria</label>
            <textarea name="kriteria" rows="6"><?= htmlspecialchars($l['kriteria']); ?></textarea>
          </div>
        </div>
      </form>
    </div>

    <div class="lw-foot">
      <button type="submit" class="btn-primary" form="formEditLowongan">Simpan Perubahan</button>
    </div>
  </div>

</div>

<script>
  // Klik area gelap = tutup
  document.getElementById('lwOverlay').addEventListener('click', closeModal);

  // ESC = tutup
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeModal();
  });

  function closeModal(){
    // kalau user datang dari halaman lowongan, balik aja.
    // fallback: ke lowongan.php
    if (window.history.length > 1) {
      window.history.back();
    } else {
      window.location.href = "lowongan.php";
    }
  }
</script>

</body>
</html>
