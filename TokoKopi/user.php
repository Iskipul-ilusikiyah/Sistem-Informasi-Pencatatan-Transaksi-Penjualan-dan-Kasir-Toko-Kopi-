<?php
session_start();

// 1. PROTEKSI UTAMA: Jika belum login, tendang ke halaman login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

// Ambil data session
$nama_user = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : (isset($_SESSION['nama']) ? $_SESSION['nama'] : $_SESSION['username']);
$role_user = isset($_SESSION['role']) ? $_SESSION['role'] : 'Kasir';

// 2. PROTEKSI HAK AKSES: Jika bukan Admin, hadang!
if (strcasecmp($role_user, 'Admin') != 0) {
    echo "<script>
            alert('Akses Ditolak! Kasir tidak diizinkan mengakses halaman Manajemen Akun.');
            window.location.href = 'dashboard.php';
          </script>";
    exit;
}

// =========================================================================
// 🚀 LOGIKA PROSES CRUD (TAMBAH, EDIT, HAPUS)
// =========================================================================

// A. PROSES TAMBAH USER BARU (Ada Role, Tanpa Password)
if (isset($_POST['tambah_user'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    
    // Password otomatis di-set default oleh sistem (misal: 12345)
    $password_default = md5('12345'); 

    // Cek apakah username sudah terdaftar
    $cek_username = mysqli_query($koneksi, "SELECT * FROM user WHERE username = '$username'");
    if (mysqli_num_rows($cek_username) > 0) {
        echo "<script>alert('Gagal! Username sudah digunakan oleh staf lain.'); window.location.href='user.php';</script>";
    } else {
        $query_tambah = mysqli_query($koneksi, "INSERT INTO user (username, password, nama_lengkap, role) VALUES ('$username', '$password_default', '$nama_lengkap', '$role')");
        if ($query_tambah) {
            echo "<script>alert('Akun baru berhasil ditambahkan! Password default: 12345'); window.location.href='user.php';</script>";
        }
    }
}

// B. PROSES EDIT / UPDATE USER (Hanya Username & Nama Lengkap)
if (isset($_POST['edit_user'])) {
    $id_user = intval($_POST['id_user']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    
    $query_update = "UPDATE user SET username='$username', nama_lengkap='$nama_lengkap' WHERE id_user='$id_user'";

    if (mysqli_query($koneksi, $query_update)) {
        echo "<script>alert('Data akun berhasil diperbarui!'); window.location.href='user.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// C. PROSES HAPUS DATA ACCOUNT
if (isset($_GET['hapus'])) {
    $id_user_hapus = intval($_GET['hapus']);
    $query_hapus = mysqli_query($koneksi, "DELETE FROM user WHERE id_user = '$id_user_hapus'");
    if ($query_hapus) {
        echo "<script>alert('Akun kasir berhasil dihapus!'); window.location.href='user.php';</script>";
    }
}

// Ambil semua data akun kasir untuk ditampilkan di tabel
$query_tampil = mysqli_query($koneksi, "SELECT * FROM user ORDER BY role ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kasir - Kopi Paduka Raja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            background-color: var(--bg-card);
            border: 1px solid var(--border-glow);
            border-radius: 16px;
            padding: 24px;
        }

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
        .form-control-premium::placeholder,
.form-control-dark::placeholder {
    color: #f4ebd9 !important;
    opacity: 1;
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

        .modal-content-dark {
            background-color: var(--bg-card);
            border: 1px solid var(--border-glow);
            color: var(--coffee-light);
            border-radius: 16px;
        }
        .modal-header-dark {
            border-bottom: 1px solid var(--border-glow);
        }
        .modal-footer-dark {
            border-top: 1px solid var(--border-glow);
        }
        .form-control-dark {
            background-color: #14110f !important;
            border: 1px solid #4a3e35 !important;
            color: #ffffff !important;
        }
        .form-control-dark:focus {
            border-color: var(--coffee-gold) !important;
            box-shadow: 0 0 8px rgba(230, 184, 138, 0.3);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top py-3">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="dashboard.php" style="font-family: 'Playfair Display', serif; color: var(--coffee-gold); font-weight:700;">
            <i class="fas fa-coffee me-2"></i>TOKO KOPI SEJAHTERA
        </a>
        <div class="ms-auto d-flex align-items-center">
            <div class="me-4 text-end">
                <div class="fw-bold text-white"><?= htmlspecialchars($nama_user); ?></div>
                <span class="luxury-badge py-0 px-2" style="font-size: 0.65rem;">👑 <?= htmlspecialchars($role_user); ?></span>
            </div>
            <a href="logout.php" class="btn btn-sm btn-outline-danger px-3 py-2 fw-semibold">Keluar</a>
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
            
            <?php if (strcasecmp($role_user, 'Admin') == 0) : ?>
                <div class="mb-4">
                    <p class="sidebar-section-title text-uppercase mb-3 px-2">Pencatatan & Master</p>
                    <a href="menu.php" class="nav-link-custom"><i class="fas fa-mug-hot me-3"></i> Kelola Menu/Stok</a>
                    <a href="laporan.php" class="nav-link-custom"><i class="fas fa-file-invoice-dollar me-3"></i> Laporan Keuangan</a>
                    <a href="user.php" class="nav-link-custom active"><i class="fas fa-user-shield me-3"></i> Manajemen Kasir</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-10 p-4">
            <div class="card premium-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold text-white mb-1" style="font-family: 'Playfair Display', serif;">Manajemen Otoritas Kasir</h3>
                        <p class="text-muted small mb-0">Kelola hak masuk sistem, tambah staff baru, atau bekukan akses.</p>
                    </div>
                    <button class="btn btn-sm btn-warning fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalTambah" style="background-color: var(--coffee-gold); color: #14110f; border-radius: 8px;">
                        <i class="fas fa-user-plus me-1"></i> Tambah Akun Baru
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-premium align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID USER</th>
                                <th>USERNAME</th>
                                <th>NAMA LENGKAP</th>
                                <th>HAK AKSES / ROLE</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($query_tampil)) { ?>
                            <tr>
                                <td class="fw-bold text-white">#USR-<?= str_pad($row['id_user'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td class="fw-medium text-warning"><?= htmlspecialchars($row['username']); ?></td>
                                <td><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']); ?></td>
                                <td>
                                    <span class="badge <?= strcasecmp($row['role'], 'Admin') == 0 ? 'bg-warning text-dark' : 'bg-secondary'; ?> px-2 py-1 small">
                                        <?= strtoupper($row['role']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEdit"
                                            data-id="<?= $row['id_user']; ?>"
                                            data-username="<?= htmlspecialchars($row['username']); ?>"
                                            data-nama="<?= htmlspecialchars($row['nama_lengkap']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="user.php?hapus=<?= $row['id_user']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="user.php" method="POST">
            <div class="modal-content modal-content-dark">
                <div class="modal-header modal-header-dark">
                    <h5 class="modal-title fw-bold text-warning"><i class="fas fa-user-plus me-2"></i>Tambah Akun Staf</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Username</label>
                        <input type="text" name="username" class="form-control form-control-dark" placeholder="Contoh: kasir_budi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control form-control-dark" placeholder="Contoh: Budi Sudarsono" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Otoritas Jabatan (Role)</label>
                        <select name="role" class="form-control form-control-dark" required>
                            <option value="Kasir">KASIR (Akses Terbatas)</option>
                            <option value="Admin">ADMIN (Akses Penuh)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer modal-footer-dark">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_user" class="btn btn-sm btn-warning fw-bold text-dark" style="background-color: var(--coffee-gold);">Simpan Akun</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="user.php" method="POST">
            <div class="modal-content modal-content-dark">
                <div class="modal-header modal-header-dark">
                    <h5 class="modal-title fw-bold text-info"><i class="fas fa-edit me-2"></i>Edit Informasi Otoritas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_user" id="edit-id">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Username</label>
                        <input type="text" name="username" id="edit-username" class="form-control form-control-dark" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="edit-nama" class="form-control form-control-dark" required>
                    </div>
                </div>
                <div class="modal-footer modal-footer-dark">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_user" class="btn btn-sm btn-info fw-bold text-dark">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const modalEdit = document.getElementById('modalEdit');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            
            const id = button.getAttribute('data-id');
            const username = button.getAttribute('data-username');
            const nama = button.getAttribute('data-nama');
            
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-username').value = username;
            document.getElementById('edit-nama').value = nama;
        });
    }
</script>
</body>
</html>