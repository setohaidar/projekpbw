<?php
// register.php
require_once 'db_connect.php';
$errors = [];

// Memproses form jika data dikirim (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $nomor_telepon = trim($_POST['nomor_telepon']);
    $password = trim($_POST['password']);

    // Validasi input dasar
    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($nomor_telepon) || empty($password)) {
        $errors[] = "Semua field harus diisi.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = "Password harus minimal 8 karakter dan merupakan kombinasi huruf dan angka.";
    }

    // Hanya lanjutkan jika validasi dasar lolos
    if (empty($errors)) {
        
        $username_exists = false;
        $email_exists = false;
        $nomor_telepon_exists = false;

        // Cek apakah username sudah ada
        $stmt_check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check_user->bind_param("s", $username);
        $stmt_check_user->execute();
        $stmt_check_user->store_result();
        if ($stmt_check_user->num_rows > 0) {
            $username_exists = true;
        }
        $stmt_check_user->close();

        // Cek apakah email sudah ada
        $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $email_exists = true;
        }
        $stmt_check_email->close();
        
        // Cek apakah nomor telepon sudah ada
        $stmt_check_phone = $conn->prepare("SELECT id FROM users WHERE nomor_telepon = ?");
        $stmt_check_phone->bind_param("s", $nomor_telepon);
        $stmt_check_phone->execute();
        $stmt_check_phone->store_result();
        if ($stmt_check_phone->num_rows > 0) {
            $nomor_telepon_exists = true;
        }
        $stmt_check_phone->close();
        
        // Logika pesan error dinamis
        $duplicate_fields = [];
        if ($username_exists) {
            $duplicate_fields[] = "USERNAME";
        }
        if ($email_exists) {
            $duplicate_fields[] = "EMAIL";
        }
        if ($nomor_telepon_exists) {
            $duplicate_fields[] = "NOMOR TELEPON";
        }

        if (!empty($duplicate_fields)) {
            $errors[] = implode(' DAN ', $duplicate_fields) . " TELAH DIGUNAKAN";
        }
    }
    
    // Jika tidak ada error sama sekali, baru lakukan INSERT
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt_insert = $conn->prepare("INSERT INTO users (nama_lengkap, username, email, nomor_telepon, password) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("sssss", $nama_lengkap, $username, $email, $nomor_telepon, $hashed_password);

        if ($stmt_insert->execute()) {
            header("Location: login.php?status=registered");
            exit();
        } else {
            $errors[] = "Terjadi kesalahan pada server. Silakan coba lagi.";
        }
        $stmt_insert->close();
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun Baru</title>
    <link rel="stylesheet" href="register_login.css">
</head>
<body>
    <div class="form-container">
        <h2>Buat Akun Baru</h2>
        
        <?php if(!empty($errors)): ?>
            <div class="error-box">
                <?php foreach($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm" novalidate>
            <div class="input-group">
                <label for="nama_lengkap">Nama Lengkap:</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" required value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>">
            </div>
            <div class="input-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="input-group">
                <label for="nomor_telepon">Nomor Telepon:</label>
                <input type="tel" id="nomor_telepon" name="nomor_telepon" required value="<?php echo htmlspecialchars($_POST['nomor_telepon'] ?? ''); ?>">
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <div id="passwordError" class="error-message"></div>
            </div>
            <button type="submit" class="btn-main">Buat Akun</button>
        </form>
        <div class="switch-form">
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
        </div>
    </div>
    
    <script src="validasi_email_password.js"></script>
</body>
</html>