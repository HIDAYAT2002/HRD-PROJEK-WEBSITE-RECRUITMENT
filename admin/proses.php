<?php
include '../config/koneksi.php';

$lowongan_id = (int)($_POST['lowongan_id'] ?? 0);
$nama        = trim($_POST['nama'] ?? '');
$email       = trim($_POST['email'] ?? '');
$telepon     = trim($_POST['telepon'] ?? '');
$kota        = trim($_POST['kota'] ?? '');
$tgl_lahir   = $_POST['tgl_lahir'] ?? '';
$pendidikan  = trim($_POST['pendidikan'] ?? '');
$jurusan     = trim($_POST['jurusan'] ?? '');

// TEXT CV untuk screening (tanpa DB -> disimpan ke .txt)
$cv_text = trim($_POST['cv_text'] ?? '');

// validasi minimal (lu bisa naikin)
if($lowongan_id <= 0 || $nama==='' || $email==='' || $tgl_lahir==='' || $pendidikan==='' || $jurusan===''){
    die("Data tidak lengkap");
}
if(mb_strlen($cv_text) < 300){
    die("Isi CV wajib minimal 300 karakter (untuk screening).");
}

/* UPLOAD CV PDF */
if(!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK){
    die("Upload CV gagal. Coba ulang.");
}

$ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
if($ext !== 'pdf'){
    die("CV harus PDF");
}

if($_FILES['cv']['size'] < 1000){
    die("File CV terlalu kecil / rusak. Upload PDF lain.");
}

if(!is_dir("../uploads")){
    mkdir("../uploads", 0777, true);
}

$baseName = time().'_'.$lowongan_id;    // base nama file
$cvName   = $baseName.'.pdf';
$destPdf  = "../uploads/".$cvName;

if(!move_uploaded_file($_FILES['cv']['tmp_name'], $destPdf)){
    die("Gagal menyimpan file CV ke server.");
}

// pastiin file kebentuk
if(!file_exists($destPdf) || filesize($destPdf) < 1000){
    @unlink($destPdf);
    die("File CV tersimpan tapi rusak. Upload ulang.");
}

/* SIMPAN CV TEXT jadi file .txt (pasangan PDF) */
$txtName = $baseName . '.txt';
$destTxt = "../uploads/" . $txtName;

// normalisasi text biar rapih & aman
$cv_text_save = str_replace(["\r\n", "\r"], "\n", $cv_text);
$cv_text_save = trim($cv_text_save);

if(@file_put_contents($destTxt, $cv_text_save) === false){
    @unlink($destPdf);
    die("Gagal menyimpan teks CV untuk screening.");
}

// extra check
if(!file_exists($destTxt) || filesize($destTxt) < 10){
    @unlink($destPdf);
    @unlink($destTxt);
    die("Teks CV tersimpan tapi kosong/rusak. Coba ulang.");
}

/* SIMPAN DB (tetap cuma simpen PDF ke kolom cv) */
$stmt = mysqli_prepare($conn,"
INSERT INTO pelamar
(lowongan_id, nama, email, telepon, kota, tgl_lahir, pendidikan, jurusan, cv, tanggal)
VALUES (?,?,?,?,?,?,?,?,?,NOW())
");

mysqli_stmt_bind_param(
  $stmt,
  "issssssss",
  $lowongan_id, $nama, $email, $telepon, $kota, $tgl_lahir, $pendidikan, $jurusan, $cvName
);

$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if(!$ok){
    // rollback file kalau insert gagal
    @unlink($destPdf);
    @unlink($destTxt);
    die("Gagal menyimpan data pelamar.");
}

header("Location: ../index.php?success=apply");
exit;
