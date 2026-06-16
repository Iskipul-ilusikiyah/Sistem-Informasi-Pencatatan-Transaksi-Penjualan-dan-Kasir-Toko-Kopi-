<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$id_menu = intval($_GET['id']);

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$menu = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "SELECT * FROM menu WHERE id_menu='$id_menu'"
    )
);

if ($menu) {

    // Jika sudah ada di keranjang
    if (isset($_SESSION['keranjang'][$id_menu])) {

        if (is_array($_SESSION['keranjang'][$id_menu])) {

            $_SESSION['keranjang'][$id_menu]['qty']++;

        } else {

            $_SESSION['keranjang'][$id_menu]++;
        }

    } else {

        $_SESSION['keranjang'][$id_menu] = [
            'nama_menu' => $menu['nama_menu'],
            'harga'     => $menu['harga'],
            'qty'       => 1
        ];
    }
}

header("Location: dashboarduser.php");
exit;
?>