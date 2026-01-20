<?php
session_start();
include '../config/koneksi.php';

$token = $_GET['token'] ?? '';
$token = trim($token);

if($token === ''){
  die("Token tidak valid.");
}

$token_safe = mysqli_real_escape_string($conn, $token);

$u = mysqli_query($conn, "SELECT id, email_verified FROM users WHERE verify_token='$token_safe' LIMIT 1");
if(!$u || mysqli_num_rows($u) === 0){
  die("Token tidak ditemukan / sudah dipakai.");
}

$user = mysqli_fetch_assoc($u);
$id = (int)$user['id'];

mysqli_query($conn, "UPDATE users SET email_verified=1, verify_token=NULL WHERE id=$id");

echo "✅ Email berhasil diverifikasi. Sekarang akun kamu menunggu persetujuan admin.";
