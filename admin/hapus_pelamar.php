<?php
session_start();
include '../config/koneksi.php';

// kalau kamu punya session admin, boleh nyalain ini:
// if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
//     header("Location: ../auth/login.php");
//     exit;
// }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0){
    header("Location: pelamar.php?error=notfound");
    exit;
}

// ambil dulu data cv nya
$stmt = mysqli_prepare($conn, "SELECT cv FROM pelamar WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if(!$row){
    header("Location: pelamar.php?error=notfound");
    exit;
}

$cv = $row['cv'] ?? '';

// hapus data pelamar
$stmt2 = mysqli_prepare($conn, "DELETE FROM pelamar WHERE id = ?");
mysqli_stmt_bind_param($stmt2, "i", $id);
$ok = mysqli_stmt_execute($stmt2);
mysqli_stmt_close($stmt2);

if($ok){
    // hapus file CV (kalau ada)
    if(!empty($cv)){
        $path = realpath(__DIR__ . "/../uploads") . DIRECTORY_SEPARATOR . $cv;

        // pastiin bener-bener di folder uploads (anti path traversal)
        $uploadsDir = realpath(__DIR__ . "/../uploads") . DIRECTORY_SEPARATOR;
        if($path && strpos($path, $uploadsDir) === 0 && file_exists($path)){
            @unlink($path);
        }
    }

    header("Location: pelamar.php?success=deleted");
    exit;
}

header("Location: pelamar.php?error=deletefailed");
exit;
