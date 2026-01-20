<?php
include '../config/koneksi.php';

$lowongan_id = (int)($_POST['lowongan_id'] ?? 0);
$nama        = trim($_POST['nama'] ?? '');
$email       = trim($_POST['email'] ?? '');
$telepon     = trim($_POST['telepon'] ?? '');
$kota        = trim($_POST['kota'] ?? '');
$tgl_lahir   = trim($_POST['tgl_lahir'] ?? '');
$pendidikan  = trim($_POST['pendidikan'] ?? '');
$jurusan     = trim($_POST['jurusan'] ?? '');

if ($lowongan_id <= 0 || $nama === '' || $email === '' || $tgl_lahir === '' || $pendidikan === '' || $jurusan === '') {
    die("Data tidak lengkap");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Email tidak valid");
}

/* UPLOAD CV */
if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
    die("Upload CV gagal. Coba ulang.");
}

$cv = $_FILES['cv'];

$ext = strtolower(pathinfo($cv['name'], PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    die("CV harus PDF");
}

if ($cv['size'] < 1000) {
    die("File CV terlalu kecil / rusak. Upload PDF lain.");
}

// batas maksimum biar gak di-spam (ubah kalau mau)
$maxSize = 3 * 1024 * 1024; // 3MB
if ($cv['size'] > $maxSize) {
    die("File CV terlalu besar. Maksimal 3MB.");
}

// cek MIME PDF (fallback kalau finfo tidak tersedia)
$mimeOk = true;
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $mime = finfo_file($finfo, $cv['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['application/pdf', 'application/x-pdf'];
        $mimeOk = in_array($mime, $allowedMimes, true);
    }
}
if (!$mimeOk) {
    die("File bukan PDF yang valid");
}

// buat folder uploads kalau belum ada (permission aman)
$uploadsPath = dirname(__DIR__) . "/uploads";
if (!is_dir($uploadsPath)) {
    if (!mkdir($uploadsPath, 0755, true)) {
        die("Gagal membuat folder uploads");
    }
}

// nama file unik (hindari ketimpa)
try {
    $random = bin2hex(random_bytes(8));
} catch (Exception $e) {
    // fallback kalau random_bytes error
    $random = uniqid();
}
$cvName = date('Ymd_His') . "_L{$lowongan_id}_{$random}.pdf";
$dest   = $uploadsPath . "/" . $cvName;

if (!move_uploaded_file($cv['tmp_name'], $dest)) {
    die("Gagal menyimpan file CV ke server.");
}

// pastiin file beneran kebentuk
if (!file_exists($dest) || filesize($dest) < 1000) {
    @unlink($dest);
    die("File CV tersimpan tapi rusak. Upload ulang.");
}

/* SIMPAN DB */
$stmt = mysqli_prepare($conn, "
    INSERT INTO pelamar
    (lowongan_id, nama, email, telepon, kota, tgl_lahir, pendidikan, jurusan, cv, tanggal)
    VALUES (?,?,?,?,?,?,?,?,?,NOW())
");

if (!$stmt) {
    @unlink($dest);
    die("Gagal menyiapkan query.");
}

mysqli_stmt_bind_param(
    $stmt,
    "issssssss",
    $lowongan_id, $nama, $email, $telepon, $kota, $tgl_lahir, $pendidikan, $jurusan, $cvName
);

$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    @unlink($dest);
    die("Gagal menyimpan data pelamar.");
}

header("Location: sukses.php");
exit;
