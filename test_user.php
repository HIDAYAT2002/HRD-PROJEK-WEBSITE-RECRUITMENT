<?php
include 'config/koneksi.php';
$q = mysqli_query($conn,"SELECT * FROM users");
while($u=mysqli_fetch_assoc($q)){
    echo $u['email']." | ".$u['password']."<br>";
}
