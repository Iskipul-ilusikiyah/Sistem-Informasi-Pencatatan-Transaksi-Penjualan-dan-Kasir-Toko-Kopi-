<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

$nama_user = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : (isset($_SESSION['nama']) ? $_SESSION['nama'] : $_SESSION['username']);
$role_user = isset($_SESSION['role']) ? $_SESSION['role'] : 'Kasir';

// ==========================================
// QUERY METRIK UTAMA
// ==========================================
$hari_ini = date('Y-m-d');
$q_pendapatan = mysqli_query($koneksi,
"SELECT SUM(
    CASE
        WHEN total_harga > 0 THEN total_harga
        ELSE total_bayar
    END
) as total
FROM transaksi
WHERE DATE(tanggal_transaksi) = '$hari_ini'");$r_pendapatan = mysqli_fetch_assoc($q_pendapatan);
$pendapatan_hari_ini = $r_pendapatan['total'] ?? 0;

$q_transaksi = mysqli_query($koneksi, "SELECT COUNT(id_transaksi) as total FROM transaksi WHERE DATE(tanggal_transaksi) = '$hari_ini'");
$r_transaksi = mysqli_fetch_assoc($q_transaksi);
$transaksi_hari_ini = $r_transaksi['total'] ?? 0;

$q_menu = mysqli_query($koneksi, "SELECT COUNT(id_menu) as total FROM menu");
$r_menu = mysqli_fetch_assoc($q_menu);
$total_menu = $r_menu['total'] ?? 0;

$q_stok_tipis = mysqli_query($koneksi, "SELECT COUNT(id_menu) as total FROM menu WHERE stok < 10");
$r_stok_tipis = mysqli_fetch_assoc($q_stok_tipis);
$stok_menipis = $r_stok_tipis['total'] ?? 0;

$query_recent = "SELECT t.*, u.nama_lengkap FROM transaksi t 
                 LEFT JOIN user u ON t.id_user = u.id_user 
                 ORDER BY t.tanggal_transaksi DESC LIMIT 5";
$result_recent = mysqli_query($koneksi, $query_recent);

$query_fav = "SELECT m.nama_menu, COUNT(dt.id_menu) as kali_terjual, SUM(dt.jumlah) as total_qty 
              FROM detail_transaksi dt 
              JOIN menu m ON dt.id_menu = m.id_menu 
              GROUP BY dt.id_menu 
              ORDER BY total_qty DESC LIMIT 3";
