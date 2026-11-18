<?php
session_start();
require_once "config/koneksi.php";

$error = "";
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    // Tombol mana yang ditekan
    $login_sebagai = isset($_POST['login_admin']) ? 'admin' : 'user';

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($user && password_verify($password, $user['password'])) {

                // Pengaman: role selain admin/user dianggap data akun bermasalah
                if (!in_array($user['role'], ['admin', 'user'], true)) {
                    session_unset();
                    session_destroy();
                    session_start();
                    $_SESSION['error'] = 'Terjadi kesalahan pada data akun Anda. Mohon hubungi pihak administrator';
                    header("Location: login.php");
                    exit;
                }

                // Role harus cocok dengan tombol yang ditekan
                if ($user['role'] !== $login_sebagai) {
                    $error = ($login_sebagai === 'admin')
                        ? "Akun ini bukan akun admin."
                        : "Akun admin silakan gunakan tombol Login as Admin.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['id_user']  = $user['id_user'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role']     = $user['role'];

                    header("Location: " . ($user['role'] === 'admin' ? "admin/dashboard.php" : "index.php"));
                    exit;
                }
            } else {
                $error = "Username atau password salah!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    } else {
        $error = "Harap isi semua kolom!";
    }
}
?>
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

        <div class="image-container">
            <img src="assets/img/img-login.jpg" alt="Illustration">
        </div>
    </div>
</body>

</html>