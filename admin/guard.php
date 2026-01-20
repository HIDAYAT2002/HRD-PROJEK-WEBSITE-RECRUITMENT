<?php
// admin/guard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wajib login
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

// (opsional) kalau mau batasi role tertentu, aktifin ini:
// $allowed = ['admin','manager','hrd'];
// $role = $_SESSION['role'] ?? '';
// if (!in_array($role, $allowed, true)) {
//     header("Location: ../auth/login.php");
//     exit;
// }
