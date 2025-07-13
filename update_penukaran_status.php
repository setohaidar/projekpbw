<?php
// update_penukaran_status.php
session_start();
require_once 'db_connect.php';

// Keamanan: Hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['penukaran_id'], $_POST['status_baru'], $_POST['user_id_penerima'])) {
    $penukaran_id = $_POST['penukaran_id'];
    $status_baru = $_POST['status_baru'];
    $user_id_penerima = $_POST['user_id_penerima'];

    $conn->begin_transaction();
    try {
        $pesan_notifikasi = "";
        
        if ($status_baru == 'Ditolak') {
            // Ambil detail penukaran untuk mengembalikan poin dan stok
            $stmt_get = $conn->prepare("SELECT hadiah_id, poin_digunakan FROM penukaran_poin WHERE id = ?");
            $stmt_get->bind_param("i", $penukaran_id);
            $stmt_get->execute();
            $result = $stmt_get->get_result();
            $penukaran = $result->fetch_assoc();
            $stmt_get->close();

            if ($penukaran) {
                // 1. Kembalikan poin ke user
                $stmt_poin = $conn->prepare("UPDATE users SET poin = poin + ? WHERE id = ?");
                $stmt_poin->bind_param("ii", $penukaran['poin_digunakan'], $user_id_penerima);
                $stmt_poin->execute();
                $stmt_poin->close();

                // 2. Kembalikan stok hadiah
                $stmt_stok = $conn->prepare("UPDATE hadiah SET stok = stok + 1 WHERE id = ?");
                $stmt_stok->bind_param("i", $penukaran['hadiah_id']);
                $stmt_stok->execute();
                $stmt_stok->close();
            }
        }
        
        // Update status penukaran
        $stmt_update = $conn->prepare("UPDATE penukaran_poin SET status = ? WHERE id = ?");
        $stmt_update->bind_param("si", $status_baru, $penukaran_id);
        $stmt_update->execute();
        $stmt_update->close();

        // Buat notifikasi untuk user
        if ($status_baru == 'Siap Dikirim') {
            $pesan_notifikasi = "Penukaran hadiah #{$penukaran_id} Anda telah disetujui dan akan segera disiapkan untuk pengiriman.";
        } else {
            $pesan_notifikasi = "Mohon maaf, penukaran hadiah #{$penukaran_id} Anda ditolak. Poin Anda telah dikembalikan.";
        }
        $link = "status_penukaran_poin.php";

        $stmt_notif = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, link) VALUES (?, ?, ?)");
        $stmt_notif->bind_param("iss", $user_id_penerima, $pesan_notifikasi, $link);
        $stmt_notif->execute();
        $stmt_notif->close();

        $conn->commit();
        header("Location: admin_penukaran_hadiah.php?update=sukses");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: admin_penukaran_hadiah.php?update=gagal");
    }
    
    $conn->close();
    exit();
}

header("Location: admin_penukaran_hadiah.php");
exit();
?>