<?php
session_start();
require_once "config/koneksi.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email_user']);
    $password = $_POST['password_user'];

    if (!empty($email) && !empty($password)) {
        try {
            // Cari user berdasarkan email
            $stmt = $pdo->prepare("SELECT * FROM user WHERE email_user = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // verifikasi password & cek apakah user ditemukan
            if ($user && password_verify($password, $user['password_user'])) {
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nama_user'] = $user['nama_user'];
                $_SESSION['role_user'] = $user['role_user'];

                header("Location: index.php");
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

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Website</title>
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

        .login-card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 320px;
        }

        .login-card h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .btn-login {
            width: 100%;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-login:hover {
            background: #0056b3;
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
    </style>
</head>

<body>

    <div class="login-card">
        <h2>Login</h2>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email_user" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password_user" required>
            </div>
            <button type="submit" class="btn-login">Masuk</button>
        </form>

        <div style="text-align: center; margin-top: 15px; font-size: 13px;">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>

</body>

</html>