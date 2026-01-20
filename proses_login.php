<?php
session_start();
include '../config/koneksi.php';

$email = $_POST['email'];
$pass  = md5($_POST['password']);

$q = mysqli_query($conn,"SELECT * FROM users 
WHERE email='$email' AND password='$pass'");

if(mysqli_num_rows($q) > 0){
    $u = mysqli_fetch_assoc($q);

    $_SESSION['login'] = true;
    $_SESSION['role']  = $u['role'];

    // TAMBAHAN BIAR AKUN.PHP NYAMBUNG
    $_SESSION['user_id'] = $u['id'];
    $_SESSION['email']   = $u['email'];

    header("Location: http://ptwgi.com/career/admin/Dashboard.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
