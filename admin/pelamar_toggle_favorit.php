<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if($id <= 0){
    header("Location: pelamar.php");
    exit;
}

// toggle favorit
mysqli_query($conn,"
  UPDATE pelamar
  SET favorit = IF(favorit=1, 0, 1)
  WHERE id=$id
");

header("Location: pelamar.php");
exit;
