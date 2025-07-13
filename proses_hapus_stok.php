<?php
// proses_hapus_stok.php
session_start();
require_once 'db_connect.php';

// Keamanan: Hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$jenis_sampah = $_POST['jenis_sampah'];
$berat_diolah = (float)$_POST['berat_diolah'];
$catatan = trim($_POST['catatan']); // Catatan ini tidak akan disimpan, sesuai permintaan

if (empty($jenis_sampah) || $berat_diolah <= 0 || empty($catatan)) {
    $_SESSION['message'] = "Semua field pada form modal harus diisi.";
    header("Location: admin_stok_sampah.php");
    exit();
}

$conn->begin_transaction();
try {
    // 1. Ambil stok saat ini untuk validasi
    $stmt_check = $conn->prepare("SELECT total_berat FROM stok_sampah WHERE jenis_sampah = ?");
    $stmt_check->bind_param("s", $jenis_sampah);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        throw new Exception("Jenis sampah tidak ditemukan di dalam stok.");
    }

    $stok_saat_ini = (float)$result_check->fetch_assoc()['total_berat'];
    $stmt_check->close();

    // 2. Validasi berat yang diolah tidak boleh melebihi stok
    if ($berat_diolah > $stok_saat_ini) {
        throw new Exception("Berat yang diolah ($berat_diolah kg) tidak boleh melebihi stok yang ada ($stok_saat_ini kg).");
    }

    // 3. Kurangi stok di tabel stok_sampah. Inilah proses "penghapusan" datanya.
    $stmt_update = $conn->prepare("UPDATE stok_sampah SET total_berat = total_berat - ? WHERE jenis_sampah = ?");
    $stmt_update->bind_param("ds", $berat_diolah, $jenis_sampah);
    $stmt_update->execute();
    
    // Periksa apakah proses update berhasil
    if ($stmt_update->affected_rows > 0) {
        $_SESSION['message'] = "Berhasil memproses $berat_diolah kg sampah jenis '$jenis_sampah'. Stok telah diperbarui.";
    } else {
        throw new Exception("Gagal memperbarui stok di database.");
    }
    
    $stmt_update->close();
    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    // Menampilkan pesan error yang lebih spesifik
    $_SESSION['message'] = "Gagal memproses: " . $e->getMessage();
}

$conn->close();
header("Location: admin_stok_sampah.php");
exit();
?>