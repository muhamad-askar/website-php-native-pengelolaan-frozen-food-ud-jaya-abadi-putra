<?php
$page_title = "Home";
require_once "includes/header.php";
?>

<div class="container">
    <h2>
        Selamat Datang<?= isset($_SESSION['username']) ? ', ' . htmlspecialchars($_SESSION['username']) : '' ?>!
    </h2>
    <p>Website UD Jaya Abadi Putra.</p>
</div>

<?php require_once "includes/footer.php"; ?>