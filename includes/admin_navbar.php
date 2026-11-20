<?php
$page_active = $page_active ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    .navbar {
        background: #0B41C9;
        color: #fff;
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .navbar-logo {
        filter: invert(100%) brightness(200%);
        width: auto; height: 50px;
    }

    .navbar h1 {
        font-size: 20px;
        font-weight: 600;
    }

    .nav-links a {
        color: #cbd5e1;
        text-decoration: none;
        margin-left: 20px;
        font-size: 16px;
        font-weight: 500;
        transition: color 0.2s;
    }

    .nav-links a:hover,
    .nav-links a.active {
        color: #FF9640;
    }

    .nav-links a.logout {
        color: #ff5151;
    }
</style>

<nav class="navbar">
    <img class="navbar-logo" src="<?= BASE_URL ?>assets/img/logo.svg" alt="Logo">
    <div class="nav-links">
        <a href="dashboard.php" class="<?php echo ($page_active === 'dashboard') ? 'active' : ''; ?>">Dashboard Pesanan</a>
        <a href="data_produk.php" class="<?php echo ($page_active === 'data_produk') ? 'active' : ''; ?>">Data Stok & Produk</a>
        <a href="../logout.php" class="logout">Logout</a>
    </div>
</nav>