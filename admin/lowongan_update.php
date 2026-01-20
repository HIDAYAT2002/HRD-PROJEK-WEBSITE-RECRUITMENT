<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
    header("Location: ../auth/login.php");
    exit;
}

$id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$posisi   = trim($_POST['posisi'] ?? '');
$kota     = trim($_POST['kota'] ?? '');
$deadline = trim($_POST['deadline'] ?? '');
$pekerjaan= trim($_POST['pekerjaan'] ?? '');
$kriteria = trim($_POST['kriteria'] ?? '');

if($id <= 0 || $posisi === '' || $kota === '' || $pekerjaan === ''){
    header("Location: lowongan.php");
    exit;
}

// deadline boleh kosong
if($deadline === '') $deadline = null;

$stmt = mysqli_prepare($conn, "
    UPDATE lowongan
    SET posisi = ?, kota = ?, deadline = ?, pekerjaan = ?, kriteria = ?
    WHERE id = ?
");

mysqli_stmt_bind_param($stmt, "sssssi", $posisi, $kota, $deadline, $pekerjaan, $kriteria, $id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if($ok){
    header("Location: lowongan.php?msg=update_sukses");
    exit;
}

header("Location: lowongan.php?msg=update_gagal");
exit;
