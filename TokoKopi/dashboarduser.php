<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$nama_user = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['username'];
$query_katalog = mysqli_query($koneksi, "SELECT * FROM menu WHERE stok > 0 ORDER BY nama_menu ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Menu Pelanggan - Toko Kopi Sejahtera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #14110f;       
            --bg-card: #1c1816;       
            --bg-sidebar: #181513;    
            --coffee-gold: #e6b88a;   
            --coffee-light: #f4ebd9;  
            --border-glow: #2d2621;   
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--coffee-light);
        }

        .navbar-custom {
            background-color: var(--bg-sidebar) !important;
            border-bottom: 1px solid var(--border-glow);
        }
        .menu-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: 1px solid var(--border-glow);
}
.menu-desc {
    color: #d1c7bd;
    font-size: 0.85rem;
    line-height: 1.4;
    min-height: 40px;
}

        .sidebar-container {
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-glow);
            min-height: calc(100vh - 73px);
        }

        .nav-link-custom {
            color: var(--coffee-light) !important;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            font-weight: 700; 
            font-size: 1rem;
            opacity: 0.8;
        }

        .nav-link-custom.active, .nav-link-custom:hover {
            color: #14110f !important; 
            background: linear-gradient(135deg, #c59b6e, var(--coffee-gold));
            font-weight: 800;
            opacity: 1;
        }

        .premium-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-glow);
            border-radius: 16px;
            padding: 28px;
        }

        .menu-item-card {
            background-color: #14110f;
            border: 1px solid var(--border-glow);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.25s ease;
        }

        .menu-item-card:hover {
            border-color: var(--coffee-gold);
        }

        .text-header-white { color: #ffffff !important; font-weight: 800; }
        .text-krem-terang { color: var(--coffee-light) !important; font-weight: 700; }
        .text-emas-terang { color: var(--coffee-gold) !important; font-weight: 800; }
        .text-muted-custom { color: #d1c7bd !important; font-weight: 500; }

        .btn-gold-action {
            background: linear-gradient(135deg, #c59b6e, var(--coffee-gold));
            color: #14110f;
            font-weight: 800;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
        }

        .menu-image-container {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
        }
        
        .menu-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 1px solid var(--border-glow);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top py-3">
    <div class="container-fluid px-4">
        <span class="navbar-brand" style="font-family: 'Playfair Display', serif; color: var(--coffee-gold); font-weight:800; font-size: 1.6rem;">
            <i class="fas fa-coffee me-2"></i>TOKO KOPI SEJAHTERA
        </span>
        <div class="ms-auto d-flex align-items-center">
            <div class="me-4 text-end">
                <div class="text-header-white fs-5"><?= htmlspecialchars($nama_user); ?></div>
                <span class="badge px-3 py-1 fw-bold" style="background-color: var(--border-glow); color: var(--coffee-gold); border: 1px solid var(--coffee-gold);">👤 PELANGGAN</span>
            </div>
            <a href="logout.php" class="btn btn-sm btn-outline-danger px-3 fw-bold">Keluar</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar-container p-3 d-none d-md-block">
            <div class="mb-4 mt-2">
                <p class="small text-uppercase fw-bold mb-3 px-2 text-emas-terang" style="letter-spacing: 1px;">MENU LAYANAN</p>
                <a href="dashboarduser.php" class="nav-link-custom active"><i class="fas fa-store me-3"></i> Katalog Menu</a>
                <a href="keranjang.php" class="nav-link-custom"><i class="fas fa-shopping-cart me-3"></i> Keranjang</a>
                <a href="riwayat.php" class="nav-link-custom"><i class="fas fa-history me-3"></i> Riwayat Belanja</a>
            </div>
        </div>

        <div class="col-md-10 p-4">
            <div class="mb-4">
                <h2 class="text-header-white" style="font-family: 'Playfair Display', serif; font-size: 2.3rem;">Daftar Menu Kopi</h2>
                <p class="text-muted-custom fs-5">Pilih varian racikan kopi terbaik kami di bawah ini.</p>
            </div>

            <div class="card premium-card">
                <div class="row g-3">
                    <?php if (mysqli_num_rows($query_katalog) > 0) : ?>
                        <?php while ($item = mysqli_fetch_assoc($query_katalog)) : 
                            // SINKRONISASI TOTAL: Menembak folder assets/ dan menggunakan cadangan berkas jepg asli Paduka
                            $gambar = (!empty($item['foto']) && $item['foto'] !== 'default_kopi.png' && $item['foto'] !== 'butterscotch.png') ? $item['foto'] : 'butterscoth.jpg';
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="menu-item-card d-flex align-items-center gap-3">
                                    
                                    <div class="menu-image-container">
                                        <img src="assets/<?= $gambar; ?>" alt="<?= htmlspecialchars($item['nama_menu']); ?>" class="menu-image rounded-3">
                                    </div>

                                    <div class="flex-grow-1">
                                    
    <h5 class="text-header-white mb-1">
        <?= htmlspecialchars($item['nama_menu']); ?>
    </h5>

    <p class="text-muted-custom mb-2" style="font-size:0.85rem; line-height:1.4;">
        <?= htmlspecialchars($item['deskripsi'] ?? 'Tidak ada deskripsi'); ?>
    </p>

    <div class="text-emas-terang fs-5 mb-2">
        Rp <?= number_format($item['harga'], 0, ',', '.'); ?>
    </div>

    <div class="d-flex align-items-center justify-content-between">
                                            <span class="badge text-white fw-bold px-2 py-1" style="background-color: #2e4a3e; color: #a3eccb !important; border: 1px solid #3d6654; font-size: 0.75rem;">
                                                <i class="fas fa-check-circle me-1"></i> Tersedia
                                            </span>
                                            <a href="proses_keranjang.php?id=<?= $item['id_menu']; ?>" class="btn btn-gold-action btn-sm">
                                                <i class="fas fa-plus me-1"></i> Pesan
                                            </a>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-mug-hot fa-4x text-emas-terang mb-3"></i>
                            <h4 class="text-header-white">Stok Kopi Sedang Habis</h4>
                            <p class="text-muted-custom fs-5">Silakan hubungi Barista di meja kasir.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>