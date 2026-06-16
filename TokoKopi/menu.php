<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

$nama_user = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : (isset($_SESSION['nama']) ? $_SESSION['nama'] : $_SESSION['username']);
$role_user = isset($_SESSION['role']) ? $_SESSION['role'] : 'Kasir';

// ==========================================
// LOGIKA HAPUS TOTAL (WEB & DATABASE MYSQL)
// ==========================================
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    
    // Sebelum hapus database, amankan nama file fotonya untuk dihapus dari folder assets
    $cek_foto = mysqli_query($koneksi, "SELECT foto FROM menu WHERE id_menu = $id_hapus");
    if ($data_foto = mysqli_fetch_assoc($cek_foto)) {
        $foto_lama = $data_foto['foto'];
        if (!empty($foto_lama) && $foto_lama != 'butterscoth.jpg' && file_exists("assets/" . $foto_lama)) {
            unlink("assets/" . $foto_lama); // Menghapus berkas gambar lama di penyimpanan lokal server
        }
    }

    $ambil_transaksi = mysqli_query($koneksi, "SELECT id_transaksi FROM detail_transaksi WHERE id_menu = $id_hapus");
    
    $id_transaksi_list = [];
    while ($row_transaksi = mysqli_fetch_assoc($ambil_transaksi)) {
        $id_transaksi_list[] = $row_transaksi['id_transaksi'];
    }

    mysqli_query($koneksi, "DELETE FROM detail_transaksi WHERE id_menu = $id_hapus");
    
    if (!empty($id_transaksi_list)) {
        $ids = implode(',', $id_transaksi_list);
        mysqli_query($koneksi, "DELETE FROM transaksi WHERE id_transaksi IN ($ids)");
    }
    
    $query_hapus = mysqli_query($koneksi, "DELETE FROM menu WHERE id_menu = $id_hapus");
    
    if ($query_hapus) {
        echo "<script>
                alert('Sukses! Menu dan seluruh riwayat transaksi di database berhasil dihapus bersih!'); 
                window.location.href='menu.php';
              </script>";
        exit;
    } else {
        echo "<script>alert('Gagal menghapus data di database: " . mysqli_error($koneksi) . "');</script>";
    }
}

$query_menu = mysqli_query($koneksi, "SELECT * FROM menu ORDER BY nama_menu ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu - Toko Kopi Sejahtera</title>
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
        .nav-link-custom:hover {
            color: #ffffff;
            background-color: rgba(230, 184, 138, 0.08);
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
            padding: 24px;
        }

        .btn-coffee-add {
            background: linear-gradient(135deg, #c59b6e, var(--coffee-gold));
            color: #14110f;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-coffee-add:hover {
            opacity: 0.9;
            color: #14110f;
        }

        .table-premium-wrapper {
            background-color: #14110f !important;
            border: 1px solid var(--border-glow);
            border-radius: 12px;
            padding: 12px;
        }
        .table-premium {
            background-color: #14110f !important;
            color: #ffffff !important;
            margin-bottom: 0 !important;
        }
        .table-premium thead th {
            color: var(--coffee-gold) !important;
            background-color: #14110f !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            border-bottom: 2px solid var(--border-glow) !important;
            padding: 14px 12px;
        }
        .table-premium tbody tr td {
            padding: 16px 12px;
            background-color: #14110f !important;
            border-bottom: 1px solid var(--border-glow) !important;
            color: #ffffff !important;
        }

        .page-subtitle-custom {
            color: #b5a89e !important;
            font-size: 0.95rem;
            font-weight: 500;
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

        .admin-menu-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border: 1px solid var(--border-glow);
        }
    </style>
</head>
<body>

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
        
        <div class="col-md-2 sidebar-container p-3 d-none d-md-block">
            <div class="mb-4 mt-2">
                <p class="sidebar-section-title text-uppercase mb-3 px-2">Menu Utama</p>
                <a href="dashboard.php" class="nav-link-custom"><i class="fas fa-chart-pie me-3"></i> Dashboard</a>
                <a href="transaksi.php" class="nav-link-custom"><i class="fas fa-cash-register me-3"></i> Transaksi Kasir</a>
            </div>
            <div class="mb-4">
                <p class="sidebar-section-title text-uppercase mb-3 px-2">Pencatatan & Master</p>
                <a href="menu.php" class="nav-link-custom active"><i class="fas fa-mug-hot me-3"></i> Kelola Menu/Stok</a>
                <a href="laporan.php" class="nav-link-custom"><i class="fas fa-file-invoice-dollar me-3"></i> Laporan Keuangan</a>
                <a href="user.php" class="nav-link-custom"><i class="fas fa-user-shield me-3"></i> Manajemen Kasir</a>
            </div>
        </div>

        <div class="col-md-10 p-4">
            
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                <div>
                    <h1 class="h2 fw-bold text-white mb-1" style="font-family: 'Playfair Display', serif;">Manajemen Menu Kopi</h1>
                    <p class="page-subtitle-custom mb-0">Tambah, ubah, atau hapus racikan produk varian kopi toko Anda.</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    <a href="tambah_menu.php" class="btn btn-coffee-add shadow-sm">
                        <i class="fas fa-plus me-2"></i> Tambah Menu Baru
                    </a>
                </div>
            </div>

            <div class="card premium-card">
                <div class="table-premium-wrapper table-responsive">
                    <table class="table table-premium align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 10%;" class="text-center">FOTO</th>
                                <th style="width: 25%;">NAMA PRODUK</th>
                                <th class="text-end" style="width: 15%;">HARGA</th>
                                <th class="text-center" style="width: 15%;">STOK</th>
                                <th style="width: 20%;">DESKRIPSI PROFIL RASA</th>
                                <th class="text-center" style="width: 15%;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($query_menu) > 0):
                                while ($row = mysqli_fetch_assoc($query_menu)): 
                                    // SINKRONISASI JALUR: Menembak folder assets/ dan cadangan butterscoth.jpg
                                    $foto_tabel = (!empty($row['foto']) && $row['foto'] != 'default_kopi.png') ? $row['foto'] : 'butterscoth.jpg';
                            ?>
                            <tr>
                                <td class="text-center">
                                    <img src="assets/<?= $foto_tabel; ?>" alt="Menu" class="admin-menu-thumb rounded-circle shadow-sm">
                                </td>
                                <td class="fw-bold text-white" style="font-size: 1.05rem;"><?= htmlspecialchars($row['nama_menu']); ?></td>
                                <td class="text-end fw-bold" style="color: var(--coffee-gold); font-size: 1.05rem;">
                                    Rp <?= number_format($row['harga'], 0, ',', '.'); ?>
                                </td>
                                <td class="text-center fw-bold fs-5 <?= ($row['stok'] <= 10) ? 'text-danger' : 'text-success'; ?>">
                                    <?= $row['stok']; ?>
                                </td>
                                <td class="small" style="color: #b5a89e; font-weight: 500;"><?= htmlspecialchars($row['deskripsi'] ?? '-'); ?></td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2">
                                        <a href="ubah_menu.php?id=<?= $row['id_menu']; ?>" class="btn btn-sm btn-outline-warning px-2 py-1" style="border-radius: 6px;" title="Ubah">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="menu.php?hapus=<?= $row['id_menu']; ?>" class="btn btn-sm btn-outline-danger px-2 py-1" style="border-radius: 6px;" onclick="return confirm('Apakah Anda yakin ingin menghapus racikan menu ini beserta seluruh riwayat penjualannya di MySQL?');" title="Hapus">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted fw-medium">Belum ada varian menu kopi yang terdaftar.</td>
                            </tr>
                            <?php endif; ?>
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