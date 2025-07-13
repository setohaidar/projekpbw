<?php
// assign_kurir_hadiah.php
session_start();
require_once 'db_connect.php';

// Keamanan: Hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['penukaran_id'], $_POST['kurir_id'], $_POST['user_id_penerima'])) {
    $penukaran_id = $_POST['penukaran_id'];
    $kurir_id = $_POST['kurir_id'];
    $user_id_penerima = $_POST['user_id_penerima'];
    $status_baru = 'Dalam Pengiriman';

    $conn->begin_transaction();
    try {
        // Update penukaran dengan kurir_id dan status baru
        $stmt_update = $conn->prepare("UPDATE penukaran_poin SET kurir_id = ?, status = ? WHERE id = ?");
        $stmt_update->bind_param("isi", $kurir_id, $status_baru, $penukaran_id);
        $stmt_update->execute();
        $stmt_update->close();

        // Notifikasi untuk user
        $pesan_user = "Kabar baik! Hadiah #{$penukaran_id} Anda sedang dalam perjalanan menuju alamat Anda.";
        $link_user = "status_penukaran_poin.php";
        $stmt_notif_user = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, link) VALUES (?, ?, ?)");
        $stmt_notif_user->bind_param("iss", $user_id_penerima, $pesan_user, $link_user);
        $stmt_notif_user->execute();
        $stmt_notif_user->close();

        // Notifikasi untuk kurir
        $pesan_kurir = "Anda mendapatkan tugas pengiriman hadiah baru dengan ID #{$penukaran_id}.";
        $link_kurir = "dashboard_kurir.php";
        $stmt_notif_kurir = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, link) VALUES (?, ?, ?)");
        $stmt_notif_kurir->bind_param("iss", $kurir_id, $pesan_kurir, $link_kurir);
        $stmt_notif_kurir->execute();
        $stmt_notif_kurir->close();

        $conn->commit();
        header("Location: admin_penukaran_hadiah.php?assign=sukses");

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: admin_penukaran_hadiah.php?assign=gagal");
    }

    $conn->close();
    exit();
}

header("Location: admin_penukaran_hadiah.php");
exit();
?>