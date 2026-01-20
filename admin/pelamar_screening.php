<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
  header("Location: ../auth/login.php");
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if($id <= 0){
  http_response_code(400);
  die("ID tidak valid");
}

/**
 * ===== Helper: baca text dari CV =====
 * - PDF text-based: coba pdftotext (server harus ada)
 * - DOCX: parse sederhana dari document.xml
 * - Kalau gagal: balikin kosong -> screening pakai field form saja
 */
function extract_cv_text($filePath){
  if(!is_file($filePath)) return "";

  $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
  $text = "";

  if($ext === 'pdf'){
    // butuh pdftotext (poppler). kalau ga ada, akan gagal -> fallback kosong
    $tmp = tempnam(sys_get_temp_dir(), 'cv_').'.txt';
    $cmd = "pdftotext -layout " . escapeshellarg($filePath) . " " . escapeshellarg($tmp) . " 2>&1";
    @exec($cmd, $out, $code);
    if($code === 0 && is_file($tmp)){
      $text = @file_get_contents($tmp);
      @unlink($tmp);
    } else {
      // pdftotext ga ada / gagal
      $text = "";
    }
  } elseif($ext === 'docx'){
    // DOCX itu zip
    $zip = new ZipArchive();
    if($zip->open($filePath) === true){
      $xml = $zip->getFromName('word/document.xml');
      $zip->close();
      if($xml){
        // hapus tag xml -> jadi text
        $text = strip_tags($xml);
      }
    }
  } else {
    // txt / doc (ga support) -> kosong
    $text = "";
  }

  // normalisasi
  $text = preg_replace('/\s+/', ' ', (string)$text);
  return trim($text);
}

function contains_any($haystack, $needles){
  foreach($needles as $n){
    if($n !== '' && mb_stripos($haystack, $n) !== false) return true;
  }
  return false;
}

function count_hits($haystack, $needles){
  $c = 0;
  foreach($needles as $n){
    if($n === '') continue;
    if(mb_stripos($haystack, $n) !== false) $c++;
  }
  return $c;
}

/**
 * ===== Rules screening (lu bisa edit keywordnya) =====
 * Output:
 * - score (0-100)
 * - kategori: "Lolos", "Review", "Tolak"
 * - alasan singkat (bullet)
 */
function screening_rules($posisi, $cvText, $row){
  $pos = mb_strtolower(trim((string)$posisi));
  $t = mb_strtolower($cvText);

  $alasan = [];
  $score = 0;

  // Basic poin dari data form (kalau lu punya kolom2 ini)
  $pendidikan = mb_strtolower(trim((string)($row['pendidikan'] ?? '')));
  $jurusan    = mb_strtolower(trim((string)($row['jurusan'] ?? '')));
  $kota       = mb_strtolower(trim((string)($row['kota'] ?? '')));

  // poin general: ada email/telepon/kota
  if(!empty($row['email']))   { $score += 5; $alasan[] = "Data kontak lengkap (+5)"; }
  if(!empty($row['telepon'])) { $score += 5; }
  if(!empty($row['kota']))    { $score += 5; }

  // poin general: pendidikan
  if(contains_any($pendidikan, ['s1','sarjana'])) { $score += 10; $alasan[]="Pendidikan S1 (+10)"; }
  else if(contains_any($pendidikan, ['d3','diploma'])) { $score += 7; $alasan[]="Pendidikan D3 (+7)"; }
  else if(contains_any($pendidikan, ['sma','smk'])) { $score += 4; $alasan[]="Pendidikan SMA/SMK (+4)"; }

  // Kalau CV text kebaca
  if($t !== ""){
    $score += 10; // bonus karena CV terbaca
    $alasan[] = "CV berhasil dibaca (+10)";
  } else {
    $alasan[] = "CV tidak bisa dibaca otomatis (fallback pakai data form)";
  }

  // ===== per posisi =====
  if(strpos($pos, 'sales') !== false){
    $kw = ['crm','closing','lead','pipeline','canvass','canvassing','target','cold call','negotiation','presentasi','b2b','b2c','deal'];
    $hit = count_hits($t, $kw);
    $add = min(30, $hit * 5); // max +30
    $score += $add;
    $alasan[] = "Skill Sales terdeteksi: {$hit} keyword (+{$add})";

    // bonus kalau ada “marketing” / “account executive”
    if(contains_any($t, ['account executive','sales executive','marketing','business development'])){
      $score += 10; $alasan[]="Pengalaman/role sales/BD terdeteksi (+10)";
    }

  } elseif(strpos($pos, 'admin') !== false){
    $kw = ['excel','vlookup','pivot','data entry','arsip','administrasi','word','google sheet','laporan','input data','teliti'];
    $hit = count_hits($t, $kw);
    $add = min(30, $hit * 5);
    $score += $add;
    $alasan[] = "Skill Admin terdeteksi: {$hit} keyword (+{$add})";

  } elseif(strpos($pos, 'it') !== false || strpos($pos, 'programmer') !== false || strpos($pos, 'developer') !== false){
    $kw = ['php','mysql','laravel','git','api','javascript','html','css','bootstrap','mvc','rest','linux'];
    $hit = count_hits($t, $kw);
    $add = min(35, $hit * 5); // max +35
    $score += $add;
    $alasan[] = "Skill IT terdeteksi: {$hit} keyword (+{$add})";

    if(contains_any($t, ['github','gitlab','portfolio','project','deploy','hosting'])){
      $score += 10; $alasan[]="Portfolio/Project terdeteksi (+10)";
    }

  } else {
    // posisi lain: general keyword kerja
    $kw = ['pengalaman','experience','project','sertifikat','certificate','training','magang','intern'];
    $hit = count_hits($t, $kw);
    $add = min(20, $hit * 4);
    $score += $add;
    $alasan[] = "Indikator pengalaman umum: {$hit} keyword (+{$add})";
  }

  // normalisasi score 0-100
  if($score > 100) $score = 100;
  if($score < 0) $score = 0;

  // kategori
  $kategori = "Review";
  if($score >= 70) $kategori = "Lolos";
  if($score < 45)  $kategori = "Tolak";

  return [$score, $kategori, $alasan];
}

