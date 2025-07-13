<?php
// assign_kurir.php
session_start();
require_once 'db_connect.php';

// Keamanan: Hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['pengajuan_id']) && isset($_POST['kurir_id'])) {
        $pengajuan_id = $_POST['pengajuan_id'];
        $kurir_id = $_POST['kurir_id'];

        $conn->begin_transaction();
        try {
            // --- PERBAIKAN DI SINI ---
            // 1. Update pengajuan dengan kurir_id DAN ubah status menjadi 'Dalam Penjemputan'
            $stmt_update = $conn->prepare("UPDATE pengajuan SET kurir_id = ?, status = 'Dalam Penjemputan' WHERE id = ?");
            $stmt_update->bind_param("ii", $kurir_id, $pengajuan_id);
            $stmt_update->execute();
            $stmt_update->close();

            // 2. Buat notifikasi untuk kurir yang ditugaskan
            $pesan = "Anda mendapatkan tugas penjemputan baru untuk pengajuan #{$pengajuan_id}.";
            $link = "dashboard_kurir.php";
            $stmt_notif = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, link) VALUES (?, ?, ?)");
            $stmt_notif->bind_param("iss", $kurir_id, $pesan, $link);
            $stmt_notif->execute();
            $stmt_notif->close();

            $conn->commit();
            header("Location: dashboard_admin.php?assign=sukses");

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: dashboard_admin.php?assign=gagal");
        }
        
        $conn->close();
        exit();
    }
}

header("Location: dashboard_admin.php");
exit();
?>
