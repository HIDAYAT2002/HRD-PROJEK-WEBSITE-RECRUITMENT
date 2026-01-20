<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

// optional proteksi
// if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
//     header("Location: ../auth/login.php"); exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pelamar.php?error=badrequest");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$mulai = $_POST['pkwt_mulai'] ?? '';
$selesai = $_POST['pkwt_selesai'] ?? '';

if ($id <= 0 || empty($mulai) || empty($selesai)) {
    header("Location: pelamar.php?error=pkwt_invalid");
    exit;
}

if (strtotime($selesai) < strtotime($mulai)) {
    header("Location: pelamar.php?error=pkwt_date");
    exit;
}

$stmt = mysqli_prepare($conn, "
    UPDATE pelamar
    SET
      pkwt_status='PKWT Aktif',
      status='PKWT Aktif',
      status_updated_at=NOW(),
      pkwt_mulai=?,
      pkwt_selesai=?
    WHERE id=?
");
mysqli_stmt_bind_param($stmt, "ssi", $mulai, $selesai, $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: pelamar.php?success=pkwt_saved");
    exit;
}

header("Location: pelamar.php?error=pkwt_failed");
exit;
