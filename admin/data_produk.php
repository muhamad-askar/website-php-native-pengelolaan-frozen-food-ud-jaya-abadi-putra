<?php
require_once '../includes/auth.php';
require_admin();

$pesan_sukses = "";
$pesan_error  = "";

// Tambah Produk Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_produk') {
    $nama      = trim($_POST['nama_produk'] ?? '');
    $kategori  = trim($_POST['kategori'] ?? '');
    $harga     = floatval($_POST['harga'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $foto_name = 'default.jpg';

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $nama_file = $_FILES['foto']['name'];
        $foto_name = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $nama_file);
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $foto_name);
    }

    if (!empty($nama) && $harga > 0 && !empty($kategori)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO produk (nama_produk, kategori, harga, deskripsi, foto, stok) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$nama, $kategori, $harga, $deskripsi, $foto_name]);
            $pesan_sukses = "Produk baru berhasil ditambahkan!";
        } catch (PDOException $e) {
            $pesan_error = "Gagal menambah produk: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Harap isi nama, kategori, dan harga produk!";
    }
}

// Edit Produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_produk') {
    $id_produk = intval($_POST['id_produk'] ?? 0);
    $nama      = trim($_POST['nama_produk'] ?? '');
    $kategori  = trim($_POST['kategori'] ?? '');
    $harga     = floatval($_POST['harga'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($id_produk > 0 && !empty($nama) && $harga > 0 && !empty($kategori)) {
        try {
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $nama_file = $_FILES['foto']['name'];
                $foto_name = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $nama_file);
                $target_dir = "../uploads/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $foto_name);

                $stmt = $pdo->prepare("UPDATE produk SET nama_produk = ?, kategori = ?, harga = ?, deskripsi = ?, foto = ? WHERE id_produk = ?");
                $stmt->execute([$nama, $kategori, $harga, $deskripsi, $foto_name, $id_produk]);
            } else {
                $stmt = $pdo->prepare("UPDATE produk SET nama_produk = ?, kategori = ?, harga = ?, deskripsi = ? WHERE id_produk = ?");
                $stmt->execute([$nama, $kategori, $harga, $deskripsi, $id_produk]);
            }
            $pesan_sukses = "Data produk berhasil diperbarui!";
        } catch (PDOException $e) {
            $pesan_error = "Gagal memperbarui produk: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Harap isi kolom yang wajib!";
    }
}

// Tambah Batch Stok
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_batch') {
    $id_produk      = intval($_POST['id_produk'] ?? 0);
    $kode_produksi  = trim($_POST['kode_produksi'] ?? '');
    $tgl_produksi   = $_POST['tanggal_produksi'] ?? date('Y-m-d');
    $tgl_kadaluarsa = $_POST['tanggal_kadaluarsa'] ?? '';
    $stok_batch     = intval($_POST['stok_batch'] ?? 0);

    if ($id_produk > 0 && !empty($kode_produksi) && !empty($tgl_kadaluarsa) && $stok_batch > 0) {
        try {
            $pdo->beginTransaction();

            $stmtBatch = $pdo->prepare("INSERT INTO batch_produk (id_produk, kode_produksi, tanggal_produksi, tanggal_kadaluarsa, stok) VALUES (?, ?, ?, ?, ?)");
            $stmtBatch->execute([$id_produk, $kode_produksi, $tgl_produksi, $tgl_kadaluarsa, $stok_batch]);

            $stmtSync = $pdo->prepare("UPDATE produk SET stok = (SELECT COALESCE(SUM(stok), 0) FROM batch_produk WHERE id_produk = ?) WHERE id_produk = ?");
            $stmtSync->execute([$id_produk, $id_produk]);

            $pdo->commit();
            $pesan_sukses = "Batch stok baru berhasil ditambahkan!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $pesan_error = "Gagal menambah batch stok: " . $e->getMessage();
        }
    } else {
        $pesan_error = "Harap lengkapi semua data batch stok!";
    }
}

// Hapus Produk
if (isset($_GET['hapus_id'])) {
    $id_hapus = intval($_GET['hapus_id']);
    try {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM detail_pesanan WHERE id_produk = ?");
        $stmtCek->execute([$id_hapus]);
        if ($stmtCek->fetchColumn() > 0) {
            $pesan_error = "Produk tidak dapat dihapus karena sudah memiliki riwayat pesanan!";
        } else {
            $stmtHapus = $pdo->prepare("DELETE FROM produk WHERE id_produk = ?");
            $stmtHapus->execute([$id_hapus]);
            $pesan_sukses = "Produk berhasil dihapus.";
        }
    } catch (PDOException $e) {
        $pesan_error = "Gagal menghapus produk: " . $e->getMessage();
    }
}

