<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

$nama_user = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : (isset($_SESSION['nama']) ? $_SESSION['nama'] : $_SESSION['username']);
$role_user = isset($_SESSION['role']) ? $_SESSION['role'] : 'Kasir';

// Proses Simpan Data Racikan Baru
if (isset($_POST['simpan'])) {
    $nama_menu = mysqli_real_escape_string($koneksi, $_POST['nama_menu']);
    $stok      = intval($_POST['stok']);
    $harga     = intval($_POST['harga']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    
    // Default gambar diselaraskan dengan aset asli milik Paduka
    $nama_foto_final = 'butterscoth.jpg'; 

    // Alur penanganan unggah gambar langsung ke folder assets/
    if (isset($_FILES['foto_produk']) && $_FILES['foto_produk']['error'] === 0) {
        $file_name = $_FILES['foto_produk']['name'];
        $file_size = $_FILES['foto_produk']['size'];
        $file_tmp  = $_FILES['foto_produk']['tmp_name'];
        
        $ekstensi_diizinkan = ['jpg', 'jpeg', 'png'];
        $ekstensi_file      = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($ekstensi_file, $ekstensi_diizinkan)) {
            if ($file_size <= 2097152) { 
                $nama_foto_final = uniqid() . '_' . str_replace(' ', '_', strtolower($nama_menu)) . '.' . $ekstensi_file;
                $direktori_tujuan = 'assets/' . $nama_foto_final; // Disimpan langsung ke assets/

                move_uploaded_file($file_tmp, $direktori_tujuan);
            } else {
                echo "<script>alert('Gagal! Ukuran berkas gambar terlalu besar, maksimal 2MB.'); window.history.back();</script>";
                exit;
            }
        } else {
            echo "<script>alert('Gagal! Format berkas salah. Hanya menerima berkas JPG, JPEG, atau PNG.'); window.history.back();</script>";
            exit;
        }
    }
    
    $query_tambah = mysqli_query($koneksi, "INSERT INTO menu (nama_menu, kategori, harga, stok, deskripsi, foto) VALUES ('$nama_menu', '', '$harga', '$stok', '$deskripsi', '$nama_foto_final')");
    
    if ($query_tambah) {
        echo "<script>alert('Mahakarya menu baru berhasil ditambahkan!'); window.location.href='menu.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menambahkan menu: " . mysqli_error($koneksi) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Menu Baru - Kopi Paduka Raja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #14110f;       
            --bg-card: #1c1816;       
            --bg-sidebar: #181513;    
            --coffee-gold: #e6b88a;   
            --coffee-light: #f4ebd9;  
            --text-muted: #d1c7bd;    
            --border-glow: #2d2621;   
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--coffee-light);
            overflow-x: hidden;
        }
        .navbar-custom {
            background-color: var(--bg-sidebar) !important;
            border-bottom: 1px solid var(--border-glow);
        }
        .navbar-brand-coffee {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: 1.5px;
            color: var(--coffee-gold) !important;
        }
        .sidebar-container {
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-glow);
            min-height: calc(100vh - 73px);
        }
        .sidebar-section-title {
            font-size: 0.75rem;
            letter-spacing: 1.5px;
            color: var(--coffee-gold);
            font-weight: 700;
            opacity: 0.9;
        }
        .nav-link-custom {
            color: var(--text-muted);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .nav-link-custom.active {
            background: linear-gradient(135deg, #c59b6e, var(--coffee-gold));
            color: #14110f;
            font-weight: 700;
        }
        .premium-card {
            background-color: var(--bg-card) !important;
            border: 1px solid var(--border-glow) !important;
            border-radius: 16px;
            padding: 32px;
        }
        .form-label-custom {
            color: var(--coffee-gold);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .form-control-premium {
    background-color: #14110f !important;
    border: 1px solid var(--border-glow) !important;
    
    /* 👑 TAMBAHKAN BARIS INI AGAR TEKS MENJADI PUTIH */
    color: #ffffff !important; 
    
    /* Tambahkan juga ini agar placeholder-nya terlihat jelas (juga berwarna putih/terang) */
    caret-color: #ffffff;
    border-radius: 8px;
    padding: 12px 16px;
}

/* Opsional: Jika Paduka ingin placeholder (tulisan abu-abu samar) juga menjadi lebih terang */
.form-control-premium::placeholder {
    color: #b5a89e !important;
    opacity: 1; 
}

        .input-group-text-premium {
            background-color: #2d2621 !important;
            border: 1px solid var(--border-glow) !important;
            color: var(--coffee-gold) !important;
            font-weight: 700;
        }
        .btn-gold-action {
            background: linear-gradient(135deg, #c59b6e, var(--coffee-gold));
            color: #14110f;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
        }
        .btn-outline-custom {
            border: 1px solid var(--border-glow);
            color: var(--text-muted);
            background: transparent;
            border-radius: 8px;
            padding: 12px 24px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top py-3">
    <div class="container-fluid px-4">
        <a class="navbar-brand navbar-brand-coffee" href="dashboard.php">
            <i class="fas fa-coffee me-2"></i>Toko Kopi Sejahtera
        </a>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        
        <div class="col-md-2 sidebar-container p-3 d-none d-md-block">
            <div class="mb-4 mt-2">
                <p class="sidebar-section-title text-uppercase mb-3 px-2">Pencatatan & Master</p>
                <a href="menu.php" class="nav-link-custom active"><i class="fas fa-mug-hot me-3"></i> Kelola Menu/Stok</a>
            </div>
        </div>

        <div class="col-md-10 p-4">
            <div class="mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                <h1 class="h2 fw-bold text-white mb-1" style="font-family: 'Playfair Display', serif;">Racik Varian Menu Baru</h1>
            </div>

            <div class="card premium-card shadow-sm">
                <form action="" method="POST" enctype="multipart/form-data">
                    
                    <div class="mb-4">
                        <label for="nama_menu" class="form-label form-label-custom">Nama Varian Menu</label>
                        <input type="text" class="form-control form-control-premium" id="nama_menu" name="nama_menu" placeholder="Contoh: Es Kopi Susu Aren Istana" required autocomplete="off">
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label for="stok" class="form-label form-label-custom">Stok Awal</label>
                            <input type="number" class="form-control form-control-premium" id="stok" name="stok" min="0" placeholder="0" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="harga" class="form-label form-label-custom">Harga Jual (RP)</label>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-premium">Rp</span>
                            <input type="number" class="form-control form-control-premium" id="harga" name="harga" min="0" placeholder="Contoh: 25000" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="deskripsi" class="form-label form-label-custom">Deskripsi Profil Rasa</label>
                        <textarea class="form-control form-control-premium" id="deskripsi" name="deskripsi" rows="4" placeholder="Gambarkah cita rasa kemewahan menu ini..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="foto_produk" class="form-label form-label-custom">Foto Menu Estetik</label>
                        <input type="file" class="form-control form-control-premium" id="foto_produk" name="foto_produk" accept="image/png, image/jpeg, image/jpg">
                    </div>

                    <div class="d-flex gap-3 justify-content-start mt-4">
                        <button type="button" onclick="window.location.href='menu.php'" class="btn btn-outline-custom">Kembali</button>
                        <button type="submit" name="simpan" class="btn btn-gold-action"><i class="fas fa-crown me-2"></i> Racikan Menu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>