// Ambil data pelamar + posisi
$q = mysqli_query($conn, "
  SELECT pelamar.*, lowongan.posisi
  FROM pelamar
  JOIN lowongan ON pelamar.lowongan_id = lowongan.id
  WHERE pelamar.id = $id
  LIMIT 1
");
$p = $q ? mysqli_fetch_assoc($q) : null;
if(!$p){
  http_response_code(404);
  die("Pelamar tidak ditemukan");
}

// Path CV
$cvRel = (string)($p['cv'] ?? '');
$cvText = "";

// Coba beberapa kemungkinan lokasi CV (sesuaikan kalau folder lu beda)
$possible = [];
if($cvRel !== ""){
  $possible[] = __DIR__ . '/../uploads/' . basename($cvRel);
  $possible[] = __DIR__ . '/../cv/' . basename($cvRel);
  $possible[] = __DIR__ . '/../assets/cv/' . basename($cvRel);
  $possible[] = __DIR__ . '/../' . ltrim($cvRel, '/');
}

$cvPath = "";
foreach($possible as $pp){
  if(is_file($pp)){ $cvPath = $pp; break; }
}

if($cvPath !== ""){
  $cvText = extract_cv_text($cvPath);
}

list($score, $kategori, $alasan) = screening_rules($p['posisi'] ?? '', $cvText, $p);

// (OPSIONAL) Simpan hasil ke DB kalau kolomnya ada
// - kalau kolom belum ada, query ini bakal error tapi gak ngehancurin proses download.
@mysqli_query($conn, "
  UPDATE pelamar SET
    screening_score = ".(int)$score.",
    screening_kategori = '".mysqli_real_escape_string($conn, $kategori)."',
    screening_detail = '".mysqli_real_escape_string($conn, implode(' | ', $alasan))."',
    screening_at = NOW()
  WHERE id = $id
");

// Auto download hasil screening (CSV)
$namaFile = "screening_pelamar_{$id}_" . date('Ymd_His') . ".csv";
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$namaFile\"");

$out = fopen('php://output', 'w');
fputcsv($out, ["ID","Nama","Posisi","Email","Telepon","Kota","Score","Kategori","Alasan"]);
fputcsv($out, [
  $p['id'] ?? '',
  $p['nama'] ?? '',
  $p['posisi'] ?? '',
  $p['email'] ?? '',
  $p['telepon'] ?? '',
  $p['kota'] ?? '',
  $score,
  $kategori,
  implode("; ", $alasan)
]);

// kalau lu mau juga dump sedikit CV text (biar HR bisa cek)
fputcsv($out, []);
fputcsv($out, ["CV_TEXT_SNIPPET"]);
$snippet = mb_substr(trim($cvText), 0, 800);
fputcsv($out, [$snippet]);

fclose($out);
exit;
