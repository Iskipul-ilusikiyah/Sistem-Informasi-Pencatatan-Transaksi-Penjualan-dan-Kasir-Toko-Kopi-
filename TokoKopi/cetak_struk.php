<?php
include 'koneksi.php';

// Cek ID transaksi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID Transaksi tidak ditemukan!");
}

$id = (int)$_GET['id'];

// Ambil data transaksi
$query = mysqli_query(
    $koneksi,
    "SELECT * FROM transaksi WHERE id_transaksi = $id"
);

if (!$query) {
    die("Query Error: " . mysqli_error($koneksi));
}

$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Data transaksi tidak ditemukan!");
}

// Ambil detail menu
$detail = mysqli_query(
    $koneksi,
    "SELECT d.*, m.nama_menu
     FROM detail_transaksi d
     JOIN menu m ON d.id_menu = m.id_menu
     WHERE d.id_transaksi = $id"
);

// QR Code
$qr_link =
"https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" .
urlencode("http://localhost/TokoKopi/cek_transaksi.php?id=".$id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Struk Transaksi #<?= $id ?></title>

<style>

body{
    font-family: Courier New, monospace;
    background:#f5f5f5;
    padding:20px;
}

.invoice-box{
    width:80mm;
    margin:auto;
    background:#fff;
    border:1px solid #ccc;
    padding:12px;
}

.text-center{
    text-align:center;
}

hr{
    border:none;
    border-top:1px dashed #000;
    margin:8px 0;
}

table{
    width:100%;
    font-size:12px;
    border-collapse:collapse;
}

table td{
    padding:3px 0;
}

.total{
    font-size:16px;
    font-weight:bold;
}

.btn{
    padding:8px 12px;
    border:none;
    background:black;
    color:white;
    cursor:pointer;
}

@media print{
    .no-print{
        display:none;
    }
}

</style>
</head>

<body onload="window.print()">

<div class="invoice-box">

    <div class="text-center">
        <h3 style="margin:0;">TOKO KOPI SEJAHTERA</h3>
        <small>Jl. Kopi Istana No. 1</small>
    </div>

    <hr>

    <p>
        ID Transaksi : <?= $data['id_transaksi']; ?><br>
        Tanggal : <?= $data['tanggal_transaksi']; ?>
    </p>

    <hr>

   <table>
        <?php while($d = mysqli_fetch_assoc($detail)) : 
            // Menghitung harga satuan (Subtotal dibagi Jumlah Beli)
            $harga_satuan = $d['subtotal'] / $d['jumlah'];
        ?>
        <tr>
            <td width="40%"><?= htmlspecialchars($d['nama_menu']); ?></td>
            
            <td width="30%" align="center">
                <?= $d['jumlah']; ?>x @<?= number_format($harga_satuan, 0, ',', '.'); ?>
            </td>
            
            <td width="30%" align="right">
                Rp <?= number_format($d['subtotal'], 0, ',', '.'); ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <hr>

    <div class="total">
        Total :
        Rp <?= number_format($data['total_bayar'],0,',','.'); ?>
    </div>

    <hr>

    <div class="text-center">
        <img src="<?= $qr_link; ?>" width="120">
        <br>
        <small>Scan untuk verifikasi transaksi</small>
    </div>

    <hr>

    <div class="text-center">
        Terima kasih atas kunjungan Anda ☕
    </div>

    <br>

    <div class="text-center no-print">

        <button class="btn" onclick="window.print()">
            Cetak Lagi
        </button>

        <div class="no-print">
    <a href="transaksi.php" class="btn-kembali">Kembali ke Transaksi Kasir</a>
</div>

<style>
/* Sembunyikan tombol ini saat struk dicetak/di-print */
@media print {
    .no-print {
        display: none;
    }
}
</style>
        </a>

    </div>

</div>

</body>
</html>