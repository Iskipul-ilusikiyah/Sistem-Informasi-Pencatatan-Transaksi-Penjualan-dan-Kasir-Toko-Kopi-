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

// Default tanggal (awal bulan sampai hari ini)
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');

// Query Laporan Keuangan berdasarkan filter tanggal
$query_laporan = "SELECT t.*, u.nama_lengkap FROM transaksi t 
                  LEFT JOIN user u ON t.id_user = u.id_user 
                  WHERE DATE(t.tanggal_transaksi) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                  ORDER BY t.tanggal_transaksi DESC";
$result_laporan = mysqli_query($koneksi, $query_laporan);

// Hitung total omset dan volume
$q_total = mysqli_query($koneksi, "
SELECT
SUM(
    COALESCE(total_bayar,0) +
    COALESCE(total_harga,0)
) as omset,
COUNT(id_transaksi) as volume
FROM transaksi
WHERE DATE(tanggal_transaksi) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
");
$r_total = mysqli_fetch_assoc($q_total);
$total_omset = $r_total['omset'] ?? 0;
$total_volume = $r_total['volume'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan</title>
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
            --text-muted: #d1c7bd;    /* DIUBAH: Menjadi warna abu terang kontras tinggi */
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

        /* Premium Form & Cards Configuration */
        .premium-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-glow);
            border-radius: 16px;
            padding: 24px;
        }
        .form-control {
            background-color: #14110f !important;
            border: 1px solid var(--border-glow) !important;
            color: #ffffff !important;
            padding: 12px;
        }
        .form-control:focus {
            border-color: var(--coffee-gold) !important;
            box-shadow: none;
        }

        /* KONTRAST MAKSIMAL UNTUK TEKS REDUP */
        .form-label-custom {
            color: #d1c7bd !important; /* Label DARI/SAMPAI TANGGAL */
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.8px;
        }
        .card-label-custom {
            color: #e6b88a !important; /* Label TOTAL OMSET & VOLUME */
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.8px;
        }
        .card-desc-custom {
            color: #b5a89e !important; /* Keterangan tambahan (Nota Terbuku, dll) */
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-coffee {
            background: linear-gradient(135deg, #c59b6e, var(--coffee-gold));
            color: #14110f;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            padding: 12px;
            transition: all 0.2s ease;
        }
        .btn-coffee:hover {
            opacity: 0.9;
            color: #14110f;
        }

        /* Table Design */
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
                <a href="dashboard.php" class="nav-link-custom"><i class="fas fa-chart-pie me-3"></i> Dashboard</a>
                <a href="transaksi.php" class="nav-link-custom"><i class="fas fa-cash-register me-3"></i> Transaksi Kasir</a>
            </div>
            <div class="mb-4">
                <p class="sidebar-section-title text-uppercase mb-3 px-2">Pencatatan & Master</p>
                <a href="menu.php" class="nav-link-custom"><i class="fas fa-mug-hot me-3"></i> Kelola Menu/Stok</a>
                <a href="laporan.php" class="nav-link-custom active"><i class="fas fa-file-invoice-dollar me-3"></i> Laporan Keuangan</a>
                <a href="user.php" class="nav-link-custom"><i class="fas fa-user-shield me-3"></i> Manajemen Kasir</a>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-10 p-4">
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                <div>
                    <span class="text-uppercase small fw-bold tracking-wider" style="color: var(--coffee-gold); font-size: 0.75rem;">Financial Statement</span>
                    <h1 class="h2 fw-bold text-white mt-1" style="font-family: 'Playfair Display', serif;">Laporan Keuangan</h1>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-outline-light btn-sm px-4 py-2 fw-bold" style="border-radius: 8px; border-color: rgba(255,255,255,0.2);">
                        <i class="fas fa-print me-2"></i> Cetak Laporan
                    </button>
                </div>
            </div>

            <!-- FILTER TANGGAL CARD -->
            <div class="card premium-card mb-4">
                <form action="laporan.php" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label form-label-custom text-uppercase">Dari Tanggal</label>
                        <input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-custom text-uppercase">Sampai Tanggal</label>
                        <input type="date" name="tgl_selesai" class="form-control" value="<?= $tgl_selesai; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-coffee w-100 fw-bold">
                            <i class="fas fa-filter me-2"></i> Saring Data
                        </button>
                    </div>
                </form>
            </div>

            <!-- METRICS SUMMARY RINGKASAN -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="premium-card d-flex justify-content-between align-items-center" style="border-left: 4px solid #e6b88a;">
                        <div>
                            <span class="card-label-custom text-uppercase mb-2 d-block">Total Omset Pendapatan</span>
                            <h2 class="fw-bold text-white m-0" style="font-size: 1.8rem;">Rp <?= number_format($total_omset, 0, ',', '.'); ?></h2>
                        </div>
                        <div class="fs-2 opacity-20 text-white-50"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="premium-card d-flex justify-content-between align-items-center" style="border-left: 4px solid #198754;">
                        <div>
                            <span class="card-label-custom text-uppercase mb-2 d-block">Volume Transaksi</span>
                            <h2 class="fw-bold text-white m-0" style="font-size: 1.8rem;"><?= $total_volume; ?> <span class="card-desc-custom fw-normal" style="font-size: 1rem;">Nota Terbuku</span></h2>
                        </div>
                        <div class="fs-2 opacity-20 text-white-50"><i class="fas fa-receipt"></i></div>
                    </div>
                </div>
            </div>

            <!-- TABLE LAPORAN -->
            <div class="card premium-card">
                <div class="table-responsive">
                    <table class="table table-premium align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 8%;">NO.</th>
                                <th>TANGGAL & WAKTU</th>
                                <th>NOMOR NOTA</th>
                                <th>NAMA KASIR</th>
                                <th class="text-end">TOTAL PENDAPATAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($result_laporan) > 0) {
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($result_laporan)) {
                            ?>
                            <tr>
                                <td style="color: #d1c7bd;" class="fw-medium"><?= $no++; ?></td>
                                <td style="color: #d1c7bd;" class="fw-medium"><?= date('d-m-Y H:i', strtotime($row['tanggal_transaksi'])); ?> WIB</td>
                                <td class="fw-bold text-white">#TRX-<?= str_pad($row['id_transaksi'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><span class="text-white-50 fw-medium"><?= htmlspecialchars($row['nama_lengkap'] ?? 'Sistem'); ?></span></td>
<td class="text-end fw-bold" style="color: var(--coffee-gold);">
    Rp <?= number_format(
        ($row['total_bayar'] ?? 0) + ($row['total_harga'] ?? 0),
        0,
        ',',
        '.'
    ); ?>
</td>                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center py-5 small fw-medium card-desc-custom'>Tidak ada data transaksi yang terekam pada periode ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>