<?php
function tambahNotifikasi($conn, $user_id, $pesan) {
    $sql = "INSERT INTO notifikasi (user_id, pesan) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("is", $user_id, $pesan);
        $stmt->execute();
        $stmt->close();
    } else {
        // Handle error jika statement gagal
        error_log("Gagal menyiapkan statement untuk notifikasi: " . $conn->error);
    }
}
?>