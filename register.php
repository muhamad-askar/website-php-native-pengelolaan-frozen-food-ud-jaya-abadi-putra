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
    <title>Register User Baru</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .register-card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 350px;
        }

        .register-card h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            margin-bottom: 4px;
            color: #666;
            font-size: 13px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .btn-reg {
            width: 100%;
            background: #28a745;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-reg:hover {
            background: #218838;
        }

        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 13px;
            text-align: center;
        }

        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 13px;
            text-align: center;
        }

        .link-login {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <div class="register-card">
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
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" rows="2"></textarea>
            </div>
            <button type="submit" class="btn-reg">Daftar</button>
            <div class="link-login">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </form>
    </div>
</body>

</html>