<?php
include '../config/koneksi.php';

$email = 'dayatsidhoephat@gmail.com';
$newPass = '123456'; // GANTI sesuai yang kamu mau

$hash = password_hash($newPass, PASSWORD_DEFAULT);

$email_safe = mysqli_real_escape_string($conn, $email);
$hash_safe  = mysqli_real_escape_string($conn, $hash);

mysqli_query($conn, "UPDATE users SET password='$hash_safe' WHERE email='$email_safe'");

echo "OK. Password sudah di-hash. Sekarang login pakai password: $newPass";
