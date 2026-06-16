<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    // Mengambil data user berdasarkan username dan password murni (Sesuai Database Paduka)
    $query = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username' AND password='$password'");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        // Menyimpan data penting ke dalam SESSION
        $_SESSION['username'] = $data['username'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'] ?? $data['nama'];
        $_SESSION['role'] = $data['role']; 
        
        // ALUR REDIRECTION 3 JALUR (ADMIN, KASIR, USER)
        if (strtolower($data['role']) == 'admin') {
            // Jika Admin, arahkan ke Dashboard Utama
            header("Location: dashboard.php");
        } elseif (strtolower($data['role']) == 'kasir') {
            // Jika Kasir, arahkan ke aplikasi Transaksi Kasir
            header("Location: transaksi.php");
        } else {
            // Jika User (Pelanggan), arahkan ke Dashboard User Baru (Katalog Menu)
            header("Location: dashboarduser.php");
        }
        exit;
    } else {
        echo "<script>alert('Username atau Password salah!'); window.location.href='login.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kopi Paduka Raja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #14110f;       
            --bg-card: #1c1816;       
            --coffee-gold: #e6b88a;   
            --coffee-light: #f4ebd9;  
            --text-muted: #ffffff;    
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
            overflow: hidden;
        }

        .login-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-glow);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: 1.5px;
            color: var(--coffee-gold);
        }

        .brand-subtitle {
            color: var(--text-muted) !important;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            opacity: 0.85;
        }

        .form-label-custom {
            color: var(--coffee-light) !important;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: block;
        }

        .form-control-premium {
            background-color: #14110f !important;
            border: 1px solid var(--border-glow) !important;
            color: #ffffff !important;
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        .form-control-premium::placeholder {
    color: #f4ebd9 !important; /* warna krem terang */
    opacity: 1;
}

.form-control-premium::-webkit-input-placeholder {
    color: #f4ebd9 !important;
}

.form-control-premium::-moz-placeholder {
    color: #f4ebd9 !important;
    opacity: 1;
}

.form-control-premium:-ms-input-placeholder {
    color: #f4ebd9 !important;
}
        
        .form-control-premium:focus {
            border-color: var(--coffee-gold) !important;
            box-shadow: 0 0 8px rgba(230, 184, 138, 0.2) !important;
        }

        .btn-gold-action {
            background: linear-gradient(135deg, #c59b6e, var(--coffee-gold));
            color: #14110f;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            width: 100%;
            transition: all 0.2s ease;
        }

        .btn-gold-action:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

<div class="login-card text-center">
    <div class="mb-4">
        <h2 class="brand-title mb-1">
            <i class="fas fa-coffee me-2"></i>Toko Kopi Sejahtera
        </h2>
        <p class="brand-subtitle">Sistem Informasi Manajemen Coffee Shop</p>
    </div>

    <form action="" method="POST">
        
        <div class="mb-4 text-start">
            <label for="username" class="form-label-custom">Username</label>
            <input type="text" class="form-control form-control-premium" id="username" name="username" placeholder="Masukkan username Anda" required autocomplete="off">
        </div>

        <div class="mb-4 text-start">
            <label for="password" class="form-label-custom">Password</label>
            <input type="password" class="form-control form-control-premium" id="password" name="password" placeholder="Masukkan password Anda" required>
        </div>

        <button type="submit" name="login" class="btn btn-gold-action mt-2">
            Masuk ke Sistem <i class="fas fa-sign-in-alt ms-2"></i>
        </button>
        <div class="text-center mt-3">
    <a href="register.php"
       style="color:#e6b88a;text-decoration:none;">
       Belum punya akun? Daftar di sini
    </a>
</div>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>