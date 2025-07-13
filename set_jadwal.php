<?php
// set_jadwal.php
session_start();
require_once 'db_connect.php';

// Keamanan: Pastikan hanya user yang login yang bisa mengakses
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['pengajuan_id']) && isset($_POST['jadwal_penjemputan'])) {
        $pengajuan_id = $_POST['pengajuan_id'];
        $jadwal_penjemputan = $_POST['jadwal_penjemputan'];
        $user_id_pengirim = $_SESSION['user_id'];

        if (empty($jadwal_penjemputan)) {
            header("Location: dashboard_pengguna.php?page=status_pengajuan&error=jadwalkosong");
            exit();
        }

        $conn->begin_transaction();
        try {
            // 1. Update pengajuan dengan jadwal baru dan status 'Jadwal Ditentukan'
            $stmt_update = $conn->prepare("UPDATE pengajuan SET jadwal_penjemputan = ?, status = 'Jadwal Ditentukan' WHERE id = ? AND user_id = ?");
            $stmt_update->bind_param("sii", $jadwal_penjemputan, $pengajuan_id, $user_id_pengirim);
            $stmt_update->execute();
            $stmt_update->close();

            // 2. Ambil semua ID admin untuk dikirimi notifikasi
            $admin_ids = [];
            $result = $conn->query("SELECT id FROM users WHERE role = 'admin'");
            while ($row = $result->fetch_assoc()) {
                $admin_ids[] = $row['id'];
            }

            // 3. Buat notifikasi untuk setiap admin
            $pesan = "Pengguna {$_SESSION['nama_lengkap']} telah mengatur jadwal untuk pengajuan #{$pengajuan_id}.";
            $link = "dashboard_admin.php";
            $stmt_notif = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, link) VALUES (?, ?, ?)");
            foreach ($admin_ids as $admin_id) {
                $stmt_notif->bind_param("iss", $admin_id, $pesan, $link);
                $stmt_notif->execute();
            }
            $stmt_notif->close();

            $conn->commit();
            // PERBAIKAN: Arahkan kembali ke halaman status di dalam template dasbor
            header("Location: dashboard_pengguna.php?page=status_pengajuan&jadwal=sukses");

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: dashboard_pengguna.php?page=status_pengajuan&jadwal=gagal");
        }
        
        $conn->close();
        exit();
    }
}

// Jika diakses tanpa POST, kembalikan ke dasbor
header("Location: dashboard_pengguna.php?page=status_pengajuan");
exit();
?>