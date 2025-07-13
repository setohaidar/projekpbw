<?php
// login.php
session_start();
require_once 'db_connect.php';
$error = '';

// Jika sudah login, redirect ke dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: dashboard_admin.php");
    } elseif ($_SESSION['role'] == 'kurir') {
        header("Location: dashboard_kurir.php");
    } else { // 'user'
        header("Location: dashboard_pengguna.php");
    }
    exit();
}

// Memproses form jika data dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<script>console.log('p')</script>";
    $login_identifier = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($login_identifier) || empty($password)) {
        $error = "Username/Email dan Password harus diisi.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, nama_lengkap, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $login_identifier, $login_identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Password benar, buat session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];

                // Arahkan berdasarkan role
                if ($user['role'] == 'admin') {
                    header("Location: dashboard_admin.php");
                } elseif ($user['role'] == 'kurir') {
                    header("Location: dashboard_kurir.php");
                } else {
                    header("Location: dashboard_pengguna.php");
                }
                exit();
            } else {
                $error = "Login gagal. Periksa kembali username/email dan password Anda.";
            }
        } else {
            $error = "Login gagal. Periksa kembali username/email dan password Anda.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pengguna</title>
    <link rel="stylesheet" href="register_login.css">
</head>
<body>
    <div class="form-container">
        <h2>Login Pengguna</h2>
        
        <?php if(!empty($error)): ?>
            <div class="error-box">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['status']) && $_GET['status'] == 'registered'): ?>
            <div class="success-box">
                <p>Registrasi berhasil! Silakan login.</p>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="loginForm">
            <div class="input-group">
                <label for="username">Username atau Email:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-main">Login</button>
        </form>
        <div class="switch-form">
            <p>Belum punya akun? <a href="register.php">Buat Akun</a></p>
        </div>
    </div>
</body>
</html>