$result_fav = mysqli_query($koneksi, $query_fav);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Toko Kopi Sejahtera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #14110f;       
            --bg-card: #1c1816;       
            --bg-sidebar: #181513;    
            --coffee-gold: #e6b88a;   
            --coffee-light: #f4ebd9;  
            --text-muted: #d1c7bd;    /* DIUBAH: Menggunakan warna abu terang yang tajam */
            --border-glow: #2d2621;   
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--coffee-light);
            overflow-x: hidden;
        }

        /* Top Navbar */
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

        /* Sidebar */
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
        .nav-link-custom:hover {
            color: #ffffff;
            background-color: rgba(230, 184, 138, 0.08);
        }
        .nav-link-custom.active {
            background: linear-gradient(135deg, #c59b6e, var(--coffee-gold));
            color: #14110f;
            font-weight: 700;
        }

        /* Cards & Metrics (PERBAIKAN KONTRAS) */
        .analytics-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-glow);
            border-radius: 16px;
            padding: 24px;
            transition: transform 0.2s ease;
        }
        .analytics-card:hover {
            transform: translateY(-3px);
            border-color: var(--coffee-gold);
        }
        .analytics-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            background-color: rgba(230, 184, 138, 0.15);
            color: var(--coffee-gold);
        }

        /* Teks Sub-Judul Atas Kartu */
        .analytics-card .card-label {
            color: #e6b88a !important; /* Diubah ke warna emas lembut agar langsung terbaca */
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.8px;
        }
        
        /* Teks Keterangan Bawah Kartu */
        .analytics-card .card-desc,
        .premium-card .card-desc {
            color: #d1c7bd !important; /* Dipaksa ke abu terang kontras tinggi */
            font-size: 0.85rem;
            font-weight: 500;
        }

        .premium-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-glow);
            border-radius: 16px;
            padding: 24px;
        }

        /* Table Area */
        .table-premium {
            background-color: transparent !important;
        }
        .table-premium thead th {
            color: var(--coffee-gold) !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            border-bottom: 2px solid var(--border-glow) !important;
            padding: 14px 12px;
            background-color: transparent !important;
        }
        .table-premium tbody tr td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border-glow) !important;
            color: var(--coffee-light) !important;
            background-color: transparent !important;
        }

        .luxury-badge {
            background: rgba(230, 184, 138, 0.15);
            color: var(--coffee-gold);
            border: 1px solid rgba(230, 184, 138, 0.3);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top py-3">
    <div class="container-fluid px-4">
        <a class="navbar-brand navbar-brand-coffee" href="dashboard.php">
            <i class="fas fa-coffee me-2"></i>TOKO KOPI SEJAHTERA
        </a>
        <div class="ms-auto d-flex align-items-center">
            <div class="me-4 text-end d-none d-sm-block">
                <div class="fw-bold text-white" style="font-size: 0.95rem;"><?= htmlspecialchars($nama_user); ?></div>
                <span class="luxury-badge py-0 px-2" style="font-size: 0.65rem;">👑 <?= htmlspecialchars($role_user); ?></span>
            </div>
            <a href="logout.php" class="btn btn-sm btn-outline-danger px-3 py-2 fw-semibold" style="border-radius: 8px;">
                <i class="fas fa-sign-out-alt me-1"></i> Keluar
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        
        <!-- SIDEBAR -->
        <div class="col-md-2 sidebar-container p-3 d-none d-md-block">
            <div class="mb-4 mt-2">
                <p class="sidebar-section-title text-uppercase mb-3 px-2">Menu Utama</p>
                <a href="dashboard.php" class="nav-link-custom active"><i class="fas fa-chart-pie me-3"></i> Dashboard</a>
                <a href="transaksi.php" class="nav-link-custom"><i class="fas fa-cash-register me-3"></i> Transaksi Kasir</a>
            </div>
            <div class="mb-4">
                <p class="sidebar-section-title text-uppercase mb-3 px-2">Pencatatan & Master</p>
                <a href="menu.php" class="nav-link-custom"><i class="fas fa-mug-hot me-3"></i> Kelola Menu/Stok</a>
                <a href="laporan.php" class="nav-link-custom"><i class="fas fa-file-invoice-dollar me-3"></i> Laporan Keuangan</a>
                <a href="user.php" class="nav-link-custom"><i class="fas fa-user-shield me-3"></i> Manajemen Kasir</a>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-10 p-4">
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                <div>
                    <span class="text-uppercase small fw-bold tracking-wider" style="color: var(--coffee-gold); font-size: 0.75rem;">Premium Lounge System</span>
                    <h1 class="h2 fw-bold text-white mt-1" style="font-family: 'Playfair Display', serif;">Selamat Datang</h1>
                </div>
                <div class="text-end small d-none d-md-block">
                    <i class="far fa-clock me-1 text-warning"></i> <span id="liveClock" class="text-white-50 fw-bold"><?= date('H:i:s'); ?> WIB</span>
                    <div class="fw-medium mt-1 text-white" style="font-size: 0.85rem;"><?= date('d F Y'); ?></div>
                </div>
            </div>

            <!-- METRICS GRID -->
            <div class="row g-4 mb-5">
                <!-- Pendapatan -->
                <div class="col-xl-3 col-md-6">
                    <div class="analytics-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="card-label text-uppercase">Pendapatan Hari Ini</span>
                            <div class="analytics-icon-box"><i class="fas fa-wallet"></i></div>
                        </div>
                        <h3 class="fw-bold text-white mb-1" style="font-size: 1.6rem;">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.'); ?></h3>
                        <p class="text-success small mb-0 fw-bold"><i class="fas fa-arrow-trend-up me-1"></i> Aliran kas masuk</p>
                    </div>
                </div>

                <!-- Pesanan -->
                <div class="col-xl-3 col-md-6">
                    <div class="analytics-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="card-label text-uppercase">Pesanan Selesai</span>
                            <div class="analytics-icon-box"><i class="fas fa-receipt"></i></div>
                        </div>
                        <h3 class="fw-bold text-white mb-1" style="font-size: 1.6rem;"><?= $transaksi_hari_ini; ?> <span style="font-size: 0.9rem; color: #d1c7bd;" class="fw-normal">Nota</span></h3>
                        <p class="card-desc mb-0">Pelanggan terlayani</p>
                    </div>
                </div>

                <!-- Varian Menu -->
                <div class="col-xl-3 col-md-6">
                    <div class="analytics-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="card-label text-uppercase">Varian Menu Kopi</span>
                            <div class="analytics-icon-box"><i class="fas fa-mug-hot"></i></div>
                        </div>
                        <h3 class="fw-bold text-white mb-1" style="font-size: 1.6rem;"><?= $total_menu; ?> <span style="font-size: 0.9rem; color: #d1c7bd;" class="fw-normal">Produk</span></h3>
                        <p class="card-desc mb-0">Racikan aktif</p>
                    </div>
                </div>

                <!-- Logistik -->
                <div class="col-xl-3 col-md-6">
                    <div class="analytics-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="card-label text-uppercase">Logistik Kritis</span>
                            <div class="analytics-icon-box <?= $stok_menipis > 0 ? 'bg-danger bg-opacity-20 text-danger' : ''; ?>"><i class="fas fa-boxes-stacked"></i></div>
                        </div>
                        <h3 class="fw-bold mb-1 <?= $stok_menipis > 0 ? 'text-danger' : 'text-white'; ?>" style="font-size: 1.6rem;"><?= $stok_menipis; ?> <span style="font-size: 0.9rem; color: #d1c7bd;" class="fw-normal">Item</span></h3>
                        <p class="small mb-0 fw-bold <?= $stok_menipis > 0 ? 'text-danger' : 'text-success'; ?>">
                            <?= $stok_menipis > 0 ? 'Perlu restock segera!' : 'Stok aman terkendali'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- DATA TABLES & UTILITIES -->
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card premium-card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="fw-bold text-white mb-1"><i class="fas fa-clock-rotate-left me-2 text-warning" style="font-size: 1rem;"></i>Aktivitas Transaksi Terakhir</h5>
                                <p class="card-desc mb-0">Daftar layanan penjualan real-time di kasir.</p>
                            </div>
                            <a href="laporan.php" class="btn btn-sm text-decoration-none p-0" style="color: var(--coffee-gold); font-size: 0.85rem; font-weight: 600;">Lihat Semua <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-premium align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>WAKTU</th>
                                        <th>KODE NOTA</th>
                                        <th>KASIR</th>
                                        <th>TOTAL PEMBAYARAN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (mysqli_num_rows($result_recent) > 0) {
                                        while ($row = mysqli_fetch_assoc($result_recent)) {
                                    ?>
                                    <tr>
                                        <td style="color: #d1c7bd;" class="small fw-medium"><?= date('H:i', strtotime($row['tanggal_transaksi'])); ?> WIB</td>
                                        <td class="fw-bold text-white">#TRX-<?= str_pad($row['id_transaksi'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td><span class="text-white-50 small fw-medium"><?= htmlspecialchars($row['nama_lengkap'] ?? 'Sistem'); ?></span></td>
                                        <td class="fw-bold" style="color: var(--coffee-gold);">Rp <?= number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center text-muted py-5 small fw-medium card-desc'>Belum ada transaksi terekam hari ini.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card premium-card h-100">
                        <div class="mb-4">
                            <h5 class="fw-bold text-white mb-1"><i class="fas fa-crown me-2 text-warning" style="font-size: 1rem;"></i>Signature Menu</h5>
                            <p class="card-desc mb-0">3 Produk paling diminati oleh pelanggan.</p>
                        </div>

                        <div class="d-flex flex-column gap-3">
                            <?php 
                            if (mysqli_num_rows($result_fav) > 0) {
                                $rank = 1;
                                while ($fav = mysqli_fetch_assoc($result_fav)) {
                            ?>
                            <div class="p-3 rounded-3 d-flex align-items-center justify-content-between" style="background-color: rgba(255,255,255,0.02); border: 1px solid var(--border-glow);">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fs-6 fw-bold text-warning" style="width: 20px; text-align: center;">
                                        <?php if($rank == 1): ?> <i class="fas fa-trophy"></i> <?php else: echo $rank; endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-white small"><?= htmlspecialchars($fav['nama_menu']); ?></div>
                                        <span class="card-desc" style="font-size: 0.75rem;">Terjual: <b class="text-white"><?= $fav['total_qty']; ?> cup</b></span>
                                    </div>
                                </div>
                                <span class="luxury-badge" style="font-size: 0.65rem;">Favorit</span>
                            </div>
                            <?php 
                                    $rank++;
                                }
                            } else {
                                echo "<div class='text-center py-5 small fw-medium card-desc'>Data penjualan belum mencukupi.</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        document.getElementById('liveClock').textContent = now.toTimeString().split(' ')[0] + " WIB";
    }
    setInterval(updateClock, 1000);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>