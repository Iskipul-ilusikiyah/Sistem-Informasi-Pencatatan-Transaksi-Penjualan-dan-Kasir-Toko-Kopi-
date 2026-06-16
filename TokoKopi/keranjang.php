<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    $id_hapus = $_GET['id'];
    unset($_SESSION['keranjang'][$id_hapus]);
    header("Location: keranjang.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Toko Kopi Sejahtera</title>
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
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-main); 
            color: #ffffff; 
        }
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
        
        /* JALUR PENYELAMAT: Custom Tabel Anti-Bootstrap Gagal */
        .tabel-raja {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background-color: #14110f !important; /* Memaksa background tetap gelap pekat */
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
            background-color: #1c1816 !important; /* Baris item dijamin gelap murni */
            color: #ffffff !important; /* TEKS UTAMA WAJIB PUTIH BERSIH */
            padding: 20px 14px;
            font-weight: 700;
            font-size: 1.1rem;
            border-bottom: 1px solid var(--border-glow);
        }
        .tabel-raja .total-row td {
            background-color: #231f1d !important;
            border-top: 2px solid var(--coffee-gold);
            padding: 22px 14px;
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
                <a href="keranjang.php" class="nav-link-custom active"><i class="fas fa-shopping-cart me-3"></i> Keranjang</a>
                <a href="riwayat.php" class="nav-link-custom"><i class="fas fa-history me-3"></i> Riwayat Belanja</a>
            </div>
        </div>

        <div class="col-md-10 p-4">
            <div class="mb-4">
                <h2 style="color: #ffffff; font-family: 'Playfair Display', serif; font-weight: 800; font-size: 2.3rem;">Keranjang Belanja</h2>
                <p style="color: var(--coffee-light); font-size: 1.1rem; opacity: 0.9;">Periksa kembali daftar pesanan Anda sebelum memproses konfirmasi.</p>
            </div>

            <div class="card premium-card">
                <?php if (!empty($_SESSION['keranjang'])) : ?>
                    <div class="table-responsive">
                        <table class="tabel-raja">
                            <thead>
                                <tr>
                                    <th>Menu Kopi</th>
                                    <th>Harga Satuan</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                
$grand_total = 0;

foreach ($_SESSION['keranjang'] as $id_menu => $item) :

    if (is_array($item)) {
        $nama_menu = $item['nama_menu'];
        $harga = $item['harga'];
        $qty = $item['qty'];
    } else {
        $ambil_menu = mysqli_query($koneksi, "SELECT * FROM menu WHERE id_menu='$id_menu'");
        $m = mysqli_fetch_assoc($ambil_menu);

        $nama_menu = $m['nama_menu'];
        $harga = $m['harga'];
        $qty = $item;
    }

    $subtotal = $harga * $qty;
    $grand_total += $subtotal;
?>

<tr>
    <td style="color:#ffffff !important;font-size:1.2rem;">
        <?= htmlspecialchars($nama_menu); ?>
    </td>

    <td style="color:var(--coffee-light) !important;">
        Rp <?= number_format($harga,0,',','.'); ?>
    </td>

    <td style="color:#ffffff !important;">
        <?= $qty; ?> Cup
    </td>

    <td style="color:var(--coffee-gold) !important;font-size:1.2rem;font-weight:800;">
        Rp <?= number_format($subtotal,0,',','.'); ?>
    </td>

    <td style="text-align:center;">
        <a href="keranjang.php?aksi=hapus&id=<?= $id_menu; ?>"
           class="btn btn-danger btn-sm px-3">
            <i class="fas fa-trash-alt"></i>
        </a>
    </td>
</tr>

<?php endforeach; ?>

<tr class="total-row">
    <td colspan="3"
        style="text-align:right;color:#ffffff !important;font-size:1.3rem;font-weight:800;">
        TOTAL BAYAR :
    </td>

    <td colspan="2"
        style="color:var(--coffee-gold) !important;font-size:1.4rem;font-weight:800;">
        Rp <?= number_format($grand_total,0,',','.'); ?>
    </td>
</tr>
                                <tr class="total-row">
                                    <td colspan="3" style="text-align: right; color: #ffffff !important; font-size: 1.3rem; font-weight: 800;">TOTAL WAJIB BAYAR :</td>
                                    <td colspan="2" style="color: var(--coffee-gold) !important; font-size: 1.4rem; font-weight: 800;">Rp <?= number_format($grand_total, 0, ',', '.'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <a href="dashboarduser.php" class="btn btn-outline-light fw-bold px-4 py-2"><i class="fas fa-arrow-left me-2"></i> Tambah Menu</a>
                        <a href="checkout.php" class="btn fw-bold px-5 py-2 text-dark" style="background: linear-gradient(135deg, #c59b6e, var(--coffee-gold)); border: none;">
                            Konfirmasi Pesanan <i class="fas fa-check ms-2"></i>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-basket fa-4x mb-3" style="color: var(--border-glow);"></i>
                        <h4 style="color: #ffffff;">Keranjang Kosong</h4>
                        <p style="color: var(--coffee-light);">Silakan kembali ke katalog untuk memesan kopi.</p>
                        <a href="dashboarduser.php" class="btn text-dark fw-bold mt-2 px-4 py-2" style="background: linear-gradient(135deg, #c59b6e, var(--coffee-gold)); border:none;">Lihat Menu</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>