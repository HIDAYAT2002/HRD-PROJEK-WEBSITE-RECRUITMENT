<?php
include '../config/koneksi.php';

mysqli_query($conn,"INSERT INTO lowongan 
(posisi,kota,pekerjaan,kriteria,deadline)
VALUES (
'$_POST[posisi]',
'$_POST[kota]',
'$_POST[pekerjaan]',
'$_POST[kriteria]',
'$_POST[deadline]'
)");

header("Location: lowongan.php");
