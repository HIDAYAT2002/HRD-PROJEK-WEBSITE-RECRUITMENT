<?php
session_start();
include '../config/koneksi.php';

// wajib login
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: lowongan.php");
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM lowongan WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header("Location: lowongan.php");
exit;
