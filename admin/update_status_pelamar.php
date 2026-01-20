<?php
session_start();
include '../config/koneksi.php';

// optional proteksi
// if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
//     header("Location: ../auth/login.php"); exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pelamar.php?error=badrequest");
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = $_POST['status'] ?? '';

$allowed = ['Sudah di Cek','Diproses','Interview','MCU','Psikotes','Diterima','PKWT Aktif','PKWT Selesai'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    header("Location: pelamar.php?error=invalid");
    exit;
}

// kalau pilih PKWT Aktif, wajib isi tanggal
$pkwt_mulai  = $_POST['pkwt_mulai'] ?? null;
$pkwt_selesai= $_POST['pkwt_selesai'] ?? null;

if ($status === 'PKWT Aktif') {
    if (empty($pkwt_mulai) || empty($pkwt_selesai)) {
        header("Location: pelamar.php?error=pkwt_required");
        exit;
    }
    if (strtotime($pkwt_selesai) < strtotime($pkwt_mulai)) {
        header("Location: pelamar.php?error=pkwt_date");
        exit;
    }

    // update status + pkwt
    $stmt = mysqli_prepare($conn, "
        UPDATE pelamar
        SET status=?, status_updated_at=NOW(),
            pkwt_status='PKWT Aktif',
            pkwt_mulai=?, pkwt_selesai=?
        WHERE id=?
    ");
    mysqli_stmt_bind_param($stmt, "sssi", $status, $pkwt_mulai, $pkwt_selesai, $id);

} else if ($status === 'PKWT Selesai') {

    $stmt = mysqli_prepare($conn, "
        UPDATE pelamar
        SET status=?, status_updated_at=NOW(),
            pkwt_status='PKWT Selesai'
        WHERE id=?
    ");
    mysqli_stmt_bind_param($stmt, "si", $status, $id);

} else {

    // status biasa
    $stmt = mysqli_prepare($conn, "
        UPDATE pelamar
        SET status=?, status_updated_at=NOW()
        WHERE id=?
    ");
    mysqli_stmt_bind_param($stmt, "si", $status, $id);
}

if (mysqli_stmt_execute($stmt)) {
    header("Location: pelamar.php?success=updated");
    exit;
}

header("Location: pelamar.php?error=updatefailed");
exit;
