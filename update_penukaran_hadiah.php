<?php
// Buat File Logika update_penukaran_hadiah.php
// File ini akan menangani semua aksi yang dilakukan Admin di halaman manajemen penukaran, seperti menyetujui, menolak, atau menugaskan kurir
session_start();
require_once 'db_connect.php';

// Keamanan: Hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['penukaran_id']) && isset($_POST['status_baru'])) {
        $penukaran_id = $_POST['penukaran_id'];
        $status_baru = $_POST['status_baru'];
        $kurir_id = isset($_POST['kurir_id']) ? $_POST['kurir_id'] : null;

        $conn->begin_transaction();
        try {
            // Ambil user_id dari penukaran untuk notifikasi
            $stmt_get_user = $conn->prepare("SELECT user_id FROM penukaran_poin WHERE id = ?");
            $stmt_get_user->bind_param("i", $penukaran_id);
            $stmt_get_user->execute();
            $result_user = $stmt_get_user->get_result();
            if ($result_user->num_rows === 0) {
                throw new Exception("ID Penukaran tidak ditemukan.");
            }
            $user_penerima = $result_user->fetch_assoc();
            $user_id_penerima = $user_penerima['user_id'];
            $stmt_get_user->close();

            // 1. Update status dan kurir_id (jika ada)
            if ($status_baru == 'Dalam Pengiriman' && $kurir_id) {
                $stmt_update = $conn->prepare("UPDATE penukaran_poin SET status = ?, kurir_id = ? WHERE id = ?");
                $stmt_update->bind_param("sii", $status_baru, $kurir_id, $penukaran_id);
            } else {
                // Untuk status 'Siap Dikirim' atau 'Ditolak'
                $stmt_update = $conn->prepare("UPDATE penukaran_poin SET status = ? WHERE id = ?");
                $stmt_update->bind_param("si", $status_baru, $penukaran_id);
            }
            $stmt_update->execute();
            $stmt_update->close();

            // 2. Buat notifikasi untuk pengguna
            $pesan = "Status penukaran hadiah #{$penukaran_id} Anda telah diperbarui menjadi: {$status_baru}.";
            
            // PERBAIKAN REKOMENDASI: Mengarahkan ke halaman di dalam dashboard
            $link = "dashboard_pengguna.php?page=status_penukaran_poin";
            
            $stmt_notif_user = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, link) VALUES (?, ?, ?)");
            $stmt_notif_user->bind_param("iss", $user_id_penerima, $pesan, $link);
            $stmt_notif_user->execute();
            $stmt_notif_user->close();

            // 3. Jika kurir ditugaskan, kirim notifikasi juga ke kurir
            if ($kurir_id) {
                $pesan_kurir = "Anda mendapatkan tugas pengiriman hadiah baru untuk penukaran #{$penukaran_id}.";
                $link_kurir = "dashboard_kurir.php";
                $stmt_notif_kurir = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, link) VALUES (?, ?, ?)");
                $stmt_notif_kurir->bind_param("iss", $kurir_id, $pesan_kurir, $link_kurir);
                $stmt_notif_kurir->execute();
                $stmt_notif_kurir->close();
            }

            $conn->commit();
            header("Location: admin_penukaran_hadiah.php?update=sukses");

        } catch (Exception $e) {
            $conn->rollback();
            // Redirect dengan pesan error untuk debugging
            header("Location: admin_penukaran_hadiah.php?update=gagal&error=" . urlencode($e->getMessage()));
        }
        
        $conn->close();
        exit();
    }
}

header("Location: admin_penukaran_hadiah.php");
exit();
?>