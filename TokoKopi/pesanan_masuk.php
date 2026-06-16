<?php
session_start();
include 'koneksi.php';

// Logika ketika Kasir menekan tombol Terima
if (isset($_GET['aksi']) && $_GET['aksi'] == 'terima') {
    $id_trx = $_GET['id'];
    
    // 1. Ubah status pesanan menjadi Selesai
    mysqli_query($koneksi, "UPDATE transaksi SET status_pesanan = 'Selesai' WHERE id_transaksi = '$id_trx'");
    
    // 2. Potong stok menu kopi secara otomatis saat uang diterima
    $ambil_detail = mysqli_query($koneksi, "SELECT id_menu, jumlah FROM detail_transaksi WHERE id_transaksi = '$id_trx'");
    while ($d = mysqli_fetch_assoc($ambil_detail)) {
        $id_m = $d['id_menu'];
        $qty = $d['jumlah'];
        mysqli_query($koneksi, "UPDATE menu SET stok = stok - $qty WHERE id_menu = '$id_m'");
    }
    
    echo "<script>alert('Pesanan berhasil diterima dan diselesaikan!'); window.location.href='pesanan_masuk.php';</script>";
    exit;
}

// Ambil semua transaksi yang statusnya masih 'Pending'
$query_pending = mysqli_query($koneksi, "SELECT t.*, u.nama_lengkap FROM transaksi t JOIN user u ON t.id_user = u.id_user WHERE t.status_pesanan = 'Pending' ORDER BY t.tanggal_transaksi ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Pesanan Pelanggan - Kasir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #14110f; color: #ffffff; font-family: 'Plus Jakarta Sans', sans-serif; padding: 30px; }
        .card-kasir { background-color: #1c1816; border: 2px solid #3a322d; border-radius: 12px; padding: 20px; }
        .table-custom { width: 100%; color: white; }
        .table-custom th { color: #e6b88a; padding: 12px; border-bottom: 2px solid #3a322d; }
        .table-custom td { padding: 15px 12px; border-bottom: 1px solid #3a322d; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-2" style="color: #e6b88a;"><i class="fas fa-bell me-2"></i> Monitor Pesanan Masuk (Sisi Kasir)</h2>
        <p class="text-muted mb-4">Daftar antrean pemesanan online pelanggan yang belum membayar di kasir.</p>
        
        <div class="card card-kasir">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Nama Pelanggan</th>
                        <th>Total Tagihan</th>
                        <th>Detail Item</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query_pending) > 0) : ?>
                        <?php while ($row = mysqli_fetch_assoc($query_pending)) : 
                            $id_t = $row['id_transaksi'];
                            // Ambil list item kopi yang dibeli
                            $item_query = mysqli_query($koneksi, "SELECT d.jumlah, m.nama_menu FROM detail_transaksi d JOIN menu m ON d.id_menu = m.id_menu WHERE d.id_transaksi = '$id_t'");
                            $list_kopi = [];
                            while($iq = mysqli_fetch_assoc($item_query)){
                                $list_kopi[] = $iq['nama_menu'] . " (" . $iq['jumlah'] . " Cup)";
                            }
                        ?>
                        <tr>
                            <td><?= date('d M - H:i', strtotime($row['tanggal_transaksi'])); ?> WIB</td>
                            <td class="text-warning"><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                            <td style="color: #e6b88a; font-weight:800;">Rp <?= number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                            <td><small><?= implode(', ', $list_kopi); ?></small></td>
                            <td>
                                <a href="pesanan_masuk.php?aksi=terima&id=<?= $id_t; ?>" class="btn btn-success btn-sm fw-bold px-3">
                                    ✓ Terima & Bayar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada antrean pesanan masuk saat ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>