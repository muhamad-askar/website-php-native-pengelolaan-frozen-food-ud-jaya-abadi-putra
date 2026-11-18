<?php
require_once __DIR__ . '/auth.php'; // memulai session + memuat koneksi ($pdo, BASE_URL)
require_once __DIR__ . '/helpers.php';

$page_title = isset($page_title) ? $page_title . ' | UD Jaya Abadi Putra' : 'UD Jaya Abadi Putra';

// Jumlah item di keranjang (hanya kalau sudah login)
$jumlah_keranjang = 0;
if (isset($_SESSION['id_user'])) {
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM keranjang WHERE id_user = ?");
        $stmt->execute([$_SESSION['id_user']]);
        $jumlah_keranjang = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        // tabel keranjang belum dibuat, abaikan dulu
    }
}

$link_akun      = isset($_SESSION['id_user']) ? 'profil.php' : 'login.php';
$link_keranjang = isset($_SESSION['id_user']) ? 'keranjang.php' : 'login.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>

<body>
    <header class="navbar">
        <a href="<?= BASE_URL ?>index.php" class="navbar-logo">
            <img src="<?= BASE_URL ?>assets/img/logo.svg" alt="Logo UD Jaya Abadi Putra">
        </a>
        <nav class="navbar-menu">
            <a href="<?= BASE_URL ?>index.php">Home</a>
            <a href="<?= BASE_URL ?>produk.php">Product</a>
            <a href="<?= BASE_URL ?>about.php">About Us</a>
            <a href="<?= BASE_URL . $link_akun ?>" title="Akun"><i class="fa-solid fa-user"></i></a>
            <a href="<?= BASE_URL . $link_keranjang ?>" title="Keranjang" class="cart-link">
                <i class="fa-solid fa-cart-shopping"></i>
                <?php if ($jumlah_keranjang > 0): ?>
                    <span class="cart-badge"><?= $jumlah_keranjang ?></span>
                <?php endif; ?>
            </a>
        </nav>
    </header>

    <main>