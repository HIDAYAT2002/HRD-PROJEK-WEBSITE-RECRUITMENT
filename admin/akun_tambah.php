<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
    header("Location: ../auth/login.php");
    exit;
}

/* batasi siapa yang boleh nambah akun */
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager'){
    header("Location: akun.php?msg=Tidak+punya+akses+menambah+akun");
    exit;
}

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';
$role  = $_POST['role'] ?? '';

if($email === '' || $pass === '' || $role === ''){
    header("Location: akun.php?msg=Semua+field+wajib+diisi");
    exit;
}

if(strlen($pass) < 6){
    header("Location: akun.php?msg=Password+minimal+6+karakter");
    exit;
}

$email_safe = mysqli_real_escape_string($conn, $email);
$role_safe  = mysqli_real_escape_string($conn, $role);

$cek = mysqli_query($conn, "SELECT id FROM users WHERE email='$email_safe'");
if(mysqli_num_rows($cek) > 0){
    header("Location: akun.php?msg=Email+sudah+terdaftar");
    exit;
}

$pass_md5 = md5($pass);
$pass_safe = mysqli_real_escape_string($conn, $pass_md5);

mysqli_query($conn, "INSERT INTO users (email, password, role) VALUES ('$email_safe', '$pass_safe', '$role_safe')");

header("Location: akun.php?msg=Akun+berhasil+ditambahkan");
exit;
