<?php
require_once "config/koneksi.php";

$pesan = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $alamat     = trim($_POST['alamat'] ?? '');
    $role       = 'user'; // Role default untuk registrasi user

    if (!empty($username) && !empty($email) && !empty($password)) {
        try {
            // Cek ketersediaan email pada tabel 'users'
            $stmtCek = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmtCek->execute([$email]);

            if ($stmtCek->rowCount() > 0) {
                $error = "Email sudah terdaftar, silakan gunakan email lain.";
            } else {
                // Enkripsi password
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);

                // Simpan data akun ke tabel 'users'
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, alamat, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password_hashed, $alamat, $role]);

                $pesan = "Registrasi berhasil! Silakan <a href='login.php'>Login di sini</a>.";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    } else {
        $error = "Harap isi kolom yang wajib (Username, Email, dan Password)!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Welcome New People</h2>
            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($pesan)): ?>
                <div class="success-msg"><?php echo $pesan; ?></div>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Placeholder" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Placeholder" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Placeholder" required>
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" placeholder="Placeholder" rows="2"></textarea>
                </div>
                <div class="btn-container">
                    <button type="submit" class="btn-login">Daftar</button>
                </div>
            </form>
            <div style="text-align: center; margin-top: 20px; font-size: 16px; color: #666;">
                Already have an account? <a href="login.php" style="color: #000; font-weight: bold; text-decoration: none;">Login here</a>
            </div>
        </div>

        <!-- Image -->
        <div class="image-container">
            <img src="assets/images/img-login.jpg" alt="Illustration">
        </div>
    </div>
</body>

</html>