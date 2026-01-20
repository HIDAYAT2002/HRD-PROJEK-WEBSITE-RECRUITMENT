<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
  header("Location: ../auth/login.php");
  exit;
}

$id = (int)($_GET['id'] ?? 0);
$to = $_GET['to'] ?? ''; // 'aktif' / 'nonaktif'

if($id <= 0){
  header("Location: lowongan.php");
  exit;
}

if($to !== 'aktif' && $to !== 'nonaktif'){
  header("Location: lowongan.php");
  exit;
}

$to_safe = mysqli_real_escape_string($conn, $to);
mysqli_query($conn, "UPDATE lowongan SET status='$to_safe' WHERE id=$id");

header("Location: lowongan.php?msg=status_ok");
exit;
