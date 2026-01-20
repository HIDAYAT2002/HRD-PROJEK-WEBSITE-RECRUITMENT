<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
    http_response_code(403);
    die('Akses ditolak');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0){
    die('ID tidak valid');
}

$stmt = mysqli_prepare($conn, "SELECT cv FROM pelamar WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$p = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if(!$p || empty($p['cv'])){
    die('File tidak ditemukan');
}

$uploadsDir = realpath(__DIR__ . '/../uploads');
$filePath   = realpath($uploadsDir . '/' . $p['cv']);

// cegah path traversal
if(!$filePath || strpos($filePath, $uploadsDir) !== 0){
    die('Akses file tidak sah');
}

if(!file_exists($filePath)){
    die('File CV tidak ada');
}

// kirim file
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
header('Content-Length: ' . filesize($filePath));
header('Pragma: public');
header('Cache-Control: must-revalidate');

readfile($filePath);
exit;
