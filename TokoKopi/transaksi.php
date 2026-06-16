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
$id_user   = $_SESSION['id_user'] ?? 1;

// Trigger variabel untuk mengaktifkan SweetAlert2 mewah
$show_success_alert = false;
$id_transaksi_cetak = 0;
$pesan_alert = "";

// =========================================================================
// 🚀 LOGIKA 1: TARIK PESANAN ONLINE (PERBAIKAN HITUNG ULANG SUBTOTAL ITEM)
// =========================================================================
if (isset($_GET['aksi']) && $_GET['aksi'] == 'tarik_online') {

    $id_transaksi = intval($_GET['id_transaksi']);
    $_SESSION['keranjang'] = [];

    // Ambil detail item dari transaksi online yang pending
    $ambil_detail = mysqli_query($koneksi,"
        SELECT d.*, m.nama_menu, m.harga
        FROM detail_transaksi d
        JOIN menu m ON d.id_menu = m.id_menu
        WHERE d.id_transaksi = '$id_transaksi'
    ");

    while($item = mysqli_fetch_assoc($ambil_detail)) {
        // 👑 PERBAIKAN SAKTI: Hitung ulang subtotal agar tidak membawa nilai 0 dari database online
        $harga_asli = $item['harga'];
        $qty_pesan  = $item['jumlah'];
        
        $_SESSION['keranjang'][$item['id_menu']] = [
            'nama_menu' => $item['nama_menu'],
            'harga'     => $harga_asli,
            'qty'       => $qty_pesan
        ];
    }

    // Kunci session bahwa kasir sedang memproses ID Transaksi Online ini
    $_SESSION['id_transaksi_online'] = $id_transaksi;

    header("Location: transaksi.php");
    exit;
}

// 1. INISIALISASI KERANJANG
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = array();
}

// 2. LOGIKA MASUKKAN KERANJANG (Hanya untuk Transaksi Manual)
if (isset($_POST['tambah_keranjang'])) {
    if (isset($_POST['id_menu'])) {
        $id_menu = intval($_POST['id_menu']);
        $qty = intval($_POST['qty']);

        if ($id_menu > 0 && $qty > 0) {
            $query_cek = mysqli_query($koneksi, "SELECT * FROM menu WHERE id_menu = $id_menu");
            if (mysqli_num_rows($query_cek) > 0) {
                $data_menu = mysqli_fetch_assoc($query_cek);
                
                if ($qty > $data_menu['stok']) {
                    echo "<script>alert('Gagal! Stok tidak mencukupi. Stok tersedia: " . $data_menu['stok'] . "'); window.location.href='transaksi.php';</script>";
                    exit;
                } else {
                    if (isset($_SESSION['keranjang'][$id_menu])) {
                        $_SESSION['keranjang'][$id_menu]['qty'] += $qty;
                    } else {
                        $_SESSION['keranjang'][$id_menu] = [
                            'nama_menu' => $data_menu['nama_menu'],
                            'harga' => $data_menu['harga'],
                            'qty' => $qty
                        ];
                    }
                    header("Location: transaksi.php");
                    exit;
                }
            }
        }
    } else {
        echo "<script>alert('Silakan pilih menu terlebih dahulu'); window.location.href='transaksi.php';</script>";
        exit;
    }
}

// 3. LOGIKA HAPUS ITEM
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    unset($_SESSION['keranjang'][$id_hapus]);
    header("Location: transaksi.php");
    exit;
}

