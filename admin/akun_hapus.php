<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
    header("Location: ../auth/login.php");
    exit;
}

$uid = (int)($_SESSION['user_id'] ?? 0);
$me  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$uid"));

if(!$me){
    header("Location: ../auth/login.php");
    exit;
}

// hanya manager boleh hapus akun
if(($me['role'] ?? '') !== 'manager'){
    header("Location: data_akun.php?msg=akses_ditolak");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0){
    header("Location: data_akun.php?msg=id_invalid");
    exit;
}

// jangan bisa hapus diri sendiri
if($id === $uid){
    header("Location: data_akun.php?msg=tidak_bisa_hapus_diri_sendiri");
    exit;
}

// cek user ada
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$u = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if(!$u){
    header("Location: data_akun.php?msg=akun_tidak_ditemukan");
    exit;
}

// eksekusi delete
$stmt2 = mysqli_prepare($conn, "DELETE FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt2, "i", $id);
$ok = mysqli_stmt_execute($stmt2);
mysqli_stmt_close($stmt2);

if($ok){
    header("Location: data_akun.php?msg=hapus_sukses");
    exit;
}

header("Location: data_akun.php?msg=hapus_gagal");
exit;
