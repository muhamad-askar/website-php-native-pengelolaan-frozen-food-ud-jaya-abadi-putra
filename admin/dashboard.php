<?php
require_once '../includes/auth.php';
require_admin();

// Update Status Pengiriman / Pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id_order    = intval($_POST['id_order']);
    $status_baru = trim($_POST['status_pesanan']);

    $alur_valid = [
        'sedang_dipersiapkan' => ['dalam_perjalanan', 'dibatalkan'],
        'dalam_perjalanan'    => ['selesai'],
        'menunggu'            => ['sedang_dipersiapkan', 'dibatalkan']
    ];

    try {
        // Status saat ini
        $stmtCek = $pdo->prepare("SELECT status_pesanan FROM pesanan WHERE id_order = ?");
        $stmtCek->execute([$id_order]);
        $pesanan_saat_ini = $stmtCek->fetch(PDO::FETCH_ASSOC);

        if ($pesanan_saat_ini) {
            $status_sekarang = $pesanan_saat_ini['status_pesanan'];

            if (isset($alur_valid[$status_sekarang]) && in_array($status_baru, $alur_valid[$status_sekarang])) {
                $pdo->beginTransaction();

                // Update status tabel pesanan
                $stmtOrder = $pdo->prepare(
                    "UPDATE pesanan
                    SET status_pesanan = ? 
                    WHERE id_order = ?"
                );
                $stmtOrder->execute([$status_baru, $id_order]);

                // Update status tabel pengiriman
                $stmtPengiriman = $pdo->prepare(
                    "UPDATE pengiriman
                    SET status_pengiriman = ?,
                        tanggal_pengiriman = IF(? = 'dalam_perjalanan', NOW(), tanggal_pengiriman)
                    WHERE id_order = ?"
                );
                $stmtPengiriman->execute([$status_baru, $status_baru, $id_order]);

                $pdo->commit();
                $pesan_sukses = "Status pesanan #" . $id_order . " berhasil diperbarui menjadi: " . strtoupper(str_replace('_', ' ', $status_baru));
            } else {
                $pesan_error = "Perubahan status dari '" . $status_sekarang . "' ke '" . $status_baru . "' tidak sesuai alur operasional!";
            }
        } else {
            $pesan_error = "Pesanan tidak ditemukan.";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $pesan_error = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
}

// Filter Status
$filter_status = trim($_GET['status'] ?? 'semua');
$where_clause  = "";
$params        = [];

if ($filter_status !== 'semua' && !empty($filter_status)) {
    $where_clause = "WHERE o.status_pesanan = ?";
    $params[]     = $filter_status;
}

// Fetch Daftar Pesanan
try {
    $sql = "SELECT o.id_order, o.tanggal_pesanan, o.total_harga, o.status_pesanan, 
                u.username, pg.alamat_pengiriman,
                GROUP_CONCAT(CONCAT(p.nama_produk, ' (x', d.jumlah, ')') SEPARATOR ', ') AS rincian_produk
            FROM pesanan o
            JOIN users u ON u.id_user = o.id_user
            LEFT JOIN detail_pesanan d ON d.id_order = o.id_order
            LEFT JOIN produk p ON p.id_produk = d.id_produk
            LEFT JOIN pengiriman pg ON pg.id_order = o.id_order
            $where_clause
            GROUP BY o.id_order
            ORDER BY o.tanggal_pesanan DESC";

    $stmtPesanan = $pdo->prepare($sql);
    $stmtPesanan->execute($params);
    $daftar_pesanan = $stmtPesanan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar_pesanan = [];
    $pesan_error = "Gagal mengambil data pesanan: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - UD Jaya Abadi Putra</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>

    <?php
    $page_active = 'dashboard';
    include BASE_PATH . 'includes/admin_navbar.php';
    ?>

    <div class="container">
        <div class="page-header">
            <h2 class="page-title">Manajemen Pesanan & Pengiriman</h2>
            <span style="font-size: 16px; color: #64748b;">Selamat Datang,
                <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong></span>
        </div>

        <div class="tabs">
            <a href="dashboard.php?status=semua" class="tab-item <?php echo $filter_status === 'semua' ? 'active' : ''; ?>">Semua Pesanan</a>
            <a href="dashboard.php?status=menunggu" class="tab-item <?php echo $filter_status === 'menunggu' ? 'active' : ''; ?>">Menunggu</a>
            <a href="dashboard.php?status=sedang_dipersiapkan" class="tab-item <?php echo $filter_status === 'sedang_dipersiapkan' ? 'active' : ''; ?>">Sedang Dipersiapkan</a>
            <a href="dashboard.php?status=dalam_perjalanan" class="tab-item <?php echo $filter_status === 'dalam_perjalanan' ? 'active' : ''; ?>">Dalam Perjalanan</a>
            <a href="dashboard.php?status=selesai" class="tab-item <?php echo $filter_status === 'selesai' ? 'active' : ''; ?>">Selesai</a>
            <a href="dashboard.php?status=dibatalkan" class="tab-item <?php echo $filter_status === 'dibatalkan' ? 'active' : ''; ?>">Dibatalkan</a>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pembeli</th>
                        <th>Rincian Produk</th>
                        <th>Alamat Pengiriman</th>
                        <th>Tanggal</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Ubah Status (Aksi)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_pesanan)): ?>
                        <?php foreach ($daftar_pesanan as $row): ?>
                            <tr>
                                <td><strong>#<?php echo $row['id_order']; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['rincian_produk'] ?? 'Detail tidak tersedia'); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($row['alamat_pengiriman'] ?? '-')); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_pesanan'])); ?></td>
                                <td><strong>Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $row['status_pesanan']; ?>">
                                        <?php echo str_replace('_', ' ', $row['status_pesanan']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status_pesanan'] !== 'selesai' && $row['status_pesanan'] !== 'dibatalkan'): ?>
                                        <form method="POST" action="" class="status-form">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id_order" value="<?php echo $row['id_order']; ?>">
                                            <select name="status_pesanan" class="status-select" required>
                                                <option value="" disabled selected>Pilih Status</option>
                                                <?php if ($row['status_pesanan'] === 'menunggu'): ?>
                                                    <option value="sedang_dipersiapkan">Sedang Dipersiapkan</option>
                                                    <option value="dibatalkan">Batalkan</option>
                                                <?php elseif ($row['status_pesanan'] === 'sedang_dipersiapkan'): ?>
                                                    <option value="dalam_perjalanan">Dalam Perjalanan</option>
                                                    <option value="dibatalkan">Batalkan</option>
                                                <?php elseif ($row['status_pesanan'] === 'dalam_perjalanan'): ?>
                                                    <option value="selesai">Selesai</option>
                                                <?php endif; ?>
                                            </select>
                                            <button type="submit" class="btn-update">Simpan</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 12px; font-style: italic;">Permanen</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px; color: #94a3b8;">
                                Belum ada data pesanan untuk kategori ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>