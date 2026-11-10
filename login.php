<?php
session_start();
require_once "config/koneksi.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            // Menggunakan tabel 'users'
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifikasi password & cek keberadaan user
            if ($user && password_verify($password, $user['password'])) {
                // Simpan data pengguna dalam $_SESSION
                $_SESSION['id_user']  = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $error = "Email atau password salah!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    } else {
        $error = "Harap isi semua kolom!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Website</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Welcome Back</h2>
            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Placeholder" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Placeholder" required>
                </div>
                <div class="btn-container">
                    <button type="submit" name="login_user" class="btn-login">Login as User</button>
                    <button type="submit" name="login_admin" class="btn-login">Login as Admin</button>
                </div>
            </form>
            <div style="text-align: center; margin-top: 20px; font-size: 16px; color: #666;">
                Don't have an account? <a href="register.php" style="color: #000; font-weight: bold; text-decoration: none;">Register Now</a>
            </div>
        </div>

        <!-- Image -->
        <div class="image-container">
            <img src="assets/images/img-login.jpg" alt="Illustration">
        </div>
    </div>
</body>

</html>