// 4. LOGIKA SELESAIKAN TRANSAKSI (PERBAIKAN TOTAL & DETAIL HARGA ONLINE)
if (isset($_POST['selesaikan_transaksi'])) {
    
    // ---- CABANG A: JIKA YANG DIPROSES ADALAH PESANAN ONLINE PENDING ----
    if (isset($_SESSION['id_transaksi_online'])) {
        $id_online = $_SESSION['id_transaksi_online'];

        // Hitung ulang total belanjaan dari item keranjang online yang ditarik
        $total_bayar_online = 0;
        if (!empty($_SESSION['keranjang'])) {
            foreach ($_SESSION['keranjang'] as $id_m => $item) {
                $subtotal_item = $item['harga'] * $item['qty'];
                $total_bayar_online += $subtotal_item;

                // 👑 UPDATE detail_transaksi agar kolom subtotal di database tidak Rp 0 lagi!
                mysqli_query($koneksi, "
                    UPDATE detail_transaksi 
                    SET subtotal = '$subtotal_item' 
                    WHERE id_transaksi = '$id_online' AND id_menu = '$id_m'
                ");

                // Kurangi stok barang karena pesanan online ini resmi dibuatkan oleh dapur
                $qty_item = $item['qty'];
                mysqli_query($koneksi, "
                    UPDATE menu
                    SET stok = stok - $qty_item
                    WHERE id_menu = $id_m
                ");
            }
        }

        // Update status sekaligus isi total bayarnya agar struk tidak Rp 0
        $update_status = mysqli_query($koneksi, "
            UPDATE transaksi
            SET status_pesanan = 'Selesai',
                total_bayar = '$total_bayar_online'
            WHERE id_transaksi = '$id_online'
        ");

        if ($update_status) {
            $id_transaksi_cetak = $id_online;
            $pesan_alert = "Pesanan online berhasil diselesaikan.<br>Nominal item & total pendapatan kasir diperbarui!";
            $show_success_alert = true;

            unset($_SESSION['id_transaksi_online']);
            unset($_SESSION['keranjang']);
        } else {
            die("Gagal memperbarui data transaksi online: " . mysqli_error($koneksi));
        }
    }

    // ---- CABANG B: JIKA TRANSAKSI MANUAL (PEMBELI DI TEMPAT) ----
    else if (!empty($_SESSION['keranjang'])) {
        $total_bayar = 0;
        foreach ($_SESSION['keranjang'] as $item) {
            $total_bayar += ($item['harga'] * $item['qty']);
        }

        $query_transaksi = "
            INSERT INTO transaksi (
                tanggal_transaksi,
                total_bayar,
                id_user,
                status_pesanan
            ) VALUES (
                NOW(),
                $total_bayar,
                $id_user,
                'Selesai'
            )
        ";

        if (mysqli_query($koneksi, $query_transaksi)) {
            $id_transaksi_baru = mysqli_insert_id($koneksi);

            foreach ($_SESSION['keranjang'] as $id_m => $item) {
                $harga_item = $item['harga'];
                $qty_item = $item['qty'];
                $subtotal_item = $harga_item * $qty_item;

                // Masukkan ke detail transaksi
                mysqli_query($koneksi, "
                    INSERT INTO detail_transaksi (id_transaksi, id_menu, jumlah, subtotal)
                    VALUES ($id_transaksi_baru, $id_m, $qty_item, $subtotal_item)
                ");
               
                // Kurangi stok barang
                mysqli_query($koneksi, "
                    UPDATE menu
                    SET stok = stok - $qty_item
                    WHERE id_menu = $id_m
                ");
            }

            $id_transaksi_cetak = $id_transaksi_baru;
            $pesan_alert = "Transaksi manual berhasil disimpan ke database harian!";
            $show_success_alert = true;

            unset($_SESSION['keranjang']);

        } else {
            echo "<script>alert('Gagal menyimpan transaksi: " . mysqli_error($koneksi) . "');</script>";
        }
    } else {
        echo "<script>alert('Keranjang masih kosong');</script>";
    }
}

$query_opsi_menu = mysqli_query($koneksi, "SELECT * FROM menu WHERE stok > 0 ORDER BY nama_menu ASC");

// =========================================================================
// 🚀 LOGIKA 3: AMBIL DATA KOTAK ANTREAN ONLINE (MURNI STATUS PENDING)
// =========================================================================
$query_kotak_online = mysqli_query($koneksi, "
    SELECT t.id_transaksi, t.id_user, t.tanggal_transaksi, t.total_bayar, u.nama_lengkap
    FROM transaksi t
    JOIN user u ON t.id_user = u.id_user
    WHERE t.status_pesanan = 'Pending'
    ORDER BY t.tanggal_transaksi ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Kasir - Toko Kopi Sejahtera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        
        .form-control, .form-select {
            background-color: #14110f !important;
            border: 1px solid #4a3e35 !important;
            color: #ffffff !important;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 600;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--coffee-gold) !important;
            box-shadow: 0 0 8px rgba(230, 184, 138, 0.3);
        }

        .form-label-custom {
            color: #e6b88a !important;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: block;
        }
        
        .text-desc-custom {
            color: #b5a89e !important;
            font-size: 0.9rem;
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

        .btn-success-custom {
            background: linear-gradient(135deg, #1f9254, #157347) !important;
            color: #ffffff !important;
            font-weight: 700;
            border: 1px solid #1e7e48 !important;
            border-radius: 8px;
            padding: 14px;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(21, 115, 71, 0.2);
        }
        .btn-success-custom:hover:not(:disabled) {
            background: linear-gradient(135deg, #24aa62, #198754) !important;
            box-shadow: 0 4px 20px rgba(25, 135, 84, 0.4);
        }
        .btn-success-custom:disabled {
            background: #282421 !important;
            color: #5c524a !important;
            border: 1px solid #38302a !important;
            cursor: not-allowed;
            box-shadow: none;
        }

        .table-premium-wrapper {
            background-color: #14110f !important;
            border: 1px solid var(--border-glow);
            border-radius: 8px;
            padding: 8px;
        }
        
        .table-premium {
            background-color: #14110f !important;
            color: var(--coffee-light) !important;
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
            padding: 12px;
        }
        .table-premium tbody tr td {
            padding: 14px 12px;
            background-color: #14110f !important;
            border-bottom: 1px solid var(--border-glow) !important;
            color: var(--coffee-light) !important;
        }
        
        .text-empty-cart {
            color: #b5a89e !important;
            font-weight: 500;
            background-color: #14110f !important;
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

        .kotak-online-wrapper {
            background: #1c1816;
            border: 2px dashed var(--coffee-gold);
            border-radius: 16px;
            padding: 18px;
            max-height: 230px;
            overflow-y: auto;
        }
        .kartu-antrean-ol {
            background: #14110f;
            border: 1px solid var(--border-glow);
            border-radius: 10px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .kartu-antrean-ol:hover {
            border-color: var(--coffee-gold);
            background: #1f1a17;
            transform: translateY(-2px);
        }

        /* 👑 Style Kustom SweetAlert2 Kasir Sejahtera */
        .swal2-popup-dark {
            background-color: var(--bg-card) !important;
            border: 1px solid var(--border-glow) !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8) !important;
        }
        .swal2-confirm-gold {
            background-color: var(--coffee-gold) !important;
            color: #14110f !important;
            font-weight: 700 !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            border-radius: 8px !important;
            padding: 12px 28px !important;
            box-shadow: 0 4px 15px rgba(230, 184, 138, 0.3) !important;
            border: none !important;
            font-size: 0.95rem !important;
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
                <a href="transaksi.php" class="nav-link-custom active"><i class="fas fa-cash-register me-3"></i> Transaksi Kasir</a>
            </div>
            <div class="mb-4">
                <p class="sidebar-section-title text-uppercase mb-3 px-2">Pencatatan & Master</p>
                <a href="menu.php" class="nav-link-custom"><i class="fas fa-mug-hot me-3"></i> Kelola Menu/Stok</a>
                <a href="laporan.php" class="nav-link-custom"><i class="fas fa-file-invoice-dollar me-3"></i> Laporan Keuangan</a>
                <a href="user.php" class="nav-link-custom"><i class="fas fa-user-shield me-3"></i> Manajemen Kasir</a>
            </div>
        </div>

        <div class="col-md-10 p-4">
            
            <div class="mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                <h1 class="h2 fw-bold text-white mb-1" style="font-family: 'Playfair Display', serif;">Transaksi Kasir</h1>
                <p class="text-desc-custom mb-0">Input pesanan pelanggan dengan cepat, akurat, dan responsif.</p>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold mb-0 text-white" style="font-size: 1.05rem;"><i class="fas fa-bolt me-2 text-warning"></i> Kotak Masuk Pesanan Online</h5>
                    <?php if(isset($_SESSION['id_transaksi_online'])): ?>
                        <span class="badge bg-warning text-dark fw-bold px-3 py-1">Memproses Data Online (#<?= $_SESSION['id_transaksi_online']; ?>)</span>
                    <?php endif; ?>
                </div>
                <div class="kotak-online-wrapper">
                    <div class="row g-2">
                        <?php if (mysqli_num_rows($query_kotak_online) > 0) : ?>
                            <?php while($ol = mysqli_fetch_assoc($query_kotak_online)) : ?>
                            <div class="col-md-4">
                                <div class="kartu-antrean-ol" onclick="window.location.href='transaksi.php?aksi=tarik_online&id_transaksi=<?= $ol['id_transaksi']; ?>'">
                                    <div class="d-flex justify-content-between small text-white mb-1">
                                        <span class="text-white">
                                            <i class="far fa-clock me-1"></i>
                                            <?= date('H:i', strtotime($ol['tanggal_transaksi'])); ?> WIB
                                        </span>
                                        <span class="badge bg-warning text-dark fw-bold" style="font-size: 0.65rem;">PENDING</span>
                                    </div>
                                    <h6 class="text-white mb-1 fw-bold"><?= htmlspecialchars($ol['nama_lengkap']); ?></h6>
                                    <p class="small text-white mb-1" style="font-size: 0.8rem;">ID Transaksi #<?= $ol['id_transaksi']; ?></p>
                                    <span class="small fw-bold" style="color: var(--coffee-gold);">Total: Rp <?= number_format($ol['total_bayar'],0,',','.'); ?></span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <div class="col-12 text-center text-white py-4">☕ Belum ada antrean pesanan online dari pelanggan masuk.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card premium-card">
                        <h5 class="fw-bold mb-4 text-white" style="font-size: 1.1rem;"><i class="fas fa-cart-plus me-2 text-warning"></i> Tambah Item</h5>
                        
                        <form action="transaksi.php" method="POST">
                            <div class="mb-4">
                                <label class="form-label form-label-custom mb-2">Pilih Menu Kopi / Makanan</label>
                                <select name="id_menu" class="form-select" <?= isset($_SESSION['id_transaksi_online']) ? 'disabled' : ''; ?> required>
                                    <option value="" disabled selected>-- Pilih Produk Varian --</option>
                                    <?php while($m = mysqli_fetch_assoc($query_opsi_menu)): ?>
                                        <option value="<?= $m['id_menu']; ?>">
                                            <?= htmlspecialchars($m['nama_menu']); ?> (Stok: <?= $m['stok']; ?>) - Rp <?= number_format($m['harga'], 0, ',', '.'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if(isset($_SESSION['id_transaksi_online'])): ?>
                                    <small class="text-warning mt-1 d-block">*Kunci otomatis: Sedang memproses pesanan online.</small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <label class="form-label form-label-custom mb-2">Jumlah Beli (Qty)</label>
                                <input type="number" name="qty" class="form-control text-center" min="1" value="1" <?= isset($_SESSION['id_transaksi_online']) ? 'disabled' : ''; ?> required>
                            </div>

                            <button type="submit" name="tambah_keranjang" class="btn btn-coffee w-100 fw-bold mt-2" <?= isset($_SESSION['id_transaksi_online']) ? 'disabled' : ''; ?>>
                                <i class="fas fa-plus me-2"></i> Masukkan Keranjang
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card premium-card">
                        <h5 class="fw-bold mb-4 text-white" style="font-size: 1.1rem;"><i class="fas fa-clipboard-list me-2 text-warning"></i> Detail Keranjang Belanja</h5>
                        
                        <div class="table-premium-wrapper table-responsive mb-4" style="min-height: 150px;">
                            <table class="table table-premium align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>NAMA PRODUK</th>
                                        <th class="text-center">QTY</th>
                                        <th class="text-end">HARGA</th>
                                        <th class="text-end">SUBTOTAL</th>
                                        <th class="text-center">HAPUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $grand_total = 0;
                                    if (!empty($_SESSION['keranjang'])): 
                                        foreach ($_SESSION['keranjang'] as $id_m => $item): 
                                            $subtotal = $item['harga'] * $item['qty'];
                                            $grand_total += $subtotal;
                                    ?>
                                    <tr>
                                        <td class="fw-bold text-white"><?= htmlspecialchars($item['nama_menu']); ?></td>
                                        <td class="text-center fw-bold text-info"><?= $item['qty']; ?></td>
                                        <td class="text-end">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></td>
                                        <td class="text-end fw-bold" style="color: var(--coffee-gold);">Rp <?= number_format($subtotal, 0, ',', '.'); ?></td>
                                        <td class="text-center">
                                            <?php if(isset($_SESSION['id_transaksi_online'])): ?>
                                                <span class="text-muted small">Lock</span>
                                            <?php else: ?>
                                                <a href="transaksi.php?hapus=<?= $id_m; ?>" class="text-danger fs-5"><i class="fas fa-minus-circle"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                        endforeach; 
                                    else: 
                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-empty-cart">Keranjang belanja kosong. Siap menerima pesanan baru.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top border-secondary border-opacity-20 pt-3 mb-4">
                            <span class="fw-bold text-uppercase tracking-wider" style="color: #b5a89e; font-size: 0.9rem;">Total Pembayaran:</span>
                            <h2 class="fw-bold m-0" style="color: var(--coffee-gold); font-size: 2.2rem;">Rp <?= number_format($grand_total, 0, ',', '.'); ?></h2>
                        </div>

                        <form action="transaksi.php" method="POST">
                            <button type="submit" name="selesaikan_transaksi" class="btn btn-success-custom fs-5 py-3" <?= empty($_SESSION['keranjang']) ? 'disabled' : ''; ?>>
                                <i class="fas fa-check-double me-2"></i> Selesaikan & Cetak Struk
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($show_success_alert) : ?>
<script>
    Swal.fire({
        position: 'center',
        title: '<span style="font-family: \'Playfair Display\', serif; color: #e6b88a; font-weight: 700; font-size: 1.5rem;">Transaksi Sukses!</span>',
        html: '<p style="color: #f4ebd9; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 0.95rem; line-height: 1.6;"><?= $pesan_alert; ?></p>',
        icon: 'success',
        iconColor: '#e6b88a',
        allowOutsideClick: false, 
        allowEscapeKey: false,
        showConfirmButton: true,
        confirmButtonText: '<i class="fas fa-print me-2"></i> Cetak Struk Sekarang',
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-popup-dark',
            confirmButton: 'swal2-confirm-gold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // 🎯 DIRECT ACTION: Bawa kasir langsung cetak struk sesuai ID transaksi dinamis
            window.location.href = 'cetak_struk.php?id=<?= $id_transaksi_cetak; ?>';
        }
    });
</script>
<?php endif; ?>

</body>
</html>