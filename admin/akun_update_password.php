<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login']) || $_SESSION['login'] !== true){
    header("Location: ../auth/login.php");
    exit;
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$email_sess = $_SESSION['email'] ?? '';

$old = $_POST['old_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if($new !== $confirm){
    header("Location: akun.php?err=Konfirmasi+password+baru+tidak+cocok");
    exit;
}
if(strlen($new) < 6){
    header("Location: akun.php?err=Password+baru+minimal+6+karakter");
    exit;
}

if($user_id > 0){
    $me = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id"));
} else {
    $email_safe = mysqli_real_escape_string($conn, $email_sess);
    $me = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE email='$email_safe'"));
}

if(!$me){
    header("Location: akun.php?err=Akun+tidak+ditemukan");
    exit;
}

if(md5($old) !== $me['password']){
    header("Location: akun.php?err=Password+lama+salah");
    exit;
}

$new_md5 = md5($new);
$new_safe = mysqli_real_escape_string($conn, $new_md5);
$uid = (int)$me['id'];

mysqli_query($conn, "UPDATE users SET password='$new_safe' WHERE id=$uid");

header("Location: akun.php?success=1");
exit;
