<?php
// update_kurir_tugas.php (Versi Baru)
session_start();
require_once 'db_connect.php';

// Keamanan: Hanya kurir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kurir') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['tugas_id'], $_POST['tipe_tugas'], $_POST['status_baru'])) {
        $tugas_id = $_POST['tugas_id'];
        $tipe_tugas = $_POST['tipe_tugas'];
        $status_baru = $_POST['status_baru'];
        $kurir_id = $_SESSION['user_id'];
        
        $tabel = '';
        if ($tipe_tugas == 'penjemputan') {
            $tabel = 'pengajuan';
        } elseif ($tipe_tugas == 'pengiriman') {
            $tabel = 'penukaran_poin';
        } else {
            header("Location: dashboard_kurir.php?error=invalid_task");
            exit();
        }

        // Ambil data tugas saat ini untuk validasi
        $stmt_get_data = $conn->prepare("SELECT user_id, status, jenis_sampah, berat FROM $tabel WHERE id = ? AND kurir_id = ?");
        $stmt_get_data->bind_param("ii", $tugas_id, $kurir_id);
        $stmt_get_data->execute();
        $result_data = $stmt_get_data->get_result();
        if ($result_data->num_rows == 0) {
            header("Location: dashboard_kurir.php?error=unauthorized");
            exit();
        }
        $data_tugas = $result_data->fetch_assoc();
        $user_id_penerima = $data_tugas['user_id'];
        
        $conn->begin_transaction();
        try {
            // 1. Update status tugas di tabel pengajuan/penukaran
            $stmt_update = $conn->prepare("UPDATE $tabel SET status = ? WHERE id = ?");
            $stmt_update->bind_param("si", $status_baru, $tugas_id);
            $stmt_update->execute();
            $stmt_update->close();
            
            $poin_didapat = 0;
            // 2. Jika PENJEMPUTAN SAMPAH sudah SELESAI
            if ($tipe_tugas == 'penjemputan' && $status_baru == 'Selesai') {
                $berat_sampah = (float)$data_tugas['berat'];
                $jenis_sampah = $data_tugas['jenis_sampah'];
                $poin_didapat = $berat_sampah * 1000;

                // 2a. Tambah poin untuk pengguna
                $stmt_add_poin = $conn->prepare("UPDATE users SET poin = poin + ? WHERE id = ?");
                $stmt_add_poin->bind_param("di", $poin_didapat, $user_id_penerima);
                $stmt_add_poin->execute();
                $stmt_add_poin->close();

                // 2b. Tambah/Update stok di tabel stok_sampah (gudang virtual)
                $stmt_stok = $conn->prepare(
                    "INSERT INTO stok_sampah (jenis_sampah, total_berat) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE total_berat = total_berat + VALUES(total_berat)"
                );
                $stmt_stok->bind_param("sd", $jenis_sampah, $berat_sampah);
                $stmt_stok->execute();
                $stmt_stok->close();
            }

            // 3. Buat notifikasi untuk pengguna
            $pesan = "Status " . ($tipe_tugas == 'penjemputan' ? "pengajuan sampah" : "penukaran hadiah") . " #{$tugas_id} Anda telah diperbarui menjadi: {$status_baru}.";
            if ($poin_didapat > 0) {
                $pesan .= " Anda mendapatkan {$poin_didapat} poin!";
            }
            $link = ($tipe_tugas == 'penjemputan' ? "dashboard_pengguna.php?page=status_pengajuan" : "dashboard_pengguna.php?page=status_penukaran_poin");
            
            $stmt_notif = $conn->prepare("INSERT INTO notifikasi (user_id, pesan, link) VALUES (?, ?, ?)");
            $stmt_notif->bind_param("iss", $user_id_penerima, $pesan, $link);
            $stmt_notif->execute();
            $stmt_notif->close();

            $conn->commit();
            header("Location: dashboard_kurir.php?update=sukses");

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: dashboard_kurir.php?update=gagal&error=" . urlencode($e->getMessage()));
        }
        
        $conn->close();
        exit();
    }
}
header("Location: dashboard_kurir.php");
exit();
?>