<?php
function rupiah($angka)
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function harga_setelah_diskon($harga, $diskon)
{
    return $harga * (100 - (int) $diskon) / 100;
}

function foto_produk_url($foto)
{
    $foto = basename((string) $foto);
    if ($foto !== '' && file_exists(BASE_PATH . 'assets/img/produk/' . $foto)) {
        return BASE_URL . 'assets/img/produk/' . rawurlencode($foto);
    }
    return BASE_URL . 'assets/img/produk/default.svg';
}