<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username_aktif = $_SESSION['username'];

// 1. Ambil id_user yang aktif dari tabel user
$ambil_id_user = mysqli_query($koneksi, "SELECT id_user FROM user WHERE username = '$username_aktif'");
$data_user = mysqli_fetch_assoc($ambil_id_user);
$id_user_aktif = $data_user['id_user'] ?? 0;

// 2. QUERY YANG SUDAH DIPERBAIKI (Menggabungkan tabel transaksi, detail, dan menu)
$query_riwayat = "SELECT t.tanggal_transaksi, m.nama_menu, d.jumlah, (m.harga * d.jumlah) AS total_harga_item
                  FROM transaksi t 
                  JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi 
                  JOIN menu m ON d.id_menu = m.id_menu 
                  WHERE t.id_user = '$id_user_aktif'
                  ORDER BY t.tanggal_transaksi DESC";

$result = mysqli_query($koneksi, $query_riwayat);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Belanja - Toko Kopi Sejahtera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #14110f;       
            --bg-card: #1c1816;       
            --bg-sidebar: #181513;    
            --coffee-gold: #e6b88a;   
            --coffee-light: #f4ebd9;  
            --border-glow: #3a322d;   
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-main); color: #ffffff; }
        .navbar-custom { background-color: var(--bg-sidebar) !important; border-bottom: 2px solid var(--border-glow); }
        .sidebar-container { background-color: var(--bg-sidebar); border-right: 2px solid var(--border-glow); min-height: calc(100vh - 73px); }
        
        .nav-link-custom {
            color: var(--coffee-light) !important;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            font-weight: 700;
        }
        .nav-link-custom.active, .nav-link-custom:hover { color: #14110f !important; background: linear-gradient(135deg, #c59b6e, var(--coffee-gold)); font-weight: 800; }
        .premium-card { background-color: var(--bg-card); border: 2px solid var(--border-glow); border-radius: 16px; padding: 28px; }
        
        /* Tabel Kustom Anti-Sakit Kepala (Hitam Pekat & Teks Terang) */
        .tabel-raja {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background-color: #14110f !important;
        }
        .tabel-raja th {
            background-color: #231f1d !important;
            color: var(--coffee-gold) !important;
            font-weight: 800;
            font-size: 1.1rem;
            padding: 18px 14px;
            text-align: left;
            border-bottom: 3px solid var(--border-glow);
        }
        .tabel-raja td {
            background-color: #1c1816 !important;
            color: #ffffff !important; /* Semua teks data wajib putih bersih */
            padding: 20px 14px;
            font-weight: 700;
            font-size: 1.1rem;
            border-bottom: 1px solid var(--border-glow);
        }
        .badge-status-premium { 
            background-color: rgba(230, 184, 138, 0.1); 
            color: var(--coffee-gold); 
            border: 1px solid var(--coffee-gold);
            padding: 6px 14px; 
            border-radius: 6px; 
            font-weight: 800; 
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top py-3">
    <div class="container-fluid px-4">
        <span class="navbar-brand" style="font-family: 'Playfair Display', serif; color: var(--coffee-gold); font-weight:800; font-size: 1.6rem;">
            <i class="fas fa-coffee me-2"></i>TOKO KOPI SEJAHTERA
        </span>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar-container p-3 d-none d-md-block">
            <div class="mb-4 mt-2">
                <p class="small text-uppercase fw-bold mb-3 px-2" style="color: var(--coffee-gold);">MENU LAYANAN</p>
                <a href="dashboarduser.php" class="nav-link-custom"><i class="fas fa-store me-3"></i> Katalog Menu</a>
                <a href="keranjang.php" class="nav-link-custom"><i class="fas fa-shopping-cart me-3"></i> Keranjang</a>
                <a href="riwayat.php" class="nav-link-custom active"><i class="fas fa-history me-3"></i> Riwayat Belanja</a>
            </div>
        </div>

        <div class="col-md-10 p-4">
            <div class="mb-4">
                <h2 style="color: #ffffff; font-family: 'Playfair Display', serif; font-weight: 800; font-size: 2.3rem;">Riwayat Transaksi</h2>
                <p style="color: var(--coffee-light); font-size: 1.1rem; opacity: 0.9;">Daftar nota belanjaan kopi yang dikirim ke sistem meja kasir.</p>
            </div>

            <div class="card premium-card">
                <?php if ($result && mysqli_num_rows($result) > 0) : ?>
                    <div class="table-responsive">
                        <table class="tabel-raja">
                            <thead>
                                <tr>
                                    <th>Waktu Transaksi</th>
                                    <th>Nama Menu</th>
                                    <th>Kuantitas</th>
                                    <th>Total Harga</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                <tr>
                                    <td style="color: #ffffff !important;">
                                        <i class="far fa-calendar-alt me-2" style="color: var(--coffee-gold) !important;"></i>
                                        <?= date('d M Y - H:i', strtotime($row['tanggal_transaksi'])); ?> WIB
                                    </td>
                                    <td style="color: var(--coffee-light) !important; font-size: 1.15rem;">
                                        <?= htmlspecialchars($row['nama_menu']); ?>
                                    </td>
                                    <td style="color: #ffffff !important;">
                                        <?= $row['jumlah']; ?> Cup
                                    </td>
                                    <td style="color: var(--coffee-gold) !important; font-size: 1.2rem; font-weight: 800;">
                                        Rp <?= number_format($row['total_harga_item'], 0, ',', '.'); ?>
                                    </td>
                                    <td>
                                        <span class="badge-status-premium"><i class="fas fa-check-circle me-1"></i> Terkirim</span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-4x mb-3" style="color: var(--border-glow);"></i>
                        <h4 style="color: #ffffff;">Belum Ada Transaksi</h4>
                        <p style="color: var(--coffee-light);">Silakan konfirmasi belanjaan Anda di menu keranjang.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>