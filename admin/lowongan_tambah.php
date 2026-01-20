<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <title>Tambah Lowongan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- CSS global lama -->
  <link rel="stylesheet" href="../assets/style.css">

  <style>
    /* ===== Overlay: bikin data belakang masih keliatan ===== */
    .lw-overlay{
      position: fixed;
      inset: 0;
      background: rgba(2,6,23,.45);         /* transparan biar belakang terlihat */
      backdrop-filter: blur(2px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 22px;
      z-index: 9999;
    }

    /* ===== Modal: ukuran wajar (nggak segede detail pelamar) ===== */
    .lw-modal{
      width: min(720px, 92vw);
      max-height: min(82vh, 760px);
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 22px 70px rgba(0,0,0,.35);
      border: 1px solid rgba(15,23,42,.08);
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    /* ===== Header modal ===== */
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

    .lw-close{
      appearance:none;
      border: 0;
      cursor:pointer;
      background: rgba(255,255,255,.10);
      color:#fff;
      padding: 8px 12px;
      border-radius: 10px;
      font-weight: 800;
      font-size: 13px;
      transition: .2s;
    }
    .lw-close:hover{ background: rgba(255,255,255,.18); }

    /* ===== Body modal (scroll kalau kepanjangan) ===== */
    .lw-body{
      padding: 18px 18px 16px;
      overflow: auto;
    }

    .lw-sub{
      margin: 0 0 14px;
      font-size: 13px;
      color: #6b7280;
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
      min-height: 110px;
      resize: vertical;
    }
    .form-group input:focus,
    .form-group textarea:focus{
      border-color: #2563eb;
      box-shadow: 0 0 0 4px rgba(37,99,235,.14);
    }

    /* ===== Footer modal ===== */
    .lw-foot{
      padding: 14px 18px;
      border-top: 1px solid #eef2f7;
      background: #fafbff;
    }

    /* Tombol BIRU */
    .btn-primary.full{
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
    .btn-primary.full:hover{
      transform: translateY(-1px);
      box-shadow: 0 14px 32px rgba(37,99,235,.30);
    }

    /* Mobile */
    @media (max-width: 520px){
      .lw-modal{ width: 94vw; border-radius: 14px; }
      .lw-body{ padding: 16px 14px 14px; }
      .lw-foot{ padding: 12px 14px; }
    }
  </style>
</head>

<body class="admin-body">

<?php include 'sidebar.php'; ?>

<!-- Background halaman tetap ada (data terlihat), modal di atasnya -->
<div class="lw-overlay" id="lwOverlay" aria-modal="true" role="dialog">

  <div class="lw-modal" onclick="event.stopPropagation()">
    <div class="lw-head">
      <div class="lw-title">Tambah Lowongan</div>
      <!-- tombol tutup: balik ke halaman sebelumnya (atau kelola lowongan) -->
      <button type="button" class="lw-close" onclick="closeModal()">Tutup ✕</button>
    </div>

    <div class="lw-body">
      <p class="lw-sub">Isi data lowongan dengan lengkap.</p>

      <!-- PENTING: action & name input JANGAN DIUBAH -->
      <form action="lowongan_simpan.php" method="POST" id="formLowongan">

        <div class="form-group">
          <label>Posisi</label>
          <input type="text" name="posisi" placeholder="Contoh: Sales Supervisor" required>
        </div>

        <div class="form-group">
          <label>Lokasi (Kota / Kabupaten)</label>
          <input type="text" name="kota" placeholder="Contoh: Jakarta Selatan" required>
        </div>

        <div class="form-group">
          <label>Job Desk</label>
          <textarea name="pekerjaan" placeholder="Jelaskan pekerjaan..." required></textarea>
        </div>

        <div class="form-group">
          <label>Kriteria</label>
          <textarea name="kriteria" placeholder="Syarat pelamar..." required></textarea>
        </div>

        <div class="form-group">
          <label>Batas Waktu Pendaftaran</label>
          <input type="date" name="deadline">
        </div>

      </form>
    </div>

    <div class="lw-foot">
      <button type="submit" class="btn-primary full" form="formLowongan">
        Simpan Lowongan
      </button>
    </div>
  </div>

</div>

<script>
  // klik area gelap = tutup (kayak modal)
  document.getElementById('lwOverlay').addEventListener('click', closeModal);

  // ESC = tutup
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeModal();
  });

  function closeModal(){
    // balik ke halaman sebelumnya kalau ada, kalau tidak ke kelola lowongan
    if (window.history.length > 1) {
      window.history.back();
    } else {
      window.location.href = "lowongan.php";
    }
  }
</script>

</body>
</html>
