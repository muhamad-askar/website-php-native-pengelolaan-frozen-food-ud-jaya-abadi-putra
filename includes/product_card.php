<?php
//data contoh variabel $p
$p = [
    'id_produk'   => '1',
    'nama_produk' => 'Daging Sapi Giling 500gr',
    'kategori'    => 'Daging sapi',
    'harga'       => '75000.00',
    'diskon'      => '10',
    'deskripsi'   => 'Daging sapi giling segar beku, ...',
    'foto'        => 'default.svg',
];

$p['nama_produk'];
$p['harga'];
$p['diskon'];

////////data contoh variabel $p
$harga_akhir = harga_setelah_diskon($p['harga'], $p['diskon']);
$url_detail  = BASE_URL . 'detail_produk.php?id=' . (int) $p['id_produk'];
?>
<div class="product-card">
    <?php if ($p['diskon'] > 0): ?>
        <span class="badge-diskon">-<?= (int) $p['diskon'] ?>%</span>
    <?php endif; ?>

    <a href="<?= $url_detail ?>">
        <img src="<?= foto_produk_url($p['foto']) ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>">
    </a>

    <div class="info">
        <a href="<?= $url_detail ?>" class="nama"><?= htmlspecialchars($p['nama_produk']) ?></a>

        <?php if ($p['diskon'] > 0): ?>
            <div class="harga-coret"><?= rupiah($p['harga']) ?></div>
        <?php endif; ?>

        <div class="bawah">
            <span class="harga"><?= rupiah($harga_akhir) ?></span>
            <form method="post" action="<?= BASE_URL ?>keranjang_tambah.php">
                <input type="hidden" name="id_produk" value="<?= (int) $p['id_produk'] ?>">
                <button type="submit" class="btn btn-sm"><i class="fa-solid fa-cart-shopping"></i> Beli</button>
            </form>
        </div>
    </div>
</div>