// Notifikasi Kadaluarsa
$batch_kadaluarsa = [];
try {
    $ambang_hari = 30;
    $sqlKadaluarsa = "SELECT b.id_batch, p.nama_produk, b.kode_produksi, b.tanggal_kadaluarsa, b.stok,
                            DATEDIFF(b.tanggal_kadaluarsa, CURDATE()) AS sisa_hari
                        FROM batch_produk b
                        JOIN produk p ON p.id_produk = b.id_produk
                        WHERE b.stok > 0 AND b.tanggal_kadaluarsa <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                        ORDER BY b.tanggal_kadaluarsa ASC";
    $stmtKadaluarsa = $pdo->prepare($sqlKadaluarsa);
    $stmtKadaluarsa->execute([$ambang_hari]);
    $batch_kadaluarsa = $stmtKadaluarsa->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

// Fetch Daftar Produk
try {
    $sqlProduk = "SELECT p.*, 
                        GROUP_CONCAT(CONCAT(b.kode_produksi, ' (Stok: ', b.stok, ' | Exp: ', DATE_FORMAT(b.tanggal_kadaluarsa, '%d/%m/%Y'), ')') SEPARATOR '<br>') AS detail_batch
                    FROM produk p
                    LEFT JOIN batch_produk b ON b.id_produk = p.id_produk
                    GROUP BY p.id_produk
                    ORDER BY p.id_produk DESC";
    $stmtProduk = $pdo->query($sqlProduk);
    $daftar_produk = $stmtProduk->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar_produk = [];
    $pesan_error = "Gagal mengambil data produk: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Stok & Produk - UD Jaya Abadi Putra</title>
    <link rel="stylesheet" href="../assets/css/data_produk.css">
</head>

<body>

    <?php
    $page_active = 'data_produk';
    include BASE_PATH . 'includes/admin_navbar.php';
    ?>

    <div class="container">

        <div class="header-actions">
            <h2 class="page-title">Manajemen Stok & Produk</h2>
            <button class="btn btn-success" onclick="openModal('modalTambahProduk')">
                + Tambahkan Produk
            </button>
        </div>

        <!-- alert kadaluarsa -->
        <?php if (!empty($batch_kadaluarsa)): ?>
            <div class="alert alert-warning">
                <strong>Perhatian Kadaluarsa!</strong>
                <ul style="margin-left: 20px; margin-top: 5px;">
                    <?php foreach ($batch_kadaluarsa as $b): ?>
                        <li>
                            Produk <strong><?php echo htmlspecialchars($b['nama_produk']); ?></strong>
                            (Kode: <?php echo htmlspecialchars($b['kode_produksi']); ?>, Stok: <?php echo $b['stok']; ?>)
                            - Exp: <strong><?php echo date('d/m/Y', strtotime($b['tanggal_kadaluarsa'])); ?></strong>
                            <?php if ($b['sisa_hari'] < 0): ?>
                                <span style="color: #dc2626; font-weight: bold;">[SUDAH KADALUARSA]</span>
                            <?php else: ?>
                                <span style="color: #d97706; font-weight: bold;">[Sisa <?php echo $b['sisa_hari']; ?> Hari]</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesan_sukses)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($pesan_sukses); ?></div>
        <?php endif; ?>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($pesan_error); ?></div>
        <?php endif; ?>

        <!-- Tabel Produk -->
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Foto</th>
                        <th>Nama & Kategori Produk</th>
                        <th>Harga</th>
                        <th>Total Stok</th>
                        <th>Rincian Stok | Masa Kadaluarsa</th>
                        <th style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_produk)): ?>
                        <?php foreach ($daftar_produk as $row): ?>
                            <tr>
                                <td><strong>#<?php echo $row['id_produk']; ?></strong></td>
                                <td>
                                    <img src="../uploads/<?php echo htmlspecialchars($row['foto'] ?? 'default.jpg'); ?>"
                                        alt="Foto Produk" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['nama_produk']); ?></strong><br>
                                    <span style="font-size: 11px; color: #0284c7; font-weight: 600;">
                                        🏷️ <?php echo htmlspecialchars($row['kategori'] ?? 'Umum'); ?>
                                    </span>
                                </td>
                                <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <strong style="color: <?php echo $row['stok'] > 0 ? '#16a34a' : '#dc2626'; ?>;">
                                        <?php echo $row['stok']; ?> pcs
                                    </strong>
                                </td>
                                <td>
                                    <?php if (!empty($row['detail_batch'])): ?>
                                        <div style="font-size: 12px; color: #475569; line-height: 1.5;">
                                            <?php echo $row['detail_batch']; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 12px;">Belum ada batch stok</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <!-- Tombol Tambah Batch -->
                                        <button class="btn btn-success" style="font-size: 11px; padding: 4px 8px;"
                                            onclick="openModalBatch(<?php echo $row['id_produk']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_produk'])); ?>')">
                                            Tambah Stok
                                        </button>
                                        <!-- Tombol Edit Produk -->
                                        <button class="btn btn-warning" style="font-size: 11px; padding: 4px 8px;"
                                            onclick="openModalEdit(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                            Edit Produk
                                        </button>
                                        <!-- Tombol Hapus Produk -->
                                        <a href="data_produk.php?hapus_id=<?php echo $row['id_produk']; ?>"
                                            class="btn btn-danger" style="font-size: 11px; padding: 4px 8px;"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">Hapus Produk</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: #94a3b8;">
                                Belum ada data produk di basis data.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- MODAL Tambah Produk -->
    <div id="modalTambahProduk" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Produk Baru</h3>
                <button class="close-btn" onclick="closeModal('modalTambahProduk')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="tambah_produk">
                    <div class="form-group">
                        <label>Nama Produk</label>
                        <input type="text" name="nama_produk" required placeholder="Contoh: Nugget Ayam Original">
                    </div>
                    <div class="form-group">
                        <label>Kategori Produk</label>
                        <select name="kategori" required>
                            <option value="" disabled selected>Pilih Kategori</option>
                            <option value="Daging Sapi">Daging Sapi</option>
                            <option value="Daging Unggas">Daging Unggas</option>
                            <option value="Seafood">Seafood</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Harga Satuan (Rp)</label>
                        <input type="number" name="harga" min="1" required placeholder="35000">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi Produk</label>
                        <textarea name="deskripsi" rows="2" placeholder="Deskripsi rasa, kemasan, dll."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Foto Produk (JPG/PNG/WEBP)</label>
                        <input type="file" name="foto" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-success" style="width: 100%;">Tambahkan Produk</button>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: Edit Produk -->
    <div id="modalEditProduk" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Data Produk</h3>
                <button class="close-btn" onclick="closeModal('modalEditProduk')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_produk">
                    <input type="hidden" name="id_produk" id="edit_id_produk">
                    <div class="form-group">
                        <label>Nama Produk</label>
                        <input type="text" name="nama_produk" id="edit_nama_produk" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori Produk</label>
                        <select name="kategori" id="edit_kategori" required>
                            <option value="Daging Sapi">Daging Sapi</option>
                            <option value="Daging Unggas">Daging Unggas</option>
                            <option value="Seafood">Seafood</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Harga Satuan (Rp)</label>
                        <input type="number" name="harga" id="edit_harga" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi Produk</label>
                        <textarea name="deskripsi" id="edit_deskripsi" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Ganti Foto (Biarkan kosong jika tidak diubah)</label>
                        <input type="file" name="foto" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-warning" style="width: 100%;">Update Produk</button>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: Tambah Batch Stok -->
    <div id="modalTambahBatch" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><span id="batch_nama_produk"></span></h3>
                <button class="close-btn" onclick="closeModal('modalTambahBatch')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="tambah_batch">
                    <input type="hidden" name="id_produk" id="batch_id_produk">
                    <div class="form-group">
                        <label>Kode Produksi</label>
                        <input type="text" name="kode_produksi" required placeholder="Contoh: BATCH-20261101-A">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Produksi</label>
                        <input type="date" name="tanggal_produksi" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Kadaluarsa (Expired)</label>
                        <input type="date" name="tanggal_kadaluarsa" required>
                    </div>
                    <div class="form-group">
                        <label>Input Jumlah Stok</label>
                        <input type="number" name="stok_batch" min="1" required placeholder="50">
                    </div>
                    <button type="submit" class="btn btn-success" style="width: 100%;">Tambah Batch Stok</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Script JavaScript untuk Operasi Pop-up Modal -->
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Fungsi Buka Modal Edit
        function openModalEdit(data) {
            document.getElementById('edit_id_produk').value = data.id_produk;
            document.getElementById('edit_nama_produk').value = data.nama_produk;
            document.getElementById('edit_kategori').value = data.kategori;
            document.getElementById('edit_harga').value = data.harga;
            document.getElementById('edit_deskripsi').value = data.deskripsi;
            openModal('modalEditProduk');
        }

        // Fungsi Buka Modal Tambah Batch Spesifik Produk
        function openModalBatch(id_produk, nama_produk) {
            document.getElementById('batch_id_produk').value = id_produk;
            document.getElementById('batch_nama_produk').innerText = nama_produk;
            openModal('modalTambahBatch');
        }

        // Tutup Modal jika area luar diklik
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>

</html>