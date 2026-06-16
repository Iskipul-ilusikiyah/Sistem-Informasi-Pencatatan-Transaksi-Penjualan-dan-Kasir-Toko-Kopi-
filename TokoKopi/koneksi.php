<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_toko_kopi";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("<div class='alert alert-danger'>Koneksi ke database gagal: " . mysqli_connect_error() . "</div>");
}
?>