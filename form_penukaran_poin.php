<?php
// form_penukaran.php
session_start();
require_once 'db_connect.php';

// Keamanan: Cek apakah pengguna sudah login dan perannya adalah 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

// Validasi ID hadiah dari URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: dashboard_pengguna.php?page=tukar_poin&error=invalid_id");
    exit();
}

$user_id = $_SESSION['user_id'];
$hadiah_id = $_GET['id'];
$errors = [];

// Ambil detail hadiah, poin pengguna, dan alamat pengguna dalam satu koneksi
$conn->begin_transaction();
try {
    // 1. Ambil detail hadiah
    $stmt_hadiah = $conn->prepare("SELECT * FROM hadiah WHERE id = ? AND stok > 0");
    $stmt_hadiah->bind_param("i", $hadiah_id);
    $stmt_hadiah->execute();
    $result_hadiah = $stmt_hadiah->get_result();
    $hadiah = $result_hadiah->fetch_assoc();
    $stmt_hadiah->close();

    if (!$hadiah) {
        throw new Exception("Hadiah tidak ditemukan atau stok habis.");
    }

    // 2. Ambil poin pengguna
    $stmt_poin = $conn->prepare("SELECT poin FROM users WHERE id = ?");
    $stmt_poin->bind_param("i", $user_id);
    $stmt_poin->execute();
    $result_poin = $stmt_poin->get_result();
    $user = $result_poin->fetch_assoc();
    $stmt_poin->close();

    if ($user['poin'] < $hadiah['poin_dibutuhkan']) {
        throw new Exception("Poin Anda tidak mencukupi untuk menukar hadiah ini.");
    }

    // 3. Ambil daftar alamat pengguna
    $alamat_list = [];
    $query_alamat = "SELECT id, label, CONCAT_WS(', ', alamat, no_rumah, kelurahan, kecamatan, kota) AS alamat_display FROM alamat WHERE user_id = ?";
    $stmt_alamat = $conn->prepare($query_alamat);
    $stmt_alamat->bind_param("i", $user_id);
    $stmt_alamat->execute();
    $result_alamat = $stmt_alamat->get_result();
    while ($row = $result_alamat->fetch_assoc()) {
        $alamat_list[] = $row;
    }
    $stmt_alamat->close();
    
    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    header("Location: dashboard_pengguna.php?page=tukar_poin&error=" . urlencode($e->getMessage()));
    exit();
}

// Proses form jika data dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['alamat_id']) && !empty($_POST['alamat_id'])) {
        $alamat_id = $_POST['alamat_id'];

        $conn->begin_transaction();
        try {
            // 1. Kurangi poin pengguna
            $stmt_update_poin = $conn->prepare("UPDATE users SET poin = poin - ? WHERE id = ? AND poin >= ?");
            $stmt_update_poin->bind_param("iii", $hadiah['poin_dibutuhkan'], $user_id, $hadiah['poin_dibutuhkan']);
            $stmt_update_poin->execute();
            if ($stmt_update_poin->affected_rows == 0) {
                throw new Exception("Gagal mengurangi poin. Poin mungkin tidak cukup.");
            }
            $stmt_update_poin->close();

            // 2. Kurangi stok hadiah
            $stmt_update_stok = $conn->prepare("UPDATE hadiah SET stok = stok - 1 WHERE id = ? AND stok > 0");
            $stmt_update_stok->bind_param("i", $hadiah_id);
            $stmt_update_stok->execute();
            if ($stmt_update_stok->affected_rows == 0) {
                throw new Exception("Gagal mengurangi stok. Stok mungkin sudah habis.");
            }
            $stmt_update_stok->close();

            // 3. Buat catatan penukaran baru
            $stmt_insert = $conn->prepare("INSERT INTO penukaran_poin (user_id, hadiah_id, alamat_id, poin_digunakan) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("iiii", $user_id, $hadiah_id, $alamat_id, $hadiah['poin_dibutuhkan']);
            $stmt_insert->execute();
            $penukaran_id = $stmt_insert->insert_id;
            $stmt_insert->close();
            
            // 4. Buat notifikasi untuk admin
            $admin_ids = [];
            $result = $conn->query("SELECT id FROM users WHERE role = 'admin'");
            while ($row = $result->fetch_assoc()) {
                $admin_ids[] = $row['id'];
            }
            $pesan = "Pengguna {$_SESSION['nama_lengkap']} mengajukan penukaran hadiah #{$penukaran_id}.";
            $link = "admin_penukaran_hadiah.php";
            $stmt_notif = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, link) VALUES (?, ?, ?)");
            foreach ($admin_ids as $admin_id) {
                $stmt_notif->bind_param("iss", $admin_id, $pesan, $link);
                $stmt_notif->execute();
            }
            $stmt_notif->close();

            $conn->commit();
            
            // ==========================================================
            // PERBAIKAN: Mengarahkan kembali ke dashboard pengguna
            // ==========================================================
            header("Location: dashboard_pengguna.php?page=status_penukaran_poin&status=sukses");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        $errors[] = "Anda harus memilih alamat pengiriman.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Penukaran Hadiah</title>
    <link rel="stylesheet" href="form_penukaran_poin.css"> 
</head>
<body>
    <div class="form-container">
        <h2>Konfirmasi Penukaran Hadiah</h2>
        
        <?php if(!empty($errors)): ?>
            <div class="error-box">
                <?php foreach($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="reward-summary">
            <img src="<?php echo !empty($hadiah['url_gambar']) ? htmlspecialchars($hadiah['url_gambar']) : 'https://placehold.co/600x400/e2e8f0/e2e8f0?text=Hadiah'; ?>" alt="<?php echo htmlspecialchars($hadiah['nama_hadiah']); ?>">
            <h3><?php echo htmlspecialchars($hadiah['nama_hadiah']); ?></h3>
            <p class="points-needed">Poin Dibutuhkan: <strong><?php echo number_format($hadiah['poin_dibutuhkan']); ?></strong></p>
            <p class="points-available">Poin Anda: <strong><?php echo number_format($user['poin']); ?></strong></p>
        </div>

        <form action="form_penukaran_poin.php?id=<?php echo $hadiah_id; ?>" method="POST">
            <div class="input-group">
                <label for="alamat_id">Pilih Alamat Pengiriman *</label>
                <select id="alamat_id" name="alamat_id" required>
                    <option value="">-- Pilih Alamat Anda --</option>
                    <?php foreach($alamat_list as $alamat): ?>
                        <option value="<?php echo $alamat['id']; ?>">
                            <?php echo htmlspecialchars($alamat['label']) . ' - ' . htmlspecialchars($alamat['alamat_display']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Alamat tidak sesuai? <a href="dashboard_pengguna.php?page=profil_page">Kelola alamat di sini.</a></small>
            </div>

            <button type="submit" class="btn-main">Konfirmasi dan Tukar Poin</button>
            <a href="dashboard_pengguna.php?page=tukar_poin" class="btn-secondary">Batal</a>
        </form>
    </div>
</body>
</html>