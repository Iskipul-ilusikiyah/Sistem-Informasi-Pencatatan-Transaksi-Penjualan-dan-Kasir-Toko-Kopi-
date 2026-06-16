<?php
session_start();
include 'koneksi.php';

// Jika user sudah login, langsung lempar ke dashboard yang sesuai
if (isset($_SESSION['username'])) {
    if (strcasecmp($_SESSION['role'], 'Pembeli') == 0) {
        header("Location: dashboarduser.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error_message = "";
$success_message = "";

// Proses ketika tombol "Buat Akun" ditekan
if (isset($_POST['register'])) {
    // Sanitasi input agar aman dari SQL Injection
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap']));
    $password = trim($_POST['password']);
    
    // Default role untuk pendaftaran mandiri adalah 'Pembeli' demi keamanan sistem
    $role = 'Pembeli'; 

    // 1. VALIDASI: Cek apakah username sudah terpakai di database
    $cek_username = mysqli_query($koneksi, "SELECT username FROM user WHERE username = '$username'");
    
    if (empty($username) || empty($nama_lengkap) || empty($password)) {
        $error_message = "Semua kolom wajib diisi, Paduka!";
    } elseif (mysqli_num_rows($cek_username) > 0) {
        $error_message = "Username sudah terdaftar! Silakan gunakan username lain.";
    } else {
        // 2. ENKRIPSI: Mengamankan password sebelum masuk database
        // Disarankan menggunakan password_hash, namun jika login Paduka masih md5, ganti menjadi md5($password)
        $password_aman = $password;

        // 3. QUERY INSERT: Memasukkan akun baru ke database
        $query_daftar = mysqli_query($koneksi, "INSERT INTO user (username, password, nama_lengkap, role) VALUES ('$username', '$password_aman', '$nama_lengkap', '$role')");

        if ($query_daftar) {
            $success_message = "Akun berhasil dibuat! Silakan dialihkan ke halaman login.";
            // Otomatis pindah ke login setelah 2 detik
            header("refresh:2;url=login.php");
        } else {
            $error_message = "Gagal mendaftarkan akun. Terjadi kesalahan sistem database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Akun - Kopi Paduka Raja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #14110f;       
            --bg-card: #1c1816;       
            --coffee-gold: #e6b88a;   
            --coffee-light: #f4ebd9;  
            --border-glow: #2d2621;   
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--coffee-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-glow);
            border-radius: 20px;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .form-control-custom {
            background-color: #14110f !important;
            border: 1px solid var(--border-glow) !important;
            color: var(--coffee-light) !important;
            border-radius: 10px;
            padding: 12px;
        }
        .form-control-custom::placeholder {
    color: #f4ebd9 !important;
    opacity: 1;
}

        .form-control-custom:focus {
            border-color: var(--coffee-gold) !important;
            box-shadow: 0 0 8px rgba(230, 184, 138, 0.2) !important;
        }

        .btn-gold {
            background: linear-gradient(135deg, #c59b6e, var(--coffee-gold));
            color: #14110f;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            padding: 12px;
            transition: all 0.3s ease;
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
    </style>
</head>
<body>

<div class="register-card">
    <div class="text-center mb-4">
        <h2 style="font-family: 'Playfair Display', serif; color: var(--coffee-gold); font-weight: 700;">BUAT AKUN</h2>
        <p class="text-white small">Daftar sekarang untuk mulai menikmati layanan digital kami</p>
    </div>

    <?php if (!empty($error_message)) : ?>
        <div class="alert alert-danger py-2 border-0 small text-center" style="border-radius: 8px;"><?= $error_message; ?></div>
    <?php endif; ?>
    <?php if (!empty($success_message)) : ?>
        <div class="alert alert-success py-2 border-0 small text-center" style="border-radius: 8px;"><?= $success_message; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-semibold text-white-50">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control form-control-custom" placeholder="Masukkan nama" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label small fw-semibold text-white-50">Username</label>
            <input type="text" name="username" class="form-control form-control-custom" placeholder="Untuk login sistem" required>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold text-white-50">Password</label>
            <input type="password" name="password" class="form-control form-control-custom" placeholder="🔒 Minimal 6 karakter" required>
        </div>

        <button type="submit" name="register" class="btn btn-gold w-100 mb-3">Daftar Sekarang</button>
        
        <div class="text-center small">
            <span class="text-white">Sudah punya akun?</span> 
            <a href="login.php" style="color: var(--coffee-gold); text-decoration: none;" class="fw-bold ms-1">Login di sini</a>
        </div>
    </form>
</div>

</body>
</html>