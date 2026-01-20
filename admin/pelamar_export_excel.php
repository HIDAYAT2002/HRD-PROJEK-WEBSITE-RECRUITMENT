<?php
require_once __DIR__ . "/guard.php";
include '../config/koneksi.php';

// ambil filter
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$lowongan_id = isset($_GET['lowongan_id']) ? (int)$_GET['lowongan_id'] : 0;

// build WHERE
$where = "WHERE 1=1 ";
if($bulan >= 1 && $bulan <= 12){
  $where .= " AND MONTH(pelamar.tanggal) = $bulan ";
}
if($tahun >= 2000 && $tahun <= 2100){
  $where .= " AND YEAR(pelamar.tanggal) = $tahun ";
}
if($lowongan_id > 0){
  $where .= " AND pelamar.lowongan_id = $lowongan_id ";
}

// query utama
$q = mysqli_query($conn, "
  SELECT 
    pelamar.*,
    pelamar.tgl_lahir AS tgl_lahir,
    lowongan.posisi,
    lowongan.created_at AS lowongan_created_at,
    lowongan.deadline   AS lowongan_deadline
  FROM pelamar
  JOIN lowongan ON pelamar.lowongan_id = lowongan.id
  $where
  ORDER BY pelamar.favorit DESC, pelamar.tanggal DESC
");

// nama file
$bulanNama = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$tagBulan = ($bulan>=1 && $bulan<=12) ? $bulanNama[$bulan] : "AllMonth";
$tagTahun = ($tahun>=2000 && $tahun<=2100) ? $tahun : "AllYear";
$tagPos   = ($lowongan_id>0) ? ("Posisi".$lowongan_id) : "AllPosisi";
$filename = "Data_Pelamar_{$tagBulan}_{$tagTahun}_{$tagPos}.xls";

// header excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// base url otomatis untuk link CV
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = $scheme.'://'.$_SERVER['HTTP_HOST'];
$projectPath = "/career"; // <-- GANTI kalau folder project beda

// ====== LABEL JUDUL BERDASARKAN FILTER ======
$posisiLabel = "Semua Posisi";
if($lowongan_id > 0){
  $rowPos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT posisi FROM lowongan WHERE id=$lowongan_id"));
  if($rowPos && !empty($rowPos['posisi'])){
    $posisiLabel = $rowPos['posisi'];
  } else {
    $posisiLabel = "Posisi ID: ".$lowongan_id;
  }
}

$bulanLabel = "Semua Bulan";
if($bulan >= 1 && $bulan <= 12){
  $bulanLabel = $bulanNama[$bulan];
}

$tahunLabel = "Semua Tahun";
if($tahun >= 2000 && $tahun <= 2100){
  $tahunLabel = $tahun;
}

$periodeLabel = ($bulanLabel === "Semua Bulan" && $tahunLabel === "Semua Tahun")
  ? "Semua Periode"
  : (($bulanLabel !== "Semua Bulan" ? $bulanLabel : "Semua Bulan") . " " . ($tahunLabel !== "Semua Tahun" ? $tahunLabel : ""));

// ====== STYLE GLOBAL ======
$tblStyle   = "font-family:'Times New Roman'; font-size:11pt; border-collapse:collapse;";
$judulStyle = "font-family:'Times New Roman'; font-size:16pt; font-weight:bold; text-align:center;";
$subStyle   = "font-family:'Times New Roman'; font-size:11pt; font-weight:bold;";
$thStyle    = "font-family:'Times New Roman'; font-size:11pt; font-weight:bold; text-align:center; background:#f2f2f2;";
$tdCenter   = "font-family:'Times New Roman'; font-size:11pt; text-align:center;";
$tdLeft     = "font-family:'Times New Roman'; font-size:11pt; text-align:left;";

// table excel
echo "<table border='1' style='$tblStyle'>";

// judul (colspan 14 karena tambah kolom CV)
echo "<tr>
        <td colspan='14' style=\"$judulStyle\">
          LAPORAN RECRUITMENT PT WIRASWASTA GEMILANG INDONESIA
        </td>
      </tr>";

echo "<tr>
        <td colspan='14' style=\"$subStyle\">
          Posisi: ".htmlspecialchars($posisiLabel)." | Periode: ".htmlspecialchars(trim($periodeLabel))."
        </td>
      </tr>";

// baris kosong
echo "<tr><td colspan='14' style=\"$tdLeft\">&nbsp;</td></tr>";

// header tabel (tambah CV)
echo "<tr>
        <th style=\"$thStyle\">No</th>
        <th style=\"$thStyle\">Nama</th>
        <th style=\"$thStyle\">Email</th>
        <th style=\"$thStyle\">Telepon</th>
        <th style=\"$thStyle\">Kota/Kabupaten</th>
        <th style=\"$thStyle\">Tanggal Lahir</th>
        <th style=\"$thStyle\">Umur</th>
        <th style=\"$thStyle\">Pendidikan</th>
        <th style=\"$thStyle\">Posisi</th>
        <th style=\"$thStyle\">Tgl Dibuka Lowongan</th>
        <th style=\"$thStyle\">Deadline Lowongan</th>
        <th style=\"$thStyle\">Tanggal Submit</th>
        <th style=\"$thStyle\">CV</th>
        <th style=\"$thStyle\">Favorit</th>
      </tr>";

$no = 1;
if($q && mysqli_num_rows($q) > 0){
  while($p = mysqli_fetch_assoc($q)){

    // tgl lahir support beberapa nama kolom
    $tgl_lahir = '';
    if(isset($p['tgl_lahir'])) $tgl_lahir = $p['tgl_lahir'];
    else if(isset($p['tanggal_lahir'])) $tgl_lahir = $p['tanggal_lahir'];
    else if(isset($p['tanggalLahir'])) $tgl_lahir = $p['tanggalLahir'];

    // umur
    $umur = '-';
    if(!empty($tgl_lahir) && $tgl_lahir != '0000-00-00'){
      try{
        $birthDate = new DateTime($tgl_lahir);
        $today = new DateTime();
        $umur = $today->diff($birthDate)->y . ' Tahun';
      } catch(Exception $e){
        $umur = '-';
      }
    }

    // pendidikan + jurusan
    $pendidikanFull = trim(
      ($p['pendidikan'] ?? '') .
      (!empty($p['jurusan']) ? ' - '.$p['jurusan'] : '')
    );

    // format tanggal
    $tglLahirView = (!empty($tgl_lahir) && $tgl_lahir != '0000-00-00') ? date('d-m-Y', strtotime($tgl_lahir)) : '-';
    $tglBuka      = (!empty($p['lowongan_created_at']) ? date('d-m-Y', strtotime($p['lowongan_created_at'])) : '-');
    $deadline     = (!empty($p['lowongan_deadline']) ? date('d-m-Y', strtotime($p['lowongan_deadline'])) : '-');
    $tglSubmit    = (!empty($p['tanggal']) ? date('d-m-Y', strtotime($p['tanggal'])) : '-');
    $favorit      = (!empty($p['favorit']) ? 'Ya' : 'Tidak');

    // TELEPON: pakai apostrophe biar 0 depan aman, tampilan tetap normal (apostrophe ga keliatan)
    $teleponRaw = $p['telepon'] ?? '';
    $teleponClean = preg_replace('/[^0-9+]/', '', $teleponRaw);
    $teleponExcel = "'".$teleponClean; // contoh: '0856...

    // CV LINK: pakai anchor biar tampil bagus & clickable (bukan rumus)
    $cvCell = "-";
    if(!empty($p['cv'])){
      $cvUrl  = $base . $projectPath . "/admin/view_cv.php?id=".(int)$p['id'];
      $cvCell = "<a href=\"".htmlspecialchars($cvUrl)."\">Lihat CV</a>";
    }

    echo "<tr>
            <td style=\"$tdCenter\">{$no}</td>
            <td style=\"$tdLeft\">".htmlspecialchars($p['nama'] ?? '')."</td>
            <td style=\"$tdLeft\">".htmlspecialchars($p['email'] ?? '')."</td>
            <td style=\"$tdLeft\">".htmlspecialchars($teleponExcel)."</td>
            <td style=\"$tdLeft\">".htmlspecialchars($p['kota'] ?? '')."</td>
            <td style=\"$tdCenter\">{$tglLahirView}</td>
            <td style=\"$tdCenter\">{$umur}</td>
            <td style=\"$tdLeft\">".htmlspecialchars($pendidikanFull)."</td>
            <td style=\"$tdLeft\">".htmlspecialchars($p['posisi'] ?? '')."</td>
            <td style=\"$tdCenter\">{$tglBuka}</td>
            <td style=\"$tdCenter\">{$deadline}</td>
            <td style=\"$tdCenter\">{$tglSubmit}</td>
            <td style=\"$tdCenter\">{$cvCell}</td>
            <td style=\"$tdCenter\">{$favorit}</td>
          </tr>";

    $no++;
  }
} else {
  echo "<tr><td colspan='14' style=\"$tdCenter\">Data tidak ditemukan</td></tr>";
}

echo "</table>";
exit;
