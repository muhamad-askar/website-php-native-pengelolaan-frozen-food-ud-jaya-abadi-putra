<?php
$page_title = "Home";
require_once "includes/header.php";

$promo = $pdo->query(
    "SELECT * FROM produk WHERE diskon > 0 ORDER BY diskon DESC, id_produk DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

$produk = $pdo->query(
    "SELECT * FROM produk ORDER BY id_produk DESC LIMIT 8"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <section class="hero">
        <h1>Tersedia berbagai jenis olahan cepat saji berbahan dasar daging</h1>
        <img src="<?= BASE_URL ?>assets/img/logo.svg" alt="UD Jaya Abadi Putra">
    </section>
</div>

<?php if ($promo): ?>
    <section class="promo">
        <div class="container">
            <h2 class="promo-title">PROMO</h2>
            <div class="product-grid">
                <?php foreach ($promo as $p) include "includes/product_card.php"; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<div class="pill"></div>

<div class="container">
    <div class="section-head">
        <h2>Produk</h2>
        <a href="<?= BASE_URL ?>produk.php" class="link-lainnya">Lihat lebih banyak &gt;&gt;</a>
    </div>

    <?php if (!$produk): ?>
        <p class="kosong">Belum ada produk.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($produk as $p) include "includes/product_card.php"; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once "includes/footer.php"; ?>