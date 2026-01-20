<?php
session_start();
include '../config/koneksi.php';

$msg = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $nama  = trim($_POST['nama'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';

  if($nama=='' || $email=='' || $pass==''){
    $msg = "Semua field wajib diisi.";
  } else {
    $email_safe = mysqli_real_escape_string($conn, $email);
    $cek = mysqli_query($conn, "SELECT id FROM users WHERE email='$email_safe' LIMIT 1");
    if($cek && mysqli_num_rows($cek) > 0){
      $msg = "Email sudah terdaftar.";
    } else {
      $token = bin2hex(random_bytes(24));
      $hash  = password_hash($pass, PASSWORD_DEFAULT);

      $nama_safe = mysqli_real_escape_string($conn, $nama);
      $hash_safe = mysqli_real_escape_string($conn, $hash);
      $token_safe = mysqli_real_escape_string($conn, $token);

      $q = mysqli_query($conn, "
        INSERT INTO users(nama, email, password, email_verified, verify_token, access_status, requested_at)
        VALUES('$nama_safe', '$email_safe', '$hash_safe', 0, '$token_safe', 'pending', NOW())
      ");

      if($q){
        // link verifikasi
        $host = $_SERVER['HTTP_HOST'];
        $base = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://".$host;
        $link = $base . "/wgi-recruitment/auth/verify.php?token=" . urlencode($token);

        // ===== KIRIM EMAIL (VERSI CEPAT mail()) =====
        $subject = "Verifikasi Email Akun WGI";
        $body = "Halo $nama,\n\nKlik link ini untuk verifikasi email:\n$link\n\nSetelah verifikasi, akun kamu menunggu persetujuan admin.";
        @mail($email, $subject, $body, "From: no-reply@".$host);

        $msg = "Pendaftaran berhasil. Cek email untuk verifikasi. Setelah itu tunggu approval admin.";
      } else {
        $msg = "Gagal daftar. Coba lagi.";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div style="max-width:420px;margin:60px auto;background:#fff;padding:20px;border-radius:14px;border:1px solid #e5e7eb">
    <h2>Daftar Akun</h2>
    <?php if($msg): ?><div style="padding:10px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;margin:10px 0;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <form method="POST">
      <input name="nama" placeholder="Nama" required style="width:100%;padding:10px;border-radius:10px;border:1px solid #e5e7eb;margin-bottom:10px">
      <input name="email" type="email" placeholder="Email" required style="width:100%;padding:10px;border-radius:10px;border:1px solid #e5e7eb;margin-bottom:10px">
      <input name="password" type="password" placeholder="Password" required style="width:100%;padding:10px;border-radius:10px;border:1px solid #e5e7eb;margin-bottom:10px">
      <button type="submit" style="width:100%;padding:10px;border-radius:10px;border:none;background:#2563eb;color:#fff;font-weight:800">Daftar</button>
    </form>
  </div>
</body>
</html>
