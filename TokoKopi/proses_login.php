<?php
session_start();
include 'koneksi.php'; // WAJIB ADA agar variabel $koneksi tidak bernilai NULL

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    // Query mencari user berdasarkan username
    $query  = "SELECT * FROM user WHERE username='$username'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Verifikasi password (menggunakan password_verify jika di-hash, atau cek langsung jika teks polos)
        // Di sini diasumsikan menggunakan cek teks polos/MD5 sesuai standar latihan dasar. 
        // Jika menggunakan hash, ganti menjadi: if (password_verify($password, $row['password']))
        if ($password === $row['password'] || md5($password) === $row['password']) {
            
            // Set session utama
            $_SESSION['username']     = $row['username'];
            $_SESSION['role']         = $row['role']; // 'admin' atau 'kasir'
            
            // Mengantisipasi perbedaan nama kolom di database (nama vs nama_lengkap)
            if (isset($row['nama_lengkap'])) {
                $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            } elseif (isset($row['nama'])) {
                $_SESSION['nama_lengkap'] = $row['nama'];
            } else {
                $_SESSION['nama_lengkap'] = $row['username'];
            }

            header("Location: dashboard.php");
            exit;
        }
    }
    
    // Jika gagal, kembalikan ke login dengan pesan eror
    header("Location: login.php?pesan=gagal");
    exit;
}
?>