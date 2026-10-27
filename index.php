<?php
session_start();

// cek status login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "includes/header.php";
?>

    <h2>Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama_user']); ?>!</h2>
    <p>Anda berhasil login ke sistem UD Jaya Abadi Putra.</p>
    
    <br>
    <a href="logout.php" style="background: red; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px;">Logout (Keluar)</a>

<?php 

require_once "includes/footer.php"; 
?>