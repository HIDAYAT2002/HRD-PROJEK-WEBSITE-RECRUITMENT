<?php
// admin/view_cv.php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

// (opsional) proteksi login
// if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
//     header("Location: ../auth/login.php");
//     exit;
// }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit("CV tidak ditemukan.");
}

$q = mysqli_query($conn, "SELECT cv FROM pelamar WHERE id=$id");
$row = mysqli_fetch_assoc($q);

if (!$row || empty($row['cv'])) {
    http_response_code(404);
    exit("CV tidak tersedia.");
}

$filename = basename($row['cv']); // keamanan
$path = realpath(__DIR__ . "/../uploads/" . $filename);

$uploadsDir = realpath(__DIR__ . "/../uploads/");
if ($path === false || $uploadsDir === false || strpos($path, $uploadsDir) !== 0) {
    http_response_code(403);
    exit("Akses ditolak.");
}

if (!file_exists($path)) {
    http_response_code(404);
    exit("File CV tidak ditemukan.");
}

if (filesize($path) < 100) { // biasanya PDF minimal > 100 byte
    http_response_code(500);
    exit("File CV rusak / kosong (0 KB). Upload ulang CV.");
}

// bersihin output biar gak ngerusak PDF
if (ob_get_length()) { ob_end_clean(); }

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="'.$filename.'"');
header('Content-Length: ' . filesize($path));
header('Accept-Ranges: bytes');

readfile($path);
exit;
