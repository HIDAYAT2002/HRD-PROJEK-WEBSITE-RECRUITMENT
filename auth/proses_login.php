<?php
session_start();
include '../config/koneksi.php';

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header("Location: login.php");
    exit;
}

$pass_md5 = md5($password);

// ambil user berdasarkan email (prepared)
$stmt = mysqli_prepare($conn, "SELECT id, email, role, password FROM users WHERE email = ? LIMIT 1");
if (!$stmt) {
    header("Location: login.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

if ($res && mysqli_num_rows($res) > 0) {
    $u = mysqli_fetch_assoc($res);

    // cocokin password md5 (karena DB lu masih md5)
    if (!empty($u['password']) && hash_equals($u['password'], $pass_md5)) {

        // amankan session
        session_regenerate_id(true);

        $_SESSION['login']   = true;
        $_SESSION['role']    = $u['role'] ?? '';
        $_SESSION['user_id'] = (int)($u['id'] ?? 0);
        $_SESSION['email']   = $u['email'] ?? $email;

        header("Location: http://ptwgi.com/career/admin/Dashboard.php");
        exit;
    }
}

header("Location: login.php");
exit;
