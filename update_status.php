<?php
// update_status.php
session_start();
require_once 'db_connect.php';

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Cek apakah semua data yang dibutuhkan dikirim melalui POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['pengajuan_id']) && isset($_POST['status_baru']) && isset($_POST['user_id_penerima'])) {
        
        $pengajuan_id = $_POST['pengajuan_id'];
        $status_baru = $_POST['status_baru'];
        $user_id_penerima = $_POST['user_id_penerima'];

        // Mulai transaksi untuk memastikan kedua operasi (update dan notifikasi) berhasil
        $conn->begin_transaction();

        try {
            // 1. Update status pengajuan di database
            $stmt_update = $conn->prepare("UPDATE pengajuan SET status = ? WHERE id = ?");
            $stmt_update->bind_param("si", $status_baru, $pengajuan_id);
            $stmt_update->execute();
            $stmt_update->close();

            // 2. Siapkan pesan notifikasi untuk pengguna
            $pesan = "";
            $link = "status_pengajuan.php"; // Link tujuan notifikasi

            if ($status_baru == 'Disetujui') {
                $pesan = "Kabar baik! Pengajuan sampah #{$pengajuan_id} Anda telah disetujui. Silakan atur jadwal penjemputan.";
            } else if ($status_baru == 'Ditolak') {
                $pesan = "Mohon maaf, pengajuan sampah #{$pengajuan_id} Anda ditolak. Silakan hubungi admin untuk info lebih lanjut.";
            }

            // 3. Jika ada pesan, simpan notifikasi ke database
            if ($pesan) {
                $stmt_notif = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, link) VALUES (?, ?, ?)");
                $stmt_notif->bind_param("iss", $user_id_penerima, $pesan, $link);
                $stmt_notif->execute();
                $stmt_notif->close();
            }

            // Jika semua query berhasil, simpan perubahan
            $conn->commit();
            header("Location: dashboard_admin.php?update=sukses");

        } catch (Exception $e) {
            // Jika ada yang gagal, batalkan semua perubahan
            $conn->rollback();
            header("Location: dashboard_admin.php?update=gagal&error=" . urlencode($e->getMessage()));
        }
        
        $conn->close();
        exit();
    } else {
        // Jika data tidak lengkap, kembali dengan pesan error
        header("Location: dashboard_admin.php?error=datidaklengkap");
        exit();
    }
}

// Jika halaman diakses langsung tanpa metode POST, redirect saja
header("Location: dashboard_admin.php");
exit();
?>
