<?php
session_start();
include 'koneksi.php';

if (!isset($_GET['id'])) {
    die("ID menu tidak ditemukan!");
}

$id = intval($_GET['id']);

$query = mysqli_query($koneksi, "SELECT * FROM menu WHERE id_menu = $id");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Data menu tidak ditemukan!");
}

if (isset($_POST['update'])) {
    $nama_menu = mysqli_real_escape_string($koneksi, $_POST['nama_menu']);
    $harga     = intval($_POST['harga']);
    $stok      = intval($_POST['stok']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    
    $nama_foto_final = $data['foto']; // Gunakan foto lama sebagai default

    // Logika jika admin mengunggah berkas gambar baru
    if (isset($_FILES['foto_produk']) && $_FILES['foto_produk']['error'] === 0) {
        $file_name = $_FILES['foto_produk']['name'];
        $file_size = $_FILES['foto_produk']['size'];
        $file_tmp  = $_FILES['foto_produk']['tmp_name'];
        
        $ekstensi_diizinkan = ['jpg', 'jpeg', 'png'];
        $ekstensi_file      = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($ekstensi_file, $ekstensi_diizinkan)) {
            if ($file_size <= 2097152) {
                // Hapus foto fisik lama dari folder jika ada dan bukan gambar default
                if (!empty($data['foto']) && $data['foto'] != 'butterscoth.jpg' && file_exists("assets/" . $data['foto'])) {
                    unlink("assets/" . $data['foto']);
                }

                $nama_foto_final = uniqid() . '_' . str_replace(' ', '_', strtolower($nama_menu)) . '.' . $ekstensi_file;
                move_uploaded_file($file_tmp, 'assets/' . $nama_foto_final);
            } else {
                echo "<script>alert('Gagal! Ukuran berkas terlalu besar, maksimal 2MB.'); window.history.back();</script>";
                exit;
            }
        } else {
            echo "<script>alert('Gagal! Format berkas wajib JPG, JPEG, atau PNG.'); window.history.back();</script>";
            exit;
        }
    }

    $update = mysqli_query($koneksi, "
        UPDATE menu SET
            nama_menu = '$nama_menu',
            harga = '$harga',
            stok = '$stok',
            deskripsi = '$deskripsi',
            foto = '$nama_foto_final'
        WHERE id_menu = $id
    ");

    if ($update) {
        echo "<script>
                alert('Menu berhasil diperbarui!');
                window.location='menu.php';
              </script>";
        exit;
    } else {
        echo "<script>alert('Gagal memperbarui menu!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Menu - Kopi Paduka Raja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background: #14110f; 
            color: #f4ebd9; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
        }
        .card-custom { 
            background: #1c1816; 
            border: 1px solid #2d2621; 
            border-radius: 16px; 
            padding: 24px;
        }
        .card-custom .form-label {
            color: #e6b88a !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .form-control { 
            background: #14110f !important; 
            border: 1px solid #2d2621 !important; 
            color: #ffffff !important; 
        }
        .form-control:focus { 
            border-color: #e6b88a !important; 
            box-shadow: none; 
        }
        .btn-coffee { 
            background: linear-gradient(135deg, #c59b6e, #e6b88a); 
            color: #14110f; 
            font-weight: 700; 
            border: none;
        }
        .btn-coffee:hover { 
            opacity: 0.9; 
            color: #14110f; 
        }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-custom shadow">
                <h3 class="mb-4 text-warning fw-bold">✏️ Ubah Data Menu</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Nama Produk</label>
                        <input type="text" name="nama_menu" class="form-control" value="<?= htmlspecialchars($data['nama_menu']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga</label>
                        <input type="number" name="harga" class="form-control" value="<?= $data['harga']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" class="form-control" value="<?= $data['stok']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($data['deskripsi']); ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Ganti Foto Menu</label>
                        <input type="file" name="foto_produk" class="form-control" accept="image/png, image/jpeg, image/jpg">
                        <div class="form-text text-muted small mt-1">Foto saat ini: <strong class="text-white"><?= $data['foto']; ?></strong></div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="update" class="btn btn-coffee py-2">Simpan Perubahan</button>
                        <a href="menu.php" class="btn btn-outline-light py-2">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>