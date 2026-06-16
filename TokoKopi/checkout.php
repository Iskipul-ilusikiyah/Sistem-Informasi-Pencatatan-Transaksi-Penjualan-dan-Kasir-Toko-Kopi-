<?php
session_start();
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['username']) || empty($_SESSION['keranjang'])) {
    header("Location: dashboarduser.php");
    exit;
}

$username_aktif = $_SESSION['username'];

$ambil_user = mysqli_query(
    $koneksi,
    "SELECT id_user FROM user WHERE username='$username_aktif'"
);

$data_user = mysqli_fetch_assoc($ambil_user);
$id_user_aktif = $data_user['id_user'] ?? 0;

/* HITUNG TOTAL */
$total_bayar = 0;

foreach ($_SESSION['keranjang'] as $id_menu => $item) {

    if (is_array($item)) {
        $harga = $item['harga'];
        $qty   = $item['qty'];
    } else {
        $m = mysqli_fetch_assoc(
            mysqli_query(
                $koneksi,
                "SELECT harga FROM menu WHERE id_menu='$id_menu'"
            )
        );

        $harga = $m['harga'];
        $qty   = $item;
    }

    $total_bayar += ($harga * $qty);
}

$tanggal_sekarang = date('Y-m-d H:i:s');

/* SIMPAN TRANSAKSI */
$insert_transaksi = mysqli_query(
    $koneksi,
    "INSERT INTO transaksi
    (id_user, tanggal_transaksi, total_bayar, status_pesanan)
    VALUES
    ('$id_user_aktif', '$tanggal_sekarang', '$total_bayar', 'Pending')"
);

// Trigger untuk menampilkan SweetAlert2 di bagian bawah HTML
$show_success_alert = false;

if ($insert_transaksi) {

    $id_transaksi_baru = mysqli_insert_id($koneksi);

    /* SIMPAN DETAIL */
    foreach ($_SESSION['keranjang'] as $id_menu => $item) {

        $qty = is_array($item)
            ? $item['qty']
            : $item;

        mysqli_query(
            $koneksi,
            "INSERT INTO detail_transaksi
            (id_transaksi, id_menu, jumlah)
            VALUES
            ('$id_transaksi_baru', '$id_menu', '$qty')"
        );

        mysqli_query(
            $koneksi,
            "UPDATE menu
            SET stok = stok - $qty
            WHERE id_menu = '$id_menu'"
        );
    }

    // 👑 Keranjang dikosongkan sukses
    $_SESSION['keranjang'] = [];
    
    // Nyalakan trigger SweetAlert2
    $show_success_alert = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memproses Pesanan - Kopi Paduka Raja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-main: #14110f;       
            --bg-card: #1c1816;       
            --coffee-gold: #e6b88a;   
            --coffee-light: #f4ebd9;  
            --border-glow: #2d2621;   
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--coffee-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            margin: 0;
        }

        /* Container Loader Halus Menunggu Pop-up Muncul */
        .loader-container {
            text-align: center;
        }

        .spinner-coffee {
            color: var(--coffee-gold);
            width: 3rem;
            height: 3rem;
            margin-bottom: 15px;
        }

        /* Custom Gaya SweetAlert2 Melayang di Tengah */
        .swal2-popup-dark {
            background-color: var(--bg-card) !important;
            border: 1px solid var(--border-glow) !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6) !important;
        }
        .swal2-confirm-gold {
            background-color: var(--coffee-gold) !important;
            color: #14110f !important;
            font-weight: 700 !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            border-radius: 8px !important;
            padding: 12px 30px !important;
            box-shadow: 0 4px 15px rgba(230, 184, 138, 0.2) !important;
            border: none !important;
        }
    </style>
</head>
<body>

<div class="loader-container">
    <div class="spinner-border spinner-coffee" role="status"></div>
    <h5 class="fw-bold" style="font-family: 'Playfair Display', serif; color: var(--coffee-gold);">Menyimpan Pesanan...</h5>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($show_success_alert) : ?>
<script>
    Swal.fire({
        position: 'center', 
        title: '<span style="font-family: \'Playfair Display\', serif; color: #e6b88a; font-weight: 700; font-size: 1.5rem;">Pesanan Berhasil!</span>',
        html: '<p style="color: #f4ebd9; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 0.95rem; line-height: 1.6;">Pesanan berhasil dikirim!<br>Silakan sebutkan nama Anda ke Kasir untuk membayar.</p>',
        icon: 'success',
        iconColor: '#e6b88a', 
        allowOutsideClick: false, // Menghalangi klik sembarangan di luar modal
        allowEscapeKey: false,
        showConfirmButton: true,
        confirmButtonText: 'Selesai',
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-popup-dark',
            confirmButton: 'swal2-confirm-gold'
        }
    }).then((result) => {
        if (result.isConfirmed || result.isDismissed) {
            // 🎯 TITAH UTAMA: Balik ke katalog menu utama setelah klik selesai
            window.location.href = 'dashboarduser.php'; 
        }
    });
</script>
<?php endif; ?>

</body>
</html>